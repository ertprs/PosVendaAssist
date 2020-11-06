<?
session_start();

require('autentica_usuario.php');
require_once("chat.php");

$refresh = new chat();

$data = $_SESSION['sess_data'];
$nick = $_SESSION['sess_nick'];
$limite=20;

if ($_SESSION['sess_login']=='fabio'){
	$limite=100;
	$data=$data-1;
}
$query="SELECT username,texto,cor,to_char(data,'HH24:mi') as data2 FROM chat WHERE data>='$data' ORDER BY data DESC LIMIT $limite";
$res = pg_exec ($con,$query);

$b = array();
$num = pg_numrows ($res);
$num2 = 4;
for($x=0;$x<$num;$x++) {
	for($i=0;$i<$num2;$i++) {
		$b[$x][$i] = pg_result ($res,$x,$i);
	}
}
if(count($b)>0){
	//$a=array_reverse($a);
	if(count($b)<100)	$end=count($b);
	else			$end=100;
	for($i=$end-1;$i>=0;$i--){
		$query="SELECT login FROM tbl_admin WHERE admin=".$b[$i][0];
		$res = pg_exec ($con,$query);
		$nome = pg_result ($res,0,0);
		echo "<p><font color=red>$nome</font> <font color=gray>(".$b[$i][3].")</font>: ".$b[$i][1]."</p>";
	}
}

?>