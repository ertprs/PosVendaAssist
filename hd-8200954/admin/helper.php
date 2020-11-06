<?php

/* Esse arquivo é carregado no arquivo autentica_admin.php */

/**
 * Classe para registrar helpers, e abstrai-los de parâmetros de conexão, execução a cada vez que forem executados.
 * @author Brayan
 * @version 1.0
 * @usage Ver final do arquivo, ao registrar instância de LoginHelper
 * As classes instanciadas aqui, estão na pasta admin/helpers/
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
 * Instância do Helper de Login
 * Apenas os parâmetros essenciais foram setados, 
 * Note que a propriedade admin pode ser sobrescrita pelo método hasPermission, o admin logado é apenas um default.
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
 * Apenas uma função para retornar os estados para criar formularios.
 * @example foreach($helper->estados as $k => $v) { .. }
 */
$helper->estados = function() {
	return array( "AC"=>"Acre","AL"=>"Alagoas","AM"=>"Amazonas",
				  "AP"=>"Amapá", "BA"=>"Bahia", "CE"=>"Ceará","DF"=>"Distrito Federal",
				  "ES"=>"Espírito Santo", "GO"=>"Goiás","MA"=>"Maranhão","MG"=>"Minas Gerais",
				  "MS"=>"Mato Grosso do Sul","MT"=>"Mato Grosso", "PA"=>"Pará","PB"=>"Paraíba",
				  "PE"=>"Pernambuco","PI"=>"Piauí","PR"=>"Paraná","RJ"=>"Rio de Janeiro",
				  "RN"=>"Rio Grande do Norte","RO"=>"Rondônia","RR"=>"Roraima",
				  "RS"=>"Rio Grande do Sul", "SC"=>"Santa Catarina","SE"=>"Sergipe",
				  "SP"=>"São Paulo","TO"=>"Tocantins"
	);
};
