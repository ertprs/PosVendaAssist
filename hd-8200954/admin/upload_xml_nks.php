<?php
include "../includes2/xml2array.php";
$hd_chamado = $_GET['hd_chamado'];
?>

<style type="text/css">

.formulario{
	font:11px Arial;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<?php
if($_POST['btn_acao']){
	$Arquivo = $_FILES['arquivos'];
			$Nome    = $Arquivo['name'];
			$Tamanho = $Arquivo['size'];
			$Tipo    = $Arquivo['type'];
			$Tmpname = $Arquivo['tmp_name'];
			$Destino = "xml/";

				if(strlen($Nome)>0){
					if(preg_match('/(xml)$/i', $Tipo)){
						
						if(!is_uploaded_file($Tmpname)){
							$msg_erro .= "Não foi possível efetuar o upload.<br>";
							break;
						}

						$tmp = explode(".",$Nome);
						$ext = $tmp[count($tmp)-1];

						if (strlen($Nome)==0){
							$ext = $Nome;
						}

						$ext = strtolower($ext);

						$nome_xml  = "$hd_chamado_correio.$ext";

						$Caminho_xml  = $Destino . $nome_xml;

						if($ext == "xml"){
							if (file_exists($Caminho_xml)) { 
								if (!unlink($Caminho_xml)) {
									$msg_erro .= "Não foi possível excluir o arquivo $Nome".".xml!<br>\n";
								} else {
									$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_xml);
								}
							} else {
								$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_xml);
							}

					} else{
						$msg_erro .= "Arquivo XML é obrigatório";
					}
			} else {
				$msg_erro .= "O formato do arquivo $Nome não é permitido!<br>Apenas permitido formato XML";
			}
		}
}

$caminho = "xml/$hd_chamado_correios.xml";

if(file_exists($caminho)){
	
	$xmlstr = xml2array($caminho);
	//print_r($xmlstr);
	$codigo_rastreio = $xmlstr['logisticareversa']['resultado_solicitacao']['objeto_postal']['numero_coleta'];
	//$codigo_rastreio = "so268239602BR";

	echo "<br><span class='formulario'>Nº Rastreio : <a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$codigo_rastreio' target='_blank'>$codigo_rastreio</a></span>";
							
}else{
?>

<?php
	if(!empty($msg_erro)){
?>
		<table width='500' align='center' class='msg_erro'>
			<tr><td><?php echo $msg_erro; ?></td></tr>
		</table>
<?php
	}
?>

<form name='frm_xml' method='post' enctype='multipart/form-data' class='formulario'>
				<input type='file' size='15' value='Procurar imagem' name='arquivos' />
		
				<input type='hidden' name='residuo_solido' value="<?php echo $callcenter; ?>">
				<input type='submit' name='btn_acao' value='Enviar Arquivo Postagem Correios'>
			</td>
		</tr>
	</table>
</form>
<?php
}
?>
