<?php
//conforme chamado 474 (fabricio -  britania) na hr em que eram buscada as informacoes da OS, estava buscando na forma antiga, ou seja, estava buscando informacoes do cliente na tbl_cliente, com o novo metodo as info do consumidor sao gravados direto na tbl_os, com isso hr que estava buscando info do cliente estava buscando no local errado -  Takashi 31/09/2006

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

use model\ModelHolder;
use util\ArrayHelper;
use html\HtmlHelper;
$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';
include_once '../class/communicator.class.php'; //HD-3191657
$programa_insert = $_SERVER['PHP_SELF'];

include_once "plugins/fileuploader/TdocsMirror.php";

$TdocsMirror = new TdocsMirror();

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

$array_pais_estado = $array_pais_estado();

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

if($login_fabrica == 88){
    $limite_anexos_nf = 5;
}

if(isset($_POST['verifica_produto_serie']) == true){
    $numero_serie = $_POST["serie"];

    if(strlen(trim($numero_serie))>0){
        
        $sql = "SELECT produto_serie, observacao
                FROM tbl_produto_serie
                WHERE '$numero_serie' between serie_inicial and serie_final
                AND fabrica = $login_fabrica AND serie_ativa is true ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            $observacao = utf8_encode(pg_fetch_result($res, 0, observacao));
            echo json_encode(array('retorno' => "erro", 'observacao' => "$observacao"));
        }else{
            echo json_encode(array('retorno' => "ok"));
        }
    }

    exit;
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


if ($S3_sdk_OK) {
    include_once S3CLASS;
    $s3_ge = new anexaS3('ge', (int) $login_fabrica); //Anexo garantia estendida para Elgin
    $S3_online = is_object($s3_ge);
}

if ($login_fabrica == 11 or $login_fabrica == 172 OR $login_fabrica == 126 OR $login_fabrica == 137 OR $login_fabrica == 3) {
    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $amazonTC = new AmazonTC("os", $login_fabrica);
}


/**
 * @author William Castro <william.castro@telecontrol.com.br>
 * hd-6639553 -> Box Uploader
 * verifica se tem anexo
 */  
if ($fabricaFileUploadOS) {

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

#HD 424887 - INICIO
/*

A variavel abaixo será para identificar as fábricas que terão o campo "Defeito_reclamado" sem integridade.
Por enquanto só a Fricon, quando precisar mais fábricas é só colocar adicionar nessa variável .

*/

/* Foram retirados os arrays, agora essas inforamçoes estão na tbl_fabrica no campo parametros_adicionais. As variáveis estão sendo montadas no arquivo autentica_admin.php -- Ronald (HD-1010800)

$fabricas_defeito_reclamado_sem_integridade = array(52);
$fab_opcao_distribuidor_fabricante          = array(51, 81, 155, 114); //escolher atendimento pedido entre o Distrib Telecontrol e a Fábrica
$vet_sem_preco                              = array(3,6,11,35,45,51,80);//HD 361213, 363345
$verifica_ressarcimento_troca               = array(81, 155, 114);
$fabrica_gerencia_telecontrol               = array(81, 155, 114);
$fabrica_modalidade_transporte              = array(3, 81, 155, 114);
$fabrica_usa_distrib_telecontrol            = array(51, 81, 155, 114);
$aExibirDefeitoReclamado                    = array(3,7,19,24,28,30,35,42,50,59,74,81, 155,85,95,52,90,98,99,114); // Já tem >=101 ... ,101,114
$defeito_reclamado_descricao_obigatorio     = array(28, 35, 50,90, 95,98,101,104,105, 114);
$fabrica_aparencia_produto_select           = array(20,114);
$troca_produto_pedido_cancelado             = array(81, 155,114);
$fabrica_os_multi_produto                   = array(81, 155,114);
$fabricas_alteram_conserto                  = array(6,80);
$fabricas_tipo_atendimento  = array(7,15,19,20,30,40,42,50,74,91,115,116,117);
*/
//  HD 234135 - MLG - Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica = in_array($login_fabrica, array(3,117));

if(in_array($login_fabrica, array(101))){
    $limite_anexos_nf = 5;
}

#HD 424887 - FIM
#HD 308346 - Função que anexa a nota fiscal e outras validações
include_once('../anexaNF_inc.php');

#HD 418875 - Alert quando o produto estiver com peça obrigatória
#acesso somente via AJAX
$referencia_troca_obrigatoria = $_POST['referencia_troca_obrigatoria'];
if(strlen($referencia_troca_obrigatoria) > 0){
    $sql = "
    SELECT
    produto
    FROM
    tbl_produto
    JOIN tbl_linha USING(linha)
    WHERE
    fabrica = $login_fabrica
    AND referencia = '$referencia_troca_obrigatoria'
    AND troca_obrigatoria IS NOT NULL;";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res)>0)
        echo 1;

    exit;
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

    if (filter_input(INPUT_POST,'tipo') == "verificaLinha") {
        $referencia = filter_input(INPUT_POST,'produto');

        $sql = "SELECT linha FROM tbl_produto WHERE referencia = '" . $referencia . "'";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $ref        = pg_result($res, 0, 0);
            $retorno    = (in_array($ref,array(4,335,510,623,915))) ? true : false;
            echo json_encode(array("ok" => true, "retorno" => $retorno));
        } else {
            echo "erro";
        }
    }
    if (filter_input(INPUT_POST,'tipo') == "produto_garantia_peca") {
        $desmarcar = filter_input(INPUT_POST,'desmarcar',FILTER_VALIDATE_BOOLEAN);
        if (!$desmarcar) {
            $ref        = pg_result($res, 0, 0);
            $desc       = pg_result($res, 0, 1);

            unset($campos_telecontrol);

            $campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tipo']          = 'data';
            $campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['obrigatorio']   = 1;

            $campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tipo']                = 'data';
            $campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['obrigatorio']         = 1;

            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tipo']        = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['obrigatorio'] = 1;

            $campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tipo']            = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['obrigatorio']     = 1;

            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tipo']           = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['obrigatorio']    = 1;

            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tipo']           = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['obrigatorio']    = 1;

            echo json_encode(array("ok" => true, "referencia" => $ref,"descricao" => utf8_encode($desc)));
        } else {
            unset($campos_telecontrol);

            $campos_telecontrol[$login_fabrica]['tbl_os']['posto_codigo']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['posto_codigo']['obrigatorio'] = 1;

            //NOME DO POSTO
            $campos_telecontrol[$login_fabrica]['tbl_os']['posto_nome']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['posto_nome']['obrigatorio'] = 1;

            //DATA DE ABERTURA
            $campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['tipo'] = 'data';
            $campos_telecontrol[$login_fabrica]['tbl_os']['data_abertura']['obrigatorio'] = 1;

            //REFERENCIA DO PRODUTO
            $campos_telecontrol[$login_fabrica]['tbl_os']['produto_referencia']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['produto_referencia']['obrigatorio'] = 1;

            //DESCRICAO DO PRODUTO
            $campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['produto_descricao']['obrigatorio'] = 1;


            //NOME DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_nome']['obrigatorio'] = 1;


            //FONE DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['obrigatorio'] = 0;

            //CEP DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cep']['obrigatorio'] = 0;

            //ENDERECO DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_endereco']['obrigatorio'] = 1;

            //NUMERO DO ENDEREÃ‡O DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_numero']['obrigatorio'] = 1;

            //COMPLEMENTO DO ENDEREÃ‡O DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_complemento']['obrigatorio'] = 0;

            //BAIRRO DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_bairro']['obrigatorio'] = 1;

            //CIDADE DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cidade']['obrigatorio'] = 1;

            //ESTADO DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['tipo'] = 'select';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_estado']['obrigatorio'] = 1;

            //EMAIL DO CONSUMIDOR
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['obrigatorio'] = 1;

            //NOME REVENDA
            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_nome']['obrigatorio'] = 1;

            //REVENDA CNPJ
            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['revenda_cnpj']['obrigatorio'] = 1;

            //NOTA FISCAL
            $campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['obrigatorio'] = 1;

            //DATA NF
            $campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['tipo'] = 'data';
            $campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['obrigatorio'] = 1;

            //ORIENTACAO SAC
            $campos_telecontrol[$login_fabrica]['tbl_os']['orientacao_sac']['tipo'] = 'texto';
            $campos_telecontrol[$login_fabrica]['tbl_os']['orientacao_sac']['obrigatorio'] = 0;

            echo json_encode(array("ok" => true));
        }
    }
    exit;
}

if($login_fabrica == 91){
    if($_POST['garantia_diferenciada'] == "ok" AND $_POST['ajax']){
        $numero_serie = $_POST['numero_serie'];
        $referencia_produto = $_POST['referencia_produto'];

        $sqlProduto = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$referencia_produto'";
        $resProduto = pg_query($con, $sqlProduto);
        if(pg_num_rows($resProduto) > 0){
            $produto = pg_fetch_result($resProduto, 0, 'produto');
        }else{
            ob_clean();
            $msg_error = trim("ok|Produto não encontrado");
        }
        $sqlGarantiaDiferenciada = "SELECT cliente_garantia_estendida, os, produto, numero_serie
                                    FROM tbl_cliente_garantia_estendida
                                    WHERE produto = $produto
                                    AND numero_serie = '$numero_serie'
                                    AND fabrica = $login_fabrica";
        $resGarantiaDiferenciada = pg_query($con, $sqlGarantiaDiferenciada);

        if(pg_num_rows($resGarantiaDiferenciada) > 0){
            ob_clean();
           $msg_error = trim("ok|Numero de Série e Produto já cadastrado com Garantia Diferenciada.");
        }else{
            $msg_error = "";
        }

        if(strlen($msg_error) > 0){
            echo $msg_error;
        }
        exit;
    }
}

if(isset($_POST['posto']) AND $_POST['verifica_posto'] == "ok" and $_POST['ajax']){

    $posto = trim($_POST['posto']);

    $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE codigo_posto = '$posto' AND fabrica = $login_fabrica and tipo_posto = 464 ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        echo "ok|sim";
    }else{
        echo "ok|nao";
    }

    exit;

}

if(isset($_POST['verifica_prefixo']) && $_POST['verifica_prefixo'] == "ok"){

    $login_posto = $_POST['posto'];

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

    $sql_pais_posto = "SELECT contato_pais FROM tbl_posto_fabrica WHERE codigo_posto = '$login_posto' AND fabrica = $login_fabrica";
    $res_pais_posto = pg_query($con, $sql_pais_posto);

    if(pg_num_rows($res_pais_posto) > 0){
        $pais_posto = pg_fetch_result($res_pais_posto, 0, 'contato_pais');

        if(array_key_exists($pais_posto, $cod_pais)){
            $prefixo = $cod_pais[$pais_posto];
        }
    }

    echo json_encode(array("cod" => utf8_encode($prefixo), "pais" => utf8_encode($pais_posto)));

    exit;

}

if($_GET['monta_defeitos'] ==  "sim"){//HD-3331834
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
					AND	tbl_diagnostico.ativo
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


//Validação de Número de Série para LatinaTec
if (trim($_GET['verificarNumeroSerie']) == '1'){

    if (strlen($referencia) > 0 AND strlen($ns) > 0){

        $referencia_produto = trim($referencia);
        $numero_serie       = strtoupper(trim($ns));
        $sql = "SELECT numero_serie_obrigatorio
        from tbl_produto
        where referencia = '$referencia_produto'
        ";
        //      and tbl_produto.ativo is true
        $res = pg_exec($con,$sql);
        if(pg_numrows($res)>0){
            $serie_obrigatorio = pg_result($res,0,0);
            if($serie_obrigatorio=="t"){
                if(strlen($numero_serie)>10 or strlen($numero_serie)<8){
                    echo utf8_encode("Número inválido. Tamanho inválido");
                    exit;
                }
                $sql = "SELECT TO_CHAR(CURRENT_DATE,'y')::numeric";
                $res = pg_exec($con,$sql);
                $ano_corrente = pg_result($res,0,0);

                $meses = array('A','B','C','D','E','F','G','H','I',
                    'J','K','L','M','N','O','P','Q','R','S',
                    'T','U','V','W','Y','X','Z');

                $sql ="SELECT SUBSTR('ABCDEFGHIJKLMNOPQRSTUVWYXZ',TO_CHAR(CURRENT_DATE,'YYYY')::INTEGER - 1994,1)";

                $res = pg_exec($con,$sql);
                $letra_ano = pg_result($res,0,0);

                $sql ="SELECT SUBSTR('ABCDEFGHIJKL',TO_CHAR(CURRENT_DATE,'MM')::INTEGER ,1)";

                $res = pg_exec($con,$sql);
                $letra_mes = pg_result($res,0,0);

                $letra_inicial = array('1','4','9');
                if(!in_array(substr($numero_serie, 0, 1),$letra_inicial)){

                    echo utf8_encode("Erro no primeiro digito. Tem que ser 1 ou 4 ou 9");
                    exit;
                }

                if(is_numeric(substr($numero_serie, 1, 1))){

                    echo utf8_encode("Erro no segundo digito. Tem que ser letra");
                    exit;
                }

                if(is_numeric(substr($numero_serie, 2, 1))){

                    echo utf8_encode("Erro no terceiro digito. Tem que ser letra");
                    exit;
                }

                /* QUARTO CARACTER TEM QUE SER LETRA. ANO */
                /* ANO NÃO PODE SER MAIOR QUE O ATUAL */
                //echo "<BR>Quarta letra ".substr($numero_serie, 3, 1);
                //echo "<BR>ano corrente $letra_ano <BR>";
                if(is_numeric(substr($numero_serie, 3, 1)) or substr($numero_serie, 3, 1) > $letra_ano){
            //      echo substr($numero_serie, 3, 1);
                    echo utf8_encode(" Erro no Quarta digito. Tem que ser letra");
                    exit;
                }

                /* QUANDO ANO CORRENTE O MES NÃO PODE SER MAIOR QUE O ATUAL */
                //echo "<BR>Quarta letra 2 - ".substr($numero_serie, 3, 1);
                //echo "<BR>mes corrente $letra_mes <BR> mes da OS ".substr($numero_serie, 2, 1)."<BR>";
                if(substr($numero_serie, 3, 1) == $letra_ano){
                    if(substr($numero_serie, 2, 1) > $letra_mes){
                    //  echo substr($numero_serie, 3, 1);
                        echo utf8_encode(" Fabricado neste ano, mas o mes esta superior[".substr($numero_serie, 2, 1)."] que o atual [$letra_mes]");
                        exit;
                    }
                }
            //  echo "resto : ".substr($numero_serie, 4,strlen(trim($numero_serie))-3);
                if(!is_numeric(substr($numero_serie, 4,strlen(trim($numero_serie))-3) )){
                    echo utf8_encode("Erro, radical final tem que ser número. Radical final: ".substr($numero_serie, 4,strlen(trim($numero_serie))-3));
                    exit;
                }
            //  echo "<BR><BR><STRONG>PARABENS!!! NÚMERO DE SÉRIE SEM PROBLEMAS!!!</STRONG><br><br>";

            }else{
                echo utf8_encode("Número de série não obrigatório");
                exit;
            }
        }else{
            echo utf8_encode("Produto não encontrado");
            exit;
        }


        //fazer validação
    }
    exit;
}

#HD 311414 - Atualização do campo "Causa Raiz" para TECTOY - INICIO
if ( $login_fabrica == 6){

    if ($_POST["causa_troca_select"]){

        $causa_troca_select = $_REQUEST["causa_troca_select"];

        $sql_item_causa_troca = "Select                 causa_troca_item,
        descricao,
        codigo
        FROM tbl_causa_troca_item
        WHERE causa_troca = $causa_troca_select
        AND tbl_causa_troca_item.ativo     IS TRUE
        ORDER BY codigo";
                                // echo nl2br($sql_item_causa_troca);

        $res_item_causa_troca = pg_query($con,$sql_item_causa_troca);

        if ( pg_num_rows($res_item_causa_troca)>0 ){

            for ($i=0;$i < pg_num_rows($res_item_causa_troca); $i++ ){

                $item_id        = pg_fetch_result($res_item_causa_troca,$i,'causa_troca_item');
                $item_codigo    = pg_fetch_result($res_item_causa_troca,$i,'codigo');
                $item_descricao = pg_fetch_result($res_item_causa_troca,$i,'descricao');

                echo "<option value='$item_id'>$item_codigo - $item_descricao</option>";
            }

        }else{
            echo "<option value=''>Nenhum item ativo</option>";
        }
        exit;
    }

}

#HD 311414 - Atualização do campo "Causa Raiz" para TECTOY - FIM


//  Para testes da tela de pesquisa
if (preg_match('/os_cadastro(.*).php/', $PHP_SELF, $a_suffix)) {
    $suffix = $a_suffix[1];
    if (file_exists("pesquisa_numero_serie$suffix.php")) $ns_suffix = $suffix;
    if (file_exists("posto_pesquisa_2$suffix.php"))      $pp_suffix = $suffix;
    if (file_exists("posto_pesquisa_km$suffix.php"))     $pk_suffix = $suffix;
    if (file_exists("pesquisa_consumidor$suffix.php"))   $pc_suffix = $suffix;
    if (file_exists("pesquisa_revenda$suffix.php"))      $rv_suffix = $suffix;
}

if($ajax=='tipo_atendimento'){
    $sql = "SELECT tipo_atendimento,km_google
    FROM tbl_tipo_atendimento
    WHERE tipo_atendimento = $id
    AND   fabrica          = $login_fabrica";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res)>0){

        $km_google = pg_fetch_result($res,0,km_google);
        echo utf8_encode(($km_google == 't') ? "ok|sim" : "no|não");
        exit;
    }
}


if(!function_exists("validaCPF")){
    function validaCPF($cpf){
        global $con;
        $cpf = preg_replace('/\D/','', $cpf);

        if(strlen($cpf) > 0){
            $res = @pg_query($con, "SELECT fn_valida_cnpj_cpf('$cpf')");
        }
        return(pg_last_error($con) == '');
    }
}

if($_GET['verifica_digita_os_posto'] == "true"){

    $linha      = $_GET['linha'];
    $id_posto   = $_GET['id_posto'];

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
                          WHERE posto = $id_posto
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


# HD 33729 - Francisco Ambrozio (19/8/08)
#   Campos "Capacidade" e "Divisão" preenchidos por ajax.
if($_GET["ajax"]=="true" AND $_GET["buscaInformacoes"]=="true"){
    $referencia = trim($_GET["produto_referencia"]);
    $serie      = trim($_GET["serie"]);

    if(strlen($referencia)>0){
        $sql = "SELECT produto, capacidade, divisao
        FROM tbl_produto
        JOIN tbl_linha USING(linha)
        WHERE fabrica  = $login_fabrica
        AND referencia ='$referencia'";
        $res = @pg_query($con,$sql);

        if (pg_num_rows($res)>0){
            $produto    = trim(pg_fetch_result($res,0,produto));
            $capacidade = trim(pg_fetch_result($res,0,capacidade));
            $divisao    = trim(pg_fetch_result($res,0,divisao));

            echo "ok|$capacidade|$divisao|$versao";
            exit;
        }
    }
    echo "nao|nao";
    exit;
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

    $busca      = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q) > 2) {

        if ($tipo_busca == 'revenda') {
            if(in_array($login_fabrica, array(3,15,117))){
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
            }else{
                if ($busca == 'nome') {

                    $sql = "SELECT tbl_revenda.cnpj,
                    tbl_revenda.nome as nome_revenda
                    FROM tbl_revenda
                    WHERE (UPPER(nome) LIKE UPPER('%$q%'))";

                }else{

                    $sql = "SELECT  tbl_revenda.cnpj,
                    tbl_revenda.nome as nome_revenda
                    FROM tbl_revenda
                    WHERE cnpj LIKE '$q%'";
                }


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

/*IGOR HD: 44202 - 16/10/2008*/
if ($login_fabrica == 3){
    $xos = $_GET['os'];
    if (strlen($xos) == 0) {
        $xos = $_POST['os'];
    }
    if (strlen($xos) > 0) {
        $status_os = "";
        $sql = "SELECT status_os
        FROM  tbl_os_status
        WHERE os=$xos
        AND status_os IN (120, 122, 123, 126)
        ORDER BY data DESC LIMIT 1";
        $res_intervencao = pg_query($con, $sql);
        $msg_erro        = pg_errormessage($con);

        if (pg_num_rows ($res_intervencao) > 0 ){
            $status_os = pg_fetch_result($res_intervencao,0,status_os);
            if ($status_os=="122"){
                header ("Location: os_press.php?os=$xos");
                exit;
            }
        }
    }
}

// HD 31188
if($_GET["ajax"]=="true" AND $_GET["buscaValores"]=="true"){
    $referencia = trim($_GET["produto_referencia"]);

    if(strlen($referencia)>0){
        $sql = "SELECT produto, capacidade, divisao
        FROM tbl_produto
        JOIN tbl_linha USING(linha)
        WHERE fabrica  = $login_fabrica
        AND referencia ='$referencia'";
        $res = @pg_query($con,$sql);

        if (pg_num_rows($res)>0){
            $produto    = trim(pg_fetch_result($res,0,produto));

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

// HD 2502295
if (in_array($login_fabrica, array(11, 172)) && isset($_POST['mostraBuscaOS'])) {

    $refPosto = $_POST['refPosto'];
    $nomePosto = $_POST['nomePosto'];

    $sqlVerPosto = "SELECT posto, nome
                                FROM tbl_posto
                                JOIN tbl_posto_fabrica USING(posto)
                                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                                AND tbl_posto_fabrica.codigo_posto = '{$refPosto}'
                                ORDER BY posto ASC
                                LIMIT 1;";
                                
    $resVerPosto = pg_query($con, $sqlVerPosto);

    if (pg_num_rows($resVerPosto) > 0) {
        $codPosto = pg_fetch_result($resVerPosto, 0, posto);

        /*
         * Verifica se o posto encontrado é o posto interno
         * Código do Posto Interno = 14301
         * Data: 08/12/2015
        */
        if ($codPosto == 14301) {
            $retorno = array("sucesso" => "Posto encontrado");
        } else {
            $retorno = array("erro" => "Posto nao encontrado");
        }
    } else {
        $retorno = array("erro" => "Posto nao encontrado");
    }

    echo json_encode($retorno);
    exit(0);

}

if (in_array($login_fabrica, array(11, 172)) && isset($_POST['buscaOS'])) {

    $codOS = $_POST['codOS'];

    $sqlBuscaOS = "SELECT o.consumidor_nome,
                                            o.consumidor_cpf,
                                            o.consumidor_fone,
                                            o.consumidor_cep,
                                            o.consumidor_endereco,
                                            o.consumidor_numero,
                                            o.consumidor_complemento,
                                            o.consumidor_bairro,
                                            o.consumidor_cidade,
                                            o.consumidor_estado,
                                            o.consumidor_email,
                                            o.consumidor_celular,
                                            o.consumidor_fone_comercial,
                                            p.referencia AS produto_referencia,
                                            p.descricao AS produto_descricao,
                                            o.serie AS produto_serie,
                                            o.fabrica
                                FROM tbl_os o
                                LEFT JOIN tbl_produto p USING(produto)
                                WHERE o.fabrica = {$login_fabrica}
                                AND o.sua_os = '{$codOS}';";

    $resBuscaOS = pg_query($con, $sqlBuscaOS);

    if (pg_num_rows($resBuscaOS) > 0) {
        $retorno = array("sucesso" => "Dados encontrados",
                                    "consumidorNome" => utf8_encode(pg_fetch_result($resBuscaOS, 0, consumidor_nome)),
                                    "consumidorCPF" => pg_fetch_result($resBuscaOS, 0, consumidor_cpf),
                                    "consumidorFone" => pg_fetch_result($resBuscaOS, 0, consumidor_fone),
                                    "consumidorCEP" => pg_fetch_result($resBuscaOS, 0, consumidor_cep),
                                    "consumidorEndereco" => utf8_encode(pg_fetch_result($resBuscaOS, 0, consumidor_endereco)),
                                    "consumidorNumero" => pg_fetch_result($resBuscaOS, 0, consumidor_numero),
                                    "consumidorComplemento" => utf8_encode(pg_fetch_result($resBuscaOS, 0, consumidor_complemento)),
                                    "consumidorBairro" => utf8_encode(pg_fetch_result($resBuscaOS, 0, consumidor_bairro)),
                                    "consumidorCidade" => utf8_encode(pg_fetch_result($resBuscaOS, 0, consumidor_cidade)),
                                    "consumidorEstado" => pg_fetch_result($resBuscaOS, 0, consumidor_estado),
                                    "consumidorEmail" => pg_fetch_result($resBuscaOS, 0, consumidor_email),
                                    "consumidorCelular" => pg_fetch_result($resBuscaOS, 0, consumidor_celular),
                                    "consumidorFoneComercial" => pg_fetch_result($resBuscaOS, 0, consumidor_fone_comercial),
                                    "produtoReferencia" => pg_fetch_result($resBuscaOS, 0, produto_referencia),
                                    "produtoDescricao" => utf8_encode(pg_fetch_result($resBuscaOS, 0, produto_descricao)),
                                    "produtoSerie" => pg_fetch_result($resBuscaOS, 0, produto_serie)
                                    );
    } else {
        $retorno = array("erro" => utf8_encode("Não foram encontrados dados para essa OS!"));
    }

    echo json_encode($retorno);
    exit(0);

}

/*********************** FECHA OS LENOXX HD 52209 **************************/
if ($btn_acao == "fechar_os" && (in_array($login_fabrica, array(11, 15, 172)))) {
    $msg_erro = "";
    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql_obs            = "SELECT orientacao_sac from tbl_os join tbl_os_extra using(os) where os = $os";
    $res_obs            = pg_query($con,$sql_obs);
    $orientacao_sac_aux = pg_fetch_result($res_obs,0,orientacao_sac);

    $sql_usario  = "SELECT login from tbl_admin where admin = $login_admin";
    $res_usuario = pg_query($con,$sql_usario);
    $usuario     = pg_fetch_result($res_usuario,0,login);

    $data_hoje = date("d/m/Y H:i:s");
    $orientacao_sac .= "<p>OS fechada pelo Admin: $usuario</p>";
    $orientacao_sac .= "<p>Data: $data_hoje</p>";
    $orientacao_sac .= $orientacao_sac_aux;
	$orientacao_sac = str_replace("'","\'", $orientacao_sac);

    $sql      = "UPDATE  tbl_os_extra SET orientacao_sac = trim(E'$orientacao_sac')
    WHERE tbl_os_extra.os = $os;";
    $res      = pg_query ($con,$sql);
    $msg_erro = pg_errormessage($con);
    if (strlen ($msg_erro) == 0 and ($login_fabrica <> 11 and $login_fabrica <> 172)) { #HD 94416
        $sql = "UPDATE tbl_os_extra SET admin_paga_mao_de_obra = 't' WHERE os = $os";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_errormessage($con) ;
    }

    if (strlen ($msg_erro) == 0) {#HD 94361
        $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_DATE, admin = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_errormessage($con) ;
    }

    if (strlen ($msg_erro) == 0 && $login_fabrica != 95) {

        if(in_array($login_fabrica, array(11,172))){
            $aux_sql = "
                SELECT DISTINCT(tbl_os_item.pedido)
                FROM tbl_os_item
                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                WHERE tbl_os_produto.os = $os
				AND tbl_os_item.pedido notnull
            ";
            $aux_res      = pg_query($con, $aux_sql);
            $aux_total    = pg_num_rows($aux_res);
            $pedidos      = array();
            $pedido_itens = array();

            for ($x = 0; $x < $aux_total; $x++) {
                $temp_pedido = pg_fetch_result($aux_res, $x, 'pedido');
                if (!in_array($pedidos, $temp_pedido)) {
                    $pedidos[] = $temp_pedido;
                }
                unset($temp_pedido);
            }

            if (count($pedidos) > 0) {
                foreach ($pedidos as $pedido) {
                    $aux_sql   = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
                    $aux_res   = pg_query($con, $aux_sql);
                    $aux_total = pg_num_rows($aux_res);

                    for ($x = 0; $x < $aux_total; $x++) {
                        $temp_pedido_item = pg_fetch_result($aux_res, $x, 'pedido_item');
                        if (!in_array($pedido_itens, $temp_pedido_item)) {
                            $pedido_itens[] = $temp_pedido_item;
                        }
                        unset($temp_pedido_item);
                    }
                }

                if (count($pedido_itens) > 0) {
                    foreach ($pedido_itens as $pedido_item) {
                        $aux_sql = "
                            SELECT pedido, qtde, qtde_faturada, qtde_cancelada
                            FROM tbl_pedido_item
                            WHERE pedido_item = $pedido_item
                            LIMIT 1
                        ";
                        $aux_res        = pg_query($con, $aux_sql);
                        $pedido         = (int) pg_fetch_result($aux_res, 0, 'pedido');
                        $qtde           = (int) pg_fetch_result($aux_res, 0, 'qtde');
                        $qtde_cancelada = (int) pg_fetch_result($aux_res, 0, 'qtde_cancelada');
                        $qtde_faturada  = (int) pg_fetch_result($aux_res, 0, 'qtde_faturada');

                        if($qtde_faturada == 0) {
                            $sql_cancel = "
                                UPDATE tbl_pedido_item SET
                                qtde_cancelada = $qtde
                                WHERE pedido_item = $pedido_item;

                                SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);
                            ";
                            $res_cancel = pg_query($con, $sql_cancel);

                            if (pg_num_rows($res_cancel) <= 0) {
                                $msg_erro = "Erro ao excluir o pedido pendente da OS";
                            }
                        }
                    }
                }
            }
            unset($aux_sql, $aux_res, $aux_total, $pedidos, $pedido_itens);

            $sql_fabrica = "SELECT fabrica FROM tbl_os WHERE os = {$os}";
            $res_fabrica = pg_query($con, $sql_fabrica);

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");
            $sql = "SELECT fn_finaliza_os($os, $fabrica_os)";
            $res = @pg_query ($con,$sql);
        } else {
            $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
            #echo $sql;
            $res = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con) ;
        }
    }

echo pg_last_error();
    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        header("Location: os_press.php?os=$os");
        exit;
    }else{
        $res = @pg_query ($con,"ROLLBACK TRANSACTION");
    }
}
/************************ FIM FECHA OS **************************/

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query ($con,$sql);
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);

if (strlen($_POST['os']) > 0){
    $os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0){
    $os = trim($_GET['os']);
}
if (strlen($_POST['os']) > 0 and strlen($_GET['os']) == 0) {
    $os = trim($_POST['os']);
}

if (strlen($_POST['sua_os']) > 0){
    $sua_os = trim($_POST['sua_os']);
}

if (strlen($_GET['sua_os']) > 0){
    $sua_os = trim($_GET['sua_os']);
}

if($login_fabrica == 35){
    if(strlen($_REQUEST['cancela_mao_obra']) > 0){
        $cancela_mao_obra = $_REQUEST['cancela_mao_obra'];
    }
}

if($gerar_pedido=='ok' ){
    $sql = "BEGIN TRANSACTION";
    $res = pg_query($con,$sql);

    $sql = "UPDATE tbl_os_troca SET gerar_pedido = TRUE WHERE os = $os";
    $res = @pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if(strlen($msg_erro)==0){
        $sql = "COMMIT TRANSACTION";
        $res = pg_query($con,$sql);
        header("Location: os_press.php?os=$os");
        exit;
    } else {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);

$btn_cancelar = strtolower ($_POST['cancelar']);
if ($btn_cancelar == "cancelar") {
    $os                  = $_POST["os"];
    $motivo_cancelamento = trim($_POST["motivo_cancelamento"]);

    if(strlen($motivo_cancelamento)==0) $msg_erro = "Por favor digite o motivo do cancelamento da OS";
    if(strlen($msg_erro)==0){
        $sql = "SELECT DISTINCT pedido
        FROM tbl_os
        JOIN tbl_os_produto USING(os)
        JOIN tbl_os_item    USING(os_produto)
        WHERE tbl_os.fabrica = $login_fabrica
        AND   tbl_os.os      = $os
        AND   tbl_os_item.pedido IS NOT NULL";
        $res1 = @pg_query($con,$sql);
        if(pg_num_rows($res1)>0){
            for($i=0;$i<pg_num_rows($res1);$i++){
                $pedido = pg_fetch_result($res1,$i,0);
                $sql = "SELECT  PI.pedido_item,
                PI.qtde      ,
                PC.peca      ,
                PC.referencia,
                PC.descricao ,
                OP.os        ,
                PE.posto     ,
                PE.distribuidor
                FROM    tbl_pedido       PE
                JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
                JOIN    tbl_peca         PC ON PC.peca       = PI.peca
                LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
                WHERE   PI.pedido      = $pedido
                AND     PE.fabrica     = $login_fabrica
                AND     PE.exportado   IS NULL";
                $res2 = pg_query($con,$sql);
                if(pg_num_rows($res2)>0){
                    $peca  = pg_fetch_result($res2,0,peca);
                    $qtde  = pg_fetch_result($res2,0,qtde);
                    $posto = pg_fetch_result($res2,0,posto);
                    $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido_item = $cancelar;";
                    $res = pg_query ($con,$sql);
                    $sql = "INSERT INTO tbl_pedido_cancelado (
                        pedido,
                        posto,
                        fabrica,
                        os,
                        peca,
                        qtde,
                        motivo,
                        data
                        )VALUES(
                        $pedido,
                        $posto,
                        $login_fabrica,
                        $os,
                        $peca,
                        $qtde,
                        '$motivo_cancelamento',
                        current_date
                        );";
 $res = pg_query ($con,$sql);
}else{
    if($login_fabrica <> 45) $msg_erro= "OS não pode ser cancelada porque o pedido já foi exportado!";
}
}
}
if(strlen($msg_erro)==0){

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect("select * from tbl_os join tbl_os_produto using(os) join tbl_os_item using(os_produto) where os = {$os}");

    $sql = "BEGIN TRANSACTION";
    $res = pg_query($con,$sql);
    $sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,15,'$motivo_cancelamento');";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);
    $sql = "UPDATE tbl_os SET excluida = TRUE WHERE os = $os";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

            #158147 Paulo/Waldir desmarcar se for reincidente
    $sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
    $res = pg_query($con, $sql);

    if(strlen($msg_erro)==0){
        $sql = "COMMIT TRANSACTION";
        $res = pg_query($con,$sql);

        $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);

        header("Location: os_press.php?os=$os");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}
}

}


/*======= Troca em Garantia =========*/
$btn_troca = strtolower ($_POST['btn_troca']);
if ($btn_troca == "trocar") {

    if (in_array($login_fabrica, array(141,144))) {
        $sql = "SELECT defeito_constatado FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $res = pg_query($con, $sql);

        $defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

        if (empty($defeito_constatado)) {
            $msg_erro = "Informe o defeito constatado na ordem de serviço para realizar a troca de produto/ressarcimento";
        }
    }

    // HD 410675 - Colocado para todas as fábricas
    $sql = "SELECT os FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os and fabrica = $login_fabrica";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) and $login_fabrica != 144) {

        $msg_erro = "OS já entrou em extrato e não pode ser trocada. ";

    }

    if ($login_fabrica == 91) {//HD 702297

        $sql = "SELECT tbl_os.os
        FROM tbl_os
        JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.troca_peca
        JOIN tbl_os_produto USING(os)
        JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND pedido IS NULL
        JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND gera_pedido AND tbl_servico_realizado.troca_de_peca
        WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_os.os = $os";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res)) {
            $msg_erro = 'Troca não pode ser efetuada, aguarde gerar o pedido da peça.';
        }

    }

    // HD 679319 - Fim
    if (isset($_POST["marca_troca"]) && strlen($_POST["marca_troca"]) == 0 && $login_fabrica != 30) {
        $msg_erro .= "Selecione a MARCA do Produto<br>";
    } else {
        if($login_fabrica == 30){
            $marca_troca = "";
        }else{
            $marca_troca = $_POST["marca_troca"];
        }
    }

    if (isset($_POST["familia_troca"]) && strlen($_POST["familia_troca"]) == 0) {
        $msg_erro .= "Selecione a FAMÍLIA do Produto<br>";
    } else {
        $familia_troca = $_POST["familia_troca"];
    }

    if (isset($_POST["troca_garantia_produto"]) && strlen($_POST["troca_garantia_produto"]) == 0) {
        $msg_erro .= "Selecione o PRODUTO para troca<br>";
    } else {
        $troca_garantia_produto = $_POST["troca_garantia_produto"];
    }

    if (isset($_POST["causa_troca"]) && strlen($_POST["causa_troca"]) == 0) {
        $msg_erro .= "Selecione a CAUSA da troca<br>";
    } else {
        $causa_troca = $_POST["causa_troca"];
    }

    if ($login_fabrica == 6){

        if (isset($_POST["causa_raiz"]) && strlen($_POST["causa_raiz"]) == 0) {
            $msg_erro .= "Selecione a Causa Raiz<br>";
        } else {
            $causa_raiz = $_POST["causa_raiz"];
        }

    }

    if ($login_fabrica==51) {
        if (isset($_POST["coleta_postagem"]) && strlen($_POST["coleta_postagem"]) == 0) {
            $msg_erro .= "Informe o N° Coleta/Postagem<br>";
        } else {
            $coleta_postagem = $_POST["coleta_postagem"];
        }

        if (isset($_POST["data_postagem"]) && strlen($_POST["data_postagem"]) == 0) {
            $msg_erro .= "Informe a Data Solicitação<br>";
        } else {
            $data_postagem   = $_POST["data_postagem"];
        }
    }

    if ($login_fabrica == 101) {
        if (filter_input(INPUT_POST,'gerar_pedido') && !filter_input(INPUT_POST,'envio_consumidor')) {
            $msg_erro .= "Selecione o DESTINO do produto<br>";
        }
    }

    if (isset($_POST["observacao_pedido"]) && strlen($_POST["observacao_pedido"]) == 0) {
        $msg_erro .= "Informe uma OBSERVAÇÃO para NOTA FISCAL<br>";
    } else {
        $observacao_pedido = addslashes(str_replace("'", '"', $_POST["observacao_pedido"]));
    }

    if ($login_fabrica == 30) {
        if (strlen($_POST["classificacao_atendimento"]) == 0) {
            $msg_erro .= 'Selecione a classificação do atendimento.<br>';
        }
    }

    if (isset($_POST["setor"]) && strlen($_POST["setor"]) == 0) {
        $msg_erro .= "Selecione o SETOR RESPONSÁVEL<br>";
    } else {
        $setor = $_POST["setor"];
    }

    if (isset($_POST["fabrica_distribuidor"]) && strlen($_POST["fabrica_distribuidor"]) == 0) {
        $msg_erro .= "Selecione EFETUAR TROCA POR: Fábrica ou Distribuidor<br>";
    } else {
        $fabrica_distribuidor = $_POST["fabrica_distribuidor"];
    }

    if (isset($_POST["envio_consumidor"]) && strlen($_POST["envio_consumidor"]) == 0) {

        $msg_erro .= "Selecione o DESTINO do produto<br>";
    } else {
        $envio_consumidor = $_POST["envio_consumidor"];
    }

    if (isset($_POST["modalidade_transporte"]) && strlen($_POST["modalidade_transporte"]) == 0) {
        $msg_erro .= "Selecione a MODALIDADE DO TRANSPORTE<br>";
    }
    else {
        $modalidade_transporte = $_POST["modalidade_transporte"];
    }

    if($telecontrol_distrib) {
        if(strlen($_POST["fabrica_distribuidor"]) == 0) {
            $msg_erro .= "Atender via Distribuidor ou Fabricante?";
        }else{
            $fabrica_distribuidor = $_POST["fabrica_distribuidor"];
            if($fabrica_distribuidor == 'distribuidor'){
                $fabrica_distribuidor = '4311';
            }else{
                $fabrica_distribuidor = 'null';
            }
        }
    }else{
        $fabrica_distribuidor = 'null';
    }
  
//      echo "Fab.Distri: $fabrica_distribuidor<br><br>";
//      HD 79774 - Paulo César 10/03/2009 sempre gera pedido para fabrica 3
//      HD 83652 - IGOR - Retirar regra de gerar pedido sempre para Britania
    if($login_fabrica==3){
        if( strlen($_POST["gerar_pedido"])         == 0 ) $gerar_pedido = "'f'";
        else                                              $gerar_pedido = "'t'";
        if($_POST["envio_consumidor"]=='t')               $envio_consumidor = " 't' ";
        else                                              $envio_consumidor = " 'f' ";
    }else if($login_fabrica == 30){
        $gerar_pedido       = "'f'";
        $envio_consumidor   = " 'f' ";
    }else{
        if( strlen($_POST["gerar_pedido"])         == 0 ) $gerar_pedido = "'f'";
        else                                              $gerar_pedido = "'t'";
        if($_POST["envio_consumidor"]=='t')               $envio_consumidor = " 't' ";
        else                                              $envio_consumidor = " 'f' ";
    }

    if ($login_fabrica == 101 && empty($msg_erro)) {
        $aux_causa_troca = $_POST["causa_troca"];
        if (empty($aux_causa_troca) || $aux_causa_troca != "199") {
            $aux_sql = "SELECT consumidor_celular FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os LIMIT 1";
            $aux_res = pg_query($con, $aux_sql);

            if (pg_num_rows($aux_res) > 0) {
                $contato = pg_fetch_result($aux_res, 0, 0);
            }

            if (!empty($contato)) {
                include_once "../class/sms/sms.class.php";

                $sms = new SMS();
                $mensagem_sms = "
                    Olá, somos da Delonghi/Kenwood, referente a OS $os iremos proceder com a troca do seu produto, por indisponibilidade de peças, em breve você receberá um contato telefônico para prosseguir com o processo, obrigado.
                ";
                $enviar_sms = $sms->enviarMensagem($contato, '', '', $mensagem_sms);

                if (!$enviar_sms) {
                    $msg_erro = "Erro ao enviar SMS ao consumidor";
                }
            }
        }
    }
}

//Status da troca - Mallory
$troca_com_nota  = $_POST['troca_com_nota'];
$justificativanf = $_POST['justificativanf'];

if($troca_com_nota == 'sem_nota_sem_troca' and $login_fabrica==72 and strlen($msg_erro)==0 ){
    //Grava o status 154 -  Troca pendente
    $xstatus_os       = 154;
    $xjustificativanf = 'Troca pendente';

    if(strlen($os)>0){
        $sqlOS = "SELECT sua_os, posto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $resOS = pg_exec($con, $sqlOS);

        if(pg_numrows($resOS)>0){
            $sua_os = pg_result($resOS,0,sua_os);
            $posto  = pg_result($resOS,0,posto);
        }
    }

    // Comunicado avisando o posto que a troca está pendente
    $sqlC = "INSERT INTO tbl_comunicado (
        descricao              ,
        mensagem               ,
        tipo                   ,
        fabrica                ,
        obrigatorio_os_produto ,
        obrigatorio_site       ,
        posto                  ,
        ativo
        ) VALUES (
        'OS com troca pendente',
        'A OS $sua_os está pendente para troca, para regularizá-la anexe uma nota fiscal <a href=http://posvenda.telecontrol.com.br/assist/os_press.php?os=$os>[Anexar Nota]</a>',
        'Troca pendente',
        $login_fabrica,
        'f' ,
        't',
        $posto,
        't'
        );";
    //echo nl2br($sqlC);
    $resC = pg_exec($con, $sqlC);

    if(strlen($msg_erro)==0){
        $sqlStatus = "INSERT INTO tbl_os_status (
           os            ,
           status_os     ,
           data          ,
           admin         ,
           fabrica_status,
           observacao
           ) VALUES (
           $os              ,
           $xstatus_os      ,
           current_timestamp,
           $login_admin     ,
           $login_fabrica   ,
           '$xjustificativanf'
           );";
        //echo nl2br($sqlStatus);
        $resStatus = pg_exec($con, $sqlStatus);
    }

    if(strlen($msg_erro)==0){
        $sucesso = "Foi enviado um comunicado ao posto informando que a troca está pendente";
    }
}
//TROCA PENDENTE FIM

if ($btn_troca == "trocar" && strlen($msg_erro) == 0) {
    $msg_erro = "";

    //HD 341693 INICIO
    $troca_garantia_produto  = $_POST["troca_garantia_produto"];
    $os                      = $_POST["os"];

    if(strlen($login_admin) > 0 && strlen($os) > 0 AND in_array($login_fabrica,array(94))){

        $sql_admin = "UPDATE tbl_os SET admin = {$login_admin} WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_admin = pg_query($con, $sql_admin);

    }

    if($login_fabrica == 30){
        $laudo          = $_POST['laudo'];
        $cadastra_laudo = $_POST['cadastra_laudo'];
        $familia_troca  = $_POST['familia_troca'];
        if(strlen($laudo) == 0){
            $msg_erro .= "É obrigatório a seleção de um tipo de laudo para troca do produto!";
        }
    }else{
        $laudo = "null";
    }
    if ($login_fabrica == 51) {
        $coleta_postagem         = $_POST["coleta_postagem"];
        $data_postagem           = $_POST["data_postagem"];
        $xdata_postagem          = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_postagem);
        $xdata_postagem          = "'".$xdata_postagem."'";
    }else{
        $coleta_postagem = 'null';
        $xdata_postagem   = 'null';

    }

    if ($login_fabrica == 101) {
        $produto_os_troca_atual_aux = $_POST['produto_os_troca_atual'];
        $troca_garantia_produto_aux = $_POST['troca_garantia_produto'];
        if ($produto_os_troca_atual_aux !== $troca_garantia_produto_aux && $solicita_lgr == 't') {
            $msg_erro = "Para solicitar o produto na LGR o produto da troca deve ser o mesmo modelo da O.S.";
        }

        $solicita_lgr = $_POST['solicita_lgr'];
    }

    $observacao_pedido       = addslashes(str_replace("'", '"', $_POST["observacao_pedido"]));
    $qtde_itens              = $_POST["qtde_itens"];
    $troca_garantia_mao_obra = $_POST["troca_garantia_mao_obra"];
    $troca_garantia_mao_obra = str_replace(",",".",$troca_garantia_mao_obra);
    $troca_via_distribuidor  = $_POST['troca_via_distribuidor'];

    if (strlen($troca_via_distribuidor) == 0) $troca_via_distribuidor = "f";

    $sql = "SELECT produto,serie, sua_os, posto FROM tbl_os WHERE os = $os;";
    $res = @pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $serie_produto = pg_fetch_result($res, 0, 'serie');
    $produto = pg_fetch_result($res, 0, 'produto');
    $sua_os  = pg_fetch_result($res, 0, 'sua_os');
    $posto   = pg_fetch_result($res, 0, 'posto');

    if (in_array($login_fabrica, array(138))) {
        $produto = $_POST["produto_troca"];

        if (empty($produto)) {
            $msg_erro = "Informe o produto a ser trocado";
        }
    }

    if ($troca_garantia_produto != "-1" && $troca_garantia_produto != "-2") {

        $sql = "BEGIN TRANSACTION";
        $res = pg_query($con,$sql);

        $sql = "SELECT *
        FROM tbl_produto
        JOIN tbl_familia USING(familia)
        WHERE produto = '$troca_garantia_produto'
        AND fabrica = $login_fabrica;";

        $resProd   = @pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if (@pg_num_rows($resProd) == 0) {
            $msg_erro .= "Produto informado não encontrado<br />";
        } else {
            $troca_produto    = pg_fetch_result($resProd, 0, 'produto');
            $troca_ipi        = pg_fetch_result($resProd, 0, 'ipi');
            $troca_referencia = pg_fetch_result($resProd, 0, 'referencia');
            $troca_descricao  = pg_fetch_result($resProd, 0, 'descricao');
            $troca_familia    = pg_fetch_result($resProd, 0, 'familia');
            $troca_linha      = pg_fetch_result($resProd, 0, 'linha');

            $troca_descricao = substr($troca_descricao,0,50);
        }

        if (strlen($msg_erro) == 0) {

            $sql = "SELECT  tbl_peca.peca           ,
                            tbl_peca.produto_acabado
                    FROM    tbl_peca
                    WHERE   tbl_peca.referencia = '$troca_referencia'
                    AND     tbl_peca.fabrica    = $login_fabrica";
            if($login_fabrica == 59){
                $sql .="  AND produto_acabado IS TRUE";
            }
            $res = pg_query($con, $sql);
            $msg_erro .= pg_errormessage($con);

            if (pg_num_rows($res) == 0) {

                if (strlen($troca_ipi) == 0) $troca_ipi = 10;

                $sql = "SELECT peca
                FROM tbl_peca
                WHERE fabrica    = $login_fabrica
                AND referencia = '$troca_referencia'
                LIMIT 1;";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                if (pg_num_rows($res) > 0) {

                    $peca           = pg_fetch_result($res, 0, 0);
                    $peca_acabado   = pg_fetch_result($res, 0, 1);

                } else {

                    $sql = "INSERT INTO tbl_peca (
                        fabrica,
                        referencia,
                        descricao,
                        ipi,
                        origem,
                        produto_acabado
                        ) VALUES (
                        $login_fabrica,
                        '$troca_referencia',
                        '$troca_descricao',
                        $troca_ipi,
                        'NAC',
                        't'
                        )";

                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    $sql = "SELECT CURRVAL ('seq_peca')";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                    $peca = pg_fetch_result($res,0,0);

                }

                $sql = "INSERT INTO tbl_lista_basica (
                    fabrica,
                    produto,
                    peca,
                    qtde
                    ) VALUES (
                    $login_fabrica,
                    $produto,
                    $peca,
                    1
                    );";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                if ($login_fabrica == 101 && !empty($peca_acabado)) {
                    $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tabela_garantia IS TRUE AND ativa IS TRUE;";
                    $resTabela = pg_query($con,$sql);

                    $preco_pp = 0;

                    for ($x = 0; $x < pg_num_rows($resTabela); $x++) {
                        $tabela = pg_fetch_result($resTabela,0,'tabela');

                        $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
                        $resX = pg_query($con,$sql);

                        if(pg_num_rows($resX) == 0){
                            $sql = "INSERT INTO tbl_tabela_item(peca,tabela,preco) VALUES($peca,$tabela, $preco_pp)";
                        }else{
                            $sql = "UPDATE tbl_tabela_item SET preco = $preco_pp WHERE peca = $peca AND tabela = $tabela";
                        }
                        $resS = pg_query($con,$sql);
                    }
                }

                // Elgin cadastro de Preço
                if (in_array($login_fabrica, array(117))) {
                    $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND tabela_garantia IS TRUE AND ativa IS TRUE;";
                    $resTabela = pg_query($con,$sql);

                    $sqlpp = "SELECT preco FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$troca_referencia';";
                    $respp = pg_query($con,$sqlpp);

                    if(pg_num_rows($respp) > 0 AND !empty($peca)){
                        $preco_pp = pg_fetch_result($respp, 0, preco);

                        if (empty($preco_pp)) {
                            $preco_pp = 0;
                        }

                        for($x = 0; $x < pg_num_rows($resTabela); $x++){
                            $tabela = pg_fetch_result($resTabela,0,'tabela');

                            $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela";
                            $resX = pg_query($con,$sql);

                            if(pg_num_rows($resX) == 0){
                                $sql = "INSERT INTO tbl_tabela_item(peca,tabela,preco) VALUES($peca,$tabela, $preco_pp)";
                            }else{
                                $sql = "UPDATE tbl_tabela_item SET preco = $preco_pp WHERE peca = $peca AND tabela = $tabela";
                            }
                            $resS = pg_query($con,$sql);
                        }
                    }
                    if (pg_last_error()) {
                        $msg_erro["msg"] = "Erro ao inserir preço do produto!";
                    }
                }

            } else {
                $produto_acabado = pg_fetch_result($res,0,'produto_acabado');
                $peca = pg_fetch_result($res, 0, 'peca');

                if($produto_acabado <> 't') {
                    if($login_fabrica == 30){
                        $sqlAcaba = "
                            UPDATE  tbl_peca
                            SET     produto_acabado = TRUE
                            WHERE   fabrica     = $login_fabrica
                            AND     referencia  = '$troca_referencia'
                        ";
                        $resAcaba = pg_query($con,$sqlAcaba);
                    }else{
                        $msg_erro = "Favor verificar o cadastro da peça $troca_referencia, deve estar marcardo como produto acabado para realizar a troca";
                    }
                }
            }

        }

        if (strlen($msg_erro) > 0) {
            $res = pg_query($con,"ROLLBACK;");
            $msg_erro .= pg_errormessage($con);
        } else {
            $res = pg_query($con,"COMMIT;");
            $msg_erro .= pg_errormessage($con);

            if ($login_fabrica == 101 && empty($msg_erro)) {
                $aux_sql = "SELECT consumidor_celular FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os LIMIT 1";
                $aux_res = pg_query($con, $aux_sql);

                if (pg_num_rows($aux_res) > 0) {
                    $contato = pg_fetch_result($aux_res, 0, 0);
                }

                if (!empty($contato)) {
                    include_once "../class/sms/sms.class.php";

                    $sms = new SMS();
                    $mensagem_sms = "
                        Olá, somos da Delonghi/Kenwood, referente a OS $os iremos proceder com a troca do seu produto, por indisponibilidade de peças, em breve você receberá um contato telefônico para prosseguir com o processo, obrigado.
                    ";
                    $enviar_sms = $sms->enviarMensagem($contato, '', '', $mensagem_sms);

                    if (!$enviar_sms) {
                        $msg_erro = "Erro ao enviar SMS ao consumidor";
                    }
                }
            }
        }

        if (!$vet_sem_preco && !in_array($login_fabrica, array(101)) || ($login_fabrica == 101 && empty($msg_erro))) {//HD 361213

            if ($login_fabrica == 14) {

                $sql_peca = "SELECT tbl_tabela_item.preco
                FROM tbl_tabela_item
                JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
                JOIN tbl_posto_linha ON tbl_tabela.tabela      = tbl_posto_linha.tabela
                WHERE tbl_posto_linha.posto   = $posto
                AND tbl_tabela_item.peca    = $peca
                AND tbl_posto_linha.familia = $troca_familia";

            } else {

                $sql_peca = "SELECT tbl_tabela_item.preco
                FROM tbl_tabela_item
                JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
                JOIN tbl_posto_linha ON tbl_tabela.tabela      = tbl_posto_linha.tabela
                WHERE tbl_posto_linha.posto = $posto
                AND tbl_tabela_item.peca  = $peca
                AND tbl_posto_linha.linha = $troca_linha";

            }

            $res = pg_query($con,$sql_peca);

            if (pg_num_rows($res) == 0) {
                $sql_peca2 = "SELECT tbl_tabela_item.preco
                FROM tbl_tabela_item
                JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
                WHERE tbl_tabela_item.peca  = $peca
                AND   tbl_tabela.fabrica = $login_fabrica";

                $res2 = pg_query($con,$sql_peca2);
                if (pg_num_rows($res2) == 0 && !in_array($login_fabrica, array(15,30,40,115,116,117,120,121,122,123,124,126,127,128,138,140,141,144))) {
                    $msg_erro = "O produto $troca_referencia não tem preço na tabela de preço. Cadastre o preço para poder dar continuidade na troca.";
                }
            }
        }

    }//HD 341693 FIM

    if (!in_array($login_fabrica, array(6, 30, 51, 81, 114, 155))) {
        if (strlen($_POST['situacao_atendimento']) == 0) $msg_erro = '<br />Informe a Situação do Atendimento';
        else                                             $situacao_atendimento = $_POST['situacao_atendimento'];
    } else {
        $situacao_atendimento = 'null';
    }

    $sql = "BEGIN TRANSACTION";
    $res = pg_query($con,$sql);

    #Verifica se a OS tem nota e grava o status na OS - Mallory
    if ($login_fabrica == 72) {
        if ($temImg = temNF($os, 'bool')) {
            //Se tiver nota deixa gravar normal e grava o status 152 - Troca com nota
            $xstatus_os       = 152;
            $xjustificativanf = 'Troca com nota';
        }else if($troca_com_nota == 'sem_nota_com_troca'){
            //Grava o status 153 - Trocado sem nota
            $xstatus_os       = 153;
            $xjustificativanf = $justificativanf;

            if(strlen($justificativanf)==0){
                $msg_erro = "Informe a justificativa para troca de OS sem nota fiscal";
            }
        }
        if(strlen($msg_erro)==0 and strlen($xstatus_os) > 0 ){
            $sqlStatus = "INSERT INTO tbl_os_status (
               os            ,
               status_os     ,
               data          ,
               admin         ,
               fabrica_status,
               observacao
               ) VALUES (
               $os              ,
               $xstatus_os      ,
               current_timestamp,
               $login_admin     ,
               $login_fabrica   ,
               '$xjustificativanf'
               );";
            //echo nl2br($sqlStatus);
            $resStatus = pg_exec($con, $sqlStatus);
        }
    }
    //Status troca Mallory Fim

    if ($verifica_ressarcimento_troca) {
        $sqlressarcimento = "SELECT hd_chamado,ressarcimento from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os";
        $resressarcimento = pg_exec($con,$sqlressarcimento);

        if (pg_num_rows($resressarcimento)>0) {
            $hd_chamado_troca    = pg_result($resressarcimento,0,0);
            $ressarcimento_troca = pg_result($resressarcimento,0,1);
            if ($ressarcimento_troca == 't' and $troca_garantia_produto <> '-1' ) {
                $msg_erro .= "Foi definido no callcenter que esta Ordem de Serviço é um ressarcimento, por favor escolha a opção ressarcimento<br>";
            } else {

            }
        }
    }

    #HD 51899
$sql = "SELECT credenciamento
FROM  tbl_posto_fabrica
JOIN  tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto
WHERE tbl_os.fabrica            = $login_fabrica
AND   tbl_os.os                 = $os
AND   tbl_posto_fabrica.fabrica = $login_fabrica
AND   tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';";
$res = pg_query ($con,$sql);
if(pg_num_rows($res)>0){
    $msg_erro .= "Este posto está DESCREDENCIADO. Não é possível efetuar a troca do produto.<br>";
}

$sql = " SELECT os FROM tbl_os WHERE os = $os and fabrica = $login_fabrica and data_fechamento IS NOT NULL and finalizada IS NOT NULL ";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
    $os_fechada = pg_fetch_result($res,0,0);
}

if ($login_fabrica == 101 && $solicita_lgr == 't') {
    $sql = "UPDATE tbl_os SET prateleira_box = 'troca_lgr' WHERE os = {$os} AND fabrica = {$login_fabrica}";
    pg_query($con, $sql);
    $msg_erro .= pg_errormessage($con);
}



if ($_REQUEST["auditoria_obrigatoria"] == 't' && in_array($login_fabrica, [35])) {

    $sqlAuditoriaTroca = "INSERT INTO tbl_auditoria_os (os,auditoria_status,observacao)
                          VALUES ({$os}, 3, 'OS em auditoria de troca de produto')";
    $resAuditoriaTroca = pg_query($con, $sqlAuditoriaTroca);

} else {

    if (in_array($login_fabrica, [35])) {

        $sqlPrecoProduto = "SELECT DISTINCT tbl_tabela_item.preco
                            FROM tbl_os_produto
                            JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
                            JOIN tbl_peca ON UPPER(tbl_peca.referencia) = UPPER(tbl_produto.referencia)
                            AND tbl_peca.produto_acabado IS TRUE
                            JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
                            JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
                            AND tbl_tabela.tabela_garantia IS TRUE
                            WHERE tbl_os_produto.os = {$os}
                            ";
        $resPrecoProduto = pg_query($con, $sqlPrecoProduto);
        
        $precoProduto = (float) pg_fetch_result($resPrecoProduto, 0, 'preco');

        if (pg_num_rows($resPrecoProduto) > 0 && $precoProduto >= 200) {
            
            $sqlAuditoria = "
                    SELECT ao.auditoria_os
                    FROM tbl_auditoria_os ao
                    INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status
                    WHERE ao.os = {$os}
                    AND a.produto IS TRUE
                    AND ao.liberada IS NULL
                    AND ao.reprovada IS NULL
                    AND ao.cancelada IS NULL
                    AND ao.observacao = 'OS em auditoria de troca de produto'
                ";
            $resAuditoria = pg_query($con, $sqlAuditoria);

            if (!pg_num_rows($resAuditoria)) {
                $sqlAuditoriaProduto = "
                    SELECT auditoria_status FROM tbl_auditoria_status WHERE produto IS TRUE
                ";
                $resAuditoriaProduto = pg_query($con, $sqlAuditoriaProduto);

                $auditoria_status = pg_fetch_result($resAuditoriaProduto, 0, 'auditoria_status');

                $insertAuditoria = "
                    INSERT INTO tbl_auditoria_os
                    (os, auditoria_status, observacao)
                    VALUES
                    ({$os}, {$auditoria_status}, 'OS em auditoria de troca de produto')
                ";
                $resInsertAuditoria = pg_query($con, $insertAuditoria);

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro .= "Erro ao gravar auditoria #1 <br />";
                }
            }

        }

    }

}

if (in_array($login_fabrica, [72])) {

    $sqlNumeroSerie = "SELECT tbl_numero_serie.numero_serie
                       FROM tbl_os
                       JOIN tbl_numero_serie ON tbl_os.serie = tbl_numero_serie.serie
                       AND tbl_numero_serie.fabrica = {$login_fabrica}
                       WHERE tbl_os.os = {$os}";
    $resNumeroSerie = pg_query($con, $sqlNumeroSerie);

    if (pg_num_rows($resNumeroSerie) == 0) {

        $sqlInsereSerie = "
        INSERT INTO tbl_numero_serie (
            fabrica,
            serie,
            referencia_produto,
            data_venda,
            produto,
            bloqueada_garantia
        ) SELECT tbl_os.fabrica,
                 tbl_os.serie,
                 tbl_produto.referencia,
                 tbl_os.data_nf,
                 tbl_os.produto,
                 true
        FROM tbl_os
        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
		WHERE tbl_os.os = {$os}
		AND tbl_os.serie notnull";
        $resInsereSerie = pg_query($con, $sqlInsereSerie);

    } else {

        $idNumeroSerie = pg_fetch_result($resNumeroSerie, 0, 'numero_serie');

        $sqlUpdSerie = "UPDATE tbl_numero_serie
                        SET bloqueada_garantia = true
                        WHERE numero_serie = {$idNumeroSerie}";
        $resUpdSerie = pg_query($con, $sqlUpdSerie);

    }

}

    //hd17603
if (!in_array($login_fabrica, array(24,35,123))) {

    $sql = "UPDATE tbl_os SET data_fechamento = NULL,finalizada=null WHERE os = $os AND fabrica = $login_fabrica ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);
}

$sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
$res = pg_query ($con,$sql);

if(pg_num_rows($res)>0){
    $troca_efetuada =  pg_fetch_result($res,0,os_troca);
    $troca_os       =  pg_fetch_result($res,0,os);
    $troca_peca     =  pg_fetch_result($res,0,peca);

    if (strlen($troca_peca) == 0 and $login_fabrica == 81 or $login_fabrica == 114) {
        $peca_para_troca = '4836000';

        $sql = "UPDATE tbl_os_produto
        SET os = $peca_para_troca
        FROM tbl_os_item
        WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
        AND os = $troca_os
        AND peca IN (
            SELECT tbl_peca.peca
            FROM tbl_peca
            JOIN tbl_os_item    USING (peca)
            JOIN tbl_os_produto USING (os_produto)
            JOIN tbl_os_extra   USING (os)
            JOIN tbl_os_troca   ON    tbl_os_produto.os = tbl_os_troca.os
            WHERE tbl_os_troca.os          =  $os
            AND tbl_peca.produto_acabado IS TRUE
            )";

 $res = pg_query ($con,$sql);
}



        //$sql = "DELETE FROM tbl_os_troca WHERE os_troca = $troca_efetuada";
$sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
$res = pg_query ($con,$sql);

        // HD 13229
if(strlen($troca_peca) > 0) {
    $sql = "UPDATE tbl_os_produto set os = 4836000 FROM tbl_os_item WHERE tbl_os_item.os_produto=tbl_os_produto.os_produto AND os=$troca_os and peca = $troca_peca and pedido isnull";
    $res = pg_query ($con,$sql);
}


}


if (strlen($qtde_itens)==0){
    $qtde_itens = 0;
}

if($login_fabrica != 30){
    for ($i=0; $i<$qtde_itens; $i++) {
        $os_item_check = $_POST["os_item_".$i];
        if (strlen($os_item_check)>0){
            $set_peca_obrigatoria = '';
            $sql = "UPDATE tbl_os_item SET originou_troca = 't' WHERE os_item = $os_item_check ";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }
    }
}else{
    $sql = "
        UPDATE  tbl_os_item
        SET     originou_troca = TRUE
        WHERE   os_item IN (
            SELECT  tbl_os_item.os_item
            FROM    tbl_os_item
            JOIN    tbl_os_produto  USING (os_produto)
            JOIN    tbl_os          USING (os)
            WHERE   tbl_os.os = $os
        )
    ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);
}

// adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (19,20,62,64,65,72,73,87,88,116,117,127) ORDER BY data DESC LIMIT 1";
$res = pg_query($con,$sql);
$qtdex = pg_num_rows($res);

if ($qtdex>0){

    $observacao = 'OS Liberada';
    if(in_array($login_fabrica,array(6, 114)) && $_REQUEST['btn_troca'] == 'trocar'){
        $observacao = 'Requisição de Troca';
    }
    $statuss=pg_fetch_result($res,0,status_os);
    $status_arr = array(20,62,65,72,87,116,127);
    if (in_array($statuss,$status_arr)){

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
        if ( $statuss == "20"){
            $proximo_status = "19";
        }

        $sql = 'INSERT INTO tbl_os_status
        (os,status_os,data,observacao,admin)
        VALUES ($1,$2,current_timestamp,$3,$4)';
        $params = array($os,$proximo_status,$observacao,$login_admin);
        $res = pg_query_params($con,$sql,$params);
        $msg_erro .= pg_errormessage($con);
    }
}

if ($telecontrol_distrib == "t") {

    $sqlAudUnica = "UPDATE tbl_auditoria_os 
                    SET liberada = CURRENT_TIMESTAMP, 
                        admin = {$login_admin}, 
                        observacao = 'OS Liberada, Requisição de troca' 
                    WHERE os = {$os} 
                    AND liberada IS NULL 
                    AND cancelada IS NULL 
                    AND reprovada IS NULL";
    $resAudUnica = pg_query($con, $sqlAudUnica);

}

    /**
     *
     * @since HD 736525
     * Inserido Houston e alterado para switch ao invés de vários if's
     * Francisco Ambrozio - Fri Nov  4 11:14:50 BRST 2011
     *
     */
    switch ($login_fabrica) {
        case 1:
            $id_servico_realizado        = 62;
            $id_servico_realizado_ajuste = 64;
            break;
        case 3:
            $id_servico_realizado        = 20;
            $id_servico_realizado_ajuste = 96;
            $id_solucao_os               = 85;
            $defeito_constatado          = 10224;
            break;
        case 11:
            //HD 340425: Para a Lenoxx, se não tiver pedido, não deixa gerar
            $id_servico_realizado        = 61;
            $id_servico_realizado_ajuste = 498;
            $id_solucao_os               = "";
            $defeito_constatado          = "";
            break;
        case 24:
            $id_servico_realizado        = 504;
            $id_servico_realizado_ajuste = 521;
            $id_solucao_os               = 701;
            $defeito_constatado          = 13308;
            break;
        case 25:
            $id_servico_realizado        = 625;
            $id_servico_realizado_ajuste = 628;
            $id_solucao_os               = 210;
            $defeito_constatado          = 10536;
            break;
        case 30:
            $id_servico_realizado        = 11143;
            $id_servico_realizado_ajuste = 11144;
            break;
        case 35:
            $id_servico_realizado        = 571;
            $id_servico_realizado_ajuste = 573;
            $id_solucao_os               = 472;
            $defeito_constatado          = 11815;
            break;
        case 45:
            $id_servico_realizado        = 638;
            $id_servico_realizado_ajuste = 639;
            $id_solucao_os               = 397;
            $defeito_constatado          = 11250;
            break;
        case 51:
            $id_servico_realizado        = 671;
            $id_servico_realizado_ajuste = 670;
            $id_solucao_os               = 491;
            $defeito_constatado          = 12068;
            break;
        case 72:
            $id_servico_realizado        = 9383;
            $id_servico_realizado_ajuste = 9380;
            $id_solucao_os               = 3047;
            $defeito_constatado          = 16123;
            break;
        case 81:
        //case 114:
            $id_servico_realizado        = 7458;
            $id_servico_realizado_ajuste = 10655;
            $id_solucao_os               = 2920;
            $defeito_constatado          = 15529;
            break;
        case 98:
            $id_servico_realizado        = 10532;
            $id_servico_realizado_ajuste = 10657;
            break;
        case 101:
            $id_servico_realizado        = 10577;
            $id_servico_realizado_ajuste = 10576;
            break;
		case 104:
            $id_servico_realizado        = 11225;
            $id_servico_realizado_ajuste = 11097;
            break;
        case 106:
            $id_servico_realizado        = 10600;
            $id_servico_realizado_ajuste = 10601;
            break;
		case 114:
            $id_servico_realizado        = 10660;
            $id_servico_realizado_ajuste = 11207;
            break;
        case 115:
            $id_servico_realizado        = 10669;
            $id_servico_realizado_ajuste = 10668;
            break;
        case 116:
            $id_servico_realizado        = 10672;
            $id_servico_realizado_ajuste = 10671;
            break;
        case 117:
            $id_servico_realizado        = 10676;
            $id_servico_realizado_ajuste = 10675;
            break;
        case 120:
            $id_servico_realizado        = 10678;
            $id_servico_realizado_ajuste = 10679;
            break;
        case 121:
            $id_servico_realizado        = 10679;
            $id_servico_realizado_ajuste = 10678;
            break;
        case 122:
            $id_servico_realizado        = 10693;
            $id_servico_realizado_ajuste = 10687;
            break;
        case 123:
            $id_servico_realizado        = 10739;
            $id_servico_realizado_ajuste = 10738;
            break;
        case 125:
            $id_servico_realizado        = "10741,10740,10742";
            $id_servico_realizado_ajuste = 10962;
            break;
        case 126:
            $id_servico_realizado        = 10766;
            $id_servico_realizado_ajuste = 10771;
            break;
        case 127:
            $id_servico_realizado        = 10748;
            $id_servico_realizado_ajuste = 10750;
            break;
        case 128:
            $id_servico_realizado        = 10763;
            $id_servico_realizado_ajuste = 10762;
            break;
        case 129:
            $id_servico_realizado        = 10759;
            $id_servico_realizado_ajuste = 10761;
            break;
        case 134:
            $id_servico_realizado        = 10772;
            $id_servico_realizado_ajuste = 10773;
            break;
        case 136:
            $id_servico_realizado        = 10966;
            $id_servico_realizado_ajuste = 10964;
            break;
        case 137:
            $id_servico_realizado        = 10974;
            $id_servico_realizado_ajuste = 10975;
            break;
        case 138:
            $id_servico_realizado        = 11119;
            $id_servico_realizado_ajuste = 11121;
            break;
        case 140:
            $id_servico_realizado        = 11104;
            $id_servico_realizado_ajuste = 11105;
            break;
        case 141:
            $id_servico_realizado        = 11109;
            $id_servico_realizado_ajuste = 11108;
            break;
        case 144:
            $id_servico_realizado        = 11113;
            $id_servico_realizado_ajuste = 11112;
            break;
        case 139:
            $id_servico_realizado        = 11122;
            $id_servico_realizado_ajuste = 11124;
            break;
        case 172:
            $id_servico_realizado        = 11287;
            $id_servico_realizado_ajuste = 11283;
            $id_solucao_os               = "";
            $defeito_constatado          = "";
            break;
    }

    if($login_fabrica == 3) {
        $id_solucao_os = 2931;

        $sql = "UPDATE tbl_os
                SET solucao_os = $id_solucao_os
                WHERE os       = $os
                AND fabrica    = $login_fabrica
                AND solucao_os IS NULL";

        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql_defeito = "UPDATE tbl_os
                        SET defeito_constatado = $defeito_constatado
                        WHERE os       = $os
                        AND fabrica    = $login_fabrica
                        AND defeito_constatado IS NULL";

        $res_defeito = pg_query($con,$sql_defeito);
        $msg_erro .= pg_errormessage($con);
    }

    if (strlen($id_servico_realizado_ajuste)>0 AND strlen($id_servico_realizado)>0){
        $sql =  "UPDATE tbl_os_item
        SET servico_realizado = $id_servico_realizado_ajuste
        WHERE os_item IN (
            SELECT os_item
            FROM tbl_os
            JOIN tbl_os_produto USING(os)
            JOIN tbl_os_item USING(os_produto)
            JOIN tbl_peca USING(peca)
			JOIN tbl_servico_realizado USING (servico_realizado)
            WHERE tbl_os.os       = $os
            AND tbl_os.fabrica    = $login_fabrica
            AND (tbl_os_item.servico_realizado in ( $id_servico_realizado )  or tbl_servico_realizado.troca_produto)
            AND tbl_os_item.pedido IS NULL
            )";
	 /* ************* retirado TRECHO DO SQL ABAIXO - hd: 50754 - IGOR ********** */
	 /*AND tbl_peca.retorna_conserto IS TRUE*/
	 /* Segundo Fábio, essa condição é desnecessária, pois todas peças devem ser canceladas*/
	 $res = pg_query($con,$sql);
	 $msg_erro .= pg_errormessage($con);
	}
	else if(in_array($login_fabrica,array(6))){
		$sql = 'UPDATE tbl_os_item
		SET servico_realizado = (
			SELECT servico_realizado
			FROM tbl_servico_realizado
			WHERE fabrica = $1
			AND NOT gera_pedido
			ORDER BY descricao
			LIMIT 1
			)
	 WHERE
	 os_item IN (
		SELECT tbl_os_item.os_item
		FROM tbl_os_item
		INNER JOIN tbl_os_produto
		ON (tbl_os_item.os_produto = tbl_os_produto.os_produto)
		WHERE tbl_os_produto.os = $2
		)
	 AND servico_realizado IN (
		SELECT servico_realizado FROM
		tbl_servico_realizado
		WHERE fabrica = $3
		AND gera_pedido
		)
	 AND pedido IS NULL
	 ';
	 $params = array($login_fabrica,$os,$login_fabrica);
	 $result = pg_query_params($con,$sql,$params);
	 $msg_erro .= pg_errormessage($con);
	}
	if($login_fabrica == 131){
		$sql = "SELECT servico_realizado from tbl_servico_realizado where fabrica = 131 and descricao ilike('cancelado')";
		$res = pg_query($con,$sql);
		$servico_realizado = pg_result($res,0,servico_realizado);
		if($servico_realizado != ""){
			$sql = "update tbl_os_item set servico_realizado = $servico_realizado where os_item in(select os_item from tbl_os_produto join tbl_os_item using(os_produto) where os  = $os)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

	}else{
		if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
			$sql = "UPDATE tbl_os
			SET solucao_os         = $id_solucao_os,
			defeito_constatado = $defeito_constatado
			WHERE os       = $os
			AND fabrica    = $login_fabrica
			AND solucao_os IS NULL
			AND defeito_constatado IS NULL";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	$orientacao_sac = trim ($_POST['orient_sac']);
	if (strlen($orientacao_sac) == 0) {
		$orientacao_sac  = "null";
	}

	if($login_fabrica == 11 or $login_fabrica == 172){
		if($troca_garantia_produto == -1){
			$orientacao_sac = "Ordem de Serviço gerou reembolso para o cliente";
		}else{
			$orientacao_sac = "Houve a troca do produto para esta Ordem de Serviço";
		}
	}
		//hd 11083 7/1/2008
	if($login_fabrica == 3){
		if (strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null'){
			$orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac;
			$sql = "UPDATE  tbl_os_extra SET
			orientacao_sac =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac')
			WHERE tbl_os_extra.os = $os;";
		}
	}else{
		if ($login_fabrica == 11 or $login_fabrica == 172) {

			$sql_obs = "SELECT orientacao_sac from tbl_os_extra where os = $os";
			$res_obs = pg_query($con,$sql_obs);
			$orientacao_sac_aux         = pg_fetch_result($res_obs,0,orientacao_sac);
			$sql_usario = "SELECT login from tbl_admin where admin = $login_admin";
			$res_usuario = pg_query($con,$sql_usario);
			$usuario         = pg_fetch_result($res_usuario,0,login);

			$data_hoje = date("d/m/Y H:i:s");
			$orientacao_sac .= "<p>Usuário: $usuario</p>";
			$orientacao_sac .= "<p>Data: $data_hoje</p>";
			$orientacao_sac .= $orientacao_sac_aux;
		}

		$orientacao_sac = pg_escape_string($con, $orientacao_sac);
		$sql = "UPDATE  tbl_os_extra SET orientacao_sac = trim('$orientacao_sac')
		WHERE tbl_os_extra.os = $os;";
	}

	$res = pg_query ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if ( $login_fabrica == 94 ) { // HD 758032

		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome
				FROM tbl_os
				JOIN tbl_posto USING(posto)
				WHERE os = $os";

		$res = pg_query($con,$sql);

		$posto_nome = pg_result($res,0,'nome');
		$posto_cnpj = pg_result($res,0,'cnpj');

		$sql = "INSERT INTO tbl_os(
					fabrica,
					posto,
					admin,
					produto,
					serie,
					nota_fiscal,
					data_digitacao,
					data_abertura,
					data_nf,
					defeito_constatado,
					defeito_reclamado_descricao,
					revenda_cnpj,
					revenda_nome,
					consumidor_nome,
					consumidor_cpf,
					consumidor_endereco,
					consumidor_cidade,
					consumidor_bairro,
					consumidor_numero,
					consumidor_complemento,
					consumidor_estado,
					consumidor_cep,
					consumidor_email,
					consumidor_fone,
					consumidor_celular,
					consumidor_fone_comercial,
					tipo_atendimento,
					acessorios,
					aparencia_produto,
					mao_de_obra
				)
				( SELECT
					tbl_os.fabrica,
					114768,
					$login_admin,
					produto,
					serie,
					nota_fiscal,
					data_digitacao,
					data_abertura,
					data_nf,
					defeito_constatado,
					defeito_reclamado_descricao,
					substr('$posto_cnpj',1,14),
					substr('$posto_nome',1,50),
					consumidor_nome,
					consumidor_cpf,
					consumidor_endereco,
					consumidor_cidade,
					consumidor_bairro,
					consumidor_numero,
					consumidor_complemento,
					consumidor_estado,
					consumidor_cep,
					consumidor_email,
					consumidor_fone,
					consumidor_celular,
					consumidor_fone_comercial,
					tipo_atendimento,
					acessorios,
					aparencia_produto,
					mao_de_obra
				FROM tbl_os
				LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os_troca_origem
				WHERE tbl_os.fabrica = $login_fabrica
				AND	tbl_os.posto <> 114768
				AND tbl_os_campo_extra.os isnull
				AND tbl_os.os = $os
				)

				RETURNING os";

        $res = pg_query($con,$sql);		
        if(pg_num_rows($res) > 0) {
			$os_interno = pg_result($res,0,0);
			$sql = "SELECT fn_valida_os($os_interno, $login_fabrica);

					INSERT INTO tbl_os_campo_extra(os,fabrica,os_troca_origem)
					VALUES($os_interno,$login_fabrica,$os)";

			$res = pg_query($con,$sql);
		}
    }

    if ($troca_garantia_produto == "-1") {//resarcimento financeiro

        if ($verifica_ressarcimento_troca) {
                $cpf_ressarcimento = $_POST['cpf_ressarcimento'];
                $banco             = $_POST['banco'];
                $agencia           = $_POST['agencia'];
                $conta             = $_POST['conta'];
                $valor             = $_POST['valor'];
                $tipo_conta        = $_POST['tipo_conta'];
                $favorecido_conta = $_POST['favorecido_conta'];

                if (strlen($cpf_ressarcimento)==0) {
                    $msg_erro .= "Para efetuar o ressarcimento digite o cpf do titular da conta<br>";
                }

                if (strlen($favorecido_conta)==0) {
                    $msg_erro .= "Para efetuar o ressarcimento digite o nome do titular da conta<br>";
                }


                if (strlen($banco)==0) {
                    $msg_erro .= "Para efetuar o ressarcimento escolha o banco do titular<br>";
                }

                if (strlen($agencia)==0) {
                    $msg_erro .= "Para efetuar o ressarcimento digite a agencia titular<br>";
                }

                if (strlen($conta)==0) {
                    $msg_erro .= "Para efetuar o ressarcimento digite a conta corrente do titular <br>";
                }

                if (strlen($valor)==0) {
                    $msg_erro .= "Para efetuar o ressarcimento digite o valor<br>";
                } else {
                    $valor = number_format($valor,2,'.','.');
                }

                if (strlen($msg_erro)==0) {

                    $sqlressarcimento = "SELECT hd_chamado,ressarcimento from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os";

                    $resressarcimento = pg_exec($con,$sqlressarcimento);

                    if (pg_num_rows($resressarcimento)>0) {
                        $hd_chamado_troca    = pg_result($resressarcimento,0,0);
                        $ressarcimento_troca = pg_result($resressarcimento,0,1);
                        if ($ressarcimento_troca == 't') {
                            $sqlatualiza = "UPDATE tbl_hd_chamado_extra_banco SET
                            agencia = $agencia,
                            contay = $conta,
                            cpf_conta = $cpf_ressarcimento,
                            favorecido_conta = '$favorecido_conta',
                            banco = $banco,
                            tipo_conta = '$tipo_conta',
                            fabrica = $login_fabrica
                            WHERE hd_chamado = $hd_chamado_troca";
                            $resatualiza = pg_exec($con,$sqlatualiza);

                            $sqlatualiza = "UPDATE tbl_hd_chamado_troca SET valor_produto = '$valor' where hd_chamado = $hd_chamado_troca";

                            $resatualiza = pg_exec($con,$sqlatualiza);
                        }
                    } else {
                        $sqlins = "INSERT INTO tbl_hd_chamado (
                            admin,
                            status,
                            atendente,
                            titulo,
                            fabrica_responsavel,
                            categoria,
                            fabrica)
                             values (
                                $login_admin,
                                'Aberto',
                                $login_admin,
                                'Atendimento Interativo',
                                $login_fabrica,
                                'ressarcimento',
                                $login_fabrica)";
                                                //echo nl2br($sqlins);
                        $resins = pg_exec($con,$sqlins);
                        $res    = pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
                        $hd_chamado = pg_fetch_result ($res,0,0);

                        $sqlins = "INSERT INTO tbl_hd_chamado_extra (
                            hd_chamado,
                            os,
                            produto,
                            posto,
                            data_nf,
                            nota_fiscal,
                            nome,
                            endereco,
                            numero,
                            bairro,
                            cep,
                            fone)
                         SELECT
                         $hd_chamado,
                         $os,
                         produto,
                         posto,
                         data_nf,
                         nota_fiscal,
                         consumidor_nome,
                         consumidor_endereco,
                         consumidor_numero,
                         consumidor_bairro,
                         consumidor_cep,
                         consumidor_fone
                         FROM tbl_os
                         WHERE os = $os;";

                        $resins = pg_exec($con,$sqlins);

                        $msg_erro .= pg_errormessage($con);

                        $sqlins = "INSERT INTO tbl_hd_chamado_item (
                            hd_chamado,
                            comentario,
                            admin,
                            status_item )
                         VALUES (
                            $hd_chamado,
                            'Foi cadastrado um ressarcimento no valor de R$ $valor e precisa ser efetivado pelo financeiro',
                            $login_admin,
                            'Aberto'
                            )";
                        $resins = pg_exec($con,$sqlins);
                        $msg_erro .= pg_errormessage($con);


                        $sqlins = "INSERT INTO tbl_hd_chamado_extra_banco (
                            hd_chamado       ,
                            banco            ,
                            agencia          ,
                            contay           ,
                            tipo_conta       ,
                            cpf_conta        ,
                            favorecido_conta )
                         VALUES (
                            $hd_chamado,
                            '$banco',
                            '$agencia',
                            '$conta',
                            '$tipo_conta',
                            '$cpf_ressarcimento',
                            '$favorecido_conta')";

                        $resins = pg_exec($con,$sqlins);
                        $msg_erro .= pg_errormessage($con);

                        $sqlins = "INSERT INTO tbl_hd_chamado_troca (
                            hd_chamado,
                            produto,
                            valor_produto,
                            ressarcimento)
                         VALUES (
                            $hd_chamado,
                            (select produto from tbl_os where os = $os),
                            $valor,
                            't')";
                        $resins = pg_exec($con,$sqlins);
                        $msg_erro .= pg_errormessage($con);
                }


                if (strlen($msg_erro)==0) {

                    $sql = "SELECT email from tbl_fabrica join tbl_admin ON tbl_fabrica.admin_ressarcimento = tbl_admin.admin where tbl_fabrica.fabrica = $login_fabrica";
                    $res = pg_exec($con,$sql);

                    if (pg_num_rows($res)>0) {

                        $sqlbanco = "SELECT nome from tbl_banco where banco = $banco";
                        $resbanco = pg_exec($con,$sqlbanco);
                        if (pg_num_rows($resbanco)>0) {
                            $nome_banco = pg_result($resbanco,0,0);
                        }
                        $message = "Foi cadastrado um novo ressarcimento financeiro e precisa ser baixado, acesse o sistema telecontrol e vá até a aba financeiro -><a href='http://posvenda.telecontrol.com.br/assist/admin/relatorio_ressarcimento.php'> <b>Baixar Ressarcimento</a></b>
                        <br><br>
                        Admin Responsável: $login_login <br>
                        <b>Os</b>: $os,<br>
                        <b>Numero Atendimento</b>: $hd_chamado,<br>
                        <b>Nome Favorecido</b>: $favorecido_conta<br>
                        <b>Cpf/CNPJ</b>: $cpf_ressarcimento<br>
                        <b>Banco</b>: $nome_banco<br>
                        <b>Tipo Conta</b>: $tipo_conta<br>
                        <b>Agencia</b>:$agencia<br>
                        <b>Conta:</b>$conta<br>
                        <b>Valor:</b>$valor";

                        $assunto = "Novo Ressarcimento";
                        $email = pg_result($res,0,0);

                        $headers = "From: Telecontrol <telecontrol@telecontrol.com.br>\n";

                        $headers .= "MIME-Version: 1.0\n";
                        $headers .= "Content-type: text/html; charset=iso-8859-1\n";
                        $headers .= "Cc: roberta@telecontrol.com.br,valeria@telecontrol.com.br,gabriel.rolon@telecontrol.com.br";

                        if (mail("$email", utf8_encode($assunto), utf8_encode($message), $headers)) {

                        }

                    }
                }

            }
        }

        $sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        if($login_fabrica == 3 AND pg_num_rows($res)==1 ) {
            $sql = "UPDATE tbl_os SET
            troca_garantia          = 't',
            ressarcimento           = 't',
            troca_garantia_admin    = $login_admin
            WHERE os = $os AND fabrica = $login_fabrica";
        }else{
            if($login_fabrica == 3){
                        // HD 18558, 24198
                $sql = "UPDATE tbl_os SET
                troca_garantia          = 't',
                ressarcimento           = 't',
                troca_garantia_admin    = $login_admin,
                data_conserto           = CURRENT_TIMESTAMP,
                data_fechamento         = CURRENT_DATE,
                finalizada              = CURRENT_TIMESTAMP
                WHERE os = $os AND fabrica = $login_fabrica";
            }elseif($login_fabrica == 35){
                        # HD 65952
                $sql = "UPDATE tbl_os SET
                troca_garantia          = 't',
                ressarcimento           = 't',
                troca_garantia_admin    = $login_admin
                WHERE os = $os AND fabrica = $login_fabrica";
            }elseif($login_fabrica == 11 or $login_fabrica == 172){
                        # HD 163061
                $sql = "UPDATE tbl_os SET
                troca_garantia          = 't',
                ressarcimento           = 't',
                troca_garantia_admin    = $login_admin
                WHERE os = $os AND fabrica = $login_fabrica";
            }elseif($login_fabrica == 6){
                $sql = "UPDATE tbl_os SET
                troca_garantia = 't',
                ressarcimento = 't',
                troca_garantia_admin = $login_admin
                WHERE os = $os AND fabrica = $login_fabrica";
            }elseif(in_array($login_fabrica, array(101,131,141,144))){
                $sql = "UPDATE tbl_os SET
                        troca_garantia          = 't',
                        ressarcimento           = 't',
                        troca_garantia_admin    = $login_admin
                        WHERE os = $os AND fabrica = $login_fabrica";
            } else {
                if($login_fabrica != 30){
                    $sql = "UPDATE tbl_os SET
                            troca_garantia          = 't',
                            ressarcimento           = 't',
                            troca_garantia_admin    = $login_admin,
                            data_fechamento         = CURRENT_DATE,
                            finalizada              = CURRENT_TIMESTAMP
                            WHERE os = $os AND fabrica = $login_fabrica";
                }
            }
        }
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE tbl_os_extra SET
        obs_nf                     = '$observacao_pedido'
        WHERE os = $os";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);

                //--== Novo Procedimento para Troca | Raphael Giovanini ===========

        if( strlen($_POST["causa_troca"])          == 0 ) $msg_erro .= "Escolha a causa da troca<br>";
        else                                              $causa_troca = $_POST["causa_troca"];

        if($login_fabrica != 30){
            if( strlen($_POST["setor"])                == 0 ) $msg_erro .= "Selecione o setor responsável<br>";
            else                                              $setor = $_POST["setor"];
        }else{
            $setor = 'null';
        }

        if (!in_array($login_fabrica, array(6, 30, 51, 81, 114, 155))) {
            if( strlen($_POST["situacao_atendimento"]) == 0 ) $msg_erro .= "<br>Selecione a situação do atendimento";
            else                                              $situacao_atendimento = $_POST["situacao_atendimento"];
        } else {
            $situacao_atendimento = 'null';
        }

        //HD 211825: O código que estava aqui foi movido para fora dos IFs, na validação

        $ri = $_POST["ri"];

        if (( $setor=='Procon' OR $setor=='SAP' OR $setor=='Jurídico' ) AND(strlen($ri)=="null")) $msg_erro .= "<br>Obrigatório o preenchimento do RI";

        if(strlen($_POST["ri"]) == 0) $ri = "null";
        else $ri = "'".$_POST["ri"]."'";

    	$modalidade_transporte = $_POST["modalidade_transporte"];

    	$xmodalidade_transporte = "'$modalidade_transporte'";

    	if($fabrica_modalidade_transporte and
    	   strlen($modalidade_transporte)==0)
    	   $msg_erro .= "É obrigatória a escolha da modalidade de transporte<br>";

    	//      echo "Fab.Distri: $fabrica_distribuidor<br><br>";
    	//      HD 79774 - Paulo César 10/03/2009 sempre gera pedido para fabrica 3

    	if($login_fabrica==3){

            if(strlen($msg_erro) == 0 ){
    			$sql = "INSERT INTO tbl_os_troca (
    				setor                 ,
    				situacao_atendimento  ,
    				os                    ,
    				admin                 ,
    				observacao            ,
    				causa_troca           ,
    				gerar_pedido          ,
    				ressarcimento         ,
    				envio_consumidor      ,
    				modalidade_transporte ,
    				ri                    ,
    				fabric                ,
    				distribuidor
    				)VALUES(
    				'$setor'                ,
    				$situacao_atendimento   ,
    				$os                     ,
    				$login_admin            ,
    				'$observacao_pedido'    ,
    				$causa_troca            ,
    				't'           ,
    				TRUE                    ,
    				$envio_consumidor       ,
    				$xmodalidade_transporte ,
    				$ri                     ,
    				$login_fabrica          ,
    				$fabrica_distribuidor
    				)";

                $res = @pg_query($con,$sql);
        		$msg_erro .= pg_errormessage($con);
    		}
    	}else{
    		if(strlen($msg_erro) == 0 ){

    			if ($login_fabrica==6){

    				$sql = "INSERT INTO tbl_os_troca (
    					setor                 ,
    					situacao_atendimento  ,
    					os                    ,
    					admin                 ,
    					observacao            ,
    					causa_troca           ,
    					causa_troca_item      ,
    					gerar_pedido          ,
    					ressarcimento         ,
    					envio_consumidor      ,
    					modalidade_transporte ,
    					ri                    ,
    					fabric                ,
    					distribuidor
    					)VALUES(
    					'$setor'                ,
    					$situacao_atendimento   ,
    					$os                     ,
    					$login_admin            ,
    					'$observacao_pedido'    ,
    					$causa_troca            ,
    					$causa_raiz             ,
    					$gerar_pedido           ,
    					TRUE                    ,
    					$envio_consumidor       ,
    					$xmodalidade_transporte ,
    					$ri                     ,
    					$login_fabrica          ,
    					$fabrica_distribuidor
    					)";
    		        $res = @pg_query($con,$sql);
    		        $msg_erro .= pg_errormessage($con);

        			try{
        				cancelaPedidoMotivoTroca($os);
        			}
        			catch(Exception $ex){
        				$msg_erro .= $ex->getMessage();
        			}

    		    }else{

					$sql = "DELETE FROM tbl_os_troca WHERE os = $os ; 
						INSERT INTO tbl_os_troca (
        				setor                 ,
        				situacao_atendimento  ,
        				os                    ,
        				admin                 ,
        				observacao            ,
        				causa_troca           ,
        				gerar_pedido          ,
        				ressarcimento         ,
        				envio_consumidor      ,
        				modalidade_transporte ,
        				ri                    ,
        				fabric                ,
        				distribuidor          ,
        				coleta_postagem       ,
        				data_postagem
        				)VALUES(
        				'$setor'                ,
        				$situacao_atendimento   ,
        				$os                     ,
        				$login_admin            ,
        				'$observacao_pedido'    ,
        				$causa_troca            ,
        				$gerar_pedido           ,
        				TRUE                    ,
        				$envio_consumidor       ,
        				$xmodalidade_transporte ,
        				$ri                     ,
        				$login_fabrica          ,
        				$fabrica_distribuidor   ,
        				'$coleta_postagem'      ,
        				$xdata_postagem
        				)";
                    $res = @pg_query($con,$sql);
    				$msg_erro .= pg_errormessage($con);

                    $pedido_cancela_garantia = true;

                    if ($fabrica_usa_distrib_telecontrol) {
                        $sql_embarque = "SELECT embarque
                            FROM tbl_os_produto
                            JOIN tbl_os_item USING(os_produto)
                            JOIN tbl_embarque_item USING(os_item)
                            WHERE tbl_os_produto.os = $os
                            AND   tbl_os_item.pedido IS NOT NULL
                            AND (
                                tbl_embarque_item.liberado IS NOT NULL
                                OR tbl_embarque_item.impresso IS NOT NULL
                            )";
                        $res_embarque = pg_query($con, $sql_embarque);

                        if (pg_num_rows($res_embarque) > 0) {
                            $pedido_cancela_garantia = false;
                        }
                    }

                    if (false === $pedido_cancela_garantia) {
                        $sql_audit_0 = "SELECT tbl_auditoria_os.auditoria_status
                            FROM tbl_auditoria_os
                            INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                            WHERE os = $os
                            AND tbl_auditoria_os.observacao = 'OS em intervenção da fábrica por Troca de Produto'
                            AND cancelada IS NULL
                            ORDER BY data_input DESC";
                        $res_audit_0 = pg_query($con, $sql_audit_0);

                        if (pg_num_rows($res_audit_0) == 0) {
                            $sql_audit = "INSERT INTO tbl_auditoria_os (
                                    os,
                                    auditoria_status,
                                    observacao
                                ) VALUES (
                                    $os,
                                    3,
                                    'OS em intervenção da fábrica por Troca de Produto'
                                )";
                            $res_audit = pg_query($con, $sql_audit);
                        }
                    } else {
                        $sql2 = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Ressarcimento Financeiro',$login_admin)
                                From tbl_os_produto
                                JOIN tbl_os_item USING(os_produto)
                                WHERE tbl_os_produto.os = $os
                                AND   tbl_os_item.pedido NOTNULL";
                        $res_x2 = pg_query($con,$sql2);
                        $msg_erro .= pg_errormessage($con);
                    }
    			}
    		}
    	}


			# HD 11631
	if (in_array($login_fabrica, array(3, 81, 114, 155)) and strlen($msg_erro)==0) {
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
		'OS $sua_os - Ressarcimento Financeiro',
		'A Fábrica irá fazer o ressarcimento financeiro do produto da OS $sua_os',
		'OS Ressarcimento Financeiro',
		$login_fabrica,
		'f' ,
		't',
		$posto,
		't'
		);";
	 $res = pg_query($con,$sql);
	 $msg_erro .= pg_errormessage($con);
	}

			#HD 311414 - INICIO
    	if (($login_fabrica==6) AND strlen($msg_erro)==0){
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
        		'OS $sua_os - Ressarcimento de Produto',
        		'A Fábrica irá Ressarcir o Produto, solicitamos para o Posto Autorizado <br />emitir Nota Fiscal com natureza de operação de Remessa para Conserto <br />e enviar preferêncialmente por e-mail ou pelo fax 11 3018-8055, <br />caso o produto esteja com acessório(s) faltante(s), <br />solicitamos para o Posto Autorizado, solicitar para o cliente os acessórios, <br />para posterior envio da Nota Fiscal.',
        		'OS Ressarcimento de Produto',
        		$login_fabrica,
        		'f' ,
        		't',
        		$posto,
        		't'
        		);";
    	    $res = pg_query($con,$sql);
    	    $msg_erro .= pg_errormessage($con);
    	}
			#HD 311414 _ FIM
    } elseif ($troca_garantia_produto == -2) {
    $sql = "UPDATE tbl_os SET
    troca_garantia          = 't',
    ressarcimento           = 'f',
    troca_garantia_admin    = $login_admin,
    data_fechamento         = CURRENT_DATE,
    finalizada              = CURRENT_TIMESTAMP
    WHERE os = $os AND fabrica = $login_fabrica";

    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "UPDATE tbl_os_troca SET
    troca_revenda           = 't'
    WHERE os = $os AND fabric = $login_fabrica";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

        $sql = "
        UPDATE tbl_os_extra SET
        obs_nf                     = '$observacao_pedido'
        WHERE os = $os";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_errormessage($con);
		$sql = "DELETE FROM tbl_os_troca WHERE os = $os ; 
				INSERT INTO tbl_os_troca (
                    setor                 ,
                    situacao_atendimento  ,
                    os                    ,
                    admin                 ,
                    observacao            ,
                    causa_troca           ,
                    gerar_pedido          ,
                    ressarcimento         ,
                    troca_revenda         ,
                    envio_consumidor      ,
                    modalidade_transporte ,
                    ri                    ,
                    fabric                ,
                    distribuidor          ,
                    coleta_postagem       ,
                    data_postagem
                )VALUES(
                    '$setor'                ,
                    $situacao_atendimento   ,
                    $os                     ,
                    $login_admin            ,
                    '$observacao_pedido'    ,
                    $causa_troca            ,
                    $gerar_pedido           ,
                    FALSE                   ,
                    TRUE                    ,
                    $envio_consumidor       ,
                    '$modalidade_transporte',
                    '$ri'                   ,
                    $login_fabrica          ,
                    $fabrica_distribuidor   ,
                    '$coleta_postagem'      ,
                    $xdata_postagem
                )";
        #echo "2<br />".nl2br($sql);exit;
 $res = @pg_query($con,$sql);
 $msg_erro .= pg_errormessage($con);

 $sql = "
 INSERT INTO tbl_comunicado (
    descricao              ,
    mensagem               ,
    tipo                   ,
    fabrica                ,
    obrigatorio_os_produto ,
    obrigatorio_site       ,
    posto                  ,
    ativo
    ) VALUES (
    'OS $sua_os - AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA',
    'A Fábrica autorizou a fazer a devolução de venda do produto relativo à OS $sua_os. A Telecontrol coletará este produto no seu posto.',
    'AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA',
    $login_fabrica,
    'f' ,
    't',
    $posto,
    't'
    );";
 $res = pg_query($con,$sql);
 $msg_erro .= pg_errormessage($con);

}else{

    if (empty($msg_erro)) {

        $queryOsProduto = "SELECT os_produto FROM tbl_os_produto where os = $os";
        
        $resOsProduto = pg_query($con, $queryOsProduto);

        $os_produto = pg_fetch_result($resOsProduto, 0, os_produto);
       
        if ($login_fabrica<>6 && pg_num_rows($resOsProduto) == 0) {
            $sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT CURRVAL ('seq_os_produto')";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            $os_produto = pg_fetch_result($res,0,0);
        }
        if(in_array($login_fabrica,array(6))){
            $cond = "AND tbl_servico_realizado.gera_pedido ";
        }
        else if ($fabrica_gerencia_telecontrol or $telecontrol_distrib) {
            $cond = "AND (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto) ";
        }
        else {
            $cond = "AND tbl_servico_realizado.troca_de_peca";
        }

        $sql = "
        SELECT *
        FROM   tbl_os_item
        JOIN   tbl_servico_realizado USING (servico_realizado)
        JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
        WHERE  tbl_os_produto.os = $os
        $cond
        AND    tbl_os_item.pedido IS NOT NULL " ;
        $res = pg_query($con,$sql);

        $msg_erro .= pg_errormessage($con);

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

                    if ($fabrica_usa_distrib_telecontrol) {#HD52537 alterado apenas para a Gama pois não sei se as outras fábricas atualizam o pedido_item
                        $sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item AND tbl_os_item.peca = tbl_pedido_item.peca ";
                    }else{
                        $sql .= " JOIN tbl_os_item     ON tbl_os_item.pedido        = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca ";
                    }
                    $sql .= " JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    WHERE tbl_pedido.pedido       = $pedido
                    AND   tbl_peca.fabrica        = $login_fabrica
                    AND   tbl_os_produto.os       = $os
                    AND   tbl_pedido_item.peca    = $pecaxx";

                    if($fabrica_usa_distrib_telecontrol){
                        $sql .= " AND   tbl_pedido.distribuidor = 4311 ";
                    }


                    #HD 311414
                    if($login_fabrica == 6){
                        //$sql .= " AND tbl_pedido.exportado IS NOT NULL ";
                    }

                    if(strlen($msg_erro) == 0){
                        $res_dis = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                    if (@pg_num_rows($res_dis) > 0 AND empty($msg_erro)) {
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

                            if(strlen($msg_erro) == 0){

                                $sql = "
                                SELECT DISTINCT tbl_embarque.embarque
                                FROM tbl_embarque
                                JOIN tbl_embarque_item USING(embarque)
                                WHERE pedido_item = $pedido_item
                                AND   os_item     = $pedido_os_item
                                AND   faturar IS NOT NULL";

                                $res_x1 = pg_query($con,$sql);
                                $tem_faturamento = pg_num_rows($res_x1);
                                if($tem_faturamento>0) {
                                    $troca_distribuidor = "TRUE";
                                    $troca_faturado     = "TRUE";
                                }

                                $pecas_canceladas .= "$pedido_peca_referencia - $pedido_peca_descricao ($pedido_qtde UN.),";

                                $distrib = ($fabrica_usa_distrib_telecontrol) ? '4311':'NULL';

                                $pedido_cancela_garantia = true;

                                if ($fabrica_usa_distrib_telecontrol) {
                                    $sql_embarque = "SELECT embarque
                                        FROM tbl_os_produto
                                        JOIN tbl_os_item USING(os_produto)
                                        JOIN tbl_embarque_item USING(os_item)
                                        WHERE tbl_os_produto.os = $os
                                        AND   tbl_os_item.pedido IS NOT NULL
                                        AND (
                                            tbl_embarque_item.liberado IS NOT NULL
                                            OR tbl_embarque_item.impresso IS NOT NULL
                                        )";
                                    $res_embarque = pg_query($con, $sql_embarque);

                                    if (pg_num_rows($res_embarque) > 0) {
                                        $pedido_cancela_garantia = false;
                                    }
                                }

                                if (false === $pedido_cancela_garantia) {
                                    $sql_audit_0 = "SELECT tbl_auditoria_os.auditoria_status
                                        FROM tbl_auditoria_os
                                        INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                                        WHERE os = $os
                                        AND tbl_auditoria_os.observacao = 'OS em intervenção da fábrica por Troca de Produto'
                                        AND cancelada IS NULL
                                        ORDER BY data_input DESC";
                                    $res_audit_0 = pg_query($con, $sql_audit_0);

                                    if (pg_num_rows($res_audit_0) == 0) {
                                        $sql_audit = "INSERT INTO tbl_auditoria_os (
                                                os,
                                                auditoria_status,
                                                observacao
                                            ) VALUES (
                                                $os,
                                                3,
                                                'OS em intervenção da fábrica por Troca de Produto'
                                            )";
                                        $res_audit = pg_query($con, $sql_audit);
                                    }
                                } else {
                                //HD 340425: Para a Lenoxx pedidos dos itens de uma OS que foi trocada são cancelados pelo integrador em Delphi.
                                    $sql2 = "SELECT fn_pedido_cancela_garantia($distrib,$login_fabrica,$pedido_pedido,$pedido_peca,$pedido_os_item,'Troca de Produto',$login_admin); ";
                                    $res_x2 = pg_query($con,$sql2);
                                    $msg_erro .= pg_errormessage($con);

                                    $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
                                    $destinatario = "suporte@telecontrol.com.br,";

                                    $assunto      = "Troca - Cancelamento de Pedido de Peça do Fabricante";
                                    $mensagem     = "$os trocada";
                                    $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                                    //Samuel tirou em 27/02/2009
                                    //mail($destinatario,$assunto,$mensagem,$headers);
                                    //Cancela a peça que ainda não teve o seu pedido exportado //Raphael Giovanini
                                    if(strlen($msg_erro) == 0){
                                        $sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + $qtde
                                        FROM tbl_pedido
                                        WHERE tbl_pedido_item.pedido      = $pedido
                                        AND   pedido_item = $pedido_item
                                        AND   peca        = $pedido_peca
                                        AND   tbl_pedido_item.pedido = tbl_pedido.pedido
                                        AND   tbl_pedido.exportado IS NULL ;";
                                        $res3 = @pg_query($con,$sql);
                                        $msg_erro .= pg_errormessage($con);
                                    }
                                }

                                if(strlen($msg_erro) > 0){
                                    continue;
                                }
                            }

                        }
                    }
                }

            }


            if ($login_fabrica == 95) { // HD 684671

                $sql = "UPDATE tbl_os SET finalizada = null, data_fechamento = null WHERE os = $os;
                UPDATE tbl_os_item
                SET servico_realizado = (select servico_realizado
                   from tbl_servico_realizado
                   where ativo
                   and gera_pedido IS NOT TRUE
                   and fabrica = $login_fabrica
                   and troca_de_peca IS NOT TRUE
                   AND troca_produto IS NOT TRUE)
                 FROM tbl_os, tbl_os_produto
                 WHERE tbl_os.os = $os
                 AND tbl_os.fabrica = $login_fabrica
                 AND tbl_os.finalizada IS NULL
                 AND tbl_os.os = tbl_os_produto.os
                 AND tbl_os_produto.os_produto = tbl_os_item.os_produto";

                 $res = pg_query($con,$sql);

            }
            // HD 132249
            if($login_fabrica == 35) {
                $sql="UPDATE tbl_os_item
                SET servico_realizado = 738
                WHERE os_item IN (
                    SELECT os_item
                    FROM tbl_os
                    JOIN tbl_os_produto USING(os)
                    JOIN tbl_os_item USING(os_produto)
                    JOIN tbl_peca USING(peca)
                    WHERE tbl_os.os       = $os
                    AND tbl_os.fabrica    = $login_fabrica
                    )";
             $res = pg_query($con,$sql);
             $msg_erro .= pg_errormessage($con);
            }

            if(strlen($msg_erro) == 0){
                $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
                if(pg_num_rows($res) > 0){
                    $servico_realizado = pg_fetch_result($res,0,0);
                }
                if ($login_fabrica <> 6){
                    if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!<br>";
                }

                if ($login_fabrica == 24) {
                    $aguardando_peca_reparo = 't';
                } else {
                    $aguardando_peca_reparo = 'f';
                }

                $quantidade_item = (int) $_POST["quantidade_item"];


                if($quantidade_item <= 0 && $_POST['radio_qtde_produtos'] != 'muitos') {
                    if($login_fabrica == 81) {
                        $msg_erro .= "Quantidade de item deve ser maior que 0<br/>";
                    }

                }
            }

            if(strlen($msg_erro)==0){

                            //print_r($_POST);die;
                if ($login_fabrica <> 6){

                    $multiplo = $_POST['radio_qtde_produtos'];

                    if($fabrica_gerencia_telecontrol AND $multiplo=='muitos') {

                        $varios_produtos = $_POST['PickList'];

                        if (count($varios_produtos)){

                            $lista_produtos = array();

                            for($k = 0; $k < count($varios_produtos); $k++) {

                                $varios_produtos[$k] = str_replace("\\", '', $varios_produtos[$k]);
								$varios_produtos[$k] = str_replace("'", '"', $varios_produtos[$k]);
								print_r($varios_produtos[$k]);

                                $varios_produtos[$k] = json_decode(utf8_encode($varios_produtos[$k]));
								echo $varios_produtos[$k]->value;
                                $sqlP = "   SELECT tbl_peca.peca,tbl_produto.referencia,tbl_produto.descricao,devolucao_obrigatoria
                                FROM tbl_peca join tbl_produto ON tbl_peca.referencia   = tbl_produto.referencia
                                AND tbl_produto.fabrica_i = $login_fabrica
                                WHERE tbl_peca.fabrica  = $login_fabrica
                                AND tbl_produto.produto = {$varios_produtos[$k]->value}
                                AND tbl_peca.produto_acabado IS TRUE";

                                $resP = pg_query($con,$sqlP);

                                if(pg_num_rows($resP) > 0){

                                    $pecaP           = pg_result($resP,0,'peca');
                                    $mult_referencia = pg_result($resP,0,'referencia');
                                    $mult_descricao  = pg_result($resP,0,'descricao');
                                    $devolucao_obrigatoria  = pg_result($resP,0,'devolucao_obrigatoria');
                                    $devolucao_obrigatoria = empty($devolucao_obrigatoria) ?'f':$devolucao_obrigatoria;

                                    array_push($lista_produtos, array($varios_produtos[$k], $mult_referencia, $mult_descricao));

                                    $sql_peca2 = "  SELECT tbl_tabela_item.preco
                                    FROM tbl_tabela_item
                                    JOIN tbl_tabela ON tbl_tabela_item.tabela = tbl_tabela.tabela
                                    WHERE tbl_tabela_item.peca = $pecaP
                                    AND   tbl_tabela.fabrica   = $login_fabrica";

                                    $res2 = pg_query($con,$sql_peca2);

                                    if (pg_num_rows($res2) == 0) {
                                        $msg_erro = "O produto $mult_referencia não tem preço na tabela de preço. Cadastre o preço para poder dar continuidade na troca. ";
                                    } else {
                                        $sql       = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin,aguardando_peca_reparo,peca_obrigatoria) VALUES ($os_produto, $pecaP, " . ($login_fabrica == 81 ? $varios_produtos[$k]->quantidade : 1) . ",$servico_realizado, $login_admin,'$aguardando_peca_reparo','$devolucao_obrigatoria')";
                                        $res       = pg_query($con,$sql);
                                        $msg_erro .= pg_errormessage($con);
                                    }
                                }
                            }


                        } else {
                            $msg_erro .= "Selecione um produto para troca";
                        }
                    } else {
                      $sqlP = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE peca = $peca";
                      $resP = pg_query($con,$sqlP);
                      $devolucao_obrigatoria = pg_fetch_result($resP,0,0);
                      $devolucao_obrigatoria = empty($devolucao_obrigatoria) ?'f':$devolucao_obrigatoria;
                      $sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin,aguardando_peca_reparo,peca_obrigatoria) VALUES ($os_produto, $peca, " . (in_array($login_fabrica,array(81,155)) ? $quantidade_item : 1) . ",$servico_realizado, $login_admin,'$aguardando_peca_reparo','$devolucao_obrigatoria')";

                      $res = pg_query($con,$sql);

                      $msg_erro .= pg_errormessage($con);
                  }
              }

              $sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
              $res = pg_query($con,$sql);
              $msg_erro .= pg_errormessage($con);

            if (in_array($login_fabrica, array(1, 3, 25, 35, 45)) and pg_num_rows($res)==1) {
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
                } else if (in_array($login_fabrica, array(6, 11, 35, 72, 81, 155, 114, 172))) {
                                    //HD 65952
                                    //HD 163061
                                    //HD 227564: Para a Salton a OS não deve ser fechada na troca
                                    //HD 324225
                    $sql = "UPDATE tbl_os SET
                    troca_garantia          = 't',
                    ressarcimento           = 'f',
                    troca_garantia_admin    = $login_admin
                    WHERE os = $os AND fabrica = $login_fabrica";
                } else {
                    $sql = "UPDATE tbl_os SET
                    troca_garantia          = 't',
                    ressarcimento           = 'f',
                    troca_garantia_admin    = $login_admin ";
                    if(!in_array($login_fabrica,array(15,24,30,91,101,123,131,141,144))){

                        $sql .= "
                        ,data_fechamento         = CURRENT_DATE,
                        finalizada = CURRENT_TIMESTAMP";
                    }
                    $sql .= "
                    WHERE os = $os AND fabrica = $login_fabrica";
                }
            }

               $res = @pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            $sql = "UPDATE tbl_os_extra
            SET obs_nf = '$observacao_pedido'
            WHERE os     = $os;";

            $res = @pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);


            if(strlen($troca_garantia_mao_obra) > 0 ){
                $sql = "UPDATE tbl_os SET mao_de_obra = $troca_garantia_mao_obra WHERE os = $os AND fabrica = $login_fabrica";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }

            $sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
            $res = @pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            //--== Novo Procedimento para Troca | Raphael Giovanini ===========

            if( strlen($_POST["causa_troca"])          == 0 ) $msg_erro .= "Escolha a causa da troca<br>";
            else                                              $causa_troca = $_POST["causa_troca"];
            if($login_fabrica != 30){
                if( strlen($_POST["setor"])                == 0 ) $msg_erro .= "Selecione o setor responsável<br>";
                else                                              $setor = $_POST["setor"];
            }
            if(!in_array($login_fabrica,array(6,30,51,81,114))){
                if( strlen($_POST["situacao_atendimento"]) == 0 ) $msg_erro .= "<br>Selecione a situação do atendimento";
                else                                              $situacao_atendimento = $_POST["situacao_atendimento"];
            }else{
                $situacao_atendimento = 'null';
            }
            if($login_fabrica != 30){
                $gerar_pedido     = ( strlen($_POST["gerar_pedido"])         == 0 ) ? "'f'" : "'t'";
            }
            $envio_consumidor = ($_POST["envio_consumidor"]=='t') ? " 't' " : " 'f' ";

            if($fabrica_usa_distrib_telecontrol){
                if(strlen($_POST["fabrica_distribuidor"]) == 0) {
                    $msg_erro .= "<br>Atender via Distribuidor ou Fabricante?";
                }else{
                    $fabrica_distribuidor = $_POST["fabrica_distribuidor"];
                    $fabrica_distribuidor = ($fabrica_distribuidor == 'distribuidor') ? '4311' : 'null';
                }
            }else{
                $fabrica_distribuidor = 'null';
            }
//COMENTADO PARA TESTES, POIS OS IDS ESTÃO DIFERENTES. EM PRODUÇÃO TEM QUE DESCOMENTAR
	/*
            if($login_fabrica == 30){
                if(strlen($laudo) > 0 && $laudo != "fats" && $causa_troca == 351){
                    $msg_erro .= "Para essa causa de troca específica, escolha o laudo de Sinistro";
                }else if(strlen($laudo) > 0 && $laudo == "fats" && $causa_troca != 351){
                    $msg_erro .= "Para o laudo de Sinistro, escolha a causa da troca Sinistro";
                }
            }
*/
            $ri = $_POST["ri"];

            if (( $setor=='Procon' OR $setor=='SAP' OR $setor=='Jurídico' ) AND(strlen($ri)=="null"))
                $msg_erro .= "<br>Obrigatório o preenchimento do RI";

            if( strlen($_POST["ri"])                   == 0 ) $ri = "null";
            else                                              $ri = "'".$_POST["ri"]."'";

            $modalidade_transporte = $_POST["modalidade_transporte"];
            if(strlen($modalidade_transporte)==0)$xmodalidade_transporte = "''";
            if(in_array($login_fabrica, array(3, 81, 155, 114))){
                if(strlen($modalidade_transporte)==0) $msg_erro .= "É obrigatório a escolha da modalidade de transporte<br>";
                else $xmodalidade_transporte = "'$modalidade_transporte'";
            }


            if($login_fabrica==3){
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
                                fabric                ,
                                modalidade_transporte ,
                                distribuidor
                            )VALUES(
                                '$setor'                 ,
                                $situacao_atendimento    ,
                                $os                      ,
                                $login_admin             ,
                                $peca                    ,
                                '$observacao_pedido'     ,
                                $causa_troca             ,
                                $gerar_pedido            ,
                                $envio_consumidor        ,
                                $ri                      ,
                                $login_fabrica           ,
                                $xmodalidade_transporte  ,
                                $fabrica_distribuidor
                            )";
                    $res = @pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }else{
                if ($fabrica_gerencia_telecontrol AND $multiplo == "muitos") {
                    $peca = 'null';
                }
                if($login_fabrica == 138 && empty($msg_erro)){
                    $produto_troca = $_REQUEST['produto_troca'];
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
                                fabric                ,
                                modalidade_transporte ,
                                distribuidor          ,
                                coleta_postagem       ,
                                data_postagem         ,
                                produto
                            )VALUES(
                                '$setor'                 ,
                                $situacao_atendimento    ,
                                $os                      ,
                                $login_admin             ,
                                $peca                    ,
                                '$observacao_pedido'     ,
                                $causa_troca             ,
                                $gerar_pedido            ,
                                $envio_consumidor        ,
                                $ri                      ,
                                $login_fabrica           ,
                                $xmodalidade_transporte  ,
                                $fabrica_distribuidor    ,
                                '$coleta_postagem'       ,
                                $xdata_postagem          ,
                                $produto_troca
                            )";
                    $res = @pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
                else if(strlen($msg_erro) == 0 ){
					$sql = "DELETE FROM tbl_os_troca WHERE os = $os ; 
								INSERT INTO tbl_os_troca (
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
                                fabric                ,
                                modalidade_transporte ,
                                distribuidor          ,
                                coleta_postagem       ,
                                data_postagem
                            )VALUES(
                                '$setor'                 ,
                                $situacao_atendimento    ,
                                $os                      ,
                                $login_admin             ,
                                $peca                    ,
                                '$observacao_pedido'     ,
                                $causa_troca             ,
                                $gerar_pedido            ,
                                $envio_consumidor        ,
                                $ri                      ,
                                $login_fabrica           ,
                                $xmodalidade_transporte  ,
                                $fabrica_distribuidor    ,
                                '$coleta_postagem'       ,
                                $xdata_postagem
                            )";
                           # echo "3".$sql;exit;
                    $res = @pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }

            }

            /*HD - 6047953*/
            if ($login_fabrica == 30) {
                $hd_classificacao = $_POST["classificacao_atendimento"];

                $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                $res = pg_query($con, $sql);
                $val = pg_fetch_result($res, 0, 'campos_adicionais');

                if (pg_num_rows($res) == 0) {
                    $aux_array = json_encode(array("hd_classificacao" => $hd_classificacao));

                    pg_query($con, "BEGIN");

                    $sql = "INSERT INTO tbl_os_campo_extra(os, fabrica, campos_adicionais)
                            VALUES ($os, $login_fabrica, '$aux_array')";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro .= "Erro ao salvar a classificação do atendimento.<br>";
                        pg_query($con, "ROLLBACK");
                    } else {
                        pg_query($con, "COMMIT");
                    }
                } else {
                    $campos_adicionais = json_decode($val, true);
                    $campos_adicionais["hd_classificacao"] = $hd_classificacao;
                    $campos_adicionais                     = json_encode($campos_adicionais);

                    pg_query($con, "BEGIN");

                    $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os AND fabrica = $login_fabrica";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro .= "Erro ao salvar a classificação do atendimento.<br>";
                        pg_query($con, "ROLLBACK");
                    } else {
                        pg_query($con, "COMMIT");
                    }
                }
            }

            if(strlen($msg_erro) == 0 ){
                if ($login_fabrica==25){
                    $sql = "SELECT fn_pedido_troca($os,$login_fabrica)";
                    $res = @pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }

            if($login_fabrica == 24){
                $sql = "INSERT INTO tbl_auditoria_os(os, auditoria_status, observacao)
				        VALUES($os, 3, 'PRODUTOS TROCADOS NA OS')";

				$res = pg_query($con, $sql);
            }
            # HD 11631
            # HD 390696 - Gabriel Silveira - Adicionando Gamma Italy para a regra
            if (in_array($login_fabrica, array(3, 24, 51,81, 155,114)) AND strlen($msg_erro) == 0) {

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
                'OS $sua_os - Troca de Produto',
                'A Fábrica irá fazer a troca do produto da OS $sua_os',
                'OS Troca de Produto',
                $login_fabrica,
                'f' ,
                't',
                $posto,
                't'
                );";
  
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }

            #HD 311414 - INICIO
            if (($login_fabrica==6) AND strlen($msg_erro)==0){
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
                'OS $sua_os - Troca de Produto',
                'A Fábrica irá efetuar a troca do produto, solicitamos para o Posto Autorizado <br /> emitir Nota Fiscal com natureza de operação de Remessa para Conserto <br /> e enviar preferêncialmente por e-mail ou pelo fax 11 3018-8055, caso o produto <br />esteja com acessório(s) faltante(s), solicitamos para o Posto Autorizado, <br />solicitar para o cliente os acessórios, para posterior envio da Nota Fiscal.',
                'OS Troca de Produto',
                $login_fabrica,
                'f' ,
                't',
                $posto,
                't'
                );";
             $res = pg_query($con,$sql);
             $msg_erro .= pg_errormessage($con);
            }
                #HD 311414 _ FIM
        }
    }

    if (strlen ($msg_erro) == 0) {
        if (!in_array($login_fabrica, array(3, 6, 11, 15, 24, 30, 35, 72, 81, 86, 91, 95, 101, 114, 123,131, 141, 143, 144, 155, 172))) {
                    // HD 18558 - OS troca não pode ser finalizada.
                    // HD 65952 - incluída a Cadence
                    // HD 163061 - Incluido Lenoxx
                    // HD 324225 - Incluí a Mallory
            $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";

            $res = @pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);
        }
    }
    if ($telecontrol_distrib && !isset($novaTelaOs)) {
        if (!in_array($login_fabrica, [11,172]) && $_POST['fabrica_distribuidor'] == 'fabrica') {
            atualiza_status_checkpoint($os, "Aguardando Conserto");

            if (in_array($login_fabrica, [123])) {
                if (empty($consumidor_celular)) {
                    $sql = "SELECT consumidor_celular FROM tbl_os WHERE fabrica = $login_fabrica AND os = ".$_POST["os"];
                    $res = pg_query($con, $sql);
                    if (pg_num_rows($res) > 0) {
                        $consumidor_celular = pg_fetch_result($res, 0, 'consumidor_celular');
                    }
                }

                $helper = new \Posvenda\Helpers\Os();

                $sql_posto = "SELECT nome FROM tbl_posto WHERE posto = $posto";
                $qry_posto = pg_query($con, $sql_posto);
                $nome_posto = pg_fetch_result($qry_posto, 0, 'nome');
                
                $consumidor_nome = trim($consumidor_nome);
                $primeiro_nome = explode(" ", $consumidor_nome);

                $msg_abertura_os = "Olá $primeiro_nome[0] ! Ordem de Serviço $os registrada para seu produto " . str_replace("'", "", $produto_referencia) . "\n Equipe Positec ( WESCO / WORX ).";

                if (!empty($consumidor_celular)) {
                    $helper->comunicaConsumidor($consumidor_celular, $msg_abertura_os, $login_fabrica, $os);
                }
            }
        } else {
            atualiza_status_checkpoint($os, "Produto Trocado");
        }
    }
}

    if (strlen ($msg_erro) == 0) {
        if($login_fabrica == 11 or $login_fabrica == 172) { # HD 175656
            if(strlen($os_fechada) > 0) {
                $sql = " UPDATE tbl_os SET data_fechamento = CURRENT_DATE WHERE os = $os_fechada ";
                $res = pg_query($con,$sql);
                $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";

                $res = @pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }
        }
        // HD 38420
        if($login_fabrica == 3) {
            if (strlen ($msg_erro) == 0) {
                if($causa_troca == 1 or $causa_troca== 7 or $causa_troca==32) {
                    $sql_ot = "SELECT COUNT(originou_troca) as qtde_troca
                    FROM  tbl_os_troca
                    JOIN  tbl_os_produto USING (os)
                    JOIN  tbl_os_item    USING (os_produto)
                    WHERE tbl_os_troca.os          = $os
                    AND   tbl_os_troca.fabric      = $login_fabrica
                    AND   tbl_os_troca.causa_troca = $causa_troca
                    AND   tbl_os_item.originou_troca IS TRUE";
                    $res_ot=@pg_query($con,$sql_ot);
                    if(@pg_num_rows($res_ot) > 0){
                        $qtde_troca=pg_fetch_result($res_ot,0,qtde_troca);
                        if($qtde_troca == 0){
                            $msg_erro .= "Para essa causa, deve informar a peça que gerou a troca.<br>";
                        }
                    }
                }
            }
        }

        if($login_fabrica == 72 and $gerar_pedido == "'t'" and $troca_com_nota == 'sem_nota_com_troca'){

            $sql = "SELECT tbl_os_item.peca
            FROM tbl_os
            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
            JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
            JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.peca = tbl_os_item.peca
            WHERE tbl_os.os = $os
            AND tbl_os.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) == 0){
                $msg_erro = "O item não foi inserido na OS, tente novamente";
            }
        }
    }

    if(empty($msg_erro) && $login_fabrica == 143){
        $sql_update_extrato = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res_update_extrato = pg_query($con, $sql_update_extrato);
    }

    if (!strlen($msg_erro) && in_array($login_fabrica, array(101,141,144)) && $troca_garantia_produto != -1) {
        $insert = "INSERT INTO tbl_os_status
                   (os, status_os, observacao, admin)
                   VALUES
                   ({$os}, 202, 'Troca de produto confirmada pela fábrica', {$login_admin})";
        $res = pg_query($con, $insert);

        if (strlen(pg_last_error()) > 0) {
            $msg_erro = "Erro ao trocar produto";
        } else {
            $sqlStatus = "SELECT fn_os_status_checkpoint_os({$os}) AS status;";
            $resStatus = pg_query($con, $sqlStatus);

            $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

            $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $resStatus = pg_query($con, $updateStatus);

            if (strlen(pg_last_error()) > 0) {
                $msg_erro = "Erro ao trocar produto";
            }
        }
    }

    if(strlen($msg_erro) == 0){
        if($login_fabrica == 3){ //hd_chamado=2705984
            $sql_ins = "INSERT INTO tbl_serie_controle(
                    fabrica, produto, serie, quantidade_produzida, motivo
                )VALUES(
                    $login_fabrica,$produto,'$serie_produto', 1, 'Produto trocado na OS {$sua_os}'
                )";
            $res_ins = pg_query($con, $sql_ins);

            if(pg_last_error($con)){
                $msg_erro .= "Erro ao cadastrar série controle - Entre em contato com suporte.";
            }
        }
    }

    if (strlen($msg_erro) == 0) {

        $res = pg_query($con,"COMMIT TRANSACTION");

        if($login_fabrica == 3 AND isset($troca_distribuidor)){
            $sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
            $res = @pg_query($con,$sql);
            $pr_sua_os = @pg_fetch_result($res,0,0);

            $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
            $destinatario = "ronaldo@telecontrol.com.br";
            $destinatario2 = "suporte@telecontrol.com.br";
            $assunto      = "Troca - Cancelamento de Pedido de Peça do Distribuidor";
            if($troca_faturado<>'TRUE'){
                $mensagem_distribuidor =  "At. Responsável,<br><br>O produto $pr_referencia - $pr_descricao da <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os=$os'>OS $pr_sua_os</a> foi trocado pelo produto $troca_referencia - $troca_descricao
                <br>A(s) peça(s) $pecas_canceladas do pedido $pedido foram canceladas automaticamente pelo sistema de Troca<br>
                <br><br>Telecontrol Networking";
            }else{
                $mensagem_distribuidor =  "At. Responsável,<br><br>O produto $pr_referencia - $pr_descricao da <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os=$os'>OS $pr_sua_os</a> foi trocado pelo produto $troca_referencia - $troca_descricao
                <br>A(s) peça(s) $pecas_canceladas do pedido $pedido  não foram canceladas automaticamente pelo sistema de Troca, porque já foram enviadas para o posto<br>
                <br><br>Telecontrol Networking";

            }

            $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
            if(strlen($mensagem_distribuidor)>0) mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem_distribuidor), $headers);
            if(strlen($mensagem_distribuidor)>0) mail($destinatario2, utf8_encode($assunto), utf8_encode($mensagem_distribuidor), $headers);
        }

        if ($login_fabrica == 24) {

            $sql_email = "SELECT email, nome from tbl_posto where posto = $posto";
            $res_email = @pg_query($con, $sql_mail);

            if (@pg_num_rows($res_email) > 0) {

                $email_posto = trim(pg_fetch_result($res_email,0,email));
                $xposto_nome = pg_fetch_result($res_email,0,nome);

                if (strlen($email_posto) > 0) {
                    $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
                    $destinatario = $email_posto;
                    $assunto      = "O fabricante Suggar abriu uma ordem de serviço para seu posto autorizado";
                    $mensagem     = "Caro posto autorizado $xposto_nome,<BR>
                    O fabricante Suggar abriu a ordem de serviço número $os para seu posto autorizado, por favor verificar.<BR><BR>
                    Atenciosamente<BR> Dep. Assistência Técnica Suggar";
                    $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

                    mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

                }

            }

        }

        # HD 390696 - Gabriel Silveira - Gamma Italy Irá também enviar email de troca
        if($login_fabrica==51){

            $sql_email = "SELECT    tbl_os.sua_os,
            tbl_posto.nome,
            contato_email
            FROM tbl_os
            JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto
            JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE tbl_os.os = $os
            AND tbl_posto_fabrica.fabrica = $login_fabrica";

            $res_email = pg_query($con,$sql_email);

            if(pg_num_rows($res_email)>0){

                $email_posto = trim(pg_fetch_result($res_email,0,contato_email));
                $tposto_nome = pg_fetch_result($res_email,0,nome);
                $sua_os      = pg_fetch_result($res_email,0,sua_os);
                if(strlen($email_posto)>0){
                    $remetente    = "Telecontrol <suporte@telecontrol.com.br>";
                    $destinatario = $email_posto;
                    $assunto      = "Troca/reembolso OS: $sua_os - Gama Italy" ;
                    $mensagem     = "MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL<BR><BR>
                    Prezado posto autorizado $tposto_nome,<BR><BR>
                    Foi inserida uma ocorrência troca/reembolso na OS: $sua_os.<BR><BR>
                    Favor verificar.<BR><BR>";
                    $headers="Return-Path: <suporte@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

                    mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

                }

            }
        }

        # HD 54581
        if($login_fabrica==45){
            $sql_email = "SELECT tbl_os.sua_os                 ,
            tbl_posto.nome                 ,
            tbl_posto_fabrica.contato_email
            FROM tbl_os
            JOIN tbl_posto USING(posto)
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_os.os = $os";
            $res_email = pg_query($con,$sql_email);
            if(pg_num_rows($res_email)>0){
                $email_posto = trim(pg_fetch_result($res_email,0,contato_email));
                $tposto_nome = pg_fetch_result($res_email,0,nome);
                $sua_os      = pg_fetch_result($res_email,0,sua_os);
                if(strlen($email_posto)>0){
                    $remetente    = "Telecontrol <suporte@telecontrol.com.br>";
                    $destinatario = $email_posto;
                    $assunto      = "Troca/reembolso OS: $sua_os - NKS";
                    $mensagem     = "MENSAGEM AUTOMÁTICA - NÃO RESPONDER A ESTE EMAIL<BR><BR>
                    Prezado posto autorizado $tposto_nome,<BR><BR>
                    Foi inserida uma ocorrência troca/reembolso na OS $os.<BR><BR>
                    Favor verificar.<BR><BR>";
                    $headers="Return-Path: <suporte@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

                    mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

                }
            }
        }


		if(strlen($msg_erro) == 0){
			if (strlen($pedido) > 0 ){
				$sql= "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido)";
				$res = pg_exec($con,$sql);
			}
        }

        if ($login_fabrica == 141 && $troca_garantia_produto == -1) {
            header("Location: $PHP_SELF?os=$os&ok=s&osacao=trocar&s=s&ressarcimento=true");
        } else {
            header("Location: $PHP_SELF?os=$os&ok=s&osacao=trocar&s=s");
        }

        /**
         * - ESMALTEC: Direciona para o cadastro de laudo
         */
        if($login_fabrica == 30 && strlen($laudo) > 0){
            header("Location: cadastro_laudo_troca.php?os=$os&laudo=$laudo&familia_troca=$familia_troca");
        }
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_bairro"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_cpf"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_cep"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["consumidor_celular"]["obrigatorio"] = 0;

$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_bairro"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_cep"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_cnpj"]["obrigatorio"] = 0;
$campos_telecontrol[$login_fabrica]["tbl_os"]["revenda_fone"]["obrigatorio"] = 0;

/*======= <PHP> FUNCOES DOS BOTOES DE ACAO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar") {
    $msg_erro = "";

    $garantia_lorenzetti = $_POST['garantia_lorenzetti'];

    if(in_array($login_fabrica,$fabricas_validam_campos_telecontrol) || $login_fabrica > 99){
        $msg_erro .= validaCamposOs($campos_telecontrol[$login_fabrica]['tbl_os'], $_POST,$login_fabrica);

    }

    $imprimir_os = $_POST["imprimir_os"];

    if ($login_fabrica == 30) {
         
        $idPosto = $_POST['id_posto'];

		if(!empty($idPosto)) {
			$sqlCredenciamento = "SELECT posto, credenciamento
								  FROM tbl_posto_fabrica 
								  WHERE posto = $idPosto
								  AND fabrica = $login_fabrica";
			
			$resCredenciamento = pg_query($con, $sqlCredenciamento);

			$statusCredenciamento = pg_fetch_result($resCredenciamento, 0, credenciamento); 

			if ($statusCredenciamento != "CREDENCIADO") {
				$msg_erro .= " Posto autorizado selecionado está “Em Descredenciamento”.<br>"; 
			}
		}
    }
    
    if (strlen (trim ($sua_os)) == 0 && !in_array($login_fabrica, array(101,104,105,87,114,115,116,117,120,121,122,123,124,126,127,128,129,134,131,132,136,137,139,140,141,144)) ) {
        $sua_os = 'null';
        if ($pedir_sua_os == 't') {
            $msg_erro .= " Digite o número da OS Fabricante.<br>";
        }
    }else{
        $expSua_os = explode("-",$sua_os);
        $sua_os = "'" . $sua_os . "'" ;
    }

    // explode a sua_os
    $fOsRevenda = 0;
    $sql = "SELECT sua_os
    FROM   tbl_os_revenda
    WHERE  sua_os = '$expSua_os[0]'
    AND    fabrica      = $login_fabrica";

    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) != 0) {
        $fOsRevenda = 1;
    }
    $data_nf =trim($_POST['data_nf']);

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

    if (in_array($login_fabrica, array(11, 172))) {
        $cod_os = $_POST['cod_os'];
    }


    if($login_fabrica == 35){
        $motivo_cancela_mao_obra    = $_POST['motivo_cancela_mao_obra'];
        $protocolo_cancela_mao_obra = $_POST['protocolo_cancela_mao_obra'];
        if(strlen($cancela_mao_obra) > 0){
            if(strlen($motivo_cancela_mao_obra) == 0){
                $msg_erro .= "Preencher o motivo do cancelamento da mão de obra da OS.<br/>";
            }

            if(strlen($protocolo_cancela_mao_obra) == 0){
                $msg_erro .= "Preencher o protocolo do cancelamento da mão de obra da OS.<br/>";
            }
        }
    }

    if (strlen($msg_erro) == 0){
        #------------ Atualiza Dados do Consumidor ----------
        $cidade = strtoupper(trim($_POST['consumidor_cidade']));
        $estado = strtoupper(trim($_POST['consumidor_estado']));
        $nome   = trim ($_POST['consumidor_nome']) ;

        if (strtoupper(trim($_POST['consumidor_revenda'])) == 'C' and $login_fabrica != 86) {

            if (strlen($estado) == 0 AND $login_fabrica != 7 AND $login_fabrica != 20) {
                $msg_erro .= " Digite o estado do consumidor. <br>";

            }else{
                $estado = ' NULL ' ;
            }

            if (strlen($cidade) == 0 AND $login_fabrica != 7 AND $login_fabrica != 20) {
                $msg_erro .= " Digite a cidade do consumidor. <br>";
            }else{
                $cidade = ' NULL ' ;
            }

            if (strlen($nome) == 0 AND $login_fabrica != 7)   {
                $msg_erro .= " Digite o nome do consumidor. <br>";
            }else{
                $nome = ' NULL ' ;
            }


            if (in_array($login_fabrica, array(3, 30, 72))) {
                if (empty($_POST['consumidor_cpf'])) {
                    $msg_erro .= 'Digite o CPF do consumidor.<br>';
                }
            }
            if ($login_fabrica == 72) {
                if (empty($_POST['consumidor_fone'])) {
                    $msg_erro .= 'Digite o telefone do consumidor.<br>';
                }
            }

            if ($login_fabrica == 74) {
                if (empty($_POST['consumidor_cpf'])) {
                    $msg_erro .= 'Digite o CPF do consumidor.<br>';
                }

                if (empty($_POST['consumidor_fone'])) {
                    $msg_erro .= 'Digite o telefone do consumidor.<br>';
                }

                if (empty($_POST['consumidor_endereco']) ||
                    empty($_POST['consumidor_numero']) ||
                    empty($_POST['consumidor_bairro']) ||
                    empty($_POST['consumidor_cidade']) ||
                    empty($_POST['consumidor_estado'])
                ) {
                    $msg_erro .= 'Digite o endereço completo do consumidor (endereço, número, bairro, cidade e estado).<br>';
                }
            }
        }

        if ($login_fabrica == 1) {
            if (strlen(trim($_POST['fisica_juridica'])) == 0) {
                $msg_erro .= "Escolha o Tipo Consumidor.<BR> ";
            } else {
                $xfisica_juridica = "'".($_POST['fisica_juridica'])."'";
            }
        } else {
            $xfisica_juridica = "null";
        }

        $pais_posto       = trim($_POST['pais_posto']);
        $cpf              = trim($_POST['consumidor_cpf']) ;
        $rg               = trim($_POST['consumidor_rg']) ;
        $fone             = trim($_POST['consumidor_fone']) ;
        $fone_celular     = trim($_POST['consumidor_celular']) ;
        if ($login_fabrica == 123) {
            $fone_celular  = preg_replace("/[^0-9]/", "", $fone_celular); 
        }
        $consumidor_profissao = filter_input(INPUT_POST, 'consumidor_profissao');
        $consumidor_profissao = str_replace('"', '', $consumidor_profissao);
        $consumidor_profissao = str_replace("'", "", $consumidor_profissao);
        $fone_comercial   = trim($_POST['consumidor_fone_comercial']) ;
        $endereco         = trim($_POST['consumidor_endereco']) ;
        $numero           = trim($_POST['consumidor_numero']);
        $complemento      = trim($_POST['consumidor_complemento']) ;
        $bairro           = trim($_POST['consumidor_bairro']) ;
        $cep              = trim($_POST['consumidor_cep']) ;
        $deslocamento_km  = trim($_POST['deslocamento_km']) ;
        $tipo_atendimento = trim($_POST['tipo_atendimento']) ;

	$endereco = str_replace("'","''",$endereco);
	$bairro   = str_replace("'","''",$bairro);

        $deslocamento_km= str_replace(",",".",$deslocamento_km);

        // HD-7652147
        // if ($login_fabrica == 91 && $_POST["consumidor_revenda"] == "R" && strlen(preg_replace("/\D/","",$cpf)) <> 14) { 
        //     $msg_erro .= "CNPJ Inválido. <br>"; 
        // }

        if ($login_fabrica == 91 && $_POST["consumidor_revenda"] == "C" && strlen(preg_replace("/\D/","",$cpf)) <> 11) {
            $msg_erro .= "CPF Inválido. <br>"; 
        }

        if(!in_array($login_fabrica,array(74, 86,90,94,88))) {
            if (strlen($cpf) > 0) {
                if (strlen(preg_replace("/\D/","",$cpf)) < 11) {
                    while (strlen(preg_replace("/\D/","",$cpf)) < 11) {
                        $cpf = "0".$cpf;
                    }
                }

                $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cpf));
                if(empty($valida_cpf_cnpj)){
                    if (!validaCPF($cpf)) {
                    }
                }else{
                    $msg_erro .= $valida_cpf_cnpj;
                }
            }
        }

        $rg = (strlen($rg) == 0) ? "null" : "'$rg'";

        if ($login_fabrica == 2) {
            if (strlen($endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
        }



        if (strlen($complemento) == 0) $complemento = "null";
        else                           $complemento = "'" . $complemento . "'";

        if($_POST['consumidor_contrato'] == 't' ) $contrato = 't';
        else                                      $contrato = 'f';

        $cep = str_replace (".","",$cep);
        $cep = str_replace ("-","",$cep);
        $cep = str_replace ("/","",$cep);
        $cep = str_replace (",","",$cep);
        $cep = str_replace (" ","",$cep);
        $cep = substr ($cep,0,8);


        if (strlen($cep) == 0) $cep = "null";
        else                   $cep = "'" . $cep . "'";

        $monta_sql .= "2: $sql<br>$msg_erro<br><br>";

        if ($login_fabrica == 1 AND strlen ($cpf) == 0) {
            $cpf = 'null';
        }
    }

    if($login_fabrica == 35){
        $informaemail = $_POST['informaemail'];
        $consumidor_email = trim($_POST['consumidor_email']);

        if(empty($consumidor_email) && empty($informaemail)){

            $msg_erro .= "Digite o e-mail do consumidor ou selecione uma opção de e-mail. <br /> ";

        }else if(!filter_var($consumidor_email, FILTER_VALIDATE_EMAIL) && !empty($consumidor_email)) {

            $msg_erro .= "O E-mail do Consumidor é inválido. <br />";

        }

    }else{

        $consumidor_email = trim($_POST['consumidor_email']);

    }

    // HD 18051
    if(strlen($consumidor_email) == 0){
        $consumidor_email = "";
    }

    $data_nascimento = '';

    if ($login_fabrica == 74) {
        $data_nascimento = $_POST['data_nascimento'];
        $consumidor_cpf_x = preg_replace(array('[\.]', '[\/]', '[-]'), '', $consumidor_cpf);

        if (strlen($consumidor_cpf_x) < 14 and empty($data_nascimento)) {
            $msg_erro .= "Data de Nascimento é obrigatória.<br>";
        } elseif (!empty($data_nascimento)) {
            $dn = explode("/", $data_nascimento);

            $d = (int) $dn[0];
            $m = (int) $dn[1];
            $y = (int) $dn[2];

            if (!checkdate($m, $d, $y)) {
                $msg_erro .= "Data de Nascimento inválida.<br>";
            }
        }
    }

    if(in_array($login_fabrica, array(35,101)) and empty($os)) {
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

    $classificacao_os = $_POST['classificacao_os'];

    if (strlen (trim ($classificacao_os)) == 0) {
        $classificacao_os = 'null';
        if ($login_fabrica == 7){
            $msg_erro .= " Classificação da OS é obrigatória. <br>";
        }
    }

    $tipo_atendimento = $_POST['tipo_atendimento'];

    if (strlen (trim ($tipo_atendimento)) == 0) {
        $tipo_atendimento = 'null';
        if ($login_fabrica == 7){
            $msg_erro .= " A natureza é obrigatória. <br>";
        } else if ($login_fabrica == 42 OR $login_fabrica == 124) { //hd_chamado=2704100
            $msg_erro .= " Selecione o tipo de atendimento. <br>";
        } else if ($login_fabrica == 1 ) {
            $garantia_pecas = filter_input(INPUT_POST,'garantia_pecas');

            if ($tipo_atendimento == 'null' && $garantia_pecas) {
                $sqlTipo = "
                    SELECT  tipo_atendimento
                    FROM    tbl_tipo_atendimento
                    WHERE   fabrica = $login_fabrica
                    AND     descricao ILIKE 'Devolu%o de Pe%as'
                ";
                $resTipo = pg_query($con,$sqlTipo);
                $tipo_atendimento = pg_fetch_result($resTipo,0,tipo_atendimento);
            }
        }
    } else if ($login_fabrica == 42) {
        $sql = "select entrega_tecnica from tbl_tipo_atendimento where fabrica = $login_fabrica and tipo_atendimento = $tipo_atendimento";
        $res = pg_query($con, $sql);

        $tipo_atendimento_et = pg_fetch_result($res, 0, entrega_tecnica);
    }

    $segmento_atuacao = $_POST['segmento_atuacao'];
    if (strlen (trim ($segmento_atuacao)) == 0) $segmento_atuacao = 'null';

    if($tipo_atendimento=='15' or $tipo_atendimento=='16'){
        if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $msg_erro .= 'Digite autorização cortesia. <br>';
        else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
    }else{
        if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $autorizacao_cortesia = 'null';
        else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
    }

    //--==== OS de Instalção ============================================
    $km_auditoria = "FALSE";
    $sql = "SELECT tipo_atendimento,km_google
    FROM tbl_tipo_atendimento
    WHERE tipo_atendimento = $tipo_atendimento";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res)>0){
        $km_google = pg_fetch_result($res,0,km_google);

        if($km_google == 't'){
            $qtd_km  = str_replace (",",".",$_POST['distancia_km']);
            $qtd_km2  = str_replace (",",".",$_POST['distancia_km_conferencia']);
            $qtde_km = number_format($qtd_km,3,'.','');
			$qtde_km2 = number_format($qtd_km2,3,'.','');

			if($login_fabrica == 74){
				if($_POST["contato_cidade"] == $cidade){
					$qtd_km = 0;
				}
            }

            if($distancia_km_maps<>'maps' AND ($qtde_km <> $qtde_km2 AND $qtde_km > 0)){
                $km_auditoria = "TRUE";
                $obs_km = " Alteração manual de km de $qtde_km2 km para $qtde_km km. ";
            }else{
                //HD: 24813 - PARA
                if ($login_fabrica == 50) {//HD: 24813 - PARA
                    if ($qtde_km >= 50) {
                        $km_auditoria = "TRUE";
                        $obs_km = " OS entrou em auditoria de km ({$qtde_km}).";
                    }
                    //desconta 20 km pois entende-se que é area hurbana e não pagam os 20
                    $qtde_km = $qtde_km - 20;
                    $qtde_km = ($qtde_km < 0) ? 0 : $qtde_km;
                }
                // if($login_fabrica ==50 AND $qtde_km> 50){
                //     $km_auditoria = "TRUE";
                // }
                if($login_fabrica ==30 AND $qtde_km> 0){
                    $km_auditoria = "TRUE";
                }

            }
            if (in_array($login_fabrica, array(91)) && $qtd_km > 0 && $sua_os == 'null') {
                $km_auditoria = "TRUE";
            }
        }else{
            if($login_fabrica <> 19) $qtde_produtos = 1;
        }
    }

    if(strlen($qtde_km)==0){
        $qtde_km = '0';
        $km_auditoria = "FALSE";
    }else{
        $update_km = " qtde_km      = $qtde_km     ,";
    }

    if (in_array($login_fabrica, [144])) {
        $km_auditoria = "FALSE";
    }

    $posto_codigo = trim ($_POST['posto_codigo']);
    $posto_codigo = str_replace ("-","",$posto_codigo);
    $posto_codigo = str_replace (".","",$posto_codigo);
    $posto_codigo = str_replace ("/","",$posto_codigo);
    $posto_codigo = substr($posto_codigo,0,14);
    if(strlen($posto_codigo)>0){
        $res = pg_query ($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo' AND credenciamento <> 'DESCREDENCIADO'");

        if (pg_num_rows($res)==0){
            $msg_erro .= "Posto Inválido. <br>";
        }else{
            $posto = @pg_fetch_result($res,0,0);
        }

    }
    else{
        $msg_erro .= "Informe o Posto. <br>";
    }

    if (in_array($login_fabrica, array(94))) {// Verifica se o posto é Revenda

        $sql = "SELECT tbl_posto_fabrica.posto, tbl_tipo_posto.tipo_revenda
        FROM tbl_posto_fabrica
        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.tipo_revenda
        WHERE tbl_posto_fabrica.fabrica = $login_fabrica
        AND tbl_posto_fabrica.posto = $posto";
        $res = pg_query($con,$sql);

        if( pg_num_rows($res) > 0) {
            $posto_revenda = true;
        }else{
            $posto_revenda = false;
        }
    }

    $data_abertura = trim($_POST['data_abertura']);
    $data_abertura = fnc_formata_data_pg($data_abertura);



    $hora_abertura = trim($_POST['hora_abertura']);
    if ($login_fabrica==7 AND strlen($hora_abertura)==0){
        $msg_erro .= " Digite a hora de abertura da OS. <br>";
    }
    if ($login_fabrica==7 AND strlen($posto) > 0){// HD 70398
        $sql = "SELECT credenciamento
        FROM  tbl_posto_fabrica
        WHERE tbl_posto_fabrica.posto = $posto
        AND   tbl_posto_fabrica.fabrica = $login_fabrica
        AND   tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';";
        $res = pg_query ($con,$sql);
        if(pg_num_rows($res)>0){
            $msg_erro .= "Este posto está DESCREDENCIADO. Não é possível cadastrar OS. <br>";
        }
    }

    if (strlen($hora_abertura) > 0){
        $hora_abertura = "'".$hora_abertura."'";
    }else{
        $hora_abertura = " NULL ";
    }


    $consumidor_nome           = str_replace ("'","",$_POST['consumidor_nome']);
    $consumidor_cidade         = str_replace ("'","",$_POST['consumidor_cidade']);
    $consumidor_estado         = $_POST['consumidor_estado'];
    $consumidor_fone           = $_POST['consumidor_fone'];
    $consumidor_celular        = $_POST['consumidor_celular'];
    if ($login_fabrica == 123) {
        $consumidor_celular = preg_replace("/[^0-9]/", "", $consumidor_celular);
    }
    $consumidor_profissao = filter_input(INPUT_POST, 'consumidor_profissao');
    $consumidor_profissao = str_replace('"', '', $consumidor_profissao);
    $consumidor_profissao = str_replace("'", "", $consumidor_profissao);
    $consumidor_fone_comercial = $_POST['consumidor_fone_comercial'];
    $consumidor_endereco       = $_POST['consumidor_endereco'];
    $consumidor_endereco       = str_replace("'", "''", $consumidor_endereco);
    $consumidor_bairro         = $_POST['consumidor_bairro'];
    $consumidor_bairro       = str_replace("'", "''", $consumidor_bairro);
    $consumidor_cep            = $_POST['consumidor_cep'];
    $consumidor_cep            = str_replace(".", "", $consumidor_cep);
    $consumidor_cep            = str_replace("-", "", $consumidor_cep);
    $consumidor_numero         = $_POST['consumidor_numero'];
    $consumidor_complemento    = $_POST['consumidor_complemento'];
    $consumidor_complemento       = str_replace("'", "''", $consumidor_complemento);

if ((strlen($_POST["consumidor_cidade"]) > 0 && $xconsumidor_cidade != "null") && (strlen($_POST["consumidor_estado"]) > 0 && $xconsumidor_estado != "null")) {
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
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
				$msg_erro .= "Cidade do consumidor não encontrada";
			}
		}
}

if (strlen($_POST["consumidor_estado"]) > 0 && !strlen($_POST["consumidor_cidade"])) {
  $msg_erro .= "Digite a cidade do consumidor";
}

if (strlen($_POST["consumidor_cidade"]) > 0 && !strlen($_POST["consumidor_estado"])) {
  $msg_erro .= "Selecione o estado do consumidor";
}

$consumidor_cpf = trim($_POST['consumidor_cpf']);
$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
$consumidor_cpf = str_replace (".","",$consumidor_cpf);
$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
$consumidor_cpf = trim (substr ($consumidor_cpf,0,14));

if($login_fabrica == 24){
    if(strlen($consumidor_cpf) == 14){
        $_POST['consumidor_revenda'] = 'R';
    }else if(strlen($consumidor_cpf) == 11){
        $_POST['consumidor_revenda'] = 'C';
    }
}

if (!empty($consumidor_cpf) && strlen(preg_replace("/\D/","",$consumidor_cpf)) < 11) {
    while (strlen(preg_replace("/\D/","",$consumidor_cpf)) < 11) {
        $consumidor_cpf = "0".$consumidor_cpf;
    }
    
    $consumidor_cpf = preg_replace("/\D/","",$consumidor_cpf);
}

    if(in_array($login_fabrica, array(7, 19, 72)) and strlen($consumidor_cpf) > 0){ // HD 46309
        $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));
        if(empty($valida_cpf_cnpj)){
            $sql = "SELECT fn_valida_cnpj_cpf('$consumidor_cpf')";
            $res = @pg_query($con,$sql);
            $cpf_erro = pg_errormessage($con);
            if(strlen($cpf_erro) > 0){
                $msg_erro = "CPF/CNPJ do consumidor inválido <br>";
            }
        }else{
            $msg_erro = $valida_cpf_cnpj;
        }
    }

    if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
    else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

    if ($login_fabrica == 42 and strlen($consumidor_cpf) == 0 and $tipo_atendimento_et == "t") {
        $msg_erro .= "Digite o CPF/CNPJ do consumidor <br />";
    }

    $consumidor_fone = strtoupper (trim ($_POST['consumidor_fone']));

    if($login_fabrica == 40){
        if (strlen($consumidor_fone) == 0) {
            $msg_erro .= "Digite o Telefone do consumidor. <br />";
        }
    }
    $revenda_cnpj = trim($_POST['revenda_cnpj']);

    if($login_fabrica == 35){
        $msg_erro .= VerificaBloqueioRevenda($revenda_cnpj, $login_fabrica);
    }

    $revenda_cnpj = preg_replace('/\D/', '', $revenda_cnpj);
    $cnpj_raiz    = $login_fabrica == 15 ? trim($_POST['revenda_cnpj_raiz']) : trim(substr($revenda_cnpj,0,8));

    $sqlNomeRevenda = "SELECT nome FROM tbl_revenda WHERE cnpj = '$revenda_cnpj'";
    $resNomeRevenda = pg_query($con, $sqlNomeRevenda);

    if(pg_num_rows($resNomeRevenda) > 0){
        $nome_revenda = pg_fetch_row($resNomeRevenda);
        $nome_revenda = $nome_revenda[0];
    }

    if($login_fabrica == 15) {
        if(substr($revenda_cnpj,0,8) != $cnpj_raiz) {
            $revenda_cnpj = $cnpj_raiz . '999999';
        }
    }

    if(($login_fabrica == 7 or $login_fabrica == 15) and (strlen($revenda_cnpj) or strlen($cnpj_raiz))) { // HD 46309
        $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",(!strlen($revenda_cnpj) ? $cnpj_raiz : $revenda_cnpj)));

        if(empty($valida_cpf_cnpj)){
            $sql = "SELECT fn_valida_cnpj_cpf('$revenda_cnpj')";
            $res = @pg_query($con,$sql);
            $cnpj_erro = pg_errormessage($con);
            if(strlen($cnpj_erro) > 0){
                $msg_erro .="CNPJ da Revenda inválido <br>";
            }
        }else{
            $msg_erro = $valida_cpf_cnpj;
        }
    }

    if($login_fabrica == 42 and $tipo_atendimento_et == "t"){
        if(strlen($consumidor_endereco) == 0)
            $msg_erro .= "Erro: Informe o endereço do cliente.<br />";

        if(intval($consumidor_numero) == 0){

            if ( trim(strtolower($consumidor_numero)) != 's/n' ){

                $msg_erro .= "Erro: Informe o endereço do cliente (número) ou insira 'S/N'.<br />";

            }
        }

        if (strlen($consumidor_fone) == 0) {
            $msg_erro .= "Digite o Telefone do consumidor. <br />";
        }



        if(strlen($consumidor_cep) == 0)
            $msg_erro .= "Erro: Informe o endereço do cliente (CEP).<br />";

    }

    // HD 17851
    if(in_array($login_fabrica, array(1,19)) and strlen($_POST['revenda_cnpj']) == 0) {        
        $msg_erro.="Digite o cnpj da revenda<br>";
    }
    if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
    else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

    // HD 17851
    if(in_array($login_fabrica, array(1,19)) and strlen($_POST['revenda_nome']) == 0) {    
        $msg_erro.="Digite o nome da revenda<br>";
    }

    $revenda_nome = str_replace ("'","",$_POST['revenda_nome']);
    $nota_fiscal  = $_POST['nota_fiscal'];

    if ($login_fabrica == 42 and strlen($nota_fiscal) == 0 and $tipo_atendimento_et == "t") {
     $msg_erro .= "Erro: Digite  o número da Nota Fiscal<br />";
 }

 if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
 else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

 $data_nf      = trim($_POST['data_nf']);
 $data_nf      = dateFormat($data_nf, 'dmy', "'y-m-d'");

 if (!$data_nf) {
    $msg_erro.= "Data de Compra Inválida <br />";
}

if($login_fabrica == 24){
    if(strlen(trim($produto_serie))==0){
        $msg_erro .= 'Informe o número de série. ';
    }

    $sqlns = "SELECT produto_serie, observacao
            FROM tbl_produto_serie
            WHERE '$produto_serie' between serie_inicial and serie_final
            AND fabrica = $login_fabrica AND serie_ativa is true ";
    $resns = pg_query($con, $sqlns);
    if(pg_num_rows($resns)>0){
        $observacao = pg_fetch_result($resns, 0, observacao);
        $msg_erro .= "Número de Série Bloqueado: $observacao <br>";
    }
}

if ($login_fabrica == 42 and strlen($_POST['data_nf']) == 0 and $tipo_atendimento_et == "t") {
    $msg_erro .= " Digite a data de compra. <br />";
}

$nota_fiscal_saida = $_POST['nota_fiscal_saida'];
$data_nf_saida     = trim($_POST['data_nf_saida']);
$data_nf_saida     = fnc_formata_data_pg($data_nf_saida);

if ($data_nf == 'null' AND $xtroca_faturada <> 't' and $login_fabrica <> 7 and $login_fabrica <> 24 and $login_fabrica <> 42) {
    $msg_erro .= " Digite a data de compra. <br />";
} else {
    if (strlen(trim($data_nf)) <> 12 and $login_fabrica <> 7 and $login_fabrica <> 24 and $login_fabrica <> 42) {
        $data_nf = "null";
        $msg_erro .= " Digite a data de compra. <br />";
    }
}

$produto_referencia = strtoupper (trim ($_POST['produto_referencia']));

    if(in_array($login_fabrica, array(20))){

        if(strlen($produto_serie) == 0 && $tipo_atendimento == "11"){
            $produto_serie = "999";
        }else if(strlen($produto_serie) == 0){
            $msg_erro .= "Digite o Número de Série. <br />";
        }else{

            if(strlen($produto_serie) != 3 && strlen($produto_serie) != 9){
                $msg_erro .= "O Número de Serie Deve Conter 3 ou 9 Dígitos. <br />";
            }

            /* if(!is_numeric($produto_serie)){
                $msg_erro .= "O Número de Série deve ser apenas números. <br />";
            } */

        }

    }

    if (in_array($login_fabrica, [124])) {
        $prodReferencia = $_POST['produto_referencia'];
        $prodSerie = trim($_POST['produto_serie']);

        $qNumeroSerie = "
            SELECT numero_serie_obrigatorio
            FROM tbl_produto
            WHERE referencia = '{$prodReferencia}'
            AND fabrica_i = {$login_fabrica}
        ";
        $rNumeroSerie = pg_query($con, $qNumeroSerie);
        $numeroSerieValida = pg_fetch_result($rNumeroSerie, 0, "numero_serie_obrigatorio");

        if ($numeroSerieValida == "t" AND strlen($prodSerie) == 0) {
            $msg_erro .= "Este produto exige um número de série.";            
        }
    }

    $produto_referencia = str_replace ("-","",$produto_referencia);
    $produto_referencia = str_replace (" ","",$produto_referencia);
    $produto_referencia = str_replace ("/","",$produto_referencia);
    $produto_referencia = str_replace (".","",$produto_referencia);

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

    if ($login_fabrica == 42) {//HD 400603

        switch($tipo_atendimento){
            case 103 : $produto_referencia = 'GAR-PECAS';break;
            case 104 : $produto_referencia = 'GAR-ACESS';break;
            case 133 : $produto_referencia = 'GAR-BATER';break;
            case 134 : $produto_referencia = 'GAR-CARRE';break;
        }
    }

    $produto_serie = strtoupper (trim ($_POST['produto_serie']));


    /* if (($login_fabrica == '20') and ($tipo_atendimento == '11')) {
        $produto_serie = '999000000';
    } */

    if ($login_fabrica == 42 and $tipo_atendimento_et == "t" and ($produto_serie == "null" or strlen($produto_serie) == 0)) {
        $msg_erro .= "Informe o número de série <br />";
    }

    if($login_fabrica == 94 AND strlen($produto_referencia) > 0 AND strlen($produto_serie) > 0){
        $sql = "SELECT serie
        FROM tbl_numero_serie
        WHERE serie = '$produto_serie'
        AND referencia_produto = '$produto_referencia'
        AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);

        if(pg_numrows($res) == 0){
           $msg_erro .= 'Número de série inválido!<br />';
       }
   }
   if ($login_fabrica == 74) {
        $produto_serie_atlas = trim($_POST["produto_serie"]);
        $data_fabricacao_atlas = trim($_POST['data_fabricacao']);
        $defeito_reclamado_atlas = trim($_POST['defeito_reclamado']);

        if (strlen($produto_serie_atlas) == 0  AND $numero_serie_obrigatorio == 't') {
            $msg_erro .= "Por favor, informe o número de série!<br />";
        }
        if (strlen($data_fabricacao_atlas) == 0 ) {
            $msg_erro .= "Por favor, informe a data de fabricação!<br />";
        }
        if (strlen($defeito_reclamado_atlas) == 0 ) {
            $msg_erro .= "Por favor, informe o defeito reclamado!<br />";
		}
   }

   if ($login_fabrica == 117) {
    $produto_serie = trim($_POST["produto_serie"]);

    if (!strlen($produto_serie)) {
        $msg_erro = "Por favor, informe o número de série";
    } else {
        if (strtolower($produto_serie) <> "n/d" and !is_numeric($produto_serie)) {
            $msg_erro = "Número de série inválido";
        } else {
            if (strtolower($produto_serie) <> "n/d" and (strlen($produto_serie) < 6 or strlen($produto_serie) > 14)) {
                $msg_erro = "Número de série inválido";
            } else {
                $produto_serie = strtoupper($produto_serie);
            }
        }
    }
}

if(strlen($data_abertura) > 0 && strlen($data_nf) > 0){

    if(strtotime(str_replace("'", "", $data_abertura)) < strtotime(str_replace("'", "", $data_nf))){
        $msg_erro .= "A data de Compra não pode ser maior que a data de Abertura";
    }

    if(strlen($os) > 0){
        $sql = "SELECT data_digitacao FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        $data_digitacao_comp = pg_fetch_result($res, 0, "data_digitacao");
        list($data_digitacao_comp, $hora) = explode(" ", $data_digitacao_comp);

        if(strtotime(str_replace("'", "", $data_abertura)) > strtotime($data_digitacao_comp)){
            $msg_erro .= "A data de abertura não pode ser maior que a data de Digitação";
        }
    }

}

$admin_paga_mao_de_obra = $_POST['admin_paga_mao_de_obra'];

if ($admin_paga_mao_de_obra == 'admin_paga_mao_de_obra' && $login_fabrica != 15)
    $admin_paga_mao_de_obra = 't';
else if ($admin_paga_mao_de_obra == 'on' && $login_fabrica == 15)
    $admin_paga_mao_de_obra = 't';
else
    $admin_paga_mao_de_obra = 'f';
$qtde_produtos     = strtoupper (trim ($_POST['qtde_produtos']));

$aparencia_produto = strtoupper (trim ($_POST['aparencia_produto']));
$acessorios        = strtoupper (trim ($_POST['acessorios']));

$consumidor_revenda= str_replace ("'","",$_POST['consumidor_revenda']);

$os_cortesia       = (!empty($_POST['os_cortesia'])) ? "t" : "f";

if($login_fabrica == 91){
    $garantia_diferenciada = $_POST['garantia_diferenciada'];
    $garantia_diferenciada_mes = $_POST['garantia_diferenciada_mes'];
}
$orientacao_sac = trim ($_POST['orientacao_sac']);

#   if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= "Tamanho do CPF/CNPJ do cliente inv?lido.";

#   if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= " Tamanho do CPF/CNPJ do cliente inv?lido.";

if(strlen($posto)>0){
    $sql = "select pais from tbl_posto where posto =$posto";
    $res = pg_query ($con,$sql) ;
    $pais = pg_fetch_result ($res, 0, pais);
}

/*IGOR HD 2935 - Quando pais for diferente de Brasil não tem CNPJ (bosch)*/
if($pais == "BR"){
    if (strlen ($revenda_cnpj)   <> 0 and strlen ($revenda_cnpj)   <> 14 and $tipo_atendimento_et <> "t") $msg_erro .= "Tamanho do CNPJ da revenda inválido. <br>";
}else{
    if (strlen ($revenda_cnpj)   == 0 and $tipo_atendimento_et <> "t")
        $msg_erro .= "Tamanho do CNPJ da revenda inválido. <br>";
}

if ($login_fabrica == 42 and strlen($revenda_cnpj) == 0) {
    $revenda_cnpj = "";
}

if (strlen ($produto_referencia) == 0) {
    if ($login_fabrica <> 7){
        $msg_erro .= " Digite o produto. <br>";
    }
}

$xquem_abriu_chamado = trim($_POST['quem_abriu_chamado']);

if (strlen($xquem_abriu_chamado) == 0) {
    $xquem_abriu_chamado = 'null';
    if ($login_fabrica == 7){
        $msg_erro .= "Digite quem abriu o Chamado. <br>";
    }
}else{
    $xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";
}

$xobs = trim($_POST['obs']);
if (strlen($xobs) == 0) $xobs = 'null';
else                    $xobs = "'".$xobs."'";

    // Campos da Black & Decker
if ($login_fabrica == 1) {
    if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $codigo_fabricacao = 'null';
    else $codigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

    if (strlen($_POST['satisfacao']) == 0) $satisfacao = "f";
    else                                   $satisfacao = "t";

    if (strlen($_POST['laudo_tecnico']) == 0) $laudo_tecnico = 'null';
    else                                      $laudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

    if ($satisfacao == 't' AND strlen($_POST['laudo_tecnico']) == 0) {
        $msg_erro .= " Digite o Laudo Técnico. <br>";
    }
}

    //HD 33095 13/08/2008
if (strlen(trim($_POST['capacidade'])) == 0) $xproduto_capacidade = 'null';
else                                         $xproduto_capacidade = "'".trim($_POST['capacidade'])."'";

if (strlen(trim($_POST['divisao'])) == 0) $xdivisao = 'null';
else                                      $xdivisao = "'".trim($_POST['divisao'])."'";

$xproduto_capacidade = str_replace(",",".",$xproduto_capacidade);
$xdivisao            = str_replace(",",".",$xdivisao);
$defeito_reclamado = trim ($_POST['defeito_reclamado']);
$marca_fricon = trim ($_POST['marca_fricon']);

if ($login_fabrica == 52 AND $marca_fricon <= '0') {
    $msg_erro .= "Selecione a Marca.<BR>";
}elseif($login_fabrica <> 52) {
		$marca_fricon = "null";
}

if (isset($_POST['defeito_reclamado'])) {
    if (strlen($defeito_reclamado) == 0) {
        $defeito_reclamado = "null";
    }

    if (($login_fabrica ==35 or $login_fabrica==28 or $login_fabrica == 42) AND $defeito_reclamado == '0') {
        $msg_erro .= "Selecione o defeito reclamado.<BR>";
    }

    if ($login_fabrica == 42 and $tipo_atendimento_et == "t" and (strlen($defeito_reclamado) == 0 or $defeito_reclamado == "null")) {
        $msg_erro .="Erro: Selecione um Defeito Reclamado.<br/>";
    }
}

if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0){
    $xdefeito_reclamado_descricao = 'null';
}else{
    $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";
}

if (strlen($os)>0){

    $sql = "select
    tbl_defeito_reclamado.defeito_reclamado
    from tbl_os
    join tbl_defeito_reclamado on(tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado and tbl_os.fabrica = tbl_defeito_reclamado.fabrica)
    where tbl_os.os=$os
    and tbl_os.fabrica=$login_fabrica
    and tbl_defeito_reclamado.ativo is true;";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res)>0 && $login_fabrica == 86){
        $defeito_reclamado = pg_result($res,0,0);

    }

}
$defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);

    // HD 413350 - Adicionar LeaderShip
if ($defeito_reclamado_descricao_obigatorio and $pedir_defeito_reclamado_descricao == 't' and
    ($defeito_reclamado_descricao == 'null' or strlen($defeito_reclamado_descricao) == 0)) {
    $msg_erro .= "Digite o defeito reclamado.<BR>";
}

    //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
if ($login_fabrica == 3){
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
            $msg_erro .= "Digite o defeito reclamado adicional.<BR>";
        }
    }
}

if (strlen ($data_abertura) <> 12) {
    $msg_erro .= " Digite a data de abertura da OS. <br>";
}else{
    $cdata_abertura = str_replace("'","",$data_abertura);
}

    //valida tipo de atendimento
    if($tipo_atendimento == 0  && in_array($login_fabrica, array(87))){
       $msg_erro .= " Selecione um tipo de atendimento<br>";
    }

if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";


$os_posto            = trim($_POST ['os_posto']);
if (strlen ($os_posto) == 0) $os_posto = null;
if($login_fabrica == 30){
    if (strlen($os_posto) > 0 AND strlen($os_posto) < 8) {
        $msg_erro .= 'O Número da "OS Posto" dever ter no mínimo 8 dígitos <br>';
    }
}

if ($login_fabrica == 3) {
    $aux_data_parametro = date("06-09-18"); 
    $aux_data_hoje  = date("d-m-y");
    
    if ($aux_data_hoje >= $aux_data_parametro) {
        if (strlen($_POST["tipo_atendimento"]) == 0) {
            $msg_erro .= "Favor informar o tipo de atendimento<br>";
        }
    }
}

$horas_trabalhadas = $_POST['horas_trabalhadas'];
    //valida tipo de atendimento
if(empty($horas_trabalhadas)  && in_array($login_fabrica, array(87))){
 $msg_erro .= " Digite as horas trabalhadas<br>";
}

    // se ? uma OS de revenda
if ($fOsRevenda == 1){

    if (strlen ($nota_fiscal) == 0){
        $nota_fiscal = "null";
        $nota_fiscal_saida = "null";
            //$msg_erro = "Entre com o n?mero da Nota Fiscal";
    }else{
        $nota_fiscal = "'" . $nota_fiscal . "'" ;
        $nota_fiscal_saida = "'" . $nota_fiscal_saida . "'" ;
    }

    if (strlen ($aparencia_produto) == 0)
        $aparencia_produto  = "null";
    else
        $aparencia_produto  = "'" . $aparencia_produto . "'" ;

    if (strlen ($acessorios) == 0) {
        $acessorios = "null";
    }

    if (strlen($consumidor_revenda) == 0)
        $msg_erro .= " Selecione consumidor ou revenda. <br>";
    else
        $xconsumidor_revenda = "'".$consumidor_revenda."'";

    if (strlen ($orientacao_sac) == 0)
        $orientacao_sac  = "null";

}else{

    if (strlen ($nota_fiscal) == 0 and $login_fabrica<>7 and $login_fabrica<>24){
            //$nota_fiscal = "null";
        $msg_erro .= "Informe o Número da Nota Fiscal <br>";
    }
    else
        $nota_fiscal = "'" . $nota_fiscal . "'" ;


    if (strlen ($nota_fiscal) == 0){
        $nota_fiscal_saida = "'" . $nota_fiscal_saida . "'" ;
    }else{
        $nota_fiscal_saida = "'" . $nota_fiscal_saida . "'" ;
    }
    if (strlen ($aparencia_produto) == 0)
        $aparencia_produto  = "null";
    else
        $aparencia_produto  = "'" . $aparencia_produto . "'" ;

    if (strlen ($acessorios) == 0) {
        $acessorios = "null";
    }

    if (strlen($consumidor_revenda) == 0)
        $msg_erro .= " Selecione consumidor ou revenda. <br>";
    else
        $xconsumidor_revenda = "'".$consumidor_revenda."'";

    if (strlen ($orientacao_sac) == 0)
        $orientacao_sac  = "null";

}

if (!empty($os)) {
    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect("SELECT * FROM tbl_os WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}");
} else {
    $auditorLog = new AuditorLog('insert');
}

$res = pg_query ($con,"BEGIN TRANSACTION");

$produto = 0;

    #HD 32668
if (strlen($produto_referencia) > 0 OR $login_fabrica <> 7 ){
    $cond_ativacao = "AND (tbl_produto.ativo IS TRUE or uso_interno_ativo)";
    if ($login_fabrica == 3) {
        $cond_ativacao = " AND ( (tbl_produto.ativo IS TRUE or uso_interno_ativo) OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't') ) ";
    }

	$xproduto_referencia = strtoupper (trim ($_POST['produto_referencia']));
    $sql = "SELECT tbl_produto.produto
    FROM   tbl_produto
    JOIN   tbl_linha USING (linha)
	WHERE  UPPER(tbl_produto.referencia)  = '$xproduto_referencia'
    AND    tbl_linha.fabrica = $login_fabrica
    $cond_ativacao ";
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) == 0) {
        if (strlen($produto_referencia) > 0){
            $msg_erro .= "Produto $produto_referencia não cadastrado <br>";
        }else{
            $produto = " null ";
        }
    }else{
        $produto = @pg_fetch_result ($res,0,0);
    }
}else{
    $produto = " null ";
}

if ($login_fabrica == 42) {
    $sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
    $res = pg_query($con, $sql);
    $tipo_atendimento_et = pg_result($res, 0, "entrega_tecnica");

    if ($tipo_atendimento_et == "t") {
        $sql = "SELECT produto, entrega_tecnica FROM tbl_produto WHERE produto = $produto AND entrega_tecnica IS TRUE";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0) {
            $msg_erro .= "Este não é um produto de entrega técnica <br />";
        }
    }

    $sql = "SELECT
    tbl_tipo_posto.tipo_revenda, tbl_posto_fabrica.entrega_tecnica
    FROM
    tbl_posto_fabrica
    JOIN
    tbl_tipo_posto
    ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
    WHERE
    tbl_posto_fabrica.fabrica = $login_fabrica
    AND tbl_posto_fabrica.posto = $posto";
    $res = pg_query($con, $sql);

    $posto_tipo_revenda    = pg_fetch_result($res, 0, "tipo_revenda");
    $posto_entrega_tecnica = pg_fetch_result($res, 0, "entrega_tecnica");

    if ($tipo_atendimento_et == "t" and ($posto_tipo_revenda == "f" and $posto_entrega_tecnica == "f")) {
        $msg_erro .= "A abertura de Os de entrega técnica não é permitida para este posto <br />";
    }

    if ($tipo_atendimento_et == "f" and $posto_tipo_revenda == "t") {
        $msg_erro .= "Para este posto só é permitido a abertura de os de entrega técnica <br />";
    }
}

    if ($xtroca_faturada <> "'t'" and $os_cortesia <> "t") { // verifica troca faturada para a Black

        // se não é uma OS de revenda, entra
        if ($fOsRevenda == 0){
            $sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";

            $res = @pg_query ($con,$sql);

            if (@pg_num_rows ($res) == 0) {
                //HD 3576 - Validar o produto somente na abertura da OS
                if($login_fabrica == 3 and strlen($os)> 0) {
                    //$msg_erro = "";
                }else{
                    if ($login_fabrica <> 7 and $login_fabrica <> 15){
                        $msg_erro .= "Produto $produto_referencia sem garantia <br>";
                    }
                }
            }else{
                $garantia = trim(@pg_fetch_result($res,0,garantia));
            }

            if (strlen($garantia)>0){
                $sql = "SELECT DATE($data_nf::date + ('$garantia months')::interval)";
                $res = @pg_query ($con,$sql);

                if (@pg_num_rows ($res) > 0) {
                    $data_final_garantia = trim(pg_fetch_result($res,0,0));
                }
                // HD 23616
//                 echo "->>".$tipo_atendimento;
                if ((!in_array($login_fabrica, array(3,6,7,11,24,30,35,51,15,91,117,128,172))) && ($login_fabrica == 1 && $tipo_atendimento != 334)) {
                    if(strlen($data_nf)>0){
                        if ($data_final_garantia < $cdata_abertura) {
                            // Corrigido. Retirei isto porque não vi o sentido: [ $data_nf ] - [ $data_final_garantia ] = [ $cdata_abertura ]
                            $msg_erro .= "Produto $produto_referencia fora da garantia, vencida em ". dateFormat($data_final_garantia,'ymd','d/m/y');
                        }
                    }
                }
            }
        }
    }

    if(strlen($msg_erro) == 0 && strlen(trim($_POST["produto_referencia"])) > 0 && strlen(trim($_POST["produto_descricao"])) > 0 && strlen($os) > 0){

        $produto_ref  = trim($_POST["produto_referencia"]);
        $produto_desc = trim($_POST["produto_descricao"]);

        $sql_produto_os = "SELECT
                                tbl_os.produto,
                                tbl_produto.referencia,
                                tbl_produto.descricao
                            FROM tbl_os
                            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                            WHERE
                                tbl_os.os = {$os}
                                AND tbl_os.fabrica = {$login_fabrica}";
        $res_produto_os = pg_query($con, $sql_produto_os);

        if(pg_num_rows($res_produto_os) > 0){

            $produto_os      = pg_fetch_result($res_produto_os, 0, "produto");
            $produto_ref_os  = pg_fetch_result($res_produto_os, 0, "referencia");
            $produto_desc_os = pg_fetch_result($res_produto_os, 0, "descricao");

            if(strlen(trim($produto_os)) > 0){

                $sql_id_produto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND referencia = '$produto_ref' AND descricao = '$produto_desc'";
                $res_id_produto = pg_query($con, $sql_id_produto);

                if(pg_num_rows($res_id_produto) > 0){

                    $produto_id = pg_fetch_result($res_id_produto, 0, "produto");

                    if($produto_os != $produto_id){

                        $sql_pecas_os = "SELECT os_item FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
                        $res_pecas_os = pg_query($con, $sql_pecas_os);

                        if(pg_num_rows($res_pecas_os) > 0){

                            $msg_erro .= "O produto não pode ser alterado, pois já existem peças lançadas na OS. <br />";

                        }else{

                            $sql_obs_adicionais = "SELECT obs_adicionais FROM tbl_os_extra WHERE os = {$os} AND i_fabrica = {$login_fabrica}";
                            $res_obs_adicionais = pg_query($con, $sql_obs_adicionais);

                            $obs_adicionais = pg_fetch_result($res_obs_adicionais, 0, "obs_adicionais");

                            $sql_admin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
                            $res_admin = pg_query($con, $sql_admin);

                            $nome_admin = pg_fetch_result($res_admin, 0, "nome_completo");

                            $obs_produto = "
                                O produto da OS <strong>{$os}</strong> foi alterado pelo admin <strong>{$nome_admin}</strong> na seguinte data: ".date("d/m/Y H:i")." <br />
                                De: {$produto_ref_os} - {$produto_desc_os} <br />
                                Para: {$produto_ref} - {$produto_desc}
                            ";

                            $obs_adicionais = (strlen($obs_adicionais) > 0) ? $obs_adicionais." <br /> <br /> ".$obs_produto : $obs_produto;

                            $sql_upd_obs = "UPDATE tbl_os_extra SET obs_adicionais = '{$obs_adicionais}' WHERE os = {$os} AND i_fabrica = {$login_fabrica}";
                            $res_upd_obs = pg_query($con, $sql_upd_obs);

                        }

                    }

                }

            }

        }

    }

    if ($login_fabrica == 1) {
        $sql =  "SELECT tbl_familia.familia, tbl_familia.descricao
        FROM tbl_produto
        JOIN tbl_familia USING (familia)
        WHERE tbl_familia.fabrica = $login_fabrica
        AND   tbl_familia.familia = 347
        AND   tbl_produto.linha   = 198
        AND   tbl_produto.produto = $produto;";
        $res = @pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            $xtipo_os_compressor = "10";
        }else{
            $xtipo_os_compressor = 'null';
        }
    }elseif($login_fabrica ==19){
        if (strlen($_POST['tipo_os']) > 0) {
            $xtipo_os_compressor = $_POST['tipo_os'];
        }else{
            $xtipo_os_compressor = 'null';
        }
    }else{
        $xtipo_os_compressor = 'null';
    }
    $os_reincidente = "'f'";

    if (strlen($os) > 0 AND ($login_fabrica == 11 or $login_fabrica == 172)) {
        $sql = "SELECT os FROM tbl_os
        WHERE os = $os AND admin IS NULL";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)  >0 and strlen ($msg_erro) == 0) { #HD 97504
            $sql = "UPDATE tbl_os_extra SET admin_paga_mao_de_obra = 't' WHERE os = $os";
            $res = pg_query ($con,$sql);
            $msg_erro .= pg_errormessage($con) ;
            $pagar_mao_de_obra = "sim";
        }
    }
    /**
     * HD 854585 - Brayan
     */
    if ($login_fabrica == 74 && !empty($os)) {

        if (empty($data_fabricacao)) {
            $xxdata_fabricacao = 'NULL';
        } else {
            $xxdata_fabricacao = "'$data_fabricacao'";
        }

        $sql = "SELECT fn_verifica_serie_atlasfogoes(os, serie, '$produto_serie',$xxdata_fabricacao,$login_admin)
        FROM tbl_os
        WHERE os = $os
        AND serie != '$produto_serie'";
        $res = pg_query($con, $sql);
        $msg_erro .= pg_errormessage($con);

    }

	if($login_fabrica == 117 or $login_fabrica == 128){
		$garantia_estendida = $_POST['garantia_estendida'];
		if($garantia_estendida){
			$opcao_garantia_estendida = $_POST['opcao_garantia_estendida'];
			if(strlen($opcao_garantia_estendida) > 0 ){
				$xcertificado_garantia = ($opcao_garantia_estendida == "t") ? "12" : "6";
			}else{
				$fabrica_garantia = ($login_fabrica == 117) ? "Elgin" : "Unilever";
				$msg_erro .= "Informe se produto foi instalado por uma autorizada {$fabrica_garantia} <br />";

			}
		}else{
			$xcertificado_garantia = "null";
		}

	}

    if (in_array($login_fabrica, [1,123])) {
        $msg_erro .= valida_celular(trim($consumidor_celular));
    }

    if($login_fabrica == 80){
        if(strlen(trim($consumidor_celular))==0){
            $msg_erro .= "Informe o Telefone Celular do consumidor.";
        }
    }
if (in_array($login_fabrica, array(11,172))) {
    $versao_cod = trim($_POST["codigo_interno"]);
    $desc_produto = trim($_POST["produto_descricao"]);
    $cod_interno_bd = "";


    $sql_ci = "SELECT JSON_FIELD('codigo_interno_obrigatorio',parametros_adicionais) AS ci_obrigatorio
                    FROM tbl_produto
                    WHERE produto = $produto
                    AND fabrica_i = $login_fabrica
                    AND ativo IS TRUE";

    $res_ci = pg_query($con,$sql_ci);
    if (pg_num_rows($res_ci) > 0 && $consumidor_revenda != 'R') {
            $ci_obrigatorio = pg_result($res_ci,0,'ci_obrigatorio');
            if ($ci_obrigatorio == 't'){
                if (strlen(trim($versao_cod) > 0)) {
                    $sql_Cod = "SELECT JSON_FIELD('codigo_interno',parametros_adicionais) AS cod_interno_bd
                                FROM tbl_produto
                                WHERE produto = $produto
                                AND fabrica_i = $login_fabrica
                                AND ativo IS TRUE";

                    $res_Cod = pg_query($con,$sql_Cod);

                    if (pg_num_rows($res_Cod) > 0) {
                        $cod_interno_bd = pg_result($res_Cod,0,'cod_interno_bd');
                        if (strlen(trim($versao_cod)) == strlen(trim($cod_interno_bd))){
                            $sub_cod_interno_bd = substr($cod_interno_bd,-1);
                            $sub_versao_cod = substr($versao_cod,-1);
                            if ($sub_versao_cod != $sub_cod_interno_bd) {
                                $msg_erro .= " Código Interno do produto $desc_produto incorreto. <br>";
                            }
                        }else{
                            $msg_erro .= " Código Interno do produto $desc_produto incorreto. <br>";
                        }
                    }else{
                            $msg_erro .= " Código Interno não Encontrado <br>";
                    }
                }else{
                    $msg_erro .= " Informe o Código Interno do Produto. <br>";
                }
        }
    }
}

if($login_fabrica == 30){
    $dadosDiferenteEsmaltec = $_POST["dadosDiferenteEsmaltec"];
}

if (strlen ($msg_erro) == 0) {

        //  HD 234135 - MLG - Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
     $usa_rev_fabrica = in_array($login_fabrica, array(3));

     if ($usa_rev_fabrica) {
            $subq_revenda = "(SELECT tbl_revenda.revenda
                FROM tbl_revenda
                JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.cnpj = tbl_revenda.cnpj
                WHERE tbl_revenda_fabrica.cnpj    = $xrevenda_cnpj
                AND tbl_revenda_fabrica.fabrica = $login_fabrica
                LIMIT 1)";
	} else {
		$subq_revenda = "(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj LIMIT 1)";
	}

	$preco_produto = (float)trim($_POST["preco_produto"]);

	if($login_fabrica == 127){
	  $codigo_ratreio = $_POST['codigo_ratreio'];

	  if(strlen($codigo_rastreio) > 0){
		$campos_adicionais = "{\"codigo_rastreio\":\"$codigo_rastreio\"}";
	  }
	}

	if(in_array($login_fabrica, array(104)) && !empty($os)){
        $sql_data_recebimento = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
        $res_data_recebimento = pg_query($con,$sql_data_recebimento);
        $data_recebimento_anterior = json_decode(pg_fetch_result($res_data_recebimento, 0, 'campos_adicionais'), true);
        $data_recebimento_anterior = $data_recebimento_anterior['data_recebimento_produto'];
		$data_recebimento_produto = trim($_POST["data_recebimento_produto"]);
        if (trim($data_recebimento_produto) != trim($data_recebimento_anterior)) {
		  $campos_adicionais .= "{\"data_recebimento_produto\":\"$data_recebimento_produto\",\"recebimento_alterado_admin\":\"$login_admin\"}";  
        }    
	}

	if($login_fabrica == 59){
		  if (strlen($_POST['origem']) > 0 ){
			$origem = $_POST['origem'];
		  }else{
			$origem = $_GET['origem'];
		  }
		  if(strlen($origem) > 0){
			$campos_adicionais = "{\"origem\":\"$origem\"}";
		}

	}

    if($login_fabrica == 50 AND strlen(trim($xdefeito_reclamado_descricao)) > 0){ //HD-3331834
        $defeito_reclamado = $_POST['defeito_reclamado_descricao'];
        $sql_def_desc = "SELECT defeito_reclamado, descricao, codigo from tbl_defeito_reclamado where fabrica=$login_fabrica and defeito_reclamado = $defeito_reclamado";
        $res_def_desc = pg_query($con, $sql_def_desc);
        $xdefeito_reclamado_descricao = pg_fetch_result($res_def_desc, 0, 'descricao');
        $xdefeito_reclamado_descricao = "'".$xdefeito_reclamado_descricao."'";
    }

    $is_insert = false;

    if(in_array($login_fabrica, array(11,172))){
        $versao = $_POST["codigo_interno"];
    }


    if(strlen(trim($os))>0){
        if($login_fabrica == 30 and strlen($msg_erro) == 0){
            $nf = $_POST['nota_fiscal'];
            $xdata_nf =  str_replace("'", "", $data_nf);
            $revenda_cnpj = $_POST["revenda_cnpj"];
            $revenda_cnpj =  str_replace(array(".", "/", "-"), "", $revenda_cnpj);

            $libera = false; 
            $sqlAud = "SELECT 
                            tbl_auditoria_os.observacao,
                            tbl_os.data_abertura,  
                            tbl_os_extra.os_reincidente,
                            tbl_os.serie
                        FROM tbl_auditoria_os 
                        join tbl_os_extra on tbl_os_extra.os = tbl_auditoria_os.os
                        join tbl_os on tbl_os_extra.os = tbl_os.os and tbl_os.fabrica = $login_fabrica 
                        WHERE tbl_auditoria_os.os = $os 
                        and tbl_auditoria_os.liberada is null 
                        and tbl_auditoria_os.auditoria_status = 1 
                        and tbl_auditoria_os.cancelada is null 
                        and tbl_auditoria_os.reprovada is null ";
            $resAud = pg_query($con, $sqlAud);

            if(pg_num_rows($resAud)>0){
                $observacao     = pg_fetch_result($resAud, 0, 'observacao');
                $num_os_reincidente = pg_fetch_result($resAud, 0, 'os_reincidente');
                $serie          = trim(pg_fetch_result($resAud, 0, 'serie'));
                $data_abertura_os  = trim(pg_fetch_result($resAud, 0, 'data_abertura'));

                if(strlen(trim($os_reincidente))> 0){
                    if($produto_serie == $serie){
                        $sqlOs = " SELECT data_abertura, revenda_cnpj, data_nf, serie, nota_fiscal from tbl_os where os = $num_os_reincidente and fabrica = $login_fabrica ";
                        $resOs = pg_query($con, $sqlOs);
                        if(pg_num_rows($resOs)>0){
                            $nota_fiscal_re = pg_fetch_result($resOs, 0, 'nota_fiscal');
                            $serie_re  = pg_fetch_result($resOs, 0, 'serie');
                            $data_nf_re  = pg_fetch_result($resOs, 0, 'data_nf');
                            $revenda_re  = pg_fetch_result($resOs, 0, 'revenda_cnpj');
                            $data_abertura_re = pg_fetch_result($resOs, 0, 'data_abertura'); 

                            if($nf != $nota_fiscal_re){
                                $diferenca .= 'nota_fiscal';
                            }

                            if(strtotime($xdata_nf) != strtotime($data_nf_re)){
                                $diferenca .= 'data compra';
                            }

                            if($revenda_cnpj != $revenda_re){
                                $diferenca .= 'revenda';
                            }

                            $datetime1 = new DateTime($data_abertura_re);
                            $datetime2 = new DateTime($data_abertura_os);
                            $interval = $datetime1->diff($datetime2);
                            $qtde_dias_diferenca = $interval->days;

                            if($qtde_dias_diferenca < 90){
                                $diferenca .= 'menos de 90 dias';
                            }

                            if(strlen(trim($diferenca))==0){
                                $libera = true;
                                $justificativa = "O.S liberada da reincidencia, dados iguais";
                            }
                        }
                    }else{
                        $libera = true; 
                        $justificativa = " Número de série alterado ";
                    }                    
                }
                if($libera == true){
                    $sqlupd = "UPDATE tbl_auditoria_os set liberada = now(), admin = $login_admin, justificativa = '$justificativa' where os = $os "; 
                    $resupd = pg_query($con, $sqlupd);
                }
            }
        }
    }

if (strlen($os) == 0) {

    if (isset($_POST['defeito_reclamado'])) {
        $mostraReclamado  = "defeito_reclamado ,";
        $mostraReclamadoV = $defeito_reclamado." ,";
    }

    $is_insert = true;

    /*================ INSERE NOVA OS =========================*/
    $sql = "INSERT INTO tbl_os (
        tipo_atendimento   ,
        segmento_atuacao   ,
        posto              ,
        admin              ,
        fabrica            ,
        sua_os             ,
        data_abertura      ,
        hora_abertura      ,
        cliente            ,
        revenda            ,
        consumidor_nome    ,
        consumidor_cpf     ,
        consumidor_cidade  ,
        consumidor_estado  ,
        consumidor_fone    ,
        consumidor_celular ,
        consumidor_fone_comercial ,
        consumidor_email   ,
        consumidor_bairro,
        consumidor_endereco,
        consumidor_cep,
        consumidor_numero,
        consumidor_complemento,
        revenda_cnpj       ,
        revenda_nome       ,
        nota_fiscal        ,
        data_nf            ,
        produto            ,
        serie              ,
        qtde_produtos      ,
        aparencia_produto  ,
        acessorios         ,
        defeito_reclamado_descricao,
        $mostraReclamado
        marca              ,
        obs                ,
        quem_abriu_chamado ,
        consumidor_revenda ,
        troca_faturada     ,
        os_reincidente     ,
        qtde_km            ,
        autorizacao_cortesia,
        capacidade         ,
        divisao            ,
        os_posto           ,
        versao,
        cortesia ";

        if ($login_fabrica == 1) {
            $sql .= ",codigo_fabricacao ,
            satisfacao          ,
            tipo_os             ,
            laudo_tecnico       ,
            fisica_juridica";
        }

            if ($login_fabrica == 19) { // hD 49849
                $sql .= ", tipo_os             ";
            }
            if ($login_fabrica == 117 or $login_fabrica == 128) { // hD 49849
                $sql .= ", certificado_garantia ";
            }
            // Verifica se a fábrica utiliza o cadastro de preco de produto
            if ($login_fabrica == 15) { // hd 947073
                $sql .= ", valores_adicionais ";
            }

            $sql .= ") VALUES (
            $tipo_atendimento                                               ,
            $segmento_atuacao                                               ,
            $posto                                                          ,
            $login_admin                                                    ,
            $login_fabrica                                                  ,
            trim ($sua_os)                                                  ,
            $data_abertura                                                  ,
            $hora_abertura                                                  ,
            (SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
            $subq_revenda                                                   ,
            trim ('$consumidor_nome')                                       ,
            trim ('$consumidor_cpf')                                        ,
            trim ('$consumidor_cidade')                                     ,
            trim ('$consumidor_estado')                                     ,
            trim ('$consumidor_fone')                                       ,
            trim ('$consumidor_celular')                                    ,
            trim ('$consumidor_fone_comercial')                             ,
            trim ('$consumidor_email')                                      ,
            trim ('$consumidor_bairro'),
            trim ('$consumidor_endereco'),
            trim ('$consumidor_cep'),
            trim ('$consumidor_numero'),
            trim ('$consumidor_complemento'),
            trim ('$revenda_cnpj')                                          ,
            trim ('$revenda_nome')                                          ,
            trim ($nota_fiscal)                                             ,
            $data_nf                                                        ,
            $produto                                                        ,
            '$produto_serie'                                                ,
            $qtde_produtos                                                  ,
            trim ($aparencia_produto)                                       ,
            ".pg_escape_literal($con,$acessorios)."                          ,
            $xdefeito_reclamado_descricao                                   ,
            $mostraReclamadoV
            $marca_fricon                                                   ,
            $xobs                                                           ,
            $xquem_abriu_chamado                                            ,
            '$consumidor_revenda'                                           ,
            $xtroca_faturada                                                ,
            $os_reincidente                                                 ,
            $qtde_km                                                        ,
            $autorizacao_cortesia                                           ,
            $xproduto_capacidade                                            ,
            $xdivisao                                                       ,
            '$os_posto'                                                     ,
            '{$versao}',
            '$os_cortesia'                                                    ";

            if ($login_fabrica == 1) {
                $sql .= ", $codigo_fabricacao ,
                '$satisfacao'         ,
                $xtipo_os_compressor  ,
                $laudo_tecnico        ,
                $xfisica_juridica";
            }
            if ($login_fabrica == 19) {
                $sql .= ",$xtipo_os_compressor ";

            }

            if ($login_fabrica == 117 or $login_fabrica == 128) { // hD 49849
                $sql .= ",'$xcertificado_garantia' ";
            }

            if ($login_fabrica == 15) {
                $sql .= ",$preco_produto ";
            }

            $sql .= ") RETURNING os;";

} else {

    $osx = "update";

    if (in_array($login_fabrica, [19])) {

        $sqlTipoAnterior = "SELECT tipo_atendimento
                            FROM tbl_os
                            WHERE os = {$os}";
        $resTipoAnterior = pg_query($con, $sqlTipoAnterior);

        $tipo_atendimento_anterior = pg_fetch_result($resTipoAnterior, 0, 'tipo_atendimento');

        if ($tipo_atendimento != $tipo_atendimento_anterior) {

            $sqlRemoveChecklist = "DELETE FROM tbl_os_defeito_reclamado_constatado
                                   WHERE os = {$os}
                                   AND checklist_fabrica IS NOT NULL";
            $resRemoveChecklist = pg_query($con, $sqlRemoveChecklist);

        }


    }

            //hd17966
    if ($login_fabrica==45) {
        $sql_os = "SELECT finalizada,data_fechamento
        FROM   tbl_os
        JOIN   tbl_os_extra USING(os)
        WHERE  fabrica = $login_fabrica
        AND    os      = $os
        AND    extrato         IS     NULL
        AND    finalizada      IS NOT NULL
        AND    data_fechamento IS NOT NULL";
        $res = pg_query ($con,$sql_os);
        if(pg_num_rows($res)>0){
            $voltar_finalizada = pg_fetch_result($res,0,0);
            $voltar_fechamento = pg_fetch_result($res,0,1);
            $sql_update_os = "UPDATE tbl_os SET data_fechamento = NULL , finalizada = NULL
            WHERE os      = $os
            AND   fabrica = $login_fabrica";
            $res = pg_query ($con,$sql_update_os);
        }
    }

     if($login_fabrica == 15) {
        $sql = "UPDATE tbl_os_extra
        SET admin_paga_mao_de_obra = '$admin_paga_mao_de_obra'
        WHERE os = $os";

        $res = pg_query ($con,$sql);
    }

    //adicionei na consulta o campo tipo atendimento para
    // não precisar fazer mais uma query de consulta
    //já consulta os, feito para saint gobain hd-2900009
    //HD-2893396 Adicionado o campo tbl_os.admin p/ não fazer mais uma query.
    $sqlConsultaKM = "SELECT tbl_os.qtde_km, tbl_os.admin, tbl_os.tipo_atendimento FROM tbl_os WHERE tbl_os.os = ".$_POST['os']." AND tbl_os.fabrica = ".$login_fabrica;
    $resConsultaKM = pg_query($con,$sqlConsultaKM);
    $consultaKM = pg_fetch_result($resConsultaKM, 0, "qtde_km");

    if($login_fabrica == 125){
        $tipo_atendimento_anterior = pg_fetch_result($resConsultaKM, 0, "tipo_atendimento");

        if($tipo_atendimento_anterior == 147 and $tipo_atendimento == 146){
            $update_km = " qtde_km      = 0  ,";

            $sqlAprovaKm = "UPDATE tbl_auditoria_os 
                            SET liberada = CURRENT_TIMESTAMP, 
                                admin = {$login_admin}, 
                                justificativa = 'Tipo de atendimento alterado para balcão' 
                            WHERE os = {$os} 
                            AND auditoria_status = 2
                            AND liberada IS NULL 
                            AND reprovada IS NULL
                            AND cancelada IS NULL";
            $resAprovaKm = pg_query($con, $sqlAprovaKm);

        }

    }

    if($consultaKM != $qtd_km and $qtd_km >= 0 and strlen($qtd_km) >0  and $km_google == 't' ){

        if ($telecontrol_distrib == "t") {

            $sqlKmAlterado = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
                              VALUES ({$os}, 2, 'Quantidade de KM alterada de {$consultaKM} para {$qtd_km}');";
            $resKmAlterado = pg_query($con, $sqlKmAlterado);

            atualiza_status_checkpoint($os, "Em auditoria");

        } else {
            $resConsultaKM = "";
            if (in_array($login_fabrica, array(74,91))) { //hd_chamado=3141903 Alterado p/ quando alterar qtde km cair em auditoria novamente.
                $id_status_os = 98;
            }else{
                $id_status_os = 216;
            }
            $sqlInsert = "INSERT INTO tbl_os_status (os,status_os,observacao,admin,fabrica_status)
                VALUES ($os,$id_status_os,'Quantidade de KM alterada de $consultaKM para $qtd_km',$login_admin,$login_fabrica)";
            $resConsultaKM = pg_query($con,$sqlInsert);
            $msg_erro = pg_last_error();
        }

    }

    if (isset($_POST['defeito_reclamado'])) {
        $mostraReclamadoUp  = ", defeito_reclamado = $defeito_reclamado ";
    }

    /*================ ALTERA OS =========================*/
    $sql = "UPDATE tbl_os SET
    tipo_atendimento   = $tipo_atendimento           ,
    segmento_atuacao   = $segmento_atuacao           , ";
            $sql .="admin_altera       = $login_admin                ,
            fabrica            = $login_fabrica              ,
            sua_os             = trim($sua_os)               ,
            data_abertura      = $data_abertura              ,
            hora_abertura      = $hora_abertura              ,
            consumidor_nome    = trim('$consumidor_nome')    ,
            consumidor_cpf     = trim('$consumidor_cpf')     ,
            consumidor_fone    = trim('$consumidor_fone')    ,
            consumidor_celular = trim('$consumidor_celular')    ,
            consumidor_fone_comercial = trim('$consumidor_fone_comercial') ,
            consumidor_endereco= trim('$consumidor_endereco'),
            consumidor_numero  = trim('$consumidor_numero'),
            consumidor_complemento= trim('$consumidor_complemento'),
            consumidor_bairro  = trim('$consumidor_bairro'),
            consumidor_cep     = $cep,
            consumidor_estado  = trim('$consumidor_estado'),
            consumidor_cidade  = trim('$consumidor_cidade'),
            consumidor_email   = trim('$consumidor_email') ,
            cliente            = (SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
            revenda_cnpj       = trim('$revenda_cnpj')       ,
            revenda_nome       = trim('$revenda_nome')       ,
            nota_fiscal        = trim($nota_fiscal)          ,
            data_nf            = $data_nf                    ,
            produto            = $produto                    ,
            "
            .
            ($login_fabrica != 15 ? "serie = '$produto_serie' ," : "")
            .
            "
            qtde_produtos      = $qtde_produtos              ,
            aparencia_produto  = trim($aparencia_produto)    ,
            acessorios         = ".pg_escape_literal($con,$acessorios).",
            " . ( $login_fabrica != 45 ? "defeito_reclamado_descricao = $xdefeito_reclamado_descricao," : '' ) . "
            quem_abriu_chamado = $xquem_abriu_chamado        ,
            obs                = $xobs                     ,
            consumidor_revenda = '$consumidor_revenda'       ,
            troca_faturada     = $xtroca_faturada            ,
            os_reincidente     = $os_reincidente             ,
            autorizacao_cortesia = $autorizacao_cortesia     ,
            $update_km
            capacidade         = $xproduto_capacidade        ,
            divisao            = $xdivisao                   ,
            revenda            = $subq_revenda               ,
            os_posto           = '$os_posto'                 ,
            nota_fiscal_saida  = trim($nota_fiscal_saida)    ,
            data_nf_saida      = $data_nf_saida              ,
            versao             = '{$versao}'                 ,
            cortesia           = '$os_cortesia'              ";


            if ($login_fabrica == 1) {
                $sql .= ", codigo_fabricacao = $codigo_fabricacao ,
                satisfacao           = '$satisfacao'      ,
                tipo_os              = $xtipo_os_compressor,
                laudo_tecnico        = $laudo_tecnico     ,
                fisica_juridica      = $xfisica_juridica";
            }
            if ($login_fabrica == 19) {
                $sql .= ", tipo_os              = $xtipo_os_compressor ";
            }
            if ($login_fabrica <> 14) {
                $sql .= " $mostraReclamadoUp    ";
            }

            if ($login_fabrica == 52) {
                $sql .= ", marca  = $marca_fricon    ";
            }

            if($login_fabrica == 117 or $login_fabrica == 128){
                $sql .= ", certificado_garantia  = $xcertificado_garantia    ";
            }

            if ($login_fabrica == 15) {
                $sql .= ", valores_adicionais = $preco_produto ";
            }


            $sql .= " WHERE os      = {$_POST['os']}
            AND   fabrica = $login_fabrica";
        }

        //pega dados para o auditor
        //verifica antes.
        if (strlen($os) > 0) {
            $sql_email_antes = "select * from tbl_os where os = $os and fabrica = $login_fabrica";
            $res_email_antes = pg_query($con, $sql_email_antes);
            if(pg_num_rows($res_email_antes)>0){
                $dados_antes = pg_fetch_assoc($res_email_antes);
            }
        }

        $res = @pg_query ($con,$sql);

		if(empty($os)) {
			$os = pg_fetch_result($res, 0, os);
		}
  
       /**
        * @author William Castro <william.castro@telecontrol.com.br>
        * hd-6639553 -> Box Uploader
        */
        
        if ($_POST['anexo_chave']) {

            $anexo_chave = $_POST['anexo_chave'];

            if ($anexo_chave == $_POST['os']) {

                $query_anexo = "SELECT * 
                                FROM tbl_tdocs
                                WHERE fabrica = {$login_fabrica}
                                AND referencia_id = '{$anexo_chave}'
                                AND situacao = 'ativo'";
                $os = $anexo_chave;
            } else {
            
                $query_anexo = "SELECT * 
                                FROM tbl_tdocs
                                WHERE fabrica = {$login_fabrica}
                                AND hash_temp = '{$anexo_chave}'
                                AND situacao = 'ativo'";
            }

            $res_anexo = pg_query($con, $query_anexo);

            if (pg_num_rows($res_anexo) > 0) {

                for ($i = 0; $i < pg_num_rows($res_anexo); $i++) {
                    
                    $imagem_id = pg_fetch_result($res_anexo, $i, tdocs);

                    $imagem_tipo_json = pg_fetch_result($res_anexo, $i, obs);

                    $imagem_tipo = json_decode($imagem_tipo_json); 

                    $imagem_tipo = $imagem_tipo[0]->typeId;
        

                    $query_update = "UPDATE tbl_tdocs
                                     SET referencia_id = {$os}, hash_temp = NULL
                                     WHERE tdocs = {$imagem_id}";

                    $resposta_anexa_imagem_os = pg_query($con, $query_update);
                }
            }  

        }

        if($login_fabrica == 30 ){
            $dadosDiferenteEsmaltec = $_POST["dadosDiferenteEsmaltec"];
            
            $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] = true;

            if(empty($os)){
                $os = pg_fetch_result($res, 0, 'os');                
            }
        }

        if ($login_fabrica == 104) {
            if (!empty($os)) {
                $sql_audito = "SELECT * FROM tbl_auditoria_os WHERE os = $os";
                $res_audito = pg_query($con, $sql_audito); 

                if (pg_num_rows($res_audito) > 0) {
                    $sql_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                    $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);

                    if (pg_num_rows($qry_campos_adicionais) == 0) {
                        $json_campos_adicionais = json_encode([
                            "os_auditoria_admin_alteracao" => $login_admin,
                            "os_auditoria_alteracao"       => date("d/m/Y H:i:s")
                        ]);

                        $sql_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '$json_campos_adicionais')";
                    } else {
                        $arr_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);
                        $arr_campos_adicionais["os_auditoria_admin_alteracao"] = $login_admin;
                        $arr_campos_adicionais["os_auditoria_alteracao"]       = date("d/m/Y H:i:s");

                        $json_campos_adicionais = json_encode($arr_campos_adicionais);
                        $sql_campos_adicionais  = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$json_campos_adicionais' WHERE os = $os";
                    }

                    $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);
                } 
            }
        }

        //pega dados para o auditor.
        //verifica depois.
        if (strlen($os) > 0) {
            $sql_email_depois = "select * from tbl_os where os = $os and fabrica = $login_fabrica";
            $res_email_depois = pg_query($con, $sql_email_depois);
            if(pg_num_rows($res_email_depois)>0){
                $dados_depois = pg_fetch_assoc($res_email_depois);

            }
        }

        if ($login_fabrica == 1 and !empty($consumidor_profissao)) {
            if (empty($os)) {
                $os = pg_fetch_result($res, 0, 'os');
            }

            // POC
            if (!empty($os)) {
                $sql_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);

                if (pg_num_rows($qry_campos_adicionais) == 0) {
                    $json_campos_adicionais = json_encode(["consumidor_profissao" => utf8_encode($consumidor_profissao)]);

                    $sql_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '$json_campos_adicionais')";
                } else {
                    $arr_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);
                    $arr_campos_adicionais["consumidor_profissao"] = utf8_encode($consumidor_profissao);

                    $json_campos_adicionais = json_encode($arr_campos_adicionais);

                    $sql_campos_adicionais = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$json_campos_adicionais' WHERE os = $os";
                }

                $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);
            }
        }

        if ($login_fabrica == 1 && isset($_POST['numero_ad']) && isset($_POST['numero_coleta'])) {
            $sql_cp = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
            $res_cp = pg_query($con, $sql_cp);
            $campos_adicionais_ad = json_decode(pg_fetch_result($res_cp, 0, 'campos_adicionais'), true);
            $campos_adicionais_ad['numero_ad'] = $_POST['numero_ad'];
            $campos_adicionais_ad['numero_coleta'] = $_POST['numero_coleta'];
            $campos_adicionais_ad = json_encode($campos_adicionais_ad);
            $sql_campos_adicionais_ad = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais_ad' WHERE os = $os";
            $res_campos_adicionais_ad = pg_query($con, $sql_campos_adicionais_ad);
        }

        //auditoria de numero de serie
        if($login_fabrica == 74 and $numero_serie_obrigatorio == 't' and strlen(trim($_POST['produto_serie']))>0){
            $os = pg_fetch_result($res, 0, 'os');

            $sql_numero_serie = "select numero_serie from tbl_numero_serie where serie= '".$_POST['produto_serie']."' and referencia_produto = '$referencia' and fabrica = $login_fabrica;";
            $res_numero_serie = pg_query($con, $sql_numero_serie);

            if(pg_num_rows($res_numero_serie)==0){
                $sql = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status)
                        VALUES($os,102,'Os em auditoria de Número de Série',$login_fabrica);";
                $query = @pg_query($con,$sql);

            }
        }
        //auditoria de numero de serie

        if ($login_fabrica == 74 and !empty($data_nascimento)) {
            if (empty($os)) {
                $os = pg_fetch_result($res, 0, 'os');
            }

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

                $sql_c_extra = "UPDATE tbl_os_campo_extra SET campos_adicionais = E'{$c_adicionais_new}' WHERE os = $os";
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

    /**
     * Verificação de alteração no cadastro da OS.
     * - Mandar email para Mallory sempre que alterar a os
     * - Para Esmaltec, grava na interação a OS, caso houve modificação
     */
    if(in_array($login_fabrica,array(30,72))){

		foreach($dados_antes AS $key => $value){
			if($dados_depois[$key] != $value){
				$diferenca[$key] = $dados_depois[$key];
			}
		}

        $sql_admin = "SELECT nome_completo FROM tbl_admin where admin = $login_admin ";
        $res_admin = pg_query($con, $sql_admin);
        if(pg_num_rows($res_admin)){
            $nome_completo = pg_fetch_result($res_admin, 0, 'nome_completo');
            $msg .= "A O.S $os foi alterado por $nome_completo. <br />";
        }

        $msg .= "Os campos alterados foram:<br />";

        foreach($diferenca as $indice=>$valor){
            $valor_de       = $dados_antes["$indice"];
            $valor_para     = $dados_depois["$indice"];

	    if(strlen($valor_de) == 0 AND strlen($valor_para) == 0){
                continue;
            }

            if(strtoupper($valor_de) == strtoupper($valor_para)){
                continue;
            }

	    $valor_de = (strlen($valor_de) == 0) ? "VAZIO" : $valor_de;
	    $valor_para = (strlen($valor_para) == 0) ? "VAZIO" : $valor_para;

		if($indice == 'produto'){
			$msg .= "PRODUTO ". " de ". $_POST['produto_descricao_anterior'] . " para " . $_POST['produto_descricao'] . "<br />";
		}

		if($indice == 'admin' OR $indice == "admin_altera"){

			if($valor_de == "VAZIO"){
				$sql = "SELECT tbl_admin.nome_completo AS admin_depois,
												tbl_admin.fabrica
										FROM    tbl_admin
										WHERE tbl_admin.admin = $valor_para";
				$res = pg_query($con,$sql);
				$msg .= "ADMIN alterado de VAZIO para ".pg_fetch_result($res,0,admin_depois);
			}else{
				$sql = "SELECT  antes.admin_antes,
						depois.admin_depois
					FROM (
						SELECT tbl_admin.nome_completo AS admin_antes,
							tbl_admin.fabrica
						FROM    tbl_admin
						WHERE tbl_admin.admin = $valor_de
					) antes JOIN (
						SELECT tbl_admin.nome_completo AS admin_depois,
							tbl_admin.fabrica
						FROM    tbl_admin
						WHERE tbl_admin.admin = $valor_para
					) depois USING(fabrica)
					WHERE fabrica = $login_fabrica
				";
				 $res = pg_query($con,$sql);
				 $msg .= "ADMIN de ".pg_fetch_result($res,0,admin_antes)." para ".pg_fetch_result($res,0,admin_depois);
			}

		}

	    if($indice == 'tipo_atendimento'){
			if((int)$valor_de and (int)$valor_para) {
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

            if (in_array($indice, $retirar)){
                continue;
            }

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
            $msg .= strtoupper($indice_limpo) . " de ". $valor_de . " para " . $valor_para . "<br />";
        }

        if($login_fabrica == 72){
            $msg .= "Orientação SAC ao Posto Autorizado: ".$_POST['orientacao_sac'];

            $posto_emails = $dados_depois['posto'];
			if(strlen($posto_emails) > 0) {
				$sql_email = "select contato_email from tbl_posto_fabrica
							where posto = $posto_emails and fabrica = $login_fabrica";
				$res_email = pg_query($con, $sql_email);
					if(pg_num_rows($res_email)>0){
						$contato_email = pg_fetch_result($res_email, 0, 'contato_email');
					}
				//Envia email para o posto.
				$assunto   = 'Alteração Cadastro de Os';
				$headers = 'From: helpdesk@telecontrol.com.br' . "\r\n" .
				'Reply-To: helpdesk@telecontrol.com.br' . "\r\n" .
				'X-Mailer: PHP/';

				mail($contato_email, $assunto, $msg, $headers);
			}
        }elseif(($login_fabrica == 30 || strlen($msg) > 0) and strlen($os) > 0 ){
            $sql = "
                INSERT INTO tbl_os_interacao (
                    programa,
                    os,
                    data,
                    admin,
                    comentario,
                    interno,
                    fabrica
                ) VALUES (
                    '$programa_insert',
                    $os,
                    CURRENT_TIMESTAMP,
                    $login_admin,
                    '$msg',
                    TRUE,
                    $login_fabrica
                );
            ";
            $res = pg_query($con,$sql);
        }
    }

	if(strlen($os) > 0) {
		//funcao de auditor -- todas as fabricas
		//auditorLog($os,$dados_antes,$dados_depois,"tbl_os",$PHP_SELF,'update');
    }
        $msg_erro .= pg_errormessage($con);
      if(strpos($msg_erro,'data_nf_superior_data_abertura')===true) {
          $msg_erro = "Data da compra não pode ser maior que a data de abertura";
      }

      if (strlen ($msg_erro) == 0) {

        if (strlen($os) == 0) {
            $res = pg_query ($con,"SELECT CURRVAL ('seq_os')");
            $os  = pg_fetch_result ($res,0,0);

            if ($login_fabrica == 30) {
                $sqlPosto = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                WHERE tbl_posto.posto = {$posto}";
                $resPosto = pg_query($con, $sqlPosto);

                $postoCodigo = pg_fetch_result($resPosto, 0, "codigo");
                $postoNome   = pg_fetch_result($resPosto, 0, "nome");

                $sqlProduto = "SELECT referencia, descricao
                FROM tbl_produto
                WHERE fabrica_i = {$login_fabrica}
                AND produto = {$produto}";
                $resProduto = pg_query($con, $sqlProduto);

                $produtoReferencia = pg_fetch_result($resProduto, 0, "referencia");
                $produtoDescricao  = pg_fetch_result($resProduto, 0, "descricao");

                $msgComunicado = "Autorizada {$postoCodigo} - {$postoNome} - A Fábrica ESMALTEC, abriu uma OS para ser atendido pelo seu posto autorizado. Segue as informações da OS: OS nº {$os}, Produto: {$produtoReferencia} - {$produtoDescricao} Consumidor: {$xconsumidor_nome} Favor atender esta OS, qualquer dúvida, entrar em contato com ESMALTEC...";

                $sqlComunicado = "INSERT INTO tbl_comunicado (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem
                    ) VALUES (
                    {$login_fabrica},
                    {$posto},
                    true,
                    'Com. Unico Posto',
                    true,
                    'OS {$os} aberta pela fábrica ESMALTEC',
                    '{$msgComunicado}'
                    )";
				 $resComunicado = pg_query($con, $sqlComunicado);

				 if (pg_last_error()) {
					$msg_erro = pg_last_error();
				}
			}
                ##### HD 1059886

			if(in_array($login_fabrica, array(87, 15))){
				$sql = "SELECT os FROM tbl_os_extra WHERE os = $os";
				$res = @pg_query($con, $sql);

				if(@pg_num_rows($res)==0){
					if($login_fabrica == 15) {
						$sql = "UPDATE tbl_os SET os_numero = $os WHERE os = $os";
						$res = pg_query($con, $sql);
						$sql = "INSERT INTO tbl_os_extra (os, admin_paga_mao_de_obra) VALUES ($os, '$admin_paga_mao_de_obra')";
					}
					else
						$sql = "INSERT INTO tbl_os_extra (hora_tecnica, os) VALUES ($horas_trabalhadas, $os)";

					$res = pg_query($con, $sql);
				}
			}

            /**
             * Auditoria KM Padrão (KM Superior a x .. Alteração manual de KM)
             */

            if ($km_auditoria == "TRUE" or ($login_fabrica == 74 and $qtde_km > 0)) {

                if ($telecontrol_distrib == "t") {

                    \Posvenda\Helpers\Auditoria::gravar($os, 2, "Auditoria de KM", "Em auditoria", $con);
                    
                } else {

                    $sql = "SELECT status_os
                    FROM tbl_os_status
                    WHERE os = $os
                    AND status_os IN (98,99,100)
                    ORDER BY data DESC
                    LIMIT 1";
                    $res = @pg_query ($con,$sql);
                    if (pg_num_rows($res) > 0){
                        $status_os  = pg_fetch_result ($res,0,status_os);
                    }

                    if ( (pg_num_rows($res) == 0 OR $status_os <> "98") || $qtde_km <> $qtde_km2 ) {
                        $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,98,'$obs_km','t')";
                        $res =  pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }

                }
            }

            /* Auditoria de KM */
            /* Se o Atendimento for com Deslocamento irá entrar em Auditoria de KM - Independentemente da quilometragem informada */
            if($login_fabrica == 128){
                $obs_km = "Auditoria de KM";
                $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,98,'$obs_km','t')";
                $res =  pg_query ($con,$sql);
            }

            if ($login_fabrica == 114) {
                \Posvenda\Helpers\Auditoria::gravar($os, 2, "Auditoria de KM", "Em auditoria", $con);
            }

            // HD 52202 comentei, não sei por que atualizar de novo depois de inserir
            /*
            $sql = "UPDATE tbl_os SET consumidor_nome = tbl_cliente.nome WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.cliente = tbl_cliente.cliente";
            $res = @pg_query ($con,$sql);*/

            $sql = "UPDATE tbl_os SET
            consumidor_cidade = tbl_cidade.nome  ,
            consumidor_estado = tbl_cidade.estado
            FROM  tbl_cliente
            JOIN  tbl_cidade on tbl_cliente.cidade = tbl_cidade.cidade
            WHERE tbl_os.os = $os
            AND   tbl_os.cliente IS NOT NULL
            AND   tbl_os.consumidor_cidade IS NULL
            AND   tbl_os.cliente = tbl_cliente.cliente ";

            $res = pg_query ($con,$sql);

            if (strlen ($consumidor_endereco)   == 0) {$consumidor_endereco     = "null";} else {$consumidor_endereco   = "'$consumidor_endereco'";}
            if (strlen ($consumidor_numero)     == 0) {$consumidor_numero       = "null";} else {$consumidor_numero     = "'$consumidor_numero'";}
            if (strlen ($consumidor_complemento)== 0) {$consumidor_complemento  = "null";} else {$consumidor_complemento= "'$consumidor_complemento'";}
            if (strlen ($consumidor_bairro)     == 0) {$consumidor_bairro       = "null";} else {$consumidor_bairro     = "'$consumidor_bairro'" ; }
            if (strlen ($consumidor_cep)        == 0) {$consumidor_cep          = "null";} else {$consumidor_cep        = "'" . preg_replace ('/\D/', '', $consumidor_cep) . "'";}
            if (strlen ($consumidor_cidade)     == 0) {$consumidor_cidade       = "null";} else { $consumidor_cidade    = "'$consumidor_cidade'";}
            if (strlen ($consumidor_estado)     == 0) {$consumidor_estado       = "null";} else { $consumidor_estado    = "'$consumidor_estado'";}

            $sql = "UPDATE tbl_os SET
            consumidor_endereco    = $consumidor_endereco       ,
            consumidor_numero      = $consumidor_numero         ,
            consumidor_complemento = $consumidor_complemento    ,
            consumidor_bairro      = $consumidor_bairro         ,
            consumidor_cep         = $consumidor_cep            ,
            consumidor_cidade      = $consumidor_cidade         ,
            consumidor_estado      = $consumidor_estado
            WHERE tbl_os.os = $os ";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_last_error($con);
		}

        if($login_fabrica == 30){
            if ($km_auditoria == "TRUE") {
                $sql = "SELECT status_os
                FROM tbl_os_status
                WHERE os = $os
                AND status_os IN (98,99,100)
                ORDER BY data DESC
                LIMIT 1";

                $res = @pg_query ($con,$sql);
                if (pg_num_rows($res) > 0){
                    $status_os  = pg_fetch_result ($res,0,status_os);
                }

                $obs_km = " Alteração manual de km de $consultaKM km para ".number_format($qtde_km,2,',','.')." km Admin: $login_admin - $nome_completo. ";

                if ( (pg_num_rows($res) == 0) || ($qtde_km <> $qtd_km && $status_os <> 98) ) {

                    $sql = "INSERT INTO tbl_os_status (os,status_os,observacao,automatico) VALUES ($os,98,'$obs_km','t')";
                    $res =  pg_query ($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }
            }
	}
		if ($login_fabrica == 91 and !empty($os)) {
			$data_fabricacao = $_POST['data_fabricacao'];
			$sql = "SELECT os FROM tbl_os_extra where os = $os";
			$res = pg_query($con,$sql);
			if ( pg_num_rows($res) == 0 ) {
				if (strlen($data_fabricacao)>0){
					list($di, $mi, $yi) = explode("/", $data_fabricacao);
					if (!checkdate($mi,$di,$yi)){
						$msg_erro .= "Data de Fabricação Inválida";
					}

					if (strlen($msg_erro) == 0){
						$xdata_fabricacao = fnc_formata_data_pg(trim($data_fabricacao));
					}

					if (strlen($msg_erro) == 0) {
					  $sql = "INSERT INTO tbl_os_extra(os,data_fabricacao) VALUES ($os, $xdata_fabricacao)";
					  $res = pg_query($con,$sql);
					}
				}else{
					$msg_erro = "Informe a Data de Fabricação";
				}
			}else{
				 if (strlen($data_fabricacao)>0){
					list($di, $mi, $yi) = explode("/", $data_fabricacao);
					if (!checkdate($mi,$di,$yi)){
						$msg_erro .= "Data de Fabricação Inválida";
					}

					if (strlen($msg_erro) == 0){
						$xdata_fabricacao = fnc_formata_data_pg(trim($data_fabricacao));
					}

					if (strlen($msg_erro) == 0) {
					  $sql = "UPDATE tbl_os_extra set data_fabricacao = $xdata_fabricacao WHERE os = $os ";
					  $res = pg_query($con,$sql);
					}
				}
			}
		}

            if(strlen($msg_erro)==0){
                //HD 23041 - Rotina de vários defeitos para uma única OS.
                if($login_fabrica==19) {
                    // HD 28155
                    if ($tipo_atendimento <> 6){
										$numero_vezes = 100;
										$array_integridade = array();
										for ($i=0;$i<$numero_vezes;$i++) {
											$int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);
											if ( $i <> $int_reclamado and strlen($int_reclamado) >0){
												array_push($array_integridade,$int_reclamado);
											}
											if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
											if (strlen($int_reclamado)==0) continue;

											$aux_defeito_reclamado = $int_reclamado;



											$sql = "SELECT defeito_constatado_reclamado
											FROM tbl_os_defeito_reclamado_constatado
											WHERE os                = $os
											AND   defeito_reclamado = $aux_defeito_reclamado";
											$res = @pg_query ($con,$sql);
											$msg_erro .= pg_errormessage($con);
											if(@pg_num_rows($res)==0){
												$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
													os,
													defeito_reclamado,
													fabrica
													)VALUES(
													$os,
													$aux_defeito_reclamado,
													$login_fabrica
													)
												 ";
												 $res = @pg_query ($con,$sql);
												 $msg_erro .= pg_errormessage($con);
												}
										}
                                if (count($array_integridade) > 0) {
										// HD 33303
    								$lista_defeitos = implode($array_integridade,",");
    								$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
    								WHERE os = $os
    								AND   defeito_reclamado NOT IN ($lista_defeitos) ";
    								$res = @pg_query ($con,$sql);
    								$msg_erro .= pg_errormessage($con);
                                }
														//o defeito reclamado recebe o primeiro defeito constatado.
														//verifica se já tem defeito cadastrado

								$sqld = "SELECT *
								FROM tbl_os_defeito_reclamado_constatado
								WHERE os = $os";
								$res = @pg_query ($con,$sqld);
								$dberr = pg_errormessage($con);
								if(strlen($dberr)>0)
									$msg_erro .= "Selecione o Defeito Reclamado. <br>";

								if(@pg_num_rows($res)==0){
									$msg_erro .= "Quando lançar o defeito reclamado é necessário clicar em adicionar defeito. <br>";
								}
					}

					if ($tipo_atendimento == 6 and $defeito_reclamado <> 0){
							$numero_vezes = 100;
							$array_integridade = array();
							for ($i=0;$i<$numero_vezes;$i++) {
								$int_reclamado = trim($_POST["integridade_defeito_reclamado_$i"]);
								if ( $i <> $int_reclamado and strlen($int_reclamado) >0){
									array_push($array_integridade,$int_reclamado);
								}
								if (!isset($_POST["integridade_defeito_reclamado_$i"])) continue;
								if (strlen($int_reclamado)==0) continue;

								$aux_defeito_reclamado = $int_reclamado;

								$sql = "SELECT defeito_constatado_reclamado
								FROM tbl_os_defeito_reclamado_constatado
								WHERE os                = $os
								AND   defeito_reclamado = $aux_defeito_reclamado";
								$res = @pg_query ($con,$sql);
								$msg_erro .= pg_errormessage($con);
								if(@pg_num_rows($res)==0){
											$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
												os,
												defeito_reclamado,
												fabrica
												)VALUES(
												$os,
												$aux_defeito_reclamado,
												$login_fabrica
												)
								 ";
								 $res = @pg_query ($con,$sql);
								 $msg_erro .= pg_errormessage($con);
								}
						}

                        if (count($array_integridade) > 0) {
												// HD 33303
    						$lista_defeitos = implode($array_integridade,",");
    						$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
    						WHERE os = $os
    						AND   defeito_reclamado NOT IN ($lista_defeitos) ";
    						$res = @pg_query ($con,$sql);
    						$msg_erro .= pg_errormessage($con);

                        }

												//o defeito reclamado recebe o primeiro defeito constatado.
												//verifica se já tem defeito cadastrado
						$sqld = "SELECT *
						FROM tbl_os_defeito_reclamado_constatado
						WHERE os = $os";
						$res = @pg_query ($con,$sqld);
						$msg_erro .= pg_errormessage($con);

						if (@pg_num_rows($res) == 0) {
							$msg_erro .= "Quando lançar o defeito reclamado é necessário clicar em adicionar defeito. <br>";
						}
					}
				}
			}

            // HD-947073
				if(!strlen($msg_erro) AND $login_fabrica == 15) {
					$sql = "SELECT sua_os
					FROM tbl_os
					WHERE os = $os";

					$res = pg_query ($con,$sql);

								// Altera a OS para a sua_os caso a sua_os estiver em branco
					if(!pg_fetch_result($res, 0, sua_os)) {

						$sql = "UPDATE tbl_os
						SET sua_os = $os
						WHERE os = $os";

						$res       = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}

            /**
            *  HD-1921299 - Cancelamento de mão-de-obra
            *  - Gravará o Status 81 na OS que estiver marcada para
            *  cancelar a cobrança de mão-de-obra
            *
            * @author William Ap. Brandino
            * @fabrica Cadence
            */

            if($login_fabrica == 35 && $cancela_mao_obra == "ok"){
                if(strlen($motivo_cancela_mao_obra) > 0 && strlen($protocolo_cancela_mao_obra) > 0){
                    $sqlP = "
                    SELECT  motivo
                    FROM    tbl_motivo_recusa
                    WHERE   motivo_recusa   = $motivo_cancela_mao_obra
                    AND     fabrica         = $login_fabrica
                    ";
                    $resP = pg_query($con,$sqlP);
                    $obs_motivo = pg_fetch_result($resP,0,motivo);

                    $sql = "INSERT INTO tbl_os_status(
                        os,
                        status_os,
                        observacao,
                        admin
                        ) VALUES (
                        $os,
                        81,
                        'Cancelamento de mão-de-obra Protocolo: $protocolo_cancela_mao_obra Motivo: $obs_motivo',
                        $login_admin
                        )
				 ";
				 $res = pg_query($con,$sql);
				 $msg_erro .= pg_errormessage($con);
				}
			}



            if(in_array($login_fabrica, array(59,101,104)) && strlen($campos_adicionais) > 0){
                $sql = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os" ;
                }else{
                    $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES ($os,$login_fabrica,'$campos_adicionais')";
                }

                $res       = pg_query($con,$sql);
                $msg_erro .= pg_last_error($con);
            }

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os($os, $login_fabrica)";//HD 256659

				// HD-947073 (Cancela a validação para a latinatec)
				if($login_fabrica != 15) {
					$res = @pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

                if ($login_fabrica == 19) {

                      if (!empty($_POST['garantia_lorenzetti'])) {

                        $notaCompleta = str_pad($_POST['nota_fiscal'] , 7 , '0' , STR_PAD_LEFT);

                        $sqlReincidenteGarantia = "SELECT tbl_os.os, tbl_os.garantia_produto
                                                   FROM tbl_os
                                                   JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
                                                   AND UPPER(tbl_produto.referencia) = UPPER('{$produto_referencia}')
                                                   WHERE UPPER(tbl_os.nota_fiscal) = UPPER('{$notaCompleta}')
                                                   AND tbl_os.data_nf = {$data_nf}
                                                   AND tbl_os.consumidor_cpf = {$xconsumidor_cpf}
                                                   AND tbl_os.fabrica = {$login_fabrica}
                                                   AND tbl_os.tipo_atendimento = 339
                                                   AND tbl_os.os != {$os}";
                        $resReincidenteGarantia = pg_query($con, $sqlReincidenteGarantia);

                        if (pg_num_rows($resReincidenteGarantia) > 0) {

                          if ($tipo_atendimento != 339) {

                            $garantia_lorenzetti = pg_fetch_result($resReincidenteGarantia, 0, 'garantia_produto');

                            $sqlDesconsideraReincidencia = "UPDATE tbl_os 
                                                            SET os_reincidente = false
                                                            WHERE os = {$os};

                                                            UPDATE tbl_os_extra
                                                            SET os_reincidente = null
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

                        $notaCompleta = str_pad($_POST['nota_fiscal'] , 7 , '0' , STR_PAD_LEFT);

                        $sqlReincidenteGarantia = "SELECT tbl_os.os, tbl_os.garantia_produto
                                                   FROM tbl_os
                                                   JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
                                                   AND UPPER(tbl_produto.referencia) = UPPER('{$produto_referencia}')
                                                   WHERE UPPER(tbl_os.nota_fiscal) = UPPER('{$notaCompleta}')
                                                   AND tbl_os.data_nf = {$data_nf}
                                                   AND tbl_os.consumidor_cpf = {$xconsumidor_cpf}
                                                   AND tbl_os.fabrica = {$login_fabrica}
                                                   AND tbl_os.tipo_atendimento = 339
                                                   AND tbl_os.os != {$os}";
                        $resReincidenteGarantia = pg_query($con, $sqlReincidenteGarantia);

                        if (pg_num_rows($resReincidenteGarantia) > 0) {

                          if ($tipo_atendimento != 339) {

                            $garantiaPadrao = pg_fetch_result($resReincidenteGarantia, 0, 'garantia_produto');

                            $sqlDesconsideraReincidencia = "UPDATE tbl_os 
                                                            SET os_reincidente = false
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

                        $sqlForaGarantia = "SELECT tipo_atendimento
                            FROM tbl_tipo_atendimento
                            WHERE tipo_atendimento = {$tipo_atendimento}
                            AND fora_garantia IS TRUE";
                        $resForaGarantia = pg_query($con, $sqlForaGarantia);

                        if (pg_num_rows($resForaGarantia) == 0) {

                            if (empty($msg_erro)) {
                                $data_fim_garantia   = date('Y-m-d', strtotime("+{$garantiaPadrao} months", strtotime(formata_data($_POST['data_nf']))));

                                if (date('Y-m-d') > $data_fim_garantia) {

                                    $msg_erro .= "Este produto está fora da garantia de {$garantiaPadrao} meses";

                                } else {

                                    $sqlGarantiaProduto = "UPDATE tbl_os
                                                           SET garantia_produto = tbl_produto.garantia
                                                           FROM tbl_produto
                                                           WHERE tbl_os.os = {$os}
                                                           AND tbl_os.fabrica = {$login_fabrica}
                                                           AND tbl_os.produto = tbl_produto.produto";
                                    pg_query($con, $sqlGarantiaProduto);
                                } 
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
                                                         SET os_reincidente = false
                                                         WHERE os = {$os};

                                                         UPDATE tbl_os_extra
                                                         SET os_reincidente = null
                                                         WHERE os = {$os};";
                         pg_query($con, $sqlDesconsideraReincidencia);

                      }

                }

            }

            if (strlen($msg_erro) == 0 and ($login_fabrica == 11 or $login_fabrica == 172) and $osx <> "update")
            {
                $sql_obs             = "SELECT orientacao_sac FROM tbl_os_extra WHERE os = $os";
                $res_obs             = pg_query($con, $sql_obs);
                $xorientacao_sac_aux = pg_fetch_result($res_obs, 0, "orientacao_sac");

                $sql_usario  = "SELECT login FROM tbl_admin WHERE admin = $login_admin";
                $res_usuario = pg_query($con, $sql_usario);
                $usuario     = pg_fetch_result($res_usuario, 0, "login");

                $data_hoje        = date("d/m/Y H:i:s");
                $xorientacao_sac  = "<p>OS Aberta pelo Admin: $usuario</p>";
                $xorientacao_sac .= "<p>Data: $data_hoje</p>";
                $xorientacao_sac .= $xorientacao_sac_aux;

                $sql_osac       = "UPDATE  tbl_os_extra SET orientacao_sac = '$xorientacao_sac' WHERE tbl_os_extra.os = $os;";
                $res_osac       = pg_query($con, $sql_osac);
                $msg_erro_osac .= pg_last_error();
            }

            if ($login_fabrica == 52){
                $ponto_referencia = (isset($_POST['ponto_referencia'])) ? trim($_POST['ponto_referencia']) : '' ;
                $sql_osac       = "UPDATE  tbl_os_extra SET obs = '$ponto_referencia' WHERE tbl_os_extra.os = $os;";
                $res_osac       = pg_query($con, $sql_osac);
            }

            if ($login_fabrica == 122) {
                $consumidor_cpd     = $_POST['consumidor_cpd'];
                $consumidor_contato = $_POST['consumidor_contato'];

                $obs_adicionais = "{\"consumidor_cpd\":\"$consumidor_cpd\",\"consumidor_contato\":\"$consumidor_contato\"}";

                $sql = "UPDATE tbl_os_extra
                SET obs_adicionais = '$obs_adicionais' where os=$os" ;
                $res = pg_query($con,$sql);
            }

            #--------- grava OS_EXTRA ------------------
            if (strlen ($msg_erro) == 0) {
                $taxa_visita                = str_replace (",",".",trim ($_POST['taxa_visita']));
                $visita_por_km              = trim ($_POST['visita_por_km']);
                $valor_por_km               = str_replace (",",".",trim ($_POST['valor_por_km']));

                $hora_tecnica               = str_replace (",",".",trim ($_POST['hora_tecnica']));

                $regulagem_peso_padrao      = str_replace (".","",trim ($_POST['regulagem_peso_padrao']));
                $regulagem_peso_padrao      = str_replace (",",".",$regulagem_peso_padrao);

                $certificado_conformidade   = str_replace (".","",trim ($_POST['certificado_conformidade']));
                $certificado_conformidade   = str_replace (",",".",$certificado_conformidade);

                $valor_diaria               = str_replace (".","",trim ($_POST['valor_diaria']));
                $valor_diaria               = str_replace (",",".",$valor_diaria);

                $condicao                   = trim ($_POST['condicao']);

                #Hd 311411
                $data_conserto              = trim($_POST['data_conserto']);

                if(strlen($condicao)==0){
                    if($login_fabrica ==7 ) {
                        $msg_erro .= "Por favor selecione a condição de pagamento.<BR>";
                    }else{
                        $xcondicao = 'null';
                        $xtabela   = 'null';
                    }

                } else {

                    $xcondicao = $condicao;

                    $sql = "SELECT tabela
                    FROM tbl_condicao
                    WHERE fabrica = $login_fabrica
                    AND condicao = $condicao; ";
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if (pg_num_rows($res) > 0) {
                        $xtabela = pg_fetch_result($res,0,'tabela');
                    }

                    if (strlen($xtabela)==0){
                        $xtabela = "null";
                    }

                }

                if (strlen ($taxa_visita)               == 0) $taxa_visita                  = '0';
                if (strlen ($visita_por_km)             == 0) $visita_por_km                = 'f';
                if (strlen ($valor_por_km)              == 0) $valor_por_km                 = '0';
                if (strlen ($hora_tecnica)              == 0) $hora_tecnica                 = '0';
                if (strlen ($regulagem_peso_padrao)     == 0) $regulagem_peso_padrao        = '0';
                if (strlen ($certificado_conformidade)  == 0) $certificado_conformidade     = '0';
                if (strlen ($valor_diaria)              == 0) $valor_diaria                 = '0';

                $cobrar_deslocamento    = trim ($_POST['cobrar_deslocamento']);
                $cobrar_hora_diaria     = trim ($_POST['cobrar_hora_diaria']);

                $desconto_deslocamento  = str_replace (",",".",trim ($_POST['desconto_deslocamento']));
                $desconto_hora_tecnica  = str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
                $desconto_diaria        = str_replace (",",".",trim ($_POST['desconto_diaria']));
                $desconto_regulagem     = str_replace (",",".",trim ($_POST['desconto_regulagem']));
                $desconto_certificado   = str_replace (",",".",trim ($_POST['desconto_certificado']));
                $desconto_peca          = str_replace (",",".",trim ($_POST['desconto_peca']));

                $cobrar_regulagem       = trim ($_POST['cobrar_regulagem']);
                $cobrar_certificado     = trim ($_POST['cobrar_certificado']);

                $sqlt ="SELECT tipo_posto, consumidor_revenda, os_numero
                FROM tbl_os
                JOIN tbl_posto_fabrica USING(posto)
                WHERE tbl_os.os = $os
                AND   tbl_posto_fabrica.fabrica = $login_fabrica";
                $rest = pg_query($con,$sqlt);
                $tipo_posto         = pg_fetch_result($rest,0,tipo_posto);
                $consumidor_revenda = pg_fetch_result($rest,0,consumidor_revenda);
                $os_numero          = pg_fetch_result($rest,0,os_numero);

                if (strtoupper($consumidor_revenda) == 'R' and $login_fabrica == 7){
                    $os_manutencao = 't';
                }

                if ($tipo_posto == 215 or $tipo_posto == 214){
                    if ($desconto_deslocamento>7){
                        $msg_erro .= "O desconto máximo permitido para deslocamento é 7%.<br>";
                    }
                    if ($desconto_hora_tecnica>7){
                        $msg_erro .= "O desconto máximo permitido para hora técnica é 7%.<br>";
                    }
                    if ($desconto_diaria>7){
                        $msg_erro .= "O desconto máximo permitido para diára é 7%.<br>";
                    }
                    if ($desconto_regulagem>7){
                        $msg_erro .= "O desconto máximo permitido para regulagem é 7%.<br>";
                    }
                    if ($desconto_certificado>7){
                        $msg_erro .= "O desconto máximo permitido para o certificado é 7%.<br>";
                    }
                }

                if (strlen($veiculo)==0){
                    $xveiculo = "NULL";
                }else{
                    $xveiculo = "'$veiculo'";
                    if ($veiculo == 'carro'){
                        $valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_carro']));
                    }
                    if ($veiculo == 'caminhao'){
                        $valor_por_km =  str_replace (",",".",trim ($_POST['valor_por_km_caminhao']));
                    }
                }

                if (strlen($valor_por_km)>0){
                    $xvalor_por_km = $valor_por_km;
                    $xvisita_por_km = "'t'";
                }else{
                    $xvalor_por_km = "0";
                    $xvisita_por_km = "'f'";
                }

                if (strlen($taxa_visita)>0){
                    $xtaxa_visita = $taxa_visita;
                }else{
                    $xtaxa_visita = '0';
                }

                /* HD 29838 */
                if ($tipo_atendimento == 63){
                    $cobrar_deslocamento = 'isento';
                }

                if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0){
                    $xvisita_por_km = "'f'";
                    $xvalor_por_km = "0";
                    $xtaxa_visita = '0';
                    $xveiculo = "NULL";
                }elseif ($cobrar_deslocamento == 'valor_por_km'){
                    $xvisita_por_km = "'t'";
                    $xtaxa_visita = '0';
                }elseif ($cobrar_deslocamento == 'taxa_visita'){
                    $xvisita_por_km = "'f'";
                    $xvalor_por_km = "0";
                }

                if(strlen($valor_diaria) > 0){
                    $xvalor_diaria = $valor_diaria;
                }else{
                    $xvalor_diaria = '0';
                }

                if(strlen($hora_tecnica) > 0){
                    $xhora_tecnica = $hora_tecnica;
                }else{
                    $xhora_tecnica = '0';
                }

                if ($cobrar_hora_diaria == 'isento' OR strlen($cobrar_hora_diaria)==0){
                    $xhora_tecnica = '0';
                    $xvalor_diaria = '0';
                }elseif ($cobrar_hora_diaria == 'diaria'){
                    $xhora_tecnica = '0';
                }elseif ($cobrar_hora_diaria == 'hora'){
                    $xvalor_diaria = '0';
                }

                if($login_fabrica == 87){
                    $xhora_tecnica = $horas_trabalhadas;
                }

                if(strlen($regulagem_peso_padrao) > 0 and $cobrar_regulagem == 't'){
                    $xregulagem_peso_padrao = $regulagem_peso_padrao;
                }else{
                    $xregulagem_peso_padrao = '0';
                }

                if(strlen($certificado_conformidade) > 0 and $cobrar_certificado == 't'){
                    $xcertificado_conformidade = $certificado_conformidade;
                }else{
                    $xcertificado_conformidade = "0";
                }

                /* Descontos */
                if(strlen($desconto_deslocamento) > 0){
                    $desconto_deslocamento = $desconto_deslocamento;
                }else{
                    $desconto_deslocamento = '0';
                }

                if(strlen($desconto_hora_tecnica) > 0){
                    $desconto_hora_tecnica = $desconto_hora_tecnica;
                }else{
                    $desconto_hora_tecnica = '0';
                }

                if(strlen($desconto_diaria) > 0){
                    $desconto_diaria = $desconto_diaria;
                }else{
                    $desconto_diaria = '0';
                }

                if(strlen($desconto_regulagem) > 0){
                    $desconto_regulagem = $desconto_regulagem;
                }else{
                    $desconto_regulagem = '0';
                }

                if(strlen($desconto_certificado) > 0){
                    $desconto_certificado = $desconto_certificado;
                }else{
                    $desconto_certificado = '0';
                }

                if(strlen($desconto_peca) > 0){
                    $desconto_peca = $desconto_peca;
                }else{
                    $desconto_peca = '0';
                }

                //hd 11083 7/1/2008
                if($login_fabrica == 3){
                    if (strlen(trim($orientacao_sac))>0 AND trim($orientacao_sac)!='null' ){
                        $orientacao_sac =  date("d/m/Y H:i")." - ".$orientacao_sac;
                        $sqlUP = "UPDATE  tbl_os_extra SET
                        orientacao_sac          =  CASE WHEN orientacao_sac IS NULL OR orientacao_sac = 'null' THEN '' ELSE orientacao_sac || ' \n' END || trim('$orientacao_sac') ,
                        taxa_visita              = $xtaxa_visita                               ,
                        visita_por_km            = $xvisita_por_km                             ,
                        hora_tecnica             = $xhora_tecnica                              ,
                        regulagem_peso_padrao    = $xregulagem_peso_padrao                     ,
                        certificado_conformidade = $xcertificado_conformidade                  ,
                        valor_diaria             = $xvalor_diaria                              ,
                        admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";
                    }
                }else{
                    if ($login_fabrica == 11 or $login_fabrica == 172) {

                        $sql_obs = "SELECT orientacao_sac from tbl_os_extra where os = $os";
                        $res_obs = pg_query($con,$sql_obs);
                        $orientacao_sac_aux         = pg_fetch_result($res_obs,0,orientacao_sac);
                        $sql_usario = "SELECT login from tbl_admin where admin = $login_admin";
                        $res_usuario = pg_query($con,$sql_usario);
                        $usuario         = pg_fetch_result($res_usuario,0,login);


                        $data_hoje = date("d/m/Y H:i:s");
                        if ($osx == "update")
                        {
                            $orientacao_sac .= "<p>Os alterada pelo admin: $usuario</p>";
                            $orientacao_sac .= "<p>Data: $data_hoje</p>";
                        }
                        $orientacao_sac .= $orientacao_sac_aux;
                    }

                    $or_sac = ($orientacao_sac != 'null') ? "'".pg_escape_string($con,$orientacao_sac)."'" :  $orientacao_sac;

                    if ($login_fabrica == 6 && strlen($orientacao_sac) > 0) {
                        $or_sac = "'$orientacao_sac'";
                    }

                    $sqlUP = "UPDATE  tbl_os_extra SET
                    orientacao_sac           = $or_sac,
                    classificacao_os         = $classificacao_os";
                    if(strlen($pagar_mao_de_obra) == 0){ #97504
                        $sqlUP .= " , admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";
                    }
                }

                if ($os_reincidente == "'t'") {
                    $sqlUP .= ", os_reincidente = $xxxos ";
                }

                if ($sqlUP){
                    $sqlUP .= " WHERE tbl_os_extra.os = $os";

                    $res = pg_query ($con,$sqlUP);
                }

                #if($ip=='187.39.215.117') echo nl2br($sql);
                $msg_erro .= pg_errormessage($con);

                if ($login_fabrica <> 3){

                    if( $login_fabrica == 7 and strlen($condicao)>0) {
                        $sql = "UPDATE tbl_os SET
                        condicao = $condicao
                        WHERE os      = $os
                        AND   fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }

                    if ($os_manutencao == 't' and strlen($os_numero)>0){
                        $sql = "UPDATE tbl_os_revenda SET
                        condicao = $condicao
                        WHERE os_revenda = $os_numero ";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                        $sql = "UPDATE tbl_os SET
                        condicao = $xcondicao,
                        tabela   = $xtabela
                        WHERE os_numero  = $os_numero

                        AND   fabrica    = $login_fabrica";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);

                    }

                    /* ATUALIZACAO: Outros Serviços */
                    $sql = "UPDATE tbl_os_extra SET
                    certificado_conformidade    = $xcertificado_conformidade,
                    desconto_certificado        = $desconto_certificado,
                    desconto_peca               = $desconto_peca
                    WHERE os = $os ";
                    $res = @pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);

                    if (strlen($deslocamento_km)>0){
                        $deslocamento_km = $deslocamento_km;
                    }else{
                        $deslocamento_km = '0';
                    }

                    if($login_fabrica == 30){ //HD-2798091
                        if($qtde_km2 != 0){
                            $deslocamento_km = $qtde_km2;
                        } else {
                            $deslocamento_km = '0';
                        }
                    }

                    #Se não for Filizola, nao alterar o valor do deslocamento
                    if (!in_array($login_fabrica,array(7,30))){ // retirada a fabrica 30 no HD-2798091
                        $deslocamento_km = ' deslocamento_km ';
                    }

                    /* ATUALIZACAO: Deslocamento e Mao de Obra (do técnico) */
                    if ($os_manutencao == 't'){
                        $sql = "UPDATE tbl_os_revenda SET

                        /* DESLOCAMENTO */
                        taxa_visita                 = $xtaxa_visita,
                        visita_por_km               = $xvisita_por_km,
                        valor_por_km                = $xvalor_por_km,
                        veiculo                     = $xveiculo,
                        deslocamento_km             = $deslocamento_km,

                        /* MAO-DE-OBRA */
                        hora_tecnica                = $xhora_tecnica,
                        valor_diaria                = $xvalor_diaria,

                        /* OUTROS SERVIÇOS */
                        regulagem_peso_padrao       = $xregulagem_peso_padrao,
                        /*desconto_regulagem        = $desconto_regulagem, (nao é usado mais desconto, se precisar de desconto tem que criar o campo)*/

                        /* DESCONTOS */
                        desconto_deslocamento       = $desconto_deslocamento,
                        desconto_hora_tecnica       = $desconto_hora_tecnica,
                        desconto_diaria             = $desconto_diaria

                        WHERE os_revenda = $os_numero ";
                    }else{
                        if($login_fabrica ==35){
                            $campos_extra = " obs_adicionais = '$informaemail', ";
                        }
                        $sql = "UPDATE tbl_os_extra SET
                        $campos_extra

                        /* DESLOCAMENTO */
                        taxa_visita                 = $xtaxa_visita,
                        visita_por_km               = $xvisita_por_km,
                        valor_por_km                = $xvalor_por_km,
                        veiculo                     = $xveiculo,
                        deslocamento_km             = $deslocamento_km,

                        /* MAO-DE-OBRA */
                        hora_tecnica                = $xhora_tecnica,
                        valor_diaria                = $xvalor_diaria,

                        /* OUTROS SERVIÇOS */
                        regulagem_peso_padrao       = $xregulagem_peso_padrao,
                        desconto_regulagem          = $desconto_regulagem,

                        /* DESCONTOS */
                        desconto_deslocamento       = $desconto_deslocamento,
                        desconto_hora_tecnica       = $desconto_hora_tecnica,
                        desconto_diaria             = $desconto_diaria
                        WHERE os = $os ";
                    }

                    $res = @pg_query($con,$sql);
                    $msg_erro .= pg_errormessage($con);
                }

                if($login_fabrica==45 and strlen($voltar_fechamento)>0 AND strlen($voltar_finalizada)>0) {
                    $sql = "UPDATE tbl_os SET data_fechamento = '$voltar_fechamento' , finalizada = '$voltar_finalizada'
                    WHERE os      = $os
                    AND   fabrica = $login_fabrica";
                    $res = pg_query ($con,$sql);
                }

                #HD 311411
                if(strlen($msg_erro)==0 && $login_fabrica==6 && strlen($data_conserto)==0) {
                    $sqlConserto = "UPDATE tbl_os SET data_conserto = NULL
                    WHERE os      = $os
                    AND   fabrica = $login_fabrica";
                    $resConserto = pg_query ($con,$sqlConserto);
                #HD 311411 Fim
                }elseif($fabricas_alteram_conserto && strlen($data_conserto)>0){

                    if(strlen($msg_erro)==0){

                        list($dc, $mc, $yc) = explode("/", $data_conserto);
                        if(!checkdate($mc,$dc,$yc))
                            $msg_erro = "Data de Conserto Inválida";

                        if(strlen($msg_erro)==0){

                            $data_conserto = fnc_formata_data_pg($data_conserto);
                            $sqlConserto = "UPDATE tbl_os SET data_conserto = $data_conserto
                            WHERE os      = $os
                            AND   fabrica = $login_fabrica";
                            $resConserto = pg_query ($con,$sqlConserto);
                        }
                    }
                }

                // HD 23217
                if(strlen($msg_erro) ==0 AND $login_fabrica==1){
                    if(strlen($os) >0){
                        $sql="SELECT fn_valida_os_reincidente ($os,$login_fabrica)";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                }

                

                // HD 32726
                if(strlen($msg_erro) ==0 AND $login_fabrica==7){
                    if(strlen($os) >0){
                        $sql="SELECT fn_calcula_os_filizola ($os,$login_fabrica)";
                        $res = pg_query ($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                    }
                }

                if(strlen($msg_erro) ==0 AND $login_fabrica==3){
                    $sql_log = "insert into  tbl_os_log_admin  (os,admin) values ('$os','$login_admin')";
                    $res_log = pg_query($con,$sql_log);
                    $msg_erro .= pg_errormessage($con);

                }

                if($login_fabrica == 127 AND strlen($campos_adicionais) > 0){
                    $sql = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os" ;
                    }else{
                        $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES ($os,$login_fabrica,'$campos_adicionais')";
                    }

                    $res       = pg_query($con,$sql);
                    $msg_erro .= pg_last_error();
                }
                if($login_fabrica == 59 AND strlen($campos_adicionais) > 0){
                    $sql = "SELECT os FROM tbl_os_campo_extra WHERE os = $os";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = $os" ;
                    }else{
                        $sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,campos_adicionais) VALUES ($os,$login_fabrica,'$campos_adicionais')";
                    }
                    $res       = pg_query($con,$sql);
                    $msg_erro .= pg_last_error($con);
                }

                # HD - 725866 - Enviar e-mail para o posto informando a Orientação da Fábrica ORBIS

                if ($login_fabrica == 88 and strlen($orientacao_sac) > 0) {

                    $sql = "select
                    tbl_posto.email,
                    tbl_posto_fabrica.contato_email,
                    tbl_admin.email as email_admin,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.nome_fantasia

                    from tbl_os

                    join tbl_posto_fabrica on (tbl_os.posto = tbl_posto_fabrica.posto
                        and tbl_posto_fabrica.fabrica = $login_fabrica)

						 join tbl_admin on tbl_posto_fabrica.fabrica = tbl_admin.fabrica

						 join tbl_posto on tbl_posto_fabrica.posto = tbl_posto.posto

						 where tbl_admin.fabrica = $login_fabrica
						 and tbl_admin.admin = $login_admin
						 and tbl_os.os = $os and tbl_os.fabrica=$login_fabrica";

											//echo nl2br($sql);
						 $res = pg_query($con, $sql);

						 if (pg_num_rows($res) > 0) {

							$email           = trim(pg_fetch_result($res,0,'email'));
							$contato_email   = trim(pg_fetch_result($res,0,'contato_email'));
							$email_remetente = trim(pg_fetch_result($res,0,'email_admin'));
							$codigo_posto    = trim(pg_fetch_result($res,0,'codigo_posto'));
							$nome_fantasia   = trim(pg_fetch_result($res,0,'nome_fantasia'));


							if (strlen($contato_email) > 0) {

								$email_destinatatio = $contato_email;

							} else if (strlen($email) > 0) {

								$email_destinatatio = $email;
							}

							if(strlen($email_destinatatio)>0){

								$remetente    = "$email_remetente";
								$destinatario = "$email_destinatatio";
								$assunto      = "Orientação da Fábrica";
								$message      = "O Fabricante fez uma orientação na OS - $os.<br><br>
								<b>Orientação:</b> $orientacao_sac<br> ";
								$headers      ="Return-Path: <$email_remetente>\nFrom:".$remetente."\nContent-type: text/html\n";

								mail($destinatario, utf8_encode($assunto), utf8_encode($message), $headers);



							} else {


								$remetente    = "$email_remetente";
								$destinatario = "$email_remetente";
								$assunto      = "Orientação da Fábrica não enviada";
								$message      = "O Posto $nome_fantasia - $codigo_posto não possuí e-mail cadastrado no Sistema Telecontrol<br><br>";
								$headers      ="Return-Path: <$email_remetente>\nFrom:".$remetente."\nContent-type: text/html\n";

								mail($destinatario, utf8_encode($assunto), utf8_encode($message), $headers);

							}

						}

				}

        if (in_array($login_fabrica, array(50,74,91,120,131))) {
			$data_fabricacao = $_POST['data_fabricacao'];
			if(!empty($data_fabricacao)){
				list($d,$m,$y) = explode('/',$data_fabricacao);
				$xdata_fabricacao = "$y-$m-$d";
				$sql = "UPDATE tbl_os_extra SET data_fabricacao = '$xdata_fabricacao' WHERE os = $os";
				$res = pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con) ;
			}
		}

		if($login_fabrica == 117 or $login_fabrica == 128){
			if($garantia_estendida){
				if ($opcao_garantia_estendida == 't' and is_array($_FILES['nf_garantia_estendida']) and $_FILES['nf_garantia_estendida']['name'] != '') {
					$arquivo          = isset($_FILES["nf_garantia_estendida"]) ? $_FILES["nf_garantia_estendida"] : FALSE;
					if(!$s3_ge->uploadFileS3($os, $arquivo)){
						$msg_erro .= "O arquivo de garantia estendida não foi enviado!!! " . $s3_ge->_erro; // . $erroS3;
					}
				}else if( $opcao_garantia_estendida == 't' and $_FILES['nf_garantia_estendida']['name'] == '' ){
					if($login_fabrica == 117){
						if(strlen($os) > 0){
							$sql_garantia = "SELECT certificado_garantia FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os";
							$res_garantia = pg_query($con, $sql_garantia);
							if(pg_num_rows($res_garantia) > 0){
								$garantia_status = pg_fetch_result($res_garantia, 0, certificado_garantia);
								if($garantia_status == 12){
									$tem_anexo = "t";
								}
							}
						}
					}

					if($tem_anexo != "t"){
						$msg_erro .= "Anexar arquivo de garantia estendida";
					}
				}
			}
		}

		// Anexo de arquivo de NF. HD 875979
		if($login_fabrica <> 15){
			if ($anexaNotaFiscal) {


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

                  if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;

                  $qt_anexo++;
                }


				//if (is_array($_FILES['foto_nf']) and $_FILES['foto_nf']['name'] != '') {

					//$anexou = anexaNF($os, $_FILES['foto_nf']);

					//if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;

				//}

			}

			if ($login_fabrica == 42 && in_array($tipo_atendimento, array(103,104,133,134,135))) {//HD 400603
				$fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] = true;
			}

            if ($login_fabrica == 42) {
                $count_anexo = false;
                $amazonTC = new AmazonTC("os", $login_fabrica);
                $amazonTC_it = new AmazonTC("os_item", $login_fabrica);
                //verifica se tem imagem na OS
                $amazonTC->getObjectList("anexo_os_{$login_fabrica}_{$os}_img_os_");
                $files_anexo_os = $amazonTC->files;
                if ($files_anexo_os) {
                    $count_anexo = true;
                }
                //verifica se tem imagem no OS Item
                $amazonTC_it->getObjectList("anexo_os_item_{$login_fabrica}_{$os}_img_os_item_");
                $files_anexo_os_it = $amazonTC_it->files;
                if ($files_anexo_os_it) {
                    $count_anexo = true;
                }
            }

			// HD 350051 - Obrigatoriedade para as que exigem imagem da NF.
			if ($anexaNotaFiscal and !temNF($os, 'bool') and !$msg_erro and
				(($login_fabrica == 43 and $consumidor_revenda == 'C') or // HD 354997 - ImgNF obrig. para 43 só OS Consumidor
				(!in_array($login_fabrica,array(3,43,72,101)) and $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'] == true))) {
                    if ($login_fabrica != 42 && $count_anexo != false) {
				        $msg_erro .= "Não pode ser gravada a OS sem que haja uma imagem da Nota Fiscal.";
                    }
			}
		}

		if (isFabrica(3, 11, 126, 137, 172)) {
			$types = array("png", "jpg", "jpeg", "bmp", "pdf", 'doc', 'docx');

			if($login_fabrica == 126 AND (strlen($_FILES["img_os_1"]["name"]) == 0 AND strlen($_FILES["img_os_2"]["name"]) == 0) ){
				//$msg_erro .= "Por favor inserir anexo da Nota Fiscal <br />";
			}

			foreach ($_FILES as $key => $imagem) {

				if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
					if($key == "img_os_1" || $key == "img_os_2"){
						$type  = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
						if(!in_array($type, $types)){
							$pathinfo = pathinfo($imagem["name"]);
							$type = $pathinfo["extension"];
						}
						if (!in_array($type, $types)) {

							$msg_erro .= "Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, doc e pdf";
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

    if($login_fabrica == 94){
        if($posto_revenda == false){
            $sql = "UPDATE tbl_os_extra SET extrato_geracao = CURRENT_DATE WHERE os = {$os}";
        }else{
            $sql = "UPDATE tbl_os_extra SET extrato = 0 WHERE os = {$os}";
        }
        $res = pg_query($con, $sql);
    }

    if ($login_fabrica == 120 && $tipo_atendimento == 145) {
        if (str_replace("'", "", $consumidor_cidade) != str_replace("'", "", $contato_cidade)) {
            $sql = "UPDATE tbl_os_extra SET percurso_total = 'true' WHERE os = $os";
            $res = pg_query ($con,$sql);
        }
    }

    if ($login_fabrica == 120) {

        $sql = "SELECT valor_km, parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
        $res = pg_query($con,$sql);

        $valor_km = pg_fetch_result($res,0,"valor_km");
        $parametros_adicionais_posto = json_decode(pg_fetch_result($res,0,"parametros_adicionais"),1);
        $km_apartir_de = $parametros_adicionais_posto['km_apartir'];
        $km_apartir = (strlen($km_apartir_de) == 0) ? 0 : $km_apartir_de;

        if ($valor_km == 0 OR strlen($valor_km) == 0) {

            $sql = "SELECT valor_km FROM tbl_fabrica WHERE fabrica = $login_fabrica";
            $res = pg_query($con,$sql);

            $valor_km = pg_fetch_result($res,0,"valor_km");

            if (strlen($valor_km) == 0) {
                $valor_km = 0;
            }

        }

	    $sqlUP = "  UPDATE tbl_os
                    SET
        			    qtde_km_calculada = ((tbl_os.qtde_km - CASE WHEN tbl_os_extra.percurso_total IS TRUE THEN 0  WHEN  tbl_os_extra.percurso_total IS NOT TRUE AND $km_apartir > 0 THEN $km_apartir ELSE 20 END) * $valor_km),
        			    qtde_km = ((tbl_os.qtde_km - CASE WHEN tbl_os_extra.percurso_total IS TRUE THEN 0 WHEN  tbl_os_extra.percurso_total IS NOT TRUE AND $km_apartir > 0 THEN $km_apartir ELSE 20 END) )
        		    FROM tbl_os_extra
        		    WHERE
                        tbl_os.os = tbl_os_extra.os
        		        AND tbl_os.os = $os;
                    UPDATE tbl_os set qtde_km = 0, qtde_km_calculada = 0 where os = $os and qtde_km < 0;";

	    $resUP = pg_query($con, $sqlUP);
        $msg_erro .= pg_last_error($con);

    }

    if(strlen($msg_erro) == 0){
        if($login_fabrica == 91 AND $garantia_diferenciada == "t"){

            if(empty($consumidor_nome) OR $consumidor_nome == "null"){
                $consumidor_nome = "";
            }
            if(empty($consumidor_cpf) OR $consumidor_cpf == "null"){
                $consumidor_cpf = "";
            }
            if(empty($consumidor_endereco) OR $consumidor_endereco == "null"){
                $consumidor_endereco = "''";
            }
            if(empty($consumidor_numero) OR $consumidor_numero == "null"){
                $consumidor_numero = "''";
            }
            if(empty($consumidor_cep) OR $consumidor_cep == "null"){
                $consumidor_cep = "''";
            }
            if(empty($consumidor_cidade) OR $consumidor_cidade == "null"){
                $consumidor_cidade = "''";
            }
            if(empty($consumidor_estado) OR $consumidor_estado == "null"){
                $consumidor_estado = "''";
            }

            if(empty($garantia_diferenciada_mes) OR $garantia_diferenciada_mes == "null"){
                $garantia_diferenciada_mes = 24;
            }
            $sqlInsert = "INSERT INTO tbl_cliente_garantia_estendida(
                            nome,
                            cpf,
                            endereco,
                            numero,
                            cep,
                            cidade,
                            produto,
                            numero_serie,
                            revenda_nome,
                            nota_fiscal,
                            data_compra,
                            estado,
                            garantia_mes,
                            os,
                            fabrica,
                            admin
                        )VALUES(
                            trim ('$consumidor_nome'),
                            trim ('$consumidor_cpf'),
                            trim ('$consumidor_endereco'),
                            trim ('$consumidor_numero'),
                            trim ('$consumidor_cep'),
                            trim ('$consumidor_cidade'),
                            $produto,
                            $produto_serie,
                            trim ('$revenda_nome'),
                            trim ($nota_fiscal),
                            $data_nf,
                            trim ('$consumidor_estado'),
                            $garantia_diferenciada_mes,
                            $os,
                            $login_fabrica,
                            $login_admin
                        )";
            #echo nl2br($sqlInsert);exit;
            $resInsert = pg_query($con, $sqlInsert);
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro .= "Erro ao inativar Produto/Série";
            }
        }

    }

    if ($login_fabrica == 3 && strlen($msg_erro) == 0) {
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
            $msg_erro = " Falha na Ativação Automática do Produto.";
          } else {
            $data_hj = date("d/m/Y H:i");

            $sql_p = "SELECT tbl_os.sua_os, tbl_posto.nome, tbl_posto_fabrica.codigo_posto 
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
	    $sua_os =  pg_fetch_result($res_p, 0, 'sua_os'); 

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
        }     
      }

        if (strlen ($msg_erro) == 0) {

            $res = pg_query ($con,"COMMIT TRANSACTION");
            if ($osx == 'update') {
                $auditorLog->retornaDadosSelect()->enviarLog($osx, "tbl_os", $login_fabrica."*".$os);
            } else {
                $auditorLog->retornaDadosSelect("SELECT * FROM tbl_os WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}")->enviarLog('insert', "tbl_os", $login_fabrica."*".$os);
            }

            if (in_array($login_fabrica, [104,123]) && true === $is_insert) {
                $helper = new \Posvenda\Helpers\Os();

                $sql_posto = "SELECT nome FROM tbl_posto WHERE posto = $posto";
                $qry_posto = pg_query($con, $sql_posto);
                $nome_posto = pg_fetch_result($qry_posto, 0, 'nome');

                if ($login_fabrica == 104) {
                    $msg_abertura_os = "Produto Vonder. Informamos que foi aberto a OS $os para seu produto " . str_replace("'", "", $produto_referencia) . " - $produto_descricao pelo posto autorizado $nome_posto. Caso a data de abertura esteja divergente com a data em que o produto foi deixado no AT, favor entrar em contato: 0800 723 4762 (OPÇÃO 1)";

                    if (!empty($consumidor_email)) {
                        $helper->comunicaConsumidor($consumidor_email, $msg_abertura_os);
                    }
                } else {
                    $consumidor_nome = trim($consumidor_nome);
                    $primeiro_nome = explode(" ", $consumidor_nome);

                    $msg_abertura_os = "Olá $primeiro_nome[0] ! Ordem de Serviço $os registrada para seu produto " . str_replace("'", "", $produto_referencia) . "\nEquipe Positec ( WESCO / WORX ).";
                }

                if (!empty($consumidor_celular)) {
                    $helper->comunicaConsumidor($consumidor_celular, $msg_abertura_os, $login_fabrica, $os);
                }
            }

            if($login_fabrica == 1){//HD-3191657
                $os_antes = $_POST['os'];
                if(strlen(trim($os_antes)) == 0){
                    if(strlen(trim($consumidor_email)) > 0){
                        $sqlSuaOS = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto
                                        FROM tbl_os
                                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                        WHERE os = $os";
                        $resSuaOS = pg_query($con, $sqlSuaOS);

                        if(pg_num_rows($resSuaOS) > 0){
                            $codPosto   = pg_fetch_result($resSuaOS, 0, 'codigo_posto');
                            $suaOS      = pg_fetch_result($resSuaOS, 0, 'sua_os');

                            $codPosto = str_replace (" ","",$codPosto);
                            $codPosto = str_replace (".","",$codPosto);
                            $codPosto = str_replace ("/","",$codPosto);
                            $codPosto = str_replace ("-","",$codPosto);

                            $osBlack = $codPosto.$suaOS;
                        }

                        $from_fabrica  = $consumidor_email;
                        $from_fabrica_descricao = "Stanley Black&Decker - Ordem de Serviço";
                        $assunto  = "Stanley Black&Decker - Ordem de Serviço";
                        $email_admin = "helpdesk@telecontrol.com.br";
                        $mensagem = '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_email_2017.png" alt="http://www.blackedecker.com.br" style="max-height:100px;max-width:310px;" border="0"><br/><br/>';
                        $mensagem .= "<strong>Prezado(a) consumidor(a),</strong><br><br>";
                        $mensagem .= "Foi registrada a ordem de serviço nº ".$osBlack." para a fábrica, referente ao atendimento de seu produto. <br/><br/>";

                        $host = $_SERVER['HTTP_HOST'];
                        if(strstr($host, "devel.telecontrol") OR strstr($host, "homologacao.telecontrol")){
                            $mensagem .= "Para acompanhar o status <a href='http://devel.telecontrol.com.br/~monteiro/telecontrol_teste/HD-3191657ATUALIZADO/externos/institucional/blackos.html'>CLIQUE AQUI</a> ou acesse nosso site comercial na aba serviços / assistência técnica. <br/><br/>";
                        }else{
                            $mensagem .= "Para acompanhar o status <a href='https://posvenda.telecontrol.com.br/assist/externos/institucional/black_os.html'>CLIQUE AQUI</a> ou acesse nosso site comercial na aba serviços / assistência técnica. <br/><br/>";
                        }

                        $mensagem .= "***Não responder este e-mail, pois ele é gerado automaticamente pelo sistema.<br/><br/>";
                        $mensagem .= "Atenciosamente,<br/> Stanley BLACK&DECKER <br/><br/><br/>";
                        $mensagem .= '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_surv_email_2017.png" alt="http://www.blackedecker.com.br" style="float:left;max-height:100px;max-width:310px;" border="0"><br/><br/><br/>';

                        $headers  = "MIME-Version: 1.0 \r\n";
                        $headers .= "Content-type: text/html \r\n";
                        $headers .= "From: $from_fabrica_descricao <$email_admin> \r\n";

                        $mailTc = new TcComm("smtp@posvenda");
                        $res = $mailTc->sendMail(
                            $from_fabrica,
                            $assunto,
                            $mensagem,
                            $email_admin
                        );
                    }
                }
            }
            if ($login_fabrica == 117) {
                $novo_status_os = 'ABERTA';
                include('../os_email_consumidor.php');
            }

                if ($imprimir_os == "imprimir") {
                    /*
                    se alterar esta validação alterar na parte que não imprime a os
                    também alterar no posto
                    */
                    if (in_array($login_fabrica, array(141,144)) && in_array($tipo_atendimento, array(177,179,182,184))) {
                        pg_query($con, "BEGIN");

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

                        if (strlen($msg_erro) > 0) {
                            pg_query($con, "ROLLBACK");
                        } else {
                            pg_query($con, "COMMIT");
                            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);
                            header("Location: os_press.php?os=$os");
                            exit;
                        }
                    }

                    header ("Location: os_item.php?os=$os&imprimir=1");
                    exit;
                }

                if ($login_fabrica == 7){
                    #HD 25608
                    $sql = "SELECT os
                    FROM tbl_os
                    WHERE fabrica = $login_fabrica
                    AND   os       = $os
                    AND   produto IS NULL";
                    $res = pg_query ($con,$sql);
                    if (pg_num_rows($res) > 0) {
                        header ("Location: os_press.php?os=$os");
                        exit;
                    }
                }

                if ($login_fabrica == 42 and ($tipo_atendimento_et == "t")) {
                    header("Location: os_press.php?os=$os");
                } else {
                    /*
                    se alterar esta validação alterar na parte que imprime a os
                    também alterar no posto
                    */
                    if (in_array($login_fabrica, array(141,144)) && in_array($tipo_atendimento, array(177,179,182,184))) {
                        pg_query($con, "BEGIN");
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

                        if (strlen($msg_erro) > 0) {
                            pg_query($con, "ROLLBACK");
                        } else {
                            pg_query($con, "COMMIT");
                            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);
                            header("Location: os_press.php?os=$os");
                            exit;
                        }
                    }

                    if ($login_fabrica == 1 && $_REQUEST["shadowbox"] == 't') {
                        header ("Location: os_item.php?os=$os&shadowbox=t");
                    } else {

                        if (in_array($login_fabrica, [19]) && !empty($_POST['garantia_lorenzetti'])) {

                            $sqlFinaliza = "UPDATE tbl_os 
                                            SET finalizada        = current_timestamp,
                                                status_checkpoint = 9,
                                                data_fechamento   = current_date
                                            WHERE os = {$os}";
                            pg_query($con, $sqlFinaliza);

                            header("Location: os_press.php?os=$os");

                        } else {

                            header("Location: os_item.php?os=$os");

                        }

                    }
                }
                exit;
        }
    }
    }

}
  if (strlen ($msg_erro) > 0) {

    if(strpos ($msg_erro,'new row for relation "tbl_os" violates check constraint "data_nf"') > 0)
        $msg_erro= 'Data da compra maior que a data da abertura da Ordem de Serviço.';

    if(strpos ($msg_erro,'new row for relation "tbl_os" violates check constraint "data_abertura"') > 0)
        $msg_erro= ' Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).';

    if(strpos ($msg_erro,'new row for relation "tbl_os" violates check constraint "data_abertura_futura"') > 0)
        $msg_erro= ' Data da abertura deve ser inferior ou igual a data de hoje.';

        if(strpos($msg_erro,'new row for relation "tbl_os" violates check constraint "data_nf_superior_data_abertura"') > 0)//HD 235182
        $msg_erro= ' Data da Nota Fiscal deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).';

        if($login_fabrica == 11 or $login_fabrica == 172 OR $login_fabrica == 126 OR $login_fabrica == 137 OR $login_fabrica == 3){
            if(strlen(trim($_FILES["img_os_1"]["name"])) > 0  ||  strlen(trim($_FILES["img_os_2"]["name"])) > 0){
              $msg_erro .= "<br/>Selecione novamente os Anexos.";
          }
      }

      $res = pg_query ($con,"ROLLBACK TRANSACTION");
  }
}

/* ====================  APAGAR  =================== */
$ajax = $_POST['ajax'];
$obs_exclusao = !empty($ajax) ? utf8_decode($_POST['obs_exclusao']) : $_POST['obs_exclusao'];

if ($btn_acao == "apagar") {

    if (tem_pedido_os($os)) {

        if(strlen($os) > 0){
            try {
                if($login_fabrica == 1){
                    $sql = "SELECT posto FROM tbl_os WHERE os = $os";
                    $res = pg_query ($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $posto = pg_fetch_result($res, 0, 'posto');
                    }

                    $sql = "SELECT tbl_os.os
                    FROM tbl_os
                    WHERE tbl_os.fabrica = $login_fabrica
                    AND   tbl_os.posto   = $posto
                    AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
                    AND   tbl_os.data_fechamento IS NULL
                    AND  tbl_os.excluida is FALSE LIMIT 1";

                    $res = pg_query ($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $tem_os_aberta = pg_fetch_result($res, 0, 'os');
                    }
                }

                @pg_query($con, "BEGIN");
                if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

                if ($login_fabrica == 1) {
                    $sql =  "SELECT sua_os
                    FROM tbl_os
                    WHERE os = $os;";
                    $res = @pg_query ($con,$sql);

                    if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

                    if (@pg_num_rows($res) == 1) {
                        $sua_os = @pg_fetch_result($res,0,0);
                        $sua_os_explode = explode("-", $sua_os);
                        $xsua_os = $sua_os_explode[0];
                    }
                }

                if ($login_fabrica == 3){
                    if(empty($obs_exclusao)){
                        $msg_erro .= "Informe o motivo da exclusão da OS. <br>";
                    }

                    $observacoes_exclusao = array(
                        'Anistia do pedido de peças',
                        'Débito de peças. OS com pedido e mais de 150 dias',
                        'Anistia do pedido de peças. OS com pedido e mais de 150 dias'
                        );

                    if (in_array($obs_exclusao, $observacoes_exclusao) ) {
                        $sql = "
                        SELECT
                        *

                        FROM
                        tbl_os_status

                        WHERE
                        os={$os}
                        AND status_os=15
                        ";
                        $res = @pg_query($con, $sql);
                        if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

                        if (pg_num_rows($res) > 0) {
                            throw new Exception("OS já excluída");
                        }

                        $sql = "
                        INSERT INTO tbl_os_status (
                            os,
                            status_os,
                            observacao,
                            admin,
                            fabrica_status
                            ) VALUES (
    						{$os},
    						15,
    						'{$obs_exclusao}',
    						{$login_admin},
    						{$login_fabrica}
    						)
    					 ";
    					 $res = @pg_query($con, $sql);

    					 $sql = "SELECT fn_auditoria_previa_admin($os,784,'t',mao_de_obra)
    					 FROM tbl_produto
    					 WHERE produto in (
    						SELECT produto
    						FROM tbl_os
    						WHERE os= $os
    						AND   fabrica = $login_fabrica
    						)";
    					 $res = pg_query($con, $sql);

    					 if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));
    				}

    					if(strlen($msg_erro)==0){
    						$sqlO = "SELECT obs FROM tbl_os where os = $os";
    						$resO = pg_query($con, $sqlO);

    						if(pg_numrows($resO)>0){
    							$obs = pg_result($resO,0,obs);

    							$obs_exclusao = $obs . " " . $obs_exclusao;
    						}

    						$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin, obs = '$obs_exclusao' WHERE os = $os AND fabrica = $login_fabrica";
    						$res = @pg_query ($con,$sql);

    						if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

    						$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin)";
    						$res = pg_query($con, $sql);

    						if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));
    										/**
    										 * Exclui os arquivos em anexo, se tiver
    										 **/
    					if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
    						foreach ($anexos as $arquivoAnexo) {
    								excluirNF($arquivoAnexo);
    							}
    						}

    					}
    			}else{

                    /**
                     * Exclui os arquivos em anexo, se tiver
                     **/
                    if (count($anexos = temNF($os, 'path'))) { //'path' devolve um array com todos os anexos
    					foreach ($anexos as $arquivoAnexo) {
    						excluirNF($arquivoAnexo);
    					}
    				}

    				if(in_array($login_fabrica,array(52,74,131))){
    					if(empty($obs_exclusao)){
    						$msg_erro .= "Informe o motivo da exclusão da OS. <br>";
    					}
    					if(empty($msg_erro)){
        
                            if ($login_fabrica == 131) {
                                $obs_exclusao = " OS Excluída pelo Admin, motivo: $obs_exclusao ";
                            }


    						$sql = "
    						INSERT INTO tbl_os_status (
    							os,
    							status_os,
    							observacao,
    							admin,
    							fabrica_status
    							)

    							 VALUES (
    								{$os},
    								15,
    								'{$obs_exclusao}',
    								{$login_admin},
    								{$login_fabrica}
    								)
    							 ";
    							 $res = @pg_query($con, $sql);

    							 $sql1 = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin, obs = '$obs_exclusao' WHERE os = $os AND fabrica = $login_fabrica;";

    							 $res1 = @pg_query ($con,$sql1);

    					}

    				}

                    $sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
                    if(empty($msg_erro)){
                        $res = @pg_query ($con,$sql);
                    }

                    if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

                    if($login_fabrica == 52 && empty($msg_erro)){
                        $sql = "UPDATE tbl_os_excluida SET motivo_exclusao = '".substr($obs_exclusao,0,50)."' WHERE os = ".$os.";";

                        $res = @pg_query ($con,$sql);

                    }


                    if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));
                }


                    if ($login_fabrica == 1) {
                        $sqlPosto = "SELECT tbl_posto.posto
                        FROM tbl_posto
                        JOIN tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
                        WHERE tbl_posto_fabrica.codigo_posto = '".trim($_POST['posto_codigo'])."'
                        AND   tbl_posto_fabrica.fabrica      = $login_fabrica;";
                        $resPosto = @pg_query($con,$sqlPosto);
                        if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));
                        if (@pg_num_rows($res) == 1) {
                            $xposto = pg_fetch_result($resPosto,0,0);
                        }

                        $sql = "SELECT tbl_os.sua_os
                        FROM tbl_os
                        WHERE sua_os ILIKE '$xsua_os-%'
                        AND   posto   = $xposto
                        AND   fabrica = $login_fabrica;";
                        $res = @pg_query($con,$sql);
                        if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

                        if (@pg_num_rows($res) == 0) {
                            $sql = "DELETE FROM tbl_os_revenda
                            WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
                            AND    tbl_os_revenda.fabrica = $login_fabrica
                            AND    tbl_os_revenda.posto   = $xposto";
                            $res = @pg_query($con,$sql);
                            if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));
                        }
                    }

                    @pg_query($con,"COMMIT");
                    if (strlen(pg_last_error($con)) > 0 ) throw new Exception("Falha na operação: " . pg_last_error($con));

                    if(!empty($tem_os_aberta)){
                        $dir = __DIR__."/../rotinas/blackedecker/bloqueia-posto.php";
                        echo `/usr/bin/php $dir $posto`;
                    }

                            if (!empty($ajax)) {
                                echo "ok";
                                die;
                            }
                            else {
                                if(empty($msg_erro)){
                                    header("Location: os_parametros.php");
                                    exit;
                                }
                            }

                }catch (Exception $e) {
                    @pg_query("ROLLBACK");
                    $msg_erro = $e->getMessage();
                    if (!empty($ajax)) {
                        echo "falha|" . $e->getMessage();
                        die;
                }
            }
        }
    } else {
        $msg_erro = "OS possui pedido e não pode ser excluida";
        if (!empty($ajax)) {
            echo "falha|" . $e->getMessage();
            die;
        }
    }
}

/*================ LE OS DA BASE DE DADOS =========================*/
//echo $os."parei"; exit;
if (strlen ($os) > 0) {

   $sql = "SELECT  tbl_os.os                                           ,
   tbl_os.tipo_atendimento                                     ,
   tbl_os.segmento_atuacao                                     ,
   tbl_os.posto                                                ,
   tbl_posto.nome                             AS posto_nome    ,
   tbl_os.sua_os                                               ,
   to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
   tbl_os.data_fechamento                                      ,
   tbl_os.hora_abertura                                        ,
   tbl_os.produto                                              ,
   tbl_produto.referencia                                      ,
   tbl_produto.descricao                                       ,
   tbl_os.serie                                                ,
   tbl_os.qtde_produtos                                        ,
   tbl_os.cliente                                              ,
   tbl_os.consumidor_nome                                      ,
   tbl_os.consumidor_cpf                                       ,
   tbl_os.consumidor_fone                                      ,
   tbl_os.consumidor_celular                                   ,
   tbl_os.consumidor_fone_comercial                            ,
   tbl_os.consumidor_cidade                                    ,
   tbl_os.consumidor_estado                                    ,
   tbl_os.consumidor_cep                                       ,
   tbl_os.consumidor_endereco                                  ,
   tbl_os.consumidor_numero                                    ,
   tbl_os.consumidor_complemento                               ,
   tbl_os.consumidor_bairro                                    ,
   tbl_os.consumidor_email                                     ,
   tbl_os.revenda                                              ,
   tbl_os.revenda_cnpj                                         ,
   tbl_os.revenda_nome                                         ,
   tbl_os.nota_fiscal                                          ,
   to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
   tbl_os.aparencia_produto                                    ,
   tbl_os_extra.orientacao_sac                                 ,
   tbl_os_extra.admin_paga_mao_de_obra                         ,
   tbl_os_extra.obs_nf                     AS observacao_pedido,
   tbl_os.acessorios                                           ,
   tbl_os.fabrica                                              ,
   tbl_os.quem_abriu_chamado                                   ,
   tbl_os.certificado_garantia                                 ,
   tbl_os.obs                                                  ,
   tbl_os.consumidor_revenda                                   ,
   tbl_os.condicao                                             ,
   tbl_os.valores_adicionais                                   ,
   tbl_os.justificativa_adicionais                              ,
   tbl_os_extra.extrato                                        ,
   tbl_posto.pais as pais_posto                                ,
   tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
   tbl_posto_fabrica.contato_endereco       AS contato_endereco,
   tbl_posto_fabrica.contato_numero           AS contato_numero,
   tbl_posto_fabrica.contato_bairro           AS contato_bairro,
   tbl_posto_fabrica.contato_cidade           AS contato_cidade,
   tbl_posto_fabrica.contato_estado           AS contato_estado,
   tbl_posto_fabrica.contato_cep                               ,
   tbl_posto_fabrica.latitude||','||tbl_posto_fabrica.longitude AS LatLng,
   tbl_os.codigo_fabricacao                                    ,
   tbl_os.satisfacao                                           ,
   tbl_os.laudo_tecnico                                        ,
   tbl_os.troca_faturada                                       ,
   tbl_os.admin                                                ,
   tbl_os.troca_garantia                                       ,
   tbl_os.autorizacao_cortesia                                 ,
   tbl_os.defeito_reclamado                                    ,
   tbl_os.defeito_reclamado_descricao                          ,
   tbl_os.marca                                                ,
   tbl_os.fisica_juridica                                      ,
   tbl_os.quem_abriu_chamado                                   ,
   tbl_os.capacidade                 AS produto_capacidade     ,
   tbl_os.versao                     AS versao                 ,
   tbl_os.divisao                    AS divisao                ,
   tbl_os.qtde_km                                              ,
   tbl_os.tipo_os                                              ,
   tbl_os_extra.taxa_visita                                    ,
   tbl_os_extra.visita_por_km                                  ,
   tbl_os_extra.valor_por_km                                   ,
   tbl_os_extra.deslocamento_km                                ,
   tbl_os_extra.hora_tecnica                                   ,
   tbl_os_extra.regulagem_peso_padrao                          ,
   tbl_os_extra.certificado_conformidade                       ,
   tbl_os_extra.valor_diaria                                   ,
   tbl_os_extra.veiculo                                        ,
   tbl_os_extra.desconto_deslocamento                          ,
   tbl_os_extra.desconto_hora_tecnica                          ,
   tbl_os_extra.desconto_diaria                                ,
   tbl_os_extra.desconto_regulagem                             ,
   tbl_os_extra.desconto_certificado                           ,
   tbl_os_extra.desconto_peca                                  ,
   tbl_os_extra.classificacao_os                               ,
   tbl_os_extra.obs_adicionais                                 ,
   TO_CHAR(tbl_os_extra.data_fabricacao,'DD/MM/YYYY') as data_fabricacao ,
   tbl_os.os_posto                                             ,
   tbl_os.nota_fiscal_saida                                    ,
   to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') as data_nf_saida ,
   tbl_os.cortesia                                             ,
   tbl_os.marca,
   tbl_tipo_posto.descricao AS tipo_posto
   FROM    tbl_os
   LEFT JOIN   tbl_produto          ON tbl_produto.produto       = tbl_os.produto
   JOIN    tbl_posto            ON tbl_posto.posto           = tbl_os.posto
   JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
   JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
   AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
   AND tbl_fabrica.fabrica       = $login_fabrica
   LEFT JOIN tbl_tipo_posto ON(tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto)
   LEFT JOIN   tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
   WHERE   tbl_os.os      = $os
   AND     tbl_os.fabrica = $login_fabrica";
   $res = pg_query ($con,$sql);

   if (pg_num_rows ($res) == 1) {
        $os                        = pg_fetch_result ($res,0,os);
        $tipo_atendimento          = pg_fetch_result ($res,0,tipo_atendimento);
        $segmento_atuacao          = pg_fetch_result ($res,0,segmento_atuacao);
        $posto                     = pg_fetch_result ($res,0,posto);
        $posto_nome                = pg_fetch_result ($res,0,posto_nome);
        $sua_os                    = pg_fetch_result ($res,0,sua_os);
        $data_abertura             = pg_fetch_result ($res,0,data_abertura);
        $data_fechamento           = pg_fetch_result ($res,0,data_fechamento);
        $hora_abertura             = pg_fetch_result ($res,0,hora_abertura);
        $produto_referencia        = pg_fetch_result ($res,0,referencia);
        $produto_descricao         = pg_fetch_result ($res,0,descricao);
        $produto_serie             = pg_fetch_result ($res,0,serie);
        $qtde_produtos             = pg_fetch_result ($res,0,qtde_produtos);
        $cliente                   = pg_fetch_result ($res,0,cliente);
        $consumidor_nome           = pg_fetch_result ($res,0,consumidor_nome);
        $consumidor_cpf            = pg_fetch_result ($res,0,consumidor_cpf);
        $consumidor_fone           = pg_fetch_result ($res,0,consumidor_fone);
        $consumidor_celular        = pg_fetch_result ($res,0,consumidor_celular);//15091
        $consumidor_fone_comercial = pg_fetch_result ($res,0,consumidor_fone_comercial);
        $consumidor_cep            = trim (pg_fetch_result ($res,0,consumidor_cep));
        $consumidor_endereco       = trim (pg_fetch_result ($res,0,consumidor_endereco));
        $consumidor_numero         = trim (pg_fetch_result ($res,0,consumidor_numero));
        $consumidor_complemento    = trim (pg_fetch_result ($res,0,consumidor_complemento));
        $consumidor_bairro         = trim (pg_fetch_result ($res,0,consumidor_bairro));
        $consumidor_cidade         = pg_fetch_result ($res,0,consumidor_cidade);
        $consumidor_estado         = pg_fetch_result ($res,0,consumidor_estado);
        $consumidor_email          = pg_fetch_result ($res,0,consumidor_email);
        $fisica_juridica           = pg_fetch_result ($res,0,fisica_juridica);

        $revenda                   = pg_fetch_result ($res,0,revenda);
        $revenda_cnpj              = pg_fetch_result ($res,0,revenda_cnpj);
        $cnpj_raiz                 = trim(substr($revenda_cnpj,0,8));

        $revenda_nome              = pg_fetch_result ($res,0,revenda_nome);
        $nota_fiscal               = pg_fetch_result ($res,0,nota_fiscal);
        $data_nf                   = pg_fetch_result ($res,0,data_nf);
        $aparencia_produto         = pg_fetch_result ($res,0,aparencia_produto);
        $acessorios                = pg_fetch_result ($res,0,acessorios);
        $fabrica                   = pg_fetch_result ($res,0,fabrica);
        $posto_codigo              = pg_fetch_result ($res,0,posto_codigo);

        /*DADOS DO POSTO PARA CALCULO KM*/
        $contato_endereco          = $endereco_posto = trim (pg_fetch_result ($res,0,contato_endereco));
        $contato_numero            = trim (pg_fetch_result ($res,0,contato_numero));
        $contato_bairro            = trim (pg_fetch_result ($res,0,contato_bairro));
        $contato_cidade            = pg_fetch_result ($res,0,contato_cidade);
        $contato_estado            = pg_fetch_result ($res,0,contato_estado);
        $contato_cep               = pg_fetch_result ($res,0,contato_cep);
        $LatLngPosto			   = pg_fetch_result ($res, 0, 'LatLng');
        $pais_posto                = pg_fetch_result($res, 0, "pais_posto");

        $condicao                  = pg_fetch_result ($res,0,condicao);
        $extrato                   = pg_fetch_result ($res,0,extrato);
        $quem_abriu_chamado        = pg_fetch_result ($res,0,quem_abriu_chamado);
        $obs                       = pg_fetch_result ($res,0,obs);
        $observacao_pedido         = addslashes(str_replace("'", '"', pg_fetch_result ($res,0,observacao_pedido)));
        $consumidor_revenda        = pg_fetch_result ($res,0,consumidor_revenda);
        $codigo_fabricacao         = pg_fetch_result ($res,0,codigo_fabricacao);
        $satisfacao                = pg_fetch_result ($res,0,satisfacao);
        $laudo_tecnico             = pg_fetch_result ($res,0,laudo_tecnico);
        $troca_faturada            = pg_fetch_result ($res,0,troca_faturada);
        $troca_garantia            = pg_fetch_result ($res,0,troca_garantia);
        $admin_os                  = trim(pg_fetch_result ($res,0,admin));
        $autorizacao_cortesia      = pg_fetch_result ($res,0, autorizacao_cortesia);

        $qtde_km                   = pg_fetch_result ($res,0,qtde_km);//48818
        $versao                    = pg_fetch_result ($res,0,versao);
        $divisao                   = pg_fetch_result ($res,0,divisao);
        $produto_capacidade        = pg_fetch_result ($res,0,produto_capacidade);
        $taxa_visita               = pg_fetch_result ($res,0,taxa_visita);
        $visita_por_km             = pg_fetch_result ($res,0,visita_por_km);
        $valor_por_km              = pg_fetch_result ($res,0,valor_por_km);
        $deslocamento_km           = pg_fetch_result ($res,0,deslocamento_km);
        $hora_tecnica              = pg_fetch_result ($res,0,hora_tecnica);
        $horas_trabalhadas         = pg_fetch_result ($res,0,hora_tecnica);
        $regulagem_peso_padrao     = pg_fetch_result ($res,0,regulagem_peso_padrao);
        $certificado_conformidade  = pg_fetch_result ($res,0,certificado_conformidade);
        $valor_diaria              = pg_fetch_result ($res,0,valor_diaria);
        $veiculo                   = pg_fetch_result ($res,0,veiculo);
        $desconto_deslocamento     = pg_fetch_result ($res,0,desconto_deslocamento);
        $desconto_hora_tecnica     = pg_fetch_result ($res,0,desconto_hora_tecnica);
        $desconto_diaria           = pg_fetch_result ($res,0,desconto_diaria);
        $desconto_regulagem        = pg_fetch_result ($res,0,desconto_regulagem);
        $desconto_certificado      = pg_fetch_result ($res,0,desconto_certificado);
        $desconto_peca             = pg_fetch_result ($res,0,desconto_peca);
        $classificacao_os          = pg_fetch_result ($res,0,classificacao_os);
        $data_fabricacao           = pg_fetch_result ($res,0,data_fabricacao);
        $os_posto                  = pg_fetch_result ($res,0,os_posto);
        $nota_fiscal_saida         = pg_fetch_result ($res,0,nota_fiscal_saida);
        $data_nf_saida             = pg_fetch_result ($res,0,data_nf_saida);
        $preco_produto             = pg_fetch_result ($res,0,valores_adicionais);
        $os_cortesia               = pg_fetch_result ($res,0,cortesia);
        $marca_fricon              = pg_fetch_result ($res,0,marca);
        $motivo_troca              = pg_fetch_result ($res,0,justificativa_adicionais);
        $tipo_posto_descr          = pg_fetch_result ($res,0,tipo_posto);

        if($login_fabrica == 117 or $login_fabrica == 128){
            $garantia_estendida    = pg_fetch_result ($res,0,certificado_garantia);
            if(strlen($garantia_estendida) > 0 && $garantia_estendida == 12){
                $opcao_garantia_estendida = "t";
            }else if(strlen($garantia_estendida) > 0 && $garantia_estendida == 6){
                $opcao_garantia_estendida = "f";
            }else{
                $opcao_garantia_estendida = "";
            }
        }

        $orientacao_sac            = pg_fetch_result ($res,0,orientacao_sac);
        $orientacao_sac            = html_entity_decode($orientacao_sac, ENT_QUOTES);
        $orientacao_sac            = str_replace ("<br />", "", $orientacao_sac);
        $orientacao_sac            = str_replace ("|", "\n", $orientacao_sac);

        $tipo_os                   = pg_fetch_result ($res,0,tipo_os);

        $admin_paga_mao_de_obra    = pg_fetch_result ($res,0,admin_paga_mao_de_obra);

        if ($login_fabrica == 35) {
            $obs_adicionais = pg_fetch_result ($res,0,obs_adicionais);
        } else {
            $obs_adicionais = utf8_encode(pg_fetch_result ($res,0,obs_adicionais));
        }

        $obs_adicional     = json_decode($obs_adicionais,true);
        $consumidor_cpd     = $obs_adicional['consumidor_cpd'];
        $consumidor_contato = $obs_adicional['consumidor_contato'];

        if ($login_fabrica == 1) {
            $qry_campos_adicionais = pg_query(
                $con,
                "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os"
            );

            if (pg_num_rows($qry_campos_adicionais) > 0) {
                $os_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);

                if (!empty($os_campos_adicionais) and  array_key_exists("consumidor_profissao", $os_campos_adicionais)) {
                    $consumidor_profissao = utf8_decode($os_campos_adicionais["consumidor_profissao"]);
                }
            }
        }


        if ($login_fabrica == 7 AND strlen($desconto_peca)==0 AND strlen($consumidor_cpf) > 0) {
            $sql = "SELECT  tbl_posto_consumidor.contrato,
            tbl_posto_consumidor.desconto_peca
            FROM   tbl_posto_consumidor
            JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_consumidor.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
            WHERE  tbl_posto.cnpj = '$consumidor_cpf' ";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows ($res2) > 0 ) {
                $contrato      = trim(pg_fetch_result($res2,0,contrato));
                $desconto_peca = trim(pg_fetch_result($res2,0,desconto_peca));

                if ($contrato != 't'){
                    $desconto_peca = "0";
                }
            }
        }

        if ($consumidor_revenda == 'R'){
            $sql = "SELECT os_manutencao
            FROM tbl_os
            LEFT JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero
            AND tbl_os_revenda.posto = tbl_os.posto
            WHERE  tbl_os.os = $os
            AND    tbl_os.fabrica = $login_fabrica
            ";
            $resRevenda = pg_query ($con,$sql);
            if (pg_num_rows ($resRevenda) > 0 ) {
                $os_manutencao = pg_fetch_result ($resRevenda,0,os_manutencao);
            }
        }

        if ($os_manutencao == 't'){
            $sql = "SELECT  tbl_os_revenda.taxa_visita,
            tbl_os_revenda.visita_por_km,
            tbl_os_revenda.valor_por_km,
            tbl_os_revenda.deslocamento_km,
            tbl_os_revenda.veiculo,
            tbl_os_revenda.hora_tecnica,
            tbl_os_revenda.valor_diaria,
            tbl_os_revenda.qtde_horas,
            tbl_os_revenda.regulagem_peso_padrao,
            tbl_os_revenda.desconto_deslocamento,
            tbl_os_revenda.desconto_hora_tecnica,
            tbl_os_revenda.desconto_diaria
            /*tbl_os_revenda.desconto_regulagem*/

            FROM   tbl_os
            JOIN   tbl_os_revenda        ON tbl_os_revenda.os_revenda = tbl_os.os_numero AND tbl_os_revenda.posto = tbl_os.posto
            WHERE  tbl_os.os = $os
            AND    tbl_os.fabrica = $login_fabrica
            ";

            $res2 = pg_query ($con,$sql);
            if (pg_num_rows ($res2) > 0 ) {

                $valor_por_km_caminhao    = trim(pg_fetch_result($res2,0,valor_por_km));
                $valor_por_km_carro       = trim(pg_fetch_result($res2,0,valor_por_km));
                $valor_por_km             = trim(pg_fetch_result($res2,0,valor_por_km));
                $deslocamento_km          = trim(pg_fetch_result($res2,0,deslocamento_km));
                $veiculo                  = trim(pg_fetch_result($res2,0,veiculo));
                $taxa_visita              = trim(pg_fetch_result($res2,0,taxa_visita));
                $hora_tecnica             = trim(pg_fetch_result($res2,0,hora_tecnica));
                $valor_diaria             = trim(pg_fetch_result($res2,0,valor_diaria));

                $regulagem_peso_padrao    = trim(pg_fetch_result($res2,0,regulagem_peso_padrao));

                $desconto_deslocamento  = pg_fetch_result ($res2,0,desconto_deslocamento);
                $desconto_hora_tecnica  = pg_fetch_result ($res2,0,desconto_hora_tecnica);
                $desconto_diaria        = pg_fetch_result ($res2,0,desconto_diaria);
                #$desconto_regulagem    = pg_fetch_result ($res2,0,desconto_regulagem);
            }
        }

        if ($regulagem_peso_padrao > 0){
            $cobrar_regulagem = 't';
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

        //HD 12606
        $defeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
        #if($login_fabrica==11 or $login_fabrica==19 or $login_fabrica==3) HD 242946
        $defeito_reclamado = pg_fetch_result($res,0,defeito_reclamado);

        if (empty($defeito_reclamado_descricao) && !empty($defeito_reclamado)) {
            $sqlDf = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado AND fabrica = $login_fabrica";
            $resDf = pg_query($con, $sqlDf);
            if (pg_num_rows($resDf) > 0) {
                $defeito_reclamado_descricao = pg_fetch_result($resDf, 0, 'descricao');
            }
        }

        $marca_fricon = pg_fetch_result($res,0,marca);

        $sql =  "SELECT tbl_os_produto.produto ,
        tbl_os_item.pedido
        FROM    tbl_os
        JOIN    tbl_produto using (produto)
        JOIN    tbl_posto using (posto)
        JOIN    tbl_fabrica using (fabrica)
        JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
        AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
        JOIN    tbl_os_produto USING (os)
        JOIN    tbl_os_item
        ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
        WHERE   tbl_os.os = $os
        AND     tbl_os.fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);

        if(pg_num_rows($res) > 0){
            $produto = pg_fetch_result($res,0,produto);
            $pedido  = pg_fetch_result($res,0,pedido);
        }

        //SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
        if (strlen($consumidor_cidade)==0){
            if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {
                $sql = "SELECT
                tbl_cliente.cliente,
                tbl_cliente.nome,
                tbl_cliente.endereco,
                tbl_cliente.numero,
                tbl_cliente.complemento,
                tbl_cliente.bairro,
                tbl_cliente.cep,
                tbl_cliente.rg,
                tbl_cliente.fone,
                tbl_cliente.contrato,
                tbl_cidade.nome AS cidade,
                tbl_cidade.estado
                FROM tbl_cliente
                LEFT JOIN tbl_cidade USING (cidade)
                WHERE 1 = 1";
                if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
                if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

                $res = pg_query ($con,$sql);
                if (pg_num_rows ($res) == 1) {
                    $consumidor_cliente     = trim (pg_fetch_result ($res,0,cliente));
                    $consumidor_fone        = trim (pg_fetch_result ($res,0,fone));
                    $consumidor_nome        = trim (pg_fetch_result ($res,0,nome));
                    $consumidor_endereco    = trim (pg_fetch_result ($res,0,endereco));
                    $consumidor_numero      = trim (pg_fetch_result ($res,0,numero));
                    $consumidor_complemento = trim (pg_fetch_result ($res,0,complemento));
                    $consumidor_bairro      = trim (pg_fetch_result ($res,0,bairro));
                    $consumidor_cep         = trim (pg_fetch_result ($res,0,cep));
                    $consumidor_rg          = trim (pg_fetch_result ($res,0,rg));
                    $consumidor_cidade      = trim (pg_fetch_result ($res,0,cidade));
                    $consumidor_estado      = trim (pg_fetch_result ($res,0,estado));
                    $consumidor_contrato    = trim (pg_fetch_result ($res,0,contrato));
                }
            }
        }


        if ($os_manutencao != 't' or 1==1){
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
                        $valor_por_km_carro       = $valor_por_km;
                    }
                    if ($veiculo == 'caminhao'){
                        $valor_por_km_carro       = trim(pg_fetch_result($res,0,valor_por_km_carro));
                        $valor_por_km_caminhao    = $valor_por_km;
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

    if($login_fabrica == 59 or $login_fabrica == 104 or $login_fabrica == 127){
        $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os AND campos_adicionais notnull";
        $res = pg_query($con,$sql);

        if(pg_num_rows($res) > 0){
            $campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'),true);

            foreach ($campos_adicionais as $key => $value) {
                $$key = $value;
            }
        }
    }

	if($_REQUEST['osacao'] == 'trocar') {
		$sqlt = "select causa_troca, observacao, campos_adicionais::jsonb->>'hd_classificacao' as hd_classificacao from tbl_os_troca left join tbl_os_campo_extra using(os) where tbl_os_troca.os = $os";
		$rest = pg_query($con,$sqlt);
		if(pg_num_rows($rest) > 0) {
			$causa_troca = pg_fetch_result($rest, 0, 'causa_troca');
			$xhd_classificacao = pg_fetch_result($rest, 0, 'hd_classificacao');
			$obs_troca = pg_fetch_result($rest,0,'observacao');
		}
	}
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
    $os                 = $_POST['os'];
    $tipo_atendimento   = $_POST['tipo_atendimento'];
    $segmento_atuacao   = $_POST['segmento_atuacao'];
    $sua_os             = $_POST['sua_os'];
    $data_abertura      = $_POST['data_abertura'];
    $hora_abertura      = $_POST['hora_abertura'];
    $cliente            = $_POST['cliente'];
    $consumidor_nome    = $_POST['consumidor_nome'];
    $consumidor_cpf     = $_POST['consumidor_cpf'];
    $consumidor_fone    = $_POST['consumidor_fone'];
    $consumidor_celular = $_POST['consumidor_celular'];
    $consumidor_profissao = $_POST['consumidor_profissao'];
    $consumidor_fone_comercial = $_POST['consumidor_fone_comercial'];
    $consumidor_email   = $_POST['consumidor_email'];
    $fisica_juridica    = $_POST['fisica_juridica'];

    $revenda            = $_POST['revenda'];
    $revenda_cnpj       = $_POST['revenda_cnpj'];
    $cnpj_raiz          = trim(substr($revenda_cnpj,0,8));
    $revenda_nome       = $_POST['revenda_nome'];
    $nota_fiscal        = $_POST['nota_fiscal'];
    $data_nf            = $_POST['data_nf'];
    $produto_referencia = $_POST['produto_referencia'];
    $cor                = $_POST['cor'];
    $campo_acessorios   = $_POST['acessorios'];
    $aparencia_produto  = $_POST['aparencia_produto'];
    $obs                = $_POST['obs'];
    $observacao_pedido  = addslashes(str_replace("'", '"', $_POST['observacao_pedido']));
    $orientacao_sac     = $_POST['orientacao_sac'];
    $consumidor_revenda = $_POST['consumidor_revenda'];
    $qtde_produtos      = $_POST['qtde_produtos'];
    $produto_serie      = $_POST['produto_serie'];
    $autorizacao_cortesia = $_POST['autorizacao_cortesia'];

    $codigo_fabricacao  = $_POST['codigo_fabricacao'];
    $satisfacao         = $_POST['satisfacao'];
    $laudo_tecnico      = $_POST['laudo_tecnico'];
    $troca_faturada     = $_POST['troca_faturada'];

    $quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
    $taxa_visita              = $_POST['taxa_visita'];
    $visita_por_km            = $_POST['visita_por_km'];
    $deslocamento_km          = $_POST['deslocamento_km'];
    $hora_tecnica             = $_POST['hora_tecnica'];
    $regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
    $certificado_conformidade = $_POST['certificado_conformidade'];
    $valor_diaria             = $_POST['valor_diaria'];
    $codigo_rastreio          = $_POST['codigo_rastreio'];

    $cancela_mao_obra           = $_POST['cancela_mao_obra'];
    $motivo_codigo_rastreio     = $_POST['motivo_cancela_mao_obra'];
    $protocolo_cancela_mao_obra = $_POST['protocolo_cancela_mao_obra'];
    $marca_fricon               = $_POST['marca_fricon'];
    $origem                     = $_POST['origem'];

    $cond_ativacao = "AND (tbl_produto.ativo IS TRUE or uso_interno_ativo)";
    if ($login_fabrica == 3) {
        $cond_ativacao = "AND ( (tbl_produto.ativo IS TRUE or uso_interno_ativo) OR (tbl_produto.ativo IS NOT TRUE AND tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't') ) ";
    }

    $sql = "SELECT descricao
    FROM    tbl_produto
    JOIN    tbl_linha USING (linha)
    WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
    AND     tbl_linha.fabrica      = $login_fabrica
    $cond_ativacao ";
    $res = pg_query ($con,$sql);
    
    $produto_descricao = @pg_fetch_result ($res,0,0);
}

if ($login_fabrica == 101) {
    $aux_os  = $_GET["os"];
    $aux_sql = "SELECT os_troca FROM tbl_os_troca where os = $aux_os LIMIT 1";
    $aux_res = pg_query($con, $aux_sql);
    $aux_osT = pg_fetch_result($aux_res, 0, 0);

    if (!empty($aux_osT)) {
        $aux_sql = "UPDATE tbl_os SET data_fechamento = current_timestamp, finalizada = current_timestamp WHERE os = $aux_os AND fabrica = $login_fabrica";
        $aux_res = pg_query($con,$aux_sql);
    }
}

if ($orientacao_sac == "null") $orientacao_sac = "";
$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

    /* $title = Aparece no sub-menu e no título do Browser ===== */
    $title = "CADASTRO DE ORDEM DE SERVIÇO - ADMIN";

    /* $layout_menu = Determina a aba em destaque do MENU ===== */
    if($login_fabrica <> 108 and $login_fabrica <> 111){
        $layout_menu = 'callcenter';
    } else {
        $layout_menu = 'gerencia';
    }

    /* Verifica se a fabrica utiliza Calculo de KM */
    $sql = "SELECT JSON_FIELD('usaCalculoKM',parametros_adicionais) AS calculoKM FROM tbl_fabrica WHERE fabrica = $login_fabrica";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res)){
        $calculoKM = (bool)pg_fetch_result($res,0,calculoKM);
    }else{
        $calculoKM = "f";
    }

    include "cabecalho.php";
    include "javascript_calendario_new.php";
    include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist */

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

    if ((in_array($login_fabrica,array(15,74,91,115,116,117,120))) && ($os != "")) {

        $sql = "SELECT qtde_km FROM tbl_os WHERE os = $os";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $distancia_km = $distancia_km = pg_result($res,0,0);

            if(strlen($distancia_km) == 0){
                $distancia_km = 0;
                $distancia_km_conferencia = 0;
            }else{
                $distancia_km_conferencia = $distancia_km;
            }

        }

    }

    ?>

    <script language="javascript" type="text/javascript" src="js/phoneparser.js"></script>

<script type = "text/javascript">
      <?php if (in_array($login_fabrica, array(19,72))) { ?>
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
    <? if (in_array($login_fabrica, array(101,141,144)) && $_GET["ok"] == "s" && $_GET["osacao"] == "trocar" && $_GET["s"] == "s") { ?>
            <? if (isset($_GET["ressarcimento"])) { ?>
                $(window.opener.document).find('tr[os=<?=$os?>]').find('button[name=trocar]').parents('td').html("<div class='alert alert-success tac' style='margin-bottom: 0px;' >Ressarcimento efetuado</div>");
            <? } else { ?>
                $(window.opener.document).find('tr[os=<?=$os?>]').find('button[name=trocar]').parents('td').html("<div class='alert alert-success tac' style='margin-bottom: 0px;' >Troca efetuada</div>");
                <? if ($login_fabrica == 141) { ?>
                    window.open("aviso_troca.php?os="+<?=$os?>);
                <? }
            } ?>

    <? } ?>

$(function(){

    <?php
    if (!empty($pais_posto && $pais_posto != "BR")) { ?>

        $("#consumidor_cep").removeClass("addressZip");
        $("#consumidor_estado").removeClass("addressState");

    <?php
    }
    ?>

    $("#consumidor_estado").change(function(){

        let pais   = $("#pais_posto").val();
        let estado = $(this).val();

        if (pais == "" || pais == "BR") {
            return;
        }

        $("#consumidor_cidade").find("option").first().nextAll().remove();

        if (estado.length > 0) {
            $.ajax({
                async: false,
                url: window.location,
                type: "POST",
                data: { ajax_busca_cidade: true, estado: estado , pais: pais},
                beforeSend: function() {
                    if ($("#consumidor_cidade").next("img").length == 0) {
                        $("#consumidor_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                    }
                },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);

                    if (data.error) {
                        alert(data.error);
                    } else {
                        $.each(data.cidades, function(key, value) {
                            var option = $("<option></option>", { value: value, text: value});

                            $("#consumidor_cidade").append(option);
                        });
                    }


                    $("#consumidor_cidade").show().next().remove();
                }
            });
        }

        if(typeof cidade != "undefined" && cidade.length > 0){

            $("#consumidor_cidade option[value='"+cidade+"']").attr('selected','selected');

        }

    });

    <?php if ($telecontrol_distrib && !in_array($login_fabrica, [11,172])) { ?>
            if ($('#fabrica_fabrica').prop("checked")) {
                $("#gerar_pedido").prop("checked", false);;
                $("#gerar_pedido").attr("disabled", true); 
            }

            $("#fabrica_fabrica").on('click', function() { 
                $("#gerar_pedido").prop("checked", false); 
                $("#gerar_pedido").attr("disabled", true); 

            });

            $("#fabrica_distrib").on('click', function() {
                $("#gerar_pedido").removeAttr("disabled");
            });
    <?php } ?>

    <?php if($login_fabrica == 24){ ?>
        $("#produto_serie").blur(function(){
            var serie       = $("#produto_serie").val();
            var referencia  = $("#referencia_produto").val();
            $.ajax({
                type: "POST",
                datatype: 'json',
                url: "os_cadastro.php",
                data: {verifica_produto_serie:true, referencia: referencia, serie:serie},
                success: function(retorno){
                    var dados = $.parseJSON(retorno);
                    if(dados.retorno == 'erro'){
                      alert("Número de Série Bloqueado: "+dados.observacao);
                    }
                }
            });
        });
    <?php } ?>

    $("#consumidor_email").keydown(function(){
        $(".informaemail:input:checked").removeProp("checked");
    });

    $(".informaemail").click(function(){
        $("#consumidor_email").val("");
    });

    var cel = "";
    var data = "";
    var prefixo = "";
    var pais = "";

    $("#consumidor_celular").blur(function() {

        var posto = $('input[name=posto_codigo]').val();

        if(posto.length == 0){
            return;
        }

        if($(this).val() == "" || $(this).val() == cel){
            return;
        }

        $.ajax({
            url: "<?php $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
                posto : posto,
                verifica_prefixo : "ok"
            },
            complete: function(data){
                data = $.parseJSON(data.responseText);

                prefixo = data.cod;
                pais    = data.pais;

                cel = prefixo + $("#consumidor_celular").val();

                var res = parsePhone(cel);
                if(JSON.stringify(res) == "null"){


                    alert("Número de Celular Inválido. Por favor verifique!");
                    $("#consumidor_celular").focus();
                    return;

                }else if(res.countryCode != prefixo || res.countryISOCode != pais){


                    alert("Número de Celular Inválido. Por favor verifique!");
                    $("#consumidor_celular").focus();
                    return;

                }

            }
        });

    });

    $("input[type=file]").change(function(){
        var tamanho = $(this).prop('files')[0]['size'];
        /*HD-3980490 Retirado o limite do tamanho do arquivo*/
    });

<?php
if ($login_fabrica == 1) {
    if ($consumidor_revenda != 'C' and !empty($os)) {
?>
        $( ".dados_consumidor" ).prop("readonly", true);
        $( ".img_consumidor" ).hide();
        $('#id_tp_consumidor option:not(:selected)').prop('disabled', true);
        $('#consumidor_estado option:not(:selected)').prop('disabled', true);
        $('#consumidor_cidade option:not(:selected)').prop('disabled', true);
        $("input[id=consumidor_possui_email]:not(:checked)").prop('disabled', true);
<?php
    }
?>
    $("#consumidor_email").css("display","none");
    $("span[rel=consumidor_email]").css("display","none");

    $("input[name=consumidor_possui_email]").click(function(){
        var valor = $("input[name=consumidor_possui_email]:checked").val();

        if (valor == "sim") {
            $("#consumidor_email").css("display","block");
            $("#consumidor_email").attr("readOnly",false);
            $("span[rel=consumidor_email]").css("display","block");
            $("#consumidor_email").val("");
        } else if (valor == "nao") {
            $("#consumidor_email").css("display","none");
            $("span[rel=consumidor_email]").css("display","none");
            $("#consumidor_email").val("nt@nt.com.br");
        }
    });

    $("#garantia_pecas").click(function(){
        if ($("#garantia_pecas:checked").val()) {

            $.ajax({
                url:"os_cadastro.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    tipo:"produto_garantia_peca",
                    desmarcar:false
                }
            })
            .done(function(data){
                console.log(data.ok);
            });
        } else {
            $.ajax({
                url:"os_cadastro.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    tipo:"produto_garantia_peca",
                    desmarcar:true
                }
            })
            .done(function(data){
                console.log(data.ok);
            });
        }
    });
<?php
}
?>

    <?php
    if (in_array($login_fabrica, [19]) and empty($os)) { ?>

     $("#tipo_atendimento").change(function(){

        if ($(this).val() == "339") {
          $("#anexo_certificado").show();
        } else {
          $("#anexo_certificado").hide();
          $("#garantia_lorenzetti").val("");
        }

      });

      $("#tipo_atendimento").change();  

      libera_tipo_atendimento_garantia($("#produto_referencia").val());
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
        console.log(referencia_prod);
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

            if (data.garantia2 != "" && data.garantia2 != undefined) {

              $("#tipo_atendimento > option[value=339]").show();

            } else {

              $("#tipo_atendimento > option").prop("selected", false);
              $("#tipo_atendimento > option[value=339]").hide();
              
            }

          } else {

             //$("#tipo_atendimento > option").prop("selected", false);
             $("#tipo_atendimento > option[value=339]").hide();

          }

        });
      }

    }

    function page_loading() {

        $.blockUI();

    }

    function verificaDados(dados){
        var data_nf         = $("#data_nf").val();
        var nota_fiscal     = $("#nota_fiscal").val();
        var nome_revenda    = $("#nome_revenda").val();

        if((dados.data_nf != data_nf) || (dados.nota_fiscal != nota_fiscal) || (dados.nome_revenda != nome_revenda) ){

            if (confirm("Os dados de Nota Fiscal, Data da Compra ou Nome Revenda estam diferentes da O.S anterior. \n\n Deseja prosseguir com a abertura da O.S?") == true) {
                
                alert("O anexo da Nota Fiscal é obrigatório.");

                $("#dadosDiferenteEsmaltec").val("true");
                return true;
            } else {
                $("#dadosDiferenteEsmaltec").val("");
                return false;
            }
        }
    }

    function gravar_esmaltec(){

        var produto_serie = $("#produto_serie").val();
        var produto_referencia = $("#produto_referencia").val();

        if(produto_referencia.length == 0){
            alert("Informe o Produto");
            return false;
        }

        if(produto_serie.length == 0){
            alert("Informe o número de Série do Produto");
            return false;
        }
        $.ajax({
            url : "valida_ns_esmaltec.php",
            type: "POST",
            data: {
                numeroSerie: produto_serie,
                produto_referencia :produto_referencia,
                ajax_ns : true
            },
            success: function(data){
                var retorno = JSON.parse(data);

                if(retorno.retorno == "ok"){
                    var result = verificaDados(retorno);
                    if(result == false){
                        return false;
                    }
                }else{
                    $("#dadosDiferenteEsmaltec").val("");
                }

                if (document.frm_os.btn_acao.value == '') { 
                    document.frm_os.btn_acao.value='continuar'; 
                    document.frm_os.submit() 
                } else { 
                    alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.')
                } 
                return false;
            }
        });
    }

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

    function formatItem(row) {
        return row[1];
    }

    function changeInput() {
        $("input.hidden_consumidor_nome").change();
    }

    $(document).ready(function() {

        $('.addressZip').blur(function(event) {
            $("input[name='consumidor_numero']").focus();
        });

        <?php if($calculoKM == "t"){ ?>
            var numeroConsumidor = "";

            if($('#consumidor_numero').val() != "") {
                numeroConsumidor = $('#consumidor_numero').val();
            }
            $('#consumidor_numero').blur(function(){
                var numeroConsumidor2 = "";
                numeroConsumidor2 = $('#consumidor_numero').val();
            });


	<?php
		if(strlen($_GET['os']) > 0 && $_GET['osacao'] != 'trocar'){
	?>
			//calcRoute();
	<?php
		}
	} ?>

 $('input.hidden_consumidor_nome').change(function(){
    $('#distancia_km').val('');
    $('#div_end_posto').html('');
    $('#div_mapa_msg').html('');
    /*
        Comentado no hd_chamado=2798091
        Waldir pediu para retirar para todas as fabricas.
        setTimeout(function(){
            calcRoute();
        }, 1000);
    */

});

 Shadowbox.init();

 verifica_atendimento();

<?php if($login_fabrica == 91){ ?>
    $("input[name=garantia_diferenciada]").click(function() {
        $("#garantia_diferenciada_mes").numeric();
        var numero_serie = $("#produto_serie").val();
        var referencia_produto = $("#produto_referencia").val();
         $.ajax({
            url : "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
              garantia_diferenciada : "ok",
              numero_serie : numero_serie,
              referencia_produto : referencia_produto,
              ajax : true
            },
            complete: function(data){
              data = data.responseText.split('|');
              if(data[0] == "ok"){
                alert(data[1]);
                $("#garantia_diferenciada").prop('checked', false);
              }
            }
        });
    });
<?php } ?>
     $("input[name=revenda_cnpj_raiz]").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=cnpj'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

     $("input[name=revenda_nome]").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

     $("input[name=revenda_cnpj_raiz], input[name=revenda_nome]").result(function(event, data, formatted) {
        $("input[name=revenda_cnpj]").val(data[0]) ;
        $("input[name=revenda_nome]").val(data[1]);
        $("input[name=revenda_cnpj_raiz]").val(data[2]);
    });

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

    $("#garantia_estendida").click(function(){
        if( $("#garantia_estendida").is(":checked") ){
            $("#op_garantia_estendida").show();
            $("input[name=os_cortesia]").attr("disabled", true).removeAttr("checked").next("font").after("<b style='color: #F00; font-size: 10px;'>( * uma os de garantia estendida não pode ser uma os cortesia )</b>");
        }else{
            $("#op_garantia_estendida").hide();
            $("#nf_garantia_estendida").hide();
            $("input[name=opcao_garantia_estendida]").each(function () {
                $(this).removeAttr("checked");
            });
            $("input[name=os_cortesia]").removeAttr("disabled").next("font").next("b").remove();
        }
    });

    $("input[name=opcao_garantia_estendida]:radio").click(function(){
        var opcao_garantia_estendida = $(this).val();
        if( opcao_garantia_estendida == "t" ){
            $("#nf_garantia_estendida").show();
        }else{
            $("#nf_garantia_estendida").hide();
        }
    });
});

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

// valida numero de serie
function mostraEsconde(){
    $("div[@rel=div_ajuda]").toggle();
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

function pesquisaRevendaLatina(campo,tipo){
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:    "pesquisa_revenda_latina.php?descricao="+campo+"&tipo="+tipo,
            player: "iframe",
            title:      "Pesquisa Revenda",
            width:  800,
            height: 500
        });
    }else
    alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function fnc_pesquisa_produto_serie (campo,form) {
    if (campo.value != "") {
        var url = "";
        url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.focus();
    }

    else{
        alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }
}

<?php
$retorno = ($login_fabrica == 15) ?"revenda,nome,nome_fantasia,cnpj,ie,cidade,fone,fax,contato,endereco,numero,complemento,bairro,cep,estado,email,cnpj_raiz":"nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email";
?>

function VerificaBloqueioRevenda(cnpj, fabrica){
  $.ajax({
      type: "POST",
      datatype: 'json',
      url: "ajax_verifica_bloquei_revenda.php",
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

function retorna_revenda(<?=$retorno?>){
    <? if ($login_fabrica == 15){ ?>
        gravaDados("revenda_cnpj_raiz",cnpj_raiz);
        <?}?>
        gravaDados("revenda_cnpj",cnpj);
        gravaDados("revenda_nome",nome);

    <?php if($login_fabrica == 35){ ?>
        VerificaBloqueioRevenda(cnpj, <?=$login_fabrica ?> );
    <?php } ?>
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
                //**
                alert(http4[curDateTime].responseText);
                var results = http4[curDateTime].responseText;
                campo.innerHTML   = results;
            }else {
                campo.innerHTML = "Erro";

            }
        }
    }
    http4[curDateTime].send(null);
}

function verifica_atendimento_clear() {
    $("#aparencia_produto").removeAttr("disabled").css("background-color", "#F0F0F0");
    $("#acessorios").removeAttr("disabled").css("background-color", "#F0F0F0");
    $('#produto_referencia').removeAttr('disabled');
    $('#produto_descricao').removeAttr('disabled');
    $('#produto_serie').removeAttr('disabled');
    $(".tipo_atendimento_obg").hide();
}

<?php
if(strlen($msg_erro) > 0){
    echo "var erroCadastro = 0;";
}
?>

function verifica_atendimento(tipo_atendimento) {

    var fabrica = "<?=$login_fabrica?>";

    if (fabrica == 42) {
        verifica_atendimento_clear();

        if ($("#tipo_atendimento").find("option:selected").attr("rel") == "t") {
            $("#aparencia_produto").attr("disabled", "disabled").val("").css("background-color", "#777");
            $("#acessorios").attr("disabled", "disabled").val("").css("background-color", "#777");
            $(".tipo_atendimento_obg").show();
        } else if ($('#tipo_atendimento').val() == 102) {

            // $('#produto_referencia').attr('disabled', '');
            // $('#produto_descricao').attr('disabled', '');
            // $('#produto_serie').attr('disabled', '');
            $('#produto_referencia').prop('readOnly', true);
            $('#produto_descricao').prop('readOnly', true);
            $('#produto_serie').prop('readOnly', true);

        } else if ($('#tipo_atendimento').val() == 103 || $('#tipo_atendimento').val() == 104) {

            $('#produto_referencia').attr('disabled', 'disabled');
            $('#produto_descricao').attr('disabled', 'disabled');
            $('#produto_serie').attr('disabled', 'disabled');

            $('#produto_referencia').val('');
            $('#produto_descricao').val('');
            $('#produto_serie').val('');

        } else if ($('#tipo_atendimento').val() == 133 || $('#tipo_atendimento').val() == 134) {
            $('#produto_referencia').attr('disabled', 'disabled');
            $('#produto_descricao').attr('disabled', 'disabled');
            $('#produto_serie').attr('disabled', 'disabled');

            var ref = ($('#tipo_atendimento').val() == 133) ? 'GAR-BATER' : 'GAR-CARRE';

            $('#produto_referencia').val(ref);

            ref = document.getElementById("produto_referencia");

            pesquisaProduto(ref, 'referencia');

        }
    } else if (fabrica == 3){
        $('#div_mapa_msg').html('');
        $('#div_end_posto').html('');

        var produto = $("#produto_referencia").val();

        $.ajax({
            url:"os_cadastro.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"verificaLinha",
                produto:produto
            }
        })
        .done(function(data){
            if (data.ok) {
                if (data.retorno) {
                    document.getElementById('div_mapa').style.position = 'relative';
                    $('#div_mapa').show();
                } else {
                   document.getElementById('div_mapa').style.position = 'absolute';
                    $('#div_mapa').hide();
                }
            }
        });

    } else {

        $('#div_mapa_msg').html('');
        $('#div_end_posto').html('');

        var http_forn = new Array();
        /*Verificacao para existencia de componente - HD 22891 */
        if (document.getElementById('div_mapa') && document.getElementById(tipo_atendimento)){
            var ref = document.getElementById(tipo_atendimento).value;

            if((ref == 71) && ($('#posto_nome').val() == "")){
                alert('Por favor insira o Nome do Posto');
                $('#posto_nome').focus();
                $('#tipo_atendimento').val('');
                return;
            }

            url = "<?=$PHP_SELF?>?ajax=tipo_atendimento&id="+ref;
            var curDateTime = new Date();
            http_forn[curDateTime] = createRequestObject();
            http_forn[curDateTime].open('GET',url,true);
            http_forn[curDateTime].onreadystatechange = function(){
                if (http_forn[curDateTime].readyState == 4)
                {
                    if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
                    {
                        var response = http_forn[curDateTime].responseText.split("|");
                        if (response[1] == "sim" && (typeof $('#tipo_posto_descr').val() =="undefined" || $('#tipo_posto_descr').val() != 'SAC' )){
                            document.getElementById('div_mapa').style.position = 'relative';
							$('#div_mapa').show();
                        }else{
                            document.getElementById('div_mapa').style.position = 'absolute';
							$('#div_mapa').hide();
                        }
                    }
                }
            }
            http_forn[curDateTime].send(null);
        }

        <?php

        if(strlen($msg_erro) == 0){
          if($calculoKM == 't' && isset($_GET['os']) && $_GET['os'] != "") {
            ?>
            //$('#distancia_km').val('');
            if(($('#consumidor_nome').val() != "" && $('#consumidor_endereco').val() != "") || ($('#consumidor_cep').val() != "")){
                /*
                    Comentado no hd_chamado=2798091
                    Waldir pediu para retirar para todas as fabricas.
                    calcRoute();
                */
            }
            <?php
        }
    }else{
        echo "
        if(erroCadastro == 0){
            $('#distancia_km').val('$qtd_km');
            erroCadastro++;
        }else{";
            /*
                Comentado no hd_chamado=2798091
                Waldir pediu para retirar para todas as fabricas.
                calcRoute();
            */
        echo "
            }
        ";
    }

    ?>

}
}

function pesquisaProduto(campo, tipo) {
    var campo = $.trim(campo.value);
    var tipo_atendimento = $("#tipo_atendimento").val();

	var id_posto = $("#id_posto").val();
	var os = '<?=$os?>';

    if((id_posto > 0 && id_posto != undefined) || os > 0 ){
        if (campo.length > 2) {
            Shadowbox.open({
                content: "produto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo_atendimento="+tipo_atendimento+"&id_posto="+id_posto,
                player: "iframe",
                title: "Pesquisa de Produto",
                width: 800,
                height: 500
            });
        } else {
            alert("Informar toda ou parte da informação para realizar a pesquisa !");
        }
    }else{
        alert("Selecione um posto autorizado.");
    }
}


function verifica_digita_os_posto(linha){
  var num_linha = linha;
  var id_posto = $("#id_posto").val();
  $.ajax({
      type: "GET",
      datatype: 'json',
      url: "os_cadastro.php?verifica_digita_os_posto=true&linha="+num_linha+"&id_posto="+id_posto,
      cache: false,
      success: function(retorno){
          var dados = $.parseJSON(retorno);
          if(dados.resultado == 'erro'){
            alert('Esse posto não é autorizado a abrir O.S dessa linha.');
          }

          /*if(dados.deslocamento == 't'){
              $("#div_mapa").css({"position": "relative", "display": "block"});
          }*/
      }
  });
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
    gravaDados("produto_referencia",referencia);
    $('#produto_referencia').change();
    $('#produto_descricao').change();
    gravaDados("produto_descricao",descricao);
    gravaDados("produto_voltagem",voltagem);

    <?php if($login_fabrica == 74){ ?>
            verifica_digita_os_posto(linha);
    <?php } ?>

    <?php if($login_fabrica == 50){ //HD-3331834 ?>
        mostra_def();
    <?php } ?>
    <?php if($login_fabrica == 3){ //HD-3331834 ?>
        verifica_atendimento();
    <?php } ?>
    <?php if (isFabrica(19)): ?>
    // Falta a informação da linha!
    if (linha == 928) {
        $("#tipo_atendimento>option").attr('disabled', true);
        $("#tipo_atendimento>option[rel=LS],#tipo_atendimento>option[rel=all]").removeAttr('disabled');
    }
    if (linha != 928) {
        $("#tipo_atendimento>option").removeAttr('disabled');
        $("#tipo_atendimento>option[rel=LS]").attr('disabled', true);
    }
    libera_tipo_atendimento_garantia(referencia);
    <?php endif; ?>

}

function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

function mostra_def (){//HD-3331834
    var produto_referencia = $("#produto_referencia").val();
    $.ajax({
        type: "GET",
        url: "os_cadastro.php",
        data: {"produto_referencia":produto_referencia, "monta_defeitos": 'sim'},
        cache: false,
        success: function(data){
            data = JSON.parse(data);
            if (data.messageError == 'error') {
                alert("Não foi encontrado Defeito Reclamado");
            }else{
                $("#defeito_reclamado_descricao").find('option').remove();
                $("#defeito_reclamado_descricao").html(data);
            }
        }
    });
}
//------------------------------

function VerificaSuaOS (sua_os){
    if (sua_os.value != "") {
        janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
        janela.focus();
    }
}

function fazer_troca(){
    <?php if ($fabrica_gerencia_telecontrol) { ?>
        var produto_atual;
        var produto_novo;

        produto_atual = $('#produto_os_troca_atual').val();
        produto_novo  = $('#troca_garantia_produto').val();

        if (produto_atual != produto_novo ){

            if (confirm ('O produto da troca é diferente do produto da OS, deseja continuar?')) {

                if (document.getElementById('gerar_pedido')){

                    gerar_pedido = document.getElementById('gerar_pedido');
                    if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
                    else                     alert('Esta troca NÃO irá gerar pedido!');

                }

                if (confirm ('Confirma Troca?')) {
                    document.frm_troca.btn_troca.value='trocar';
                    if (document.frm_troca.orient_sac !=""){
                        document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value;
                    }

                    document.frm_troca.submit();

                }

            }else{

                $('#troca_garantia_produto').val("");
                $('#marca_troca').val("");
                $('#familia_troca').val("");
                $('html, body').animate( { scrollTop: 0 }, 'fast');

            }

        }else{

            if (document.getElementById('gerar_pedido')){

                gerar_pedido = document.getElementById('gerar_pedido');
                if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
                else                     alert('Esta troca NÃO irá gerar pedido!');

            }

            if (confirm ('Confirma Troca?')) {
                document.frm_troca.btn_troca.value='trocar';
                if (document.frm_troca.orient_sac !=""){
                    document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value;
                }

                document.frm_troca.submit();
            }
        }

<?php 
    }else{
        if($login_fabrica != 30){
?>
            if (document.getElementById('gerar_pedido')){
                gerar_pedido = document.getElementById('gerar_pedido');
                if(gerar_pedido.checked) alert('Esta troca irá gerar pedido!');
                else                     alert('Esta troca NÃO irá gerar pedido!');
            }
<?
        }
?>
        if (confirm ('Confirma?')) {
            document.frm_troca.btn_troca.value='trocar';

            if (document.frm_troca.orient_sac !=""){
                document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value;
            }
            document.frm_troca.submit();
        }
<?php 
    }
?>
}

function cancelar_os() {
    if (confirm ('Cancelar esta OS?')) {
        document.frm_cancelar.cancelar.value = 'cancelar';
        document.frm_cancelar.submit();
    }
}

// ========= Funcao PESQUISA DE POSTO POR C?DIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {

    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != ""){
        Shadowbox.open({
            content:    "posto_pesquisa_2_nv.php?" + tipo + "="+xcampo.value+"&tipo="+tipo+"&origem=os_cadastro",
            player:     "iframe",
            title:      "Pesquisa Posto",
            width:      800,
            height:     500
        });
    }else
    alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto,cep, endereco, numero, bairro, pais){
    gravaDados('posto_codigo',codigo_posto);
    gravaDados('posto_nome',nome);
    gravaDados('contato_endereco',endereco);
    gravaDados('contato_numero',numero);
    gravaDados('contato_bairro',bairro);
    gravaDados('contato_cep',cep);
    gravaDados('contato_cidade',cidade);
    gravaDados('contato_estado',estado);
    gravaDados('id_posto',posto);
    gravaDados('pais_posto',pais);

    carrega_estados_pais(pais);

<?php if ($login_fabrica == 59) { ?>
    campo_origem(codigo_posto);
<?php } ?>
}

function carrega_estados_pais(sigla) {


    if (sigla != "BR") {
        $('span[rel="consumidor_bairro"], span[rel="consumidor_cep"]').css({
            color: "black !important"
        });

        $("#consumidor_cep").removeClass("addressZip");
        $("#consumidor_estado").removeClass("addressState");

    }

    $("#consumidor_estado > option:not(:first)").remove();
    $("#consumidor_cidade > option:not(:first)").remove();

    if (jsonPaisEstado[sigla] != undefined) {

        $.each(jsonPaisEstado[sigla], function(key, objEstado) {

           $.each(objEstado, function(sigla, nome) {

                var option = $("<option></option>", { value: sigla, text: nome});

                $("#consumidor_estado").append(option);
            });

        });

    }

}

<?php if ($login_fabrica == 59) { ?>

function campo_origem(codigo_posto){

      $.ajax({
        url : "<?php echo $_SERVER['PHP_SELF']; ?>",
        type: "POST",
        data: {
          verifica_posto : "ok",
          posto : codigo_posto,
          ajax : true
        },
        complete: function(data){
          data = data.responseText.split('|');
          // console.log($('#origem'));
          if(data[1] == "sim"){
            $('#origem').removeAttr('disabled');
          }else if(data[1] == "nao"){
            $('#origem').attr('disabled', 'disabled');
          }else{
            $('#origem').attr('disabled', 'disabled');
          }
        }
      });
    }
<?php } ?>


function fnc_pesquisa_serie(campo) {//HD 256659
    //var valida = /^\d{10}[A-Z]\d{3}[A-Z]$/;
    var valida = /^\d{10}[A-Z0-9]{5}$/;

    if (campo.value.match(valida)) {

        var url = "produto_serie_pesquisa_britania.php?serie=" + campo.value;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");

        janela.serie      = campo;
        janela.referencia = document.frm_os.produto_referencia;
        janela.descricao  = document.frm_os.produto_descricao;
        janela.focus();

    } else {

        <?php if (!in_array($login_fabrica, [3])) { ?>
          alert("A pesquisa válida somente para o serial com 15 caracteres no formato NNNNNNNNNNLNNNL ou NNNNNNNNNNNNNNN !");
        <?php } else { ?> 
          alert("Esta pesquisa é válida somente para serial com 15 dígitos, formado por Letras e Números. Caso o serial tenha menos de 15 dígitos preencha com 0 (zeros) à esquerda.");
        <?php } ?>

    }

}

// ========= FUNCAO PESQUISA DE POSTO POR CODIGO OU NOME ========= //
function fnc_pesquisa_posto_km (campo, campo2, tipo) {

    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "posto_pesquisa_km<?=$pk_suffix?>.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;

        janela.contato_endereco= document.frm_os.contato_endereco;


        janela.contato_numero  = document.frm_os.contato_numero  ;
        janela.contato_bairro  = document.frm_os.contato_bairro  ;
        janela.contato_cidade  = document.frm_os.contato_cidade  ;
        janela.contato_estado  = document.frm_os.contato_estado  ;
		janela.contato_cep     = document.frm_os.contato_cep     ;
		janela.posto			= document.frm_os.id_posto;
        janela.tipo_posto      = document.frm_os.tipo_posto_descr;

        if ("<? echo $pedir_sua_os; ?>" == "t") {
            janela.proximo = document.frm_os.sua_os;
        }else{
            janela.proximo = document.frm_os.data_abertura;
        }
        janela.focus();
    }

    else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
}


// ========= Função PESQUISA DE PRODUTO POR REFERÊNCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    var tipo_atendimento = $("#tipo_atendimento").val();

    var id_posto = $("#id_posto").val();
	var os = '<?=$os?>';

    if((id_posto > 0 && id_posto != undefined) || os > 0 ){
        if (xcampo.value.length > 2) {
            Shadowbox.open({
                content: "produto_pesquisa_2_nv.php?"+tipo+"="+xcampo.value+"&tipo_atendimento="+tipo_atendimento+"&id_posto="+id_posto,
                player: "iframe",
                title: "Pesquisa de Produto",
                width: 800,
                height: 500
            });
        } else {
            alert("Informar toda ou parte da informação para realizar a pesquisa !");
        }
    }else{
        alert("Selecione um posto autorizado.");
    }


}

function busca_atendimento_produto_familia() {
    var produto_referencia = jQuery.trim($("#produto_referencia").val());
    //var total_input = $("#tipo_atendimento option").size();

    if(produto_referencia.length > 0){
        $("#tipo_atendimento").html('<option value="0"> Aguarde</option>');
        $.ajax({
            url : 'ajax_os_cadastro_unico.php',
            type : "POST",
            data : "tipo=atendimento_pela_familia_produto&produto_referencia=" + produto_referencia,
            success : function(retorno) {
                $("#tipo_atendimento").html(retorno);
                return false;
            }
        });
    }
}

// ========= Função PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "pesquisa_consumidor<?=$pc_suffix?>.php?nome=" + campo.value + "&tipo=nome&proximo=t";
    }
    if (tipo == "cpf") {
        url = "pesquisa_consumidor<?=$pc_suffix?>.php?cpf=" + campo.value + "&tipo=cpf&proximo=t";
    }
    if (campo.value != "") {
        if (campo.value.length >= 3) {
            janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
            janela.cliente      = document.frm_os.consumidor_cliente;
            janela.hidden_consumidor_nome       = document.frm_os.hidden_consumidor_nome;
            janela.nome         = document.frm_os.consumidor_nome;
            janela.cpf          = document.frm_os.consumidor_cpf;
            janela.rg           = document.frm_os.consumidor_rg;
            janela.cidade       = document.frm_os.consumidor_cidade;
            janela.estado       = document.frm_os.consumidor_estado;
            janela.fone         = document.frm_os.consumidor_fone;
            janela.endereco     = document.frm_os.consumidor_endereco;
            janela.numero       = document.frm_os.consumidor_numero;
            janela.complemento  = document.frm_os.consumidor_complemento;
            janela.bairro       = document.frm_os.consumidor_bairro;
            janela.cep          = document.frm_os.consumidor_cep;
            janela.proximo      = document.frm_os.revenda_nome;
            janela.focus();

            if($('#nome_posto').val() != ""){
                $('#consumidor_endereco').change(function(){
                    /*
                        Comentado no hd_chamado=2798091
                        Waldir pediu para retirar para todas as fabricas.
                        setTimeout(function(){
                            calcRoute();
                        }, 1500);
                    */
                });
            }

        }else{
            alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");
        }
    }
    else{
        alert("Digite pelo menos 3 caracteres para efetuar a pesquisa!");
    }

    /*$(janela).attr('onunload', function(){
        $("#consumidor_nome").trigger("focus");
        $("#consumidor_cpf").trigger("focus");
    });*/
}

// ========= Função PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda(campo, tipo) {
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:"pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player: "iframe",
            title:  "Pesquisa Revenda",
            width:  800,
            height: 500
        });
    }else
    alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Fun??o : ajustar_data (input, evento)
        Ajusta a formata??o da M?scara de DATAS a medida que ocorre
        a digita??o do texto.
        =================================================================*/
        function ajustar_data(input , evento)
        {
            var BACKSPACE=  8;
            var DEL=  46;
            var FRENTE=  39;
            var TRAS=  37;
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

        function fnc_num_serie_confirma(valor) {

            if(valor  =='sim'){
                document.getElementById('revenda_nome').readOnly =false;
                document.getElementById('revenda_cnpj').readOnly =true;
                document.getElementById('revenda_fixo').style.display='none';
                document.getElementById('revenda_fone').readOnly =true;
                document.getElementById('revenda_cidade').readOnly =true;
                document.getElementById('revenda_estado').readOnly =true;
                document.getElementById('revenda_endereco').readOnly =true;
                document.getElementById('revenda_numero').readOnly =true;
                document.getElementById('revenda_complemento').readOnly =true;
                document.getElementById('revenda_bairro').readOnly =true;
                document.getElementById('revenda_cep').readOnly =true;
            }else{
                document.getElementById('revenda_nome').readOnly =false;
                document.getElementById('revenda_cnpj').readOnly =false;
                document.getElementById('revenda_nome').value='';
                document.getElementById('revenda_cnpj').value='';
                document.getElementById('revenda_fixo').style.display='block';
                document.getElementById('revenda_fone').readOnly =false;
                document.getElementById('revenda_cidade').readOnly =false;
                document.getElementById('revenda_estado').readOnly =false;
                document.getElementById('revenda_endereco').readOnly =false;
                document.getElementById('revenda_numero').readOnly =false;
                document.getElementById('revenda_complemento').readOnly =false;
                document.getElementById('revenda_bairro').readOnly =false;
                document.getElementById('revenda_cep').readOnly =false;
                document.getElementById('revenda_fone').value='';
                document.getElementById('revenda_cidade').value='';
                document.getElementById('revenda_estado').value='';
                document.getElementById('revenda_endereco').value='';
                document.getElementById('revenda_numero').value='';
                document.getElementById('revenda_complemento').value='';
                document.getElementById('revenda_bairro').value='';
                document.getElementById('revenda_cep').value='';
            }
        }

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
            janela.nome         = document.frm_os.revenda_nome;
            janela.cnpj         = document.frm_os.revenda_cnpj;
            janela.fone         = document.frm_os.revenda_fone;
            janela.cidade       = document.frm_os.revenda_cidade;
            janela.estado       = document.frm_os.revenda_estado;
            janela.endereco     = document.frm_os.revenda_endereco;
            janela.numero       = document.frm_os.revenda_numero;
            janela.complemento  = document.frm_os.revenda_complemento;
            janela.bairro       = document.frm_os.revenda_bairro;
            janela.cep          = document.frm_os.revenda_cep;
            janela.email        = document.frm_os.revenda_email;

            janela.txt_nome         = document.frm_os.txt_revenda_nome;
            janela.txt_cnpj         = document.frm_os.txt_revenda_cnpj;
            janela.txt_fone         = document.frm_os.txt_revenda_fone;
            janela.txt_cidade       = document.frm_os.txt_revenda_cidade;
            janela.txt_estado       = document.frm_os.txt_revenda_estado;
            janela.txt_endereco     = document.frm_os.txt_revenda_endereco;
            janela.txt_numero       = document.frm_os.txt_revenda_numero;
            janela.txt_complemento  = document.frm_os.txt_revenda_complemento;
            janela.txt_bairro       = document.frm_os.txt_revenda_bairro;
            janela.txt_cep          = document.frm_os.txt_revenda_cep;

            janela.txt_data_venda   = document.frm_os.txt_data_venda;
            if (document.getElementById('revenda_fixo')){
                janela.revenda_fixo     = document.getElementById('revenda_fixo');
            }

    //PRODUTO
    janela.produto_referencia = document.frm_os.produto_referencia;
    janela.produto_descricao  = document.frm_os.produto_descricao;
    janela.produto_voltagem   = document.frm_os.produto_voltagem;
    janela.data_fabricacao    = document.frm_os.data_fabricacao;
    janela.focus();
}

function peganome(valor){
    <?
    $xmarca = $aux_marca;
    ?>
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function mostraDefeitoDescricao(fabrica) {
    var referencia = document.frm_os.produto_referencia.value;
    var td = document.getElementById('td_defeito_reclamado_descricao');
    td.style.display = 'none';

    url = "os_cadastro_ajax.php?acao=sql&sql=SELECT tbl_linha.linha FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha WHERE tbl_linha.fabrica="+fabrica+" AND tbl_produto.referencia='" + referencia + "'";

    requisicaoHTTP("GET", url, true, "trataDefeitoDescricao", fabrica);
}

//HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
function trataDefeitoDescricao(retorno, fabrica) {
    if (retorno == "528") {
        var td = document.getElementById('td_defeito_reclamado_descricao');
        td.style.display = 'block';
    }
    else {
        td.style.display = 'none';
    }
}

</script>

<script language='javascript' >

    var jsonPaisEstado = JSON.parse('<?= json_encode(array_map_recursive('utf8_encode', $array_pais_estado)) ?>');

    function atualizaValorKM(campo){
        if (campo.value == 'carro'){
            $('input[name=valor_por_km]').val( $('input[name=valor_por_km_carro]').val() );
        }
        if (campo.value == 'caminhao'){
            $('input[name=valor_por_km]').val( $('input[name=valor_por_km_caminhao]').val() );
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


    var http5 = new Array();
    var http6 = new Array();

    function busca_valores(){
        referencia   = $("input[@name='produto_referencia']").val();

        if (referencia.length > 0) {
            var curDateTime = new Date();
            http5[curDateTime] = createRequestObject();
            url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+referencia+'&data='+curDateTime;
            http5[curDateTime].open('get',url);

            http5[curDateTime].onreadystatechange = function(){
                if (http5[curDateTime].readyState == 4){
                    if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
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
        $("input[name=nota_fiscal]").numeric({allow:"CBWcbw-"}); /* Deu conflito e parou... ??? */
        $("input[name=nota_fiscal_saida]").numeric({allow:"-"}); /* Deu conflito e parou... ??? */
        $("input[name=consumidor_numero]").numeric();
        $('input[rel="data"]').datepick({startDate : "01/01/2000"});
            $('input[rel="data_recebimento"]').datepick({startDate : "01/01/2000"});
        $("#data_conserto").datepick({startDate : "01/01/2000"});
        $("#data_postagem").datepick({startDate : "01/01/2000"});
        $("#data_nf").datepick({startDate : "01/01/2000"});
        $("#data_nf_saida").datepick({startDate : "01/01/2000"});
        $("#data_fabricacao").datepick({startDate : "01/01/2000"});

        $('input[rel="data"]').mask("99/99/9999");
        $("#data_conserto").mask("99/99/9999");
        $("#data_postagem").mask("99/99/9999");
        $("#data_nf").mask("99/99/9999");
        $("#data_nf_saida").mask("99/99/9999");
        $("#data_fabricacao").mask("99/99/9999");
        $("#consumidor_cep").mask("00.000-000");
        $("#hora").mask("99:99");
        $("#consumidor_fone123").mask("(00) 0000-0000");
        $(".consumidor_fone123").mask("(00) 0000-00009");
        /*$("#txt_revenda_fone").mask("(99) 9999-9999");
        $("#consumidor_fone_comercial").mask("(99) 9999-9999");
        $("#consumidor_celular").mask("(99) 9999-9999");*/
        $(".content").corner("dog 10px");
        <?php if(in_array($login_fabrica, array(72))) {?>
            $("#consumidor_fone").mask("(99) 9999-99999");
        <?php }?>
        <?php if(!in_array($login_fabrica, array('1','7','3','19','52','72','74','122','11','85','172','141','144'))){?>
				<? if(strlen($consumidor_cpf) > 11) { ?>
					$("#consumidor_cpf").mask('00.000.000/0000-00');
				<? }else{ ?>
					$("#consumidor_cpf").mask('000.000.000-00');
				<? } ?>
            <?php }?>
            $("#revenda_cnpj").mask('00.000.000/0000-00');

        <?php if ($login_fabrica == 19) { ?>
                var cpf_cnpj_x = 0;
                cpf_cnpj_x = $("#consumidor_cpf").val().replace(/[^\d]+/g,'')
                if (cpf_cnpj_x.length >= 14) {
                  $("#consumidor_cpf").mask("99.999.999/9999-99");
                } else if (cpf_cnpj_x.length == 11) {
                  $("#consumidor_cpf").mask("999.999.999-99");
                }
        <?php } ?>
            
        //$(".money").numeric(); /* Deu conflito e parou... ??? */
        $(".money").maskMoney({ symbol : "", decimal : ".", thousands : '', precision : 2, maxlength : 9 }); /* Deu conflito e parou... ??? - R: Não sei não cara!*/
        $('input[name=troca_com_nota]').change(function(){
            var mostrar = ($('input[name=troca_com_nota]:checked').val() == 'sem_nota_com_troca') ? 'block' : 'none';
            $('#id_justificativanf').css('display', mostrar);
        });
        $('#id_justificativanf').css('display', ($('input[name=troca_com_nota]:checked').val() == 'sem_nota_com_troca') ? 'block':'none');
    });

 function somenteNumeros(e)
 {
    var tecla=new Number();
    if(window.event) {
        tecla = e.keyCode;
    }
    else if(e.which) {
        tecla = e.which;
    }
    else {
        return true;
    }
    if((tecla >= "97") && (tecla <= "122")){
        return false;
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
    $("input[@name='veiculo']").each( function (){
        if (this.checked){
            atualizaValorKM( this );
        }
    });
}
function valida_campo_ant(){
    <? if($login_fabrica==3){ ?>
        if(document.getElementById('marca_troca').value==""){
            alert("Preencha o Campo \"Marca do Produto\".");
            setFocus(document.getElementById('marca_troca'));
        }
        <?}?>
    }
    function buscaFamilia(marca) {
        //alert(marca);
        $.ajax({
            type: "GET",
            url: "ajax_busca_familia.php",
            data: "marca=" + marca,
            cache: false,
            beforeSend: function() {
                // enquanto a função esta sendo processada, você
                // pode exibir na tela uma
                // msg de carregando
            },
            success: function(txt) {
                // pego o id da div que envolve o select com
                // name="id_modelo" e a substituiu
                // com o texto enviado pelo php, que é um novo
                //select com dados da marca x
                $('#familia_troca').html(txt);
                //HD 215281: Deixar sem selecionar familia por padrão
                $('#familia_troca').val('');
            },
            error: function(txt) {
                alert(txt);
            }
        });
    }

    function listaProduto(valor,marca) {
    //verifica se o browser tem suporte a ajax
    try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
    catch(e) {
        try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
        catch(ex) {
            try {ajax = new XMLHttpRequest();}
            catch(exc) {alert("Esse browser nao tem recursos para uso do Ajax"); ajax = null;}
        }
    }
    if(ajax) {
            //deixa apenas o elemento 1 no option, os outros sÃ£o excluÃ­dos
            window.document.frm_troca.troca_garantia_produto.options.length = 1;

            //opcoes Ã© o nome do campo combo
            idOpcao  = document.getElementById("opcoes");

            //HD 83158
            <? if (in_array($login_fabrica, array(3, 81,  114))) { ?>
                var marca = document.getElementById("marca_troca").value;
                <?}?>

                ajax.open("GET", "ajax_produto_familia.php?familia="+valor+"&marca="+marca, true);
//          alert("ajax_produto_familia.php?familia="+valor);
ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
ajax.onreadystatechange = function() {
    if(ajax.readyState == 1) {
        idOpcao.innerHTML = "Carregando...!";
                }//enquanto estiver processando...emite a msg
                if(ajax.readyState == 4 ) {
                    if(ajax.responseXML) {
                        montaCombo(ajax.responseXML);//após ser processado-chama função
                    }else {
                        //caso nÃ£o seja um arquivo XML emite a mensagem abaixo
                        idOpcao.innerHTML = "Selecione a familia";
                    }
                }
            }
        //passa o cÃ³digo do produto escolhido
        var params = "linha="+valor;
        ajax.send(null);
    }
}

function montaCombo(obj){
        var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
        if(dataArray.length > 0) {//total de elementos contidos na tag cidade
            for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
                var item = dataArray[i];
                //contÃ©udo dos campos no arquivo XML
                var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
                var nome      =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
                idOpcao.innerHTML = "Selecione o produto";
                //cria um novo option dinamicamente
                var novo = document.createElement("option");
                //          echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";

                novo.setAttribute("id", "opcoes"); //atribui um ID a esse elemento
                novo.value = codigo;               //atribui um valor
                novo.text  = nome;                 //atribui um texto
                window.document.frm_troca.troca_garantia_produto.options.add(novo);//adiciona o novo elemento
            }

        } else {
            //idOpcao.innerHTML = "Selecione a família";//caso o XML volte vazio, printa a mensagem abaixo
            idOpcao.innerHTML = "Nenhum produto";
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

//se tiver suporte ajax
if(ajax) {
    //deixa apenas o elemento 1 no option, os outros são excluídos
    document.forms[0].defeito_reclamado.options.length = 1;
    //opcoes é o nome do campo combo
    idOpcao  = document.getElementById("opcoes");
    //   ajax.open("POST", "ajax_produto.php", true);
    ajax.open("GET", "ajax_produto.php?produto_referencia="+valor+"&tipo_atendimento="+tipo_atendimento, true);
    ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    ajax.onreadystatechange = function() {
        if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
        if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaComboDefeitoReclamado(ajax.responseXML);//após ser processado-chama fun
            } else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
        }
    }
}
    //passa o código do produto escolhido
    var params = "produto_referencia="+valor;
    ajax.send(null);
}
}

function montaComboDefeitoReclamado(obj){
    var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
    if(dataArray.length > 0) {//total de elementos contidos na tag cidade
    for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
       var item = dataArray[i];
        //contéudo dos campos no arquivo XML
        var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
        var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
        var rel    = item.getElementsByTagName("rel")[0].firstChild.nodeValue;

        idOpcao.innerHTML = "Selecione o defeito";

        //cria um novo option dinamicamente
        var novo = document.createElement("option");
        novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
        novo.setAttribute("rel", rel);     //atribuit um rel a esse elemento
        novo.value = codigo;        //atribui um valor
        novo.text  = nome;//atribui um texto
        document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
    }
    } else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
}
}


</script>

<?php if(in_array($login_fabrica, array(20))){ ?>
<script type='text/javascript' src='../js/jquery.numeric.js'></script>
<script type="text/javascript">
    $(function(){
        $("#produto_serie").numeric();
    });
</script>
<?php } ?>

<script type="text/javascript">

    var produto_troca = '';

    function fnc_pesquisa_troca_obrigatoria(referencia){
        var ref_pesquisa = referencia.value;
        var ref_descricao = document.frm_os.produto_descricao.value;

        if(ref_pesquisa.length > 0 && ref_descricao.length > 0 && produto_troca != ref_pesquisa){
            $.ajax({
                type: "POST",
                url: "<?=$PHP_SELF?>",
                data: "referencia_troca_obrigatoria=" + ref_pesquisa,
                success: function(retorno) {
                    if(retorno == 1){
                        produto_troca = ref_pesquisa;
                        var pergunta = confirm("Atenção!\nProduto com troca obrigatória. Deseja continuar?");
                        if (pergunta){
                            produto_troca = ref_pesquisa;
                            document.frm_os.produto_serie.focus();
                        }else{
                            produto_troca = '';
                            document.frm_os.produto_referencia.value = '';
                            document.frm_os.produto_descricao.value = '';
                            document.frm_os.produto_referencia.focus();
                        }
                    }
                }
            });
        }
        return false;
    }

    function atuSac(str,os) {

        //alert('atualiza_sac_ajax.php?str='+str+'&os='+os);
        requisicaoHTTP('GET','atualiza_sac_ajax.php?str='+str+'&os='+os, true , 'div_detalhe_carrega');

    }


    function div_detalhe_carrega (campos) {
        campos_array = campos.split("|");
        var msg = campos_array [0];
        var os = campos_array [1];

        if (msg=='ok') {
            alert('Sac Atualizado');
            window.location = "<?=$PHP_SELF?>?os="+os;
        }

    }


    /* Função mostra o campo quando muda o select(combo)*/
    function MudaCampo(campo){
        //alert(campo.value);
        if (campo.value== '15' || campo.value== '16' ) {
            document.getElementById('autorizacao_cortesia').style.display='block';
        }else{
            document.getElementById('autorizacao_cortesia').style.display='none';
        }
    }


    /******************* INTEGRIDADE ***************************/

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

    function BloqueiaNumeros(e){
        var tecla=new Number();
        if(window.event) {
            tecla = e.keyCode;
        }
        else if(e.which) {
            tecla = e.which;
        }
        else {
            return true;
        }
        if((tecla >= "48") && (tecla <= "57")){
            return false;
        }
    }

var singleSelect = true;  // Allows an item to be selected once only
var sortSelect = true;  // Only effective if above flag set to true
var sortPick = true;  // Will order the picklist in sort sequence

// Initialise - invoked on load
function initIt() {
    var pickList    = document.getElementById("PickList");
    var pickOptions = pickList.options;
    pickOptions[0]  = null;  // Remove initial entry from picklist (was only used to set default width)
}

// Adds a selected item into the picklist
function addIt() {

    var existe = false;
    var produto_descricao = $('#troca_garantia_produto option:selected').text();

    if ($('#troca_garantia_produto').val()=='')
        return false;

    if(parseInt($("#quantidade_item").val()) <= 0 || isNaN(parseInt($("#quantidade_item").val()))) {
        alert("Atenção, quantidade deve ser maior que 0");
        $("#quantidade_item").focus();
        return false;
    }

    $("#PickList option").each(function() {
        if($(this).val().indexOf(produto_descricao) >= 0) {
            existe = true;
            return;
        }
    });

    if(existe) {
        alert("Produto já existente na lista, para alterar a quantidade de um duplo clique no produto desejado");
        return;
    }

    var pickList                   = document.getElementById("PickList");
    var pickOptions                = pickList.options;
    var pickOLength                = pickOptions.length;
    pickOptions[pickOLength]       = new Option($('#troca_garantia_produto option:selected').text());
    pickOptions[pickOLength].title = "Duplo clique neste produto para editar a quantidade";
    pickOptions[pickOLength].value = '{"value":' + $('#troca_garantia_produto').val() + ',"quantidade":' + parseInt($("#quantidade_item").val()) + ',"texto":"' + $('#troca_garantia_produto option:selected').text() + '"}';

    if (sortPick) {
        var tempText;
        var tempValue;
        // Sort the pick list
        while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
            tempText = pickOptions[pickOLength-1].text;
            tempValue = pickOptions[pickOLength-1].value;
            pickOptions[pickOLength-1].text  = pickOptions[pickOLength].text;
            pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
            pickOptions[pickOLength].text    = tempText;
            pickOptions[pickOLength].value   = tempValue;
            pickOLength = pickOLength - 1;
        }
    }
    pickOLength = pickOptions.length;
    $('#troca_garantia_produto').focus();

}

function toogleProd(radio){

    var obj = document.getElementsByName('radio_qtde_produtos');

    if (obj[0].checked){
        $('#id_multi').hide("slow");
    }
    if (obj[1].checked){
        $('#id_multi').show("slow");
    }

}

// Deletes an item from the picklist
function delIt() {
  var pickList = document.getElementById("PickList");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    pickOptions[pickIndex] = null;
    pickIndex = pickList.selectedIndex;
}
}

// Selection - invoked on submit
function selIt(btn) {
    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;
    for (var i = 0; i < pickOLength; i++) {
        pickOptions[i].selected = true;
    }
}

function pesquisaNumeroSerie(serie, produto, posicao){
    var serie = jQuery.trim(serie.value);
    var produto = jQuery.trim(produto.value);

    var id_posto = $("#id_posto").val();

    if(id_posto > 0 && id_posto != undefined){
        if (serie.length > 2){
            Shadowbox.open({
                content:    "produto_serie_pesquisa_nv.php?serie="+serie+"&posicao="+posicao+"&produto="+produto+"&id_posto="+id_posto,
                player: "iframe",
                title:      "Pesquisa Número de Serie",
                width:  800,
                height: 500
            });
        }else{
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
        }
    }else{
        alert("Selecione um posto autorizado.");
    }
}

function retorna_numero_serie(produto,referencia,descricao, posicao,cnpj,nome,fone,email,serie,data_fabricacao){
    var data = data_fabricacao.split('-');
    data_fabricacao = data[2]+'/'+data[1]+'/'+data[0];
    gravaDados("produto_referencia",referencia);
    gravaDados("produto_descricao",descricao);
    gravaDados("produto_serie",serie);
    gravaDados("data_fabricacao",data_fabricacao);
}

function gravaDados(name, valor){
    try {
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

    <?if($login_fabrica == 114){?>
        $(function(){
            <?
                if(!strlen($msg_erro)){
            ?>
                    $("#mostra_tipo_atendimento").hide();
            <?
                }
            ?>

        var produto_referencia = '';
        var produto_descricao = '';
        var login_fabrica = '<?=$login_fabrica;?>';

        $("#produto_referencia, #produto_descricao").change(function(){

            if($('#produto_referencia').length > 0){
                produto_referencia = $('#produto_referencia').val();
            }
            if($('#produto_descricao').length > 0){
                produto_descricao = $('#produto_descricao').val();
            }

            $.ajax({
                type: "GET",
                url: "ajax_deslocamento_linha.php",
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
 <?php } ?>

 <? // HD 2502295
 if (in_array($login_fabrica, array(11, 172))) { ?>
    function fncMostraBuscaOS(ref_posto, nome_posto) {
        $.ajax({
            type: "POST",
            url: "os_cadastro.php",
            data: {mostraBuscaOS: true, refPosto: ref_posto, nomePosto: nome_posto},
        }).done(function(data) {
            data = $.parseJSON(data);
            if (data.sucesso) {
                $("#busca_os").attr("style", "visibility:visible;");
                $("#chckCortesia").attr("style", "visibility:visible;");
            } else {
                $("#busca_os").attr("style", "visibility:hidden;");
                $("#chckCortesia").attr("style", "visibility:hidden;");
            }
        });
    }

    function fncBuscaOS(os) {
        $.ajax({
            type: "POST",
            url: "os_cadastro.php",
            data: {buscaOS: true, codOS: os},
        }).done(function(data) {
            data = $.parseJSON(data);

            if (data.erro) {
                alert(data.erro);
            } else {
                $("#produto_referencia").val(data.produtoReferencia);
                $("#produto_descricao").val(data.produtoDescricao);
                $("#produto_serie").val(data.produtoSerie);
                $("#consumidor_nome").val(data.consumidorNome);
                $("#consumidor_cpf").val(data.consumidorCPF);
                $("#consumidor_fone").val(data.consumidorFone);
                $("#consumidor_cep").val(data.consumidorCEP);
                $("#consumidor_endereco").val(data.consumidorEndereco);
                $("#consumidor_numero").val(data.consumidorNumero);
                $("#consumidor_complemento").val(data.consumidorComplemento);
                $("#consumidor_bairro").val(data.consumidorBairro);
                $("#consumidor_cidade").val(data.consumidorCidade);
                $("#consumidor_estado option[value='"+data.consumidorEstado+"']").attr("selected", true);
                $("#consumidor_email").val(data.consumidorEmail);
                $("#consumidor_celular").val(data.consumidorCelular);
                $("#consumidor_fone_comercial").val(data.consumidorFoneComercial);
            }

        });
    }
<? } ?>

</script>

<? include "javascript_valida_campos_obrigatorios.php"; ?>
<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
        Verifica a exist?ncia de uma OS com o mesmo n?mero e em
        caso positivo passa a mensagem para o usu?rio.
        =============================================================== -->
        <?
        if (strlen ($msg_erro) > 0) {
            if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
            ?>

            <!-- ============= <HTML> COME?A FORMATA??O ===================== -->

            <table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700'>
                <tr>
                    <td valign="middle" align="center" class='error' id='erro_msg_'>
                        <?
    // retira palavra ERROR:
//echo $msg_erro;
                        if (strpos($msg_erro,"ERROR: ") !== false) {
                          $msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
                          $msg_erro = preg_replace('/^.+ERROR:/','',$msg_erro);
                      }

    // retira CONTEXT:
                      if (strpos($msg_erro,"CONTEXT:")) {
                        $x = explode('CONTEXT:',$msg_erro);
                        $msg_erro = $x[0];
                    }
                    echo $erro . str_ireplace('data de abertura', 'data de entrada do produto', $msg_erro);
                    //echo $erro . utf8_decode($msg_erro);
                    ?>
                </td>
            </tr>
        </table>
        <?php
        }elseif (strlen($msg_gravado_sucesso) > 0) {
        ?>
            <table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700' id="tbl_success_msg">
                <tr>
                    <td valign="middle" align="center" class="sucesso" id='sucesso_msg'>
                    <?=$msg_gravado_sucesso; ?>
                    </td>
                </tr>
            </table>
        <? }else{
            echo $msg_debug ;
            ?>
            <table border="0" cellpadding="1" cellspacing="1" align="center"  width = '700' style="display:none" id="tbl_erro_msg">
                <tr>
                    <td valign="middle" align="center" class='error' id='erro_msg_'>
                    </td>
                </tr>
            </table>
            <?
            }
        $sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
        $res = pg_query ($con,$sql);
        $hoje = pg_fetch_result ($res,0,0);
        ?>
        <style>
            .Conteudo{
                font-family: Verdana;
                font-size: 10px;
                color: #333333;
            }
            .Caixa{
                FONT: 8pt Arial ;
                BORDER-RIGHT:     #6699CC 1px solid;
                BORDER-TOP:       #6699CC 1px solid;
                BORDER-LEFT:      #6699CC 1px solid;
                BORDER-BOTTOM:    #6699CC 1px solid;
                BACKGROUND-COLOR: #FFFFFF;
            }

            fieldset.valores , fieldset.valores div{
                padding: 0.2em;
                font-size:10px;
                width:200px;
            }

            fieldset.valores label {
                float:left;
                width:43%;
                margin-right:0.2em;
                padding-top:0.2em;
                text-align:right;
            }

            fieldset.valores span {
                font-size:11px;
                font-weight:bold;
            }

            .tipo_atendimento_obg {
                display: none;
                color: red;
                font-weight: bold;
            }


            table.bordasimples {border-collapse: collapse;}

            table.bordasimples tr td {border:1px solid #000000;}

            .titulo_tabela{
                background-color:#596d9b;
                font: bold 14px "Arial";
                color:#FFFFFF;
                text-align:center;
            }


            .titulo_coluna{
                background-color:#596d9b;
                font: bold 11px "Arial";
                color:#FFFFFF;
                text-align:center;
            }

/*table tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #FFFFFF;
}*/

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:green;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;


}

.subtitulo{

    background-color: #7092BE;
    font: bold 13px "Arial";
    color:#FFFFFF;
    text-align:center;
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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.frm_obrigatorio{
    background-color: #FCC;
    border: #888 1px solid;
    font:bold 8pt Verdana;
}


</style>

<?php

if ($_GET["osacao"] == "trocar") {
    $display_frm_os = "none";
    $display_frm_troca = "block";
} else {
    $display_frm_os = "table";
    $display_frm_troca = "none";
}

?>
<!-- ------------- Formulário ----------------- -->
<form id="frm_os" name="frm_os" method="post" action="<? if (strtoupper($tipo_posto_descr) == 'SAC') { echo $PHP_SELF.'?os='.$os; }else{ echo $PHP_SELF; } ?>" enctype='multipart/form-data'>
    <table border="0"  class="formulario" align="center" width="700" style="display: <? echo $display_frm_os; ?>">
        <tr class="titulo_tabela"><td>Cadastrar Ordem de Serviço</td></tr>
        <?php
            if ($login_fabrica == 1 && isset($_REQUEST['shadowbox'])) { ?>
                <input type="hidden" name="shadowbox" value='<?= $_REQUEST['shadowbox'] ?>' />
        <?php
            }

        if ($login_fabrica == 19) { ?>
          <input type="hidden" name="garantia_lorenzetti" id="garantia_lorenzetti" value="<?= $garantia_lorenzetti ?>" />
        <?php
        } ?>
<!-- HD 194731: Coloquei o formulário da OS inteiro dentro de uma tag table para dar
    display:none quando ele não deve estar disponível -->
    <tr>
        <td>
            <table border="0" width='700' align="center">
                <tr>
                    <td valign="top" align="left" >

                        <?
                        if (strlen ($msg_erro) > 0) {

                            $consumidor_cidade      = $_POST['consumidor_cidade'];
                            $consumidor_estado      = $_POST['consumidor_estado'];
                            $consumidor_email       = trim ($_POST['consumidor_email']) ;
                            $consumidor_nome        = trim ($_POST['consumidor_nome']) ;
                            $consumidor_fone        = trim ($_POST['consumidor_fone']) ;
                            $consumidor_endereco    = trim ($_POST['consumidor_endereco']) ;
                            $consumidor_numero      = trim ($_POST['consumidor_numero']) ;
                            $consumidor_complemento = trim ($_POST['consumidor_complemento']) ;
                            $consumidor_bairro      = trim ($_POST['consumidor_bairro']) ;
                            $consumidor_cep         = trim ($_POST['consumidor_cep']) ;
                            $consumidor_rg          = trim ($_POST['consumidor_rg']) ;

                        }
                        ?>



                        <input class="frm" type="hidden" name="os" value="<? echo $os ?>">
                    </td>
                </tr>
                <? if ($login_fabrica == 19 OR $login_fabrica == 20 OR $login_fabrica == 30 OR $login_fabrica == 50 OR $login_fabrica == 7) { ?>
                <tr class="subtitulo" >
                    <td colspan="100%" width='650'>Informações do Atendimento</td>
                </tr>
                <? } ?>
                <? if ($login_fabrica==7) { // HD 75762 para Filizola ?>
                <tr>
                    <td>
                        <div style='font: 11px; Arial;
                        color:#333333; width:650px;' class='CaixaMensagem' >
                        Classificação da OS
                        <select name='classificacao_os' id='classificacao_os' class="frm">
                            <option <? if (strlen($classificacao_os)==0) {echo "selected";} ?>></option><?php
                            $sql = "SELECT  *
                            FROM    tbl_classificacao_os
                            WHERE   fabrica = $login_fabrica
                            AND     ativo IS TRUE
                            ORDER BY descricao";
                            $res = @pg_query($con,$sql);
                            if (pg_num_rows($res) > 0) {
                                for ($i = 0; $i < pg_num_rows($res); $i++) {
                                    $xclassificacao_os = pg_fetch_result($res,$i,'classificacao_os');
                                    if ($xclassificacao_os == 5 and $classificacao_os != 5) {
                                        continue;
                                    }
                                    echo "<option value='$xclassificacao_os'";
                                    if ($classificacao_os == $xclassificacao_os) echo " selected";
                                    echo ">".pg_fetch_result($res,$i,descricao)."</option>\n";
                                }
                            }?>
                        </select>
                    </div>
                </td>
            </tr>
            <? }

            echo "<tr><td colspan='1'>";
            if ($login_fabrica == 20) {
            //alterado gustavo HD 5909
                /*#####################################*/
                if($tipo_atendimento==15 or $tipo_atendimento==16) $mostrar = "display:block";
                else                                               $mostrar = "display:none";
                ?>
                <div id='autorizacao_cortesia' style="<?echo $mostrar;?>; width:600px;" >

                    <?
                    if($sistema_lingua)
                        echo "Autorización Cortesía";
                    else
                        echo "Autorização Cortesia";
                    ?>
                    &nbsp;<INPUT TYPE='text' NAME='autorizacao_cortesia' value='<? echo $autorizacao_cortesia; ?>' size='40' class="frm">

                    <br />
                    <font style="color:#FF0000;">
                        <?
                        if ($sistema_lingua)
                            echo "En el caso de comerciales o técnicos cortesía está obligado a informar el nombre de la persona que aprobó y la fecha de su aprobación";
                        else
                            echo 'Cortesia Comercial ou Técnica, é obrigatório informar o aprovante e a "data de aprovação"';
                        ?>
                    </FONT>

                </div>

                <?
            }
            ?>
        </td></tr>
    </table>
    <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
        <tr class="subtitulo"><td colspan="6">Informações do Posto</td></tr>
        <tr>
            <? if ($login_fabrica == 6){ ?>
            <td nowrap colspan="2">
                Número de Série
                <br>
                <input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" maxlength="9" value="<? echo $produto_serie ?>" onkeypress='javascript:mascara(this,soNumeros)' >
                &nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'>
            </td>
        </tr>
        <tr>
            <? } ?>
            <?php
            if (isset($posto_codigo) && isset($posto_nome) && in_array($login_fabrica, array(85))) {
                $readonly = 'readonly';
            }
            ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">

                    <span rel="posto_codigo">
                        Código do Posto
                    </span>
                </font>
                <br>
                <input type="hidden" name="pais_posto" id="pais_posto" value="<?= $pais_posto ?>" />
                <input class="frm" id="posto_codigo" type="text" name="posto_codigo"  size="15" value="<? echo $posto_codigo ?>"
                <? if (($login_fabrica == 5)) { ?>
                    onblur="fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
                <? // HD 2502295
                } else if (in_array($login_fabrica, array(11, 172))) { ?>
                    onblur="fncMostraBuscaOS(this.value,document.frm_os.posto_nome.value);"
                <? } ?> <?=$readonly;?>>&nbsp;
                <? if(!isset($readonly)){ ?>
                <img src='imagens/lupa.png' border='0' align='absmiddle'
                <?
                /*HD 2159-24601- PESQUISA DIFERENTE PARA CALCULO KM*/
                if (in_array($login_fabrica, array(30, 50))) { ?>
                onclick="javascript: fnc_pesquisa_posto_km(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
                <?}else{ ?>
                onclick="javascript: fnc_pesquisa_posto2(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"
                <?}?>
                style="cursor:pointer" >
                <?}?>

            <?if(!isset($id_posto)){
                $id_posto = $posto;
            }?>

            <input type='hidden' name='id_posto' id='id_posto' value='<?=$id_posto?>'>
            <input type='hidden' name='contato_endereco' id='contato_endereco' value='<?echo $contato_endereco;?>'>
            <input type='hidden' name='contato_numero' id='contato_numero' value='<?echo $contato_numero;?>'>
            <input type='hidden' name='contato_cep' id='contato_cep' value='<?echo $contato_cep;?>'>
            <input type='hidden' name='contato_bairro' id='contato_bairro' value='<?echo $contato_bairro;?>'>
            <input type='hidden' name='contato_cidade' id='contato_cidade' value='<?echo $contato_cidade;?>'>
            <input type='hidden' name='contato_estado' id='contato_estado' value='<?echo $contato_estado;?>'>
			<input type="hidden" name="LatLngPosto" id="LatLngPosto" value="<?=$LatLngPosto;?>">
        </td>

        <td nowrap>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                <span rel="posto_nome">
                    Nome do Posto
                </span>
            </font>
            <br>
            <input class="frm" id="posto_nome" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>"
            <? if ($login_fabrica == 50) { ?>
                onChange="javascript: this.value=this.value.toUpperCase();"
            <? }
            if ($login_fabrica == 5) { ?>
                onblur="fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')"
            <? // HD 2502295
            } else if (in_array($login_fabrica, array(11, 172))) { ?>
                onblur="fncMostraBuscaOS(document.frm_os.posto_codigo.value,this.value);"
            <? } ?><?=$readonly;?>>&nbsp;
            <? if(!isset($readonly)){ ?>
            <img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle'
            <? // HD 2159-24601- PESQUISA DIFERENTE PARA CALCULO KM
            if (in_array($login_fabrica, array(30, 50))) { ?>
            onclick="javascript: fnc_pesquisa_posto_km(document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')"
            <?}else{ ?>
            onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')"
            <?}?>
            style="cursor:pointer;">
            <?php
            }
                if (in_array($login_fabrica, array(30))) {
            ?>
                <input type='hidden' id="tipo_posto_descr" name='tipo_posto_descr' value='<?=$tipo_posto_descr; ?>'>
            <?php
                }
            ?>
        </td>

    </tr>
</table>

<table width="640" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="subtitulo"><td colspan="4">Informações do Fabricante</td></tr>
    <tr valign="top">
        <?php if($login_fabrica == 24){ ?>
        <td nowrap width='120' height="32">
            <font size='1' face='Geneva, Arial, Helvetica, san-serif'> <span rel='produto_referencia'>Número de Série</span></font>
            <br>
            <input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>">
        </td>
        <?php } ?>

        <td nowrap width="160" height="32" >
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                <span rel="data_abertura">
                    <?php echo getValorFabrica(['Data Abertura', 3 => 'Data de Entrada do Produto no Posto', 'Data Entrada']); ?>
                </span>
            </font>
            <br>

<?php
            if(in_array($login_fabrica, array(6, 120, 134, 140))){
                if(empty($data_abertura)){
                    $data_abertura = date("d/m/Y");
                }
                $readonly = "readOnly";
            }

            if($login_fabrica == 134) { ?>
                <input name="data_abertura" id='data_abertura' size="12" maxlength="10" value="<?=$data_abertura?>" type="text" class="frm" tabindex="0" readonly >
            <? } else { ?>
                <input name="data_abertura" id='data_abertura' rel="data" size="12" maxlength="10" value="<?=$data_abertura?>" type="text" class="frm" tabindex="0" <? echo $readonly; ?>>
            </td>
<?php
            }
            if ($login_fabrica == 1) {
                 if (empty($os)) {
?>
            <td>
                <input type="checkbox" name="garantia_pecas" id="garantia_pecas" value='t' <?=($garantia_pecas) ? "checked" : ""?> />Devolução de Peças (90 dias de garantia)
            </td>

<?php
                 } else {
?>
            <input type="hidden" name="tipo_atendimento" value="<?=$tipo_atendimento?>" />
<?php
                 }
            }

        if(in_array($login_fabrica, array(104))) { ?>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif" style="color: rgb(168, 0, 0);">
                    Data Recebimento Produto
                </font><br>
                <input name="data_recebimento_produto" id='data_recebimento_produto' rel="data_recebimento" size="12" maxlength="10" value="<? echo $data_recebimento_produto ?>" type="text" class="frm" tabindex="0" <? echo $readonly; ?>>
            </td>
        <? }

        // HD 2502295
        if (in_array($login_fabrica, array(11, 172))) {
            if ($posto != 14301) {
                $visibilidade_cod_os = "style='visibility: hidden;'";
            } else {
                $visibilidade_cod_os = "";
            } ?>
            <td id="busca_os" <?= $visibilidade_cod_os; ?>>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Busca OS (Cód.):</font>
                <br />
                <input name="cod_os" id='cod_os' rel="cod_os" size="12" maxlength="12" type="text" class="frm" tabindex="0" value="<?= $cod_os; ?>" />&nbsp;
                <img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fncBuscaOS($('#cod_os').val());" />
            </td>
        <? }

        if ($fabricas_tipo_atendimento) { ?>
        <td colspan="1" id='mostra_tipo_atendimento'>
            <? if ($login_fabrica == 7) { ?>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Natureza <br></font>
            <?}else{?>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                <span rel="tipo_atendimento">
                    Tipo de Atendimento
                </span>
            </font><br />
            <?}?>
            <select name="tipo_atendimento" id="tipo_atendimento" size="1" class="frm"
            <? if ($login_fabrica==20) {
                echo "onChange='MudaCampo(this)'";
            }else{

                if (in_array($login_fabrica,array(30,50,42,74,15,91,114,115,116,117,120,128)) || $usaCalculoKM) {
                   ?> onchange="javascript:verifica_atendimento('tipo_atendimento')"; <?
               }
           };?>>
           <option selected></option>
           <?

                            //IGOR  - HD 2909  | Garantía de repuesto - Não tem | Garantía de accesorios - Não tem | Garantía de reparación - Não tem
           $wr = "";
           if($login_fabrica == 20 ){
            if(strlen($posto)>0){
                $sql = "select pais from tbl_posto where posto =$posto";
                $res = pg_query ($con,$sql) ;
                $pais = pg_fetch_result ($res, 0, pais);

                if($pais == "PE"){
                    $wr = "AND tbl_tipo_atendimento.tipo_atendimento NOT IN(11, 12, 14) ";

                }
            }
        }

        if ($login_fabrica == 3) {
            $order_by = "codigo";
        } else {
            $order_by = "tipo_atendimento";
        }

        $sql = "SELECT *
        FROM tbl_tipo_atendimento
        WHERE fabrica = $login_fabrica
        AND   ativo IS TRUE
        $wr
        $sql_deslocamento
        ORDER BY $order_by";
        $res = pg_query ($con,$sql) ;

        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
            $tipo_atendimento_id        = pg_fetch_result($res, $i, "tipo_atendimento");
            $tipo_atendimento_codigo    = pg_fetch_result($res, $i, "codigo");
            $tipo_atendimento_descricao = pg_fetch_result($res, $i, "descricao");
            $km_google_aux              = pg_fetch_result($res, $i, "km_google");
            if ($login_fabrica == 42) {
                $tipo_atendimento_et = pg_fetch_result($res, $i, "entrega_tecnica");
            }

            $selected = ($tipo_atendimento == $tipo_atendimento_id) ? "SELECTED" : "" ;

            if($selected == "SELECTED" AND $km_google_aux == "t") {
                $km_google = 't';
            }

            if (isFabrica(19) and !$permiteLS and in_array($codigo, [15, 16, 18, 20]) and !in_array($codigo, ['00', '14'])) {
                $opt_sel .= ' rel="LS" ';
                // $selected .= $linha == 928 ? '' : ' disabled';
            }
            if (isFabrica(19) and in_array($codigo, ['00', '14'])) {
                $opt_sel .= ' rel="all"';
            }

            echo "<option value='{$tipo_atendimento_id}' rel='{$tipo_atendimento_et}' {$selected} >
            {$tipo_atendimento_codigo} - {$tipo_atendimento_descricao}
        </option>";
    }
    ?>
</select>

</font>
</td>
<?
}
if ((($login_fabrica == 11 or $login_fabrica == 172) and (strlen($admin_os)) and (strlen($os) > 0)) or $login_fabrica == 15) {
    echo "<td>";
                if ($login_admin == 532 or $login_fabrica == 15) { //HD 106085
                    echo "<input type='checkbox' name='admin_paga_mao_de_obra' id='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra'";
                    if ($admin_paga_mao_de_obra == 't') echo "checked";
                    echo ">";
                } else if ($login_admin <> 532 AND $admin_paga_mao_de_obra == 'f') {
                    echo "<input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra' disabled>";
                } else {
                    echo "<input type='hidden' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra'>";
                    echo "<input type='checkbox' name='admin_paga_mao_de_obra' value='admin_paga_mao_de_obra' checked disabled>";
                }
                echo "<br><font size='1' face='Geneva, Arial'>Pagar M<E3>o-de-Obra</font>";
                echo "</td>";
            }

            if ($login_fabrica == 7){ #HD 49336 ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora Abertura</font>
                <br>
                <?
                if (strlen($hora_abertura)==0){
                    #$hora_abertura = date("H:i"); //Vazio para forçar o preenchimento
                }else{
                    $hora_abertura = substr($hora_abertura,0,5);
                }
                ?>
                <input name="hora_abertura" size="7" maxlength="5" id='hora' value="<? echo $hora_abertura ?>" type="text" class="frm" tabindex="0" >
            </td>
            <?}?>

            <?php
            # HD 311411

            if($fabricas_alteram_conserto && $os > 0){
                $sqlConserto = "SELECT to_char(tbl_os.data_conserto,'DD/MM/YYYY') AS data_conserto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_conserto IS NOT NULL";
                $resConserto = pg_query($con,$sqlConserto);
                if(pg_num_rows($resConserto) > 0){
                    $data_conserto = pg_fetch_result($resConserto,0,'data_conserto');
                    if($login_fabrica == 6){
                        $readonly_conserto = ' readonly="readonly"';
                        $botao_limpar_conserto = '<input type="button" value="Limpar" onclick="$(\'#data_conserto\').val(\'\');"/>';
                    }
                    ?>
                    <td nowrap>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Conserto</font>
                        <br />
                        <input name="data_conserto" id="data_conserto" size="12" value="<? echo $data_conserto ?>" <?=$readonly_conserto;?> type="text" class="frm">
                        <?=$botao_limpar_conserto;?>
                    </td>
                    <?php
                }
            }?>


            <td nowrap>

                <? if ($pedir_sua_os == 't' && !in_array($login_fabrica,array(101,104,105,87,114,115,116,117,120,121,122,123,124,126,127,128,129,131,134,136,137,140,141,144)) ){ ?>

                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='sua_os'>OS Fabricante</font>
                <br>
                <input  name     ="sua_os"
                id       ="sua_os"
                class    ="frm"
                type     ="text"
                size     ="20"
                <?
                if ($login_fabrica == 5) {echo "maxlength='6'  ";} else { echo "maxlength='20'  ";}
                ?>
                value    ="<? echo $sua_os ?>"
                onblur   ="VerificaSuaOS(this); this.className='frm'; displayText('&nbsp;');"
                onfocus  ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
                <?
            } else {
                echo "&nbsp;";
                if (strlen($sua_os) > 0) {
                    echo "<input type='hidden' name='sua_os' value='$sua_os'>";
                }else{
                    echo "<input type='hidden' name='sua_os'>";
                }

            }
            if($login_fabrica == 87){?>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='produto_serie'>Número de Série</span></font>
            <br />
            <input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<?=$produto_serie?>" onchange=" this.value = this.value.toUpperCase();" />
            <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_numero_serie(document.frm_os.produto_serie, "produto_serie")' style='cursor: pointer' />
            <? }
            ?>

        </td><?php

        if (trim (strlen ($data_abertura)) == 0 AND ($login_fabrica == 7 OR $login_fabrica == 50)) {
            $data_abertura = $hoje;
        }

        if($login_fabrica == 87){
            echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='horas_trabalhadas'>Horas Trabalhadas</span></font><BR>";
            echo "<input type='text' class='frm' name='horas_trabalhadas' id='horas_trabalhadas' value='$horas_trabalhadas' size='9' maxlength='5'>";
            echo "</td>";
        }

        if ($login_fabrica == 50) {?>

        <td nowrap>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='produto_serie'>Número de Série</span></font>
            <br />
            <input class="frm" type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<?=$produto_serie?>" onchange=" this.value = this.value.toUpperCase();" />
            <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_numero_serie(document.frm_os.produto_serie, "produto_serie")' style='cursor: pointer' />
        </td>
        <?php
    } else {?>

    <td>&nbsp;</td>

    <? } ?>

    <? if ($login_fabrica == 19) { ?>
    <td nowrap>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
        <br>
        <input name="qtde_produtos" id="qtde_produtos" onkeypress="return somenteNumeros(event)" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" type="text" class="frm" tabindex="0" >
        <INPUT TYPE="hidden" NAME="qtde_km" value="<? echo $qtde_km; ?>">
        </td>
        <? } ?>
    </tr>
    <?php
    if (in_array($login_fabrica, [19])) { ?>
        <tr id="anexo_certificado" style="height: 40px;">
            <td align="center" colspan="100%">
                <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif;color: red;"> <?= traduz("Certificado de instalação") ?> </label>
                <input type='file' name='certificado_instalacao' class='frm' />
            </td>
        </tr>
    <?php
    } ?>
    <tr>
        <td nowrap>
            <?
            if ($login_fabrica == 3) {
                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
            }else{
                echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'> <span rel='produto_referencia'>Referência do Produto</span></font>";
            }
            ?>
            <br>
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
<?php
            if ($login_fabrica == 1 && (strlen($os) > 0 || !empty($garantia_pecas))) {
?>
                <input class="frm" type="text" name="produto_referencia" id="produto_referencia" value="<?=$produto_referencia?>" readOnly />
<?php
            } else {
?>
                <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>"
                <?php if ($login_fabrica == 5) {?>  onblur="fnc_pesquisa_produto2 (this, document.frm_os.produto_descricao,'referencia')" <? } ?>
                <?php if ($login_fabrica == 7) {?> onblur="busca_valores(); verificaProduto(document.frm_os.produto_referencia,this,document.frm_os.mapa_linha)"; <?} ?>
                <?php if ($login_fabrica == 50) {?> onChange="javascript:this.value=this.value.toUpperCase();"<?}?>
                >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia')">
<?php
            }
?>
            </td>
            <td nowrap width="280">
                <?
                if ($login_fabrica == 3) {
                    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
                }else{
                    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='produto_descricao'>Descrição do Produto</span></font>";
                }
                ?>
                <br>
                <!--<?  //if (strlen($pedido) > 0) { ?>!-->
                <!--<font size="1" face="Geneva, Arial, Helvetica, san-serif">
                <b><? //echo $produto_descricao ?></b>
                </font>
                <? //}else{ ?>
                <span class="tipo_atendimento_obg">*</span>&nbsp;<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? //echo $produto_descricao ?>"!-->
                <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
                <?php if($login_fabrica == 72 or $login_fabrica == 30){?>
                <input type='hidden' name='produto_descricao_anterior' id='produto_descricao_anterior' value=''>
                <?php
                }
                if ($login_fabrica == 1 && (strlen($os) > 0 || !empty($garantia_pecas))) {
?>
                <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<?=$produto_descricao?>" readOnly />
<?php
                } else {
                ?>
                <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<?=$produto_descricao?>"
                <? if (in_array($login_fabrica,array(98,106,108,111))) { ?>  onblur="fnc_pesquisa_troca_obrigatoria (document.frm_os.produto_referencia)" <? } ?>
                <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>
                <? if (($login_fabrica == 5)) { ?> onblur="fnc_pesquisa_produto2(document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')" <? } ?>
                <? if($login_fabrica==7) {?> onblur="busca_valores(); verificaProduto(document.frm_os.produto_referencia,this)"; <?} ?>
                >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript:fnc_pesquisa_produto2 (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"></A>
<?php
                }
?>
            </td>
            <? if (!in_array($login_fabrica,array(6,24,50,87,127))){ // HD-2296739 ?>
            <td nowrap>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?php
                    if($login_fabrica != 15) {
                        if ($login_fabrica == 35) {
                            echo 'PO#';
                        } elseif($login_fabrica == 137){
                            echo "Lote";
                        } else {
                            echo '<span rel="produto_serie">Número de Série</span>';
                        }
                        ?>
                    </font>
                    <br />
                    <span class="tipo_atendimento_obg">*</span>&nbsp;<input class="frm produto_serie" type="text" name="produto_serie" id="produto_serie" size="15" <? if ($login_fabrica == 35) {?> maxlength="12" <?} else if( $login_fabrica == 94){ ?>maxlength="12" <? } else {?> maxlength="20" <?}?> value="<?=$produto_serie?>" <? if ($login_fabrica == 50) {?>onchange="this.value = this.value.toUpperCase();"<?}?> <? if (in_array($login_fabrica,array(98,106,108,111))) { ?>  onblur="fnc_pesquisa_troca_obrigatoria (document.frm_os.produto_referencia)" <? } ?> /><?php
                }
                if ($login_fabrica == 25) {?>
                &nbsp;<input type="button" onclick='fn_verifica_garantia();' name='Verificar' value='Verificar' /><?php
            }
            if ($login_fabrica == 50 || $login_fabrica == 3) {//HD 256659?>
            <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='<?php if ($login_fabrica == 50) {?>fnc_pesquisa_numero_serie(document.frm_os.produto_serie, "produto_serie")<?php } else { ?> fnc_pesquisa_serie(document.frm_os.produto_serie) <?php }?>' style='cursor: pointer' /><?php
        }

        if($login_fabrica == 74 || $login_fabrica == 120){
            echo "<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: pesquisaNumeroSerie (document.frm_os.produto_serie, document.frm_os.produto_referencia)\" style='cursor:pointer;'>";
        }
        ?>
    </td><?php
}
if (in_array($login_fabrica, array(50,74,91,120,131))) {
    echo "<td valign='top' align='left'>
    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Fabricação</font>";
    echo "<br>";
    echo "<INPUT TYPE='text' name='data_fabricacao' id='data_fabricacao' class='frm' value='$data_fabricacao' size='9' maxlength='10'>";
    echo "</td>";
}
?>
</tr><?php
if($login_fabrica == 80 or $login_fabrica == 87 or $login_fabrica == 15){
    if($login_fabrica == 87){
        echo "<tr>";
        echo "<td>";
        echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='tipo_atendimento'>Tipo de Atendimento</span></font><br>";
        echo "<select name='tipo_atendimento' id='tipo_atendimento' class='frm'>";

        if(empty($produto_referencia))
            echo "<option value='0'> - informe um produto</option>";
        else{
            $sql = "
            SELECT
            tbl_produto.familia
            FROM
            tbl_produto
            JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
            WHERE
            tbl_produto.referencia = '$produto_referencia'
            AND tbl_linha.fabrica = $login_fabrica;";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) == 1){
                $familia = pg_fetch_result($res, 0, "familia");
                $sql = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND familia = $familia";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){
                                        //echo "<option value='0' selected>selecione um atendimento</option>";
                    for($i = 0; $i < pg_num_rows($res); $i++) {
                                            //extract(pg_fetch_array($res));
                        $cod_tipo_atendimento = pg_fetch_result($res,$i,'tipo_atendimento');
                        $descricao = pg_fetch_result($res,$i,'descricao');

                        $selected = ($tipo_atendimento == $cod_tipo_atendimento) ? " selected " : "";

                        echo "<option value='$cod_tipo_atendimento' label='$descricao' $selected>$descricao</option>";
                    }
                }
            }
        }

        echo "</select>";
        echo "</td>";
    }

    echo "<td colspan='2'><font size='1' face='Geneva, Arial, Helvetica, san-serif'><span rel='defeito_reclamado_descricao'>Defeito Reclamado</span></font><BR>";
    echo "<INPUT TYPE='text' id='defeito_reclamado_descricao' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='" . ($login_fabrica == 15 ? '55' : '50') . "' >";
    echo "</td>";

    if($login_fabrica == 15) {
        ?>
        <td>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Preço do Produto</font>
            <br />
            <input class="frm money" type="text" name="preco_produto" id="preco_produto" size="10" maxlength="8" value="<? echo $preco_produto ?>">
        </td>
        <?
    }
    echo "</tr>";
}

if($login_fabrica == 72){
     echo "<INPUT TYPE='hidden' id='defeito_reclamado_descricao' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='' >";
}

if ($login_fabrica == 7) {?>
<tr>
    <td nowrap  valign='top'>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Capacidade</font>
        <br>
        <? if (strlen($produto_capacidade)>0){
            echo "<INPUT TYPE='hidden' name='capacidade' class='frm' id='capacidade' value='$produto_capacidade'>";
            echo "<INPUT TYPE='text' VALUE='$produto_capacidade' class='frm' SIZE='9' onClick=\"alert('Não é possível alterar a capacidade')\" disabled>";
        }else{?>
        <INPUT TYPE="text" NAME="capacidade" class='frm'  id='produto_capacidade' VALUE="<?=$produto_capacidade?>" SIZE='9' MAXLENGTH='9'>
            <?}?>
        </td>
        <td nowrap  valign='top'>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Divisão</font>
            <br>
            <? if (strlen($produto_divisao)>0){
                echo "<input type='hidden' name='divisao' class='frm' value='$produto_divisao'>";
                echo "<INPUT TYPE='text' VALUE='$produto_divisao' id='produto_divisao' class='frm' SIZE='9' onClick=\"alert('Não é possível alterar a divisão')\" disabled>";
            }else{?>
            <INPUT TYPE="text" NAME="divisao" class='frm' id='divisao' VALUE="<?=$divisao?>" SIZE='9' MAXLENGTH='9'>
                <?}?>
            </td>
            <td nowrap>
            </td>
        </tr>
        <? } ?>
        <?if ($login_fabrica == 122) {?>
        <tr>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="nota_fiscal">Nota Fiscal</span></font>
                <br>
                <input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="20"  maxlength="20" id="nota_fiscal" value="<? echo $nota_fiscal ?>">

            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="data_nf">Data Compra</span></font>
                <br>
                <input class="frm" type="text" name="data_nf"  id="data_nf"  size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" >
            </td>
        </tr>
        <? } ?>
    </table>

    <?
                //hbtech 3/3/2008 14824
    if($login_fabrica == 25){
        ?>
        <div id='div_estendida' style='text-align:center'>
            <?if(strlen($produto_serie)>0){
                include "conexao_hbtech.php";

                $sql = "SELECT  idNumeroSerie  ,
                idGarantia     ,
                revenda        ,
                cnpj
                FROM numero_serie
                WHERE numero = '$produto_serie'";
                $res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

                if(mysql_num_rows($res)>0){
                    $idNumeroSerie = mysql_result($res,0,idNumeroSerie);
                    $idGarantia    = mysql_result($res,0,idGarantia);
                    $es_revenda    = mysql_result($res,0,revenda);
                    $es_cnpj       = mysql_result($res,0,cnpj);

                    if(strlen($idGarantia)==0){
                        echo "Número de série não encontrado nas vendas";

                    }
                }
            }
            ?>
        </div>
        <?
                }//fim hbtech

                if ($login_fabrica == 42) {
                    $pedir_defeito_reclamado_descricao = "f";
                }

                /*  HD: 110888 - LIBERADO PARA SUGGAR
                HD 413350 - Adicionar LeaderShip*/
                if ($aExibirDefeitoReclamado || $login_fabrica == 11 || $login_fabrica == 172 || $login_fabrica == 46|| $login_fabrica >= 101) {
?>

                    <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
                        <tr valign='top'>
                            <td valign='top' align='left'>
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="defeito_reclamado_descricao" id="defeito_reclamado_descricao_title"><? echo ($login_fabrica == 115 OR $login_fabrica == 116) ? 'Defeito Reclamado Cliente' : 'Defeito Reclamado'; ?></span></font><br>
                                <?
                                if($pedir_defeito_reclamado_descricao == 't' && !in_array($login_fabrica, array(120))){
                                    if($login_fabrica==50 || $login_fabrica == 11 || $login_fabrica == 172){//HD-3331834
                                        $onchange= "onChange=\"javascript: this.value=this.value.toUpperCase();\"";
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
															AND	tbl_diagnostico.ativo
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
                                                echo '<select class="frm" id="defeito_reclamado_descricao" name="defeito_reclamado_descricao" >
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
                                        echo "<INPUT TYPE='text' id='defeito_reclamado_descricao' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='50' $onchange>";
                                    }
                                    if($login_fabrica == 115 OR $login_fabrica == 116){
                                        echo "</td>";
                                        echo "<td> <span rel='defeito_reclamado'>Defeito Reclamado</span> <br>";
                                        echo "<select name='defeito_reclamado' class='frm' id='defeito_reclamado' style='width: 220px;' $onfocus_integridade_def_reclamado $defeito_reclamado_onchange >";

                                        $sql = " SELECT defeito_reclamado, descricao
                                        FROM tbl_defeito_reclamado
                                        WHERE fabrica=$login_fabrica
                                        AND ativo is TRUE";

                                        $res = pg_query($con,$sql);

                                        if(pg_num_rows($res) > 0){

                                            for ($y = 0; $y < pg_num_rows($res); $y++){
                                                $xdefeito_reclamado  = pg_fetch_result($res,$y,defeito_reclamado);
                                                $reclamado_descricao = pg_fetch_result($res,$y,descricao);

                                                echo "<option id='opcoes_$y' value='$xdefeito_reclamado'";
                                                if($defeito_reclamado==$xdefeito_reclamado) echo "selected";
                                                echo ">$reclamado_descricao</option>";
                                            }

                                        }
                                        echo "</select>";


                                    }
                                }else{

                    #HD 424887 - INICIO

                            /* ESTA VERIFICAÇÃO ESTÁ SENDO FEITA PORQUE PARA AS FABRICAS QUE ESTÃO NESTE ARRAY
                            A CHAMADA DESTA FUNÇÃO "listaDefeitos" SERÁ FEITA NO ONBLUR DO PRODUTO, POIS NÃO
                            HAVERÁ INTEGRIDADE COM O DEFEITO_RECLAMADO - by: gabriel silveira */

                            if (!$fabricas_defeito_reclamado_sem_integridade || $login_fabrica == 120){
                                $onfocus_integridade_def_reclamado = "onfocus='listaDefeitos(document.frm_os.produto_referencia.value);'";

                            }else{
                                $onfocus_integridade_def_reclamado = null;
                            }
                    #HD 424887 - FIM

                    //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                            if ($login_fabrica == 3) { $defeito_reclamado_onchange = "onchange='mostraDefeitoDescricao($login_fabrica)'"; }
                            echo "<select name='defeito_reclamado' class='frm' id='defeito_reclamado' style='width: 220px;' $onfocus_integridade_def_reclamado $defeito_reclamado_onchange >";

                            if(strlen($defeito_reclamado) > 0 || strlen($defeito_reclamado) == 0) {

                                if ($fabricas_defeito_reclamado_sem_integridade) {

                                    $sql = " SELECT defeito_reclamado, descricao
                                    FROM tbl_defeito_reclamado
                                    WHERE fabrica=$login_fabrica
                                    AND ativo is TRUE";

                                    $res = pg_query($con,$sql);

                                    if(pg_num_rows($res) > 0){

                                        for ($y = 0; $y < pg_num_rows($res); $y++){
                                            $xdefeito_reclamado  = pg_fetch_result($res,$y,defeito_reclamado);
                                            $reclamado_descricao = pg_fetch_result($res,$y,descricao);

                                            echo "<option id='opcoes_$y' value='$xdefeito_reclamado'";
                                            if($defeito_reclamado==$xdefeito_reclamado) echo "selected";
                                            echo ">$reclamado_descricao</option>";
                                        }

                                    }

                                }else{

                                    if(strlen(trim($defeito_reclamado)) > 0 ) {
                                        $sql = " SELECT defeito_reclamado, descricao
                                        FROM tbl_defeito_reclamado
                                        WHERE defeito_reclamado = $defeito_reclamado";
                                        $res = pg_query($con,$sql);
                                        if(pg_num_rows($res) > 0){
                                            $xdefeito_reclamado  = pg_fetch_result($res,0,defeito_reclamado);
                                            $reclamado_descricao = pg_fetch_result($res,0,descricao);
                                        }
                                    }
                                    echo "<option id='opcoes' value='$defeito_reclamado'";
                        #HD 242946
                                    if($defeito_reclamado==$xdefeito_reclamado) echo "selected";
                                    echo ">$reclamado_descricao</option>";

                                }

                            }else{

                                echo "<option id='opcoes' value='0'></option>";

                            }

                            echo "</select>";
                        }
                        echo "</td>";

                //HD 172561 - Usar tbl_os.defeito_reclamado e tbl_os.defeito_reclamado_descricao para Fabrica 3 e Linha 528
                        if ($login_fabrica == 3){
                            echo "<td style='display:none;' nowrap valign='top' id='td_defeito_reclamado_descricao'>Defeito Reclamado Adicional<br><INPUT TYPE='text' name='defeito_reclamado_descricao' class='frm' value='$defeito_reclamado_descricao' size='30' class='frm'></td>
                            <script language='javascript'>
                                mostraDefeitoDescricao($login_fabrica);
                            </script>
                            ";
                        }

                        if($login_fabrica == 52){ ?>
                            <td valign='top' align='left'>
                            <font face="Geneva, Arial, Helvetica, san-serif" size="1">
                            <span id="marca_fricon" rel="marca_fricon">Marca</span>
                            </font>
                            <br>
                            <select name='marca_fricon' size='1' class='frm' style='width:190px'>
                            <option value=''></option>
                            <?
                                $sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res)>0){
                                    for($i=0;pg_num_rows($res)>$i;$i++){
                                        $xmarca = pg_fetch_result($res,$i,marca);
                                        $xnome = pg_fetch_result($res,$i,nome);
                                        ?>
                                        <option value="<?echo $xmarca;?>" <? if ($xmarca == $marca_fricon) echo " SELECTED "; ?>><?echo $xnome;?></option>
                                        <?
                                    }
                                } ?>
                            </SELECT>
                            </td>
                    <?  }

                if ($login_fabrica == 19) { // HD 49849
                    echo "<td valign='top' align='left'>
                    Motivo";
                    echo "<br>";
                    echo "<SELECT NAME='tipo_os' style='width:150px' class='frm'>";
                    echo "<OPTION VALUE=''></OPTION>";
                    $sql = " SELECT tipo_os,
                    descricao
                    FROM tbl_tipo_os
                    WHERE tipo_os in (11,12)
                    ORDER BY tipo_os ";
                    $res = pg_query ($con,$sql) ;
                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
                        echo "<option ";
                        if ($tipo_os== pg_fetch_result ($res,$i,tipo_os) ) echo " selected ";
                        echo " value='" . pg_fetch_result ($res,$i,tipo_os) . "'>" ;
                        echo pg_fetch_result ($res,$i,descricao) ;
                        echo "</option>";
                    }
                    echo "</SELECT>";
                    echo "</td>";
                }

                if ($login_fabrica == 30) {
                    echo "<td valign='top' align='left'>
                    <font size='1' face='Geneva, Arial, Helvetica, san-serif'>OS Posto</font>";
                    echo "<br>";
                    echo "<INPUT TYPE='text' name='os_posto' class='frm' value='$os_posto' size='12' maxlength='20'>";
                    echo "</td>";
                }

                if(in_array($login_fabrica, array(11,172))){

                    ?>
                    <td valign="top" align="left">
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Interno</font>
                        <input type="text" name="codigo_interno" value="<?php echo (isset($_POST["codigo_interno"])) ? $_POST["codigo_interno"] : $versao; ?>" class="frm" maxlength="8">
                    </td>
                    <?php

                }

                if ($login_fabrica == 50) {
                    /*echo "<td valign='top' align='left'>
                        <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Fabricação</font>";
                        echo "<br>";
                    echo "<INPUT TYPE='text' name='data_fabricacao' id='data_fabricacao' class='frm' value='$data_fabricacao' size='12' maxlength='12'>";
                    echo "</td>";*/
                }

                /**
                * - hd-1921299
                * Cancelará a mão de obra da OS, que deverá ser zerada no extrato
                *
                * @author William Ap. Brandino
                * @fabrica Cadence
                */
                if($login_fabrica == 35 && strlen($os) > 0 && $cancela_mao_obra == "ok"){
                    ?>
                </tr>
                <tr>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Cancelar Mão-de-obra</font>
                        <br />
                        <?
                        $sqlCancela = "
                        SELECT  motivo_recusa,
                        motivo
                        FROM    tbl_motivo_recusa
                        WHERE   fabrica     = $login_fabrica
                        AND     liberado    IS TRUE
                        AND     status_os   = 81
                        ";
                        $resCancela = pg_query($con,$sqlCancela);
                        ?>
                        <input type="hidden" name="cancela_mao_obra" value="<?=$cancela_mao_obra?>" />
                        <select id="motivo_cancela_mao_obra" name="motivo_cancela_mao_obra">
                            <option value="">&nbsp;</option>
                            <?
                            if(pg_num_rows($resCancela) > 0){
                                foreach(pg_fetch_all($resCancela) as $valor){
                                    ?>
                                    <option value="<?=$valor['motivo_recusa']?>" <?echo $valor['motivo_recusa'] == $motivo_cancela_mao_obra ? 'selected' : ''; ?>><?=$valor['motivo']?></option>
                                    <?
                                }
                            }
                            ?>
                        </select>
                        <?
                        ?>
                    </td>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Protocolo</font>
                        <br />
                        <INPUT TYPE='text' name='protocolo_cancela_mao_obra' class='frm' value='<?=$protocolo_cancela_mao_obra?>' size='12' maxlength='20' />
                        </td>
                        <?
                    }
                    ?>

                </tr>
            </table>
            <?
        }elseif ($login_fabrica==11 or $login_fabrica == 172){
            echo "<input type='hidden' name='defeito_reclamado' value='$defeito_reclamado'>";
        }

        if($login_fabrica==19){
            echo "<center><font size='-2'>Antes de Gravar a OS Adicione os Defeitos</font></center>";
            echo "<center><input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'></center><br>";
            echo "
            <table class='tabela' style='display:none; margin-left:25px;' align='center' width='650' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
                <thead>
                    <tr bgcolor='#596D9B' style='color:#FFFFFF;'>
                        <td align='center' nowrap><b>Defeito Reclamado</b></td>
                        <td align='center' nowrap><b>Defeito Constatado</b></td>
                        <td align='center'><b>Ações</b></td>
                    </tr>
                </thead>
                <tbody>";
                    if(strlen($os)>0){
                        $sql_cons = "SELECT distinct
                        tbl_defeito_reclamado.defeito_reclamado ,
                        tbl_defeito_reclamado.descricao  AS dr_descricao   ,
                        tbl_defeito_reclamado.codigo     AS dr_codigo
                        FROM tbl_os_defeito_reclamado_constatado
                        JOIN tbl_defeito_reclamado USING(defeito_reclamado)
                        LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_os_defeito_reclamado_constatado.defeito_constatado
                        WHERE os = $os";
                        $res_dr = pg_query($con, $sql_cons);
                        if(pg_num_rows($res_dr) > 0){
                            for($x=0;$x<pg_num_rows($res_dr);$x++){
                                $dr_defeito_reclamado = pg_fetch_result($res_dr,$x,defeito_reclamado);
                                $dr_descricao         = pg_fetch_result($res_dr,$x,dr_descricao);
                                $dr_codigo            = pg_fetch_result($res_dr,$x,dr_codigo);
                                $aa = $x+1;
                                if($x % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
                                echo "<tr bgcolor='$cor'>";
                                echo "<td nowrap><font size='1'><input type='hidden' name='integridade_defeito_reclamado_$aa' value='$dr_defeito_reclamado'>$dr_codigo-$dr_descricao</font></td>";
                        // HD 33303
                                echo "<td align='left' nowrap>";
                                if(strlen($dr_defeito_reclamado) >0){
                                    $sql_dc="SELECT DISTINCT
                                    tbl_defeito_constatado.descricao         ,
                                    tbl_defeito_constatado.codigo
                                    FROM tbl_os_defeito_reclamado_constatado
                                    LEFT JOIN tbl_defeito_constatado USING(defeito_constatado)
                                    WHERE os = $os
                                    and   tbl_os_defeito_reclamado_constatado.defeito_reclamado = $dr_defeito_reclamado";
                                    $res_dc = pg_query($con, $sql_dc);
                                    if(pg_num_rows($res_dc) > 0){
                                        for($y=0;$y<pg_num_rows($res_dc);$y++){
                                            $dc_descricao = pg_fetch_result($res_dc,$y,descricao);
                                            $dc_codigo    = pg_fetch_result($res_dc,$y,codigo);
                                            if(strlen($dc_descricao) >0 ){
                                                echo "<font size='-2'>$dc_descricao</font><br>";
                                            }
                                        }
                                    }
                                    
                                    if (empty($dc_descricao) && $login_fabrica == 19) {
                                        $sql_defeito = "SELECT tbl_os.defeito_constatado, tbl_defeito_constatado.descricao
                                                        FROM tbl_os
                                                        JOIN tbl_defeito_constatado USING(defeito_constatado)
                                                        WHERE tbl_os.os = $os
                                                        AND tbl_os.fabrica = $login_fabrica";
                                        $res_defeito = pg_query($con, $sql_defeito);

                                        if(pg_num_rows($res_defeito)>0){
                                            for ($dc=0;$dc<pg_num_rows($res_defeito);$dc++){
                                                $dc_descricao = pg_fetch_result($res_defeito,$dc,'descricao');
                                                echo "<font size='-2'>$dc_descricao</font><br>";
                                            }
                                        }
                                    }
                                }
                    
                                echo "</td>";
                                echo "<td align='center'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
                                echo "</tr>";
                            }
                            echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
                        }
                    }
                    echo "</tbody></table>";
                }
                ?>


                <? if ($login_fabrica == 1) { ?>
                <table width="700" border="0" align="center" cellspacing="5" cellpadding="0">
                    <tr valign='top'>
                        <td width="20">&nbsp;</td>
                        <td nowrap width="170">
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
                            <br>
                            <input  name ="codigo_fabricacao" class ="frm" type ="text" size ="15" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de FabricaÃ§Ã£o.');">
                        </td>
                        <td nowrap width="260"><?// HD15589?>
                            <br>
                            <input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>&nbsp;
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT/Porter Cable</font>
                        </td>
                        <td nowrap>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
                            <br>
                            <input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo t?cnico.');">
                        </td>
                        <?php 
                            $sql_ad = " SELECT JSON_FIELD('numero_ad', campos_adicionais) AS numero_ad, 
                                               JSON_FIELD('numero_coleta', campos_adicionais) AS numero_coleta 
                                        FROM tbl_os_campo_extra 
                                        WHERE os = $os";
                            $res_ad = pg_query($con, $sql_ad);
                            if (pg_num_rows($res_ad) > 0) {
                                $numero_ad     = pg_fetch_result($res_ad, 0, 'numero_ad');
                                $numero_coleta = pg_fetch_result($res_ad, 0, 'numero_coleta'); 
                            }
                            if (!empty($numero_ad) || !empty($numero_coleta)) {
                            ?>
                        </tr>
                        <tr valign='top'> 
                            <td width="20">&nbsp;</td>
                            <td nowrap>
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Número AD</font>
                                <br>
                                <input  name ="numero_ad" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $numero_ad; ?>">
                            </td>
                            <td nowrap>
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Número Coleta</font>
                                <br>
                                <input  name ="numero_coleta" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $numero_coleta; ?>">
                            </td>
                        </tr> 
                            <?php    
                            } else {
                            ?>
                        </tr>
                            <?php
                            }
                            ?>
                    
                </table>
                <? } ?>

                <? if($login_fabrica == 117 || $login_fabrica == 128){ ?>
                <table width="650" align='center' border="0" cellspacing="5" cellpadding="0" >
                    <tr>
                        <td>
                            <?
                            $checked_garantia = ($garantia_estendida) ? "checked" : "";
                            ?>
                            <input type='checkbox' value='t' class='frm' name='garantia_estendida' id='garantia_estendida' <?=$checked_garantia?>>&nbsp;
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Garantia Estendida</font>
                        </td>
                        <td id='op_garantia_estendida' style='display:none;' align='left'>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Instalado por uma autorizada <? echo ($login_fabrica == 117) ? "Elgin" : "Unilever"; ?> : </font>
                            <input type='radio' name='opcao_garantia_estendida' value='t' class='frm' <? echo ($opcao_garantia_estendida == "t") ? "checked" : "";?>>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Sim</font> &nbsp;
                            <input type='radio' name='opcao_garantia_estendida' value='f' class='frm' <? echo ($opcao_garantia_estendida == "f") ? "checked" : "";?>>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Não</font>
                        </td>

                    </tr>
                    <tr id='nf_garantia_estendida' style='display:none;'>
                        <td colspan='2' align='center'>
                            <label title="Inserir a imagem digitalizada da CTI, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX. Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC." style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> <? echo ($login_fabrica == 117) ? "Anexar CTI:" : "Anexar Certificado de Garantia Estendida:"; ?> </label>
                            <span title="Inserir a imagem digitalizada da CTI, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX. Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC." style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
                            <input type='file' name='nf_garantia_estendida' class='frm' title="Inserir a imagem digitalizada da CTI, formatos JPG, JPEG, GIF, PNG, PDF, XML, DOC, DOCX.  Máx. 3 Megapixels para imagens ou 2Mb para PDF, XML e DOC.">
                        </td>
                    </tr>
                </table>
                <?php } ?>



                <input type="hidden" name="consumidor_cliente">
                <?
//      <input type="hidden" name="consumidor_cep">
                ?>
                <input type="hidden" name="consumidor_rg">

                <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
                    <tr class="subtitulo"><td colspan="3">Informações do <? echo ($login_fabrica == 122)? "Cliente" : "Consumidor"; ?></td></tr>
                    <tr>
                        <td width="330">
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                                <span rel="consumidor_nome">Nome <? echo ($login_fabrica == 122)? "Cliente" : "Consumidor"; ?></span>
                            </font>
                            <br>
                            <span class="tipo_atendimento_obg">*</span>&nbsp;<input class="frm dados_consumidor" id="consumidor_nome" type="text" name="consumidor_nome" size="35" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>  <? if ($login_fabrica == 5) { ?> onblur=" fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, 'nome'); displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';  displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img class="img_consumidor" src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
                            <input type="hidden" name="hidden_consumidor_nome" class="hidden_consumidor_nome" value="" />
                        </td>

                        <td nowrap>
                            <? if(in_array($login_fabrica, array(1,7,3,72, 141,144))){
                                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span style="color: rgb(168, 0, 0);">CPF/CNPJ</span></font>';
                            }else if(in_array($login_fabrica, [19,74,122])){
                                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CPF/CNPJ Cliente<span></font>';
                            }elseif(strlen(preg_replace('/\D/','', $consumidor_cpf)) == 14){
                                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CNPJ<span></font>';
                            }elseif($login_fabrica == 24){
                                if($consumidor_revenda == "C"){
                                    $checked_cpf = " checked ";
                                }elseif($consumidor_revenda == "R"){
                                    $checked_cnpj = " checked ";
                                }

                                if($consumidor_revenda == null){
                                    $checked = ' checked ';
                                }


                                echo '<input class="consumidor_revenda" type="radio" name="cpf_cnpj_revenda_suggar" value="CNPJ" '.$checked_cnpj .''.$checked.' >';
                                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CNPJ&nbsp;&nbsp;&nbsp;</span></font>';
                                echo '<input class="consumidor_revenda" type="radio" name="cpf_cnpj_revenda_suggar" value="CPF" '.$checked_cpf.'>';
                                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CPF</span></font>';
                            }else{
                                
                                echo '<font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cpf">CPF</span></font>';
                            }

                            ?>
                            <br />
                            <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;
                            
                            <input class="frm dados_consumidor" type="text" name="consumidor_cpf" id="consumidor_cpf" size="20" maxlength="19" value="<? echo $consumidor_cpf ?>"
                            <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,'cpf'); this.className='frm'; displayText('&nbsp;');" <? } ?>
                            onblur = "this.className='frm'; displayText('&nbsp;');"
                            onfocus ="this.className='frm-on';  displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e tra?os.');">&nbsp;
                            <img class="img_consumidor" src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer' />
                        </td>

                        <?php if(in_array($login_fabrica, array(1))){?>
                        <td width="140">
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Consumidor</font><br>
                            <SELECT id="id_tp_consumidor" NAME="fisica_juridica" class="frm"><?php //HD 235182 - Tinha BUG aqui para a Black ?>
                                <OPTION VALUE="F" <?php if($fisica_juridica=="F") echo "SELECTED"; ?>>Pessoa Física</OPTION>
                                <OPTION VALUE="J" <?php if($fisica_juridica=="J") echo "SELECTED"; ?>>Pessoa Jurídica</OPTION>
                            </SELECT>
                        </td>
                        <?php } elseif ($login_fabrica == 74) {
                            if (empty($_POST)) {

                                if(strlen(trim($os)) > 0){// Adicionada validação conteudo da OS, erro passado pela Marisa chamado 3531142
                                    $qry_c_extra = pg_query($con, "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os");
                                    $data_nascimento = '';
                                    if (pg_num_rows($qry_c_extra)) {
                                        $arr_c_adicionais = json_decode(pg_fetch_result($qry_c_extra, 0, 'campos_adicionais'), true);
                                        if (array_key_exists("data_nascimento", $arr_c_adicionais)) {
                                            $data_nascimento = $arr_c_adicionais["data_nascimento"];
                                        }
                                    }
                                }
                            }
                        ?>
                        <td width="140">
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                                <span rel="data_nascimento">Data de Nascimento</span>
                            </font>
                            <INPUT TYPE='text' rel='data' name='data_nascimento' id='data_nascimento' class='frm' value='<?php echo $data_nascimento ?>' size='16'>
                        </td>
                        <?php } else {?>
                        <td width="120">&nbsp;</td>
                        <?php } ?>
                    </tr>
                </table>
                <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
                    <tr>
                        <?
                        if (in_array($login_fabrica, [1])) { ?>
                            <td width="150">
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Profissão</font>
                                <br>
                                <input class="frm dados_consumidor" type="text" name="consumidor_profissao" id="consumidor_profissao" size="15" value="<?= $consumidor_profissao ?>" onkeyup="somenteMaiusculaSemAcento(this)" >
                            </td>
                            <td width="150">
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Celular</font>
                                <br>
                                <input class="frm telefone dados_consumidor" type="text" name="consumidor_celular" id="consumidor_celular" size="15" maxlength="15" value="<? echo $consumidor_celular ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                            </td>
                        <?php
                        } ?>
                        <td width="150">
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span <?=($login_fabrica == 123) ? : "rel='consumidor_fone'"?>>Fone</span></font>
                            <br>
                            <?php if ($login_fabrica == 123) { ?>
                                    <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="telefone frm dados_consumidor" id="consumidor_fone123" type="text" name="consumidor_fone" size="15" maxlength="15" value="<?php echo $consumidor_fone; ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                            <?php } else { ?>
                                    <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input class="telefone frm dados_consumidor" id="consumidor_fone" type="text" name="consumidor_fone" size="15" maxlength="15" value="<?php echo $consumidor_fone; ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                            <?php } ?>
                        </td>
<?php
                        if (in_array($login_fabrica,array(30,35,123))) {
?>
                            <td>
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span <?=($login_fabrica == 123) ? "rel='consumidor_fone'" : ''?>><?php echo (in_array($login_fabrica, [104,123])) ? "Celular" : "Telefone Celular"; ?> </span></font>
                                <br>
                                <?php if ($login_fabrica == 123) { ?>
                                    <input class="frm telefone consumidor_fone123" type="text" name="consumidor_celular" id="consumidor_fone" size="15" maxlength="15" value="<? echo $consumidor_celular ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                                <?php } else { ?>
                                        <input class="frm telefone" type="text" name="consumidor_celular" id="consumidor_celular" size="15" maxlength="15" value="<? echo $consumidor_celular ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                                <?php } ?>
                            </td>
                        <?php } ?>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel='consumidor_cep'>CEP</span></font>
                            <br>
                            <b style="color: #f00; display: none;" id="tipo_atendimento_obg">*</b>&nbsp;<input <? if($login_fabrica == 1 && $consumidor_revenda == 'R'){ ?> class="frm dados_consumidor" <?}else{?> class="frm addressZip" <?}?> id="consumidor_cep" type="text" name="consumidor_cep" id="consumidor_cep" size="10" maxlength="10" value="<? echo $consumidor_cep; ?>" <? if($login_fabrica == 1 && $consumidor_revenda == 'R'){?> <?}else{?> onblur="consumidor_numero.value='';displayText('&nbsp;');" onfocus="displayText('&nbsp;Digite o CEP do consumidor.');" <?}?> >
                        </td>
                        <?php
                        if (!in_array($login_fabrica, [1])) { ?>
                            <td>&nbsp;</td>
                        <?php
                        }
                        ?>
                    </tr>
                </table>

                <table width='650' align='center' border='0' cellspacing='2' cellpadding='2'>
                    <tr>
                        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_estado">Estado</span></font></td>

                        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_cidade">Cidade</span></font></td>

                        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_bairro">Bairro</span></font></td>
                    </tr>
                    <tr class="top">
                        <td>
                            <span class="tipo_atendimento_obg">*</span>
                            &nbsp;
                            <select id="consumidor_estado" name="consumidor_estado" class="frm addressState">
                            <option value="" >Selecione</option>
                            <?php

                            $pais_busca = (empty($pais_posto)) ? "BR" : $pais_posto;

                              $arrEstados = getListaDeEstadosDoPais($pais_busca);

                              foreach ($arrEstados as $key => $dadosEstados) {

                                $selected = ($consumidor_estado == $dadosEstados['sigla']) ? "selected" : "";

                                ?>
                                  <option value="<?= $dadosEstados['sigla'] ?>" <?= $selected ?>><?= $dadosEstados['descricao'] ?></option>
                              <?php
                              } ?>
                        </select>
                        </td>
                        <td>
                            <select id="consumidor_cidade" name="consumidor_cidade" class="frm addressCity" style="width:150px">
                                <option value="" >Selecione</option>
                                <?php
                                    if (strlen($consumidor_estado) > 0) {
                                        if (empty($pais_posto) || $pais_posto == "BR") { 
                                            $sql = "SELECT DISTINCT * FROM (
                                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                                        UNION (
                                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                                        )
                                                    ) AS cidade
                                                    ORDER BY cidade ASC";
                                          } else if ($pais_posto != "BR") {
                                            $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                                                    FROM tbl_cidade 
                                                    WHERE UPPER(estado_exterior) = UPPER('{$consumidor_estado}')
                                                    AND UPPER(pais) = UPPER('{$pais_posto}')
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
                        </td>
                        <td>
                            <span class="tipo_atendimento_obg">*</span>&nbsp;<input class="frm addressDistrict dados_consumidor" id="consumidor_bairro" type="text" name="consumidor_bairro" id="consumidor_bairro"  size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm addressDistrict'; displayText('&nbsp;');" onfocus="this.className='frm-on addressDistrict'; displayText('&nbsp;Digite o bairro do consumidor.');">
                        </td>

                    </tr>
                    <tr class="top">
                        <td width="300"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_endereco">Endereço</span></font></td>

                        <td width="170"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_numero">Número</span></font></td>

                        <td><font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_complemento">Complemento</span></font></td>

                    </tr>

                    <tr>
                        <td width="300">
                            <span class="tipo_atendimento_obg">*</span>&nbsp;<input class="frm address dados_consumidor" type="text" name="consumidor_endereco" id="consumidor_endereco" size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm address'; displayText('&nbsp;');" onfocus="this.className='frm-on address'; displayText('&nbsp;Digite o endere?o do consumidor.');">
                        </td>

                        <td width="69">
                            <span class="tipo_atendimento_obg">*</span>&nbsp;<input class="frm dados_consumidor" id="consumidor_numero" type="text" name="consumidor_numero" id="consumidor_numero"  size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endere?o do consumidor.');">
                        </td>

                        <td>
                            <input class="frm dados_consumidor" id="consumidor_complemento" type="text" name="consumidor_complemento"  id="consumidor_complemento" size="15" maxlength="20" value="<? echo $consumidor_complemento ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere?o do consumidor.');">
                        </td>


                    </tr>

<?php
                    if ($login_fabrica == 52) {
                        if ($os) {
                            $sql_pr = "SELECT obs FROM tbl_os_extra where tbl_os_extra.os = $os";
                            $res_pr = pg_query($con,$sql_pr);
                            $ponto_referencia = (pg_num_rows($res_pr)>0) ? pg_fetch_result($res_pr, 0, 0) : "" ;
                        }
?>
                <tr>
                    <td>
                        <label for="" style="font:10px 'Arial' " > Ponto de referencia </label>
                        <br>
                        <input type="text" name="ponto_referencia" id="ponto_referencia" value="<?=$ponto_referencia?>" class="frm" >
                    </td>
                </tr>
<?php
                    }
?>
            <tr class="top">
<?php
                    if ($login_fabrica == 1) {
?>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_possui_email">Consumidor deseja receber novidades por e-mail?</span></font>
                    <br />
                    <input type="radio" name="consumidor_possui_email" id="consumidor_possui_email" value="sim" />Sim
                    <input type="radio" name="consumidor_possui_email" id="consumidor_possui_email" value="nao" />Não
                </td>
<?php
                    }
?>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_email">E-mail</span></font>
                    <BR />
                        <input class="frm dados_consumidor" id="consumidor_email" type="text" name="consumidor_email"  size="30" maxlength="50" value="<? echo $consumidor_email ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
                    </td>
                    <?php if($login_fabrica == 35){

                        if (!empty($obs_adicionais)) {
                            $informaemail = $obs_adicionais;
                        }

                    ?>
                    <td>
                        <input type="radio" class="informaemail" name="informaemail" value="Não possui e-mail" <?=($informaemail == 'Não possui e-mail') ? " checked " : "" ?> >

                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_email">Não possui e-mail</span></font>
                    </td>
                    <td>
                        <input type="radio" class="informaemail" name="informaemail" value="Não deseja informar e-mail" <?= ($informaemail == "Não deseja informar e-mail") ? " checked " : "" ?> >
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_email">Não deseja informar e-mail</span></font>

                    </td>
<?php
                    }
                    if ($login_fabrica == 7) {
?>

                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Distância Cliente (KM)</font>
                        <br>
                        <input class="frm" type="text" name="deslocamento_km"   size="14" id='deslocamento_km' maxlength="7" value="<? echo $deslocamento_km ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';">
                    </td>
                    <td>
                    </td>
<?php
                    }
                    if ($login_fabrica == 59) {
                        if (strlen($posto) > 0) {
                            $sql = "SELECT tipo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
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
                            }else{
                                $bloquear = "disabled='disabled'";
                            }
                            ?>
                            <td>
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Origem</font>
                                <br>
                                <SELECT NAME='origem' id='origem' style='width:150px' <?php echo $bloquear ?> class="frm">
                                <!-- opções "RECEPÇÃO" e "SEDEX REVERSO" -->
                                <option value=""<? if (strlen($origem) == 0)     echo " selected "; ?>></option>
                                <option value="recepcao"<? if ($origem == "recepcao")    echo " selected "; ?>>Recepção</option>
                                <option value="sedex_reverso" <? if ($origem == "sedex_reverso") echo " selected "; ?>>Sedex reverso</option>
                                </SELECT>
                            </td>
                            <td>
                            </td>
                            <?
                        }else{
                            ?>
                            <td>
                                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Origem</font>
                                <br>
                                <SELECT NAME='origem' id='origem'  style='width:150px' class="frm">
                                <!-- opções "RECEPÇÃO" e "SEDEX REVERSO" -->
                                <option value=""<? if (strlen($origem) == 0)     echo " selected "; ?>></option>
                                <option value="recepcao"<? if ($origem == "recepcao")    echo " selected "; ?>>Recepção</option>
                                <option value="sedex_reverso" <? if ($origem == "sedex_reverso") echo " selected "; ?>>Sedex reverso</option>
                                </SELECT>
                            </td>
                            <td>
                            </td>
                            <?
                        }
                    }  ?>

                    <? if (in_array($login_fabrica, array(3,11,45,50,74,80,101,104,120,172))) { ?>

                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif"> <?php echo ($login_fabrica == 104) ? "Celular" : "Telefone Celular"; ?> </font>
                        <br>
                        <input class="frm telefone" type="text" name="consumidor_celular" id="consumidor_celular" size="15" maxlength="15" value="<? echo $consumidor_celular ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                    </td>
                    <?php }?>
                    <? if (in_array($login_fabrica, array(3,11,30,45,74,80,104,120,172))) { ?>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone Comercial</font>
                        <br>
                        <input class="telefone frm" type="text" name="consumidor_fone_comercial"  id="consumidor_fone_comercial" size="15" maxlength="20" value="<? echo $consumidor_fone_comercial ?>" onblur="this.className='telefone frm'; displayText('&nbsp;');" onfocus="this.className='telefone frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
                    </td>
                    <?}?>

                    <?if ($login_fabrica == 122) {?>

                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">CPD do Cliente</font>
                        <br />
                        <input class="frm" type="text" name="consumidor_cpd" id="consumidor_cpd"   size="25" value="<? echo $consumidor_cpd ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número do CPD');" />
                    </td>
                    <td>
                        <font size="1" face="Geneva, Arial, Helvetica, san-serif">Contato</font>
                        <br />
                        <input class="frm" type="text" name="consumidor_contato" id="consumidor_contato"   size="25" value="<? echo $consumidor_contato ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o nome do contato.');" />
                    </td>

                    <? } ?>
                </tr>
            </table>

            <?


            if( /*HD21590 - O POSTO DEVE ESTAR CARREGADO, SENÃO VAI APRESENTAR ERRO NA BUSCA EM BRANCO*/
                (strlen($posto) > 0) AND
                (
                    ($login_fabrica==1 AND $posto==6359) OR
                    ($login_fabrica==15 AND $posto==6359) OR
                    in_array($login_fabrica,array(56,57,50,46,30,91))
                    )
                ){
    //--== Calculo de Distância com Google MAPS =========================================

                $sql_posto = "SELECT contato_endereco AS endereco,
            contato_numero   AS numero  ,
            contato_bairro   AS bairro  ,
            contato_cidade   AS cidade  ,
            contato_estado   AS estado
            FROM tbl_posto_fabrica
            WHERE posto   = $posto
            AND   fabrica = $login_fabrica ";

            $res_posto = pg_query($con,$sql_posto);
            if(pg_num_rows($res_posto)>0) {
                $endereco_posto = pg_fetch_result($res_posto,0,endereco).', '.pg_fetch_result($res_posto,0,numero).' '.pg_fetch_result($res_posto,0,bairro).' '.pg_fetch_result($res_posto,0,cidade).' '.pg_fetch_result($res_posto,0,estado);
                if(strlen($distancia_km)==0) $distancia_km=0;
            }

            if(strlen($tipo_atendimento)>0){
                $sql = "SELECT tipo_atendimento,km_google
                FROM tbl_tipo_atendimento
                WHERE tipo_atendimento = $tipo_atendimento";
                $resa = pg_query($con,$sql);
                if(pg_num_rows($resa)>0){
                    $km_google = pg_fetch_result($resa,0,km_google);
                }
            }
        }
//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
// include '../gMapsKeys.inc';
        ?>

        <?php if($calculoKM == "t"){ ?>

        <style type="text/css">
            #GoogleMapsContainer{
                z-index: 888;
                position: relative;
                width: 700px;
                height: 400px;
                border: 2px solid #000;
            }
            #DirectionPanel{
                width: 250px;
                height: 400px;
                float: right;
                background-color: #fff;
                overflow: auto;
            }
            /*#GoogleMaps{
                width: 450px;
                height: 400px;
                float: left;
                background-color: #fff;
            }*/
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
        </style>

        <? } ?>

        <meta name="viewport" content="initial-scale=1.0, user-scalable=no">

        <?php if($calculoKM == "t"){

            ?>

        <!-- CSS e JavaScript Google Maps -->
        <!-- <link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet">
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt-br&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ"></script>
 -->
        <link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <script src="plugins/leaflet/leaflet.js" ></script>
        <script src="plugins/leaflet/map.js" ></script>
        <script src="plugins/mapbox/geocoder.js"></script>
        <script src="plugins/mapbox/polyline.js"></script>

        <!-- <div id="GoogleMapsContainer">
            <div style="margin-top: 5px; margin-left: 5px; position: absolute; z-index: 889;" id="fechamapa" onclick="fechaMapa();"><img src="../admin/imagens/close_black_opaque.png" /></div>
            <div id="GoogleMaps"></div>
            <div id="DirectionPanel"></div>
        </div> -->

        <div id="GoogleMapsContainer" style="display: none;">
            <div style="margin-top: 5px; margin-left: 5px; float: right; z-index: 9999999; position: relative; background-color: white;" id="fechamapa" onclick="fechaMapa();"><img src="imagens/close_black_opaque.png" /></div>
            <div id="GoogleMaps"></div>
            <!-- <div id="DirectionPanel"></div> -->
        </div>

        <?php } ?>

        <script language="javascript">

            <?php
            if($calculoKM == "t"){ ?>

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
                // var directionsService = new google.maps.DirectionsService();
                var map;

                /* INICIO - MAPBOX */
                var geocoder, c_latlon, c_lat, c_lon, p_latlon, p_lat, p_lon, LatLngPosto;
                var Map, Markers, Route, Geocoder, geometry;

                function calcRoute(){
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
                          //if(!verificarNumero(document.getElementById("consumidor_numero").value)){
                            consumidor += " "+document.getElementById("consumidor_numero").value;
                          //}
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

                    function liberar_campoKM_posto(){
                      <?php
                        if(in_array($login_fabrica, array(120))){
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

                    var endereco = Contato_endereco+" "+Contato_numero+" "+Contato_bairro+" "+Contato_cidade+" "+Contato_estado;

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

                        requestConsumidor = Geocoder.getLatLon();

                        requestConsumidor.then(
                            function(resposta_c) {

                                c_lat  = resposta_c.latitude;
                                c_lon  = resposta_c.longitude;
                                c_latlon = c_lat+","+c_lon;

                                //geocoder do posto
                                Geocoder.setEndereco({
                                    endereco: Contato_endereco,
                                    numero: Contato_numero,
                                    bairro: Contato_bairro,
                                    cidade: Contato_cidade,
                                    estado: Contato_estado,
                                    cep: Contato_cep,
                                    pais: Pais
                                });

                                requestPosto = Geocoder.getLatLon();

                                requestPosto.then(
                                    function(resposta_p) {
                                        p_lat  = resposta_p.latitude;
                                        p_lon  = resposta_p.longitude;
                                        p_latlon = p_lat+","+p_lon;
										LatLngPosto = $('#LatLngPosto').val();
										if(LatLngPosto !="") {
											p_latlon = LatLngPosto;
										}


                                        $.ajax({
                                            url: "controllers/TcMaps.php",
                                            type: "POST",
                                            data: {ajax: "route", origem: p_latlon, destino: c_latlon, ida_volta: "sim"},
                                            timeout: 60000
                                        }).done(function(data){
                                            data = JSON.parse(data);

                                            geometry = data.rota.routes[0].geometry;
                                            var kmtotal = data.total_km.toFixed(2);

                                            $('#ida_volta').html('<strong>Ida:</strong> '+data.km_ida+" &nbsp; <strong>Volta:</strong> "+data.km_volta);
                                            $('#distancia_km_conferencia').val(kmtotal);
                                            $('#distancia_km').val(kmtotal);
                                            $('#div_mapa_msg').html('Distância calculada <a href= "javascript: vermapa();">Ver mapa</a>');
                                            $('#div_end_posto').html("<strong>Endereço do Posto:</strong> "+endereco);
                                            $('#loading-map').hide();
                                        }).fail(function(){
                                            $('#loading-map').hide();
                                            alert('Erro ao tentar calcular a rota!');
                                        });
                                    }
                                )
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

                function vermapa(){
                    $("#GoogleMapsContainer").css({'display' : 'block'});

                    Map.load();

		    /* Marcar pontos no mapa */
		    Markers.remove();
                    Markers.clear();
                    Markers.add(c_lat, c_lon, "blue", "Cliente");
                    Markers.add(p_lat, p_lon, "red", "Posto");
                    Markers.render();
                    Markers.focus();

                    Router.remove();
                    Router.clear();
                    Router.add(Polyline.decode(geometry));
                    Router.render();
                }
                /* FIM - MAPBOX*/

function fechaMapa(){

    $("#GoogleMapsContainer").css({'display' : 'none'});

}

/* Fim Google Maps */

<?php } ?>

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

function verificarNS(numero){

    ns = numero.value;

    if (ns.length>0){

        var referencia = document.getElementById('produto_referencia').value;

        if (referencia.length==0){
            return false;
        }

        var curDateTime = new Date();

        url = "<?=$PHP_SELF ?>?verificarNumeroSerie=1&referencia="+referencia+"&ns="+ns+"&data="+curDateTime;

        $.ajax({
            url: url,
            type: "GET",
            success: function(result) {
                if(result.length > 1) {
                    alert(result);
                }
            }
        })
    }
}

$(document).ready(function() {

    <?php
        if($login_fabrica == 72 or $login_fabrica == 30){
    ?>
        $("#produto_descricao_anterior").val($("#produto_descricao").val());
    <?php } ?>

    $("#PickList").dblclick(function() {

        var texto     = $(this).find("option:selected").val();
        var de        = texto.indexOf("quantidade") + 12;
        var para      = texto.indexOf("texto") - 2;
        var value     = texto.substring(de, para);
        var new_value = parseInt(window.prompt("Digite a quantidade deste produto", value));

        if(isNaN(new_value)) return;

        texto = texto.replace(":"+value, ":"+new_value);

        $(this).find("option:selected").val(texto);
    });

    <?php
    if($login_fabrica == 87){
        echo "$('#tipo_atendimento').focus(function() {busca_atendimento_produto_familia();});";
    }
    ?>

    $("#causa_troca").change(function(){

        var causa_troca_id = $("select#causa_troca").val();

        // alert(causa_troca_id);
        if ( causa_troca_id.length > 0 ){

            $.post("<?php echo $PHP_SELF ?>",{causa_troca_select:causa_troca_id},
                function(resposta){
                    $("#causa_raiz").html(resposta);
                }
                );

        }

    });

});

</script>

<?php if(($calculoKM == "t" && strtoupper($tipo_posto_descr) !== 'SAC') || $login_fabrica == 3){ ?>

<div id='div_mapa' style='width:625px;margin-left:30px;background:#efefef;border:#999999 1px solid;font-size:10px;padding:5px;<?if($km_google<>'t') echo "display:none;position:absolute;";?>' >
    <b>Para Calcular a distância percorrida pelo técnico para execução do serviço(ida e volta):<br>
        Preencha todos os campos de endereço acima ou preencha o campo de distância</b>
        <br /><br />
        <span id="ida_volta"></span> <br />
        <input type="hidden" id="ponto1" value="<?=$endereco_posto?>" >
        <input type="hidden" id="distancia_km_maps" value="" >
        <?php
            if($login_fabrica == 30){
                $km_anterior = $qtde_km;
        ?>
                <input type='hidden' name='km_anterior' id='km_anterior' value='<?=$km_anterior?>'>
        <?php
            }

        ?>
        <input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='<?=$distancia_km_conferencia?>'>
        Distância: <input type='text' name='distancia_km' id='distancia_km' value="<?=$qtde_km?>" size='5'> KM
        <input  type="button" onclick="calcRoute();" value="Calcular Distância" size='5' >
        <img id="loading-map" src="imagens/grid/loading.gif" style="display: none; width: 22px; vertical-align: middle;" >
        <div id='div_mapa_msg' style='color:#FF0000'></div>
        <br>
        <div id='div_end_posto' style='color:#000000'>
            <B>Endereço do posto:</b>
                <u>
                    <?if(strlen($posto)>0){?>
                    <?=$contato_endereco . ", " . $contato_numero . ", " . $contato_bairro?>
                    <?}?>
                </u>
            </div>
        </div>

        <?php } ?>

        <?
        if ($login_fabrica == 50) {
            ?>
            <div id='revenda_fixo' style='display:none; background:#efefef; border:#999999 1px solid; width:700px;'>
                <table  width="650" border="0" cellspacing="5" cellpadding="0" align="center">
                    <tr class="subtitulo"><td colspan="4">Informações da Revenda</td></tr>
                    <tr valign='top'>
                        <td width="300">
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
                            <br>
                            <input class="frm" type="text" name="txt_revenda_nome" id="txt_revenda_nome" size="50" maxlength="50" value="" onkeyup="somenteMaiusculaSemAcento(this)" readonly>
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
                            <br>
                            <input class="frm" type="text" name="txt_revenda_cnpj" id="txt_revenda_cnpj" size="20" maxlength="18" id="txt_revenda_cnpj" value="" onkeyup="re = /\D/g; this.value
                            = this.value.replace(re, '');" readonly>
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
                            <br>
                            <input class="frm telefone" type="text" name="txt_revenda_fone" id="txt_revenda_fone" size="15" maxlength="15"  value="" readonly>
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
                            <br>
                            <input class="frm" type="text" name="txt_revenda_cep" id="txt_revenda_cep"  size="10" maxlength="10" value="" readonly>
                        </td>
                    </tr>
                </table>

                <table  width="650"  border="0" cellspacing="5" cellpadding="0">
                    <tr valign='top'>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. EAN</font>
                            <br>
                            <input class="frm" type="text" name="txt_cod_ean" id="txt_cod_ean" size="30" maxlength="50" value="" readonly>
                        </td>
                        <td>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Data do Faturamento</font>
                            <br>
                            <input class="frm" type="text" name="txt_data_venda" id="txt_data_venda" size="12" maxlength="10" value="" readonly>

                            <input class="frm" type="hidden" name="txt_revenda_endereco"    id="txt_revenda_endereco" value="">
                            <input class="frm" type="hidden" name="txt_revenda_numero"      id="txt_revenda_numero" value="">
                            <input class="frm" type="hidden" name="txt_revenda_complemento" id="txt_revenda_complemento"  value="">
                            <input class="frm" type="hidden" name="txt_revenda_bairro"      id="txt_revenda_bairro"  value="">
                            <input class="frm" type="hidden" name="txt_revenda_cidade"      id="txt_revenda_cidade" value="">
                            <input class="frm" type="hidden" name="txt_revenda_estado"      id="txt_revenda_estado"  value="" >
                            <input class="frm" type="hidden" name="produto_voltagem"      id="produto_voltagem"  value="" >

                        </td>
                    </tr>
                </table>

                <table  width="650"  border="0" cellspacing="5" cellpadding="0">
                    <tr valign='top'>
                        <td>
                            <font size="2" face="Geneva, Arial, Helvetica, san-serif" color='red'>
                                AS INFORMAÇÕES AUTOMÁTICAS QUE ESTÃO ACIMA SÃO AS MESMAS DA NOTA FISCAL DO CONSUMIDOR?
                            </font>
                        </td>
                        <td>
                            <input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('sim');" value="sim"> Sim
                        </td>
                        <td>
                            <input class="frm" type="radio" name="nf_confirma_num_serie" onclick="fnc_num_serie_confirma('nao');" value="nao"> Não
                        </td>

                    </tr>
                </table>
            </div>
            <?
        }
        ?>
        <? if($login_fabrica != 122){ ?>
        <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
            <tr class="subtitulo"><td colspan="4">Informações da Revenda</td></tr>
            <tr valign="top">
                <td width="300">
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="revenda_nome">Nome Revenda</span></font>
                    <br>
                    <input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: <? echo ($login_fabrica == 15) ? 'pesquisaRevendaLatina':'fnc_pesquisa_revenda';?> (document.frm_os.revenda_nome, "nome")' style='cursor: pointer' >
                </td>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="revenda_cnpj">CNPJ <?php echo $login_fabrica == 15 ? 'Raiz' : '' ?> Revenda</span></font>
                    <br>
                    <? if($login_fabrica == 15) { ?>
                    <input type="text" name="revenda_cnpj_raiz" class="frm" id="revenda_cnpj_raiz" value="<? echo $cnpj_raiz ?>" maxlength="8" ><img src="imagens/lupa.png" border="0" align="absmiddle" onclick="pesquisaRevendaLatina (document.frm_os.revenda_cnpj_raiz, 'cnpj' ) " style="cursor: pointer">
                    <? } ?>
                    <input class="frm" type="<?=($login_fabrica == 15 ? 'hidden' : 'text') ?>" name="revenda_cnpj" size="20" maxlength="18" id="revenda_cnpj" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); ">&nbsp;<? if($login_fabrica != 15) { ?><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer' /> <? } ?>
                </td>
            </tr>

            <tr>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="nota_fiscal">Nota Fiscal</span></font>
                    <br>
                    <input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="20"  maxlength="20" id="nota_fiscal" value="<? echo $nota_fiscal ?>"
                    <?php
                    if($login_fabrica==45){?>
                    onkeypress="mascara(this,soNumeros);"
                    <?php } ?>>
                </td>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="data_nf">Data Compra</span></font>
                    <br>
                    <input class="frm" type="text" name="data_nf"  id="data_nf"  size="12" maxlength="10" value="<? echo $data_nf ?>" tabindex="0" >
                </td>
            </tr>
            <? if($login_fabrica == 6) { ?>
            <tr valign="top">

                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">NF Saída</font>
                    <br>
                    <input class="frm" type="text" name="nota_fiscal_saida" id="nota_fiscal" size="8"  maxlength="8" id="nota_fiscal" value="<? echo $nota_fiscal_saida ?>">
                </td>
                <td>
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Data NF Saída</font>
                    <br>
                    <input class="frm" type="text" name="data_nf_saida"  id="data_nf_saida" size="12" maxlength="10" value="<? echo $data_nf_saida ?>" tabindex="0" >
                </td>
                <td colspan='2'>&nbsp;</td>
            </tr>
            <? } ?>
        </table>

        <?php if($login_fabrica == 87){?>
        <input type='hidden' name='consumidor_revenda' value='C' />
        <?php }else{?>
        <table width="650" border="0" cellspacing="5" cellpadding="2" align="center">
            <tr>
                <td width="295">
                    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="aparencia_produto">Aparência do Produto</span></font>
                    <br>
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

if ($login_fabrica == 114) {
    $a_aparencia = array('pt-br' => explode(',', 'NOVA SEM USO,USO NORMAL,USO INADEQUADO'));
}

echo array2select('aparencia_produto', 'aparencia_produto', $a_aparencia['pt-br'], $aparencia_produto, ' class="frm"', 'ESCOLHA', $login_fabrica==20);
}else if($login_fabrica==50){
    echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onChange=\"javascript: this.value=this.value.toUpperCase();\" onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";
}else{
    echo "<input class='frm' id='aparencia_produto' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";
}
?>
</td>

<td>
    <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="acessorios">Acessórios</span></font>
    <br>    
    <?
        if($acessorios == "null"){
            $acessorios = "";
        }
    ?>
    <input class="frm" id="acessorios" type="text" name="acessorios" size="30" value="<?=$acessorios ?>" <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess?rios deixados junto ao produto.');">
</td>

<? if ($login_fabrica == 1 AND 1==2) { # retirado por Fabio a pedido da Lilian - 28/12/2007 - Cadastro de troca somente na OS TROCA?>
<td>
    <font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br>
    <input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
</td>
<? } ?>
</tr>

<tr>
    <? if ($login_fabrica == 7) { # HD 32143 ?>
    <input type='hidden' name="consumidor_revenda" value='<? if (strlen($consumidor_revenda)==0 or strlen($os)==0) {echo 'C';}else{echo $consumidor_revenda;}?>'>
    <?}else{?>
    <td colspan="2" align='center'>
    <?php 
    if($login_fabrica == 24){?>
               <input type='hidden' name="consumidor_revenda" id="consumidor_revenda_hidden" value=''>
               <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_revenda">Consumidor</span></font>&nbsp;<input class='consumidor_revenda' type="radio" id="consumidor_revenda_suggar_cpf" name="consumidor_revenda_suggar" value='C'>
               <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_revenda">Revenda</span></font>&nbsp;<input class='consumidor_revenda' type="radio" id="consumidor_revenda_suggar_cnpj" name="consumidor_revenda_suggar" value='R'>
    <?php } else { ?>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_revenda">Consumidor</span></font>&nbsp;<input class='consumidor_revenda' type="radio" name="consumidor_revenda" value='C' <? if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; ?> <? if($login_fabrica==50){?>onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
        <font size="1" face="Geneva, Arial, Helvetica, san-serif"><span rel="consumidor_revenda">Revenda</span></font>&nbsp;<input class='consumidor_revenda' type="radio" name="consumidor_revenda" value='R' <? if ($consumidor_revenda == 'R') echo " checked"; ?>>&nbsp;&nbsp;
        <?}
        if(in_array($login_fabrica,array(11,42,117,123,124,125,127,128,129,132,134,136,139,141,144,172))){
            if (in_array($login_fabrica, array(11,172))) {
                if ($posto != 14301) {
                    $mostraCortesia = "style='visibility: hidden;'";
                } else {
                    $mostraCortesia = "";
                }
                $labelCortesia = "OS Brinde";
            } else if ($login_fabrica == 42) {

                $mostraCortesia = "";
                $labelCortesia = "Solicitação de Cortesia Comercial";
            } else {
                $mostraCortesia = "";
                $labelCortesia = "OS Cortesia";
            }
            $checked_cortesia = ($os_cortesia == "t") ? "checked" : "";

            echo "<span id='chckCortesia' $mostraCortesia><input type='checkbox' value='t' name='os_cortesia' class='frm' $checked_cortesia><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$labelCortesia</font></span>";
        }

        if($login_fabrica == 91){
            echo "<input type='checkbox' value='t' id='garantia_diferenciada' name='garantia_diferenciada' class='frm' $garantia_diferenciada><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Deixar Produto/Série Inativo</font>
            <input style='display:none;' class='frm' type='text' id='garantia_diferenciada_mes' name='garantia_diferenciada_mes' size='5' maxlength='20' value='$garantia_diferenciada_mes'><font style='display:none;' class='garantia_diferenciada_mes' size='1' face='Geneva, Arial, Helvetica, san-serif'> Qtde Meses</font>";
        }

        ?>
    </td>
    <? }
    ?>
</tr>

<?php

if($login_fabrica == 127){
    ?>
    <tr>
      <td align='left'><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cód. Rastreio</font><br /> <input type="text" name="codigo_rastreio" id="codigo_ratreio" class="frm" maxlength="13" value="<?=$codigo_rastreio?>" /> </td>
  </tr>
  <?php
}
?>


</table>

<?php }
}else{
    $checked_cortesia = ($os_cortesia == "t") ? "checked" : "";
    ?>
    <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
        <tr>
            <td>
                <input type='checkbox' value='t' name='os_cortesia' class='frm' <?=$checked_cortesia?>><font size='1' face='Geneva, Arial, Helvetica, san-serif'>OS Cortesia</font>
                <input type='hidden' name="consumidor_revenda" value='<? if (strlen($consumidor_revenda)==0 or strlen($os)==0) {echo 'C';}else{echo $consumidor_revenda;}?>'>
            </td>
        </tr>
    </table>
    <?php
}
//  MLG - 06/12/2010 - HD 321132 - O anexo de imagens à OS está 'unificado' em um include que serve
//                                 para todas as telas, admin e posto.

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

if ($anexaNotaFiscal && !in_array($login_fabrica, [15,137])) {
    $temNFs = temNF($os, 'count');

    if(in_array($login_fabrica, array(101))){

        for ($i = 0; $i < LIMITE_ANEXOS; $i++) {
            ?>

            <table align='center'>
                <tr>
                    <td colspan='2'>
                        <span class="tipo_atendimento_obg">*</span>
                        <?=$inputNotaFiscal?>
                    </td>
                </tr>
            </table>

            <?php
        }

    }

    if ($temNFs < LIMITE_ANEXOS && !in_array($login_fabrica, array(101)) && !fabricaFileUploadOS) {?>
    <table align='center'>
        <tr>
            <td colspan='2'>
                <span class="tipo_atendimento_obg">*</span>
                <?=$inputNotaFiscal?>
            </td>
        </tr>
    </table>
    <?}?>
    <?} ?>

    <?php
    if($login_fabrica == 11 or $login_fabrica == 172 OR $login_fabrica == 126 OR $login_fabrica == 137 OR $login_fabrica == 3){?>
    <table width="100%" align='center' border="0" cellspacing="5" cellpadding="0" >
      <tr>
          <td  align="center" width="100px" >
              <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif;"> Inserir Anexo: </label>
              <input type="file" class="frm" name="img_os_1" id="img_os_1"/>
          </td>
      </tr>
      <? if($login_fabrica != 126 AND $login_fabrica != 3){ ?>
      <tr>
          <td  align="center" width="100px" >
              <label style="position:relative;top:-3px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif;"> Inserir Anexo: </label>
              <input type="file" class="frm" name="img_os_2" id="img_os_2"/>
          </td>
      </tr>
      <? } ?>
  </table>
  <?}?>
  <p>

    <center>

        <?
            if (in_array($login_fabrica,array(3,52,74,131))){ #Chamado 941943
                if($orientacao_sac!="null" AND strlen($orientacao_sac)>0) {
                    ?>
                    <div style="width:650px;margin:auto" class="subtitulo">Orientações do SAC ao Posto Autorizado</div>

                    <br>
                    <textarea name='orientacao_sac_anterior' rows='4' cols='100' readonly='readonly' style='background-color:#FBFBFB;border:1px solid #4D4D4D' class="frm"><? echo trim($orientacao_sac); ?></textarea>
                    <br />
                    <?
                }
                ?>

                <div  style="width:650px;margin:auto">
                    <p class="subtitulo">Adicionar Orientação do SAC ao Posto Autorizado</p>
                    <?
                    if ($login_fabrica == 3 and strlen($os) > 0){

                        $sql = "SELECT
                        tbl_os_troca.os
                        FROM   tbl_os_troca
                        WHERE   tbl_os_troca.os      = $os
                        AND     tbl_os_troca.fabric = $login_fabrica
                        AND     tbl_os_troca.pedido IS NOT NULL;";
                        $res_troca = pg_query($con, $sql);
                        $msg_erro        = pg_errormessage($con);
                        if (pg_num_rows ($res_troca) > 0 ){
                            $alterar_os = 'f';
                        }
                    }
                    ?>

                    <textarea name='orientacao_sac' rows='4' style='width:650px' class="frm"></textarea>
                    <?
                    if ($alterar_os == 'f') {
                        ?>
                        <br><img src='imagens/btn_gravar.gif' style onclick="atuSac(document.frm_os.orientacao_sac.value,<? echo $os;?>);">
                        <?
                    }

                    if(strlen($os)>0){
                        ?>
                        <BR><BR>
                            <font size="1" face="Geneva, Arial, Helvetica, san-serif">Motivo da Exclusão</font>
                            <BR><INPUT TYPE="text" SIZE="50" NAME="obs_exclusao" VALUE="<? echo $obs_exclusao; ?>">
                                <?
                            }
                            ?>
                        </div>
                        <?
                    }else{
                        if ($login_fabrica == 11 or $login_fabrica == 172) {
                            ?>
                            <div style="width:650px;margin:auto">
                                <p class="subtitulo">Orientações do SAC ao Posto Autorizado</p>
                                <textarea name='orientacao_sac' rows='4' style='width:650px' class="frm"></textarea>
                            </div>
                            <?
                        } else {
                            ?>
                            <div style="width:650px;margin:auto">
                                <p class="subtitulo">Orientações do SAC ao Posto Autorizado</p>
                                <textarea name='orientacao_sac' rows='4' style='width:650px' class="frm" id="orientacao_sac"><? echo trim($orientacao_sac); ?></textarea>
                            </div>
                            <?
                        }
                    }
                    ?>
                    <br />
                </center>
                <? if ($os != '') { ?>

                <table width='650' align='center' border='0' cellspacing='2' cellpadding='2'>
                    <tr >
                        <td>Histórico de Orientações do SAC ao Posto Autorizado  </td>
                    </tr>
                </table>

                <table width='640' border="0" align="center" cellspacing="5" cellpadding="3" bgcolor='#CCCCCC' class="bordasimples">
                    <tr>
                        <td>
                            <? echo trim($orientacao_sac);?>
                        </td>
                    </tr>
                    <table>
                        <? }?>
<!--        <table width="100%" border="0" cellspacing="5" cellpadding="0">
        <tr>
        <hr>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Apar?ncia do Produto</font>
                <br>
                <input class="frm" type="text" name="aparencia_produto" size="35" value="<? echo $aparencia_produto ?>" >
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Acess?rios</font>
                <br>
                <input class="frm" type="text" name="acessorios" size="35" value="<? echo $acessorios ?>" >
            </td>
        </tr>
    </table>-->

    <?
    if ($login_fabrica <> 7) {
        echo "<input class='frm' type='hidden' name='obs' size='50' value='$obs'>";
        echo "<!-- ";
    }
    ?>

    <table width="650" border="0" cellspacing="5" cellpadding="0" align="center">
        <tr class="subtitulo"><td colspan="3">Informações do Chamado</td></tr>
        <tr>

            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado Aberto por</font>
                <br>
                <input class="frm" type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
            </td>
            <td>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
                <br>
                <input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
            </td>
        </tr>
    </table>


    <table width="650" border="0" cellspacing="5" cellpadding="0" align="center" class="formulario">
        <tr valign='top'>
            <td valign='top'>
                <fieldset class='valores' style='height:140px;'>
                    <legend>Deslocamento</legend>
                    <div>
                        <?  /*HD: 55895*/
                        if ($login_fabrica <> 7) {?>
                        <label for="cobrar_deslocamento">Isento:</label>
                        <input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
                        <br>
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
                        <input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
                        <br />
                    </div>

                    <div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
                        <label for="veiculo">Carro:</label>
                        <input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
                        <input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' >
                        <br>
                        <label for="veiculo">Caminhão:</label>
                        <input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
                        <input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
                        <input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>'>
                    </div>

                    <?if  (1==2){ #HD 32483 ?>
                    <div <? if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_desconto_deslocamento'>
                        <label>Desconto:</label>
                        <input type='text' name='desconto_deslocamento' value="<? echo $desconto_deslocamento ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
                    </div>
                    <?}?>
                </fieldset>
            </td>
            <td>
                <fieldset class='valores' style='height:140px;'>
                    <legend>Mão de Obra</legend>
                    <div>
                        <label for="cobrar_hora_diaria">Diária:</label>
                        <input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'diaria') echo "checked";?>>
                        <br>
                        <label for="cobrar_hora_diaria">Hora Técnica:</label>
                        <input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'hora') echo "checked";?>>
                        <br>
                    </div>
                    <div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
                        <label>Valor:</label>
                        <input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
                        <br>
<?/*                        <!--<br>
                        <label>Desconto:</label>
                        <input type='text' name='desconto_hora_tecnica' value="<? echo $desconto_hora_tecnica ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %-->
*/?>
</div>
<div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
    <label>Valor:</label>
    <input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
    <br>
<?/*                        <!--                        <br>
                        <label>Desconto:</label>
                        <input type='text' name='desconto_diaria' value="<? echo $desconto_diaria ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
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
            <input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
            <br />
<?/*                        <!--                        <br />
                        <label>Desconto:</label>
                        <input type='text' name='desconto_regulagem' value="<? echo $desconto_regulagem ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
                        <br />
-->
*/?>
<br />
<label>Certificado:</label>
<input type="checkbox" name="cobrar_certificado" value="t" <? if ($cobrar_certificado=='t') echo "checked" ?>>
<br />
<label>Valor:</label>
<input type="text" name="certificado_conformidade" value="<? echo number_format($certificado_conformidade,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
<br>
<?/*                        <!--                        <br />
                        <label>Desconto:</label>
                        <input type='text' name='desconto_certificado' value="<? echo $desconto_certificado ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
</div>
</fieldset>
</td>
</tr>
<tr style='font-size:10px' valign='top'>
    <td nowrap  valign='top'>
        <font size='1' face='Geneva, Arial, Helvetica, san-serif'>% DESCONTO PEÇAS</font><BR>
        <input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='10' maxlength='5'>
    </td>
    <td nowrap  valign='top'>
        <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Condição de Pagamento</font><BR>
        <SELECT NAME='condicao' style='width:150px' class="frm">
            <OPTION VALUE=''></OPTION>
            <?
            $sql = " SELECT condicao,
            codigo_condicao,
            descricao
            FROM tbl_condicao
            WHERE fabrica = $login_fabrica
            AND visivel is true
            ORDER BY codigo_condicao ";
            $res = pg_query ($con,$sql) ;
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
                echo "<option ";
                if ($condicao== pg_fetch_result ($res,$i,condicao) ) echo " selected ";
                echo " value='" . pg_fetch_result ($res,$i,condicao) . "'>" ;
                echo pg_fetch_result ($res,$i,descricao) ;
                echo "</option>";
            }
            ?>
        </SELECT>
    </td>
    <?
    echo "<td valign='bottom'>";
    if ($login_fabrica == 7) {
        echo "<input type='checkbox' name='imprimir_os' id='imprimir_os' value='imprimir'> <font size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>";
    }
    echo "</td>";
    ?>
</tr>
</table>

<?
if ($login_fabrica <> 7) {
    echo " --> ";
}
?>

</td>

</tr>
</table>

<table width="700" border="0" class="formulario" cellspacing="5" cellpadding="0" style="width: 706px;" align="center">
    <tr>
        <td height="27" valign="middle" align="center" >

            <input type="hidden" name="btn_acao" value="">
            <?


            /*IGOR HD: 47695 - 17/12/2008*/
            if ($login_fabrica == 7 AND strlen($os) > 0){
                $sql = "SELECT
                tbl_os_item.pedido
                FROM    tbl_os_item
                JOIN    tbl_os_produto             USING (os_produto)
                JOIN    tbl_os                     USING (os)
                JOIN    tbl_pedido                 ON tbl_os_item.pedido = tbl_pedido.pedido
                WHERE   tbl_os.os      = $os
                AND     tbl_os.fabrica = $login_fabrica
                AND     tbl_pedido.tipo_pedido <> 144;";

                $res_pedido = pg_query($con, $sql);
                $msg_erro        = pg_errormessage($con);

                if (pg_num_rows ($res_pedido) > 0 ){
                    $alterar_os = false;
                }else{
                    $alterar_os = true;
                }
            }
// HD 68376
            if ($login_fabrica == 3 and strlen($os) > 0){
                $sql = "SELECT
                tbl_os_troca.os
                FROM   tbl_os_troca
                WHERE   tbl_os_troca.os      = $os
                AND     tbl_os_troca.fabric = $login_fabrica
                AND     tbl_os_troca.pedido IS NOT NULL;";
                $res_troca = pg_query($con, $sql);
                $msg_erro        = pg_errormessage($con);
                if (pg_num_rows ($res_troca) > 0 ){
                    $alterar_os = 'f';
                }
            }

            if (strlen ($os) > 0) {
                if ($login_fabrica == 30 and !$login_cliente_admin){
                    $sqlAdmin = "
                        SELECT  tbl_admin.responsavel_postos
                        FROM    tbl_admin
                        WHERE   fabrica = $login_fabrica
                        AND     admin   = $login_admin;
                    ";
                    $resAdmin = pg_query($con,$sqlAdmin);
                    $cadastra_laudo = pg_fetch_result($resAdmin,0,responsavel_postos);

                    $sqlTroca = "
                        SELECT  os AS trocou_os
                        FROM    tbl_laudo_tecnico_os
                        WHERE   os = $os
                    ";
                    $resTroca = pg_query($con,$sqlTroca);
                    $trocou_os = pg_fetch_result($resTroca,0,trocou_os);

                    if($cadastra_laudo == 't' && strlen($trocou_os) == 0){
                        echo "<a href='" . $PHP_SELF . "?os=$os&osacao=trocar' title='Clique para abrir a tela de troca de produto'><img src='imagens/btn_trocarcinza.gif' style='cursor:pointer' border='0'></a>&nbsp;";
                    }
                } else if ($login_fabrica != 1){
                    echo "<a href='" . $PHP_SELF . "?os=$os&osacao=trocar' title='Clique para abrir a tela de troca de produto'><img src='imagens/btn_trocarcinza.gif' style='cursor:pointer' border='0'></a>&nbsp;";
                } else {
                    if (in_array($tipo_atendimento ,array(17,18,334))) {
                        echo "&nbsp;";
                    } else {
                        echo "<a href='os_cadastro_troca.php?os=$os&acao=troca' target='_blank'><img src='imagens/btn_trocarcinza.gif' style='cursor:pointer' border='0'></a>";
                    }
                }
                if (strtoupper($tipo_posto_descr) == 'SAC') {
                    echo "<img src='imagens/btn_gravar.gif' style='cursor:pointer' ";
                }else{
                    echo "<img src='imagens/btn_alterarcinza.gif' style='cursor:pointer' ";
                }
                /* HD: 47695 */
                if($login_fabrica<> 7  OR ($login_fabrica== 7 AND $alterar_os)){
            if ($login_fabrica == 3 and $alterar_os =='f') { // HD68376
                echo " onclick=\"javascript: alert('Produto já trocado, não pode alterar'); return false;\"";
            }else{
                if (in_array($login_fabrica,$fabricas_validam_campos_telecontrol)){
                    echo " onclick=\"func_submit_os()\" ";
                }else{                    
                    echo " onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ;  document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }\" ";                                    
                }
            }
            echo " ALT='Alterar os itens da Ordem de Serviço' ";
        }else{
            echo " ALT='Ordem de Serviço bloqueada para alteração por ter pedido gerado.' ";
        }
        echo "border='0'>";

        if(($login_fabrica==11 or $login_fabrica == 172 or ($login_fabrica == 15 and $consumidor_revenda == 'C')) AND !strlen($data_fechamento)){
            ?>
            <img src='imagens_admin/btn_fechar3.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente fechar esta OS?') == true) { document.frm_os.btn_acao.value='fechar_os'; document.frm_os.submit(); }else{ return; }; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Fechar Ordem de Serviço" border='0'>
            <? } ?>

			<?php
			$permitir_apagar = true;

			if ($login_fabrica == 19) {
				$sql_importa = "SELECT os FROM tbl_os WHERE os = $os AND importacao_fabrica IS NOT NULL";
				$qry_importa = pg_query($con, $sql_importa);

				if (pg_num_rows($qry_importa) > 0) {
					$permitir_apagar = false;
				}
			}
			?>

			<?php if ($permitir_apagar == true): ?>
            <img src='imagens_admin/btn_apagar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Apagar a Ordem de Serviço" border='0'>
			<?php endif ?>
            <?
        }else{
            if (!$validacao_dados_telecontrol){
                if (in_array($login_fabrica, array(24))) {
                    ?>
                    <input type="button" style='cursor:pointer;' value="Continuar"  onclick="javascript:
                    if (document.frm_os.btn_acao.value == '') {
                        if (fn_valida_consumidor_cpf(document.frm_os.consumidor_cpf.value, document.frm_os.consumidor_nome.value) == true) {
                            document.frm_os.btn_acao.value='continuar' ;
                            document.frm_os.submit()
                        }
                    } else {
                        alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.')
                    }
                    return false;" ALT="Continuar com Ordem de Serviço" border='0'>
                    <?
                } else if (in_array($login_fabrica, [19])) { ?>

                    <input type="button" value="Continuar" onclick="valida_garantias_adicionais()" />

                <?php
                } else {
                    ?>
                    <input type="button" style='cursor:pointer;' value="Continuar"  onclick="javascript: if (document.frm_os.btn_acao.value == '') { <? echo $login_fabrica == 15 ? ' if((!document.getElementById(\'admin_paga_mao_de_obra\').checked && confirm(\'Não será pago mão-de-obra para esta OS\')) || document.getElementById(\'admin_paga_mao_de_obra\').checked) {' : '' ?>document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() <? echo $login_fabrica == 15 ? '}' : '' ?>} else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') } return false;" ALT="Continuar com Ordem de Serviço" border='0'>
                    <?                    
                }

            }else{
                ?>
                <input type="button" style='cursor:pointer;' value="Continuar"  onclick="func_submit_os()" ALT="Continuar com Ordem de Serviço" border='0'>
                <?
            }
        }
        ?>
    </td>
</tr>
<!-- HD 194731: Coloquei o formulário da OS inteiro dentro de uma tag table para dar
    display:none quando ele não deve estar disponível -->

</table></td>

</tr>

</table>

<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>

</form>

<p>

    <?
    if(strlen($os) > 0) {
        if($login_fabrica == 11 or $login_fabrica == 172 OR $login_fabrica == 45 ){
/*      echo "<form method='post' name='frm_cancelar' action='$PHP_SELF?os=$os'>";
        echo "<table width='600' align='center' border='2' cellspacing='0' bgcolor='#F7D7D7' style='' class=''>";
        echo "<input type='hidden' name='os' value='$os'>";
        echo "<input type='hidden' name='cancelar' value=''>";
        echo "<tr>";
        echo "<td align='center' style='color: #F7D7D7'> ";
        echo "<font color='#3300CC' size='+1'> <b>Cancelar OS?</b> </font> ";
            echo "<table border='0' cellspacing='0' width='600'>";
            echo "<tr bgcolor='#F7D7D7' class='Conteudo'>";
            echo "<td align='left'><b>Motivo:</b></td>";
            echo "<td align='left'><textarea name='motivo_cancelamento' cols='100' rows='3' class='Caixa'>$motivo_cancelamento</textarea></td>";
            echo "</tr>";
            echo "</table>";
        echo "<input type='button' value='Cancelar' name='btn_cancelar' id='btn_cancelar' onclick=\"javascript: cancelar_os();\">";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</form>";
*/

    #HD 308346
        if(strlen($os)>0){
            $sqlStatus = "SELECT to_char(data, 'dd/mm/yyyy') AS data,
            (select descricao from tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
            observacao,
            (select nome_completo from tbl_admin where tbl_admin.admin = tbl_os_status.admin) AS admin,
            max(os_status)
            FROM tbl_os_status
            WHERE tbl_os_status.os = $os
            GROUP BY data,
            status_os    ,
            observacao   ,
            admin";
            $resStatus = pg_exec($con, $sqlStatus);

            if(pg_numrows($resStatus)>0){
                $data       = pg_result($resStatus,0,data);
                $status_os  = pg_result($resStatus,0,status_os);
                $observacao = pg_result($resStatus,0,observacao);
                $admin      = pg_result($resStatus,0,admin);

                echo '<br>';
                echo "<table width='500' align='center' border='0' cellspacing='1' cellpadding='0' class='Tabela'>";
                echo "<tr class='titulo_tabela'>";
                echo "<td colspan='4'>Status da OS</td>";
                echo '</tr>';
                echo "<tr class='subtitulo'>";
                echo '<td>Data</td>';
                echo '<td>Status</td>';
                echo '<td>Obs</td>';
                echo '<td>Admin</td>';
                echo '</tr>';
                echo "<tr style='background-color: #ffffff; font-size: 9pt;'>";
                echo "<td>$data</td>";
                echo "<td>$status_os</td>";
                echo "<td>$observacao</td>";
                echo "<td>$admin</td>";
                echo '</tr>';
                echo '</table>';
                echo '<br>';
            }
        }
    #HD 308346 - Fim

        #HD49669 . Efetuando este cancelamento não verifica se está em extrato e se tiver não faz o recalculo
        $sql2 = "SELECT extrato FROM tbl_extrato JOIN tbl_os_extra using(extrato) WHERE tbl_os_extra.os = $os AND tbl_os_extra.extrato IS NOT NULL;";
        $res2 = pg_query($con,$sql2);
        //HD 194731: Coloquei nas tables abaixo um display com variável para ocultar quando for troca
        if(pg_num_rows($res2) == 0){

            echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#FFDDDD' style='border:#660000 1px solid; display:$display_frm_os;' >";
            echo "<tr>";
            echo "<td align='center' style='color: #ffffff'> ";
            echo "<a href='os_cancelar.php?os=$os' style='font-size:12px'>Cancelar esta OS e informar o motivo</a>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "<center><br>";

        }else{
            $extrato_rec = pg_fetch_result($res2,0,0);
            echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#FFDDDD' style='border:#660000 1px solid; display:$display_frm_os;' >";
            echo "<tr>";
            echo "<td align='center' style='color: #ffffff'> ";
            echo "<b><FONT SIZE='3' COLOR='#330000'>Esta OS não pode ser cancelada pois está no extrato $extrato_rec. Para cancelar acesse o extrato correspondente.</FONT></b>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "<center><br>";
        }

    }

    if ($troca_produto_pedido_cancelado) {
        $cond_pedido_cancelado = "OR tbl_pedido.status_pedido=14";
    }

    $sql = "
        SELECT  tbl_os_troca.os_troca
        FROM    tbl_os_troca
   LEFT JOIN    tbl_pedido ON tbl_os_troca.pedido = tbl_pedido.pedido
        WHERE   tbl_os_troca.os=$os
        AND     (
                    tbl_os_troca.pedido IS NULL
                    $cond_pedido_cancelado
                )";
    if($login_fabrica == 30){
        $sql .= "
        AND     (
                    tbl_os_troca.gerar_pedido   IS TRUE
                OR  tbl_os_troca.ressarcimento  IS TRUE
                )
        ";
    }
 $res = pg_query ($con,$sql);

 if (pg_num_rows($res) and ($_GET["osacao"] == "trocar")) {
    $display_frm_troca = "block";
    $troca_efetuada = false;
}

    //HD 205476: Permitir trocar novamente produtos que tenham todos os itens da OS cancelados no pedido, sem
    //necessariamente cancelar o pedido total (status_pedido == 14)
if ($troca_produto_pedido_cancelado or $telecontrol_distrib) {
    $sql = "
    SELECT
    COUNT(*)

    FROM
    tbl_os_item
    JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
    JOIN tbl_os ON  tbl_os_produto.os=tbl_os.os
    JOIN tbl_pedido_item ON tbl_os_item.pedido_item=tbl_pedido_item.pedido_item
    JOIN tbl_pedido ON tbl_pedido_item.pedido=tbl_pedido.pedido

    WHERE
    tbl_os.os=$os
    AND tbl_os.fabrica=$login_fabrica
    AND tbl_pedido_item.qtde=tbl_pedido_item.qtde_cancelada
    ";
    $res_item_cancelado = pg_query($con, $sql);

    $sql = "
    SELECT
    COUNT(tbl_os_item.os_item)

    FROM
    tbl_os
    JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
    JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto

    WHERE
    tbl_os.os=$os
    AND tbl_os_item.pedido_item IS NOT NULL
    AND tbl_os.fabrica=$login_fabrica
    ";
    $res_total_item = pg_query($con, $sql);

    $total_cancelados = pg_result($res_item_cancelado, 0, 0);
    $total_itens_os = pg_result($res_total_item, 0, 0);

    if ($total_cancelados == $total_itens_os && $total_cancelados > 0) {
        $display_frm_troca = "block";
        $troca_efetuada = false;
    }

}

    //HD 194731: Coloquei o formulário da TROCA DE OS inteiro dentro de uma tag table para dar
    //display:none quando ele não deve estar disponível
?>

<table width="100%" border="0">
    <table style="display:<?=$display_frm_troca?>" align="center" width="700">

        <tr>
            <td>
<?
$s = $_GET['s'];
if($login_fabrica != 30){
    if(pg_num_rows($res)>0 ) {
        if (!$s){
            echo "<table width='700' align='center' cellspacing='0'  class='sucesso'>";
            echo "<tr>";
            echo "<td align='center' > ";
            echo "";
            echo "Produto já trocado anteriormente!";
            if ($ok != 's') {
                echo "<br>Preencha o formulário abaixo para trocar o produto novamente!";
            }
            echo "";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        }
    } else {
        $xsql = "SELECT os_troca    ,
                        gerar_pedido ,
                        referencia   ,
                        descricao    ,
                        ressarcimento
                FROM    tbl_os_troca
        LEFT JOIN    tbl_peca USING(peca)
                WHERE   os = $os
                AND     gerar_pedido IS NOT TRUE";
        $xres = pg_query ($con,$xsql);
        if(pg_num_rows($xres)>0) {
            $troca_efetuada      = pg_fetch_result($xres,0,os_troca);
            $troca_referencia    = pg_fetch_result($xres,0,referencia);
            $troca_descricao     = pg_fetch_result($xres,0,descricao);
            $troca_ressarcimento = pg_fetch_result($xres,0,ressarcimento);

            echo "<table width='500' align='center' border='2' cellspacing='0' bgcolor='#E8EEFF' style='border:#3300CC 1px solid;' >";
            echo "<tr>";
            if($troca_ressarcimento=='t') echo "<td align='center' ><h1>Ressarcimento Financeiro</h1> ";
            else                          echo "<td align='center' ><h1>Troca pelo produto: $troca_referencia - $troca_descricao</h1> ";
            echo "<a href='$PHP_SELF?os=$os&gerar_pedido=ok' style='font-size:12px'>Esta troca não irá gerar pedido!<br> Clique aqui para que esta troca gere pedido </A>";
            echo "</td>";
            echo "</tr>";
            echo "</table><br>";
        }
    }
} else {
    $sqlVerLaudo = "SELECT
                        count(1) AS tem_laudo
                    FROM tbl_laudo_tecnico_os
                    WHERE
                        os = $os
                        AND afirmativa = true
    ";
    $resVerLaudo = pg_query($con,$sqlVerLaudo);
    $temLaudo = pg_fetch_result($resVerLaudo,0,tem_laudo);
}

//HD 194731: Informações do produto e posto
$sql_produto = "
    SELECT  tbl_produto.produto,
            tbl_produto.referencia,
            tbl_produto.descricao,
            tbl_produto.familia,
            tbl_posto.nome
    FROM    tbl_os
    JOIN    tbl_produto ON tbl_os.produto   = tbl_produto.produto
    JOIN    tbl_posto   ON tbl_os.posto     = tbl_posto.posto
    WHERE   tbl_os.os       = $os
    AND     tbl_os.fabrica  = $login_fabrica
";

if(in_array($login_fabrica, array(142,143))){

    $sql_produto = "SELECT  tbl_produto.referencia,
                            tbl_produto.descricao,
                            tbl_os_produto.serie,
                            tbl_os_produto.produto,
                            tbl_posto.nome
                    FROM    tbl_produto
                    JOIN    tbl_os_produto  ON  tbl_produto.produto = tbl_os_produto.produto
                                            AND tbl_os_produto.os   = {$os}
                    JOIN    tbl_os          ON  tbl_os_produto.os   = tbl_os.os
                    JOIN    tbl_posto       ON  tbl_os.posto        = tbl_posto.posto";

}
$res_produto = pg_query($con, $sql_produto);

if (($troca_garantia == 't' AND !isset($troca_efetuada)) and !$troca_produto_pedido_cancelado && ($temLaudo > 0 or $telecontrol_distrib)) {
    echo "<table width='700' align='center' border='2' cellspacing='0' bgcolor='#3366FF' style='' class=''>";
    echo "<tr>";
    echo "<td align='center' style='color: #ffffff'> ";
    if ($login_fabrica != 30) {
        echo "<font color='#ffffff' size='+1'> <b> Produto já trocado </b> </font> </a> ";
    } else {
        echo "<font color='#ffffff' size='+1'> <b> Produto em processo de laudo técnico </b> </font> </a> ";
    }
    echo '<br>OS <a href="os_press.php?os=' . $os . '" target="_blank" style="color:#FFFFFF" title="Cliique para ver a OS em outra janela"><b>' . $sua_os . '</b></a>';
    echo "<br><font style='font-size: 10pt;'>
    Produto da OS: <b>[" . pg_fetch_result($res_produto, 0, referencia) . "] " . pg_fetch_result($res_produto, 0, descricao) . "</b><br>
    Posto: " . pg_fetch_result($res_produto, 0, nome) . "
    </font>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

}else{
    if ($login_fabrica <> 7 and $login_fabrica <> 50){

        if($login_fabrica==3){ //HD 72857
            $sqlRT = "SELECT * FROM tbl_programa_restrito
            WHERE fabrica  = $login_fabrica
            AND   admin    = $login_admin
            AND   programa = '$PHP_SELF'";
            $resRT = pg_query($con, $sqlRT);
            if(pg_num_rows($resRT)>0){
                $permissao="t";
            }
            $permissao = "t";
        }
        
        if( $login_fabrica <> 3 or ($login_fabrica == 3  AND $permissao=='t') ){
            if ($s){
?>

                <table class="sucesso" width="700px">
                    <tr>
                        <td>
                            Produto Trocado com Sucesso!
                            <? if ($login_fabrica == 101) echo "<br>SMS Enviado para o consumidor!"; ?>
                        </td>
                    </tr>
                </table>

                <?}
                if ($sucesso){
                    ?>
                    <table class="sucesso" width="700px">
                        <tr>
                            <td><?=$sucesso?></td>
                        </tr>
                    </table>

                    <?
		}

		if($login_fabrica == 123){
			$sql = "SELECT credenciamento FROM tbl_os JOIN tbl_posto_fabrica USING(posto,fabrica) WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica} AND credenciamento <> 'CREDENCIADO'";
			$resP = pg_query($con,$sql);

			if(pg_num_rows($resP) > 0){
				$credenciamento = pg_fetch_result($resP,0,'credenciamento');
			?>
			    <table class="msg_erro" width="700px">
				<tr>
				    <td>Posto <?=$credenciamento?></td>
				</tr>
			    </table>

			<?
			}
		}

                ?>

                <form method='post' name='frm_troca' action="<? echo $PHP_SELF . "?os=$os&osacao=trocar"; ?>" enctype='multipart/form-data'>
                    <input type='hidden' name='os' value='<? echo $os ?>'>
                    <!-- colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca -->
                    <input type='hidden' name='orient_sac' value=''>
                    <input type="hidden" name="auditoria_obrigatoria" value="<?= $_REQUEST['auditoria_obrigatoria'] ?>" />
                    <tr>
                        <td align="center">
                            <table width='700px' align='center' border='0' cellspacing='0'  class='formulario'>
                                <tr class='titulo_tabela'>
                                    <td align='center' colspan="100%" >
                                        Trocar Produto em Garantia <?=$total1?>
                                    </td>
                                </tr>

                                <tr class="subtitulo">
                                    <td colspan="100%">
                                        Informações do Produto para Troca
                                    </td>
                                </tr>

                                <tr><td>&nbsp;</td></tr>

                                <tr>
                                    <td colspan="100%" align="center">

                                        <table align="center" width="600px" border="0">
                                            <tr>
                                                <td width="14%" align='left'><?php
                                echo "Produto da OS: ";
                            echo "</td>";

                            echo "<td align='left'>";
                                if($login_fabrica == 138){
                                    $content = getSelectContentProdutosOs($_REQUEST['os']);
                                    $html = HtmlHelper::inlineBuild('select[name=produto_troca].frm',array('value'=>$content[0]['value']),$content);
                                    $html->render();
                                }
                                else{
                                    $produto_os_troca = pg_result($res_produto,0, 'produto');
                                    echo "<input type='hidden' name='produto_os_troca_atual' id='produto_os_troca_atual' value='$produto_os_troca'>";
                                    echo "<b>[" . @pg_fetch_result($res_produto, 0, 'referencia') . "] " . @pg_fetch_result($res_produto, 0, 'descricao') . "</b>";
                                }
                            echo "</td>";
                        echo "</tr>";

                                                    echo "<tr>";
                                                    echo "<td align='left'>";
                                                    echo "Posto: ";
                                                    echo "</td>";

                                                    echo "<td align='left'>";
                                                    echo "<b>";
                                                    echo @pg_fetch_result($res_produto, 0, 'nome');
                                                    echo "</b>";
                                                    if ($verifica_ressarcimento_troca) {
                                                        $sqlressarcimento = "SELECT hd_chamado,ressarcimento from tbl_hd_chamado_troca join tbl_hd_chamado_extra using(hd_chamado) where os = $os";
                                                        $resressarcimento = pg_exec($con,$sqlressarcimento);

                                                        if (pg_num_rows($resressarcimento)>0) {
                                                            $hd_chamado_troca    = pg_result($resressarcimento,0,0);
                                                            $ressarcimento_troca = pg_result($resressarcimento,0,1);
                                                            if ($ressarcimento_troca == 't') {
                                                                echo "<br>Foi Aberto o Chamado $hd_chamado_troca solicitando o ressarcimento deste produto, para efetivar o ressarcimento complete os campos abaixo";
                                                            }
                                                        }

                                                    } ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td align='left'>
                                                    N° OS:
                                                </td>
                                                <td align='left'>
                                                    <b><a href="os_press.php?os=<? echo $os; ?>" target="_blank" title="Cliique para ver a OS em outra janela" style='color:RoyalBlue; text-decoration:underline; '><?=$sua_os?></a></b>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td align='left'>
                                                    Responsável:
                                                </td>
                                                <td align='left'>
                                                   <b><?=$login_login?></b>
                                               </td>
                                           </tr>
<?
                                        if($login_fabrica == 30 && strlen($motivo_troca) > 0){
?>
                                            <tr>
                                                <td align='left'>
                                                    Sugestão troca posto:
                                                </td>
                                                <td align='left'>
                                                   <b><?=$motivo_troca?></b>
                                               </td>
                                           </tr>

<?
                                        }
?>
                                       </table>

                                       <br />
                                       <table align="center" width="600" border="0">

                                        <?
                                        if (in_array($login_fabrica,array(3,6,10,11,14,15,19,24,25,30,35,40,45,51,59,66,72,80)) or $login_fabrica > 80) {
                                            if (in_array($login_fabrica, array(3,81,155, 114))) {

                                                if(!in_array($login_fabrica,array(3))) {
                                                    if (count($lista_produtos) || $_POST['radio_qtde_produtos'] == 'muitos'){
                                                        $display_multi_produto = "";
                                                        $display_um            = "";
                                                        $display_multi         = " CHECKED ";
                                                    }else{
                                                        $display_multi_produto = "display:none";
                                                        $display_um            = " CHECKED ";
                                                        $display_multi         = "";
                                                    }

                                                    ?>
                                                    <tr>
                                                        <td align='left'>
                                                            Um produto
                                                            <input type="radio" name="radio_qtde_produtos" id="radio_qtde_produtos1" value='um'  <?=$display_um?>  onClick='javascript:toogleProd(this)'>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                            Vários Produtos
                                                            <input type="radio" name="radio_qtde_produtos" id="radio_qtde_produtos2" value='muitos' <?=$display_multi?> onClick='javascript:toogleProd(this)'>
                                                        </td>
                                                    </tr>
                                                    <?
                                                }
                                                ?>
                                                <tr>
                                                    <td align='left' width="40%"><b>Marca do Produto</b></td>
                                                    <td width="20%"></td>
                                                    <? if(in_array($login_fabrica, array(81,155))) { ?>
                                                    <td width="40%">Quantidade</td>
                                                    <? } ?>
                                                </tr>
                                                <tr>
                                                    <td align='left'>
                                                        <?
                                                        $sql = "SELECT  tbl_marca.nome, tbl_marca.marca
                                                        FROM      tbl_marca
                                                        WHERE     tbl_marca.fabrica = $login_fabrica
                                                        ORDER BY tbl_marca.nome;";

                                                        $res = pg_query ($con,$sql);
                                                        if (pg_num_rows($res) > 0) {
                                                            echo "<select class='frm' style='width:200px' name='marca_troca' id='marca_troca' onChange='buscaFamilia(this.value);'>\n";
                                                            echo "<option value=''>ESCOLHA</option>\n";

                                                            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                                                $aux_marca     = trim(pg_fetch_result($res,$x,marca));
                                                                $aux_descricao  = trim(pg_fetch_result($res,$x,nome));
                                                                if ($marca_troca == $aux_marca) {
                                                                    $selected = "selected";
                                                                }
                                                                else {
                                                                    $selected = "";
                                                                }
                                                                echo "<option $selected value='$aux_marca'>$aux_descricao</option>\n";
                                                            }

                                                            echo "</select>";
                                                        }
                                                        else {
                                                            $aux_marca = '001';
                                                        }
                                                        echo "</td>";
                                                    }else{
                                                        echo "<tr>";
                                        #echo "<td align='left'><b>Marca do Produto</td>";
                                                        echo "<td align='left'>";
                                                        $sql = "SELECT  tbl_marca.*
                                                        FROM      tbl_os
                                                        JOIN      tbl_produto USING(produto)
                                                        JOIN      tbl_marca ON tbl_produto.marca = tbl_marca.marca
                                                        WHERE     tbl_marca.fabrica = $login_fabrica
                                                        AND       tbl_os.os = $os
                                                        ORDER BY tbl_marca.nome;";

                                                        $res = pg_query ($con,$sql);
                                                        if (pg_num_rows($res) > 0) {
                                                            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                                                $aux_marca     = trim(pg_fetch_result($res,$x,marca));
                                                                $aux_descricao = trim(pg_fetch_result($res,$x,nome));

                                                                echo "<input type='hidden' name='marca' id='marca' value='$aux_marca'> <b><font color='#990000'>$aux_descricao</font></b><br>";
                                                            }
                                                        }else $aux_marca = '001';
                                                        echo "</td>";
                                                    }
                                                    ?>

                                                    <td width="20%"></td>

                                                    <? if(in_array($login_fabrica, array(81,155))) { ?>
                                                    <td width="40%"><input type="text" name="quantidade_item" id="quantidade_item" min="1" class="frm" style="width: 80px" value="<?=$quantidade_item?>" /></td>
                                                    <? } ?>

                                                    <tr>
                                                        <td width='40%' align='left'>Família do Produto</td>
                                                        <td width="20%"></td>
                                                        <td width='40%' align='left'><?=($login_fabrica != 30) ? "Número de Registro" : ""?></td>

                                                    </tr>

                                                    <tr>
                                                        <td width='40%' align="left">
                                                            <?
                                                            if (in_array($login_fabrica, array(3,81, 155,114)) && (strlen($marca_troca))) {
                                                                $sql = "
                                                                SELECT
                                                                DISTINCT
                                                                tbl_familia.familia,
                                                                tbl_familia.descricao

                                                                FROM
                                                                tbl_familia
                                                                JOIN tbl_produto USING(familia)

                                                                WHERE
                                                                tbl_familia.fabrica = $login_fabrica
                                                                AND tbl_produto.marca='$marca_troca'

                                                                ORDER BY tbl_familia.descricao
                                                                ";
                                                            }else{
                                                                $sql = "SELECT  familia,
                                                                                descricao
                                                                        FROM    tbl_familia
                                                                        WHERE   tbl_familia.fabrica = $login_fabrica
                                                                        AND     tbl_familia.ativo IS TRUE
                                                                  ORDER BY      tbl_familia.descricao;";
                                                            }
                                                            $res = pg_query ($con, $sql);
                                                            if (pg_num_rows($res) > 0) {
                                                                if($login_fabrica == 30 && strlen($familia_troca) == 0){
                                                                    $familia_troca = pg_fetch_result($res_produto, 0, familia);
                                                                }
                                                                ?>
                                                                <select class='frm' style='width:100%' name='familia_troca' id='familia_troca' onChange='listaProduto(this.value,<?="$aux_marca"?>);valida_campo_ant();'>
                                                                    <option value=''>ESCOLHA</option>

                                                                    <?

                                                                    for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                                                        $aux_familia   = trim(pg_fetch_result($res,$x,familia));
                                                                        $aux_descricao = trim(pg_fetch_result($res,$x,descricao));

                                                                        if ($familia_troca == $aux_familia) {
                                                                            $selected = "selected";
                                                                        }else {
                                                                            $selected = "";
                                                                        }

                                                                        echo "<option $selected value='$aux_familia'>$aux_descricao</option>\n";
                                                                    }
                                                                    echo "</select>\n";
                                                                }
                                                                ?>
                                                            </td>
                                                            <td width="20%"></td>

                                                            <?
                                                            if($login_fabrica != 30){
                                                            echo "<td align='left' width='40%'>";
                                                            echo "<input type='text' name='ri' value='" . $_POST[ri] . "' style='width:100%;' maxlength='10' class='frm'>";
                                                            echo "</td>";
                                                            }
                                                            ?>

                                                        </tr>

                                                        <?
                                                        if ($login_fabrica == 3){?>
                                                        <tr>

                                                            <td nowrap width='40%' align='left'>Trocar pelo produto/Ressarc.:</td>

                                                        </tr>



                                                        <?
                                                    }else{
                                                        ?>

                                                        <tr>
                                                            <td nowrap width='40%' align='left'><?=(
                                                                !in_array($login_fabrica, array(30, 101))) ? "Trocar pelo produto/Ressarc.:" : "Trocar pelo produto" ;?></td>
                                                            <td width="20%"></td>

                                                            <td nowrap width='40%' align='left'>
                                                                <?
                                                                    if ($login_fabrica == 30) {
                                                                        echo "Causa da Troca/Restituição";
                                                                    } else if ($login_fabrica == 101) {
                                                                        echo "Causa da Troca";
                                                                    } else {
                                                                        echo "Causa da Troca/Ressarcimento";
                                                                    };
                                                                ?>
                                                            </td>
                                                        </tr>

                                                        <?
                                                    }
                                                    ?>



                                                    <?if ($login_fabrica == 3){?>
                                                    <tr>

                                                        <td  colspan="3" align="left">

                                                            <?
                                                            echo "<select name='troca_garantia_produto' id='troca_garantia_produto' style='width:100%' class='frm' ";
                                                            if ($verifica_ressarcimento_troca) {
                                                                echo "onchange='javascript: if (this.value == \"-1\") {document.getElementById(\"dados_ressarcimento\").style.display = \"block\"} else {document.getElementById(\"dados_ressarcimento\").style.display = \"none\"}'";
                                                            }
                                                            echo ">";
                                                            echo "<option id='opcoes' value=''></option>";
                                                            if (strlen($familia_troca)) {
                                                                if(strlen($marca_troca) && in_array($login_fabrica, array(3,81, 155,114))) {
                                                                    $sql_marca = " AND tbl_produto.marca = $marca_troca ";
                                                                }

                                                                $sql ="
                                                                SELECT
                                                                tbl_produto.referencia,
                                                                tbl_produto.descricao,
                                                                tbl_produto.produto

                                                                FROM
                                                                tbl_produto
                                                                JOIN tbl_familia USING(familia)

                                                                WHERE
                                                                tbl_produto.familia = $familia_troca
                                                                AND tbl_familia.fabrica = $login_fabrica
                                                                AND tbl_produto.lista_troca IS TRUE
                                                                $sql_marca
                                                                ORDER BY tbl_produto.referencia
                                                                ";
                                                                $res = pg_query($con, $sql);

                                                                switch($troca_garantia_produto) {
                                                                    case -1:
                                                                    $selected_ressarcimento = "selected";
                                                                    break;

                                                                    case -2:
                                                                    $selected_troca_revenda= "selected";
                                                                    break;
                                                                }

                                                                echo "<option $selected_ressarcimento value='-1'>RESSARCIMENTO FINANCEIRO</option>";
                                        //HD 211825: Opção de trocar através da revenda um produto
                                                                if ($verifica_ressarcimento_troca) {
                                                                    echo "<option $selected_troca_revenda value='-2'>AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA</option>";
                                                                }

                                                                for($p = 0; $p < pg_num_rows($res); $p++) {
                                                                    $aux_referencia = pg_fetch_result($res, $p, referencia);
                                                                    $aux_descricao = pg_fetch_result($res, $p, descricao);
                                                                    $aux_produto = pg_fetch_result($res, $p, produto);

                                                                    if ($troca_garantia_produto == $aux_produto) {
                                                                        $selected = "selected";
                                                                    }
                                                                    else {
                                                                        $selected = "";
                                                                    }

                                                                    echo "<option value='$aux_produto' $selected>$aux_referencia - $aux_descricao</option>";
                                                                }
                                                            }
                                                            ?>

                                                        </select>
                                                    </td>

                                                </tr>

                                                <tr>
                                                    <td nowrap width='40%' align='left'>Causa da Troca/Ressarcimento</td>

                                                </tr>

                                                <tr>

                                                    <td width='40%' align="left">

                                                        <?
                                                        $sql = "SELECT  tbl_causa_troca.causa_troca,
                                                        tbl_causa_troca.codigo     ,
                                                        tbl_causa_troca.descricao
                                                        FROM tbl_causa_troca
                                                        WHERE tbl_causa_troca.fabrica = $login_fabrica
                                                        AND tbl_causa_troca.ativo     IS TRUE
                                                        ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
                                                        $resTroca = pg_query ($con,$sql);
                                                        echo "<select name='causa_troca' id='causa_troca' size='1' class='frm' style='width:100%'>";
                                                        echo "<option value='' ></option>";
                                                        for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
                                                            $aux_causa_troca = pg_fetch_result ($resTroca,$i,causa_troca);

                                                            if ($causa_troca == $aux_causa_troca) {
                                                                $selected = "selected";
                                                            }
                                                            else {
                                                                $selected = "";
                                                            }

                                                            echo "<option $selected value='" . $aux_causa_troca . "'";
                                                            echo ">" . pg_fetch_result ($resTroca,$i,codigo) . " - " . pg_fetch_result ($resTroca,$i,descricao) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </td>

                                            </tr>


                                            <?}else{?>
                                            <tr>

                                                <td width='40%'  align="left">

                                                    <?
                                                    echo "<select name='troca_garantia_produto' id='troca_garantia_produto'  class='frm' style='width:100%' ";
                                                    if ($verifica_ressarcimento_troca) {
                                                        echo "onchange='javascript: if (this.value == \"-1\") {document.getElementById(\"dados_ressarcimento\").style.display = \"block\"} else {document.getElementById(\"dados_ressarcimento\").style.display = \"none\"}'";
                                                    }
                                                    echo ">";
                                                    echo "<option id='opcoes' value=''></option>";
                                                    if (strlen($familia_troca)) {
                                                        if(strlen($marca_troca) && in_array($login_fabrica, array(3,30,81, 114, 155))) {
                                                            $sql_marca = " AND tbl_produto.marca = $marca_troca ";
                                                        }
                                                        if($login_fabrica == 30 && strlen($troca_garantia_produto)==0){
                                                            $troca_garantia_produto = $produto_os_troca;
                                                            $produto_os = $produto_os_troca;
                                                        }

                                                        $sql ="
                                                        SELECT
                                                        tbl_produto.referencia,
                                                        tbl_produto.descricao,
                                                        tbl_produto.produto

                                                        FROM
                                                        tbl_produto
                                                        JOIN tbl_familia USING(familia)

                                                        WHERE
                                                        tbl_produto.familia = $familia_troca
                                                        AND tbl_familia.fabrica = $login_fabrica
                                                        AND tbl_produto.lista_troca IS TRUE
                                                        $sql_marca
                                                        ORDER BY tbl_produto.referencia
                                                        ";
                                                        $res = pg_query($con, $sql);

                                                        switch($troca_garantia_produto) {
                                                            case -1:
                                                            $selected_ressarcimento = "selected";
                                                            break;

                                                            case -2:
                                                            $selected_troca_revenda= "selected";
                                                            break;
                                                        }

                                                        if (!in_array($login_fabrica, array(30, 101))) {
                                                            echo "<option $selected_ressarcimento value='-1'>RESSARCIMENTO FINANCEIRO</option>";
                                                        }
                                        //HD 211825: Opção de trocar através da revenda um produto
                                                        if ($verifica_ressarcimento_troca) {
                                                            echo "<option $selected_troca_revenda value='-2'>AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA</option>";
                                                        }

                                                        for($p = 0; $p < pg_num_rows($res); $p++) {
                                                            $aux_referencia = pg_fetch_result($res, $p, referencia);
                                                            $aux_descricao = pg_fetch_result($res, $p, descricao);
                                                            $aux_produto = pg_fetch_result($res, $p, produto);

                                                            if ($troca_garantia_produto == $aux_produto) {
                                                                $selected = "selected";
                                                            }
                                                            else {
                                                                $selected = "";
                                                            }

                                                            echo "<option value='$aux_produto' $selected>$aux_referencia - $aux_descricao</option>";
                                                        }
                                                    }
                                                    ?>

                                                </select>
                                            </td>
                                            <td width="20%"></td>

                                            <td width='40%' align="left">

                                                <?
												$order = ($login_fabrica == 35) ? " tbl_causa_troca.descricao " : " tbl_causa_troca.codigo ";
                                                $sql = "SELECT  tbl_causa_troca.causa_troca,
                                                tbl_causa_troca.codigo     ,
                                                tbl_causa_troca.descricao
                                                FROM tbl_causa_troca
                                                WHERE tbl_causa_troca.fabrica = $login_fabrica
                                                AND tbl_causa_troca.ativo     IS TRUE
                                                ORDER BY $order ";
                                                $resTroca = pg_query ($con,$sql);
                                                echo "<select name='causa_troca' id='causa_troca' size='1' class='frm' style='width:100%'>";
                                                echo "<option value='' ></option>";
                                                for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
                                                    $aux_causa_troca = pg_fetch_result ($resTroca,$i,causa_troca);

                                                    if ($causa_troca == $aux_causa_troca) {
                                                        $selected = "selected";
                                                    }
                                                    else {
                                                        $selected = "";
                                                    }

                                                    echo "<option $selected value='" . $aux_causa_troca . "'";
                                                    echo "title='". pg_fetch_result ($resTroca,$i,descricao) . "'";
													echo ">";
													echo ($login_fabrica == 35) ? pg_fetch_result ($resTroca,$i,descricao) 	:  pg_fetch_result ($resTroca,$i,codigo) . " - " . pg_fetch_result ($resTroca,$i,descricao) ;
													echo "</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>


                                    </tr>
                                    <?}?>


<?php
                                    if ($login_fabrica <> 6) {
                                        if ($login_fabrica==51) { #HD 390687
?>
                                    <tr>
                                        <td colspan='2' align='left'>N° Coleta/Postagem</td>
                                        <td align='left'>Data Solicitação</td>
                                    </tr>
                                    <tr>
                                        <td colspan='2' align='left'><input type="text" name="coleta_postagem" value="<? echo $coleta_postagem; ?>" style="width:65%;" maxlength="20" class='frm'></td>
                                        <td align='left'><input type="text" name="data_postagem" id="data_postagem" value="<? echo $data_postagem; ?>" style="width:39%;" size="11" class='frm'></td>
                                    </tr>
<?php
                                        }
?>
                                    <tr>
                                        <?php if ($login_fabrica == 30) { ?>
                                            <tr>
                                                <td>
                                                    <font size="1" face="Geneva, Arial, Helvetica, san-serif">
                                                        <span rel="classificacao_atendimento">
                                                            Classificação do Atendimento
                                                        </span>
                                                    </font>
                                                    <br>
                                                    <select id="classificacao_atendimento" name="classificacao_atendimento" size="1" class="frm">
                                                        <option value=""></option>
                                                        <?php
                                                            $aux_sql = "SELECT hd_classificacao, descricao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND hd_classificacao IN (50, 51, 52) ORDER BY descricao";
                                                            $aux_res = pg_query($con, $aux_sql);
                                                            $aux_row = pg_num_rows($aux_res);

                                                            for ($wx = 0; $wx < $aux_row; $wx++) { 
                                                                $hd_classificacao = pg_fetch_result($aux_res, $wx, 'hd_classificacao');
                                                                $hd_descricao     = pg_fetch_result($aux_res, $wx, 'descricao');

                                                                if ($_POST["classificacao_atendimento"] == $hd_classificacao or $xhd_classificacao == $hd_classificacao) {
                                                                    $selected = "SELECTED";
                                                                } else {
                                                                    $selected = "";
                                                                }

                                                                ?> <option <?=$selected;?> value="<?=$hd_classificacao;?>"><?=$hd_descricao;?></option> <?
                                                            }
                                                        ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php } else { ?>
                                            <td>&nbsp;</td>
                                        <?php } ?>
                                    </tr>

                                    <?

                                    $varios_produtos = $_POST['PickList'];

                                    if (count($varios_produtos)>0){

                                        $lista_produtos = array();

                                        for($k = 0; $k < count($varios_produtos); $k++) {

                                            $varios_produtos[$k] = str_replace("'", '"', $varios_produtos[$k]);
                                            $varios_produtos[$k] = json_decode(utf8_encode($varios_produtos[$k]));

                                            $sqlP = "SELECT tbl_peca.peca,tbl_produto.referencia,tbl_produto.descricao
                                            FROM tbl_peca join tbl_produto ON tbl_peca.referencia = tbl_produto.referencia AND tbl_produto.fabrica_i = $login_fabrica
                                            WHERE tbl_peca.fabrica = $login_fabrica
                                            AND tbl_produto.produto = {$varios_produtos[$k]->value}
                                            AND tbl_peca.produto_acabado IS TRUE";
                                            $resP = pg_query($con,$sqlP);

                                            if(pg_num_rows($resP) > 0){

                                                $pecaP = pg_result($resP,0,'peca');
                                                $mult_referencia = pg_result($resP,0,'referencia');
                                                $mult_descricao = pg_result($resP,0,'descricao');

                                                array_push($lista_produtos,array($varios_produtos[$k],$mult_referencia,$mult_descricao));

                                                $sql_peca2 = "SELECT tbl_tabela_item.preco
                                                FROM tbl_tabela_item
                                                JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
                                                WHERE tbl_tabela_item.peca  = $pecaP
                                                AND   tbl_tabela.fabrica = $login_fabrica";
                                                $res2 = pg_query($con,$sql_peca2);
                                            }
                                        }

                                    }

                                    if($fabrica_os_multi_produto){ #HD 868635 ?>
                                    <tr>
                                        <td colspan = '3'>
                                            <div id='id_multi' style='<?echo $display_multi_produto;?>'>
                                                <input type='button' name='adicionar_produto' id='adicionar_produto' value='Adicionar' class='frm' onclick='addIt();'>
                                                <b style='font-weight:normal;color:gray;font-size:10px'>(Selecione o produto e clique em 'Adicionar')</b>
                                                <br>
                                                <select multiple size='6' style="width:100%" id="PickList" name="PickList[]" class='frm'>

                                                    <?

                                                    if (count($lista_produtos)){
                                                        foreach($lista_produtos as $produto) {
                                                            echo "<option value=\"{'value':{$produto[0]->value},'quantidade':{$produto[0]->quantidade},'texto':'".utf8_decode($produto[0]->texto)."'}\">" . utf8_decode($produto[0]->texto) . "</option>";
                                                        }
                                                    }
                                                    ?>

                                                </select> <br>
                                                <input type="button" value="Remover" ONCLICK="delIt();" class='frm'></input>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <? } ?>
                                    <tr>
                                        <td colspan="3" align="left">
                                            <?=($login_fabrica != 30) ? "Observação para nota fiscal" : "Informações Adicionais/justificativa";?>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td colspan="3" align="left">

                                            <textarea style='width:100%' name='observacao_pedido' rows='5' class='frm'><? echo (!empty($obs_troca)) ? $obs_troca : $_POST["observacao_pedido"] ; ?></textarea>
                                            <br />

                                        </td>
                                    </tr>

                                    <?php } else {?>

                                    <tr>
                                        <td>Causa Raiz</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select style="width:100%" class='frm' name="causa_raiz" id="causa_raiz">
                                                <option value=""></option>
                                            </select>

                                        </td>
                                    </tr>

                                    <?php }?>
                                </table>
                                <br />
                            </td>
                        </tr>
<?
if($login_fabrica != 30){
?>
                        <tr class="subtitulo">
                            <td colspan="100%">
                                Informações Adicionais
                            </td>
                        </tr>
<?
}
?>
                        <tr>
                            <td colspan="100%">
                                <?
                                if($login_fabrica != 30 && $login_fabrica != 101){
                                    $sql = "SELECT tbl_os_item.os_item,
                                    tbl_peca.referencia,
                                    tbl_peca.descricao,
                                    tbl_os_item.qtde
                                    FROM tbl_os_item
                                    JOIN tbl_os_produto USING (os_produto)
                                    JOIN tbl_peca       USING (peca)
                                    WHERE os = $os";
                                    $resTroca = pg_query ($con,$sql);
                                    $qtde_itens = pg_num_rows($resTroca);
                                    echo "<input type='hidden' name='qtde_itens' value='$qtde_itens'>";
                                    if(pg_num_rows($resTroca)>0) {

                                        echo "<br><table border='0' class='tabela' cellspacing='1' cellpadding='1' width='600' align='center'>";
                                        echo "<tr class='titulo_coluna'>";
                                        echo "<td></td>";
                                        echo "<td align='left'> <b>Referência</td>";
                                        echo "<td align='left'> <b>Peça</td>";
                                        echo "<td align='right'> <b>Qtde</td>";
                                        echo "</tr>";
                                        for ($i = 0 ; $i < pg_num_rows($resTroca) ; $i++) {
                                            $os_item         = pg_fetch_result ($resTroca,$i,os_item) ;
                                            $peca_referencia = pg_fetch_result ($resTroca,$i,referencia) ;
                                            $peca_descricao  = pg_fetch_result ($resTroca,$i,descricao) ;
                                            $peca_qtde       = pg_fetch_result ($resTroca,$i,qtde)      ;
                                            if($cor == "#F1F4FA") $cor = '#F7F5F0';
                                            else                  $cor = '#F1F4FA';

                                            if($_POST["os_item_$i"] == $os_item) {
                                                $checked = "checked";
                                            }
                                            else {
                                                $checked = "";
                                            }

                                            echo "<tr style='background-color:$cor'>";
                                            echo "<td align='left'> <input type='checkbox' $checked value='$os_item' name='os_item_$i'></td>";
                                            echo "<td align='left'> $peca_referencia</td>";
                                            echo "<td align='left'> $peca_descricao</td>";
                                            echo "<td align='right'> $peca_qtde</td>";
                                            echo "</tr>";
                                        }

                                        echo "<tr class='titulo_coluna'>";
                                        echo "<td align='left' colspan='4'>&nbsp;<img src='imagens/seta_checkbox.gif'> Se o motivo da troca foi peça, selecione a peça que originou a troca</td>";
                                        echo "</tr>";
                                        echo "<tr class='texto_avulso'>";
                                        echo "<td align='center' colspan='4'><u> Em caso de troca TODAS as peças acima serão canceladas</td>";
                                        echo "</tr>";

                                        echo "</table><br>";
                                    }
                                }else{
                                    ?>
                                    <table border='0' class='tabela' cellspacing='1' cellpadding='1' align='center'>
                                        <tr class="subtitulo">
                                            <td colspan="100%">
                                                Em caso de troca ou restituição aprovada, TODAS as peças pendentes da OS serão canceladas
                                            </td>
                                        </tr>
                                    </table>
                                    <?
                                }
                                ?>
                                <br>
                                <table align='center' border='0' cellspacing='0' width='600'>
<?
if($login_fabrica != 30){
?>
                                    <tr>
                                        <td width="50px">&nbsp;</td>
                                        <td align='left' rowspan="2">
                                            <fieldset style="width:150px">
                                                <legend>Setor Responsável</legend>
                                                <?php
                                                switch($_POST["setor"]) {
                                                    case "Revenda":
                                                    $revenda_checked = "checked";
                                                    break;

                                                    case "Carteira":
                                                    $carteira_checked = "checked";
                                                    break;

                                                    case "SAC":
                                                    $sac_checked = "checked";
                                                    break;

                                                    case "Procon":
                                                    $procon_checked = "checked";
                                                    break;

                                                    case "SAP":
                                                    $sap_checked = "checked";
                                                    break;

                                                    case "Suporte Técnico":
                                                    $suporte_checked = "checked";
                                                    break;

                                                    case "Supervisão":
                                                    $supervisao_checked = "checked";
                                                    break;

                                                    case "S.P.V":
                                                    $spv_checked = "checked";
                                                    break;

                                                    case "Jurídico":
                                                    $juridico_checked = "checked";
                                                    break;

                                                    case "02":
                                                    $ilheus_checked = "checked";
                                                    break;

                                                    case "04":
                                                    $manaus_checked = "checked";
                                                    break;

                                                    case "08":
                                                    $extrema_checked = "checked";
                                                    break;

                                                    case "12":
                                                    $services_checked = "checked";
                                                    break;

                                                    case "18":
                                                    $extremafl_checked = "checked";
                                                    break;

                                                    case "19":
                                                    $decodes_checked = "checked";
                                                    break;
                                                }

                                                if($login_fabrica == 11 or $login_fabrica == 172){
                                                    echo "<input type='radio' name='setor' id='setor_spv'               $spv_checked        value='S.P.V'           > <label for='setor_spv' style='cursor:pointer'> S.P.V </label> <br>";
                                                    echo "<input type='radio' name='setor' id='setor_supervisao'        $supervisao_checked value='Supervisão'      > <label for='setor_supervisao' style='cursor:pointer'>Supervisão</label> <br>";
                                                    echo "<input type='radio' name='setor' id='setor_sac'               $sac_checked        value='SAC'             > <label for='setor_sac' style='cursor:pointer'>SAC</label> <br>";
                                                    echo "<input type='radio' name='setor' id='setor_juridico'          $juridico_checked   value='Jurídico'        > <label for='setor_juridico' style='cursor:pointer'>Jurídico</label><br>";
                                                    echo "<input type='radio' name='setor' id='setor_sap'               $sap_checked        value='SAP'             > <label for='setor_sap' style='cursor:pointer'>SAP</label> <br>";
                                                    echo "<input type='radio' name='setor' id='setor_suporte_tecnico'   $suporte_checked    value='Suporte Técnico' > <label for='setor_suporte_tecnico' style='cursor:pointer'>Suporte Técnico</label> <br>";
                                                }else if($login_fabrica == 141){
                                                    echo "<input type='radio' name='setor' id='ilheus'    $ilheus_checked    value='02'> <label for='ilheus'    style='cursor:pointer'> ILHEUS     </label> <br>";
                                                    echo "<input type='radio' name='setor' id='manaus'    $manaus_checked    value='04'> <label for='manaus'    style='cursor:pointer'> MANAUS     </label> <br>";
                                                    echo "<input type='radio' name='setor' id='extrema'   $extrema_checked   value='08'> <label for='extrema'   style='cursor:pointer'> EXTREMA    </label> <br>";
                                                    echo "<input type='radio' name='setor' id='services'  $services_checked  value='12'> <label for='services'  style='cursor:pointer'> SERVICES   </label> <br>";
                                                    echo "<input type='radio' name='setor' id='extremafl' $extremafl_checked value='18'> <label for='extremafl' style='cursor:pointer'> EXTREMA FL </label> <br>";
                                                    echo "<input type='radio' name='setor' id='decoder'   $decoder_checked   value='19'> <label for='decoder'   style='cursor:pointer'> DECODER    </label> <br>";
                                                }else{
                                                    echo "<input type='radio' name='setor' id='setor_revenda' $revenda_checked value='Revenda'> <label for='setor_revenda' style='cursor:pointer'> Revenda </label> <br>";
                                                    if ($login_fabrica<>6){
                                                        echo "<input type='radio' name='setor' id='setor_carteira' $carteira_checked value='Carteira'> <label for='setor_carteira' style='cursor:pointer'>Carteira</label> <br>";
                                                    }
                                                    echo "<input type='radio' name='setor' id='setor_sac' $sac_checked value='SAC'> <label for='setor_sac' style='cursor:pointer'>SAC</label> <br>";
                                                    echo "<input type='radio' name='setor' id='setor_procon'$procon_checked value='Procon'> <label for='setor_procon' style='cursor:pointer'>Procon</label><br>";
                                                    echo "<input type='radio' name='setor' id='setor_sap' $sap_checked value='SAP'> <label for='setor_sap' style='cursor:pointer'>SAP</label> <br>";
                                                    echo "<input type='radio' name='setor' id='setor_sap' $suporte_checked value='Suporte Técnico'> <label for='setor_sap' style='cursor:pointer'>Suporte Técnico</label> <br>";
                                                }
                                                ?>
                                            </fieldset>
                                        </td>

                                        <td width="50px">&nbsp;</td>

                                        <td>
<?
                                            if ($login_fabrica <> 6){
?>

                                            <fieldset style="width:150px">
                                                <?php
                                                if(!in_array($login_fabrica, array(6, 51, 81, 155, 114))){
                                                    echo "<legend>Situação do Atendimento</legend>";
                                                }else{
                                                    echo "<legend>Efetuar Troca Por</legend>";
                                                }

                                                if(!$telecontrol_distrib AND !in_array($login_fabrica,array(124,126,128))){
                                                    switch($_POST["situacao_atendimento"]) {
                                                        case "0":
                                                        $checked_0 = "checked";
                                                        break;

                                                        case "50":
                                                        $checked_50 = "checked";
                                                        break;

                                                        case "100":
                                                        $checked_100 = "checked";
                                                        break;
                                                    }

                                                    echo "<input type='radio' name='situacao_atendimento' id='situacao_produto_garantia' $checked_0 value='0'> <label for='situacao_produto_garantia' style='cursor:pointer'>Produto em garantia</label> <br>";
                                                    echo "<input type='radio' name='situacao_atendimento' id='situacao_faturado_50' $checked_50 value='50'> <label for='situacao_faturado_50' style='cursor:pointer'>Faturado 50%</label> <br>";
                                                    echo "<input type='radio' name='situacao_atendimento' id='situacao_faturado_100' $checked_100 value='100'> <label for='situacao_faturado_100' style='cursor:pointer'>Faturado 100%</label> <br>";
                                                }else{
                                                    switch($_POST["fabrica_distribuidor"]) {
                                                        case "fabrica":
                                                        $fabrica_checked = "checked";
                                                        break;

                                                        case "distribuidor":
                                                        $distribuidor_checked = "checked";
                                                        break;
                                                    }

                                                    echo "<input type='hidden' name='situacao_atendimento' value='0'>";
                                                    echo "<input type='radio' name='fabrica_distribuidor' id='fabrica_fabrica' $fabrica_checked value='fabrica'> <label for='fabrica_fabrica' style='cursor:pointer'>Fábrica</label><br>";
                                                    echo "<input type='radio' name='fabrica_distribuidor' id='fabrica_distrib' $distribuidor_checked value='distribuidor'> <label for='fabrica_distrib' style='cursor:pointer'> Distribuidor </label>  <br>";
                                                }
                                                ?>
                                            </fieldset>
                                            <?
                                        }
                                        ?>
                                    </td>
                                    <td width="50px">&nbsp;</td>
                                </tr>
<?
}
                                    if ($login_fabrica == 101) {
?>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td><input type="checkbox" name="solicita_lgr" value="t" <?=(isset($_POST['solicita_lgr']) && $_POST['solicita_lgr'] == 't') ? 'checked' : ''?>>Solicitar LGR (Logística Reversa)</td>
                                        </tr>
<?php
                                    }
?>

                                <tr>
                                    <td></td>
                                    <td></td>
                                    <?php if ($login_fabrica == 101) { echo "<td></td>"; } ?>
                                    <td>
<?

                                //HD 79774 - Paulo César 10/03/2009 esta como disable pois oi travado geração de pedido automatica para  fabrica=3
                                //HD 83652 - IGOR - Retirar regra de gerar pedido sempre para Britania
                                        if (!in_array($login_fabrica,array(14,15,30))) {
                                            if ($_POST["gerar_pedido"]) {
                                                $checked = "checked";
                                            }
                                            echo "<input type='checkbox' $display_gerar_pedido $checked name='gerar_pedido' id='gerar_pedido' value='t'> <label for='gerar_pedido' $display_gerar_pedido> Gerar pedido </label> ";
                                        }else if($login_fabrica == 30){
?>
                                            <input type="hidden" name="gerar_pedido" id="gerar_pedido" value="f" />
<?
                                        }
?>
                                    </td>


<?php
                                    echo "</tr>";
                                    if (in_array($login_fabrica, array(3,81,101, 155,114))) {
?>


                                        <tr>

                                            <td width="50px">&nbsp;</td>

                                            <td>
                                                <fieldset style="width:150px">
                                                    <legend>Destino</legend>
                                                    <?


                                                    switch($_POST["envio_consumidor"]) {
                                                        case "t":
                                                        $t_checked = "checked";
                                                        break;

                                                        case "f":
                                                        $f_checked = "checked";
                                                        break;
                                                    }

                                                    echo "<input type='radio' name='envio_consumidor' id='envio_direto_cons' $t_checked value='t'> <label for='envio_direto_cons' style='cursor:pointer'> Direto ao Consumidor </label> <br>";
                                                    echo "<input type='radio' name='envio_consumidor' id='envio_posto' $f_checked value='f'> <label for='envio_posto' style='cursor:pointer'> Para o Posto </label> <br>";
?>

                                                </fieldset>
                                            </td>
                                            <td width="50px">&nbsp;</td>
<?php
                                                    if ($login_fabrica != 101) {
?>
                                            <td align='left' >
                                                <fieldset style="width:150px">
                                                    <legend>Modalidade de Transporte</legend>
                                                    <?
                                                    switch($_POST["modalidade_transporte"]) {
                                                        case "urgente":
                                                        $urgente_checked = "checked";
                                                        break;

                                                        case "normal":
                                                        $normal_checked = "checked";
                                                        break;
                                                    }

                                                    echo "<input type='radio' name='modalidade_transporte' id='modalidade_ri_urgente' $urgente_checked value='urgente'> <label for='modalidade_ri_urgente' style='cursor:pointer'> RI Urgente </label> <br>";
                                                    echo "<input type='radio' name='modalidade_transporte' id='modalidade_ri_normal' $normal_checked value='normal'> <label for='modalidade_ri_normal' style='cursor:pointer'> RI Normal </label>";


                                                    ?>
                                                </fieldset>
                                            </td>
<?php
                                                    }
?>
                                        </tr>

<?php
                                    } else if($login_fabrica == 30) {
?>
                                        <tr>
                                            <input type="hidden" name="cadastra_laudo" value="<?=$cadastra_laudo?>" />
                                            <td width="50px">&nbsp;</td>

                                            <td>
                                                <fieldset style="width:150px">
                                                    <legend>Laudo</legend>
                                                    <input type='radio' name='laudo' id="laudo_fat" value='fat'> <label for='laudo_fat' style='cursor:pointer' <?=($_POST['laudo'] == "fat") ? "checked" : "";?>> F.A.T.</label> <br>
                                                    <input type='radio' name='laudo' id="laudo_far" value='far'> <label for='laudo_far' style='cursor:pointer' <?=($_POST['laudo'] == "far") ? "checked" : "";?>> F.A.R.</label> <br>
                                                    <input type='radio' name='laudo' id="laudo_fats" value='fats'> <label for='laudo_fats' style='cursor:pointer'<?=($_POST['laudo'] == "fats") ? "checked" : "";?>> F.A.T. Sinistro</label> <br>
                                                    <input type='radio' name='laudo' id="laudo_fatrev" value='fatrev'> <label for='laudo_fatrev' style='cursor:pointer'<?=($_POST['laudo'] == "fatrev") ? "checked" : "";?>> F.A.T. Revenda</label> <br>
                                                </fieldset>
                                            </td>
                                        </tr>
<?
                                    }
                        //Status troca Mallory
									if($login_fabrica==72){
										$sql = "select tdocs from tbl_tdocs where referencia_id = $os and fabrica = $login_fabrica ";
										$res = pg_query($con,$sql);

                                        if ($temImg = temNF($os, 'bool') or pg_num_rows($res) > 0) {
                                //OK OS com nota fiscal
                                        }else{
                                            ?>
                                            <tr>
                                                <td style='width:50px'>&nbsp;</td>
                                                <td colspan="3">
                                                    <fieldset>
                                                        <legend>OS sem Nota Fiscal anexada</legend>



                                                        <?      echo "<input type='radio' name='troca_com_nota' id='sem_nota_sem_troca' value='sem_nota_sem_troca'";
                                                        if(strlen($troca_com_nota)==0 or $troca_com_nota=='sem_nota_sem_troca') echo 'checked';
                                                        echo ">&nbsp; <label for='sem_nota_sem_troca' style='cursor:pointer'> Comunicar posto para anexar nota fiscal e não fazer a troca </label><br />";
                                                        echo "<input type='radio' name='troca_com_nota' id='sem_nota_com_troca' value='sem_nota_com_troca'";
                                                        if($troca_com_nota=='sem_nota_com_troca') echo 'checked';
                                                        echo ">&nbsp;<label for='sem_nota_com_troca' style='cursor:pointer'> Proceder com a troca sem a nota fiscal </label>";
                                                        ?>
                                                    </fieldset>
                                                </td>
                                                <td style='width:50px'>&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td style='width:50px'>&nbsp;</td>

                                                <td colspan='3' align='center'>
                                                    <div id='id_justificativanf' class='Conteudo' style='display: none;'>
                                                        <br>Justificativa<br>

                                                        <?
                                                        echo "<textarea name='justificativanf' rows='4' cols='60'>$justificativanf</textarea>";

                                                        ?>
                                                    </div>
                                                </td>

                                                <td style='width:50px'>&nbsp;</td>
                                            </tr>
                                            <?
                                        }
                                    }
                        //Status troca Mallory - Fim
                                    echo "</table>";

                                    if ($login_fabrica == $fabrica_gerencia_telecontrol) {
                                        $sql = "SELECT  hd_chamado,
                                        cpf_conta,
                                        banco,
                                        agencia,
                                        tipo_conta,
                                        favorecido_conta,
                                        valor_produto,
                                        contay
                                        FROM
                                        tbl_hd_chamado_extra_banco
                                        LEFT JOIN tbl_hd_chamado_extra USING(hd_chamado)
                                        LEFT JOIN tbl_hd_chamado_troca USING(hd_chamado)
                                        where os = $os";

                                        $res = pg_exec($con,$sql);

                                        if (pg_num_rows($res)>0) {

                                            $cpf_ressarcimento      = pg_result($res,0,cpf_conta);
                                            $agencia                = pg_result($res,0,agencia);
                                            $conta                = pg_result($res,0,contay);
                                            $banco                  = pg_result($res,0,banco);
                                            $tipo_conta             = pg_result($res,0,tipo_conta);
                                            $valor                = pg_result($res,0,valor_produto);
                                            $favorecido_conta     = pg_result($res,0,favorecido_conta);

                                            $valor = number_format($valor,2,',','.');
                                        }

                                        if ($troca_garantia_produto == '-1') {
                                            $display = 'block';
                                        } else {
                                            $display = 'none';
                                        }
                                        echo "<br><div id='dados_ressarcimento' style='display:$display;width:100%;'>
                                        <div class='subtitulo' style='width:100%'>
                                            Informações Bancárias para o Ressarcimento
                                        </div>
                                        <table border='0' width='600px' align='center'>
                                            <tr>
                                                <td>CPF/CNPJ do Titular</td>
                                                <td>Nome Favorecido</td>

                                            </tr>
                                            <tr>
                                                <td><input type='text' name='cpf_ressarcimento' id='cpf_ressarcimento' class='frm' value='$cpf_ressarcimento' maxlength='14'></td>
                                                <td><input type='text' maxlength='50' name='favorecido_conta' class='frm' value='$favorecido_conta'></td>
                                            </tr>
                                            <tr>
                                                <td colspan='2'>Banco</td>
                                                <td>Tipo Conta</td>
                                            </tr>
                                            <tr>";

                                                $sql = "SELECT banco,codigo,nome from tbl_banco order by nome";
                                                $res = pg_exec($con,$sql);

                                                echo "<td colspan='2'>
                                                <select name='banco' id='banco' class='frm'>
                                                    <option>- escolha</option>";
                                                    for ($i=0;$i<pg_num_rows($res);$i++) {
                                                        $xbanco = pg_result($res,$i,banco);
                                                        $codigo = pg_result($res,$i,codigo);
                                                        $nome = pg_result($res,$i,nome);

                                                        if ($banco == $xbanco) {
                                                            $selected = "SELECTED";
                                                        }
                                                        echo "<option value='$xbanco' $selected>$codigo-$nome</option>";
                                                        $selected = '';
                                                    }
                                                    echo "</select>
                                                </td>
                                                <td><select class='frm' name='tipo_conta' id='tipo_conta'><option>Conta corrente</option> <option>Poupança</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>Agência</td>
                                                <td>Conta Corrente</td>
                                                <td>Valor</td>
                                            </tr>

                                            <tr>
                                                <td><input type='30' maxlength='10' name='agencia' id='agencia' class='frm' value='$agencia'></td>
                                                <td><input type='30' maxlength='15' name='conta' id='conta' class='frm' value='$conta'></td>
                                                <td><input style='text-align:right' type='30' name='valor' id='valor' class='frm money' value='$valor'></td>
                                            </tr>
                                        </table>
                                    </div>";
                                }

                            }else{
                                echo "<p>Trocar pelo Produto ";
                                echo "<input type='text' name='troca_garantia_produto' size='15' maxlength='15' value='$troca_garantia_produto' class='frm'>" ;
                                echo "&nbsp;&nbsp;&nbsp;";
                                if($login_fabrica==20) echo "<b>Valor para Troca</b>";
                                else echo "Mão-de-Obra para Troca";
                                echo" <input type='text' name='troca_garantia_mao_obra' size='5' maxlength='10' value='$troca_garantia_mao_obra' class='frm'>";
                                echo "<br>";
                                echo "(deixe em branco para pagar valor padrão)";
                                echo "<br>";
                                echo "<input type='radio' name='troca_via_distribuidor' value='f' ";
                                if ($troca_via_distribuidor == 'f') echo " checked " ;
                                echo "> Troca Direta ";
                                echo "&nbsp;&nbsp;&nbsp;";
                                echo "<input type='radio' name='troca_via_distribuidor' value='t' ";
                                if ($troca_via_distribuidor == 't') echo " checked " ;
                                echo "> Via Distribuidor";
                                echo "<br>";
                            }
                            echo "<p>";
                            echo "<input type='hidden' name='btn_troca' value=''>";
                    //colocado por Wellington 29/09/2006 - Estava limpando o campo orientaca_sac qdo executava troca
                    //colocado "document.frm_troca.orient_sac.value = document.frm_os.orientacao_sac.value"

                            ?>
                        </td>
                    </tr>
<?
if($login_fabrica == 30){
?>
                    <tr class="formulario">
                        <td colspan="100%" align="center"   >
                            <input type='button' value="Gravar" onclick="javascript: fazer_troca();">
                        </td>
                    </tr>
<?
}else{
?>
                    <tr class="formulario">
                        <td colspan="100%" align="center"   >
                            <input type='button' value="Efetuar Troca" onclick="javascript: <?if($verifica_ressarcimento_troca) { ?>selIt(); <?}?> fazer_troca();">
                        </td>
                    </tr>
<?
}
?>
                </table>
            </table>
        </td>
    </tr>



    <?
    if (in_array($login_fabrica, array(14,43,66))) {

        echo "<tr class='Conteudo'><td align='center'><a href='#'><img src='imagens/btn_solicitar_coleta.gif'></a></td></tr>";

    }

    ?>
</table>

</td>
</tr>
</form>
</table>

<?
}
}
}
}

if ($login_fabrica == 101 && (isset($_GET['osacao']) && $_GET['osacao'] == 'trocar')) {
?>
    <script type="text/javascript">
        $(function(){
            $('input[name=solicita_lgr]').change(function(){
                if ($(this).is(':checked')) {
                    if ($('#troca_garantia_produto :selected').val()) {
                        if ($('#troca_garantia_produto :selected').val() !== $('#produto_os_troca_atual').val()) {
                            alert('Para solicitar o produto na LGR o produto da troca deve ser o mesmo modelo da O.S.');
                        }
                    }else{
                        alert('IMPORTANTE: Não esqueça de selecionar o mesmo produto para solicitar a LGR corretamente do produto');
                    }

                    $('#gerar_pedido').trigger('change');
                }
            });
            $('#gerar_pedido').change(function(){
                if (!$(this).is(':checked') && $('input[name=solicita_lgr]').is(':checked')) {
                    $('#gerar_pedido').prop('checked', true);
                }
            });
        });
    </script>
<?php
}

if ($login_fabrica == 91) {
?>
    <script type="text/javascript">
        $(function(){
            $("input[name=consumidor_revenda]").click(function() {
                alterarLabelWanke();
            });
        });

        function alterarLabelWanke() {
          var consumidor_revenda = $("input[name=consumidor_revenda]:checked").val();
          var descricao_campo = "";
          //$("#consumidor_cpf").val("");

          if (consumidor_revenda == "C") {
            descricao_campo = "CPF";
            $("#consumidor_cpf").mask("999.999.999-99");
          } else {
            descricao_campo = "CNPJ";
            $("#consumidor_cpf").mask("99.999.999/9999-99");
          }

          $("span[rel=consumidor_cpf").html(descricao_campo);
        }

        $(document).ready(function() {
            alterarLabelWanke();
        });

    </script>

<?php
}
?>

<?php if ($login_fabrica == 20) { ?>
        <script type="text/javascript">
            $(document).ready(function() {
                let tel = $("input[name=consumidor_fone]").val().replace(/\D/g, '');
                $("input[name=consumidor_fone]").val(tel).mask('99999999999');
            });
        </script>
<?php } ?>


<p>
<script language='javascript' src='address_components.js'></script>
    <? include "rodape.php";?>
    <?php

function getSelectContentProdutosOs($os){
    $model = ModelHolder::init('Produto');
    $sql = 'SELECT tbl_produto.*
            FROM tbl_produto
            INNER JOIN tbl_os_produto
                ON (tbl_produto.produto = tbl_os_produto.produto)
            INNER JOIN tbl_os
                ON (tbl_os_produto.os = tbl_os.os)
            WHERE
            tbl_os.os = :os';
    $params = array(':os'=>$os);
    $result = $model->executeSql($sql,$params);
    $result = array_map(function($e){
        return array(
            'value' => $e['produto'],
            'label' => '['.$e['referencia'].'] '.$e['descricao']
        );
    },$result);
    return $result;
}

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
        document.getElementById("consumidor_revenda_hidden").value = 'C';

    } else {
        $("#consumidor_cpf").mask("99.999.999/9999-99");
        document.getElementById("consumidor_revenda_suggar_cpf").checked = false;
        document.getElementById("consumidor_revenda_suggar_cnpj").checked = true;
        document.getElementById("consumidor_revenda_hidden").value = 'R';
    }

}


$(document).ready(function() {
    alterarMaskSuggar();
});

</script>
<?php } ?>

<?php

if($login_fabrica == 123) : ?>
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
