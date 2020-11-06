<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";

function converte_data($date){
	$date = explode("/", $date);
	$date2 = $date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if (strlen($_POST["acao"]) > 0) $acao = trim( $_POST["acao"]);
if (strlen($_POST["ajax"]) > 0) $ajax = trim($_POST["ajax"]);

##### G R A V A R   P E D I D O #####

$importa = $_POST["importar"];
if(strlen($importa) > 0){
	$caminho       = "/www/cgi-bin/britania/bkp-entrada";

	$arquivo       = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if(strlen($arquivo["tmp_name"])==0) $msg_erro = "Selecione um arquivo";

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> "text/plain") {
			$msg_erro = "Arquivo em formato inválido!";
		}else{
			if ($arquivo["size"] > $config["tamanho"]) $msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}
		if (strlen($msg_erro) == 0) {
			$nome_arquivo = $caminho."/pedidos_nao_importados".date("d-m-Y-his").".txt";
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro .= "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$f = fopen("$caminho/pedidos_nao_importados".date("d-m-Y-his").".txt", "r");
				$i=1;

				$sql = "DROP TABLE britania_nao_importado;";
				$res = @pg_exec($con,$sql);
				$sql = "CREATE TABLE britania_nao_importado (posto int4, pedido int4, txt_peca text,peca int4, txt_os text,os int4, mensagem text);";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				while (!feof($f)){
					$buffer = fgets($f, 4096);
					if($buffer <> "\n" and strlen(trim($buffer))>0){
						list($xcodigo_posto, $xpedido,$xpeca,$xos,$xmensagem) = explode("\t", $buffer);
						$xcodigo_posto   = trim($xcodigo_posto);
						$xpedido         = trim($xpedido);
						$xpeca           = trim($xpeca);
						$xos             = trim($xos);
						$xmensagem       = trim($xmensagem);

						if(strlen($xcodigo_posto)==0) $msg_erro = "Falta o código do posto";
						if(strlen($xpedido)      ==0) $msg_erro = "Falta o número do pedido";
						if(strlen($xpeca)        ==0) $msg_erro = "Falta a peça";
						if(strlen($xos)          ==0) $msg_erro = "Falta o número da OS";
						if(strlen($xmensagem)    ==0) $msg_erro = "Falta a mensagem";
		
						$sql =	"
							SELECT tbl_peca.peca
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica = $login_fabrica
							AND     tbl_peca.referencia = '$xpeca' ";
						$res = pg_exec($con,$sql);
						if (pg_numrows($res) == 1) $peca = pg_result($res,0,peca);
						else                       $peca = "null";

						$sql =	"
							SELECT tbl_pedido.pedido
							FROM    tbl_pedido
							WHERE   tbl_pedido.fabrica = $login_fabrica
							AND     tbl_pedido.pedido  = '$xpedido' ";
						$res = pg_exec($con,$sql);
						if (pg_numrows($res) == 1) $pedido = pg_result($res,0,pedido);
						else                       $msg_erro = "Pedido não encontrado";

						$sql =	"
							SELECT tbl_os.os
							FROM    tbl_os
							WHERE   tbl_os.fabrica = $login_fabrica

							AND     tbl_os.sua_os = '$xos' ";
						$res = @pg_exec($con,$sql);
						if (@pg_numrows($res) == 1) $os = pg_result($res,0,os);
						else                        $os = "null";

						$sql = "SELECT  tbl_posto.posto
							FROM    tbl_posto
							JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
							WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
							AND tbl_posto_fabrica.codigo_posto = '$xcodigo_posto' ";
						
						$res = @pg_exec($con,$sql);
						if (pg_numrows($res) == 1) $posto = "'".pg_result($res,0,0)."'";
						else                       $posto = "null";


						if(strlen($msg_erro)>0)    $msg_erro = "Erro na linha $i:".$msg_erro;
						else{
							$sql =	"INSERT INTO britania_nao_importado (
										posto,
										pedido  ,
										txt_peca,
										peca    ,
										txt_os  ,
										os      ,
										mensagem
									) VALUES (
										$posto  ,
										$pedido ,
										'$xpeca',
										$peca   ,
										'$xos'  ,
										$os     ,
										'$xmensagem'
									)";
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
						if (strlen ($msg_erro) > 0) break;
					}
					$i++;
				}
				fclose($f);
			}
			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "Carga efetuada com sucesso.";
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg = "Carga não foi efetuada.";
			}
		}
	}


}

$layout_menu = "callcenter";
$title       = "Pedidos não importados";
include "cabecalho.php";

?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<style>
.Conteudo{
	font-family: Arial;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; 
	background-color: #990000;
}

#Propaganda{
	text-align: justify;
}

</style>

<?
$cnpj = trim($_POST['revenda_cnpj']);
$nome = trim($_POST['revenda_nome']);

$qtde_item=0;

$sql = "SELECT pedido,peca,os,txt_os,mensagem FROM britania_nao_importado";
@$res = pg_exec ($con,$sql);
if (strlen(pg_errormessage) == 0) {
	$num_rows = pg_numrows($res);
}
else {
	$num_rows = 0;
}

if ($num_rows > 0 AND strlen($importa) > 0 and strlen($msg_erro)==0) {
	
	echo "<center><div style='width:750px;'><font size='2'> Os pedidos abaixo foram colocados como NÃO EXPORTADOS!</font><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
	echo "<thead>";
	echo "<TR>";
	echo "<TD><b>Pedido</b></TD>";
	echo "<TD><b>Peça</b></TD>";
	echo "<TD><b>OS</b></TD>";
	echo "<TD><b>Mensagem</b></TD>";
	echo "</TR>";
	echo "</thead>";
	echo "<tbody>";
	

	for ($x = 0; $x < pg_numrows($res); $x++) {
		$pedido   = pg_result($res,$x,pedido);
		$peca     = pg_result($res,$x,peca);
		$os       = pg_result($res,$x,os);
		$txt_os   = pg_result($res,$x,txt_os);
		$mensagem = pg_result($res,$x,mensagem);

		if($cor=="#FFFFFF") $cor = "#EEEEEE";
		else                $cor = "#FFFFFF";

		echo "<TR bgcolor='$cor'>";
		echo "<TD>$pedido</TD>";
		echo "<TD>$peca</TD>";
		echo "<TD>$txt_os</TD>";
		echo "<TD>$mensagem</TD>";
		echo "</TR>";

		$sql2 = "SELECT fn_pedido_nao_importado($pedido,$login_fabrica);";
		$res = pg_exec ($con2,$sql2);

	}
	echo "</tbody>";
	echo "</table>";

}


if(strlen($msg_erro)>0) echo "<div  class='Erro'>$msg_erro</div>";


echo "<table style=' border:#485989 1px solid; background-color: #F0F4FF' align='center' width='750' border='0' cellspacing='0'>\n";
echo "<form name='frm_upload' method='post' action='$PHP_SELF' enctype='multipart/form-data'>";
echo "<tr height='20' bgcolor='#BCCBE0'>\n";
echo "<td align='left' colspan='4'><b>Pedidos não importados</b>&nbsp;</td>\n";
echo "</tr>\n";
echo "<tr><td colspan='4'><br>\n";
echo "</td></tr>\n";
echo "<tr>";
echo "<td align='right' ><b>Arquivo</b>&nbsp;</td>";
echo "<td align='left' colspan='3'><b><input type='file' name='arquivo' size='30' class='Caixa'></td>";
echo "</tr>";
echo "</table>";

echo "<table style='border:#B63434 1px solid; background-color: #EED5D2' align='center' width='750' border='0'height='40'>";
echo "<tr>";
echo "<td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='importar' value='Importar'></td>";
echo "<td ><div id='saida' style='display:inline;'>$msg_erro $msg</div></td>";
echo "</tr>";
echo "</form>";
echo "</table>";



 include "rodape.php";
?>
