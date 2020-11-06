<?php

    /**
     * - Rotina de importação de Categorias de mão-de-obra
     * separado por país
     *
     * @date 2016-10-20
     * @author William Ap. Brandino
     */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';
include  ('/var/www/includes/traducao.php');

$admin      = $argv[1];
$fabrica    = 20;

if (!function_exists("traduz")) {
    function traduz($inputText,$con,$cook_idioma_pesquisa,$x_parametros = null)
    {

        global $msg_traducao;
        global $PHP_SELF;

        $cook_idioma_pesquisa = strtolower($cook_idioma_pesquisa);

        if (strlen($cook_idioma_pesquisa)==0){
            $cook_idioma_pesquisa = 'pt-br';
        }

        $mensagem = $msg_traducao[$cook_idioma_pesquisa][$inputText];

        if (strlen($mensagem)==0){
            $mensagem = $msg_traducao['pt-br'][$inputText];
        }

        if (strlen($mensagem)==0){
            $mensagem = $msg_traducao['es'][$inputText];
        }

        if (strlen($mensagem)==0){
            $mensagem = $msg_traducao['en-us'][$inputText];
        }

        if ($x_parametros){
            if (!is_array($x_parametros)){
                $x_parametros = explode(",",$x_parametros);
            }
            while ( list($x_variavel,$x_valor) = each($x_parametros)){
                $mensagem = preg_replace('/%/',$x_valor,$mensagem,1);
            }
        }

        return $mensagem;

    }
}

function msgErro($msg = "")
{
    $retorno = '';

    if (!empty($msg)) {
        if (strpos ($msg,"invalid input syntax") > 0 or strpos ($msg,"value too long for type character") > 0) {
            $msg = traduz("erro.favor.verificar.o.layout.do.arquivo.",$con,$cook_idioma);
        }
        if (strpos ($msg,"does not exist") > 0 or strpos ($msg,"syntax error at or near") > 0) {
            $msg = traduz("erro.arquivo.nao.processado.favor.entrar.em.contato.com.o.suporte.da.telecontrol.",$con,$cook_idioma);
        }

        $hoje = date("d/m/Y - H:i:s");
        $retorno = "<p style='color: #ee2222;font-size:16px'>";
        $retorno .= $hoje." / ".$msg;
        $retorno .= "</p>";
    }

    return $retorno;
}

if (!function_exists("fecho")) {
    function fecho($inputText,$con,$cook_idioma,$x_parametros = null){
        echo traduz($inputText,$con,$cook_idioma,$x_parametros);
    };
}

try {
    $sql_admin = "SELECT email FROM tbl_admin WHERE admin = $admin AND fabrica = $fabrica";
    $res_admin = pg_query($con,$sql_admin);

    $vet['dest'][0] = "helpdesk@telecontrol.com.br";
    $vet['dest'][1] = "suporte@telecontrol.com.br";
    $vet['dest'][2] = "robson.gastao@br.bosch.com";
    $vet['fabrica'] = "bosch";
    if (pg_num_rows($res_admin) > 0) {
        $email_admin = pg_result($res_admin,0,"email");
        $vet['dest'][5] = "$email_admin";
    } else {
        echo "<script>alert('".traduz("seu.usuario.nao.possui.e.mail.cadastrado.por.favor.cadastre.um.e.mail",$con,$cook_idioma)."');</script>";
    }

    $vet['log']     = 2;

    $data       = date('Y-m-d-H');
    $arquivos = "/tmp";
    $origem   = "/tmp/bosch/";
    $arq = $origem."categoria-mao-obra.txt";
    $msg_erro = "";

    if (file_exists($arq) && (filesize($arq) > 0)) {
        $sql = "
            CREATE TEMP TABLE tmp_bosch_categoria (
                categoria text,
                pais text,
                valor text
            )
        ";
        $res = pg_query($con,$sql);

        if (pg_last_error($con)) {
            Log::log2($vet, pg_last_error());
            throw new Exception(pg_last_error());
        }

        $conteudo       = file_get_contents($arq);
        $conteudo_array = explode("\n",$conteudo);

        foreach ($conteudo_array as $linha) {

            if (empty($linha)) {
                continue;
            }

            $linha_vf = explode("\t",$linha);
            $linha_ct = count($linha_vf);

            if ($linha_ct == 3) {
                list($categoria,$pais,$valor) = explode("\t",$linha);
                $categoria  = trim($categoria);
                $pais       = trim($pais);
                $valor      = trim($valor);

                $sqlIns = "
                    INSERT INTO tmp_bosch_categoria (
                        categoria,
                        pais,
                        valor
                    ) VALUES (
                        '$categoria',
                        '$pais',
                        '$valor'
                    )
                ";
                $resIns = pg_query($con,$sqlIns);

                if (pg_last_error($con)) {
                    $msg_erro = msgErro(pg_last_error($con));
                    Log::log2($vet, $msg_erro);
                    throw new Exception($msg_erro);
                    break;
                }
            }
        }
    }

    if (empty($msg_erro)) {
        /*
         * - Valores da tabela temporária
         * pós coleta de arquivo de importação.
         * - Preparo para gravação de dados
         */

        $sqlAll = "
            SELECT  *
            FROM    tmp_bosch_categoria
        ";
        $resAll = pg_query($con,$sqlAll);

        $resultado = pg_fetch_all($resAll);

        foreach ($resultado as $valor) {
            /*
             * - Verificação se categoria
             * já existe na base de dados
             * CASO SIM: Seleciona categoria e prepara gravação de país e valor
             * CASO NÃO: Insere nova categoria e prepara gravação de país e valor,
             */

            pg_query($con,"BEGIN TRANSACTION");

            $sqlVer = "
                SELECT  categoria
                FROM    tbl_categoria
                WHERE   fabrica     = $fabrica
                AND     descricao   ILIKE '".$valor['categoria']."'
            ";
            $resVer = pg_query($con,$sqlVer);

            if (pg_num_rows($resVer) == 0) {
                $sqlVer = "
                    INSERT INTO tbl_categoria (
                        fabrica     ,
                        descricao
                    ) VALUES (
                        $fabrica,
                        '".$valor['categoria']."'
                    ) RETURNING categoria
                ";
                $resVer = pg_query($con,$sqlVer);
            }

            $categoria = pg_fetch_result($resVer,0,categoria);

            /*
             * - Gravar o país e seu valor de mão-de-obra
             * relacionado com a categoria criada / resgatada
             *
             * CASO NÃO HAJA VALOR E PAÍS:              Faz a inserção do valor e país
             * CASO HAJA PAÍS E VALOR SEJA DIFERENTE:   Faz a alteração do valor
             */

            $sqlPais = "
                SELECT  tbl_categoria_pais.mao_de_obra
                FROM    tbl_categoria_pais
                WHERE   tbl_categoria_pais.pais         = '".$valor['pais']."'
                AND     tbl_categoria_pais.categoria    = $categoria
            ";
            $resPais = pg_query($con,$sqlPais);

            if (pg_num_rows($resPais) > 0) {
                $sqlUp = "
                    UPDATE  tbl_categoria_pais
                    SET     mao_de_obra = ".(float)$valor['valor']."
                    WHERE   categoria   = $categoria
                    AND     fabrica     = $fabrica
                    AND     pais        = '".$valor['pais']."'";
            } else {
                $sqlUp = "
                    INSERT INTO tbl_categoria_pais (
                        fabrica                     ,
                        pais                        ,
                        categoria                   ,
                        mao_de_obra
                    ) VALUES (
                        $fabrica                    ,
                        '".$valor['pais']."'        ,
                        $categoria                  ,
                        ".(float)$valor['valor']."
                    )
                ";
            }

            $resUp = pg_query($con,$sqlUp);

            if (pg_last_error($con)) {

                echo pg_last_error($con);
                $msg_erro = msgErro(pg_last_error($con));
                Log::log2($vet, $msg_erro);
                pg_query($con,"ROLLBACK TRANSACTION");
                throw new Exception($msg_erro);
                break;
            }

            pg_query($con,"COMMIT TRANSACTION");
        }
    }

    echo "<script>alert('".traduz("arquivo.importado.com.sucesso.",$con,$cook_idioma)."');</script>";

} catch (Exception $e) {
    $arq_erro = '/tmp/' . $vet['fabrica'] . '/categoria-mao-obra-' . $data . '.erro';
    if (file_exists($arq_erro)) {
        $hoje = date("d/m/Y - H:i:s");
        $msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) ".traduz("erro.na.importacao.de.mao.de.obra.",$con,$cook_idioma)."</h2><br>";
        $msg .= file_get_contents($arq_erro);
        $msg .= "</div>";
        Log::envia_email($vet, "BOSCH - ".traduz("erro.na.importacao.de.mao.de.obra.",$con,$cook_idioma), $msg);
    }
    echo $e->getMessage();
}
