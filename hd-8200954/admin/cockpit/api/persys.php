<?php

//config
global $accessEnv;

$develApplicationKey = '519e67fe737c5de1c5656f1c08f9eac902c5eb25';
#$productionApplicationKey = '701c59e0eb73d5ffe533183b253384bd52cd6973';
$productionApplicationKey = '3bee2ca5a245f8b278ed3da48361250d6c45fb81';
if($_serverEnvironment == 'development'){
    $applicationKey = $develApplicationKey;
    $accessEnv = 'DEVEL';
}else{
    $applicationKey= $productionApplicationKey;
    $accessEnv = 'PRODUCTION';
}

global $dePara;
$dePara = array(
    '158' => array(
        'KOF' => array(
           //'campo-arquivo'  => 'campo-apiCallcenter'
           'nomeFantasia'     => 'nome',
           'enderecoCliente'  => 'endereco',
           'bairroCliente'    => 'bairro',
           'cepCliente'       => 'cep',
           'cidadeCliente'    => 'cidade', //verificar tbl_ibge
           'estadoCliente'    => 'estado',
           'telefoneCliente'  => 'fone',
           'telefoneCliente2' => 'fone2',
           'modeloKof'        => 'produto',
           'osKof'            => 'sua_os', //tbl_hd_chamado_extra.sua_os
           'defeito'          => 'defeitoReclamado', //tbl_defeito_reclamado.codigo
           'dataAbertura'     => 'dataAbertura',
           'nomeContato'      => 'contatoNome',
           'comentario'       => 'obs',
           'numeroSerie'      => 'serie',
           'distancia'        => 'qtde_km',
           //'defeito'        => 'reclamado', retirado pois o defeito reclamado é cadastrado no sistema
           'tipoOrdem'        => 'tipoAtendimento',
           'patrimonioKof'    => 'patrimonioKof'
        ),
        'AMBV' => array(
           //'campo-arquivo'  => 'campo-apiCallcenter'
           'nomeFantasia'     => 'nome',
           'enderecoCliente'  => 'endereco',
           'bairroCliente'    => 'bairro',
           'cepCliente'       => 'cep',
           'cidadeCliente'    => 'cidade', //verificar tbl_ibge
           'estadoCliente'    => 'estado',
           'telefoneCliente'  => 'fone',
           'telefoneCliente2' => 'fone2',
           'modeloKof'        => 'produto',
           'osKof'            => 'sua_os', //tbl_hd_chamado_extra.sua_os
           'defeitoReclamado'=> 'defeitoReclamado', //tbl_defeito_reclamado.codigo
           'dataAbertura'     => 'dataAbertura',
           'nomeContato'      => 'contatoNome',
           'comentario'       => 'obs',
           'numeroSerie'      => 'serie',
           'distancia'        => 'qtde_km',
           'tipoOrdem'        => 'tipoAtendimento',
           'patrimonioKof'    => 'patrimonioKof',
	   'garantia'         => 'garantia'
        )
    )
);

// Caso não tenha nenhum cliente admin selecionado grava null
if (empty($cliente_admin)) {
    $cliente_admin = 'null';
}

global $camposAdicionais;
$camposAdicionais = array(
    '158' => array(
        'KOF' => array(
            'admin' => $login_admin,
            'atendente' => $login_admin,
            'categoria' => 'reclamacao_produto',
            'titulo' => 'Atendimento Interativo',
            'fabricaResponsavel' => $login_fabrica,
            'fabrica' => $login_fabrica,
            'clienteAdmin' => $cliente_admin,
            'estaAgendado' => false,
            'diasAberto' => 0,
            'diasUltimaInteracao' => 0,
            'receberInfoFabrica' => false,
            'origem' => 'Telefone',
            'consumidorRevenda' => 'C',
            'garantia' => false,
            'abreOs' => false,
            'atendimentoCallcenter' => false,
            'status' => 'Aberto'
        ),
	'AMBV' => array(
             'admin' => $login_admin,
             'atendente' => $login_admin,
             'categoria' => 'reclamacao_produto',
             'titulo' => 'Atendimento Interativo',
             'fabricaResponsavel' => $login_fabrica,
             'fabrica' => $login_fabrica,
             'clienteAdmin' => $cliente_admin,
             'estaAgendado' => false,
             'diasAberto' => 0,
             'diasUltimaInteracao' => 0,
             'receberInfoFabrica' => false,
             'origem' => 'Telefone',
             'consumidorRevenda' => 'C',
             'abreOs' => false,
             'atendimentoCallcenter' => false,
             'status' => 'Aberto'
         ),
    )
);

function validateToken($applicationKey, $token){
    $application = 'CALLCENTER';
    $url = 'http://api2.telecontrol.com.br/AccessControl/validation' . '/token/' . $token . '/application-key/' . $applicationKey;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível validar Token de acesso");
    }
    curl_close($ch);

    $validationData = validateResponseReturningArray($result);

    if($validationData['status'] == 'VALID'){
        return true;
    }else{
        return false;
    }
}

function generateToken($applicationKey) {
    $application = 'CALLCENTER';
    $url = 'http://api2.telecontrol.com.br/AccessControl/token';

    $fields = array(
        'applicationKey' => $applicationKey,
        'application' => 'CALLCENTER'
    );
    $json = json_encode($fields);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json"
    ));

    $result = curl_exec($ch);
    if(!$result){
        throw new Exception("Não foi possível gerar Token de acesso");
    }
    curl_close($ch);

    $tokenData = validateResponseReturningArray($result);

    return $tokenData['token'];
}

function getData($applicationKey, $token, $parameters){
    global $login_fabrica;
    global $accessEnv;

    $header = array(
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv

    );
    $url = 'http://api2.telecontrol.com.br/Callcenter/integrationCockpit'.$parameters;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível obter os dados");
    }
    curl_close($ch);

    return  validateResponseReturningArray($result);

}

function putData($dados, $applicationKey, $token, $accessEnv, $ticket = null){
    global $login_fabrica, $con;

    $sql = "SELECT dados FROM tbl_hd_chamado_cockpit WHERE fabrica = {$login_fabrica} AND hd_chamado_cockpit = {$ticket}";
    $res = pg_query($con, $sql);

    $dados2 = pg_fetch_result($res, 0, "dados");
    $dados2 = json_decode($dados2, true);

    if($dados2['centroDistribuidor'] == 'AMBV'){
        $dados2['codDefeito'] = $dados['defeitoReclamado'];
    }
        
    $application = 'CALLCENTER';

    if (is_null($ticket)) {
        $ticket = $dados["hd_chamado_cockpit"];
    }

    $url = 'http://api2.telecontrol.com.br/Callcenter/IntegrationCockpit/hdChamadoCockpit/'.$ticket;
    unset($dados['hdChamadoCockpit']);
    unset($dados['hd_chamado_cockpit']);

    $fields = array(
        'dados' => array_merge($dados2, $dados),
    );

    $json = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv
    ));

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível salvar os dados");
    }
    curl_close($ch);
    try{
        return validateResponseReturningArray($result);
    }catch(Exception $ex){
        echo $ex->getMessage();
    }

}

function setHdChamadoCockpit($dados, $applicationKey, $token, $accessEnv){
    global $fabrica;
    $application = 'CALLCENTER';

    $url = 'http://api2.telecontrol.com.br/Callcenter/IntegrationCockpit/hdChamadoCockpit/'.$dados['hd_chamado_cockpit'];
    unset($dados['hd_chamado_cockpit']);

    $json = json_encode($dados);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv
    ));

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível salvar os dados");
    }
    curl_close($ch);
    try{
        return validateResponseReturningArray($result);
    }catch(Exception $ex){
        echo $ex->getMessage();
        //TODO: logar mensagem
    }

}

function getOsMobileDataByStatusCode($applicationKey, $token, $statusCode){
    global $login_fabrica;
    global $accessEnv;
    global $login_fabrica;
    $header = array(
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv

    );
    $url = 'http://api2.telecontrol.com.br/Callcenter/OsIntegration/fabrica/'.$login_fabrica.'/statusCode/'.$statusCode;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível obter os dados");
    }
    curl_close($ch);

    return  validateResponseReturningArray($result);

}

function getOsMobileDataByStatusCodeAndOs($applicationKey, $token, $statusCode, $os){
    global $login_fabrica;
    global $accessEnv;
    global $login_fabrica;
    $header = array(
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv

    );
    $url = 'http://api2.telecontrol.com.br/Callcenter/OsIntegration/fabrica/'.$login_fabrica.'/statusCode/'.$statusCode .'/os/'.$os;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível obter os dados");
    }
    curl_close($ch);

    return  validateResponseReturningArray($result);

}

function validateResponseReturningArray($curlResult){
    $arrResult = json_decode($curlResult, true);

    if(array_key_exists('exception', $arrResult)){
        throw new Exception($arrResult['message']);
    } else if (array_key_exists('valid', $arrResult) && array_key_exists('message', $arrResult) && $arrResult["valid"] == false) {
        if (is_array($arrResult["message"])) {
            $message = implode("<br />", $arrResult["message"]);
        } else {
            $message = $arrResult["message"];
        }

        throw new Exception($message);
    }

    return $arrResult;
}

function validateData($arrDados, $applicationKey, $token, $accessEnv){
    global $camposAdicionais;
    global $login_fabrica;

    $cockpit = new \Posvenda\Cockpit($login_fabrica);

    $produto = $cockpit->getProdutoByRef($arrDados["modeloKof"], true);

    if ($produto["linha_nome"] != "REFRIGERADOR") {
        $garantia = "";
    } else {
        $garantia = $arrDados["garantia"];
    }

    $tipo_atendimento = $cockpit->getTipoAtendimentoKOF($arrDados["tipoOrdem"], $garantia);
    $unidade_negocio = $cockpit->getUnidadeNegocio($arrDados["centroDistribuidor"]);

    if ($tipo_atendimento['fora_garantia'] == 't') {
        $res_clienteAdmin = $cockpit->getClienteAdmin($login_fabrica."-KOF");
    } else {
        $res_clienteAdmin = $cockpit->getClienteAdmin($login_fabrica."-Alpunto");
    }

    $application = 'CALLCENTER';
    $url = 'http://api2.telecontrol.com.br/Callcenter/ValidateCallcenterData';

    if (isset($arrDados["codDefeito"])) {
        $arrDados["codDefeito"] = $arrDados["defeito"];
    }

    if($arrDados['centroDistribuidor'] == "AMBV"){
	$ctrDistribuidor = $arrDados['centroDistribuidor'];
	$arrDados['garantia'] = (bool)($arrDados['garantia'] == 'sim') ? true : false;
	}else{
	$ctrDistribuidor = "KOF";
	}

    $arrSend = deParaKOF($arrDados);
    $arrSend                 = array_merge($arrSend, getCamposAdicionaisKOF($ctrDistribuidor));
    $arrSend['cep']          = str_replace('-', '',$arrSend['cep']);
    $arrSend["clienteAdmin"] = $res_clienteAdmin['cliente_admin'];
    $arrSend["tipoOrdem"]    = $arrSend["tipoAtendimento"];

	if($arrDados['centroDistribuidor'] == "AMBV"){
        	$ctrDistribuidor = $arrDados['centroDistribuidor'];
	        $arrSend['garantia'] = ($arrDados['garantia'] == 'sim') ? true : false;
        }else{
	        $ctrDistribuidor = "KOF";
        }

    $json = json_encode($arrSend);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Access-Token: ".$token,
        "Access-Application-Key: " . $applicationKey,
        "Access-Env: " . $accessEnv
    ));

    $result = curl_exec($ch);

    if(!$result){
        throw new Exception("Não foi possível validar os dados");
    }

    curl_close($ch);
    $data = validateResponseReturningArray($result);

    return $data;
}

function getCamposAdicionaisKOF($centro = 'KOF'){
    global $login_fabrica;
    global $camposAdicionais;
    return $camposAdicionais[$login_fabrica][$centro];
}

function deParaKOF($arrDados){
    global $dePara;
    global $login_fabrica;

    if($arrDados['centroDistribuidor'] == "AMBV"){
        $empr = "AMBV";
    }else{
        $empr = "KOF";
    }    

    $deParaKOF = $dePara[$login_fabrica][$empr];
    $aux = array();

    foreach($deParaKOF as $kofName => $callcenterName){
        $aux[$callcenterName] = trim($arrDados[$kofName]);
    }

    if (array_key_exists("codDefeito", $arrDados)) {
        $aux["defeitoReclamado"] = $arrDados["codDefeito"];
    }

    return $aux;
}
 
