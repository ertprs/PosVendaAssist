<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$btn_acao = $_POST["btn_acao"];
if($btn_acao=="atualizar"){

	$cnpj                    = $_POST["cnpj"];
	if (strlen($cnpj) > 0 and strlen($msg_erro) == 0){$cnpj = "'".$cnpj."'";
	}elseif(strlen($msg_erro) == 0){$msg_erro = '<BR>Digite o cnpj.';}
	
	$cidade                    = $_POST["cidade"];
	if (strlen($cidade) > 0){$cidade = "'".$cidade."'";
	}elseif(strlen($msg_erro) == 0){$msg_erro = '<BR>Digite a cidade.';}
	
	$estado                    = $_POST["estado"];
	if (strlen($estado) > 0){$estado = "'".$estado."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro = '<BR>Digite o estado.';}	
	$ie                      = $_POST["ie"];
	if (strlen($ie) > 0 and strlen($msg_erro) == 0){$ie = "'".$ie."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro = '<BR>Digite a Inscri��o Estadual.';}
	
	$codigo                  = $_POST["codigo"];
	if (strlen($codigo) > 0 and strlen($msg_erro) == 0){$codigo = "'".$codigo."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro = '<BR>Digite o C�digo do Posto.';}

	$nome                    = $_POST["nome"];
	if (strlen($nome) > 0 and strlen($msg_erro) == 0){$nome = "'".$nome."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro = '<BR>Digite o endere�o.';}

	$endereco            = $_POST["endereco"];
	if (strlen($endereco) > 0 and strlen($msg_erro) == 0){$endereco = "'".$endereco."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro = '<BR>Digite o endere�o.';}
	
	$numero              = $_POST["numero"];
	if (strlen($numero) > 0 and strlen($msg_erro) == 0){$numero = "'".$numero."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro .= '<BR>Digite o n�mero.';}
	
	$complemento         = $_POST["complemento"];
	if (strlen($complemento) > 0 ){$complemento = "'".$complemento."'";}else{$complemento = 'null';}
	
	$bairro              = $_POST["bairro"];
	if (strlen($bairro) > 0){$bairro = "'".$bairro."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro .= '<BR>Digite o bairro.';}
	
	$cep                 = $_POST["cep"];
	if (strlen($cep) > 0){$cep = "'".$cep."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro .= '<BR>Digite o CEP.';}
	
	$email               = $_POST["email"];
	if (strlen($email) > 0){$email = "'".$email."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro .= '<BR>Digite o e-mail.';}
	
	$fone                = $_POST["fone"];
	if (strlen($fone) > 0){$fone = "'".$fone."'";
	}elseif(strlen($msg_erro) == 0){	$msg_erro .= '<BR>Digite o telefone.';}
	
	$fax                 = $_POST["fax"];
	if (strlen($fax) > 0){$fax = "'".$fax."'";}else{$fax = 'null';}
	
	$contato             = $_POST["contato"];
	if (strlen($contato) > 0){$contato = "'".$contato."'";}else{$contato = 'null';}
	
	$capital_interior    = $_POST["capital_interior"];
	if (strlen($capital_interior) > 0){$capital_interior = "'".$capital_interior."'";
	}else{$capital_interior = 'null';}
	
	$nome_fantasia       = $_POST["nome_fantasia"];
	if (strlen($nome_fantasia) > 0){$nome_fantasia = "'".$nome_fantasia."'";
	}else{$nome_fantasia = 'null';}
	
	$obs                 = $_POST["obs"];
	if (strlen($obs) > 0){$obs = "'".$obs."'";}else{$obs = 'null';}
	/*
	$banco               = $_POST["banco"];
	if (strlen($banco) > 0){$banco = "'".$banco."'";
	}else{	$msg_erro .= '<BR>Escolha o banco.';}
	
	$agencia             = $_POST["agencia"];
	if (strlen($agencia) > 0){$agencia = "'".$agencia."'";
	}else{	$msg_erro .= '<BR>Digite a ag�ncia.';}
	
	$conta               = $_POST["conta"];
	if (strlen($conta) > 0){$conta = "'".$conta."'";
	}else{	$msg_erro .= '<BR>Digite a conta.';}
	
	$favorecido_conta    = $_POST["favorecido_conta"];
	if (strlen($favorecido_conta) > 0){$favorecido_conta = "'".$favorecido_conta."'";
	}else{$favorecido_conta = 'null';}
	
	$cpf_conta           = $_POST["cpf_conta"];
	if (strlen($cpf_conta) > 0){$cpf_conta = "'".$cpf_conta."'";}else{$cpf_conta = 'null';}
	
	$tipo_conta          = $_POST["tipo_conta"];
	if (strlen($tipo_conta) > 0){$tipo_conta = "'".$tipo_conta."'";}else{$tipo_conta = 'null';}
	
	$obs_conta           = $_POST["obs_conta"];
	if (strlen($obs_conta) > 0){$obs_conta = "'".$obs_conta."'";}else{$obs_conta = 'null';}
*/
/*informacoes questionario*/
	
	$linhas_eletro          = $_POST["linhas_eletro"];
	if (strlen($linhas_eletro) > 0){$linhas_eletro = "'".$linhas_eletro."'";}else{$linhas_eletro ="'f'";}
	
	$linhas_dw              = $_POST["linhas_dw"];
	if (strlen($linhas_dw) > 0){$linhas_dw = "'".$linhas_dw."'";}else{$linhas_dw ="'f'";}
	
	$linhas_ferramenta      = $_POST["linhas_ferramenta"];
	if (strlen($linhas_ferramenta) > 0){$linhas_ferramenta = "'".$linhas_ferramenta."'";}else{$linhas_ferramenta ="'f'";}
	
	$linhas_lavadora        = $_POST["linhas_lavadora"];
	if (strlen($linhas_lavadora) > 0){$linhas_lavadora = "'".$linhas_lavadora."'";}else{$linhas_lavadora ="'f'";}
	
	$linhas_compressores    = $_POST["linhas_compressores"];
	if (strlen($linhas_compressores) > 0){$linhas_compressores = "'".$linhas_compressores."'";}else{$linhas_compressores ="'f'";}
	
	
//Pergunta 2 tecnicos treinados S ou N, quais
	$treinados    = $_POST["treinados"];
	$nome_tecnicos =$_POST["nome_tecnicos"];
	if(strlen($treinados)==0 and strlen($msg_erro) == 0){$msg_erro.="Responda a pergunta 2.<BR>";}
	if($treinados=='t' and strlen($nome_tecnicos)==0 and strlen($msg_erro) == 0){
		$msg_erro.="Informe o nome dos t�cnicos treinados.<BR>";
	}
	
	if($treinados=='f' and strlen($nome_tecnicos)>0 and strlen($msg_erro) == 0){
		$msg_erro.="Marque como SIM na pergunta 2.<BR>";
	}
	
	if($treinados=='f' and strlen($nome_tecnicos)==0){
		$nome_tecnicos=="null";
	}
	if (strlen($nome_tecnicos) > 0){$nome_tecnicos = "'".$nome_tecnicos."'";}else{$nome_tecnicos ="null";}
//Pergunta 2 tecnicos treinados S ou N, quais


//pertunta 3 atende outros fabricantes?
//autorizado de outros eletronicos
	$outros_eletronicos_0 = trim($_POST["outros_eletronicos_0"]);
	$outros_eletronicos_1 = trim($_POST["outros_eletronicos_1"]);
	$outros_eletronicos_2 = trim($_POST["outros_eletronicos_2"]);
	$fabricantes="";
	if(strlen($outros_eletronicos_0)>0) $fabricantes .= " $outros_eletronicos_0 ";
	if(strlen($outros_eletronicos_1)>0) $fabricantes .= " $outros_eletronicos_1 ";
	if(strlen($outros_eletronicos_2)>0) $fabricantes .= " $outros_eletronicos_2 ";
	//autorizado de outros eletronicos
	
	//autorizado de outros ferramentas
	$outros_ferramentas_0 = trim($_POST["outros_ferramentas_0"]);
	$outros_ferramentas_1 = trim($_POST["outros_ferramentas_1"]);
	$outros_ferramentas_2 = trim($_POST["outros_ferramentas_2"]);
	
	if(strlen($outros_ferramentas_0)>0) $fabricantes .= " $outros_ferramentas_0 ";
	if(strlen($outros_ferramentas_1)>0) $fabricantes .= " $outros_ferramentas_1 ";
	if(strlen($outros_ferramentas_2)>0) $fabricantes .= " $outros_ferramentas_2 ";
	//autorizado de outros ferramentas
	
	//autorizado de outros compressores
	$outros_compressores_0 = trim($_POST["outros_compressores_0"]);
	$outros_compressores_1 = trim($_POST["outros_compressores_1"]);
	$outros_compressores_2 = trim($_POST["outros_compressores_2"]);
	
	if(strlen($outros_compressores_0)>0) $fabricantes .= " $outros_compressores_0 ";
	if(strlen($outros_compressores_1)>0) $fabricantes .= " $outros_compressores_1 ";
	if(strlen($outros_compressores_2)>0) $fabricantes .= " $outros_compressores_2 ";
	//autorizado de outros compressores
	
	//autorizado de outros lavadoras
	$outros_lavadoras_0 = trim($_POST["outros_lavadoras_0"]);
	$outros_lavadoras_1 = trim($_POST["outros_lavadoras_1"]);
	$outros_lavadoras_2 = trim($_POST["outros_lavadoras_2"]);
	
	if(strlen($outros_lavadoras_0)>0) $fabricantes .= " $outros_lavadoras_0 ";
	if(strlen($outros_lavadoras_1)>0) $fabricantes .= " $outros_lavadoras_1 ";
	if(strlen($outros_lavadoras_2)>0) $fabricantes .= " $outros_lavadoras_2 ";
	//autorizado de outros lavadoras
	
	if(strlen($fabricantes)==0){ $fabricantes="null";}else{$fabricantes="'$fabricantes'";}

	//pergunta 4, fez treinamento para outras fabricas? quais
	$treinado_outros = $_POST["treinado_outros"]; 
	$treinado_outros_quais = trim($_POST["treinado_outros_quais"]); 	
	if(strlen($treinado_outros)==0 and strlen($msg_erro) == 0){$msg_erro.="Responda a pergunta 4.<BR>";}
	if($treinado_outros == 'f' AND strlen($treinado_outros_quais)>0 and strlen($msg_erro) == 0){$msg_erro.="Marque a como SIM a pergunta 4.<BR>";}
	if($treinado_outros == 't' AND strlen($treinado_outros_quais)==0 and strlen($msg_erro) == 0){$msg_erro.="Informe a f�brica que seu posto recebeu treinamento.<BR>";}
	if($treinado_outros == 'f' AND strlen($treinado_outros_quais)==0 and strlen($msg_erro) == 0){$treinado_outros_quais="null";}
	if(strlen($treinado_outros_quais)==0){$treinado_outros_quais="null";}else{$treinado_outros_quais="'$treinado_outros_quais'";}
	if ($treinado_outros=='f'){$treinado_outros = "'f'";}else{$treinado_outros ="'t'";}
	
	//pergunta5. Sua empresa � autorizada de alguma marca de ferramentas pneum�ticas?
$treinado_pneu = $_POST['treinado_pneu'];
$treinado_pneu_quais = $_POST['treinado_pneu_quais'];
if(strlen($treinado_pneu)==0 and strlen($msg_erro) == 0){$msg_erro.="Responda a pergunta 5.<BR>";}
if($treinado_pneu == 'f' AND strlen($treinado_pneu_quais)>0 and strlen($msg_erro) == 0){$msg_erro.="Marque a como SIM a pergunta 5.<BR>";}
if($treinado_pneu == 't' AND strlen($treinado_pneu_quais)==0 and strlen($msg_erro) == 0){$msg_erro.="Informe a f�brica que seu posto � autorizado.<BR>";}
if(strlen($treinado_pneu_quais)==0){$treinado_pneu_quais="null";}else{$treinado_pneu_quais="'$treinado_pneu_quais'";}
if ($treinado_pneu=='f'){$treinado_pneu = "'f'";}else{$treinado_pneu ="'t'";}



//pergunta6. Alguns de seus t�cnicos j� participaram de treinamento de ferramentas pneum�ticas?
$treinado_pneu_outros= $_POST['treinado_pneu_outros'];
$treinado_pneu_outros_quais = $_POST['treinado_pneu_outros_quais'];
if(strlen($treinado_pneu_outros)==0 and strlen($msg_erro) == 0){$msg_erro.="Responda a pergunta 6.<BR>";}
if($treinado_pneu_outros == 'f' AND strlen($treinado_pneu_outros_quais)>0 and strlen($msg_erro) == 0){$msg_erro.="Marque a como SIM a pergunta 5.<BR>";}
if($treinado_pneu_outros == 't' AND strlen($treinado_pneu_outros_quais)==0 and strlen($msg_erro) == 0){$msg_erro.="Informe a f�brica que seu posto recebeu treinamento.<BR>";}
if(strlen($treinado_pneu_outros_quais)==0){$treinado_pneu_outros_quais="null";}else{$treinado_pneu_outros_quais="'$treinado_pneu_outros_quais'";}
if ($treinado_pneu_outros=='f'){$treinado_pneu_outros = "'f'";}else{$treinado_pneu_outros ="'t'";}


	if(strlen($msg_erro)==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");
		$sql = "INSERT INTO tbl_black_questionario(
								posto                             ,
								eletro                            ,
								dewalt                            ,
								black                             ,
								lavadora                          ,
								compressor                        ,
								nome_tecnico                      ,
								fabricantes                       ,
								treinamento                       ,
								treinamento_fabrica               ,
								pneumatico                        ,
								pneumatico_fabrica                ,
								pneumatico_treinamento            ,
								pneumatico_treinamento_fabrica 
							)values(
							$login_posto                ,
							$linhas_eletro              ,
							$linhas_dw                  ,
							$linhas_ferramenta          ,
							$linhas_lavadora            ,
							$linhas_compressores        ,
							$nome_tecnicos              ,
							$fabricantes                ,
							$treinado_outros            ,
							$treinado_outros_quais      ,
							$treinado_pneu              ,
							$treinado_pneu_quais        ,
							$treinado_pneu_outros       ,
							$treinado_pneu_outros_quais
							)";
			$res=pg_exec($con, $sql);
		//echo nl2br($sql);
		if (strlen (pg_errormessage($con)) > 0 ) {
					$msg_erro.= pg_errormessage($con);
		}

	}


	/*atualiza dados posto_fabrica*/
	if(strlen($msg_erro)==0){

		$sql1="INSERT INTO black_tbl_posto(
											codigo_posto          ,
											cnpj                  ,
											inscricao_estadual    ,
											razao_social          ,
											fantasia              ,
											endereco              ,
											numero                ,
											complemento           ,
											bairro                ,
											cidade                ,
											estado                ,
											capital_interior      ,
											cep                   ,
											fone                  ,
											fax                   ,
											contato               ,
											email                 ,
											observacao
										)values(
											'$login_posto'        ,
											$cnpj             ,
											$ie                 ,
											$nome               ,
											$nome_fantasia      ,
											$endereco           ,
											$numero             ,
											$complemento        ,
											$bairro             ,
											$cidade             ,
											$estado             ,
											$capital_interior   ,
											$cep                ,
											$fone               ,
											$fax                ,
											$contato            ,
											$email              ,
											$obs)";
		//echo nl2br($sql1);
		$res = pg_exec($con, $sql1);
	
		if (strlen (pg_errormessage($con)) > 0 ) {
					$msg_erro.= pg_errormessage($con);
		}
	}/*atualiza dados posto_fabrica*/

/*
,
	banco               = $banco                ,
	agencia             = $agencia              ,
	conta               = $conta                ,
	nomebanco           = (select nome from tbl_banco where codigo=$banco limit 1),
	favorecido_conta    = $favorecido_conta     , 
	cpf_conta           = $cpf_conta            ,
	tipo_conta          = $tipo_conta           ,
	obs_conta           = $obs_conta            ,

*/



	/*atualiza dados posto*/
	if(strlen($msg_erro)==0){
		$sql2="UPDATE tbl_posto set
						endereco         = $endereco          ,
						numero           = $numero            ,
						complemento      = $complemento       ,
						bairro           = $bairro            ,
						cep              = $cep               ,
						email            = $email             ,
						fone             = $fone              ,
						fax              = $fax               ,
						contato          = $contato           ,
						capital_interior = $capital_interior  ,
						nome_fantasia    =  $nome_fantasia
				WHERE   tbl_posto.posto   = $login_posto";
		//echo nl2br($sql2);
		//$res = pg_exec($con, $sql2);
		if (strlen (pg_errormessage($con)) > 0 ) {
					$msg_erro.= pg_errormessage($con);
		}
	}
/*atualiza dados posto*/


	if (strlen ($msg_erro) == 0) {
//ENVIA EMAIL PARA POSTO PRA CONFIRMA��O
//ENVIA EMAIL PARA POSTO PRA CONFIRMA��O

			$email_origem  = "helpdesk@telecontrol.com.br";
			$email_destino = "helpdesk@telecontrol.com.br";
			$assunto       = "Informacoes atualizadas black";
			$corpo.="<br>posto $login_posto atualizou info na black.\n\n";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br><br>Telecontrol\n";
			$corpo.="<br>www.telecontrol.com.br\n";

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
//$corpo = $body_top.$corpo;

		/*	if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
				//$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
			}else{
				$msg_erro .= "N�o foi poss�vel enviar o email. Por favor entre em contato com a TELECONTROL.";
			}*/
//FIM DO ENVIAR EMAIL
				


		$res = @pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: login.php");
		exit;
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}//se clicar no botao atualizar

if (strlen ($msg_erro)> 0) {
	$cnpj                    = $_POST["cnpj"];
	$ie                      = $_POST["ie"];
	$codigo                  = $_POST["codigo"];
	$nome                    = $_POST["nome"];
	$endereco                = $_POST["endereco"];
	$numero                  = $_POST["numero"];
	$complemento             = $_POST["complemento"];
	$bairro                  = $_POST["bairro"];
	$cep                     = $_POST["cep"];
	$email                   = $_POST["email"];
	$fone                    = $_POST["fone"];
	$fax                     = $_POST["fax"];
	$contato                 = $_POST["contato"];
	$capital_interior        = $_POST["capital_interior"];
	$nome_fantasia           = $_POST["nome_fantasia"];
	$obs                     = $_POST["obs"];
	/*$banco                   = $_POST["banco"];
	$agencia                 = $_POST["agencia"];
	$conta                   = $_POST["conta"];
	$favorecido_conta        = $_POST["favorecido_conta"];
	$cpf_conta               = $_POST["cpf_conta"];
	$tipo_conta              = $_POST["tipo_conta"];
	$obs_conta               = $_POST["obs_conta"];*/
	$linhas_eletro           = $_POST["linhas_eletro"];
	$linhas_dw               = $_POST["linhas_dw"];
	$linhas_ferramenta       = $_POST["linhas_ferramenta"];
	$linhas_lavadora         = $_POST["linhas_lavadora"];
	$linhas_compressores     = $_POST["linhas_compressores"];
	$treinados               = $_POST["treinados"];
	$nome_tecnicos           = $_POST["nome_tecnicos"];
	$outros_eletronicos_0    = trim($_POST["outros_eletronicos_0"]);
	$outros_eletronicos_1    = trim($_POST["outros_eletronicos_1"]);
	$outros_eletronicos_2    = trim($_POST["outros_eletronicos_2"]);
	$outros_ferramentas_0    = trim($_POST["outros_ferramentas_0"]);
	$outros_ferramentas_1    = trim($_POST["outros_ferramentas_1"]);
	$outros_ferramentas_2    = trim($_POST["outros_ferramentas_2"]);
	$outros_compressores_0   = trim($_POST["outros_compressores_0"]);
	$outros_compressores_1   = trim($_POST["outros_compressores_1"]);
	$outros_compressores_2   = trim($_POST["outros_compressores_2"]);
	$outros_lavadoras_0      = trim($_POST["outros_lavadoras_0"]);
	$outros_lavadoras_1      = trim($_POST["outros_lavadoras_1"]);
	$outros_lavadoras_2      = trim($_POST["outros_lavadoras_2"]);
	$treinado_outros         = $_POST["treinado_outros"]; 
	$treinado_outros_quais   = $_POST["treinado_outros_quais"]; 
	$treinado_pneu           = $_POST['treinado_pneu'];
	$treinado_pneu_quais     = $_POST['treinado_pneu_quais'];
	$treinado_pneu_outros    = $_POST['treinado_pneu_outros'];
	$treinado_pneu_outros_quais     = $_POST['treinado_pneu_outros_quais'];



}
#-------------------- Pesquisa Posto -----------------
	$sql = "SELECT  tbl_posto_fabrica.obs                 ,
					tbl_posto_fabrica.posto               ,
					tbl_posto_fabrica.codigo_posto        ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.endereco                    ,
					tbl_posto.numero                      ,
					tbl_posto.complemento                 ,
					tbl_posto.bairro                      ,
					tbl_posto.cep                         ,
					tbl_posto.cidade                      ,
					tbl_posto.estado                      ,
					tbl_posto.email                       ,
					tbl_posto.fone                        ,
					tbl_posto.fax                         ,
					tbl_posto.contato                     ,
					tbl_posto.capital_interior            ,
					tbl_posto.nome_fantasia               ,
					to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $login_posto ";
	$res = pg_exec ($con,$sql);

/*

					
					tbl_posto_fabrica.banco               ,
					tbl_posto_fabrica.agencia             ,
					tbl_posto_fabrica.conta               ,
					tbl_posto_fabrica.nomebanco           ,
					tbl_posto_fabrica.favorecido_conta    ,
					tbl_posto_fabrica.cpf_conta           ,
					tbl_posto_fabrica.tipo_conta          ,
					tbl_posto_fabrica.obs_conta           ,

*/



if (@pg_numrows ($res) > 0) {
	$codigo           = trim(pg_result($res,0,codigo_posto));
	$nome             = trim(pg_result($res,0,nome));
	$cnpj             = trim(pg_result($res,0,cnpj));
	$ie               = trim(pg_result($res,0,ie));
	$cidade              = trim(pg_result($res,0,cidade));
	$estado              = trim(pg_result($res,0,estado));
//estes dados nao sao atualizados
	if (strlen($cnpj) == 14) {
		$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
	}
	if (strlen($cnpj) == 11) {
		$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
	}

	$endereco            = trim(pg_result($res,0,endereco));
	$endereco            = str_replace("\"","",$endereco);
	$numero              = trim(pg_result($res,0,numero));
	$complemento         = trim(pg_result($res,0,complemento));
	$bairro              = trim(pg_result($res,0,bairro));
	$cep                 = trim(pg_result($res,0,cep));
	$email               = trim(pg_result($res,0,email));
	$fone                = trim(pg_result($res,0,fone));
	$fax                 = trim(pg_result($res,0,fax));
	$contato             = trim(pg_result($res,0,contato));
	$capital_interior    = trim(pg_result($res,0,capital_interior));
	$nome_fantasia       = trim(pg_result($res,0,nome_fantasia));
	$obs                 = trim(pg_result($res,0,obs));
	/*$banco               = trim(pg_result($res,0,banco));
	$agencia             = trim(pg_result($res,0,agencia));
	$conta               = trim(pg_result($res,0,conta));
	$favorecido_conta    = trim(pg_result($res,0,favorecido_conta));
	$cpf_conta           = trim(pg_result($res,0,cpf_conta));
	$tipo_conta          = trim(pg_result($res,0,tipo_conta));
	$obs_conta           = trim(pg_result($res,0,obs_conta));*/
	$data_alteracao	     = trim(pg_result($res,0,data_alteracao));
}

$title = "Atualizar Dados Cadastrais";
$layout_menu = "cadastro";

include 'cabecalho.php';

?>


<script type="text/javascript" charset="utf-8" src="js/jquery.js"></script>
<script type="text/javascript" charset="utf-8" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" charset="utf-8">
    $(function() {
        $("#cnpj_revenda").numeric();
    });


    function mascara_cnpj(campo, event) {


        var cnpj  = campo.value.length;
        var tecla = event.keyCode ? event.keyCode : event.which ? event.which : 
                                                                    event.charCode;


        if (tecla != 8 && tecla != 46) {


            if (cnpj == 2 || cnpj == 6) campo.value += '.';
            if (cnpj == 10) campo.value += '/';
            if (cnpj == 15) campo.value += '-';


        }


    }

    function formata_cpf_cnpj(campo, tipo) {


        var valor = campo.value;


        valor = valor.replace(".","");
        valor = valor.replace(".","");
        valor = valor.replace("-","");


        if (tipo == 2) {
            valor = valor.replace("/","");
        }


        if (valor.length == 11 && tipo == 1) {


            campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF


        } else if (valor.length == 14 && tipo == 2) {


            campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ


        }


    }
</script>

<style type="text/css">

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.borda{
    border-collapse: collapse;
    border-color:#596d9b;
	
	border-style: solid;
	border-bottom-width: 1px;
	border-top-width: 0px;
	border-right-width: 1px;
	border-left-width: 1px;

}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
	text-transform:capitalize;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
</style>


<p>
<table class='texto_avulso' align='center' border='0'>
<tr>
	<td align='center'>
		
		<? echo "Por favor atualize as informa��es do seu posto autorizado!";?>
	</td>
</tr>
</table>
<p>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table class='msg_erro' align='center' border='0' width='700px'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo $msg_erro;
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<!-- INFORMA�OES CADASTRAIS -->
<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">

<!-- CNPJ/CPF - I.E. - FONE - FAX - CONTATO - C�DIGO - RAZ�O SOCIAL -->
<table class='formulario' width='700' align='center' border='0' cellpadding="3" cellspacing="1">
	<tr>
		<td colspan="6" class="titulo_tabela" align='center'>
			<? echo "Informa��es Cadastrais";?>
		</td>
	</tr>
	<tr class='subtitulo'>
		<td><? echo "CNPJ/CPF";?></td>
		<td><? echo "I.E.";?></td>
		<td><? echo "Fone";?></td>
		<td><? echo "Fax";?></td>
	</tr>
	<tr >
		<td>
		<input type="text" name="cnpj" id="cnpj_revenda" onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" class="frm" size="20" maxlength="18" value="<? echo $cnpj ?>" />
		</td>
		<td><input type="text" name="ie" style="width:100%" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" style="width:100%" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" style="width:100%" value="<? echo $fax ?>"></td>
		
	</tr>
	
	<tr class="subtitulo">
		<td colspan="100%"><? echo "Contato";?></td>
	</tr>
	
	<tr>
		<td colspan="100%"><input type="text" name="contato" style="width:100%" value="<? echo $contato ?>" style="width:100px"></td>
	</tr>

	<tr  class='subtitulo'>
		<td colspan="1"><? echo "C�digo";?></td>
		<td colspan="4"><? echo "Raz�o Social";?></td>
	</tr>
	<tr>
		<td colspan="1"><input type="text" disabled="" value="<? echo $codigo ?>" style="width:100%"></td>
		<input type="hidden" name="codigo" value="<? echo $codigo ?>">
		<td colspan="4"><input type="text" disabled="" value="<? echo $nome ?>" style="width:100%"></td>
		<input type="hidden" name="nome" value="<? echo $nome ?>">
	</tr>
</table>

<!-- ENDERE�O - N�MERO - COMPLEMENTO - BAIRRO - CEP - CIDADE - ESTADO -->
<table class="formulario" align='center' border='0' cellpadding="3" width='700px' cellspacing="1">
	<tr class='subtitulo'>
		<td width='45%'><? echo "Endere�o";?></td>
		<td width="100px"><? echo "N�mero";?></td>
		<td colspan="2"><? echo "Complemento";?></td>
	</tr>
	<tr>
		<td><input type="text" name="endereco" style="width:100%" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" style="width:100%" value="<? echo $numero ?>"></td>
		<td colspan="2" class='borda'><input type="text" name="complemento" style="width:100%" value="<? echo $complemento ?>"></td>
	</tr>
	<tr class='subtitulo'>
		<td><?  echo "Bairro";?></td>
		<td><? echo "Cep";?></td>
		<td><? echo "Cidade";?></td>
		<td style="width:20px"><? echo "Estado";?></td>
	</tr>
	<tr>
		<td><input type="text" name="bairro" style="width:100%"  value="<? echo $bairro ?>"></td>
		<td><input type="text" name="cep" style="width:100%"  value="<? echo $cep ?>"></td>
		<td><input type="text" name="cidade" style="width:100%"  value="<? echo $cidade ?>"></td>
		<td><input type="text" name="estado" style="width:100%"  value="<? echo $estado ?>"></td>
	</tr>
	
</table>
<!-- NOME FANTASIA - EMAIL - CAPITAL/INTERIOR -->
<table class="formulario" align='center' border='0' cellpadding="3" cellspacing="1" width='700px'>
	<tr class='subtitulo'>
		<td width='45%'><? echo "Nome Fantasia";?></td>
		<td><? echo "E-mail";?></td>
		<td width="30px"><? echo "Capital/Interior";?></td>
		
	</tr>
	<tr>
		<td  width='248px'>
			<input type="text" name="nome_fantasia" style="width:100%" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" style="width:100%" value="<? echo $email ?>">
		</td>
		<td>
			<select class='frm' style="width:100%" name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? if($sistema_lingua) echo "CAPITAL";else echo "Capital";?></option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? if($sistema_lingua) echo "PROVINCIA";else echo "Interior";?></option>
			</select>
		</td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="3"><? echo "Observa��es";?></td>
	</tr>
	<tr>
		<td colspan="3"><textarea name="obs" style="width:100%"><? echo $obs ?></textarea></td>
	</tr>
</table>

<p>
<!--  Cobranca -->
<? if($login_fabrica<>1){ ?>
<table class="formulario" align='center' border='0' cellpadding="3" cellspacing="1" width='700px'>
	<tr class='titulo_tabela'>
		<td colspan='4' class="menu_top" align='center'>
			<font color='#36425C'><? echo "Informa��es Para Cobran�a";?></td>
	</tr>
	<tr>
		<td colspan="2"><? echo "Endere�o";?></td>
		<td><? echo "N�mero";?></td>
		<td><? echo "Complemento";?></td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;<? echo $cobranca_endereco ?></td>
		<td>&nbsp;<? echo $cobranca_numero ?></td>
		<td>&nbsp;<? echo $cobranca_complemento ?></td>
	</tr>
	<tr>
		<td><?  echo "Bairro";?></td>
		<td><?  echo "CEP";?></td>
		<td><?  echo "Cidade";?></td>
		<td><?  echo "UF";?></td>
	</tr>
	<tr>
		<td>&nbsp;<? echo $cobranca_bairro ?></td>
		<td>&nbsp;<? echo $cobranca_cep ?></td>
		<td>&nbsp;<? echo $cobranca_cidade ?></td>
		<td>&nbsp;<? echo $cobranca_estado ?></td>
	</tr>
</table>
<? } ?>



<p>
 
<table class="formulario" align='center' border='0' cellpadding="3" cellspacing="1" width='700px'>
	<tr>
		<td colspan="4" class="titulo_tabela" align='center'>
			<? echo "Question�rio";?>
		</td>
	</tr>
	<tr class='subtitulo' >
		<td colspan="4">1. Marque quais as linhas de produto Black & Decker sua empresa atende:</td>
		
	</tr>
	<tr align='left' >
		<td><input type='checkbox' name='linhas_eletro' value='t' class='frm' <? if($linhas_eletro=='t')echo "checked"; ?>>Eletro</td>
		<td><input type='checkbox' name='linhas_dw' value='t' class='frm' <? if($linhas_dw=='t')echo "checked"; ?>>Ferramenta DEWALT</td>
		<td colspan="2"><input type='checkbox' name='linhas_ferramenta' value='t' class='frm' <? if($linhas_ferramenta=='t')echo "checked"; ?>>Ferramenta Black & Decker</td>
	</tr>
	<tr >
		<td><input type='checkbox' name='linhas_lavadora' value='t' class='frm' <? if($linhas_lavadora=='t')echo "checked"; ?>>Lavadora de press�o</td>
		<td colspan="3"><input type='checkbox' name='linhas_compressores' value='t' class='frm' <? if($linhas_compressores=='t')echo "checked"; ?>>Compressores</td>
	</tr>
	<tr  class='subtitulo' >
		<td colspan="4">2. Sua empresa tem t�cnicos treinados?</td>

	</tr>
	<tr >
		<td colspan="2"><input type='radio' name='treinados' value='t' <? if($treinados=='t')echo "checked"; ?>>Sim</td>
		<td colspan="2"><input type='radio' name='treinados' value='f' <? if($treinados=='f')echo "checked"; ?>>N�o</td>
	</tr>
	<tr >
		<td colspan="4">&nbsp Nome dos t�cnicos  <input type="text" name="nome_tecnicos" size="82" maxlength="50" value="<? echo $nome_tecnicos ?>"></td>
	</tr>
	<tr  class='subtitulo' >
		<td colspan="4">3. Marque os demais fabricantes al�m da Black & Decker que sua empresa � autorizada.</td>
	</tr>
	
	<tr>
		<td>Eletrodom�sticos:</td><td><input type='checkbox' name='outros_eletronicos_0' value='Walita' class='frm' <? if($outros_eletronicos_0=='Walita')echo "checked"; ?>>Walita</td>
		<td><input type='checkbox' name='outros_eletronicos_1' value='Arno' class='frm' <? if($outros_eletronicos_1=='Arno')echo "checked"; ?>>Arno</td>
		<td>Outros <input type="text" name="outros_eletronicos_2" size="15" maxlength="50" value="<? echo $outros_eletronicos_2 ?>"></td>
	</tr>

	<tr >
		<td>Ferramentas el�tricas:</td><td><input type='checkbox' name='outros_ferramentas_0' value='Bosch' class='frm' <? if($outros_ferramentas_0=='Bosch')echo "checked"; ?>>Bosch</td>
		<td><input type='checkbox' name='outros_ferramentas_1' value='Makita' class='frm' <? if($outros_ferramentas_1=='Makita')echo "checked"; ?>>Makita</td>
		<td>Outros <input type="text" name="outros_ferramentas_2" size="15" maxlength="50" value="<? echo $outros_ferramentas_2 ?>"></td>
	</tr>
	<tr >
		<td>Compressores:</td><td><input type='checkbox' name='outros_compressores_0' value='Schulz' class='frm' <? if($outros_compressores_0=='Schulz')echo "checked"; ?>>Schulz</td>
		<td><input type='checkbox' name='outros_compressores_1' value='Chiaperini' class='frm' <? if($outros_compressores_1=='Chiaperini')echo "checked"; ?>>Chiaperini</td>
		<td>Outros <input type="text" name="outros_compressores_2" size="15" maxlength="50" value="<? echo $outros_compressores_2 ?>"></td>
	</tr>
	<tr >
		<td>Lavadoras:</td><td><input type='checkbox' name='outros_lavadoras_0' value='Eletrolux' class='frm' <? if($outros_lavadoras_0=='Eletrolux')echo "checked"; ?>>Eletrolux</td>
		<td><input type='checkbox' name='outros_lavadoras_1' value='Kracher' class='frm' <? if($outros_lavadoras_1=='Kracher')echo "checked"; ?>>Kracher</td>
		<td>Outros <input type="text" name="outros_lavadoras_2" size="15" maxlength="50" value="<? echo $outros_lavadoras_2 ?>"></td>
	</tr>

	<tr  class='subtitulo' >
		<td colspan="4">4. Fez treinamento com outras fabricantes?</td>
	</tr>
	<tr >
		<td><input type='radio' name='treinado_outros' value='t' <? if($treinado_outros=='f')echo "checked"; ?>>Sim</td>
		<td><input type='radio' name='treinado_outros' value='f' <? if($treinado_outros=='f')echo "checked"; ?>>N�o</td>
		<td colspan="2">Quais? &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="treinado_outros_quais" size="25" maxlength="50" value="<? echo $treinado_outros_quais ?>"></td>
	</tr>
<!--questao nova -->
	<tr  class='subtitulo' >
		<td colspan="4">5. Sua empresa � autorizada de alguma marca de ferramentas pneum�ticas? </td>
	</tr>
	<tr >
		<td><input type='radio' name='treinado_pneu' value='t' <? if($treinado_pneu=='f')echo "checked"; ?>>Sim</td>
		<td><input type='radio' name='treinado_pneu' value='f' <? if($treinado_pneu=='f')echo "checked"; ?>>N�o</td>
		<td colspan="2">Qual marca? <input type="text" name="treinado_pneu_quais" size="25" maxlength="50" value="<? echo $treinado_pneu_quais ?>"></td>
	</tr>
	<tr class='subtitulo' >
		<td colspan="4">6. Alguns de seus t�cnicos j� participaram de treinamento de ferramentas pneum�ticas?  </td>
	</tr>
	<tr >
		<td><input type='radio' name='treinado_pneu_outros' value='t' <? if($treinado_pneu_outros=='f')echo "checked"; ?>>Sim</td>
		<td><input type='radio' name='treinado_pneu_outros' value='f' <? if($treinado_pneu_outros=='f')echo "checked"; ?>>N�o</td>
		<td colspan="2">Qual marca? <input type="text" name="treinado_pneu_outros_quais" size="25" maxlength="50" value="<? echo $treinado_pneu_outros_quais ?>"></td>
	</tr>
	<tr>
		<td align='center' colspan='5'>

			<INPUT TYPE="hidden" name="btn_acao" value="" >
			<input type="button" value="Gravar" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='atualizar' ; document.frm_posto.submit() } else { alert ('Aguarde submiss�o') }" ALT="Gravar formul�rio" title="Gravar Altera��es">

		</td>
	</tr>
	
</table>


<!-- ============================ Botoes de Acao ========================= -->

</form>

<p>

<? include "rodape.php"; ?>