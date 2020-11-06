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
			$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais({$xconsumidor_cidade})) AND UPPER(estado) = UPPER({$xconsumidor_estado})";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais({$xconsumidor_cidade})) AND UPPER(estado) = UPPER({$xconsumidor_estado})";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
					$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

					$sql = "INSERT INTO tbl_cidade (
								nome, estado
							) VALUES (
								'{$cidade_ibge}', '{$cidade_estado_ibge}'
							) RETURNING cidade";
					$res = pg_query($con, $sql);

					$cidade = pg_fetch_result($res, 0, "cidade");
				} else {
					$msg_erro .= "Cidade não encontrada";
				}
			}
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
							reclamado            ,
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
						$xreclamado        ,
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
?>
<style>
.input {font-size: 10px; 
		  font-family: verdana; 
		  BORDER-RIGHT: #666666 1px double; 
		  BORDER-TOP: #666666 1px double; 
		  BORDER-LEFT: #666666 1px double; 
		  BORDER-BOTTOM: #666666 1px double; 
		  BACKGROUND-COLOR: #ffffff}
</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
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
		<a href="<? echo "$PHP_SELF?height=300&width=700&modal=true"; ?>" class="thickbox" title="Please Sign In"><img src='imagens_admin/btn_edit.gif' border='0' alt='Alterar'></a> <a href="javascript:minimizar('dados_1');"><img src='imagens_admin/btn_fechar.gif' border='0' alt='Minimizar'></a>
<?		}	?>
	
		</td>
	</tr></table>
	<div id='dados_1' style='position:relative; display:block;width:700px;'>
        <table width='700' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
          <tr> 
            <td align='left'><strong>Atendente:</strong></td>
            <td align='left'><?	if(strlen($callcenter)>0){
									echo "$atendente";
								}else{
									echo $login_login;
								}?>
			</td>
            <td align='left'><strong>Data <BR>Abertura:</strong></td>
            <td align='left'><input name="data_abertura_callcenter" <?if(strlen($callcenter)>0)echo "DISABLED";?> class="input" type="text" 
			size="12" maxlength="10" 
			onKeyUp="formata_data(this.value,'frm_callcenter', 'data_abertura_callcenter')"
			value="<?	if(strlen($callcenter)>0){
							echo "$abertura_callcenter";
						}else{
							echo date("d/m/Y");
						}
					?>"></td>
            <td align='left'><strong>Natureza:</strong></td>
            <td align='left'>
			<select name="natureza_chamado" style='width:100px; font-size:9px'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  <? if(strlen($callcenter)==0){ ?> onBlur='mostraDefeitos(document.frm_callcenter.natureza_chamado.value,document.frm_callcenter.produto_referencia.value)'<?}?>>
<?			if($login_fabrica==6){ ?>
			<option value='Reclamação'       <? if($natureza_chamado == 'Reclamação')       echo ' selected';?>>Reclamação</option>
			<option value='Informação'       <? if($natureza_chamado == 'Informação')       echo ' selected';?>>Informação</option>
			<?if($login_fabrica <> 6){ //chamado 1237?>
				<option value='Insatisfação'     <? if($natureza_chamado == 'Insatisfação')     echo ' selected';?>>Insatisfação</option>
				<option value='Troca de produto' <? if($natureza_chamado == 'Troca de produto') echo ' selected';?>>Troca de produto</option>
			<?}?>
			<option value='Engano'           <? if($natureza_chamado == 'Engano')           echo ' selected';?>>Engano</option>
			<option value='Outras áreas'     <? if($natureza_chamado == 'Outras áreas')     echo ' selected';?>>Outras áreas</option>
			<option value='Email'            <? if($natureza_chamado == 'Email')            echo ' selected';?>>Email</option>
			<option value='Ocorrência'       <? if($natureza_chamado == 'Ocorrência')       echo ' selected';?>>Ocorrência</option>

<?			}else{ ?>
			<option value='Dúvida'<? if($natureza_chamado == 'Dúvida') echo ' selected';?>>Dúvida</option>
			<option value='Reclamação'<? if($natureza_chamado == 'Reclamação') echo ' selected';?>>Reclamação</option>
			<option value='Insatisfação'<? if($natureza_chamado == 'Insatisfação') echo ' selected';?>>Insatisfação</option>

<? } ?>
              </select></td>
          </tr>
          <tr> 
            <td align='left'><strong>Nome:</strong></td> 
            <td align='left'> <input name="consumidor_nome"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $consumidor_nome ;?>' class="input" type="text" size="30" maxlength="500"> <img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer'>
            </td>
            <td align='left'><strong>Cpf:</strong></td>
            <td align='left'>
			<input name="consumidor_cpf" value='<?echo $consumidor_cpf ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="12" maxlength="14">
			<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor: pointer' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, "cpf")'>
			<input name="cliente"  class="input" value='<?echo $cliente ;?>' type="hidden"> 

			</td>
            <td align='left'><strong>Rg:</strong></td>
            <td align='left'><input name="consumidor_rg"  value='<?echo $consumidor_rg ;?>' <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="14" maxlength="14"> 
            </td>
          </tr>
          <tr> 
            <td align='left'><strong>E-mail</strong></td>
            <td align='left'><input name="consumidor_email"   value='<?echo $consumidor_email ;?>' <?if(strlen($callcenter)>0)echo "DISABLED";?> class="input" type="text" size="33" maxlength="500"></td>
            <td align='left'><strong>Telefone:</strong></td>
            <td align='left'><input name="consumidor_fone" value='<?echo $consumidor_fone ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?> class="input"  type="text" size="15" maxlength="16"></td>
            <td align='left'><strong>Cep:</strong></td>
            <td align='left'><input name="consumidor_cep" value='<?echo $consumidor_cep ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="14" maxlength="10" onblur="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;">
			</td>
          </tr>
          <tr>
            <td align='left'><strong>Endereço:</strong></td>
            <td align='left'><input name="consumidor_endereco"  value='<?echo $consumidor_endereco ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="33" maxlength="500"></td>
            <td align='left'><strong>Número:</strong></td>
            <td align='left'><input name="consumidor_numero"  value='<?echo $consumidor_numero ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="15" maxlength="16"></td>
            <td align='left'><strong>Complem.</strong></td>
            <td align='left'><input name="consumidor_complemento" value='<?echo $consumidor_complemento ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>   class="input" type="text" size="14" maxlength="14"></td>
          </tr>
          <tr>
            <td align='left'><strong>Bairro:</strong></td>
            <td align='left'><input name="consumidor_bairro" value='<?echo $consumidor_bairro ;?>'   <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="33" maxlength="500"></td>
            <td align='left'><strong>Cidade:</strong></td>
            <td align='left'><input name="consumidor_cidade" value='<?echo $consumidor_cidade ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>   class="input" type="text" size="15" maxlength="16"></td>
            <td align='left'><strong>Estado:</strong></td>
            <td align='left'>
			<select name="consumidor_estado" style='width:100px; font-size:9px' <?if(strlen($callcenter)>0)echo "DISABLED";?> >
			<? $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
					for ($i=0; $i<=26; $i++)
					{
					echo"<option value='".$ArrayEstados[$i]."'";
					if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
					echo ">".$ArrayEstados[$i]."</option>\n";
					}
					?>
             </select>
			 </td>
          </tr>
        </table>
		</div>
</td>
  </tr>
  <tr>
    <td align='left'><span style=' position:absolute;float:left;'><strong>Informações do Produto</strong></span><span style='position:relative;float:right;'><img src='imagens_admin/btn_edit.gif' border='0' alt='Alterar'> <a href="javascript:minimizar('dados_2');"><img src='imagens_admin/btn_fechar.gif' border='0' alt='Minimizar'></a></span>
	<div id='dados_2' style='position:relative; display:block;width:700px;'>
	<table width='700' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
        <tr> 
          <td align='left'><strong>Assunto:</strong></td>
          <td align='left'><input name="assunto" value='<?echo $assunto ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="35" maxlength="50"></td>
           <td align='left'><strong>OS:</strong></td>
          <td align='left'> <input name="sua_os" value='<?echo $sua_os ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" <?if(strlen($callcenter)>0)echo "DISABLED";?>  size="8" maxlength="10">
		  <img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_os (document.frm_callcenter.sua_os.value)">
		  </td>
		   <td align='left'><strong>Data OS:</strong></td>
          <td align='left'><input name="data_abertura" value='<?echo $data_abertura ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="11" maxlength="10" onKeyUp="formata_data(this.value,'frm_callcenter', 'data_abertura')"></td>
        </tr>
        <tr> 
          <td align='left'><strong>Produto:</strong></td>
          <td align='left'> 
		  <input type='hidden' name='produto' value="<? echo $produto; ?>">
		  <input type='hidden' name='produto_referencia' value="<? echo $produto_referencia; ?>">
		  <input name="produto_nome"  class="input" <?if(strlen($callcenter)>0)echo "DISABLED";?>  value='<?echo $produto_nome ;?>' 
		  onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao');
		  <? if(strlen($callcenter)==0){ ?> mostraDefeitos(document.frm_callcenter.natureza_chamado.value,document.frm_callcenter.produto_referencia.value)<?}?>" type="text" size="30" maxlength="500"> 
		  <img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle'
		  style='cursor: pointer' 
		  onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')">
          </td>
		 <td align='left'><strong>Série:</strong></td>
          <td align='left'><input name="serie" value='<?echo $serie ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="12" maxlength="20"></td>
		  <td align='left'><strong>Data<BR>Compra:</strong></td>
          <td align='left'><input name="data_nf"  value='<?echo $data_nf ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="11" maxlength="10" onKeyUp="formata_data(this.value,'frm_callcenter', 'data_nf')"></td>
        </tr>
        <tr> 
          <td align='left'><strong>Nota Fiscal:</strong></td>
          <td align='left'><input name="nota_fiscal" value='<?echo $nota_fiscal ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input" type="text" size="10" maxlength="10"></td>
          <td align='left'><strong>Revenda:</strong></td>
          <td align='left'  colspan='3'>
		  <input name="revenda_nome" value='<?echo $revenda_nome ;?>'  <?if(strlen($callcenter)>0)echo "DISABLED";?>  class="input"  type="text" size="33" maxlength="50">
		  <img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor: pointer'
		  onclick="javascript:fnc_pesquisa_revenda(document.frm_callcenter.revenda_nome, 'nome')">
		  <input type='hidden' name='revenda' value="<? echo $revenda; ?>" ></td>
        </tr>
        <tr> 
          <td align='left'><strong>Posto Autor.</strong></td>
          <td align='left' colspan='4'>
		  <input name="posto_nome" value='<?echo $posto_nome ;?>' class="input" type="text"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  size="35" maxlength="255" onblur="javascript:fnc_pesquisa_posto(document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome, 'nome')">
		  <img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style='cursor: pointer'
		  onclick="javascript:fnc_pesquisa_posto(document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome, 'nome')">
			<input type='hidden' name='codigo_posto' value="<? echo $codigo_posto; ?>"  <?if(strlen($callcenter)>0)echo "DISABLED";?> >
		  </td>
        </tr>

      </table>
	  </div></td>
  </tr>
   <tr>
    <td align='left'><span style=' position:absolute;float:left;'><strong>Ocorrência</strong></span><span style='position:relative;float:right;'><img src='imagens_admin/btn_edit.gif' border='0' alt='Alterar'> <a href="javascript:minimizar('dados_3');"><img src='imagens_admin/btn_fechar.gif' border='0' alt='Minimizar'></a></span>
	<BR>
	<div id='dados_3' style='position:relative; display:block;width:700px;'>
	<table width='700' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
        <tr> 
          <td align='left' valign='top'><strong><a href='javascript:mostraDefeitos(document.frm_callcenter.natureza_chamado.value,document.frm_callcenter.produto_referencia.value)'>Defeito:</a></strong></td>
          <td align='left' colspan='5'>
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
          <td align='left' colspan='5'><TEXTAREA NAME="reclamado"  <?if(strlen($callcenter)>0)echo "DISABLED";?>  ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
          </td>
        </tr>
      </table>
	  </div>
	  </td>
  </tr>
    <tr>
    <td align='left'><strong>Ações</strong><BR>
	<? if(strlen($callcenter)>0){ 
			$sql = "SELECT 
						hd_chamado_item    ,    
						to_char(data,'DD/MM/YYYY HH24:MI:SS') as data,
						comentario         ,
						tbl_admin.login    ,           
						interno            ,
						status_item        , 
						interno
					FROM tbl_hd_chamado_item
					join tbl_admin on tbl_hd_chamado_item.admin = tbl_admin.admin
					WHERE tbl_hd_chamado_item.hd_chamado = $callcenter
							order by tbl_hd_chamado_item.data ";
					//echo $sql;
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($x=0;pg_numrows($res)>$x;$x++){
					$data               = pg_result($res,$x,data);
					$comentario         = pg_result($res,$x,comentario);
					$atendente_resposta = pg_result($res,$x,login);
					$status_item        = pg_result($res,$x,status_item);
					$interno            = pg_result($res,$x,interno);
					$xx = $xx + 1;
					?>
				<B><table width='700' border='0' align='center' cellpadding="3" cellspacing="1" style=' border:#485989 1px solid; background-color: #A0BFE0;font-size:10px'>
				<tr> 
				  <td align='left' valign='top'>
				  <table style='font-size: 10px' border='0' width='100%'><tr> 
				  <td align='left' width='70%'>Resposta: <strong><?echo $xx;?></strong> Por: <strong><?echo nl2br($atendente_resposta);?></strong> </td>
				  <td align='right' nowrap><?echo "$data";?></td>
				  </tr>
				  </table>
				  </td>
				  </tr>
				   <? if($interno == "t"){?>
				  <tr> 
					  <td align='center' valign='top' bgcolor='#DFDBBF'>
					  <?echo "<font size='2'>Chamado Interno</font>";?></td>
					</tr>
				<?}?>
				  <? if($status_item == "Cancelado" or $status_item == "Resolvido"){?>
				  <tr> 
					  <td align='center' valign='top' bgcolor='#DFDA99'><?echo "<font size='2'>$status_item</font>";?></td>
					</tr>
				<?}?>
				  <tr> 
				  <td align='left' valign='top' bgcolor='#FFFFFF'><?echo nl2br($comentario);?>
				  </td>
				</tr>
				</table></p>
<?				}
			}
	}
	
	?>
	<table width='700' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#006633 1px solid; background-color: #DCF1DF;font-size:10px'>
        <? if(strlen($callcenter)>0){ ?>
		<tr> 
          <td align='left'><strong>Transferir p/:</strong></td>
          <td align='left'>
		  <select name="transferir" style='width:100px; font-size:9px' class="input" >
			 <option value=''></option>
		  <?	$sql = "SELECT admin, login
						from tbl_admin
						where fabrica = $login_fabrica
						and ativo is true
						and privilegios like '%call_center%' order by login";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$tranferir = pg_result($res,$i,admin);
						$tranferir_nome = pg_result($res,$i,login);
						echo "<option value='$tranferir'>$tranferir_nome</option>";
					}
				
				}
				  
		  
		  ?>
		 
		  </select>
		  <td align='left'><strong>Status Chamado:</strong></td>
          <td align='left'>
		  <select name="status_interacao" style='width:100px; font-size:9px' class="input" >
			<option value=""></option>
			<?php
			$sql = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
			$res = pg_query($con,$sql);
			
			for ($i = 0; $i < pg_num_rows($res);$i++){
				
				$status_hd = pg_result($res,$i,0);
				
				$selected_status = ($status_hd == $status_interacao) ? "SELECTED" : null;
			?>
				<option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd?></option>				
			<?
			}
			?>
		  </select>
		</td>
		<td align='left'><INPUT TYPE="checkbox" NAME="chamado_interno" class="input" ><strong>Apenas Chamado Interno</strong></td>
        </tr>
        <tr> 
          <td align='left' valign='top'><strong>Resposta:</strong></td>
          <td align='left' colspan='4'><TEXTAREA NAME="resposta" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $resposta;?></TEXTAREA>
          </td>
        </tr>
		<? } ?>
		 <tr> 
          <td align='center' colspan='5'><input class="botao" type="hidden" name="btn_acao"  value=''>
				<input  class="input"  type="button" name="bt"        value='Gravar' onclick="javascript:if (document.frm_callcenter.btn_acao.value!='') alert('Aguarde Submissão'); else{document.frm_callcenter.btn_acao.value='Gravar';document.frm_callcenter.submit();}">
          </td>
        </tr>

      </table>
	  </td>
  </tr>

</table>
</form>
<? include "rodape.php";?>
