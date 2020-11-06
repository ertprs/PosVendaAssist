<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title = "Cadastro de Pesquisa de Satisfação";
$cabecalho = "Cadastro de Pesquisa";
$layout_menu = "cadastro";
$admin_privilegios="cadastro";

require_once 'autentica_admin.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';
include_once '../class/fn_sql_cmd.php';

$LocaisDePesquisa = array(
    'callcenter' => array(
        'if' => !isFabrica(30,152),
        'desc' => 'Callcenter',
    ),
    'externo' => array(
        'if' => isFabrica(1, 24, 74, 85, 94, 129, 145, 161),
        'desc' => array(
              0 => 'E-mail Consumidor',
            // Alguns fabricantes mostram outro "nome" para este ítem
             24 => 'Posto',
             74 => 'Posto',
            129 => 'Callcenter E-mail',
            161 => 'E-mail Callcenter'
        ),
        'sel' => ['externo','posto_2'],
    ),
    'posto' => array(
        'if' => !isFabrica(30, 52, 94),
        'desc' => 'Posto Autorizado',
    ),
    'ordem_de_servico' => array(
        'if' => isFabrica(30, 129, 145, 161),
        'desc' => 'Ordem de Serviço',
    ),
    'ordem_de_servico_email' => array(
        'if' => isFabrica(129, 161),
        'desc' => 'Ordem de Serviço - E-mail',
    ),
);

// Deixa apenas os elementos com "if" === true
$locais = array_filter($locais, function($d) {
    return $d['if'];
});

// ----- Inicio do cadastro ----------
if ( isset($_POST['gravar'] ) ) {

    /* Início das Validações*/

    $msg_erro = null;
    $pesquisa = (int) trim ($_POST['pesquisa']);

    if (in_array($login_fabrica, array(169,170))) {
        $pesquisa_categoria = "fabrica";
    }

    if ( empty($msg_erro) && !empty($pesquisa) ) {
        $sql = "SELECT pesquisa
                FROM tbl_pesquisa
                WHERE pesquisa = $pesquisa
                AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);

        if ( pg_num_rows($res) == 0 ) {
            $msg_erro["msg"][] = 'Pesquisa ' . $pesquisa . ' não Encontrada.';
        }
    }

    $camposPesquisa = [
        'pesquisa_descricao' => [
            'dbfield' => 'descricao',
            'missing' => 'Digite o Título da Pesquisa',
            'valida'  => '/^[ -ÿ]{1,80}$/',
            'invalid' => 'O Título não pode estar vazio nem conter mais de 80 caracteres!',
        ],
        'pesquisa_categoria' => [
            'if' => !isFabrica(52),
            'dbfield' => 'categoria',
            'missing' => 'Escolha o local da pesquisa.',
            'valida'  => array_keys($LocaisDePesquisa),
            'invalid' => 'Selecione um Local de Pesquisa válido!',
        ],
        'pesquisa_ativo' => [
            'dbfield' => 'ativo',
            'missing' => 'Informe se a Pesquisa está Ativa ou Não!',
            'valida'  => ['t','f'],
            'invalid' => 'Informe se a Pesquisa está Ativa ou Não!',
        ],
        'pesquisa_resp_obrigatorio' => [
            'dbfield' => 'resposta_obrigatoria',
            'missing' => 'Informe se a Pesquisa é de preenchimento obrigatório ou não!',
            'valida'  => ['t','f'],
            'invalid' => 'Informe se a Pesquisa é de preenchimento obrigatório ou não!',
        ]
    ];

    foreach ($camposPesquisa as $field => $cond) {
        if (array_key_exists('if', $cond) and $cond['if'] === false) {
             continue;
        }

        if (!array_key_exists($field, $_POST) or strlen($_POST[$field]) === 0) {
            $msg_erro['msg'][] = $cond['missing'];
            $msg_erro['campos'][] = $field;
            continue; // se não existe, não faz sentido continuar... ;-)
        }

        $validacao = $cond['valida'];

        if (is_array($validacao)) {
            if (!in_array($_POST[$field], $$validacao)) {
                $msg_erro['msg'][] = $cond['invalid'];
                $msg_erro['campos'][] = $field;
                continue;
            }
        } else if (is_string($validacao) and ($validacao[0] == $validacao[-1] && ord($validacao[0]) < 48)) {
            if (!preg_match($validacao, $_POST[$field])) {
                $msg_erro['msg'][] = $cond['invalid'];
                $msg_erro['campos'][] = $field;
                continue;
            }
        } // outras validações poderão vir aqui

        // Se não houve erro chegou aqui, o campo pode ser adicionado
        $$field = getPost($field);
        $camposSQL[$cond['dbfield']] = $$field;
    }

    if (count($add['pergunta']) == 0) {
        $msg_erro["msg"][] = "Adicione pelo menos uma pergunta";
    }

    if ($pesquisa_ativo == 't' && !in_array($login_fabrica, [52])) {
        $sql = "SELECT pesquisa, descricao
                  FROM tbl_pesquisa
                 WHERE fabrica = $login_fabrica
                   AND ativo
                   AND categoria = '$pesquisa_categoria'";
        if ($pesquisa)
            $sql .= "\n AND pesquisa <> $pesquisa";

        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
            // Pdem ser usadas para melhorar a mensagem de erro, informando
            // dealhes da pesquisa ativa.
            $pesquisa_ativa  = pg_fetch_result($res, 0, 0);
            $pesquisa_titulo = pg_fetch_result($res, 0, 1);

            if (isFabrica(94)) {
                if ($pesquisa_categoria <> 'callcenter') {
                    $msg_erro["msg"][] = 'Já existe uma pesquisa ativa para Local ' .
                                         ucwords($pesquisa_categoria);
                    $msg_erro["campos"][] = "pesquisa_categoria";
                }
            }else{
                $pesquisa_categoria_desc = getValorFabrica($LocaisDePesquisa[$pesquisa_categoria]['desc']);
                $msg_erro["msg"][] = 'Já existe uma pesquisa ativa para o ' . ucwords($pesquisa_categoria);
                $msg_erro["campos"][] = "pesquisa_categoria";
            }
        }
    }

    $cant_perguntas = count($add['descricao']);
    pre_echo(array_combine(array_keys($add), array_column($add, 0)), "Columna 1");
pre_echo($add, 'Perguntas', true);
    for ($i=0, $num_perg=1; $i < $cant_perguntas; $i++,$num_perg++) {
        /**
         * O formato do array $add é parecido com o $_FILES, ou seja, lógica invertida.
         * Ao extrair os valores para variáveis escalares fica menos confuso, acho.
         */
        $perguntaTipo  = $add['tipo_descricao'][$i] ? : null;
        $perguntaID    = $add['pergunta'][$i] ? : null;
        $perguntaDesc  = $add['descricao'][$i] ? : null;
        $perguntaAjuda = $add['texto_ajuda'][$i] ? : null;
        if($add['tipo_descricao'][$i] == "range" && (strlen(trim($add['inicio'][$i])) == 0 || strlen( trim($add['fim'][$i]) ) == 0 || strlen( trim($add['intervalo'][$i]) ) == 0)){
            $msg_erro["msg"][] = "Complete todos os campos da escala";
            break;

        }else if($add['tipo_descricao'][$i] != "range"){

            $label_inicio    = "null";
            $label_fim       = "null";
            $label_intervalo = "null";

        }else{

            $label_inicio    = str_replace( ',', '.', trim($add['inicio'][$i]) );
            $label_fim       = str_replace( ',', '.', trim($add['fim'][$i]) );
            $label_intervalo = str_replace( ',', '.', trim($add['intervalo'][$i]) );

        }

        $peso = (strlen(trim($add['peso'][$i])) == 0) ? "null" : $add['perg_peso'][$i];



        if ( strlen($add['descricao'][$i]) == 0 ) {
            $msg_erro["msg"][] = "Digite a descrição da Pergunta núm. $num_perg.";
            break;
        } else if (strlen($add['ativo'][$i]) == 0) {
            $msg_erro["msg"][] = "Selecione a situação, ativo ou inativo núm. $num_perg.";
            break;
        } else if (strlen($add['obrigatorio'][$i]) == 0) {
            $msg_erro["msg"][] = "Selecione uma obrigatoriedade da resposta núm. $num_perg.";
            break;
        }

        if (in_array($add['tipo_descricao'][$i], ['radio','checkbox'] ) ) {
            if (count($add['respostas'][$i]) == 0) {
                $msg_erro["msg"][] = "Insira ao menos uma opção de resposta.";
                break;
            }
        }
    }
    /* Fim das Validações*/

    if (empty($msg_erro)) {
        pg_query($con, "BEGIN TRANSACTION");
        if (!empty($pesquisa)) {
            $sql = "UPDATE  tbl_pesquisa SET
                            admin       = $login_admin  ,
                            descricao   = E'".addslashes($pesquisa_descricao)."'  ,
                            ativo       = '$pesquisa_ativo'      ,
                            resposta_obrigatoria = '$pesquisa_resp_obrigatorio',
                            categoria   = E'".addslashes($pesquisa_categoria)."'  ,
                            texto_ajuda = E'".addslashes($pesquisa_texto_ajuda)."'
                        WHERE pesquisa = {$pesquisa}
                        AND fabrica = {$login_fabrica}
                        RETURNING pesquisa;";
        } else {
            $sql = "INSERT INTO tbl_pesquisa (
                            fabrica     ,
                            admin       ,
                            ativo       ,
                            resposta_obrigatoria ,
                            descricao   ,
                            categoria   ,
                            texto_ajuda
                        )VALUES(
                            $login_fabrica  ,
                            $login_admin    ,
                            '$pesquisa_ativo'        ,
                            '$pesquisa_resp_obrigatorio'  ,
                            E'".addslashes($pesquisa_descricao)."'    ,
                            E'".addslashes($pesquisa_categoria)."'    ,
                            E'".addslashes($pesquisa_texto_ajuda)."'
                        )RETURNING pesquisa";
        }

        $res = pg_query($con,$sql);

        if (pg_last_error()) {
            $msg_erro["msg"][] = "Erro ao Inserir a Pesquisa.";
        } else {
            $pesquisa = pg_fetch_result($res,0,0);
        }

        if (empty($msg_erro)) {

            $loop_pergunta = count($add['pergunta_qtde']);

            for ($i=0; $i < $loop_pergunta ; $i++) {

                if (strlen($add['descricao'][$i]) > 80) {
                    $msg_erro['msg'][] = 'Pergunta não pode conter mais que 50 caracteres!';
                    break;
                }

                if (strlen($add['tipo_descricao'][$i]) > 30) {
                    $msg_erro['msg'][] = 'Tipo da pergunta não pode conter mais que 30 caracteres!';
                    break;
                }

                /* Início gravar Tipo Resposta */
                /* Verificar se existe o tipo da resposta */
                if ( !empty($add['tipo_resp'][$i]) ) {

                    $sqlTR = "SELECT    tipo_resposta,
                                        fabrica,
                                        descricao,
                                        tipo_descricao,
                                        label_inicio,
                                        label_fim,
                                        label_intervalo,
                                        ativo,
                                        obrigatorio,
                                        peso
                                FROM  tbl_tipo_resposta
                                WHERE fabrica = {$login_fabrica}
                                    AND tipo_resposta = ".$add['tipo_resp'][$i].";";
                    $resTR = pg_query($con,$sqlTR);

                    if (pg_num_rows($resTR) > 0 ) {

                        $up_descricao = pg_fetch_result($resTR, 0, descricao);
                        $up_tipo_descricao = pg_fetch_result($resTR, 0, tipo_descricao);
                        $up_label_inicio = pg_fetch_result($resTR, 0, label_inicio);
                        $up_label_fim = pg_fetch_result($resTR, 0, label_fim);
                        $up_label_intervalo = pg_fetch_result($resTR, 0, label_intervalo);
                        $up_ativo = pg_fetch_result($resTR, 0, ativo);
                        $up_obrigatorio = pg_fetch_result($resTR, 0, obrigatorio);
                        $up_peso = pg_fetch_result($resTR, 0, peso);
                        $up_tipo_resposta = pg_fetch_result($resTR, 0, tipo_resposta);


                        $upTipoResposta = array();

                        if ( $up_descricao != $add['descricao'][$i]) {
                            $upTipoResposta[] = " descricao = E'".addslashes( substr($add['descricao'][$i],0,50) )."'";
                        }

                        if ( $up_tipo_descricao != $add['tipo_descricao'][$i]) {
                            $upTipoResposta[] = " tipo_descricao = E'".addslashes($add['tipo_descricao'][$i])."'";
                        }

                        if ( $up_label_inicio != $add['inicio'][$i]) {
                            $upTipoResposta[] = " label_inicio = {$add['inicio'][$i]}";
                        }

                        if ( $up_label_fim != $add['fim'][$i]) {
                            $upTipoResposta[] = " label_fim = {$add['fim'][$i]}";
                        }

                        if ( $up_label_intervalo != $add['intervalo'][$i]) {
                            $upTipoResposta[] = " label_intervalo = {$add['intervalo'][$i]}";
                        }

                        if ( $up_obrigatorio != $add['obrigatorio'][$i]) {
                            $upTipoResposta[] = "";
                        }

                        if ( $up_peso != $add['peso'][$i]) {
                            $upTipoResposta[] = " peso = {$add['peso'][$i]}";
                        }

                        if (!empty($upTipoResposta)) {
                            $sqlTR = "UPDATE tbl_tipo_resposta SET ". implode(",", $upTipoResposta)." WHERE tipo_resposta = {$up_tipo_resposta} RETURNING tipo_resposta;";
                        }
                    } else {
                        if ( strlen($add['inicio'][$i]) AND strlen($add['fim'][$i]) AND strlen($add['label_intervalo'][$i]) ) {
                            $insertRangeField = " label_inicio, label_fim, label_intervalo,";
                            $insertRangeValue = " {$add['inicio'][$i]}, {$add['fim'][$i]}, {$add['intervalo'][$i]},";
                        }

                        if (!empty($add['obrigatorio'][$i])) {
                            $insertObrigatorioField = " obrigatorio,";
                            $insertObrigatorioValue = "'{$add['obrigatorio'][$i]}',";
                        }
                        if (!empty($add['peso'][$i])) {
                            $insertPesoField = " peso,";
                            $insertPesoValue = " {$add['peso'][$i]},";
                        }

                        $sqlTR = "INSERT INTO tbl_tipo_resposta
                                    (
                                        fabrica,
                                        descricao,
                                        tipo_descricao,
                                        $insertRangeField
                                        $insertObrigatorioField
                                        $insertPesoField
                                        ativo
                                    ) VALUES (
                                        {$login_fabrica},
                                        E'".addslashes( substr($add['descricao'][$i],0,50) )."',
                                        E'".addslashes($add['tipo_descricao'][$i])."',
                                        $insertRangeValue
                                        $insertObrigatorioValue
                                        $insertPesoValue
                                        't'
                                    ) RETURNING tipo_resposta;";
                    }

                } else {
                    if ( strlen($add['inicio'][$i]) AND strlen($add['fim'][$i]) AND strlen($add['intervalo'][$i]) ) {
                        $insertRangeField = " label_inicio, label_fim, label_intervalo,";
                        $insertRangeValue = " {$add['inicio'][$i]}, {$add['fim'][$i]}, {$add['intervalo'][$i]},";
                    }

                    if (!empty($add['obrigatorio'][$i])) {
                        $insertObrigatorioField = " obrigatorio,";
                        $insertObrigatorioValue = "'{$add['obrigatorio'][$i]}',";
                    }
                    if (!empty($add['peso'][$i])) {
                        $insertPesoField = " peso,";
                        $insertPesoValue = " {$add['peso'][$i]},";
                    }

                    $sqlTR = "INSERT INTO tbl_tipo_resposta
                                (
                                    fabrica,
                                    descricao,
                                    tipo_descricao,
                                    $insertRangeField
                                    $insertObrigatorioField
                                    $insertPesoField
                                    ativo
                                ) VALUES (
                                    {$login_fabrica},
                                    E'".addslashes( substr($add['descricao'][$i],0,50) )."',
                                    E'".addslashes($add['tipo_descricao'][$i])."',
                                    $insertRangeValue
                                    $insertObrigatorioValue
                                    $insertPesoValue
                                    't'
                                ) RETURNING tipo_resposta;";
                }

                if (empty($msg_erro)) {
                    $resTR = pg_query($con,$sqlTR);
                    if (pg_last_error()) {
                        $msg_erro['msg'][] = "Erro ao inserir Tipo da Resposta!";
                    }
                    $tipo_resposta = pg_fetch_result($resTR, 0, 0);
                }
                /* Fim gravar Tipo Resposta */

                /* Início Gravar Tipo Resposta Item */
                if (empty($msg_erro) AND !empty($tipo_resposta) AND ( $add['tipo_descricao'][$i] == 'radio' OR $add['tipo_descricao'][$i] == 'checkbox' ) ) {

                    $qtde_respostas = count($add['respostas'][$i]);
                    for ($j=0; $j < $qtde_respostas; $j++) {
                        if (!empty($add['respostas_item'][$i][$j])) {

                            $sqlTRIs = "SELECT   tipo_resposta_item,
                                                descricao,
                                                tipo_resposta,
                                                ordem,
                                                peso
                                            FROM tbl_tipo_resposta_item
                                            WHERE tipo_resposta = {$tipo_resposta}
                                                AND tipo_resposta_item = ".$add['respostas_item'][$i][$j].";";
                            $resTRIs = pg_query($con,$sqlTRIs);

                            if (pg_num_rows($resTRIs) > 0) {

                                $up_tipo_resposta_item = pg_fetch_result($resTRIs, 0, tipo_resposta_item );
                                $up_descricao = pg_fetch_result($resTRIs, 0, descricao );
                                $up_tipo_resposta = pg_fetch_result($resTRIs, 0, tipo_resposta );
                                $up_ordem = pg_fetch_result($resTRIs, 0, ordem );
                                $up_peso = pg_fetch_result($resTRIs, 0, peso );

                                $upTipoRespostaItem = array();

                                $upTipoRespostaItem[] = " ordem = {$j}";

                                if ( $up_descricao !== $add['respostas'][$i][$j]) {
                                    $upTipoResposta[] = " descricao = E'".addslashes($add['respostas'][$i][$j])."'";
                                }

                                if ( $up_peso != $add['peso_resposta_item'][$i][$j]) {
                                    $upTipoRespostaItem[] = " peso = {$add['resposta_peso'][$i][$j]}";
                                }

                                // if ( $up_tipo_resposta !== $add['tipo_resp'][$i]) {
                                //     $upTipoRespostaItem[] = " tipo_resposta = {$add['tipo_resp'][$i]}";
                                // }

                                if (!empty($upTipoRespostaItem)) {
                                    $sqlTRI = "UPDATE tbl_tipo_resposta_item SET ". implode(",", $upTipoRespostaItem)."
                                                    WHERE tipo_resposta = {$up_tipo_resposta}
                                                    AND tipo_resposta_item = {$up_tipo_resposta_item}
                                                RETURNING tipo_resposta_item;";
                                }
                            } else {
                                $msg_erro[] = "Resposta não encontrada!";
                            }
                        } else {
                            if (!empty($add['peso_resposta_item'][$i][$j])) {
                                $insertPesoField = " peso,";
                                $insertPesoValue = " {$add['resposta_peso'][$i][$j]},";
                            }

                            $sqlTRI = "INSERT INTO tbl_tipo_resposta_item
                                        (
                                            descricao,
                                            tipo_resposta,
                                            $insertPesoField
                                            ordem
                                        ) VALUES (
                                            E'".addslashes($add['respostas'][$i][$j])."',
                                            {$tipo_resposta},
                                            $insertPesoValue
                                            {$j}
                                        ) RETURNING tipo_resposta_item;";
                        }

                        if (empty($msg_erro)) {
                            $resTRI = pg_query($con,$sqlTRI);
                            if (pg_last_error()) {
                                $msg_erro['msg'][] = "Erro ao inserir Item da Resposta!";
                            }
                            $tipo_resposta_item = pg_fetch_result($resTRI, 0, 0);
                        }
                    }
                }
                /* Fim Gravar Tipo Resposta Item */

                /* Início para gravar Tipo Pergunta */
                if (empty($msg_erro)) {
                    if (!empty($add['tipo_descricao'])) {
                        $tipo_pergunta_desc = $add['tipo_descricao'];
                        $sqlTipoPergunta = "SELECT tipo_pergunta
                                                FROM tbl_tipo_pergunta
                                                WHERE fabrica = {$login_fabrica}
                                                    AND descricao = '{$tipo_pergunta_desc}'
                                                    AND ativo is TRUE ";
                        $resTipoPergunta = pg_query($con,$sqlTipoPergunta);

                        if (pg_num_rows($resTipoPergunta) == 0) {
                            $sqlTipoPergunta = "INSERT INTO tbl_tipo_pergunta
                                        (
                                            fabrica,
                                            ativo,
                                            descricao
                                        ) VALUES (
                                            {$login_fabrica},
                                            't',
                                            '{$tipo_pergunta_desc}'
                                        ) RETURNING tipo_pergunta;";
                            $resTipoPergunta = pg_query($con,$sqlTipoPergunta);
                        }

                        if (pg_last_error()) {
                            $msg_erro['msg'][] = "Erro ao cadastrar o tipo pergunta!";
                        } else {
                            $tipo_pergunta = pg_fetch_result($resTipoPergunta, 0, tipo_pergunta);
                        }
                    }
                }
                /* Fim Gravar Tipo Pergunta */

                /* Início gravar Pergunta */
                if (empty($msg_erro)) {
                    if ( !empty($add['pergunta'][$i]) ) {
                        $pergunta = $add['pergunta'][$i];
                        $sqlP = "SELECT pergunta,
                                        tipo_pergunta,
                                        descricao,
                                        ativo,
                                        fabrica,
                                        tipo_resposta,
                                        ordem,
                                        texto_ajuda
                                    FROM tbl_pergunta
                                    WHERE fabrica = {$login_fabrica}
                                        AND tipo_resposta = {$tipo_resposta}
                                        AND pergunta = ".$add['pergunta'][$i].";";
                        $resP = pg_query($con,$sqlP);

                        if (pg_num_rows($resP) > 0) {

                            $up_pergunta = pg_fetch_result($resP , 0, pergunta);
                            $up_tipo_pergunta = pg_fetch_result($resP , 0, tipo_pergunta);
                            $up_descricao = pg_fetch_result($resP , 0, descricao);
                            $up_ativo = pg_fetch_result($resP , 0, ativo);
                            $up_fabrica = pg_fetch_result($resP , 0, fabrica);
                            $up_tipo_resposta = pg_fetch_result($resP , 0, tipo_resposta);
                            $up_ordem = pg_fetch_result($resP , 0, ordem);
                            $up_texto_ajuda = pg_fetch_result($resP , 0, texto_ajuda);

                            $upPergunta = array();

                            if ( $up_descricao !== $add['descricao'][$i]) {
                                $upPergunta[] = " descricao = E'".addslashes($add['descricao'][$i])."'";
                            }

                            if ( $up_ativo !== $add['ativo'][$i]) {
                                $upPergunta[] = " ativo = E'".addslashes($add['ativo'][$i][$j])."'";
                            }

                            if ( $up_tipo_resposta !== $tipo_resposta) {
                                $upPergunta[] = " tipo_resposta = {$tipo_resposta}";
                            }

                            if ( $up_ordem !== $add['ordem'][$i]) {
                                $upPergunta[] = " ordem = ".$add['ordem'][$i];
                            }

                            if ( $up_texto_ajuda !== $add['texto_ajuda'][$i]) {
                                $upPergunta[] = " texto_ajuda = E'".addslashes($add['texto_ajuda'][$i])."'";
                            }

                            if (!empty($tipo_pergunta)) {
                                $upPergunta[] = " tipo_pergunta = {$tipo_pergunta}";
                            }

                            if (!empty($upPergunta)) {
                                $sqlPerg = "UPDATE tbl_pergunta SET ". implode(",", $upPergunta)."
                                            WHERE fabrica = {$login_fabrica}
                                                AND pergunta = {$up_pergunta}
                                                RETURNING pergunta;";
                            }

                        } else {
                            $msg_erro[] = "Pergunta não encontrada!";
                        }
                    } else {

                        if (!empty($add['descricao'][$i])) {
                            $descricaoField = " descricao,";
                            $descricaoValue = " E'".addslashes($add['descricao'][$i])."',";
                        }

                        if (!empty($add['ativo'][$i])) {
                            $ativoField = " ativo,";
                            $ativoValue = " E'".addslashes($add['ativo'][$i])."',";
                        }

                        if (!empty($tipo_resposta)) {
                            $tipoRespostaField = " tipo_resposta,";
                            $tipoRespostaValue = " $tipo_resposta,";
                        }

                        if (!empty($add['ordem'][$i])) {
                            $ordemField = " ordem,";
                            $ordemValue = " {$add['ordem'][$i]},";
                        }

                        if (!empty($add['texto_ajuda'][$i])) {
                            $textoAjudaField = " texto_ajuda,";
                            $textoAjudaValue = " E'".addslashes($add['texto_ajuda'][$i])."',";
                        }

                        if (!empty($tipo_pergunta)) {
                            $tipoPerguntaField = " tipo_pergunta,";
                            $tipoPerguntaValue = " {$tipo_pergunta},";
                        }

                        $sqlPerg = "INSERT INTO tbl_pergunta
                                    (
                                        {$descricaoField}
                                        {$ativoField}
                                        {$tipoRespostaField}
                                        {$ordemField}
                                        {$textoAjudaField}
                                        {$tipoPerguntaField}
                                        fabrica
                                    ) VALUES (
                                        {$descricaoValue}
                                        {$ativoValue}
                                        {$tipoRespostaValue}
                                        {$ordemValue}
                                        {$textoAjudaValue}
                                        {$tipoPerguntaValue}
                                        {$login_fabrica}
                                    ) RETURNING pergunta;";

                    }

                    if (empty($msg_erro) AND !empty($sqlPerg)) {
                        $resPerg = pg_query($con,$sqlPerg);
                        if (pg_last_error()) {
                            $msg_erro['msg'][] = "Erro ao cadastrar a pergunta!";
                        }
                        $pergunta = pg_fetch_result($resPerg, 0, 0);
                    }
                }

                /*Associar a pesquisa na pergunta*/
                if (empty($msg_erro)) {

                    $sqlPP = "SELECT pesquisa,
                                     pergunta
                                FROM tbl_pesquisa_pergunta
                                WHERE pesquisa = {$pesquisa}
                                    AND pergunta = {$pergunta}; ";
                    $resPP = pg_query($con,$sqlPP);

                    if (pg_num_rows($resPP) == 0 ) {

                        if (!empty($add['ordem'][$i])) {
                            $ordemField = " ordem,";
                            $ordemValue = " {$add['ordem'][$i]},";
                        }

                        $sqlPP = "INSERT INTO tbl_pesquisa_pergunta
                                  (
                                  pesquisa,
                                  $ordemField
                                  pergunta

                                  ) VALUES (
                                  {$pesquisa},
                                  {$ordemValue}
                                  {$pergunta}
                                  );";
                        $resPP = pg_query($con,$sqlPP);

                        if (pg_last_error()) {
                            $msg_erro['msg'][] = "Erro ao relacionar a pesquisa com a pergunta!";
                        }
                    }
                }
            }
        }
    }

    if (empty($msg_erro)) {
        pg_query($con,'COMMIT TRANSACTION');
        $msg = "Pesquisa cadastrada com sucesso!";
        unset($_POST);
    } else {
        pg_query($con,'ROLLBACK TRANSACTION');

        $sqlPE = "SELECT * FROM tbl_pesquisa WHERE pesquisa = {$pesquisa} AND fabrica = {$login_fabrica};";
        $resPE = pg_query($con,$sqlPE);

        if (pg_num_rows($resPE) == 0) {
            unset($pesquisa);
        }
    }
}
// ----- Fim do cadastro ----------

// ----- Inicio do Editar ----------
if (isset($_GET['edit'])) {
    try {
        $pesquisa   = (int) trim ($_GET['edit']);

        if ( empty($msg_erro) && !empty($pesquisa) ) {
            $sql = "SELECT pesquisa
                    FROM tbl_pesquisa
                    WHERE pesquisa = $pesquisa
                    AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);

            if ( pg_num_rows($res) == 0 ) {
                throw new Exception("Pesquisa " . $pesquisa . " não Encontrada.", 1);
            }
        }

        $sqlPesquisa = "SELECT  descricao   ,
                                categoria   ,
                                ativo       ,
                                resposta_obrigatoria ,
                                texto_ajuda
                        FROM tbl_pesquisa
                        WHERE tbl_pesquisa.fabrica = {$login_fabrica}
                            AND tbl_pesquisa.pesquisa = {$pesquisa}
                        ";
        $resPesquisa = pg_query($con,$sqlPesquisa);

        if (pg_num_rows($resPesquisa)) {
            $pesquisa_descricao = pg_fetch_result($resPesquisa, 0, descricao);
            $pesquisa_categoria = pg_fetch_result($resPesquisa, 0, categoria);
            $pesquisa_ativo = pg_fetch_result($resPesquisa, 0, ativo);
            $pesquisa_resp_obrigatorio = pg_fetch_result($resPesquisa, 0, resposta_obrigatoria);
            $pesquisa_texto_ajuda = pg_fetch_result($resPesquisa, 0, texto_ajuda);
        }

    } catch (Exception $e) {
        $msg_erro["msg"][] = $e->getMessage();
    }

}
// ----- Fim do Editar ----------

/* Início excluir Pergunta */
if ( isset($_POST['excluir']) AND isset($_POST['ajax_exclui_pergunta'])) {

    $id_pergunta = (int) $_POST['excluir'];

    if(!empty($id_pergunta)) {

        pg_query($con,"BEGIN TRANSACTION");

        $sql = "SELECT resposta
                    FROM tbl_resposta
                        JOIN tbl_pergunta USING(pergunta)
                    WHERE tbl_pergunta.pergunta = {$id_pergunta}
                        AND tbl_pergunta.fabrica = {$login_fabrica};";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $retorno = array("error" => utf8_encode("Pergunta não pode ser excluída, respostas existentes!"));
            exit(json_encode($retorno));
        } else {
            $sql = "SELECT  tbl_pesquisa_pergunta.pesquisa,
                            tbl_pesquisa_pergunta.pergunta,
                            tbl_pergunta.tipo_pergunta,
                            tbl_pergunta.tipo_resposta
                    FROM tbl_pesquisa_pergunta
                        JOIN tbl_pergunta USING(pergunta)
                    WHERE pergunta = {$id_pergunta}";
            $res = pg_query($con,$sql);

            // Verifica se tem pergunta para deletar as Perguntas
            if (pg_num_rows($res) > 0) {
                for ($i=0; $i < pg_num_rows($res) ; $i++) {
                    $id_pesquisa = pg_fetch_result($res, $i, pesquisa);
                    $id_tipo_pergunta = pg_fetch_result($res, $i, tipo_pergunta);
                    $id_tipo_resposta = pg_fetch_result($res, $i, tipo_resposta);

                    //Deletar o Tipo da Pergunta
                    if (!empty($id_tipo_pergunta)) {
                        $sqlTP = "SELECT tipo_pergunta
                                    FROM tbl_pergunta
                                        JOIN tbl_tipo_pergunta USING(tipo_pergunta)
                                    WHERE tbl_tipo_pergunta.fabrica = {$login_fabrica}
                                        AND tbl_tipo_pergunta.tipo_pergunta = {$id_tipo_pergunta}
                                        AND tbl_pergunta.pergunta != {$id_pergunta};";
                        $resTP = pg_query($con,$sqlTP);

                        if (pg_num_rows($resTP) == 0) {

                            $sqlDell = "UPDATE tbl_pergunta SET tipo_pergunta = NULL WHERE pergunta = {$id_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            $sqlDell = "DELETE FROM tbl_tipo_pergunta WHERE tipo_pergunta = {$id_tipo_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("Tipo de Pergunta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }
                        }
                    }

                    //Deletar o Tipo Resposta
                    if (!empty($id_tipo_resposta)) {
                        $sqlTR = "SELECT tipo_resposta
                                    FROM tbl_tipo_resposta
                                        JOIN tbl_pergunta USING(tipo_resposta)
                                    WHERE tbl_pergunta.fabrica = {$login_fabrica}
                                        AND tbl_tipo_resposta.tipo_resposta = {$id_tipo_resposta}
                                        AND tbl_pergunta.pergunta != {$id_pergunta};";
                        $resTR = pg_query($con,$sqlTR);

                        if (pg_num_rows($resTR) == 0) {
                            $sqlTRI = "SELECT tipo_resposta_item
                                            FROM tbl_tipo_resposta_item
                                                JOIN tbl_tipo_resposta USING(tipo_resposta)
                                            WHERE tbl_tipo_resposta.tipo_resposta = {$id_tipo_resposta}
                                                AND tbl_tipo_resposta.fabrica = {$login_fabrica};";
                            $resTRI = pg_query($con,$sqlTRI);

                            //Deletar o Item da Resposta
                            if (pg_num_rows($resTRI) > 0) {
                                for ($j=0; $j < pg_num_rows($resTRI); $j++) {
                                    $id_tipo_resposta_item = pg_fetch_result($resTRI, $j, tipo_resposta_item);

                                    $sqlDell = "DELETE FROM tbl_tipo_resposta_item WHERE tipo_resposta_item = {$id_tipo_resposta_item};";
                                    $resDell = pg_query($con,$sqlDell);

                                    if (pg_last_error($con)) {
                                        pg_query($con,"ROLLBACK TRANSACTION");
                                        $retorno = array("error" => utf8_encode("O item da resposta não pode ser excluído!"));
                                        exit(json_encode($retorno));
                                    }
                                }
                            }

                            $sqlDell = "UPDATE tbl_pergunta SET tipo_resposta = NULL WHERE pergunta = {$id_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            $sqlDell = "DELETE FROM tbl_tipo_resposta WHERE tipo_resposta = {$id_tipo_resposta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("Resposta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }
                        }
                    }

                    //Deletar a Pergunta
                    if (!empty($id_pergunta)) {
                        $sqlP = "SELECT tbl_pergunta.pergunta
                                    FROM tbl_pergunta
                                        JOIN tbl_pesquisa_pergunta USING(pergunta)
                                    WHERE tbl_pergunta.pergunta = {$id_pergunta}
                                        AND tbl_pesquisa_pergunta.pesquisa != {$id_pesquisa}";
                        $resP = pg_query($con,$sqlP);

                        if (pg_num_rows($resP) == 0) {

                            $sqlDell = "DELETE FROM tbl_pesquisa_pergunta WHERE pergunta = {$id_pergunta} AND pesquisa = {$id_pesquisa};";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("A relação da pesquisa com pergunta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }

                            $sqlDell = "DELETE FROM tbl_pergunta WHERE pergunta = {$id_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("Pergunta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }
                        }
                    }
                }
            }
        }
        //echo pg_last_error();
        if ( pg_last_error($con)) {
            $retorno = array("error" => utf8_encode(pg_last_error($con)));
        } else {
            pg_query($con,"COMMIT TRANSACTION");
            // pg_query($con,"ROLLBACK TRANSACTION");
            $retorno = array("ok" => utf8_encode("Pergunta excluída com sucesso!"));
        }
    }
    exit(json_encode($retorno));
}
/* Fim excluir Pergunta */


// ----- Inicio excluir Pesquisa ----------
if ( isset($_POST['excluir']) AND isset($_POST['ajax_exclui_pesquisa'])) {

    $id_pesquisa = (int) $_POST['excluir'];

    if(!empty($id_pesquisa)) {

        pg_query($con,"BEGIN TRANSACTION");

        $sql = "SELECT resposta
                    FROM tbl_resposta
                        JOIN tbl_pesquisa USING(pesquisa)
                    WHERE tbl_pesquisa.pesquisa = {$id_pesquisa}
                        AND tbl_pesquisa.fabrica = {$login_fabrica};";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $retorno = array("error" => utf8_encode("Pesquisa não pode ser excluída, respostas existentes!"));
            exit(json_encode($retorno));
        } else {
            $sql = "SELECT  tbl_pesquisa_pergunta.pesquisa,
                            tbl_pesquisa_pergunta.pergunta,
                            tbl_pergunta.tipo_pergunta,
                            tbl_pergunta.tipo_resposta
                    FROM tbl_pesquisa_pergunta
                        JOIN tbl_pergunta USING(pergunta)
                    WHERE pesquisa = {$id_pesquisa}";
            $res = pg_query($con,$sql);

            // Verifica se tem pergunta para deletar as Perguntas
            if (pg_num_rows($res) > 0) {
                for ($i=0; $i < pg_num_rows($res) ; $i++) {
                    $id_pergunta = pg_fetch_result($res, $i, pergunta);
                    $id_tipo_pergunta = pg_fetch_result($res, $i, tipo_pergunta);
                    $id_tipo_resposta = pg_fetch_result($res, $i, tipo_resposta);

                    //Deletar o Tipo da Pergunta
                    if (!empty($id_tipo_pergunta)) {
                        $sqlTP = "SELECT tipo_pergunta
                                    FROM tbl_pergunta
                                        JOIN tbl_tipo_pergunta USING(tipo_pergunta)
                                    WHERE tbl_tipo_pergunta.fabrica = {$login_fabrica}
                                        AND tbl_tipo_pergunta.tipo_pergunta = {$id_tipo_pergunta}
                                        AND tbl_pergunta.pergunta != {$id_pergunta};";
                        $resTP = pg_query($con,$sqlTP);

                        if (pg_num_rows($resTP) == 0) {

                            $sqlDell = "UPDATE tbl_pergunta SET tipo_pergunta = NULL WHERE pergunta = {$id_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            $sqlDell = "DELETE FROM tbl_tipo_pergunta WHERE tipo_pergunta = {$id_tipo_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("Tipo de Pergunta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }
                        }
                    }

                    //Deletar o Tipo Resposta
                    if (!empty($id_tipo_resposta)) {
                        $sqlTR = "SELECT tipo_resposta
                                    FROM tbl_tipo_resposta
                                        JOIN tbl_pergunta USING(tipo_resposta)
                                    WHERE tbl_pergunta.fabrica = {$login_fabrica}
                                        AND tbl_tipo_resposta.tipo_resposta = {$id_tipo_resposta}
                                        AND tbl_pergunta.pergunta != {$id_pergunta};";
                        $resTR = pg_query($con,$sqlTR);

                        if (pg_num_rows($resTR) == 0) {
                            $sqlTRI = "SELECT tipo_resposta_item
                                            FROM tbl_tipo_resposta_item
                                                JOIN tbl_tipo_resposta USING(tipo_resposta)
                                            WHERE tbl_tipo_resposta.tipo_resposta = {$id_tipo_resposta}
                                                AND tbl_tipo_resposta.fabrica = {$login_fabrica};";
                            $resTRI = pg_query($con,$sqlTRI);

                            //Deletar o Item da Resposta
                            if (pg_num_rows($resTRI) > 0) {
                                for ($j=0; $j < pg_num_rows($resTRI); $j++) {
                                    $id_tipo_resposta_item = pg_fetch_result($resTRI, $j, tipo_resposta_item);

                                    $sqlDell = "DELETE FROM tbl_tipo_resposta_item WHERE tipo_resposta_item = {$id_tipo_resposta_item};";
                                    $resDell = pg_query($con,$sqlDell);

                                    if (pg_last_error($con)) {
                                        pg_query($con,"ROLLBACK TRANSACTION");
                                        $retorno = array("error" => utf8_encode("O item da resposta não pode ser excluído!"));
                                        exit(json_encode($retorno));
                                    }
                                }
                            }

                            $sqlDell = "UPDATE tbl_pergunta SET tipo_resposta = NULL WHERE pergunta = {$id_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            $sqlDell = "DELETE FROM tbl_tipo_resposta WHERE tipo_resposta = {$id_tipo_resposta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("Resposta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }
                        }
                    }

                    //Deletar a Pergunta
                    if (!empty($id_pergunta)) {
                        $sqlP = "SELECT tbl_pergunta.pergunta
                                    FROM tbl_pergunta
                                        JOIN tbl_pesquisa_pergunta USING(pergunta)
                                    WHERE tbl_pergunta.pergunta = {$id_pergunta}
                                        AND tbl_pesquisa_pergunta.pesquisa != {$id_pesquisa}";
                        $resP = pg_query($con,$sqlP);

                        if (pg_num_rows($resP) == 0) {

                            $sqlDell = "DELETE FROM tbl_pesquisa_pergunta WHERE pergunta = {$id_pergunta} AND pesquisa = {$id_pesquisa};";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("A relação da pesquisa com pergunta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }

                            $sqlDell = "DELETE FROM tbl_pergunta WHERE pergunta = {$id_pergunta} AND fabrica = {$login_fabrica}";
                            $resDell = pg_query($con,$sqlDell);

                            if (pg_last_error($con)) {
                                pg_query($con,"ROLLBACK TRANSACTION");
                                $retorno = array("error" => utf8_encode("Pergunta não pode ser excluída!"));
                                exit(json_encode($retorno));
                            }
                        }
                    }
                }
            }

            $sqlDell = "DELETE FROM tbl_pesquisa WHERE pesquisa = {$id_pesquisa} AND fabrica = {$login_fabrica};";
            $resDell = pg_query($con,$sqlDell);

            if (pg_last_error($con)) {
                pg_query($con,"ROLLBACK TRANSACTION");
                $retorno = array("error" => utf8_encode("Erro ao excluir a pesquisa!"));
                exit(json_encode($retorno));
            }
        }
        //echo pg_last_error();
        if ( pg_last_error($con)) {
            $retorno = array("error" => utf8_encode(pg_last_error($con)));
        } else {
            pg_query($con,"COMMIT TRANSACTION");
            // pg_query($con,"ROLLBACK TRANSACTION");
            $retorno = array("ok" => utf8_encode("Pesquisa excluída com sucesso!"));
        }

    }
    exit(json_encode($retorno));
}
// ----- Fim do Excluir ----------

$sqlListaTudo = "SELECT pesquisa,
                        descricao,
                        nome_completo AS admin,
                        CASE WHEN tbl_pesquisa.ativo IS TRUE
                             THEN 'Ativo'
                             ELSE 'Inativo'
                        END AS ativo,
                        CASE WHEN tbl_pesquisa.resposta_obrigatoria IS TRUE
                             THEN 'Sim'
                             ELSE 'Não'
                        END AS obrigatorio,
                        categoria,
                        texto_ajuda
                    FROM    tbl_pesquisa
                        JOIN    tbl_admin USING(admin)
                    WHERE   tbl_pesquisa.fabrica = $login_fabrica
                    ORDER BY ativo";

$resListaTudo = pg_query($con,$sqlListaTudo);

include 'cabecalho_new.php';

$plugins = array(
    "dataTable",
    "select2",
    "shadowbox",
);

include 'plugin_loader.php';
?>

<script type="text/javascript">
    function alteraPergunta(id,posicao,hid){
        if (hid == true ) {
            addPerguntaHid(id,posicao,hid);
        } else {
            addPergunta(id,posicao);
        }
    }
    // hid é quando a pergunta ainda nao está gravada e tem que alterar a pergunta
    function addPergunta(id = '', posicao = '', hid = '') {

        $("#carrega_pergunta").html('');

        var url = 'pergunta_pesquisa_satisfacao.php';

        if (id >= 0 && posicao >= 0 && hid == '') {
            url = url + "?edit="+id+"&posicao="+posicao;
        }

        $("#carrega_pergunta").load(url);
    }

    function addPerguntaHid(id = '', posicao = '', hid = '') {

        $("#carrega_pergunta").html('');

        var url = 'pergunta_pesquisa_satisfacao.php';

        if (hid == true && posicao >= 0) {

            var pergunta = new Object();

            pergunta.pergunta_qtde = $("input[name='add[pergunta_qtde]["+posicao+"]'").val();
            pergunta.descricao = $("input[name='add[descricao]["+posicao+"]'").val();
            pergunta.texto_ajuda = $("input[name='add[texto_ajuda]["+posicao+"]'").val();
            pergunta.tipo_descricao = $("input[name='add[tipo_descricao]["+posicao+"]'").val();
            pergunta.obrigatorio = $("input[name='add[obrigatorio]["+posicao+"]'").val();
            pergunta.inicio = $("input[name='add[inicio]["+posicao+"]'").val();
            pergunta.fim = $("input[name='add[fim]["+posicao+"]'").val();
            pergunta.intervalo = $("input[name='add[intervalo]["+posicao+"]'").val();
            pergunta.ativo = $("input[name='add[ativo]["+posicao+"]'").val();
            pergunta.peso = $("input[name='add[peso]["+posicao+"]'").val();
            pergunta.ordem = $("input[name='add[ordem]["+posicao+"]'").val();



            var add_respostas_item = [];
            var add_respostas = [];
            var add_peso = [];
            $("div[id='perg_item_pesquisa_"+posicao+"'] > input[name^='add[respostas]']").each(function(indice){
                add_respostas_item.push( $("input[name='add[respostas_item]["+posicao+"]["+indice+"]']").val() );
                add_respostas.push( $("input[name='add[respostas]["+posicao+"]["+indice+"]']").val() );
                add_peso.push( $("input[name='add[resposta_peso]["+posicao+"]["+indice+"]']").val() );
            });


            pergunta.resposta_item = add_respostas_item;
            pergunta.resposta = add_respostas;
            pergunta.resposta_peso = add_peso;

            pergunta = JSON.stringify(pergunta);
        }

        $.ajax({
            async: false,
            url: url,
            type: "POST",
            data: {
                ajax_editar: true,
                editar: pergunta,
                posicao: posicao
            },
            complete: function(data) {
                $("#carrega_pergunta").html(data.responseText);
            }
        });
    }

    function deletaPesquisa(id){

        if ( confirm("Deseja mesmo excluir essa pesquisa?") ){
            $.ajax({
                async: false,
                url: "<?=$PHP_SELF?>",
                type: "POST",
                data: {
                    ajax_exclui_pesquisa: true,
                    excluir: id
                },beforeSend : function() {
                    $("#loading-block").show();
                    $("#loading").show();
                },
                complete: function(data) {

                    $("#loading-block").hide();
                    $("#loading").hide();

                    data = $.parseJSON(data.responseText);
                    if (data.error) {
                        alert(data.error);
                    } else {
                        alert(data.ok);
                        $("#tr_"+id).remove();
                    }
                }
            });
        } else {
            return false;
        }
    }

    function cancelPerguntaTabela(){
        $("#carrega_pergunta").html('');
    }

    function retorna_pergunta(pergunta) {

        var conti = $("tr[id^=pergunta_]").length;


        var htm_input = '<tr class="count_linha" id="pergunta_'+conti+'">\
                            <td align="center">';

        htm_input +=   '<input type="hidden" value="' + conti + '" name="add[pergunta_qtde]['+conti+']"  />';

        if (pergunta.descricao != '' && pergunta.descricao != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.descricao + '" name="add[descricao]['+conti+']"  />';
        }

        if (pergunta.texto_ajuda != '' && pergunta.texto_ajuda != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.texto_ajuda + '" name="add[texto_ajuda]['+conti+']"  />';
        }

        if (pergunta.tipo_descricao != '' && pergunta.tipo_descricao != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.tipo_descricao + '" name="add[tipo_descricao]['+conti+']"  />';
        }

        if (pergunta.tipo_resp != '' && pergunta.tipo_resp != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.tipo_resp + '" name="add[tipo_resp]['+pergunta.posicao+']"  />';
        }

        if (pergunta.obrigatorio != '' && pergunta.obrigatorio != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.obrigatorio + '" name="add[obrigatorio]['+conti+']"  />';
        }

        if (pergunta.inicio != '' && pergunta.inicio != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.inicio + '" name="add[inicio]['+conti+']"  />';
        }

        if (pergunta.fim != '' && pergunta.fim != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.fim + '" name="add[fim]['+conti+']"  />';
        }

        if (pergunta.intervalo != '' && pergunta.intervalo != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.intervalo + '" name="add[intervalo]['+conti+']"  />';
        }

        if (pergunta.ativo != '' && pergunta.ativo != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.ativo + '" name="add[ativo]['+conti+']"  />';
        }

        if (pergunta.peso != '' && pergunta.peso != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.peso + '" name="add[peso]['+conti+']"  />';
        }

        if (pergunta.resposta.length > 0) {
            htm_input += "<div id='perg_item_pesquisa_"+pergunta.posicao+"' >";
        }

        for (j = 0; j < pergunta.resposta.length; j++) {
            if (pergunta.resposta_item[j] != '' && pergunta.resposta_item[j] != undefined) {
                htm_input += "<input type='hidden' value='"+pergunta.resposta_item[j]+"' name='add[respostas_item]["+conti+"]["+j+"]'  />";
            }
            htm_input += "<input type='hidden' value='"+pergunta.resposta[j]+"' name='add[respostas]["+conti+"]["+j+"]'  />";
            if (pergunta.resposta_peso[j] != '' && pergunta.resposta_peso[j] != undefined) {
                htm_input += "<input type='hidden' value='"+pergunta.resposta_peso[j]+"' name='add[resposta_peso]["+conti+"]["+j+"]'  />";
            }
        }

        if (pergunta.resposta.length > 0) {
            htm_input += "</div>";
        }

        htm_input += pergunta.descricao+"\
                            </td>\
                            <td align='center'>\
                                <input type='text' name='add[ordem]["+conti+"]' class='span1'>\
                            </td>\
                            <td align='center'>\
                            <button type='button' class='btn btn-mini btn-info' onclick='alteraPergunta("+pergunta.id+","+conti+",true)'>Editar</button>\
                                <button type='button' class='btn btn-mini btn-danger' onclick='deletePergunta("+conti+","+pergunta.id+")'>Remover</button>\
                            </td>\
                        </tr>";

        if ( pergunta.descricao  != '' || pergunta.tipo_descricao != '' || pergunta.ativo != '' || pergunta.obrigatorio != '' ) {
            $(htm_input).appendTo("#tabela_perguntas_cadastradas");
        }
        $("#carrega_pergunta").html('');
    }

    function retorna_editpergunta(pergunta) {

        $("#carrega_pergunta").html('');

        var conti = $("tr[id^=pergunta_]").length;


        var htm_input = '<td align="center">';

        htm_input +=   '<input type="hidden" value="' + conti + '" name="add[pergunta_qtde]['+pergunta.posicao+']"  />';

        if (pergunta.id != '' && pergunta.id != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.id + '" name="add[pergunta]['+pergunta.posicao+']"  />';
        }

        if (pergunta.descricao != '' && pergunta.descricao != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.descricao + '" name="add[descricao]['+pergunta.posicao+']"  />';
        }

        if (pergunta.texto_ajuda != '' && pergunta.texto_ajuda != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.texto_ajuda + '" name="add[texto_ajuda]['+pergunta.posicao+']"  />';
        }

        if (pergunta.tipo_descricao != '' && pergunta.tipo_descricao != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.tipo_descricao + '" name="add[tipo_descricao]['+pergunta.posicao+']"  />';
        }

        if (pergunta.tipo_resp != '' && pergunta.tipo_resp != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.tipo_resp + '" name="add[tipo_resp]['+pergunta.posicao+']"  />';
        }

        if (pergunta.obrigatorio != '' && pergunta.obrigatorio != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.obrigatorio + '" name="add[obrigatorio]['+pergunta.posicao+']"  />';
        }

        if (pergunta.inicio != '' && pergunta.inicio != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.inicio + '" name="add[inicio]['+pergunta.posicao+']"  />';
        }

        if (pergunta.fim != '' && pergunta.fim != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.fim + '" name="add[fim]['+pergunta.posicao+']"  />';
        }

        if (pergunta.intervalo != '' && pergunta.intervalo != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.intervalo + '" name="add[intervalo]['+pergunta.posicao+']"  />';
        }

        if (pergunta.ativo != '' && pergunta.ativo != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.ativo + '" name="add[ativo]['+pergunta.posicao+']"  />';
        }

        if (pergunta.peso != '' && pergunta.peso != undefined ) {
            htm_input +=   '<input type="hidden" value="' + pergunta.peso + '" name="add[peso]['+pergunta.posicao+']"  />';
        }

        if (pergunta.resposta.length > 0) {
            htm_input += "<div id='perg_item_pesquisa_"+pergunta.posicao+"' >";
        }

        for (j = 0; j < pergunta.resposta.length; j++) {

            if (pergunta.resposta_item[j] != '' && pergunta.resposta_item[j] != undefined) {
                htm_input += "<input type='hidden' value='"+pergunta.resposta_item[j]+"' name='add[respostas_item]["+pergunta.posicao+"]["+j+"]'  />";
            }
            htm_input += "<input type='hidden' value='"+pergunta.resposta[j]+"' name='add[respostas]["+pergunta.posicao+"]["+j+"]'  />";
            if (pergunta.resposta_peso[j] != '' && pergunta.resposta_peso[j] != undefined) {
                htm_input += "<input type='hidden' value='"+pergunta.resposta_peso[j]+"' name='add[resposta_peso]["+pergunta.posicao+"]["+j+"]'  />";
            }
        }

        if (pergunta.resposta.length > 0) {
            htm_input += "</div>";
        }

        htm_input += pergunta.descricao+"\
                            </td>\
                            <td align='center'>\
                                <input type='text' name='add[ordem]["+pergunta.posicao+"]' value='"+pergunta.ordem+"' class='span1'>\
                            </td>\
                            <td align='center'>\
                                <button type='button' class='btn btn-mini btn-info' onclick='alteraPergunta(\"\","+pergunta.posicao+",true)'>Editar</button>\
                                <button type='button' class='btn btn-mini btn-danger' onclick='deletePergunta("+pergunta.posicao+")'>Remover</button>\
                            </td>";

        if ( pergunta.descricao  != '' || pergunta.tipo_descricao != '' || pergunta.ativo != '' || pergunta.obrigatorio != '' ) {
            $("tr[id=pergunta_"+pergunta.posicao+"]").html(htm_input);
        }
    }

    function deletePergunta(id_linha, id_pergunta) {
        if ( confirm("Deseja mesmo excluir essa pesquisa?") ){
            if (id_pergunta != 'undefined' && id_pergunta != '') {
                $.ajax({
                    async: false,
                    url: "<?=$PHP_SELF?>",
                    type: "POST",
                    data: {
                        ajax_exclui_pergunta: true,
                        excluir: id_pergunta
                    },beforeSend : function() {
                        $("#loading-block").show();
                        $("#loading").show();
                    },
                    complete: function(data) {
                        $("#loading-block").hide();
                        $("#loading").hide();

                        data = $.parseJSON(data.responseText);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            alert(data.ok);
                            $("#pergunta_"+id_linha).remove();
                        }
                    }
                });
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
</script>

<?php
if (count($msg_erro["msg"]) > 0) { ?>
<br />
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} ?>

<?php
if (!empty($msg)) { ?>
    <br />
    <div class="alert alert-success">
        <h4> <? echo $msg;?></h4>
    </div>
<?php
} ?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_cadastro_pesquisa_satisfacao' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
    <input type="hidden" name="pesquisa" value="<?=$pesquisa?>" />
    <div id="div_pesquisa" class="tc_formulario">
        <div class="titulo_tabela">Cadastro Pesquisa</div>
        <br>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("pesquisa_descricao", $msg_erro["campos"])) ? "error" : "" ?>'>
                    <label class='control-label'>Título da Pesquisa</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" name="pesquisa_descricao" id="pesquisa_descricao"  class='span12' value= "<?=$pesquisa_descricao?>" maxlength="80">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <div class="row-fluid">
            <div class="span1"></div>
            <?php
            $spanClass = 'span5';

            if(!in_array($login_fabrica, array(169,170))){ ?>
                <div class="span4">
                    <div class='control-group <?=(in_array("pesquisa_categoria", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class="control-label">Local da Pesquisa</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class='asteristico'>*</h5>
                                <select name="pesquisa_categoria" id="pesquisa_categoria" class="span12">
                                    <option value=""></option>
<?php
                foreach($LocaisDePesquisa as $value => $cond) {
                    if (!$cond['if'])
                        continue;
                    $text = getValorFabrica($cond['desc'], 129);
                    $selIf = $con['sel'] ? : (array)$value;
                    $sel  = in_array($pesquisa_categoria, $selIf) ? ' selected' : '';
                    echo "                                            <option value='$value'$sel>$text</option>\n";
                }
?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                $spanClass = 'span1';
            } ?>
            <div class='span3'>
                <div class='control-group <?=(in_array("pesquisa_ativo", $msg_erro["campos"])) ? "error" : "" ?>'>
                    <label class='control-label'>Ativo</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="pesquisa_ativo" id="pesquisa_ativo" class="span12">
                                <option value=""></option>
                                <option value="t" <?=($pesquisa_ativo=='t') ? 'selected' : ''?>>Ativo</option>
                                <option value="f" <?=($pesquisa_ativo=='f') ? 'selected' : ''?>>Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class='span3'>
                <div class='control-group <?=(in_array("pesquisa_resp_obrigatorio", $msg_erro["campos"])) ? "error" : "" ?>'>
                    <label class='control-label'>Resposta Obrigatória</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <h5 class='asteristico'>*</h5>
                            <select name="pesquisa_resp_obrigatorio" id="pesquisa_resp_obrigatorio" class="span12">
                                <option value=""></option>
                                <option value="t" <?=($pesquisa_resp_obrigatorio=='t') ? 'selected' : ''?>>Sim</option>
                                <option value="f" <?=($pesquisa_resp_obrigatorio=='f') ? 'selected' : ''?>>Não</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="<?=$spanClass;?>"></div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
            <div class='span10'>
                <div class='control-group'>
                    <label class='control-label'>Texto da pesquisa:</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <textarea name="pesquisa_texto_ajuda" value="<?=$pesquisa_texto_ajuda;?>" class="span12" rows="5" ><?=$pesquisa_texto_ajuda;?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span1"></div>
        </div>
        <br>
    </div>
    <br />
    <div id="div_pergunta_add" >
        <table id='tabela_perguntas' class='table table-striped table-bordered table-hover table-fixed'>
            <thead class='titulo_tabela'>
                <tr>
                    <th colspan="100%">Perguntas da Pesquisa</th>
                </tr>
                <tr>
                    <th width="75%">Pergunta</th>
                    <th width="10%" align="center">Ordem</th>
                    <th width="15%" align="center">Ações</th>
                </tr>
            </thead>
            <tbody id='tabela_perguntas_cadastradas'>
                <?php
                if (is_array($_POST['add'])) {
                    for ($i=0; $i < count($_POST['add']['pergunta_qtde']) ; $i++) {

                        $tb_pergunta_qtde = $_POST['add']['pergunta_qtde'][$i];
                        $tb_pergunta = $_POST['add']['pergunta'][$i];
                        $tb_descricao = $_POST['add']['descricao'][$i];
                        $tb_texto_ajuda = $_POST['add']['texto_ajuda'][$i];
                        $tb_tipo_descricao = $_POST['add']['tipo_descricao'][$i];
                        $tb_obrigatorio = $_POST['add']['obrigatorio'][$i];
                        $tb_label_inicio = $_POST['add']['inicio'][$i];
                        $tb_label_fim = $_POST['add']['fim'][$i];
                        $tb_label_intervalo = $_POST['add']['intervalo'][$i];
                        $tb_ativo = $_POST['add']['ativo'][$i];
                        $tb_peso = $_POST['add']['peso'][$i];
                        $tb_respostas = $_POST['add']['respostas'][$i];
                        $tb_ordem = $_POST['add']['ordem'][$i];

                        ?>
                        <tr class="count_linha" id="pergunta_<?=$i?>">
                            <td align="center">
                                <input type="hidden" value="<?=$tb_pergunta_qtde?>" name="add[pergunta_qtde][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_pergunta?>" name="add[pergunta][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_descricao?>" name="add[descricao][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_texto_ajuda?>" name="add[texto_ajuda][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_tipo_descricao?>" name="add[tipo_descricao][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_obrigatorio?>" name="add[obrigatorio][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_label_inicio?>" name="add[inicio][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_label_fim?>" name="add[fim][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_label_intervalo?>" name="add[intervalo][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_ativo?>" name="add[ativo][<?=$i?>]"  />
                                <input type="hidden" value="<?=$tb_peso?>" name="add[peso][<?=$i?>]"  />

                                <div id="perg_item_pesquisa_<?=$i?>" >
                                    <?php
                                    $tot_resposta = count($_POST['add']['respostas'][$i]);
                                    for ($j=0; $j < $tot_resposta ; $j++) {
                                        //$tb_tipo_resposta_item = $_POST['add']['respostas'][$i][$j];
                                        $tb_descricao_resposta_item = $_POST['add']['respostas'][$i][$j];
                                        $tb_peso_resposta_item = $_POST['add']['resposta_peso'][$i][$j];
                                        ?>
                                        <input type='hidden' value='<?=$tb_tipo_resposta_item?>' name='add[respostas_item][<?=$i?>][<?=$j?>]'  />
                                        <input type='hidden' value='<?=$tb_descricao_resposta_item?>' name='add[respostas][<?=$i?>][<?=$j?>]'  />
                                        <input type='hidden' value='<?=$tb_peso_resposta_item?>' name='add[resposta_peso][<?=$i?>][<?=$j?>]'  />
                                    <?php
                                    }?>
                                </div>
                                <?=$tb_descricao?>
                            </td>
                            <td align='center'>
                                <input type='text' value="<?=$tb_ordem;?>" name="add[ordem][<?=$i;?>]" class='span1'>
                            </td>
                            <td align='center'>
                                <button type="button" class='btn btn-mini btn-info' onclick='alteraPergunta("",<?=$i?>,true)'>
                                Editar</button>
                                <button type="button" class='btn btn-mini btn-danger' onclick='deletePergunta(<?=$i;?>,<?=$tb_pergunta?>)'>Remover</button>
                            </td>
                        </tr>
                    <?php
                    }
                } elseif (!empty($pesquisa)) {

                    $sqlPergunta = "SELECT  tbl_pergunta.pergunta,
                                            tbl_pergunta.descricao,
                                            tbl_pergunta.texto_ajuda,
                                            tbl_pergunta.tipo_resposta,
                                            tbl_pergunta.ativo,
                                            tbl_tipo_resposta.obrigatorio,
                                            tbl_tipo_resposta.label_inicio,
                                            tbl_tipo_resposta.label_fim,
                                            tbl_tipo_resposta.label_intervalo,
                                            tbl_tipo_resposta.peso,
                                            tbl_tipo_resposta.tipo_descricao,
                                            tbl_pesquisa_pergunta.ordem
                                        FROM tbl_pesquisa_pergunta
                                            JOIN tbl_pergunta USING(pergunta)
                                            JOIN tbl_tipo_resposta USING(tipo_resposta)
                                        WHERE tbl_pesquisa_pergunta.pesquisa = {$pesquisa}
                                            AND tbl_pergunta.fabrica = {$login_fabrica}";
                    $resPergunta = pg_query($con,$sqlPergunta);

                    if (pg_num_rows($resPergunta) > 0 ) {
                        $pergunta_qtde = pg_num_rows($resPergunta);
                        for ($i=0; $i < $pergunta_qtde; $i++) {
                            $tb_pergunta = pg_fetch_result($resPergunta, $i, pergunta);
                            $tb_descricao = pg_fetch_result($resPergunta, $i, descricao);
                            $tb_texto_ajuda = pg_fetch_result($resPergunta, $i, texto_ajuda);
                            $tb_tipo_resposta = pg_fetch_result($resPergunta, $i, tipo_resposta);
                            $tb_ativo = pg_fetch_result($resPergunta, $i, ativo);

                            $tb_obrigatorio = pg_fetch_result($resPergunta, $i, obrigatorio);
                            $tb_label_inicio = pg_fetch_result($resPergunta, $i, label_inicio);
                            $tb_label_fim = pg_fetch_result($resPergunta, $i, label_fim);
                            $tb_label_intervalo = pg_fetch_result($resPergunta, $i, label_intervalo);
                            $tb_peso = pg_fetch_result($resPergunta, $i, peso);
                            $tb_tipo_descricao = pg_fetch_result($resPergunta, $i, tipo_descricao);

                            $tb_ordem = pg_fetch_result($resPergunta, $i, ordem);?>

                            <tr class="count_linha" id="pergunta_<?=$i?>">
                                <td align="center">
                                    <input type="hidden" value="<?=$pergunta_qtde?>" name="add[pergunta_qtde][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_pergunta?>" name="add[pergunta][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_descricao?>" name="add[descricao][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_texto_ajuda?>" name="add[texto_ajuda][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_tipo_resposta?>" name="add[tipo_resp][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_obrigatorio?>" name="add[obrigatorio][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_label_inicio?>" name="add[inicio][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_label_fim?>" name="add[fim][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_label_intervalo?>" name="add[intervalo][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_ativo?>" name="add[ativo][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_peso?>" name="add[peso][<?=$i?>]"  />
                                    <input type="hidden" value="<?=$tb_tipo_descricao?>" name="add[tipo_descricao][<?=$i?>]"  />

                                    <?php
                                    if (!empty($tb_tipo_resposta)) {
                                        $sqlTipoResp = "SELECT *
                                                    FROM tbl_tipo_resposta_item
                                                    WHERE tipo_resposta = $tb_tipo_resposta";
                                        $resTipoResp = pg_query($con,$sqlTipoResp);

                                        // echo nl2br($sqlTipoResp);

                                        if (pg_num_rows($resTipoResp) > 0) {
                                            for ($j=0; $j < pg_num_rows($resTipoResp); $j++) {
                                                $tb_tipo_resposta_item = pg_result($resTipoResp,$j,'tipo_resposta_item');
                                                $tb_descricao_resposta_item = pg_result($resTipoResp,$j,'descricao');
                                                $tb_peso_resposta_item = pg_result($resTipoResp,$j,'peso');
                                                ?>
                                                <input type='hidden' value='<?=$tb_tipo_resposta_item?>' name='add[respostas_item][<?=$i?>][<?=$j?>]'  />
                                                <input type='hidden' value='<?=$tb_descricao_resposta_item?>' name='add[respostas][<?=$i?>][<?=$j?>]'  />
                                                <input type='hidden' value='<?=$tb_peso_resposta_item?>' name='add[resposta_peso][<?=$i?>][<?=$j?>]'  />
                                            <?php
                                            }
                                        }
                                    }
                                    ?>

                                    <?=$tb_descricao;?>
                                </td>
                                <td align='center'>
                                    <input type='text' value="<?=$tb_ordem;?>" name="add[ordem][<?=$i;?>]" class='span1'>
                                </td>
                                <td align='center'>
                                    <button type="button" class='btn btn-mini btn-info' onclick='alteraPergunta(<?=$tb_pergunta;?>,<?=$i;?>)'>
                                    Editar</button>
                                    <button type="button" class='btn btn-mini btn-danger' onclick='deletePergunta(<?=$i;?>,<?=$tb_pergunta?>)'>Remover</button>
                                </td>
                            </tr>
                        <?php
                        }
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tac" colspan="3">
                        <button type="button" onclick='addPergunta();' class="btn btn-success">Adicionar</button>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <br />
    <div id="carrega_pergunta">
    </div>
    <br />
    <div id="div_salvar" class="tc_formulario">
        <div class="titulo_tabela">&nbsp;</div>
        <br />
        <p class="tac">
            <input type="submit" class="btn" name="gravar" value="Gravar" />
        </p>
        <br />
    </div>
</FORM>

<?php
if (is_resource($resListaTudo)) { ?>
    <table id="tabela_pesquisas" class="table table-striped table-bordered table-hover table-fixed">
        <thead class="titulo_tabela">
            <tr>
                <th colspan="100%">Pesquisas Cadastradas</th>
            </tr>
            <tr class="titulo_coluna">
                <th>Pesquisa</th>
                <th>Responsável</th>
                <th>Ativo</th>
                <th>Obrigatório</th>
                <?php if(!in_array($login_fabrica, array(169,170))){ ?>
                <th>Local da Pesquisa</th>
                <?php } ?>
                <th>Ações</th>
            </tr>
        </thead>
            <tbody>
                <?php
                for ($i=0; $i<pg_numrows($resListaTudo); $i++) {
                    $x_ativo        = pg_fetch_result($resListaTudo,$i,'ativo');
                    $x_obrigatorio  = pg_fetch_result($resListaTudo,$i,'obrigatorio');
                    $img_src        = ($x_ativo == 'Ativo') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";
                    $img_src_obg    = ($x_obrigatorio == 'Sim') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";

                    $id = pg_fetch_result($resListaTudo,$i,'pesquisa');
                    $texto_ajuda = pg_fetch_result($resListaTudo,$i,'texto_ajuda');

                    $categoria = pg_fetch_result($resListaTudo, $i, "categoria");

                    if($login_fabrica == 129){

                        switch ($categoria) {
                            case "callcenter":
                                $categoria_desc = "Callcenter";
                            break;

                            case "externo":
                                $categoria_desc = "Callcenter - E-mail";
                            break;

                            case "posto":
                                $categoria_desc = "Posto Autorizado (Login)";
                            break;

                            case "ordem_de_servico_email":
                                $categoria_desc = "Ordem de Serviço - E-mail";
                            break;

                            case "ordem_de_servico":
                                $categoria_desc = "Ordem de Serviço";
                            break;
                        }

                    }else{

                        switch ($categoria) {
                            case "callcenter":
                                $categoria_desc = "Callcenter";
                            break;

                            case "externo":
                                $categoria_desc = "E-mail Call-Center";
                            break;

                            case "posto":
                                $categoria_desc = (in_array($login_fabrica, [30]))? "Ordem de Serviço": "Posto Autorizado (Login)";
                            break;

                            case "posto_2":
                                $categoria_desc = "posto_2";
                            break;

                            case "ordem_de_servico":
                                $categoria_desc = "Ordem de Serviço";
                            break;

                            case "ordem_de_servico_email":
                                $categoria_desc = "Ordem de Serviço - Email";
                            break;
                        }
                    } ?>

                    <tr id="tr_<?=$id?>">
                        <td><?=pg_fetch_result($resListaTudo,$i,'descricao');?></td>
                        <td><?=pg_fetch_result($resListaTudo,$i,'admin');?></td>
                        <td class="tac"><img src='<?=$img_src;?>' alt=""></td>
                        <td class="tac"><img src='<?=$img_src_obg;?>' alt=""></td>
                        <?php
                        if(!in_array($login_fabrica, array(169,170))){ ?>
                            <td><?=$categoria_desc;?></td>
                        <?php
                        } ?>
                        <td class="tac">
                            <a class="btn btn-mini btn-info" href="cadastro_pesquisa_satisfacao.php?edit=<?=$id?>">Editar</a>
                            <?php
                            $sql = "SELECT * from tbl_resposta WHERE pesquisa = {$id};";
                            $resPesquisaResposta  = pg_fetch_result($con,$sql);

                            if (pg_num_rows($resPesquisaResposta)==0) { ?>
                                <button type="button" class="btn btn-mini btn-danger" onclick="deletaPesquisa('<?=$id?>')">Remover</button>
                            <?php
                            } ?>
                        </td>
                    </tr>
                <?php
                } ?>
            </tbody>
        </table>

    </div>
<?php
}
include "rodape.php";

