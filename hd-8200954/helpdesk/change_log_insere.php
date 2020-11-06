<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$hd_chamado=$_GET['hd_chamado'];

if(strlen($hd_chamado) >0){
	$sql="SELECT tbl_hd_chamado.titulo ,
				 tbl_hd_chamado.fabrica,
				 tbl_fabrica.nome
			FROM tbl_hd_chamado
			JOIN tbl_fabrica USING(fabrica)
			WHERE hd_chamado=$hd_chamado";
	$res=pg_exec($con,$sql);
	$titulo       = pg_result($res,0,titulo);
	$fabrica      = pg_result($res,0,fabrica);
	$fabrica_nome = pg_result($res,0,nome);
}

$btn_acao=$_POST['btn_acao'];

if(strlen($btn_acao) >0){
	$hd_chamado         = $_POST['hd_chamado'];
	$titulo             = $_POST['titulo'];
	$tipo               = $_POST['tipo'];
	$change_log_interno = $_POST['change_log_interno'];
	$change_log_fabrica = $_POST['change_log_fabrica'];
	$change_log_geral   = $_POST['change_log_geral'];
	$fabrica            = $_POST['fabrica'];
	
	if(strlen($fabrica) >0 and strlen($change_log_geral) >0){
		$msg_erro="Esse Change Log é para fábrica determinada, não pode colocar o change log aqui";
	}
	if(strlen($change_log_fabrica) >0 and strlen($change_log_geral) ==0){
		$change_log=$change_log_fabrica;
	}elseif(strlen($change_log_fabrica) ==0 and strlen($change_log_geral) >0) {
		$change_log=$change_log_geral;
	}elseif(strlen($change_log_interno) ==0){
		$msg_erro="É obrigatório o preenchimento de Change Log Interno";
	}
	
	
	if(strlen($hd_chamado) ==0){
		$hd_chamado="null";
	}

	if(strlen($titulo) ==0){
		$msg_erro="Por favor, colocar o título.";
	}
	
	if(strlen($fabrica) ==0){
		$fabrica="null";
	}

	if(strlen($msg_erro) ==0){
		$sql="INSERT INTO tbl_change_log (
				hd_chamado          ,
				titulo              ,
				admin               ,
				fabrica             ,
				change_log_interno  ,
				change_log_fabrica  ,
				tipo                ,
				data
				)values(
				$hd_chamado           ,
				'$titulo'             ,
				$login_admin          ,
				$fabrica              ,
				'$change_log_interno' ,
				'$change_log' ,
				'$tipo'               ,
				current_timestamp
				)";
		$res=pg_exec($con,$sql);
		$msg_erro.=pg_errormessage($con);
	}
	if(strlen($msg_erro) ==0) {
		$msg="Change Log inserido com sucesso";
	}
}

$TITULO = "Inserir Change Log";

include "menu.php";
?>

<style>
.aviso{
	position:relative;
	color:#FF0000;
	text-align:center; 
	font: bold;
	font-size: 27px;
}
.change_log_titulo{
	font: bold ;
	font-size: 30px;
	letter-spacing: 6px;
	color:#FF0000;
}
</style>

<table width = '720' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>

<form name='frm_log' action='<? echo $PHP_SELF ?>' method='post' >

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='50' name='titulo' maxlength='50' value='<?= $titulo ?>'  > </td>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;HD CHAMADO </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='50' name='hd_chamado' maxlength='50' value='<?= $hd_chamado ?>' > </td>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fábrica </strong></td>

<?
$sql = "SELECT   * 
		FROM     tbl_fabrica 
		ORDER BY nome";

$res = pg_exec ($con,$sql);
$n_fabricas = pg_numrows($res);
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'>";
	echo "<select class='frm' style='width: 200px;' name='fabrica' class='caixa' onChange='listaAdmin(this)'></center>\n";
	echo "<option value=''>- FÁBRICA -</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$xfabrica   = trim(pg_result($res,$x,fabrica));
		$nome      = trim(pg_result($res,$x,nome));
		echo "<option value='$xfabrica' ";
		if($fabrica == $xfabrica){echo "SELECTED";}
		echo ">$nome</option>\n";
	}
	echo "</select>\n";
	echo"</td>";?>
</tr>
<tr>

	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Tipo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
	<select class='frm' style='width: 200px;' name='tipo' >
	<option value='Importante'>Importante</option>
	<option value='Necessário'>Necessário</option>
	<option value='Telas'>Telas</option>
	</select>
	</td>
</tr>
<?
echo "<center>";
echo "<tr>";
echo "<td colspan='100%'>";
echo "<table width = '720' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";
echo "<td colspan='3' align='center' class='change_log_titulo'>";
echo "CHANGE LOG INTERNO ";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'>";
echo "<textarea name='change_log_interno' cols='80' rows='10' wrap='VIRTUAL'>$change_log_interno</textarea><br>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='3' align='center' class='change_log_titulo'>";
echo "CHANGE LOG FÁBRICA $fabrica_nome ";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'>";
echo "<textarea name='change_log_fabrica' cols='80' rows='10' wrap='VIRTUAL'>$change_log_fabrica</textarea><br>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='3' align='center' class='change_log_titulo'>";
echo "CHANGE LOG GERAL ";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'>";
echo "<textarea name='change_log_geral' cols='80' rows='10' wrap='VIRTUAL'>$change_log_geral</textarea><br>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td align='center'>";
echo "<input type='submit' name='btn_acao' value='Gravar'>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</center>";
echo "</td>";
echo "</tr>";
echo "</form>";
echo "</table>";


?>
<? include "rodape.php" ?>