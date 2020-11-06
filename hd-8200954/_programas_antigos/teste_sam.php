<?include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
?>
<script>
function onoff(id) {
var el = document.getElementById(id);
el.style.display = (el.style.display=="") ? "none" : "";
}
</script>
<?
$contador = 0;
//$referencia = "78909";
if ($handle = opendir('imagens_pecas/pequena/.')) {
			while (false !== ($file = readdir($handle))) {
				$contador++;
				if($contador == 10) break;
				$posicao = strpos($file, $referencia);
				if ($file != "." && $file != ".." ) {
					?>
					<a href="#" onclick="onoff('teste<? echo $contador; ?>')">
					<img src="imagens_pecas/pequena/<? echo $file;?>">
					</a>
					<div id="teste<? echo $contador;?>" style="display:none">
					<img src="imagens_pecas/media/<? echo $file;?>">
					</div><br> 
					<?
				}				
			}
	closedir($handle);
}		

