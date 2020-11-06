<?php

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_admin.php";

	include "../includes2/xml2array.php";

	$hd_chamado = $_GET['hd_chamado'];
	$coleta = $_GET['coleta'];
	$valor_declarado = $_GET['valor_declarado'];

	$sql = "SELECT 	tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco,
					tbl_hd_chamado_extra.numero,
					tbl_hd_chamado_extra.bairro,
					tbl_hd_chamado_extra.complemento,
					tbl_hd_chamado_extra.cep,
					tbl_hd_chamado_extra.fone,
					tbl_hd_chamado_extra.email,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado,
					tbl_produto.descricao AS produto
			FROM tbl_hd_chamado_extra
			JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
			JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
			$nome 			= pg_fetch_result($res, 0, 'nome');
			$endereco 		= pg_fetch_result($res, 0, 'endereco');
			$numero 		= pg_fetch_result($res, 0, 'numero');
			$bairro 		= pg_fetch_result($res, 0, 'bairro');
			$complemento 	= pg_fetch_result($res, 0, 'complemento');
			$cep 			= pg_fetch_result($res, 0, 'cep');
			$fone 			= pg_fetch_result($res, 0, 'fone');
			$email 			= pg_fetch_result($res, 0, 'email');
			$cidade 		= pg_fetch_result($res, 0, 'cidade');
			$estado 		= pg_fetch_result($res, 0, 'estado');
			$produto 		= pg_fetch_result($res, 0, 'produto');

			list($ddd,$fone) = explode(")",$fone);
			$ddd = str_replace("(", "", $ddd);
			$fone = trim($fone);

$corpo = array("logisticareversa" => 
		array(
				"reg"					=> "",
				"versao_arquivo"		=> "4.0",							
				"data_processamento"	=> "",
				"agendamento"			=> "",
				"codigo_administrativo"	=> "01234567",
				"contrato"				=> "12034673",
				"codigo_servico"		=> "",
				"cartao"				=> "12034673",
				"destinatario"			=> array(
													"nome"			=> "CBI Industria e Comercio Ltda",
													"logradouro"	=> "Rua E",
													"numero"		=> "S/N",
													"complemento"	=> "Q3L1",
													"bairro"		=> "Distrito Industrial",
													"referencia"	=> "",
													"cidade"		=> "Queimados",
													"uf"			=> "RJ",
													"cep"			=> "26373-28",
													"ddd"			=> "21",
													"telefone"		=> "3670-1400",
													"email"			=> ""
											),
				"coletas_solicitadas"	=> array(
													"coleta"		=> array(
																		"tipo"	=> $coleta,
																		"numero" => "",
																		"id_cliente" => "",
																		"ag" => "",
																		"cartao" => "",
																		"valor_declarado" => $valor_declarado,
																		"servico_adicional" => "",
																		"descricao" => "",
																		"ar" => "",
																		"cklist" => "",
																		"remetente" => array(
																						"nome"			=> $nome,
																						"logradouro"	=> $endereco,
																						"numero"		=> $numero,
																						"complemento"	=> $complemento,
																						"bairro"		=> $bairro,		
																						"cidade"		=> $cidade,
																						"uf"			=> $estado,
																						"cep"			=> $cep,
																						"referencia"	=> "",
																						"ddd"			=> $ddd,
																						"telefone"		=> $fone,
																						"email"			=> $email
																							),
																		"obj_col"	=> array( "obj" => array(
																								"item" => "1",
																								"desc" => $produto,
																								"entrega" => "",
																								"num" => "",
																								"id" =>$hd_chamado
																								)
																							),
																		"produto"	=> array(
																							"desc" => array(
																										"codigo" => "",
																										"tipo"	 => "",
																										"qtd"	 => ""
																								)
																							)
																		)
																		
												)

			)
	);

			
		$cabecalho =  "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
		$xml = $cabecalho.array2xml($corpo,0);
		$x = fopen("xls/$hd_chamado.xml","w");
		fwrite($x,$xml);
		fclose($x);	
		//gera o zip
		exec("zip -quomT xls/$hd_chamado.zip xls/$hd_chamado.xml > /dev/null");
			exec("rm xls/$hd_chamado.xml");
			echo "OK";


		
		
		
	}
	
	
