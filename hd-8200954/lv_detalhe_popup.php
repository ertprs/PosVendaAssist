<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once "class/tdocs.class.php";

$login_fabrica    = $_POST['login_fabrica'];
$login_fabrica    = $_GET['login_fabrica'];

$tDocs = new TDocs($con, $login_fabrica);

$caminho_dir = "imagens_pecas";
?>
<style>
body{
	margin: 0px;
	padding: 0px;
}
.texto{
	margin: 5px auto;
	font-family: arial;
	font-size: 13px;
	font-weight: bold;
}
.top{
	background-color: #005f9d;
	background-image: url('helpdesk/imagem/fundo_dh2.jpg');
}
</style>
<?
	$caminho    = $_POST['caminho'];
	$caminho    = $_GET['caminho'];

	$descricao  = $_POST['descricao'];
	$descricao  = $_GET['descricao'];

	$referencia = $_POST['referencia'];
	$referencia = $_GET['referencia'];

	$peca       = $_POST['peca'];
	$peca       = $_GET['peca'];

	$caminho_final = $_POST['caminho_final'];
	$caminho_final = $_GET['caminho_final'];

/*	
	echo "<table width='100%' border='0' cellspacing='0' align='center' cellpadding='2' background='helpdesk/imagem/fundo_dh5.jpg'>";
	echo "<tr  height='83'>";
	echo "<td width='100%' align='left'>&nbsp;</td>";
	echo "</tr></table>";	
*/
	

	$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
			$fotoPeca = "";
		    $fotoPeca = $vFoto["link"];
			echo "<a href=\"$PHP_SELF?caminho_final=$fotoPeca&peca=$peca&referencia=$referencia&login_fabrica=$login_fabrica\" title=''><img src='$fotoPeca' border='0'></a>";
		}
	} else {	

		$sql = "SELECT  peca_item_foto, caminho, caminho_thumb, descricao
				FROM tbl_peca_item_foto
				WHERE peca = $peca";
		$res = pg_exec ($con,$sql) ;
		$num_fotos = pg_num_rows($res);

		echo '<BR>';


		if ($num_fotos>0){
			for ($i=0; $i<$num_fotos; $i++){
				$caminho        = trim(pg_result($res,$i,caminho));
				$caminho_thum   = trim(pg_result($res,$i,caminho_thumb));
				$foto_descricao = trim(pg_result($res,$i,descricao));
				$foto_id        = trim(pg_result($res,$i,peca_item_foto));

				$caminho      = str_replace("/www/assist/www/".$caminho_dir."/",'',$caminho);
				$caminho_thum = str_replace("/www/assist/www/".$caminho_dir."/",'',$caminho_thum);
				//echo $caminho_dir."/".$caminho_thum;

				?>
				<a href="<? echo $PHP_SELF; ?>?caminho_final=<?echo $caminho_dir."/".$caminho; ?>&peca=<? echo $peca; ?>&referencia=<? echo $referencia; ?>" title="<?=$descricao;?>"><img src="<?echo $caminho_dir."/".$caminho_thum; ?>" border="0"></a>

				<?
			}
			if ($num_fotos > 1){
				$tem_foto = "SIM";
			}
		}else{
			if($login_fabrica == 3 OR $login_fabrica == 10) $diretorio = "imagens_pecas//";
			else                     $diretorio = "imagens_pecas/$login_fabrica/";
		
			if ($dh = opendir("$diretorio/pequena")) {
				$contador=0;
				while (false !== ($filename = readdir($dh))) {

					if (strpos($filename,$referencia) !== false){
						$contador++;
						//$peca_referencia = ntval($peca_referencia);
						$po = strlen($referencia);
						if(substr($filename, 0,$po)==$referencia){
							$file_final = $filename;
							echo "<a href=\"$PHP_SELF?caminho_final=$diretorio/media/$file_final&peca=$peca&referencia=$referencia\" title=''><img src='$diretorio/pequena/$file_final' border='0'></a>";
							$tem_foto = "SIM";

						}
					}
				}
				
			}
		}
		if($tem_foto<>'SIM'){
			#echo "<img src='imagens_pecas/semimagem.jpg' border='0'>\n";
			echo "<input type='hidden' name='peca_imagem' value='$filename'>";
		}
	}
	echo '<BR>';
	echo "<TABLE width='100%' border='0' cellpadding='1' cellspacing='1' align='center'>";
		echo "<TR>";
			echo "<TD colspan='2'><HR></TD>";
		echo "</TR>";

		$sql = "SELECT  
				tbl_peca.peca            ,
				referencia              ,
				descricao               
			FROM tbl_peca 
			WHERE tbl_peca.peca='$peca'";

		$res = pg_exec ($con,$sql);

		if(pg_numrows($res)>0){
			$peca						= trim(pg_result ($res,0,peca));
			$referencia					= trim(pg_result ($res,0,referencia));
			$descricao					= trim(pg_result ($res,0,descricao));

			echo "<TR>";
				echo "<TD colspan='2' class='texto'>&nbsp;$descricao&nbsp;<span style='color=#808080; font-size=11;'>Referência: $referencia</span></TD>";
			echo "</TR>";
		}
		echo "<TR>";
			echo "<TD colspan='2' align='center'><IMG SRC='$caminho_final'  BORDER='0' ALT=''></TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD colspan='2' align='center'><font size='-4' color='#999999'>Foto Ilustrativa</font></TD>";
		echo "</TR>";
	echo "</TABLE>";
?>