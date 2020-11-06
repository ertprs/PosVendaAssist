<?php

	echo "teste ";


	if(isset($_POST['grava_hd_chamado'])){

		$num_os = (int)$_POST["os"];

		echo "num_os $num_os";

		$sql_os = "select * from tbl_os where os_";




		/*
			#-------------- INSERT ---------------
			$sql = "INSERT INTO tbl_hd_chamado (
						admin                 ,
						data                  ,
						status                ,
						atendente             ,
						fabrica_responsavel   ,
						titulo                ,
						categoria             ,
						fabrica
					)values(
						$login_admin            ,
						current_timestamp       ,
						$xstatus_interacao      ,
						$login_admin            ,
						$login_fabrica          ,
						'$titulo'               ,
						'$tipo_contato'         ,
						$login_fabrica
				)";

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);

			$fale_conosco = json_encode(array("fale_conosco" => "true"));

			$sql = "INSERT INTO tbl_hd_chamado_extra(
								hd_chamado           ,
								produto				 ,	
								reclamado            ,
								nome                 ,
								cpf					 ,
								endereco             ,
								numero               ,
								complemento          ,
								bairro               ,
								cep                  ,
								fone                 ,
								email                ,
								cidade               ,
								array_campos_adicionais
							)values(
							$hd_chamado              ,
							$aux_produto             ,
							'$aux_msg'               ,
							upper('$aux_nome')       ,
							upper('$aux_cpf')		 ,
							upper('$aux_endereco')   ,
							upper('$aux_numero')     ,
							upper('$aux_complemento'),
							upper('$aux_bairro')     ,
							upper('$aux_cep')        ,
							upper('$aux_telefone')   ,
							upper('$aux_email')      ,
							'$cidade'                ,
							'$fale_conosco'
							) ";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

		*/




	}

?>