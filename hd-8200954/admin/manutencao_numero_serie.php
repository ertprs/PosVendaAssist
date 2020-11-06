<?php
    header("Cache-Control: no-cache, must-revalidate");
    header('Pragma: no-cache');
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'funcoes.php';
    $admin_privilegios = "cadastros";
    include "autentica_admin.php";
    function valida_cnpj($cnpj){
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        // Lista de CNPJs inválidos
        $invalidos = array(
            '00000000000000',
            '11111111111111',
            '22222222222222',
            '33333333333333',
            '44444444444444',
            '55555555555555',
            '66666666666666',
            '77777777777777',
            '88888888888888',
            '99999999999999'
        );
        // Verifica se o CNPJ está na lista de inválidos
        if (in_array($cnpj, $invalidos)) {
            return false;
        }
        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;
        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
        {
            $soma += $cnpj{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
            return false;
        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
        {
            $soma += $cnpj{$i} * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj{13} == ($resto < 2 ? 0 : 11 - $resto);
    }
    if (strlen($_POST["btn_acao"]) > 0) {
        if (empty($_FILES["arquivo"]['tmp_name'])){
            $msg_erro["msg"][] = 'Nenhum arquivo selecionado';
            $msg_erro["campos"][] = 'arquivo';
        }

        if (!in_array($_FILES["arquivo"]["type"], array('text/plain')) && !count($msg_erro["msg"]))
            $msg_erro["msg"][] = 'Arquivo inválido para importação, utilize arquivo texto (txt)';
        $sql = "SELECT lower(nome) FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
        $res = pg_query($con,$sql);
        $nome_fabrica = str_replace(" ", "", pg_fetch_result($res,0,0));

        if(in_array($login_fabrica, array(154,165,167,203))){
            if(!file_exists("/tmp/$nome_fabrica/")){
                system("mkdir /tmp/$nome_fabrica/ 2> /dev/null ; chmod 777 /tmp/$nome_fabrica/");
            }
            $caminho = "/tmp/$nome_fabrica/";
        }else{
            $caminho = "/var/www/cgi-bin/$nome_fabrica/entrada/";
        }
        $arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
        if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND !count($msg_erro["msg"])){
            flush();
            $nome_arquivo     = "num_serie";
            $nome_arquivo_aux = $nome_arquivo;
            $nome_arquivo     = $caminho.$nome_arquivo.".txt";
            $config["tamanho"] = 2048000;
            if ($arquivo["size"] > $config["tamanho"]) {
                $msg_erro["msg"][] = "O arquivo deve ter de no máximo 2MB";
            }
            if (!count($msg_erro["msg"])) {
                system ("rm -f $nome_arquivo");
                if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
                    $msg_erro["msg"][] = "Arquivo não enviado";
                }else{

                    if(in_array($login_fabrica,array(150))){
                        $msg_erro_aux = system("php ../rotinas/$nome_fabrica/importa_numero_serie.php",$ret);
                    }else if(in_array($login_fabrica, array(154,165,167,203))){
                        $msg_erro_aux = system("php ../rotinas/$nome_fabrica/importa-numero-serie.php",$ret);
                    }else{
                        $msg_erro_aux = system("/www/cgi-bin/$nome_fabrica/importa-numero-serie.pl",$ret);
                    }
                    $arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
                    if (strlen($msg_erro_aux) > 0) {
                        $msg_erro["msg"][] = "Erro no formato do arquivo, por favor, verifique";
                    } else {
                        $sql = "
                                SELECT
                                    serie,
                                    referencia_produto,
                                    TO_CHAR(data_fabricacao,'DD/MM/YYYY') AS data_fabricacao,
                                    TO_CHAR(data_venda,'DD/MM/YYYY') AS data_venda,
                                    serie_peca,
                                    referencia_peca
                                FROM tbl_numero_serie
                                LEFT JOIN tbl_numero_serie_peca USING(numero_serie)
                                WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                                AND tbl_numero_serie.data_carga::DATE = CURRENT_DATE;
                            ";
                        $res_upload = pg_query($con,$sql);
                        if (pg_num_rows($res_upload) > 0) {
                            $msg .= "Arquivo importado com sucesso";
                        }else{
                            $msg_erro["msg"][] = "Arquivo não importado";
                        }
                    }
                }
            }
        }
        $arq_erro = "/tmp/$nome_fabrica/imp_numero_serie.err";
        if(!count($msg_erro["msg"]) and file_exists($arq_erro) and filesize($arq_erro) > 0) {
            $abrir = fopen($arq_erro, "r");
            $msg_erro["msg"][] = fread($abrir, filesize($arq_erro));
            fclose($abrir);
        }
    }
    if(isset($_POST["upload_arquivo_pecas"])){
        $arquivo = $_FILES["arquivo"];
        if($arquivo["size"] == 0){
            $msg_erro["msg"][] = "Por favor, insira um arquivo para realizar o Upload";
        }
        $arquivo_nome = explode(".", $arquivo["name"]);
        $ext          = $arquivo_nome[count($arquivo_nome) - 1];
        if(!in_array($ext, array("csv", "txt"))){
            $msg_erro["msg"][] = "Por favor, insira um arquivo TXT ou CSV";
        }
        if(count($msg_erro) == 0){
            $conteudo   = file_get_contents($arquivo["tmp_name"]);
            $linhas     = explode("\n", $conteudo);
            $cont_linha     = 1;
            $serie_gravadas = 0;


            foreach ($linhas as $linha) {

                // ignora linhas em branco
                if(empty(trim($linha))){
                    continue;
                }

                $dados = explode(";", $linha);
                $produto_referencia = trim($dados[0]);
                $data_fabricacao    = trim($dados[1]);
                $data_venda         = trim($dados[2]);
                $numero_serie       = trim($dados[3]);
                $cnpj_cliente       = trim($dados[4]);

                $dados_informados = true;
                $acoes = '';
                $liberado_garantia = '';

                if( strlen($produto_referencia) == 0 ||
                    strlen($data_fabricacao) == 0 ||
                    strlen($numero_serie) == 0 ){           
                    $dados_informados = false;
                }

                if($login_fabrica == 158){
                    $acoes = trim($dados[5]);

                    if(strlen($acoes) == 0){
                        $dados_informados = false;
                    } 
                }else{

                    $liberado_garantia  = trim($dados[5]); 

                    if(strlen($liberado_garantia) == 0){
                        $dados_informados = false;
                    }
                }

                $linha_erro = false;
                if(!$dados_informados){
                    $linha_erro = true;
                    $msg_erro["msg"][] = "A linha {$cont_linha} não está com as informações corretas";
                }else if(!in_array($liberado_garantia, array("não", "nao", "sim")) && $login_fabrica != 158){
                    $linha_erro = true;
                    $msg_erro["msg"][] = "A linha {$cont_linha} está com a informação de <u>liberado para garantia</u> diferente de <u>sim</u> ou <u>nao</u>";
                }else if(!in_array(strtoupper($acoes), array("INSERIR", "ALTERAR")) && $login_fabrica != 85){
                    $linha_erro = true;
                    $msg_erro["msg"][] = "A linha {$cont_linha} está com a informação de <u>ações</u> diferente de <u>inserir</u> ou <u>alterar</u>";
                }else if(!strstr($data_fabricacao, "/")){
                    $linha_erro = true;
                    $msg_erro["msg"][] = "A linha {$cont_linha} está com a informação de <u>data de fabrica</u> com formato incorreto";
                }else if(strlen($data_venda) > 0 && !strstr($data_venda, "/")){
                    $linha_erro = true;
                    $msg_erro["msg"][] = "A linha {$cont_linha} está com a informação de <u>data de venda</u> com formato incorreto";
                }else if(strlen($cnpj_cliente) > 20){
                    $linha_erro = true;
                    $msg_erro["msg"][] = "A linha {$cont_linha} está com a informação do <u>CNPJ do cliente</u> acima de 20 caracteres";
                }else{
                    if(strlen($cnpj_cliente) > 0){
                        if(valida_cnpj($cnpj_cliente) == false){
                            $linha_erro = true;
                            $msg_erro["msg"][] = "A linha {$cont_linha}: O <u>CNPJ revenda</u> é inválido";
                        }
                    }
                    list($dia, $mes, $ano) = explode("/", $data_fabricacao);
                    $data_fabricacao = $ano."-".$mes."-".$dia;
                    if(!checkdate($mes, $dia, $ano)){
                        $linha_erro = true;
                        $msg_erro["msg"][] = "A linha {$cont_linha}: A <u>data de fabricação</u> é inválida";
                    }
                    if(strlen($data_venda) > 0){
                        list($dia, $mes, $ano) = explode("/", $data_venda);
                        $data_venda = $ano."-".$mes."-".$dia;
                        if(!checkdate($mes, $dia, $ano)){
                            $linha_erro = true;
                            $msg_erro["msg"][] = "A linha {$cont_linha}: A <u>data de venda</u> é inválida";
                        }
                        if(strtotime($data_fabricacao) > strtotime($data_venda)){
                            $linha_erro = true;
                            $msg_erro["msg"][] = "A linha {$cont_linha}: A <u>data de venda</u> é menor que a <u>data de fabricação</u>";
                        }
                    }
                    $sql_peca = "SELECT produto FROM tbl_produto WHERE referencia = '{$produto_referencia}' AND fabrica_i = {$login_fabrica}";
                    $res_peca = pg_query($con, $sql_peca);
                    if(pg_num_rows($res_peca) == 0){
                        $linha_erro = true;
                        $msg_erro["msg"][] = "A linha {$cont_linha}: O produto <u>{$produto_referencia}</u> não foi localizado na base dados";
                    }else{
                        $produto = pg_fetch_result($res_peca, 0, "produto");
                    }

                    if(strtoupper($acoes) == 'ALTERAR' && $login_fabrica == 158){

                        $cond_cnpj_cliente = (strlen($cnpj_cliente) > 0) ? " AND cnpj = '{$cnpj_cliente}' " : "";
                        $sql_num_serie = "SELECT numero_serie FROM tbl_numero_serie WHERE produto = {$produto} AND fabrica = {$login_fabrica} AND serie = '{$numero_serie}' {$cond_cnpj_cliente}";
                        $res_num_serie = pg_query($con, $sql_num_serie);

                        if(pg_num_rows($res_num_serie) == 0){
                            $linha_erro = true;
                            $msg_erro["msg"][] = "A linha {$cont_linha}: O número de sére <u>{$numero_serie}</u> não foi localizado na base dados";
                        }  
                    }
                }

                if($linha_erro == false && strlen($produto) > 0){
                    $garantia_extendida = ($liberado_garantia == "sim") ? "t" : "f";
                    $cond_cnpj_cliente = (strlen($cnpj_cliente) > 0) ? " AND cnpj = '{$cnpj_cliente}' " : "";

                    $sql_num_serie = "SELECT numero_serie FROM tbl_numero_serie WHERE produto = {$produto} AND fabrica = {$login_fabrica} AND serie = '{$numero_serie}' {$cond_cnpj_cliente}";
                    $res_num_serie = pg_query($con, $sql_num_serie);

                    // Para a fábrica Imbera, a ação de inserir ou alterar irá vir como informação no arquivo importado
                    $acao = '';
                    if($login_fabrica == 158){
                        $acao = $acoes;
                    }else if(pg_num_rows($res_num_serie) > 0){
                        $acao = 'alterar';
                    }else{
                        $acao = 'inserir';
                    }
                  
                    if(strtoupper($acao) == 'ALTERAR'){
                        $serie_id = pg_fetch_result($res_num_serie, 0, "numero_serie");
                        $opr = "update";
                        $sql_serie = "UPDATE tbl_numero_serie SET
                                        serie           = '{$numero_serie}',
                                        data_venda      = '{$data_venda}',
                                        data_fabricacao = '{$data_fabricacao}',
                                        cnpj            = '{$cnpj_cliente}'
                                    WHERE
                                        numero_serie = {$serie_id}
                                        AND fabrica = {$login_fabrica}
                                        AND produto = {$produto}
                                    ";
                    }else{
                        $opr = "insert";
                        $sql_serie = "INSERT INTO tbl_numero_serie (
                            fabrica,
                            serie,
                            referencia_produto,
                            garantia_extendida,
                            data_venda,
                            data_fabricacao,
                            produto,
                            admin,
                            cnpj
                        ) VALUES (
                            {$login_fabrica},
                            '{$numero_serie}',
                            '{$produto_referencia}',
                            '{$garantia_extendida}',
                            '{$data_venda}',
                            '{$data_fabricacao}',
                            {$produto},
                            {$login_admin},
                            '{$cnpj_cliente}'
                        )";
                    }
                    pg_query($con, "BEGIN TRANSACTION");
                    $res = pg_query($con, $sql_serie);
                    if(strlen(pg_last_error() > 0)){
                        pg_query($con, "ROLLBACK TRANSACTION");
                        $opr = ($opr == "insert") ? "gravar" : "atualizar";
                        $msg_erro["msg"][] = "A linha {$cont_linha}: Erro ao {$opr} a série <u>{$numero_serie}</u>";
                    }else{
                        pg_query($con, "COMMIT TRANSACTION");
                        $serie_gravadas++;
                    }
                }
                $cont_linha++;
            }
            if($serie_gravadas > 0){
                $msg_sucesso = "Upload realizado com Sucesso";
            }
        }
    }
    if(isset($_GET['serie']) and isset($_GET['ajax'])) {
        $sql = " SELECT referencia,
                        descricao,
                        serie_peca,
                        CASE WHEN tbl_numero_serie_peca.qtde ISNULL THEN 0 ELSE tbl_numero_serie_peca.qtde END as qtde
                FROM   tbl_numero_serie_peca
                JOIN   tbl_peca USING(peca,fabrica)
                WHERE  tbl_numero_serie_peca.fabrica = $login_fabrica
                AND    numero_serie = ".$_GET['serie'];
        $res = pg_query($con,$sql);
        $resultados = pg_fetch_all($res);
        if(pg_num_rows($res) > 0){
            //@todo verificar isso.. esta com cache no IE
            echo '<table class="tabela" width="700" align="center" cellspacing="1" id="ajax_'.$_GET['serie'].'">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>Nº de Série</th>
                            <th>Referência</th>
                            <th>Peça</th>
                            <th>Qtde</th>
                        </tr>
                    </thead>
                    <tbody>';
            $i = 0 ;
            foreach($resultados as $resultado){
                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                echo '<tr bgcolor="'.$cor.'">
                        <td>'.$resultado['serie_peca'].'</td>
                        <td>'.$resultado['referencia'].'</td>
                        <td>'.$resultado['descricao'].'</td>
                        <td>'.$resultado['qtde'].'</td></tr>';
                $i++;
            }
            echo '</tbody></table>';
        }else{
            echo "Nenhum resultado encontrado";
        }
        die;
    }
    $layout_menu = "cadastro";
    $title = "MANUTENÇÃO DE NÚMERO DE SÉRIE";
    include "cabecalho_new.php";
    $plugins = array(
        "datepicker",
        "mask",
        "dataTable",
        "shadowbox"
    );
    include("plugin_loader.php");
    $perm = array(74,85,90,94,95,106,108,111,120,138,145,146,148,149,150,151,156,157,158,165,167,169,170,175,176,183,203);
    if( !in_array( $login_fabrica, $perm ) ){
        echo " <br /> <div class='alert alert-error'><h4> Você não tem acesso à essa página </h4></div> <br /> ";
        include "rodape.php";
        exit;
    }
    if ( isset( $_GET['serie']) && !empty($_GET['serie']) ) { //ao clicar em alterar
        $serie = $_GET['serie'];
        $sql = 'SELECT serie,
                        cnpj,
                        referencia_produto,
                        TO_CHAR (data_venda,\'DD/MM/YYYY\') as data_venda,
                        TO_CHAR (data_fabricacao, \'DD/MM/YYYY\') as data_fabricacao,
                        tbl_numero_serie.produto,
                        tbl_numero_serie.ordem as pin, 
                        numero_serie,garantia_extendida,
                        tbl_produto.descricao as descricao_produto,
                        tbl_produto.fabrica_i
                FROM tbl_numero_serie
                JOIN tbl_produto ON tbl_produto.referencia = referencia_produto AND tbl_produto.fabrica_i = '.$login_fabrica.'
                WHERE fabrica = ' . $login_fabrica .'
                AND numero_serie = ' . $serie . '
                LIMIT 1';
        $query = pg_query($con,$sql);
        if (pg_numrows($query) == 0)
            $msg_erro = 'Nenhum Resultado Encontrado';
        else{
            $referencia         = trim(pg_result ($query,0,referencia_produto));
	    $referencia = (in_array($login_fabrica, array(169,170))) ? str_replace("YY", "-", $referencia) : $referencia;
            $produto_descricao  = trim(pg_result ($query,0,descricao_produto));
            $serie              = trim(pg_result ($query,0,serie));
            $num_serie          = trim(pg_result ($query,0,numero_serie));
            $data_fab           = trim(pg_result ($query,0,data_fabricacao));
            if ($login_fabrica != 120 and $login_fabrica != 201) {
                $data_venda         = trim(pg_result ($query,0,data_venda));
                $cnpj               = trim(pg_result ($query,0,cnpj));
                $garantia_extendida = trim(pg_result ($query,0,garantia_extendida));
                $xcnpj = $cnpj;
                $rec_data_venda = $data_venda;
            }

            if($login_fabrica == 148){
                $pin = pg_fetch_result($query, 0, 'pin');
            }
            $rec_data_fab = $data_fab;
        }
    }
    function get_post_action($name) {
        $params = func_get_args();
        foreach ($params as $name)
            if (isset($_POST[$name]))
                return $name;
    }
    if( isset($_POST['gravar']) || isset($_POST['pesquisar']) ) { //requisicao post, pesquisar ou gravar

        $serie      = trim($_POST['serie']);
        $referencia = trim($_POST['produto_referencia']);
        $data_fab   = trim($_POST['data_fabricacao']);

        if($login_fabrica == 148){
            $pin = $_POST['pin'];
        }

        if ($login_fabrica != 120 and $login_fabrica != 201) {
            $data_venda = trim($_POST['data_venda']);
            $cnpj       = preg_replace('/\D/', '', $_POST['cnpj']);
            $xcnpj = $cnpj;
            $rec_data_venda = $data_venda;
            $x_data_venda   = implode("/", array_reverse(explode("/", $data_venda)));
        }
        $rec_data_fab = $data_fab;
        $x_data_fab     = implode("/", array_reverse(explode("/", $data_fab)));
        $erro = array();
        unset($msg_erro);
        if ($login_fabrica != 120 and $login_fabrica != 201) {
            if (empty($data_venda))
                $x_data_venda = 'NULL';
            else
                $x_data_venda = "'".$x_data_venda."'";
        }

        if (empty($x_data_fab)){
            $x_data_fab = 'NULL';
        }else{
            $x_data_fab = "'".$x_data_fab."'";
        }

        if (empty($serie)) {
            if (empty($referencia)) {
                $erro["campos"][] = "produto_referencia";
                $erro["campos"][] = "produto_descricao";
                $msg_erro = 'Preencha os campos obrigatórios';
            }
            if($login_fabrica == 85){
                if (empty($data_fab)) {
                    $erro["campos"][] = 'dtfabricacao';
                    $msg_erro = 'Preencha os campos obrigatórios';
                }
            }else{
                if(!in_array($login_fabrica, [167, 203])){
                    if (empty($data_fab) && empty($data_venda)) {
                        $erro["campos"][] = 'dtfabricacao';
                        $erro["campos"][] = 'dtvenda';
                        $msg_erro = 'Preencha os campos obrigatórios';
                    }
                }
            }

            if (strlen($msg_erro) > 0 && empty($referencia)) {
                $erro["campos"][] = "serie";
                $msg_erro = 'Preencha os campos obrigatórios';
            }
        }

        if ($login_fabrica == 175){
            if (empty($referencia) AND empty($data_venda) AND empty($serie)){
                $msg_erro = "Preencha os campos obrigatórios";
            }else{

                unset($msg_erro);
                unset($erro["campos"]);
            }
        }

        if( empty($msg_erro)) {

    	    if (in_array($login_fabrica, array(169,170))) {
        		$referencia_pesquisa = str_replace("-", "YY", $referencia);
        		$wherePrd = "AND (UPPER(REPLACE(tbl_produto.referencia_pesquisa, '-', 'YY')) = UPPER('{$referencia_pesquisa}') OR UPPER(REPLACE(tbl_produto.referencia_fabrica, '-', 'YY')) = UPPER('{$referencia_pesquisa}') OR UPPER(REPLACE(tbl_produto.referencia, '-', 'YY')) = UPPER('{$referencia_pesquisa}'))";
    	    } else {
    		  $wherePrd = "AND referencia = '{$referencia}'";
    	    }

            $sql = "SELECT referencia, produto, descricao
                    FROM tbl_produto
                    JOIN tbl_linha USING (linha)
                    WHERE fabrica = {$login_fabrica}
		    {$wherePrd};";

            $query = pg_query($con,$sql);
            //pesquisa por num. de serie nao precisa de produto
            if (pg_numrows($query) == 0 && get_post_action('gravar') ){ //
                $erro["campos"][] = "produto_referencia";
                $erro["campos"][] = "produto_descricao";
                $msg_erro = 'Produto não Encontrado';
            } else {
                /* esconde erro por causa da pesquisa por num. de serie nao precisar de produto */
        		if (pg_num_rows($query) > 0) {
        		    $referencia	        = trim(pg_fetch_result($query,0,referencia));
                            $produto            = trim(pg_fetch_result($query,0,produto));
                            $produto_descricao  = trim(pg_fetch_result($query,0,descricao));
        		}
                switch (get_post_action('gravar', 'pesquisar')) {
                    case 'gravar':
                        /* validações antes do update/insert */
                        if(!in_array($login_fabrica, array(167,175,176,203))){
                            list($di, $mi, $yi) = explode("/", $data_fab);
                            if(!checkdate($mi,$di,$yi)) {
                                $erro["campos"][] = 'dtfabricacao';
                                $msg_erro = "Data Inválida";
                                break;
                            }
                        }

                        if(strlen(trim($serie)) == 0){
                            $erro["campos"][] = 'serie';
                            $msg_erro = "Preencha o número de série.";
                            break;
                        }

                        if(!empty($data_venda) && !in_array($login_fabrica, array(85,120,201))) {
                            list($di, $mi, $yi) = explode("/", $data_venda);
                            if(!checkdate($mi,$di,$yi)) {
                                $erro["campos"][] = 'dtvenda';
                                $msg_erro = "Data Inválida";
                                break;
                            }
                        }
                        if (!empty($cnpj) && checa_cnpj($cnpj) == 1 && $login_fabrica != 120 and $login_fabrica != 201) {
                            $erro["campos"][] = 'cnpj';
                            $msg_erro = 'CNPJ Inválido!';
                            break;
                        }
                        /* verifica se ja existe num. de serie para esse produto */
                        $sql = 'SELECT serie FROM tbl_numero_serie
                                WHERE produto = ' . $produto . '
                                AND fabrica = ' . $login_fabrica . '
                                AND serie = \'' . $serie . '\'';
                        /* pega apenas se for diferente do que vai fazer update */
                        if (!empty($_POST['num_serie']))
                            $sql .= ' AND numero_serie <>' . $_POST['num_serie'];
                        $query = pg_query ($con,$sql);

                        if(pg_numrows($query) > 0 ){
                            $msg_erro = 'Nº de Série Já Cadastrado';
                            break;
                        }
                        if ($login_fabrica != 120 and $login_fabrica != 201) {
                            if(isset($_POST['garantia_extendida'])){
                                $garantia_extendida = ($_POST['garantia_extendida'] == 't') ? 't' : 'f';
                            }
                            else // HD 711069
                                $garantia_extendida = 'f';
                            /* fim validações */
                        }
                        if(!empty($_POST['num_serie'])) { // faz update
                            $num_serie = $_POST['num_serie'];
                            if ($login_fabrica == 120 or $login_fabrica == 201) {
                                $sql_update = array(
                                    "serie"              => "'{$serie}'",
                                    "referencia_produto" => "'{$referencia}'",
                                    "produto"            => $produto,
                                    "data_fabricacao"    => $x_data_fab,
                                );
                            } else {
                                $sql_update = array(
                                    "serie"              => "'{$serie}'",
                                    "referencia_produto" => "'{$referencia}'",
                                    "data_venda"         => $x_data_venda,
                                    "produto"            => $produto,
                                    "garantia_extendida" => "'{$garantia_extendida}'",
                                    "data_fabricacao"    => $x_data_fab,
                                );
                            }

                            if($login_fabrica == 148){
                                $sql_update['ordem'] = "'$pin'";
                            }

                            $campos_update = array();
                            foreach($sql_update as $key => $value){
                                if(in_array($login_fabrica, [167, 203]) AND $key == 'data_fabricacao'){
                                    continue;
                                }
                                $campos_update[] = "$key = $value";
                            }
                            $sql = "UPDATE tbl_numero_serie
                                    SET ".implode(", ", $campos_update)."
                                    WHERE fabrica = {$login_fabrica}
                                    AND numero_serie = {$num_serie}";
                            $upd = pg_query($con,$sql);
                            $msg_erro .= pg_errormessage($con);
                            echo '<script>window.location=\'?serie='.$_POST['num_serie'].'&msg=Gravado Com Sucesso!\'</script>';
                            break;
                        }
                        if ($login_fabrica == 120 or $login_fabrica == 201) {
                            $sql_insert = array(
                                "fabrica"            => $login_fabrica,
                                "serie"              => "'{$serie}'",
                                "referencia_produto" => "'{$referencia}'",
                                "data_fabricacao"    => $x_data_fab,
                                "produto"            => $produto,
                                "admin"              => $login_admin
                            );
                        } else {
                            $sql_insert = array(
                                "fabrica"            => $login_fabrica,
                                "serie"              => "'{$serie}'",
                                "cnpj"               => "'{$cnpj}'",
                                "referencia_produto" => "'{$referencia}'",
                                "garantia_extendida" => "'{$garantia_extendida}'",
                                "data_venda"         => $x_data_venda,
                                "data_fabricacao"    => $x_data_fab,
                                "produto"            => $produto,
                                "admin"              => $login_admin
                            );
                        }

                        if($login_fabrica == 148){
                            $sql_insert['ordem'] = "'$pin'";
                        }

                        if(in_array($login_fabrica, array(167,176,203))){
                            unset($sql_insert['cnpj']);
                            unset($sql_insert['garantia_extendida']);
                            unset($sql_insert['data_fabricacao']);
                        }

                        $sql = "INSERT INTO tbl_numero_serie
                                (".implode(", ", array_keys($sql_insert)).")
                                VALUES
                                (".implode(", ", $sql_insert).")";
                      
                        $ins = pg_query($con,$sql);
                        $msg_erro .= pg_errormessage($con);
                        if ( strlen($msg_erro) == 0){ // HD 711069
                            $msg = 'Gravado com Sucesso!';
                        }else{
                            $msg_erro = 'Houve um problema ao gravar. Entre em contato com o suporte.';
                        }
                        unset($num_serie, $data_fab, $data_venda, $serie, $cnpj);
                        break;
                    case 'pesquisar':
                        if(empty($produto) && empty($serie)){
                            $erro["campos"][] = "produto_referencia";
                            $erro["campos"][] = "produto_descricao";
                            $msg_erro = "Preencha os campos obrigatórios";
                            break;
                        }
                        if(!empty($produto) AND empty($serie) AND empty($data_venda) AND empty($data_fab) AND empty($cnpj) and !in_array($login_fabrica,array(94,120,201,165,167,175,203))){
                            $msg_erro = "Informe mais campos para a pesquisa";
                            break;
                        }
                        if(!empty($produto)){
                            $cond = ' AND tbl_numero_serie.produto = ' . $produto ;
                        }
                        if(!empty($serie)) {
                            if($login_fabrica == 94) {
                                $cond .= " AND lpad(serie,12,'0') = lpad('$serie',12,'0') ";
                            }else{
                                $cond .= ' AND serie = \''.$serie.'\'';
                            }
                        }
                        if(!empty($cnpj) && $login_fabrica != 120 and $login_fabrica != 201)
                            $cond .= " AND cnpj = '$cnpj'";
                        if(!empty($data_fab)){
                            list($di, $mi, $yi) = explode("/", $data_fab);
                            $aux_data_fab = "$yi-$mi-$di";
                            $cond .= " AND data_fabricacao = '$aux_data_fab'";
                        }
                        if(!empty($data_venda) && $login_fabrica != 120 and $login_fabrica != 201){
                            list($di, $mi, $yi) = explode("/", $data_venda);
                            $aux_data_venda = "$yi-$mi-$di";
                            $cond .= " AND data_venda = '$aux_data_venda'";
                        }
                        if(strlen($msg_erro) == 0){
                            if (isset($novaTelaOs)) {
                                $joinProduto = "JOIN tbl_produto ON (tbl_produto.referencia_pesquisa = upper(referencia_produto) or tbl_produto.referencia = upper(referencia_produto)) and tbl_produto.fabrica_i = {$login_fabrica}";
                            } else {
                                $joinProduto = "JOIN tbl_produto ON tbl_produto.referencia = referencia_produto and tbl_produto.fabrica_i = {$login_fabrica}";
                            }
                            $sql    = "SELECT
                                       referencia_produto, serie, numero_serie, cnpj, TO_CHAR (data_venda,'DD/MM/YYYY') as data_venda, TO_CHAR (data_fabricacao, 'DD/MM/YYYY') as data_fabricacao, descricao,tbl_produto.linha,garantia_extendida, ordem 
                                       FROM tbl_numero_serie
                                       {$joinProduto}
                                       JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
                                       WHERE
                                       tbl_numero_serie.fabrica = $login_fabrica
                                        $cond";
                            $query = pg_query($con, $sql);
                            ob_start();
                            if(pg_numrows($query) == 0) {
                                $msg_info = 'Não foram encontrados resultados para esta pesquisa';
                                $result = ob_get_contents();
                                ob_end_clean();
                                break;
                            }
                        $garantia_ext_texto = (in_array($login_fabrica, array(85,94))) ? '<th>Garantia Est.</th>' : null;
                        echo '<table width="100%" id="TableResult" class="table table-bordered table-hover">
                                <thead>
                                    <tr class="titulo_coluna">';
                                    echo "<th width='15%'>Nº de Série</th>";
                                        echo "<th>Produto</th>";
                                        if(!in_array($login_fabrica, array(167,175,203))){
                                            echo "<th>Fabricação</th>";
                                        }

                                        if (!in_array($login_fabrica, array(120,201, 165))) {
                                            echo "<th>Venda</th>";
                                            echo $garantia_ext_texto;
                                        }

                                        if($login_fabrica == 148){
                                            echo "<th>PIN</th>";
                                        }

                                        if (!in_array($login_fabrica, array(120,201, 165, 167, 169, 175, 203))) {
                                            echo "<th width='15%'>CNPJ Cliente</th>";
                                        } else if($login_fabrica == 169){
                                            echo "<th width='15%'>CNPJ/CPF</th>";
                                        }
                                        echo "<th>Ação</th>";
                                    echo '</tr>
                                </thead>
                                <tbody>';
                        for($i=0; $i<pg_numrows($query);$i++) {
                            $produto        = trim(pg_result ($query,$i,referencia_produto));
			                $produto = (in_array($login_fabrica, array(169,170))) ? str_replace("YY", "-", $produto) : $produto;
                            $serie          = trim(pg_result ($query,$i,serie));
                            $num_serie      = trim(pg_result ($query,$i,numero_serie));
                            $data_fab       = trim(pg_result ($query,$i,data_fabricacao));
                            $prod_descricao = trim(pg_result ($query,$i,descricao));

                            if($login_fabrica == 148){
                                $pin = pg_fetch_result($query, $i, 'ordem');
                            }

                            if (!in_array($login_fabrica, array(120,201, 165))) {
                                $cnpj           = trim(pg_result ($query,$i,cnpj));
                                $data_venda     = trim(pg_result ($query,$i,data_venda));
                                $garantia_ext   = trim(pg_result ($query,$i,garantia_extendida));
                            }
                            if(in_array($login_fabrica, array(85,94))){
                                $garantia_ext_texto = ($garantia_ext == 't') ? '<td class="tac">Sim</td>' : '<td class="tac">Não</td>';
                            }
                            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                            echo '<tr bgcolor="'.$cor.'">';
                                echo ($login_fabrica == 95) ? '<td><a href="javascript: abreSerie('.$num_serie.')">'.$serie.'</a></td>' : '<td>'.$serie.'</td>';
                                echo '<td>'.$produto.' - '.$prod_descricao.'</td>';
                                if(!in_array($login_fabrica, array(167,175,203))){
                                    echo '<td>'.$data_fab.'</td>';
                                }

                                if (!in_array($login_fabrica, array(120,201, 165))) {
                                    echo '<td>'.$data_venda.'&nbsp;</td>';
                                    echo $garantia_ext_texto;
                                }
                                if($login_fabrica == 148){
                                    echo '<td>'.$pin.'&nbsp;</td>';
                                }
                                if (!in_array($login_fabrica, array(120,201, 165, 167, 175, 203))) {
                                    echo '<td>'.$cnpj.'&nbsp;</td>';
                                }
                                echo '<td class="tac">
                                        <button class="btn btn-warning" onclick="window.location=\'?serie='.$num_serie.'\'">Alterar </button>
                                    </td>
                                  </tr>';
                            echo ($login_fabrica == 95) ? '<tr><td id='.$num_serie.' colspan="7"></td></tr>':'';
                        }
                        echo '  </tbody>
                            </table>' . PHP_EOL;
                        /* para nao ficar valor nos campos quando pesquisar novamente */
                        unset($data_fab, $data_venda, $cnpj, $num_serie);
                        $serie = $_POST['serie']; //armazena valor do post, para consulta por num. de serie
                        $result = ob_get_contents();
                        ob_end_clean();
                        break;
                    }
                }
            }
        }
        if (isset($msg_info))
            $display_msg = '<div id="msg" class="alert alert-alert" ><h4>'.$msg_info.'</h4></div>';
    }
    if (isset($msg) || isset($_GET['msg']) ) {
        $msg = isset($msg) ? $msg : $_GET['msg'];
        $display_msg = '<div id="msg" class="alert alert-success"><h4>'.$msg.'</h4></div>';
    }
?>

<?php
if ((is_array($msg_erro) && count($msg_erro)) || (strlen($msg_erro) > 0 && !is_array($msg_erro))) {
?>
    <div id="msg_erro" class="alert alert-error">
        <h4>
        <?php
        if (is_array($msg_erro))
            echo implode("<br />", $msg_erro["msg"]);
        else
            echo $msg_erro;
        ?>
        </h4>
    </div>
    <script type="text/javascript">
        $("#msg_erro").fadeIn();
        setTimeout(function(){
            $('#msg_erro').fadeOut();
        }, 50000);
    </script>
<?php
}
?>

<?php
if(strlen($msg_sucesso) > 0){
    echo "<div id='msg' class='alert alert-success'><h4> {$msg_sucesso} </h4></div>";
}
?>

<script type="text/javascript">
    $("#msg").fadeIn();
    setTimeout(function(){
        $('#msg').fadeOut();
    }, 3000);
    function retorna_produto(data){
        $('#produto_descricao').val(data.descricao);
        $('#produto_referencia').val(data.referencia);
    }
    function abreSerie(serie){
        if ($('#ajax_'+serie).length > 0) {
                $('#'+serie).toggle('');
        }else{
            $.get(
                '<?=$PHP_SELF?>',
                {
                    serie:serie,
                    ajax:'sim'
                },
                function(resposta){
                    $('#'+serie).html(resposta);
                }
            )
        }
    }
    $().ready(function(){
        Shadowbox.init();
        $("span[rel=lupa]").click(function () { $.lupa($(this));});
        $.dataTableLoad({table: "#TableResult"});
        $.dataTableLoad({ table : "#tableSerie" });
        $.datepickerLoad(Array("data_fabricacao", "data_venda"));
        $( "#data_fabricacao" ).mask("99/99/9999");
        $( "#data_venda" ).mask("99/99/9999");
        <? if($login_fabrica != 169) { ?>
            $("#cnpj").mask("99.999.999/9999-99");            
        <?php } if (isset($msg_erro) ){ ?>
            $("#erro").appendTo("#msg").fadeIn("slow");
        <?php } ?>
        <?php
            if (isset($msg) || isset($_GET['msg']) ){
        ?>
            $("#sucesso").appendTo("#msg").fadeIn("slow");
        <?php } ?>

        <?php if ($login_fabrica == 175){  ?>
            $("#data_fabricacao").prev('h5').hide();

            $("#serie").blur(function(){
                if (($("#serie").val() != '') || ($("#produto_referencia").val() != '') || ($("#data_venda").val() != '')){
                    $("#produto_referencia, #produto_descricao, #data_venda, #serie").prev("h5").hide();
                }else{
                    $("#produto_referencia, #produto_descricao, #data_venda, #serie").prev("h5").show();
                }
            });
            $("#data_venda").blur(function(){
                if (($("#serie").val() != '') || ($("#produto_referencia").val() != '') || ($("#data_venda").val() != '')){
                    $("#produto_referencia, #produto_descricao, #serie, #data_venda").prev("h5").hide();
                }else{
                    $("#produto_referencia, #produto_descricao, #serie, #data_venda").prev("h5").show();
                }
            });
            
            $("#produto_referencia, #produto_descricao").blur(function(){
                if (($("#serie").val() != '') || ($("#produto_referencia").val() != '') || ($("#data_venda").val() != '')){
                    $("#produto_referencia, #produto_descricao, #serie, #data_venda").prev("h5").hide();
                }else{
                    $("#produto_referencia, #produto_descricao, #serie, #data_venda").prev("h5").show();
                }
            });
        <?php } ?>
    });
</script>

<?php
if (isset($display_msg) && !empty($display_msg)) {
    echo $display_msg;
}
if(in_array($login_fabrica, array(95,108,111,120,201,146,149,150,154,156,165,167,203))) {
    $layout = array(
        95 => array('Série','Série Peça','Referência Produto','Referência Peça', 'Data Fabricação','Quantidade'),
        108 => array('Série','Referência Produto','Data Fabricação'),
        111 => array('Série','Referência Produto','Data Fabricação'),
        120 => array('Série','Referência Produto','Data Fabricação'),
        146 => array('Série','Referência Produto','Data Fabricação'),
        150 => array('Série Início', 'Série Fim', 'Número Lote', 'Referência Produto', 'Data Fabricação.'),
        154 => array('Série', 'Referência Produto', 'CNPJ'),
        165 => array('Série', 'Referência Produto', 'Data Fabricação (FORMATO AAAA-MM-DD)'),
        167 => array('Série','Referência Produto','Data Venda (FORMATO DD-MM-AAAA)'),
        203 => array('Série','Referência Produto','Data Venda (FORMATO DD-MM-AAAA)')
    )
?>
<div class="row">
    <strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
</div>
<form method="POST" class="tc_formulario" action="<?=$PHP_SELF?>" enctype="multipart/form-data">
    <input type='hidden' name='btn_acao' value=''>
    <div class="titulo_tabela">Envio de Arquivo para Atualização</div>
    <br />
    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span4">
            <div class='control-group <?=(in_array("arquivo", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Anexar Arquivo</label>
                <h5 class='asteristico'>*</h5>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type='file' name='arquivo' size='30'></td>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />
    <p class="tac">
        <input type='button' class="btn" onclick="javascript: loading('show'); if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão'); }" alt="Gravar Formulario" value='Importar'>
    </p>
    <br />
</form>
<div class="alert alert-block alert-warning">
    <?php
        if(in_array($login_fabrica, [167, 203])){
            $titulo_texto = " ** O formato do arquivo deve ser texto(.txt), ter no máximo 2MB e o dados devem ser separados por <strong> ; </strong><br /> ";
        }else{
            $titulo_texto = " ** O formato do arquivo deve ser texto(.txt), ter no máximo 2MB e o dados devem ser separados por <strong>TAB</strong><br /> ";
        }
    ?>
    <?=$titulo_texto?>
    <strong>Layout do arquivo:</strong> <?php echo implode(" | ",$layout[$login_fabrica]); ?>
</div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<div id="msg"></div>
<form class="form-search form-inline tc_formulario" action="<?=$PHP_SELF?>" method="POST" name="frm_num_serie">
    <div class='titulo_tabela '>Informações de Cadastro e Pesquisa</div>
    <br/>
    <input type="hidden" name="num_serie" value="<?=isset($num_serie)?$num_serie : ''?>" />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("serie", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='serie'>Nº de Série</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="serie" name="serie" class='span12' value="<?=isset($serie)? $serie : ''?>" >
                    </div>
                </div>
            </div>
        </div>
        <?php if ($login_fabrica == 176){ ?>
        <div class='span4'>
            <div class='control-group <?=(in_array("cnpj", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='cnpj'>CNPJ do Cliente</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="cnpj" name="cnpj" class='span12' value="<?=isset($xcnpj)?$xcnpj:''?>" >
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php if(in_array($login_fabrica, array(167,175,203))){ ?>
        <div class='span4'>
            <div class='control-group <?=(in_array("dtvenda", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_venda'>Data da Venda</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <?php echo (in_array($login_fabrica, array(85,167,203))) ? "" : "<h5 class='asteristico'>*</h5>"; ?>
                        <input type="text" id="data_venda" name="data_venda" class='span12' value="<?=isset($rec_data_venda)?$rec_data_venda:''?>" >
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        <?php if( !in_array($login_fabrica, array(167,175,176,203))){ ?>
        <div class='span4'>
            <div class='control-group <?=(in_array("dtfabricacao", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_fabricacao'>Data de Fabricação</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <?php if (!in_array($login_fabrica,array(94,120,201,165,167,203))) { ?>
                        <h5 class='asteristico'>*</h5>
                        <?php } ?>
                        <input type="text" id="data_fabricacao" name="data_fabricacao" class='span12' value="<?=isset($rec_data_fab) ? $rec_data_fab :''?>" >
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto_referencia", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Referência</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
			<? if (in_array($login_fabrica, array(169,170)) && !empty($referencia)) {
			    $referencia = str_replace("YY", "-", $referencia);
			} ?>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' value="<?=isset($referencia)?$referencia:''?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span6'>
            <div class='control-group <?=(in_array("produto_descricao", $erro["campos"])) ? "error" : ""?>''>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?=isset($produto_descricao)?$produto_descricao:''?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (!in_array($login_fabrica,array(120,201,165,167,175,176,203))) { ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("dtvenda", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_venda'>Data da Venda</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <?php echo (in_array($login_fabrica, array(85))) ? "" : "<h5 class='asteristico'>*</h5>"; ?>
                        <input type="text" id="data_venda" name="data_venda" class='span12' value="<?=isset($rec_data_venda)?$rec_data_venda:''?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("cnpj", $erro["campos"])) ? "error" : ""?>'>
                <? if($login_fabrica == 169) { ?>
                    <label class='control-label' for='cnpj'>CNPJ / CPF</label>
                <? } else { ?>
                    <label class='control-label' for='cnpj'>CNPJ do Cliente</label>
                <? } ?>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <? if($login_fabrica == 169) { ?>
                            <input type="text" id="cnpj" name="cnpj" class='span12' value="<?=isset($xcnpj)?$xcnpj:''?>" onblur="validaCNPJCPF(this.value);" onclick="limparCNPJCPF(this.value);" >
                        <? } else { ?>
                            <input type="text" id="cnpj" name="cnpj" class='span12' value="<?=isset($xcnpj)?$xcnpj:''?>" >
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if($login_fabrica == 148){ ?>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("dtvenda", $erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_venda'>PIN</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" id="pin" name="pin" class='span12' value="<?=$pin ?>" >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <? } if (!in_array($login_fabrica,array(74,165,167,175,203))) {?>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='cnpj'>Garantia Estendida</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="checkbox" name="garantia_extendida" id="garantia_extendida" class="frm" value="t" <?=($garantia_extendida == 't')?'checked':''?> maxlength="20" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
    <br />
    <div class="row-fluid" style="text-align: center;">
        <button class='btn' type="submit" name="gravar">Gravar</button>
        <button class='btn btn-info' type="submit" name="pesquisar">Pesquisar</button>
    </div>
</form>
<br />
<?php if(in_array($login_fabrica, array(85, 158))){ ?>

    <? if($login_fabrica == 158) : ?>
        <div class="alert alert-warning">
            O arquivo deverá ser do tipo <strong>TXT</strong> ou <strong>CSV</strong> e seguir o seguinte layout em seu conteúdo, sendo <strong>referência do produto;data da fabricação;data da venda;série;CNPJ revenda;ações</strong>, separados por <strong>ponto e virgula (;)</strong>.
            Confira o exemplo abaixo: <br /> <br />
            produto123;01/01/<?php echo date("Y"); ?>;01/02/<?php echo date("Y"); ?>;123321123;48655225000102;inserir  <br />
            produto321;01/02/<?php echo date("Y"); ?>;01/03/<?php echo date("Y"); ?>;321123321;75623528000116;alterar  <br />
        </div>
    <? else : ?>
         <div class="alert alert-warning">
            O arquivo deverá seguir o seguinte layout em seu conteúdo, sendo <strong>referência do produto;data da fabricação;data da venda;série;CNPJ revenda; garantia estendida</strong>, separados por <strong>ponto e virgula (;)</strong>.
            Confira o exemplo abaixo: <br /> <br />
            produto123;01/01/<?php echo date("Y"); ?>;01/02/<?php echo date("Y"); ?>;123321123;48655225000102;sim  <br />
            produto321;01/02/<?php echo date("Y"); ?>;01/03/<?php echo date("Y"); ?>;321123321;75623528000116;nao  <br />
        </div>
    <? endif; ?>
<form name="frm_relatorio" method="post" action="<?php echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data">

    <div class='titulo_tabela '>Upload de Arquivos de Números de Séries</div>
    <input type="hidden" name="upload_arquivo_pecas" value="sim">
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class='span8'>
            <label class='control-label' for='peca_referencia'>Arquivo</label>
            <div class='controls controls-row'>
                <div class='span7 input-append'>
                    <h5 class='asteristico'>*</h5>
                    <input type="file" id="arquivo" name="arquivo" class='span12'>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8 tac">
            <input type="submit" class="btn btn-info" value="Realizar Upload">
        </div>
    </div>
</form>
<?php } ?>
<?php echo isset($result) ?     $result : '';
if (strlen($_POST["btn_acao"]) > 0 && !count($msg_erro["msg"])) {
    $res = $res_upload;
    if(pg_num_rows($res) > 0 && pg_num_rows($res) <= 100) { ?>
        <br />
        <table id="tableSerie" width="100%" class="table table-striped table-bordered table-hover table-fixed">
            <thead>
                <tr class="titulo_coluna" role="row">
                    <th>Série</th>
                    <th>Produto</th>
                    <?php
                    if (!in_array($login_fabrica, array(165,167,203))) {
                    ?>
                    <th>Série Peça</th>
                    <th>Peça</th>
                    <?php } ?>
                    <th>Data Fabricação</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = pg_num_rows($res);
                for($i=0; $i<$count; $i++){
                ?>
                <tr>
                    <td><?=pg_fetch_result($res,$i,'serie')?></td>
                    <td class='tac'><?=pg_fetch_result($res,$i,'referencia_produto')?></td>
                    <?php if (!in_array($login_fabrica, array(165,167,203))) { ?>
                    <td><?=pg_fetch_result($res,$i,'serie_peca')?></td>
                    <td><?=pg_fetch_result($res,$i,'referencia_peca')?></td>
                    <?php } ?>

                    <?php if(in_array($login_fabrica, [167, 203])){ ?>
                            <td class='tac'><?=pg_fetch_result($res,$i,'data_venda')?></td>
                    <?php }else{ ?>
                            <td><?=pg_fetch_result($res,$i,'data_fabricacao')?></td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
<?php
    }
}
?>
<br />

<script>
    function limparCNPJCPF(cnpj){
        $("#cnpj").unmask();
        //$("#cnpj").val('');
    }

    function validaCNPJCPF(cnpj){                                
        let total = cnpj.length;
        if(total == 14){
            $("#cnpj").mask("99.999.999/9999-99");   
            if (cnpj == "00000000000000" || 
                cnpj == "11111111111111" || 
                cnpj == "22222222222222" || 
                cnpj == "33333333333333" || 
                cnpj == "44444444444444" || 
                cnpj == "55555555555555" || 
                cnpj == "66666666666666" || 
                cnpj == "77777777777777" || 
                cnpj == "88888888888888" || 
                cnpj == "99999999999999") {
                    alert('CNPJ inválido');
                    $("#cnpj").unmask();                    
            }
        } else if (total == 11) {
            $("#cnpj").mask("999.999.999-99");
            if (cnpj == "00000000000" || 
                cnpj == "11111111111" || 
                cnpj == "22222222222" || 
                cnpj == "33333333333" || 
                cnpj == "44444444444" || 
                cnpj == "55555555555" || 
                cnpj == "66666666666" || 
                cnpj == "77777777777" || 
                cnpj == "88888888888" || 
                cnpj == "99999999999") {
                    alert('CPF inválido');
                    $("#cnpj").unmask();                    
            }
        } else if((total > 0 && total < 11) || total > 14) {
            alert('CNPJ/CPF inválido');
        }                           
    }
</script>

<?php include 'rodape.php'; ?>
