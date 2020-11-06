<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
//include 'funcoes.php';


if (strlen($_POST["btn_acao"]) > 0)  $btn_acao  = trim($_POST["btn_acao"]);

$deletar = $_GET['excluir'];

if (strlen ($deletar) > 0) {
	$sql = "DELETE FROM tbl_produto_fornecedor_admin
			WHERE  produto_fornecedor_admin = $deletar
			AND    produto_fornecedor       = $produto_fornecedor;";

	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);
	header("Location: $PHP_SELF?produto_fornecedor=$produto_fornecedor");
	exit;
}


if ($btn_acao == "gravar") {
	$produto_fornecedor  = trim($_POST["produto_fornecedor"]);

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$total_admin = count($admin);
		for($i=0;$i<$total_admin;$i++){
			$sql = "SELECT produto_fornecedor_admin
				FROM   tbl_produto_fornecedor_admin
				WHERE  produto_fornecedor = $produto_fornecedor
				AND    admin              = ".$admin[$i];
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 0) {
				$sql = "INSERT INTO tbl_produto_fornecedor_admin (
							produto_fornecedor ,
							admin
						) VALUES (
							$produto_fornecedor,
							".$admin[$i]."
						);";
			}
			$res = @pg_exec ($con,$sql);
			$msg_erro = @pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?produto_fornecedor=$produto_fornecedor");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
		
	}
}


$title = "Cadastro de Usuários dos Fornecedores de Produto";
$layout_menu = "cadastro";
include 'cabecalho.php';

?>
<style type="text/css">
.Relatorio{
	font-family: Verdana,sans;
	font-size:10px;
}
.Relatorio thead{
	background: #596D9B ;
	color:#FFFFFF;
}
</style>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?}


$sql = "SELECT  tbl_produto_fornecedor_admin.produto_fornecedor_admin,
				tbl_admin.admin                                      ,
				tbl_admin.nome_completo
		FROM tbl_produto_fornecedor_admin
		JOIN tbl_admin USING(admin)
		WHERE fabrica            = $login_fabrica
		AND   produto_fornecedor = $produto_fornecedor";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0) {
	$busca_array     = array();
	$localizou_array = array();
	for ($x = 0; $x < pg_numrows($res); $x++) {
		$admin   = pg_result($res,$x,admin);
		$busca_array[] = $admin;
	}
}

$sql = "SELECT * 
		FROM tbl_produto_fornecedor
		WHERE fabrica            = $login_fabrica
		AND   produto_fornecedor = $produto_fornecedor";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	for($i=0;$i<pg_numrows($res);$i++){
		$produto_fornecedor  = pg_result($res,$i,produto_fornecedor);
		$codigo              = pg_result($res,$i,codigo);
		$nome                = pg_result($res,$i,nome);
	}
}
?> 

<form name="frm_situacao" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="produto_fornecedor" value="<? echo $produto_fornecedor ?>">

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='700' border='0' class='Relatorio'>
			<tr  bgcolor="#596D9B" >
				<th align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro de Usuários dos Fornecedores de Produto</font></th>
			</tr>
			<tr  align='left'>
				<th>Código</th>
				<td align='left'><?=$codigo?></td>
			</tr>
			<tr  align='left'>
				<th>Nome</th>
				<td align='left'><?=$nome?></td>
			</tr>
			<tr align='left'>
				<th colspan='4'>
				<?
					$sql = "SELECT  admin,
									login,
									nome_completo
							FROM tbl_admin
							WHERE fabrica = $login_fabrica
							AND   ativo   = TRUE
							ORDER BY login";
					$res = pg_exec($con,$sql);
					$x = 1;
					if(pg_numrows($res)>0) {
						echo "<table >";
						echo "<tbody>";
						for($i=0;$i<pg_numrows($res);$i++) {
							$admin         = pg_result($res,$i,admin);
							$login         = pg_result($res,$i,login);
							$nome_completo = pg_result($res,$i,nome_completo);
							if(strlen($nome_completo)==0)$nome_completo = $login;
							$x ++;
							if($x==5) echo "<tr>";
							echo "<td><input type='checkbox' name='admin[]' value='$admin' ";
							if (sizeof($busca_array) > 0 and in_array($admin, $busca_array)) echo " CHECKED ";
							echo "></td>";
							echo "<td style='font-weight:none;font-size:10px;'>$nome_completo</td>";
							if($x==4){
								echo "</tr>";
								$x=1;
							}

						}
						echo "</tbody>";
						echo "</table>";
					}
				
				?>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>

<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_situacao.btn_acao.value == '' ) { document.frm_situacao.btn_acao.value='gravar' ; document.frm_situacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>


</center>

</form>

<? 
$sql = "SELECT  tbl_produto_fornecedor_admin.produto_fornecedor_admin,
				tbl_admin.admin                                      ,
				tbl_admin.nome_completo
		FROM tbl_produto_fornecedor_admin
		JOIN tbl_admin USING(admin)
		WHERE fabrica            = $login_fabrica
		AND   produto_fornecedor = $produto_fornecedor";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	echo "<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='700' border='0' class='Relatorio'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>Código</th>";
	echo "<th>Nome</th>";
	echo "<th>Ação</th>";
	echo "</tr>";

	echo "</thead>";
	echo "<tbody>";
	for($i=0;$i<pg_numrows($res);$i++){
		$produto_fornecedor_admin = pg_result($res,$i,produto_fornecedor_admin);
		$codigo                   = pg_result($res,$i,admin);
		$nome                     = pg_result($res,$i,nome_completo);

		if($cor <>'#FFFFFF') $cor = '#FFFFFF';
		else                 $cor = '#dddddd';

		echo "<tr bgcolor='$cor'>";
		echo "<td>$codigo</td>";
		echo "<td>$nome</td>";
		echo "<td><a href='$PHP_SELF?excluir=$produto_fornecedor_admin&produto_fornecedor=$produto_fornecedor'>Deletar</a></td>";
		echo "</tr>";
	}
	echo "</tbody>";
}
include "rodape.php";
?>