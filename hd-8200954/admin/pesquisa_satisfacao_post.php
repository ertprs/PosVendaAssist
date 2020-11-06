<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['HTTP_REFERER']) > 0 ? true : false;

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
if ($areaAdmin === true) {
    include_once __DIR__.'/autentica_admin.php';
} else {
    include_once __DIR__.'/../autentica_usuario.php';
}


if (!function_exists('valida_data') ) {

    function valida_data($valor) {

        //VALIDADE APENAS PARA SECULO 19 | 20 | 21
        define('RE_DATE', '/^(([0-2]\d|3[01])\W(0[1-9]|1[0-2])\W((19|20|21)\d{2}))$/');

        if (preg_match(RE_DATE, $valor, $a_data)) {

            list($dataehora_inicial, $data_inicial, $did, $dim, $diy) = $a_data;

            if (!checkdate($dim, $did, $diy)) {
                $msg_erro = 'Data inválida!';
            }

        } else {

            $msg_erro = 'Data inválida!';

        }

        return $msg_erro;

    }
}

if ($areaAdmin === true) {
    $campoAdmin = " admin";
    $gravaAdmin = " $login_admin";
} else {
    $campoAdmin = " posto";
    $gravaAdmin = " $login_posto";
}
// exit($campoAdmin);
if (filter_input(INPUT_POST,'sem_resposta')) {

    $pesquisa   = filter_input(INPUT_POST,'pesquisa');
    $hd_chamado = filter_input(INPUT_POST,'hdChamado');
    $os         = filter_input(INPUT_POST,'os');

    $os = (empty($os)) ? "NULL" : $os;
    $hd_chamado = (empty($hd_chamado)) ? "NULL" : $hd_chamado;


    $sqlBuscaPerguntas = " SELECT  pergunta
                                FROM    tbl_pesquisa_pergunta
                                WHERE   pesquisa = $pesquisa
                                ORDER BY      ordem ";
    $resBuscaPerguntas = pg_query($con,$sqlBuscaPerguntas);
    $perguntasSemRespostas = pg_fetch_all_columns($resBuscaPerguntas,0);

    $res = pg_query($con,'BEGIN TRANSACTION');

    foreach($perguntasSemRespostas as $pergunta){
        $sql = " INSERT INTO tbl_resposta (
                        pergunta            ,
                        hd_chamado          ,
                        os                  ,
                        pesquisa            ,
                        data_input          ,
                        sem_resposta        ,
                        $campoAdmin
                    ) VALUES (
                        $pergunta,
                        $hd_chamado,
                        $os,
                        $pesquisa,
                        CURRENT_TIMESTAMP,
                        TRUE                ,
                        $gravaAdmin
                    )";
        $res = pg_query($con,$sql);
        if (pg_last_error($con)){
            $erro[] = pg_last_error($con) ;
        }
    }
    // print_r($erro);exit;
    if(count($erro) > 0){
        $res = pg_query($con,'ROLLBACK TRANSACTION');
        echo "1|$erro";
    }else{
        $res = pg_query($con,'COMMIT TRANSACTION');
        // echo pg_last_error($con);
        echo "0|sucesso";
    }
    exit;
}

if ($_POST['gravaPerguntas'] == 'true') {

    $pesquisa = $_POST['pesquisa'];

    if ($login_fabrica == 51) {//HD 816610

        $arrPerguntas = $_POST['pergunta'];

        $vet_resposta = implode(',', array_keys($_POST['pergunta']));
        $sql_resposta = "
            SELECT  COUNT(*) AS total
            FROM    tbl_pesquisa_pergunta
            JOIN    tbl_pergunta      USING(pergunta)
            JOIN    tbl_tipo_resposta USING(tipo_resposta)
            WHERE   tbl_pesquisa_pergunta.pesquisa = $pesquisa
            AND     tbl_tipo_resposta.ativo        IS TRUE
            AND     tbl_pesquisa_pergunta.pergunta NOT IN($vet_resposta)
        ";

        $res_resposta = pg_query($con, $sql_resposta);
        if (pg_num_rows($res_resposta)) {

            if (pg_result($res_resposta, 0, 'total') > 0) $msg_erro = 'Preencha corretamente a pesquisa!';

        }

    }
    if (in_array($login_fabrica,array(30,35,85,94,129,138,145,161))) {

        $erro           = array();
        $arrayCheckbox  = array();
        $hdChamado      = (!empty($_POST['hdChamado'])) ? $_POST['hdChamado'] : "null";
        $os             = (!empty($_POST['os'])) ? $_POST['os'] : "null";
        $qtde_perg      = $_POST['qtde_perg'];
        $pesquisa       = $_POST['pesquisa'];
        $input          = $_POST['input'];
        $textarea       = $_POST['textarea'];
        $pergunta           = explode("&",$input);
        $perguntaTextarea   = explode("&",$textarea);

        $sql = "SELECT  pergunta            ,
                        txt_resposta        ,
                        tipo_resposta_item
                FROM    tbl_resposta
                WHERE   pesquisa = '$pesquisa'
                AND     os = $os
          ORDER BY      pergunta";
        $resResValida = pg_query($con,$sql);

        if(pg_num_rows($resResValida) > 0){
            $erro = "Pesquisa já respondida";
        }else{

            foreach($pergunta as $key=>$value){
                $dados = explode("=",$value);
                $valores[$dados[0]] = $dados[1];
            }

            foreach($perguntaTextarea as $key=>$value){
                $dadosT = explode("=",$value);
                $valores[$dadosT[0]] = $dadosT[1];
            }

            foreach ($valores as $keyPost => $valuePost) {

                $keyExplode = explode("_",$keyPost);

                if($keyExplode[2]=='checkbox'){
                    $arrayCheckbox[$keyExplode[3]][] = $keyExplode[5];
                }
            }

            if(in_array($login_fabrica,array(129,161))){
                $sqlObrigatorio = " SELECT pergunta_obrigatoria AS obrigatorio
                                    FROM tbl_pesquisa
                                    WHERE pesquisa = $pesquisa
                                    AND fabrica = $login_fabrica
                                    AND ativo IS TRUE ";
                $resObrigatorio = pg_query($con, $sqlObrigatorio);

                if(pg_num_rows($resObrigatorio) > 0){
                    $pesquisa_obrigatoria = pg_fetch_result($resObrigatorio, 0, 'obrigatorio');
                }
            }

            $res = pg_query($con,'BEGIN');

            for ($i=0; $i < $valores['qtde_perg']; $i++) {

                $txt_resposta = ''; //HD-2441661
                $resposta = ''; //HD-2441661

                $pergunta = $valores['perg_'.$i];
                $tipo_resposta = $valores['hidden_'.$i];
                $obrigatorio    = $valores['obrig_'.$i];

                $resposta = (isset($valores['perg_opt'.$pergunta])) ? utf8_decode(trim($valores['perg_opt'.$pergunta])) : '';

                if(empty($resposta) && $obrigatorio == 't' && $tipo_resposta != "checkbox"){
                    $erro['obg'] = "Favor, preencher as respostas obrigatórias";
                }

                if (in_array($tipo_resposta, array('text','range','textarea','date'))) {
                    $txt_resposta = htmlentities(str_replace("+"," ",rawurldecode($resposta)),ENT_QUOTES,'UTF-8');
                    $resposta = 'null';
                }

                if ( $tipo_resposta == 'checkbox') {
                    if(isset($arrayCheckbox[$pergunta])){
                        foreach ($arrayCheckbox[$pergunta] as $value) {
                            $resposta = $value;

                            $sqlItens = "   SELECT  tbl_tipo_resposta_item.descricao
                                            FROM    tbl_tipo_resposta_item
                                            WHERE   tipo_resposta_item = ".$value;

                            $resItens = pg_query($con,$sqlItens);

                            if (pg_num_rows($resItens)>0) {

                                $txt_resposta = pg_fetch_result($resItens,0,0);

                            }else{
                                $txt_resposta = '';
                            }

                            $sql = "INSERT INTO tbl_resposta(
                                        pergunta            ,
                                        hd_chamado          ,
                                        os                  ,
                                        txt_resposta        ,
                                        tipo_resposta_item  ,
                                        pesquisa            ,
                                        data_input          ,
                                        $campoAdmin
                                    )VALUES(
                                        $pergunta           ,
                                        $hdChamado          ,
                                        $os                 ,
                                        '$txt_resposta'     ,
                                        $resposta           ,
                                        '$pesquisa'         ,
                                        current_timestamp   ,
                                        $gravaAdmin
                                    )
                                ";
                            $res = pg_query($con,$sql);
                        }
                        continue ;
                    }else{
                        if($obrigatorio == 't'){
                            $erro['obg'] = "Favor, preencher as respostas obrigatórias";
                        }
                        continue ;
                    }
                }


                if (!empty($resposta) and $resposta != 'null') {

                    $sqlItens = "   SELECT  tbl_tipo_resposta_item.descricao
                                    FROM    tbl_tipo_resposta_item
                                    WHERE   tipo_resposta_item = $resposta";

                    $resItens = pg_query($con,$sqlItens);

                    if (pg_num_rows($resItens)>0) {

                        $txt_resposta = pg_fetch_result($resItens,0,0);

                    }else{
                        $txt_resposta = $resposta;
                    }
                }else{
                    $resposta = 'null';
                }

                $sql = "INSERT INTO tbl_resposta(
                            pergunta            ,
                            hd_chamado          ,
                            os                  ,
                            txt_resposta        ,
                            tipo_resposta_item  ,
                            pesquisa            ,
                            data_input          ,
                            $campoAdmin
                        )VALUES(
                            $pergunta           ,
                            $hdChamado          ,
                            $os                 ,
                            '$txt_resposta'     ,
                            $resposta           ,
                            '$pesquisa'         ,
                            current_timestamp   ,
                            $gravaAdmin
                        )
                        ";
                $res = pg_query($con,$sql);
                if (pg_last_error($con)){
                    $erro[] = pg_last_error($con) ;
                }
            }


            if (count($erro)>0){
                $erro = implode('<br>', $erro);
                if(strpos($erro, 'syntax erro') > 0 ){
                    $erro = "Favor preencher todas as respostas da pesquisa";
                }elseif(strpos($erro,'preencher') AND in_array($login_fabrica,array(129,161))){
                    $erro = "Favor, preencher as respostas obrigatórias";
                }
                $res = pg_query($con,'ROLLBACK TRANSACTION');
            }else{
                $res = pg_query($con,'COMMIT TRANSACTION');
            }
        }
        if ($erro){
            echo "1|$erro";
        }else{
            echo "0|Pesquisa de Satisfação Grava com Sucesso!";
        }
        exit;
    }
}

?>
