<?

include "ajax.php";

$data_abertura_fixa  = false;
$historico_alteracao = true;
$grava_defeito_peca  = true;
unset($usaSolucao);
$valida_anexo = "valida_anexo_fricon";

/* OS */
$regras["os|tipo_atendimento"]["obrigatorio"] = true;
$regras["os|nota_fiscal"]["obrigatorio"]      = true;
$regras["os|data_compra"]["obrigatorio"]      = true;

if($areaAdmin === true){

    /* OS */
    $regras["os|motivo_atraso"]["obrigatorio"]     = false;
    $regras["os|tipo_atendimento"]["obrigatorio"]  = true;
    $regras["os|defeito_reclamado"]["obrigatorio"] = true;

    /* Consumidor */
    $regras["consumidor|nome"]["obrigatorio"]      = true;
    $regras["consumidor|estado"]["obrigatorio"]    = false;
    $regras["consumidor|cidade"]["obrigatorio"]    = false;
    $regras["consumidor|telefone"]["obrigatorio"]  = false;

    /* Revenda */
    $regras["revenda|nome"]["obrigatorio"]         = true;
    $regras["revenda|cnpj"]["obrigatorio"]         = true;
    $regras["revenda|cnpj"]["function"]            = array();
    $regras["revenda|estado"]["obrigatorio"]       = true;
    $regras["revenda|cidade"]["obrigatorio"]       = true;

    /* Produto */
    $regras["produto|defeito_constatado"]["obrigatorio"] = false;
    $regras["produto|defeito_constatado"]["function"] = array();

}

/*HD - 4304128*/
$regras["consumidor|pais"]["obrigatorio"]     = true;
$regras["consumidor|cpf"]["obrigatorio"]      = true;
$regras["consumidor|cep"]["obrigatorio"]      = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;

if($areaAdmin !== true){

    $regras["os|defeito_reclamado"]["function"] = array("valida_defeito_reclamado_produto");

    /* Técnico */
    $regras["os|nome_tecnico"]["obrigatorio"] = true;
    $regras["os|rg_tecnico"] = array(
        "obrigatorio" => true,
        "function"    => array("valida_rg")
    );

    /* Consumidor */
    $regras["consumidor|cidade"]["obrigatorio"]   = false;
    $regras["consumidor|estado"]["obrigatorio"]   = false;
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|email"]["obrigatorio"]    = false;

    /* Produto */
    $regras["produto|grupo_defeito_constatado"]["obrigatorio"] = true;
    $regras["produto|defeito_constatado"]["obrigatorio"]       = true;
    $regras["produto|solucao"]["obrigatorio"]                  = true;

    $regras["produto|serie"] = array(
        "obrigatorio" => true,
        "function"    => array("valida_serie")
    );

    /* OS */
    $regras["os|motivo_atraso"]["function"]      = array("valida_data_72_horas");
    $regras["os|consumidor_revenda"]["function"] = array("valida_cnpj_os_revenda");

}

function valida_anexo_fricon(){

    global $con, $campos, $msg_erro, $os , $login_fabrica;

    $count_anexo = array();

    foreach ($campos["anexo"] as $key => $value) {
        if (strlen($value) > 0) {
            $count_anexo[] = "ok";
        }
    }

    if(strlen($campos["os"]["pedagio"]) > 0 && $campos["os"]["pedagio"] > 0 and !empty($os)){
	
		$sql = "SELECT tdocs, obs FROM tbl_tdocs WHERE referencia_id = {$os} and fabrica = $login_fabrica and referencia='os' --and obs::jsonb->0->>'typeId' = 'comprovante_pedagio'
             and situacao = 'ativo'";

		$res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0 ){           
            for($x=0; $x<pg_num_rows($res); $x++){
                $x_pedagio = pg_fetch_result($res, $x, 'obs');            
                $x_pedagio = json_decode($x_pedagio, true);
                $tipoanexo = $x_pedagio[0];                
                if($tipoanexo['typeId'] == 'comprovante_pedagio'){
                    $cont_tipoanexo += 1;
                }                
            }            
        }  

        if($cont_tipoanexo == 0){
            $msg_erro["msg"][] = "Insira o comprovante de pagamento do pedágio em anexo(s)";
        }

        /*if(pg_num_rows($res) ==  0 ){            
            $msg_erro["msg"][] = "Insira o comprovante de pagamento do pedágio em anexo(s)";
        }*/
    }

    if(strlen($campos["os"]["pedagio"]) > 0 && $campos["os"]["pedagio"] > 0 && empty($os)){
        $anexo_chave_tdocs = trim($_POST['anexo_chave']);        
        $sql = "SELECT tdocs FROM tbl_tdocs WHERE hash_temp = '$anexo_chave_tdocs' AND obs::jsonb->0->>'typeId' = 'comprovante_pedagio' AND fabrica = $login_fabrica AND situacao = 'ativo'";        
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) == 0){
            $msg_erro["msg"][] = 'Insira o comprovante de pagamento do pedágio em anexo(s)';
        }
    }
}


if(strlen($os) > 0 and $areaAdmin !== true){

    $sql_ma = "SELECT current_date - data_abertura AS interval FROM tbl_os WHERE os = {$os}";
    $res_ma = pg_query($con, $sql_ma);

    $interval = pg_fetch_result($res_ma, 0, "interval");

    /* maaior que 72 horas */
    if($interval > 3){

        $regras["os|motivo_atraso"]["obrigatorio"] = true;

    }

}

/* Valida se o número de Série e obrigatório */
function valida_serie(){

    global $con, $campos, $login_fabrica;

    $produto = $campos["produto"]["id"];

    $sql = "SELECT produto FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica} AND numero_serie_obrigatorio IS TRUE";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0 && strlen($campos["produto"]["serie"]) == 0){
        throw new Exception("Série obrigatoria para este produto");
    }

}

/* Valida RG*/
function valida_rg(){

    global $campos;

    $rg = $campos["os"]["rg_tecnico"];

    if(strlen($rg) > 15){
        throw new Exception("RG inválido");
    }

}

/**
 * Função para validação de data de abertura
 */
function valida_data_72_horas() {
    global $campos, $os, $con;

    $data_abertura = $campos["os"]["data_abertura"];

    if(strlen($data_abertura) > 0){

        list($d, $m, $a) = explode("/", $data_abertura);
        $data_abertura = $a."-".$m."-".$d;

        $sql = "SELECT current_date - '{$data_abertura}' AS intervalo";
        $res = pg_query($con, $sql);

        $interval = pg_fetch_result($res, 0, "intervalo");

        /* maaior que 72 horas */
        if($interval > 3){

            if(strlen($campos["os"]["motivo_atraso"]) == 0){

                throw new Exception("Insira o Motivo de Atraso para a abertura da OS");

            }

        }

    }

}

/* Valida CNPJ quando for OS de Revenda */
function valida_cnpj_os_revenda(){

    global $con, $login_fabrica, $campos;

    $tipo_os = $campos["os"]["consumidor_revenda"];

    if($tipo_os == "R"){

        /* CNPJ com pontos, hífen e barra - 11.111.111/1111-11 */
        if(strlen($campos["revenda"]["cnpj"]) < 14){
            throw new Exception("OS do tipo Revenda deve conter o CNPJ da Revenda");
        }

    }


}

/* Valida se o Defeito Reclamado pertence ao Produto da OS */
function valida_defeito_reclamado_produto(){

    global $con, $login_fabrica, $campos;

    $produto           = $campos["produto"]["id"];
    $defeito_reclamado = $campos["os"]["defeito_reclamado"];
    $familia_produto   = $campos["produto"]["familia"];

    if(strlen($produto) > 0 && strlen($defeito_reclamado) > 0){

        $sql = "SELECT tbl_diagnostico.diagnostico
                FROM tbl_diagnostico
                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
                WHERE tbl_diagnostico.familia = {$familia_produto}
                AND tbl_diagnostico.defeito_reclamado = {$defeito_reclamado}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) == 0){
            throw new Exception("Defeito Reclamado inválido para esse produto");
        }

    }


}

/* Auditoria de Número de Série */

function auditoria_numero_serie_fricon() {
    global $con, $login_fabrica, $campos, $os;

    $serie = $campos["produto"]["serie"];
    $posto = $campos["posto"]["id"];

    if(strlen($serie) > 0){

        $sql = "SELECT tbl_os.os
                FROM tbl_os
                JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                WHERE tbl_os.os <> {$os}
                AND tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.posto = {$posto}
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                AND tbl_os.serie IS NOT NULL
                AND tbl_os.serie = '{$serie}'
                ORDER BY tbl_os.data_abertura
                DESC LIMIT 1";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            if (verifica_auditoria(array(102,103), array(102,103), $os) === true) {
                $sql = "INSERT INTO tbl_os_status
                        (os, status_os, observacao)
                        VALUES
                        ({$os}, 102, 'OS aguardando aprovação de número de série')";
                #$res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                 #   throw new Exception("Erro ao lançar ordem de serviço");
                }
            }
        }

    }

}


/* Auditoria de KM */

function auditoria_km_fricon(){

    global $con, $login_fabrica, $os, $campos;

    if(verifica_auditoria(array(98, 99, 100), array(99), $os) == true){

        if ((($campos["os"]["qtde_km"] > 0) OR ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"])) && verifica_auditoria(array(98, 99, 100), array(98), $os) === true) {

            if($campos["os"]["qtde_km"] >  50){

                $sql = "INSERT INTO tbl_os_status
                        (os, status_os, observacao)
                        VALUES
                        ({$os}, 98, 'OS aguardando aprovação de KM')";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                }

            }

        }

    }

}

$auditorias = array(
    "auditoria_numero_serie_fricon",
    "auditoria_troca_obrigatoria",
    "auditoria_peca_critica",
    "auditoria_km_fricon"
);

/* Grava OS Fábrica */
function grava_os_fabrica(){

    global $campos, $login_fabrica, $con;

    if($campos['consumidor']['pais'] !== 'BR'){
        $regras["consumidor|cep"]["obrigatorio"]      = false;
    }

    $campos_bd = array();
    $pedagio = (strlen($campos["os"]["pedagio"]) == 0) ?  0.00 : $campos["os"]["pedagio"];

    if(strlen($campos["produto"]["grupo_defeito_constatado"]) > 0){
	    $campos_bd["defeito_constatado_grupo"] = $campos["produto"]["grupo_defeito_constatado"];
    }

    if(strlen($campos["produto"]["solucao"]) > 0){
	    $campos_bd["solucao_os"] = $campos["produto"]["solucao"];
    }

    if(strlen($campos["revenda"]["nome"]) > 0){
        $campos_bd["cliente_admin"] = verifica_revenda('cliente_admin');
    }

    if(strlen($pedagio) > 0){
	    $campos_bd["pedagio"] = $pedagio;
    }

    if(strlen($campos["os"]["defeito_reclamado"]) > 0){

        $sql_descricao = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = ".$campos["os"]["defeito_reclamado"]. " AND fabrica = $login_fabrica";
        $res_descricao = pg_query($con, $sql_descricao);
        if(pg_num_rows($res_descricao)> 0){
            $descricao = pg_fetch_result($res_descricao, 0, 'descricao');
        }
        $campos_bd["defeito_reclamado"] = $campos["os"]["defeito_reclamado"];
        $campos["os"]["defeito_reclamado"] = $descricao;
        
    }    

    if(strlen($campos["os"]["motivo_atraso"]) > 0){
        $campos_bd["motivo_atraso"] = $campos["os"]["motivo_atraso"];
    }

    if(strlen($campos["produto"]["marca"]) > 0){
        $campos_bd["marca"] = $campos["produto"]["marca"];
    }

    return $campos_bd;

}


/* OS Extra Fabrica */
function grava_os_extra_fabrica(){

    global $campos;

    return array(
        "tecnico" => "'".$campos["os"]["nome_tecnico"] . "|" . $campos["os"]["rg_tecnico"]."'"
    );

}

/* Funcções fábrica */

function atualiza_hd_chamado_item_fricon(){

    global $os, $campos, $con, $login_fabrica;

    $hd_chamado_item = $campos["os"]["hd_chamado_item"];

    if(strlen($hd_chamado_item) > 0){

        $sql = "UPDATE tbl_hd_chamado_item SET os = {$os} WHERE hd_chamado_item = {$hd_chamado_item}";
        $res = pg_query($con, $sql);

        if(strlen(pg_last_error()) > 0){
            throw new Exception("Erro ao atualizar o Chamado Item da Pré-OS");
        }

    }

}

$funcoes_fabrica = array("atualiza_hd_chamado_item_fricon");

function grava_historico($historico, $os, $posto, $login_fabrica, $login_admin){

    global $con;

    $sql = "SELECT nome_completo, login FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    $nome_admin = pg_fetch_result($res, 0, "nome_completo");
    $login = pg_fetch_result($res, 0, "login");

    /* Dados da alteração */
    $obs = "<div style=\"padding: 5px;\">";
        $obs .= "<strong>" . date("d/m/Y") . " - Admin: " . $nome_admin . " (" . $login . ") </strong> <br />";
        $obs .= "<strong>Alterações:</strong> <br />";
        $obs .= implode("<br />", $historico);
    $obs .= "</div>";

    /* Verificando se já tem interaçoes de LOG gravadas */
    $sql = "SELECT
                os_interacao
            FROM
                tbl_os_interacao
            WHERE
                tbl_os_interacao.os          = $os
                AND tbl_os_interacao.posto   = $posto
                AND tbl_os_interacao.fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) >= 1){

        $os_interacao = pg_fetch_result($res, 0, "os_interacao");

        /* Atualizando o campo "comentario", seja a 1ª vez que estiver vazio ou concatenando seu conteúdo com o que já tem gravado */
        $sql = "UPDATE
                    tbl_os_interacao
                SET
                    comentario = CASE WHEN comentario IS NULL
                                     THEN E'$obs'
                                 ELSE
                                     comentario || E'$obs'
                                 END
                WHERE tbl_os_interacao.os             = $os
                    AND tbl_os_interacao.posto        = $posto
                    AND tbl_os_interacao.fabrica      = $login_fabrica
                    AND tbl_os_interacao.os_interacao = $os_interacao";
    }else{
        
        /* Inserindo no campo "comentario", o seu conteúdo do 1º LOG */
        $programa_insert = $_SERVER['PHP_SELF'];
        $sql = "INSERT INTO tbl_os_interacao
                (
                    programa,
                    os,
                    admin,
                    comentario,
                    fabrica,
                    posto,
                    interno
                )
                VALUES
                (
                    '$programa_insert',
                    $os,
                    $login_admin,
                    E'$obs',
                    $login_fabrica,
                    $posto,
                    't'
                )";
    }

    $res = pg_query($con, $sql);

    if(strlen(pg_last_error()) > 0){
         throw new Exception("Erro ao gravar o historico de alterações de Peças da OS");
    }

    if(pg_affected_rows($res) >= 1){

        $sql = "SELECT
                    tbl_fabrica.nome AS nome_fabrica
                FROM
                    tbl_fabrica
                WHERE fabrica = $login_fabrica";

        $res = pg_query($con, $sql);

        if(strlen(pg_last_error()) > 0){
             throw new Exception("Erro ao recuperar o nome da Fábrica para o envio de historico de alterações de Peças da OS");
        }

        if(pg_num_rows($res) > 0){
            $nome_fabrica   = pg_fetch_result($res, 0, "nome_fabrica");
            $comentario_log = $obs;
        }

        /* Envio de e-mail do LOG */
        include "../class/email/PHPMailer/PHPMailerAutoload.php";

        $PhpMailer = new PHPMailer();

        $PhpMailer->setFrom("suporte@telecontrol.com.br");
        // $PhpMailer->addAddress("guilherme.silva@telecontrol.com.br");
        // $PhpMailer->addAddress("fernando.rodrigues@telecontrol.com.br");
        $PhpMailer->addAddress("apoio.sac@fricon.com.br");
        $PhpMailer->addAddress("coord.posvenda@fricon.com.br");
        $PhpMailer->isHTML(true);

        $PhpMailer->Subject = "OS $os - LOG de Alterações de Usuários";
        $PhpMailer->Body = "A/C de ".ucfirst($nome_fabrica).", segue abaixo o LOG da OS $os: <br /> <br /> ".$comentario_log;
        $PhpMailer->send();

    }else{
        throw new Exception("Erro ao enviar email com as alterações das Peças da OS");
    }

}

function grava_os_campo_extra_fabrica() {
    global $campos;

    $pais = $campos["consumidor"]["pais"];
    
    $return = array();
    if(strlen($pais) > 0){
        $return["pais"] = $pais;
    }

    return $return;
}


