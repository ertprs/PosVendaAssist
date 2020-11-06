<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "funcoes.php";
$admin_privilegios="auditoria";
include 'autentica_admin.php';

 $array_estado = array(""=>"","AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if($_GET['div_motivo']){

	$residuo_solido = $_GET['linha'];

	if($_GET['div_motivo']==1 OR $_GET['div_motivo']==4){
		
		$onclick = ($_GET['div_motivo']==1) ? "onclick='excluiDevolucao($residuo_solido)'" : "onclick='reprovaDevolucao($residuo_solido)'";
		echo "<td colspan='100%'><table width='100%' align='center' class='formulario'>
				<tr>
					<td>Motivo:</td>
					<td><input type='text' name='motivo_".$residuo_solido."' id='motivo_".$residuo_solido."' size='60' class='frm'></td>
					<td><input type='button' value='Confirmar' $onclick> </td>
				</tr>
			  </table></td>";

	} elseif($_GET['div_motivo']==2) {

		echo "<td colspan='100%'> <iframe src='relatorio_devolucao_bateria_frame.php?residuo_solido=".$residuo_solido."' width='700' height='185'></iframe> </td>";

	} else {

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
						<td align='left'>$ref_produto - $desc_produto</td>
						<td align='left'>$ref_peca - $desc_peca</td>
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

			$sql = "SELECT   tbl_posto.email AS posto_email, tbl_admin.email AS admin_email, tbl_residuo_solido.protocolo,tbl_residuo_solido.posto,tbl_residuo_solido.motivo_exclusao
						FROM tbl_residuo_solido 
						JOIN tbl_posto ON tbl_posto.posto = tbl_residuo_solido.posto 
						JOIN tbl_admin ON tbl_admin.admin = tbl_residuo_solido.admin_exclusao
						WHERE tbl_residuo_solido.residuo_solido = $residuo_solido
						AND tbl_residuo_solido.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) > 0){

				$email_cadastros	 = pg_fetch_result($res,0,posto_email);
				$admin_email		 = pg_fetch_result($res,0,admin_email);
				$protocolo			 = pg_fetch_result($res,0,protocolo);
				$posto				 = pg_fetch_result($res,0,posto);
				$motivo_exclusao	 = pg_fetch_result($res,0,motivo_exclusao);

				$sql = "INSERT INTO tbl_comunicado(mensagem,tipo,fabrica,posto,ativo,obrigatorio_site) VALUES('Relatório $protocolo reprovado pelo seguinte motivo : $motivo_exclusao','Comunicado',$login_fabrica,$posto,true,true)";
				$res = pg_query($con,$sql);
				$erro = pg_errormessage($con);

				$remetente    = $admin_email;
				$destinatario = $email_cadastros ;
				$assunto      = "Devolução de Baterias B&D";
				$mensagem     = "Prezado, <br> o relatório $protocolo foi reprovado pelo seguinte motivo : <br><br> $motivo.";
				$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
				mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
			}
	}

	if(empty($erro)){
		echo "ok";
	} else {
		echo "Erro ao excluir";
	}
	exit;
}

if($_GET['reprova']){
	$residuo_solido = $_GET['linha'];
	$motivo = $_GET['motivo'];
	
	$sql = "UPDATE tbl_residuo_solido SET
					recusado = TRUE
			    WHERE residuo_solido = $residuo_solido";
	$res = pg_query($con,$sql);
	$erro = pg_errormessage($con);
	
	
	if(empty($erro)){
			
			$sql = "INSERt INTO tbl_residuo_solido_historico (
																residuo_solido,
																data_input,
																historico,
																admin
															 ) VALUES (
																$residuo_solido,
																current_timestamp,
																'$motivo',
																$login_admin
															 ) RETURNING residuo_solido_historico";
			$res = pg_query($con,$sql);
			$erro = pg_errormessage($con);
			
			if(empty($erro)){
				$residuo_solido_historico = pg_result($res,0,residuo_solido_historico);

				$sql = "SELECT  tbl_residuo_solido.protocolo,
								tbl_residuo_solido.posto,
								tbl_residuo_solido_historico.historico,
								tbl_posto_fabrica.contato_email AS posto_email,
								tbl_admin.email AS admin_email
							FROM tbl_residuo_solido 
							JOIN tbl_residuo_solido_historico ON tbl_residuo_solido_historico.residuo_solido = tbl_residuo_solido.residuo_solido
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_residuo_solido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							JOIN tbl_admin ON tbl_admin.admin = tbl_residuo_solido_historico.admin
							WHERE tbl_residuo_solido.residuo_solido = $residuo_solido
							AND tbl_residuo_solido.fabrica = $login_fabrica";
				$res = pg_query($con,$sql);

				if(pg_numrows($res) > 0){

					$email_cadastros	 = pg_fetch_result($res,0,posto_email);
					$admin_email		 = pg_fetch_result($res,0,admin_email);
					$protocolo			 = pg_fetch_result($res,0,protocolo);
					$posto				 = pg_fetch_result($res,0,posto);
					$historico	 = pg_fetch_result($res,0,historico);

					$sql = "INSERT INTO tbl_comunicado(mensagem,tipo,fabrica,posto,ativo,obrigatorio_site) VALUES('Relatório $protocolo foi recusado pelo seguinte motivo : $historico','Comunicado',$login_fabrica,$posto,true,true)";
					$res = pg_query($con,$sql);
					$erro = pg_errormessage($con);

					$remetente    = $admin_email;
					$destinatario = $email_cadastros ;
					$assunto      = "Devolução de Baterias B&D";
					$mensagem     = "Prezado, <br> o relatório $protocolo foi recusado pelo seguinte motivo : <br><br> $motivo.";
					$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
					mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
	}

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
	//VALDAÇÕES - INÍCIO
	$data_inicial 		= $_POST['data_inicial'];
	$data_final 		= $_POST['data_final'];
	$codigo_posto 		= $_POST['codigo_posto'];
	$posto_nome 		= $_POST['posto_nome'];
	$produto 			= $_POST['produto'];
	$referencia_produto = $_POST['produto_referencia'];
	$descricao_produto 	= $_POST['produto_descricao'];
	$peca 				= $_POST['peca'];
	$referencia_peca	= $_POST['peca_referencia'];
	$descricao_peca 	= $_POST['peca_descricao'];
	$numero_devolucao 	= strtoupper($_POST['num_devolucao']);
	$nota_fiscal 		= $_POST['nf_devolucao'];
	$estado 			= $_POST['estado'];
	$regiao 			= $_POST['regiao'];
	$situacao			= $_POST['situacao'];
	$gera_excel			= $_POST['gera_excel'];
	
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$posto = pg_result($res,0,posto);
	}
	//validação data - início
		if(!empty($data_inicial) and !empty($data_final)){

			list($di, $mi, $yi) = explode("/", $data_inicial);
		    if(!checkdate($mi,$di,$yi)) 
		        $msg_erro = "Data Inválida";

			list($df, $mf, $yf) = explode("/", $data_final);
		    if(!checkdate($mf,$df,$yf)) 
		        $msg_erro = "Data Inválida";

			if(strlen($msg_erro)==0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";
			}
			if(strlen($msg_erro)==0){
				if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
				or strtotime($aux_data_final) > strtotime('today')){
				    $msg_erro = "Data Inválida.";
				} else {
					$cond .= " AND tbl_residuo_solido.digitacao BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
				}
			}
		}
	//validação data - fim


	//validação Posto - início
		if(!empty($posto)){
			$cond .= " AND tbl_residuo_solido.posto = $posto ";
		}
	//validação Posto - fim


	//validação Produto - início
		if(!empty($produto)){
			$cond .= " AND tbl_residuo_solido_item.produto = $produto ";
		}
	//validação Produto - fim
	
	//validação Peça - início
		if(!empty($peca)){
			$cond .= " AND tbl_residuo_solido_item.peca = $peca ";
		}
	//validação Peça - fim

	//validação Número e Nota de Devolução - início
		if(!empty($numero_devolucao)){
			$cond .= " AND tbl_residuo_solido.protocolo = '$numero_devolucao' ";
		}

		if(!empty($nota_fiscal)){
			$cond .= " AND tbl_residuo_solido.nf_devolucao = '$nota_fiscal' ";
		}
	//validação Número e Nota de Devolução - fim


	//validação estado e região - início
		if(!empty($estado)){
			$cond .= " AND tbl_posto.estado = '$estado' ";
		}
	
		if(!empty($regiao) and empty($estado)){

			switch($regiao){
				case 1 : $cond .= " AND tbl_posto.estado in('SC','RS','PR') "; break;
				case 2 : $cond .= " AND tbl_posto.estado in('SP','RJ','ES','MG') "; break;
				case 3 : $cond .= " AND tbl_posto.estado in('GO','MS','MT','DF') "; break;
				case 4 : $cond .= " AND tbl_posto.estado in('SE','AL','RN','MA','PE','PB','CE','PI','BA') "; break;
				case 5 : $cond .= " AND tbl_posto.estado in('TO','PA','AP','RR','AM','AC','RO') "; break;
			}
		}
	//validação estado e região - fim

	//validação Status - início
		if(!empty($estado)){
			$cond .= " AND tbl_posto.estado = '$estado' ";
		}
	
		if(!empty($situacao)){

			switch($situacao){
				case 1 : $cond .= " AND tbl_residuo_solido.data_aprova IS NOT NULL AND tbl_residuo_solido.data_exclusao IS NULL"; break;
				case 2 : $cond .= " AND tbl_residuo_solido.data_aprova IS NULL AND tbl_residuo_solido.data_exclusao IS NULL AND tbl_residuo_solido.recusado IS NOT TRUE"; break;
				case 3 : $cond .= "AND tbl_residuo_solido.data_aprova IS NOT NULL AND tbl_residuo_solido.extrato_lancamento IS NOT NULL AND tbl_residuo_solido.data_exclusao IS NULL AND tbl_extrato.aprovado IS NOT NULL "; 
						 $join = "JOIN tbl_extrato_lancamento ON tbl_residuo_solido.extrato_lancamento = tbl_extrato_lancamento.extrato_lancamento
						 JOIN tbl_extrato ON tbl_extrato_lancamento.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
						";
						$campo = " ,tbl_extrato.protocolo AS protocolo_extrato ";
						$group_by = " ,tbl_extrato.protocolo ";
						break;
				case 4 : $cond .= " AND tbl_residuo_solido.data_exclusao IS NOT NULL "; break;
				case 5 : $cond .= " AND tbl_residuo_solido.recusado IS TRUE AND tbl_residuo_solido.data_exclusao IS NULL "; break;
				case 6 : $join = "JOIN tbl_extrato_lancamento ON tbl_residuo_solido.extrato_lancamento = tbl_extrato_lancamento.extrato_lancamento
						JOIN tbl_extrato ON tbl_extrato_lancamento.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
						JOIN tbl_extrato_financeiro ON tbl_extrato_lancamento.extrato = tbl_extrato_financeiro.extrato 
						";
						$cond .= " AND tbl_residuo_solido.data_aprova IS NOT NULL AND tbl_residuo_solido.extrato_lancamento IS NOT NULL AND tbl_residuo_solido.data_exclusao IS NULL AND tbl_residuo_solido.recusado IS NOT TRUE "; 
						$campo = " ,tbl_extrato.protocolo AS protocolo_extrato ";
						$group_by = " ,tbl_extrato.protocolo ";
				break;
			}
		}
	//validação Status - fim


	//VALDAÇÕES - FIM
}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE DEVOLUÇÃO DE BATERIAS";
include "cabecalho.php";
?>

<style type="text/css">
INPUT BUTTON{
	height:15px;
	font:bold 10px Arial;
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

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script src="js/jquery.alphanumeric.js" type="text/javascript"></script>
<script type="text/javascript">
	function init(){
		$("#data_inicial").datePicker({startDate : "01/01/2000"});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").datePicker({startDate : "01/01/2000"});
		$("#data_final").maskedinput("99/99/9999");

		$("#data_de").datePicker({startDate : "01/01/2000"});
		$("#data_de").maskedinput("99/99/9999");
		$("#data_ate").datePicker({startDate : "01/01/2000"});
		$("#data_ate").maskedinput("99/99/9999");

		$("#nf_devolucao").numeric();
	}
	$().ready(function(){
		Shadowbox.init();
		init();
	});

	//PESQUISA POSTO - 

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
		
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,nome,credenciamento){
		gravaDados('codigo_posto',codigo_posto);
		gravaDados('posto_nome',nome);
		gravaDados('posto',posto);
	}
	
	//PESQUISA PRODUTO - 
	function pesquisaProduto(produto,tipo,posicao){

		if (jQuery.trim(produto.value).length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_2_nv.php?"+tipo+"="+produto.value+"&posicao="+posicao,
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

	function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){
		gravaDados('produto_referencia',referencia);
		gravaDados('produto_descricao',descricao);
		gravaDados('produto',produto);	
	}

	//PESQUISA PECA
	function pesquisaPeca(peca,tipo){
		if (jQuery.trim(peca.value).length > 2){
			Shadowbox.open({
				content:	"../peca_pesquisa_nv.php?"+tipo+"="+peca.value+"&bateria=1",
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
		gravaDados('peca_referencia',referencia);
		gravaDados('peca_descricao',descricao);
		gravaDados('peca',peca);
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function mostraDiv(num,id){
		
		var div = document.getElementById("div_motivo_"+id).style;		
		if(div.display == "none"){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?div_motivo="+num+"&linha="+id,
				cache: false,
				success: function(data){
					if(num == 3){
						$('#linha_itens_'+id).html(data);	
						$('#linha_itens_'+id).toggle();
					} else {
						$('#div_motivo_'+id).html(data);	
						$('#div_motivo_'+id).toggle();	
						init();
					}
				}
			});
		} else {
			div.display = "none";
		}
	}


	function excluiDevolucao(id){
		var motivo = document.getElementById("motivo_"+id).value
		if (jQuery.trim(motivo).length > 0){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?exclui=1&linha="+id+"&motivo="+motivo,
				cache: false,
				success: function(data){	
					if(data =="ok"){
						$("#div_motivo_"+id).remove();
						$("#linha_"+id).remove();
						$("#msg_exclui"+id).toggle();
					}
					else{
						alert(data);
					}
				}
			});
		} else {
			alert('Informe o motivo');
		}
	}

	function reprovaDevolucao(id){
		var motivo = document.getElementById("motivo_"+id).value
		if (jQuery.trim(motivo).length > 0){
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>?reprova=1&linha="+id+"&motivo="+motivo,
				cache: false,
				success: function(data){	
					if(data =="ok"){
						$("#div_motivo_"+id).remove();
						$("#linha_"+id).remove();
						$("#msg_exclui"+id).toggle();
					}
					else{
						alert(data);
					}
				}
			});
		} else {
			alert('Informe o motivo');
		}
	}

	function mostraHistorico(id){
		var linha = document.getElementById('historico_'+id).style;

		if(linha.display == "none"){
			linha.display = "table-row";
		} else {
			linha.display = "none";
		}
	}


</script>

<center> <div style="width:700px; display:none;" id="msg_retorno" class='sucesso'>Aprovado com Sucesso</div> </center>
<center> <div style="width:700px; display:none;" id="msg_exclui" class='sucesso'>Excluído com Sucesso</div> </center>

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
			<td width='100'>&nbsp;</td>
			<td align="left">
				Data Inicial
				<br>
				<input class="frm" type="text" name="data_inicial" id="data_inicial" size="10" value="<? echo $data_inicial ?>" >
			</td>
			<td align="left">
				Data Final
				<br>
				<input class="frm" type="text" name="data_final" id="data_final" size="10" value="<? echo $data_final ?>" >
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<td align="left">
				Código do Posto
				<br>
				<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="15" value="<? echo $codigo_posto ?>" >
				&nbsp;
				<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: pesquisaPosto(document.frm_consulta.codigo_posto,'codigo')">
				</a>
			</td>
			<td align="left">
				Nome do Posto
				<br>
				<input class="frm" type="text" name="posto_nome" id="posto_nome" size="35" value="<? echo $posto_nome ?>" >
				&nbsp;
				<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: pesquisaPosto(document.frm_consulta.posto_nome,'nome')" style="cursor:pointer;">
				</a>
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>
		
		<tr>
			<td width='100'>&nbsp;</td>
			<td align="left">
				Ref. Produto<br>
				<input class="frm" type="text" id="produto_referencia" name="produto_referencia2" size="15" maxlength="50" value="<?php echo $referencia_produto; ?>"  >&nbsp;
				<img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: pesquisaProduto(document.frm_consulta.produto_referencia,'referencia')" style="cursor:pointer;">
				<input type="hidden" name="produto">
			</td>
			<td align="left">
				Descrição Produto<br>
				<input class="frm" type="text" name="produto_descricao" size="35" maxlength="50" value="<?php echo$produto_descricao; ?>" id="produto_descricao2">&nbsp;
				<img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: pesquisaProduto (document.frm_consulta.produto_descricao,'descricao')" style="cursor:pointer;">
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>

			<tr><td colspan='5'>&nbsp;</td></tr>
		
		<tr>
			<td width='100'>&nbsp;</td>
			<td align="left">
				Ref. Bateria<br>
				<input class="frm" type="text" id="peca_referencia" name="peca_referencia" size="15" maxlength="50" value="<?php echo $referencia_peca; ?>"  >&nbsp;
				<img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: pesquisaPeca(document.frm_consulta.peca_referencia,'referencia')" style="cursor:pointer;">
				<input type="hidden" name="peca">
			</td>
			<td align="left">
				Desc. Bateria<br>
				<input class="frm" type="text" name="peca_descricao" size="35" maxlength="50" value="<?php echo $peca_descricao; ?>" id="peca_descricao">&nbsp;
				<img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: pesquisaProduto (document.frm_consulta.produto_descricao,'descricao')" style="cursor:pointer;">
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<td align="left">
				Número Devolução<br>
				<input type="text" name="num_devolucao" id="num_devolucao" size="15" class="frm" value="<?php echo $numero_devolucao;?>">
			</td>

			<td align="left">
				NF Devolução<br>
				<input type="text" name="nf_devolucao" id="nf_devolucao" size="15" value="<?php echo $nf_devolucao;?>" class="frm">
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>
		
		<tr><td colspan='5'>&nbsp;</td></tr>

		<tr>
			<td width='100'>&nbsp;</td>
			<td align="left">
				Estado<br>
				<select name="estado" class="frm" id="estado">
					<?php
						foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
						}
					?>
				</select>
			</td>
			<td align="left">
				Região<br>
				<select name='regiao' size='1' class='frm' style='width:320px'>
				<option value=''></option>
				<option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>Sul (SC,RS e PR)</option>
				<option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>Sudeste (SP, RJ, ES e MG)</option>
				<option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>Centro Oeste (GO, MS, MT e DF)</option>
				<option value='4' <? if ($regiao == 4) echo " SELECTED "; ?>>Nordeste (SE, AL, RN, MA, PE, PB, CE, PI e BA)</option>
				<option value='5' <? if ($regiao == 5) echo " SELECTED "; ?>>Norte (TO, PA, AP, RR, AM, AC E RO)</option>
			</select>				
			</td>
			<td  colspan='2'>&nbsp;</td>
		</tr>

		<tr><td colspan='5'>&nbsp;</td></tr>
		
		<tr>
			<td width='100'>&nbsp;</td>
			<td colspan='4' >
				<fieldset style='width:500px; text-align:left;'>
					<legend>Situação</legend>
					<input type='radio' name='situacao' value='2' CHECKED>Em Aprovação &nbsp;&nbsp;
					<input type='radio' name='situacao' value='1' <?php echo ($situacao == 1) ? "CHECKED" : "";?>>Aprovado &nbsp;&nbsp;
					<input type='radio' name='situacao' value='3'  <?php echo ($situacao == 3) ? "CHECKED" : "";?>>Extratos aprovados &nbsp;&nbsp; <br>
					<input type='radio' name='situacao' value='6'  <?php echo ($situacao == 6) ? "CHECKED" : "";?>>Enviado p/ Financeiro &nbsp;&nbsp;
					<input type='radio' name='situacao' value='4'  <?php echo ($situacao == 4) ? "CHECKED" : "";?>>Excluídos&nbsp;&nbsp;
					<input type='radio' name='situacao' value='5'  <?php echo ($situacao == 5) ? "CHECKED" : "";?>>Recusados
				</fieldset>
			</td>
		</tr>

		<tr><td colspan='5'>&nbsp;</td></tr>
		
		<tr>
			<td width='100'>&nbsp;</td>
			<td colspan='4' align="left" >
				<input type="checkbox" name="gera_excel" value="gerar">Gerar Excel
			</td>
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

		if(empty($gera_excel)){
		
			$sql = "SELECT tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome as posto_nome,
							tbl_residuo_solido.residuo_solido,
							tbl_residuo_solido.protocolo,
							tbl_residuo_solido.qtde,
							tbl_residuo_solido.nf_devolucao,
							TO_CHAR(tbl_residuo_solido.data_nf,'DD/MM/YYYY') AS data_nf,
							tbl_residuo_solido.data_aprova,
							tbl_residuo_solido.data_exclusao,
							tbl_residuo_solido.motivo_exclusao,
							tbl_residuo_solido.numero_devolucao,
							TO_CHAR(tbl_residuo_solido.data_devolucao_inicial,'DD/MM/YYYY') AS data_devolucao_inicial,
							TO_CHAR(tbl_residuo_solido.data_devolucao_final,'DD/MM/YYYY') AS data_devolucao_final,
							tbl_residuo_solido.extrato_lancamento,
							tbl_residuo_solido.codigo_transporte
							$campo
						FROM tbl_residuo_solido
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_residuo_solido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
						$join
						WHERE tbl_residuo_solido.fabrica = $login_fabrica
						$cond
						GROUP BY 
						tbl_residuo_solido.residuo_solido,
						tbl_residuo_solido.protocolo,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_residuo_solido.data_devolucao_inicial,
						tbl_residuo_solido.data_devolucao_final,
						tbl_residuo_solido.qtde,
						tbl_residuo_solido.nf_devolucao,
						tbl_residuo_solido.data_nf,
						tbl_residuo_solido.data_aprova,
						tbl_residuo_solido.data_exclusao,
						tbl_residuo_solido.motivo_exclusao,
						tbl_residuo_solido.numero_devolucao,
						tbl_residuo_solido.extrato_lancamento,
						tbl_residuo_solido.codigo_transporte
						$group_by
						";
			$res = pg_query($con,$sql);
	#echo nl2br($sql);
			if(pg_numrows($res) > 0){
	?>
				<table  align="center" class="tabela">
					<caption class="titulo_tabela">Resultado</caption>
							<tr class="titulo_coluna">
								<td>Cód.Posto</td>
								<td>Posto</td>
								<td>N° do Relatório</td>
								<td>Qtde</td>
								<td>Consultar</td>
								<td>NF Devolução</td>
								<td>Data NF</td>
								<?php if($situacao == 1 OR $situacao == 3 OR $situacao == 6){ ?>
										<td>Numero Objeto</td>
										<td>Comprovante Devolução</td>
										<td colspan='2'>
											<table width='100%'>
												<tr class='titulo_coluna'>
													<td colspan='2' align='center'>Data Devolução</td>
												</tr>
												<tr class='titulo_coluna'>
													<td>De</td>
													<td>Até</td>
												</tr>
											</table>
										</td>
								<?php } ?>
								<?php
								if($situacao == 4){
								?>
								<td colspan="2">Motivo</td>
								<?php
								}if($situacao == 2){
								?>
									<td>Aprovar</td>
									<td>Reprovar</td>
									<td>Excluir</td>
								<?php
								}
								?>
								<?php if($situacao == 3 or $situacao == 6){ ?>
										<td>Extrato</td>
								<?php } ?>
								<td>Histórico</td>
							</tr>
	<?php
				
				$dir_nf = "../nf_bateria/";
				$dir_pac = "../nf_bateria/correio/";
				
				for($i = 0; $i < pg_numrows($res); $i++){
					$residuo_solido				= pg_result($res,$i,residuo_solido);
					$codigo_posto				= pg_result($res,$i,codigo_posto);
					$posto_nome					= pg_result($res,$i,posto_nome);
					$protocolo					= pg_result($res,$i,protocolo);
					$qtde						= pg_result($res,$i,qtde);
					$nf_devolucao				= pg_result($res,$i,nf_devolucao);
					$data_nf					= pg_result($res,$i,data_nf);
					$numero_devolucao			= pg_result($res,$i,numero_devolucao);
					$data_devolucao_inicial	    = pg_result($res,$i,data_devolucao_inicial);
					$data_devolucao_final	    = pg_result($res,$i,data_devolucao_final);
					$data_exclusao			    = pg_result($res,$i,data_exclusao);
					$motivo					    = pg_result($res,$i,motivo_exclusao);
					$extrato_lancamento			= pg_result($res,$i,extrato_lancamento);
					$codigo_transporte			= pg_result($res,$i,codigo_transporte);

					if($situacao == 3 OR $situacao == 6){
						$extrato_lancamento			= pg_result($res,$i,protocolo_extrato);
					}

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					$arquivo_nf = exec("cd $dir_nf; ls $residuo_solido.*");
					$arquivo_pac = exec("cd $dir_pac; ls $residuo_solido.*");

					$sqlH = "SELECT TO_CHAR(data_input::date,'DD/MM/YYYY') AS data_input, historico,tbl_admin.login 
								FROM tbl_residuo_solido_historico 
								JOIN tbl_admin ON tbl_residuo_solido_historico.admin = tbl_admin.admin
								WHERE residuo_solido = $residuo_solido";
					$resH = pg_query($con,$sqlH);
					if( pg_numrows($resH) > 0){
						$total_historico = pg_numrows($resH);
					}

			?>
					<tr bgcolor="<?php echo $cor; ?>" id="linha_<?php echo $residuo_solido; ?>">
						<td><?php echo $codigo_posto; ?></td>
						<td><?php echo $posto_nome; ?></td>
						<td><?php echo $protocolo; ?></td>
						<td><?php echo $qtde; ?></td>
						<td> <input type="button" value="Consultar" onclick="mostraDiv(3,<?php echo $residuo_solido; ?>)"> </td>
						<td><a href="<?php echo $dir_nf.$arquivo_nf; ?>" target="_blank" title="Nota Fiscal: <?php echo $nf_devolucao; ?>"><?php echo $nf_devolucao; ?></a></td>
						<td><?php echo $data_nf; ?></td>
						<?php 
							if($situacao == 1 OR $situacao == 3 OR $situacao == 6){
						?>
								<td><?php echo strtoupper($codigo_transporte); ?></td>
								<td><a href="<?php echo $dir_pac.$arquivo_pac; ?>" title="Numero Devolução: <?php echo $numero_devolucao; ?>" target="_blank"><?php echo $numero_devolucao; ?></a></td>
								<td><?php echo $data_devolucao_inicial; ?></td>
								<td><?php echo $data_devolucao_final; ?></td>
						<?php
								if($situacao == 3 OR $situacao == 6){
						?>
									<td><?php echo $extrato_lancamento; ?></td>
						<?php
								}
							} else {
						?>
								
								<?php
								if(empty($data_exclusao) and $situacao == 2){
								?>
								<td> <input type="button" value="Aprovar" id="aprovar_<?php echo $residuo_solido; ?>" onclick="javascript: mostraDiv(2,<?php echo $residuo_solido; ?>)"> </td>
								<td> <input type="button" value="Recusar" id="reprovar_<?php echo $residuo_solido; ?>" onclick="javascript: mostraDiv(4,<?php echo $residuo_solido; ?>)"> </td>
								<td> <input type="button" value="Excluir" id="excluir_<?php echo $residuo_solido; ?>" onclick="javascript: mostraDiv(1,<?php echo $residuo_solido; ?>)"> </td>
								<?php
								}elseif(!empty($data_exclusao) and $situacao == 2) {
									echo "<td colspan='2'>$motivo</td>";
								}
								
								?>
						<?php
							}
							if($total_historico > 0){
								echo "<td><input type='button' value='Histórico' onclick='javascript:mostraHistorico($i);'></td>";
							}
						?>
					</tr>
					<tr id="div_motivo_<?php echo $residuo_solido; ?>" style="width:700px; display:none; "> </tr>
					<tr id="linha_itens_<?php echo $residuo_solido; ?>" style="width:700px; display:none; "> </tr>

						<tr id='historico_<?php echo $i; ?>' style="display:none;">
							<td colspan='100%'>
								<table width="100%">
									<caption class="titulo_tabela">Histórico de recusas</caption>
									<tr class="titulo_coluna">
										<td>Data</td>
										<td>Motivo</td>
										<td>Admin</td>
									</tr>
	<?php
						for($j = 0; $j < $total_historico; $j++){
							$data_input = pg_result($resH,$j,data_input);
							$historico  = pg_result($resH,$j,historico);
							$admin      = pg_result($resH,$j,login);
	?>
									<tr bgcolor="<?php echo $cor; ?>">
										<td><?php echo $data_input; ?></td>
										<td><?php echo $historico; ?></td>
										<td><?php echo $admin; ?></td>
									</tr>
	<?php
						}
					
	?>
								</table>
							</td>
						</tr>
					
	<?php
				}
				echo "</table>";
			} else {
				echo "<center>Nenhum resultado encontrado</center>";
			}
		} else {
			$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_residuo_solido.nf_devolucao,
							TO_CHAR(tbl_residuo_solido.data_nf,'DD/MM/YYYY') AS data_nf,
							tbl_residuo_solido.numero_devolucao,
							TO_CHAR(tbl_residuo_solido.data_devolucao_inicial,'DD/MM/YYYY') AS data_devolucao_inicial,
							TO_CHAR(tbl_residuo_solido.data_devolucao_final,'DD/MM/YYYY') AS data_devolucao_final,
							tbl_produto.referencia AS cod_produto,
							tbl_produto.descricao AS produto,
							tbl_peca.referencia AS cod_peca,
							tbl_peca.descricao AS peca,
							CASE WHEN tbl_residuo_solido_item.troca_garantia IS TRUE THEN 'Sim'
								 ELSE 'Não'
							END AS garantia,
							tbl_residuo_solido_item.defeito_constatado,
							tbl_residuo_solido_item.cliente_nome,
							tbl_residuo_solido_item.cliente_fone,
							tbl_residuo_solido.extrato_lancamento
							$campo
						FROM tbl_residuo_solido
						JOIN tbl_residuo_solido_item ON tbl_residuo_solido_item.residuo_solido = tbl_residuo_solido.residuo_solido AND tbl_residuo_solido.fabrica = $login_fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_residuo_solido.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_produto ON tbl_produto.produto = tbl_residuo_solido_item.produto AND tbl_produto.fabrica_i = $login_fabrica
						JOIN tbl_peca ON tbl_peca.peca = tbl_residuo_solido_item.peca AND tbl_peca.fabrica = $login_fabrica
						$join
						$cond";

					$res = pg_query($con,$sql);

					if(pg_numrows($res) > 0){
						
						$data = date ("Y-m-d");
						$arquivo_nome     = "relatorio-devolucao-bateria-$login_fabrica-$data.xls";
						$path             = "../xls/";
						$arquivo_completo     = $path.$arquivo_nome;
						echo `rm $arquivo_completo `;

						$fp = fopen($arquivo_completo,"w");
						fputs ($fp,"<html>");
						fputs ($fp,"<head>");
						fputs ($fp,"<title>FIELD CALL-RATE - $data");
						fputs ($fp,"</title>");
						fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
						fputs ($fp,"</head>");
						fputs ($fp,"<body>");
						

						for($i = 0; $i < pg_numrows($res); $i++){

							
							$codigo_posto		= pg_result($res,$i,codigo_posto);
							$nome			= pg_result($res,$i,nome);
							$nf_devolucao		= pg_result($res,$i,nf_devolucao);
							$data_nf		= pg_result($res,$i,data_nf);
							$numero_devolucao	= pg_result($res,$i,numero_devolucao);
							$data_devolucao_inicial = pg_result($res,$i,data_devolucao_inicial);
							$data_devolucao_final	= pg_result($res,$i,data_devolucao_final);
							$cod_produto		= pg_result($res,$i,cod_produto);
							$produto		= pg_result($res,$i,produto);
							$cod_peca		= pg_result($res,$i,cod_peca);
							$peca			= pg_result($res,$i,peca);
							$garantia		= pg_result($res,$i,garantia);
							$defeito_constatado	= pg_result($res,$i,defeito_constatado);
							$cliente_nome		= pg_result($res,$i,cliente_nome);
							$cliente_fone		= pg_result($res,$i,cliente_fone);
							//$extrato_lancamento			= pg_result($res,$i,extrato_lancamento);

							if($situacao == 3 OR $situacao == 6){
								$extrato_lancamento = pg_result($res,$i,protocolo_extrato);
							}

							if($i == 0){
								fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2' class='tabela'>");
								fputs($fp,"<tr class='titulo_coluna' bgcolor='#596d9b' style='color:#FFFFFF; text-align:center;'>");
								fputs($fp,"<td>Código</td>");
								fputs($fp,"<td>Posto</td>");
								fputs($fp,"<td>Nota Devolução</td>");
								fputs($fp,"<td>Data NF</td>");
								fputs($fp,"<td>Nº do comprovante</td>");
								fputs($fp,"<td>Data ínicio dev</td>");
								fputs($fp,"<td>Data final dev</td>");
								fputs($fp,"<td>Produto</td>");
								fputs($fp,"<td>Descrição</td>");
								fputs($fp,"<td>Peça</td>");
								fputs($fp,"<td>Descrição</td>");
								fputs($fp,"<td>Garantia</td>");
								fputs($fp,"<td>Defeito</td>");
								fputs($fp,"<td>Cliente</td>");
								fputs($fp,"<td>Telefone</td>");
								if(!empty($extrato_lancamento)){
									fputs($fp,"<td>Extrato</td>");
								}
								fputs($fp,"</tr>");
							}
							
							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

							fputs($fp,"<tr bgcolor='$cor'>");
							fputs($fp,"<td>$codigo_posto</td>");
							fputs($fp,"<td>$nome</td>");
							fputs($fp,"<td>$nf_devolucao</td>");
							fputs($fp,"<td>$data_nf</td>");
							fputs($fp,"<td>$numero_devolucao</td>");
							fputs($fp,"<td>$data_devolucao_inicial</td>");
							fputs($fp,"<td>$data_devolucao_final</td>");
							fputs($fp,"<td>$cod_produto</td>");
							fputs($fp,"<td>$produto</td>");
							fputs($fp,"<td>$cod_peca</td>");
							fputs($fp,"<td>$peca</td>");
							fputs($fp,"<td>$garantia</td>");
							fputs($fp,"<td>$defeito_constatado</td>");
							fputs($fp,"<td>$cliente_nome</td>");
							fputs($fp,"<td>$cliente_fone</td>");
							if(!empty($extrato_lancamento)){
								fputs($fp,"<td>$extrato_lancamento</td>");
							}
							fputs($fp,"</tr>");
						}
						fputs ($fp,"</table>");
						fputs ($fp,"</body>");
						fputs ($fp,"</html>");
						fclose ($fp);

						echo "<center><input type='button' value='Download Excel' onclick='window.location.href=\"$arquivo_completo\";'></center>";
					} else {
						echo "<center>Nenhum resultado encontrado</center>";
					}
		}
	} 
?>

<?php include "rodape.php"; ?>
