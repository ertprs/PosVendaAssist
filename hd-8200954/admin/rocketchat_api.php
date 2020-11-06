<?php

include 'rocketchat_tokens.php';
include_once 'funcoes.php';

$rocketConfig = getRocketConfig();

function postUsers($fabrica, $email, $username, $name, $password, $role, $customFields =array()){
	global $rocketConfig;


	$username = prepareUsername($username);


	$params = array(		
		"email" => $email,
		"username" => $username,
		"name" => utf8_encode($name),
		"password"=> $password,
		"roles"=> array($role),
		"customFields"=> $customFields
		);

	$params['token'] = $rocketConfig[$fabrica]['token'];

	$curl = curl_init();

	curl_setopt_array($curl, array(		
		CURLOPT_URL => "http://api2.telecontrol.com.br/rocketchat/users",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode($params),
		CURLOPT_HTTPHEADER => array(			
			"content-type: application/json",
			),
		));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		throw new Exception("Ocorreu um erro ao tentar incluir o usuário no Chat, por favor tente novamente: ".$err, 1);		
	} else {
		return json_decode($response, true);
	}
}

function iniciaAtendimento($fabrica, $posto_chat_id, $atendente_chat_id){
	global $rocketConfig;

	$params = array(		
		"posto" => $posto_chat_id,
		"atendente" => $atendente_chat_id
		);

	$params['token'] = $rocketConfig[$fabrica]['token'];


	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://api2.telecontrol.com.br/rocketchat/iniciaAtendimento",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode($params),
	  CURLOPT_HTTPHEADER => array(
	    "content-type: application/json",
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		throw new Exception("Ocorreu um erro ao tentar iniciar atendimento, por favor tente novamente: ".$err, 1);		
	} else {
		return json_decode($response, true);
	}

}


function supervisionarAtendimento($fabrica, $supervisor, $chatId){
	global $rocketConfig;


	$username = prepareUsername($username);


	$params = array(		
		"supervisor" => $supervisor,
		"chatId" => $chatId		
	);

	$params['token'] = $rocketConfig[$fabrica]['token'];

	$curl = curl_init();

	curl_setopt_array($curl, array(		
		CURLOPT_URL => "http://api2.telecontrol.com.br/rocketchat/supervisionarAtendimento",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode($params),
		CURLOPT_HTTPHEADER => array(			
			"content-type: application/json",
			),
		));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		throw new Exception("Ocorreu um erro ao tentar incluir o usuário no Chat, por favor tente novamente: ".$err, 1);		
	} else {
		return json_decode($response, true);
	}
}

function updateUser($userId, $active, $fabrica){
	global $rocketConfig;

	$token = $rocketConfig[$fabrica]['token'];

	if($active == true){
		$active = "true";
	}elseif ($active == false) {
		$active = "false";
	}

	$data = array(
		"token" => $token,
		"userId" => $userId,
		"active" => $active
	);

	
	$curl = curl_init();

	curl_setopt_array($curl, array(		
		CURLOPT_URL => "http://api2.telecontrol.com.br/rocketchat/users",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => json_encode($data),
		CURLOPT_HTTPHEADER => array(		
			"content-type: application/json",			
			),
		));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		throw new Exception("Ocorreu um erro ao tentar atualizar o usuário no Chat, por favor tente novamente: ".$err, 1);		
	} else {
		return json_decode($response, true);
	}
}

function updateUserRoles($userId, $roles, $fabrica){
	global $rocketConfig;

	$token = $rocketConfig[$fabrica]['token'];

	$data = array(
		"token" => $token,
		"userId" => $userId,
		"roles" => $roles
		);

	$curl = curl_init();

	curl_setopt_array($curl, array(		
		CURLOPT_URL => "http://api2.telecontrol.com.br/rocketchat/users",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => json_encode($data),
		CURLOPT_HTTPHEADER => array(		
			"content-type: application/json",			
			),
		));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		throw new Exception("Ocorreu um erro ao tentar atualizar o usuário no Chat, por favor tente novamente: ".$err, 1);		
	} else {
		return json_decode($response, true);
	}
}

function getUserInfo($userId,$fabrica){
	global $rocketConfig;

	$token = $rocketConfig[$fabrica]['token'];

	$curl = curl_init();

	

	curl_setopt_array($curl, array(
		CURLOPT_URL => "http://api2.telecontrol.com.br/rocketchat/users/userId/$userId/token/$token",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET"				
		));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		throw new Exception("Ocorreu um erro ao tentar incluir o usuário no Chat, por favor tente novamente: ".$err, 1);		
	} else {
		return json_decode($response, true);
	}
}


function prepareUsername($username){
	$username = retira_acentos($username);
	$username = str_replace(array("(",")"), "", $username);	
	$username = str_replace(" ", "", $username);	
	
	return $username;
}