<?php 

class PedidoWackerNeuson
{

	private $_reference;

	function __construct($referencia) 
	{
		$this->_reference = $referencia;
	}

	function verificaEstoquePeca()
	{
		$request = new SoapClient('http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_estruturaprodutos?wsdl', array('trace' => 1, 'exception' => 1));

		$arguments = array(
			'user'       => 'Telecontrol',
			'password'   => 'Telecontrol',
			'encryption' => '0',
			'parameters' => array(
				'codEmp' => 1,
				'produto' => array(
					'codPro' => $this->_reference
				)
			),
    	);
		try{
			$soapResult = $request->__soapCall('ConsultaEstoque', $arguments);
			return $soapResult;
		}catch (Exception $e) {
			return $erro['errro'] = $e->getMessage();
		}
	}
}

