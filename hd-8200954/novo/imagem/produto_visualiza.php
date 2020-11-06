<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../autentica_usuario.php';
$tipo = urldecode ($tipo);
$title='$tipo';
include "cabecalho.php";
?>

<style>
.titulo {
	font-family: Arial;
	font-size: 9pt;
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background: #408BF2;
}
.titulo2 {
	font-family: Arial;
	font-size: 12pt;
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background: #408BF2;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	text-align: left;
}
.Tabela{
	border:1px solid #408BF2;
	
}
img{
	border: 0px;
}
</style>
<?

$tipo       = $_GET ['tipo'];
$familia    = $_GET ['familia'];
$linha      = $_GET ['linha'];

# SELECIONA A FAMÍLIA DO POSTO
$sql = "SELECT familia FROM tbl_posto_linha WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);

$familia_posto = '';

for ($i=0; $i<pg_numrows($res); $i++){
	if(strlen(pg_result ($res,$i,0))){
		$familia_posto .= pg_result ($res,$i,0);
		$familia_posto .= ", ";
		}
}

# SELECECIONA O TIPO DE COMUNICADO DO POSTO
$sql2 = "SELECT tbl_posto_fabrica.codigo_posto        ,
				tbl_posto_fabrica.tipo_posto       
		FROM	tbl_posto
		LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_posto.posto   = $login_posto ";

$res2 = pg_exec ($con,$sql2);

if (pg_numrows ($res2) > 0) {
	$tipo_posto            = trim(pg_result($res2,0,tipo_posto));
}


#SELECIONA O COMUNICADO
if (strlen ($tipo) > 0 AND strlen ($comunicado) == 0) {
	$tipo = urldecode ($tipo);

	$sql = "SELECT	tbl_comunicado.comunicado, 
					tbl_comunicado.descricao , 
					tbl_comunicado.mensagem  , 
					tbl_produto.produto      , 
					tbl_produto.referencia   , 
					tbl_produto.descricao AS descricao_produto        , 
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data 
			FROM	tbl_comunicado 
			LEFT JOIN tbl_produto USING (produto) 
			WHERE	tbl_comunicado.fabrica = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto           = $login_posto) OR (tbl_comunicado.posto           IS NULL))
			AND    tbl_comunicado.ativo IS TRUE ";

			if($tipo == 'zero'){ 
				$tipo = "Sem Título";
				$sql .= "AND	tbl_comunicado.tipo IS NULL "; 
			}else{
				$sql .= "AND	tbl_comunicado.tipo = '$tipo' ";
			}
	if ($linha)   $sql .= "AND (tbl_produto.linha = $linha OR tbl_comunicado.linha = $linha) ";
	if ($familia) $sql .= "AND tbl_produto.familia = $familia ";

	$sql .= "ORDER BY tbl_produto.descricao DESC, tbl_produto.referencia " ;
//echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {	
		echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='Tabela'>";
		echo "<tr class='titulo2'>";
		echo "<td colspan='4'>$tipo</td>";
		echo "</tr>";
	
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='center' colspan='4'><font color='#000000' size='0'><b>Se você não possui o Acrobat Reader&reg;, <a href='http://www.adobe.com/products/acrobat/readstep2.html' target='_blank'>instale agora</a>.</b></font></td>";
		echo "</tr>";
	
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='center' colspan='4'><b>Você está em ";
		
		$sql1="SELECT nome FROM tbl_linha WHERE linha=$linha";
		$res1 = pg_exec ($con,$sql1);
		echo trim(pg_result($res1,0,nome));
	
		if(strlen($familia)>0){
			$sql2="SELECT descricao FROM tbl_familia WHERE familia=$familia";
			$res2 = pg_exec ($con,$sql2);
			echo " - ".trim(pg_result($res2,0,descricao));
		}
		echo"</b></td>";
		echo "</tr>";
		
		echo "<tr class='titulo'>";
		echo "<td>Referência</td>";
		echo "<td>Produto</td>";
		echo "</tr>";
		
		$total = pg_numrows ($res);
	
		for ($i=0; $i<$total; $i++) {
			$Xcomunicado           = trim(pg_result($res,$i,comunicado));
			$produto               = trim(pg_result ($res,$i,produto));
			$referencia            = trim(pg_result ($res,$i,referencia));
			$descricao             = trim(pg_result ($res,$i,descricao_produto));
			$comunicado_descricao  = trim(pg_result ($res,$i,descricao));
	
			$cor = "#ffffff";
			if ($i % 2 == 0) $cor = '#eeeeff';
	
			echo "<tr bgcolor='$cor' class='conteudo'>\n";
			echo "<td nowrap>$referencia </td>";
			echo "<td nowrap height='20'>";
	
			$gif = "../comunicados/$Xcomunicado.gif";
			$jpg = "../comunicados/$Xcomunicado.jpg";
			$pdf = "../comunicados/$Xcomunicado.pdf";
			$doc = "../comunicados/$Xcomunicado.doc";
			$rtf = "../comunicados/$Xcomunicado.rtf";
			$xls = "../comunicados/$Xcomunicado.xls";
			$ppt = "../comunicados/$Xcomunicado.ppt";
			$zip = "../comunicados/$Xcomunicado.zip";
		
			if (file_exists($rtf) == true) echo "<a href='../comunicados/$Xcomunicado.rtf' target='_blank'>";
			if (file_exists($xls) == true) echo "<a href='../comunicados/$Xcomunicado.xls' target='_blank'>";
			if (file_exists($pdf) == true) echo "<a href='../comunicados/$Xcomunicado.pdf' target='_blank'>";
			if (file_exists($ppt) == true) echo "<a href='../comunicados/$Xcomunicado.ppt' target='_blank'>";
			if (file_exists($zip) == true) echo "<a href='../comunicados/$Xcomunicado.zip' target='_blank'>";

			if (strlen ($descricao) > 0) {
				echo $descricao;
			}else{
				echo $comunicado_descricao;
			}
			echo "</a>";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</form>\n";
		echo "</table>\n";
	
		echo "<hr>";
	}else{
	echo "<center>Nenhum $tipo cadastrado</center>";
	}
}





?>