<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/* Área do Admin    */
//Opções: 'auditoria', 'cadastros', 'call_center', 'financeiro', 'gerencia'  'info_tecnica'
$admin_privilegios = "financeiro";
include 'autentica_admin.php';

/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin

if ($S3_sdk_OK) {

	include_once S3CLASS;
	$s3 = new AmazonTC('co', (int) $login_fabrica);
	$S3_online = is_object($s3);
}

$pesquisa = 17;

$arrThumb = array(
				"foto_fachada" => "",
				"foto_balcao"  => "",
				"foto_oficina" => "",
				"foto_estoque" => ""
			);
$arrFiles = array(
				"foto_fachada" => array("nome"=>"", "link"=>""),
				"foto_balcao"  => array("nome"=>"", "link"=>""),
				"foto_oficina" => array("nome"=>"", "link"=>""),
				"foto_estoque" => array("nome"=>"", "link"=>"")
			);

function pegaThumbsS3($posto){
	global $s3;
	$s3->getObjectList("thumb_pesquisa_black_{$posto}");
	$arrLinks = $s3->getLinkList($s3->files);
	foreach ($arrLinks as $link) {

		if(strpos($link, "foto_fachada") !== false){
			$arrThumb["foto_fachada"] = $link;
		}else if (strpos($link, "foto_balcao") !== false){
			$arrThumb["foto_balcao"] = $link;
		}else if(strpos($link, "foto_oficina") !== false){
			$arrThumb["foto_oficina"] = $link;
		}else if(strpos($link, "foto_estoque") !== false){
			$arrThumb["foto_estoque"] = $link;
		}
	}
	return $arrThumb;
}

function pegaFotosS3($posto){
	global $s3;

	// pega imagens tamanho normal
	$s3->getObjectList("pesquisa_black_{$posto}");
	foreach ($s3->files as $file) {

		if(strpos($file, "foto_fachada") !== false){
			$arrFiles["foto_fachada"]["nome"] = basename($file);
		}else if (strpos($file, "foto_balcao") !== false){
			$arrFiles["foto_balcao"]["nome"] = basename($file);
		}else if(strpos($file, "foto_oficina") !== false){
			$arrFiles["foto_oficina"]["nome"] = basename($file);
		}else if(strpos($file, "foto_estoque") !== false){
			$arrFiles["foto_estoque"]["nome"] = basename($file);
		}
	}

	$arrLinks = $s3->getLinkList($s3->files);
	foreach ($arrLinks as $link) {

		if(strpos($link, "foto_fachada") !== false){
			$arrFiles["foto_fachada"]["link"] = $link;
		}else if (strpos($link, "foto_balcao") !== false){
			$arrFiles["foto_balcao"]["link"] = $link;
		}else if(strpos($link, "foto_oficina") !== false){
			$arrFiles["foto_oficina"]["link"] = $link;
		}else if(strpos($link, "foto_estoque") !== false){
			$arrFiles["foto_estoque"]["link"] = $link;
		}
	}
	return $arrFiles;

}
if($_POST["excluiFoto"]=="1"){
	if(strlen($_POST["nomeArquivo"]) > 0){
		if(!$s3->deleteObject($_POST["nomeArquivo"])){
			$respJson = json_encode(array("success" => "true", "msg" => "Arquivo Apagado com Sucesso"));	
		}else{
			$respJson = json_encode(array("success" => "false", "msg" => "Erro ao deletar o arquivo"));	
		}
	}
	echo $respJson;
	die;
}
if($_POST["gravaBrindes"] == "1"){
	$posto 			= $_POST["posto"];

	$temBrinde 		= $_POST["tem_brinde"];
	$qtdeBrinde 	= trim($_POST["qtde_brinde"]);
	$tpBrinde 		= trim($_POST["tp_brinde"]);

	if( (strlen($temBrinde) > 0)){
		//verificar se ja existe resposta
		$sqlVerifica = "SELECT resposta FROM tbl_resposta WHERE pergunta = 233 and pesquisa = 17 and posto = $posto";
		$resVerifica = pg_query($con,$sqlVerifica);

		if(pg_num_rows($resVerifica)>0){
			$resposta = pg_fetch_result($resVerifica, 0, 'resposta');
			$sql = "UPDATE tbl_resposta 
					SET txt_resposta = '$temBrinde'
					WHERE resposta = $resposta";
		}else{
			$sql = "INSERT INTO tbl_resposta (pergunta, txt_resposta, pesquisa, admin, posto)
								  values (233, '$temBrinde', 17, $login_admin, $posto)";
		}
		$res = pg_query($con,$sql);

	}


	if((strlen($tpBrinde) > 0) && $res){
		//verificar se ja existe resposta
		$sqlVerifica = "SELECT resposta FROM tbl_resposta WHERE pergunta = 234 and pesquisa = 17 and posto = $posto";
		$resVerifica = pg_query($con,$sqlVerifica);

		if(pg_num_rows($resVerifica)>0){
			$resposta = pg_fetch_result($resVerifica, 0, 'resposta');
			$sql = "UPDATE tbl_resposta 
					SET txt_resposta = '$qtdeBrinde'
					WHERE resposta = $resposta";
		}else{
			$sql = "INSERT INTO tbl_resposta (pergunta, txt_resposta, pesquisa, admin, posto) 
								  values (234, '$qtdeBrinde', 17, $login_admin, $posto)";
		}
		$res = pg_query($con,$sql);
		
	}

	if((strlen($qtdeBrinde) > 0) && $res ){
		//verificar se ja existe resposta
		$sqlVerifica = "SELECT resposta FROM tbl_resposta WHERE pergunta = 235 and pesquisa = 17 and posto = $posto";
		$resVerifica = pg_query($con,$sqlVerifica);

		if(pg_num_rows($resVerifica)>0){
			$resposta = pg_fetch_result($resVerifica, 0, 'resposta');
			$sql = "UPDATE tbl_resposta 
					SET txt_resposta = '$tpBrinde'
					WHERE resposta = $resposta";
		}else{
			$sql = "INSERT INTO tbl_resposta (pergunta, txt_resposta, pesquisa, admin, posto)
								  values (235, '$tpBrinde', 17, $login_admin, $posto)";
		}
		$res = pg_query($con,$sql);
	}
	
	if(!$res){
		$respJson = json_encode(array("success" => "false", "msg" => "Erro ao salvar os dados:".pg_last_error($con) ));
	}else{
		$respJson = json_encode(array("success" => "true", "msg" => "Gravado com Sucesso" ));
	}
	echo $respJson;
	die;
}
	$title = "RELATÓRIO DE PESQUISA DE POSTO";
	//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
	$layout_menu = 'auditoria';
	include "cabecalho.php";
	extract(array_filter($_POST, 'anti_injection')); // cria as variáveis com os campos do formulário

$regioes = array('NORTE'  => 'Região Norte',
				 'NORDESTE' => 'Região Nordeste',
				 'CENTRO-OESTE' => 'Região Centro-Oeste',
				 'SUDESTE' => 'Região Sudeste',
				 'SUL'  => 'Região Sul');

if($_GET['buscaRegiao']){
	$reg = $_GET['regiao'];
	if($reg == "CENTRO-OESTE"){
		$estados = array('GO'=>'Goiás',
						'MS'=>'Mato Grosso do Sul',
						'MT'=>'Mato Grosso',
						'DF'=>'Distrito Federal');
	} else if($reg == "NORDESTE"){

		$estados = array('SE'=>'Sergipe',
						'AL'=>'Alagoas',
						'RN'=>'Rio Grande do Norte',
						'MA'=>'Maranhão',
						'PE'=>'Pernambuco',
						'PB'=>'Paraíba',
						'CE'=>'Ceará',
						'PI'=>'Piauí',
						'BA'=>'Bahia');

	} else if($reg == "NORTE"){
		$estados = array('TO'=>'Tocantins',
						'PA'=>'Pará',
						'AP'=>'Amapa',
						'RR'=>'Roraima',
						'AM'=>'Amazonas',
						'AC'=>'Acre',
						'RO'=>'Rondônia');
	} else if($reg == "SUDESTE"){
		$estados = array('ES'=>'Espírito Santos',
						'MG'=>'Minas Gerais',
						'RJ'=>'Rio de Janeiro',
						'SP'=>'São Paulo');
	} else if($reg == "SUL"){
		$estados = array('PR'=>'Paraná',
						'RS'=>'Rio Grande do Sul',
						'SC'=>'Santa Catarina');
	}
	
		$retorno = "<option value=''>Selecione um Estado</option>";
		foreach ($estados as $sigla_estado=>$nome_estado) {
			$nome_estado = utf8_encode($nome_estado);
			$retorno .= "<option value='$sigla_estado'>$nome_estado</option>";
		}

	echo $retorno;
	exit;
}else if($_POST['geraRelatorio']=='1'){
	
	//validar Data
	if( (!empty($_POST["data_inicial"]) && !empty($_POST["data_final"])) ){
		
		$data_inicial=$_POST["data_inicial"];
		$data_fim=$_POST["data_final"];

		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_fim );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$xdata_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_fim);//tira a barra
			$xdata_fim = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($xdata_fim < $xdata_inicial){
				$msg_erro = "A Data Fim deve ser maior do que a Data Início.";
			}
		}
		if(strlen($msg_erro)==0){
			$sqlDataInput = "AND tbl_resposta.data_input BETWEEN '$xdata_inicial 00:00:00' and '$xdata_fim 23:59:59'";
		}
	}else{
		$msg_erro = "Por favor, preencha as datas";
	}

	if (strlen($_POST["posto_codigo"]) > 0) {
			$posto_codigo = trim($_POST["posto_codigo"]);
			//TODO SELECIONAR ID DO POSTO
			$sqlPosto = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";

	} else {
		$sqlPosto = "";
	}


	if (strlen($_POST["regiao"]) > 0)  {
		$regiao = trim($_POST["regiao"]);

		$sqlRegiao = " AND tbl_estado.regiao = '$regiao' ";
		
	} else {
		$sqlRegiao = "";
	}

	if (strlen($_POST["estado"]) > 0)  {
		$estado = trim($_POST["estado"]);
		$sqlEstado = " and tbl_posto.estado = '$estado' ";
		
	} else {
		$sqlEstado = "";
	}
	// TODO: FAZER FILTRO POR POSTO,ESTADO,REGIAO,DATA
	//sql da consulta
	$sql = "SELECT 	
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.estado,
					tbl_posto.cidade,
					tbl_tipo_posto.descricao as tipo_posto,
					tbl_estado.regiao,
					tbl_posto_fabrica.fabrica,
					tbl_posto_fabrica.codigo_posto,
					tbl_pesquisa.pesquisa,
					tbl_resposta.pergunta,
					tbl_pergunta.descricao,
					tbl_resposta.txt_resposta
			FROM tbl_posto_fabrica 
			JOIN tbl_posto 				ON 	tbl_posto.posto = tbl_posto_fabrica.posto
			JOIN tbl_tipo_posto 		ON 	tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN tbl_estado 			ON 	tbl_estado.estado = tbl_posto.estado
			JOIN tbl_pesquisa 			ON 	tbl_pesquisa.fabrica = $login_fabrica AND
											tbl_pesquisa.ativo
			JOIN tbl_pesquisa_pergunta 	ON 	tbl_pesquisa_pergunta.pesquisa = tbl_pesquisa.pesquisa
			JOIN tbl_pergunta 			ON 	tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta AND
											tbl_pergunta.fabrica = $login_fabrica AND
											tbl_pergunta.ativo
			LEFT JOIN tbl_resposta 		ON 	tbl_resposta.pergunta = tbl_pergunta.pergunta AND
											tbl_resposta.posto = tbl_posto.posto

			WHERE 	tbl_posto_fabrica.fabrica = $login_fabrica
					$sqlDataInput
					$sqlPosto
					$sqlEstado			
					$sqlRegiao
					and tbl_pesquisa.pesquisa = $pesquisa
			ORDER BY tbl_posto_fabrica.codigo_posto, tbl_pergunta.pergunta
		   ";
		   
	$res = pg_query($con, $sql);

	$numRows = pg_num_rows($res);
	if($numRows==0){
		$msg_erro = "Nenhum registro encontrado.";
	}
	//pegar imagens S3
}

include 'javascript_pesquisas_novo.php'; //Admin
?>
<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" />
<link rel="stylesheet" type="text/css" href="bootstrap/css/extra.css" />
<script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script type="text/javascript" src="plugins/jquery.form.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" />
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<script type="text/javascript" charset="utf-8">
$(function(){
	$("#data_inicial").datepick({startDate:"01/01/2000"});
	$("#data_inicial").maskedinput("99/99/9999");

	$("#data_final").datepick({startDate:"01/01/2000"});
	$("#data_final").maskedinput("99/99/9999");
	Shadowbox.init();
	setupZoom();
});

function retorna_posto(codigo_posto, posto,nome, cnpj, cidade, estado, credenciamento, num_posto, cep, endereco, numero, bairro){
	gravaDados('posto_codigo',codigo_posto);
	gravaDados('posto_nome',nome);
}

function excluiFoto(img, idTd){
	$.ajax({
		url:"relatorio_pesquisa_posto_blackedecker.php",
		type:"POST",
		dataType:"JSON",
		data: {
		
		    "excluiFoto" : 1,
		    "nomeArquivo" : img
		},
		beforeSend: function(){
			$("#msg_ajax").show();
		},
		complete:function(data){

			data = data.responseText;
			data = $.parseJSON(data)

			if(data.success == "true"){

				alert(data.msg);
				$("#"+idTd).remove();
			}else{
				alert(data.msg);
				
			}	
			$("#msg_ajax").hide();
		}

	});
}
function montaComboEstado(){

	var regiao = $('#regiao').val();

	$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaRegiao=1&regiao="+regiao,
			cache: false,
			success: function(data) {
				$('#estado').html(data);
			}

		});

}

function gravaBrindes (posto){
	
	var tp_brinde 	= $('#tp_brinde_'+posto).val();
	var qtde_brinde = $('#qtde_brinde_'+posto).val();
	var tem_brinde 	= $('#tem_brinde_'+posto).val();
	
	$.ajax({
		url:"relatorio_pesquisa_posto_blackedecker.php",
		type:"POST",
		dataType:"JSON",
		data: {
			"posto"			: posto,
			"tp_brinde"		: tp_brinde,
			"qtde_brinde"	: qtde_brinde,
		    "tem_brinde"	: tem_brinde,
		    "gravaBrindes"	: 1
		},
		beforeSend: function(){
			$("#msg_ajax").show();
		},
		complete:function(data){

			data = data.responseText;
			data = $.parseJSON(data)

			if(data.success == "true"){

				alert(data.msg);
			}else{
				alert(data.msg);
				
			}	
			$("#msg_ajax").hide();
		}

	});

}

 </script>

<style type="text/css">
.menu_top{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;border:1px solid;color:#596d9b;background-color:#d9e2ef;}
.border{border:1px solid #ced7e7;}
.table_line{text-align:center;font:normal normal 10px Verdana,Geneva,Arial,Helvetica,sans-serif;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;border:0px solid;background-color:white;}
input{font-size:10px;}
.top_list{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;color:#596d9b;background-color:#d9e2ef;}
.line_list{text-align:left;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:x-small;font-weight:normal;color:#596d9b;background-color:white;}
caption, .titulo_tabela {background-color:#596d9b;font:bold 14px "Arial";color:white;text-align:center;}
thead, .titulo_coluna {background-color:#596d9b;font:bold 11px "Arial";color:white;text-align:center;}
.formulario{background-color:#D9E2EF;font:normal normal 11px Arial;width:700px;margin:auto;text-align:left;}
.msg, .msg_erro{background-color:#FF0000;font:bold 16px "Arial";color:white;text-align:center;}
.formulario caption{padding: 3px;}
.msg{background-color:#51AE51;color:white;}
table.tabela tr td{font-family:verdana;font-size:11px;border-collapse:collapse;border:1px solid #596d9b;}
.texto_avulso{font:14px Arial;color:rgb(89,109,155);background-color:#d9e2ef;text-align:center;width:700px;margin:0 auto;border-collapse:collapse;border:1px solid #596d9b;}
.btn_excel {
  -pie-background: linear-gradient(top, #559435 0%, #63AE3D 72%);
  behavior: url(plugins/PIE/PIE.htc);
}
.btn_excel, .btn_excel span, .btn_excel span img, .btn_excel span.txt {
  background: #FFFFFF !important;
  background-color: #FFFFFF !important;
  background-image: #FFFFFF !important;
  border: 0px;
}
.btn_excel span.txt {
        color: #0088cc;
}
</style>


<table id = "erro" width='700' align='center' border='0' bgcolor='#d9e2ef'>
<? if (strlen ($msg_erro) > 0) { ?>
	<tr class="msg_erro">
		<td> <? echo $msg_erro; ?></td>
	</tr>
<? } ?>

<? if (strlen ($msg) > 0) { ?>
	<tr class="msg">
		<td> <? echo $msg; ?></td>
	</tr>
<? } ?>
</table>

<form method="post" name="frm_pesquisa" action="relatorio_pesquisa_posto_blackedecker.php">
	<table class="formulario" border='0' cellpadding='5' cellspacing='2'>
		<caption>PARÂMETROS DA CONSULTA - RELATÓRIO DE PESQUISA DE POSTO</caption>
		<tr><td colspan='3'>&nbsp;</td></tr>
		<tr>
			<td style='width:135px'>&nbsp;</td>
			<td style='width:165px'>
				<label for="data_inicial">Código Posto</label><br>
                <input type="text" name="posto_codigo" size="12" value="<?echo $posto_codigo?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', document.frm_pesquisa.posto_codigo,'')">
			</td>
			<td>
				<label for="data_final">Descrição Posto</label><br>
    			<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', '', document.frm_pesquisa.posto_nome)">
			</td>
		</tr>
		<tr>
			<td style='width:135px'>&nbsp;</td>
			<td style='width:165px'>
				<label for="data_inicial">Data Inicial *</label><br>
                <input id="data_inicial" maxlength="10" name="data_inicial" size='12' type="text" class="frm" value="<?=$data_inicial?>">
			</td>
			<td>
				<label for="data_final">Data Final *</label><br>
                <input id="data_final" maxlength="10" name="data_final" size='12' type="text" class="frm" value="<?=$data_final?>">
			</td>
		</tr>
       
        <tr>
        	<td style='width:135px'>&nbsp;</td>
        	<td>
				<label >Região</label><br>
				<select title='Selecione a Região' style='width:200px;' name='regiao' id='regiao' onchange="montaComboEstado();" >
					<option></option>
					<? foreach ($regioes as $sigla=>$regiao_nome) {
							echo "<option value='$sigla'";
									if($sigla == $regiao){
										print "selected";
									}
							echo ">$regiao_nome</option>\n";
						}
					?>				
				</select>
			</td>
			<td>
				<label >Estado</label><br>
				<select title='Selecione o Estado' style='width:200px;' name='estado' id='estado'>
					<option></option>
					<? foreach ($estados as $sigla=>$estado_nome) {// a variavel $estados esta definida em ../helpdesk/mlg_funciones
							echo "<option value='$sigla'";
									if($sigla == $estado){
										print "selected";
									}
							echo ">$estado_nome</option>\n";
						}
					?>				
				</select>
			</td>
			
        </tr>
   
		<tr style='text-align:center!important; margin: 30px !important;'>
			<td colspan="3"><br />
				<input name="btn_acao" type="hidden" value='t' />
				<!-- <button value="" type='button' onclick="geraRelatorio()">Filtrar</button> -->
				<button value="1" name="geraRelatorio" type="submit">Filtrar</button>
			</td>
		</tr>
	</table>
</form> <br/>
<? if(($_POST['geraRelatorio']=='1') && (strlen($msg_erro)==0)){ ?>

<table class="tabela" width="700px" cellspacing="1" cellpadding="1" border="0" align="center">
	<thead>
		<th>Cód. Posto</th>
		<th width:'100px'>Nome Posto</th>
		<th>Classificação Posto</th>
		<th>Cidade</th>
		<th>Estado</th>
		<th>Região</th>
		<th>Classif. Colaboradores</th>
		<th>Dt. Nasc</th>
		<th>Função</th>
		<th>Tam. Camisa</th>
		<th>Brinde</th>
		<th>Qtde Brinde</th>
		<th>Tp. Brinde</th>
		<th></th>
		<th colspan="4">Fotos</th>
		
	</thead>
	<tbody>
		
		<?
			$postoAnt="";
			$temBrinde 		= false;
			$temQtdBrinde	= false;
			$temTpBrinde	= false;
			for ($i=0; $i <= $numRows; $i++) { 
				$row = pg_fetch_assoc($res, $i);
				$posto 			= $row["posto"];
				$tipo_posto		= $row["tipo_posto"];
				$nomePosto 		= $row["nome"];
				$estado 		= $row["estado"];
				$cidade 		= $row["cidade"];
				$regiao 		= $row["regiao"];
				$fabrica 		= $row["fabrica"];
				$codigo_posto 	= $row["codigo_posto"];
				$pesquisa 		= $row["pesquisa"];
				$descricao		= $row["descricao"];
				$txt_resposta	= $row["txt_resposta"];

				$txt_pergunta	= $row["descricao"];

				if($postoAnt != $posto){ 
					
					//se for a primeira iteração, abre uma linha
					if($i == 0 ){?>
						<tr>
					<?} else {
		
						//verifica se campos ja foram preenchidos
						if(!$temBrinde){?>
							<td>
								<select name="tem_brinde_<?=$postoAnt?>" id="tem_brinde_<?=$postoAnt?>">
									<option value=""></option>
									<option value="sim">SIM</option>
									<option value="nao">NÃO</option>
								</select>
							</td>
					  <?}
					  	if(!$temQtdBrinde){?>
							<td><input style="width:30px;" type="text" name="qtde_brinde_<?=$postoAnt?>" id="qtde_brinde_<?=$postoAnt?>" /></td>
					  <?}
					  	if(!$temTpBrinde){?>
							<td><input style="width:100px;" type="text" name="tp_brinde_<?=$postoAnt?>" id="tp_brinde_<?=$postoAnt?>" /></td>	
						<?}?>
						<td><button type="button" name="gravaBrindes_<?=$postoAnt?>" onclick="gravaBrindes(<?=$postoAnt?>)" >Salvar</button></td>	
						<? 
							$files = pegaFotosS3($postoAnt);
							
							$thumbs = pegaThumbsS3($postoAnt);
							
							foreach ($files as $key => $file) {

								$linkFile = $file["link"];
								$nomeFile = $file["nome"];
								$thumb = $thumbs[$key];
								if($key == "foto_fachada"){?>
									<td id="td_foto_fachada_<?=$postoAnt?>">
										<a id="src_foto_fachada" href="<?=$linkFile?>">
											<img title="Foto da Fachada" id='img_foto_fachada' src="<?=$thumb?>" alt='Sem Fotos'>
											</a><br/>
										<button type='button' name='exclui_foto_fachada' onclick="excluiFoto('<?=$nomeFile?>', 'td_foto_fachada_<?=$postoAnt?>')" >Excluir</button>
									</td>
									<?
									continue;
								}
								if ($key == "foto_balcao"){?>
									<td id="td_foto_balcao_<?=$postoAnt?>">
										<a id='src_foto_balcao' href="<?=$linkFile?>"> 
											<img title='Foto Balcão' id='img_foto_balcao' src="<?=$thumb?>" alt='Sem Fotos'>
										</a><br/>
										<button type='button' name='exclui_foto_balcao' onclick="excluiFoto('<?=$nomeFile?>', 'td_foto_balcao_<?=$postoAnt?>')" >Excluir</button>
									</td>
									<?
									continue;

								}
								if($key == "foto_oficina"){?>
									<td id="td_foto_oficina_<?=$postoAnt?>">
										<a id='src_foto_oficina' href='<?=$linkFile?>'>
											<img title='Foto da Oficina' id='img_foto_oficina' src="<?=$thumb?>" alt='Sem Fotos'>
										</a><br/>
										<button type='button' name='exclui_foto_oficina' onclick="excluiFoto('<?=$nomeFile?>', 'td_foto_oficina_<?=$postoAnt?>')" >Excluir</button>
									</td>
									<?
									continue;
								}
								if($key =="foto_estoque"){?>
									<td id="td_foto_estoque_<?=$postoAnt?>">
										<a id='src_foto_estoque' href="<?=$linkFile?>">
											<img title='Foto do Estoque' id='img_foto_estoque' src='<?=$thumb?>' alt='Sem Fotos'>
										</a><br/>
										<button type='button' name='exclui_foto_estoque' onclick="excluiFoto('<?=$nomeFile?>', 'td_foto_estoque_<?=$postoAnt?>')" >Excluir</button>
									</td>
									<?
									continue;
								}
							}
						?>	

						</tr>
						<? 
							if(empty($posto)) break;

						?>
						<tr>
					<? }
						$postoAnt = $posto;
						$temBrinde 		= false;
						$temQtdBrinde	= false;
						$temTpBrinde	= false;
					?>
						<td><? echo $posto; ?></td>
						<td><? echo $nomePosto; ?></td>	
						<td><? echo $tipo_posto; ?></td>	
						<td><? echo $cidade; ?></td>
						<td><? echo $estado; ?></td>	
						<td><? echo $regiao; ?></td>	
						<td><? echo $txt_resposta; ?></td>
				<? }else{
						// verificar se estes campos foram preenchidos

						if($txt_pergunta == "Brinde"){
							$temBrinde 		= true;
						?>
							<td>
								<select name="tem_brinde_<?=$posto?>" id="tem_brinde_<?=$posto?>">
									<option value=""></option>
									<option value="sim" <?if($txt_resposta =="sim") echo "selected"; ?> >SIM</option>
									<option value="nao" <?if($txt_resposta =="nao") echo "selected"; ?> >NÃO</option>
								</select>
							</td>
					 <? }else{
					 		if(!$temBrinde){
					 			$temBrinde = false;
					 		}
					    }
					 	if($txt_pergunta == "Qtde"){
							$temQtdBrinde = true;?>
							<td><input style="width:30px;" type="text" name="qtde_brinde_<?=$posto?>" id="qtde_brinde_<?=$posto?>" value="<? echo $txt_resposta; ?>"/></td>
					<?  }else{
							if(!$temQtdBrinde){
								$temQtdBrinde = false;
							}
					 	}
						if($txt_pergunta == "Tipo de Brinde"){
							$temTpBrinde = true;?>
							<td><input style="width:100px;" type="text" name="tp_brinde_<?=$posto?>" id="tp_brinde_<?=$posto?>" value="<? echo $txt_resposta; ?>"/></td>	
					<?	}else{
							if(!$temTpBrinde){
								$temTpBrinde = false;
							}
					 	}
					 	?>

						<? if(!$temTpBrinde && !$temQtdBrinde && !$temBrinde ){?>
							<td><? echo $txt_resposta; ?></td>
						<?}?>
				<?}?>		
			<? } ?>
	</tbody>
</table>
<?
	/*
		GERAR arquivo DOS RESULTADOS
	*/
	$date = date('Ymd-Hi');
	$nomeArquivoTmp 	 = "/tmp/relatorio_pesquisa_posto_blackedecker-$login_admin-".$date.'.csv';
	$nomeArquivoDownload = "xls/relatorio_pesquisa_posto_blackedecker-$login_admin-".$date.".csv";
	
	$xls = fopen($nomeArquivoTmp, 'a');
	$thead .= "Cód. Posto;";
	$thead .= "Nome Posto;";
	$thead .= "Classificação Posto;";
	$thead .= "Cidade;";
	$thead .= "Estado;";
	$thead .= "Região;";
	$thead .= "Classif. Colaboradores;";
	$thead .= "Dt. Nasc;";
	$thead .= "Função;";
	$thead .= "Tam. Camisa;";
	$thead .= "Brinde;";
	$thead .= "Qtde Brinde;";
	$thead .= "Tp. Brinde;\n";
	fwrite($xls, $thead);
	fclose($xls);
	$postoAnt="";
	$z=0;
	$tRow="";
	for ($i=0; $i <= $numRows; $i++) { 
		$row = pg_fetch_assoc($res, $i);

		$posto 			= $row["posto"];
		$tipo_posto		= $row["tipo_posto"];
		$nomePosto 		= $row["nome"];
		$estado 		= $row["estado"];
		$cidade 		= $row["cidade"];
		$regiao 		= $row["regiao"];
		$fabrica 		= $row["fabrica"];
		$codigo_posto 	= $row["codigo_posto"];
		$pesquisa 		= $row["pesquisa"];
		$descricao		= $row["descricao"];
		$txt_resposta	= $row["txt_resposta"];

		$txt_pergunta	= $row["descricao"];

		if($posto != $postoAnt){
			if($i != 0){
				$tRow .= "\n";
			}
			### escreve n arquivo a quantidade especificada em $z;
			if($i-1==$z){
				
				$z=$z+30;
				$xls = fopen($nomeArquivoTmp, 'a');
				
				fwrite($xls, $tRow);
				fclose($xls);
				unset($xls);
				unset($tRow);
			}else if($i == ($numRows)){
				$xls = fopen($nomeArquivoTmp, 'a');
				fwrite($xls, $tRow);
				fclose($xls);
				unset($xls);
				unset($tRow);
			}
			$postoAnt = $posto;

			$tRow .= "$posto;";
			$tRow .= "$nomePosto;";
			$tRow .= "$tipo_posto;";
			$tRow .= "$cidade;";
			$tRow .= "$estado;";
			$tRow .= "$regiao;";
			$tRow .= "$txt_resposta;";
			
		}else{
			$tRow .= "$txt_resposta;";
		}
	}
	if(file_exists($nomeArquivoTmp)){
			system("mv $nomeArquivoTmp $nomeArquivoDownload");
		}
		if(file_exists($nomeArquivoDownload)){

			echo "<br/><div class='btn_excel' id='gerar_excel'>
					<a href=\"$nomeArquivoDownload\" class='botao' target='_blank' id='download_link' class='botao'><span><img src='imagens/excel.png' width='20px' /></span><span class='txt'>Gerar Excel</span></a>
				  </div>";
		}
?>
<? } ?>
<div id="msg_ajax" style="display: none;"><img valign="absmiddle" src="imagens/loading.gif" /> <span style="display: inline-block; margin-left: 5px; top:-2px; position:relative; ">Carregando</span></div>
<? include 'rodape.php'; ?>
