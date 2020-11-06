<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include '../ajax_cabecalho.php';

$admin_privilegios="info_tecnica,call_center";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';
include_once '../class/ComunicatorMirror.php';

function fnc_formata_data_hora_pg ($data) {
    if (strlen ($data) == 0) return null;

    $xdata = $data.":00 ";
    $aux_ano  = substr ($xdata,6,4);
    $aux_mes  = substr ($xdata,3,2);
    $aux_dia  = substr ($xdata,0,2);
    $aux_hora = substr ($xdata,11,5).":00";

    return "'" . $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora . "'";
}

//--====== POST AJAX ===========================================================================-
if($_POST['ajax'] == 'sim' and $_POST['acao'] == 'deletar_treinamento_estado'){
    $treinamento = $_POST['treinamento'];

    $sql = "UPDATE tbl_treinamento SET estado = null WHERE treinamento = $treinamento;";

    pg_query($con, $sql);
    if(pg_last_error($con) == FALSE){
        echo json_encode(array("success" => "Estado removido"));
        exit;
    }

    echo json_encode(array("messageError" => $msg_erro,"messageLog" => "Ocorreu um erro ao tentar remover o estado, por favor tente novamente"));
    exit;
}

if($_POST['ajax'] == 'sim' and $_POST['acao'] == 'deletar_treinamento_cidade'){

    $treinamento_cidade = $_POST['treinamento_cidade'];
    $treinamento = $_POST['treinamento'];

    $sql = "SELECT treinamento_cidade
            FROM tbl_treinamento_cidade tc
            JOIN tbl_treinamento t ON tc.treinamento = t.treinamento
            WHERE tc.treinamento_cidade = $treinamento_cidade
            AND t.treinamento = $treinamento
            AND t.fabrica = $login_fabrica";

    $res = pg_query($con,$sql);
    $res = pg_fetch_all($res);

    if(count($res) > 0){
        $sql = "DELETE FROM tbl_treinamento_cidade WHERE treinamento_cidade = $treinamento_cidade";
        $res = pg_query($con,$sql);
        if(pg_last_error($con) == FALSE){
            echo json_encode(array("success" => "Cidade removida"));
            exit;
        }else{
            $msg_erro = utf8_encode("Não foi possível remover a cidade");
            $msg_log = pg_last_error($con);
        }
    }else{
        $msg_erro = utf8_encode("Não foi possível remover a cidade");
    }

    echo json_encode(array("messageError" => $msg_erro,"messageLog" => $msg_log));
    exit;
}


if($_POST['ajax'] == 'sim' and $_POST['acao'] == 'deletar_promotor_treinamento'){

    $promotor_treinamento = $_POST['promotor_treinamento'];
    $treinamento = $_POST['treinamento'];

    $sql = "SELECT treinamento_promotor FROM tbl_treinamento_promotor tp WHERE tp.treinamento_promotor = $promotor_treinamento and treinamento = $treinamento";

    $res = pg_query($con, $sql);
    $res = pg_fetch_all($res);

    if(count($res) > 0){
        $sql = "DELETE FROM tbl_treinamento_promotor WHERE treinamento_promotor = $promotor_treinamento and treinamento = $treinamento";
        $res = pg_query($con,$sql);
        if(pg_last_error($con) == FALSE){
            echo json_encode(array("success" => "Promotor removido"));
            exit;
        }else{
            $msg_erro = utf8_encode("Não foi possível remover o promotor");
            $msg_log = pg_last_error($con);
        }
    }else{
        $msg_erro = utf8_encode("Não foi possível remover o promotor");
    }

    echo json_encode(array("messageError" => $msg_erro,"messageLog" => $msg_log));
    exit;
}

//--====== NOTA TECNICOS ===========================================================================-
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'atualiza_nota_tecnico') {
    $treinamento_posto = $_GET['treinamento_posto'];
    $nota = $_GET['nota'];
    $nota = str_replace(",", ".", $nota);


    $sql = "SELECT treinamento_posto FROM tbl_treinamento_posto JOIN tbl_treinamento USING(treinamento) WHERE treinamento_posto = $treinamento_posto AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)){

        $sql = "UPDATE tbl_treinamento_posto SET nota_tecnico = $nota WHERE treinamento_posto = $treinamento_posto";
        $res = pg_query($con, $sql);

        if(pg_last_error($con)){
            echo json_encode(array("messageError" => utf8_encode("Não foi possível atualizar o registro")));
        }else{
            echo json_encode(array("success" => "Nota atualizada"));
        }

    }else{
        echo json_encode(array("messageError" => utf8_encode("Registro não encontrado")));
    }
    exit;
}

//--====== DIAS PARTICIPOU ===========================================================================-
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'atualiza_dia_participou') {
    $treinamento_posto = $_GET['treinamento_posto'];
    $dia_participou    = $_GET['dia'];

    $sql = "SELECT treinamento_posto FROM tbl_treinamento_posto JOIN tbl_treinamento USING(treinamento) WHERE treinamento_posto = $treinamento_posto AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)){

        $sql = "UPDATE tbl_treinamento_posto SET dia_participou = $dia_participou WHERE treinamento_posto = $treinamento_posto";
        $res = pg_query($con, $sql);

        if(pg_last_error($con)){
            echo json_encode(array("messageError" => utf8_encode("Não foi possível atualizar o registro")));
        }else{
            echo json_encode(array("success" => "Dias Participado, Atualizado"));
        }

    }else{
        echo json_encode(array("messageError" => utf8_encode("Registro não encontrado")));
    }
    exit;
}

//--====== ATUALIZA APROVA OU REPROVA =============================================================-
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'atualiza_aprova_reprova') {
    $treinamento_posto = $_GET['treinamento_posto'];
    $aprovado_reprovado = $_GET['aprovado_reprovado'];

    $sql = "SELECT treinamento_posto FROM tbl_treinamento_posto JOIN tbl_treinamento USING(treinamento) WHERE treinamento_posto = $treinamento_posto AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)){
        if($aprovado == "SIM"){
            $aprovado = 'true';
        }elseif($aprovado == "NAO"){
            $aprovado = 'false';
        }

        $sql = "UPDATE tbl_treinamento_posto SET aprovado = $aprovado WHERE treinamento_posto = $treinamento_posto";
        $res = pg_query($con, $sql);
        if(pg_last_error($con)){
            echo json_encode(array("messageError" => utf8_encode("Ocorreu um erro ao atualizar o registro")));
        }else{
            echo json_encode(array("success" => "Oks"));
        }
    }else{
        echo json_encode(array("messageError" => utf8_encode("Registro não encontrado")));
    }
    exit;
}

//--====== ATUALIZA REALIZOU PROVA =============================================================-
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'atualiza_realizou_prova') {
    $treinamento_posto  = $_GET['treinamento_posto'];
    $aux_realizou_prova = $_GET['realizou_prova'];

    $sql = "SELECT treinamento_posto FROM tbl_treinamento_posto JOIN tbl_treinamento USING(treinamento) WHERE treinamento_posto = $treinamento_posto AND fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res)){
        if($aux_realizou_prova == "t"){
            $realizou_prova = 'true';
        }elseif($aux_realizou_prova == "f"){
            $realizou_prova = 'false';
        }

        $sql = "UPDATE tbl_treinamento_posto SET aplicado = $realizou_prova WHERE treinamento_posto = $treinamento_posto";
        $res = pg_query($con, $sql);
        if(pg_last_error($con)){
            echo json_encode(array("messageError" => utf8_encode("Ocorreu um erro ao atualizar o registro")));
        }else{
            echo json_encode(array("success" => "Oks"));
        }
    }else{
        echo json_encode(array("messageError" => utf8_encode("Registro não encontrado")));
    }
    exit;
}
//--====== AJAX CIDADES ===========================================================================-
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'consulta_cidades') {

    if (strlen($_GET['estados']) >0 ){
        if(array_key_exists('estados', $_GET)){
            $estados = $_GET['estados'];
            $estados = explode(',', $estados);

            foreach ($estados as $key => $value) {
                $estados[$key] = "'".trim($value)."'";
            }
            $estados = implode(',',$estados);

        }else{
            echo json_encode(array("messageError" => utf8_encode("Informe uma região ou estado")));
            exit;
        }
    }else{
        echo json_encode(array("messageError" => utf8_encode("Informe uma região ou estado")));
        exit;
    }

    if (isset($_GET['cadastro']) && $_GET['cadastro'] == "sim") {
        $sql = "SELECT cidade, nome FROM tbl_cidade WHERE UPPER(estado) = UPPER($estados) ORDER BY nome ASC";
        $res = pg_exec($con,$sql);
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $cidades[] = array("cidade" => utf8_encode(pg_result($res,$i,nome)),"codigo" => pg_result($res,$i,cidade));
        }
    }else{
        $sql = "SELECT DISTINCT * FROM
                    (
                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER($estados)
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER($estados)
                        )
                    ) AS cidade
                    ORDER BY cidade ASC";

        $res = pg_exec($con,$sql);
        for ($i=0; $i < pg_num_rows($res); $i++) {
            $cidades[] = array("cidade" => utf8_encode(pg_result($res,$i,cidade)));
        }
    }

    echo json_encode($cidades);
    exit;
}

//--====== AJAX ESTADOS ============================================================================-
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'consulta_estados') {

    if ($_GET['treinamento_id'] != "") {
        $treinamento_id = $_GET['treinamento_id'];
        
        $sql_tem_posto = "SELECT posto FROM tbl_treinamento_posto WHERE treinamento = $treinamento_id";
        $res_tem_posto = pg_query($con, $sql_tem_posto);
        if (pg_num_rows($res_tem_posto) > 0) {
            $estado = [];
            $estado[] = array("cod_estado" => "tem_posto", "estado" => "tem_posto");
        }
    }


    if(!array_key_exists('estados', $_GET)){
        exit(json_encode(array("messageError" => utf8_encode("Informe uma região"))));
    }

    $regiao = $_GET['estados'];

    switch ($regiao) {
        case "PR, RS, SC":
            $estado[] = array("cod_estado" => "PR", "estado" => utf8_encode("Paraná"));
            $estado[] = array("cod_estado" => "RS", "estado" => utf8_encode("Rio Grande do Sul"));
            $estado[] = array("cod_estado" => "SC", "estado" => utf8_encode("Santa Catarina"));
            break;

        case "AL, BA, CE, MA, PB, PE, PI, RN, SE":
            $estado[] = array("cod_estado" => "AL", "estado" => utf8_encode("Alagoas"));
            $estado[] = array("cod_estado" => "BA", "estado" => utf8_encode("Bahia"));
            $estado[] = array("cod_estado" => "CE", "estado" => utf8_encode("Ceará"));
            $estado[] = array("cod_estado" => "MA", "estado" => utf8_encode("Maranhão"));
            $estado[] = array("cod_estado" => "PB", "estado" => utf8_encode("Paraíba"));
            $estado[] = array("cod_estado" => "PE", "estado" => utf8_encode("Pernambuco"));
            $estado[] = array("cod_estado" => "PI", "estado" => utf8_encode("Piaui"));
            $estado[] = array("cod_estado" => "RN", "estado" => utf8_encode("Rio Grande do Norte"));
            $estado[] = array("cod_estado" => "SE", "estado" => utf8_encode("Sergipe"));
            break;

        case "ES, MG, RJ, SP":
            $estado[] = array("cod_estado" => "ES", "estado" => utf8_encode("Espirito Santo"));
            $estado[] = array("cod_estado" => "MG", "estado" => utf8_encode("Minas Gerais"));
            $estado[] = array("cod_estado" => "RJ", "estado" => utf8_encode("Rio de Janeiro"));
            $estado[] = array("cod_estado" => "SP", "estado" => utf8_encode("São Paulo"));
            break;

        case "DF, GO, MT, MS":
            $estado[] = array("cod_estado" => "DF", "estado" => utf8_encode("Distrito Federal"));
            $estado[] = array("cod_estado" => "GO", "estado" => utf8_encode("Goiás"));
            $estado[] = array("cod_estado" => "MT", "estado" => utf8_encode("Mato Grosso"));
            $estado[] = array("cod_estado" => "MS", "estado" => utf8_encode("Mato Grosso do Sul"));
            break;

        case "AC, AP, AM, PA, RO, RR, TO":
            $estado[] = array("cod_estado" => "AC", "estado" => utf8_encode("Acre"));
            $estado[] = array("cod_estado" => "AP", "estado" => utf8_encode("Amapá"));
            $estado[] = array("cod_estado" => "AM", "estado" => utf8_encode("Amazonas"));
            $estado[] = array("cod_estado" => "PA", "estado" => utf8_encode("Pará"));
            $estado[] = array("cod_estado" => "RO", "estado" => utf8_encode("Rondônia"));
            $estado[] = array("cod_estado" => "RR", "estado" => utf8_encode("Roraima"));
            $estado[] = array("cod_estado" => "TO", "estado" => utf8_encode("Tocantins"));
            break;
        case"":
            $estado[] = array("cod_estado" => "AC", "estado" => utf8_encode("Acre"));
            $estado[] = array("cod_estado" => "AL", "estado" => utf8_encode("Alagoas"));
            $estado[] = array("cod_estado" => "AP", "estado" => utf8_encode("Amapá"));
            $estado[] = array("cod_estado" => "AM", "estado" => utf8_encode("Amazonas"));
            $estado[] = array("cod_estado" => "BA", "estado" => utf8_encode("Bahia"));
            $estado[] = array("cod_estado" => "CE", "estado" => utf8_encode("Ceará"));
            $estado[] = array("cod_estado" => "DF", "estado" => utf8_encode("Distrito Federal"));
            $estado[] = array("cod_estado" => "ES", "estado" => utf8_encode("Espirito Santo"));
            $estado[] = array("cod_estado" => "GO", "estado" => utf8_encode("Goiás"));
            $estado[] = array("cod_estado" => "MA", "estado" => utf8_encode("Maranhão"));
            $estado[] = array("cod_estado" => "MT", "estado" => utf8_encode("Mato Grosso"));
            $estado[] = array("cod_estado" => "MS", "estado" => utf8_encode("Mato Grosso do Sul"));
            $estado[] = array("cod_estado" => "MG", "estado" => utf8_encode("Minas Gerais"));
            $estado[] = array("cod_estado" => "PA", "estado" => utf8_encode("Pará"));
            $estado[] = array("cod_estado" => "PB", "estado" => utf8_encode("Paraíba"));
            $estado[] = array("cod_estado" => "PR", "estado" => utf8_encode("Paraná"));
            $estado[] = array("cod_estado" => "PE", "estado" => utf8_encode("Pernambuco"));
            $estado[] = array("cod_estado" => "PI", "estado" => utf8_encode("Piaui"));
            $estado[] = array("cod_estado" => "RJ", "estado" => utf8_encode("Rio de Janeiro"));
            $estado[] = array("cod_estado" => "RN", "estado" => utf8_encode("Rio Grande do Norte"));
            $estado[] = array("cod_estado" => "RS", "estado" => utf8_encode("Rio Grande do Sul"));
            $estado[] = array("cod_estado" => "RO", "estado" => utf8_encode("Rondônia"));
            $estado[] = array("cod_estado" => "RR", "estado" => utf8_encode("Roraima"));
            $estado[] = array("cod_estado" => "SC", "estado" => utf8_encode("Santa Catarina"));
            $estado[] = array("cod_estado" => "SP", "estado" => utf8_encode("São Paulo"));
            $estado[] = array("cod_estado" => "SE", "estado" => utf8_encode("Sergipe"));
            $estado[] = array("cod_estado" => "TO", "estado" => utf8_encode("Tocantins"));
        break;
    }
    exit(json_encode($estado));
}
//--====== AJAX REGIAO =================================================================
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == 'consulta_regiao') {
    $sql = "SELECT regiao,descricao,estados_regiao from tbl_regiao where fabrica = $login_fabrica and ativo is true";
    $res = pg_exec($con,$sql);

    if (pg_num_rows($res) > 0) {
        $tabela = "<select id='regiao' name='regiao' size='1' class='frm'>";
        $tabela .= "<option value=''>Selecione uma região</option>";
        for($i=0;$i<pg_num_rows($res);$i++){
            if(strstr(pg_result($res,$i,estados_regiao), $estado_cidade)){
                $selected = "selected";
                $estados_regiao_combo = pg_result($res,$i,estados_regiao);
            }else{
                $selected = "";
            }
            $tabela .= "<option $selected value='".pg_result($res,$i,estados_regiao)."'>".pg_result($res,$i,descricao)." - (".pg_result($res,$i,estados_regiao).")</option>";
        }
        $tabela .= "</select>";
        exit(json_encode(array("ok" => utf8_encode($tabela))));
    }else{
        exit(json_encode(array("erro" => utf8_encode("Não foi possível listar as regiões para pesquisa, recarregue a pagina..."))));
    }
}

//--====== LISTA ESCRITÓRIO =================================================================
if ($_GET['ajax'] == 'sim' and $_GET['acao'] == "listaEscritorio") {
    $sql = "SELECT DISTINCT
                descricao,
                escritorio_regional
            FROM tbl_escritorio_regional WHERE fabrica=$login_fabrica AND ativo ORDER BY descricao;";

    $res = pg_exec ($con,$sql);
    if (pg_numrows($res) > 0) {
        $listaEscritorio .= "<select name='escritorio_regional' id='escritorio_regional' class='Caixa'>\n";
        $listaEscritorio .= "<option value=''>Selecione um escritório</option>\n";

        for ($x = 0 ; $x < pg_numrows($res) ; $x++){
            $aux_descricao     = trim(pg_result($res,$x,descricao));
            $aux_escritorio_regional = trim(pg_result($res,$x,escritorio_regional));

            $listaEscritorio .= "<option value='$aux_escritorio_regional'";
            $listaEscritorio .= ">$aux_descricao</option>\n";
        }
        $listaEscritorio .= "</select>\n";
        exit(json_encode(array("ok" => utf8_encode($listaEscritorio))));
    }else{
        exit(json_encode(array("erro" => "sem registro")));
    }
}

// DELETA POSTO TREINAMENTO //
if($_GET['ajax']=='sim' AND $_GET['acao']=='deletar_posto') { //HD-3261932
    $treinamento = trim($_GET['treinamento']);
    $posto = trim($_GET['posto']);

    $sql_treinamento = "SELECT treinamento, tecnico, confirma_inscricao
                        FROM tbl_treinamento_posto
                        WHERE posto = $posto
                        AND treinamento = $treinamento
                        AND tecnico IS NOT NULL
						AND confirma_inscricao = 't' 
						AND ativo ";
    $res_treinamento = pg_query($con, $sql_treinamento);
    if(pg_num_rows($res_treinamento) == 0){
        $sql_delete = "DELETE FROM tbl_treinamento_posto
                        WHERE treinamento = $treinamento AND posto = $posto";
        $res_delete = pg_query($con, $sql_delete);
        exit(json_encode(array("success" => "deletado")));
    }else{
        exit(json_encode(array("erro" => "error")));
    }
    exit;
}

//--====== CADASTRO DE POSTO (addPosto()) =================================================================
if ($_GET['ajax'] == 'sim' AND $_GET['acao'] == 'adicionar_posto'){
    $array_tipo_posto  = $_GET['select_tipo_posto'];
    $treinamento_id    = trim($_GET['treinamento']);
    $array_linha       = $_GET['linha'];
    $codigo_posto      = trim($_GET['codigo_posto']);

    if (!is_array($array_tipo_posto)){
        $array_tipo_posto[] = $array_tipo_posto;
    }

    if (!is_array($array_linha)){
        $array_linha[] = $array_linha;
    }

    $posto_ok       = false;
    $linha_ok       = false;
    $aux_tipo_posto = implode(",", $array_tipo_posto);
    $aux_linha      = implode(",", $array_linha);

    $sql = "SELECT tipo_posto
                FROM tbl_posto_fabrica
            WHERE fabrica    = {$login_fabrica}
            AND codigo_posto = '$codigo_posto'";
    $res = pg_query($con,$sql);
    if (pg_numrows($res) > 0) {

        $resultado = pg_fetch_array($res);
        if (!in_array($resultado['tipo_posto'], $array_tipo_posto))
        {
            $msg_erro = 'O Tipo de Posto selecionado é inválido';
        }
    }

    $sql_linha = "SELECT
                    tbl_posto_fabrica.posto,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_linha.linha,
                    tbl_posto_linha.posto
                FROM tbl_posto_fabrica
                    JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
                    AND tbl_posto_linha.linha IN ({$aux_linha})
                WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
                    AND tbl_posto_fabrica.fabrica    = {$login_fabrica}";
    $res_linha = pg_query($con, $sql_linha);
    $msg_erro  = pg_last_error($con);

    if(pg_num_rows($res_linha) > 0){
        $sql_posto = "SELECT
                        tbl_posto_fabrica.posto AS posto_id,
                        tbl_posto_fabrica.tipo_posto,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto.nome
                    FROM tbl_posto_fabrica
                JOIN tbl_posto ON tbl_posto.posto    = tbl_posto_fabrica.posto
                WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
                    AND tbl_posto_fabrica.tipo_posto IN ({$aux_tipo_posto})
                    AND tbl_posto_fabrica.fabrica    = {$login_fabrica}";
        $res_posto = pg_query($con, $sql_posto);
        $msg_erro = pg_last_error($con);

        if(pg_num_rows($res_posto) > 0){

            $count = pg_num_rows($res_posto);
            for ($i=0; $i<$count; $i++){
                $posto_id     = pg_fetch_result($res_posto, $i, 'posto_id');
                $sql_verifica = "SELECT treinamento_posto FROM tbl_treinamento_posto
                                    WHERE treinamento = {$treinamento_id} AND posto = {$posto_id}";
                $res_verifica = pg_query($con, $sql_verifica);

                // se o posto não estiver cadastrado...
                if(pg_num_rows($res_verifica) == 0){
                    $sqlInsert = "INSERT INTO tbl_treinamento_posto(treinamento,posto) VALUES ($treinamento_id,$posto_id)";
                    $resInsert = pg_query($con, $sqlInsert);
                    $linha_ok  = true;
                    $posto_ok  = true;
                }else{
                    $msg_erro = "O posto selecionado já foi adicionado";
                }
            }
        }else{
            $msg_erro = "O tipo de posto selecionado, não é válido para o posto selecionado";
        }
    }else{
        $msg_erro = "O tipo de linha selecionada, não é válida para o posto selecionado";
    }

    if (strlen($msg_erro) > 0 AND $posto_ok != true AND $linha_ok != true) {
        exit(json_encode(array("error" => utf8_encode($msg_erro))));

    }elseif ($posto_ok != false AND $linha_ok != false){
        exit(json_encode(array("success" => "success")));
    }
}

//--====== CADASTRO DE TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao'] =='cadastrar') {
    $id_treinamento  = trim($_GET['treinamento']);
    if (!in_array($login_fabrica, array(175))){
        $prazo_inscricao = trim($_GET['prazo_inscricao']);            
    }
    $data_inicial    = trim($_GET['data_inicial']);
    $data_final      = trim($_GET['data_final']);

    if ($login_fabrica == 42 && valida_data_treinamento_new($data_inicial, $data_final)) {
        $msg_erro = "Data Inválida";
    }

    $titulo          = trim($_GET['titulo']);
    $linha           = trim($_GET['linha']);

    if(in_array($login_fabrica, array(169,170,175,193))){

        if (!in_array($login_fabrica, array(175))){
            $qtde_participante          = $_GET['qtde_participante'];
            $estado_participante        = $_GET['estado_participante'];

            if($qtde_participante == ""){
                $qtde_participante = 0;
            }

            $qtde_min = trim($_GET['qtde_min']);
        }

        $treinamento_tipo           = trim($_GET['treinamento_tipo']);
        if (in_array($login_fabrica, array(175))){
             if (strlen($treinamento_tipo)    == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o tipo de Treinamento<br>';
        }

        if (!empty($treinamento_tipo)){
            $sql = "SELECT nome FROM tbl_treinamento_tipo WHERE treinamento_tipo = {$treinamento_tipo} AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0){
                $treinamento_tipo_nome = pg_fetch_result($res, 0, 'nome');
            }
        }
    }else{
        $familia         = trim($_GET['familia']);
    }

    if (in_array($login_fabrica, [1])) {
        $vaga_posto      = $_GET['vaga_posto'];
        $linha           = $_GET['linha_bd'];
        $familia         = $_GET['familia_bd'];
        $marca           = $_GET['marca_bd'];
        $tipo_posto      = $_GET['tipo_posto_bd'];
        $categoria_posto = utf8_decode($_GET['categoria_bd']);

        if(empty($vaga_posto) OR $vaga_posto == "" OR $vaga_posto < 0){
            $vaga_posto = 0;
        }
    }

    $descricao       = trim($_GET['descricao']);
    $qtde            = trim($_GET['qtde']);
    $vagas_min       = trim($_GET['vagas_min']);
    $adicional       = trim($_GET['adicional']);
    $cidade          = trim($_GET['cidade']);
    $palestrante     = trim($_GET["palestrante"]);
    $macro_linha     = trim($_GET['macro_linha']);
    $estado          = trim($_GET['listaEstado']);

    if (!in_array($login_fabrica, array(175))){
        $local           = trim($_GET['local']);
    }

    if(in_array($login_fabrica, array(169,170,193))){
        $carga_horaria = $_GET['carga_horaria'];
        $tipo_posto    = $_GET['tipo_posto'];
        $promotor      = $_GET['promotor'];
        $prazo_final_inscricao = $_GET['prazo_final_inscricao'];
        $cidades_treinamento = json_decode($_GET['cidades']);
        if($cidades_treinamento[0] == NULL){
            $cidades_treinamento = array();
        }
        $promotores_treinamento = json_decode($_GET['promotores']);

        if($promotores_treinamento[0] == NULL){
            $promotores_treinamento = array();
        }
    }

    if (in_array($login_fabrica, array(175))){
        $treinamento_por      = trim($_GET['treinamento_por']);
        $validade_treinamento = trim($_GET['validade_treinamento']);

        if (empty($_GET['produto'])){
            $msg_erro = "Favor informar um produto";
        }
        
        if (strtolower($treinamento_tipo_nome) == 'presencial'){
            
            $inicio_inscricao     = trim($_GET['inicio_inscricao']);
            $prazo_inscricao      = trim($_GET['prazo_inscricao']);
            $local                = trim($_GET['local']);
              
            if (strlen($inicio_inscricao) == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o Início da Inscrição';  
            if (strlen($prazo_inscricao) == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o Prazo da Inscrição';  
            if (strlen($qtde)         == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a quantidade de vagas';
            if (strlen($local)         == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o Local do Treinamento';
        }

        if (strlen($validade_treinamento) == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a Validade do Treinamento';
        
    }

    /*if (in_array($login_fabrica, array(193))) {
        $treinamento_por      = trim($_GET['treinamento_por']);
        
        if (strtolower($treinamento_tipo_nome) == 'presencial'){
            
            $inicio_inscricao = trim($_GET['inicio_inscricao']);
            $prazo_inscricao  = trim($_GET['prazo_inscricao']);
            $local            = trim($_GET['local']);
              
            if (strlen($inicio_inscricao) == 0 && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o Início da Inscrição';  
            if (strlen($prazo_inscricao)  == 0 && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o Prazo da Inscrição';  
            if (strlen($qtde)             == 0 && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a quantidade de vagas';
        }
    }*/

    if (!in_array($login_fabrica, array(175))){
        $local          = utf8_decode($local);
    }
    $descricao      = utf8_decode($descricao);
    $adicional      = utf8_decode($adicional);
    $titulo         = utf8_decode($titulo);
    $palestrante    = utf8_decode($palestrante);

    $visivel_portal = 'false';
    $codigo_posto = $_GET['xcodigo_posto'];

    if($login_fabrica == 138){ //HD-2930346
        $titulo_antigo          = trim($_GET['titulo_antigo']);
        $linha_antiga           = trim($_GET['linha_antiga']);
        $familia_antiga         = trim($_GET['familia_antiga']);
        $data_inicial_antiga    = trim($_GET['data_inicial_antiga']);
        $data_final_antiga      = trim($_GET['data_final_antiga']);
        $visivel_portal = 'false';
    }

    if (!in_array($login_fabrica, array(117,148,138,169,170,171,193))){
        if (!in_array($login_fabrica, array(175))){
            $cidade = "";
        }
        $palestrante = "";
        $visivel_portal = 'false';
    }

    if(in_array($login_fabrica, array(169,170,193))){
        $visivel_portal = 'false';
        $palestrante = "";
    }

    if ($login_fabrica == 117) {
        if(array_key_exists('visivel_portal', $_GET)){
            $visivel_portal = 'true';
        }else{
            $visivel_portal = 'false';
        }
    }

    if (strlen($titulo) == 0)                                    $msg_erro = 'Favor informar o tema';
    if (in_array($login_fabrica, array(169,170,193))) {
        if (!is_array($_GET['linha'])) {
            $array_linhas[] = $_GET['linha'];
        }

        foreach ($array_linhas as $linha) {
            if ( (strlen($linha)        == 0    && strlen($msg_erro) == 0) AND !in_array($login_fabrica, [1])) $msg_erro = 'Favor informar a linha';
        }
    } else {
        if (!in_array($login_fabrica, array(175))){
            if ( (strlen($linha)        == 0    && strlen($msg_erro) == 0) AND !in_array($login_fabrica, [1])) $msg_erro = 'Favor informar a linha';    
        }
        
    }

    if (in_array($login_fabrica, [175])) {
        //fazer validação
        if (strtolower($treinamento_tipo_nome) == 'presencial' OR empty($treinamento_tipo_nome)){
            if (strlen($data_inicial) == 0    && strlen($msg_erro) == 0) $msg_erro = 'Data não pode ser vazia';
            if (strlen($data_final)   == 0    && strlen($msg_erro) == 0) $msg_erro = 'Data não pode ser vazia';
        }
        
    }else{
        if (strlen($data_inicial) == 0    && strlen($msg_erro) == 0) $msg_erro = 'Data não pode ser vazia';
        if (strlen($data_final)   == 0    && strlen($msg_erro) == 0) $msg_erro = 'Data não pode ser vazia';
        if (strlen($qtde)         == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a quantidade de vagas';
    }

    if (strlen($descricao)    == 0    && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a descrição<br>';
    if ($data_inicial == 'dd/mm/aaaa' && strlen($msg_erro) == 0) $msg_erro = 'Data Inválida';
    if ($data_final   == 'dd/mm/aaaa' && strlen($msg_erro) == 0) $msg_erro = 'Data Inválida';

    if (in_array($login_fabrica, array(169,170,193))){
        if ((strlen($qtde_participante) == 0 && strlen($msg_erro) == 0) OR ($qtde_participante == 0 && strlen($msg_erro) == 0)) $msg_erro = 'Favor informar a quantidade de vagas por posto';

        if ((strlen($qtde_min) == 0 && strlen($msg_erro) == 0) OR ($qtde_min == 0 && strlen($msg_erro) == 0)) $msg_erro = 'Favor informar a quantidade mínima de participantes';

        if (strlen($local) == 0 && strlen($msg_erro) == 0) $msg_erro = 'Favor informar o Local do treinamento';
    }

    if ($login_fabrica == 117) {
        if (strlen($cidade) == 0 && strlen($msg_erro)==0)        $msg_erro = "Favor informar uma região ou estado, e uma cidade";
        if (strlen($familia) == 0) {
            $familia = 'null';
        }
    }

    if (strlen($msg_erro) == 0) {

        //--====== Formata data inicial do treinamento =====================================================
        if (in_array($login_fabrica, array(148,169,170,175,193))){
            $data_inicial = fnc_formata_data_hora_pg($data_inicial);
            $data_inicial = str_replace("'", "", $data_inicial);

            if (strtotime($data_inicial)){
                $aux_data_inicial = $data_inicial;
            }else{
                $msg_erro = "Data Inválida";
            }
        }else{
            $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro = "Data Inválida";//pg_last_error($con) ;
            } else {
                $aux_data_inicial = @pg_fetch_result($fnc,0,0);
            }
        }

        //--====== Formata data final do treinamento =======================================================
        if (in_array($login_fabrica, array(148,169,170,175,193))){
            $data_final = fnc_formata_data_hora_pg($data_final);
            $data_final = str_replace("'", "", $data_final);

            if (strtotime($data_final)){
                $aux_data_final = $data_final;
            }else{
                $msg_erro = "Data Inválida";
            }
        }else{
            $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro = "Data Inválida";//pg_last_error($con) ;
            } else {
                $aux_data_final = @pg_fetch_result($fnc,0,0);
            }
        }

        if(in_array($login_fabrica, array(169,170,193))){
            //--====== Formata data prazo final do treinamento =======================================================
            $fnc = @pg_query($con,"SELECT fnc_formata_data('$prazo_final_inscricao')");

            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro = "Data Inválida";//pg_last_error($con) ;
            } else {
                $prazo_final_inscricao = @pg_fetch_result($fnc,0,0);
            }
        }

        if (in_array($login_fabrica, array(175))){
            //--====== Formata data inicial da inscrição do treinamento =======================================================
            $fnc_inscricao = @pg_query($con,"SELECT fnc_formata_data('$inicio_inscricao')");
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro .= "Data para Início das Inscrições Inválida";
            } else {
                $aux_inicio_inscricao = @pg_fetch_result($fnc_inscricao,0,0);
            }

            //--====== Formata data prazo inscrição do treinamento =======================================================
            $fnc_prazo = @pg_query($con,"SELECT fnc_formata_data('$prazo_inscricao')");
            if (strlen(pg_last_error($con)) > 0) {
                $msg_erro .= "<br /> Data para Prazo das Inscrições Inválida";
            } else {
                $aux_prazo_inscricao = @pg_fetch_result($fnc_prazo,0,0);
            }
        }

        //--====== Valida se as datas do treinamento =======================================================

        function pg_check($con, $sql) {
            $valida = trim(pg_fetch_result(pg_query($con, $sql), 0, 0));
            return ($valida == 't'); // true ou false
        }

        if (!in_array($login_fabrica, array(169,170,193))) {
            $sql =  "SELECT '$aux_data_inicial'::DATE >= CURRENT_DATE ";
            $valida  = pg_check($con, $sql);
            if (!$valida)
                $msg_erro = ($id_treinamento > 0) ? "Treinamento em andamento, não é possível alterar" : "Data Inicial deve ser maior ou igual que data atual";
        }

        if (!in_array($login_fabrica, array(169,170,193))) {
            $sql = "SELECT '$aux_data_final'::DATE >= CURRENT_DATE ";
            $valida = pg_check($con, $sql);
            if (!$valida)
                $msg_erro = "Data Final deve ser maior ou igual que data atual";
        }
        $sql = "SELECT '$aux_data_inicial'::DATE <= '$aux_data_final'::date ";
        $valida = pg_check($con, $sql);
        if (!$valida)
            $msg_erro = "Data Inicial deve ser anterior à Data Final";

        if (!in_array($login_fabrica, array(175))){
            if ($prazo_inscricao) {
                if (!in_array($login_fabrica, array(169,170,193))) {
                    $sql = "SELECT '$prazo_inscricao'::DATE >= CURRENT_DATE ";
                    $valida = pg_check($con, $sql);
                    if (!$valida)
                        $msg_erro = "O Prazo de inscrição deve ser posterior à data atual";
                }

                // Prazo para inscrição deve ser anterior à data de início 
                if (in_array($login_fabrica, array(169,170,193))) {
                    $prazoInicial = DateTime::createFromFormat('d/m/Y', $prazo_inscricao);                                                    
                    $prazo_inscricao = $prazoInicial->format('Y-m-d');
                    $sql = "SELECT '" . $prazo_inscricao . "'::DATE < '$aux_data_inicial'::DATE";                     
                    $valida = pg_check($con, $sql);
                    if (!$valida)
                        $msg_erro = "O Prazo de Inscrição deve ser anterior à Data de Início do treinamento";
                }
            }            
        }
    }

    if (!in_array($login_fabrica, array(148,169,170,175,193))) {
        if ($prazo_inscricao and !$vagas_min){
            $msg_erro .= iif(($msg_erro != ''), '<br />')."Informar a lotação mínima para o treinamento a ser cancelado em $prazo_inscricao se não preencher a quantidade";
        }

        if (in_array($login_fabrica, [1])) {
            if($vaga_posto != ""){
                if($vaga_posto > $qtde){
                    $msg_erro .= iif(($msg_erro != ''), '<br />')."Quantidade de vagas por posto não pode ser maior do que o número de vagas";
                }
            }
        }
    } else {
        if($qtde_participante != ""){
            if($qtde_participante > $qtde){
                $msg_erro .= iif(($msg_erro != ''), '<br />')."Quantidade de vagas por posto não pode ser maior do que o número de vagas";
            }
        }
    }

    if ($login_fabrica == 171) {
        $visivel_portal = 'false';
    }

    if (strlen($msg_erro) > 0) {
        //$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
        $msg = $msg_erro;
    } else{
        $listar = "ok";
    }

    if ($listar == "ok") {
        $comunicatorMirror = new ComunicatorMirror();

        $res = @pg_query($con,"BEGIN TRANSACTION");

        $familia = (strlen($familia)==0) ? 'null' : "$familia";
        $linha = (strlen($linha)==0 AND in_array($login_fabrica, [1])) ? 'null' : "$linha";

        $descricao = pg_escape_literal($con, $descricao);
        $adicional = pg_escape_literal($con, $adicional);
        $titulo    = pg_escape_literal($con, $titulo);
        if (!in_array($login_fabrica, array(175))){
            $local     = pg_escape_literal($con, $local);
        }

        if ($id_treinamento > 0) {
            if ($login_fabrica==117) {
                $campo_cidade = "cidade       = $cidade,";
                $campo_palestrante = 'palestrante = '."'$palestrante'".',';
            } else if (in_array($login_fabrica, array(138,169,170,193)) AND strlen($cidade) > 0) {
                $campo_cidade = "cidade       = $cidade,";

                $campo_treinamento_tipo = (!empty($treinamento_tipo)) ? "treinamento_tipo = $treinamento_tipo," : "treinamento_tipo = null,";

            } else if (in_array($login_fabrica, array(175))){
                $campo_treinamento_tipo = (!empty($treinamento_tipo)) ? "treinamento_tipo = $treinamento_tipo," : "treinamento_tipo = null,";
            }else {
                $campo_cidade = '';
                $campo_palestrante = '';
            }                     
            $campo_prazo_inscricao = strlen($prazo_inscricao)>0 ? "prazo_inscricao = '$prazo_inscricao'," : '';
            $campo_vagas_min = strlen($vagas_min)>0 ? "vagas_min   = '$vagas_min'," : '';

            $campo_tipo_posto = "";
            $campo_qtde_participante = "";
            $campo_qtde_min_participante = "";
	    $campo_categoria_posto = "";

            $campo_estado     = '';

            if (in_array($login_fabrica, [193]) && strlen($estado) > 0) {
                $campo_estado = "estado       = '$estado',";
            }

            if (in_array($login_fabrica, array(169,170,175,193))) {
                if (!in_array($login_fabrica, array(175))){
                    $aux_tipo_posto = implode(",",$tipo_posto);
		    $campo_tipo_posto = "";
		    $valores_parametros_add  = json_encode(array("tipo_posto" => $tipo_posto, "carga_horaria" => $carga_horaria));
                    $campos_add              = ", parametros_adicionais = '{$valores_parametros_add}'";
                    $campo_qtde_participante = " qtde_participante = $qtde_participante,";
                    $campo_qtde_min_participante = " vagas_min = $qtde_min,";    
                }   
                $campo_linha = "linha       = null,";  
            } else {
                $campo_linha = "linha       = $linha,";
	    }

	    if (in_array($login_fabrica, array(175))){

            $campo_categoria = ", categoria = '$treinamento_por' ";
            $campo_validade        = strlen($validade_treinamento)>0 ? ", validade_treinamento = $validade_treinamento " : '';

            if (strtolower($treinamento_tipo_nome) == 'presencial'){
                $campo_cidade           = "cidade       = $cidade,";
                $campo_inicio_inscricao = strlen($inicio_inscricao)>0 ? ", inicio_inscricao = '$aux_inicio_inscricao' " : '';
                $campo_prazo_inscricao  = strlen($prazo_inscricao)>0 ? " prazo_inscricao = '$aux_prazo_inscricao', " : '';
                $campo_vagas            = "vagas       = $qtde,";
                $local                  = pg_escape_literal($con, $local);
                $local                  = utf8_decode($local);
            }

            $local = (strlen($local) > 0) ? $local : "NULL";

            $ativo             = trim($_GET['ativo']);
            $finalizado        = trim($_GET['finalizado']);
            $x_ativo           = (strlen($ativo) > 0) ? 't' : 'f';
            $x_finalizado      = (strlen($finalizado) > 0) ? 't' : 'f';
            $campo_ativo       = ", ativo = '$x_ativo' ";
            $campo_finalizado  = ($x_finalizado == 't') ? ", data_finalizado = current_timestamp " : ", data_finalizado = NULL ";
        }else{
            $campo_vagas = "vagas       = $qtde,";
        }

        /*if (in_array($login_fabrica, [193])) {
            $campo_categoria = ", categoria = '$treinamento_por' ";
            $campo_validade  = strlen($validade_treinamento)>0 ? ", validade_treinamento = $validade_treinamento " : '';    
            $campo_vagas     = "vagas       = $qtde,";

            if (strtolower($treinamento_tipo_nome) == 'presencial'){
                $campo_inicio_inscricao = strlen($inicio_inscricao)>0 ? ", inicio_inscricao = '$aux_inicio_inscricao' " : '';
                $campo_prazo_inscricao  = strlen($prazo_inscricao)>0 ? " prazo_inscricao = '$aux_prazo_inscricao', " : '';
            } 

            $qtde_participante        = (empty($qtde_participante)) ? "null" : $qtde_participante;
            $campo_qtde_participante  = " qtde_participante = $qtde_participante,";
        }
*/
        if (in_array($login_fabrica, [1])) {
            $campo_vaga_posto = " vaga_posto = $vaga_posto,";     

            $parametros_adicionais = [];

            $linha           = $_GET['linha_bd'];
            $familia         = $_GET['familia_bd'];
            $marca           = $_GET['marca_bd'];
            $tipo_posto      = $_GET['tipo_posto_bd'];
            $categoria_posto = $_GET['categoria_bd'];
            $local     = utf8_encode($local); 
            $descricao = utf8_encode($descricao);
            $titulo    = utf8_encode($titulo);

            if (!empty($familia)) {
                if(is_array($familia)) {
                    $parametros_adicionais['familia'] = $familia;
                } else {
                    $campos_familia = "familia = $familia ,";
                }
            }

            if (!empty($tipo_posto)) {
                $parametros_adicionais['tipo_posto'] = $tipo_posto;
            }

            if (!empty($marca)) {
                $parametros_adicionais['marca'] = $marca;
            }

            if (!empty($linha)) {
                $parametros_adicionais['linha'] = $linha;
            }

            if (!empty($categoria_posto)) {
                $parametros_adicionais['categoria_posto'] = $categoria_posto;
            }

            $valores_parametros_add = json_encode($parametros_adicionais); 

            $campos_add = ", parametros_adicionais = '{$valores_parametros_add}'";
            
        } else {
           $campos_familia =  " familia     = $familia, ";
        }
            $sql = "UPDATE tbl_treinamento SET
                            titulo      = $titulo,
                            data_inicio = '$aux_data_inicial',
                            data_fim    = '$aux_data_final',
                            $campo_linha
                            $campo_vagas
                            $campos_familia
                            descricao   = $descricao,
                            adicional   = $adicional,
                            admin       = $login_admin,
                            local       = $local,
                            $campo_cidade
                            $campo_estado
                            $campo_tipo_posto
                            $campo_categoria_posto
                            $campo_qtde_participante
                            $campo_qtde_min_participante
                            $campo_vaga_posto
                            $campo_marca
                            $campo_prazo_inscricao
                            $campo_treinamento_tipo
                            $campo_vagas_min
                            $campo_palestrante
                            visivel_portal = $visivel_portal
                            $campo_categoria
                            $campo_validade
                            $campo_inicio_inscricao
                            $campo_ativo
                            $campo_finalizado
                            $campos_add 
                     WHERE  treinamento = $id_treinamento
                       AND  fabrica     = $login_fabrica";                       
            $res = pg_query($con, $sql);
            $sucesso .= "Treinamento alterado com sucesso!";

            if (in_array($login_fabrica, array(169,170,193))) {

                if(count($cidades_treinamento) > 0){
                    foreach ($cidades_treinamento as $cidade) {
                        $sql = "INSERT INTO tbl_treinamento_cidade (treinamento, cidade) VALUES($id_treinamento, $cidade);";
                        $res = pg_query($con, $sql);
                        $msg_erro = pg_last_error($con);
                        if($msg_erro != false){
                            break;
                        }
                    }
                }

                if (!empty($estado_participante)){
                    $estados_participantes = explode(',', $estado_participante);
                    if (count($estados_participantes) > 0){
                        foreach ($estados_participantes as $key => $value) {
                            if (!empty($value)){
                                $sql = "INSERT INTO tbl_treinamento_cidade (treinamento, estado) VALUES($id_treinamento, '$value');";
                                $res = pg_query($con, $sql);
                                $msg_erro = pg_last_error($con);
                                if($msg_erro != false){
                                    break;
                                }
                            }
                        }
                    }
                }

                foreach ($promotores_treinamento as $promotor) {
                    $sql = "INSERT INTO tbl_treinamento_promotor (treinamento, promotor_treinamento) VALUES($id_treinamento, $promotor)";
                    $res = pg_query($con, $sql);
                    $msg_erro = pg_last_error($con);
                    if($msg_erro != false){
                        break;
                    }
                }

                if($instrutor != "" && $msg_erro == ""){
                    $sql = "SELECT treinamento_instrutor, instrutor_treinamento FROM tbl_treinamento_instrutor WHERE treinamento = $id_treinamento";
                    $res = pg_query($con,$sql);
                    $resultInst = pg_fetch_all($res);
                    $alterouInstrutor = true;

                    foreach ($resultInst as $chave => $v) {
                        if ($instrutor != $v['instrutor_treinamento']) {
                            $sql_delete = "DELETE FROM tbl_treinamento_instrutor WHERE treinamento = $id_treinamento AND instrutor_treinamento = ".$v['instrutor_treinamento'];
                            $res = pg_query($con,$sql_delete);
                        } else {
                            $alterouInstrutor = false;
                        }
                    }

                    if ($alterouInstrutor) {
                        $sql_insert = "INSERT INTO tbl_treinamento_instrutor (treinamento, instrutor_treinamento) VALUES($id_treinamento, $instrutor)";
                        $res = pg_query($con, $sql_insert);
                        $msg_erro = pg_last_error($con);
                    }

                }

                if (in_array($login_fabrica, array(169,170,193))) {

                    ############            UPDATE               ############
                    ############ CADASTRO DE TÉCNICOS CONVIDADOS ############
                        $delete_tecnico = "DELETE FROM tbl_treinamento_posto
                                            WHERE treinamento = {$id_treinamento}
                                            AND posto IS NULL";
                        $res_delete     = pg_query($con,$delete_tecnico);
                        $msg_erro       = pg_last_error($con);

                        if (!strlen($msg_erro) > 0){
                            $tecnicos_ids       = $_GET['tecnico_convidado'];
                        
                            if (!is_array($tecnicos_ids)){
                                $tecnicos_ids[] = $tecnicos_ids;
                            }

                            foreach ($tecnicos_ids as $id)
                            {
                                if (!empty($id))
                                {
                                    $select_tecnico = "SELECT 
                                                            nome,
                                                            cpf,
                                                            email
                                                        FROM tbl_tecnico
                                                            WHERE fabrica = {$login_fabrica}
                                                        AND tecnico = {$id}";
                                    $res_select     = pg_query($con, $select_tecnico);
                                    
                                    if (pg_num_rows($res_select) > 0)
                                    {
                                        $tecnico_nome   = pg_fetch_result($res_select,0,nome);              
                                        $tecnico_email  = pg_fetch_result($res_select,0,email);
                                        $tecnico_cpf    = pg_fetch_result($res_select,0,cpf);
                                        
                                        $select_check   = "SELECT
                                                                tecnico,
                                                                treinamento
                                                            FROM tbl_treinamento_posto
                                                                WHERE tecnico  = {$id}
                                                            AND treinamento    = {$id_treinamento}";
                                        $res_check      = pg_query($con, $select_check);
                                        if (pg_num_rows($res_check) > 0)
                                        {
                                            $msg_erro = "Técnico '{$tecnico_nome}' já esta cadastrado";
                                        }else
                                        {
                                            $insert_tenico  = "INSERT INTO tbl_treinamento_posto(
                                                                treinamento,
                                                                tecnico,
                                                                tecnico_nome,
                                                                tecnico_email,
                                                                tecnico_cpf
                                                            ) VALUES (
                                                                {$id_treinamento},
                                                                {$id},
                                                                '{$tecnico_nome}',
                                                                '{$tecnico_email}',
                                                                '{$tecnico_cpf}'
                                                        );";
                                            $res_insert     = pg_query($con, $insert_tenico);
                                            $msg_erro       = pg_last_error($con);  
                                        }                           
                                    }
                                }       
                            }    
                        }
                }
            }

            if (in_array($login_fabrica, array(169,170,175,193))){
                 /* ATUALIZANDO AS LINHAS */
                $linhas_array = $_GET['linha'];

                if (!is_array($linhas_array)) {
                    $linhas[] = $linhas_array;

                } else { $linhas = $linhas_array; }

                /* Delete */
                $sql_delete_linha = "DELETE FROM tbl_treinamento_produto
                                    WHERE fabrica = {$login_fabrica}
                                        AND treinamento = {$id_treinamento}";
                $query_delete_linha = pg_query($con, $sql_delete_linha);

                /* "Update" => que no caso, um insert */
                foreach ($linhas as $linha) {
                    if (!empty($linha)){
                        $sql_insert_linha = "INSERT INTO tbl_treinamento_produto(
                                            fabrica,
                                            treinamento,
                                            linha
                                        ) VALUES (
                                            {$login_fabrica},
                                            {$id_treinamento},
                                            {$linha}
                                        )";
                        $query_insert_linha = pg_query($con, $sql_insert_linha);
                        $msg_erro = pg_last_error();
                    }
                }

                if (in_array($login_fabrica, array(175))){
                    /* ATUALIZANDO OS PRODUTOS */          
                    $produtos_array  = $_GET['produto'];      
                    
                    if (!is_array($produtos_array)) {
                        $produtos[] = $produtos_array;

                    } else { $produtos = $produtos_array; }

                    foreach ($produtos as $produto) {
                        if (!empty($produto)){
                            $select_linha_produto = "SELECT linha FROM tbl_produto WHERE produto = {$produto}";
                            $res_linha_produto    = pg_query($con, $select_linha_produto);
                            $msg_erro             = pg_last_error();
                            if (pg_num_rows($res_linha_produto) > 0) {
                                $linha_produto        = pg_fetch_result($res_linha_produto, 0, 'linha');     
                            }

                            $sql_insert_produto = "INSERT INTO tbl_treinamento_produto(
                                                fabrica,
                                                treinamento,
                                                produto,
                                                linha
                                            ) VALUES (
                                                {$login_fabrica},
                                                {$id_treinamento},
                                                {$produto},
                                                {$linha_produto}
                                            )";
                            $query_insert_produto = pg_query($con, $sql_insert_produto);
                            $msg_erro = pg_last_error();
                        }
                    }
                }
            }

            if ($login_fabrica == 138) { //HD-2930346
                if($titulo_antigo <> $titulo OR $linha_antiga <> $linha OR $familia_antiga <> $familia OR $data_inicial_antiga <> $data_inicial OR $data_final_antiga <> $data_final){
                    $sql_t = "SELECT DISTINCT posto FROM tbl_treinamento_posto WHERE treinamento = $id_treinamento AND ativo IS TRUE AND confirma_inscricao IS TRUE";
                    $res_t = pg_query($con, $sql_t);
                    $tituloc = str_replace("'","",$titulo);
                    if(pg_num_rows($res_t) > 0){
                        $rows = pg_num_rows($res_t);
                        for ($x=0; $x < $rows ; $x++) {
                            $id_posto = pg_fetch_result($res_t, $x, 'posto');
                            $sql_insert = "INSERT INTO tbl_comunicado(
                                                    mensagem,
                                                    tipo,
                                                    fabrica,
                                                    descricao,
                                                    ativo,
                                                    posto,
                                                    obrigatorio_site
                                                )VALUES(
                                                    'Houve alterações no treinamento: $tituloc, acesse o link Treinamentos no menu inicial para verificar as alteraçõe.',
                                                    'Comunicado Inicial',
                                                    $login_fabrica,
                                                    'ATENÇÃO: Treinamento: $tituloc.',
                                                    true,
                                                    $id_posto,
                                                    true
                                                );";
                            $res_insert = pg_query($con, $sql_insert);
                        }
                    }
                }
            }
        } else {
            if ($login_fabrica == 117) {
                $campo_cidade = 'cidade               ,';
                $campo_palestrante = "palestrante ,";

                $valor_cidade = "'$cidade'           ,";
                $valor_palestrante = "'$palestrante' ,";
            } else if(in_array($login_fabrica,array(138,169,170,171,193)) AND strlen($cidade) > 0) {
                $campo_cidade = 'cidade               ,';
                $valor_cidade = "'$cidade'           ,";
            } else {
                $campo_cidade = '';
                $campo_palestrante = "";
                $valor_cidade = "";
                $valor_palestrante = "";
            }

			if (in_array($login_fabrica, array(169,170,193)) AND strlen($estado) > 0){
				$campo_estado = 'estado ,';
				$valor_estado = "'$estado' ,";
			}else{
				$campo_estado = "";
				$valor_estado = "";
			}

            if($login_fabrica == 138 AND strlen($local) == 0){
                $local = "";
            }
            $campo_prazo_inscricao = strlen($prazo_inscricao)>0 ? "prazo_inscricao," : '';
            $campo_vagas_min = strlen($vagas_min)>0 ? "vagas_min," : '';

            $valor_prazo_inscricao = strlen($prazo_inscricao)>0 ? "'$prazo_inscricao'," : '';
            $valor_vagas_min = strlen($vagas_min)>0 ? "'$vagas_min'," : '';

			$campo_tipo_posto = "";
			$valor_tipo_posto = "";
			if(in_array($login_fabrica, array(169,170,193))){
				$campo_tipo_posto = "";
                $valor_tipo_posto = "";
                $aux_tipo_posto   = implode(",",$tipo_posto);
                
                $campo_parametros_add  = ", parametros_adicionais"; 
                $valor_parametros_add  = "'" .json_encode(array("tipo_posto" => $tipo_posto, "carga_horaria" => $carga_horaria)) . "'";
                $virgula = ",";

                $campo_treinamento_tipo = "treinamento_tipo,";
                $valor_treinamento_tipo = (empty($treinamento_tipo)) ? null : $treinamento_tipo;
                $valor_treinamento_tipo .= ",";

                $campo_qtde_participante = "qtde_participante,";
                $valor_qtde_participante = "$qtde_participante,";
            
                $campo_qtde_min_participante = "vagas_min,";
                $valor_qtde_min_participante = "$qtde_min,";
			}

            /*if (in_array($login_fabrica, [193])) {
                $campo_validade         = strlen($validade_treinamento)>0 ? ", validade_treinamento" : '';
                $valor_validade         = strlen($validade_treinamento)>0 ? ", $validade_treinamento" : '';
                $campo_treinamento_tipo = "treinamento_tipo,";
                $valor_treinamento_tipo = "$treinamento_tipo,";
                
                if (strtolower($treinamento_tipo_nome) == 'presencial'){
                    $campo_inicio_inscricao = strlen($inicio_inscricao)>0 ? ", inicio_inscricao" : '';
                    $valor_inicio_inscricao = strlen($inicio_inscricao)>0 ? ", '$aux_inicio_inscricao' " : '';
                    $campo_prazo_inscricao  = strlen($prazo_inscricao)>0 ? "prazo_inscricao," : '';                    
                    $valor_prazo_inscricao  = strlen($prazo_inscricao)>0 ? "'$aux_prazo_inscricao'," : '';
                }

                $qtde        = (empty($qtde)) ? "null" : $qtde;
                $campo_vagas = "vagas             ,";
                $valor_vagas = "$qtde            ,";
            }*/

            if(in_array($login_fabrica, array(175))){
                $adicional = "null";
                $campo_treinamento_tipo = "treinamento_tipo,";
                $valor_treinamento_tipo = "$treinamento_tipo,";

                $campo_categoria        = ", categoria";
                $valor_categoria        = ", '$treinamento_por'";

                $campo_validade         = strlen($validade_treinamento)>0 ? ", validade_treinamento" : '';
                $valor_validade         = strlen($validade_treinamento)>0 ? ", $validade_treinamento" : '';

                if (strtolower($treinamento_tipo_nome) == 'presencial'){
                    

                    $campo_inicio_inscricao = strlen($inicio_inscricao)>0 ? ", inicio_inscricao" : '';
                    $valor_inicio_inscricao = strlen($inicio_inscricao)>0 ? ", '$aux_inicio_inscricao' " : '';

                    $campo_prazo_inscricao = strlen($prazo_inscricao)>0 ? "prazo_inscricao," : '';                    
                    $valor_prazo_inscricao = strlen($prazo_inscricao)>0 ? "'$aux_prazo_inscricao'," : '';   

                    $campo_cidade = 'cidade               ,';
                    $valor_cidade = "$cidade          ,";
                    
                    $campo_estado = 'estado ,';
                    $valor_estado = "'$estado' ,";

                    if (empty($local)){
                        $local        = "null";
                    }else{
                        $local        = $_GET['local'];
                        $local        = utf8_decode($local);
                        $local        = pg_escape_literal($con, $local);
                    }
                    
                    
                    $campo_vagas  = "vagas             ,";
                    $valor_vagas  = "$qtde            ,";
                }else if (strtolower($treinamento_tipo_nome) == 'online'){
                    $local        = "null";
                }


                $ativo             = trim($_GET['ativo']);
                $finalizado        = trim($_GET['finalizado']);
                $x_ativo           = (strlen($ativo) > 0) ? 't' : 'f'; 
                $x_finalizado      = (strlen($finalizado) > 0) ? 't' : 'f'; 
               
                $campo_finalizado = ", data_finalizado ";
                $valor_finalizado  = ($x_finalizado == 't') ? ", current_timestamp " : ", NULL ";
                $campo_ativo      = ", ativo ";
                $valor_ativo      = ", '$x_ativo' ";         
            }else{
                $campo_vagas = "vagas             ,";
                $valor_vagas = "$qtde            ,";
            }

			if (in_array($login_fabrica, [1])) {
                $familia_campo = "";
                $familia_valor = "";
				$campo_vaga_posto = " vaga_posto,";
				$valor_vaga_posto = " $vaga_posto,";
                
                $parametros_adicionais = [];

                $linha           = $_GET['linha_bd'];
                $familia         = $_GET['familia_bd'];
                $marca           = $_GET['marca_bd'];
                $tipo_posto      = $_GET['tipo_posto_bd'];
                $categoria_posto = $_GET['categoria_bd'];
                $local     = utf8_encode($local); 
                $descricao = utf8_encode($descricao);
                $titulo    = utf8_encode($titulo);

                if (!empty($familia)) {
                    $parametros_adicionais['familia'] = $familia;
                }

                if (!empty($tipo_posto)) {
                    $parametros_adicionais['tipo_posto'] = $tipo_posto;
                }

                if (!empty($marca)) {
                    $parametros_adicionais['marca'] = $marca;
                }

                if (!empty($linha)) {
                    $parametros_adicionais['linha'] = $linha;
                }

                if (!empty($categoria_posto)) {
                    $parametros_adicionais['categoria_posto'] = $categoria_posto;
                }

                $campo_parametros_add = ", parametros_adicionais"; 
                $virgula = ",";
                $valor_parametros_add = "'".json_encode($parametros_adicionais)."'";
            } else {
                $familia_campo = " familia           ,";
                $familia_valor = "$familia           ,";
                if (!in_array($login_fabrica, [169,170,193])){
                    $virgula = "";    
                }                
            }
            
            if (!in_array($login_fabrica, array(1,169,170,175,193)))
            {
                $campo_linha = "linha             ,";
                $valor_linha = "$linha            ,";
            }
		if ($login_fabrica == 148){
		 $visivel_portal = 'true';
		}
			$sql = "INSERT INTO tbl_treinamento (
					titulo            ,
					data_inicio       ,
					data_fim          ,
					$campo_linha
					$campo_vagas
                    $familia_campo
					descricao         ,
					adicional         ,
					fabrica           ,
					admin             ,
					local 			  ,
					$campo_cidade
					$campo_estado
					$campo_prazo_inscricao
					$campo_vagas_min
					$campo_palestrante
					$campo_treinamento_tipo
					$campo_qtde_participante
                    $campo_qtde_min_participante
					$campo_tipo_posto
					$campo_categoria_posto
					$campo_vaga_posto
					$campo_marca
                    $campo_tecnico
					visivel_portal
                    $campo_categoria
                    $campo_validade
                    $campo_inicio_inscricao
                    $campo_ativo
                    $campo_finalizado
                    $campo_parametros_add
				)VALUES(
					$titulo           ,
					'$aux_data_inicial',
					'$aux_data_final'  ,
					$valor_linha
					$valor_vagas
                    $familia_valor
					$descricao       ,
					$adicional      ,
					'$login_fabrica'   ,
					'$login_admin'     ,
					$local           ,
					$valor_cidade
					$valor_estado
					$valor_prazo_inscricao
					$valor_vagas_min
					$valor_palestrante
					$valor_treinamento_tipo
					$valor_qtde_participante
                    $valor_qtde_min_participante
					$valor_tipo_posto
					$valor_categoria_posto
					$valor_vaga_posto
					$valor_marca
                    $valor_tecnico
					$visivel_portal
                    $valor_categoria
                    $valor_validade
                    $valor_inicio_inscricao
                    $valor_ativo
                    $valor_finalizado
                    $virgula
                    $valor_parametros_add
				)";

			$res_treinamento = pg_query($con,$sql);
			if(pg_last_error($con)){
				$msg_erro = pg_last_error($con);
				$msg_erro .= "<br>$sql";
			}

			$sucesso .= "Gravado com Sucesso";

			if(in_array($login_fabrica, array(169,170,193)) && $msg_erro == false){
				$res_treina_posto = pg_query ($con,"SELECT CURRVAL ('seq_treinamento') as treinamento_id");
				$treinamento_id = pg_fetch_result($res_treina_posto,0,treinamento_id);

				if(count($cidades_treinamento) > 0){
					foreach ($cidades_treinamento as $cidade) {
						$sql = "INSERT INTO tbl_treinamento_cidade (treinamento, cidade) VALUES($treinamento_id, $cidade);";
						$res = pg_query($con, $sql);
						$msg_erro = pg_last_error($con);
						if($msg_erro != false){
							break;
						}
					}
				}

				if (!empty($estado_participante)){
					$estados_participantes = explode(',', $estado_participante);

					if (count($estados_participantes) > 0){
						foreach ($estados_participantes as $key => $value) {
							if (!empty($value)){
								$sql = "INSERT INTO tbl_treinamento_cidade (treinamento, estado) VALUES($treinamento_id, '$value');";
								$res = pg_query($con, $sql);
								$msg_erro = pg_last_error($con);
								if($msg_erro != false){
									break;
								}
							}
						}
					}
				}

				// if($estado_participante != ""){
				// 	$sql = "UPDATE tbl_treinamento SET estado = '$estado_participante' WHERE treinamento = $treinamento_id";
				// 	$res = pg_query($con, $sql);
				// 	$msg_erro = pg_last_error($con);
				// }
				// var_dump($msg_erro);exit;
				foreach ($promotores_treinamento as $promotor) {
					$sql = "INSERT INTO tbl_treinamento_promotor (treinamento, promotor_treinamento) VALUES($treinamento_id, $promotor)";
					$res = pg_query($con, $sql);
					$msg_erro = pg_last_error($con);
					if($msg_erro != false){
						break;
					}
				}

				if($instrutor != "" && $msg_erro == ""){
					$sql = "INSERT INTO tbl_treinamento_instrutor (treinamento, instrutor_treinamento) VALUES($treinamento_id, $instrutor)";
					$res = pg_query($con, $sql);
					$msg_erro = pg_last_error($con);
				}
			}
		}

		//$res = pg_query($con, $sql);
		//$msg_erro = pg_last_error($con);

		if(in_array($login_fabrica, array(1,117,138,169,170,175,193)) AND $msg_erro == false ){ //HD-3261932
			if($id_treinamento > 0){
				$treinamento_id = $id_treinamento;
			}else{
				$res_treina_posto = pg_query ($con,"SELECT CURRVAL ('seq_treinamento') as treinamento_id");
				$treinamento_id = pg_fetch_result($res_treina_posto,0,treinamento_id);
			}

            if (!in_array($login_fabrica, array(175)))
            {
                if(!empty($tipo_posto) && !in_array($login_fabrica, array(169,170,193))) {
                    $cond_tipo_posto = " AND tbl_posto_fabrica.tipo_posto = {$tipo_posto} ";
                }
                if(count($codigo_posto) > 0){
                    foreach ($codigo_posto as $key => $value) {
                        $sql_posto = "
                                SELECT tbl_posto_fabrica.posto
                                FROM tbl_posto_fabrica
                                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica
                                WHERE tbl_posto_fabrica.codigo_posto = '$value' 
                                $cond_tipo_posto";
                        $res_posto = pg_query($con, $sql_posto);
                        $msg_erro = pg_last_error($con);

                        if(pg_num_rows($res_posto) > 0){
                            $posto_id = pg_fetch_result($res_posto, 0, 'posto');

                            $sql_verifica = "SELECT treinamento_posto FROM tbl_treinamento_posto
                                                WHERE treinamento = $treinamento_id AND posto = $posto_id";
                            $res_verifica = pg_query($con, $sql_verifica);

                            if(pg_num_rows($res_verifica) == 0){
                                $sqlInsert = "INSERT INTO tbl_treinamento_posto (treinamento,posto)VALUES($treinamento_id,$posto_id)";
                                $resInsert = pg_query($con, $sqlInsert);
                                $msg_erro = pg_last_error($con);
                            }
                        }
                    }
                }
            }

            /************************* ENVIA E-MAIL SE O TREINAMENTO FOR DESATIVADO *************************/
            if (in_array($login_fabrica, array(175))){
                if ($x_ativo == 'f'){
                    $sql_tecnicos = "SELECT 
                                        tbl_tecnico.tecnico,
                                        tbl_tecnico.email,
                                        tbl_treinamento.titulo,
                                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio
                                    FROM tbl_treinamento_posto
                                         JOIN tbl_tecnico     ON tbl_tecnico.tecnico         = tbl_treinamento_posto.tecnico
                                         JOIN tbl_treinamento ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
                                    WHERE tbl_treinamento_posto.treinamento  = {$treinamento_id}
                                    AND   tbl_treinamento.fabrica            = {$login_fabrica}
                                    AND   tbl_treinamento_posto.tecnico IS NOT NULL";
                    $res_tecnicos = pg_query($con,$sql_tecnicos);
                    $msg_erro     = pg_last_error($con);

                    if (pg_num_rows($res_tecnicos) > 0){
                        for ($i=0; $i<pg_num_rows($res_tecnicos); $i++){
                            $titulo_treinamento = pg_fetch_result($res_tecnicos,0,'titulo');
                            $data_inicio        = pg_fetch_result($res_tecnicos,0,'data_inicio');
                            $tecnico            = pg_fetch_result($res_tecnicos,0,'tecnico');
                            $tecnico_email      = pg_fetch_result($res_tecnicos,0,'email');

                            $titulo_email  = "Cancelamento do Treinamento";
                            $corpo_email   = "O treinamento {$titulo_treinamento} que seria realizado no dia {$dia_treinamento} foi cancelado pelo fabricante.";

                            if (!empty($tecnico_email)){
                                try {
                                    $comunicatorMirror->post($tecnico_email, utf8_encode("$titulo_email"), utf8_encode("$corpo_email"), "smtp@posvenda");
                                } catch (\Exception $e) {
                                }
                            }else{
                                $msg_erro .= "Email com informações de cancelamento do treinamento, não enviado para os técnicos inscritos.";
                            }
                        }
                    }
                }
            }
        }
        //$res = pg_query($con, $sql);
        //$msg_erro = pg_last_error($con);

        if (in_array($login_fabrica, array(169,170,175,193))){
            if (!in_array($login_fabrica, array(175))){
                ############            INSERT               ############
                ############ CADASTRO DE TÉCNICOS CONVIDADOS ############
                $tecnicos_ids       = $_GET['tecnico_convidado'];
                
                if (!is_array($tecnicos_ids)){
                    $tecnicos_ids[] = $tecnicos_ids;
                }

                foreach ($tecnicos_ids as $id)
                {
                    if (!empty($id))
                    {
                        $select_tecnico = "SELECT 
                                                nome,
                                                cpf,
                                                email
                                            FROM tbl_tecnico
                                                WHERE fabrica = {$login_fabrica}
                                            AND tecnico = {$id}";
                        $res_select     = pg_query($con, $select_tecnico);

                        if (pg_num_rows($res_select) > 0)
                        {
                            $tecnico_nome   = pg_fetch_result($res_select,0,nome);              
                            $tecnico_email  = pg_fetch_result($res_select,0,email);
                            $tecnico_cpf    = pg_fetch_result($res_select,0,cpf);
                            
                            $select_check   = "SELECT
                                                    tecnico,
                                                    treinamento
                                                FROM tbl_treinamento_posto
                                                    WHERE tecnico  = {$id}
                                                AND treinamento    = {$treinamento_id}";
                            $res_check      = pg_query($con, $select_check);
                            if (pg_num_rows($res_check) > 0)
                            {
                                $msg_erro = "Técnico '{$tecnico_nome}' já esta cadastrado";
                            }else
                            {
                                $insert_tenico  = "INSERT INTO tbl_treinamento_posto(
                                                    treinamento,
                                                    tecnico,
                                                    tecnico_nome,
                                                    tecnico_email,
                                                    tecnico_cpf
                                                ) VALUES (
                                                    {$treinamento_id},
                                                    {$id},
                                                    '{$tecnico_nome}',
                                                    '{$tecnico_email}',
                                                    '{$tecnico_cpf}'
                                            );";
                                $res_insert     = pg_query($con, $insert_tenico);
                                $msg_erro       = pg_last_error($con);  
                            }                           
                        }
                    }       
                }
            }
        }

        if (in_array($login_fabrica, array(169,170,175,193))){
            ## INSERT LINHA
            $linhas_array = $_GET['linha'];

            if (!is_array($linhas_array)) {
                $linhas[] = $linhas_array;

            }else { $linhas = $linhas_array; }

            $aux_linhas = implode(",", $linhas);    

            $sql_delete_linha = "DELETE FROM tbl_treinamento_produto
                                            WHERE fabrica = {$login_fabrica}
                                                AND treinamento = {$treinamento_id}";
                        $query_delete_linha = pg_query($con, $sql_delete_linha);

            foreach ($linhas as $linha){
                if (!empty($linha)){
                    $sql_insert_linha = "INSERT INTO tbl_treinamento_produto(
                                        fabrica,
                                        treinamento,
                                        linha
                                    ) VALUES (
                                        {$login_fabrica},
                                        {$treinamento_id},
                                        {$linha}
                                    )";
                    $query_insert_linha = pg_query($con, $sql_insert_linha);
                    $msg_erro = pg_last_error();
                }
            }

            if (in_array($login_fabrica, array(175))){
                ## INSERT LINHA
                $produtos_array = $_GET['produto'];

                if (!is_array($produtos_array)) {
                    $produtos[] = $produtos_array;

                }else { $produtos = $produtos_array; }

                $aux_produtos = implode(",", $produtos);    

                foreach ($produtos as $produto){
                    if (!empty($produto)){
                        $select_linha_produto = "SELECT linha FROM tbl_produto WHERE produto = {$produto}";
                        $res_linha_produto    = pg_query($con, $select_linha_produto);
                        $msg_erro             = pg_last_error();
                        if (pg_num_rows($res_linha_produto) > 0) {
                            $linha_produto        = pg_fetch_result($res_linha_produto, 0, 'linha');     
                        }

                        $sql_insert_linha = "INSERT INTO tbl_treinamento_produto(
                                            fabrica,
                                            treinamento,
                                            produto,
                                            linha
                                        ) VALUES (
                                            {$login_fabrica},
                                            {$treinamento_id},
                                            {$produto},
                                            {$linha_produto}
                                        )";
                        $query_insert_linha = pg_query($con, $sql_insert_linha);
                        $msg_erro = pg_last_error();
                    }
                }
            }
        }

        if(in_array($login_fabrica, array(1,117,138,169,170,171,193)) AND $msg_erro == false ){ //HD-3261932
            if($id_treinamento > 0){
                $treinamento_id = $id_treinamento;
            }else{
                $res_treina_posto = pg_query ($con,"SELECT CURRVAL ('seq_treinamento') as treinamento_id");
                $treinamento_id = pg_fetch_result($res_treina_posto,0,treinamento_id);
            }

            if (!is_array($codigo_posto)){
                $codigo_posto[] = $codigo_posto;
            }

            $codigo_posto     = implode("','", $codigo_posto);
            $aux_codigo_posto = "'$codigo_posto'";
            $sql_posto        = "SELECT DISTINCT tbl_posto_fabrica.posto AS posto_id
                        FROM tbl_posto_fabrica
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                        WHERE tbl_posto_fabrica.codigo_posto IN ($aux_codigo_posto)
                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
            $res_posto        = pg_query($con,$sql_posto);
            $count            = pg_numrows($res_posto);

            for ($i=0; $i<$count; $i++){
                $posto_id     = pg_fetch_result($res_posto, $i, 'posto_id');
                $sql_verifica = "SELECT treinamento_posto FROM tbl_treinamento_posto
                                    WHERE treinamento = {$treinamento_id} AND posto = {$posto_id}";
                $res_verifica = pg_query($con, $sql_verifica);

                // se o posto não estiver cadastrado...
                if(pg_num_rows($res_verifica) == 0){
                    $sqlInsert = "INSERT INTO tbl_treinamento_posto(treinamento,posto) VALUES ($treinamento_id,$posto_id)";
                    $resInsert = pg_query($con, $sqlInsert);
                }
            }
        }
        /*HD - 6261912*/
        $mensagem_enviada = false;
        if (in_array($login_fabrica, [169,170,193]) && strlen($treinamento) > 0) {
            $aux_sql = "SELECT treinamento FROM tbl_treinamento WHERE fabrica = $login_fabrica AND data_finalizado IS NOT NULL AND treinamento = $treinamento";
            $aux_res = pg_query($con, $aux_sql);
            $aux_val = pg_fetch_result($aux_res, 0, 'treinamento');

            if (strlen($aux_val) > 0) {
                $mensagem_enviada = true;
            }
        }

        if (in_array($login_fabrica, array(169,170,193)) AND $msg_erro == false AND $mensagem_enviada == false){

            if (count($cidades_treinamento) > 0){
                $cidades_in = implode(',', $cidades_treinamento);

                $sql_t = "SELECT tbl_posto_fabrica.posto, tbl_posto_fabrica.contato_email
                            FROM tbl_posto_fabrica
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                            JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto
                            JOIN tbl_ibge ON tbl_ibge.cod_ibge = tbl_posto_fabrica.cod_ibge
                            JOIN tbl_cidade ON tbl_cidade.cod_ibge = tbl_ibge.cod_ibge
                            WHERE tbl_cidade.cidade IN ($cidades_in)
                            AND tbl_posto_linha.linha = $linha
                            AND tbl_posto_fabrica.tipo_posto IN ({$aux_tipo_posto})
                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                            AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
                            AND ativo IS TRUE";
                $res_t = pg_query($con, $sql_t);
                $msg_erro = pg_last_error($con);
                if (pg_num_rows($res_t) > 0){
                    $result_t = pg_fetch_all($res_t);
                }
            }

            if (!empty($estado_participante)){
                $estados_participantes = explode(',', $estado_participante);
                if (count($estados_participantes) > 0){
                    $estadosx = array();
                    foreach ($estados_participantes as $key => $value) {
                        $estadosx[] = "'$value'";
                    }
                    $estadosx = implode(',', $estadosx);

                    $sql_ts = "SELECT tbl_posto_fabrica.posto, tbl_posto_fabrica.contato_email
                                FROM tbl_posto_fabrica
                                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                                JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto
                                WHERE tbl_posto.estado IN ($estadosx)
                                AND tbl_posto_linha.linha = $linha
                                AND tbl_posto_fabrica.tipo_posto IN ({$aux_tipo_posto})
                                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
                                AND ativo IS TRUE";
                    $res_ts = pg_query($con, $sql_ts);

                    $msg_erro = pg_last_error($con);
                    if (pg_num_rows($res_ts) > 0){
                        $result_ts = pg_fetch_all($res_ts);
                    }
                }
            }

            if (count($resul_t) > 0 AND count($result_ts) > 0) {
                $result_postos = array_merge($result_t, $result_ts);
            } else if (count($result_t) > 0 AND !count($result_ts)) {
                $result_postos = $result_t;
            } else {
                $result_postos = $result_ts;
            }

            $sql_t_posto = "
                    SELECT tbl_posto_fabrica.posto, tbl_posto_fabrica.contato_email
                    FROM tbl_treinamento_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_treinamento_posto.posto
                        AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                    WHERE tbl_treinamento_posto.treinamento = {$treinamento_id}
                    AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO' ";
            $res_t_posto = pg_query($con, $sql_t_posto);
            $msg_erro = pg_last_error($con);

            if (pg_num_rows($res_t_posto) > 0){
                $result_t_posto = pg_fetch_all($res_t_posto);
            }

            if (count($result_t_posto) > 0 AND count($result_postos) > 0){
                $result_postos = array_merge($result_postos, $result_t_posto);
            }else if (count($result_t_posto) > 0 AND !count($result_postos)){
                $result_postos = $result_t_posto;
            }else{
                $result_postos = $result_postos;
            }

            $tituloc = str_replace("'","",$titulo);

            if (in_array($login_fabrica, array(169,170,193)))
                {
                    $data_inicial_verifica = $data_inicial;
                    $data_inicial_old = explode(' ', $data_inicial);
                    $hora_inicial = $data_inicial_old[1];
                    $hora_inicial = explode(":", $hora_inicial);
                    $data_inicial = explode('-', $data_inicial_old[0]);

                    $data_final_old  = explode(' ', $data_final);
                    $hora_final  = $data_final_old[1];
                    $hora_final  = explode(":", $hora_final);
                    $data_final  = explode('-', $data_final_old[0]);
                    $local       = str_replace("'", "", $local);

                    switch($data_inicial[1]){
                        case '01': $mes = 'Janeiro'; break; case '02': $mes = 'Fevererio'; break; case '03': $mes = 'Março';    break;
                        case '04': $mes = 'Abril';   break; case '05': $mes = 'Maio';      break; case '06': $mes = 'Junho';    break;
                        case '07': $mes = 'Julho';   break; case '08': $mes = 'Agosto';    break; case '09': $mes = 'Setembro'; break;
                        case '10': $mes = 'Outubro'; break; case '11': $mes = 'Novembro';  break; case '12': $mes = 'Dezembro'; break;
                    }

                    if ($treinamento_tipo_nome == 'TREINAMENTO'){
                        $treinamento_tipo_nome_aux = 'do treinamento';
                    }else if ($treinamento_tipo_nome == 'PALESTRA'){
                        $treinamento_tipo_nome_aux = 'da palestra';
                    }

                    $titulo_treinamento = "$treinamento_tipo_nome: $tituloc";
                    
                    if (in_array($login_fabrica, [193])) {  
                        $mensagem = "
                            <div>
                                <center><h1>CONVITE</h1></center>
                            </div>

                            A <b>Dancor</b> tem o prazer de convidá-lo para participar ".$treinamento_tipo_nome_aux." de <b>".$tituloc."</b>, que será realizado nos dias <b>".$data_inicial[2]." a ".$data_final[2]." de ".$mes."</b>, na <b>".$local."</b>, das <b>".$hora_inicial[0].":".$hora_inicial[1]." às ".$hora_final[0].":".$hora_final[1]."h</b>. <br /><br />

                            As inscrições deverão ser realizadas via <b><a href=www.telecontrol.com.br>Telecontrol</a></b>. <br /><br />

                            <center><b>Contamos com sua presença!</b></center> <br /><br /><br />

                            <div align=right>
                                <span>
                                    Contato: 
                                    <a href=mailto:contato@dancor.com.br target=_top>
                                        treinamentostecnicos@dancor.com.br
                                    </a>
                                <span> <br />
                                <span> Fone: +55 (21) 2529-9500 </span>
                            </div>
                        "; 
                    } else {
                        $mensagem = "
                            <div>
                                <center><h1>CONVITE</h1></center>
                            </div>

                            A <b>Midea Carrier</b> tem o prazer de convidá-lo para participar ".$treinamento_tipo_nome_aux." de <b>".$tituloc."</b>, que será realizado nos dias <b>".$data_inicial[2]." a ".$data_final[2]." de ".$mes."</b>, na <b>".$local."</b>, das <b>".$hora_inicial[0].":".$hora_inicial[1]." às ".$hora_final[0].":".$hora_final[1]."h</b>. <br /><br />

                            As inscrições deverão ser realizadas via <b><a href=www.telecontrol.com.br>Telecontrol</a></b>. <br /><br />

                            Este treinamento é gratuito, porém será cobrado taxa de <b>NoShow</b>, no valor de <b>R$160,00</b>, caso o inscrito não compareça sem aviso prévio. <br /><br />

                            <center><b>Contamos com sua presença!</b></center> <br /><br /><br />

                            <div align=right>
                                <span>Contato: <a href=mailto:treinamentostecnicos©mideacarrier.com target=_top>treinamentostecnicos@mideacarrier.com</a><span> <br />
                                <span>Fone: (51) 3477-9014</span>
                            </div>
                        ";                    
                    }

                    if (strlen($id_treinamento) > 0){
                        $titulo_email            = "ALTERAÇÃO - $treinamento_tipo_nome"; 
                        $titulo_email_convidado  = "ALTERAÇÃO - $treinamento_tipo_nome"; 
                    }else{
                        $titulo_email            = "CONVITE - $treinamento_tipo_nome";     
                        $titulo_email_convidado  = "CONVITE - $treinamento_tipo_nome";  
                    }

                    $mensagem_email = "$mensagem";

                }else{
                    $titulo_treinamento = "$treinamento_tipo_nome: $tituloc";
                    $mensagem = "
                        Data início: $data_inicial <br/>
                        Data término: $data_final <br/>
                        Prazo inscrições até: $prazo_inscricao <br/>
                        Quantidade de vagas: $qtde <br/>
                        Quantidade de vagas por Posto: $qtde_participante <br/>
                        Descrição: $descricao
                    ";

                    $titulo_email   = $titulo_treinamento;
                    $mensagem_email = "$treinamento_tipo_nome: $tituloc <br/> $mensagem";
                }

            if (count($result_postos) > 0) {

                $valor_prazo_inscricao = str_replace("'","",$valor_prazo_inscricao);
                $descricao             = str_replace("'","",$descricao);

                foreach ($result_postos as $key => $value) {
                    $id_posto = $value["posto"];
                    $email_posto = $value["contato_email"];
                    // rota alternativa
                    $select_tecnico     = "SELECT parametros_adicionais
                                            FROM tbl_comunicado
                                        WHERE fabrica  = {$login_fabrica}
                                            AND posto  = {$id_posto}
                                            AND tipo   = 'Comunicado'";
                    $res_tecnico       = pg_query($con,$select_tecnico);
                    if (pg_numrows($res_tecnico) > 0){
                        $count = pg_numrows($res_tecnico);
                        for ($i=0; $i<$count; $i++){
                            $parametros_adicionais = json_decode(pg_fetch_result($res_tecnico, $i, parametros_adicionais));
                            $res_treinamento       = $parametros_adicionais->treinamento;
                            $tecnico_id            = $parametros_adicionais->tecnico;
                            if ($res_treinamento == $treinamento_id){
                                if ($tecnico_id == $id){
                                    $titulo_email_convidado = "ALTERAÇÃO - $treinamento_tipo_nome";
                                }else{
                                    $titulo_email_convidado = "CONVITE - $treinamento_tipo_nome";
                                }
                            }
                        }
                    }
                    
                    if (!strlen($treinamento_id) > 0 || !strlen($id_treinamento) > 0){    ##### HD-CHAMADO 4388993
                        $parametros_adicionais = json_encode(array("tecnico" => $id, "treinamento" => $treinamento_id));
                        $sql_insert = "INSERT INTO tbl_comunicado(
                                                mensagem,
                                                tipo,
                                                fabrica,
                                                descricao,
                                                ativo,
                                                posto,
                                                obrigatorio_site,
                                                parametros_adicionais
                                            )VALUES(
                                                '$mensagem',
                                                'Comunicado',
                                                $login_fabrica,
                                                '$titulo_treinamento',
                                                true,
                                                $id_posto,
                                                true,
                                                '$parametros_adicionais'
                                            );";
                        $res_insert = pg_query($con, $sql_insert);
                        $msg_erro = pg_last_error($con);    
                    }
                }
            }
                
            // Envia e-mail para os técnicos convidados
            if (in_array($login_fabrica, array(169,170,193))){
                $tecnicos_ids       = $_GET['tecnico_convidado'];
                
                if (!is_array($tecnicos_ids)){ $tecnicos_ids[] = $tecnicos_ids; }

                foreach ($tecnicos_ids as $id) {
                    if (!empty($id)) {
                        $select_tecnico = "SELECT email
                                            FROM tbl_tecnico
                                                WHERE fabrica = {$login_fabrica}
                                            AND tecnico = {$id}";
                        $res_select     = pg_query($con, $select_tecnico);
                        
                        if (pg_num_rows($res_select) > 0) {
                            $tecnico_email  = pg_fetch_result($res_select,0,email);

                            try {
                                if (in_array($login_fabrica,array(169,170,193))){
                                    $data_hoje = date('Y-m-d');
                                    if ($data_inicial_old[0] >= $data_hoje){
                                        $comunicatorMirror->post($tecnico_email, utf8_encode("$titulo_email_convidado"), utf8_encode("$mensagem_email"));
                                    }
                                }else{
                                    $comunicatorMirror->post($tecnico_email, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));    
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    }       
                }
            }

            foreach ($promotores_treinamento as $promotor) {
                $sql_email_promotor = "SELECT email AS email_promotor FROM tbl_promotor_treinamento WHERE fabrica = {$login_fabrica} AND promotor_treinamento = {$promotor}";
                $res_email_promotor = pg_query($con, $sql_email_promotor);
                $msg_erro = pg_last_error($con);

                if (pg_num_rows($res_email_promotor) > 0){
                    $email_promotor = pg_fetch_result($res_email_promotor, 0, 'email_promotor');
                    try {
                        if (in_array($login_fabrica,array(169,170,193))){
                            $data_hoje = date('Y-m-d');
                            if ($data_inicial_old[0] >= $data_hoje){
                                $comunicatorMirror->post($email_promotor, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));
                            }
                        }else{
                            $comunicatorMirror->post($email_promotor, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));    
                        }
                    } catch (\Exception $e) {
                    }
                }
            }

            $sql_email_instrutor = "SELECT email AS email_instrutor FROM tbl_promotor_treinamento WHERE fabrica = {$login_fabrica} AND promotor_treinamento = {$instrutor}";
            $res_email_instrutor = pg_query($con, $sql_email_instrutor);
            
            if (pg_num_rows($res_email_instrutor) > 0){
                $email_instrutor = pg_fetch_result($res_email_instrutor, 0, 'email_instrutor');
                try {
                    if (in_array($login_fabrica,array(169,170,193))){
                        $data_hoje = date('Y-m-d');
                        if ($data_inicial_old[0] >= $data_hoje){
                            $comunicatorMirror->post($email_instrutor, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));
                        }
                    }else{
                        $comunicatorMirror->post($email_instrutor, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));
                    }
                } catch (\Exception $e) {
                }
            }
        }

        if ($login_fabrica == 171){
            $descricao = str_replace("'","",$descricao);
            $titulo_treinamento = str_replace("'","",$titulo);
            $mensagem = "
                Data início: $data_inicial <br/>
                Data término: $data_final <br/>
                Quantidade de vagas: $qtde <br/>
                Descrição: $descricao
            ";

            $mensagem_email = "$titulo_treinamento <br/> $mensagem";

            $sql = " SELECT nome FROM tbl_cidade WHERE cidade = $cidade ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0){
                $cidade_nome = pg_fetch_result($res, 0, 'nome');
            }

            $sql = "
                SELECT
                    tbl_posto.nome,
                    tbl_posto_fabrica.contato_email,
                    tbl_posto.posto
                FROM tbl_posto_fabrica
                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                AND UPPER(fn_retira_especiais(tbl_posto.cidade)) = UPPER(fn_retira_especiais(trim('{$cidade_nome}'))) ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0){
                for ($i=0; $i < pg_num_rows($res); $i++) {
                    $posto_nome     = pg_fetch_result($res, $i, 'nome');
                    $posto_email    = pg_fetch_result($res, $i, 'contato_email');
                    $id_posto       = pg_fetch_result($res, $i, 'posto');

                    $sql_insert = "
                        INSERT INTO tbl_comunicado(
                            mensagem,
                            tipo,
                            fabrica,
                            descricao,
                            ativo,
                            posto,
                            obrigatorio_site
                        )VALUES(
                            '$mensagem',
                            'Comunicado',
                            $login_fabrica,
                            '$titulo_treinamento',
                            true,
                            $id_posto,
                            true
                        );";
                    $res_insert = pg_query($con, $sql_insert);
                    $msg_erro = pg_last_error($con);

                    try {
                        $comunicatorMirror->post($posto_email, utf8_encode("$titulo_treinamento"), utf8_encode("$mensagem_email"));
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        if (strlen($msg_erro) == 0 ) {
            $res = @pg_query($con,"COMMIT TRANSACTION");
            exit(json_encode(array("ok" => utf8_encode($sucesso))));
        }else{
            $res = @pg_query($con,"ROLLBACK TRANSACTION");
            exit(json_encode(array("error" => utf8_encode($msg_erro))));
        }

    }
    if (strlen($msg_erro) > 0) {
        exit(json_encode(array("error" => utf8_encode($msg_erro))));
    }

    flush();
    exit;
}
//--====== EXCLUIR PROMOTOR TREINAMENTO ===============================================================
if ($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar_makita') {

    $id_treinamento = trim($_GET['treinamento']);
    $data_inicial   = trim($_GET['data_inicial']);
    $data_final     = trim($_GET['data_final']);
    $titulo         = trim($_GET['titulo']);
    $linha          = trim($_GET['linha']);
    $familia        = trim($_GET['familia']);
    $descricao      = trim($_GET['descricao']);
    $qtde           = trim($_GET['qtde']);
    $adicional      = trim($_GET['adicional']);
    $local          = trim($_GET['local']);

    $titulo = utf8_decode($titulo);
    $descricao = utf8_decode($descricao);
    $adicional = utf8_decode($adicional);
    $local = utf8_decode($local);

    if (strlen($titulo)       == 0)                                      $msg_erro = 'Favor informar o tema';
    if (strlen($linha)        == 0            && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a linha';
    if (strlen($data_inicial) == 0            && strlen($msg_erro) == 0) $msg_erro = 'Data Inválida';
    if (strlen($data_final)   == 0            && strlen($msg_erro) == 0) $msg_erro = 'Data Inválida';
    if (strlen($qtde)         == 0            && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a quantidade de vagas';
    if (strlen($descricao)    == 0            && strlen($msg_erro) == 0) $msg_erro = 'Favor informar a descrição<br>';
    if ($data_inicial         == 'dd/mm/aaaa' && strlen($msg_erro) == 0) $msg_erro = 'Data Inválida';
    if ($data_final           == 'dd/mm/aaaa' && strlen($msg_erro) == 0) $msg_erro = 'Data Inválida';

    if ($login_fabrica == 42 && valida_data_treinamento_new($data_inicial, $data_final)) {
        $msg_erro = "Data Inválida";
    }

    if (strlen($msg_erro) == 0) {

        //--====== Formata data inicial do treinamento =====================================================
        $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");

        if (strlen(pg_last_error($con)) > 0) $msg_erro = "Data Inválida";//pg_errormessage ($con) ;
        if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_fetch_result ($fnc,0,0);

        //--====== Formata data final do treinamento =======================================================
        $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
        if (strlen ( pg_last_error($con) ) > 0) $msg_erro = "Data Inválida";//pg_errormessage ($con) ;
        if (strlen($msg_erro) == 0) $aux_data_final = @pg_fetch_result ($fnc,0,0);

        //--====== Valida se as datas do treinamento =======================================================
        $data_hoje = date("d/m/Y");

        if($login_fabrica != 42){
            $sql     = "SELECT '$aux_data_inicial'::date > current_date ";
            $res     = pg_query($con, $sql);
            $valida  = trim(pg_fetch_result($res,0,0));
            if($valida=='f') $msg_erro = "Data Inválida";
        }

        if($login_fabrica != 42){
            $sql     = "SELECT '$aux_data_final'::date > '$aux_data_inicial'::date ";
            $res     = pg_query($con, $sql);
            $valida  = trim(pg_fetch_result($res,0,0));
            if($valida=='f') $msg_erro = "Data Inválida";
        }

        if (strlen($msg_erro) > 0) {
            $msg = $msg_erro;
        }
    }

    if (empty($msg_erro)) {
        $listar = "ok";
    }

    if ($listar == "ok") {

        $res = @pg_query($con,"BEGIN TRANSACTION");

        if(strlen($familia)==0) $familia = "null"; else $familia = "'$familia'";

        if($id_treinamento > 0 ){
            $sql = "UPDATE tbl_treinamento SET
                    titulo               = '$titulo'          ,
                    data_inicio          = '$aux_data_inicial',
                    data_fim             = '$aux_data_final'  ,
                    linha                = '$linha'           ,
                    vagas                = '$qtde'            ,
                    descricao            = '$descricao'       ,
                    adicional            = '$adicional'       ,
                    admin                = '$login_admin'     ,
                    treinamento_tipo     = $familia           ,
                    local                = '$local'
                WHERE treinamento        = $treinamento
                AND   fabrica            = $login_fabrica";
            $sucesso .= "Treinamento alterado com sucesso!";
        }else{
            $sql = "INSERT INTO tbl_treinamento (
                    titulo            ,
                    data_inicio       ,
                    data_fim          ,
                    linha             ,
                    vagas             ,
                    descricao         ,
                    adicional         ,
                    fabrica           ,
                    admin             ,
                    treinamento_tipo  ,
                    local
                )VALUES(
                    '$titulo'          ,
                    '$aux_data_inicial',
                    '$aux_data_final'  ,
                    '$linha'           ,
                    '$qtde'            ,
                    '$descricao'       ,
                    '$adicional'       ,
                    '$login_fabrica'   ,
                    '$login_admin'     ,
                    $familia           ,
                    '$local'
                )";
            $sucesso .= "Gravado com Sucesso";
        }
        $res = pg_query($con, $sql);
        $msg_erro = pg_last_error($con);

        if (strlen($msg_erro) == 0 ) {
            //$res = @pg_query($con,"ROLLBACK TRANSACTION");
            $res = @pg_query($con,"COMMIT TRANSACTION");
            exit(json_encode(array("ok" => utf8_encode($sucesso))));
        }else{
            $res = @pg_query($con,"ROLLBACK TRANSACTION");
            exit(json_encode(array("error" => utf8_encode($msg_erro))));
        }
    }

    if (strlen($msg_erro) > 0) {
        exit(json_encode(array("error" => utf8_encode($msg_erro))));
    }

    exit;

}

//--====== CANCELAR DE TREINAMENTO =================================================================
if ($_GET['ajax']=='sim' AND $_GET['acao']=='cancelar') {
    
    $motivo_cancelamento = trim($_GET['motivo']);
    $id_treinamento    = trim($_GET['treinamento']);
    $treinamento_tipo  = trim($_GET['treinamento_tipo']);
    $data_inicial      = trim($_GET['data_inicial']);
    $data_final        = trim($_GET['data_final']);
    $titulo            = trim($_GET['titulo']);
    $tituloc           = str_replace("'","",$titulo);     
    $data_inicial_old  = explode(' ', $data_inicial);
    $data_inicial      = explode('/', $data_inicial_old[0]);
    $data_final_old    = explode(' ', $data_final);
    $data_final        = explode('/', $data_final_old[0]);

    $sql_check         = "SELECT 
                            treinamento, 
                            parametros_adicionais, 
                            ativo
                        FROM tbl_treinamento
                        WHERE treinamento = {$id_treinamento}
                            AND fabrica   = {$login_fabrica}";
    $res_check         = pg_query($con,$sql_check);
    if (pg_numrows($res_check) > 0){

        $parametros_adicionais                        = json_decode(pg_fetch_result($res_check, 0, 'parametros_adicionais'), true);
        $parametros_adicionais['motivo_cancelamento'] = utf8_encode($motivo_cancelamento);
        $parametros_adicionais                        = json_encode($parametros_adicionais);
        $ativo                                        = pg_fetch_result($res_check, 0, 'ativo');

        // treinamento já cancelado
        if ($ativo == 'f' || $ativo == 'false' || $ativo == FALSE){
            $msg_erro = "Treinamento já cancelado!";
            exit(json_encode(array("error" => utf8_encode($msg_erro))));
        }

        /* inativando treinamento */
        $sql_inativa = "UPDATE tbl_treinamento
                    SET ativo = FALSE,
                        parametros_adicionais = '$parametros_adicionais'
                    WHERE treinamento = {$id_treinamento}
                        AND fabrica   = {$login_fabrica}";
        $res_inativa = pg_query($con,$sql_inativa);
        $msg_erro    = pg_last_error($con);

        /*'e-mail de cancelamento */
        switch($data_inicial[1]){ 
            case '01': $mes = 'Janeiro'; break; case '02': $mes = 'Fevererio'; break; case '03': $mes = 'Março';    break;
            case '04': $mes = 'Abril';   break; case '05': $mes = 'Maio';      break; case '06': $mes = 'Junho';    break;
            case '07': $mes = 'Julho';   break; case '08': $mes = 'Agosto';    break; case '09': $mes = 'Setembro'; break;
            case '10': $mes = 'Outubro'; break; case '11': $mes = 'Novembro';  break; case '12': $mes = 'Dezembro'; break;
        }

        if (!empty($treinamento_tipo)){
            $sql = "SELECT nome FROM tbl_treinamento_tipo WHERE treinamento_tipo = {$treinamento_tipo} AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0){
                $treinamento_tipo_nome = pg_fetch_result($res, 0, 'nome');
            }
        }
        
        if ($treinamento_tipo_nome == 'TREINAMENTO'){
            $treinamento_tipo_nome_aux = 'o treinamento';
        }else if ($treinamento_tipo_nome == 'PALESTRA'){
            $treinamento_tipo_nome_aux = 'a palestra';
        }

        $titulo_email   = "CANCELAMENTO - $treinamento_tipo_nome"; 
        $mensagem_email = "
                <div>
                    <center><h1>COMUNICADO</h1></center>
                </div>

                Informamos que ".$treinamento_tipo_nome_aux." <b>".$tituloc."</b> que seria realizado nos dias <b>".$data_inicial[0]." a ".$data_final[0]." de ".$mes."</b>, foi cancelado pelo organizador.<br />

                <center>Agradecemos a compreensão e qualquer dúvida estaremos à disposição.</center><br />

                <div align=right>
                    <span>Contato: <a href=mailto:treinamentostecnicos©mideacarrier.com target=_top>treinamentostecnicos@mideacarrier.com</a><span> <br />
                    <span>Fone: (51) 3477-9014</span>
                </div>";

        /****** ENVIANDO P/ TÉCNICOS ******/
        $comunicatorMirror = new ComunicatorMirror();
        $sql_tecnico       = "SELECT 
                            tbl_treinamento_posto.tecnico,
                            tbl_treinamento_posto.tecnico_email,
                            tbl_tecnico.email
                        FROM tbl_treinamento_posto
                            INNER JOIN tbl_treinamento ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
                            INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
                        WHERE   tbl_treinamento_posto.treinamento = {$id_treinamento}
                            AND tbl_treinamento.treinamento     = {$id_treinamento}
                            AND tbl_treinamento.fabrica         = {$login_fabrica}";
        $res_tecnico       = pg_query($con,$sql_tecnico);
        if (pg_num_rows($res_tecnico) > 0)
        {   
            for ($i=0; $i<pg_num_rows($res_tecnico); $i++)
            {
                $id                = pg_fetch_result($res_tecnico, $i, tecnico);
                $tecnico_email     = pg_fetch_result($res_tecnico, $i, tecnico_email);
                if (empty($tecnico_email) || $tecnico_email == ''){
                    $tecnico_email = pg_fetch_result($res_tecnico, $i, email);
                }

                /* envia o e-mail para todos técnicos cadastrados */
                try {
                    $comunicatorMirror->post($tecnico_email, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));    
                } catch (\Exception $e) {
                }
            }
        }

        /****** ENVIANDO P/ O INSTRUTOR ******/
        $sql_instrutor = "SELECT DISTINCT
                            tbl_promotor_treinamento.email AS email_instrutor
                        FROM tbl_treinamento_posto
                            INNER JOIN tbl_treinamento ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
                            INNER JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.treinamento         = tbl_treinamento.treinamento
                            INNER JOIN tbl_promotor_treinamento  ON tbl_promotor_treinamento.promotor_treinamento = tbl_treinamento_instrutor.instrutor_treinamento
                        WHERE   tbl_treinamento_posto.treinamento = {$id_treinamento}
                            AND tbl_treinamento.treinamento       = {$id_treinamento}
                            AND tbl_treinamento.fabrica           = {$login_fabrica}";
        $res_instrutor = pg_query($con,$sql_instrutor);
        if (pg_num_rows($res_instrutor) > 0)
        {
            $email_instrutor     = pg_fetch_result($res_instrutor, 0, 'email_instrutor');
            if (!empty($email_instrutor)){
                try {
                  $comunicatorMirror->post($email_instrutor, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));    
                } catch (\Exception $e) {
                }
            }
        }

        /****** ENVIANDO P/ OS PROMOTORES ******/
        $sql_promotor = "SELECT 
                            tbl_promotor_treinamento.email AS email_promotor
                        FROM tbl_treinamento_promotor 
                            INNER JOIN tbl_treinamento          ON tbl_treinamento.treinamento                   = tbl_treinamento_promotor.treinamento
                            INNER JOIN tbl_promotor_treinamento ON tbl_treinamento_promotor.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento 
                        WHERE   tbl_treinamento.treinamento              = {$id_treinamento}
                                AND tbl_treinamento.fabrica              = {$login_fabrica}
                                AND tbl_treinamento_promotor.treinamento = {$id_treinamento}";
        $res_promotor = pg_query($con,$sql_promotor);
        if (pg_num_rows($res_promotor) > 0)
        {
            $email_promotor     = pg_fetch_result($res_promotor, 0, 'email_promotor');
            if (!empty($email_promotor)){
                try {
                  $comunicatorMirror->post($email_promotor, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));    
                } catch (\Exception $e) {
                }
            }
        }

        /****** ENVIANDO P/ OS POSTOS ******/
        $sql_posto = "SELECT 
                            tbl_posto_fabrica.contato_email AS email_posto,
                            tbl_posto.email                 AS email_posto2
                        FROM tbl_treinamento_posto
                            INNER JOIN tbl_treinamento   ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
                            INNER JOIN tbl_posto_fabrica ON tbl_treinamento_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                            INNER JOIN tbl_posto         ON tbl_posto_fabrica.posto     = tbl_posto.posto
                        WHERE   tbl_treinamento_posto.treinamento = {$id_treinamento}
                            AND tbl_treinamento.treinamento       = {$id_treinamento}
                            AND tbl_treinamento.fabrica           = {$login_fabrica}";

        $res_posto = pg_query($con,$sql_posto);
        if (pg_num_rows($res_posto) > 0){
            for ($i=0; $i<pg_num_rows($res_posto); $i++)
            {
                $email_posto  = pg_fetch_result($res_posto, $i, 'email_posto');
                $email_posto2 = pg_fetch_result($res_posto, $i, 'email_posto2');
                $posto_send   = (!empty($email_posto)) ? $email_posto : $email_posto2;

                if (!empty($posto_send)){
                    try {
                        $comunicatorMirror->post($posto_send, utf8_encode("$titulo_email"), utf8_encode("$mensagem_email"));    
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        $sucesso = $treinamento_tipo_nome_aux." foi cancelado!";    
    }else{
        $msg_erro = "O Treinamento não existe";
    }                            

    if (strlen($msg_erro) > 0) {
        exit(json_encode(array("error" => utf8_encode($msg_erro))));
    }else{
        exit(json_encode(array("ok" => utf8_encode($sucesso))));
    }
    exit;
}
//--====== FINALIZAR TREINAMENTO =========================================================================

if($_GET['ajax']=='sim' AND $_GET['acao']=='finalizar_treinamento') {

    $treinamento = $_GET['treinamento'];

    $sql = "SELECT t.treinamento, tt.nome FROM tbl_treinamento t LEFT JOIN tbl_treinamento_tipo tt ON  t.treinamento_tipo = tt.treinamento_tipo WHERE t.treinamento = $treinamento and t.fabrica = $login_fabrica";

    $res = pg_query($con,$sql);
    $res = pg_fetch_array($res);

    $qtde_participante = "";
    if($res['treinamento'] == ""){
        exit(json_encode(array("error" => utf8_encode("Treinamento não encontrado"))));
    }

    if($res['nome'] == "Palestra"){

        if($_GET['qtde_participantes'] == ""){
            exit(json_encode(array("error" => utf8_encode("Informe a quantidade de participantes"))));
        }else{
            $qtde_participante = ", qtde_participante = ".$_GET['qtde_participantes'];
        }
    }


    if($res['nome'] != "Palestra"){
        /**
        Avaliaçao do treinamento feito pelo técnico no ambiente do posto
        */
        $questionario = array(
            array(
                "main_title" => "Conteúdo Abordado",
                "question" => "Como você avalia o conteúdo nos seguintes aspectos",
                "itens" => array(
                    "Cumprimentos dos objetivos propostos",
                    "Carga horária planejada",
                    "Forma de apresentação dos conteúdos",
                    "Material didático distribuído",
                    "Conteúdo de uma forma geral"
                )
            ),
            array(
                "main_title" => "Instrutor",
                "question" => "Como você avalia o instrutor do treinamento nos seguintes aspectos",
                "itens" => array(
                    "Clareza e objetividade",
                    "Cumprimento do programa de treinamento",
                    "Domínio sobre o assunto",
                    "Relação com a turma",
                    "Desempenho geral"
                )
            ),
            array(
                "main_title" => "Ambiente",
                "question" => "Como você avalia o ambiente do treinamento nos seguintes aspectos",
                "itens" => array(
                    "Equipamentos utilizados",
                    "Instalações físicas",
                    "Ruído",
                    "Iluminação",
                    "Impressão geral"
                )
            ),
            array(
                "main_title" => "Geral",
                "itens" => array(
                    "De forma geral como você avalia o treinamento recebido?"
                )
            ),
            array(
                "main_title" => "Comentários",
                "itens" => array(
                    // open_text_area Esse item insere uma text area para livre digitação
                    "open_text_area"
                )
            )
        );
        array_walk_recursive($questionario, function (&$value) {
                $value = utf8_encode($value);
            }
        );
        $questionario = json_encode($questionario);

        $sql = "INSERT INTO tbl_pesquisa(fabrica, descricao, categoria, admin, treinamento, texto_ajuda)
                VALUES($login_fabrica,'Avaliação de Reação', 'Pesquisa de Satisfação', $login_admin, $treinamento, '$questionario')";
        $res_insere_pesquisa = pg_query($con,$sql);
    }

    $sql = "UPDATE tbl_treinamento SET data_finalizado = CURRENT_DATE $qtde_participante WHERE treinamento = $treinamento";
    $res = pg_query($con,$sql);
    if(pg_last_error($con) == FALSE){
        exit(json_encode(array("success" => utf8_encode("Treinamento finalizado"))));
    }

    exit(json_encode(array("error" => utf8_encode("Ocoreeu um erro ao tentar finalizar o treinamento, por favor tente novamente"))));
}

//--====== VER TREINAMENTO =========================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='ver') {

	if(in_array($login_fabrica, [1,117,138,169,170,193])){ //HD-3261932
		$cond_tecnico = " AND tbl_treinamento_posto.tecnico IS NOT NULL ";
	}

    $data_finalizado = "";
	if(in_array($login_fabrica, array(169,170,193))){

		if(!array_key_exists("todos", $_GET)){
			$data_finalizado = "AND tbl_treinamento.data_finalizado IS NULL";
		}

		if(array_key_exists('excel', $_GET)){
			$excel = true;

			$data = date("d-m-Y-H:i");
			$fileName = "relatorio_treinamentos-{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");
            
            if(in_array($login_fabrica, [169,170,193])){
                fwrite($file, utf8_encode("Titulo Treinamento;Data Inicio;Data Fim;Cidade;Estado;Local do Treinamento;Instrutor;Linha;Vagas;Inscritos;Presentes;Tipo;Posto;Técnico;Compareceu;Nota;\n"));    
            } else {
                fwrite($file, utf8_encode("Titulo Treinamento;Data Inicio;Data Fim;Cidade;Estado;Local do Treinamento;Instrutor;Linha;Vagas;Inscritos;Presentes;Tipo;Posto;Técnico;Nota;\n"));    
            }
			
		} else {
			$excel = false;
		}

		if ($_GET['treinamentos_realizado'] == 'sim'){
			$data_finalizado = "AND tbl_treinamento.data_finalizado IS NOT NULL";
        	}

				$join_instrutor = " LEFT JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento ";
            	$join_promotor  = " LEFT JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_treinamento_instrutor.instrutor_treinamento ";
                if(in_array($login_fabrica, [169,170,193])){
            	   $select  = ",tbl_treinamento.qtde_participante,tbl_treinamento.local ";
                } else {
                    $select  = ",tbl_treinamento.qtde_participante,tbl_treinamento.local, tbl_promotor_treinamento.nome AS instrutor_nome";
                }
            	$select .= ",(
                            SELECT COUNT(*)
                            FROM tbl_treinamento
                            WHERE tbl_treinamento.palestrante IS NOT NULL
                            AND tbl_treinamento.fabrica = $login_fabrica
                        ) AS qtde_palestra";
            	$select .= ",(
                            SELECT COUNT(*)
                            FROM tbl_treinamento
                            WHERE tbl_treinamento.ativo IS TRUE
                            AND tbl_treinamento.fabrica = $login_fabrica
                        ) AS qtde_treinamento";

            	$select .= ", (
                	SELECT COUNT(*)
                	FROM tbl_treinamento_posto
                	WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
               		AND tbl_treinamento_posto.ativo IS TRUE
                	AND tbl_treinamento_posto.participou = 't'
                	$cond_tecnico
            		) AS presentes";

            	$join_linha            = "LEFT JOIN      tbl_linha   USING(linha)";
            	$parametros_adicionais = " , tbl_treinamento.parametros_adicionais";
            	$campo_cidade_uf       = ", tbl_cidade.nome AS cidade_nome, tbl_treinamento.estado ";
                $join_cidade_uf        = " JOIN tbl_cidade ON tbl_cidade.cidade = tbl_treinamento.cidade";

        $extraConds = "";
        
        if (in_array($login_fabrica, [169, 170, 193])) {

            if (array_key_exists("instrutor_pesquisa", $_GET) && !empty($_GET['instrutor_pesquisa'])) {
                $inst_pesquisa = $_GET['instrutor_pesquisa'];
                $sql_midea_treinamento = " ,(SELECT tbl_promotor_treinamento.nome
                                            FROM tbl_promotor_treinamento
                                            JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.instrutor_treinamento = tbl_promotor_treinamento.promotor_treinamento
                                            AND tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento
                                            WHERE tbl_treinamento_instrutor.instrutor_treinamento = {$inst_pesquisa}
                                            ORDER BY tbl_treinamento_instrutor.data_input DESC LIMIT 1
                                        ) AS instrutor_nome ";
            } else {
                $sql_midea_treinamento = " ,(SELECT tbl_promotor_treinamento.nome
                                            FROM tbl_promotor_treinamento
                                            JOIN tbl_treinamento_instrutor ON tbl_treinamento_instrutor.instrutor_treinamento = tbl_promotor_treinamento.promotor_treinamento
                                            AND tbl_treinamento_instrutor.treinamento = tbl_treinamento.treinamento
                                            ORDER BY tbl_treinamento_instrutor.data_input DESC LIMIT 1
                                        ) AS instrutor_nome ";
            }

            if (
                (array_key_exists("data_inicial", $_GET) && !empty($_GET['data_inicial'])) 
                &&
                (array_key_exists("data_final", $_GET) && !empty($_GET['data_final']))
            ) {
                $data_inicial = implode("-", array_reverse(explode("/", trim($_GET['data_inicial'])))) . " 00:00:00";
                $data_final = implode("-", array_reverse(explode("/", trim($_GET['data_final'])))) . " 23:59:59";
                $extraConds .= " 
                    AND tbl_treinamento.data_inicio >= '{$data_inicial}'
                    AND tbl_treinamento.data_fim <= '{$data_final}'
                ";
            }

            if (array_key_exists("titulo_pesquisa", $_GET) && !empty($_GET["titulo_pesquisa"])) {
                $titulo_pesquisa = $_GET['titulo_pesquisa'];
                $extraConds .= " AND tbl_treinamento.titulo ILIKE '%{$titulo_pesquisa}%' ";
            }

            if (array_key_exists("estado_pesquisa", $_GET) && !empty($_GET['estado_pesquisa'])) {
                $estado_pesquisa = trim($_GET['estado_pesquisa']);
                $extraConds .= " AND tbl_treinamento.estado = '{$estado_pesquisa}' ";
            }

            if (array_key_exists("cidade_pesquisa", $_GET) && !empty($_GET['cidade_pesquisa'])) {
                $cidade_pesquisa = (int)trim($_GET['cidade_pesquisa']);
                $extraConds .= " AND tbl_treinamento.cidade = {$cidade_pesquisa} ";
            }

            if (array_key_exists("instrutor_pesquisa", $_GET) && !empty($_GET['instrutor_pesquisa'])) {
                $instrutor_pesquisa = (int)trim($_GET['instrutor_pesquisa']);
                $extraConds .= " AND tbl_treinamento_instrutor.instrutor_treinamento = {$instrutor_pesquisa} ";
            }

            if (array_key_exists("tipo_pesquisa", $_GET) && !empty($_GET['tipo_pesquisa'])) {
                $tipo_pesquisa = (int)trim($_GET['tipo_pesquisa']);
                $extraConds .= " AND tbl_treinamento.treinamento_tipo = {$tipo_pesquisa} ";
            }
        }

		$sql = "
			SELECT
                		tbl_treinamento.treinamento,
				tbl_treinamento.titulo,
				tbl_treinamento.descricao,
				tbl_treinamento.ativo,
				tbl_treinamento.vagas,
				tbl_treinamento.vagas_min,
				TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
				TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY') AS data_fim,
			    	TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
				tbl_admin.nome_completo,
				tbl_linha.nome AS linha_nome,
				tbl_treinamento_tipo.nome AS treinamento_tipo,
				(
					SELECT COUNT(*)
					FROM tbl_treinamento_posto
					WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
					AND   tbl_treinamento_posto.ativo IS TRUE
					{$cond_tecnico}
				) AS qtde_postos"; 
                    if(in_array($login_fabrica, [169,170,193])){
                        $sql .= $sql_midea_treinamento;
                    }

            $sql .= "
                		{$select}
                		{$parametros_adicionais}
                		{$campo_cidade_uf}
			FROM tbl_treinamento";


                $sql .= "{$join_instrutor}
                         {$join_promotor}";    
           

			$sql .= "
			{$join_cidade_uf}
			JOIN tbl_admin ON tbl_admin.admin = tbl_treinamento.admin
			{$join_linha}
			LEFT JOIN tbl_treinamento_tipo USING(treinamento_tipo)
			WHERE tbl_treinamento.fabrica = {$login_fabrica}
            {$data_finalizado}
            {$extraConds}
		";
	}else{
        if (in_array($login_fabrica, array(175))){
            if ($_GET['treinamentos_realizado'] == 'sim'){
                $data_finalizado  = " AND tbl_treinamento.data_finalizado IS NOT NULL ";
                $data_finalizado .= " AND tbl_treinamento_tipo.nome = 'Presencial' ";
            }else{
                $data_finalizado = " AND tbl_treinamento.data_finalizado IS NULL ";    
            }
            
            $ativo                   = " AND tbl_treinamento.ativo IS TRUE ";
            $linhaMarca              = "<th>Linhas</th>";
            $campo_inicio_inscricao  = " TO_CHAR(tbl_treinamento.inicio_inscricao,'DD/MM/YYYY') AS inicio_inscricao, ";

            $relacaoLinhaTreinamento  = " LEFT JOIN tbl_treinamento_produto on tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento";
            $relacaoLinhaTreinamento .= " LEFT JOIN tbl_linha on tbl_linha.linha = tbl_treinamento_produto.linha ";
            $relacaoLinhaTreinamento .= " LEFT JOIN tbl_produto on tbl_produto.produto = tbl_treinamento_produto.produto ";
            $linhaMarcaSql  = " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.nome)), ', ', null) AS linhas, ";
            $linhaMarcaSql .= " tbl_treinamento.linha, ";
            $linhaMarcaSql .= " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_produto.descricao)), ', ', null) AS produtos, ";

            $campo_treinamento_tipo = " tbl_treinamento.treinamento_tipo, tbl_treinamento_tipo.nome AS treinamento_tipo_nome, ";

            $cond_treinamento_tipo = " LEFT JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo ";
        }else{
            $relacaoLinhaTreinamento = " JOIN      tbl_linha   USING(linha) ";    
            $ativo = "";
            $data_finalizado = "";
            $linhaMarca ="<th>Linha</th>";
            $linhaMarcaSql = " tbl_linha.nome AS linha_nome, ";
        }

        /*if (in_array($login_fabrica, [193])) {
            $campo_inicio_inscricao  = " TO_CHAR(tbl_treinamento.inicio_inscricao,'DD/MM/YYYY') AS inicio_inscricao, ";
            $campo_treinamento_tipo = " tbl_treinamento.treinamento_tipo, tbl_treinamento_tipo.nome AS treinamento_tipo_nome, ";
            $cond_treinamento_tipo  = " LEFT JOIN tbl_treinamento_tipo ON tbl_treinamento_tipo.treinamento_tipo = tbl_treinamento.treinamento_tipo ";
        }*/
		
		

		if (in_array($login_fabrica, [1])) {
			$relacaoLinhaTreinamento = "";
            $linhaMarcaSql = "";
			$linhaMarca ="<th>Marca</th>";
			$data_finalizado = "AND tbl_treinamento.data_finalizado IS NULL";

			if ($_GET['treinamentos_realizado'] == 'sim'){
                $data_finalizado = "AND tbl_treinamento.data_finalizado IS NOT NULL";
            }

		}

		$sql = "SELECT tbl_treinamento.treinamento,
				tbl_treinamento.titulo,
				tbl_treinamento.descricao,
				tbl_treinamento.ativo,
				tbl_treinamento.vagas,
				tbl_treinamento.vagas_min,
                $campo_treinamento_tipo
				TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
				TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
			    TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
                $campo_inicio_inscricao
				tbl_admin.nome_completo,
				$linhaMarcaSql
				(
					SELECT COUNT(*)
					FROM tbl_treinamento_posto
					WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
					AND   tbl_treinamento_posto.ativo IS TRUE
					$cond_tecnico
				)                                                     AS qtde_postos
			FROM tbl_treinamento
			JOIN      tbl_admin   USING(admin)
            $cond_treinamento_tipo
            $relacaoLinhaTreinamento
			WHERE tbl_treinamento.fabrica = $login_fabrica
			$data_finalizado
            $ativo
            ";
			if (!in_array($login_fabrica, array(175)) && $_GET['treinamentos_realizado'] != 'sim') {
                $sql .= (strlen($_GET['todos'])==0) ? " AND   tbl_treinamento.data_fim >= CURRENT_DATE " : " AND   tbl_treinamento.data_fim < CURRENT_DATE ";
			}
	}

	if (in_array($login_fabrica, [1])) {
		$sql .= " GROUP BY
				tbl_treinamento.treinamento,
				tbl_treinamento.titulo,
				tbl_treinamento.descricao,
				tbl_treinamento.ativo,
				tbl_treinamento.vagas,
				tbl_treinamento.vagas_min,
				data_inicio,
				data_fim,
			    prazo_inscricao,
				tbl_admin.nome_completo,
				qtde_postos" ;
	}
    if (in_array($login_fabrica, array(175))){
        $sql .= " GROUP BY tbl_treinamento.treinamento,
                    tbl_treinamento.titulo,
                    tbl_treinamento.descricao,
                    tbl_treinamento.ativo,
                    tbl_treinamento.vagas,
                    tbl_treinamento.vagas_min,
                    data_inicio,
                    data_fim,
                    prazo_inscricao,
                    inicio_inscricao,
                    nome_completo, treinamento_tipo_nome ";
    }

    $sql .= " ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo"; 

    $res = pg_query($con,$sql);
    
	if (pg_num_rows($res) > 0) {

		$resposta  .=  "<table id='tblTreinamento' class='table table-striped table-bordered table-fixed'>";
		$resposta  .=  "<thead>";

		switch ($login_fabrica) {
			case 1:
				$colspan = '10';
				break;
            case 169:
			case 170:
            case 193:
                $colspan = '14';
				break;
            case 175:
                $colspan = '15';
                break;
			default:
				$colspan = '9';
				break;
		}

		if (in_array($login_fabrica, array(175))){ 
            $resposta  .=  "<tr class='titulo_tabela'><th colspan='$colspan'> Treinamentos Agendados</th></tr>";
        }else{
            if (in_array($login_fabrica, [169,170])) {
                $resposta  .=  "<tr class='titulo_tabela'><th colspan='$colspan'> Treinamentos Previstos</th></tr>";    
            } else {
                $resposta  .=  "<tr class='titulo_tabela'><th colspan='$colspan'> Treinamentos Realizados</th></tr>";
            }
        }
		$resposta  .=  "<tr class='titulo_coluna'>";
		$resposta  .=  "<th>Titulo</th>";
		$resposta  .=  "<th class='date_column'>Data In&iacute;cio</th>";
        $resposta  .=  "<th class='date_column'>Data Fim</th>";
        if (in_array($login_fabrica, array(175))){
            $resposta .= '<th>Início Inscrição</th>';
            $resposta .= '<th>Prazo Inscrição</th>';
        }
		if ($treinamento_prazo_inscricao){
			$resposta .= '<th>Prazo Inscrição</th>';
		}
		if (!in_array($login_fabrica, array(169,170,175,193)))
        {
            $resposta  .=  $linhaMarca;
        }
        if (in_array($login_fabrica, array(175))){
            $resposta .= "<th>Produtos</th>";
        }
        if (in_array($login_fabrica, array(169,170,193)))
        {
            $resposta .= "<th>Cidade/UF</th>";
            $resposta .= "<th>Instrutor</th>";
        }
		$resposta  .=  "<th>Vagas</th>";
		if ($treinamento_vagas_min){
			$resposta  .=  "<th>Mín. Vagas</th>";
		}
		$resposta  .=  "<th>Inscritos</th>";

        if (in_array($login_fabrica, array(169,170,193))){
           $resposta  .=  "<th>Presentes</td>";
        }

		$resposta  .=  "<th>Ativo</td>";
		if(!in_array($login_fabrica, array(169,170,175,193))){
			if (in_array($login_fabrica, [1])) {
				$resposta  .=  "<th colspan='1'>Status</th>
                                <th colspan='1'>Notifica&ccedil;&otilde;es</th>";
			} else {
				$resposta  .=  "<th>Op&ccedil;&otilde;es</th>";
			}
		}else{
            if (!in_array($login_fabrica, array(175))){
                $resposta .= "<th>Tipo</th>";    
            }
		}

		if(strlen($_GET['todos'])==0 || (in_array($login_fabrica, [169,170,193]) && $_REQUEST['treinamentos_realizado'] == 'sim')) {
			$resposta  .=  "<th>Alterar</th>";
		}

		if(in_array($login_fabrica, array(169,170,193)) && !array_key_exists("todos", $_GET)){
			$resposta  .=  "<th>Finalizar Treinamento</td>";
			$resposta  .= "<th>Cadastrar Técnicos</th>";
			$resposta  .= "<th>Lista de presença</th>";
		}

		$resposta  .=  "</tr>";
		$resposta  .=  "</thead>";
		$resposta  .=  "<tbody>";

		$columnSpan = preg_match_all("/\/td>/", $resposta, $void) + 1;

		if (in_array($login_fabrica, array(169,170,193))){
			$count_treinamento = array();
			$count_palestra    = array();
			$posto_treinamento = array();
			$notas_treinamento = array();
		}
		for ($i=0; $i<pg_num_rows($res); $i++){

            if (in_array($login_fabrica, array(175))){
                $treinamento_tipo      = trim(pg_fetch_result($res,$i,'treinamento_tipo'));
                $treinamento_tipo_nome = pg_fetch_result($res, $i, 'treinamento_tipo_nome');

                if (strtolower($treinamento_tipo_nome) != "presencial"){
                    continue;
                }
            }
			$treinamento     = trim(pg_fetch_result($res,$i,'treinamento'));
			$titulo          = trim(pg_fetch_result($res,$i,'titulo'));
            if (mb_check_encoding($titulo, 'UTF-8')) {
                $titulo = utf8_decode($titulo);
            }
			$descricao       = trim(pg_fetch_result($res,$i,'descricao'));
			$ativo           = trim(pg_fetch_result($res,$i,'ativo'));
			$data_inicio     = trim(pg_fetch_result($res,$i,'data_inicio'));
			$data_fim        = trim(pg_fetch_result($res,$i,'data_fim'));
			$inicio_inscricao = trim(pg_fetch_result($res,$i,'inicio_inscricao'));
            $prazo_inscricao = trim(pg_fetch_result($res,$i,'prazo_inscricao'));
			$nome_completo   = trim(pg_fetch_result($res,$i,'nome_completo'));
			if ($login_fabrica == 1) {
                unset($array_linha_nome);
                unset($linha_nome);
                $sql_marca = "SELECT (parametros_adicionais -> 'marca') AS marca FROM tbl_treinamento WHERE fabrica = $login_fabrica AND treinamento = $treinamento";
                $res_marca = pg_query($con, $sql_marca);
                if (pg_num_rows($res_marca) > 0) {
                    $marca_sql = pg_fetch_result($res_marca, 0, 'marca');
                    $marca_sql  = json_decode($marca_sql);    
                    $sql_linha_nome = "SELECT nome FROM tbl_marca WHERE fabrica = $login_fabrica AND marca in (".implode(',', $marca_sql).")";
                    $res_linha_nome = pg_query($con, $sql_linha_nome);
                    if (pg_num_rows($res_linha_nome) > 0) {
                        for ($m=0; $m < pg_num_rows($res_linha_nome); $m++) { 
                            $array_linha_nome[] = pg_fetch_result($res_linha_nome, $m, 'nome'); 
                        }
                    }
                    $linha_nome = implode(',', $array_linha_nome);    
                }
            } else {
                $linha_nome      = trim(pg_fetch_result($res,$i,'linha_nome'));
            }
			
            $vagas           = trim(pg_fetch_result($res,$i,'vagas'));
			$vagas_min       = trim(pg_fetch_result($res,$i,'vagas_min'));
            $qtde_postos     = trim(pg_fetch_result($res,$i,'qtde_postos'));
            
            if (in_array($login_fabrica, array(169,170,193))) {
                $qtde_palestra      = trim(pg_fetch_result($res,$i,'qtde_palestra'));
                $qtde_treinamento   = trim(pg_fetch_result($res,$i,'qtde_treinamento'));
                $qtde_participantes = trim(pg_fetch_result($res,$i,'qtde_participante'));
                $tecnico_nome       = trim(pg_fetch_result($res,$i,'instrutor_nome'));
                $local_treinamento  = trim(pg_fetch_result($res,$i,'local'));
                $parametros_adicionais = json_decode(trim(pg_fetch_result($res, $i, 'parametros_adicionais')), true);
                $motivo_cancelamento   = $parametros_adicionais['motivo_cancelamento'];
                $carga_horaria         = $parametros_adicionais['carga_horaria'];
                $cidade                = trim(pg_fetch_result($res,$i,'cidade_nome'));
                $estado                = trim(pg_fetch_result($res,$i,'estado'));
                $presentes             = pg_fetch_result($res, $i, 'presentes');
                $xinstrutor_nome       = $tecnico_nome;
            }

			if(in_array($login_fabrica, array(169,170,193))){
				$treinamento_tipo = trim(pg_fetch_result($res,$i,'treinamento_tipo'));                

				if (strtolower($treinamento_tipo) == "treinamento"){

                    if ($ativo == 't') {
					   $count_treinamento[] = $treinamento;
    					//$count_participantes_treinamento += $qtde_postos;
                        $count_participantes_treinamento += $presentes;
                    }


					$sql_posto = "
						SELECT 
							tbl_treinamento_posto.posto,
							tbl_treinamento_posto.nota_tecnico,
                            tbl_treinamento_posto.participou,
							tbl_tecnico.nome AS tecnico_nome,
							tbl_posto.nome AS posto_nome,
                            tbl_tecnico.tipo_tecnico,
                            tbl_tecnico.dados_complementares
						FROM tbl_treinamento_posto
						JOIN tbl_treinamento USING(treinamento)
						LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_treinamento_posto.posto
							AND tbl_posto_fabrica.fabrica = $login_fabrica
						LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_treinamento_posto.tecnico
							AND tbl_tecnico.fabrica = $login_fabrica
						WHERE tbl_treinamento.treinamento = $treinamento
						AND tbl_treinamento.fabrica = $login_fabrica
						AND tbl_treinamento.ativo IS TRUE
                        AND tbl_treinamento.data_finalizado IS NOT NULL
						AND tbl_treinamento_posto.tecnico IS NOT NULL
                        AND tbl_treinamento_posto.ativo IS TRUE
                        /*AND tbl_treinamento_posto.aplicado IS TRUE*/";
                        
                    $res_posto = pg_query($con, $sql_posto);
					if (pg_num_rows($res_posto) > 0){
						$dados_treinamento_posto = pg_fetch_all($res_posto);

						unset($posto_treinamento);
						foreach ($dados_treinamento_posto as $key => $value) {

							$xnota_tecnico 	= $value['nota_tecnico'];
							$xtecnico_nome 	= $value['tecnico_nome'];
							$xposto_nome 	= $value['posto_nome'];
            				$xtipo_tecnico  = $value['tipo_tecnico'];
                            $dados_complementares = $value['dados_complementares'];
                            $xparticipou    = $value['participou'];

                            if (!empty($dados_complementares)){
                                $dados_complementares = json_decode($dados_complementares, true);
                            }

                            if (empty($xposto_nome) AND $xtipo_tecnico == "TF"){
                                $xposto_nome = $dados_complementares["empresa"];
            				}else{
                				$xposto_nome = $xposto_nome;
            				}

							$notas_treinamento[] = $value['nota_tecnico'];
							$posto_treinamento[] = $value['posto'];

							if ($excel){
                                if(in_array($login_fabrica, [169,170,193])){
                                    if($xparticipou == "t"){
                                        $xcompareceu = "SIM";
                                    } else {
                                        $xcompareceu = "NÃO";
                                    }
                                    $dados = "$titulo;$data_inicio;$data_fim;$cidade;$estado;$local_treinamento;$xinstrutor_nome;$linha_nome;$vagas;$qtde_postos;$presentes;$treinamento_tipo;$xposto_nome;$xtecnico_nome;$xcompareceu;$xnota_tecnico\n";    
                                } else {
                                    $dados = "$titulo;$data_inicio;$data_fim;$cidade;$estado;$local_treinamento;$xinstrutor_nome;$linha_nome;$vagas;$qtde_postos;$presentes;$treinamento_tipo;$xposto_nome;$xtecnico_nome;$xnota_tecnico\n";
                                }								
								fwrite($file,$dados);
							}
						}
                        $posto_treinamento = array_unique($posto_treinamento);
						$total_posto_treinamento += count($posto_treinamento);
					}
				}else{

                    if ($ativo == 't') {
    					$count_palestra[] = $treinamento;
    					$count_participantes_palestra += $vagas;
                    }

					unset($xposto_nome);
					unset($xtecnico_nome);
					unset($xnota_tecnico);
					if ($excel){
                            		    $dados = "$titulo;$data_inicio;$data_fim;$cidade;$estado;$local_treinamento;$xinstrutor_nome;$linha_nome;$vagas;$qtde_postos;$vagas;$treinamento_tipo;$xposto_nome;$xtecnico_nome;$xnota_tecnico\n";
                    			    fwrite($file,$dados);
					}
				}
			}
            if(in_array($login_fabrica, array(175))){
                $treinamento_tipo = trim(pg_fetch_result($res,$i,'treinamento_tipo'));
                $inicio_inscricao = trim(pg_fetch_result($res,$i,'inicio_inscricao'));
                $produtos         = trim(pg_fetch_result($res,$i,'produtos')); 
                $linhas           = trim(pg_fetch_result($res,$i,'linhas'));
            }
			if($ativo == 't'){
                // oculta um nº para a ordernação poder funcionar
                if (in_array($login_fabrica, array(169,170,193))) {
                    $order_text = '<span style="display:none;">9</span>';
                }
                $statusTreinamento   = "<img src='imagens_admin/status_verde.gif' id='img_ativo_$i'> {$order_text}";
                $x_ativo = "Confirmado";
            } else {
                // oculta um nº para a ordernação poder funcionar
                if (in_array($login_fabrica, array(169,170,193))) {
                    $alt_title  = "alt='{$motivo_cancelamento}' title='$motivo_cancelamento'";
                    $order_text = '<span style="display:none;">1</span>';
                }
                $statusTreinamento = "<img src='imagens_admin/status_vermelho.gif' id='img_ativo_$i' {$alt_title}> {$order_text}";
                $x_ativo = "Cancelado";
            }
            if($cor=="#F1F4FA")$cor = '#F7F5F0';
            else               $cor = '#F1F4FA';

            if($qtde_postos==0)$qtde_postos = "<font color='#990000'>$qtde_postos</font>";

			$resposta  .=  "<tr>";
			if($login_fabrica == 138 || $login_fabrica == 117){
				$resposta  .=  "<td><a class='shadow_treinamento' href='#' data-url='detalhes_treinamento_new.php?treinamento=$treinamento' >".$titulo."</a></td>";
			}else{
                if (in_array($login_fabrica, array(175))){
                    //$resposta  .=  "<td>".$titulo."</td>";
                    $resposta  .=  "<td><a class='shadow_treinamento' href='#' data-url='detalhes_treinamento.php?treinamento=$treinamento' >".$titulo."</a></td>";
                }else{
				    $resposta  .=  "<td><a class='shadow_treinamento' href='#' data-url='detalhes_treinamento.php?treinamento=$treinamento' >".$titulo."</a></td>";
                }
			}
			$resposta  .=  "<td>$data_inicio</td>";
			$resposta  .=  "<td>$data_fim</td>";
            if (in_array($login_fabrica, array(175))){
                $resposta .= '<td>'.$inicio_inscricao.'</td>';
                $resposta .= '<td>'.$prazo_inscricao.'</td>';
            }
			if ($treinamento_prazo_inscricao){
				$resposta  .=  "<td>$prazo_inscricao</td>";
			}
			if (!in_array($login_fabrica, array(169,170,175,193)))
            {
                $resposta  .=  "<td>".htmlentities($linha_nome)."</td>";
            }
            if (in_array($login_fabrica, array(175))){
                $resposta .= "<td>".$produtos."</td>";
            }
            if (in_array($login_fabrica, array(169,170,193)))
            {
                $resposta .= "<td>".$cidade."-".$estado."</td>";
                $resposta .= "<td>".$tecnico_nome."</td>";
            }

			$resposta  .=  "<td class='tac'>$vagas</td>";
			if ($treinamento_vagas_min){
				$resposta  .=  "<td>$vagas_min</td>";
			}
			$resposta  .=  "<td class='tac'>$qtde_postos</td>";

            if (in_array($login_fabrica, array(169,170,193))){
                if (strtolower($treinamento_tipo) == "palestra"){
                    $resposta .= "<td class='tac'>$vagas</td>";
                }else{
                    $resposta .= "<td class='tac'>$presentes</td>";
                }
            }

			$resposta  .=  "<td class='tac'>$statusTreinamento</td>";
			if(!in_array($login_fabrica, array(169,170,175,193))){
				if ($x_ativo == "Cancelado"){
					$resposta  .= "<td class='tac'><button type='button' class='btn btn-danger btn-small seleciona-treinamento' data-treinamento='$treinamento'>Cancelado</button></td>";
				}else{
					$resposta  .= "<td class='tac'><button type='button' class='btn btn-primary btn-small seleciona-treinamento' data-treinamento='$treinamento'>Confirmado</button></td>";
				}
				if(in_array($login_fabrica, [1])){
					$data_fim_reverse = explode('/', $data_fim);
					$data_fim_reverse = implode('-', array_reverse($data_fim_reverse));
					if ($data_fim_reverse >= date("Y-m-d")) {
						$resposta  .=  "<td class='tac'><button type='button' class='btn btn-warning btn-small envia-notificao' data-url='treinamento_notificacao.php?treinamento=$treinamento'>Notificação</button></td>";
					} elseif ( strlen($_GET['todos'])==0 ){
						$resposta  .=  "<td></td>";
					} else {
                        $resposta  .=  "<td></td>";
                    }
				}
			}else{
				if (!in_array($login_fabrica, array(175))){
                		    $resposta .= "<td class='tac'>".$treinamento_tipo."</td>";
		                }
			}
			if(strlen($_GET['todos'])==0 || (in_array($login_fabrica, [169,170,193]) && $_REQUEST['treinamentos_realizado'] == 'sim')) {
				//$resposta  .=  "<td><a href='treinamento_cadastro.php?treinamento=$treinamento'>Alterar</td>";
				$resposta  .=  "<td class='tac'><a role='button' class='btn btn-success btn-small' href='treinamento_cadastro.php?treinamento=$treinamento'>
				Alterar</a>";
				if ($login_fabrica == 1) {
                    $resposta .= "
                        <button type='button' class='btn btn-info btn-small' id='concluir_$treinamento' data-url='treinamento_cadastro.php?treinamento=$treinamento' >Concluir</button>
                        <button type='button' class='btn btn-danger btn-small' id='excluir_$treinamento' data-url='treinamento_cadastro.php?treinamento=$treinamento' >Excluir</button>
                    ";
				}
				$resposta .= "</td>";
			}

            if(in_array($login_fabrica, array(169,170,193)) && !array_key_exists("todos", $_GET)){
                if ($ativo == 't'){
                    $resposta  .=  "<td class='tac'><a href='finaliza_treinamento.php?treinamento=$treinamento'>Finalizar</td>";
                }else{
                    $resposta  .=  "<td class='tac'>Cancelado</td>";
                }
                if (strtolower($treinamento_tipo) != "palestra"){
                    if ($ativo == 't'){
                        $resposta  .= "<td class='tac'><button type='button' data-id_treinamento='$treinamento' class=' btn btn-small btn-primary btn_cadastra_tecnico'>Cadastrar</button></td>";
                        $resposta  .= "<td class='tac'><a href='lista_presenca_treinamento.php?treinamento=$treinamento' target='_blank'>Imprimir</td>";
                    }else{
                        $resposta  .= "<td class='tac'><button type='button' class=' btn btn-small btn-primary' disabled>Cadastrar</button></td>";
                        $resposta  .= "<td class='tac'>Cancelado</td>";
                    }
                }else{
                    $resposta  .= "<td></td><td></td>";
                }
            }
            $resposta  .=  "</tr>";
        }
        $resposta  .=  "</tbody>";

        if (in_array($login_fabrica, array(169,170,193))){
            
            if ($_GET['treinamentos_realizado'] == 'sim'){
                
                $notas_treinamento = array_count_values($notas_treinamento);
                $total_nota = array_sum($notas_treinamento);
                
                $notas_treinamento = array_map(function($x) use ($total_nota){
                    return ($x/$total_nota)*100;
                }, $notas_treinamento);
                $resposta .= "
                    <tfoot>
                        <tr class='titulo_coluna'>
                            <th class='tal'>Qtde Treinamento: ".count($count_treinamento)."</th>
                            <th class='tal' colspan='2'>Qtde Participantes: ".$count_participantes_treinamento."</th>
                            <th class='tal' colspan='2'>Qtde Postos: ".$total_posto_treinamento."</th>
                            <th colspan='6'>
                                % Notas
                                <table class='table table-bordered'>
                                    <thead>
                                        <tr>";
                                            for ($i=0; $i < 11; $i++) { 
                                                $resposta .= "
                                                    <th class='titulo_coluna'> $i </th>
                                                ";
                                            }   
                                    $resposta .="</tr>
                                    </thead>
                                    <tbody>
                                        <tr>";
                                            $dados_resposta = array();
                                            for ($i=0; $i < 11; $i++) { 
                                                $resposta .= "<th>";
                                                
                                                if (!empty($notas_treinamento[$i])){
                                                    $resposta .=  number_format($notas_treinamento[$i],2,",","."); 
                                                    $dados_resposta[] = number_format($notas_treinamento[$i],2,".",","); 
                                                }else{
                                                    $resposta .= 0;
                                                    $dados_resposta[] = 0;
                                                }

                                                $resposta .="</th>";
                                            }
                                    $resposta .="</tr>
                                    </tbody>
                                </table>
                            </th>                            
                        </tr>
                        <tr class='titulo_coluna'>
                            <th class='tal'>Qtde Palestra: ".count($count_palestra)."</th>
                            <th class='tal' colspan='10'>Qtde Participantes: ".$count_participantes_palestra."</th>
                        </tr>
                    </tfoot>
                ";
                $dados_resposta = implode(";",$dados_resposta);
                
                if($excel){
                    fwrite($file, utf8_encode("\n Qtde Treinamentos;Qtde Participantes;Qtde Postos;Notas;0;1;2;3;4;5;6;7;8;9;10;\n"));
                    $countTreinamentos = count($count_treinamento);
                    $dados_t = "$countTreinamentos;$count_participantes_treinamento;$total_posto_treinamento;%Notas;$dados_resposta \n";
                    fwrite($file,$dados_t);
                        
                    $countPaletra = count($count_palestra); 
                    fwrite($file, utf8_encode("\n Qtde Palestra;Qtde Participantes;\n"));
                    $dados_p = "$countPaletra;$count_participantes_palestra;\n";
                    fwrite($file,$dados_p);

                    fclose($file);
                    if (file_exists("/tmp/{$fileName}")) {
                        system("mv /tmp/{$fileName} xls/{$fileName}");
                        exit(json_encode(array("ok" => utf8_encode("xls/{$fileName}"))));
                    }
                }
            }
        }

		$resposta .= " </table>";
		
        // tabela 'treinamentos online'
        if (in_array($login_fabrica, array(175))){
            if (!strlen($_GET['treinamentos_realizado']) > 0){
                $resposta  .=  "<table id='tblTreinamento' class='table table-striped table-bordered table-fixed'>";
                    $resposta  .=  "<thead>";
                        $resposta  .=  "<tr class='titulo_tabela'><th colspan='$colspan'> Treinamentos Online</th></tr>";
                        $resposta  .=  "<tr class='titulo_coluna'>";
                        $resposta  .=  "<th>Título</th>";
                        $resposta  .=  "<th>Ativo</th>";
                        /*$resposta  .=  (in_array($login_fabrica, [193])) ? "<th>Linha</th>" : "";*/
                        $resposta  .=  (in_array($login_fabrica, [175])) ? "<th>Produtos</th>" : "";
                        $resposta  .=  "<th>Inscritos</th>";
                        $resposta  .=  "<th>Alterar</th>";
                        $resposta  .=  "</tr>";
                    $resposta  .=  "</thead>";
                    $resposta  .=  "<tbody>";
                        for ($i=0; $i<pg_num_rows($res); $i++){
                                $treinamento_tipo      = trim(pg_fetch_result($res,$i,'treinamento_tipo'));
                                $treinamento_tipo_nome = trim(pg_fetch_result($res,$i,'treinamento_tipo_nome'));
                                if (strtolower($treinamento_tipo_nome) != "online"){
                                    continue;
                                }
                                $treinamento      = trim(pg_fetch_result($res,$i,'treinamento'));
                                $titulo           = trim(pg_fetch_result($res,$i,'titulo'));
                                $ativo            = trim(pg_fetch_result($res,$i,'ativo'));
                                $vagas            = trim(pg_fetch_result($res,$i,'vagas'));
                                $qtde_postos      = trim(pg_fetch_result($res,$i,'qtde_postos'));
                                /*$linhas           = (in_array($login_fabrica, [193])) ?  trim(pg_fetch_result($res,$i,'linha_nome')) : trim(pg_fetch_result($res,$i,'linhas'));*/
                                $produtos         = trim(pg_fetch_result($res,$i,'produtos'));

                                if($ativo == 't'){
                                    $statusTreinamento   = "<img src='imagens_admin/status_verde.gif' id='img_ativo_$i'>";
                                    $x_ativo = "Confirmado";
                                }else{
                                    $statusTreinamento = "<img src='imagens_admin/status_vermelho.gif' id='img_ativo_$i'>";
                                    $x_ativo = "Cancelado";
                                }
                                if($qtde_postos==0)$qtde_postos = "<font color='#990000'>$qtde_postos</font>";

                                $resposta  .=  "<tr>";

                                if (in_array($login_fabrica, array(175))){
                                    $resposta  .=  "<td><a class='shadow_treinamento' href='#' data-url='detalhes_treinamento.php?treinamento=$treinamento' >".$titulo."</a></td>";
                                }else{
                                    $resposta  .=  "<td>".$titulo."</td>";
                                }

                                $resposta  .=  "<td>$statusTreinamento</td>";
                                /*$resposta  .=  (in_array($login_fabrica, [193])) ? "<td>".$linhas."</td>" : "";*/
                                $resposta  .=  (in_array($login_fabrica, [175])) ? "<td>".$produtos."</td>" : "";
                                $resposta  .=  "<td>$qtde_postos</td>";
                                $resposta  .=  "<td class='tac'><a role='button' class='btn btn-success btn-small' href='treinamento_cadastro.php?treinamento=$treinamento'>Alterar</a>";
                        }
                    $resposta  .=  "</tbody>";
                $resposta  .=  "</table>";
            }
        }
        exit(json_encode(array("ok" => utf8_encode($resposta))));
	}else{
		exit(json_encode(array("nenhum" => utf8_encode("Não foi encontrado nenhum treinamento realizado!"))));
	}
}
//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {

    $treinamento = $_GET["treinamento"];
    $cor         = $_GET["cor"];

    $sql = "SELECT tbl_treinamento.treinamento,
            tbl_treinamento.titulo,
            tbl_treinamento.descricao,
            tbl_treinamento.ativo,
            tbl_treinamento.local,
            tbl_treinamento.vagas,
            tbl_treinamento.vagas_min,
            TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
            TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
            TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
            tbl_admin.nome_completo,
            tbl_linha.nome                                        AS linha_nome,
            tbl_familia.descricao                                 AS familia_descricao,
            (
                SELECT COUNT(*)
                FROM tbl_treinamento_posto
                WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                AND   tbl_treinamento_posto.ativo IS TRUE
            )                                                     AS qtde_postos,
            tbl_treinamento.adicional,
            tbl_treinamento.cidade,
            tbl_treinamento.visivel_portal
        FROM tbl_treinamento
        JOIN      tbl_admin   USING(admin)
        JOIN      tbl_linha   USING(linha)
        LEFT JOIN tbl_familia USING(familia)
        WHERE tbl_treinamento.fabrica = $login_fabrica
        AND   tbl_treinamento.treinamento = $treinamento
        ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo" ;

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $treinamento       = trim(pg_fetch_result($res,0,'treinamento'));
        $titulo            = trim(pg_fetch_result($res,0,'titulo'));
        $descricao         = trim(pg_fetch_result($res,0,'descricao'));
        $ativo             = trim(pg_fetch_result($res,0,'ativo'));
        $data_inicio       = trim(pg_fetch_result($res,0,'data_inicio'));
        $data_fim          = trim(pg_fetch_result($res,0,'data_fim'));
        $prazo_inscricao   = trim(pg_fetch_result($res,0,'prazo_inscricao'));
        $nome_completo     = trim(pg_fetch_result($res,0,'nome_completo'));
        $linha_nome        = trim(pg_fetch_result($res,0,'linha_nome'));
        $familia_descricao = trim(pg_fetch_result($res,0,'familia_descricao'));
        $vagas             = trim(pg_fetch_result($res,0,'vagas'));
        $vagas_min         = trim(pg_fetch_result($res,0,'vagas_min'));
        $qtde_postos       = trim(pg_fetch_result($res,0,'qtde_postos'));
        $adicional         = trim(pg_fetch_result($res,0,'adicional'));
        $local             = trim(pg_fetch_result($res,0,'local'));
        $cidade            = trim(pg_fetch_result($res,0,'cidade'));
        $visivel_portal    = trim(pg_fetch_result($res,0,'visivel_portal'));

        $array_resposta['Tema'] = htmlentities($titulo);
        $array_resposta['Linha'] = htmlentities($linha_nome);
        $array_resposta['Fam&iacute;lia'] = htmlentities($familia_descricao);
        $array_resposta['Data de In&iacute;cio'] = $data_inicio;
        $array_resposta['Data de T&eacute;rmino'] = $data_fim;
        if ($treinamento_prazo_inscricao) {
            $array_resposta['Inscri&ccedil;&otilde;es at&eacute;'] = $prazo_inscricao;
        }
        $array_resposta['Informa&ccedil;&otilde;es Adicionais'] = htmlentities($adicional);
        if ($login_fabrica == 117) { //elgin
            $visivel_portal = ($visivel_portal == 't') ? 'Sim':'Não';
            $array_resposta['Visualizar Portal'] = htmlentities($visivel_portal);
        }
        if ($treinamento_vagas_min) {
            $array_resposta['Mínimo de Vagas'] = $vagas_min;
        }
        $array_resposta['Vagas'] = $vagas;
        $array_resposta['Inscritos'] = $qtde_postos;

        $resposta .= "<table class='table' style='border:0px !important' >
                        <tr>
                            <td valign='top' class='span6'  border=0>";

            $resposta .= "<table  span6' style='border:0px !important'>
            <tbody>";
            foreach ($array_resposta as $dt=>$dd) {
                $corLinha = ($corLinha == '#F7F5F0') ? '#F1F4FA' : '#F7F5F0';
                $resposta .= "<tr bgcolor='$corLinha'>";
                $resposta .= "<td class='span3' align='left'  border=0><b>$dt</b></td>";
                $resposta .= "<td class='span3' align='left'  border=0>$dd</td>";
                $resposta .= "</tr>";
            }
            $resposta .= "</tbody></table>";

            $resposta .= "</td><td class='descricao_detalhe span5'  border=0>";
            $resposta .= "<div><b>Descri&ccedil;&atilde;o:</b><br>".nl2br(htmlentities($descricao))."</div></td>";

            if ($login_fabrica == 117 or $login_fabrica == 42) {
                if ($login_fabrica == 117 && $cidade != "") {
                    $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";

                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $cidade        = pg_fetch_result($res,0,'cidade');
                        $nome_cidade   = pg_fetch_result($res,0,'nome');
                        $estado_cidade = pg_fetch_result($res,0,'estado');
                    }else{
                        $cidade = "";
                        $nome_cidade = "";
                        $estado_cidade = "";
                    }
                    $local = $local.", ".$nome_cidade." - ".$estado_cidade;
                }
                $resposta .= "<td valign='top' align='justify' bgcolor='#F1F4FA'><b>Local:</b><br>".htmlentities($local)."</td>";
            }
            $resposta .= "</tr>";
            $resposta .= "</table>";

        $resposta .= "</td></tr>";
        $resposta .= "</table>";

    }

    $sql = "SELECT  tbl_treinamento_posto.treinamento_posto,
                    tbl_tecnico.nome     AS tecnico_nome,
                    tbl_tecnico.rg       AS tecnico_rg,
                    tbl_tecnico.cpf      AS tecnico_cpf,
                    tbl_tecnico.email    AS tecnico_email,
                    tbl_tecnico.telefone AS tecnico_fone,
                    tbl_treinamento_posto.ativo,
                    tbl_treinamento_posto.hotel,
                    tbl_treinamento_posto.participou,
                    tbl_treinamento_posto.confirma_inscricao,
                    tbl_treinamento_posto.promotor,
                    tbl_treinamento_posto.motivo_cancelamento AS motivo,
                    TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
                    TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao,
                    tbl_posto.nome                                             AS posto_nome,
                    tbl_posto.estado,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_promotor_treinamento.nome,
                    tbl_treinamento_posto.observacao    AS observacao_antigo,
                    tbl_treinamento_posto.tecnico_nome  AS tecnico_nome_antigo,
                    tbl_treinamento_posto.tecnico_rg    AS tecnico_rg_antigo,
                    tbl_treinamento_posto.tecnico_cpf   AS tecnico_cpf_antigo,
                    tbl_treinamento_posto.tecnico_email AS tecnico_email_antigo,
                    tbl_treinamento_posto.tecnico_fone  AS tecnico_fone_antigo
               FROM tbl_treinamento_posto
          LEFT JOIN tbl_promotor_treinamento USING(promotor_treinamento)
          LEFT JOIN tbl_posto USING(posto)
          LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto       = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
          LEFT JOIN tbl_admin         ON tbl_treinamento_posto.admin   = tbl_admin.admin
          LEFT JOIN tbl_tecnico       ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
              WHERE tbl_treinamento_posto.treinamento = $treinamento
                AND tbl_treinamento_posto.ativo IS TRUE
           ORDER BY tbl_posto.nome" ;

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $resposta  .=  "<table border='0' cellpadding='0' cellspacing='0' class='table table-striped table-fixed'  align='center' width='700px'>";
        $resposta  .=  "<thead>";
        $resposta  .=  "<TR class='titulo_coluna'  height='25'>";
        $resposta  .=  "<th>Posto</th>";
        $resposta  .=  "<th width='25'>UF</th>";
        $resposta  .=  "<th>Informações do T&eacute;cnico</th>";
        if ($adicional) $resposta .= "<th WIDTH=110>".htmlentities($adicional)."</th>";
        if($login_fabrica == 20) $resposta  .=  "<th width='80'>Promotor</th>";
        $resposta  .=  "<th >Data</th>";
        $resposta  .=  "<th width='60' colspan='2'>Inscri&ccedil;&atilde;o</th>";
        $resposta  .=  "<th width='60' colspan='2'>Confirmado<br> por email</th>";
        if($login_fabrica != 117){
            $resposta  .=  "<th width='60' colspan='2'>Hotel</th>";
        }
        $resposta  .=  "<th width='60' colspan='2'>Presente</th>";
        $resposta  .=  "<th >Motivo Cancelamento</th>";
        $resposta  .=  "</TR>";
        $resposta  .=  "</thead>";

        for ($i=0; $i<pg_num_rows($res); $i++){

            $treinamento_posto = trim(pg_fetch_result($res,$i,'treinamento_posto'));
            $tecnico_nome      = trim(pg_fetch_result($res,$i,'tecnico_nome'));
            if($tecnico_nome == "" and trim(pg_fetch_result($res,$i,'tecnico_nome_antigo')) != ""){
                $tecnico_nome  = trim(pg_fetch_result($res,$i,'tecnico_nome_antigo'));
                $tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg_antigo'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
                $tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf_antigo'));
                $tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email_antigo'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
            }else{
                $tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
                $tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf'));
                $tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email'));
                $tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
            }
            $tecnico_tipo_sanguineo       = trim(pg_fetch_result($res,$i,'tecnico_tipo_sanguineo'));
            $tecnico_calcado              = trim(pg_fetch_result($res,$i,'tecnico_calcado'));
            $tecnico_celular              = trim(pg_fetch_result($res,$i,'tecnico_celular'));
            $tecnico_doencas              = trim(pg_fetch_result($res,$i,'tecnico_doencas'));
            $tecnico_medicamento          = trim(pg_fetch_result($res,$i,'tecnico_medicamento'));
            $tecnico_necessidade_especial = trim(pg_fetch_result($res,$i,'tecnico_necessidade_especial'));
            $motivo                       = trim(pg_fetch_result($res,$i,'motivo'));
            $data_inscricao               = trim(pg_fetch_result($res,$i,'data_inscricao'));
            $hora_inscricao               = trim(pg_fetch_result($res,$i,'hora_inscricao'));
            $posto_nome                   = trim(pg_fetch_result($res,$i,'posto_nome'));
            $estado                       = trim(pg_fetch_result($res,$i,'estado'));
            $codigo_posto                 = trim(pg_fetch_result($res,$i,'codigo_posto'));
            $ativo                        = trim(pg_fetch_result($res,$i,'ativo'));
            $hotel                        = trim(pg_fetch_result($res,$i,'hotel'));
            $participou                   = trim(pg_fetch_result($res,$i,'participou'));
            $promotor                     = trim(pg_fetch_result($res,$i,'promotor'));
            $confirma                     = trim(pg_fetch_result($res,$i,'confirma_inscricao'));
            $nome                         = trim(pg_fetch_result($res,$i,'nome'));
            $observacao                   = trim(pg_fetch_result($res,$i,'observacao'));


            if($ativo == 't'){
                $ativo   = "<img src='imagens_admin/status_verde.gif' id='tec_img_ativo_$i'>";
                $x_ativo = "Confirmado";
            }
            else{
                $ativo = "<img src='imagens_admin/status_vermelho.gif' id='tec_img_ativo_$i'>";
                $x_ativo = "Cancelado";
            }

            if($participou == 't'){
                $participou = "<img src='imagens_admin/status_verde.gif' id='participou_img_$i'>";
                $x_participou = "Sim";
            }
            else{
                $participou = "<img src='imagens_admin/status_vermelho.gif' id='participou_img_$i'>";
                $x_participou = "Não";
            }
            if($confirma == 't'){
                $confirma = "<img src='imagens_admin/status_verde.gif' id='confirma_img_$i'>";
                $x_confirma = "Sim";
            }
            else{
                $confirma = "<img src='imagens_admin/status_vermelho.gif' id='confirma_img_$i'>";
                $x_confirma = "Não<br><a href='treinamento_cadastro.php?treinamento_posto=$treinamento_posto&ajax=enviar'>Enviar</a>";
            }
            if($login_fabrica != 117){
                if($hotel == 't'){
                    $hotel = "<img src='imagens_admin/status_verde.gif' id='hotel_img_$i'>";
                    $x_hotel = "Sim";
                }
                else{
                    $hotel = "<img src='imagens_admin/status_vermelho.gif' id='hotel_img_$i'>";
                    $x_hotel = "Não";
                }
            }

            if($cor=="#F1F4FA")$cor = '#F7F5F0';
            else               $cor = '#F1F4FA';

            $resposta  .=  "<TR class='Conteudo' bgcolor='$cor'>";
            $resposta  .=  "<TD align='left'>$codigo_posto - $posto_nome </TD>";
            $resposta  .=  "<TD align='center'nowrap>$estado</TD>";
            if($login_fabrica == 42){
                $resposta  .=  "<TD align='left'nowrap>
                                <b>Nome: </b>".htmlentities($tecnico_nome)." <br>
                                <b>E-mail:</b> $tecnico_email<br>
                                <b>RG:</b> $tecnico_rg<br>
                                <b>CPF:</b> $tecnico_cpf<br>
                                <b>Fone:</b> $tecnico_fone<br>
                                <b>Celular:</b> $tecnico_celular<br>
                                <b>Tipo Sangu&iacute;neo:</b> $tecnico_tipo_sanguineo<br>
                                <b>N&ord; do Calçado:</b> $tecnico_calcado<br>
                                <b>O Participante sofreu ou sofre de alguma doença? - </b> $tecnico_doencas<br>
                                <b>Toma algum medicamento controlado? Qual? - </b> $tecnico_medicamento<br>
                                <b>&Eacute; portador de alguma necessidade especial? Qual? - </b> ".htmlentities($tecnico_necessidade_especial)."<br>
                                <a href='treinamento_cadastro.php?treinamento_posto=$treinamento_posto'>[Alterar dados]</a>
                            </TD>";
            }else{
                $resposta  .=  "<TD align='left'nowrap>
                                    <b>Nome: </b>".htmlentities($tecnico_nome)." <br>
                                    <b>RG:</b> $tecnico_rg<br>
                                    <b>CPF:</b> $tecnico_cpf<br>
                                    <b>Fone:</b> $tecnico_fone<br>
                                    <a href='treinamento_cadastro.php?treinamento_posto=$treinamento_posto'>[Alterar dados]</a>
                                </TD>";
            }
            if ($adicional) $resposta .= "<TD>$observacao</TD>";
            if($login_fabrica == 20){
                $resposta  .=  "<TD align='left'>";
                if(strlen($nome)>0) $resposta  .=  "$nome";
                else                $resposta  .=  "$promotor";
            }

            $resposta  .=  "</TD>";
            $resposta  .=  "<TD align='center'>$data_inscricao <br> $hora_inscricao</TD>";
            $resposta  .=  "<TD align='center'>$ativo</TD>";
            $resposta  .=  "<TD align='center' width='60' title='Inscri&ccedil;&atilde;o?'><div id='tec_ativo_$i'><a href='javascript:if (confirm(\"Deseja cancelar esta inscrição?\") == true) {ativa_desativa_tecnico(\"$treinamento_posto\",\"$i\")}'>$x_ativo</a></div></TD>";

            $resposta  .=  "<TD align='center'>$confirma</TD>";
            $resposta  .=  "<TD align='center' width='60'title='Confirmado inscri&ccedil;&atilde;o por email?'><div id='confirma_$i'>$x_confirma</div></TD>";

            if ($login_fabrica == 20){
                $resposta  .=  "<TD align='center'>$hotel</TD>";
                $resposta  .=  "<TD align='center' width='60' title='Agendar Hotel?'><div id='hotel_$i'>$x_hotel</div></TD>";
            }else{
                if($login_fabrica != 117){
                    $resposta  .=  "<TD align='center'>$hotel</TD>";
                    $resposta  .=  "<TD align='center' width='60' title='Agendar Hotel?'><div id='hotel_$i'><a href=\"javascript:ativa_desativa_hotel('$treinamento_posto','$i')\">$x_hotel</a></div></TD>";
                }

            }

            $resposta  .=  "<TD align='center'>$participou</TD>";
            $resposta  .=  "<TD align='center' width='60' title='Esteve presente no treinamento?'><div id='participou_$i'><a href='javascript:ativa_desativa_participou(\"$treinamento_posto\",\"$i\")'>$x_participou</a></div></TD>";
            $resposta  .= "<td>$motivo</td>";
            $resposta  .=  "</TR>";

        }
        $resposta .= " </TABLE>";

    }else{

        if($qtde_postos == 0)   {
            $resposta .= "<b> Nenhum posto fez a inscri&ccedil;&atilde;o de seu t&eacute;cnico para participar do treinamento</b>";
        }


    }
    echo "ok|".$resposta."<p>";
    exit;
}



//--====== RELATORIO DO TREINAMENTO ================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='relatorio') {

    if(array_key_exists('excel', $_GET)){
        $excel = true;

        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_treinamentos-{$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");
        //head
        if ($login_fabrica == 117) {
            fwrite($file, utf8_encode("Estado;Cidade;Local;Título;Data Inicio;Data Fim;Técnico;Informações Adicionais;Status;\n"));
        }elseif($login_fabrica == 138){
            fwrite($file, utf8_encode("Posto;Estado;Título;Data Inicio;Data Fim;Tipo Posto;Técnico;RG;Informações Adicionais;Local;\n"));
        }elseif($login_fabrica == 1){
            fwrite($file, ("Posto;Estado;Título;Data Inicio;Data Fim;Tipo Posto;Técnico;Informacões Adicionais;Local;\n"));
        }else{
            fwrite($file, utf8_encode("Posto;Estado;Título;Data Inicio;Data Fim;Tipo Posto;Técnico;Informações Adicionais;Local;\n"));
        }
        $listar = "ok";
    }else{
        $msg_erro = array(
            "msg",
            "campos" => array()
        );

        /* VALIDANDO SE A DATA DIGITADA É VALIDA */
        $data_inicial = trim($_GET['data_inicial']);
        $data_final   = trim($_GET['data_final']);

        if (strlen($data_inicial) == 0 && strlen($data_final) == 0){
            $msg_erro['msg'] = utf8_encode('Data Inválida!');
            $msg_erro['campos'][] = utf8_encode('data_inicial');
            $msg_erro['campos'][] = utf8_encode('data_final');
            exit(json_encode(array("erro" => $msg_erro)));
        }
        if (strlen($data_inicial) == 0 || $data_inicial == 'dd/mm/aaaa'){
            $msg_erro['msg'] = utf8_encode('Data inicial é um campo obrigatório, digite uma data válida!');
            $msg_erro['campos'][] = utf8_encode('data_inicial');
            exit(json_encode(array("erro" => $msg_erro)));
        }
        if (strlen($data_final) == 0 || $data_final   == 'dd/mm/aaaa'){
            $msg_erro['msg'] = utf8_encode('Data final é um campo obrigatório, digite uma data válida!');
            $msg_erro['campos'][] = utf8_encode('data_final');
            exit(json_encode(array("erro" => $msg_erro)));
        }

        $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
        if (strlen(pg_last_error($con)) > 0){
            $msg_erro['msg'] = utf8_encode('Data Inválida, digite uma data válida!');
            $msg_erro['campos'][] = utf8_encode('data_inicial');
            exit(json_encode(array("erro" => $msg_erro)));
        }
        $data_inicial = @pg_fetch_result ($fnc,0,0);

        $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
        if (strlen ( pg_last_error($con) ) > 0){
            $msg_erro['msg'] = utf8_encode('Data Inválida, digite uma data válida!');
            $msg_erro['campos'][] = utf8_encode('data_final');
            exit(json_encode(array("erro" => $msg_erro)));
        }
        $data_final = @pg_fetch_result ($fnc,0,0);

        if( $data_inicial > $data_final ){
            $msg_erro['msg'] = utf8_encode('Data inicial não pode ser maior que a data final!');
            $msg_erro['campos'][] = utf8_encode('data_inicial');
            exit(json_encode(array("erro" => $msg_erro)));
        }
        $listar = "ok";
        $excel = false;
    }

    /* INICIA RELATÓRIO */
    $estado       = trim($_GET['listaEstado']);
    if (in_array($login_fabrica, array(169,170,193)))
    {
        $aux_tipo_posto = implode(",",$tipo_posto);
    }
    $tipo_posto   = trim($_GET['tipo_posto']);
    $categoria_posto   = trim($_GET['categoria_posto']);
    $titulo       = trim($_GET['titulo']);
    if (mb_check_encoding($titulo, 'UTF-8')) {
        $titulo = utf8_decode($titulo);
    }
    $escritorio   = trim($_GET['escritorio']);

    if ($login_fabrica == 117) {
        $cidade = trim($_GET['cidade']);
        $regiao = trim($_GET['listaRegiao']);
        //$estadoPesquisa = trim($_GET['estados']);
        $estadoPesquisa = trim($_GET['listaEstado']);
        $status         = trim($_GET["listaStatus"]);
        $linha          = trim($_GET["listaLinha"]);

        if($cidade != ""){
            $sql2 = "SELECT cidade FROM tbl_cidade WHERE nome ilike '$cidade'";
            $res = pg_query($con,$sql2);
            $result = pg_fetch_all($res);
            foreach ($result as $value) {
                $ibge .= ",".$value['cidade'];
            }

            $cond5 = " AND tbl_cidade.cidade in(".substr($ibge, 1).")";
        }elseif ($estadoPesquisa != "") {
            $cond5 = " AND tbl_cidade.estado = '$estadoPesquisa'";
        }elseif ($regiao != "") {
            $regiao = explode(',', $regiao);
            foreach ($regiao as $key => $value) {
                $regiao[$key] = "'".trim($value)."'";
            }
            $regiao = implode(',',$regiao);
            $cond5 = " AND tbl_cidade.estado IN($regiao)";
        }

        if ($status != "") {
            // a confirmar
            if ($status == 1) {
                $cond5 .= " AND tbl_treinamento.data_fim >= CURRENT_DATE
                            AND tbl_treinamento.ativo = 't'
                            AND CASE WHEN ( SELECT count(*) FROM tbl_treinamento_posto WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento ) = 0
                                     THEN true
                                     ELSE false
                                END ";
            // confirmado
            }elseif ($status == 2) {
                $cond5 .= " AND tbl_treinamento.data_fim > CURRENT_DATE
                            AND tbl_treinamento.ativo = 't'
                            AND CASE WHEN ( SELECT count(*) FROM tbl_treinamento_posto WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento ) >= 1
                                     THEN true
                                     ELSE false
                                END";
            // realizado
            }elseif ($status == 3) {
                $cond5 .= " AND tbl_treinamento.data_fim < CURRENT_DATE
                            AND tbl_treinamento.ativo = 't'
                            AND CASE WHEN ( SELECT count(*) FROM tbl_treinamento_posto WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento ) >= 1
                                     THEN true
                                     ELSE false
                                END ";
            // cancelado
            }elseif ($status == 4) {
                $cond5 .= " AND tbl_treinamento.data_fim < CURRENT_DATE
                            AND CASE WHEN ( SELECT count(*) FROM tbl_treinamento_posto WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento ) = 0
                                     THEN true
                                     ELSE false
                                END ";
            }

        }
        if ($linha != "") {
            $cond5  .= " AND tbl_treinamento.linha = $linha";
        }

    }else{
        $cond5 = "";
        $estado       = trim($_GET['listaEstado']);
    }

    if (strlen($titulo)==0){
        $cond3 = '';
    }else{
        $titulo = utf8_decode($titulo);
        $cond3 = " AND tbl_treinamento.titulo ILIKE '%$titulo%' ";
    }

    if ($listar == "ok") {
        if ($login_fabrica == 117) {
            $sql = "SELECT DISTINCT(tbl_treinamento.treinamento) ,
                        tbl_cidade.estado                                                     ,
                        tbl_treinamento.palestrante as tecnico_nome                           ,
                        tbl_treinamento.titulo                                                ,
                        tbl_treinamento.descricao                                             ,
                        tbl_treinamento.ativo                                                 ,
                        tbl_treinamento.local                                                 ,
                        tbl_treinamento.cidade                                                ,
                        TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio      ,
                        tbl_treinamento.data_inicio AS data_inicio_ordenar      ,
                        TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim         ,
                        tbl_admin.nome_completo                                               ,
                        (
                            SELECT count(*)
                            FROM tbl_treinamento_posto
                            WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                        )                                                 AS qtde_postos      ,
                        tbl_treinamento.adicional
                     FROM tbl_treinamento
                     LEFT JOIN      tbl_treinamento_posto USING(treinamento)
                     JOIN      tbl_admin         ON tbl_treinamento.admin = tbl_admin.admin
                     LEFT JOIN tbl_cidade ON tbl_treinamento.cidade = tbl_cidade.cidade
                     WHERE tbl_treinamento.fabrica = $login_fabrica
                     AND   tbl_treinamento.data_inicio BETWEEN '$data_inicial' AND '$data_final'
                     $cond5
                     $cond3
                     ORDER BY  data_inicio_ordenar

                    " ;
        }else{
            // tirando os '1=1' da vida...
            if (strlen($estado)==0)     $cond1 = ''; else $cond1 = " AND tbl_posto.estado             = '$estado' ";
            if (in_array($login_fabrica, array(169,170,193)))
            {
                if (strlen($tipo_posto)==0) $cond2 = ''; else $cond2 = " AND tbl_posto_fabrica.tipo_posto IN ({$aux_tipo_posto}) ";
            }else
            {
                if (strlen($tipo_posto)==0) $cond2 = ''; else $cond2 = " AND tbl_posto_fabrica.tipo_posto = $tipo_posto ";
            }

            if (strlen($escritorio)==0) $cond4 = ''; else $cond4 = " AND tbl_escritorio_regional.escritorio_regional ='$escritorio' ";
            if (strlen($categoria_posto)==0) $cond6 = ''; else $cond6 = " AND lower(tbl_posto_fabrica.categoria) = ('$categoria_posto') ";

            if(in_array($login_fabrica, [1, 138])){ //HD-2930346 
                $cond_treinamento_posto_ativo = " AND tbl_treinamento_posto.ativo IS TRUE ";
            }

            $sql = "SELECT tbl_posto.nome,
                           tbl_posto.estado,
                           tbl_posto_fabrica.codigo_posto,
                           tbl_posto_fabrica.tipo_posto,
                           tbl_posto_fabrica.categoria,
                           tbl_tipo_posto.descricao AS descricao_er,
                           tbl_treinamento.treinamento,
                           tbl_treinamento.titulo,
                           tbl_treinamento.descricao,
                           tbl_treinamento.ativo,
                           tbl_treinamento.local,
                           tbl_treinamento.cidade,
                           tbl_treinamento_posto.ativo AS tecnico_ativo,
                           tbl_treinamento.vagas_min,
                           TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
                           TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
                           TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
                           tbl_treinamento_posto.promotor_treinamento,
                           tbl_admin.nome_completo,
                           CASE WHEN tbl_tecnico.nome NOTNULL
                                THEN tbl_tecnico.nome
                                ELSE tbl_treinamento_posto.tecnico_nome
                           END             AS tecnico_nome,
                           tbl_tecnico.cpf AS tecnico_cpf,
                           tbl_tecnico.rg  AS tecnico_rg,
                           tbl_escritorio_regional.descricao AS descricao_escritorio,
                           (SELECT count(*)
                              FROM tbl_treinamento_posto
                             WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                           )               AS qtde_postos,
                           tbl_treinamento.adicional,
                           tbl_treinamento_posto.observacao
                      FROM tbl_treinamento
                 LEFT JOIN tbl_treinamento_posto    USING (treinamento)
                 LEFT JOIN tbl_posto                USING (posto)
                 LEFT JOIN tbl_posto_fabrica        ON    tbl_posto.posto = tbl_posto_fabrica.posto
                                                      AND tbl_posto_fabrica.fabrica = $login_fabrica
                 LEFT JOIN tbl_tipo_posto           ON    tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                                                      AND tbl_posto_fabrica.fabrica = $login_fabrica
                 JOIN tbl_admin                     ON    tbl_treinamento.admin = tbl_admin.admin
                 LEFT JOIN tbl_promotor_treinamento ON    tbl_treinamento_posto.promotor_treinamento =tbl_promotor_treinamento.promotor_treinamento
                                                      AND tbl_promotor_treinamento.fabrica=$login_fabrica
                 LEFT JOIN tbl_escritorio_regional  ON    tbl_promotor_treinamento.escritorio_regional = tbl_escritorio_regional.escritorio_regional
                 LEFT JOIN tbl_tecnico              ON    tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
                 LEFT JOIN tbl_cidade               ON    tbl_treinamento.cidade = tbl_cidade.cidade
                     WHERE tbl_treinamento.fabrica = $login_fabrica
                       AND tbl_treinamento.data_inicio BETWEEN '$data_inicial' AND '$data_final'
                       $cond1
                       $cond2
                       $cond3
                       $cond4
                       $cond5
                       $cond6
                       $cond_treinamento_posto_ativo
                    " ;
        }

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $resposta  .=  "<table id='tbtreinamento' class='table table-striped table-bordered table-hover table-fixed'>";
            $resposta  .= "<thead>";

            $resposta .= "<tr class='titulo_coluna'>";
            if ($login_fabrica == 117) {
                $resposta  .=  "<th>Estado</th>";
                $resposta  .=  "<th>Cidade</th>";
                $resposta  .=  "<th>Local</th>";
                $resposta  .=  "<th>Título</th>";
                $resposta  .=  "<th>Data Início</th>";
                $resposta  .=  "<th>Data Fim</th>";
                $resposta  .=  "<th>Palestrante</th>";
                $resposta  .=  "<th>Informações Adicionais</th>";
                $resposta  .=  "<th>Status</th>";
            }else{
                $resposta  .=  "<th>Posto</th>";
                if ($login_fabrica==20){
                    $resposta  .=  "<th>Escritório</th>";
                }else{
                    $resposta  .=  "<th>Estado</th>";
                }
                $resposta  .=  "<th>Título</th>";
                $resposta  .=  "<th width='10%'>Data Início</th>";
                $resposta  .=  "<th width='10%'>Data Fim</th>";

                if ($treinamento_prazo_inscricao) {
                    $resposta .= '<th>Prazo Inscrição</th>';
                }
                if ($treinamento_vagas_min) {
                    $resposta .= '<th align="center">Mín. Vagas</th>';
                }
                $resposta  .=  "<th width='12%'>Tipo Posto</th>";
                $resposta  .=  "<th>Técnico</th>";
                $resposta  .=  "<th width=''>Informações Adicionais</th>";
                $resposta  .=  "<th>Local</th>";
            }
            $resposta  .=  "</tr>";
            $resposta  .=  "</thead>";
            $resposta .=  "<tbody>";

            for ($i=0; $i<pg_num_rows($res); $i++){

                $codigo_posto         = trim(pg_fetch_result($res,$i,'codigo_posto'));
                $nome                 = trim(pg_fetch_result($res,$i,'nome'));
                $estado               = trim(pg_fetch_result($res,$i,'estado'));
                $treinamento          = trim(pg_fetch_result($res,$i,'treinamento'));
                $titulo               = trim(pg_fetch_result($res,$i,'titulo'));
                if (mb_check_encoding($titulo)) {
                    $titulo = utf8_decode($titulo);
                }
                $descricao            = trim(pg_fetch_result($res,$i,'descricao'));
                $promotor_treinamento = trim(pg_fetch_result($res,$i,'promotor_treinamento'));
                $ativo                = trim(pg_fetch_result($res,$i,'ativo'));
                $data_inicio          = trim(pg_fetch_result($res,$i,'data_inicio'));
                $data_fim             = trim(pg_fetch_result($res,$i,'data_fim'));
                $prazo_inscricao      = trim(pg_fetch_result($res,$i,'prazo_inscricao'));
                $vagas_min            = trim(pg_fetch_result($res,$i,'vagas_min'));
                $nome_completo        = trim(pg_fetch_result($res,$i,'nome_completo'));
                if (in_array($login_fabrica, array(169,170,193))){
                    $parametros_adicionais = json_decode(trim(pg_fetch_result($res,$i,'parametros_adicionais')));
                    $tipo_posto            = $parametros_adicionais->tipo_posto;
                }else{
                    $tipo_posto           = trim(pg_fetch_result($res,$i,'tipo_posto'));
                }

                $categoria_posto      = trim(pg_fetch_result($res,$i,'categoria'));
                $descricao_er         = trim(pg_fetch_result($res,$i,'descricao_er'));
                $tecnico_nome         = trim(pg_fetch_result($res,$i,'tecnico_nome'));
                if (mb_check_encoding($tecnico_nome, 'UTF-8')) {
                    $tecnico_nome = utf8_decode($tecnico_nome);
                }
                $tecnico_cpf          = trim(pg_fetch_result($res,$i,'tecnico_cpf'));
                $tecnico_rg           = trim(pg_fetch_result($res,$i,'tecnico_rg'));
                $descricao_escritorio = trim(pg_fetch_result($res,$i,'descricao_escritorio'));
                $observacao           = trim(pg_fetch_result($res,$i,'observacao'));
                $adicional            = trim(pg_fetch_result($res,$i,'adicional'));
                $local                = trim(pg_fetch_result($res,$i,'local'));
                if (mb_check_encoding($local, 'UTF-8')) {
                    $local = utf8_decode($local);
                }
                $tecnico_ativo        = trim(pg_fetch_result($res,$i,'tecnico_ativo'));

                $localizacao = '';

                if (strlen(pg_fetch_result($res,$i,'cidade')) > 0) {
                    $cidade = pg_fetch_result($res,$i,'cidade');
                    $sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";
                    $resCidade = pg_query($con,$sql);
                    if(pg_num_rows($res) > 0){
                        $cidade        = pg_fetch_result($resCidade,0,'cidade');
                        $nome_cidade   = pg_fetch_result($resCidade,0,'nome');
                        $estado_cidade = pg_fetch_result($resCidade,0,'estado');
                        $localizacao   = " $nome_cidade - $estado_cidade";
                    }else{
                        $localizacao = "";
                    }
                }else{
                    $localizacao = "";
                }

                if ($login_fabrica == 117) {
                    $qtde_postos                = trim(pg_result($res,$i,'qtde_postos'));

                    $obj_data_fim = DateTime::createFromFormat('d/m/Y', $data_fim);
                    $obj_data = DateTime::createFromFormat('d/m/Y', date('d/m/Y'));

                    if ($ativo == 'f') {
                        $status_t = "Cancelado";
                    }else{
                        if ($qtde_postos == 0 AND ($obj_data <= $obj_data_fim)) {
                            $status_t = "A Confirmar";
                        }elseif ($qtde_postos >= 1 AND ($obj_data <= $obj_data_fim)) {
                            $status_t = "Confirmado";
                        }elseif ($qtde_postos >= 1 AND ($obj_data > $obj_data_fim)) {
                            $status_t = "Realizado";
                        }elseif($qtde_postos == 0 AND ($obj_data > $obj_data_fim)){
                            $status_t = "Cancelado";
                        }
                    }
                }

                if($excel){

                    $codigo_posto = utf8_encode($codigo_posto);
                    $nome = utf8_encode($nome);
                    if ($login_fabrica == 1) {
                        if (mb_check_encoding($titulo, 'UTF-8')) {
                            $titulo = utf8_decode($titulo);
                        }
                    } else {
                        $titulo = utf8_encode($titulo);
                    }
                    $data_inicio = utf8_encode($data_inicio);
                    $data_fim = utf8_encode($data_fim);
                    $descricao_er = utf8_encode($descricao_er);
                    $tecnico_nome = $tecnico_nome;
                    $adicional = $adicional;
                    if ($login_fabrica == 1) {
                        if (mb_check_encoding($observacao, 'UTF-8')) {
                            $observacao = utf8_decode($observacao);        
                        }
                    } else{ 
                        $observacao = utf8_encode($observacao);
                    }
                    $local  = $local;
                    $localizacao  = $localizacao;
                    $status_t = utf8_encode($status_t);
                    $tecnico_rg = utf8_encode($tecnico_rg);
                    if ($login_fabrica == 117) {
                        $dados = "$estado;$nome_cidade;$local;$titulo;$data_inicio;$data_fim;$tecnico_nome;$adicional;$status_t;\n";
                    }elseif($login_fabrica == 138){ //HD-2930346
                        if($tecnico_ativo == 't'){
                            $tecnico_nome = $tecnico_nome;
                            $tecnico_rg = $tecnico_rg;
                        }else{
                            $tecnico_nome = "";
                            $tecnico_rg = "";
                        }
                        $dados = "$codigo_posto - $nome;$estado;$titulo;$data_inicio;$data_fim;$descricao_er;$tecnico_nome;$tecnico_rg;$adicional - $observacao;$local $localizacao;\n";
                    }else{
                        $dados = "$codigo_posto - $nome;$estado;$titulo;$data_inicio;$data_fim;$descricao_er;$tecnico_nome;$adicional - $observacao;$local $localizacao;\n";
                    }

                    fwrite($file,$dados);
                }else{
                    if($cor=="#F1F4FA")$cor = '#F7F5F0';
                    else               $cor = '#F1F4FA';

                    $resposta .= "<tr>";

                    if ($login_fabrica == 117) {

                        $resposta  .=  "<td>$estado</td>";
                        $resposta  .=  "<td>$nome_cidade</td>";
                        $resposta  .=  "<td>$local</td>";
                        $resposta  .=  "<td>$titulo</td>";
                        $resposta  .=  "<td>$data_inicio</td>";
                        $resposta  .=  "<td>$data_fim</td>";
                        $resposta  .=  "<td>$tecnico_nome</td>";
                        if ($adicional) $resposta  .=  "<td>$adicional</td>";
                        else $resposta .= "<td></td>";
                        $resposta  .=   "<td>$status_t</td>";

                    }else{
                        $resposta  .=  "<td>$codigo_posto - $nome</td>";
                        if ($login_fabrica==20){
                            $resposta  .=  "<td>$descricao_escritorio</td>";
                        }else{
                            $resposta  .=  "<td>$estado</td>";
                        }
                        $resposta  .=  "<td>$titulo</td>";
                        $resposta  .=  "<td>$data_inicio</td>";
                        $resposta  .=  "<td>$data_fim</td>";
                        if ($treinamento_prazo_inscricao) {
                            $resposta .= "<td>$prazo_inscricao</td>";
                        }
                        if ($treinamento_vagas_min) {
                            $resposta .= "<td align='right'>$vagas_min</td>";
                        }

                        $resposta  .=  "<TD align='left'>$descricao_er</TD>";

                        if($login_fabrica == 138){ //HD-2930346
                            if($tecnico_ativo == 't'){
                                $tecnico_nome = $tecnico_nome;
                            }else{
                                $tecnico_nome = "";
                            }
                        }

                        $resposta  .=  "<TD align='left'>$tecnico_nome</TD>";
                        if ($adicional) $resposta  .=  "<TD align='left'><b>$adicional:</b>$observacao</TD>";
                        else $resposta .= "<TD></TD>";
                        $resposta  .=  "<TD align='left'>$local $localizacao</TD>";
                    }
                    $resposta  .=  "</TR>";
                }

            }
            $resposta  .=  "</tbody>";
            if($excel){
                fclose($file);
                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");
                    exit(json_encode(array("ok" => utf8_encode("xls/{$fileName}"))));
                }
            }else{
                $resposta .= " </TABLE>";
            }
        }else{
            exit(json_encode(array("nenhum" => utf8_encode("Nenhum resultado econtrado neste período"))));
        }
        exit(json_encode(array("ok" => utf8_encode($resposta))));
    }
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='relatorio_pesquisa_treinamento') {
    /* VALIDANDO SE A DATA DIGITADA É VALIDA */
    $data_inicial = trim($_GET['data_inicial']);
    $data_final   = trim($_GET['data_final']);
    
    if ( (strlen($data_inicial) == 0 && strlen($data_final) == 0) && strlen($_GET['treinamento']) == 0){
        $msg_erro['msg'] = utf8_encode('Data Inválida!');
        $msg_erro['campos'][] = utf8_encode('data_inicial');
        $msg_erro['campos'][] = utf8_encode('data_final');
        exit(json_encode(array("erro" => $msg_erro)));
    }

    if ( (strlen($data_inicial) == 0 || $data_inicial == 'dd/mm/aaaa') && strlen($_GET['treinamento']) == 0){
        $msg_erro['msg'] = utf8_encode('Data inicial é um campo obrigatório, digite uma data válida!');
        $msg_erro['campos'][] = utf8_encode('data_inicial');
        exit(json_encode(array("erro" => $msg_erro)));
    }
    if ( (strlen($data_final) == 0 || $data_final   == 'dd/mm/aaaa') && strlen($_GET['treinamento']) == 0){
        $msg_erro['msg'] = utf8_encode('Data final é um campo obrigatório, digite uma data válida!');
        $msg_erro['campos'][] = utf8_encode('data_final');
        exit(json_encode(array("erro" => $msg_erro)));
    }
    

    $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
    if (strlen(pg_last_error($con)) > 0){
        $msg_erro['msg'] = utf8_encode('Data Inválida, digite uma data válida!');
        $msg_erro['campos'][] = utf8_encode('data_inicial');
        exit(json_encode(array("erro" => $msg_erro)));
    }
    $data_inicial = @pg_fetch_result ($fnc,0,0);

    $fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
    if (strlen ( pg_last_error($con) ) > 0){
        $msg_erro['msg'] = utf8_encode('Data Inválida, digite uma data válida!');
        $msg_erro['campos'][] = utf8_encode('data_final');
        exit(json_encode(array("erro" => $msg_erro)));
    }
    $data_final = @pg_fetch_result ($fnc,0,0);

    if( $data_inicial > $data_final ){
        $msg_erro['msg'] = utf8_encode('Data inicial não pode ser maior que a data final!');
        $msg_erro['campos'][] = utf8_encode('data_inicial');
        exit(json_encode(array("erro" => $msg_erro)));
    }
    $listar = "ok";

    if ($listar == "ok") {
        $estado      = trim($_GET['listaEstado']);
        $tipo_posto  = trim($_GET['tipo_posto']);
        $treinamento = trim($_GET['treinamento']);

        if (strlen($treinamento) > 0) {
            $whereTreinamento = " AND p.treinamento = {$treinamento}";
        } else {
            $whereData = " AND r.data_input BETWEEN '{$data_inicial} 00:00' AND '{$data_final} 23:59'";
        }

        if (strlen($tipo_posto) > 0) {
            if (!is_array($tipo_posto)) {
                $arr_tipo_posto[] = $tipo_posto;
                $aux_tipo_posto   = implode(",", $arr_tipo_posto);    
            } else {
                $aux_tipo_posto   = implode(",", $tipo_posto);    
            }
            $wherePosto     = " AND pf.tipo_posto IN ({$aux_tipo_posto})";
        }

        if (strlen($estado) > 0) {
            $whereEstado = " AND pst.estado = '$estado'";
        }
 
        $sql_busca   = "SELECT DISTINCT
                             p.treinamento,
                             tr.titulo,
                             pst.estado,
                             p.descricao,
                             p.texto_ajuda,
                             r.txt_resposta,
                             pst.nome,
                             pst.posto,
                             r.tecnico
                    FROM tbl_pesquisa p
                        JOIN tbl_resposta r USING(pesquisa)
                        JOIN tbl_tecnico  t USING(tecnico)
                        JOIN tbl_posto_fabrica pf ON pf.posto       = r.posto AND pf.fabrica = {$login_fabrica}
                        JOIN tbl_posto pst        ON pst.posto      = pf.posto
                        JOIN tbl_treinamento tr   ON tr.treinamento = p.treinamento
                    WHERE p.fabrica = {$login_fabrica}
                        {$whereData}
                        {$whereTreinamento}
                        {$wherePosto}
                        {$whereEstado}; ";
        $res_busca   = pg_query($con, $sql_busca);

        $array_perguntas = array();

        /******* MONTA TABLE *******/
        if (pg_num_rows($res_busca) > 0) {
            $array_perguntas = json_decode(pg_fetch_result($res_busca, 0, 'texto_ajuda'), true);
            $perguntas       = array();
           
            foreach ($array_perguntas AS $x_pergunta) {
                if (utf8_decode($x_pergunta['main_title']) == "Comentários") {
                    continue;
                }

                if (!array_key_exists($x_pergunta['main_title'], $perguntas)) {
                    $perguntas[$x_pergunta['main_title']] = array();
                }

                foreach ($x_pergunta['itens'] AS $x_pergunta_item) {
                    $total_perguntas_item = count($x_pergunta['itens']);
                    $perguntas[$x_pergunta['main_title']][$x_pergunta_item] = array(
                        1 => 0,
                        2 => 0,
                        3 => 0,
                        4 => 0,
                        5 => 0
                    );
                }
            }

            $tfoot .= "<tfoot>
                    <tr class='titulo_coluna'>
                        <th colspan='2'>Pesquisas Respondidas</th>
                    </tr>
                    <tr>
                        <td colspan='2' style='padding: 0px;'>
                            <table class='table table-bordered' style='margin-bottom: 0px; width: 100%;'>
                                <thead>
                                    <tr class='titulo_coluna'>
                                        <th>Título do Treinamento</th>
                                        <th>Posto</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody class='tbody-lista'>";
                                    $array_posto_treinamento = array();
                                    for ($i_busca_pst=0; $i_busca_pst < pg_num_rows($res_busca); $i_busca_pst++) {
                                        $txt_respostas       = json_decode(pg_fetch_result($res_busca, $i_busca_pst, 'txt_resposta'), true);;
                                        $treinamento         = pg_fetch_result($res_busca, $i_busca_pst, 'treinamento');
                                        $titulo              = pg_fetch_result($res_busca, $i_busca_pst, 'titulo');
                                        $posto_id            = pg_fetch_result($res_busca, $i_busca_pst, 'posto');
                                        $posto_nome          = pg_fetch_result($res_busca, $i_busca_pst, 'nome');
                                        $descricao           = pg_fetch_result($res_busca, $i_busca_pst, 'descricao');
                                        $tecnico             = pg_fetch_result($res_busca, $i_busca_pst, 'tecnico');

                                        foreach ($txt_respostas AS $x_resposta) {
                                            if (utf8_decode($x_resposta['main_title']) == "Comentários") {
                                                continue;
                                            }
                                            foreach ($x_resposta['itens'] AS $x_resposta_item) {
                                                $perguntas[$x_resposta['main_title']][$x_resposta_item['ask']][$x_resposta_item['val']] += 1;
                                            }
                                        }

                                        $tfoot .= " <tr>
                                                        <td class='tac'>".$titulo."</td>
                                                        <td class='tac'>".$posto_nome."</td>
                                                        <td class='tac'>
                                                            <button type='button' class='btn btn-info btn-small btn-resposta' data-posto='".$posto_id."' data-treinamento='".$treinamento."' data-tecnico='".$tecnico."' ><i class='fa fa-question-circle'></i> Ver resposta</button>
                                                        </td>
                                                    </tr>";
                                        $array_posto_treinamento[] = array("posto" => $posto_nome, "treinamento" => $titulo);
                                    }   
            $tfoot .=           "</tbody>
                            </table>
                        </td>
                    </tr>
                </tfoot>";

            $count               = count($perguntas);
            $count_array_foreach = 0;

            pg_result_seek($res_busca, 0);

            /***** CSV ****/
            $csv              = 'relatorio-pesquisa-satisfacao-treinamento-'.date('dmYHis').'.csv';
            $arquivo_download = fopen("/tmp/{$csv}", 'w');
            fwrite($arquivo_download, "'posto';'treinamento';'tecnico';'pesquisa';'pergunta';'resposta';\n");

            while ($row = pg_fetch_object($res_busca)) {
                $titulo_tr  = $row->titulo;
                $posto_nome = $row->nome;
                $tecnico    = $row->tecnico;

                $resposta   = json_decode($row->txt_resposta, true);
                
                foreach ($perguntas AS $titulo => $x_pergunta_item) {
                    foreach ($x_pergunta_item AS $titulo_pergunta => $valor) {
                        $param = array($titulo, $titulo_pergunta); 
                        $arr_nota  = array_filter($resposta, function($valor) use ($param) {
                            list($xtitulo, $xtitulo_pergunta) = $param;

                            if ($valor['main_title'] == $xtitulo) {
                                foreach ($valor['itens'] AS $itens) {
                                    if ($itens['ask'] == $xtitulo_pergunta) {
                                        return true;
                                    } else {
                                        continue; 
                                    }
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        });

                        $nota = array_filter($arr_nota[key($arr_nota)]['itens'], function($valor) use ($titulo_pergunta) {
                            if ($valor['ask'] == $titulo_pergunta) {
                                return true;
                            } else {
                                return false;
                            }
                        });

                        $nota = $nota[key($nota)]['val'];

                        fwrite($arquivo_download, "'".$posto_nome.";'".$titulo_tr."'; '".$tecnico."';'".$titulo."';'".utf8_decode($titulo_pergunta)."';'".$nota."';\n");    
                    }
                }
            }

            foreach ($perguntas AS $titulo => $x_pergunta_item) {
                $count_array_foreach++;
                $print .= "<table id='respostas-pesquisa-<?=$id?>' class='table table-striped table-bordered table-large table-center table-respostas' >
                        <thead>
                            <tr>
                                <th colspan='2'>".utf8_decode($titulo)."</th>
                            </tr>
                            <tr class='titulo_coluna' >
                                <th colspan='2' >Respostas</th>
                            </tr>
                        </thead>
                        <tbody>";
                            foreach ($x_pergunta_item AS $titulo_pergunta => $array_nota) {
                                $print .= "<tr class='titulo_coluna'>
                                            <th style='vertical-align: middle; width: 300px;'>".utf8_decode($titulo_pergunta)."</th>
                                            <td style='padding: 0px;'>
                                                <table class='table table-bordered' style='margin-bottom: 0px; table-layout: fixed;'>
                                                    <thead>
                                                        <tr class='titulo_coluna'>
                                                            <th><i class='fa fa-star'></i></th>
                                                            <th>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                            </th>
                                                            <th>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                            </th>
                                                            <th>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                            </th>
                                                            <th>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                                <i class='fa fa-star'></i>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>";
                                                        $total = array_sum($array_nota);
                                                        foreach ($array_nota AS $nota) {
                                                            $porcento = ($nota / $total) * 100; 
                                                            $print   .= "<td class='tac' nowrap>".number_format($porcento, 2, '.', '')."% </td>";
                                                        }
                                $print .= "         </tbody>
                                                </table>
                                            </td>
                                        </tr>";
                            }
                $print     .= "</tbody>";
                if ($count_array_foreach == $count) {
                    $print .= $tfoot;
                }
                $print     .= "</table> <br />";
            }

            $print .= "<center> <button type='button' class='btn btn-success btn-download-csv' data-arquivo='$csv'><i class='fa fa-file-excel'></i> Download arquivo CSV</button> </center>";
            fclose($arquivo_download);
            system("mv /tmp/{$csv} xls/{$csv}");

           exit(json_encode(array("ok" => utf8_encode($print))));
        } else {
            exit(json_encode(array("erro" => array("msg" => utf8_encode("Nenhum registro encontrado!")))));
        }
    }
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='ativa_desativa') {
    $treinamento = $_GET["treinamento"];
    $id          = $_GET["id"];

    $sql = "SELECT ativo FROM tbl_treinamento WHERE treinamento = $treinamento";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $ativo       = trim(pg_fetch_result($res,0,ativo))       ;
        if($ativo == 't'){
            $x_ativo = 'f';
            $resposta = "Cancelado";

        }
        else{
            $x_ativo = 't';
            $resposta = "Confirmado";
        }
        $sql = "UPDATE tbl_treinamento SET ativo = '$x_ativo' WHERE treinamento = $treinamento";
        $res = pg_query($con,$sql);
        exit(json_encode(array("ok" => utf8_encode($resposta))));
    }else{
        exit(json_encode(array("erro" => utf8_encode("Ocorreu um erro ao tentar cancelar/confirmar um treinamento!"))));
    }
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='ativa_desativa_tecnico') {
    $treinamento_posto = $_GET["treinamento_posto"];
    $id                = $_GET["id"];

    $sql = "SELECT  tbl_treinamento_posto.ativo       ,
                    tbl_tecnico.nome AS tecnico_nome,
                    tbl_posto.email
            FROM    tbl_treinamento_posto
            JOIN    tbl_tecnico USING (tecnico)
            JOIN    tbl_posto ON tbl_posto.posto = tbl_treinamento_posto.posto
        WHERE treinamento_posto = $treinamento_posto";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $ativo        = trim(pg_fetch_result($res,0,ativo))       ;
        $email        = trim(pg_fetch_result($res,0,email))       ;
        $tecnico_nome = trim(pg_fetch_result($res,0,tecnico_nome));

        if($ativo == 't'){
            $x_ativo = 'f';
            $resposta = "<a href=\"javascript:ativa_desativa_tecnico('$treinamento_posto','$id')\">Cancelado</a>|vermelho";

            //--== Envio de email =========================================================================
            $sql=  "SELECT  titulo                            ,
                    TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY') AS data_inicio,
                    TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')    AS data_fim
                    FROM tbl_treinamento
                    JOIN tbl_treinamento_posto USING(treinamento)
                     WHERE treinamento_posto = $treinamento_posto";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $titulo      = pg_fetch_result($res,0,titulo)     ;
                $data_inicio = pg_fetch_result($res,0,data_inicio);
                $data_fim    = pg_fetch_result($res,0,data_fim)   ;
            }

            $email_origem  = "verificacao@telecontrol.com.br";
            $email_destino = "$email";
            $assunto       = "Incrição Cancelada em Treinamento";

            $corpo.= "Titulo: $titulo <br>\n";
            $corpo.= "Data Inicío: $data_inicio<br> \n";
            $corpo.= "Data Término: $data_fim <p>\n";

            $corpo.="<br>Desculpe-nos pelo transtorno, mas estamos cancelando o treinamento conforme agendado!\nPor favor fazer a inscrição do mesmo em uma outra data, conforme disponível no sistema Telecontrol..\n\n";
            $corpo.="<br>Nome: $tecnico_nome \n";
            $corpo.="<br>Email: $email\n\n";
            $corpo.="<br><br><br>Telecontrol\n";
            $corpo.="<br>www.telecontrol.com.br\n";
            $corpo.="<br>_______________________________________________\n";
            $corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";


            $body_top = "MIME-Version: 1.0\r\n";
            $body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
            $body_top .= "From: $email_origem\r\n";

            if (!in_array($login_fabrica, [175])) {
                if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), $body_top ) ){
                    $msg = "$email";
                }else{
                    $msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
                }
            }

        }
        else{
            $x_ativo = 't';
            $resposta = "<a href=\"javascript:ativa_desativa_tecnico('$treinamento_posto','$id')\">Confirmado</a>|verde";
        }
        $sql = "UPDATE tbl_treinamento_posto SET ativo = '$x_ativo' WHERE treinamento_posto = $treinamento_posto";
        $res = pg_query($con,$sql);
    }
    echo "ok|".$resposta;
    exit;
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='ativa_desativa_participou') {
    $treinamento_posto = $_GET["treinamento_posto"];
    $id                = $_GET["id"];

    $sql = "SELECT participou FROM tbl_treinamento_posto WHERE treinamento_posto = $treinamento_posto";

    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $participou       = trim(pg_fetch_result($res,0,participou))       ;
        if($participou == 't'){
            $x_participou = 'f';
            $resposta = "<a href=\"javascript:ativa_desativa_participou('$treinamento_posto','$id')\">Não</a>|vermelho";

        }
        else{
            $x_participou = 't';
            $resposta = "<a href=\"javascript:ativa_desativa_participou('$treinamento_posto','$id')\">Sim</a>|verde";
        }

        if($_GET['participou'] != ""){
            if($_GET['participou'] == 'SIM'){
                $x_participou = 't';
            }elseif($_GET['participou'] == "NAO"){
                $x_participou = 'f';
            }
        }

        $sql = "UPDATE tbl_treinamento_posto SET participou = '$x_participou' WHERE treinamento_posto = $treinamento_posto";
        $res = pg_query($con,$sql);

        if($_GET['participou'] != ""){
            echo json_encode(array("success" => "ok"));exit;
        }
    }
    echo "ok|".$resposta;
    exit;
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='ativa_desativa_hotel') {
    $treinamento_posto = $_GET["treinamento_posto"];
    $id                = $_GET["id"];

    $sql = "SELECT hotel FROM tbl_treinamento_posto WHERE treinamento_posto = $treinamento_posto";

    $res = pg_query($con,$sql);

    if ($login_fabrica == 20){

        if (pg_num_rows($res) > 0) {
            $hotel       = trim(pg_fetch_result($res,0,hotel))       ;
            if($hotel == 't'){
                $x_hotel  = 'f';
                $resposta = "Não|vermelho";

            }
            else{
                $x_hotel  = 't';
                $resposta = "Sim|verde";
            }
        }
    }else{

        if (pg_num_rows($res) > 0) {
            $hotel       = trim(pg_fetch_result($res,0,hotel))       ;
            if($hotel == 't'){
                $x_hotel  = 'f';
                $resposta = "<a href=\"javascript:ativa_desativa_hotel('$treinamento_posto','$id')\">Não</a>|vermelho";

            }
            else{
                $x_hotel  = 't';
                $resposta = "<a href=\"javascript:ativa_desativa_hotel('$treinamento_posto','$id')\">Sim</a>|verde";
            }
            $sql = "UPDATE tbl_treinamento_posto SET hotel = '$x_hotel' WHERE treinamento_posto = $treinamento_posto";
            $res = pg_query($con,$sql);
        }
    }
    echo "ok|".$resposta;
    exit;
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='relatorio_postos') {
    $sql = "SELECT(
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 92) ) AS autorizado,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 151) ) AS locadora,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 108) ) AS pr_be  ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 110) ) AS pr_fo ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 109) ) AS pr_go,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 117) ) AS vc_bh ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 111) ) AS vc_ct,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 112) ) AS  vc_pa  ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 116) ) AS  vc_re  ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 120) ) AS vc_rj ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 115) ) AS vc_sa  ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 113) ) AS  vc_sp1  ,
      (
    SELECT count(*) from tbl_posto_fabrica
    JOIN tbl_tipo_posto USING(tipo_posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN(SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 114) ) AS  vc_sp2,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 92)
      ) x
        )AS autorizado_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 151)
      ) x
        )AS locadora_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 108)
      ) x
    )AS pr_be_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 110)
      ) x
    )AS pr_fo_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 109)
      ) x
    )AS pr_go_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 117)
      ) x
    )AS vc_bh_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 111)
      ) x
    )AS vc_ct_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 112)
      ) x
     )AS vc_pa_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 116)
      ) x
    )AS vc_re_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 120)
      ) x
    )AS vc_rj_treinados,
      (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 115)
      ) x
    )AS vc_sa_treinados,
     (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 113)
     ) x
    )AS vc_sp1_treinados,
     (
    SELECT count(*)
    FROM (
    SELECT distinct posto
    FROM tbl_treinamento_posto
    JOIN tbl_treinamento   USING(treinamento)
    JOIN tbl_posto_fabrica USING(posto)
    WHERE tbl_posto_fabrica.fabrica = $login_fabrica
    AND tipo_posto IN (SELECT tipo_posto from tbl_tipo_posto WHERE tipo_posto = 114)
      ) x
    )AS vc_sp2_treinados";
    $res = pg_query($con,$sql);

    $resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' >";
    $resposta  .=  "<TR class='Titulo'  height='25'>";
    $resposta  .=  "<TD nowrap></TD>";
    $resposta  .=  "<TD nowrap>Quantidade</TD>";
    $resposta  .=  "<TD nowrap>Treinados</TD>";
    $resposta  .=  "<TD nowrap>%</TD>";
    $resposta  .=  "</TR>";

    if (pg_num_rows($res)>0){
        $autorizado           = trim(pg_fetch_result($res,0,autorizado))            ;
        $locadora             = trim(pg_fetch_result($res,0,locadora))              ;
        $pr_be                = trim(pg_fetch_result($res,0,pr_be))                 ;
        $pr_fo                = trim(pg_fetch_result($res,0,pr_fo))                 ;
        $pr_go                = trim(pg_fetch_result($res,0,pr_go))                 ;
        $vc_bh                = trim(pg_fetch_result($res,0,vc_bh))                 ;
        $vc_ct                = trim(pg_fetch_result($res,0,vc_ct))                 ;
        $vc_pa                = trim(pg_fetch_result($res,0,vc_pa))                 ;
        $vc_re                = trim(pg_fetch_result($res,0,vc_re))                 ;
        $vc_rj                = trim(pg_fetch_result($res,0,vc_rj))                 ;
        $vc_sa                = trim(pg_fetch_result($res,0,vc_sa))                 ;
        $vc_sp1               = trim(pg_fetch_result($res,0,vc_sp1))                ;
        $vc_sp2               = trim(pg_fetch_result($res,0,vc_sp2))                ;
        $autorizado_treinados = trim(pg_fetch_result($res,0,autorizado_treinados))  ;
        $locadora_treinados   = trim(pg_fetch_result($res,0,locadora_treinados))    ;
        $pr_be_treinados      = trim(pg_fetch_result($res,0,pr_be_treinados))       ;
        $pr_fo_treinados      = trim(pg_fetch_result($res,0,pr_fo_treinados))       ;
        $pr_go_treinados      = trim(pg_fetch_result($res,0,pr_go_treinados))       ;
        $vc_bh_treinados      = trim(pg_fetch_result($res,0,vc_bh_treinados))       ;
        $vc_ct_treinados      = trim(pg_fetch_result($res,0,vc_ct_treinados))       ;
        $vc_pa_treinados      = trim(pg_fetch_result($res,0,vc_pa_treinados))       ;
        $vc_re_treinados      = trim(pg_fetch_result($res,0,vc_re_treinados))       ;
        $vc_rj_treinados      = trim(pg_fetch_result($res,0,vc_rj_treinados))       ;
        $vc_sa_treinados      = trim(pg_fetch_result($res,0,vc_sa_treinados))       ;
        $vc_sp1_treinados     = trim(pg_fetch_result($res,0,vc_sp1_treinados))      ;
        $vc_sp2_treinados     = trim(pg_fetch_result($res,0,vc_sp2_treinados))      ;

    if($autorizado == 0 OR $autorizado_treinados == 0) $s_per = 0;
    else                                   $s_per = ($autorizado_treinados * 100) / $autorizado;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>AUTORIZADO</TD>";
    $resposta  .=  "<TD align='center'nowrap>$autorizado</TD>";
    $resposta  .=  "<TD align='center'nowrap>$autorizado_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($locadora == 0 OR $locadora_treinados == 0) $s_per = 0;
    else                                   $s_per = ($locadora_treinados * 100) / $locadora;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>LOCADORA</TD>";
    $resposta  .=  "<TD align='center'nowrap>$locadora</TD>";
    $resposta  .=  "<TD align='center'nowrap>$locadora_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($pr_be == 0 OR $pr_be_treinados == 0) $s_per = 0;
    else                                   $s_per = ($pr_be_treinados * 100) / $pr_be;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>PR/BE</TD>";
    $resposta  .=  "<TD align='center'nowrap>$pr_be</TD>";
    $resposta  .=  "<TD align='center'nowrap>$pr_be_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($pr_fo == 0 OR $pr_fo_treinados == 0) $s_per = 0;
    else                                   $s_per = ($pr_fo_treinados * 100) / $pr_fo;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>PR/FO</TD>";
    $resposta  .=  "<TD align='center'nowrap>$pr_fo</TD>";
    $resposta  .=  "<TD align='center'nowrap>$pr_fo_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($pr_go == 0 OR $pr_go_treinados == 0) $s_per = 0;
    else                                   $s_per = ($pr_go_treinados * 100) / $pr_go;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>PR/GO</TD>";
    $resposta  .=  "<TD align='center'nowrap>$pr_go</TD>";
    $resposta  .=  "<TD align='center'nowrap>$pr_go_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_bh == 0 OR $vc_bh_treinados == 0) $s_per = 0;
    else                                     $s_per = ($vc_bh_treinados * 100) / $vc_bh;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/BH</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_bh</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_bh_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_ct == 0 OR $vc_ct_treinados == 0) $s_per = 0;
    else                                     $s_per = ($vc_ct_treinados * 100) / $vc_ct;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/CT</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_ct</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_ct_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_pa == 0 OR $vc_pa_treinados == 0) $s_per = 0;
    else                                     $s_per = ($vc_pa_treinados * 100) / $vc_pa;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/PA</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_pa</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_pa_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_re == 0 OR $vc_re_treinados == 0) $s_per = 0;
    else                                     $s_per = ($vc_re_treinados * 100) / $vc_re;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/RE</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_re</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_re_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_rj == 0 OR $vc_rj_treinados == 0) $s_per = 0;
    else                                   $s_per = ($vc_rj_treinados * 100) / $vc_rj;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/RJ</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_rj</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_rj_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_sa == 0 OR $vc_sa_treinados == 0) $s_per = 0;
    else                                     $s_per = ($vc_sa_treinados * 100) / $vc_sa;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/SA</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_sa</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_sa_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_sp1 == 0 OR $vc_sp1_treinados == 0) $s_per = 0;
    else                                   $s_per = ($vc_sp1_treinados * 100) / $vc_sp1;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/SP1</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_sp1</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_sp1_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    if($vc_sp2 == 0 OR $vc_sp2_treinados == 0) $s_per = 0;
    else                                   $s_per = ($vc_sp2_treinados * 100) / $vc_sp2;
    $per = number_format($s_per,2,",",".");
    $resposta  .=  "<TR bgcolor='#F7F5F0' class='Conteudo'>";
    $resposta  .=  "<TD align='left'nowrap>VC/SP2</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_sp2</TD>";
    $resposta  .=  "<TD align='center'nowrap>$vc_sp2_treinados</TD>";
    $resposta  .=  "<TD align='left'nowrap>$per</TD>";
    $resposta  .=  "</TR>";

    $resposta .=  "</table>";

    }

    if($login_fabrica == 1){
        $resposta .= "<a href=\"javascript:gerar_relatorio_treinamento_regiao('168','')\">DeWalt - Treinamento Completo</a><br>";
        $resposta .= "<a href=\"javascript:gerar_relatorio_treinamento_regiao('','347')\">DeWalt - Compressores</a><br>";
        $resposta .= "<a href=\"javascript:gerar_relatorio_treinamento_regiao('','303')\">DeWalt - Martelo</a><br>";
        $resposta .= "<a href=\"javascript:gerar_relatorio_treinamento_regiao('','')\">Todos Treinamentos</a><br>";
    }else{

    }
    echo "ok|".$resposta;
    exit;
}

function verificarPostoTreinamento($con,$treinamento)
{
    $sql = "
        SELECT  COUNT(1) AS temPosto
        FROM    tbl_treinamento_posto
        JOIN    tbl_treinamento USING(treinamento)
        WHERE   treinamento = $treinamento
        AND     tecnico IS NOT NULL

    ";
    $res = pg_query($con,$sql);
    $temPosto = pg_fetch_result($res,0,temPosto);

    if ($temPosto == 0) {
        $sqlData = "
            SELECT  treinamento
            FROM    tbl_treinamento
            WHERE   data_fim >= CURRENT_DATE
            AND     data_finalizado IS NULL
            AND     treinamento = $treinamento
        ";
        $resData = pg_query($con,$sqlData);

        $treinamentoAberto = pg_fetch_result($resData,0,treinamento);

        $temPosto = ($treinamentoAberto == $treinamento) ? 0 : 1;

    }

    return $temPosto;
}

function valida_data_treinamento_new($data_inicial, $data_final) {
    if (empty($data_inicial) || empty($data_final)) {
        return true;
    } else {
        $data_inicial_format = date_create_from_format('d/m/Y', $data_inicial);
        $data_inicial_format = date_format($data_inicial_format, 'Y-m-d');
        $data_final_format   = date_create_from_format('d/m/Y', $data_final);
        $data_final_format   = date_format($data_final_format, 'Y-m-d');

        $hj = date('Y-m-d');

        if (strtotime($data_inicial_format) < strtotime($hj)) {
            return true;
        }

        if (strtotime($data_inicial_format) > strtotime($data_final_format)) {
            return true;
        }
    }

    return false;
}

if (filter_input(INPUT_POST,"ajax",FILTER_VALIDATE_BOOLEAN)) {
    $tipo               = filter_input(INPUT_POST,"tipo");
    $treinamento_posto  = filter_input(INPUT_POST,"treinamento_posto",FILTER_VALIDATE_INT);
    $nome               = filter_input(INPUT_POST,"nome");
    $rg                 = filter_input(INPUT_POST,"rg",FILTER_SANITIZE_NUMBER_INT);
    $cpf                = filter_input(INPUT_POST,"cpf",FILTER_SANITIZE_NUMBER_INT);
    $telefone           = filter_input(INPUT_POST,"telefone");
	$nascimento = filter_input(INPUT_POST, 'nascimento');

    switch ($tipo) {
        case "mostraTecnico":
            $sqlTecnico = "SELECT   tbl_tecnico.nome,
                                    tbl_tecnico.rg,
                                    tbl_tecnico.cpf,
                                    tbl_tecnico.celular,
                                    tbl_tecnico.telefone,
                                    tbl_tecnico.email,
									TO_CHAR(data_nascimento,'DD/MM/YYYY') AS data_nascimento
                            FROM    tbl_tecnico
                            JOIN    tbl_treinamento_posto USING(tecnico)
                            WHERE   tbl_treinamento_posto.treinamento_posto = $treinamento_posto";

            $resTecnico = pg_query($con,$sqlTecnico);

            $tecnicoNome        = utf8_decode(pg_fetch_result($resTecnico,0,nome));
            $tecnicoRg          = pg_fetch_result($resTecnico,0,rg);
            $tecnicoCpf         = pg_fetch_result($resTecnico,0,cpf);
            $tecnicoTelefone    = pg_fetch_result($resTecnico,0,telefone);
            $tecnicoCelular     = pg_fetch_result($resTecnico,0,celular);
            $tecnicoEmail       = pg_fetch_result($resTecnico,0,email);
			$tecnicoNascimento = pg_fetch_result($resTecnico, 0, 'data_nascimento');

            switch ($login_fabrica) {
            	case 1:
            		$respHtml = "
		                <tr id='resp_$treinamento_posto'>
		                    <td colspan='11'>
                                <table border='0'>
                                    <tr>
                                        <td>
                                            Nome Técnico<br />
                                            <input type='text' name='tecnico_nome' id='txt_tecnico_nome_$treinamento_posto' value='$tecnicoNome' />
                                        </td>
                                        <td>
                                            RG Técnico<br />
                                            <input type='text' name='tecnico_rg'   id='txt_tecnico_rg_$treinamento_posto'   value='$tecnicoRg' />
                                            <br />
                                        </td>
                                        <td>
                                            E-mail Técnico<br />
                                            <input type='text' name='tecnico_email' id='txt_tecnico_email_$treinamento_posto' value='$tecnicoEmail' />
                                            <br />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            CPF Técnico<br />
                                            <input type='text' name='tecnico_cpf'  id='txt_tecnico_cpf_$treinamento_posto'  value='$tecnicoCpf' maxlength='11' />
                                        </td>
                                        <td>
                                            Nascimento Técnico<br />
                                            <input type='text' name='tecnico_cpf' class='txt_nascimento' id='txt_tecnico_nasc_$treinamento_posto'  value='$tecnicoNascimento' maxlength='10' />
                                        </td>
                                        <td>
                                            Celular Técnico<br />
                                            <input type='text' name='tecnico_fone' class='txt_fone' id='txt_tecnico_fone_$treinamento_posto' value='$tecnicoCelular' maxlength='13' />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan='3'>
                                            <button type='button' class='btn btn-success' id='enviar_$treinamento_posto' onclick='javascript:gravaTecnico($treinamento_posto)'>Enviar</button>
                                        </td>
                                    </tr>
                                </table>
		                    </td>
		                <tr> ";
            		break;

            	default:
            		$respHtml = "
		                <tr id='resp_$treinamento_posto'>
                            <td colspan='10'>
                                <table>
                                    <tr>
                                        <td>
                                            <label>Nome:</label>
                                            <input type='text' name='tecnico_nome' id='txt_tecnico_nome_$treinamento_posto' value='$tecnicoNome' />
                                        </td>
                                        <td>
                                            <label>RG:</label>
                                            <input type='text' name='tecnico_rg' id='txt_tecnico_rg_$treinamento_posto'   value='$tecnicoRg'/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label>CPF:</label>
                                            <input type='text' name='tecnico_cpf'  id='txt_tecnico_cpf_$treinamento_posto'  value='$tecnicoCpf' maxlength='11' />
                                        </td>
                                        <td>
                                            <label>Telefone:</label>
                                            <input type='text' name='tecnico_fone' class='txt_fone' id='txt_tecnico_fone_$treinamento_posto' value='$tecnicoTelefone' maxlength='13' />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <button type='button' class='btn btn-success' id='enviar_$treinamento_posto' onclick='javascript:gravaTecnico($treinamento_posto)'>Enviar</button>
                                        </td>
                                    </tr>
                                <table>
		                    </td>
		                <tr> ";
            		break;
            }


            echo $respHtml;
            break;
        case "gravaTecnico":
            /*
             * Busca ID do técnico
             */

            $sqlBuscaTec = "
                SELECT  tbl_treinamento_posto.tecnico
                FROM    tbl_treinamento_posto
                WHERE   tbl_treinamento_posto.treinamento_posto = $treinamento_posto
            ";
            $resBuscaTec    = pg_query($con,$sqlBuscaTec);
            $tecnico        = pg_fetch_result($resBuscaTec,0,tecnico);

            /*
             * Atualiza os dados
             * do técnico mencionado
             */
            if (!pg_last_error($con)) {
                pg_query($con,"BEGIN TRANSACTION");

                $nome = pg_escape_literal($con, $nome);

                $sqlUp = "
                    UPDATE  tbl_tecnico
                    SET     nome = $nome,
                            cpf = '$cpf',
                            rg = '$rg',
                            telefone = '$telefone'
                    WHERE   tecnico = $tecnico
                ";
                if (in_array($login_fabrica, [1])) {
					if (!empty($nascimento)) {
						$dt = DateTime::createFromFormat('d/m/Y', $nascimento);

						if ($dt->format('d/m/Y') <> $nascimento) {
							pg_query($con,"ROLLBACK TRANSACTION");
							die("Data nascimento inválida.");
						}

						$data_nascimento = $dt->format('Y-m-d');
					}

                    $sqlUp = "
                        UPDATE  tbl_tecnico
                        SET     nome = $nome,
                                cpf = '$cpf',
                                rg = '$rg',
								data_nascimento = '$data_nascimento',
                                celular = '$telefone'
                        WHERE   tecnico = $tecnico
                    ";
                }
                $resUp = pg_query($con,$sqlUp);

                if (pg_last_error($con)) {
                    $erro = pg_last_error($con);
                    pg_query($con,"ROLLBACK TRANSACTION");
                    echo "Erro ao alterar dados do técnico: ".$erro;
                    exit;
                }

                pg_query($con,"COMMIT TRANSACTION");
                echo json_encode(array("ok"=>true,"msg"=>utf8_encode("Dados do Técnico alterados corretamente")));
            }
            break;
        case "concluir_treinamento":
            /*
             * - Marca o treinamento como concluído.
             * - Envia o SMS de satisfação aos participantes
             */

            pg_query($con,"BEGIN TRANSACTION");

            $sqlGrava = "
                UPDATE  tbl_treinamento
                SET     data_finalizado = CURRENT_TIMESTAMP
                WHERE   fabrica         = $login_fabrica
                AND     treinamento     = $treinamento_posto
            ";
//             exit($sqlGrava);
            $resGrava = pg_query($con,$sqlGrava);

            if (pg_last_error($con)) {
                $erro = pg_last_error($con);
                pg_query($con,"ROLLBACK TRANSACTION");

                echo $erro;
            }
            /*
             * - Envio de SMS
             */

            $sqlBuscaSms = "
                SELECT  tbl_treinamento.treinamento,
                        tbl_treinamento_posto.posto,
                        tbl_tecnico.celular         AS tecnico_celular,
                        tbl_tecnico.telefone        AS tecnico_fone,
                        tbl_tecnico.nome            AS tecnico,
                        tbl_tecnico.tecnico         AS id_tecnico
                FROM    tbl_treinamento
                JOIN    tbl_treinamento_posto   USING(treinamento)
                JOIN    tbl_tecnico             USING(tecnico)
                WHERE   tbl_treinamento.fabrica     = $login_fabrica
                AND     tbl_treinamento.treinamento  = $treinamento_posto
            ";

            $resBuscaSms = pg_query($con,$sqlBuscaSms);

            $helper = new \Posvenda\Helpers\Os();
            while($tecnico = pg_fetch_object($resBuscaSms)) {

                //$link = " http://novodevel.telecontrol.com.br/~lucas/PosVendaAssist/externos/blackedecker/treinamento_pesquisa_satisfacao.php";
                $link = " https://posvenda.telecontrol.com.br/assist/externos/blackedecker/treinamento_pesquisa_satisfacao.php";
                $idTecnico = $tecnico->id_tecnico;
                $msgSMS = "Favor realizar a pesquisa de satisfação do treinamento no link: ".$link."?b=$idTecnico&a=$treinamento_posto";
                $numMsg = strlen($msgSMS);
                $creditoEnvio = ceil($numMsg/160);
                $campo = "";
                $value = "";


                if (!pg_last_error($con)) {
                    $helper->comunicaConsumidor($tecnico->tecnico_celular, $msgSMS, $fabrica);
                } else {
                    $erro = pg_last_error($con);
                    pg_query($con,"ROLLBACK TRANSACTION");

                    echo $erro;
                }
            }

            pg_query($con,"COMMIT TRANSACTION");

            echo json_encode(array("ok"=>true,"msg"=>utf8_encode("Treinamento concluído.")));

            break;
        case "excluir_treinamento":
            /*
             * - Sem Postos cadastrados no treinamento:
             * pode apagar normalmente.
             *
             * - Com Postos cadastrados no treinamento:
             * Verificar se o treinamento NÃO está concluído ou finalizado;
             * Abrir tela para texto de comunicado a enviar aos postos
             */

            $verificarPosto = verificarPostoTreinamento($con,$treinamento_posto);

            if ($verificarPosto == 0) {
                pg_query($con,"BEGIN TRANSACTION");

                $sqlDel = "
                    DELETE  FROM tbl_treinamento
                    WHERE   fabrica = $login_fabrica
                    AND     treinamento = $treinamento_posto
                ";
                $resDel = pg_query($con,$sqlDel);

                if (pg_last_error($con)) {
                    $erro = pg_last_error($con);
                    pg_query($con,"ROLLBACK TRANSACTION");
                    echo "erro: ".$erro;
                }

                pg_query($con,"COMMIT TRANSACTION");
                echo json_encode(array("ok"=>true,"envia_comunicado"=>false,"msg"=>utf8_encode("Treinamento excluído com sucesso")));
            } else {
                echo json_encode(array("ok"=>true,"envia_comunicado"=>true));
            }
            break;
    }

    exit;
}
