<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if(strlen($_POST['btn_acao'])>0) $btn_acao = trim($_POST['btn_acao']);
else                             $btn_acao = trim($_GET ['btn_acao']);

if(strlen($_POST['cod_produto'])>0) $cod_produto = $_POST['cod_produto'];
else                                $cod_produto = $_GET ['cod_produto'];

if(strlen($_POST['nome_origem'])>0) $nome_origem = $_POST['nome_origem'];
else                                $nome_origem = $_GET ['nome_origem'];

if(strlen($_POST['email_origem'])>0) $email_origem = $_POST['email_origem'];
else                                 $email_origem = $_GET ['email_origem'];

if(strlen($_POST['email_destino'])>0) $email_destino = $_POST['email_destino'];
else                                  $email_destino = $_GET ['email_destino'];

if(strlen($_POST['assunto'])>0)     $assunto = $_POST['assunto'];
else                                $assunto = $_GET ['assunto'];

if(strlen($_POST['mensagem'])>0) $mensagem = $_POST['mensagem'];
else                             $mensagem = $_GET ['mensagem'];

if(strlen($_POST['informacoes'])>0) $informacoes = $_POST['informacoes'];
else                                $informacoes = $_GET ['informacoes'];

if(strlen($_POST['codigo_produto'])>0) $codigo_produto = $_POST['codigo_produto'];
else                                   $codigo_produto = $_GET ['codigo_produto'];

if(strlen($_POST['descricao_produto'])>0) $descricao_produto = $_POST['descricao_produto'];
else                                      $descricao_produto = $_GET ['descricao_produto'];

if($btn_acao=="Enviar"){
	if(strlen($nome_origem)==0)   $msg_erro  = " Informe o campo Nome "."<BR>";
	if(strlen($email_origem)==0)  $msg_erro .= " Informe o campo Email"."<BR>";
	if(strlen($email_destino)==0) $msg_erro .= " Informe o campo Email Destino "."<BR>";
	if(strlen($assunto)==0)       $msg_erro .= " Informe o campo Assunto "."<BR>";
	if(strlen($mensagem)==0)      $msg_erro .= " Informe o campo Descrição "."<BR>";

	if(strpos($email_origem,  "@")==0)  $msg_erro .= " Email Invalido"."<BR>";
	if(strpos($email_destino, "@")==0) $msg_erro .= " Email Destino Invalido"."<BR>";

	if(strlen($msg_erro)==0){
		$corpo.= $nome_origem." indicou um produto da loja virtual da Telecontrol.";
		$corpo.="<BR><BR>\n\n";
		$corpo.= $codigo_produto . ' - ' . $descricao_produto;
		$corpo.="<BR><BR>\n\n";
		$corpo.= $mensagem;
		$corpo.="<BR>\n\n";
		$corpo.= $informacoes;
		$corpo.="<BR>\n_______________________________________________<BR>\n";
		$corpo.="Loja Virtual Telecontrol<BR>\n";
		$corpo.="www.telecontrol.com.br\n";

		$body_top  = "MIME-Version: 1.0\n";
		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=\"ISO-8859-1\"\n\n";

		$send = mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " );
		$msg_erro= "Email enviado com sucesso!";
	}
}

//CARREGA DADOS DA PEÇA
if(strlen($cod_produto)>0){
	$sql = "SELECT  
			tbl_peca.peca            ,
			referencia              ,
			tbl_peca.ipi            ,
			descricao               ,
			estoque                 ,
			garantia_diferenciada   , 
			informacoes             ,
			linha_peca              ,
			multiplo_site           ,
			qtde_minima_site        ,
			qtde_max_site           ,
			qtde_disponivel_site    ,
			preco_anterior
		FROM tbl_peca 
		WHERE tbl_peca.peca='$cod_produto'";
	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		$peca						= trim(pg_result ($res,0,peca));
		$referencia					= trim(pg_result ($res,0,referencia));
		$ipi						= trim(pg_result ($res,0,ipi));
		$descricao					= trim(pg_result ($res,0,descricao));
		$estoque					= trim(pg_result ($res,0,estoque));
		$garantia_diferenciada		= trim(pg_result ($res,0,garantia_diferenciada));
		$informacoes				= trim(pg_result ($res,0,informacoes));
		#$informacoes				= nl2br($informacoes);
		$linha						= trim(pg_result ($res,0,linha_peca));
		$multiplo_site 				= trim(pg_result ($res,0,multiplo_site));
		$qtde_minima_site			= trim(pg_result ($res,0,qtde_minima_site));
		$qtde_max_site				= trim(pg_result ($res,0,qtde_max_site));
		$qtde_disponivel_site		= trim(pg_result ($res,0,qtde_disponivel_site));
		$preco_anterior				= trim(pg_result ($res,0,preco_anterior)); #HD 13429
		
		$assunto = "Recomendação do produto ".$referencia;
	}
}
?>
<style>
body{
	margin: 0px;
	font-family: arial;
	font-size: 11px;
}
#topo{
	margin: 0px;
	font-family: arial;
	font-size: 11px;
}

#Erro{
	color: #FFFFFF;
	background-color: #FF3300;
	font-family: arial;
	font-size: 14px;
	font-weight:bold;
}

td{
	font-family: arial;
	font-size: 11px;
}
.top{
	background-color: #005f9d;
	background-image: url('helpdesk/imagem/fundo_dh2.jpg');
}
</style>

<div width='100%' class='top'><img src='helpdesk/imagem/fundo_dh5.jpg'></div>

<?
	if(strlen($msg_erro)>0){
		echo "<div id='Erro'>$msg_erro</div>";
	}
?>

<FORM METHOD="POST" ACTION="<? echo $PHP_SELF; ?>">
	<table border='0' cellpadding='2' cellspacing='2' align="center">
		<? if(strlen($caminho)>0){ ?>
		<TR>
			<TD colspan='2'><INPUT TYPE="image" SRC="<? echo $caminho ?>"></TD>
		</TR>
		<? } ?>
		<TR>
			<TD>Nome:</TD>
			<TD><INPUT TYPE="text" NAME="nome_origem" VALUE="<? echo $nome_origem; ?>" SIZE="50" MAXLENGTH="100"></TD>
		</TR>
		<TR>
			<TD>Email:</TD>
			<TD><INPUT TYPE="text" NAME="email_origem" VALUE="<? echo $email_origem; ?>" SIZE="50" MAXLENGTH="100"></TD>
		</TR>
		<TR>
			<TD>Email Destino:</TD>
			<TD><INPUT TYPE="text" NAME="email_destino" VALUE="<? echo $email_destino; ?>" SIZE="50" MAXLENGTH="100"></TD>
		</TR>
		<TR>
			<TD>Assunto:</TD>
			<TD><INPUT TYPE="text" NAME="assunto" VALUE="<? echo $assunto; ?>" SIZE="50" MAXLENGTH="100"></TD>
		</TR>
		<TR>
			<TD colspan="2" align="center">Descrição:<BR>
				<TEXTAREA NAME="mensagem" ROWS="10" COLS="50">
				<? 
				if(strlen($mensagem)>0) echo $mensagem;
				?>
				</TEXTAREA>
			</TD>
		</TR>
		<TR>
			<TD colspan="2" align="center">
				<INPUT TYPE="submit" NAME="btn_acao" VALUE="  Enviar  ">
				<INPUT TYPE="hidden" NAME="codigo_produto" VALUE="<? echo $referencia; ?>">
				<INPUT TYPE="hidden" NAME="descricao_produto" VALUE="<? echo $descricao; ?>">
				<INPUT TYPE="hidden" NAME="informacoes" VALUE='<? echo $informacoes; ?>'>
			</TD>
		</TR>
	</TABLE>
</FORM>