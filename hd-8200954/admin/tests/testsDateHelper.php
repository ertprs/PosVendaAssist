<?php
require_once '../helpers/DateHelper.php';
class testsDateHelper extends PHPUnit_Framework_TestCase {

	public function testValidaDataCorreta() {

		$this->assertTrue(DateHelper::validate('01/01/2012'));
		$this->assertTrue(DateHelper::validate( array('01/01/2012', '07/08/2012') ));

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testValidaDataIncorreta() {

		$this->assertTrue(DateHelper::validate('01/31/2012'));
		$this->assertTrue(DateHelper::validate( array('21/11/2112', '00/08/2012') ));

	}

	public function testConverteData() {

		$this->assertEquals('2012/01/01', DateHelper::converte('01/01/2012'));
		$this->assertEquals('2012-01-01', DateHelper::converte('01-01-2012'));

		$this->assertEquals('01/01/2012', DateHelper::converte('2012/01/01'));
		$this->assertEquals('01-01-2012', DateHelper::converte('2012-01-01'));

	}

	/**
	 * @depends testConverteData
	 */
	public function testValidaPeriodos() {

		// Default 1 mes
		$this->assertTrue(DateHelper::validaPeriodo('01/01/2012', '01/02/2012'));

		$this->assertTrue(DateHelper::validaPeriodo('01/01/2012', '01/03/2012', '2 months', 'dois meses'));


	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testValidaPeriodoIncorreto() {

		$this->assertTrue(DateHelper::validaPeriodo('01/01/2012', '02/02/2012'));

	}
	
}
