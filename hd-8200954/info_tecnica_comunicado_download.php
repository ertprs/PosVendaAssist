<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
$tipo = urldecode ($tipo);
$title=$tipo;
if ($tipo=='Manual de Serviço'){ $layout_menu = 'Manual de Serviço'; }
include "cabecalho.php";
$mostra_download = true;
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
	border:1px solid #485989;
	
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
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.produto ELSE tbl_produto.produto END AS produto,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao ELSE tbl_produto.descricao END AS descricao_produto,
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data,
					CASE WHEN tbl_comunicado_download.qtde IS NULL THEN 0 ELSE tbl_comunicado_download.qtde END AS downloads
		FROM tbl_comunicado 
		LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		LEFT JOIN tbl_produto prod ON prod.produto = tbl_comunicado_produto.produto
		LEFT JOIN tbl_comunicado_download ON tbl_comunicado.comunicado=tbl_comunicado_download.comunicado
		WHERE	tbl_comunicado.fabrica = $login_fabrica
		AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND    ((tbl_comunicado.posto     = $login_posto) OR (tbl_comunicado.posto           IS NULL))
		AND    tbl_comunicado.ativo IS TRUE ";
	if($login_fabrica==5){
		$sql .=" AND    (tbl_produto.ativo IS TRUE OR prod.ativo IS TRUE) ";
	}
		if($tipo == 'zero'){ 
			$tipo = "Sem Título";
			$sql .= "AND	tbl_comunicado.tipo IS NULL "; 
		}else{
			$sql .= "AND	tbl_comunicado.tipo = '$tipo' ";
		}
	if ($linha)   $sql .= "AND (tbl_produto.linha = $linha OR prod.linha = $linha OR tbl_comunicado.linha = $linha) ";
	if ($familia) $sql .= "AND (tbl_produto.familia = $familia OR prod.familia = $familia)";
	
	if($mostra_download)
	{
		$sql .= "ORDER BY downloads DESC, tbl_produto.descricao DESC,tbl_comunicado.descricao , tbl_produto.referencia " ;
	}
	else
	{
		$sql .= "ORDER BY tbl_produto.descricao DESC,tbl_comunicado.descricao , tbl_produto.referencia " ;
	}

if ($ip=="201.76.85.4"){
//	echo nl2br($sql);
}

	$res = pg_exec ($con,$sql);

	$total = pg_numrows ($res);
	
	if ($total) {	
		echo "<table width='700' align='center' class='Tabela' cellspacing='0' cellpadding='0' border='1' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
		echo "<tr class='titulo2'>";
		echo "<td colspan='4' background='admin/imagens_admin/laranja.gif' height='25'>$tipo</td>";
		echo "</tr>";
	
		echo "<tr bgcolor='#ffffff'>";
		echo "<td align='center' colspan='4'><font color='#000000' size='0'><b>Se você não possui o Acrobat Reader&reg;, <a href='http://www.adobe.com/products/acrobat/readstep2.html' target='_blank'>instale agora</a>.</b></font></td>";
		echo "</tr>";
		if(strlen($familia)>0){
			$sql2="SELECT descricao FROM tbl_familia WHERE familia=$familia";
			$res2 = pg_exec ($con,$sql2);
			echo " - ".trim(pg_result($res2,0,descricao));
		}
		echo "</b></td>";
		echo "</tr>";
		
		echo "<tr class='titulo' >";
		if($mostra_download)
		{
			echo "<td background='admin/imagens_admin/azul.gif' width=80>Downloads</td>";

			if ($S3_sdk_OK) {
				include_once S3CLASS;
				$s3 = new anexaS3('ve', (int) $login_fabrica);
			}

		}
		echo "<td background='admin/imagens_admin/azul.gif'>Referência</td>";
		echo "<td background='admin/imagens_admin/azul.gif'>Produto</td>";
		echo "</tr>";
		
		for ($i=0; $i<$total; $i++) {
			$Xcomunicado           = trim(pg_result($res,$i,comunicado));
			$produto               = trim(pg_result ($res,$i,produto));
			$referencia            = trim(pg_result ($res,$i,referencia));
			$descricao             = trim(pg_result ($res,$i,descricao_produto));
			$comunicado_descricao  = trim(pg_result ($res,$i,descricao));
	
			$cor = "#ffffff";
			if ($i % 2 == 0) $cor = '#eeeeff';
	
			echo "<tr bgcolor='$cor' class='conteudo'>\n";
			if($mostra_download)
			{
			$downloads             = trim(pg_result ($res,$i,downloads));
			if ($downloads == 0) $downloads = "-";
			echo "<td align='center'>$downloads </td>";
			}
			echo "<td align='center'>$referencia </td>";
			echo "<td nowrap >";
			
			if ($S3_online) {
				$tipo_s3 = in_array($comunicado_tipo, explode(',', utf8_decode(anexaS3::TIPOS_VE))) ? 've' : 'co';
				if ($s3->tipo_anexo != $tipo_s3)
					$s3->set_tipo_anexoS3($tipo_s3);
				$s3->temAnexos($Xcomunicado);
				if ($s3->temAnexo) {
					echo "<a href=''" . $s3->url . "' target='_blank'>";
					echo ($descricao) ? $descricao : $comunicado_descricao;
					echo '</a>';
				}
			} else {
				/* HD 147515 - INICIO */
				$tipos = array("gif", "jpg", "pdf", "doc", "rtf", "xls", "ppt", "zip");

				foreach($tipos as $index => $tipo)
				{
					$arquivo = "comunicados/$Xcomunicado." . $tipo;
					if (file_exists($arquivo) == true) echo "<a href='info_tecnica_visualiza_manual_abrir.php?tipo=" . $tipo . "&comunicado=$Xcomunicado' target='_blank'>";
				}
				/* HD 147515 - FIM */

				if (strlen ($descricao) > 0) {
					echo $descricao;
				}else{
					echo $comunicado_descricao;
				}
				if($login_fabrica==14) echo " - ".$comunicado_descricao;
				echo "</a>";
			}
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
