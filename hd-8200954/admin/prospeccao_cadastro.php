<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include 'funcoes.php';
	include_once '../class/email/mailer/class.phpmailer.php';
	$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

	if (!function_exists('checaCPF')) {
		function checaCPF  ($cpf,$return_str = true, $use_savepoint = false){
		   global $con, $login_fabrica;	// Para conectar com o banco...
				$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
		
				if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

				if ($use_savepoint) $n = @pg_query($con,"SAVEPOINT checa_CPF");

				if(strlen($cpf) > 0){
					$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
					if ($res_cpf === false) {
						$cpf_erro = pg_last_error($con);
						if ($use_savepoint) $n = @pg_query($con,"ROLLBACK TO SAVEPOINT checa_CPF");
						return ($return_str) ? $cpf_erro : false;
					}
				}
				return $cpf;

		}
	}


	$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

	$ajax = $_GET['ajax'];

	if($ajax == 'cidade'){
		$estado = $_GET['estado'];

		$sql = "SELECT cod_ibge, cidade FROM tbl_ibge WHERE estado = '".$estado."'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			for($i = 0; $i < pg_num_rows($res); $i++){
				$cod_ibge = pg_fetch_result($res, $i, 'cod_ibge');
				$cidade   = pg_fetch_result($res, $i, 'cidade');

				$retorno .= "<option value='".$cod_ibge."'>".utf8_encode($cidade)."</option>";
			}			
		}else{
			$retorno = "<option value=''>Cidade não encontrada</option>";
		}

		echo $retorno;

		exit;
	}

	if($ajax == 'editar' OR $ajax == 'pendencia'){
		$prospeccao = $_GET['prospeccao'];
		$posto = $_GET['posto'];
		$cnpj = $_GET['cnpj'];
		$cnpj = str_replace('.', '', $cnpj);
		$cnpj = str_replace('-', '', $cnpj);
		$cnpj = str_replace('/', '', $cnpj);

		if($posto){
			$cond = " tbl_prospeccao.posto = $posto ";
		}else{
			if(!empty($prospeccao)){
				$cond = " tbl_prospeccao.prospeccao = $prospeccao ";
			}else{
				$sql = "SELECT posto FROM tbl_posto WHERE cnpj = '$cnpj'";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$posto = pg_fetch_result($res, 0, 'posto');
					$cond = " tbl_prospeccao.posto = $posto ";
				}
			}
		}

		$sql = "SELECT  tbl_posto.posto,
						tbl_posto.cnpj,
						tbl_posto.nome,
						tbl_ibge.cod_ibge,
						tbl_ibge.cidade,
						tbl_ibge.estado,
						tbl_prospeccao.prospeccao,
						tbl_prospeccao.contato,
						tbl_prospeccao.linha,
						tbl_prospeccao.email,
						tbl_prospeccao.datas,
						tbl_prospeccao.via_documento,
						tbl_prospeccao.situacao_documento,
						tbl_prospeccao.status_contrato,
						tbl_prospeccao.observacao,
						tbl_prospeccao.telefones[1][1] AS fone,
						tbl_prospeccao.pendencias[1][2] AS produtos,
						tbl_prospeccao.pendencias[2][2] AS pendencias
					FROM tbl_prospeccao
					LEFT JOIN tbl_posto ON tbl_prospeccao.posto = tbl_posto.posto
					JOIN tbl_ibge ON tbl_prospeccao.cod_ibge = tbl_ibge.cod_ibge
					WHERE $cond";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$posto 				= pg_fetch_result($res, 0, 'posto');
			$prospeccao			= pg_fetch_result($res, 0, 'prospeccao');
			$cnpj 				= pg_fetch_result($res, 0, 'cnpj');
			$nome 				= pg_fetch_result($res, 0, 'nome');
			$cod_ibge 			= pg_fetch_result($res, 0, 'cod_ibge');
			$cidade 			= utf8_encode(pg_fetch_result($res, 0, 'cidade'));
			$estado 			= pg_fetch_result($res, 0, 'estado');
			$contato 			= pg_fetch_result($res, 0, 'contato');
			$linha 				= pg_fetch_result($res, 0, 'linha');
			$email 				= pg_fetch_result($res, 0, 'email');
			$datas 				= pg_fetch_result($res, 0, 'datas');
			$via_documento 		= pg_fetch_result($res, 0, 'via_documento');
			$situacao_documento = pg_fetch_result($res, 0, 'situacao_documento');
			$status_contrato 	= pg_fetch_result($res, 0, 'status_contrato');
			$observacao 		= pg_fetch_result($res, 0, 'observacao');
			$fone 				= pg_fetch_result($res, 0, 'fone');
			$produtos 			= pg_fetch_result($res, 0, 'produtos');
			$pendencias 		= pg_fetch_result($res, 0, 'pendencias');

			$linha = str_replace('{', '', $linha);
			$linha = str_replace('}', '', $linha);

			if($ajax == 'pendencia'){
				$sqlLinha = "SELECT nome FROM tbl_linha WHERE linha IN($linha)";
				$resLinha = pg_query($con,$sqlLinha);				

				for($i = 0; $i < pg_num_rows($resLinha); $i++){
					$linhas .= utf8_encode(pg_fetch_result($resLinha, $i, 'nome'));
					if($i < (pg_num_rows($resLinha) - 1)){
						$linhas .= ',';
					}
					if(($i + 1)%3==0) $linhas .= '<br>';
				}				
				$linha = $linhas;

				$sqlOs = "SELECT sua_os FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto AND excluida IS NOT TRUE AND finalizada IS NULL";
				$resOs = pg_query($con,$sqlOs);
				if(pg_num_rows($resOs) > 0){
					for($i = 0; $i < pg_num_rows($resOs); $i++){
						$os .= pg_fetch_result($resOs, $i, 'sua_os');
						if($i < (pg_num_rows($resOs) - 1)){
							$os .= ',';
						}
						if(($i + 1)%3==0) $os .= "<br>";
					}
				}

				$sqlEx = "SELECT sum(mao_de_obra) FROM tbl_extrato WHERE fabrica = $login_fabrica AND posto = $posto AND extrato NOT IN(SELECT extrato FROM tbl_extrato JOIN tbl_extrato_conferencia USING(extrato) WHERE tbl_extrato.fabrica = $login_fabrica AND tbl_extrato.posto = $posto)";
				$resEx = pg_query($con,$sqlEx);
				if(pg_num_rows($resEx) > 0){
					$mao_obra = "R$".number_format(pg_fetch_result($resEx,0,0),2,',','.');

				}
			}
			//echo $linha;
			$datas = str_replace('},','};',$datas);
			$datas = str_replace('}','',$datas);
			$datas = str_replace('{','',$datas);

			$xls_datas = explode(';',$datas);

			foreach($xls_datas AS $data){
				list($key,$valor) = explode(",",$data);
				$$key = $valor;
				$$key = ($$key == "NULL" OR $$key == "null") ? "" : $$key;
			}

			
			$retorno = array(	'posto'				=>$posto,
								'prospeccao'		=>$prospeccao,
								'cnpj'				=>$cnpj,
								'nome'				=>$nome,
								'cod_ibge'			=>$cod_ibge,
								'cidade'			=>$cidade,
								'estado'			=>$estado,
								'contato'			=>$contato,
								'linha'				=>$linha,
								'email'				=>$email,
								'datas'				=>$datas,
								'datas'				=>$datas,
								'via_documento'		=>$via_documento,
								'situacao_documento'=>$situacao_documento,
								'status_contrato'	=>$status_contrato,
								'observacao'		=>$observacao,
								'fone'				=>$fone,
								'os'				=>$os,
								'mobra'				=>$mao_obra,
								'produtos'			=>$produtos,
								'pendencias'		=>$pendencias
							);
			
			$xls = "<table>
						<tr bgcolor='#596d9b'>
							<td align='center'><b><font color='#FFFFFF'>CNPJ</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Razão</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Estado</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Cidade</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Telefone</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Contato</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>E-mail</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Linhas</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de chegada de documentos </font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Via de documentos</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Situação de documentos</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de notificação de pendências</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de regularização de pendências</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de Entrega (envio) do cadastro financeiro</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de Retorno (recebimento) cadastro financeiro</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de Envio de contrato de prestação </font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de Retorno do contrato de prestação</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Status do contrato</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de Envio de documento ao cartório</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de Retorno dos documentos do cartório</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Data de envio do contrato</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Observação</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Ordens de Serviço abertas</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>MO a pagar ao posto</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Produtos a devolver</font></b></td>
							<td align='center'><b><font color='#FFFFFF'>Pendencias Financeiras</font></b></td>
						</tr>

						<tr>
							<td>".$retorno['cnpj']."</td>
							<td>".$retorno['nome']."</td>
							<td>".$retorno['estado']."</td>
							<td>".$retorno['cidade']."</td>
							<td>".$retorno['fone']."</td>
							<td>".$retorno['contato']."</td>
							<td>".$retorno['email']."</td>
							<td>".$retorno['linha']."</td>
							<td>".$dt_chegada."</td>
							<td>".$retorno['via_documento']."</td>
							<td>".$retorno['situacao_documento']."</td>
							<td>".$dt_notificacao."</td>
							<td>".$dt_regularizacao."</td>
							<td>".$dt_envio_financeiro."</td>
							<td>".$dt_retorno_financeiro."</td>
							<td>".$dt_envio_prestacao."</td>
							<td>".$dt_retorno_prestacao."</td>
							<td>".$retorno['status_contrato']."</td>
							<td>".$dt_envio_cartorio."</td>
							<td>".$dt_retorno_cartorio."</td>
							<td>".$dt_envio_contrato."</td>
							<td>".$retorno['observacao']."</td>
							<td align='left'>".$retorno['os']."</td>
							<td>".$retorno['mobra']."</td>
							<td>".$retorno['produtos']."</td>
							<td>".$retorno['pendencias']."</td>
						</tr>
					</table>";

			$fp = fopen("xls/relatorio-prospeccao-pendencia-$posto.xls","w");
			fwrite($fp,$xls);
			fclose($fp);

			$retorno = json_encode($retorno);
			echo $retorno;

		}
		exit;
	}

	if($ajax == 'credenciar'){
		$prospeccao = $_GET['prospeccao'];
		$posto 		= $_GET['posto'];

		$resT = pg_query($con,'BEGIN');

		$sql = "INSERT INTO tbl_credenciamento(
											posto,
											fabrica,
											status,
											confirmacao,
											confirmacao_admin) VALUES(
											$posto,
											$login_fabrica,
											'CREDENCIADO',
											CURRENT_TIMESTAMP,
											$login_admin)";
		$res = pg_query($con,$sql);

		if(!pg_last_error($con)){
			$sql = "INSERT INTO tbl_posto_fabrica(
											posto,
											fabrica,
											senha,
											admin,
											tipo_posto,
											contato_nome,
											contato_email,
											contato_estado,
											contato_cidade,
											contato_fone_comercial)
											(SELECT $posto,$login_fabrica,'*',$login_admin,1,contato,email,estado,cidade,telefones[1][1]
													FROM tbl_prospeccao
													JOIN tbl_ibge ON tbl_prospeccao.cod_ibge = tbl_ibge.cod_ibge
													WHERE tbl_prospeccao.prospeccao = $prospeccao
													AND tbl_prospeccao.posto = $posto)
											";
			$res = pg_query($con,$sql);
			if(!pg_last_error($con)){
				$sqlLinha = "SELECT linha FROM tbl_prospeccao WHERE prospeccao = $prospeccao AND posto = $posto";
				$resLinha = pg_query($con,$resLinha);

				$linhas = pg_fetch_result($resLinha, 0, 'linha');
				$linhas = str_replace('{', '', $linhas);
				$linhas = str_replace('}', '', $linhas);

				foreach($linhas AS $linha){
					$sql = "INSERT INTO tbl_posto_linha(posto,linha) VALUES($posto,$linha)";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_last_error($con);
				}
			}else{
				$msg_erro = pg_last_error($con);
			}
		}else{
			$msg_erro = pg_last_error($con);
		}

		if(empty($msg_erro)){
			$resT = pg_query($con,'COMMIT');
			echo 'OK|Posto Credenciado com Sucesso, você será redirecionado para tela de cadasto de Postos para completar os dados';
		}else{
			$resT = pg_query($con,'ROLLBACK');
			echo 'NO|Erro ao credenciar Posto';
		}
		
		exit;
	}

	if($ajax == 'email'){
		$prospeccao = $_GET['prospeccao'];
		
		$sqlEmail = "SELECT email FROM tbl_prospeccao WHERE fabrica = $login_fabrica AND prospeccao = $prospeccao";
		$resEmail = pg_query($con,$sqlEmail);

		if(pg_num_rows($resEmail) > 0){

			$email_posto = strtolower(pg_fetch_result($resEmail, 0, 'email'));

			$sql = "SELECT email FROM tbl_admin WHERE admin = $login_admin";
			$res = pg_query($con,$sql);

			$admin_email = strtolower(pg_fetch_result($res, 0, 'email'));

			$subject  = "Rede Autorizada";
			$message  = "<b>Prezado Posto</b><br><br>";
			$message .= $_GET['mensagem']."<br><br>";
			$message .= "<b> Atenciosamente<br><br>";

            $mailer->IsSMTP();
            $mailer->IsHTML();
            $mailer->AddAddress($email_posto);
            $mailer->Subject = $subject;
            $mailer->Body = $message;
            $mailer->AddReplyTo($admin_email);

            if($mailer->Send()){
            	echo "OK|E-mail enviado com sucesso";
            }else{
            	echo "NO|Erro ao enviar E-mail";
            }
		}

		exit;
	}

	$btn_acao = $_POST['btn_acao'];

	if($btn_acao == 'pesquisar'){

		$estado_consulta	= $_POST['estado_consulta'];
		$cidade_consulta	= $_POST['cidade_consulta'];
		$linha_pesquisa = $_POST['linha_pesquisa'];

		if(!empty($estado_consulta) AND empty($cidade_consulta)){
			$msg_erro = "Informe a cidade";			
		}

		if(!empty($cidade_consulta)){
			$cond = " AND tbl_prospeccao.cod_ibge = $cidade_consulta ";
		}

		if(!empty($linha_pesquisa)){
			$cond .= " AND $linha_pesquisa = ANY(linha) ";
		}

	}

	if($btn_acao == 'gravar'){
		$prospeccao 			= $_POST['prospeccao'];
		$posto 					= $_POST['posto'];
		$cnpj 					= $_POST['cnpj'];
		$razao 					= $_POST['razao'];
		$estado 				= $_POST['estado'];
		$cidade 				= $_POST['cidade'];
		$linhas					= $_POST['linha'];
		$fone 					= $_POST['fone'];
		$contato				= $_POST['contato'];
		$email 					= $_POST['email'];
		$dt_chegada 			= (!empty($_POST['dt_chegada'])) ? $_POST['dt_chegada'] : 'null';
		$via_documento 			= (!empty($_POST['via_documento'])) ? $_POST['via_documento'] : 'null';
		$situacao_documento 	= (!empty($_POST['situacao_documento'])) ? $_POST['situacao_documento'] : 'null';
		$dt_notificacao 		= (!empty($_POST['dt_notificacao'])) ? $_POST['dt_notificacao'] : 'null';
		$dt_regularizacao 		= (!empty($_POST['dt_regularizacao'])) ? $_POST['dt_regularizacao'] : 'null';
		$dt_envio_financeiro 	= (!empty($_POST['dt_envio_financeiro'])) ? $_POST['dt_envio_financeiro'] : 'null';
		$dt_retorno_financeiro 	= (!empty($_POST['dt_retorno_financeiro'])) ? $_POST['dt_retorno_financeiro'] : 'null';
		$dt_envio_prestacao 	= (!empty($_POST['dt_envio_prestacao'])) ? $_POST['dt_envio_prestacao'] : 'null';
		$dt_retorno_prestacao 	= (!empty($_POST['dt_retorno_prestacao'])) ? $_POST['dt_retorno_prestacao'] : 'null';
		$status_contrato 		= (!empty($_POST['status_contrato'])) ? $_POST['status_contrato'] : 'null';
		$dt_envio_cartorio 		= (!empty($_POST['dt_envio_cartorio'])) ? $_POST['dt_envio_cartorio'] : 'null';
		$dt_retorno_cartorio 	= (!empty($_POST['dt_retorno_cartorio'])) ? $_POST['dt_retorno_cartorio'] : 'null';
		$dt_envio_contrato 		= (!empty($_POST['dt_envio_contrato'])) ? $_POST['dt_envio_contrato'] : 'null';
		$obs 			 		= (!empty($_POST['obs'])) ? $_POST['obs'] : 'null';

		$fone = "{{".$fone."}}";

		if(empty($estado)){
			$msg_erro = "Informe o estado";
		}

		if(empty($cidade)){
			$msg_erro = "Informe a cidade";
		}else{
			$sql = "SELECT cidade FROM tbl_ibge WHERE cod_ibge = $cidade";
			$res = pg_query($con,$sql);
			$cidade_nome = pg_fetch_result($res, 0, 'cidade');
		}

		if(empty($fone)){
			$msg_erro = "Informe o telefone";
		}

		if(empty($contato)){
			$msg_erro = "Informe o contato";
		}

		if(empty($email)){
			$msg_erro = "Informe o e-mail";
		}

		if(!count($linhas)){
			$msg_erro = "Informe a linha";
		}else{
			
				$linha = "{".implode(',',$linhas)."}";			
		}

		if($cnpj){ 
			if(empty($posto)){ 
				$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cnpj));
				if(empty($valida_cpf_cnpj)){
					$posto_cnpj = checaCPF($cnpj,false);

					if (is_numeric($posto_cnpj)) {

						$sqlPosto = "SELECT posto FROM tbl_posto WHERE cnpj = '$posto_cnpj'";
						$resPosto = pg_query($con,$sqlPosto);
						if(pg_num_rows($resPosto) > 0){
							$posto = pg_fetch_result($resPosto, 0, 'posto');
						}else{
							if(empty($razao)){
								$msg_erro = "Informe a razão social do posto";
							}else{
								$sqlPosto = "INSERT INTO tbl_posto(
															nome,
															cnpj,
															cidade,
															estado,
															email,
															fone,
															contato) VALUES(
															'$razao',
															'$posto_cnpj',
															'$cidade_nome',
															'$estado',
															'$email',
															'$fone',
															'$contato') RETURNING posto";
								$resPosto = pg_query($con,$sqlPosto);
								$msg_erro = pg_last_error($con);
								if(empty($msg_erro)){
									$posto = pg_fetch_result($resPosto, 0, 0);
								}
							}
						}

					}else{
						$msg_erro = "CNPJ inválido";
					}

				}else{

					$msg_erro = $valida_cpf_cnpj;
				}
			}
		}else{
			
			$posto = "null";
			
		}

		if(empty($msg_erro)){
			
			$datas = "{{dt_chegada,$dt_chegada},{dt_notificacao,$dt_notificacao},{dt_regularizacao,$dt_regularizacao},{dt_envio_financeiro,$dt_envio_financeiro},{dt_retorno_financeiro,$dt_retorno_financeiro},{dt_envio_prestacao,$dt_envio_prestacao},{dt_retorno_prestacao,$dt_retorno_prestacao},{dt_envio_cartorio,$dt_envio_cartorio},{dt_retorno_cartorio,$dt_retorno_cartorio},{dt_envio_contrato,$dt_envio_contrato}}";

			if(empty($prospeccao)){

				$sqlP = "INSERT INTO tbl_prospeccao(
												fabrica,
												admin,
												posto,
												cod_ibge,
												contato,
												linha,
												email,
												datas,
												via_documento,
												situacao_documento,
												status_contrato,
												observacao,
												data_input,
												telefones) VALUES(
												$login_fabrica,
												$login_admin,
												$posto,
												$cidade,
												'$contato',
												'$linha',
												'$email',
												'$datas',
												'$via_documento',
												'$situacao_documento',
												'$status_contrato',
												'$obs',
												CURRENT_TIMESTAMP,
												'$fone')";
				$resP = pg_query($con,$sqlP);
				$msg_erro = pg_last_error($con);

			}else{

				$sqlP = "UPDATE tbl_prospeccao SET 
									posto 				= $posto,
									cod_ibge 			= $cidade,
									contato 			= '$contato',
									linha 				= '$linha',
									email 				= '$email',
									datas 				= '$datas',
									via_documento 		= '$via_documento',
									situacao_documento 	= '$situacao_documento',
									status_contrato 	= '$status_contrato',
									observacao 			= '$obs',
									telefones			= '$fone'
							WHERE prospeccao = $prospeccao";
				$resP = pg_query($con,$sqlP);
				$msg_erro = pg_last_error($con);
				
			}

			echo nl2br($sqlP);
			if(empty($msg_erro)){
				header("Location: prospeccao_cadastro.php?msgs=Gravado com sucesso");
			}
		}
				
	}

	if($btn_acao == 'gravar_pendencia'){
		$prospeccao_pendencia = $_POST['prospeccao_pendencia_cadastro'];
		$produtos = $_POST['produtos_devolver'];
		$pendencias = $_POST['pendencias'];

		$produtos = (empty($produtos)) ? "null" : $produtos;
		$pendencias = (empty($pendencias)) ? "null" : $pendencias;

		if(empty($pendencias) AND empty($produtos)){
			$pendencias_posto = "";
		}else{
			$pendencias_posto = "{{produtos,$produtos},{pendencias,$pendencias}}";
		}

		
		$sql = "UPDATE tbl_prospeccao SET pendencias = '$pendencias_posto' WHERE prospeccao = $prospeccao_pendencia";
		$res = pg_query($con,$sql);

		$msg_erro = pg_last_error($con);

		if(empty($msg_erro)){
			header("Location: prospeccao_cadastro.php?msgs=Gravado com sucesso");
		}

	}

	include "cabecalho.php";
?>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<script src="https://code.jquery.com/ui/1.9.1/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css" />

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>

<script type='text/javascript'>
	$(document).ready(function(){

		Shadowbox.init();

		$("input[rel=data]").datepick({startDate:'01/01/2000'});
		$("input[rel=data]").mask("99/99/9999");

		$("#cnpj").mask("99.999.999/9999-99");

		$("#fone").mask("(99) 9999-9999");

		$( "#tabs" ).tabs();

		$("#estado").change(function(){
			var estado = $(this).val();
			consultaCidade(estado);			
		});

		if($(".sucesso").is(':visible')){
			setTimeout(function(){$(".sucesso").hide();},2000);
		}

		$("#estado_consulta").change(function(){
			var estado = $(this).val();
			$.ajax({
				url: "<?php echo $PHP_SELF;?>?ajax=cidade&estado="+estado,
				cache: false,
				success: function(data){
					$("#cidade_consulta").html(data);					
				}
			});
		});

		$(".prospeccao").click(function(){
			var prospeccao = $(this).attr('rel');
			buscaProspeccao(prospeccao,cnpj=null);			
		});

		$("#envia_email").click(function(){
			var prospeccao = $("#prospeccao").val();	
			var mensagem = $("#msg").val();			

			$.ajax({
				url: "<?php echo $PHP_SELF;?>?ajax=email&prospeccao="+prospeccao+"&mensagem="+mensagem,
				cache: false,
				success: function(data){
					var retorno = data.split('|');

					if(retorno[0] == 'OK'){
						$("#msg").val("");
					}

					alert(retorno[1]);
				}
			});
		});

		
		$("#cad_pendencia").click(function(){
			
			var posto = $("#posto_pendencia_cadastro").val();

			if(posto == ''){
				alert('Informe o posto');
				return false;
			}

			$.ajax({
				url: "<?php echo $PHP_SELF;?>?ajax=pendencia&posto="+posto,
				cache: false,
				success: function(data){	
					
					var obj = jQuery.parseJSON(data);
					if(obj.via_documento == 'null'){
						obj.via_documento = '';
					}
					if(obj.situacao_documento == 'null'){
						obj.situacao_documento = '';
					}
					if(obj.status_contrato == 'null'){
						obj.status_contrato = '';
					}
					if(obj.observacao == 'null'){
						obj.observacao = '';
					}

					$("#posto_pendencia_cadastro").val(obj.posto);
					$("#prospeccao_pendencia_cadastro").val(obj.prospeccao);
					$("#contato_pendencia_cadastro").html(obj.contato);
					$("#email_pendencia_cadastro").html(obj.email);
					$("#cnpj_pendencia_cadastro").html(obj.cnpj);
					$("#razao_pendencia_cadastro").html(obj.nome);
					$("#via_documento_pendencia_cadastro").html(obj.via_documento);
					$("#situacao_documento_pendencia_cadastro").html(obj.situacao_documento);
					$("#status_contrato_pendencia_cadastro").html(obj.status_contrato);
					$("#obs_pendencia_cadastro").html(obj.observacao);
					$("#fone_pendencia_cadastro").html(obj.fone);
					$("#estado_pendencia_cadastro").html(obj.estado);
					$("#cidade_pendencia_cadastro").html(obj.cidade);
					$("#linha_pendencia_cadastro").html(obj.linha);
					$("#os_pendencia_cadastro").html(obj.os);
					$("#mo_pendencia_cadastro").html(obj.mobra);
					$("#produtos_devolver").val(obj.produtos);
					$("#pendencias").val(obj.pendencias);
					
					var datas = obj.datas.split(';');
					for(i = 0; i < datas.length; i++){
						vet = datas[i].split(',');
						if(vet[1] == 'NULL' || vet[1] == 'null'){
							vet[1] = '';
						}
						$("#"+vet[0]+"_pendencia_cadastro").html(vet[1]);
					}

					$("#pendencia_cadastro").show();
					$("#excel").html("<input type='button' value='Download Excel' onclick='window.open(\"xls/relatorio-prospeccao-pendencia-"+posto+".xls\")'>");
				}
			});
		});

		$("#credenciar").click(function(){
			var prospeccao = $("#prospeccao").val();
			var posto = $("#posto").val();

			$.ajax({
				url: "<?php echo $PHP_SELF;?>?ajax=credenciar&prospeccao="+prospeccao+"&posto="+posto,
				cache: false,
				success: function(data){
					var retorno = data.split('|');

					if(retorno[0] == 'OK'){
						alert(retorno[1]);
						window.open("posto_cadastro.php?posto="+posto);
					}else{
						alert(retorno[1]);
					}
				}
			});
		});

	});
	
	function buscaProspeccao(prospeccao){
			
			var cnpj = $("#cnpj").val();

			$.ajax({
				url: "<?php echo $PHP_SELF;?>?ajax=editar&prospeccao="+prospeccao+"&cnpj="+cnpj,
				cache: false,
				success: function(data){	
					
					var obj = jQuery.parseJSON(data);
					if(obj.via_documento == 'null'){
						obj.via_documento = '';
					}
					if(obj.situacao_documento == 'null'){
						obj.situacao_documento = '';
					}
					if(obj.status_contrato == 'null'){
						obj.status_contrato = '';
					}
					if(obj.observacao == 'null'){
						obj.observacao = '';
					}
					

					$("#posto").val(obj.posto);
					$("#prospeccao").val(obj.prospeccao);
					$("#contato").val(obj.contato);
					$("#email").val(obj.email);
					$("#cnpj").val(obj.cnpj);
					$("#razao").val(obj.nome);
					$("#via_documento").val(obj.via_documento);
					$("#situacao_documento").val(obj.situacao_documento);
					$("#status_contrato").val(obj.status_contrato);
					$("#obs").val(obj.observacao);
					$("#fone").val(obj.fone);
					$("#estado").val(obj.estado);
					$("#posto").val(obj.posto);
					$("#cidade").html("<option value='"+obj.cod_ibge+"'>"+obj.cidade+"</option>");
					
					$("input[type=checkbox]").attr('checked',false);
					var linhas = obj.linha.split(',');
					for(i = 0; i < linhas.length; i++){
						$("input[rel="+linhas[i]+"]").attr('checked','checked');
					}

					var datas = obj.datas.split(';');
					for(i = 0; i < datas.length; i++){
						vet = datas[i].split(',');
						if(vet[1] == 'NULL'){
							vet[1] = '';
						}
						$("#"+vet[0]).val(vet[1]);
					}

					if( $("#dt_envio_contrato").val() != "" ){
						$("#credenciar").show();
					}else{
						$("#credenciar").hide();
					}

					if(obj.posto == 'null'){
						obj.posto = '';
					}
					
								
				}
			});
		}
	function consultaCidade(estado){
		$.ajax({
			url: "<?php echo $PHP_SELF;?>?ajax=cidade&estado="+estado,
			cache: false,
			success: function(data){
				$("#cidade").html(data);					
			}
		});
	}

	function pesquisaPosto(campo,tipo){
	    var campo = campo.value;

	    if (jQuery.trim(campo).length > 2){
	        Shadowbox.open({
	            content:	"posto_pesquisa_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
	            player:	    "iframe",
	            title:		"Pesquisa Posto",
	            width:	    800,
	            height:	    500
	        });
	    }else
	        alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

	function retorna_posto(posto,codigo_posto,nome,cnpj,pais,cidade,estado,nome_fantasia){
		gravaDados('posto_pendencia_cadastro',posto);
	    gravaDados('posto_nome_pendencia_cadastro',nome);
	    gravaDados('posto_codigo_pendencia_cadastro',codigo_posto);
	}

	function gravaDados(name, valor){
	        try {
	                $("input[name="+name+"]").val(valor);
	        } catch(err){
	                return false;
	        }
	}

	function descredenciaPosto(){
	    var posto = $("posto_pendencia_cadastro").val();
	    var codigo_posto = $("input[name=posto_codigo_pendencia_cadastro]").val();

	   window.open("credenciamento.php?codigo="+codigo_posto+"&posto"+posto+"&listar=3");
	}

</script>	

<style type='text/css'>
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.formulario tr td{
	padding:10px 0 0 20px;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.prospeccao:hover{
	background: #CCC;
	cursor: pointer;
}

ul li{
	width: 140px;
	height: 20px;
	font-size: 11px;
	font-weight: bold;
	text-align: center;
}
</style>

<?php
	
	if(!empty($msg_erro)){
?>
		<table align='center' width='700'>
			<tr class='msg_erro'>
				<td>
					<?php
						echo $msg_erro;
					?>
				</td>
		</table>
<?php
	}
?>

<?php
	if($_GET['msgs']){
?>
		<table align='center' width='700'>
			<tr class='sucesso'>
				<td>
					<?php
						echo $_GET['msgs'];
					?>
				</td>
		</table>
<?php
	}
?>

<div id='tabs' style='border:none;'>
	<ul style='width: 700px;margin:auto;background: #FFFFFF;border: none;'>
        <li><a href="#tabs-1">Cadastro Posto</a></li>
        <li><a href="#tabs-2">Cadastro Pendências</a></li>
        <!-- <li><a href="#tabs-3">Consulta Pendências</a></li> -->
    </ul>

    <div id='tabs-1'>
    	<form name='frm_cadastro' method='post' action='<?=$PHP_SELF?>'>
			<table align='center' width='700' class='formulario'>
				<caption class='titulo_tabela'> Cadastro </caption>
				
				<tr>
					<td>
						Estado <br />
						<select name="estado" id="estado" size="1" class="frm" style="width:170px">
							<option value="">Selecione um Estado</option>
							<?php
							foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							}?>
						</select>
					</td>
					<td>
						Cidade <br />
						<select name="cidade" id="cidade" size="1" class="frm" style="width:170px">
							<option value="">Selecione uma Cidade</option>
						</select>
					</td>
				</tr> 

				<tr>
					<td>
						<fieldset>
							<legend>Linhas</legend>
							<?php
								$sql = "SELECT linha,nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
								$res = pg_query($con,$sql);
								if(pg_num_fields($res) > 0){
									for($i = 0; $i < pg_num_rows($res); $i++){
										$linha = pg_fetch_result($res, $i, 'linha');
										$linha_desc = pg_fetch_result($res, $i, 'nome');

										$checked = (in_array($linha,$linhas)) ? 'checked' : '';
										if($i%4 == 0) echo "<br />";
										echo "<input type='checkbox' name='linha[]' rel='$linha' value='$linha' $checked>$linha_desc";

										
									}
								}
							?>
						</fieldset>				
					</td>
					<td>
						Telefone <br />
						<input type='text' name='fone' id='fone' value='<?=$fone?>' size='15' class='frm telefone'>
					</td>
				</tr>

				<tr>
					<td>
						
						Contato <br />
						<input type='text' name='contato' id='contato' value='<?=$contato?>' size='35' class='frm'>
					</td>
					<td>
						E-mail <br />
						<input type='text' name='email' id='email' value='<?=$email?>' size='35' maxlenght='50' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						CNPJ <br />
						<input type='text' name='cnpj' id='cnpj' value='<?=$cnpj?>' size='16' onblur="buscaProspeccao('');" class='frm'>
					</td>
					<td>
						Razão Social <br />
						<input type='text' name='razao' id='razao' value='<?=$razao?>' size='35' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						Data de chegada de documentos <br />
						<input type='text' name='dt_chegada' id='dt_chegada' value='<?=$dt_chegada?>' rel='data' size='13' class='frm'>
					</td>
					<td>
						Via de documentos <br>
						<input type='text' name='via_documento' id='via_documento' value='<?=$via_documento?>' size='35' maxlenght='30' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						Situação de documentos <br />
						<input type='text' name='situacao_documento' id='situacao_documento' value='<?=$situacao_documento?>' size='35' maxlenght='50' class='frm'>
					</td>
					<td>
						Data de notificação de pendências <br />
						<input type='text' name='dt_notificacao' id='dt_notificacao' value='<?=$dt_notificacao?>' rel='data' size='13' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						Data de regularização de pendências <br />
						<input type='text' name='dt_regularizacao' id='dt_regularizacao' value='<?=$dt_regularizacao?>' rel='data' size='13' class='frm'>
					</td>
					<td>
						Data de Entrega (envio) do cadastro financeiro <br />
						<input type='text' name='dt_envio_financeiro' id='dt_envio_financeiro' value='<?=$dt_envio_financeiro?>' rel='data' size='13' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						Data de Retorno (recebimento) cadastro financeiro <br />
						<input type='text' name='dt_retorno_financeiro' id='dt_retorno_financeiro' value='<?=$dt_retorno_financeiro?>' rel='data' size='13' class='frm'>
					</td>
					<td>
						Data de Envio de contrato de prestação <br />
						<input type='text' name='dt_envio_prestacao' id='dt_envio_prestacao' value='<?=$dt_envio_prestacao?>' rel='data' size='13' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						Data de Retorno do contrato de prestação <br />
						<input type='text' name='dt_retorno_prestacao' id='dt_retorno_prestacao' value='<?=$dt_retorno_prestacao?>' rel='data' size='13' class='frm'>
					</td>
					<td>
						Status do contrato <br />
						<input type='text' name='status_contrato' id='status_contrato' value='<?=$status_contrato?>' size='35' maxlenght='50' class='frm'>
					</td>
				</tr>

				<tr>
					<td>
						Data de Envio de documento ao cartório <br />
						<input type='text' name='dt_envio_cartorio' id='dt_envio_cartorio' value='<?=$dt_envio_cartorio?>' rel='data' size='13' class='frm'>
					</td>
					<td>
						Data de Retorno dos documentos do cartório <br />
						<input type='text' name='dt_retorno_cartorio' id='dt_retorno_cartorio' value='<?=$dt_retorno_cartorio?>' rel='data' size='13' class='frm'>
					</td>
				</tr>

				<tr>
					<td colspan='2'>
						Data de envio do contrato <br />
						<input type='text' name='dt_envio_contrato' id='dt_envio_contrato' value='<?=$dt_envio_contrato?>' rel='data' size='13' class='frm'>
					</td>
				</tr>

				<tr>
					<td colspan='2'>
						Observação <br />
						<textarea name='obs' id='obs' cols='80' rows='5' class='frm'><?=$obs?></textarea>
					</td>
				</tr>

				<tr id='email_linha'>
					<td colspan='2'>
						Mensagem <br />
						<textarea name='msg' id='msg' cols='80' rows='5' class='frm'><?=$msg?></textarea> <br />
						<input type='button' value='Enviar E-mail' id='envia_email'>
					</td>
				</tr>

				<tr>
					<td colspan='2' align='center'>
						<input type='hidden' name='btn_acao' value=''>
						<input type='hidden' name='prospeccao' id='prospeccao' value='<?=$prospeccao?>'>
						<input type='hidden' name='posto' id='posto' value='<?=$posto?>'>
						<input type='submit' value='Cadastrar' onclick="javascript: if(document.frm_cadastro.btn_acao.value == ''){document.frm_cadastro.btn_acao.value = 'gravar';document.frm_cadastro.submit();}else{alert('Aguarde submissão');}">
						<input type='button' value='Limpar Dados' onclick="javascript: window.location='prospeccao_cadastro.php'">
						<input type='button' value='Credenciar' id='credenciar' style='display:none;'>
					</td>
				</tr>

			</table>
		</form>

		<br />
		<form name='frm_consulta' method='post' action='<?=$PHP_SELF?>'>
			<table align='center' width='700' class='formulario'>
				<caption class='titulo_tabela'>Parâmetros de Pesquisa</caption>
				<tr>
					<td>
						Linha <br />
						<select name="linha_pesquisa" id="linha_pesquisa" size="1" class="frm" style="width:170px">
							<option value="">Selecione uma Linha</option>
							<?php
								$sql = "SELECT linha,nome FROM tbl_linha WHERE fabrica = $login_fabrica ORDER BY nome";
								$res = pg_query($con,$sql);
								if(pg_num_fields($res) > 0){
									for($i = 0; $i < pg_num_rows($res); $i++){
										$linha = pg_fetch_result($res, $i, 'linha');
										$linha_desc = pg_fetch_result($res, $i, 'nome');

										$selected = ($linha == $linha_pesquisa) ? "selected" : "";
										echo "<option value='".$linha."' $selected>".$linha_desc."</option>";
									}
								}
							?>
						</select>
					</td>
					<td>
						Estado <br />
						<select name="estado_consulta" id="estado_consulta" size="1" class="frm" style="width:170px">
							<option value="">Selecione um Estado</option>
							<?php
							foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							}?>
						</select>
					</td>
					<td>
						Cidade <br />
						<select name="cidade_consulta" id="cidade_consulta" size="1" class="frm" style="width:170px">
							<option value="">Selecione uma Cidade</option>
						</select>
					</td>
				</tr>

				<tr>
					<td colspan='3' align='center'>
						<input type='hidden' name='btn_acao' value=''>
						<input type='submit' value='Pesquisar' onclick="javascript: if(document.frm_consulta.btn_acao.value == ''){document.frm_consulta.btn_acao.value = 'pesquisar';document.frm_consulta.submit();}else{alert('Aguarde submissão');}">
					</td>
				</tr>
			</table>
		</form>
		<br />
		<?php
			if($btn_acao == 'pesquisar' AND empty($msg_erro)){

				$sql = "SELECT tbl_posto.cnpj,
								tbl_posto.nome,
								tbl_ibge.cidade,
								tbl_ibge.estado,
								tbl_prospeccao.prospeccao,
								tbl_prospeccao.email,
								tbl_prospeccao.contato,
								tbl_prospeccao.linha,
								tbl_prospeccao.telefones[1][1] AS telefone,
								tbl_prospeccao.via_documento,
								tbl_prospeccao.situacao_documento,
								tbl_prospeccao.status_contrato,
								tbl_prospeccao.observacao,
								tbl_prospeccao.datas
							FROM tbl_prospeccao
							LEFT JOIN tbl_posto ON tbl_prospeccao.posto = tbl_posto.posto
							JOIN tbl_ibge ON tbl_prospeccao.cod_ibge = tbl_ibge.cod_ibge
							WHERE 1 = 1
							$cond";
				$res = pg_query($con,$sql);

				$rows = pg_num_rows($res);
				if($rows > 0){
					$caminho = "xls/relatorio-prospeccao-{$login_fabrica}-".date('Y-m-d').".xls";
					echo "<input type='button' value='Download Excel' onclick=\"window.open('{$caminho}')\"> <br><br>";
					$relatorio = "<table align='center' class='tabela'>
							<tr class='titulo_coluna' bgcolor='#596d9b'>
								<th align='center'><b><font color='#FFFFFF'>CNPJ</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Razão</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Estado</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Cidade</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Telefone</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Contato</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>E-mail</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Linhas</font></b></th>";

					$tela = $relatorio."</tr>";

					$arquivo = $relatorio."<th align='center'><b><font color='#FFFFFF'>Data de chegada de documentos </font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Via de documentos</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Situação de documentos</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de notificação de pendências</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de regularização de pendências</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de Entrega (envio) do cadastro financeiro</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de Retorno (recebimento) cadastro financeiro</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de Envio de contrato de prestação </font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de Retorno do contrato de prestação</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Status do contrato</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de Envio de documento ao cartório</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de Retorno dos documentos do cartório</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Data de envio do contrato</font></b></th>
								<th align='center'><b><font color='#FFFFFF'>Observação</font></b></th>
							</tr>";
							

					for($i = 0; $i < $rows; $i++){
						$cnpj 				= pg_fetch_result($res, $i, 'cnpj');
						$nome 				= pg_fetch_result($res, $i, 'nome');
						$cidade 			= pg_fetch_result($res, $i, 'cidade');
						$estado 			= pg_fetch_result($res, $i, 'estado');
						$email 				= pg_fetch_result($res, $i, 'email');
						$contato 			= pg_fetch_result($res, $i, 'contato');
						$linha				= pg_fetch_result($res, $i, 'linha');
						$prospeccao			= pg_fetch_result($res, $i, 'prospeccao');
						$telefone			= pg_fetch_result($res, $i, 'telefone');
						$via_documento		= pg_fetch_result($res, $i, 'via_documento');
						$situacao_documento	= pg_fetch_result($res, $i, 'situacao_documento');
						$status_contrato	= pg_fetch_result($res, $i, 'status_contrato');
						$observacao			= pg_fetch_result($res, $i, 'observacao');
						$datas				= pg_fetch_result($res, $i, 'datas');

						$via_documento = ($via_documento == "NULL" OR $via_documento == "null") ? "" : $via_documento;
						$situacao_documento = ($situacao_documento == "NULL" OR $situacao_documento == "null") ? "" : $situacao_documento;
						$status_contrato = ($status_contrato == "NULL" OR $status_contrato == "null") ? "" : $status_contrato;
						$observacao = ($observacao == "NULL" OR $observacao == "null") ? "" : $observacao;

						$datas = str_replace('},','};',$datas);
						$datas = str_replace('}','',$datas);
						$datas = str_replace('{','',$datas);

						$xls_datas = explode(';',$datas);

						foreach($xls_datas AS $data){
							list($key,$valor) = explode(",",$data);
							$$key = $valor;
							$$key = ($$key == "NULL" OR $$key == "null") ? "" : $$key;
						}


						$relatorio = "<tr class='prospeccao' rel='$prospeccao'>";
						$relatorio .= "<td>$cnpj&nbsp;</td>";
						$relatorio .= "<td>$nome</td>";
						$relatorio .= "<td>$estado</td>";
						$relatorio .= "<td>$cidade</td>";
						$relatorio .= "<td>$telefone</td>";
						$relatorio .= "<td>$contato</td>";
						$relatorio .= "<td>$email</td>";
						$relatorio .= "<td align='left'>";
							$linha = str_replace('{','', $linha);
							$linha = str_replace('}','', $linha);
							$sqlLinha = "SELECT nome FROM tbl_linha WHERE fabrica = $login_fabrica AND linha IN($linha)";
							$resLinha = pg_query($con,$sqlLinha);
							for($j = 0; $j < pg_num_rows($resLinha); $j++){
								$relatorio .= pg_fetch_result($resLinha, $j, 'nome')."<br>";
							}
						$relatorio .= "</td>";

						$arquivo .= $relatorio."<td>".$dt_chegada."</td>
						<td>".$via_documento."</td>
						<td>".$situacao_documento."</td>
						<td>".$dt_notificacao."</td>
						<td>".$dt_regularizacao."</td>
						<td>".$dt_envio_financeiro."</td>
						<td>".$dt_retorno_financeiro."</td>
						<td>".$dt_envio_prestacao."</td>
						<td>".$dt_retorno_prestacao."</td>
						<td>".$status_contrato."</td>
						<td>".$dt_envio_cartorio."</td>
						<td>".$dt_retorno_cartorio."</td>
						<td>".$dt_envio_contrato."</td>
						<td>".$observacao."</td>
						</tr>";

						$tela .= $relatorio."</tr>";

					}
					$relatorio = "</table>";
					$tela .= $relatorio;
					$arquivo .= $relatorio;

					$fp = fopen($caminho, "w");
					fwrite($fp, $arquivo);
					fclose($fp);

					echo $tela;

				}
			}?>
    </div>

    <div id='tabs-2'>
    	<form name='frm_pendencias' method='post' action='<?=$PHP_SELF?>'>
    		<table align='center' width='700' class='formulario'>
    			<caption class='titulo_tabela'>Cadastro</caption>

    			<tr>
    				<td>
    					Código Posto<br />
						<input type='text' name='posto_codigo_pendencia_cadastro' size='12' value='<?=$posto_codigo?>' class='frm' />&nbsp;
						<img src='../imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick="javascript: pesquisaPosto(document.frm_pendencias.posto_codigo_pendencia_cadastro, 'codigo'); " />
    				</td>

    				<td>
    					Nome Posto<br />
						<input type='text' name='posto_nome_pendencia_cadastro' size='30' value='<?=$posto_nome?>' class='frm' />&nbsp;
						<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_pendencias.posto_nome_pendencia_cadastro, 'nome'); " style='cursor: pointer;' />
    				</td>
    			</tr>

    			<tr>
					<td colspan="2" style="padding-left:0px;" align="center">
						<input type='hidden' name='posto_pendencia_cadastro' id='posto_pendencia_cadastro' value='<?=$posto_pendencia_cadastro?>'>
						<input type="button" onclick="" style="cursor:pointer;" value="Pesquisar" id='cad_pendencia' />
					</td>
				</tr>    			
    		</table>

			<table align='center' width='700' class='formulario' id='pendencia_cadastro' style='display:none;'>
				<tr>
					<td>
						Estado <br />
						<span id='estado_pendencia_cadastro'></span>
					</td>
					<td>
						Cidade <br />
						<span id='cidade_pendencia_cadastro'></span>
					</td>
				</tr> 

				<tr>
					<td>
						Linhas <br />
						<span id='linha_pendencia_cadastro'></span>			
					</td>
					<td>
						Telefone <br />
						<span id='fone_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>						
						Contato <br />
						<span id='contato_pendencia_cadastro'></span>
					</td>
					<td>
						E-mail <br />
						<span id='email_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						CNPJ <br />
						<span id='cnpj_pendencia_cadastro'></span>
					</td>
					<td>
						Razão Social <br />
						<span id='razao_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Data de chegada de documentos <br />
						<span id='dt_chegada_pendencia_cadastro'></span>
					</td>
					<td>
						Via de documentos <br>
						<span id='via_documento_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Situação de documentos <br />
						<span id='situacao_documento_pendencia_cadastro'></span>
					</td>
					<td>
						Data de notificação de pendências <br />
						<span id='dt_notificacao_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Data de regularização de pendências <br />
						<span id='dt_regularizacao_pendencia_cadastro'></span>
					</td>
					<td>
						Data de Entrega (envio) do cadastro financeiro <br />
						<span id='dt_envio_financeiro_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Data de Retorno (recebimento) cadastro financeiro <br />
						<span id='dt_retorno_financeiro_pendencia_cadastro'></span>
					</td>
					<td>
						Data de Envio de contrato de prestação <br />
						<span id='dt_envio_prestacao_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Data de Retorno do contrato de prestação <br />
						<span id='dt_retorno_prestacao_pendencia_cadastro'></span>
					</td>
					<td>
						Status do contrato <br />
						<span id='status_contrato_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Data de Envio de documento ao cartório <br />
						<span id='dt_envio_cartorio_pendencia_cadastro'></span>
					</td>
					<td>
						Data de Retorno dos documentos do cartório <br />
						<span id='dt_retorno_cartorio_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td colspan='2'>
						Data de envio do contrato <br />
						<span id='dt_envio_contrato_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td colspan='2'>
						Observação <br />
						<span id='obs_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Ordens de Serviço abertas <br />
						<span id='os_pendencia_cadastro'></span>
					</td>
					<td>
						MO a pagar ao posto <br />
						<span id='mo_pendencia_cadastro'></span>
					</td>
				</tr>

				<tr>
					<td>
						Produtos a devolver <br />
						<input type='text' name='produtos_devolver' id='produtos_devolver' size='35' value='<?=$produtos?>' class='frm'>
					</td>
					<td>
						Pendencias Financeiras <br />
						<input type='text' name='pendencias' id='pendencias' size='35' value='<?=$pendencias?>' class='frm'>
					</td>
				</tr>

				<tr>
					<td colspan="2" style="padding-left:0px;" align="center">
						<input type='hidden' name='btn_acao' value=''>
						<input type='hidden' name='prospeccao_pendencia_cadastro' id='prospeccao_pendencia_cadastro' value='<?=$prospeccao_pendencia?>'>
						<input type="button" onclick="javascript: if ( document.frm_pendencias.btn_acao.value == '' ) { document.frm_pendencias.btn_acao.value='gravar_pendencia'; document.frm_pendencias.submit() ; } else { alert ('Aguarde submissão'); }" style="cursor:pointer;" value="Gravar Pendências" />

						<input type='button' value='Descredenciar' id='btn_descredenciar' onclick='descredenciaPosto();'>
						<span id='excel'></span>
					</td>
				</tr>
			</table>    		
    	</form>
    </div>
</div>



<?php
	
	include "rodape.php";

?>
