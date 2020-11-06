<?php

	include '../../dbconfig.php';
	include '../../includes/dbconnect-inc.php';
	require( '../../class_resize.php' );
	include('../../admin/funcoes.php');

	if(isset($_POST['grava_hd_chamado'])){

		$num_os = (int)$_POST["os"];

		$sql_os = "select 
		produto, 
		fabrica, 
		data_abertura, 
		data_fechamento, 
		defeito_reclamado_descricao, 
		consumidor_cpf, 
		consumidor_nome,
		consumidor_endereco,
		consumidor_numero,
		consumidor_cep,
		consumidor_bairro,
		consumidor_complemento,
		consumidor_email,
		consumidor_fone,
		consumidor_cidade
        from tbl_os where os = $num_os ";
		$res_os = pg_exec($con,$sql_os);
			if(pg_num_rows($res_os) > 0){
				for($i=0; $i<pg_num_rows($res_os); $i++){
					$produto 						= pg_fetch_result($res_os, $i, 'produto');
					$fabrica 						= pg_fetch_result($res_os, $i, 'fabrica');
					$data_abertura 					= pg_fetch_result($res_os, $i, 'data_abertura');
					$data_fechamento 				= pg_fetch_result($res_os, $i, 'data_fechamento');
					$defeito_reclamado_descricao 	= pg_fetch_result($res_os, $i, 'defeito_reclamado_descricao');
					$consumidor_cpf 				= pg_fetch_result($res_os, $i, 'consumidor_cpf');
					$consumidor_nome 				= pg_fetch_result($res_os, $i, 'consumidor_nome');
					$consumidor_endereco 			= pg_fetch_result($res_os, $i, 'consumidor_endereco');
					$consumidor_numero 				= pg_fetch_result($res_os, $i, 'consumidor_numero');
					$consumidor_cep 				= pg_fetch_result($res_os, $i, 'consumidor_cep');
					$consumidor_bairro 				= pg_fetch_result($res_os, $i, 'consumidor_bairro');
					$consumidor_complemento 		= pg_fetch_result($res_os, $i, 'consumidor_complemento');
					$consumidor_email 				= pg_fetch_result($res_os, $i, 'consumidor_email');
					$consumidor_fone 				= pg_fetch_result($res_os, $i, 'consumidor_fone');
					$consumidor_cidade 				= pg_fetch_result($res_os, $i, 'consumidor_cidade');

					$cidade = retira_acentos($consumidor_cidade);

					$sql_cidade = "select * from tbl_cidade where nome ilike '$cidade' ";
					$res_cidade = pg_query($con, $sql_cidade);
						if(pg_num_rows($res_cidade)){
							$cod_cidade = pg_fetch_result($res_cidade, 0, 'cidade');
						}
				}
			}

			$login_admin 		= 7145;
			$login_fabrica		= 35;
			$titulo            	= 'Atendimento interativo';
			$xstatus_interacao 	= "'Aberto'";
			$tipo_contato		= "reclamacao_produto";
			$defeito_reclamado_descricao = "Atendimento aberto com o origem no site CADENCE - CONSULTA OS, referente a O.S $num_os - ". $defeito_reclamado_descricao;
		
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

			if(strlen(trim($con))>0){
				$erro .= "Falha ao gravar hd_chamado";
			}

			$sql = "INSERT INTO tbl_hd_chamado_extra(
								os 					 ,
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
								cidade								
							)values(
							$num_os 				 ,
							$hd_chamado              ,
							$produto 	             ,
							'$defeito_reclamado_descricao',
							upper('$consumidor_nome')       ,
							upper('$consumidor_cpf')		 ,
							upper('$consumidor_endereco')   ,
							upper('$consumidor_numero')     ,
							upper('$consumidor_complemento'),
							upper('$consumidor_bairro')     ,
							upper('$consumidor_cep')        ,
							upper('$consumidor_fone')   ,
							upper('$consumidor_email')      ,
							$cod_cidade
							) ";
			$res = pg_exec($con,$sql);

			$msg_erro .= pg_errormessage($con);

			if(strlen(trim($con))>0){
				$erro .= "Falha ao gravar hd_chamado_extra";
			}
		
			if(strlen(trim($erro))==0){
				echo "<div class='alert alert-success' role='alert'>Olá, acabamos de encaminhar sua solicitação(Atendimento $hd_chamado) para nossa equipe de atendimento. <br>Faremos contato com a Assistência Técnica onde está o seu produto e retornaremos em breve com uma solução.</div>";
			}else{
				echo "<div class='alert alert-danger' role='alert'>Ocorreu um erro ao grava contato. </div>";
			}

			$mensagem = "Foi aberto um chamado no Call-center de número: $hd_chamado";

			$headers = 'From: helpdesk@telecontrol.com.br' . "\r\n" .
		    'Reply-To: no-reply@telecontrol.com.br' . "\r\n" ;		    

			mail("sac@cadence.com.br", "Chamado Aberto no Call-Center", $mensagem, $headers);
			

	}

?>
