<? 

$hoje = date("Y-m-d");

$sql="select SUM(data_expira_senha-'$hoje') as data from tbl_admin where admin=$login_admin;";
$res = @pg_exec($con, $sql);
$data_expira_senha= pg_result($res,0,data);
if($data_expira_senha<0){
	//header("Location: alterar_senha.php");
	//exit;
	include "alterar_senha.php";
	exit;
//	echo "senha expirou $data_expira_senha";
}else{
	
	if (strlen($msg_validade_cadastro)==0){
		$msg_validade_cadastro="<a href='alterar_senha.php'><font size='1' face='arial,verdana'>Su contrase�a ir� caducar en $data_expira_senha d�as. Click aqu� para registrar una nueva contrase�a.</font></a>";
	}
//echo "$msg_validade_cadastro";
}
	//takashi

?>