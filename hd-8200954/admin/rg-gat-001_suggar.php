<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';




include 'funcoes.php';

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$imagem_upload =  $login_admin."_".date("dmyhmi");

# Verifica permissões do usuario para acessar a tela
$sql = "select login,privilegios,nome_completo from tbl_admin where fabrica = $login_fabrica and admin = $login_admin;";

$res = pg_query ($con,$sql);
$usuario_privilegio = pg_fetch_result($res, 0, 1);
$nome_admin = pg_fetch_result($res, 0, 2);

if($usuario_privilegio != '*' && !strstr($usuario_privilegio, 'inspetor')){
	header('location: menu_auditoria.php');			
}

##########################################################


function makeComponent($idpergunta,$pergunta,$tipo,$options = null){
	

	switch ($tipo) {
	 	case 'text':			
			$return = '	<div class="span8">
							<div class="control-group">				
								<label class="control-label">'.$pergunta.'</label>
								<div class="controls">
									<input type="text" id="'.$idpergunta.'" name="'.$idpergunta.'" class="span7" value=""> 
								</div>									
							</div>
						</div>	';
			break;

		case 'textarea':
			
			$return ='<div class="span8">
							<div class="control-group">				
								<label class="control-label">'.$pergunta.'</label>
								<div class="controls">
									<textarea name="'.$idpergunta.'" id="'.$idpergunta.'" class="span7"></textarea> 
								</div>									
							</div>
						</div>';

			break;

		case 'radio':

			$selectOptions = $options['selectOptions'];

			foreach ($selectOptions as $value) {
				$ops .= '<div class="radio">
						 	<label>
						    	<input type="radio" name="'.$idpergunta.'" id="'.$value['tipo_resposta_item'].'" value="'.$value['tipo_resposta_item'].'">
						    	'.$value['descricao'].'
						  	</label>
						</div>';
			}

			$return = '<div class="span8">
							<div class="control-group">	
							<label class="control-label">'.$pergunta.'</label>			
								<div class="controls">
									'.$ops.'
								</div>
							</div>			
						</div>';
			break;
	}

	return $return;
}



##########################################################

# AJAX


if(isset($_POST['ajax_pesquisa'])){

	$codigo_posto = $_POST['codigo_posto'];
	$nome_posto = $_POST['nome_posto'];
	$data_inicial = $_POST['data_inicial'];
	$data_final = $_POST['data_final'];


	if(strlen($data_inicial) == 0 ||strlen($data_final) == 0 || strlen($codigo_posto) == 0 || strlen($nome_posto) == 0){
		echo json_encode(array("status" => "error","message" => "Preencha todos os campos"));
		exit;
	}



	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) >= 1){
		$posto = pg_result($res,0,posto);


		$sql = "SELECT COUNT(*) FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto AND data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
		$res = pg_query($con,$sql);		
		$response['qtd_os'] = pg_result($res,0,count);

		
		$sql = "SELECT os, sua_os FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto AND data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
		$res = pg_query($con,$sql);		
		$response['qtd_os_periodo'] = pg_fetch_all($res);

		$sql = "SELECT SUM(tbl_faturamento_item.qtde) FROM tbl_faturamento JOIN tbl_faturamento_item USING(faturamento) WHERE tbl_faturamento.fabrica = $login_fabrica AND (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%') AND tbl_faturamento.posto = $posto AND tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final'";
		$res = pg_query($con,$sql);
		$response['qtd_pecas_periodo'] = pg_result($res,0,sum);

		if($response['qtd_pecas_periodo'] == ""){
			$response['qtd_pecas_periodo'] = "0";
		}


		$sql = "SELECT COUNT(*) FROM tbl_hd_chamado JOIN tbl_hd_chamado_extra USING(hd_chamado) WHERE tbl_hd_chamado.fabrica = $login_fabrica AND tbl_hd_chamado.categoria = 'at_reclamacao' AND tbl_hd_chamado_extra.posto = $posto AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";				
		$res = pg_query($con,$sql);		
		$response['qtd_reclamacoes_periodo'] =  pg_result($res,0,count);

		$sql = "SELECT COUNT(*) FROM tbl_comunicado JOIN tbl_comunicado_posto_blackedecker USING(comunicado) WHERE tbl_comunicado.fabrica = $login_fabrica AND tbl_comunicado.posto = $posto AND tbl_comunicado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' AND tbl_comunicado_posto_blackedecker.data_confirmacao IS NOT NULL";				
		$res = pg_query($con,$sql);		
		$response['qtd_comunicaados_periodo'] = pg_result($res,0,count);



		$response['status'] = "success";

		
		echo json_encode($response);

	}else{
		echo json_encode(array("status" => "error","message" => utf8_encode("Posto não encontrado")));
	}
	

	exit;
}

if(isset($_POST['ajax_posto'])){


	$codigo_posto = $_POST['codigo_posto'];

	if(strlen($codigo_posto) > 0){
		$sql = "select tbl_posto.posto,endereco,fone,cidade,estado,email,contato from tbl_posto join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
		where tbl_posto_fabrica.codigo_posto = '$codigo_posto' and fabrica = $login_fabrica;";
	
		
		$res = pg_query($con,$sql); 
		if(pg_num_rows($res) > 0){

			$informacoes = pg_fetch_row($res);

		}else{
			echo json_encode(array("status" => "error","message" => utf8_encode("Informações do posto não encontradas")));	
			exit;
		}

		$sql = " select data from tbl_credenciamento where fabrica = $login_fabrica and posto = ".$informacoes[0]." order by data desc limit 1;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) == 0){
			$sql = "select data_input from tbl_posto_fabrica where fabrica = $login_fabrica and posto = ".$informacoes[0].";";
			
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$data_credenciamento = pg_result($res,0,data_input);
				$data_credenciamento = date('d/m/Y',strtotime($data_credenciamento));

			}else{
				$data_credenciamento = "";
			}
		}else{
			$data_credenciamento = date('d/m/Y',strtotime(pg_result($res,0,data)));
		}

		$response['posto'] = $informacoes[0];
		$response['endereco'] = $informacoes[1];
		$response['fone'] = $informacoes[2];
		$response['cidade'] = $informacoes[3].'/'.$informacoes[4];
		$response['email'] = $informacoes[5];
		$response['contato'] = $informacoes[6];
		$response['credenciamento'] = $data_credenciamento;
		$response['status'] = "success";

		echo json_encode($response);	
	}else{
		echo json_encode(array("status" => "error","message" => utf8_encode("Posto não encontrado")));
	}

	exit;
}


##########################################################



include 'cabecalho_new.php';
$plugins = array(
	"mask",
	"datepicker",
	"shadowbox"

);

include("plugin_loader.php");

# Make Radio Tables


function makeRadioTable($radios,$login_fabrica,$con){




	$sql = "select tbl_tipo_resposta.tipo_resposta,tbl_tipo_resposta_item.descricao, tbl_tipo_resposta_item.tipo_resposta_item from tbl_tipo_resposta join tbl_tipo_resposta_item on tbl_tipo_resposta.tipo_resposta = tbl_tipo_resposta_item.tipo_resposta where fabrica = $login_fabrica and tbl_tipo_resposta.tipo_resposta = ".$radios[0]['tipo_resposta'].";";
	$res = pg_query($con,$sql);
	$opcoes = pg_fetch_all($res);

	$checkboxesPattern = "";
	for($i=0; $i < count($opcoes);$i++){

		$ths .= "<th>".$opcoes[$i]['descricao']."</th>";

		$checkboxesPattern .= '<td class="tac">
									<input type="radio"    name="[pergunta]" value="'.$opcoes[$i]['tipo_resposta_item'].'">
								</td>';
	}


	$table = '<table class="table table-striped table-hover table-bordered">';
	$table .= '<thead>
					<tr>
						<th>Pergunta</th>
						'.$ths.'
					</tr>
				</thead>
				<tbody>';




	for($i=0;$i< count($radios);$i++){

		$sql = "select descricao from tbl_pergunta  where fabrica = ".$login_fabrica." and pergunta = ".$radios[$i]['idpergunta'].";";	
		$res = pg_query($con,$sql);
		$pergunta = pg_result($res,0,descricao);

		$ops = str_replace('[pergunta]', $radios[$i]['idpergunta'], $checkboxesPattern);

		$table .= "<tr>
						<td>".$pergunta."</td>
						".$ops."
					</tr>";



	}

	$table .= '		</tbody>
				</table>';

	return $table;
}

##################################################


if(isset($_POST['gravar_inspecao_s'])){

	$tipo_inspecao = $_POST['tipo_inspecao'];
	$elaboracao = $_POST['elaboracao'];
	$data_elaboracao = $_POST['data_elaboracao'];
	$responsavel_posto = $_POST['responsavel_posto'];
	$posto = $_POST['codigo_posto'];
	$contato = $_POST['contato'];
	$endereco = $_POST['endereco'];
	$telefone = $_POST['telefone'];
	$cidade_estado = $_POST['cidade_estado'];
	$email = $_POST['email'];
	$data_credenciamento = $_POST['data_credenciamento'];
	$data_incial_atende = $_POST['data_incial_atende'];
	$data_final_atende = $_POST['data_final_atende'];
	$codigo_posto = $_POST['codigo_posto'];
	$descricao_posto = $_POST['codigo_posto'];
	$motivo_visita = $_POST['motivo_visita'];
	$conclusao_auditoria = $_POST['ck-avaliacao'];
	$cidades_posto = $_POST['cidades_posto'];
	$comentario_qtd_comunicaados_periodo = $_POST['comentario_qtd_comunicaados_periodo']; 	#tbl_auditoria_online.comentario_qtde_os_atendida
	$comentario_qtd_reclamacoes_periodo = $_POST['comentario_qtd_reclamacoes_periodo']; 	#tbl_auditoria_online.comentario_qtde_os_revenda
	$comentario_qtd_pecas_periodo = $_POST['comentario_qtd_pecas_periodo']; 				#tbl_auditoria_online.comentario_qtde_peca_trocada
	$comentario_qtd_os_periodo = $_POST['comentario_qtd_os_periodo'];						#tbl_auditoria_online.comentario_qtde_peca_revenda



			

	$arquivo_1 = $_POST['arquivo_1'];
	$arquivo_2 = $_POST['arquivo_2'];

	

	// $arquivo_1_array = explode('.', $arquivo_1);

	// if(count($arquivo_1_array) > 0){
	// 	$arquivo_1 = $arquivo_1_array[0];	
	// }

	// $arquivo_2_array = explode('.', $arquivo_2);
	// if(count($arquivo_2_array) > 0){
	// 	$arquivo_2 = $arquivo_1_array[0];	
	// }

	


	$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$posto' AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	$campos_vazios = false;

	if(pg_num_rows($res) > 0){
		$posto = pg_result($res,0,posto);	
	}else{
		$campos_vazios = true;
		$msg_error = "Posto incorreto";
	}	
	
	foreach ($$_POST as $key => $value) {
		if($value == ""){
			$campos_vazios = true;			
		}		
	}



	if($campos_vazios == false){
		$sql = "SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE";
		$res = pg_query($con,$sql);
		for($i=0;$i < pg_num_rows($res);$i++){
			if(isset($_POST[pg_result($res,$i,linha)])){
				$linhasAtende[] = pg_result($res,$i,linha);
			}
		}

		$linhasAtende = "Array[".implode(',', $linhasAtende)."]";

		$sql = "SELECT pesquisa FROM tbl_pesquisa WHERE fabrica = $login_fabrica AND ativo IS TRUE";
		$res = pg_query($con,$sql);		
		$idpesquisa = pg_result($res,0,pesquisa);

		if($tipo_inspecao == 'visita'){
			$tipo_inspecao = "true";
		}else{
			$tipo_inspecao = "false";
		}

		$sql = "insert into tbl_auditoria_online(fabrica,posto,admin,data_visita,visita_posto,tipo_auditoria,responsavel_pa,
			data_credenciamento,linha,data_pesquisa,data_final,conclusao_auditoria,pesquisa,inspetor,comentario_lgr,comentario_qtde_os_atendida,
			comentario_qtde_peca_trocada,comentario_qtde_os_revenda,comentario_qtde_peca_revenda) 
			values($login_fabrica,$posto,$login_admin,'$data_elaboracao',$tipo_inspecao,'$motivo_visita','$responsavel_posto','$data_credenciamento',
			$linhasAtende,'$data_incial_atende','$data_final_atende','$conclusao_auditoria',$idpesquisa,$login_admin,'$cidades_posto','$comentario_qtd_comunicaados_periodo',
			'$comentario_qtd_pecas_periodo','$comentario_qtd_reclamacoes_periodo','$comentario_qtd_os_periodo')
			RETURNING auditoria_online;";

		$res = pg_query($con,$sql);
		$id_auditoria_online = pg_result($res,0,auditoria_online);


		// $s3 = new AmazonTC("inspecao", $login_fabrica);
		// if($arquivo_1 != ""){

		// 	$aux = array("file_temp" => $arquivo_1,"file_new" => $id_auditoria_online."_1");			
		// 	$ret = $s3->moveTempToBucket($aux);
			
		// }

		// if($arquivo_2 != ""){
		// 	$aux = array("file_temp" => $arquivo_2,"file_new" => $id_auditoria_online."_2");
		// 	$ret = $s3->moveTempToBucket($aux);

			

			
		// }

	
		$extencao = explode('.', $arquivo_1);
		if(count($extencao) > 0){
			$extencao = $extencao[1];
		}else{
			$extencao = "jpg";
		}

		$s3 = new AmazonTC("inspecao", $login_fabrica);
		
		$aux = array(
			array("file_temp" => $arquivo_1,
				"file_new" => $id_auditoria_online."_1.".$extencao
			)
		);		

		

		$ret = $s3->moveTempToBucket($aux);

		$extencao = explode('.', $arquivo_2);
		if(count($extencao) > 0){
			$extencao = $extencao[1];
		}else{
			$extencao = "jpg";
		}

		$aux = array(
			array("file_temp" => $arquivo_2,
				"file_new" => $id_auditoria_online."_2.".$extencao
				)
			);					

		

		$ret = $s3->moveTempToBucket($aux);


		$sql = "select tbl_pergunta.pergunta as idpergunta, tbl_tipo_resposta.tipo_resposta, tbl_tipo_resposta.tipo_descricao
			from tbl_pesquisa join tbl_pesquisa_pergunta on tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa  
			join tbl_pergunta on tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta 
			join tbl_tipo_resposta on tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta 
			where tbl_pesquisa.fabrica = $login_fabrica order by tbl_pesquisa_pergunta.ordem;";

		$res = pg_query($con,$sql);
		$perguntas = pg_fetch_all($res);

		
		$sql = "insert into tbl_resposta(auditoria_online,os) values($id_auditoria_online,".$_POST['ordens-periodo'].");";		
		pg_query($con,$sql);

		for($i=0;$i<count($perguntas);$i++){
			if(isset($_POST[$perguntas[$i]['idpergunta']])){

				if($perguntas[$i]['tipo_descricao'] == 'radio'){
					$sql = "insert into tbl_resposta(pergunta,tipo_resposta_item,pesquisa,auditoria_online) values(".$perguntas[$i]['idpergunta'].",".$_POST[$perguntas[$i]['idpergunta']].",$idpesquisa,$id_auditoria_online);";
				}else{
					$sql = "insert into tbl_resposta(pergunta,txt_resposta,pesquisa,auditoria_online) values(".$perguntas[$i]['idpergunta'].",'".$_POST[$perguntas[$i]['idpergunta']]."',$idpesquisa,$id_auditoria_online);";
				}
				pg_query($con,$sql);


			}
		}

		 $msg_success = "Inspeção gravada com sucesso";

		// $sql = "select pergunta from tbl_pergunta where fabrica = $login_fabrica";
		// $res = pg_query($con,$sql);

		// $count = pg_num_rows($res);
		// for($i=0;$i<$count;$i++){

		// 	if(isset($_POST[pg_result($res,$i,pergunta)])){
		// 		$respostas[pg_result($res,$i,pergunta)] = $_POST[pg_result($res,$i,pergunta)];
		// 	}
		// }	

	}else{
		if(strlen($msg_error) == 0){
			$msg_error = "Preencha todos campos por favor";
		}		
	}

	
}
?>

<script language="javascript" src="plugins/jquery.form.js"></script>
<script language="javascript" src="../js/FancyZoom.js"></script>
<script language="javascript" src="../js/FancyZoomHTML.js"></script>

<script language="javascript">
	$(function() {
		$.datepickerLoad(Array("data_credenciamento","data_inicial","data_final"));
	});
</script>


<form name="frm_inspecao" id='frm_inspecao' method="post" action="<?=$PHP_SELF?>"  enctype="multipart/form-data" class="form-inline tc_formulario">
	<div class="titulo_tabela ">Inspeção de Posto Autorizado</div>
	<br/>
	<?php
	if(strlen($msg_success) > 0){
	?>
	<div class='row-fluid'>		
		<div class="span12">
			<div class="alert">
				<p><?php echo $msg_success ?></p>
			</div>			
		</div>
	</div>
	<?php
	}elseif(strlen($msg_error) > 0){
	?>
	<div class='row-fluid'>		
		<div class="span12">
			<div class="alert alert-danger">
				<p><?php echo $msg_error ?></p>
			</div>
			
		</div>
	</div>
	<?php
	}
	?>
	<div class='row-fluid'>		
		<div class='span8' style="background: #f3f3f3;width: 233px;padding-left: 15px;">
			<div class="control-group">				
				<label><b>Motivo da Visita</b></label>
				<div class="control-row">
					<label class="checkbox inline" for="motivo_visita1">
			            <input type="radio" name="motivo_visita" id="motivo_visita1" value="rotina"> Rotina
			        </label>
				</div>
				<div class="control-row">
					<label class="checkbox inline" for="motivo_visita">
			            <input type="radio" name="motivo_visita" id="motivo_visita" value="resultado"> Resultado da Auditoria On-Line
			        </label>
				</div>
			</div>			
		</div>		
		<div class='span4'></div>
	</div>
	<br/>
	
	<div class='row-fluid' style="margin-top:15px">
		<div class='span2'><img style="margin-left:5px" src="../logos/suggar_admin1.jpg"></div>
		<div class='span2'></div>
		<div class='span4'>
			<div class="control-group">				
				<div class="control-row">
					<label class="checkbox inline" for="tipo_inspecao_0">
			            <input type="radio" name="tipo_inspecao" id="tipo_inspecao_0" value="visita"> Visita	            
			        </label>
				</div>
				<div class="control-row">
					<label class="checkbox inline" for="tipo_inspecao_1">
			            <input type="radio" name="tipo_inspecao" id="tipo_inspecao_1" value="auditoria"> Auditoria
			        </label>
				</div>
			</div>			
		</div>		
		<div class='span4'>
			<div class="control-group">
				<div class="control-row">
					<label class="control-label">Elaboração</label>
					<div class="controls">
						<input disabled type="text" name="elaboracao_v"  value="<?php echo $nome_admin ?>"> 
						<input type="hidden" name="elaboracao" value="<?php echo $nome_admin ?>" > 
					</div>					
				</div>

				<div class="control-row">
					<label class="control-label">Data</label>
					<div class="controls">
						<input type="text" name="data_elaboracao" id="data_elaboracao" value="">  
					</div>
				</div>
			</div>
		</div>
		
	</div>	

	<div class="row">
		<div class='span2'></div>
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Responsável Posto</label>
				<div class="controls">
					<input type="text" name="responsavel_posto"> 
				</div>									
			</div>
		</div>
		<div class='span4'>
				
				<label class="control-label" style="margin-top: 25px"><b>RG - GAT - 001</b></label>				
			
		</div>
		<div class='span2'></div>		
	</div>

	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class='control-group' >
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto" class=''  >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>		
		<div class='span4'>
			<div class='control-group'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class=''  >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
		</div>		
		<div class='span2'></div>		
	</div>	


	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class="control-group">				
				<label  class="control-label">Endereço</label>
				<div class="controls">
					<input  disabled type="text" name="endereco_v" id="endereco_v"> 
					<input   type="hidden" name="endereco" id="endereco"> 
				</div>									
			</div>
		</div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Telefone</label>
				<div class="controls">
					<input  disabled type="text" name="telefone_v" id="telefone_v"> 
					<input   type="hidden" name="telefone" id="telefone"> 
				</div>									
			</div>
		</div>		
		<div class='span2'></div>		
	</div>

	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Cidade/Estado</label>
				<div class="controls">
					<input disabled type="text" name="cidade_estado_v" id="cidade_estado_v"> 
					<input  type="hidden" name="cidade_estado" id="cidade_estado"> 
				</div>									
			</div>
		</div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Email</label>
				<div class="controls">
					<input disabled type="text" name="email_v" id="email_v"> 
					<input  type="hidden" name="email" id="email"> 
				</div>									
			</div>
		</div>		
		<div class='span2'></div>		
	</div>

	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Data Credenciamento</label>
				<div class="controls">
					<input disabled type="text" name="data_credenciamento_v" id="data_credenciamento_v"> 
					<input  type="hidden" name="data_credenciamento" id="data_credenciamento"> 
				</div>									
			</div>
		</div>				
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Contato</label>
				<div class="controls">
					<input disabled type="text" name="contato_v" id="contato_v"> 
					<input  type="hidden" name="contato" id="contato"> 
				</div>									
			</div>
		</div>		
		<div class='span2'></div>		
	</div>

	<div class="titulo_tabela ">Linha de Atendimento</div>

	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th>Linha</th>
				<th>Atende</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE";
				$res = pg_query($con,$sql);

				for($i = 0;$i < pg_num_rows($res);$i++){
					?>
					<tr>
						<td class="tac">
							<?php echo pg_result($res,$i,nome) ?>
						</td>
						<td class="tac">
							<input type="checkbox" name="<?php echo pg_result($res,$i,linha) ?>" value="t">
						</td>
					</tr>

					<?php
				}
			?>
		</tbody>
	</table>

	<div class="row">
	       <div class='span2'></div>
	       <div class='span8'>
                       <div class="control-group">                                
			       <label class="control-label">Descreva as cidades que posto atende e distâncias</label>
                               <div class="controls">                                       
                                       <textarea class="span7" name="cidades_posto" id="cidades_posto"></textarea>
                               </div>                                                                        
                       </div>
               </div> 
	       <div class='span2'></div>
	</div>

	<?php


		$sql = "select tbl_pergunta.pergunta as idpergunta, tbl_pergunta.descricao as pergunta, tbl_tipo_resposta.tipo_descricao as tipo, tbl_tipo_resposta.tipo_resposta
		from tbl_pesquisa join tbl_pesquisa_pergunta on tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa  
		join tbl_pergunta on tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta 
		join tbl_tipo_resposta on tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta 
		where tbl_pesquisa.fabrica = $login_fabrica and tbl_tipo_resposta.tipo_descricao <> 'radio' order by tbl_pesquisa_pergunta.ordem;";
		
		$res = pg_query($con,$sql);

		$pesquisaComponentes = pg_fetch_all($res);
		$pesquisaComponentesInputs = $pesquisaComponentes;

		$questionNo = array("Parecer do posto em relação a fabrica","Parecer do inspetor técnico");
		$questionQuadroFunc = array("Quadro de funcionários","Veículos para atendimento");
		$questionNo2 = array("Parecer do inspetor técnico as questões acima");


		$mostrar = false;
		foreach ($pesquisaComponentesInputs as $componente) {					
			
			foreach ($questionQuadroFunc as  $value) {
				if(strtolower($value) == strtolower($componente['pergunta'])){
					
					$mostrar = true;
				}	
			}

			if($mostrar == true){
				$mostrar = false;
				
				if(!in_array($questionNo, strtolower($componente['pergunta']))){

					if($componente['tipo'] == 'radio'){

						$sql = "select tipo_resposta_item,descricao from tbl_tipo_resposta_item where tipo_resposta = ".$componente['tipo_resposta'];				
						$res = pg_query($con,$sql);
						$itens = pg_fetch_all($res);				

						$options = array("selectOptions" => $itens);

						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo'],$options);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php

					}else{

						
						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo']);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php
					}					
				}			 	
			}else{
				$mostrar = false;

			 }
		}

	?>


	<div class="row">
			<div class="span2">
			</div>
			<div class="span3">
				<label class="control-label">Aparência interna</label>
				<input form="file_form_1" type="file" id="arq1" name="arq1" value=''> 
		        <input form="file_form_1" type="hidden" name="nome_arquivo" value="<?php echo $imagem_upload."_1"; ?>">			        
		        <input form="file_form_1" type="hidden" name="i" value="1">
		        <span  form="file_form_1" id="loading_1" style="display:none">Uploading...</span>
		        <img form="file_form_1" src="" id="img_1"/>
			</div>
			<div class="span3">
				<label form="file_form_2" class="control-label">Aparência externa</label>
				<input form="file_form_2" type="file" id="arq2" name="arq2" value=''> 
			    <input form="file_form_2" type="hidden" name="nome_arquivo" value="<?php echo $imagem_upload."_2"; ?>">
		        <input form="file_form_2" type="hidden" name="i" value="2">
		        <span form="file_form_2" id="loading_2" style="display:none">Uploading...</span>
		        <img form="file_form_2" src="" id="img_2"/>
			</div>
			<div class="span2">
			</div>
		</div>
		

	<div class="titulo_tabela ">Pesquisa de Período</div>

	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Data Inicial</label>
				<div class="controls">
					<input type="text" class="tac" name="data_incial_atende" id="data_incial_atende"> 
				</div>									
			</div>
		</div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Data Final</label>
				<div class="controls">
					<input type="text" class="tac" name="data_final_atende" id="data_final_atende"> 
				</div>									
			</div>
		</div>		
		<div class='span2'></div>		
	</div>	

	

	<p>		
		<input type="button" class="btn btn-small" id="btn_pesquisa" value="Buscar Informações">
		<input type="button" class="btn btn-small btn-danger" id="btn_limpa_pesquisa" value="Limpar Pesquisa">		
	</p>
	<p><small id="message_busca" style="display:none">Buscando informações</small></p>


	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Quantidade de OS's do período</label>
				<div class="controls">
					<input type="text" disabled id="qtd_os_periodo" name="qtd_os_periodo" value=''> 
				</div>				
				<label class="control-label">Comentário</label>					
				<div class="controls">
					<textarea name="comentario_qtd_os_periodo"></textarea>
				</div>									
			</div>
		</div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Quantidade de peças enviadas</label>
				<div class="controls">
					<input type="text" disabled id="qtd_pecas_periodo" name="qtd_pecas_periodo" value=''> 
				</div>			
				<label class="control-label">Comentário</label>					
				<div class="controls">
					<textarea name="comentario_qtd_pecas_periodo"></textarea>
				</div>															
			</div>
		</div>		
		<div class='span2'></div>		
	</div>	
	
	<div class="row">
		<div class='span2'></div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Quantidade de reclamações de callcenter</label>
				<div class="controls">
					<input type="text" disabled id="qtd_reclamacoes_periodo" name="qtd_reclamacoes_periodo" value=''> 
				</div>									
				<label class="control-label">Comentário</label>					
				<div class="controls">
					<textarea name="comentario_qtd_reclamacoes_periodo"></textarea>
				</div>									
			</div>
		</div>		
		<div class='span4'>
			<div class="control-group">				
				<label class="control-label">Comunicados que o posto marcou como lido</label>
				<div class="controls">
					<input type="text" disabled id="qtd_comunicaados_periodo" name="qtd_comunicaados_periodo" value=''> 
				</div>			
				<label class="control-label">Comentário</label>					
				<div class="controls">
					<textarea name="comentario_qtd_comunicaados_periodo"></textarea>
				</div>															
			</div>
		</div>		
		<div class='span2'></div>		
	</div>	




	
	<?php

		

		


		?>

		

		<?php



		$sql = "select tbl_pergunta.pergunta as idpergunta, tbl_pergunta.descricao as pergunta, tbl_tipo_resposta.tipo_descricao as tipo, 
		tbl_tipo_resposta.tipo_resposta,tbl_tipo_resposta.descricao as descricao_tipo_resposta
		from tbl_pesquisa join tbl_pesquisa_pergunta on tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa  
		join tbl_pergunta on tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta 
		join tbl_tipo_resposta on tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta 
		where tbl_pesquisa.fabrica = $login_fabrica and tbl_tipo_resposta.tipo_descricao = 'radio' and to_ascii(upper(tbl_tipo_resposta.descricao),'LATIN9') ilike(to_ascii(upper('%Satisfação2'),'LATIN9')) order by tbl_tipo_resposta.tipo_resposta,tbl_pesquisa_pergunta.ordem ;";
//		echo $sql;exit;
		
		$res = pg_query($con,$sql);


		$pesquisaComponentes = pg_fetch_all($res);				
		
		$tipoAux = $pesquisaComponentes[0]['tipo_resposta'];

		for ($i=0; $i < count($pesquisaComponentes); $i++) { 
			$radioTable[] = $pesquisaComponentes[$i]; 	
		}

		$return = makeRadioTable($radioTable,$login_fabrica,$con);
		echo "<br>";	
		echo '<div class="titulo_tabela ">Satisfação do Cliente</div>';					

		?>
		<div class='row'>			
			<div class='span3'></div>			
			<div class='span3'>			
				<div class="control-group">				
					<label class="control-label">Ordens de Serviço do período</label>
					<div class="controls"  >
						<select id="ordens-periodo" name="ordens-periodo">
							<option></option>									
						</select>
					</div>									
				</div>
			</div>
			<div class="span3">
				<a class="btn btn-primary btn-small" id="os_press" target="_BLANK" href="<?php echo "os_press.php" ?>" style="margin-top: 23px">Visualiza OS</a>
			</div>
		</div>		
		<?php

		echo $return;
		echo "</br>";

		$mostrar = false;
		foreach ($pesquisaComponentesInputs as $componente) {					
			
			foreach ($questionNo as  $value) {
				if(strtolower($value) == strtolower($componente['pergunta'])){
					
					$mostrar = true;
				}	
			}

			if($mostrar == true){
				$mostrar = false;
				
				if(!in_array($questionNo, strtolower($componente['pergunta']))){

					if($componente['tipo'] == 'radio'){

						$sql = "select tipo_resposta_item,descricao from tbl_tipo_resposta_item where tipo_resposta = ".$componente['tipo_resposta'];				
						$res = pg_query($con,$sql);
						$itens = pg_fetch_all($res);				

						$options = array("selectOptions" => $itens);

						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo'],$options);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php

					}else{


						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo']); 

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php
					}					
				}			 	
			}else{
				$mostrar = false;

			 }
		}

		$sql = "select tbl_pergunta.pergunta as idpergunta, tbl_pergunta.descricao as pergunta, tbl_tipo_resposta.tipo_descricao as tipo, 
		tbl_tipo_resposta.tipo_resposta,tbl_tipo_resposta.descricao as descricao_tipo_resposta
		from tbl_pesquisa join tbl_pesquisa_pergunta on tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa  
		join tbl_pergunta on tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta 
		join tbl_tipo_resposta on tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta 
		where tbl_pesquisa.fabrica = $login_fabrica and tbl_tipo_resposta.tipo_descricao = 'radio' and to_ascii(upper(tbl_tipo_resposta.descricao),'LATIN9') = to_ascii(upper('Satisfação'),'LATIN9') order by tbl_tipo_resposta.tipo_resposta,tbl_pesquisa_pergunta.ordem ;";
//		echo $sql;exit;
		
		$res = pg_query($con,$sql);


		$pesquisaComponentes = pg_fetch_all($res);		
		
		$tipoAux = $pesquisaComponentes[0]['tipo_resposta'];

		
		$radioTable = array();
		for ($i=0; $i < count($pesquisaComponentes); $i++) { 			
			$radioTable[] = $pesquisaComponentes[$i]; 	
		}

		$return = makeRadioTable($radioTable,$login_fabrica,$con);

		echo "<br>";	
		echo '<div class="titulo_tabela ">Quadro a ser informado Pelo Posto Autorizado</div>';
		echo $return;
		echo "</br>";


		$mostrar = false;
		foreach ($pesquisaComponentesInputs as $componente) {					
			
			foreach ($questionNo2 as  $value) {
				if(strtolower($value) == strtolower($componente['pergunta'])){
					
					$mostrar = true;
				}	
			}

			if($mostrar == true){
				$mostrar = false;
				
				if(!in_array($questionNo, strtolower($componente['pergunta']))){

					if($componente['tipo'] == 'radio'){

						$sql = "select tipo_resposta_item,descricao from tbl_tipo_resposta_item where tipo_resposta = ".$componente['tipo_resposta'];				
						$res = pg_query($con,$sql);
						$itens = pg_fetch_all($res);				

						$options = array("selectOptions" => $itens);

						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo'],$options);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php

					}else{						
						
						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo']);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php
					}					
				}			 	
			}else{
				$mostrar = false;

			 }
		}





		$sql = "select tbl_pergunta.pergunta as idpergunta, tbl_pergunta.descricao as pergunta, tbl_tipo_resposta.tipo_descricao as tipo, 
		tbl_tipo_resposta.tipo_resposta,tbl_tipo_resposta.descricao as descricao_tipo_resposta
		from tbl_pesquisa join tbl_pesquisa_pergunta on tbl_pesquisa.pesquisa = tbl_pesquisa_pergunta.pesquisa  
		join tbl_pergunta on tbl_pesquisa_pergunta.pergunta = tbl_pergunta.pergunta 
		join tbl_tipo_resposta on tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta 
		where tbl_pesquisa.fabrica = $login_fabrica and tbl_tipo_resposta.tipo_descricao = 'radio' and to_ascii(upper(tbl_tipo_resposta.descricao),'LATIN9') ilike(to_ascii(upper('Sim,%'),'LATIN9')) order by tbl_tipo_resposta.tipo_resposta,tbl_pesquisa_pergunta.ordem ;";


		
		$res = pg_query($con,$sql);

		$pesquisaComponentes = pg_fetch_all($res);		
		
		
		$tipoAux = $pesquisaComponentes[0]['tipo_resposta'];

		
		$radioTable = array();
		for ($i=0; $i < count($pesquisaComponentes); $i++) { 			
			$radioTable[] = $pesquisaComponentes[$i]; 	
		}

		$return = makeRadioTable($radioTable,$login_fabrica,$con);
		
		echo "<br>";			
		echo '<div class="titulo_tabela ">Prováveis causas de defeitos nos produtos</div>';
		echo $return;
		echo "</br>";



		$pular = false;

		foreach ($pesquisaComponentesInputs as $componente) {			
			
			foreach ($questionNo as  $value) {
				if(strtolower($value) == strtolower($componente['pergunta'])){
					$pular = true;
				}	
			}

			foreach ($questionNo2 as  $value) {
				if(strtolower($value) == strtolower($componente['pergunta'])){
					$pular = true;
				}				
			}

			foreach ($questionQuadroFunc as  $value) {
				if(strtolower($value) == strtolower($componente['pergunta'])){
					$pular = true;
				}	
			}

			if($pular == true){
			 	$pular = false;			 	
			}else{
				if(!in_array($questionNo, strtolower($componente['pergunta']))){

					if($componente['tipo'] == 'radio'){

						$sql = "select tipo_resposta_item,descricao from tbl_tipo_resposta_item where tipo_resposta = ".$componente['tipo_resposta'];				
						$res = pg_query($con,$sql);
						$itens = pg_fetch_all($res);				

						$options = array("selectOptions" => $itens);

						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo'],$options);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php

					}else{


						$return = makeComponent($componente['idpergunta'],$componente['pergunta'],$componente['tipo']);

						?>
						<div class="row">
							<div class='span2'></div>	
							<?php 
								echo $return;
							?>
							<div class='span2'></div>	
						</div>
						<?php
					}
					
				}


			 }

			

			
		}

		// if(count($radioTable) > 0){
		// 	echo '<div class="titulo_tabela ">Maior índice de defeito no parecer tecnico</div>';					
		// 	$return = makeRadioTable($radioTable,$login_fabrica,$con);
		// 	echo $return;
		// 	echo "</br>";
		// }
		

	?>

	<div class="row">
		<div class="span10">&nbsp</div>
	</div>
	<div class="row">
		<div class='span1'></div>	
		<div class='span8 tac'>
			<div class="control-group">				
				<div class="controls tac">
					<label class="control-label"><b>Avaliação Final<b></label>			
					<div class="radio tac">
					 	<label>
					    	<input type="radio" name="ck-avaliacao" id="ck-aprovado" value="aprovado" >
					    	Aprovado
					  	</label>
					  	<label>
					    	<input type="radio" name="ck-avaliacao" id="ck-aprovado-parcial" value="aprovado-parcial" >
					    	Aprovado parcial
					  	</label>
					  	<label>
					    	<input type="radio" name="ck-avaliacao" id="ck-reprovado-descredenciamento" value="reprovado-descredenciamento">
					    	Reprovado/Descredenciamento
					  	</label>
					</div>										
				</div>
			</div>				
		</div>			
		<div class='span2'></div>	
	</div>
	<div class="row">
		<div class="span10">&nbsp</div>
	</div>
	<div class="row">
		<div class='span1'></div>	
		<div class='span8 tac'>			
			<input type="hidden" name="arquivo_1" id="arquivo_1" value="">
			<input type="hidden" name="arquivo_2" id="arquivo_2" value="">

			<input type="submit" style="display:none" class="btn" name="gravar_inspecao_s" id="gravar_inspecao_s" value="Enviar Inspeção">						
			<!-- <input type="button" class="btn btn-danger" value="Limpar formulario"> -->
		</div>			
		<div class='span2'></div>	
	</div>
	<div class="row">
		<div class="span10">&nbsp</div>
	</div>

</form>


<div class="row">
	<div class='span2'></div>		
	<div class='span4'>
		<div class="control-group">							
			<div class="controls">
				<form id="file_form_1" name="file_form" action="inspecao_suggar_temp_upload.php" method="post" enctype="multipart/form-data">				        
			        <!-- <input type="file" id="arq1" name="arq1" value=''> 4
			        <input form="file_form_1" type="hidden" name="nome_arquivo" value="<?php echo $imagem_upload."_1"; ?>">			        
			        <input type="hidden" name="i" value="1">
			        <span id="loading_1" style="display:none">Uploading...</span>
			        <img src="" id="img_1"/> -->
			    </form>
				
			</div>									
		</div>
	</div>		
	<div class='span4'>
		<div class="control-group">				
			
			<div class="controls">				
				<form id="file_form_2" name="file_form" action="inspecao_suggar_temp_upload.php" method="post" enctype="multipart/form-data">				        
			        <!-- <input type="file" id="arq2" name="arq2" value=''> 
			        <input type="hidden" name="nome_arquivo" value="<?php echo $imagem_upload."_2"; ?>">
			        <input type="hidden" name="i" value="2">
			        <span id="loading_2" style="display:none">Uploading...</span>
			        <img src="" id="img_2"/> -->
			    </form>
			</div>									
		</div>
	</div>		
	<div class='span2'></div>		
</div>

<div class="row">
	<div class="span10">&nbsp</div>
</div>

<div class="row">
	<div class='span1'>

	</div>		
	<div class='span8 tac'>
		<input type="button" class="btn" name="gravar_inspecao" id="gravar_inspecao" value="Enviar Inspeção">
		<input type="hidden" name="anexo_1">
		<input type="hidden" name="anexo_2">
	</div>	
	<div class='span2'>

	</div>
</div>

<div class="row">
	<div class="span10">&nbsp</div>
</div>


<script type="text/javascript">

var os_periodo = "";

$(function() {
    $("#data_incial_atende").datepicker().mask("99/99/9999");
    $("#data_final_atende").datepicker().mask("99/99/9999");
    $("#data_elaboracao").datepicker().mask("99/99/9999");

    $('#gravar_inspecao').click(function(){    	

    	if($("input[name=tipo_inspecao]:checked").val() == 'auditoria' || $("input[name=tipo_inspecao]:checked").val() == ""){
    		var campos_vazios = false;
	    	$('input[type=text]').each(function(){
	    		
	    		if($(this).val() == ""){
	    			if($(this).attr('name') != "endereco_v" && $(this).attr('name') != "endereco" && $(this).attr('name') != "fone_v" && $(this).attr('name') != "fone" && $(this).attr('name') != "cidade_estado_v" && $(this).attr('name') != "cidade_estado" && $(this).attr('name') != "email_v" && $(this).attr('name') != "email" && $(this).attr('name') != "data_credenciamento_v" && $(this).attr('name') != "data_credenciamento" && $(this).attr('name') != "contato_v" && $(this).attr('name') != "contato"){
	    				$(this).select();
	    				campos_vazios = true;    			
	    			}
	    		}
	    	});
	    	$('textarea').each(function(){

	    		if($(this).val() == ""){
	    			$(this).select();
	    			campos_vazios = true;    			
	    			
	    		}
	    	});


	    	if($('input[type=checkbox]:checked').length == 0){
	    		campos_vazios = true;
	    	}


	    	var radio;
			$('input[type=radio]').each(function(){
				if(radio != $(this).attr('name')){			   
				   radio = $(this).attr('name');

				   if($("input[name="+radio+"]:checked").length == 0){			   
				   	  campos_vazios = true;
				      //console.log($(this).attr('name'));
				   }
				}
			});

	    	if(campos_vazios == false){    		
	    		$('#gravar_inspecao_s').click();
	    	}else{
	    		alert("Preencha todos os campos por favor");    			
	    	}	
    	}else{
    		campos_vazios = false;

//    		console.log($('#codigo_posto').val());
    		if($('#codigo_posto').val() == ""){
    			campos_vazios = true;
    			campo = "Código do Posto";
    		}else{
    			if($('input[type=checkbox]:checked').length == 0){
	    			campos_vazios = true;
	    			campo = "linhas";
	    		}
    		}

    		if($('#data_elaboracao').val() == ""){
    			campos_vazios = true;
    			campo = "Data Elaboração";
    		}

    		if(($("#data_incial_atende").val() == "" || $("#data_final_atende").val() == "") && campos_vazios == false){
    			campos_vazios = true;
				campo = "data inicial e data final da pesquisa";
    		}

    		if(campos_vazios == false){    		
	    		$('#gravar_inspecao_s').click();
	    	}else{
	    		alert("Preencha o campo "+campo);    			
	    	}	
    	}
    });


    $("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	Shadowbox.init();


	$("#btn_pesquisa").click(function(){
		
		var data_inicial = $("#data_incial_atende").val();
		var data_final = $("#data_final_atende").val(); 
		var codigo_posto = $("#codigo_posto").val();
		var nome_posto = $("#descricao_posto").val();

		if(data_inicial != "" && data_final != "" && codigo_posto != "" && nome_posto != ""){			
			
			$("#message_busca").fadeIn('500');
			$.ajax({
				url: "<?php echo $PHP_SELF; ?>",
				type: "POST",
				data: {
						data_inicial: data_inicial, 
						data_final: data_final,
						codigo_posto: codigo_posto,
						nome_posto: nome_posto,
						"ajax_pesquisa": true
					}}				
				).done(function(retorno){
					
					retorno = JSON.parse(retorno);					
					if(retorno.status == 'success'){

						$("#qtd_os_periodo").val(retorno.qtd_os);
						$("#qtd_pecas_periodo").val(retorno.qtd_pecas_periodo);
						$("#qtd_reclamacoes_periodo").val(retorno.qtd_reclamacoes_periodo);
						$("#qtd_comunicaados_periodo").val(retorno.qtd_comunicaados_periodo);

						os_periodo = retorno.qtd_os_periodo;

						 for(i=0; i < os_periodo.length ; i++){
						 	$('#ordens-periodo').append('<option value="'+os_periodo[i].os+'">'+os_periodo[i].sua_os+'</option>');
						 }


						$("#message_busca").fadeOut('2000');
					}else{
						if(retorno.message != undefined){
							alert(retorno.message);
						}						
						$("#message_busca").fadeOut('2000');
					}
				});
				 
		}else{
			alert("Preencha todos campos para realizar a consulta");
		}
	});

	$("#btn_limpa_pesquisa").click(function(){
		$("#data_incial_atende").val("");
		$("#data_final_atende").val(""); 
		$("#codigo_posto").val("");
		$("#descricao_posto").val("");
	});

	$('#ordens-periodo').change(function(){
		$('#os_press').attr('href','os_press.php?os='+$('#ordens-periodo').val());
	});


	$("#arq1").change(function(){
		$("#loading_1").fadeIn('1000');
		$("#file_form_1").submit();
		
	});

	$("#arq2").change(function(){	
		$("#loading_2").fadeIn('1000');	
		$("#file_form_2").submit();
	});

	$("form[name=file_form]").ajaxForm({
		beforeSend: function(){
			console.log("Sending");
		},
        complete: function(data) {
        	console.log("Return");
            if (data.responseText == "erro") {
                alert("Arquivo inválido, selecione outro arquivo!");                
            } else {
                data = $.parseJSON(data.responseText);

                if(data.i == '1'){
                	$('#img_1').attr('src',data.file);
                	$('#arquivo_1').val(data.nome);
                	$("#loading_1").fadeOut('1000');
                }else{
                	$('#img_2').attr('src',data.file);
                	$('#arquivo_2').val(data.nome);
                	$("#loading_2").fadeOut('1000');
                }

                console.log(data);               
            }


        }
    });
});






function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);

	setTimeout(function(){

	// 	alert("hey");
		$.post("<?php echo $PHP_SELF ?>",{ajax_posto: true,codigo_posto: retorno.codigo},function(data){
			retorno = JSON.parse(data);					

			if(retorno.status == "success"){
				$('#endereco').val(retorno.endereco);
				$('#endereco_v').val(retorno.endereco);
				$('#telefone').val(retorno.fone);
				$('#telefone_v').val(retorno.fone);
				$('#cidade_estado').val(retorno.cidade);
				$('#cidade_estado_v').val(retorno.cidade);
				$('#email').val(retorno.email);
				$('#email_v').val(retorno.email);
				$('#data_credenciamento').val(retorno.credenciamento);
				$('#data_credenciamento_v').val(retorno.credenciamento);
				$('#contato').val(retorno.contato);
				$('#contato_v').val(retorno.contato);

				$("#ordens-periodo").html("");
				$("#qtd_os_periodo").val("");
				$("#qtd_pecas_periodo").val("");
				$("#qtd_reclamacoes_periodo").val("");
				$("#qtd_comunicaados_periodo").val("");

			}else{
				alert('Informações do posto não encontradas')
			}
		});
		// $.ajax({
	 //  		// url: "<?php echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'] ?>",
		//   		url: "http://www.telecontrol.com",
		//   		type: "POST",
		//   		data: {
		//   			codigo_posto: codigo_posto,
		//   			"ajax_posto": true
		//   		},
		//   		complete: function(data){
		//   			alert("*");
		//   		}
	 //  		}				
	 //  	);
	 },1000);
}

</script>


<?php
include "rodape.php" 
?>
