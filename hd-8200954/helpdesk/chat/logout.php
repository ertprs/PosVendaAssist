<?
/******************************************************************
Script .........: Encerra a sessão
Por ............: Fabio Nowaki
Data ...........: 31/07/2006
******************************************************************/

	//INICIALIZA A SESSÃO
	session_start();
	
	//VERIFICA SE AS VARIÁVEIS ESTÃO REGISTRADAS
	//if( (!empty($_SESSION["sess_login"])) && (!empty($_SESSION["sess_nome"])) ) {
		//DESTRÓI A SESSÃO
		$destroi	=	session_destroy();
	//} //FECHA IF
	//header("Location: index.php");
	echo "<html><head><script language='javascript'>window.close()</script><body></body></head><html>";
?>

