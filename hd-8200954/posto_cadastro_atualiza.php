<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$btn_acao = $_POST["btn_acao"];
if($btn_acao=="atualizar"){

	$cnpj                    = $_POST["cnpj"];
	if (strlen($cnpj) > 0){$cnpj = "'".$cnpj."'";
	}else{	$msg_erro = '<BR>Digite o cnpj.';}
	
	$cidade                    = $_POST["cidade"];
	if (strlen($cidade) > 0){$cidade = "'".$cidade."'";
	}else{	$msg_erro = '<BR>Digite a cidade.';}
	
	$estado                    = $_POST["estado"];
	if (strlen($estado) > 0){$estado = "'".$estado."'";
	}else{	$msg_erro = '<BR>Digite o estado.';}	
	$ie                      = $_POST["ie"];
	if (strlen($ie) > 0){$ie = "'".$ie."'";
	}else{	$msg_erro = '<BR>Digite a Inscrição Estadual.';}
	
	$codigo                  = $_POST["codigo"];
	if (strlen($codigo) > 0){$codigo = "'".$codigo."'";
	}else{	$msg_erro = '<BR>Digite o Código do Posto.';}

	$nome                    = $_POST["nome"];
	if (strlen($nome) > 0){$nome = "'".$nome."'";
	}else{	$msg_erro = '<BR>Digite o endereço.';}

	$endereco            = $_POST["endereco"];
	if (strlen($endereco) > 0){$endereco = "'".$endereco."'";
	}else{	$msg_erro = '<BR>Digite o endereço.';}
	
	$numero              = $_POST["numero"];
	if (strlen($numero) > 0){$numero = "'".$numero."'";
	}else{	$msg_erro .= '<BR>Digite o número.';}
	
	$complemento         = $_POST["complemento"];
	if (strlen($complemento) > 0){$complemento = "'".$complemento."'";}else{$complemento = 'null';}
	
	$bairro              = $_POST["bairro"];
	if (strlen($bairro) > 0){$bairro = "'".$bairro."'";
	}else{	$msg_erro .= '<BR>Digite o bairro.';}
	
	$cep                 = $_POST["cep"];
	if (strlen($cep) > 0){$cep = "'".$cep."'";
	}else{	$msg_erro .= '<BR>Digite o CEP.';}
	
	$email               = $_POST["email"];
	if (strlen($email) > 0){$email = "'".$email."'";
	}else{	$msg_erro .= '<BR>Digite o e-mail.';}
	
	$fone                = $_POST["fone"];
	if (strlen($fone) > 0){$fone = "'".$fone."'";
	}else{	$msg_erro .= '<BR>Digite o telefone.';}
	
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
	}else{	$msg_erro .= '<BR>Digite a agência.';}
	
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
	if(strlen($treinados)==0){$msg_erro.="Responda a pergunta 2.<BR>";}
	if($treinados=='t' and strlen($nome_tecnicos)==0){
		$msg_erro.="Informe o nome dos técnicos treinados.<BR>";
	}
	
	if($treinados=='f' and strlen($nome_tecnicos)>0){
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
	
	if(strlen($outros_ferramentas_0)>0) $fabricantes .= "$outros_ferramentas_0 ";
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
	if(strlen($treinado_outros)==0){$msg_erro.="Responda a pergunta 4.<BR>";}
	if($treinado_outros == 'f' AND strlen($treinado_outros_quais)>0){$msg_erro.="Marque a como SIM a pergunta 4.<BR>";}
	if($treinado_outros == 't' AND strlen($treinado_outros_quais)==0){$msg_erro.="Informe a fábrica que seu posto recebeu treinamento.<BR>";}
	if($treinado_outros == 'f' AND strlen($treinado_outros_quais)==0){$treinado_outros_quais="null";}
	if(strlen($treinado_outros_quais)==0){$treinado_outros_quais="null";}else{$treinado_outros_quais="'$treinado_outros_quais'";}
	if ($treinado_outros=='f'){$treinado_outros = "'f'";}else{$treinado_outros ="'t'";}
	
	//pergunta5. Sua empresa é autorizada de alguma marca de ferramentas pneumáticas?
$treinado_pneu = $_POST['treinado_pneu'];
$treinado_pneu_quais = $_POST['treinado_pneu_quais'];
if(strlen($treinado_pneu)==0){$msg_erro.="Responda a pergunta 5.<BR>";}
if($treinado_pneu == 'f' AND strlen($treinado_pneu_quais)>0){$msg_erro.="Marque a como SIM a pergunta 5.<BR>";}
if($treinado_pneu == 't' AND strlen($treinado_pneu_quais)==0){$msg_erro.="Informe a fábrica que seu posto é autorizado.<BR>";}
if(strlen($treinado_pneu_quais)==0){$treinado_pneu_quais="null";}else{$treinado_pneu_quais="'$treinado_pneu_quais'";}
if ($treinado_pneu=='f'){$treinado_pneu = "'f'";}else{$treinado_pneu ="'t'";}



//pergunta6. Alguns de seus técnicos já participaram de treinamento de ferramentas pneumáticas?
$treinado_pneu_outros= $_POST['treinado_pneu_outros'];
$treinado_pneu_outros_quais = $_POST['treinado_pneu_outros_quais'];
if(strlen($treinado_pneu_outros)==0){$msg_erro.="Responda a pergunta 6.<BR>";}
if($treinado_pneu_outros == 'f' AND strlen($treinado_pneu_outros_quais)>0){$msg_erro.="Marque a como SIM a pergunta 5.<BR>";}
if($treinado_pneu_outros == 't' AND strlen($treinado_pneu_outros_quais)==0){$msg_erro.="Informe a fábrica que seu posto recebeu treinamento.<BR>";}
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
//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

			$email_origem  = "takashi@telecontrol.com.br";
			$email_destino = "takashi@telecontrol.com.br";
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
				$msg_erro .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
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

$title = "Suas Informações";
$layout_menu = "cadastro";
//include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {

	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}
.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #e7e9ec
}
 
input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "	<font face='arial, verdana' color='#330000' size='-1'>";
	echo $msg_erro;
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<p>
<table width='600' align='center' border='0' bgcolor='#db3d3d'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#000000' size='-1'>
		<? echo "Por favor atualize as informações do seu posto autorizado!";?>
		</font>
	</td>
</tr>
</table>


<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5" class="menu_top" align='center'>
			<font color='#36425C'><? echo "INFORMAÇÕES CADASTRAIS";?>
		</td>
	</tr>
	<tr class="menu_top" align='center'>
		<td><? echo "CNPJ/CPF";?></td>
		<td><? echo "I.E.";?></td>
		<td><? echo "FONE";?></td>
		<td><? echo "FAX";?></td>
		<td><? echo "CONTATO";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td><input type="text" name="cnpj" size="15" maxlength="20" value="<? echo $cnpj ?>"></td>
		<td><input type="text" name="ie" size="20" maxlength="20" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" size="10" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" size="10" maxlength="20" value="<? echo $fax ?>"></td>
		<td><input type="text" name="contato" size="20" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
	</tr>
	<tr class="menu_top" align='center'>
		<td colspan="2"><? echo "CÓDIGO";?></td>
		<td colspan="3"><? echo "RAZÃO SOCIAL";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><? echo $codigo ?></td>
		<input type="hidden" name="codigo" value="<? echo $codigo ?>">
		<td colspan="3"><? echo $nome ?></td>
		<input type="hidden" name="nome" value="<? echo $nome ?>">
	</tr>
</table>

<br>

<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top" align='center'>
		<td colspan="2"><? echo "ENDEREÇO";?></td>
		<td><? echo "NÚMERO";?></td>
		<td colspan="2"><? echo "COMPLEMENTO";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="endereco" size="30" maxlength="49" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" name="complemento" size="20" maxlength="20" value="<? echo $complemento ?>"></td>
	</tr>
	<tr class="menu_top" align='center'>
		<td colspan="2"><?  echo "BAIRRO";?></td>
		<td><? echo "CEP";?></td>
		<td><? echo "CIDADE";?></td>
		<td><? echo "ESTADO";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="bairro" size="30" maxlength="30" value="<? echo $bairro ?>"></td>
		<td><input type="text" name="cep" size="8" maxlength="8" value="<? echo $cep ?>"></td>
		<td><input type="text" name="cidade" size="10" maxlength="30" value="<? echo $cidade ?>"></td>
		<td><input type="text" name="estado" size="2" maxlength="2" value="<? echo $estado ?>"></td>
	</tr>
</table>
<br>
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top" align='center'>
		<td><? echo "NOME FANTASIA";?></td>
		<td><? echo "E-MAIL";?></td>
		<td><? echo "CAPITAL/INTERIOR";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td>
			<input type="text" name="nome_fantasia" size="30" maxlength="40" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>">
		</td>
		<td>
			<select name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? if($sistema_lingua) echo "CAPITAL";else echo "Capital";?></option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? if($sistema_lingua) echo "PROVINCIA";else echo "Interior";?></option>
			</select>
		</td>
	</tr>
	<tr class="menu_top" align='center'>
		<td colspan="3"><? echo "Observações";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="3"><input type="text" name="obs" size="70" maxlength="100" value="<? echo $obs ?>" ></td>
	</tr>
</table>

<p>
<!--  Cobranca -->
<? if(1<>1){ ?>
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan='4' class="menu_top" align='center'>
			<font color='#36425C'><? echo "INFORMAÇÕES PARA COBRANÇA";?></td>
	</tr>
	<tr class="menu_top" align='center'>
		<td colspan="2"><? echo "ENDEREÇO";?></td>
		<td><? echo "NÚMERO";?></td>
		<td><? echo "COMPLEMENTO";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2">&nbsp;<? echo $cobranca_endereco ?></td>
		<td>&nbsp;<? echo $cobranca_numero ?></td>
		<td>&nbsp;<? echo $cobranca_complemento ?></td>
	</tr>
	<tr class="menu_top" align='center'>
		<td><?  echo "BAIRRO";?></td>
		<td><?  echo "CEP";?></td>
		<td><?  echo "CIDADE";?></td>
		<td><?  echo "UF";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td>&nbsp;<? echo $cobranca_bairro ?></td>
		<td>&nbsp;<? echo $cobranca_cep ?></td>
		<td>&nbsp;<? echo $cobranca_cidade ?></td>
		<td>&nbsp;<? echo $cobranca_estado ?></td>
	</tr>
</table>
<? } ?>



<p>

<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="4" class="menu_top" align='center'>
			<font color='#36425C'><? echo "QUESTIONÁRIO";?>
		</td>
	</tr>
	<tr class="menu_top">
		<td colspan="4">1. Marque quais as linhas de produto Black & Decker sua empresa atende:</td>
		
	</tr>
	<tr class="table_line" align='left'>
		<td><input type='checkbox' name='linhas_eletro' value='t' class='frm' <? if($linhas_eletro=='t')echo "checked"; ?>>Eletro</td>
		<td><input type='checkbox' name='linhas_dw' value='t' class='frm' <? if($linhas_dw=='t')echo "checked"; ?>>Ferramenta DEWALT</td>
		<td colspan="2"><input type='checkbox' name='linhas_ferramenta' value='t' class='frm' <? if($linhas_ferramenta=='t')echo "checked"; ?>>Ferramenta Black & Decker</td>
	</tr>
	<tr class="table_line">
		<td><input type='checkbox' name='linhas_lavadora' value='t' class='frm' <? if($linhas_lavadora=='t')echo "checked"; ?>>Lavadora de pressão</td>
		<td colspan="3"><input type='checkbox' name='linhas_compressores' value='t' class='frm' <? if($linhas_compressores=='t')echo "checked"; ?>>Compressores</td>
	</tr>
	<tr class="menu_top">
		<td colspan="4">2. Sua empresa tem técnicos treinados?</td>

	</tr>
	<tr class="table_line">
		<td colspan="2"><input type='radio' name='treinados' value='t' <? if($treinados=='t')echo "checked"; ?>>Sim</td>
		<td colspan="2"><input type='radio' name='treinados' value='f' <? if($treinados=='f')echo "checked"; ?>>Não</td>
	</tr>
	<tr class="table_line">
		<td colspan="4">&nbsp Nome dos técnicos  <input type="text" name="nome_tecnicos" size="30" maxlength="50" value="<? echo $nome_tecnicos ?>">.</td>
	</tr>
	<tr class="menu_top">
		<td colspan="4">3. Marque os demais fabricantes além da Black & Decker que sua empresa é autorizada.</td>
	</tr>
	
	<tr class="table_line2">
		<td>Eletrodomésticos:</td><td><input type='checkbox' name='outros_eletronicos_0' value='Walita' class='frm' <? if($outros_eletronicos_0=='Walita')echo "checked"; ?>>Walita</td>
		<td><input type='checkbox' name='outros_eletronicos_1' value='Arno' class='frm' <? if($outros_eletronicos_1=='Arno')echo "checked"; ?>>Arno</td>
		<td>Outros <input type="text" name="outros_eletronicos_2" size="15" maxlength="50" value="<? echo $outros_eletronicos_2 ?>">.</td>
	</tr>

	<tr class="table_line">
		<td>Ferramentas elétricas:</td><td><input type='checkbox' name='outros_ferramentas_0' value='Bosch' class='frm' <? if($outros_ferramentas_0=='Bosch')echo "checked"; ?>>Bosch</td>
		<td><input type='checkbox' name='outros_ferramentas_1' value='Makita' class='frm' <? if($outros_ferramentas_1=='Makita')echo "checked"; ?>>Makita</td>
		<td>Outros <input type="text" name="outros_ferramentas_2" size="15" maxlength="50" value="<? echo $outros_ferramentas_2 ?>">.</td>
	</tr>
	<tr class="table_line2">
		<td>Compressores:</td><td><input type='checkbox' name='outros_compressores_0' value='Schulz' class='frm' <? if($outros_compressores_0=='Schulz')echo "checked"; ?>>Schulz</td>
		<td><input type='checkbox' name='outros_compressores_1' value='Chiaperini' class='frm' <? if($outros_compressores_1=='Chiaperini')echo "checked"; ?>>Chiaperini</td>
		<td>Outros <input type="text" name="outros_compressores_2" size="15" maxlength="50" value="<? echo $outros_compressores_2 ?>">.</td>
	</tr>
	<tr class="table_line">
		<td>Lavadoras:</td><td><input type='checkbox' name='outros_lavadoras_0' value='Eletrolux' class='frm' <? if($outros_lavadoras_0=='Eletrolux')echo "checked"; ?>>Eletrolux</td>
		<td><input type='checkbox' name='outros_lavadoras_1' value='Kracher' class='frm' <? if($outros_lavadoras_1=='Kracher')echo "checked"; ?>>Kracher</td>
		<td>Outros <input type="text" name="outros_lavadoras_2" size="15" maxlength="50" value="<? echo $outros_lavadoras_2 ?>">.</td>
	</tr>

	<tr class="menu_top">
		<td colspan="4">4. Fez treinamento com outras fabricantes?</td>
	</tr>
	<tr class="table_line">
		<td><input type='radio' name='treinado_outros' value='t' <? if($treinado_outros=='f')echo "checked"; ?>>Sim</td>
		<td><input type='radio' name='treinado_outros' value='f' <? if($treinado_outros=='f')echo "checked"; ?>>Não</td>
		<td colspan="2">Quais? &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="treinado_outros_quais" size="25" maxlength="50" value="<? echo $treinado_outros_quais ?>"></td>
	</tr>
<!--questao nova -->
	<tr class="menu_top">
		<td colspan="4">5. Sua empresa é autorizada de alguma marca de ferramentas pneumáticas? </td>
	</tr>
	<tr class="table_line">
		<td><input type='radio' name='treinado_pneu' value='t' <? if($treinado_pneu=='f')echo "checked"; ?>>Sim</td>
		<td><input type='radio' name='treinado_pneu' value='f' <? if($treinado_pneu=='f')echo "checked"; ?>>Não</td>
		<td colspan="2">Qual marca? <input type="text" name="treinado_pneu_quais" size="25" maxlength="50" value="<? echo $treinado_pneu_quais ?>"></td>
	</tr>
	<tr class="menu_top">
		<td colspan="4">6. Alguns de seus técnicos já participaram de treinamento de ferramentas pneumáticas?  </td>
	</tr>
	<tr class="table_line">
		<td><input type='radio' name='treinado_pneu_outros' value='t' <? if($treinado_pneu_outros=='f')echo "checked"; ?>>Sim</td>
		<td><input type='radio' name='treinado_pneu_outros' value='f' <? if($treinado_pneu_outros=='f')echo "checked"; ?>>Não</td>
		<td colspan="2">Qual marca? <input type="text" name="treinado_pneu_outros_quais" size="25" maxlength="50" value="<? echo $treinado_pneu_outros_quais ?>"></td>
	</tr>
</table>

<br>
<BR>

<!-- ============================ Botoes de Acao ========================= -->
<center>

<INPUT TYPE="hidden" name="btn_acao" value="">
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='atualizar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
</center>

</form>

<p>

<? //include "rodape.php"; ?>