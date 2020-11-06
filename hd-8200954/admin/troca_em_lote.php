<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

$btn_troca = $_POST['osacao'];

if ($btn_troca == "trocar" && strlen($msg_erro) == 0) {
	$msg_erro = "";

	$array_sua_os = array();
	$oss                     = $_POST["oss"];
	$coleta_postagem 		 = 'null';
	$xdata_postagem   		 = 'null';
	$troca_garantia_mao_obra = $_POST["troca_garantia_mao_obra"];
	$troca_garantia_mao_obra = str_replace(",",".",$troca_garantia_mao_obra);
	$gerar_pedido			 = $_POST['gerar_pedido'];
	$fabrica_distribuidor	 = $_POST["fabrica_distribuidor"];
	$fabrica_distribuidor = ($fabrica_distribuidor == 'distribuidor') ? '4311' : 'null';
	$ri 					 = (empty($_POST['ri'])) ? "null" : "'".$_POST['ri']."'";
	$troca_garantia_produto  = $_POST["troca_garantia_produto"];
	$causa_troca 			 = $_POST["causa_troca"];
	$observacao_pedido 		 = $_POST["observacao_pedido"];
	$setor 					 = $_POST["setor"];
	$modalidade_transporte 	 = $_POST["modalidade_transporte"];
	$gerar_pedido 			 = "'t'";
	$situacao_atendimento 	 = 'null';

	$oss = str_replace("\\", "", $oss);
	$oss = json_decode($oss,true);

	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con,$sql);
	foreach ($oss as $os) {
		$peca = "";

		$sql = "SELECT os FROM tbl_os_extra WHERE os = $os AND extrato IS NOT NULL";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res)) {

			$msg_erro = "OS já entrou em extrato e não pode ser trocada. ";

		}

		$sql = "SELECT produto, sua_os, posto, revenda_nome FROM tbl_os WHERE os = $os;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$produto = pg_fetch_result($res, 0, 'produto');
		$sua_os  = pg_fetch_result($res, 0, 'sua_os');
		$posto   = pg_fetch_result($res, 0, 'posto');
		$revenda_nome   = pg_fetch_result($res, 0, 'revenda_nome');

		if ($troca_garantia_produto != "-1" && $troca_garantia_produto != "-2") {

			$sql = "SELECT *
					  FROM tbl_produto
					  JOIN tbl_familia USING(familia)
					 WHERE produto = '$produto'
					   AND fabrica = $login_fabrica;";

			$resProd   = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (pg_num_rows($resProd) == 0) {
				$msg_erro .= "Produto informado não encontrado<br />";
			} else {
				$troca_produto    = pg_fetch_result($resProd, 0, 'produto');
				$troca_ipi        = pg_fetch_result($resProd, 0, 'ipi');
				$troca_referencia = pg_fetch_result($resProd, 0, 'referencia');
				$troca_descricao  = pg_fetch_result($resProd, 0, 'descricao');
				$troca_familia    = pg_fetch_result($resProd, 0, 'familia');
				$troca_linha      = pg_fetch_result($resProd, 0, 'linha');

				$troca_descricao = substr($troca_descricao,0,50);
			}

			if (strlen($msg_erro) == 0) {

				$sql = "SELECT *
						  FROM tbl_peca
						 WHERE referencia = '$troca_referencia'
						   AND fabrica    = $login_fabrica";
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);

				if (pg_num_rows($res) == 0) {

					if (strlen($troca_ipi) == 0) $troca_ipi = 10;

					$sql = "INSERT INTO tbl_peca (
								fabrica,
								referencia,
								descricao,
								ipi,
								origem,
								produto_acabado
							) VALUES (
								$login_fabrica,
								'$troca_referencia',
								'$troca_descricao',
								$troca_ipi,
								'NAC',
								't'
							)";

					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT CURRVAL ('seq_peca')";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$peca = pg_fetch_result($res,0,0);

					$sql = "INSERT INTO tbl_lista_basica (
								fabrica,
								produto,
								peca,
								qtde
							) VALUES (
								$login_fabrica,
								$produto,
								$peca,
								1
							);";

					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

				} else {

					$peca = pg_fetch_result($res, 0, 'peca');

				}

			}

			$sql_peca = "SELECT tbl_tabela_item.preco
						   FROM tbl_tabela_item
						   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
						   JOIN tbl_posto_linha ON tbl_tabela.tabela      = tbl_posto_linha.tabela
						  WHERE tbl_posto_linha.posto = $posto
							AND tbl_tabela_item.peca  = $peca
							AND tbl_posto_linha.linha = $troca_linha";
			$res = pg_query($con,$sql_peca);

			if (pg_num_rows($res) == 0) {
				$sql_peca2 = "SELECT tbl_tabela_item.preco
						   FROM tbl_tabela_item
						   JOIN tbl_tabela      ON tbl_tabela_item.tabela = tbl_tabela.tabela
						   WHERE tbl_tabela_item.peca  = $peca
						   AND   tbl_tabela.fabrica = $login_fabrica";

				$res2 = pg_query($con,$sql_peca2);
				if (pg_num_rows($res2) == 0 ) {
					$msg_erro = "O produto $troca_referencia não tem preço na tabela de preço. Cadastre o preço para poder dar continuidade na troca.";
				}
			}

		}

		$sql = "SELECT credenciamento
				FROM  tbl_posto_fabrica
				JOIN  tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto
				WHERE tbl_os.fabrica            = $login_fabrica
				AND   tbl_os.os                 = $os
				AND   tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';";
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res)>0){
			$msg_erro .= "Este posto está DESCREDENCIADO. Não é possível efetuar a troca do produto.\n";
		}

		$sql = " SELECT os FROM tbl_os WHERE os = $os and fabrica = $login_fabrica and data_fechamento IS NOT NULL and finalizada IS NOT NULL ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$os_fechada = pg_fetch_result($res,0,0);
		}

		$sql = "UPDATE tbl_os SET data_fechamento = NULL,finalizada=null WHERE os = $os AND fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql = "SELECT os_troca,peca,os FROM tbl_os_troca WHERE os = $os AND pedido IS NULL ";
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res)>0){
			$troca_efetuada =  pg_fetch_result($res,0,os_troca);
			$troca_os       =  pg_fetch_result($res,0,os);
			$troca_peca     =  pg_fetch_result($res,0,peca);

			if (strlen($troca_peca) == 0) {
				$peca_para_troca = '4836000';

				$sql = "UPDATE tbl_os_produto
						   SET os = $peca_para_troca
						  FROM tbl_os_item
						 WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
						   AND os = $troca_os
						   AND peca IN (
								SELECT tbl_peca.peca
								  FROM tbl_peca
								  JOIN tbl_os_item    USING (peca)
								  JOIN tbl_os_produto USING (os_produto)
								  JOIN tbl_os_extra   USING (os)
								  JOIN tbl_os_troca   ON    tbl_os_produto.os = tbl_os_troca.os
								 WHERE tbl_os_troca.os          =  $os
								   AND tbl_peca.produto_acabado IS TRUE
								)";

				$res = pg_query ($con,$sql);
			}

			$sql = "DELETE FROM tbl_os_troca WHERE os_troca = $troca_efetuada";
			$sql = "UPDATE tbl_os_troca SET os = 4836000 WHERE os_troca = $troca_efetuada";
			$res = pg_query ($con,$sql);

			if(strlen($troca_peca) > 0) {
				$sql = "UPDATE tbl_os_produto set os = 4836000 FROM tbl_os_item WHERE tbl_os_item.os_produto=tbl_os_produto.os_produto AND os=$troca_os and peca = $troca_peca";
				$res = pg_query ($con,$sql);
			}

		}

	// adicionado por Fabio - Altera o status para liberado da Assis. Tec. da Fábrica caso tenha intervencao.
		$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65,72,73,87,88,116,117,127) ORDER BY data DESC LIMIT 1";
		$res = pg_query($con,$sql);
		$qtdex = pg_num_rows($res);
		if ($qtdex>0){
			$statuss=pg_fetch_result($res,0,status_os);
			$status_arr = array(62,65,72,87,116,127);
			if (in_array($statuss,$status_arr)){

				$proximo_status = "64";

				if ( $statuss == "72"){
					$proximo_status = "73";
				}
				if ( $statuss == "87"){
					$proximo_status = "88";
				}
				if ( $statuss == "116"){
					$proximo_status = "117";
				}

				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao,admin)
						VALUES ($os,$proximo_status,current_timestamp,'OS Liberada',$login_admin)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}
		}


		switch ($login_fabrica) {
			case 81:
				$id_servico_realizado        = 7458;
				$id_servico_realizado_ajuste = 10655;
				$id_solucao_os               = 2920;
				$defeito_constatado          = 15529;
				break;
		}

		if (strlen($id_servico_realizado_ajuste)>0 AND strlen($id_servico_realizado)>0){

			$sql =  "UPDATE tbl_os_item
					SET servico_realizado = $id_servico_realizado_ajuste
					WHERE os_item IN (
						SELECT os_item
						FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
						WHERE tbl_os.os       = $os
						AND tbl_os.fabrica    = $login_fabrica
						AND tbl_os_item.servico_realizado = $id_servico_realizado
						AND tbl_os_item.pedido IS NULL
					)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($defeito_constatado)>0 AND strlen($id_solucao_os)>0){
			$sql = "UPDATE tbl_os
					SET solucao_os         = $id_solucao_os,
						defeito_constatado = $defeito_constatado
					WHERE os       = $os
					AND fabrica    = $login_fabrica
					AND solucao_os IS NULL
					AND defeito_constatado IS NULL";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		//HD 211825: Opção de trocar através da revenda um produto
		if ($troca_garantia_produto == -2) {
			$sql = "UPDATE tbl_os SET
					troca_garantia          = 't',
					ressarcimento           = 'f',
					troca_garantia_admin    = $login_admin,
					data_fechamento         = CURRENT_DATE,
					finalizada              = CURRENT_TIMESTAMP
					WHERE os = $os AND fabrica = $login_fabrica";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "UPDATE tbl_os_troca SET
					troca_revenda			= 't'
					WHERE os = $os AND fabric = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "
			UPDATE tbl_os_extra SET
			obs_nf = '$observacao_pedido'
			WHERE os = $os";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "INSERT INTO tbl_os_troca (
						setor                 ,
						situacao_atendimento  ,
						os                    ,
						admin                 ,
						observacao            ,
						causa_troca           ,
						gerar_pedido          ,
						ressarcimento         ,
						troca_revenda		  ,
						envio_consumidor      ,
						modalidade_transporte ,
						ri                    ,
						fabric                ,
						distribuidor          ,
						coleta_postagem       ,
						data_postagem         ,
						obs_causa
					)VALUES(
						'$setor'                ,
						$situacao_atendimento   ,
						$os                     ,
						$login_admin            ,
						'$observacao_pedido'    ,
						$causa_troca            ,
						$gerar_pedido           ,
						FALSE                   ,
						TRUE					,
						'$envio_consumidor'       ,
						'$modalidade_transporte',
						'$ri'                   ,
						$login_fabrica          ,
						$fabrica_distribuidor   ,
						'$coleta_postagem'      ,
						$xdata_postagem         ,
						'troca_lote'
					)";
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "
			INSERT INTO tbl_comunicado (
				descricao              ,
				mensagem               ,
				tipo                   ,
				fabrica                ,
				obrigatorio_os_produto ,
				obrigatorio_site       ,
				posto                  ,
				ativo
			) VALUES (
				'".utf8_decode('OS $sua_os - AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA')."',
				'".utf8_decode('A Fábrica autorizou a fazer a devolução de venda do produto relativo à OS $sua_os. A Telecontrol coletará este produto no seu posto.')."',
				'".utf8_decode('AUTORIZAÇÃO DE DEVOLUÇÃO DE VENDA')."',
				$login_fabrica,
				'f' ,
				't',
				$posto,
				't'
			);";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}else{
			if (strlen($msg_erro) == 0) {

				$sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($os, $produto);";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT CURRVAL ('seq_os_produto')";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$os_produto = pg_fetch_result($res,0,0);

				$sql = "SELECT pedido
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						WHERE tbl_os_produto.os = $os
						AND tbl_os_item.pedido NOTNULL";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$sql2 = "SELECT fn_pedido_cancela_garantia(4311,$login_fabrica,pedido,peca,os_item,'Troca de Produto',$login_admin)
								FROM  tbl_os_item
								JOIN   tbl_servico_realizado USING (servico_realizado)
								JOIN   tbl_os_produto        ON tbl_os_item.os_produto = tbl_os_produto.os_produto
								WHERE  tbl_os_produto.os = $os
								AND (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto)
								AND    tbl_os_item.pedido NOTNULL " ;
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					//Cancela a peça que ainda não teve o seu pedido exportado //Raphael Giovanini
					$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde_cancelada + tbl_pedido_item.qtde
							FROM tbl_os_item, tbl_os_produto
							WHERE tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
							AND   tbl_os_item.os_produto = tbl_os_produto.os_produto
							AND   tbl_os_produto.os = $os
							AND   tbl_os_item.pedido NOTNULL";
					$res3 = @pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

			}


				if(strlen($msg_erro)==0){

					$sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE troca_produto AND fabrica = $login_fabrica" ;
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if(pg_num_rows($res) > 0){
						$servico_realizado = pg_fetch_result($res,0,0);
					}

					if(strlen($servico_realizado)==0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!\n";

					$aguardando_peca_reparo = 'f';

					$quantidade_item = 1;

					$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin,aguardando_peca_reparo) VALUES ($os_produto, $peca, " . ($login_fabrica == 81 ? $quantidade_item : 1) . ",$servico_realizado, $login_admin,'$aguardando_peca_reparo')";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);


					$sql = "SELECT data_fechamento FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NOT NULL";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_os SET
							troca_garantia          = 't',
							ressarcimento           = 'f',
							troca_garantia_admin    = $login_admin
							WHERE os = $os AND fabrica = $login_fabrica";
					$res = @pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_os_extra
							   SET obs_nf = '$observacao_pedido'
							 WHERE os     = $os;";

					$res = @pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);


					if(strlen($troca_garantia_mao_obra) > 0 ){
						$sql = "UPDATE tbl_os SET mao_de_obra = $troca_garantia_mao_obra WHERE os = $os AND fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "SELECT * FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_fechamento IS NULL";
					$res = @pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);


					//--== Novo Procedimento para Troca | Raphael Giovanini ===========

					if (( $setor=='Procon' OR $setor=='SAP' OR $setor=='Jurídico' ) AND(strlen($ri)=="null"))
						$msg_erro .= "\nObrigatório o preenchimento do RI";

					$modalidade_transporte = $_POST["modalidade_transporte"];
					if(strlen($modalidade_transporte)==0)$xmodalidade_transporte = "''";
					if(in_array($login_fabrica, array(81, 114))){
						if(strlen($modalidade_transporte)==0) $msg_erro .= "É obrigatório a escolha da modalidade de transporte\n";
						else $xmodalidade_transporte = "'$modalidade_transporte'";
					}

					if(strlen($msg_erro) == 0 ){
						$sql = "INSERT INTO tbl_os_troca (
									setor                 ,
									situacao_atendimento  ,
									os                    ,
									admin                 ,
									peca                  ,
									observacao            ,
									causa_troca           ,
									gerar_pedido          ,
									envio_consumidor      ,
									ri                    ,
									fabric                ,
									modalidade_transporte ,
									distribuidor          ,
									coleta_postagem       ,
									data_postagem         ,
									obs_causa
								)VALUES(
									'$setor'                 ,
									$situacao_atendimento    ,
									$os                      ,
									$login_admin             ,
									$peca                    ,
									'$observacao_pedido'     ,
									$causa_troca             ,
									$gerar_pedido            ,
									'$envio_consumidor'      ,
									$ri                      ,
									$login_fabrica           ,
									$xmodalidade_transporte  ,
									$fabrica_distribuidor    ,
									'$coleta_postagem'       ,
									$xdata_postagem          ,
									'troca_lote'
								)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

					}

				 	$sql = "INSERT INTO tbl_comunicado (
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_os_produto ,
								obrigatorio_site       ,
								posto                  ,
								ativo
							) VALUES (
								'OS $sua_os - Troca de Produto',
								'".utf8_decode('A Fábrica irá fazer a troca do produto da OS ')."$sua_os',
								'OS Troca de Produto',
								$login_fabrica,
								'f' ,
								't',
								$posto,
								't'
							);";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		if(!empty($msg_erro)){
			$erro = "Erro na OS : ".$sua_os." :\n".$msg_erro;
			break;
		}else{
			$array_sua_os[] = $sua_os;
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT");

		$sql = "SELECT  nome,
						cnpj,
						contato_endereco,
						contato_numero,
						contato_complemento,
						contato_bairro,
						contato_cidade,
						contato_cep,
						contato_estado
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto.posto = $posto";
		$res = pg_query($con,$sql);
		$posto_nome 		= pg_fetch_result($res, 0, 'nome');
		$posto_cnpj 		= pg_fetch_result($res, 0, 'cnpj');
		$posto_endereco 	= pg_fetch_result($res, 0, 'contato_endereco');
		$posto_numero 		= pg_fetch_result($res, 0, 'contato_numero');
		$posto_complemento  = pg_fetch_result($res, 0, 'contato_complemento');
		$posto_bairro 		= pg_fetch_result($res, 0, 'contato_bairro');
		$posto_cidade 		= pg_fetch_result($res, 0, 'contato_cidade');
		$posto_cep 			= pg_fetch_result($res, 0, 'contato_cep');
		$posto_estado		= pg_fetch_result($res, 0, 'contato_estado');

		$endereco = $posto_endereco.", ".$posto_numero." ".$posto_complemento;

		$array_sua_os = implode("<br>", $array_sua_os);
		$headers  = "MIME-Version: 1.0 \r\n";
        $headers .= "Content-type: text/html \r\n";
        $headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";

		$message = "Segue em anexo as Ordens de Serviço que geraram troca para atender a reposição de aparelhos em garantia";
		$message .= "<br><br> Orderm de Serviço:<br> {$array_sua_os}";
		$message .= "<br><br> Revenda : {$revenda_nome} <br><br>";
		$message .= "Enviar os aparelhos para:<br>
					<font color='red'>{$posto_nome}</font> <br>
					<font color='red'>Endereço de entrega</font> <br>
					<font color='red'>{$endereco}</font> <br>
					<font color='red'>{$posto_bairro} - {$posto_cidade} / {$posto_estado}</font> <br>
					<font color='red'>CEP:{$posto_cep}</font> <br>
					<font color='red'>CNPJ: {$posto_cnpj}</font><br><br>
					OBS: {$observacao_pedido}";

		$assunto = 'Troca de produto na Ordem de Serviço';
		$emails = "juliane.santosdasilva@la.spectrumbrands.com,carlos.uzeda@bestwaybrasil.com.br,claudio.silva@telecontrol.com.br,jader.abdo@telecontrol.com.br,marcos.barbante@telecontrol.com.br";
		#$emails = "ronald.santos@telecontrol.com.br,";
		/*include_once '../class/email/mailer/class.phpmailer.php';
		$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

		$mailer->IsSMTP();
        $mailer->IsHTML();
        foreach ($emails as $email) {
        	 $mailer->AddAddress($email);
        }
        $mailer->Subject = $assunto;
        $mailer->Body = $message;*/
        if(mail($emails, utf8_decode($assunto), utf8_decode($message), $headers)){
        	echo "1";
        }else{
        	echo "Erro ao enviar E-mail";
        }
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
		echo $erro;
	}

	exit;

}
