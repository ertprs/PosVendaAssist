<?
/******************************************************************
Script .........: Encerra a sess�o
Por ............: Fabio Nowaki
Data ...........: 31/07/2006
******************************************************************/

	//INICIALIZA A SESS�O
	session_start();
	
	//VERIFICA SE AS VARI�VEIS EST�O REGISTRADAS
	//if( (!empty($_SESSION["sess_login"])) && (!empty($_SESSION["sess_nome"])) ) {
		//DESTR�I A SESS�O
		$destroi	=	session_destroy();
	//} //FECHA IF
	//header("Location: index.php");
	echo "<html><head><script language='javascript'>window.close()</script><body></body></head><html>";
?>

