<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$layout_menu = "callcenter";
$title       = "Pedido Cadastro Permissão";

#HD 273876
if(strlen($_POST['btn_acao'])>0) $btn_acao = $_POST['btn_acao'];
else                             $btn_acao = $_GET['btn_acao'];

if($btn_acao=="gravar"){
	if(strlen($_POST['linhas'])>0) $linhas = $_POST['linhas'];
	else                           $linhas = $_GET['linhas'];

	for($x=0; $x<$linhas; $x++){
		$admin     = $_POST['admin_'.$x];
		$permissao = $_POST['permissao_'.$x];

		if(strlen($permissao)>0) $permissao = "t";
		else                     $permissao = "f";

		if(strlen($admin)>0){
			$sql = "UPDATE tbl_admin SET altera_pedido = '$permissao' WHERE admin = $admin AND fabrica = $login_fabrica;";
			#echo nl2br($sql);
			$res = pg_exec($con,$sql);
		}
	}
}

include "cabecalho.php";

?>

<style>
	.tabelas td{
		font-family: arial;
		font-size: 12px;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
</style>

<?

$sql = "SELECT tbl_admin.admin        ,
			   tbl_admin.nome_completo,
			   tbl_admin.login        ,
			   tbl_admin.altera_pedido
		FROM tbl_admin
		WHERE tbl_admin.ativo IS TRUE
		AND   tbl_admin.fabrica = $login_fabrica
		ORDER BY tbl_admin.login ASC";
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){
	
	echo "<BR>";
	echo "<FORM METHOD='POST' NAME='frm_permissao' ACTION='$PHP_SELF'>";

		$linhas = pg_numrows($res);
		echo "<INPUT TYPE='hidden' NAME='linhas' VALUE='$linhas'>";

		echo "<TABLE border='0' cellpadding='4' cellspacing='1' align='center' class='tabelas'>";
		echo "<TR class='titulo_tabela'>";
			echo "<TD>Nome Completo</TD>";
			echo "<TD>Login</TD>";
			echo "<TD>Permissão</TD>";
		echo "</TR>";
		for($i=0; $i<pg_numrows($res); $i++){
			$admin         = pg_result($res,$i,admin);
			$nome_completo = pg_result($res,$i,nome_completo);
			$login         = pg_result($res,$i,login);
			$xpermissao    = pg_result($res,$i,altera_pedido);

			if($i%2) $cor = "#CECEFF";
			else     $cor = "#E8E8E8";

			echo "<TR bgcolor='$cor'>";
				echo "<TD>$nome_completo</TD>";
				echo "<TD>$login</TD>";
				echo "<TD>";
					echo "<INPUT TYPE='checkbox' NAME='permissao_$i' VALUE='t'";
					if($xpermissao=="t") echo "checked";
					echo ">";
					echo "<INPUT TYPE='hidden' NAME='admin_$i' VALUE='$admin'>";
				echo "</TD>";
			echo "</TR>";
		}
		echo "<TR>";
			echo "<td colspan='3' align='center'>";
					?>
					<INPUT TYPE="hidden" NAME="btn_acao">
					<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_permissao.btn_acao.value == '' ) { document.frm_permissao.btn_acao.value='gravar' ; document.frm_permissao.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Gravar formulário" border='0' >
					<?
			echo "</td>";
		echo "</TR>";
		echo "</TABLE>";
	echo "</FORM>";

}

?>

<? include "rodape.php"; ?>