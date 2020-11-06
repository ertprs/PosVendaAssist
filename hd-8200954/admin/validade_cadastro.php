<? 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


	if($ip=="201.42.112.110"){ 
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
				$msg_validade_cadastro="<a href='alterar_senha.php'><font size='1' face='arial,verdana'>Sua senha irá expirar em $data_expira_senha dias. Clique aqui para cadastrar uma senha nova.</font></a>";
			}
	//echo "$msg_validade_cadastro";
		}
	}
	//takashi

?>