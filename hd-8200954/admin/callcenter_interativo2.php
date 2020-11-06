<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';


$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

include 'funcoes.php';
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$callcenter                = trim($_POST['callcenter']);
	$data_abertura_callcenter  = trim($_POST['data_abertura_callcenter']);
	$natureza_chamado          = trim($_POST['natureza_chamado']);
	$consumidor_nome           = trim($_POST['consumidor_nome']);
	$cliente                   = trim($_POST['cliente']);
	$consumidor_cpf            = trim($_POST['consumidor_cpf']);
	$consumidor_cpf            = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf            = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf            = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf            = str_replace(",","",$consumidor_cpf);
	$consumidor_rg             = trim($_POST['consumidor_rg']);
	$consumidor_rg             = str_replace("/","",$consumidor_rg);
	$consumidor_rg             = str_replace("-","",$consumidor_rg);
	$consumidor_rg             = str_replace(".","",$consumidor_rg);
	$consumidor_rg             = str_replace(",","",$consumidor_rg);
	$consumidor_email          = trim($_POST['consumidor_email']);
	$consumidor_fone           = trim($_POST['consumidor_fone']);
	$consumidor_fone           = str_replace("'","",$consumidor_fone);
	$consumidor_cep            = trim($_POST['consumidor_cep']);
	$consumidor_cep            = str_replace("-","",$consumidor_cep);
	$consumidor_cep            = str_replace("/","",$consumidor_cep);
	$consumidor_endereco       = trim($_POST['consumidor_endereco']);
	$consumidor_numero         = trim($_POST['consumidor_numero']);
	$consumidor_numero         = str_replace("'","",$consumidor_numero);
	$consumidor_complemento    = trim($_POST['consumidor_complemento']);
	$consumidor_bairro         = trim($_POST['consumidor_bairro']);
	$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
	$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
	$assunto                   = trim($_POST['assunto']);
	$sua_os                    = trim($_POST['sua_os']);
	$data_abertura             = trim($_POST['data_abertura']);

	$produto                   = trim($_POST['produto']);
	$produto_referencia        = trim($_POST['produto_referencia']);
	$produto_nome              = trim($_POST['produto_nome']);
	$serie                     = trim($_POST['serie']);
	$data_nf                   = trim($_POST['data_nf']);

	$nota_fiscal               = trim($_POST['nota_fiscal']);
	$revenda                   = trim($_POST['revenda']);
	$revenda_nome              = trim($_POST['revenda_nome']);
	$xcodigo_posto             = trim($_POST['codigo_posto']);
	$posto_nome                = trim($_POST['posto_nome']);
	$defeito_reclamado         = trim($_POST['defeito_reclamado']);
	$reclamado                 = trim($_POST['reclamado']);
	$status                    = trim($_POST['status']);

	$transferir                = trim($_POST['transferir']);
	$chamado_interno           = trim($_POST['chamado_interno']);
	$status_interacao          = trim($_POST['status_interacao']);
	$resposta                  = trim($_POST['resposta']);
	//echo "login_admin $login_admin";
//	echo "adasdas> $resposta<< <BR>";
if(strlen($callcenter)==0){
	/*FORMATA DATA*/
		$xdata_abertura_callcenter = fnc_formata_data_pg(trim($data_abertura_callcenter));
		if ($xdata_abertura_callcenter == 'null') $msg_erro .= " Digite a data de abertura do Call Center.";
	//	$xdata_abertura_callcenter = str_replace("'","",$xdata_abertura_callcenter);
		if(strlen($data_nf)>0) {
			$xdata_nf = fnc_formata_data_pg(trim($data_nf));
		//	$xdata_nf = str_replace("'","",$xdata_nf);
		}else{ 
			$xdata_nf = "null";
			
		}
		if(strlen($data_abertura)>0){
			$xdata_abertura =  fnc_formata_data_pg(trim($data_abertura));
		}else{
			$xdata_abertura = "null";
		}
	/*FORMATA DATA*/

	/*VALIDACAO DE DADOS*/
		if(strlen($consumidor_nome)==0){ $msg_erro .="<BR>Digite o nome do cliente";}else{ $xconsumidor_nome = "'".$consumidor_nome."'";}
		if(strlen($consumidor_cpf)==0)    { $xconsumidor_cpf   = "null"; }else{ $xconsumidor_cpf   = "'".$consumidor_cpf."'";}
		if(strlen($consumidor_rg)==0)     {	$xconsumidor_rg    = "null"; }else{ $xconsumidor_rg    = "'".$consumidor_rg."'";}
		if(strlen($consumidor_email)==0)  { $xconsumidor_email ="null";  }else{ $xconsumidor_email = "'".$consumidor_email."'";}
		if(strlen($consumidor_fone)==0)   { $xconsumidor_fone  ="null";  }else{ $xconsumidor_fone  = "'".$consumidor_fone."'";}
		if(strlen($consumidor_cep)==0)        { $xconsumidor_cep        ="null"; }else{ $xconsumidor_cep      = "'".$consumidor_cep."'";}
		if(strlen($consumidor_endereco)==0)   { $xconsumidor_endereco   ="null"; }else{ $xconsumidor_endereco = "'".$consumidor_endereco."'";}
		if(strlen($consumidor_numero)==0)     { $xconsumidor_numero     ="null"; }else{ $xconsumidor_numero   = "'".$consumidor_numero."'";}
		if(strlen($consumidor_complemento)==0){$xconsumidor_complemento ="null"; }else{ $xconsumidor_complemento      = "'".$consumidor_complemento."'";}
		if(strlen($consumidor_bairro)==0) { $xconsumidor_bairro ="null"; }else{ $xconsumidor_bairro = "'".$consumidor_bairro."'";}
		if(strlen($consumidor_cidade)==0) { $msg_erro .="<BR>Digite a cidade do cliente"; }else{ $xconsumidor_cidade = "'".$consumidor_cidade."'";}
		if(strlen($consumidor_estado)==0) {  $msg_erro .="<BR>Digite o estado do cliente"; }else{ $xconsumidor_estado = "'".$consumidor_estado."'";}
		if(strlen($assunto)==0)     { $msg_erro .="<BR>Digite o assunto"; }else{ $xassunto = "'".$assunto."'";}
		if(strlen($serie)==0)       { $xserie ="null";                    }else{ $xserie = "'".$serie."'";}
		if(strlen($nota_fiscal)==0) { $xnota_fiscal ="null";              }else{ $xnota_fiscal = "'".$nota_fiscal."'";}
		if(strlen($defeito_reclamado)==0) { $xdefeito_reclamado ="null";  }else{ $xdefeito_reclamado = $defeito_reclamado;}
		if(strlen($reclamado)==0)        { $xreclamado ="null";           }else{ $xreclamado = "'".$reclamado."'";}
		if(strlen($natureza_chamado)==0)  { $xnatureza_chamado ="null";   }else{ $xnatureza_chamado = "'".$natureza_chamado."'";}
		if(strlen($status)==0)  { $xstatus ="'Aberto'";    }else{ $xstatus = "'".$xstatus."'";}

	/*VALIDACAO DE DADOS*/

	/*INICIO*/
		$res = pg_exec ($con,"BEGIN TRANSACTION");

	/*CIDADE*/
		if(strlen($msg_erro)==0) { 
			/*verifica cidade*/
			$sql = "SELECT tbl_cidade.cidade
					FROM tbl_cidade
					where tbl_cidade.nome = $xconsumidor_cidade
					AND tbl_cidade.estado = $xconsumidor_estado
					limit 1";
			$res = pg_exec($con,$sql);
			//echo nl2br($sql)."<BR>";
			if(pg_numrows($res)>0){
				$cidade = pg_result($res,0,0);
			}else{
				$sql = "INSERT INTO tbl_cidade(nome, estado)values($xconsumidor_cidade,$xconsumidor_estado)";		
				//echo nl2br($sql)."<BR>";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$res    = pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
				$cidade = pg_result ($res,0,0);
			}
			/*verifica cidade*/
		}
	/*CIDADE*/

	/*CLIENTE*/
		if(strlen($msg_erro)==0) { //insere o cliente
			if(strlen($cliente)>0) {
				$sql = "SELECT tbl_cliente.cliente
						from tbl_cliente 
						where cliente = $cliente";
				$res = pg_exec($con,$sql);
				//echo nl2br($sql)."<BR>";
				if(pg_numrows($res)>0){
					$xcliente = pg_result($res,0,0);
					
					$sql = "UPDATE tbl_cliente set 
								nome        = $xconsumidor_nome       ,
								endereco    = $xconsumidor_endereco   ,
								numero      = $xconsumidor_numero     ,
								complemento = $xconsumidor_complemento,
								bairro   = $xconsumidor_bairro        ,
								cep      = $xconsumidor_cep           ,
								fone     = $xconsumidor_fone          ,
								email    = $xconsumidor_email         ,
								cpf      = $xconsumidor_cpf           ,
								rg       = $xconsumidor_rg            ,
								cidade   = $cidade
							WHERE tbl_cliente.cliente = $xcliente";
				//	echo nl2br($sql)."<BR>";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					
				}
			}else{
					$sql = "SELECT tbl_cliente.cliente
							from tbl_cliente 
							where cpf = $xconsumidor_cpf";
					$res = pg_exec($con,$sql);
					//echo nl2br($sql)."<BR>";
					if(pg_numrows($res)==0){
					$sql = "INSERT INTO tbl_cliente(
								nome            ,
								endereco        ,
								numero          ,
								complemento     ,
								bairro          ,
								cep             ,
								fone            ,
								email           ,
								cpf             ,
								rg              ,
								cidade  
								)VALUES(
								$xconsumidor_nome       ,
								$xconsumidor_endereco   ,
								$xconsumidor_numero     ,
								$xconsumidor_complemento,
								$xconsumidor_bairro     ,
								$xconsumidor_cep        ,
								$xconsumidor_fone       ,
								$xconsumidor_email      ,
								$xconsumidor_cpf        ,
								$xconsumidor_rg         ,
								$cidade
								)";
				//	echo nl2br($sql)."<BR>";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$res    = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
					$xcliente = pg_result ($res,0,0);
					}else{
					$xcliente = pg_result($res,0,0);
					}
				}
			}
		//insere o cliente
	/*CLIENTE*/

	/*SUA OS*/
		if(strlen($msg_erro)==0) {
			if(strlen($sua_os)>0){
				$xos = "'$sua_os'";
			}else{
				$xos = "null";
			}
		}
	/*SUA OS*/

	/*PRODUTO*/
		if(strlen($msg_erro)==0) {
			if(strlen($produto)>0) {
				$sql = "SELECT produto 
						FROM  tbl_produto 
						WHERE produto = $produto";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
		//		echo nl2br($sql)."<BR>";
				if(pg_numrows($res)>0){
					$xproduto = pg_result($res,0,0);
				}else{
					$xproduto = "null";
				}
			}else{
				if(strlen($produto_nome)>0){
					$sql = "SELECT tbl_produto.produto 
								FROM  tbl_produto
								join  tbl_linha on tbl_produto.linha = tbl_linha.linha
								WHERE tbl_produto.descricao ilike '%$produto_nome%'
								limit 1";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
			//		echo nl2br($sql)."<BR>";
					if(pg_numrows($res)>0){
						$xproduto = pg_result($res,0,0);
					}else{
						$xproduto = "null";
					}
				}else{
						$xproduto = "null";
				}
			}
		}
	/*PRODUTO*/

	/*REVENDA*/
	if(strlen($msg_erro)==0) {
		if(strlen($revenda)>0){
			$xrevenda = $revenda;
			if(strlen($revenda_nome)>0){
				$xrevenda_nome = "'".$revenda_nome."'";
			}else{
				$xrevenda_nome = "null";
			}
		}else{
			if(strlen($revenda_nome)>0){
				$sql = "SELECT revenda from tbl_revenda 
						where nome ilike '%$revenda_nome%' limit 1";
				$res = pg_exec($con,$sql);
			//	echo nl2br($sql)."<BR>";
				if(pg_numrows($res)>0){
					$xrevenda = pg_result($res,0,0);
					$xrevenda_nome = "'".$revenda_nome."'";
				}else{
					$xrevenda = "null";
					$xrevenda_nome = "'".$revenda_nome."'";
				}
			}else{
				$xrevenda = "null";
				$xrevenda_nome = "null";
			}
		}
	}
	/*REVENDA*/

	/*POSTO*/
	if(strlen($msg_erro)==0) {
		if(strlen($xcodigo_posto)>0){
			$sql = "SELECT tbl_posto_fabrica.posto
					from tbl_posto_fabrica
					where fabrica = $login_fabrica 
					and codigo_posto = '$xcodigo_posto'";
			$res = pg_exec($con,$sql);
		//	echo nl2br($sql)."<BR>";
			if(pg_numrows($res)>0){
				$xposto = pg_result($res,0,0);
				$xposto_nome = "'".$posto_nome."'";
			}else{
				$xposto = "null";
				if(strlen($posto_nome)>0){
					$xposto_nome = "'".$posto_nome."'";
				}else{
					$xposto_nome = "null";
				}
			}
		}else{
			if(strlen($posto_nome)>0){
				$sql = "SELECT posto from tbl_posto 
							where nome ilike '%$posto_nome%' limit 1";
				$res = pg_exec($con,$sql);
			//	echo nl2br($sql)."<BR>";
				if(pg_numrows($res)>0){
					$xposto = pg_result($res,0,0);
					$xposto_nome = "'".$posto_nome."'";
				}else{
					$xposto = "null";
					$xposto_nome = "'".$posto_nome."'";
				}
			}else{
				$xposto = "null";
				$xposto_nome = "null";
			}
		}
	}
	/*POSTO*/

	/*INSERINDO*/
		if(strlen($msg_erro)==0) {
			$sql = "INSERT INTO tbl_hd_chamado (
						admin                ,
						posto                ,
						data                 ,
						titulo               ,
						status               ,
						atendente            ,
						fabrica_responsavel  ,
						categoria            ,
						fabrica
					)values(
						$login_admin,
						$xposto     ,
						current_timestamp,
						$xassunto,
						$xstatus,
						$login_admin,
						$login_fabrica,
						$xnatureza_chamado,
						$login_fabrica
					)";
		//	echo nl2br($sql)."<BR>";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);
		}
		if(strlen($msg_erro)==0) {
			$sql = "INSERT INTO tbl_hd_chamado_extra(
							hd_chamado           ,
							data_abertura        ,
							produto              ,
							cliente              ,
							revenda              ,
							revenda_nome         ,
							posto                ,
							posto_nome           ,
							sua_os               ,
							serie                ,
							data_nf              ,
							nota_fiscal          ,
							defeito_reclamado    ,
							reclamado,
							data_abertura_os
					)values(
						$hd_chamado        ,
						$xdata_abertura_callcenter,
						$xproduto          ,
						$xcliente          ,
						$xrevenda          ,
						$xrevenda_nome     ,
						$xposto            ,
						$xposto_nome       ,
						$xos               ,
						$xserie            ,
						$xdata_nf          ,
						$xnota_fiscal      ,
						$xdefeito_reclamado,
						$xreclamado,
						$xdata_abertura
					);";
		//	echo nl2br($sql)."<BR>";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
//			$msg_erro = "aaa";
		}

		if (strlen($msg_erro) == 0) {
		//	$res = pg_exec($con,"ROLLBACK TRANSACTION");
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header ("Location: cadastra_callcenter.php?callcenter=$hd_chamado");
		//	exit;
		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	//	echo "aaa> $hd_chamado";
}else{ //inserindo interação
	if(strlen($resposta)==0){ $msg_erro = "Por favor insira uma resposta";}else{ $xresposta = "'".$resposta."'";}
	if(strlen($chamado_interno)>0){$chamado_interno = "'t'";}else{$chamado_interno="'f'";}
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	if(strlen($msg_erro)==0){
		if(strlen($transferir)>0){
			$sql = "UPDATE tbl_hd_chamado set atendente = $transferir
					WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					and    hd_chamado = $callcenter";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if(strlen($msg_erro)==0){
		if(strlen($resposta)>0){
			$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						status_item
						)values(
						$callcenter       ,
						current_timestamp ,
						'$resposta'       ,
						$login_admin      ,
						$chamado_interno  ,
						'$status_interacao'
						)";
			//			echo $sql;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}
	if(strlen($msg_erro)==0){
		$sql = "SELECT status from tbl_hd_chamado where hd_chamado = $callcenter";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$status_chamado = pg_result($res,0,0);
			if($status_chamado<>$status_interacao){
				$sql = "UPDATE tbl_hd_chamado set status = '$status_interacao'
						where tbl_hd_chamado.hd_chamado = $callcenter";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}
	
	if (strlen($msg_erro) == 0) {
		//	$res = pg_exec($con,"ROLLBACK TRANSACTION");
			$res = pg_exec($con,"COMMIT TRANSACTION");
	//		header ("Location: cadastra_callcenter.php?callcenter=$hd_chamado");
	//		exit;
			$sql = "SELECT fn_callcenter_dias_interacao($callcenter,$login_fabrica)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT fn_callcenter_dias_aberto($callcenter,$login_fabrica);";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

		}else{
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}



}




}



$callcenter = $_GET['callcenter'];
if(strlen($callcenter)>0){

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_cliente.nome,
					tbl_cliente.endereco ,
					tbl_cliente.numero ,
					tbl_cliente.complemento ,
					tbl_cliente.bairro ,
					tbl_cliente.cep ,
					tbl_cliente.fone ,
					tbl_cliente.email ,
					tbl_cliente.cpf ,
					tbl_cliente.rg ,
					tbl_cliente.cliente ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_hd_chamado_extra.posto_nome as posto_nome,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_produto.produto,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_hd_chamado_extra.sua_os,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		JOIN tbl_cliente on tbl_hd_chamado_extra.cliente = tbl_cliente.cliente
		LEFT JOIN tbl_cidade on tbl_cliente.cidade = tbl_cidade.cidade
		JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
		LEFT JOIN tbl_posto on tbl_hd_chamado.posto = tbl_posto.posto
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";
		$res = pg_exec($con,$sql);
//	echo $sql;
		if(pg_numrows($res)>0){
			$callcenter                = pg_result($res,0,callcenter);
			$abertura_callcenter       = pg_result($res,0,abertura_callcenter);
			$data_abertura_callcenter  = pg_result($res,0,data);
			$natureza_chamado          = pg_result($res,0,natureza_operacao);
			$consumidor_nome           = pg_result($res,0,nome);
			$cliente                   = pg_result($res,0,cliente);
			$consumidor_cpf            = pg_result($res,0,cpf);
			$consumidor_rg             = pg_result($res,0,rg);
			$consumidor_email          = pg_result($res,0,email);
			$consumidor_fone           = pg_result($res,0,fone);
			$consumidor_cep            = pg_result($res,0,cep);
			$consumidor_endereco      = pg_result($res,0,endereco);
			$consumidor_numero        = pg_result($res,0,numero);
			$consumidor_complemento   = pg_result($res,0,complemento);
			$consumidor_bairro        = pg_result($res,0,bairro);
			$consumidor_cidade        = pg_result($res,0,cidade_nome);
			$consumidor_estado        = pg_result($res,0,estado);
			$assunto                  = pg_result($res,0,assunto);
			$sua_os                   = pg_result($res,0,sua_os);
			$os                       = pg_result($res,0,os);
			$data_abertura            = pg_result($res,0,data_abertura);
			$produto                  = pg_result($res,0,produto);
			$produto_referencia       = pg_result($res,0,produto_referencia);
			$produto_nome             = pg_result($res,0,produto_nome);
			$serie                    = pg_result($res,0,serie);
			$data_nf                  = pg_result($res,0,data_nf);
			$nota_fiscal              = pg_result($res,0,nota_fiscal);
			$revenda                  = pg_result($res,0,revenda);
			$revenda_nome             = pg_result($res,0,revenda_nome);
			$posto                    = pg_result($res,0,posto);
			$posto_nome               = pg_result($res,0,posto_nome);
			$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
			$reclamado                = pg_result($res,0,reclamado);
			$status                   = pg_result($res,0,status);
			$atendente                = pg_result($res,0,atendente);
		}

}

include "cabecalho.php";

?>
<style>
.input {font-size: 10px; 
		  font-family: verdana; 
		  BORDER-RIGHT: #666666 1px double; 
		  BORDER-TOP: #666666 1px double; 
		  BORDER-LEFT: #666666 1px double; 
		  BORDER-BOTTOM: #666666 1px double; 
		  BACKGROUND-COLOR: #ffffff}

.respondido {font-size: 10px; 
				color: #4D4D4D;
			  font-family: verdana; 
			   BORDER-RIGHT: #666666 1px double; 
		  BORDER-TOP: #666666 1px double; 
		  BORDER-LEFT: #666666 1px double; 
		  BORDER-BOTTOM: #666666 1px double; 
			  BACKGROUND-COLOR: #ffffff;
}
.inicio{
	border:#485989 1px solid;
	background-color: #e6eef7;
	font-size:10px;
	font-family:verdana;
	text-align:center;
	margin: 0 auto;
	width:200px;
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;

}
.tab_content{
	border:#485989 1px solid;
	font-size:10px;
	font-family:verdana;
	margin: 0 auto;
	float:center;
	width:680px;
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;

}
</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->
<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxSpeed: 'fast'} );
		//fxAutoHeight: true,
	});

</script>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_abertura_callcenter').datePicker({startDate:'01/01/2000'});
		$("#consumidor_cpf").maskedinput("999.999.999-99");
		$("#data_abertura_callcenter").maskedinput("99/99/9999");
		$("#data_abertura").maskedinput("99/99/9999");
		$("#data_nf").maskedinput("99/99/9999");
		$("#consumidor_fone").maskedinput("(999) 9999-9999");
		$("#consumidor_cep").maskedinput("99999-999");
	});
</script>

<script type="text/javascript" src="js/thickbox.js"></script>

<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
function minimizar(arquivo){
	if (document.getElementById(arquivo)){
		var style2 = document.getElementById(arquivo); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
function fnc_pesquisa_posto_regiao(nome,cidade,estado) {
	if (cidade.value != "" || estado.value != "" || nome.value != ""){
		var url = "";
		url = "posto_pesquisa_regiao.php?nome=" + nome.value + "&cidade=" + cidade.value + "&estado=" + estado.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = document.frm_callcenter.posto_codigo;
		janela.nome    = document.frm_callcenter.posto_nome;
		janela.focus();
	}
}
function formata_data(valor_campo, form, campo){
	var mydata = '';
	mydata = mydata + valor_campo;
	myrecord = campo;
	myform = form;

	if (mydata.length == 2){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}
	if (mydata.length == 5){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}

}

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor_callcenter (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor_callcenter.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.cliente		= document.frm_callcenter.cliente;
		janela.nome			= document.frm_callcenter.consumidor_nome;
		janela.cpf			= document.frm_callcenter.consumidor_cpf;
		janela.rg			= document.frm_callcenter.consumidor_rg;
		janela.cidade		= document.frm_callcenter.consumidor_cidade;
		janela.estado		= document.frm_callcenter.consumidor_estado;
		janela.fone			= document.frm_callcenter.consumidor_fone;
		janela.endereco		= document.frm_callcenter.consumidor_endereco;
		janela.numero		= document.frm_callcenter.consumidor_numero;
		janela.complemento	= document.frm_callcenter.consumidor_complemento;
		janela.bairro		= document.frm_callcenter.consumidor_bairro;
		janela.cep			= document.frm_callcenter.consumidor_cep;
		janela.focus();
	}
}


function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda_callcenter.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda_callcenter.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_callcenter.revenda_nome;
	janela.revenda		= document.frm_callcenter.revenda;

	janela.focus();
}


function fnc_pesquisa_os (campo) {
	
	url = "pesquisa_os_callcenter.php?sua_os=" + campo;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.sua_os			= document.frm_callcenter.sua_os;
	janela.data_abertura	= document.frm_callcenter.data_abertura;
	janela.data_nf	        = document.frm_callcenter.data_nf;
	janela.serie	        = document.frm_callcenter.serie;
	janela.nota_fiscal	    = document.frm_callcenter.nota_fiscal;
	janela.produto	        = document.frm_callcenter.produto;
	janela.produto_nome	    = document.frm_callcenter.produto_nome;
	janela.revenda_nome	    = document.frm_callcenter.revenda_nome;
	janela.revenda	        = document.frm_callcenter.revenda;
	//janela.posto        	= document.frm_callcenter.posto;
	janela.posto_nome     	= document.frm_callcenter.posto_nome;

	janela.focus();
	
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

/* ============= Função FORMATA CPF =============================
Nome da Função : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digitação
		Parâm.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;
		
		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
			}
		if ( tecla == 13) return false; 
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla); 
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http1 = new Array();
function mostraDefeitos(natureza,produto){

	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();

	url = "cadastra_callcenter_ajax.php?ajax=true&natureza="+ natureza +"&produto=" + produto;
	http1[curDateTime].open('get',url);
	
	var campo = document.getElementById('div_defeitos');
//alert(natureza);
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http1[curDateTime].readyState == 4){
			if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
				var results = http1[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
				
			}
		}
	}
	http1[curDateTime].send(null);

}
var http2 = new Array();
function localizarFaq(produto,local){
	var faq_duvida = document.getElementById(local);
	var campo = document.getElementById('div_'+local);
	if(produto.length==0){
		alert('Por favor selecione o produto');
		return 0;	
	}
	
	if(faq_duvida.value.length==0){
		alert('Por favor inserir a dúvida');
		return 0;	
	}

	var curDateTime = new Date();
	http2[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&produto=" + produto;
	http2[curDateTime].open('get',url);
	
	http2[curDateTime].onreadystatechange = function(){
		if(http2[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http2[curDateTime].readyState == 4){
			if (http2[curDateTime].status == 200 || http2[curDateTime].status == 304){
				var results = http2[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
				
			}
		}
	}
	http2[curDateTime].send(null);


}

</script>
<br><br>
<? if(strlen($msg_erro)>0){ ?>

<? //recarrega informacoes
	$callcenter                = trim($_POST['callcenter']);
	$data_abertura_callcenter  = trim($_POST['data_abertura_callcenter']);
	$natureza_chamado          = trim($_POST['natureza_chamado']);
	$consumidor_nome           = trim($_POST['consumidor_nome']);
	$cliente                   = trim($_POST['cliente']);
	$consumidor_cpf            = trim($_POST['consumidor_cpf']);
	$consumidor_cpf            = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf            = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf            = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf            = str_replace(",","",$consumidor_cpf);
	$consumidor_rg             = trim($_POST['consumidor_rg']);
	$consumidor_rg            = str_replace("/","",$consumidor_rg);
	$consumidor_rg            = str_replace("-","",$consumidor_rg);
	$consumidor_rg            = str_replace(".","",$consumidor_rg);
	$consumidor_rg            = str_replace(",","",$consumidor_rg);
	$consumidor_email          = trim($_POST['consumidor_email']);
	$consumidor_fone           = trim($_POST['consumidor_fone']);
	$consumidor_cep            = trim($_POST['consumidor_cep']);
	$consumidor_cep            = str_replace("-","",$consumidor_cep);
	$consumidor_cep            = str_replace("/","",$consumidor_cep);
	$consumidor_endereco       = trim($_POST['consumidor_endereco']);
	$consumidor_numero         = trim($_POST['consumidor_numero']);
	$consumidor_complemento    = trim($_POST['consumidor_complemento']);
	$consumidor_bairro         = trim($_POST['consumidor_bairro']);
	$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
	$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
	$assunto                   = trim($_POST['assunto']);
	$sua_os                    = trim($_POST['sua_os']);
	$data_abertura             = trim($_POST['data_abertura']);

	$produto                   = trim($_POST['produto']);
	$produto_referencia        = trim($_POST['produto_referencia']);
	$produto_nome              = trim($_POST['produto_nome']);
	$serie                     = trim($_POST['serie']);
	$data_nf                   = trim($_POST['data_nf']);

	$nota_fiscal               = trim($_POST['nota_fiscal']);
	$revenda                   = trim($_POST['revenda']);
	$revenda_nome              = trim($_POST['revenda_nome']);
	$posto                     = trim($_POST['posto']);
	$posto_nome                = trim($_POST['posto_nome']);
	$defeito_reclamado         = trim($_POST['defeito_reclamado']);
	$reclamado                 = trim($_POST['reclamado']);
	$status                    = trim($_POST['status']);

	$transferir                = trim($_POST['transferir']);
	$chamado_interno           = trim($_POST['chamado_interno']);
	$status_interacao          = trim($_POST['status_interacao']);
	$resposta                  = trim($_POST['resposta']);

?>

 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F7503E;font-size:10px'><tr>
<td align='center'><? echo "<font color='#FFFFFF'>$msg_erro</font>"; ?>
</td>
</tr>
</table>
<?}?>

 <table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
<td align='right' width='150'></td>
<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
<td align='center'><STRONG>APRESENTAÇÃO</STRONG><BR>
HBFlex, <?echo $login_login;?>, bom dia.<BR>
Em que posso ajudá-lo?
</td>
<td align='right' width='150'></td>
</tr>
</table>

<BR />
<form name="frm_callcenter" method="post" action="<?$PHP_SELF?>">
<input name="callcenter" class="input" type="hidden" value='<?echo $callcenter;?>'>
<table width="700" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:12px'>
  <tr>
    <td align='left'>
	<table width="100%" border='0'><tr><td align='left'><strong>Cadastro de Atendimento</strong></td>
	<td align='right'><strong><?
		if(strlen($callcenter)>0){	
			echo "nº <font color='#CC0033'>$callcenter</font>";
	}
		?></strong>
	<?	if(strlen($callcenter)>0){	?>
		<a href="javascript:minimizar('dados_1');"><img src='imagens_admin/btn_fechar.gif' border='0' alt='Minimizar'></a>
<?		}	?>
	
		</td>
	</tr></table>
	<div id='dados_1' style='position:relative; display:block;width:700px;'>
        <table width='700' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
          <tr> 
            <td align='left'><strong>Atendente:</strong></td>
            <td align='left' colspan='3'><?	if(strlen($callcenter)>0){
									echo "$atendente";
								}else{
									echo $login_login;
								}?>
			</td>


            <td align='left'><strong>Data Abertura:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="abertura_callcenter"><? echo "$abertura_callcenter";?></h1>
				<? }else{ ?>
			<input name="data_abertura_callcenter" id="data_abertura_callcenter" class="input" type="text" 
			size="13" maxlength="10" value="<?	echo date("d/m/Y");	?>">
			<!-- onKeyUp="formata_data(this.value,'frm_callcenter', 'data_abertura_callcenter')" -->
				<? } ?>
			</td>
          </tr>
          <tr> 
            <td align='left'><strong>Nome:</strong></td> 
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:200px" id="consumidor_nome"><? echo "$consumidor_nome";?></h1>
			<? }else{ ?>
			<input name="consumidor_nome"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $consumidor_nome ;?>' class="input" type="text" size="30" maxlength="500"> <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer'>
 <? } ?>
            </td>
            <td align='left'><strong>Cpf:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_cpf"><? echo "$consumidor_cpf";?></h1>
			<? }else{ ?>
			<input name="consumidor_cpf" value='<?echo $consumidor_cpf ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="14" maxlength="14">
			<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, "cpf")'>
			<input name="cliente"  class="input" value='<?echo $cliente ;?>' type="hidden"> 
 <? } ?>
			</td>
            <td align='left'><strong>Rg:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_rg"><? echo "$consumidor_rg";?></h1>
			<? }else{ ?>
			<input name="consumidor_rg"  value='<?echo $consumidor_rg ;?>' <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="14" maxlength="14"> 
			 <? } ?>
            </td>
          </tr>
          <tr> 
            <td align='left'><strong>E-mail:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:200px" id="consumidor_email"><? echo "$consumidor_email";?></h1>
			<? }else{ ?>
			<input name="consumidor_email"   value='<?echo $consumidor_email ;?>' <?if(strlen($callcenter)>0)echo "DISABLED";?> class="input" type="text" size="33" maxlength="500">
			 <? } ?>
			</td>
            <td align='left'><strong>Telefone:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_fone"><? echo "$consumidor_fone";?></h1>
			<? }else{ ?>
			<input name="consumidor_fone" value='<?echo $consumidor_fone ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?> class="input"  type="text" size="15" maxlength="16">
			 <? } ?>
			</td>
            <td align='left'><strong>Cep:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_cep"><? echo "$consumidor_cep";?></h1>
			<? }else{ ?>
			<input name="consumidor_cep" value='<?echo $consumidor_cep ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="14" maxlength="10" onblur="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;">
			 <? } ?>
			</td>
          </tr>
          <tr>
            <td align='left'><strong>Endereço:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:200px" id="consumidor_endereco"><? echo "$consumidor_endereco";?></h1>
			<? }else{ ?>
			<input name="consumidor_endereco"  value='<?echo $consumidor_endereco ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="33" maxlength="500"> 
			<? } ?>
			</td>
            <td align='left'><strong>Número:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_numero"><? echo "$consumidor_numero";?></h1>
			<? }else{ ?>
			<input name="consumidor_numero"  value='<?echo $consumidor_numero ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="15" maxlength="16"> 
			<? } ?>
			</td>
            <td align='left'><strong>Complem.</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_complemento"><? echo "$consumidor_complemento";?></h1>
			<? }else{ ?>
			<input name="consumidor_complemento" value='<?echo $consumidor_complemento ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>   class="input" type="text" size="14" maxlength="14">
			<? } ?>
			</td>
          </tr>
          <tr>
            <td align='left'><strong>Bairro:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:200px" id="consumidor_bairro"><? echo "$consumidor_bairro";?></h1>
			<? }else{ ?>
			<input name="consumidor_bairro" value='<?echo $consumidor_bairro ;?>'   <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="33" maxlength="30">
			<? } ?>
			</td>
            <td align='left'><strong>Cidade:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_cidade"><? echo "$consumidor_cidade";?></h1>
			<? }else{ ?>
			<input name="consumidor_cidade" value='<?echo $consumidor_cidade ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>   class="input" type="text" size="15" maxlength="16">
			<? } ?>
			</td>
            <td align='left'><strong>Estado:</strong></td>
            <td align='left'>
			<?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:100px" id="consumidor_estado"><? echo "$consumidor_estado";?></h1>
			<? }else{ ?>
			<select name="consumidor_estado" style='width:100px; font-size:9px' <?if(strlen($callcenter)>0)echo "DISABLED";?> >
			<? $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
			for ($i=0; $i<=26; $i++){
				echo"<option value='".$ArrayEstados[$i]."'";
				if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
					echo ">".$ArrayEstados[$i]."</option>\n";
				}
				?>
             </select>
			 <? } ?>
			 </td>
          </tr>
        </table>
		</div>
</td>
  </tr>
  <tr>
    <td align='left'>
	<input type='hidden' name='tab_atual' id='tab_atual' value='' >
	<div id="container-Principal">
	<ul>
		<li>
			<a href="#extensao" onclick="javascript:$('#tab_atual').val('extensao')"><span><img src='imagens/lupa.png' align=absmiddle> Ext. da garantia</span></a>
		</li>
		<li>
			<a href="#reclamacao_produto" onclick="javascript:$('#tab_atual').val('reclamacao_produto')"><span><img src='imagens/lupa.png' align=absmiddle> Recl. Produto</span></a>
		</li>
		<li>
			<a href="#reclamacao_empresa" onclick="javascript:$('#tab_atual').val('reclamacao_empresa')"><span><img src='imagens/lupa.png' align=absmiddle> Recl. Empresa</span></a>
		</li>
		<li>
			<a href="#duvida_produto" onclick="javascript:$('#tab_atual').val('duvida_produto')"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle> Dúvida Produto</span></a>
		</li>
		<li>
			<a href="#sugestao" onclick="javascript:$('#tab_atual').val('sugestao')"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle>Sugestão</span></a>
		</li>
		<li>
			<a href="#assistencia" onclick="javascript:$('#tab_atual').val('assistencia')"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle>Assist. Téc.</span></a>
		</li>
	</ul>
		<div id="extensao" class='tab_content'>extensao</div>
		<div id="reclamacao_produto" class='tab_content'>
		 <table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
			Qual é a sua reclamação SR.(a)?<BR>
			ou<BR>
			O Sr.(a) diz que...., correto?
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table>
		Informações do Produto
		 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
		<td align='left'><strong>Referência:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:50px" id="produto_nome"><? echo "$produto_nome";?></h1>
			<? }else{ ?>
		  <input name="produto_referencia"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_referencia ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'referencia');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value)" type="text" size="15" maxlength="15"> 
		  <img src='imagens/lupa.png' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')"> <? } ?>
          </td>
		<td align='left'><strong>Descrição:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:200px" id="produto_nome"><? echo "$produto_nome";?></h1>
			<? }else{ ?>
		  <input type='hidden' name='produto' value="<? echo $produto; ?>">
		  <input name="produto_nome"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_nome ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value)" type="text" size="35" maxlength="500"> 
		  <img src='imagens/lupa.png' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')"> <? } ?>
          </td>
		  <td align='left'><strong>Série:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="serie"  style="width:100px" id="serie"><? echo "$serie";?></h1>
			<? }else{ ?>
		  <input name="serie"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $serie ;?>'>
		  <? } ?>
          </td>
		</tr>
		<tr>
		<td><a href="javascript:mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value)">Defeitos</a></td>
		 <td align='left' colspan='5' width='630' valign='top'>
			<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
				<?   if(strlen($defeito_reclamado)>0){
						$sql = "Select defeito_reclamado, descricao from tbl_defeito_reclamado
						where defeito_reclamado = $defeito_reclamado";				
						$res = pg_exec($con,$sql);
						if(pg_numrows($res)>0){
							$defeito_reclamado_descricao = pg_result($res,0,descricao);
						echo "<input type='radio' checked value='$defeito_reclamado'><font size='1'>$defeito_reclamado_descricao</font>";
						}
					}
				?>
			</div>
			</td>
		</tr>
		<tr> 
          <td align='left' valign='top'><strong>Descrição:</strong></td>
          <td align='left' colspan='5'>
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:600px; height:60px" id="reclamado"><? echo "$reclamado";?></h1>
			<? }else{ ?>
		  <TEXTAREA NAME="reclamado"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
		  <? } ?>
          </td>
        </tr>
		</table>
		Consultar FAQ´s sobre o Produto
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
		<td align='left'><strong>Dúvida:</strong></td>
        <td align='left'> <input name="faq_duvida_produto"  id='faq_duvida_produto' class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $faq_duvida ;?>'>
		<input  class="input"  type="button" name="bt_localizar"        value='Localizar' onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_produto')">
		</td>
		</tr>
		<tr>
		<td colspan='2'>
		<div id='div_faq_duvida_produto' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		</div>
		</td>
		</tr>
		</table>
		Consultar Posto Autorizado
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
		<td align='left'><strong><a href="callcenter_interativo_posto.php?fabrica=1225&keepThis=trueTB_iframe=true&height=400&width=700" title="Localize o Posto Autorizado" class="thickbox">Clique aqui para consultar o posto autorizado mais próximo do consumidor</a></strong></td>
		</tr>
		</table>
		</div>


		<div id="reclamacao_empresa" class='tab_content'>
		<table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
			Qual é a sua reclamação SR.(a)?<BR>
			ou<BR>
			O Sr.(a) diz que...., correto?
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table>
		Informações da Reclamação
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
		<td align='left' valign='top'><strong>Reclamação:</strong></td>
          <td align='left' colspan='5'>
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:600px; height:60px" id="reclamado"><? echo "$reclamado";?></h1>
			<? }else{ ?>
		  <TEXTAREA NAME="reclamado"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
		  <? } ?>
          </td>
		</tr>
		</table><BR>
		<table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 48 /24 / 12 hs.
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table>
		</div>


		<div id="duvida_produto" class='tab_content'>
		<table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Confirmar ou perguntar a dúvida.</STRONG><BR>
			Qual é a sua dúvida SR.(a)?<BR>
			ou<BR>
			O dúvida do Sr.(a) é sobre como...., correto?
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table>
		Informações do Produto
		 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
		<td align='left'><strong>Referência:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:50px" id="produto_nome"><? echo "$produto_nome";?></h1>
			<? }else{ ?>
		  <input name="produto_referencia_duvida"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_referencia ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_duvida,document.frm_callcenter.produto_nome_duvida,'referencia');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia_duvida.value)" type="text" size="15" maxlength="15"> 
		  <img src='imagens/lupa.png' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_duvida,document.frm_callcenter.produto_nome_duvida,'descricao')"> <? } ?>
          </td>
		<td align='left'><strong>Descrição:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:200px" id="produto_nome"><? echo "$produto_nome";?></h1>
			<? }else{ ?>
		  <input type='hidden' name='produto' value="<? echo $produto; ?>">
		  <input name="produto_nome_duvida"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_nome ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_duvida,document.frm_callcenter.produto_nome_duvida,'descricao');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value)" type="text" size="35" maxlength="500"> 
		  <img src='imagens/lupa.png' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_duvida,document.frm_callcenter.produto_nome_duvida,'descricao')"> <? } ?>
          </td>
		  <td align='left'><strong>Série:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="serie"  style="width:100px" id="serie"><? echo "$serie";?></h1>
			<? }else{ ?>
		  <input name="serie"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $serie ;?>'>
		  <? } ?>
          </td>
		</tr>
		<tr>
		<td><strong>Dúvida:</strong></td>
		<td align='left' colspan='5'> <input name="faq_duvida_duvida"  id='faq_duvida_duvida' class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?> size='74' value='<?echo $faq_duvida ;?>'>
		<input  class="input"  type="button" name="bt_localizar"        value='Localizar' onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia_duvida.value,'faq_duvida_duvida')">
		</td>
		</tr>
		<tr>
		<td colspan='2'>
		<div id='div_faq_duvida_duvida' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		</div>
		</td>
		</tr>
		</table>
		</div>
		<div id="sugestao" class='tab_content'>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>		
		<tr> 
          <td align='left' valign='top'><strong>Sugestão:</strong></td>
          <td align='left' colspan='5'>
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido" style="width:600px; height:60px" id="reclamado"><? echo "$reclamado";?></h1>
			<? }else{ ?>
		  <TEXTAREA NAME="reclamado"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
		  <? } ?>
          </td>
        </tr>
		</table><BR>
		<table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 48 /24 / 12 hs.
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table>

		</div>
		<div id="assistencia" class='tab_content'>
		<table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Qual o problema com o produto?</strong></td>
		<td align='right' width='150'></td>
		</tr>
		</table>
		Informações do Produto
		 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'><tr>
		<td align='left'><strong>Referência:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:50px" id="produto_nome"><? echo "$produto_nome";?></h1>
			<? }else{ ?>
		  <input name="produto_referencia_pa"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_referencia ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'referencia');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia_pa.value)" type="text" size="15" maxlength="15"> 
		  <img src='imagens/lupa.png' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'descricao')"> <? } ?>
          </td>
		<td align='left'><strong>Descrição:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="respondido"  style="width:200px" id="produto_nome"><? echo "$produto_nome";?></h1>
			<? }else{ ?>
		  <input type='hidden' name='produto' value="<? echo $produto; ?>">
		  <input name="produto_nome_pa"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_nome ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'descricao');mostraDefeitos('Reclamação',document.frm_callcenter.produto_pa.value)" type="text" size="35" maxlength="500"> 
		  <img src='imagens/lupa.png' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'descricao')"> <? } ?>
          </td>
		  <td align='left'><strong>Série:</strong></td>
          <td align='left'> 
		  <?	if(strlen($callcenter)>0){ ?>
				<h1 class="serie"  style="width:100px" id="serie"><? echo "$serie";?></h1>
			<? }else{ ?>
		  <input name="serie"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $serie ;?>'>
		  <? } ?>
          </td>
		</tr>
		<tr>
		<td><strong>Dúvida:</strong></td>
		<td align='left' colspan='5'> <input name="faq_duvida_pa"  id='faq_duvida_pa' class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?> size='74' value='<?echo $faq_duvida ;?>'>
		<input  class="input"  type="button" name="bt_localizar"        value='Localizar' onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia_pa.value,'faq_duvida_pa')">
		</td>
		</tr>
		<tr>
		<td colspan='2'>
		<div id='div_faq_duvida_pa' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		</div>
		</td>
		</tr>
		</table>



		</div>
	</div>
	  </td>
  </tr>
	 <tr> 
      <td align='center' colspan='5'>
     <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#5AA962 1px solid; background-color:#D1E7D3;font-size:10px'><tr>
		<td align='center'>
		<input class="botao" type="hidden" name="btn_acao"  value=''>
		<input  class="input"  type="button" name="bt"        value='Gravar e Gerar Atendimento' onclick="javascript:if (document.frm_callcenter.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_callcenter.btn_acao.value='Gravar';document.frm_callcenter.submit();}"></td>
		</tr>
		</table>
		  </td>
     </tr>
	 <tr> 
      <td align='center' colspan='5'>
		 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Posso ajudá-lo(a) em algo mais Sr.(a)?</STRONG><BR>
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table><bR>
		  </td>
     </tr>
	 <tr> 
      <td align='center' colspan='5'>
		 <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'><tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
		<td align='center'><STRONG>Por favor, queira anotar o n° do protocolo de atendimento</STRONG><BR>
		Número <font color='#D1130E'><?echo $callcenter;?></font>
		</td>
		<td align='right' width='150'></td>
		</tr>
		</table>
	  </td>
     </tr>
</table>
</form>
<? include "rodape.php";?>