<?
session_start();	
require_once('autentica_admin.php');
require_once('banco.inc.php');

$msg_erro="";
  
if (isset($_POST["Nickar"])) {
	$nick = addslashes(trim($_POST["txtnick"]));
	
	if (strlen($nick)<3){
		$msg_erro .='Erro: digite 4 ou mais caracteres para seu nick!';
	}
	
	if (strlen($msg_erro)==0){

		$sql = "SELECT login FROM tbl_admin WHERE admin=$login_admin";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) > 0){
			$_SESSION["sess_nome"] = pg_result ($res,0,0);
		}

		$_SESSION["sess_login"]       = $login_admin;
		$_SESSION["sess_nick_ultimo"] = $nick;
		$_SESSION["sess_nick"] = $nick;
		$_SESSION["sess_email"]       = 'email';
		$_SESSION["sess_ultimo_aces"] = date("Y-m-d H:i:m");
		$_SESSION["sess_codigo"]      = $login_admin;
		$_SESSION["sess_data"]        = date("Y-m-d H:i:m");

		$sql = "INSERT INTO chat_acesso (nome,nick,data) VALUES ('$login_admin','$nick',NOW())";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);



		header("Location: index.php");
		exit();
	}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Telecontrol Suporte Live</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="estilo.css" rel="stylesheet" type="text/css" />
<script language="JavaScript1.2">
var highlightcolor="lightblue"
var ns6=document.getElementById&&!document.all
var previous=''
var eventobj
//Regular expression to highlight only form elements
var intended=/INPUT|TEXTAREA|SELECT|OPTION/
//Function to check whether element clicked is form element
function checkel(which){
	if (which.style&&intended.test(which.tagName)){
		if (ns6&&eventobj.nodeType==3)
			eventobj=eventobj.parentNode.parentNode
		return true
	}
	else
	return false
}
//Function to highlight form element
function highlight(e){
	eventobj=ns6? e.target : event.srcElement
	if (previous!=''){
		if (checkel(previous))
			previous.style.backgroundColor=''
		previous=eventobj
		if (checkel(eventobj))
			eventobj.style.backgroundColor=highlightcolor
	}
	else{
		if (checkel(eventobj))
			eventobj.style.backgroundColor=highlightcolor
		previous=eventobj
	}
}
</script>
</head>

<body>
<div class="formLogin">
<div class="formulario"><span><? echo $msg_erro; ?></span> <br />
  <form id="Nickar" name="Nickar" method="post" action="nick.php"  onKeyUp="highlight(event)" onClick="highlight(event)" >
      	<label for="txtnick">Escolha seu nick</label>  	    
    	<input type="text" name="txtnick" /><br />
  		<input name="Nickar" type="submit" id="Nickar" value="Nickar" class="botao"/>
  </form>
</div>
</div>
</body>

</html>




