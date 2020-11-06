<?
include "/www/assist/www/dbconfig.php";
include "/www/assist/www/includes/dbconnect-inc.php";
require( '/www/assist/www/class_resize.php' );

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<?

$mail         = trim($_GET['email']);

if(strlen($mail) > 0){
	$sql = "SELECT DISTINCT tbl_posto.posto        ,
				tbl_posto.nome         ,
				tbl_posto.cnpj
			FROM tbl_posto 
			WHERE email = '$mail'
			AND posto <> 4928 
			ORDER BY posto ASC limit 1;";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		$posto      = pg_result($res,0,posto);
		$nome_posto  = pg_result($res,0,nome);
		$cnpj        = pg_result($res,0,cnpj);

		if(strlen($posto) > 0){
			$sql3 = "insert into tbl_posto_fabrica (fabrica, posto, login_provisorio, senha, codigo_posto, tipo_posto ) 
						VALUES (47, $posto, true, '*',  $cnpj, 171); ";
			$res3 = pg_exec($con,$sql3);
	//		echo "$sql3";
		}

		if(strlen($nome) == 0) $from = $nome;
		else $from = $mail;

		echo "<br><br><br><br>";

		if(strlen($msg_erro) == 0){
			echo "<table width='600' align='center' style='font-family: verdana; font-size: 12px'>
					<tr>
						<td style='font-size: 16' align='center'><b>$nome_posto</b></td>
					</tr>
					<tr>
						<td align='center'>
							Parabéns por compor a mais nova rede de assistência técnica de ferramentas do país.<br> 
							Em breve você receberá o contrato de prestação de serviços, posteriormente login e senha de acesso ao sistema Telecontrol.
						<td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td align='center'>Dúvidas utilize o endereço:<br> <a href='mailto:suporte@crownferramentas.com.br'><b>suporte@crownferramentas.com.br</b></a></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td align='center'><a href=\"javascript: this.close();\">Fechar</a></td>
					</tr>
				</table>"; 
		}else{
			echo "<p align='center'>$msg_erro</p>";
		}
	}
}
?>