<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

if($_POST['fornecedor']) $fornecedor = trim ($_POST['fornecedor']);
if($_GET ['fornecedor']) $fornecedor = trim ($_GET ['fornecedor']);

//include "menu.php";

	$sql = "select
		tbl_pessoa_fornecedor.pessoa   ,
		tbl_pessoa.nome                ,
		tbl_pessoa.email               ,
		tbl_cotacao_fornecedor.cotacao
		from tbl_pessoa_fornecedor
		JOIN tbl_pessoa
		ON tbl_pessoa.pessoa = tbl_pessoa_fornecedor.pessoa
		JOIN tbl_cotacao_fornecedor 
		ON tbl_cotacao_fornecedor.pessoa_fornecedor = tbl_pessoa_fornecedor.pessoa
		where tbl_pessoa_fornecedor.pessoa = $fornecedor ";

	$res = pg_exec($con,$sql);
	
	if(pg_numrows($res)>0){
		$xemail     = trim(pg_result($res,0,email));
		$xnome      = trim(pg_result($res,0,nome));
		$cotacao    = trim(pg_result($res,0,cotacao));
		$fornecedor = trim(pg_result($res,0,pessoa));
	}

	$key=md5($fornecedor.$cotacao.$login_empresa);
	
	$email_origem  = "cotacao@tecnoplus.com.br";
	$assunto       = "COTAÇÃO";
	$email_destino = $xemail;

	$corpo.="<br>\n";
	$corpo.="<br>Sistema de COTAÇÃO ON-LINE\n";
	$corpo.="<br>\n";
	$corpo.="<br>Prezado Fornecedor $xnome, <br>\n";
	$corpo.="<br>\n";
	$corpo.="<br>Estamos enviado este email com a oportunidade.........\n";
	$corpo.="<br>\n";
	$corpo.="<br>Acesse o link abaixo para abrir a Cotação e preencha com seus preço.\n";
	$corpo.="<br><a href='http://201.77.210.68/assist/erp/fornecedor_cotacao.php?cotacao=$cotacao&fornecedor=$fornecedor&key=$key'><FONT SIZE='2' COLOR='#CC0033'><B>Clique aqui para fazer a cotação</B></FONT></a>.\n";
	$corpo.="<br>_______________________________________________\n";
	$corpo.="<br><br>Tecnoplus\n";
	$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

	$body_top  = "--Message-Boundary\n";
	$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
	$body_top .= "Content-transfer-encoding: 7BIT\n";
	$body_top .= "Content-description: Mail message body\n\n";
	mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " );

	echo "<html><head></head><body>\n";
	echo "<FONT SIZE='4'><B>Email encaminhado com sucesso para $xnome</B></FONT>\n";
	echo "<script language='javascript'> setTimeOut('this.close()',1000); </script>\n";
	echo "</body></html>\n";
	
?>
