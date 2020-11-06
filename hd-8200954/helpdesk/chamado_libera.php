<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>
<head>
<title>Liberação de Chamadas - Telecontrol Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<body>
<?
include "menu.php";
?>


<?
$libera = trim ($_POST['libera']);
if (strlen($libera)>0){
	echo 'Liberado: '.$libera;
}

$id = 509108 ;
$categoria ="Cobrança";
$titulo ="dominio adicional acaocidadao.org.br";
$status = "Criado";
$criacao = " 07/03/06";
$prazo = "08/03/06";


?>

<?
$sql = "SELECT * from tbl_hd_chamadas ";

$res = @pg_exec ($con,$sql);
if (@pg_numrows($res) > 0) {


/*--===============================TABELA DE CHAMADOS========================--*/
	echo "<br>";
	echo "<form name='frm_chamada' action='$PHP_SELF' method='post'>";
	echo "<table width='98%' align='center'cellpadding='1' cellspacing='1' border='0' bordercolor='CCCCCC' class='pontilhado'>";
	echo "<tr><td colspan =  '6' class = 'Titulo2' align = 'center'  height='30'>Chamados Aguardando Liberação</td></tr>";
	echo "<tr bgcolor='DDDDDD' align = 'center' class='Tabela_titulo'>";
	echo "<td>ID</td>";
	echo "<td>Categoria</td>";
	echo "<td>Título</td>";
	echo "<td>Status</td>";
	echo "<td>Criação</td>";
	echo "<td>Libera</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$posto                = pg_result($res,$i,posto);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$fabrica_responsavel  = pg_result($res,$i,fabrica_responsavel);

		echo "<tr bgcolor='eeeeee' align = 'center' >";
		echo "<td>$id</td>";
		echo "<td>$categoria</td>";
		echo "<td>$titulo</td>";
		echo "<td>$status</td>";
		echo "<td>$criacao</td>";
		echo "<td align='center'><input type='checkbox' name='libera' value='1' class='botao'></td>";
		echo "</tr>";
	}
	echo "</table>"; //fim da tabela de chamadas
	echo "<br><center><input type='submit' name='btn_acao' value='Gravar' class = 'botao'></center>";
	echo "</form>";
}
?>
		</td>
	</tr>
<tr>
		<td height ='7' colspan='2' class='rodape' align='center'>TELECONTROL NETWORKING LTDA - <?=date(Y)?></td>
	</tr>
</table>
</body>