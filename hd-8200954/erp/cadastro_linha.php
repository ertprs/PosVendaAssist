<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'cadastros') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>
<style>
a{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 12px;
	
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#FFF;
}
.Titulo_Colunas{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}
.ok{
	font-family: Verdana;
	font-size: 12px;
	color:blue;
	border:#39AED5 1px solid; background-color: #B0DFEE;
}
img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}

</style>
<?

echo "<h2><center>A manutenção deste cadastro é realizado pelo TELECONTROL, favor enviar e-mail suporte@telecontrol.com.br</center></h2>";

	$sql = "SELECT linha,nome
			FROM tbl_linha
			WHERE fabrica = $login_empresa
			ORDER BY nome ASC";

	$res = pg_exec ($con,$sql) ;

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' class='tabela'>";
		echo "<caption>";
		echo "Relação das Linha Cadastradas";
		echo"<BR>";
		echo"(A linha se refere ao ramo de atividade que a sua loja atende)";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#95ACCE'>";
		echo "<td align='center' class='Titulo_Colunas'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Colunas'><b>Nome</b></td>";

		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$nome      = trim(pg_result($res,$k,nome));
			$linha     = trim(pg_result($res,$k,linha));
			
			echo "<tr>";
			echo "<td align='center'><input type='hidden' name='marca' value='$marca'>$linha</td>";
			echo "<td align='left'  >$nome</td>";

			echo "</tr>";

		}
		echo "</table>";
	}else{
		echo "Nenhuma Linha cadastrada.";
	}

include "rodape.php";


?>