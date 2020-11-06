<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
require __DIR__.'/../classes/api/Client.php';

use api\Client;

$tabela = $_GET['parametro'];
$id        = $_GET['id'];

$layout_menu = "gerencia";
$title = "RELATÓRIO DE LOG DE ALTERAÇÃO";
include 'cabecalho_new.php';

$plugins = array(
    "dataTable"
);
?>
    <style>
        .log-ul{
            list-style: none;
            border:solid 1px #eeeeee;
            margin-left: 0px;
        }
        .titulo-log{
            background:#273975;color:#ffffff;padding: 3px 10px 3px 10px;
        }
        .log-li{
            padding-left: 10px;
            border-bottom: solid 1px #eeeeee;
        }
    </style>
<?
include("plugin_loader.php");

$client = Client::makeTelecontrolClient("auditor","auditor");

############## RELATORIO LOG OS_CADASTRO ##############
    $client->urlParams = array(
        "aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
        #"table" =>$tabela,
        "table" =>'tbl_os',
        "primaryKey" => $login_fabrica."*".$id,
        "limit" => "50"
    );

    try{
        $res = $client->get();
        if(count($res)){

            foreach ($res as $key => $value) {
                // Pega o nome do responsável pela alteração
                if($value['data']['user_level'] == "posto"){
                    $sql = "SELECT nome FROM tbl_posto where posto = ".$value['data']['user'];

                    $result = pg_query($con,$sql);
                    $nome = pg_result($result,0,nome);
                }elseif($value['data']['user_level'] == "admin"){
                    $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['user']." and fabrica = ".$login_fabrica;
                    $result = pg_query($con,$sql);
                    $nome = pg_result($result,0,nome_completo);
                }
                $value['user_name'] = $nome;
                unset($value['data']['content']['antes']['faq']); //HD-3103180
                unset($value['data']['content']['antes']['faq_causa']); //HD-3103180
                unset($value['data']['content']['antes']['os_reincidente']); //HD-3103180
                unset($value['data']['content']['antes']['admin_altera']);
                unset($value['data']['content']['antes']['data_modificacao']);
                unset($value['data']['content']['antes']['status_os_ultimo']);

                unset($value['data']['content']['depois']['os_reincidente']); //HD-3103180
                unset($value['data']['content']['depois']['faq']); //HD-3103180
                unset($value['data']['content']['depois']['faq_causa']); //HD-3103180
                unset($value['data']['content']['depois']['admin_altera']); //HD-3103180
                unset($value['data']['content']['depois']['data_modificacao']);
                unset($value['data']['content']['depois']['status_os_ultimo']);

                $value['data']['action'] = strtoupper($value['data']['action']);

                $value['data']['content']['antes']['outros_motivos'] = $value['data']['content']['antes']['situacao'];
                $value['data']['content']['depois']['outros_motivos'] = $value['data']['content']['depois']['situacao'];

                $solucao_antes  = $value['data']['content']['antes']['solucao'];
                $solucao_depois = $value['data']['content']['depois']['solucao'];

                if(strlen(trim($solucao_antes)) > 0){
                    $value['data']['content']['antes']['resposta'] = $solucao_antes;
                }
                if(strlen(trim($solucao_depois)) > 0){
                    $value['data']['content']['depois']['resposta'] = $solucao_depois;
                }

                unset($value['data']['content']['antes']['situacao']);
                unset($value['data']['content']['depois']['situacao']);
                unset($value['data']['content']['antes']['solucao']);
                unset($value['data']['content']['depois']['solucao']);
                unset($value['data']['content']['antes']['faq_solucao']);
                unset($value['data']['content']['depois']['faq_solucao']);
                // Parse de valores do banco para UI
                foreach($value['data']['content']['antes'] AS $keyA => $valueA){
                    if($valueA == "t"){
                        $value['data']['content']['antes'][$keyA] = "Sim";
                    }
                    if($valueA == "f"){
                        $value['data']['content']['antes'][$keyA] = "Não";
                    }
                    if($valueA == "null"){
                        $value['data']['content']['antes'][$keyA] = "";
                    }
                    if(trim($valueA) == ""){
                        $value['data']['content']['antes'][$keyA] = "";
                    }
                }

                foreach($value['data']['content']['depois'] AS $keyD => $valueD){
                     if($valueD == "t"){
                        $value['data']['content']['depois'][$keyD] = "Sim";
                     }

                     if($valueD == "f"){
                        $value['data']['content']['depois'][$keyD] = "Não";
                     }

                     if($valueD == "null"){
                        $value['data']['content']['depois'][$keyD] = "";
                     }

                     if(trim($valueD) == ""){
                        $value['data']['content']['depois'][$keyD] = "";
                     }
                }

                //Coloca nome nos admins de antes e depois
                $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['antes']['admin']." and fabrica = ".$login_fabrica;

                $result = pg_query($con,$sql);
                $nome = pg_result($result,0,nome_completo);

                if($nome != ""){
                    $value['data']['content']['antes']['admin'] = $nome;
                }

                $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['depois']['admin']." and fabrica = ".$login_fabrica;

                $result = pg_query($con,$sql);
                $nome = pg_result($result,0,nome_completo);

                if($nome != ""){
                    $value['data']['admin'] = $nome;
                    $value['data']['content']['depois']['admin'] = $nome;
                }

				$sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['antes']['admin_altera']." and fabrica = ".$login_fabrica;

                $result = pg_query($con,$sql);
                $nome = pg_result($result,0,nome_completo);

                if($nome != ""){
                    $value['data']['content']['antes']['admin_altera'] = $nome;
                }


                $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['depois']['admin_altera']." and fabrica = ".$login_fabrica;

                $result = pg_query($con,$sql);
                $nome = pg_result($result,0,nome_completo);

                if($nome != ""){
                    $value['data']['admin'] = $nome;
                    $value['data']['content']['depois']['admin_altera'] = $nome;
                }
                //Verifica diferenças e retira chaves sem valor significativo
                $array_antes = $value['data']['content']['antes'];
                $array_depois = $value['data']['content']['depois'];

                $alteracoes = array();
                if(strtoupper($value['data']['action']) != "INSERT"){
                    foreach($array_antes AS $keyA => $valueA){
                        if($valueA != $array_depois[$keyA]){
                            $alteracoes[$keyA] = $array_depois[$keyA];
                        }
                    }
                }else{
                    foreach ($value['data']['content']['depois'] as $k => $val) {
                        if($val == ""){
                            $keysUnset[] = $k;
                        }
                    }
                    foreach ($keysUnset as $k) {
                        unset($value['data']['content']['depois'][$k]);
                    }
                    $alteracoes = $value['data']['content']['depois'];
                }
                //-------------------
                $value['data']['alteracoes'] = $alteracoes;
                $res[$key] = $value;
            }
        }else{
            #$error = "Nenhum log encontrado";
        }
    }catch(Exception $ex){
        $error = $ex->getMessage();
    }
############## FIM RELATORIO LOG OS ##############

############## RELATORIO LOG OS_ITEM ##############
    $client->urlParams = array(
        "aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
        "table" =>$tabela,
        "primaryKey" => $login_fabrica."*".$id,
        "limit" => "50"
    );

    try{
        $res_result = $client->get();
        if(count($res_result)){
            foreach ($res_result as $key => $value) {
                unset($alteracoes);

                //tratando o retorno
                $value['data']['content']['antes'] = array(
                                                            'posicao' => $value['data']['content']['antes']['posicao'],
                                                            'peca' => $value['data']['content']['antes']['peca'],
                                                            'qtde' => $value['data']['content']['antes']['qtde'],
                                                            'defeito' => $value['data']['content']['antes']['defeito'],
                                                            'causa_defeito' => $value['data']['content']['antes']['causa_defeito'],
                                                            'servico_realizado' => $value['data']['content']['antes']['servico_realizado'],
                                                            'admin' => $value['data']['content']['antes']['admin'],
                                                            'peca_causadora' => $value['data']['content']['antes']['peca_causadora'],
                                                            'parametros_adicionais' => $value['data']['content']['antes']['parametros_adicionais'],
                                                            'peca_obrigatoria' => $value['data']['content']['antes']['peca_obrigatoria'],
                                                            'fornecedor' => $value['data']['content']['antes']['fornecedor'],
                                                        );

                $value['data']['content']['depois'] = array(
                                                            'posicao' => $value['data']['content']['depois']['posicao'],
                                                            'peca' => $value['data']['content']['depois']['peca'],
                                                            'qtde' => $value['data']['content']['depois']['qtde'],
                                                            'defeito' => $value['data']['content']['depois']['defeito'],
                                                            'causa_defeito' => $value['data']['content']['depois']['causa_defeito'],
                                                            'servico_realizado' => $value['data']['content']['depois']['servico_realizado'],
                                                            'admin' => $value['data']['content']['depois']['admin'],
                                                            'peca_causadora' => $value['data']['content']['depois']['peca_causadora'],
                                                            'parametros_adicionais' => $value['data']['content']['depois']['parametros_adicionais'],
                                                            'peca_obrigatoria' => $value['data']['content']['depois']['peca_obrigatoria'],
                                                            'fornecedor' => $value['data']['content']['depois']['fornecedor'],
                                                        );
                //retirado do array pois foi renomeado para os_item
                unset($value['data']['content']['antes']['os_produto']);
                unset($value['data']['content']['depois']['os_produto']);
                foreach($value['data']['content']['antes'] AS $keyA => $valueA){
                    if($valueA == "t"){
                        $value['data']['content']['antes'][$keyA] = "Sim";
                    }

                    if($valueA == "f"){
                        $value['data']['content']['antes'][$keyA] = "Não";
                    }

                    if($valueA == "null"){
                        $value['data']['content']['antes'][$keyA] = "";
                    }

                    if(trim($valueA) == ""){
                        $value['data']['content']['antes'][$keyA] = "";
                    }
                }

                foreach($value['data']['content']['depois'] AS $keyD => $valueD){
                    if($valueD == "t"){
                        $value['data']['content']['depois'][$keyD] = "Sim";
                    }

                    if($valueD == "f"){
                        $value['data']['content']['depois'][$keyD] = "Não";
                    }

                    if($valueD == "null"){
                        $value['data']['content']['depois'][$keyD] = "";
                    }

                    if(trim($valueD) == ""){
                        $value['data']['content']['depois'][$keyD] = "";
                    }
                }

                // Pega o nome do responsável pela alteração
                if ($value['data']['user_level'] == "posto") {
                    $sql = "SELECT nome FROM tbl_posto where posto = ".$value['data']['user'];

                    $result = pg_query($con,$sql);
                    $nome = pg_result($result,0,nome);
                } elseif($value['data']['user_level'] == "admin"){
                    $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['user']." and fabrica = ".$login_fabrica;
                    $result = pg_query($con,$sql);
                    $nome = pg_result($result,0,nome_completo);
                }

                $value['user_name'] = $nome;


                /*admin altera antes*/
                $admin_altera_antes = $value['data']['content']['antes']['admin'];
                if(strlen($admin_altera_antes) > 0){

                    $sql_altera_antes = "SELECT nome_completo from tbl_admin where admin = ".$value['data']['content']['antes']['admin']." AND fabrica = $login_fabrica";
                    $res_altera_antes = pg_query($con,$sql_altera_antes);

                    $admin_altera_antes_nome = pg_fetch_result($res_altera_antes, 0, "nome_completo");

                    if($admin_altera_antes_nome != ""){
                        $value['data']['content']['antes']['admin'] = $admin_altera_antes_nome;
                    }
                }

                 /*admin altera depois*/
                $admin_altera_depois = $value['data']['content']['depois']['admin'];
                if(strlen(trim($admin_altera_depois)) > 0){
                    $sql_altera_depois = "SELECT nome_completo from tbl_admin where admin = ".$value['data']['content']['depois']['admin']." AND fabrica = $login_fabrica";
                    $res_altera_depois = pg_query($con,$sql_altera_depois);
                    $admin_altera_depois_nome = pg_fetch_result($res_altera_depois, 0, "nome_completo");
                    if($admin_altera_depois_nome != ""){
                        $value['data']['content']['depois']['admin'] = $admin_altera_depois_nome;
                    }
                }

                /*defeito constatado antes */
                $defeito_constatado_antes = $value['data']['content']['antes']['defeito_constatado'];
                if(strlen($defeito_constatado_antes) > 0){
                    $sql_defeito_antes = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = ".$value['data']['content']['antes']['defeito_constatado']."";
                    $res_defeito_antes = pg_query($con,$sql_defeito_antes);

                    $defeito_constatado_descricao_antes = pg_fetch_result($res_defeito_antes, 0, descricao);
                    if($defeito_constatado_descricao_antes != ""){
                        $value['data']['content']['antes']['defeito_constatado'] = $defeito_constatado_descricao_antes;
                    }
                }

                /*defeito constatado depois */
                $defeito_constatado_depois = $value['data']['content']['depois']['defeito_constatado'];
                if(strlen($defeito_constatado_depois) > 0){
                    $sql_defeito_depois = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = ".$value['data']['content']['depois']['defeito_constatado']."";
                    $res_defeito_depois = pg_query($con,$sql_defeito_depois);

                    $defeito_constatado_descricao_depois = pg_fetch_result($res_defeito_depois, 0, descricao);
                    if($defeito_constatado_descricao_depois != ""){
                        $value['data']['content']['depois']['defeito_constatado'] = $defeito_constatado_descricao_depois;
                    }
                }

                /*servico realizado antes */
                $servico_realizado_antes = $value['data']['content']['antes']['servico_realizado'];
                if(strlen($servico_realizado_antes) > 0){
                    $sql_realizado_antes = "SELECT descricao from tbl_servico_realizado where servico_realizado = ".$value['data']['content']['antes']['servico_realizado']."";
                    $res_realizado_antes = pg_query($con,$sql_realizado_antes);
                    $servico_realizado_descricao_antes = pg_fetch_result($res_realizado_antes, 0, "descricao");
                    if($servico_realizado_descricao_antes != ""){
                        $value['data']['content']['antes']['servico_realizado'] = $servico_realizado_descricao_antes;
                    }
                }

                /*servico realizado depois */
                $servico_realizado_depois = $value['data']['content']['depois']['servico_realizado'];
                if(strlen($servico_realizado_depois) > 0){
                    $sql_realizado_depois = "SELECT descricao from tbl_servico_realizado where servico_realizado = ".$value['data']['content']['depois']['servico_realizado']."";
                    $res_realizado_depois = pg_query($con,$sql_realizado_depois);
                    $servico_realizado_descricao_depois = pg_fetch_result($res_realizado_depois, 0, "descricao");
                    if($servico_realizado_descricao_depois != ""){
                        $value['data']['content']['depois']['servico_realizado'] = $servico_realizado_descricao_depois;
                    }
                }

                /*tipo atendimento antes*/
                $tipo_atendimento_antes = $value['data']['content']['antes']['tipo_atendimento'];
                if(strlen($tipo_atendimento_antes) > 0){
                    $sql_atendimento_antes = "SELECT descricao FROM tbl_tipo_atendimento where tipo_atendimento = ".$value['data']['content']['antes']['tipo_atendimento']." and fabrica = ".$login_fabrica;
                    $res_atendimento_antes = pg_query($con,$sql_atendimento_antes);
                    $tipo_atendimento_antes_descricao = pg_result($res_atendimento_antes,0,descricao);

                    if($tipo_atendimento_antes_descricao != ""){
                        $value['data']['content']['antes']['tipo_atendimento'] = $tipo_atendimento_antes_descricao;
                    }
                }

                /*tipo atendimento depois*/
                $tipo_atendimento_depois = $value['data']['content']['depois']['tipo_atendimento'];
                if(strlen($tipo_atendimento_depois) > 0){
                    $sql_atendimento_depois = "SELECT descricao FROM tbl_tipo_atendimento where tipo_atendimento = ".$value['data']['content']['depois']['tipo_atendimento']." and fabrica = ".$login_fabrica;
                    $res_atendimento_depois = pg_query($con,$sql_atendimento_depois);
                    $tipo_atendimento_depois_descricao = pg_result($res_atendimento_depois,0,descricao);

                    if($tipo_atendimento_depois_descricao != ""){
                        $value['data']['content']['depois']['tipo_atendimento'] = $tipo_atendimento_depois_descricao;
                    }
                }

                /*admin antes */
                $admin_antes = $value['data']['content']['antes']['admin'];
                if(strlen($admin_antes) > 0){
                    $sql_nome_antes = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['antes']['admin']." and fabrica = ".$login_fabrica;
                    $res_nome_antes = pg_query($con,$sql_nome_antes);
                    $nome_antes = pg_result($res_nome_antes,0,nome_completo);

                    if($nome_antes != ""){
                        $value['data']['admin'] = $nome_antes;
                        $value['data']['content']['antes']['admin'] = $nome_antes;
                    }
                }

                /*admin depois */
                $admin_depois = $value['data']['content']['depois']['admin'];
                if(strlen($admin_antes) > 0){
                    $sql_nome_depois = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['depois']['admin']." and fabrica = ".$login_fabrica;
                    $res_nome_depois = pg_query($con,$sql_nome_depois);
                    $nome_depois = pg_result($res_nome_depois,0,nome_completo);

                    if($nome_depois != ""){
                        $value['data']['admin'] = $nome_depois;
                        $value['data']['content']['depois']['admin'] = $nome_depois;
                    }
                }

                /*solucao antes*/
                $solucao_os_antes = $value['data']['content']['antes']['solucao_os'];
                if(strlen($solucao_os_antes) > 0){
                    $sql_solucao_antes = "SELECT descricao FROM tbl_solucao WHERE solucao = $solucao_os_antes AND fabrica = $login_fabrica";
                    $res_solucao_antes = pg_query($con, $sql_solucao_antes);

                    $descricao_solucao_antes = pg_fetch_result($res_solucao_antes, 0, 'descricao');
                    if($descricao_solucao_antes != ""){
                        $value['data']['content']['antes']['solucao_os'] = $descricao_solucao_antes;
                    }
                }

                /*solucao depois*/
                $solucao_os_depois = $value['data']['content']['depois']['solucao_os'];
                if(strlen($solucao_os_depois) > 0){
                    $sql_solucao_depois = "SELECT descricao FROM tbl_solucao WHERE solucao = $solucao_os_depois AND fabrica = $login_fabrica";
                    $res_solucao_depois = pg_query($con, $sql_solucao_depois);

                    $descricao_solucao_depois = pg_fetch_result($res_solucao_depois, 0, 'descricao');
                    if($descricao_solucao_depois != ""){
                        $value['data']['content']['depois']['solucao_os'] = $descricao_solucao_depois;
                    }
                }

                /*status_check antes*/
                $status_check_antes = $value['data']['content']['antes']['status_checkpoint'];
                if(strlen($status_check_antes) > 0){
                    $sql_status_check_antes = "SELECT descricao FROM tbl_status_checkpoint WHERE status_checkpoint = $status_check_antes";
                    $res_status_check_antes = pg_query($con, $sql_status_check_antes);

                    $descricao_check_antes = pg_fetch_result($res_status_check_antes, 0, 'descricao');
                    if($descricao_check_antes != ""){
                        $value['data']['content']['antes']['status_checkpoint'] = $descricao_check_antes;
                    }
                }

                /*status_check depois*/
                $status_check_depois = $value['data']['content']['depois']['status_checkpoint'];
                if(strlen($status_check_depois) > 0){
                    $sql_status_check_depois = "SELECT descricao FROM tbl_status_checkpoint WHERE status_checkpoint = $status_check_depois";
                    $res_status_check_depois = pg_query($con, $sql_status_check_depois);

                    $descricao_check_depois = pg_fetch_result($res_status_check_depois, 0, 'descricao');
                    if($descricao_check_depois != ""){
                        $value['data']['content']['depois']['status_checkpoint'] = $descricao_check_depois;
                    }
                }

                /*fornecedor antes*/
                $fornecedor_antes = $value['data']['content']['antes']['fornecedor'];
                if(strlen($fornecedor_antes) > 0){
                    $sql_fornecedor_antes = "SELECT fornecedor, nome FROM tbl_fornecedor WHERE fornecedor=".$fornecedor_antes;
                    $res_fornecedor_antes = pg_query($con, $sql_fornecedor_antes);
                    $nomeFornecedor = pg_result($res_fornecedor_antes,0,'nome');
                    $fornecedor = pg_result($res_fornecedor_antes,0,'fornecedor');
                    if ($fornecedor && !empty($nomeFornecedor)) {
                        $value['data']['content']['antes']['fornecedor'] = $fornecedor . ' - ' . $nomeFornecedor;
                    }
                }

                /*fornecedor depois*/
                $fornecedor_depois = $value['data']['content']['depois']['fornecedor'];
                if(strlen($fornecedor_depois) > 0){
                    $sql_fornecedor_depois = "SELECT fornecedor, nome FROM tbl_fornecedor WHERE fornecedor=".$fornecedor_depois;
                    $res_fornecedor_depois = pg_query($con,$sql_fornecedor_depois);
                    $nomeFornecedor = pg_result($res_fornecedor_depois,0,'nome');
                    $fornecedor = pg_result($res_fornecedor_depois,0,'fornecedor');

                    if ($fornecedor && !empty($nomeFornecedor)) {
                        $value['data']['content']['depois']['fornecedor'] = $fornecedor . ' - ' . $nomeFornecedor;
                    }
                }

                /*defeito antes*/
                $defeito_antes = $value['data']['content']['antes']['defeito'];
                if(strlen($defeito_antes) > 0){
                    $sql_defeito_antes = "SELECT codigo_defeito, descricao FROM tbl_defeito WHERE defeito=".$defeito_antes." AND fabrica = ".$login_fabrica;
                    $res_defeito_antes = pg_query($con, $sql_defeito_antes);
                    $descricaoDefeito = pg_result($res_defeito_antes,0,'descricao');
                    $defeito = pg_result($res_defeito_antes,0,'codigo_defeito');

                    if ($defeito && !empty($descricaoDefeito)) {
                        $value['data']['content']['antes']['defeito'] = $defeito . ' - ' . $descricaoDefeito;
                    }
                }

                /*defeito depois*/
                $defeito_depois = $value['data']['content']['depois']['defeito'];
                if(strlen($defeito_depois) > 0){
                    $sql_defeito_depois = "SELECT codigo_defeito, descricao FROM tbl_defeito WHERE defeito=".$defeito_depois." AND fabrica = ".$login_fabrica;
                    $res_defeito_depois = pg_query($con,$sql_defeito_depois);
                    $descricaoDefeito = pg_result($res_defeito_depois,0,'descricao');
                    $defeito = pg_result($res_defeito_depois,0,'codigo_defeito');

                    if ($defeito && !empty($descricaoDefeito)) {
                        $value['data']['content']['depois']['defeito'] = $defeito . ' - ' . $descricaoDefeito;
                    }
                }

                /*peca antes*/
                $peca_antes = $value['data']['content']['antes']['peca'];
                if(strlen($peca_antes) > 0){
                    $sql_peca_antes = "SELECT peca, descricao,referencia FROM tbl_peca WHERE peca=".$peca_antes." AND fabrica = ".$login_fabrica;
                    $res_peca_antes = pg_query($con, $sql_peca_antes);
                    $descricaoPeca = pg_result($res_peca_antes,0,'descricao');
                    $referencia = pg_result($res_peca_antes,0,'referencia');

                    if ($referencia && !empty($descricaoPeca)) {
                        $value['data']['content']['antes']['peca'] = $referencia . ' - ' . $descricaoPeca;
                    }
                }

                /*peca depois*/
                $peca_depois = $value['data']['content']['depois']['peca'];
                if(strlen($peca_depois) > 0){
                    $sql_peca_depois = "SELECT peca, descricao,referencia FROM tbl_peca WHERE peca=".$peca_depois." AND fabrica = ".$login_fabrica;
                    $res_peca_depois = pg_query($con, $sql_peca_depois);
                    $descricaoPeca = pg_result($res_peca_depois,0,'descricao');
                    $referencia = pg_result($res_peca_depois,0,'referencia');

                    if ($referencia && !empty($descricaoPeca)) {
                        $value['data']['content']['depois']['peca'] = $referencia . ' - ' . $descricaoPeca;
                    }
                }

                $array_antes = $value['data']['content']['antes'];
                $array_depois = $value['data']['content']['depois'];

                $alteracoes = array();
                if ($value['data']['action'] != "INSERT") {
                    foreach($array_antes AS $keyA => $valueA){
                        if($valueA != $array_depois[$keyA]){
                            if ($value['data']['action'] == "UPDATE") {
                                $alteracoes['peca'] = $array_antes['peca'];
                            }
                            $alteracoes[$keyA] = $array_depois[$keyA];
                        }
                    }
                } else {
                    foreach ($value['data']['content']['depois'] as $k => $val) {
                        if ($val == "") {
                            $keysUnset[] = $k;
                        }
                    }
                    foreach ($keysUnset as $k) {
                        unset($value['data']['content']['depois'][$k]);
                    }
                    $alteracoes = $value['data']['content']['depois'];
                }
                $value['data']['alteracoes'] = $alteracoes;
                $res_result[$key] = $value;
            }

        } else {
            $error2 = "Nenhum log encontrado";
        }
    } catch(Exception $ex) {
        $error = $ex->getMessage();
    }

    $res_result = array_merge($res, $res_result);
    #print_r($teste);exit;

    $actionTraduzida = array ('UPDATE' => 'Alterado','INSERT' => 'Inserido','DELETE' => 'Deletado');
    if (strlen($error2) == 0) {
    ?>
<!--     <style>
        .log-ul{
            list-style: none;
            border:solid 1px #eeeeee;
            margin-left: 0px;
        }
        .titulo-log{
            background:#273975;color:#ffffff;padding: 3px 10px 3px 10px;
        }
        .log-li{
            padding-left: 10px;
            border-bottom: solid 1px #eeeeee;
        }
    </style> -->
    </div>
    <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="5">Logs de Alteração OS</th>
            </tr>
            <tr class="titulo_coluna">
                <th width="20%">Usuário</th>
                <?php if (in_array($login_fabrica, array(91))) {?>
                <th>Ação</th>
                <?php }?>
                <th width="17%">Horário</th>
                <th>Antes</th>
                <th>Depois</th>
                <!-- <th>Alterações</th> -->
            </tr>
        </thead>
        <tbody>
    <?php
        foreach ($res_result as $key => $value) {
            $atualizacao = (!empty($value['data']['content']['depois']['data_modificacao'])) ? $value['data']['content']['depois']['data_modificacao'] : $value['data']['content']['depois']['data_atualizacao'];
            if ($atualizacao == "") {
                $atualizacao = date("Y-m-d H:i:s",$value['data']['created']);
                //Ajuste de time da backend2 para o Pos-Venda
                $atualizacao =  strtotime($atualizacao." -10 minutes");
                #echo "b";
            } else {
                #echo 'a';
                $atualizacao = strtotime($atualizacao);
            }
            $keys = array_keys($value['data']['alteracoes']);

            if(count($keys)>0){
            echo "<tr>";
            if ($value['user_name'] == "") {
                echo "<td>".$value['data']['content']['depois']['admin']."</td>";
            } else {
                echo "<td>".$value['user_name']."</td>";
            }
            if (in_array($login_fabrica, array(91))) {
            echo "<td>".$actionTraduzida[$value['data']['action']]."</td>";
            }
            echo "<td>".date("d-m-Y H:i:s",$atualizacao)."</td>";
            if(count($keys)>0){
            echo "<td>";
            echo "  <ul class='log-ul'>";
            foreach ($keys as $keyname) {
                echo "<li><b>".strtoupper(str_replace("_", " ", $keyname)).":</b> ".$value['data']['content']['antes'][$keyname]."</li>";
            }
            echo "</ul>";
            echo "</td>";
            } else {
            echo "<td colspan='2'>";
            echo "<p align='center'>Registro gravado sem alterações</p>";
            echo "</td>";
            }
            if (count($keys)>0) {
            echo "<td>";
            echo "  <ul class='log-ul'>";
                foreach ($value['data']['alteracoes'] as $key => $alt) {
                    echo "<li><b>".strtoupper(str_replace("_", " ",$key)).":</b> ".$alt."</li>";
                }
            echo "</ul>";
            echo "</td>";
            }
            echo "</tr>";
        }}
        ?>
        </tbody>
    </table>
    <?php } else { ?>
        <div class='container'>
            <div class='row-fluid'>
                <div class='span12'>
                    <div class="alert">
                      Nenhum Registro de Log encontrado para Log de Itens.
                    </div>
                </div>
            </div>
        </div>
    <?php }
############## FIM RELATORIO LOG OS_ITEM ##############
 include 'rodape.php';?>
