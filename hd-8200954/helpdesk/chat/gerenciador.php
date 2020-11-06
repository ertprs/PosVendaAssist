<?

session_start();	
$msg_erro="";
require_once('autentica_adm.php');
require_once('banco.inc.php');
  
if (isset($_POST["Cadastrar"])) {
	$txtnome	 =	addslashes(trim($_POST["txtnome"]));
	$txtlogin	 =	addslashes(trim($_POST["txtlogin"]));
	$txtsenha	 =	addslashes(trim($_POST["txtsenha"]));
	$txtemail	 =	addslashes(trim($_POST["txtemail"]));
	$txtnivel	 =	addslashes(trim($_POST["txtnivel"]));	

	if (strlen($txtnome)==0)	$msg_erro .= "<br>Nome não preenchido.";	
	if (strlen($txtlogin)==0)	$msg_erro .= "<br>Login não preenchido.";	
	if (strlen($txtsenha)==0)	$msg_erro .= "<br>Senha não preenchido.";	
	if (strlen($txtemail)==0)	$msg_erro .= "<br>Email não preenchido.";	
	if (strlen($txtnivel)==0)	$msg_erro .= "<br>Nivel não preenchido.";	
   	
	$txtsenha	 = md5($txtsenha);
	
  	if (strlen($msg_erro==0)){
  	   $rSet = $db->Query("INSERT INTO chat (nome,email,senha,nick,data,login,nivel) VALUES ('$txtnome','$txtemail','$txtsenha','-',now(),'$txtlogin','$txtnivel')");
	}
}

$usuarios="";
$rSet = $db->Query("SELECT codigo,nome,email,data,nick,nivel FROM chat order by codigo");
if ($db->NumRows($rSet) > 0){
	while ($row = $db->FetchArray($rSet)){
		$usuarios .= $row['codigo'].") ".$row['nome']."<br>";
		$usuarios .= "email: ".$row['email']." - Ultimo Nick: ".$row['nick']." (".$row['data'].")<br>";
		$usuarios .= "Nivel: ".$row['nivel']."<br><hr>";
	}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>FN - TELEMEDIABR</title>
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
  <form id="Cadastrar" name="Cadastrar" method="post" action="gerenciador.php"  onKeyUp="highlight(event)" onClick="highlight(event)" >
  		<span><? echo $msg_erro; ?></span> <br />
      	<label for="txtnome">nome</label>  	    
    	<input type="text" name="txtnome" /><br />
    	<label for="txtemail">e-Mail</label>
    	<input type="text" name="txtemail" /><br />
      	<label for="txtsenha">Senha</label>  	    
    	<input type="text" name="txtsenha" /><br />
    	<label for="txtnivel">Nivel</label>
    	<input type="text" name="txtnivel" /><br />
      	<label for="txtlogin">login</label>  	    
    	<input type="text" name="txtlogin" /><br />
  		<input name="Cadastrar" type="submit" id="Cadastrar" value="Cadastrar" class="botao"/>
  </form>
</div>
</div>
<br />
<b>Usuario cadastrados:</b><br />
<br />

<?
echo $usuarios;
?>

</body>

</html>




