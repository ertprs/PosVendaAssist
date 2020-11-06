<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
$grava_defeito_peca  = false;




$regras["consumidor|email"] = array(
	"obrigatorio" => true
);


$regras["consumidor|numero"] = array(
	"obrigatorio" => true
);

$regras["consumidor|cep"] = array(
	"obrigatorio" => true
);

$regras["os|data_abertura"] = array(
    "obrigatorio" => true,
    "function" => array('valida_abertura')
);

$regras["os|aparencia_produto"] = array(
	"obrigatorio" => true
);

$regras["revenda|estado"] = array(
    "obrigatorio" => false
);

$regras["revenda|cidade"] = array(
    "obrigatorio" => false
);

$regras["produto|descricao"] = array( 
    "obrigatorio" => true
);

$regras["produto|serie"] = array( 
    "obrigatorio" => true
);

$regras["produto|data_fabricacao"] = array( 
    "obrigatorio" => true
);

$regras["produto|causa_defeito"] = array( 
    "obrigatorio" => false
);

if ($areaAdmin) {

	if (!$os_admin) {
		$regras["os|justificativa_abertura"] = array(
			"obrigatorio" => true
		);
	}
}

$auditorias = array(
    "auditoria_valor_adicional_pressure",
    "auditoria_peca_critica",
    "auditoria_peca_lancada_pressure",
    "auditoria_pecas_excedentes",
    "auditoria_os_reincidente",
    "auditoria_km_pressure"
);

$funcoes_fabrica = [
    "grava_justificativa_abertura"
];

function grava_justificativa_abertura() {
    global $login_fabrica, $campos, $os, $login_admin, $con, $areaAdmin;

    if ($areaAdmin) {

        $sql = "SELECT justificativa_adicionais FROM tbl_os WHERE os = {$os}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            $arr_justificativa_adicionais = json_decode(pg_fetch_result($res, 0, 'justificativa_adicionais'), true);
            $arr_justificativa_adicionais['justificativa_abertura'] = $campos['os']['justificativa_abertura'];
            $json_justificativa_adicionais = json_encode($arr_justificativa_adicionais);

            $sql = "UPDATE tbl_os SET justificativa_adicionais = '{$json_justificativa_adicionais}' WHERE os = {$os}";
            $res = pg_query($con, $sql);

        }

    }

}

function valida_abertura(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    $data_abertura  = $campos['os']['data_abertura'];
    $data_compra    = $campos['os']['data_compra'];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 2 days")) {
            throw new Exception("Data de abertura não pode ser anterior a 2 dias");
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


function valida_peca_defeito_constatado(){
    global $con, $campos, $os, $login_fabrica;


    $sql = "SELECT tbl_peca.peca, tbl_peca_defeito.defeito,tbl_defeito.defeito,tbl_defeito.codigo_defeito
            from tbl_lista_basica join tbl_peca using(peca)
            join tbl_peca_defeito using(peca)
            join tbl_defeito using(defeito)
            join tbl_defeito_constatado on tbl_defeito_constatado.codigo = tbl_defeito.codigo_defeito
            where tbl_lista_basica.produto = $produto_aux
            and tbl_lista_basica.peca = $peca_aux
            and tbl_defeito_constatado.defeito_constatado in($defeitos_constatado) ";

    $res = pg_query($con,$sql);

     if(pg_result($res,0,peca) == ""){
        throw new Exception("A peça $xpeca não está relacionada ao(s) defeito(s) constatado(s) selecionados");
     }
}

/* Grava OS Fábrica */

function grava_os_fabrica() {
    global $campos;
    
    $causa_defeito = $campos["produto"]["causa_defeito"];
	$causa_defeito = (empty($causa_defeito)) ? "null" : $causa_defeito ; 
    return array(
        "causa_defeito" => "{$causa_defeito}"
    );
}

function grava_os_extra_fabrica(){

    global $campos;

    return array(
        "data_fabricacao" => "'".$campos['produto']['data_fabricacao']."'"
    );
}
/** hd */
function grava_os_campo_extra_fabrica() {
   
    global $campos;

    if ($campos['estoque_aguardar'] == "aguardar_pecas") {
        return array( "tipo_envio_peca" => $campos['estoque_aguardar'] );
    } 

    $data_abertura = $campos['os']['data_abertura'];
    $data_abertura = str_replace('/', '-', $data_abertura);
    $data_abertura = date("Y-m-d", strtotime($data_abertura));

    $data_previsao = $campos['previsao_estoque'];

    $data_previsao = str_replace('/', '-', $data_previsao);
    $data_previsao = date("Y-m-d", strtotime($data_previsao));

    if (strtotime($data_abertura) > strtotime($data_previsao)) {
        throw new Exception(" <br> Previsão de conserto não pode ser menor que a data de abertura da OS <br>");  
    } 

    return array(
        "tipo_envio_peca" => $campos['estoque_aguardar'],
        "previsao_entrega" => $data_previsao
    );
    
}

function auditoria_km_pressure(){
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
            
            if( ($km >= 100) and $qtde_km_hidden  == $qtde_km){
                $sql = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Deslocamento acima de 100 KM', false, 2)";
                $res = pg_query($con, $sql);
            }elseif($qtde_km_hidden  != $qtde_km){
                $sql = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, ' Alterado Manualmente de $qtde_km_hidden para $qtde_km', false, 2)";
                $res = pg_query($con, $sql);
            }
        }   
    }
}

function auditoria_valor_adicional_pressure(){
    global $con, $campos, $os, $login_fabrica;

    if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){
        
        if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais' AND tbl_auditoria_os.reprovada IS NULL AND tbl_auditoria_os.liberada IS NULL ", $os) === true){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais')";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de aplicação indevida para a OS");

                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria aplicação indevida");

            }
        }
    }
}

function auditoria_peca_lancada_pressure(){
    global $con, $campos, $os, $login_fabrica;

    if(verifica_peca_lancada() === true){

        $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

        if($busca["resultado"]){
            $auditoria = $busca["auditoria"];

            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'Aguardando aprovação para gerar pedido')";
            pg_query($con,$sql);

            if(strlen(pg_last_error()) > 0){
                throw new Exception("Erro ao criar auditoria de aprovação de pedido para a OS");

            }else{
                return true;
            }
        }else{
            throw new Exception("Erro ao criar auditoria de aprovação de pedido para a OS");

        }
    }

}


?>
