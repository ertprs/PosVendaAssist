 <?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once S3CLASS;

$s3 = new AmazonTC("procedimento", $login_fabrica);
$ano = '2016';
$mes = '04';

if (isset($_POST["excluidefeitosolucao"])) {

    $defeito_solucao_id = $_POST["id_defeito_solucao"];

    //pg_query($con,'BEGIN');

    $sql_up = "DELETE FROM tbl_defeito_constatado_solucao
               WHERE defeito_constatado_solucao = {$defeito_solucao_id}
               AND fabrica = {$login_fabrica}";
    $res_up = pg_query($con, $sql_up);

    $status = (pg_affected_rows($res_up) > 0) ? true : false;

    if($status == true){
        $descricao = "";
    }else{
        $descricao = "Não é possível excluir este relacionamento pois ele já possui ligação com um atendimento";
    }

    $descricao = utf8_encode($descricao);

    echo json_encode(array("status" => $status, "descricao" => $descricao));
    exit;

}

/**
* Cria a chave do anexo
*/

if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}

/**
* Inclui o arquivo no s3
*/
if (isset($_POST["ajax_anexo_upload"])) {
    
    $chave   = $_POST["anexo_chave"];
    $arquivo = $_FILES["anexo_upload"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($arquivo["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
        } else {
            $arquivo_nome = "{$chave}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == "pdf"){
                $link = "imagens/pdf_icone.png";
            } else if(in_array($ext, array("doc", "docx"))) {
                $link = "imagens/docx_icone.png";
            } else {
                $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
            }

            if (!strlen($link)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);
                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    //$retorno["posicao"] = $posicao;

    exit(json_encode($retorno));
}

/**
* Excluir anexo
*/
if (isset($_POST["ajax_anexo_exclui"])) {

    $anexo_nome_excluir = $_POST['anexo_nome_excluir'];     

    if (count($anexo_nome_excluir) > 0) {
        $s3->deleteObject($anexo_nome_excluir, false, $ano, $mes);
        $retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
    }else{
        $retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
    }

    exit(json_encode($retorno));

}//Fim excluir anexo

if(strlen($_GET["produto_referencia"]) > 0){

    $produto_referencia = trim($_GET["produto_referencia"]);
    $sql_referencia = "SELECT descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND referencia = '{$produto_referencia}'";
    $res_referencia = pg_query($con, $sql_referencia);

    $produto_descricao = pg_fetch_result($res_referencia, 0, "descricao");
}

if($login_fabrica == 52){
	
    if(isset($_POST["ajax_defeito_constatado_grupo"])){

        $defeito_constatado_grupo = $_POST["defeito_constatado_grupo"];
        $familia = $_POST["familia"];

        $sql = "SELECT DISTINCT
                    tbl_defeito_constatado.defeito_constatado,
                    tbl_defeito_constatado.descricao
                FROM tbl_defeito_constatado
                JOIN tbl_diagnostico USING(fabrica,defeito_constatado)
                --JOIN tbl_posto_fabrica USING(tabela_mao_obra)
                WHERE tbl_defeito_constatado.defeito_constatado_grupo = $defeito_constatado_grupo
                    AND tbl_defeito_constatado.ativo = 't'
                    AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                    AND tbl_diagnostico.ativo = 't'
                    AND tbl_diagnostico.familia = $familia";
                    //AND tbl_posto_fabrica.fabrica = $login_fabrica
                    

        $res = pg_query($con, $sql);

        $defeitos_constatados = array();

        $contador_res = pg_num_rows($res);

        if($contador_res > 0){

            for($i = 0; $i < $contador_res; $i++){

                $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
                $descricao          = pg_fetch_result($res, $i, "descricao");

                $defeitos_constatados[] = array("defeito_constatado" => utf8_encode($defeito_constatado), "descricao" => utf8_encode($descricao));

            }

        }else{

            $defeitos_constatados[] = array("defeito_constatado" => "", "descricao" => "Nenhum Defeito Constatado encontrado");

        }

        exit(json_encode(array("retorno" => $defeitos_constatados)));

    }

}

if (strlen($_GET["prod"]) > 0 ) {
    $produto_consulta = $_GET["prod"];
    $sql_prod = "SELECT produto, referencia, descricao
                    FROM tbl_produto
                    WHERE fabrica_i = $login_fabrica
                    AND produto = $produto_consulta
                    LIMIT 1;";
        $res_prod = pg_query($con,$sql_prod);
        if (pg_num_rows($res_prod) > 0) {
            $produto_referencia = pg_fetch_result($res_prod, 0, referencia);
            $produto_descricao = pg_fetch_result($res_prod, 0, descricao);
            $produto = pg_fetch_result($res_prod, 0, produto);
        }
        //echo $produto_referencia."<<<<";
}

if (isset($_POST["ativaprocedimento"])) {
    $defeito_solucao_id = $_POST["id_defeito_solucao"];

    //pg_query($con,'BEGIN');

    $sql_up = "UPDATE tbl_defeito_constatado_solucao
                    SET ativo = 't'
                    WHERE defeito_constatado_solucao = {$defeito_solucao_id}
                    AND fabrica = {$login_fabrica}";
    $res_up = pg_query($con, $sql_up);

    // if ( ! is_resource($res_up) ) {
    //  pg_query($con,"ROLLBACK");
    // }else{
    //  pg_query($con,'COMMIT');
    // }

    $status = (pg_affected_rows($res_up) > 0) ? true : false;

    if($status == true){
        $descricao = "";
    }else{
        $descricao = "Erro ao Ativar o Defeito / Solução";
    }

    $descricao = utf8_encode($descricao);

    echo json_encode(array("status" => $status, "descricao" => $descricao));
    exit;

}

if (isset($_POST["inativaprocedimento"])) {
    $defeito_solucao_id = $_POST["id_defeito_solucao"];

    //pg_query($con,'BEGIN');

    $sql_up = "UPDATE tbl_defeito_constatado_solucao
                    SET ativo = 'f'
                    WHERE defeito_constatado_solucao = {$defeito_solucao_id}
                    AND fabrica = {$login_fabrica}";
    $res_up = pg_query($con, $sql_up);

    // if ( ! is_resource($res_up) ) {
    //  pg_query($con,"ROLLBACK");
    // }else{
    //  pg_query($con,'COMMIT');
    // }

    $status = (pg_affected_rows($res_up) > 0) ? true : false;

    if($status == true){
        $descricao = "";
    }else{
        $descricao = "Erro ao Inativar o Defeito / Solução";
    }

    $descricao = utf8_encode($descricao);

    echo json_encode(array("status" => $status, "descricao" => $descricao));
    exit;

}

if (isset($_POST["atualizacombo"])) {
    $combo = $_POST["combo"];

    if ($combo == 'defeito') {
        if ($login_fabrica == 3) {
            $sqlx = "SELECT tbl_defeito_constatado.defeito_constatado,
                                        tbl_defeito_constatado.codigo,
                                        tbl_defeito_constatado.descricao
                                from tbl_defeito_constatado
                                JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                                where tbl_defeito_constatado.fabrica = $login_fabrica
                                AND tbl_defeito_constatado_grupo.descricao = 'HD'
                                AND tbl_defeito_constatado.ativo = 't'
                                ORDER BY descricao";
        }else{
            $sqlx = "SELECT defeito_constatado, codigo, descricao
                        FROM tbl_defeito_constatado
                        WHERE fabrica = $login_fabrica AND ativo = 't'
                        ORDER BY descricao";
        }

        $resx = pg_exec($con,$sqlx);
        if (pg_num_rows($resx)>0) {
            $result = "<option value=''>Selecione</option>";
            foreach (pg_fetch_all($resx) as $key) {
                $selected_defeito = (isset($defeito) and ($key['defeito_constatado'] == $defeito)) ? "SELECTED" : '' ;
                $key['codigo'] = (strlen($key['codigo']) > 0) ? $key['codigo']." - " : "";
                $result .= "<option value=".$key['defeito_constatado']."".$selected_defeito.">".$key['codigo']."".$key['descricao']."</option>";
            }
        }
        echo $result;
        exit;
    }

    if ($combo == 'solucao') {
        if ($login_fabrica == 3) {
            $where_cod = "AND tbl_solucao.codigo = 'HD' ";
        }
        $sqlx = "SELECT solucao, descricao
                    FROM tbl_solucao
                    WHERE fabrica = $login_fabrica
                    AND ativo = 't'
                    $where_cod
                    ORDER BY descricao ASC";

        $resx = pg_exec($con,$sqlx);
        if (pg_num_rows($resx)>0) {

            $result = '<h5 class="asteristico">*</h5>
                            <select name="solucao[]" id="solucao" multiple="multiple">';

            foreach (pg_fetch_all($resx) as $key) {
                $selected_solucao = (isset($solucao_post) and (in_array($key['solucao'], $solucao_post))) ? "SELECTED" : '' ;
                $result .= "<option value=".$key['solucao']."".$selected_solucao.">".$key['solucao']." - ".$key['descricao']."</option>";
            }
            $result .= "</select>";
        }
        echo $result;
        exit;
    }
}

if ($login_fabrica == 3) {

    if (isset($_POST["atualizacombodefeito"])) {
        $produto = $_POST["produto"];
        $defeito = $_POST["defeito"];
        $familia = $_POST['familia'];

        $produtos = implode("','", $produto);

        if ($_POST['todosProdutos'] != "true") {
            $condProd = "AND tbl_produto.referencia IN ('{$produtos}')";
        } else {
            $condProd = "AND tbl_produto.familia = $familia";
        }

        $sqlx = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao) 
                        tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.codigo,
                        tbl_defeito_constatado.descricao,
                        tbl_defeito_constatado.ativo
                FROM tbl_defeito_constatado
                JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                JOIN tbl_produto_defeito_constatado USING(defeito_constatado)
                JOIN tbl_produto ON tbl_produto.produto = tbl_produto_defeito_constatado.produto
                {$condProd}
                WHERE tbl_defeito_constatado.fabrica = $login_fabrica
                AND tbl_defeito_constatado_grupo.descricao = 'HD'
                ORDER BY descricao";

        $resx = pg_exec($con,$sqlx);

        if (pg_num_rows($resx)>0) {
            $result = "<option value=''>Selecione</option>";
            foreach (pg_fetch_all($resx) as $key) {
                $selected_defeito = ($key['defeito_constatado'] == $defeito) ? "SELECTED" : '' ;
                $key['codigo'] = (strlen($key['codigo']) > 0) ? $key['codigo']." - " : "";
                $result .= "<option value='".$key['defeito_constatado']."' ".$selected_defeito.">".$key['codigo']."".$key['descricao']."</option>";
            }
        }

        echo $result;
        exit;
    }

    if (isset($_POST["atualizacombosolucao"])) {
        $produto  = $_POST["produto"];
        $solucoes = $_POST["solucoes"];
        $familia  = $_POST['familia'];

        $produtos = implode("','", $produto);

        if ($_POST['todosProdutos'] != "true") {
            $condProd = "AND tbl_produto.referencia IN ('{$produtos}')";
        } else {
            $condProd = "AND tbl_produto.familia = $familia";
        }

        $sqlx = "SELECT DISTINCT ON (tbl_solucao.descricao)
                        tbl_solucao.solucao,
                        tbl_solucao.codigo,
                        tbl_solucao.descricao,
                        tbl_solucao.ativo,
                        tbl_solucao.troca_peca
                    FROM tbl_solucao
                    JOIN tbl_defeito_constatado_solucao USING (solucao)
                    JOIN tbl_produto ON tbl_defeito_constatado_solucao.produto = tbl_produto.produto
                    {$condProd}
                    WHERE tbl_solucao.fabrica = $login_fabrica
                    AND tbl_solucao.codigo = 'HD'
                    AND tbl_defeito_constatado_solucao.defeito_constatado is null
                    ORDER BY descricao";
        $resx = pg_exec($con,$sqlx);
        if (pg_num_rows($resx)>0) {

            $result = '<h5 class="asteristico">*</h5>
                            <select name="solucao[]" id="solucao" multiple="multiple">';

            foreach (pg_fetch_all($resx) as $key) {
                $selected_solucao = (in_array($key['solucao'], $solucoes)) ? "SELECTED" : '' ;
                $result .= "<option value='".$key['solucao']."' ".$selected_solucao.">".$key['solucao']." - ".$key['descricao']."</option>";
            }
            $result .= "</select>";
        }
        echo $result;
        exit;

    }
}


if(isset($_POST["get_procedimento"])){

    $defeito = $_POST["defeito"];
    $solucao = $_POST["solucao"];
    $produto = $_POST["produto"];

    $sql_comp = "SELECT solucao_procedimento AS procedimento
                FROM tbl_defeito_constatado_solucao
                WHERE defeito_constatado = {$defeito}
                        AND solucao = {$solucao}
                        AND produto = {$produto}
                        AND fabrica = {$login_fabrica}";
    $res_comp = pg_query($con, $sql_comp);

    if(pg_num_rows($res_comp) > 0){

        $procedimento = pg_fetch_result($res_comp, 0, "procedimento");

        if (!in_array($login_fabrica, array(3))) {
            $link_alterar = "<a href='defeitos_solucoes_procedimento.php?defeito={$defeito}&solucao={$solucao}&produto={$produto}' rel='shadowbox; width = 900; height = 450;'>Editar Descrição</a>";

            if (strlen($procedimento) > 60) {
                $procedimento = substr($procedimento, 0, 50)."... <br /> <a href='defeitos_solucoes_procedimento.php?defeito={$defeito}&solucao={$solucao}&produto={$produto}&box=1' rel='shadowbox; width = 900; height = 355;'>Leia Mais</a> &nbsp; ".$link_alterar;
            } else {
                $procedimento = $procedimento."<br />".$link_alterar;
            }
        }
    }else{
        $procedimento = "";
    }

    echo $procedimento;

    exit;

}

if(isset($_POST["del_procedimento"])){

    $id_defeito_solucao = $_POST["id_defeito_solucao"];
    $acao = $_POST["btn_acao"];

    $status = ($acao == 'ativar') ? "true" : "false";

    if (in_array($login_fabrica, array(52,158))) {
        if ($login_fabrica == 52) {
            $sql_del = "UPDATE tbl_defeito_constatado_solucao SET ativo = {$status} WHERE defeito_constatado_solucao = {$id_defeito_solucao}";
        } else {
            $sql_del = "UPDATE tbl_diagnostico SET ativo = {$status} WHERE diagnostico = {$id_defeito_solucao}";
        }

    }else{

        $sql_verifica = "SELECT defeito_constatado_solucao FROM tbl_dc_solucao_hd WHERE defeito_constatado_solucao = {$id_defeito_solucao}";
        $res_verifica = pg_query($con, $sql_verifica);

        if(pg_num_rows($res_verifica) > 0){
            $descricao = utf8_encode("O Defeito / Solução não pode ser excluido, pois existem chamados atrelados aos mesmos.");
            echo json_encode(array("status" => false, "descricao" => $descricao));
            exit;
        }

        $sql_del = "DELETE FROM tbl_defeito_constatado_solucao
                    WHERE defeito_constatado_solucao = {$id_defeito_solucao}
                    AND fabrica = {$login_fabrica}";

    }

    $res_del = pg_query($con, $sql_del);

    $status = (pg_affected_rows($res_del) > 0) ? true : false;

    if($status == true){
        $descricao = "";
    }else{
        $descricao = "Erro ao Excluir o Defeito / Solução";
    }

    $descricao = utf8_encode($descricao);

    echo json_encode(array("status" => $status, "descricao" => $descricao));
    exit;

}

if ($_POST["btn_acao"] == "submit") {

    $_GET["listar_tudo"] = 1;
    $_GET["familia_x"] = $_POST['familia'];

    if($login_fabrica == 52){    	
        $defeito_constatado_grupo = $_POST["defeito_constatado_grupo"];
    } else {
        $produto_referencia = trim($_POST["produto_referencia"]);
        $produto_descricao = trim($_POST["produto_descricao"]);
    }

    $defeito = trim($_POST["defeito"]);
    $solucao_post = $_POST["solucao"];
    $procedimento = str_replace("\t","    ", $_POST["procedimento"]);
    $anexo_procedimento = $_POST["anexo"];
    $situacao = $_POST["situacao"];

    if (!in_array($login_fabrica, array(3,52,158)) || (in_array($login_fabrica, [3]) && $situacao == 'alterar')) {

        if (strlen($produto_referencia) == 0 || strlen($produto_descricao) == 0) {

            $msg_erro["msg"][]    = "Preencha os Produtos";
            $msg_erro["campos"][] = "produto";

        } else {

            $sql_produto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND referencia = '{$produto_referencia}' OR descricao = '{$produto_descricao}' LIMIT 1;";
            $res_produto = pg_query($con, $sql_produto);

            if(pg_num_rows($res_produto) > 0){
                $produto = pg_fetch_result($res_produto, 0, "produto");
                $cond_produto = "AND produto = {$produto}";
            }

        }

    }

    if (in_array($login_fabrica, [3]) && $situacao != "alterar") {

        $radio_qtde_produtos = $_POST['radio_qtde_produtos'];

        $produtos_pesquisa = implode("','",$_POST['PickList']);
        $produtos_pesquisa = strtoupper($produtos_pesquisa);

        $sqlProdutos = "SELECT tbl_produto.produto,
                               tbl_produto.referencia,
                               tbl_produto.descricao
                        FROM tbl_produto
                        WHERE UPPER(tbl_produto.referencia) IN ('{$produtos_pesquisa}')
                        AND tbl_produto.fabrica_i = {$login_fabrica}";
        $resProdutos = pg_query($con, $sqlProdutos);

        while ($dadosProd = pg_fetch_object($resProdutos)) {

            $lista_produtos[] = [$dadosProd->produto, $dadosProd->referencia, $dadosProd->descricao];

        }

    }
    
    if (empty($produto)) {
        $produto = "null";
    }

    if (strlen($defeito) == 0) {
        $msg_erro["msg"][]    = "Preencha o Defeito";
        $msg_erro["campos"][] = "defeito";
    }

    if (count($solucao_post) == 0) {
        if(count($msg_erro["msg"]) == 0){
            $msg_erro["msg"][] = "Preencha os campos obrigatórios";
        }
        $msg_erro["campos"][] = "solucao";
    }

    $familia = $_REQUEST['familia'];

    if (in_array($login_fabrica, array(158))) {
        $tipo     = $_REQUEST['tipo'];
        $garantia = $_REQUEST['garantia'];

        if ($tipo == 272) {
            $garantia = 'f';
        }

        if (empty($garantia)) {
            $msg_erro["msg"][] = "Preencha a Garantia";
            $msg_erro["campos"][] = "garantia";
        }

        if (empty($familia)) {
            $msg_erro["msg"][] = "Selecione uma Família";
            $msg_erro["campos"][] = "familia";
        }
    }

    if (count($msg_erro["msg"]) == 0 && (!in_array($login_fabrica, [3]) || (in_array($login_fabrica, [3]) && $situacao == "alterar"))) {

        if (empty($situacao)) {

        	$contador_situacao = count($solucao_post);

            for($i = 0; $i < $contador_situacao; $i++){

                $solucao = $solucao_post[$i];

                if (in_array($login_fabrica, array(158))) {
                    $sql_comp = "
                        SELECT defeito_constatado, solucao
                        FROM tbl_diagnostico
                        WHERE defeito_constatado = {$defeito}
                        AND solucao = {$solucao}
                        AND garantia = '{$garantia}'
                        AND familia = {$familia}
                        AND fabrica = {$login_fabrica}
                    ";
                } else {
                    $sql_comp = "
                        SELECT defeito_constatado, solucao
                        FROM tbl_defeito_constatado_solucao
                        WHERE defeito_constatado = {$defeito}
                        AND solucao = {$solucao}
                        {$cond_produto}
                        AND fabrica = {$login_fabrica}
                    ";
                }

                $res_comp = pg_query($con, $sql_comp);

                if(pg_num_rows($res_comp) == 0){
                    if (in_array($login_fabrica, array(158))) {
                        $sql = "INSERT INTO tbl_diagnostico
                                    (defeito_constatado, solucao, garantia, familia, fabrica, tipo_atendimento)
                                VALUES
                                    ($defeito, $solucao, '$garantia', $familia, $login_fabrica, $tipo)";
                    } else {
                        $sql = "INSERT INTO tbl_defeito_constatado_solucao
                                        (defeito_constatado, solucao, produto, fabrica)
                                VALUES
                                        ($defeito, $solucao, $produto, $login_fabrica)";
                    }

                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][] = "Ocorreu um erro no cadastro das informações";
                    }

                }
            }

        } else if (strlen($situacao) > 0 && $situacao == "alterar") {

            if (in_array($login_fabrica, array(158))) {

                $sql_del = "DELETE FROM tbl_diagnostico
                            WHERE defeito_constatado = {$defeito}
                            AND garantia = '{$garantia}'
                            AND familia = {$familia}
                            AND fabrica = {$login_fabrica};";

            } else {

                $sql_del = "DELETE FROM tbl_defeito_constatado_solucao
                            WHERE defeito_constatado = {$defeito}
                            {$cond_produto}
                            AND fabrica = {$login_fabrica};";

            }

            $res_del = pg_query($con, $sql_del);

            $contador_situacao = count($solucao_post);

            for($i = 0; $i < $contador_situacao; $i++){

                $solucao = $solucao_post[$i];

                if (in_array($login_fabrica, array(158))) {
                    $sql_comp = "SELECT defeito_constatado, solucao
                                FROM tbl_diagnostico
                                WHERE defeito_constatado = {$defeito}
                                AND solucao = {$solucao}
                                AND garantia = '{$garantia}'
                                AND familia = {$familia}
                                AND fabrica = {$login_fabrica}";
                } else {
                    $sql_comp = "SELECT defeito_constatado, solucao
                                FROM tbl_defeito_constatado_solucao
                                WHERE defeito_constatado = {$defeito}
                                AND solucao = {$solucao}
                                {$cond_produto}
                                AND fabrica = {$login_fabrica}";
                }

                //die(nl2br($sql_comp));

                $res_comp = pg_query($con, $sql_comp);

                if(pg_num_rows($res_comp) == 0){
                    if (in_array($login_fabrica, array(158))) {
                        $sql = "INSERT INTO tbl_diagnostico
                                    (defeito_constatado, solucao, garantia, familia, fabrica)
                                VALUES
                                    ($defeito, $solucao, '$garantia', $familia, $login_fabrica)";
                    } else {
                        $sql = "INSERT INTO tbl_defeito_constatado_solucao
                                        (defeito_constatado, solucao, produto, fabrica)
                                VALUES
                                        ($defeito, $solucao, $produto, $login_fabrica)";
                    }

                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][] = "Ocorreu um erro na alteração das informações";
                    }

                }

            }

        }

    } else if (count($msg_erro["msg"]) == 0 && in_array($login_fabrica, [3]) && $situacao != "alterar") {

        pg_query($con, "BEGIN");

        if ($radio_qtde_produtos != 'muitos') {

            foreach ($lista_produtos as $prodInfo) {

                $produto = $prodInfo[0];

                $contador_situacao = count($solucao_post);

                for($i = 0; $i < $contador_situacao; $i++){

                    $solucao = $solucao_post[$i];

                    $sql_comp = "
                        SELECT defeito_constatado, solucao, defeito_constatado_solucao
                        FROM tbl_defeito_constatado_solucao
                        WHERE defeito_constatado = {$defeito}
                        AND solucao = {$solucao}
                        AND produto = {$produto}
                        AND fabrica = {$login_fabrica}
                    ";

                    $res_comp = pg_query($con, $sql_comp);

                    if(pg_num_rows($res_comp) == 0){
                    
                        $sql = "INSERT INTO tbl_defeito_constatado_solucao
                                            (defeito_constatado, solucao, produto, fabrica, solucao_procedimento)
                                    VALUES
                                            ($defeito, $solucao, $produto, $login_fabrica, '{$procedimento}')
                                    RETURNING defeito_constatado_solucao";

                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            $msg_erro["msg"][] = "Ocorreu um erro no cadastro das informações";
                        }

                        $def_cs = pg_fetch_result($res, 0, 'defeito_constatado_solucao');

                        if (strlen($anexo_procedimento) > 0) {
                            $ext = preg_replace("/.+\./", "", $anexo_procedimento);
                            $arquivos[] = array(
                                "file_temp" => $anexo_procedimento,
                                "file_new"  => "{$login_fabrica}_{$def_cs}.{$ext}"
                            );
                        }

                        if (count($arquivos) > 0) {
                            $s3->moveTempToBucket($arquivos, $ano, $mes, false);
                        }

                    } else {

                        $defeitoConstatadoSolucao = pg_fetch_result($res_comp, 0, 'defeito_constatado_solucao');

                        $sql = "UPDATE tbl_defeito_constatado_solucao
                                SET defeito_constatado = {$defeito},
                                    solucao_procedimento = '{$procedimento}'
                                WHERE defeito_constatado_solucao = {$defeitoConstatadoSolucao}
                                ";
                        $res = pg_query($con, $sql);

                    }
                }

            }
        } else {

            $familiaForm = $_POST['familia'];

            $contador_situacao = count($solucao_post);

            for($i = 0; $i < $contador_situacao; $i++){

                $solucao = $solucao_post[$i];

                $arrProd = [];

                foreach ($lista_produtos as $produtoInfo) {

                    $arrProd[] = $produtoInfo[0];

                }

                $sqlVerificaFamilia = "SELECT defeito_constatado_solucao,
                                              campos_adicionais
                                       FROM tbl_defeito_constatado_solucao
                                       WHERE campos_adicionais->>'familia' = '{$familiaForm}'
                                       AND fabrica = {$login_fabrica}
                                       AND solucao = {$solucao}";
                $resVerificaFamilia = pg_query($con, $sqlVerificaFamilia);

                if (pg_num_rows($resVerificaFamilia) > 0) {

                    $defeitoConstatadoSolucao = pg_fetch_result($resVerificaFamilia, 0, "defeito_constatado_solucao");
                    $arrJsonDefeito           = json_decode(pg_fetch_result($resVerificaFamilia, 0, "campos_adicionais"), true);

                    $arrJsonDefeito["familia"]               = $familiaForm;
                    $arrJsonDefeito["produtosDesconsiderar"] = $arrProd;
                    $jsonDefeito = json_encode($arrJsonDefeito);

                    $sql = "UPDATE tbl_defeito_constatado_solucao
                            SET defeito_constatado = {$defeito},
                                solucao = {$solucao},
                                solucao_procedimento = '{$procedimento}',
                                campos_adicionais = '$jsonDefeito'
                            WHERE defeito_constatado_solucao = {$defeitoConstatadoSolucao}
                                ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][] = "Ocorreu um erro no cadastro das informações";
                    }

                } else {

                    $arrJsonDefeito["familia"]               = $familiaForm;
                    $arrJsonDefeito["produtosDesconsiderar"] = $arrProd;
                    $jsonDefeito = json_encode($arrJsonDefeito);

                    $sql = "INSERT INTO tbl_defeito_constatado_solucao (defeito_constatado, solucao, fabrica, solucao_procedimento, campos_adicionais)
                            VALUES ($defeito, $solucao, $login_fabrica, '{$procedimento}', '{$jsonDefeito}') 
                            RETURNING defeito_constatado_solucao";

					//die(nl2br($sql));                            
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][] = "Ocorreu um erro no cadastro das informações";
                    }

                    $defeitoConstatadoSolucao = pg_fetch_result($res, 0, "defeito_constatado_solucao");

                }

                if (strlen($anexo_procedimento) > 0) {
                    $ext = preg_replace("/.+\./", "", $anexo_procedimento);
                    $arquivos[] = array(
                        "file_temp" => $anexo_procedimento,
                        "file_new"  => "{$login_fabrica}_{$defeitoConstatadoSolucao}.{$ext}"
                    );
                }

                if (count($arquivos) > 0) {
                    $s3->moveTempToBucket($arquivos, $ano, $mes, false);
                }

            }

        }

        if (count($msg_erro["msg"]) > 0) {
            pg_query($con, "ROLLBACK");
        } else {
            pg_query($con, "COMMIT");
        }

    }

    if (count($msg_erro['msg']) == 0) {
        $desc_sit = ((strlen($situacao) > 0 && $situacao == "alterar") || $radio_qtde_produtos == 'muitos') ? "alteradas" : "cadastradas";
        unset($produto_referencia,$produto_descricao,$defeito,$solucao_post,$situacao,$garantia, $lista_produtos,$radio_qtde_produtos,$familia,$procedimento, $_POST, $_REQUEST);
        $msg = "Informações $desc_sit com Sucesso";
    }

}

if (isset($_GET["defeito"]) && isset($_GET["solucao"]) && isset($_GET["produto"])) {

    $defeito = $_GET["defeito"];
    $solucao = $_GET["solucao"];
    $produto = $_GET["produto"];

    $solucao_post[] = $solucao;

    $sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
    $res_produto = pg_query($con, $sql_produto);

    $produto_referencia = pg_fetch_result($res_produto, 0, "referencia");
    $produto_descricao = pg_fetch_result($res_produto, 0, "descricao");

    $situacao = "alterar";

} else if (isset($_REQUEST['edita_defeito_familia']) && isset($_REQUEST['id_defeito_solucao'])) {

    $id_defeito_solucao_alterar = $_REQUEST['id_defeito_solucao'];

    $sqlFamilia = "SELECT
                        tbl_defeito_constatado_solucao.defeito_constatado_solucao AS id_defeito_solucao,
                        tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                        tbl_defeito_constatado.descricao AS desc_defeito,
                        tbl_solucao.solucao AS id_solucao,
                        tbl_solucao.descricao AS desc_solucao,
                        tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                        tbl_defeito_constatado_solucao.ativo,
                        tbl_defeito_constatado_solucao.campos_adicionais,
                        tbl_familia.descricao as desc_familia,
                        tbl_familia.familia as id_familia,
                        tbl_defeito_constatado_solucao.ativo
                FROM tbl_defeito_constatado_solucao
                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
                AND tbl_familia.fabrica = {$login_fabrica}
                JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                WHERE tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                AND tbl_defeito_constatado_solucao.campos_adicionais->>'familia' IS NOT NULL
                AND tbl_defeito_constatado_solucao.produto IS NULL
                AND tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$id_defeito_solucao_alterar}
                ORDER BY tbl_defeito_constatado_solucao.defeito_constatado ASC";
    $resFamilia = pg_query($con, $sqlFamilia);

    $familia        = pg_fetch_result($resFamilia, 0, 'id_familia');
    $defeito        = pg_fetch_result($resFamilia, 0, 'defeito_constatado');
    $solucao_post[] = pg_fetch_result($resFamilia, 0, 'id_solucao');
    $procedimento   = pg_fetch_result($resFamilia, 0, 'procedimento');
    $campos_adicionais = json_decode(pg_fetch_result($resFamilia, 0, 'campos_adicionais'), true);

    $produtosId = implode(",",$campos_adicionais["produtosDesconsiderar"]);

    $sqlProdutos = "SELECT tbl_produto.produto,
                           tbl_produto.referencia,
                           tbl_produto.descricao
                    FROM tbl_produto
                    WHERE tbl_produto.produto IN ({$produtosId})
                    AND tbl_produto.fabrica_i = {$login_fabrica}";
    $resProdutos = pg_query($con, $sqlProdutos);

    while ($dadosProd = pg_fetch_object($resProdutos)) {

        $lista_produtos[] = [$dadosProd->produto, $dadosProd->referencia, $dadosProd->descricao];

    }

    $radio_qtde_produtos = "muitos";
    //$situacao = "alterar";

}

if(strlen($msg) > 0){
    $situacao = "";
}

if (!in_array($login_fabrica, array(3))) {
    $layout_menu = "cadastro";
    $admin_privilegios = "cadastro";
}else{
    $layout_menu = "info_tecnica";
    $admin_privilegios = "info_tecnica";
}
$title = 'CAD-6310 : ' . getValorFabrica([
    "CADASTRO DE DEFEITOS / SOLUÇÕES",
    158 => "CADASTRO FAMÍLIA/DEFEITO/SOLUÇÃO"
]);

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect",
    "tooltip",
    "ajaxform"
);

include("plugin_loader.php"); ?>
<script type='text/javascript' src='js/fckeditor/fckeditor.js'></script>
<script type="text/javascript">

$(function() {

    $(document).delegate('#procedimento', 'keydown', function(e) {
      var keyCode = e.keyCode || e.which;

      if (keyCode == 9) {
        e.preventDefault();
        var start = this.selectionStart;
        var end = this.selectionEnd;

        // set textarea value to: text before caret + tab + text after caret
        $(this).val($(this).val().substring(0, start)
                    + "\t"
                    + $(this).val().substring(end));

        // put caret at right position again
        this.selectionStart =
        this.selectionEnd = start + 1;
      }
    });

    /**
    * Eventos para anexar/excluir imagem
    */
    $("button.btn_acao_anexo").click(function(){
        var name = $(this).attr("name");
        
        if (name == "anexar") {
            $(this).trigger("anexar_s3", [$(this)]);
        }else{
            $(this).trigger("excluir_s3", [$(this)]);
        }
    });

    //ativa o anexar
    $("button.btn_acao_anexo").bind("anexar_s3",function(){
        var button = $(this);
        $("input[name=anexo_upload]").click();
    });
    
    //ativa o excluir
    $("button.btn_acao_anexo").bind("excluir_s3",function(){

        var button = $(this);
        var nome_an_p = $("input[name='anexo']").val();
        // alert(nome_an_p);
        // return;
        $.ajax({            
            url: "cadastro_defeitos_solucoes.php",
            type: "POST",
            data: { ajax_anexo_exclui: true, 
                    anexo_nome_excluir: nome_an_p
            },
            beforeSend: function() {
                $("#div_anexo").find("button").hide();
                $("#div_anexo").find("img.anexo_thumb").hide();
                $("#div_anexo").find("img.anexo_loading").show();
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $("#div_anexo").find("a[target='_blank']").remove();
                    $("#baixar").remove();
                    $(button).text("Anexar").attr({
                        id:"anexar",
                        class:"btn btn-mini btn-primary btn-block",
                        name: "anexar"
                    });
                    $("input[name='anexo']").val("f");              
                    $("#div_anexo").prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                    $("#div_anexo").find("img.anexo_loading").hide();
                    $("#div_anexo").find("button").show();
                    $("#div_anexo").find("img.anexo_thumb").show();
                }
            }
        });
    });

    /**
    * Eventos para anexar imagem
    */
    $("form[name=form_anexo]").ajaxForm({
        complete: function(data) {
            data = $.parseJSON(data.responseText);

            if (data.error) {
                alert(data.error);
            } else {
                var imagem = $("#div_anexo").find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

                $("#div_anexo").find("img.anexo_thumb").remove();

                var link = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(link).html(imagem);

                $("#div_anexo").prepend(link);                                      

                $("#div_anexo").find("input[rel=anexo]").val(data.arquivo_nome);
            }

            $("#div_anexo").find("img.anexo_loading").hide();
            $("#div_anexo").find("button").show();
            $("#div_anexo").find("img.anexo_thumb").show();
        }
    });

    $("input[name^=anexo_upload]").change(function() {
        //var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

        $("#div_anexo").find("button").hide();
        $("#div_anexo").find("img.anexo_thumb").hide();
        $("#div_anexo").find("img.anexo_loading").show();

        $(this).parent("form").submit();
    });

    $(document).on('click','.btn-open-shawdowbox', function(){
        var width = $(this).data('width');
        var height = $(this).data('height');

        Shadowbox.open({
            content: $(this).data('url')+'?'+$(this).data('parametros'),
            player: 'iframe',
            width: width,
            height: height
        });
    });

    $('#listar_tudo').on('click', function(){
        var tipo = $('input[name=tipo]:checked').val();        
        var link = window.location.pathname + '?listar_tudo=1';

        if (tipo == '272') {
            link += '&atendimento=piso';
        }
        window.location.href = link;
    });

    $('input[name=tipo]').on('change', function(){
        if ($(this).val() == '272') {
            $('#garantia').hide();
        }else{
            $('#garantia').show();
        }
    });

    $.autocompleteLoad(Array("produto"),["produto"]);

    Shadowbox.init();

    <?php
    if (in_array($login_fabrica, [3]) && $situacao != "alterar") { ?>
        var parametros_lupa = ["posicao","familia"];
    <?php
    }
    ?>

    $("span[rel=lupa]").click(function () {
        $.lupa($(this), parametros_lupa);
    });

    $("#solucao").multiselect({
        selectedText: "# de # opções"
    });

    <? if ($login_fabrica == 52) { ?>

        $("#produto_grupo_defeito_constatado").change(function(){

            var defeito_constatado_grupo = $(this).val();
            var familia = $("#familia").val();

            if(familia == ""){
                alert("Por favor, selecione a Família");
                $("#familia").focus();
                return;
            }

            $.ajax({
                url: "<?= $_SERVER['PHP_SELF']; ?>",
                type: "post",
                data: {
                    defeito_constatado_grupo : defeito_constatado_grupo,
                    familia : familia,
                    ajax_defeito_constatado_grupo : true
                }
            }).always(function(data){

                data = JSON.parse(data);

                $("#defeito").html("<option value=''></option>");

                $.each(data.retorno, function(key, value){

                    var option = "<option value='"+value.defeito_constatado+"'>"+value.descricao+"</option>";

                    $("#defeito").append(option);

                });
            });

        });

    <? } ?>

    $(document).on("click", ".status", function() {

        var id_defeito_solucao = $(this).attr('rel');
        var that = $(this);

        var btn_acao = $(this).text().toLowerCase();

        <? if (in_array($login_fabrica, array(158))) { ?>
                var r = confirm("Deseja realmente "+$(this).text()+" esse registro?");
        <? } else { ?>
                var r = confirm("Deseja realmente Excluir esse registro?");
        <? } ?>
        if(r == false) {
                return;
        }

        $.ajax({
            url : "<?= $PHP_SELF; ?>",
            type : "POST",
            data : {
                del_procedimento : true,
                id_defeito_solucao : id_defeito_solucao,
                btn_acao : btn_acao
            },
            complete: function(data){

                data = $.parseJSON(data.responseText);

                if(data.status == true){
                    <? if (in_array($login_fabrica, array(158))) { ?>
                        if (btn_acao == 'ativar') {
                            $(that).removeClass("btn-success").addClass("btn-danger");
                            $(that).text("Inativar");
                            $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
                        } else {
                            $(that).removeClass("btn-danger").addClass("btn-success");
                            $(that).text("Ativar");
                            $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
                        }
                    <? } else { ?>
                            $("#box_"+id_defeito_solucao).remove();
                    <? } ?>
                }else{
                    alert(data.descricao);
                }
            }
        });
    });
    <?php
    if (in_array($login_fabrica, [3]) && $situacao != "alterar") {
    ?>  

        $("#solucao").multiselect({
           selectedText: "# de # opções"
        });

        atualiza_combos();

        $("#btn_gravar").click(function(){

            selIt();
            $("form[name=frm_relatorio]").submit();

        });

        $("#familia").change(function(){
            
            if ($(this).val() != "") {

                $(this).prop("readonly",true);
                $(this).find("option:not(:selected)").remove();

                $("#um_varios").show("fast");

            } else {
                $("#um_varios").hide("fast");
            }

            $(".lupa_config_produto").attr("familia", $(this).val());
            
        });

        $("#familia").change();

        $("#defeito").change(function(){

            let selected_option = $("input[name=multiselect_solucao]:checked").length;
            
            if ($("#defeito").val() == "" || selected_option == 0) {
                $("#div_procedimento").hide();
            } else {
                $("#div_procedimento").show();
            }

        });

        $("#defeito").change();

        $(document).on("click","input[name=multiselect_solucao]",function(){

            let selected_option = $("input[name=multiselect_solucao]:checked").length;
            
            if ($("#defeito").val() == "" || selected_option == 0) {
                $("#div_procedimento").hide();
            } else {
                $("#div_procedimento").show();
            }

        });

        let selected_option = $("input[name=multiselect_solucao]:checked").length;
            
        if ($("#defeito").val() == "" || selected_option == 0) {
            $("#div_procedimento").hide();
        } else {
            $("#div_procedimento").show();
        }

        $("input[name=radio_qtde_produtos]").click(function(){
            $("#id_multi").show();

            if ($(this).val() == "um") {
                $(".frase_selecione").html("(Selecione o produto e clique em adicionar)");
                $(".label_desconsiderar").hide();
            } else {
                $(".frase_selecione").html("<div class='alert alert-info'><strong>ATENÇÃO:</strong> Você deve selecionar apenas os produtos da família selecionada que NÃO irão entrar na regra. Caso não selecionado nenhum produto, a regra será aplicada para todos da família selecionada.</div>");
                $(".label_desconsiderar").show();
                pesquisa_defeito();
                pesquisa_solucao();
            }

        });

        $("input[name=radio_qtde_produtos]:checked").click();

    <?php
    }
    ?>

});

///////////////////////////////////////////////////////////

var singleSelect = true;  // Allows an item to be selected once only
var sortSelect = true;  // Only effective if above flag set to true
var sortPick = false;  // Will order the picklist in sort sequence

// Initialise - invoked on load
function initIt() {
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
}

// Adds a selected item into the picklist
function addIt() {

    if ($('#produto_referencia_multi').val()=='')
        return false;

    if ($('#produto_descricao_multi').val()=='')
        return false;

    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;

    pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
    pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

    $('#produto_referencia_multi').val("");
    $('#produto_descricao_multi').val("");

    if (sortPick) {
        var tempText;
        var tempValue;
        // Sort the pick list
        while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
            tempText = pickOptions[pickOLength-1].text;
            tempValue = pickOptions[pickOLength-1].value;
            pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
            pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
            pickOptions[pickOLength].text = tempText;
            pickOptions[pickOLength].value = tempValue;
            pickOLength = pickOLength - 1;
        }
    }

    pickOLength = pickOptions.length;
    $('#produto_referencia_multi').focus();

    <?php
    if (in_array($login_fabrica, [3]) && $situacao != "alterar") { ?>
        atualiza_combos();
    <?php
    }
    ?>

}

function atualiza_combos() {
    let produtos = $("#PickList > option").map(function(){
        return $(this).val();
    }).get();

    pesquisa_defeito(produtos);
    pesquisa_solucao(produtos);
}

 /*--------------------------------------*/
// Deletes an item from the picklist
function delIt() {
  
  var fabrica = <?=$login_fabrica?>;

  var pickList = document.getElementById("PickList");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    pickOptions[pickIndex] = null;
    pickIndex = pickList.selectedIndex;
  }

    <?php
    if (in_array($login_fabrica, [3]) && $situacao != "alterar") { ?>

        atualiza_combos();

    <?php
    }
    ?>
}

function delItPeca() {
  var pickList = document.getElementById("PickListPeca");
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
    if (pickList == null) return true;
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;
/*  if (pickOLength < 1) {
        alert("Nenhuma produto selecionado!");
        return false;
    }*/
    for (var i = 0; i < pickOLength; i++) {
        pickOptions[i].selected = true;
    }
/*  return true;*/
}


function ativa_procedimento(id_defeito_solucao){
    $.ajax({
        url : "<?php echo $PHP_SELF; ?>",
        type : "POST",
        data : {
            ativaprocedimento : true,
            id_defeito_solucao : id_defeito_solucao
        },
        complete: function(data){
            //data = data.responseText;
            data = $.parseJSON(data.responseText);

            if(data.status == true){
                $("#btn_ativar_"+id_defeito_solucao).attr('disabled', true);
                $("#btn_inativar_"+id_defeito_solucao).removeClass('disabled');
            }else{
                alert(data.descricao);
            }


        }
    });
}

function inativa_procedimento(id_defeito_solucao){
    $.ajax({
        url : "<?php echo $PHP_SELF; ?>",
        type : "POST",
        data : {
            inativaprocedimento : true,
            id_defeito_solucao : id_defeito_solucao
        },
        complete: function(data){
            //data = data.responseText;
            data = $.parseJSON(data.responseText);

            if(data.status == true){
                $("#btn_ativar_"+id_defeito_solucao).removeClass('disabled');
                $("#btn_inativar_"+id_defeito_solucao).attr('disabled', true);
            }else{
                alert(data.descricao);
            }


        }
    });
}

function exclui_defeito_solucao_familia(id_defeito_solucao) {
    $.ajax({
        url : window.location,
        type : "POST",
        data : {
            excluidefeitosolucao : true,
            id_defeito_solucao : id_defeito_solucao
        },
        complete: function(data){
            //data = data.responseText;
            data = $.parseJSON(data.responseText);

            if(data.status == true){
                $("#btn_excluir_"+id_defeito_solucao).closest('tr').hide("fast");
            }else{
                alert(data.descricao);
            }

        }
    });
}

// function atualizaCombo(combo){
//     $.ajax({
//         url : "<?php echo $PHP_SELF; ?>",
//         type : "POST",
//         data : {
//             atualizacombo : true,
//             combo : combo
//         },
//         complete: function(data){

//             data = data.responseText;
//             if (combo == 'defeito'){
//                 if(data == ""){
//                     $("#defeito").html("");
//                     $("#defeito").html("Defeitos não cadastrados para esse produto.");
//                 }else{
//                     $("#defeito").html("");
//                     $("#defeito").html(data);
//                 }
//             }
//             if (combo == 'solucao') {
//                 if(data == ""){
//                     $("#solucao_div").html("");
//                     $("#solucao_div").html("Solução não cadastrados para esse produto.");
//                     $("#solucao_div").html("");
//                 }else{
//                     $("#solucao_div").html("");
//                     $("#solucao_div").html(data);
//                     $("#solucao").multiselect({
//                        selectedText: "# de # opções"
//                     });
//                 }

//             }
//         }
//     });
// }

<?php
if ($login_fabrica == 3) { ?>
function retorna_produto (retorno) {
    <?php
    if ($situacao != "alterar") {
    ?>
        if(retorno.posicao == "pesquisa"){

            $("#psq_produto_referencia").val(retorno.referencia);
            $("#psq_produto_nome").val(retorno.descricao);

        }else if(retorno.posicao == "um_produto"){

            $("#produto_referencia").val(retorno.referencia);
            $("#produto_descricao").val(retorno.descricao);

        }else if(retorno.posicao == "multi_produto"){

            $("#produto_referencia_multi").val(retorno.referencia);
            $("#produto_descricao_multi").val(retorno.descricao);

        }
    <?php
    } else { ?>
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
    <?php
    }
    ?>

}

function pesquisa_defeito(produto = null){

    let defeito = $("#defeito option:selected").val();
    
    $.ajax({
        url : "<?php echo $PHP_SELF; ?>",
        type : "POST",
        data : {
            atualizacombodefeito : true,
            produto : produto,
            defeito: defeito,
            todosProdutos: $("#radio_qtde_produtos2").is(":checked"),
            familia: $("#familia").val()
        },
        complete: function(data){
            data = data.responseText;
            if(data == ""){
                $("#defeito").html("");
                $("#defeito").html("Defeitos não cadastrados para esse produto.");
            }else{
                $("#defeito").html("");
                $("#defeito").html(data);
            }
        }
    });
}

function pesquisa_solucao(produto = null){
    if($("#produto_id").val() !== ''){

        let solucoes = $("#solucao option:selected").map(function(){
            return $(this).val();
        }).get();

        $.ajax({
            url : "<?php echo $PHP_SELF; ?>",
            type : "POST",
            data : {
                atualizacombosolucao : true,
                produto : produto,
                solucoes: solucoes,
                todosProdutos: $("#radio_qtde_produtos2").is(":checked"),
                familia: $("#familia").val()
            },
            complete: function(data){
                data = data.responseText;
                if(data == " "){
                    //$("#solucao_div").html("");
                    //alert('Solução não cadastrados para esse produto 1 !');
                    //$("#solucao_div").html("Solução não cadastrados para esse produto.");
                    //$("#solucao_div").html("");
                }else{
                    $("#solucao_div").html("");
                    $("#solucao_div").html(data);
                    $("#solucao").multiselect({
                        selectedText: "# de # opções"
                    });
                }
            }
        });
    }
}

function relacionamento_diagnostico_ajaxx(tipo){
    var produto = $("#produto_id").val();
    //var defeito = $("#defeito").val();
    var html = '';

    <?php
    if (in_array($login_fabrica, [3]) && $situacao != "alterar") { ?>
        if ($("#radio_qtde_produtos1").is(":checked")) {
            var produto = "";
            var multiplosProd = "true";

            $("#PickList > option").each(function(){
                produto += $(this).val()+",";
            });
        }
    <?php
    }
    ?>

    var todosProdutos = ($("#radio_qtde_produtos2").is(":checked")) ? "sim" : "nao";
    let familia       = $("#familia").val();

    // if (defeito != '') {
    //     html = "relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo="+tipo+"&grupo=HD&produto="+produto+"&defeito_constatado="+defeito;
    // } else {
        html = "relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo="+tipo+"&grupo=HD&produto="+produto+"&multiplos="+multiplosProd+"&todos_produtos="+todosProdutos+"&familia_selecionada="+familia;
    // }

    Shadowbox.open({
        content: html,
        player: "iframe",
        width: 900,
        height: 450,
        options: {
            modal: true,
            enableKeys: false
        }
    });
}

<?php
} else { ?>
function retorna_produto (retorno){
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

<?php
}
?>

function close(){
    Shadowbox.close();
}

function insere_procedimento(defeito, solucao, produto){

    $.ajax({
        url : "<?= $PHP_SELF; ?>",
        type : "POST",
        data : {
            get_procedimento : true,
            defeito : defeito,
            solucao : solucao,
            produto : produto
        },
        complete: function(data){

            var procedimento = data.responseText;

            if(procedimento != ""){

                $("#box_procedimento_"+defeito+"_"+solucao).html(procedimento);
                $("#box_procedimento_"+defeito+"_"+solucao).removeClass("tac");

                Shadowbox.setup();

            }
        }
    });
}

function deleta_procedimento(id_defeito_solucao){

    var r = confirm("Deseja realmente Excluir esse registro?");
    if(r == false) {
        return;
    }

    $.ajax({
        url : "<?php echo $PHP_SELF; ?>",
        type : "POST",
        data : {
            del_procedimento : true,
            id_defeito_solucao : id_defeito_solucao
        },
        complete: function(data){

            data = $.parseJSON(data.responseText);

            if(data.status == true){
                $("#box_"+id_defeito_solucao).remove();
            }else{
                alert(data.descricao);
            }
        }
    });
}

</script>

<style>
    .ui-multiselect{
        width: 300px !important;
    }
</style>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? }
if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
    <div class="alert alert-success">
        <h4><?= $msg; ?></h4>
    </div>
<? } ?>

<?php //conflito da qui para baixo ?>
<div class="row">
    <strong class="obrigatorio pull-right">* Campos obrigatórios</strong>
</div>

<!-- Form -->
<form name="frm_relatorio" method="POST" action="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Cadastro</div>
    <br />
    <input type="hidden" name="situacao" id="situacao" value="<?php echo $situacao; ?>" />
    <input type="hidden" name="edita_defeito_familia" value="<?= $_REQUEST["edita_defeito_familia"] ?>" />
    <input type="hidden" name="id_defeito_solucao" value="<?= $_REQUEST["id_defeito_solucao"] ?>" />
    <?php

    if (!in_array($login_fabrica, [3]) || (in_array($login_fabrica, [3]) && $situacao == "alterar")) {

        if ($login_fabrica == 52) {?>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='produto_referencia'>Família</label>
                        <div class='controls controls-row'>
                            <div class='span9 input-append'>
                                <select id="familia" name="familia" class="span12" >
                                    <option value="">Selecione</option>
                                    <?php
                                    $sql = "SELECT familia, descricao
                                              FROM tbl_familia
                                             WHERE fabrica = {$login_fabrica}
                                               AND ativo IS TRUE";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected = ($result->familia == $familia) ? "selected" : "";
                                            echo "<option value='{$result->familia}' {$selected} >{$result->descricao}</option>";
                                        }
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span5'>
                    <div class='control-group <?=(in_array("defeito_constatado_grupo", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='produto_referencia'>Grupo de Defeitos Constatados</label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <select id="produto_grupo_defeito_constatado" name="defeito_constatado_grupo" class="span12" >
                                    <option value="">Selecione</option>
                                    <?php
                                    $sql = "SELECT
                                                defeito_constatado_grupo,
                                                grupo_codigo,
                                                descricao
                                            FROM tbl_defeito_constatado_grupo
    										WHERE fabrica = $login_fabrica
                                            ORDER BY descricao ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected = ($result->defeito_constatado_grupo == $defeito_constatado_grupo) ? "selected" : "";
                                            echo "<option value='{$result->defeito_constatado_grupo}' {$selected} >{$result->descricao}</option>";
                                        }
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
        <? } else {
            if (!in_array($login_fabrica, array(158))) { ?>
                <!-- Cód Produto / Nome produto -->
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
                                                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?= $produto_descricao; ?>" >
                                                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                                                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                                                </div>
                                        </div>
                                </div>
                        </div>
                        <div class='span2'></div>
                </div>
                <input type="hidden" name="produto_id" id="produto_id" value="<?=$produto?>">
            <?php
            }
        }
    } else { ?>

        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span5'>
                <div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_referencia'>Família</label>
                    <div class='controls controls-row'>
                        <div class='span9 input-append'>
                            <select id="familia" name="familia" class="span12" >
                                <option value="">Selecione</option>
                                <?php
                                $sql = "SELECT familia, descricao
                                          FROM tbl_familia
                                         WHERE fabrica = {$login_fabrica}
                                           AND ativo IS TRUE";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    while ($result = pg_fetch_object($res)) {
                                        $selected = ($result->familia == $familia) ? "selected" : "";
                                        echo "<option value='{$result->familia}' {$selected} >{$result->descricao}</option>";
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid" id="um_varios" hidden>
            <div class="span2"></div>
            <?php
            if (!isset($_REQUEST["edita_defeito_familia"])) {
            ?>
                <div class="span4">

                    <div class='control-group'>

                        <label class='control-label' for='codigo_posto'>
                            <?php echo traduz("Cadastro para:"); ?>
                            <i id="btnPopover2" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Informação" data-content="<?php echo $titulo_produto; ?>" class="icon-question-sign"></i>
                        </label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <label class="checkbox">
                                    <input type="radio" name="radio_qtde_produtos" id="radio_qtde_produtos1" value='um' <?= ($radio_qtde_produtos == 'um') ? "checked" : "" ?> />
                                    <?php echo traduz("Um ou mais produtos"); ?>
                                </label>
                            </div>

                        </div>

                    </div>

                </div>
            <?php
            }
            ?>
            <div class="span4">

                <div class='control-group'>

                    <label class='control-label' for='codigo_posto'>&nbsp;</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <label class="checkbox">
                                <input type="radio" name="radio_qtde_produtos" id="radio_qtde_produtos2" value='muitos' <?= ($radio_qtde_produtos == 'muitos') ? "checked" : "" ?> />
                                <?php echo traduz("Todos os Produtos"); ?>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div id='id_multi' hidden>

            <div class='row-fluid'>

                <div class='span2'></div>

                <div class='span2'>
                    <div class='control-group'>
                        <label class='control-label' for='produto_referencia_multi'><?php echo traduz("Ref. Produto"); ?></label>
                        <div class='controls controls-row'>
                            <div class='span10 input-append'>
                                <input type="text" id="produto_referencia_multi" name="produto_referencia_multi" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="multi_produto" familia="" class="lupa_config_produto" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group'>
                        <label class='control-label' for='produto_descricao_multi'><?php echo traduz("Descrição Produto"); ?></label>
                        <div class='controls controls-row'>
                            <div class='span11 input-append'>
                                <input type="text" id="produto_descricao_multi" name="produto_descricao_multi" class='span12' value="<? echo $produto_descricao ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="multi_produto" familia="" class="lupa_config_produto" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class='span2'>
                    <label>&nbsp;</label>
                    <input type='button' name='adicionar' id='adicionar' value='Adicionar' class='btn btn-success' onclick='addIt();' style="width: 100%;">
                </div>

                <div class='span2'></div>

            </div>

            <p class="tac frase_selecione">
                <?php echo traduz("(Selecione o produto e clique em <strong>Adicionar</strong>)"); ?>
            </p>

            <div class='row-fluid'>

                <div class='span2'></div>

                <div class='span8'>
                    <label class="label_desconsiderar" hidden>Produtos desconsiderados</label>
                    <select multiple id="PickList" name="PickList[]" class='span12'>

                    <?php
                        if (count($lista_produtos)>0){
                            for ($i=0; $i<count($lista_produtos); $i++){
                                $linha_prod = $lista_produtos[$i];
                                echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
                            }
                        }
                    ?>

                    </select>

                    <p class="tac">
                        <input type="button" value="Remover" onclick="delIt();" class='btn btn-danger' style="width: 126px;">
                    </p>

                </div>

                <div class='span2'></div>

            </div>

        </div>

    <?php
    }

    // Garantia/Fora Garantia/Família
    if (in_array($login_fabrica, array(158))) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='familia'>Família</label>
                    <div class='controls controls-row'>
                        <div class='span11'>
                            <h5 class='asteristico'>*</h5>
                            <select name="familia" id="familia" class='span4'>
                                <option value="">Selecione</option>
                                <?
                                $sql ="SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo = 't' ORDER BY descricao;";
                                $res = pg_query($con,$sql);
                                for ($y = 0 ; $y < pg_numrows($res) ; $y++){
                                    $familia            = trim(pg_result($res,$y,familia));
                                    $descricao          = trim(pg_result($res,$y,descricao));
                                    echo "<option value='$familia'";
                                        if ($familia == $aux_familia) echo " SELECTED ";
                                    echo ">$descricao</option>";
                                }
                                ?>

                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group'>
                    <div class='control-label'>Tipo</div>
                    <label class="radio inline" for="tipoGar">
                        <input id="tipoGar" type="radio" name="tipo" value="273" <?=(empty($tipo) || $tipo == 273) ? 'checked' : ''?>>
                        Garantia</label>
                    <label class="radio inline" for="tipoPiso">
                        <input id="tipoPiso" type="radio" name="tipo" value="272" <?=($tipo == 272) ? 'checked' : ''?>>
                        Atendimento Tipo Piso</label>
                </div>
            </div>
            <div class="span5">
                <div class="control-group">
                    <label class="control-label" for="garantia">Garantia</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?php $hidden = ($tipo == 272) ? "style='display: none;'" : '' ?>
                            <select name='garantia' id='garantia' class='span5' <?=$hidden?>>
                                <option value="">Selecione</option>
                                <option value="t">Sim</option>
                                <option value="f">Não</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <? } ?>
    <!-- Defeito / Solução -->
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("defeito", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='defeito'>Defeito</label>
                <div class='controls controls-row'>
                    <div class='span11'>
                        <h5 class='asteristico'>*</h5>
                        <select name="defeito" id="defeito">
                            <?php if ($login_fabrica == 52) {
                                ?>
                                <option value="">Selecione</option>
                                <?php
                                if(strlen($familia) > 0 && strlen($defeito_constatado_grupo) > 0){
                                    $sql = "SELECT DISTINCT
                                                    tbl_defeito_constatado.defeito_constatado,
                                                    tbl_defeito_constatado.descricao
                                            FROM tbl_defeito_constatado
                                            JOIN tbl_diagnostico USING(fabrica,defeito_constatado)
                                            JOIN tbl_posto_fabrica USING(tabela_mao_obra)
                                            WHERE tbl_defeito_constatado.defeito_constatado_grupo = $defeito_constatado_grupo
                                            AND tbl_defeito_constatado.ativo
                                            AND tbl_diagnostico.ativo
                                            AND tbl_posto_fabrica.fabrica = $login_fabrica
                                            AND tbl_diagnostico.familia = $familia";                                    
                                    $res = pg_query($con, $sql);

                                    $contador_res = pg_num_rows($res);

                                    if($contador_res > 0){
                                        for($i = 0; $i < $contador_res; $i++){
                                            $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
                                            $descricao          = pg_fetch_result($res, $i, "descricao");
                                            $selected = ($defeito_constatado == $defeito) ? "SELECTED" : "";
                                            echo "<option value='".$defeito_constatado."' {$selected}>".$descricao."</option>";
                                        }
                                    }
                                }
                                //inicio login fabrica 3
                            }elseif ($login_fabrica == 3) {

                                if (!empty($produto) || count($lista_produtos) > 0 || isset($_REQUEST['edita_defeito_familia'])) {

                                    if (!isset($_REQUEST['edita_defeito_familia'])) {

                                        if (count($lista_produtos) > 0) {

                                            foreach ($lista_produtos as $dados) {
                                                $arrDefProd[] = $dados[0];
                                            }

                                            $condDef = "AND tbl_produto_defeito_constatado.produto IN (".implode(",", $arrDefProd).")";

                                        } else {

                                            $condDef = "AND tbl_produto_defeito_constatado.produto = {$produto}";

                                        }

                                    } else {

                                        $condDef = "AND tbl_produto.familia = {$familia}";

                                    }

                                    $sqlx = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao) tbl_defeito_constatado.defeito_constatado,
                                                    tbl_defeito_constatado.codigo,
                                                    tbl_defeito_constatado.descricao ,
                                                    tbl_defeito_constatado.ativo
                                            FROM tbl_defeito_constatado
                                                JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                                                JOIN tbl_produto_defeito_constatado USING(defeito_constatado)
                                            LEFT JOIN tbl_produto ON tbl_produto_defeito_constatado.produto = tbl_produto.produto
                                            WHERE tbl_defeito_constatado.fabrica = $login_fabrica
                                            AND tbl_defeito_constatado_grupo.descricao = 'HD'
                                            {$condDef}
                                            ORDER BY descricao";
                                    $resx = pg_exec($con,$sqlx);

                                    if (pg_num_rows($resx) == 0 && !empty($defeito) && $situacao == "alterar") {
                                        $sqlx = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao) tbl_defeito_constatado.defeito_constatado,
                                                    tbl_defeito_constatado.codigo,
                                                    tbl_defeito_constatado.descricao ,
                                                    tbl_defeito_constatado.ativo
                                            FROM tbl_defeito_constatado
                                            JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
                                            JOIN tbl_produto_defeito_constatado USING(defeito_constatado)
                                            WHERE tbl_defeito_constatado.fabrica = $login_fabrica
                                            AND tbl_defeito_constatado_grupo.descricao = 'HD'
                                            AND tbl_defeito_constatado.defeito_constatado = {$defeito}
                                            ORDER BY descricao";
                                        $resx = pg_exec($con,$sqlx);
                                    }

                                    foreach (pg_fetch_all($resx) as $key) {
                                        $selected_defeito = (isset($defeito) and ($key['defeito_constatado'] == $defeito)) ? "SELECTED" : '' ;
                                        $key['codigo'] = (strlen($key['codigo']) > 0) ? $key['codigo']." - " : "";
                                        ?>
                                        <option value="<?php echo $key['defeito_constatado']?>" <?php echo $selected_defeito; ?> >
                                        <?php echo $key['codigo'] . $key['descricao']; ?>
                                        </option>
                                    <?php
                                    }
                                }

                                //fim login fabrica 3
                            } else {
                                ?>
                                <option value="">Selecione</option>
                                <?php
                                $sqlx = "SELECT defeito_constatado, codigo, descricao
                                            FROM tbl_defeito_constatado
                                            WHERE fabrica = {$login_fabrica} AND ativo = 't'
                                            ORDER BY descricao";

                                $resx = pg_query($con,$sqlx);

                                foreach (pg_fetch_all($resx) as $key) {
                                    $selected_defeito = (isset($defeito) and ($key['defeito_constatado'] == $defeito)) ? "SELECTED" : '' ;
                                    $key['codigo'] = (strlen($key['codigo']) > 0) ? $key['codigo']." - " : ""; ?>
                                    <option value="<?= $key['defeito_constatado']?>" <?= $selected_defeito; ?> >
                                        <?= $key['codigo'] . $key['descricao']; ?>
                                    </option>
                                <?php
                                }
                            } ?>
                        </select>
                        <?php
                        if($login_fabrica == 3){ ?>
                            <!-- // verificar se é necessário inicio -->
                            <a href="javascript: relacionamento_diagnostico_ajaxx('defeito_constatado');" class='btn btn-mini btn-info' style='margin: 0 auto !important; margin-top: 5px !important;'>Inserir/Alterar</a>
                            <!-- <a href="javascript:atualizaCombo('defeito');" class='btn btn-mini btn-primary' >Atualizar</a> -->
                            <!-- // verificar se é necessário fim -->
                        <?}?>

                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("solucao", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='solucao'>Solução</label>
                <div class='controls controls-row'>
                    <div class='span11' id='solucao_div'>
                       <h5 class='asteristico'>*</h5>
                            <?php
                            if ($login_fabrica == 3 ) { ?>
                                <select name="solucao[]" id="solucao" multiple="multiple">
                                <?php
                                if (!empty($produto) || count($lista_produtos) > 0 || isset($_REQUEST['edita_defeito_familia'])) {
                                    $where_cod = " ";

                                    if (!isset($_REQUEST['edita_defeito_familia'])) {
                                        if (count($lista_produtos) > 0) {

                                            foreach ($lista_produtos as $dados) {
                                                $arrSolProd[] = $dados[0];
                                            }

                                            $condSol = "AND tbl_defeito_constatado_solucao.produto IN (".implode(",", $arrSolProd).")";

                                        } else {

                                            $condSol = "AND tbl_defeito_constatado_solucao.produto = {$produto}";

                                        }
                                    } else {

                                        $condSol = "AND tbl_produto.familia = {$familia}";

                                    }

                                    $sql = "SELECT  tbl_solucao.solucao,
                                                    tbl_solucao.codigo,
                                                    tbl_solucao.descricao,
                                                    tbl_solucao.ativo,
                                                    tbl_solucao.troca_peca
                                                FROM tbl_solucao
                                                JOIN tbl_defeito_constatado_solucao USING (solucao)
                                                LEFT JOIN tbl_produto ON tbl_defeito_constatado_solucao.produto = tbl_produto.produto
                                                WHERE tbl_solucao.fabrica = $login_fabrica
                                                    AND tbl_solucao.codigo = 'HD'
                                                    AND tbl_defeito_constatado_solucao.defeito_constatado is null
                                                    {$condSol}
                                                ORDER BY descricao";
                                    $res = pg_exec($con,$sql);

                                    if (pg_num_rows($res) == 0 && !empty($solucao) && $situacao == "alterar") {

                                        $sql = "SELECT DISTINCT ON (tbl_solucao.descricao) 
                                                    tbl_solucao.solucao,
                                                    tbl_solucao.codigo,
                                                    tbl_solucao.descricao,
                                                    tbl_solucao.ativo,
                                                    tbl_solucao.troca_peca
                                                FROM tbl_solucao
                                                JOIN tbl_defeito_constatado_solucao USING (solucao)
                                                WHERE tbl_solucao.fabrica = $login_fabrica
                                                    AND tbl_solucao.codigo = 'HD'
                                                    AND tbl_defeito_constatado_solucao.defeito_constatado is null
                                                    AND tbl_defeito_constatado_solucao.solucao = {$solucao}
                                                ORDER BY descricao";
                                        $res = pg_exec($con,$sql);

                                    }

                                    foreach (pg_fetch_all($res) as $key) {
                                        $selected_solucao = (isset($solucao_post) and (in_array($key['solucao'], $solucao_post))) ? "SELECTED" : '' ;?>
                                        <option value="<?= $key['solucao']?>" <?= $selected_solucao ?> >
                                        <?= $key['solucao']." - ".$key['descricao']; ?>
                                        </option>
                                    <?php
                                    }
                                }
                                ?>
                                </select>
                                <?php
                            } else { ?>
                                <select name="solucao[]" id="solucao" multiple="multiple">
                                <?php
                                $sql = "SELECT solucao, codigo, descricao FROM tbl_solucao WHERE fabrica = $login_fabrica AND ativo = 't' ORDER BY descricao ASC";
                                $res = pg_exec($con,$sql);

                                foreach (pg_fetch_all($res) as $key) {
                                    $selected_solucao = (isset($solucao_post) and (in_array($key['solucao'], $solucao_post))) ? "SELECTED" : '' ;?>
                                    <option value="<?= $key['solucao']?>" <?= $selected_solucao ?> >
                                    <?= $key['codigo']." - ".$key['descricao']; ?>
                                    </option>
                                <?php
                                }
                                ?>
                                </select>
                                <?php
                            }?>
                    </div>
                    <?php
                    if ($login_fabrica == 3) {?>
                        <?php // verificar se é necessário inicio ?>
                        <a href="javascript: relacionamento_diagnostico_ajaxx('solucao');" class='btn btn-mini btn-info' style='margin: 0 auto !important; margin-top: 5px !important;'>Inserir/Alterar</a>
                        <!-- <a href="javascript:atualizaCombo('solucao');" class='btn btn-mini btn-primary' >Atualizar</a> -->
                        <?php // verificar se é necessário fim ?>
                    <?php
                    }
                    ?>

                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <!-- Botão Action -->
    <br />
    <?php
    if (in_array($login_fabrica, [3])) { ?>
        <div id="div_procedimento" hidden>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span8">
                    Descrição para a solução <br />
                    <textarea tabindex="-1" name="procedimento" id="procedimento" rows="4" class="span12"><?php echo $procedimento; ?></textarea>
                </div>
            </div>
            <br />
            <!-- ANexo -->
            <div id="div_anexos" class="row-fluid">
                <div class="span2"></div>
                <div class="span8 tac">
                    <input type='hidden' name='anexo_chave' value='<?=$anexo_chave?>' />
                <?php
                    unset($anexo_link);

                    $anexo_imagem = "imagens/imagem_upload.png";
                    $anexo_s3     = false;
                    $anexo        = "";

                    if(strlen($id_defeito_solucao_alterar) > 0) {
                        $anexos = $s3->getObjectList("{$login_fabrica}_{$id_defeito_solucao_alterar}", false, $ano, $mes);
                           
                        if (count($anexos) > 0) {
                            $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                            if ($ext == "pdf") {
                                $anexo_imagem = "imagens/pdf_icone.png";
                            } else if (in_array($ext, array("doc", "docx"))) {
                                $anexo_imagem = "imagens/docx_icone.png";
                            } else {
                                $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
                            }
                        
                            $anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

                            $anexo        = basename($anexos[0]);
                            $anexo_s3     = true;
                        }
                    }
                    ?>
                    <div id="div_anexo" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                        <?php 
                        if (isset($anexo_link)) { ?>
                            <a href="<?=$anexo_link?>" target="_blank" >
                        <?php 
                        } ?>

                        <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                        <?php
                        if (isset($anexo_link)) { ?>
                            </a>                                                
                        <?php } ?>

                        <?php
                        if ($anexo_s3 === false) {
                        ?>
                            <button id="anexar" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" >Anexar</button>
                        <?php
                        }
                        ?>

                        <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                        <input type="hidden" rel="anexo" class="classe_anexo" name="anexo" value="<?=$anexo?>" />
                        <input type="hidden" name="anexo_s3" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                        <?php
                        if ($anexo_s3 === true) {?>
                            <button id="baixar" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>
                        <?php   
                        }
                        ?>                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Fim anexo-->
        <br />
    <?php
    }
    ?>
    <p>
        <br />
        <?php
        if (!in_array($login_fabrica, [3]) || (in_array($login_fabrica, [3]) && $situacao == "alterar")) {
        ?>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));" value="Gravar"><?php echo (strlen($situacao) > 0 && $situacao == "alterar") ? "Alterar" : "Cadastrar"; ?></button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <?php
        } else { ?>
            <input type="button" style="cursor:pointer;" class="btn" id="btn_gravar" value="<?= (isset($_REQUEST['edita_defeito_familia'])) ? "Alterar" : "Cadastrar" ?>">
            <input type='hidden' id="btn_click" name='btn_acao' value='submit' />
        <?php
        }
        ?>
    </p>
    <br />
</form>
<br />
<p class="tac">
        <button type="button" class="btn" id="listar_tudo">Listar Defeitos / Soluções</button>
        <?php
        if ($login_fabrica == 158) {
        ?>
            <p class="text text-info"><span class="label label-info">Dica:</span>
            Para listar apenas os defeitos/soluções para <strong>Piso</strong>, selecione
            <em><label style="display:inline" for='tipoPiso'>Atendimento Tipo Piso</label></em> no formulário e clique a seguir clique no botão.</p>
        <!-- <a href="<?= $PHP_SELF ?>?listar_tudo=1" class="btn">Listar Defeitos / Soluções</a> -->
        <?php
        } ?>

</p>

</div>
<?php

if($_GET["listar_tudo"] == "1"){

    if(in_array($login_fabrica, array(52))) {     	   	
    	$familia_x = $_GET['familia_x'];
    	if(!empty($familia_x)){
    		$cond_familia = " AND tbl_familia.familia = {$familia_x} ";
    	}
        $sql_lista = "
            SELECT
                    tbl_defeito_constatado_solucao.defeito_constatado_solucao AS id_defeito_solucao,
                    tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                    tbl_defeito_constatado.descricao AS desc_defeito,
                    tbl_solucao.solucao,
                    tbl_solucao.descricao AS desc_solucao,
                    --0 AS familia,
                    --0 AS desc_familia,
                    tbl_familia.codigo_familia AS familia,
                    tbl_familia.descricao AS desc_familia,
                    tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                    0 AS produto,
                    0 AS ref_produto,
                    0 AS desc_produto,
                    tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo,
                    tbl_defeito_constatado_grupo.ativo,
                    0 AS garantia
            FROM tbl_defeito_constatado_solucao
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
            JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
            JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado.defeito_constatado_grupo = tbl_defeito_constatado_grupo.defeito_constatado_grupo
            JOIN tbl_familia ON tbl_familia.fabrica = tbl_defeito_constatado_solucao.fabrica AND tbl_familia.fabrica = {$login_fabrica}
            WHERE tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
            AND tbl_defeito_constatado_solucao.ativo IS TRUE                    
            {$cond_familia}
            ORDER BY tbl_defeito_constatado_solucao.defeito_constatado ASC;
        ";
    } else if (in_array($login_fabrica, array(158)))  {

        if (isset($_GET['atendimento'])) {
            $where = " AND tipo_atendimento = 272";
        }

        $sql_lista = "
            SELECT
                    tbl_diagnostico.diagnostico AS id_defeito_solucao,
                    tbl_diagnostico.defeito_constatado AS defeito_constatado,
                    tbl_defeito_constatado.codigo||' - '||tbl_defeito_constatado.descricao AS desc_defeito,
                    tbl_solucao.solucao,
                    tbl_solucao.codigo||' - '||tbl_solucao.descricao AS desc_solucao,
                    tbl_familia.familia,
                    tbl_familia.descricao AS desc_familia,
                    0 AS procedimento,
                    0 AS produto,
                    0 AS ref_produto,
                    0 AS desc_produto,
                    0 AS defeito_constatado_grupo,
                    tbl_diagnostico.ativo,
                    tbl_diagnostico.garantia,
                    tbl_diagnostico.tipo_atendimento
            FROM tbl_diagnostico
            JOIN tbl_familia ON tbl_familia.familia  = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
            JOIN tbl_solucao ON tbl_solucao.solucao = tbl_diagnostico.solucao AND tbl_solucao.fabrica = {$login_fabrica}
            WHERE tbl_diagnostico.fabrica = {$login_fabrica}
            AND tbl_diagnostico.defeito_constatado IS NOT NULL
            AND tbl_diagnostico.solucao IS NOT NULL
            {$where}
            ORDER BY tbl_familia.descricao,tbl_defeito_constatado.codigo ASC;
        ";

    } else {
        $sql_lista = "
            SELECT
                    tbl_defeito_constatado_solucao.defeito_constatado_solucao AS id_defeito_solucao,
                    tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                    tbl_defeito_constatado.descricao AS desc_defeito,
                    tbl_solucao.solucao,
                    tbl_solucao.descricao AS desc_solucao,
                    0 AS familia,
                    0 AS desc_familia,
                    tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                    tbl_produto.produto AS produto,
                    tbl_produto.referencia AS ref_produto,
                    tbl_produto.descricao AS desc_produto,
                    0 AS defeito_constatado_grupo,
                    tbl_defeito_constatado_solucao.ativo,
                    0 AS garantia
            FROM tbl_defeito_constatado_solucao
            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
            JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
            JOIN tbl_produto ON tbl_produto.produto = tbl_defeito_constatado_solucao.produto
            WHERE tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
            ORDER BY tbl_defeito_constatado_solucao.defeito_constatado ASC;
        ";
    }

    //die(nl2br($sql_lista));

    $res_lista = pg_query($con, $sql_lista);

    if (pg_num_rows($res_lista) > 0) {  

        if (in_array($login_fabrica, [3])) {

            $sqlFamilia = "SELECT
                                tbl_defeito_constatado_solucao.defeito_constatado_solucao AS id_defeito_solucao,
                                tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                                tbl_defeito_constatado.descricao AS desc_defeito,
                                tbl_solucao.solucao,
                                tbl_solucao.descricao AS desc_solucao,
                                tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                                tbl_defeito_constatado_solucao.ativo,
                                tbl_defeito_constatado_solucao.campos_adicionais,
                                tbl_familia.descricao as desc_familia,
                                tbl_defeito_constatado_solucao.ativo
                        FROM tbl_defeito_constatado_solucao
                        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
                        AND tbl_familia.fabrica = {$login_fabrica}
                        JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                        WHERE tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                        AND tbl_defeito_constatado_solucao.campos_adicionais->>'familia' IS NOT NULL
                        AND tbl_defeito_constatado_solucao.produto IS NULL
                        ORDER BY tbl_defeito_constatado_solucao.defeito_constatado ASC";
            $resFamilia = pg_query($con, $sqlFamilia);
            ?>

            <table class="table tabela_item table-striped table-bordered table-hover table-large" style="margin: 0 auto; min-width: 1200px;">
                <thead>
                    <tr class="titulo_tabela">
                        <th colspan="100%">Integridade defeito x solução por família</th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th>Família</th>
                        <th>Defeito Constatado</th>
                        <th>Solução</th>
                        <th>Procedimento</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($dadosFam = pg_fetch_object($resFamilia)) { ?>
                        <tr>
                            <td><?= $dadosFam->desc_familia ?></td>
                            <td><?= $dadosFam->desc_defeito ?></td>
                            <td><?= $dadosFam->desc_solucao ?></td>
                            <td><?= substr($dadosFam->procedimento, 0, 30) ?></td>
                            <td class="tac">
                                <button type="button" class="btn btn-warning <?= ($dadosFam->ativo == "t") ? "" : "disabled" ?>" id="btn_inativar_<?= $dadosFam->id_defeito_solucao ?>" onclick="inativa_procedimento(<?= $dadosFam->id_defeito_solucao ?>)">Inativar</button>
                                <button type="button" class="btn btn-success <?= ($dadosFam->ativo == "t") ? "disabled" : "" ?>" id="btn_ativar_<?= $dadosFam->id_defeito_solucao ?>" onclick="ativa_procedimento(<?= $dadosFam->id_defeito_solucao ?>)">Ativar</button>
                                <a class="btn btn-primary" href="<?= $_SERVER['PHP_SELF']."?edita_defeito_familia=true&id_defeito_solucao={$dadosFam->id_defeito_solucao}" ?>">Editar</a>
                                <button type="button" class="btn btn-danger" id="btn_excluir_<?= $dadosFam->id_defeito_solucao ?>" onclick="exclui_defeito_solucao_familia(<?= $dadosFam->id_defeito_solucao ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php
                    } ?>
                </tbody>
            </table>
        <?php
        }

        ?>
        <br />
        <table id="tbl_defeito_solucao_familia" class="table tabela_item table-striped table-bordered table-hover table-large" style="margin: 0 auto; min-width: 1100px;">
            <thead>
                <tr class="titulo_coluna">
                    <?php
                    if($login_fabrica == 3){
                        ?>
                        <th>Ref. Produto</th>
                        <th>Desc. Produto</th>
                        <th>Defeito</th>
                        <th>Solução</th>
                        <th>Descrição Solução</th>
                        <th>Qtde</th>
                        <th>Índice de Solução %</th>
                        <th>Ação</th>
                        <?php
                    }else{
                    ?>
                        <? if (in_array($login_fabrica, array(52, 158))) { ?>
                            <th>Família</th>
                            <? if ($login_fabrica == 52) { ?>
                                <th>Grupo de Defeito</th>
                            <? }
                        } ?>
                        <th>Defeito</th>
                        <th>Solução</th>
                        <? if (!in_array($login_fabrica, array(52,158))) { ?>
                            <th>Descrição Solução</th>
                            <th>Ref. Produto</th>
                            <th>Desc. Produto</th>
                        <? } else if ($login_fabrica == 158) { ?>
                            <th>Status</th>
                            <th>Garantia</th>
                            <th>Piso</th>
                        <? } ?>
                        <th>Ação</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
            <?php
                $contador = pg_num_rows($res_lista);
                for ($i = 0; $i < $contador; $i++) {

                    $id_defeito_solucao = pg_fetch_result($res_lista, $i, "id_defeito_solucao");
                    $defeito_constatado = pg_fetch_result($res_lista, $i, "defeito_constatado");
                    $desc_defeito       = pg_fetch_result($res_lista, $i, "desc_defeito");
                    $solucao            = pg_fetch_result($res_lista, $i, "solucao");
                    $desc_solucao       = pg_fetch_result($res_lista, $i, "desc_solucao");

                    if (!in_array($login_fabrica, array(52,158))) {
                        $produto      = pg_fetch_result($res_lista, $i, "produto");
                        $ref_produto  = pg_fetch_result($res_lista, $i, "ref_produto");
                        $desc_produto = pg_fetch_result($res_lista, $i, "desc_produto");
                    } else if ($login_fabrica == 52) {
                        $defeito_constatado_grupo = pg_fetch_result($res_lista, $i, "defeito_constatado_grupo");
                    }

                    if (!in_array($login_fabrica, array(52, 158))) {
                        $procedimento = pg_fetch_result($res_lista, $i, "procedimento");
                    } else {
                        $cod_defeito     = pg_fetch_result($res_lista, $i, "cod_defeito");
                        $cod_solucao     = pg_fetch_result($res_lista, $i, "cod_solucao");
                        $desc_familia    = pg_fetch_result($res_lista, $i, "desc_familia");
                        $garantia        = pg_fetch_result($res_lista, $i, "garantia");
                        $tipo_atendimento= pg_fetch_result($res_lista, $i, "tipo_atendimento");

                        $imagem_garantia = ($garantia == 't') ? 'status_verde.png' : 'status_vermelho.png';
                        $imagem_piso     = ($tipo_atendimento == 272) ? 'status_verde.png' : 'status_vermelho.png';
                        $ativo           = pg_fetch_result($res_lista, $i, "ativo");
                        $imagem_ativo    = ($ativo == "t") ? 'status_verde.png' : 'status_vermelho.png';
                    }

                    if($login_fabrica == 3){

                        $ativo_t = pg_fetch_result($res_lista, $i, "ativo");

                        $sql_total_solucoes = "SELECT
                                                COUNT(dc_solucao_hd) AS total_solucoes
                                            FROM tbl_dc_solucao_hd
                                            JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
                                            JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                            WHERE
                                                tbl_dc_solucao_hd.fabrica = {$login_fabrica}
                                                AND tbl_defeito_constatado_solucao.produto = {$produto}
                                                AND tbl_hd_chamado.resolvido is not null
                                                AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado};";

                        $res_total_solucoes = pg_query($con, $sql_total_solucoes);

                        $total_solucoes = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

                        /* Estatística */
                        $sql_estatistica = "SELECT
                                                COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
                                            FROM tbl_dc_solucao_hd
                                            JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                            JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                            WHERE
                                                tbl_defeito_constatado_solucao.solucao = {$solucao}
                                                AND tbl_defeito_constatado_solucao.produto = {$produto}
                                                AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado}
                                                AND tbl_hd_chamado.resolvido is not null
                                                AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica};";
                        $res_estatistica = pg_query($con, $sql_estatistica);

                        $total_ds = pg_fetch_result($res_estatistica, 0, "total_ds");

                        if($total_ds > 0){
                            $total_porc = number_format(($total_ds * 100) / $total_solucoes, 1);
                        }else{
                            $total_porc = 0;
                        }

                        /* Fim - Estatística */
                        if ($ativo_t == 't') {
                            $visual_ativa   = 'none';
                            $visual_inativa = 'hide';
                        }else{
                            $visual_ativa   = 'hide';
                            $visual_inativa = 'none';
                        }

                    }

                    $botao_editar = (!in_array($login_fabrica, array(52,158))) ? "<a href='cadastro_defeitos_solucoes.php?defeito={$defeito_constatado}&solucao={$solucao}&produto={$produto}' class='btn btn-primary'>Editar</a>" : "";

                    $botao_excluir = "<button type='button' class='btn btn-danger status' rel='$id_defeito_solucao'>";
                    $botao_excluir .= (in_array($login_fabrica, array(158))) ? (($ativo == "t") ? "Inativar" : "Ativar") : "Excluir";
                    $botao_excluir .="</button>";

                    if ($login_fabrica == 3) {
                        unset($botao_editar);
                        unset($botao_excluir);

                        $disabled = ($total_porc !== 0) ? 'disabled' : '';
                        $onclick  = ($total_porc !== 0) ? '' : "onclick='javascript:window.location.href=\"cadastro_defeitos_solucoes.php?defeito={$defeito_constatado}&solucao={$solucao}&produto={$produto}\"'";
                        $disabled1 = ($ativo_t == 't' && $total_porc !== 0) ? '' : 'disabled';
                        $onclick1  = ($ativo_t == 't' && $total_porc !== 0) ? "onclick='inativa_procedimento($id_defeito_solucao)'" : '';
                        $onclick2  = ($total_porc !== 0) ? '' : "onclick='deleta_procedimento($id_defeito_solucao)'";
                        $disabled2 = ($ativo_t !== 't' && $total_porc !== 0) ? '' : 'disabled';
                        $onclick3  = ($ativo_t !== 't' && $total_porc !== 0) ? "onclick='ativa_procedimento($id_defeito_solucao)'" : '';

                        $botao_editar = "
                        <button type='button' class='btn btn-info btn-open-shawdowbox' data-url='defeitos_solucoes_procedimento.php' data-parametros='defeito={$defeito_constatado}&solucao={$solucao}&produto={$produto}' data-width = 900 data-height = 500>Inserir Descrição</button>
                        <button type='button' {$onclick} class='btn btn-primary {$disabled}' style='cursor: pointer;'>Editar</button>
                        <button type='button' class='btn btn-warning {$disabled1}' id='btn_inativar_{$id_defeito_solucao}' {$onclick1} >Inativar</button>
                        <button type='button' class='btn btn-success {$disabled2}' id='btn_ativar_{$id_defeito_solucao}' {$onclick3}>Ativar</button>
                        <button type='button' class='btn btn-danger $disabled' {$onclick2}>Excluir</button>";

                        if(strlen($procedimento) !== 0){
                            $procedimento = (mb_detect_encoding($procedimento, 'utf-8', true)) ? utf8_decode($procedimento) : $procedimento;

                            $procedimento = (strlen($procedimento) > 60) ? substr($procedimento, 0, 50)."..." : $procedimento;
                        }
                    }else{
                        if(strlen($procedimento) == 0){
                            $class_tac = "class='tac'";
                            $procedimento = "<a href='defeitos_solucoes_procedimento.php?defeito={$defeito_constatado}&solucao={$solucao}&produto={$produto}' rel='shadowbox; width = 900; height = 500;' class='btn btn-info' style='margin: 0 auto !important;'>Inserir Descrição</a>";
                        }else{
                            $class_tac = "";
                            $link_alterar = "<a href='defeitos_solucoes_procedimento.php?defeito={$defeito_constatado}&solucao={$solucao}&produto={$produto}' rel='shadowbox; width = 900; height = 500;'>Editar Descrição</a>";
                            $procedimento = (strlen($procedimento) > 60) ? substr(utf8_decode($procedimento), 0, 50)."... <br /> <a href='defeitos_solucoes_procedimento.php?defeito={$defeito_constatado}&solucao={$solucao}&produto={$produto}&box=1' rel='shadowbox; width = 900; height = 355;'>Leia Mais</a> &nbsp; ".$link_alterar : $procedimento."<br />".$link_alterar;
                        }
                    }

                    if($login_fabrica == 3){

                        ?>
                        <tr id='box_<?= $id_defeito_solucao; ?>'>
                            <td><?= $ref_produto; ?></td>
                            <td><?= $desc_produto; ?></td>
                            <td><?= $desc_defeito; ?></td>
                            <td><?= $desc_solucao; ?></td>
                            <td><div style='min-width: 150px;' <?=$class_tac;?> id='box_procedimento_<?= $defeito_constatado."_".$solucao; ?>'><?= $procedimento; ?></div></td>
                            <td class='tac'><?= $total_ds; ?></td>
                            <td class='tac'><?= $total_porc; ?> %</td>
                            <td class='tac' nowrap><?= $botao_editar; ?> &nbsp; <?= $botao_excluir; ?></td>
                        </tr>
                        <?php

                    }else{

                        if ($login_fabrica == 52) { ?>
                        <tr id='box_<?= $id_defeito_solucao; ?>'>
                            <td><?= $desc_familia; ?></td>
                            <td><?= $defeito_constatado_grupo; ?></td>
                            <td><?= $desc_defeito; ?></td>
                            <td><?= $desc_solucao; ?></td>                            
                            <td width='30%' class='tac' nowrap><?= $botao_editar; ?> &nbsp; <?= $botao_excluir; ?></td>
                        </tr>
                        <? } else { ?>
                        <tr id='box_<?= $id_defeito_solucao; ?>'>
                            <? if (in_array($login_fabrica, array(158))) { ?>
                                <td><?= $desc_familia; ?></td>
                            <? } ?>
                            <td><?= $desc_defeito; ?></td>
                            <td><?= $desc_solucao; ?></td>
                            <? if (!in_array($login_fabrica, array(158))) { ?>
                                <td><div style='min-width: 150px;' $class_tac id='box_procedimento_<?= $defeito_constatado."_".$solucao; ?>'><?= $procedimento; ?></div></td>
                                <td><?= $ref_produto; ?></td>
                                <td><?= $desc_produto; ?></td>
                            <? } else if (in_array($login_fabrica, array(158))) { ?>
                                <td class="tac"><img name="visivel" src="imagens/<?= $imagem_ativo; ?>" /></td>
                                <td class="tac"><img src="imagens/<?= $imagem_garantia; ?>" /></td>
                                <td class="tac"><img src="imagens/<?=$imagem_piso; ?>" /></td>
                            <? } ?>
                            <td class='tac' nowrap><?= $botao_editar; ?> &nbsp; <?= $botao_excluir; ?></td>
                        </tr>
                        <?php
                        }
                    }
                } ?>
            </tbody>
        </table>
        <br />
        <? if ($login_fabrica == 158) {
            if (pg_num_rows($res_lista) > 50) { ?>
                <script>
                    $.dataTableLoad({ table: "#tbl_defeito_solucao_familia" });
                </script>
            <? }
        }else{
            ?>
            <script>
                $.dataTableLoad({ table: "#tbl_defeito_solucao_familia" });
            </script>
            <?php
        }
    } else { ?>
        <br />
        <div class="container">
            <div class="alert alert-warning text-center"><h4>Nenhum resultado encontrado</h4></div>
        </div>
        <br />
    <? }
} 

if (in_array($login_fabrica, [3])) { ?>
    <!-- Inicio anexo -->
    <form name="form_anexo" method="post" action="cadastro_defeitos_solucoes.php" enctype="multipart/form-data" style="display: none;" >
        <input type="file" name="anexo_upload" value="" />

        <input type="hidden" name="ajax_anexo_upload" value="t" />
        <!-- <input type="hidden" name="anexo_posicao" value="<?=$i?>" /> -->
        <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
    </form>
<?php
} 

include "rodape.php"; ?>

