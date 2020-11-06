 <?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "funcoes.php";
include '../fn_logoResize.php';

if(filter_input(INPUT_POST, "periodo")){
    $periodo = $_POST['periodo'];
}else{
    $periodo = "3";
}

if($periodo == "3"){
    $tempo_periodo = "6";
}else{
    $tempo_periodo = "3";
}

function verificaDataValida($data){
    if(!empty($data)){
        list($di, $mi, $yi) = explode("/", $data);

        return checkdate($mi,$di,$yi) ? true : false;
    }

    return false;
}

function mascara($val, $mascara){
    $maskared = '';
    $k = 0;
    for($i = 0; $i<=strlen($mascara)-1; $i++){
        if($mascara[$i] == '#'){
            if(isset($val[$k]))
                $maskared .= $val[$k++];
        }else{
            if(isset($mascara[$i]))
                $maskared .= $mascara[$i];
        }
    }
    return $maskared;
}

if (!empty($_POST['bt_periodo']) AND $login_fabrica == 151) {

    //Validação Datas
    if (strlen($_POST["data_inicio"])) {
        $data_inicio = $_POST["data_inicio"];
        $data_inicial = $data_inicio;
        if(empty($data_inicial) OR !verificaDataValida($data_inicial)){
            $msg_erro["campos"][] = "data_inicio";
        }
        $aux_data_inicial   = implode("-", array_reverse(explode("/", $data_inicial)));
    }else{
        $msg_erro["campos"][] = "data_inicio";
    }


    if (strlen($_POST["data_fim"])) {
        $data_fim = $_POST["data_fim"];
        $data_final = $data_fim;
        if(empty($data_final) OR !verificaDataValida($data_final)){
            $msg_erro["campos"][] = "data_fim";
        }
        $aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));
    }else{
         $msg_erro["campos"][] = "data_fim";
    }

    if (count($msg_erro["campos"])>0) {
        $msg_erro["msg"][] = traduz("Informar campo Data. Com intervalo de no máximo 6 meses");
    }

    if (count($msg_erro["msg"])==0) {

        if($aux_data_inicial > $aux_data_final){

            $msg_erro["msg"][] = traduz("Intervalo de Datas Incorreto.");

        }else{

            $sqlX = "SELECT '$aux_data_inicial'::date + interval '6 months' > '$aux_data_final'";
            $resX = pg_query($con,$sqlX);
            $periodo_meses = pg_fetch_result($resX,0,0);

            if($periodo_meses == 'f'){

                $msg_erro["msg"][] = traduz("AS DATAS DEVEM SER NO MÁXIMO 6 MESES");
                $msg_erro["campos"][] = "data_inicio";
                $msg_erro["campos"][] = "data_fim";

            }
        }
    }

    if (count($msg_erro) == 0 ) {

        $aux_data_inicial   = implode("-", array_reverse(explode("/", $data_inicial)));
        $aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));
        $intervalo = "AND tbl_os.data_digitacao between '$aux_data_inicial' and '$aux_data_final'";

        //Posto
        $descricao_posto = $_POST["descricao_posto"];
        $codigo_posto = $_POST['codigo_posto'];
        if(!empty($codigo_posto)) {
            $sql = "SELECT posto FROM tbl_posto_fabrica where fabrica = $login_fabrica and codigo_posto = '$codigo_posto'";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0 ) {
                $posto = pg_fetch_result($res,0,0);
                $condPosto = "AND posto = $posto";
            }else{
                $msg_erro["msg"][] = traduz("Posto inválido!");
                $msg_erro["campos"][] = "codigo_posto";
                $msg_erro["campos"][] = "descricao_posto";
            }
        }else{
            $condPosto = "AND posto NOT IN (6359) ";
        }

        //Produto
        $produto_referencia =  $_POST["produto_referencia"];
        $produto_descricao =  $_POST["produto_descricao"];
        if (!empty($produto_referencia)) {
            $sql_prod = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$produto_referencia'";
            $res_prod = pg_query($con,$sql_prod);
            if (pg_num_rows($res_prod) > 0) {
                $produto = pg_fetch_result($res_prod, 0, produto);
                $condProduto = " AND tbl_os.produto = $produto";
            }else{
                $msg_erro["msg"][] = traduz("Produto não encontrado!");
                $msg_erro["campos"][] = "produto_referencia";
                $msg_erro["campos"][] = "produto_descricao";
            }
        }

        //Peça
        $peca_referencia =  $_POST["peca_referencia"];
        $peca_descricao =  $_POST["peca_descricao"];
        if (!empty($peca_referencia)) {

            $sql_peca = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$peca_referencia';";
            $res_peca = pg_query($con,$sql_peca);

            if (pg_num_rows($res_peca) > 0) {
                $peca = pg_fetch_result($res_peca, 0, peca);

                //$selectPeca = "tbl_os_item.peca ,";

                $joinPeca = "
                JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                                AND tbl_os.fabrica = $login_fabrica
                JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                                AND tbl_os_item.fabrica_i = $login_fabrica";

                $condPeca = " AND tbl_os_item.peca = $peca";
            }else{
                $msg_erro["msg"][] = "Peça não encontrada!";
                $msg_erro["campos"][] = "peca_referencia";
                $msg_erro["campos"][] = "peca_descricao";
            }
        }
        //consumidor
        $nome =  $_POST["nome"];
        $cpf =  $_POST["cpf"];
        if (!empty($cpf) AND !empty($nome)) {
            $selectConsumidor = "tbl_os_item.peca ,";

            $joinConsumidor = "
            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                            AND tbl_os.fabrica = $login_fabrica
            JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                            AND tbl_os_item.fabrica_i = $login_fabrica";

            $condConsumidor = " AND tbl_os_item.peca = $peca";

        }

        //revenda
        $revenda_nome =  $_POST["revenda_nome"];
        $revenda_cnpj =  $_POST["revenda_cnpj"];
        if (!empty($revenda_cnpj)) {
            $revenda_cnpj = str_replace(array(".", ",", "-", "/"), "", $revenda_cnpj);
            $sql_rev = "SELECT  tbl_revenda.revenda,
                                tbl_revenda.nome,
                                tbl_revenda.cnpj
                            FROM tbl_revenda
                                JOIN tbl_cidade USING(cidade)
                            WHERE tbl_revenda.cnpj ~* '^$revenda_cnpj' AND tbl_revenda.cnpj_validado IS TRUE
                            ORDER BY tbl_revenda.nome;";
            $res_rev = pg_query($con,$sql_rev);

            if (pg_num_rows($res_rev) > 0) {
                $rev_cod = pg_fetch_result($res_rev, 0, revenda);

                //$selectRev = "";

                $joinRev = "JOIN tbl_revenda ON tbl_os.revenda = tbl_revenda.revenda";

                $condRev = " AND tbl_revenda.revenda = $rev_cod";


            }else{
                $msg_erro["msg"][] = traduz("Revenda não encontrada!");
                $msg_erro["campos"][] = "revenda_cnpj";
                $msg_erro["campos"][] = "revenda_nome";
            }
        }

        //status OS
        $status_checkpoint =  $_POST["status_checkpoint"];
        if (!empty($status_checkpoint)) {
            $condStatusOs = "AND tbl_os.status_checkpoint = $status_checkpoint";
        }else{
            $condStatusOs = "AND tbl_os.status_checkpoint in (1,2,3,4,8,9)";
        }

        //OS Troca Produto ou Troca Peça
        $os_produto_peca = $_POST["os_produto_peca"];
        if (!empty($os_produto_peca)) {
            // $condOsPP = condição OS Produto ou Peça
            if ($os_produto_peca == 'produto') {
                $joinTroca = "
                JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                                                AND tbl_os.fabrica = $login_fabrica";
                $condOSpp = "";
            } elseif ($os_produto_peca == 'peca') {
                $joinTroca = "
                LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                                                AND tbl_os.fabrica = $login_fabrica";
                $condOSpp =  " AND tbl_os_troca.os is null";
            } else {
                $joinTroca = "";
                $condOSpp = "";
            }
        }

        //OS Consumidor ou Revenda
        $os_revenda_consumidor = $_POST["os_revenda_consumidor"];
        if (!empty($os_revenda_consumidor)) {
            // $condOsRC condição OS Revenda ou Consumidor
            if ($os_revenda_consumidor == 'consumidor') {
                $condOsRC = "AND tbl_os.consumidor_revenda = 'C'";
            } elseif ($os_revenda_consumidor == 'revenda') {
                $condOsRC = "AND tbl_os.consumidor_revenda = 'R'";
            } else {
                $condOsRC = "";
            }
        }

    }else{
        $intervalo = "AND tbl_os.data_digitacao between current_timestamp::date - interval '{$periodo} months' and current_timestamp::date + interval '23:59:59 hours'";
    }

}else if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){

    $data_inicial = $_GET["data_inicial"];
    $data_final = $_GET["data_final"];

    list($ano, $mes, $dia) = explode("-", $data_inicial);
    $inicial_format = $dia."/".$mes."/".$ano;

    list($ano, $mes, $dia) = explode("-", $data_final);
    $final_format = $dia."/".$mes."/".$ano;

    $intervalo = " AND tbl_os.data_abertura BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
                   AND tbl_os.finalizada ISNULL
                   AND tbl_os.data_fechamento ISNULL
                   AND tbl_os.excluida IS NOT TRUE ";
    $condPosto = "AND posto NOT IN (6359) ";
    $condStatusOs = "AND tbl_os.status_checkpoint in (1,2,3,4,8)";

    $joinOsProduto = " INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os ";
    $joinPostoFabrica = " INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} ";

}else{
    $intervalo = "AND tbl_os.data_digitacao between current_timestamp::date - interval '{$periodo} months' and current_timestamp::date + interval '23:59:59 hours'";
    $condPosto = "AND posto NOT IN (6359) ";
    $condStatusOs = "AND tbl_os.status_checkpoint in (1,2,3,4,8,9)";
    if (in_array($login_fabrica, [174])) {
        $condStatusOs = "AND tbl_os.status_checkpoint in (1,2,3,4,8,9,40,41,42,43)";
    }
}

if ($login_fabrica != 151) {
    $codigo_posto = $_POST['codigo_posto'];
    if(!empty($codigo_posto)) {
        $sql = "SELECT posto FROM tbl_posto_fabrica where fabrica = $login_fabrica and codigo_posto = '$codigo_posto'";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0 ) {
            $posto = pg_fetch_result($res,0,0);
        }
    }
}

$title = "DASHBOARD";
$layout_menu = 'gerencia';


$campos_linha = "tbl_linha.linha, tbl_linha.nome AS linha_nome, tbl_os_status.status_os AS status_os,";

$joinProdutoOs = " JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				   JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
				   LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os";

$sql = "SELECT DISTINCT tbl_os.os,
                tbl_os.data_digitacao,
                tbl_os.data_abertura,
                tbl_os.finalizada,
                tbl_status_checkpoint.status_checkpoint,
                tbl_status_checkpoint.descricao,
                $campos_linha
                tbl_os.posto,
                tbl_os.consumidor_estado,
                tbl_posto.nome AS posto_nome,
                tbl_os.status_os_ultimo    AS ultimo_status
                INTO TEMP status_os
        FROM tbl_os
        $joinOsProduto
        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
        $joinPostoFabrica
        $joinProdutoOs
        INNER JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
        $joinRev
        $joinPeca
        $joinTroca
        WHERE tbl_os.fabrica = $login_fabrica
        $condRev
        $condPeca
        $condProduto
        $condStatusOs
        $condOsRC
        $condOSpp
        $intervalo;";

if (empty($msg_erro)) {
    $res = pg_query($con,$sql);
    // echo pg_last_error(); exit;
}

if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){

    $sql = "SELECT dez_dias, vinte_dias,trinta_dias,novanta_dias,mais_dias,descricao, status_checkpoint from (

            SELECT  sum(case when data_abertura::date between current_date - interval '10 days' and current_date then 1 else 0 end) as dez_dias,
                    sum(case when data_abertura::date between current_date - interval '20 days' and current_date - interval '11 days' then 1 else 0 end) as vinte_dias,
                    sum(case when data_abertura::date between current_date - interval '30 days' and current_date - interval '21 days' then 1 else 0 end) as trinta_dias,
                    sum(case when data_abertura::date between current_date - interval '90 days' and current_date - interval '31 days' then 1 else 0 end) as novanta_dias,
                    sum(case when data_abertura::date between current_date - interval '300 days' and current_date - interval '91 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
                FROM    status_os
                WHERE status_checkpoint in (1,2,3,4,8)
                    $condPosto
                GROUP BY      descricao ,  status_checkpoint
        ) x
        ORDER BY status_checkpoint";

    // echo nl2br($sql); exit;

}else{
    if (in_array($login_fabrica, [174])) {
        $condStatusOs2 = " status_checkpoint in (1,2,3,4,8,9,40,41,42,43)";
    }else{
        $condStatusOs2 = " status_checkpoint in (1,2,3,4,8)";
    }
    $sql = "SELECT tres_dias, sete_dias,quinze_dias,vintecinco_dias,mais_dias,descricao, status_checkpoint from (

            SELECT  sum(case when data_digitacao::date between current_date - interval '3 days' and current_date then 1 else 0 end) as tres_dias,
                    sum(case when data_digitacao::date between current_date - interval '7 days' and current_date - interval '4 days' then 1 else 0 end) as sete_dias,
                    sum(case when data_digitacao::date between current_date - interval '15 days' and current_date - interval '8 days' then 1 else 0 end) as quinze_dias,
                    sum(case when data_digitacao::date between current_date - interval '25 days' and current_date - interval '16 days' then 1 else 0 end) as vintecinco_dias,
                    sum(case when data_digitacao::date between current_date - interval '90 days' and current_date - interval '26 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
                FROM    status_os
                WHERE {$condStatusOs2}
                    $condPosto
                GROUP BY      descricao ,  status_checkpoint
            UNION
            SELECT  sum(case when finalizada - data_digitacao::date between '0 day' and '3 days' then 1 else 0 end) as tres_dias,
                    sum(case when finalizada - data_digitacao::date between '3 days 1 second' and '7 days' then 1 else 0 end) as sete_dias,
                    sum(case when finalizada - data_digitacao::date between '7 days 1 second' and '15 days' then 1 else 0 end) as quinze_dias,
                    sum(case when finalizada - data_digitacao::date between '15 days 1 second' and '25 days' then 1 else 0 end) as vintecinco_dias,
                    sum(case when finalizada - data_digitacao::date > '25 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
                FROM status_os
                WHERE status_checkpoint = 9
                    $condPosto
                GROUP BY descricao , status_checkpoint
        ) x
        ORDER BY      status_checkpoint";
}

if (empty($msg_erro)) {
    $res = pg_query($con,$sql);
}
$resultados = array();
for($i=0;$i<pg_num_rows($res);$i++){

    if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){

        $dez            = pg_fetch_result($res,$i,0);
        $vinte          = pg_fetch_result($res,$i,1);
        $trinta         = pg_fetch_result($res,$i,2);
        $noventa        = pg_fetch_result($res,$i,3);
        $mais_dias      = pg_fetch_result($res,$i,4);
        $status         = pg_fetch_result($res,$i,5);
        $total_graf[]   = $tres + $sete + $quinze + $vintecinco + $mais_dias;
        $resultados[]   = "
                        {
                            name: '$status',
                            data: [$dez,$vinte,$trinta,$noventa,$mais_dias]
                        } ";

    }else{

        $tres       = pg_fetch_result($res,$i,0);
        $sete       = pg_fetch_result($res,$i,1);
        $quinze     = pg_fetch_result($res,$i,2);
        $vintecinco = pg_fetch_result($res,$i,3);
        $mais_dias  = pg_fetch_result($res,$i,4);
        $status     = pg_fetch_result($res,$i,5);
        $total_graf[] = $tres + $sete + $quinze + $vintecinco + $mais_dias;
        $resultados[] = "
                        {
                            name: '$status',
                            data: [$tres,$sete,$quinze,$vintecinco,$mais_dias]
                        } ";

    }

}
$resultadosArray = implode(",",$resultados) ;

$sql = "SELECT  sum(case when finalizada isnull then 1 else 0 end) as abertas,
                count(1)
        FROM    status_os";
if($condPosto  != ""){
    $sql .= " WHERE 1=1 {$condPosto } ";
}
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0) {
    $abertas  = (int)pg_fetch_result($res,0,0);
    $total    = (int)pg_fetch_result($res,0,1);
    $fechadas = $total - $abertas;
}

if($login_fabrica == 151){ //hd_chamado=2787856

    $data = date('d-m-Y');
    $sqlDate = "SELECT to_char(('$data'::date) - interval '3 month','DD/MM/YYYY') AS data_pesquisa";
    $resDate = pg_query($con, $sqlDate);
    $data_pesquisa = pg_fetch_result($resDate, 0, 'data_pesquisa');

    $sql_m = "SELECT  sum(case when finalizada is not null then 1 else 0 end) as fechadas_sem_reparo,
                    count(1)
            FROM    status_os ";
    if($condPosto  != ""){
        $sql_m .= " WHERE 1=1 {$condPosto }";
    }
    $sql_m .= "AND status_os.ultimo_status = 240";
    $res_m = pg_query($con,$sql_m);

    if(pg_num_rows($res_m) > 0) {
        $fechadas_sem_reparo  = (int)pg_fetch_result($res_m,0,0);
        $total_sem_reparo     = (int)pg_fetch_result($res_m,0,1);
    }
    $fechadas_geral = $fechadas;

    $fechadas = $fechadas_geral - $fechadas_sem_reparo;

    $dados_pizza = "['Abertas',".$abertas."],
                    ['Fechadas',".$fechadas."],
                    ['Fechadas sem reparo',".$fechadas_sem_reparo."]
                    ";
}else{
    $dados_pizza = "['Abertas',".$abertas."],
                    ['Fechadas',".$fechadas."]
                    ";
}


$sqlLinha = "   SELECT  DISTINCT
                        linha       ,
                        linha_nome
                FROM    status_os
";
$resLinha = pg_query($con,$sqlLinha);

$sql = "SELECT  count(1) AS pedidos,
                SUM(
                    CASE WHEN status_pedido NOT IN (4,14)
                         THEN 1
                         ELSE 0
                    END
                ) AS pendente
        FROM    tbl_pedido
        WHERE   fabrica = $login_fabrica
        AND     data BETWEEN (CURRENT_TIMESTAMP - INTERVAL '3 months') and CURRENT_TIMESTAMP";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
    $pedidos    = (int)pg_fetch_result($res,0,pedidos);
    $pendente   = (int)pg_fetch_result($res,0,pendente);
    $finalizadas = $pedidos - $pendente;
}

$sql = "SELECT  senha_financeiro
        FROM    tbl_posto_fabrica
        WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
        AND     senha_financeiro            IS NOT NULL
        AND     LENGTH(senha_financeiro)    > 0
";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
    $senha_financeiro = pg_fetch_result($res,0,senha_financeiro);
    $esconder = traduz("NAO");
}else{
    $esconder = traduz("NAO");
}

//AJAX
if($_POST['ajax'] == 'sim'){
    $linha   = $_POST['linha'];
    $posto   = $_POST['posto'];
    $periodo = $_POST['periodo'];

    $condicao = "";
    
    if ($login_fabrica == 160 or $replica_einhell) {
        $array_estados = $_POST["estados"];

        if (count($array_estados) > 0) {
            for ($z=0; $z < count($array_estados); $z++) {
                if (strlen($array_estados[$z]) > 0) {
                    $array_estados[$z] = "'" . $array_estados[$z] . "'"; 
                }
            }

            $estados = implode(",", $array_estados);

            if (strlen($estados) > 0) {
                $condicao .= " AND consumidor_estado IN ($estados) ";
            }
        }
    }

    if(strlen($linha) > 0){
        $condicao .= " AND linha = $linha ";
    }

    if(strlen($posto) > 0){
        $condicao .= " AND posto = {$posto} ";
    }else{
        $condicao .= " AND posto NOT IN (6359) ";
    }

    if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){

        $sql = "SELECT sum(case when data_digitacao::date between current_date - interval '10 days' and current_date then 1 else 0 end) as dez_dias,
                    sum(case when data_digitacao::date between current_date - interval '11 days' and current_date - interval '20 days' then 1 else 0 end) as vinte_dias,
                    sum(case when data_digitacao::date between current_date - interval '21 days' and current_date - interval '30 days' then 1 else 0 end) as trinta_dias,
                    sum(case when data_digitacao::date between current_date - interval '31 days' and current_date - interval '90 days' then 1 else 0 end) as novanta_dias,
                    sum(case when data_digitacao::date between current_date - interval '91 days' and current_date - interval '200 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
            FROM    status_os
            WHERE status_checkpoint in (1,2,3,4,8)
                    $condicao
                GROUP BY descricao , status_checkpoint
            /* UNION
            SELECT  sum(case when finalizada - data_digitacao between '0 day' and '10 days' then 1 else 0 end) as dez_dias,
                    sum(case when finalizada - data_digitacao between '11 days 1 second' and '20 days' then 1 else 0 end) as vinte_dias,
                    sum(case when finalizada - data_digitacao between '21 days 1 second' and '30 days' then 1 else 0 end) as trinta_dias,
                    sum(case when finalizada - data_digitacao between '31 days 1 second' and '90 days' then 1 else 0 end) as novanta_dias,
                    sum(case when finalizada - data_digitacao > '90 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
                FROM status_os
                WHERE status_checkpoint = 9
                    $condicao
                GROUP BY descricao , status_checkpoint */
        ) x
        ORDER BY status_checkpoint;";

    }else{

        $sql = "SELECT  sum(case when data_digitacao between current_timestamp - interval '3 days' and current_timestamp then 1 else 0 end) as tres_dias,
                    sum(case when data_digitacao between current_timestamp - interval '7 days' and current_timestamp - interval '4 days' then 1 else 0 end) as sete_dias,
                    sum(case when data_digitacao between current_timestamp - interval '15 days' and current_timestamp - interval '8 days' then 1 else 0 end) as quinze_dias,
                    sum(case when data_digitacao between current_timestamp - interval '25 days' and current_timestamp - interval '16 days' then 1 else 0 end) as vintecinco_dias,
                    sum(case when data_digitacao between current_timestamp - interval '3 months' and current_timestamp - interval '26 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
            FROM    status_os
            WHERE status_checkpoint in (1,2,3,4,8)
                    $condicao
                GROUP BY descricao , status_checkpoint
            UNION
            SELECT  sum(case when finalizada - data_digitacao between '0 day' and '3 days' then 1 else 0 end) as tres_dias,
                    sum(case when finalizada - data_digitacao between '3 days 1 second' and '7 days' then 1 else 0 end) as sete_dias,
                    sum(case when finalizada - data_digitacao between '7 days 1 second' and '15 days' then 1 else 0 end) as quinze_dias,
                    sum(case when finalizada - data_digitacao between '15 days 1 second' and '25 days' then 1 else 0 end) as vintecinco_dias,
                    sum(case when finalizada - data_digitacao > '25 days' then 1 else 0 end) as mais_dias,
                    descricao,
                    status_checkpoint
                FROM status_os
                WHERE status_checkpoint = 9
                    $condicao
                GROUP BY descricao , status_checkpoint
        ORDER BY status_checkpoint;";
    }

    $res = pg_query($con,$sql);
    $resultadosNovos = array();
    $contaRes = pg_num_rows($res);
    for($i=0;$i<$contaRes;$i++){
        $tres               = (int)pg_fetch_result($res,$i,0);
        $sete               = (int)pg_fetch_result($res,$i,1);
        $quinze             = (int)pg_fetch_result($res,$i,2);
        $vintecinco         = (int)pg_fetch_result($res,$i,3);
        $mais_dias          = (int)pg_fetch_result($res,$i,4);
        $status             = pg_fetch_result($res,$i,5);
        $status             = htmlentities($status);
        $resultadosNovos[]  = array("nome" => $status,"data" => array($tres,$sete,$quinze,$vintecinco,$mais_dias));
    }

    $sql = "SELECT  sum(case when finalizada isnull then 1 else 0 end) as abertas,
                    count(1)
            FROM    status_os";


    if($condicao != ""){
        $sql .= " WHERE 1=1 {$condicao} ";
    }

    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0) {
        $abertas    = (int)pg_fetch_result($res,0,0);
        $total      = (int)pg_fetch_result($res,0,1);
        $fechadas   = $total - $abertas;
    }
    $resultadosPizza = array("Abertas" => $abertas,"Fechadas" => $fechadas);
    array_push($resultadosNovos,$resultadosPizza);
    $resultadosArrayNovos = json_encode($resultadosNovos);
    echo $resultadosArrayNovos;
    exit;
}

function trocaMes($valor){
    switch($valor){
        case 1:
            return traduz("Janeiro");
        break;
        case 2:
            return traduz("Fevereiro");
        break;
        case 3:
            return traduz("Março");
        break;
        case 4:
            return traduz("Abril");
        break;
        case 5:
            return traduz("Maio");
        break;
        case 6:
            return traduz("Junho");
        break;
        case 7:
            return traduz("Julho");
        break;
        case 8:
            return traduz("Agosto");
        break;
        case 9:
            return traduz("Setembro");
        break;
        case 10:
            return traduz("Outubro");
        break;
        case 11:
            return traduz("Novembro");
        break;
        case 12:
            return traduz("Dezembro");
        break;
    }
}

$join_conferencia = '';
if ($login_fabrica == '3') {
    $join_conferencia = ' JOIN tbl_extrato_conferencia USING (extrato)
                          JOIN    tbl_extrato_conferencia_item    USING (extrato_conferencia)
                          ';
    $campo_mo = "0";
    $campo_extrato = "tbl_extrato_conferencia_item";
    $campo_data = "data_conferencia";
    $cond = "AND     cancelada           IS NOT TRUE";
}else{
    $campo_mo = "0" ;
    $campo_data = "data_geracao";
    $campo_extrato = "tbl_extrato";
}

if(!empty($posto)) {
    $cond_posto = " and tbl_extrato.posto = $posto ";
}

if($login_fabrica == 50){
    $campo_total_extrato = "tbl_extrato.total";
}else{
    $campo_total_extrato = $campo_extrato.".mao_de_obra";
}

$sql = "SELECT  SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '1 month'  AND current_timestamp                        THEN $campo_total_extrato ELSE $campo_mo END)   AS extrato_hoje         ,
                SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '2 months' AND current_timestamp - INTERVAL '1 month'   THEN $campo_total_extrato ELSE $campo_mo END)   AS extrato_mes          ,
                SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '3 months' AND current_timestamp - INTERVAL '2 months'  THEN $campo_total_extrato ELSE $campo_mo END)   AS extrato_dois_meses   ,
                SUM(CASE WHEN $campo_data BETWEEN current_timestamp - INTERVAL '4 months' AND current_timestamp - INTERVAL '3 months'  THEN $campo_total_extrato ELSE $campo_mo END)   AS extrato_tres_meses   ,
                EXTRACT(month FROM current_timestamp)                                                                                                                                                   AS mes_atual            ,
                EXTRACT(month FROM current_timestamp - INTERVAL '1 months')                                                                                                                             AS mes_primeiro         ,
                EXTRACT(month FROM current_timestamp - INTERVAL '2 months')                                                                                                                             AS mes_segundo          ,
                EXTRACT(month FROM current_timestamp - INTERVAL '3 months')                                                                                                                             AS mes_terceiro
        FROM    tbl_extrato
        $join_conferencia
        WHERE   tbl_extrato.fabrica = $login_fabrica
        $cond
        $cond_posto
";
// echo nl2br($sql); exit;
if (empty($msg_erro)) {
    $res = pg_query($con,$sql);
}

$sqlConf = "
        SELECT  SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '1 month'  AND current_timestamp                        THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_hoje         ,
                SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '2 months' AND current_timestamp - INTERVAL '1 month'   THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_mes          ,
                SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '3 months' AND current_timestamp - INTERVAL '2 months'  THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_dois_meses   ,
                SUM(CASE WHEN data_conferencia BETWEEN current_timestamp - INTERVAL '4 months' AND current_timestamp - INTERVAL '3 months'  THEN tbl_extrato_lancamento.valor ELSE 0 END)   AS lancamento_tres_meses
        FROM    tbl_extrato
        JOIN    tbl_extrato_conferencia USING (extrato)
   LEFT JOIN    tbl_extrato_lancamento  ON  tbl_extrato_lancamento.extrato = tbl_extrato.extrato
                                        AND debito_credito = 'C'
        WHERE   tbl_extrato.fabrica = $login_fabrica
        and     cancelada           IS NOT TRUE
        AND     (
                    tbl_extrato_conferencia.admin IS NOT NULL
                OR  lancamento IN (103,104)
                );
";

$resConf = pg_query($con,$sqlConf);

$extrato_hoje       = (float)pg_fetch_result($res,0,extrato_hoje) + (float)pg_fetch_result($resConf,0,lancamento_hoje);
$extrato_mes        = (float)pg_fetch_result($res,0,extrato_mes) + (float)pg_fetch_result($resConf,0,lancamento_mes);
$extrato_dois_meses = (float)pg_fetch_result($res,0,extrato_dois_meses) + (float)pg_fetch_result($resConf,0,lancamento_dois_meses);
$extrato_tres_meses = (float)pg_fetch_result($res,0,extrato_tres_meses) + (float)pg_fetch_result($resConf,0,lancamento_tres_meses);
$mes_atual          = pg_fetch_result($res,0,mes_atual);
$mes_primeiro       = pg_fetch_result($res,0,mes_primeiro);
$mes_segundo        = pg_fetch_result($res,0,mes_segundo);
$mes_terceiro       = pg_fetch_result($res,0,mes_terceiro);
$extrato_resultado  = ($login_fabrica == 50) ? array($extrato_tres_meses,$extrato_dois_meses,$extrato_mes) : array($extrato_tres_meses,$extrato_dois_meses,$extrato_mes,$extrato_hoje);
$extrato_valor      = json_encode($extrato_resultado);

$atual_mes          = trocaMes($mes_atual);
$primeiro_mes       = trocaMes($mes_primeiro);
$segundo_mes        = trocaMes($mes_segundo);
$terceiro_mes       = trocaMes($mes_terceiro);

$meses = ($login_fabrica == 50) ? "'$terceiro_mes','$segundo_mes','$primeiro_mes'" : "'$terceiro_mes','$segundo_mes','$primeiro_mes','$atual_mes'";

$sqlTipo = "SELECT tbl_posto_fabrica.tipo_posto
            FROM   tbl_posto_fabrica
            AND    fabrica  = $login_fabrica
";
$resTipo = pg_query($con,$sqlTipo);
$tipo_posto = pg_fetch_result($resTipo,0,tipo_posto);

//Comunicados Fabrica
$sql = "SELECT  DISTINCT
                tbl_comunicado.comunicado                           ,
                tbl_comunicado.mensagem                             ,
                to_char(tbl_comunicado.data, 'DD/MM/YYYY') AS data  ,
                tbl_comunicado.tipo                                 ,
                tbl_comunicado.data
        FROM    tbl_comunicado
   LEFT JOIN    tbl_comunicado_produto  ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
        WHERE   tbl_comunicado.fabrica = $login_fabrica
        AND     tbl_comunicado.obrigatorio_site
        AND     (
                    tbl_comunicado.linha IN (
                        SELECT  tbl_linha.linha
                        FROM    tbl_posto_linha
                        JOIN    tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                        WHERE   fabrica = $login_fabrica
                  ORDER BY      linha
                    )
                OR  tbl_comunicado.linha IS NULL
                )
        AND     tbl_comunicado.ativo IS TRUE
  ORDER BY      tbl_comunicado.data DESC
        LIMIT   6
";
$res = pg_query($con,$sql);
for($i=0;$i<pg_num_rows($res);$i++){
    $comunicado             = pg_fetch_result($res,$i,comunicado);
    $comunicado_mensagem    = htmlentities(pg_fetch_result($res,$i,mensagem));
    $comunicado_tipo        = htmlentities(pg_fetch_result($res,$i,tipo));
    $comunicado_data        = pg_fetch_result($res,$i,data);
    $resultadosComunicados[$comunicado] = array("mensagem"=>$comunicado_mensagem,"data"=>$comunicado_data,"tipo"=>$comunicado_tipo);
}
$resultadosArrayComunicados = json_encode($resultadosComunicados);

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "maskedinput",
    "shadowbox",
    "multiselect"
);

include "plugin_loader.php";

?>
<script type="text/javascript" src="plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>
<!-- <script src="js/highcharts_4.1.5.js"></script> -->
<!-- <script src="js/novo_highcharts.js"></script> -->
<!-- Teve que atualizar o plugin para identificar se clicou no grafico de pizza ou no grafico de coluna-->
<script src="js/highcharts_4.2.3.js"></script>
<!-- <script src="js/modules/exporting.js"></script> -->

<script type="text/javascript">
<?
if(strlen($senha_financeiro) > 0){
?>
function senhaFinanceiro(){
    var senha = document.getElementById("senha_financeiro").value;
    if(senha == '<?=$senha_financeiro?>'){
        document.getElementById("senha_extrato").style.display="none";
        document.getElementById("extratos_chart").style.opacity=1;
    }else{
        document.getElementById("msg").style.display="block";
        document.getElementById("msg").style.color="#F00";
        document.getElementById("senha_financeiro").value="";
    }
}
<?
}
?>
function chartOs(){
    $('#os_chart').highcharts({
        chart: {
            borderColor: '#CCC',
            borderWidth: 2,
            type: 'column'
        },
        title: {

            text: '<?=traduz('Totais: ')?>',

            useHTML: true
        },
        subtitle:{
            text: '<?=traduz('Aguardando Analise')?>(<?php echo $total_graf[0]?>), <?=traduz('Aguardando Peças')?>(<?php echo $total_graf[1]?>), <?=traduz('Aguardando Conserto')?>(<?php echo $total_graf[2]?>), <?=traduz('Aguardando Retirada')?>(<?php echo $total_graf[3]?>), <?=traduz('Aguardando Produto')?>(<?php echo $total_graf[4]?>), <?=traduz('Finalizada')?>(<?php echo $total_graf[5]?>) '
        },
        style:{
            visibility:'hidden'
        },
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },

        xAxis: {
            categories: [
                <?php
                if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){
                    $campos_cat = array("'0-10'", "'11-20'", "'> 20'", "'> 30'", "'> 90'", "'Total'");
                }else{
                    $campos_cat = array("'0-3 dias'", "'4-7 dias'", "'8-15 dias'", "'16-25 dias'", "'> 25 dias'", "'Total'");
                }
                echo implode(",", $campos_cat);
                ?>
            ]
        },
        yAxis: {
            minorTickInterval: 'auto',
            minorTickLength: 0,
            min: 0,
            title: {
                text: 'OSs'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px;width:150px">{point.key}</span><table style="width:150px;">',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0" nowrap><b>{point.y} OS</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                dataLabels:{
                    enabled: true,
                    format: '{y}'
                }
            },
            series:{
                cursor: 'pointer',
                point:{
                    events:{
                        click: function(){
                            var data                = this.category;
                            var status              = this.series.name;
                            var status_pie          = this.name;
                            var tipo                = this.series.type;
                            var data_inicial        = new Date();
                            var data_final          = new Date();
                            var linha_id            = $('#linha').val();
                            var status_checkpoint;
                            var dia_inicial;
                            var mes_inicial;
                            var ano_inicial;
                            var dia_final;
                            var mes_final;
                            var ano_final;
                            var os_aberta = '';
                            var tipo_fechada;

                            var intervalo_dia = data;

                            var data_inicial_campo = $("#data_inicio").val();
                            var data_final_campo = $("#data_fim").val();

                            var codigo_posto = $("#codigo_posto").val();
                            var produto_referencia = $("#produto_referencia").val();
                            var peca_referencia = $("#peca_referencia").val();

                            var os_produto_peca = $("input[name='os_produto_peca']:checked").val();
                            var os_revenda_consumidor =$("input[name='os_revenda_consumidor']:checked").val();

                            if(tipo == 'column'){
                                switch(status){
                                    case traduz('Aguardando Analise'):
                                        status_checkpoint = 1;
                                    break;
                                    case traduz('Aguardando Peças'):
                                        status_checkpoint = 2;
                                    break;
                                    case traduz('Aguardando Conserto'):
                                        status_checkpoint = 3;
                                    break;
                                    case traduz('Aguardando Retirada'):
                                        status_checkpoint = 4;
                                    break;
                                    case traduz('Aguardando Produto'):
                                        status_checkpoint = 8;
                                    break;
                                    case traduz('Finalizada'):
                                        status_checkpoint = 9;
                                    break;
                                    case traduz('Aguardando Emissão NF de Entrada'):
                                        status_checkpoint = 40;
                                    break;
                                    case traduz('Aguardando NF Saída '):
                                        status_checkpoint = 41;
                                    break;
                                    case traduz('Produto Liberado para Expedição'):
                                        status_checkpoint = 42;
                                    break;
                                    case traduz('Produto Expedido'):
                                        status_checkpoint = 43;
                                    break;
                                }


                                var inicial_format;
                                var final_format;

                                <?php
                                if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){
                                    $data = array("0-10", "11-20", "> 20", "> 30", "> 90");
                                }else{
                                    $data = array("0-3 dias", "4-7 dias", "8-15 dias", "16-25 dias", "> 25 dias");
                                }
                                ?>

                                if (status_checkpoint != 9) {

                                    switch(data){
                                        case '<?php echo $data[0]; ?>':
                                            // data_inicial.setDate(data_inicial.getDate()-3);
                                            tipo_fechada = 1;
                                        break;

                                        case '<?php echo $data[1]; ?>':
                                            // data_inicial.setDate(data_inicial.getDate()-7);
                                            // data_final.setDate(data_final.getDate()-4);
                                            tipo_fechada = 2;
                                        break;
                                        case '<?php echo $data[2]; ?>':
                                            // data_inicial.setDate(data_inicial.getDate()-15);
                                            // data_final.setDate(data_final.getDate()-8);
                                            tipo_fechada = 3;
                                        break;
                                        case '<?php echo $data[3]; ?>':
                                            // data_inicial.setDate(data_inicial.getDate()-25);
                                            // data_final.setDate(data_final.getDate()-16);
                                            tipo_fechada = 4;
                                        break;
                                        case '<?php echo $data[4]; ?>':
                                            // data_inicial.setDate(data_inicial.getDate()-90);
                                            // data_final.setDate(data_final.getDate()-25);
                                            tipo_fechada = 5;
                                        break;
                                        // case 'Total':
                                        //     if ($("#data_fim").val() != "" ) {
                                        //         data_final = data_final_campo_fim;
                                        //         data_inicial = data_inicial_campo;
                                        //     }else{
                                        //         data_inicial.setDate(data_inicial.getDate()-90);
                                        //     }
                                        //     tipo_fechada = 6;
                                        // break;
                                    }
                                }else{
                                    switch(data){
                                        case '<?php echo $data[0]; ?>':
                                            tipo_fechada = 1;
                                        break;
                                        case '<?php echo $data[1]; ?>':
                                            tipo_fechada = 2;
                                        break;
                                        case '<?php echo $data[2]; ?>':
                                            tipo_fechada = 3;
                                        break;
                                        case '<?php echo $data[3]; ?>':
                                            tipo_fechada = 4;
                                        break;
                                        case '<?php echo $data[4]; ?>':
                                            tipo_fechada = 5;
                                        break;
                                        // case 'Total':
                                        //     tipo_fechada = 6;
                                        // break;
                                    }
                                }
                                //console.log(tipo_fechada);
                                //data inicial periodo new Date -90
                                //data fim periodo = new Date

                                if (data_final_campo != "" && data_final_campo != undefined) {
                                    data_inicial_campo = data_inicial_campo.split("/");
                                    data_final_campo = data_final_campo.split("/");

                                    //data_inicial_campo = new Date(data_inicial_campo[2],data_inicial_campo[1]-1,data_inicial_campo[0]);
                                    //data_inicial e data_final tem que receber o valor do campo data_final_campo meno no case do TOTAL.
                                    data_final  = new Date(data_final_campo[2],data_final_campo[1]-1,data_final_campo[0]);
                                    data_inicial = new Date(data_inicial_campo[2],data_inicial_campo[1]-1,data_inicial_campo[0]);
                                    data_inicial.setDate(data_inicial.getDate());
                                    data_final.setDate(data_final.getDate());
                                }else{
                                    data_inicial.setMonth(data_inicial.getMonth()-3);
                                    data_final.setDate(data_final.getDate());
                                }


                                dia_inicial = data_inicial.getDate();
                                mes_inicial = data_inicial.getMonth()+1;
                                ano_inicial = data_inicial.getFullYear();
                                dia_final = data_final.getDate();
                                mes_final = data_final.getMonth()+1;
                                ano_final = data_final.getFullYear();

                                if(dia_inicial < 10){
                                    dia_inicial = "0"+dia_inicial;
                                }
                                if(dia_final < 10){
                                    dia_final = "0"+dia_final;
                                }

                                if(mes_inicial < 10){
                                    mes_inicial = "0"+mes_inicial;
                                }
                                if(mes_final < 10){
                                    mes_final = "0"+mes_final;
                                }

                                inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                                final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;
                            }else{
                                if (data_final_campo == "") {
                                    data_inicial.setMonth(data_inicial.getMonth()-3);
                                }

                                dia_inicial = data_inicial.getDate();
                                mes_inicial = data_inicial.getMonth()+1;
                                ano_inicial = data_inicial.getFullYear();
                                dia_final = data_final.getDate();
                                mes_final = data_final.getMonth()+1;
                                ano_final = data_final.getFullYear();

                                if(dia_inicial < 10){
                                    dia_inicial = "0"+dia_inicial;
                                }
                                if(dia_final < 10){
                                    dia_final = "0"+dia_final;
                                }

                                if(mes_inicial < 10){
                                    mes_inicial = "0"+mes_inicial;
                                }
                                if(mes_final < 10){
                                    mes_final = "0"+mes_final;
                                }

                                inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                                final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;

                                if(status_pie == 'Abertas'){
                                    os_aberta = 1;
                                }
                                status_checkpoint = '';
                                tipo_fechada = '';
                            }

                            <?php

                            if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){
                                echo "inicial_format = '{$inicial_format}';";
                                echo "final_format = '{$final_format}';";
                            }

                            ?>

                            window.open('os_consulta_lite.php?data_inicial='+inicial_format+'&data_final='+final_format+'&status_checkpoint='+status_checkpoint+'&os_aberta='+os_aberta+'&linha='+linha_id+'&btn_acao=1&dash=1&tipo_fechada='+tipo_fechada+'&os_produto_peca='+os_produto_peca+'&os_revenda_consumidor='+os_revenda_consumidor+'&dash_codigo_posto='+codigo_posto+'&dash_produto_referencia='+produto_referencia+'&dash_peca_referencia='+peca_referencia<?php if($login_fabrica == 50 && isset($_GET["data_inicial"]) && isset($_GET["data_final"])){ echo "+'&dashboard=sim&intervalo_dia='+intervalo_dia"; } ?>);

                            // window.open('os_consulta_lite.php?data_inicial='+inicial_format+'&data_final='+final_format+'&status_checkpoint='+status_checkpoint+'&os_aberta='+os_aberta+'&linha='+linha_id+'&btn_acao=1&dash=1&tipo_fechada='+tipo_fechada+'&os_produto_peca='+os_produto_peca+'&os_revenda_consumidor='+os_revenda_consumidor+'&dash_codigo_posto='+codigo_posto+'&dash_produto_referencia='+produto_referencia+'&dash_peca_referencia='+peca_referencia+'&dash_cpf='+cpf+'&dash_revenda_cnpj='+revenda_cnpj);
                            // window.open('os_consulta_lite.php?data_inicial='+inicial_format+'&data_final='+final_format+'&status_checkpoint='+status_checkpoint+'&os_aberta='+os_aberta+'&linha='+linha_id+'&btn_acao=1&dash=1&tipo_fechada='+tipo_fechada);
                        }
                    }
                }
            }
        },
        series: [
            <?=$resultadosArray?>
        ]
    });
}

function filtro_grafico(){
    var posto_id = $('#posto_codigo').val();
    var linha_id = $('#linha').val();
    var estados = "";

    <?php if ($login_fabrica == 160 or $replica_einhell) { ?>
        estados = $("#estados").val();
    <?php } ?>

    $.ajax({
        url:"<?=$PHP_SELF?>",
        type:"POST",
        dataType:"json",
        data:{
            posto  : posto_id,
            linha  : linha_id,
            estados: estados,
            ajax   : "sim"
        },
        beforeSend:function(){
            var carregando = false;
            var chart = $('#os_chart').highcharts();

            if(!carregando){
                chart.showLoading("Carregando...");
            }
        }
    })
    .done(function(result){
        var chart           = $('#os_chart').highcharts();
        var nomes           = new Array();
        var nomesMudados    = new Array();
        var idPizza         = 0;
        var idJson          = 0;
        var pizza;
        var data;
        var nome;
        var nomeCorrigido;

        chart.hideLoading();

        $.each(chart.series,function(i,val){
            nomes.push(val.name);
        });
        pizza = nomes.pop();

        for(i=0;i<result.length;i++){
            nome = result[i].nome;
            data = result[i].data;
            nomeCorrigido = $('<div/>').html(nome).text();

            $.each(nomes,function(k,val){
                if(val == nomeCorrigido){
                    nomesMudados.push(nomeCorrigido);
                    chart.series[k].setData(data);
                }
            });
        }

        $.each(nomes,function(j,val2){
            if($.inArray(val2,nomesMudados) == -1){
                chart.series[j].setData([0,0,0,0,0]);
            }
        });

        idPizza = (chart.series.length) - 1;
        idJson = (result.length) - 1;
        chart.series[idPizza].setData([result[idJson].Abertas,result[idJson].Fechadas]);
        chart.series[idPizza].data[0].update({
            name:"Abertas"
        });
        chart.series[idPizza].data[1].update({
            name:"Fechadas"
        });
    });
}

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
    $("#posto_codigo").val(retorno.posto);


    $("#lista_todos").uncheck();

    if ($("#login_fabrica_codigo").val() != 151) {
        filtro_grafico();
    }

}

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}
function retorna_peca(retorno){
    $("#peca").val(retorno.peca);
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
}
function retorna_consumidor(retorno){
    $("#cliente").val(retorno.cliente);
    $("#cpf").val(retorno.cpf);
    $("#nome").val(retorno.nome);
    $("#consumidor_cidade").val(retorno.consumidor_cidade);
    $("#endereco").val(retorno.endereco);
    $("#rg").val(retorno.rg);
    $("#numero").val(retorno.numero);
    $("#complemento").val(retorno.complemento);
    $("#bairro").val(retorno.bairro);
    $("#consumidor_estado").val(retorno.estado);
    $("#fone").val(retorno.fone);
    $("#cep").val(retorno.cep);
    $("#contrato_numero").val(retorno.contrato_numero);

    if (retorno.contrato == 't'){
        $('#contratoSim').prop('checked', true);
        $('#contratoNao').prop('checked', false);
    }else{
        $('#contratoNao').prop('checked', true);
        $('#contratoSim').prop('checked', false);
    }

    if (retorno.consumidor_final == 't'){
        $('#consumidorSim').prop('checked', true);
        $('#consumidorNao').prop('checked', false);
    }else{
        $('#consumidorSim').prop('checked', false);
        $('#consumidorNao').prop('checked', true);
    }

    $("#contrato").val(retorno.contrato);
    $("#consumidor_final").val(retorno.consumidor_final);
    // console.log(retorno.contrato);
    // console.log(retorno.consumidor_final);
}
/**
 * Função de retorno da lupa de revenda
 */
function retorna_revenda(retorno) {
    $("#revenda_nome").val(retorno.razao);
    $("#revenda_cnpj").val(retorno.cnpj);
}

$(function () {
    Shadowbox.init();
    // $.autocompleteLoad(["posto", "peca"]);
    if ($("#login_fabrica_codigo").val() == 151) {
        $("#revenda_cnpj").mask("99.999.999/9999-99");
        $("#data_inicio").datepicker({maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
        $("#data_fim").datepicker({maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    }

    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    $("#lista_todos").on("click", function(){
        if(this.checked){
            $("#codigo_posto").val("");
            $("#descricao_posto").val("");

            if ($("#login_fabrica_codigo").val() != 151) {
                filtro_grafico();
            }
        }
    });

    $('#estados').change(function(){
        if ($("#login_fabrica_codigo").val() != 151) {
            filtro_grafico();
        }
    });

    <?php if ($login_fabrica == 160 or $replica_einhell) { ?> 
        $('select[name=estados]').change(function(){
            filtro_grafico();
        });
    <?php } ?>

    chartOs();

    $('#os_chart_pizza').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: ''
        },
        tooltip: {
            pointFormat: '{name}: <b>{point.y} OSs</b>'
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
            },
            series:{
                point:{
                    events:{
                        click: function(){
                            var data                = this.category;
                            var status              = this.series.name;
                            var status_pie          = this.name;
                            var tipo                = this.series.type;
                            var data_inicial        = new Date();
                            var data_final          = new Date();
                            var linha_id            = $('#linha').val();
                            var status_checkpoint = '';
                            var dia_inicial;
                            var mes_inicial;
                            var ano_inicial;
                            var dia_final;
                            var mes_final;
                            var ano_final;
                            var os_aberta = '';
                            var tipo_fechada = '';
                            var fechadas_sem_reparo = '';
                            var data_inicial_campo = $("#data_inicio").val();
                            var data_final_campo = $("#data_fim").val();

                            var os_produto_peca = $("input[name='os_produto_peca']:checked").val();
                            var os_revenda_consumidor =$("input[name='os_revenda_consumidor']:checked").val();

                            //data inicial periodo new Date -90
                            //data fim periodo = new Date

                            if (data_final_campo != "") {
                                data_inicial_campo = data_inicial_campo.split("/");
                                data_final_campo = data_final_campo.split("/");
                                data_final = new Date(data_final_campo[2],data_final_campo[1]-1,data_final_campo[0]);
                                data_inicial = new Date(data_inicial_campo[2],data_inicial_campo[1]-1,data_inicial_campo[0]);
                            }

                            if (data_final_campo == "") {
                                data_inicial.setMonth(data_inicial.getMonth()-3);
                            }

                            dia_inicial = data_inicial.getDate();
                            mes_inicial = data_inicial.getMonth()+1;
                            ano_inicial = data_inicial.getFullYear();
                            dia_final = data_final.getDate();
                            mes_final = data_final.getMonth()+1;
                            ano_final = data_final.getFullYear();

                            if(dia_inicial < 10){
                                dia_inicial = "0"+dia_inicial;
                            }
                            if(dia_final < 10){
                                dia_final = "0"+dia_final;
                            }

                            if(mes_inicial < 10){
                                mes_inicial = "0"+mes_inicial;
                            }
                            if(mes_final < 10){
                                mes_final = "0"+mes_final;
                            }

                            inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                            final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;

                            if(status_pie == traduz('Abertas')){
                                os_aberta = 1;
                            }

                            if(status_pie == traduz("Fechadas sem reparo")){
                                fechadas_sem_reparo = 'true';
                            }

                            if (data_final_campo == "") { //hd_chamado=2787856
                                inicial_format = $("#data_pesquisa").val();
                            }

                            var codigo_posto = $("#codigo_posto").val();
                            var produto_referencia = $("#produto_referencia").val();
                            var peca_referencia = $("#peca_referencia").val();

                            window.open('os_consulta_lite.php?data_inicial='+inicial_format+'&data_final='+final_format+'&status_checkpoint='+status_checkpoint+'&os_aberta='+os_aberta+'&linha='+linha_id+'&btn_acao=1&dash=1&tipo_fechada='+tipo_fechada+'&os_produto_peca='+os_produto_peca+'&os_revenda_consumidor='+os_revenda_consumidor+'&dash_codigo_posto='+codigo_posto+'&dash_produto_referencia='+produto_referencia+'&dash_peca_referencia='+peca_referencia+'&fechadas_sem_reparo='+fechadas_sem_reparo);

                            // window.open('os_consulta_lite.php?data_inicial='+inicial_format+'&data_final='+final_format+'&status_checkpoint='+status_checkpoint+'&os_aberta='+os_aberta+'&linha='+linha_id+'&btn_acao=1&dash=1&tipo_fechada='+tipo_fechada+'&os_produto_peca='+os_produto_peca+'&os_revenda_consumidor='+os_revenda_consumidor+'&dash_codigo_posto'+codigo_posto+'&dash_produto_referencia'+produto_referencia+'&dash_peca_referencia'+peca_referencia+'&dash_cpf'+cpf+'&dash_revenda_cnpj'+revenda_cnpj);
                        }
                    }
                }
            }
        },
        series: [{
            colorByPoint: true,
            data: [
                    <?=$dados_pizza?>
                ],
            dataLabels: {
                    enabled: true,
                    formatter:function(){
                        var nome    = this.point.name;
                        var pc      = parseFloat(this.percentage);
                        var novaPc  = Highcharts.numberFormat(pc,2,',','.');
                        return nome+' - '+novaPc+'%';
                    }
                }
        }]

    });

    $('#pedidos_chart').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            borderColor: '#CCC',
            borderWidth: 2
        },
        title: {
            text: '',
            useHTML: true
        },
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px;width:150px">{point.key}</span><table style="width:150px;">',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0" nowrap><b>{point.y} Pedidos</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
            },
            series:{
                point:{
                    events:{
                        click: function(){
                            var data_inicial    = new Date();
                            var data_final      = new Date();
                            var status_pie      = this.name;
                            var dia_inicial;
                            var mes_inicial;
                            var ano_inicial;
                            var dia_final;
                            var mes_final;
                            var ano_final;
                            var estado_pedido = '';

                            data_inicial.setDate(data_inicial.getDate()-90);

                            dia_inicial = data_inicial.getDate();
                            mes_inicial = data_inicial.getMonth()+1;
                            ano_inicial = data_inicial.getFullYear();
                            dia_final = data_final.getDate();
                            mes_final = data_final.getMonth()+1;
                            ano_final = data_final.getFullYear();

                            if(dia_inicial < 10){
                                dia_inicial = "0"+dia_inicial;
                            }
                            if(dia_final < 10){
                                dia_final = "0"+dia_final;
                            }

                            if(mes_inicial < 10){
                                mes_inicial = "0"+mes_inicial;
                            }
                            if(mes_final < 10){
                                mes_final = "0"+mes_final;
                            }

                            inicial_format = dia_inicial+"/"+mes_inicial+"/"+ano_inicial;
                            final_format   = dia_final  +"/"+mes_final  +"/"+ano_final;

                            if(status_pie == 'Pendentes'){
                                estado_pedido = 1;
                            }else{
                                estado_pedido = 2;
                            }
                            window.open('pedido_consulta.php?btn_acao_pesquisa=continuar&dash=1&estado_pedido='+estado_pedido+'&data_inicial_01='+inicial_format+'&data_final_01='+final_format+'&chk_opt6=sim');
                        }
                    }
                }
            }
        },
        series:[{
            type: 'pie',
            name: 'Total de pedidos',
            tooltip: {
                pointFormat: '{name}: <b>{point.y} Pedidos</b>'
            },
            data: [
                ['<?=traduz('Pendentes')?>',<?=$pendente?>],
                ['<?=traduz('Finalizados')?>',<?=$finalizadas?>]
            ],
            dataLabels: {
                enabled: true,
                formatter:function(){
                    var nome    = this.point.name;
                    var pc      = parseFloat(this.percentage);
                    var novaPc  = Highcharts.numberFormat(pc,2,',','.');
                    return nome+' - '+novaPc+'%';
                }
            }
        }]
    });

    $('#extratos_chart').highcharts({
        chart: {
            borderColor: '#CCC',
            borderWidth: 2,
            type: 'column'
        },
        title: {
            text: ''
        },
        navigation: {
            buttonOptions: {
                enabled: false
            }
        },
        credits: {
            enabled: false
        },

        xAxis: {
            categories: [
                <?=$meses?>
            ]
        },
        yAxis: {
            minorTickInterval: 'auto',
            minorTickLength: 0,
            min: 0,
            title: {
                text: 'Extratos'
            }
        },
        tooltip: {
            formatter:function(){

                var formato = Highcharts.numberFormat(parseFloat(this.y),2,',','.');
                var chart = $('#extratos_chart').highcharts();
                var series  = chart.series;
                var key     = this.x;
                var cor     = series.color;
                var nome    = series.name;

                return '<span style="font-size:10px;width:150px">'+key+'</span><table style="width:150px;">'+
                        '<tr><td style="color:'+cor+';padding:0">Valor: </td>'+
                        '<td style="padding:0" nowrap><b>R$ '+formato+' </b></td></tr>'+
                        '</table>';
            },
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                dataLabels:{
                    enabled: true,
                    formatter: function(){
                        var valor = Highcharts.numberFormat(parseFloat(this.y),2,',','.');

                        return 'R$ '+valor;
                    }
                }
            },
            series:{
                cursor: 'pointer',
                point:{
                    events:{
                        click: function(){
                            window.open('extrato_consulta.php?dashboard=sim&mes='+this.category);
                        }
                    }
                }
            }
        },
        series:[
            {
                name:"Valor",
                data: <?=$extrato_valor?>
            }
        ]
    });


    <?php if ($login_fabrica == 160 or $replica_einhell) { /*HD - 6074502*/?>
        $("#estados").multiselect({
           selectedText: "selecionados # de #"
        });
    <?php } ?>
});

</script>

<style>
.postit{
    background: url('../imagens/sticky.png') no-repeat;
    background-position: center top;
    background-size: cover;
    height: 250px;
}
.row-fluid .span4{
    width: 30%;
}

#row-fluid-comunicados{
    margin-bottom:0;
    width:99.5%;
}

.row-fluid .span4{
    width: 31.2%;
}

#senha_extrato{
    height:350px;
    width:845px;
    position:absolute;
    border:2px solid #CCC;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    z-index:1;
}

#senha{
    background-color: #D9E2EF;
    height:150px;
    width:300px;
    text-align:center;
    position:absolute;
    top:50%;
    left:50%;
    margin-top:-80px;
    margin-left:-160px;
}
#senha span{
    background-color:#596D9B;
    border:1px solid;
    color:#FFF;
    padding-left:91px;
    padding-right:91px;
}

#bt_periodo{
    margin-left:10px;
    margin-bottom: 10px;
}
</style>
</head>
<body>

<?php
if($login_fabrica == 43){
    include ('posto_medias.php');
}
?>

<?php
if (count($msg_erro["msg"]) > 0 AND $login_fabrica == 151) {
?>
<br />
<div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
</div>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<?php
}
?>

<form id="fm_dashboard_fabrica" action="<?=$PHP_SELF?>" method="POST" class="form-search form-inline tc_formulario" >
<input type="hidden" id="posto_codigo" name='posto_codigo' value='<?=$posto?>' >
<input type="hidden" id="login_fabrica_codigo" name='login_fabrica_codigo' value='<?=$login_fabrica?>' >
<input type="hidden" name="data_pesquisa" id="data_pesquisa" value="<?=$data_pesquisa?>">
<div class='container tc_container' style="background-color:#D3D3D3;">
<div class="titulo_tabela">DASHBOARD</div>
    <br>
    <?php
    if ($login_fabrica == 151) {?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class="span4">
                <div class='control-group <?=(in_array("data_inicio", $msg_erro["campos"])) ? "error" : ""?>' >
                    <label class="control-label" for="data_inicio"><?=traduz('Data Inicio')?></label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <h5 class='asteristico'>*</h5>
                            <input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?=getValue('data_inicio')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class='control-group <?=(in_array("data_fim", $msg_erro["campos"])) ? "error" : ""?>' >
                    <label class="control-label" for="data_fim"><?=traduz('Data Fim')?></label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <h5 class='asteristico'>*</h5>
                            <input id="data_fim" name="data_fim" class="span12" type="text" value="<?=getValue('data_fim')?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>
    <div class="row-fluid">
        <div class="span2" ></div>

        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="codigo_posto" ><?=traduz('Código Posto')?></label>

                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<? echo $codigo_posto ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="descricao_posto" ><?=traduz('Nome Posto')?></label>

                <div class="controls controls-row" >
                    <div class="span11 input-append" >
                        <input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<? echo $descricao_posto ?>" />
                        <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>

        <div class="span2"></div>
    </div>
    <?php
    if ($login_fabrica == 151) {?>
        <div class="row-fluid">
            <div class="span2"></div>

            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?=getValue('produto_referencia')?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?=getValue('produto_descricao')?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span2"></div>
        </div>

        <div class="row-fluid">
            <div class="span2"></div>

            <div class='span4'>
                <div class='control-group <?=(in_array("peca_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_referencia'><?=traduz('Ref. Peças')?></label>
                    <div class='controls controls-row'>
                        <div class='span10 input-append'>
                            <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
                        </div>
                    </div>
                </div>
            </div>

            <div class='span4'>
                <div class='control-group <?=(in_array("peca_descricao", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='peca_descricao'><?=traduz('Descrição Peça')?></label>
                    <div class='controls controls-row'>
                        <div class='span11 input-append'>
                            <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span2"></div>
        </div>
    <?
    }
    ?>
    <br>
    <div class="row-fluid">
        <div class="span2" ></div>
        <div class="span4" >
            <input type='checkbox' <? if ($lista_todos == 't' ) echo " checked " ?> name='lista_todos' id="lista_todos" value='t'>
            <label><?=traduz('Listar OS de todos os Postos')?></label>
        </div>
        <div class="span4" >
            <select name="linha" id="linha" >
                <optgroup label="<?=traduz('Selecione a Linha')?>">
                    <option value="">'<?=traduz('Todas as Linhas')?></option>
                    <?
                for($c=0;$c<pg_num_rows($resLinha);$c++){
                    $linha      = pg_fetch_result($resLinha,$c,0);
                    $linha_nome = pg_fetch_result($resLinha,$c,1);
                    ?>
                    <option value="<?=$linha?>"><?=$linha_nome?></option>
                <?
                }
                ?>
                </optgroup>
            </select>
        </div>
        <div class="span2" ></div>
    </div>
    <?php if ($login_fabrica == 160 or $replica_einhell) { /*HD - 6074502*/ ?> 
        <div class="row-fluid">
            <div class="span2" ></div>
            <div class="span4" >
                <label class='control-label' for='estados'>Estado</label><br>
                <select name="estados[]" id="estados" multiple="multiple">
                    <?php
                        $aux_sql = "SELECT estado, nome FROM tbl_estado WHERE visivel IS TRUE ORDER BY estado";
                        $aux_res = pg_query($con, $aux_sql);
                        $aux_row = pg_num_rows($aux_res);

                        for ($z = 0; $z < $aux_row; $z++) { 
                            $estado = pg_fetch_result($aux_res, $z, 'estado');
                            $nome   = pg_fetch_result($aux_res, $z, 'nome');

                            ?> <option value="<?=$estado;?>"><?=$estado;?> - <?=$nome;?></option> <?
                        }
                    ?>
                </select>
            </div>
            <div class="span4" ></div>
            <div class="span2" ></div>
        </div>
    <?php } 
    
    if ($login_fabrica == 151) {?>
        <div class="row-fluid">
            <div class="span2" ></div>
            <div class="span3" >
                <input type='radio' <? if ($os_produto_peca == 'produto' ) echo " checked " ?> name='os_produto_peca' id="os_produto" value='produto'>
                <label>Listar OS de Troca Produtos</label>
            </div>
            <div class="span3" >
                <input type='radio' <? if ($os_produto_peca == 'peca' ) echo " checked " ?> name='os_produto_peca' id="os_peca" value='peca'>
                <label>Listar OS de Peças</label>
            </div>
            <div class="span3" >
                <input type='radio' <? if ($os_produto_peca == 'todas' OR empty($os_produto_peca) ) echo " checked " ?> name='os_produto_peca' id="os_todas" value='todas'>
                <label>Listar Todas OS</label>
            </div>
            <div class="span1" ></div>
        </div>

        <div class="row-fluid">
            <div class="span2" ></div>
            <div class="span3" >
                <input type='radio' <? if ($os_revenda_consumidor == 'consumidor' ) echo " checked " ?> name='os_revenda_consumidor' id="os_consumidor" value='consumidor'>
                <label>Listar OS Consumidor</label>
            </div>
            <div class="span3" >
                <input type='radio' <? if ($os_revenda_consumidor == 'revenda' ) echo " checked " ?> name='os_revenda_consumidor' id="os_revenda" value='revenda'>
                <label>Listar OS Revenda</label>
            </div>
            <div class="span3" >
                <input type='radio' <? if ($os_revenda_consumidor == 'todas' OR empty($os_revenda_consumidor) ) echo " checked " ?> name='os_revenda_consumidor' id="os_revenda_consumidor" value='todas'>
                <label>Listar Todas OS</label>
            </div>
            <div class="span1" ></div>
        </div>

        <div class="row-fluid" >
            <div class="span2" ></div>
            <div class="span4">
                <div class='control-group <?=(in_array("status_checkpoint", $msg_erro["campos"])) ? "error" : ""?>' >
                    <label class="control-label" for="status_checkpoint">Status OS</label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <?
                            $array_status_os= array(
                                "0"  => "Aberta Call-Center",
                                "1"  => "Aguardando Análise",
                                "2"  => "Aguardando Peças",
                                "8"  => "Aguardando Produto",
                                "3"  => "Aguardando Conserto",
                                "4"  => "Aguardando Retirada (Consertada)",
                                "9"  => "Finalizada");
                            ?>
                            <select id="status_checkpoint" name="status_checkpoint" class="span12">
                                <option value="">Selecione</option>
                                <?
                                foreach ($array_status_os as $sigla => $nome_status_os) {
                                    $selected = ($sigla === getValue('status_checkpoint')) ? "selected" : "";?>
                                    <option value='<?=$sigla?>' <?=$selected?>> <?=$nome_status_os?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2" ></div>
        </div>
        <br>
    <?php
    }
    if(in_array($login_fabrica, array(3,151))){
    ?>
        <div class="row-fluid" >
        <?php
        if ($login_fabrica != 151) {
            ?>
            <div class="span4" ></div>
            <div class="span3" >
                <input type="submit" id="bt_periodo" class="btn btn-primary" value="Pesquisar por <?=$tempo_periodo?> meses" name="bt_periodo" >
                <input type="hidden" id="periodo" name="periodo" value="<?=$tempo_periodo?>">
            </div>
        <?
        }else{
            ?>
            <div class="span12" align="center" >
            <p class="tac">
                <input type="submit" id="bt_periodo" class="btn btn-primary" value="Pesquisar" name="bt_periodo" >
                </p>
            </div>
        <?
        }
        ?>
        </div>
    <?php
    }
    if ($login_fabrica == 151 and !empty($msg_erro)) {?>
        <div class="row-fluid" style="display:none;">
    <?php
    }else{?>
        <div class="row-fluid" style="display:">
    <?php
    }
    ?>

        <div class="span12" >

                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion4" href="#collapseThree">
                            <?php
                            if(strlen($inicial_format) > 0 && strlen($final_format) > 0 && $login_fabrica == 50){
                                echo "<strong>Ordens de Serviço Pendentes de $inicial_format a $final_format</strong>";
                            }else if (!empty($_POST['bt_periodo']) AND $login_fabrica == 151) {?>
                                <b>Ordens de Serviço Abertas no Período</b>
                            <?php
                            }else{?>
                                <b><?=traduz('Ordens de Serviço Abertas nos Últimosi % meses ', null,null,[$periodo]);?></b>
                            <?php
                            }
                            ?>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseThree" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="os_chart" style="height: 500px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <br />
                <?php
                if ($login_fabrica == 151) {?>
                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion4" href="#collapsePizza">
                            <b>Total de OSs</b>
                            <i class="icon-zoom-in pull-right"></i>
                      </a>
                    </div>
                    <div id="collapsePizza" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="os_chart_pizza" style="height: 350px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <br />
                <?php
                }
                ?>


                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                            <?php
                            if (!empty($_POST['bt_periodo']) AND $login_fabrica == 151) {?>
                                <b>Pedidos Gerados no Período</b>
                            <?php
                            }else{?>
                                <b><?=traduz('Pedidos Gerados nos Últimos % meses', null, null, [$periodo])?></b>
                            <?php
                            }
                            ?>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseOne" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="pedidos_chart" style="height: 350px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>



                <br />
<?
if($esconder == "SIM"){
?>
        <div class="accordion">
                <div id="senha_extrato">
                    <div id="senha">
                        <span><?=traduz('Validação de Senha')?></span>
                        <br />
                        <p style="text-align:center;">
                            <?=traduz('Para acessar o gráfico, favor Digitar a senha de acesso do financeiro')?>
                        </p>
                        <br />
                        <cite id="msg" style="display:none;"><?=traduz('Favor, digitar a senha correta')?></cite>
                        <input type="password" name="senha_financeiro" id="senha_financeiro" >
                        <br />
                        <button type="button" onclick="javascript:senhaFinanceiro();"><?=traduz('Acessar')?></button>
                    </div>
                </div>
<?
}
?>

                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#collapseTwo">
                            <?php
                            if (!empty($_POST['bt_periodo']) AND $login_fabrica == 151) {?>
                                <b>Extratos Gerados no Período</b>
                            <?php
                            }else{?>
                                <b><?=traduz('Extratos Gerados nos Últimos % meses ',null,null,[$periodo]);?></b>
                            <?php
                            }
                            ?>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseTwo" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="extratos_chart" style="height: 350px; margin: 0 auto;<? if($esconder == "SIM"){?>opacity:0;<?}?>"></div>
                      </div>
                    </div>
                  </div>
                </div>

            </div>
        </div>
    </div>
    <br />
    <div class="row-fluid" id="row-fluid-comunicados">
        <div class="span12">
            <h4 style="text-align:center;color:274B6D;font-family:'Lucida Grande', 'Lucida Sans Unicode',Verdana,Arial, Helvetica, sans-serif">
                <?=traduz('Últimos Comunicados')?>
            </h4>
            <?php
            $decode = json_decode($resultadosArrayComunicados);
            foreach($decode as $comunicado=>$valores){
            ?>
            <div class="span4 postit" id="<?=$comunicado?>">
                <div style="width: 220px; margin: 0 auto;">
                    <h6 class="tac">
                    <?php
                        echo $valores->data." - ".$valores->tipo;
                    ?>
                    </h6>
                    <p style="margin-left:20px;margin-right:60px;">
                    <?php
                    echo substr(html_entity_decode($valores->mensagem),0,110)."...";
                    ?>
                        <a href="comunicado_produto.php?comunicado=<?=$comunicado?>" target="_BLANK"><?=traduz('Continua')?></a>
                    </p>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <br />
</div>
<?php
    include "rodape.php";
?>
