<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


print urlencode("Programa: <b>".$_GET['digito']."</b><br><br>");
$hd_chamado = $_GET['hd_chamado'];
$sql= "SELECT * FROM tbl_ARQUIVO where descricao like '%".$_GET['digito']."%'ORDER BY DESCRICAO";

$res = pg_exec ($con,$sql);

if(pg_numrows($res)>0){
	$resposta .= "<table>";
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
		$arquivo   = trim(pg_result($res,$i,arquivo));	
		$descricao = trim(pg_result($res,$i,descricao));	
		 $msg_erro = "";
	
		$sql2= "SELECT * 
			FROM tbl_controle_acesso_arquivo
			JOIN tbl_arquivo USING (arquivo)
			JOIN tbl_admin USING (admin)
			WHERE tbl_controle_acesso_arquivo.status  = 'em uso' 
			AND   tbl_controle_acesso_arquivo.arquivo = $arquivo";

		$res2 = pg_exec ($con,$sql2);
		if(@pg_numrows($res2)>0){
			$msg_erro = "<font color='FF0000' size='1'><b>EM USO</b></font>";
		}

		$resposta .= "<tr>";
		$resposta .= "<td><font size='1'>";
		$resposta .= substr_replace($descricao, '', 0, 9);
		$resposta .= "</font></td>";
		$resposta .= "<td nowrap>";
		if(strlen($msg_erro)>0) $resposta .= $msg_erro;
		else                    $resposta .= "<a href=\"javascript:Exibir('dados','$arquivo','',$hd_chamado);\">Requisistar</a>";
		$resposta .= "</td>";
		$resposta .= "</tr>";
		
		
		
		
	}
	$resposta .= "</table>";
	print urlencode($resposta);
}
