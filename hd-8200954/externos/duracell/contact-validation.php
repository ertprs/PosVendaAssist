<?php

if (!count(array_filter($_POST))) {
    if (isset($_SERVER['HTTP_REFERER']))
        header("Location: " . $_SERVER['HTTP_REFERER']);
    header('Location: http://www.duracellcarregadores.com/#formularioContato');
    exit;
}

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
// include '../../helpdesk/mlg_funciones.php';

$SuccessPage = 'http://www.duracellcarregadores.com/contato-obrigado.html';
$ErrorPage = 'http://www.duracellcarregadores.com/contato-erro.html';


if($_POST["buscaProduto"] == true){
    $sql = "SELECT
                tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
            FROM tbl_produto
            JOIN tbl_linha USING (linha)
            WHERE tbl_linha.fabrica = 155";
    $res = pg_query($con,$sql);

    if((pg_num_rows ($res)) > 0) {

        $produtos = array();

        for($i = 0; $i < pg_num_rows ($res); $i++) {
            $produto    = trim(pg_fetch_result($res,$i,'produto'));
            $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
            $referencia = trim(pg_fetch_result($res, $i, 'referencia'));
            $descricao = $referencia.' - '.$descricao;

            $produtos[$i] = array(
                "produto" => $produto,
                "descricao" => $descricao
            );
        }
        $retorno = array('produtos' => $produtos );
    }
    exit(json_encode($retorno));
}

if($_POST["buscaCidade"] == true) {

    $estado = strtoupper($_POST["estado"]);

    if(strlen($estado) > 0) {

        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade ORDER BY cidade ASC";
        $res  = pg_query($con, $sql);

        $rows = pg_num_rows($res);
        if ($rows > 0) {
          $cidades = array();

          for ($i = 0; $i < $rows; $i++) {
            $cidades[$i] = array(
              "cidade"          => utf8_encode(pg_fetch_result($res, $i, "cidade")),
              "cidade_pesquisa" => utf8_encode(pg_fetch_result($res, $i, "cidade")),
            );
          }

          $retorno = array("cidades" => $cidades);
        } else {
          $retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
        }
      } else {
        $retorno = array("erro" => "Nenhum estado selecionado");
      }

    exit(json_encode($retorno));
}

if($_POST['buscaDefeito'] == true){
    $produto = $_POST['produto'];

    $sql = "SELECT DISTINCT tbl_defeito_reclamado.descricao,
                tbl_defeito_reclamado.defeito_reclamado
            FROM tbl_diagnostico
                JOIN tbl_defeito_reclamado
                    ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
                JOIN tbl_produto
                    ON tbl_diagnostico.familia = tbl_produto.familia
            WHERE tbl_diagnostico.fabrica = 155
                AND tbl_diagnostico.ativo IS TRUE
                AND tbl_produto.produto = $produto
            UNION
            SELECT DISTINCT tbl_defeito_reclamado.descricao,
                tbl_familia_defeito_reclamado.defeito_reclamado
            FROM tbl_familia_defeito_reclamado
                JOIN tbl_defeito_reclamado
                    ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
                AND tbl_defeito_reclamado.fabrica = 155
            ORDER BY 1";
    $res = pg_query($con, $sql);
    #echo $sql;exit;
    if((pg_num_rows ($res)) > 0) {

        $defeitos = array();

        for($i = 0; $i < pg_num_rows ($res); $i++) {
            $defeito_reclamado = trim(pg_fetch_result($res,$i,'defeito_reclamado'));
            $descricao_defeito = trim(pg_fetch_result($res,$i,'descricao'));

            $defeitos[$i] = array(
                "defeito_reclamado" => $defeito_reclamado,
                "descricao_defeito" => utf8_encode($descricao_defeito),
            );
        }
        $retorno = array('defeitos' => $defeitos );
    }
    exit(json_encode($retorno));
}

/**
 * Registra um chamado para uma mensagem enviada pela página "fale_conosco"
 * @param array $data Array associativo contendo pares K=V
 * @param connection $con Conexão com o banco de dados. Se null, retorno imediato
 * @return integer Se sucesso, o ID do chamado; se erro, zero
 */

if(!function_exists('retira_acentos')){
    function retira_acentos( $texto ){
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
        return str_replace( $array1, $array2, $texto );
    }
}

function registraEmail( $data, $con = null ) {
    if ($con == NULL) {
        return 0;
    }

    $sqlCidade = "SELECT cidade
                FROM tbl_cidade
                WHERE UPPER(TO_ASCII(nome, 'LATIN9')) = UPPER(TO_ASCII('{$data['cidade']}', 'LATIN9'))
                AND UPPER(estado) = UPPER('{$data['estado']}')";
    $resCidade = pg_query($con, $sqlCidade);

    if(pg_num_rows($resCidade) > 0){
        $data['cidade_id'] = pg_fetch_result($resCidade, 0, 'cidade');
    }

    if($data['assunto'] == "duvida_produto"){
        $data['assunto_text'] = "Informação";
    }elseif($data['assunto'] == "sugestao"){
        $data['assunto_text'] = "Sugestão";
    }else{
        $data['assunto_text'] = "Reclamação";
    }

    if(strlen($data['cep']) > 0){
        $cep = $data['cep'];
        $valor = str_replace("-", "", $cep);
        $data['cep'] = $valor;
    }

    if(strlen(trim($data['nome']))          == ""){ $msg_erro = 1; }
    if(strlen(trim($data['email']))         == ""){ $msg_erro = 1; }
    if(strlen(trim($data['telefone']))      == ""){ $msg_erro = 1; }
    #if(strlen(trim($data['celular']))       == ""){ $msg_erro = 1; }
    if(strlen(trim($data['cep']))           == ""){ $msg_erro = 1; }
    if(strlen(trim($data['endereco']))      == ""){ $msg_erro = 1; }
    if(strlen(trim($data['num']))           == ""){ $msg_erro = 1; }
    #if(strlen(trim($data['complemento']))   == ""){ $msg_erro = 1; }
    if(strlen(trim($data['bairro']))        == ""){ $msg_erro = 1; }
    if(strlen(trim($data['produto']))       == ""){ $msg_erro = 1; }
    if(strlen(trim($data['defeito']))       == ""){ $msg_erro = 1; }

    $sql1 = "INSERT INTO tbl_hd_chamado (" .
            "admin, data, atendente, " .
            "fabrica_responsavel, fabrica, " .
            "titulo, status, categoria " .
            ") VALUES ( " .
            "{$data['admin']}, NOW(), {$data['admin']}, " .
            "{$data['fabrica']}, {$data['fabrica']}, " .
            "'Atendimento Fale Conosco', 'Aberto', '{$data['assunto']}'" .
            ") RETURNING hd_chamado";

    $sql2 = "INSERT INTO tbl_hd_chamado_extra ( " .
            "hd_chamado, " .
            "nome, email, fone, " .
            "celular, cep, endereco, " .
            "numero, complemento, bairro, " .
            "cidade, produto, defeito_reclamado, reclamado" .
            ") VALUES ( " .
            ":chamado, " .
            "'{$data['nome']}', '{$data['email']}', '{$data['telefone']}'," .
            "'{$data['celular']}', '{$data['cep']}', '{$data['endereco']}'," .
            "'{$data['num']}', '{$data['complemento']}', '{$data['bairro']}'," .
            "'{$data['cidade_id']}', '{$data['produto']}', '{$data['defeito']}', '{$data['mensagem']}' )";

    $sql3 = "INSERT INTO tbl_hd_chamado_item ( " .
            "hd_chamado, admin, " .
            "comentario " .
            ") VALUES ( " .
            ":chamado, {$data['admin']}, " .
            "E'{$data['assunto_text']}\n{$data['mensagem']}' )";

    $retValue = 0;

    try {
        pg_query($con, 'BEGIN');

        $result = pg_query($con, $sql1);

        if ($result == false) {
            throw new Exception($sql1);
        }

        if($msg_erro > 0){
            throw new Exception("Erro ao enviar formulario");
        }

        $retValue = pg_fetch_result($result, 0, 0);
        $sql2 = str_replace(":chamado", $retValue, $sql2);
        $sql3 = str_replace(":chamado", $retValue, $sql3);

        if (pg_query($con, $sql2) == false) {
            throw new Exception($sql2);
        }

        if (pg_query($con, $sql3) == false) {
            throw new Exception($sql3);
        }

        pg_query($con, 'COMMIT');
    } catch (Exception $ex) {
        $retValue = 0;
        pg_query($con, 'ROLLBACK');
    }

    return $retValue;
}

$data = array_map('utf8_decode', $_POST);
$data = array_map('trim', $data);
$data['fabrica'] = 155;
$data['admin'] = 7820;
$hd_chamado = registraEmail($data, $con);

if ( $hd_chamado == 0) {
?>
    <script type="text/javascript">
        window.top.location.href = "http://www.duracellcarregadores.com/contato-erro.html";
    </script>
<?php
exit;
}

$email_assunto = "Contato Site Duracell Protocolo - $hd_chamado";
$email_destinatario = "sac@duracellcarregadores.com";
$email_reply = $email;

if ($_serverEnvironment == "development") {
    $email_remetente = "guilherme.curcio@telecontrol.com.br";
    $email_destinatario = "guilherme.monteiro@telecontrol.com.br";
}

$email_headers = implode ( "\n",
  array (
    "From: $email_remetente",
    "Reply-To: $email_reply",
    "Return-Path: $email_remetente",
    "MIME-Version: 1.0",
    "X-Priority: 3",
    "Content-Type: text/html; charset=UTF-8"
  )
);


$mensagem.="<br/><br/>Segue abaixo o link para acesso ao chamado:<br/><br/> http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado";

$email_form = "Contato Site<br/><br/>" .
  "<strong>Nome:</strong> $nome <br/>" .
  "<strong>Email:</strong> $email <br/>" .
  "<strong>Cidade:</strong> $cidade <br/>" .
  "<strong>Telefone:</strong> $telefone <br/>" .
  "<strong>Protocolo:</strong> $hd_chamado <br/><br/>" .
  "<strong>Mensagem:</strong> $mensagem <br/>";

// poderemos enviar uma cópia do email para o remetende?
mail("$email_destinatario", "$email_assunto", "$email_form", "$email_headers" );


//header("Location: " . $SuccessPage);
?>

<script type="text/javascript">
window.top.location.href = "http://www.duracellcarregadores.com/contato-obrigado.html";
</script>
