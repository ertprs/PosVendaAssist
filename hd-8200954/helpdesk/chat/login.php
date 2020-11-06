<?

session_start();	
$msg_erro="";
require_once('banco.inc.php');
  
if (isset($_POST["Logar"])) {
	$usuario =	addslashes(trim($_POST["txtlogin"]));
	$email	 =	addslashes(trim($_POST["txtemail"]));
   	$senha = md5($senha);

	$sql = "SELECT nome,email,data,nick FROM chat_acesso WHERE nome='$usuario' AND email='$email'";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

   	if (pg_numrows ($res) > 0){
	   $_SESSION["sess_login"]	= $usuario;
	   $_SESSION["sess_nome"]	= pg_result ($res,0,nome);
	   $_SESSION["sess_nick_ultimo"]= pg_result ($res,0,nick);
	   $_SESSION["sess_email"]	= pg_result ($res,0,email);
	   $_SESSION["sess_ultimo_aces"]= pg_result ($res,0,data);
	   $_SESSION["sess_codigo"]	= $usuario;
	   $_SESSION["sess_data"]	= date("Y-m-d H:i:m");
	   header("Location: index.php");
	   exit();
 	}
	else {
	      $msg_erro .= "Login ou senha incorretos.";
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
<div class="formulario">
  <form id="Logar" name="Logar" method="post" action="login.php"  onKeyUp="highlight(event)" onClick="highlight(event)" >
  		<span><? echo $msg_erro; ?></span> <br />
      	<label for="txtlogin">Nome de usuário</label>  	    
    	<input type="text" name="txtlogin" /><br />
    	<label for="txtemail">eMail</label>
  		<input type="password" name="txtemail" /><br />
  		<input name="Logar" type="submit" id="Logar" value="Logar" class="botao"/>
  </form>
</div>
</div>
</body>

</html>




