<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/./funcoes.php';

global $login_fabrica;
$login_fabrica = 158;

if ($_serverEnvironment == 'development') {
    $chave_persys = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447';
}else{
    $chave_persys = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';
}

$Url_Base   = "http://telecontrol.eprodutiva.com.br/api/recurso";
$Array_Curl = array(
                CURLOPT_URL => "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => array(
                    "authorizationv2: {$chave_persys}",
                    "Content-Type: application/json")
            );

/* LISTA TODOS OS PRODUTO DA FÁBRICA */
$sql = "SELECT produto,referencia,descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica};";
$res = pg_query($con, $sql);
$totalProdutos = pg_num_rows($res);

for ($i=0; $i < $totalProdutos; $i++) {
    $Produto = pg_result($res,$i,'produto');
    $Referencia = pg_result($res,$i,'referencia');
    $Produto_descr = pg_result($res,$i,'descricao');

    echo "\n-> produto $Referencia, $i, falta ".($totalProdutos - $i)."\n\n";

    /* VERIFICA SE PRECISA ADICIONAR O EQUIPAMENTO(PRODUTO) NA API */
    $Array_Curl[CURLOPT_URL] = "{$Url_Base}/equipamento/codigo/{$Referencia}";
    unset($Array_Curl[CURLOPT_CUSTOMREQUEST]);
    unset($Array_Curl[CURLOPT_POSTFIELDS]);

    $ch = curl_init();
    curl_setopt_array($ch,$Array_Curl);
    $equipamento = curl_exec($ch);
    $equipamento = json_decode($equipamento, true);
    print_r($equipamento);echo "\n";
    curl_close($ch);

    if (isset($equipamento['error']['message']) && $equipamento['error']['message'] == "equipment not found") {

        $row['codigo'] = $Referencia;
        $row['equipamento'] = $Produto_descr;
        $row['medida'] = array("id" => '301');
        $row['statusModel']  = '1';
        $json = json_encode($row);

        $Array_Curl[CURLOPT_URL] = "{$Url_Base}/equipamento";
        $Array_Curl[CURLOPT_CUSTOMREQUEST] = 'POST';
        $Array_Curl[CURLOPT_POSTFIELDS] = $json;

        $ch = curl_init();
        curl_setopt_array($ch,$Array_Curl);
        $result = curl_exec($ch);
        print_r($result);echo "\n";
        curl_close($ch);
    }

    /* CONSULTA LISTA BÁSICA DO PRODUTO NA API */
    $Array_Curl[CURLOPT_URL] = "{$Url_Base}/equipamento/codigo/{$Referencia}/material";
    unset($Array_Curl[CURLOPT_CUSTOMREQUEST]);
    unset($Array_Curl[CURLOPT_POSTFIELDS]);

    $ch = curl_init();
    curl_setopt_array($ch,$Array_Curl);
    $response = curl_exec($ch);
    print_r($response);echo "\n";

    curl_close($ch);

    $Equipamento_API = json_decode($response, true); /* LISTA BÁSICA API */

    /* RETORNA LISTA BÁSICA DO PRODUTO NA BASE DE DADOS TELECONTROL */
    $sql = "SELECT tbl_peca.referencia,tbl_lista_basica.qtde,tbl_peca.descricao FROM tbl_lista_basica JOIN tbl_peca ON(tbl_lista_basica.peca = tbl_peca.peca) WHERE tbl_lista_basica.produto = {$Produto} AND tbl_lista_basica.fabrica = {$login_fabrica};";
    $result = pg_query($con, $sql);
    
    if (isset($Equipamento_API)) {

        /* ATUALIZA LISTA BÁSICA DA TELECONTROL COM A DA API */
        foreach ($Equipamento_API as $Material_API_Array) {
            foreach ($Material_API_Array as $Indice => $Material_API) {
                if (isset($Material_API['material']['statusModel']) && $Material_API['material']['statusModel'] == '1') {

                    /* INATIVA TODOS AS PEÇAS */
                    $data = array("statusModel" => "0");
                    $json = json_encode($data);

                    $Array_Curl[CURLOPT_URL] = "{$Url_Base}/equipamento/codigo/{$Referencia}/material/codigo/{$Material_API['material']['codigo']}";
                    $Array_Curl[CURLOPT_CUSTOMREQUEST] = 'PUT';
                    $Array_Curl[CURLOPT_POSTFIELDS] = $json;

                    $ch = curl_init();
                    curl_setopt_array($ch,$Array_Curl);
                    $response = curl_exec($ch);
                    print_r($response);echo "\n";
                    curl_close($ch);
                }
            }
        }
        for ($x=0; $x < pg_num_rows($result); $x++) {
            $peca = pg_result($result,$x,'referencia');
            $qtde = pg_result($result,$x,'qtde');
            $descricao = pg_result($result,$x,'descricao');
            $Ativado = 0;

            foreach ($Equipamento_API as $Material_API_Array) {
                foreach ($Material_API_Array as $Indice => $Material_API) {
                    if(isset($Material_API['material']['codigo']) && $Material_API['material']['codigo'] == $peca){
                        $Ativado = 1;
                        $data = array("statusModel" => "1","maxQuantity" => $Material_API['maxQuantity'],"minQuantity" => '1');
                        $json = json_encode($data);

                        $Array_Curl[CURLOPT_URL] = "{$Url_Base}/equipamento/codigo/{$Referencia}/material/codigo/{$peca}";
                        $Array_Curl[CURLOPT_CUSTOMREQUEST] = 'PUT';
                        $Array_Curl[CURLOPT_POSTFIELDS] = $json;

                        $ch = curl_init();
                        curl_setopt_array($ch,$Array_Curl);
                        $response = curl_exec($ch);
                        print_r($response);echo "\n";
                        curl_close($ch);
                        break;
                    }
                }
            }
            if ($Ativado == 0) { /* PEÇA NÃO ESTA NA LISTA BÁSICA DA API, INSERE NA LISTA */
                Insere_Novo_Material($Referencia,$peca,$qtde,$descricao);
            }
        }
    }else{

        /* ATUALIZA API COM A BASE DE DADOS TELECONTROL */
        for ($x=0; $x < pg_num_rows($result); $x++) {
            $peca = pg_result($result,$x,'referencia');
            $qtde = pg_result($result,$x,'qtde');
            $descricao = pg_result($result,$x,'descricao');

            Insere_Novo_Material($Referencia,$peca,$qtde,$descricao);
        }
    }
    flush();
}

function Insere_Novo_Material($Equipamento,$Material,$Qtde,$Descricao){
    global $Array_Curl,$Url_Base;

    /* VERIFICA SE PRECISA ADICIONAR O MATERIAL(PEÇA) NA API */
    $Array_Curl[CURLOPT_URL] = "{$Url_Base}/material/codigo/{$Material}";
    unset($Array_Curl[CURLOPT_CUSTOMREQUEST]);
    unset($Array_Curl[CURLOPT_POSTFIELDS]);

    $ch = curl_init();
    curl_setopt_array($ch,$Array_Curl);
    $response = curl_exec($ch);
    $response = json_decode($response, true);
    print_r($response);echo "\n";
    curl_close($ch);

    if (isset($response['error']['message']) && $response['error']['message'] == "material not found") {

        $campos['codigo'] = $Material;
        $campos['medida'] = array("id" => "301");
        $campos['material'] = utf8_encode($Descricao);
        $campos['statusModel'] = '1';
        $json = json_encode($campos);

        $Array_Curl[CURLOPT_URL] = "{$Url_Base}/material";
        $Array_Curl[CURLOPT_CUSTOMREQUEST] = "POST";
        $Array_Curl[CURLOPT_POSTFIELDS] = $json;

        $ch = curl_init();
        curl_setopt_array($ch,$Array_Curl);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        print_r($response);echo "\n";
        curl_close($ch);
    }

    $data = array(
        "material" => array(
            "id" => $response['id']
        ),
        "maxQuantity" => "$Qtde",
        "minQuantity" => "1"
    );
    $json = json_encode($data);

    $Array_Curl[CURLOPT_URL] = "{$Url_Base}/equipamento/codigo/{$Equipamento}/material";
    $Array_Curl[CURLOPT_CUSTOMREQUEST] = 'POST';
    $Array_Curl[CURLOPT_POSTFIELDS] = $json;

    $ch = curl_init();
    curl_setopt_array($ch,$Array_Curl);
    $response = curl_exec($ch);
    print_r($response);echo "\n";
    curl_close($ch);
}
?>
