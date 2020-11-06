<?php

require '../helpers/Parametros.php';
require '../helpers/LoginHelper.php';
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
class testLoginHelper extends PHPUnit_Framework_TestCase {

	public function __construct() {

		global $con;

		$this->obj = new LoginHelper();
		$this->obj->setDB($con);
		$this->obj->setFabrica(10);
		$this->obj->setAdmin(3011);

	}

	public function testHasPermission() {

		$this->assertTrue( $this->obj->hasPermission('cadastro') );

	}
	public function testNaoTemPermissao() {

		$this->obj->setFabrica(1);
		$this->assertFalse( $this->obj->hasPermission('cadastro') );

	}
	public function testGetInfo() {

		$data = $this->obj->getInfo(array('nome_completo'));

		$this->assertEquals('Brayan Laurindo Rastelli', $data['nome_completo']);

	}

}
