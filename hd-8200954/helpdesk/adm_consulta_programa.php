<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}
$TITULO = "ADM - Consulta Programa";

include "menu.php";
?>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<script>
function recuperardados(hd_chamado) {

	var programa = document.adm_consulta.programa.value;
	if(programa.length > 4 ){

		var busca = new BUSCA();
		alert ("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado));
		busca.Updater("ajax_listar_programa.php?digito="+programa+"&hd_chamado="+escape(hd_chamado),"conteudo","get","carregando os dados...");
	}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}


var http3 = new Array();

</script>


<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 13px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: verdana;
	font-size: 10px;
	font-weight: bold;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 12px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 12px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>

<?

$programa= $_POST['programa'];
if(strlen($programa)==0){
	$programa= $_GET['programa'];
}
?>
<body>
<form name='adm_consulta' method='GET' ACTION='<?echo $PHP_SELF?>'>
<table align='center'>
	<tr>	
		<td  class ='sub_label'>
		<strong>Busca de Arquivos:</strong>
		
		</td>
	</tr>
	<tr>
		<td  class ='sub_label'>Arquivo:</td>
		<td   class ='sub_label' align='center'>
		<input name='programa' id='programa'value='<?echo $programa;?>' class='caixa' size='60' onblur='this.value=""'><br>
		<? //<input name='programa' id='programa'value='' class='caixa' size='25' onKeyUp = 'recuperardados(2)' onblur='this.value=""'><br> ?>
		</td>
		<td>
			<input type='submit' name='pesquisa' value='pesquisa'>
		</td>

	</tr>
</table>
</form>

<?

echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";
echo "<tr class='Titulo'>";
echo "<td background='../admin/imagens_admin/azul.gif' height='20' width='50'><font size='2'>Arq. Inicio</font></td>";
echo "<td background='../admin/imagens_admin/azul.gif' height='20' width='50'><font size='2'>Arq. Fim</font></td>";
echo "<td nowrap background='../admin/imagens_admin/azul.gif' height='20' width='300'><font size='2'>Arquivo</font></td>";
echo "<td background='../admin/imagens_admin/azul.gif' height='20' width='50'><font size='2'>Login</font></td>";
echo "<td background='../admin/imagens_admin/azul.gif' height='20' width='30'><font size='2'>HD</font></td>";
echo "<td background='../admin/imagens_admin/azul.gif' height='20' width='50'><font size='2'>HD INICIO</font></td>";
echo "<td background='../admin/imagens_admin/azul.gif' height='20' width='50'><font size='2'>HD Resolv.</font></td>";
echo "</tr>";

if(strlen($programa)> 0){
	$sql = "
		SELECT 
			tbl_arquivo.descricao AS arquivo,
			to_char (tbl_controle_acesso_arquivo.data_inicio,'DD/MM/YYYY') || to_char (tbl_controle_acesso_arquivo.hora_inicio,' HH24:MI')AS data_inicio,
			to_char (tbl_controle_acesso_arquivo.data_fim,'DD/MM/YYYY') || to_char (tbl_controle_acesso_arquivo.hora_fim,' HH24:MI')AS data_fim,
			to_char (tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI') AS hd_inicio,
			to_char (tbl_hd_chamado.data_resolvido,'DD/MM/YYYY HH24:MI') AS hd_resolvido,
			tbl_hd_chamado.hd_chamado,
			login
		FROM tbl_arquivo 
		JOIN tbl_controle_acesso_arquivo USING(arquivo)
		JOIN tbl_admin using(admin)
		JOIN tbl_hd_chamado using(hd_chamado)
		WHERE  tbl_arquivo.descricao ilike '%$programa%' 
		ORDER BY 		tbl_controle_acesso_arquivo.data_inicio desc,
		tbl_admin.login,
			tbl_arquivo.descricao
	
		limit 300";

	$res = pg_exec ($con,$sql);
	//echo "sql: $sql <br><br>";

	if (pg_numrows($res) > 0) {
		
		for ($i=0; $i<pg_numrows($res); $i++){

			$arquivo     = trim(pg_result($res,$i,arquivo))  ;
			$hd_inicio   = trim(pg_result($res,$i,hd_inicio)) ;
			$hd_resolvido= trim(pg_result($res,$i,hd_resolvido))   ;
			$data_inicio = trim(pg_result($res,$i,data_inicio)) ;
			$data_fim    = trim(pg_result($res,$i,data_fim))   ;
			$hd_chamado  = trim(pg_result($res,$i,hd_chamado));
			$login       = trim(pg_result($res,$i,login));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';


			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' align='left' >$data_inicio</td>";
			echo "<td bgcolor='$cor' align='left' >$data_fim</td>";
			echo "<td bgcolor='$cor' align='left' >$arquivo</td>";
			echo "<td bgcolor='$cor' align='left' ><b>$login</b></td>";
			echo "<td bgcolor='$cor' align='left' >$hd_chamado</td>";
			echo "<td bgcolor='$cor' align='left' >$hd_inicio</td>";
			echo "<td bgcolor='$cor' align='left' >$hd_resolvido</td>";
			echo "</tr>";
		}


	}
}else{
	echo "<tr>";
	echo "<td bgcolor='$cor' align='left' colspan='7' >Digite o Nome do programa para realizar a busca</td>";
	echo "</tr>";
}
echo "<tr>";
echo "<td colspan ='7' align='left' ><font color = 'red'><b>OBS: Existe um limite de 300 linhas para essa busca, digite o nome completo do programa.</b></font></td>";
echo "</tr>";


echo "</table>";
include 'rodape.php';

?>
</body>