<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "funcoes.php";
include 'autentica_usuario.php';

 $array_estado = array(""=>"","AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if($_GET['residuo_id']){
	$residuo_solido = $_GET['residuo_id'];
	$num_objeto = $_GET['objeto'];
			
	$sql = "SELECT admin_aprova FROM tbl_residuo_solido WHERE residuo_solido = $residuo_solido";
	$res = pg_query($con,$sql);
	$admin = pg_result($res,0,$posto);

	$sql = "SELECT COUNT(1) AS qtde FROM tbl_residuo_solido_item WHERE residuo_solido = $residuo_solido";
	$res = pg_query($con,$sql);
	$qtde = pg_result($res,0,qtde);
	$total = $qtde * 2;

	$res = pg_query($con,'BEGIN TRANSACTION');
	
	$sql = "UPDATE tbl_residuo_solido SET confirmar_envio = CURRENT_TIMESTAMP, codigo_transporte = '$num_objeto' WHERE residuo_solido = $residuo_solido";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	if(empty($msg_erro)){
		$sql = "INSERT INTO tbl_extrato_lancamento(
													posto,
													fabrica,
													lancamento,
													descricao,
													debito_credito,
													valor,
													admin
													) VALUES(
													$login_posto,
													$login_fabrica,
													266,
													'Crédito de devolução de baterias',
													'C',
													$total,
													$admin
													) RETURNING extrato_lancamento";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if(empty($msg_erro)){
			$extrato_lancamento = pg_result($res,0,extrato_lancamento);

			$sql = "UPDATE tbl_residuo_solido SET
							extrato_lancamento     = $extrato_lancamento,
							total				   = $total
						WHERE residuo_solido = $residuo_solido";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	if(empty($msg_erro)){
		$res = pg_query($con,'COMMIT TRANSACTION');
		echo "OK";
	} else{
		$res = pg_query($con,'ROLLBACK TRANSACTION');
		echo $msg_erro;
	}
	
	exit;
}

if($_GET['div_motivo']){

	$residuo_solido = $_GET['linha'];

	$sql = "SELECT tbl_produto.referencia AS produto_referencia, 
					   tbl_produto.descricao AS produto_descricao, 
					   tbl_peca.referencia AS peca_referencia, 
					   tbl_peca.descricao AS peca_descricao, 
					   tbl_residuo_solido_item.troca_garantia,
					   tbl_residuo_solido_item.defeito_constatado, 
					   tbl_residuo_solido_item.cliente_nome, 
					   tbl_residuo_solido_item.cliente_fone 
					  FROM tbl_residuo_solido_item 
					  JOIN tbl_residuo_solido ON tbl_residuo_solido.residuo_solido = tbl_residuo_solido_item.residuo_solido AND tbl_residuo_solido.fabrica = $login_fabrica
					  JOIN tbl_produto ON tbl_produto.produto = tbl_residuo_solido_item.produto 
					  JOIN tbl_peca ON tbl_peca.peca = tbl_residuo_solido_item.peca AND tbl_peca.fabrica = $login_fabrica 
					WHERE tbl_residuo_solido_item.residuo_solido = $residuo_solido";

		$res = pg_query($con,$sql);

		if(pg_numrows($res) > 0){
			$resultado = "<td colspan='100%'><table width='100%' class='tabela'>
				<caption class='titulo_tabela'>Itens da Devolu&ccedil;&atilde;o</caption>
				<tr class='titulo_coluna'>
					<td>Produto</td>
					<td>Pe&ccedil;a</td>
					<td>Garantia</td>
					<td>Defeito</td>
					<td>Cliente</td>
					<td>Fone</td>
				</tr>";

				for($i = 0; $i < pg_numrows($res); $i++){
					$ref_produto	= pg_result($res,$i,produto_referencia);
					$desc_produto	= pg_result($res,$i,produto_descricao);
					$ref_peca	= pg_result($res,$i,peca_referencia);
					$desc_peca	= pg_result($res,$i,peca_descricao);
					$garantia	= pg_result($res,$i,troca_garantia);
					$defeito	= pg_result($res,$i,defeito_constatado);
					$cliente	= pg_result($res,$i,cliente_nome);
					$fone		= pg_result($res,$i,cliente_fone);
					
					$garantia = ($garantia == "t") ? "Sim" : "N&atilde;o";
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					$resultado .= "<tr bgcolor='$cor'>
						<td>$ref_produto - $desc_produto</td>
						<td>$ref_peca - $desc_peca</td>
						<td>$garantia</td>
						<td>$defeito</td>
						<td>$cliente</td>
						<td>$fone</td>
					</tr>";
				}
			$resultado .= "</table></td>";

			echo $resultado;
		} else {
			echo "<td colspan='100%'><center>Nenhum resultado encontrado</center></td>";
		}
	
	exit;

}

if($_GET['exclui']){
	$residuo_solido = $_GET['linha'];
	$motivo = $_GET['motivo'];
	
	$sql = "UPDATE tbl_residuo_solido SET
					data_exclusao = current_timestamp,
					motivo_exclusao = '$motivo',
					admin_exclusao = $login_admin
			    WHERE residuo_solido = $residuo_solido";
	$res = pg_query($con,$sql);
	$erro = pg_errormessage($con);
	if(empty($erro)){
		echo "ok";
	} else {
		echo "Erro ao excluir";
	}
	exit;
}

$msg_erro = "";
$btn_acao = $_POST['btn_acao'];

if($btn_acao == "pesquisar"){

	include_once 'class/aws/s3_config.php';
    include_once S3CLASS;
    $s3 = new AmazonTC("nf_bateria", $login_fabrica);

	//VALDAÇÕES - INÍCIO
	$data_inicial 		= $_POST['data_inicial'];
	$data_final 		= $_POST['data_final'];
	$numero_devolucao 	= strtoupper($_POST['num_devolucao']);
	$nota_fiscal 		= $_POST['nf_devolucao'];
	
	if(empty($data_inicial) and empty($data_final) and empty($numero_devolucao) and empty($nota_fiscal)){
		$msg_erro = "Informe algum parâmetro para pesquisa";
	}
	//validação data - início
		if(!empty($data_inicial) and !empty($data_final)){

			list($di, $mi, $yi) = explode("/", $data_inicial);
		    if(!checkdate($mi,$di,$yi)) 
		        $msg_erro = "Data Inicial Inválida";

			list($df, $mf, $yf) = explode("/", $data_final);
		    if(!checkdate($mf,$df,$yf)) 
		        $msg_erro = "Data Final Inválida";

			if(strlen($msg_erro)==0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";
			}
			if(strlen($msg_erro)==0){
				if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
				or strtotime($aux_data_final) > strtotime('today')){
				    $msg_erro = "Data Inválida.";
				} else {
					$cond = " AND tbl_residuo_solido.digitacao BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
				}
			}
		}
	//validação data - fim


	//validação Número e Nota de Devolução - início
		if(!empty($numero_devolucao)){
			$cond .= " AND tbl_residuo_solido.protocolo = '$numero_devolucao' ";
		}

		if(!empty($nota_fiscal)){
			$cond .= " AND tbl_residuo_solido.nf_devolucao = '$nota_fiscal' ";
		}
	//validação Número e Nota de Devolução - fim


	//VALDAÇÕES - FIM
}

$layout_menu = "os";
$title = "RELATÓRIO DE DEVOLUÇÃO DE BATERIAS";
include "cabecalho.php";
?>

<style type="text/css">

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
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

table.tabela table tr td{
	border:0;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<?php include "javascript_calendario.php"; ?>

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script src="js/jquery.alphanumeric.js" type="text/javascript"></script>
<script type="text/javascript">

	$().ready(function(){
		Shadowbox.init();
		
		$("#data_inicial").datePicker({startDate : "01/01/2000"});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").datePicker({startDate : "01/01/2000"});
		$("#data_final").maskedinput("99/99/9999");

		$("#nf_devolucao").numeric();
	});

	
	function mostraDiv(num,id){
		var div = document.getElementById("linha_itens_"+id).style;		
		if(div.display == "none"){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?div_motivo="+num+"&linha="+id,
				cache: false,
				success: function(data){
					$('#linha_itens_'+id).html(data);	
					$('#linha_itens_'+id).toggle();	
				}
			});
		} else {
			div.display = "none";
		}
	}
	
	function ocultaMsg(){
		$("#msg_retorno").toggle();
	}

	function confirmaEnvio(id){	
		
		var objeto = document.getElementById("num_objeto_"+id).value;

		if (jQuery.trim(objeto).length != 13){
			alert("Número do objeto deve ter 13 dígitos");
			return false;
		}

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?residuo_id="+id+"&objeto="+objeto,
			cache: false,
			success: function(data){
				if(data == "OK"){
					$("#msg_retorno").html("Envio confirmado com sucesso");
					$("#msg_retorno").attr("class","sucesso");
					$("#msg_retorno").toggle();
					$("#confirma_envio_"+id).parent('td').html('Envio confirmado em <?php echo date("d/m/Y");?>');
					$("#confirma_envio_"+id).remove();
					setTimeout('ocultaMsg()', 3000);
				} else {
					$("#msg_retorno").html(data);
					$("#msg_retorno").attr("class","msg_erro");
					$("#msg_retorno").toggle();
					setTimeout('ocultaMsg()', 3000);
				}
			}
		});
		
	}

	function campoObjeto(id){
		$("#objeto_"+id).toggle();
	}

</script>

<div style="width:700px; display:none;" id="msg_retorno"></div>

<form name="frm_consulta" method="post" action="<? echo $PHP_SELF ?>">
	<table width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario'>
		<?if(strlen($msg_erro)>0){ ?>
			<tr class="msg_erro">
				<td colspan='5'><? echo $msg_erro; ?></td>
			</tr>
		<?}?>
		
		<?if(strlen($msg)>0){ ?>
			<tr class="sucesso">
				<td colspan='5'><? echo $msg; ?></td>
			</tr>
		<?}?>
		
		<tr class="titulo_tabela">
			<td colspan='5'>Parâmetros de Pesquisa</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>

		<tr>
			<td width='250'>&nbsp;</td>
			<td align="left" width='130'>
				Data Inicial
				<br>
				<input class="frm" type="text" name="data_inicial" id="data_inicial" size="12" value="<? echo $data_inicial ?>" >
			</td>
			<td align="left">
				Data Final
				<br>
				<input class="frm" type="text" name="data_final" id="data_final" size="12" value="<? echo $data_final ?>" >
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>
		
		<tr>
			<td width='100'>&nbsp;</td>
			<td align="left">
				Número Devolução<br>
				<input type="text" name="num_devolucao" id="num_devolucao" size="15" value="<?php echo $numero_devolucao;?>" class="frm">
			</td>
			<td align="left">
				NF Devolução<br>
				<input type="text" name="nf_devolucao" id="nf_devolucao" size="15" value="<?php echo $nf_devolucao;?>" class="frm">
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>

		<tr>
			<td colspan='5'>
				<center>
					<input type='hidden' name='btn_acao' value=''>
					<input type="button" style="cursor:pointer;" value="Pesquisar" onclick="if (document.frm_consulta.btn_acao.value == '') { document.frm_consulta.btn_acao.value='pesquisar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar'>
				</center>
			</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>
	</table>
</form> <br />

<?php
	if(!empty($btn_acao) and empty($msg_erro)){
		
		$sql = "SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome as posto_nome,
						tbl_residuo_solido.residuo_solido,
						tbl_residuo_solido.protocolo,
						tbl_residuo_solido.qtde,
						tbl_residuo_solido.nf_devolucao,
						tbl_residuo_solido.data_aprova,
						tbl_residuo_solido.numero_devolucao,
						TO_CHAR(tbl_residuo_solido.confirmar_envio::date,'DD/MM/YYYY') AS confirmar_envio,
						TO_CHAR(tbl_residuo_solido.data_devolucao_inicial,'DD/MM/YYYY') AS data_devolucao_inicial,
						TO_CHAR(tbl_residuo_solido.data_devolucao_final,'DD/MM/YYYY') AS data_devolucao_final,
						tbl_residuo_solido.extrato_lancamento,
						tbl_residuo_solido.recusado
					FROM tbl_residuo_solido
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_residuo_solido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					WHERE tbl_residuo_solido.fabrica = $login_fabrica
					AND tbl_residuo_solido.posto = $login_posto
					AND tbl_residuo_solido.data_exclusao IS NULL
					$cond";
		$res = pg_query($con,$sql);
#echo nl2br($sql);
		if(pg_numrows($res) > 0){
?>
			<table align="center" class="tabela">
				<caption class="titulo_tabela">Resultado</caption>

					<tr class="titulo_coluna">
						<td>Posto</td>
						<td>N° do Relatório</td>
						<td>Qtde</td>
						<td>Consultar</td>
						<td>NF Devolução</td>
						<td>Status</td>
						<td>Ação</td>
					</tr>
<?php
			$dir_nf = "nf_bateria/";
			$dir_pac = "nf_bateria/correio/";
			
			for($i = 0; $i < pg_numrows($res); $i++){
				$residuo_solido				= pg_result($res,$i,residuo_solido);
				$codigo_posto				= pg_result($res,$i,codigo_posto);
				$posto_nome					= pg_result($res,$i,posto_nome);
				$protocolo					= pg_result($res,$i,protocolo);
				$data_aprova				= pg_result($res,$i,data_aprova);
				$qtde						= pg_result($res,$i,qtde);
				$nf_devolucao				= pg_result($res,$i,nf_devolucao);
				$numero_devolucao			= pg_result($res,$i,numero_devolucao);
				$data_devolucao_inicial	    = pg_result($res,$i,data_devolucao_inicial);
				$data_devolucao_final	    = pg_result($res,$i,data_devolucao_final);
				$extrato_lancamento			= pg_result($res,$i,extrato_lancamento);
				$confirmar_envio			= pg_result($res,$i,confirmar_envio);
				$recusado					= pg_result($res,$i,recusado);

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$img_nf_bateria = $s3->getObjectList($residuo_solido);
		        $img_nf_bateria = basename($img_nf_bateria[0]);
		        $img_nf_bateria = $s3->getLink($img_nf_bateria);

		        if(strlen($img_nf_bateria) > 0){
		        	$link_nf_bateria = "<a href='$img_nf_bateria' rel='shadowbox' title='Nota Fiscal: $nf_devolucao'>$nf_devolucao</a>";
		        }else{
		        	$link_nf_bateria = $nf_devolucao;
		        }

?>
				<tr bgcolor="<?php echo $cor; ?>" id="linha_<?php echo $residuo_solido; ?>">
					<td><?php echo $codigo_posto." - ".$posto_nome; ?></td>
					<td align='center'><?php echo $protocolo; ?></td>
					<td align='center'><?php echo $qtde; ?></td>
					<td> <input type="button" value="Consultar" onclick="mostraDiv(3,<?php echo $residuo_solido; ?>)"> </td>
					<td align='center'><?php echo $link_nf_bateria; ?></td>
					<td align='center'>
						<?php if(empty($data_aprova)){	
								if($recusado == "t"){
									echo "Recusado";
								} else {
									echo "Aguardando Aprovação";
								}
							  } else {
								  if($ext == "jpg"){
										echo "<a href='devolucao_bateria_print.php?residuo_solido=$residuo_solido' target='_blank'>Imprimir comprovante<br> para devolução</a>";
								  } else{
										echo "<a href='".$dir_pac.$arquivo_pac."' target='_blank'>Imprimir comprovante<br> para devolução</a>";
								  }
							  }  
						?>
					</td>
					<?php if(empty($confirmar_envio)){ ?>
							<td align='center'>
								<?php
								if(empty($data_aprova)){
									if($recusado == "t"){
										echo "<input type='button' value='Alterar' onclick=\"window.open('controle_devolucao_bateria.php?residuo_solido=$residuo_solido');\">";
									} else {
										echo "Aguardando Aprovação";
									}
								}else{
								?>
									<input type='button' value='Confirmar Envio' id="confirma_envio_<?php echo $residuo_solido; ?>" onclick="campoObjeto(<?php echo $residuo_solido; ?>);">
									<br />
									<span style="display:none" id="objeto_<?php echo $residuo_solido; ?>">
										Nº Objeto
										<input type="text" size="15" maxlength="13" class="frm" name="num_objeto_<?php echo $residuo_solido; ?>" id="num_objeto_<?php echo $residuo_solido; ?>">
										<input type='button' value='OK' id="confirma_envio_<?php echo $residuo_solido; ?>" onclick="confirmaEnvio(<?php echo $residuo_solido; ?>);">
									</span>

								<?php
								}
								?>
							</td>
					<?php } else {?>
							<td align='center'>Envio confirmado em <?php echo $confirmar_envio; ?></td>
					<?php } ?>
				</tr>
				<tr id="linha_itens_<?php echo $residuo_solido; ?>" style="width:700px; display:none; "> </tr>
<?php
			}
			echo "</table>";
		} else {
			echo "<center>Nenhum resultado encontrado</center>";
		}
	}
?>

<?php include "rodape.php"; ?>
