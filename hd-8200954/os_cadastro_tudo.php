<?php

include_once('class/tdocs.class.php');

if(in_array($login_fabrica, array(11,172))){

  $codigo_interno_os       = $_GET["codigo_interno_os"];
  $pre_os_redirecionada    = $_GET["pre_os_redirecionada"];

  if(strlen(trim($codigo_interno_os)) > 0){

    list($codigo_interno_os, $codigo_interno_digitado, $produto_ci) = explode("_", $codigo_interno_os);

    if(strstr($codigo_interno_os, "|")){

      list($codigo_interno_os, $pre_os, $hd_chamado) = explode("|", $codigo_interno_os);

      if(strlen($codigo_interno_os) > 0 && $pre_os == "t" && strlen($hd_chamado) > 0){

        header("Location: os_cadastro.php?pre_os=t&hd_chamado={$hd_chamado}&hd_chamado_item=&codigo_interno_os=sim&pre_os_redirecionada=sim&codigo_interno_digitado={$codigo_interno_digitado}");
        exit();

      }

    }

  }

  if(isset($_GET["pre_os"]) && isset($_GET["codigo_interno_digitado"])){
    $codigo_interno_digitado = $_GET["codigo_interno_digitado"];
  }

}

header("Content-Type: text/html; charset=iso-8859-1");
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'classes/cep.php';
include_once('funcoes.php');
include_once 'class/AuditorLog.php';
include_once "plugins/fileuploader/TdocsMirror.php";

$TdocsMirror = new TdocsMirror();

if ($login_fabrica == 3) {
  include_once 'class/communicator.class.php';
}

$programa_insert = $_SERVER['PHP_SELF'];

if (in_array($login_fabrica, array(88,101))) {
  $limite_anexos_nf = 5;
}

$usam_valida_serie_bloqueada = array(3,11,172); // HD-2403711

/**
* @author William Castro <william.castro@telecontrol.com.br>
*
* hd-6568221
*/

if ($login_fabrica == 30) {

    if (!$_REQUEST['os']) {

      if (!isset($digita_os_consumidor) || $digita_os_consumidor == 'f') {
        header("Location: menu_inicial.php");
        exit;
      }

    } else {

      $sql = "SELECT tbl_os.consumidor_revenda
              FROM tbl_os
              WHERE tbl_os.fabrica = {$login_fabrica}
              AND tbl_os.os = {$_GET['os']}
              ORDER BY os DESC LIMIT 1";

      $res = pg_query ($con,$sql);

      if(pg_num_rows($res) > 0) {

        $tipo_os_consumidor_revenda = pg_fetch_result($res, 0, 'consumidor_revenda');
      }


      if ($tipo_os_consumidor_revenda == "C" && $digita_os_consumidor != "t") {
        header("Location: menu_inicial.php");
        exit;
      }

      if ($tipo_os_consumidor_revenda == "R" && !$login_posto_digita_os) {
        header("Location: menu_inicial.php");
        exit;
      }

    }
}

if (isset($novaTelaOs)) {
  header("Location: cadastro_os.php");
}

 /**
  * @author William Castro <william.castro@telecontrol.com.br>
  * hd-6639553 -> Box Uploader
  * verifica se tem anexo
  */
if ($fabricaFileUploadOS) {
  $os = $_REQUEST['os'];
  if (!empty($os)) {
      $tempUniqueId = $os;
      $anexoNoHash = null;
  } else if (strlen(getValue("anexo_chave")) > 0) {
      $tempUniqueId = getValue("anexo_chave");
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

/**
 * Area para colocar os AJAX
 */
if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {

    $estado = strtoupper($_POST["estado"]);
    $pais   = strtoupper($_POST["pais"]);

    if (!empty($pais) && $pais != "BR") {
        
        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                FROM tbl_cidade 
                WHERE UPPER(estado_exterior) = UPPER('{$estado}')
                AND UPPER(pais) = UPPER('{$pais}')
                ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
        }

    } else {

        if (array_key_exists($estado, $array_estados())) {
            $sql = "SELECT DISTINCT * FROM (
                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                        UNION (
                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                        )
                    ) AS cidade
                    ORDER BY cidade ASC";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $array_cidades = array();

                while ($result = pg_fetch_object($res)) {
                    $array_cidades[] = $result->cidade;
                }

                $retorno = array("cidades" => $array_cidades);
            } else {
                $retorno = array("error" => utf8_encode("Nenhuma cidade encontrada para o estado: {$estado}"));
            }
        } else {
            $retorno = array("error" => utf8_encode("Estado não encontrado"));
        }

    }

    exit(json_encode($retorno));
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $tipo       = filter_input(INPUT_POST,'tipo');
    $latlon     = filter_input(INPUT_POST,'latlon');
    $produto    = filter_input(INPUT_POST,'produto');
    $coord      = explode(",",$latlon);
    if ($tipo == "postoProximo") {
        $sql = "
            SELECT  DISTINCT
                    tbl_posto.posto,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_posto_fabrica.latitude AS lat,
                    tbl_posto_fabrica.longitude AS lng,
                    (111.045 * DEGREES(ACOS(COS(RADIANS(".$coord[0].")) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS(".$coord[1].")) + SIN(RADIANS(".$coord[0].")) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica   USING(posto)
            JOIN    tbl_posto_linha     USING(posto)
            JOIN    tbl_produto         USING(linha)
            WHERE   fabrica = $login_fabrica
            AND     tbl_produto.referencia = '$produto'
            AND     tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
      ORDER BY      distance
            LIMIT   1
        ";
//         echo $sql;
        $res = pg_query($con,$sql);

        $postoCoord     = pg_fetch_result($res,0,posto);
        $codPostoCoord  = pg_fetch_result($res,0,codigo_posto);
        $nomePostoCoord = pg_fetch_result($res,0,nome);
        $lat            = pg_fetch_result($res,0,lat);
        $lon            = pg_fetch_result($res,0,lng);
        $distancia      = pg_fetch_result($res,0,distance);

        if ($postoCoord == $login_posto) {
            echo json_encode(array("posto"=>"igual"));
        } else {
            echo json_encode(
                array(
                    "posto"     => "diferente",
                    "proximo"   => $postoCoord,
                    "codPosto"  => $codPostoCoord,
                    "nomePosto" => utf8_encode($nomePostoCoord),
                    "lat" => (float)$lat,
                    "lon" => (float)$lon,
                    "distancia" => (float)$distancia
                )
            );
        }
    }
    exit;
}

if (isset($_POST['ajax_nome_readonly'])) {
  
  $cpf = $_POST['cpf'];
  $cpf = str_replace(array("-",".","/"), "", $cpf);
  
  $sql = "SELECT nome, cpf FROM tbl_cliente WHERE cpf = '$cpf'";

  $res = pg_query($con,$sql);

  if (pg_num_rows($res) > 0) {
    exit(json_encode(["retorno" => true]));
  }
  exit(json_encode(["retorno" => false]));
} 

if (isset($_POST['ajax_verifica_garantia_adicional'])) {

  $referencia_produto = $_POST['referencia_produto'];

  $sqlGar = "SELECT tbl_produto.produto,
                    tbl_produto.garantia as garantia1,
                    tbl_produto.parametros_adicionais::jsonb->>'garantia2' as garantia2,
                    tbl_produto.parametros_adicionais::jsonb->>'garantia3' as garantia3
          FROM tbl_produto
          JOIN tbl_familia USING(familia)
          WHERE tbl_produto.fabrica_i = {$login_fabrica}
          AND UPPER(tbl_produto.referencia) = UPPER('{$referencia_produto}')
          AND ((tbl_produto.parametros_adicionais::jsonb->>'garantia2' IS NOT NULL AND tbl_produto.parametros_adicionais::jsonb->>'garantia2' != '0')
            OR (tbl_produto.parametros_adicionais::jsonb->>'garantia3' IS NOT NULL AND tbl_produto.parametros_adicionais::jsonb->>'garantia3' != '0'))";

  $resGar = pg_query($con, $sqlGar);

  if (pg_num_rows($resGar) > 0) {

    exit(json_encode([
      "retorno" => true,
      "garantia1" => pg_fetch_result($resGar, 0, 'garantia1'),
      "garantia2" => pg_fetch_result($resGar, 0, 'garantia2'),
      "garantia3" => pg_fetch_result($resGar, 0, 'garantia3')
    ]));
  } else {
    exit(json_encode(["retorno" => false]));
  }
}

if (isset($_POST['ajax_verifica_limite_numero_serie'])) {

    $referencia_produto = $_POST["produto_referencia"];
    $serie = $_POST["produto_serie"];
    $sqlProduto = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$referencia_produto'";
    $resProduto = pg_query($con, $sqlProduto);
    $produto = null;
    $msg_erro = false;

    if(pg_num_rows($resProduto) > 0){

        $produto = pg_fetch_result($resProduto, 0, "produto");
        $sql = "SELECT serie_inicial,serie_final FROM tbl_produto_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $serie_inicial = pg_fetch_result($res, 0, "serie_inicial");
            $serie_final = pg_fetch_result($res, 0, "serie_final");

            if($serie_inicial == 0 AND $serie > $serie_final){
               $msg_erro = "$referencia_produto - Produtos fabricados até o número de série: $serie_final";
            }

            if($serie_final == 0 AND $serie < $serie_inicial){
                $msg_erro = "$referencia_produto - Produtos fabricados a partir do número de série: $serie_inicial";
            }
        }
    }

    die($msg_erro);
}



function ativa_produto($produto, $os, $produto_serie, $posto) {
  global $con, $login_fabrica, $externalId;

  $sql_ativacao = " SELECT referencia, descricao
                    FROM tbl_produto
                    WHERE fabrica_i = $login_fabrica
                    AND ativo IS NOT TRUE
                    AND produto = $produto
                    AND parametros_adicionais::jsonb->>'ativacao_automatica' = 't'";
  $res_ativacao = pg_query($con, $sql_ativacao);

  if (pg_num_rows($res_ativacao) > 0) {
    $prod_ref  = pg_fetch_result($res_ativacao, 0, 'referencia');
    $prod_desc = pg_fetch_result($res_ativacao, 0, 'descricao');
    $valores_add  = json_encode(array("ativacao_automatica" => "f", "os_ativacao" => "$os"));

    $sql_update_ativacao = " UPDATE tbl_produto SET ativo = TRUE, parametros_adicionais = '$valores_add' WHERE produto =  $produto";
    $res_update_ativacao = pg_query($con, $sql_update_ativacao);

    if (strlen(pg_last_error()) > 0) {
      return false;
    } else {
      $data_hj = date("d/m/Y H:i");

      $sql_p = "SELECT tbl_os.sua_os,tbl_posto.nome, tbl_posto_fabrica.codigo_posto
		FROM tbl_os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_os.os = $os
		AND tbl_posto.posto = $posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica";
      $res_p = pg_query($con, $sql_p);
      $nome_p = pg_fetch_result($res_p, 0, 'nome');
      $cod_p  = pg_fetch_result($res_p, 0, 'codigo_posto');
      $sua_os  = pg_fetch_result($res_p, 0, 'sua_os');

      $assunto = " Ativação do produto $prod_ref - $prod_desc";
      $mensagem = "O Produto $prod_ref - $prod_desc, foi ativado na OS $sua_os lançada pelo posto $cod_p - $nome_p com o número de série $produto_serie na data $data_hj. ";
      $email = array('caio.nagorski@britania.com.br', 'jose.pedrini@britania.com.br', 'ricardo.roque@britania.com.br');

      $mailTc = new TcComm($externalId);

      $res = $mailTc->sendMail(
          $email,
          utf8_encode($assunto),
          utf8_encode($mensagem),
          'noreply@telecontrol.com.br'
      );
    }
    return true;
  } else {
    return true;
  }
}


function valida_celular($celular) {
  global $login_fabrica, $login_pais;

  if (strlen($celular) > 0 && $login_pais == "BR") {

    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

    $celular          = $phoneUtil->parse("+55".$celular, "BR");
    $isValid          = $phoneUtil->isValidNumber($celular);
    $numberType       = $phoneUtil->getNumberType($celular);
    $mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

    if (!$isValid || $numberType != $mobileNumberType) {
        return "Número de Celular inválido <br>";
    }

  }
}
$reclamacao_cliente = $_POST['reclamacao_cliente'];

if($_POST["verifica_pre_os_atlas"] == true){

  $referencia = $_POST["referencia"];
  $cpf = $_POST["cpf"];

  $retirar = array(".", "-");
  $cpf = str_replace($retirar, "", $cpf);
  $referencia = str_replace($retirar, "", $referencia);

  $data_inicial = date('Y-m-d', strtotime("-90 days"));
  $data_final = date('Y-m-d');

  $sql = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado_extra.abre_os
          FROM tbl_hd_chamado_extra
          INNER JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
          INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
          WHERE tbl_produto.referencia = '$referencia'
          AND tbl_hd_chamado_extra.cpf = '$cpf'
          AND tbl_hd_chamado_extra.posto = $login_posto
          AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
          AND tbl_hd_chamado_extra.abre_os = 't'
          AND tbl_hd_chamado_extra.os is null
          AND tbl_hd_chamado.fabrica = $login_fabrica ";
  $res = pg_query($con, $sql);

  if(pg_num_rows($res)>0){
      $hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');

      $retorno = json_encode(array("retorno" => true, "hd_chamado" => $hd_chamado));

  }else{
      $retorno = json_encode(array("retorno"=>"erro"));
  }

  echo $retorno;

  exit;
}

if($_GET['monta_defeitos'] ==  "sim"){
  $produto_referencia = $_GET['produto_referencia'];

  $sql_def_int = "SELECT
                    tbl_diagnostico.diagnostico,
                    tbl_diagnostico.ativo,
                    tbl_diagnostico.garantia,
                    tbl_defeito_reclamado.defeito_reclamado,
                    tbl_defeito_reclamado.descricao AS defeito_descricao,
                    tbl_defeito_reclamado.codigo AS defeito_codigo,
                    tbl_familia.descricao AS familia_descricao
                  FROM tbl_diagnostico
                  JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica
                  JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia
                  JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia
                  WHERE tbl_diagnostico.fabrica = $login_fabrica
                  AND tbl_diagnostico.defeito_constatado IS NULL
                  AND tbl_diagnostico.ativo
                  AND tbl_produto.referencia = '$produto_referencia'
                  ORDER BY familia_descricao, defeito_descricao ASC;";
  $res_def_int = pg_query($con, $sql_def_int);

  if(pg_num_rows($res_def_int) > 0){
    for ($i=0; $i <pg_num_rows($res_def_int) ; $i++) {
      $xdefeito_reclamado = pg_fetch_result($res_def_int, $i, 'defeito_reclamado');
      $xdefeito_reclamado_descricao = pg_fetch_result($res_def_int, $i, 'defeito_descricao');
      $xdefeito_reclamado_codigo = pg_fetch_result($res_def_int, $i, 'defeito_codigo');
      $option .= "<option value='$xdefeito_reclamado'>".utf8_encode($xdefeito_reclamado_descricao)."</option>";
    }
    exit(json_encode($option));
  }else{
    exit(json_encode(array("messageError" => utf8_encode("error"))));
  }

}

if($_GET['verifica_digita_os_posto'] == "true"){

    $sql_linha = " select codigo_linha, deslocamento from tbl_linha where linha = $linha and fabrica = $login_fabrica ";
    $res_linha = pg_query($con, $sql_linha);
    if(pg_num_rows($res_linha)> 0){
        $codigo_linha = pg_fetch_result($res_linha, 0, 'codigo_linha');
        $deslocamento = pg_fetch_result($res_linha, 0, 'deslocamento');
    }

    if($codigo_linha == "02"){
      $cond_digita = " AND JSON_FIELD('digita_os_portateis', parametros_adicionais) = 't'";
    }
    if($codigo_linha == "01"){
      $cond_digita = " AND JSON_FIELD('digita_os_fogo', parametros_adicionais) = 't'";
    }

    $sql_posto_fabrica = "SELECT posto
                          FROM tbl_posto_fabrica
                          WHERE posto = $login_posto
                          AND tbl_posto_fabrica.fabrica = $login_fabrica
                          $cond_digita";

    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

    if(pg_num_rows($res_posto_fabrica) > 0 ){
        echo json_encode(array("resultado" => "ok", "deslocamento" => "$deslocamento"));
    }else{
        echo json_encode(array("resultado" => "erro", "deslocamento" => "$deslocamento"));
    }

    exit;

}

if($_GET['monta_cidade'] == "sim"){ //hd_chamado=2909049
    $cnpj_revenda = $_GET['cnpj_revenda'];
	if($login_fabrica == 3) {
		$cond_revenda = " join tbl_revenda_fabrica on tbl_revenda_fabrica.cidade = tbl_cidade.cidade and fabrica = $login_fabrica join tbl_revenda using(revenda) ";
	}else{
        $cond_revenda = " JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade ";
	}
    $sql = "SELECT tbl_cidade.nome,
                    tbl_cidade.cidade
					FROM tbl_cidade
					$cond_revenda
                    WHERE tbl_revenda.cnpj = '$cnpj_revenda'";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $cidade_nome = pg_fetch_result($res, 0, 'nome');
        $option = "<option value='$cidade_nome'>$cidade_nome</option>";
    }
    echo "$option";
    exit;
}

if (in_array($login_fabrica, array(94))) {// Verifica se o posto é Revenda

    $sql = "SELECT tbl_posto_fabrica.posto, tbl_tipo_posto.tipo_revenda
    FROM tbl_posto_fabrica
    JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND (tbl_tipo_posto.tipo_revenda or tbl_tipo_posto.posto_interno)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tbl_posto_fabrica.posto = $login_posto";
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {
        $posto_revenda = true;
    }else{
        $posto_revenda = false;
    }
}

if($login_fabrica == 30 && $_GET["os"]){
    $os = $_GET["os"];
    //verifica se tem itens na OS
    $sqlQtdItem = "SELECT count(*) as qtd
             FROM tbl_os_produto
             JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
             where os=$os";
    $resQtde = pg_query($con,$sqlQtdItem);
    $qtd = pg_result($resQtde,0, 'qtd');

    //se houver itens, redireciona para página anterior
    if($qtd > 0){
        header("Location: os_item_new.php?os=$os");

    }
}

if($login_fabrica == 140 OR $login_fabrica == 117){

  include "class/log/log.class.php";
  $email_consumidor = new Log();

}

if (in_array($login_fabrica, array(94,141,144))) {// Verifica se o posto é Interno

    $sql = "SELECT posto
            FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
            AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

        $posto_interno = true;

    } else {

      $posto_interno = false;

    }
}

#HD 424887 - INICIO
/*

A variavel abaixo será para identificar as fábricas que terão o campo "Defeito_reclamado" sem integridade.
Por enquanto só a Fricon, quando precisar mais fábricas é só colocar adicionar nessa variável que funciona.

@since HD 911149 - Fricon agora tem relacionamento com familia
*/
$fabricas_defeito_reclamado_sem_integridade = array();

if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3_ge = new anexaS3('ge', (int) $login_fabrica); //Anexo garantia estendida para Elgin
    $S3_online = is_object($s3_ge);
}

if ($usaPreOS) {
  $fabrica_pre_os = $login_fabrica;
}

// if ($login_fabrica == 11 OR $login_fabrica == 126 OR $login_fabrica == 137 OR $login_fabrica == 3) {
if (in_array($login_fabrica,array(3, 11, 126, 137, 172))) {
    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $amazonTC = new AmazonTC("os", $login_fabrica);
}

$fabricas_image_uploader            = !in_array($login_fabrica, array(104, 126, 137));
$fabrica_fone_cons_obrigatorio      = in_array($login_fabrica, array(3, 81, 95, 114));
$fabrica_cnpj_revenda_obrigatorio   = in_array($login_fabrica, array(3, 15, 24, 51, 72, 81, 95, 114));
$fabrica_revenda_nao_obirgatoria    = in_array($login_fabrica, array(7, 14, 30, 42, 43 ,96, 122)) || (in_array($login_fabrica, array(11,172)) && $login_posto==20321);
$fabrica_ajax_produto_voltagem      = in_array($login_fabrica, array(81,101, 114,$fabrica_pre_os));
$fabrica_nota_fiscal_os_obrigatoria = in_array($login_fabrica, array(6, 14, 24, 81, 95, 114)) || ( in_array($login_fabrica, array(11,172)) && $login_posto != 20321);
$fabrica_aparencia_produto_select   = in_array($login_fabrica, array(20));

// Atendimento atualizado automáticamente para 'Resolvido' após abrir OS
$fabrica_preos_resolvido_automatico = in_array($login_fabrica, array(46,80,81, 114,123,124,125,126,127,128,129,131,134,136, $fabrica_pre_os));
unset($fabrica_preos_resolvido_automatico['90']);
//Carrega todos os dados da revenda ao verificar a PréOS
$dados_revenda_preOS    = in_array($login_fabrica, array(7,15,46,74,81,114,115,116,117,120,201,123,124,125,126,127,128,129,131,134,136,140, $fabrica_pre_os));
$cpf_obrigatorio        = array(7, 43, 45, 80); // Fábricas que exigem seja colocado o CPF na OS
//HD 3809394
$calcula_km             = (($login_fabrica == 1  AND $login_posto == 6359) OR
	($login_fabrica == 15 AND $login_posto == 6359) OR
	($login_fabrica == 24 AND $login_tipo_posto == 256) OR
	in_array($login_fabrica, array(3,30,46,50,56,57,72,74,85,88,90,91,92, 35,114,115,116,117,120,201,125,128,129,131,141,144))
);

$combo_tipo_atendimento = (
	($login_fabrica == 1  and $login_posto      == 6359) or
	($login_fabrica == 24 and $login_tipo_posto ==  256) or
	in_array($login_fabrica, array(3, 7, 15, 19, 20, 30, 35, 40, 46, 50, 56, 58, 72, 74, 85, 88, 90, 91, 92, 96,114,115,116,117,120,201,124,125,128,129,131,140,141,144))
); //hd_chamado=2704100 adicionada fabrica 124


if(isset($_POST['verifica_serie']) && $_POST['verifica_serie'] == "ok"){

  $produto_serie      = trim($_POST['serie']);
  $produto_referencia = trim($_POST['referencia']);

  $sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia' AND fabrica_i = $login_fabrica";
  $res_produto = pg_query($con, $sql_produto);

  if(pg_num_rows($res_produto) > 0){

    $produto = pg_fetch_result($res_produto, 0, 'produto');

    $sqlMascara = "SELECT trim(mascara) mascara FROM tbl_produto_valida_serie WHERE produto = $produto and fabrica = $login_fabrica";
    $qryMascara = pg_query($con, $sqlMascara);
    $qtde_mascara = pg_num_rows($qryMascara);
    $ok = true;
    $matchw = array(1);
    $serie_maior = 0;

    while ($fetch = pg_fetch_assoc($qryMascara)) {
      $mascara = $fetch['mascara'];
      $len = strlen($mascara);

      if (strlen($produto_serie) < $len OR strlen($produto_serie) > $len) {
        $serie_maior++;
        continue;
      }

      for ($i=0; $i < $len; $i++) {
        $mask = $mascara[$i];
        $toCheck = $produto_serie[$i];

        if ($mask == 'N') {
          preg_match('/\d/', $toCheck, $match);
        } elseif ($mask == 'L') {
          preg_match('/\D/', $toCheck, $match);
          preg_match('/\w/', $toCheck, $matchw);
        }

        if (empty($match) or empty($matchw)) {
          $ok = false;
          break;
        }

      }

    }

    if(!$ok OR ($qtde_mascara == $serie_maior)){
      echo "invalido";
    }else{
      echo "valido";
    }

  }

  exit;

}


//$login_fabrica = 80;
#HD 424887 - FIM

// ajax hd 342651
if ($_GET['consulta_split'] == 's') {

    $referencia = $_GET['referencia'];

    $sql = "SELECT linha FROM tbl_produto WHERE referencia = '" . $referencia . "'";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $ref = pg_result($res, 0, 0);
        if ($ref != 510) {
            echo (in_array($ref,array(4,335,623,915))) ? 't' : 'f';
        } else {
            $sql2 = "
                SELECT  descricao
                FROM    tbl_produto
                WHERE   linha       = $ref
                AND     referencia  = '$referencia'
                AND     descricao   ~ '^TV PH\\\\d+'
                AND     SUBSTR(descricao,6,2)::INTEGER >= 46
            ";
            $res2 = pg_query($con,$sql2);

            if (pg_num_rows($res2) > 0) {
                echo 't';
            } else {
                echo 'f';
            }
        }
    } else {
        fecho('produto.nao.encontrado', $con);
    }
    exit;
}

if ($_GET['verifica_reincidencia'] == 'sim') {

    $referencia = $_GET['produto'];
    $serie = $_GET['serie'];

    if(!empty($referencia)){
        $sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia' AND fabrica_i = $login_fabrica";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
          $produto = pg_result($res,0,'produto');
          $cond_produto = " AND tbl_os.produto = $produto ";
        }
    }

    $sql = "SELECT  tbl_os.sua_os                            ,
          tbl_os.consumidor_nome                                           ,
          tbl_os.consumidor_cpf                                            ,
          tbl_os.consumidor_cidade                                         ,
          tbl_os.consumidor_fone                                           ,
          tbl_os.consumidor_celular                                        ,
          tbl_os.consumidor_fone_comercial                                 ,
          tbl_os.consumidor_estado                                         ,
          tbl_os.consumidor_endereco                                       ,
          tbl_os.consumidor_numero                                         ,
          tbl_os.consumidor_complemento                                    ,
          tbl_os.consumidor_bairro                                         ,
          tbl_os.consumidor_cep                                            ,
          tbl_os.consumidor_email                                          ,
          tbl_os.revenda_cnpj                                              ,
          tbl_os.nota_fiscal                                               ,
          to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
          tbl_os.consumidor_revenda                                        ,
          tbl_os.aparencia_produto                                         ,
          tbl_os.acessorios                                                ,
          tbl_os.obs                                                       ,
          tbl_os.defeito_reclamado                                         ,
          tbl_produto.referencia                                           ,
          tbl_produto.descricao                                            ,
          tbl_produto.voltagem                                             ,
          tbl_defeito_reclamado.descricao AS desc_defeito

      FROM tbl_os
      LEFT JOIN tbl_produto           ON tbl_produto.produto       = tbl_os.produto
      JOIN      tbl_posto_fabrica     ON tbl_posto_fabrica.fabrica = $login_fabrica
                                     AND tbl_posto_fabrica.posto   = $login_posto
      LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado.defeito_reclamado
      WHERE tbl_os.fabrica = $login_fabrica
      AND tbl_os.posto = $login_posto
      AND tbl_os.serie = '$serie'
      AND tbl_os.consumidor_revenda = 'C'
      ORDER BY os DESC LIMIT 1";
    $res = pg_query ($con,$sql);

      if(pg_num_rows($res) > 0){

          $sua_os                    = pg_fetch_result($res, 0, 'sua_os');
          $consumidor_nome           = pg_fetch_result($res, 0, 'consumidor_nome');
          $consumidor_cpf            = pg_fetch_result($res, 0, 'consumidor_cpf');
          $consumidor_cidade         = pg_fetch_result($res, 0, 'consumidor_cidade');
          $consumidor_fone           = pg_fetch_result($res, 0, 'consumidor_fone');
          $consumidor_celular        = pg_fetch_result($res, 0, 'consumidor_celular');
          $consumidor_fone_comercial = pg_fetch_result($res, 0, 'consumidor_fone_comercial');
          $consumidor_estado         = pg_fetch_result($res, 0, 'consumidor_estado');
          $consumidor_endereco       = pg_fetch_result($res, 0, 'consumidor_endereco');
          $consumidor_numero         = pg_fetch_result($res, 0, 'consumidor_numero');
          $consumidor_complemento    = pg_fetch_result($res, 0, 'consumidor_complemento');
          $consumidor_bairro         = pg_fetch_result($res, 0, 'consumidor_bairro');
          $consumidor_cep            = pg_fetch_result($res, 0, 'consumidor_cep');
          $consumidor_email          = pg_fetch_result($res, 0, 'consumidor_email');
          $revenda_cnpj              = pg_fetch_result($res, 0, 'revenda_cnpj');
          $nota_fiscal               = pg_fetch_result($res, 0, 'nota_fiscal');
          $data_nf                   = pg_fetch_result($res, 0, 'data_nf');
          $consumidor_revenda        = pg_fetch_result($res, 0, 'consumidor_revenda');
          $aparencia_produto         = pg_fetch_result($res, 0, 'aparencia_produto');
          $referencia                = pg_fetch_result($res, 0, 'referencia');
          $descricao                 = pg_fetch_result($res, 0, 'descricao');
          $voltagem                  = pg_fetch_result($res, 0, 'voltagem');
          $acessorio                 = pg_fetch_result($res, 0, 'acessorio');
          $obs                       = pg_fetch_result($res, 0, 'obs');
          $defeito_reclamado         = pg_fetch_result($res, 0, 'defeito_reclamado');
          $desc_defeito              = pg_fetch_result($res, 0, 'desc_defeito');

          echo "ok|$consumidor_nome|$consumidor_cpf|$consumidor_cidade|$consumidor_fone|$consumidor_celular|$consumidor_fone_comercial|$consumidor_estado|$consumidor_endereco|$consumidor_numero|$consumidor_complemento|$consumidor_bairro|$consumidor_cep|$revenda_cnpj|$nota_fiscal|$data_nf|$consumidor_revenda|$aparencia_produto|$consumidor_email|$sua_os|$referencia|$descricao|$voltagem|$acessorio|$obs|$defeito_reclamado|$desc_defeito";

      } else{
        echo "no";
      }

  exit;
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

    $busca      = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q) > 2) {

        if ($tipo_busca == 'revenda') {

            if ($busca == 'nome') {

                $sql = "SELECT tbl_revenda_fabrica.cnpj,
                        CASE
                            WHEN UPPER(contato_razao_social) LIKE UPPER('%$q%') THEN
                                tbl_revenda_fabrica.contato_razao_social
                            ELSE
                                tbl_revenda_fabrica.contato_nome_fantasia
                            END AS nome_revenda
                        FROM tbl_revenda_fabrica
                        WHERE tbl_revenda_fabrica.fabrica = $login_fabrica
                        AND (UPPER(contato_razao_social) LIKE UPPER('%$q%') OR UPPER(contato_nome_fantasia) LIKE UPPER('%$q%'))";
            }else{

                $sql = "SELECT  tbl_revenda_fabrica.cnpj,
                                tbl_revenda_fabrica.contato_razao_social as nome_revenda
                        FROM tbl_revenda_fabrica
                        WHERE tbl_revenda_fabrica.fabrica = $login_fabrica
                        AND   cnpj LIKE '$q%'";

            }

            $res = pg_query($con,$sql);

            if (pg_num_rows ($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $cnpj = trim(pg_fetch_result($res, $i, 'cnpj'));
                    $nome = trim(pg_fetch_result($res, $i, 'nome_revenda'));

                    $cnpj_raiz  = trim(substr($cnpj,0,8));

                    echo "$cnpj|$nome|$cnpj_raiz";
                    echo "\n";

                }

            }

        }

    }

    exit;

}

?>
<?php

if ($_GET['verifica_familia'] == 'sim') {

    $prod_referencia = $_GET['produto_referencia_familia'];

    $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    $tipo_posto = pg_fetch_result($res, 0, 0);

    if (!in_array($login_fabrica, array(40)))
    {
        $sql = "select tbl_produto.produto,
                       tbl_familia.familia
                from tbl_produto
                join tbl_familia using(familia)
                where tbl_familia.paga_km is TRUE
                and tbl_familia.fabrica=$login_fabrica
                and tbl_produto.referencia='$prod_referencia' ";
    }
    else
    {
        $sql = "select tbl_familia.familia
                from tbl_produto
                join tbl_familia on tbl_familia.familia = tbl_produto.familia
                where tbl_produto.referencia = '$prod_referencia'
               ";
    }

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {

        if (!in_array($login_fabrica, array(40)))
        {
            header("Content-Type: text/html; charset=ISO-8859-1");

            echo "<option value=\"\"></option><option value=\"21\">01 - Garantia (Com Deslocamento)</option>";
            if ($tipo_posto == 174) echo "<option value=\"22\">02 - Instalação</option>";
            echo "<option value=\"23\">03 - Garantia (Sem Deslocamento)</option>";
        }
        else
        {
            echo pg_result($res,0,familia);
        }

    } else {

        header("Content-Type: text/html; charset=ISO-8859-1");
        echo "<option value=\"\"></option>";
        if ($tipo_posto == 174) echo "<option value=\"22\">02 - Instalação</option>";
        echo "<option value=\"23\">03 - Garantia (Sem Deslocamento)</option>";

    }

    exit;

}

if ($_GET['verifica_origem'] == 'sim') {
    $prod_referencia = $_GET['produto_referencia'];
    $sql = "SELECT upper(origem) AS origem
            FROM tbl_produto
            WHERE fabrica_i = $login_fabrica
            AND upper(referencia) = upper('$prod_referencia')";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){
        $origem = pg_fetch_result($res, 0, 'origem');

        echo ($origem == "NAC" OR empty($origem)) ? "NAC" : "IMP";
    }
    exit;
}

if (in_array($login_fabrica, array(11,172))) {
    session_start();
}

$fabrica_com_preOS = in_array($login_fabrica, array(2, 7, 11, 24, 30, 40, 46, 50, 52, 72, 74, 80, 81, 85, 90, 91, 96,115,116, 117, 120,201, 122, 123,124,125,126,127,128,129,131,134,136,172,$fabrica_pre_os) );

//HD 234135
if (in_array($login_fabrica, array(3,117))) {

    $usa_revenda_fabrica      = true;
    $revenda_fabrica_status   = $_POST["revenda_fabrica_status"];
    $revenda_fabrica_pesquisa = $_POST["revenda_fabrica_pesquisa"];

} else {

    $usa_revenda_fabrica = false;

}

if (!empty($_COOKIE['debug'])) $debug = $_COOKIE['debug'];

include_once 'helpdesk/mlg_funciones.php';

$bd_locacao = array(36,82,83,84,90);// Tipo Posto locação para Black & Decker

if ($login_fabrica == 1 and (in_array($login_tipo_posto, $bd_locacao))) {
    header("Location: os_cadastro_locacao.php");
    exit;
}

if ($login_fabrica == 28) {
    header("Location: os_consulta_lite.php");
    exit;
}

/*  MLG - 19/11/2009 - HD 171045 - Cont.
*   MLG - 03/12/2010 - HD 321132
*       Inicializa o array, variáveis e funções.
        Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
        Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
        Para saber se tem anexo:temNF($os, 'bool');
        Para saber se 2º anexo: temNF($os, 'bool', 2);
        Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
                                echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
                                echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include_once('anexaNF_inc.php');

if (!function_exists('date_to_timestamp')) {

    function date_to_timestamp($fecha='hoje') { // $fecha formato YYYY-MM-DD H24:MI:SS ou DD-MM-YYYY H24:MI:SS

        if ($fecha == "hoje") $fecha = date('Y-m-d H:i:s');

        list($date, $time)         = explode(' ', $fecha);
        list($year, $month, $day)  = preg_split('/[\/|\.|-]/', $date);

        if (strlen($year) == 2 and strlen($day) == 4) list($day,$year) = array($year,$day); // Troca a ordem de dia e ano, se precisar
        if ($time == "") $time = "00:00:00";

        list($hour, $minute, $second) = explode(':', $time);
        return mktime($hour, $minute, $second, $month, $day, $year);

    }

}

/* MLG HD 175044    */
/*  14/12/2009 - Alteração direta, colquei conferência de 'funcion exists', porque mesmo que o include
                 e 'exit' esteja antes da declaração da função, ela é declarada na primeira passagem
                 do interpretador. */
if (!function_exists('checaCPF')) {
    function checaCPF ($cpf,$return_str = true) {
        global $con, $login_fabrica;// Para conectar com o banco...
        $cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
        //  23/12/2009 HD 186382 - a função pula as pré-OS anteriores à hoje...
        if (($login_fabrica==52 or $login_fabrica == 30 or $login_fabrica == 88)  and
            strlen($_REQUEST['pre_os'])>0 and
            date_to_timestamp($_REQUEST['data_abertura']) < strtotime('2009-12-24')) return $cpf;
        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

        if(strlen($cpf) > 0){
            $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
            if ($res_cpf === false) {
                return ($return_str) ? pg_last_error($con) : false;
            }
        }
        return $cpf;
    }
}


if (!function_exists('checaFone')) {
    function checaFone ($telefone) {
        if($telefone == 'null' or empty($telefone)) {
            return false;
        }

        if (!preg_match('|\(?[1-9]{2}\)? ?9?[2-9]{1}[0-9]{3}\-?[0-9]{4}|', $telefone)) {
            return false;
        }
        for($n =0;$n<=9;$n++) {
            $verifica_fone = substr_count($telefone,"$n");
            if($verifica_fone >=10) {
                return false;
            }
        }
        return $telefone;
    }
}

/* HD 35521 */
if (strlen(trim($_GET['pre_os']))>0){

    $pre_os         = trim($_GET['pre_os']);
    $produto_serie  = trim($_GET['serie']);
    $hd_chamado     = trim($_GET['hd_chamado']);

    if(in_array($login_fabrica, array(11,172))){

      if(strlen($hd_chamado) > 0 && strlen($pre_os_redirecionada) == 0){

        $sql_fabrica = "SELECT fabrica FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
        $res_fabrica = pg_query($con, $sql_fabrica);

        if(pg_num_rows($res_fabrica) > 0){

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

            if($fabrica_os != $login_fabrica){

                $self = $_SERVER['PHP_SELF'];
                $self = explode("/", $self);

                unset($self[count($self)-1]);

                $page = implode("/", $self);
                $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
                $pageReturn = urlencode("http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?pre_os=t&serie=&hd_chamado={$hd_chamado}&hd_chamado_item=&codigo_interno_os={$codigo_interno_os}");

                $params = "?cook_admin=&cook_fabrica={$fabrica_os}&page_return={$pageReturn}";
                $page = $page.$params;

                header("Location: {$page}");
                exit;

            }

        }

      }

    }

} elseif (strlen(trim($_POST['pre_os']))>0) {

    $pre_os = trim($_POST['pre_os']);

    if(!empty($_POST['serie'])){
        $produto_serie  = trim($_POST['serie']);

    }else if(!empty($_POST["produto_serie"]) ){
        $produto_serie = trim($_POST['produto_serie']);
    }else{
        $produto_serie = "";
    }

    $hd_chamado = trim($_POST['hd_chamado']);

}

if ($pre_os == 't') {

    $sqllinha =    "SELECT tbl_linha.informatica
                FROM    tbl_posto_linha
                JOIN    tbl_linha USING (linha)
                WHERE   tbl_posto_linha.posto = $login_posto
                AND     tbl_linha.informatica = 't'
                AND     tbl_linha.fabrica = $login_fabrica
                LIMIT 1";

    $reslinha = pg_query($con,$sqllinha);

    if (pg_num_rows($reslinha) > 0) {
        $linhainf = trim(pg_fetch_result($reslinha, 0, 'informatica')); //linha informatica
    }

}

if (in_array($login_fabrica, array(24))) {
    // fn_valida_consumidor
    if($_POST['valida_consumidor_cpf'] == "ok" AND $_POST['ajax'] == true){
        $cpf_consumidor = str_replace(array(".","-"), "", $_POST['cpf_consumidor']);
        $nome_consumidor = $_POST['nome_consumidor'];

        $sql_cpf = "SELECT tbl_os.consumidor_nome
                        FROM tbl_os
                        WHERE tbl_os.fabrica = {$login_fabrica}
                            AND tbl_os.consumidor_cpf = '{$cpf_consumidor}'
                            AND tbl_os.consumidor_nome <> '{$nome_consumidor}'
                            AND tbl_os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '1 year') AND CURRENT_DATE ";
        $res_cpf = pg_query($con,$sql_cpf);

        if (pg_last_error() > 0) {
            $msg_error = "errosql|Problema na validação do CPF!";
        } else {
            if (pg_num_rows($res_cpf) == 0) {
                $msg_error = "ok|Validação ok!";
            } else {
                $nome_consumidor_ant = array();
                for ($i=0; $i < pg_num_rows($res_cpf); $i++) {
                    $nome_consumidor_ant[] = pg_fetch_result($res_cpf, $i, consumidor_nome) ;
                }
                $msg_error = "erro|CPF existente para Consumidor: ".implode(" / ", $nome_consumidor_ant)."\n\nDeseja continuar?";
            }
        }

        if(strlen($msg_error) > 0){
            echo $msg_error;
        }
        exit;
    }
}

if ($_GET["ajax"] == "sim") {

    $referencia = $_GET["produto_referencia"];

    $sql = "SELECT linha
            FROM tbl_produto
            JOIN tbl_linha USING(linha)
            WHERE fabrica  = $login_fabrica
            AND referencia ='$referencia'";

    $res   = pg_query($con,$sql);
    $linha = pg_fetch_result ($res,0,0);

    if ($linha == 3 AND $login_fabrica == 3) {
        echo "ok|Mascara: LLNNNNNNLNNL<br />
                L: Letra<br />
                N: Número";
    }

    exit;

}

if ($_GET["ajax"] == "true" AND $_GET["buscaInformacoes"] == "true") {

    $referencia = trim($_GET["produto_referencia"]);
    $serie      = trim($_GET["serie"]);

    if (strlen($referencia) > 0) {

        $sql = "SELECT produto, capacidade, divisao
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND referencia = '$referencia'";

        $res = @pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $produto    = trim(pg_fetch_result($res, 0, 'produto'));
            $capacidade = trim(pg_fetch_result($res, 0, 'capacidade'));
            $divisao    = trim(pg_fetch_result($res, 0, 'divisao'));

            if (strlen($serie) > 0) {

                $sql = "SELECT capacidade, divisao, versao
                        FROM tbl_os
                        WHERE fabrica  = $login_fabrica
                        AND   posto    = $login_posto
                        AND   produto  = $produto
                        AND   serie    = '$serie' ;";

                $res = @pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {

                    $capacidade = trim(pg_fetch_result($res, 0, 'capacidade'));
                    $divisao    = trim(pg_fetch_result($res, 0, 'divisao'));
                    $versao     = trim(pg_fetch_result($res, 0, 'versao'));

                    echo "ok|$capacidade|$divisao|$versao";
                    exit;

                }

            }

            echo "ok|$capacidade|$divisao|$versao";
            exit;

        }

    }

    echo "nao|nao";
    exit;

}
if (strlen($_GET["produto_referencia"]) > 0 AND $_GET["produto_troca"] == "sim") {

    $referencia = trim($_GET["produto_referencia"]);

    $sql  = "SELECT produto
            FROM tbl_produto
            JOIN tbl_linha USING(linha)
            WHERE fabrica = $login_fabrica
            AND   referencia ='$referencia'
            AND   troca_obrigatoria IS TRUE";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        echo "sim";
    }

    exit;

}

if ($_GET["ajax"] == "true" AND $_GET["buscaPreOS"] == "true") { #HD 38369


    $serie           = trim($_GET["serie"]);
    $hd_chamado      = trim($_GET["hd_chamado"]);
    $hd_chamado_item = trim($_GET["hd_chamado_item"]);

    if (strlen($hd_chamado_item) > 0) {
        $sql_and = " AND hd_chamado_item = $hd_chamado_item ";
    }
    if (strlen($serie) > 0 or strlen($hd_chamado) > 0) {
        if ((!in_array($login_fabrica,array(7,30,52,96)) AND $usaPreOS ) and strlen($hd_chamado) > 0) {

            $sql = "SELECT tbl_hd_chamado_extra.hd_chamado,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.endereco ,
                        tbl_hd_chamado_extra.numero ,
                        tbl_hd_chamado_extra.complemento ,
                        tbl_hd_chamado_extra.bairro ,
                        tbl_hd_chamado_extra.cep ,
                        tbl_hd_chamado_extra.fone ,
                        tbl_hd_chamado_extra.reclamado AS reclamado_historico ,
                        tbl_hd_chamado_extra.fone2 ,
                        tbl_hd_chamado_extra.celular ,
                        tbl_hd_chamado_extra.email ,
                        tbl_hd_chamado_extra.cpf ,
                        tbl_hd_chamado_extra.rg ,
                        tbl_cidade.nome                                    AS cidade_nome,
                        tbl_cidade.estado                                  AS estado,
                        tbl_produto.referencia                             AS produto_referencia,
                        tbl_produto.descricao                              AS produto_nome,
                        tbl_produto.voltagem                               As produto_voltagem,
                        tbl_defeito_reclamado.defeito_reclamado            AS defeito_reclamado,
                        tbl_defeito_reclamado.descricao                    AS defeito_reclamado_descricao,
                        tbl_hd_chamado_extra.defeito_reclamado_descricao   AS defeito_reclamado_descricao2,
                        to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
                        to_char(tbl_hd_chamado.data,'DD/MM/YYYY')          AS data_abertura,
                        to_char(current_date,'DD/MM/YYYY')                 AS data_atual,
                        tbl_hd_chamado_extra.nota_fiscal                   AS nota_fiscal,
                        tbl_hd_chamado_extra.os                            AS os,
                        tbl_os.sua_os                                      AS sua_os,
                        tbl_os.data_fechamento                             AS data_fechamento,
                        tbl_hd_chamado_extra.qtde_km,
                        tbl_hd_chamado.admin,
                        tbl_hd_chamado.cliente_admin,
                        /*HD HD 204082: Recuperar dados da revenda da pré-os*/
                        tbl_hd_chamado_extra.revenda_cnpj,
                        tbl_hd_chamado_extra.revenda_nome,
                        tbl_hd_chamado_extra.tipo_atendimento,
                        tbl_hd_chamado_extra.array_campos_adicionais
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra   ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_extra.hd_chamado
                LEFT JOIN tbl_produto       ON tbl_produto.produto        = tbl_hd_chamado_extra.produto
                LEFT JOIN tbl_cidade        ON tbl_cidade.cidade          = tbl_hd_chamado_extra.cidade
                LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                LEFT JOIN tbl_os            ON tbl_os.os = tbl_hd_chamado_extra.os
                WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                AND   tbl_hd_chamado_extra.posto         = $login_posto
                /* 425985: Tinha na condição o número de série e ninguém soube explicar porque, está desde sempre ai. Se alguma fábrica reclamar e for corrigir, falar com Ébano ou Tulio antes. */
                AND   tbl_hd_chamado_extra.hd_chamado = $hd_chamado
                ORDER BY tbl_hd_chamado.data DESC ";

        } else if (strlen($hd_chamado) > 0) {
            //HD 905951
            $sql = "SELECT    tbl_hd_chamado_extra.hd_chamado,
                        tbl_hd_chamado_extra.nome,
                        tbl_hd_chamado_extra.endereco ,
                        tbl_hd_chamado_extra.numero ,
                        tbl_hd_chamado_extra.complemento ,
                        tbl_hd_chamado_extra.bairro ,
                        to_char(tbl_hd_chamado_extra.data_nascimento,'DD/MM/YYYY') as data_nascimento,
                        tbl_hd_chamado_extra.cep ,
                        tbl_hd_chamado_extra.fone ,
                        tbl_hd_chamado_extra.fone2 ,
                        tbl_hd_chamado_extra.celular ,
                        tbl_hd_chamado_extra.email ,
                        tbl_hd_chamado_extra.cpf ,
                        tbl_hd_chamado_extra.rg ,
                        tbl_hd_chamado_extra.posto_nome AS ponto_referencia,
                        tbl_cidade.nome                 AS cidade_nome,
                        tbl_cidade.estado               AS estado,
                        tbl_produto.voltagem            AS produto_voltagem,
                        tbl_produto.referencia          AS produto_referencia,
                        tbl_produto.linha               AS linha_produto,
                        tbl_produto.descricao           AS produto_nome,";

            if($login_fabrica == 7){
                $sql .= " tbl_admin.nome_completo AS nome_dono_chamado, ";
            }

            if ($login_fabrica == 96){
                $sql .= "tbl_produto.referencia_fabrica                    AS referencia_fabrica,";
            }

            $sql .="
                        tbl_defeito_reclamado.defeito_reclamado            AS defeito_reclamado,
                        tbl_defeito_reclamado.descricao                    AS defeito_reclamado_descricao,
                        tbl_hd_chamado_item.defeito_reclamado_descricao    AS defeito_reclamado_descricao2,
                        TO_CHAR(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
                        TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')          AS data_abertura,
                        tbl_hd_chamado_extra.nota_fiscal                   AS nota_fiscal,
                        TO_CHAR(current_date,'DD/MM/YYYY')                 AS data_atual,
                        tbl_hd_chamado_extra.os                            AS os,
                        tbl_os.sua_os                                      AS sua_os,
                        tbl_os.data_fechamento                             AS data_fechamento,
                        tbl_hd_chamado_extra.qtde_km,
                        tbl_hd_chamado.admin,
                        tbl_hd_chamado.cliente_admin,
                        /*HD HD 204082: Recuperar dados da revenda da pré-os*/
                        tbl_hd_chamado_extra.revenda_cnpj,
                        tbl_hd_chamado_extra.revenda_nome,
                        tbl_hd_chamado_extra.tipo_atendimento
                FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_extra   ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_extra.hd_chamado
                JOIN tbl_hd_chamado_item    ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_item.hd_chamado
                LEFT JOIN tbl_produto       ON tbl_produto.produto        = tbl_hd_chamado_item.produto
                LEFT JOIN tbl_cidade        ON tbl_cidade.cidade          = tbl_hd_chamado_extra.cidade
                LEFT JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
                AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                LEFT JOIN tbl_os            ON tbl_os.os = tbl_hd_chamado_extra.os ";

                if($login_fabrica == 7){
                    $sql .= " LEFT JOIN tbl_admin on tbl_hd_chamado.admin = tbl_admin.admin ";
                }

                $sql .= " WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                $sql_and
                AND   tbl_hd_chamado_extra.posto         = $login_posto
                /* 425985: Tinha na condição o número de série e ninguém soube explicar porque, está desde sempre ai. Se alguma fábrica reclamar e for corrigir, falar com Ébano ou Tulio antes. */
                AND   tbl_hd_chamado_extra.hd_chamado = $hd_chamado
                ORDER BY tbl_hd_chamado.data DESC ";

        }
        if (strlen($hd_chamado) > 0) {
            $res  = @pg_query($con,$sql);
        }
        $sql2 = $sql;
        if ((pg_num_rows($res) == 0 or strlen($hd_chamado) == 0) and strlen($serie) > 0) {

            $sql = "SELECT  '' AS hd_chamado,
                            '' AS  nome,
                            '' AS  endereco ,
                            '' AS  numero ,
                            '' AS  complemento ,
                            '' AS  bairro ,
                            '' AS  cep ,
                            '' AS  fone ,
                            '' AS  fone2 ,
                            '' AS  email ,
                            to_char(current_date,'DD/MM/YYYY') as data_atual,
                            '' AS  cpf ,
                            '' AS  rg ,
                            ''                                 AS cidade_nome,
                            ''                                 AS estado,
                            tbl_produto.referencia             AS produto_referencia,";
            if ($login_fabrica == 96){
                $sql .= "   tbl_produto.referencia_fabrica     AS referencia_fabrica,";
            }
            $sql .="
                            tbl_produto.descricao              AS produto_nome,
                            tbl_produto.voltagem               AS produto_voltagem,
                            ''                                 AS defeito_reclamado,
                            ''                                 AS defeito_reclamado_descricao,
                            ''                                 AS data_nf,
                            ''                                 AS data_abertura,
                            ''                                 AS nota_fiscal,
                            ''                                 AS os,
                            ''                                 AS sua_os,
                            ''                                 AS data_fechamento,
                            /*HD HD 204082: Recuperar dados da revenda da pré-os*/
                            '' AS revenda_cnpj,
                            '' AS revenda_nome,
                            '' AS tipo_atendimento
                    FROM tbl_numero_serie
                    JOIN tbl_produto      ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_produto.fabrica_i = tbl_numero_serie.fabrica
                    JOIN tbl_linha        ON tbl_linha.linha        = tbl_produto.linha
                    WHERE tbl_numero_serie.fabrica = $login_fabrica
                    AND   tbl_linha.fabrica        = $login_fabrica
                    AND   upper(tbl_numero_serie.serie)         = '$serie'
                    ORDER BY tbl_numero_serie.data_venda DESC ";

            $res = @pg_query($con,$sql);

        }

        if (pg_num_rows($res) > 0) {
            $hd_chamado                   = trim(pg_fetch_result($res,0,'hd_chamado'));
            $consumidor_nome              = trim(pg_fetch_result($res,0,'nome'));
            $consumidor_endereco          = trim(pg_fetch_result($res,0,'endereco'));
            $consumidor_numero            = trim(pg_fetch_result($res,0,'numero'));
            $consumidor_complemento       = trim(pg_fetch_result($res,0,'complemento'));
            $consuidor_ponto_ref          = trim(pg_fetch_result($res,0,'ponto_referencia')); //HD 905951
            $consumidor_bairro            = trim(pg_fetch_result($res,0,'bairro'));
            $consumidor_cep               = trim(pg_fetch_result($res,0,'cep'));
            if( in_array($login_fabrica, array(11,59,172)) )  {
              $consumidor_fone              = trim(pg_fetch_result($res,0,'fone'));
              $consumidor_fone2             = trim(pg_fetch_result($res,0,'fone2'));
              $consumidor_celular           = trim(pg_fetch_result($res,0,'celular'));
            }else{
              $consumidor_fone              = trim(pg_fetch_result($res,0,'fone'));
              $consumidor_fone_comercial    = trim(pg_fetch_result($res,0,'fone2'));
              $consumidor_celular           = trim(pg_fetch_result($res,0,'celular'));
            }

            if($login_fabrica == 74){
              $data_nasc           = trim(pg_fetch_result($res,0,'data_nascimento'));
            }
            if (isFabrica(19,74)) {
              $linha_produto       = trim(pg_fetch_result($res,0,'linha_produto'));
            }
            $consumidor_email             = trim(pg_fetch_result($res,0,'email'));
            $consumidor_cpf               = trim(pg_fetch_result($res,0,'cpf'));
            $consumidor_cidade            = trim(pg_fetch_result($res,0,'cidade_nome'));
            $consumidor_estado            = trim(pg_fetch_result($res,0,'estado'));
            $produto_referencia           = trim(pg_fetch_result($res,0,'produto_referencia'));
            if ($login_fabrica == 96){
                $referencia_fabrica       = trim(pg_fetch_result($res,0,'referencia_fabrica'));
            }
            if ($fabrica_ajax_produto_voltagem) {
                $produto_voltagem         = trim(pg_fetch_result($res,0,'produto_voltagem'));
            }
            $produto_descricao            = trim(pg_fetch_result($res,0,'produto_nome'));
            $defeito_reclamado            = trim(pg_fetch_result($res,0,'defeito_reclamado'));
            $defeito_reclamado_descricao  = trim(pg_fetch_result($res,0,'defeito_reclamado_descricao'));
            $defeito_reclamado_descricao2 = trim(pg_fetch_result($res,0,'defeito_reclamado_descricao2'));
            $data_atual                   = trim(pg_fetch_result($res,0,'data_atual'));
            $data_abertura                = trim(pg_fetch_result($res,0,'data_abertura'));
            $data_nf                      = trim(pg_fetch_result($res,0,'data_nf'));
            $nota_fiscal                  = trim(pg_fetch_result($res,0,'nota_fiscal'));
            $qtde_km                      = trim(pg_fetch_result($res,0,'qtde_km'));
            $os                           = trim(pg_fetch_result($res,0,'os'));
            $sua_os                       = trim(pg_fetch_result($res,0,'sua_os'));
            $data_fechamento              = trim(pg_fetch_result($res,0,'data_fechamento'));
            $admin                        = trim(pg_fetch_result($res,0,'admin'));
            $cliente_admin                = trim(pg_fetch_result($res,0,'cliente_admin'));
            $tipo_atendimento             = trim(pg_fetch_result($res,0,'tipo_atendimento'));
            $nome_dono_chamado            = trim(pg_fetch_result($res,0,'nome_dono_chamado'));

            if ($login_fabrica <> 52 and $login_fabrica <> 30 and $login_fabrica <> 96) {
                $reclamado_historico = trim(pg_fetch_result($res, 0, 'reclamado_historico'));
            }

            //HD 204082: Carregar revenda do chamado
		        if ($login_fabrica >= 81 || $login_fabrica == 46 || $login_fabrica == 74 || $login_fabrica == 15) {
                $revenda_nome = trim(pg_fetch_result($res, 0, 'revenda_nome'));
                $revenda_cnpj = trim(pg_fetch_result($res, 0, 'revenda_cnpj'));
            }

            if($login_fabrica == 85){
                $campos_adicionais = pg_fetch_result($res,0,array_campos_adicionais);
                $aux_campos = json_decode($campos_adicionais);

                if(array_key_exists("consumidor_cpf_cnpj",$aux_campos)){
                    if($aux_campos['consumidor_cpf_cnpj'] == 'R'){
                        $consumidor_nome = $aux_campos['nome_fantasia'];
                    }
                }
            }

            if ($login_fabrica == 141) {
              $campos_adicionais = pg_fetch_result($res,0,'array_campos_adicionais');
              $aux_campos = json_decode($campos_adicionais, true);
              $obs_sac = utf8_decode($aux_campos['observacao_sac']);
            }

            /* Retorno:
                ok
                id do campo ## valor
                id do campo ## valor
                .
                A funções explode e coloca os valores nos campos da OS
            */
                //HD 204082: Carregar revenda do chamado
            if (strlen($os)==0 or strlen($data_fechamento)>0){

                header("Content-Type: text/html; charset=ISO-8859-1");

                $array_resposta = compact(
                    // Consumidor
                    'consumidor_nome','consumidor_endereco','consumidor_numero','consumidor_complemento',
                    'consumidor_bairro','consumidor_cep','consumidor_fone','consumidor_celular','consumidor_fone_comercial',
                    'consumidor_email','consumidor_cpf','consumidor_cidade', 'consumidor_estado',
                    'produto_referencia','produto_descricao','produto_voltagem','data_nf','nota_fiscal',
                    'revenda_nome','revenda_cnpj','tipo_atendimento','consumidor_fone_comercial','obs'
                );
                $array_resposta['consumidor_fone_recado'] = $consumidor_fone2;
                $array_resposta['data_nascimento']        = $data_nasc;

                if (isFabrica(15))
                    $array_resposta['revenda_cnpj_raiz'] = $revenda_cnpj;

                //HD 907550 - Mais dados da revenda
                if ($dados_revenda_preOS and strlen($revenda_cnpj) > 3) {
                    $sqlR = "SELECT endereco        AS revenda_endereco,
                                    tbl_revenda.numero          AS revenda_numero,
                                    tbl_revenda.complemento     AS revenda_complemento,
                                    tbl_revenda.cep             AS revenda_cep,
                                    tbl_cidade.nome AS revenda_cidade,
                                    tbl_revenda.fone            AS revenda_fone,
                                    tbl_revenda.bairro          AS revenda_bairro,
                                    tbl_cidade.estado          AS revenda_estado
                               FROM tbl_revenda
                               JOIN tbl_cidade USING(cidade)
                              WHERE cnpj = '$revenda_cnpj'";
                    $resR = pg_query($con, $sqlR);
                    $dados_Revenda = pg_fetch_assoc($resR, 0);
					if(pg_num_rows($resR) > 0) {
						$array_resposta = array_merge($array_resposta, $dados_Revenda);
					}
                    //pre_echo($dados_Revenda, $sqlR);
                }
                // Data de abertura padrão 'hoje'
                $array_resposta['data_abertura'] = is_date('hoje', '', 'EUR');
                // FIM HD 907550

                if ($login_fabrica == 141) {
                    $array_resposta['obs'] = $obs_sac;
                }

                if ($login_fabrica == 3) {
                    $array_resposta['obs'] = $reclamado_historico;
                }

                if ($login_fabrica == 96) {
                    $array_resposta['referencia_fabrica'] = $referencia_fabrica;
                }

                if ($fabrica_ajax_produto_voltagem) {
					$array_resposta["produto_voltagem"] =$produto_voltagem;
                }

                if (strlen($defeito_reclamado_descricao2) > 0) {
                    $array_resposta['defeito_reclamado_descricao'] = $defeito_reclamado_descricao2;
                }

                if (strlen($defeito_reclamado_descricao) > 0 and $login_fabrica <> 96) {
                    $array_resposta['defeito_reclamado_descricao'] = $defeito_reclamado_descricao;
                }

                if (strlen($defeito_reclamado) > 0) {
                    $array_resposta['defeito_reclamado'] = $defeito_reclamado;
                }
                if($login_fabrica == 74 ){
                  $array_resposta['linha_id'] = $linha_produto;
                }

                if ($login_fabrica == 80) {
                    $array_resposta['data_abertura'] = $data_atual;
                }

                if ($login_fabrica == 3) {
                    $array_resposta['data_abertura'] = $data_abertura;
                }

                if (isFabrica(30, 52, 96)) {
                    if (strlen($cliente_admin)) {

                        $sql_cliente = "SELECT  nome,
                                                cnpj,
                                                endereco,
                                                numero,
                                                bairro,
                                                cep,
                                                cidade,
                                                estado,
                                                fone
                                        FROM tbl_cliente_admin
                                        WHERE cliente_admin = $cliente_admin";

                        $res_cliente = pg_query($con,$sql_cliente);

                        $nome_cliente      = pg_fetch_result($res_cliente, 0, nome);
                        $cnpj_cliente      = pg_fetch_result($res_cliente, 0, cnpj);
                        $endereco_cliente  = pg_fetch_result($res_cliente, 0, endereco);
                        $numero_cliente    = pg_fetch_result($res_cliente, 0, numero);
                        $bairro_cliente    = pg_fetch_result($res_cliente, 0, bairro);
                        $cep_cliente       = pg_fetch_result($res_cliente, 0, cep);
                        $cidade_cliente    = pg_fetch_result($res_cliente, 0, cidade);
                        $estado_cliente    = pg_fetch_result($res_cliente, 0, estado);
                        $fone_cliente      = pg_fetch_result($res_cliente, 0, fone);

                    } else {

                        $nome_cliente      = "";
                        $cnpj_cliente      = "";
                        $endereco_cliente  = "";
                        $numero_cliente    = "";
                        $bairro_cliente    = "";
                        $cep_cliente       = "";
                        $cidade_cliente    = "";
                        $estado_cliente    = "";
                        $fone_cliente      = "";

                    }

                    $array_resposta['ponto_referencia'] = $consuidor_ponto_ref;
                    $array_resposta['admin']            = $admin;
                    $array_resposta['cliente_admin']    = $cliente_admin;
                    $array_resposta['data_abertura']    = $data_abertura;
                    $array_resposta['revenda_nome']     = $nome_cliente;
                    $array_resposta['revenda_cnpj']     = $cnpj_cliente;
                    $array_resposta['revenda_fone']     = $fone_cliente;
                    $array_resposta['revenda_endereco'] = $endereco_cliente;
                    $array_resposta['revenda_cep']      = $cep_cliente;
                    $array_resposta['revenda_bairro']   = $bairro_cliente;
                    $array_resposta['revenda_cidade']   = $cidade_cliente;
                    $array_resposta['revenda_estado']   = $estado_cliente;
                    $array_resposta['qtde_km']          = $qtde_km;

                }

            } else {
                echo "nao|Já existe uma OS em aberto para este número de série. ($sua_os)";
                exit;
            }

        }

    }

    $array_resposta['nao'] = 'nao';
    if($login_fabrica == 7 && strlen(trim($nome_dono_chamado)) > 0 ){
        $array_resposta['quem_abriu_chamado'] = $nome_dono_chamado;
    }
    // Formata a resposta
    foreach ($array_resposta as $k => $v) {
        $respostas[]= "$k##$v";
    }
    echo 'ok|'. implode('|', $respostas);
    unset($array_resposta, $respostas, $resposta);
    exit;

}

if ($_GET["ajax"] == "true" AND $_GET["buscaValores"] == "true") {

    $referencia = trim($_GET["produto_referencia"]);

    if (strlen($referencia) > 0) {

        $sql = "SELECT produto, capacidade, divisao
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND referencia ='$referencia'";

        $res = @pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {

            $produto = trim(pg_fetch_result($res, 0, 'produto'));

            $sql = "SELECT  taxa_visita,
                            hora_tecnica,
                            valor_diaria,
                            valor_por_km_caminhao,
                            valor_por_km_carro,
                            regulagem_peso_padrao,
                            certificado_conformidade
                    FROM    tbl_familia_valores
                    JOIN    tbl_produto USING(familia)
                    WHERE   tbl_produto.produto = $produto";

            $res = pg_query ($con,$sql);

            if (pg_num_rows($res) > 0) {

                $taxa_visita              = number_format(trim(pg_fetch_result($res,0,taxa_visita)),2,',','.');
                $hora_tecnica             = number_format(trim(pg_fetch_result($res,0,hora_tecnica)),2,',','.');
                $valor_diaria             = number_format(trim(pg_fetch_result($res,0,valor_diaria)),2,',','.');
                $valor_por_km_caminhao    = number_format(trim(pg_fetch_result($res,0,valor_por_km_caminhao)),2,',','.');
                $valor_por_km_carro       = number_format(trim(pg_fetch_result($res,0,valor_por_km_carro)),2,',','.');
                $regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,regulagem_peso_padrao)),2,',','.');
                $certificado_conformidade = number_format(trim(pg_fetch_result($res,0,certificado_conformidade)),2,',','.');

                /* HD 46784 */
                $sql = "SELECT  valor_regulagem, valor_certificado
                        FROM    tbl_capacidade_valores
                        WHERE   fabrica = $login_fabrica
                        AND     capacidade_de <= (SELECT capacidade FROM tbl_produto WHERE produto = $produto )
                        AND     capacidade_ate >= (SELECT capacidade FROM tbl_produto WHERE produto = $produto ) ";
                $res = pg_query ($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,valor_regulagem)),2,',','.');
                    $certificado_conformidade = number_format(trim(pg_fetch_result($res,0,valor_certificado)),2,',','.');
                }
                echo "ok|$taxa_visita|$hora_tecnica|$valor_diaria|$valor_por_km_carro|$valor_por_km_caminhao|$regulagem_peso_padrao|$certificado_conformidade";
                exit;
            }
            exit;
        }
    }
    echo "nao|nao";
    exit;
}

//HD 20682 20/6/2008
if($_GET["verifica_linha"]=="sim"){
    $referencia = $_GET["produto_referencia"];
    if (strlen($referencia)>0){
        $sql = "SELECT linha
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE fabrica  = $login_fabrica
                AND referencia ='$referencia' ";
        $res = @pg_query($con,$sql);
        if (pg_num_rows($res)>0){
            $linha = pg_fetch_result ($res,0,0);
            if($login_fabrica==3 AND $linha==335){
                echo "ok";
            }else{
                echo "nao";
            }
        }
    }
    exit;
}


if($ajax=='tipo_atendimento'){

    if(!empty($id) && $id!="undefined"){
        $sql = "SELECT tipo_atendimento,km_google
                FROM tbl_tipo_atendimento
                WHERE tipo_atendimento = $id
                AND   fabrica          = $login_fabrica";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)>0){

            $km_google = pg_fetch_result($res,0,km_google);
            if($km_google == 't'){
                echo "ok|sim";
            }else{
                echo "no|nao";
            }

        }
    }
     exit;
}

if($ajax=='valida_garantia'){
    $xdata_nf       = fnc_formata_data_pg(trim($data_nf));
    $xdata_abertura = fnc_formata_data_pg(trim($data_abertura));

    $sql = "SELECT  garantia,
                    produto
            FROM tbl_produto
            JOIN tbl_linha   USING(linha)
            WHERE referencia = '$produto_ref'
            AND   fabrica    = $login_fabrica";
    //echo $sql;

    $res = @pg_query($con,$sql);
    if(pg_num_rows($res)>0){

        $produto  = pg_fetch_result($res,0,produto);
        $garantia = pg_fetch_result($res,0,garantia);

        $sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date";
        $res = @pg_query($con,$sql);

        $garantia_menor = pg_fetch_result($res,0,0);
        $sql = "SELECT ($xdata_nf::date + (('50 months')::interval))::date";
        $res = @pg_query($con,$sql);
        $garantia_maior = pg_fetch_result($res,0,0);

        $xdata_abertura = str_replace("'","",$xdata_abertura);
        if($garantia_menor < $xdata_abertura){

            if($garantia_maior>$xdata_abertura){
                $liberar = 'true';
            }
        }

        if(isset($liberar)){
            echo "ok|sim";
        }else{
            echo "no|não";
        }
        exit;
    }else
        echo "no|não";
    exit;

}

#-------- Libera digitação de OS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
    $sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res = @pg_query($con,$sql);
    $distribuidor_digita = pg_fetch_result ($res,0,0);
    if (strlen ($posto) == 0) $posto = $login_posto;
}
#----------------------------------------------------------------

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar" && $login_fabrica != 74) {

  $verifica_pre_os = $_POST["pre_os"];

  if ($verifica_pre_os == "t") {
    $verifica_posto      = $login_posto;
    $verifica_hd_chamado = $_POST["hd_chamado"];

  	if(!empty($verifica_hd_chamado)){
  			$sql = "SELECT posto FROM tbl_hd_chamado_extra WHERE hd_chamado = $verifica_hd_chamado AND posto = $verifica_posto";
  			$res = pg_query($con, $sql);

  			if (!pg_num_rows($res)) {
  			  echo "<div style='width: 600px; font-weight: bold; padding-top: 10px; padding-bottom: 10px; text-align: center; margin: 0 auto; background-color: #FF0000; color: #FFFFFF;'>Não foi possível lançar a OS os dados não pertencem ao posto</div>";
  			  echo "<div style='width: 600px; text-align: center; margin: 0 auto;'><a href='menu_inicial.php'>Clique aqui para retornar para a tela inicial</a></div>";
  			  exit;
  			}
  	}
  }
}
//MLG 06/12/2010 - HD 326935 - Limitar por HTML e PHP o comprimento das strings para campos varchar(x).
$_POST['certificado_garantia']      = substr($_POST['certificado_garantia']     , 0, 30);
$_POST['consumidor_bairro']         = substr($_POST['consumidor_bairro']        , 0, 80);
$_POST['consumidor_celular']        = preg_replace("/\D/", "", substr($_POST['consumidor_celular']       , 0, 20));
$_POST['consumidor_cep']            = substr(preg_replace('/\D/', '', $_POST['consumidor_cep']) , 0, 8);
$_POST['consumidor_cpf']            = substr(preg_replace('/\D/', '', $_POST['consumidor_cpf']) , 0, 14);
$_POST['consumidor_cidade']         = substr($_POST['consumidor_cidade']        , 0, 70);
$_POST['consumidor_complemento']    = substr($_POST['consumidor_complemento']   , 0, 20);
$_POST['consumidor_email']          = substr($_POST['consumidor_email']         , 0, 50);
$_POST['consumidor_estado']         = substr($_POST['consumidor_estado']        , 0, 2);
$_POST['consumidor_fone']           = preg_replace("/[^0-9\(\)\-]/", "", substr($_POST['consumidor_fone']          , 0, 20));
$_POST['consumidor_fone_comercial'] = preg_replace("/[^0-9\(\)\-]/", "", substr($_POST['consumidor_fone_comercial'], 0, 20));
$_POST['consumidor_fone_recado']    = preg_replace("/[^0-9\(\)\-]/", "", substr($_POST['consumidor_fone_recado']   , 0, 20));
$_POST['consumidor_nome']           = substr($_POST['consumidor_nome']          , 0, 50);
$_POST['consumidor_nome_assinatura']= substr($_POST['consumidor_nome_assinatura'],0, 50);
$_POST['consumidor_numero']         = substr($_POST['consumidor_numero']        , 0, 20);
$_POST['consumidor_revenda']        = substr($_POST['consumidor_revenda']       , 0, 1);
$_POST['divisao']                   = substr($_POST['divisao']                  , 0, 20);
$_POST['nota_fiscal']               = substr($_POST['nota_fiscal']              , 0, 20);
$_POST['nota_fiscal_saida']         = substr($_POST['nota_fiscal_saida']        , 0, 20);
$_POST['os_posto']                  = substr($_POST['os_posto']                 , 0, 20);
$_POST['prateleira_box']            = substr($_POST['prateleira_box']           , 0, 10);
$_POST['quem_abriu_chamado']        = substr($_POST['quem_abriu_chamado']       , 0, 30);
$_POST['revenda_bairro']            = substr($_POST['revenda_bairro']           , 0, 80);
$_POST['revenda_cep']               = substr(preg_replace('/\D/', '', $_POST['revenda_cep'])    , 0, 8);
$_POST['revenda_cnpj']              = substr(preg_replace('/\D/', '', $_POST['revenda_cnpj'])   , 0, 14);
$_POST['revenda_complemento']       = substr($_POST['revenda_complemento']      , 0, 30);
$_POST['revenda_email']             = substr($_POST['revenda_email']            , 0, 50);
$_POST['revenda_endereco']          = substr($_POST['revenda_endereco']         , 0, 60);
$_POST['revenda_fone']              = preg_replace("/[^0-9\(\)\-]/", "", substr($_POST['revenda_fone'], 0, 20));
$_POST['revenda_nome']              = substr($_POST['revenda_nome']             , 0, 50);
$_POST['revenda_numero']            = substr($_POST['revenda_numero']           , 0, 20);
$_POST['rg_produto']                = substr($_POST['rg_produto']               , 0, 50);
$_POST['produto_voltagem']          = substr($_POST['produto_voltagem']         , 0, 20);
$_POST['produto_serie']             = substr($_POST['produto_serie']            , 0, 20);
$_POST['serie_reoperado']           = substr($_POST['serie_reoperado']          , 0, 20);
$_POST['sua_os']                    = substr($_POST['sua_os']                   , 0, 20);
$_POST['sua_os_offline']            = substr($_POST['sua_os_offline']           , 0, 20);
$_POST['tecnico_nome']              = substr($_POST['tecnico_nome']             , 0, 20);
$_POST['tipo_os_cortesia']          = substr($_POST['tipo_os_cortesia']         , 0, 20);
$_POST['type']                      = substr($_POST['type']                     , 0, 10);
$_POST['versao']                    = substr($_POST['versao']                   , 0, 20);
$_POST['natureza_servico']          = substr($_POST['natureza_servico']         , 0, 20);
$_POST['pac']                       = substr($_POST['pac']                      , 0, 13);
$_POST['veiculo']                   = substr($_POST['veiculo']                  , 0, 20);

$_POST = array_filter($_POST, 'anti_injection'); // Exclui todos os ítens do array que estiverem vazios depois de filtrar com a função anti_injection

// if($btn_acao and $login_posto = 6359) die(nl2br(print_r($_POST, true)));

$msg_erro = "";

/*============= HD 121247 VALIDAR CAMPOS DO CONSUMIDOR PARA FABRICA 51/GAMA=========*/
/*============= HD 137679 VALIDAR CAMPOS DO CONSUMIDOR PARA FABRICA 30/ESMALTEC=====*/

//HD-6871292
if($login_fabrica == 24) {
	$data_nf       = formata_data(trim($_POST['data_nf']));
	$numero_serie  = $_POST["numero_serie"];
	$prodRef = $_REQUEST['produto_referencia'];
	$sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '$prodRef' AND fabrica_i = $login_fabrica";
	$res_produto = pg_query($con, $sql_produto);

	if(pg_num_rows($res_produto) > 0 and !empty($data_nf)){

		$produto = pg_fetch_result($res_produto, 0 , 'produto');
		if ($login_fabrica == 24 and strlen($numero_serie) > 0 and !empty($produto)) {
			$sqlDataFabricacao = "SELECT data_fabricacao
				FROM   tbl_numero_serie
				WHERE  UPPER(serie) = UPPER('$numero_serie')
				AND	   produto = $produto
				AND    fabrica = $login_fabrica";
			$res  =  pg_query($con, $sqlDataFabricacao);

			if (pg_num_rows($res) > 0) {
				$data_fabricacao = pg_fetch_result($res, 0, 'data_fabricacao');

				if(strtotime($data_nf) < strtotime($data_fabricacao)) {
					$msg_erro .= "A Data da Compra deve ser maior que a Data de Fabricação.<br />";
				}
			}
		}
	}
}

if (($login_fabrica == 30 || $login_fabrica == 51 || $login_fabrica == 72 || $login_fabrica == 74) and $btn_acao == "continuar") {

    $validados = 0;

    $pre_os_verificacao = $_POST['pre_os'];

    /* Verifica se o Chamado possui OS */
    if($pre_os_verificacao == "t"){

      $hd_chamado         = $_POST['hd_chamado'];

      $sql_verificacao = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
      $res_verificacao = pg_query($con, $sql_verificacao);

      if(pg_num_rows($res_verificacao) > 0){

        $os_hd_chamado = pg_fetch_result($res_verificacao, 0, 'os');

        if(strlen($os_hd_chamado) > 0){

            $pendentes = "Já existe uma OS em aberto para este número - $hd_chamado";
            $validados = $validados +1;

        }

      }

    }

    $consumidor_nome_x = $_POST['consumidor_nome'];
    if ($login_fabrica == 51 or $login_fabrica == 74) {    // Esmaltec não pediu no HD137679 a obrigatoriedade destes campos
        $consumidor_fone_x = $_POST['consumidor_fone'];
        $consumidor_cep_x = $_POST['consumidor_cep'];
	$consumidor_cpf_x = $_POST['consumidor_cpf'];
    }
    if ($login_fabrica == 72) {//HD 249034
        $consumidor_cep_x = $_POST['consumidor_cep'];
    }
    $consumidor_endereco_x = $_POST['consumidor_endereco'];
    $consumidor_numero_x = $_POST['consumidor_numero'];
    $consumidor_bairro_x = $_POST['consumidor_bairro'];
    $consumidor_cidade_x = $_POST['consumidor_cidade'];
    $consumidor_estado_x = $_POST['consumidor_estado'];

    /**
    * @author William Castro <william.castro@telecontrol.com.br>
    * hd-6568221 - validação de campo obrigatório
    */

    if ($login_fabrica != 30) {

      if(strlen($consumidor_nome_x)<=0){
        $pendentes_arr[] = "Nome";
        $validados = $validados +1;
      }
    } else {

      if ($consumidor_revenda == "C" && strlen($consumidor_nome_x) <= 0) {
        $pendentes_arr[] = "Nome";
        $validados = $validados +1;
      }
    }

    if ($login_fabrica == 51 or $login_fabrica == 74) {    // Esmaltec não pediu no HD137679 a obrigatoriedade destes campos
        if(strlen($consumidor_fone_x)<=0){
            $pendentes_arr[] = "Fone";
            $validados = $validados +1;
        }
        if(strlen($consumidor_cep_x)<=0){
            $pendentes_arr[] = "CEP";
            $validados = $validados +1;
        }

	if(strlen($consumidor_cpf_x)<=0){
            $pendentes_arr[] = "CPF/CNPJ";
            $validados = $validados +1;
        }
    }
    if ($login_fabrica == 72) {//HD 249034
        if(strlen($consumidor_cep_x)<=0){
            $pendentes_arr[] = "CEP";
            $validados = $validados +1;
        }
    }

    /**
    * @author William Castro <william.castro@telecontrol.com.br>
    * hd-6568221 - validação de campo obrigatório
    */

    if ($login_fabrica != 30) {

      if(strlen($consumidor_endereco_x)<=0){
        $pendentes_arr[] = "Endereço";
        $validados = $validados +1;
      }
    } else {

      if ($consumidor_revenda == "C" && strlen($consumidor_endereco_x) <= 0) {
        $pendentes_arr[] = "Endereço";
        $validados = $validados +1;
      }
    }

    if ($login_fabrica != 30) {

      if(strlen($consumidor_numero_x)<=0){
        $pendentes_arr[] = "Número";
        $validados = $validados +1;
      }
    } else {

      if ($consumidor_revenda == "C" && strlen($consumidor_numero_x) <= 0) {
        $pendentes_arr[] = "Número";
        $validados = $validados +1;
      }
    }

    if ($login_fabrica != 30) {

      if(strlen($consumidor_bairro_x)<=0){
        $pendentes_arr[] = "Bairro";
        $validados = $validados +1;
      }
    } else {

      if ($consumidor_revenda == "C" && strlen($consumidor_bairro_x) <= 0) {
        $pendentes_arr[] = "Bairro";
        $validados = $validados +1;
      }
    }

    if ($login_fabrica != 30) {

      if(strlen($consumidor_cidade_x)<=0){
        $pendentes_arr[] = "Cidade";
        $validados = $validados +1;
      }
    } else {

      if ($consumidor_revenda == "C" && strlen($consumidor_cidade_x) <= 0) {
        $pendentes_arr[] = "Cidade";
        $validados = $validados +1;
      }
    }

    if ($login_fabrica != 30) {

      if(strlen($consumidor_estado_x)<=0){
        $pendentes_arr[] = "Estado";
        $validados = $validados +1;
      }
    } else {

      if ($consumidor_revenda == "C" && strlen($consumidor_estado_x) <= 0) {
        $pendentes_arr[] = "Estado";
        $validados = $validados +1;
      }
    }

    $dn_erro = '';

    if ($login_fabrica == 74) {
        if (empty($_POST['consumidor_email'])) {
            $pendentes_arr[] = "Email";
            $validados++;
        }

        $consumidor_cpf_xy = preg_replace(array('[\.]', '[\/]', '[-]'), '', $consumidor_cpf_x);
	$data_nascimento = $_POST['data_nascimento'];
        if (strlen($consumidor_cpf_xy) < 14 and empty($data_nascimento)) {
            $pendentes_arr[] = "Data de Nascimento";
            $validados++;
        } elseif (!empty($data_nascimento)) {
            $dn = explode("/", $data_nascimento);

            $d = (int) $dn[0];
            $m = (int) $dn[1];
            $y = (int) $dn[2];

            if (!checkdate($m, $d, $y) OR strlen($y) < 4) {
                $dn_erro = "Data de Nascimento inválida.<br>";
            }
        }
    }

    if(count($pendentes_arr) > 0) {
        $pendentes = implode(", ",$pendentes_arr);
        if(count($pendentes_arr) == 1)
            $pendentes = 'O campo '. $pendentes . ' é obrigatório <br />';
        else
            $pendentes = 'Os campos '. $pendentes . ' são obrigatórios. <br />';
    }

    if ($validados == 0){
        $msg_erro = "";
    }else{
        $msg_erro = $pendentes;
    }
    $validados = 0;
    $pendentes = "";

    $msg_erro .= $dn_erro;
}

if(in_array($login_fabrica, array(35, 101))){
    if($login_fabrica == 35){
        $qtdDias = 5;
    }elseif($login_fabrica == 101){
        $qtdDias = 7;
    }

    if(strlen($_POST["data_abertura"]) > 0){
        list($d, $m, $a) = explode("/", $_POST["data_abertura"]);
        $data_abertura = $a."-".$m."-".$d;
        $sete_dias     = date("Y-m-d", strtotime("-$qtdDias day", strtotime(date("Y-m-d"))));

        if(strtotime($sete_dias) > strtotime($data_abertura)){
            $msg_erro .= "A Data de Entrada não pode ser inferior a $qtdDias dias <br />";
        }
    }
}

if($login_fabrica == 15){
  $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
  $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
  if (pg_num_rows($resParametrosAdicionais) > 0) {
    $parametrosAdicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais"), true);
    extract($parametrosAdicionais);
    if ($nf_obrigatorio != 't') {
      $ob_nf = "display: none;";
    }else{
      $ob_nf = "";
    }
  }
}

/*============= HD 121247 VALIDAR CAMPOS DO CONSUMIDOR PARA FABRICA 51/GAMA=========*/

if ($btn_acao == "continuar") {

  $os = $_POST['os'];
  $imprimir_os = $_POST["imprimir_os"];
  $garantia_lorenzetti = $_POST['garantia_lorenzetti'];

  if (in_array($login_fabrica, array(40))){
    $familia     = $_POST['familia'];

    if (!empty($familia)){
        $unidade_cor = $_POST['unidade_cor'];

        if (empty($unidade_cor)){
            $msg_erro .= "Erro: Selecione a Cor da Unidade.<br>";
        }
    }

    if(strlen($consumidor_fone) == 0){
        $msg_erro .= "Erro: Digite  o telefone do consumidor<br />";
    }
  }

  if($login_fabrica == 90){
      if(strlen($consumidor_fone) == 0){
        $msg_erro .= "Digite  o telefone do consumidor<br />";
    }
  }

  /* if ($login_fabrica == 15 AND $nf_obrigatorio == 't' AND (empty($_FILES["foto_nf"]["name"])) ) {
    $msg_erro .= "Por favor inserir anexo da Nota Fiscal <br />";
  } */


  if( in_array($login_fabrica, array(11,172)) && (empty($_FILES["foto_nf"]["name"]))){
      $msg_erro .= "Por favor inserir anexo da Nota Fiscal <br />";
  }

  if ($fabricas_validam_campos_telecontrol) {
    if (in_array($login_fabrica, [124])) {
      $prodRef = $_REQUEST['produto_referencia'];

      $qProdNS = "
        SELECT
          numero_serie_obrigatorio
        FROM tbl_produto
        WHERE referencia = '{$prodRef}'
        AND fabrica_i = {$login_fabrica}
      ";
      $rProdNS = pg_query($con, $qProdNS);
      $prodNSObrigatorio = pg_fetch_result($rProdNS, 0, 'numero_serie_obrigatorio');

      if ($prodNSObrigatorio == 't') {
        $campos_telecontrol[$login_fabrica]['tbl_os']['produto_serie']['obrigatorio'] = 0;
      }
    }

    $msg_erro .= validaCamposOs($campos_telecontrol[$login_fabrica]['tbl_os'], $_REQUEST);
  }

  if ($login_fabrica == 91) {//HD 682454

    $val_data_nf         = strtotime(implode('-',array_reverse(explode('/',trim($_POST['data_nf'])))));
    $val_data_fabricacao = strtotime(implode('-',array_reverse(explode('/',trim($_POST['data_fabricacao'])))));

    if ($val_data_nf < $val_data_fabricacao) {
        $msg_erro .= "Erro: A data de compra não pode ser inferior a data de fabricação.<br />";
    }

  }

  //HD1078212
  if ( in_array($login_fabrica, array(11,172)) && $consumidor_revenda == 'R'){
      $mdata_abertura = fnc_formata_data_pg($_POST['data_abertura']);
      $mano = (int)substr($mdata_abertura,1,4);
      $mmes = (int)substr($mdata_abertura,6,2);
      $mdia = (int)substr($mdata_abertura,9,2);;

      if(!checkdate($mmes, $mdia, $mano)) {
          $msg_erro = "Data de abertura inválida.<br/>";
      }
      $mdata_nf = fnc_formata_data_pg($_POST['data_nf']);
      $mano = (int)substr($mdata_nf,1,4);
      $mmes = (int)substr($mdata_nf,6,2);
      $mdia = (int)substr($mdata_nf,9,2);;

      if(!checkdate($mmes, $mdia, $mano)) {
          $msg_erro = "Data de nota fiscal inválida.<br/>";
      }
      if( in_array($login_fabrica, array(11,172)) && $mdata_abertura != 'null' && $mdata_nf != 'null' ) { // HD 689217
          list($di, $mi, $yi) = explode("/", $_POST['data_abertura']);
          list($df, $mf, $yf) = explode("/", $_POST['data_nf']);

          if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf) ){
              $msg_erro = "Data Inválida <br />";
          }
          $maux_data_inicial = "$yf-$mf-$df";
          $maux_data_final = "$yi-$mi-$di";
          if (strtotime($maux_data_inicial.'+ 15 days') < strtotime($maux_data_final) ) {
              $msg_erro = 'O intervalo entre as datas não pode ser maior que 15 dias. <br />';
          }
      }
  }



  $sua_os_offline = $_POST['sua_os_offline'];

  if (strlen (trim ($sua_os_offline)) == 0) {
      $sua_os_offline = 'null';
  } else {
      $sua_os_offline = "'" . trim ($sua_os_offline) . "'";
  }

  $sua_os = $_POST['sua_os'];
  if (strlen (trim ($sua_os)) == 0) {

      $sua_os = 'null';
      //hd 4617
      if ($pedir_sua_os == 't' AND $login_fabrica<>5 AND $login_fabrica <> 86 and $login_fabrica < 101) {
          $msg_erro .= "Erro: Digite o número da OS Fabricante. <br />";
      }

  } else {

      //ALTERAR DIA 04/01/2007 - WELLINGTON
      if (!in_array($login_fabrica,array(1,3,5,11,172))) {

          if (strlen($sua_os) < 7) {
              $sua_os = str_pad($sua_os, 7, '0', STR_PAD_LEFT);
              // 30/11/09        $sua_os = "000000" . trim ($sua_os);
              // MLG            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
          }
          # inserido pelo Ricardo - 04/07/2006
          //hd 4617 - retirar posto teste
          if ($login_fabrica == 3 and 1==2) {
              if (is_numeric($sua_os)) {
                  // retira os ZEROS a esquerda
                  $sua_os = intval(trim($sua_os));
              }
          }

          #            if (strlen($sua_os) > 6) {
          #                $sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
          #            }
          #  CUIDADO para OS de Revenda que já vem com = "-" e a sequencia.
          #  fazer rotina para contar 6 caracteres antes do "-"
      }

      $sua_os = "'$sua_os'" ;

  }

  ##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####
  //Conforme chamado: 390975 - Fazer com que os campos que a cliente solicitou sejam obrigatórios para a Orbis e para fabrica > 96 (novas)
  //HD 413556 - LeaderShip também (95)
  if( ($login_fabrica == 86 || $login_fabrica == 88 || $login_fabrica == 95 || $login_fabrica > 96) && !in_array($login_fabrica, array(172)) ) {
      $produto_serie               = trim($_POST['produto_serie']);
      $nota_fiscal                 = trim($_POST['nota_fiscal']);
      $data_nf                     = trim($_POST['data_nf']);
      $consumidor_nome             = trim($_POST['consumidor_nome']);
      $consumidor_fone             = trim($_POST['consumidor_fone']);
      $consumidor_endereco         = trim($_POST['consumidor_endereco']);
      $consumidor_cidade           = trim($_POST['consumidor_cidade']);
      $consumidor_estado           = trim($_POST['consumidor_estado']);
      $defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);
      $data_abertura               = trim($_POST['data_abertura']);

      if($login_fabrica != 86){
          if(strlen($produto_serie) == 0 && $login_fabrica < 104){
              $msg_erro .= " Digite  o número de série do produto<br />";
          }

          if(strlen($nota_fiscal) == 0){
              $msg_erro .= " Digite  o número da Nota Fiscal<br />";
          }


          if(strlen($data_nf) == 0){
              $msg_erro .= " Digite a data de compra <br />";
          }else{
              list($di, $mi, $yi) = explode("/", $data_nf);
              if(!checkdate($mi,$di,$yi))
                  $msg_erro .= "Data de Compra Inválida<br />";
              else{
                  $_data = "$yi-$mi-$di";
                  if($_data > date('Y-m-d'))
                      $msg_erro .= "Data de Compra Inválida<br />";

              }
          }

          if(strlen($data_abertura) > 0){
              list($di, $mi, $yi) = explode("/", $data_abertura);
              if(!checkdate($mi,$di,$yi))
                  $msg_erro .= "Data de Abertura Inválida<br />";
              else{
                  $_data = "$yi-$mi-$di";
                  if($_data > date('Y-m-d'))
                      $msg_erro .= "Data de Abertura Inválida<br />";

              }

              if ($login_fabrica == 123) {//valida data de abertura tem que ser igual a data atual
                if (date_to_timestamp($data_abertura) != mktime(0, 0, 0, date("m"), date("d"), date("Y"))) {
                    $msg_erro .= "Data de Abertura tem que ser igual a data atual!<br />";
                }
              }
          }
      }

      if(strlen($consumidor_nome) == 0){
          $msg_erro .= " Digite  o nome do consumidor<br />";
      }

      if(strlen($consumidor_fone) == 0 && $login_fabrica != 123){
          $msg_erro .= "Digite  o telefone do consumidor<br />";
      }

      if(strlen($consumidor_celular) == 0 && $login_fabrica == 123){
          $msg_erro .= "Digite o celular do consumidor<br />";
      }      

      if ($login_fabrica == 86 || $login_fabrica == 95) { //HD 413556
          if(strlen($consumidor_endereco) == 0){
              $msg_erro .= " Digite o endereço do consumidor<br />";
          }

          if(strlen($consumidor_cidade) == 0){
              $msg_erro .= "Digite a cidade do consumidor<br />";
          }

          if(strlen($consumidor_estado) == 0){
              $msg_erro .= "Selecione o estado do consumidor<br />";
          }

          if(strlen($defeito_reclamado_descricao) == 0 && $login_fabrica != 95 && $login_fabrica != 86){
              $msg_erro .= 'Digite  o defeito reclamado<br />';
          }
      }
  }

  if($login_fabrica == 19){
      if (empty($consumidor_cpf) && $consumidor_revenda == 'C') {
          $msg_erro .= 'Digite o CPF/CNPJ do consumidor.<br>';
      }
  }

  if ($login_fabrica == 72) {
      if(strlen($consumidor_fone) == 0){
          $msg_erro .= "Digite  o telefone do consumidor<br />";
      }
      if (empty($consumidor_cpf) && $consumidor_revenda == 'C') {
          $msg_erro .= 'Digite o CPF/CNPJ do consumidor.<br>';
      }
  }
  if(strlen($defeito_reclamado) == 0 && in_array($login_fabrica,[120,201])) {
    $msg_erro .= 'Escolha um defeito reclamado<br />';
  }

  /* Valida Série */
  if(in_array($login_fabrica, array(140,141))){

    $produto_serie = $_POST['produto_serie'];

    if(empty($produto_serie)){
      $msg_erro .= "Por favor digite o número de série <br />";
    }else{

      $sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '$produto_referencia' AND fabrica_i = $login_fabrica";
      $res_produto = pg_query($con, $sql_produto);

      if(pg_num_rows($res_produto) > 0){

        $produto = pg_fetch_result($res_produto, 0, 'produto');

        $sqlMascara = "SELECT trim(mascara) mascara FROM tbl_produto_valida_serie WHERE produto = $produto and fabrica = $login_fabrica";
        $qryMascara = pg_query($con, $sqlMascara);
        $qtde_mascara = pg_num_rows($qryMascara);
        $ok = true;
        $matchw = array(1);
        $serie_maior = 0;

        while ($fetch = pg_fetch_assoc($qryMascara)) {
          $mascara = $fetch['mascara'];
          $len = strlen($mascara);

          if (strlen($produto_serie) < $len OR strlen($produto_serie) > $len) {
            $serie_maior++;
            continue;
          }

          for ($i=0; $i < $len; $i++) {
            $mask = $mascara[$i];
            $toCheck = $produto_serie[$i];

            if (strtoupper($mask) == 'N') {
              preg_match('/\d/', $toCheck, $match);
            } elseif (strtoupper($mask) == 'L') {
              preg_match('/\D/', $toCheck, $match);
              preg_match('/\w/', $toCheck, $matchw);
            }

            if (empty($match) or empty($matchw)) {
              $ok = false;
              break;
            }

          }

        }

        if(!$ok OR ($qtde_mascara == $serie_maior)){
          $serie_auditoria = "TRUE";
          // $msg_erro .= "Número de Série {$produto_serie} Inválido <br />";
        }

      }

    }

  }

  $locacao = trim($_POST["locacao"]);
  $x_locacao = (strlen($locacao) > 0) ? "7" : "null";

  if ($login_fabrica == 7) { // HD 75762 para Filizola

      $classificacao_os = trim($_POST['classificacao_os']);

      if (strlen($classificacao_os) == 0) {
          $msg_erro .= " Escolha a classificação da OS. <br />";
      }

  } else {
      $classificacao_os = 'null';
  }

    $tipo_atendimento = $_POST['tipo_atendimento'];
    if (strlen(trim($tipo_atendimento)) == 0) {

        if($login_fabrica == 114){
            $produto_referencia = $_POST['produto_referencia'];

            $sql = "SELECT
                        tbl_produto.produto,
                        tbl_linha.deslocamento
                    FROM
                        tbl_produto
                        JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                    WHERE tbl_produto.referencia = '$produto_referencia'
                    AND tbl_linha.fabrica = $login_fabrica
                    AND tbl_linha.deslocamento IS TRUE";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res) > 0){

                $deslocamento = pg_fetch_result($res, 0, "deslocamento");

                if($deslocamento === 't'){
                    $com_deslocamento = 1;
                }
                $msg_erro .= " Selecione um Tipo de Atendimento. <br />";
            }
        }

        $tipo_atendimento = 'null';

        if ($login_fabrica == 7) {
            $msg_erro .= " A natureza é obrigatória. <br />";
        } else if ($login_fabrica == 42 or $login_fabrica == 131 or $login_fabrica == 124) {
            $msg_erro .= " Selecione um Tipo de Atendimento. <br />";
        }
    } else if ($login_fabrica == 42) {

        $sql = "select entrega_tecnica from tbl_tipo_atendimento where fabrica = $login_fabrica and tipo_atendimento = $tipo_atendimento";
        $res = pg_query($con, $sql);

        $tipo_atendimento_et = pg_fetch_result($res, 0, entrega_tecnica);
        $os_cortesia = filter_input(INPUT_POST,'os_cortesia');

        if (strlen($nota_fiscal) == 0 && $tipo_atendimento_et == "t") {
            $msg_erro .= "Erro: Digite  o número da Nota Fiscal<br />";
        }
    }

    $produto_referencia = strtoupper(trim($_POST['produto_referencia']));

    if (!in_array($login_fabrica, [124])) {
      $produto_referencia = str_replace(["-"," ","/","."],"",$produto_referencia);
    }


    if (in_array($login_fabrica, array(141,144))) {
        $sql_tipo_posto = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
        $res_tipo_posto = pg_query($con, $sql_tipo_posto);

        if (pg_num_rows($res_tipo_posto)) {
        $tipo_posto = json_decode(pg_fetch_result($res_tipo_posto, 0, "parametros_adicionais"), true);

            if ($tipo_posto["posto_troca"] == "t") {
                $sql = "SELECT produto
                        FROM tbl_produto
                        WHERE fabrica_i = {$login_fabrica}
                        AND referencia = '{$produto_referencia}'
                        AND troca_obrigatoria IS TRUE";
                $res = pg_query($con, $sql);

                if (!pg_num_rows($res)) {
                $msg_erro .= "Produto inválido<br />";
                }
            }
        }
    }

  if($login_fabrica == 15) {

      if($tipo_atendimento == 'null'){
          $msg_erro .= "Selecione um Tipo de Atendimento<br />";
      }
      //echo "TIPO ATENDIMENTO =".$tipo_atendimento."<br>";
      if(strlen($msg_erro) == 0) {
          $sql = "select tbl_produto.produto
                  from tbl_produto
                  join tbl_familia using(familia)
                  where tbl_familia.paga_km is TRUE
                  and tbl_familia.fabrica=$login_fabrica
                  and tbl_produto.referencia='$produto_referencia' ";
                  //echo $sql;
          $res = pg_query($con,$sql);
          if (pg_num_rows($res) == 0) {
              if($tipo_atendimento == 21){
                  $msg_erro .= "Tipo de atendimento inválido para esse produto<br />";
              }
          }
      }
  }

  if ($login_fabrica == 35 and $tipo_atendimento == 100) {
      $xxproduto_referencia = strtoupper(trim($_POST['produto_referencia']));
      $sqlDesl = "SELECT tbl_linha.linha FROM tbl_linha JOIN tbl_produto USING(linha)
                  WHERE tbl_linha.deslocamento IS TRUE
                  AND tbl_produto.referencia = '$xxproduto_referencia'";
      //echo $sqlDesl;
      $qryDesl = pg_query($con, $sqlDesl);

      if (pg_num_rows($qryDesl) == 0) {
          $msg_erro .= "Tipo de atendimento inválido para esse produto<br />";
      }
  }

  if ($login_fabrica == 42) {//HD 400603

      switch($tipo_atendimento){
          case 103 : $produto_referencia = 'GAR-PECAS';break;
          case 104 : $produto_referencia = 'GAR-ACESS';break;
          case 133 : $produto_referencia = 'GAR-BATER';break;
          case 134 : $produto_referencia = 'GAR-CARRE';break;
      }
  }

  if (strlen($produto_referencia) == 0) {
      $produto_referencia = 'null';
      $msg_erro .= " Digite o produto.<br />";
      if ($login_fabrica == 19) {
        $msg_erro = "Preencher os campos obrigatórios. <br />";
      }
  } else {
      $produto_referencia = "'".$produto_referencia."'" ;
  }

  if($login_fabrica == 59 and $posto == 386708){
      if(strlen(trim($origem))==0){
          $msg_erro .= "Selecione o campo origem. <Br>";
      }
  }

  if ($login_fabrica == 30) {

      $sql = "SELECT marca FROM tbl_produto WHERE referencia = $produto_referencia AND marca = 164";
      $res = pg_query($con, $sql);

      if (pg_num_rows($res) > 0) {
          $msg_erro = "Este produto ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!<br>";
      }

  }

  $produto_capacidade = strtoupper(trim($_POST['produto_capacidade']));

  if (strlen($produto_capacidade) == 0) {
      $xproduto_capacidade = 'null';
  } else {
      $xproduto_capacidade = str_replace(",",".",$produto_capacidade);
  }

  $versao = trim($_POST['versao']);

  if (strlen($versao) == 0) {
      $xversao = 'null';
  } else {
      $xversao = "'".$versao."'";
  }

  $divisao = trim($_POST['divisao']);

  if (strlen($divisao) == 0) {
      $xdivisao = 'null';
  } else {
      $xdivisao = str_replace(",",".",$divisao);
  }

  if($login_fabrica == 42){
      if(strlen($consumidor_nome) == 0){
          $msg_erro .= "Erro: Digite  o nome do consumidor<br />";
      }

      if(strlen($consumidor_fone) == 0){
          $msg_erro .= "Digite  o telefone do consumidor<br />";
      }
  }

  $xdata_abertura = trim($_POST['data_abertura']);

  if(!empty($xdata_abertura)){
      list($di, $mi, $yi) = explode("/", $xdata_abertura);
      if(!checkdate($mi,$di,$yi)){
        $msg_erro .= "Data Inválida.<br />";
      }

      if(strlen($yi) < 4){
        $msg_erro .= "Verifique o formato da data de abertura";
      }

      if(strlen($msg_erro)==0)
      {
          $xdata_abertura = "$yi-$mi-$di";
          $cdata_abertura = str_replace("'","",$xdata_abertura);
      }
  }
  else{
      $msg_erro .= " Digite a data de abertura da OS.<br />";
  }
  /**
   * HD 854585 - Arrumando bug - Erro de data não tratado
   * @author Brayan
   */
  if ( !empty($_POST['data_nf']) ) {

      list($di, $mi, $yi) = explode("/", $_POST['data_nf']);
      if(!checkdate($mi,$di,$yi))
          $msg_erro .= "Data Inválida.<br />";

  }

  if ($login_fabrica == 72) {//HD 249034

      //Mallory não pode ter data de abertura > 5 dias
      if (date_to_timestamp(trim($_POST['data_abertura'])) < mktime(0, 0, 0, date("m"), date("d")-5, date("Y"))) {
          $msg_erro .= " Erro: A Mallory não permite que OS com mais de 5 dias sejam inseridas. Favor entrar em contato com a Mallory<br />";
      }

  }

  $hora_abertura = trim($_POST['hora_abertura']);
  if ($login_fabrica == 7 AND strlen($hora_abertura) == 0) {
      $msg_erro .= " Digite a hora de abertura da OS.<br />";
  }

  if (strlen($msg_erro) == 0) {

      if (strlen($hora_abertura) > 0) {
          $xhora_abertura = "'".$hora_abertura."'";
      } else {
          $xhora_abertura = " NULL ";
      }

  }

  ##############################################################
  # AVISO PARA POSTOS DA BLACK & DECKER
  # Verifica se data de abertura da OS é inferior a 01/09/2005
  ##############################################################
  if ($login_fabrica == 1) {
      $sdata_abertura = str_replace("-","",$cdata_abertura);

      // liberados pela Fabiola em 05/01/2006
      if ($login_posto == 5089) { // liberados pela Fabiola em 20/03/2006
          if ($sdata_abertura < 20050101)
              $msg_erro = " Data de abertura inferior a 01/01/2005.<br />Lançamento restrito às OSs com data de lançamento superior a 01/01/2005.<br />";
      } else if ($login_posto == 5059 OR $login_posto == 5212) {
          if ($sdata_abertura < 20050502)
              $msg_erro = " Data de abertura inferior a 02/05/2005.<br />Lançamento restrito às OSs com data de lançamento superior a 01/05/2005.<br />";
      } else {
          if ($sdata_abertura < 20050901)
              $msg_erro = " Data de abertura inferior a 01/09/2005.<br />OS deve ser lançada no sistema antigo até 30/09.<br />";
      }
  }
  ##############################################################

  if (in_array($login_fabrica, array(6, 7, 19, 51,80))) {
      if (strlen(trim($_POST['consumidor_nome'])) == 0) {
          $msg_erro .= " Digite o nome do consumidor. <br />";
          if ($login_fabrica == 19) {
            $msg_erro = "Preencher os campos obrigatórios. <br />";
          }
      } else {
          $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
      }
  } else {
      if (strlen(trim($_POST['consumidor_nome'])) == 0) {
          $xconsumidor_nome = 'null';
      } else {
          $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
      }
  }
  $consumidor_cpf = trim($_POST['consumidor_cpf']);

  if (in_array($login_fabrica, [3,19,30])) {
      if (empty($consumidor_cpf) && $consumidor_revenda == 'C') {
          $msg_erro .= 'Digite o CPF/CNPJ do consumidor.<br>';
      }
  }

  if ($login_fabrica == 42) {
      if (strlen($consumidor_cpf) == 0 && ($tipo_atendimento_et == "t" || $os_cortesia == 't')) {
          $msg_erro .= "Digite o CPF/CNPJ do consumidor <br />";
      }

      if (strlen($consumidor_nome) == 0 && $os_cortesia == 't') {
          $msg_erro .= "Digite o Nome do consumidor <br />";
      }

      if (strlen($consumidor_fone) == 0 && $os_cortesia == 't') {
          $msg_erro .= "Digite o Telefone do consumidor <br />";
      }
  }

  if((in_array($login_fabrica, array(80))) or ($login_fabrica == 42 and $tipo_atendimento_et == "t")){
      if(strlen($consumidor_endereco) == 0)
          $msg_erro .= " Informe o endereço do cliente.<br />";

      if(intval($consumidor_numero) == 0){

          if ( trim(strtolower($consumidor_numero)) != 's/n' ){

          $msg_erro .= " Informe o endereço do cliente (número) ou insira 'S/N'.<br />";

          }
      }

      if(strlen($consumidor_cidade) == 0)
          $msg_erro .= " Informe o endereço do cliente (cidade).<br />";

      if(strlen($consumidor_estado) == 0)
          $msg_erro .= " Informe o endereço do cliente (estado).<br />";

      if(strlen($consumidor_cep) == 0)
          $msg_erro .= " Informe o endereço do cliente (CEP).<br />";

      if(strlen($consumidor_bairro) == 0)
          $msg_erro .= " Informe o endereço do cliente (bairro).<br />";

  }

  if ($login_fabrica == 19 && empty($consumidor_endereco)) {
    $msg_erro = "Preencher os campos obrigatórios. <br />";
  }

  if(in_array($login_fabrica, $cpf_obrigatorio) OR ($login_fabrica == 43 and $consumidor_revenda == 'C')) {

      $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));

      if(empty($valida_cpf_cnpj)){
          $cnpj_valido = (!is_bool($consumidor_cpf = checaCPF($consumidor_cpf,false)));
          if ($cnpj_valido) {
              $xconsumidor_cpf = "'".checaCPF($consumidor_cpf,false)."'";
          } else {
              $msg_erro .= " CPF/CNPJ do consumidor inválido<br />";
              $xconsumidor_cpf = 'null';
          }
      }else{
          $msg_erro .= $valida_cpf_cnpj."<br />";
      }
  } else {

      $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));
      if(empty($valida_cpf_cnpj)){
          $cnpj_valido = false;
          if (strlen($consumidor_cpf) != 0) {
              if (!is_bool(checaCPF($consumidor_cpf, false))) {
                  $consumidor_cpf = checaCPF($consumidor_cpf);
                  $cnpj_valido = true;
              } else {
                  $msg_erro .= "CPF/CNPJ do cliente inválido<br />";
              }
          }
          $xconsumidor_cpf = ($cnpj_valido) ? "'$consumidor_cpf'" : 'null';
      }else{
          $msg_erro .= $valida_cpf_cnpj."<br />";
      }
  }

  if ($login_fabrica == 91 && $consumidor_revenda == 'R') {
    if (strlen(preg_replace("/\D/","",$consumidor_cpf)) <> 14) {
      $msg_erro .= "CPF/CNPJ do cliente inválido<br />";
    } else {
      $checkCpf = checaCPF(preg_replace("/\D/","",$consumidor_cpf));
      if (strlen($checkCpf) <> 14) {
        $msg_erro = $checkCpf;
      }
    }
  }

  if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
  else             $xconsumidor_cidade = trim($_POST['consumidor_cidade']);

  if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
  else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

  if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
  else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

  if (strlen(trim($_POST['consumidor_celular'])) == 0) $xconsumidor_celular = 'null';// hd 15091
  else             $xconsumidor_celular = "'".trim($_POST['consumidor_celular'])."'";

  if (strlen(trim($_POST['consumidor_fone_comercial'])) == 0) $xconsumidor_fone_comercial = 'null';
  else            $xconsumidor_fone_comercial = "'".trim($_POST['consumidor_fone_comercial'])."'";

  if (strlen(trim($_POST['consumidor_fone_recado'])) == 0) $xconsumidor_fone_recado = 'null';
  else             $xconsumidor_fone_recado = "'".trim($_POST['consumidor_fone_recado'])."'";

  //HD 413556 - Campos obrigatórios para a LeaderShip
  if (in_array($login_fabrica, array(7, 14, 30, 45, 50, 51, 80, 95)) and $xconsumidor_fone=='null') {
    if ($login_fabrica != 30) {
      $msg_erro .= "Erro: Digite  o telefone do consumidor<br />";
    } else {
      if ($consumidor_revenda == "C") {
        $msg_erro .= "Erro: Digite  o telefone do consumidor<br />";
      }
    }
  }


  if (in_array($login_fabrica, array(35)) and $xconsumidor_celular=='null') {
      $msg_erro .= " Digite o telefone celular do consumidor.<br />";
  }

  if($login_fabrica == 35){
      $informaemail = $_POST['informaemail'];
      if(strlen($consumidor_email)==0 AND strlen($informaemail)==0){
          $msg_erro .= "Digite o e-mail do consumidor.<br>";
      }
  }

  if ($login_fabrica == 19 && empty($consumidor_email)) {
  }

  if (in_array($login_fabrica, array(7, 14, 19, 45)) AND $xconsumidor_cidade == 'null') {
      $msg_erro .= " Digite a cidade do consumidor.<br />";
      if ($login_fabrica == 19) {
        $msg_erro = "Preencher os campos obrigatórios. <br />";
      }
  }

  if (in_array($login_fabrica, array(7, 14, 19, 45)) AND $xconsumidor_estado == 'null') {
      $msg_erro .= " Digite o estado do consumidor.<br />";
      if ($login_fabrica == 19) {
        $msg_erro = "Preencher os campos obrigatórios. <br />";
      }
  }

  if ($login_fabrica == 19) {
      if (strlen($xconsumidor_fone) < 12) {
          $msg_erro = "Preencher os campos obrigatórios. <br>";
      }

      if (empty($consumidor_cep)) {
        $msg_erro = "Preencher os campos obrigatórios. <br>";
      }

      if (empty($consumidor_numero)) {
        $msg_erro = "Preencher os campos obrigatórios. <br>";
      }

      if (empty($consumidor_bairro)) {
        $msg_erro = "Preencher os campos obrigatórios. <br>";
      }
  }

  if(strlen($xconsumidor_celular) > 0 && $xconsumidor_celular != "null" && $login_fabrica != 35){
	$celular_conf = trim(str_replace("'", "", $xconsumidor_celular));

	$erro_cel = valida_celular((int)$celular_conf);
	$msg_erro .= $erro_cel;
  }

  #takashi 02-09
  $xconsumidor_endereco    = trim ($_POST['consumidor_endereco']) ;
  $xconsumidor_endereco    = str_replace("'","''",$xconsumidor_endereco);
  $xconsumidor_numero      = trim ($_POST['consumidor_numero']);
  $xconsumidor_complemento = trim ($_POST['consumidor_complemento']) ;
  $xconsumidor_bairro      = trim ($_POST['consumidor_bairro']) ;
  $xconsumidor_bairro      = str_replace("'","''",$xconsumidor_bairro);
  $xconsumidor_cep         = trim ($_POST['consumidor_cep']) ;

  $xconsumidor_cidade = "'".str_replace("'","''",$xconsumidor_cidade)."'";

  if ($cook_idioma == "pt-br" && (strlen($_POST["consumidor_cidade"]) > 0 && $xconsumidor_cidade != "null") && (strlen($_POST["consumidor_estado"]) > 0 && $xconsumidor_estado != "null")) {
    $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais({$xconsumidor_cidade})) AND UPPER(estado) = UPPER({$xconsumidor_estado})";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
      $sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais({$xconsumidor_cidade})) AND UPPER(estado) = UPPER({$xconsumidor_estado})";
      $res = pg_query($con, $sql);

      if (pg_num_rows($res) > 0) {
        $cidade_ibge        = pg_fetch_result($res, 0, "cidade");
        $cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

        $sql = "INSERT INTO tbl_cidade (
              nome, estado
            ) VALUES (
              '{$cidade_ibge}', '{$cidade_estado_ibge}'
            )";
        $res = pg_query($con, $sql);
      } else {
        $msg_erro .= "Cidade do consumidor não encontrada<br />";
      }
    }
  }

  if ($cook_idioma == "pt-br" && strlen($_POST["consumidor_estado"]) > 0 && !strlen($_POST["consumidor_cidade"])) {
    $msg_erro .= "Digite a cidade do consumidor";
  }

  if ($cook_idioma == "pt-br" && strlen($_POST["consumidor_cidade"]) > 0 && !strlen($_POST["consumidor_estado"])) {
    $msg_erro .= "Selecione o estado do consumidor";
  }

  //HD 413556 - Campos obrigatórios para a LeaderShip
  if (in_array($login_fabrica, array(1, 2, 7, 45, 51, 80))) {
      if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Erro: Digite o endereço do consumidor. <br />";
  }

  //HD 413556 - Campos obrigatórios para a LeaderShip
  if (in_array($login_fabrica, array(1, 7, 45, 51, 95))) {
      if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Erro: Digite o número do endereço do consumidor. <br />";
      if (strlen($xconsumidor_bairro) == 0) $msg_erro .= " Erro: Digite o bairro do consumidor. <br />";
      if (strlen($xconsumidor_estado) == 0) {
          $msg_erro .= " Erro: Digite o estado do consumidor. <br />";
      } else {
          $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";
      }
  }

  //--==== OS de Instalação ============================================
  if(strlen($tipo_atendimento) > 0) {
      $automatico = "t";
      $obs_km = " OS Aguardando aprovação de Kilometragem. ";
      $km_auditoria = "FALSE";
      $sql = "SELECT tipo_atendimento,km_google
              FROM tbl_tipo_atendimento
              WHERE tipo_atendimento = $tipo_atendimento";
      $res = pg_query($con,$sql);

      if (pg_num_rows($res) > 0) {
        $km_google = pg_fetch_result($res,0,km_google);

        $obs_km="";
        if ($km_google == 't') {
            $qtd_km    = str_replace (",",".",$_POST['distancia_km']);
            $qtd_km2   = str_replace (",",".",$_POST['distancia_km_conferencia']);
            $xqtde_km  = $qtd_km  ? : '0';
            $xqtde_km2 = $qtd_km2 ? : '0';
            $qtde_km   = number_format($qtd_km,3,'.','');
            $qtde_km2  = number_format($qtd_km2,3,'.','');
            
            if(in_array($login_fabrica,[120,201])){
                if(strlen($qtd_km) == 0){
                    $msg_erro .= "Por favor preencha o Distância";
                }
            }
            if($login_fabrica == 74){
                if (!strlen(trim($_POST["cidade_posto"]))) {
                    $sqlPostoCidade = "SELECT contato_cidade FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
                    $resPostoCidade = pg_query($con, $sqlPostoCidade);
                    $_POST["cidade_posto"] = pg_fetch_result($resPostoCidade, 0, "contato_cidade");
                }
                if (strtoupper(retira_acentos(trim($_POST["cidade_posto"]))) == strtoupper(retira_acentos(trim($consumidor_cidade)))) {
                    $qtd_km = 0;
                    $qtde_km = 0;
                }
            }

            if ($login_fabrica == 30) {
                if ($xqtde_km or $xqtde_km2) {
                    $km_auditoria = "TRUE"; # HD 112039
                    $obs_km=" OS com intervenção de KM. ";
                }
            }

              if (in_array($login_fabrica,array(15,120,201,125,131))) { //HD 275256 inicio
                  if ($qtde_km >= '100'){
                      $km_auditoria = "TRUE";
                      #$obs_km = ($xqtde_km <>'0') ? " Cálculo Automático. " : null;
                      $obs_km = "Deslocamento Acima de 100Km";
                  }

              }else if ((in_array($login_fabrica,array(3,114,115,116,117,129,137,140,141))) AND $qtde_km > 0) {
                      $km_auditoria = "TRUE";
                      $obs_km = " OS com intervenção de KM($qtde_km). ";
              }else{

                  $qtde_maior_100 = false;

              }//HD 275256 fim

			  if(empty($obs_km)) {
                      $obs_km=" OS com intervenção de KM, Qtde : $qtde_km ";
			  }
              if ($distancia_km_maps <> 'maps' AND ($qtde_km <> $qtde_km2 AND $qtde_km > 0) OR ($qtde_maior_100 == true) ) {

                  /*HD20487 - 04/07/2008*/
                  // if ($login_fabrica == 30) {
                  //     if (($qtde_km*1.2) > $qtde_km2) {
                  //         $km_auditoria = "TRUE";
                  //     }
                  // } else {
                      $km_auditoria = "TRUE";
                  // }

                  if (in_array($login_fabrica,array(24,91,120,201))){
                      $xqtde_km  = str_replace(".", ",", $qtde_km);
                      $xqtde_km2 = str_replace(".", ",", $qtde_km2);
                      // HD 47644
                      $obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
                      $automatico = "f";
                  }

                  if ($login_fabrica == 15) {
                      $xqtde_km  = str_replace(".", ",", $qtde_km);
                      $xqtde_km2 = str_replace(".", ",", $qtde_km2);
                      // HD 699862
                      $obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
                  }

                  if ($login_fabrica == 35 ) { // HD 708697

                      if ($qtde_km < 20) {
                          $qtde_km = 0;
                      }
                      else {

                          $xqtde_km  = str_replace(".", ",", $qtde_km);
                          $xqtde_km2 = str_replace(".", ",", $qtde_km2);
                          $obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";

                      }

                  }

                  if($login_fabrica == 90 && $qtde_km > 30) { // HD 310122
                      $xqtde_km  = str_replace(".", ",", $qtde_km);
                      $xqtde_km2 = str_replace(".", ",", $qtde_km2);
                      $obs_km = " Alteração manual de km de $xqtde_km2 km para $xqtde_km km. ";
                      $automatico = "f";
                  }

				  if ($login_fabrica == 74 ) {//HD:358194
					  $xqtde_km  = str_replace(".", ",", $qtde_km);
					  $xqtde_km2 = str_replace(".", ",", $qtde_km2);

					  $km_auditoria = "TRUE";
					  $obs_km = " Alteração manual de km de $qtde_km2 km para $xqtde_km km. ";
					  if($login_fabrica == 74){
						  if($_POST["cidade_posto"] == $consumidor_cidade){
							  $qtd_km = 0;
								$km_auditoria = "FALSE";
						  }
					  }

                  }

              } else {

                  if ($login_fabrica == 50) { //HD: 24813 - PARA

                      // if ($qtde_km >= 50) {
                      //     //desconta 20 km pois entende-se que é area hurbana e não pagam os 20
                      //     $qtde_km = $qtde_km - 20;
                      //     $qtde_km = ($qtde_km < 0) ? 0 : $qtde_km;
                      // }

                      if ($tipo_atendimento == 55 AND $qtde_km >= 20) {
                            $km_auditoria = "TRUE";
                            $obs_km = " OS entrou em auditoria de km ({$qtde_km}).";
                      }

                  }

                  if ($login_fabrica == 35 ) { // HD 708697

                      if ($qtde_km < 20) {
                          $qtde_km = 0;
                      } else if ($qtde_km > 50) {
                          $km_auditoria = "TRUE";
                          $obs_km = " Quantidade de KM calculado superior a 50 km. ";
                      }

                  }

                  /*if ($login_fabrica == 74 AND $qtde_km > 80) {//HD:358194
                      $km_auditoria = "TRUE";
                      $obs_km = " Quantidade de KM calculado superior a 80 km. ";
                  }*/

                  if ($login_fabrica == 24  AND $qtde_km > 40) {
                      $km_auditoria = "TRUE";
                      $obs_km       = " KM maior que 40km. ";
                  }

                  if ($login_fabrica == 85  AND $qtde_km > 40) { //HD 323345

                      $km_auditoria = "TRUE";
                      $obs_kmi      = " KM maior que 40km. ";

                  } else if( $login_fabrica == 85 )
                      $qtde_km = 0;

                  if ($login_fabrica == 90 AND $qtde_km > 40) { // fabrica 90 HD 310122
                      $km_auditoria = "TRUE";
                      $obs_km       = " KM maior que 40km. ";
                  }

                  // HD 310122, waldir pediu p alterar p 10, 17/11/2010
                  if ($login_fabrica == 90 && $qtde_km < 10) {
                      $qtde_km = 0;
                  }

                  if ($login_fabrica == 91 && strlen($os) == 0) {//HD 375933

                      if ($qtde_km > 0) {

                          $km_auditoria = "TRUE";
                          $obs_km       = "OS Aguardando aprovação de Kilometragem";
                          $automatico   = "t";

                      } else {

                          $qtde_km = 0;

                      }

                  }

                  // if ($login_fabrica == 30 AND $qtde_km > 200) {// HD 47644

                  //     $km_auditoria = "TRUE";
                  //     $obs_km       = " KM maior que 200km. ";

                  // }

              }
          } else {

              if ($login_fabrica <> 19)
                  $qtde_produtos = 1;

              // HD 3272576
              if ($login_fabrica == 30) {
                  $km_auditoria = 'FALSE';
                  $xqtde_km  = '0';
                  $xqtde_km2 = '0';
                  $qtde_km   = '0';
                  $qtde_km2  = '0';
              }

          }

      }

  }

  if (strlen($qtde_km) == 0) {

      $qtde_km      = "NULL";
      $km_auditoria = "FALSE";

  }

    /**
     *   HD-1780026 - ESMALTEC
     *       Se o posto ter cadastro de KM FIXO
     *       não entrará em AUDITORIA DE KM
     *
     *   @author William Ap. Brandino
     */
    if($login_fabrica == 30 OR $login_fabrica == 74){

        $sql = "SELECT  JSON_FIELD('valor_km_fixo',parametros_adicionais) AS valor_km_fixo
                FROM    tbl_posto_fabrica
                WHERE   fabrica = $login_fabrica
                AND     posto   = $login_posto
        ";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){

            $valor_km_fixo = pg_fetch_result($res,0,valor_km_fixo);

            if (!empty($valor_km_fixo)) {
                $qtde_km      = number_format($qtd_km,3,'.','');
                $km_auditoria = "FALSE";
            }
        }
    }

    if (in_array($login_fabrica, [144])) {
      $km_auditoria = "FALSE";
    }

  //$msg_erro = "$qtde_km $km_auditoria $qtde_km2";
  //--================================================================

  if (strlen($xconsumidor_complemento) == 0) $xconsumidor_complemento = "null";
  else                                       $xconsumidor_complemento = "'" . $xconsumidor_complemento . "'";

  if ($_POST['consumidor_contrato'] == 't') $contrato    = 't';
  else                                      $contrato    = 'f';

  //$xconsumidor_cep = preg_replace('/\D/', '', $xconsumidor_cep);
  $xconsumidor_cep = str_replace('-', '', $xconsumidor_cep);
  $xconsumidor_cep = str_replace('.', '', $xconsumidor_cep);

  //HD 413556 - Campos obrigatórios para a LeaderShip
  if($login_fabrica==7 or $login_fabrica==45 or $login_fabrica==95) {
      if (strlen(trim($xconsumidor_cep)) == 0) $msg_erro .= " Erro: Digite o CEP do consumidor. <br />";
      else                                     $xconsumidor_cep = "'" . $xconsumidor_cep . "'";
  }else{
      if (strlen(trim($xconsumidor_cep)) == 0) $xconsumidor_cep = "null";
      else                                     $xconsumidor_cep = "'" . $xconsumidor_cep . "'";
  }
  ##takashi 02-09
  #HD 26730
  if ($login_fabrica == 7 or $login_fabrica == 30){
      if (strlen($msg_erro)==0 and strlen($xconsumidor_cidade)>0 and $xconsumidor_cidade <> 'null' and strlen($xconsumidor_estado)>0 and $xconsumidor_estado <> 'null') {
          if ($login_fabrica == 7){
              $sql = "SELECT tbl_posto.posto AS cliente
                      FROM   tbl_posto
                      WHERE  tbl_posto.cnpj = $xconsumidor_cpf";
              $res = pg_query ($con,$sql);

              if (pg_num_rows ($res) == 0){
                  $sql = "INSERT INTO tbl_posto
                              (nome,cnpj,endereco,numero,complemento,bairro,cep,cidade,estado,fone)
                          VALUES
                              ($xconsumidor_nome, $xconsumidor_cpf, '$xconsumidor_endereco', '$xconsumidor_numero', $xconsumidor_complemento, '$xconsumidor_bairro', $xconsumidor_cep, $xconsumidor_cidade,$xconsumidor_estado, $xconsumidor_fone) ";
                  $res = pg_query ($con,$sql);
                  $msg_erro .= pg_last_error($con);

                  $res   = pg_query ($con,"SELECT CURRVAL ('seq_posto') as cliente");
              }

              $xcliente = pg_fetch_result($res,0,cliente);

              $sql = "SELECT tbl_posto_consumidor.posto AS cliente
                      FROM   tbl_posto_consumidor
                      WHERE  tbl_posto_consumidor.fabrica = $login_fabrica
                      AND    tbl_posto_consumidor.posto   = $xcliente";
              $res = pg_query ($con,$sql);

              if (pg_num_rows ($res) == 0){
                   $sql = "INSERT INTO tbl_posto_consumidor
                              (fabrica,posto,obs)
                          VALUES
                              ($login_fabrica, $xcliente, 'Cliente cadastrado automaticamente apartir da OS') ";
                  $res = pg_query ($con,$sql);
                  $msg_erro .= pg_last_error($con);
              }
          }

          if ($login_fabrica == 30){
              $sql = "SELECT    tbl_cliente.cliente,
                              tbl_cliente.nome,
                              tbl_cliente.fone,
                              tbl_cliente.cpf,
                              tbl_cidade.nome AS cidade,
                              tbl_cidade.estado
                      FROM tbl_cliente
                      LEFT JOIN tbl_cidade
                      USING (cidade)
                      WHERE tbl_cliente.cpf = $xconsumidor_cpf";
              $res = pg_query ($con,$sql);

              if (pg_num_rows ($res) == 0){

                  $sql = "SELECT fnc_qual_cidade ($xconsumidor_cidade,$xconsumidor_estado)";
                  $res = pg_query ($con,$sql);
                  $xconsumidor_cidade2 = pg_fetch_result($res,0,0);

                  if(empty($xconsumidor_cidade2)){
                    $xcon_city = str_replace(array(' ','-',"'"), '%', $consumidor_cidade);
                    $sql = "SELECT cidade FROM tbl_cidade where nome ILIKE '$xcon_city'";
                    $res = pg_query ($con,$sql);
                      if(pg_num_rows($res) > 0){
                        $xconsumidor_cidade2 = pg_fetch_result($res, 0, cidade);
                      }else{
                        $xconsumidor_cidade2 = 'null';
                      }
                  }
                  $sql = "INSERT INTO tbl_cliente
                              (nome,cpf,endereco,numero,complemento,bairro,cep,cidade,fone)
                          VALUES
                              ($xconsumidor_nome, $xconsumidor_cpf, '$xconsumidor_endereco', '$xconsumidor_numero', $xconsumidor_complemento, '$xconsumidor_bairro', $xconsumidor_cep, $xconsumidor_cidade2, $xconsumidor_fone) ";
                  $res = pg_query ($con,$sql);
                  $msg_erro .= pg_last_error($con);

              }
          }
      }
  }

  $revenda_cnpj = preg_replace('/\D/', '', trim($_POST['revenda_cnpj']));

  // Mesmo que a fábrica não exiga a revenda, se digitou um CNPJ, tem que validar
  if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14 && $login_fabrica != 125) {
      $msg_erro .= " Tamanho do CNPJ da revenda inválido.<br />";
  }
  // email do ronaldo pedindo para validar cnpj da Gama Italy
  //HD 413556 - Campos obrigatórios para a LeaderShip
  if (strlen($revenda_cnpj) == 0 and $fabrica_cnpj_revenda_obrigatorio) {
      if(in_array($login_fabrica, array(15,24))){
          $msg_erro     .= " Revenda inválida<br />";
      }else{
          $msg_erro     .= " Erro: Insira o CNPJ da Revenda.<br />";
      }
  } else {
      $xrevenda_cnpj = "' $revenda_cnpj'";
  }

  if ( in_array($login_fabrica, array(11,172)) && $login_posto != 20321) {

      if (strlen($revenda_cnpj) == 0) $msg_erro .= " Insira o CNPJ da Revenda.<br />";
      else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

  } else {

      if (strlen($revenda_cnpj) == 0) {

          $xrevenda_cnpj = 'null';

      } else {

          $xrevenda_cnpj = "'".$revenda_cnpj."'";

          if ($login_fabrica == 7) {//HD 46309

              $cnpj_erro = verificaCpfCnpj($revenda_cnpj);
              if (strlen($cnpj_erro) == 0) {
                  $sql = "SELECT fn_valida_cnpj_cpf('$revenda_cnpj')";
                  $res = @pg_query($con,$sql);
                  $cnpj_erro = pg_errormessage($con);
                  if (strlen($cnpj_erro) > 0) {
                      $msg_erro .=" CNPJ da Revenda inválida <br />";
                  }
              }else{
                  $msg_erro .= $cnpj_erro."<br />";
              }

          }

      }

  }

  if (strlen(trim($_POST['revenda_nome'])) == 0){
      #hd 15835 17136 | HD 25450
      # HD 73415 - Nova

      if ($fabrica_revenda_nao_obirgatoria) {
          $xrevenda_nome = "NULL";
      }else{
          $msg_erro .= " Digite o nome da revenda. <br />";
          if ($login_fabrica == 19) {
            $msg_erro = "Preencher os campos obrigatórios. <br />";
          }
      }
  }else{
      $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";


    if($login_fabrica == 35){
        $msg_erro .= VerificaBloqueioRevenda($revenda_cnpj, $login_fabrica);
    }
  }

  if($login_fabrica == 19 and empty($_POST['revenda_cnpj'])){
    $msg_erro .= "Digite o CNPJ da revenda. <br />";
  }

  if (strlen(trim($_POST['consumidor_cpf'])) == 0 and ($login_fabrica == 35)){
          $msg_erro .= "Digite o CPF/CNPJ do Consumidor. <br />";
  }

  if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
  else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

  if($login_fabrica <> 15 AND $login_fabrica <> 24 AND $login_fabrica <> 122){
      //=====================revenda
          $xrevenda_cep = preg_replace('/\D/', '', trim($_POST['revenda_cep']));
          $xrevenda_cep = substr ($xrevenda_cep,0,8);
          /*takashi HD 931  21-12*/
          //HD 206869: Exigir CNPJ da Revenda para a Salton
          if (strlen ($_POST['revenda_cnpj']) == 0 and $fabrica_cnpj_revenda_obrigatorio) $msg_erro .= " Digite o CNPJ da Revenda.<br />";

          if (strlen($xrevenda_cep) == 0) $xrevenda_cep = "null";
          else $xrevenda_cep = "'" . $xrevenda_cep . "'";

          //if (strlen(trim($_POST['revenda_cep'])) == 0) $xrevenda_cep = 'null';
          //else $xrevenda_cep = "'".str_replace("'","",trim($_POST['revenda_cep']))."'";

          if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
          else $xrevenda_endereco = "'".str_replace("'","''",trim($_POST['revenda_endereco']))."'";

          if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
          else $xrevenda_numero = "'".str_replace("'","''",trim($_POST['revenda_numero']))."'";

          if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
          else $xrevenda_complemento = "'".str_replace("'","''",trim($_POST['revenda_complemento']))."'";


          if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
          else $xrevenda_bairro = "'".str_replace("'","''",trim($_POST['revenda_bairro']))."'";

          if (strlen(trim($_POST['revenda_cidade'])) == 0) {
              #hd 15835 17136 25450 73415
              if ($login_fabrica == 134 || $login_fabrica==7 or $login_fabrica==14
                  or ( in_array($login_fabrica, array(11,172)) && $login_posto==20321)
                  or $login_fabrica==30 or $login_fabrica == 43 or $login_fabrica == 96 or $tipo_atendimento_et == "t"){
                  $xrevenda_cidade='null';
              }else{
                  $msg_erro .= " Digite a cidade da revenda. <br />";
                  if ($login_fabrica == 19) {
                    $msg_erro = "Preencher os campos obrigatórios. <br />";
                  }
              }
          }else{
              $xrevenda_cidade = "'".str_replace("'","''",trim($_POST['revenda_cidade']))."'";
          }
          #hd 15835 17136 25450 73415
          if (strlen(trim($_POST['revenda_estado'])) == 0){
              if ($login_fabrica == 134 || $login_fabrica==7 or $login_fabrica==14
                  or ( in_array($login_fabrica, array(11,172)) && $login_posto==20321)
                  or $login_fabrica==30 or $login_fabrica == 43 or $login_fabrica == 96 or $tipo_atendimento_et == "t"){
                  $xrevenda_estado='null';
              }else{
                  $msg_erro .= "  Selecione o estado da revenda. <br />";
                  if ($login_fabrica == 19) {
                    $msg_erro = "Preencher os campos obrigatórios. <br />";
                  }
              }
          }else{
              $xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
          }
      //=====================revenda
  }

  if($login_fabrica == 74){

     $referencia = $_POST['produto_referencia'];
     $linha_id   = $_POST['linha_id'];

     $sql_linha = "select tbl_linha.codigo_linha, numero_serie_obrigatorio
                            from
                            tbl_produto
                            inner join tbl_linha on tbl_produto.linha = tbl_linha.linha
                            where tbl_produto.referencia = '$referencia'
                            and tbl_produto.fabrica_i = $login_fabrica  ";

      $res_linha = pg_query($con, $sql_linha);

      if(pg_num_rows($res_linha)> 0){
          $codigo_linha = pg_fetch_result($res_linha, 0, 'codigo_linha');
          $numero_serie_obrigatorio = pg_fetch_result($res_linha, 0, 'numero_serie_obrigatorio');
      }

      if($codigo_linha == "02"){
        $cond_digita = " AND JSON_FIELD('digita_os_portateis', parametros_adicionais) = 't'";
      }
      if($codigo_linha == "01"){
        $cond_digita = " AND JSON_FIELD('digita_os_fogo', parametros_adicionais) = 't'";
      }

      $sql_posto_fabrica = "SELECT posto
                            FROM tbl_posto_fabrica
                            WHERE posto = $login_posto
                            AND tbl_posto_fabrica.fabrica = $login_fabrica
                            $cond_digita";

      $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

      if(pg_num_rows($res_posto_fabrica) == 0 ){
          $msg_erro .= " Esse posto não é autorizado a abrir O.S dessa linha. <br> ";
      }
  }


  if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
  else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";
  // HD 15835
  //HD 206869: Exigir nota fiscal para a Salton - HD 413556 LeaderShip
  if ($xnota_fiscal == 'null' and $fabrica_nota_fiscal_os_obrigatoria) {
      $msg_erro .= " Erro: Digite o número da nota fiscal.<br />";
  }

  if(strlen($xnota_fiscal)>0 AND $login_fabrica == 30){
      $xnota_fiscal = str_replace (".","",$xnota_fiscal);
      $xnota_fiscal = str_replace ("-","",$xnota_fiscal);
      $xnota_fiscal = str_replace ("/","",$xnota_fiscal);
      $xnota_fiscal = str_replace (",","",$xnota_fiscal);
      $xnota_fiscal = str_replace (" ","",$xnota_fiscal);
  }

  if ( in_array($login_fabrica, array(11,172)) && !empty($revenda_cnpj)) {
    $sqlRev = "
      SELECT r.revenda, rf.contato_razao_social
      FROM tbl_revenda r
      INNER JOIN tbl_revenda_fabrica rf ON rf.revenda = r.revenda AND rf.fabrica = {$login_fabrica}
      WHERE rf.cnpj = '{$revenda_cnpj}'
      AND rf.data_bloqueio IS NOT NULL
    ";
    $resRev = pg_query($con, $sqlRev);

    if (pg_num_rows($resRev) > 0) {
      $msg_erro .= " Este CNPJ e/ou Razão Social encontra-se bloqueado(a).<br />Maiores esclarecimentos poderão ser realizados com o Inspetor Técnico responsável pela sua região.<br />";

      if ($_serverEnvironment == "development") {
        $email_revenda_bloqueada = "gustavo.paulo@telecontrol.com.br";
      } else {
        $email_revenda_bloqueada = "dat@lenoxx.com.br";
      }

      $revenda_razao_social = pg_fetch_result($resRev, 0, "contato_razao_social");

      $assunto   = "Telecontrol - Abertura de OS para Revenda Bloqueada";
      $mensagem  = "
        O Posto Autorizado $login_nome, CNPJ $login_cnpj, Email $login_email, Telefone $login_telefone<br />
        Tentou abrir uma OS para uma Revenda Bloqueada, segue dados da revenda<br />
        Razão Social $revenda_razao_social CNPJ $revenda_cnpj";
      $remetente = "<noreply@telecontrol.com.br>";
      $headers   = "From:".$remetente."\nContent-type: text/html\n";
      mail($email_revenda_bloqueada, utf8_encode($assunto), $mensagem, $headers);
    }
  }

  $qtde_produtos = trim ($_POST['qtde_produtos']);
  if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

  if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
  else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";
  //pedido por Leandro Tectoy, feito por takashi 04/08
  //HD 413556 - Campos obrigatórios para a LeaderShip
  if (in_array($login_fabrica, array(5, 6, 24)) or ( in_array($login_fabrica, array(11,172)) && $login_posto != 20321 )){
      if (strlen ($_POST['data_nf']) == 0) $msg_erro .= "Erro: Digite a data de compra.<br />";
  }
  //pedido por Leandrot tectoy, feito por takashi 04/08
  $xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
  if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.<br />";

  if ($login_fabrica == 42 and strlen($_POST['data_nf']) == 0 and $tipo_atendimento_et == "t") {
      $msg_erro .= " Digite a data de compra. <br />";
  }

  // HD 56479
  // if (substr($revenda_cnpj, 0, 8) != '59291534' and $login_posto <> 653) {

  //     if (strlen($xdata_nf) > 0 and $login_fabrica == 30 and $tipo_atendimento == 41) {

  //         $sql = "SELECT $xdata_nf >= '$xdata_abertura'::date - interval '3 months' ";
  //         $res= pg_query($con,$sql);

  //         if (pg_fetch_result($res,0,0) == 'f') {

  //             $msg_erro=" Liberação de KM apenas até os primeiros três meses de compra<br />";

  //             if ((strpos(strtoupper($xconsumidor_cidade),"QUEDAS DO IGUA")) AND $login_posto == 855) {
  //                 $msg_erro = "";
  //             }

  //         }

  //     }

  // } //HD21373

  //HD26244 - Liberado para Esmaltec
  if ($login_fabrica == 30 OR $login_fabrica == 51) {
      $sql = "SELECT  garantia,
                  produto
          FROM tbl_produto
          JOIN tbl_linha   USING(linha)
          WHERE referencia = $produto_referencia
          AND   fabrica    = $login_fabrica";

      $res = @pg_query($con,$sql);
      if(pg_num_rows($res)>0){

          $garantia = pg_fetch_result($res,0,garantia);

          $sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date";
          $res = @pg_query($con,$sql);

          $garantia_menor = pg_fetch_result($res,0,0);
          $sql = "SELECT ($xdata_nf::date + (('50 months')::interval))::date";
          $res = @pg_query($con,$sql);
          $garantia_maior = pg_fetch_result($res,0,0);

          $xxdata_abertura = str_replace("'","",$xdata_abertura);

		  $dt_abertura = new DateTime($xxdata_abertura);
		  $dt_garantia_menor = new DateTime($garantia_menor);
		  $dt_garantia_maior = new DateTime($garantia_maior);

          if(($dt_garantia_menor < $dt_abertura) and ($dt_garantia_maior > $dt_abertura)) {
			  $liberar_digi = 'true';
          }

          if ($login_fabrica == 30 and empty($_POST['data_nf'])) {
			  $liberar_digi = 'true';
          }
      }
  }

  //HD 893100
  //OS DADOS DA REVENDA ADICIONAL SERÁ GRAVADO NA tbl_os_extra.recomendacoes
  if ($login_fabrica == 50){
      if ($_POST['atacadista']=='t'){

          $txt_revenda_nome        = trim($_POST['txt_revenda_nome']) ;
          $txt_revenda_cnpj        = trim($_POST['txt_revenda_cnpj']) ;
          $txt_revenda_fone        = trim($_POST['txt_revenda_fone']) ;
          $txt_revenda_cep         = trim($_POST['txt_revenda_cep']) ;
          $txt_revenda_endereco    = trim($_POST['txt_revenda_endereco']) ;
          $txt_revenda_numero      = trim($_POST['txt_revenda_numero']) ;
          $txt_revenda_complemento = trim($_POST['txt_revenda_complemento']) ;
          $txt_revenda_bairro      = trim($_POST['txt_revenda_bairro']) ;
          $txt_revenda_cidade      = trim($_POST['txt_revenda_cidade']) ;
          $txt_revenda_estado      = trim($_POST['txt_revenda_estado']) ;

          if (empty($txt_revenda_nome)) {
              $msg_erro = "Informe o Nome da Revenda da Nota Fiscal <br>";
          }

          if (empty($txt_revenda_cnpj)) {
              $msg_erro = "Informe o CNPJ da Revenda da Nota Fiscal <br> ";
          }

          if (empty($txt_revenda_cep)) {
              $msg_erro = "Informe o CEP da Revenda da Nota Fiscal <br> ";
          }
          if (empty($txt_revenda_endereco)) {
              $msg_erro = "Informe o Endereço da Revenda da Nota Fiscal <br> ";
          }
          if (empty($txt_revenda_numero)) {
              $msg_erro = "Informe o Número do Endereço da Revenda da Nota Fiscal <br> ";
          }

          if (empty($txt_revenda_bairro)) {
              $msg_erro = "Informe o Bairro da Revenda da Nota Fiscal <br> ";
          }
          if (empty($txt_revenda_cidade)) {
              $msg_erro = "Informe a Cidade da Revenda da Nota Fiscal <br> ";
          }
          if (empty($txt_revenda_estado)) {
              $msg_erro = "Informe o Estado da Revenda da Nota Fiscal <br> ";
          }

          if (empty($msg_erro)) {
              $obs_atacadista = "
                  Nome: $txt_revenda_nome
                  CNPJ: $txt_revenda_cnpj
                  FONE: $txt_revenda_fone
                  CEP: $txt_revenda_cep
                  ENDEREÇO: $txt_revenda_endereco
                  Nº: $txt_revenda_numero
                  COMPLEMENTO: $txt_revenda_complemento
                  CIDADE: $txt_revenda_cidade
                  Bairro: $txt_revenda_bairro
                  ESTADO: $txt_revenda_estado
              ";
          }

      }
  }

  if(strlen(trim($_POST['certificado_garantia']))==0) { // HD 63188
      if($login_fabrica == 30 and $liberar_digi == 'true') {
          $msg_erro .= "Digite 6 digitos para LGI<br />";
      }else{
          $xcertificado_garantia = 'null';
      }
  }else{
      if($login_fabrica == 30 and strlen(trim($_POST['certificado_garantia'])) <> 6 and $liberar_digi =='true') {
          $msg_erro .= "Digite 6 digitos para LGI <br />";
      }else{
          $xcertificado_garantia = "'$certificado_garantia'";
      }
  }

  if ($login_fabrica <> 117) {
      if (strlen(trim($_POST['produto_serie'])) == 0) {
          $xproduto_serie = 'null';
      }else{
          if($login_fabrica == 40) { // HD 205803
              if(strlen($_POST['produto_serie_ini']) == 0) {
                  $msg_erro = "Por favor, informe os 2 campos de número de série<br />";
              }else{
                  $xproduto_serie = $_POST['produto_serie_ini']."".str_pad($_POST['produto_serie'],7,"0",STR_PAD_LEFT);
                  $xproduto_serie = "'". strtoupper(trim($xproduto_serie)) ."'";
              }
          }else{
              $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";
          }
      }
  } else {
      $xproduto_serie = trim($_POST["produto_serie"]);

      if (!strlen($xproduto_serie)) {
          $msg_erro = "Por favor, informe o número de série<br />";
      } else {
          if (strtolower($xproduto_serie) <> "n/d" and !is_numeric($xproduto_serie)) {
              $msg_erro = "Número de série inválido<br />";
          } else {
              if (strtolower($xproduto_serie) <> "n/d" and (strlen($xproduto_serie) < 6 or strlen($xproduto_serie) > 14)) {
                  $msg_erro = "Número de série inválido<br />";
              } else {
                  $xproduto_serie = "'".strtoupper($xproduto_serie)."'";
              }
          }
      }
  }

  if ($login_fabrica == 42 and $tipo_atendimento_et == "t" and $xproduto_serie == "null") {
      $msg_erro .= "Informe o número de série <br />";
  }

  if (in_array($login_fabrica, [72]) && $xproduto_serie != "null") { 

    $sqlVerificaSerie = "SELECT numero_serie
                         FROM tbl_numero_serie
                         WHERE serie = {$xproduto_serie}
                         AND fabrica = {$login_fabrica}
                         AND bloqueada_garantia IS TRUE";
    $resVerificaSerie = pg_query($con, $sqlVerificaSerie);

    if (pg_num_rows($resVerificaSerie) > 0) {

      $msg_erro .= "o Número de Série informado está bloqueado para garantia.<br />";

    }
  
  }

  //MLG 19-04-2011 - HD 396972: Adicionar Ga.Ma Italy. Validação simples: 1 min, 20 máx, só letras e números.
  if($xproduto_serie=='null' and in_array($login_fabrica, array( 51, 56, 72, 79))) $msg_erro .= ' Digite o Número de Série.<br />';
  if ($login_fabrica == 51 and $xproduto_serie != 'null' and
    !preg_match('/^[0-9A-Z]{1,20}$/', $_POST['produto_serie'])) $msg_erro .= 'Número de série inválido!<br />';

  if ($login_fabrica == 56 AND strlen($msg_erro) == 0){
      if($tipo_atendimento == 42){
          $sql = "SELECT tipo_atendimento
                      FROM tbl_os
                      WHERE fabrica = $login_fabrica
                      AND tipo_atendimento = 42
                      AND serie = $xproduto_serie";

          $res = pg_query($con,$sql);
          if(pg_num_rows($res) > 0){
              $msg_erro = "O Produto com este número de série já foi instalado.<br />";
          }
      }
      if(strlen($msg_erro) == 0 AND ($tipo_atendimento == 43 OR $tipo_atendimento == 44)){
          $sql = "SELECT tipo_atendimento
                      FROM tbl_os
                      WHERE fabrica = $login_fabrica
                      AND tipo_atendimento = 42";
          $res = pg_query($con,$sql);
          if(pg_num_rows($res) == 0){
              $msg_erro = "O Produto com este número de série ainda não foi instalado.<br />";
          }
      }
  }


    /*if ($login_fabrica == 15) {

        $sql = "SELECT tbl_produto.numero_serie_obrigatorio FROM tbl_produto  WHERE referencia = $produto_referencia AND fabrica_i = $login_fabrica";
        $res = pg_query($con, $sql);

        $numero_serie_obrigatorio = pg_fetch_result($res, 0, 'numero_serie_obrigatorio');
        if (strlen($produto_serie) == 0 && $numero_serie_obrigatorio == 't') {
            $msg_erro .= 'Obrigatório preenchimento do Número de Série <br />';
        }

    }*/

  if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
  else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

  //hd 14269 7/3/2008
  if ($login_fabrica == 15 OR $login_fabrica == 45) {
      if (strlen(trim($_POST['preco_produto'])) == 0) $msg_erro = 'Digite o Preço do Produto <br /> ';
      else            $xpreco_produto = trim($_POST['preco_produto']);

      if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
      else                                                $xaparencia_produto = trim($_POST['aparencia_produto <br />']);

      if($login_fabrica == 45){
          $xaparencia_produto = "'".$xaparencia_produto.$xpreco_produto."'";
      } else {
          $xaparencia_produto = "'".$xaparencia_produto."'";
      }
  }else{
      if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
      else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";
  }

  if($login_fabrica == 140){

    $sql_ta = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento AND fabrica = $login_fabrica";
    $res_ta = pg_query($con, $sql_ta);
    $entrega_tecnica = pg_fetch_result($res_ta, 0, 'entrega_tecnica');

    if($entrega_tecnica == "t"){

      if(empty($_POST['preco_produto'])){
        $msg_erro .= "Digite o Preço do Produto <br />";
      }else{
        $xpreco_produto = trim($_POST['preco_produto']);
      }

    }else{
        $xpreco_produto = 0;
    }

  }

  //pedido leandro tectoy
  if($login_fabrica==6){
      if (strlen ($_POST['aparencia_produto']) == 0) $msg_erro .= " Digite a aparência do produto.<br />";
  }

  if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
  else                                         $xacessorios = "'".pg_escape_string($con, trim($_POST['acessorios']))."'";
  //pedido leandro tectoy
  if($login_fabrica==6){
      if (strlen ($_POST['acessorios']) == 0) $msg_erro .= " Digite os acessórios do produto.<br />";
  }

  if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) {
          $xdefeito_reclamado_descricao = 'null';
  }else{
      $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";
  }
  $defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);

  if($login_fabrica == 35){

      $informaemail = $_POST['informaemail'];
      if(strlen($informaemail)==0){
		  if(strlen($consumidor_email) == 0){

			  $msg_erro .= "Digite o E-mail do Consumidor. <br /> ";

		  }else if(!filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)){

			  $msg_erro .= "O E-mail do Consumidor é inválido. <br />";

		  }
	  }
  }

  //HD 722524 - Validação do campo "consumidor_email" para LATINATEC
  if ($login_fabrica == 15) {

      if (strlen(trim($_POST['consumidor_email'])) == 0) {

          $msg_erro = "Insira o e-mail do consumidor. Informe caso o mesmo não possua";

      } else if (strlen(trim($_POST['consumidor_email'])) > 0) {

          $email = trim($_POST['consumidor_email']);

          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

              if (preg_match('/(.)(?=\1{2,})/',$email)){
                  $msg_erro .= "<br />E-mail ou informação com varios caracteres repetidos<br />";
              }

          }

          if (strlen($email) < 5) {
              $msg_erro .= "<br />E-mail muito pequeno, ou informação muito curta.<br />";
          }

      }

      if(empty($msg_erro)){

          $xconsumidor_email = "'".trim(substr($_POST['consumidor_email'],0,49))."'";
          $consumidor_email = trim($_POST['consumidor_email']);

      }

  }else{

      if (strlen(trim($_POST['consumidor_email'])) == 0) {
          $xconsumidor_email = 'null';
      } else {
          $xconsumidor_email = "'".trim(substr($_POST['consumidor_email'],0,49))."'";
      }
      $consumidor_email = trim($_POST['consumidor_email']);

  }

  $data_nascimento = '';
  if ($login_fabrica == 74) {
      $data_nascimento = $_POST['data_nascimento'];
  }

  if (strlen(trim($_POST['obs'])) == 0) $xobs = 'null';
  else                                  $xobs = "'".trim($_POST['obs'])."'";

  //SE FOR COLORMAQ, o $xobs vai receber o $obs setado anteriormente para a revenda atacadista
  //ESTE CAMPO VAI SER GRAVADO EM: tbl_os_extra.recomendacoes
  if($login_fabrica == 50 and $_POST['atacadista']=='t'){
      $obs_atacadista = "'".$obs_atacadista."'";
  }else{
      $obs_atacadista = 'null';
  }

  if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) {
      if ($login_fabrica == 7 and !empty($hd_chamado)) {
          $msg_erro .= "Digite quem abriu o Chamado.<br />";
      } else {
          $xquem_abriu_chamado = 'null';
      }
  } else {
      $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";
  }

	if($login_fabrica == 24){
		$consumidor_revenda  = trim($_POST["consumidor_revenda_hidden"]);
	}else{
		$consumidor_revenda = $_POST["consumidor_revenda"];
	}
  if ( strlen($consumidor_revenda) == 0) $msg_erro .= " Selecione consumidor ou revenda.<br />";
  else                                $xconsumidor_revenda = "'".$consumidor_revenda."'";

  //if (strlen($_POST['type']) == 0) $xtype = 'null';
  //else             $xtype = "'".$_POST['type']."'";

  if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
  else             $xsatisfacao = "'".$_POST['satisfacao']."'";

  if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
  else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

  $defeito_reclamado = trim ($_POST['defeito_reclamado']);

  //if ($ip == '201.0.9.216') echo "[ $defeito_reclamado ] e ".strlen($defeito_reclamado);
  //    $os = $_POST['os'];

if (isset($_POST['defeito_reclamado'])) {
  if ((strlen ($defeito_reclamado) == 0 AND ($login_fabrica == 95 OR $pedir_defeito_reclamado_descricao == 't')))
      $defeito_reclamado = "null";
  else if ((strlen($defeito_reclamado) == 0 AND ((!in_array($login_fabrica,array(46,95,120,201,123,124,125,126,127,128,129,134))) OR $pedir_defeito_reclamado_descricao == 't')))
      $msg_erro .= "Selecione o defeito reclamado.<br />";

  # HD 28155
  if ($defeito_reclamado == '0' AND  (!in_array($login_fabrica, array(19, 134)) || $login_fabrica == 42) ) {
      $msg_erro .= "Selecione o defeito reclamado.<br />";
  }
  if ($defeito_reclamado == '0' AND $login_fabrica == 19){
      if ($tipo_atendimento <> 6){
          $msg_erro .= "Selecione o defeito reclamado.<br />";
          if ($login_fabrica == 19) {
            $msg_erro = "Preencher os campos obrigatórios. <br />";
          }
      }
  }
}


  //HD-3006621
  if ($login_fabrica == 24) {
    if (strlen($consumidor_cpf) <= 0) {
      $msg_erro .= "Digite o CPF/CNPJ do consumidor.<br />";
    }
    
    if (strlen($_POST['consumidor_nome']) == 0) {
      $msg_erro .= "Digite o nome do consumidor.<br />";
    }
    if (strlen($_POST['consumidor_cep']) == 0) {
      $msg_erro .= "Digite o CEP do consumidor.<br />";
    }
    if (strlen($_POST['consumidor_numero']) == 0) {
      $msg_erro .= "Digite o Número do endereço do consumidor.<br />";
    }
    if (strlen($_POST['consumidor_endereco']) == 0) {
      $msg_erro .= "Digite o Endereço do consumidor.<br />";
    }
    if (strlen($_POST['consumidor_cidade']) == 0) {
      $msg_erro .= "Digite a Cidade do consumidor.<br />";
    }
    if (strlen($_POST['consumidor_estado']) == 0) {
      $msg_erro .= "Digite o Estado do consumidor.<br />";
    }
    if (strlen($consumidor_fone) <= 0) {
      $msg_erro .= "Digite o Fone do consumidor.<br />";
    }

    $numero_serie = $_POST["numero_serie"];

  }

  if ($login_fabrica == 134 && !strlen($defeito_reclamado)) {
    $defeito_reclamado = "null";
  }

  if ($login_fabrica == 50) {
      $ver_data_fabricacao = $_POST['data_fabricacao'];
      $data_fabricacao_modif = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $ver_data_fabricacao);

      if(!$ver_data_fabricacao) {
          $msg_erro .= " Erro: Informe a data de fabricação.<br />";
      }
      if(strlen($msg_erro)==0){
          list($df, $mf, $yf) = explode("/", $ver_data_fabricacao);
          if(!checkdate($mf,$df,$yf))
              $msg_erro .= "Data de fabricação Inválida.<br />";
      }

      if(strlen($msg_erro)==0){
          $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
          $resX = pg_query ($con,$sqlX);
          $aux_atual = pg_fetch_result ($resX,0,0);
      }
      if(strlen($msg_erro)==0){
          if(empty($aux_atual)){
              $msg_erro .= "Data de fabricação Inválida.<br />";
          }
      }
      if(strlen($msg_erro)==0){
          $sqlX = "SELECT '$aux_atual'::date  > '$data_fabricacao_modif'";
          $resX = pg_query($con,$sqlX);
          $periodo_data = pg_fetch_result($resX,0,0);
      }
      if(strlen($msg_erro)==0){
          if($periodo_data == f){
              $msg_erro .= "Data de fabricação Inválida.<br />";
          }
      }
  }

  if(in_array($login_fabrica, array(11,104,123,172)) ){
    $xconsumidor_celular = (strlen($_POST['consumidor_celular']) > 0) ? $_POST['consumidor_celular'] : "null";

    if  ($login_fabrica == 123) {
      if (empty($xconsumidor_celular)) {
        $msg_erro .= "Preencha o campo Celular.<br />";
      }
    }
  }

  if ($login_fabrica == 35) { /* HD - 4203773*/
	  $arr_tdocs = array();
	  $objectId = $_POST['objectid'];
	  $sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs FROM tbl_tdocs WHERE referencia_id = 0 AND referencia = '$objectId' AND contexto = 'os'";
	  $resDocs = pg_query($con,$sqlDocs);
	  $filesByImageUploader = pg_num_rows($resDocs);

	  while ($fetch = pg_fetch_assoc($resDocs)) {
		  $arr_tdocs[] = $fetch['tdocs'];
	  }

    $ver_nf = $_FILES["foto_nf"]["name"];
    if (empty($ver_nf[0]) and $filesByImageUploader == 0) {
      $aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto LIMIT 1";
      $aux_res = pg_query($con, $aux_sql);
      $aux_par_ad = (array) json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'));

      if (empty($aux_par_ad["anexar_nf_os"]) || $aux_par_ad["anexar_nf_os"] != "nao") {
        $msg_erro .= "Por favor inserir anexo da Nota Fiscal <br />";
      }
    }
  }

  /*HD - 4276928*/
  if ($login_fabrica == 91) {
    if (empty($_POST["consumidor_cpf"])) {
      $aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
      $aux_res = pg_query($con, $aux_sql);
      $aux_par = json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'), true);

      if (!empty($aux_par["informar_cpf_cnpj"])) {
        $informar_cpf_cnpj = $aux_par["informar_cpf_cnpj"];
      } else {
        $informar_cpf_cnpj = "true";
      }

      if ($informar_cpf_cnpj == "true") {
        $msg_erro .= "Por favor informar o CPF / CNPJ do consumidor <br>";
      }
    }
  }

  #HD 389165
  if (!in_array($login_fabrica,array(42,46,74,86,115,116,117,120,201,123,124,125,126,127,128,129))) {

      if ($pedir_defeito_reclamado_descricao == 't' AND ($xdefeito_reclamado_descricao == 'null' OR strlen($xdefeito_reclamado_descricao) == 0)){
          $msg_erro .= " Erro: Digite o defeito reclamado.<br />";
      }

  } else if ($defeito_reclamado == 'null' and ($login_fabrica==86 or $login_fabrica == 74)){

      $msg_erro .=" Selecione um Defeito Reclamado.<br/>";

      }else{
      if(empty($defeito_reclamado)){
          $defeito_reclamado = "null";
      }
  }

  if ($login_fabrica == 42 and $tipo_atendimento_et == "t" and (strlen($defeito_reclamado) == 0 or $defeito_reclamado == "null")) {
      $msg_erro .="Erro: Selecione um Defeito Reclamado.<br/>";
  }

  //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
  if ($login_fabrica == 3) {
      $sql = "
      SELECT
      tbl_linha.linha

      FROM
      tbl_produto
      JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha

      WHERE
      tbl_linha.linha = 528
      AND tbl_produto.referencia='" . $_POST["produto_referencia"] . "'";
      $res = pg_query($con, $sql);

      if (pg_num_rows($res)) {
          if ($xdefeito_reclamado_descricao == 'null' OR strlen($xdefeito_reclamado_descricao) == 0) {
              $msg_erro .= "Digite o defeito reclamado adicional.<br />";
          }
      }
  }

  //HD 73930 18/02/2009
  $coa_microsoft = trim($_POST['coa_microsoft']);
  if (strlen($coa_microsoft) == 0) {
      if ($login_fabrica == 43) {
          // email da Gisele para Samuel pedindo para retirar obrigatoriedade. 20/02/2009
          //$msg_erro .= " Digite o COA MIcrosoft.";
      }
  }
  //
  if ($login_fabrica == 5) { // hD 61255
      if (strlen($xconsumidor_numero) == 0) $msg_erro .= " Digite o número do endereço do consumidor. <br />";
      if (strlen($xconsumidor_cep)    == 0 or $xconsumidor_cep == 'null') $msg_erro .= " Digite o CEP do consumidor. <br />";
  }

  if ($login_fabrica == 5) {
      if ($xconsumidor_fone == 'null' || $xconsumidor_fone == "") $msg_erro .= " Digite o telefone do consumidor. <br />";
  }

  //HD 206869: Exigir número de telefone do consumidor para a Salton
  //HD 413556 - Campos obrigatórios para a LeaderShip
  if ($fabrica_fone_cons_obrigatorio and $consumidor_revenda == 'C') {
      if  (($xconsumidor_fone == 'null' || $xconsumidor_fone == "") &&
           ($xconsumidor_celular == 'null' || $xconsumidor_celular == "") &&
           ($xconsumidor_fone_comercial == 'null' || $xconsumidor_fone_comercial == "")) {
          $msg_erro = "Digite pelo menos um telefone para o consumidor (Telefone, Celular ou Telefone Comercial)<br />";
      }/* else{
          if($login_fabrica == 3) {
              $fone_verifica = (!empty($xconsumidor_fone) and $xconsumidor_fone <> 'null') ? $xconsumidor_fone : ((!empty($xconsumidor_celular) and $xconsumidor_celular <> 'null')?$xconsumidor_celular:$xconsumidor_fone_comercial);
              if($fone_verifica <> 'null') {
                  $fone_valido = (!is_bool($fone_verifica = checaFone($fone_verifica)));
                  if(!$fone_valido) {
                      $msg_erro .= "Telefone do consumidor inválido, insira o numero no seguinte formato (99)9999-9999<br />";
                  }
              }
          }
      }*/
      /* Retirado a regra, pois foi inserido a nova regra para validação de celular */
  }

  /*HD - 6078292*/
  if ($login_fabrica == 3) {
    $aux_data_parametro = date("05-09-18");
    $aux_data_hoje  = date("d-m-y");

    if ($aux_data_hoje >= $aux_data_parametro) {
      if (strlen($_POST["tipo_atendimento"]) == 0) {
          $msg_erro .= "Favor informar o tipo de atendimento<br>";
      }
    }
  }

  if ($login_fabrica == 14 || $login_fabrica == 52 || ($login_fabrica > 100 && $login_fabrica != 137)) {
      if (strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
          $sql = "SELECT  tbl_produto.numero_serie_obrigatorio
                  FROM    tbl_produto
                  WHERE   (upper(tbl_produto.referencia_pesquisa) = upper($produto_referencia) or upper(tbl_produto.referencia) = upper($produto_referencia))
                  AND     tbl_produto.fabrica_i = $login_fabrica";
          $res = @pg_query($con,$sql);
          if (pg_num_rows($res) > 0) {
              $numero_serie_obrigatorio = trim(pg_fetch_result($res,0,numero_serie_obrigatorio));

              if ($numero_serie_obrigatorio == 't') {
                  $msg_erro .= "Nº de Série para o produto $produto_referencia é obrigatório.<br />";
              }
          }
      }
  }

  $serie_auditoria = (($login_fabrica == 140 OR $login_fabrica == 141) && $serie_auditoria == "TRUE") ? $serie_auditoria : "FALSE";
  if (($login_fabrica == 50 && $xproduto_serie !== 'null') || in_array($login_fabrica,[120,201])) {
    if ($login_fabrica == 50) {
      if (strlen($produto_serie) > 20) $msg_erro .= "Número de série não pode ser maior que 12 dígitos <br />";
      if (strlen($xproduto_serie) == 'null') $serie_auditoria = "TRUE";
    }

    $sql = "SELECT serie FROM tbl_numero_serie WHERE serie = $xproduto_serie AND fabrica = $login_fabrica";
    $res = @pg_query($con,$sql);

    if (pg_num_rows($res) == 0) {
        $serie_auditoria = "TRUE";
    }
  }

  //Chamado 2354
  if ($login_fabrica == 15) {
      if ($consumidor_revenda == 'C') {
          if (strlen($xconsumidor_endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br />";
          if (strlen($xconsumidor_numero)   == 0) $msg_erro .= " Digite o número do endereço do consumidor. <br />";
          if (strlen($xconsumidor_bairro)   == 0) $msg_erro .= " Digite o bairro do consumidor. <br />";
          if ($xconsumidor_fone == 'null'       ) $msg_erro .= " Digite o telefone do consumidor. <br />";
      }
  }
  ##### FIM DA VALIDAÇÃO DOS CAMPOS #####

  #if ($login_fabrica == 19 and $login_posto == 14068) echo "aqui ";
  #echo "<br />";
  #flush;
  // HD 51964
  if ( in_array($login_fabrica, array(11,172)) ) {
      if ($consumidor_revenda == 'R') {
          if ($xrevenda_fone == 'null' or strlen(trim($xrevenda_fone)) == 0) $msg_erro .= " Digite o telefone da revenda. <br />";
      }
      if ($consumidor_revenda == 'C') {
          if ($xconsumidor_fone=='null' or strlen($xconsumidor_fone) == 0) $msg_erro .= " Digite o telefone do consumidor.<br />";
      }
  }
  $os_reincidente = "'f'";

  if ($login_fabrica == 51) {
      if ($xrevenda_fone == 'null' or strlen(trim($xrevenda_fone)) == 0) $msg_erro .= " Erro: Digite o telefone da revenda. <br />";
  }

    /*TAKASHI 18-12 HD-854*/
  if ($login_fabrica == 3 and $login_posto == 6359) {
      $sqlX = "SELECT to_char ('$xdata_abertura'::date - INTERVAL '90 days', 'YYYY-MM-DD')";
      $resX = @pg_query($con,$sqlX);
      $data_inicial = pg_fetch_result($resX,0,0);
      //echo $sqlX;
      $sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
      $resX = @pg_query($con,$sqlX);
      $data_final = pg_fetch_result($resX,0,0);

      if (strlen($produto_serie) > 0) {
          $sql = "SELECT  tbl_os.os            ,
                          tbl_os.sua_os        ,
                          tbl_os.data_digitacao,
                          tbl_os.finalizada,
                          tbl_os.data_fechamento
                  FROM    tbl_os
                  JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
                  WHERE   tbl_os.serie   = '$produto_serie'
                  AND     tbl_os.fabrica = $login_fabrica
                  AND     tbl_produto.numero_serie_obrigatorio IS TRUE
                  AND     tbl_produto.linha=3
                  ORDER BY tbl_os.data_abertura DESC
                  LIMIT 1";
          $res = @pg_query($con,$sql);
          //if ($ip=="201.42.46.223"){ echo "$sql"; }
          //AND     tbl_os.data_fechamento::date BETWEEN '$data_inicial' AND '$data_final'
          //linha 3, pois é a linha audio e video
          if (pg_num_rows($res) > 0) {
              $xxxos      = trim(pg_fetch_result($res,0,os));
              $xxfinalizada   = trim(pg_fetch_result($res,0,finalizada));
              $xx_sua_os   = trim(pg_fetch_result($res,0,sua_os));
              $xxdata_fechamento =   trim(pg_fetch_result($res,0,data_fechamento));

              if (strlen($xxfinalizada) == 0) { //aberta
                  $os_reincidente = "'t'";
                  $msg_erro .= "Este Produto já possui ordem de serviço em aberto. Por favor consultar OS $xx_sua_os.<br />";
              } else {//fechada
                  if (($xxdata_fechamento > $data_inicial) and ($xxdata_fechamento < $data_final)) {
                      $os_reincidente = "'t'";
                  }//se a data de fechamento da ultima OS estiver no periodo de 90 dias.. seta como reincidente
              }
          }
      }
  }

  if ($login_fabrica == 79) { // HD 78055
      $sql = "SELECT cnpj,fone,contato_email
              FROM tbl_posto
              JOIN tbl_posto_fabrica USING(posto)
              WHERE fabrica = $login_fabrica
              AND   tbl_posto.posto = $login_posto ";
      $res = pg_query($con,$sql);

      if(strlen($xconsumidor_cpf) == 0 or $xconsumidor_cpf=='null') {
          $xconsumidor_cpf = preg_replace("/\D/","",pg_fetch_result($res,0,cnpj));
      }
      if(strlen($xconsumidor_fone) == 0 or $xconsumidor_fone=='null') {
          $xconsumidor_fone = pg_fetch_result($res,0,fone);
      }
      if(strlen($xconsumidor_email) == 0 or $xconsumidor_email=='null') {
          $xconsumidor_email = pg_fetch_result($res,0,contato_email);
      }

  }
  /*TAKASHI 18-12 HD-854*/

  #if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

  #if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

  #if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

  if ($login_fabrica == '134') {
      $pserie = $_POST['produto_serie'];
      $ok_serie = true;

      if (is_numeric($pserie)) {
          if (strlen($pserie) <> 4) {
              $ok_serie = false;
          } else {
              $pre = $pserie[0] . $pserie[1];
              $ipre = (int) $pre;

              if ($ipre > 12) {
                  $ok_serie = false;
              }
          }
      } else {
          $ok_serie = false;
      }

      if (false === $ok_serie) {
          $msg_erro.= 'Número de série inválido.<br/>';
      }
  }
  $produto = 0;

  if (strlen($_POST['produto_voltagem']) == 0)    $voltagem = "null";
  else    $voltagem = "'". $_POST['produto_voltagem'] ."'";
  //HD 413556
  if ($login_fabrica == 95 and $voltagem == 'null') $msg_erro .= 'Informe a Voltagem do produto.<br />';

  if($login_fabrica == 94 and $posto_interno == true){
    $cond_posto_interno = " AND tbl_produto.uso_interno_ativo ";
  }else{
    $cond_posto_interno = " AND tbl_produto.ativo IS TRUE ";
  }

  if ($login_fabrica == 3) {
    $cond_posto_interno = " AND (tbl_produto.ativo IS TRUE OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't')) ";
  }

  if (strlen($msg_erro) == 0) {
     $sql = "SELECT tbl_produto.produto, tbl_produto.linha, tbl_produto.abre_os
              FROM   tbl_produto
              JOIN   tbl_linha USING (linha)
              WHERE  (UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) or UPPER(tbl_produto.referencia) = UPPER($produto_referencia)) ";

      if ($login_fabrica == 1) {
          $voltagem_pesquisa = str_replace("'","",$voltagem);
          $sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
      }
      $sql .= " AND    tbl_linha.fabrica      = $login_fabrica
              $cond_posto_interno ";

      $res = @pg_query($con,$sql);

      if (@pg_num_rows ($res) == 0) {
          $msg_erro .= " Produto $produto_referencia não cadastrado.<br />";
      } else {
          $produto = @pg_fetch_result ($res,0,produto);
          $linha   = @pg_fetch_result ($res,0,linha);
          if ( in_array($login_fabrica, array(11,172)) ) {
              $abre_os = pg_fetch_result($res, 0, "abre_os");

      $sqlPosto = "SELECT permite_envio_produto
                  FROM tbl_posto
                          JOIN tbl_posto_fabrica USING(posto)
                          WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                          AND tbl_posto_fabrica.posto = $login_posto";
              $resPosto = pg_query($con, $sqlPosto);

              $permite_envio_produto = pg_fetch_result($resPosto, 0, "permite_envio_produto");

              if ($abre_os == "f" && $permite_envio_produto == "f") {
                  $msg_erro = "Não é permitido abrir OS para este produto<br />";
              }
          }

          if (isFabrica(19)) {
              //hd 4774 takashi 27/09/07
              if ($tipo_atendimento == 2 and in_array($linha, [260, 263]))
                  $msg_erro = "Tipo de atendimento não permitido para o produto<br />";
              if ($linha == 928 and $tipo_atendimento < 81 and $tipo_atendimento != 4)
                  $msg_erro = "Tipo de atendimento não permitido para o produto<br />";
          }
      }
  }
// die("DIE HARD...");
  if ($login_fabrica == 1) {
      $sql =    "SELECT tbl_familia.familia, tbl_familia.descricao
              FROM tbl_produto
              JOIN tbl_familia USING (familia)
              WHERE tbl_familia.fabrica = $login_fabrica
              AND   tbl_familia.familia = 347
              AND   tbl_produto.produto = $produto;";
      $res = @pg_query($con,$sql);
      if (pg_num_rows($res) > 0) {
          $xtipo_os_cortesia = "'Compressor'";
      }else{
          $xtipo_os_cortesia = 'null';
      }
  }else{
      $xtipo_os_cortesia = 'null';
  }

  #----------- OS digitada pelo Distribuidor -----------------
  $digitacao_distribuidor = "null";
  if ($distribuidor_digita == 't'){
      $codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
      $codigo_posto = str_replace (" ","",$codigo_posto);
      $codigo_posto = str_replace (".","",$codigo_posto);
      $codigo_posto = str_replace ("/","",$codigo_posto);
      $codigo_posto = str_replace ("-","",$codigo_posto);

      if (strlen ($codigo_posto) > 0) {
          $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
          $res = @pg_query($con,$sql);
          if (pg_num_rows ($res) <> 1) {
              $msg_erro = "Posto $codigo_posto não cadastrado<br />";
              $posto = $login_posto;
          }else{
              $posto = pg_fetch_result ($res,0,0);
              if ($posto <> $login_posto) {
                  $sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
                  $res = @pg_query($con,$sql);
                  if (pg_num_rows ($res) <> 1) {
                      $msg_erro = "Posto $codigo_posto não pertence a sua região<br />";
                      $posto = $login_posto;
                  }else{
                      $posto = pg_fetch_result ($res,0,0);
                      $digitacao_distribuidor = $login_posto;
                  }
              }
          }
      }
  }
    #------------------------------------------------------
  if($login_fabrica==15 and $tipo_atendimento==22){
      /*
          descricao         | familia
      --------------------------+---------
      Purificador Convencional |     787
      Purificador Eletrônico   |     788
      Purificador Hot & Cold   |     789
      Purificador Purifive          1299
      HD 107103 acrescentada a familia abaixo
      Purificador E Purifive   |     1309
      HD 241943 mais linhas
      1310 | Purificador E Mineralizer
      1311 | Purificador E Sterilizer


      */

      $sql_5 = "SELECT produto
            FROM tbl_produto
            where familia IN (787,788,789,1299,1309,1310,1311)
            AND produto = '$produto' ; ";
      $res_5 = pg_query($con,$sql_5);
      if(pg_num_rows($res_5) == 0){
          $msg_erro = "Esta OS não pode ser aberta porque o produto da instalação não é um purificador.<br />";
      }

  }

  $os_offline = $_POST['os_offline'];
  if (strlen ($os_offline) == 0) $os_offline = "null";

  if ( !in_array($login_fabrica, array(7,11,15,172)) ) {
      $prateleira_box = strtoupper(trim($_POST['prateleira_box']));
      if (strlen ($prateleira_box) == 0) $prateleira_box = " ";
  }

  if(in_array($login_fabrica, array(11,172))){
      $codigo_interno_digitado = $_POST["codigo_interno"];
      $xversao = "'".$codigo_interno_digitado."'";
  }

  //HD 932838
  if($login_fabrica==59 AND !empty($produto) and $xproduto_serie <> 'null') {
      $sql = "SELECT fn_serie_controle_sightgps($produto,$login_fabrica,$xproduto_serie)";
      $res = pg_query($con,$sql);
      $msg_erro = pg_last_error($con);
  }

  if($login_fabrica==3) {
        if (strlen($_POST['atendimento_domicilio']) == 0) $xatendimento_domicilio = 'null';
        else        $xatendimento_domicilio = $_POST['atendimento_domicilio'];

        if($xatendimento_domicilio=='t'){
            $tipo_atendimento = '37';
        }
  }

  //HD 20862 20/6/2008
  if(strlen(trim($_POST['condicao']))==0) $condicao= "";
  else                                    $condicao= $_POST['condicao'];

  // HD 51454
  if(($login_tipo_posto == 214 OR $login_tipo_posto == 215 OR $login_tipo_posto == 7) and $login_fabrica ==7 ) {
      if(strlen($condicao) == 0) {
          $msg_erro .= "Por favor, selecione a condição de pagamento<br />";
      }
  }

  $desconto_peca            = trim($_POST ['desconto_peca']);

  if(strlen($desconto_peca)==0){
      $xdesconto_peca = '0';
  }else{
      $xdesconto_peca = $desconto_peca;
  }

  if (strlen($desconto_peca)>0 AND $desconto_peca>100){
      $xdesconto_peca = 100;
  }

  $rg_produto          = trim($_POST ['rg_produto']);
  if (strlen ($rg_produto) == 0) $rg_produto = null;
  $os_posto            = trim($_POST ['os_posto']);
  if (strlen ($os_posto) == 0) $os_posto = null;
  if($login_fabrica == 30){
      if (strlen($os_posto) > 0 AND strlen($os_posto) < 8) {
          $msg_erro = "OS Revendedor deve ter no mínimo 8 dígitos.<br />";
      }
  }

  if (in_array($login_fabrica, [144]) && $posto_interno && empty($os_posto)) {
    $msg_erro .= "Preencha o campo Número Único <br />";
  }

  if (in_array($login_fabrica,array(7,52,30,46,74,81,96,114,115,116,117,120,201,122,123,124,125,126,127,128,129,131,132,134,136,$fabrica_pre_os))) {
      $admin           = trim($_POST['admin']);
      $cliente_admin   = trim($_POST['cliente_admin']);
      $hd_chamado      = (int)trim($_POST['hd_chamado']);
      $hd_chamado_item = (int)trim($_POST['hd_chamado_item']);
  }

  if (strlen($hd_chamado)==0 or $hd_chamado == 0 ) {

      $hd_chamado = 'null';
  }

  if ($login_fabrica == 42 and empty($msg_erro) && $os_cortesia != 't') {
      $sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
      $res = pg_query($con, $sql);
      $tipo_atendimento_et = pg_result($res, 0, "entrega_tecnica");

      if ($cook_tipo_posto_et == "t" or $tipo_atendimento_et == "t") {
          $sql = "SELECT produto, entrega_tecnica FROM tbl_produto WHERE produto = $produto AND entrega_tecnica IS TRUE";
          $res = pg_query($con, $sql);
          if (pg_num_rows($res) == 0) {
              $msg_erro .= "Este não é um produto de entrega técnica<br />";
          }
      }
  }

  if(in_array($login_fabrica,array(15))){
      try{
        $cep = $_POST['consumidor_cep'];
        if(!CEP::consulta($cep))
          throw new Exception('CEP Inválido');
      }
      catch(Exception $ex){
        $msg_erro .= $ex->getMessage();
      }
  }


  if($login_fabrica == 117 or $login_fabrica == 128){
      $garantia_estendida = $_POST['garantia_estendida'];
      if($garantia_estendida){
          $opcao_garantia_estendida = $_POST['opcao_garantia_estendida'];
          if($opcao_garantia_estendida){
              $xcertificado_garantia = ($opcao_garantia_estendida == "t") ? "'12'" : "'6'";
          }else{
              $msg_erro .= "Informe se produto foi instalado por uma autorizada $login_fabrica_nome<br />";
          }
      }

  }
  
  $res = @pg_query($con,"BEGIN TRANSACTION");

  if (strlen($msg_erro) == 0) {
    if($login_fabrica == 140){

      if($entrega_tecnica == "t"){

        $sql_cpf = "SELECT * FROM tbl_os WHERE consumidor_cpf = $xconsumidor_cpf AND fabrica = $login_fabrica AND posto = $login_posto AND data_abertura = CURRENT_DATE";
        $res_cpf = pg_query($con, $sql_cpf);

        if(pg_num_rows($res_cpf) > 0){
          $pct = 1.00;
          $valor_entrega_tecnica =  ($xpreco_produto / 100) * $pct;
        }else{
          $pct = 1.50;
          $valor_entrega_tecnica =  ($xpreco_produto / 100) * $pct;
        }
        $valor_entrega_tecnica = number_format($valor_entrega_tecnica, 2);

      }

    }

      /*================ INSERE NOVA OS =========================*/
      //  O campo cidade é de 30 chars... Tem cidades com mais caracteres. Por enquanto, vamos cortar

    //  (combinado com Samuel, 24/02/2010. Manuel.)

        if($login_fabrica == 91){
            if ($tipo_atendimento <> 91) {
                $qtde_km = "NULL";
            } else {
                $qtde_km2 = number_format($qtde_km2,2,'.','');
                if(strlen(trim($distancia_km))== 0){
                    $distancia_km = $qtde_km2;
                }
                if($qtde_km2 <> $distancia_km ){
                    $qtde_km = $distancia_km;
                }else{
                    $qtde_km = $qtd_km2;
                }
                $qtde_km = str_replace(",", ".", $qtde_km);
                if(strlen($qtde_km) == 0) $qtde_km = 0 ;
                $qtde_km = "'$qtde_km'";
            }
        }
    if ($login_fabrica == 30 && empty($qtde_km)) {
      $qtde_km = 'null';
    }

    if($login_fabrica == 50){
        if (strlen(trim($xdefeito_reclamado_descricao)) > 0) { //HD-3331834
            $defeito_reclamado = $_POST['defeito_reclamado_descricao'];
            $sql_def_desc = "SELECT defeito_reclamado, descricao, codigo from tbl_defeito_reclamado where fabrica=$login_fabrica and defeito_reclamado = $defeito_reclamado";
            $res_def_desc = pg_query($con, $sql_def_desc);
            $xdefeito_reclamado_descricao = pg_fetch_result($res_def_desc, 0, 'descricao');
            $xdefeito_reclamado_descricao = "'".$xdefeito_reclamado_descricao."'";
        }
        if (filter_input(INPUT_POST,'posto_proximo')) {
            $posto_proximo = filter_input(INPUT_POST,'posto_proximo');
            $posto_cobra_km = array("posto proximo" => $posto_proximo);

        }
    }

    if ($login_fabrica == 24) {

      $xconsumidor_id = $_POST['consumidor_id'];

      if (empty($xconsumidor_id)) {
         
        $sql = "SELECT cidade FROM tbl_cidade where nome = $xconsumidor_cidade";
        
        $res = pg_query($con, $sql);
        
        $cidade = pg_fetch_result($res, 0, cidade);
		if(!empty($cidade)) {       
 
			$queryInsertCliente = "INSERT INTO tbl_cliente (
				nome, 
				endereco, 
				numero, 
				complemento, 
				bairro, 
				cep, 
				cidade,
				fone,
				cpf,
				email,
				estado
					) 
					VALUES (
						$xconsumidor_nome, 
						'$xconsumidor_endereco',
						'$xconsumidor_numero', 
						$xconsumidor_complemento,
						'$xconsumidor_bairro',
						$xconsumidor_cep,
						$cidade,
						$xconsumidor_fone,
						$xconsumidor_cpf,
						$xconsumidor_email,
						$xconsumidor_estado
					) RETURNING cliente";

			  $query = pg_query($con, $queryInsertCliente);

			  $id_cliente = pg_fetch_result($query, 0, cliente);

			  $insertFabricaCliente = "INSERT INTO tbl_fabrica_cliente (fabrica, cliente) 
				  VALUES ($login_fabrica, $id_cliente)";

			  $insertFabricaCliente = pg_query($con, $insertFabricaCliente);

			  $msg_erro = pg_last_error();
		}
      } else {

        $sql = "SELECT cidade FROM tbl_cidade where nome = $xconsumidor_cidade";
      
        $res = pg_query($con, $sql);
      
        $cidade = pg_fetch_result($res, 0, cidade);
      
		if(!empty($cidade)) {        
			$queryUpdateCliente = "UPDATE tbl_cliente 
								   SET
									  endereco    = '$xconsumidor_endereco',
									  numero      = '$xconsumidor_numero',
									  complemento = $xconsumidor_complemento,
									  bairro      = '$xconsumidor_bairro',
									  cep         = $xconsumidor_cep,
									  cidade      = $cidade,
									  fone        = $xconsumidor_fone,
									  cpf         = $xconsumidor_cpf,
									  email       = $xconsumidor_email,
									  estado      = $xconsumidor_estado
									WHERE cliente = $xconsumidor_id";

			$query = pg_query($con, $queryUpdateCliente);

			if (strlen(pg_last_error($con)) > 0) {
			  $msg_erro .= pg_last_error($con);
			}
		}
      }
    }

    $is_insert = false;
    
    if($login_fabrica == 24){
        $consumidor_cpf = trim($_POST['consumidor_cpf']);
        $consumidor_cpf = str_replace ("-","",$consumidor_cpf);
        $consumidor_cpf = str_replace (".","",$consumidor_cpf);
        $consumidor_cpf = str_replace ("/","",$consumidor_cpf);
        $consumidor_cpf = trim (substr ($consumidor_cpf,0,14));

        if(strlen($consumidor_cpf) == 14){
            $_POST['consumidor_revenda'] = 'R';
        }else if(strlen($consumidor_cpf) == 11){
            $_POST['consumidor_revenda'] = 'C';
        }
    }

    if (strlen($os) == 0 ) {
        $is_insert = true;

        if ($login_fabrica == 30) {
            $tipo_gravar = "INSERT";
        }

          if (strlen($hd_chamado) > 4 and is_int($hd_chamado)) {
              $sql_hd = "SELECT os, sua_os FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $login_posto AND hd_chamado = $hd_chamado";
              $res_hd = pg_query($con,$sql_hd);
              if(pg_num_rows($res_hd) > 0){
                  $sua_os_aberta = pg_fetch_result($res_hd,0,'sua_os');
                  if ($login_fabrica == 81 OR $login_fabrica == 122) {
                      $msg_erro = "Já existe a OS({$sua_os_aberta}) aberta para o atendimento $hd_chamado <br />";
                  }
              }
          }

          // MLG 03/12/2010 HD 326935 - Campos limitados no início direto no _POST, e o campo
          //                            tbl_os.consumidor_cidade agora tem 70 caracteres


            if (strlen($tipo_atendimento) > 0) {
                $and_tipo_atendimento   = "tipo_atendimento ,";
                $value_tipo_atendimento = $tipo_atendimento.",";
            }
            if (strlen($os_cortesia) > 0) {
                $and_os_cortesia = "cortesia ,";
                $value_os_cortesia = "true ,";
                $xtipo_os_cortesia = "'OS Cortesia'";
            }

            if (isset($_POST["defeito_reclamado"])) {
              $mostraReclamado  = "defeito_reclamado ,";
              $mostraReclamadoV = $defeito_reclamado." ,";
            }

          $sql = "INSERT INTO tbl_os (
              $and_tipo_atendimento
              $and_os_cortesia
              posto                                                          ,
              fabrica                                                        ,
              sua_os                                                         ,
              sua_os_offline                                                 ,
              data_abertura                                                  ,
              hora_abertura                                                  ,
              cliente                                                        ,
              revenda                                                        ,
              consumidor_nome                                                ,
              consumidor_cpf                                                 ,
              consumidor_fone                                                ,
              consumidor_celular                                             ,
              consumidor_fone_comercial                                      ,
              consumidor_fone_recado                                         ,
              consumidor_endereco                                            ,
              consumidor_numero                                              ,
              consumidor_complemento                                         ,
              consumidor_bairro                                              ,
              consumidor_cep                                                 ,
              consumidor_cidade                                              ,
              consumidor_estado                                              ,
              revenda_cnpj                                                   ,
              revenda_nome                                                   ,
              revenda_fone                                                   ,
              nota_fiscal                                                    ,
              data_nf                                                        ,
              produto                                                        ,
              serie                                                          ,
              qtde_produtos                                                  ,
              codigo_fabricacao                                              ,
              aparencia_produto                                              ,
              acessorios                                                     ,
              defeito_reclamado_descricao                                    ,
              consumidor_email                                               ,
              obs                                                            ,
              quem_abriu_chamado                                             ,
              consumidor_revenda                                             ,
              laudo_tecnico                                                  ,
              tipo_os_cortesia                                               ,
              troca_faturada                                                 ,
              os_offline                                                     ,
              os_reincidente                                                 ,
              digitacao_distribuidor                                         ,
              tipo_os                                                        ,
              qtde_km                                                        ,
              certificado_garantia                                           ,
              $mostraReclamado
              capacidade                                                     ,
              versao                                                         ,
              divisao                                                        ,
              rg_produto                                                     ,
              hd_chamado                                                     ,
              os_posto                                                       " ;

          if ($login_fabrica == 7 and strlen($condicao) > 0) {
              $sql .= ", condicao
                  , tabela ";
          }

          if ( !in_array($login_fabrica, array(7,11,15,172)) ) {
              $sql.=", prateleira_box ";
          }

          if ($login_fabrica == 52 or $login_fabrica == 96 or ($login_fabrica == 30 and strlen($admin > 0))) {
              $sql .= ($admin) ? ", admin " : '';
              $sql .= ($cliente_admin) ? ", cliente_admin " : '';
          }

          if ($login_fabrica == 15 OR $login_fabrica == 140) {
              $sql.=", valores_adicionais ";
          }

          if (in_array($login_fabrica, array(11,172))) {
            $sql .= " , observacao";
          }

          $sql .= ") VALUES (
              $value_tipo_atendimento
              $value_os_cortesia
              $posto                                                         ,
              $login_fabrica                                                 ,
              $sua_os                                                        ,
              $sua_os_offline                                                ,
              '$xdata_abertura'                                                ,
              $xhora_abertura                                                ,
              null                                                           ,
              (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
              $xconsumidor_nome                                              ,
              $xconsumidor_cpf                                               ,
              $xconsumidor_fone                                              ,
              $xconsumidor_celular                                          ,
              $xconsumidor_fone_comercial                                    ,
              $xconsumidor_fone_recado                                       ,
              '$xconsumidor_endereco'                                        ,
              '$xconsumidor_numero'                                          ,
              $xconsumidor_complemento                                       ,
              '$xconsumidor_bairro'                                          ,
              $xconsumidor_cep                                               ,
              $xconsumidor_cidade                                            ,
              $xconsumidor_estado                                            ,
              $xrevenda_cnpj                                                 ,
              $xrevenda_nome                                                 ,
              $xrevenda_fone                                                 ,
              $xnota_fiscal                                                  ,
              $xdata_nf                                                      ,
              $produto                                                       ,
              $xproduto_serie                                                ,
              $qtde_produtos                                                 ,
              $xcodigo_fabricacao                                            ,
              $xaparencia_produto                                            ,
              $xacessorios                                                   ,
              fn_retira_especiais($xdefeito_reclamado_descricao)             ,
              $xconsumidor_email                                             ,
              $xobs                                                          ,
              $xquem_abriu_chamado                                           ,
              $xconsumidor_revenda                                           ,
              $xlaudo_tecnico                                                ,
              $xtipo_os_cortesia                                             ,
              $xtroca_faturada                                               ,
              $os_offline                                                    ,
              $os_reincidente                                                ,
              $digitacao_distribuidor                                        ,
              $x_locacao                                                     ,
              $qtde_km                                                       ,
              $xcertificado_garantia                                         ,
              $mostraReclamadoV
              $xproduto_capacidade                                           ,
              $xversao                                                       ,
              $xdivisao                                                      ,
              '$rg_produto'                                                  ,
              $hd_chamado                                                    ,
              '$os_posto'                                                     ";

          if ($login_fabrica == 7 and strlen($condicao) > 0) {

              $sql.=",
                  $condicao                                                      ,
                  (SELECT tabela FROM tbl_condicao
                  WHERE fabrica = $login_fabrica AND condicao = $condicao )    ";
          }

          if ( !in_array($login_fabrica, array(7,11,15,172)) ) {
              $sql.=", '$prateleira_box' ";
          }

          if ($login_fabrica == 52 or $login_fabrica == 96 or ($login_fabrica == 30 and strlen($admin > 0))) {
              $sql .= ($admin) ? ", $admin " : '';
              $sql .= ($cliente_admin) ? ", $cliente_admin " : '';
          }

          if ($login_fabrica == 15 OR $login_fabrica == 140) {
              $sql.=", '$xpreco_produto' ";
          }

          if( in_array($login_fabrica, array(11,172)) ){
              $reclamacao_cliente = $_POST['reclamacao_cliente'];
              $sql .= "
                ,'{$reclamacao_cliente}')
                RETURNING  os
              ";
          } else {
            $sql .= "    ) RETURNING  os";
          }
    /*            if($login_fabrica == 24 and $login_posto == 669){
              #HD 153152
              #mail("igor@telecontrol.com.br", "SQL", "$sql");
    }*/
          //IDENTIFICA INSERÇÃO DE OS PARA VALIDAR INTERVENÇÃO NA LORENZETTI
          $nova_os = 1;

          $acao = "insert";
          $auditorLog = new AuditorLog('insert');
      } else {
          $acao = "update";
          $auditorLog = new AuditorLog();
          $auditorLog->retornaDadosTabela("tbl_os", array('os' => $os, 'fabrica' => $login_fabrica));

          if($login_fabrica == 30){
              $tipo_gravar = "UPDATE";
          }
          if (strlen($tipo_atendimento) > 0) {
              $and_tipo_atendimento =  "tipo_atendimento            = ".$tipo_atendimento.",";
          }
            if (strlen($os_cortesia) > 0) {
                $and_os_cortesia = "cortesia = TRUE ,";
            }

          if (isset($_POST['defeito_reclamado'])) {
            $mostraReclamadoUp  = " defeito_reclamado = $defeito_reclamado , ";
          }

          $sql = "UPDATE tbl_os SET
                      $and_tipo_atendimento
                      $and_os_cortesia
                      data_abertura               = '$xdata_abertura'                 ,
                      hora_abertura               = $xhora_abertura                   ,
                      revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
                      consumidor_nome             = $xconsumidor_nome                 ,
                      consumidor_cpf              = $xconsumidor_cpf                  ,
                      consumidor_fone             = $xconsumidor_fone                 ,
                      consumidor_celular          = $xconsumidor_celular              ,
                      consumidor_fone_comercial   = $xconsumidor_fone_comercial       ,
                      consumidor_endereco         = '$xconsumidor_endereco'           ,
                      consumidor_numero           = '$xconsumidor_numero'             ,
                      consumidor_complemento      = $xconsumidor_complemento          ,
                      consumidor_bairro           = '$xconsumidor_bairro'             ,
                      consumidor_cep              = $xconsumidor_cep                  ,
                      consumidor_cidade           = $xconsumidor_cidade               ,
                      consumidor_estado           = $xconsumidor_estado               ,
                      revenda_cnpj                = $xrevenda_cnpj                    ,
                      revenda_nome                = $xrevenda_nome                    ,
                      revenda_fone                = $xrevenda_fone                    ,
                      nota_fiscal                 = $xnota_fiscal                     ,
                      data_nf                     = $xdata_nf                         ,
                      serie                       = $xproduto_serie                   ,
                      qtde_produtos               = $qtde_produtos                    ,
                      codigo_fabricacao           = $xcodigo_fabricacao               ,
                      aparencia_produto           = $xaparencia_produto               ,
                      defeito_reclamado_descricao = fn_retira_especiais($xdefeito_reclamado_descricao),
                      consumidor_email            = $xconsumidor_email                ,
                      consumidor_revenda          = $xconsumidor_revenda              ,
                      laudo_tecnico               = $xlaudo_tecnico                   ,
                      troca_faturada              = $xtroca_faturada                  ,
                      tipo_os_cortesia            = $xtipo_os_cortesia                ,
                      tipo_os                     = $x_locacao                        ,
                      acessorios                  = $xacessorios                      ,
                      qtde_km                     = $qtde_km                          ,
                      $mostraReclamadoUp
                      capacidade                  = $xproduto_capacidade              ,
                      versao                      = $xversao                          ,
                      divisao                     = $xdivisao                         ,
                      rg_produto                  = '$rg_produto'                       ,
                      os_posto                    = '$os_posto'                          ";

          if ($login_fabrica == 7 and strlen($condicao) > 0) {
                  $sql.=", condicao  = $condicao
                         , tabela    = (select tabela from tbl_condicao where fabrica = $login_fabrica and condicao = $condicao ) ";
          }

          if ( !in_array($login_fabrica, array(7,11,15,172)) ) {
              $sql.=", prateleira_box= '$prateleira_box' ";
          }

          if ($login_fabrica == 7) {
              $sql.=" , produto = $produto ";
          }

          $sql.="    WHERE os      = $os
                      AND   fabrica = $login_fabrica
                      AND   posto   = $posto;";

          if (in_array($login_fabrica, array(91, 131))) {
              if (empty($_POST['data_fabricacao'])) {
                  	$data_fabricacao = 'NULL';
              } else {
                  $data_fabricacao_post = $_POST['data_fabricacao'];
                  $data_fabricacao_array = explode('/', $data_fabricacao_post);

                  if (count($data_fabricacao_array) == 3) {
                      $data_fabricacao = "'" . $data_fabricacao_array[2] . '-' . $data_fabricacao_array[1] . '-' . $data_fabricacao_array[0] . "'";
                  } else {
                      	$data_fabricacao = 'NULL';
                  }
              }

             $sql.= " UPDATE tbl_os_extra SET data_fabricacao = $data_fabricacao WHERE os = $os; ";
          }
    }

    //pega dados para o auditor
    //verifica antes.
    if (strlen($os) > 0) {
        $sql_email_antes = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $res_email_antes = pg_query($con, $sql_email_antes);
        if(pg_num_rows($res_email_antes)>0){
            $dados_antes = pg_fetch_assoc($res_email_antes);
        }
    }

	$res = @pg_query ($con,$sql);
	$msg_erro = pg_last_error();
	if(strpos ($msg_erro,"data_abertura_futura") > 0){
        $msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
      }

	if(strpos ($msg_erro,"data_nf_superior_data_abertura") > 0){
        $msg_erro = " Data da nota deve ser inferior ou igual a data de abertura da OS.";
      }

     
    if ($acao == "insert") {
		$os = pg_fetch_result($res, 0, os);
		$auditorLog->retornaDadosTabela("tbl_os", array('os' => $os, 'fabrica' => $login_fabrica));
		$auditorLog->enviarLog("$acao", 'tbl_os', $login_fabrica."*".$os);
    }
   
     /*
       * @author William Castro <william.castro@telecontrol.com.br>
       * hd-6639553 -> Box Uploader
       */

      if (isset($_POST['anexo_chave']) AND strlen($os) > 0) {

        $anexo_chave = $_POST['anexo_chave'];

        $query_anexo = "SELECT *
                        FROM tbl_tdocs
                        WHERE fabrica = {$login_fabrica}
                        AND (hash_temp = '{$anexo_chave}' or referencia_id = $os)
                        AND situacao = 'ativo'";
        $res_anexo = pg_query($con, $query_anexo);

        if (pg_num_rows($res_anexo) > 0) {

          $nota = false;

          for ($i = 0; $i < pg_num_rows($res_anexo); $i++) {

            $imagem_id = pg_fetch_result($res_anexo, $i, tdocs);

            $imagem_tipo_json = pg_fetch_result($res_anexo, $i, obs);

            $imagem_tipo = json_decode($imagem_tipo_json);

            $imagem_tipo = $imagem_tipo[0]->typeId;

            if ($imagem_tipo == "notafiscal") {

              $nota = true;
            }

            $query_update = "UPDATE tbl_tdocs
                             SET referencia_id = {$os},
                             hash_temp = NULL
                             WHERE tdocs = {$imagem_id}";

            $resposta_anexa_imagem_os = pg_query($con, $query_update);
          }
        }

        if (!$nota) {

            $msg_erro = "Erro: Anexo de Nota Fiscal Obrigatório ";
        }

      }

    if($login_fabrica == 74 and empty($os)){
          $res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
          $os  = pg_fetch_result ($res,0,0);

          $data_inicial = date('Y-m-d', strtotime("-90 days"));
          $data_final = date('Y-m-d');

          $sql = "SELECT tbl_hd_chamado.hd_chamado, tbl_hd_chamado_extra.abre_os
                  FROM tbl_hd_chamado_extra
                  INNER JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
                  INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                  WHERE tbl_produto.referencia = '$referencia'
                  AND tbl_hd_chamado_extra.cpf = '$consumidor_cpf'
                  AND tbl_hd_chamado_extra.posto <> $login_posto
                  AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
                  AND tbl_hd_chamado_extra.abre_os = 't'
                  AND tbl_hd_chamado.fabrica = $login_fabrica ";
          $res = pg_query($con, $sql);

          for($b=0; $b<pg_num_rows($res); $b++){
              $hd_chamado = pg_fetch_result($res, $b, 'hd_chamado');

                $sql_hd_chamado_extra = "UPDATE tbl_hd_chamado_extra SET abre_os = 'f', os = $os WHERE hd_chamado = $hd_chamado";
                $res_hd_chamado_extra = pg_query($con, $sql_hd_chamado_extra);

                $sql_hd_chamado_item = "INSERT INTO tbl_hd_chamado_item (hd_chamado, data, comentario) VALUES ($hd_chamado, now(), ' O.S $os foi aberta em outro posto autorizado. Pré-os cancelada. ');";
                $res_hd_chamado_item = pg_query($con, $sql_hd_chamado_item);

                $sql_troca_posto = "UPDATE tbl_hd_chamado_extra SET posto = $login_posto WHERE hd_chamado = $hd_chamado";
                $res_troca_posto = pg_query($con, $sql_troca_posto);
          }
    }

    //pega dados para o auditor.
    //verifica depois.
    if (strlen($os) > 0 and strlen($msg_erro) == 0 ) {
        $sql_email_depois = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $res_email_depois = pg_query($con, $sql_email_depois);
        if(pg_num_rows($res_email_depois)>0){
            $dados_depois = pg_fetch_assoc($res_email_depois);
        }
    }

    if (strlen ($msg_erro) > 0) {

      if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0){
        $msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";
      }

      if(strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0){
        $msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
      }
    }

    if (strlen ($msg_erro) == 0) {

        if (strlen($os) == 0) {
          $res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
          $os  = pg_fetch_result ($res,0,0);
	      }

          if (in_array($login_fabrica, array(104))) {

            $os_remanufatura = $_POST["os_remanufatura"];

            if (empty($os_remanufatura)) {
              $os_remanufatura = "f";
            }

            $json_campos_adicionais = array(
              "os_remanufatura" => $os_remanufatura,
              "data_recebimento_produto" => $data_recebimento_produto
            );

            $select_campo_extra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res_campo_extra    = pg_query($con, $select_campo_extra);

            if (pg_num_rows($res_campo_extra) > 0) {
              $json_res_campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");

              if (!empty($json_res_campos_adicionais)) {
                $json_res_campos_adicionais = json_decode($json_res_campos_adicionais, true);
                $json_campos_adicionais     = array_merge($json_res_campos_adicionais, $json_campos_adicionais);
              }

              $query_campo_extra = "UPDATE tbl_os_campo_extra
                                    SET campos_adicionais = '".json_encode($json_campos_adicionais)."'
                                    WHERE fabrica = {$login_fabrica}
                                    AND os = {$os}";
              }else{

              $query_campo_extra = "INSERT INTO tbl_os_campo_extra
                                    (os, fabrica, campos_adicionais)
                                    VALUES
                                    ({$os}, {$login_fabrica}, '".json_encode($json_campos_adicionais)."')";
              }
              $res_campo_extra = pg_query($con, $query_campo_extra);

            if (strlen(pg_last_error()) > 0) {
              $msg_erro = "Erro ao gravar ordem de serviço";
            }
          }
          //vonder/

          if (in_array($login_fabrica, array(141,144)) && $posto_interno) {

            $os_remanufatura = $_POST["os_remanufatura"];

            if (empty($os_remanufatura)) {
              $os_remanufatura = "f";
            }


            $json_campos_adicionais = array(
              "os_remanufatura" => $os_remanufatura
            );


            $select_campo_extra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res_campo_extra    = pg_query($con, $select_campo_extra);

            if (pg_num_rows($res_campo_extra) > 0) {
              $json_res_campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");

              if (!empty($json_res_campos_adicionais)) {
                $json_res_campos_adicionais = json_decode($json_res_campos_adicionais, true);
                $json_campos_adicionais     = array_merge($json_res_campos_adicionais, $json_campos_adicionais);
              }

              $query_campo_extra = "UPDATE tbl_os_campo_extra
                                    SET campos_adicionais = '".json_encode($json_campos_adicionais)."'
                                    WHERE fabrica = {$login_fabrica}
                                    AND os = {$os}";
            } else {

              $query_campo_extra = "INSERT INTO tbl_os_campo_extra
                                    (os, fabrica, campos_adicionais)
                                    VALUES
                                    ({$os}, {$login_fabrica}, '".json_encode($json_campos_adicionais)."')";
            }

            $res_campo_extra = pg_query($con, $query_campo_extra);

            if (strlen(pg_last_error()) > 0) {
              $msg_erro = "Erro ao gravar ordem de serviço";
            }
          }

	if($login_fabrica == 30){

        if($tipo_gravar == "UPDATE"){

	    foreach($dados_antes AS $key => $value){

                if($dados_depois[$key] != $value){
                        $diferenca[$key] = $dados_depois[$key];
                }

            }

            foreach($diferenca as $indice=>$valor){
                $valor_de       = trim($dados_antes["$indice"]);
                $valor_para     = trim($dados_depois["$indice"]);

                if(strlen($valor_de) == 0 AND strlen($valor_para) == 0){
                  continue;
                }

		            $valor_de = (strlen($valor_de) == 0) ? "VAZIO" : $valor_de;
	              $valor_para = (strlen($valor_para) == 0) ? "VAZIO" : $valor_para;

                if($indice == 'produto'){
                    $msg .= "PRODUTO ". " de ". $_POST['produto_descricao_anterior'] . " para " . $_POST['produto_descricao'] . "\n";
                }

		if($indice == "tipo_atendimento"){
			if($valor_de == "VAZIO"){
			      $sql = "SELECT tbl_tipo_atendimento.descricao AS atendimento_depois,
				tbl_tipo_atendimento.fabrica
			      FROM    tbl_tipo_atendimento
			      WHERE tbl_tipo_atendimento.tipo_atendimento = $valor_para";
			      $res = pg_query($con,$sql);
			      $msg .= "TIPO ATENDIMENTO alterado para ".pg_fetch_result($res,0,atendimento_depois);
			}else{
			      $sql = "SELECT  antes.atendimento_antes,
				  depois.atendimento_depois
				FROM (
			      SELECT tbl_tipo_atendimento.descricao AS atendimento_antes,
				tbl_tipo_atendimento.fabrica
			      FROM    tbl_tipo_atendimento
			      WHERE tbl_tipo_atendimento.tipo_atendimento = $valor_de
				) antes JOIN (
			      SELECT tbl_tipo_atendimento.descricao AS atendimento_depois,
				tbl_tipo_atendimento.fabrica
			      FROM    tbl_tipo_atendimento
			      WHERE tbl_tipo_atendimento.tipo_atendimento = $valor_para
				) depois USING(fabrica)
				WHERE fabrica = $login_fabrica
			      ";
			       $res = pg_query($con,$sql);

			       $msg .= "TIPO ATENDIMENTO de ".pg_fetch_result($res,0,atendimento_antes)." para ".pg_fetch_result($res,0,atendimento_depois);
			    }
			}
		}

                $retirar = array('revenda', 'produto','admin','admin_altera','tipo_atendimento','qtde_produtos','defeito_reclamado','cliente');


                    if($indice == "data_modificacao"){
                        $valor_de       = mostra_data_hora($dados_antes["$indice"]);
                        $valor_para     = mostra_data_hora($dados_depois["$indice"]);
                    }

                    if($valor_de == "t"){
                        $valor_de = "Sim";
                    }elseif($valor_de == "f"){
                        $valor_de = "Não";
                    }

                    if($valor_para == "t"){
                        $valor_para = "Sim";
                    }elseif($valor_para == "f"){
                        $valor_para = "Não";
                    }

                    $indice_limpo = str_replace("_", " ", $indice);

                    $msg .= strtoupper($indice_limpo) . " de ". $valor_de . " para " . $valor_para . "\n";

                    $msg_gravar = "Os campos alterados foram: ".$msg;
        }else{
            $msg_gravar = "OS Cadastrada.";
        }

//         $msg .= "Orientação SAC ao Posto Autorizado: ".$_POST['orientacao_sac'];

        if(strlen($msg_gravar) > 0){
            $sql = "
                INSERT INTO tbl_os_interacao (
                    programa,
                    os,
                    data,
                    comentario,
                    interno,
                    posto
                ) VALUES (
                    '$programa_insert',
                    $os,
                    CURRENT_TIMESTAMP,
                    '$msg_gravar',
                    TRUE,
                    $login_posto
                );
            ";
            $res = pg_query($con,$sql);

            $sql = "SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE os = $os";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){

              verificaAlteracaoDadosAtendimento(pg_fetch_result($res, 0, 'hd_chamado'),$os);

            }
        }
    }

    }

    if (strlen ($msg_erro) == 0) {
      if (strlen($os) == 0) {
        $res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
        $os  = pg_fetch_result ($res,0,0);

        if (($login_fabrica == 15 ) or  ($fabrica_pre_os)){
          $sql_email = "SELECT tbl_admin.email
                                FROM tbl_hd_chamado
                                JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin AND tbl_admin.fabrica = $login_fabrica
                                WHERE tbl_hd_chamado.hd_chamado = $hd_chamado
                                AND tbl_hd_chamado.fabrica = $login_fabrica";
          $res_email = @pg_query ($con,$sql_email);

          if (strlen(pg_last_error($con)) > 0) {

              $msg_erro = pg_last_error($con);
              $msg_erro = substr($msg_erro,6);
          }else{

            $email_destino = pg_result($res_email,0,'email');
            //$email_destino = "william.lopes@telecontrol.com.br";
            $assunto = "Atendimento pré-os nº  $hd_chamado";
            $mensagem = "Para o atendimento  $hd_chamado foi aberta a Ordem de Serviço  numero $os http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os=$os ";
            $remetente = "<helpdesk@telecontrol.com.br>";
            $headers       = "Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
            mail($email_destino, utf8_encode($assunto), $mensagem, $headers);
          }
        }
      }
    }

  if($login_fabrica == 140){

    if($entrega_tecnica == "t"){
      $sql_valor_et = "INSERT INTO tbl_os_extra (os, i_fabrica, valor_total_deslocamento, mao_de_obra_adicional) VALUES ($os, $login_fabrica, $valor_entrega_tecnica, $pct)";
      $res_valor_et = pg_query($con, $sql_valor_et);

    }

  }

  if (strlen ($msg_erro) == 0) {

    if ($fabricas_image_uploader) {
        $filesByImageUploader = 0;

        $objectId = $_POST['objectid'];
        $sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs FROM tbl_tdocs WHERE referencia_id = 0 AND referencia = '$objectId' AND contexto = 'os'";
        $resDocs = pg_query($con,$sqlDocs);
        $filesByImageUploader = pg_num_rows($resDocs);
    }

    if(in_array($login_fabrica,array(42,126,137))){
		$amazonTC = new AmazonTC("os", $login_fabrica);
      $types = array("png", "jpg", "jpeg", "bmp", "pdf", 'doc', 'docx', 'odt');

      if ((strlen($_FILES["img_os_1"]["name"]) == 0 and strlen($_FILES["img_os_2"]["name"]) == 0) and !$filesByImageUploader) {
        $msg_erro .= "Por favor inserir anexo da Nota Fiscal <br />";
      }

      foreach ($_FILES as $key => $imagem) {

        if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
           if (strpos($key, 'img_os') !== false) {
            $type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
            if(!in_array($type, $types)){
              $pathinfo = pathinfo($imagem["name"]);
              $type = $pathinfo["extension"];
            }
            if (!in_array($type, $types)) {

              $msg_erro .= "Formato inválido, são aceitos os seguintes formatos: png, jpg, jpeg, bmp, doc, odt e pdf <br />";
              break;

            } else {

              if(strlen($os) > 0 ){
                $fileName = "anexo_os_{$login_fabrica}_{$os}_{$key}";
              }else{
                $os_upload = pg_fetch_result($sql, 0, 'os');
                $fileName = "anexo_os_{$login_fabrica}_{$os_upload}_{$key}";
              }

              $amazonTC->upload($fileName, $imagem, "", "");

              $link = $amazonTC->getLink("$fileName.{$type}", false, "", "");

            }
          }
        }
      }
    }
    if($login_fabrica == 114){
      $selo_obrigatorio = $_POST["selo_obrigatorio"];
      if($selo_obrigatorio == "t"){

        $upload_selo = $_FILES["upload_selo"];

        if($upload_selo["size"] > 0 && strlen($upload_selo["name"]) > 0){

          $types = array("png", "jpg", "jpeg", "bmp", "pdf");
          $type  = strtolower(preg_replace("/.+\//", "", $upload_selo["type"]));

          if ($type == "jpeg") {
              $type = "jpg";
          }

          if (!in_array($type, $types)) {
              $msg_erro = "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx";
          }

          if(strlen($msg_erro) == 0){

            $tem_selo = true;

            include_once S3CLASS;
            $s3 = new AmazonTC("os", $login_fabrica);

            $s3->upload("selo_{$login_fabrica}_{$os}", $upload_selo);

          }

        }else{
          $xdata_nf_selo = str_replace("'", "", $xdata_nf);
          if (strtotime($xdata_nf_selo.'+ 1 year') < strtotime($xdata_abertura) ) {
            $msg_erro = "Por favor insira a imagem do Selo Obrigatório <br />";
          }
        }
      }
    }

    if (strlen($os) == 0) {
        $res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
        $os  = pg_fetch_result ($res,0,0);
    }

    if (strlen($msg_erro) == 0) {

        if (in_array($login_fabrica, array(40,59))) {
            if (!empty($familia)) {
                if ($os) {
                    $sql       = "INSERT INTO tbl_os_campo_extra(os,fabrica,cor_produto) VALUES ($os,$login_fabrica,'$unidade_cor')";
                    $res       = pg_query($con,$sql);
                    $msg_erro .= pg_last_error();
                }
            }

            if($login_fabrica == 59) {
                $origem = $_POST['origem'];
                if(strlen($origem) > 0) {
                    $campos_adicionais = "{\"origem\":\"$origem\"}";
                }
            }

            if (!empty($campos_adicionais)) {
                if ($os) {
                    $sql       = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES ($os,$login_fabrica,'$campos_adicionais')";
                    $res       = pg_query($con,$sql);
                    $msg_erro .= pg_last_error();
                }
            }
        }

        if ($login_fabrica == 74 and !empty($data_nascimento)) {
            $qry_c_extra = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
            $arr_data_nascimento = array("data_nascimento" => $data_nascimento);

            if (pg_num_rows($qry_c_extra)) {
                $c_adicionais_old = json_decode(pg_fetch_result($qry_c_extra, 0, 'campos_adicionais'), true);

                if ($c_adicionais_old === NULL) {
                    $c_adicionais_old = array();
                }

                if (array_key_exists("data_nascimento", $c_adicionais_old)) {
                    $c_adicionais_old["data_nascimento"] = $data_nascimento;
                    $c_adicionais_new = json_encode($c_adicionais_old);
                } else {
                    $c_adicionais_new = json_encode(array_merge($c_adicionais_old, $arr_data_nascimento));
                }

                $sql_c_extra = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$c_adicionais_new}' WHERE os = $os";
            } else {
                $sql_c_extra = "INSERT INTO tbl_os_campo_extra (
                    os,
                    fabrica,
                    campos_adicionais
                ) VALUES (
                    $os,
                    $login_fabrica,
                    E'" . json_encode($arr_data_nascimento) . "'
                )";
            }

            $qry_c_extra = pg_query($con, $sql_c_extra);
        }

        if ($login_fabrica == 50 && is_array($posto_cobra_km)) {
            $campos_adicionais = json_encode($posto_cobra_km);
            $sqlCampos = "
                INSERT INTO tbl_os_campo_extra (
                    os,
                    fabrica,
                    campos_adicionais
                ) VALUES (
                    $os,
                    $login_fabrica,
                    E'".$campos_adicionais."'
                )
            ";
            $resCampos = pg_query($con,$sqlCampos);
        }

        //HD 16252 - Rotina de vários defeitos para uma única OS.
        if ($login_fabrica == 19) {

            # HD 28155
            if ($tipo_atendimento <> 6) {

                $numero_vezes      = 100;
                $array_integridade = array();

                for ($i = 0; $i < $numero_vezes; $i++) {

                    $int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);

                    if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
                    if (strlen($int_reclamado) == 0) continue;

                    $aux_defeito_reclamado = $int_reclamado;

                    array_push($array_integridade,$aux_defeito_reclamado);

                    $sql = "SELECT defeito_constatado_reclamado
                            FROM tbl_os_defeito_reclamado_constatado
                            WHERE os                = $os
                            AND   defeito_reclamado = $aux_defeito_reclamado";

                    $res = @pg_query ($con,$sql);
                    $msg_erro .= pg_last_error($con);

                    if (@pg_num_rows($res) == 0) {

                        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                    os,
                                    defeito_reclamado,
                                    fabrica
                                ) VALUES (
                                    $os,
                                    $aux_defeito_reclamado,
                                    $login_fabrica
                                )";

                        $res = @pg_query ($con,$sql);
                        $msg_erro .= pg_last_error($con);

                    }

                }

                //o defeito reclamado recebe o primeiro defeito constatado.
                if($login_fabrica == 19){
                  if (strlen($aux_defeito_reclamado) == 0) $msg_erro = "Quando lançar o Defeito Reclamado é necessário clicar em adicionar defeito. <br />";
                }else{
                  if (strlen($aux_defeito_reclamado) == 0) $msg_erro = "Quando lançar o defeito constatado é necessário clicar em adicionar defeito. <br />";
                }
            }

            if ($tipo_atendimento == 6 and $defeito_reclamado <> 0) {

                $numero_vezes = 100;
                $array_integridade = array();

                for ($i = 0; $i < $numero_vezes; $i++) {

                    $int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);

                    if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
                    if (strlen($int_reclamado)==0) continue;

                    $aux_defeito_reclamado = $int_reclamado;

                    array_push($array_integridade,$aux_defeito_reclamado);

                    $sql = "SELECT defeito_constatado_reclamado
                            FROM tbl_os_defeito_reclamado_constatado
                            WHERE os                = $os
                            AND   defeito_reclamado = $aux_defeito_reclamado";

                    $res = @pg_query ($con,$sql);
                    $msg_erro .= pg_last_error($con);

                    if (@pg_num_rows($res) == 0) {

                        $sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
                                    os,
				    defeito_reclamado,
				    fabrica
                                )VALUES(
                                    $os,
				    $aux_defeito_reclamado,
				    $login_fabrica
                                )";

                        $res = @pg_query ($con,$sql);
                        $msg_erro .= pg_last_error($con);

                    }

                }

                //o defeito reclamado recebe o primeiro defeito constatado.
                if (strlen($aux_defeito_reclamado) == 0) $msg_erro = "Quando lançar o defeito constatado é necessário clicar em adicionar defeito.<br />";

            }

        }

    }
    //hd 289254 precisava atualizar antes de chamar a funcao valida_os
    if (in_array($login_fabrica,array(7,46,74,81,85,90,114,115,116,117,120,201,122,123,124,125,126,127,128,129,131,132,134,136,$fabrica_pre_os)) ) {

        if ($login_fabrica <> 52 and $login_fabrica <> 30 and $login_fabrica != 96) {
        $hd_chamado = $_POST['hd_chamado'];

				if (strlen($hd_chamado) > 0) {

					$sqlinf = "UPDATE tbl_hd_chamado_extra
								  SET os = $os
								WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado;";

					$resinf = pg_query ($con,$sqlinf);

				}

		}
    }

    if ($login_fabrica == 96 AND !empty($hd_chamado)) { # HD 390996

        $origem_anexo  = dirname(__FILE__) . '/admin_cliente/anexos';

        // HD 871246 - MLG - Integrar com o anexo padrão no S3/AWS

        $curdir = getcwd();   // Salva o dir. atual

        chdir($origem_anexo); // Muda para o diretório onde podem estar os anexos
        $anexos = glob("$hd_chamado*");
        usort($anexos, 'cmpFileName'); // Ordena os arquivos pelo nome

        chdir($curdir);

        if (count($anexos)) {
            foreach ($anexos as $anexo) {
                $arquivo['size']     = filesize("$origem_anexo/$anexo");
                $arquivo['name']     = basename($anexo);
                $arquivo['error']    = 0;
                $arquivo['tmp_name'] = "$origem_anexo/$anexo";
                $arquivo['type']     = $mimeTypes[strtolower(pathinfo($anexo, PATHINFO_EXTENSION))];

                //pre_echo($arquivo, "anexo para a OS $os");
                $anexou = anexaNF($os, $arquivo);
            }
        }
    }

    if (in_array($login_fabrica, array(50, 91, 120,201, 131))) {

		$data_fabricacao = $_POST['data_fabricacao'];

		if($login_fabrica == 131){
			$data_fabricacao = "01/".$data_fabricacao;
			// echo $data_fabricacao.'-->';exit;
		}

		if (strlen($data_fabricacao)>0) {
			list($di, $mi, $yi) = explode("/", $data_fabricacao);

			if (!checkdate($mi,$di,$yi)) {
				$msg_erro .= "Data de Fabricação Inválida<br />";
			}

			if (strlen($msg_erro) == 0) {
				$xdata_fabricacao = fnc_formata_data_pg(trim($data_fabricacao));
			}

			if (strlen($msg_erro) == 0)	{
				$sql = "SELECT os FROM tbl_os_extra where os = $os";
				$res = pg_query($con,$sql);

				if (!pg_num_rows($res) ) {
					$sql = "INSERT INTO tbl_os_extra(os,data_fabricacao) VALUES ($os, $xdata_fabricacao)";

					$res = pg_query($con,$sql);
				}
			}
		}
    }

    //VALIDA OS
    $res = pg_query($con, "SELECT fn_valida_os($os, $login_fabrica)");
    $msg_erro .= pg_last_error($con);
    $msg_alerta = pg_last_notice($con);

    if ($login_fabrica == 19) {

      if (!empty($_POST['garantia_lorenzetti'])) {

        $notaCompleta = str_pad($nota_fiscal , 7 , '0' , STR_PAD_LEFT);

        $sqlReincidenteGarantia = "SELECT tbl_os.os, tbl_os.garantia_produto
                                   FROM tbl_os
                                   JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                                   AND UPPER(tbl_produto.referencia) = UPPER({$produto_referencia})
                                   WHERE UPPER(tbl_os.nota_fiscal) = UPPER('{$notaCompleta}')
                                   AND tbl_os.data_nf = {$xdata_nf}
                                   AND tbl_os.consumidor_cpf = {$xconsumidor_cpf}
                                   AND tbl_os.fabrica = {$login_fabrica}
                                   AND tbl_os.tipo_atendimento = 339
                                   AND tbl_os.os != {$os}";
        $resReincidenteGarantia = pg_query($con, $sqlReincidenteGarantia);

        if (pg_num_rows($resReincidenteGarantia) > 0) {

          if ($tipo_atendimento != 339) {

            $garantia_lorenzetti = pg_fetch_result($resReincidenteGarantia, 0, 'garantia_produto');

            $sqlDesconsideraReincidencia = "UPDATE tbl_os
                                            SET os_reincidente = null
                                            WHERE os = {$os};

                                            UPDATE tbl_os_extra
                                            SET os_reincidente = NULL
                                            WHERE os = {$os};";
            pg_query($con, $sqlDesconsideraReincidencia);

          } else {

            $msg_erro .= "Produto já cadastrado! <br />";

          }

        } else if ($tipo_atendimento != 339) {

          $sqlBuscaGarantia = "SELECT tbl_produto.garantia
                               FROM tbl_os
                               JOIN tbl_produto USING(produto)
                               WHERE os = {$os}
                               AND tbl_os.fabrica = {$login_fabrica}
                              ";
          $resBuscaGarantia = pg_query($con, $sqlBuscaGarantia);

          $garantia_lorenzetti = pg_fetch_result($resBuscaGarantia, 0, 'garantia');

        }

        if (empty($_POST['produto_serie'])) {
          $msg_erro .= "Número de série obrigatório para este tipo de atendimento <br />";
        }

        if (empty($_POST['consumidor_email'])) {
          $msg_erro .= "E-mail do consumidor obrigatório para este tipo de atendimento <br />";
        }

        if (empty($_FILES['certificado_instalacao']["name"])) {

            $msg_erro .= "Favor, anexar o certificado de instalação <br />";

        } else {

          $retornoTdocs = $TdocsMirror->post($_FILES['certificado_instalacao']["tmp_name"]);

          foreach ($retornoTdocs[0] as $keyTdocs => $valTdocs) {

            $obs = json_encode(array(
                "acao"     => "anexar",
                "filename" => $_FILES['certificado_instalacao']["name"],
                "filesize" => $_FILES['certificado_instalacao']["size"],
                "data"     => date("Y-m-d\TH:i:s"),
                "fabrica"  => $login_fabrica,
                "page"     => "os_cadastro_tudo.php",
                "typeId"   => "cert_instalacao"
            ));


            $sql = "INSERT INTO tbl_tdocs (obs, tdocs_id, fabrica, situacao, referencia, referencia_id,contexto) VALUES ('[{$obs}]', '".$valTdocs['unique_id']."', {$login_fabrica}, 'Ativo','cert_instalacao',{$os},'cert_instalacao')";

            pg_query($con, $sql);

          }

        }

        if (empty($msg_erro)) {

          $data_fim_garantia   = date('Y-m-d', strtotime("+{$garantia_lorenzetti} months", strtotime(formata_data($_POST['data_nf']))));

          if (date('Y-m-d') > $data_fim_garantia) {
              $msg_erro .= "Este produto está fora da garantia de {$garantia_lorenzetti} meses";
          } else {
              $sqlGarantiaProduto = "UPDATE tbl_os
                                     SET garantia_produto = {$garantia_lorenzetti}
                                     WHERE os = {$os}
                                     AND fabrica = {$login_fabrica} ";
              pg_query($con, $sqlGarantiaProduto);
          }

        }

      } else {

        $notaCompleta = str_pad($nota_fiscal , 7 , '0' , STR_PAD_LEFT);

        $sqlReincidenteGarantia = "SELECT tbl_os.os, tbl_os.garantia_produto
                                   FROM tbl_os
                                   JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
                                   AND UPPER(tbl_produto.referencia) = UPPER({$produto_referencia})
                                   WHERE UPPER(tbl_os.nota_fiscal) = UPPER('{$notaCompleta}')
                                   AND tbl_os.data_nf = {$xdata_nf}
                                   AND tbl_os.consumidor_cpf = {$xconsumidor_cpf}
                                   AND tbl_os.fabrica = {$login_fabrica}
                                   AND tbl_os.tipo_atendimento = 339
                                   AND tbl_os.os != {$os}";

        $resReincidenteGarantia = pg_query($con, $sqlReincidenteGarantia);

        if (pg_num_rows($resReincidenteGarantia) > 0) {

          if ($tipo_atendimento != 339) {

            $garantiaPadrao = pg_fetch_result($resReincidenteGarantia, 0, 'garantia_produto');

            $sqlDesconsideraReincidencia = "UPDATE tbl_os
                                            SET os_reincidente = null
                                            WHERE os = {$os};

                                            UPDATE tbl_os_extra
                                            SET os_reincidente = null
                                            WHERE os = {$os};";
            pg_query($con, $sqlDesconsideraReincidencia);

          } else {

            $msg_erro .= "Produto já cadastrado!";

          }

        } else {

            $sqlBuscaGarantia = "SELECT tbl_produto.garantia
                                 FROM tbl_os
                                 JOIN tbl_produto USING(produto)
                                 WHERE os = {$os}
                                 AND tbl_os.fabrica = {$login_fabrica}
                                 ";
            $resBuscaGarantia = pg_query($con, $sqlBuscaGarantia);

            $garantiaPadrao = pg_fetch_result($resBuscaGarantia, 0, 'garantia');

        }

        $data_fim_garantia   = date('Y-m-d', strtotime("+{$garantiaPadrao} months", strtotime(formata_data($_POST['data_nf']))));

        $sqlForaGarantia = "SELECT tipo_atendimento
                            FROM tbl_tipo_atendimento
                            WHERE tipo_atendimento = {$tipo_atendimento}
                            AND fora_garantia IS TRUE";
        $resForaGarantia = pg_query($con, $sqlForaGarantia);

        if (pg_num_rows($resForaGarantia) == 0) {

          if (date('Y-m-d') > $data_fim_garantia) {

              $msg_erro .= "Este produto está fora da garantia de {$garantiaPadrao} meses";

          } else {

              $sqlGarantiaProduto = "UPDATE tbl_os
                                     SET garantia_produto = {$garantiaPadrao}
                                     WHERE os = {$os}
                                     AND fabrica = {$login_fabrica}";
              pg_query($con, $sqlGarantiaProduto);

          }
        }
      }

      $sqlVerificaReincidenciaGarantia = "SELECT tbl_os_extra.os_reincidente, r.tipo_atendimento
                                          FROM tbl_os_extra
                                          JOIN tbl_os r ON r.os = tbl_os_extra.os_reincidente
                                          AND r.fabrica = {$login_fabrica}
                                          WHERE tbl_os_extra.os = {$os}
                                          AND r.tipo_atendimento = 339";
      $resVerificaReincidenciaGarantia = pg_query($con, $sqlVerificaReincidenciaGarantia);

      if (pg_num_rows($resVerificaReincidenciaGarantia) > 0) {

         $sqlDesconsideraReincidencia = "UPDATE tbl_os
                                         SET os_reincidente = null
                                         WHERE os = {$os};

                                         UPDATE tbl_os_extra
                                         SET os_reincidente = null
                                         WHERE os = {$os};";
          pg_query($con, $sqlDesconsideraReincidencia);

      }

    }

    if($login_fabrica == 104){ //HD-2303024
      // POSTO AUDITADO
        $sql_posto_auditado = "SELECT parametros_adicionais
                          FROM tbl_posto_fabrica
                          WHERE fabrica = $login_fabrica
                          AND posto = $posto";
        $res_posto_auditado = pg_query($con,$sql_posto_auditado);

        $parametros_adicionais = pg_fetch_result($res_posto_auditado,0,parametros_adicionais);

        if(!empty($parametros_adicionais)){
          $adicionais = json_decode($parametros_adicionais,true);
          $posto_auditado = $adicionais['posto_auditado'];
        }

        if($posto_auditado == 't'){
          $sql_insert_auditado = "INSERT INTO tbl_auditoria_os (
                                  os,
                                  auditoria_status,
                                  observacao,
                                  bloqueio_pedido
                                ) VALUES (
                                    $os,
                                    6,
                                    'Auditoria Posto Auditado.',
                                    't'
                                )";
          $res_insert_auditado = pg_query($con, $sql_insert_auditado);
        }
      // FIM - POSTO AUDITADO

      // PRODUTO AUDITADO
        $sql_produto_auditado = "SELECT tbl_produto.produto_critico
                              FROM tbl_produto
                              WHERE produto = $produto
                              AND fabrica_i = $login_fabrica";
        $res_produto_auditado = pg_query($con, $sql_produto_auditado);

        $produto_auditado = pg_fetch_result($res_produto_auditado, 0, 'produto_critico');

        if($produto_auditado == 't'){
          $sql_insert_auditado = "INSERT INTO tbl_auditoria_os (
                                  os,
                                  auditoria_status,
                                  observacao,
                                  bloqueio_pedido
                                ) VALUES (
                                    $os,
                                    3,
                                    'Auditoria Produto Auditado.',
                                    't'
                                )";
          $res_insert_auditado = pg_query($con, $sql_insert_auditado);
        }
      // FIM PRODUTO AUDITADO

    }

    if($login_fabrica == 94){
      if($posto_revenda == false){
          $sql = "UPDATE tbl_os_extra SET extrato_geracao = CURRENT_DATE WHERE os = {$os}";
      }else{
          $sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$os}";
      }
      $res = pg_query($con, $sql);
    }

    if (in_array($login_fabrica,[120,201]) && $tipo_atendimento == 145) {
        if (str_replace("'", "", $consumidor_cidade) != str_replace("'", "", $contato_cidade)) {
            $sql = "UPDATE tbl_os_extra SET percurso_total = 'true' WHERE os = $os";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_last_error($con);
        }
    }

    if (in_array($login_fabrica,[120,201])) {
        $sql = "SELECT valor_km, parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
        $res = pg_query($con,$sql);

        $valor_km = pg_fetch_result($res,0,"valor_km");
        $parametros_adicionais_posto = json_decode(pg_fetch_result($res,0,"parametros_adicionais"),1);


        $km_apartir_de = $parametros_adicionais_posto['km_apartir'];
        $km_apartir = (strlen($km_apartir_de) == 0) ? 0 : $km_apartir_de;
		$km_apartir = str_replace(",",".",$km_apartir);

        if ($valor_km == 0 OR strlen($valor_km) == 0) {
            $sql = "SELECT valor_km FROM tbl_fabrica WHERE fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $valor_km = pg_fetch_result($res,0,"valor_km");
            if (strlen($valor_km) == 0) {
                $valor_km = 0;
            }
        }

      	$sqlUP = "UPDATE tbl_os SET
              			qtde_km_calculada = ((tbl_os.qtde_km - CASE WHEN tbl_os_extra.percurso_total IS TRUE THEN 0  WHEN  tbl_os_extra.percurso_total IS NOT TRUE AND $km_apartir > 0 THEN $km_apartir ELSE 20 END) * $valor_km),
      	         		qtde_km = ((tbl_os.qtde_km - CASE WHEN tbl_os_extra.percurso_total IS TRUE THEN 0 WHEN  tbl_os_extra.percurso_total IS NOT TRUE AND $km_apartir > 0 THEN $km_apartir ELSE 20 END) )
              		FROM tbl_os_extra
              		WHERE tbl_os.os = tbl_os_extra.os
              		AND tbl_os.os = $os;

		              UPDATE tbl_os set qtde_km = 0, qtde_km_calculada = 0 where os = $os and qtde_km < 0 ;";
        $resUP = pg_query($con, $sqlUP);
        $msg_erro .= pg_last_error($con);

    }

    //HD 893100
    //ATUALIZA tbl_os_extra.recomendacoes, SETANDO OS DADOS DA REVENDA ADICIONAL
    if (empty($msg_erro)){
        if ($login_fabrica == 50) {
            $sql = "SELECT count(*) from tbl_os_extra where os=$os";
            $res = pg_query($con,$sql);
            if (pg_fetch_result($res, 0, 0)>0) {
                $sql = "UPDATE tbl_os_extra
                        SET recomendacoes = $obs_atacadista where os=$os" ;
                $res = pg_query($con,$sql);
            }
        }

        if ($login_fabrica == 122) {
            $consumidor_cpd     = $_POST['consumidor_cpd'];
            $consumidor_contato = $_POST['consumidor_contato'];

            $obs_adicionais = "{\"consumidor_cpd\":\"$consumidor_cpd\",\"consumidor_contato\":\"$consumidor_contato\"}";
                $sql = "UPDATE tbl_os_extra
                        SET obs_adicionais = '$obs_adicionais' where os=$os" ;
                $res = pg_query($con,$sql);
        }

        //OS LORENZETTI, INSERE INTERVENÇÃO
        if ($login_fabrica == 19 and $nova_os == 1) {

                if (strpos($msg_alerta, "Não fazer reparo neste produto!") > 0) {

                    $sql_int = "INSERT INTO tbl_os_status (
                                    os,
                                    status_os,
                                    observacao,
                                    status_os_troca,
                                    fabrica_status
                                ) VALUES (
                                    $os,
                                    62,
                                    'Produto com mão de obra maior ou igual a 80% de seu preço.',
                                    false,
                                    19
                                )";

                $res = pg_query($con, $sql_int);

            }

        }

        if (($login_fabrica == 3 and $linhainf == 't') or $fabrica_com_preOS) {

            $sql        = "SELECT sua_os from tbl_os where os = $os and fabrica = $login_fabrica";
            $res        = @pg_query($con,$sql);
            $sua_os     = @pg_fetch_result($res,0,0);

            $hd_chamado      = (!empty($_POST['hd_chamado'])) ? (int)$_POST['hd_chamado'] : '' ;
            $hd_chamado_item = (!empty($_POST['hd_chamado_item'])) ? (int)$_POST['hd_chamado_item'] : '' ;

            if ($login_fabrica == 52  and strlen($hd_chamado) > 0 and strlen($hd_chamado_item) > 0) {

                $sqlHDChamado = "SELECT os, tbl_os.sua_os AS os_fabrica
                                   FROM tbl_hd_chamado_item
                              LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_item.os
                                  WHERE hd_chamado_item = $hd_chamado_item
                                    AND hd_chamado = $hd_chamado
                                    AND os IS NOT NULL
                                  LIMIT 1";
                $resHDChamado = pg_query($con, $sqlHDChamado);

                if (pg_num_rows($resHDChamado) > 0) {

                    $OSHdChamado = pg_fetch_result($resHDChamado, 0, 'os');
                    $osAnterior  = pg_fetch_result($resHDChamado, 0, 'os_fabrica');
                    $msg_erro    = "Já existe uma OS com esse chamado: <a href='os_press.php?os=".$OSHdChamado."' target='_blank'>" . $osAnterior . "</a>";

                }

            }

            if (strlen($os) > 0 and strlen($hd_chamado) > 0 and empty($msg_erro)) {

                if ($login_fabrica <> 52 and $login_fabrica <> 30 and $login_fabrica != 96) {
                    $sqlinf = "UPDATE tbl_hd_chamado_extra SET os = $os WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado;";
                } else {
                    $sqlinf = "UPDATE tbl_hd_chamado_item SET os = $os WHERE tbl_hd_chamado_item.hd_chamado_item = $hd_chamado_item;";
                }

                $resinf = @pg_query ($con,$sqlinf);

                if (strlen(pg_last_error($con)) > 0) {
                    $msg_erro = pg_last_error($con);
                    $msg_erro = substr($msg_erro,6);
                }

                if ($fabrica_preos_resolvido_automatico and empty($msg_erro) and !in_array($login_fabrica, array(15,90, 120,201))) {

                        $sql      = "UPDATE tbl_hd_chamado set status = 'Resolvido', resolvido = NOW() where hd_chamado = $hd_chamado and resolvido isnull";
                        $res      = pg_query($con,$sql);

                        $msg_erro = pg_last_error($con);
                        $msg_erro = substr($msg_erro,6);
                }

                if($login_fabrica == 74){
                  $select_admin = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica and login = 'sistema' and ativo is true LIMIT 1";
                }else{
                  $select_admin = "SELECT admin FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado LIMIT 1";
                }
                if( in_array($login_fabrica, array(11,172)) ){

                  $sqlinf = "INSERT INTO tbl_hd_chamado_item(
                                hd_chamado   ,
                                data         ,
                                comentario   ,
                                interno
                            ) values (
                                $hd_chamado       ,
                                current_timestamp ,
                                'Foi aberto pelo posto a OS deste chamado com o número $sua_os'       ,
                                't'
                            )";

                }else{

                  $sqlinf = "INSERT INTO tbl_hd_chamado_item(
                                  hd_chamado   ,
                                  data         ,
                                  comentario   ,
                                  interno      ,
                                  admin
                              ) values (
                                  $hd_chamado       ,
                                  current_timestamp ,
                                  'Foi aberto pelo posto a OS deste chamado com o número $sua_os'       ,
                                  't',
                                  ($select_admin)
                              )";
                }
                $resinf    = pg_query($con,$sqlinf);
                $msg_erro .= pg_last_error($con);
            }
        }
    }


        #--------- grava OS_EXTRA ------------------
        if (strlen($msg_erro) == 0) {

          //Master Frio ainda não tem valida_os.
          if ($login_fabrica == 40 OR $login_fabrica == 46 OR ($login_fabrica == 5 AND (strlen($sua_os) == 0 OR $sua_os == 'null'))) {

              $sql = "UPDATE tbl_os SET sua_os = $os WHERE os = $os and fabrica = $login_fabrica; ";
              $res = pg_query($con, $sql);

          }

          //===============================REVEND*****AA
          //revenda_cnpj
          if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 AND $xrevenda_cidade <> 'null' and strlen ($xrevenda_estado) > 0 AND $xrevenda_estado<>'null' && $login_pais == 'BR') {

              	$sql        = "SELECT fnc_qual_cidade($xrevenda_cidade, $xrevenda_estado)";
              	$res        = pg_query($con, $sql);
              	$monta_sql .= "9: $sql<br />$msg_erro<br /><br />";

              	$msg_erro .= pg_last_error($con);

              if(strlen($msg_erro) == 0){

                $xrevenda_cidade = pg_fetch_result($res, 0, 0);

                $sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
                $res1 = pg_query ($con,$sql);

                $monta_sql .= "10: $sql<br />$msg_erro<br /><br />";

                if (pg_num_rows($res1) > 0) {

                    $revenda = pg_fetch_result ($res1, 0, 'revenda');

                    $sql = "UPDATE tbl_revenda SET
                                nome        = $xrevenda_nome          ,
                                cnpj        = $xrevenda_cnpj          ,
                                fone        = $xrevenda_fone          ,
                                endereco    = $xrevenda_endereco      ,
                                numero      = $xrevenda_numero        ,
                                complemento = $xrevenda_complemento   ,
                                bairro      = $xrevenda_bairro        ,
                                cep         = $xrevenda_cep           ,
                                cidade      = $xrevenda_cidade
                            WHERE tbl_revenda.revenda = $revenda";

                    $res3       = @pg_query ($con,$sql);
                    $msg_erro  .= pg_last_error ($con);
                    $monta_sql .= "11: $sql<br />$msg_erro<br /><br />";

                } else {

                    $sql = "INSERT INTO tbl_revenda (
                                nome,
                                cnpj,
                                fone,
                                endereco,
                                numero,
                                complemento,
                                bairro,
                                cep,
                                cidade
                            ) VALUES (
                                $xrevenda_nome ,
                                $xrevenda_cnpj ,
                                $xrevenda_fone ,
                                $xrevenda_endereco ,
                                $xrevenda_numero ,
                                $xrevenda_complemento ,
                                $xrevenda_bairro ,
                                $xrevenda_cep ,
                                $xrevenda_cidade
                            )";

                    $res3       = @pg_query ($con,$sql);
                    $msg_erro  .= pg_last_error ($con);
                    $monta_sql .= "12: $sql<br />$msg_erro<br /><br />";

                    $sql     = "SELECT currval ('seq_revenda')";
                    $res3    = @pg_query ($con,$sql);
                    $revenda = @pg_fetch_result ($res3, 0, 0);

                }

                if($login_fabrica == 114 && strlen($os) > 0){
                  if($tem_selo == true){
                    $sql = "UPDATE tbl_os_extra SET selo = 'selo anexado' WHERE os = {$os}";
                    $res = pg_query($con, $sql);
                  }
                }

                $sql = "UPDATE tbl_os SET revenda = $revenda WHERE os = $os AND fabrica = $login_fabrica";
                $res = @pg_query ($con,$sql);
                $monta_sql .= "13: $sql<br />$msg_erro<br /><br />";

                if ($usa_revenda_fabrica) {//HD 234135

                    if ($revenda_fabrica_status == "nao_cadastrado") {

                        $sql = "INSERT INTO
                                    tbl_revenda_fabrica (
                                    fabrica,
                                    contato_razao_social,
                                    cnpj,
                                    contato_fone,
                                    contato_cep,
                                    contato_endereco,
                                    contato_numero,
                                    contato_complemento,
                                    contato_bairro,
                                    cidade,
                                    revenda
                                ) VALUES (
                                    $login_fabrica,
                                    $xrevenda_nome,
                                    $xrevenda_cnpj,
                                    $xrevenda_fone,
                                    $xrevenda_cep,
                                    $xrevenda_endereco,
                                    $xrevenda_numero,
                                    $xrevenda_complemento,
                                    $xrevenda_bairro,
                                    $xrevenda_cidade,
                                    $revenda
                                )";

                        $res = pg_query($con, $sql);
                        if (pg_last_error($con)) {
                            $msg_erro .= "Falha ao cadastrar a revenda <br>";
                        }

                    } else if ($revenda_fabrica_status == "cadastrado") {

                        $sql = "UPDATE tbl_revenda_fabrica SET
                                contato_fone = $xrevenda_fone,
                                contato_cep = $xrevenda_cep,
                                contato_endereco = $xrevenda_endereco,
                                contato_numero = $xrevenda_numero,
                                contato_complemento = $xrevenda_complemento,
                                contato_bairro = $xrevenda_bairro,
                                cidade = $xrevenda_cidade
                        WHERE fabrica = $login_fabrica
                        AND cnpj = $xrevenda_cnpj";

                        $res = pg_query($con, $sql);

                        if (pg_last_error($con)) {
                            $msg_erro .= "Falha ao cadastrar a revenda <br>";
                        }

                    }

                  }

              }

          }

          //REVENDA

          $taxa_visita                = str_replace (",",".",trim ($_POST['taxa_visita']));
          $visita_por_km              = trim($_POST['visita_por_km']);
          $valor_por_km               = str_replace (",",".",trim ($_POST['valor_por_km']));
          $veiculo                    = trim ($_POST['veiculo']);
          $deslocamento_km            = str_replace (",",".",trim ($_POST['deslocamento_km']));

          $hora_tecnica               = str_replace (",",".",trim ($_POST['hora_tecnica']));

          $regulagem_peso_padrao      = str_replace (".","",trim ($_POST['regulagem_peso_padrao']));
          $regulagem_peso_padrao      = str_replace (",",".",$regulagem_peso_padrao);

          $certificado_conformidade   = str_replace (".","",trim ($_POST['certificado_conformidade']));
          $certificado_conformidade   = str_replace (",",".",$certificado_conformidade);

          $valor_diaria               = str_replace (".","",trim ($_POST['valor_diaria']));
          $valor_diaria               = str_replace (",",".",$valor_diaria);

          $cobrar_deslocamento        = trim ($_POST['cobrar_deslocamento']);
          $cobrar_hora_diaria         = trim ($_POST['cobrar_hora_diaria']);

          $desconto_deslocamento      = str_replace (",",".",trim ($_POST['desconto_deslocamento']));
          $desconto_hora_tecnica      = str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
          $desconto_diaria            = str_replace (",",".",trim ($_POST['desconto_diaria']));
          $desconto_regulagem         = str_replace (",",".",trim ($_POST['desconto_regulagem']));
          $desconto_certificado       = str_replace (",",".",trim ($_POST['desconto_certificado']));

          $cobrar_regulagem           = trim ($_POST['cobrar_regulagem']);
          $cobrar_certificado         = trim ($_POST['cobrar_certificado']);

          if ($login_tipo_posto == 215) {

              if ($desconto_deslocamento > 7) {
                  $msg_erro .= "O desconto máximo permitido para deslocamento é 7%.<br />";
              }

              if ($desconto_hora_tecnica > 7) {
                  $msg_erro .= "O desconto máximo permitido para hora técnica é 7%.<br />";
              }

              if ($desconto_diaria > 7) {
                  $msg_erro .= "O desconto máximo permitido para diára é 7%.<br />";
              }

              if ($desconto_regulagem > 7) {
                  $msg_erro .= "O desconto máximo permitido para regulagem é 7%.<br />";
              }

              if ($desconto_certificado > 7) {
                  $msg_erro .= "O desconto máximo permitido para o certificado é 7%.<br />";
              }

          }

          if (strlen($veiculo) == 0) {
              $xveiculo = "NULL";
          } else {

              $xveiculo = "'$veiculo'";

              if ($veiculo == 'carro') {
                  $valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_carro']));
              }

              if ($veiculo == 'caminhao') {
                  $valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_caminhao']));
              }

          }

          if (strlen($valor_por_km) > 0) {
              $xvalor_por_km  = $valor_por_km;
              $xvisita_por_km = "'t'";
          } else {
              $xvalor_por_km  = "0";
              $xvisita_por_km = "'f'";
          }

          if (strlen($taxa_visita) > 0) {
              $xtaxa_visita = $taxa_visita;
          } else {
              $xtaxa_visita = '0';
          }

          if (strlen($deslocamento_km) > 0) {
              $deslocamento_km = $deslocamento_km;
          } else {
              $deslocamento_km = '0';
          }
        if($login_fabrica == 30){
            if($qtde_km2 != 0){
                $deslocamento_km = $qtde_km2;
            } else {
                $deslocamento_km = '0';
            }
        }

          /* HD 29838 */
          if ($tipo_atendimento == 63) {
              $cobrar_deslocamento = 'isento';
          }

          if ($cobrar_deslocamento == 'isento') {

              $xvisita_por_km = "'f'";
              $xvalor_por_km  = "0";
              $xtaxa_visita   = '0';
              $xveiculo       = "NULL";

          } else if ($cobrar_deslocamento == 'valor_por_km') {

              $xvisita_por_km = "'t'";
              $xtaxa_visita   = '0';

          } else if ($cobrar_deslocamento == 'taxa_visita') {

              $xvisita_por_km = "'f'";
              $xvalor_por_km  = "0";

          }

          if (strlen($valor_diaria) > 0) {
              $xvalor_diaria = $valor_diaria;
          } else {
              $xvalor_diaria = '0';
          }

          if (strlen($hora_tecnica) > 0) {
              $xhora_tecnica = $hora_tecnica;
          } else {
              $xhora_tecnica = '0';
          }

          if ($cobrar_hora_diaria == 'isento') {
              $xhora_tecnica = '0';
              $xvalor_diaria = '0';
          } else if ($cobrar_hora_diaria == 'diaria') {
              $xhora_tecnica = '0';
          } else if ($cobrar_hora_diaria == 'hora') {
              $xvalor_diaria = '0';
          }

          if (strlen($regulagem_peso_padrao) > 0 and $cobrar_regulagem == 't') {
              $xregulagem_peso_padrao = $regulagem_peso_padrao;
          } else {
              $xregulagem_peso_padrao = '0';
          }

          if (strlen($certificado_conformidade) > 0 and $cobrar_certificado == 't') {
              $xcertificado_conformidade = $certificado_conformidade;
          } else {
              $xcertificado_conformidade = "0";
          }

          /* Descontos */
          if (strlen($desconto_deslocamento) > 0) {
              $desconto_deslocamento = $desconto_deslocamento;
          } else {
              $desconto_deslocamento = '0';
          }

          if (strlen($desconto_hora_tecnica) > 0) {
              $desconto_hora_tecnica = $desconto_hora_tecnica;
          } else {
              $desconto_hora_tecnica = '0';
          }

          if (strlen($desconto_diaria) > 0) {
              $desconto_diaria = $desconto_diaria;
          } else {
              $desconto_diaria = '0';
          }

          if (strlen($desconto_regulagem) > 0) {
              $desconto_regulagem = $desconto_regulagem;
          } else {
              $desconto_regulagem = '0';
          }

          if (strlen($desconto_certificado) > 0) {
              $desconto_certificado = $desconto_certificado;
          } else {
              $desconto_certificado = '0';
          }

          if (in_array($login_fabrica, array(50, 91, 96, 131))) {

              $data_fabricacao = $_POST['data_fabricacao'];
            		if($login_fabrica == 131){
            			$data_fabricacao = "01/".$data_fabricacao;
                }

              if (strlen($data_fabricacao)>0) {
                  $xdata_fabricacao = fnc_formata_data_pg(trim($data_fabricacao));
              } else {
                  $xdata_fabricacao = 'null';
                  $msg_erro = "Favor Digite a data de Fabricação";
              }

          } else {

              if (!in_array($login_fabrica, [120,201])) {
                $xdata_fabricacao = 'null';
              }

              if (in_array($login_fabrica,[120,201]) && !strlen($data_fabricacao)) {
                  $xdata_fabricacao = "null";
              }
          }
          if($login_fabrica ==35){
              $campos_extra = " obs_adicionais = '$informaemail', ";
          }

          $sql = "UPDATE tbl_os_extra SET
                      taxa_visita              = $xtaxa_visita             ,
                      visita_por_km            = $xvisita_por_km           ,
                      valor_por_km             = $xvalor_por_km            ,
                      hora_tecnica             = $xhora_tecnica            ,
                      regulagem_peso_padrao    = $xregulagem_peso_padrao   ,
                      certificado_conformidade = $xcertificado_conformidade,
                      valor_diaria             = $xvalor_diaria            ,
                      veiculo                  = $xveiculo                 ,
                      deslocamento_km          = $deslocamento_km          ,
                      desconto_deslocamento    = $desconto_deslocamento    ,
                      desconto_hora_tecnica    = $desconto_hora_tecnica    ,
                      desconto_diaria          = $desconto_diaria          ,
                      desconto_regulagem       = $desconto_regulagem       ,
                      desconto_certificado     = $desconto_certificado     ,
                      desconto_peca            = $xdesconto_peca           ,
                      coa_microsoft            = '$coa_microsoft'          ,
                      data_fabricacao          = $xdata_fabricacao         ,
                      $campos_extra
                      "
                      .
                      ($login_fabrica == 15 ? "admin_paga_mao_de_obra  = 't' ," : "")
                      .
                      "
                      classificacao_os         = $classificacao_os ";
          if ($login_fabrica == 52){
                      $ponto_referencia = (isset($_POST['ponto_referencia'])) ? trim($_POST['ponto_referencia']) : '' ;
                      $sql .= ",obs = '$ponto_referencia' ";

          }

          if ($os_reincidente == "'t'") $sql .= ", os_reincidente = $xxxos ";

          $sql .= "WHERE tbl_os_extra.os = $os";

        if(strlen($msg_erro) == 0){
            $res = @pg_query ($con,$sql);
            $msg_erro .= pg_last_error($con);
        }

		//HD 682454 removido desse arquivo, validacao colocada na valida_os da wanke. HD 805857

		if($login_fabrica == 117 or $login_fabrica == 128){
			if($garantia_estendida){
				if ($opcao_garantia_estendida == 't' and is_array($_FILES['nf_garantia_estendida']) and $_FILES['nf_garantia_estendida']['name'] != '') {
					$arquivo          = isset($_FILES["nf_garantia_estendida"]) ? $_FILES["nf_garantia_estendida"] : FALSE;
					if(!$s3_ge->uploadFileS3($os, $arquivo)){
						$msg_erro .= "O arquivo de garantia estendida não foi enviado!!! " . $s3_ge->_erro; // . $erroS3;
					}
				}else if( $opcao_garantia_estendida == 't' and $_FILES['nf_garantia_estendida']['name'] == '' ){
					$msg_erro .= "Anexar arquivo de garantia estendida";
				}
			}
		}


		if ($anexaNotaFiscal) {
			if (is_array($_FILES['foto_nf']) and $_FILES['foto_nf']['name'] != '') {

				// HD 2544375
				// 2016-10-14 - MLG Retirado FTP do cadastro de OS:
				// Algumas transações demoram minutos. Mesmo se o retirarmos da
				// transação do banco, a demora não é admissível.
				  /*
				  if ($login_fabrica == 3 and $os) {
					  $ftp_src_file = $_FILES['foto_nf'];
					  $sua_os = pg_fetch_result(
						  pg_query(
							  $con,
							  "SELECT sua_os FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os"
						  ), 0, 0
					  );
					  if ($sua_os) {
						  include_once ('class/ftp.class.php');
						  // para teste local
						  $ftpURL = 'pftp://aroldo:tele6588@ftp.telecontrol.com.br/britania';
						  $ftpURL = "pftp://akacia:britania2009@telecontrol.britania.com.br/Entrada/Imagens";

						  $ftp = new Ftp($ftpURL);
						  if ($ftp->loggedIn)
							  $ftpOK = $ftp->put($ftp_src_file['tmp_name'], "$sua_os.".pathinfo($ftp_src_file['name'], PATHINFO_EXTENSION));

						  if (strlen($ftp->error) == 0 ) {
							  pg_query($con, "UPDATE tbl_os_extra SET baixada = CURRENT_TIMESTAMP WHERE os = $os");
						  }
						  $ftp->close();
					  }

				  }
				   */

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
            $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;
          }

          $qt_anexo++;
        }
				if ($login_fabrica == 6 && $anexou === 0) {
					$sql = "INSERT INTO tbl_os_status (os, status_os, observacao) VALUES ($os, 189, 'OS em auditoria de nota fiscal')";
					$res = pg_query($con, $sql);
				}
			}
		}

		$filesByImageUploader = 0;
		if ($fabricas_image_uploader) {

			$objectId = $_POST['objectid'];
			$sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs FROM tbl_tdocs WHERE referencia_id = 0 AND referencia = '$objectId' AND contexto = 'os'";
			$resDocs = pg_query($con,$sqlDocs);
			$resDocs = pg_fetch_all($resDocs);
			if(count($resDocs)>0 && $resDocs != false){
				foreach ($resDocs as $key => $value) {
					$sqlUpate = "UPDATE tbl_tdocs set fabrica = $login_fabrica, referencia = 'os', referencia_id = $os WHERE tdocs = ".$value['tdocs'];
					$res = pg_query($con, $sqlUpate);
					if(pg_last_error($con)){
						$msg_erro .= "<br>".pg_last_error($con);
					}
					$filesByImageUploader += 1;
				}
			}
		}

		if ($login_fabrica == 35 and !empty($arr_tdocs)) {
			$sqlUpdate = "UPDATE tbl_tdocs set fabrica = $login_fabrica, referencia = 'os', referencia_id = $os WHERE tdocs = ".$arr_tdocs[0];
			$res = pg_query($con, $sqlUpdate);
			if(pg_last_error($con)){
				$msg_erro .= "<br>".pg_last_error($con);
			}
		}

		// HD 350051 - Obrigatoriedade para as que exigem imagem da NF.
		if(!in_array($login_fabrica,array(15,42))){
			if ($anexaNotaFiscal and !temNF($os, 'bool') and !$msg_erro and $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] == true and $fabricas_image_uploader and $filesByImageUploader == 0) {
				if ($login_fabrica == 114){

					$sql = "SELECT linha FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto ";
					$res = pg_query($con,$sql);
					$linha_temp = pg_fetch_result($res, 0, 'linha');
					if ($linha_temp == '691'){
						$msg_erro .= "Não pode ser gravada a OS sem que haja uma imagem da Nota Fiscal.";
					}
				}else{
					if ($fabricas_image_uploader and $filesByImageUploader == 0) {
						$msg_erro .= traduz("Não pode ser gravada a OS sem que haja uma imagem da Nota Fiscal.");
					}
				}
			}
        }
		// FIM Anexa imagem NF

          $entra_intervencao_famastil = 'f';

          if (in_array($login_fabrica,array(86))) {//HD 416877 - INICIO -  Reincidencia famastil

              $sql = "SELECT tbl_os_status.status_os
                          FROM tbl_os_status
                          JOIN tbl_os using(os)
                          WHERE tbl_os.fabrica = $login_fabrica
                          AND tbl_os.os = $os
                          ORDER BY data DESC LIMIT 1;";
              $res = pg_query($con,$sql);

              if (pg_num_rows($res) > 0) {
                  $ultimo_status_interv = pg_fetch_result($res,0,'status_os');
              } else {
                  $ultimo_status_interv = "";
              }

              if ( $ultimo_status_interv <> 62 || empty($ultimo_status_interv)) {
                  $entra_intervencao_famastil = 't';
              }


              if ($entra_intervencao_famastil == 't') {

                  if($login_fabrica == 114){ //hd_chamado=2634503
                    $id_status = 20;
                  }else{
                    $id_status = 62;
                  }

                  $sql = "INSERT INTO tbl_os_status (
                              os,
                              status_os,
                              data,
                              observacao
                          ) values (
                              $os,
                              $id_status,
                              current_timestamp,
                              'OS com intervenção técnica'
                          )";

                  $res = pg_query ($con, $sql);

                  if (strlen(pg_last_error($con)) > 0) {
                      $msg_erro = pg_last_error($con);
                  }

              }

          } //HD 416877 - FIM

          if (in_array($login_fabrica, [114])) {

            \Posvenda\Helpers\Auditoria::gravar($os, 6, "OS com intervenção técnica", "Em auditoria", $con);

          }

          if($login_fabrica == 140 AND strlen ($msg_erro) == 0){

            if(!empty($consumidor_email)){

              $ip_devel = $_SERVER['REMOTE_ADDR'];
              $consumidor_email = ($ip_devel == "179.233.213.77") ? "guilherme.silva@telecontrol.com.br" : $consumidor_email;

              $email_consumidor->adicionaLog(array("titulo" => "Abertura de OS Lavor - {$os}"));

              $sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
              $res_produto = pg_query($con, $sql_produto);

              $referencia   = pg_fetch_result($res_produto, 0, 'referencia');
              $desc_produto = pg_fetch_result($res_produto, 0, 'descricao');

              $mensagem_email = "
                Foi aberta a Ordem de Serviço nº {$os}
                <br /> <br />
                Data: ".date("d/m/Y")." Produto: {$referencia} - {$desc_produto}
                <br /> <br />
                Serviço Lavor de Atendimento
              ";

              $email_consumidor->adicionaLog($mensagem_email);

              $email_consumidor->adicionaTituloEmail("Abertura de OS Lavor - {$os}");
              $email_consumidor->adicionaEmail($consumidor_email);
              $email_consumidor->enviaEmails();

            }

          }

          if (in_array($login_fabrica, $usam_valida_serie_bloqueada)) {
            include 'valida_serie_bloqueada.php';
          }

          // if($login_fabrica == 117 AND $nova_os == 1 AND strlen ($msg_erro) == 0){

          //   if(!empty($consumidor_email)){

          //     $ip_devel = $_SERVER['REMOTE_ADDR'];
          //     $consumidor_email = ($ip_devel == "179.233.213.77") ? "thiago.tobias@telecontrol.com.br" : $consumidor_email;

          //     $email_consumidor->adicionaLog(array("titulo" => "Abertura de OS Elgin - {$os}"));

          //     $sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
          //     $res_produto = pg_query($con, $sql_produto);

          //     $referencia   = pg_fetch_result($res_produto, 0, 'referencia');
          //     $desc_produto = pg_fetch_result($res_produto, 0, 'descricao');

          //     $mensagem_email = "
          //       Foi aberta a Ordem de Serviço nº {$os}
          //       <br /> <br />
          //       Data: ".date("d/m/Y")." Produto: {$referencia} - {$desc_produto}
          //       <br />
          //       Segue abaixo o link para consultar o andamento de sua O.S..
          //       <br />
          //       http://posvenda.telecontrol.com.br/assist/externos/institucional/statusos.html
          //       <br /> <br />
          //       Serviço Elgin de Atendimento
          //     ";

          //     $email_consumidor->adicionaLog($mensagem_email);

          //     $email_consumidor->adicionaTituloEmail("Abertura de OS Elgin - {$os}");
          //     $email_consumidor->adicionaEmail($consumidor_email);
          //     $email_consumidor->enviaEmails();
          //   }
          // }
          if($login_fabrica == 50){ //HD-3321672
            if(strlen(trim($xproduto_serie)) > 0){
              $sql_serie = "SELECT serie
                            FROM tbl_numero_serie
                            WHERE serie = $xproduto_serie
                            AND produto = $produto
                            AND fabrica = $login_fabrica";
              $res_serie = pg_query($con, $sql_serie);
              $msg_erro .= pg_last_error($con);
              if(pg_num_rows($res_serie) == 0){
                $sql_update = "UPDATE tbl_os set serie_reoperado = $xproduto_serie WHERE os = $os AND fabrica = $login_fabrica";
                $res_update = pg_query($con, $sql_update);
                $msg_erro .= pg_last_error($con);
              }else{
                $sql_update = "UPDATE tbl_os set serie_reoperado = null WHERE os = $os AND fabrica = $login_fabrica";
                $res_update = pg_query($con, $sql_update);
                $msg_erro .= pg_last_error($con);
              }
            }
          }

          if (strlen ($msg_erro) == 0) {
            if ($login_fabrica == 3) {
		    if (!ativa_produto($produto, $os, $produto_serie, $posto)) {

                $res = @pg_query ($con,"ROLLBACK TRANSACTION");
              }
            }
                $res = @pg_query ($con,"COMMIT TRANSACTION");

              //transferir os anexos do atendimento para a ordem de serviço
              if (in_array($login_fabrica, [144])) {

                    if ($_POST['pre_os'] == 't') {

                      $sqlInsertTdocs = "INSERT INTO tbl_tdocs (
                                                        tdocs_id,
                                                        fabrica,
                                                        contexto,
                                                        situacao,
                                                        obs,
                                                        referencia,
                                                        referencia_id
                                                     ) SELECT tdocs_id,
                                                              fabrica,
                                                              'os' as contexto,
                                                              situacao,
                                                              obs,
                                                              'os' as referencia,
                                                              '{$os}' as referencia_id
                                                        FROM tbl_tdocs
                                                        WHERE tbl_tdocs.referencia  = 'callcenter'
                                                        AND tbl_tdocs.referencia_id = ".$_POST['hd_chamado']."
                                                        AND tbl_tdocs.fabrica = {$login_fabrica}";
                      $resInsertTdocs = pg_query($con, $sqlInsertTdocs);

                    }

              }

              if (in_array($login_fabrica, [104,123]) && true === $is_insert) {
                $helper = new \Posvenda\Helpers\Os();

                $sql_posto = "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
                $qry_posto = pg_query($con, $sql_posto);
                $nome_posto = pg_fetch_result($qry_posto, 0, 'nome');

		if  ($login_fabrica == 104) {
		//Mensagem alteradas devido ao COVID-19, futuramente voltará a ser reenviada
                  #$msg_abertura_os = "Produto Vonder. Informamos que foi aberto a OS $os para seu produto " . str_replace("'", "", $produto_referencia) . " - $produto_descricao pelo posto autorizado $nome_posto. Caso a data de abertura esteja divergente com a data em que o produto foi deixado no AT, favor entrar em contato: 0800 723 4762 (OPÇÃO 1).";

		  $msg_abertura_os = "Em razão das medidas de prevenção e combate à transmissão do COVID-19 determinadas pelo Poder Público, com restrição de funcionamente de estabelecimentos comerciais, dentre eles, Assistências Técnicas e Correios, informamos que sua OS poderá não ser atendida no prazo de 30 dias. Para mais informações ligue gratuitamente para a ASCON - 0800 723 4762 OPÇÃO 1";

                  if (!empty($consumidor_email)) {
                      $helper->comunicaConsumidor($consumidor_email, $msg_abertura_os);
                  }
                
                } else {
                  $consumidor_celular = $_POST['consumidor_celular'];

                  $consumidor_nome = trim($consumidor_nome);
                  $primeiro_nome = explode(" ", $consumidor_nome);

                  $msg_abertura_os = "Olá $primeiro_nome[0] ! Ordem de Serviço $os registrada para seu produto " . str_replace("'", "", $produto_referencia) . "\n Equipe Positec ( WESCO / WORX ).";
                }

                if (!empty($consumidor_celular)) {
                    $helper->comunicaConsumidor($consumidor_celular, $msg_abertura_os, $fabrica, $os);
                }
              }

              //Envia e-mail para o consumidor, avisando da abertura da OS
              if ($login_fabrica == 14 || $login_fabrica == 43 || $login_fabrica == 66 || $login_fabrica == 117) {//HD 150972
                  $novo_status_os = "ABERTA";
                  include('os_email_consumidor.php');
              }

              if (strlen($_SESSION['fabrica']) > 0) {

                  $sql = "insert into tbl_os_log (
                              sua_os,
                              fabrica,
                              produto,
                              posto,
                              nota_fiscal,
                              data_nf,
                              data_abertura,
                              digitacao,
                              numero_serie,
                              cnpj_revenda,
                              nome_revenda,
                              os_atual
                          ) values (
                              $sua_os                                                        ,
                              $login_fabrica                                                 ,
                              $produto                                                       ,
                              $posto                                                         ,
                              $xnota_fiscal                                                  ,
                              $xdata_nf                                                      ,
                              '$xdata_abertura'                                              ,
                              current_timestamp                                              ,
                              $xproduto_serie                                                ,
                              $xrevenda_cnpj                                                 ,
                              $xrevenda_nome                                                 ,
                              $os
                          );";

                  $res = @pg_query($con, $sql);

              }

              if ($login_fabrica == 3 and $pedir_sua_os == 'f') {//HD 3371 e 12881

                  $sua_os_repetiu = 't';

                  while ($sua_os_repetiu == 't') {

                      $sql_sua_os = " SELECT sua_os
                                      FROM   tbl_os
                                      WHERE  fabrica =  $login_fabrica
                                      AND    posto   =  $login_posto
                                      AND    sua_os  =  (SELECT sua_os from tbl_os where os = $os)
                                      AND    os      <> $os";

                      $res_sua_os = pg_query($con, $sql_sua_os);

                      if (pg_num_rows($res_sua_os) > 0) {

                          //HD 52457 - Da um sleep se a OS for repetida. Entra neste caso somente quando duas OS estao sendo gravadas no mesmo momento.
                          //Entao da um tempo para outra OS passar no processo sem duplicadas a numeracao
                          //Pausa de 1 à 15 segundos. Aleatório. Acho tempo suficiente para ouro processo executar sem repetir
                          $num = mt_rand(1,15);
                          sleep($num);

                          $sql_sua_os = "UPDATE tbl_posto_fabrica SET sua_os = (sua_os + 1) where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $login_posto";
                          $res_sua_os = pg_query($con, $sql_sua_os);

                          $sql_sua_os   = " SELECT sua_os FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
                          $res_sua_os   = pg_query($con, $sql_sua_os);
                          $sua_os_atual = pg_fetch_result($res_sua_os,0,0);

                          if ($login_fabrica == 1) {

                              $sql_sua_os = "UPDATE tbl_os set sua_os = lpad('$sua_os_atual',6,'0') WHERE tbl_os.os = $os and tbl_os.fabrica = $login_fabrica";
                          }

                          #HD 12881
                          #HD 52457 - Corrigi o UPDATE abaixo
                          if ($login_fabrica == 3) {

                              $sql_sua_os = " UPDATE tbl_os SET
                                                  sua_os    =  lpad(tbl_posto_fabrica.codigo_posto,6,'0') || lpad ('$sua_os_atual',6,'0'),
                                                  os_numero =  (lpad(tbl_posto_fabrica.codigo_posto,6,'0') || lpad ('$sua_os_atual',6,'0'))::float
                                              FROM   tbl_posto_fabrica
                                              WHERE  tbl_os.os      = $os
                                              and    tbl_os.fabrica = $login_fabrica
                                              and    tbl_posto_fabrica.posto = tbl_os.posto
                                              and    tbl_posto_fabrica.fabrica = $login_fabrica";

                          }

                          $res_sua_os = pg_query($con, $sql_sua_os);

                      } else {
                          $sua_os_repetiu = 'f';
                      }

                  }

              }


              /*  HD 7998 - TAKASHI 12/12/2007 - VERIFICA SE EXISTE ALGUM CHAMADO DE CALLCENTER PARA ESSE PRODUTO,
                  SE TIVER REABRE O CHAMADO INSERE UM CHAMADO ITEM, MANDA EMAIL PARA O SUPERVISOR E PARA QUEM ABRIU O CHAMADO
                  HD 10359 - takashi 21/12/07 agora qdo o chamado estiver fechado, abre um chamado novo, esta atrapalhando no desempenho*/
             if ($login_fabrica == 6) {


                  $sql = "SELECT tbl_hd_chamado_extra.hd_chamado   ,
                                  tbl_admin.admin                  ,
                                  tbl_admin.nome_completo          ,
                                  tbl_hd_chamado.status            ,
                                  tbl_hd_chamado.categoria         ,
                                  tbl_hd_chamado_extra.produto     ,
                                  tbl_hd_chamado_extra.serie       ,
                                  tbl_admin.email
                          FROM tbl_hd_chamado_extra
                          JOIN tbl_hd_chamado on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                          JOIN tbl_admin on tbl_hd_chamado.atendente = tbl_admin.admin
                          WHERE tbl_hd_chamado_extra.produto = $produto
                          AND tbl_hd_chamado_extra.serie     = $xproduto_serie
                          AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
                          and tbl_hd_chamado.status <> 'Cancelado'
                          ORDER BY tbl_hd_chamado.data DESC";

                  $res = pg_query($con,$sql);

                  if (pg_num_rows($res) > 0) {

                      $hd_chamado           = pg_fetch_result($res, 0, 'hd_chamado');
                      $atendente            = pg_fetch_result($res, 0, 'admin');
                      $atendente_nome       = pg_fetch_result($res, 0, 'nome_completo');
                      $atendente_email      = pg_fetch_result($res, 0, 'email');
                      $hd_chamado_status    = pg_fetch_result($res, 0, 'status');
                      $hd_chamado_categoria = pg_fetch_result($res, 0, 'categoria');
                      $hd_chamado_produto   = pg_fetch_result($res, 0, 'produto');
                      $hd_chamado_serie     = pg_fetch_result($res, 0, 'serie');

                      # HD 46952
                      $sqlQato = "SELECT count (hd_chamado) AS chamados FROM tbl_hd_chamado WHERE atendente = 1631 AND status = 'Aberto'";
                      $resQato = pg_query($con,$sqlQato);

                      if (pg_num_rows($resQato)>0){
                          $chamados_aatendente = pg_fetch_result($resQato,0,chamados);
                      }

                      $sqlQbto = "SELECT count (hd_chamado) AS chamados FROM tbl_hd_chamado WHERE atendente = 1348 AND status = 'Aberto'";
                      $resQbto = pg_query($con,$sqlQbto);

                      if (pg_num_rows($resQbto) > 0) {
                          $chamados_batendente = pg_fetch_result($resQbto,0,chamados);
                      }

                      if ($chamados_aatendente <= $chamados_batendente) {
                          # HD 46952
                          #   Encaminhar os callcenter para a renatabrito ou para a queiroz
                          #   Verificar quem tem menos chamados e encaminha para esta
                          #   Se as duas tiverem com o mesmo número de chamados encaminha para a renatabrito
                          $sql = "SELECT admin, nome_completo, email FROM tbl_admin WHERE admin = 1631";
                          $res = pg_query($con,$sql);

                          if (pg_num_rows($res) > 0) {

                              $atendente       = pg_fetch_result($res, 0, 'admin');
                              $atendente_nome  = pg_fetch_result($res, 0, 'nome_completo');
                              $atendente_email = pg_fetch_result($res, 0, 'email');

                          }

                      } else {

                          $sql = "SELECT admin, nome_completo, email FROM tbl_admin WHERE admin = 1348";
                          $res = pg_query($con,$sql);

                          if (pg_num_rows($res) > 0) {

                              $atendente       = pg_fetch_result($res, 0, 'admin');
                              $atendente_nome  = pg_fetch_result($res, 0, 'nome_completo');
                              $atendente_email = pg_fetch_result($res, 0, 'email');

                          }

                      }

                      $sql = "SELECT  tbl_os.os                             ,
                                      tbl_os.sua_os                         ,
                                      tbl_os.data_abertura                  ,
                                      tbl_os.hora_abertura                  ,
                                      tbl_os.nota_fiscal                    ,
                                      tbl_os.serie                          ,
                                      tbl_os.data_nf                        ,
                                      tbl_os.produto                        ,
                                      tbl_os.posto                          ,
                                      tbl_os.consumidor_nome                ,
                                      tbl_os.consumidor_cpf                 ,
                                      tbl_os.consumidor_email               ,
                                      tbl_os.consumidor_fone                ,
                                      tbl_os.consumidor_cep                 ,
                                      tbl_os.consumidor_endereco            ,
                                      tbl_os.consumidor_numero              ,
                                      tbl_os.consumidor_complemento         ,
                                      tbl_os.consumidor_bairro              ,
                                      tbl_os.consumidor_cidade              ,
                                      tbl_os.consumidor_estado              ,
                                      tbl_os.defeito_reclamado              ,
                                      tbl_os.data_abertura                  ,
                                      tbl_os.os_posto                       ,
                                      tbl_revenda.revenda                   ,
                                      tbl_revenda.nome as revenda_nome      ,
                                      tbl_posto_fabrica.codigo_posto        ,
                                      tbl_posto.nome
                              FROM tbl_os
                              JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda
                              JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                              JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
                              JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
                              AND tbl_posto_fabrica.fabrica = $login_fabrica
                              WHERE tbl_os.os = $os";

                      $res      = pg_query($con,$sql);
                      $msg_erro = pg_last_error($con);

                      if (pg_num_rows($res) > 0) {

                          $os                     = pg_fetch_result($res,0,os);
                          $sua_os                 = pg_fetch_result($res,0,sua_os);
                          $data_abertura          = pg_fetch_result($res,0,data_abertura);
                          $hora_abertura          = pg_fetch_result($res,0,hora_abertura);
                          $nota_fiscal            = pg_fetch_result($res,0,nota_fiscal);
                          $serie                  = pg_fetch_result($res,0,serie);
                          $data_nf                = pg_fetch_result($res,0,data_nf);
                          $produto                = pg_fetch_result($res,0,produto);
                          $posto                  = pg_fetch_result($res,0,posto);
                          $consumidor_nome        = pg_fetch_result($res,0,consumidor_nome);
                          $consumidor_cpf         = pg_fetch_result($res,0,consumidor_cpf);
                          $consumidor_email       = pg_fetch_result($res,0,consumidor_email);
                          $consumidor_fone        = pg_fetch_result($res,0,consumidor_fone);
                          $consumidor_cep         = pg_fetch_result($res,0,consumidor_cep);
                          $consumidor_endereco    = pg_fetch_result($res,0,consumidor_endereco);
                          $consumidor_numero      = pg_fetch_result($res,0,consumidor_numero);
                          $consumidor_complemento = pg_fetch_result($res,0,consumidor_complemento);
                          $consumidor_bairro      = pg_fetch_result($res,0,consumidor_bairro);
                          $consumidor_cidade      = pg_fetch_result($res,0,consumidor_cidade);
                          $consumidor_estado      = pg_fetch_result($res,0,consumidor_estado);
                          $defeito_reclamado      = pg_fetch_result($res,0,defeito_reclamado);
                          $data_abertura_os       = pg_fetch_result($res,0,data_abertura);
                          $os_posto               = pg_fetch_result($res,0,os_posto);
                          $revenda                = pg_fetch_result($res,0,revenda);
                          $revenda_nome           = pg_fetch_result($res,0,revenda_nome);
                          $codigo_posto           = pg_fetch_result($res,0,codigo_posto);
                          $posto_nome             = pg_fetch_result($res,0,nome);

                          $mensagem_callcenter    = "Esta mensagem é gerada automaticamente, por favor não responda. <br /><br /> O posto $codigo_posto - $posto_nome abriu a OS $sua_os com o mesmo número de série informado no chamado de Call-Center $hd_chamado";

                      }

                      if (strlen($msg_erro) == 0 and $hd_chamado_status <> "Resolvido") {

                          $sql = "INSERT INTO tbl_hd_chamado_item (
                                      hd_chamado    ,
                                      data          ,
                                      comentario    ,
                                      admin         ,
                                      interno       ,
                                      status_item
                                  ) values (
                                      $hd_chamado             ,
                                      current_timestamp       ,
                                      '$mensagem_callcenter'  ,
                                      $atendente              ,
                                      'f'                     ,
                                      'Aberto'
                                  )";

                          $res       = pg_query($con,$sql);
                          $msg_erro .= pg_last_error($con);

                      }

                      if (strlen($msg_erro) == 0 and $hd_chamado_status <> "Resolvido") {

                          $sql = "INSERT INTO tbl_hd_chamado_item (
                                      hd_chamado    ,
                                      data          ,
                                      comentario    ,
                                      admin         ,
                                      interno       ,
                                      status_item
                                  ) values (
                                      $hd_chamado               ,
                                      current_timestamp         ,
                                      '$mensagem_callcenter'    ,
                                      $atendente                ,
                                      'f'                       ,
                                      'Aberto'
                                  )";

                          $res       = pg_query($con,$sql);
                          $msg_erro .= pg_last_error($con);

                      }

                      $xxhd_chamado = $hd_chamado;

                      if (strlen($msg_erro) == 0 and $hd_chamado_status == "Resolvido") {

                          $sql     = "SELECT posto FROM tbl_hd_chamado WHERE hd_chamado = $xxhd_chamado AND fabrica_responsavel = $login_fabrica";
                          $res     = pg_query($con,$sql);
                          $xxposto = pg_fetch_result($res, 0, 0);

                          if (strlen($xxposto) == 0) $xxposto = "NULL";

                          /* HD 48987 - a categoria tem que ser Ocorrência. - Samuel*/
                          $sql = "INSERT INTO tbl_hd_chamado (
                                      admin                ,
                                      data                 ,
                                      titulo               ,
                                      status               ,
                                      atendente            ,
                                      fabrica_responsavel  ,
                                      categoria            ,
                                      posto                ,
                                      fabrica
                                  ) values (
                                      $atendente                                   ,
                                      current_timestamp                            ,
                                      'Atendimento da OS $sua_os - chamado $xxhd_chamado',
                                      'Aberto'                                     ,
                                      $atendente                                   ,
                                      $login_fabrica                               ,
                                      'Ocorrência'                      ,
                                      $xxposto                                    ,
                                      $login_fabrica
                                  )";

                          $res        = pg_query($con, $sql);
                          $msg_erro  .= pg_last_error($con);
                          $res        = pg_query ($con, "SELECT CURRVAL ('seq_hd_chamado')");
                          $hd_chamado = pg_fetch_result($res, 0, 0);

                          if (strlen($msg_erro) == 0 and strlen($hd_chamado) > 0) {

                              $sql = "INSERT INTO tbl_hd_chamado_extra(
                                          hd_chamado             ,
                                          produto                ,
                                          nome                   ,
                                          endereco               ,
                                          numero                 ,
                                          complemento            ,
                                          bairro                 ,
                                          cep                    ,
                                          fone                   ,
                                          email                  ,
                                          cpf                    ,
                                          revenda                ,
                                          revenda_nome           ,
                                          posto                  ,
                                          os                     ,
                                          data_abertura_os       ,
                                          serie                  ,
                                          data_nf                ,
                                          nota_fiscal            ,
                                          defeito_reclamado      ,
                                          posto_nome             ,
                                          sua_os                 ,
                                          reclamado              ,
                                          data_abertura
                                          ) VALUES (
                                          $hd_chamado,
                                          $produto,
                                          '$consumidor_nome',
                                          '$consumidor_endereco',
                                          '$consumidor_numero',
                                          '$consumidor_complemento',
                                          '$consumidor_bairro',
                                          '$consumidor_cep',
                                          '$consumidor_fone',
                                          '$consumidor_email',
                                          '$consumidor_cpf',
                                          $revenda,
                                          '$revenda_nome',
                                          $posto,
                                          $os,
                                          '$data_abertura_os',
                                          '$serie',
                                          '$data_nf',
                                          '$nota_fiscal',
                                          $defeito_reclamado,
                                          '$posto_nome',
                                          '$sua_os',
                                          '$mensagem_callcenter' ,
                                          current_date
                                          );";
                              $res        = pg_query($con,$sql);
                              $msg_erro  .= pg_last_error($con);
                          }
                      }

                      if (strlen($msg_erro) == 0) {

                          $sql = "SELECT nome_completo, email FROM tbl_admin WHERE fabrica = $login_fabrica AND callcenter_supervisor IS TRUE";
                          $res = pg_query($con, $sql);

                          if (pg_num_rows($res) > 0) {

                              for ($w = 0; pg_num_rows($res) > $w; $w++) {

                                  $supervisor_nome  = pg_fetch_result($res, $w, 'nome_completo');
                                  $supervisor_email = pg_fetch_result($res, $w, 'email');

                                  if (strlen($msg_erro) == 0) {

                                      $mensagem      = "";
                                      $remetente     = "Suporte <helpdesk@telecontrol.com.br>";
                                      $destinatario  = $supervisor_email;
                                      $assunto       = "OS $sua_os aberta com o mesmo número de série do chamado $hd_chamado";
                                      $mensagem      = $mensagem_callcenter;
                                      $mensagem     .= "<br />Favor acompanhar\n";
                                      $mensagem     .= "<br /><br />Telecontrol\n";
                                      $mensagem     .= "<br />www.telecontrol.com.br\n";
                                      $headers       = "Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

                                      if (mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers)) {
                                          //$msg = "<br />Foi enviado um email para: ".$email_destino."<br />";
                                      } else {
                                          $msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                                      }

                                  }

                              }

                              if (strlen($msg_erro) == 0) {

                                  $mensagem      = "";
                                  $remetente     = "Suporte <helpdesk@telecontrol.com.br>";
                                  $destinatario  = $atendente_email;
                                  $assunto       = "OS $sua_os aberta com o mesmo número de série do chamado $hd_chamado";
                                  $mensagem      =  $mensagem_callcenter;
                                  $mensagem     .= "<br />Favor acompanhar\n";
                                  $mensagem     .= "<br /><br />Telecontrol\n";
                                  $mensagem     .= "<br />www.telecontrol.com.br\n";
                                  $headers       = "Return-Path: <helpdesk@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

                                  if (mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers)) {
                                      //$msg = "<br />Foi enviado um email para: ".$email_destino."<br />";
                                  } else {
                                      $msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                                  }

                              }

                          }
                      }
                  }
              }

              /* HD 7998 - TAKASHI 12/12/2007 - VERIFICA SE EXISTE ALGUM CHAMADO DE CALLCENTER PARA ESSE PRODUTO,
               SE TIVER REABRE O CHAMADO INSERE UM CHAMADO ITEM, MANDA EMAIL PARA O SUPERVISOR E PARA QUEM ABRIU O CHAMADO*/

              // se o produto tiver TROCA OBRIGATORIA, bloqueia a OS para intervencao da fabrica
              // fabio 17/01/2007 - alterado em 04/07/2007
              // adicionado para HBTech - #HD 14830 - Fabrica 25
              // adicionado para HBTech - #HD 13618 - Fabrica 45
              // adicionado para Gama - #HD 46730 - Fabrica 51
              // Destivado  or ($login_fabrica==45  AND $login_posto==6359) - HD 13618
              // Lenoxx HD 13826
              // Tectoy HD 1875997
              if (in_array($login_fabrica,array(3,6,11,25,35,51,72,98,106,108,111,115,116,117,120,201,123,124,125,126,127,128,131,172)) OR $login_fabrica >= 105) {
                  $sql = "SELECT  troca_obrigatoria,
                                  intervencao_tecnica,
                                  produto_critico
                          FROM    tbl_produto
                          WHERE   produto = $produto";
                  $res = @pg_query($con,$sql);

                  if (pg_num_rows($res) > 0 && $telecontrol_distrib != "t") {
                      $troca_obrigatoria   = trim(pg_fetch_result($res,0,troca_obrigatoria));
                      $intervencao_tecnica = trim(pg_fetch_result($res,0,intervencao_tecnica));
                      $produto_critico = trim(pg_fetch_result($res,0,produto_critico));

                      if ($troca_obrigatoria == 't' or $intervencao_tecnica=='t' or $produto_critico =='t') {

                          $sql_intervencao = "SELECT status_os
                                              FROM  tbl_os_status
                                              WHERE os = $os
                                              AND status_os IN (62,64,65)
                                              ORDER BY data DESC
                                              LIMIT 1";

                          $res_intervencao = pg_query($con, $sql_intervencao);

                          $status_os = "";

                          if (pg_num_rows ($res_intervencao) > 0){
                              $status_os = trim(pg_fetch_result($res_intervencao,0,status_os));
                          }

                          if (pg_num_rows ($res_intervencao) == 0 or $status_os == "64"){

                              if ($produto_critico == 't' and $login_fabrica <> 35) {
                                  $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O.S. com Produto Crítico')";
                                  $res = pg_query ($con,$sql);
                              }

                              if ($troca_obrigatoria == 't' && !in_array($login_fabrica, array(11,172)) ) {
                                  if($login_fabrica == 117){
                                      $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao,status_os_troca) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.',true)";
                                      $res = @pg_query ($con,$sql);
                                  }else{
                                      if ($login_fabrica == 6 ){
                                        $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O produto será analisado pela fábrica')";
                                        $res = @pg_query ($con,$sql);

                                      }else{
                                      $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
                                      $res = @pg_query ($con,$sql);
                                      }
                                  }

                                  if ($login_fabrica == 35 and $troca_obrigatoria == 't') {

                                      //hd17603
                                      $sql = "UPDATE  tbl_os
                                              SET     data_fechamento = NULL,
                                                      finalizada      = NULL
                                              WHERE   os      = $os
                                              AND     fabrica = $login_fabrica ";
                                      $res = pg_query($con,$sql);
                                      $msg_erro .= pg_last_error($con);

                                      $sql = "SELECT  os_troca,
                                                      peca,
                                                      os
                                              FROM    tbl_os_troca
                                              WHERE   os      = $os
                                              AND     pedido  IS NULL ";
                                      $res = pg_query ($con,$sql);
                                      if(pg_num_rows($res)>0){
                                          $troca_efetuada =  pg_fetch_result($res,0,os_troca);
                                          $troca_os       =  pg_fetch_result($res,0,os);
                                          $troca_peca     =  pg_fetch_result($res,0,peca);

                                          $sql = "UPDATE  tbl_os_troca
                                                  SET     os = 4836000
                                                  WHERE   os_troca = $troca_efetuada";
                                          $res = pg_query ($con,$sql);

                                          // HD 13229
                                          if(strlen($troca_peca) > 0) {
                                              $sql = "DELETE  FROM tbl_os_item
                                                      WHERE   os_item IN (
                                                                  SELECT  os_item
                                                                  FROM    tbl_os_item
                                                                  JOIN    tbl_os_produto USING(os_produto)
                                                                  WHERE   os      = $troca_os
                                                                  AND     peca    = $troca_peca
                                                              )";

                                              $res = pg_query ($con,$sql);
                                          }
                                      }

                                      $sql = "SELECT produto,sua_os,posto FROM tbl_os WHERE os = $os;";
                                      $res = @pg_query($con,$sql);
                                      $msg_erro .= pg_last_error($con);

                                      $produto = pg_fetch_result($res,0,produto);
                                      $sua_os  = pg_fetch_result($res,0,sua_os);
                                      $posto   = pg_fetch_result($res,0,posto);

                                      // adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
                                      $sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65,72,73,87,88,116,117) ORDER BY data DESC LIMIT 1";
                                      $res = pg_query($con,$sql);
                                      $qtdex = pg_num_rows($res);
                                      if ($qtdex>0){
                                          $statuss=pg_fetch_result($res,0,status_os);
                                          if (in_array($statuss,array('62','65','72','87','116'))){
                                              $proximo_status = "64";

                                              if ( $statuss == "72"){
                                                  $proximo_status = "73";
                                              }
                                              if ( $statuss == "87"){
                                                  $proximo_status = "88";
                                              }
                                              if ( $statuss == "116"){
                                                  $proximo_status = "117";
                                              }

                                              $sql = "INSERT INTO tbl_os_status
                                                      (os,status_os,data,observacao)
                                                      VALUES ($os,$proximo_status,current_timestamp,'OS Liberada- Troca Automatica')";
                                              $res = pg_query($con,$sql);
                                              $msg_erro .= pg_last_error($con);

                                              $id_servico_realizado        = 571;
                                              $id_servico_realizado_ajuste = 573;
                                              $id_solucao_os               = 472;
                                              $defeito_constatado          = 11815;

                                              if (strlen($id_servico_realizado_ajuste) > 0 AND strlen($id_servico_realizado) > 0) {
                                                  $sql = "UPDATE  tbl_os_item
                                                          SET     servico_realizado = $id_servico_realizado_ajuste
                                                          WHERE   os_item IN (
                                                              SELECT os_item
                                                              FROM tbl_os
                                                              JOIN tbl_os_produto USING(os)
                                                              JOIN tbl_os_item USING(os_produto)
                                                              JOIN tbl_peca USING(peca)
                                                              WHERE tbl_os.os       = $os
                                                              AND tbl_os.fabrica    = $login_fabrica
                                                              AND tbl_os_item.servico_realizado = $id_servico_realizado
                                                              AND tbl_os_item.pedido IS NULL
                                                          )";
                                                  /* ************* retirado TRECHO DO SQL ABAIXO - hd: 50754 - IGOR ********** */
                                                  /*AND tbl_peca.retorna_conserto IS TRUE*/
                                                  /* Segundo Fábio, essa condição é desnecessária, pois todas peças devem ser canceladas*/


                                                  $res = pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);
                                              }

                                              if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
                                                  $sql = "UPDATE tbl_os
                                                      SET solucao_os         = $id_solucao_os,
                                                          defeito_constatado = $defeito_constatado
                                                      WHERE os       = $os
                                                      AND fabrica    = $login_fabrica
                                                      AND solucao_os IS NULL
                                                      AND defeito_constatado IS NULL";
                                                  $res = pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);
                                              }
                                          }
                                      }


                                      $troca_garantia_produto = $produto;

                                      $sql = "SELECT * FROM tbl_produto JOIN tbl_familia using(familia) WHERE produto = '$troca_garantia_produto' AND fabrica = $login_fabrica;";
                                      $resProd = @pg_query($con,$sql);
                                      $msg_erro .= pg_last_error($con);

                                      if (@pg_num_rows($resProd) == 0) {
                                              $msg_erro .= "Produto informado não encontrado";
                                      }else{
                                              $troca_produto    = @pg_fetch_result ($resProd,0,produto);
                                              $troca_ipi        = @pg_fetch_result ($resProd,0,ipi);
                                              $troca_referencia = @pg_fetch_result ($resProd,0,referencia);
                                              $troca_descricao  = @pg_fetch_result ($resProd,0,descricao);
                                      }

                                      if (strlen($msg_erro) == 0) {
                                          $sql = "SELECT * FROM tbl_peca WHERE referencia = '$troca_referencia' and fabrica = $login_fabrica;";
                                          $res = pg_query($con,$sql);
                                          $msg_erro .= pg_last_error($con);

                                          if (pg_num_rows($res) == 0) {
                                              if (strlen ($troca_ipi) == 0) $troca_ipi = 10;

                                              $sql =    "SELECT peca
                                                      FROM tbl_peca
                                                      WHERE fabrica    = $login_fabrica
                                                      AND   referencia = '$troca_garantia_produto'
                                                      LIMIT 1;";
                                              $res = pg_query($con,$sql);
                                              $msg_erro .= pg_last_error($con);

                                              if (pg_num_rows($res) > 0) {
                                                  $peca = pg_fetch_result($res,0,0);
                                              }else{
                                                  $sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado) VALUES ($login_fabrica, '$troca_referencia', '$troca_descricao' , $troca_ipi , 'NAC','t')" ;
                                                  $res = pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);

                                                  $sql = "SELECT CURRVAL ('seq_peca')";
                                                  $res = pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);
                                                  $peca = pg_fetch_result($res,0,0);
                                              }
                                              $sql = "INSERT INTO tbl_lista_basica (fabrica, produto,peca,qtde) VALUES ($login_fabrica, $produto, $peca, 1);" ;
                                              $res = pg_query($con,$sql);
                                              $msg_erro .= pg_last_error($con);
                                          }else{
                                              $peca = pg_fetch_result($res,0,peca);
                                          }

                                          $sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
                                          $res = pg_query($con,$sql);
                                          $msg_erro .= pg_last_error($con);

                                          $sql = "SELECT CURRVAL ('seq_os_produto')";
                                          $res = pg_query($con,$sql);
                                          $msg_erro .= pg_last_error($con);

                                          $os_produto = pg_fetch_result($res,0,0);

                                          $sql = "
                                              SELECT *
                                              FROM   tbl_os_item
                                              JOIN   tbl_servico_realizado USING (servico_realizado)
                                              JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                              WHERE  tbl_os_produto.os = $os
                                              AND    tbl_servico_realizado.troca_de_peca
                                              AND    tbl_os_item.pedido NOTNULL " ;
                                          $res = pg_query($con,$sql);
                                          $msg_erro .= pg_last_error($con);

                                          if ( pg_num_rows($res) > 0 ) {
                                              for($w = 0 ; $w < pg_num_rows($res) ; $w++ ) {
                                                  $os_item = pg_fetch_result($res,$w,os_item);
                                                  $qtde    = pg_fetch_result($res,$w,qtde);
                                                  $pedido  = pg_fetch_result($res,$w,pedido);
                                                  $pecaxx  = pg_fetch_result($res,$w,peca);

                                                  //Verifica se está faturado, se esta embarcado devolve para estoque e cancela pedido para os itens da OS

                                                  $sql = "SELECT DISTINCT
                                                          tbl_pedido.pedido,
                                                          tbl_peca.peca,
                                                          tbl_peca.descricao,
                                                          tbl_peca.referencia,
                                                          tbl_pedido_item.qtde,
                                                          tbl_pedido_item.pedido_item,
                                                          tbl_pedido.exportado,
                                                          tbl_pedido.posto,
                                                          tbl_os_item.os_item
                                                      FROM tbl_pedido
                                                      JOIN tbl_pedido_item USING(pedido)
                                                      JOIN tbl_peca        USING(peca) ";
                                                  if($login_fabrica == 51){#HD52537 alterado apenas para a Gama pois não sei se as outras fábrica atualiza o pedido_item
                                                      $sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item AND tbl_os_item.peca = tbl_pedido_item.peca ";
                                                  }else{
                                                      $sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido        = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca ";
                                                  }
                                                      $sql .= " JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                                          WHERE tbl_pedido.pedido       = $pedido
                                                          AND   tbl_peca.fabrica        = $login_fabrica
                                                          AND   tbl_os_produto.os       = $os
                                                          AND   tbl_pedido_item.peca    = $pecaxx
                                                          AND   tbl_pedido.distribuidor = 4311 ";
                                                      $res_dis = @pg_query($con,$sql);
                                                      $msg_erro .= pg_last_error($con);

                                                      if (@pg_num_rows($res_dis) > 0) {
                                                          for($x=0;$x<@pg_num_rows($res_dis);$x++){

                                                              $pedido_pedido          = pg_fetch_result($res_dis,$x,pedido);
                                                              $pedido_peca            = pg_fetch_result($res_dis,$x,peca);
                                                              $pedido_item            = pg_fetch_result($res_dis,$x,pedido_item);
                                                              $pedido_qtde            = pg_fetch_result($res_dis,$x,qtde);
                                                              $pedido_peca_referencia = pg_fetch_result($res_dis,$x,referencia);
                                                              $pedido_peca_descricao  = pg_fetch_result($res_dis,$x,descricao);
                                                              $pedido_posto           = pg_fetch_result($res_dis,$x,posto);
                                                              $pedido_os_item         = pg_fetch_result($res_dis,$x,os_item);

                                                              if($pedido_posto==4311) $troca_distribuidor = "TRUE";

                                                              $sql = "
                                                                  SELECT DISTINCT tbl_embarque.embarque
                                                                  FROM tbl_embarque
                                                                  JOIN tbl_embarque_item USING(embarque)
                                                                  WHERE pedido_item = $pedido_item
                                                                  AND   os_item     = $pedido_os_item
                                                                  AND   faturar IS NOT NULL";

                                                              $res_x1 = @pg_query($con,$sql);
                                                              $tem_faturamento = @pg_num_rows($res_x1);
                                                              if($tem_faturamento>0) {
                                                                  $troca_distribuidor = "TRUE";
                                                                  $troca_faturado     = "TRUE";
                                                              }

                                                              $pecas_canceladas .= "$pedido_peca_referencia - $pedido_peca_descricao ($pedido_qtde UN.),";

                                                              $sql2 = "SELECT fn_pedido_cancela_garantia(4311,$login_fabrica,$pedido_pedido,$pedido_peca,$pedido_os_item,'Troca de Produto','null'); ";

                                                              $res_x2 = pg_query($con,$sql2);

                                                              $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
                                                              $destinatario = "helpdesk@telecontrol.com.br,";

                                                              $assunto      = "Troca - Cancelamento de Pedido de Peça do Fabricante";
                                                              $mensagem     = "$os trocada";
                                                              $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                                                              //Samuel tirou em 27/02/2009
                                                              //mail($destinatario,$assunto,$mensagem,$headers);

                                                          }
                                                      }
                                                      //Cancela a peça que ainda não teve o seu pedido exportado //Raphael Giovanini
                                                      $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
                                                          WHERE pedido = $pedido
                                                          AND   pedido = tbl_pedido.pedido
                                                          AND   peca   = $pecaxx
                                                          AND   tbl_pedido.exportado IS NULL ;";
                                                      $res3 = @pg_query($con,$sql);
                                                      $msg_erro .= pg_last_error($con);
                                                  }
                                              }

                                              $sql = "SELECT  servico_realizado
                                                      FROM    tbl_servico_realizado
                                                      WHERE   troca_produto
                                                      AND     fabrica = $login_fabrica" ;
                                              $res = pg_query($con,$sql);
                                              $msg_erro .= pg_last_error($con);
                                              if(pg_num_rows($res) > 0){
                                                  $servico_realizado = pg_fetch_result($res,0,0);
                                              }
                                              if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

                                              if(strlen($msg_erro)==0){
                                                  $sql = "INSERT INTO tbl_os_item (
                                                                                      os_produto,
                                                                                      peca,
                                                                                      qtde,
                                                                                      servico_realizado,
                                                                                      admin,
                                                                                      peca_obrigatoria
                                                                                  ) VALUES (
                                                                                      $os_produto,
                                                                                      $peca,
                                                                                      1,
                                                                                      $servico_realizado,
                                                                                      null,
                                                                                      TRUE
                                                                                  )
                                                  ";

                                                  $res = pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);

                                                  $sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
                                                  $res = pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);


                                                  if(($login_fabrica == 3 or $login_fabrica==45 or $login_fabrica==35 OR $login_fabrica==25) AND pg_num_rows($res)==1 ) {
                                                      $sql = "UPDATE tbl_os SET
                                                              troca_garantia          = 't',
                                                              ressarcimento           = 'f',
                                                              troca_garantia_admin    = $login_admin
                                                              WHERE os = $os AND fabrica = $login_fabrica";
                                                  }else{
                                                      if($login_fabrica == 3){
                                                          $sql = "UPDATE tbl_os SET
                                                              troca_garantia          = 't',
                                                              ressarcimento           = 'f',
                                                              troca_garantia_admin    = $login_admin,
                                                              data_conserto           = CURRENT_TIMESTAMP
                                                              WHERE os = $os AND fabrica = $login_fabrica";
                                                      }elseif($login_fabrica == 35){
                                                          # HD 65952
                                                          $sql = "UPDATE tbl_os SET
                                                              troca_garantia          = 't',
                                                              ressarcimento           = 'f'
                                                              WHERE os = $os AND fabrica = $login_fabrica";
                                                      }else{
                                                          $sql = "UPDATE tbl_os SET
                                                              troca_garantia          = 't',
                                                              ressarcimento           = 'f',
                                                              troca_garantia_admin    = $login_admin,
                                                              data_fechamento         = CURRENT_DATE
                                                              WHERE os = $os AND fabrica = $login_fabrica";
                                                      }
                                                  }
                                                  $res = @pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);

                                                  $observacao_pedido = 'Troca de Produto Automatica';

                                                  $sql = "UPDATE tbl_os_extra SET
                                                          obs_nf                     = '$observacao_pedido'
                                                          WHERE os = $os;";

                                                  $res = @pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);

                                                  $sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
                                                  $res = @pg_query($con,$sql);
                                                  $msg_erro .= pg_last_error($con);

                                                  $causa_troca = '25';
                                                  $setor = 'SAP';
                                                  $situacao_atendimento = '0';
                                                  $gerar_pedido = "'t'";

                                                  if(strlen($msg_erro) == 0 ){
                                                      $sql = "INSERT INTO tbl_os_troca (
                                                                  setor                 ,
                                                                  situacao_atendimento  ,
                                                                  os                    ,
                                                                  admin                 ,
                                                                  peca                  ,
                                                                  observacao            ,
                                                                  causa_troca           ,
                                                                  gerar_pedido          ,
                                                                  envio_consumidor      ,
                                                                  ri                    ,
                                                                  fabric
                                                              )VALUES(
                                                                  '$setor'                 ,
                                                                  $situacao_atendimento    ,
                                                                  $os                      ,
                                                                  null                     ,
                                                                  $peca                    ,
                                                                  '$observacao_pedido'     ,
                                                                  $causa_troca             ,
                                                                  $gerar_pedido            ,
                                                                  'f'                      ,
                                                                  $os                      ,
                                                                  $login_fabrica
                                                              )";
                                                      $res = @pg_query($con,$sql);
                                                      $msg_erro .= pg_last_error($con);
                                                  }
                                          }
                                      }
                                      //termina aqui troca
                                  }

                              }else if ($intervencao_tecnica == 't') { # HD 13826
                                  if ( in_array($login_fabrica, array(11,172)) ) {
                                      $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'OS com intervenção técnica')";
                                      $res = @pg_query ($con,$sql);

                                      $sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,65,current_timestamp,'Reparo do Produto na Fábrica')";
                                      $res = @pg_query ($con,$sql);

                                      $sql = "INSERT INTO tbl_os_retorno (os) values ($os)";
                                      $res = @pg_query ($con,$sql);
                                  }
                              }
                          }
                      }
                  } else {

                    if ($telecontrol_distrib == 't') {

                      $sql = "SELECT  troca_obrigatoria,
                                      intervencao_tecnica,
                                      produto_critico
                              FROM    tbl_produto
                              WHERE   produto = {$produto}";
                      $res = pg_query($con,$sql);

                      $troca_obrigatoria   = pg_fetch_result($res,0, "troca_obrigatoria");
                      $intervencao_tecnica = pg_fetch_result($res,0, "intervencao_tecnica");
                      $produto_critico     = pg_fetch_result($res,0, "produto_critico");

                      if ($produto_critico == 't') {

                        \Posvenda\Helpers\Auditoria::gravar($os, 3, "Produto Crítico", "Em auditoria", $con);

                      }

                      if ($troca_obrigatoria == 't') {

                        \Posvenda\Helpers\Auditoria::gravar($os, 3, "OS em auditoria por Produto de troca obrigatoria", "Em auditoria", $con);

                      }

                      if (in_array($login_fabrica, array(11,172)) && $intervencao_tecnica == "t") {

                        \Posvenda\Helpers\Auditoria::gravar($os, 6, "OS com intervenção técnica", "Em auditoria", $con);

                        \Posvenda\Helpers\Auditoria::gravar($os, 3, "Reparo do Produto na Fábrica", "Em auditoria", $con);

                        $sql = "INSERT INTO tbl_os_retorno (os) values ($os)";
                        $res = pg_query ($con,$sql);

                      }

                    }

                  }
              }
              // fim TROCA OBRIGATORIA

              /**
               * Auditoria KM Padrão (KM Superior a x .. Alteração manual de KM)
               */

              if ($km_auditoria == "TRUE" OR ($login_fabrica == 140 && $entrega_tecnica == "t")) {

                  if($login_fabrica == 140 && $entrega_tecnica == "t"){
                    $obs_km = "OS com intervenção de Entrega T&eacute;cnica";
                  }

                  $sql = "SELECT status_os
                          FROM tbl_os_status
                          WHERE os = $os
                          AND status_os IN (98,99,100,101)
                          ORDER BY data DESC
                          LIMIT 1";
                  $res = @pg_query ($con,$sql);
                  if (pg_num_rows($res) > 0){
                      $status_os  = pg_fetch_result ($res,0,status_os);
                  }
                  if (pg_num_rows($res) == 0 OR $status_os <> "98") {
                      $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,98,'$obs_km','$automatico')";
                      $res = @pg_query ($con,$sql);
                  }
              }

              /**
               * Auditoria de numero de série (Intervenção de NS)
               */

              if ($serie_auditoria == "TRUE") {

                  $sql = "SELECT status_os
                          FROM tbl_os_status
                          WHERE os = $os
                          AND status_os IN (102,103,104)
                          ORDER BY data DESC
                          LIMIT 1";

                  $res = @pg_query ($con,$sql);

                  if (pg_num_rows($res) > 0) {
                      $status_os  = pg_fetch_result($res, 0, 'status_os');
                  }

                  if (pg_num_rows($res) == 0 OR $status_os <> "102") {
                      $sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,102,'OS Aguardando aprovação de número de Série.')";
                      $res = @pg_query ($con,$sql);
                  }

              }
              if ($login_fabrica == 51 or $login_fabrica == 35) {

                      $sqlT = "SELECT tbl_produto.troca_obrigatoria
                          FROM tbl_os
                          JOIN tbl_produto USING(produto)
                          WHERE os      = $os
                          AND   fabrica = $login_fabrica";

                  $resT = pg_query ($con,$sqlT) ;

                  if (pg_num_rows($resT) > 0) {
                      $troca_obrigatoria = pg_fetch_result($resT, 0, 'troca_obrigatoria');
                  }

              }

              if ($login_fabrica == 116) {
                  $sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
                  $res = pg_query($con, $sql);

                  if (pg_fetch_result($res, 0, "entrega_tecnica") == "t") {
                      header("Location: checklist_entrega_tecnica.php?os=$os");
                      exit;
                  }
              }
              if ($login_fabrica == 24) {
                 $sql = "SELECT tbl_hd_chamado.hd_chamado
                            FROM tbl_hd_chamado
                            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                            WHERE tbl_hd_chamado.fabrica = $login_fabrica
                            AND (tbl_hd_chamado_extra.cpf = '$consumidor_cpf' or tbl_hd_chamado_extra.fone = '$consumidor_fone')
							AND tbl_hd_chamado_extra.os IS NULL
							AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							AND	tbl_hd_chamado.data > CURRENT_TIMESTAMP - interval ' 2 year'
                            AND  UPPER(fn_retira_especiais(tbl_hd_chamado.titulo)) = UPPER(fn_retira_especiais('Indicação de Posto'));";
                  $res = pg_query($con, $sql);

                  if (pg_num_rows($res)> 0 ){

                    $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

                    $sql = "UPDATE tbl_os SET hd_chamado = $hd_chamado WHERE os = $os";
                    $res = pg_query($con, $sql);
                    $sql = "UPDATE tbl_hd_chamado_extra SET os = $os WHERE hd_chamado = $hd_chamado";
                    $res = pg_query($con, $sql);

                  }
              }

              if(empty($hd_chamado) AND $login_fabrica == 30){
                $sql_hd = "SELECT hd_chamado FROM tbl_os WHERE os = {$os}";
                $res_hd = pg_query($con,$sql_hd);

                $hd_chamado = pg_fetch_result($res_hd, 0, 'hd_chamado');
              }

              if($login_fabrica == 30 AND !empty($hd_chamado)){
                //verificaAlteracaoDadosAtendimento($hd_chamado,$os);
              }

              if ($imprimir_os == "imprimir") {
                  if ($login_fabrica == 3) {

                      $sql    = "SELECT sua_os from tbl_os where os = $os and fabrica = $login_fabrica";
                      $res    = @pg_query($con,$sql);
                      $sua_os = @pg_fetch_result($res, 0, 0);

                      header("Location: os_consulta_lite.php?btn_acao=ok&sua_os=$sua_os");
                      exit;

                  } else {

                      $qtde_estiquetas = $_POST['qtde_etiquetas'];

                      if ($login_fabrica == 7) {

                          header("Location: os_filizola_valores.php?os=$os&imprimir=1");
                          exit;

                      }

                      if (($login_fabrica == 51 or $login_fabrica == 35 or $login_fabrica == 6) AND $troca_obrigatoria == "t") {

                          header("Location: os_finalizada.php?os=$os");
                          exit;

                      } else {
                        /*
                        se alterar esta validação alterar na parte que não imprime a os
                        também alterar no admin
                        */
                        if (in_array($login_fabrica, array(141,144)) && in_array($tipo_atendimento, array(177,179,182,184))) {
                          /*
                          tem que procurar por true porque no banco o default do campo lancar_peca é false
                          todos os defeitos constatados estão gravados com o lancar_peca false
                          */
                          $sql = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = $login_fabrica AND lancar_peca IS TRUE";
                          $res = pg_query($con, $sql);

                          $defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

                          $sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado, data_fechamento = CURRENT_DATE WHERE fabrica = $login_fabrica AND os = $os";
                          $res = pg_query($con, $sql);

                          if (strlen(pg_last_error()) > 0) {
                            $msg_erro = "Erro ao gravar OS";
                          }

                          $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
                          $res = pg_query($con, $sql);

                          if (strlen(pg_last_error()) > 0) {
                            $msg_erro = pg_last_error();
                          }

                            if(strlen($msg_erro) == 0){

                              if (in_array($login_fabrica, array(141,144))) {
                                $sql = "SELECT tbl_os.consumidor_email, tbl_produto.descricao, tbl_os.nota_fiscal
                                        FROM tbl_os
                                        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                                        WHERE tbl_os.fabrica = {$login_fabrica}
                                        AND tbl_os.os = {$os}";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    $consumidor_email  = pg_fetch_result($res, 0, "consumidor_email");
                                    $produto_descricao = pg_fetch_result($res, 0, "descricao");
                                    $nota_fiscal       = pg_fetch_result($res, 0, "nota_fiscal");

                                    if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {
                                        $header  = "MIME-Version: 1.0 \r\n";
                                        $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                                        $header .= "To: {$consumidor_email} \r\n";
                                        $header .= "From: naoresponder@telecontrol.com.br\r\n";

                                        $conteudo = "Foi aberta a Ordem de Serviço {$os}.<br />
                                                    Produto: {$produto_descricao}.<br />
                                                    Nota fiscal: {$nota_fiscal}.<br />
                                                    Para mais informações entre em contato com a assistência.";

                                        $nome_fabrica = ($login_fabrica == 141) ? "UNICOBA" : "HIKARI";

                                        mail($consumidor_email, "{$nome_fabrica} - Abertura de Ordem de Serviço", $conteudo, $header);
                                    }
                                }
                              }

                            }

                            if (strlen($msg_erro) > 0) {
                                pg_query($con, "ROLLBACK");
                            } else {
                                pg_query($con, "COMMIT");
                                header("Location: os_finalizada.php?os=$os");
                                exit;
                            }

                          }

                          header("Location: os_item_new.php?os=$os&imprimir=1&qtde_etiq=$qtde_estiquetas");
                          exit;

                      }

                  }

              } else {

                  if ($login_fabrica == 3 or $login_fabrica == 85) {
                      $sql    = "SELECT sua_os from tbl_os where os = $os and fabrica = $login_fabrica";
                      $res    = @pg_query($con,$sql);
                      $sua_os = @pg_fetch_result($res,0,0);

                      header("Location: os_consulta_lite.php?btn_acao=ok&sua_os=$sua_os");
                      exit;

                  } else {

                      if ($login_fabrica == 7) {
                          header("Location: os_filizola_valores.php?os=$os");
                          exit;
                      }
                      if (($login_fabrica == 51 or $login_fabrica == 35 or $login_fabrica == 6) AND $troca_obrigatoria == "t") {
                          header("Location: os_finalizada.php?os=$os");
                          exit;
                      } else {
                          if ($login_fabrica == 42 and ($cook_tipo_posto_et == "t" or $tipo_atendimento_et == "t")) {
                                header("Location: os_press.php?os=$os");
                                exit;
                          } else {
                              /*
                              se alterar esta validação alterar na parte que imprime a os
                              também alterar no admin
                              */
                              if (in_array($login_fabrica, array(141,144)) && in_array($tipo_atendimento, array(177,179,182,184))) {
                                /*
                                tem que procurar por true porque no banco o default do campo lancar_peca é false
                                todos os defeitos constatados estão gravados com o lancar_peca false
                                */
                                $sql = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = $login_fabrica AND lancar_peca IS TRUE";
                                $res = pg_query($con, $sql);

                                $defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

                                $sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado, data_fechamento = CURRENT_DATE WHERE fabrica = $login_fabrica AND os = $os";
                                $res = pg_query($con, $sql);

                                if (strlen(pg_last_error()) > 0) {
                                  $msg_erro = "Erro ao gravar OS";
                                }

                                $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
                                $res = pg_query($con, $sql);

                                if (strlen(pg_last_error()) > 0) {
                                  $msg_erro = pg_last_error();
                                }

                                if (in_array($login_fabrica, array(141,144))) {
                                    $sql = "SELECT tbl_os.consumidor_email, tbl_produto.descricao, tbl_os.nota_fiscal
                                            FROM tbl_os
                                            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                                            WHERE tbl_os.fabrica = {$login_fabrica}
                                            AND tbl_os.os = {$os}";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        $consumidor_email  = pg_fetch_result($res, 0, "consumidor_email");
                                        $produto_descricao = pg_fetch_result($res, 0, "descricao");
                                        $nota_fiscal       = pg_fetch_result($res, 0, "nota_fiscal");

                                        if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {
                                            $header  = "MIME-Version: 1.0 \r\n";
                                            $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                                            $header .= "To: {$consumidor_email} \r\n";
                                            $header .= "From: naoresponder@telecontrol.com.br\r\n";

                                            $conteudo = "Foi aberta a Ordem de Serviço {$os}.<br />
                                                        Produto: {$produto_descricao}.<br />
                                                        Nota fiscal: {$nota_fiscal}.<br />
                                                        Para mais informações entre em contato com a assistência.";

                                            $nome_fabrica = ($login_fabrica == 141) ? "UNICOBA" : "HIKARI";

                                            mail($consumidor_email, "{$nome_fabrica} - Abertura de Ordem de Serviço", $conteudo, $header);
                                        }
                                    }
                                }

                                if (strlen($msg_erro) > 0) {
                                  pg_query($con, "ROLLBACK");
                                } else {
                                  pg_query($con, "COMMIT");
                                }

                                header("Location: os_item_new.php?os=$os");
                                exit;
                              }

                              if (strlen($msg_erro) > 0) {
                                pg_query($con, "ROLLBACK");
                              } else {
                                pg_query($con, "COMMIT");

                                 if (in_array($login_fabrica, array(144))) {
                                  $sql = "SELECT tbl_os.consumidor_email, tbl_produto.descricao, tbl_os.nota_fiscal
                                          FROM tbl_os
                                          INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                                          WHERE tbl_os.fabrica = {$login_fabrica}
                                          AND tbl_os.os = {$os}";
                                  $res = pg_query($con, $sql);

                                  if (pg_num_rows($res) > 0) {
                                      $consumidor_email  = pg_fetch_result($res, 0, "consumidor_email");
                                      $produto_descricao = pg_fetch_result($res, 0, "descricao");
                                      $nota_fiscal       = pg_fetch_result($res, 0, "nota_fiscal");

                                      if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {
                                          $header  = "MIME-Version: 1.0 \r\n";
                                          $header .= "Content-type: text/html; charset=iso-8859-1 \r\n";
                                          $header .= "To: {$consumidor_email} \r\n";
                                          $header .= "From: naoresponder@telecontrol.com.br\r\n";

                                          $conteudo = "Foi aberta a Ordem de Serviço {$os}.<br />
                                                      Produto: {$produto_descricao}.<br />
                                                      Nota fiscal: {$nota_fiscal}.<br />
                                                      Para mais informações entre em contato com a assistência.";

                                          $nome_fabrica = ($login_fabrica == 141) ? "UNICOBA" : "HIKARI";

                                          mail($consumidor_email, "{$nome_fabrica} - Abertura de Ordem de Serviço", $conteudo, $header);
                                      }
                                  }
                                }

                              }


                          }

                          if (in_array($login_fabrica, [19])) {

                            if (!empty($_POST['garantia_lorenzetti']) or $tipo_atendimento == 339) {

                              $sqlFinaliza = "UPDATE tbl_os
                                              SET finalizada        = current_timestamp,
                                                  status_checkpoint = 9,
                                                  data_fechamento   = current_date
                                              WHERE os = {$os}";
                              pg_query($con, $sqlFinaliza);

                              header("Location: menu_inicial.php");

                            } else {
                              header("Location: os_item_new.php?os=$os");
                            }

                          } else {
                            header("Location: os_item_new.php?os=$os");
                          }

                          exit;
                      }



                      }

                  }

	  	}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
	  	}

          } else {
              $res = @pg_query ($con,"ROLLBACK TRANSACTION");

              if ( in_array($login_fabrica, array(11,172)) ) {

                  if (strpos ($msg_erro, "Produto fora da Garantia") > 0) {

                      $_SESSION['sua_os']        = $sua_os            ;
                      $_SESSION['fabrica']       = $login_fabrica     ;
                      $_SESSION['produto']       = $produto           ;
                      $_SESSION['posto']         = $posto             ;
                      $_SESSION['nota_fiscal']   = $xnota_fiscal      ;
                      $_SESSION['data_nf']       = $xdata_nf          ;
                      $_SESSION['data_abertura'] = $xdata_abertura    ;
                      $_SESSION['numero_serie']  = $xproduto_serie    ;
                      $_SESSION['cnpj_revenda']  = $xrevenda_cnpj     ;
                      $_SESSION['nome_revenda']  = $xrevenda_nome     ;

                  }

              }

          }

      } else {

          $res = @pg_query($con, "ROLLBACK TRANSACTION");

          if ( in_array($login_fabrica, array(11,172)) ) {

              if (strpos($msg_erro, "Produto fora da Garantia") > 0) {

                  $_SESSION['sua_os']        = $sua_os            ;
                  $_SESSION['fabrica']       = $login_fabrica     ;
                  $_SESSION['produto']       = $produto           ;
                  $_SESSION['posto']         = $posto             ;
                  $_SESSION['nota_fiscal']   = $xnota_fiscal      ;
                  $_SESSION['data_nf']       = $xdata_nf          ;
                  $_SESSION['data_abertura'] = $xdata_abertura    ;
                  $_SESSION['numero_serie']  = $xproduto_serie    ;
                  $_SESSION['cnpj_revenda']  = $xrevenda_cnpj     ;
                  $_SESSION['nome_revenda']  = $xrevenda_nome     ;

              }

          }

      }

  } else {

      if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
          $msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

      if(strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0){
        $msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
      }


      if (strpos ($msg_erro,"tbl_os_unico") > 0)
          $msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

      if ( in_array($login_fabrica, array(11,172)) ) {

          if (strpos($msg_erro, "Produto fora da Garantia") > 0) {

              $_SESSION['sua_os']        = $sua_os            ;
              $_SESSION['fabrica']       = $login_fabrica     ;
              $_SESSION['produto']       = $produto           ;
              $_SESSION['posto']         = $posto             ;
              $_SESSION['nota_fiscal']   = $xnota_fiscal      ;
              $_SESSION['data_nf']       = $xdata_nf          ;
              $_SESSION['data_abertura'] = $xdata_abertura    ;
              $_SESSION['numero_serie']  = $xproduto_serie    ;
              $_SESSION['cnpj_revenda']  = $xrevenda_cnpj     ;
              $_SESSION['nome_revenda']  = $xrevenda_nome     ;

          }

      }
  }

  if(strlen(pg_last_error()) > 0 or strlen($msg_erro) > 0) {
      $res = @pg_query ($con,"ROLLBACK TRANSACTION");
  }else{
    if ($login_fabrica == 3) {
      if (!ativa_produto($produto, $os, $produto_serie, $posto)) {
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
      }
    }
	  $res = @pg_query ($con,"COMMIT TRANSACTION");
	  header("Location: os_item_new.php?os=$os");
	  exit;
  }
}

/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen ($os) > 0) {
    $sql =    "SELECT tbl_os.sua_os                                                    ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
                    to_char(tbl_os.data_digitacao,'YYYY-MM-DD') AS data_digitacao,
                    tbl_os.hora_abertura                                             ,
                    tbl_os.consumidor_nome                                           ,
                    tbl_os.consumidor_cpf                                            ,
                    tbl_os.consumidor_cidade                                         ,
                    tbl_os.consumidor_fone                                           ,
                    tbl_os.consumidor_celular                                        ,
                    tbl_os.consumidor_email                                        ,
                    tbl_os.consumidor_fone_comercial                                 ,
                    tbl_os.consumidor_estado                                         ,
                    tbl_os.consumidor_endereco                                       ,
                    tbl_os.consumidor_numero                                         ,
                    tbl_os.consumidor_complemento                                    ,
                    tbl_os.consumidor_bairro                                         ,
                    tbl_os.consumidor_cep                                            ,
                    tbl_os.revenda_cnpj                                              ,
                    tbl_os.revenda_nome                                              ,
                    tbl_os.revenda                                                   ,
                    tbl_os.nota_fiscal                                               ,
                    to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
                    tbl_os.consumidor_revenda                                        ,
                    tbl_os.aparencia_produto                                         ,
                    tbl_os.codigo_fabricacao                                         ,
                    tbl_os.type                                                      ,
                    tbl_os.satisfacao                                                ,
                    tbl_os.laudo_tecnico                                             ,
                    tbl_os.tipo_os_cortesia                                          ,
                    tbl_os.serie                                                     ,
                    tbl_os.qtde_produtos                                             ,
                    tbl_os.troca_faturada                                            ,
                    tbl_os.acessorios                                                ,
                    tbl_os.tipo_os                                                   ,
                    tbl_os.condicao                                                  ,
                    tbl_produto.produto                        AS produto            ,
                    tbl_produto.referencia                     AS produto_referencia ,
                    tbl_produto.descricao                      AS produto_descricao  ,
                    tbl_produto.voltagem                       AS produto_voltagem   ,
                    tbl_posto_fabrica.codigo_posto                                   ,
                    tbl_os.prateleira_box                                            ,
                    tbl_os.tipo_atendimento                                          ,
                    tbl_os.cortesia                                                 ,
                    CASE WHEN (tbl_os.defeito_reclamado_descricao IS NULL OR tbl_os.defeito_reclamado_descricao = '') AND tbl_os.defeito_reclamado NOTNULL
                         THEN
                              tbl_defeito_reclamado.descricao
                         ELSE
                              tbl_os.defeito_reclamado_descricao
                    END AS defeito_reclamado_descricao                               ,
                    tbl_os.defeito_reclamado                  AS def_reclamado,
                    tbl_os.quem_abriu_chamado                                        ,
                    tbl_os.capacidade                           AS produto_capacidade,
                    tbl_os.versao                               AS versao            ,
                    tbl_os.divisao                              AS divisao           ,
                    tbl_os.valores_adicionais                                        ,
                    tbl_os.os_posto                                                  ,
                    tbl_os_extra.taxa_visita                                         ,
                    tbl_os_extra.visita_por_km                                       ,
                    tbl_os_extra.valor_por_km                                        ,
                    tbl_os_extra.hora_tecnica                                        ,
                    tbl_os_extra.regulagem_peso_padrao                               ,
                    tbl_os_extra.certificado_conformidade                            ,
                    tbl_os_extra.valor_diaria                                        ,
                    tbl_os_extra.veiculo                                             ,
                    tbl_os_extra.desconto_deslocamento                               ,
                    tbl_os_extra.desconto_hora_tecnica                               ,
                    tbl_os_extra.desconto_diaria                                     ,
                    tbl_os_extra.desconto_regulagem                                  ,
                    tbl_os_extra.desconto_certificado                                ,
                    tbl_os.qtde_km                                                   ,
                    tbl_os_extra.deslocamento_km                                     ,
                    tbl_os_extra.coa_microsoft                                       ,
                    tbl_os_extra.classificacao_os
            FROM tbl_os
            LEFT JOIN tbl_produto  ON tbl_produto.produto       = tbl_os.produto
            JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
            LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
            LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
            WHERE tbl_os.os = $os
            AND   tbl_os.posto = $posto
            AND   tbl_os.fabrica = $login_fabrica";
    $res = @pg_query ($con,$sql);

    if (pg_num_rows ($res) == 1) {

        if ($login_fabrica == '91') {
            $hoje = new DateTime(date('Y-m-d'));
            $abriu_os = new DateTime(pg_fetch_result($res, 0, 'data_digitacao'));

            if ($abriu_os <> $hoje) {
                echo '<meta http-equiv="refresh" content="0; url=menu_os.php" />';
                exit;
            }
        }

        $sua_os                        = pg_fetch_result ($res,0,sua_os);

        if($login_fabrica == 91){
          $distancia_km_conferencia   = pg_fetch_result ($res,0,qtde_km);
        }
        if($login_fabrica == 30){ //hd_chamado=2798091
          $qtde_km = pg_fetch_result($res, 0, qtde_km);
          $distancia_km_conferencia   = pg_fetch_result ($res,0,deslocamento_km);
        }

        $data_abertura                  = pg_fetch_result ($res,0,data_abertura);
        $hora_abertura                  = pg_fetch_result ($res,0,hora_abertura);
        $consumidor_nome                = pg_fetch_result ($res,0,consumidor_nome);
        $consumidor_cpf                 = pg_fetch_result ($res,0,consumidor_cpf);
        $consumidor_cidade              = pg_fetch_result ($res,0,consumidor_cidade);
        $consumidor_fone                = pg_fetch_result ($res,0,consumidor_fone);
        $consumidor_email               = pg_fetch_result ($res,0,consumidor_email);
        $consumidor_celular             = pg_fetch_result ($res,0,consumidor_celular);//15091
        $consumidor_fone_comercial      = pg_fetch_result ($res,0,consumidor_fone_comercial);
        $consumidor_estado              = pg_fetch_result ($res,0,consumidor_estado);
        //takashi 02-09
        $consumidor_endereco            = pg_fetch_result ($res,0,consumidor_endereco);
        $consumidor_numero              = pg_fetch_result ($res,0,consumidor_numero);
        $consumidor_complemento         = pg_fetch_result ($res,0,consumidor_complemento);
        $consumidor_bairro              = pg_fetch_result ($res,0,consumidor_bairro);
        $consumidor_cep                 = pg_fetch_result ($res,0,consumidor_cep);
        //takashi 02-09
        $revenda_cnpj                   = pg_fetch_result ($res,0,revenda_cnpj);
        $revenda_nome                   = pg_fetch_result ($res,0,revenda_nome);
        $nota_fiscal                    = pg_fetch_result ($res,0,nota_fiscal);
        $data_nf                        = pg_fetch_result ($res,0,data_nf);
        $consumidor_revenda             = pg_fetch_result ($res,0,consumidor_revenda);
        $aparencia_produto              = pg_fetch_result ($res,0,aparencia_produto);
        $acessorios                     = pg_fetch_result ($res,0,acessorios);
        $codigo_fabricacao              = pg_fetch_result ($res,0,codigo_fabricacao);
        $type                           = pg_fetch_result ($res,0,type);
        $satisfacao                     = pg_fetch_result ($res,0,satisfacao);
        $laudo_tecnico                  = pg_fetch_result ($res,0,laudo_tecnico);
        $tipo_os_cortesia               = pg_fetch_result ($res,0,tipo_os_cortesia);
        $produto_serie                  = pg_fetch_result ($res,0,serie);
        $qtde_produtos                  = pg_fetch_result ($res,0,qtde_produtos);
        $produto                        = pg_fetch_result ($res,0,produto);
        $produto_referencia             = pg_fetch_result ($res,0,produto_referencia);
        $produto_descricao              = pg_fetch_result ($res,0,produto_descricao);
        $produto_voltagem               = pg_fetch_result ($res,0,produto_voltagem);
        $troca_faturada                 = pg_fetch_result ($res,0,troca_faturada);
        $codigo_posto                   = pg_fetch_result ($res,0,codigo_posto);
        $tipo_os                        = pg_fetch_result ($res,0,tipo_os);
        $condicao                       = pg_fetch_result ($res,0,condicao);
        $xxxrevenda                     = pg_fetch_result ($res,0,revenda);
        $tipo_atendimento               = pg_fetch_result ($res,0,tipo_atendimento);
        $os_cortesia                    = pg_fetch_result ($res,0,cortesia);
        $defeito_reclamado_descricao    = pg_fetch_result ($res,0,defeito_reclamado_descricao);
        $produto_capacidade             = pg_fetch_result ($res,0,produto_capacidade);
        $versao                         = pg_fetch_result ($res,0,versao);
        $divisao                        = pg_fetch_result ($res,0,divisao);
        $os_posto                       = pg_fetch_result ($res,0,os_posto);
        $quem_abriu_chamado             = pg_fetch_result ($res,0,quem_abriu_chamado);
        $taxa_visita                    = pg_fetch_result ($res,0,taxa_visita);
        $visita_por_km                  = pg_fetch_result ($res,0,visita_por_km);
        $valor_por_km                   = pg_fetch_result ($res,0,valor_por_km);
        $hora_tecnica                   = pg_fetch_result ($res,0,hora_tecnica);
        $regulagem_peso_padrao          = pg_fetch_result ($res,0,regulagem_peso_padrao);
        $certificado_conformidade       = pg_fetch_result ($res,0,certificado_conformidade);
        $valor_diaria                   = pg_fetch_result ($res,0,valor_diaria);
        $veiculo                        = pg_fetch_result ($res,0,veiculo);
        $desconto_deslocamento          = pg_fetch_result ($res,0,desconto_deslocamento);
        $desconto_hora_tecnica          = pg_fetch_result ($res,0,desconto_hora_tecnica);
        $desconto_diaria                = pg_fetch_result ($res,0,desconto_diaria);
        $desconto_regulagem             = pg_fetch_result ($res,0,desconto_regulagem);
        $desconto_certificado           = pg_fetch_result ($res,0,desconto_certificado);
        $deslocamento_km                = pg_fetch_result ($res,0,deslocamento_km);
        $coa_microsoft                  = pg_fetch_result ($res,0,coa_microsoft);
        $classificacao_os               = pg_fetch_result ($res,0,classificacao_os);

        if ($regulagem_peso_padrao > 0){
            $cobrar_regulagem = 't';
        }

        if($login_fabrica == 50){
          $defeito_reclamado = pg_fetch_result($res, 0, 'def_reclamado');
        }
        if ($certificado_conformidade > 0){
            $cobrar_certificado = 't';
        }

        if ($valor_diaria == 0 AND $hora_tecnica == 0){
            $cobrar_hora_diaria = "isento";
        }
        if ($valor_diaria > 0 AND $hora_tecnica == 0){
            $cobrar_hora_diaria = "diaria";
        }
        if ($valor_diaria == 0 AND $hora_tecnica > 0){
            $cobrar_hora_diaria = "hora";
        }

        if ($valor_por_km == 0 AND $taxa_visita == 0){
            $cobrar_deslocamento = "isento";
        }
        if ($valor_por_km > 0 AND $taxa_visita == 0){
            $cobrar_deslocamento = "valor_por_km";
        }
        if ($valor_por_km == 0 AND $taxa_visita > 0){
            $cobrar_deslocamento = "taxa_visita";
        }

        if ($veiculo == 'carro'){
            $valor_por_km_carro = $valor_por_km;
        }

        if ($veiculo == 'caminhao'){
            $valor_por_km_caminhao = $valor_por_km;
        }

        if( !in_array($login_fabrica, array(7,11,172)) ) {
            $prateleira_box        = pg_fetch_result($res,0, prateleira_box);
        }

        if($login_fabrica == 15 OR $login_fabrica == 140) {
            $preco_produto        = pg_fetch_result($res,0, valores_adicionais);
        }

        if (strlen($xxxrevenda)>0){
            $xsql  = "SELECT tbl_revenda.revenda,
                            tbl_revenda.nome,
                            tbl_revenda.cnpj,
                            tbl_revenda.fone,
                            tbl_revenda.endereco,
                            tbl_revenda.numero,
                            tbl_revenda.complemento,
                            tbl_revenda.bairro,
                            tbl_revenda.cep,
                            tbl_cidade.nome AS cidade,
                            tbl_cidade.estado
                            FROM tbl_revenda
                            LEFT JOIN tbl_cidade USING (cidade)
                            WHERE tbl_revenda.revenda = $xxxrevenda";
            $res1 = pg_query ($con,$xsql);
            //echo "$xsql";
            if (pg_num_rows($res1) > 0) {
                $revenda_nome = pg_fetch_result ($res1,0,nome);
                $revenda_cnpj = pg_fetch_result ($res1,0,cnpj);
                $revenda_fone = pg_fetch_result ($res1,0,fone);
                $revenda_endereco = pg_fetch_result ($res1,0,endereco);
                $revenda_numero = pg_fetch_result ($res1,0,numero);
                $revenda_complemento = pg_fetch_result ($res1,0,complemento);
                $revenda_bairro = pg_fetch_result ($res1,0,bairro);
                $revenda_cep = pg_fetch_result ($res1,0,cep);
                $revenda_cidade = pg_fetch_result ($res1,0,cidade);
                $revenda_estado = pg_fetch_result ($res1,0,estado);
            }
        }
        else if ($login_fabrica == 94 and $posto == 146534) { // HD 758032

            $sql = "SELECT contato_fone_comercial,
                           contato_cep,
                           contato_endereco,
                           contato_numero,
                           contato_complemento,
                           contato_bairro,
                           contato_cidade,
                           contato_estado
                    FROM tbl_posto_fabrica
                    JOIN tbl_posto USING(posto)
                    WHERE fabrica = $login_fabrica
                    AND cnpj = '$revenda_cnpj'";
            $res1 = pg_query ($con,$sql);
            if (pg_num_rows($res1) > 0) {
                $revenda_fone = pg_fetch_result ($res1,0,'contato_fone_comercial');
                $revenda_endereco = pg_fetch_result ($res1,0,'contato_endereco');
                $revenda_numero = pg_fetch_result ($res1,0,'contato_numero');
                $revenda_complemento = pg_fetch_result ($res1,0,'contato_complemento');
                $revenda_bairro = pg_fetch_result ($res1,0,'contato_bairro');
                $revenda_cep = pg_fetch_result ($res1,0,'contato_cep');
                $revenda_cidade = pg_fetch_result ($res1,0,'contato_cidade');
                $revenda_estado = pg_fetch_result ($res1,0,'contato_estado');
            }

        }

        $sql = "SELECT  tbl_familia_valores.taxa_visita,
                        tbl_familia_valores.hora_tecnica,
                        tbl_familia_valores.valor_diaria,
                        tbl_familia_valores.valor_por_km_caminhao,
                        tbl_familia_valores.valor_por_km_carro,
                        tbl_familia_valores.regulagem_peso_padrao,
                        tbl_familia_valores.certificado_conformidade
                FROM    tbl_os
                JOIN    tbl_produto         USING(produto)
                JOIN    tbl_familia_valores USING(familia)
                WHERE   tbl_os.os = $os
                AND     tbl_os.fabrica = $login_fabrica ";
        #echo nl2br($sql);
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {

            if ($cobrar_deslocamento  == 'taxa_visita'){
                $valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
                $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
            }

            if ($cobrar_deslocamento  == 'valor_por_km'){
                $taxa_visita                  = trim(pg_fetch_result($res,0,taxa_visita));
                if ($veiculo == 'carro'){
                    $valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
                }
                if ($veiculo == 'caminhao'){
                    $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
                }
            }

            if ($cobrar_hora_diaria == "diaria"){
                $hora_tecnica             = trim(pg_fetch_result($res,0,hora_tecnica));
            }
            if ($cobrar_hora_diaria == "hora"){
                $valor_diaria             = trim(pg_fetch_result($res,0,valor_diaria));
            }
            if ($cobrar_regulagem != "t"){
                $regulagem_peso_padrao    = trim(pg_fetch_result($res,0,regulagem_peso_padrao));
            }
            if ($cobrar_certificado != "t"){
                $certificado_conformidade = trim(pg_fetch_result($res,0,certificado_conformidade));
            }
        }

        /* HD 46784 */
        $sql = "SELECT  valor_regulagem, valor_certificado
                FROM    tbl_capacidade_valores
                WHERE   fabrica = $login_fabrica
                AND     capacidade_de <= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )
                AND     capacidade_ate >= (SELECT capacidade FROM tbl_os WHERE tbl_os.os = $os AND fabrica = $login_fabrica )";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            if ($cobrar_regulagem != "t"){
                $regulagem_peso_padrao    = trim(pg_fetch_result($res,0,valor_regulagem));
            }
            if ($cobrar_certificado != "t"){
                $certificado_conformidade = trim(pg_fetch_result($res,0,valor_certificado));
            }
        }
    }
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {

    $os                         = $_POST['os'];
    $hd_chamado                 = $_POST['hd_chamado'];
    $sua_os                     = $_POST['sua_os'];
    $data_abertura              = $_POST['data_abertura'];
    $hora_abertura              = $_POST['hora_abertura'];
    $consumidor_nome            = $_POST['consumidor_nome'];
    $consumidor_cpf             = $_POST['consumidor_cpf'];
    $consumidor_cidade          = $_POST['consumidor_cidade'];
    $consumidor_fone            = $_POST['consumidor_fone'];
    $consumidor_email           = $_POST['consumidor_email'];
    $consumidor_celular         = $_POST['consumidor_celular'];//hd 15091
    $consumidor_fone_comercial  = $_POST['consumidor_fone_comercial'];
    $tipo_atendimento           = $_POST['tipo_atendimento'];
    $os_cortesia                = $_POST['os_cortesia'];
    $consumidor_estado          = $_POST['consumidor_estado'];
    //takashi 02-09
    $consumidor_endereco        = $_POST['consumidor_endereco'];
    $consumidor_numero          = $_POST['consumidor_numero'];
    $consumidor_complemento     = $_POST['consumidor_complemento'];
    $consumidor_bairro          = $_POST['consumidor_bairro'];
    $consumidor_cep             = $_POST['consumidor_cep'];
    //takashi 02-09
    $revenda_cnpj               = $_POST['revenda_cnpj'];
    $revenda_nome               = $_POST['revenda_nome'];
    $nota_fiscal                = $_POST['nota_fiscal'];
    $data_nf                    = $_POST['data_nf'];
    $produto_referencia         = $_POST['produto_referencia'];
    $produto_descricao          = $_POST['produto_descricao'];
    $produto_voltagem           = $_POST['produto_voltagem'];
    $produto_serie              = ($login_fabrica == 40)
        ? strtoupper($_POST['produto_serie_ini'])."".str_pad($_POST['produto_serie'],7,"0",STR_PAD_LEFT)
        : $_POST['produto_serie'];
    $qtde_produtos              = $_POST['qtde_produtos'];
    $cor                        = $_POST['cor'];
    $consumidor_revenda         = $_POST['consumidor_revenda'];
    $acessorios                 = $_POST['acessorios'];
    $produto_capacidade         = $_POST['produto_capacidade'];
    $versao                     = $_POST['versao'];
    $divisao                    = $_POST['divisao'];
    $os_posto                   = $_POST['os_posto'];
    $type                       = $_POST['type'];
    $satisfacao                 = $_POST['satisfacao'];
    $laudo_tecnico              = $_POST['laudo_tecnico'];
    $obs                        = $_POST['obs'];
    $quem_abriu_chamado         = $_POST['quem_abriu_chamado'];
    $taxa_visita                = $_POST['taxa_visita'];
    $visita_por_km              = $_POST['visita_por_km'];
    $valor_por_km               = $_POST['valor_por_km'];
    $hora_tecnica               = $_POST['hora_tecnica'];
    $regulagem_peso_padrao      = $_POST['regulagem_peso_padrao'];
    $certificado_conformidade   = $_POST['certificado_conformidade'];
    $valor_diaria               = $_POST['valor_diaria'];
    $deslocamento_km            = $_POST['deslocamento_km'];
    $codigo_posto               = $_POST['codigo_posto'];
    $locacao                    = $_POST['locacao'];
    $preco_produto              = $_POST['preco_produto'];

    if (strlen(trim($_POST['produto_referencia'])) > 0 ) {
        $sql = "SELECT  tbl_familia_valores.taxa_visita,
                        tbl_familia_valores.hora_tecnica,
                        tbl_familia_valores.valor_diaria,
                        tbl_familia_valores.valor_por_km_caminhao,
                        tbl_familia_valores.valor_por_km_carro,
                        tbl_familia_valores.regulagem_peso_padrao,
                        tbl_familia_valores.certificado_conformidade
                FROM    tbl_produto
                JOIN    tbl_familia         USING(familia)
                JOIN    tbl_familia_valores USING(familia)
                WHERE   tbl_produto.referencia = '".trim($_POST['produto_referencia'])."'
                AND     tbl_familia.fabrica = $login_fabrica ";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            $taxa_visita              = trim(pg_fetch_result($res,0,taxa_visita));
            $hora_tecnica             = trim(pg_fetch_result($res,0,hora_tecnica));
            $valor_diaria             = trim(pg_fetch_result($res,0,valor_diaria));
            $valor_por_km_caminhao    = trim(pg_fetch_result($res,0,valor_por_km_caminhao));
            $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
            $regulagem_peso_padrao    = trim(pg_fetch_result($res,0,regulagem_peso_padrao));
            $certificado_conformidade = trim(pg_fetch_result($res,0,certificado_conformidade));
        }

        /* HD 46784 */
        $sql = "SELECT  valor_regulagem, valor_certificado
                FROM    tbl_capacidade_valores
                WHERE   fabrica = $login_fabrica
                AND     capacidade_de <= (SELECT capacidade
                                            FROM tbl_produto
                                            JOIN tbl_linha USING(linha)
                                            WHERE fabrica= $login_fabrica
                                            AND tbl_produto.referencia = '".trim($_POST['produto_referencia'])."'
                                            LIMIT 1)
                AND     capacidade_ate >= (SELECT capacidade
                                            FROM tbl_produto
                                            JOIN tbl_linha USING(linha)
                                            WHERE fabrica= $login_fabrica
                                            AND tbl_produto.referencia = '".trim($_POST['produto_referencia'])."'
                                            LIMIT 1) ";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0) {
            $regulagem_peso_padrao    = number_format(trim(pg_fetch_result($res,0,valor_regulagem)),2,',','.');
            $certificado_conformidade = number_format(trim(pg_fetch_result($res,0,valor_certificado)),2,',','.');
        }
    }

    if(strpos($msg_erro,"data_nf_superior_data_abertura")){
        $msg_erro = "Data da nota fiscal não pode ser maior que a data de abertura";
    }
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";

if($login_fabrica == 51) {
    echo "<br><br><h4><center>A abertura de novas OS da GAMA segue novos critérios. Favor se informar com o fabricante</center></h4>";
    die;
}

// HD-7144987
if ($login_fabrica == 24) {
  if (verifica_posto_bloqueado_os($login_posto)) {
    
    $dados_os = retrona_os_bloqueada_interacao_posto($login_posto);

    if (count($dados_os) > 0) {
      $oss_posto = implode(",", $dados_os);
    }

    echo "<br><br><h3 style='background-color: #ff6161;'><center>A abertura de novas OS's bloqueado, favor responder as interação das OS's pendentes ! OS's: $oss_posto</center></h3>";
    die;
  }
}

//bloaqueado a pedido da suggar em 03/01/2013 - waldir

if (strlen($pre_os)==0){
    $sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
    $res = @pg_query($con,$sql);
    $digita_os = pg_fetch_result ($res,0,0);
    if ($digita_os == 'f' and strlen($hd_chamado)==0) {
        echo "<div class='error'>Sem permissão de acesso.</div>";
		include "rodape.php";
        exit;
    }
}

/* Verifica se a fabrica utiliza Calculo de KM */
// $sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$sql = "SELECT JSON_FIELD('usaCalculoKM',parametros_adicionais) AS calculoKM FROM tbl_fabrica WHERE fabrica = $login_fabrica";

$res = pg_query($con, $sql);

if(pg_num_rows($res)){
    $calculoKM = (bool)pg_fetch_result($res,0,calculoKM);
}else{
    $calculoKM = "f";
}

include "javascript_pesquisas.php";
include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<link href="plugins/leaflet/leaflet.css?<?=date('s');?>" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js?<?=date('s');?>" ></script>
<script src="plugins/leaflet/map.js?<?=date('s');?>" ></script>
<script src="plugins/mapbox/geocoder.js?<?=date('s');?>"></script>
<script src="plugins/mapbox/polyline.js?<?=date('s');?>"></script>

<!-- <link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script> -->
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<style type ="text/css">
.mobile:hover {
  background: #5b5c8d;
}
.mobile:active{
  background: #373865;
}
.mobile{
  display: inline-flex;
  height: 45px;
  width: 190px;
  background: #373865;
  padding: 5px;
  border-radius: 10px;
  cursor: pointer;
}
.google_play{
  margin-left: 10%;
  display: inline-flex;
  height: 45px;
  padding: 5px;
  cursor: pointer;

}
.google_play > a >span{
  color: #373865;
}
.google_play:hover{
  background: #f3f3f3;
}
.mobile > span{
  font-size: 14px;
  float: right;
  margin-top: 14px;
  margin-right: 14px;
  color: #fac814;
}

.btn-danger{
    width: 58px;
    height: 25px;
    color: #ffffff;
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
    background-color: #da4f49;
    background-image: -moz-linear-gradient(top, #ee5f5b, #bd362f);
    background-image: -o-linear-gradient(top, #ee5f5b, #bd362f);
    background-image: linear-gradient(to bottom, #ee5f5b, #bd362f);
    background-repeat: repeat-x;
    border-color: #bd362f #bd362f #802420;
}
.env-code{
  width: 100%;
  border: solid 3px;
  border-color: #373866;
  width: 205px;
  border-radius: 7px;
  margin-top: 10px;
}

.env-img {
 /*   float: left;*/
    max-width: 150px;
    margin-left: 10px;
    margin-top: 10px;
    display: inline-block;
}

.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px 30px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:center;
}
.content h1 {
    color:black;
    font-size: 120%;
}

fieldset.valores {
    border:1px solid #4E4E4E;
}

fieldset.valores , fieldset.valores div{
    padding: 0.2em;
    font-size:10px;
    width:225px;
}

fieldset.valores label {
    float:left;
    width:43%;
    margin-right:0.2em;
    padding-top:0.2em;
    text-align:right;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

fieldset.valores span {
    font-size:11px;
    font-weight:bold;
}

.anexo_cortesia {
    display:none;
}
<?php
if ($login_pais != "BR") { ?>

  span[rel=consumidor_bairro],
  span[rel=consumidor_cpf],
  span[rel=consumidor_cep],
  span[rel=revenda_bairro],
  span[rel=revenda_cep]
  {
    color: black !important;
  }

<?php
}
?>
</style>

<?php if($login_fabrica == 35){ ?>
  <script type="text/javascript">
      $(function(){
        <?php if($_GET['pre_os'] == 't' OR $_POST['pre_os'] == 't'){ ?>
          setTimeout(function(){
            $("[id^='consumidor_']").each(function(i) {
              var identificador = $(this)[0].id;

              var tipo = $('#'+identificador).get(0).nodeName;

              if($("#"+identificador).val().length > 0){
                if(tipo == 'INPUT'){
                  $("#"+identificador).prop('readonly', true);
                }else if(tipo == 'SELECT'){
                  $('#'+ identificador +' option:not(:selected)').prop('disabled', true);
                }

              }
            });
          },1000);
        <?php } ?>
      });
  </script>
<?php }

if(in_array($login_fabrica, array(11,172)) && strlen($msg_erro) == 0){
?>

<style>
  #sb-nav{
    display: none;
  }
</style>

<script>

  function retorno_codigo_interno(fabrica, codigo_interno_digitado = "", produto = ""){

    var login_fabrica = "<?php echo $login_fabrica; ?>";

    <?php
    $self = $_SERVER['PHP_SELF'];
    $self = explode("/", $self);
    unset($self[count($self)-1]);
    $page = implode("/", $self);
    $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";

    $pre_os     = $_GET["pre_os"];
    $hd_chamado = $_GET["hd_chamado"];

    if(strlen($pre_os) > 0){
      $param_pre_os = "|{$pre_os}|{$hd_chamado}";
    }

    $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?codigo_interno_os=sim{$param_pre_os}";
    ?>

    var redireciona = "";
	var hd_chamado =  "<?=$hd_chamado?>";
    if(parseInt(login_fabrica) == parseInt(fabrica)){

      redireciona = "<?=$pageReturn?>_"+codigo_interno_digitado+"_"+produto;

    }else{

      var pageReturn = "<?=$pageReturn?>";

      var params = "cook_admin=&cook_fabrica="+fabrica+"&page_return="+pageReturn+"_"+codigo_interno_digitado+"_"+produto+"&hd_chamado="+hd_chamado;

      redireciona = "<?=$page?>?"+params;

    }

    window.location = redireciona;

  }

  function codigo_interno(){

      var codigo_interno_os = "<?php echo $codigo_interno_os; ?>";

      if(codigo_interno_os == "sim"){
        return;
      }

      Shadowbox.open({
          content: "verifica_codigo_interno.php?hd_chamado=<?php echo $_GET["hd_chamado"]; ?>",
          player: "iframe",
          title: "Inserir o Modelo do Produto",
          width: 660,
          height: 360,
          options: {
              modal: true,
              enableKeys: false
          }
      });

  }

  $(document).on("ShadowboxInit", function() {
     codigo_interno();
  });

</script>

<?php } ?>

<?php
if($login_fabrica == 114){
?>
  <script type="text/javascript">
        $(function(){

            <?
                if(strlen($msg_erro) AND $com_deslocamento <> 1){
            ?>
                    $("#mostra_tipo_atendimento").hide();
            <?
                }
            ?>

            var produto_referencia = '';
            var produto_descricao = '';
            var login_fabrica = '<?=$login_fabrica;?>';

            $("#produto_referencia, #produto_descricao").blur(function(){

              if ($('#produto_referencia').length > 0){
                produto_referencia = $('#produto_referencia').val();
              }
              if ($('#produto_descricao').length > 0){
                produto_descricao = $('#produto_descricao').val();
              }

                $.ajax({
                    type: "GET",
                    url: "admin/ajax_deslocamento_linha.php",
                    data: {"produto_referencia":produto_referencia,"login_fabrica":login_fabrica},
                    cache: false,
                    success: function(data){
                        if(data == 1){
                          $("#mostra_tipo_atendimento").show();
                        }else{
                          $("#mostra_tipo_atendimento").hide();
                        }
                    }
                });
            });
        });
        </script>
    <?php } ?>


<?php

if($login_fabrica == 86) {
?>
    <script type="text/javascript">
        $(function(){
            $('#nota_fiscal').numeric();
        });
    </script>
<?php
}
?>

<?php

  $cod_pais = array(
    "AR" => 54,
    "BO" => 591,
    "BR" => 55,
    "CL" => 56,
    "CO" => 57,
    "CR" => 506,
    "CU" => 53,
    "EC" => 593,
    "SV" => 503,
    "GT" => 502,
    "HN" => 504,
    "MX" => 52,
    "NI" => 505,
    "PA" => 507,
    "PY" => 595,
    "PE" => 51,
    "DO" => 1849,
    "UY" => 598,
    "VE" => 58
  );

  $sql_pais_posto = "SELECT contato_pais FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
  $res_pais_posto = pg_query($con, $sql_pais_posto);

  if(pg_num_rows($res_pais_posto) > 0){
    $pais_posto = pg_fetch_result($res_pais_posto, 0, 'contato_pais');

    if(array_key_exists($pais_posto, $cod_pais)){
      $prefixo = $cod_pais[$pais_posto];
    }
  }

  if(!empty($prefixo)){
?>

  <script type="text/javascript" src="admin/js/phoneparser.js"></script>

  <script type="text/javascript">

  function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla>47 && tecla<58))
      return true;
    else{
      if (tecla==8 || tecla==0)
        return true;
      else
        return false;
    }
  }

  $(function(){

    <?php if($login_fabrica == 24){ ?>
    $("#numero_serie").blur(function(){
        var serie       = $("#numero_serie").val();
        var referencia  = $("#referencia_produto").val();
        $.ajax({
            type: "POST",
            datatype: 'json',
            url: "verifica_produto_serie.php",
            data: {verifica_produto_serie:true, referencia: referencia, serie:serie},
            success: function(retorno){
                var dados = $.parseJSON(retorno);
                if(dados.retorno == 'erro'){
                  $("#msg_erro_produto_serie").css('color', '#ff0000')
                  $("#msg_erro_produto_serie").text('Número de série bloqueado, entrar em contato com a fábrica.');
                }

            }
        });
    });
    <?php } ?>

    //HD-6874141
    /*$("#consumidor_bairro").blur(function() {
      $("#consumidor_email").focus();
    });
    $("#revenda_bairro").blur(function() {
      $("input[name='aparencia_produto']").focus();
    });*/
    var cel = "";
    <?php
    if ($login_pais == "BR") {
    ?>
      $("#consumidor_celular").blur(function() {

        if($(this).val() == "" || $(this).val() == cel){
          return;
        }

        let celularPuro = $(this).val().replace(/[^0-9]/g,'')

        cel = "<?php echo $prefixo?>"+celularPuro;
        
        var res = parsePhone($.trim(cel));
        if(JSON.stringify(res) == "null"){

          $(this).focus();
          alert("Número de Celular Inválido.");
          $(this).val('');
          return;

        }else if(res.countryCode != "<?php echo $prefixo; ?>" || res.countryISOCode != "<?php echo $pais_posto; ?>"){

          $(this).focus();
          alert("Número de Celular Inválido. Por favor verifique!");
          $(this).val('');
          return;

        }
      });
    <?php
    }
    ?>
  });
  </script>

<?php

}

if($login_fabrica != 131) {
?>
    <script type="text/javascript">
        $(function(){
            $('#data_fabricacao').mask("99/99/9999");
        });
    </script>
<?php
}

if($login_fabrica == 131) {
?>
    <script type="text/javascript">
        $(function(){
            $('#data_fabricacao').mask("99/9999");
        });
    </script>
<?php
}
?>
<script type="text/javascript">

    var login_fabrica = <?php echo $login_fabrica; ?>;
    var verificador_funcao = 0;

    <?php if (isFabrica(19)): ?>
    function tipo_atendimento_produto(linha) {
        var qtde = $("input[name=qtde_produtos]").val() || '1';
        qtde = parseInt(qtde);
        linha = linha || $("#linha_id").val();
        console.log(linha);
        if (linha.length == 0 || typeof linha == 'undefined') {
            return;
        }

        if (linha == "928") {
            $("#tipo_atendimento>option").attr('disabled', true);
            $("#tipo_atendimento>option[rel=LS],#tipo_atendimento>option[rel=all]").removeAttr('disabled');
        } else {
            $("#tipo_atendimento>option").attr('disabled', true);
            $("#tipo_atendimento>option[rel!=LS]").removeAttr('disabled');
        }

        if (qtde == 1) {
            $('#tipo_atendimento option').filter(function(i,e) {
                return /^\s?15/.test(e.innerText);
                }).removeAttr('disabled', true);
        }
        if (qtde > 1) {
            $('#tipo_atendimento option').filter(function(i,e) {
                return /^\s?16/.test(e.innerText);
                }).removeAttr('disabled', true);
        }
    }
    <?php endif; ?>
    $(document).ready(function(){

        <?php
        if ($login_pais != "BR") { ?>

          $("#consumidor_estado").removeClass("addressState");
          $("#consumidor_cep").removeClass("addressZip");

          $("#revenda_estado").removeClass("addressState_rev");
          $("#revenda_cep").removeClass("addressZip_rev");

          $("#consumidor_estado, #revenda_estado").change(function(){

            let idElemento = $(this).attr("id");

            let revendaConsumidor;
            if (idElemento == "consumidor_estado") {
              revendaConsumidor = "consumidor";
            } else {
              revendaConsumidor = "revenda";
            }

            $.ajax({
              async: false,
              url: window.location,
              type: "POST",
              data: { ajax_busca_cidade: true, estado: $(this).val(), pais: '<?= $login_pais ?>' },
              beforeSend: function() {
                  if ($("#"+revendaConsumidor+"_cidade").next("img").length == 0) {
                      $("#"+revendaConsumidor+"_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                  }
              },
              complete: function(data) {
                  data = $.parseJSON(data.responseText);

                  $("#"+revendaConsumidor+"_cidade option").remove();

                  if (data.error) {
                      alert(data.error);
                  } else {

                      $("#"+revendaConsumidor+"_cidade").append($("<option></option>", {
                        value: "",
                        text: "<?= traduz("Selecione") ?>"
                      }));

                      $.each(data.cidades, function(key, value) {

                          var option = $("<option></option>", { value: value, text: value});

                          $("#"+revendaConsumidor+"_cidade").append(option);

                      });
                  }

                  $("#"+revendaConsumidor+"_cidade").show().next().remove();
              }
            });
          });
          

        <?php
        } ?>

        Shadowbox.init();

        <?php
        if ($login_pais == "BR") {
        ?>
          $('#consumidor_cep, #revenda_cep').mask("99.999-999"); $("#revenda_cnpj").mask("99.999.999/9999-99"); //$('#consumidor_cep').data('mask').remove();
        <?php
        }
        ?>
        $("#data_abertura").datepick();
        $("#data_nf").datepick();

        <?php if ($login_fabrica == 74): ?>
        $("#data_nascimento").datepick();
        <?php endif ?>

        <?php if($login_fabrica == 74){ ?>
            $("#consumidor_cpf").blur(function(){
              var referencia = $("#produto_referencia").val();
              var cpf = $("#consumidor_cpf").val();
              var hd_chamado = $("#hd_chamado").val();
              if(hd_chamado.length > 0 ){
                return false
              }
              $.ajax({
                  type: "POST",
                  datatype: 'json',
                  url: "os_cadastro_tudo.php",
                  data: {verifica_pre_os_atlas:true, referencia: referencia, cpf: cpf},
                  success: function(retorno){
                      var dados = $.parseJSON(retorno);
                      if(dados.retorno == true){
                          var r = confirm("Já existe uma pré-os aberta pelo Call-Center para atender esse Produto e CPF.\nDeseja confinuar o atendimento? ");
                          if (r == true) {
                              window.location="os_cadastro.php?pre_os=t&hd_chamado="+dados.hd_chamado;
                          }
                      }
                  }
              });
            });
        <?php } ?>


        var familia = $('#familia').val();
        if (familia == 2467 || familia == 2464 || familia == 2466){
            $('#unidade_cor').show('slow');
        }else{
            $('#unidade_cor').hide('slow');
            $('#familia').val('');
        }

        <?php if ($login_fabrica == 74 && !empty($_POST['consumidor_cep'])) : ?>
            //hd_chamado=3141903
            //buscaCEP( $("#consumidor_cep").val(), document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado);

        <?php endif; ?>

        $("input[name=preco_produto]").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength: 15});

        <?php if($login_fabrica == 72){ ?>
                $("input[name=produto_serie]").attr("readonly",true);
        <?php } ?>

<?php
        if($login_fabrica == 15 || $login_fabrica == 24){
?>
            function formatItem(row) {
                return row[1];
            }

            /* Busca pelo Nome */
            $("input[name=revenda_nome]").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
                minChars: 3,
                delay: 150,
                width: 350,
                matchContains: true,
                formatItem: formatItem,
                formatResult: function(row) {return row[1];}
            });

            $("input[name=revenda_nome]").result(function(event, data, formatted) {
    $("input[name=revenda_cnpj]").val(data[0]) ;
                $('#revenda_cnpj_raiz').val(data[2]);
            });

            $("input[name=revenda_cnpj_raiz]").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=cnpj'; ?>", {
                minChars: 3,
                delay: 150,
                width: 350,
                matchContains: true,
                formatItem: formatItem,
                formatResult: function(row) {return row[1];}
            });

            $("input[name=revenda_cnpj_raiz]").result(function(event, data, formatted) {
                $("input[name=revenda_cnpj]").val(data[0]) ;
                $("input[name=revenda_nome]").val(data[1]);
                $("input[name=revenda_cnpj_raiz]").val(data[2]);
            });

<?
        }
?>

        <? if($login_fabrica == 3 AND (strlen($msg_erro) == 0 or (strlen($msg_erro) > 0 and strlen($serie) == 0))){ ?>
            fnc_bloqueia_campo("revenda_cnpj_pesquisa");
            $('#consumidor_nome').attr('readonly', true);
            $('#consumidor_cpf').attr('readonly', true);
            $('#consumidor_cidade').attr('readonly', true);
            $('#consumidor_fone').attr('readonly', true);
            $('input[name=consumidor_celular]').attr('readonly', true);
            $('input[name=consumidor_fone_comercial]').attr('readonly', true);
            $('#consumidor_endereco').attr('readonly', true);
            $('#consumidor_numero').attr('readonly', true);
            $('#consumidor_complemento').attr('readonly', true);
            $('#consumidor_bairro').attr('readonly', true);
            $('#consumidor_cep').attr('readonly', true);
            $('#nota_fiscal').attr('readonly', true);
            $('#data_nf').attr('readonly', true);
            $('#consumidor_email').attr('readonly', true);
            $('#produto_referencia').attr('readonly', true);
            $('#produto_descricao').attr('readonly', true);
            $('#produto_voltagem').attr('readonly', true);
        <? } ?>

        if( $("#garantia_estendida").is(":checked") ){
            $("#op_garantia_estendida").show();
        }else{
            $("#op_garantia_estendida").hide();
            $("#nf_garantia_estendida").hide();
        }

        if( $("input[name=opcao_garantia_estendida]:checked").val() == "t" ){
            $("#nf_garantia_estendida").show();
        }else{
            $("#nf_garantia_estendida").hide();
        }

        <? if($login_fabrica == 7){?>
        if($('#hd_chamado').val().length > 0 ){
            $('#table_abriu_chamado').attr('display','');
        }else{
            $('#table_abriu_chamado').hide();
        }
        <?}?>
        $("#garantia_estendida").click(function(){
            if( $("#garantia_estendida").is(":checked") ){
                $("#op_garantia_estendida").show();
            }else{
                $("#op_garantia_estendida").hide();
                $("#nf_garantia_estendida").hide();
                $("input[name=opcao_garantia_estendida]").each(function () {
                    $(this).removeAttr("checked");
                });
            }
        });

        $("input[name=opcao_garantia_estendida]").click(function(){
            var opcao_garantia_estendida = $(this).val();
            if( opcao_garantia_estendida == "t" ){
                $("#nf_garantia_estendida").show();
            }else{
                $("#nf_garantia_estendida").hide();
            }
        });

        <?php
        if (in_array($login_fabrica, [19])) { ?>
          $("#tipo_atendimento").change(function(){

            if ($(this).val() == "339") {
              $("#anexo_certificado").show();
              $("span[rel=consumidor_email], span[rel=numero_serie]").css({color: "red"});
            } else {
              $("#anexo_certificado").hide();
              $("#garantia_lorenzetti").val("");
              $("span[rel=consumidor_email], span[rel=numero_serie]").css({color: "black"});
            }

          });

          $("#tipo_atendimento").change();
        <?php
        }
        ?>

    });

    function valida_garantias_adicionais() {

        let referencia_produto = $("#produto_referencia").val();

        if ($("#tipo_atendimento").val() == "339") {

          $.ajax({
              async: true,
              type: 'POST',
              dataType:"JSON",
              url: location.href,
              data: {
                  ajax_verifica_garantia_adicional : true,
                  referencia_produto : referencia_produto
              },
          }).done(function(data) {

              if(data.retorno){

                  var garantia1 = data.garantia1;
                  var garantia2 = data.garantia2;
                  var garantia3 = data.garantia3;

                  Shadowbox.open({
                    content :   "questionario_garantia.php?garantia1="+garantia1+"&garantia2="+garantia2+"&garantia3="+garantia3,
                    player  :   "iframe",
                    title   :   "",
                    width   :   500,
                    height  :   300,
                    options: {
                          modal: true,
                          enableKeys: false
                   }
                });

              } else {

                  $("input[name=btn_acao]").val("continuar");

                  page_loading();

                  $("form[name=frm_os]").submit();

              }

          });

        } else {

          $("input[name=btn_acao]").val("continuar");

          page_loading();

          $("form[name=frm_os]").submit();

        }

    }

    function libera_tipo_atendimento_garantia(referencia_prod) {

      if (referencia_prod != "" && referencia_prod != undefined) {
        $.ajax({
              async: true,
              type: 'POST',
              dataType:"JSON",
              url: location.href,
              data: {
                  ajax_verifica_garantia_adicional : true,
                  referencia_produto : referencia_prod
              },
        }).done(function(data) {

          if (data.retorno) {

            if (data.garantia2 != "") {

              $("#tipo_atendimento > option[value=339]").prop("disabled", false);

            } else {

              $("#tipo_atendimento > option[value=339]").prop("disabled", true);

            }

          } else {
            $("#tipo_atendimento > option[value=339]").prop("disabled", true);
          }

        });
      }

    }

    function page_loading() {

        $.blockUI();

    }

    <?php if ($login_fabrica == 74) : // HD 854585 - Bloqueio da edição da cidade/estado caso o cep encontre valores, ver ajax_cep_new.js ?>
        function submitComboEstado() {

            if ($("#consumidor_estado").is(':disabled') ) {
                $("#consumidor_estado").removeAttr('disabled');

                $(this).val( $("#consumidor_estado").attr('rel') );

            }

        }
    <?php endif; ?>

    function pesquisaProduto(campo, tipo){
        var campo   = jQuery.trim(campo.value);
        var tipo_atendimento = $("#tipo_atendimento").val();

        if (campo.length > 2){
            Shadowbox.open({
                content :   "produto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo_atendimento="+tipo_atendimento+"&exibe=os_cadastro.php",
                player  :   "iframe",
                title   :   "<?php traduz('pesquisa.de.produto', $con, $cook_idioma);?>",
                width   :   800,
                height  :   500
            });
        }else
            alert("<?php echo traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
    }

    function verifica_digita_os_posto(linha){

      var num_linha = linha;

      $.ajax({
          type: "GET",
          datatype: 'json',
          url: "os_cadastro_tudo.php?verifica_digita_os_posto=true&linha="+num_linha,
          cache: false,
          success: function(retorno){
              var dados = $.parseJSON(retorno);
              if(dados.resultado == 'erro'){
                alert('Esse posto não é autorizado a abrir O.S dessa linha.');
              }
             /* if(dados.deslocamento == 't'){
                  $("#div_mapa").css({"display": "block"});
              }*/
          }
      });
    }

    function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao,numero_serie_obrigatorio,oem){


        gravaDados("produto_referencia",referencia);
        $('#produto_referencia').blur();

        gravaDados("produto_descricao",descricao);
        gravaDados("produto_voltagem",voltagem);

        <?php
        if ($login_fabrica == 114) {
        ?>
          if (numero_serie_obrigatorio == "t") {
            $("span[rel=produto_serie]").css({ color: "#A80000" });
          } else {
            $("span[rel=produto_serie]").css({ color: "#000" });
          }
          if(oem == "t"){
            $('#selo_obrigatorio').val("t");
            $(".box-upload").show('slow');
          }else{
            $('#selo_obrigatorio').val("f");
            $(".box-upload").hide('slow');
          }
        <?php
        }
        ?>
        <?php if($login_fabrica == 50){ //HD-3331834 ?>
          mostra_def();
        <?php } ?>

        <?php if (isFabrica(19)): ?>
        $("#produto_referencia").data('linha', linha);
        $("#linha_id").val(linha);
        tipo_atendimento_produto(linha);
        libera_tipo_atendimento_garantia(referencia);
        <?php endif; ?>

        <?php if ($login_fabrica == 74) {?>
            verifica_digita_os_posto(linha);
            $("#linha_id").val(linha);
       <?php } ?>
    }

    function retorna_peca(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email,tipo_revenda){
        if (tipo_revenda == 'revenda_nf'){

            gravaDados("txt_revenda_nome",nome);
            gravaDados("txt_revenda_cnpj",cnpj);
            gravaDados("txt_revenda_fone",fone);
            gravaDados("txt_revenda_email",email);
            gravaDados("txt_revenda_cidade",nome_cidade);
            gravaDados("txt_revenda_estado",estado);
            gravaDados("txt_revenda_endereco",endereco);
            gravaDados("txt_revenda_cep",cep);
            gravaDados("txt_revenda_numero",numero);
            gravaDados("txt_revenda_complemento",complemento);
            gravaDados("txt_revenda_bairro",bairro);

        }else{

            gravaDados("revenda_nome",nome);
            gravaDados("revenda_cnpj",cnpj);
            gravaDados("revenda_fone",fone);
            gravaDados("revenda_email",email);
            gravaDados("revenda_cidade",nome_cidade);
            gravaDados("revenda_estado",estado);
            gravaDados("revenda_endereco",endereco);
            gravaDados("revenda_cep",cep);
            gravaDados("revenda_numero",numero);
            gravaDados("revenda_complemento",complemento);
            gravaDados("revenda_bairro",bairro);
        }

        monta_cidade(cnpj);
        <?php if($login_fabrica == 35){ ?>
            VerificaBloqueioRevenda(cnpj, <?=$login_fabrica?>);
        <?php } ?>
    }

    function monta_cidade(cnpj){//2914204
      if(cnpj.length > 0){
          $.ajax({
              url: "<?php echo $_SERVER['PHP_SELF']; ?>?monta_cidade=sim&cnpj_revenda="+cnpj,
              cache: false,
              success: function(data) {
                  retorno = data;
                  $("#revenda_cidade").html(retorno);
              }
          });
      }
    }

    function pesquisaSerie(campo,produto){
        var campo   = jQuery.trim(campo.value);
        var produto = jQuery.trim(produto.value);

        if(produto.length > 0){
            if (campo.length > 2){
                Shadowbox.open({
                    content :   "produto_serie_pesquisa_famastil.php?serie="+campo+"&produto="+produto,
                    player  :   "iframe",
                    title   :   "<?php fecho('pesquisa.de.numero.serie', $con, $cook_idioma);?>",
                    width   :   800,
                    height  :   500
                });
            }else{
                alert("<?php fecho('informar.toda.parte.informacao.para.realizar.pesquisa', $con, $cook_idioma);?>");
            }
        }else{
            alert("<?php fecho('informar.produto.para.realizar.pesquisa', $con, $cook_idioma);?>");
        }
    }

    function retorna_consumidor_garantia(serie,nome,doc,fone,email,cidade,estado,compra){
        gravaDados("produto_serie",serie);
        gravaDados("consumidor_nome",nome);
        gravaDados("consumidor_cpf",doc);
        gravaDados("consumidor_fone",fone);
        gravaDados("consumidor_email",email);
        gravaDados("consumidor_cidade",cidade);
        gravaDados("consumidor_estado",estado);
        gravaDados("data_nf",compra);
    }

    function pesquisaRevenda(campo,tipo,tipo_revenda){
        var campo = campo.value;

        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
                content:    "pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo+"&tipo_revenda="+tipo_revenda,
                player: "iframe",
                title:      "<?=traduz('Pesquisa Revenda')?>",
                width:  800,
                height: 500
            });
        }else
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
    }

    function gravaDados(name, valor){
        if(name == 'revenda_estado'){
            $("#revenda_estado").val(valor);
              return false;
        }

        if(name == 'consumidor_estado'){
            $("#consumidor_estado").val(valor);
            return false;
        }

        try {
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }

    <?php if (in_array($login_fabrica, [35])) { // HD 692399 ?>

        $().ready(function(){

            $("#produto_referencia, #produto_descricao").blur(function(){

                referencia  = $("#produto_referencia").val();
                descricao   = $("#produto_descricao").val();

                $.get( 'os_cadastro_tudo_ajax_deslocamento.php?deslocamento=t&referencia=' + referencia + '&descricao=' + descricao, function(data){

                    if (data === 't') {

                        $("#mostra_tipo_atendimento").show();

                    }
                    else {

                        $("#mostra_tipo_atendimento").hide();

                        $("#tipo_atendimento").val('');

                        var div_mapa_visibility = $("#div_mapa").css('visibility');
                        var div_mapa_msg_visibility = document.getElementById('div_mapa_msg').style.visibility;

                        if (div_mapa_visibility == "visible") {
                            $("#div_mapa").css('visibility', 'hidden');
                            $("#div_mapa").css('position', 'absolute');
                        }

                        if (div_mapa_msg_visibility == "visible") {
                            document.getElementById('div_mapa_msg').style.visibility = "hidden";
                        }

                    }

                });

            });

        });

    <?php } else if (in_array($login_fabrica, 24)) { ?>

      $().ready(function () {
          if ($('#tipo_atendimento').val() == 87) {
            $("#mostra_tipo_atendimento").show();
            $('#div_mapa').css("display", "block");
        }
      });
      
    <?php } ?>

    //HD 121247
    function valida_consumidor_gama(){
        var pendentes = "Os Campo(s) ";
        var validados = 0;
        var consumidor_nome_x = document.getElementById('consumidor_nome').value;
        var consumidor_fone_x = document.getElementById("consumidor_fone").value;
        var consumidor_cep_x = document.getElementById("consumidor_cep").value;
        var consumidor_endereco_x = document.getElementById('consumidor_endereco').value;
        var consumidor_numero_x = document.getElementById('consumidor_numero').value;
        var consumidor_bairro_x = document.getElementById('consumidor_bairro').value;
        var consumidor_cidade_x = document.getElementById('consumidor_cidade').value;
        var consumidor_estado_x = document.getElementById('consumidor_estado').value;

        if(consumidor_nome_x.length<=0){
            pendentes += "Nome, ";
            validados = validados +1;
        }
        if(consumidor_fone_x.length<=0){
            pendentes += "Fone, ";
            validados = validados +1;
        }
        if(consumidor_cep_x.length<=0){
            pendentes += "CEP, ";
            validados = validados +1;
        }
        if(consumidor_endereco_x.length<=0){
            pendentes += "Endereço, ";
        }
        if(consumidor_numero_x.length<=0){
            pendentes += "Numero, ";
            validados = validados +1;
        }
        if(consumidor_bairro_x.length<=0){
            pendentes += "Bairro, ";
            validados = validados +1;
        }
        if(consumidor_cidade_x.length<=0){
            pendentes += "Cidade, ";
        }
        if(consumidor_estado_x.length<=0){
            pendentes += "Estado, ";
            validados = validados +1;
        }
        pendentes += "do Consumidor são OBRIGATÓRIOS!";

        if (validados == 0){
            if (document.frm_os.btn_acao.value == '' ) {
                    document.frm_os.btn_acao.value='continuar' ;
                    document.frm_os.submit()
            }else{
                    alert ('Aguarde submissão')
            }
        }else{
            alert(pendentes);
        }
        validados = 0;
        pendentes = "";
    }

    function atualizaValorKM(campo){
        if (campo.value == 'carro'){
            $('input[name=valor_por_km]').val( $('input[name=valor_por_km_carro]').val() );
        }
        if (campo.value == 'caminhao'){
            $('input[name=valor_por_km]').val( $('input[name=valor_por_km_caminhao]').val() );
        }
    }

    //HD 275256
    function verifica_familia_atendimento(){

        var fabrica      = '<?=$login_fabrica?>';
        var referencia   = $("#produto_referencia").val();
        if (referencia.length > 0){
            if (fabrica == '40')
            {
                $.ajax({
                    type: "GET",
                    url: "<?=$PHP_SELF?>",
                    data: 'produto_referencia_familia='+referencia+'&verifica_familia=sim',
                    success: function(data){
                        var familia = data;

                        if (familia == 2467 || familia == 2464 || familia == 2466)
                        {
                            $('#unidade_cor').show('slow');
                            $('#familia').val(familia);
                        }
                        else
                        {
                            $('#unidade_cor').hide('slow');
                            $('#familia').val('');
                        }
                    }
                });
            }
            else
            {
                $.ajax({
                    type: "GET",
                    url: "<?=$PHP_SELF?>",
                    data: 'produto_referencia_familia='+referencia+'&verifica_familia=sim',
                    complete: function(http) {
                        results = http.responseText;
                        if (results != ''){
                            $('#tipo_atendimento').html(results);

                        }
                    }
                });
            }

        }
        else
        {
            if (fabrica == 40)
            {
                $('#unidade_cor').hide('slow');
                $('#familia').val('');
            }
        }

    }

    function atualizaCobraHoraDiaria(campo){
        if (campo.value == 'isento'){
            $('div[name=div_hora]').css('display','none');
            $('div[name=div_diaria]').css('display','none');
            $('div[name=div_desconto_hora_diaria]').css('display','none');
            $('input[name=hora_tecnica]').attr('disabled','disabled');
            $('input[name=valor_diaria]').attr('disabled','disabled');
        }
        if (campo.value == 'hora'){
            $('div[name=div_hora]').css('display','');
            $('div[name=div_diaria]').css('display','none');
            $('div[name=div_desconto_hora_diaria]').css('display','');
            $('#hora_tecnica').removeAttr("disabled")
            $('#valor_diaria').attr('disabled','disabled');
        }
        if (campo.value == 'diaria'){
            $('div[name=div_hora]').css('display','none');
            $('div[name=div_diaria]').css('display','');
            $('div[name=div_desconto_hora_diaria]').css('display','');
            $('#hora_tecnica').attr('disabled','disabled');
            $('#valor_diaria').removeAttr("disabled")
        }
    }

    function atualizaCobraDeslocamento(campo){
        if (campo.value == 'isento'){
            $('div[name=div_valor_por_km]').css('display','none');
            $('div[name=div_taxa_visita]').css('display','none');
            $('div[name=div_desconto_deslocamento]').css('display','none');
            $('input[name=valor_por_km]').attr('disabled','disabled');
            $('input[name=taxa_visita]').attr('disabled','disabled');
        }
        if (campo.value == 'valor_por_km'){
            $('div[name=div_valor_por_km]').css('display','');
            $('div[name=div_taxa_visita]').css('display','none');
            $('div[name=div_desconto_deslocamento]').css('display','');
            $('input[name=valor_por_km]').removeAttr("disabled")
            $('input[name=taxa_visita]').attr('disabled','disabled');

            $('input[name=veiculo]').each(function (){
                if (this.checked){
                    atualizaValorKM(this);
                }
            });
        }
        if (campo.value == 'taxa_visita'){
            $('div[name=div_valor_por_km]').css('display','none');
            $('div[name=div_taxa_visita]').css('display','');
            $('div[name=div_desconto_deslocamento]').css('display','');
            $('input[name=valor_por_km]').attr('disabled','disabled');
            $('input[name=taxa_visita]').removeAttr("disabled")
        }
    }

    function fnc_pesquisa_produto_serie (campo,form) {
        if (campo.value != "") {
            var url = "";
            url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.focus();
        }
    }

    function fnc_pesquisa_produto_modelo (campo,form) {
        if (campo.value != "") {
            var url = "";
            url = "produto_pesquisa_modelo.php?campo=" + campo.value + "&form=" + form ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.focus();
        }
    }

    function fnc_pesquisa_serie_atlas (serie, referencia, descricao) {
        if (serie.value != "") {
            var url = "";
            url = "produto_pesquisa_new_atlas.php?serie=" + serie.value + "&form=frm_os";
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.serie   = serie;
            janela.referencia   = referencia;
            janela.descricao    = descricao;
            janela.focus();
        }else{
            alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
            return false;

        }
    }

<?    if ($login_fabrica == 56) {    ?>
    function fnc_pesquisa_produto_serie56 (campo,form) {
        if (campo.value != "") {
            var url = "";
            url = "produto_serie_pesquisa56.php?campo=" + campo.value + "&form=" + form ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.focus();
        }
    }
<?    }   ?>

    function busca_valores(){
        referencia   = $("input[name='produto_referencia']").val();

        if (referencia.length > 0) {
            var curDateTime = new Date();
            http5[curDateTime] = createRequestObject();

            url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+referencia+'&data='+curDateTime;
            http5[curDateTime].open('get',url);

            http5[curDateTime].onreadystatechange = function(){
                if (http5[curDateTime].readyState == 4){
                    if (http5[curDateTime].status == 200 || http4[curDateTime].status == 304){
                        var results = http5[curDateTime].responseText.split("|");
                        if (results[0] == 'ok') {
                            $('input[name=taxa_visita]').val(results[1]);
                            $('#taxa_visita').html(results[1]);
                            $('input[name=hora_tecnica]').val(results[2]);
                            $('#hora_tecnica').html(results[2]);
                            $('input[name=valor_diaria]').val(results[3]);
                            $('#valor_diaria').html(results[3]);
                            $('input[name=valor_por_km_carro]').val(results[4]);
                            $('#valor_por_km_carro').html('R$ '+results[4]);
                            $('input[name=valor_por_km_caminhao]').val(results[5]);
                            $('#valor_por_km_caminhao').html('R$ '+results[5]);
                            $('input[name=regulagem_peso_padrao]').val(results[6]);
                            $('#regulagem_peso_padrao').html(results[6]);
                            $('input[name=certificado_conformidade]').val(results[7]);
                            $('#certificado_conformidade').html(results[7]);

                            $('input[name=veiculo]').each(function (){
                                if (this.checked){
                                    atualizaValorKM(this);
                                }
                            });
                        }
                    }
                }
            }
            http5[curDateTime].send(null);
        }
    }

    $(document).ready(function(){
        <?php
        if ($login_pais == "BR") { ?>
          $(".fone_c").mask("(00) 0000-00009");
        <?php
        }
        
        if ($login_fabrica == 123 && $login_pais == "BR") { ?>
                $("#consumidor_fone").mask("(00) 0000-0000")
                $("#consumidor_celular").mask("(00) 0000-00009")
        <?php } ?>
        $("input[rel='data']").mask("99/99/9999");
        $("input[rel='data_recebimento_produto']").mask("99/99/9999")<?php if($login_fabrica == 101){ echo ".datepick();"; } ?>;
        $("input[rel='hora']").mask("99:99");
        //$("input[rel='fone']").mask("(99) 9999-9999");
        $("input[rel='cnpj']").mask("99.999.999/9999-99");
        $("input[rel='coa']").mask("*****-*****-*****-*****-*****"); //HD 73930 18/02/2009

        <?php if (in_array($login_fabrica,array(7,43, 24, 46,50,88, 89, 74, 99, 101,114, 115,116,117,120,201,123,124,125,126,127,128,129,131,134,136,$fabrica_pre_os)) ) {  // +89 HD 320189  ?>
          if ($('input[name=hd_chamado]').val() != '' && $('input[name=pre_os]').val()=='t') verificaPreOS();
        <?}?>
    });

    function verificaValorPorKm(campo){
        if (campo.checked){
            $('div[name=div_valor_por_km]').css('display','');
            $('div[name=div_taxa_visita]').css('display','none');
            $('input[name=taxa_visita]').attr("disabled", true);
        }else{
            $('div[name=div_valor_por_km]').css('display','none');
            $('div[name=div_taxa_visita]').css('display','');
            $('input[name=taxa_visita]').removeAttr("disabled");
        }
        $("input[name='veiculo']").each( function (){
            if (this.checked){
                atualizaValorKM( this );
            }
        });
    }

//valida numero p/ nota fiscal - esmaltec hd 20685
function mascara(o,f){
    v_obj=o
    v_fun=f
    setTimeout("execmascara()",1)
}

function execmascara(){
    v_obj.value=v_fun(v_obj.value)
}

function soNumeros(campo){
    return campo.replace(/\D/g,"")
}
//-----------------------------

// valida numero de serie
function mostraEsconde(){
    $("div[rel=div_ajuda]").toggle();
}

function verificaPreOS(){

    var numero_serie = '';
    if ($('#produto_serie').length > 0) numero_serie = $('#produto_serie').val();
    chamado      = $('input[name=hd_chamado]').val();
    chamado_item = $('input[name=hd_chamado_item]').val();

    if (numero_serie.length > 0 || chamado.length>0) {

        $.ajax({
            url: "<?=$PHP_SELF?>?ajax=true&buscaPreOS=true&serie="+numero_serie.replace('#','%23')+'&hd_chamado=' +chamado+'&hd_chamado_item='+chamado_item,
            cache: false,
            success: function(retorno){
                var results = retorno.split("|");

                if ($.trim(results[0]).indexOf('ok') > -1) {
                    for (i=1; i < results.length; i++){
                        var arrayy = results[i].split("##");

                        if(login_fabrica == 7){
                            if(arrayy[0] == 'tipo_atendimento' ){
                                if(arrayy[1] != ""){
                                    $('#'+arrayy[0]).val (arrayy[1]);
                                }
                            }else{
                                $('#'+arrayy[0]).val (arrayy[1]);
                            }
                        }else{
                            if($('#'+arrayy[0]).val() == ""){
                                $('#'+arrayy[0]).val (arrayy[1]);
                            }

                            if (arrayy[0] == "revenda_cnpj" && arrayy[1] != "") {
                                pesquisaRevenda(document.frm_os.revenda_cnpj,'cnpj');
                            }
                        }
                    }
                } else {
                    if (results[1] != "nao"){
                        alert(results[1]);
                        window.location.href = 'menu_inicial.php';
                    }
                }
            }
        });
    }
}

function verificaProduto(produto,serie){
    referencia   = produto.value;
    numero_serie = serie.value;

    if (referencia.length > 0 || numero_serie.length > 0) {
        var curDateTime = new Date();
        http6[curDateTime] = createRequestObject();
        url = "<?=$PHP_SELF?>?ajax=true&buscaInformacoes=true&produto_referencia="+referencia+"&serie="+numero_serie+'&data='+curDateTime;
        http6[curDateTime].open('get',url);

        http6[curDateTime].onreadystatechange = function(){
            if (http6[curDateTime].readyState == 4){
                if (http6[curDateTime].status == 200 || http4[curDateTime].status == 304){
                    var results = http6[curDateTime].responseText.split("|");
                    if (results[0] == 'ok') {
                        if (document.getElementById('produto_capacidade')){
                            document.getElementById('produto_capacidade').value = results[1];
                        }
                        if (document.getElementById('divisao')){
                            document.getElementById('divisao').value            = results[2];
                        }
                        if (document.getElementById('versao')){
                            document.getElementById('versao').value             = results[3];
                        }
                    }else{
                        if (document.getElementById('produto_capacidade')){
                            document.getElementById('produto_capacidade').value='';
                        }
                        if (document.getElementById('divisao')){
                            document.getElementById('divisao').value='';
                        }
                        if (document.getElementById('versao')){
                            document.getElementById('versao').value='';
                        }
                    }
                }
            }
        }
        http6[curDateTime].send(null);
    }
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}


var http4 = new Array();
function fn_verifica_garantia(){
    var produto_descricao  = document.getElementById('produto_descricao').value;
    var produto_referencia = document.getElementById('produto_referencia').value;
    var serie              = document.getElementById('produto_serie').value;
    var campo              = document.getElementById('div_estendida');
    var curDateTime = new Date();
    http4[curDateTime] = createRequestObject();

    url = "callcenter_interativo_ajax.php?ajax=true&origem=os_cadastro&garantia=tue&produto_nome=" + produto_descricao + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
    http4[curDateTime].open('get',url);

    http4[curDateTime].onreadystatechange = function(){
        if(http4[curDateTime].readyState == 1) {
            campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
        }
        if (http4[curDateTime].readyState == 4){
            if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
                var results = http4[curDateTime].responseText;
                campo.innerHTML   = results;
            }else {
                campo.innerHTML = "Erro";
            }
        }
    }
    http4[curDateTime].send(null);
}
//------------------------------

function txtBoxFormat(objeto, sMask, evtKeyPress) {
    var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

    if(document.all) { // Internet Explorer
        nTecla = evtKeyPress.keyCode;
    } else if(document.layers) { // Nestcape
        nTecla = evtKeyPress.which;
    } else {
        nTecla = evtKeyPress.which;
        if (nTecla == 8) {
            return true;
        }
    }

    sValue = objeto.value;

    // Limpa todos os caracteres de formatação que
    // já estiverem no campo.
    sValue = sValue.toString().replace( "-", "" );
    sValue = sValue.toString().replace( "-", "" );
    sValue = sValue.toString().replace( ".", "" );
    sValue = sValue.toString().replace( ".", "" );
    sValue = sValue.toString().replace( "/", "" );
    sValue = sValue.toString().replace( "/", "" );
    sValue = sValue.toString().replace( ":", "" );
    sValue = sValue.toString().replace( ":", "" );
    sValue = sValue.toString().replace( "(", "" );
    sValue = sValue.toString().replace( "(", "" );
    sValue = sValue.toString().replace( ")", "" );
    sValue = sValue.toString().replace( ")", "" );
    sValue = sValue.toString().replace( " ", "" );
    sValue = sValue.toString().replace( " ", "" );
    fldLen = sValue.length;
    mskLen = sMask.length;

    i = 0;
    nCount = 0;
    sCod = "";
    mskLen = fldLen;

    while (i <= mskLen) {
        bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/") || (sMask.charAt(i) == ":"))
        bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " "))

    if (bolMask) {
        sCod += sMask.charAt(i);
        mskLen++; }
    else {
        sCod += sValue.charAt(nCount);
        nCount++;
    }

      i++;
    }

    objeto.value = sCod;

    if (nTecla != 8) { // backspace
        if (sMask.charAt(i-1) == "9") { // apenas números...
            return ((nTecla > 47) && (nTecla < 58)); }
        else { // qualquer caracter...
            return true;
    }
    }
    else {
        return true;
    }
}

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
    
    var url = "";
    
    if (tipo == "nome") {
        url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
    }

    if (tipo == "cpf") {
      url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
    }

    if (tipo == "fone") {
        url = "pesquisa_consumidor.php?fone=" + campo.value + "&tipo=fone";
    }
    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
    janela.cliente        = document.frm_os.consumidor_cliente;
    janela.cliente        = document.frm_os.consumidor_id;
    janela.nome            = document.frm_os.consumidor_nome;
    janela.cpf            = document.frm_os.consumidor_cpf;
    janela.rg            = document.frm_os.consumidor_rg;
    janela.cidade        = document.frm_os.consumidor_cidade;
    janela.estado        = document.frm_os.consumidor_estado;
    janela.fone            = document.frm_os.consumidor_fone;
    janela.endereco        = document.frm_os.consumidor_endereco;
    janela.numero        = document.frm_os.consumidor_numero;
    janela.complemento    = document.frm_os.consumidor_complemento;
    janela.bairro        = document.frm_os.consumidor_bairro;
    janela.cep            = document.frm_os.consumidor_cep;
    janela.focus();

}

function nome_readonly(campo, tipo) {
  
  var cpf = $("#consumidor_cpf").val();

  $.ajax({
        type:"POST",
        dataType: "JSON",
        url: location.href,
        data:{
            ajax_nome_readonly : true,
            cpf: cpf
        }
  }).done(function(data){

    if (data["retorno"]) {

      fnc_pesquisa_consumidor (campo, tipo);
      $("#consumidor_nome").prop("readonly", true);
      $("#consumidor_cep").focus();

    } else {

      alert("Nenhum Consumidor encontrado");
      $("#consumidor_nome").prop("readonly", false);
      $("#consumidor_nome").focus();
    }
    
  });
}

function fnc_pesquisa_revenda (campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "pesquisa_revenda<?=$pr_suffix?>.php?nome=" + campo.value + "&tipo=nome";
    }
    if (tipo == "cnpj") {
        url = "pesquisa_revenda<?=$pr_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj";
    }
    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
    janela.nome            = document.frm_os.revenda_nome;
    janela.cnpj            = document.frm_os.revenda_cnpj;
    janela.fone            = document.frm_os.revenda_fone;
    janela.cidade        = document.frm_os.revenda_cidade;
    janela.estado        = document.frm_os.revenda_estado;
    janela.endereco        = document.frm_os.revenda_endereco;
    janela.numero        = document.frm_os.revenda_numero;
    janela.complemento    = document.frm_os.revenda_complemento;
    janela.bairro        = document.frm_os.revenda_bairro;
    janela.cep            = document.frm_os.revenda_cep;
    janela.email        = document.frm_os.revenda_email;
    janela.focus();
}

//HD 234135
function fnc_pesquisa_revenda_fabrica() {
    var cnpj = $("#revenda_cnpj_pesquisa").val();
    cnpj = cnpj.replace(/[^0-9]/g, '');
    if (cnpj.length == 14) {
        $("#revenda_fabrica_msg").html("Aguarde enquanto a pesquisa é realizada");
        url = "os_cadastro_tudo_ajax.php?acao=pesquisa_revenda_fabrica&cnpj="+cnpj;
        requisicaoHTTP("GET", url, true, "fnc_pesquisa_revenda_fabrica_retorno");
    }
    else {
        alert('Digite o CNPJ da revenda com 14 dígitos');
    }
}

//HD 234135
function fnc_pesquisa_revenda_fabrica_retorno(retorno) {
    var retorno = retorno.split('|');
    var cnpj = $("#revenda_cnpj_pesquisa").val();
    fnc_limpa_campos_revenda();

    $("#revenda_fabrica_status").val(retorno[0]);

    switch(retorno[0]) {
        case "cnpj_invalido":
            alert('CNPJ inválido');
            $("#revenda_fabrica_msg").html("Digite o CNPJ da revenda com 14 dígitos e clique na lupa");

            $("#revenda_cnpj_pesquisa").focus();
        break;

        case "cadastrado":
            $("#revenda_cnpj").val(cnpj);
            $("#revenda_nome").val(retorno[1]);
            $("#revenda_fone").val(retorno[2]);
            $("#revenda_cep").val(retorno[3]);
            $("#revenda_endereco").val(retorno[4]);
            $("#revenda_numero").val(retorno[5]);
            $("#revenda_complemento").val(retorno[6]);
            $("#revenda_bairro").val(retorno[7]);
            $("#revenda_cidade").val(retorno[8]);
            $("#revenda_estado").val(retorno[9]);

            $("#consumidor_celular").removeAttr("readonly");

            //350218
            $("#revenda_fabrica_msg").html("CNPJ já cadastrado: confira os dados para dar continuidade");
        break;

        case "radical":
            $("#revenda_cnpj").val(cnpj);
            $("#revenda_nome").val(retorno[1]);

            $("#revenda_fabrica_msg").html("CNPJ não cadastrado: complete os dados da revenda para prosseguir");

            $("#revenda_fone").focus();
        break;

        case "nao_cadastrado":
            $("#revenda_cnpj").val(cnpj);
            $("#revenda_fabrica_msg").html("CNPJ não cadastrado: complete os dados da revenda para prosseguir");

            $("#revenda_nome").focus();
        break;
    }
    monta_cidade(cnpj);
    fnc_pesquisa_revenda_status(retorno[0]);
}

function fnc_pesquisa_revenda_status(cnpj_status) {
    switch(cnpj_status) {
        case "cnpj_invalido":
            fnc_bloqueia_campos_revenda();
        break;

        case "cadastrado":
            //350218
            fnc_desbloqueia_campos_revenda();
            fnc_bloqueia_campo("revenda_nome");
        break;

        case "radical":
            fnc_desbloqueia_campos_revenda();
            fnc_bloqueia_campo("revenda_nome");
        break;

        case "nao_cadastrado":
            fnc_desbloqueia_campos_revenda();
        break;

        default:
            fnc_bloqueia_campos_revenda();
    }
}

function fnc_pesquisa_revenda_fabrica_onblur() {
    var cnpj_pesquisa = $("#revenda_cnpj_pesquisa").val();
    var cnpj = $("#revenda_cnpj").val();

    if (cnpj.length == 14 && cnpj != cnpj_pesquisa) {
        if (cnpj_pesquisa.length == 14) {
            if (confirm("Efetuar nova pesquisa com o CNPJ " + cnpj_pesquisa + ", descartando todos os dados atuais da revenda?")) {
                fnc_pesquisa_revenda_fabrica();
            }
            else {
                $("#revenda_cnpj_pesquisa").val(cnpj);
            }
        }
        else {
            $("#revenda_cnpj_pesquisa").val(cnpj);
        }
    }
}

//HD 234135
function fnc_bloqueia_campo(id_campo) {
    $("#"+id_campo).attr("readonly", "readonly").css("color", "#999999");
}

//HD 234135
function fnc_bloqueia_campos_revenda() {
    fnc_bloqueia_campo("revenda_nome");
    fnc_bloqueia_campo("revenda_fone");
    fnc_bloqueia_campo("revenda_cep");
    fnc_bloqueia_campo("revenda_endereco");
    fnc_bloqueia_campo("revenda_numero");
    fnc_bloqueia_campo("revenda_complemento");
    fnc_bloqueia_campo("revenda_bairro");
    fnc_bloqueia_campo("revenda_cidade");
    fnc_bloqueia_campo("revenda_estado");

    $("#revenda_estado option").hide();
    $("#revenda_estado option:selected").show();
}

function fnc_bloqueia_campos_consumidor(){
    fnc_bloqueia_campo('consumidor_nome');
    fnc_bloqueia_campo('consumidor_cpf');
    fnc_bloqueia_campo('consumidor_cidade');
    fnc_bloqueia_campo('consumidor_fone');
    fnc_bloqueia_campo('consumidor_celular');
    fnc_bloqueia_campo('consumidor_fone_comercial');
    fnc_bloqueia_campo('consumidor_endereco');
    fnc_bloqueia_campo('consumidor_numero');
    fnc_bloqueia_campo('consumidor_complemento');
    fnc_bloqueia_campo('consumidor_bairro');
    fnc_bloqueia_campo('consumidor_cep');
    fnc_bloqueia_campo('nota_fiscal');
    fnc_bloqueia_campo('data_nf');
    fnc_bloqueia_campo('consumidor_email');
    fnc_bloqueia_campo('produto_referencia');
    fnc_bloqueia_campo('produto_descricao');
    fnc_bloqueia_campo('produto_voltagem');
}

//HD 234135
function fnc_desbloqueia_campo(id_campo) {
    $("#"+id_campo).removeAttr("readonly").css("color", "#000000");
}

//HD 234135
function fnc_desbloqueia_campos_revenda() {
    fnc_desbloqueia_campo("revenda_cnpj");
    fnc_desbloqueia_campo("revenda_nome");
    fnc_desbloqueia_campo("revenda_fone");
    fnc_desbloqueia_campo("revenda_cep");
    fnc_desbloqueia_campo("revenda_endereco");
    fnc_desbloqueia_campo("revenda_numero");
    fnc_desbloqueia_campo("revenda_complemento");
    fnc_desbloqueia_campo("revenda_bairro");
    fnc_desbloqueia_campo("revenda_cidade");
    fnc_desbloqueia_campo("revenda_estado");

    $("#revenda_estado option").show();
}

//HD 234135
function fnc_limpa_campo(id_campo) {
    $("#"+id_campo).val("");
}

//HD 234135
function fnc_limpa_campos_revenda() {
    fnc_limpa_campo("revenda_cnpj");
    fnc_limpa_campo("revenda_nome");
    fnc_limpa_campo("revenda_fone");
    fnc_limpa_campo("revenda_cep");
    fnc_limpa_campo("revenda_endereco");
    fnc_limpa_campo("revenda_numero");
    fnc_limpa_campo("revenda_complemento");
    fnc_limpa_campo("revenda_bairro");
    fnc_limpa_campo("revenda_cidade");
    fnc_limpa_campo("revenda_estado");
}

function fnc_limpa_campos_consumidor() {
    fnc_limpa_campo("produto_referencia");
    fnc_limpa_campo("produto_descricao");
    fnc_limpa_campo("produto_voltagem");
    fnc_limpa_campo("nota_fiscal");
    fnc_limpa_campo("data_nf");
    fnc_limpa_campo("defeito_reclamado");
    fnc_limpa_campo("box_prateleira");
    fnc_limpa_campo("consumidor_nome");
    fnc_limpa_campo("consumidor_cpf");
    fnc_limpa_campo("consumidor_fone");
    fnc_limpa_campo("consumidor_cep");
    fnc_limpa_campo("consumidor_endereco");
    fnc_limpa_campo("consumidor_numero");
    fnc_limpa_campo("consumidor_complemento");
    fnc_limpa_campo("consumidor_bairro");
    fnc_limpa_campo("consumidor_cidade");
    fnc_limpa_campo("consumidor_estado");
    fnc_limpa_campo("consumidor_email");
    fnc_limpa_campo("consumidor_celular");
    fnc_limpa_campo("consumidor_fone_comercial");
    $('#data_nf').data('mask').remove();//$("#data_nf").unmask();
    $("#data_nf").datepick();
}

function fnc_desbloqueia_campos_consumidor() {
    fnc_desbloqueia_campo("produto_referencia");
    fnc_desbloqueia_campo("produto_descricao");
    fnc_desbloqueia_campo("produto_voltagem");
    fnc_desbloqueia_campo("nota_fiscal");
    fnc_desbloqueia_campo("data_nf");
    fnc_desbloqueia_campo("defeito_reclamado");
    fnc_desbloqueia_campo("box_prateleira");
    fnc_desbloqueia_campo("consumidor_nome");
    fnc_desbloqueia_campo("consumidor_cpf");
    fnc_desbloqueia_campo("consumidor_fone");
    fnc_desbloqueia_campo("consumidor_cep");
    fnc_desbloqueia_campo("consumidor_endereco");
    fnc_desbloqueia_campo("consumidor_numero");
    fnc_desbloqueia_campo("consumidor_complemento");
    fnc_desbloqueia_campo("consumidor_bairro");
    fnc_desbloqueia_campo("consumidor_cidade");
    fnc_desbloqueia_campo("consumidor_estado");
    fnc_desbloqueia_campo("consumidor_email");
    fnc_desbloqueia_campo("consumidor_celular");
    fnc_desbloqueia_campo("consumidor_fone_comercial");

    //$('#consumidor_celular').data('mask').remove();//$("#consumidor_celular").unmask();
    //$("#consumidor_celular").maskedinput("(99) 9999-9999");
    $('#data_nf').data('mask');//$("#data_nf").unmask();
    $("#data_nf").datepick();
    $("#consumidor_estado option").show();
}

function fnc_num_serie_confirma(valor) {

    if(valor  =='sim'){
        document.getElementById('revenda_nome').readOnly =true;
        document.getElementById('revenda_cnpj').readOnly =true;
        document.getElementById('revenda_fone').readOnly =true;
        document.getElementById('revenda_cidade').readOnly =true;
        document.getElementById('revenda_estado').readOnly =true;
        document.getElementById('revenda_endereco').readOnly =true;
        document.getElementById('revenda_numero').readOnly =true;
        document.getElementById('revenda_complemento').readOnly =true;
        document.getElementById('revenda_bairro').readOnly =true;
        document.getElementById('revenda_cep').readOnly =true;
        document.getElementById('revenda_fixo').style.display='none';
    }else{
        document.getElementById('revenda_nome').readOnly =false;
        document.getElementById('revenda_cnpj').readOnly =false;
        document.getElementById('revenda_fone').readOnly =false;
        document.getElementById('revenda_cidade').readOnly =false;
        document.getElementById('revenda_estado').readOnly =false;
        document.getElementById('revenda_endereco').readOnly =false;
        document.getElementById('revenda_numero').readOnly =false;
        document.getElementById('revenda_complemento').readOnly =false;
        document.getElementById('revenda_bairro').readOnly =false;
        document.getElementById('revenda_cep').readOnly =false;
        document.getElementById('revenda_nome').value='';
        document.getElementById('revenda_cnpj').value='';
        document.getElementById('revenda_fone').value='';
        document.getElementById('revenda_cidade').value='';
        document.getElementById('revenda_estado').value='';
        document.getElementById('revenda_endereco').value='';
        document.getElementById('revenda_numero').value='';
        document.getElementById('revenda_complemento').value='';
        document.getElementById('revenda_bairro').value='';
        document.getElementById('revenda_cep').value='';
        document.getElementById('revenda_fixo').style.display='block';
    }
}

/*if(document.formOne.fieldInfo.checked){
       document.forms['myFormId'].myTextArea.setAttribute('readonly','readonly');
}else if(!document.formOne.fieldInfo.checked){
      document.forms['myFormId'].myTextArea.setAttribute('readonly',true);
      // also tried document.formOne.fieldtextarea.focus();
}*/

<? //HD 731643
if ($login_fabrica==50 || in_array($login_fabrica,[120,201])){
    if ($login_fabrica == 50) {
?>
    $(function() {
        if ($('div#revenda_fixo').css('display') == 'block'){
            $('#revenda_nome').attr('readonly',true);
            $('#revenda_cnpj').attr('readonly',true);
            $('#revenda_fone').attr('disabled',true);
            $('#revenda_cep').attr('readonly',true);
            $('#revenda_endereco').attr('readonly',true);
            $('#revenda_numero').attr('readonly',true);
            $('#revenda_cidade').attr('readonly',true);
            $('#revenda_estado').attr('disabled',true);
            $('#revenda_complemento').attr('readonly',true);
            $('#revenda_bairro').attr('readonly',true);

        }
    });
    <?php
    }
    ?>

    function pesquisaSerie(campo){
        <?php
        if ($login_fabrica == 50) {
        ?>
            gravaDados('data_fabricacao',"");
            $('#data_fabricacao').attr('readonly', false);
        <?php } ?>
        var campo = campo.value;

        var revenda_fixo_url = "";

        if (jQuery.trim(campo).length > 0){
            Shadowbox.open({
                content:    "pesquisa_numero_serie_nv.php?produto_serie="+campo,
                player: "iframe",
                title:      "Pesquisa de Número de Série",
                width:  800,
                height: 500
            });
        }else
            alert("Informar o número de série para realizar esta pesquisa!");

    }

    function retorna_dados_serie(serie,revenda, nome, cnpj, fone, endereco, numero, complemento, bairro, cep, cidade, estado, data_venda, data_fabricacao, referencia, descricao,voltagem,atacadista){
            <?php
            if (in_array($login_fabrica,[120,201])) {
            ?>
            gravaDados('revenda_nome',nome);
            gravaDados('revenda_cnpj',cnpj);
            gravaDados('revenda_fone',fone);
            gravaDados('revenda_cep',cep);
            gravaDados('revenda_endereco',endereco);
            gravaDados('revenda_numero',numero);
            gravaDados('revenda_cidade',cidade);
            gravaDados('revenda_estado',estado);
            gravaDados('revenda_complemento',complemento);
            gravaDados('revenda_bairro',bairro);
            gravaDados('atacadista',atacadista);
            if (atacadista == 't'){

                if ($('#revenda_fixo'))
                {
                    $('#revenda_fixo').show();
                }

                $('#revenda_nome').attr('readonly',true);
                $('#revenda_cnpj').attr('readonly',true);
                $('#revenda_fone').attr('disabled',true);
                $('#revenda_cep').attr('readonly',true);
                $('#revenda_endereco').attr('readonly',true);
                $('#revenda_numero').attr('readonly',true);
                $('#revenda_cidade').attr('readonly',true);
                $('#revenda_estado').attr('disabled',true);
                $('#revenda_complemento').attr('readonly',true);
                $('#revenda_bairro').attr('readonly',true);

            }else{

                if ($('#revenda_fixo'))
                {
                    $('#revenda_fixo').hide();
                }

            }
            <?php
            }
            ?>


            //HD 893100 - foi tirado do retorno estes campos. pois agora vai ser preciso digitar os dados da NF.
            // gravaDados('txt_revenda_nome',nome);
            // gravaDados('txt_revenda_cnpj',cnpj);
            // gravaDados('txt_revenda_fone',fone);
            // gravaDados('txt_revenda_cidade',cidade);
            // gravaDados('txt_revenda_estado',estado);
            // gravaDados('txt_revenda_endereco',endereco);
            // gravaDados('txt_revenda_numero',numero);
            // gravaDados('txt_revenda_complemento',complemento);
            // gravaDados('txt_revenda_bairro',bairro);
            // gravaDados('txt_revenda_cep',cep);

            // gravaDados('txt_data_venda',data_venda);
            // gravaDados('txt_data_fabricacao',data_fabricacao);

            gravaDados('produto_serie',serie);
            gravaDados('produto_referencia',referencia);
            gravaDados('produto_descricao',descricao);
            gravaDados('produto_voltagem',voltagem);
            gravaDados('data_fabricacao',data_fabricacao);
            if (data_fabricacao) {
                $('#data_fabricacao').attr('readonly', true);
            }

    }

    function gravaDados(nome, valor){

        try{
            if (nome == 'revenda_estado'){
                $("select[name="+nome+"]").val(valor);
                $("input[name="+nome+"]").val(valor); // HD 893100 - inseri este "input" por que para a colormaq quando retornar os dados da revenda, irá bloquear o <select> e inserir o valor do ESTADO num hidden
            }else{

                $("input[name="+nome+"]").val(valor);

            }
        } catch(err){
            return false;
        }

    }

<?
}
?>
function fnc_pesquisa_numero_serie (campo, tipo) {

    var url = "";
    var revenda_fixo_url = "";

    if (document.getElementById('revenda_fixo')){
        revenda_fixo_url = "&revenda_fixo=1"
    }

    if (tipo == "produto_serie") {
        url = "pesquisa_numero_serie<?=$ns_suffix?>.php?produto_serie=" + campo.value + "&tipo=produto_serie"+revenda_fixo_url;
    }
    if (tipo == "cnpj") {
        url = "pesquisa_numero_serie<?=$ns_suffix?>.php?cnpj=" + campo.value + "&tipo=cnpj"+revenda_fixo_url;
    }

    janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");

    <? if ($login_fabrica <> 43 && $login_fabrica <> 120 && $login_fabrica <> 201) {?>
    janela.nome            = document.frm_os.revenda_nome;
    janela.cnpj            = document.frm_os.revenda_cnpj;
    janela.fone            = document.frm_os.revenda_fone;
    janela.cidade        = document.frm_os.revenda_cidade;
    janela.estado        = document.frm_os.revenda_estado;
    janela.endereco        = document.frm_os.revenda_endereco;
    janela.numero        = document.frm_os.revenda_numero;
    janela.complemento    = document.frm_os.revenda_complemento;
    janela.bairro        = document.frm_os.revenda_bairro;
    janela.cep            = document.frm_os.revenda_cep;
    janela.email        = document.frm_os.revenda_email;

    janela.txt_nome            = document.frm_os.txt_revenda_nome;
    janela.txt_cnpj            = document.frm_os.txt_revenda_cnpj;
    janela.txt_fone            = document.frm_os.txt_revenda_fone;
    janela.txt_cidade        = document.frm_os.txt_revenda_cidade;
    janela.txt_estado        = document.frm_os.txt_revenda_estado;
    janela.txt_endereco        = document.frm_os.txt_revenda_endereco;
    janela.txt_numero        = document.frm_os.txt_revenda_numero;
    janela.txt_complemento    = document.frm_os.txt_revenda_complemento;
    janela.txt_bairro        = document.frm_os.txt_revenda_bairro;
    janela.txt_cep            = document.frm_os.txt_revenda_cep;

    janela.txt_data_venda    = document.frm_os.txt_data_venda;
    janela.data_fabricacao    = document.frm_os.data_fabricacao;
    if (document.getElementById('revenda_fixo')){
        janela.revenda_fixo        = document.getElementById('revenda_fixo');
    }
    <? }?>

    <?php
    if (in_array($login_fabrica,[120,201])) {
    ?>
      janela.data_fabricacao    = document.frm_os.data_fabricacao;
    <?php
    }
    ?>

    //PRODUTO
    janela.produto_referencia = document.frm_os.produto_referencia;
    janela.produto_descricao  = document.frm_os.produto_descricao;
    janela.produto_voltagem      = document.frm_os.produto_voltagem;
    janela.focus();
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
        Ajusta a formatação da Máscara de DATAS a medida que ocorre
        a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
    var BACKSPACE =  8;
    var DEL       = 46;
    var FRENTE    = 39;
    var TRAS      = 37;
    var key;
    var tecla;
    var strValidos = "0123456789" ;
    var temp;
    tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

    if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
        return true;
            }
        if ( tecla == 13) return false;
        if ((tecla<48)||(tecla>57)){
            return false;
            }
        key = String.fromCharCode(tecla);
        input.value = input.value+key;
        temp="";
        for (var i = 0; i<input.value.length;i++ )
            {
                if (temp.length==2) temp=temp+"/";
                if (temp.length==5) temp=temp+"/";
                if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
                    temp=temp+input.value.substr(i,1);
            }
            }
                    input.value = temp.substr(0,10);
                return false;
}

function MostraAtencao(atencao) {
    var abertura = document.frm_os.data_abertura.value;
    var xnota_fiscal = document.frm_os.data_nf.value;

    if (document.getElementById){
        var style2 = document.getElementById(atencao);

            style2.style.display = "block";
            retornaAtencao(abertura,xnota_fiscal);

    }
}

var http3 = new Array();
function retornaAtencao(abertura,xnota_fiscal){
    if (abertura.length==10 && xnota_fiscal.length==10){
        var prod_ref = document.frm_os.produto_referencia.value;
        var curDateTime = new Date();
        http3[curDateTime] = createRequestObject();
        url = "ajax_validade.php?produto="+prod_ref + "&data_abertura=" + abertura + "&data_nf=" + xnota_fiscal;
        http3[curDateTime].open('get',url);
        var atencao = document.getElementById('atencao');
        http3[curDateTime].onreadystatechange = function(){
            if(http3[curDateTime].readyState == 1) {
                atencao.innerHTML = "<font size='1'>Calculando validade..</font>";
            }
            if (http3[curDateTime].readyState == 4){
                if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
                    var results = http3[curDateTime].responseText;
                    atencao.innerHTML   = results;
                }else {
                    atencao.innerHTML = "Erro";
                }
            }
        }
        http3[curDateTime].send(null);
    }
}
/* ============= <PHP> VERIFICA SE HÁ COMUNICADOS =============
        VERIFICA SE TEM COMUNICADOS PARA ESTE PRODUTO E SE TIVER, RETORNA UM
        LINK PARA VISUALIZAR-LO
        Fábio 07/12/2006
=============================================================== */
function trim(str)
{  while(str.charAt(0) == (" ") )
  {  str = str.substring(1);
  }
  while(str.charAt(str.length-1) == " " )
  {  str = str.substring(0,str.length-1);
  }
  return str;
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http5 = new Array();
var http6 = new Array();
var http7 = new Array();
var http8 = new Array();
var http9 = new Array();

function checarFoto(fabrica){
    var ximagem = document.getElementById('img_produto');
    var xref = document.frm_os.produto_referencia.value;

    document.frm_os.link_comunicado.value="";
    ximagem.title = "NÃO HÁ FOTO PARA ESTE PRODUTO";
    xref = trim(xref);

    if (xref.length>0){
        var curDateTime = new Date();
        http9[curDateTime] = createRequestObject();
        url = "ajax_os_cadastro_foto.php?fabrica="+fabrica+"&produto="+escape(xref);
        http9[curDateTime].open('get',url);
        http9[curDateTime].onreadystatechange = function(){
            if (http9[curDateTime].readyState == 4)
            {
                if (http9[curDateTime].status == 200 || http9[curDateTime].status == 304)
                {
                    var response = http9[curDateTime].responseText;
                    if (response=="ok"){
                        document.frm_os.link_foto.value="CLIQUE AQUI PARA VER A FOTO DESTE PRODUTO";
                        ximagem.title = "CLIQUE AQUI PARA VER A FOTO DESTE PRODUTO";
                    }
                    else {
                        document.frm_os.link_foto.value="";
                        ximagem.title = "NÃO HÁ FOTO PARA ESTE PRODUTO";
                    }
                }
            }
        }
        http9[curDateTime].send(null);
    }
}

function checarComunicado(fabrica){
    var imagem = document.getElementById('img_comunicado');
    var ref = document.frm_os.produto_referencia.value;

    //imagem.style.visibility = "hidden";
    document.frm_os.link_comunicado.value="";
    imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
    ref = trim(ref);

    if (ref.length>0){
        var curDateTime = new Date();
        http7[curDateTime] = createRequestObject();
        url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
        http7[curDateTime].open('get',url);
        http7[curDateTime].onreadystatechange = function(){
            if (http7[curDateTime].readyState == 4)
            {
                if (http7[curDateTime].status == 200 || http7[curDateTime].status == 304)
                {
                    var response = http7[curDateTime].responseText;
                    if (response=="ok"){
                        document.frm_os.link_comunicado.value="HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
                        imagem.title = "HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
                    }
                    else {
                        document.frm_os.link_comunicado.value="";
                        imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
                    }
                }
            }
        }
        http7[curDateTime].send(null);
    }
}

//HD 20682 20/6/2008

function mostraDomicilio(){
    var ref = document.frm_os.produto_referencia.value;
    if (ref.length>0){
        var curDateTime = new Date();
        http8[curDateTime] = createRequestObject();
        url = "<?=$PHP_SELF?>?verifica_linha=sim&produto_referencia="+escape(ref);
        http8[curDateTime].open('get',url);
        http8[curDateTime].onreadystatechange = function(){
            if (http8[curDateTime].readyState == 4)
            {
                if (http8[curDateTime].status == 200 || http8[curDateTime].status == 304)
                {
                    var response = http8[curDateTime].responseText;
                    if (response=="ok"){
                        document.getElementById('atendimento_dominico_span').style.display = "block";
                    } else {
                        document.getElementById('atendimento_dominico_span').style.display = "none";
                    }
                }
            }
        }
        http8[curDateTime].send(null);
    }
}

function abreComunicado(){
    var ref = document.frm_os.produto_referencia.value;
    var desc = document.frm_os.produto_descricao.value;
    if (document.frm_os.link_comunicado.value!=""){
        url = "pesquisa_comunicado.php?produto=" + ref +"&descricao="+desc;
        window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
    }
}

function abreFoto(){
    var xref  = document.frm_os.produto_referencia.value;
    if (document.frm_os.link_foto.value!=""){
        url = "pesquisa_foto_produto.php?produto=" + xref;
        window.open(url);
    }
}

//ajax defeito_reclamado
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) { try {ajax = new XMLHttpRequest();}
                catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
        }
    }

    var tipo_atendimento = $("#tipo_atendimento").val();

    <?php if( in_array($login_fabrica, array(11,172)) ) { ?>
            if(valor == ""){
                return false;
            }
    <?php } ?>
//se tiver suporte ajax
    if(ajax) {
    //deixa apenas o elemento 1 no option, os outros são excluídos
    document.forms[0].defeito_reclamado.options.length = 1;
    //opcoes é o nome do campo combo
    idOpcao  = document.getElementById("opcoes");
    //     ajax.open("POST", "ajax_produto.php", true);
    ajax.open("GET", "ajax_produto<?=$ap_suffix?>.php?produto_referencia="+valor+"&tipo_atendimento="+tipo_atendimento, true);
    ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    ajax.onreadystatechange = function() {
        if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
        if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//após ser processado-chama fun
            } else {idOpcao.innerHTML = "";//caso não seja um arquivo XML emite a mensagem abaixo
                    }
        }
    }
    //passa o código do produto escolhido
    var params = "produto_referencia="+valor;
    ajax.send(null);
    }
}

function resetaDefeito() { //HD 381252

    defeito = $('#defeito_reclamado').find('option').filter(':selected').text();

    if(defeito !== "") {

        $('#defeito_reclamado').find('option').remove();
        $("#defeito_reclamado").append("<option value='' id='opcoes'>Selecione o Defeito</option>");

    }

}

function montaCombo(obj){
    var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
    if(dataArray.length > 0) {//total de elementos contidos na tag cidade
    for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
         var item = dataArray[i];
        //contéudo dos campos no arquivo XML
        var codigo = item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
	var nome   = item.getElementsByTagName("nome")[0].firstChild.nodeValue;
	var rel = "";
	<?php
		if($login_fabrica == 42){
	?>
		var rel    = item.getElementsByTagName("rel")[0].firstChild.nodeValue;
	<?php } ?>

        idOpcao.innerHTML = "";

        //cria um novo option dinamicamente
        var novo = document.createElement("option");
        novo.setAttribute("id", "opcoes"); //atribui um ID a esse elemento
        novo.setAttribute("rel", rel);     //atribuit um rel a esse elemento
        novo.value = codigo; //atribui um valor
        novo.text  = nome;   //atribui um texto
        document.forms[0].defeito_reclamado.options.add(novo); //adiciona o novo elemento
        }
    } else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
    }
}


function MostraEsconde(dados){
    if (document.getElementById){
        var style2 = document.getElementById(dados);
        if (!style2) return;
        if (style2.style.display=="block"){
            style2.style.display = "none";
        }else{
            style2.style.display = "block";
            retornaLinha(dados);
        }
    }
}
var http2 = new Array();
function retornaLinha (dados) {
    var com = document.getElementById(dados);
    var ref = document.frm_os.produto_referencia.value;

    if (ref.length>0){
        var curDateTime = new Date();
        http2[curDateTime] = createRequestObject();
        url = "os_cadastro_tudo.php?ajax=sim&produto_referencia=" + escape(ref);
        http2[curDateTime].open('get',url);
        http2[curDateTime].onreadystatechange = function(){
            if (http2[curDateTime].readyState == 4){
                if (http2[curDateTime].status == 200 || http2[curDateTime].status == 304){
                    var results = http2[curDateTime].responseText.split("|");
                    if (results[0] == 'ok') {
                        //document.getElementById("dados_01").innerHTML = results[1];
                        //alert(results[1]);
                        com.innerHTML   = results[1];
                    }
                    else {
                    }
                }
            }
        }
        http2[curDateTime].send(null);
    }
}

function formata_data(campo_data, form, campo){
    var mycnpj = '';
    mycnpj = mycnpj + campo_data;
    myrecord = campo;
    myform = form;

    if (mycnpj.length == 2){
        mycnpj = mycnpj + '/';
        window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
    }
    if (mycnpj.length == 5){
        mycnpj = mycnpj + '/';
        window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
    }

}

    window.onload = function(){
        $("#revenda_cnpj").keypress(function(e) {
            var c = String.fromCharCode(e.which);
            var allowed = '1234567890 ';
            if (e.which != 8 && allowed.indexOf(c) < 0) return false;
        });
    }

<? if($login_fabrica == 3) { /* hd 17735 */ ?>
function char(serie){
    try{var element = serie.which    }catch(er){};
    try{var element = event.keyCode    }catch(er){};
    if (String.fromCharCode(element).search(/[0-9]|[A-Z]/gi) == -1){
        if (element != 0 && element != 8){
            return false
        }
    }
}
window.onload = function(){
    document.getElementById('produto_serie').onkeypress = char;
}
<? } ?>

$(function() {
    $("input[name=os_cortesia]").click(function(){
        if ($(this).is(":checked")) {
            $(".anexo_cortesia").css("display","table-row");
        } else {
            $(".anexo_cortesia").css("display","none");
            (".anexo_cortesia input").val("");
        }
    });
    <?
    if ($usa_revenda_fabrica) {
        echo "fnc_pesquisa_revenda_status('$revenda_fabrica_status');";
    }
    ?>
    displayText('&nbsp;');
    $("input[rel='garantia']").blur(function(){
        var campo = $(this);


            $.post('<? echo $PHP_SELF; ?>',
                {
                    gravarDataconserto : campo.val(),
                    produto: campo.attr("alt")
                },
                function(resposta){
                }
            );

    });

    /*$("input[name^=foto_nf]").change(function(){
        var tamanho = $(this).prop('files')[0]['size'];

        if (parseInt(tamanho) > 4404019) {
            alert("Anexo não será aceito pois é maior que 2MB");
            $(this).val("");
        }
    });*/

    <?php
    if (in_array($login_fabrica, [19])) { ?>

      libera_tipo_atendimento_garantia($("#produto_referencia").val());

      $("#tipo_atendimento").change(function(){

        if ($(this).val() == "339") {
          Shadowbox.open({
            content: '<div style="height: 100%;;width: 100%; padding: 20px;background-color: white;"> \
                        <h1 style="color: red;text-align: center;">Atenção!</h1><br /> <span style="font-size: 16px;">Esta ação é apenas para cadastro de validação de garantia do produto. Em caso de dúvidas, favor entrar em contato com o fabricante.</span>  \
                        <br /> \
                        <br /> \
                        <center><button style="color: #fff; background-color: #5cb85c; border-color: #4cae4c;height: 50px;width: 100px;font-size: 17px;cursor: pointer;border-radius: 5px;"                                      onClick="confirmaGar()">Ciente</button></center> \
                    </div>',
            player: "html",
            title: "Alerta",
            width: 500,
            height: 300
            });
        }

      });

    <?php
    }
    ?>

});

function confirmaGar() {
  Shadowbox.close();
}

function createRequestObject(){
    var request_;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
         request_ = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
         request_ = new XMLHttpRequest();
    }
    return request_;
}

var http_forn = new Array();

function verifica_atendimento_clear() {
    $("#defeito_reclamado_descricao").removeAttr("disabled").css("background-color", "#F0F0F0");
    $("#prateleira_box").removeAttr("disabled").css("background-color", "#F0F0F0");
    $("#aparencia_produto").removeAttr("disabled").css("background-color", "#F0F0F0");
    $("#acessorios").removeAttr("disabled").css("background-color", "#F0F0F0");
    $("#defeito_reclamado_descricao_title").html("Defeito Reclamado");
    $('#produto_referencia').removeAttr('disabled');
    $('#produto_descricao').removeAttr('disabled');
    $('#produto_serie').removeAttr('disabled');
    $('#produto_voltagem').removeAttr('disabled');
    $('#defeito_reclamado').removeAttr('disabled');
    $("b[id=tipo_atendimento_obg]").each(function() {
        $(this).hide();
    });
}

function verifica_atendimento() {

    /*Verificacao para existencia de componente - HD 22891 */<?php
    if ($login_fabrica == 42) {?>
         verifica_atendimento_clear();

        if ($("#tipo_atendimento").find("option:selected").attr("rel") == "t") {
            $("#defeito_reclamado_descricao").attr("disabled", "disabled").val("").css("background-color", "#777");
            $("#prateleira_box").attr("disabled", "disabled").val("").css("background-color", "#777");
            $("#aparencia_produto").attr("disabled", "disabled").val("").css("background-color", "#777");
            $("#acessorios").attr("disabled", "disabled").val("").css("background-color", "#777");
            $("#defeito_reclamado_descricao_title").html("Defeito Reclamado / Aplicação");
            $("b[id=tipo_atendimento_obg]").each(function() {
                $(this).show();
            });
        } /*else if ($('#tipo_atendimento').val() == 102) {

            $('#produto_referencia').val('');
            $('#produto_descricao').val('');

        }*/ else if ($('#tipo_atendimento').val() == 103 || $('#tipo_atendimento').val() == 104) {

            $('#produto_referencia').attr('disabled', 'disabled');
            $('#produto_descricao').attr('disabled', 'disabled');
            $('#produto_serie').attr('disabled', 'disabled');
            $('#defeito_reclamado').attr('disabled', 'disabled');

            $('#produto_referencia').val('');
            $('#produto_descricao').val('');
            $('#produto_serie').val('');

        } else if ($('#tipo_atendimento').val() == 133 || $('#tipo_atendimento').val() == 134) {
            $('#produto_referencia').attr('disabled', 'disabled');
            $('#produto_descricao').attr('disabled', 'disabled');
            $('#produto_serie').attr('disabled', 'disabled');
            $('#produto_voltagem').attr('disabled', 'disabled');

            var ref = document.frm_os.produto_referencia;

            if($('#tipo_atendimento').val() == 133){
                $('#produto_referencia').val('GAR-BATER');
            } else {
                $('#produto_referencia').val('GAR-CARRE');
            }

            pesquisaProduto(ref, 'referencia');

        }

        <?php

    }?>

    $('#distancia_km').val('');
    $('#div_mapa_msg').html('');
    $('#div_end_posto').html('');

    //$('#div_mapa').toggle();
    if (document.getElementById('div_mapa')){
      var ref = $('#tipo_atendimento').val();

      $.get('<?=$PHP_SELF?>', {'ajax': 'tipo_atendimento', 'id'  : ref}, function(responseText) {
        var response = responseText.split("|");

        if ($.trim(response[0]).indexOf('ok') > -1) {
          //document.getElementById('div_mapa').style.visibility = "visible";
          //document.getElementById('div_mapa').style.position = 'static';
          //document.getElementById('div_mapa_msg').style.visibility = "visible";
          $('#div_mapa').show();
        } else {

          //document.getElementById('div_mapa').style.visibility = "hidden";
          //document.getElementById('div_mapa').style.position = 'absolute';
          //document.getElementById('div_mapa_msg').style.visibility = "hidden";
          $('#div_mapa').hide();
         }
      });
    }


    var consumidor_cep = $('consumidor_cep').val();
    var consumidor_endereco = $('#consumidor_endereco').val();

}

function noReadonlyCidade(){
  setTimeout(function(){
    $("input[name='consumidor_cidade']").removeAttr("readonly");
  }, 1000);
}

/* Inicio Google Maps */

function siglaEstado(sigla){

    switch(sigla){
        case "AC" : sigla1 = "Acre"; break;
        case "AL" : sigla1 = "Alagoas"; break;
        case "AP" : sigla1 = "Amapá"; break;
        case "AM" : sigla1 = "Amazonas"; break;
        case "BA" : sigla1 = "Bahia"; break;
        case "CE" : sigla1 = "Ceará"; break;
        case "DF" : sigla1 = "Distrito Federal"; break;
        case "ES" : sigla1 = "Espírito Santo"; break;
        case "GO" : sigla1 = "Goiás"; break;
        case "MA" : sigla1 = "Maranhão"; break;
        case "MT" : sigla1 = "Mato Grosso"; break;
        case "MS" : sigla1 = "Mato Grosso do Sul"; break;
        case "MG" : sigla1 = "Minas Gerais"; break;
        case "PA" : sigla1 = "Pará"; break;
        case "PB" : sigla1 = "Paraíba"; break;
        case "PR" : sigla1 = "Paraná"; break;
        case "PE" : sigla1 = "Pernambuco"; break;
        case "PI" : sigla1 = "Piauí"; break;
        case "RJ" : sigla1 = "Rio de Janeiro"; break;
        case "RN" : sigla1 = "Rio Grande do Norte"; break;
        case "RS" : sigla1 = "Rio Grande do Sul"; break;
        case "RO" : sigla1 = "Rondônia"; break;
        case "RR" : sigla1 = "Roraima"; break;
        case "SC" : sigla1 = "Santa Catarina"; break;
        case "SP" : sigla1 = "São Paulo"; break;
        case "SE" : sigla1 = "Sergipe"; break;
        case "TO" : sigla1 = "Tocantins"; break;
    }

    return sigla1;

}

function retiraAcentos(palavra){

    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
        if (com_acento.search(palavra.substr(i,1)) >= 0) {
            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i,1)),1);
        }
        else{
            newPalavra += palavra.substr(i,1);
        }
    }

    return newPalavra.toUpperCase();
}

$('#GoogleMapsContainer').css({'display' : 'none'});

var directionsDisplay;
//var directionsService = new google.maps.DirectionsService();
var map;

var qtdVezesRota = 0;

var googleNaoEncontrou = 0;

var alterarDistanciaVezes = 0;

<?php
    if(strlen($msg_erro) > 0){
        echo "var erroCadastro = 0;";
    }
?>

<?php if($calculoKM == "t"){ ?>

/* INICIO - MAPBOX */
var geocoder, latlon, c_lat, c_lon, LatLngPosto;
var Map, Markers, Route, Geocoder, geometry;

function verificaPostoProximo(LatLngPosto, latlon, produto)
{
    $.ajax({
        url: "os_cadastro_tudo.php",
        type:"POST",
        dataType: "JSON",
        data:{
            ajax:true,
            tipo:"postoProximo",
            latlon:latlon,
            produto:produto
        }
    })
    .done(function(data){
        if (data.posto == "diferente") {
            var LatLngProximo = data.lat + ","+ data.lon;
            var get = [
                "proximo="+LatLngProximo,
                "cliente="+latlon,
                "codPosto="+data.codPosto,
                "nomePosto="+data.nomePosto,
                "kmProximo="+data.distancia
            ];

            Shadowbox.open({
                content: "mapa_rede_posto_proximo.php?"+get.join("&"),
                player: "iframe",
                width: '1400px',
                height: '900px'
            });

        } else {
            calcRouteAjax(LatLngPosto, latlon);
        }
    });
}

function recebeDados(km_ida,km_volta,distancia,proximo,latLongProximo,endereco)
{
    $('#ida_volta').html('<strong>Ida:</strong> '+km_ida+" &nbsp; <strong>Volta:</strong> "+km_volta);
    $('#distancia_km_conferencia').val(distancia);
    $('#distancia_km').val(distancia);
    $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa('+latLongProximo+');">Ver mapa</a>');
    $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+endereco);

    $("#distancia_km").attr("readOnly",true);
    $("#route").attr("disabled",true);
    $("#loading-map").hide();
    $("#posto_proximo").val(proximo);

    Shadowbox.close();
}

function calcRouteAjax(LatLngPosto, latlon)
{
    $.ajax({
        url: "controllers/TcMaps.php",
        type: "POST",
        dataType:"JSON",
        data: {
            ajax: "route",
            origem: LatLngPosto,
            destino: latlon,
            ida_volta: "sim"
        },
        timeout: 60000
    })
    .done(function(data){
        var kmtotal = data.total_km.toFixed(2);
        geometry    = data.rota.routes[0].geometry;

        $('#ida_volta').html('<strong>Ida:</strong> '+data.km_ida+" &nbsp; <strong>Volta:</strong> "+data.km_volta);
        $('#distancia_km_conferencia').val(kmtotal);
        $('#distancia_km').val(kmtotal);
        $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa('+LatLngPosto+');">Ver mapa</a>');
        $('#loading-map').hide();
    }).fail(function(){
        $('#loading-map').hide();
        alert('Erro ao tentar calcular a rota!');
    });
}

function calcRoute()
{
    var type = arguments[0];
    var textIdaVolta = "";

    $('#ida_volta').html("");

    /* validacoes */
    if($('#consumidor_cidade').val() == ""){
        alert("Por favor insira a cidade do Consumidor!");
        $(this).focus();
        return;
    }

    if($('#consumidor_estado').val() == ""){
        alert("Por favor insira o estado do Consumidor!");
        $(this).focus();
        return;
    }

    var cidadeConsumidor = "";
    var estadoConsumidor = "";

    $('#div_end_posto').html('');
    $('#div_mapa_msg').html('');

    var posto = "";
    var consumidor = "";


    if ($('#contato_endereco').val() != "" ||
        $('#contato_cidade').val() != ""   ||
        $('#contato_estado').val() == "") {

        if ($('#contato_endereco').val() != "") {
            posto += " "+document.getElementById("contato_endereco").value;
        }
        if ($('#contato_numero').val() != "") {
            posto += " "+document.getElementById("contato_numero").value;
        }

        posto += ", "+document.getElementById("contato_cidade").value;
        posto += ", "+document.getElementById("contato_estado").value;
        posto += ", Brasil";
    } else if($('#contato_cep').val() != "") {
        posto = $('#contato_cep').val();
    } else {
        alert("Dados insuficientes do Posto para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
        return;
    }
    if (type =='cep' && $('#consumidor_cep').val() != "") {
        consumidor = $('#consumidor_cep').val();
        consumidor = "cep: "+ consumidor.replace('.','');
        consumidor += ", "+document.getElementById("consumidor_cidade").value;
        consumidor += ", "+document.getElementById("consumidor_estado").value;
        consumidor += ", Brasil";

    } else {
        if ($('#consumidor_endereco').val() != "" || $('#consumidor_cidade').val() != "" || $('#consumidor_estado').val() != "") {
            if ($('#consumidor_endereco').val() != "") {
                consumidor += " "+document.getElementById("consumidor_endereco").value;
            }

            if ($('#consumidor_numero').val() != "") {
                if (!verificarNumero(document.getElementById("consumidor_numero").value)) {
                    consumidor += " "+document.getElementById("consumidor_numero").value;
                }
            }

            // if($('#consumidor_bairro').val() != "") { consumidor += ", "+document.getElementById("consumidor_bairro").value; }

            consumidor += (consumidor != "") ? ", " : "";
            consumidor += document.getElementById("consumidor_cidade").value;
            consumidor += ", "+document.getElementById("consumidor_estado").value;
            consumidor += ", Brasil";

            /* Cidade e Estado Consumidor */
            cidadeConsumidor = $('#consumidor_cidade').val();
            estadoConsumidor = $('#consumidor_estado').val();

        } else if ($('#consumidor_cep').val() != "") {

            consumidor = $('#consumidor_cep').val();
        } else {

            alert("Dados insuficientes do Consumidor para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
            return;
        }
    }
    if (posto == "") {
        alert('Endereço do Posto não localizado! Por favor verifique se os dados es corretos!');
        return;
    }

    if (consumidor == "") {
        if ($('#consumidor_cep').val() == "") {
            alert('Por favor insira o Consumidor');
            $('#consumidor_nome').focus();
            return;
        } else {
            consumidor = $('#consumidor_cep').val();
        }
    }

    function liberar_campoKM_posto(){
      <?php
        if(in_array($login_fabrica, array(120,201))){
      ?>
          $('#distancia_km').val("0").prop({readonly:false});
      <?php
        }
      ?>

    }

    var Contato_endereco = $('#contato_endereco').val();
    var Contato_cidade   = $('#contato_cidade').val();
    var Contato_estado   = $('#contato_estado').val();
    var Contato_numero   = $('#contato_numero').val();
    var Contato_bairro   = $('#contato_bairro').val();
    var Contato_cep      = $('#contato_cep').val();

    var Consumidor_endereco = $('#consumidor_endereco').val();
    var Consumidor_cidade   = $('#consumidor_cidade').val();
    var Consumidor_estado   = $('#consumidor_estado').val();
    var Consumidor_numero   = $('#consumidor_numero').val();
    var Consumidor_bairro   = $('#consumidor_bairro').val();
    var Consumidor_cep      = $('#consumidor_cep').val();

    var Pais = "Brasil";

    var endereco = Contato_endereco+" "+Contato_numero+" "+Contato_bairro+" "+Contato_cidade+" "+Contato_estado ;

    if (typeof Map !== "object") {
        Map      = new Map("GoogleMaps");
        Markers  = new Markers(Map);
        Router   = new Router(Map);
        Geocoder = new Geocoder();
    }

    $('#loading-map').show();

    try {
        Geocoder.setEndereco({
            endereco: Consumidor_endereco,
            numero: Consumidor_numero,
            bairro: Consumidor_bairro,
            cidade: Consumidor_cidade,
			estado: Consumidor_estado,
			cep: Consumidor_cep,
            pais: Pais
        });

        request = Geocoder.getLatLon();

        request.then(
            function(resposta) {
                LatLngPosto = $('#LatLngPosto').val();

                c_lat  = resposta.latitude;
                c_lon  = resposta.longitude;
                latlon = c_lat+","+c_lon;
<?php
if ($login_fabrica == 50) {
?>
                var tipo_atendimento = $("#tipo_atendimento").val();
                var produto = $("#produto_referencia").val();

                if (tipo_atendimento == 55) {
                    verificaPostoProximo(LatLngPosto,latlon,produto);
                } else {
                    calcRouteAjax(LatLngPosto,latlon);
                }
<?php
} else {
?>
                calcRouteAjax(LatLngPosto,latlon);
<?php
}
?>
            },
            function(erro) {
                $('#loading-map').hide();
                alert(erro);
            }
        );
    } catch(e) {
        $('#loading-map').hide();
        alert(e.message);
    }
}

function vermapa(latMapa=null,longMapa=null)
{
    $("#GoogleMapsContainer").css({'display' : 'block'});

    Map.load();

    var LatLngSplit = LatLngPosto.split(',');
    if (latMapa.length == 0 && longMapa.length == 0) {
        latPonto = LatLngSplit[0];
        longPont = LatLngSplit[1];
    } else {
        latPonto = latMapa;
        longPont = longMapa;
    }
    /* Marcar pontos no mapa */
    Markers.remove();
    Markers.clear();
    Markers.add(c_lat, c_lon, "blue", "Cliente");
    Markers.add(latPonto, longPont, "red", "Posto");
    Markers.render();
    Markers.focus();

    Router.remove();
    Router.clear();
    Router.add(Polyline.decode(geometry));
    Router.render();
}

/* FIM - MAPBOX*/

function verificarNumero(numBusca){
  var numero = false;

  for (var i = 0; i < numBusca.length; i++){
    if (!numBusca.charAt(i).match("^[0-9]*$")){
      numero = true;
      break;
    }
  }
  return numero;
}

function compara(){

    var campo1 = "";
    var campo2 = "";

    campo1 = $('#distancia_km_conferencia').val();
    campo2 = $('#distancia_km').val();

    var num1 = campo1.replace(".",",");
    var num2 = campo2.replace(".",",");

    if(num1 != num2){
        return 1;
    }else{
        return 2;
    }

}

function calcRoute_antigo(){
	var type = arguments[0];
    var textIdaVolta = "";

    $('#ida_volta').html("");

    /* validacoes */
    if($('#consumidor_cidade').val() == ""){
        alert("Por favor insira a cidade do Consumidor!");
        $(this).focus();
        return;
    }

    if($('#consumidor_estado').val() == ""){
        alert("Por favor insira o estado do Consumidor!");
        $(this).focus();
        return;
    }

    var cidadeConsumidor = "";
    var estadoConsumidor = "";

    $('#distancia_km').val('');
    $('#div_end_posto').html('');
    $('#div_mapa_msg').html('');

    var posto = "";
    var consumidor = "";


    if($('#contato_endereco').val() != "" ||
        $('#contato_cidade').val() != "" ||
        $('#contato_estado').val() == ""){

        if($('#contato_endereco').val() != ""){
            posto += " "+document.getElementById("contato_endereco").value;
        }
        if($('#contato_numero').val() != ""){
            posto += " "+document.getElementById("contato_numero").value;
        }

        posto += ", "+document.getElementById("contato_cidade").value;
        posto += ", "+document.getElementById("contato_estado").value;
        posto += ", Brasil";
    }else if($('#contato_cep').val() != ""){
        posto = $('#contato_cep').val();
    }else{
        alert("Dados insuficientes do Posto para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
        return;
    }
	if (type =='cep' && $('#consumidor_cep').val() != "") {
			consumidor = $('#consumidor_cep').val();
			consumidor = "cep: "+ consumidor.replace('.','');
			consumidor += ", "+document.getElementById("consumidor_cidade").value;
			consumidor += ", "+document.getElementById("consumidor_estado").value;
			consumidor += ", Brasil";

	}else{
		if($('#consumidor_endereco').val() != "" || $('#consumidor_cidade').val() != "" || $('#consumidor_estado').val() != ""){
			if($('#consumidor_endereco').val() != "") { consumidor += " "+document.getElementById("consumidor_endereco").value; }

				if($('#consumidor_numero').val() != ""){
					if(!verificarNumero(document.getElementById("consumidor_numero").value)){
						consumidor += " "+document.getElementById("consumidor_numero").value;
					}
				}

			// if($('#consumidor_bairro').val() != "") { consumidor += ", "+document.getElementById("consumidor_bairro").value; }

			consumidor += (consumidor != "") ? ", " : "";
			consumidor += document.getElementById("consumidor_cidade").value;
			consumidor += ", "+document.getElementById("consumidor_estado").value;
			consumidor += ", Brasil";

			/* Cidade e Estado Consumidor */
			cidadeConsumidor = $('#consumidor_cidade').val();
			estadoConsumidor = $('#consumidor_estado').val();

		}else if($('#consumidor_cep').val() != ""){
			consumidor = $('#consumidor_cep').val();
		}else{
			alert("Dados insuficientes do Consumidor para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
			return;
		}
	}
    if(posto == ""){
        alert('Endereço do Posto não localizado! Por favor verifique se os dados es corretos!');
        return;
    }

    if(consumidor == ""){
        if($('#consumidor_cep').val() == ""){
            alert('Por favor insira o Consumidor');
            $('#consumidor_nome').focus();
            return;
        }else{
            consumidor = $('#consumidor_cep').val();
        }
    }

    console.log(consumidor);
    console.log(posto);

    /* Quilometragem da Rota */

    var service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix(
    {
        origins: [posto],
        destinations: [consumidor],
        travelMode: google.maps.TravelMode.DRIVING,
        unitSystem: google.maps.UnitSystem.METRIC,
        avoidHighways: false,
        avoidTolls: false
    }, callback);

    function liberar_campoKM_posto(){
      <?php
        if(in_array($login_fabrica, array(120,201))){
      ?>
          $('#distancia_km').val("0").prop({readonly:false});
      <?php
        }
      ?>

    }

    function callback(response, status) {
        if (status != google.maps.DistanceMatrixStatus.OK) {
          // tratar
          alert('Error was: ' + status);
        } else {

            var results = response.rows[0].elements;
            var destino = response.destinationAddresses;
            destino = destino.toString();
			if (type =='cep'){
		  		destinoCep = destino.replace(/\D/g,'');
	  			consumidorCep = consumidor.replace(/\D/g,'');
			}

            if(results[0].status == "OK"){

                var cidadesIguais = 0;
                var estadosIguais = 0;

                /* Reescreve a Sigla do estado para o nome completo */
                estadoConsumidor = siglaEstado(estadoConsumidor);

                var comp1   = new Array();
                var comp2   = new Array();
                var seq     = 0;

                destino = destino.replace(/\d{5}-\d{3},/g,'');
                destino = destino.replace(/-/g,',');
                comp1 = destino.split(",");
                var c1 = comp1.length;

                var cidadeComp = "";
                var estadoComp = "";

                if(comp1[c1-3] !== undefined){ cidadeComp = comp1[c1-3]; }
                if(comp1[c1-2] !== undefined){ estadoComp = comp1[c1-2]; }

                if(cidadeComp.length > 0){
                    cidadeComp = retiraAcentos(cidadeComp);
                    cidadeConsumidor = retiraAcentos(cidadeConsumidor);
                }

                if(estadoComp.length > 0){
                    estadoComp = siglaEstado(estadoComp);
                    estadoComp = retiraAcentos(estadoComp);
                    estadoConsumidor = retiraAcentos(estadoConsumidor);
                }

				if(cidadeComp.trim() != cidadeConsumidor.trim() && type !='cep'){
					calcRoute('cep');
				}

                /* Compara se a cidade e o estado estão corretos */
                if(estadoComp.length > 0){
                    if(estadoComp.trim() == estadoConsumidor.trim() || cidadeComp.trim() == cidadeConsumidor.trim()){
                        cidadesIguais++;
                        estadosIguais++;
                    }
                }

                if(cidadesIguais == 0 && estadosIguais == 0){
                    if(cidadeComp.trim().length > 0){
                        if(cidadeComp.trim() == cidadeConsumidor.trim()){
                            cidadesIguais++;
                        }
                    }
                    if(estadoComp.trim() == estadoConsumidor.trim()){
                        estadosIguais++;
                    }
                }

				if(type =='cep' && destinoCep == consumidorCep) {
	                cidadesIguais++;
                    estadosIguais++;
				}

                var kmtotal1 = results[0].distance.value;

                // getRouteInverse(kmtotal1, consumidor, posto);

                /* Realiza a rota inversa */
                var service2 = new google.maps.DistanceMatrixService();

                function callback2(response2, status2){

                    if (status2 != google.maps.DistanceMatrixStatus.OK) {

                        var kmtotal = 0;
                        kmtotal = kmtotal1.toFixed(2);
                        kmtotal = kmtotal.toString();
                        kmtotal = kmtotal.replace(".", ",");

                    } else {

                        var results = response2.rows[0].elements;

                        if(results[0].status == "OK"){

                            var kmtotal = 0;
                            var kmtotal2 = results[0].distance.value;
                            kmtotal = (kmtotal1 + kmtotal2) / 1000;

                            kmtotal = kmtotal.toFixed(2);
                            kmtotal = kmtotal.toString();
                            kmtotal = kmtotal.replace(".", ",");

                            kmtotal1 = kmtotal1 / 1000;
                            kmtotal1 = kmtotal1.toFixed(2);
                            kmtotal1 = kmtotal1.toString();
                            kmtotal1 = kmtotal1.replace(".", ",");

                            kmtotal2 = kmtotal2 / 1000;
                            kmtotal2 = kmtotal2.toFixed(2);
                            kmtotal2 = kmtotal2.toString();
                            kmtotal2 = kmtotal2.replace(".", ",");

<?php
                                if ((strlen($qtde_km) > 0 and strtoupper($qtde_km) !='NULL') || strlen($msg_erro) > 0) {

                                    if(strlen($msg_erro) > 0){
                                        $distancia_km = $qtd_km;
                                        echo "alterarDistanciaVezes++;";
                                    };

?>

                                    // alert(alterarDistanciaVezes);

                                    if (qtdVezesRota == 0) {

                                        if(alterarDistanciaVezes > 0){
                                            textIdaVolta = '<strong>Ida:</strong> '+kmtotal1+" &nbsp; <strong>Volta:</strong> "+kmtotal2;
                                            $('#ida_volta').html(textIdaVolta);
                                        }else{
                                            alterarDistanciaVezes++;
                                        }

                                        $('#distancia_km').val("");
                                        $('#distancia_km_conferencia').val("");
                                        $('#distancia_km').val("<?=$qtde_km;?>");
                                        $('#distancia_km_conferencia').val("<?=$qtde_km;?>");

<?php
                                        if (strlen($msg_erro) > 0) {
?>
                                        if(erroCadastro == 0){
                                            $('#distancia_km').val(kmtotal);
                                            $('#distancia_km_conferencia').val(kmtotal);
                                        }
<?php
                                        }
?>

                                        var comp = compara();

                                        if(cidadesIguais != 0 && estadosIguais != 0){

                                            if(comp == 2){
                                                $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                                $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+posto);
                                            }else{
                                                $('#div_mapa_msg').html('A distância percorrida pelo técnico estará sujeito a auditoria');
                                            }
                                        }else{

                                            if(googleNaoEncontrou != 0){
                                                $('#distancia_km').val("0");
                                                $('#distancia_km_conferencia').val("0");
                                                $('#div_mapa_msg').html("");
                                                $('#div_end_posto').html("");
                                                $('#ida_volta').html("");
                                                if($('#tipo_atendimento').val() == 71){
                                                    alert('Endereço não localizado pelo Google Maps, por favor insira manualmente.');
                                                }
                                            }

                                            googleNaoEncontrou++;

                                        }

                                        qtdVezesRota++;
                                    } else {
                                        $('#distancia_km').val("");
                                        $('#distancia_km').attr('value', kmtotal);

                                        if(alterarDistanciaVezes > 0){
                                            textIdaVolta = '<strong>Ida:</strong> '+kmtotal1+" &nbsp; <strong>Volta:</strong> "+kmtotal2;
                                            $('#ida_volta').html(textIdaVolta);
                                        }else{
                                            alterarDistanciaVezes++;
                                        }

                                        var comp = compara();

                                        if(cidadesIguais != 0 && estadosIguais != 0){
                                            if (comp == 2) {
                                                $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                                $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+posto);
                                            }else{
                                                $('#div_mapa_msg').html('A distância percorrida pelo técnico estará sujeito a auditoria');
                                            }
                                        } else {

                                            if (googleNaoEncontrou != 0) {
                                                $('#distancia_km').val("0");
                                                $('#distancia_km_conferencia').val("0");
                                                $('#div_mapa_msg').html("");
                                                $('#div_end_posto').html("");
                                                $('#ida_volta').html("");
                                                if($('#tipo_atendimento').val() == 71){
                                                    alert('Endereço não localizado pelo Google Maps, por favor insira manualmente.');
                                                }
                                            }

                                            googleNaoEncontrou++;

                                        }
                                    }
<?php
                                } else {
?>
                                    if (cidadesIguais != 0 && estadosIguais != 0) {

                                        textIdaVolta = '<strong>Ida:</strong> '+kmtotal1+" &nbsp; <strong>Volta:</strong> "+kmtotal2;
                                        $('#ida_volta').html(textIdaVolta);

                                        $('#distancia_km').val(kmtotal);
                                        $('#distancia_km_conferencia').val(kmtotal);
                                        $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                        $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+posto);

                                    } else {

                                        liberar_campoKM_posto();
                                        $('#ida_volta').html("");

                                        $('#distancia_km').val("0");
                                        $('#distancia_km_conferencia').val("0");
                                        $('#div_mapa_msg').html('');
                                        $('#div_end_posto').html("");

                                        if($('#tipo_atendimento').val() == 71){
                                            alert('Endereço não localizado pelo Google Maps, por favor insira manualmente.');
                                        }
                                    }
<?php
                                }
?>

                        }
                    }
                }

                service2.getDistanceMatrix(
                {
                    origins: [consumidor],
                    destinations: [posto],
                    travelMode: google.maps.TravelMode.DRIVING,
                    unitSystem: google.maps.UnitSystem.METRIC,
                    avoidHighways: false,
                    avoidTolls: false
                }, callback2);

            } else {
                if (type != 'cep') {
                    calcRoute('cep');
                } else {
                    if (response.originAddresses == '' ||
                        response.destinationAddresses == '') {
                        var text = 'O Endereço deste';

                        if (response.originAddresses == '') {
                            text += ' Posto Autorizado';
                        }
                        if (response.destinationAddresses == '') {
                            if (text !== 'O Endereço deste') {
                                text += ' e';
                            }
                            text += ' Consumidor';
                        }
                        text += " não foi localizado no Google Maps. O campo 'Distância KM' pode ser inserido manualmente e neste caso a Ordem de Serviço passará por auditoria do fabricante.";
                        alert(text);
                    }

                    liberar_campoKM_posto();
					$('#ida_volta').html("");
					$('#distancia_km').val('');
					$('#div_mapa_msg').html('');
					$('#div_end_posto').html("<strong>Rota:</strong> <em style='color: #ff0000;'>Não localizado</em>");
					if(!$('#posto_nome').val() == ""){
						alert('Endereço não localizado! Por favor verifique se os dados(Endereço, Cidade, Estado e CEP) do Consumidor e Posto estão corretos.');
					}
				}
            }
        }
    }
}

function vermapa_antigo(){

	var type=arguments[0];
    var posto = "";
    var consumidor = "";

    $("#GoogleMapsContainer").css({'display' : 'block'});

    directionsDisplay = new google.maps.DirectionsRenderer();
    var mapOptions = {
      zoom: 7,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
	  center: new google.maps.LatLng(-14.235004,-51.92528),
		streetViewControl:false
    };
    var map = new google.maps.Map(document.getElementById('GoogleMaps'),
        mapOptions);

    directionsDisplay.setMap(map);
    directionsDisplay.setPanel(document.getElementById('DirectionPanel'));

    if($('#contato_endereco').val() != "" || $('#contato_cidade').val() != "" || $('#contato_estado').val() == ""){
        posto += document.getElementById("contato_endereco").value;
        if($('#contato_numero').val() != ""){ posto += " "+document.getElementById("contato_numero").value; }
        if($('#contato_bairro').val() != ""){ posto += ", "+document.getElementById("contato_bairro").value; }
        posto += ", "+document.getElementById("contato_cidade").value;
        posto += ", "+document.getElementById("contato_estado").value;
    }else if($('#contato_cep').val() != ""){
        posto = $('#contato_cep').val();
    }else{
        alert("Dados insuficientes do Posto para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
        return;
    }

    if($('#consumidor_endereco').val() != "" || $('#consumidor_cidade').val() != "" || $('#consumidor_cidade').val() != ""){
        consumidor += document.getElementById("consumidor_endereco").value;
        if($('#consumidor_numero').val() != "") { consumidor += " "+document.getElementById("consumidor_numero").value; }
        //if($('#consumidor_bairro').val() != "") { consumidor += " "+document.getElementById("consumidor_bairro").value; }
        consumidor += ", "+document.getElementById("consumidor_cidade").value;
        consumidor += ", "+document.getElementById("consumidor_estado").value;
    }else if($('#consumidor_cep').val() != ""){
        consumidor = $('#consumidor_cep').val();
    }else{
        alert("Dados insuficientes do Consumidor para realizar Rota e Calculo da Distância, por favor verificar se há endereço, cidade, estado e CEP.");
        return;
    }

	if(type=='cep') {
			consumidor = $('#consumidor_cep').val();
			consumidor = "cep: "+ consumidor.replace('.','');
	}

    if(posto == ""){
        alert('Endereço do Posto não localizado! Por favor verifique se os dados es corretos!');
        return;
    }

    if(consumidor == ""){
        if($('#consumidor_cep').val() == ""){
            alert('Por favor insira o Consumidor');
            $('#consumidor_nome').focus();
            return;
        }else{
            consumidor = $('#consumidor_cep').val();
        }
    }

    var start = posto;
    var end = consumidor;

    var request = {
          origin: start,
          destination: end,
          travelMode: google.maps.DirectionsTravelMode.DRIVING
    };
	directionsService.route(request, function(response, status) {
		if(status == 'ok'){
				var km = response.routes[0].legs[0].distance.value;
				km = parseFloat(km);
				km = km /1000;
				var distancia_km = parseFloat($('#distancia_km').val());
				if(km - distancia_km > 100) {
					status = 'no';
				}
		}
         if (status == google.maps.DirectionsStatus.OK) {
            $('#DirectionPanel').html('');
            directionsDisplay.setDirections(response);
        }else{
				posto =  $('#contato_cep').val().replace(/\D/g,'');
				posto = "cep: " + posto.replace(/(\d{5})(\d{3})/,"$1-$2");
				consumidor_r =  $('#consumidor_cep').val().replace(/\D/g,'');
				consumidor = "cep: " + consumidor.replace(/(\d{5})(\d{3})/,"$1-$2");
				var request = {
				  origin: consumidor,
				  destination: posto,
				  travelMode: google.maps.DirectionsTravelMode.DRIVING
			  };
				directionsService.route(request, function(response, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					$('#DirectionPanel').html('');
					directionsDisplay.setDirections(response);
				}else{
					$("#GoogleMapsContainer").css({'display' : 'none'});
				}
			});
		}

    });

}

function fechaMapa(){

    $("#GoogleMapsContainer").css({'display' : 'none'});

}

/* Fim Google Maps */

<?php } ?>

function verifica_garantia(data_nf,produto_ref,data_abertura) {
    var ref1 = document.getElementById(data_nf).value;
    var ref2 = document.getElementById(produto_ref).value;
    var ref3 = document.getElementById(data_abertura).value;

        url = "<?=$PHP_SELF?>?ajax=valida_garantia&data_nf="+ref1+"&produto_ref="+ref2+"&data_abertura="+ref3;
        var curDateTime = new Date();
        http_forn[curDateTime] = createRequestObject();
        http_forn[curDateTime].open('GET',url,true);
        http_forn[curDateTime].onreadystatechange = function(){
            if (http_forn[curDateTime].readyState == 4)
            {
                if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                {
                    var response = http_forn[curDateTime].responseText.split("|");
                    //alert(http_forn[curDateTime].responseText);
                    if (response[0]=="ok"){
                        document.getElementById('div_garantia').style.visibility = "visible";
                        document.getElementById('div_garantia').style.position = 'static';
                    }else{
                        document.getElementById('div_garantia').style.visibility = "hidden";
                        document.getElementById('div_garantia').style.position = 'absolute';
                    }
                }
            }
        }
        http_forn[curDateTime].send(null);
}

    function adicionaIntegridade() {

        if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}

        var tbl = document.getElementById('tbl_integridade');
        var lastRow = tbl.rows.length;
        var iteration = lastRow;


        if (iteration>0){
            document.getElementById('tbl_integridade').style.display = "inline";
        }


        var linha = document.createElement('tr');
        linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

        // COLUNA 1 - LINHA
        var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
        celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'integridade_defeito_reclamado_' + iteration);
        el.setAttribute('id', 'integridade_defeito_reclamado_' + iteration);
        el.setAttribute('value',document.getElementById('defeito_reclamado').value);
        celula.appendChild(el);


        linha.appendChild(celula);

        // coluna 3 - DEFEITO RECLAMADO
        //var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
        //celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
        //linha.appendChild(celula);


        // coluna 6 - botacao
        var celula = document.createElement('td');
        celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

        var el = document.createElement('input');
        el.setAttribute('type', 'button');
        el.setAttribute('value','Excluir');
        el.onclick=function(){removerIntegridade(this);};
        celula.appendChild(el);
        linha.appendChild(celula);

        // finaliza linha da tabela
        var tbody = document.createElement('TBODY');
        tbody.appendChild(linha);
        /*linha.style.cssText = 'color: #404e2a;';*/
        tbl.appendChild(tbody);

        //document.getElementById('solucao').selectedIndex=0;
    }

    function removerIntegridade(iidd){
        var tbl = document.getElementById('tbl_integridade');
        tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

    }

    function criaCelula(texto) {
        var celula = document.createElement('td');
        var textoNode = document.createTextNode(texto);
        celula.appendChild(textoNode);
        return celula;
    }

    function checarNumero(campo){
        var num = campo.value;
        campo.value = parseInt(num);
        if (campo.value=='NaN') {
            campo.value='';
            return false;
        }
    }
//esta função estava apresentando erro para esmaltec no firefox, você digitava no campo porém não conseguia apagar.
<? /*if($login_fabrica == 30  or $login_fabrica == 51 ) {?>
    function char(nota_fiscal){
        try{var element = nota_fiscal.which    }catch(er){};
        try{var element = event.keyCode    }catch(er){};
        if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
        return false
    }
    window.onload = function(){
        document.getElementById('nota_fiscal').onkeypress = char;
    }
<? }*/?>
<? if($login_fabrica == 7) {?>
    function char(nota_fiscal){
        try{var element = nota_fiscal.which    }catch(er){};
        try{var element = event.keyCode    }catch(er){};
        if (String.fromCharCode(element).search(/[0-9]|[,]|[.]/gi) == -1)
        return false
    }
    window.onload = function(){
            document.getElementById('produto_capacidade').onkeypress = char;
            document.getElementById('divisao').onkeypress = char;
            document.getElementById('deslocamento_km').onkeypress = char;
        }
<? }?>

function verificaProdutoTroca(produto){
    var referencia = produto.value;
    var data = new Date();
    if (referencia.length > 0){
        $.ajax({
            type: "GET",
            url: "<?=$PHP_SELF?>",
            data: 'produto_referencia='+referencia+'&produto_troca=sim&data='+data.getTime(),
            complete: function(http) {
                results = http.responseText;
                if (results =='sim'){
                    document.frm_os.data_abertura.focus();
                    alert('OS irá para intervenção da Fábrica para providenciar a troca do produto e a mão-de-obra será de R$ 2,00. Caso consiga consertar o produto sem necessidade de peças, feche a OS para receber a mão-de-obra integral.') ;
                }
            }
        });
    }
}
function tipoatendimento() {
// HD 54668 para Colormaq
    var referencia = document.frm_os.produto_referencia.value;

    // Se já foi preenchido para o mesmo produto, não faz nada
    if ($('#tipo_atendimento>option').length  > 1 && $('#tipo_atendimento').attr('info_ref')==referencia) {
            return true;
    } else {
        $.ajax({
            type: "GET",
            url: "tipo_atendimento_ajax.php",
            data: "q="+referencia ,
            cache: false,
            success: function(txt) {
                $('#tipo_atendimento').html(txt).attr('info_ref', referencia);
            },
            error: function(txt) {
                alert(txt);
            }
        });
    }
}
<? if ( in_array($login_fabrica, array(11,45,50,80,120,201,172)) ){?>
        $('#data_abertura').readonly(true);
<?}?>

function dataAbertura(){
    $('#data_abertura').focus(function(){
        alert('Não é possível alterar a data da abertura');
        $('#data_abertura').readonly(true);
    }).click(function(){
        $('#data_abertura').readonly(true);
    });
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function mostraDefeitoDescricao(fabrica) {
    var referencia = document.frm_os.produto_referencia.value;
    var td = document.getElementById('td_defeito_reclamado_descricao');
    if (typeof td != "undefined") {
        td.style.display = 'none';
    }

    url = "os_cadastro_tudo_ajax.php?acao=produto&produto=" + referencia ;

    requisicaoHTTP("GET", url, true, "trataDefeitoDescricao", fabrica);
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function trataDefeitoDescricao(retorno, fabrica) {
    var td = document.getElementById('td_defeito_reclamado_descricao'); /* MLG 06/12/2010 - Declarar sempre, senão, dá erro!*/
    if (retorno == "528") {
        if (typeof td != "undefined") {
            td.style.display = 'block';
        }
    }
    else {
        if (typeof td != "undefined") {
            td.style.display = 'none';
        }
    }
}
/*
var objER = /^[0-9]{2}\.[0-9]{3}-[0-9]{3}$/;

strCEP = jQuery.trim(strCEP);
if(strCEP.length > 0){

}
*/

</script>


<script language="JavaScript">

function fnc_pesquisa_serie(campo) {//HD 256659

    //var valida = /^\d{10}[A-Z]\d{3}[A-Z]$/;
    var valida = /^\d{10}[A-Z0-9]{5}$/;

    //if (campo.value.length == 15) {
    if (campo.value.match(valida)) {

        var url = "produto_serie_pesquisa_britania.php?serie=" + campo.value;
        Shadowbox.open({
            content:url,
            player: "iframe",
            title:  "Pesquisa Serie",
            width:  800,
            height: 500
        });

    } else {

        <?php if (!in_array($login_fabrica, [3])) { ?>
          alert("A pesquisa válida somente para o serial com 15 caracteres no formato NNNNNNNNNNLNNNL ou NNNNNNNNNNNNNNN !");
        <?php } else { ?> 
          alert("Esta pesquisa é válida somente para serial com 15 dígitos, formado por Letras e Números. Caso o serial tenha menos de 15 dígitos preencha com 0 (zeros) à esquerda.");
        <?php } ?>

    }

}

function retorna_serie(referencia,descricao,voltagem,serie){
    gravaDados("produto_referencia",referencia);
    gravaDados("produto_descricao",descricao);
    gravaDados("produto_voltagem",voltagem);
    gravaDados("produto_serie",serie);

<?php
    if($login_fabrica == 3){
?>
	    /*rifica_split(referencia);*/
<?php
    }
?>
}

function pesquisaRevendaLatina(campo,tipo){
    var campo = campo.value;
    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:"pesquisa_revenda_latina.php?descricao="+campo+"&tipo="+tipo,
            player: "iframe",
            title:  "Pesquisa Revenda",
            width:  800,
            height: 500
        });
    }else
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function VerificaBloqueioRevenda(cnpj, fabrica){
  $.ajax({
      type: "POST",
      datatype: 'json',
      url: "./admin/ajax_verifica_bloquei_revenda.php",
      data: {VerificaBloqueioRevenda: true, cnpj:cnpj, fabrica:fabrica},
      cache: false,
      success: function(retorno){
          var dados = $.parseJSON(retorno);
          if(dados.retorno.length > 0){
            alert(dados.retorno);
          }
      }
  });
}

function retorna_revenda(revenda,nome,nome_fantasia,cnpj,ie,cidade,fone,fax,contato,endereco,numero,complemento,bairro,cep,estado,email,cnpj_raiz){
    gravaDados("revenda_cnpj_raiz",cnpj_raiz);
    gravaDados("revenda_cnpj",cnpj);
    gravaDados("revenda_nome",nome);
}
function verificaReincidente() {
    var produto = $('#produto_referencia').val();
    var serie = $('#produto_serie').val();
    verificador_funcao = 1;
    if(serie != ""){
      $.ajax({
          url: "<?php echo $_SERVER['PHP_SELF']; ?>?verifica_reincidencia=sim&produto="+produto+"&serie="+serie,
          cache: false,
          success: function(data) {

              retorno = data.split('|');

              if ($.trim(retorno[0]) =="ok") {

              if(retorno[1] != ""){
                $('#consumidor_nome').val(retorno[1]);
                fnc_bloqueia_campo('consumidor_nome');
              }else{
                fnc_desbloqueia_campo("consumidor_nome");
              }

              if(retorno[2] != ""){
                $('#consumidor_cpf').val(retorno[2]);
                fnc_bloqueia_campo('consumidor_cpf');
              }else{
                fnc_desbloqueia_campo("consumidor_cpf");
              }

              if(retorno[3] != ""){
                $('#consumidor_cidade').val(retorno[3]);
                fnc_bloqueia_campo('consumidor_cidade');

                $("#consumidor_cidade").html("<option value='"+retorno[3]+"'>"+retorno[3]+"</option>");

              }else{
                fnc_desbloqueia_campo("consumidor_cidade");
              }

              if(retorno[4] != ""){
                $('#consumidor_fone').val(retorno[4]).attr('readonly', true);
                fnc_bloqueia_campo('consumidor_fone');
              }else{
                fnc_desbloqueia_campo("consumidor_fone");
              }

              if(retorno[5] != ""){
                $('input[name=consumidor_celular]').val(retorno[5]);
                //$("input[name=consumidor_celular]").data('mask').remove();
				fnc_bloqueia_campo('consumidor_celular');
				<? if(!empty($erro_cel)) { ?>
					fnc_desbloqueia_campo("consumidor_celular");
				<?	} ?>
              }else{
                fnc_desbloqueia_campo("consumidor_celular");
                //$("#consumidor_celular").mask("(99) 9999-9999");
              }

              if(retorno[6] != ""){
                $('input[name=consumidor_fone_comercial]').val(retorno[6]);
                fnc_bloqueia_campo('consumidor_fone_comercial');
              }else{
                fnc_desbloqueia_campo("consumidor_fone_comercial");
              }

            if(retorno[7] != ""){
                $('#consumidor_estado').val(retorno[7]);
                $("#consumidor_estado option").hide();
                $("#consumidor_estado option:selected").show();
                fnc_bloqueia_campo('consumidor_estado');
            }else{
                fnc_desbloqueia_campo("consumidor_estado");
            }

            if(retorno[8] != ""){
                $('#consumidor_endereco').val(retorno[8]).attr('readonly', true);
                fnc_bloqueia_campo('consumidor_endereco');
            }else{
                fnc_desbloqueia_campo("consumidor_endereco");
            }

            if(retorno[9] != ""){
                $('#consumidor_numero').val(retorno[9]);
                fnc_bloqueia_campo('consumidor_numero');
            }else{
                fnc_desbloqueia_campo("consumidor_numero");
            }

            if(retorno[10] != ""){
                $('#consumidor_complemento').val(retorno[10]);
                fnc_bloqueia_campo('consumidor_complemento');
            }else{
                fnc_desbloqueia_campo("consumidor_complemento");
            }

            if(retorno[11] != ""){
                $('#consumidor_bairro').val(retorno[11]);
                fnc_bloqueia_campo('consumidor_bairro');
            }else{
                fnc_desbloqueia_campo("consumidor_bairro");
            }

              if(retorno[12] != ""){
                $('#consumidor_cep').val(retorno[12]);
                fnc_bloqueia_campo('consumidor_cep');
              }else{
                fnc_desbloqueia_campo("consumidor_cep");
              }

            if(retorno[14] != ""){
                $('#nota_fiscal').val(retorno[14]);
                fnc_bloqueia_campo('nota_fiscal');
            }else{
                fnc_desbloqueia_campo("nota_fiscal");
            }

            if(retorno[15] != ""){
                $('#data_nf').val(retorno[15]);
                fnc_bloqueia_campo('data_nf');
            }else{
                fnc_desbloqueia_campo("data_nf");
                $("#data_nf").datepick();
            }

              if(retorno[18] != ""){
                $('#consumidor_email').val(retorno[18]);
                fnc_bloqueia_campo('consumidor_email');
              }else{
                fnc_desbloqueia_campo("consumidor_email");
              }

              if(retorno[20] != ""){
                $('#produto_referencia').val(retorno[20]);
                fnc_bloqueia_campo('produto_referencia');
              }else{
                fnc_desbloqueia_campo("produto_referencia");
              }

              if(retorno[21] != ""){
                $('#produto_descricao').val(retorno[21]);
                fnc_bloqueia_campo('produto_descricao');
              }else{
                fnc_desbloqueia_campo("produto_descricao");
              }

              if(retorno[22] != ""){
                $('#produto_voltagem').val(retorno[22]);
                fnc_bloqueia_campo('produto_voltagem');
              }else{
                fnc_desbloqueia_campo("produto_voltagem");
              }

              if(retorno[25] != ""){
                var novo = document.createElement("option");
                novo.value = retorno[25];  // atribui um valor
                novo.text  = retorno[26]; // atribui  um texto
                document.forms[0].defeito_reclamado.options.add(novo);
                $('#defeito_reclamado').val(retorno[25]);
              }

              if(retorno[13] != ""){
                $('#revenda_cnpj_pesquisa').val(retorno[13]).attr('readonly', true);
                fnc_pesquisa_revenda_fabrica();
                setTimeout(function(){ fnc_bloqueia_campos_revenda(); }, 500);
              }

            }else{
                if( $('#produto_referencia').val() != "" ){
                    if( confirm('Deseja limpar os dados já inseridos') ){
                        fnc_limpa_campos_consumidor();
                        fnc_limpa_campos_revenda();
                        setTimeout(function(){ fnc_desbloqueia_campos_consumidor(); }, 500);
                        setTimeout(function(){ $('#revenda_cnpj_pesquisa').val('').removeAttr('readonly'); }, 500);
                    } else {
                      setTimeout(function(){ fnc_desbloqueia_campos_consumidor(); }, 500);
                      setTimeout(function(){ $('#revenda_cnpj_pesquisa').val('').removeAttr('readonly'); }, 500);
                    }
                }else{
                    fnc_limpa_campos_consumidor();
                    fnc_limpa_campos_revenda();
                    setTimeout(function(){ fnc_desbloqueia_campos_consumidor(); }, 500);
                    setTimeout(function(){ $('#revenda_cnpj_pesquisa').val('').removeAttr('readonly'); }, 500);
                }
	    }
        }
    });
    }
}

function verificaOrigem(){
    var referencia   = $("#produto_referencia").val();
    var rel = $('#produto_serie').attr("rel");
    if (referencia.length > 0){
        $.ajax({
            url: "<?=$PHP_SELF?>?produto_referencia="+referencia+"&verifica_origem=sim",
            cache: false,
            success: function(data) {
                if (data.trim() == 'NAC'){
                    //$('#produto_serie').attr("maxlength","14");
                    $('#produto_serie').attr("rel","NAC");
                }else if(data.trim() == 'IMP'){
                    //$('#produto_serie').attr("maxlength","12");
                    $('#produto_serie').attr("rel","IMP");
		}else{
                    return false;
                }

                $('#produto_serie').attr("readonly",false);
                //$('#produto_serie').val("");
                $("#div_aviso_serie").show();
            }
        });
    }else{
        $("#displayArea").html("Informe o produto antes de digitar o número de série");
    }
}

  <?php if($login_fabrica == 140){ ?>

    $(function(){
      $('#produto_referencia').change(function(){
        var serie = $('#produto_serie').val();
        if(serie != ""){
          verificaNS(serie);
        }
        $('#produto_serie').attr('readonly', false);
      });
    })

    function verificaNS(serie){

      var referencia = $('#produto_referencia').val();

      if(referencia == ""){
        $('#produto_referencia').focus();
        alert("Por favor insira um Produto!");
        return;
      }

      $.ajax({
        url : "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: "POST",
        data: {
          verifica_serie : "ok",
          serie : serie,
          referencia : referencia
        },
        complete: function(data){
          data = data.responseText;
          if(data == "invalido"){
            $('#produto_serie').focus();
            alert("Número de Série Inválido, caso o mesmo não seja corrigido a OS entrará em Auditoria");
          }
        }
      });

    }

  <?php } ?>

  <?php if($login_fabrica == 30){?>
    $(function() {
      var verificaTipoAt = $("#tipo_atendimento").val();

      if(verificaTipoAt == 41){
        $("#div_mapa").show();
        //calcRoute();
      }
    });
  <?php }?>

  <?php if (in_array($login_fabrica, array(72))) { ?>
      $(function() {
        $("#consumidor_cpf").on("focus", function() {
          $("#consumidor_cpf").data('mask').remove();
        });
        alterarMascara();
        $("#consumidor_cpf").on("change, blur", function() {
          alterarMascara();
        });
      });

      function alterarMascara() {
        if ($("#consumidor_cpf").val().length >= 14) {
          $("#consumidor_cpf").mask("99.999.999/9999-99");
        } else if ($("#consumidor_cpf").val().length == 11) {
          $("#consumidor_cpf").mask("999.999.999-99");
        }
      }
  <?php }?>

  <?php if (in_array($login_fabrica, array(24))) { ?>
        function fn_valida_consumidor_cpf(cpf_consumidor, nome_consumidor){

          var retorno;

          if (cpf_consumidor == '' || cpf_consumidor == 'undefined' ) {
              return 'vazio';
          }

          $.ajax({
              async: false,
              url : "<?php echo $_SERVER['PHP_SELF']; ?>",
              type: "POST",
              data: {
                valida_consumidor_cpf : "ok",
                cpf_consumidor : cpf_consumidor,
                nome_consumidor : nome_consumidor,
                ajax : true
              },
              complete: function(data){
                  data = data.responseText.split('|');
                  if(data[0] == "erro"){
                      if (confirm(data[1])) {
                          retorno = true;
                      } else {
                          retorno = false;
                      }
                  }
                  if (data[0] == "errosql") {
                      alert(data[1]);
                      retorno = false;
                  }
                  if (data[0] == "ok") {
                      retorno = true;
                  }
              }
          });

          return retorno;
      }

  <?php
  }?>

</script>

<?php if($calculoKM == "t"){ ?>

<style type="text/css">
    #GoogleMapsContainer{
        z-index: 888;
        position: absolute;
        width: 700px;
        height: 400px;
        border: 2px solid #000;
        font-size: 12px;
        margin-left: 80px;
        top: 300px;
    }
    #DirectionPanel{
        width: 250px;
        height: 400px;
        float: right;
        background-color: #fff;
        overflow: auto;
        font: 10px arial;
    }
    #GoogleMaps{
        width: 700px;
        height: 400px;
        float: left;
        margin-top: -33px;
        background-color: #fff;
    }
    #fechamapa:hover{
        cursor: pointer;
    }
    .adp-text{
        font: 12px arial;
    }
    .adp-substep{
        font: 12px arial;
    }
</style>

<?php } ?>

<?php
    /**
     * include do arquivo que valida os campos obrigatorios
     * o arquivo usa o array $campos_telecontrol que armazena os campos obrigatorios de cada fabrica
     * o array $campos_telecontrol está no arquivo valida_campos_obrigatorios.php
    **/
    if ($fabricas_validam_campos_telecontrol) {
        include "javascript_valida_campos_obrigatorios.php";
    }
?>
<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
        Verifica a existência de uma OS com o mesmo número e em
        caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?php
//if ($ip == '201.0.9.216') echo $msg_erro;

if (strlen ($msg_erro) > 0) {
    if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
    <td valign="middle" align="center" class='error' id='erro_msg_'>
<?php
    if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
        $produto_referencia = trim($_POST["produto_referencia"]);
        $produto_voltagem   = trim($_POST["produto_voltagem"]);
        $sqlT =    "SELECT tbl_lista_basica.type
                FROM tbl_produto
                JOIN tbl_lista_basica USING (produto)
                WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
                AND   tbl_produto.voltagem = '$produto_voltagem'
                AND   tbl_lista_basica.fabrica = $login_fabrica
                AND   tbl_produto.ativo IS TRUE
                GROUP BY tbl_lista_basica.type
                ORDER BY tbl_lista_basica.type;";
        $resT = @pg_query ($con,$sqlT);


        if (pg_num_rows($resT) > 0) {
            $s = pg_num_rows($resT) - 1;
            for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
                $typeT = pg_fetch_result($resT,$t,type);
                $result_type = $result_type.$typeT;

                if ($t == $s) $result_type = $result_type.".";
                else          $result_type = $result_type.",";
            }
            $msg_erro .= "<br />Selecione o Type: $result_type";
        }
    }

    // retira palavra ERROR:
    if (strpos($msg_erro,"ERROR: ") !== false) {
        if( !in_array( $login_fabrica, array(98,108,111,126) ) )
           // $erro = "Foi detectado o seguinte erro:<br />";
          $msg_erro = str_replace("ERROR:", "", $msg_erro);
        //$msg_erro = substr($msg_erro, 6);
    }

    // retira CONTEXT:
    if (strpos($msg_erro,"CONTEXT:")) {
        $x = explode('CONTEXT:',$msg_erro);
        $msg_erro = $x[0];
    }

    if( in_array($login_fabrica, array(3,11,126,172)) ){
      if(strlen($msg_erro)){
        if(strlen(trim($_FILES["img_os_1"]["name"])) > 0  ||  strlen(trim($_FILES["img_os_2"]["name"])) > 0){
          $msg_erro .= "<br/>Selecione novamente os Anexos.";
        }
      }
    }

    if (in_array($login_fabrica, [24])) {
      $atendimento = $_POST['tipo_atendimento'];
      $hasDistance = isset($_POST['distancia_km']) ? true : false;

      if (isset($atendimento)) {
        $sqlAtendimento = "SELECT descricao
                          FROM tbl_tipo_atendimento 
                          WHERE tipo_atendimento = {$atendimento}";

        $resAtendimento = pg_query($con, $sqlAtendimento);

        $atendimento = pg_fetch_result($resAtendimento, 0, descricao);

        if (trim($atendimento) == 'Garantia com deslocamento' && !$hasDistance) {
          $msg_erro = "É necessário inserir a distância <br/> ";
        }
      }
    }

    // echo "<!-- ERRO INICIO //-->";
    //echo $erro . $msg_erro . "<br /><!-- " . $sql . "<br />" . $sql_OS . " -->";
    if($login_fabrica == 88 or $login_fabrica == 95 OR $login_fabrica > 96){
        $msg_erro = str_replace("Erro:", "",$msg_erro);
        $erro = str_replace("Erro: ", "",$erro);
        echo $erro . $msg_erro;
    }else{
        if (trim($msg_erro) == 'Foi detectado erro na abertura de sua OS, favor verificar dados do produto, (codigo de referência, modelo e número de serie)') {
            echo $msg_erro;
        } else {
            echo $erro . $msg_erro;
        }
    }
    // echo "<!-- ERRO FINAL //-->";
?>
    </td>
</tr>
</table>

<? }else{?>
<table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700' style="display:none" id="tbl_erro_msg">
<tr>
    <td valign="middle" align="center" class='error' id='erro_msg_'>
    </td>
</tr>
</table>

<?php

}


$sql  = "SELECT TO_CHAR(current_timestamp, 'DD/MM/YYYY')";
$res  = @pg_query($con, $sql);
$hoje = @pg_fetch_result($res, 0, 0);

//Chamado 1982
if( $login_fabrica == 15 ){
?>
<div id="layout">
    <div class="content">
     Duvidas e sugestões, envie um e-mail para telecontrol@latina.com.br
    </div>
</div>

<?php
}
?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <td><img height="1" width="20" src="imagens/spacer.gif"></td>

    <td valign="top" align="left">
<?php

        if ($login_fabrica == 24 and $login_posto == 6359) {

        echo "<br /><br /><table width='600' border='0' cellpadding='3' cellspacing='5' align='center' bgcolor='#ecc3c3'>";
            echo "<tr>";
            echo "<td valign='middle' align='center'>";
            echo "<font face='Arial, Helvetica, sans-serif' color='#d03838' size='1'><B>Atenção:</B> Este programa é específico para lançamento de ORDEM DE SERVIÇO DE CONSUMIDOR,<br /> caso a ordem de serviço seja de REVENDA <a href='os_revenda.php'>clique aqui</a>. </font>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";

        }?>

        <?php
            if($login_fabrica == 72){
                echo "<br /><div class='texto_avulso' id='div_aviso_serie'>
                        <p style='font-size:18px;font-weight:bold;'>Importante! </p>
                        <p>&nbsp;</p>
                        <p style='font-size:12.5px;font-weight:bold'>O Nº de Série é obrigatório, pedimos que informem o nº correto. Este poderá ser encontrado na etiqueta do produto.</p>
                      </div>";
            }

            $sql = "SELECT latitude||','||longitude AS LatLng FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";

            $res = pg_query($con, $sql);
            $LatLngPosto = pg_result($res, 0, 'LatLng');
        ?>

        <!-- ------------- Formulário ----------------- -->

        <form style="margin: 0px;" name="frm_os" id="frm_os" method='post' enctype="multipart/form-data" action="<? echo $PHP_SELF ?>">

        <?php

        if(in_array($login_fabrica, array(11,172))){
          echo "código Interno <br /> <input type='text' name='codigo_interno' value='{$codigo_interno_digitado}' readonly='readonly' />";
        }

        ?>

        <input class="frm" type="hidden" name="os" value="<? echo $os; ?>">
        <input class="frm" type="hidden" name="pre_os" value="<? echo $pre_os; ?>">
        <?if ($login_fabrica == 52 or $login_fabrica == 96 or $login_fabrica == 30) { ?>
            <input class="frm" type="hidden" id='cliente_admin' name="cliente_admin" value="<? echo $cliente_admin; ?>">
            <input class="frm" type="hidden" id='admin' name="admin" value="<? echo $admin; ?>">
        <?if($login_fabrica != 96){?>
            <input class="frm" type="hidden" id='qtde_km' name="qtde_km" value="<? echo $qtde_km; ?>">
        <? }}?>
        <input class="frm" type="hidden"  id="hd_chamado" name="hd_chamado" value="<? echo $hd_chamado; ?>">
        <input class="frm" type="hidden" name="hd_chamado_item" value="<? echo $hd_chamado_item; ?>">
        <input type="hidden" name="LatLngPosto" id="LatLngPosto" value="<?=$LatLngPosto;?>">
        <?php

        if ($login_fabrica == 1 && $tipo_os == "7") {
            echo "<input type='hidden' name='locacao' value='$tipo_os'>";
        }

        if ($login_fabrica == 19) { ?>
          <input type="hidden" name="garantia_lorenzetti" id="garantia_lorenzetti" value="<?= $garantia_lorenzetti ?>" />
        <?php
        }

        if ($login_fabrica == 3) {
            echo "<center><table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
            echo "<tr>";
            echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
            echo "Não é permitido abrir Ordens de Serviço com data de abertura superior a 20 dias.";
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center' style='font-color:#ffffff ; font-size:12px'>";
            echo "&nbsp;";
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center' bgcolor='#FFA54F' style='font-color:#ffffff ; font-size:12px'>";
            echo "Informe o <font color='#FF0000'><b>Número de Série</b></font> do produto antes de digitar os demais dados da OS";
            echo "</td>";
            echo "</tr>";
            echo "</table></center>";
        }

        if ($login_fabrica == 79) {
            echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
            echo "<tr>";
            echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
            echo "*campos obrigatórios - as informações que o consumidor não fornecer deverão ser preenchidas com as informações do Posto Autorizado: e-mail, CNPJ, telefone. ";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        }

        if ($distribuidor_digita == 't') {?>

            <table width="100%" border="0" cellspacing="5" cellpadding="0">
                <tr valign='top' style='font-size:12px'>
                    <td valign='top'>
                        Distribuidor pode digitar OS para seus postos.
                        <br />
                        Digite o código do posto
                        <input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
                        ou deixe em branco para suas próprias OS.
                    </td>
                </tr>
            </table><?php

        } ?>

        <br /><?php

        if ($login_fabrica == 74) { //hd 377814

            echo '<div style="display:none; width:700px;margin:auto;text-align:center;font-weight:bold;" id="data_fabricacao_opener">
                        Data de Fabricação do Produto
                        <p>&nbsp;</p>
                  </div>';
        } ?>

        <table width="100%" border="0" cellspacing="5" cellpadding="2">

        <? if ($login_fabrica == 7) { // HD 75762 para Filizola ?>
            <tr>
                <td nowrap  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Classificação da OS</font></td>
            </tr>
            <tr>
                <td>
                    <select name='classificacao_os' id='classificacao_os' size="1" class="frm">
                        <option <? if (strlen($classificacao_os)==0 && $login_fabrica != 7) {echo "selected";} ?>></option><?php
                        $sql = "SELECT    *
                                FROM    tbl_classificacao_os
                                WHERE    fabrica = $login_fabrica
                                AND        ativo IS TRUE
                                ORDER BY descricao";

                        $res = @pg_query ($con,$sql);

                        if($login_fabrica == 7){
                            $descricao_default = "GARANTIA";
                        }

                        if (pg_num_rows($res) > 0) {
                            for ($i = 0; $i < pg_num_rows($res); $i++) {

                                $xclassificacao_os=pg_fetch_result($res, $i, 'classificacao_os');

                                if($login_fabrica == 7){
                                    $xdescricao_default = pg_fetch_result($res,$i,descricao);
                                }else{
                                    $xdescricao_default = "";
                                }


                                if ($xclassificacao_os == 5 and $classificacao_os != 5) {
                                    continue;
                                }

                                echo "<option value='$xclassificacao_os'";
                                if ($classificacao_os == $xclassificacao_os){
                                    echo " selected";
                                } elseif($login_fabrica == 7 && $descricao_default == $xdescricao_default){
                                    echo " selected";
                                }
                                echo ">".pg_fetch_result($res,$i,descricao)."</option>\n";

                            }

                        }
?>

                    </select>
                </td>
            </tr>
<?php
        }

        if (in_array($login_fabrica, [42])) {
?>

            <tr>
                <td align='left' id="mostra_tipo_atendimento">
                    <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif">Tipo Atendimento</font><br />
                    <select name="tipo_atendimento" id='tipo_atendimento' class='frm' style='width:220px;' onChange="verifica_atendimento();">
                        <option></option>
<?php

            if ($cook_entrega_tecnica == 'f') {
                $sql_entrega_tecnica .= " AND entrega_tecnica = 'f' ";
            } else {
                if ($cook_tipo_posto_et == "t") {
                    $sql_entrega_tecnica .= " AND entrega_tecnica = 't' ";
                }
            }

            $sql = "SELECT *
                    FROM tbl_tipo_atendimento
                    WHERE fabrica = $login_fabrica
                    AND   ativo IS TRUE
                    $sql_add1
                    $sql_deslocamento
                    $sql_entrega_tecnica
                    ORDER BY tipo_atendimento ";

            $res = pg_query ($con, $sql);

            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                $codigo  = str_pad(pg_fetch_result($res, $i, 'codigo'), 2, '0', STR_PAD_LEFT);
                $desc    = pg_fetch_result($res, $i, 'descricao');
                $tipo_at = pg_fetch_result($res, $i, 'tipo_atendimento');
                $entrega_tecnica = pg_fetch_result($res, $i, "entrega_tecnica");

                $txt_option = $codigo." - ".$desc;
                $opt_sel    = ($tipo_atendimento == $tipo_at) ? ' SELECTED':'';

                echo "<option value='$tipo_at' rel='$entrega_tecnica' $opt_sel >$txt_option</option>";

            }
?>
                    </select>
                </td>
                <td colspan="100%" style="text-align:left;">
<?PHP
            $checked_cortesia = ($os_cortesia == "t")
                ? "checked"
                : "";
?>
                    <input type='checkbox' value='t' name='os_cortesia' class='frm' <?=$checked_cortesia?>><font size="1" face="Geneva, Arial, Helvetica, san-serif">Solicitação de Cortesia Comercial</font>
                </td>
            </tr>
<?php

        }
        //if($login_fabrica == 24){ ?>
            <!--<tr>
              <td nowrap valign="top" >
                <font size='1' color="red" face='Geneva, Arial, Helvetica, san-serif'>
                    <span rel='numero_serie' >Número de Série</span>
                </font>
                <bR>
                <input class="frm" type="text" name="numero_serie" id="numero_serie" size="15" maxlength="20" value="<? echo $numero_serie ?>"
              </td>
              <td colspan='3' id="msg_erro_produto_serie"></td>
            </tr>-->

        <?php //}

        /*HD - 4276928*/
        if ($login_fabrica == 91) { ?>

          <script>
            function alterarLabelWanke() {
              var consumidor_revenda = $("input[name=consumidor_revenda]:checked").val();
              var descricao_campo = "";
              //$("#consumidor_cpf").val("");

              if (consumidor_revenda == "C") {
                descricao_campo = "CPF Consumidor";
                $("#consumidor_cpf").mask("999.999.999-99");
              } else {
                descricao_campo = "CNPJ Consumidor";
                $("#consumidor_cpf").mask("99.999.999/9999-99");
              }

              $("span[rel=consumidor_cpf").html(descricao_campo);
            }
            $(function(){
              alterarLabelWanke();
	      $("#data_abertura").datepick();
              $("input[name=consumidor_revenda]").click(function() {
                alterarLabelWanke();
              });

            });
          </script>

          <tr>
            <td nowrap="" valign="top" align="left">
              <font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>&nbsp;&nbsp;
              <input type='radio' name='consumidor_revenda' value='C'
                <?= ($_POST['consumidor_revenda'] == 'C' || empty($_POST['consumidor_revenda'])) ? 'checked' : '' ?>
              >
              <font size='1' face='Geneva, Arial, Helvetica, san-serif'>ou&nbsp;&nbsp;</font>
              <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Revenda</font>&nbsp;&nbsp;
              <input type='radio' name='consumidor_revenda' value='R'
                <?= ($_POST['consumidor_revenda'] == 'R') ? 'checked' : '' ?>
              >&nbsp;&nbsp;&nbsp;
            </td>
          </tr>
        <?php }

        if ($pedir_sua_os == 't' && ($login_fabrica != 86 && $login_fabrica != 101 && $login_fabrica < 104 )) {
?>
            <td nowrap  valign='top'>

                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='sua_os'>OS Fabricante</span></font>
                <br />
                <input  name="sua_os" id='sua_os' class ="frm" type ="text" size ="10" <?if ($login_fabrica==5){?> maxlength="6" ReadOnly onclick="alert('Mantenha esse campo em branco para geração automática de número de ordem de serviço.\nCaso tenha alguma dúvida, entrar em contato com a Mondial através do 0800-7707810 ou ata@mondialline.com.br');" <?}else{?> maxlength="20"<?}?> value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');"><?php
                } else {
                    echo "&nbsp;";
                    echo "<input type='hidden' name='sua_os'>"; ?>
            </td>
            <?}

            if ( strlen( trim($data_abertura)) == 0 and isFabrica(7, 14, 19, 30, 59, 85, 72, 80)) {
                $data_abertura = $hoje;
            }

            if (in_array($login_fabrica,array(3,6,50,56,43,95,120,201))) {
                $maxSerie = $login_fabrica == 6 ? 9 : 20;
?>

                <td nowrap valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="produto_serie">N. Série</span></font>
                    <br />
                    <input class="frm" type="text" name="produto_serie"  id="produto_serie" size="12" maxlength="<?=$maxSerie?>"
                           value="<? echo $produto_serie ?>"
                         onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');"
                          onblur="this.className='frm'; displayText('&nbsp;');<?
                            if ($login_fabrica == 50  or in_array($login_fabrica,[120,201])) echo 'pesquisaSerie(document.frm_os.produto_serie);';
                            if ($login_fabrica == 3) echo 'verificaReincidente();';
							?>" <? if ($login_fabrica == 3) echo "onkeyup='javascript:somenteMaiusculaSemAcento(this);'"; ?>
								<? if ($login_fabrica == 6) echo "onkeyup='javascript:mascara(this,soNumeros);'"; ?> >
                    <? if(in_array($login_fabrica,array(74,95))) { ?><img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_serie_atlas (document.frm_os.produto_serie, document.frm_os.produto_referencia,document.frm_os.produto_descricao)"><? } ?>   &nbsp;
                    <? if(in_array($login_fabrica,array(3,6,43,50,120,201))) { ?>
                        <img src='imagens/lupa.png' border='0' align='absmiddle'   style='cursor: pointer' <?
                        if($login_fabrica==6) { ?>
                        onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os');"></A>
                        <?} else if ($login_fabrica == 50 or in_array($login_fabrica,[120,201])){?>
                            onclick="javascript: pesquisaSerie (document.frm_os.produto_serie);"></a>

                        <? } else if($login_fabrica == 3){?>
                            onclick='fnc_pesquisa_serie(document.frm_os.produto_serie)' style='cursor: pointer' onBlur="upperMe()"
                        <?}else{?>
                            onclick="javascript: fnc_pesquisa_numero_serie (document.frm_os.produto_serie, 'produto_serie');" ></a>
                        <?}

                    }

                    if($login_fabrica == 56) { ?>
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie56 (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'></A>
                    <?}?>
                </td><?php

            }

            if ($login_fabrica == 19 OR ($login_fabrica == 1 AND $login_posto == 6359)) {?>
                <td nowrap align='center'  valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
                    <br />
                    <input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>"
                          onblur="this.className='frm'; displayText('&nbsp;');"
                         onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.');"
                        onchange='tipo_atendimento_produto()'>
                </td><?php
            }

            if ($login_fabrica ==2) { ?>
            <td nowrap  valign='top'>
                <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
                <br />
                <input class="frm" type="text" name="produto_serie" id="produto_serie"  size="8" maxlength="<?=($login_fabrica==35) ? '12' : '20' ?>" value="<? echo $produto_serie ?>" <?
                if ($login_fabrica == 50 or $login_fabrica == 43) {
                ?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');<?
                if ($login_fabrica == 3 and $login_posto == 6359) {
                    echo " MostraEsconde('dados_1');";
                }
                echo " verificaPreOS();";
                if ($login_fabrica==7 /*and 1==2*/) {
                    echo "verificaProduto(document.frm_os.produto_referencia,this)";
                } ?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); <?
                if ($login_fabrica == 3 and $login_posto == 6359) {
                    echo " MostraEsconde('dados_1');";
                } ?> "><? if($login_fabrica == 25) { ?>
                &nbsp;<INPUT TYPE="button" onClick='javascritp:fn_verifica_garantia();' name='Verificar' value='Verificar' <? if($login_fabrica ==3) echo "rel=serie";?>>
                <? } ?>
                <br /><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
                <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
                </div>
                <? if ($login_fabrica == 35){
                    echo "<div width='100' style='font-size: 9px;'>Encontra-se na etiqueta de voltagem do aparelho.<br />
                    Caso o produto não possua número de série,<br /> entre em contato com o fabricante.</<div>";
                }
            }

            if ($login_fabrica <> 15) {
?>
              <td nowrap  valign='top' align='left'>
<?php
                if ($login_fabrica == 3) {
?>
                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>
<?
                } else {
                    if ($login_fabrica == 30) {
?>
                        <acronym title='Campo Obrigatório'>
                            <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>
                        </acronym>
<?
                    } else {
                      $color_ref = '';
                      if ($login_fabrica == 19) {
                        $color_ref = "color = 'red'";
                      }
?>
                        <font size='1' <?=$color_ref?> face='Geneva, Arial, Helvetica, san-serif'>
                            <span rel='produto_referencia'><?=traduz("Referência do Produto")?></span>
                        </font>
<?
                    }
                }

                if(strlen($produto_ci) > 0){

                  $sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto_ci} AND fabrica_i = {$login_fabrica}";
                  $res_produto = pg_query($con, $sql_produto);

                  if(pg_num_rows($res_produto) > 0){

                    $produto_referencia = pg_fetch_result($res_produto, 0, "referencia");
                    $produto_descricao  = pg_fetch_result($res_produto, 0, "descricao");

                  }

                }

                // verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
                $arquivo_comunicado  = "";
                $arquivo_comunicadoi = "";

                if (strlen ($produto_referencia) > 0) {
                    $sql =" SELECT  tbl_comunicado.comunicado,
                                    tbl_comunicado.extensao
                            FROM    tbl_comunicado
                            JOIN    tbl_produto USING(produto)
                            WHERE   tbl_produto.referencia  = '$produto_referencia'
                            AND     tbl_comunicado.fabrica  = $login_fabrica
                            AND     tbl_comunicado.ativo    IS TRUE";

                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res) > 0) {
                        $arquivo_comunicado= "HÁ ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
                    }

                }
?>

                    <br />
<?php
                if ($login_fabrica == 1 AND strlen($os) > 0) {
?>
                    <input class="frm" type="text" name="produto_referencia"  id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly><?php
                } else {
?>
                    <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
<?
                    if (($login_fabrica == 30) && (strlen($_GET['os']) > 0 )) {
?>
                    <input readonly="readonly" class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"
<?
                    }else{
?>
                    <input class="frm" type="text" style='margin: -10px' name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"
<?
                    }
                    if ($login_fabrica == 50) {
?>
                    onChange="javascript: this.value=this.value.toUpperCase(); resetaDefeito();"
<?
                    } else {

                        #HD 424887 - INICIO

                        /* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
                        NÃO HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

                        if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){
                            if($login_fabrica <> 3){
                                echo   " onChange=\"resetaDefeito();\" ";
                            }
                        }

                        #HD 424887 - FIM

                    }
?>
                    onblur="this.className='frm'; displayText('&nbsp;');
<?php
                    if ($login_fabrica == 5){
?>
                        checarFoto(<? echo $login_fabrica ?>) ;
<?
                    }
                    if ( !in_array($login_fabrica, array(11,172)) ){ // HD 68996
?>
                        checarComunicado(<? echo $login_fabrica ?>);
<?
                    }

                    if($login_fabrica==24) {
?>
                        pesquisaProduto(document.frm_os.produto_referencia,'referencia');
<?
                    }
                    if($login_fabrica==7) {
?>
                        busca_valores(this);
                        verificaProduto(document.frm_os.produto_referencia,this);
<?
                    }
?>
                    " onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?> >&nbsp;
<?php
					if($login_fabrica != 3) {
?>
                    <img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: pesquisaProduto(document.frm_os.produto_referencia,'referencia');" />
<?
					}else{
						echo "&nbsp;&nbsp;&nbsp;";
					}
                }
                if ( !in_array($login_fabrica, array(11,172)) ){ // HD 68996
?>
                    <img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0'
                        align='absmiddle'  title="NÃO HÁ COMUNICADOS PARA ESTE PRODUTO"
                        onclick="javascript:abreComunicado()"
                        style='cursor: pointer;'>
                    <input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
<?php
                }
                if ($login_fabrica == 5){# HD 50627
?>
                    <img src='imagens/picture_mach.gif' id="img_produto" target="_blank" name='img_produto' border='0'
                        align='absmiddle' title="NÃO HÁ FOTO PARA ESTE PRODUTO"
                        onclick="javascript:abreFoto()"
                        style='cursor: pointer;'>
                    <input type="hidden" name="link_foto" value="<? echo $link_foto; ?>">
<?php
                }
?>
                </td>
<?
                if ($login_fabrica == 96){ //HD 746279
?>
                <td>
                    <font size="1" face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>
                    <br />
                    <input type="text" name="referencia_fabrica" id="referencia_fabrica" value="<?=$referencia_fabrica?>"/>
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_modelo (document.frm_os.referencia_fabrica,'frm_os')" style='cursor: pointer'>

                </td>
<?
                }
?>
                <td nowrap valign='top'>
<?php
                if ($login_fabrica == 3) {
?>
                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>&nbsp;&nbsp;&nbsp;Modelo do Produto</font>
<?
                } else {
                    if ($login_fabrica == 30) {
?>
                    <acronym title='Campo Obrigatório'>
                        <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>
                    </acronym>
<?
                    }else{
                      $left_produto = '';
                      if ($login_fabrica == 19) {
                        $left_produto = "style='margin-left: 10px;'";
                      }
?>
                    <font <?=$left_produto?> size='1' color="red" face='Geneva, Arial, Helvetica, san-serif'>
                        <span rel='produto_descricao'><?=traduz("Descrição do Produto")?></span>
                    </font>
<?
                    }
                }
?>
                    <br />
<?
                if ($login_fabrica == 1 AND strlen($os) > 0) {
?>
                    <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly>
<?
                }else{
?>
                    <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
<?
                    if (($login_fabrica == 30) && (strlen($_GET['os']) > 0 )) {
?>
                   <input readonly="readonly" class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>"
<?
                    }else{
?>
                        <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>"
<?
                    }
                    if($login_fabrica==50){
?>
                            onChange="javascript: this.value=this.value.toUpperCase();resetaDefeito();"
<?
                    } else {

                        #HD 424887 - INICIO

                        /* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
                        A CHAMADA DESTA FUNÇÃO "listaDefeitos" SERÁ FEITA NO ONBLUR DO PRODUTO, POIS NÃO
                        HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

                        if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){
                            if($login_fabrica <> 3){
                                echo " onChange=\"resetaDefeito();\" ";
                            }
                        }

                        #HD 424887 - FIM

                    }
?>
                        onblur="this.className='frm'; displayText('&nbsp;');
<?

                    if($login_fabrica==7) {
?>
                            busca_valores();
                            verificaProduto(document.frm_os.produto_referencia,this);
<?
                    }
                    if ($login_fabrica == 40){
?>
                        verifica_familia_atendimento(document.frm_os.produto_referencia.value);
<?
                    }
?>
                    "onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');
<?php
                    if ($login_fabrica == 5){
?>
                        checarFoto(<? echo $login_fabrica ?>) ;
<?
                    }
                    if ( !in_array($login_fabrica, array(11,172)) ){ // HD 68996
?>
                        checarComunicado(<? echo $login_fabrica ?>);
<?
                    }
?>
                    "
<?
                    if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;
<?php
					if($login_fabrica != 3) {
?>
                    <img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: pesquisaProduto(document.frm_os.produto_descricao,'descricao');"></A>
<?
					}
                }
?>
                </td>
<?
            }else{
?>
                <td nowrap valign='top'>
                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font><br />
                    <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="60" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');<?echo (in_array($login_fabrica, array(15))) ? "verifica_familia_atendimento(document.frm_os.produto_referencia.value);" : "";?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_os.produto_descricao,'descricao');"  style='cursor: pointer'></A>
                </td>
                <td nowrap  valign='top'>

                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>

                    <?// verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
                    $arquivo_comunicado="";
                    $arquivo_comunicado="";
                    if (strlen ($produto_referencia) >0) {
                        $sql ="SELECT tbl_comunicado.comunicado, tbl_comunicado.extensao
                            FROM  tbl_comunicado JOIN tbl_produto USING(produto)
                            WHERE tbl_produto.referencia = '$produto_referencia'
                            AND tbl_comunicado.fabrica = $login_fabrica
                            AND tbl_comunicado.ativo IS TRUE";
                        $res = pg_query($con,$sql);
                        if (pg_num_rows($res) > 0)
                            $arquivo_comunicado= "HÁ ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
                    } ?>
                    <br />
                    <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');verifica_familia_atendimento(document.frm_os.produto_referencia.value);"  onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?> />&nbsp;
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript:  pesquisaProduto(document.frm_os.produto_referencia,'referencia'); " style='cursor: hand'>
                    <img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0'
                        align='absmiddle'  title="NÃO HÁ COMUNICADOS PARA ESTE PRODUTO"
                        onclick="javascript:abreComunicado()"
                        style='cursor: pointer;'>
                    <input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
                </td>
<?
            }
            if ($login_fabrica == 7 || $login_fabrica == 59){//HD 188632
            //30544 31/7/2008?>
                <td nowrap  valign='top'>
                    <input type="hidden" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>">
                </td>
<?
            }else{
                if ($login_fabrica == 52) {
                  ?>
                  <td nowrap  valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                        <span rel='produto_voltagem'>Marca</span>
                    </font>
                    <br />
                    <?
                      echo "<select name='marca_logo' size='1' class='frm' style='width:95px'>";
                      echo "<option value=''></option>";
                      $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
                      $res = pg_query($con,$sql);
                      if(pg_num_rows($res)>0){
                          for($i=0;pg_num_rows($res)>$i;$i++){
                              $xmarca = pg_fetch_result($res,$i,marca);
                              $xnome = pg_fetch_result($res,$i,nome);
                              ?>
                              <option value="<?echo $xmarca;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>
                              <?
                          }
                      }
                      echo "</SELECT>";
                      ?>
                  </td>
                  <?
                }
?>
                <td nowrap  valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                        <span rel='produto_voltagem'><?=traduz("Voltagem")?></span>
                    </font>
                    <br />
                    <input class="frm" id='produto_voltagem' type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >
                </td>
<?
            }
?>
                <td nowrap  valign='top'>
                    <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif">
                        <span rel='data_abertura'>
                            <?php echo getValorFabrica([
                                0 => traduz('data.abertura'),
                                6 => 'Data de Entrada',
                                3 => 'Data de Entrada Produto',
                                104 => 'Data de Entrada']);?>
                        </span>&nbsp;
                    </font>
                    <br/>
<?
//                if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y");
    if(in_array($login_fabrica,array(6,11,45,50,80,104,120,201,134,139,140,172)) and strlen($os) == 0){
        $data_abertura = date("d/m/Y");
        $bloqueia_data =  " READONLY onclick='javascript:dataAbertura();' ";
        echo "<input name='data_abertura' id='data_abertura' value='$data_abertura' type='hidden'>";
    }

    if ($login_fabrica == 30) {
        $readonly_data_abertura = 'readonly="readonly"';
    }else{
        $readonly_data_abertura = '';
    }

?>

                    <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>
                    <input  name="data_abertura"
                            id="data_abertura"
                            rel='data'
                            size="12"
                            maxlength="10"
                            value="<? echo $data_abertura; ?>"
                            <?php echo $readonly_data_abertura ?>
                            type="text" class="frm"
                            onblur="this.className='frm';
                                    displayText('&nbsp;');
							<? if($login_fabrica==24){
									echo "MostraAtencao('atencao');";
								}

							?> "
                            onfocus="this.className='frm-on';
                                     displayText('&nbsp;Entre com a Data da Abertura da OS.');
                                     <? if($login_fabrica==24){echo "MostraAtencao('atencao');"; } ?>"
                            tabindex="0"
                            <? echo $bloqueia_data ?>
                    />

<?php
    if(in_array($login_fabrica, [120,201])){
?>
                   <br />
                   <font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
<?
    }
?>
                </td>
<?

            #wanke pediu para adicionar campo data_fabricacao
            #HD-3331834 removida fabrica 50
            if (in_array($login_fabrica, array(91, 96 , 120,201, 131))) {

                if (!empty($os)) {

                    if($login_fabrica == 131){
                      $maskFabricacao = "MM/YYYY";
                    }else{
                      $maskFabricacao = "DD/MM/YYYY";
                    }

                    $sqlDF = "SELECT TO_CHAR(data_fabricacao, '$maskFabricacao') as data_fabricacao FROM tbl_os_extra WHERE os = $os";
                    $qryDF = pg_query($con, $sqlDF);

                    if (pg_num_rows($qryDF) > 0) {
                        $data_fabricacao = pg_fetch_result($qryDF, 0, 'data_fabricacao');

                    }
                }

                if($login_fabrica == 131){
                    $exemplo = "<br />
                    <font face='arial' size='1'>Ex.:mm/aaaa</font>";
                }else{
                    $exemplo = "";
                }


                if($login_fabrica == 131){
                  $teste = explode("/", $data_fabricacao);
                  if(count($teste) > 2){
                    $data_fabricacao = $teste[1]."/".$teste[2];
                  }
                }

		$campo_obrig = ($login_fabrica == 91) ? "color='red'" : "";
                echo"<td nowrap  valign='top'>
                        <font size='1' face='Geneva, Arial, Helvetica, san-serif' {$campo_obrig}>
                          <span rel='data_fabricacao'>Data Fabricação</span>
                        </font><br>
                        <input name='data_fabricacao' id='data_fabricacao' size='12' maxlength='10'
                          title='Favor informar a Data de fabricação'
                          value='$data_fabricacao' type='text' class='frm'>
                          $exemplo
                      </td>";
            }

            if ($login_fabrica == 7) { #HD 49336?>
                <td nowrap  valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                        Hora da Abertura
                    </font>
                    <br /><?php
                    if (strlen($hora_abertura) == 0) {
                        #$hora_abertura = date("H:i"); //Vazio para forçar o preenchimento
                    } else {
                        $hora_abertura = substr($hora_abertura,0,5);
                    }?>
                    <input name="hora_abertura" id="hora_abertura" rel='hora' size="7" maxlength="5"
                          value="<? echo $hora_abertura; ?>" type="text" class="frm"
                          title='Favor informar a Data/Hora que o equipamento foi recebido ou da solicitação de atendimento'
                         onblur="this.className='frm'; displayText('&nbsp;');"
                        onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Hora da Abertura da OS.');">
                </td><?php
            }

            if ($login_fabrica == 19) {
                if (strlen($data_digitacao) == 0) {
                    $data_digitacao= date('d/m/Y');
                }?>
                <td nowrap valign='top'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua) echo "Data Digitação";else echo "Data Digitação";?></font>
                    <br />
                    <input class="frm" type="text" name="data_digitacao" size="12" value="<? echo $data_digitacao?>" readonly>
                </td><?php
                echo "</td>";
            }

            if (!in_array($login_fabrica,array(3,6,24,19,50,43,56,59,2,30,85,74,95,120,201,127,137)) AND $linhainf <> 't') { // HD-2296739 ?>
                <td nowrap  valign='top'>
                <font size="1" color="<?= (!in_array($login_fabrica, [124])) ? 'red' : '' ?>" face="Geneva, Arial, Helvetica, san-serif"><?php
                if ($login_fabrica == 35) {
                    echo "PO#";
                } else {
                    $serie_desc = ($login_fabrica == 134) ? "N. Série / Data Fabricação" : "N. Série";
                    echo "&nbsp;<span rel='produto_serie'>$serie_desc</span>";
                }?>
                </font>
                <br /><?php
                if ($login_fabrica == 40) {
                    $produto_serie_ini = substr($produto_serie,0,2);
                    $produto_serie     = substr($produto_serie,2,7);?>
                    <input type='text' name='produto_serie_ini' value='<?=$produto_serie_ini?>' maxlength='2' size='3' class="frm"> -
                <?}

                $serie_ml = '30'; $serie_evt = ''; $serie_blur = '';
                switch ($login_fabrica) {
                    case 94: $serie_ml = '12'; break;
                    case 40: $serie_ml = '10'; break;
                    case 35: $serie_ml = '20'; break;

                    case 51:
                        $serie_ml = '20';
                        $serie_evt= 'onKeyUp="javascript:somenteMaiusculaSemAcento(this);"';
                        break;
                    case  7: $serie_blur = 'verificaProduto(document.frm_os.produto_referencia,this);'; break;
                    case  72: $serie_focus = 'verificaOrigem();'; break;
                    case 140: $serie_evt = 'onchange="verificaNS(this.value)";';  break;
                }

                if($login_fabrica == 96 && strlen($hd_chamado) > 0){
                  $sql_serie = "SELECT serie FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
                  $res_serie = pg_query($con, $sql_serie);
                  if(pg_num_rows($res_serie) > 0){
                    $produto_serie = pg_fetch_result($res_serie, 0, "serie");
                  }
                }

                if ($serie_ml != '') $serie_ml = "maxlength='$serie_ml' ";
                $size_qtde = ($login_fabrica == 15) ? 'size="20"' : 'size="14"';
                ?>
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm" type="text" name="produto_serie" id="produto_serie" <? echo $size_qtde; echo $serie_ml;?>
                       value="<? echo $produto_serie; ?>" <? echo $serie_evt;?>
                      onblur="this.className='frm'; displayText('&nbsp;');<? echo $serie_blur;?>"
                      onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.');<? echo $serie_blur;?>; <? echo $serie_focus;?>">
                <?php

                if( in_array($login_fabrica,[120,201]) || $login_fabrica == 86){
                ?>
                &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript:  pesquisaSerie(document.frm_os.produto_serie,document.frm_os.produto_referencia); " style='cursor: hand'>
                <?
                }

                if ($login_fabrica == 25) {?>
                    &nbsp;<INPUT TYPE="button" onClick='javascript:fn_verifica_garantia();' name='Verificar' value='Verificar'><?php
                }?>
                <br /><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
                <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'></div><?php
                if ($login_fabrica == 35) {
                    echo "<div width='100' style='font-size: 9px;'>Encontra-se na etiqueta de voltagem do aparelho.<br />
                    Caso o produto não possua número de série,<br />
                    entre em contato com o fabricante.</<div>";
                }

                if ($login_fabrica == 40) {
                    echo "<div width='100' style='font-size: 9px;'>Ex: AB - 0123456789</div>";
                }?>
            </td>
            <? } ?>
        </tr>

        <?    //hbtech 4/3/2008 14824
        if ($login_fabrica == 25) {?>
            <tr>
                <td colspan='4'>
                    <div id='div_estendida' style='text-align:center;'><?php
                    if (strlen($produto_serie) > 0) {
                        include "conexao_hbtech.php";

                        $sql = "SELECT    idNumeroSerie  ,
                                        idGarantia     ,
                                        revenda        ,
                                        cnpj
                                FROM numero_serie
                                WHERE numero = '$produto_serie'";
                        $res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

                        if (mysql_num_rows($res) > 0) {
                            $idNumeroSerie = mysql_result($res,0,idNumeroSerie);
                            $idGarantia    = mysql_result($res,0,idGarantia);
                            $es_revenda       = mysql_result($res,0,revenda);
                            $es_cnpj          = mysql_result($res,0,cnpj);

                            if(strlen($idGarantia)==0){
                                echo "Número de série não encontrado nas vendas";

                            }
                        }
                    }?>
                    </div>
                </td>
            </tr><?php
        }//fim hbtech ?>
		</table>
		<? if(in_array($login_fabrica, array(104))) {
        if(strlen($data_recebimento_produto) == ''){
          $bloqueia_data = '';
        }
     ?>
        <table>
          <tr>
            <td nowrap  valign='top' style="padding-left:11px">
              <font size="1" face="Geneva, Arial, Helvetica, san-serif" style="color: rgb(168, 0, 0);"> Data Recebimento Produto </font>
              <br>
              <input type="text" name="data_recebimento_produto" rel='data_recebimento_produto' id='data_recebimento_produto' size="13" value="<?php echo $data_recebimento_produto ?>" <? echo $bloqueia_data ?>>
              </td>
              <?php
                if(in_array($login_fabrica, array(104)) and !empty($hd_chamado)) {
                  $sqlPostagem = "SELECT
                                    tbl_hd_chamado_postagem.numero_postagem
                                 FROM
                                    tbl_hd_chamado_postagem
                                 JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_postagem.hd_chamado AND tbl_hd_chamado_postagem.fabrica=$login_fabrica
                                 WHERE
                                    tbl_hd_chamado_postagem.hd_chamado=$hd_chamado
                                 AND
                                    tbl_hd_chamado_postagem.fabrica=$login_fabrica
                               ";

                $resPostagem = pg_query($con,$sqlPostagem);
                if (pg_num_rows($resPostagem) > 0) {
                    echo "
                      <td nowrap style='padding-left:11px'>
                      <font size='1' face='Geneva, Arial, Helvetica, san-serif' style='color: rgb(168, 0, 0);''>Recebido</font><br />
                      Via Correios
                      </td>";
                } else {
                    echo "
                      <td nowrap style='padding-left:11px'>
                      <font size='1' face='Geneva, Arial, Helvetica, san-serif' style='color: rgb(168, 0, 0);''>Recebido</font><br />
                      Via Consumidor
                      </td>";

                }
              }?>
          </tr>
        </table>
   <?php
		}


        if ($login_fabrica == 7) {?>

            <table width="50%" border="0" cellspacing="5" cellpadding="2">
                <tr>
                    <td nowrap  valign='top'>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidade</font>
                        <br />
                        <? if (strlen($produto_capacidade)>0){
                            echo "<INPUT TYPE='hidden' name='capacidade' id='capacidade' value='$produto_capacidade'>";
                            echo "<INPUT TYPE='text' VALUE='$produto_capacidade' SIZE='9' onClick=\"alert('Não é possível alterar a capacidade')\" disabled>";
                        }else{?>
                            <INPUT TYPE="text" NAME="produto_capacidade" id='produto_capacidade' VALUE="<?=$produto_capacidade?>" SIZE='9' MAXLENGTH='9'>
                        <?}?>
                    </td>
                    <td nowrap  valign='top'>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Divisão</font>
                        <br />
                        <? if (strlen($produto_divisao)>0){
                            echo "<input type='hidden' name='divisao' value='$produto_divisao'>";
                            echo "<INPUT TYPE='text' VALUE='$produto_divisao' maxlength='19' SIZE='9' onClick=\"alert('Não é possível alterar a divisão')\" disabled>";
                        }else{?>
                            <input type="text" name="divisao" id='divisao' value="<?=$divisao?>" size='9' maxlength='9'>
                        <?}?>
                    </td>
                </tr>
            </table><?php

        } ?>

        <table width="100%" border="0" cellspacing="5" cellpadding="2">
        <tr valign='top'>
        <? if ($login_fabrica==19){ ?>
        <td nowrap  valign='top' width="10%">
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">
              <span rel="numero_serie">N. Série</span>
            </font>
            <br />
            <input class="frm" type="text" name="produto_serie" id="produto_serie"  size="8"
               maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==3 and $login_posto==6359){ echo " MostraEsconde('dados_1');";} ?>" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); <? if($login_fabrica==3 and $login_posto==6359){ echo " MostraEsconde('dados_1');";} ?> ">
            <br />
            <font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt)"; ?></font>
            <div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
            </div>
        </td>
        <? } ?>
        <td width='100' valign='top' nowrap>
        <? if ($login_fabrica == 30){?>
        <acronym title='Campo Obrigatório'>
            <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Nota Fiscal</font>
        </acronym>
        <?}else{?>
        <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif"><span rel="nota_fiscal"><?=traduz("Nota Fiscal")?></span></font>
        <br />
        <?}?>
        <?    if($login_fabrica ==45){ // HD 31076
                $maxlength = "14";
            }elseif($login_posto==20314){
                $maxlength = "12";
            }else{
                $maxlength = "10";
            }
        ?>
        <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>
        <? if (($login_fabrica == 30) && (strlen($_GET['os']) > 0 )) {?>
            <input readonly="readonly" class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="10" maxlength="<? echo $maxlength ?>" value="<? echo $nota_fiscal ?>" <? if($login_fabrica==30 or $login_fabrica==45){?> onkeypress="mascara(this,soNumeros)"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
        <?}else{?>
            <input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="10" maxlength="<? echo $maxlength ?>" value="<? echo $nota_fiscal ?>" <? if($login_fabrica==30 or $login_fabrica==45){?> onkeypress="mascara(this,soNumeros)"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
        <?}?>
        </td>
        <? if($login_fabrica == 15 OR $login_fabrica == 45 OR $login_fabrica == 140){ ?>
        <td width='100' valign='top' nowrap>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="preco_produto">Preço do Produto</span></font>
        <br />
        <input class="frm" type="text" name="preco_produto" id="preco_produto" size="10" maxlength="8"  value="<? echo $preco_produto ?>">
        </td>
        <? } ?>
        <td width='110' valign='top' nowrap>
        <? if ($login_fabrica == 30){?>
            <acronym title='Campo Obrigatório'>
                <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Compra</font>
            </acronym>
        <?}else{?>
            <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif"><span rel='data_nf'>&nbsp;&nbsp;&nbsp;<?=traduz("Data Compra")?></span></font>
            <br />
        <?}?>
        <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
        <? if (($login_fabrica == 30) && (strlen($_GET['os']) > 0 )) {?>
            <input readonly="readonly" class="frm" type="text" name="data_nf" id="data_nf" rel='data' size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==24){echo "MostraAtencao('atencao'); "; } if($login_fabrica==51) echo "verifica_garantia('data_nf','produto_referencia','data_abertura');";?>" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');<? if($login_fabrica==24){echo "MostraAtencao('atencao');"; } ?>" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?> ><br /><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
        <?}else{
            $margin_data = '';
            if ($login_fabrica == 19) {
              $margin_data = "style='margin-left: -10px;'";
            }
          ?>
            <input class="frm" <?=$margin_data?> type="text" name="data_nf" id="data_nf" rel='data' size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');<? if($login_fabrica==24){echo "MostraAtencao('atencao'); "; } if($login_fabrica==51) echo "verifica_garantia('data_nf','produto_referencia','data_abertura');";?>" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');<? if($login_fabrica==24){echo "MostraAtencao('atencao');"; } ?>" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?> ><br />
            <font face='arial' size='1'>&nbsp;&nbsp;&nbsp;Ex.: <?php echo ($login_fabrica == 131) ? "dd/mm/aaaa" : date("d/m/Y"); ?></font>
        <?}?>

        <div id='atencao' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
                        </div>

                <div id='div_garantia' style='background:#efefef;border:#999999 1px solid;font-size:10px;<?if(!isset($liberar_digi)) echo "visibility:hidden;position:absolute;";?>' >
                <?
                /* HD 26244 */
                if ($login_fabrica == 30){
                    echo "<b>LGI:</b>";
                }else{
                    echo "<b>Anexar a cópia do certificado de garantia na OS</b><br /><br />Certificado de Garantia:";
                }
                ?>
                <input type='text' name='certificado_garantia' id='certificado_garantia' value='<?=$certificado_garantia?>' size='5' maxlength='<?=($login_fabrica == 30)?'6':'30'?>'>
        </div>
        </td>
            <? if ($login_fabrica == 30) { ?>
                <td valign='top' align='left'>
                    <acronym title='Campo Obrigatório'>
                        <font color="#AA0000" size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
                        <br />
                    </acronym>
                    <?
                        if($pedir_defeito_reclamado_descricao == 't'){
                            //HD 204082: Recuperar defeito reclamado da pré-os
                            echo "<acronym title='Campo Obrigatório'><input type='text' name='defeito_reclamado_descricao' id='defeito_reclamado_descricao' class='frm' ".
                         "value='$defeito_reclamado_descricao' size='40' onKeyUp='somenteMaiusculaSemAcento(this);'></acronym>";
                        }else{
                            if(strlen($defeito_reclamado) >0) {
                                $sql = " SELECT descricao
                                    FROM tbl_defeito_reclamado
                                    WHERE defeito_reclamado = $defeito_reclamado";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    $reclamado_descricao = pg_fetch_result($res,0,descricao);
                                }
                            }


                            //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                            if ($login_fabrica == 3) {
                                $defeito_reclamado_onchange = "onchange='mostraDefeitoDescricao($login_fabrica)'";
                            }

                            echo "<acronym title='Campo Obrigatório'><select name='defeito_reclamado' id='defeito_reclamado' style='width: 220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' class='frm' $defeito_reclamado_onchange>";
                            if(strlen($defeito_reclamado) > 0) {
                                echo "<option id='opcoes' value='$defeito_reclamado'>$reclamado_descricao</option>";
                            }else{
                                echo "<option id='opcoes' value='0'></option>";
                            }
                            echo "</select></acronym>";
                            if ( in_array($login_fabrica, array(11,172)) ){
                                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><br />*Caso o defeito não seja listado verifique se os dados<br />do <B><U><I>produto</I></U></B> estão corretos pesquisando-o pela lupa.</font>";
                            }
                            echo "</td>";
                        }
                        //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                        if ($login_fabrica == 3){
                            echo "<td style='display:none;' nowrap valign='top' id='td_defeito_reclamado_descricao'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado Adicional</font><br /><INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='30'></td>
                            <script language='javascript'>
                            mostraDefeitoDescricao($login_fabrica);
                            </script>
                            ";
                        }

            } else {
              if ($login_fabrica != 19) { ?>

                <td valign='top' align='left'><?php
                    if (!in_array($login_fabrica,array(46,94,114,117,120,201,121,122,123,124,125,126,127,128,129,132,134,136,139,140,141,144))) {
                        if($login_fabrica != 131){?>
                        <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif" ><span rel='defeito_reclamado' id="defeito_reclamado_descricao_title">Defeito Reclamado</span></font>
                        <br /><?php
                        }else{
                          ?>
                          <font size="1"  color='#AA0000' face="Geneva, Arial, Helvetica, san-serif" ><span rel='defeito_reclamado' id="defeito_reclamado_descricao_title">Defeito Reclamado</span></font>
                        <br />
                          <?php
                        }
                    }

                    if (in_array($login_fabrica,array(114,117,120,201,121,122,132,134,136,139,140,141,144))) {
                      if(in_array($login_fabrica, [120,201,134])){ //hd_chamado=3143195
                        $color_red = 'style="color: rgb(168, 0, 0)"';
                      }
                    ?>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif" ><span <?=$color_red?> rel='defeito_reclamado_descricao' id="defeito_reclamado_descricao_title">Defeito Reclamado</span></font>
                        <br />
                    <?
                    }

                    if (!in_array($login_fabrica, array(42,46,86,94,74,96,94,115,116,123,124,125,126,127,128,129))) //HD314245

                        if ($pedir_defeito_reclamado_descricao == 't' && !in_array($login_fabrica, array(120,201))){
                            //HD 204082: Recuperar defeito reclamado da pré-os
    			                   if(!empty($hd_chamado)) {
									   $sql = 'SELECT defeito_reclamado_descricao FROM tbl_hd_chamado_extra WHERE hd_chamado = $1;';
									   $result = pg_query_params($con,$sql,array($hd_chamado));
									   $_defeito_reclamado = pg_fetch_result($result,0,0);
									   $_defeito_reclamado = empty($_defeito_reclamado)?$_POST['defeito_reclamado_descricao']:$_defeito_reclamado;
									   $_defeito_reclamado = empty($_defeito_reclamado)?'':$_defeito_reclamado;
    			                   }

                                   if ($login_fabrica == '131' OR $login_fabrica >= 137) {
                                       $_defeito_reclamado = $_POST['defeito_reclamado_descricao'];
                                   }
                            if($login_fabrica == 50){ //HD-3331834
                              if(strlen(trim($produto_referencia)) > 0 AND strlen(trim($os)) > 0){
                                $sql_def_int = "SELECT
                                                  tbl_diagnostico.diagnostico,
                                                  tbl_diagnostico.ativo,
                                                  tbl_diagnostico.garantia,
                                                  tbl_defeito_reclamado.defeito_reclamado,
                                                  tbl_defeito_reclamado.descricao AS defeito_descricao,
                                                  tbl_defeito_reclamado.codigo AS defeito_codigo,
                                                  tbl_familia.descricao AS familia_descricao
                                                FROM tbl_diagnostico
                                                JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = $login_fabrica
                                                JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia
                                                JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia
                                                WHERE tbl_diagnostico.fabrica = $login_fabrica
                                                AND tbl_diagnostico.defeito_constatado IS NULL
												AND tbl_diagnostico.ativo
                                                AND tbl_produto.referencia = '$produto_referencia'
                                                ORDER BY familia_descricao, defeito_descricao ASC;";
                                $res_def_int = pg_query($con, $sql_def_int);

                                if(pg_num_rows($res_def_int) > 0){
                                  echo '<select class="frm" id="defeito_reclamado_descricao" name="defeito_reclamado_descricao" >
                                    <option value="">Selecione o Defeito</option>';
                                    for ($i=0; $i <pg_num_rows($res_def_int) ; $i++) {
                                      $xdefeito_reclamado = pg_fetch_result($res_def_int, $i, 'defeito_reclamado');
                                      $xdefeito_reclamado_descricao = pg_fetch_result($res_def_int, $i, 'defeito_descricao');
                                      $xdefeito_reclamado_codigo = pg_fetch_result($res_def_int, $i, 'defeito_codigo');

                                      if($defeito_reclamado == $xdefeito_reclamado){
                                        $selected = "selected";
                                      }else{
                                        $selected = "";
                                      }
                                      echo "<option value='$xdefeito_reclamado' $selected >$xdefeito_reclamado_descricao</option>";
                                    }
                                  echo '</select>';
                                }
                              }else{
                                $sql_def ="SELECT defeito_reclamado, descricao, codigo from tbl_defeito_reclamado where fabrica=$login_fabrica and ativo='t' order by descricao;";
                                $res_def = pg_query($con, $sql_def);
                                if(pg_num_rows($res_def) > 0){
                                  echo '<select class="frm" id="defeito_reclamado_descricao" name="defeito_reclamado_descricao">
                                    <option value="">Selecione o Defeito</option>';
                                    for ($i=0; $i <pg_num_rows($res_def) ; $i++) {
                                      $xdefeito_reclamado = pg_fetch_result($res_def, $i, 'defeito_reclamado');
                                      $xdefeito_reclamado_descricao = pg_fetch_result($res_def, $i, 'descricao');
                                      $xdefeito_reclamado_codigo = pg_fetch_result($res_def, $i, 'codigo');
                                      echo "<option value='$xdefeito_reclamado' $selected >$xdefeito_reclamado_descricao</option>";
                                    }
                                  echo '</select>';
                                }
                              }
                            }else{
                              echo "<input type='text' name='defeito_reclamado_descricao' id='defeito_reclamado_descricao' class='frm' ". "value='$defeito_reclamado_descricao' size='40' onKeyUp='somenteMaiusculaSemAcento(this);'>";
                            }
                        } else {
                            if(strlen($defeito_reclamado) >0) {
                                $sql = " SELECT descricao
                                    FROM tbl_defeito_reclamado
                                    WHERE defeito_reclamado = $defeito_reclamado";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    $reclamado_descricao = pg_fetch_result($res,0,descricao);
                                }
                            }

                            //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                            if ($login_fabrica == 3) {
                                $defeito_reclamado_onchange = "onchange='mostraDefeitoDescricao($login_fabrica)'";
                            }

                            #HD 424887 - INICIO

                            /* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
                            A CHAMADA DESTA FUNÇÃO "listaDefeitos" SERÁ FEITA NO ONBLUR DO PRODUTO, POIS NÃO
                            HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

                            if (!in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)) {
                                $onfocus_integridade_def_reclamado = "onfocus='listaDefeitos(document.frm_os.produto_referencia.value);'";
                            } else {
                                $onfocus_integridade_def_reclamado = null;
                            }

                            if ($login_fabrica == '3' && $hd_chamado <> '') {?>

                                <select name="defeito_reclamado" style='width: 420px;' id="defeito_reclamado"  class="frm">
                                    <option value="">Selecione um Defeito</option><?php
                                    $sql ="SELECT DISTINCT(tbl_defeito_reclamado.defeito_reclamado) AS cod_reclamado,
                                            tbl_defeito_reclamado.descricao AS desc_reclamado
                                            FROM tbl_diagnostico
                                            JOIN tbl_familia ON tbl_diagnostico.familia = tbl_familia.familia
                                            AND tbl_diagnostico.fabrica = tbl_familia.fabrica
                                            JOIN tbl_defeito_reclamado ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                                            AND tbl_diagnostico.fabrica = tbl_defeito_reclamado.fabrica
                                            JOIN tbl_linha             ON tbl_diagnostico.linha = tbl_linha.linha
                                            WHERE tbl_diagnostico.linha = 528
                                            AND tbl_diagnostico.fabrica = $login_fabrica
                                            AND tbl_diagnostico.ativo = 't'
                                            ORDER BY tbl_defeito_reclamado.descricao";

                                    $res = pg_query($con,$sql);

                                    if (pg_num_rows($res) > 0) {

                                        for ($x = 0; pg_num_rows($res) > $x; $x++) {

                                            $cod_reclamado   = pg_fetch_result($res, $x, 'cod_reclamado');
                                            $cdesc_reclamado = pg_fetch_result($res, $x, 'desc_reclamado');

                                            $selectd_reclamado = '';

                                            if ($defeito_reclamado == $cod_reclamado) {
                                                $selectd_reclamado = " SELECTED ";
                                            }?>
                                            <option value="<?php echo $cod_reclamado;?>" <?php echo $selectd_reclamado;?> title="<?php echo $cdesc_reclamado;?>"><?php echo $cdesc_reclamado;?></option> <?php

                                        }
                                    }?>
                                </select><?php

                            } else if ($login_fabrica != 19){

                                echo "<select name='defeito_reclamado' id='defeito_reclamado' style='width: 200px;' $onfocus_integridade_def_reclamado class='frm' $defeito_reclamado_onchange>";
                                if (strlen($defeito_reclamado) > 0 || strlen($defeito_reclamado) == 0) {

                                    if (in_array($login_fabrica, $fabricas_defeito_reclamado_sem_integridade)) {

                                        $sql = " SELECT defeito_reclamado, descricao
                                                    FROM tbl_defeito_reclamado
                                                    WHERE fabrica = $login_fabrica
                                                    AND ativo='t' ORDER BY descricao";

                                        $res = pg_query($con,$sql);

                                        if (pg_num_rows($res) > 0) {

                                            for ($y = 0; $y < pg_num_rows($res); $y++) {

                                                $xdefeito_reclamado  = pg_fetch_result($res, $y, 'defeito_reclamado');
                                                $reclamado_descricao = pg_fetch_result($res, $y, 'descricao');

                                                echo "<option id='opcoes' title='$reclamado_descricao' value='$xdefeito_reclamado'";
                                                if ($defeito_reclamado == $xdefeito_reclamado) echo "selected";
                                                echo ">$reclamado_descricao</option>";

                                            }

                                        } else {
                                            echo "<option id='opcoes' value='0'></option>";
                                        }

                                    } else {

                                        if(strlen($_GET['pre_os'])>0 && in_array($login_fabrica, array(11,172)) && strlen($defeito_constatado)==0){

                                            $sql = " SELECT descricao,
                                                             tbl_defeito_reclamado.defeito_reclamado
                                                        FROM tbl_defeito_reclamado
                                                        INNER JOIN tbl_hd_chamado_extra on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
                                                        WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado";
                                            $res = pg_query($con,$sql);

                                            if (pg_num_rows($res) > 0) {
                                                $reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
                                                $defeito_reclamado = pg_fetch_result($res, 0, 'defeito_reclamado');
                                            }

                                        }

                                        if (strlen($_GET['pre_os']) > 0 && in_array($login_fabrica, array(101,120,201))) {

                                            $sql = " SELECT D.descricao,
                                                            D.defeito_reclamado
                                                       FROM tbl_defeito_reclamado D
                                                       JOIN tbl_hd_chamado_extra H ON D.defeito_reclamado = H.defeito_reclamado
                                                      WHERE H.hd_chamado = $hd_chamado";
                                            $res = pg_query($con,$sql);

                                            if (pg_num_rows($res) > 0) {
                                                $reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
                                                $defeito_reclamado   = pg_fetch_result($res, 0, 'defeito_reclamado');
                                            }

                                        }

                                        if(!empty($defeito_reclamado)) {
                                            $sql = " SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado";
                                            $res = pg_query($con,$sql);

                                            if (pg_num_rows($res) > 0) {
                                                $reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
                                            }

                                        }

                                        if ($login_fabrica == 52 and $pre_os == 't' and !empty($hd_chamado_item)) {
                                            $sql_def_recl = "SELECt produto, defeito_reclamado FROM tbl_hd_chamado_item WHERE hd_chamado_item = $hd_chamado_item";
                                            $qry_def_recl = pg_query($con, $sql_def_recl);

                                            if (pg_num_rows($qry_def_recl) > 0) {
                                                $produto_atendimento = pg_fetch_result($qry_def_recl, 0, 'produto');
                                                $defeito_reclamado_atendimento = pg_fetch_result($qry_def_recl, 0, 'defeito_reclamado');

                                               $sql_defeitos = "
                                                        SELECT distinct tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
                                                        FROM tbl_defeito_reclamado
                                                        JOIN tbl_diagnostico ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                                                        JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia
                                                        WHERE tbl_defeito_reclamado.fabrica = $login_fabrica
                                                        AND tbl_produto.produto = $produto_atendimento
                                                        AND tbl_defeito_reclamado.ativo='t' ORDER BY tbl_defeito_reclamado.descricao";
                                                $qry_defeitos = pg_query($con, $sql_defeitos);

                                                if (pg_num_rows($qry_defeitos) > 0) {
                                                    while ($fetch = pg_fetch_assoc($qry_defeitos)) {
                                                        $tmp_dc = $fetch['defeito_reclamado'];
                                                        $tmp_desc = $fetch['descricao'];

                                                        echo '<option id="opcoes" value="' , $tmp_dc , '" title="' , $tmp_desc , '"';
                                                        if ($tmp_dc == $defeito_reclamado_atendimento) {
                                                            echo ' SELECTED="SELECTED" ';
                                                        }
                                                        echo '>' , $tmp_desc , '</option>';
                                                    }
                                                }


                                            }

                                        } else {
                                            echo "<option id='opcoes' value='$defeito_reclamado' title='$reclamado_descricao'>$reclamado_descricao</option>";
                                        }

                                    }

                                } else {
                                    echo "<option id='opcoes' value=''></option>";
                                }

                            echo "</select>";

                            if ( in_array($login_fabrica, array(11,172)) ) {

                                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><br />*Caso o defeito não seja listado verifique se os dados<br />do <B><U><I>produto</I></U></B> estão corretos pesquisando-o pela lupa.</font>";

                            }

                        }

                        echo "</td>";

                        } else{ //mostra os dois, para a makita HD314245, IBBL HD 322650, Atlas, Bosch Security

                            if (!in_array($login_fabrica,array(46,94,120,201,123,124,125,126,127,128, 129))) {


                                if (strlen($defeito_reclamado) > 0) {

                                    $sql = " SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        $reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
                                    }

                                }elseif ($login_fabrica == 96 and $_GET['hd_chamado_item']){
                                    $chamado_item = $_GET['hd_chamado_item'];
                                    $sql_def_cons = "SELECT
                                                        tbl_defeito_reclamado.defeito_reclamado,
                                                        tbl_defeito_reclamado.descricao
                                                    FROM tbl_hd_chamado_item
                                                    JOIN tbl_defeito_reclamado on tbl_hd_chamado_item.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                                                    WHERE tbl_hd_chamado_item.hd_chamado_item = $chamado_item
                                    ";
                                    $res_def_cons = pg_query($con,$sql_def_cons);

                                    if (pg_num_rows($res_def_cons) > 0) {
                                        $defeito_reclamado = pg_fetch_result($res_def_cons, 0, 'defeito_reclamado');
                                        $reclamado_descricao = pg_fetch_result($res_def_cons, 0, 'descricao');
                                    }

                                }

                                echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>&nbsp;<select name='defeito_reclamado' id='defeito_reclamado' style='width: 200px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' class='frm' $defeito_reclamado_onchange>";

                                if (strlen($defeito_reclamado) > 0) {
                                    echo "<option id='opcoes' value='$defeito_reclamado'>$reclamado_descricao</option>";
                                } else {
                                    echo "<option id='opcoes' value=''></option>";
                                }

                                echo '</select></td>'; #HD 389165

                            }

                            if ($login_fabrica<>86 and $login_fabrica <> 74){

                                if ($login_fabrica == 96 and $_GET['hd_chamado_item']){

                                    $chamado_item = $_GET['hd_chamado_item'];
                                    $sql_def_cons_desc = "SELECT
                                                        tbl_hd_chamado_item.defeito_reclamado_descricao
                                                    FROM tbl_hd_chamado_item
                                                    WHERE tbl_hd_chamado_item.hd_chamado_item = $chamado_item
                                    ";
                                    $res_def_cons_desc = pg_query($con,$sql_def_cons_desc);

                                     if (pg_num_rows($res_def_cons_desc) > 0) {
                                        $defeito_reclamado_descricao = pg_fetch_result($res_def_cons_desc, 0, 'defeito_reclamado_descricao');
                                    }

                                }
                                $label_campo = (in_array($login_fabrica,array(120,201,123,124,125,126,127,128))) ? traduz("Defeito Reclamado") : "Descrição do Defeito Reclamado";
                                echo '<td valign="top" align="left" style="padding:0 5px 0 5px;"> <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="defeito_reclamado_descricao">'.$label_campo.'</span></font>
                                <br />';
                                echo "<input type='text' name='defeito_reclamado_descricao' valida='defeito_reclamado_descricao' id='defeito_reclamado_descricao' class='frm' ".
                                "value='$defeito_reclamado_descricao' size='22' onKeyUp='somenteMaiusculaSemAcento(this);'> </td>";
                            }

                        }
                        //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                        if ($login_fabrica == 3){
                            echo "<td style='display:none;' nowrap valign='top' id='td_defeito_reclamado_descricao'>".
                                 "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado Adicional</font><br />".
                                 "<INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='30'>".
                                 "</td>
                            <script language='javascript'>
                            mostraDefeitoDescricao($login_fabrica);
                            </script>
                            ";
                        }
              }
            }

            //hd 24288
            if ($login_fabrica == 3 AND $login_posto == 6359 AND 1==2) {
            if ($linha == 335) {
                $mostrar = "block";
            } else {
                $mostrar = "none";
            }?>
            <td nowrap valign='middle' style='font-size: 10px'>
                <span id='atendimento_dominico_span' style='display:<?=$mostrar?>'>
                <input type="checkbox" NAME="atendimento_domicilio" value="t" <?if($atendimento_domicilio=='t')echo "checked";?> onFocus="mostraDomicilio()" ><font size="1" face="Geneva, Arial, Helvetica, san-serif">Atendimento Domicilio</font>
                </span>
            </td>
        <?}

        if ($login_fabrica == 15) {?>
            <!--
            <td nowrap valign='top' style='font-size: 10px'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Cartão Clube</font>
                <br />
                <input  name ="cartao_clube" class ="frm" type ="text" size ="15" maxlength="15" value ="<? echo $cartao_clube ?>" onblur = "this.className='frm';MostraEsconde('cartao'); displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Cartão Clube, caso tenha.');MostraEsconde('cartao')"><br /><div id='cartao' style='position:absolute; border: 1px solid #949494;background-color: #f4f4f4; font-size:10px; padding:1px; display:none;'><i>Caso o consumidor <b>não</b><br /> tenha, deixe em branco.</i></div>
            </td>--><?php

        }

        if ($combo_tipo_atendimento) {?>

            <td align='left' id="mostra_tipo_atendimento"
                    <?php
                    if ($login_fabrica == 19) {
                        echo 'style="width:10%;"';
                    }
                    if ($login_fabrica == 35 and (empty($tipo_atendimento) or $tipo_atendimento == 'null')){
                        echo 'style="display:none;"';
                    }
                    ?> >
                <?php
                if($login_fabrica == 131){
                  echo "<acronym title='Campo Obrigatório'>";
                  $color = 'color="#AA0000"';
                }
                if(in_array($login_fabrica,array(15,19,114))){
                  $color = 'color="red"';
                }
                ?>
                <font size="1" <?php echo $color; ?> face="Geneva, Arial, Helvetica, san-serif" ><?=($login_fabrica == 7) ? "Natureza" : "<span rel='tipo_atendimento'>".traduz('Tipo de Atendimento'
              )."</span>"?></font><br />
                <?php
                if($login_fabrica == 131){
                  echo "</acronym>";
                }
                ?>
                <select name="tipo_atendimento" id='tipo_atendimento' class='frm' style='width:220px;'<?php
                    if ($login_fabrica == 50 or $login_fabrica == 74 or $login_fabrica == 137  or $login_fabrica == 140) { // HD 54668 para Colormaq ?>
                        onfocus="tipoatendimento();"<?php
		    }

		?>
			    onChange="verifica_atendimento();" >
		?>
                    <option></option><?php

			    if ($login_fabrica == 1) $sql_add1 = " AND tipo_atendimento NOT IN (17,18,35,64) ";
		//	if($login_fabrica == 3) $sql_add1 = " AND tipo_atendimento NOT IN(37) ";

                    /*HD:22505- COLORMAQ - Tipo atendimento de deslocamento só aparece se o posto tem km cadastrado(maior que 0)*/
                    $sql_deslocamento = " ";

                    if ($login_fabrica == 50) {

                        $sql_deslocamento = " AND tipo_atendimento NOT IN (
                                                    SELECT
                                                        CASE WHEN valor_km > 0
                                                            Then 0
                                                            Else 55
                                                    END as tipo_atendimento
                                                    FROM tbl_posto_fabrica
                                                    WHERE fabrica = $login_fabrica
                                                        AND posto = $posto
                                                ) ";

                    }

                    if($login_fabrica <> 137){

                      $order_by = (in_array($login_fabrica, array(3, 141, 144))) ? "codigo" : "tipo_atendimento";

                      if (in_array($login_fabrica, [144]) && !$posto_interno) {
                        $condDeslocamento = "AND km_google IS NOT TRUE";
                      }

                      $sql = "SELECT *
                              FROM tbl_tipo_atendimento
                              WHERE fabrica = $login_fabrica
                              AND   ativo IS TRUE
                              $sql_add1
                              $sql_deslocamento
                              {$condDeslocamento}
                              ORDER BY $order_by";

                        if ($login_fabrica == 19) {//HD 15937
                            // die($linha_produto . "LINHA DO PRODUTO");
                            $tipos_at = ['00', '02', '03', '04', '05', '14', '15', '16', '18', '20', '22'];
                            $linha_do_produto = $linha ? : $_POST['linha_id'];
                            $permiteLS = ($linha_do_produto == 928);

                            $sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND codigo IN (". implode(',', $tipos_at) . ") ORDER BY codigo";
                        }

                      if ($login_fabrica == 15) {

                          if ($login_posto == 2405) {
                              $sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY descricao";

                          } else {
                              $verifica_atendimento_latina = false;
                              if ($produto_referencia){

                                  $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
                                  $res = pg_query($con, $sql);

                                  $tipo_posto = pg_result($res, 0, 0);

                                  $sql_latina = "select tbl_produto.produto,
                                                 tbl_familia.familia
                                          from tbl_produto
                                          join tbl_familia using(familia)
                                          where tbl_familia.paga_km is TRUE
                                          and tbl_familia.fabrica=$login_fabrica
                                          and tbl_produto.referencia='$produto_referencia' ";

                                  $res_latina = pg_query($con,$sql_latina);

                                  $verifica_atendimento_latina = true;

                              }else{

                                  $sql = "SELECT *
                                          FROM tbl_tipo_atendimento
                                          WHERE fabrica = $login_fabrica
                                          AND   ativo   IS TRUE $sql_add1
                                          ORDER BY tipo_atendimento";

                              }

                          }

                      }
//                       echo $sql;
                      $res = pg_query ($con, $sql);

                        if (!$verifica_atendimento_latina) {
                              if($login_fabrica == 7){
                                  $natureza_default = "Contrato";
                              }

                              for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                                  $codigo  = str_pad(pg_fetch_result($res, $i, 'codigo'), 2, '0', STR_PAD_LEFT);
                                  $desc    = pg_fetch_result($res, $i, 'descricao');
                                  $tipo_at = pg_fetch_result($res, $i, 'tipo_atendimento');

                                  $selected = '';
                                  $opt_sel  = '';
                                  if($login_fabrica == 7 && strtolower($natureza_default) == strtolower($desc)){
                                      $selected = " selected ";
                                  }

                                  $txt_option = ($login_fabrica != 90) ? "$codigo - $desc" : $desc;
                                  $selected   = ($tipo_atendimento == $tipo_at) ? ' SELECTED':$selected;

                                  if (isFabrica(19)) {
                                      if (!in_array($codigo, ['00', '14', '18','20'])) {
                                          if (
                                              ($qtde_produtos >= 2 and $codigo == '16')
                                              or
                                              ((!$qtde_produtos or $qtde_produtos == 1) and $codigo == '15')
                                          ) {
                                              $opt_sel .= ' disabled';
                                          }
                                          if ($codigo > 16 && $codigo != 20) $opt_sel .= " rel='LS'";
                                          if (($permiteLS and $codigo < 14) or (!$permiteLS  and $codigo > 16)) $opt_sel .= " disabled";
                                      }
                                      if (in_array($codigo, ['00', '14', '20'])) {
                                          $opt_sel = " rel='all'";
                                      }
                                  }
                                  echo "<option $selected value='$tipo_at'$opt_sel> ".traduz($txt_option)."</option>";
                            }

                        } else {

                              if (pg_num_rows($res_latina) > 0) {


                                  echo "<option value=\"21\"";
                                  if ($tipo_atendimento == 21 ) echo " SELECTED ";
                                  echo ">01 - Garantia (Com Deslocamento)</option>";
                                  if ($tipo_posto == 174) echo "<option value=\"22\">02 - Instalação</option>";

                                  echo "<option value=\"23\"";
                                  if ($tipo_atendimento == 23 ) echo " SELECTED ";
                                  echo ">03 - Garantia (Sem Deslocamento)</option>";


                              } else {

                                  if ($tipo_posto == 174){
                                      echo "<option value=\"22\"";
                                      if ($tipo_atendimento == 22 ) echo " selected ";
                                      echo ">02 - Instalação</option>";

                                  }

                                  echo "<option value=\"23\"";
                                  if ($tipo_atendimento == 23 ) echo " SELECTED ";
                                  echo ">03 - Garantia (Sem Deslocamento)</option>";

                              }

                        }
                    }
                    if ($login_fabrica == 19 OR $login_posto == 6359) {

                        $sql = " SELECT atende_comgas FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
                        $res = pg_query($con,$sql);

                        $atende_comgas = pg_fetch_result($res,0,0);
                        if (strlen($atende_comgas) > 0 and $atende_comgas == 't') {
                            echo "<option ";
                            if ($permiteLS) echo " disabled";
                        if ($tipo_atendimento == 20 ) echo " selected ";
                            echo " value='20'>08 - Atend.Comgás</option>\n";
                        }

                    }?>
                </select>
            </td><?php

        }

        if ( in_array($login_fabrica, array(6,11,172)) ) {

            if ($login_posto == 4262 or $login_fabrica == 11) {

                echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='rg_produto'>Rg do Produto</label></font><br />";
                echo "<input type='text' name='rg_produto' class='frm' id='rg_produto' size='12' maxlength='10' value='$rg_produto'>";
                echo "</td>";

            }

            if ( !in_array($login_fabrica, array(11,172)) ) {

                echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Posto</label></font><br />";
                    echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='10' value='$os_posto'>";
                echo "</td>";

            }

        } else if ($login_fabrica == 30) {//HD 65178

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Revendedor</label></font><br />";
                echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='20' value='$os_posto'>";
            echo "</td>";

        } else if ($login_fabrica == 2) { // HD 81252

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Posto</label></font><br />";
                echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='10' value='$os_posto'>";
            echo "</td>";

        }  else if ($login_fabrica == 35) { // HD 1387089

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto'>OS Interna</label></font><br />";
                echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='10' value='$os_posto'>";
            echo "</td>";

        } else if ($login_fabrica == 50) {//HD 79844

            echo "<td align='left' width='110' valign='top' nowrap><font size='1' color='red' face='Geneva, Arial, Helvetica, san-serif'><label for='data_fabricacao'>Data Fabricação</label></font><br />";
                echo "<input type='text' name='data_fabricacao' class='frm' id='data_fabricacao' size='12' maxlength='10' value='$data_fabricacao'>";
            echo "</td>";

        } else if (in_array($login_fabrica, [144]) && $posto_interno) {
            echo "<td align='left' width='110' valign='top' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'><label for='os_posto' style='color:red;'>Número Único</label></font><br />";
                echo "<input type='text' name='os_posto' class='frm' id='os_posto' size='12' maxlength='20' value='$os_posto'>";
            echo "</td>";
        }

        if ( !in_array($login_fabrica, array(7,11,15,172)) ) {

            echo "<td nowrap  valign='top'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>&nbsp;<span rel='prateleira_box'>&nbsp;".traduz('Box').('/').traduz('Prateleira')."</span></font><br />";
            echo "&nbsp;<INPUT TYPE='text' id='prateleira_box' name='prateleira_box' class='frm' value='$prateleira_box' size='8' maxlength='10'>";
            echo "</td>";

        }?>
        </tr><?php

        if (in_array($login_fabrica, [19])) { ?>
          <table id="anexo_certificado" width="100%" align='center' border="0" cellspacing="5" cellpadding="0" style="display: none;">
              </tr>
                    <td align="center">
                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif;color: red;"> <?= traduz("Certificado de instalação") ?> </label>
                        <input type='file' name='certificado_instalacao' class='frm' />
                    </td>
                </tr>
            </table>
        <?php
        }

        if($login_fabrica == 117 or $login_fabrica == 128){
        ?>
            <table width="100%" align='center' border="0" cellspacing="5" cellpadding="0" >
                <tr>
                    <td>
                        <?
                            $checked_garantia = ($garantia_estendida) ? "checked" : "";
                        ?>
                        <input type='checkbox' value='t' class='frm' name='garantia_estendida' id='garantia_estendida' <?=$checked_garantia?>>&nbsp;
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Garantia Estendida</font>
                    </td>
                    <td id='op_garantia_estendida' style='display:none;'>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Instalado por uma autorizada <?php echo ($login_fabrica == 117) ? "Elgin" : "Unilever"; ?> : </font>
                        <input type='radio' name='opcao_garantia_estendida' value='t' class='frm' <? echo ($opcao_garantia_estendida == "t") ? "checked" : "";?>>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Sim</font> &nbsp;
                        <input type='radio' name='opcao_garantia_estendida' value='f' class='frm' <? echo ($opcao_garantia_estendida == "f") ? "checked" : "";?>>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Não</font>
                    </td>
                </tr>
              </tr>
                  <td></td>
                    <td id='nf_garantia_estendida' style='display:none;'>
                        <label title="Inserir a imagem digitalizada da CTI, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX. Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC." style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> <? echo ($login_fabrica == 117) ? "Anexar CTI:" : "Anexar Certificado de Garantia Estendida:"; ?> </label>
                         <span title="Inserir a imagem digitalizada da CTI, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX. Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC." style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
                        <input type='file' name='nf_garantia_estendida' class='frm' title="Inserir a imagem digitalizada da CTI, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX.  Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC.">
                    </td>
                </tr>
            </table>
        <?php
        }

        if ($login_fabrica == 43) {//HD 73930 18/02/2009?>
            <tr>
                <td colspan = '2'></td>
                <td valign='top' align='left'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">COA Microsoft</font>
                    <br />
                    <input rel="coa" class="frm" type="text" name="coa_microsoft" id="coa_microsoft" size="40" maxlength="29" value="<? echo $coa_microsoft;?>" onBlur="javascript: this.value=this.value.toUpperCase(); this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o COA Microsoft.');">
                </td>
            </tr><?php
        }

        if (in_array($login_fabrica, array(40)))
        {

        ?>

            </table>

            <center>
            <table border='0' id='unidade_cor' style='display: none;'>
            <input type='hidden' name='familia' id='familia' value='<?=$familia?>'>
            <tr>
                <td bgcolor='#FFAE00' width='28px' align='center'>
                    <input type='radio' name='unidade_cor' id='unidade_cor' value='amarelo' <? if($unidade_cor == 'amarelo') echo "CHECKED"; ?>>
                </td>
                <td align='center' nowrap>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidade Amarela</font>
                </td>
                <td>
                    &nbsp;
                </td>
                <td bgcolor='#1E1E1E' width='28px' align='center'>
                    <input type='radio' name='unidade_cor' id='unidade_cor' value='preto' <? if($unidade_cor == 'preto') echo "CHECKED"; ?>>
                </td>
                <td align='center' nowrap>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Unidade Preta</font>
                </td>
            </tr>

            </table>
            </center>

        <?
        }


if ($login_fabrica == 19) {
  echo "<table>";
    echo "<thead>";
      echo "<tr>";
        echo "<th>";
          echo "<font size='1' color='red' style='margin: 0px 107px 0px 0px;'><span rel='defeito_reclamado' id='defeito_reclamado_descricao_title'>Defeito Reclamado</span></font>";
        echo "</th>";
        echo "<th>";
        echo "<font size='-2' style='margin-left: 41px;'>Para gravar a OS é necessário adicionar os defeitos reclamados, basta clicar em ADICIONAR DEFEITOS</font>";
        echo "</th>";
      echo "<tr>";
    echo "</thead>";
    echo "<tbody>";
      echo "<tr>";
        echo "<td>";
          echo "<select name='defeito_reclamado' id='defeito_reclamado' style='width: 200px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' class='frm' $defeito_reclamado_onchange>";
            if (strlen($defeito_reclamado) > 0 || strlen($defeito_reclamado) == 0) {
              if(!empty($defeito_reclamado)) {
                $sql = " SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado";
                $res = pg_query($con,$sql);

                  if (pg_num_rows($res) > 0) {
                    $reclamado_descricao = pg_fetch_result($res, 0, 'descricao');
                  }
              }

              echo "<option id='opcoes' value='$defeito_reclamado' title='$reclamado_descricao'>$reclamado_descricao</option>";

            } else {
                echo "<option id='opcoes' value=''></option>";
            }

        echo "</select>";
      echo "</td>";
      echo "<td>";
        echo "<input style='margin-left: 41px;' type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'><br />";
      echo "</td>";
      echo "</tr>";
    echo "</tbody>";
  echo "</table>";
    echo "<table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='400' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
      <thead>
        <tr bgcolor='#596D9B' style='color:#FFFFFF;'>
          <td align='center'><b>Defeito Reclamado</b></td>
          <td align='center'><b>Ações</b></td>
        </tr>
      </thead>
      <tbody>";
        if (strlen($os) > 0) {
          $sql_cons = "SELECT
                          tbl_defeito_constatado.defeito_constatado,
                          tbl_defeito_constatado.descricao         ,
                          tbl_defeito_constatado.codigo
                        FROM tbl_os_defeito_reclamado_constatado
                        JOIN tbl_defeito_constatado USING(defeito_constatado)
                        WHERE os = $os";
          $res_dc = pg_query($con, $sql_cons);

          if (pg_num_rows($res_dc) > 0) {

            for ($x = 0; $x < pg_num_rows($res_dc); $x++) {

                $dc_defeito_constatado = pg_fetch_result($res_dc, $x, 'defeito_constatado');
                $dc_descricao          = pg_fetch_result($res_dc, $x, 'descricao');
                $dc_codigo             = pg_fetch_result($res_dc, $x, 'codigo');

                $aa = $x + 1;

                echo "<tr>";
                    echo "<td><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";
                    echo "<td align='right'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
                echo "</tr>";

            }

            echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
          }

        }

    echo "</tbody></table>";
}

if ($login_fabrica == 1) { ?>

    <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr valign='top'>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
                <br />
                <input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
            </td>
            <td nowrap>
            </td>
            <td nowrap><?// HD15589?>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT/Porter Cable</font>
                <br />
                <input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
            </td>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
                <br />
                <input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo técnico.');">
            </td>
        </tr>
    </table><?php

}?>

<hr>
<input type="hidden" name="consumidor_cliente">
<input type="hidden" name="consumidor_rg">
<?php
  //HD-3139131
  $readonlyConsumidor = '';
  if ($login_fabrica == 104 && $_GET['pre_os'] == 't') {
    $readonlyConsumidor = ' readonly="readonly"';
  }

  $tamanho_tabela = ($login_fabrica == 15) ? 900 : '100%';
?>
<table width="<?=$tamanho_tabela?>" align='center' border="0"    cellspacing="5" cellpadding="0" class="multiCep">
    <tr>
        <td align='left'>

            <? if ($login_fabrica == 30 && $consumidor_revenda == "C") { ?>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif"><?traduz("Nome Consumidor")?></font>
                </acronym>
                <br/>
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" name="consumidor_nome" id="consumidor_nome" size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">
                    <? if($login_fabrica == 7 OR $login_fabrica == 30){ ?>
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
                    <?}?>
                    &nbsp;
                </acronym>
            <? } else { ?>
                    <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_nome'><?php echo ($login_fabrica == 122) ? "Nome Cliente" :"&nbsp;&nbsp;".traduz("Nome Consumidor"); ?></span></font>
                    <br />

                    <?
              $tamanho_campo = ($login_fabrica == 15) ? 50 : 30;
                    ?>
                     <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm" type="text" <?php echo $readonlyConsumidor;?> name="consumidor_nome" id="consumidor_nome" size="<?=$tamanho_campo?>" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>
                     <? if($login_fabrica==24){?> readonly <?}?>
                      onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">

                    <? if($login_fabrica == 7 OR $login_fabrica == 30){ ?>
                        <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
                    <?}?>
                    <?php 
                    $espaco_mensagem = "";
                    if ($login_fabrica == 24) {  
                      $espaco_mensagem = "<br>  &nbsp;";
                      echo "<br> <font color='#AA0000'size='1' face='Geneva, Arial, Helvetica, san-serif'>*Para preencher esse campo é necessário CPF </font>";
                    } ?>
                    &nbsp;
            <? } ?>
        </td>

        <?php if ($login_fabrica == 24) { ?>
          <input class="frm" type="hidden" name="consumidor_id" id="consumidor_id" value="<?= $_POST['consumidor_id'];?>">

        <?php } ?>

        <? //if($login_fabrica<>19){ ?>
            <td><?php if($login_fabrica == 24){
		if($_POST['cpf_cnpj_revenda_suggar'] == "CNPJ"){
			$check_cnpj = " checked ";
		}elseif($_POST["cpf_cnpj_revenda_suggar"] == "CPF"){
			$check_cpf = " checked ";
        }
            if($consumidor_revenda == null){
                $checked = ' checked ';
            }
		
                echo '<input class="consumidor_revenda" type="radio" name="cpf_cnpj_revenda_suggar" value="CNPJ"'." $check_cnpj".''.$checked.' >';
                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CNPJ&nbsp;&nbsp;&nbsp;</span></font>';
                echo '<input class="consumidor_revenda" type="radio" name="cpf_cnpj_revenda_suggar" value="CPF"'." $check_cpf".' >';
                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CPF</span></font>';
            }else{
                ?>
        		    <font <?php echo (in_array($login_fabrica, array(3,19,30,35,72,74,91))) ? "color='red'" : ""; ?>size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cpf'>&nbsp;&nbsp;&nbsp;<?=traduz("CPF/CNPJ")?>&nbsp;&nbsp;<?php echo ($login_fabrica == 122) ? "Cliente" : traduz("Consumidor"); ?></span></font>
            <?php } ?>
                <br />
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;

                <?php if ($login_fabrica == 24) {?>
                  <input class="frm" type="text" name="consumidor_cpf" id="consumidor_cpf" onblur="nome_readonly(document.frm_os.consumidor_cpf,'cpf'); this.className='frm'" 
                  value="<?=$_POST['consumidor_cpf']?>">
                  <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: nome_readonly(document.frm_os.consumidor_cpf, "cpf");' style='cursor: pointer'>
                <?php } else { ?> 
                  <input class="frm" type="text" <?php echo $readonlyConsumidor;?> name="consumidor_cpf"  id="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">
                <?php } 
                
                echo $espaco_mensagem;
                
                if($login_fabrica == 124) { ?>
                <span title="NECESSÁRIO PARA CONSULTA VIA SITE" style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
                <?}?>
                <? if($login_fabrica == 7 OR $login_fabrica == 30 ) { ?>
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
                <?}?>
                <? if(($login_fabrica== 79))
                    echo "<font color='#FF0000'>*</font>"; // HD 78055?>
                &nbsp;
            </td>
        <? // } ?>

        <?php if(in_array($login_fabrica, array(35,104))){ ?>

          <td>
              <font size="1" <?php if($login_fabrica == 35){ echo "color=red"; } ?> face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
              <br />
              <input class="frm telefone" type="text" name="consumidor_celular" id="consumidor_celular" size="15" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on telefone'; displayText(' Insira o celular com o DDD. ex.: 14944556677.');" maxlength="11" rel='fone'>
              <span style='font-size:10px;color:#8F8F8F'></span>
          </td>

        <?php } ?>

        <td <?php echo (in_array($login_fabrica, [104,123])) ? "colspan='1'" : "colspan='2'"; ?>>
            <?if ($login_fabrica == 30) { ?>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000" size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
                    <br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm telefone" type="text" rel='fone' name="consumidor_fone" id="consumidor_fone" size="16" value="<?php echo $consumidor_fone; ?>" onblur="this.className='frm telefone'; displayText('&nbsp;');" onfocus="this.className='frm-on telefone'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14944556677.');">
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_fone,"fone")'  style='cursor: pointer'>
                </acronym>
            <?php }else{ ?>
                <font size="1" <?php if (in_array($login_fabrica, [19,42,72,90])) { echo "color='red'"; } if(in_array($login_fabrica, array(7, 14, 30, 45, 50, 51, 80, 95))){ echo "color=true"; } ?> face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_fone'>&nbsp;&nbsp;<?=traduz("Fone")?> <?php echo ( in_array($login_fabrica, array(11,172)) ) ? "Residencial" : ""; ?> </span></font>
                <br />
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm telefone fone_c" type="text" <?php echo $readonlyConsumidor;?> rel='fone' name="consumidor_fone" id="consumidor_fone" size="16" value="<?php echo $consumidor_fone; ?>" onblur="this.className='frm telefone'; displayText('&nbsp;');" onkeypress='return SomenteNumero(event)' onfocus="this.className='frm-on telefone'; displayText('&nbsp;Insira o telefone <?php echo ( in_array($login_fabrica, array(11,123,172)) ) ? "Residencial" : ""; ?> com o DDD. ex.: 14944556677.');"

                <?php if( $login_fabrica==19 or $login_fabrica==3 or $login_fabrica==50 ){ ?>
                    maxlength="16"
                <?php }else{ ?>
                    maxlength="20" <?}?>>
                <?php if( $login_fabrica==19 or $login_fabrica==3 ){ ?>
                        <span style='font-size:10px;color:#8F8F8F'></span>
                <?php } ?>
                <?php if( $login_fabrica == 30 ){ ?>
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor    (document.frm_os.consumidor_fone,"fone")'  style='cursor: pointer'>
                <?php } ?>
                <?php if( $login_fabrica == 79 )
                    echo "<font color='#FF0000'>*</font>"; // HD 78055?>
            <?php } echo $espaco_mensagem; ?>
        </td>
<?php
            if ($login_fabrica == 19) {
?>
              <td valign='top' align='left'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                    <span rel='consumidor_email'> Email </span><?php
                    $tamanho_campo = 30;
                    ?>
                </font>
                <br />
                <INPUT TYPE='text' <?=$style_email?> name='consumidor_email' <?php echo $readonlyConsumidor;?> id='consumidor_email' class='frm' value='<? echo $consumidor_email ?>' size='<?=$tamanho_campo?>' maxlength='50' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
              </td>
<?php
            }

           if(in_array($login_fabrica, array(11,50,101,123,172))){ ?>
          <td>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_fone'><?=($login_fabrica == 123) ? "Celular" : "Fone Celular"?></span></font>
            <br />
            <input class="frm telefone" type="text" rel='fone' name="consumidor_celular" id="consumidor_celular" size="16" value="<?php echo $consumidor_celular; ?>" onblur="this.className='frm telefone'; displayText('&nbsp;');" onfocus="this.className='frm-on telefone'; displayText('&nbsp;Insira o telefone <?php echo ( in_array($login_fabrica, array(11,50,123,172)) ) ? "Celular" : ""; ?> com o DDD. ex.: 14/94455-6677.');">
          </td>
        <?php }?>
        <?php if( in_array($login_fabrica, array(11,172)) ){ ?>
           <td>
              <font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone Rec</font>
              <br />
              <input class="frm" type="text" rel='fone' name="consumidor_fone_recado" id="consumidor_fone_recado"   size="16" value="<? echo $consumidor_fone_recado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14944556677.');"
              maxlength="20">
          </td>
         <?php } ?>
        <td>
            <font size="1" color = "red" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cep'>&nbsp;<?=traduz("CEP")?></span></font>
            <br />
            <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm addressZip" type="text" name="consumidor_cep" <?php echo $readonlyConsumidor;?> id="consumidor_cep"  size="12" maxlength="10" value="<? echo $consumidor_cep ?>"
        onkeypress="mascara(this,soNumeros)"
        <?php if($login_fabrica != 91){?>
        onblur="this.className='frm addressZip'; displayText('&nbsp;'); noReadonlyCidade(); "
        <?php } ?>
        onfocus="this.className='frm-on addressZip'; displayText('&nbsp;Digite o CEP do consumidor.');"
        />
        <?php if($login_fabrica == 91){?>
        <input type='button' value='Pesquisar' class='frm' onclick="if(document.frm_os.consumidor_cep.value.length < 8) {alert('Informe um CEP válido!');}else{ buscaCEP(document.frm_os.consumidor_cep.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;}" style='cursor: pointer' />
        <?php } echo $espaco_mensagem; ?>
        </td> 
    </tr>
<!-- </table> -->

<?php
    $tamanho_tabela = ($login_fabrica == 15) ? 900 : 750;
    $tamanho_coluna = ($login_fabrica == 15) ? "style='width:457px'" : '';

?>
<!-- <table width='<?=$tamanho_tabela?>' align='center' border='0' cellspacing='5' cellpadding='2'> -->
    <tr>
        <?php if( $login_fabrica == 30 ){ ?>
            <td align='left' nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
                    <br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm address" type="text" name="consumidor_endereco"   id='consumidor_endereco' size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm address'; displayText('&nbsp;');" onfocus="this.className='frm-on address'; displayText('&nbsp;Digite o endereço do consumidor.');">
                </acron
            </td>
        <?php }else{ ?>
            <td align='left' nowrap <?=$tamanho_coluna?>>
                <?php
                    $tamanho_campo = ($login_fabrica == 15) ? 50 : 30;
                    if (in_array($login_fabrica, array(42,80))) {
                      $color = "color = 'red'";
                    }
                ?>
                <font size="1" <?=$color?> face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_endereco'>&nbsp;&nbsp;<?=traduz("Endereço")?></span></font><br />

                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm address" type="text" name="consumidor_endereco"  <?php echo $readonlyConsumidor;?>  id='consumidor_endereco' size="<?=$tamanho_campo?>" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm address'; displayText('&nbsp;');" onfocus="this.className='frm-on address'; displayText('&nbsp;Digite o endereço do consumidor.');">

            </td>
        <?php } ?>

        <?php if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font><br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm" type="text" name="consumidor_numero"  id='consumidor_numero' size="5" maxlength="10" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
                </acronym>
            </td>
        <?php } else { ?>
            <td nowrap>
                <font size="1" color = "red" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_numero">&nbsp;&nbsp;<?=traduz("Número")?></span></font><br />
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm" type="text" name="consumidor_numero"  <?php echo $readonlyConsumidor;?> id='consumidor_numero' size="5" maxlength="10" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
            </td>
        <?php } ?>
        <td nowrap>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_complemento'><?=traduz("Complemento")?></span></font><br />
            <input class="frm" type="text"  <?php echo $readonlyConsumidor;?> name="consumidor_complemento" id="consumidor_complemento"  size="10" maxlength="20" value="<? echo $consumidor_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
        </td>
        <?php if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz("Bairro")?></font><br />
                </acronym>
                <acronym title='Campo Obrigatório'>
                    <input class="frm addressDistrict" type="text" name="consumidor_bairro"  id='consumidor_bairro' size="15" maxlength="80" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm addressDistrict'; displayText('&nbsp;');" onfocus="this.className='frm-on addressDistrict'; displayText('&nbsp;Digite o bairro do consumidor.');">
                </acronym>
            </td>
        <?php } else {
            if (in_array($login_fabrica, array(42,80))) {
              $color = "color = 'red'";
            }
            ?>
            <td nowrap>
                <font size="1" <?=$color?> face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_bairro'>&nbsp;&nbsp;<?=traduz("Bairro")?></span></font><br />
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="frm addressDistrict" type="text" <?php echo $readonlyConsumidor;?> name="consumidor_bairro"  id='consumidor_bairro' size="15" maxlength="80" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm addressDistrict'; displayText('&nbsp;');" onfocus="this.className='frm-on addressDistrict'; displayText('&nbsp;Digite o bairro do consumidor.');">
            </td>
        <?php } ?>
        <?php
            $cons_cidade_readonly = '';
            if ($login_fabrica == 30) {
                //solicitação de tirar por Eduardo hd: 11318
                //$cons_cidade_readonly = 'readonly';
                $cons_cidade_readonly = '';
            }
        ?>
        <?php if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000" size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font><br />
                <acronym title='Campo Obrigatório'>
                </acronym>
                  <select id="consumidor_cidade" name="consumidor_cidade" class="frm addressCity" style="width:100px">
                    <option value="" >Selecione</option>
                    <?php
                        if (strlen($consumidor_estado) > 0) {
                            $sql = "SELECT DISTINCT * FROM (
                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                while ($result = pg_fetch_object($res)) {
                                    $selected  = (trim($result->cidade) == $consumidor_cidade) ? "SELECTED" : "";

                                    echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                }
                            }
                        }
                    ?>
                  </select>
                </acronym>
            </td>
        <?php } else {
                echo ($login_fabrica == 15) ? "</tr><tr>" : "";
                $tamanho_campo = ($login_fabrica == 15) ? 50 : 30;
        ?>
              <td nowrap><font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_estado'>&nbsp;&nbsp;<?=traduz("Estado")?></span></font><br />

                  <center>
                      <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
                      <select name="consumidor_estado" <?php echo $readonlyConsumidor;?> id='consumidor_estado' size="1" class="frm addressState">
                          <option value=""><?= traduz("selecione") ?></option>
                          <?php
                          $arrEstados = getListaDeEstadosDoPais($login_pais);

                          foreach ($arrEstados as $key => $dadosEstados) {

                            $selected = ($consumidor_estado == $dadosEstados['sigla']) ? "selected" : "";

                            ?>
                              <option value="<?= $dadosEstados['sigla'] ?>" <?= $selected ?>><?= $dadosEstados['descricao'] ?></option>
                          <?php
                          } ?>
                      </select>

              </td>
           
        <?php } ?>

        <?php if ($login_fabrica == 30) { ?>
            <td nowrap>
                <acronym title='Campo Obrigatório'>
                    <font color="#AA0000"size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font><br />
                </acronym>
                <center>
                    <acronym title='Campo Obrigatório'>
                        <select name="consumidor_estado" id='consumidor_estado' size="1" class="frm addressState">
                            <option value=""   <? if (strlen($consumidor_estado) == 0)    echo " selected "; ?>></option>
                            <option value="AC" <? if ($consumidor_estado == "AC") echo " selected "; ?>>AC</option>
                            <option value="AL" <? if ($consumidor_estado == "AL") echo " selected "; ?>>AL</option>
                            <option value="AM" <? if ($consumidor_estado == "AM") echo " selected "; ?>>AM</option>
                            <option value="AP" <? if ($consumidor_estado == "AP") echo " selected "; ?>>AP</option>
                            <option value="BA" <? if ($consumidor_estado == "BA") echo " selected "; ?>>BA</option>
                            <option value="CE" <? if ($consumidor_estado == "CE") echo " selected "; ?>>CE</option>
                            <option value="DF" <? if ($consumidor_estado == "DF") echo " selected "; ?>>DF</option>
                            <option value="ES" <? if ($consumidor_estado == "ES") echo " selected "; ?>>ES</option>
                            <option value="GO" <? if ($consumidor_estado == "GO") echo " selected "; ?>>GO</option>
                            <option value="MA" <? if ($consumidor_estado == "MA") echo " selected "; ?>>MA</option>
                            <option value="MG" <? if ($consumidor_estado == "MG") echo " selected "; ?>>MG</option>
                            <option value="MS" <? if ($consumidor_estado == "MS") echo " selected "; ?>>MS</option>
                            <option value="MT" <? if ($consumidor_estado == "MT") echo " selected "; ?>>MT</option>
                            <option value="PA" <? if ($consumidor_estado == "PA") echo " selected "; ?>>PA</option>
                            <option value="PB" <? if ($consumidor_estado == "PB") echo " selected "; ?>>PB</option>
                            <option value="PE" <? if ($consumidor_estado == "PE") echo " selected "; ?>>PE</option>
                            <option value="PI" <? if ($consumidor_estado == "PI") echo " selected "; ?>>PI</option>
                            <option value="PR" <? if ($consumidor_estado == "PR") echo " selected "; ?>>PR</option>
                            <option value="RJ" <? if ($consumidor_estado == "RJ") echo " selected "; ?>>RJ</option>
                            <option value="RN" <? if ($consumidor_estado == "RN") echo " selected "; ?>>RN</option>
                            <option value="RO" <? if ($consumidor_estado == "RO") echo " selected "; ?>>RO</option>
                            <option value="RR" <? if ($consumidor_estado == "RR") echo " selected "; ?>>RR</option>
                            <option value="RS" <? if ($consumidor_estado == "RS") echo " selected "; ?>>RS</option>
                            <option value="SC" <? if ($consumidor_estado == "SC") echo " selected "; ?>>SC</option>
                            <option value="SE" <? if ($consumidor_estado == "SE") echo " selected "; ?>>SE</option>
                            <option value="SP" <? if ($consumidor_estado == "SP") echo " selected "; ?>>SP</option>
                            <option value="TO" <? if ($consumidor_estado == "TO") echo " selected "; ?>>TO</option>
                        </select>
                    </acronym>
                </center>
            </td><?php

        } else { ?>

             <td nowrap>
                <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cidade'><?=traduz("Cidade")?></span></font><br />

                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>

                <select id="consumidor_cidade" <?php echo $readonlyConsumidor;?> name="consumidor_cidade" class="frm addressCity" style="width:100px">
                  <option value="" ><?=traduz("Selecione")?></option>
                  <?php
                      if($_GET['pre_os'] == 't' AND $_GET['hd_chamado'] > 0){ //hd_chamado=2905059
                        $sqlEstado = "SELECT tbl_cidade.estado
                                        FROM tbl_hd_chamado_extra
                                        JOIN tbl_cidade ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
                                        WHERE hd_chamado = $hd_chamado";
                        $resEstado = pg_query($con, $sqlEstado);
                        $consumidor_estado = pg_fetch_result($resEstado, 0, estado);
                      }
                      if (strlen($consumidor_estado) > 0) {

                          if ($login_pais == "BR") { 
                            $sql = "SELECT DISTINCT * FROM (
                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC";
                          } else {
                            $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                                    FROM tbl_cidade 
                                    WHERE UPPER(estado_exterior) = UPPER('{$consumidor_estado}')
                                    AND UPPER(pais) = UPPER('{$login_pais}')
                                    ";
                          }

                          $res = pg_query($con, $sql);

                          if (pg_num_rows($res) > 0) {
                              while ($result = pg_fetch_object($res)) {
                                  $selected  = (trim($result->cidade) == $consumidor_cidade) ? "SELECTED" : "";

                                  echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                              }
                          }
                      }
                  ?>
              </select>
            </td><?php

        }?>
        </tr>
        <tr>
            <? if ($login_fabrica == 52) {
                        $ponto_referencia = (isset($_POST['ponto_referencia'])) ? trim($_POST['ponto_referencia']) : '' ;
            ?>
                <td valign='top' align='left'>
                    <label style='font: 10px "Arial" '>Ponto de Referência</label>
                    <br>
                    <input type="text" name="ponto_referencia" id="ponto_referencia" class='frm' value="<?=$ponto_referencia?>">
                </td>
            <? } ?>


          <?php if ($login_fabrica != 19) { ?>
            <td valign='top' align='left'>
                <font size="1" <?php if (!in_array($login_fabrica, array(30))) { echo 'color="red"'; } ?> face="Geneva, Arial, Helvetica, san-serif">
                    <span rel='consumidor_email'> Email </span><?php
                    echo ($login_fabrica == 15) ? "* " : null; //HD 722524
                    $style_email = ($login_fabrica == 15) ? "style='background-color:#FFCCCC'" : null; //HD 722524
                    $tamanho_campo = ($login_fabrica == 15) ? 50 : 30;
                    ?>
                </font>
                <br />
                <INPUT TYPE='text' <?=$style_email?> name='consumidor_email' <?php echo $readonlyConsumidor;?> id='consumidor_email' class='frm' value='<? echo $consumidor_email ?>' size='<?=$tamanho_campo?>' maxlength='50' <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
                <? if($login_fabrica== 79) echo "<font color='#FF0000'>*</fotn>"; // HD 78055?>
            </td>
            <?php }
               if($login_fabrica == 35){ ?>
            <td><br>
                <input type="radio" name="informaemail" value="Não possui e-mail" <?php if($informaemail == 'Não possui e-mail'){ echo " checked ";} ?>>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span > Não possui e-mail </span>
                </font>

            </td>
            <td><br>
              <input type="radio" name="informaemail" value="Não deseja informar e-mail" <?php if($informaemail == "Não deseja informar e-mail"){echo " checked ";} ?>>
              <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span > Não deseja informar e-mail </span>
                </font>
            </td>
            <?php
            }
            if ($login_fabrica==59){
		          $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
         	    $res = pg_query($con,$sql);
		          $tipo_posto = pg_fetch_result($res,0,'tipo_posto');
              if ($tipo_posto == 464){
	             if (strlen($os)>0){
                $sql = "SELECT campos_adicionais from tbl_os_campo_extra where os = $os";
                $res = pg_query($con,$sql);
                $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'), true);

                foreach ($campos_adicionais as $key => $value) {
                    $$key = $value;
                }
              }
              ?>


             <td nowrap><font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif"><span rel='origem'>Origem</span></font><br />

                <center>
                    <b style="color: #f00; display: none;">*</b>&nbsp;<select name="origem" id='origem' size="1" class="frm">

                       <option value=""<? if (strlen($origem) == 0)     echo " selected "; ?>></option>
                        <option value="recepcao"<? if ($origem == "recepcao")    echo " selected "; ?>>Recepção</option>
                        <option value="sedex_reverso" <? if ($origem == "sedex_reverso") echo " selected "; ?>>Sedex reverso</option>
                    </select>
              </td>
            <?php
          		}
            } ?>

            <?php if ($login_fabrica == 74): ?>
            <td valign='top' align='left'>
                <font size="1" color="red" face="Geneva, Arial, Helvetica, san-serif">
                    <span rel='data_nascimento'> Data de Nascimento </span>
                </font>
                <br />
                <INPUT TYPE='text' rel='data' name='data_nascimento' id='data_nascimento' class='frm' value='<?php echo $data_nascimento ?>' size='16'>
            </td>
            <?php endif ?>

            <?php
            if (in_array($login_fabrica,array(30,43,52,74))) {?>

                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
                    <br />
                    <input class="frm telefone" type="text" rel='fone' name="consumidor_celular" id="consumidor_celular"   size="16" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14944556677.');" />
                </td>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
                    <br />
                    <input class="frm telefone" type="text" rel='fone' name="consumidor_fone_comercial" id="consumidor_fone_comercial"   size="16" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14944556677.');" />
                </td><?php

            }

            if ($login_fabrica == 122) {?>

                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">CPD do Cliente</font>
                    <br />
                    <input class="frm" type="text" name="consumidor_cpd" id="consumidor_cpd"   size="15" value="<? echo $consumidor_cpd ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número do CPD');" />
                </td>
               <td colspan='4'>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Contato</font>
                    <br />
                    <input class="frm" type="text" name="consumidor_contato" id="consumidor_contato"   size="15" value="<? echo $consumidor_contato ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o nome do contato.');" />
                </td><?php

            }

            if ($login_fabrica == 7) { ?>

                <td></td>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Distância Cliente (KM)</font>
                    <br />
                    <input class="frm" type="text" name="deslocamento_km"  id='deslocamento_km' size="14" maxlength="7" value="<? echo $deslocamento_km ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
                </td><?php

            }

            if ( in_array($login_fabrica, array(3,45,59,80,104,120,201))) { // HD67164 HD 260273?>
                <?php if($login_fabrica != 104){ ?>

                  <td>
                      <font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Celular</font>
                      <br />
                      <input class="frm telefone" type="text" name="consumidor_celular" id="consumidor_celular" size="15" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" maxlength="14" rel='fone'>
                      <span style='font-size:10px;color:#8F8F8F'></span>
                  </td>

                <?php } ?>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
                    <br />
                    <input class="frm telefone" type="text"  <?php echo $readonlyConsumidor;?> name="consumidor_fone_comercial" id="consumidor_fone_comercial" size="15" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='frm'; displayText('&nbsp;');" maxlength="14" rel='fone' />
                    <span style='font-size:10px;color:#8F8F8F'></span>
                </td><?php
            }?>
        </tr>
    </table><?php

    if ($calcula_km || in_array($login_fabrica, array(15,24, 140))) {

        //--== Calculo de Distância com Google MAPS =========================================

        $sql_posto = "SELECT contato_endereco AS endereco,
                             contato_numero   AS numero  ,
                             contato_bairro   AS bairro  ,
                             contato_cidade   AS cidade  ,
                             contato_estado   AS estado  ,
                             contato_cep      AS cep
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto USING(posto)
                        WHERE posto   = $login_posto
                        AND   fabrica = $login_fabrica ";

        $res_posto = pg_query($con, $sql_posto);

        //  14/07/2010 MLG - HD 264024 - Retirei o bairro do endereço (confunde o GoogleMaps) e adicionei, se tem, a latitude e longitude.
        if (pg_num_rows($res_posto) > 0) {

            $endereco_posto = "";

            while ($data = pg_fetch_object($res_posto)) {
                echo "<input type='hidden' name='contato_endereco' id='contato_endereco' value='".$data->endereco."' />";
                echo "<input type='hidden' name='contato_numero' id='contato_numero' value='".$data->numero."' />";
                echo "<input type='hidden' name='contato_bairro' id='contato_bairro' value='".$data->bairro."' />";
                echo "<input type='hidden' name='contato_cidade' id='contato_cidade' value='".$data->cidade."' />";
                echo "<input type='hidden' name='contato_estado' id='contato_estado' value='".$data->estado."' />";
                echo "<input type='hidden' name='contato_cep' id='contato_cep' value='".$data->cep."' />";

                $endereco_posto .= ($data->endereco != "") ? $data->endereco : "";
                $endereco_posto .= ($data->numero != "") ? ", ".$data->numero : "";
                $endereco_posto .= ($data->bairro != "") ? ", ".$data->bairro : "";
                $endereco_posto .= ($data->cidade != "") ? ", ".$data->cidade : "";
                $endereco_posto .= ($data->estado != "") ? ", ".$data->estado : "";

                if($login_fabrica == 74){
                  $cidade_posto_atlas = $data->cidade;
                }
            }

        }

        if (strlen($tipo_atendimento) > 0) {

            $sql  = "SELECT tipo_atendimento,km_google FROM tbl_tipo_atendimento WHERE tipo_atendimento = $tipo_atendimento";
            $resa = pg_query($con,$sql);

            if (pg_num_rows($resa) > 0) {
                $km_google = pg_fetch_result($resa, 0, 'km_google');
            }

        }?>



    <?php

    }

    if ($calculoKM == "t") {
?>

    <div id='div_mapa' style='width:840px;background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px; display: none;' >
        <b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br>
        Preencha todos os campos de endereço acima ou preencha o campo de distância</b>
        <br><br>
        <span id="ida_volta"></span> <br />
        <input type="hidden" id="ponto1" value="<?=$contato_endereco . ", " . $contato_numero . ", " . $contato_bairro?>" >
        <input type="hidden" id="distancia_km_maps"  value="" >
        <input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>
        Distância:

        <?
        if (in_array($login_fabrica,array(50,74,120,201)) ) {
          $readonlyy = "readonly='true'";
        }else{
          $readonlyy = "";
        }
        ?>

        <input type='text' <?php echo $readonlyy;?> name='distancia_km' id='distancia_km' value='<?=$qtde_km?>' size='10' style="background-color: #fff;"> KM

<?php
        if ($login_fabrica == 74) {
?>
            <input type='hidden' name='cidade_posto' value='<?=$cidade_posto_atlas?>' />
<?php
        }
        if ($login_fabrica == 50) {
?>
            <input type='hidden' name='posto_proximo' id='posto_proximo' value='<?=$posto_proximo?>' />
<?php
        }
?>
        <input  type="button" id='route' onclick="calcRoute();" value="Calcular Distância" size='5' >
        <img id="loading-map" src="imagens/grid/loading.gif" style="display: none; width: 22px; vertical-align: middle;" >
        <div id='div_mapa_msg' style='color:#FF0000'></div>
        <br>
        <div id='div_end_posto' style='color:#000000'>
            <B>Endereço do posto:</b>
            <u>
<?php
        if (strlen($endereco_posto) > 0) {
            if (empty($posto_proximo)) {
                echo $endereco_posto;
            } else {

                $sql_posto = "
                    SELECT  contato_endereco AS endereco,
                            contato_numero   AS numero  ,
                            contato_bairro   AS bairro  ,
                            contato_cidade   AS cidade  ,
                            contato_estado   AS estado  ,
                            contato_cep      AS cep
                    FROM    tbl_posto_fabrica
                    JOIN    tbl_posto USING(posto)
                    WHERE   codigo_posto    = '$posto_proximo'
                    AND     fabrica         = $login_fabrica ";

                $res_posto = pg_query($con, $sql_posto);

                while ($data = pg_fetch_object($res_posto)) {

                    $endereco_posto .= ($data->endereco != "") ? $data->endereco : "";
                    $endereco_posto .= ($data->numero != "") ? ", ".$data->numero : "";
                    $endereco_posto .= ($data->bairro != "") ? ", ".$data->bairro : "";
                    $endereco_posto .= ($data->cidade != "") ? ", ".$data->cidade : "";
                    $endereco_posto .= ($data->estado != "") ? ", ".$data->estado : "";

                }

                echo $endereco_posto;
            }
        }
?>
            </u>

        </div>
    </div>

    <?php } ?>

    <?php if($calculoKM == "t"){ ?>

    <div id="GoogleMapsContainer" style="display: none;">
        <div style="margin-top: 5px; margin-left: 5px; float: right; z-index: 9999999; position: relative; background-color: white;" id="fechamapa" onclick="fechaMapa();"><img src="./admin/imagens/close_black_opaque.png" /></div>
        <div id="GoogleMaps"></div>
        <!-- <div id="DirectionPanel"></div> -->
    </div>

    <?php } ?>

<?php

    //HD 234135
    if ($usa_revenda_fabrica) {
        echo '<div width="750" align="center" id="revenda_fabrica_msg"
               style="font:14px Arial;background-color:#7092BE;padding:5px;margin-top:10px;margin-bottom:10px;color:white">
                Digite o CNPJ da revenda com 14 dígitos e clique na lupa
            </div>';
    }
    if (!in_array($login_fabrica,array(15,24,96,122))) {?>
   <hr />
    <table width="750" align='center' border="0" cellspacing="5" cellpadding="0" class="multiCep">
        <tr valign='top'>
            <td>
                <font size="1" <?php if (!in_array($login_fabrica, array(30))) { echo 'color="red"'; } ?> face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_nome'><?=traduz("Nome Revenda")?></span></font>
                <br />
                <input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)"
                  <?
                  if($login_fabrica==50){?>
                    onChange="javascript: this.value=this.value.toUpperCase();"
                  <?
                  }?>
                  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;
                <?
                if(!$usa_revenda_fabrica){ //HD 234135 ?>
                <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_nome, "nome");' style='cursor: pointer'>
                <?
                }
                if ($login_fabrica == 50){?>
                <input type="hidden" name="atacadista" id="atacadista" value="<?=$_POST['atacadista']?>">
                <?php
                }?>
            </td>
            <td colspan="2">
                <font size="1" <?php if (in_array($login_fabrica, array(19))) { echo 'color="red"'; } ?> face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_cnpj'><?=traduz("CNPJ Revenda")?></span></font>
                <br />
<?php //HD 234135
        if ($usa_revenda_fabrica) {
?>
                <input type='hidden' name='revenda_cnpj' id='revenda_cnpj' value='<? echo $revenda_cnpj; ?>'>
                <input type='hidden' name='revenda_fabrica_status' id='revenda_fabrica_status' value='<? echo $revenda_fabrica_status; ?>'>
                        <input class="frm" type="text" name="revenda_cnpj_pesquisa" size="16" maxlength="14" id="revenda_cnpj_pesquisa" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;'); fnc_pesquisa_revenda_fabrica_onblur();" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.');">&nbsp;
                <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda_fabrica();' style='cursor: pointer'>
<?php
        }else {
?>
                <input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" id="revenda_cnpj" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); ">&nbsp;
                <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_cnpj, "cnpj");' style='cursor: pointer'>
<?php
        }
?>
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_fone'><?=traduz("Fone")?></span></font>
                <br />
<?php
        if ($login_fabrica == 50){
?>
                <input type="hidden" name="revenda_fone" value="<?=$revenda_fone?>">
<?php
        }
?>
                <input class="frm telefone" type="text" name="revenda_fone"  id="revenda_fone"  size="16" maxlength="16" rel='fone' value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14944556677.');">
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_cep'><?=traduz("Cep")?></span></font>
                <br />
                <input class="frm addressZip_rev" type="text" name="revenda_cep" id="revenda_cep"  size="10" maxlength="10" value="<? echo $revenda_cep ?>"
<?php
        if(!in_array($login_fabrica,array(74,91))){
?>
                    onblur="this.className='frm addressZip'; displayText('&nbsp;');"
<?php
        }elseif($login_fabrica == 74){
?>
                    onblur="this.className='frm addressZip'; displayText('&nbsp;'); buscaCepRevenda(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;"
<?php
        }
?>
                    onfocus="this.className='frm-on addressZip'; displayText('&nbsp;Digite o CEP da revenda.');"
                />
<?php
        if($login_fabrica == 91){
?>
                <input type='button' value='Pesquisar' class='frm' onclick="if(document.frm_os.revenda_cep.value.length < 8) {alert('Informe um CEP válido!');}else{buscaCEP(document.frm_os.revenda_cep.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;}" style='cursor: pointer' />
        <?php
        }
        ?>
            </td>
        </tr>
    <!-- </table>
    <table width="750" align='center' border="0" cellspacing="5" cellpadding="0"> -->
        <tr valign='top'>
            <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_endereco'><?=traduz("Endereço")?></span></font>
                <br />
                <input class="frm address_rev" type="text" name="revenda_endereco" id="revenda_endereco" size="30" maxlength="60" value="<? echo $revenda_endereco ?>"  <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm address_rev'; displayText('&nbsp;');" onfocus="this.className='frm-on address_rev'; displayText('&nbsp;Digite o endereço da Revenda.');">
            </td>

            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_numero'><?=traduz("Número")?></span></font>
                <br />
                <input class="frm" type="text" name="revenda_numero" id="revenda_numero"  size="5" maxlength="10" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');">
            </td>

            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_complemento'><?=traduz("Complemento")?></span></font>
                <br />
                <input class="frm" type="text" name="revenda_complemento" id="revenda_complemento" size="15" maxlength="30" value="<? echo $revenda_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');">
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_bairro'><?=traduz("Bairro")?></span></font>
                <br />
                <input class="frm addressDistrict_rev" type="text" name="revenda_bairro" id="revenda_bairro" size="13" maxlength="30" value="<? echo $revenda_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm addressDistrict'; displayText('&nbsp;');" onfocus="this.className='frm-on addressDistrict'; displayText('&nbsp;Digite o bairro da revenda.');">
            </td>
            <td>
                  <font size="1" <?php if (!in_array($login_fabrica, array(30))) { echo 'color="red"'; } ?> face="Geneva, Arial, Helvetica, san-serif">
                    <span rel='revenda_estado'><?=traduz("Estado")?></span>
                </font>
                <br />
                <?php
                if ($login_fabrica == 50){
                ?>
                        <input type="hidden" name="revenda_estado" value="<?=$revenda_estado?>">
                <?php
                }

                // Array para otimizar a criação de options de ESTADOS da REVENDA
                // Gabriel Silveira - 19/10/2012
                $estados_revenda_array = array("AC", "AL", "AM", "AP","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RO","RS","RR","SC","SE","SP","TO");

                /**
                 * Se ocorreu algum erro, e o estado e cep estiverem preenchidos, o array é redefinido para ter somente o estado que foi no POST
                 * POIS o posto não pode alterar a UF da revenda
                 * lembrando que o <select> estado é sempre alterado ou redefinido quando ocorre o blur no CEP
                 */
                if (!empty($msg_erro) and !empty($revenda_estado) and !empty($revenda_cep)) {
                        unset($estados_revenda_array);
                        $estados_revenda_array = array($revenda_estado);
                }
                ?>
                <select name="revenda_estado" id="revenda_estado" size="1" class="frm addressState_rev">
                    <option value=""   <? if (strlen($revenda_estado) == 0)    echo " selected "; ?>></option>
                <?php
                  $arrEstados = getListaDeEstadosDoPais($login_pais);

                  foreach ($arrEstados as $key => $dadosEstados) {

                    $selected = ($revenda_estado == $dadosEstados['sigla']) ? "selected" : "";

                    ?>
                      <option value="<?= $dadosEstados['sigla'] ?>" <?= $selected ?>><?= $dadosEstados['descricao'] ?></option>
                  <?php
                  } ?>
                </select>
            </td>
            <td nowrap>
                <font size="1" <?php if (!in_array($login_fabrica, array(30))) { echo 'color="red"'; } ?> face="Geneva, Arial, Helvetica, san-serif"><span rel='revenda_cidade'><?=traduz("Cidade")?></span></font>
                <br />
                <?php
                $rev_cidade_readonly = '';
                if ($login_fabrica == 30) {
                    $rev_cidade_readonly = 'readonly';
                }
                ?>
              <select id="revenda_cidade" name="revenda_cidade" class="frm addressCity_rev" style="width:100px">
                    <option value="" ><?=traduz("Selecione")?></option>
                    <?php
                        if (strlen($revenda_estado) > 0) {
                            if ($login_pais == "BR") { 
                            $sql = "SELECT DISTINCT * FROM (
                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$revenda_estado."')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$revenda_estado."')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC";
                          } else {
                            $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                                    FROM tbl_cidade 
                                    WHERE UPPER(estado_exterior) = UPPER('{$revenda_estado}')
                                    AND UPPER(pais) = UPPER('{$login_pais}')
                                    ";
                          }

                          $res = pg_query($con, $sql);

                          if (pg_num_rows($res) > 0) {
                              while ($result = pg_fetch_object($res)) {
                                  $selected  = (trim($result->cidade) == $revenda_cidade) ? "SELECTED" : "";

                                  echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                              }
                          }
                        }
                    ?>
                  </select>
            </td>
        </tr>
    </table>
<?
    }
    if (in_array($login_fabrica, array(15,24))) {
?>
    <table width="780" border="0" cellspacing="5" cellpadding="0">
        <tr valign='top'>
            <td>
                <span rel='revenda_nome' style="font:10px Arial">Nome Revenda</span>
                <br />
                <input type='hidden' name='revenda_cnpj' id='revenda_cnpj' value='<? echo $revenda_cnpj; ?>'>
                <input type="text" class="frm" name="revenda_nome" id="revenda_nome" size="50" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;
                <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevendaLatina (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>

            </td>
            <td>
                <span rel="revenda_cnpj" style="font:10px Arial"> CNPJ Raiz Revenda </span>
                <br>
                <input type="text" name="revenda_cnpj_raiz" class="frm" id="revenda_cnpj_raiz" value="<?=$revenda_cnpj_raiz?>" maxlength="8" >
                <img src="imagens/lupa.png" border="0" align="absmiddle" onclick="pesquisaRevendaLatina (document.frm_os.revenda_cnpj_raiz, 'cnpj' ) " style="cursor: pointer">
            </td>
        </tr>
            <tr>
                <td colspan="2">
                    <p>
                        <?php
                          $email_contato = ($login_fabrica == 15) ? "cadastro@latina.com.br" : "posvenda1@suggar.com.br";
                        ?>
                        <b> * Caso não encontre o cadastro da revenda, favor enviar um e-mail para <?=$email_contato?> com o CNPJ e Razão social da loja.</b>
                    </p>
                </td>
            </tr>
        </table>
      <?}

    if ($login_fabrica == 50) {
        if ($_POST['atacadista']=='t' and !empty($msg_erro)){
            $display_fixo = '';
        }else{
            $display_fixo = 'display:none;';
        }
        ?>
        <br>
        <div id='revenda_fixo' style='<?=$display_fixo?> background:#efefef; border:#999999 1px solid;'>
            <table width="800" align='center' border="0" cellspacing="5" cellpadding="0">
                <tr>
                    <td colspan="4" style='color:#E23A3A;font:bold 16px Arial'>FAVOR INFORMAR OS DADOS DA REVENDA QUE ESTÁ NA NOTA FISCAL</td>
                </tr>
                <tr valign='top'>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
                        <br />
                        <input class="frm" type="text" name="txt_revenda_nome" id="txt_revenda_nome" size="50" maxlength="50" value="<?=$txt_revenda_nome?>" onkeyup="somenteMaiusculaSemAcento(this)">
                        <img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: pesquisaRevenda (document.frm_os.txt_revenda_nome, &quot;nome&quot;,&quot;revenda_nf&quot;);" style="cursor: pointer">
                    </td>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
                        <br />
                        <input class="frm" type="text" name="txt_revenda_cnpj" id="txt_revenda_cnpj" size="20" maxlength="18" id="txt_revenda_cnpj" value="<?=$txt_revenda_cnpj?>">
                        <img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: pesquisaRevenda (document.frm_os.revenda_cnpj, &quot;cnpj&quot;,&quot;revenda_nf&quot;);" style="cursor: pointer">
                    </td>
                    <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
                    <br />
                    <input class="frm" type="text" name="txt_revenda_fone" id="txt_revenda_fone" size="15" maxlength="15"  rel='fone' value="<?=$txt_revenda_fone?>">
                    </td>
                    <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
                    <br />
                    <input class="frm" type="text" name="txt_revenda_cep" id="txt_revenda_cep"  size="10" maxlength="10" value="<?=$txt_revenda_cep?>" onblur="buscaCEP(this.value, document.frm_os.txt_revenda_endereco, document.frm_os.txt_revenda_bairro, document.frm_os.txt_revenda_cidade, document.frm_os.txt_revenda_estado) ;" >
                    </td>
                </tr>
            </table>
            <table width="800" align='center' border="0" cellspacing="5" cellpadding="0">
                <tr valign='top'>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço</font>
                    <br />
                        <input class="frm" type="text" name="txt_revenda_endereco" id="txt_revenda_endereco" size="30" maxlength="50" value="<?=$txt_revenda_endereco?>">
                    </td>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font>
                    <br />
                        <input class="frm" type="text" name="txt_revenda_numero" id="txt_revenda_numero" size="5" maxlength="5" value="<?=$txt_revenda_numero?>">
                    </td>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
                    <br />
                        <input class="frm" type="text" name="txt_revenda_complemento" id="txt_revenda_complemento" size="10" maxlength="10" value="<?=$txt_revenda_complemento?>">
                    </td>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
                    <br />
                        <input class="frm" type="text" name="txt_revenda_bairro" id="txt_revenda_bairro" size="10" maxlength="20" value="<?=$txt_revenda_bairro?>">
                    </td>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font>
                    <br />
                        <input class="frm" type="text" name="txt_revenda_cidade" id="txt_revenda_cidade" size="12" maxlength="10" value="<?=$txt_revenda_cidade?>">
                    </td>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font>
                    <br />
                        <input class="frm" type="text" name="txt_revenda_estado" id="txt_revenda_estado" size="2" maxlength="2" value="<?=$txt_revenda_estado?>">
                    </td>
                </tr>
            </table>
            <!--
            <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
                <tr valign='top'>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. EAN</font>
                    <br />
                        <input class="frm" type="text" name="txt_cod_ean" id="txt_cod_ean" size="30" maxlength="50" value="">
                    </td>
                    <td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Faturamento</font>
                    <br />
                        <input class="frm" type="text" name="txt_data_venda" id="txt_data_venda" size="12" maxlength="10" value="">
                    </td>
                    <td colspan = '3'>&nbsp; <br />&nbsp;
                    </td>
                </tr>
                </table>
                <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
                <tr valign='top'>
                    <td><font size="2" face="Geneva, Arial, Helvetica, san-serif" color='red'>AS INFORMAÇÕES AUTOMÁTICAS QUE ESTÃO ACIMA SÃO AS MESMAS DA NOTA FISCAL DO CONSUMIDOR?</font>
                    </td>
                    <td>
                        <input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('sim');" value="sim"> Sim
                    </td>
                    <td>
                        <input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('nao');" value="nao"> Não
                    </td>

                </tr>
            </table>
            -->
        </div>

    <?php
    }
    ?>
                        <!--
                        <input type='hidden' name = 'revenda_fone'>
                        <input type='hidden' name = 'revenda_cep'>
                        <input type='hidden' name = 'revenda_endereco'>
                        <input type='hidden' name = 'revenda_numero'>
                        <input type='hidden' name = 'revenda_complemento'>
                        <input type='hidden' name = 'revenda_bairro'>
                        <input type='hidden' name = 'revenda_cidade'>
                        <input type='hidden' name = 'revenda_estado'>
                        <input type='hidden' name = 'revenda_email'>
                        -->
        <?
        if ($login_fabrica == 7) {
#            echo " -->";
        }
        ?>

        <?if ($login_fabrica <> 96) { ?>
        <hr>
        <?}?>
        <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
        <tr>
            <?
    if($login_fabrica == 24){
               echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                echo "Consumidor</font>&nbsp;";
		echo "<input type='hidden' name='consumidor_revenda_hidden' value='' id='consumidor_revenda_hidden'>";
                echo "<input type='radio' name='consumidor_revenda' id='consumidor_revenda_suggar_cpf' value='C'>";
                echo "</td>";

                    echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>ou</font></td>";
                    echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz('Revenda')."</font>&nbsp;";
                    echo "<input type='radio' name='consumidor_revenda' id='consumidor_revenda_suggar_cnpj' value='R' >";
                    echo '</td>';
    } else if (!in_array($login_fabrica,array(19,30,35,45,86,91,122))) { // HD 717347
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                echo "Consumidor</font>&nbsp;";
                echo "<input type='radio' name='consumidor_revenda' value='C'";
                if ($consumidor_revenda == 'C' or in_array($login_fabrica, array(3,24,40,46,72,74) ) or $login_fabrica > 80)
                    echo "checked";
                echo ">";

                if($login_fabrica != 30){
                    echo "</td>";

                    echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>ou</font></td>";
                    echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>".traduz('Revenda')."</font>&nbsp;";
                    echo "<input type='radio' name='consumidor_revenda' value='R' ";
                    //MALLORY não quer que cadastre OS de revenda por está tela
                    if ($login_fabrica == 72)//HD 249034
                        echo ' disabled="disabled" ';
                    if ($consumidor_revenda == 'R')
                        echo ' checked="checked"';
                    echo '>&nbsp;&nbsp;';
                    echo '</td>';
                }
            } else {

                if (!in_array($login_fabrica, [30, 91])) {
                    echo "<input type='hidden' name='consumidor_revenda' value='C'>";
                }

                if ($login_fabrica == 30) {

                    if ($consumidor_revenda <> 'R') {
                        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                        echo "Consumidor</font>&nbsp;";
                        echo "<input type='radio' name='consumidor_revenda' value='C' checked";
                    } else {
                        echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                        echo "Revenda</font>&nbsp;";
                        echo "<input type='radio' name='consumidor_revenda' value='R' checked";
                    }
                }
            }?>
            <td><?php
                if ( in_array($login_fabrica, array(11,172))) {
                    //NAO IMPRIME NADA
                    echo "<td width='440px'>&nbsp;";
                } else {
                    echo "<td>";
                    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
                    echo "<span rel='aparencia_produto'>".traduz('Aparência do Produto')."</span>";
                    echo "</font>";
                }?>
                <br />
<?
                if ($fabrica_aparencia_produto_select) {
                    if ($login_fabrica == 20) {

                    $a_aparencia = array(
                        'pt-br' => array(
                            'NEW' => 'Bom estado',
                            'USL' => 'Uso intenso',
                            'USN' => 'Uso Normal',
                            'USH' => 'Uso Pesado',
                            'ABU' => 'Uso Abusivo',
                            'ORI' => 'Original, sem uso',
                            'PCK' => 'Embalagem'
                        ),
                        'es'    => array(
                            'NEW' => 'Buena aparencia',
                            'USL' => 'Uso continuo',
                            'USN' => 'Uso Normal',
                            'USH' => 'Uso Pesado',
                            'ABU' => 'Uso Abusivo',
                            'ORI' => 'Original, sin uso',
                            'PCK' => 'Embalaje'
                        ),
                        'en-US' => array(
                            'NEW' => 'New',
                            'USL' => 'Intense Use',
                            'USN' => 'Normal Use',
                            'USH' => 'Heavy Use',
                            'ABU' => 'Abusive Use',
                            'ORI' => 'Original, no use',
                            'PCK' => 'Packed'
                        )
                    );
                }

                /*if ($login_fabrica == 114) {
                    $a_aparencia = array('pt-br' => explode(',', 'NOVA SEM USO,USO NORMAL,USO INADEQUADO'));
                }*/

                echo array2select('aparencia_produto', 'aparencia_produto', $a_aparencia[$cook_idioma], $aparencia_produto, ' class="frm"', 'ESCOLHA', $login_fabrica==20);

                } else {
                    if ( in_array($login_fabrica, array(11,172)) ) {
                        echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
                    } else if ($login_fabrica == 50) {
                        echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onChange=\"javascript: this.value=this.value.toUpperCase();\" onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
                    } else {
                        echo "<input class='frm' type='text' id='aparencia_produto' name='aparencia_produto' size='30' value='$aparencia_produto' if($login_fabrica==50){onChange=\"javascript: this.value=this.value.toUpperCase();\"} onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
                    }
                }?>
            </td><?php
            if ($login_fabrica <> 1) {
                if ( in_array($login_fabrica, array(11,172)) ) {
                    //nao mostra acessórios
                } else {?>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='acessorios'><?=traduz("Acessórios")?></span></font>
                        <br />
                        <input class="frm" type="text" name="acessorios" id="acessorios" size="30" value="<? echo $acessorios ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
                    </td><?php
                }
            }
            if ($login_fabrica == 1) {//OR $login_fabrica == 3
                //conforme e-mail de Samuel (sirlei) a partir de 21/08 nao tem troca de produto para britania, somente ressarcimento financeiro?>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br />
                    <input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
                </td><?php
            }

            if (in_array($login_fabrica, array(141,144)) && $posto_interno) {
              $checked = ($os_remanufatura == "t") ? "checked" : "";

              echo "
                <td>
                  <input type='checkbox' name='os_remanufatura' value='t' {$checked} /> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Remanufatura</font>
                </td>
              ";
            }

            ?>
        </tr>

    </table>

    <?
        if (in_array($login_fabrica, [3,30,43,141])) {
            ?>
            <table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
            <tr>

                <td align='center' >
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
                    <br />
                    <?php if($login_fabrica == 3){?>
                    <input class="frm" type="text" name="obs" id="obs" size="50" value="<? echo $defeito_reclamado_descricao2;?>">
                    <?php }else{?>
                    <input class="frm" type="text" name="obs" id="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
                    <?php } ?>
                </td>
            </tr>
            </table>
            <?
        }
        if ($login_fabrica == 7 ) {  ?>
        <hr>
        <table width="750" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td id="table_abriu_chamado" >
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado aberto por</font>
                <br />
                <?php
                if($login_fabrica == 7){
                    $readonly = "readonly";
                }else{
                    $readonly = "";
                }

                ?>
                <input <?php echo $readonly ?> class="frm" type="text" id="quem_abriu_chamado" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
                <br />
                <input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
            </td>
        </tr>
        </table>

        <?PHP
        $sql = "SELECT tipo_posto
                FROM tbl_posto_fabrica
                WHERE fabrica = $login_fabrica AND posto = $login_posto";
        $res = pg_query($con,$sql);
        $tipo_posto = pg_fetch_result($res,0,tipo_posto);
        if ($tipo_posto == 214 OR $tipo_posto == 215 OR $tipo_posto == 7) {

            if ($tipo_posto != 214 AND $tipo_posto != 215){
                $valores_somente_leitura = 't';
            }
        ?>
        <table width="750" border="0" cellspacing="5" cellpadding="0">
        <tr>
            <td><font size="2" face="Geneva, Arial, Helvetica, san-serif">
                    Valores Combinados na Abertura da OS
                </font>
            </td>
        </tr>
        <tr style='font-size:10px' valign='top'>
            <td valign='top'>
                <fieldset class='valores' style='height:140px;'>
                <legend>Deslocamento</legend>
                    <div>
                    <?    /*HD: 55895*/
                    if ($login_fabrica <> 7) {?>
                        <label for="cobrar_deslocamento">Isento:</label>
                        <input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
                        <br />
                    <?}?>
                    <label for="cobrar_deslocamento">Por Km:</label>
                    <input type='radio' name='cobrar_deslocamento' value='valor_por_km' <? if ($cobrar_deslocamento == 'valor_por_km') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
                    <br />
                    <label for="cobrar_deslocamento">Taxa de Visita:</label>
                    <input type='radio' name='cobrar_deslocamento' value='taxa_visita' <? if ($cobrar_deslocamento == 'taxa_visita') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
                    <br />
                    </div>

                    <div name='div_taxa_visita' <? if ($cobrar_deslocamento != 'taxa_visita') echo " style='display:none' "?>>
                        <label for="taxa_visita">Valor:</label>
                        <input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                    </div>

                    <div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
                        <label for="veiculo">Carro:</label>
                        <input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
                        <input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                        <label for="veiculo">Caminhão:</label>
                        <input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
                        <input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>'>
                    </div>
                </fieldset>
            </td>
            <td>
                <fieldset class='valores' style='height:140px;'>
                    <legend>Mão de Obra</legend>
                    <div>
                    <label for="cobrar_hora_diaria">Diária:</label>
                    <input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'diaria') echo "checked";?>>
                    <br />
                    <label for="cobrar_hora_diaria">Hora Técnica:</label>
                    <input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'hora') echo "checked";?>>
                    <br />
                    </div>
                    <div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
                        <label>Valor:</label>
                        <input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                    </div>
                    <div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
                        <label>Valor:</label>
                        <input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                    </div>
                </fieldset>
            </td>
            <td>
                <fieldset class='valores' style='height:140px;'>
                    <legend>Outros Serviços</legend>
                    <div>
                        <label>Regulagem:</label>
                        <input type="checkbox" name="cobrar_regulagem" value="t" <? if ($cobrar_regulagem=='t') echo "checked" ?>>
                        <br />
                        <label>Valor:</label>
                        <input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                        <br />
                        <label>Certificado:</label>
                        <input type="checkbox" name="cobrar_certificado" value="t" <? if ($cobrar_certificado=='t') echo "checked" ?>>
                        <br />
                        <label>Valor:</label>
                        <input type="text" name="certificado_conformidade" value="<? echo number_format($certificado_conformidade,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                        <br />
                        </div>
                </fieldset>
            </td>
        </tr>
        <tr  style='font-size:10px'>
            <td class="menu_top" colspan='3'>

                <table border="0" cellspacing="10" cellpadding="0">
                <tr style='font-size:10px'>
                    <td class="table_line2">% Desconto Peças</td>
                    <td class="table_line2" >Condição de Pagamento</td>
                </tr>
                <tr style='font-size:10px'>
                    <td class="table_line2">
                        <input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='15' maxlength='5' <? if($valores_somente_leitura == 't') {echo " READONLY='readonly'";} ?>>
                    </td>
                    <td class="table_line2" >
                        <select name='condicao' class='frm'>
                            <option value=''></option>
                        <?
                        $sql = " SELECT condicao,
                                        codigo_condicao,
                                        descricao
                                FROM tbl_condicao
                                WHERE fabrica = $login_fabrica
                                    AND visivel is true";
                        $res = pg_query ($con,$sql) ;



                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
                            list($cond_cond, $cond_codigo, $cond_desc) = pg_fetch_row($res, $i);
                            $sel = ($cond_cond == $condicao) ? ' selected' : '';
                            echo "<option value='$cond_cond'$sel>$cond_codigo - $cond_desc</option>\n";
                        }   ?>
                        </select>&nbsp;
                    </td>
                </tr>
                </table>

            </td>
        </tr>
        </table>

        <?
            }
        }
        ?>

    </td>

    <td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<?
if (strlen($os)==0 and
    strlen($msg_erro)==0 and
    ($linhainf == 't' or $fabrica_com_preOS)) { //89, ?>
<script  type="text/javascript">
    $(document).ready(function(){

      $('#tipo_atendimento').trigger('change');

      if ($('#distancia_km').val() == 'NULL') {
        $('#distancia_km').html(''); 
      }
        //Verifica Endereço Posto
        <?php if( in_array($login_fabrica, array(3, 15)) ){ ?> verifica_posto = testaEndOrigem(''); <?php } ?>
    });
</script>
<?}?>

<script type="text/javascript">
    $(document).ready(function(){

        <?php if($calculoKM == "t"){ ?>

        var numeroConsumidor = "";
        if($('#consumidor_numero').val() != "") { numeroConsumidor = $('#consumidor_numero').val(); }

        <?php
        if($login_fabrica == 30){
        ?>
          $('#consumidor_estado').blur(function(){

              var numeroConsumidor2 = "";
              numeroConsumidor2 = $('#consumidor_numero').val();


              if($('#consumidor_cep').val() != "" && $('#distancia_km').val() != 0){

                  if(numeroConsumidor != numeroConsumidor2){
                      $('#distancia_km').val('');
                      $('#div_end_posto').html('');
                      $('#div_mapa_msg').html('');
                      setTimeout(function(){
                          calcRoute();
                      }, 500);
                  }

              }

              if(numeroConsumidor2.length == 0){
                  $('#distancia_km').val('');
                  $('#div_end_posto').html('');
                  $('#div_mapa_msg').html('');
              }

              if(numeroConsumidor != numeroConsumidor2){
                  $('#distancia_km').val('');
                  $('#div_end_posto').html('');
                  $('#div_mapa_msg').html('');
                  setTimeout(function(){
                      calcRoute();
                  }, 500);
              }


          });
        <?php
        }
        ?>
        $('#consumidor_cep, #consumidor_endereco, #consumidor_numero, #consumidor_bairro, #consumidor_complemento, #consumidor_cidade, #consumidor_estado').change(function(){
                $('#distancia_km').val('');
                $('#ida_volta').html('');
                $('#div_end_posto').html('');
                $('#div_mapa_msg').html('');
                $('#distancia_km_conferencia').val('');

        });
        $('#consumidor_numero').blur(function(){

            var numeroConsumidor2 = "";
            numeroConsumidor2 = $('#consumidor_numero').val();

            if(numeroConsumidor2 != "") {
                if($('#consumidor_cep').val() != "" && $('#distancia_km').val() != 0){

                    if(numeroConsumidor != numeroConsumidor2){
                        $('#distancia_km').val('');
                        $('#div_end_posto').html('');
                        $('#div_mapa_msg').html('');
                        setTimeout(function(){
                            calcRoute();
                        }, 500);
                    }

                }

                if(numeroConsumidor2.length == 0){
                    $('#distancia_km').val('');
                    $('#div_end_posto').html('');
                    $('#div_mapa_msg').html('');
                    setTimeout(function(){
                        calcRoute();
                    }, 500);
                }

                if(numeroConsumidor != numeroConsumidor2){
                    $('#distancia_km').val('');
                    $('#div_end_posto').html('');
                    $('#div_mapa_msg').html('');
                    setTimeout(function(){
                        calcRoute();
                    }, 500);
                }
            }
        });

        <?php } ?>

        $('input.hidden_consumidor_nome').change(function(){
            $('#distancia_km').val('');
            $('#div_end_posto').html('');
            $('#div_mapa_msg').html('');

            setTimeout(function(){
                calcRoute();
            }, 1000);

        });

        $(function(){
            <?php
                if(strlen($msg_erro) > 0 && (strlen($qtde_km) > 0 and strtoupper($qtde_km) !='NULL' AND $km_google == 't') ){
                    ?>
                        $('#div_mapa').show();
                        $('#distancia_km').val('<?=$qtd_km?>');
                    <?php
                }
            ?>
        });

    });

    function mostra_def (){//HD-3331834
      var produto_referencia = $("#produto_referencia").val();
      $.ajax({
          type: "GET",
          url: "os_cadastro_tudo.php",
          data: {"produto_referencia":produto_referencia, "monta_defeitos": 'sim'},
          cache: false,
          success: function(data){
            data = JSON.parse(data);
            console.log(data);
            if (data.messageError == 'error') {
              alert("Não foi encontrado Defeito Reclamado");
            }else{
              $("#defeito_reclamado_descricao").find('option').remove();
              $("#defeito_reclamado_descricao").html(data);
            }
          }
      });
    }
</script>

<hr width='780'>
<table width="100%" align='center' border="0" cellspacing="5" cellpadding="0" >
<?
	if( in_array($login_fabrica, array(11,172)) ){
		if(!empty($hd_chamado)) {
			$aux_sql = "
			  SELECT defeito_reclamado_descricao
			  FROM tbl_hd_chamado_extra
			  WHERE hd_chamado = $hd_chamado
			";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
			  $aux_reclamado = pg_fetch_result($aux_res, 0, 0); ?>
			  <tr>
				<td  align="center" width="100px" >
				  <font size="1" face="Geneva, Arial, Helvetica, san-serif"> Reclamação do Cliente </font>
				  <br>
				  <textarea class="frm" name="reclamacao_cliente" id="reclamacao_cliente" cols="80" rows="5"><?=$aux_reclamado;?></textarea>
				</td>
			  </tr>
			<?php }
	  } ?>
    <tr>
        <td  align="center" width="100px" >
        <label style="position:relative;top:-3px;color:red;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> Inserir Nota Fiscal: </label>
          <input type="file" class="frm" name="foto_nf[]" id="foto_nf"/>
        </td>
    </tr>
  <?
  }
    if (in_array($login_fabrica,array( 42, 126, 137))) {
        $label = ($login_fabrica == 42)
            ? "NF de Compra"
            : "Inserir Nota Fiscal";
?>

      <tr>
          <td  align="center" width="100px" >
          <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> <?=$label?>: </label>
            <input type="file" class="frm" name="img_os_1" id="img_os_1"/>
          </td>
      </tr>
<?php
        if($login_fabrica == 42){
?>

      <tr class="anexo_cortesia">
          <td  align="center" width="100px" >
          <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> Foto  </label>
            <input type="file" class="frm" name="img_os_2" id="img_os_2"/>
          </td>
      </tr>
      <tr class="anexo_cortesia">
          <td  align="center" width="100px" >
          <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> Foto  </label>
            <input type="file" class="frm" name="img_os_3" id="img_os_3"/>
          </td>
      </tr>
      <tr class="anexo_cortesia">
          <td  align="center" width="100px" >
          <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> Foto </label>
            <input type="file" class="frm" name="img_os_4" id="img_os_4"/>
          </td>
      </tr>
      <tr class="anexo_cortesia">
          <td  align="center" width="100px" >
          <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif; margin-left:-176px;"> Foto </label>
            <input type="file" class="frm" name="img_os_5" id="img_os_5"/>
          </td>
      </tr>

<?php
        }
?>
    </table>
<?
    }
?>

<?php

if($login_fabrica == 114){
  ?>
  <style>
    .box-upload{
      width: 800px;
      margin: 0 auto;
      padding-top: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #999;
      margin-bottom: 20px;
    }
  </style>
  <div class="box-upload" <?php echo ($selo_obrigatorio == "t") ? "style='display: block;'" : "style='display: none;'"; ?>>
    <input type="hidden" name="selo_obrigatorio" id="selo_obrigatorio" value="<?php echo (strlen($selo_obrigatorio) == 0) ? "f" : ($selo_obrigatorio == 'f') ? "f" : "t"; ?>">
    Upload da Imagem de Selo: &nbsp;
    <input type="file" name="upload_selo" id="upload_selo" />
  </div>
  <?php
}

?>

<table width="100%" border="0" cellspacing="5" cellpadding="0">

<?php

  $arquivo_regras = "os_cadastro_unico/fabricas/" . $login_fabrica . "/regras.php";

  if (file_exists($arquivo_regras)) {

    include $arquivo_regras;
  }

 /**
  * @author William Castro <william.castro@telecontrol.com.br>
  * hd-6639553 -> Box Uploader
  *
  */
if ($fabricaFileUploadOS) {

  $boxUploader = array(
      "div_id" => "div_anexos",
      "prepend" => $anexo_prepend,
      "context" => "os",
      "unique_id" => $tempUniqueId,
      "hash_temp" => $anexoNoHash,
      "reference_id" => $os
  );

  include "box_uploader.php";
}

if ($fabricas_image_uploader) {

  ?>
  <div id="env-qrcode" style="display:none">
    <div class='env-code'>
      <img style="width: 200px;" src="">
    </div>
  </div>
  <!-- <img id="btn-qrcode-request" src="imagens/btn_imageuploader.gif" onclick="getQrCode()" alt="Fazer Upload via Image Uploader" border="0" style="cursor: pointer;border: 1px solid #888;">-->
  <div style="width:920px;text-align:center">
    <span class="mobile" id="btn-qrcode-request" onclick="getQrCode()">
    <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_mobile.png">
    <span><?=traduz("Anexar via Mobile")?></span>
    </span>
    <span class="google_play" id="btn-google-play">
      <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">
        <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_google_play.png">
        <span style="margin-top: 17px;float: left;font-size: 12px; color: #373865;"><?=traduz("Baixar Aplicativo Image Uploader")?></span>
      </a>
    </span>
  </div>
  <div id="env-images"></div>
<?php
  #color: #373865
  echo $include_imgZoom;
}

?>

<tr>
    <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
        <input type="hidden" name="btn_acao" value="">
    <?php if (isFabrica(19, 74)) { ?>
        <input type='hidden' name='linha_id' id='linha_id' value='<?=$linha_id?>'>
    <?php } ?>
        <input type="hidden" name="qtde_etiquetas" value="">
        <?
//  MLG - 19/11/2009 - HD 171045 - Para inserir imagem da NF da NKS...
//  MLG - 19/11/2009 - HD 171045 - Para inserir imagem da NF da NKS...
//  MLG - 26/10/2010 - Mudei o sistema:
//  MLG - 06/12/2010 - HD 321132 - O anexo de imagens à OS está 'unificado' em um include que serve
//                                 para todas as telas, admin e posto.
        if(!in_array($login_fabrica,array(11,42,137,172))) {
            if ($anexaNotaFiscal && (!$fabricaFileUploadOS)){
              if ($login_fabrica == 15) {
                echo "<!-- <b style='color: #f00;".$ob_nf."' id='tipo_atendimento_obg'>*</b>&nbsp;&nbsp; --> ".$inputNotaFiscal;
              } else {

                if ($login_fabrica == 101) {

                  echo "<br>";
                  echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>".$inputNotaFiscal;

                  echo "<br>";
                  echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>".$inputNotaFiscal;

                  echo "<br>";
                  echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>".$inputNotaFiscal;

                  echo "<br>";
                  echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>".$inputNotaFiscal;

                  echo "<br>";
                  echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>".$inputNotaFiscal;
                  echo "<br>";

                }else{
                  echo "<br>";
                  echo "<b style='color: #f00; display: none;' id='tipo_atendimento_obg'>*</b>".$inputNotaFiscal;
                  echo "<br>";
                }
              }
            }

        }

        if ($login_fabrica != 1) {
        echo " <br /> <input type='checkbox' name='imprimir_os' id='imprimir_os' value='imprimir' ";
        if( $imprimir_os) { // HD  56871
            echo " CHECKED ";
            echo " onClick='javascript: alert(\"É obrigatório a impressão de OS\"); return false;'";
        }
        echo "> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
        }
        ?>
        <? if ($login_fabrica == 1) { ?>
        <img src='imagens/btn_continuar.gif' onclick="javascript:

        if (document.frm_os.btn_acao.value == '' ) {
            document.frm_os.btn_acao.value='continuar' ;
            document.frm_os.submit();
        } else {
            alert ('Não clique no botão voltar do navegador, utilize somente os botões da tela')
        }"

        ALT="Continuar com Ordem de Serviço" border='0' style='cursor: hand;'>
        <? }elseif($login_fabrica==51){ ?>
            <img src='imagens/btn_continuar.gif' onclick="javascript:valida_consumidor_gama();" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
    <?}else { //botão continuar fricon
        if (in_array($login_fabrica, array(24))) {
          ?>
          <img src='imagens/btn_continuar.gif' onclick="
              <?if ($fabricas_validam_campos_telecontrol) {?>
                  func_submit_os();"
              <?}else{?>
                  javascript: if (document.frm_os.btn_acao.value == '' ) {
                                  if (fn_valida_consumidor_cpf(document.frm_os.consumidor_cpf.value, document.frm_os.consumidor_nome.value) == true) {
                                      document.frm_os.btn_acao.value='continuar' ;
                                      document.frm_os.submit();
                                  } else {
                                      document.frm_os.btn_acao.value='continuar' ;
                                      document.frm_os.submit();
                                  }
                              }"
                name="sem_submit" class="verifica_servidor"
              <?}?>
              ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
         <?
        } else {
          ?>
          <img src='imagens/btn_continuar.gif' onclick="
              <?if ($fabricas_validam_campos_telecontrol) {?>
                  func_submit_os();"
              <?} else if ($login_fabrica == 19) { ?>
                valida_garantias_adicionais();"
              <?php
              } else {?>

                  javascript: if (document.frm_os.btn_acao.value == '' ) {
                                  document.frm_os.btn_acao.value='continuar' ;
                                  <? if ( in_array($login_fabrica, array(11,172)) ) {?>
                                  if (document.frm_os.imprimir_os.checked == true){
                                      var qtde_aux = prompt('Quantas etiquetas deseja imprimir? Ou tecle ENTER para imprimir a quantidade padrão','');
                                      if ( (qtde_aux=='') || (qtde_aux==null) || qtde_aux.length==0 ){
                                          document.frm_os.qtde_etiquetas.value = '';
                                      }else{
                                          document.frm_os.qtde_etiquetas.value = qtde_aux;
                                      }
                                  }
                                  <?}?>
                                  <?php if ($login_fabrica == 74) { ?>
                                      submitComboEstado();
                                  <?php } ?>

                                    document.frm_os.submit();
                              }"
                name="sem_submit" class="verifica_servidor"
              <? } ?>
              ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
         <?
        }
      }?>
    </td>
</tr>
</table>

<?php

  if($_POST['objectid'] == ""){
      $objectId = $login_fabrica.$login_posto.date('dmyhis').rand(1,10000);
  }else{
      $objectId = $_POST['objectid'];
  }

  ?>
  <input type="hidden" id="objectid"  name="objectid" value="<?php echo $objectId; ?>">

</form>
<? if (in_array($login_fabrica, [24, 42,120]) && !empty($msg_erro)) {
    echo "<script>verifica_atendimento()</script>";
}
    if($login_fabrica == 3 && !empty($msg_erro) AND !empty($produto_serie)){
        echo "<script>verificaReincidente();</script>";
    }
?>
<script language='javascript' src='admin/address_components.js'></script>
<script>
  $(function() {
    window.setTimeout(function(){
      $("#consumidor_estado").blur();
    },10000);
    verifyObjectId($("#objectid").val());
  });

  setIntervalRunning = false;
  setIntervalHandler = null;

  <?php if (isFabrica(19)): ?>
  tipo_atendimento_produto('<?=$linha?>');
  <?php endif; ?>

  function getQrCode(){
    $("#btn-qrcode-request").fadeOut(1000);
    $("#btn-google-play").fadeOut(1000);
   $.ajax("controllers/QrCode.php",{
      method: "POST",
      data: {
        "ajax": "requireQrCode",
        "options": [
          "notafiscal"
        ],
        "title": "Upload de Nota Fiscal",
        "objectId": $("#objectid").val()
      }
   }).done(function(response){

      response = JSON.parse(response);
      console.log(response);

      $("#env-qrcode").find("img").attr("src",response.qrcode)
      $("#env-qrcode").fadeIn(1000);

      if(setIntervalRunning==false){
        setIntervalHandler = setInterval(function(){
          console.log("buscando...");


          verifyObjectId($("#objectid").val());
        },5000);
      }
   });
  }

  function verifyObjectId(objectId){

    $.ajax("controllers/TDocs.php",{
            method: "POST",
            data:{
              "ajax": "verifyObjectId",
              "objectId": objectId,
              "context": "os"
            }
          }).done(function(response){
            response = JSON.parse(response);

            if(response.exception == undefined){
              $(response).each(function(idx,elem){

                if($("#"+elem.tdocs_id).length == 0){
                  //var img = $("<div class='env-img'><img id='"+elem.tdocs_id+"' style='width: 150px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //##var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //$(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id);

                  var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button class='btn-danger' data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");

                  $(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg");
                  $(img).find("button").click(function(){
                      $.ajax("controllers/TDocs.php",{
                        method: "POST",
                        data: {
                          "ajax": "removeImage",
                          "objectId": elem.tdocs_id,
                          "context": "os"
                        }
                      }).done(function(response){
                          response = JSON.parse(response);
                          console.log(response);
                          if(response.res == 'ok'){
                            $("#"+elem.tdocs_id).parents(".env-img").fadeOut(1000);
                          }else{
                            alert("Não foi possível excluir o anexo, por favor tente novamente");
                          }
                      });
                  });

                  $("#env-images").append(img);
                  setupZoom();
                  console.log(elem.tdocs_id);
                }
              });
            }
          });
  }

</script>
<?php
if($login_fabrica == 24){
    ?>
<script type="text/javascript">

$(function(){
    $("input[name=cpf_cnpj_revenda_suggar]").click(function() {
        alterarMaskSuggar();
    });
});

function alterarMaskSuggar() {

    var consumidor_revenda_suggar = $("input[name=cpf_cnpj_revenda_suggar]:checked").val();

    document.getElementById("consumidor_revenda_suggar_cnpj").disabled = true;
    document.getElementById("consumidor_revenda_suggar_cpf").disabled = true;

    if (consumidor_revenda_suggar == "CPF") {
        $("#consumidor_cpf").mask("999.999.999-99");
        document.getElementById("consumidor_revenda_suggar_cnpj").checked = false;
        document.getElementById("consumidor_revenda_suggar_cpf").checked = true;
	$("#consumidor_revenda_hidden").val('C');
    } else {
        $("#consumidor_cpf").mask("99.999.999/9999-99");
        document.getElementById("consumidor_revenda_suggar_cpf").checked = false;
        document.getElementById("consumidor_revenda_suggar_cnpj").checked = true;
	$("#consumidor_revenda_hidden").val('R');
    }

}

$(document).ready(function() {
    alterarMaskSuggar();
});

</script>
<?php } ?>

<?php if($login_fabrica == 123) : ?>
    <script type="text/javascript">
        var inputNumSerie = $('#produto_serie');
        inputNumSerie.blur(function(){
            inputNumSerie.css("background-color", "#FFF");
            verificaLimiteNumSerie(function(msgErro){
                if(msgErro){
                    alert("Número de série inválido.\n\n" + msgErro);
                    inputNumSerie.css("background-color", "#FCC");
                }
            });   
        });
    </script>

<?php endif; ?>

<? include "rodape.php";?>
