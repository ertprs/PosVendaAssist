<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$grava_defeito_peca  = false;

if($areaAdmin === false || $areaAdmin != false){
    $regras["consumidor|telefone"] = array(
        "obrigatorio" => false
    );
    $regras["consumidor|celular"] = array(
        "obrigatorio" => true
    );
}
if(!empty($posto) and is_numeric($posto)) {
	$aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto LIMIT 1";
	$aux_res = pg_query($con, $aux_sql);
	$aux_par_ad = (array) json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'));
	if ($aux_par_ad["anexar_nf_os"] == "nao") {
		$valida_anexo = "";
	}
}


if (!empty($os)) {
    $sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = {$os};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $consumidor_revenda = pg_fetch_result($res, 0, "consumidor_revenda");
    }
}

if (getValue("os[consumidor_revenda]") != 'R' || $consumidor_revenda != 'R' ) {
	$regras["consumidor|cpf"] = array(
		"obrigatorio" => true
	);

	$regras["consumidor|email"] = array(
		"function" => array("verifica_email")
	);


	$regras["consumidor|numero"] = array(
		"obrigatorio" => true
	);

	$regras["consumidor|cep"] = array(
		"obrigatorio" => true
	);
}else{
	$regras["consumidor|nome"] = array(
		"obrigatorio" => false
	);
	$regras["consumidor|cidade"] = array(
		"obrigatorio" => false
	);

	$regras["consumidor|email"] = array(
		"obrigatorio" => false
	);
	$regras["consumidor|estado"] = array(
		"obrigatorio" => false
	);
	$regras["consumidor|celular"] = array(
		"obrigatorio" => false
	);

	$regras["consumidor|cpf"] = array(
		"obrigatorio" => false
	);

	$regras["consumidor|numero"] = array(
		"obrigatorio" => false
	);

	$regras["consumidor|cep"] = array(
		"obrigatorio" => false
	);
}
$regras["os|data_compra"] = array(
    "obrigatorio" => true
);

$regras["os|data_abertura"] = array(
    "obrigatorio" => true,
    "function" => array('valida_abertura')
);

$regras["os|defeito_reclamado"] = array(
    "obrigatorio" => false
);

$regras["os|tipo_atendimento"] = array(
    "obrigatorio" => false
);

$regras["revenda|cnpj"] = array(
    "obrigatorio" => true
);

$regras["revenda|estado"] = array(
    "obrigatorio" => true
);

$regras["revenda|cidade"] = array(
    "obrigatorio" => true
);

$regras["produto|descricao"] = array( 
    "obrigatorio" => true
);

$regras["produto|serie"] = array(
    "obrigatorio" => true,
    "function" => array("valida_serie_cadence")
);
 
$auditorias = array(
    "auditoria_os_reincidente_cadence",
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria_cadence",
    "auditoria_pecas_excedentes_cadence",
    "valida_item_aparencia", 
    "auditoria_peca_fora_linha_cadence"
);

//"auditoria_troca_obrigatoria", retirado no dia 02-05-2018 hd-3824281 interacao 340
$regras_pecas = array(
    "lista_basica" => true,
    "servico_realizado" => false,
	"bloqueada_garantia" => true
);
$pre_funcoes_fabrica = array("verifica_troca_os", 'verifica_tipo_atendimento_deslocamento', "Verifica_Bloqueio_Revenda", "valida_po_pecas", "verifica_solucao");

$funcoes_fabrica = array("valida_km", 'auditoria_produto_critico', "ValidaEstoquePosto", "valida_produto_troca");

$grava_os_item_function = "grava_os_item_cadence";

function valida_troca_obrigatoria(){
    global $campos, $login_fabrica, $con, $os, $login_admin;

    $id_produto = $campos["produto"]["id"];
    
    $sql_troca = "SELECT  troca_obrigatoria,
                    intervencao_tecnica,
                    produto_critico
            FROM    tbl_produto
            WHERE   produto = $id_produto
            AND     fabrica_i = $login_fabrica";

    $res_troca = pg_query($con,$sql_troca);
    $msg_erro .= pg_last_error($con);

    if (pg_num_rows($res_troca) > 0) {
      $troca_obrigatoria   = trim(pg_fetch_result($res_troca,0,troca_obrigatoria));
      $intervencao_tecnica = trim(pg_fetch_result($res_troca,0,intervencao_tecnica));
      $produto_critico = trim(pg_fetch_result($res_troca,0,produto_critico));

        if ($troca_obrigatoria == 't') {
                
            pg_query($con, "BEGIN");

            $sql = "SELECT liberada, bloqueio_pedido 
                    FROM tbl_auditoria_os
                    WHERE os = $os";
            $res = pg_query($con, $sql);
            $msg_erro .= pg_last_error($con);

            if(pg_num_rows($res) > 0){
                $liberada_troca = trim(pg_fetch_result($res,0,liberada));
                $bloqueio_pedido_troca = trim(pg_fetch_result($res,0,bloqueio_pedido));

                if($bloqueio_pedido_troca == 't' || empty($liberada_troca)){
                    $msg_erro .= "OS em Auditoria";
                }
            }
        
            $referencia_produto = $campos["produto"]["referencia"];
           
            $sql = "SELECT peca 
                    FROM tbl_peca 
                    WHERE referencia = '$referencia_produto' 
                    AND fabrica = $login_fabrica
                    AND produto_acabado IS TRUE";

            $res = pg_query($con, $sql);
            $msg_erro .= pg_last_error($con);

            if (pg_num_rows($res) > 0){
                $peca_troca = trim(pg_fetch_result($res,0,peca));

            }else{

                $sql_ipi = "SELECT ipi FROM tbl_produto WHERE produto = $id_produto";
                $res_ipi = pg_query($con, $sql_ipi);
                $msg_erro .= pg_last_error($con);

                if (pg_num_rows($res_ipi) > 0){
                    $ipi_troca = pg_fetch_result($res,0,ipi);
                }else{
                    $ipi_troca = 10;
                }

                $sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado) VALUES ($login_fabrica, '$troca_referencia', '$referencia_produto' , $ipi_troca , 'NAC','t')" ;
                
                $res = pg_query($con,$sql);
                $msg_erro .= pg_last_error($con);

                $sql = "SELECT CURRVAL ('seq_peca')";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_last_error($con);
                
                $peca_troca = pg_fetch_result($res,0,0);

                $sql = "INSERT INTO tbl_lista_basica (fabrica, produto,peca,qtde) VALUES ($login_fabrica, $id_produto, $peca_troca, 1);" ;
                
                $res = pg_query($con,$sql);
                $msg_erro .= pg_last_error($con);
              
            }

                if (empty($msg_erro)){
                    $sql = "SELECT  servico_realizado
                            FROM    tbl_servico_realizado
                            WHERE   troca_produto
                            AND     fabrica = $login_fabrica" ;
                            
                    $res = pg_query($con,$sql);
                    $msg_erro .= pg_last_error($con);
                    
                    if (pg_num_rows($res) > 0){
                        $servico_realizado = pg_fetch_result($res,0,0);
                    }

                    $sql_os_produto = "SELECT os_produto 
                                       FROM tbl_os_produto
                                       WHERE os = $os";

                    $res_os_produto = pg_query($con, $sql_os_produto);
                    $msg_erro .= pg_last_error($con);

                    if (pg_num_rows($res_os_produto) > 0){
                        $os_produto_troca = pg_fetch_result($res_os_produto,0,os_produto);
                    }else{
                        $sql_os_produto = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $id_produto)";
                        $res_os_produto = pg_query($con, $sql_os_produto);
                        $msg_erro .= pg_last_error($con);

                        $sql = "SELECT CURRVAL ('seq_os_produto')";
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_last_error($con);
                
                        $os_produto_troca = pg_fetch_result($res,0,0);
                    }

                    $sql_item = "INSERT INTO tbl_os_item (os_produto,
                                                          peca,
                                                          qtde,
                                                          servico_realizado,
                                                          admin,
                                                          peca_obrigatoria
                                                      ) VALUES (
                                                          $os_produto_troca,
                                                          $peca_troca,
                                                          1,
                                                          $servico_realizado,
                                                          null,
                                                          TRUE
                                                      )";
                    
                    $res_item = pg_query($con,$sql_item);
                    $msg_erro .= pg_last_error($con);

                    if (pg_num_rows($res_item) == 1){
                        $sql = "UPDATE tbl_os SET
                                  troca_garantia          = 't',
                                  ressarcimento           = 'f',
                                  troca_garantia_admin    = $login_admin
                                WHERE os = $os 
                                AND fabrica = $login_fabrica";
                                 
                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_last_error($con);
                    }
                        $sql = "UPDATE tbl_os_troca SET peca = $peca_troca
                                WHERE  os     = $os
                                AND    fabric = $login_fabrica";

                        $res = pg_query($con,$sql);
                        $msg_erro .= pg_last_error($con);        

                        $sqlSolucao = "SELECT solucao FROM tbl_solucao WHERE descricao = 'Troca do produto' AND fabrica = $login_fabrica";
                        $resSolucao = pg_query($con, $sqlSolucao);
                        if (pg_last_error($con)) {
                            $msg_erro .= "Erro ao atualizar solução.";
                        }

                        if (pg_num_rows($resSolucao)) {
                            $solucao_troca_produto = pg_fetch_result($resSolucao, 0, 'solucao');
                            $sqlUpSolucao = "UPDATE tbl_os 
                                                SET solucao_os = $solucao_troca_produto
                                              WHERE os = $os 
                                                AND fabrica = $login_fabrica";
                            $resUpSolucao = pg_query($con, $sqlUpSolucao);
                            if (pg_last_error($con)) {
                                $msg_erro .= "Erro ao atualizar solução.";
                            }
                        } else {
                            $msg_erro .= "Erro ao atualizar solução.";
                        }
                        
                }

                if (empty($msg_erro)){
                    pg_query($con, "COMMIT");
                } else {
                    pg_query($con, "ROLLBACK");

                }
            }
        }          
    }

/* Grava OS Fábrica */
function grava_os_fabrica(){

    global $campos, $login_fabrica, $con;

    if($campos['produto']['deslocamento_km'] == 'f' and strlen($campos['os']['tipo_atendimento']) == 0){
        $campos['os']['tipo_atendimento'] = 101;
    }

    $consumidor_revenda = $campos["os"]["consumidor_revenda"];
 
    if(empty($consumidor_revenda)  and $areaAdmin != true){
        $campos["os"]["consumidor_revenda"] = "C";
    }

    $campos_bd = array();

    if(strlen($campos["produto"]["solucao"]) > 0){
        $campos_bd["solucao_os"] = $campos["produto"]["solucao"];
    }else{
        $campos_bd["solucao_os"] = 'null';
    }
    return $campos_bd;
}

function valida_produto_troca(){
    global $login_fabrica, $campos, $os, $con, $login_admin;
    
    if ($campos['os']['consumidor_revenda'] == 'C'){

        $produto_id = $campos['produto']['id'];

        $sqlTr = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica and produto = $produto_id and troca_obrigatoria is true ";
        $resTr = pg_query($con, $sqlTr);
        if(pg_num_rows($resTr)>0){
            $sqlTr_os = "SELECT * from tbl_os_troca where os = $os and fabric = $login_fabrica";
            $resTr_os = pg_query($con, $sqlTr_os);
            if(pg_num_rows($resTr_os)==0){
                $sql_os_troca = "INSERT INTO tbl_os_troca (fabric, os, produto, observacao, causa_troca, gerar_pedido) VALUES ($login_fabrica, $os, $produto_id, 'Troca de Produto Automatica - Cadastro de OS', 25, true); ";
                $res_os_troca = pg_query($con, $sql_os_troca);
                valida_troca_obrigatoria();
            }
        }
    }
}

function verifica_email(){

    global $login_fabrica, $campos, $os, $con, $login_admin;

    $email      = $campos['consumidor']['email'];
    $op_email   = $campos['consumidor']['op_email'];

    if(strlen(trim($email))==0 AND strlen(trim($op_email))==0){
        throw new Exception("O campos e-mail é obrigatório.");
    }
}

function grava_os_extra_fabrica(){

    global $campos;
    return array(
        "obs_adicionais" => "'".$campos['consumidor']['op_email']."'"
    );
}

function valida_abertura(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $data_abertura  = $campos['os']['data_abertura'];
    $data_compra    = $campos['os']['data_compra'];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 5 days")) {
            throw new Exception("Data de abertura não pode ser anterior a 5 dias");
        }
    }

    if(!empty($data_compra) && empty($os)){
        list($dia, $mes, $ano) = explode("/", $data_abertura);
        list($diaC, $mesC, $anoC) = explode("/", $data_compra);

        if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("{$anoC}-{$mesC}-{$diaC}")   ){
            throw new Exception("Data da Compra não pode ser maior que a Data de Abertura");
        }    
    }
}


function verifica_troca_os(){
    global $login_fabrica, $campos, $os, $con, $login_admin;
    if(!empty($os)){        
        $sql = "SELECT os FROM tbl_os_troca WHERE fabric = $login_fabrica and os = $os ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            throw new Exception("OS $os com troca de produto não pode ser alterada.");
        }
    }
}

function verifica_tipo_atendimento_deslocamento(){
    global $login_fabrica, $campos, $os, $con, $login_admin, $msg_erro;

    $referencia         = $campos['produto']['referencia'];
    $tipo_atendimento   = $campos['os']['tipo_atendimento'];

    $sql = "SELECT tbl_linha.deslocamento from tbl_produto  
            join tbl_linha using(linha)
            where referencia = '$referencia' and fabrica_i = $login_fabrica ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $deslocamento = pg_fetch_result($res, 0, deslocamento);
    }

    if($deslocamento == 't' and strlen(trim($tipo_atendimento))==0){
        $msg_erro['campos'][] = 'os[tipo_atendimento]';
        throw new Exception("Informe o tipo de atendimento.");
    }

    if(strlen(trim($referencia))>0 AND $tipo_atendimento == 100){
        $sql = "SELECT tbl_linha.linha
            FROM tbl_linha
            JOIN tbl_produto USING(linha)
            WHERE tbl_linha.deslocamento IS TRUE
            AND tbl_produto.referencia = '$referencia' 
            AND tbl_produto.fabrica_i = $login_fabrica";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)==0){
            throw new Exception("Tipo de atendimento inválido para esse produto");
        }
    }
}

function valida_km(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $km                 = $campos['os']['qtde_km'];
    $qtde_km_hidden     = $campos['os']['qtde_km_hidden'] ;
    $qtde_km            = $campos['os']['qtde_km'] ;
    $tipo_atendimento   = $campos['os']['tipo_atendimento'] ;

	if(empty($tipo_atendimento)) return true; 

    $sqlVerTipoAtendimento = "SELECT km_google from tbl_tipo_atendimento where ativo is true and tipo_atendimento = $tipo_atendimento and km_google is true ";
    $resVerTipoAtendimento = pg_query($con, $sqlVerTipoAtendimento);
    if(pg_num_rows($resVerTipoAtendimento)>0){

        $sqlAud = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 2";
        $resAud = pg_query($con, $sqlAud);
        if(pg_num_rows($resAud)==0){
            /*if($km <= 20){
                $campos['os']['qtde_km'] = 0;
                $sql = "UPDATE tbl_os SET qtde_km = 0 WHERE os = $os and fabrica = $login_fabrica ";
                $res = pg_query($con, $sql);
            }else*/ //solicitada a retirada da regra de 0 a 20 km no chamado 3824281 interacao 225

            if( ($km > 50) and $qtde_km_hidden  == $qtde_km){
                $sql = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, '', false, 2)";
                $res = pg_query($con, $sql);
            }elseif($qtde_km_hidden  != $qtde_km){
                $sql = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, ' Alterado Manualmente', false, 2)";
                $res = pg_query($con, $sql);
            }
        }   
    }
}

function Verifica_Bloqueio_Revenda(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $cnpj = $campos['revenda']['cnpj'];
    $limpaCnpj = array('-', '/', '.');
    $cnpj = str_replace($limpaCnpj, "", $cnpj);    

    $sql = "SELECT tbl_revenda_fabrica.motivo_bloqueio
            FROM tbl_revenda_fabrica
            INNER JOIN tbl_revenda on tbl_revenda.revenda = tbl_revenda_fabrica.revenda
            WHERE tbl_revenda_fabrica.fabrica = $login_fabrica
            AND tbl_revenda.cnpj = '$cnpj'
            AND tbl_revenda_fabrica.data_bloqueio IS NOT NULL";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $motivo = pg_fetch_result($res,0,'motivo_bloqueio');
        $msg_bloqueio = "Revenda Bloqueada para abertura de Ordem de Serviço. Motivo: $motivo .";
        throw new Exception("$msg_bloqueio");
    }    
}

function auditoria_produto_critico() {
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $produto_id = $campos['produto']['id'];

    $sqlCr = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica and produto = $produto_id and produto_critico is true ";
    $resCr = pg_query($con, $sqlCr);
    if(pg_num_rows($resCr)>0){
        $sqlAud = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 3";
        $resAud = pg_query($con, $sqlAud);
        if(pg_num_rows($resAud)==0){
            $sql = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, ' Produto Crítico', true, 3)";
            $res = pg_query($con, $sql);
        }   
    }
}

function auditoria_os_reincidente_cadence() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $sqlAud = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 1";
    $resAud = pg_query($con, $sqlAud);
    if(pg_num_rows($resAud)==0){            

        $posto = $campos['posto']['id'];

        $sql = "SELECT  os
                FROM    tbl_os
                WHERE   fabrica         = {$login_fabrica}
                AND     os              = {$os}
                AND     os_reincidente  IS NOT TRUE
                AND     cancelada       IS NOT TRUE
        ";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $select = "SELECT tbl_os.sua_os, tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.os < {$os}
                    AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
                    AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
                    AND tbl_os_produto.produto = {$campos['produto']['id']}
                    AND tbl_os.consumidor_revenda='C'
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1";
            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0 ) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
                $sua_os_reincidente   = pg_fetch_result($resSelect, 0, "sua_os");

                $sql = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Auditoria de OS Reincidencia - $sua_os_reincidente', true, 1)";
                $res = pg_query($con, $sql);

                $sql_extra = "UPDATE tbl_os_extra set os_reincidente = '$os_reincidente_numero' where os = $os ;";
                $res_extra = pg_query($con, $sql_extra);

                $sql_os = "UPDATE tbl_os set os_reincidente = true where os = $os";
                $res_os = pg_query($con, $sql_os);
            }
        }
    }        
}

function grava_os_campo_extra_fabrica(){
    global $campos;

    return array(
        "resposta_reincidencia"      => utf8_encode($campos['os']['formulario_reincidencia_opcao']),
        "justificativa_reincidencia" => utf8_encode($campos['os']['formulario_reincidencia_justificativa'])
    );
}

function auditoria_valor_peca($grava_auditoria_valor_peca = false){
    global $login_fabrica, $campos, $os, $con, $login_admin, $msg_erro;

    $produto_id     = $campos['produto']['id'];
    $pecas          = $campos['produto_pecas'];
    $peca_nova      = 'nao';
    $produto_referencia = $campos['produto']['referencia'];

    $sqlVProduto = " SELECT tbl_peca.peca, tbl_peca.referencia, tbl_tabela_item.preco from tbl_peca 
                    inner join tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
                    inner join tbl_tabela using(tabela)
                    where referencia = '$produto_referencia'
                    and tbl_peca.fabrica = $login_fabrica
                    and tbl_tabela.ativa = 't' and tbl_tabela.tabela_garantia = 't' ";
    $resVProduto = pg_query($con, $sqlVProduto);
    if(pg_num_rows($resVProduto)>0){
        $preco_produto     = pg_fetch_result($resVProduto, 0, 'preco');
    }else{
        $preco_produto     = 0;
    }
   
    $sql_mao_obra_produto = "SELECT mao_de_obra_troca 
                            FROM tbl_produto 
                            WHERE fabrica_i = $login_fabrica
                            AND produto = $produto_id "; 
    $res_mao_obra_produto = pg_query($con, $sql_mao_obra_produto);
    if(pg_num_rows($res_mao_obra_produto)>0){
        $mao_de_obra_troca = pg_fetch_result($res_mao_obra_produto, 0, 'mao_de_obra_troca');
    }else{
        throw new Exception("Produto não encontrado.");
    }

    $sql_preco_pecas = "SELECT sum(tbl_os_item.preco) as valor_total_pecas
                        FROM tbl_os_item
                        join tbl_os_produto using(os_produto)
                        join tbl_servico_realizado using(servico_realizado)
                        where tbl_os_produto.os = $os 
                        and tbl_servico_realizado.troca_de_peca is true
                        AND tbl_servico_realizado.ativo is true";
    $res_preco_pecas = pg_query($con, $sql_preco_pecas);
    if(pg_num_rows($res_preco_pecas)>0){
        $valor_total_pecas = pg_fetch_result($res_preco_pecas, 0, 'valor_total_pecas');
    }

    //$peca_com_mo = $valor_total_pecas + $mao_de_obra_troca;

    $valor_80_produto = ($preco_produto + $mao_de_obra_troca) * 0.80;

    if($valor_total_pecas > $valor_80_produto){
        $grava_auditoria_valor_peca = true;
        $regra_valor = true;
    }else{
        $grava_auditoria_valor_peca = false;
        $regra_valor == false;
    }

    foreach ($pecas as $value) {
        if(!isset($value['id'])){
            continue;
        }
        if($value['nova'] == 'sim' AND $regra_valor == true ){
            $grava_auditoria_valor_peca = true;
            $peca_nova = 'sim';
        }
    }

    $sqlAud = "SELECT auditoria_os, liberada, reprovada FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 8 and observacao = 'Valor de Peças' order by auditoria_os desc limit 1";
    $resAud = pg_query($con, $sqlAud);
    if(pg_num_rows($resAud)==0){
        $grava_auditoria_valor_peca = true;
    }else{  
        $liberada   = pg_fetch_result($resAud, 0, liberada);
        $reprovada  = pg_fetch_result($resAud, 0, reprovada);
        if(empty($liberada) AND empty($reprovada)){
            $grava_auditoria_valor_peca = false;
        }elseif($peca_nova == 'sim'){
            $grava_auditoria_valor_peca = true;
        }else{
            $grava_auditoria_valor_peca = false;
        }        
    }

    if(!empty($reprovada)){
        $grava_auditoria_valor_peca = true;
    }

    if($grava_auditoria_valor_peca == true AND $regra_valor == true) {
		$sql_auditoria = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Valor de Peças', true, 8) ; 
							UPDATE tbl_os set troca_garantia_mao_obra = '$mao_de_obra_troca' where os = $os;
							UPDATE tbl_os_extra set custo_produto_troca_faturada='$preco_produto' where os = $os ; 
							";
        $res_auditoria = pg_query($con, $sql_auditoria);
    }
}

function valida_item_aparencia(){
    global $login_fabrica, $campos, $os, $con, $login_admin, $msg_erro;

    foreach($campos['produto_pecas'] as $pecas){
        if(strlen(trim($pecas['id']))>0){
            $peca_id[] = $pecas['id'];
        }        
    }

    if(count($peca_id)>0){
        $sql = "SELECT * FROM tbl_peca where peca in (".implode(",", $peca_id).") and fabrica = $login_fabrica and item_aparencia is true";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            $sqlAud = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 4 AND tbl_auditoria_os.observacao ILIKE '%Item de Aparência%' ";
            $resAud = pg_query($con, $sqlAud);
            if(pg_num_rows($resAud)==0){
                $sql_auditoria = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, ' Item de Aparência', true, 4)";
                $res_auditoria = pg_query($con, $sql_auditoria);
            }
        }
    }
}
function verifica_solucao(){
    global $login_fabrica, $campos, $os, $con, $login_admin, $msg_erro;
    if(strlen(trim($campos['produto']['defeito_constatado']))>0  and strlen(trim($campos['produto']['solucao']))==0 ){
        $msg_erro['campos'][] = 'produto[solucao]';
        throw new Exception("Informe a solução da Ordem de Serviço.");
    }

   if(strlen(trim($campos['produto']['solucao'])) > 0){ 
	    $sql = "SELECT troca_peca 
		      FROM tbl_solucao 
		     WHERE troca_peca IS TRUE
			AND ativo IS TRUE 
		       AND solucao = " . $campos['produto']['solucao'];
	    $res = pg_query($con, $sql);
	    if (pg_num_rows($res) > 0) {
		    foreach($campos['produto_pecas'] as $k => $peca) {
			if (empty($peca['id'])){
				unset($campos['produto_pecas'][$k]);
			}
		    }

		if(count($campos['produto_pecas']) == 0){
			
			throw new Exception("Para solução informada é obrigatório o lançamento de pelo menos uma peça.");  
		}

	    } else {
		foreach($campos['produto_pecas'] as $peca){
		    if (empty($peca['id'])){
			continue;
		    } else {
			$msg_erro['campos'][] = 'produto[solucao]';
			throw new Exception("Solução informada não permite lançamentos de peças.");  
			break;
		    }
		}
	    }
   }
}

function valida_po_pecas(){    
    global $login_fabrica, $campos, $os, $con, $login_admin, $msg_erro;
    $cont = 0;
    foreach($campos['produto_pecas'] as $peca){

        if(!isset($peca['id'])){
            continue;
        }
        $parametros_adicionais= "";

        if($peca['po_pecas_hidden'] == 't'){
            if(strlen(trim($peca['po_pecas']))==0){
                $msg_erro['campos'][$peca['referencia']] = 'sim';
                $erro_po_pecas[] = 'sim';
            }else{
                $valida_po = explode("-", $peca['po_pecas']);

                if(strlen($valida_po[0])==5 AND strlen($valida_po[1])==3 ){
                    $parametros_adicionais['po_pecas'] = $peca['po_pecas'];
                    $parametros_adicionais = json_encode($parametros_adicionais);
                    $campos['produto_pecas'][$cont]['parametros_adicionais'] = $parametros_adicionais;
                }else{
                    $msg_erro['campos'][$peca['referencia']] = 'sim';
                    $erro_po_pecas_formato[] = 'sim';
                }                
            }            
        }
        $cont++;
    }

    if(count($erro_po_pecas)>0){
        throw new Exception("O campos PO Peças é obrigatório.");
    }
    if(count($erro_po_pecas_formato)>0){
        throw new Exception("O campo não esta no formato correto XXXXX-XXX.");
    }
}


$valida_pecas = "valida_pecas_cadence";

/**
 * Função que valida as peças do produto $regras_pecas
 */
function valida_pecas_cadence($nome = "produto_pecas") {

    global $con, $msg_erro, $login_fabrica, $regras_pecas, $regras_subproduto_pecas, $campos , $areaAdmin;
    if(verifica_peca_lancada(false) === true){

        $pecas_os = array();

        foreach ($campos[$nome] as $posicao => $campos_peca) {
            $peca       = $campos_peca["id"];
            $cancelada  = $campos_peca["cancelada"];
            $pedido     = $campos_peca["pedido"];
            $referencia = $campos_peca["referencia"];
			$servico_id = $campos_peca["servico_realizado"];

            if (empty($peca)) {
                continue;
            }

            if (!empty($peca) && empty($campos_peca["qtde"])) {
                $msg_erro["msg"]["peca_qtde"] = traduz('informe.uma.quantidade.para.a.peca.%', null, null, $referencia);
                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                continue;
            }

            if ($nome == "subproduto_pecas") {
                $regra_validar = $regras_subproduto_pecas;
            } else {
                $regra_validar = $regras_pecas;
            }

            if(isset($campos_peca["defeito_peca"]) && empty($campos_peca["defeito_peca"])){
                $msg_erro["msg"]["peca_qtde"] = traduz('favor.informar.o.defeito.da.peca.%', null, null, $referencia);
                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                continue;
            }

            foreach ($regra_validar as $tipo_regra => $regra) {
                switch ($tipo_regra) {
                    case 'lista_basica':
                        if ($nome == "subproduto_pecas") {
                            $produto = $campos["subproduto"]["id"];
                        } else {
                            $produto = $campos["produto"]["id"];
                        }

                        $peca_qtde = $campos_peca["qtde"];

                        if ($regra == true && !empty($produto)) {
                            $sql = "SELECT qtde
                                    FROM tbl_lista_basica
                                    WHERE fabrica = {$login_fabrica}
                                    AND produto = {$produto}
                                    AND peca = {$peca}";
                            $res = pg_query($con, $sql);

                            if (!pg_num_rows($res)) {
                                if(strlen(trim($pedido))>0){
                                    continue;
                                }
                                $msg_erro["msg"][]    = traduz("Peça não consta na lista básica do produto");
                                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                            } else {
                                $lista_basica_qtde = pg_fetch_result($res, 0, "qtde");
                                

                                if(array_key_exists($peca, $pecas_os)){
                                    $pecas_os[$peca]["qtde"] += $peca_qtde;
                                }else{
                                    $pecas_os[$peca]["qtde"] = $peca_qtde;
                                }

                                if($cancelada > 0){
                                    $pecas_os[$peca]["qtde"] -= $cancelada;
                                }


                                $reenviaPeca = false;
								if(!empty($pedido)) {
									$sql = "SELECT tbl_os_item.obs, tbl_os_item.parametros_adicionais
											FROM tbl_os_item
											JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
											JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
											WHERE tbl_os.fabrica = {$login_fabrica} 
											AND tbl_os_item.pedido={$pedido}
											AND tbl_os_item.peca={$peca}";
									$res = pg_query($con, $sql);
									if (pg_num_rows($res) > 0) {
										$parametrosAdd = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),1);
										if (isset($parametrosAdd["pecaReenviada"]) && $parametrosAdd["pecaReenviada"]) {
											$reenviaPeca = true;
											$pecas_os[$peca]["qtde"] -= 1;
										}
										
									}
								}


                                if ($pecas_os[$peca]["qtde"] > $lista_basica_qtde && !$reenviaPeca) {
                                    $msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça maior que a permitida na lista básica");
                                    $msg_erro["campos"][]                 = "{$nome}[{$posicao}]";
                                }
                            }
                        }
                        break;
                    case 'servico_realizado':
                        if ($regra === true && !empty($campos_peca["id"]) && empty($campos_peca["servico_realizado"])) {
                            $msg_erro["msg"]["servico_realizado"] = traduz("Selecione o serviço da peça".$cont);
                            $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                        }
                        break;
                    case 'serie_peca':
                        if(strlen(trim($campos_peca['id'])) > 0 AND $regra === true){ //HD-3428297
                            $sql_serie = "SELECT tbl_peca.peca FROM tbl_peca WHERE peca = {$campos_peca['id']} AND fabrica = {$login_fabrica} AND numero_serie_peca IS TRUE ";
                            $res_serie = pg_query($con, $sql_serie);
                            if(pg_num_rows($res_serie) > 0 AND strlen(trim($campos_peca["serie_peca"])) == 0){
                                $msg_erro["msg"][] = traduz("Preencha a série da peça");
                                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                            }
                        }
						break;
					case 'bloqueada_garantia':
						if($areaAdmin === false) {
							if(strlen(trim($campos_peca['id'])) > 0){
								$sql_peca = "SELECT tbl_peca.peca FROM tbl_peca WHERE peca = {$campos_peca['id']} AND fabrica = {$login_fabrica} AND bloqueada_garantia ";
								$res_peca = pg_query($con, $sql_peca);
								$sql_ge = "SELECT descricao FROM tbl_servico_realizado where servico_realizado = $servico_id and gera_pedido";
								$res_ge = pg_query($con, $sql_ge);
								if(pg_num_rows($res_peca) > 0  and pg_num_rows($res_ge) > 0){
									$msg_erro["msg"][] = traduz("Peça bloqueada para garantia, entrar em contato com fabricante");
									$msg_erro["campos"][] = "{$nome}[{$posicao}]";
								}
							}
						}
						break;

                }
            }
        }

    }

}



function PegaPrecoPeca($id_peca){
    global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

    $sql_peca = "SELECT tbl_tabela.tabela,  tbl_tabela_item.preco, tbl_tabela_item.peca
                FROM tbl_tabela 
                JOIN tbl_tabela_item using(tabela)
                WHERE tbl_tabela.tabela_garantia is true 
                AND tbl_tabela.fabrica = $login_fabrica
                AND tbl_tabela_item.tabela = tbl_tabela.tabela and tbl_tabela_item.peca = $id_peca
                ORDER BY tbl_tabela.tabela DESC LIMIT 1 ";
    $res_peca = pg_query($con, $sql_peca);
    if(pg_num_rows($res_peca)>0){
        $preco_peca = pg_fetch_result($res_peca, 0, preco);
    }

    return $preco_peca;
}

//grava os item novo
function grava_os_item_cadence($os_produto, $subproduto = "produto_pecas") {

    global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;    

    if (function_exists("grava_custo_peca") ) {
        /**
         * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
         * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
         */
        $custo_peca = grava_custo_peca();
        if($custo_peca==false){
            unset($custo_peca);
        }
    }

    if($historico_alteracao === true){
        $historico = array();
    }

    foreach ($campos[$subproduto] as $posicao => $campos_peca) {

        if (strlen($campos_peca["id"]) > 0) {

            if($historico_alteracao === true){
                include "$login_fabrica/historico_alteracao.php";
            }

            $preco_peca = PegaPrecoPeca($campos_peca["id"]);

            $preco_total_item = $preco_peca * $campos_peca['qtde'];

            $sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
            $res = pg_query($con, $sql);

            $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

            if ($troca_de_peca == "t") {
                $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$campos_peca['id']}";
                $res = pg_query($con, $sql);

                $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

                if ($devolucao_obrigatoria == "t") {
                    $devolucao_obrigatoria = "TRUE";
                } else {
                    $devolucao_obrigatoria = "FALSE";
                }
            } else {
                $devolucao_obrigatoria = "FALSE";
            }
            $login_admin = (empty($login_admin)) ? "null" : $login_admin;

            if (empty($campos_peca["os_item"])) {
                $sql = "INSERT INTO tbl_os_item
                        (
                            os_produto,
                            peca,
                            qtde,
                            servico_realizado,
                            peca_obrigatoria,
                            preco,
                            admin
                            ".(($grava_defeito_peca == true) ? ", defeito" : "").",
                            parametros_adicionais
                        )
                        VALUES
                        (
                            {$os_produto},
                            {$campos_peca['id']},
                            {$campos_peca['qtde']},
                            {$campos_peca['servico_realizado']},
                            {$devolucao_obrigatoria},
                            '$preco_total_item',
                            {$login_admin}
                            ".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "").",
                            '".$campos_peca['parametros_adicionais']."'
                        )
                        RETURNING os_item";
                $acao = "insert";

                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar Ordem de Serviço #9");
                }
                $campos[$subproduto][$posicao]["nova"] = "sim";
                $campos[$subproduto][$posicao]["os_item"] = pg_fetch_result($res, 0, "os_item");

            } else {
                $sql = "SELECT tbl_os_item.os_item
                        FROM tbl_os_item
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                        WHERE tbl_os_item.os_produto = {$os_produto}
                        AND tbl_os_item.os_item = {$campos_peca['os_item']}
                        AND tbl_os_item.pedido IS NULL
                        AND UPPER(tbl_servico_realizado.descricao) NOT IN('CANCELADO', 'TROCA PRODUTO')";
                $res = pg_query($con, $sql);

                if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
                    continue;
                }

                if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
                    continue;
                }

                if (pg_num_rows($res) > 0) {
                    $sql = "UPDATE tbl_os_item SET preco = '$preco_total_item', 
                                qtde = {$campos_peca['qtde']},parametros_adicionais= '".$campos_peca['parametros_adicionais']."',
                                servico_realizado = {$campos_peca['servico_realizado']}
                                ".(($grava_defeito_peca == true) ? ", defeito = {$campos_peca['defeito_peca']}" : "")."
                            WHERE os_produto = {$os_produto}
                            AND os_item = {$campos_peca['os_item']}";
                    $acao = "update";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar Ordem de Serviço #10");
                    }
                }
                $campos[$subproduto][$posicao]["nova"] = "nao";
            }
        }
    }

    if (!empty($objLog)) {//logositem
        $objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os_item", $login_fabrica."*".$os);
    }
    unset($objLog);
    if($historico_alteracao === true){

        if(count($historico) > 0){
            grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);
        }
    }
}


function ValidaEstoquePosto(){
    global $login_fabrica, $campos, $os, $con, $login_admin, $msg_erro;

    $posto_id = $campos['posto']['id'];   

    foreach($campos['produto_pecas'] as $pecas){
        if(!isset($pecas['id'])){
            continue;
        }

        $xservico   = $pecas['servico_realizado'];
        $xpeca      = $pecas['id'];
        $xos_item   = $pecas['os_item'];
        $xqtde      = $pecas['qtde'];

        if(empty($xpeca)){
            continue;
        }

        $sql = "SELECT gera_pedido, troca_de_peca
                FROM tbl_servico_realizado
                WHERE fabrica = $login_fabrica
                AND servico_realizado = '$xservico'
                AND troca_de_peca IS TRUE
                AND (gera_pedido IS TRUE OR peca_estoque IS TRUE)";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){           

            $sql = "SELECT servico_realizado
                    FROM tbl_servico_realizado
                    WHERE fabrica = $login_fabrica
                    AND troca_de_peca IS TRUE
                    AND peca_estoque IS TRUE";
            $res = pg_query($con, $sql);

            $novo_servico = pg_fetch_result($res, 0, 'servico_realizado');
            $tipo_estoque = "estoque";


            /*Verificar se o posto possui a peça em estoque*/
            $sql = "SELECT qtde
                    FROM tbl_estoque_posto
                    WHERE peca = $xpeca
                    AND posto = $posto_id
                    AND fabrica = $login_fabrica
                    AND tipo = '$tipo_estoque'";
            $res = pg_query($con, $sql);


            /* Se tiver Qtde no estoque */
            if(pg_num_rows($res) > 0){
                /* Qtde no estoque */
                $qtde_estoque = pg_fetch_result($res, 0, 'qtde');

                /* Verifica se há movimento para aquela peça com o pedido */
                $sql_verifica_movimento = "SELECT peca, qtde_saida
                                            FROM tbl_estoque_posto_movimento
                                            WHERE
                                            peca = $xpeca
                                            AND posto = $posto_id
                                            AND fabrica = $login_fabrica
                                            AND tipo = '$tipo_estoque'
                                            AND os = $os
                                            AND os_item = $xos_item";
                $res_verifica_movimento = pg_query($con, $sql_verifica_movimento);

                /* Se tiver movimentação */
                if(pg_num_rows($res_verifica_movimento) > 0){
                    $qtde_pecas_movimento = pg_fetch_result($res_verifica_movimento, 0, 'qtde_saida');
                }else{
                    $qtde_pecas_movimento = 0;
                }

                /*Verifica se a quantidade movimentação é da mesma que está sendo enviada*/
                $sql = "SELECT peca
                        FROM tbl_estoque_posto_movimento
                        WHERE fabrica = $login_fabrica
                        AND posto = $posto_id
                        AND os = $os
                        AND peca = $xpeca
                        AND qtde_saida = $xqtde
                        AND tipo = '$tipo_estoque'";
                $resS = pg_query($con, $sql);

                if(pg_num_rows($resS) == 0){

                    if($qtde_pecas_movimento != $xqtde){

                        /* Se a qtde do estoque for maior do ele está passando e ainda não haver movimentação.. insere na tbl_estoque_posto_movimentacao */
                        if($qtde_estoque >= $xqtde && $qtde_pecas_movimento == 0){

                            $sql_posto_movimento = "INSERT INTO tbl_estoque_posto_movimento
                            (fabrica, posto, os, peca, qtde_saida, os_item, tipo,obs,data) VALUES
                            ($login_fabrica, $posto_id, $os, $xpeca, $xqtde, $xos_item, '$tipo_estoque','Saída automática, peça solicitada em Ordem de Serviço OS: $os',current_date)";
                            $res_posto_movimento = pg_query($con, $sql_posto_movimento);



                            // Atualiza a quantidade da peça no estoque
                            $sql_qtde_update = "UPDATE tbl_estoque_posto
                                                SET qtde = qtde - $xqtde
                                                WHERE
                                                fabrica = $login_fabrica
                                                AND posto = $posto_id
                                                AND tipo = '$tipo_estoque'
                                                AND peca = $xpeca";
                            $res_servico_update = pg_query($con, $sql_qtde_update);

                            /*Altera o serviço realizado da peça*/
                            $update_servico_realizado = "UPDATE tbl_os_item
                                                            SET servico_realizado = $novo_servico
                                                            WHERE tbl_os_item.os_item = $xos_item";
                            $res_update_servico_realizado = pg_query($con, $update_servico_realizado);
                        }
                    }
                }
            }
        }
    }
}

function verifica_peca_lancada_cadence(){
    global $campos;
    $peca_lancada = false;

    foreach($campos["produto_pecas"] as $key => $value) {
        if ($value["nova"] == 'sim'){
            $peca_lancada = true;
            break;
        }     
    }

    return $peca_lancada;
}

#Peças excedentes
    function auditoria_pecas_excedentes_cadence() {
        global $con, $os, $login_fabrica, $qtde_pecas;

        if(verifica_peca_lancada_cadence() === true){

            $sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            $qtde_pecas_intervencao = pg_fetch_result($res, 0, "qtde_pecas_intervencao");

            if(!strlen($qtde_pecas_intervencao)){
                $qtde_pecas_intervencao = 0;
            }

            if ($qtde_pecas_intervencao > 0) {
                $sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
                        FROM tbl_os_item
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
                        WHERE tbl_os_produto.os = {$os}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){
                    $qtde_pecas = pg_fetch_result($res, 0, "qtde_pecas");
                }else{
                    $qtde_pecas = 0;
                }

                if($qtde_pecas > $qtde_pecas_intervencao){
                    $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

                    if($busca['resultado']){
                        $auditoria_status = $busca['auditoria'];
                    }

                    if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'")) {
                        $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                            ({$os}, $auditoria_status, 'OS em auditoria de peças excedentes')";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao lançar ordem de serviço");
                        }
                    }
                }
            }
        }
    }


    #Troca obrigatória
    function auditoria_troca_obrigatoria_cadence() {
        global $con, $os, $campos, $login_fabrica;

        if ($campos['os']['consumidor_revenda'] == 'C'){

            $sql = "SELECT tbl_produto.produto
                    FROM tbl_os_produto
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                    WHERE tbl_os_produto.os = {$os}
                    AND tbl_produto.troca_obrigatoria IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0 && verifica_auditoria_unica(" tbl_auditoria_status.produto = 't' AND tbl_auditoria_os.observacao ILIKE '%troca obrigatória%'", $os) === true) {
                $busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, liberada, admin, justificativa, bloqueio_pedido) VALUES
                    ({$os}, $auditoria_status, 'OS em intervenção da fábrica por Produto de troca obrigatória', now(), null, 'Aprovação Automática', false)";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                }
            }
        }
    }

    function auditoria_peca_fora_linha_cadence(){
        global $campos, $login_fabrica, $con, $os;

        $auditoria_peca = false;

        foreach($campos['produto_pecas'] as $pecas){

            $xpeca      = $pecas['id'];

            if (!empty($xpeca)) {
                $sql = "SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica AND peca = $xpeca AND libera_garantia IS TRUE";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0){

                    $auditoria_peca = true;
                    
                }
            }

        }

        if ($auditoria_peca) {
            $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }

            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
            ({$os}, $auditoria_status, 'Auditoria de peça fora de linha')";
            $res = pg_query($con, $sql);
        }

    }

    #$grava_anexo = "grava_anexo_cadence";

    /**
 * Função para mover os anexos do bucket temporario para o bucket da Ordem de Serviço
 */
    function grava_anexo_cadence() {
        global $campos, $s3, $os, $fabricaFileUploadOS, $con, $login_fabrica, $msg_erro;
    
        $tdocs = new TDocs($con, $login_fabrica, "os");
     
        $sql_sua_os = "SELECT sua_os, consumidor_revenda FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $res_sua_os = pg_query($con, $sql_sua_os);
        $xconsumidor_revenda = pg_fetch_result($res_sua_os, 0, 'consumidor_revenda'); 
        $xsua_os             = pg_fetch_result($res_sua_os, 0, 'sua_os');
        $recorte_sua_os = explode("-", $xsua_os);
        $xsua_os = $recorte_sua_os[0];
        if ($xconsumidor_revenda == 'R') {
            $sql_revenda = "SELECT os_revenda FROM tbl_os_revenda WHERE os_revenda = $xsua_os AND fabrica = $login_fabrica";
            $res_revenda = pg_query($con, $sql_revenda);
            if (pg_num_rows($res_revenda) > 0 ) {
                $context = 'revenda';
                $xid     = $xsua_os;
            } else {
                $context = 'os';
                $xid     = $xsua_os;
            }
        } else {
            $context = 'os';
            $xid     = $os;
        }

        if (!empty($campos["anexo"])) {
            foreach ($campos["anexo"] as $vAnexo) {
                if (empty($vAnexo)) {
                    continue;
                }
                $dadosAnexo = json_decode($vAnexo, 1);
                $tdocs->setContext("os",$context);
                $anexoID = $tdocs->setDocumentReference($dadosAnexo, $xid, "anexar", false);
                
                if (!$anexoID) {
                    $msg_erro["msg"][] = 'Erro ao fazer upload !';
                }
            }
        }
    }

    $valida_anexo = "valida_anexo";

    /**
 * Função para validar anexo
 */
    function valida_anexo_cadence() {
        global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica;

        $count_anexo = array();

        foreach ($campos["anexo"] as $key => $value) {
            if (strlen($value) > 0) {
                $count_anexo[] = "ok";
            }
        }

        if (count($count_anexo) == 0) {
            foreach ($campos["anexo_s3"] as $ky => $vl) {
                if ($vl == t) {
                    $count_anexo[] = "ok";
                }
            }           
        }

        if(!count($count_anexo)){
            $sql_sua_os = "SELECT sua_os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
            $res_sua_os = pg_query($con, $sql_sua_os);
            $xsua_os = pg_fetch_result($res_sua_os, 0, 'sua_os');
            $recorte_sua_os = explode("-", $xsua_os);
            $xsua_os = $recorte_sua_os[0];
            
            $sql_tem_anexo = "SELECT tdocs_id FROM tbl_tdocs WHERE fabrica = $login_fabrica AND referencia_id = $xsua_os";
            $res_tem_anexo = pg_query($con, $sql_tem_anexo);
            if (pg_num_rows($res_tem_anexo) > 0) {
                $anexos = pg_fetch_result($res_tem_anexo, 0, 'tdocs_id');
            } else {
                $msg_erro["msg"][] = traduz("Os anexos são obrigatórios");
            }
        }

    }

function valida_serie_cadence() {
    global $con, $campos, $login_fabrica;

    $produto = $campos['produto']['id'];
    $serie = preg_replace("/\-/", "", $campos['produto']['serie']);

    $sql_ns = " SELECT produto 
                FROM tbl_produto 
                WHERE fabrica_i = $login_fabrica 
                AND numero_serie_obrigatorio IS TRUE 
                AND produto = $produto";
    $res_ns = pg_query($con, $sql_ns);
    if (pg_num_rows($res_ns) > 0) {
        if (!empty($produto) && !empty($serie)) {
            $sql = "SELECT mascara, posicao_versao
                    FROM tbl_produto_valida_serie
                    WHERE produto = {$produto}
                    AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $mascara_ok = null;
                $versao = null;

                while ($mascara = pg_fetch_object($res)) {
                    $regExp = str_replace(array('L','N','X'), array('[A-Z]', '[0-9]','\w'),$mascara->mascara);

                    if (preg_match("/$regExp/i", $serie)) {
                        $mascara_ok = $mascara->mascara;
                        break;
                    }
                }
                
                if ($mascara_ok != null) {
                    $msg["success"] = true;
                } else {
                    throw new Exception("Número de série inválido") ;
                }
            } else {
                if (strlen($produto) > 0) {
                    $sql = "SELECT produto, JSON_FIELD('pecas_reposicao', parametros_adicionais) AS pecas_reposicao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto} AND numero_serie_obrigatorio IS TRUE;";
                    $res = pg_query($con,$sql);
                    $pecas_reposicao = pg_fetch_result($res, 0, pecas_reposicao);
                    if(pg_num_rows($res) > 0 && empty($serie) && $pecas_reposicao !== 't'){
                        throw new Exception("Preencha todos os campos obrigatórios");
                    }
                }        
            }
        } else {
            throw new Exception("Série não informada");
        }   
    } else {
        if (strlen($produto) > 0) {
            $sql = "SELECT produto, JSON_FIELD('pecas_reposicao', parametros_adicionais) AS pecas_reposicao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto} AND numero_serie_obrigatorio IS TRUE;";
            $res = pg_query($con,$sql);
            $pecas_reposicao = pg_fetch_result($res, 0, pecas_reposicao);
            if(pg_num_rows($res) > 0 && empty($serie) && $pecas_reposicao !== 't'){
                throw new Exception("Preencha todos os campos obrigatórios");
            }
        }
    }
}

?>
