<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}



if (strlen ($btn_acao) > 0) {

	if($_POST['programa']) $programa = trim ($_POST['programa']);
	if($_POST['help']) $help = nl2br(trim ($_POST['help']));

	if (strlen($programa) == 0){
		$msg_erro="Por favor inserir o ENDEREÇO do help";
	}
	if (strlen($help) == 0){
		$msg_erro="Por favor inserir o TEXTO do help";
	}
	if(strlen($msg_erro)==0){
		if(strlen($fabrica)>0){
			$sql = "SELECT * 
					FROM tbl_help 
					WHERE fabrica  = $fabrica
					AND   programa = '$programa'";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==0){
					$sql =	"INSERT INTO tbl_help (
							programa    ,
							help        ,
							fabrica
						) VALUES (
							'$programa' ,
							'$help'     ,
							$fabrica
					)";
			}else{
				$sql = "UPDATE tbl_help SET 
							help = '$help'
						WHERE programa = '$programa'
						AND   fabrica  = $fabrica";
			}
		}else{
			$sql = "SELECT * 
					FROM tbl_help 
					WHERE programa = '$programa'
					AND   fabrica IS NULL";
			$res = pg_exec($con,$sql);
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==0){
					$sql =	"INSERT INTO tbl_help (
							programa    ,
							help
						) VALUES (
							'$programa' ,
							'$help'
					)";
			}else{
				$sql = "UPDATE tbl_help SET 
							help = '$help'
						WHERE programa = '$programa'
						AND   fabrica  IS NULL";
			}
		}
		//echo $sql;exit;
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
	}
}

if(strlen($_GET["programa"])>0){
	$programa   = $_GET["programa"];
	$hd_chamado = $_GET["hd_chamado"];

	if(strlen($hd_chamado)>0){
		$sql = "SELECT fabrica
				FROM tbl_hd_chamado
				WHERE hd_chamado = $hd_chamado";
		$res = @pg_exec ($con,$sql);
		if (@pg_numrows($res) >= 0) $fabrica  = pg_result($res,0,fabrica);
	}


	$sql = "SELECT * 
			FROM tbl_help
			WHERE programa = '$programa'
			AND   fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	if(@pg_numrows($res)==0){
		$sql = "SELECT *
				FROM tbl_help
				WHERE programa ='$programa'
				AND   fabrica  IS NULL";
		$res = pg_exec ($con,$sql);
	}
	if (@pg_numrows($res) >= 0) {
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$programa       = pg_result($res,$i,programa);
			$help           = pg_result($res,$i,help);
		}
	}
}
include "menu.php";
?>
<style>
.resolvido{
	background: #259826;
	color: #FCFCFC;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}
.interno{
	background: #FFE0B0;
	color: #000000;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}

	table.tab_cabeca{
		border:1px solid #3e83c9;
		font-family: Verdana;
		font-size: 11px;

	}
	.titulo_cab{
		background: #C9D7E7;
		padding: 5px;
		color: #000000;
		font: bold;
	}
	.sub_label{
		background: #E7EAF1;
		padding: 5px;
		color: #000000;
		
	}
	table.relatorio {
		font-family: Verdana;
		font-size: 11px;
		border-collapse: collapse;
		width: 750px;
		font-size: 1.1em;
		border-left: 1px solid #8BA4EB;
		border-right: 1px solid #8BA4EB;
	}

	table.relatorio th {
		font-family: Verdana;
		font-size: 11px;
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 2px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		padding-top: 5px;
		padding-bottom: 5px;
	}

	table.relatorio td {
		font-family: Verdana;
		font-size: 11px;
		padding: 1px 5px 5px 5px;
		border-bottom: 1px solid #95bce2;
		line-height: 15px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}

	table.relatorio tr.over td {
		background: #bcd4ec;
	}
	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}
	
	</style>
<?
echo "<form name='frm_ajuda' action='$PHP_SELF' method='post' >";



echo "<table width = '750' class = 'tab_cabeca' align = 'center' border='0' cellpadding='2' cellspacing='2' >";
echo "<tr>";
echo "<td class='titulo_cab' width='10'><strong>Fábrica</strong></td>";
echo "<td class='sub_label'>";
$sql = "SELECT   * 
		FROM     tbl_fabrica 
		ORDER BY nome";

$res = pg_exec ($con,$sql);
$n_fabricas = pg_numrows($res);
echo "<select class='frm' style='width: 200px;' name='fabrica' class='caixa'></center>\n";
echo "<option value=''>- FÁBRICA -</option>\n";
for ($x = 0 ; $x < pg_numrows($res) ; $x++){
	$xfabrica  = trim(pg_result($res,$x,fabrica));
	$nome      = trim(pg_result($res,$x,nome));
	echo "<option value='$xfabrica' ";
	if($fabrica==$xfabrica) echo "SELECTED ";
	echo ">$nome</option>\n";
}
echo "</select>\n";
echo "</td>";
echo "</tr>";
echO "<tr>";
echo "<td class='titulo_cab' width='60'><strong>Programa</strong></td>";
echo "<td class='sub_label'><input type='text' size='60' name='programa' value='$programa'></td>";
echo "</tr>";
echO "<tr>";
echo "<td class='titulo_cab' width='60'><strong>Help</strong></td>";
echo "<td class='sub_label'><textarea name='help' cols='80' rows='14' wrap='VIRTUAL'>$help</textarea></td>";
echo "</tr>";
echo "</table>";
echo "<BR><center><input type='submit' name='btn_acao' value='Gravar'></center><BR>";
echo "</form>";

?>