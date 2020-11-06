<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
#$admin_privilegios="financeiro,gerencia,call_center";

include 'autentica_usuario.php';
include 'funcoes.php';

require __DIR__.'/classes/api/Client.php';
use api\Client;
$tabela = $_GET['parametro'];
$id        = $_GET['id'];

$layout_menu = "gerencia";
$title = "RELATÓRIO DE LOG DE ALTERAÇÃO";
include 'cabecalho_new.php';

$plugins = array(
    "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';

$client = Client::makeTelecontrolClient("auditor","auditor");
$client->urlParams = array(
    "aplication" => "da82d339d0552bcfcf10188a36125270",
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
                                                            #OS
                                                            'data_abertura_os' => $value['data']['content']['antes']['os']['data_abertura'],
                                                            'tipo_atendimento' => $value['data']['content']['antes']['os']['tipo_atendimento'],
                                                            'nota_fiscal' => $value['data']['content']['antes']['os']['nota_fiscal'],
                                                            'data_compra' => $value['data']['content']['antes']['os']['data_compra'],
                                                            'defeito_reclamado' => $value['data']['content']['antes']['os']['defeito_reclamado'],
                                                            'aparencia_produto' => $value['data']['content']['antes']['os']['aparencia_produto'],
                                                            'acessorios' => $value['data']['content']['antes']['os']['acessorios'],
                                                            'observacoes_os' => $value['data']['content']['antes']['os']['observacoes'],
                                                            #'qtde_km' => $value['data']['content']['antes']['os']['qtde_km'],
                                                            'solucao' => $value['data']['content']['antes']['os']['solucao'],
                                                            'cortesia' => $value['data']['content']['antes']['os']['cortesia'],
                                                            'consumidor_revenda' => $value['data']['content']['antes']['os']['consumidor_revenda'],
                                                            'motivo_atraso_os' => $value['data']['content']['antes']['os']['motivo_atraso'],
                                                            'pedagio' => $value['data']['content']['antes']['os']['pedagio'],
                                                            'natureza_operacao' => $value['data']['content']['antes']['os']['natureza_operacao'],
                                                            #'fora_garantia' => $value['data']['content']['antes']['os']['fora_garantia'],
                                                            #'tipo_produto' => $value['data']['content']['antes']['os']['tipo_produto'],
                                                            'nf_envio' => $value['data']['content']['antes']['os']['nf_envio'],
                                                            'data_nf_envio' => $value['data']['content']['antes']['os']['data_nf_envio'],
                                                            'valor_nf_envio' => $value['data']['content']['antes']['os']['valor_nf_envio'],
                                                            'nf_retorno' => $value['data']['content']['antes']['os']['nf_retorno'],
                                                            'data_nf_retorno' => $value['data']['content']['antes']['os']['data_nf_retorno'],
                                                            'valor_nf_retorno' => $value['data']['content']['antes']['os']['valor_nf_retorno'],
                                                            'nota_fiscal_mo' => $value['data']['content']['antes']['os']['nota_fiscal_mo'],
                                                            'data_nota_fiscal_mo' => $value['data']['content']['antes']['os']['data_nota_fiscal_mo'],
                                                            'valor_nota_fiscal_mo' => $value['data']['content']['antes']['os']['valor_nota_fiscal_mo'],
                                                            'nota_fiscal_peca' => $value['data']['content']['antes']['os']['nota_fiscal_peca'],
                                                            'data_nota_fiscal_peca' => $value['data']['content']['antes']['os']['data_nota_fiscal_peca'],
                                                            'valor_nota_fiscal_peca' => $value['data']['content']['antes']['os']['valor_nota_fiscal_peca'],
                                                            'hd_chamado' => $value['data']['content']['antes']['os']['hd_chamado'],
                                                            #'admin_altera' => $value['data']['content']['antes']['os']['admin_altera'],

                                                            #Consumidor
                                                            'nome_consumidor' => $value['data']['content']['antes']['consumidor']['nome'],
                                                            'cpf_consumidor' => $value['data']['content']['antes']['consumidor']['cpf'],
                                                            'cep_consumidor' => $value['data']['content']['antes']['consumidor']['cep'],
                                                            'estado_consumidor' => $value['data']['content']['antes']['consumidor']['estado'],
                                                            'cidade_consumidor' => $value['data']['content']['antes']['consumidor']['cidade'],
                                                            'bairro_consumidor' => $value['data']['content']['antes']['consumidor']['bairro'],
                                                            'endereco_consumidor' => $value['data']['content']['antes']['consumidor']['endereco'],
                                                            'numero_consumidor' => $value['data']['content']['antes']['consumidor']['numero'],
                                                            'complemento_consumidor' => $value['data']['content']['antes']['consumidor']['complemento'],
                                                            'telefone_consumidor' => $value['data']['content']['antes']['consumidor']['telefone'],
                                                            'celular_consumidor' => $value['data']['content']['antes']['consumidor']['celular'],
                                                            'email_consumidor' => $value['data']['content']['antes']['consumidor']['email'],

                                                            #Revenda
                                                            /*
                                                            'nome_revenda' => $value['data']['content']['antes']['revenda']['nome'],
                                                            'cnpj_revenda' => $value['data']['content']['antes']['revenda']['cnpj'],
                                                            'cep_revenda' => $value['data']['content']['antes']['revenda']['cep'],
                                                            'estado_revenda' => $value['data']['content']['antes']['revenda']['estado'],
                                                            'cidade_revenda' => $value['data']['content']['antes']['revenda']['cidade'],
                                                            'bairro_revenda' => $value['data']['content']['antes']['revenda']['bairro'],
                                                            'endereco_revenda' => $value['data']['content']['antes']['revenda']['endereco'],
                                                            'numero_revenda' => $value['data']['content']['antes']['revenda']['numero'],
                                                            'complemento_revenda' => $value['data']['content']['antes']['revenda']['complemento'],
                                                            'telefone_revenda' => $value['data']['content']['antes']['revenda']['telefone'],
                                                            */
                                                            #Posto
                                                            'codigo_posto' => $value['data']['content']['antes']['posto']['codigo'],
                                                            'nome_posto' => $value['data']['content']['antes']['posto']['nome'],

                                                            #Produto
                                                            'referencia_produto' => $value['data']['content']['antes']['produto']['referencia'],
                                                            'descricao_produto' => $value['data']['content']['antes']['produto']['descricao'],
                                                            'voltagem_produto' => $value['data']['content']['antes']['produto']['voltagem'],
                                                            'serie_produto' => $value['data']['content']['antes']['produto']['serie'],
                                                            'defeito_constatado_produto' => $value['data']['content']['antes']['produto']['defeito_constatado'],
                                                            'defeitos_constatados_multiplos' => $value['data']['content']['antes']['produto']['defeitos_constatados_multiplos'],
                                                        );

                $value['data']['content']['depois'] = array(
                                                            #OS
                                                            'data_abertura_os' => $value['data']['content']['depois']['os']['data_abertura'],
                                                            'tipo_atendimento' => $value['data']['content']['depois']['os']['tipo_atendimento'],
                                                            'nota_fiscal' => $value['data']['content']['depois']['os']['nota_fiscal'],
                                                            'data_compra' => $value['data']['content']['depois']['os']['data_compra'],
                                                            'defeito_reclamado' => $value['data']['content']['depois']['os']['defeito_reclamado'],
                                                            'aparencia_produto' => $value['data']['content']['depois']['os']['aparencia_produto'],
                                                            'acessorios' => $value['data']['content']['depois']['os']['acessorios'],
                                                            'observacoes_os' => $value['data']['content']['depois']['os']['observacoes'],
                                                            #'qtde_km' => $value['data']['content']['depois']['os']['qtde_km'],
                                                            'solucao' => $value['data']['content']['depois']['os']['solucao'],
                                                            'cortesia' => $value['data']['content']['depois']['os']['cortesia'],
                                                            'consumidor_revenda' => $value['data']['content']['depois']['os']['consumidor_revenda'],
                                                            'motivo_atraso' => $value['data']['content']['depois']['os']['motivo_atraso'],
                                                            'pedagio' => $value['data']['content']['depois']['os']['pedagio'],
                                                            'natureza_operacao' => $value['data']['content']['depois']['os']['natureza_operacao'],
                                                            #'fora_garantia' => $value['data']['content']['depois']['os']['fora_garantia'],
                                                            #'tipo_produto' => $value['data']['content']['depois']['os']['tipo_produto'],
                                                            'nf_envio' => $value['data']['content']['depois']['os']['nf_envio'],
                                                            'data_nf_envio' => $value['data']['content']['depois']['os']['data_nf_envio'],
                                                            'valor_nf_envio' => $value['data']['content']['depois']['os']['valor_nf_envio'],
                                                            'nf_retorno' => $value['data']['content']['depois']['os']['nf_retorno'],
                                                            'data_nf_retorno' => $value['data']['content']['depois']['os']['data_nf_retorno'],
                                                            'valor_nf_retorno' => $value['data']['content']['depois']['os']['valor_nf_retorno'],
                                                            'nota_fiscal_mo' => $value['data']['content']['depois']['os']['nota_fiscal_mo'],
                                                            'data_nota_fiscal_mo' => $value['data']['content']['depois']['os']['data_nota_fiscal_mo'],
                                                            'valor_nota_fiscal_mo' => $value['data']['content']['depois']['os']['valor_nota_fiscal_mo'],
                                                            'nota_fiscal_peca' => $value['data']['content']['depois']['os']['nota_fiscal_peca'],
                                                            'data_nota_fiscal_peca' => $value['data']['content']['depois']['os']['data_nota_fiscal_peca'],
                                                            'valor_nota_fiscal_peca' => $value['data']['content']['depois']['os']['valor_nota_fiscal_peca'],
                                                            'hd_chamado' => $value['data']['content']['depois']['os']['hd_chamado'],
                                                            #'admin_altera' => $value['data']['content']['depois']['os']['admin_altera'],

                                                            #Consumidor
                                                            'nome_consumidor' => $value['data']['content']['depois']['consumidor']['nome'],
                                                            'cpf_consumidor' => $value['data']['content']['depois']['consumidor']['cpf'],
                                                            'cep_consumidor' => $value['data']['content']['depois']['consumidor']['cep'],
                                                            'estado_consumidor' => $value['data']['content']['depois']['consumidor']['estado'],
                                                            'cidade_consumidor' => $value['data']['content']['depois']['consumidor']['cidade'],
                                                            'bairro_consumidor' => $value['data']['content']['depois']['consumidor']['bairro'],
                                                            'endereco_consumidor' => $value['data']['content']['depois']['consumidor']['endereco'],
                                                            'numero_consumidor' => $value['data']['content']['depois']['consumidor']['numero'],
                                                            'complemento_consumidor' => $value['data']['content']['depois']['consumidor']['complemento'],
                                                            'telefone_consumidor' => $value['data']['content']['depois']['consumidor']['telefone'],
                                                            'celular_consumidor' => $value['data']['content']['depois']['consumidor']['celular'],
                                                            'email_consumidor' => $value['data']['content']['depois']['consumidor']['email'],

                                                            #Revenda
                                                            /*
                                                            'nome_revenda' => $value['data']['content']['depois']['revenda']['nome'],
                                                            'cnpj_revenda' => $value['data']['content']['depois']['revenda']['cnpj'],
                                                            'cep_revenda' => $value['data']['content']['depois']['revenda']['cep'],
                                                            'estado_revenda' => $value['data']['content']['depois']['revenda']['estado'],
                                                            'cidade_revenda' => $value['data']['content']['depois']['revenda']['cidade'],
                                                            'bairro_revenda' => $value['data']['content']['depois']['revenda']['bairro'],
                                                            'endereco_revenda' => $value['data']['content']['depois']['revenda']['endereco'],
                                                            'numero_revenda' => $value['data']['content']['depois']['revenda']['numero'],
                                                            'complemento_revenda' => $value['data']['content']['depois']['revenda']['complemento'],
                                                            'telefone_revenda' => $value['data']['content']['depois']['revenda']['telefone'],
                                                            */
                                                            #Posto
                                                            'codigo_posto' => $value['data']['content']['depois']['posto']['codigo'],
                                                            'nome_posto' => $value['data']['content']['depois']['posto']['nome'],

                                                            #Produto
                                                            'referencia_produto' => $value['data']['content']['depois']['produto']['referencia'],
                                                            'descricao_produto' => $value['data']['content']['depois']['produto']['descricao'],
                                                            'voltagem_produto' => $value['data']['content']['depois']['produto']['voltagem'],
                                                            'serie_produto' => $value['data']['content']['depois']['produto']['serie'],
                                                            'defeito_constatado_produto' => $value['data']['content']['depois']['produto']['defeito_constatado'],
                                                            'defeitos_constatados_multiplos' => $value['data']['content']['depois']['produto']['defeitos_constatados_multiplos'],
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
                $admin_altera_antes = $value['data']['content']['antes']['admin_altera'];
                if(strlen($admin_altera_antes) > 0){
                    $sql_altera_antes = "SELECT nome_completo from tbl_admin where admin = ".$value['data']['content']['antes']['admin_altera']." AND fabrica = $login_fabrica";
                    $res_altera_antes = pg_query($con,$sql_altera_antes);
                    $admin_altera_antes_nome = pg_fetch_result($res_altera_antes, 0, "nome_completo");

                    if($admin_altera_antes_nome != ""){
                        $value['data']['content']['antes']['admin_altera'] = $admin_altera_antes_nome;
                    }
                }

                /*admin altera depois*/
                $admin_altera_depois = $value['data']['content']['depois']['admin_altera'];
                if(strlen(trim($admin_altera_depois)) > 0){
                    $sql_altera_depois = "SELECT nome_completo from tbl_admin where admin = ".$value['data']['content']['depois']['admin_altera']." AND fabrica = $login_fabrica";
                    $res_altera_depois = pg_query($con,$sql_altera_depois);

                    $admin_altera_depois_nome = pg_fetch_result($res_altera_depois, 0, "nome_completo");
                    if($admin_altera_depois_nome != ""){
                        $value['data']['content']['depois']['admin_altera'] = $admin_altera_depois_nome;
                    }
                }

                /*consumidor cpf depois*/
                $consumidor_cpf_depois = $value['data']['content']['depois']['cpf_consumidor'];
                $valida_cpf_cnpj = preg_replace("/\D/","",$consumidor_cpf_depois);
                $value['data']['content']['depois']['cpf_consumidor'] = $valida_cpf_cnpj;

                $consumidor_cep_depois = $value['data']['content']['depois']['cep_consumidor'];
                $valida_cep = preg_replace("/\D/","",$consumidor_cep_depois);
                $value['data']['content']['depois']['cep_consumidor'] = $valida_cep;

                /*defeito constatado antes */
                $defeito_constatado_antes = $value['data']['content']['antes']['defeito_constatado_produto'];
                if(strlen($defeito_constatado_antes) > 0){
                    $sql_defeito_antes = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = ".$value['data']['content']['antes']['defeito_constatado_produto']."";
                    $res_defeito_antes = pg_query($con,$sql_defeito_antes);
                    echo $sql_defeito_antes;exit;
                    $defeito_constatado_descricao_antes = pg_fetch_result($res_defeito_antes, 0, descricao);
                    if($defeito_constatado_descricao_antes != ""){
                        $value['data']['content']['antes']['defeito_constatado'] = $defeito_constatado_descricao_antes;
                    }
                }

                /*defeito constatado depois */
                $defeito_constatado_depois = $value['data']['content']['depois']['defeito_constatado_produto'];
                if(strlen($defeito_constatado_depois) > 0){
                    $sql_defeito_depois = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = ".$value['data']['content']['depois']['defeito_constatado_produto']."";
                    $res_defeito_depois = pg_query($con,$sql_defeito_depois);

                    $defeito_constatado_descricao_depois = pg_fetch_result($res_defeito_depois, 0, descricao);
                    if($defeito_constatado_descricao_depois != ""){
                        $value['data']['content']['depois']['defeito_constatado'] = $defeito_constatado_descricao_depois;
                    }
                }

                /*quantidade km*/
                $qtde_km_antes = $value['data']['content']['antes']['qtde_km'];
                $qtde_km_depois = $value['data']['content']['depois']['qtde_km'];

                if(strlen($qtde_km_antes) == 0 AND $qtde_km_depois == 0){
                    unset($value['data']['content']['antes']['qtde_km']);
                    unset($value['data']['content']['depois']['qtde_km']);
                }

                /*defeito constatado multiplos antes */
                $defeito_constatado_multiplos_antes = $value['data']['content']['antes']['defeitos_constatados_multiplos'];
                if(strlen($defeito_constatado_multiplos_antes) > 0){
                    $sql_defeito_antes = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = ".$value['data']['content']['antes']['defeitos_constatados_multiplos']."";
                    $res_defeito_antes = pg_query($con,$sql_defeito_antes);

                    $defeito_constatado_multiplos_antes = pg_fetch_result($res_defeito_antes, 0, descricao);
                    if($defeito_constatado_multiplos_antes != ""){
                        $value['data']['content']['antes']['defeitos_constatados_multiplos'] = $defeito_constatado_multiplos_antes;
                    }
                }

                /*defeito constatado multiplos depois */
                $defeito_constatado_multiplos_depois = $value['data']['content']['depois']['defeitos_constatados_multiplos'];
                if(strlen($defeito_constatado_multiplos_depois) > 0){
                    $sql_defeito_depois = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = ".$value['data']['content']['depois']['defeitos_constatados_multiplos']."";
                    $res_defeito_depois = pg_query($con,$sql_defeito_depois);

                    $defeito_constatado_multiplos_depois = pg_fetch_result($res_defeito_depois, 0, descricao);
                    if($defeito_constatado_multiplos_depois != ""){
                        $value['data']['content']['depois']['defeitos_constatados_multiplos'] = $defeito_constatado_multiplos_depois;
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
        $error = "Nenhum log encontrado";
    }
} catch(Exception $ex) {
    $error = $ex->getMessage();
}

if (strlen($error) == 0) {
?>
<script type="text/javascript">
    function mostrar_os(){
        if(!$('.tabela_os').is(':visible')){
            $("#tabela_os > thead").find('.tabela_os').show();
            $("#tabela_os").find('tbody').show();
        }else{
            $("#tabela_os > thead").find('.tabela_os').hide();
            $("#tabela_os").find('tbody').hide();
        }
    }
    function mostrar_pecas(){
        if(!$('.tabela_pecas').is(':visible')){
            $("#tabela_pecas > thead").find('.tabela_pecas').show();
            $("#tabela_pecas").find('tbody').show();
        }else{
            $("#tabela_pecas > thead").find('.tabela_pecas').hide();
            $("#tabela_pecas").find('tbody').hide();
        }
    }
    function mostrar_pecas_excluidas(){
        if(!$('.tabela_pecas_excluidas').is(':visible')){
            $("#tabela_pecas_excluidas > thead").find('.tabela_pecas_excluidas').show();
            $("#tabela_pecas_excluidas").find('tbody').show();
        }else{
            $("#tabela_pecas_excluidas > thead").find('.tabela_pecas_excluidas').hide();
            $("#tabela_pecas_excluidas").find('tbody').hide();
        }
    }
</script>
<style>
    .log-ul{
        list-style: none;
        /*border:solid 1px #eeeeee;*/
        margin-left: 0px;
    }
    .titulo-log{
        background:#273975;color:#ffffff;padding: 3px 10px 3px 10px;
    }
    .log-li{
        padding-left: 10px;
        border-bottom: solid 1px #eeeeee;
    }
    .icon-white{
        mouse
    }
</style>
</div>
<table class="table table-striped table-bordered table-hover" style='width: 1000px;' id='tabela_os'>
    <thead>
        <tr class="titulo_tabela" '>
            <th colspan="5"><i class='icon-plus icon-white' style='float: left;' onclick="mostrar_os();"></i> Logs de Alteração OS</th>
        </tr>
        <tr class="titulo_coluna tabela_os" style="display: none;">
            <th width="20%">Usuário</th>
            <th width="17%">Horário</th>
            <th>Antes</th>
            <th>Depois</th>
        </tr>
    </thead>
    <tbody style='display: none;'>
<?php
    foreach ($res_result as $key => $value) {
       $atualizacao = (!empty($value['data']['content']['depois']['data_modificacao'])) ? $value['data']['content']['depois']['data_modificacao'] : $value['data']['content']['depois']['data_atualizacao'];
        if ($atualizacao == "") {
            $atualizacao = date("Y-m-d H:i:s",$value['data']['created']);
            //Ajuste de time da backend2 para o Pos-Venda
            $atualizacao =  strtotime($atualizacao." -10 minutes");
        } else {
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
                  Nenhum Registro de Log encontrado.
                </div>
            </div>
        </div>
    </div>
<?php
}
if(strlen($error) == 0){
    $res_pecas = $client->get();
    if(count($res_pecas)){
?>
        <table class="table table-striped table-bordered table-hover" id="tabela_pecas" style='width: 1000px;'>
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="5"><i class='icon-plus icon-white' style='float: left;' onclick="mostrar_pecas();"></i>Logs de Alteração Peças</th>
                </tr>
                <tr class="titulo_coluna tabela_pecas" style="display: none;">
                    <th width="20%">Usuário</th>
                    <th width="17%">Horário</th>
                    <th>Antes</th>
                    <th>Depois</th>
                </tr>
            </thead>
            <tbody style='display: none;'>
<?php
        foreach ($res_pecas as $key_pecas => $value_pecas) {
            /*retira array ['__modelo']*/
            unset($value_pecas['data']['content']['depois']['produto_pecas']['__modelo']);

            /*remove arrays peças antes*/
            $count_antes = count($value_pecas['data']['content']['antes']['produto_pecas']);
            for ($i=0; $i <= $count_antes; $i++) {
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['os_item']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['id']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['defeito_peca']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['parametros_adicionais']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['pedido']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['admin']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['troca']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['valor']);
                unset($value_pecas['data']['content']['antes']['produto_pecas'][$i]['valor_total']);
            }


            /*remove arrays peças depois*/
            $count_depois = count($value_pecas['data']['content']['depois']['produto_pecas']);
            for ($i=0; $i <= $count_depois; $i++) {
                $value_pecas['data']['content']['depois']['produto_pecas'];
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['id']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['os_item']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['defeito_peca']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['parametros_adicionais']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['pedido']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['admin']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['troca']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['valor']);
                unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]['valor_total']);
                if(!$value_pecas['data']['content']['depois']['produto_pecas'][$i]['referencia']){
                    unset($value_pecas['data']['content']['depois']['produto_pecas'][$i]);
                }
                // $sql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = ".$value_pecas['data']['content']['depois']['produto_pecas'][$i]['servico_realizado']."WHERE fabrica = $login_fabrica";
                // $res = pg_query($con,$sql);
                // echo $sql;
            }

            /*pegas peças antes e depois*/
            $pecas_antes = $value_pecas['data']['content']['antes']['produto_pecas'];
            $pecas_depois = $value_pecas['data']['content']['depois']['produto_pecas'];

            for($y=0; $y < $count_depois; $y++) {
                $keys_d = array_diff_assoc($pecas_depois[$y], $pecas_antes[$y]);
                if(count($keys_d) > 0){
                   foreach ($keys_d as $keyX => $valueX) {
                       $pecas_depois[$y][$keyX] = $valueX;
                   }
                }
            }


            /*verefica diferença em antes/depois*/
            if($value_pecas['data']['content']['depois']['os']['admin_altera']){
                $id_altera = $value_pecas['data']['content']['depois']['os']['admin_altera'];
                $sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $id_altera ";
                $res = pg_query($con,$sql);
                $nome_altera = pg_fetch_result($res, 0, 'nome_completo');
            }

            /*monta tabelas*/
            for ($x=0; $x < count($pecas_depois); $x++) {
                $keys_d = array_diff_assoc($pecas_depois[$x], $pecas_antes[$x]);
                $style='';
                if(count($keys_d) == 0 AND count($pecas_antes[$x]) > 0){
                    $style = "style='display:none;'";
                }
            }
            if(count($pecas_depois)>0){
                $atualizacao_pecas = date("Y-m-d H:i:s",$value_pecas['data']['created']);
                //Ajuste de time da backend2 para o Pos-Venda
                $atualizacao_pecas =  strtotime($atualizacao_pecas." -14 minutes");
                echo "<tr $style>";
                    echo "<td>".$nome_altera."</td>";
                    echo "<td>".date("d-m-Y H:i:s",$atualizacao_pecas)."</td>";
                        echo "<td>";
                            echo "<ul class='log-ul'>";
                                    for ($x=0; $x < count($pecas_depois); $x++) {
                                        if(count($pecas_depois[$x]) > count($pecas_antes[$x])){
                                           echo "<li>NOVA PEÇA INSERIDA</li>";
                                           echo "<br><br><br><br><br>";
                                        }else{
                                            $keys_a = array_diff_assoc($pecas_depois[$x], $pecas_antes[$x]);
                                            if(count($keys_a) == 0){
                                                continue;
                                            }
                                            foreach ($pecas_antes[$x] as $key_name_a =>$value_name_a) {

                                                if($key_name_a == 'servico_realizado'){
                                                    $sql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $value_name_a AND fabrica = $login_fabrica";
                                                    $res = pg_query($con, $sql);
                                                    $value_name_a = pg_fetch_result($res, 0, 'descricao');
                                                }

                                               echo "<li><b>".strtoupper(str_replace("_", " ", $key_name_a)).":</b> ".$value_name_a."</li>";
                                            }
                                            echo "----------------------";
                                        }
                                    }
                            echo "</ul>";
                        echo "</td>";
                        echo "<td>";
                            echo "<ul class='log-ul'>";
                                for ($x=0; $x < count($pecas_depois); $x++) {
                                    $keys_d = array_diff_assoc($pecas_depois[$x], $pecas_antes[$x]);
                                    if(count($keys_d) == 0 AND count($pecas_antes[$x]) > 0){
                                        continue;
                                    }
                                    foreach ($pecas_depois[$x] as $key_name_d =>$value_name_d) {
                                        if($key_name_d == 'servico_realizado'){
                                            $sql = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $key_name_d AND fabrica = $login_fabrica";
                                            $res = pg_query($con, $sql);
                                            $value_name_d = pg_fetch_result($res, 0, 'descricao');
                                        }
                                       echo "<li><b>".strtoupper(str_replace("_", " ", $key_name_d)).":</b> ".$value_name_d."</li>";
                                    }
                                    echo "----------------------";
                                }
                            echo "</ul>";
                        echo "</td>";
                echo "</tr>";
            }
        }
?>
            </tbody>
        </table>
<?php
    }
}
if (strlen($error) == 0) {
    $res_pecas_excluidas = $client->get();
    if(count($res_pecas_excluidas)){
?>
        <table class="table table-striped table-bordered table-hover" id="tabela_pecas_excluidas" style='width: 1000px;'>
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="5"><i class='icon-plus icon-white' style='float: left;' onclick="mostrar_pecas_excluidas();"></i>Peças Excluidas da OS</th>
                </tr>
                <tr class="titulo_coluna tabela_pecas_excluidas" style="display: none;">
                    <th width="20%">Usuário</th>
                    <th width="17%">Horário</th>
                    <th>Antes</th>
                    <th>Depois</th>
                </tr>
            </thead>
            <tbody style='display: none;'>
<?php
        foreach ($res_pecas_excluidas as $key_pecas_ex => $value_pecas_ex) {
            /*remove arrays antes*/
            $count_antes = count($value_pecas_ex['data']['content']['antes']['produto_pecas']);
            for ($i=0; $i <= $count_antes; $i++) {
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['os_item']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['id']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['defeito_peca']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['parametros_adicionais']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['pedido']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['admin']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['troca']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['valor']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['valor_total']);
                unset($value_pecas_ex['data']['content']['antes']['produto_pecas'][$i]['servico_realizado']);
            }

            /*remove arrays peças_antes depois*/
            $count_pecas_antes = count($value_pecas_ex['data']['content']['pecas_antes']['pecas_antes']);
            for ($i=0; $i <= $count_pecas_antes; $i++) {
                unset($value_pecas_ex['data']['content']['pecas_antes']['pecas_antes'][$i]['id']);

                if(!$value_pecas_ex['data']['content']['pecas_antes']['pecas_antes'][$i]['referencia']){
                    unset($value_pecas_ex['data']['content']['pecas_antes']['pecas_antes'][$i]);
                }
            }

            /*pega valores antes e pecas_antes*/
            $antes = $value_pecas_ex['data']['content']['antes']['produto_pecas'];
            $pecas_antes = $value_pecas_ex['data']['content']['pecas_antes']['pecas_antes'];

            /* utiliza mesmo dados alteração OS*/
            $atualizacao = (!empty($value['data']['content']['depois']['data_modificacao'])) ? $value['data']['content']['depois']['data_modificacao'] : $value['data']['content']['depois']['data_atualizacao'];
            if ($atualizacao == "") {
                $atualizaatualizacaocao_pecas = date("Y-m-d H:i:s",$value['data']['created']);
                //Ajuste de time da backend2 para o Pos-Venda
                $atualizacao =  strtotime($atualizacao." -10 minutes");
            } else {
                $atualizacao = strtotime($atualizacao);
            }

            /*verefica diferença em antes/depois*/
            if($value_pecas['data']['content']['depois']['os']['admin_altera']){
                $id_altera = $value_pecas['data']['content']['depois']['os']['admin_altera'];
                $sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $id_altera ";
                $res = pg_query($con,$sql);
                $nome_altera = pg_fetch_result($res, 0, 'nome_completo');
            }

            /*monta tabelas*/
            // echo "<pre>Antes===>";
            // print_r($antes);
            // echo "<br><br>pecas_antes ==>";
            // print_r($pecas_antes);
            for ($x=0; $x < count($pecas_antes); $x++) {
                $style = "";
                if(count($pecas_antes[$x]) <= count($antes[$x])){
                   $style = "style='display:none;'";
                }
            }

            $diff_array = array_diff_assoc($pecas_antes, $antes);
            if(!count($diff_array)){
                  $style = "style='display:none;'";
            }

            // for ($x=0; $x < count($pecas_depois); $x++) {
            //     $keys_d = array_diff_assoc($pecas_depois[$x], $pecas_antes[$x]);
            //     $style='';
            //     if(count($keys_d) == 0 AND count($pecas_antes[$x]) > 0){
            //         $style = "style='display:none;'";
            //     }
            // }






            if(count($antes)>0){
                $atualizacao_pecas_excluidas = date("Y-m-d H:i:s",$value_pecas_ex['data']['created']);
                //Ajuste de time da backend2 para o Pos-Venda
                $atualizacao_pecas_excluidas =  strtotime($atualizacao_pecas_excluidas." -10 minutes");
                echo "<tr $style>";
                    echo "<td>".$nome_altera."</td>";
                    echo "<td>".date("d-m-Y H:i:s",$atualizacao_pecas_excluidas)."</td>";
                        echo "<td>";
                            echo "  <ul class='log-ul'>";
                                    for ($x=0; $x < count($pecas_antes); $x++) {
                                        if(count($pecas_antes[$x]) > count($antes[$x])){
                                           echo "<li>PEÇAS EXCLUIDAS</li>";
                                           echo "<br><br><br><br>";
                                        }
                                    }
                            echo "</ul>";
                        echo "</td>";
                        echo "<td>";
                            echo "<ul class='log-ul'>";
                                $keys_d = array_diff_assoc($pecas_antes, $antes);
                                foreach ($keys_d as $key_name_d =>$value_name_d) {
                                    foreach ($value_name_d as $keyX => $valueX) {
                                        echo "<li><b>".strtoupper(str_replace("_", " ", $keyX)).":</b> ".$valueX."</li>";
                                    }
                                    echo "----------------------";
                                }
                            echo "</ul>";
                        echo "</td>";
                echo "</tr>";
            }
        }
?>
            </tbody>
        </table>
<?php
    }
}



include 'rodape.php';?>
