<?php 

namespace PosVenda\Fabricas\_143;

class PedidoWackerNeuson()
{

	function verificaEstoquePeca($referencia)
	{
		$request = 'http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_estruturaprodutos?wsdl', array('trace' => 1, 'exception' => 1);

		$arguments = array(
			"user"       => "Telecontrol",
			"password"   => "Telecontrol",
			"encryption" => "0",
			"parameters" => array(
				"codEmp" => 1,
				"sitPro" => "A"
			)
    	);

    	$soapResult = $request->__soapCall('ConsultaEstoque', $arguments);
	}
}

