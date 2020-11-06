<?php
/******************************************************************
Script .........: Controle de Gado e Fazendas
Por ............: Fabio Nowaki
Data ...........: 30/08/2006
********************************************************************************************/

##############################################################################
## INCLUDES E CONEXÔES BANCO
##############################################################################

session_start();
include_once "class.Template.inc.php";
require_once('banco.inc.php');

include "autentica_adm.php";

header('Cache-Control: no-cache');
header('Pragma: no-cache');
header("Content-Type: text/html;  charset=ISO-8859-1",true);

$lista_ordem="";
$desc=" ASC";

$msg_erro="";
$msg="";

##############################################################################
##############                      AJAX STAR                  	##############
##############################################################################	

	if (isset($_GET['star']) && strlen($_GET['star'])>0 && isset($_GET['codigo']) && strlen($_GET['codigo'])>0){
		$codi=$_GET['codigo'];
		$estrela=$_GET['star'];
		
		if ($estrela==1) $estrela=0;
		else $estrela=1;
		
		$query = "UPDATE tbl_venda SET star=$estrela WHERE venda = $codi LIMIT 1";
		$rSet = $db->Query($query);
		exit();
	}
	

##############################################################################
##############                 AJAX   DETALHES                	##############
##############################################################################	

	if (isset($_GET['detalhes']) && strlen($_GET['detalhes'])>0){
		$codi=$_GET['detalhes'];

		$query = "SELECT tbl_venda.venda AS venda,
					 tbl_venda.cliente  AS cliente ,
					 tbl_venda.email   AS email,
					 tbl_venda.endereco  AS endereco,
					 tbl_venda.produto  AS produto,
					 tbl_venda.rastreio  AS rastreio,					 
					 tbl_venda.envio_obs  AS envio_obs,					 					 
					 tbl_venda.senha  AS senha,					 					 
					 tbl_venda.obs  AS obs,		
					 tbl_venda.acessou  AS acessou,							 
					 tbl_venda.star  AS star,							 			 
					 DATE_FORMAT(tbl_venda.data , '%d/%m/%Y') AS data,
					 DATE_FORMAT(tbl_venda.data_digitacao , '%d/%m/%Y') AS data_digitacao,
					 DATE_FORMAT(tbl_venda.data_pago , '%d/%m/%Y') AS data_pago,					 
					 DATE_FORMAT(tbl_venda.data_envio , '%d/%m/%Y') AS data_envio,
					 tbl_venda.valor_pago AS valor_pago,
					 tbl_venda.pagamento_conferido AS pagamento_conferido,
					 tbl_venda.banco AS banco,					 
					 tbl_venda.pagamento_obs AS pagamento_obs,					 					 
					 
					 tbl_produto.nome AS produto_nome,
					 tbl_produto.cor AS cor ,
					 tbl_produto.preco_compra  AS preco_compra,
					 tbl_produto.preco_venda   AS preco_venda,
					 tbl_produto.codigo   AS codigo
			 FROM tbl_venda
			 	JOIN tbl_produto ON tbl_produto.produto=tbl_venda.produto
			 WHERE tbl_venda.venda=$codi;
					";	
		$rSet = $db->Query($query);
		$linha = $db->FetchArray($rSet);
		$tmp1 = ($linha['data_envio']=="00/00/0000")?"Produto não enviado":$linha['data_envio'];
		$tmp1_ras = $linha['rastreio'];
		$tmp1_obs = $linha['envio_obs'];
						
		$tmp2=($linha['data_digitacao']=="00/00/0000")?"-":$linha['data_digitacao'];
		$tmp3=nl2br($linha['endereco']);
		$tmp4=($linha['data_pago']=="00/00/0000")?"":$linha['data_pago'];
		$tmp5=$linha['valor_pago'];
		$tmp6=$linha['pagamento_conferido'];
		$tmp7=$linha['banco'];						
		$tmp8=$linha['pagamento_obs'];								
		$tmp9=($linha['acessou']==1)?"ACESSOU":"NÃO ACESOU";										

$teste = `echo 'formProcesso=true&cbPesquisa=NUMPROC&dePesquisa=001910008117&submit=Pesquisar' | lynx -post_data -cookie_save_file=teste.cok "http://ww2.tjrn.gov.br/cpopg/pcpoSelecaoPG.jsp"`;
	echo $teste = nl2br($teste);
		
		$query = "SELECT tbl_mensagem.venda AS venda,
					 DATE_FORMAT(tbl_mensagem.data , '%d/%m/%Y %H:%i') AS data,
					 tbl_mensagem.corpo   AS corpo,
					 tbl_mensagem.admin   AS admin					 
				 FROM tbl_mensagem
					JOIN tbl_venda ON tbl_venda.venda=tbl_mensagem.venda
				 WHERE tbl_mensagem.venda=$codi
				 ORDER by data ASC;
					";	
		$res_mensagens = $db->Query($query);
		$temp_men = "";
		while($mensagem = $db->FetchArray($res_mensagens)){
				$usuario=(trim($mensagem['admin'])=='')?'Cliente':'Vendedor';
				$temp_men .="
						   <tr>
							 <td class='classe_4' nowrap>".$mensagem['data']."</td>
							 <td class='classe_4'><b>$usuario </b> diz:  ".$mensagem['corpo']."</td>			 				 
						   </tr>
				
				";
		}
				
		$tabela_detalhes= "
			 <table border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse;' bordercolor='#999999'>
			  <tr>
				 <td class='titulo_detalhe_titulo' colspan='2'>Detalhes da Negociação</td>
			   </tr>
			   <tr>
				 <td class='titulo_detalhe' nowrap>Senha</td>
				 <td class='classe_4'>".$linha['senha']."</td>			 
			   </tr>				   
			   <tr>
				 <td class='titulo_detalhe' nowrap>Preço de Venda</td>
				 <td class='classe_4'>".$linha['preco_venda']."</td>			 
			   </tr>		   
			   <tr>
				 <td class='titulo_detalhe' nowrap>Data do Envio</td>
				 <td class='classe_4'>$tmp1</td>			 
			   </tr>		   
			   <tr>
				 <td class='titulo_detalhe' nowrap>Data Digitação</td>
				 <td class='classe_4'>$tmp2</td>			 
			   </tr>		   
			   <tr>
				 <td class='titulo_detalhe' valign='top' nowrap>Endereço</td>
				 <td class='classe_4'>$tmp3</td>			 
			   </tr>		   
			   <tr>
				 <td class='titulo_detalhe' valign='top' nowrap>Acessou?</td>
				 <td class='classe_4'>$tmp9</td>			 
			   </tr>			   

			   <tr>
				 <td class='titulo_detalhe' nowrap>Observação</td>
				 <td class='classe_4'>".$linha['obs']."</td>			 
			   </tr>
			   </table>";		
		$tabela_mensagens= "
			<form action='movimento.vendas.php' id='enviarMSG' method='POST' name='enviarMSG' >	  
         	<input name='txtvenda' type='hidden' id='txtvenda' value='".$linha['venda']."'/>
			 <table border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse;' bordercolor='#999999'>
			  <tr>
				 <td class='titulo_detalhe_titulo' colspan='3'>Interações com o Cliente</td>
			   </tr>
			   <tr>
				 <td class='titulo_detalhe'>Data</td>
				 <td class='titulo_detalhe'>Mensagem</td>			 				 
			   </tr>		   
			   $temp_men
			   </table>
			   <textarea name='txtmensagem' cols='40' rows='2'></textarea>
			   <input name='btnEnviarMSG' type='button' value='Enviar' onclick=\"javascript: if(this.value == 'Enviar' ) {this.value='Enviando...'; this.form.submit() } else { alert ('Aguarde submissão') }\"/>
			   </table>
			   </form>
			   ";		
		
		if ($tmp6==0)	   {
		$tabela_atualiza= "
			<form action='movimento.vendas.php' id='confirmarPagamento' method='POST' name='confirmarPagamento' >	  
         	<input name='txtvenda' type='hidden' id='txtvenda' value='".$linha['venda']."'/>		
			 <table border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse;' bordercolor='#999999'>
			  <tr>
				 <td class='titulo_detalhe_titulo' colspan='2'>Confirmar Pagamento</td>
			   </tr>
			   <tr>
				 <td class='titulo_detalhe'>Dta Pgto</td>
				 <td class='classe_4'><input name='txtdatapagamento' type='text'   onclick='showCalendarControl(this);' value='$tmp4'/></td>			 
			   </tr>
			   <tr>
				 <td class='titulo_detalhe'>Valor</td>
				 <td class='classe_4'><input name='txtvalorpago' type='text' value='$tmp5'/></td>			 
			   </tr>				   		   
			   <tr>
				 <td class='titulo_detalhe'>Banco ($tmp7)</td>
				 <td class='classe_4'>
								 <select name='txtbanco'  id='txtbanco'>
								   <option value='Bradesco' selected>Bradesco</option>
								   <option value='Banespa' >Banespa</option>
								   <option value='Caixa Economica Federal' >Caixa</option>
								   <option value='Banco do Brasil' >BB</option>				   				   				   
								 </select>
				 </td>			 
			   </tr>
			   <tr>
				 <td class='titulo_detalhe'>Observação</td>
				 <td class='classe_4'><textarea name='txtobs' cols='10' rows='2'>$tmp8</textarea></td>			 
			   </tr>				

			   <tr>
				 <td class='titulo_detalhe'>Mandar e-Mail</td>
				 <td class='classe_4'><input type='checkbox' name='txtmandaremail' value='mandar' id='txtmandaremail' /></td>			 
			   </tr>				
			   			   
			   <tr>
				 <td class='titulo_detalhe' colspan='2'><input name='Gravar' type='button' value='Confirmar Pagamento' onclick=\"javascript: if(this.value == 'Confirmar Pagamento' ) {this.value='Aguarde...'; this.form.submit() } else { alert ('Aguarde submissão') }\"/></td>			 
			   </tr>			   		   
			   </table>
			   </form>
			   ";
		}
		else {
			$tabela_atualiza= "
			<form action='movimento.vendas.php' id='confirmarEnvio' method='POST' name='confirmarEnvio' >	  
         	<input name='txtvenda' type='hidden' id='txtvenda' value='".$linha['venda']."'/>
			 <table border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse;' bordercolor='#999999'>
			  <tr>
				 <td class='titulo_detalhe_titulo' colspan='2'>Confirmar Envio</td>
			   </tr>			   	   
			   <tr>
				 <td class='titulo_detalhe'>Data do Envio</td>
				 <td class='classe_4'><input name='txtdataenvio' type='text'   onclick='showCalendarControl(this);' value='$tmp1'/></td>			 
			   </tr>	   
			   <tr>
				 <td class='titulo_detalhe'>Rastreio</td>
				 <td class='classe_4'><input name='txtrastreio' type='text' value='$tmp1_ras'/></td>			 
			   </tr>
			   <tr>
				 <td class='titulo_detalhe'>Observação</td>
				 <td class='classe_4'><textarea name='txtobs' cols='15' rows='2'>$tmp1_obs</textarea></td>			 
			   </tr>			   
			   <tr>
				 <td class='titulo_detalhe'>Mandar e-Mail</td>
				 <td class='classe_4'><input type='checkbox' name='txtmandaremail' value='mandar' id='txtmandaremail' /></td>			 
			   </tr>					   
			   <tr>
				 <td class='titulo_detalhe' colspan='2'><input name='gravar' type='button' value='Confirmar Envio' onclick=\"javascript: if(this.value == 'Confirmar Envio' ) {this.value='Aguarde...'; this.form.submit() } else { alert ('Aguarde submissão') }\"/></td>			 
			   </tr>				   			   
			   </table>
			    </form>";		
		}				   		   
		echo	"
		   <table border='0' cellspacing='10' cellpadding='0'>		
		   	<tr>
				<td valign='top'>$tabela_detalhes</td>
				<td valign='top'>$tabela_atualiza</td>
				<td valign='top'>$tabela_mensagens</td>				
			</tr>
			</table>
			";

		exit();
}
			
##############################################################################
##############                       ORDEM                   	##############
##############################################################################	

	if (isset($_SESSION["lista_ordem"]) && strlen($_SESSION["lista_ordem"])>0){
		$lista_ordem=$_SESSION['lista_ordem'];
		$desc=$_SESSION['ASC_DESC'];
	}
	if (isset($_GET['ordem']) && strlen($_GET['ordem'])>0){
		$lista_ordem = $_GET['ordem'];
		if (isset($_SESSION["lista_ordem"]) && strlen($_SESSION["lista_ordem"])>0){
			if ($_GET['ordem']==$_SESSION["lista_ordem"]){
				if (trim($desc)=="ASC")
					$desc=" DESC";
				else $desc=" ASC";
			}
		}
		$_SESSION['ASC_DESC'] = $desc;
		$_SESSION["lista_ordem"]= $lista_ordem;
	}
	
	if (strlen($lista_ordem)>0){
		switch ($lista_ordem){
			case "rasteio":
				$lista_ordem = " ORDER BY tbl_venda.rastreio $desc";
			 break;
			case "cliente":
				$lista_ordem = " ORDER BY tbl_venda.cliente $desc";
			 break;
			case "data":
				$lista_ordem = " ORDER BY tbl_venda.data $desc";
			 break;
 			 			 
		}
	}
	
	$sql_filtro = "";
	if (isset($_GET['filtro']) && strlen($_GET['filtro'])>0){
		$filtro = $_GET['filtro'];
		if ($filtro=='pendentes'){
			$sql_filtro = " WHERE 
							tbl_venda.rastreio IS NULL 
							AND tbl_venda.data_envio ='0000-00-00'
							AND pagamento_conferido=1";
		}
	}	
							
##############################################################################
##############                INCLUDES : CABECALHO             	##############
##############################################################################	

$layout="movimento";
$titulo="Controle de Vendas";
$sub_titulo="Controle de Vendas";

include "cabecalho.php";

##############################################################################
##############                INDEXA O TEMPLATE             	##############
##############################################################################	

$model->set_filenames(array('movimento.vendas' => 'movimento.vendas.htm'));


							
##############################################################################
##############             	  	 ACOES      	    	    	##############
##############################################################################	





if (isset($_POST['txtmensagem'])){	

	$txtvenda			=	trim($_POST['txtvenda']);
	$temp=(strlen(trim($_POST['txtmensagem']))==0)?'Mensagem em branco!':'';
	$txtmensagem			=	addslashes(trim($_POST['txtmensagem']));
	$msg_erro = $temp;
	if (strlen($msg_erro)==0){
			$query = "INSERT INTO tbl_mensagem   (venda,corpo,admin)
									values 	($txtvenda,'$txtmensagem',".$_SESSION["login_admin_codigo"].")";
			$rSet = $db->Query($query);				
			$msg_erro .= $db->MyError();
	}
	if (strlen($msg_erro)>0)
		$msg_erro = "<b>Ocorreu o seguinte erro na hora de atualizar o pagamento:</b><br> $msg_erro";		
}


if (isset($_POST['txtdatapagamento'])){	
	$txtvenda			=	trim($_POST['txtvenda']);
	$temp =(strlen(trim($_POST['txtdatapagamento']))==0)?'<br>Data do pagamento não especificado!':'';
	$txtdatapagamento			=	@converte_data(addslashes(trim($_POST['txtdatapagamento'])));
	
	$temp.=(strlen(trim($_POST['txtvalorpago']))==0)?'<br>Valor não especificado!':'';
	$txtvalorpago			=	addslashes(trim($_POST['txtvalorpago']));
	
	$temp.=(strlen(trim($_POST['txtbanco']))==0)?'<br>Banco não selecionado!':'';
	$txtbanco			=	addslashes(trim($_POST['txtbanco']));		
	
	$txtobs			=	addslashes(trim($_POST['txtobs']));		
	
	$txtmandaremail		=	trim($_POST['txtmandaremail']);			
		
	$msg_erro = $temp;
	if (strlen($msg_erro)==0){
			$query = "UPDATE tbl_venda SET		data_pago	= '$txtdatapagamento',
												banco		='$txtbanco',
												valor_pago		=$txtvalorpago,
												pagamento_conferido = 1,
												pagamento_obs = '$txtobs'
						WHERE 	venda	=	$txtvenda";
			$rSet = $db->Query($query);				
			$msg_erro = $db->MyError();
	}
	if (strlen($msg_erro)>0){
		$msg_erro = "<b>Ocorreu o seguinte erro na hora de atualizar o pagamento:</b><br> $msg_erro";	
	}
	else{
		include_once('ultramail.php');																
		$query = "SELECT tbl_venda.venda AS venda,
					 tbl_venda.cliente  AS cliente ,
					 tbl_venda.email   AS email,
					 tbl_venda.endereco  AS endereco,
					 tbl_venda.rastreio  AS rastreio,
					 tbl_venda.senha  AS senha,					 					 
					 tbl_venda.obs  AS obs,		
					 DATE_FORMAT(tbl_venda.data , '%d/%m/%Y') AS data,
					 DATE_FORMAT(tbl_venda.data_pago , '%d/%m/%Y') AS data_pago,					 
					 DATE_FORMAT(tbl_venda.data_envio , '%d/%m/%Y') AS data_envio,					 
					 tbl_produto.nome AS produto_nome,
					 tbl_produto.preco_venda   AS preco_venda
			 FROM tbl_venda
			 	JOIN tbl_produto ON tbl_produto.produto=tbl_venda.produto
			 WHERE tbl_venda.venda=$txtvenda;
					";	
		$rSet = $db->Query($query);
		$linha = $db->FetchArray($rSet);
		$linha_endereco = trim($linha['endereco']);
		$linha_cliente = trim($linha['cliente']);
		$linha_email = trim($linha['email']);
		$linha_senha = trim($linha['senha']);		
		$linha_produto_nome = trim($linha['produto_nome']);		
		$linha_rastreio = trim($linha['rastreio']);
		$linha_data_pago = ($linha['data_pago']=="00/00/0000")?"":$linha['data_pago'];								
		$linha_data_envio = ($linha['data_envio']=="00/00/0000")?"":$linha['data_envio'];
$corpo="Olá $linha_cliente, tudo bom?

Nós da TecnoMedia informamos ao Sr(a) que seu pagamento referente ao produto $linha_produto_nome foi confirmando com sucesso e nas próximas horas estará sendo enviado no seguinte endereço:

$linha_endereco

Caso tenha alguma dúvida, acesse: http://mercadolivre.telemediajp.com/index.php?email=$linha_email&senha=$linha_senha

Ou acesse: http://mercadolivre.telemediajp.com/ e entre com os dados:
Login: $linha_email
Senha: $linha_senha

Att

Fábio";
		if ($txtmandaremail=='mandar'){
		  if ( UltraMail( "$linha_cliente <$linha_email>", "$linha_produto_nome - Pagamento Confirmado", $corpo ) == TRUE ) 
			 $msg = "Pagamento confirmado! Um e-mail foi enviado ao cliente para informa-lo"; 
		  else 
			  $msg = 'ERRO DE ENVIO: ' . $UltraMailError; 	
		} else {	
			 $msg = 'Pagamento confirmado. Cliente não receberá email';
		}
	}
}

if (isset($_POST['txtdataenvio'])){	
	$txtvenda			=	trim($_POST['txtvenda']);
	$temp =(strlen(trim($_POST['txtdataenvio']))==0)?'<br>Data do envio não especificado!':'';
	$txtdataenvio			=	@converte_data(addslashes(trim($_POST['txtdataenvio'])));
	
	$temp.=(strlen(trim($_POST['txtrastreio']))==0)?'<br>Código de rastreio não especificado!':'';
	$txtrastreio			=	addslashes(trim($_POST['txtrastreio']));
	
	$txtobs			=	addslashes(trim($_POST['txtobs']));	
	
	$txtmandaremail		=	trim($_POST['txtmandaremail']);			
		
	$msg_erro = $temp;
	if (strlen($msg_erro)==0){
			$query = "UPDATE tbl_venda SET		data_envio	= '$txtdataenvio',
												rastreio		=UPPER('$txtrastreio'),
												envio_obs = '$txtobs'
						WHERE 	venda	=	$txtvenda";
			$rSet = $db->Query($query);				
			$msg_erro = $db->MyError();
	}
	if (strlen($msg_erro)>0){
		$msg_erro = "<b>Ocorreu o seguinte erro na hora de gravar o envio:</b><br> $msg_erro";	
	}
	else{
	$query = "SELECT tbl_venda.venda AS venda,
					 tbl_venda.cliente  AS cliente ,
					 tbl_venda.email   AS email,
					 tbl_venda.endereco  AS endereco,
					 tbl_venda.rastreio  AS rastreio,
					 tbl_venda.senha  AS senha,					 					 
					 tbl_venda.obs  AS obs,		
					 DATE_FORMAT(tbl_venda.data , '%d/%m/%Y') AS data,
					 DATE_FORMAT(tbl_venda.data_pago , '%d/%m/%Y') AS data_pago,					 
					 DATE_FORMAT(tbl_venda.data_envio , '%d/%m/%Y') AS data_envio,					 
					 tbl_produto.nome AS produto_nome,
					 tbl_produto.preco_venda   AS preco_venda
			 FROM tbl_venda
			 	JOIN tbl_produto ON tbl_produto.produto=tbl_venda.produto
			 WHERE tbl_venda.venda=$txtvenda;
					";	
		$rSet = $db->Query($query);
		$linha = $db->FetchArray($rSet);
		$linha_endereco = trim($linha['endereco']);
		$linha_cliente = trim($linha['cliente']);
		$linha_email = trim($linha['email']);
		$linha_senha = trim($linha['senha']);		
		$linha_produto_nome = trim($linha['produto_nome']);		
		$linha_rastreio = trim($linha['rastreio']);
		$linha_data_pago = ($linha['data_pago']=="00/00/0000")?"":$linha['data_pago'];								
		$linha_data_envio = ($linha['data_envio']=="00/00/0000")?"":$linha['data_envio'];
		
		if (strlen($txtobs)>0){
			$txtobs = "Observação: ".$txtobs;
		}
$corpo="Olá $linha_cliente, tudo bom?

Nós da TecnoMedia informamos ao Sr(a) que seu produto $linha_produto_nome foi enviado em $linha_data_envio com o seguinte código de rastreio: $linha_rastreio
Para rastrear, acesse: http://mercadolivre.telemediajp.com/index.php?email=$linha_email&senha=$linha_senha

Ou acesse: http://mercadolivre.telemediajp.com/ e entre com os dados:
Login: $linha_email
Senha: $linha_senha

Seu produto foi enviado para o seguinte endereço:

 $linha_endereco				
 
$txtobs 

Caso tenha alguma dúvida ou queira falar conosco, acesse:   http://mercadolivre.telemediajp.com/index.php?email=$linha_email&senha=$linha_senha


Att

Fábio";
		include_once('ultramail.php');	
		if ($txtmandaremail=='mandar'){
		  if ( UltraMail( "$linha_cliente <$linha_email>", "$linha_produto_nome - Produto Enviado", $corpo ) == TRUE ) 
			 $msg = "Envio confirmado! Um e-mail foi enviado ao cliente para informa-lo"; 
		  else 
			  $msg = 'ERRO DE ENVIO: ' . $UltraMailError; 		
		}else{
			 $msg = "Envio confirmado! Cliente não receberá e-mail"; 
		}
	}
}
##############################################################################
##############                      PAGINA                  	##############
##############################################################################	

####### PAGINAÇÂO - INICIO

	$query = "SELECT count(*)				
			 FROM tbl_venda
				JOIN tbl_produto ON tbl_produto.produto = tbl_venda.produto
				$sql_filtro
				";	
	$rSet = $db->Query($query);
	$linha = $db->FetchArray($rSet);
	$numero_registro = $linha[0];

	
	if (!isset($_GET['pg']) || empty($_GET['pg']))	$_GET['pg'] = 1;
	if ($_GET['pg']==0) $_GET['pg'] = 1;		
	$npp = 20;
	$paginaAtual = $_GET['pg'];
	$numero_paginas = ceil($numero_registro/$npp);
	
	$PAGINACAO = "";
	$tmp=$paginaAtual-1;
	if ($paginaAtual==1)
		$PAGINACAO .= "<span class='next'>&#171; Anterior</span>";
	if ($paginaAtual>1)
		$PAGINACAO .= "<a href='?pg=$tmp' class='next'  title='Voltar para a Página Anterior'><b>Anterior</b></a>";

	for ($i=1;$i<=$numero_paginas;$i++){
		if ($paginaAtual==$i){
			if ($numero_paginas>1) $PAGINACAO .= "<span class=\"current\">$i</span>";
			}
		else
			$PAGINACAO .= "<a href='?pg=$i' title='Ir para página $i'>$i</a>";
	}

	$tmp=$paginaAtual+1;
	if ($paginaAtual < $numero_paginas) $PAGINACAO .= " <a href='?pg=$tmp' class='next' title='Ir para a próxima página'><b>Próximo &#187;</b></a>";
	else								$PAGINACAO .= "<span class='next'>Próximo &#187;</span>";;
	
	$paginaAtual = ($paginaAtual-1)*$npp;
	$paginaLimite = $npp;
	
	$tmp1=$paginaAtual+1;
	$tmp2=$paginaAtual+$npp;	
	$model->assign_vars(array('PAGINACAO' => $PAGINACAO."<br><br>Venda <b>$tmp1</b> até <b>$tmp2</b> de um total de <b>$numero_registro</b>"));
####### PAGINAÇÂO - FIM

	$query = "SELECT tbl_venda.venda AS venda,
					 tbl_venda.cliente  AS cliente ,
					 tbl_venda.email   AS email,
					 tbl_venda.endereco  AS endereco,
					 tbl_venda.produto  AS produto,
					 tbl_venda.rastreio  AS rastreio,					 
					 tbl_venda.senha  AS senha,					 					 
					 tbl_venda.obs  AS obs,		
					 tbl_venda.star  AS star,							 			 
					 DATE_FORMAT(tbl_venda.data , '%d/%m/%Y') AS data,
					 DATE_FORMAT(tbl_venda.data_digitacao , '%d/%m/%Y') AS data_digitacao,
					 DATE_FORMAT(tbl_venda.data_pago , '%d/%m/%Y') AS data_pago,					 
					 DATE_FORMAT(tbl_venda.data_envio , '%d/%m/%Y') AS data_envio,					 
					 
					 tbl_produto.nome AS produto_nome,
					 tbl_produto.cor AS cor ,
					 tbl_produto.preco_compra  AS preco_compra,
					 tbl_produto.preco_venda   AS preco_venda,
					 tbl_produto.codigo   AS codigo
				
			 FROM tbl_venda
			 	JOIN tbl_produto ON tbl_produto.produto=tbl_venda.produto
				$sql_filtro
				ORDER BY data DESC
				LIMIT $paginaAtual,$paginaLimite
				";	
			 
	$rSet = $db->Query($query);
	$msg_erro .= $db->MyError();
	
	if (isset($msg) && strlen($msg)>0){
		$model->assign_vars(array('MSG' => "<br><div id='msg_ok'><img src='imagens/warning.gif' align='absmiddle' style='padding-right:5px'/> $msg</div>"));	
	}
	if (isset($msg_erro) && strlen($msg_erro)>0){
		$model->assign_vars(array('MSG' => "<br><div id='msg_erro'><img src='imagens/warning.gif' align='absmiddle' style='padding-right:5px'/> $msg_erro</div>"));	
	}	
	if (isset($filtro) && strlen($filtro)>0){	
				$model->assign_vars(array('FILTRO' => "<b style='font-size:15px;color:red'>ENVIO PENDENTES</b><br><br>"));
	}	
					
	$count=0;	
	while ($linha = $db->FetchArray($rSet)){
		  $model->assign_block_vars('venda', array(				'VENDA'			=>	$linha['venda'],
		  														'DATA'			=>	$linha['data'],
																'CLIENTE'		=>	$linha['cliente'],		  
																'EMAIL'			=>	$linha['email'],
																'SENHA'			=>	$linha['senha'],
																'PRODUTO'		=>	$linha['produto_nome'],
																'PREÇO'			=>	$linha['preco_compra'],
																'DATAPGTO'		=>	($linha['data_pago']=="00/00/0000")?"Pagamento Pendente":$linha['data_pago'],
																'DATAENVIO'		=>	($linha['data_envio']=="00/00/0000")?"Produto Não Enviado":$linha['data_envio'],
																'RASTREIO'		=>	$linha['rastreio'],																
																'STAR'			=>	$linha['star']?"1":"0",
																'CLASSE'		=>  ($count++%2==0)?"classe_1":"classe_2"
																));			  
	}
	if ($count==0){
		$model->assign_block_vars('naoencontrado', array('MSG'	=>	'Nenhum envio pendente!'));
	}				
																	  	
	

				
$model->pparse('movimento.vendas');

##############################################################################
##############                INCLUDES : RODAPE             	##############
##############################################################################	

include "rodape.php";


?>
