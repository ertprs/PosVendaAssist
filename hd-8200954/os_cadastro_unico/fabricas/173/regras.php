<?php
$regras["os|nota_fiscal"] = array(
    "obrigatorio" => false
);
$regras["produto|serie"] = array(
    "function" => array("valida_numero_de_serie_jfa")
);
$regras["os|data_abertura"] = array(
    "obrigatorio" => true,
    "regex"       => "date",
    "function"    => array("valida_data_abertura_jfa")
);
$regras["os|data_compra"] = array(
    "obrigatorio" => false,
    "regex"       => "date",
    "function"    => array("valida_data_compra")
);
$regras["consumidor|email"] = array(
    "regex" => "email",
    "obrigatorio" => false
);
$regras["revenda|nome"] = array(
    "obrigatorio" => false
);
$regras["revenda|cnpj"] = array(
    "obrigatorio" => false,
    "function"    => array("valida_revenda_cnpj")
);
$regras["revenda|cidade"] = array(
    "obrigatorio" => false
);
$regras["revenda|estado"] = array(
    "obrigatorio" => false
);

$antes_valida_campos      = "regras_campos_obrigatorios_jfa";

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

$anexos_obrigatorios      = [];

$auditorias = array(
	"auditoria_peca_critica",
    "auditoria_troca_obrigatoria",
    "auditoria_valor_adicional_jfa",
    "auditoria_numero_de_serie_jfa"
);

$funcoes_fabrica = array("verifica_estoque_peca","auditoria_reincidente_jfa");

function grava_os_fabrica(){

    global $campos;

    $tecnico = $campos["os"]["id_tecnico"];

    if (empty($tecnico)) {
        $tecnico = "null";
    }

    return array(
        "tecnico"   => "{$tecnico}"
    );

}

function regras_campos_obrigatorios_jfa() {
    global $campos, $regras, $login_posto_interno, $areaAdmin, $con, $login_fabrica, $valida_garantia, $anexos_obrigatorios;
    
    if ($areaAdmin == true) {
        $posto = $campos['posto']['id'];
        
        if (!empty($posto)) {
            $sql = "
                SELECT pf.posto 
                FROM tbl_posto_fabrica pf 
                INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
                WHERE pf.fabrica = {$login_fabrica}
                AND pf.posto = {$posto}
                AND tp.posto_interno IS TRUE
            ";
            $res = pg_query($con, $sql);
            
            if (pg_num_rows($res) > 0) {
                $valida_garantia = "";
            } else {
                $valida_garantia = "valida_garantia_jfa";
            }
        }
    } else {
        if ($login_posto_interno === true){
            $valida_garantia = "";
        }else{
            $valida_garantia = "valida_garantia_jfa";
        }
    }



    if (!empty($campos["os"]["data_compra"]) || !empty($campos["os"]["nota_fiscal"])) {
        
        $regras["os|nota_fiscal"] = array(
            "obrigatorio" => true
        );
        $regras["os|data_compra"] = array(
            "obrigatorio" => true,
            "regex"       => "date",
            "function"    => array("valida_data_compra")
        );
        
        $anexos_obrigatorios[] = "notafiscal";

    }

    if ($login_posto_interno === true){
        $regras["os|defeito_reclamado"] = array(
            "obrigatorio" => false
        );
    }
}

########### Validações - Início ########### 
/**
 * Função para validação do numero de série
 */
function valida_numero_de_serie_jfa(){
    global $con, $campos, $login_fabrica, $msg_erro;

    $produto = $campos["produto"]["id"];
    $serie = $campos["produto"]["serie"];

    $sql = "SELECT  numero_serie_obrigatorio,
                    referencia 
                FROM tbl_produto 
                WHERE produto = {$produto} 
                AND fabrica_i = {$login_fabrica}
                AND numero_serie_obrigatorio IS TRUE";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");
        $prod_referencia = pg_fetch_result($res, 0, "referencia");
        
        if($numero_serie_obrigatorio == "t" && empty($serie)){
            throw new Exception("Para este produto o número de Série é obrigatório");
        } else {
            $dataSerieInvalida = false;
            $serieData = substr($serie, 0,4);
            $serieDataMes = substr($serie, 0,2);
            $serieDataAno = substr($serie, 2,2);
            $serieReferencia = substr($serie, 4,3);
            
            if (!is_numeric($serieData)) {
                $dataSerieInvalida = true;
            }

            if (!preg_match('/^(0[1-9]|1[0-2])$/', $serieDataMes)) {
                $dataSerieInvalida = true;
            }

            if (!preg_match('/^(10|[0-9][0-9])$/', $serieDataAno)) {
                $dataSerieInvalida = true;
            }

            //verificar se é a referencia do produto
            /*if ( mb_strtoupper($prod_referencia) != mb_strtoupper($serieReferencia) ) {
                $dataSerieInvalida = true;
            }*/

            if ($dataSerieInvalida == true) {
                throw new Exception("Número de Série inválido!");
            }
        }
    }
}

/**
 * Função para validação de data de abertura
 */
function valida_data_abertura_jfa() {
    global $campos, $os, $login_posto_interno;

    $data_abertura = $campos["os"]["data_abertura"];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 3 days")) {
            if ($login_posto_interno === true){
                throw new Exception("Data de abertura não pode ser anterior a 7 dias");
            }else{
                throw new Exception("Data de abertura não pode ser anterior a 3 dias");
            }
        }
    }
}

/**
 * Função para validar a garantia do produto
 */
function valida_garantia_jfa($boolean = false) {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];
    $serie         = $campos["produto"]["serie"];

    if (!empty($produto) && !empty($data_abertura)) {
        $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $garantia = pg_fetch_result($res, 0, "garantia");

            if (!empty($data_compra)) {
                if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                    if ($boolean == false) {
                        $msg_erro["msg"][] = traduz("Produto fora de garantia");
                    } else {
                        return false;
                    }
                } else if ($boolean == true) {
                    return true;
                }
            } else {
                $serieDataMes = substr($serie, 0,2);
                $serieDataAno = substr($serie, 2,2);
                $serieDataDia = date("t",mktime(0,0,0,$serieDataMes,"01",$serieDataAno));
                $serieData = $serieDataAno."-".$serieDataMes."-".$serieDataDia;

                if (strtotime($serieData." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                    if ($boolean == false) {
                        $msg_erro["msg"][] = traduz("Produto fora de garantia");
                    } else {
                        return false;
                    }
                } else if ($boolean == true) {
                    return true;
                }
            }
        }
    }
}
########### Validações - Fim ########### 

########### Auditorias - Início ########### 
function auditoria_numero_de_serie_jfa() {
    global $con, $login_fabrica, $campos, $os;

    $produto = $campos["produto"]["id"];
    $serie   = $campos["produto"]["serie"];
    $produtoReferencia   = $campos["produto"]["referencia"];

    $notPostoInterno = verifica_tipo_posto("posto_interno", "FALSE", $posto_id);

    if (!empty($serie) && $notPostoInterno == TRUE) {
        $serieData = substr($serie, 0,4);
        $serieDataMes = substr($serie, 0,2);
        $serieDataAno = substr($serie, 2,2);
        $serieReferencia = substr($serie, 4,3);
        
        if (!is_numeric($serie) || (strlen($serie) != 12 && strlen($serie) != 13)) {
            $dataSerieInvalida = true;
        }

        if (!preg_match('/^(0[1-9]|1[0-2])$/', $serieDataMes)) {
            $dataSerieInvalida = true;
        }

        //validar se os dois ultimos digitos do ano atual é menor que o informado
        //valida se o ano é 00
        if ( !preg_match('/^[0-9]{2}$/', $serieDataAno) || ($serieDataAno == '00') || ($serieDataAno > date('y')) ) {
            $dataSerieInvalida = true;
        }

        //verificar se é a referencia do produto
        if ( mb_strtoupper($produtoReferencia) != mb_strtoupper($serieReferencia) ) {
            $dataSerieInvalida = true;
        }

        if ( $dataSerieInvalida == true ) {

            if (verifica_auditoria_unica("tbl_auditoria_status.numero_serie = 't' AND tbl_auditoria_os.observacao ILIKE '%número de série%'", $os) === true) {

                $busca = buscaAuditoria("tbl_auditoria_status.numero_serie = 't'");
    
                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
                            ({$os}, $auditoria_status, 'OS aguardando aprovação de número de série', true)";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    }
                } else {
                    throw new Exception("Erro ao lançar ordem de serviço");
                }
            }
        }
    }
}

function auditoria_reincidente_jfa(){

    global $login_fabrica, $campos, $os, $con, $login_admin, $os_reincidente_numero, $os_reincidente;

    $produto        = $campos["produto"]["id"];
    $serie          = $campos["produto"]["serie"];
    $auditoria_status = 1;

    $sql_verifica_auditoria = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
    $res_verifica_auditoria = pg_query($con, $sql_verifica_auditoria);

    if(pg_num_rows($res_verifica_auditoria) == 0){

        $sql = "SELECT tbl_os.os 
                    FROM tbl_os
                    INNER JOIN tbl_os_produto USING(os)
                    WHERE tbl_os_produto.serie = '{$serie}'
                        AND tbl_os_produto.produto = {$produto}
                        AND fabrica = {$login_fabrica}
                        AND os < {$os}
                        AND data_abertura >= (data_abertura - INTERVAL '90 days') 
                        ORDER BY os DESC 
                        LIMIT 1";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $os_reincidente_numero = pg_fetch_result($res, 0, 'os');

            $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];

                $observacao = "OS Reincidente com mesmo número de série, OS reincidente: ".$os_reincidente_numero;
	            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
	                    ({$os}, $auditoria_status, '$observacao')";
	            $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                } else {
                	$os_reincidente = true;
                }
            } else {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}

function auditoria_valor_adicional_jfa(){
    global $con, $login_fabrica, $campos, $os;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if(!empty($campos["os"]["valor_adicional"]) ){

        foreach ($campos["os"]["valor_adicional"] as $key => $value) {

            list($chave,$valor) = explode("|", $value);
            $valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);

        }

        $valores = json_encode($valores);

        $valores = str_replace("\\", "\\\\", $valores);

        grava_valor_adicional($valores,$os);

        if (verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%valores adicionais%'", $os) === true) {
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }

            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
                    ({$os}, $auditoria_status, 'OS em auditoria de Valores Adicionais', true)";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}
########### Auditorias - Fim ########### 

function grava_os_extra_fabrica(){
	global $campos;

	$posicao_componente  = $campos["os"]["obs_adicionais"];

	return array(
                "obs_adicionais" => "'$posicao_componente'"
        );
}
?>
