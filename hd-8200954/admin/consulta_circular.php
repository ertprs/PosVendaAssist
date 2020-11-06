<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$btn_acao     = $_POST['btn_acao'];
$ano          = trim ($_POST['ano']);
$numero_ci    = trim ($_POST['numero_ci']);


if($btn_acao=="Pesquisar"){
	if(strlen($ano)==0 AND strlen($numero_ci)==0){
		$msg_erro = "Favor informar o número do CI, ou escolher o ano";
	}

	if(strlen($msg_erro)==0){
		$diretorio = '/var/www/assist/www/admin/tmp'; 

		$dir = opendir($diretorio);
		while(false !== ($arq = readdir($dir))){
			$arq_nomes[] = $arq;
		}
		
		foreach($arq_nomes as $listar){
			if ($listar!="." && $listar!=".."){ 
				if (!is_dir($listar)) { 
					$arquivos[]=$listar; 
				}
			}
		}

		if(strlen($numero_ci)==0){
			foreach($arquivos as $lista){
				$xlista = explode("_",$lista);
				$ano_base = $ano.".pdf";
				if($xlista[1] == $ano_base){
					$result[] = $lista;
				}
			}
		}else{
			$numero_ci = str_replace("/", "_", $numero_ci);
			foreach($arquivos as $lista){
				$lista = explode(".",$lista);
				if($lista[0] == $numero_ci){
					$result[] = $lista[0];
					$numero_ci = str_replace("_", "/", $numero_ci);
				}
			}
		}

		if(strlen($result)==0){
			$numero_ci = str_replace("_", "/", $numero_ci);
			$msg_erro = "Número CI não encontrado.";
		}
	}
}

$layout_menu = "financeiro";
$title = "Consulta Circular Interna";

include "cabecalho.php";
?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}
</style>
<?
if(strlen($msg_erro)>0){
	echo "<table width='700' height=16 border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo "<tr>";
	echo "<td align='center' bgcolor='#FF0000'><font size=2 color='#FFFFFF'><b>";
	echo $msg_erro;
	echo "</b></font></td>";
	echo "</tr>";
	echo "</table>";
}

echo "<FORM METHOD='POST' ACTION='$PHP_SELF'>";
	echo "<table width='300' height=16 border='0' cellspacing='0' cellpadding='4' align='center'>";
		echo "<TR  class='Conteudo' bgcolor='#596D9B'>";
			echo "<td colspan='2' height='27' align='center'><FONT COLOR='#FFFFFF'>Consulta Circular Interna</TD>";
		echo "</TR>";
		echo "<TR bgcolor='#D9E2EF'>";
			echo "<TD align='left' class='Conteudo'>Número CI</TD>";
			echo "<TD align='left' class='Conteudo'><INPUT TYPE='text' NAME='numero_ci' value='$numero_ci'></TD>";
		echo "</TR>";
		echo "<TR bgcolor='#D9E2EF'>";
			echo "<TD align='left' class='Conteudo'>Ano</TD>";
			echo "<td align='left' class='Conteudo'>";
				echo "<select name='ano' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($i = 2008 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				echo "</select>";
			echo "</td>";
		echo "</TR>";
		echo "<tr class='Conteudo' bgcolor='#D9E2EF'>";
			echo "<td colspan='3' align='center'><br><input type='submit' name='btn_acao' value='Pesquisar'></td>";
		echo "</tr>";
	echo "</TABLE>";
echo "</FORM>";


if(strlen($msg_erro)==0 AND $btn_acao=="Pesquisar"){
echo "<table width='300' border='0' cellspacing='2' cellpadding='4' align='center'>";
echo "<TR  class='Conteudo' bgcolor='#596D9B'>";
	echo "<td align='center'><FONT COLOR='#FFFFFF'>Número CI</TD>";
	echo "<td align='center'><FONT COLOR='#FFFFFF'>PDF</TD>";
echo "</TR>";
	foreach($result as $listar){
		$ex_listar  = explode(".", $listar);
		$xex_listar = str_replace("_", "/", $ex_listar);
		
		if($cor=='#D9E2EF')$cor='#D1D1D1';
		else               $cor='#D9E2EF';
		echo "<TR bgcolor='$cor'>";
			echo "<TD class='Conteudo'>$xex_listar[0]</TD>";
			echo "<TD><A HREF='tmp/$listar' target='_black' class='Conteudo'>Arquivo</A><BR></TD>";
		echo "</TR>";
	}
}
echo "</TABLE>";
