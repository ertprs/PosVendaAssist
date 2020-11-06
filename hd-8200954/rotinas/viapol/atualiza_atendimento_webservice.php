<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) .'/../../class/communicator.class.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;
$mailTc = new TcComm('smtp@posvenda');

$login_fabrica  = 189;
$tdocs          = new TDocs($con, $login_fabrica, 'rotina');
$origem         = "0";
$token          = "KTMcnSJEbOBF+BgQHxKnEQ==";
$usuario        = "admin";
if ($_serverEnvironment == 'development') {
	$url_api = "https://portalvendas.dev.rpmsfa.com/api/telecontrol/atendimento";
} else {
	$url_api = "https://www.portalvendas.viapol.com.br/api/telecontrol/atendimento";
}
$arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
$processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina} | grep -v grep"));
$arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

$count_routine = 0;

foreach ($processos as $value) {
	if (preg_match("/(.*)php (.*)\/viapol\/{$arquivo_rotina}/", $value)) {
		$count_routine += 1;
	}
}

if($count_routine > 3) {
	exit('Ainda em execuзгo');
}

if(!function_exists('retira_acentos')){
    function retira_acentos( $texto ){
        $array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З","є","&","%","$","?","@", "'" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_","" );
        return str_replace( $array1, $array2, $texto );
    }
}

$sql = "SELECT hc.hd_chamado,
               hc.data AS data_abertura,
               hc.status,
               hce.nome,
               hce.cpf,
               hce.cpf AS cnpj,
               hce.rg,
               hce.data_nascimento,
               hce.email,
               hce.pedido,
               hce.hd_chamado_origem,
               hce.hd_motivo_ligacao,
               hce.array_campos_adicionais,
               hce.hd_subclassificacao,
               hc.hd_classificacao,
               at.admin AS atendente_id,
               at.nome_completo AS atendente_nome,
               ab.admin AS aberto_id,
               ab.nome_completo AS aberto_nome,
               cla.descricao AS nome_classificacao,
               ho.descricao AS nome_origem,
               mt.descricao AS nome_providencia,
               subc.descricao AS nome_subclassificacao,
               hce.celular,
               hce.fone,
               hce.consumidor_revenda AS tipo,
               hce.fone2,
               hce.endereco,
               hce.numero,
               hce.reclamado,
               hce.complemento,
               hce.bairro,
               hce.cep,
               hce.cidade,
               cid.nome AS nome_cidade,
               cid.estado,
               pe.pedido_cliente AS pedido,
               pe.pedido AS pedido_tc,
               pf.codigo_posto AS codigo,
               hcie.hd_chamado_item_externo,
               hci.hd_chamado_item,
               hci.data AS data_atendimento,
               hci.comentario,
               hci.interno,
               hci.admin_transferencia
          FROM tbl_hd_chamado hc
          JOIN tbl_hd_chamado_extra hce         USING(hd_chamado)
          JOIN tbl_hd_chamado_item hci          USING(hd_chamado)
          JOIN tbl_admin at                     ON at.admin = hc.atendente AND at.fabrica = {$login_fabrica}
          JOIN tbl_admin ab                     ON ab.admin = hc.admin AND ab.fabrica = {$login_fabrica}
          JOIN tbl_pedido pe                    ON pe.pedido = hce.pedido AND pe.fabrica = {$login_fabrica}
      LEFT JOIN tbl_posto_fabrica pf            ON pf.posto = hce.posto AND pf.fabrica = {$login_fabrica}
      LEFT JOIN tbl_cidade cid                  ON cid.cidade = hce.cidade
      LEFT JOIN tbl_hd_classificacao cla        ON cla.hd_classificacao = hc.hd_classificacao
      LEFT JOIN tbl_hd_motivo_ligacao mt        ON mt.hd_motivo_ligacao = hce.hd_motivo_ligacao
      LEFT JOIN tbl_hd_chamado_origem ho        ON ho.hd_chamado_origem = hce.hd_chamado_origem
      LEFT JOIN tbl_hd_subclassificacao subc    ON subc.hd_subclassificacao = hce.hd_subclassificacao
      LEFT JOIN tbl_hd_chamado_item_externo hcie ON hcie.hd_chamado_item = hci.hd_chamado_item AND hcie.fabrica = {$login_fabrica}
     WHERE hc.fabrica_responsavel = {$login_fabrica}
/*
and hc.hd_chamado=7433738
 and hci.hd_chamado_item=36321399
*/
         AND hce.pedido IS NOT NULL
		   AND hci.comentario IS NOT NULL 
			AND hci.data > current_timestamp -  interval '30 minutes'
			AND hc.status not in ('Cancelado')";
$res = pg_query($con, $sql);

//exit($sql);

$dados_api = [];
if (pg_num_rows($res) > 0) {
    $logErro    = [];
    $logSucesso = [];
    foreach (pg_fetch_all($res) as $key => $rows) {
        if (strlen($rows["hd_chamado_item_externo"]) > 0) {
            continue;
        }

        $nome_origem  =  strtolower(retira_acentos($rows["nome_origem"]));

	if ($nome_origem  == "producao") {
		continue;
	}



        $dados_api["consumidor"]["tipo"]                        = strlen($rows["cpf"]) >= 14 ? "J" : "F";
        $dados_api["consumidor"]["nome"]                        = utf8_encode($rows["nome"]);
        $dados_api["consumidor"]["cpf"]                         = $rows["cpf"];
        $dados_api["consumidor"]["cnpj"]                        = $rows["cnpj"];
        $dados_api["consumidor"]["rg"]                          = $rows["rg"];
        $dados_api["consumidor"]["data_nascimento"]             = $rows["data_nascimento"];
        $dados_api["consumidor"]["email"]                       = $rows["email"];
        $dados_api["consumidor"]["telefone"]                    = $rows["fone"];
        $dados_api["consumidor"]["celular"]                     = $rows["celular"];
        $dados_api["consumidor"]["telefone_comercial"]          = $rows["fone2"];
        $dados_api["consumidor"]["cep"]                         = $rows["cep"];   
        $dados_api["consumidor"]["estado"]                      = $rows["estado"];
        $dados_api["consumidor"]["cidade"]                      = utf8_encode($rows["nome_cidade"]);
        $dados_api["consumidor"]["bairro"]                      = substr(utf8_encode($rows["bairro"]), 0, 50);  
        $dados_api["consumidor"]["endereco"]                    = utf8_encode($rows["endereco"]);
        $dados_api["consumidor"]["numero"]                      = utf8_encode($rows["numero"]);
        $dados_api["consumidor"]["complemento"]                 = utf8_encode($rows["complemento"]);
        $dados_api["consumidor"]["melhor_horario_contato"]      = "";
        $dados_api["atendimento"]["pedido"]                     = $rows["pedido"];
        $dados_api["atendimento"]["protocolo"]                  = $rows["hd_chamado"];
        $dados_api["atendimento"]["origem"]                     = substr(utf8_encode($rows["nome_origem"]), 0, 20);
        $dados_api["atendimento"]["classificacao"]["id"]        = $rows["hd_classificacao"];
        $dados_api["atendimento"]["classificacao"]["descricao"] = utf8_encode($rows["nome_classificacao"]);
        $dados_api["atendimento"]["posto"]["codigo"]            = $rows["codigo"];
        $dados_api["atendimento"]["abrir_os"]                   = "";
        $dados_api["atendimento"]["data_agendamento"]           = "";

        $specialChars    = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, "ISO-8859-1"));
        $xcomentario     = utf8_encode($rows["comentario"]);
        $xxcomentario    = str_replace(["&nbsp;"], " ", $xcomentario);
        $xxxcomentario   = strip_tags($xxcomentario);
        $xxxxcomentario  = strtr($xxxcomentario, $specialChars);
        
        $dados_api["atendimento"]["observacao"]                 = utf8_encode($xxxxcomentario);

        if (strlen($rows["admin_transferencia"]) > 0) {
            $dados_admin = getAdmin($rows["admin_transferencia"]);
            $dados_api["atendimento"]["destinatario"]["id"]   = $dados_admin["admin"];
            $dados_api["atendimento"]["destinatario"]["nome"] = utf8_encode($dados_admin["nome_completo"]);
        } else {
            $dados_api["atendimento"]["destinatario"]["id"]   = "";
           $dados_api["atendimento"]["destinatario"]["nome"] = "";
        }
        $dados_api["atendimento"]["observacao_sac"]             = "";
        $dados_api["atendimento"]["descricao"]                  = utf8_encode($rows["reclamado"]);
        $dados_api["atendimento"]["situacao"]                   = $rows["status"];
        $dados_api["atendimento"]["aberto_por"]["id"]           = $rows["aberto_id"];
        $dados_api["atendimento"]["aberto_por"]["nome"]         = utf8_encode($rows["aberto_nome"]);
        $dados_api["atendimento"]["atendente"]["id"]            = $rows["atendente_id"];
        $dados_api["atendimento"]["atendente"]["nome"]          = utf8_encode($rows["atendente_nome"]);
        $dados_api["atendimento"]["data_abertura"]              = $rows["data_abertura"];
        $dados_api["atendimento"]["providencia"]["id"]          = $rows["hd_motivo_ligacao"];
        $dados_api["atendimento"]["providencia"]["nome"]        = utf8_encode($rows["nome_providencia"]);
        $dados_api["atendimento"]["data_atendimento"]           = $rows["data_atendimento"];
        $dados_api["atendimento"]["produtos"]                   =  getProdutos($rows["hd_chamado"]);




//$dd = json_encode($dados_api);
//echo "<pre>".print_r($dd,1)."</pre>";exit;

        $retornoAPI = postViapol($dados_api);
//echo "<pre>".print_r($retornoAPI,1)."</pre>";
        if (empty($retornoAPI)) {

            $logErro[]    = "NГЈo foi possivel comunicar com webservice da VIAPOL - <b>hd_chamado:</b> " . $rows["hd_chamado"] ." - <b>hd_chamado_item:</b> " . $rows["hd_chamado_item"] ." - <b>pedido_tc:</b> " . $rows["pedido_tc"] ." - <b>pedido_viapol:</b> " . $rows["pedido"] ;

        } else {

            if (isset($retornoAPI["erro"]) && $retornoAPI["erro"]) {
                $logErro[]    = $retornoAPI["msg"] . " - <b>hd_chamado:</b> " . $rows["hd_chamado"] ." - <b>hd_chamado_item:</b> " . $rows["hd_chamado_item"] ." - <b>pedido_tc:</b> " . $rows["pedido_tc"] ." - <b>pedido_viapol:</b> " . $rows["pedido"] ;
            } else {
                $sqlExterno = "INSERT INTO tbl_hd_chamado_item_externo (fabrica,hd_chamado,hd_chamado_item) VALUES({$login_fabrica},".$rows["hd_chamado"].",".$rows["hd_chamado_item"].")";
                $resExterno = pg_query($con, $sqlExterno);
                $logSucesso[] = $retornoAPI["msg"] . " - <b>hd_chamao:</b> " . $rows["hd_chamado"] ." - <b>hd_chamado_item:</b> " . $rows["hd_chamado_item"] ." - <b>pedido_tc:</b> " . $rows["pedido_tc"] ." - <b>pedido_viapol:</b> " . $rows["pedido"] ;
            }

        }
    }

}

if (count($logErro) > 0) {
    $res = $mailTc->sendMail(
        'felipe.marttos@telecontrol.com.br;luis.carlos@telecontrol.com.br',
        "Log de erro - Atualiza Callcenter Webservice Viapol - " . date("d/m/Y H:i:s"),
        montaEmail($logErro, 'Erro'),
        "noreply@telecontrol.com.br"
    );
}
/*

if (count($logSucesso) > 0) {
    $res = $mailTc->sendMail(
        'felipe.marttos@telecontrol.com.br;luis.carlos@telecontrol.com.br',
        "Log de sucesso - Atualiza Callcenter Webservice Viapol - " . date("d/m/Y H:i:s"),
        montaEmail($logSucesso, 'Sucesso'),
        "noreply@telecontrol.com.br"
    );
}
*/

function montaEmail($log, $tipo)
{
    if ($tipo == 'Sucesso') {
        $cor = "green";
    } else {
        $cor = "#d90000";
    }
   
    $body = '<table>
    <tr>
        <td style="background:'.$cor.';color:#ffffff;font-family: arial;padding:10px"><b>Log de '.$tipo.'</b></td>
    </tr>
    ';
    $i = 0;
    foreach ($log as $key => $value) {
        $cor =  ($i % 2 == 0) ? "#eeeeee" : "#ffffff";        
        $body .= '
        <tr style="background:'.$cor.'">
            <td style="font-family: arial;padding:10px">'.$value.'</td>
        </tr>
        ';
        $i++;
    }

    $body .= '</table>';

    return $body;

}

function getUltimaInteracao($hd_chamado)
{
    global $con, $login_fabrica;


    $sql = "SELECT comentario,interno,admin_transferencia  FROM tbl_hd_chamado_item 
                    WHERE hd_chamado = {$hd_chamado} 
                    ORDER BY data DESC LIMIT 1"; 
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return [
            'comentario' => strip_tags(pg_fetch_result($res, 0, 'comentario')), 
            'interno' => strip_tags(pg_fetch_result($res, 0, 'interno')), 
            'admin_transferencia' => pg_fetch_result($res, 0, 'admin_transferencia')
        ];
    }
    return "";
}

function getAdmin($admin)
{
    global $con, $login_fabrica;


    $sql = "SELECT *
              FROM tbl_admin
                    WHERE admin = {$admin} AND fabrica = $login_fabrica"; 
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        return pg_fetch_assoc($res);
    }
    return "";
}

function getProdutos($hd_chamado)
{
    global $con, $login_fabrica;

    $retorno = [];
    $sql = "SELECT distinct pd.referencia, 
                   hti.nota_fiscal,
                   dr.descricao AS nome_defeito,
                   dr.codigo AS codigo_defeito,
                   pd.descricao
              FROM tbl_hd_chamado_item hti
              JOIN tbl_produto pd ON pd.produto = hti.produto AND pd.fabrica_i = {$login_fabrica}
             LEFT JOIN tbl_defeito_reclamado dr ON dr.defeito_reclamado = hti.defeito_reclamado AND dr.fabrica = {$login_fabrica}
             WHERE hti.hd_chamado = {$hd_chamado} 
               AND hti.produto IS NOT NULL 
              "; 
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        foreach (pg_fetch_all($res) as $key => $value) {
            $retorno[$key]["referencia"]  = $value["referencia"];
            $retorno[$key]["descricao"]   = utf8_encode($value["descricao"]);
            $retorno[$key]["nota_fiscal"] = $value["nota_fiscal"];
            $retorno[$key]["defeito_reclamado"]["codigo"] = $value["codigo_defeito"];
            $retorno[$key]["defeito_reclamado"]["defeito_reclamado"] = $value["nome_defeito"];
        }
    }
    return $retorno;
}

function postViapol($conteudo) {
    global $con, $login_fabrica, $url_api, $origem, $token, $usuario;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url_api,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($conteudo),
      CURLOPT_HTTPHEADER => array(
         "content-type: application/json",
        "x-app-origem: {$origem}",
        "x-app-token: {$token}",
        "x-app-usuario: {$usuario}"
      ),
    ));

    $response = curl_exec($curl);
    $xresponse = json_decode($response,1);
    $err = curl_error($curl);
    curl_close($curl);
//echo "<pre>".print_r($xresponse,1)."</pre>";exit;


    if ($err) {
      return ["erro" => true, "msg" => json_decode($err,1)];
    } 
    if (empty($xresponse)) {
        return ["erro" => true, "msg" => "Sem resposta"];
    } elseif (isset($xresponse["status"]) && $xresponse["status"] === false) {
        return ["erro" => true, "msg" => $xresponse["message"]];
    } else {
        return ["erro" => false, "msg" => $xresponse["message"]];
    }
}
