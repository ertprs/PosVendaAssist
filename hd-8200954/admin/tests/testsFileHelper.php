<?php
require_once '../helpers/FileHelper.php';
class testsFileHelper extends PHPUnit_Framework_TestCase {

	protected function setUp()
	{
	    parent::setUp();

	    $_FILES['fotos'] = array(
		    'name'      =>  array ('foo.jpg', 'bar.png'),
		    'tmp_name'  =>  array ('/tmp/php42up23', '/tmp/shaus'),
		    'type'      =>  array ('image/jpeg', 'image/png'),
		    'size'      =>  array ('50000', '80000'),
		    'error'     =>  0	
	    );
	}

	public function __construct() {

		$this->obj = new FileHelper();

	}

	public function testValidaMimeType() {

		$this->assertTrue($this->obj->validate($_FILES['fotos'],'image') );

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testNaoValidaMimeType() {

		$this->assertTrue($this->obj->validate($_FILES['fotos'],'doc') );

	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testValidaTamanhoMaximo() {

		$_FILES['fotos']['size'][1] = 2000000;
		$this->assertTrue($this->obj->validate($_FILES['fotos'], 'image'));

	}

	public function testModificaMimeTypesPermitido () {

		$_FILES['fotos']['type'][0] = 'image/bmp';
		$this->assertTrue($this->obj->validate($_FILES['fotos'], 'image', array('image/png', 'image/bmp')));

	}

}
