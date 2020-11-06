<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../funcoes.php';
require dirname(__FILE__) .'/../../class/communicator.class.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Log;
use Posvenda\LogError;
$mailTc = new TcComm('smtp@posvenda');

$login_fabrica  = 183;
#suporte@cozinhasitatiaia.com.br

$token   = "suporte@cozinhasitatiaia.com.br/token:6rU0zz68Poc0zJkcKo4pvXSX5oUbMhrAhI1LYVUF";
$url_api = "https://cozinhasitatiaia.zendesk.com/api/v2/tickets/";

$sql = "
    SELECT
        hdc.fabrica,
        hdci.hd_chamado_item,
        hdc.campos_adicionais AS hd_campos_adicionais,
        hdc.hd_chamado,
        TO_CHAR(hdci.data, 'DD/MM/YYYY HH24:MI:SS') AS data,
        hdci.comentario,
        ad.nome_completo AS atendente,
        hdce.array_campos_adicionais,
        hdci.status_item,
        hdci.produto,
        hdcl.descricao AS hd_classificacao,
        hdce.posto_nome AS ponto_referencia,
        hdce.nome AS consumidor_nome,
	    p.nome,
        p.cnpj,
        o.sua_os,
        pf.contato_fone_comercial,
        pf.contato_fax,
        pf.contato_cel
    FROM tbl_hd_chamado hdc
    JOIN tbl_hd_chamado_item hdci USING(hd_chamado)
    JOIN tbl_hd_chamado_extra hdce USING(hd_chamado)
    LEFT JOIN tbl_os o ON o.os = hdci.os AND o.fabrica = {$login_fabrica}
    LEFT JOIN tbl_posto_fabrica pf ON pf.posto = hdci.posto AND pf.fabrica = {$login_fabrica}
    LEFT JOIN tbl_posto p ON p.posto = pf.posto
    LEFT JOIN tbl_hd_chamado_item_externo hdcie ON hdcie.hd_chamado_item =  hdci.hd_chamado_item AND hdcie.fabrica = {$login_fabrica}
    JOIN tbl_admin ad ON ad.admin = hdc.admin AND ad.fabrica = {$login_fabrica}
    JOIN tbl_hd_classificacao hdcl ON hdcl.hd_classificacao = hdc.hd_classificacao AND hdcl.fabrica = {$login_fabrica}
    WHERE hdc.fabrica = {$login_fabrica}
    AND hdci.comentario IS NOT NULL
    AND hdci.hd_motivo_ligacao IS NULL
    AND hdcie.hd_chamado_item IS NULL
    AND hdc.campos_adicionais ? 'id_zendesk' 
    AND hdc.campos_adicionais->>'id_zendesk' <> '' ";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
    
        $logErro    = [];
    foreach (pg_fetch_all($res) as $key => $rows) {
     
        $id_zendesk = "";
	$pedido_sap = "";
        $fone = "";
        $logSucesso = [];
        $dados_envio = [];

	$hd_campos_adicionais = $rows['hd_campos_adicionais'];
	if (!empty($hd_campos_adicionais)){
		$hd_campos_adicionais = json_decode($hd_campos_adicionais, true);
		$id_zendesk = $hd_campos_adicionais["id_zendesk"];
	}


        if (empty($id_zendesk)){ continue; }
        
	$url_zendesk = $url_api.$id_zendesk.'.json';
        $public = false;

	$campos_adicionais = $rows['array_campos_adicionais'];
	if (!empty($campos_adicionais)){
		$campos_adicionais = json_decode($campos_adicionais, true);
		$pedido_sap = $campos_adicionais["pedido_sap"];	
	}

        if (strlen($rows['sua_os']) > 0){
            $public = true;
            if (strlen($rows['contato_fone_comercial']) > 0){
                $fone = $rows['contato_fone_comercial'];
            }else if (strlen($rows['contato_fax']) > 0){
                $fone = $rows['contato_fax'];
            }
        
            $rows['comentario'] = "Prezado(a) {$rows['consumidor_nome']},\r \r Obrigado(a) por entrar em contato conosco.\r \r
                Nosso(a) agente {$rows['atendente']} realizou o atendimento através do ticket {$id_zendesk}.\r \r
                Foi gerada a Ordem de Serviço (OS) de nº {$rows['sua_os']} para concluir os ajustes necessários no seu produto.\r \r
                Pedimos que aguarde ou entre em contato com a nossa autorizada para agendar o atendimento.
                Abaixo os dados do posto de serviço autorizado responsável por esse atendimento:\r \r
                {$rows['nome']} \r \r
                $fone \r \r \r
                Conheça nossa Loja Virtual, onde encontrará nossos produtos no endereço: https://loja.cozinhasitatiaia.com.br.\r \r
                E caso tenha dúvidas técnicas e precisar de algum outro auxilio, acesse nosso site de atendimento no endereço: https://atendimento.cozinhasitatiaia.com.br.\r \r
                Atenciosamente.";
        }

        if ($rows['status_item'] == "Resolvido"){
            $status = "solved";
        } else if ($rows['status_item'] == "Cancelado") {
            $status = "closed";
        }else{
            $status = "hold";
        }

        $hd_classificacao = $rows['hd_classificacao'];
        $hd_classificacao = retira_acentos($hd_classificacao);
        $hd_classificacao = strtolower($hd_classificacao);
  
        if ($hd_classificacao == "reclamacao - moveis" AND strlen(trim($pedido_sap)) > 0 AND $key == 0){
            $public = true;
            
            $rows['comentario'] = "Olá, {$rows['consumidor_nome']}! \r \r
                Obrigado por entrar em contato com a Cozinhas Itatiaia. \r \r
                Registramos sua solicitação nº {$id_zendesk} e estamos enviando para você as peças conforme a análise realizada.\r \r
                Nº do pedido: {$pedido_sap} \r \r
                A entrega será feita no prazo de 15 a 30 dias. \r \r
                Conheça nossa Loja Virtual, onde encontrará nossos produtos no endereço: https://loja.cozinhasitatiaia.com.br \r \r
                E caso tenha dúvidas técnicas e precisar de algum auxilio, acesse nosso site de atendimento no endereço: https://atendimento.cozinhasitatiaia.com.br \r \r
                Abraços, \r \r
                {$rows['atendente']} \r \r
                Cozinhas Itatiaia.";
        }

        $comentario = "{$rows['comentario']} \r \r Data da interação: {$rows['data']}";
        
        $specialChars    = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, "ISO-8859-1"));
        $xcomentario     = utf8_encode($comentario);
        
        $xxcomentario    = str_replace(["&nbsp;"], " ", $xcomentario);
        $xxxcomentario   = strip_tags($xxcomentario);
        $xxxxcomentario  = strtr($xxxcomentario, $specialChars);
        $ponto_referencia = utf8_encode($rows['ponto_referencia']);
     
        $array_campos = array(
            "360016328192" => $rows['sua_os'], #numero da OS
            "360025356492" => $rows["hd_chamado"] #hd_chamado
        );
	
        if (empty($rows['sua_os'])){
            unset($array_campos['360016328192']);
        }   
	
        $dados_envio = array(
            "ticket" => array(
                "status" => $status,
                "comment" => array(
                    "body" => $xxxcomentario,
                    "public" => $public
                ),
                "custom_fields" => $array_campos
            ),
        );
        
        $retornoAPI = postItatiaia($dados_envio, $url_zendesk);
        
        if (empty($retornoAPI)) {
            $logErro[] = "Não foi possivel comunicar com zendesk Itatiaia - <b>hd_chamado:</b> " . $rows["hd_chamado"];
        } else {
            if (isset($retornoAPI["erro"]) && $retornoAPI["erro"]) {
               $logErro[]    = $retornoAPI["msg"] . " - <b>hd_chamado:</b> " . $rows["hd_chamado"]. " - <b>Ticket:</b> ". $id_zendesk;
		$pos = strpos($retornoAPI["msg"], 'o status Fechado evita');
		
		if($pos != false){
        	        $sqlExterno = "INSERT INTO tbl_hd_chamado_item_externo (fabrica,hd_chamado,hd_chamado_item,id_ligacao) VALUES({$login_fabrica},".$rows["hd_chamado"].",".$rows["hd_chamado_item"].", 'Status: o status Fechado evita a atualização do ticket')";
                	$resExterno = pg_query($con, $sqlExterno);
		}
	    } else {
                $sqlExterno = "INSERT INTO tbl_hd_chamado_item_externo (fabrica,hd_chamado,hd_chamado_item) VALUES({$login_fabrica},".$rows["hd_chamado"].",".$rows["hd_chamado_item"].")";
                $resExterno = pg_query($con, $sqlExterno);
                $logSucesso[] = $retornoAPI["msg"] . " - <b>hd_chamao:</b> " . $rows["hd_chamado"] ." - <b>hd_chamado_item:</b> " . $rows["hd_chamado_item"];
            }
        }
    }
}



if (count($logErro) > 0) {
	$res = $mailTc->sendMail(
        'luis.carlos@telecontrol.com.br;rafael.santos@cozinhasitatiaia.com.br',
        "Log de erro - Atualiza zendesk Itatiaia - " . date("d/m/Y H:i:s"),
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

function montaEmail($log, $tipo){
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

function postItatiaia($conteudo, $url) {
    global $con, $login_fabrica, $token;
	#echo json_encode($conteudo);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        #token usuario FEFO => CtoeTupiXXG6aV6JGGnfUm2Q4VKfJWOOoCHpxC81
        // EXEMPLO VIA USUARIO SENHA: CURLOPT_USERPWD => 'felipemarttos@hotmail.com:parnala01', 
        // EXEMPLO VIA TOKEN: CURLOPT_USERPWD => "ricardo.tamiao@acaciaeletro.com.br/token:CtoeTupiXXG6aV6JGGnfUm2Q4VKfJWOOoCHpxC81",
        CURLOPT_USERPWD => $token,
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($conteudo),
        CURLOPT_HTTPHEADER => array(
            "content-type: application/json",
        )
    ));

    $response = curl_exec($curl);
    $xresponse = json_decode($response,1);
    $err = curl_error($curl);
    $erro_fechamento = $xresponse["error"];
    
    if ($err) {
       return ["erro" => true, "msg" => json_decode($err,1)];
    } 
    
    if (empty($xresponse)) {
        return ["erro" => true, "msg" => "Sem resposta"];
    }else if (strlen(trim($erro_fechamento)) > 0){
	
	if (strlen($xresponse["details"]["status"][0]["description"]) > 0){
		$msg_erro = $xresponse["details"]["status"][0]["description"];
	}elseif (strlen($xresponse["details"]["base"][0]["description"]) > 0){
		$msg_erro = $xresponse["details"]["base"][0]["description"];
	}


        if (strlen($xresponse["description"]) > 0 AND $xresponse['description'] == "Not found"){
		$msg_erro = $xresponse["description"];
        }
        return ["erro" => true, "msg" => $msg_erro];
    }else{
        return ["success" => true];	
    }
}
