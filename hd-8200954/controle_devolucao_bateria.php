<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$verifica = $_REQUEST['verifica'];
if($verifica){
	$sql = "SELECT residuo_solido, recusado
				FROM tbl_residuo_solido
				WHERE posto = $login_posto
				AND fabrica = $login_fabrica
				AND (CASE WHEN tbl_residuo_solido.recusado IS NOT TRUE THEN digitacao::date >= current_date - INTERVAL '30 days' ELSE tbl_residuo_solido.recusado IS TRUE END)
				AND tbl_residuo_solido.data_exclusao IS NULL LIMIT 1";
	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){
		$recusado = pg_result($res,0,recusado);
		$residuo_solido = pg_result($res,0,residuo_solido);

		if($recusado == "f"){
			echo "bloqueia";
		} else {
			echo "recusado|$residuo_solido";
		}
	} else {
		echo "libera";
	}
	exit;
}

function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
	list($original_x, $original_y) = getimagesize($img);	//pega o tamanho da imagem

	// se a largura for maior que altura
	if($original_x > $original_y) {
	   $porcentagem = (100 * $max_x) / $original_x;
	}
	else {
	   $porcentagem = (100 * $max_y) / $original_y;
	}

	$tamanho_x	= $original_x * ($porcentagem / 100);
	$tamanho_y	= $original_y * ($porcentagem / 100);
	$image_p	= imagecreatetruecolor($tamanho_x, $tamanho_y);
	$image		= imagecreatefromjpeg($img);

	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
	imagejpeg($image_p, $nome_foto, 65);
}

$qtde_form = $_GET['qtde_linhas'];

$btn_acao = $_POST['btn_acao'];

if($btn_acao == "Gravar"){
	
	$aux_residuo = $_POST['residuo_solido'];

	$linhas = $_POST['qtde_linhas'];

	$nf_devolucao = $_POST['num_nota'];
	$data_nf   =  $_POST['data_nota'];

	if($data_nf){
		list($di, $mi, $yi) = explode("/", $data_nf);
		if(!checkdate($mi,$di,$yi)){ 
			$msg_erro .= "Data Inválida";
		} else {
			$aux_data_nf = "$yi-$mi-$di";
		}
	} else {
		$msg_erro .= "Data da nota fiscal é obrigatória <br>";
	}
	
	if(empty($nf_devolucao)){
		$msg_erro .= "Número da nota fiscal é obrigatório <br>";
	}
	
	$itens = 0;
	for($i = 0; $i < $linhas; $i++){
		$prod_referencia    = $_POST['ref_produto_'.$i];
		$prod_descricao     = $_POST['desc_produto_'.$i];
		$peca_referencia    = $_POST['ref_peca_'.$i];

		if(!empty($prod_referencia) OR !empty($prod_descricao) OR !empty($peca_referencia) OR !empty($peca_descricao) OR !empty($troca_garantia) OR !empty($defeito_cosntatado) OR !empty($cliente) OR !empty($fone) ){
			$itens += 1;
		}
	}
	
	if($itens < 1){
		$msg_erro .= "Preencha pelo menos uma linha<br>";
	}
	$res = pg_query($con,"BEGIN TRANSACTION");
		
	if(empty($msg_erro)){

		if(empty($aux_residuo)){

			$sql = "INSERT INTO tbl_residuo_solido(
								 fabrica          ,               
								 posto            ,           
								 protocolo        ,           
								 nf_devolucao     ,         
								 data_nf          ,        
								 qtde              
								) VALUES(
								 $login_fabrica   ,
								 $login_posto     ,
								 '$protocolo'     ,
								 '$nf_devolucao'  ,
								 '$aux_data_nf'  ,
								 0						 
								) RETURNING residuo_solido";
		} else {

			$sql = "UPDATE tbl_residuo_solido SET 
							nf_devolucao = '$nf_devolucao', 
							data_nf = '$aux_data_nf',
							recusado = FALSE
						WHERE residuo_solido = $aux_residuo";

		}
		$res = pg_query($con,$sql);
		if(strlen(pg_errormessage($con)) > 0){
			$msg_erro .= pg_errormessage($con)."<br>";
		}elseif(empty($aux_residuo)){
			$residuo_solido = pg_result($res,0,residuo_solido);
		}
		
	}
	
		
		$linha_erro = array();
		$cont = 0;
		for($i = 0; $i < $linhas; $i++){
			$prod_referencia    = $_POST['ref_produto_'.$i];
			$prod_descricao     = $_POST['desc_produto_'.$i];
			$peca_referencia    = $_POST['ref_peca_'.$i];
			$peca_descricao     = $_POST['desc_peca_'.$i];
			$troca_garantia     = $_POST['troca_garantia_'.$i];
			$defeito_constatado = $_POST['defeito_constatado_'.$i];
			$cliente            = $_POST['nome_cliente_'.$i];
			$fone               = $_POST['fone_'.$i];
			$aux_residuo_item   = $_POST['residuo_solido_item_'.$i];
			
			if(!empty($prod_referencia) OR !empty($prod_descricao) OR !empty($peca_referencia) OR !empty($peca_descricao) OR !empty($troca_garantia) OR !empty($defeito_constatado) OR !empty($cliente) OR !empty($fone) ){

				if(!empty($prod_referencia) AND !empty($prod_descricao) AND !empty($peca_referencia) AND !empty($peca_descricao) AND !empty($troca_garantia) AND !empty($defeito_constatado) AND !empty($cliente) AND !empty($fone) ){
					
					$cont++;
					$sqlProd = "SELECT produto FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia = '$prod_referencia' AND fabrica = $login_fabrica";
					$resProd = @pg_query($con,$sqlProd);
					if(@pg_numrows($resProd) > 0){
						
						$produto = pg_result($resProd,0,produto);

						$sqlPeca = "SELECT peca FROM tbl_peca WHERE fabrica = $login_fabrica AND referencia = '$peca_referencia'";
						$resPeca = pg_query($con,$sqlPeca);

						if(@pg_numrows($resPeca) > 0){
							$peca = @pg_result($resPeca,0,peca);
						} else {
							$msg_erro .= "Peça não encontrada<br>";
							array_push($linha_erro,$i);
						}

					} else {
						$msg_erro .= "Produto não encontrado<br>";
						array_push($linha_erro,$i);
					}

					if(empty($msg_erro)){

						if(empty($aux_residuo)){
							$sqlItem = "INSERT INTO tbl_residuo_solido_item(
												 residuo_solido     , 
												 peca               ,
												 produto            ,
												 troca_garantia     ,
												 defeito_constatado ,
												 cliente_nome       ,
												 cliente_fone
												) VALUES(
													$residuo_solido   ,
													$peca             ,
													$produto          ,
													'$troca_garantia' ,
													'$defeito_constatado' ,
													'$cliente'        ,
													'$fone'           
												)";
						} else {
							$sqlItem = "UPDATE tbl_residuo_solido_item SET
												peca = $peca,
												produto = $produto,
												troca_garantia = '$troca_garantia',
												defeito_constatado = '$defeito_constatado',
												cliente_nome = '$cliente',
												cliente_fone = '$fone'
											WHERE residuo_solido_item = $aux_residuo_item
											AND residuo_solido = $aux_residuo";
						}

						$resItem = pg_query($con,$sqlItem);
						if(strlen(pg_errormessage($con)) > 0){
							$msg_erro .= pg_errormessage($con);
						}
					}
				} else {
					$msg_erro .= "Todos os campos devem ser preenchidos<br>";
					array_push($linha_erro,$i);
				}
			}
		}
		
		
		if (isset($_FILES['arquivos'])){
			
			// $Destino = "nf_bateria/";

			$a_foto = $_FILES['arquivos'];

			if(strlen($a_foto["name"]) > 0){

				include_once 'class/aws/s3_config.php';

		        include_once S3CLASS;

		        $s3 = new AmazonTC('nf_bateria', $login_fabrica);

		        $tipo  = strtolower(preg_replace("/.+\./", "", $a_foto["name"]));

		        $nome  = $a_foto["name"];

		        $tipo = ($tipo == "jpeg") ? "jpg" : $tipo;

		        if(in_array($tipo, array("jpg", "jpeg", "png", "bmp","pdf","doc"))){

		            $s3->upload($residuo_solido, $_FILES["arquivos"]);

		        }else{

		        	$msg_erro .= "O formato do arquivo $nome não é permitido! <br />";

		        }

				/* if(preg_match('/(jpeg|jpg|pdf)$/i', $Tipo)){
					//echo $Tmpname;
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

					foreach(glob($Destino.$residuo_solido.".*") AS $imagem_ant){
						$filename = basename($imagem_ant);
						$ext2 = ".".substr($filename, strrpos($filename, '.') + 1);
						rename($Destino.$residuo_solido.$ext2,$Destino.$residuo_solido."_ant".$ext2);
					}

					$nome_foto  = "$residuo_solido.$ext";

					$Caminho_foto  = $Destino . $nome_foto;

					if(strtolower($ext)=="pdf"){
						
						$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_foto);
							
						if (file_exists($Caminho_foto)) {
							chmod($Caminho_foto, 0666); 
						}

					}else{
					
						reduz_imagem($Tmpname, '800', '600', $Caminho_foto);
						$copiou_arquivo = file_exists($Caminho_foto);

						if(!$copiou_arquivo){
							$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_foto);
						}
					}
					
					if ($copiou_arquivo and !file_exists($Caminho_foto)) {
						rename($Destino.$residuo_solido."_ant".$ext2,$Destino.$residuo_solido.".".$ext2);
						$copiou_arquivo = false;
					} 
					
					else if  (!$copiou_arquivo){ 
						rename($Destino.$residuo_solido."_ant".$ext2,$Destino.$residuo_solido.".".$ext2);
						$msg_erro.= ($ext=='pdf') ? 'Documento não anexado.':'Imagem não anexada.';
					} 
					
					else {
						foreach(glob($Destino.$residuo_solido.'_ant.*') AS $imagem_ant){
							$filename = basename($imagem_ant);
							$ext2 = ".".substr($filename, strrpos($filename, '.') + 1);
							unlink($Destino.$residuo_solido."_ant".$ext2);
						}

					}
				}else{
						$msg_erro = "O formato do arquivo $Nome não é permitido!<br>São permitidas apenas imagens JPG ou PDF<br>";
				} */

			} else{
				$msg_erro .= "Nota Fiscal obrigatória <br />";
			}
		} else {
			$msg_erro .= "Nota Fiscal obrigatória <br />";
		}
		

		
			if(empty($msg_erro)){
				$protocolo = "DB".str_pad($residuo_solido,6,'0',STR_PAD_LEFT);
				$sql = "UPDATE tbl_residuo_solido SET
								qtde = $cont,
								protocolo = '$protocolo'
							WHERE residuo_solido = $residuo_solido";
				$res = pg_query($con,$sql);
				if(strlen(pg_errormessage($con)) > 0){
					$msg_erro.= pg_errormessage($con)."<br>";
				}
			}
		
		
		if(empty($msg_erro)){
			$res = pg_query($con,"COMMIT TRANSACTION");
			header("Location: $PHP_SELF?msg=$protocolo&ok=1");
		} else {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	

}

$msg = $_GET['msg'];
$layout_menu = "os";
$title = "Relatório de Devolução das Baterias";

include "cabecalho.php";
?>
<style type="text/css">
	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script language='javascript' src='ajax.js'></script>
<script src="js/jquery.maskedinput-1.3.min.js" type="text/javascript"></script>
<script src="js/jquery.alphanumeric.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">

	$().ready(function(){	
		Shadowbox.init();	
		
		$(".mask_tel").mask('(99)9999-9999');
		$("input[name=data_nota]").mask('99/99/9999');
		$("#num_nota").numeric();
		
	});
	
	function informativo(){	
		Shadowbox.open({
			content:	"informativo_devolucao_bateria.php",
			player:	"iframe",
			title:	"Informações ",
			width:	800,
			height:	500
		});		
	}

	function mostrarCampo(){
		//var campo = document.getElementById("qtde_itens");

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?verifica=1",
			cache: false,
			success: function(data){					
				retorno = data.split('|');
				if(retorno[0] =="bloqueia"){
					$('#qtde_itens').toggle();
					$('#qtde_itens').css('color','#FF0000');
					$('#qtde_itens').css('font-weight','bold');
					$('#qtde_itens').css('font-size','14px');
					$('#qtde_itens').html('A devolução de baterias deve ser feita mensalmente e foi detectado pelo sistema que o seu posto gravou um relatório de devolução a menos de 30 dias. Gentileza aguardar o prazo correto para abrir um novo relatório');
				} else if(retorno[0] == "recusado"){
					window.location="<?php echo $PHP_SELF;?>?residuo_solido="+retorno[1]+"";
				} else{
					$('#qtde_itens').toggle();
				}
			}
		});
		
	}

	function abreFormulario(){
		var qtde = document.getElementById("qtde").value;
		window.location="<?php echo $PHP_SELF;?>?qtde_linhas="+qtde+"";
	}


	//PESQUISA PECA
	function pesquisaPeca(peca,tipo,posicao){

		var ref_produto = $("input[name=ref_produto_"+posicao+"]").val();
		var prod = $("input[name=produto_"+posicao+"]").val();

		if(ref_produto == ""){
			prod = "";
		}

		if (jQuery.trim(peca.value).length > 2){
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?"+tipo+"="+peca.value+"&posicao="+posicao+"&bateria=1&produto="+prod,
				player:	"iframe",
				title:		"Peça",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			peca.focus();
		}

	}
	
	function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo,posicao){
		gravaDados('ref_peca_'+posicao,referencia);
		gravaDados('desc_peca_'+posicao,descricao);
		gravaDados('peca_'+posicao,peca);
	}

	
	function pesquisaProduto(produto,tipo,posicao){
		
		var ref_peca = $("input[name=ref_peca_"+posicao+"]").val();
		var pec = $("input[name=peca_"+posicao+"]").val();

		if(ref_peca == ""){
			pec = "";
		}

		if (jQuery.trim(produto.value).length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_nv.php?"+tipo+"="+produto.value+"&posicao="+posicao+"&peca="+pec,
				player:	"iframe",
				title:		"Produto",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			produto.focus();
		}
	}

	function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,posicao){
		gravaDados("ref_produto_"+posicao,referencia);
		gravaDados("desc_produto_"+posicao,descricao);
		gravaDados("produto_"+posicao,produto);
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}



</script>

<?php
			if(!empty($msg)){
		?>
				<table width="700" align="center">
					<tr class="sucesso"><td>Protocolo <?php echo $msg; ?> gravado com sucesso</td></tr>
				</table>
		<?php

			}
		?>
<br />

<?php
			if(!empty($msg)){
		?>
				<table width="700" align="center">
					<tr class="sucesso"><td>Será aprovado num prazo de aproximadamente 48 horas. Gentileza consultar o relatório nesse prazo para verificar a autorização de envio das baterias.</td></tr>
				</table>
		<?php

			}
		?>
<br />
<div class="texto_avulso" style="width:700px;">
	<?php if($login_fabrica== 1){ ?>
			<a href="javascript:void(0)" style="font-wheight:bold;" onclick="javascript: informativo();">RELATÓRIO DE DEVOLUÇÃO DE BATERIAS</a> <br />
			(<a href="javascript:void(0)" style="font-wheight:bold;" onclick="javascript: informativo();">clique aqui</a> para obter mais informações sobre esse processo e emissão da NF de Devolução) <br /> <br />
<?php } else{ ?>
			<b>RELATÓRIO DE DEVOLUÇÃO DE BATERIAS</b> <br />
			<br /> <br />
<?php }  ?>
	<a href="javascript:void(0)" style="font-wheight:bold;" onclick="javascript: mostrarCampo();">Abrir novo relatório</a> &nbsp;&nbsp;
	<a href="relatorio_devolucao_bateria.php" style="font-wheight:bold;" target="_blank">Consultar Relatório</a>
</div>

<br />

<div class="texto_avulso" style="width:700px; text-align:justify;">
	<p style="font-size:14px;">Após o envio das baterias é imprescindível guardar o comprovante emitido pelos Correios para inserir a informação no site Telecontrol nessa mesma tela "Consultar relatório".</p>
	<p style="font-size:14px;"> Lembrando que, as baterias deverão ser embaladas em sacos plásticos com os seus respectivos códigos e colocadas dentro de uma caixa juntamente com a Nota fiscal emitida.</p>
</div>

<div id="qtde_itens" style="width:700px; display:none; text-align:center;" class='formulario'>
	<p>
		Qtde de Itens para Devolução: &nbsp; <input type="text" size="5" name="qtde" id="qtde"> &nbsp; <input type="button" value="OK" onclick="javascript:abreFormulario();">
	</p>
</div>

<?php
	$residuo_solido = $_GET['residuo_solido'];

	if(!empty($qtde_form) OR !empty($residuo_solido)){ 
?>
		<br />
		<?php
			if(!empty($msg_erro)){
		?>
				<table width="700" align="center">
					<tr class="msg_erro"><td><?php echo $msg_erro; ?></td></tr>
				</table>
		<?php

			}
		?>
		<form name="frm_cadastro" method="post" enctype='multipart/form-data'>
			<table align="center" class="formulario">
				<caption class="titulo_tabela">Cadastro</caption>
				<tr class="titulo_coluna">
					<td>Ref. Produto</td>
					<td>Desc. Produto</td>
					<td>Ref. Bateria</td>
					<td>Desc. Bateria</td>
					<td> Troca Garantia</td>
					<td>Def. Constatado</td>
					<td>Nome cliente</td>
					<td>Telefone</td>
				</tr>
				<?php
					if(!empty($residuo_solido)){
						$sql = "SELECT  tbl_produto.referencia AS cod_produto,
										tbl_produto.descricao AS produto,
										tbl_peca.referencia AS cod_peca,
										tbl_peca.descricao AS peca,
										tbl_residuo_solido_item.residuo_solido_item,
										tbl_residuo_solido_item.troca_garantia,
										tbl_residuo_solido_item.defeito_constatado,
										tbl_residuo_solido_item.cliente_nome,
										tbl_residuo_solido_item.cliente_fone,
										tbl_residuo_solido.nf_devolucao,
										TO_CHAR(tbl_residuo_solido.data_nf,'DD/MM/YYYY') AS data_nf
									FROM tbl_residuo_solido
									JOIN tbl_residuo_solido_item ON tbl_residuo_solido_item.residuo_solido = tbl_residuo_solido.residuo_solido AND tbl_residuo_solido.fabrica = $login_fabrica
									JOIN tbl_produto ON tbl_produto.produto = tbl_residuo_solido_item.produto AND tbl_produto.fabrica_i = $login_fabrica
									JOIN tbl_peca ON tbl_peca.peca = tbl_residuo_solido_item.peca AND tbl_peca.fabrica = $login_fabrica
									WHERE tbl_residuo_solido.residuo_solido = $residuo_solido";
						$res = pg_query($con,$sql);
						$qtde_form = pg_numrows($res);
					}
					for($i = 0; $i < $qtde_form; $i++){ 
						
						if(empty($residuo_solido)){
							$prod_referencia    = $_POST['ref_produto_'.$i];
							$prod_descricao     = $_POST['desc_produto_'.$i];
							$peca_referencia    = $_POST['ref_peca_'.$i];
							$peca_descricao     = $_POST['desc_peca_'.$i];
							$troca_garantia     = $_POST['troca_garantia_'.$i];
							$defeito_constatado = $_POST['defeito_constatado_'.$i];
							$cliente            = $_POST['nome_cliente_'.$i];
							$fone               = $_POST['fone_'.$i];
						} else {
							$prod_referencia     = pg_result($res,$i,cod_produto);
							$prod_descricao      = pg_result($res,$i,produto);
							$peca_referencia     = pg_result($res,$i,cod_peca);
							$peca_descricao      = pg_result($res,$i,peca);
							$residuo_solido_item = pg_result($res,$i,residuo_solido_item);
							$troca_garantia      = pg_result($res,$i,troca_garantia);
							$defeito_constatado  = pg_result($res,$i,defeito_constatado);
							$cliente             = pg_result($res,$i,cliente_nome);
							$fone                = pg_result($res,$i,cliente_fone);
							$nf_devolucao        = pg_result($res,$i,nf_devolucao);
							$data_nf             = pg_result($res,$i,data_nf);
						}

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						if(isset($linha_erro)){
							if(in_array($i,$linha_erro)){
								$cor = "#FA8072";
							}
						}
				?>
						<tr bgcolor="<?php echo $cor; ?>">
							<td>
								<input type="text" size="8" name="ref_produto_<?php echo $i; ?>" value="<?php echo $prod_referencia;?>">
								&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_cadastro.ref_produto_<?php echo $i; ?>,'referencia',<?php echo $i; ?>)" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'>
								<input type="hidden" name="produto_<?php echo $i; ?>">
								<input type="hidden" name="residuo_solido_item_<?php echo $i; ?>" value="<?php echo $residuo_solido_item; ?>">
						</td>

							<td>
								<input type="text" size="25" name="desc_produto_<?php echo $i; ?>"  value="<?php echo $prod_descricao;?>">
								&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_cadastro.desc_produto_<?php echo $i; ?>,'descricao',<?php echo $i; ?>)" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>
							</td>

							<td>
								<input type="text" size="8" name="ref_peca_<?php echo $i; ?>" value="<?php echo $peca_referencia;?>">
								<a href="javascript: pesquisaPeca(document.frm_cadastro.ref_peca_<?php echo $i; ?>,'referencia',<?php echo $i; ?>)">
								<IMG SRC='imagens/lupa.png' style='cursor : pointer'></a>
								<input type="hidden" name="peca_<?php echo $i; ?>">
							</td>

							<td><input type="text" size="25" name="desc_peca_<?php echo $i; ?>" value="<?php echo $peca_descricao;?>"></td>

							<td>
								Sim<input type="radio" name="troca_garantia_<?php echo $i; ?>" value="t" <?php if($troca_garantia=="t") echo "CHECKED";?>>&nbsp;
								Não<input type="radio" name="troca_garantia_<?php echo $i; ?>" value="f" <?php if($troca_garantia=="f") echo "CHECKED";?>>
							</td>

							<td><input type="text" size="25" name="defeito_constatado_<?php echo $i; ?>"  value="<?php echo $defeito_constatado;?>"></td>

							<td><input type="text" size="25" name="nome_cliente_<?php echo $i; ?>"  value="<?php echo $cliente;?>"></td>

							<td><input type="text" size="14" name="fone_<?php echo $i; ?>" class="mask_tel"  value="<?php echo $fone;?>"></td>
						</tr>
				<?php

					}
				?>
					<tr><td colspan="8">&nbsp;</td></tr>
					<tr>
						<td colspan="8" align="center">
							<input type="hidden" value="<?php echo $qtde_form; ?>" name="qtde_linhas">
							Nº da NF : <input type="text" size="20" maxlength="20" name="num_nota" id="num_nota" value="<?php echo $nf_devolucao; ?>" > &nbsp;
							Data NF  : <input type="text" size="12" name="data_nota" value="<?php echo $data_nf; ?>">&nbsp;&nbsp;&nbsp;&nbsp;
							<?php echo ' Anexar nota fiscal : &nbsp;<input type="file" size="35" value="Procurar foto" name="arquivos" />'; ?>
						</td>
					</tr>
					<tr><td colspan="8">&nbsp;</td></tr>
					<tr>
						<td colspan="8" align="center">
							<input type="hidden" name="residuo_solido" value="<?php echo $residuo_solido; ?>">
							<input type="submit" name="btn_acao" value="Gravar">
						</td>
					</tr>
					<tr><td colspan="8">&nbsp;</td></tr>
			</table>
		</form>
<?php
		if(!empty($residuo_solido)){

			$sql = "SELECT historico,
						   TO_CHAR(data_input,'DD/MM/YYYY') AS data_input
						FROM tbl_residuo_solido_historico
						WHERE residuo_solido = $residuo_solido";
			$res = pg_query($con,$sql);
?>
			<br />
			<table width="700" align='center' class='tabela'>
				<caption class='titulo_tabela'>Histórico de reprovas</caption>
				<tr class='titulo_coluna'>
					<td>Data</td>
					<td>Motivo</td>
				</tr>
<?php
			for($i = 0; $i < pg_numrows($res); $i++){
				$data   = pg_result($res,$i,data_input);
				$motivo = pg_result($res,$i,historico);
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
				<tr bgcolor="<?php echo $cor; ?>">
					<td><?php echo $data; ?></td>
					<td><?php echo $motivo; ?></td>
				</tr>
<?php
			}
?>
			</table>
<?php
		}
	}
?>
