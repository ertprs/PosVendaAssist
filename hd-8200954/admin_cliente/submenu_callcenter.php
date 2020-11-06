<?
if($login_fabrica <> 85 && $login_fabrica != 158) {
echo "<table border='0' height='18' cellpadding='0' cellspacing='0' align='center' >";
echo "<tr align='center' style='font-face:arial ; font-size: 12px ; color: #000000' valign='top'>";

	echo "<td onclick=\"javascript: window.location='pre_os_cadastro_sac.php'\" style='CURSOR: hand;' width='100' align='center'>";
	echo "<acronym title='Abre um novo chamado no Callcenter'>";
	if($login_fabrica == 30) {
		echo "Abre OS";
	}else if($login_fabrica == 156){
        echo "Abre Chamados";
    }else{
		echo "Abre Pré-OS";
	}
	echo "</acronym>";
	echo "</td>";
echo "</tr>";
echo "</table>";
}
?>
