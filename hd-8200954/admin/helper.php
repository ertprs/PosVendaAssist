<?php

/* Esse arquivo � carregado no arquivo autentica_admin.php */

/**
 * Classe para registrar helpers, e abstrai-los de par�metros de conex�o, execu��o a cada vez que forem executados.
 * @author Brayan
 * @version 1.0
 * @usage Ver final do arquivo, ao registrar inst�ncia de LoginHelper
 * As classes instanciadas aqui, est�o na pasta admin/helpers/
 */
class IoC {

   	protected $registry = array();
 
   	public function __set($name, $resolver) {
    	$this->registry[$name] = $resolver;
   	}
 
   	public function __get($name) {
    	return $this->registry[$name]();
   	}

}

/* Cria Objeto para utilizar no sistema */
$helper = new IoC;

/**
 * Inst�ncia do Helper de Login
 * Apenas os par�metros essenciais foram setados, 
 * Note que a propriedade admin pode ser sobrescrita pelo m�todo hasPermission, o admin logado � apenas um default.
 */
$helper->login = function() {

	global $con, $login_fabrica, $login_admin;

	$instance = new LoginHelper;
	$instance->setDB($con);
	$instance->setFabrica($login_fabrica);
	$instance->setAdmin($login_admin);

	return $instance;

};

$helper->posto = function () {

	global $con, $login_fabrica;
	$instance = new PostoHelper;
	$instance->setDB($con);
	$instance->setFabrica($login_fabrica);

	return $instance;

};

$helper->crud = function () {

	global $con, $login_fabrica;
	$instance = new CRUDHelper;
	$instance->setDB($con);
	$instance->setFabrica($login_fabrica);

	return $instance;

};

$helper->file = function () {

	return new FileHelper;

};

/**
 * Apenas uma fun��o para retornar os estados para criar formularios.
 * @example foreach($helper->estados as $k => $v) { .. }
 */
$helper->estados = function() {
	return array( "AC"=>"Acre","AL"=>"Alagoas","AM"=>"Amazonas",
				  "AP"=>"Amap�", "BA"=>"Bahia", "CE"=>"Cear�","DF"=>"Distrito Federal",
				  "ES"=>"Esp�rito Santo", "GO"=>"Goi�s","MA"=>"Maranh�o","MG"=>"Minas Gerais",
				  "MS"=>"Mato Grosso do Sul","MT"=>"Mato Grosso", "PA"=>"Par�","PB"=>"Para�ba",
				  "PE"=>"Pernambuco","PI"=>"Piau�","PR"=>"Paran�","RJ"=>"Rio de Janeiro",
				  "RN"=>"Rio Grande do Norte","RO"=>"Rond�nia","RR"=>"Roraima",
				  "RS"=>"Rio Grande do Sul", "SC"=>"Santa Catarina","SE"=>"Sergipe",
				  "SP"=>"S�o Paulo","TO"=>"Tocantins"
	);
};
