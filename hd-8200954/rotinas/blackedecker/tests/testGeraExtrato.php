<?php

define('PHPUNIT', TRUE);

require_once '../gera-extrato.php';

class GeraExtratoTest extends PHPUnit_Framework_TestCase {

    /** 
     * @var object GeraExtratoBlack
     */
    private $obj;

    function __construct() {

        $config = array(
            'fabrica'   => 'blackedecker',
            'dest'      => 'brayan@telecontrol.com.br'
        );

        $this->obj = new GeraExtratoBlack($config);

    }

    /**
     * Irá falhar, pois a classe é abstrata.
     */
    public function testInstanceGeraExtrato() {

        //$this->assertInstanceOf( 'GeraExtrato', new GeraExtrato() );

    }

    public function testInstanceGeraExtratoBlack() {

        $this->assertInstanceOf('GeraExtratoBlack', $this->obj);

    }

    public function testVerificaDataFinalValida() {

        $this->assertTrue($this->obj->setDataFinal('2012-03-01'));

    }

    public function testVerificaDataFinalInvalida() {

        $this->assertFalse($this->obj->setDataFinal('2013-05-19'));

    }

    public function testLogPerl() {

        pg_query($this->obj->getCon(), "BEGIN TRANSACTION");

            $this->assertTrue($this->obj->logPerl('teste'));

            $this->assertTrue($this->obj->logPerl('fim teste', TRUE));

        pg_query($this->obj->getCon(), "ROLLBACK");

    }

    public function testLogPerlMsgNula () {

        pg_query($this->obj->getCon(), "BEGIN TRANSACTION");

            $this->assertFalse($this->obj->logPerl(''));

        pg_query($this->obj->getCon(), "ROLLBACK");

    }

    public function testRetornaPosto () {

        $this->assertNotEmpty($this->obj->getPostos());

    }

    /**
     * @depends testRetornaPosto
     */
    public function testSetaPostosParaExtrato() {

        //teste com inteiro
        $this->assertTrue($this->obj->setPosto( 6359 ) );

        // teste com array
        $this->assertTrue($this->obj->setPosto( array(6359, 877) ) );

        // Teste com função da black para pegar os postos que vao gerar extrato
        $this->assertTrue($this->obj->setPosto( $this->obj->getPostos() ) );

    }

    /**
     * @depends testLogPerl
     * @depends testRetornaPosto
     * @depends testSetaPostosParaExtrato
     */
    public function testGeraExtratoIntegrado() {

        $this->obj->setPosto( $this->obj->getPostos() );
        
        pg_query($this->obj->getCon(), "BEGIN TRANSACTION");

            $this->assertTrue($this->obj->logPerl('Iniciando processamento..'));

            $this->assertTrue($this->obj->gerar());

            $this->assertTrue($this->obj->logPerl('Fazendo calculo de extrato..'));

            $this->assertTrue($this->obj->calculaExtrato());

            $this->assertTrue($this->obj->logPerl('Finalizando processamento..', TRUE));

        pg_query($this->obj->getCon(), "ROLLBACK"); 

    }

}