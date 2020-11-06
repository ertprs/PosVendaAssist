<?php


// print_r($_POST);exit;
//HD 706810 SEGUNDO O HD 424887, a fricon deveria ficar sem integridade de defeito_reclamado.
//E foi esquecido de colocar nesta tela também

$fabricas_defeito_reclamado_sem_integridade = array();
$fabricas_validam_campos_obrigatorios = array(81,114,125);
//HD 706810 END


include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';


//HD 235203: Alterar assuntos Fale Conosco e CallCenter
//			 É no arquivo abaixo que é definido o array $assuntos
include_once("callcenter_suggar_assuntos.php");
include_once '../class/email/mailer/class.phpmailer.php';
$mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor



if($login_fabrica == 85 AND strlen($_GET['os']) AND strlen($_GET['callcenter']) > 0 ){
	$xos = $_GET['os'];
}



if (isset($_GET['ajax']) && isset($_GET['hdanterior']) && isset($_GET['resp'])) {//HD 277105

	if ($_GET['ajax'] != 'sim' || empty($_GET['hdanterior']) || empty($_GET['resp']) )
		return 'Erro com os parâmetros. Verifique os dados na url.';

	require_once '../helpdesk.inc.php';
	$aRespostas = hdBuscarRespostas($_GET['hdanterior'], false, true);

	if (!empty($aRespostas)) {

		$sql = 'SELECT categoria FROM tbl_hd_chamado
				WHERE hd_chamado = ' . $_GET['hdanterior'] . ' AND fabrica = ' . $login_fabrica . 'LIMIT 1';

		$res         = pg_query($con, $sql);
		$assunto_cat = pg_result($res, 0, 'categoria');

		foreach ($assuntos as $categoria_assunto => $itens_categoria_assunto) {

			foreach ($itens_categoria_assunto as $label_assunto => $bd_assunto) {

				if ($bd_assunto == $assunto_cat) {
					$categoria               = $label_assunto;
					$achou_categoria_assunto = true;
				}

			}

		}

		echo '<tr class="resp'.$_GET['resp'].'"  bgcolor="#A0BFE0"><td colspan="4"><b>Assunto: ' . $categoria . '</b></td></tr>';


		foreach ($aRespostas as $iResposta=>$aResposta): ?>
			<tr class="resp<?=$_GET['resp']?>" bgcolor="#A0BFE0">
				<td colspan="4">
								Resposta <strong><?php echo $iResposta + 1; ?></strong>
								Por <strong><?php echo ( ! empty($aResposta['atendente']) ) ? $aResposta['atendente'] : $aResposta['posto_nome'] ; ?></strong>
								em <?php echo $aResposta['data']; ?> </td>
				</td>
			</tr>

			<?php if ( $aResposta['interno'] == 't' ): ?>
			<? if($login_fabrica != 74){ ?>
				<tr class="resp<?=$_GET['resp']?>">
					<td align="center" bgcolor="#EFEBCF" colspan="4"> Chamado Interno </td>
				</tr>
			<? } ?>
			<?php endif; ?>
			<?php if ( in_array($aResposta['status_item'],array('Cancelado','Resolvido')) ): ?>
			<tr class="resp<?=$_GET['resp']?>">
				<td align="center" colspan="4" bgcolor="#EFEBCF" > <?php echo $aResposta['status_item']; ?> </td>
			</tr>
			<?php endif; ?>
			<tr class="resp<?=$_GET['resp']?>">
				<td align="left" colspan="4" bgcolor="#FFFFFF" style="border-bottom:1px solid black;"> <?php echo nl2br($aResposta['comentario']); ?> </td>
			</tr><?php
		endforeach;
	}
	else
		echo '<tr class="resp'.$_GET['resp'].' "><td colspan="4">Não foram feitas Interações nesse Chamado<td></td></tr>';
	return;

}

if(isset($_POST['ajax-estado'])){

	$estado = $_POST['ajax-estado'];

	$sql = "
		SELECT DISTINCT(tbl_ibge.cidade) AS cidade, tbl_ibge.cod_ibge AS cod
		FROM tbl_ibge
		JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge AND tbl_posto_fabrica_ibge.fabrica = $login_fabrica
		WHERE tbl_ibge.estado = '$estado'
		ORDER BY tbl_ibge.cidade
	";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)){

		$est = "<option value=''></option>";

		while($data = pg_fetch_object($res)){
			$est .= "<option value='".$data->cod."'>".$data->cidade."</option>";
		}

		echo $est;

	}else{
		echo "<option value=''>Nenhuma cidade localiza</option>";
	}

	exit;
}

if(isset($_POST['ajax-cidade'])){

	$cidade = $_POST['ajax-cidade'];
	 if(isset($_POST['bairro']) || isset($_POST['bairro_escolhido'])){
	 	$campoAdd = ",tbl_posto_fabrica_ibge.bairro ";
	 }else{
	 	$campoAdd = "";
	 }

	$orderby = (isset($_POST['bairro_escolhido'])) ? "nome_posto" : "tbl_posto_fabrica_ibge.bairro";
	$sql = "
		SELECT tbl_posto.nome AS nome_posto, tbl_posto.posto AS cod_posto, tbl_posto.cidade AS cidade $campoAdd
		FROM tbl_ibge
		JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge AND tbl_posto_fabrica_ibge.fabrica = $login_fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica_ibge.posto
		WHERE tbl_ibge.cod_ibge = '$cidade'
		ORDER BY $orderby";

	$res = pg_query($con, $sql);

	if(pg_num_rows($res)){

		$est = "<option value=''></option>";

		if($login_fabrica == 74){
			if(isset($_POST['bairro'])){
				$arrayBairros = array();
				$auxBairros = array();
				while($data = pg_fetch_object($res)){
					if($data->bairro != ""){
						$arrayBairro[$data->cod_posto] = json_decode($data->bairro);
						$arrayBairros = array_merge($arrayBairros,$arrayBairro[$data->cod_posto]);

					}
				}

				$est .= "<option  value='todos'>Todos bairros</option>";
				sort($arrayBairros);
				foreach ($arrayBairros as $bairro) {
					if(!empty($bairro)){
						if(!in_array($bairro, $auxBairros)){
							$auxBairros[] = $bairro;
							$est .= "<option  value='".md5(trim($bairro))."'>".utf8_decode($bairro)."</option>";
						}
					}
				}
				echo $est;exit;


			}elseif(isset($_POST['bairro_escolhido'])){
				$bairroEscolhido = $_POST['bairro_escolhido'];
				if($bairroEscolhido == "todos"){

					while($data = pg_fetch_object($res)){
						if(strlen($data->cidade) == 0){
							$data->cidade = "Cidade não informada";
						}

						$est .= "<option bairro='".$data->bairro."' value='".$data->cod_posto."'>".$data->nome_posto." - ".$data->cidade."</option>";
					}
				}else{

					$resPostos = pg_fetch_all($res);
					sort($resPostos);



					foreach ($resPostos as $posto) {
						if($posto['bairro'] != ""){
							$arrayBairros[$posto['cod_posto']] = json_decode($posto['bairro']);
						}
					}

					$postos = array();
					foreach ($arrayBairros as $posto => $bairros) {
						for($i=0;$i<count($bairros);$i++){
							if($bairroEscolhido == md5(trim($bairros[$i]))){

								$postos[] = $posto;
							}
						}
					}

					for($i=0;$i<count($resPostos);$i++){
						if(in_array($resPostos[$i]['cod_posto'],$postos)){
							if(strlen($resPostos[$i]['cidade']) == 0){
								$resPostos[$i]['cidade'] = "Cidade não informada";
							}

							$est .= "<option  value='".$resPostos[$i]['cod_posto']."'>".$resPostos[$i]['nome_posto']." - ".$resPostos[$i]['cidade']."</option>";

						}
					}

					// // while($data = pg_fetch_object($res)){
					// // 	if($data->bairro != ""){
					// // 		$arrayBairros[$data->cod_posto] = json_decode($data->bairro);
					// // 	}
					// // }


					// print_r($arrayBairros);exit;
					// foreach ($arrayBairros as $posto => $bairros) {
					// 	for($i=0;$i<count($arrayBairros[$posto]);$i++){
					// 		if($bairroEscolhido == md5(strlen($arrayBairros[$posto][$i]))){
					// 			echo $bairroEscolhido."\n";
					// 			echo md5(strlen($arrayBairros[$posto][$i]))."\n";
					// 			$resPosto[$posto][] = md5(strlen($arrayBairros[$posto][$i]));
					// 		}
					// 		// $arrayBairros[$posto][$i] = md5(strlen($arrayBairros[$posto][$i]));
					// 	}
					// }
					// echo $bairroEscolhido;
					// print_r($resPostos);exit;

					// foreach ($arrayBairros as $posto => $bairros) {
					// 	if(in_array($bairroEscolhido, $arrayBairros[$posto])){
					// 		$arrayPostos[] = $posto;
					// 	}
					// }

					// foreach ($resPostos as $posto) {
					// 	if(strlen($posto['cidade']) == 0){
					// 		$resPostos[$posto]['cidade'] = "Cidade não informada";
					// 	}
					// 	if(in_array($resPostos[$posto]['cod_posto'], $arrayPostos)){
					// 		$est .= "<option  value='".$posto['cod_posto']."'>".$posto['nome_posto']." - ".$posto['cidade']."</option>";
					// 	}

					// }
				}

			}else{
				while($data = pg_fetch_object($res)){
					if(strlen($data->cidade) == 0){
						$data->cidade = "Cidade não informada";
					}

					$est .= "<option bairro='".$data->bairro."' value='".$data->cod_posto."'>".$data->nome_posto." - ".$data->cidade."</option>";
				}
			}

		}else{
			while($data = pg_fetch_object($res)){
				if(strlen($data->cidade) == 0){
					$data->cidade = "Cidade não informada";
				}

				$est .= "<option  value='".$data->cod_posto."'>".$data->nome_posto." - ".$data->cidade."</option>";
			}
		}

		echo $est;

	}else{
		echo "<option value=''>Nenhuma cidade localiza</option>";
	}

	exit;
}

function retira_acentos($texto){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'");
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" ,"");
	return str_replace( $array1, $array2, $texto );
}

if (!empty($_COOKIE['debug'])) $debug = ($_COOKIE['debug']=='true');

if ($login_fabrica == 3) {
	if (strlen($callcenter) > 0) header ("Location:callcenter_interativo_new_britania.php?callcenter=$callcenter");
	else                      	 header ('Location:callcenter_interativo_new_britania.php');
	exit;
}else if($login_fabrica == 5){
	if (strlen($callcenter) > 0) header ("Location:callcenter_interativo_new_mondial.php?callcenter=$callcenter");
	else                         header ('Location:callcenter_interativo_new_mondial.php');
	exit;
}

$fab_usa_tipo_cons = array(51); // HD 317864

if ($_GET["continuar_chamado"] && $_GET["Id"]) {

	$hd_chamado = $_GET["Id"];

	$sql = "SELECT hd_chamado FROM tbl_hd_chamado
			WHERE  hd_chamado = $hd_chamado
			AND    fabrica = $login_fabrica
			AND    fabrica_responsavel = $login_fabrica";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {

		$sql = "BEGIN TRANSACTION";
		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);

		$sql = "INSERT INTO tbl_hd_chamado (
					admin,
					posto,
					titulo,
					status,
					atendente,
					fabrica_responsavel,
					categoria,
					duracao,
					exigir_resposta,
					fabrica,
					empregado,
					orcamento,
					pessoa,
					sequencia_atendimento,
					tipo_chamado,
					cliente,
					cliente_admin,
					hd_chamado_anterior
				) SELECT
					$login_admin,
					posto,
					titulo,
					'Aberto',
					$login_admin,
					fabrica_responsavel,
					categoria,
					duracao,
					exigir_resposta,
					fabrica,
					empregado,
					orcamento,
					pessoa,
					sequencia_atendimento,
					tipo_chamado,
					cliente,
					cliente_admin,
					$hd_chamado
			FROM tbl_hd_chamado
			WHERE hd_chamado = $hd_chamado ";

		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);

		$res = pg_query($con, "SELECT CURRVAL('seq_hd_chamado')");
		$hd_chamado_novo = pg_result($res, 0, 0);
		$msg_erro[] = pg_errormessage($con);

		$sql = "INSERT INTO tbl_hd_chamado_extra (
					hd_chamado,
					reclamado,
					defeito_reclamado,
					serie,
					hora_ligacao,
					produto,
					posto,
					os,
					receber_info_fabrica,
					consumidor_revenda,
					origem,
					revenda,
					revenda_nome,
					data_nf,
					nota_fiscal,
					nome,
					endereco,
					numero,
					complemento,
					bairro,
					cep,
					fone,
					fone2,
					email,
					cpf,
					rg,
					cidade,
					qtde_km,
					abre_os,
					defeito_reclamado_descricao,
					numero_processo,
					tipo_registro,
					celular,
					revenda_cnpj,
					atendimento_callcenter,
					contato_nome,
					marca
			) SELECT $hd_chamado_novo,
				reclamado,
				defeito_reclamado,
				serie,
				hora_ligacao,
				produto,
				posto,
				os,
				receber_info_fabrica,
				consumidor_revenda,
				origem,
				revenda,
				revenda_nome,
				data_nf,
				nota_fiscal,
				nome,
				endereco,
				numero,
				complemento,
				bairro,
				cep,
				fone,
				fone2,
				email,
				cpf,
				rg,
				cidade,
				qtde_km,
				'f',
				defeito_reclamado_descricao,
				numero_processo,
				tipo_registro,
				celular,
				revenda_cnpj,
				atendimento_callcenter,
				contato_nome,
				marca
		FROM tbl_hd_chamado_extra
		WHERE hd_chamado=$hd_chamado ";
		 //echo nl2br($sql);exit;
		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);

		if ($login_fabrica != 24) {

			$sql = "INSERT INTO tbl_hd_chamado_item (
						hd_chamado,
						data,
						comentario,
						admin,
						posto,
						interno,
						status_item,
						empregado,
						pessoa,
						termino,
						tempo_interacao,
						enviar_email,
						atendimento_telefone,
						produto,
						serie,
						defeito_reclamado
					) SELECT
						$hd_chamado_novo,
						data,
						comentario,
						admin,
						posto,
						interno,
						'Aberto',
						empregado,
						pessoa,
						termino,
						tempo_interacao,
						enviar_email,
						atendimento_telefone,
						produto,
						serie,
						defeito_reclamado
				FROM tbl_hd_chamado_item
				WHERE hd_chamado = $hd_chamado ";

			$res = pg_query($con, $sql);
			$msg_erro[] = pg_errormessage($con);

		}

		$msg_erro = implode("", $msg_erro);

		if (strlen($msg_erro)) {

			$sql = "ROLLBACK TRANSACTION";
			$res = pg_query($con, $sql);
			header("location:" . $PHP_SELF);
			die;

		} else {

			$sql = "COMMIT TRANSACTION";
			$res = pg_query($con, $sql);
			header("location:" . $PHP_SELF . "?callcenter=$hd_chamado_novo");
			die;

		}

	} else {

		header("location:" . $PHP_SELF);
		die;

	}

}

//AJAX PARA RETORNAR O GRID COM AS PESQUISAS DE SATISFACAO DA FRICON - HD 925147
if (isset($_GET['showPesquisa']) and $login_fabrica == 52) {

	$pesquisa = $_GET['pesquisa'];
	$hd_chamado = $_GET['hd_chamado'];

	/*PEGA O TEXTO DE AJUDA na tbl_pesquisa.texto_ajuda*/
	$sql = "SELECT tbl_pesquisa.texto_ajuda FROM tbl_pesquisa WHERE pesquisa = $pesquisa and fabrica=$login_fabrica";
	$res = pg_query($con,$sql);
	$texto_ajuda = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,0) : '' ;

	$sql = "SELECT  tbl_pesquisa_pergunta.ordem,
			tbl_pergunta.pergunta,
			tbl_pergunta.descricao,
			tbl_pergunta.tipo_resposta,
			tbl_tipo_resposta.tipo_descricao,
			tbl_pesquisa.pesquisa
		FROM tbl_pesquisa_pergunta

		INNER JOIN tbl_pergunta using(pergunta)
		INNER JOIN tbl_pesquisa using(pesquisa)
		LEFT JOIN tbl_tipo_resposta on (tbl_pergunta.tipo_resposta = tbl_tipo_resposta.tipo_resposta)

		WHERE tbl_pesquisa.pesquisa = $pesquisa
		and tbl_pesquisa.fabrica = $login_fabrica
		and tbl_pergunta.ativo is true ORDER BY tbl_pesquisa_pergunta.ordem";

	$res = pg_query($con,$sql);

	$html_pesquisa .= '	<script
						<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
						<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
						<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
						<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" />
						<script>
						$(function() {

							$(".date").datepick({startDate:"01/01/2000"});
							$(".date").mask("99/99/9999");

						});
						</script>';

	$html_pesquisa .= '<table width="900px" class="tabela table_perguntas_fricon_pesquisa" border="0" cellpadding="2" cellspacing="2" style="margin:auto;background-color: #F4E6D7;font-size:10px" >';
	//exibe o texto de ajuda
	$html_pesquisa .= '
			<tr>
				<td colspan="100%">
					'.nl2br($texto_ajuda).'
				</td>
			</tr>';
	if (pg_num_rows($res)>0) {

		$i = 0;
		$respostasPergunta = array();
		//percorre o array da consulta principal 1ª vez para jogar as respostas em um array
		foreach (pg_fetch_all($res) as $key) {
			$sql = "SELECT pergunta, txt_resposta,tipo_resposta_item
				FROM tbl_resposta
				WHERE pergunta = {$key['pergunta']}
				AND pesquisa = {$pesquisa}
				and hd_chamado = $hd_chamado
				ORDER BY pergunta";
			$resRespostas = pg_query($con,$sql);

			if (pg_num_rows($resRespostas)>0) {
				foreach (pg_fetch_all($resRespostas) as $keyRespostas) {
					if (!empty($keyRespostas['tipo_resposta_item'])) {

						$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][] = $keyRespostas['tipo_resposta_item'];

					}else{
						$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][] = $keyRespostas['txt_resposta'];
					}
				}
			}
		}
		//percorre a segunda vez para montar o formulário
		foreach (pg_fetch_all($res) as $key) {

			$cor = ($i % 2) ? "#E4E9FF" : "#F3F3F3";

			$html_pesquisa .= "
					<tr bgcolor='$cor'>
						<td style='text-align:center;padding: 0px 10px 0px 10px' >
							<input type='hidden' name='perg_".$i."' value='".$key['pergunta']."' placeholder=''>
							<input type='hidden' name='hidden_$i' value='".$key['tipo_descricao']."' >
							<label > ".$key['ordem']." </label>
						</td>
						<td  align='left' nowrap  style='text-align:justify;padding: 0px 10px 0px 10px' >
							".$key['descricao']."
						</td>";

			if (!empty($key['tipo_resposta'])) {

				$sql = "SELECT tbl_tipo_resposta_item.descricao,
						tbl_tipo_resposta.label_inicio,
						tbl_tipo_resposta.label_fim,
						tbl_tipo_resposta.label_intervalo,
						tbl_tipo_resposta.tipo_descricao,
						tbl_tipo_resposta_item.tipo_resposta_item
					FROM tbl_tipo_resposta
					LEFT JOIN    tbl_tipo_resposta_item using(tipo_resposta)
					WHERE tbl_tipo_resposta.tipo_resposta = ".$key['tipo_resposta']."
					AND tbl_tipo_resposta.fabrica = $login_fabrica
					ORDER BY tbl_tipo_resposta_item.ordem ";

				$res = pg_query($con,$sql);
				if (pg_num_rows($res)>0) {
					for ($x=0; $x < pg_num_rows($res); $x++) {

						if (!empty($respostasPergunta)) {
							$disabled = 'disabled="DISABLED"';
						}

						$item_tipo_resposta_desc  = pg_fetch_result($res, $x, 'descricao');
						$item_tipo_resposta_tipo   = pg_fetch_result($res, $x, 'tipo_descricao');
						$item_tipo_resposta_label_inicio = pg_fetch_result($res, $x, 'label_inicio');
						$item_tipo_resposta_label_fim    = pg_fetch_result($res, $x, 'label_fim');
						$item_tipo_resposta_label_intervalo    = pg_fetch_result($res, $x, 'label_intervalo');
						$tipo_resposta_item_id    = pg_fetch_result($res, $x, 'tipo_resposta_item');

						if (in_array($item_tipo_resposta_tipo, array('checkbox','radio'))) {
							$colspan = "";
							$width = "";
						}else{
							$colspan = "100%";
						}

						$html_pesquisa .= '<td align="center" nowrap colspan="'.$colspan.'" >';

						if ($item_tipo_resposta_tipo == 'radio' or $item_tipo_resposta_tipo == 'checkbox') {
							$value_resposta = $tipo_resposta_item_id;
						}else{
							$value_resposta = $item_tipo_resposta_desc;
						}

						switch ($item_tipo_resposta_tipo) {

							case 'radio':


								$html_pesquisa .= $item_tipo_resposta_desc;
								$value_resposta = $tipo_resposta_item_id;

								if (is_array($respostasPergunta) and !empty($respostasPergunta)) {

									if (in_array($tipo_resposta_item_id,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
										$checked_radio = "checked='CHECKED'";
									}

								}


								$html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"  class="frm" value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

								break;

							case 'text':

								$item_tipo_resposta_desc = $key['txt_resposta'];
								$disabled_resposta = "disabled='DISABLED'";
								$value_resposta = $item_tipo_resposta_desc;
								if (is_array($respostasPergunta) and !empty($respostasPergunta)){
									if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
										$value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
									}
								}
								$html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"  class="frm" value="'.$value_resposta.'" '.$disabled.' />';

								break;

							case 'range':

								$value_resposta = $item_tipo_resposta_desc;

								for ($z=$item_tipo_resposta_label_inicio; $z <= $item_tipo_resposta_label_fim ; $z+=$item_tipo_resposta_label_intervalo) {

									if (is_array($respostasPergunta) and !empty($respostasPergunta)){
										if (in_array($z,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
											$checked_radio = "checked='CHECKED'";
										}else{
											$checked_radio = "";
										}
									}

									$html_pesquisa .= $z.' <input type="radio" name="perg_opt'.$key['pergunta'].'" value="'.$z.'" '.$checked_radio.$disabled.' /> &nbsp; &nbsp;';

								}

								break;

							case 'checkbox':

								$html_pesquisa .= $item_tipo_resposta_desc;
								$value_resposta = $tipo_resposta_item_id;
								if (is_array($respostasPergunta) and !empty($respostasPergunta)){
									if (in_array($tipo_resposta_item_id,$respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
										$checked_radio = "checked='CHECKED'";
									}
								}
								$html_pesquisa .= ' <input  type="'.$item_tipo_resposta_tipo.'"  style="width:'.$width.'" name="perg_opt_checkbox_'.$key['pergunta'].'_'.$i.'_'.$value_resposta.'"  class="frm" value="'.$value_resposta.'" '.$checked_radio.$disabled.' />';

								break;

							case 'textarea':

								if (is_array($respostasPergunta) and !empty($respostasPergunta)){
									if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
										$value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
									}
								}
								$html_pesquisa .= ' <textarea name="perg_opt'.$key['pergunta'].'" class="frm" '.$disabled.' style="width:90%" >'.$value_resposta.'</textarea> ';
								break;

							case 'date':

								if (is_array($respostasPergunta) and !empty($respostasPergunta)){
									if (!empty($respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'])) {
										$value_resposta = $respostasPergunta[$key['pesquisa']][$key['pergunta']]['respostas'][0];
									}
								}

								$width="";
								$html_pesquisa .= ' <input  type="text"  style="width:'.$width.'" name="perg_opt'.$key['pergunta'].'"  class="frm date" value="'.$value_resposta.'" '.$disabled.' />';

								break;

							default:

								break;

						}

						$html_pesquisa .= '</td>';
						unset($checked_radio);
					}

				}

			}else{
				$html_pesquisa .= "<td colspan='3'>&nbsp; </td>";
			}

			$html_pesquisa .= "
					</tr>";
			$i++;



		}

	}

	if (is_array($respostasPergunta) and empty($respostasPergunta)) {
		$html_pesquisa .= '<tr><td colspan="100%">
			<input type="hidden" name="qtde_perg" value="'.$i.'">
			<input type="button" value="Gravar" class="btn_grava_pesquisa_fricon" rel="'.$pesquisa.'">
			<div class="td_btn_gravar_pergunta"></div>
		</td></tr>';

	}
	$html_pesquisa .= "</table>";
	echo $html_pesquisa;
	exit();
}
//HD 895693
if ($_GET['ajax'] && $_GET['gravaPerguntasFricon'] && $login_fabrica == 52){

	$erro          = array();
	$hdChamado     = $_GET['hdChamado'];
	$qtde_perg     = $_GET['qtde_perg'];
	$pesquisa      = $_GET['pesquisa'];
	$arrayCheckbox = array();

	foreach ($_GET as $keyPost => $valuePost) {

		$keyExplode = explode("_",$keyPost);

		if($keyExplode[2]=='checkbox'){
			$arrayCheckbox[$keyExplode[3]][] = $keyExplode[5];

		}

	}

	$res = pg_query($con,'BEGIN');

	for ($i=0; $i < $qtde_perg; $i++) {

		$pergunta = $_GET['perg_'.$i];
		$tipo_resposta = $_GET['hidden_'.$i];

		$resposta = (isset($_GET['perg_opt'.$pergunta])) ? utf8_decode(trim($_GET['perg_opt'.$pergunta])) : '';

		if (in_array($tipo_resposta, array('text','range','textarea','date'))) {

			$txt_resposta = $resposta;
			$resposta = 'null';

		}

		if ( is_array($arrayCheckbox[$pergunta]) and $tipo_resposta == 'checkbox' and count($arrayCheckbox[$pergunta])>0 ) {

			foreach ($arrayCheckbox[$pergunta] as $value) {
				$resposta = $value;

				$sqlItens = "SELECT tbl_tipo_resposta_item.descricao FROM tbl_tipo_resposta_item where tipo_resposta_item = ".$value;
				$resItens = pg_query($con,$sqlItens);

				if (pg_num_rows($resItens)>0) {

					$txt_resposta = pg_fetch_result($resItens,0,0);

				}else{
					$txt_resposta = '';
				}

				$sql = "INSERT INTO tbl_resposta (
						pergunta,
						hd_chamado,
						txt_resposta,
						tipo_resposta_item,
						pesquisa,
						admin,
						data_input
					)VALUES(
						$pergunta,
						$hdChamado,
						'$txt_resposta',
						$resposta,
						'$pesquisa',
						$login_admin,
						current_timestamp
					)
					";
				$res = pg_query($con,$sql);

			}
			continue ;
		}

		if (!empty($resposta) and $resposta != 'null') {

			$sqlItens = "SELECT tbl_tipo_resposta_item.descricao FROM tbl_tipo_resposta_item where tipo_resposta_item = $resposta";
			$resItens = pg_query($con,$sqlItens);

			if (pg_num_rows($resItens)>0) {

				$txt_resposta = pg_fetch_result($resItens,0,0);

			}else{

				$txt_resposta = $resposta;

			}

		}
		$sql = "INSERT INTO tbl_resposta (
					pergunta,
					hd_chamado,
					txt_resposta,
					tipo_resposta_item,
					pesquisa,
					admin,
					data_input
				)VALUES(
					$pergunta,
					$hdChamado,
					'$txt_resposta',
					$resposta,
					'$pesquisa',
					$login_admin,
					current_timestamp
				)
				";
		$res = pg_query($con,$sql);



		if (pg_last_error($con)){
			$erro[] = pg_last_error($con) ;
		}

	}
	if (count($erro)>0){
		$erro = implode('<br>ttt', $erro);
		if(strpos($erro, 'syntax erro') > 0 ){
			$erro = "Favor preencher todas as respostas da pesquisa";
		}
		$res = pg_query($con,'ROLLBACK TRANSACTION');
	}else{
		$res = pg_query($con,'COMMIT TRANSACTION');
	}

	if ($erro){
		echo "1|$erro";
	}else{
		echo "0|Sucesso";
	}

	exit;

}

if ($_GET['ajax'] && $_GET['combo_motivo']){
	$tipo = $_GET['tipo'];
	$motivo = $_GET['motivo'];

	if($tipo == 'R'){
		$categoria = 'Revenda';
	}else if($tipo == 'P'){
		$categoria = 'Posto Autorizado';
	}else if($tipo == 'T'){
		$categoria = 'Representante';
	}
	$sql = "SELECT hd_motivo_ligacao,descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica AND categoria = '$categoria' ORDER BY descricao";
	$res = pg_query($con,$sql);
	$options .="<option value=''>Escolha</option>";
	if(pg_num_rows($res) > 0){
		for($i = 0; $i < pg_num_rows($res); $i++){

			$hd_motivo_ligacao 	= pg_fetch_result($res,$i,'hd_motivo_ligacao');
			$descricao 			= pg_fetch_result($res,$i,'descricao');

			$selected = ($hd_motivo_ligacao == $motivo) ? "SELECTED" : "";

			$options .= "<option value='{$hd_motivo_ligacao}' {$selected}>{$descricao}</option>";
		}
	}else{
		$options = "<option value=''></option>";
	}

	echo $options;
	exit;

}

// !129655
// HD 129655 - Gravar faq para dúvida de produtos
/**
 * Insere as dúvidas do produto pesquisadas.
 *
 * @return boolean Se true a função gravou as Dúvidas
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
function gravarFaq() {
	global $con,$hd_chamado,$msg_erro;

	if ( empty($hd_chamado) || $hd_chamado <= 0 ) {
		$msg_erro .= "<p>Não foi possível gravar dúvidas do produto, número do chamado não informado. $hd_chamado</p>";
		return false;
	}

	if ( isset($_POST['faq']) && count($_POST['faq']) > 0 && is_array($_POST['faq']) ) {
		$aFaqs = array();
		foreach ( $_POST['faq'] as $xfaq ) {
			$xfaq = (int) $xfaq;
			$aFaqs[] = "({$hd_chamado},{$xfaq})";
		}
		@pg_query($con,"DELETE FROM tbl_hd_chamado_faq WHERE hd_chamado = {$hd_chamado}");
		$sql = "INSERT INTO tbl_hd_chamado_faq (hd_chamado,faq) VALUES " . implode(',',$aFaqs);
		$res = @pg_query($con,$sql);
		if ( is_resource($res) && pg_affected_rows($res) > 0 ) {
			return true;
		}
		$msg_erro .= "<p>Erro ao inserir as dúvidas.</p>";
		return false;
	}
}
// fim HD 129655

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		//HD 204082: Busca de revenda para fábricas >= 81 e Telecontrol Net
		if ($tipo_busca=="revenda"){
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome
					FROM tbl_revenda
					JOIN tbl_revenda_compra USING(revenda)
					WHERE tbl_revenda_compra.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_revenda.cnpj like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_revenda.nome) ilike UPPER('%$q%') ";
			}


			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$revenda = trim(pg_fetch_result($res,$i,revenda));
					$cnpj    = trim(pg_fetch_result($res,$i,cnpj));
					$nome    = trim(pg_fetch_result($res,$i,nome));
					echo "$revenda|$cnpj|$nome";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="revenda_geral"){

			if($login_fabrica == 15) {
				$cond_join = " JOIN tbl_revenda_fabrica ON tbl_revenda.revenda=tbl_revenda_fabrica.revenda and tbl_revenda_fabrica.fabrica = $login_fabrica ";
			}

			$cond_campo = ($login_fabrica == 15) ? " tbl_revenda_fabrica.contato_razao_social as nome" : " tbl_revenda.nome";
			$sql = "SELECT 	tbl_revenda.revenda,
							tbl_revenda.cnpj,
							tbl_cidade.nome AS cidade_nome,
							$cond_campo";
			if($login_fabrica == 74){
				$sql .= ", tbl_revenda.fone ";
			}
			$sql .=" FROM tbl_revenda
					JOIN tbl_cidade USING(cidade)
					$cond_join
					WHERE cnpj_validado
					";

			if ($busca == "codigo"){
				$sql .= " AND tbl_revenda.cnpj like '%$q%' ";
			}else{
				$sql .= " AND (tbl_revenda.nome ilike '%$q%' ";
				$sql .= " OR tbl_cidade.nome ilike '%$q%' ";
				$sql .= " OR tbl_revenda.nome || ' - ' || tbl_cidade.nome ilike '%$q%') ";
			}

			$sql .= " LIMIT 10 ";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$revenda = trim(pg_fetch_result($res,$i,revenda));
					$cnpj    = trim(pg_fetch_result($res,$i,cnpj));
					$nome    = trim(pg_fetch_result($res,$i,nome));
					$cidade_nome = trim(pg_fetch_result($res,$i,cidade_nome));
					if($login_fabrica == 74){
						$fone_revenda = trim(pg_fetch_result($res,$i,fone));
						echo "$revenda|$cnpj|$nome|$cidade_nome|$fone_revenda";
					}else{
						echo "$revenda|$cnpj|$nome|$cidade_nome";
					}
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="revenda_os"){
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome
					FROM tbl_revenda
					JOIN tbl_revenda_compra USING(revenda)
					WHERE UPPER(tbl_revenda.nome) ilike UPPER('%$q%') ";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$revenda = trim(pg_fetch_result($res,$i,revenda));
					$cnpj    = trim(pg_fetch_result($res,$i,cnpj));
					$nome    = trim(pg_fetch_result($res,$i,nome));
					echo "$revenda|$cnpj|$nome";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.posto,
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.nome_fantasia,
					tbl_posto.fone,
					tbl_posto_fabrica.contato_fone_comercial as contato_fone_comercial,
					tbl_posto_fabrica.contato_email as contato_email,
					tbl_posto_fabrica.contato_email as email
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto ilike '%$q%' ";
			}else{
				$sql .= "  AND (
								UPPER(tbl_posto.nome) like UPPER('%$q%')
							OR  UPPER(tbl_posto_fabrica.nome_fantasia) like UPPER('%$q%')
								)";
			}
//var_dump( $sql );
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$posto         = trim(pg_fetch_result($res,$i,posto));
					$cnpj          = trim(pg_fetch_result($res,$i,cnpj));
					$nome          = trim(pg_fetch_result($res,$i,nome));
					$codigo_posto  = trim(pg_fetch_result($res,$i,codigo_posto));
					$nome_fantasia = trim(pg_fetch_result($res,$i,nome_fantasia));
					$fone = trim(pg_fetch_result($res,$i,fone));
					$fone_2 = trim(pg_fetch_result($res,$i,contato_fone_comercial));
					$email  = trim(pg_fetch_result($res,$i,email));
					$email_2 = trim(pg_fetch_result($res,$i,contato_email));

					if(strlen($fone_2) > 0){
						echo "$posto|$cnpj|$codigo_posto|$nome|$nome_fantasia|$fone_2|$email_2";
					}else{
						echo "$posto|$cnpj|$codigo_posto|$nome|$nome_fantasia|$fone|$email_2";
					}
					echo "\n";
				}
			}
		}
			if ($tipo_busca=="mapa_cidade"){

			$sql = "SELECT      DISTINCT tbl_posto.cidade
					FROM        tbl_posto_fabrica
					JOIN tbl_posto using(posto)
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
					AND         tbl_posto.cidade ILIKE UPPER('%$q%')
					ORDER BY    tbl_posto.cidade";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$mapa_cidade        = trim(pg_fetch_result($res,$i,cidade));
					echo "$mapa_cidade";
					echo "\n";
				}
			}
		}
	}


	if ($tipo_busca=="cliente_admin"){
			$y = trim (strtoupper ($q));
			$palavras = explode(' ',$y);
			$count = count($palavras);
			$sql_and = "";
			for($i=0 ; $i < $count ; $i++){
				if(strlen(trim($palavras[$i]))>0){
					$cnpj_pesquisa = trim($palavras[$i]);
					$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
					$sql_and .= " AND (tbl_cliente_admin.nome ILIKE '%".trim($palavras[$i])."%'
								 	  OR  tbl_cliente_admin.cnpj ILIKE '%$cnpj_pesquisa%' OR tbl_cliente_admin.cidade ILIKE '%".trim($palavras[$i])."%')";
					if (strlen($cidade)>0) {
						$sql_and .= " AND tbl_cliente_admin.cidade ILIKE '%".trim($cidade)."%'";
					}
				}
			}

			$sql = "SELECT      tbl_cliente_admin.cliente_admin,
								tbl_cliente_admin.nome,
								tbl_cliente_admin.codigo,
								tbl_cliente_admin.cnpj,
								tbl_cliente_admin.cidade
					FROM        tbl_cliente_admin
					WHERE       tbl_cliente_admin.fabrica = $login_fabrica
					$sql_and limit 30";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$cliente_admin      = trim(pg_fetch_result($res,$i,cliente_admin));
					$nome               = trim(pg_fetch_result($res,$i,nome));
					$codigo             = trim(pg_fetch_result($res,$i,codigo));
					$cnpj               = trim(pg_fetch_result($res,$i,cnpj));
					$cidade             = trim(pg_fetch_result($res,$i,cidade));

					echo "$cliente_admin|$cnpj|$codigo|$nome|$cidade";
					echo "\n";
				}
			}
		}

		if ($tipo_busca == "localizar") {

				$y = trim (strtoupper ($q));
				$palavras = explode(' ',$y);
				$count = count($palavras);
				$sql_and = "";

				for ($i = 0 ; $i < $count; $i++) {

					if (strlen(trim($palavras[$i])) > 0) {

						$cnpj_pesquisa = trim($palavras[$i]);
						$cnpj_pesquisa = str_replace(' ','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace('-','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace('\'','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace('.','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace('/','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace('\\','',$cnpj_pesquisa);

						$sql_and .= " AND (tbl_hd_chamado_extra.nome ILIKE '%".trim($palavras[$i])."%'
										  OR  tbl_hd_chamado_extra.cpf ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.fone ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.nota_fiscal ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.serie ILIKE '%".trim($palavras[$i])."%' OR tbl_os.sua_os ILIKE'%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.cep ILIKE '%".$cnpj_pesquisa."%')";
					}
				}

				$sql = "SELECT      tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado_extra.serie,
									tbl_hd_chamado_extra.nota_fiscal,
									tbl_hd_chamado_extra.nome,
									tbl_hd_chamado_extra.cpf,
									tbl_hd_chamado_extra.rg,
									tbl_hd_chamado_extra.endereco,
									tbl_hd_chamado_extra.email,
									tbl_hd_chamado_extra.numero,
									tbl_hd_chamado_extra.complemento,
									tbl_hd_chamado_extra.bairro,
									tbl_cidade.nome as nome_cidade,
									tbl_cidade.estado,
									tbl_os.sua_os,
									tbl_hd_chamado_extra.cep,
									tbl_hd_chamado_extra.fone,
						FROM        tbl_hd_chamado JOIN tbl_hd_chamado_extra using(hd_chamado)
						LEFT JOIN tbl_os USING(os)
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE       tbl_hd_chamado.fabrica = $login_fabrica
						$sql_and limit 30";

				$res = pg_query($con,$sql);
				if (pg_num_rows ($res) > 0) {
					for ($i=0; $i<pg_num_rows ($res); $i++ ){
						$hd_chamado        = trim(pg_fetch_result($res,$i,hd_chamado));
						$nome              = trim(pg_fetch_result($res,$i,nome));
						$serie             = trim(pg_fetch_result($res,$i,serie));
						$cpf               = trim(pg_fetch_result($res,$i,cpf));
						$rg               = trim(pg_fetch_result($res,$i,rg));
						$email               = trim(pg_fetch_result($res,$i,email));
						$nota_fiscal       = trim(pg_fetch_result($res,$i,nota_fiscal));
						$fone              = trim(pg_fetch_result($res,$i,fone));
						$endereco          = trim(pg_fetch_result($res,$i,endereco));
						$numero            = trim(pg_fetch_result($res,$i,numero));
						$complemento       = trim(pg_fetch_result($res,$i,complemento));
						$cep               = trim(pg_fetch_result($res,$i,cep));
						$sua_os            = trim(pg_fetch_result($res,$i,sua_os));
						$bairro            = trim(pg_fetch_result($res,$i,bairro));
						$cidade            = trim(pg_fetch_result($res,$i,nome_cidade));
						$estado            = trim(pg_fetch_result($res,$i,estado));



						echo "$hd_chamado|$cpf|$nome|$serie|$nota_fiscal|$fone|$sua_os|$cep|$endereco|$numero|$bairro|$complemento|$cidade|$estado|$rg|$email";
						echo "\n";
					}
				}
		}

	exit;
}

$title = "Atendimento Call-Center";
$layout_menu = 'callcenter';

include 'funcoes.php';
function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}
function acentos1( $texto ){
	 $array1 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" , "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","" );
	$array2 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" ,"", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","");
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" , "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","" );
 $array2 = array("A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","N","N" );
 return str_replace( $array1, $array2, $texto );
}

if (!function_exists('date_to_timestamp')) {
	function date_to_timestamp($fecha='hoje') { // $fecha formato YYYY-MM-DD H24:MI:SS ou DD-MM-YYYY H24:MI:SS
	    if ($fecha=="hoje") $fecha= date('Y-m-d H:i:s');
		list($date, $time)		  = explode(' ', $fecha);
		list($year, $month, $day) = preg_split('/[\/|\.|-]/', $date);
		if (strlen($year)==2 and strlen($day)==4) list($day,$year) = array($year,$day); // Troca a ordem de dia e ano, se precisar
		if ($time=="") $time = "00:00:00";
		list($hour, $minute, $second) = explode(':', $time);
		return mktime((int) $hour, (int) $minute, (int) $second, (int) $month, (int) $day, (int) $year);
	}
}

/* MLG HD 175044    */
/*  14/12/2009 - Alteração direta, colquei conferência de 'funcion exists', porque mesmo que o include
				 e 'exit' esteja antes da declaração da função, ela é declarada na primeira passagem
				 do interpretador. */
if (!function_exists('checaCPF')) {
	function checaCPF  ($cpf,$return_str = true, $use_savepoint = false){
	   global $con, $login_fabrica;	// Para conectar com o banco...
			$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	//  23/12/2009 HD 186382 - a função pula as pré-OS anteriores à hoje...
			if ((($login_fabrica==52  and strlen($_REQUEST['pre_os'])>0) or
				$login_fabrica==11) and
				date_to_timestamp($_REQUEST['data_abertura'])<date_to_timestamp('24/12/2009')) return $cpf;
			if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

			if ($use_savepoint) $n = @pg_query($con,"SAVEPOINT checa_CPF");

			if(strlen($cpf) > 0){
				$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
				if ($res_cpf === false) {
					$cpf_erro = pg_last_error($con);
					if ($use_savepoint) $n = @pg_query($con,"ROLLBACK TO SAVEPOINT checa_CPF");
					return ($return_str) ? $cpf_erro : false;
				}
			}
			return $cpf;

}
}
include '../helpdesk/mlg_funciones.php';

$indicacao_posto = $_GET['indicacao_posto'];

if (strlen($indicacao_posto) == 0) {
	$indicacao_posto = $_POST['indicacao_posto'];
}

if (strlen($indicacao_posto) == 0) {
	$indicacao_posto = 'f';
}

$atendimento_callcenter = (strlen($_GET['atendimento_callcenter']) > 0) ? trim($_GET['atendimento_callcenter']) : trim($_POST['atendimento_callcenter']);

$btn_acao = $_POST['btn_acao'];

if (strlen($btn_acao) > 0) {

	$callcenter         = $_POST['callcenter'];
	$hd_chamado         = $callcenter;
	$tab_atual          = $_POST['tab_atual'];
	$status_interacao   = $_POST['status_interacao'];
	$transferir         = $_POST['transferir'];
	$intervensor        = $_POST['intervensor'];
	$chamado_interno    = $_POST['chamado_interno'];
	$envia_email        = $_POST['envia_email'];
	$marca        		= $_POST['marca'];

	if ($marca <= 0) {
		$marca = 'NULL';
	}

	if ($login_fabrica == 24 and !empty($hd_chamado) ) {
		$sql = "SELECT status FROM tbl_hd_chamado WHERE fabrica = $login_fabrica AND hd_chamado = $hd_chamado AND status = 'Resolvido'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			 $chamado_resolvido_reabertura = true;
			 $chamado_resolvido_reabertura_admin = $login_admin;
		} else {
			$chamado_resolvido_reabertura = false;
		}
	}

	if ($login_fabrica == 24) {
		$ligacao_agendada = $_POST["ligacao_agendada"];

		if (strlen($_POST["ligacao_agendada"]) > 0) {
			list($dmsd, $dmsm, $dmsa) = explode("/", $ligacao_agendada);

			if(!checkdate($dmsm, $dmsd, $dmsa)) {
				$msg_erro .= 'Data para ligação agendada inválida';
			} else {
				$aux_ligacao_agendada = "{$dmsa}-{$dmsm}-{$dmsd}";
			}
		}
	}
	if ($login_fabrica == 15) {
		$xdata_retorno = $data_retorno ;
		if (isset($_POST["data_retorno"])){
			$data_retorno = $_POST["data_retorno"];
			if (strlen($_POST["data_retorno"]) > 0) {
				list($dmsd, $dmsm, $dmsa) = explode("/", $data_retorno);

				if(!checkdate($dmsm, $dmsd, $dmsa)) {
					$msg_erro .= 'Data para ligação agendada inválida';
				} else {
					$aux_data_retorno = "{$dmsa}-{$dmsm}-{$dmsd}";
				}
			}
		}
		if (($status_interacao == "Retorno") AND(strlen($_POST["data_retorno"])) == 0 ){
			$msg_erro .= " Data Retorno Obrigatório ";
		}

		if ($status_interacao == "Retorno" && $_POST["data_retorno_alterou"] == "t" && strtotime($aux_data_retorno) < strtotime("today")) {
			$msg_erro .= " Data Retorno não pode ser anterior a data atual ";
		}

	}

	if ($login_fabrica == 85) {
		$atendimento_ambev      = $_POST["atendimento_ambev"];
		$atendimento_ambev_nome = $_POST["atendimento_ambev_nome"];
		$nome_fantasia          = $_POST["nome_fantasia"];
		$consumidor_cpf_cnpj    = $_POST["consumidor_cpf_cnpj"];
        $data_fabricacao_ambev  = $_POST["data_fabricacao_ambev"];


		if (!strlen($atendimento_ambev)) {
			$msg_erro .= 'Selecione se é atendimento ambev ou não';
		} else {
			if ($atendimento_ambev == "t") {
				$codigo_ambev            = $_POST["codigo_ambev"];
				$data_encerramento_ambev = $_POST["data_encerramento_ambev"];
				if(!strlen($atendimento_ambev_nome)){
					$msg_erro .= "Escreva o nome no atendimento ambev";
				}

				$sql = "SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica = 85 AND codigo = 'ambev'";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res) > 0 ) {
					$cliente_admin_ambev = pg_fetch_result($res, 0, "cliente_admin");
				}else{
					$msg_erro = "Não foi encontrado nenhum cliente admin com código 'ambev', favor cadastrar";
				}
			}
		}
	}

	if (strlen($envia_email) == 0) {
		$xenvia_email = "'f'";
	} else {
		$xenvia_email = "'t'";
	}

	if (in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)) {
		if ($hd_chamado){
			$sql = "SELECT '2012-08-14 00:00:00' > tbl_hd_chamado.data as data
					FROM tbl_hd_chamado
					WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			if (pg_fetch_result($res, 0, 0) == 't') {
				$fabricas_validam_campos_obrigatorios = array();
			}
		}
	}

	if ($login_fabrica == 24) {

		$pedir_produto = false;

		if ($_POST['orientacao_uso'] == 't') {

			$orientacao_uso = 't';

			if ( $_POST['abre_os'] == 't') {

				$msg_erro = 'Não é possível abrir Pré-OS com Orientação ao Consumidor selecionado.';

			}

		} else {

			$orientacao_uso = 'f';

		}

		if ($indicacao_posto == 't') {
			$callcenter_assunto = "produto_local_de_assistencia";
		} else if(isset($_POST['callcenter_assunto'])) {
			$callcenter_assunto = trim($_POST["callcenter_assunto"]["$tab_atual"]);
		}

		if (strlen($callcenter_assunto) == 0) {
			$categoria = $_POST['tab_atual'];
		} else {
			$categoria = $callcenter_assunto;
		}

		foreach ($assuntos as $categoria_assunto => $itens_categoria_assunto) {

			foreach ($itens_categoria_assunto as $label_assunto => $bd_assunto) {
				if ($bd_assunto == $categoria) {
					$categoria_assunto_seleciona = $categoria_assunto;
				}

			}

		}

		switch ($categoria_assunto_seleciona) {

			case "PRODUTOS":
				if ($tab_atual == "reclamacao_produto") {
					$pedir_produto = true;
				} else {
					$categoria = "";
				}
			break;

			case "MANUAL":
				if ($tab_atual == "reclamacao_produto") {
					$pedir_produto = true;
				} else {
					$categoria = "";
				}
			break;

			case "EMPRESA":
				if ($tab_atual == "reclamacao_empresa") {
				} else {
					$categoria = "";
				}
			break;

			case "ASSISTÊNCIA TÉCNICA":
				if ($tab_atual == "reclamacao_at") {
				} else {
					$categoria = "";
				}
			break;

			case "REVENDA":
				if ($tab_atual == "onde_comprar") {
				} else {
					$categoria = "";
				}
			break;

			case "PROCON":
				if ($tab_atual == "procon"){
				} else {
					$categoria = "";
				}

			default:
				$categoria = "";

		}

	}

	if ($login_fabrica == 11) {//HD 53881 27/11/2008

		$tipo_reclamacao = $_POST['tipo_reclamacao'];

		if(strlen($callcenter) == 0 OR !empty($_POST['protocolo_id'])){
			$hd_motivo_ligacao = $_POST['hd_motivo_ligacao'];
			if($tab_atual == "reclamacao_produto" AND empty($hd_motivo_ligacao)){
				$msg_erro = "Escolha o motivo da ligação";
			}
		}

		if ($tab_atual == "reclamacao_at" AND strlen($tipo_reclamacao) == 0) {
			$msg_erro = "Escolha o Tipo da Reclamação";
		}


		$sub_tipo_reclamacao = array("mau_atendimento","posto_nao_contribui","demonstra_desorg","possui_bom_atend","demonstra_org","reclamacao_at_info");

		if (in_array($tipo_reclamacao, $sub_tipo_reclamacao)) {
			$tab_atual = $tipo_reclamacao;
		}

		$reclamado = $_POST['reclamado_at'];

		if (strlen($reclamado) > 0) {
			$xreclamado = "'" . $reclamado . "'";
		} else {
			$xreclamado = "null";
		}

	}

	if (strlen($chamado_interno) > 0) {
		$xchamado_interno = "'t'";
	} else {
		$xchamado_interno = "'f'";
	}

	if (strlen($transferir) == 0) {
		if($login_fabrica <> 24){
			$xtransferir = $login_admin;
		}
	} else {
		$xtransferir = $transferir;
	}

	if (strlen($status_interacao) > 0) {
		$xstatus_interacao = "'".$status_interacao."'";
	} else {
		$xstatus_interacao = "''";
	}

	if (strlen($tab_atual) == 0 and $login_fabrica == 25) {
		$tab_atual = "extensao";
	}

	if (strlen($tab_atual) == 0 and $login_fabrica <> 25) {
		$tab_atual = "reclamacao_produto";
	}

	$xconsumidor_revenda = "'C'";

	if (strlen(trim($_POST['consumidor_revenda'])) > 0) {
		$xconsumidor_revenda = "'".trim($_POST['consumidor_revenda'])."'";
	}

	$xorigem             = "'".trim($_POST['origem'])."'";
	$receber_informacoes = $_POST['receber_informacoes'];
	$hora_ligacao        = $_POST['hora_ligacao'];

	if (strlen($hora_ligacao) == 0) {
		$xhora_ligacao = "null";
	} else {
		$xhora_ligacao = "'$hora_ligacao".":00'";
	}

	$defeito_reclamado      = $_POST['defeito_reclamado'];
	$consumidor_nome        = trim($_POST['consumidor_nome']);
	$contato_nome           = trim($_POST['contato_nome']);
	$cliente                = trim($_POST['cliente']);
	$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$_POST['consumidor_cpf']));
	if(empty($valida_cpf_cnpj)){
		$consumidor_cpf         = checaCPF($_POST['consumidor_cpf'],false);
	}else{
		$msg_erro = $valida_cpf_cnpj;
	}
	$atendimento_callcenter = trim($_POST['atendimento_callcenter']);
	$hd_motivo_ligacao 		= (empty($hd_motivo_ligacao)) ? 'null' : $hd_motivo_ligacao;

	if (is_numeric($consumidor_cpf)) {

		$mask = (strlen($consumidor_cpf) == 14) ? '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/':'/(\d{3})(\d{3})(\d{3})(\d{2})/';
		$fmt  = (strlen($consumidor_cpf) == 14) ? '$1.$2.$3/$4-$5':'$1.$2.$3-$4';

		$consumidor_cpf = preg_replace($mask, $fmt, $consumidor_cpf);

	} else {

		$consumidor_cpf  = "null";

		//28/12/2009 - Lembrando que só deve dar erro se o usuário digitou um CPF/CNPJ...
		if (strlen($_POST['consumidor_cpf']) != 0) {
			$msg_erro .= "CPF / CNPJ do consumidor inválido <br />";
		}

	}
    if($login_fabrica != 85){
        $consumidor_rg      = trim($_POST['consumidor_rg']);
        $consumidor_rg      = preg_replace("/\W/","",$consumidor_rg);
	}
	$consumidor_email       = trim($_POST['consumidor_email']);
	$consumidor_fone        = trim($_POST['consumidor_fone']);
	$consumidor_fone        = str_replace("'","",$consumidor_fone);
	$consumidor_fone2       = trim($_POST['consumidor_fone2']);
	$consumidor_fone2       = str_replace("'","",$consumidor_fone2);
	$consumidor_fone3       = trim($_POST['consumidor_fone3']);
	$consumidor_fone3       = str_replace("'","",$consumidor_fone3);
	$consumidor_cep         = trim($_POST['consumidor_cep']);
	$consumidor_cep		    = substr(preg_replace( '/[^0-9]+/', '', $consumidor_cep), 0, 8);
	$consumidor_endereco    = str_replace("'", "", trim($_POST['consumidor_endereco']));
	$consumidor_numero      = trim($_POST['consumidor_numero']);
	$consumidor_numero      = str_replace("'","",$consumidor_numero);
	$consumidor_complemento = trim($_POST['consumidor_complemento']);
	$consumidor_bairro      = trim($_POST['consumidor_bairro']);
    $consumidor_cidade      = str_replace("'", "", trim(strtoupper($_POST['consumidor_cidade'])));
	$consumidor_cidade      = str_replace("\\", "", $consumidor_cidade);
	$consumidor_estado      = trim(strtoupper($_POST['consumidor_estado']));
	$origem                 = $_POST['origem'];
	$consumidor_revenda     = $_POST['consumidor_revenda'];
	$consumidor_cpf_cnpj	= $_POST['consumidor_cpf_cnpj'];
	$cnpj_revenda			= $_POST['cnpj_revenda'];
	$nome_revenda			= substr($_POST['nome_revenda'], 0, 50);
	$hd_motivo_ligacao      = $_POST['hd_motivo_ligacao'];
// echo $_POST['consumidor_cidade']." - ".$consumidor_cidade;exit;
	if ($login_fabrica == 86) {
		if( ($consumidor_revenda == 'R' OR $consumidor_revenda == 'T' OR $consumidor_revenda == 'P') AND empty($hd_motivo_ligacao)){
			$msg_erro = "Informe o motivo do atendimento <br>";
		}
	}


	$hd_motivo_ligacao = (empty($hd_motivo_ligacao)) ? "NULL" : $hd_motivo_ligacao;
	if (strlen($cnpj_revenda)) {

		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cnpj_revenda));

		if(empty($valida_cpf_cnpj)){
			@$res_cnpj_revenda = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj_revenda')");

			if (pg_errormessage($con)) {
				$msg_erro = "Erro na validação do CNPJ da Revenda: " . substr(pg_errormessage($con), 6);
			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}

	}else{
        if($login_fabrica == 85){
            $msg_erro .= "O campo CNPJ de revenda é obrigatório";
        }
	}

	if($login_fabrica == 74 and (empty($nome_revenda) or empty($cnpj_revenda))) {
		$msg_erro .= "Informe o nome da revenda <br>";
	}

	if (in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)) {

		if (empty($consumidor_nome)) {
			$msg_erro .= "Informe o nome do consumidor <br>";
		}

		if (empty($consumidor_email)) {
			$msg_erro .= "Informe o email do consumidor <br>";
		}

		if (empty($consumidor_cep)) {
			$msg_erro .= "Informe o cep do consumidor <br>";
		}

		if (empty($consumidor_endereco)) {
			$msg_erro .= "Informe o endereco do consumidor <br>";
		}

		if (empty($consumidor_numero)) {
			$msg_erro .= "Informe o número endereco do consumidor <br>";
		}

		if (empty($consumidor_bairro)) {
			$msg_erro .= "Informe o bairro do consumidor <br>";
		}

		if (empty($consumidor_cidade)) {
			$msg_erro .= "Informe a cidade do consumidor <br>";
		}

		if (empty($consumidor_estado)) {
			$msg_erro .= "Informe o estado (UF) do consumidor <br>";
		}

	}

	if($login_fabrica == 52){

		if (strlen(trim($_POST['consumidor_cep'])) == 0) {
			$msg_erro .= "Por favor informe CEP do consumidor <br>";
		}

		if(strlen($_POST['consumidor_cpf']) == 0){
			$msg_erro .= "Informe o CPF / CNPJ do Cliente <br>";
		}

		if(strlen($_POST['ponto_referencia']) == 0){
			$msg_erro .= "Informe o Ponto de Referência <br>";
		}

	}

	if (in_array($login_fabrica, array(139))) {
		if (!strlen($consumidor_cpf) || $consumidor_cpf == "null") {
			$msg_erro .= "Informe o CPF do Cliente <br>";
		}

		if (!strlen($consumidor_fone)) {
			$msg_erro .= "Informe o Telefone do Cliente <br >";
		}

		if (!strlen($consumidor_fone3)) {
			$msg_erro .= "Informe o Celular do Cliente <br >";
		}

		if (!strlen($consumidor_email)) {
			$msg_erro .= "Informe o Email do Cliente <br >";
		}

		if (!strlen(trim($_POST["nota_fiscal"])) || $_POST["nota_fiscal"] == "null") {
			$msg_erro .= "Informe a Nota Fiscal <br >";
		}

		if (!strlen(trim($_POST["data_nf"]))) {
			$msg_erro .= "Informe a data da nota fiscal <br >";
		}

		if (!strlen(trim($_POST["produto_referencia"])) || !strlen(trim($_POST["produto_nome"]))) {
			$msg_erro .= "Informe a referência e descrição do produto <br >";
		}

		if (!strlen(trim($_POST["cnpj_revenda"])) && !strlen(trim($_POST["nome_revenda"]))) {
			$msg_erro .= "Informe o cnpj ou nome da revenda <br >";
		}
	}

	if ($indicacao_posto == 't' and $login_fabrica <> 24) {

		$consumidor_nome    = 'Indicação de Posto';
		$consumidor_fone    = '00000000000';
		$consumidor_estado  = '00';
		$consumidor_cidade  = 'Indicação de Posto';
		$consumidor_revenda = 'Indicação de Posto';
		$origem             = 'Indicação de Posto';
		$consumidor_cpf     = '00000000000';
		$consumidor_cep     = '00000000';
		$produto_referencia = 'Indicação de Posto';
		$hora_ligacao       = '00:00';

	} else if ($indicacao_posto == 't' and $login_fabrica == 24) {

		if (strlen($_POST['produto_referencia']) == 0 or strlen($_POST['produto_nome']) == 0) {
			$msg_erro .= "Por favor, informe a referência e a descrição do Produto ";
		}

	}

	if ($login_fabrica == 24) {

		if ($pedir_produto) {

			if (strlen($_POST['produto_referencia']) == 0 or strlen($_POST['produto_nome']) == 0) {
				$msg_erro .= "Por favor, informe a referência e a descrição do Produto";
			}

		}

	}
	$consumidor_nome = substr($consumidor_nome, 0, 50);
	$xconsumidor_nome        = (strlen($consumidor_nome)==0)  ? "null" : "'".$consumidor_nome."'";
	$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));
	if(empty($valida_cpf_cnpj)){
		$xconsumidor_cpf     = (!is_numeric(checaCPF($consumidor_cpf)) and strlen(trim($consumidor_cpf)) != 0) ? "null" : "'".checaCPF($consumidor_cpf)."'";
	}else{
		$msg_erro = $valida_cpf_cnpj;
	}

	$consumidor_fone  = preg_replace("/\D/","",$consumidor_fone);
	$consumidor_fone  = str_replace(" ","",$consumidor_fone);
	$consumidor_fone2 = preg_replace("/\D/","",$consumidor_fone2);
	$consumidor_fone3 = preg_replace("/\D/","",$consumidor_fone3);


	$xconsumidor_rg          = (strlen($consumidor_rg) == 0)          ? "null" : "'".$consumidor_rg."'";
	$xconsumidor_email       = (strlen($consumidor_email) == 0)       ? "null" : "'".$consumidor_email."'";
	$xconsumidor_fone        = (strlen($consumidor_fone) == 0)        ? "null" : "'".$consumidor_fone."'";
	$xconsumidor_fone2       = (strlen($consumidor_fone2) == 0)       ? "null" : "'".$consumidor_fone2."'";
	$xconsumidor_fone3       = (strlen($consumidor_fone3) == 0)       ? "null" : "'".$consumidor_fone3."'";
	$xconsumidor_cep         = (strlen($consumidor_cep) == 0)         ? "null" : "'".$consumidor_cep."'";
	$xconsumidor_endereco    = (strlen($consumidor_endereco) == 0)    ? "null" : "'".$consumidor_endereco."'";
	$xconsumidor_numero      = (strlen($consumidor_numero) == 0)      ? "null" : "'".$consumidor_numero."'";
	$xconsumidor_complemento = (strlen($consumidor_complemento) == 0) ? "null" : "'".$consumidor_complemento."'";
	$xconsumidor_bairro      = (strlen($consumidor_bairro) == 0)      ? "null" : "'".$consumidor_bairro."'";
	$xconsumidor_cidade      = (strlen($consumidor_cidade) == 0)      ? "null" : "'".$consumidor_cidade."'";
	$xconsumidor_estado      = (strlen($consumidor_estado) == 0)      ? "null" : "'".$consumidor_estado."'";

	if ($login_fabrica == 52) {

		if (!empty($_POST['ponto_referencia'])) {
			$xconsumidor_ponto_referencia 	= "'".trim($_POST['ponto_referencia'])."'";
		} else {
			$xconsumidor_ponto_referencia = 'null';
		}

		$campos_adicionais2 = array();

		if (!empty($_POST['operadora_celular'])){
			$campos_adicionais2["operadora"]		 		= trim($_POST['operadora_celular']);
		}else{
			$campos_adicionais2["operadora"]		 		= "";
		}

	}


	if (in_array($login_fabrica,array(3,24,85,94,35,145)) || ($login_fabrica == 5 && $indicacao_posto == 'f') ) {//HD 48900 58796


		if (!in_array($login_fabrica,array(94,35,145))) {
			if (strlen($consumidor_nome) == 0) {
				$msg_erro .= "Por favor inserir o nome do consumidor ";
			} else {
				$consumidor_nome = substr($consumidor_nome, 0, 50);
			}

			if (strlen($consumidor_cep) == 0 && !in_array($login_fabrica,array(24,85))) {
				$msg_erro .= "Por favor inserir o cep do consumidor ";
			}

			if (strlen($consumidor_bairro) == 0) {
				$msg_erro .= "Por favor inserir o bairro do consumidor ";
			}

			if (strlen($consumidor_endereco) == 0) {
				$msg_erro .= "Por favor inserir o endereco do consumidor ";
			}

			if (strlen($consumidor_fone) == 0) {
				$msg_erro .= "Por favor inserir o telefone do consumidor ";
			}

			if (strlen($consumidor_estado) == 0) {
				$msg_erro .= "Por favor selecione o estado ";
			}

			if (strlen($consumidor_cidade) == 0) {
				$msg_erro .= "Por favor inserir a cidade ";
			}

			if (strlen(trim($_POST['consumidor_revenda'])) == 0) {
				$msg_erro .= "Por favor selecione o tipo (Consumidor ou Revenda) ";
			}

			if (strlen(trim($_POST['origem'])) == 0) {
				$msg_erro .= "Por favor selecione a origem ";
			}
		}

        if(in_array($login_fabrica,array(35,85,94,145)) && strlen($msg_erro) == 0){
            $disparar_pesquisa = $_POST["disparar_pesquisa"];

            if($disparar_pesquisa == 't' && strlen($consumidor_email) > 0){
                $xenvia_email = "'t'";
                $valida_email = filter_var($consumidor_email,FILTER_VALIDATE_EMAIL);
                if($valida_email === false){
                    $msg_erro .= "O email informado não é válido para envio de pesquisa de satisfação ";
                }else{
                    $link_temp = explode("admin/",$HTTP_REFERER);

                    switch($login_fabrica){
                        case 85:
                            $from_fabrica           = "suporte@gelopar.com.br";
                            $from_fabrica_descricao = "Pós-Venda Gelopar";
                            $link_pesquisa = $link_temp[0]."externos/gelopar/callcenter_pesquisa_satisfacao2.php?atendimento=$hd_chamado";
                            $assunto  = "Pesquisa de Satisfação - GELOPAR";
                        break;
                        case 94:
                            #$from_fabrica           = "suporte@everest.ind.br";
                            $from_fabrica           = "no_reply@telecontrol.com.br";
                            $from_fabrica_descricao = "Pós-Venda Everest";
                            $link_pesquisa = $link_temp[0]."externos/everest/callcenter_pesquisa_satisfacao2.php?atendimento=$hd_chamado";
                            $assunto  = "Pesquisa de Satisfação - EVEREST";
                        break;
                        case 145:
                            $from_fabrica           = "no_reply@telecontrol.com.br";
                            $from_fabrica_descricao = "Pós-Venda Fabrimar";
                            $link_pesquisa = $link_temp[0]."externos/fabrimar/callcenter_pesquisa_satisfacao2.php?atendimento=$hd_chamado";
                            $assunto  = "Pesquisa de Satisfação - Fabrimar";
                        break;
                    }
                    $mensagem = "Produto: $produto_referencia - $produto_nome <br>";
                    $mensagem .= "Protocolo: $hd_chamado, <br>";
                    $mensagem .= "Prezado(a) $consumidor_nome, <br>";
                    $mensagem .= "Sua opinião é muito importante para melhorarmos nossos serviços<br>";
                    $mensagem .= "Por favor, faça uma avaliação sobre nossos produtos e atendimento através do link abaixo: <br />";
                    $mensagem .= "Pesquisa de Satisfação: <a href='$link_pesquisa' target='_blank'>Acesso Aqui</a> <br><br>Att <br>Equipe ".$login_fabrica_nome;

                    $headers  = "MIME-Version: 1.0 \r\n";
                    $headers .= "Content-type: text/html \r\n";
                    $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";

                    //$headers .= "To: $consumidor_nome <$consumidor_email> \r\n";
                    mail($consumidor_nome .'<'.$consumidor_email.'>', $assunto, utf8_encode($mensagem), $headers);

                    /*$mailer->IsSMTP();
                    $mailer->IsHTML();
                    $mailer->AddAddress($consumidor_email,$consumidor_nome);
                    $mailer->SetFrom($from_fabrica,$from_fabrica_descricao);
                    $mailer->Subject = $assunto;
                    $mailer->Body = $mensagem;

                    $mailer->Send();*/
                }
            }else if($login_fabrica == 35){
            	$transferir_consumidor = $_POST["transferir_consumidor"];
            	if (strlen($transferir_consumidor) > 0 ){
            		$valida_email = filter_var($consumidor_email,FILTER_VALIDATE_EMAIL);
           			if($valida_email === false){
                		$msg_erro .= " O email informado não é válido para envio do e-mail. ";
            		}
            		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));
					if(empty($valida_cpf_cnpj) AND strlen($msg_erro) == 0){
						$variavel = md5($callcenter.$consumidor_cpf);

						$link_temp = explode("admin/",$HTTP_REFERER);
                        $from_fabrica           = "no_reply@telecontrol.com.br";
                        $from_fabrica_descricao = "Pós-Venda CADENCE";
                        $link_pesquisa = $link_temp[0]."externos/callcenter/callcenter_resposta.php?x=$variavel";
                        $assunto  = "Responder ao atendimento - CADENCE";
	                    $mensagem = "Produto: $produto_referencia - $produto_nome <br>";
	                    $mensagem .= "Protocolo: $callcenter , <br> <br>";
	                    $mensagem .= "Prezado(a) $consumidor_nome, <br> <br>";
	                    $mensagem .= "$resposta <br> <br> Protocolo:$callcenter <br> CPF: $consumidor_cpf <br> <br>";
	                    $mensagem .= "Para responder esse atendimento por favor acesse o link $link_pesquisa <br>";

	                    $mailer->IsSMTP();
	                    $mailer->IsHTML();
	                    $mailer->AddAddress($consumidor_email,$consumidor_nome);
	                    $mailer->SetFrom($from_fabrica,$from_fabrica_descricao);
	                    $mailer->Subject = $assunto;
	                    $mailer->Body = $mensagem;

	                    $mailer->Send();


	                    $sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							status_item
						) values (
							$callcenter       ,
							current_timestamp ,
							'Foi enviado um E-mail para o consumidor responder ao atendimento',
							$login_admin      ,
							$xstatus_interacao
						)";

                    $res = pg_query($con,$sql);
                	}else{
                		$msg_erro .= $valida_cpf_cnpj;
                	}
				}
        	}else if(in_array($login_fabrica,array(85,94,145)) && ($disparar_pesquisa == 't' && strlen($consumidor_email) == 0)){
                $msg_erro .= "Favor digitar um email do consumidor para envio de pesquisa de satisfação ";
            }
        }

		if ($login_fabrica == 5) { // HD 59786
			$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$_POST['consumidor_cpf']));

			if(empty($valida_cpf_cnpj)){
				if (checaCPF($_POST['consumidor_cpf'],false) === false) {
					$msg_erro .= "Por favor inserir o CPF do consumidor ";
				}
			}else{
				$msg_erro .= $valida_cpf_cnpj;
			}

			if (strlen(trim($_POST['consumidor_cep'])) == 0) {
				$msg_erro .= "Por favor inserir CEP do consumidor ";
			}

			if (strlen($_POST["produto_referencia"]) == 0) {
				$msg_erro .= "Por favor, insira a referência do produto ";
			}

		}

	} else if ($indicacao_posto == 'f') {

		if (!in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)) {

			if (strlen($consumidor_nome) == 0) {
				$msg_erro .= "Por favor informe o nome do consumidor<br>";
			}

			if (strlen($consumidor_nome) > 0 and strlen($consumidor_estado) == 0) {
				$msg_erro .= "Por favor selecione o estado<br>";
			}

			if (strlen($consumidor_nome) > 0 and strlen($consumidor_cidade) == 0) {
				$msg_erro .= "Por favor inserir a cidade<br>";
			}

		}

	}

	$abre_os            = trim($_POST['abre_os']);
	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	$abre_ordem_servico = trim($_POST['abre_ordem_servico']);
	$imprimir_os        = trim($_POST['imprimir_os']);
	$resposta           = trim($_POST['resposta']);
	$posto_tab          = trim(strtoupper($_POST['posto_tab']));
	$codigo_posto_tab   = trim(strtoupper($_POST['codigo_posto_tab']));
	$posto_nome_tab     = trim(strtoupper($_POST['posto_nome_tab']));
	$posto_endereco_tab = trim(strtoupper($_POST['posto_endereco_tab']));
	$posto_cidade_tab   = trim(strtoupper($_POST['posto_cidade_tab']));
	$posto_estado_tab   = trim(strtoupper($_POST['posto_estado_tab']));
	$posto_fone_tab     = trim(strtoupper($_POST['posto_fone_tab']));
	$posto_email_tab    = trim(strtoupper($_POST['posto_email_tab']));
	$posto_km_tab       = trim(strtoupper($_POST['posto_km_tab']));
	$revenda_nome       = substr(trim($_POST['revenda_nome']), 0, 50);
	$revenda	        = trim($_POST['revenda']);
	$revenda_endereco   = trim($_POST['revenda_endereco']);
	$revenda_nro        = trim($_POST['revenda_nro']);
	$revenda_cmpto      = trim($_POST['revenda_cmpto']);
	$revenda_bairro     = trim($_POST['revenda_bairro']);
	$revenda_city       = trim($_POST['revenda_city']);
	$revenda_uf         = trim($_POST['revenda_uf']);
	$revenda_fone       = trim($_POST['revenda_fone']);
	$hd_extra_defeito   = trim($_POST['hd_extra_defeito']);
	$faq_situacao       = trim($_POST['faq_situacao']);
	$reclama_posto      = trim($_POST['tipo_reclamacao']);

    if(in_array($login_fabrica,array(85,94))){
        if(strlen($mensagem) > 0){
            $resposta = $mensagem;
        }
    }

    if($login_fabrica == 127 && (in_array($posto_tab,array(367307,371239)))){
        if (empty($consumidor_cep)) {
            $msg_erro .= "Informe o cep do consumidor <br>";
        }

        if (empty($consumidor_endereco)) {
            $msg_erro .= "Informe o endereco do consumidor <br>";
        }

        if (empty($consumidor_numero)) {
            $msg_erro .= "Informe o número endereco do consumidor <br>";
        }

        if (empty($consumidor_bairro)) {
            $msg_erro .= "Informe o bairro do consumidor <br>";
        }

        if (empty($consumidor_cidade)) {
            $msg_erro .= "Informe a cidade do consumidor <br>";
        }

        if (empty($consumidor_estado)) {
            $msg_erro .= "Informe o estado (UF) do consumidor <br>";
        }

        if(strlen($_POST['consumidor_cpf']) == 0){
            $msg_erro .= "Informe o CPF / CNPJ do Cliente <br>";
        }
        if(strlen($_POST['consumidor_fone']) == 0){
            $msg_erro .= "Informe o telefone do Cliente <br>";
        }
    }

	$xresposta            = (strlen($resposta) == 0)           ? "null" : "'".$resposta."'";
	$xreceber_informacoes = (strlen($receber_informacoes) > 0) ? "'$receber_informacoes'" : "'f'";

	if ($abre_os <> 't') {
		$abre_os = 'f';
	}

	if (in_array($login_fabrica,array(90,91))) {

		$hd_extra_defeito  = $_POST['hd_extra_defeito']  ?  $_POST['hd_extra_defeito']  : $_GET['hd_extra_defeito'];
		$reclamado_produto = $_POST['reclamado_produto'] ?  $_POST['reclamado_produto'] : $_GET['reclamado_produto'];

		if ((strlen($hd_extra_defeito) == 0 || strlen($reclamado_produto) == 0) and $tab_atual == "reclamacao_produto") {
			$msg_erro = "Digite o defeito do produto";
		}

	}

	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	if ($abre_ordem_servico <> 't') {
		$abre_ordem_servico = 'f';
	}

	if ($login_fabrica == 24) {

		if ($tab_atual == "outros_assuntos") {

			$reclamado = trim($_POST['outros_assuntos_descricao']);

			if (strlen($reclamado) == 0) {
				$msg_erro = 'Digite a descrição de outros assuntos';
			} else {
				$xreclamado = "'".$reclamado."'";
			}

		}

	}

	if ($tab_atual == "extensao") {

		$produto_referencia = $_POST['produto_referencia_es'];
		$produto_nome       = $_POST['produto_nome_es'];
		$reclamado          = trim($_POST['reclamado_es']);

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

		$xserie = $_POST['serie'];

		if (strlen($_POST["serie_es"]) > 0) $xserie = $_POST['serie_es'];

		//HD 12749
		if (strlen($produto_referencia) == 0) {
			$msg_erro .= " Insira a referência do produto\n ";
		}

		if (strlen($produto_nome) == 0) {
			$msg_erro .= " Insira nome do produto\n ";
		}

		if (strlen($xserie) == 0) {
			$msg_erro .= " Insira o número de série do produto\n ";
		}

		$es_id_numeroserie = $_POST['es_id_numeroserie'];
		$es_revenda_cnpj   = $_POST['es_revenda_cnpj'];
		$es_revenda        = $_POST['es_revenda'];

		if (strlen($es_revenda) == 0) {
			$xes_revenda = "NULL";
		} else {
			$xes_revenda = "'".$es_revenda."'";
		}

		$es_nota_fiscal = $_POST['es_nota_fiscal'];

		if (strlen($es_nota_fiscal) == 0) {
			$xes_nota_fiscal = "NULL";
		} else {
			$xes_nota_fiscal = "'".$es_nota_fiscal."'";
		}

		$es_data_compra = $_POST['es_data_compra'];

		if (strlen($es_data_compra) == 0) {
			$xes_data_compra = "NULL";
		} else {
			$xes_data_compra = "'".converte_data($es_data_compra)."'";
		}

		$es_municipiocompra = $_POST['es_municipiocompra'];

		if (strlen($es_municipiocompra) == 0) {
			$xes_municipiocompra = "NULL";
		} else {
			$xes_municipiocompra = "'".$es_municipiocompra."'";
		}

		$es_estadocompra = $_POST['es_estadocompra'];

		if (strlen($es_estadocompra) == 0) {
			$xes_estadocompra = "NULL";
		} else {
			$xes_estadocompra = "'".$es_estadocompra."'";
		}

		$es_data_nascimento = $_POST['es_data_nascimento'];

		if (strlen($es_data_nascimento) == 0) {
			$xes_data_nascimento = "NULL";
		} else {
			$xes_data_nascimento = "'".converte_data($es_data_nascimento)."'";
		}

		$es_estadocivil = $_POST['es_estadocivil'];

		if (strlen($es_estadocivil) == 0) {
			$xes_estadocivil = "NULL";
		} else {
			$xes_estadocivil = "'".$es_estadocivil."'";
		}

		$es_sexo = $_POST['es_sexo'];

		if (strlen($es_sexo) == 0) {
			$xes_sexo = "NULL";
		} else {
			$xes_sexo = "'".$es_sexo."'";
		}

		$es_filhos = $_POST['es_filhos'];

		if (strlen($es_filhos) == 0) {
			$xes_filhos = "NULL";
		} else {
			$xes_filhos = "'".$es_filhos."'";
		}

		$es_fonecomercial = $_POST['es_fonecomercial'];

		if (strlen($es_fonecomercial) == 0) {
			$xes_dddcomercial  = " NULL ";
			$xes_fonecomercial = "NULL";
		} else {
			$xes_dddcomercial  = "'".substr($es_fonecomercial,1,2)."'";
			$xes_fonecomercial = "'".substr($es_fonecomercial,5,9)."'";
		}

		$es_celular = $_POST['es_celular'];

		if (strlen($es_celular) == 0) {

			$xes_dddcelular = " NULL ";
			$xes_celular    = "NULL";

		} else {

			$xes_dddcelular = "'".substr($es_celular,1,2)."'";
			$xes_celular    = "'".substr($es_celular,5,9)."'";

		}

		$es_preferenciamusical = $_POST['es_preferenciamusical'];

		if (strlen($es_preferenciamusical) == 0) {
			$xes_preferenciamusical = "NULL";
		} else {
			$xes_preferenciamusical = "'".$es_preferenciamusical."'";
		}

	}
	if ($tab_atual == "reclamacao_produto") {

		$produto_referencia = $_POST['produto_referencia'];
		$produto_nome       = $_POST['produto_nome'];
		$voltagem           = $_POST['voltagem'];
		$reclamado          = trim($_POST['reclamado_produto']);
		$xserie             = $_POST['serie'];

		if ($login_fabrica == 43 or $login_fabrica == 14) {
			$ordem_montagem  = $_POST['ordem_montagem'];
			$codigo_postagem = $_POST['codigo_postagem'];
		}

		if ($login_fabrica == 85) {//HD 237892

			if (strlen($produto_referencia) == 0) {
				$msg_erro .= " Insira a referência do produto\n ";
			}

			if (strlen($produto_nome) == 0) {
				$msg_erro .= " Insira nome do produto\n ";
			}

			if (strlen($_POST['voltagem'])== 0){
				$msg_erro .= "Informe voltagem do produto.";
			}

			if (strlen($_POST['nome_revenda']) == 0) {
				$msg_erro .= "Informe a revenda.";
			}

		}

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if ($tab_atual == "reclamacao_at") {

		$reclamado = trim($_POST['reclamado_at']);
		$xserie    = $_POST['serie'];

		if (strlen($reclamado) == 0) {
			$msg_erro = "Insira a reclamação";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	$posto_nome          = $_POST['posto_nome'];
	$codigo_posto        = $_POST['codigo_posto'];
	$procon_posto_nome   = $_POST['procon_posto_nome'];
	$procon_codigo_posto = $_POST['procon_codigo_posto'];
	$reclamacao_procon   = $_POST['reclamacao_procon'];

	if(((!empty($posto_tab) and empty($codigo_posto_tab)) or empty($posto_tab)) AND $tab_atual != 'reclamacao_at'){
		$posto_tab = "";
		$posto_nome          = "";
		$codigo_posto        = "";
		$procon_posto_nome   = "";
		$procon_codigo_posto = "";

	}

	if ($login_fabrica == 2 AND $reclama_posto <> 'reclamacao_at') {
		$codigo_posto = "";
	}

	if ($login_fabrica == 94 AND $tab_atual == 'indicacao_at') {
		$posto_nome          = $_POST['posto_nome_ind'];
		$codigo_posto        = $_POST['codigo_posto_ind'];

		if(empty($codigo_posto)){
			$msg_erro = "Informe um Posto Autorizado para indicação";
		}

		$reclamado = $_POST['ind_posto_desc'];
	}

	if (strlen($codigo_posto_tab) > 0) {

		$sql = "SELECT posto from tbl_posto_fabrica where upper(codigo_posto) = upper('$codigo_posto_tab') and fabrica = $login_fabrica";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {

			$mr_codigo_posto = pg_fetch_result($res,0,0);

			$sql = "SELECT contato_endereco,
				contato_numero,
				contato_bairro,
				contato_cidade,
				contato_estado,
				contato_email,
				contato_fone_comercial
				FROM
					 tbl_posto_fabrica
				WHERE
					 posto = $mr_codigo_posto
				AND
					fabrica = $login_fabrica";
			$resm = pg_query($con, $sql);
			if (pg_num_rows($resm) > 0) {

				$posto_endereco = pg_fetch_result($resm,0,'contato_endereco');
				$posto_numero= pg_fetch_result($resm,0,'contato_numero');
				$posto_bairro = pg_fetch_result($resm,0,'contato_bairro');
				$posto_endereco_tab = $posto_endereco . ', ' . $posto_numero . ' - ' . $posto_bairro;
				$posto_cidade_tab = pg_fetch_result($resm,0,'contato_cidade');
				$posto_estado_tab = pg_fetch_result($resm,0,'contato_estado');
				$posto_fone_tab = pg_fetch_result($resm,0,'contato_fone_comercial');
				$posto_email_tab = pg_fetch_result($resm,0,'contato_email');

			}
		}
	}


	if (strlen($codigo_posto) == 0) {

		$xcodigo_posto = "null";

	} else {

		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$xcodigo_posto = pg_fetch_result($res, 0, 0);
		} else {
			$xcodigo_posto = "null";
		}

	}

	if ($login_fabrica == 11 && $tab_atual == "procon") {

		if (strlen($procon_codigo_posto) == 0) {//HD 55995

			$xcodigo_posto = "null";

		} else {

			$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$procon_codigo_posto' and fabrica = $login_fabrica";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$xcodigo_posto = pg_fetch_result($res, 0, 0);
			} else {
				$xcodigo_posto = "null";
			}

		}

	}

	$os = trim($_POST['os']);

	if (in_array($login_fabrica,array(74,85)) && strlen($os) > 0) {
 		$sql = "SELECT tbl_hd_chamado_extra.os FROM tbl_hd_chamado_extra WHERE hd_chamado = {$callcenter} AND os = {$os}";
 		$res = pg_query($con, $sql);

 		if (pg_num_rows($res) == 0) {
 			unset($os);
 		}
 	}

	if (strlen($os) == 0) {

		$xos = "null";

		if($login_fabrica == 85){
			$xos = trim($_POST['xos']);
		}

	} else {

		if (!in_array($login_fabrica,array(74,85))){

			$sql = "SELECT os from tbl_os where sua_os = '$os' and fabrica = $login_fabrica";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$xos = pg_fetch_result($res, 0, 0);
			} else {
				$msg_erro .= "OS {$os} informada não encontrada no sistema";
			}
		} else {
			$xos = $os;
		}
	}



	if ((strlen($xos) == 0 or $xos == 'null') and $_POST['os_ressarcimento'] != '') {

		$xos = $_POST['os_ressarcimento'];
	}

	if ($tab_atual == "reclamacao_empresa") {

		$reclamado = trim($_POST['reclamado_empresa']);

		if (strlen($reclamado) == 0) {
			$msg_erro = "Insira a reclamação";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if ($tab_atual == "reclamacoes") {

		$reclamado      = trim($_POST['reclamado']);
		$tipo_reclamado = trim($_POST['tipo_reclamacao']);

		if (strlen($reclamado) == 0) {
			$msg_erro = "Insira a reclamação";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if ($login_fabrica == 2) {

		if ($tab_atual == "reclamacao_produto") {

			$reclamado      = trim($_POST['reclamado_produto']);
			$tipo_reclamado = trim($_POST['tipo_reclamacao']);

			if (strlen($reclamado) == 0) {
				$msg_erro = "Insira a reclamação";
			} else {
				$xreclamado = "'".$reclamado."'";
			}

		}

		if ($tab_atual == "reclamacoes") {

			$reclamado      = trim($_POST['reclamado']);
			$tipo_reclamado = trim($_POST['tipo_reclamacao']);

			if (strlen($reclamado) == 0) {
				$msg_erro = "Insira a reclamação";
			} else {
				$xreclamado = "'".$reclamado."'";
			}

		}

		if ($tab_atual == "duvida_produto") {

			$reclamado      = trim($_POST['faq_duvida_duvida']);
			$tipo_reclamado = trim($_POST['tipo_reclamacao']);

			if (strlen($reclamado) == 0) {
				$msg_erro = "Insira a reclamação";
			} else {
				$xreclamado = "'".$reclamado."'";
			}

		}

	}

	if ($tab_atual == "sugestao") {

		$reclamado = trim($_POST['reclamado_sugestao']);

		if (strlen($reclamado) == 0) {
			$msg_erro .= "Insira a sugestão";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if ($tab_atual == "assistencia") {

		$produto_referencia = $_POST['produto_referencia_pa'];
		$produto_nome       = $_POST['produto_nome_pa'];
		$xserie             = $_POST['serie_pa'];
		$reclamado          = trim($_POST['reclamado_pa']);

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if ($tab_atual == "procon") {

		$reclamado = trim($_POST['reclamado_procon']);

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

		if (strlen($reclamacao_procon) > 0) {

			$sub_reclamacao_procon = array("pr_reclamacao_at","pr_info_at","pr_mau_atend","pr_posto_n_contrib","pr_demonstra_desorg","pr_bom_atend","pr_demonstra_org");

			if (in_array($reclamacao_procon, $sub_reclamacao_procon)) {
				$tab_atual = $reclamacao_procon;
			}

		}

	}

	if ($tab_atual == "garantia") {

		$produto_referencia = $_POST['produto_referencia_garantia'];
		$produto_nome       = $_POST['produto_nome_garantia'];
		$xserie             = $_POST['serie_garantia'];
		$reclamado          = trim($_POST['reclamado_produto_garantia']);

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if ($tab_atual == "troca_produto") {

		$produto_referencia = $_POST['troca_produto_referencia'];
		$produto_nome       = $_POST['troca_produto_nome'];
		$reclamado          = trim($_POST['troca_produto_descricao']);
		$xserie             = $_POST['troca_serie'];

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

		if (strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0) {
			$msg_erro = "Por favor Escolha o produto.";
		}

	}

	if ($tab_atual == 'informacao' || $tab_atual == 'reclamacao'){
		$reclamado          = trim($_POST['reclamado_produto']);

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}


	}

	$xrevenda      = 'null';
	$xrevenda_nome = "''";

	if ($tab_atual == "onde_comprar") {

		$revenda          = $_POST['revenda'];
		$revenda_cnpj     = $_POST['revenda_cnpj'];
		$revenda_nome     = substr(trim($_POST['revenda_nome']), 0, 50);
		$revenda_endereco = trim($_POST['revenda_endereco']);
		$revenda_nro      = trim($_POST['revenda_nro']);
		$revenda_cmpto    = trim($_POST['revenda_cmpto']);
		$revenda_bairro   = trim($_POST['revenda_bairro']);
		$revenda_city     = trim($_POST['revenda_city']);
		$revenda_uf       = trim($_POST['revenda_uf']);
		$revenda_fone     = trim($_POST['revenda_fone']);
		$reclamado        = trim($_POST['reclamado_onde_comprar']);

		$xrevenda      = ($revenda != '') ? $revenda : 'null';

		$xrevenda_nome = "'$xrevenda_nome'";

	} else if($login_fabrica == 94 AND $tab_atual == "indicacao_rev"){

		$revenda_ind          = $_POST['revenda_ind'];
		$revenda_ind_cnpj     = $_POST['revenda_ind_cnpj'];
		$revenda_ind_nome     = substr(trim($_POST['revenda_ind_nome']), 0, 50);

		if(empty($revenda_ind_nome) or empty($revenda_ind_cnpj)){
			$msg_erro = "Informe uma Revenda para indicação";
		} else {
			$xrevenda		= $revenda_ind;
			$revenda_cnpj   = $revenda_ind_cnpj;
			$revenda_nome	= $revenda_ind_nome;
		}

		$xrevenda_nome = "'$revenda_nome'";
		$reclamado = $_POST['ind_revenda_desc'];

	}else {

		$revenda          = $_POST['revenda_tab'];

		if(strlen($revenda) > 0){
			$xrevenda = $revenda;
		}

		if (strlen($nome_revenda)) {
			$xrevenda_nome = "'$nome_revenda'";
		}

	}


	if ($tab_atual == "ressarcimento") {

		$banco             = trim($_POST['banco']);
		$agencia           = trim($_POST['agencia']);
		$contay            = trim($_POST['contay']);
		$nomebanco         = trim($_POST['nomebanco']);
		$tipo_conta        = trim($_POST['tipo_conta']);
		$favorecido_conta  = trim($_POST['favorecido_conta']);
		$cpf_conta         = trim($_POST['cpf_conta']);
		$reclamado         = trim($_POST['obs_ressarcimento']);

		$valor_produto     = trim($_POST['valor_produto']);
		$valor_inpc        = trim($_POST['valor_inpc']);
		$valor_corrigido   = trim($_POST['valor_corrigido']);

		$reclamado         = trim($_POST['troca_produto_descricao']);

		$data_pagamento    = trim($_POST['data_pagamento']);
		$procon            = trim($_POST['procon']);
		$numero_processo   = trim($_POST['numero_processo']);

		$valor_produto     = str_replace(",",".",$valor_produto);
		$valor_inpc        = str_replace(",",".",$valor_inpc);
		$valor_corrigido   = str_replace(",",".",$valor_corrigido);

		if (strlen($xos) == 0 or $xos == 'null') {
			$msg_erro .= "Para fazer o ressarcimento é necesário ter uma Ordem de Serviço Aberta";
		}

		if (strlen($banco) == 0) {
			$xbanco = "null";
		} else {
			$xbanco = "'".$banco."'";
		}

		if (strlen($agencia) == 0) {
			$xagencia = "null";
		} else {
			$xagencia = "'".$agencia."'";
		}

		if (strlen($contay) == 0) {
			$xcontay = "null";
		} else {
			$xcontay = "'".$contay."'";
		}

		if (strlen($nomebanco) == 0) {
			$xnomebanco = "null";
		} else {
			$xnomebanco = "'".$nomebanco."'";
		}

		if (strlen($tipo_conta) == 0) {
			$xtipo_conta = "null";
		} else {
			$xtipo_conta = "'".$tipo_conta."'";
		}

		if (strlen($favorecido_conta) == 0) {
			$xfavorecido_conta = "null";
		} else {
			$xfavorecido_conta = "'".$favorecido_conta."'";
		}

		if (strlen($cpf_conta) == 0) {
			$xcpf_conta = "null";
		} else {
			$xcpf_conta = "'".$cpf_conta."'";
		}

		if (strlen($obs_conta) == 0) {
			$xobs_conta = "null";
		} else {
			$xobs_conta = "'".$obs_conta."'";
		}

		if (strlen($data_pagamento) == 0) {
			$xdata_pagamento = "null";
		} else {
			$xdata_pagamento = "'".$data_pagamento."'";
		}

	}

	if ($tab_atual == "sedex_reverso") {

		$troca_produto_referencia = trim($_POST['troca_produto_referencia']);
		$troca_produto_nome       = trim($_POST['troca_produto_nome']);
		$reclamado                = trim($_POST['troca_observacao']);
		$numero_objeto            = trim($_POST['numero_objeto']);
		$nota_fiscal_saida        = trim($_POST['nota_fiscal_saida']);
		$data_nf_saida            = trim($_POST['data_nf_saida']);
		$data_retorno_produto     = trim($_POST['data_retorno_produto']);
		$procon                   = trim($_POST['procon2']);
		$numero_processo          = trim($_POST['numero_processo2']);

		if (strlen($nota_fiscal_saida) == 0) {
			$xnota_fiscal_saida = "null";
		} else {
			$xnota_fiscal_saida = "'".$nota_fiscal_saida."'";
		}

		if (strlen($data_nf_saida) == 0) {
			$xdata_nf_saida = "null";
		} else {
			$xdata_nf_saida = "'".converte_data($data_nf_saida)."'";
		}

		if (strlen($data_retorno_produto) == 0) {
			$xdata_retorno_produto = "null";
		} else {
			$xdata_retorno_produto = "'".converte_data($data_retorno_produto)."'";
		}

		if (strlen($numero_objeto) == 0) {
			$xnumero_objeto = "null";
		} else {
			$xnumero_objeto = "'".$numero_objeto."'";
		}

		if (strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0) {
			$msg_erro = "Por favor Escolha o produto.";
		}

	}

	if ($tab_atual == "informacoes") {

		$reclamado = trim($_POST['informacoes_cliente']);

		if (strlen($reclamado) == 0) {
			$msg_erro .= "Insira as informações <br />";
		} else {
			$xreclamado = "'".$reclamado."'";

			if($login_fabrica == 74){

				if($status_interacao == "PROTOCOLO DE INFORMACAO" && strlen($_POST['informacoes_cliente']) == 0){
					$msg_erro .= "Insira as informações do Cliente <br />";
				}
			}

		}

	}

	$array_campos_adicionais = "''"	;
	if ($login_fabrica == 81) {
		$referencia_adi          = trim($_POST['referencia_adi']);
		$descricao_adi           = trim($_POST['descricao_adi']);
		$qtde_adi                = trim($_POST['qtde_adi']);
		$recebido                = trim($_POST['recebido']);
		$analise                 = trim($_POST['analise']);
		$validade                = trim($_POST['validade']);
		$lote                    = trim($_POST['lote']);
		$origem_adi              = trim($_POST['origem_adi']);
		$aco_de                  = trim($_POST['aco_de']);
		$procedente              = trim($_POST['procedente']);
		$reposicao               = trim($_POST['reposicao']);
		$disposicao              = trim($_POST['disposicao']);
		$descricao_analise       = trim($_POST['descricao_analise']);
		$valor_nf 		 = trim($_POST['valor_nf']);

		$array_campos_adicionais = array(
			"referencia_adi"    => $referencia_adi,
			"descricao_adi"     => $descricao_adi,
			"qtde_adi"          => $qtde_adi,
			"recebido"          => $recebido,
			"analise"           => $analise,
			"validade"          => $validade,
			"lote"              => $lote,
			"origem_adi"        => $origem_adi,
			"aco_de"            => $aco_de,
			"procedente"        => $procedente,
			"reposicao"         => $reposicao,
			"disposicao"        => $disposicao,
			"descricao_analise" => $descricao_analise
		);
	}

	if (in_array($login_fabrica, array(81, 114, 122, 123, 128))) {
		if ($login_fabrica != 81) {
			$array_campos_adicionais = array();
		}

		$origem_reclamacao       = $_POST["origem_reclamacao"];
		$origem_reclamacao_outro = trim($_POST["origem_reclamacao_outro"]);

		sort($origem_reclamacao);

		$array_campos_adicionais["origem_reclamacao"] = $origem_reclamacao;

		if (!count($origem_reclamacao)) {
			$msg_erro = "Selecione uma origem de reclamação";
		} else {
			if (in_array("outro", $array_campos_adicionais["origem_reclamacao"])) {

				if (!strlen(trim($origem_reclamacao_outro))) {
					$msg_erro = "Digite a outra origem de reclamação";
				} else {
					$array_campos_adicionais["origem_reclamacao_outro"] = $origem_reclamacao_outro;
				}
			}

			foreach ($array_campos_adicionais as $key => $value) {
				if (!is_array($value)) {
					$array_campos_adicionais[$key] = utf8_encode($value);
				}
			}

			$array_campos_adicionais = str_replace("\\", "\\\\", json_encode($array_campos_adicionais));
		}
	}

	if ($login_fabrica == 74) {

		$fone_revenda = trim($_POST['fone_revenda']);
		$data_visita_tecnico = trim($_POST['data_visita_tecnico']);
		$fone_revenda2 = trim($_POST['fone_revenda2']);
		$dt_fabricacao = trim($_POST['dt_fabricacao']);

		$array_campos_adicionais =  array("data_visita_tecnico" => $data_visita_tecnico,
											"data_fabricacao"   => $dt_fabricacao,
											"fone_revenda"   => $fone_revenda,
											"fone_revenda2"   => $fone_revenda2
									);
		$array_campos_adicionais = json_encode($array_campos_adicionais);
		$garantia_produto = $_POST['garantia_produto'];
	}

	if ($login_fabrica == 11) {
		$array_campos_adicionais = array();
		$data_programada     = $_POST["data_programada"];
        $ant_data_programada = $array_campos_adicionais['data_programada'];
		if (strlen($data_programada) > 0) {
			list($dp, $mp, $yp) = explode("/", $data_programada);

			if (!checkdate($mp, $dp, $yp)) {
				$msg_erro = "A data programada informada é inválida";
			} else {
				if ($status_interacao == "Resolvido") {
					$leitura_pendente = "false";
				} else {
					$leitura_pendente = "true";
				}
			}
		} else {
			$leitura_pendente = "false";
		}

		$array_campos_adicionais['data_programada'] = $data_programada;
		if(!empty($callcenter)) {
            $sql = 'SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $1;';
            $result = pg_query_params($con,$sql,array($callcenter));
            if($result && pg_num_rows($result) == 1){
                $json = json_decode(pg_fetch_result($result,0,'array_campos_adicionais'),true);
                if($json['data_programada'] != $data_programada){
                    $array_campos_adicionais['admin_agendamento'] = $login_admin;
                    $msg_alteracao_data = "A Data Programada foi alterada de <b>".$json['data_programada']."</b> para <b>$data_programada</b> ";

                    $sql = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado   ,
                            data         ,
                            comentario   ,
                            admin        ,
                            interno      ,
                            status_item
                        ) values (
                            $hd_chamado       ,
                            current_timestamp ,
                            E'$msg_alteracao_data',
                            $login_admin      ,
                            't'  ,
                            $xstatus_interacao
                        )";

                    $res = pg_query($con,$sql);
                }else{
                    $sqlVer = " SELECT  status
                                FROM    tbl_hd_chamado
                                WHERE   fabrica     = $login_fabrica
                                AND     hd_chamado  = $callcenter
                    ";
                    $resVer = pg_query($con,$sqlVer);
                    $verStatus = pg_fetch_result($resVer,0,status);
                    if($verStatus == "Resolvido" && $status_interacao != "Resolvido"){
                        $array_campos_adicionais['admin_agendamento'] = $login_admin;
                    }else{
                        $array_campos_adicionais['admin_agendamento'] = $json['admin_agendamento'];
                    }
                }
            }else{
                $array_campos_adicionais['admin_agendamento'] = $login_admin;
            }
            $array_campos_adicionais = json_encode($array_campos_adicionais);
		}
	}

	if (strlen($valor_produto) == 0) {
		$xvalor_produto = "null";
	} else {
		$xvalor_produto = $valor_produto;
	}

	if (strlen($valor_inpc) == 0) {
		$xvalor_inpc = "null";
	} else {
		$xvalor_inpc = $valor_inpc;
	}

	if (strlen($valor_corrigido) == 0) {
		$xvalor_corrigido = "null";
	} else {
		$xvalor_corrigido = $valor_corrigido;
	}

	if (strlen($numero_processo) == 0) {
		$xnumero_processo = "null";
	} else {
		$xnumero_processo = "'".$numero_processo."'";
	}

	if (strlen($cliente) == 0) {
		$cliente = "null";
	}

	if (strlen($faq_situacao) > 0) {
		$produto_referencia = $_POST['produto_referencia'];
	}

	if (strlen($_POST['produto_referencia']) > 0 && $login_fabrica == 30) { //hd 311031
		$produto_referencia = $_POST['produto_referencia'];
	}

	if (strlen($defeito_reclamado) == 0) {
		$xdefeito_reclamado  = "null";
	} else {
		$xdefeito_reclamado = $defeito_reclamado;
	}

	if ($login_fabrica <> 2) {

		if (strlen($reclamado) == 0) {
			$xreclamado = "null";
		} else {
			$xreclamado = "'".$reclamado."'";
		}

	}

	if(strlen($valor_nf)>0) {
		$xvalor_nf =  str_replace('.','',$valor_nf);
		$xvalor_nf =  str_replace(',','.',$xvalor_nf);
	} else {
		$xvalor_nf = 'null';
	}
//	echo $xvalor_nf; die;
	if (strlen($produto_referencia) > 0) {

		if ($login_fabrica == 96) {
			$cond_produto = "tbl_produto.referencia_fabrica = '$produto_referencia'";
		} else {
			$cond_produto = "tbl_produto.referencia = '$produto_referencia'";
		}

		$sql = "SELECT tbl_produto.produto
				FROM  tbl_produto
				join  tbl_linha on tbl_produto.linha = tbl_linha.linha
				WHERE $cond_produto
				and tbl_linha.fabrica = $login_fabrica
				limit 1";

		$res = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (pg_num_rows($res) > 0) {

			$xproduto = pg_fetch_result($res,0,0);

		} else {

			if ($tab_atual == "reclamacao_produto"){
				$msg_erro = "Produto $produto_referencia informado não encontrado no sistema <br>";
			}

			$xproduto = "null";

		}

	} else {


		if (in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)) {
			$msg_erro .= "Informe o produto para o atendimento <br>";


		}else{
			$xproduto = "null";
		}

	}

	if (strlen($produto_nome) == 0 and in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)){
		$msg_erro .= "Informe a descrição do produto para o atendimento <br>";
	}

	if (in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)) {
		if (empty($_POST['reclamado_produto'])){
			$msg_erro .= "Informe a descrição do defeito reclamado <br>";
		}
	}

	if (in_array($login_fabrica, $fabricas_validam_campos_obrigatorios)) {

		if (empty($_POST['defeito_reclamado'])) {
			$msg_erro .= "Informe o defeito reclamado do produto <br>";
		}


	}

	if (strlen($troca_produto_referencia) > 0) {

		$sql = "SELECT tbl_produto.produto
					FROM  tbl_produto
					join  tbl_linha on tbl_produto.linha = tbl_linha.linha
					WHERE tbl_produto.referencia = '$troca_produto_referencia'
					and tbl_linha.fabrica = $login_fabrica
					limit 1";

		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (pg_num_rows($res) > 0) {
			$xproduto_troca = pg_fetch_result($res,0,0);
		} else {
			$xproduto_troca = "null";
		}

	} else {

		$xproduto_troca = "null";

	}

	if (strlen($faq_situacao) > 0) {//HD 45991

		$sql = "INSERT INTO tbl_faq (
					situacao,
					produto
				) VALUES (
					'$faq_situacao',
					$xproduto
				);";

		$res = @pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0) {

			$sql = "SELECT email_cadastros FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

				$email_cadastros = pg_fetch_result($res, 0, 'email_cadastros');
				$admin_email     = "suporte@telecontrol.com.br";
				$remetente       = $admin_email;
				$destinatario    = $email_cadastros ;
				$assunto         = "Nova dúvida cadastrada";
				$mensagem        = "Prezado, <br> Foi cadastrada uma nova dúvida no sistema para o produto $produto_referencia:<br>  - $faq_situacao <br><br>Por favor, entre na aba <b>Cadastro - Perguntas Frequentes</b> para cadastrar causa e solução da mesma. <br>Att <br>Equipe Telecontrol";
				$headers  = "MIME-Version: 1.0 \r\n";
				$headers .= "Content-type: text/html \r\n";
				$headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";
				mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

			}

		}

	}

	#HD Chamado 13106 Bloqueia
	#HD Chamado 21419 DESBloqueia
	if ($login_fabrica == 25 AND strlen($xserie) > 0 AND 1 == 2) {

		$sql = "SELECT tbl_hd_chamado_extra.hd_chamado
				FROM tbl_hd_chamado_extra
				JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
				WHERE tbl_hd_chamado.fabrica        = $login_fabrica
				AND   tbl_hd_chamado_extra.serie    = '$xserie' ";
				//AND   tbl_hd_chamado_extra.produto  = $xproduto

		if (strlen($callcenter) > 0) {
			$sql .= " AND tbl_hd_chamado_extra.hd_chamado <> $callcenter ";
		}

		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (pg_num_rows($res) > 0) {

			$hd_chamado_serie = pg_fetch_result($res,0,0);
			$msg_erro .= "Número de série $xserie já cadastrado anteriormente. Número do chamado: <a href='$PHP_SELF?callcenter=$hd_chamado_serie' target='_blank'>$hd_chamado_serie</a> ";

		}

	}

	if (strlen($xserie) == 0) {
		$xserie = "null";
	} else {
		$xserie = "'".$xserie."'";
	}

	if ($login_fabrica == 11) { // HD 45078

		$xconsumidor_nome        = acentos1($xconsumidor_nome);
		$xconsumidor_nome        = acentos3($xconsumidor_nome);
		$xconsumidor_endereco    = acentos1($xconsumidor_endereco);
		$xconsumidor_endereco    = acentos3($xconsumidor_endereco);
		$xconsumidor_numero      = acentos1($xconsumidor_numero);
		$xconsumidor_numero      = acentos3($xconsumidor_numero);
		$xconsumidor_complemento = acentos1($xconsumidor_complemento);
		$xconsumidor_complemento = acentos3($xconsumidor_complemento);
		$xconsumidor_bairro      = acentos1($xconsumidor_bairro);
		$xconsumidor_bairro      = acentos3($xconsumidor_bairro);
		$xconsumidor_cidade      = acentos1($xconsumidor_cidade);
		$xconsumidor_cidade      = acentos3($xconsumidor_cidade);
		$xconsumidor_email       = acentos1($xconsumidor_email);
		$xconsumidor_email       = acentos3($xconsumidor_email);

	}

	$data_nf = $_POST["data_nf"];
	$data_nf_anterior = $_POST["data_nf_anterior"];

	if (strlen($data_nf) == 0){
		$xdata_nf = "NULL";
	}else{
		list($ddf, $mdf, $ydf) = explode("/", $data_nf);

		if(!checkdate($mdf,$ddf,$ydf)) {
			$msg_erro .= "Data NF Inválida";
		}else{
			if(strtotime("{$ydf}-{$mdf}-{$ddf}") > strtotime('today')) {
				$msg_erro .= "Data NF não pode ser maior que a data atual <br />";
			} else {
				$xdata_nf = "'".$ydf.'-'.$mdf.'-'.$ddf."'";
			}
		}
	}

	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	/********************************* VALIDACOES DA ABERTURA DE OS *********************************/
	if (strlen($msg_erro) == 0 && $abre_ordem_servico == 't') {

		if (strlen($mr_codigo_posto) == 0) {
			$msg_erro = "Para que a ORDEM DE SERVIÇO seja aberta é necessário escolher um posto";
		}

		if (strlen($mr_codigo_posto) > 0) {

			$xcodigo_posto    = $mr_codigo_posto;
		}


		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));

		if(empty($valida_cpf_cnpj)){
			$consumidor_cpf = checaCPF($consumidor_cpf, false, true);

			if ($consumidor_cpf === false) {

				$consumidor_cpf = 'null';

				if ($consumidor_cpf == "nul" and strlen($_POST['consumidor_cpf']) != 0) {
					$msg_erro = "CPF do consumidor inválido";
				}

			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}

	}

	//HD 211895: Separando rotina de PRÉ-OS das rotinas de inserção e atualização
	//			 As validações estão no começo do código, a a inserção depois das rotinas de inserir/atualizar
	/************************************* VALIDACOES DA PRE-OS *************************************/
	if ($login_fabrica == 52) {

		$cliente_admin      = $_POST['cliente_admin'];
		$cliente_nome_admin = trim($_POST['cliente_nome_admin']);

		if (strlen($cliente_admin) == 0) {
			$msg_erro = "Informe o Cliente Fricon";
		}

		if (strlen($cliente_nome_admin) > 0) {
			$sql = "SELECT count(*) from tbl_cliente_admin where trim(nome) = '$cliente_nome_admin' and fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if (pg_fetch_result($res, 0, 0) == 0) {
				$msg_erro = "Cliente Fricon '$cliente_nome_admin' não existe";
			}
		}

		if (strlen($msg_erro) == 0 and strlen($callcenter) == 0 and $abre_ordem_servico == 't') {

			if (strlen($posto_km_tab) == 0) {
				$msg_erro .= "É necessario digitar a Qtde de Km, clique em Mapa da Rede<br>";
			}

			if ($xserie <> 'null') {

				$sql = "SELECT serie from tbl_numero_serie where serie = $xserie and fabrica = 52";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) == 0) {
					$msg_erro .="Numero de Série Inválido, preencha corretamente ou deixe em branco o campo série";
				}

			}

		}
		//PARA A FRICON A REVENDA VAI SER O CLIENTE FRICON
		//echo $xrevenda;exit;
		if (!empty($cliente_admin)){

			$sql = "SELECT nome,cnpj,fone,cidade,estado FROM tbl_cliente_admin WHERE cliente_admin = $cliente_admin";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res)>0){
				$xrevenda = $cliente_admin;
				$xrevenda_nome = "'".pg_fetch_result($res, 0, 'nome')."'";
				$cnpj_revenda  = pg_fetch_result($res, 0, 'cnpj');
				$fone_revenda  = pg_fetch_result($res, 0, 'fone');
				$cidade_revenda  = trim(pg_fetch_result($res, 0, 'cidade'));
				$estado_revenda  = trim(pg_fetch_result($res, 0, 'estado'));

				$sql = "SELECT revenda,cnpj,nome,fone FROM tbl_revenda where cnpj = '$cnpj_revenda' ";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)==0){

					$sql = "SELECT cidade from tbl_cidade where nome = '$cidade_revenda' and estado = '$estado_revenda' limit 1 ";
					$res = pg_query($con,$sql);

					$cidade_cli_admin = pg_fetch_result($res, 0, 0);
					if(!empty($cidade_cli_admin)) {
						$sql = "INSERT INTO tbl_revenda (
									nome,
									numero,
									complemento,
									cidade,
									cep,
									cnpj
								)SELECT
										tbl_cliente_admin.nome,
										tbl_cliente_admin.numero,
										tbl_cliente_admin.complemento,
										$cidade_cli_admin,
										tbl_cliente_admin.cep,
										tbl_cliente_admin.cnpj
									FROM tbl_cliente_admin
									WHERE cliente_admin = $cliente_admin";

						// echo nl2br($sql);
						$res = pg_query($con,$sql);
						$msg_erro = pg_last_error($con);
						// echo $msg_erro;
						// exit;
						if (empty($msg_erro)) {
							$sql = "SELECT nome,cnpj,fone,revenda FROM tbl_revenda where cnpj = '$cnpj_revenda'";
							$res = pg_query($con,$sql);
							$xrevenda      = pg_fetch_result($res, 0, 'revenda');
							$xrevenda_nome = "'".pg_fetch_result($res, 0, 'nome')."'";
							$cnpj_revenda  = pg_fetch_result($res, 0, 'cnpj');
							$fone_revenda  = pg_fetch_result($res, 0, 'fone');
						}
					}
				}else{
					$xrevenda = pg_fetch_result($res, 0, 'revenda');
					$xrevenda_nome = "'".pg_fetch_result($res, 0, 'nome')."'";
					$cnpj_revenda  = pg_fetch_result($res, 0, 'cnpj');
					$fone_revenda  = pg_fetch_result($res, 0, 'fone');
				}
			}

		}

	}

	if($xrevenda == "null" and strlen($cnpj_revenda) > 5 ){
		$sql = "SELECT nome,cnpj,fone,revenda FROM tbl_revenda where cnpj = '$cnpj_revenda'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){ 
				$xrevenda      = pg_fetch_result($res, 0, 'revenda');
				$xrevenda_nome = "'".pg_fetch_result($res, 0, 'nome')."'";
				$cnpj_revenda  = pg_fetch_result($res, 0, 'cnpj');
				$fone_revenda  = pg_fetch_result($res, 0, 'fone');
		}else{
			$sql = "INSERT INTO tbl_revenda (
							nome,
							cnpj
					)values(
						$xrevenda_nome,	
						'$cnpj_revenda'
					) returning revenda";
			$res = pg_query($con,$sql);
		    $xrevenda      = pg_fetch_result($res, 0, 'revenda');
		}
	}

	if (strlen($msg_erro) == 0 and $abre_os == 't') {

		if (strlen($mr_codigo_posto) == 0) {
			$msg_erro = "Para que a PRÉ-OS seja aberta é necessário escolher um posto";
		}

		if (strlen($mr_codigo_posto) > 0) {

			$xcodigo_posto    = $mr_codigo_posto;
		}

		if (strlen($mr_codigo_posto) == 0) {

			if ($login_fabrica == 30) {
				$msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
			} else {
				$msg_erro = "Para que a PRÉ-OS seja aberta é necessário escolher um posto";
			}

		}

		if(strlen($produto_referencia) == 0){
			$msg_erro = "Para que a PRÉ-OS seja aberta é necessário escolher um produto";
		}

		$xnota_fiscal   = "'".$_POST["nota_fiscal"]."'";
		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));

		if(empty($valida_cpf_cnpj)){
			$consumidor_cpf = checaCPF($consumidor_cpf,false,true);

			if ($consumidor_cpf === false) {

				$consumidor_cpf = 'null';

				if ($consumidor_cpf == "nul" and strlen($_POST['consumidor_cpf']) != 0) {
					$msg_erro = "CPF do consumidor inválido";
				}

			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}

		if (strlen($msg_erro) == 0) {

			if (strlen($data_nf) == 0){
				$xdata_nf = "NULL";
			}else{
				list($ddf, $mdf, $ydf) = explode("/", $data_nf);
				if(!checkdate($mdf,$ddf,$ydf)) {
					$msg_erro = "Data NF Inválida";
				}else{
					$xdata_nf = "'".$ydf.'-'.$mdf.'-'.$ddf."'";
				}

			}

		}

	}

	//HD 932838
	    if($login_fabrica==59 AND !empty($xproduto) and $xserie <> 'null') {
		    $sql = "SELECT fn_serie_controle_sightgps($xproduto,$login_fabrica,$xserie)";
		    $res = pg_query($con,$sql);
		    $msg_erro = pg_last_error($con);
	    }

	if(strlen($xos) == 0){
		$xos = "null";
	}

	/******************************************* INSERÇÃO *******************************************/

	if (strlen($callcenter) == 0) {

		if (strlen($msg_erro) == 0) {
			$res = pg_query ($con, "BEGIN TRANSACTION");

			if (strlen($consumidor_nome) > 0 and strlen($consumidor_estado) > 0 and strlen($consumidor_cidade) > 0) {

				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
						$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

						$sql = "INSERT INTO tbl_cidade (
									nome, estado
								) VALUES (
									'{$cidade_ibge}', '{$cidade_estado_ibge}'
								) RETURNING cidade";
						$res = pg_query($con, $sql);

						$cidade = pg_fetch_result($res, 0, "cidade");
					} else {
						$msg_erro .= "Cidade do consumidor não encontrada";
					}
				}
			} else if($indicacao_posto == 'f') {
				$msg_erro .= "Informe a cidade do consumidor";
			}

		}

		if ($tab_atual == 'reclamacoes') {
			$tab_atual = $tipo_reclamado;
		}

		if ($login_fabrica == 2) {

			if ($tab_atual == "reclamacao_produto") {

				$up_tipo_reclamacao = array("aquisicao_mat","aquisicao_prod","indicacao_posto","solicitacao_manual");

				if (in_array($tipo_reclamacao, $up_tipo_reclamacao)) {
					$tab_atual = $tipo_reclamacao;
				}

				if ($tab_atual == 'reclamacao_produto') {
					$tab_atual = $tipo_reclamado;
				}

			}

			if ($tab_atual == "reclamacoes") {

				$up_tipo_reclamacao = array("reclamacao_revenda","reclamacao_at","reclamacao_enderecos","reclamacao_produto",
					"reclamacao_conserto", "reclamacao_posto_aut", "reclamacao_orgao_ser", "repeticao_chamado", "reclamacao_outro");
				if (in_array($tipo_reclamacao, $up_tipo_reclamacao)) {
					$tab_atual = $tipo_reclamacao;
				}

				if ($tab_atual == 'reclamacoes') {
					$tab_atual = $tipo_reclamado;
				}

			}

			if ($tab_atual == "duvida_produto") {

				$up_tipo_reclamacao = array("especificacao_manuseio","informacao_manuseio","informacao_tecnica","orientacao_instalacao");

				if (in_array($tipo_reclamacao, $up_tipo_reclamacao)) {
					$tab_atual = $tipo_reclamacao;
				}

				if ($tab_atual == 'duvida_produto') {
					$tab_atual = $tipo_reclamado;
				}

			}

		}

		if (strlen($cliente_admin) == 0 and $login_fabrica <> 52) {
			$cliente_admin = 'null';
		}

		$protocolo = $_POST['protocolo_id'];

		if (strlen($callcenter_assunto) > 0 && is_array($callcenter_assunto)) { #HD 251241
			$callcenter_assunto = trim(implode("", $callcenter_assunto));
		}
		if ($login_fabrica == 24 and !in_array($tab_atual, array('outros_assuntos','procon','sugestao'))) {

			if (strlen($callcenter_assunto) > 0) {
				$achou_categoria_assunto = false;

				//Localiza o assunto dentro do array $assuntos (definido em callcenter_suggar_assuntos)
				foreach ($assuntos as $categoria_assunto => $itens_categoria_assunto) {

					foreach ($itens_categoria_assunto as $label_assunto => $bd_assunto) {

						if ($bd_assunto == $callcenter_assunto) {
							$categoria_banco         = $callcenter_assunto;
							$achou_categoria_assunto = true;
						}

					}

				}

				if ($achou_categoria_assunto == false) {
					$msg_erro .= "<br>Assunto não encontrado";
				}

			}/* else {

				if ($tab_atual <> 'outros_assuntos' AND $indicacao_posto == 'f') {
					$msg_erro .= "Escolha um assunto";
				}

			}*/

		} else {

			$categoria_banco = $tab_atual;

		}

		if (strlen($msg_erro) == 0 and strlen($callcenter) == 0 and strlen($protocolo) == 0) {

			$titulo = 'Atendimento interativo';

			if ($indicacao_posto == 't') $titulo = 'Indicação de Posto';

			if ($login_fabrica == 85 && $atendimento_ambev == "t") {
				$cliente_admin = $cliente_admin_ambev;
			}

			$sql = "INSERT INTO tbl_hd_chamado (
						admin                 ,
						cliente_admin         ,
						data                  ,
						status                ,
						atendente             ,
						fabrica_responsavel   ,
						titulo                ,
						categoria             ,
						fabrica
					) values (
						$login_admin            ,
						$cliente_admin           ,
						current_timestamp       ,
						$xstatus_interacao      ,
						$login_admin            ,
						$login_fabrica          ,
						'$titulo'               ,
						'$categoria_banco'            ,
						$login_fabrica
				)";
			$res       = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$res        = pg_query ($con, "SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_fetch_result($res,0,0);

		} else {

			if (strlen($msg_erro) == 0 and strlen($protocolo) > 0) {

				$sql = "UPDATE tbl_hd_chamado set
						admin                 = $login_admin,
						cliente_admin         = $cliente_admin,
						data                  = current_timestamp,
						status                = $xstatus_interacao,
						atendente             = $login_admin,
						fabrica_responsavel   = $login_fabrica,
						titulo                = '$titulo',
						categoria             = '$categoria_banco',
						fabrica               = $login_fabrica
						WHERE hd_chamado = $protocolo";

				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);

				if (strlen($msg_erro) == 0) {
					$hd_chamado = $protocolo;
				}

			}

		}

		if ($login_fabrica == 51) {//HD 816610

			include_once 'pesquisa_satisfacao_config.php';
			include_once 'pesquisa_satisfacao_post.php';

		}


		//HD 196942: Controle de postagem
		//o checkbox para solicitar postagem para o chamado só aparece quando não tem solicitação para aquele chamado, portanto só virá alguma coisa por POST em hd_chamado_postagem se não estiver cadastrado e se estiver com o checkbox marcado
		if ($_POST["hd_chamado_postagem"] == "sim" && strlen($hd_chamado) > 0 && strlen($msg_erro) == 0) {

			$sql = "SELECT hd_chamado FROM tbl_hd_chamado_postagem WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {

				$msg_erro = "Já existe solicitação de postagem cadastrada para este chamado. Não é permitido o cadastramento de mais de uma postagem para um mesmo chamado";

			} else {

				$sql = "INSERT INTO tbl_hd_chamado_postagem(hd_chamado) VALUES($hd_chamado)";
				$res = @pg_query($con, $sql);

				if (pg_errormessage($con)) {
					$msg_erro = "Houve um erro ao cadastrar a sua solicitação de postagem. Contate o HelpDesk";
				}

			}

		}

		if (strlen($msg_erro) == 0 and strlen($callcenter) == 0) {

			$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

			if (strlen($abre_os) == 0) {
				$abre_os = 'f';
			}

			$xabre_os = "'".$abre_os."'";

			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if (strlen($abre_ordem_servico) == 0) { $abre_ordem_servico = 'f';}
			$xabre_ordem_servico = "'".$abre_ordem_servico."'";

			$data_nf = $_POST["data_nf"] ;
			if (strlen($data_nf) == 0){
				$xdata_nf = "NULL";
			}else{
				list($ddf, $mdf, $ydf) = explode("/", $data_nf);
				if(!checkdate($mdf,$ddf,$ydf)) {
					$msg_erro = "Data NF Inválida";
				}else{
					$xdata_nf = "'".$ydf.'-'.$mdf.'-'.$ddf."'";
				}

			}

			if ($login_fabrica == 3) {

				if ($status_interacao == 'Resolvido' OR $status_interacao == 'Cancelado') {
					$tipo_registro ="Contato";
				} else if ($status_interacao == 'Aberto') {
					$tipo_registro ="Processo";
				}

			} else {
				$tipo_registro = "";
			}
			if (strlen($mr_codigo_posto) > 0 and ($tab_atual <> 'indicacao_at') and empty($codigo_posto)) {
				$xcodigo_posto = $mr_codigo_posto;
			}

			if (strlen($posto_km_tab) == 0) {
				$posto_km_tab = 'null';
			} else {
				$posto_km_tab = str_replace(',','.',$posto_km_tab);
			}

			if (strlen($atendimento_callcenter) > 0) {
				$condicao = ", atendimento_callcenter = '$atendimento_callcenter' ";
			}

			$contato_nome = !empty($contato_nome) ? $contato_nome : "";

			if (strlen($protocolo) == 0) {
				if ($login_fabrica == 85 || $login_fabrica == 24 || $login_fabrica == 15 ) {
					if ($login_fabrica == 85) {
						if ($atendimento_ambev == "t") {
							$array_campos_adicionais = array(
								"atendimento_ambev"       => $atendimento_ambev,
								"codigo_ambev"            => $codigo_ambev,
                                "data_fabricacao_ambev"   => $data_fabricacao_ambev,
								"data_encerramento_ambev" => $data_encerramento_ambev,
                                "atendimento_ambev_nome"  => $atendimento_ambev_nome
							);
						} else {
							$array_campos_adicionais = array(
								"atendimento_ambev"         => "f"                      ,
								"data_fabricacao_ambev"     => $data_fabricacao_ambev   ,
								"atendimento_ambev_nome"    => $atendimento_ambev_nome
							);
						}

						$array_campos_adicionais["consumidor_cpf_cnpj"] = $consumidor_cpf_cnpj;

						if ($consumidor_cpf_cnpj == "R") {
							$array_campos_adicionais["nome_fantasia"] = utf8_encode($nome_fantasia);
						}
						$array_campos_adicionais["voltagem"] = $voltagem;
					}
					if( $login_fabrica == 15) {
						if (strlen($aux_data_retorno) > 0) {
							$array_campos_adicionais = array(
								"data_retorno"       => $aux_data_retorno
							);

						}
					}


					if ($login_fabrica == 24 and strlen($aux_ligacao_agendada) > 5) {
						$array_campos_adicionais = array("ligacao_agendada" => $aux_ligacao_agendada);

					}

				 	$array_campos_adicionais = json_encode($array_campos_adicionais);

				}else if($login_fabrica >= 138){

					$array_campos_adicionais = json_encode(array("voltagem" => $voltagem));

				}

				/*
				Se um dia for necessario adicionar informações na variavel $array_campos_adicionais
				para a Cadence(35), lembrar que ja existe informação no campo, e não substituir, e sim
				adicionar.
				*/

				$sql = "INSERT INTO tbl_hd_chamado_extra(
							hd_chamado           ,
							reclamado            ,
							defeito_reclamado    ,
							serie                ,
							hora_ligacao         ,
							produto              ,
							posto                ,
							os                   ,
							receber_info_fabrica ,
							consumidor_revenda   ,
							origem               ,
							revenda              ,
							revenda_nome         ,
							data_nf              ,
							nota_fiscal          ,
							valor_nf             ,
							nome                 ,
							endereco             ,
							numero               ,
							complemento          ,
							bairro               ,
							cep                  ,
							fone                 ,
							fone2                ,
							email                ,
							cpf                  ,
							rg                   ,
							cidade               ,
							qtde_km              ,
							abre_os              ,
							defeito_reclamado_descricao,
							numero_processo      ,
							tipo_registro		 ,
							celular				 ,
							revenda_cnpj         ,
							contato_nome         ,";

				if ($login_fabrica == 43 or $login_fabrica == 14) {

					if (strlen($ordem_montagem) > 0) $sql.= " ordem_montagem,  ";

					$sql.= " codigo_postagem, ";

				}

				if (in_array($login_fabrica,array(11,74, 86))) {

					$sql.= " hd_motivo_ligacao, ";

				}

				if (in_array($login_fabrica, array(11,15 , 81, 74, 85, 24, 128, 122, 123, 114)) or $login_fabrica >= 138){
					$sql .= " array_campos_adicionais, ";
				}

				if ($login_fabrica == 11) {
					$sql .= " leitura_pendente, ";
				}

				if ($login_fabrica == 74 AND !empty($garantia_produto)){
					$sql .= " garantia, ";
				}

				if ($login_fabrica == 52){
					$sql .= " posto_nome,
							  marca, ";
				}

				if (strlen($xdata_nf) == 0){
	                                $xdata_nf = "NULL";
				}
				if (strlen($xdata_ag) == 0){
	                                $xdata_ag = "NULL";
				}

				$sql .= " atendimento_callcenter ";


				$sql .= " ) values (
						$hd_chamado                    ,
						$xreclamado                    ,
						$xdefeito_reclamado            ,
						substr($xserie,0,20)           ,
						$xhora_ligacao                 ,
						$xproduto                      ,
						$xcodigo_posto                 ,
						$xos                           ,
						$xreceber_informacoes          ,
						$xconsumidor_revenda           ,
						$xorigem                       ,
						$xrevenda                      ,
						substr($xrevenda_nome,0,51)    ,
						$xdata_nf                      ,
						$xnota_fiscal                  ,
						$xvalor_nf                     ,
						upper($xconsumidor_nome)       ,
						upper($xconsumidor_endereco)   ,
						upper($xconsumidor_numero)     ,
						upper($xconsumidor_complemento),
						upper($xconsumidor_bairro)     ,
						$xconsumidor_cep               ,
						$xconsumidor_fone              ,
						$xconsumidor_fone2             ,
						$xconsumidor_email             ,
						$xconsumidor_cpf               ,
						upper($xconsumidor_rg)         ,
						$cidade                        ,
						$posto_km_tab                  ,
						$xabre_os                      ,
						'$hd_extra_defeito'            ,
						$xnumero_processo              ,
						'$tipo_registro'			   ,
						$xconsumidor_fone3			   ,
						'$cnpj_revenda'                ,
						'$contato_nome'                ,";

				 if ($login_fabrica == 43 or $login_fabrica == 14) { #HD 251241

						 if (strlen($ordem_montagem) > 0) $sql.= " '$ordem_montagem' , ";

						 $sql.= " '$codigo_postagem', ";

				 }

				 if (in_array($login_fabrica,array(11,74,86))) {

						 $sql.= " $hd_motivo_ligacao, ";

				 }
				if (in_array($login_fabrica, array(11,15 , 81, 74, 85, 24, 128, 122, 123, 114)) or $login_fabrica >= 138){
					$sql .= " '$array_campos_adicionais', ";
				}

				if ($login_fabrica == 11) {
					$sql .= " '$leitura_pendente', ";
				}

				if ($login_fabrica == 74 AND !empty($garantia_produto)){
				 	$sql .= "'$garantia_produto', ";
				}

				if ($login_fabrica == 52){
					if ($marca<=0) {
						$marca = 'null';
					}
				 	$sql .= "$xconsumidor_ponto_referencia, $marca, ";
				}

				$sql .= "'$atendimento_callcenter'       ";

				$sql .=");";
			} else {

				if (strlen($xdata_nf) == 0){
	                                $xdata_nf = "NULL";
				}

				if($login_fabrica == 11 OR $login_fabrica == 86){
					$cond_motivo_ligacao = ", hd_motivo_ligacao = $hd_motivo_ligacao";
				}
				$sql = "UPDATE tbl_hd_chamado_extra SET
					reclamado            = E$xreclamado                    ,
					defeito_reclamado    = $xdefeito_reclamado            ,
					serie                = substr($xserie,0,21)           ,
					hora_ligacao         = $xhora_ligacao                 ,
					produto              = $xproduto                      ,
					posto                = $xcodigo_posto                 ,
					os                   = case when os notnull then os else $xos end                           ,
					receber_info_fabrica = $xreceber_informacoes          ,
					consumidor_revenda   = $xconsumidor_revenda           ,
					origem               = $xorigem                       ,
					revenda              = $xrevenda                      ,
					revenda_nome         = substr($xrevenda_nome,0,51)    ,
					data_nf              = $xdata_nf                      ,
					valor_nf             = $xvalor_nf                     ,
					nota_fiscal          = substr($xnota_fiscal,0,11)     ,
					nome                 = upper($xconsumidor_nome)       ,
					endereco             = upper($xconsumidor_endereco)   ,
					numero               = upper($xconsumidor_numero)     ,
					complemento          = upper($xconsumidor_complemento),
					bairro               = upper($xconsumidor_bairro),
					cep                  = $xconsumidor_cep               ,
					fone                 = substr($xconsumidor_fone,0,21) ,
					fone2                = substr($xconsumidor_fone2,0,21) ,
					email                = $xconsumidor_email             ,
					cpf                  = $xconsumidor_cpf               ,
					rg                   = upper($xconsumidor_rg)         ,
					cidade               = $cidade                        ,
					qtde_km              = $posto_km_tab                  ,";

				if (in_array($login_fabrica, array(11,15 , 81, 74, 128, 122, 123, 114))){
					$sql .= " array_campos_adicionais = '$array_campos_adicionais' , ";
				}

				if ($login_fabrica == 11) {
					$sql .= " leitura_pendente = '{$leitura_pendente}', ";
				}

				if ($login_fabrica == 74 AND !empty($garantia_produto)){
				 	$sql .= "garantia          = '$garantia_produto', ";
				}

				if ($login_fabrica == 52){
					$sql .= "posto_nome          = $xconsumidor_ponto_referencia,
							 marca 				 = $marca, ";
				}

				$sql .="
							abre_os              = $xabre_os                      ,
							defeito_reclamado_descricao= '$hd_extra_defeito'      ,
							numero_processo      = $xnumero_processo              ,
							tipo_registro        = '$tipo_registro'				  ,
							celular				 = $xconsumidor_fone3			  ,
							revenda_cnpj		 = '$cnpj_revenda'                ,
							contato_nome         = '$contato_nome'
							$condicao
							$cond_motivo_ligacao";

				$sql .= " WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado";

			}
			$res = pg_query($con,$sql);
			if(pg_last_error($con)){

				$msg_erro = "Erro ao gravar dados no banco!<br>";
			}
			if ($xstatus_interacao == "'Retorno'" AND $login_fabrica == 15 ) {

				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item  ,
							enviar_email
						) values (
							$hd_chamado       ,
							current_timestamp ,
							'Data para retorno : $xdata_retorno'       ,
							$login_admin      ,
							$xchamado_interno ,
							$xstatus_interacao,
							$xenvia_email
						)";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			if ($xstatus_interacao == "'Resolvido'" AND $login_fabrica <> 6) {

				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item  ,
							enviar_email
						) values (
							$hd_chamado       ,
							current_timestamp ,
							'Resolvido'       ,
							$login_admin      ,
							$xchamado_interno ,
							$xstatus_interacao,
							$xenvia_email
						)";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if (strlen($msg_erro) == 0) {

					$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
					$res = pg_query($con, $sql);

					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
					$res = pg_query($con, $sql);

					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
					$res = pg_query($con, $sql);

					$msg_erro .= pg_errormessage($con);

				}

			}

			if (isset($_POST['tipo_consumidor']) && !empty($hd_chamado)) { // HD 317864

				$sql = "UPDATE tbl_hd_chamado_extra SET tipo_consumidor = '" . $_POST['tipo_consumidor'] . "' WHERE hd_chamado = $hd_chamado";
				$res = pg_query($con,$sql);

			} else if (in_array($login_fabrica,$fab_usa_tipo_cons)) {

				$msg_erro = "Selecione o Tipo do Consumidor";

			}

			//IGOR - HD: 10441 QUANDO FOR INDICADO UM POSTO AUTORIZADO, DEVE-SE INSERIR UMA INTERAO NO CHAMADO
			if (strlen($posto_tab) > 0 and strlen($msg_erro) == 0) {

				if ($login_fabrica == 52){

					$sql = "SELECT contato_endereco,
									contato_numero,
									contato_bairro,
									contato_cidade,
									contato_estado,
									contato_email,
									contato_fone_comercial
							FROM
								 tbl_posto_fabrica
							WHERE
								 posto = $xcodigo_posto
							AND
								fabrica = $login_fabrica";

					$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0) {

							$posto_endereco = pg_fetch_result($res,0,'contato_endereco');
							$posto_numero= pg_fetch_result($res,0,'contato_numero');
							$posto_bairro = pg_fetch_result($res,0,'contato_bairro');
							$posto_endereco_tab = $posto_endereco . ', ' . $posto_numero . ' - ' . $posto_bairro;
							$posto_cidade_tab = pg_fetch_result($res,0,'contato_cidade');
							$posto_estado_tab = pg_fetch_result($res,0,'contato_estado');
							$posto_fone_tab = pg_fetch_result($res,0,'contato_fone_comercial');
							$posto_email_tab = pg_fetch_result($res,0,'contato_email');


						}
				}

					$comentario = "Indicação do posto mais próximo do consumidor: <br>
									Código: $codigo_posto_tab <br>
									Nome: $posto_nome_tab<br>
									Endereço: $posto_endereco_tab <br>
									Cidade: $posto_cidade_tab <br>
									Estado: $posto_estado_tab<br>
									Telefone: $posto_fone_tab<br>
									E-mail: $posto_email_tab";

					if($login_fabrica == 94 and $tab_atual == 'indicacao_at'){
						$sql_posto = "SELECT contato_endereco,
											contato_numero,
											contato_complemento,
											contato_cidade,
											contato_estado,
											contato_email,
											contato_fone_comercial
										FROM tbl_posto_fabrica
										WHERE codigo_posto = '$codigo_posto'
										AND fabrica = $login_fabrica";
						$res_posto = pg_query($con,$sql_posto);
						if(pg_num_rows($res_posto) > 0){
							$contato_endereco	 = pg_result($res_posto,0,'contato_endereco');
							$contato_numero		 = pg_result($res_posto,0,'contato_numero');
							$contato_complemento = pg_result($res_posto,0,'contato_complemento');
							$contato_cidade		 = pg_result($res_posto,0,'contato_cidade');
							$contato_estado		 = pg_result($res_posto,0,'contato_estado');
							$contato_email		 = pg_result($res_posto,0,'contato_email');
							$contato_fone_comercial = pg_result($res_posto,0,'contato_fone_comercial');

							$comentario = "Indicação do posto mais próximo do consumidor: <br>
										Código: $codigo_posto <br>
										Nome: $posto_nome<br>
										Endereço: $contato_endereco, $contato_numero $contato_complemento<br>
										Cidade: $contato_cidade <br>
										Estado: $contato_estado<br>
										Telefone: $contato_fone_comercial<br>
										E-mail: $contato_email";
						}
					}



				//HD 211895: Retirado da rotina abaixo para buscar OS no caso de PRÉ-OS, uma vez que não será
				//			 mais aberta OS no caso de PRÉ-OS
				if ($abre_os == 't') {

					if ($login_fabrica == 30) {
						$comentario .= "<Br><br> Foi disponibilizado para o posto a Ordem de Serviço.";
					} else {
						$comentario .= "<Br><br> Foi disponibilizado para o posto a Pré-Ordem de Serviço.";
					}

				}

				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
						) values (
							$hd_chamado       ,
							current_timestamp ,
							E'$comentario'       ,
							$login_admin      ,
							'".(($login_fabrica == 74) ? "t": "f")."',
							$xstatus_interacao
						)";


				$res       = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}

		}

		$herdar_x            = $_GET['herdar']; //HD 94971
		$hd_chamado_herdar_x = $_GET['Id'];

		if ($login_fabrica == 59 AND strlen($herdar_x) > 0 AND strlen($hd_chamado_herdar_x) > 0 AND strlen($callcenter) <= 0) {

			$interacao   = $_POST['reclamado_produto_x'];
			$reclamado_x = "Histórico do HD $hd_chamado_herdar_x: $interacao ";

			$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						status_item
					) values (
						$hd_chamado       ,
						current_timestamp ,
						E'$reclamado_x'       ,
						$login_admin      ,
						'f',
						$xstatus_interacao
					)";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT hd_chamado, data,comentario,admin,interno,status_item FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado_herdar_x";

			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$linhas = pg_num_rows($res);

			if (strlen($linhas) > 0) {

				for ($y = 0; $y < $linhas; $y++) {

					$data_hd_hist        = pg_fetch_result($res, $y, 'data');
					$comentario_hd_hist  = pg_fetch_result($res, $y, 'comentario');
					$admin_hd_hist       = pg_fetch_result($res, $y, 'admin');
					$interno_hd_hist     = pg_fetch_result($res, $y, 'interno');
					$status_item_hd_hist = pg_fetch_result($res, $y, 'status_item');
					$hd_chamado_hd_hist  = pg_fetch_result($res, $y, 'hd_chamado');

					$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
							) values (
								'$hd_chamado'       ,
								'$data_hd_hist' ,
								E'Histórico do HD $hd_chamado_herdar_x: $comentario_hd_hist'       ,
								'$admin_hd_hist'      ,
								'$interno_hd_hist',
								'$status_item_hd_hist'
							)";

					$res2 = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$finaliza = $y + 1;

				}

				if (strlen($finaliza) <= 0) {
					$finaliza = 0;
				}

			}

		}

		if ($login_fabrica == 52 AND strlen($msg_erro) == 0) {

			$qtde_produto = $_POST['qtde_produto'];
			if ($qtde_produto > 0) {
				$series_digitadas = array();
				for ($w = 1; $w <= $qtde_produto; $w++) {

					$produto_referencia = trim($_POST['produto_referencia_'.$w]);
					$serie              = trim($_POST['serie_'.$w]);
					$defeito_reclamado  = $_POST['defeito_reclamado_'.$w];
					$controle_cliente  = $_POST['controle_cliente_'.$w];

					if (strlen($produto_referencia) > 0) {

						$sql_ref = "SELECT tbl_produto.produto
									FROM  tbl_produto
									join  tbl_linha on tbl_produto.linha = tbl_linha.linha
									WHERE tbl_produto.referencia = '$produto_referencia'
									and tbl_linha.fabrica = $login_fabrica
									limit 1";

						$res_ref = pg_query($con,$sql_ref);
						$msg_erro .= pg_errormessage($con);

						if (pg_num_rows($res_ref) > 0) {
							$xproduto = pg_fetch_result($res_ref, 0, 0);

							if($xcodigo_posto != "null"){
								$sql_linha = "SELECT tbl_linha.linha
												FROM tbl_produto
												JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
												JOIN tbl_posto_linha ON tbl_linha.linha = tbl_posto_linha.linha AND posto = $xcodigo_posto
												WHERE tbl_produto.produto = $xproduto";
								$res_linha = pg_query($con,$sql_linha);
								if(pg_num_rows($res_linha) == 0){
									$msg_erro .= "O posto não atende a linha do produto $produto_referencia";
								}
							}
						} else {
							$xproduto = "null";
						}

					} else {

						$xproduto = "null";
					}

					if (strlen($defeito_reclamado) == 0 and (strlen($xproduto)>0 and $xproduto != 'null') ) {
						$msg_erro .= "Favor Escolha um defeito reclamado para o produto";
					}

					if (strlen($defeito_reclamado) > 0 and (strlen($xproduto) == 0 or $xproduto == 'null')) {
						$msg_erro .= "Favor Escolha o produto";
					}

					if (($xproduto == 'null' or empty($xproduto)) and $abre_ordem_servico == 't'){
						$msg_erro .= "<br> Favor Escolha um produto";
					}

					if (empty($serie) and (strlen($xproduto)>0 and $xproduto != 'null' ) ) {

						$msg_erro .= "<br> Favor inserir o número de série para o produto '$produto_referencia'";

					}else{

						if (in_array($serie, $series_digitadas[$xproduto])) {
							$msg_erro .= "A série digitada: '$serie' ja foi cadastrada para algum outro produto neste atendimento ";
						}else{
							$series_digitadas[$xproduto][] = $serie;
						}


					}

					$serie = "'$serie'";

					if (strlen($produto_referencia) != 0 and strlen($defeito_reclamado) != 0) {

						if (strlen($msg_erro)==0) {


							$comentario_x = 'Insercao de Produto para pré-os';


							$sql = "INSERT INTO tbl_hd_chamado_item(
										hd_chamado   ,
										data         ,
										admin        ,
										interno      ,
										produto      ,
										serie        ,
										defeito_reclamado,
										status_item
									) values (
										$hd_chamado                       ,
										current_timestamp                 ,
										$login_admin                      ,
										't'                               ,
										'$xproduto'                       ,
										$serie                          ,
										$defeito_reclamado                ,
										'Aberto'
									)";
							// echo nl2br($sql);
							$res = pg_query($con,$sql);

							if(strlen($controle_cliente) > 0){
								$sql = "UPDATE tbl_hd_chamado SET protocolo_cliente = '$controle_cliente' WHERE hd_chamado = $hd_chamado";
								$res = pg_query($con,$sql);
							}
						}

					}

				}

			}

		}

		/* HD 37805 */
		if ($tab_atual == "ressarcimento" and strlen($msg_erro) == 0) {

			if (strlen($xdata_nf) == 0 OR $xdata_nf == 'NULL') {
				$msg_erro .= "Informe a data da Nota fiscal.";
			}

			$sql  = "SELECT hd_chamado FROM tbl_hd_chamado_extra_banco WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con,$sql);

			if (@pg_num_rows($resx) == 0) {

				$sql = "INSERT INTO tbl_hd_chamado_extra_banco ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con, $sql);

				$msg_erro .= pg_errormessage($con);

			}

			$sql = "UPDATE tbl_hd_chamado_extra_banco SET
							banco            = $xbanco,
							agencia          = $xagencia,
							contay           = $xcontay,
							nomebanco        = $xnomebanco,
							favorecido_conta = $xfavorecido_conta,
							cpf_conta        = $xcpf_conta,
							tipo_conta       = $xtipo_conta
					WHERE hd_chamado = $hd_chamado";

			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			$sql  = "SELECT hd_chamado FROM tbl_hd_chamado_troca WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con, $sql);

			if (@pg_num_rows($resx) == 0) {
				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento    = $xdata_pagamento,
							ressarcimento     = 't',
							numero_objeto     = NULL,
							nota_fiscal_saida = NULL,
							data_nf_saida     = NULL,
							produto           = NULL,
							valor_produto     = $xvalor_produto,
							valor_inpc        = $xvalor_inpc,
							valor_corrigido   = $xvalor_corrigido
					WHERE hd_chamado = $hd_chamado";

			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($valor_produto) > 0 AND strlen($valor_inpc) > 0 AND strlen($msg_erro) == 0) {

				$sql  = "SELECT CURRENT_DATE - data_nf AS qtde_dias FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado ";
				$resx = @pg_query($con, $sql);

				if (@pg_num_rows($resx) > 0) {

					$qtde_dias = pg_fetch_result($resx, 0, 'qtde_dias');

					if ($qtde_dias > 0) {

						$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);

						$sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
						$res = pg_query($con, $sql);

						$msg_erro .= pg_errormessage($con);

					}

				}

			}

		}

		/* HD 37805 */
		if ($tab_atual == "sedex_reverso" and strlen($msg_erro) == 0) {

			$sql  = "SELECT hd_chamado FROM tbl_hd_chamado_troca WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con, $sql);

			if (@pg_num_rows($resx) == 0) {

				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con,$sql);

				$msg_erro .= pg_errormessage($con);

			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento       =  NULL,
							ressarcimento        = 'f',
							numero_objeto        = $xnumero_objeto,
							nota_fiscal_saida    = $xnota_fiscal_saida,
							data_nf_saida        = $xdata_nf_saida,
							produto              = $xproduto_troca,
							data_retorno_produto = $xdata_retorno_produto
					WHERE hd_chamado = $hd_chamado";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

		}

		if ($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica == 25) {

			if (strlen($es_data_compra) == 0) {
				$msg_erro .= "Informe a data da Compra do produto.";
			}

		}

		/* ##################  grava no banco de dados da hbtech ##################### */
		if ($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica == 25) {

			if (strlen($consumidor_fone) == 15) {

				 $xddd_consumidor  = "'".substr($consumidor_fone,2,2)."'";
				 $xfone_consumidor = "'".substr($consumidor_fone,6,9)."'";

			} else if (strlen($consumidor_fone)==9 or strlen($consumidor_fone) == 8) {

				 $xddd_consumidor  = "null";
				 $xfone_consumidor = "'".$consumidor_fone."'";

			} else if (strlen($consumidor_fone) == 11 or strlen($consumidor_fone) == 10) {

				 $xddd_consumidor  = "'".substr($consumidor_fone,0,2)."'";
				 $xfone_consumidor = "'".substr($consumidor_fone,2,9)."'";

			} else if (strlen($consumidor_fone) == 0) {

				 $xddd_consumidor  = "NULL";
				 $xfone_consumidor = "NULL";

			} else {

				 $xddd_consumidor  = "NULL";
				 $xfone_consumidor = "'".$consumidor_fone."'";

			}

			$xxes_data_compra = converte_data($es_data_compra);
			$sql = "SELECT garantia from tbl_produto where produto = $xproduto";
			$res = pg_query($con, $sql);
			$garantia = pg_fetch_result($res, 0, 0);

			$sql = "SELECT to_char(('$xxes_data_compra'::date + interval '$garantia month') + interval '6 month','YYYY-MM-DD') ";
			$res = pg_query($con, $sql);
			$es_garantia = "'".pg_fetch_result($res, 0, 0)."'";

			if (strlen($es_id_numeroserie) > 0) {

				include "conexao_hbtech.php";

				/*INSERINDO NO SITE DO HIBEATS, VERIFICAMOS ANTES SE EXISTE ESSE NUMERO DE SRIE E INSERIMOS OS DADOS DO CLIENTE*/
				$sql = "INSERT INTO garantia(
							produto           ,
							numeroSerie       ,
							nome              ,
							endereco          ,
							numero            ,
							complemento       ,
							cep               ,
							bairro            ,
							cidade            ,
							estado            ,
							sexo              ,
							dataNascimento    ,
							cpf               ,
							dddComercial      ,
							foneComercial     ,
							dddResidencial    ,
							foneResidencial   ,
							dddCelular        ,
							foneCelular       ,
							email             ,
							estadoCivil       ,
							filhos            ,
							prefMusical       ,
							dataCompra        ,
							nf                ,
							lojaAdquirida     ,
							estadoCompra      ,
							municipioCompra   ,
							dataGarantia
						) values (
							'$produto_referencia||$produto_nome',
							$xserie  ,
							$xconsumidor_nome       ,
							$xconsumidor_endereco   ,
							$xconsumidor_numero     ,
							$xconsumidor_complemento,
							$xconsumidor_cep        ,
							$xconsumidor_bairro     ,
							$xconsumidor_cidade     ,
							$xconsumidor_estado     ,
							$xes_sexo               ,
							$xes_data_nascimento    ,
							$xconsumidor_cpf        ,
							$xes_dddcomercial       ,
							$xes_fonecomercial      ,
							$xddd_consumidor        ,
							$xfone_consumidor       ,
							$xes_dddcelular         ,
							$xes_celular            ,
							$xconsumidor_email      ,
							$xes_estadocivil        ,
							$xes_filhos             ,
							$xes_preferenciamusical ,
							$xes_data_compra        ,
							$xes_nota_fiscal        ,
							$xes_revenda            ,
							$xes_estadocompra       ,
							$xes_municipiocompra    ,
							$es_garantia
						);";

				$res = mysql_query($sql) or die("Erro no Sql1: ".mysql_error());

				if ($xconsumidor_cpf == 'null' or strlen($xconsumidor_cpf) == 0 ){
					$pesquisa_xconsumidor_cpf = " AND cpf  IS NULL ";
				} else {
					$pesquisa_xconsumidor_cpf = " AND cpf  = $xconsumidor_cpf";
				}

				$sql = "SELECT idGarantia FROM garantia WHERE numeroSerie = $xserie $pesquisa_xconsumidor_cpf";
				$res = mysql_query($sql) or die("Erro no Sql2:".mysql_error());

				if (mysql_num_rows($res) > 0) {

					$idGarantia = mysql_result($res, 0, 'idGarantia');
					$sql = "UPDATE numero_serie SET idGarantia = $idGarantia WHERE numero = $xserie";
					$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

				}

			}

		}

		if (strlen($msg_erro) == 0) {

			$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

		}

		if ($abre_os == 't' AND $imprimir_os == 't') {
			$imprimir_os = "&imprimir_os=t";
		} else {
			$imprimir_os = "";
		}


		$assunto_vazio = 0;

		$tot_campos = count($_POST['callcenter_assunto']);

		if ($login_fabrica == 24){

			$assunto_atendimento = $_POST['callcenter_assunto'];

				if ($tab_atual != "sugestao"){



					unset($assunto_atendimento["sugestao"]);
				}

				$tot_campos = count($assunto_atendimento);

			foreach ($assunto_atendimento as $campo => $valor) {
				if (strlen($valor)==0) {
					$assunto_vazio++;
				}elseif ($assunto_vazio > 0) {
					$assunto_vazio --;
				}
			}

			if ($assunto_vazio == $tot_campos){
				$msg_erro = "Insira o assunto do atendimento";
			}

		}

		if (strlen($xtransferir) > 0 AND strlen($hd_chamado) > 0 AND ($login_admin <> $xtransferir)) {//HD 26968
			$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					and tbl_hd_chamado.hd_chamado = $hd_chamado	";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			if($login_fabrica != 74){

				$sql = "SELECT login from tbl_admin where admin = $login_admin";
				$res = pg_query($con, $sql);

				$nome_ultimo_atendente = pg_fetch_result($res, 0, 'login');

				$sql = "SELECT login from tbl_admin where admin = $xtransferir";
				$res = pg_query($con, $sql);

				$nome_atendente = pg_fetch_result($res,0,login);

				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
						) values (
							$hd_chamado       ,
							current_timestamp ,
							E'Atendimento transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>',
							$login_admin      ,
							't'  ,
							$xstatus_interacao
						)";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}

		}

		// HD 129655 - Gravar dúvidas selecionadas [augusto]
		if (strlen($msg_erro) == 0) {
			gravarFaq();
		}

		//HD 211895: Rotina de PRÉ-OS movida para fora das rotinas de inserção e atualização
		//ATENÇÃO: COMMIT E ROLLBACK DESTA TRANSAÇÃO FOI MOVIDO PARA DEPOIS DA ROTINA DE INSERÇÃO
		//DE PRÉ-OS, QUE ESTÁ LOGO ABAIXO DA ROTINA DE ATUALIZAÇÃO (QUE COMEÇA AI EMABAIXO)

	}

	if ($login_fabrica == 24 && $chamado_resolvido_reabertura === true && !empty($chamado_resolvido_reabertura_admin) && !empty($hd_chamado)) {
		$sql = "UPDATE tbl_hd_chamado SET atendente = $chamado_resolvido_reabertura_admin WHERE fabrica = $login_fabrica AND hd_chamado = $hd_chamado";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro .= "Erro ao definir novo admin responsável para o chamado";
		}
	}
	/****************************************** ATUALIZAÇÃO *****************************************/
	if (strlen($callcenter) > 0) {

		if($login_fabrica == 52){

			if (strlen(trim($_POST['consumidor_cep'])) == 0) {
				$msg_erro .= "Por favor informe CEP do consumidor <br>";
			}

			if(strlen($_POST['consumidor_cpf']) == 0){
				$msg_erro .= "Informe o CPF / CNPJ do Cliente <br>";
			}

			if(strlen($_POST['ponto_referencia']) == 0){
				$msg_erro .= "Informe o Ponto de Referência <br>";
			}
			if ($xresposta == "null") {
				$msg_erro .= "Por favor insira a resposta";
			}

		}else{

			if ($xresposta == "null" && $login_fabrica != 74) {
				$msg_erro = "Por favor insira a resposta";
			}

		}

		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$cidade = pg_fetch_result($res, 0, "cidade");
		} else {
			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
				$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

				$sql = "INSERT INTO tbl_cidade (
							nome, estado
						) VALUES (
							'{$cidade_ibge}', '{$cidade_estado_ibge}'
						) RETURNING cidade";
				$res = pg_query($con, $sql);

				$cidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$msg_erro .= "Cidade do consumidor não encontrada";
			}
		}

		$data_ag = $_POST["data_ag"] ;
		if (strlen($data_ag) == 0){
			$xdata_ag = "NULL";
		}else{
			list($ddf, $mdf, $ydf) = explode("/", $data_ag);
			if(!checkdate($mdf,$ddf,$ydf)) {
				$msg_erro = "Data Agendamento Inválida";
			}else{
				$dataexplode = explode('/', $data_ag);
				$data_ag_timestamp = strtotime($dataexplode[2]."-".$dataexplode[1]."-".$dataexplode[0]);
				$data_hoje_timestamp = strtotime(date('y-m-d',time()));


				if($data_ag_timestamp < $data_hoje_timestamp){
					$msg_erro = "A Data de Agendamento não pode ser inferior ao dia ".date("d-m-Y",time());
				}else{
					$xdata_ag = "'".$ydf.'-'.$mdf.'-'.$ddf."'";

					$sql = "UPDATE tbl_hd_chamado set data_providencia = $xdata_ag,
													  esta_agendado = true
							WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							AND tbl_hd_chamado.hd_chamado = $callcenter	";

					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

			}


		}

		$sql = "SELECT atendente,login
				from tbl_hd_chamado
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
				where fabrica_responsavel= $login_fabrica
				and hd_chamado = $callcenter";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$ultimo_atendente       = pg_fetch_result($res,0,'atendente');
			$ultimo_atendente_login = pg_fetch_result($res,0,'login');
		}

		// ! Gravar alterações de dados
		// HD 122446 (augusto) - Criar interação quando o endereço do cliente for modificado (Lenoxx)
		// HD 124579 (augusto) - Implementar isso em outros campos que podem ser modificados para todas as fábricas
		$msg_interacao = '';

		if ((strlen($msg_erro) <= 0 && $login_fabrica == 11 && $hd_chamado > 0 && $xstatus_interacao == "'Aberto'" ) ||
			(strlen($msg_erro) <= 0 && $login_fabrica != 11 && $hd_chamado > 0)) {
			unset($array_campos_consumidor_verificar);
			unset($array_consumidor_label);
			$array_campos_consumidor_verificar 					= array('consumidor_nome','consumidor_cpf','consumidor_rg','consumidor_email','consumidor_fone',
																		'consumidor_fone2','consumidor_fone3','consumidor_cep','consumidor_endereco','consumidor_numero',
																		'consumidor_complemento','consumidor_bairro','consumidor_cidade','ponto_referencia','cliente_nome_admin','posto_km_tab','consumidor_estado',
																		'produto_referencia','produto_nome','voltagem','serie','nota_fiscal','data_nf','marca');
			$array_consumidor_label            					= array_flip($array_campos_consumidor_verificar);
			$array_consumidor_label['consumidor_nome']	 		= 'Nome';
			$array_consumidor_label['consumidor_cpf']	 		= 'CPF';
			$array_consumidor_label['consumidor_rg']	 		= 'RG';
			$array_consumidor_label['consumidor_email'] 		= 'E-mail';
			$array_consumidor_label['consumidor_fone'] 			= 'Telefone';
			$array_consumidor_label['consumidor_fone2']			= 'Telefone Comercial';
			$array_consumidor_label['consumidor_fone3'] 		= 'Telefone Celular';
			$array_consumidor_label['consumidor_cep'] 			= 'CEP';
			$array_consumidor_label['consumidor_endereco'] 		= 'Endereço';
			$array_consumidor_label['consumidor_numero'] 		= 'Número';
			$array_consumidor_label['consumidor_complemento']   = 'Complem.';
			$array_consumidor_label['ponto_referencia'] 		= 'Ponto de Referência';
			$array_consumidor_label['consumidor_bairro'] 		= 'Bairro';
			$array_consumidor_label['consumidor_cidade']		= 'Cidade';
			$array_consumidor_label['consumidor_estado'] 		= 'Estado';
			$array_consumidor_label['produto_referencia'] 		= 'Referência (do Produto)';
			$array_consumidor_label['produto_nome'] 			= 'Descrição (do Produto)';
			$array_consumidor_label['voltagem'] 				= 'Voltagem';
			$array_consumidor_label['serie'] 					= 'Série';
			$array_consumidor_label['nota_fiscal'] 				= 'NF Compra';
			$array_consumidor_label['data_nf'] 					= 'Data NF';
			$array_consumidor_label['marca'] 					= 'Marca';
			$array_consumidor_label['posto_km_tab'] 			= 'Qtde de KM';
			$array_consumidor_label['cliente_nome_admin'] 			= 'Cliente Fricon';


			unset($interacao_campos_consumidor_msgs);
			$interacao_campos_consumidor_msgs  					= array();

			foreach ($array_campos_consumidor_verificar as $campo_consumidor) {

				$valor_anterior = $campo_consumidor.'_anterior';

				if ( ! isset($_POST[$campo_consumidor]) ) { continue; }
				//  30/12/2009 MLG HD 188091 - "Null" e "nada" deveriam ser a mesma coisa...
				if (empty($_POST[$valor_anterior]) and $$campo_consumidor == "null") { continue; }
				//  22/01/2010 MLG HD 198090 - O campo CPF é re-formatado, mas isso significa que volta a ser alterado.
					$replaceArgs = array('.','-',' ','/');
				$valor_atual    = $$campo_consumidor;

				// 				if ($campo_consumidor=="consumidor_cpf") $valor_atual = preg_replace("/\D/","",$valor_atual);
				if ($campo_consumidor=="consumidor_cpf"){

					$valor_atual = str_replace($replaceArgs, '', $valor_atual);
					$_POST[$valor_anterior] = str_replace($replaceArgs, '', $$valor_anterior);

				}




				if (strtoupper(retira_acentos($_POST[$valor_anterior])) != strtoupper(retira_acentos($valor_atual))) {

					$msg_valor_anterior = ( empty($_POST[$valor_anterior]) ) ? 'Em branco' : $_POST[$valor_anterior] ;
					if ($campo_consumidor == "marca" AND $login_fabrica == 52 ) {

						if ($msg_valor_anterior != 'Em branco' OR $valor_atual != 'NULL'  ) {
							$sql_marca = "SELECT nome from tbl_marca where marca = $msg_valor_anterior and fabrica = 52";
							$res_marca = pg_query($con,$sql_marca);
							$marca_logo_ant = pg_fetch_result($res_marca, 0, nome);
							//echo $sql_marca;
							$sql_marca = "SELECT nome from tbl_marca where marca = $valor_atual and fabrica = 52";
							$res_marca = pg_query($con,$sql_marca);
							//echo $sql_marca;exit;
							$marca_logo_at = pg_fetch_result($res_marca, 0, nome);

							$sql_up = "UPDATE tbl_os set marca = $valor_atual where hd_chamado = $hd_chamado";
							//echo $sql;exit;
							$res_up = pg_query($con,$sql_up);

							$msg_alteracao      = "<li>Campo <strong>{$array_consumidor_label[$campo_consumidor]}</strong> alterado de '<em>{$marca_logo_ant}</em>' para '<em>{$marca_logo_at}</em>'</li>";

						}

					}else{
						$msg_alteracao      = "<li>Campo <strong>{$array_consumidor_label[$campo_consumidor]}</strong> alterado de '<em>{$msg_valor_anterior}</em>' para '<em>{$$campo_consumidor}</em>'</li>";
					}
					if (count($msg_alteracao) > 0) {
						$interacao_campos_consumidor_msgs[] = $msg_alteracao;
					}else{
						$interacao_campos_consumidor_msgs = NULL;
					}

				}

			}
			if (count($interacao_campos_consumidor_msgs) > 0) {

				$msg_interacao  = "<p>As seguintes informações do chamado foram alteradas nesta interação:</p><p>&nbsp;</p>";
				$msg_interacao .= "<ul>".implode('',$interacao_campos_consumidor_msgs)."</ul>";
				$msg_interacao = addslashes($msg_interacao);
				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
						) values (
							$hd_chamado       ,
							current_timestamp ,
							'$msg_interacao'       ,
							$login_admin      ,
							't',
							$xstatus_interacao
						)";
				$res = pg_query($con, $sql);
			}

			if(in_array($login_fabrica, array(74, 24, 52))){
				unset($msg_interacao);
			}

			unset($array_campos_consumidor_verificar,$interacao_campos_consumidor_msgs,$msg_alteracao,$valor_anterior,$campo_consumidor,$sql,$res);
		}
		// fim HD 122446

		# HD 45756
		if ($login_fabrica == 3) {

			if ($ultimo_atendente <> $login_admin) {
				$msg_erro = "Sem permissão de alteração. Admin responsável: $ultimo_atendente_login";
			}

		}
		if (isset($_POST['tipo_consumidor']) && !empty($hd_chamado)) { // HD 317864

			$sql = "UPDATE tbl_hd_chamado_extra SET tipo_consumidor = '" . $_POST['tipo_consumidor'] . "' WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con, $sql);

		} else if (in_array($login_fabrica, $fab_usa_tipo_cons) ) {

			$msg_erro = "Selecione o Tipo do Consumidor";

		}


		if (strlen($msg_erro) == 0) {

			$res        = pg_query ($con,"BEGIN TRANSACTION");
			$_xresposta = pg_escape_string("{$resposta}<p>&nbsp;</p> {$msg_interacao}");

			if ($login_fabrica == 74 && strlen($msg_interacao) == 0 && strlen($resposta) == 0 && $xstatus_interacao == "'Resolvido'") {
				$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						status_item  ,
						enviar_email
					) values (
						$callcenter        ,
						current_timestamp  ,
						'Atendimento resolvido'     ,
						$login_admin       ,
						$xchamado_interno  ,
						$xstatus_interacao ,
						$xenvia_email
					)";

				$res = pg_query($con,$sql);
			}

			if(!($login_fabrica == 74 && strlen($msg_interacao) == 0 && strlen($resposta) == 0)){
				if (($login_fabrica == 15) AND (strlen($xdata_retorno) > 0 )) {
					$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						status_item  ,
						enviar_email
					) values (
						$callcenter        ,
						current_timestamp  ,
						E'$_xresposta Data para retorno : $xdata_retorno'      ,
						$login_admin       ,
						$xchamado_interno  ,
						$xstatus_interacao ,
						$xenvia_email
					)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
				}else{
				$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						status_item  ,
						enviar_email
					) values (
						$callcenter        ,
						current_timestamp  ,
						E'$_xresposta'      ,
						$login_admin       ,
						$xchamado_interno  ,
						$xstatus_interacao ,
						$xenvia_email
					)";

				$res = pg_query($con,$sql);
				$chamado = buscaChamado($callcenter);
				if(in_array($login_fabrica,array(81,114,122,123,125,128,136)) && $chamado['admin'] != $login_admin){
					$mail = montaEmail($chamado,$login_admin,$_xresposta);
					enviaEmail($mail);
				}

				$msg_erro .= pg_errormessage($con);
					}
				if ($login_fabrica == 1 and $xchamado_interno <> "'t'") {

					$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (pg_num_rows($res) > 0) {
						$admin_email = pg_fetch_result($res, 0, 'email');
					}

					$sql = "SELECT email from tbl_hd_chamado_extra where hd_chamado = $hd_chamado";
					$res = pg_query($con, $sql);

					if (@pg_num_rows($res)  > 0 and strlen(pg_fetch_result($res,0,email)) > 0) {

						$email_posto = strtolower(pg_fetch_result($res, 0, 'email'));
						$subject  = "Help-Desk $hd_chamado respondido pelo fabricante";
						$message  = "<b>Prezado Posto</b><br><br>";
						$message .= "<b> O Help-Desk $hd_chamado foi respondido pelo fabrincate.<br><br>";
						$message .= "<b> Atenciosamente<br><br>";

						// $email_posto = "guilherme.silva@telecontrol.com.br";

	                    $mailer->IsSMTP();
	                    $mailer->IsHTML();
	                    $mailer->AddAddress($email_posto);
	                    $mailer->Subject = $subject;
	                    $mailer->Body = $message;

	                    $mailer->Send();
					}

				}

			}

		}

		if($login_fabrica == 52 && strlen($msg_erro) == 0){
			//echo "Aki";

			/* ------ Campo Operadora ------ */

			$campos_adicionais2 = array();

			if (!empty($_POST['operadora_celular'])){
				$campos_adicionais2["operadora"] = trim($_POST['operadora_celular']);
			}else{
				$campos_adicionais2["operadora"] = "";
			}


			$sqlOS = "SELECT os FROM tbl_os JOIN tbl_os_extra USING(os) where tbl_os.hd_chamado = $callcenter and tbl_os.fabrica = $login_fabrica";
			$resOS = pg_query($con,$sqlOS);
			if(pg_num_rows($resOS) > 0 ){
				$os_fricon = pg_fetch_result($resOS, 0, 'os');
				$json = json_encode($campos_adicionais2);

				$json = str_replace("\\", "", $json);

				$sql = "SELECT * FROM tbl_os_campo_extra WHERE os = $os_fricon";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res) > 0){
					$sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$json' WHERE os = $os_fricon AND fabrica = $login_fabrica";
					$res = pg_query($con, $sql);
				}else{
					$sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os_fricon, $login_fabrica, '$json')";
					$res = pg_query($con, $sql);
				}

			}
			/* ------ Campo Operadora ------ */

		}

		if ($login_fabrica == 52 and empty($msg_erro)){ //HD 905951
			//echo "aki - 2";


			$sqlPontoReferencia = "UPDATE tbl_hd_chamado_extra set posto_nome = $xconsumidor_ponto_referencia where hd_chamado = $hd_chamado";
			$resPontoReferencia = pg_query($con,$sqlPontoReferencia);
			$msg_erro = pg_last_error($con);

			if (!empty($posto_km_tab) or $posto_km_tab == 0){
				if (empty($posto_km_tab)) {
					$posto_km_tab = "null";
				}
				$sqlKm = "UPDATE tbl_hd_chamado_extra set qtde_km = $posto_km_tab where hd_chamado = $hd_chamado";
				$resKm = pg_query($con,$sqlKm);
				$msg_erro = pg_last_error($con);

			}else{
				$msg_erro = "É necessario digitar a Qtde de Km, clique em Mapa da Rede";
			}

			$qtde_produto = $_POST['qtde_produto'];
			if ($qtde_produto > 0) {

				$series_digitadas = array();

				for ($w = 1; $w <= $qtde_produto; $w++) {

					$produto_referencia = $_POST['produto_referencia_'.$w];
					$serie              = $_POST['serie_'.$w];
					$defeito_reclamado  = $_POST['defeito_reclamado_'.$w];

					if (!isset($_POST['hd_chamado_item_'.$w]) or empty($_POST['hd_chamado_item_'.$w])){

						if (strlen($produto_referencia) > 0) {

							$sql_ref = "SELECT tbl_produto.produto
										FROM  tbl_produto
										join  tbl_linha on tbl_produto.linha = tbl_linha.linha
										WHERE tbl_produto.referencia = '$produto_referencia'
										and tbl_linha.fabrica = $login_fabrica
										limit 1";

							$res_ref = pg_query($con,$sql_ref);
							$msg_erro .= pg_errormessage($con);

							if (pg_num_rows($res_ref) > 0) {
								$xproduto = pg_fetch_result($res_ref, 0, 0);

								if($xcodigo_posto != "null"){
									$sql_linha = "SELECT tbl_linha.linha
													FROM tbl_produto
													JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
													JOIN tbl_posto_linha ON tbl_linha.linha = tbl_posto_linha.linha AND posto = $xcodigo_posto
													WHERE tbl_produto.produto = $xproduto";
									$res_linha = pg_query($con,$sql_linha);
									if(pg_num_rows($res_linha) == 0){
										$msg_erro .= "O posto não atende a linha do produto $produto_referencia";
									}
								}
							} else {
								$xproduto = "null";
							}

						} else {

							$xproduto = "null";
						}

						if (strlen($defeito_reclamado) == 0 and (strlen($xproduto)>0 and $xproduto != 'null' ) ) {
							$msg_erro .= "Favor Escolha um defeito reclamado para o produto $produto_referencia";
						}

						if (strlen($defeito_reclamado) > 0 and (strlen($xproduto) == 0 or $xproduto == 'null')) {
							$msg_erro .= "Favor Escolha o produto";
						}

						if (($xproduto == 'null' or empty($xproduto)) and $abre_ordem_servico == 't'){
							$msg_erro .= "<br> Favor Escolha um produto";
						}

						if (empty($serie) and (strlen($xproduto)>0 and $xproduto != 'null' ) ) {

							$msg_erro .= "<br> Favor inserir o número de série para o produto '$produto_referencia'";

						}else{

							if (in_array($serie, $series_digitadas[$xproduto])) {
								$msg_erro .= "A série digitada: '$serie' ja foi cadastrada para algum outro produto neste atendimento ";
							}else{
								$series_digitadas[$xproduto][] = $serie;
							}


						}

						$serie = "'$serie'";

						if (strlen($produto_referencia) != 0 and strlen($defeito_reclamado) != 0 and empty($msg_erro)) {

							$comentario_x = 'Insercao de Produto para pré-os';


							$sql = "INSERT INTO tbl_hd_chamado_item(
										hd_chamado   ,
										data         ,
										admin        ,
										interno      ,
										produto      ,
										serie        ,
										defeito_reclamado,
										status_item
									) values (
										$hd_chamado                       ,
										current_timestamp                 ,
										$login_admin                      ,
										't'                               ,
										'$xproduto'                       ,
										$serie                            ,
										$defeito_reclamado                ,
										'Aberto'
									)";
							//echo nl2br($sql)."<br><br>";
							$res = pg_query($con,$sql);

						}

					}

				}

			}

		}

		//HD 196942: Controle de postagem
		//o checkbox para solicitar postagem para o chamado só aparece quando não tem solicitação para aquele chamado,
		//portanto só virá alguma coisa por POST em hd_chamado_postagem se não estiver cadastrado e se estiver com o checkbox marcado
		if ($_POST["hd_chamado_postagem"] == "sim" && strlen($hd_chamado) > 0 && strlen($msg_erro) == 0) {

			$sql = "SELECT hd_chamado FROM tbl_hd_chamado_postagem WHERE hd_chamado=$hd_chamado";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {
				$msg_erro = "Já existe solicitação de postagem cadastrada para este chamado. Não é permitido o cadastramento de mais de uma postagem para um mesmo chamado";
			} else {

				$sql = "INSERT INTO tbl_hd_chamado_postagem(hd_chamado) VALUES($hd_chamado)";
				@$res = pg_query($con, $sql);

				if (pg_errormessage($con)) {
					$msg_erro = "Houve um erro ao cadastrar a sua solicitação de postagem. Contate o HelpDesk";
				}

			}

		}

		if (strlen($_POST["hd_chamado_codigo_postagem"]) > 0 && strlen($hd_chamado) && strlen($msg_erro) == 0) {

			$hd_chamado_codigo_postagem = $_POST["hd_chamado_codigo_postagem"];

			$sql = "SELECT hd_chamado FROM tbl_hd_chamado_postagem WHERE hd_chamado=$hd_chamado AND aprovado IS TRUE";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {

				$sql = "UPDATE tbl_hd_chamado_postagem SET codigo_postagem = '$hd_chamado_codigo_postagem' WHERE hd_chamado=$hd_chamado";
				@$res = pg_query($con, $sql);

				if (pg_errormessage($con)) {
					$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
				}

			} else {
				$msg_erro = "Foi informado Código de Postagem, mas não existe postagem autorizada para este chamado";
			}

		}

		if (strlen($posto_tab) > 0 and strlen($msg_erro) == 0) {

			$comentario = "Indicação do posto mais próximo do consumidor: <br>
						Código: $codigo_posto_tab <br>
						Nome: $posto_nome_tab<br>
						Endereço: $posto_endereco_tab <br>
						Cidade: $posto_cidade_tab <br>
						Estado: $posto_estado_tab<br>
						Telefone: $posto_fone_tab<br>
						E-mail: $posto_email_tab";

			$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						status_item
					) values (
						$hd_chamado       ,
						current_timestamp ,
						E'$comentario'       ,
						$login_admin      ,
						'".(($login_fabrica == 74) ? "t": "f")."',
						$xstatus_interacao
					)";

			$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			if ($login_fabrica == 74 && $abre_os == "t") {
 				$comentario = "Foi disponibilizado para o posto a Pré-Ordem de Serviço.";

 				$sql = "INSERT INTO tbl_hd_chamado_item(
 							hd_chamado   ,
 							data         ,
 							comentario   ,
 							admin        ,
 							interno      ,
 							status_item
 						) values (
 							$hd_chamado,
 							current_timestamp ,
 							E'$comentario',
 							6223,
 							't',
 							$xstatus_interacao
 						)";
 				$res = pg_query($con,$sql);
 			}

			//HD 211895: Atualizando posto
			$sql = "UPDATE tbl_hd_chamado_extra SET posto = $posto_tab WHERE hd_chamado = $callcenter";
			$res_atualiza_posto = pg_query($con, $sql);

			$msg_erro .= pg_errormessage($con);

			//echo "Aki - 3";

		}

		//se  para enviar email para consumidor
		if (strlen($msg_erro) == 0 && $xenvia_email == "'t'" && in_array($login_fabrica,array(24,86))) {

			if ($_POST["consumidor_email"]) {

				/* Realiza uma interação no chamado - Foi enviado um email para o Consumidor */
				if($login_fabrica == 35 && isset($_POST['consumidor_email']) && $xenvia_email == "'t'"){
					$sql_email_chamado = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
						) values (
							$callcenter       ,
							current_timestamp ,
							'Foi enviado um E-mail para o consumidor',
							$login_admin      ,
							't'  ,
							$xstatus_interacao
						)";

					$res_email_chamado = pg_query($con, $sql_email_chamado);
					$msg_erro .= pg_errormessage($con);

				}

				if ($login_fabrica == 24) {
					$admin_email = "Suggar <resposta_automatica@suggar.com.br>";
				} else {

					if($login_fabrica == 86){
						$sql = "SELECT email,responsabilidade
								FROM tbl_admin
								WHERE fabrica = $login_fabrica
								AND responsabilidade = 'envia_email'
								AND admin = $login_admin
							";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

						if (pg_num_rows($res) > 0) {
							$admin_email_assinatura = pg_fetch_result($res,0,email);
							$responsabilidade_admin = pg_fetch_result($res,0,responsabilidade);
						}
					}
					$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (pg_num_rows($res) > 0) {
						$admin_email = pg_fetch_result($res,0,email);
					} else {
						$admin_email = "telecontrol@telecontrol.com.br";
					}

					if($login_fabrica == 86 AND $responsabilidade_admin == "envia_email"){

						$assinatura = "\n\n\nAssistência Técnica Famastil Taurus Ferramentas S.A.\nFone: 0800 644 8284\n$admin_email_assinatura";
					}else{
						$assinatura = '';
					}
				}

				$xxresposta   = str_replace("'","",$xresposta);
				$remetente    = $admin_email;
				$destinatario = $_POST["consumidor_email"];
				$assunto      = "Protocolo $callcenter - Resposta atendimento Call Center";
				$mensagem     = $xxresposta.$assinatura;
				$headers      = "Return-Path: $admin_email\nFrom:".$remetente."\nContent-type: text/html\n";

				//HD 234227: Mensagem da Suggar
				if ($login_fabrica == 24) {
					$remetente = "resposta_automatica@suggar.com.br";
					$mensagem = "
					<font size='-1'>Esta é uma mensagem automática. Por favor, não responda este e-mail. Estamos sempre prontos para atendê-lo.  Caso queira entrar em contato novamente, acesse www.suggar.com.br no link Fale conosco</font><br>
					<br>
					$mensagem<br>
					<br>
					Atenciosamente,<br>
					<br>
					Central de Relacionamento com o cliente<br>
					<br>
					SUGGAR<br>
					<br>";

					// mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

					$mailer->IsSMTP();
					$mailer->IsHTML();
					$mailer->AddAddress($destinatario);
					$mailer->Subject = $assunto;
					$mailer->Body = $mensagem;
					$mailer->AddReplyTo($admin_email);
					$mailer->Send();

				} else {

					//mail($destinatario,$assunto,$mensagem,$headers);

					$mailer->IsSMTP();
					$mailer->IsHTML();
					$mailer->AddAddress($destinatario);
					$mailer->Subject = $assunto;
					$mailer->Body = $mensagem;
					$mailer->AddReplyTo($admin_email);
					$mailer->Send();
				}

			}

		}


		if (strlen($msg_erro) == 0) {

			if(!empty($data_ag)){
				$xstatus_interacao = "'Agendado'";
			}

			$sql = "UPDATE tbl_hd_chamado set status = $xstatus_interacao ";

			if ($login_fabrica == 24) {//HD 262718

				$assunto_vazio = 0;
				$tot_campos = count($_POST['callcenter_assunto']);
				foreach ($_POST['callcenter_assunto'] as $campo => $valor) {
					if (strlen($valor)==0) {
						$assunto_vazio++;
					}elseif ($assunto_vazio > 0) {
						$assunto_vazio --;
					}elseif(!empty($valor)){
						$callcenter_assunto = $valor;
					}
				}

				if ($assunto_vazio == $tot_campos){
					$msg_erro = "Insira o assunto do atendimento";
				}

				if (empty($msg_erro)){
					$sql .= " , categoria = '$callcenter_assunto' ";
				}

			}

			$sql .= " WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						and tbl_hd_chamado.hd_chamado = $callcenter	";

			$res       = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($intervensor) > 0 AND strlen($hd_chamado) > 0 AND ($login_admin <> $intervensor) AND ($xtransferir <> $intervensor)) {

				$sql = "UPDATE tbl_hd_chamado set sequencia_atendimento = $intervensor
							WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							and tbl_hd_chamado.hd_chamado = $hd_chamado	";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT login,email from tbl_admin where admin = $ultimo_atendente";
				$res = pg_query($con, $sql);

				$nome_ultimo_atendente  = pg_fetch_result($res, 0, 'login');
				$email_ultimo_atendente = pg_fetch_result($res, 0, 'email');

				$sql = "SELECT login,email from tbl_admin where admin = $intervensor";
				$res = pg_query($con, $sql);

				$nome_intervensor  = pg_fetch_result($res, 0, 'login');
				$email_intervensor = pg_fetch_result($res, 0, 'email');

				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
						) values (
							$callcenter       ,
							current_timestamp ,
							E'O Atendente <b>$login_login</b> precisou da intenvencão do <b>$nome_intervensor</b> para resolver este atendimento',
							$login_admin      ,
							't'  ,
							$xstatus_interacao
						)";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if (strlen($email_ultimo_atendente) > 0 AND strlen($email_intervensor) > 0) {

					$assunto = "O Atendente $login_login, precisou de sua intervenção no chamado $callcenter";

					if ($login_fabrica == 24 || $login_fabrica == 11) {

						$sql = " SELECT  tbl_hd_chamado_extra.nome       ,
										 tbl_hd_chamado_extra.endereco   ,
										 tbl_hd_chamado_extra.numero     ,
										 tbl_hd_chamado_extra.complemento,
										 tbl_hd_chamado_extra.bairro     ,
										 tbl_hd_chamado_extra.cep        ,
										 tbl_hd_chamado_extra.fone       ,
										 tbl_hd_chamado_extra.email      ,
										 tbl_hd_chamado_extra.cpf        ,
										 tbl_hd_chamado_extra.rg         ,
										 tbl_hd_chamado.categoria  ,
										 tbl_hd_chamado_extra.reclamado  ,
										 tbl_cidade.nome as cidade,
										 tbl_cidade.estado         ,
										 tbl_produto.referencia    ,
										 tbl_produto.descricao
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra USING(hd_chamado)
								JOIN tbl_cidade           USING(cidade)
								LEFT JOIN tbl_produto     USING(produto)
								WHERE tbl_hd_chamado.hd_chamado = $callcenter";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0) {

							$nome        = pg_fetch_result($res, 0, 'nome');
							$endereco    = pg_fetch_result($res, 0, 'endereco');
							$numero      = pg_fetch_result($res, 0, 'numero');
							$bairro      = pg_fetch_result($res, 0, 'bairro');
							$cep         = pg_fetch_result($res, 0, 'cep');
							$fone        = pg_fetch_result($res, 0, 'fone');
							$email       = pg_fetch_result($res, 0, 'email');
							$categoria   = pg_fetch_result($res, 0, 'categoria');
							$cidade_nome = pg_fetch_result($res, 0, 'cidade');
							$estado      = pg_fetch_result($res, 0, 'estado');
							$reclamado   = pg_fetch_result($res, 0, 'reclamado');
							$referencia  = @pg_fetch_result($res, 0, 'referencia');
							$descricao   = @pg_fetch_result($res, 0, 'descricao');

							//HD 235203: Colocar vários assuntos para a Suggar
							$achou_categoria_assunto = false;

							foreach ($assuntos as $categoria_assunto => $itens_categoria_assunto) {

								foreach ($itens_categoria_assunto as $label_assunto => $bd_assunto) {

									if ($bd_assunto == $categoria) {
										$categoria               = $label_assunto;
										$achou_categoria_assunto = true;
									}

								}

							}

							if ($achou_categoria_assunto == false) {

								if ($categoria == 'reclamacao_produto') $categoria = "Reclamação do Produto";
								if ($categoria == "duvida_produto")     $categoria = "Dúvida do Produto";
								if ($categoria == "reclamacao_at")      $categoria = "Reclamação da Assistência Técnica";
								if ($categoria == "sugestao")           $categoria = "Sugestão";
								if ($categoria == "reclamacao_empresa") $categoria = "Reclamação da Empresa";
								if ($categoria == "procon")             $categoria = "Procon";
								if ($categoria == "onde_comprar")       $categoria = "Onde comprar";

							}

						}

					}

					if ($status_interacao == 'Resolvido' || ($login_fabrica == 74 && $status_interacao == "PROTOCOLO DE INFORMACAO")) {//HD 226230
						$corpo = "<P align='left'><STRONG>Chamado finalizado</STRONG> </P>
						<P align='left'>".ucwords($nome_atendente).",</P>
						<P align='justify'>
						O atendimento $callcenter foi concluído por <b>$nome_ultimo_atendente</b>
						</P>";
					} else {
						$corpo = "<P align='left'><STRONG>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align='left'>$nome_atendente,</P>
						<P align='justify'>
						O atendimento $callcenter foi transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para você
						</P>";
					}

					if ($login_fabrica == 24 || $login_fabrica == 11 || $login_fabrica == 85) {

						$corpo .= "<p align='justify'>Informação do atendimento:</p>";
						$corpo .= "<p align='justify'>Nome do consumidor: $nome&nbsp;&nbsp;Telefone: $fone</p>";
						$corpo .= "<p align='justify'>E-mail: $email</p>";
						$corpo .= "<p align='justify'>Endereço:$endereco&nbsp;$numero - $bairro - $cidade_nome - $estado CEP: $cep</p>";
						$corpo .= "<p align='justify'>Tipo de atendimento: $categoria</p>";

						if (strlen($referencia) > 0) {
							$corpo .="<p align='justify'>Produto: $referencia - $descricao</p>";
						}

						$corpo .= "<p align='justify'>Descrição: $reclamado</p>";

					}

					//HD 190736 - Link para chamado no corpo do e-mail
					$corpo .= "<p>Segue abaixo link para acesso ao chamado:</p><p align='justify'><a href='http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter'>http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter</a>";
					// HD 112313 (augusto) - Problema no cabeçalho do email, removidas partes com problema;
		        	$headers  = "MIME-Version: 1.0 \r\n";
					$headers .= "Content-type: text/html \r\n";
					$headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";

					//$email_intervensor = "guilherme.silva@telecontrol.com.br";

                    if (mail($email_intervensor, $assunto, $corpo, $headers)) {
                       $msg = "<br>Foi enviado um email para: ".$email_atendente."<br>";
                    }

				}

			}

			if ($login_fabrica == 24 || $login_fabrica == 11 || $login_fabrica == 85) {
				$sql = "SELECT atendente, sequencia_atendimento from tbl_hd_chamado where hd_chamado = $callcenter";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					$atendente_chamado   = pg_result($res, 0, 0);
					$intervensor_chamado = pg_result($res, 0, 1);

					if($login_admin <> $atendente_chamado AND empty($msg_erro) ){
						$sql = "SELECT login,email from tbl_admin where admin = $ultimo_atendente";
						$res = pg_query($con, $sql);

						$nome_ultimo_atendente  = pg_fetch_result($res, 0, 'login');
						$email_ultimo_atendente = pg_fetch_result($res, 0, 'email');
						$link_atendimento = "<a href='http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter={$callcenter}'>{$callcenter}</a>";
						$assunto .= "Informação do atendimento: $callcenter";
						$corpo    = "<p align='justify'>O atendimento $link_atendimento recebeu uma interação de $login_login</p>";
						$headers  = "MIME-Version: 1.0 \r\n";
						$headers .= "Content-type: text/html \r\n";
						$headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";

						if (mail($email_ultimo_atendente, utf8_encode($assunto), utf8_encode($corpo), $headers)) {
							$msg = "<br>Foi enviado um email para: ".$email_ultimo_atendente."<br>";
						}
					}
				}

				if($xtransferir){

					if (($intervensor_chamado == $login_admin) AND ($atendente_chamado <> $xtransferir)) {
						$msg_erro = "O interventor pode transferir o chamado apenas para o responsável do atendimento";
					}


					if ($intervensor_chamado == $login_admin && strlen($msg_erro) == 0) {
						$sql = "UPDATE tbl_hd_chamado set sequencia_atendimento = null
							WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							and tbl_hd_chamado.hd_chamado = $callcenter";

						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}

			} else {
				$intervensor_chamado = '0';
			}

			if ($login_fabrica == 74){
			 		$sql = "
			 		SELECT admin
			 		FROM tbl_hd_chamado
			 		WHERE hd_chamado = $callcenter
			 		";
			 		$res = pg_query($con,$sql);
	 			if(pg_num_rows($res) > 0) $admin = pg_fetch_result($res,0,0);
		 		if ($admin == 6437 ) {
					$novo_admin = $login_admin;
					if ($admin <> $novo_admin) {

						$sql = "UPDATE tbl_hd_chamado set
									admin = $novo_admin,
									categoria = '$tab_atual'
								WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
								and tbl_hd_chamado.hd_chamado = $callcenter	";

						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}

			if (($ultimo_atendente <> $xtransferir) AND !empty($xtransferir) AND strlen($msg_erro) == 0) {

				$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						and tbl_hd_chamado.hd_chamado = $callcenter	";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);


				# HD 35488
				# Marca HD como pendente
				if ($login_fabrica == 51) {

					$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = 't' WHERE hd_chamado = $callcenter	";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

				}

				if($login_fabrica != 74 AND !empty($ultimo_atendente) AND !empty($xtransferir)){

					$sql = "SELECT login,email from tbl_admin where admin = $ultimo_atendente";
					$res = pg_query($con, $sql);
					$msg_erro .= pg_errormessage($con);

					$nome_ultimo_atendente  = pg_fetch_result($res,0, 'login');
					$email_ultimo_atendente = pg_fetch_result($res,0, 'email');

					$sql = "SELECT login,email from tbl_admin where admin = $xtransferir";
					$res = pg_query($con, $sql);

					$nome_atendente  = pg_fetch_result($res, 0, 'login');
					$email_atendente = pg_fetch_result($res, 0, 'email');

					$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
							) values (
								$callcenter       ,
								current_timestamp ,
								E'Atendimento transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>',
								$login_admin      ,
								't'  ,
								$xstatus_interacao
							)";

					$res = pg_query($con, $sql);
					$msg_erro .= pg_errormessage($con);

				}

				if (strlen($email_ultimo_atendente) > 0 AND strlen($email_atendente) > 0) {

					$assunto = "O atendimento $callcenter foi transferido por $login_login para você";

					if ($login_fabrica == 24) {

						$sql = " SELECT  tbl_hd_chamado_extra.nome       ,
										 tbl_hd_chamado_extra.endereco   ,
										 tbl_hd_chamado_extra.numero     ,
										 tbl_hd_chamado_extra.complemento,
										 tbl_hd_chamado_extra.bairro     ,
										 tbl_hd_chamado_extra.cep        ,
										 tbl_hd_chamado_extra.fone       ,
										 tbl_hd_chamado_extra.email      ,
										 tbl_hd_chamado_extra.cpf        ,
										 tbl_hd_chamado_extra.rg         ,
										 tbl_hd_chamado.categoria  ,
										 tbl_hd_chamado_extra.reclamado  ,
										 tbl_cidade.nome as cidade,
										 tbl_cidade.estado         ,
										 tbl_produto.referencia    ,
										 tbl_produto.descricao
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra USING(hd_chamado)
								JOIN tbl_cidade           USING(cidade)
								LEFT JOIN tbl_produto     USING(produto)
								WHERE tbl_hd_chamado.hd_chamado = $callcenter";

						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0) {

							$nome        = pg_fetch_result($res, 0, 'nome');
							$endereco    = pg_fetch_result($res, 0, 'endereco');
							$numero      = pg_fetch_result($res, 0, 'numero');
							$bairro      = pg_fetch_result($res, 0, 'bairro');
							$cep         = pg_fetch_result($res, 0, 'cep');
							$fone        = pg_fetch_result($res, 0, 'fone');
							$email       = pg_fetch_result($res, 0, 'email');
							$categoria   = pg_fetch_result($res, 0, 'categoria');
							$cidade_nome = pg_fetch_result($res, 0, 'cidade');
							$estado      = pg_fetch_result($res, 0, 'estado');
							$reclamado   = pg_fetch_result($res, 0, 'reclamado');
							$referencia  = @pg_fetch_result($res, 0, 'referencia');
							$descricao   = @pg_fetch_result($res, 0, 'descricao');

							//HD 235203: Colocar vários assuntos para a Suggar
							$achou_categoria_assunto = false;

							foreach ($assuntos as $categoria_assunto => $itens_categoria_assunto) {

								foreach ($itens_categoria_assunto as $label_assunto => $bd_assunto) {

									if ($bd_assunto == $categoria) {
										$categoria = $label_assunto;
										$achou_categoria_assunto = true;
									}

								}

							}

							if ($achou_categoria_assunto == false) {

								if ($categoria == 'reclamacao_produto') $categoria = "Reclamação do Produto";
								if ($categoria == "duvida_produto")     $categoria = "Dúvida do Produto";
								if ($categoria == "reclamacao_at")      $categoria = "Reclamação da Assistência Técnica";
								if ($categoria == "sugestao")           $categoria = "Sugestão";
								if ($categoria == "reclamacao_empresa") $categoria = "Reclamação da Empresa";
								if ($categoria == "procon")             $categoria = "Procon";
								if ($categoria == "onde_comprar")       $categoria = "Onde comprar";

							}

						}

					}

					if ($status_interacao == 'Resolvido' || ($login_fabrica == 74 && $status_interacao == "PROTOCOLO DE INFORMACAO")) {//HD 226230
						$corpo = "<P align=left><STRONG>Chamado finalizado</STRONG> </P>
						<P align=left>".ucwords($nome_atendente).",</P>
						<P align=justify>
						O atendimento $callcenter foi concluído por <b>$nome_ultimo_atendente</b>
						</P>";
					} else {
						$corpo = "<P align=left><STRONG>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=left>$nome_atendente,</P>
						<P align=justify>
						O atendimento $callcenter foi transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para você
						</P>";
					}

					if ($login_fabrica == 24) {

						$corpo .= "<p align=justify>Informação do atendimento:</p>";
						$corpo .= "<p align=justify>Nome do consumidor: $nome&nbsp;&nbsp;Telefone: $fone</p>";
						$corpo .= "<p align=justify>E-mail: $email</p>";
						$corpo .= "<p align=justify>Endereço:$endereco&nbsp;$numero - $bairro - $cidade_nome - $estado CEP: $cep</p>";
						$corpo .= "<p align=justify>Tipo de atendimento: $categoria</p>";

						if (strlen($referencia) > 0) {
							$corpo .="<p align=justify>Produto: $referencia - $descricao</p>";
						}

						$corpo .= "<p align=justify>Descrição: $reclamado</p>";

					}

					//HD 190736 - Link para chamado no corpo do e-mail
					$corpo .= "<p>Segue abaixo link para acesso ao chamado:</p><p align=justify><a href='http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter'>http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter</a>";

					if (strlen($msg_erro) == 0) {
                        //echo "Email ".$email_atendente;
                     /*   $mailer->IsSMTP();
                        $mailer->IsHTML();
                        $mailer->AddAddress($email_atendente);
                        $mailer->Subject = stripslashes($assunto);
                        $mailer->Body = $corpo;

                        if (!$mailer->Send())
						{*/

		        $headers  = "MIME-Version: 1.0 \r\n";
			$headers .= "Content-type: text/html \r\n";
			$headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";
			 if (mail($email_atendente, utf8_encode($assunto), utf8_encode($corpo), $headers)) {
								$msg = "<br>Foi enviado um email para: ".$email_atendente."<br>";
							}
                            //echo "Erro: " . $mailer->ErrorInfo;
                       /* }*/
						else
						{
							 $msg = "<br>Foi enviado um email para: ".$email_atendente."<br>";
                        }
					}

				}

			}
		}

		//hd 14231 22/2/2008
		if (strlen($msg_erro) == 0) {

			// HD 122446 (augusto) - Lenoxx (11) - Salvar informações do consumidor se elas forem modificadas
			// Para Lenox só é possível modificar as informações de cliente se o chamado ainda estiver aberto
			// HD 124579 (augusto) - Todas as fábricas: acrescentar update das informações do Produto
			if (strlen($hd_chamado) > 0 && ($login_fabrica != 11 || (($login_fabrica == 11 && $xstatus_interacao == "'Aberto'") or strlen($protocolo) > 0))) {//*ja tem cadastro no telecontrol/

				$xserie       = (empty($_POST['serie']))       ? 'null' : "'".pg_escape_string($_POST['serie'])."'" ;
				$xnota_fiscal = (empty($_POST['nota_fiscal'])) ? 'null' : pg_escape_string($_POST['nota_fiscal']) ;

				if (empty($_POST['data_nf'])){
					$xdata_nf = "null";
				}else{
					list($ddf, $mdf, $ydf) = explode("/", $_POST['data_nf']);
					if(!checkdate($mdf,$ddf,$ydf)) {
						$msg_erro = "Data NF Inválida <br>";
					}else{
						$xdata_nf = "'".$ydf.'-'.$mdf.'-'.$ddf."'";
					}

				}

				if($login_fabrica == 30 AND $status_interacao == "Aberto"){
					$motivo_situacao = $_POST['motivo_situacao'];
					if(empty($motivo_situacao)){
						$msg_erro = "Informe o motivo por este atendimento estar em aberto";
					}else{
						$cond_motivo_situacao = ", hd_situacao = $motivo_situacao ";
					}
				}

				$sql = "SELECT  tbl_hd_chamado.hd_chamado,
								tbl_hd_chamado.status
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						where tbl_hd_chamado.hd_chamado = $hd_chamado";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_last_error();

				if (pg_num_rows($res) > 0) {

					$xhd_chamado = pg_result($res, 0, 0);
					$xstatus     = pg_fetch_result($res, 0, 'status');

					if (empty($msg_erro)){
						$xcodigo_posto = ($xcodigo_posto == 'null') ? $mr_codigo_posto : $xcodigo_posto;
						$xcodigo_posto = ($xcodigo_posto == '') ? 'null' : $xcodigo_posto;
						$marca = ($marca == '') ? 'null' : $marca;

						if ($login_fabrica == 85 || $login_fabrica == 24  || $login_fabrica == 15 ) {
							$sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $xhd_chamado";
							$res = pg_query($con, $sql);
							$msg_erro .= pg_last_error();

							$array_campos_adicionais = json_decode(pg_fetch_result($res, 0, "array_campos_adicionais"), true);

							if ($login_fabrica == 85) {
								if ($atendimento_ambev == "t") {
									$array_campos_adicionais["atendimento_ambev"]       = $atendimento_ambev;
									$array_campos_adicionais["codigo_ambev"]            = $codigo_ambev;
									$array_campos_adicionais["data_fabricacao_ambev"]   = $data_fabricacao_ambev;
									$array_campos_adicionais["data_encerramento_ambev"] = $data_encerramento_ambev;
									$array_campos_adicionais["atendimento_ambev_nome"]  = $atendimento_ambev_nome;
								} else {
									$array_campos_adicionais["atendimento_ambev"]       = "f";
									$array_campos_adicionais["codigo_ambev"]            = "";
									$array_campos_adicionais["data_fabricacao_ambev"]   = $data_fabricacao_ambev;
									$array_campos_adicionais["data_encerramento_ambev"] = "";
									$array_campos_adicionais["atendimento_ambev_nome"]  = $atendimento_ambev_nome;
								}

								if ($array_campos_adicionais["consumidor_cpf_cnpj"] == "R") {
									$array_campos_adicionais["nome_fantasia"] = utf8_encode($nome_fantasia);
								} else {
									$array_campos_adicionais["nome_fantasia"] = "";
								}
							}

							if ($login_fabrica == 24) {
								if( array_key_exists("ligacao_agendada", $array_campos_adicionais)){
									$array_campos_adicionais["ligacao_agendada"] = $aux_ligacao_agendada;
								}else{

									$array_campos_adicionais = array("ligacao_agendada" => $aux_ligacao_agendada);
								}

							}
							if ($login_fabrica == 15) {
								if( array_key_exists("data_retorno", $array_campos_adicionais)){
									$array_campos_adicionais["data_retorno"] = $aux_data_retorno;
								}else{

									$array_campos_adicionais = array("data_retorno" => $aux_data_retorno);
								}
							}
							if ($login_fabrica != 15 ){
								$array_campos_adicionais["voltagem"] = $voltagem;
							}

							$array_campos_adicionais = json_encode($array_campos_adicionais);

						}else if($login_fabrica >= 138){
							$array_campos_adicionais = json_encode(array("voltagem" => $voltagem));
						}

						$receber_informacoes = $_POST['receber_informacoes'];

						$xreceber_informacoes = (strlen($receber_informacoes) > 0) ? "'$receber_informacoes'" : "'f'";

						$sql = "UPDATE
									tbl_hd_chamado_extra
								SET
									nome                        = UPPER($xconsumidor_nome)       ,
									cpf                         = $xconsumidor_cpf               ,
									rg                          = UPPER($xconsumidor_rg)         ,
									email                       = $xconsumidor_email             ,
									fone                        = substr($xconsumidor_fone,0,21) ,
									cep                         = $xconsumidor_cep               ,
									endereco                    = UPPER($xconsumidor_endereco)   ,
									numero                      = UPPER($xconsumidor_numero)     ,
									complemento                 = UPPER($xconsumidor_complemento),
									bairro                      = UPPER($xconsumidor_bairro)     ,
									receber_info_fabrica        = $xreceber_informacoes          ,
									cidade                      = $cidade                        ,
									hora_ligacao                = $xhora_ligacao                 ,
									origem                      = $xorigem                       ,
									consumidor_revenda          = $xconsumidor_revenda           ,
									fone2                       = substr($xconsumidor_fone2,0,21),
									celular                     = $xconsumidor_fone3             ,
									os                          = case when os notnull then os else $xos end                          ,
									contato_nome                = '$contato_nome'                ,
									produto                     = $xproduto                      ,
									serie                       = substr($xserie,0,21)           ,
									nota_fiscal                 = substr('$xnota_fiscal',0,11)   ,
									data_nf                     = $xdata_nf                      ,
									valor_nf                    = $xvalor_nf                     ,
									marca 	                    = $marca                         ,
									revenda                     = $xrevenda                      ,
									revenda_cnpj                = '$cnpj_revenda'                ,
									revenda_nome                = substr($xrevenda_nome,0,51)    ,
									posto                       = $xcodigo_posto                 ,
									defeito_reclamado           = $xdefeito_reclamado            ,
									defeito_reclamado_descricao = '$hd_extra_defeito'            ,
									reclamado                   = $xreclamado 					 ,
									hd_motivo_ligacao 			= $hd_motivo_ligacao
									".(($login_fabrica == 85) ? ", array_campos_adicionais = '$array_campos_adicionais' " : "")."
									$cond_motivo_situacao";
				if (in_array($login_fabrica, array(11,15, 81, 74, 24, 128, 122, 123, 114)) or $login_fabrica >= 138){
					$sql .= ", array_campos_adicionais = '$array_campos_adicionais'  ";
				}

				if ($login_fabrica == 11) {
					$sql .= ", leitura_pendente = '{$leitura_pendente}' ";
				}

						$sql .=" WHERE
									tbl_hd_chamado_extra.hd_chamado = $xhd_chamado";
						//echo $marca;
						//echo nl2br($sql); exit;
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

					}
				}
			}

			if($login_fabrica == 11) {
				$sql = "UPDATE 	tbl_hd_chamado_extra SET
							array_campos_adicionais = '$array_campos_adicionais',
							 leitura_pendente = '{$leitura_pendente}'
						 WHERE 	tbl_hd_chamado_extra.hd_chamado = $hd_chamado";
						//echo nl2br($sql);exit;
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
			}

			if (($login_fabrica == 52 or $login_fabrica == 85) && empty($msg_erro)) {

				if($login_fabrica == 85){

					if ($atendimento_ambev == "t") {
						$cliente_admin = $cliente_admin_ambev;

						$sql = "UPDATE tbl_hd_chamado set cliente_admin = $cliente_admin where hd_chamado = $xhd_chamado";

						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

					}

				}else{

					if (strlen($cliente_admin) > 0) {

						$sql = "UPDATE tbl_hd_chamado set cliente_admin = $cliente_admin where hd_chamado = $xhd_chamado";

						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

					}

				}

			}

		}

		/* HD 37805 */
		if ($tab_atual == "ressarcimento" and strlen($msg_erro) == 0) {

			$sql  = "SELECT hd_chamado FROM tbl_hd_chamado_extra_banco WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con, $sql);

			if (@pg_num_rows($resx) == 0) {

				$sql = "INSERT INTO tbl_hd_chamado_extra_banco ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con, $sql);

				$msg_erro .= pg_errormessage($con);

			}

			$sql = "UPDATE tbl_hd_chamado_extra_banco SET
									banco            = $xbanco,
									agencia          = $xagencia,
									contay           = $xcontay,
									nomebanco        = $xnomebanco,
									favorecido_conta = $xfavorecido_conta,
									cpf_conta        = $xcpf_conta,
									tipo_conta       = $xtipo_conta
					WHERE hd_chamado = $hd_chamado";

			$res       = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);

			$sql  = "SELECT hd_chamado FROM tbl_hd_chamado_troca WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con, $sql);

			if (@pg_num_rows($resx) == 0) {

				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);

			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento    = $xdata_pagamento,
							ressarcimento     = 't',
							numero_objeto     = NULL,
							nota_fiscal_saida = NULL,
							data_nf_saida     = NULL,
							produto           = NULL,
							valor_produto     = $xvalor_produto,
							valor_inpc        = $xvalor_inpc,
							valor_corrigido   = $xvalor_corrigido
					WHERE hd_chamado = $hd_chamado";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($valor_produto) > 0 AND strlen($valor_inpc) > 0 AND strlen($msg_erro) == 0) {

				$sql  = "SELECT CURRENT_DATE - data_nf AS qtde_dias FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado ";
				$resx = @pg_query($con, $sql);

				if (@pg_num_rows($resx) > 0) {

					$qtde_dias = pg_fetch_result($resx, 0, 'qtde_dias');

					if ($qtde_dias > 0) {

						$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);

						$sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
						$res = pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);

					}

				}

			}

		}


		/* HD 37805 */
		if ($tab_atual == "sedex_reverso" and strlen($msg_erro) == 0) {

			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_troca
					WHERE hd_chamado = $hd_chamado ";

			$resx = @pg_query($con, $sql);

			if (@pg_num_rows($resx) == 0) {

				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento       = NULL,
							ressarcimento        = 'f',
							numero_objeto        = $xnumero_objeto,
							nota_fiscal_saida    = $xnota_fiscal_saida,
							data_nf_saida        = $xdata_nf_saida,
							produto              = $xproduto_troca,
							data_retorno_produto = $xdata_retorno_produto
					WHERE hd_chamado = $hd_chamado";

			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

		}

		if ($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica == 25) {

			if (strlen($es_data_compra) == 0) {
				$msg_erro .= "Informe a data da Compra do produto.";
			}

		}

		$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
		$res = pg_query($con, $sql);

		$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
		$res = pg_query($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0) {//HD 129655 - Gravar dúvidas selecionadas [augusto]
			gravarFaq();
		}

	}

	if ($login_fabrica == 24 && strlen($msg_erro) == 0) {//HD 751794 e (262718 - Adicionando mais campos)
		if($xreclamado == "null"){
			$xreclamado = "''";
		}
		$sql = "UPDATE 	tbl_hd_chamado_extra
				SET 	orientacao_uso = '$orientacao_uso',
						reclamado      = E$xreclamado      ,
						posto          = $xcodigo_posto   ,
						os             = $xos
				WHERE 	hd_chamado = $hd_chamado
				AND 	abre_os IS NOT TRUE";

		$res = pg_query($con, $sql);

	}

	// HD 674943
	if ($login_fabrica == 85 && strlen($msg_erro) == 0) {

		if (!empty($_POST['sem_resposta'])) {

			$campo_resposta = $_POST['sem_resposta'];
			$campo_sem_resp = $campo_resposta == 'recusou_pesquisa' ? 'cliente_nao_encontrado' : 'recusou_pesquisa';

			$sql = "UPDATE tbl_hd_chamado_extra
					SET $campo_resposta = 't', $campo_sem_resp = null
					WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);

		} else {

			$sql = "UPDATE tbl_hd_chamado_extra
					SET recusou_pesquisa = null, cliente_nao_encontrado = null
					WHERE hd_chamado = $hd_chamado";

			$res = pg_query($con,$sql);

			$sql = "SELECT pergunta
					FROM tbl_pergunta
					JOIN tbl_tipo_pergunta ON tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta AND tbl_tipo_pergunta.ativo
					WHERE tbl_pergunta.fabrica = $login_fabrica
					AND tbl_pergunta.ativo";

			$res = pg_query($con,$sql);

			for ($i = 0; $i < pg_num_rows($res); $i++) {

				$pergunta = pg_result($res,$i,'pergunta');
				$nota     = $_POST['nota_'.$pergunta];

				if (!isset($_POST['nota_'.$pergunta]) || empty($nota)) {
					continue;
				}

				if (strlen($hd_chamado) > 0) {
					$campo_gravar = $hd_chamado;
					$cond         = ' hd_chamado = ' . $hd_chamado;
				} else {
					$campo_gravar = $callcenter;
					$cond         = ' hd_chamado = ' . $callcenter;
				}

				$sql = "SELECT resposta
						FROM tbl_resposta
						JOIN tbl_pergunta USING(pergunta)
						JOIN tbl_tipo_pergunta USING(tipo_pergunta)
						WHERE $cond AND tbl_pergunta.fabrica = $login_fabrica
						AND tbl_resposta.pergunta = $pergunta";

				$res2 = pg_query($con, $sql);

				if (pg_num_rows($res2)) {
					$sql = "UPDATE tbl_resposta SET nota = $nota WHERE pergunta = $pergunta AND $cond";
				} else {
					$sql = "INSERT INTO tbl_resposta(hd_chamado, pergunta, nota) VALUES($campo_gravar,$pergunta,$nota)";
				}

				$res2 = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

			}

		}

	} // HD 674943

	/********************************************* PRE-OS *******************************************/
	// HD 120306 - envia e-mail para o posto informando pre-OS cadastrada

	if (strlen($msg_erro) == 0) {

		if ($abre_os == 't') {

			//HD 211895: Caso o chamado já esteja aberto anteriormente, atualiza abre_os='t'
			if (strlen($callcenter)) {

				$sql = "UPDATE tbl_hd_chamado_extra SET abre_os=true WHERE hd_chamado=$callcenter";
				$res = pg_query($con, $sql);

				$msg_erro .= pg_errormessage($con);

			}

			switch ($login_fabrica) {

				case 2: $sac_email = "sac@dynacom.com.br";
					break;
				case 52: $sac_email = "sac@fricon.com.br";
					break;
				case 96:
					$sac_email = "andre.dias@br.bosch.com; renato.lima2@br.bosch.com";
					break;

			}

			$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
			$res = pg_query($con,$sql);

			$msg_erro .= pg_errormessage($con);

			if (pg_num_rows($res) > 0) {
				$admin_email = pg_fetch_result($res, 0, 'email');
			} else {
				$admin_email = $sac_email;
			}

			$sql = "SELECT contato_email from tbl_posto_fabrica where posto = $xcodigo_posto and fabrica = $login_fabrica";
			$res = pg_query($con, $sql);

			if (@pg_num_rows($res) > 0) {

				$email_posto = pg_fetch_result($res, 0, 'contato_email');

			} else {

				$sql = "SELECT email from tbl_posto where posto = $xcodigo_posto";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) $email_posto = pg_fetch_result($res, 0, 'email');

			}

			if ($login_fabrica == 30) {
				$subject = "Nova OS :: Call-center $clogin_fabrica_nome;";
			} else {
				$subject = "Nova Pré-OS :: Call-center $clogin_fabrica_nome;";
			}

			//HD 199901 - Alterar mensagem de abertura de pré-OS
			$sql = " SELECT tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto.nome AS posto_nome,
							tbl_fabrica.nome AS fabrica_nome,
							TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY HH24:MI') AS data_atendimento,
							tbl_hd_chamado_extra.nome,
							tbl_hd_chamado_extra.endereco,
							tbl_hd_chamado_extra.numero,
							tbl_hd_chamado_extra.complemento,
							tbl_hd_chamado_extra.bairro,
							tbl_cidade.nome AS cidade_nome,
							tbl_cidade.estado,
							tbl_hd_chamado_extra.revenda_nome,
							tbl_revenda.cnpj AS revenda_cnpj,
							tbl_admin.nome_completo AS admin_nome,
							tbl_admin.email AS admin_email,
							tbl_hd_chamado_extra.posto
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
						JOIN tbl_posto ON tbl_hd_chamado_extra.posto=tbl_posto.posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
						JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica=tbl_fabrica.fabrica
						LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
						LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda=tbl_revenda.revenda
						JOIN tbl_admin ON tbl_hd_chamado.admin=tbl_admin.admin
					WHERE tbl_hd_chamado.hd_chamado=$hd_chamado
					AND tbl_hd_chamado.fabrica=$login_fabrica
					AND tbl_posto_fabrica.fabrica=$login_fabrica ";

			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {

				$email_preos_codigo_posto     = pg_fetch_result($res, 0, 'codigo_posto');
				$email_preos_posto_nome       = pg_fetch_result($res, 0, 'posto_nome');
				$email_preos_fabrica_nome     = pg_fetch_result($res, 0, 'fabrica_nome');
				$email_preos_data_atendimento = pg_fetch_result($res, 0, 'data_atendimento');
				$email_preos_nome             = pg_fetch_result($res, 0, 'nome');
				$email_preos_endereco         = pg_fetch_result($res, 0, 'endereco');
				$email_preos_numero           = pg_fetch_result($res, 0, 'numero');
				$email_preos_complemento      = pg_fetch_result($res, 0, 'complemento');
				$email_preos_bairro           = pg_fetch_result($res, 0, 'bairro');
				$email_preos_bairro           = preg_replace("/'/g","",$email_preos_bairro);
				$email_preos_cidade_nome      = pg_fetch_result($res, 0, 'cidade_nome');
				$email_preos_cidade_nome      = preg_replace("/'/g","",$email_preos_cidade_nome);
				$email_preos_estado           = pg_fetch_result($res, 0, 'estado');
				$email_preos_revenda_nome     = pg_fetch_result($res, 0, 'revenda_nome');
				$email_preos_revenda_cnpj     = pg_fetch_result($res, 0, 'revenda_cnpj');
				$email_preos_admin_nome       = pg_fetch_result($res, 0, 'admin_nome');
				$email_preos_admin_email      = pg_fetch_result($res, 0, 'admin_email');
				$comunicado_posto             = pg_fetch_result($res, 0, 'posto');

				if ($login_fabrica == 52) {

					$sql = " SELECT tbl_produto.referencia,
									tbl_produto.descricao
								FROM tbl_hd_chamado_item
								JOIN tbl_produto ON tbl_hd_chamado_item.produto=tbl_produto.produto
								WHERE tbl_hd_chamado_item.hd_chamado=$hd_chamado ";

					$res = pg_query($con, $sql);

					$produtos = array();

					for ($p = 0; $p < pg_num_rows($res); $p++) {
						$produtos[] = "[" . pg_fetch_result($res, $p, 'referencia') . "] " . pg_fetch_result($res, $p, 'descricao');
					}

					$produtos = implode(", ", $produtos);

				} else {

					$sql = " SELECT tbl_produto.referencia,
									tbl_produto.descricao
								FROM tbl_hd_chamado_extra
								JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto
								WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado ";

					$res = pg_query($con, $sql);

					$produtos = "[" . pg_fetch_result($res, 0, 'referencia') . "] " . pg_fetch_result($res, 0, 'descricao');

				}

				$email_endereco = "";

				if ($email_preos_endereco)    $email_endereco .= $email_preos_endereco;
				if ($email_preos_numero)      $email_endereco .= ", " . $email_preos_numero;
				if ($email_preos_complemento) $email_endereco .= " " . $email_preos_complemento;
				if ($email_preos_bairro)      $email_endereco .= " - " . $email_preos_bairro;
				if ($email_preos_cidade_nome) $email_endereco .= " - " . $email_preos_cidade_nome;
				if ($email_preos_estado)      $email_endereco .= " - " . $email_preos_estado;

				if ($email_preos_revenda_cnpj) $email_preos_revenda_cnpj = " - " . $email_preos_revenda_cnpj;

				$message = "Autorizada $email_preos_codigo_posto - $email_preos_posto_nome

				O Callcenter da Fábrica $email_preos_fabrica_nome, abriu um atendimento que se tornou uma ";

				if ($login_fabrica == 30) {
					$message .= "OS";
				} else {
					$message .= "pré-OS";
				}

				$message .= " para ser atendido pelo seu posto autorizado.  Segue as informações da ";

				if ($login_fabrica == 30) {
					$message .= "OS";
				} else {
					$message .= "pré-OS";
				}

				$message .= " :

				Atendimento do Call-Center nº $hd_chamado - Aberto por $email_preos_admin_nome em $email_preos_data_atendimento
				Produto: $produtos
				Consumidor: $email_preos_nome ($email_endereco)
				Revenda: $email_preos_revenda_cnpj $email_preos_revenda_nome";

				if ($login_fabrica <> 59) {
					$message .= " Favor completar este atendimento o mais rápido possível, e qualquer dúvida, entrar em contato com ";
				}

				$message .= "$email_preos_admin_nome Callcenter";

				$message = str_replace("\n", "<br>", $message);

                $mailer->IsSMTP();
                $mailer->IsHTML();
                $mailer->AddAddress($email_preos_admin_email);
                $mailer->Subject = $subject;
                $mailer->Body = $message;
                //$mailer->Send();

                $mailer->ClearAddresses();
                $mailer->AddAddress($email_posto);
                //$mailer->Send();
			}

			if ($login_fabrica == 96) $admin_email = "null";

			$peca                       = "null";
			$produto                    = "null";
			$aux_familia                = "null";
			$aux_linha                  = "null";
			$aux_extensao               = "null";
			$aux_descricao              = substr($subject, 0, 50);
			$aux_mensagem               = $message;
			$aux_tipo                   = "Comunicado";
			$posto                      = ($xcodigo_posto =='null' or empty($xcodigo_posto)) ?$comunicado_posto : $xcodigo_posto;
			$aux_obrigatorio_os_produto = "'f'";
			$aux_obrigatorio_site       = "'t'";
			$aux_tipo_posto             = "null";
			$aux_ativo                  = "'t'";
			$aux_estado                 = "null";
			$aux_pais                   = "'BR'";
			$remetente_email            = "$admin_email";
			$pedido_faturado            = "'f'";
			$pedido_em_garantia         = "'f'";
			$digita_os                  = "'f'";
			$reembolso_peca_estoque     = "'f'";

			// if (empty($posto)) {
			// 	$posto = 'null';
			// }

			if($login_fabrica <> 59){
				$sql = "INSERT INTO tbl_comunicado (
							peca                   ,
							produto                ,
							familia                ,
							linha                  ,
							extensao               ,
							descricao              ,
							mensagem               ,
							tipo                   ,
							fabrica                ,
							obrigatorio_os_produto ,
							obrigatorio_site       ,
							posto                  ,
							tipo_posto             ,
							ativo                  ,
							estado                 ,
							pais                   ,
							remetente_email        ,
							pedido_faturado        ,
							pedido_em_garantia     ,
							digita_os              ,
							reembolso_peca_estoque
						) VALUES (
							$peca                       ,
							$produto                    ,
							$aux_familia                ,
							$aux_linha                  ,
							$aux_extensao               ,
							'$aux_descricao'            ,
							'$aux_mensagem'             ,
							'$aux_tipo'                 ,
							$login_fabrica              ,
							$aux_obrigatorio_os_produto ,
							$aux_obrigatorio_site       ,
							$posto                      ,
							$aux_tipo_posto             ,
							$aux_ativo                  ,
							$aux_estado                 ,
							$aux_pais                   ,
							'$remetente_email'          ,
							$pedido_faturado            ,
							$pedido_em_garantia         ,
							$digita_os                  ,
							$reembolso_peca_estoque
						);";
				echo $sql;
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}
			// echo nl2br($sql);exit;

		}//IF PARA CONTROLAR ABERTURA DE PRE-OS

	}//IF QUE VERIFICA SE TEM ERROS
	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec

	if ($abre_ordem_servico == 't' && strlen($msg_erro) == 0) {

		/************************************************************************************/
		/******************************* VALIDAÇÕES DOS CAMPOS *******************************/
		/************************************************************************************/
		$sql = "SELECT  tbl_hd_chamado_extra.hd_chamado,
						tbl_hd_chamado_extra.posto,
						tbl_hd_chamado_extra.nome,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado,
						tbl_hd_chamado_extra.fone,
						tbl_hd_chamado_extra.cpf,
						tbl_hd_chamado_extra.qtde_km,
						tbl_hd_chamado_extra.endereco,
						tbl_hd_chamado_extra.bairro,
						tbl_hd_chamado_extra.celular,
						tbl_hd_chamado_extra.fone2,
						tbl_hd_chamado_extra.revenda,
						tbl_hd_chamado_extra.consumidor_revenda,";

		if ($login_fabrica == 52){

			$sql .= "	tbl_hd_chamado_item.produto,
						tbl_hd_chamado_item.defeito_reclamado,
						tbl_hd_chamado_item.serie,";

		}else{

			$sql .= "	tbl_hd_chamado_extra.produto,
						tbl_hd_chamado_extra.defeito_reclamado,
						tbl_hd_chamado_extra.defeito_reclamado_descricao, ";

		}

		$sql .= "		tbl_hd_chamado_extra.nota_fiscal,
						tbl_hd_chamado_extra.data_nf,
						tbl_hd_chamado_extra.marca
					FROM tbl_hd_chamado_extra
					LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade ";

		if ($login_fabrica == 52){
			$sql .= " JOIN tbl_hd_chamado_item on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado and tbl_hd_chamado_item.hd_chamado = $hd_chamado";
		}

		$sql .= "
					WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado";

		if ($login_fabrica == 52){

			$sql .= "AND tbl_hd_chamado_item.produto is not null
					AND tbl_hd_chamado_item.serie is not null
					AND tbl_hd_chamado_item.os is null ";

		}

		$res = pg_query($con, $sql);



		if (pg_num_rows($res)>0){

			for ($i=0; $i < pg_num_rows($res); $i++) {

				$s_posto                       = trim(pg_fetch_result($res, $i, 'posto'));
				$s_nome                        = trim(pg_fetch_result($res, $i, 'nome'));
				$s_cidade                      = trim(pg_fetch_result($res, $i, 'cidade'));
				$s_estado                      = trim(pg_fetch_result($res, $i, 'estado'));
				$s_fone                        = trim(pg_fetch_result($res, $i, 'fone'));
				$s_fone2                       = trim(pg_fetch_result($res, $i, 'fone2'));
				$s_celular                     = trim(pg_fetch_result($res, $i, 'celular'));
				$s_cpf                         = trim(pg_fetch_result($res, $i, 'cpf'));
				$s_endereco                    = trim(pg_fetch_result($res, $i, 'endereco'));
				$s_bairro                      = trim(pg_fetch_result($res, $i, 'bairro'));
				$s_produto                     = trim(pg_fetch_result($res, $i, 'produto'));
				$s_defeito_reclamado           = trim(pg_fetch_result($res, $i, 'defeito_reclamado'));
				$s_defeito_reclamado_descricao = trim(pg_fetch_result($res, $i, 'defeito_reclamado_descricao'));
				$s_nota_fiscal                 = trim(pg_fetch_result($res, $i, 'nota_fiscal'));
				$s_data_nf                     = trim(pg_fetch_result($res, $i, 'data_nf'));
				$s_marca                       = trim(pg_fetch_result($res, $i, 'marca'));


				if (empty($s_posto)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informar o posto autorizado<br>";
				}

				if (empty($s_nome)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o nome do consumidor<br>";
				}

				if (empty($s_cidade)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe a cidade o consumidor<br>";
				}

				if (empty($s_estado)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o estado do consumidor<br>";
				}

				if (empty($s_fone) && empty($s_celular) && empty($s_fone2)){
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe pelo menos um dos telefones do cliente<br>";
				}

				if (empty($s_cpf) and $login_fabrica <> 30 and $login_fabrica <> 52){
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o CPF do consumidor<br>";
				}

				if (empty($s_endereco)){
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o endereço do consumidor<br>";
				}

				if (empty($s_bairro)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o bairro do consumidor<br>";
				}

				if (empty($s_produto)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o produto<br>";
				}

				if ($login_fabrica <> 52){

					if (empty($s_defeito_reclamado) && empty($s_defeito_reclamado_descricao)){
						$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o defeito reclamado no produto<br>";
					}

				}else{

					if (empty($s_defeito_reclamado)){
						$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o defeito reclamado no produto<br>";
					}

				}

				if (empty($s_nota_fiscal)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o número da nota fiscal<br>";
				}

				if (empty($s_data_nf)) {
					$msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe a data da nota fiscal<br>";
				}

			}
		}


		if (strlen($msg_erro) == 0) {
			/************************************************************************************/
			/********************************** INSERÇÃO DA OS **********************************/
			/************************************************************************************/
			$sql = "SELECT tbl_hd_chamado_extra.posto from tbl_hd_chamado_extra where hd_chamado=$hd_chamado";
			$res = pg_query($con,$sql);
			$xcodigo_posto = trim(pg_fetch_result($res, 0, 'posto'));

			if ($login_fabrica == 52){
				$campo_fricon = "
								qtde_km,
								";
				$valor_campo_fricon =  "
									    tbl_hd_chamado_extra.qtde_km,
										";
			}


			$sql = "INSERT INTO tbl_os (
						fabrica,
						posto,
						data_abertura,
						consumidor_nome,
						consumidor_cidade,
						consumidor_estado,
						consumidor_fone,
						consumidor_cpf,
						consumidor_endereco,
						consumidor_numero,
						consumidor_cep,
						consumidor_complemento,
						consumidor_bairro,
						consumidor_email,
						consumidor_celular,
						consumidor_fone_comercial,
						consumidor_revenda,
						revenda,
						revenda_cnpj,
						revenda_nome,
						$campo_fricon
						revenda_fone,
						produto,
						serie,
						defeito_reclamado,
						defeito_reclamado_descricao,
						nota_fiscal,
						data_nf,
						marca,
						admin,
						hd_chamado,
						cliente_admin,
						obs,
						observacao
					) SELECT $login_fabrica,
						tbl_hd_chamado_extra.posto,
						tbl_hd_chamado.data,
						tbl_hd_chamado_extra.nome,
						tbl_cidade.nome,
						tbl_cidade.estado,
						tbl_hd_chamado_extra.fone,
						tbl_hd_chamado_extra.cpf,
						tbl_hd_chamado_extra.endereco,
						tbl_hd_chamado_extra.numero,
						tbl_hd_chamado_extra.cep,
						tbl_hd_chamado_extra.complemento,
						tbl_hd_chamado_extra.bairro,
						tbl_hd_chamado_extra.email,
						tbl_hd_chamado_extra.celular,
						tbl_hd_chamado_extra.fone2,
						tbl_hd_chamado_extra.consumidor_revenda,
						tbl_hd_chamado_extra.revenda,
						tbl_hd_chamado_extra.revenda_cnpj,
						tbl_hd_chamado_extra.revenda_nome, $valor_campo_fricon";

			if ($login_fabrica <> 52){

				$sql .= "
						tbl_revenda.fone,
						tbl_hd_chamado_extra.produto,
						tbl_hd_chamado_extra.serie,
						tbl_hd_chamado_extra.defeito_reclamado,
						tbl_hd_chamado_extra.defeito_reclamado_descricao,
						";
			}else{
				$sql .= "
						'$fone_revenda',
						tbl_hd_chamado_item.produto,

						tbl_hd_chamado_item.serie,
						tbl_hd_chamado_item.defeito_reclamado,
						null,
						";
			}

			$sql .= "
						tbl_hd_chamado_extra.nota_fiscal,
						tbl_hd_chamado_extra.data_nf,
						tbl_hd_chamado_extra.marca,
						tbl_hd_chamado.admin,
						$hd_chamado,
						tbl_hd_chamado.cliente_admin,
						'Ordem de Serviço aberta pelo CallCenter, atendimento $hd_chamado',
						'Ordem de Serviço aberta pelo CallCenter, atendimento $hd_chamado'
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
			";

			if ($login_fabrica == 52){
				$sql .= "
					JOIN tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
				";
			}

			$sql .= "
					LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda=tbl_revenda.revenda
					LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
					WHERE tbl_hd_chamado.hd_chamado = $hd_chamado ";

			if ($login_fabrica == 52){
				$sql .= "
					AND tbl_hd_chamado_item.produto is not null
					AND tbl_hd_chamado_item.serie is not null
					AND tbl_hd_chamado_item.os is null
				";
			}

			$sql .= " RETURNING os; ";
			$res = pg_query($con, $sql);


			if($login_fabrica == 52){

				$os_aberta = pg_result($res, 0, 0);
				if(!empty($os_aberta)){
					$json = json_encode($campos_adicionais2);
					$json = str_replace("\\", "", $json);

					$sql = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os_aberta, $login_fabrica, '$json')";
					$res = pg_query($con, $sql);
				}
			}

			// echo "OS's QUE FORAM AABERTAS:<br>";
			// foreach (pg_fetch_all($res) as $key => $value) {
			// 	foreach ($value as $val) {
			// 		echo "<br>".$val;
			// 		# code...
			// 	}
			// }
			// echo "<br>----------------------------------------------------<br>HD CHAMADO ITEM - GRAVADOS<br><br>";
			// $ressss = pg_query($con,"SELECT os,produto,serie,hd_chamado from tbl_hd_chamado_item where hd_chamado = $hd_chamado");
			// var_dump(pg_fetch_all($ressss));

 			if (strlen($msg_erro) == 0) {

				if ($login_fabrica <> 52){

					$os_aberta = pg_result($res, 0, 0);

					$sql = "SELECT fn_valida_os($os_aberta, $login_fabrica)";
					$res = @pg_query($con, $sql);
					$erro_valida = pg_errormessage($con);

					if (strlen($erro_valida) == 0) {

						$sql = "SELECT sua_os FROM tbl_os WHERE os=$os_aberta";
						$res = @pg_query($con, $sql);

						$msg_erro .= pg_errormessage($con);
						$sua_os_aberta = pg_result($res, 0, sua_os);

						$sql = "UPDATE tbl_hd_chamado_extra SET os=$os_aberta WHERE hd_chamado=$hd_chamado";
						$res = @pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);

						if ($login_fabrica == 30) {//Insere o intervenção de KM para esmaltec - hd 311031

							$sql = "INSERT into tbl_os_status (os,status_os,observacao) values ($os_aberta,98,'Os aberto pelo Callcenter');";
							$res = @pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);

						}

					} else {

						$erro_valida = explode("CONTEXT", $erro_valida);
						$erro_valida = explode("ERROR:", $erro_valida[0]);
						$erro_valida = trim($erro_valida[1]);
						$msg_erro .= $erro_valida . "<br>";

					}

				}else{


					$sql = "SELECT  tbl_os.os,
									tbl_os.sua_os,
									tbl_produto.referencia,
									tbl_produto.descricao,
									tbl_os.serie,
									tbl_hd_chamado_item.hd_chamado_item
							FROM tbl_os
							JOIN tbl_hd_chamado_item ON(tbl_os.produto = tbl_hd_chamado_item.produto and tbl_os.serie = UPPER(tbl_hd_chamado_item.serie))
							LEFT JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
							WHERE tbl_hd_chamado_item.hd_chamado = $hd_chamado
							AND tbl_os.hd_chamado = $hd_chamado
							AND tbl_hd_chamado_item.produto is not null
							AND tbl_hd_chamado_item.os is null
							AND tbl_os.fabrica=$login_fabrica ";
					$resX = pg_query($con,$sql);
					// echo "<br>----------------------------------------------------<br>RESULTADO resX<br><br>";
					// echo nl2br($sql);
					// var_dump(pg_fetch_all($resX));

					if (pg_num_rows($resX)>0){

						$abriuOsNova = 't';
						$osNova = array();

						for ($s=0; $s < pg_num_rows($resX); $s++) {

							$os_abertaX           = pg_fetch_result($resX, $s,0);
							$sua_os_aberta       = pg_fetch_result($resX, $s,1);
							$prod_ref_os_aberta  = pg_fetch_result($resX, $s,2);
							$prod_desc_os_aberta = pg_fetch_result($resX, $s,3);
							$hd_chamado_item_os_new = pg_fetch_result($resX, $s,5);

							$osNova[]      = $os_abertaX;
							$osNovaDados[] = $os_abertaX.";".$prod_ref_os_aberta.";".$prod_desc_os_aberta;

							$sql = "SELECT fn_valida_os($os_abertaX, $login_fabrica)";
							$res = pg_query($con, $sql);
							$erro_valida = pg_errormessage($con);

							if (strlen($erro_valida) == 0) {

								$sql = "UPDATE tbl_hd_chamado_item
										set os = $os_abertaX
										WHERE hd_chamado_item = $hd_chamado_item_os_new";
								$res = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os_extra
										set obs = tbl_hd_chamado_extra.posto_nome
										from tbl_hd_chamado_extra
										where tbl_os_extra.i_fabrica = $login_fabrica
										AND tbl_os_extra.os = $os_abertaX
										and tbl_hd_chamado_extra.hd_chamado=$hd_chamado";
								$res = pg_query($con, $sql);
								$msg_erro .= pg_errormessage($con);

							} else {

								$erro_valida = explode("CONTEXT", $erro_valida);
								$erro_valida = explode("ERROR:", $erro_valida[0]);
								$erro_valida = trim($erro_valida[1]);
								$msg_erro .= $erro_valida . "<br>";

							}

						}

					}else{

						$abriuOsNova = 'f';

					}


				}

			}

			// exit;


			//Envia e-mail para o posto informando OS cadastrada
			if (strlen($msg_erro) == 0) {

				$sql = "SELECT email FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				switch($login_fabrica) {
					case 2:
						$sac_email = "sac@dynacom.com.br";
					break;

					case 52:
						$sac_email = "sac@fricon.com.br";
					break;
					case 96:
						//$sac_email = "brayan@telecontrol.com.br; ronald@telecontrol.com.br";
					break;
				}


				if (pg_num_rows($res) > 0) {
					$admin_email = pg_fetch_result($res, 0, 'email');
				} else {
					$admin_email = $sac_email;
				}



				$sql = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = $xcodigo_posto AND fabrica=$login_fabrica";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) > 0) {
					$email_posto = pg_fetch_result($res, 0, 'contato_email');
				} else {
					$sql = "SELECT email from tbl_posto where posto = $xcodigo_posto";
					$res = pg_query($con, $sql);
					$email_posto = pg_fetch_result($res, 0, email);
				}



				if ($login_fabrica == 52){

					if (count($osNova)>0){

						$sua_os_aberta = implode(', ', $osNova);

						$subject  = "Nova ORDEM DE SERVIÇO nº $sua_os_aberta :: $login_fabrica_nome
						";

					}


				}else{
					$subject  = "Nova ORDEM DE SERVIÇO nº $sua_os_aberta :: $login_fabrica_nome
					";
				}

				// echo $subject;exit;
				//Mensagem de abertura de ORDEM DE SERVICO
				$sql = "SELECT
							tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto.nome AS posto_nome,
							tbl_fabrica.nome AS fabrica_nome,
							TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY HH24:MI') AS data_atendimento,
							tbl_hd_chamado_extra.nome,
							tbl_hd_chamado_extra.endereco,
							tbl_hd_chamado_extra.numero,
							tbl_hd_chamado_extra.complemento,
							tbl_hd_chamado_extra.bairro,
							tbl_cidade.nome AS cidade_nome,
							tbl_cidade.estado,
							tbl_hd_chamado_extra.revenda_nome,
							tbl_revenda.cnpj AS revenda_cnpj,
							tbl_admin.nome_completo AS admin_nome
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
						JOIN tbl_posto ON tbl_hd_chamado_extra.posto=tbl_posto.posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
						JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica=tbl_fabrica.fabrica
						LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
						LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda=tbl_revenda.revenda
						JOIN tbl_admin ON tbl_hd_chamado.admin=tbl_admin.admin

						WHERE tbl_hd_chamado.hd_chamado=$hd_chamado
						AND tbl_hd_chamado.fabrica=$login_fabrica
						AND tbl_posto_fabrica.fabrica=$login_fabrica ";

				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {

					$email_os_codigo_posto     = pg_fetch_result($res, 0, 'codigo_posto');
					$email_os_posto_nome       = pg_fetch_result($res, 0, 'posto_nome');
					$email_os_fabrica_nome     = pg_fetch_result($res, 0, 'fabrica_nome');
					$email_os_data_atendimento = pg_fetch_result($res, 0, 'data_atendimento');
					$email_os_nome             = pg_fetch_result($res, 0, 'nome');
					$email_os_endereco         = pg_fetch_result($res, 0, 'endereco');
					$email_os_numero           = pg_fetch_result($res, 0, 'numero');
					$email_os_complemento      = pg_fetch_result($res, 0, 'complemento');
					$email_os_bairro           = pg_fetch_result($res, 0, 'bairro');
					$email_os_cidade_nome      = addslashes(pg_fetch_result($res, 0, 'cidade_nome'));
					$email_os_estado           = pg_fetch_result($res, 0, 'estado');
					$email_os_revenda_nome     = pg_fetch_result($res, 0, 'revenda_nome');
					$email_os_revenda_cnpj     = pg_fetch_result($res, 0, 'revenda_cnpj');
					$email_os_admin_nome       = pg_fetch_result($res, 0, 'admin_nome');

					if ($login_fabrica <> 52) {

						$sql = " SELECT tbl_produto.referencia,
										tbl_produto.descricao
									FROM tbl_hd_chamado_extra
									JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto
									WHERE tbl_hd_chamado_extra.hd_chamado=$hd_chamado ";

						$res = pg_query($con, $sql);

						$produtos = "[" . pg_fetch_result($res, 0, referencia) . "] " . pg_fetch_result($res, 0, descricao);

					}

					$email_endereco = "";

					if ($email_os_endereco)     $email_endereco .= $email_os_endereco;
					if ($email_os_numero)       $email_endereco .= ", " . $email_os_numero;
					if ($email_os_complemento)  $email_endereco .= " " . $email_os_complemento;
					if ($email_os_bairro)       $email_endereco .= " - " . $email_os_bairro;
					if ($email_os_cidade_nome)  $email_endereco .= " - " . $email_os_cidade_nome;
					if ($email_os_estado)       $email_endereco .= " - " . $email_os_estado;
					if ($email_os_revenda_cnpj) $email_os_revenda_cnpj = " - " . $email_os_revenda_cnpj;

					$message = "<p> Autorizada $email_os_codigo_posto - $email_os_posto_nome </p>
	<p >O Callcenter da Fábrica $email_os_fabrica_nome, abriu um atendimento que se tornou uma ORDEM DE SERVIÇO para ser atendido pelo seu posto autorizado.</p>
	<p >Segue as informações da ORDEM DE SERVIÇO nº $sua_os_aberta:</p>
	<p >	Atendimento do Call-Center nº $hd_chamado - Aberto por $email_os_admin_nome em $email_os_data_atendimento </p>
	";

	if ($login_fabrica == 52) {
		if (count($osNovaDados)> 0) {

			$message .= "<fieldset style=\"width:450px\">	<legend>OS e Produtos</legend>";
			for ($o=0; $o < count($osNovaDados); $o++) {
				$dadosOs = $osNovaDados[$o];
				$dadosOs = explode(';', $dadosOs);
				$message .= "<p>Os: ".$dadosOs[0]."</p>";
				$message .= "<ul >	<li>".$dadosOs[1]." - ".$dadosOs[2]."</li>	 </ul>
							";

				unset($dadosOs);
			}

			$message .= "</fieldset>";

		}
	} else {
		$message .= "<p > Produto: $produtos</p>";
	}

	$message .= "<p >Consumidor: $email_os_nome ($email_endereco) </p>
	<p >Revenda: $email_os_revenda_cnpj $email_os_revenda_nome </p>
	<p >Favor completar este atendimento o mais rápido possível, e qualquer dúvida, entrar em contato.</p>
	<p >$email_os_admin_nome</p>
	<p>Callcenter $login_fabrica_nome</p>";

					$message = str_replace("\n", "<br>", $message);
					$headers = "From: Call-center <$admin_email>\n";

					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";


                    $mailer->IsSMTP();
                    $mailer->IsHTML();

                    $mailer->Subject = $subject;
                    $mailer->Body = $message;

                    if ($login_fabrica == 52 and $abriuOsNova == 't') {
    					if ($admin_email) {
	                        $mailer->ClearAddresses();
	                        $mailer->AddAddress($admin_email);
	                        //$mailer->Send();
	                    }
	                    if ($email_posto) {
	                        $mailer->ClearAddresses();
	                        $mailer->AddAddress($email_posto);
	                        //$mailer->Send();
	                    }

                    } elseif($login_fabrica <> 52) {

	                    if ($admin_email) {
	                        $mailer->ClearAddresses();
	                        $mailer->AddAddress($admin_email);
	                        //$mailer->Send();
	                    }
	                    if ($email_posto) {
	                        $mailer->ClearAddresses();
	                        $mailer->AddAddress($email_posto);
	                        //$mailer->Send();
	                    }

                    }

				}

				$insereComunicado = 't';
				if ($login_fabrica == 52 and $abriuOsNova != 't') {
					$insereComunicado = 'f';
				}

				if ($login_fabrica == 96) $admin_email = "null";
				$peca                       = "null";
				$produto                    = "null";
				$aux_familia                = "null";
				$aux_linha                  = "null";
				$aux_extensao               = "null";
				$aux_descricao              = substr($subject, 0, 79);
				$aux_mensagem               = $message;
				$aux_tipo                   = "Comunicado";
				$posto                      = $xcodigo_posto;
				$aux_obrigatorio_os_produto = "'f'";
				$aux_obrigatorio_site       = "'t'";
				$aux_tipo_posto             = "null";
				$aux_ativo                  = "'t'";
				$aux_estado                 = "null";
				$aux_pais                   = "'BR'";
				$remetente_email            = "$admin_email";
				$pedido_faturado            = "'f'";
				$pedido_em_garantia         = "'f'";
				$digita_os                  = "'f'";
				$reembolso_peca_estoque     = "'f'";

				if (empty($posto)) {
					$posto = 'null';
				}

				if (empty($msg_erro) and $insereComunicado == 't') {
						# code...
					$sql = "INSERT INTO tbl_comunicado (
								peca                   ,
								produto                ,
								familia                ,
								linha                  ,
								extensao               ,
								descricao              ,
								mensagem               ,
								tipo                   ,
								fabrica                ,
								obrigatorio_os_produto ,
								obrigatorio_site       ,
								posto                  ,
								tipo_posto             ,
								ativo                  ,
								estado                 ,
								pais                   ,
								remetente_email        ,
								pedido_faturado        ,
								pedido_em_garantia     ,
								digita_os              ,
								reembolso_peca_estoque
							) VALUES (
								$peca                       ,
								$produto                    ,
								$aux_familia                ,
								$aux_linha                  ,
								$aux_extensao               ,
								'$aux_descricao'            ,
								'$aux_mensagem'             ,
								'$aux_tipo'                 ,
								$login_fabrica              ,
								$aux_obrigatorio_os_produto ,
								$aux_obrigatorio_site       ,
								$posto                      ,
								$aux_tipo_posto             ,
								$aux_ativo                  ,
								$aux_estado                 ,
								$aux_pais                   ,
								'$remetente_email'          ,
								$pedido_faturado            ,
								$pedido_em_garantia         ,
								$digita_os                  ,
								$reembolso_peca_estoque
							);";

					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					//echo nl2br($sql);exit;
					// echo pg_errormessage($con);exit;
				}
				// exit;
			}//IF QUE VERIFICA SE TEM ERROS - INSERÇÃO DA OS

		}//IF QUE VERIFICA SE TEM ERROS - VALIDAÇÃO

	}

    if (empty($msg_erro)) {
        $enviou = false;
        $enviou_email = '';
        $enviou_sms = '';

        if (!empty($_POST['enviar_por_email'])) {
            $sql = "SELECT nome, email from tbl_hd_chamado_extra where hd_chamado = $callcenter";
            $qry = pg_query($con, $sql);

            if (pg_num_rows($qry) == 0) {
                $warning = 'Não foi possível enviar interação por e-mail - e-mail do consumidor não cadastrado.';
            } else {
                $consumidor_nome = pg_fetch_result($qry, 0, 'nome');
                $consumidor_email = pg_fetch_result($qry, 0, 'email');
                $headers = 'From: Telecontrol <noreply@telecontrol.com.br>';
                $msg = 'Prezado ' . $consumidor_nome . ",\n\n" . $_POST['resposta'];

                $nome_fab = '';
                if ($login_fabrica == '80') {
                    $nome_fab = 'Amvox';
                }

                if (mail($consumidor_email, utf8_encode('Protocolo de Atendimento ' . $nome_fab . ' '. $callcenter), utf8_encode($msg), $headers)) {
                    $enviou_email = 'e-mail ';
                    $enviou = true;
                }
            }
        }

        if (!empty($_POST['enviar_por_sms'])) {
            $sql = "SELECT celular from tbl_hd_chamado_extra where hd_chamado = $callcenter";
            $qry = pg_query($con, $sql);

            if (pg_num_rows($qry) == 0) {
                $warning = 'Não foi possível enviar interação por SMS - celular do consumidor não cadastrado.';
            } else {
                $consumidor_celular = pg_fetch_result($qry, 0, 'celular');

                require '../class/sms/sms.class.php';
                $sms = new SMS();

                $nome_fab = '';
                if ($login_fabrica == '80') {
                    $nome_fab = 'Amvox';
                }

                $sms_msg = utf8_encode('Protocolo de Atendimento ' . $nome_fab . ' ' .  $callcenter . '. ' . $_POST['resposta']);

                if ($sms->enviarMensagem($consumidor_celular, $sua_os, '', $login_fabrica, $con, $sms_msg)) {
                    $enviou_sms = (empty($enviou_email)) ? 'SMS ' : 'e SMS ';
                    $enviou = true;
                }
            }
        }

        if (true === $enviou) {
            $interacao = 'Foi enviado ' . $enviou_email . $enviou_sms . 'para o consumidor';
            $ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($callcenter, '$interacao', $login_admin, 't')";
            $qry = pg_query($con, $ins);
        }
    }

	//Se veio conteúdo na variável $callcenter é atualização

	if (strlen($callcenter)) {

		if (strlen($msg_erro) == 0) {

            //$res = pg_query($con,"ROLLBACK TRANSACTION");
			$msg_erro .= pg_errormessage($con);

            $res = pg_query($con,"COMMIT TRANSACTION");



			header ("Location: $PHP_SELF?callcenter=$hd_chamado");
			exit;

		} else {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}

	} else { //Se não veio conteúdo na variável $callcenter é inserção

		if (strlen($msg_erro) == 0) {

			$res = pg_query($con,"COMMIT TRANSACTION");

			#94971
			if ($login_fabrica == 59 AND ($finaliza == $linhas)) {
				header ("Location: $PHP_SELF?indicacao_posto=$indicacao_posto&callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;
			} else if($login_fabrica <> 59) {
				header ("Location: $PHP_SELF?indicacao_posto=$indicacao_posto&callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;
			}

		} else {
			$res = pg_query($con, "ROLLBACK TRANSACTION");
		}

	}



}//IF DO BTN_ACAO

function saudacao() {
	$hora = date("H");
	echo ($hora >= 7 and $hora <= 11) ? "bom dia" : (($hora>=18) ? "boa noite" : "boa tarde");
}

$callcenter  = $_GET['callcenter'];
$imprimir_os = trim($_GET['imprimir_os']);


if (strlen($callcenter) > 0) {
	if (in_array($login_fabrica, array(96)))
		$produto_referencia_s = "tbl_produto.referencia_fabrica as produto_referencia,";
	else
		$produto_referencia_s = "tbl_produto.referencia as produto_referencia,";

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					tbl_hd_chamado.admin as usuario_abriu,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado.protocolo_cliente,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_hd_chamado_extra.qtde_km,
                    tbl_hd_chamado_extra.contato_nome,
                    tbl_hd_chamado_extra.posto_nome as ponto_referencia,
                    tbl_hd_chamado_extra.garantia as garantia_produto,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_hd_chamado.admin AS admin_abriu,
					tbl_admin.login as atendente,
					tbl_hd_chamado.data::date as data_hd_chamado,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_posto_fabrica.contato_email as posto_email,
					tbl_posto.fone as posto_fone,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado.categoria,
					tbl_produto.produto,
					tbl_produto.linha,
					$produto_referencia_s
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.marca,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') as data_ag,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_hd_chamado_extra.revenda_cnpj,
					tbl_hd_chamado_extra.ordem_montagem,
					tbl_hd_chamado_extra.valor_nf,
					tbl_hd_chamado_extra.tipo_postagem,
					tbl_hd_chamado_extra.codigo_postagem,
					tbl_hd_chamado_extra.hora_ligacao,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os as sua_os,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_extra.numero_processo,
					tbl_hd_chamado_extra.tipo_registro  ,
					tbl_hd_chamado_extra.atendimento_callcenter  ,
					tbl_hd_chamado_extra.familia ,
					tbl_hd_chamado_extra.hd_motivo_ligacao ,
					tbl_hd_chamado_extra.hd_situacao ,
					tbl_admin.login            AS admin_login ,
					tbl_admin.nome_completo    AS admin_nome_completo,
					tbl_cliente_admin.nome     as nome_cliente_admin,
					tbl_cliente_admin.cliente_admin,
					tbl_hd_chamado_extra.array_campos_adicionais
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_admin  on tbl_hd_chamado.admin = tbl_admin.admin
		LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";
//		echo $sql;
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {

			$callcenter               = pg_fetch_result($res,0,'callcenter');
			$usuario_abriu			  = pg_fetch_result($res,0,'usuario_abriu');
			$abertura_callcenter      = pg_fetch_result($res,0,'abertura_callcenter');
			$data_abertura_callcenter = pg_fetch_result($res,0,'data');
			$data_hd_chamado		  = pg_fetch_result($res,0,'data_hd_chamado');
			$natureza_chamado         = pg_fetch_result($res,0,'natureza_operacao');
			$consumidor_nome          = pg_fetch_result($res,0,'nome');
			$cliente                  = pg_fetch_result($res,0,'cliente');
			$consumidor_cpf           = pg_fetch_result($res,0,'cpf');
			$consumidor_rg            = pg_fetch_result($res,0,'rg');
			$consumidor_email         = pg_fetch_result($res,0,'email');
			$consumidor_fone          = pg_fetch_result($res,0,'fone');
			$consumidor_fone2         = pg_fetch_result($res,0,'fone2');
			$consumidor_fone3         = pg_fetch_result($res,0,'celular');
			$consumidor_cep           = pg_fetch_result($res,0,'cep');
			$consumidor_endereco      = pg_fetch_result($res,0,'endereco');
			$consumidor_numero        = pg_fetch_result($res,0,'numero');
			$consumidor_complemento   = pg_fetch_result($res,0,'complemento');
			$ponto_referencia   	  = pg_fetch_result($res,0,'ponto_referencia');
			$consumidor_bairro        = pg_fetch_result($res,0,'bairro');
			$consumidor_cidade        = pg_fetch_result($res,0,'cidade_nome');
			$consumidor_estado        = pg_fetch_result($res,0,'estado');
			$consumidor_revenda       = pg_fetch_result($res,0,'consumidor_revenda');
			$origem                   = pg_fetch_result($res,0,'origem');
			$assunto                  = pg_fetch_result($res,0,'assunto');
			$sua_os                   = pg_fetch_result($res,0,'sua_os');
			$os                       = pg_fetch_result($res,0,'os');
			$data_abertura            = pg_fetch_result($res,0,'data_abertura');
			$produto                  = pg_fetch_result($res,0,'produto');
			$produto_referencia       = pg_fetch_result($res,0,'produto_referencia');
			$produto_nome             = pg_fetch_result($res,0,'produto_nome');
			$voltagem                 = pg_fetch_result($res,0,'voltagem');
			$serie                    = pg_fetch_result($res,0,'serie');
			$data_nf                  = pg_fetch_result($res,0,'data_nf');
			$marca                    = pg_fetch_result($res,0,'marca');
			$data_nf_anterior         = $data_nf;
			$data_ag                  = pg_fetch_result($res,0,'data_ag');
			$nota_fiscal              = pg_fetch_result($res,0,'nota_fiscal');
			$revenda                  = pg_fetch_result($res,0,'revenda');
			$revenda_nome             = pg_fetch_result($res,0,'revenda_nome');
			$revenda_cnpj             = pg_fetch_result($res,0,'revenda_cnpj');
			$ordem_montagem 	  = pg_fetch_result($res,0,'ordem_montagem');
			$valor_nf		  = number_format(pg_fetch_result($res,0,'valor_nf'),2,',','.');
			$tipo_postagem	 	  = pg_fetch_result($res,0,'tipo_postagem');
			$codigo_postagem	  = pg_fetch_result($res,0,'codigo_postagem');
			$posto                    = pg_fetch_result($res,0,'posto');
			$posto_nome               = pg_fetch_result($res,0,'posto_nome');
			$defeito_reclamado        = pg_fetch_result($res,0,'defeito_reclamado');
			$reclamado                = pg_fetch_result($res,0,'reclamado');
			$status_interacao         = pg_fetch_result($res,0,'status');
			$atendente                = pg_fetch_result($res,0,'atendente');
			$receber_informacoes	  = pg_fetch_result($res,0,'receber_info_fabrica');
			$codigo_posto	          = pg_fetch_result($res,0,'codigo_posto');
			$linha         	          = pg_fetch_result($res,0,'linha');
			$abre_os                  = pg_fetch_result($res,0,'abre_os');
			$leitura_pendente         = pg_fetch_result($res,0,'leitura_pendente');
			$atendente_pendente       = pg_fetch_result($res,0,'atendente_pendente');
			$categoria                = pg_fetch_result($res,0,'categoria');
			$hd_extra_defeito         = pg_fetch_result($res,0,'hd_extra_defeito');
			$numero_processo          = pg_fetch_result($res,0,'numero_processo');
			$tipo_registro            = pg_fetch_result($res,0,'tipo_registro');
			$admin_abriu              = pg_fetch_result($res,0,'admin_abriu');
			$familia                  = pg_fetch_result($res,0,'familia');
			$admin_login              = pg_fetch_result($res,0,'admin_login');
			$admin_nome_completo      = pg_fetch_result($res,0,'admin_nome_completo');
			$cliente_admin            = pg_fetch_result($res,0,'cliente_admin');
			$cliente_nome_admin       = pg_fetch_result($res,0,'nome_cliente_admin');
			$posto_km_tab             = pg_fetch_result($res,0,'qtde_km');
			$posto_email_tab          = pg_fetch_result($res,0,'posto_email');
			$posto_fone_tab           = pg_fetch_result($res,0,'posto_fone');
			$cnpj_revenda             = pg_fetch_result($res,0,'revenda_cnpj');
			$atendimento_callcenter   = pg_fetch_result($res,0,'atendimento_callcenter');
			$hora_ligacao             = pg_fetch_result($res,0,'hora_ligacao');
			$protocolo_cliente	  	  = pg_fetch_result($res,0,'protocolo_cliente');
			$contato_nome   	  	  = pg_fetch_result($res,0,'contato_nome');
			$hd_motivo_ligacao   	  = pg_fetch_result($res,0,'hd_motivo_ligacao');
			$situacao_interacao   	  = pg_fetch_result($res,0,'hd_situacao');
			$garantia_produto  	  	  = pg_fetch_result($res,0,'garantia_produto');
			$array_campos_adicionais  = pg_fetch_result($res,0,'array_campos_adicionais');

			if (strlen($revenda) > 0)  {
				$sql_revenda = "SELECT nome, cnpj FROM tbl_revenda WHERE revenda = $revenda";
				$res_revenda = pg_query($con, $sql_revenda);

				if (pg_num_rows($res) > 0) {
					$revenda_nome = pg_fetch_result($res_revenda, 0, "nome");
					$revenda_cnpj = pg_fetch_result($res_revenda, 0, "cnpj");
				}
			}

			if (in_array($login_fabrica,array(11,15,24,74,85)) or $login_fabrica >= 138) {
				if($login_fabrica == 74){
					$array_campos_adicionais = json_decode($array_campos_adicionais);

					$dt_fabricacao 			= $array_campos_adicionais->data_fabricacao;
					$data_visita_tecnico 	= $array_campos_adicionais->data_visita_tecnico;
					$fone_revenda 			= $array_campos_adicionais->fone_revenda;
					$fone_revenda2 			= $array_campos_adicionais->fone_revenda2;
				}else if($login_fabrica <= 138){
					$array_campos_adicionais = json_decode($array_campos_adicionais, true);
					extract($array_campos_adicionais, EXTR_OVERWRITE);

					if (strlen($ligacao_agendada) > 0) {
						list($dmsa, $dmsm, $dmsd) = explode("-", $ligacao_agendada);
						$ligacao_agendada = "{$dmsd}/{$dmsm}/{$dmsa}";
					}
					if ((strlen($data_retorno) > 0) AND ($login_fabrica == 15)) {
						list($dmsa, $dmsm, $dmsd) = explode("-", $data_retorno);
						$xdata_retorno = "{$dmsd}/{$dmsm}/{$dmsa}";
					}

				}else if($login_fabrica >= 138){
					$array_campos_adicionais = json_decode($array_campos_adicionais);
					$voltagem 			= $array_campos_adicionais->voltagem;
				}

			} else {
				if (!$_POST) {
					$array_campos_adicionais = json_decode($array_campos_adicionais, true);

					if ($login_fabrica == 81) {
						$array_campos_adicionais["descricao_analise"] = utf8_decode($array_campos_adicionais["descricao_analise"]);
					}

					if (is_array($array_campos_adicionais) && count($array_campos_adicionais) > 0) {
						foreach($array_campos_adicionais as $key => $value){
							$$key = $value;
						}
					}
				}
			}
			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if ($os) {
				$abre_ordem_servico = 't';
			} else {
				$abre_ordem_servico = 'f';
			}

			if (is_numeric($consumidor_cpf)) {
			    $mask = (strlen($consumidor_cpf) == 14) ? '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/':'/(\d{3})(\d{3})(\d{3})(\d{2})/';
			    $fmt  = (strlen($consumidor_cpf) == 14) ? '$1.$2.$3/$4-$5':'$1.$2.$3-$4';
				$consumidor_cpf = preg_replace($mask, $fmt, $consumidor_cpf);
			}

			if ($login_fabrica == 51 and $leitura_pendente == "t"){
				if ($atendente_pendente == $login_admin){
					$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = null
							WHERE hd_chamado = $callcenter	";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
			if (strlen($codigo_posto) > 0 && empty($_POST['codigo_posto_tab']) ) {
				$procon_codigo_posto = $codigo_posto;
				$procon_posto_nome   = $posto_nome;
				$codigo_posto_tab    = $codigo_posto;
				$posto_nome_tab      = $posto_nome;
			}

			$sql ="SELECT	tbl_hd_chamado_troca.valor_corrigido   ,
							tbl_hd_chamado_troca.hd_chamado        ,
							to_char(tbl_hd_chamado_troca.data_pagamento,'DD/MM/YYYY') as data_pagamento,
							tbl_hd_chamado_troca.ressarcimento     ,
							tbl_hd_chamado_troca.numero_objeto     ,
							tbl_hd_chamado_troca.nota_fiscal_saida ,
							TO_CHAR(tbl_hd_chamado_troca.data_nf_saida,'DD/MM/YYYY')        AS data_nf_saida,
							TO_CHAR(tbl_hd_chamado_troca.data_retorno_produto,'DD/MM/YYYY') AS data_retorno_produto,
							tbl_hd_chamado_troca.valor_produto     ,
							tbl_hd_chamado_troca.valor_inpc        ,
							tbl_hd_chamado_troca.valor_corrigido   ,
							tbl_produto.referencia                 AS troca_produto_referencia,
							tbl_produto.referencia                 AS troca_produto_descricao
				FROM tbl_hd_chamado_troca
				LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_troca.produto
				WHERE tbl_hd_chamado_troca.hd_chamado = $callcenter";

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {

				$valor_corrigido          = pg_fetch_result($res, 0, 'valor_corrigido');
				$hd_chamado               = pg_fetch_result($res, 0, 'hd_chamado');
				$data_pagamento           = pg_fetch_result($res, 0, 'data_pagamento');
				$ressarcimento            = pg_fetch_result($res, 0, 'ressarcimento');
				$numero_objeto            = pg_fetch_result($res, 0, 'numero_objeto');
				$nota_fiscal_saida        = pg_fetch_result($res, 0, 'nota_fiscal_saida');
				$nota_fiscal_saida        = pg_fetch_result($res, 0, 'nota_fiscal_saida');
				$data_nf_saida            = pg_fetch_result($res, 0, 'data_nf_saida');
				$data_retorno_produto     = pg_fetch_result($res, 0, 'data_retorno_produto');
				$valor_produto            = pg_fetch_result($res, 0, 'valor_produto');
				$valor_inpc               = pg_fetch_result($res, 0, 'valor_inpc');
				$valor_corrigido          = pg_fetch_result($res, 0, 'valor_corrigido');
				$troca_produto_referencia = pg_fetch_result($res, 0, 'troca_produto_referencia');
				$troca_produto_descricao  = pg_fetch_result($res, 0, 'troca_produto_descricao');

			}

			if($login_fabrica == 52){

				$sqlXX = "SELECT os FROM tbl_os JOIN tbl_os_extra USING(os) where tbl_os.hd_chamado = $callcenter and tbl_os.fabrica = $login_fabrica";
				$resXX = pg_query($con,$sqlXX);
				if(pg_num_rows($resXX) > 0 ){
					$os_fricon = pg_fetch_result($resXX, 0, 'os');

					$sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = '$os_fricon'";
					$res = pg_query($con, $sql);
					if(pg_num_rows($res) > 0){
						$campos_adicionais2 = pg_fetch_result($res, 0, 'campos_adicionais');
						$dados = json_decode($campos_adicionais2);
						$operadora_celular = $dados->operadora;
					}else{
						$operadora_celular = "";
					}
				}
			}

			/* HD 37805 - Adicionei 59 - Arrumei esta parte de baixo*/
			if ($login_fabrica == 59) {

				$tipo_atendimento = array(	1 => 'reclamacao_produto',
											2 => 'reclamacao_empresa',
											3 => 'reclamacao_at',
											4 => 'duvida_produto',
											5 => 'sugestao',
											6 => 'onde_comprar',
											7 => 'ressarcimento',
											8 => 'sedex_reverso',);

			} else if ($login_fabrica == 2) {

				if ($natureza_chamado == 'aquisicao_mat' or $natureza_chamado == 'aquisicao_prod' or $natureza_chamado == 'indicacao_posto' or $natureza_chamado == 'solicitacao_manual') {

				$natureza_chamado2 = $natureza_chamado;
				$natureza_chamado = "reclamacao_produto";

				}

				if ($natureza_chamado == 'reclamacao_revenda' or $natureza_chamado == 'reclamacao_at' or $natureza_chamado == 'reclamacao_enderecos') {
					$natureza_chamado2 = $natureza_chamado;
					$natureza_chamado = "reclamacoes";
				}

				$tipo_atendimento = array(	1 => 'reclamacao_produto',
											2 => 'reclamacoes',
											3 => 'duvida_produto',
											4 => 'sugestao',
											5 => 'procon' ,
											6 => 'onde_comprar');
			} else if ($login_fabrica == 11) {

				$sub_tipo_reclamacao = array("mau_atendimento","posto_nao_contribui","demonstra_desorg","possui_bom_atend","demonstra_org","reclamacao_at_info");
				if (in_array($natureza_chamado, $sub_tipo_reclamacao) or $natureza_chamado == 'reclamacao_at') {
					$natureza_chamado2 = $natureza_chamado;
					$natureza_chamado = "reclamacao_at";
				}

				$sub_reclamacao_procon = array("pr_reclamacao_at","pr_info_at","pr_mau_atend","pr_posto_n_contrib","pr_demonstra_desorg","pr_bom_atend","pr_demonstra_org");

				if ($natureza_chamado == 'procon' or in_array($natureza_chamado, $sub_reclamacao_procon)) {
					$natureza_chamado2 = $natureza_chamado;
					$natureza_chamado  = "procon";
				}

				$tipo_atendimento = array(
						1 => 'reclamacao_produto',
						2 => 'reclamacao_empresa',
						3 => 'reclamacao_at',
						4 => 'duvida_produto',
						5 => 'sugestao',
						6 => 'procon' ,
						7 => 'onde_comprar');

			} else if ($login_fabrica == 1) {

				$hd_posto = array("digitacao_fechamento_de_os","utilizacao_do_site","falha_no_site","pendencias_de_pecas","pedido_de_pecas","duvida_tecnica_sobre_produto","outros");
				if (in_array($natureza_chamado, $hd_posto)) {
					$natureza_chamado = "hd_posto";
				}

				$tipo_atendimento = array(	1 => 'extensao',
											2 => 'reclamacao_produto',
											3 => 'reclamacao_empresa',
											4 => 'reclamacao_at',
											5 => 'duvida_produto',
											6 => 'sugestao',
											7 => 'assistencia',
											8 => 'garantia',
											9 => 'troca_produto',
											10 => 'procon' ,
											11 => 'onde_comprar',
											12 => 'hd_posto');
			} else if($login_fabrica == 94){
				$tipo_atendimento = array(	1 => 'reclamacao_produto',
											2 => 'reclamacao_empresa',
											3 => 'reclamacao_at',
											4 => 'duvida_produto',
											5 => 'sugestao',
											6 => 'procon',
											7 => 'onde_comprar',
											8 => 'indicacao_rev',
											9 => 'indicacao_at'
					);
			}else {

				$tipo_atendimento = array(	1 => 'extensao',
											2 => 'reclamacao_produto',
											3 => 'reclamacao_empresa',
											4 => 'reclamacao_at',
											5 => 'duvida_produto',
											6 => 'sugestao',
											7 => 'assistencia',
											8 => 'garantia',
											9 => 'troca_produto',
											10 => 'procon' ,
											11 => 'onde_comprar',
											12 => 'ressarcimento',
											13 => 'outros_assuntos');

			}

			$posicao = array_search($natureza_chamado, $tipo_atendimento); // $key = 2;

			if ($imprimir_os == 't' AND strlen ($os) > 0 ) {

				echo "<script language='javascript'>";
					echo "window.open ('os_print.php?os=$os&qtde_etiquetas=$qtde_etiquetas','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
				echo "</script>";

			}

		}

		if ($assunto == 'Indicação de Posto' and ($login_fabrica == 5 or $login_fabrica == 24)) {
			$indicacao_posto = 't';
		}

	}


$Id = $_GET['Id'];

if (strlen($Id) > 0) {

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.ordem_montagem,
					tbl_hd_chamado_extra.codigo_postagem,
					tbl_hd_chamado_extra.revenda ,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_hd_chamado_extra.revenda_cnpj,
					tbl_hd_chamado_extra.hora_ligacao ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.marca,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') as data_ag,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_extra.tipo_registro ,
					tbl_admin.login            AS admin_login ,
					tbl_admin.nome_completo    AS admin_nome_completo,
					tbl_cliente_admin.nome     as nome_cliente_admin,
					tbl_cliente_admin.cliente_admin,
					tbl_hd_chamado_extra.os
		FROM      tbl_hd_chamado
		JOIN      tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		WHERE tbl_hd_chamado.hd_chamado = $Id";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$consumidor_nome        = pg_fetch_result($res, 0, 'nome');
		$cliente                = pg_fetch_result($res, 0, 'cliente');
		$consumidor_cpf         = pg_fetch_result($res, 0, 'cpf');
		$consumidor_rg          = pg_fetch_result($res, 0, 'rg');
		$consumidor_email       = pg_fetch_result($res, 0, 'email');
		$consumidor_fone        = pg_fetch_result($res, 0, 'fone');
		$consumidor_fone2       = pg_fetch_result($res, 0, 'fone2');
		$consumidor_fone3       = pg_fetch_result($res, 0, 'celular');
		$consumidor_cep         = pg_fetch_result($res, 0, 'cep');
		$consumidor_endereco    = pg_fetch_result($res, 0, 'endereco');
		$consumidor_numero      = pg_fetch_result($res, 0, 'numero');
		$consumidor_complemento = pg_fetch_result($res, 0, 'complemento');
		$consumidor_bairro      = pg_fetch_result($res, 0, 'bairro');
		$consumidor_cidade      = pg_fetch_result($res, 0, 'cidade_nome');
		$consumidor_estado      = pg_fetch_result($res, 0, 'estado');
		$produto                = pg_fetch_result($res, 0, 'produto');
		$produto_referencia     = pg_fetch_result($res, 0, 'produto_referencia');
		$produto_nome           = pg_fetch_result($res, 0, 'produto_nome');
		$voltagem               = pg_fetch_result($res, 0, 'voltagem');
		$serie                  = pg_fetch_result($res, 0, 'serie');
		$data_nf                = pg_fetch_result($res, 0, 'data_nf');
		$marca                  = pg_fetch_result($res, 0, 'marca');
		$data_ag                = pg_fetch_result($res, 0, 'data_ag');
		$nota_fiscal            = pg_fetch_result($res, 0, 'nota_fiscal');
		$revenda                = pg_fetch_result($res, 0, 'revenda');
		$nome_revenda           = pg_fetch_result($res, 0, "revenda_nome");
		$cnpj_revenda           = pg_fetch_result($res, 0, "revenda_cnpj");
		$abre_os                = pg_fetch_result($res, 0, 'abre_os');
		$leitura_pendente       = pg_fetch_result($res, 0, 'leitura_pendente');
		$atendente_pendente     = pg_fetch_result($res, 0, 'atendente_pendente');
		$hd_extra_defeito       = pg_fetch_result($res, 0, 'hd_extra_defeito');
		$tipo_registro          = pg_fetch_result($res, 0, 'tipo_registro');
		$admin_login            = pg_fetch_result($res, 0, 'admin_login');
		$admin_nome_completo    = pg_fetch_result($res, 0, 'admin_nome_completo');
		$cliente_admin          = pg_fetch_result($res, 0, 'cliente_admin');
		$cliente_nome_admin     = pg_fetch_result($res, 0, 'nome_cliente_admin');
		$hora_ligacao           = pg_fetch_result($res, 0, 'hora_ligacao');



		if (is_numeric($consumidor_cpf)) {

		    $mask = (strlen($consumidor_cpf) == 14) ? '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/':'/(\d{3})(\d{3})(\d{3})(\d{2})/';
		    $fmt  = (strlen($consumidor_cpf) == 14) ? '$1.$2.$3/$4-$5':'$1.$2.$3-$4';

			$consumidor_cpf = preg_replace($mask, $fmt, $consumidor_cpf);

		}

		if ($login_fabrica == 51 and $leitura_pendente == "t") {

			if ($atendente_pendente == $login_admin) {

				$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = null WHERE hd_chamado = $Id";
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);

			}

		}

		//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
		$abre_ordem_servico = pg_result($res, 0, 'os');
		if ($abre_ordem_servico) {
			$abre_ordem_servico = 't';
		} else {
			$abre_ordem_servico = 'f';
		}


	}

}

if ($login_fabrica == 74 and !empty($callcenter)) {
	$sql_status_interacao = "SELECT status
							 FROM tbl_hd_chamado
							 WHERE hd_chamado = {$callcenter}
							 AND fabrica = {$login_fabrica}";
	$res_status_interacao = pg_query($con, $sql_status_interacao);

	$hd_chamado_status = pg_fetch_result($res_status_interacao, 0, "status");
}

if ($login_fabrica == 24) {//HD 235203: Colocar vários assuntos para a Suggar

	if (strlen($tab_atual) == 0) {

		foreach ($assuntos as $categoria_assunto => $itens_categoria_assunto) {

			foreach ($itens_categoria_assunto as $label_assunto => $bd_assunto) {

				if ($bd_assunto == $categoria) {

					$callcenter_assunto = $categoria;

					switch ($categoria_assunto) {

						case "PRODUTOS":
							$natureza_chamado = "reclamacao_produto";
						break;

						case "MANUAL":
							$natureza_chamado = "reclamacao_produto";
						break;

						case "EMPRESA":
							$natureza_chamado = "reclamacao_empresa";
						break;

						case "ASSISTÊNCIA TÉCNICA":
							$natureza_chamado = "reclamacao_at";
						break;

						case "REVENDA":
							$natureza_chamado = "onde_comprar";
						break;

					}

				}

			}

		}

	} else {

		$natureza_chamado = $tab_atual;

	}

	$tipo_atendimento = array(	1 => 'extensao',
								2 => 'reclamacao_produto',
								3 => 'reclamacao_empresa',
								4 => 'reclamacao_at',
								5 => 'duvida_produto',
								6 => 'sugestao',
								7 => 'assistencia',
								8 => 'garantia',
								9 => 'troca_produto',
								10 => 'procon' ,
								11 => 'onde_comprar',
								12 => 'ressarcimento',
								13 => 'outros_assuntos');

	$posicao = array_search($natureza_chamado, $tipo_atendimento); // $key = 2;

	if (!$posicao) {
		$posicao = 2;
	}

}

if (strlen($_GET['Id']) > 0) {//HD 94971
	$id_x = $_GET['Id'];
} else {
	$id_x = "";
}

if (strlen($callcenter) > 0 OR strlen($id_x) > 0) {
	require_once '../helpdesk.inc.php';
}

include "cabecalho.php";

?>

<style>

.input {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff
}

.respondido {
	font-size: 10px;
	color: #4D4D4D;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.inicio{
	border:#485989 1px solid;
	background-color: #e6eef7;
	font-size:10px;
	font-family:verdana;
	text-align:center;
	margin: 0 auto;
	width:200px;
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;
}

.tab_content{
	border:#485989 1px solid;
	font-size:10px;
	font-family:verdana;
	margin: 0 auto;
	float:center;
/*	width:680px;*/
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;
}

.padding {
	padding-left: 150px;
}

.input_req {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.input_req2 {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.box {
	border-width: 1px;
	border-style: solid;
}
.box {
	display: block;
	margin: 0 auto;
	width: 100%;
}
.azul {
	border-color: #1937D9;
	background-color: #D9E2EF;
}
.label {
	width: 20%;
}
.border{
	text-align:left;
	font-weight:bold;
}
.dados {
	text-align:left;
}
body {
	text-align: left!important;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
}

table.tabela tr{
    background-color: #FFFFFF;
}

table.tabela tr:nth-child(2n+1) {
  background-color: #E4E9FF;
}


.titulo_tabela{
    background-color:#596d9b !important;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.hd_motivo_ligacao{
	display: none;
}
</style>

<!--=============== <FUNES> ================================!-->
<script src="js/jquery-1.8.3.min.js"></script>
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='js/bibliotecaAJAX.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script type='text/javascript' src='../inc_soMAYSsemAcento.js'></script>
<script type='text/javascript' src='js/assist.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />

<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<script type="text/javascript" src="js/jquery.maskMoney.min.js"></script>

<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->


<script language="javascript">

<?php if($login_fabrica == 11 or $login_fabrica == 85){ ?>

    $(function(){
        /* Uso do input: <input maxlength="15" name="nome_do_campo" id="id_do_campo" class="telefone" /> */
        $('#telefone, .telefone').each(function(){
          /* Carrega a máscara default do post/get conforme o valor que já vier no value */
          /* Para adicionar mais DDD's  =>  $(this).val().match(/^\(11|21\) 9/i) */

          // if( $(this).val().match(/^1\d/i) ){
          //   $(this).mask('(00) 00000-0000', $(this).val());  // 9º Dígito
          // }else{
          //   $(this).mask('(00) 0000-0000',  $(this).val()); /* Máscara default */
          // }

        	var valor =	$(this).val().length;

        	if(valor <= 10){
        		$(this).mask('(00) 0000-0000',  $(this).val()); /* Máscara default */
        	}else{
        		$(this).mask('(00) 00000-0000', $(this).val());  // 9º Dígito
        	}
       	});

        var phoneMask = function(){
					if($(this).val().match(/^\(0/)){
        		$(this).val('(');
        		return;
        	}

        	if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
        		$(this).mask('(00) 0000-0000');
        		console.debug('telefone');
        	}
        	else{
						$(this).mask('(00) 00000-0000');
						console.debug('celular');
        	}
        	$(this).keyup(phoneMask);
        };

        $('.telefone').keyup(phoneMask);

	        //$('.telefone').keyup(function(){
	        	//if( $(this).val().match(/^\([1-9]/)){
						//  $(this).mask('(00) 00000-0000'); /* 9º Dígito */
						//  if( $(this).val().match(/^\([1-9][0-9]\) *[^9]/)) {
						//  	$(this).mask('(00) 0000-0000');  /* Máscara default */
						//   }
						//}else{
						//  $(this).mask('(00) 0000-0000');  /* Máscara default */
						//}

	        	// if( $(this).val().match(/^\(1\d\) 9/i) ){
	          //     $(this).mask('(00) 00000-0000'); // 9º Dígito
	          // }else{
	          //    $(this).mask('(00) 0000-0000');  // Máscara default
	          // }
	        //});

    });
    /* fim - 9º Dígito para São Paulo-SP, digitando o DDD 11 + 9 deixará entrar o 9º caracter */

<?php } ?>

var login_fabrica = "<?=$login_fabrica?>";

$(function () {
	$("#consumidor_estado").change(function () {
		if ($(this).val().length > 0) {
			$("#consumidor_cidade").removeAttr("readonly");
		} else {
			$("#consumidor_cidade").attr({"readonly": "readonly"});
		}
	});

	if (login_fabrica == 15) {
		$("#data_retorno").blur(function () {
			$("#data_retorno_alterou").val("t");
		});
	}
});

 function verifica_os_aberta () {
 	var hd_chamado = $("input[name=callcenter]").val();

 	if (hd_chamado.length > 0) {
 		$.ajax({
 			url: "verifica_os_atendimento.php",
 			type: "POST",
 			data: { hd_chamado: hd_chamado },
 			complete: function (data) {
 				data = $.parseJSON(data.responseText);

 				if (data.erro) {
 					alert(data.erro);
 				} else if (data.os) {
 					desassociar_os(data.os, hd_chamado);
 				}
 			}
 		});
 	}
 }

 function desassociar_os (os, hd_chamado) {
 	var posto = $("#posto_tab").val();

 	Shadowbox.options.modal      = true;
 	Shadowbox.options.enableKeys = false;

 	Shadowbox.open({
 		content: "desassociar_os.php?os="+os+"&hd_chamado="+hd_chamado+"&posto="+posto,
 		player: "iframe",
 		width: 450,
 		height: 160
 	});

 	$("#sb-nav").css({ display: "none" });
 }

 function resetaPosto (hd_chamado) {
 	$.ajax({
 		url: "desassocia_os_ajax.php",
 		type: "POST",
 		data: { resetaPosto: true, hd_chamado: hd_chamado },
 		complete: function (data) {
 			data = $.parseJSON(data.responseText);

 			$('#codigo_posto_tab').val(data.codigo);
 			$('#posto_tab').val("");
 			$('#posto_atual').val(data.posto);
 			$('#posto_nome_tab').val(data.nome);
 			$('#posto_fone_tab').val(data.fone);
 			$('#posto_email_tab').val(data.email);
 			$("#codigo_posto").val(data.codigo);
 			$("#posto_nome").val(data.nome);

 			Shadowbox.close();
 			Shadowbox.options.modal      = false;
 			Shadowbox.options.enableKeys = true;
 			$("#sb-nav").css({ display: "block" });
 		}
 	});
 }

 function fechaShadowboxDesassocia () {
 	Shadowbox.close();
 	Shadowbox.options.modal      = false;
 	Shadowbox.options.enableKeys = true;
 	$("#sb-nav").css({ display: "block" });
 }

 /* 9º Dígito para São Paulo-SP, digitando o DDD 11 + 9 deixará entrar o 9º caracter */
    /* Exemplos de uso das máscaras: http://igorescobar.github.com/jQuery-Mask-Plugin/ */
    $(function()
    {


        $('.telefone').keypress(function()
        {
            if( $(this).val().match(/[^\d|-|\(|\)]/g) )
	    {
		    this.value = this.value.replace(/[^\d|\-|\(|\)]/g, '');
            }
        });
    });
    /* fim - 9º Dígito para São Paulo-SP, digitando o DDD 11 + 9 deixará entrar o 9º caracter */

function mostraPesquisaFricon(){

	var pesquisa_id   = $("input[name=pesquisa_fricon]:checked").val();
	var hdChamado = $('input[name=callcenter]').val();
	$.ajax({
		url: "<?php echo $_SERVER['PHP_SELF']; ?>?showPesquisa=1&pesquisa="+pesquisa_id+"&hd_chamado="+hdChamado,
		cache: false,
		success: function(data){
			$("#div_pesquisa").html('');
			$("#div_pesquisa").append(data);
			$("#div_pesquisa").show();
			$(".div_btn_gravar").show();
		}
	});

}

function mostraPesquisaFriconAntigo(){
	var rel_input = $("input[name=tipo_pergunta]:checked").attr('rel');
	if ( rel_input == 'Auditoria em Campo'){
		$('#AuditoriaCampo').show();
		$('#PesquisaSatisfacao').hide();
	}else if(rel_input == 'Pesquisa de Satisfação'){
		$('#AuditoriaCampo').hide();
		$('#PesquisaSatisfacao').show();
	}
}

function informacoesPosto(id, cidade){
	var dados = new Array();
	$.ajax({
		url: 'informacoes_posto.php',
		type: 'post',
		data: 'cod='+id,
		success: function(data){
			if (login_fabrica == 74 && ($("#posto_atual").val() != id)) {
 				verifica_os_aberta();
 			}

			dados = data.split("|");
			$('#codigo_posto_tab').attr('value', dados[4]);
			$('#posto_tab').attr('value', id);
			$('#posto_nome_tab').attr('value', dados[0]);
			$('#posto_fone_tab').attr('value', dados[1]);
			$('#posto_email_tab').attr('value', dados[2]);
			//$('#mapa_cidade').attr('value', cidade);

			$("#codigo_posto").val(dados[4]) ;
 			$("#posto_nome").val(dados[0]) ;
 			<?php
 			if ($login_fabrica == 74) {
 			?>
 				$("#posto_atual").val(id);
 			<?php
 			}
 			?>
		}
	});
}


$().ready(function(){

	$("input, textarea").keyup(function () {
		$(this).val($(this).val().replace(/\'/gi, ""));
	});

	$("input, textarea").blur(function () {
		$(this).val($(this).val().replace(/\'/gi, ""));
	});

	$(".btn_consulta_cidades_atendidas").click(function(){
		var codigo = $('#codigo_posto_tab').val();
		if(codigo != ""){
			var URL = "cidades_atendidas.php?codigo="+ codigo +"&nome="+$('#posto_nome_tab').val();
			window.open(URL,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=200,top=18,left=0" );
		}else{
			alert("Para consultar as cidades atendidas, informe um posto, preenchendo o campo código.");
		}

	});



	if ($('input[name=tipo_pergunta]').is(':checked')){
		mostraPesquisaFriconAntigo()
	}

	if ($('input[name=pesquisa_fricon]').is(':checked')){
		mostraPesquisaFricon()
	}

	$("input[name=pesquisa_fricon]").click(function(){
		mostraPesquisaFricon();
	});

	$(document).on("click",'.btn_grava_pesquisa_fricon',function(){

		var curDateTime = new Date();
		var relBtn = $(this).attr('rel');
		var hdChamado = $('input[name=callcenter]').val();
		var dados = '';
		dados = 'ajax=true&gravaPerguntasFricon=true&pesquisa='+relBtn+'&hdChamado='+hdChamado+'&'+$('.table_perguntas_fricon_pesquisa').find('input').serialize()+'&'+$('.table_perguntas_fricon_pesquisa').find('textarea').serialize()

		$.ajax({

			type: "GET",
			url: "<?=$PHP_SELF?>",
			data: dados,
			beforeSend: function(){

				$('input[name=pesquisa_fricon]').attr('disabled',true);
				$('.btn_grava_pesquisa_fricon').hide();
				$('.td_btn_gravar_pergunta').show();
				$('.td_btn_gravar_pergunta').html("&nbsp;&nbsp;Gravando...&nbsp;&nbsp;<br><img src='imagens/loading_bar.gif'> ");

				$('.divTranspBlock').show();


			},
			complete: function(http) {

				results = http.responseText;
				results = results.split('|');
				if (results[0] == 1){

					$('div.errorPergunta').html(results[1]);
					$('div.errorPergunta').show();
					$('.td_btn_gravar_pergunta').hide();
					$('input[name=pesquisa_fricon]').attr('disabled',false);
					$('.divTranspBlock').hide();
					$('.btn_grava_pesquisa_fricon').show();


				}else{
					$('div.errorPergunta').hide();
					$('.divTranspBlock').hide();
					$('input[name=pesquisa_fricon]').attr('disabled',true);
					$('.table_perguntas_fricon_pesquisa').find('input').attr('disabled',true);
					$('.table_perguntas_fricon_pesquisa').find('textarea').attr('disabled',true);
					$('.agradecimentosPesquisa').show();
					$('.td_btn_gravar_pergunta').hide();
				}
			}

		});

		$("input[name=valor_declarado]").maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 15});
	});



});

	function retiraAcentos1(obj) {
		re = /[^a-z^A-Z^0-9\s]/g;		//Expressão regular que localiza tudo que for diferente de caracteres a-Z ou 0-9 ou espaços
		obj.value = obj.value.replace(re, "");
	}

	//HD 201434 - Retirar acentos da digitação do nome do cliente
	function retiraAcentos(obj) {

		com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
		sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';

		resultado = '';

		for (i = 0; i < obj.value.length; i++) {

			if (com_acento.search(obj.value.substr(i,1)) >= 0) {
				resultado += sem_acento.substr(com_acento.search(obj.value.substr(i,1)),1);
			} else {
				resultado += obj.value.substr(i,1);
			}

		}

		obj.value = resultado;

	}

    $(function() {
<?
    if(in_array($login_fabrica,array(85,94))){
?>
            $('#status_interacao').change(function(){
                var valor = $('#status_interacao option:selected').val();
                if(valor == 'Resolvido'){
                    $('td#satisfacao').css("display","table-cell");
                    $('td#satisfacao').css("width","130px");
                }else{
                    $('td#satisfacao').css("display","none");
                }
            });
<?
    }
?>
<?php
if (in_array($login_fabrica,array(52,85,138,145)) && strlen($msg_erro) > 0) {
?>
			var tipo = "<?echo $consumidor_cpf_cnpj;?>";
			if (tipo == 'C') {
				$('#cpf').attr('maxLength', 14);
				$('#cpf').attr('size', 18);
				$('#label_cpf').html('CPF:');
<?
    if($login_fabrica == 85){
?>
                $("#label_nome").html("Nome:");
<?
    }
?>
				$('#cpf').keypress (function(e) {
					return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
				});
			} else {
				if (tipo == 'R') {
					$('#consumidor_cnpj').attr('checked', true);
					$('#cpf').attr('maxLength', 18);
					$('#cpf').attr('size', 23);
					$('#label_cpf').html('CNPJ:');
<?
    if($login_fabrica == 85){
?>
                $("#label_nome").html("Razão Social:");
<?
    }
?>
					$('#cpf').keypress(function(e) {
						return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
					});
				}
			}

<?php
}
?>
});
</script>
<script type="text/javascript">
/* HD 969678 - Simulando click na aba corrente ao carregar a página,
	   na Fricon estava fican abas erradas clicadas por conta do arquivos js e css incluídos. */
$(window).load(function () {
	var categoria = "<?=$categoria?>";
	$("#container-Principal > ul > li").find("a[id="+categoria+"_click]").click();
});

function abrir_anexo(anexo){
	window.open ("callcenter_interativo_anexo.php?anexo="+anexo, "Anexo", "status = yes, scrollbars=yes");
	abrir() ;
}<?php

if ($login_fabrica == 24) :?>

	$().ready(function(){ // HD 751794

		function toggleOrientacao() {

			if ( $("#callcenter_assunto").attr('value') === 'produto_reclamacao' ) {

				$("#orientacao_uso").show();

			} else {

				$("#orientacao_uso").hide();

			}

		}

		toggleOrientacao();

		$("#callcenter_assunto").change(function(){

			toggleOrientacao();

		});

	});<?php

endif;?>

function function1(linha2) {

	var linha = document.getElementById('qtde_produto').value;
	linha = parseInt(linha) + 1;

	if (!document.getElementById('item'+linha)) {

		var tbl = document.getElementById('tabela_itens');
		//var lastRow = tbl.rows.length;
		//var iteration = lastRow;

		//Atualiza a qtde de linhas
		$('#qtde_produto').val(linha);

		/*Criar TR - Linha*/
		var nova_linha = document.createElement('tr');
		nova_linha.setAttribute('rel', linha);

		/********************* COLUNA 1 ****************************/

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Série:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'serie_' +linha);
		el.setAttribute('id', 'serie_' + linha);
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			var serie      = document.getElementById('serie_'+linha);
			var ativo      = document.getElementById('numero_ativo_'+linha);
			fnc_pesquisa_serie(produto,nome,'serie',mapa_linha,serie, linha);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 2 ****************************/
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Referência:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_referencia_' +linha);
		el.setAttribute('id', 'produto_referencia_' + linha);
		el.setAttribute('size','15');
		el.setAttribute('class','input');
		<?php if ($login_fabrica == 52): ?>
			el.onblur = function() {
				var nat = "Reclamado";
				var val = document.getElementById('produto_referencia_'+linha).value;
				var l = linha;
				mostraDefeitos(nat, val, l);
			}
		<?php endif; ?>
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(produto,nome,'referencia',mapa_linha, linha);
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 3 ****************************/
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Descrição:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_nome_' +linha);
		el.setAttribute('id', 'produto_nome_' + linha);
		el.setAttribute('size','20');
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(produto,nome,'descricao',mapa_linha, linha);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Defeito Reclamado  </strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		//var teste_array = '<?	if ($login_fabrica == 52 ) { $sql = "SELECT distinct tbl_defeito_reclamado.descricao, tbl_defeito_reclamado.defeito_reclamado FROM tbl_diagnostico JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia WHERE tbl_diagnostico.fabrica = $login_fabrica AND tbl_diagnostico.ativo is true"; $res1 = pg_query ($con,$sql); if (pg_num_rows($res1) > 0) { for ($x = 0 ; $x < pg_num_rows($res1) ; $x++){$defeito_reclamado = trim(pg_fetch_result($res1,$x,defeito_reclamado)); $descricao  = trim(pg_fetch_result($res1,$x,descricao)); $descricao = substr($descricao,0,30); echo $defeito_reclamado;echo'/';echo $descricao;echo '|'; }	 }	}?>';
		var teste_array = '<?	if ($login_fabrica == 52 ) { $sql = "SELECT defeito_reclamado, descricao FROM tbl_defeito_reclamado WHERE fabrica = $login_fabrica and ativo='t' ORDER BY descricao"; $res1 = pg_query ($con,$sql); if (pg_num_rows($res1) > 0) { for ($x = 0 ; $x < pg_num_rows($res1) ; $x++){$defeito_reclamado = trim(pg_fetch_result($res1,$x,defeito_reclamado)); $descricao  = trim(pg_fetch_result($res1,$x,descricao)); $descricao = substr($descricao,0,30); echo $defeito_reclamado;echo'/';echo $descricao;echo '|'; }	 }	}?>';

		teste_array = teste_array.split('|');
		var qtd = teste_array.length;
		var el = document.createElement("select");
		el.setAttribute('name', 'defeito_reclamado_' + linha);
		el.setAttribute('id', 'defeito_reclamado_' + linha);
		el.setAttribute('class','input');
		elop=document.createElement("OPTION");
		elop.setAttribute('value','');
		texto1=document.createTextNode(" ");
		elop.appendChild(texto1);
		el.appendChild(elop);

		for ($i=0;$i<qtd;$i++) {
			var array = teste_array[$i].split('/');
			var codigo = array[0];
			var nome = array[1];

			if (codigo != '') {
				elop=document.createElement("OPTION");
				elop.setAttribute('value',codigo);
				texto1=document.createTextNode(nome);
				elop.appendChild(texto1);
				el.appendChild(elop);
			}
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 5 ****************************/

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: right;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Nº Controle Cliente:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'controle_cliente_' +linha);
		el.setAttribute('id', 'controle_cliente_' + linha);
		el.setAttribute('class','input');
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/************ FINALIZA LINHA DA TABELA ***********/
		var tbody = document.createElement('TBODY');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);

		/********************* COLUNA 6 ****************************/

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Número do Ativo:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'numero_ativo_' +linha);
		el.setAttribute('id', 'numero_ativo_' + linha);
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome         = document.getElementById('produto_nome_'+linha);
			var produto      = document.getElementById('produto_referencia_'+linha);
			var mapa_linha   = $('#mapa_linha'+linha);
			var serie        = document.getElementById('serie_'+linha);
			var numero_ativo = document.getElementById('numero_ativo_'+linha);
			fnc_pesquisa_ordem(produto,nome,'ordem',mapa_linha,serie,numero_ativo,linha);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);



	}

}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

$(function() {
	// !129655 - Carregar dúvidas já cadastradas para este chamado [augusto]
	// !133157 - Carregar dúvidas quando o usuário não selecionou a aba dúvida, ou seja, a dúvida de produto [augusto]
	<?php if (isset($callcenter) && ! empty($callcenter) && $login_fabrica <> 52 ) : ?>
		if (document.frm_callcenter.produto_referencia) {
			localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_duvida','carregar');
			localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_produto','carregar');
		}
	<?php endif; ?>
});
<?php
if ($login_fabrica == 25 OR $login_fabrica == 59 OR $login_fabrica == 94) {
	$w = 1;
} else if ($login_fabrica == 45) {
	$w = 1;
	$posicao = $posicao-1;
} else if ($login_fabrica == 46) {
	$w = 1;
	$posicao = $posicao-1;
} else if ($login_fabrica == 2 OR $login_fabrica == 11) {
	$w = 1;
	$posicao = $posicao;
} else {
	$w = 1;
	if ($posicao >= 10) $posicao = $posicao-4;
	else                $posicao = $posicao-1;
}
if (!empty($callcenter) and empty($tab_atual)){
	$sql = "SELECT categoria
		FROM tbl_hd_chamado
		WHERE hd_chamado = $callcenter";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0) $tab_atual = pg_fetch_result($res,0,0);
}

if ($tab_atual == "informacao" and $login_fabrica == 81) :
	$posicao = 1;
	$xtab_atual = "reclamacao_produto";
endif;
?>


function solicitaPostagem(hd_chamado) {

	    tipo_postagem = $('#tipo_postagem').val();


	    if(tipo_postagem != '') {
		    Shadowbox.open({
        	        content :   "solicitacao_postagem_correios.php?hd_chamado="+ hd_chamado+"&tipo="+tipo_postagem,
                	player  :   "iframe",
                	title   :   "Autorização de Postagem",
                	width   :   800,
                	height  :   500
            	    });
	    } else {
		alert('Escolha o tipo de postagem');
            }

}


$().ready(function() {
	<?php
	if ($login_fabrica == 81) { ?>
		$("#valor_nf").maskMoney({prefix:'R$ ', allowNegative: false, thousands:'.', decimal:',', affixesStay: false});
	<?php
	}
	?>

	//alert ('Aqui');
	<?php

	if ($login_fabrica == 24) {?>
		$('#container-Principal').tabs( <? echo "$posicao"; ?>);<?php
	} else { ?>
		$('#container-Principal').tabs( 2<? //if(strlen($callcenter)>0){ echo "active: $posicao"; }?> ); <?php
	}


 	if ($login_fabrica == 74){
 		$sql = "
 		SELECT admin
 		FROM tbl_hd_chamado
 		WHERE hd_chamado = $callcenter
 		";
 		$res = pg_query($con,$sql);
 		if(pg_num_rows($res) > 0) $admin = pg_fetch_result($res,0,0);
 		//echo "alert ('$callcenter');";
 		//echo "alert ('$admin');";
 	}
	if ((strlen($callcenter) > 0) AND ($admin <> 6437)) {
		for ($x = $w; $x < 12; $x++) {

			if ($x <> $posicao) {

				if (strlen($protocolo) == 0)  {?>
					$('#container-Principal').disableTab(<?echo $x;?>);<?php
				}

			}

		}

	}?>

	//$('#container').disableTab(3);
	//fxAutoHeight: true,
	$("#consumidor_cpf").mask("999.999.999-99");
	$("#consumidor_cep").mask("99999-999");
	$("#hora_ligacao").mask("99:99");
	$("input[rel='data']").datepick({startDate:'01/01/2000'});
	$("input[rel='data']").mask("99/99/9999");
	$("input[rel='mes_ano']").mask("99/9999");
	$("#data_abertura").mask("99/99/9999");
	$("#expedicao").mask("99/99/9999");
	//$("#localizar_telefone").mask("(99) 9999-9999");
	$("#localizar_cep").mask("99999-999");

	<?
	if(!empty($tab_atual)){
		$xtab_atual = $tab_atual;

		if ($tab_atual == "informacao" and $login_fabrica == 81) :
			$xtab_atual = "reclamacao_produto";
		endif;
	?>
		$("#<?php echo $xtab_atual.'_click'; ?>").click();
	<?}?>
	//$('#cpf').keypress();
	<?if ($login_fabrica == 86) { ?>
	var motivo_atendimento = '<?=$hd_motivo_ligacao?>';
	var consumidor_revenda = '<?=$consumidor_revenda?>';
	if(motivo_atendimento != ''){
		comboMotivo(consumidor_revenda,motivo_atendimento);
	}

	$('#consumidor_revenda').change(function(){
		var tipo = $(this).val();
		comboMotivo(tipo,motivo_atendimento);
	});
	<?}?>

	<?if ($login_fabrica == 74) { ?>
		var status_atual = "<?=$hd_chamado_status?>";

		if(status_atual == "Resolvido" || status_atual == "PROTOCOLO DE INFORMACAO"){
			bloquear_campos();
            if(status_atual == "Resolvido"){
                $("#continuar_chamado").find("input[type=button]").removeAttr("disabled");
            }
		}

        if(status_atual != "Resolvido"){
            $("#continuar_chamado").find("input[type=button]").attr({'disabled':true});
        }

	<?}?>

});

function formatItem(row) {
	return row[1] + " - " + row[2];
}

function formatItemNomeRevenda(row) {
	return row[1] + " - " + row[2] + " - " + row[3];
}

function formatItemPosto(row) {
	return row[2] + " - " + row[3] + " (Fantasia:" + row[4] + ")";
}

function formatCliente(row) {
	return row[2] + " - " + row[3] + " - Cidade: " + row[4];
}

function formatLocalizar(row) {

	var data        = "";
	var nota_fiscal = "";
	var serie       = "";
	var cep         = "";

	if (row[0] == "erro") {
		return "Erro: " + row[1];
	}

	switch(row[24]) {

		case "O":
			sua_os = row[16];
			atendimento = row[22];
		break;

		case "C":
			sua_os = row[16];
			atendimento = row[0];
		break;

		case "R":
			sua_os = 0;
			atendimento = 0;
		break;

		case "A":
			sua_os = 0;
			atendimento = 0;
		break;

		default:
			sua_os = 0;
			atendimento = 0;

	}

	if (atendimento) {
		atendimento = "Chamado: " + atendimento;
	} else {
		atendimento = "<font style='font-size: 7pt; font-weight: bold; background-color: #CC5555; color: #FFFFFF;'>SEM ATENDIMENTO</font>";
	}

	if (sua_os) {
		sua_os = "OS: " + sua_os;
	} else {
		sua_os = "<font style='font-size: 7pt; font-weight: bold; background-color: #CC5555; color: #FFFFFF;'>SEM OS</font>";
	}

	if (row[18]) {
		nota_fiscal = " Nota Fiscal: " + row[18];
	}

	if (row[17]) {
		serie = " Série: " + row[17];
	}

	if (row[6]) {
		cep = " Cep: " + row[6];
	}

	return atendimento + " - Cliente: " + row[1] + " - " + sua_os + nota_fiscal + serie + cep;

}
/**
 * Adiciona o evento do autocomplete para o campo "Cliente Fricon"
 * Esta função existe porque é necessário executar ela depois de algumas requisições AJAX.
 *
 * @param Object reference É a referencia da onde o INPUT do autocomplete deve ser buscado, se nenhum for informado, busca no BODY inteiro
 */
function bindEventClienteNomeAdmin(reference) {
	if ( reference == undefined ) {
		reference = $('BODY').get(0);
	}
	$("#cliente_nome_admin",reference).autocomplete("<?php echo $PHP_SELF.'?tipo_busca=cliente_admin&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		max: 30,
		matchContains: true,
		formatItem: formatCliente,
		formatResult: function(row) {
		return row[3];
		}
	});
}

$().ready(function() {
<?php
if($login_fabrica == 11){
?>

    $('#cpf').autocomplete('pesquisa_consumidor_callcenter_nv?tipo_pesquisa=cpf&ajax=sim',{
        minChars:6,
        delay: 700,
        width: 300,
        max: 30,
        matchContains:true,
        formatItem: formatLocalizar,
        formatResult: function(row){
            return $('#cpf').val();
        }
    });

    $('#cpf').result(function(event,data,formatted){
        preencheConsumidorAutocomplete(event,data,formatted);
        fnc_pesquisa_consumidor_callcenter($('#cpf').val(),'cpf',true);
    });
<?php
}
?>
	/* Busca pelo Cdigo */
	$("#revenda_cnpj").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[1];
		}
	});

	$("#revenda_cnpj").result(function(event, data, formatted) {
		$("#revenda_tab").val(data[0]) ;
		$("#revenda_cnpj").val(data[1]) ;
		$("#revenda_nome").val(data[2]) ;
	});

	/* Busca pelo CNPJ da Revenda no quadro Informações do Produto */
	$("#cnpj_revenda").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda_geral&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[1];
		}
	});

	$("#cnpj_revenda").result(function(event, data, formatted) {
		$("#revenda_tab").val(data[0]) ;
		$("#cnpj_revenda").val(data[1]) ;
		$("#nome_revenda").val(data[2]) ;
		if(data[4] != undefined){
			$("#fone_revenda").val(data[4]) ;
		}
	});

	/* Busca pelo Nome */
	$("#revenda_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#revenda_nome").result(function(event, data, formatted) {
		$("#revenda").val(data[0]) ;
		$("#revenda_cnpj").val(data[1]) ;
		$("#revenda_nome").val(data[2]) ;
		if(data[4] != undefined){
			$("#fone_revenda").val(data[4]) ;
		}
		//alert(data[2]);
	});

	/* Busca pelo Nome da Revenda no quadro Informações do Produto */
	$("#nome_revenda").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda_geral&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemNomeRevenda,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#nome_revenda").result(function(event, data, formatted) {
		$("#cnpj_revenda").val(data[1]) ;
		$("#nome_revenda").val(data[2]) ;
		if(data[4] != undefined){
			$("#fone_revenda").val(data[4]) ;
		}
		//alert(data[2]);
	});

	$("#revenda_nome_os").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda_os&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#revenda_nome_os").result(function(event, data, formatted) {
		$("#revenda_os").val(data[0]) ;
	});


	$("#mapa_cidade").autocomplete("<?echo $PHP_SELF.'?tipo_busca=mapa_cidade&busca=mapa_cidade'; ?>", {
		minChars: 3,
		delay: 150,
		width: 205,
		matchContains: true,
		formatItem: function(row) {
			return row[0];
		},
		formatResult: function(row) {
			return row[0];
		}
	});

	/* Busca pelo Código */
	$("#codigo_posto_tab").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemPosto,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#codigo_posto_tab").result(function(event, data, formatted) {
		if (login_fabrica == 74 && ($("#posto_atual").val() != data[0])) {
 			verifica_os_aberta();
 		}

		$("#posto_tab").val(data[0]) ;
		$("#codigo_posto_tab").val(data[2]) ;
		$("#posto_nome_tab").val(data[3]) ;
		$("#posto_fone_tab").val(data[5]) ;
		$("#posto_email_tab").val(data[6]) ;

		$("#codigo_posto").val(data[2]) ;
		$("#posto_nome").val(data[3]) ;

		<?php
 		if ($login_fabrica == 74) {
 		?>
 			$("#posto_atual").val(data[0]);
 		<?php
 		}
 		?>
	});

	var extraParamEstado = {
		estado: function () {
			return $("#consumidor_estado").val()
		}
	};

	$("#consumidor_cidade").autocomplete("autocomplete_cidade_new.php", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		extraParams: extraParamEstado,
		formatItem: function (row) { return row[0]; },
		formatResult: function (row) { return row[0]; }
	});

	$("#consumidor_cidade").result(function(event, data, formatted) {
		$("#consumidor_cidade").val(data[0].toUpperCase());
	});

	var getEstado = function () { return $("#consumidor_estado").val(); };

	/* Busca pelo Nome */
	$("#posto_nome_tab").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		extraParams: {estado: getEstado},
		formatItem: formatItemPosto,
		formatResult: function(row) {
			return row[3];
		}
	});

	$("#posto_nome_tab").result(function(event, data, formatted) {
		if (login_fabrica == 74 && ($("#posto_atual").val() != data[0])) {
 			verifica_os_aberta();
 		}

		$("#posto_tab").val(data[0]) ;
		$("#codigo_posto_tab").val(data[2]) ;
		$("#posto_nome_tab").val(data[3]) ;
		$("#posto_fone_tab").val(data[5]) ;
		$("#posto_email_tab").val(data[6]) ;

		$("#codigo_posto").val(data[2]) ;
		$("#posto_nome").val(data[3]) ;

		<?php
 		if ($login_fabrica == 74) {
 		?>
 			$("#posto_atual").val(data[0]);
 		<?php
 		}
 		?>

	});

	/* ! Busca pelo Nome do cliente  --------- */
	bindEventClienteNomeAdmin();
	/* Busca pelo Nome do cliente  FIM ----- */

	$("#cliente_nome_admin").result(function(event, data, formatted) {
	$("#cliente_admin").val(data[0]) ;
	$("#cliente_nome_admin").val(data[3]) ;
	});

	$("input[name=cliente_nome_admin]").blur(function(){
		if ($(this).val().length == 0) {
			$("#cliente_admin").val('') ;
		};
	});

	hora = new Date();
	engana = hora.getTime()

	//HD 201434 - Campo Localizar desmembrado em vários, linhas do autocomplete do antigo campo excluídas

	$("#container-Principal > ul > li > a").click(function () {
		if ($(this).parent("li").hasClass("tabs-disabled")) {
			return;
		}

		$('#tab_atual').val($(this).attr("rel"));

		if ($(this).attr("rel") == "ressarcimento") {
			validaOsRessarcimento();
		}
	});
});

function preencheConsumidorAutocomplete(event, data, formatted) {

	var formulario = document.frm_callcenter;
	var os = document.getElementById('os')

	formulario.consumidor_nome.value = data[1];
	formulario.consumidor_cpf.value = data[11];
<?if ($login_fabrica <> 85) {?>
	formulario.consumidor_rg.value = data[12];
<? } ?>
	formulario.consumidor_email.value = data[13];
	formulario.consumidor_fone.value = data[8];
	formulario.consumidor_cep.value = data[6];
	formulario.consumidor_endereco.value = data[2];
	formulario.consumidor_numero.value = data[3];
	formulario.consumidor_complemento.value = data[4];
	formulario.consumidor_bairro.value = data[5];
	formulario.consumidor_cidade.value = data[14];
	formulario.consumidor_estado.value = data[15];
	os.value = data[16];
	formulario.consumidor_fone2.value = data[9];
	formulario.consumidor_fone3.value =	 data[10];

	if ((typeof formulario.consumidor_cpf != "undefined") && (typeof formulario.consumidor_cnpj != "undefined")) {
		if (data[11].length > 11) {
			formulario.consumidor_cnpj.checked = true;
		}
		else {
			formulario.consumidor_cpf.checked = true;
		}
	}

	if (typeof formulario.consumidor_revenda_c != "undefined") {
		switch (data[38]) {
			case "O":
				formulario.consumidor_revenda_c.checked = true;
			break;

			case "C":
				formulario.consumidor_revenda_c.checked = true;
			break

			case "R":
				formulario.consumidor_revenda_r.checked = true;
			break

			case "A":
				formulario.consumidor_revenda_a.checked = true;
			break
		}
	}
}


function verificarImpressao(check){
	if (check.checked){
		$('#imprimir_os').show();
	}else{
		$('#imprimir_os').hide();
	}
}

function fnc_pesquisa_serie (campo, campo2, tipo, mapa_linha,campo3, pos) {
	if (tipo == "serie") {
		var xcampo = campo3;
	}

	if (xcampo.value != "") {
		Shadowbox.open({
	        content :   "produto_serie_pesquisa_new_nv.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t" + "&pos=" + pos,
	        player  :   "iframe",
	        title   :   "Pesquisa",
	        width   :   800,
	        height  :   500
	    });
	}else{
		alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
		return false;
	}
}

function retorna_serie(descricao, referencia, serie, voltagem, produto, ordem, linha, pos, data_fabricacao) {
	var fabrica = "<?=$login_fabrica?>";
	var opt = "";
	if(pos.length > 0 && fabrica == 52) {
		opt = "_" + pos;
	}

	if(fabrica != 52) {
		gravaDados("voltagem", voltagem);

		if(linha) {
			$("#mapa_linha").val(linha);
		}
	}

	gravaDados("produto_nome" + opt, descricao);
	gravaDados("produto_referencia" + opt, referencia);
	gravaDados("serie" + opt, serie);
	gravaDados("numero_ativo" + opt, ordem);

	<? if($login_fabrica == 74){?>
		gravaDados("dt_fabricacao" + opt, data_fabricacao);
	<?}?>
}

function retorna_produto(descricao, referencia, voltagem, produto, linha, pos) {
	var fabrica = "<?=$login_fabrica?>";
	var opt 	= '';

	if(pos.length > 0 && fabrica == 52) {
		var opt = "_" + pos;
	}

	if(fabrica != 52) {
		gravaDados("voltagem", voltagem);

		if(linha) {
			$("#mapa_linha").val(linha);
		}
	}

	gravaDados("produto_nome" + opt, descricao);
	gravaDados("produto_referencia" + opt, referencia);
	$("#serie").focus();
}

function retorna_numero_ativo(ordem, serie, referencia, descricao, produto, linha, voltagem, pos) {

	var fabrica = "<?=$login_fabrica?>";

	if(pos.length > 0 && fabrica == 52) {
		var opt = "_" + pos;
	}

	if(fabrica != 52) {
		gravaDados("voltagem", voltagem);

		if(linha) {
			gravaDados("mapa_linha", linha);
		}
	}

	gravaDados("serie" + opt, serie);
	gravaDados("produto_nome" + opt, descricao);
	gravaDados("produto_referencia" + opt, referencia);
	gravaDados("numero_ativo" + opt, ordem);
}

function fnc_pesquisa_ordem (campo, campo2, tipo, mapa_linha,campo3, campo4, pos) {
	if (tipo == "ordem") {
		var xcampo = campo4;
	}

	if (xcampo.value != "") {
		var url = "produto_numero_ativo_pesquisa_nv.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t&pos=" + pos;

		Shadowbox.open({
	        content :   url,
	        player  :   "iframe",
	        title   :   "Pesquisa",
	        width   :   800,
	        height  :   500
   		});

		/*janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.ordem   = campo4;
		janela.serie   = campo3;
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.mapa_linha   = mapa_linha;
		janela.voltagem     = document.frm_callcenter.voltagem;
		janela.focus();*/
	}else{
		alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
		return false;
	}
}

var janela = null;
var janela_descricao = null;

function fnc_pesquisa_produto2 (campo, campo2, tipo, mapa_linha, pos) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
		//alert(xcampo.value);
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "produto_pesquisa_3.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t&pos=" + pos;

		Shadowbox.open({
	        content :   url,
	        player  :   "iframe",
	        title   :   "Pesquisa",
	        width   :   800,
	        height  :   500
	    });
	}
}

function MudaCampo(campo){
	if (campo.value == 'reclamacao_at') {
		document.getElementById('info_posto').style.display='inline';
	}else{
		document.getElementById('info_posto').style.display='none';
	}
}

function enviaEmail(callcenter){
	url = "envio_email_callcenter.php?callcenter=" + callcenter;
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=700, height=500, top=18, left=0");
}

function validaOsRessarcimento() {

	var os = $('#os').val();

	if (os.length == 0) {
		alert('Para fazer o ressarcimento é necessario escolher a Ordem de serviço no cabeçalho do programa');
	} else {
		$('#os_ressarcimento').val(os);
	}
}

</script>

<script type="text/javascript" src="js/thickbox.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script type="text/javascript">
	$(document).ready(function() {
		Shadowbox.init();

		var login_fabrica = "<?=$login_fabrica?>";

		if (login_fabrica == 85) {
			$("input[name=data_fabricacao_ambev]").mask("99/99/9999");
			$("input[name=data_encerramento_ambev]").mask("99/99/9999");

			$("input[name=atendimento_ambev]").change(function () {
				if ($("input[name=atendimento_ambev]:checked").val() == "t") {
                    $("#codigo_ambev").show();
					$("#data_encerramento_ambev").show();
				} else {
                    $("#codigo_ambev").hide();
					$("#data_encerramento_ambev").hide();
				}
			});

			$('#mapa_estado').change(function(){

				var estado = $(this).val();

				$.ajax({

					url: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: "ajax-estado="+estado,
					complete: function(data){
						data = data.responseText;
						$('#mapa_cidade').html(data);
					}

				});

			});

			$('#mapa_cidade').change(function(){

				var cidade = $(this).val();

				$.ajax({

					url: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: "ajax-cidade="+cidade,
					complete: function(data){
						data = data.responseText;
						$('#postos_cidade').html(data);
					}

				});

			});

			$('#postos_cidade').change(function(){

				var posto = $(this).val();
				if(login_fabrica != 74){
					var cidade_posto = "";
				}
				informacoesPosto(posto, cidade_posto);

			});

		}else{
			if(login_fabrica == 74){

				$('#mapa_estado').change(function(){

				var estado = $(this).val();

					$.ajax({

						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: "ajax-estado="+estado,
						complete: function(data){


							data = data.responseText;
							$('#mapa_cidade').html(data);
						}

					});

				});

				$('#mapa_cidade').change(function(){

					var cidade = $(this).val();

					$.ajax({

						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: "ajax-cidade="+cidade+"&bairro=true",
						complete: function(data){
							data = data.responseText;
							$('#mapa_bairro').html(data);
						}

					});

				});

				$('#mapa_bairro').change(function(){

					var cidade = $('#mapa_cidade').val();
					var bairro = $(this).val();
					console.log(cidade);
					console.log(bairro);
					$.ajax({

						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: "ajax-cidade="+cidade+"&bairro_escolhido="+bairro,
						complete: function(data){
							data = data.responseText;
							$('#postos_cidade').html(data);
						}

					});

				});

				function getPostosAjax(){
					$.ajax({

						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: "ajax-cidade="+cidade,
						complete: function(data){
							data = data.responseText;
							$('#postos_cidade').html(data);
						}
					});

				}

				$('#postos_cidade').change(function(){

					var posto = $(this).val();
					var cidade_posto = "";
					informacoesPosto(posto, cidade_posto);

				});




			}

		}

	});

function atualizaMapa(){
	var cidade = $('#consumidor_cidade').val();
	var estado = $('#consumidor_estado').val();
	$('#link').attr('href','callcenter_interativo_posto.php?fabrica=12<?echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
	$('#link2').attr('href','callcenter_interativo_posto.php?fabrica=12<?echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
}
function minimizar(arquivo){
	if (document.getElementById(arquivo)){
		var style2 = document.getElementById(arquivo);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

function formata_data(valor_campo, form, campo){
	var mydata = '';
	mydata = mydata + valor_campo;
	myrecord = campo;
	myform = form;

	if (mydata.length == 2){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}
	if (mydata.length == 5){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}

}

function fnc_pesquisa_satisfacao(callcenter){
    Shadowbox.open({
        content :   "pesquisa_satisfacao_new.php?callcenter="+callcenter,
        player  :   "iframe",
        title   :   "Pesquisa de Satisfação",
        width   :   800,
        height  :   500
    });
}

//HD 163220 - Alterada a função para chamar pesquisa de todos os tipos pelo popup
/*************************************************************************************/
/*************************** Função PESQUISA DE CONSUMIDOR ***************************/
/*************************************************************************************/
function fnc_pesquisa_consumidor_callcenter(valor, tipo_pesquisa, busca_exata) {

	/*
		var url = "pesquisa_consumidor_callcenter_new.php?localizar=" + valor + "&tipo=" + tipo_pesquisa;

		if (typeof busca_exata == "undefined") {
			busca_exata = false;
		}

		if (busca_exata) {
			url += "&exata=sim";
		}

		if (valor.length > 5) {
			janela = window.open(url,"callcenter_interativo_new_pesquisa_consumidor","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=780,height=400,top=18,left=0");
			janela.formulario = document.frm_callcenter;
			janela.os = document.getElementById('os_ressarcimento');
			janela.focus();
		}else {
			alert("Digite pelo menos 6 caracteres para efetuar a busca");
		}
	*/

	if (busca_exata) {
		var exata = "sim";
	}

	if (valor.length > 5) {
		Shadowbox.open({
		content :   "pesquisa_consumidor_callcenter_nv.php?descricao_pesquisa="+valor+"&tipo_pesquisa="+tipo_pesquisa+"&exata="+exata,
		player  :   "iframe",
		title   :   "Pesquisa",
		width   :   800,
		height  :   500
		});

	}else{
			alert("Digite pelo menos 6 caracteres para efetuar a busca");
	}

}

function fnc_pesquisa_revenda (campo, tipo,cidade) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda_callcenter.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda_callcenter.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if (tipo == "cidade") {
		url = "pesquisa_revenda_callcenter.php?cidade=" + campo.value + "&tipo=cidade";
	}
	if (tipo == "familia") {
		url = "pesquisa_revenda_callcenter.php?familia=" + campo.value + "&tipo=familia&consumidor_cidade=" + cidade.value;

	}

	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");

	<?php if($tab_atual == "indicacao_rev"){ ?>
			janela.nome         = document.frm_callcenter.revenda_ind_nome;
			janela.cnpj         = document.frm_callcenter.revenda_ind_cnpj;
			janela.revenda      = document.frm_callcenter.revenda_ind;
	<?php }else { ?>
			janela.nome         = document.frm_callcenter.revenda_nome;
			janela.revenda      = document.frm_callcenter.revenda;
	<?php } ?>
	janela.endereco     = document.frm_callcenter.revenda_endereco;
	janela.numero       = document.frm_callcenter.revenda_nro;
	janela.complemento  = document.frm_callcenter.revenda_cmpto;
	janela.bairro       = document.frm_callcenter.revenda_bairro;
	janela.cidade       = document.frm_callcenter.revenda_city;
	janela.estado       = document.frm_callcenter.revenda_uf;
	janela.fone         = document.frm_callcenter.revenda_fone;
	janela.focus();
}

function pesquisaRevenda(campo,tipo){
		var campo = campo.value;

		if (campo.value != ""){
			Shadowbox.open({
				content:	"pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Revenda",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

function retorna_revenda(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email,revenda){
	gravaDados("revenda_ind_nome",nome);
	gravaDados("revenda_ind_cnpj",cnpj);
	gravaDados("revenda_ind",revenda);
}

function pesquisaPosto(campo,tipo){
	var campo = campo.value;

	if (campo.value != ""){
		Shadowbox.open({
			content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
			player:	    "iframe",
			title:		"Pesquisa Posto",
			width:	    800,
			height:	    500
		});
	}else
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function retorna_posto(codigo,posto, nome) {
	gravaDados("codigo_posto_ind", codigo);
	gravaDados("posto_nome_ind", nome);
}

function gravaDados(name, valor){
	try{
		$("input[name="+name+"]").val(valor);
	} catch(err){
		return false;
	}
}

function zxxx (campo) {

	url = "pesquisa_os_callcenter.php?sua_os=" + campo;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.sua_os			= document.frm_callcenter.sua_os;
	janela.data_abertura	= document.frm_callcenter.data_abertura;
	janela.data_nf	        = document.frm_callcenter.data_nf;
	janela.serie	        = document.frm_callcenter.serie;
	janela.nota_fiscal	    = document.frm_callcenter.nota_fiscal;
	janela.produto	        = document.frm_callcenter.produto;
	janela.produto_nome	    = document.frm_callcenter.produto_nome;
	janela.revenda_nome	    = document.frm_callcenter.revenda_nome;
	janela.revenda	        = document.frm_callcenter.revenda;
	//janela.posto        	= document.frm_callcenter.posto;
	janela.posto_nome     	= document.frm_callcenter.posto_nome;

	janela.focus();

}

/* ============= Função PESQUISA DE POSTO POR MAPA ====================
Nome da Função : fnc_pesquisa_at_proximo()
=================================================================*/
function fnc_pesquisa_at_proximo(fabrica) {
	url = "callcenter_interativo_posto.php?fabrica=12"+fabrica;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=750,height=500,top=18,left=0");
	janela.posto_tab = document.frm_callcenter.posto_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab = document.frm_callcenter.posto_nome_tab;
	janela.posto_cidade_tab = document.frm_callcenter.posto_cidade_tab;
	janela.posto_estado_tab= document.frm_callcenter.posto_estado_tab;
	janela.posto_endereco_tab = document.frm_callcenter.posto_endereco_tab;
	janela.posto_km_tab = document.frm_callcenter.posto_km_tab;
	janela.posto_fone_tab = document.frm_callcenter.posto_fone_tab;
	janela.posto_email_tab = document.frm_callcenter.posto_email_tab;
	janela.abas = $('#container-Principal');
	janela.focus();
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatao da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8;
	var DEL=  46;
	var FRENTE=  39;
	var TRAS=  37;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true;
			}
		if ( tecla == 13) return false;
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla);
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http1 = new Array();

function mostraDefeitos(natureza,produto,idx,defeito){
	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();
	url = "callcenter_interativo_defeitos.php?ajax=true&natureza="+ natureza +"&produto=" + produto + "&defeito=" +defeito;
	http1[curDateTime].open('get',url);

	<?php
	if ($login_fabrica == 52) {
		$campo = "'defeito_reclamado_'";
	} else {
		$campo = "'div_defeitos'";
	}
	?>

	if (idx) {
		var el = <?php echo $campo ?> + idx;
	} else {
		var el = <?php echo $campo ?>;
	}

	var campo = document.getElementById(el);

	if (!campo) {
		return true;
	}

	<?php if ($login_fabrica == 52) { ?>
	if (campo.value.length == 0){
	<?php } ?>

		http1[curDateTime].onreadystatechange = function(){
			if(http1[curDateTime].readyState == 1) {
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}
			if (http1[curDateTime].readyState == 4){
				if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
					var results = http1[curDateTime].responseText;
					$(campo).html(results);
				}else {
					campo.innerHTML = "Erro";
				}
			}
		}
		http1[curDateTime].send(null);

	<?php if ($login_fabrica == 52) { ?>
	}else{
		return false;
	}
	<?php } ?>

}

var http2 = new Array();
function localizarFaq(produto,local,action){
	var action          = ( action == undefined ) ? 'pesquisa' : action ;
	var faq_duvida      = (action == 'pesquisa') ? document.getElementById(local).value : '    ';
	var campo           = '#div_'+local;
	var hd_chamado      = '<?php echo $callcenter; ?>';
	var buscar_marcados = ( action == 'pesquisa' ) ? '0' : '1' ;
	if ( hd_chamado.length <= 0 ) {
		hd_chamado = 0;
	}
	if(produto.length==0 && action == 'pesquisa'){
		alert('Por favor selecione o produto');
		return 0;
	} else if ( produto.length==0 ) {
		return 0;
	}

	if(faq_duvida.length==0){
		alert('Por favor inserir a dúvida');
		return 0;
	}

	//url    = "callcenter_interativo_ajax.php";
	//params = {'ajax':'true', 'faq_duvida':'true','produto':produto,'duvida':faq_duvida,'hd_chamado':hd_chamado,'buscar_marcados':buscar_marcados };
	url    = "callcenter_interativo_ajax.php?ajax=true&faq_duvida=true&produto="+produto+"&faq_duvida="+faq_duvida+"&hd_chamado="+hd_chamado+"&buscar_marcados="+buscar_marcados;
	params = '';
	$(campo).empty();
	$.get(url,params,function(resposta) {
		$(campo).html(resposta);
		$('.chk_faq').click(function() {
			$('.chk_faq').parent().css('background','none').css('cursor','pointer')
					     .end().filter(':checked').parent().css('background','#BCCACD');
		});
	},'html');
}
var http3 = new Array();
function localizarConsumidor(busca,tipo){
	if (tipo=='novo'){
		$('#tabela_consumidor input').each( function(){
			$(this).val('');
		});
		$('#consumidor_nome').focus();
		return false;
	}
	var campo = document.getElementById('div_consumidor');
	$(campo).empty();
	var busca = document.getElementById(busca).value;
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&busca_cliente=tue&busca=" + busca + "&tipo=" + tipo;
	http3[curDateTime].open('get',url);

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
				bindEventClienteNomeAdmin(campo);
			}else {
				campo.innerHTML = "Erro";
			}
		}

		$("#consumidor_fone").mask("(999) 9999-9999");
		$("#consumidor_cep").mask("99999-999");
		$("#hora_ligacao").mask("99:99");
	}
	http3[curDateTime].send(null);
}

function mostraEsconde(){
	$("div[@rel=div_ajuda]").toggle();
}
var http4 = new Array();
function fn_verifica_garantia(){
	var produto_nome       = document.getElementById('produto_nome_es').value;
	var produto_referencia = document.getElementById('produto_referencia_es').value;
	var serie              = document.getElementById('serie_es').value;
	 var campo = document.getElementById('div_estendida');
	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&garantia=tue&produto_nome=" + produto_nome + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
	http4[curDateTime].open('get',url);

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
				var results = http4[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
		$("#es_data_compra").mask("99/99/9999");
		$("#es_data_nascimento").mask("99/99/9999");
		$("#es_fonecomercial").mask("(99) 9999-9999");
	}
	http4[curDateTime].send(null);
}

/*  Tirei o bairro e aumentei o tamanhodo pop-up    */
function mapa_rede_new(linha,estado,cidade,cep,endereco,numero,bairro,consumidor_cidade,consumidor_estado){

	var endereco_completo = "";
	var endereco_rota = "";

	if(endereco.value != ""){ endereco_completo += endereco.value+","; }
	if(numero.value != ""){ endereco_completo += numero.value+","; }
	if(bairro.value != ""){ endereco_completo += bairro.value+","; }
	if(consumidor_cidade.value != ""){ endereco_completo += consumidor_cidade.value+","; }
	if(consumidor_estado.value != ""){ endereco_completo += consumidor_estado.value; }
	endereco_completo += ", Brasil";

	if(endereco.value != ""){ endereco_rota += endereco.value+","; }
	if(numero.value != ""){ endereco_rota += numero.value+","; }
	if(consumidor_cidade.value != ""){ endereco_rota += consumidor_cidade.value+","; }
	if(consumidor_estado.value != ""){ endereco_rota += consumidor_estado.value; }
	endereco_rota += ", Brasil";

	var nome_cliente = $("#consumidor_nome").val();
	url = "mapa_rede_new.php?callcenter=true&pais=BR&estado="+estado.value+"&linha="+linha.value+"&cidade="+cidade.value+"&cep="+cep.value+"&consumidor_cidade="+consumidor_cidade.value+"&consumidor_estado="+consumidor_estado.value+"&consumidor="+endereco_completo+"&nome="+nome_cliente+"&endereco_rota="+endereco_rota;
	janela = window.open(url,"janela","width=960,height=600,scrollbars=yes,resizable=yes");
	janela.posto_tab        = document.frm_callcenter.posto_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab   = document.frm_callcenter.posto_nome_tab;
	janela.posto_email_tab  = document.frm_callcenter.posto_email_tab;
	janela.posto_fone_tab   = document.frm_callcenter.posto_fone_tab;
	janela.posto_km_tab     = document.frm_callcenter.posto_km_tab;

}

function fnc_pesquisa_os (campo, tipo) {
	var url = "";
	if (tipo == "os") {
		url = "pesquisa_os_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=os";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=cpf";
	}

	if (tipo == "nota_fiscal") {
		url = "pesquisa_os_callcenter.php?nota_fiscal=" + campo.value + "&tipo=nota_fiscal";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0,resizable=yes");

		janela.produto_referencia      = document.frm_callcenter.produto_referencia;
		janela.produto_nome            = document.frm_callcenter.produto_nome;
		janela.produto_serie           = document.frm_callcenter.serie;
		janela.produto_nf              = document.frm_callcenter.nota_fiscal;
		janela.produto_nf_data         = document.frm_callcenter.data_nf;
		janela.sua_os                  = document.frm_callcenter.os;
		janela.posto_nome              = document.frm_callcenter.posto_nome;
		janela.posto_codigo            = document.frm_callcenter.codigo_posto;
		<? if($login_fabrica==11) { //HD 14549 ?>
			janela.consumidor_nome         = document.frm_callcenter.consumidor_nome;
			janela.consumidor_cpf          = document.frm_callcenter.consumidor_cpf;
			janela.consumidor_cep          = document.frm_callcenter.consumidor_cep;
			janela.consumidor_fone         = document.frm_callcenter.consumidor_fone;
			janela.consumidor_endereco     = document.frm_callcenter.consumidor_endereco;
			janela.consumidor_numero       = document.frm_callcenter.consumidor_numero;
			janela.consumidor_complemento  = document.frm_callcenter.consumidor_complemento;
			janela.consumidor_bairro       = document.frm_callcenter.consumidor_bairro;
			janela.consumidor_cidade       = document.frm_callcenter.consumidor_cidade;
			janela.consumidor_estado       = document.frm_callcenter.consumidor_estado;
			janela.abas = $('#container-Principal');
		<? } ?>
		janela.focus();
	}
}

function atualizaQuadroMapas(){

	/* Atualiza os dados do posto conforme cidade e estado do Consumidor */

	var estado_selecionado = $('#consumidor_estado').val();

	/* Centro Oeste */
	if (estado_selecionado == 'GO' || estado_selecionado == 'MT' || estado_selecionado == 'MS' || estado_selecionado == 'DF'){
		estado_selecionado = 'BR-CO';
	}

	/* Nordeste */
	if (estado_selecionado == 'AL' || estado_selecionado == 'BA' || estado_selecionado == 'CE' || estado_selecionado == 'MA' || estado_selecionado == 'PB' || estado_selecionado == 'PE' || estado_selecionado == 'PI' || estado_selecionado == 'RN' || estado_selecionado == 'SE'){
		estado_selecionado = 'BR-NE';
	}

	/* Norte */
	if (estado_selecionado == 'AC' || estado_selecionado == 'AP' || estado_selecionado == 'AM' || estado_selecionado == 'PA' || estado_selecionado == 'RR' || estado_selecionado == 'RO' || estado_selecionado == 'TO'){
		estado_selecionado = 'BR-N';
	}

	$('#mapa_cidade').val( $('#consumidor_cidade').val() );
	$('#mapa_estado').val( estado_selecionado );
}


function txtBoxFormat(objForm, strField, sMask, evtKeyPress) {
	var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

	if(document.all) { // Internet Explorer
		nTecla = evtKeyPress.keyCode;
	} else if(document.layers) { // Nestcape
		nTecla = evtKeyPress.which;
	} else {
		nTecla = evtKeyPress.which;
		if (nTecla == 8) {
			return true;
		}
	}

	sValue = objForm[strField].value;

	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( " ", "" );
	sValue = sValue.toString().replace( " ", "" );
	fldLen = sValue.length;
	mskLen = sMask.length;

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
	bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
	bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))


	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++;

	} else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}
	i++;
	}

	objForm[strField].value = sCod;
	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
		return ((nTecla > 47) && (nTecla < 58)); } // números de 0 a 9
	else { // qualquer caracter...
		return true;
	}
	} else {
		return true;
	}
}


<?PHP if ($login_fabrica == 3) { ?>
	window.onload = function foco(){
		var campo = document.getElementById("consumidor_nome");
		campo.focus();
	}
<? } ?>

<?PHP if ($login_fabrica == 2 || $login_fabrica == 10 || $login_fabrica == 91 || $login_fabrica == 7 || $login_fabrica == 35) { ?>
function fnc_tipo_atendimento(tipo) {
		$('#cpf').val('');
		$('#label_nome').show();
	if (tipo.value == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
		$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );
		$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
		$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
		$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
		$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
	} else {
		if (tipo.value == 'R' || tipo.value=='E') {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );
			$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
			$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		} else {
			if (tipo.value == "F") {
				$('#label_nome').hide();
			}
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
			$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
			$('#label_cnpj').click(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );

			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		}
	}
}

<? }elseif(in_array($login_fabrica,array(24,30,52,59,85,138,145))){ // HD 75777 ?>
function fnc_tipo_atendimento(tipo) {
		$('#cpf').val('');
	if (tipo.value == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
		<?php
		if ($login_fabrica == 85) {
		?>
            $('#label_nome').html('Nome:');
			$("#nome_fantasia").hide();
			$("input[name=nome_fantasia]").val("");
		<?php
		}
		?>
	} else {
		if (tipo.value == 'R') {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
			<?php
			if ($login_fabrica == 85) {
			?>
                $('#label_nome').html('Razão Social:');
				$("#nome_fantasia").show();
			<?php
			}
			?>
		}
	}
}
<?}?>

var http5 = new Array();
function listaFaq(produto){
	var campo = document.getElementById('div_faq_duvida_duvida');
	if(produto.length==0){
		alert('Por favor selecione o produto');
	}else{
		var curDateTime = new Date();
		http5[curDateTime] = createRequestObject();

		url = "callcenter_interativo_ajax.php?ajax=true&listar=sim&produto=" + produto;
		http5[curDateTime].open('get',url);

		http5[curDateTime].onreadystatechange = function(){
			if(http5[curDateTime].readyState == 1) {
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}
			if (http5[curDateTime].readyState == 4){
				if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
					var results = http5[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";

				}
			}
		}
		http5[curDateTime].send(null);
	}
}

function indicacao(check){
	if (check.checked){
		$('.input_req').val('Indicação de Posto');
		$('#telefone').val('(000) 0000-0000');
		$('#cpf').val('000.000.000-00');
		$('#cep').val('00000-000');
		$('#consumidor_numero').val('00');
		$('#hora_ligacao').val('00:00');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);
		$('#status_interacao').val('Resolvido');


		$('.input_req').attr('readonly', true);
		$('.input_req').attr('disabled', true);

		$('#consumidor_estado').attr('disabled', true);
		$('#origem').attr('disabled', true);
		$('#consumidor_revenda').attr('disabled', true);
		$('#receber_informacoes').attr('disabled', true);
		$('#status_interacao').attr('disabled', true);


	} else {

		$('.input_req').val('');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);

		input_req = $(".input_req").get();
		for(i = 0; i < input_req.length; i++){
			$(input_req[i]).removeAttr('readonly');
			$(input_req[i]).removeAttr('disabled');
		}

		$('#consumidor_estado').removeAttr('disabled');
		$('#origem').removeAttr('disabled');
		$('#consumidor_revenda').removeAttr('disabled');
		$('#receber_informacoes').removeAttr('disabled');
		$('#status_interacao').removeAttr('disabled');

	}
}


// hd-2109903 


function indicacao_suggar(check, evento){
	if (check.checked){
		
		$('#receber_informacoes').attr('disabled', true);
		$('#consumidor_rg').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#cep').val('00000-000').attr({'disabled':true , 'readonly': true});
		// $('#telefone').val('00000-000').attr({'disabled':true , 'readonly': true});
		$('#consumidor_complemento').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#consumidor_numero').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#consumidor_email').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#consumidor_endereco').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#contato_nome').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#consumidor_bairro').val('Indicação de Posto').attr({'disabled':true , 'readonly': true});
		$('#hora_ligacao').val('00:00');
		$('#status_interacao').val('Resolvido').attr('disabled', true);
		$('#data_nf').val('').attr({'disabled':true , 'readonly': true});
		$('#nota_fiscal').val('').attr({'disabled':true , 'readonly': true});
		$('#serie').val('').attr({'disabled':true , 'readonly': true});

	//HD 235203: Coloquei o evento, pois quando carrega a página e não está marcado indicação de posto, não pode entrar aqui, senão apaga tudo o que o usuário digitou nos dados do consumidor
	} else if (evento == 'onchange') {

		$('#consumidor_revenda').attr('disabled', false);
		$('#receber_informacoes').attr('disabled', false);
		$('#status_interacao').attr('disabled', false);
		$('#consumidor_rg').val('').attr({'readonly': false , 'disabled':false});
		$('#cep').val('').attr({'readonly': false , 'disabled':false});
		// $('#telefone').val('').attr({'readonly': false , 'disabled':false});
		$('#consumidor_complemento').val('').attr({'readonly': false , 'disabled':false});
		$('#consumidor_numero').val('').attr({'readonly': false , 'disabled':false});
		$('#consumidor_email').val('').attr({'readonly': false , 'disabled':false});
		$('#consumidor_endereco').val('').attr({'readonly': false , 'disabled':false});
		$('#contato_nome').val('').attr({'readonly': false , 'disabled':false});
		$('#consumidor_bairro').val('').attr({'readonly': false , 'disabled':false});
		$('#data_nf').val('').attr({'disabled':false , 'readonly': false});
		$('#nota_fiscal').val('').attr({'disabled':false , 'readonly': false});
		$('#serie').val('').attr({'disabled':falas , 'readonly': false});

	}
}


function liberar_campos(){
	input_req = $(".input_req").get();
	for(i = 0; i < input_req.length; i++){
		$(input_req[i]).removeAttr('readonly');
		$(input_req[i]).removeAttr('disabled');
	}
	select_req = $("select:disabled").get();
	for(i = 0; i < select_req.length; i++){
		$(select_req[i]).removeAttr('disabled');
	}
}

function bloquear_campos(){
	$("input").attr({'disabled':true});
	$("textarea").attr({'disabled':true});
	$("select").attr({'disabled':true});


}


function geraProtocolo() {
	var div = $('#protocolo');
	div.html("<img src='imagens/ajax-loader.gif' width='20' height='20'>");
	requisicaoHTTP('GET','gera_protocolo.php', true , 'mostraProtocolo');
}

function mostraProtocolo(campos) {
	var campos_array = campos.split('|');
	if (campos_array[0]=='sim') {
		var div = $('#protocolo');
		var protocolo = $('#protocolo_id');
		div.html("n <font color='#CC0033'><b>"+campos_array[1]+"</font></b>");
		protocolo.val(campos_array[1]);

	} else {
		var div = $('#protocolo');
		div.html('Erro ao gerar Protocolo');
	}
}

function altera2Aba(nu) {//HD 262718

	if ($('#container-Principal > ul > li').length) {

		var obj   = $('#container-Principal > ul > li > a')[(nu-1)];
		var tabId = obj.href.split('#')[1];

		$('#container-Principal > ul > li').each(function(i) {

			$('#container-Principal').disableTab(parseInt(i) + 1);

		});

		$('#container-Principal').enableTab(parseInt(nu));
		$('#tab_atual').val(tabId);

		$(obj).click();

	}

}

function comboMotivo(tipo,motivo){

	if(tipo == 'R' || tipo == 'P' || tipo == 'T') {
		$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: {ajax : 1, combo_motivo : 1, tipo : tipo, motivo : motivo},

		complete: function(http) {
			results = http.responseText;
			$('#hd_motivo_ligacao').html(results);
			$('.hd_motivo_ligacao').show();
		}

	});

	}else{
		$('#hd_motivo_ligacao').html('');
		$('.hd_motivo_ligacao').hide();
	}
}

function geraXml(atendimento){
	var coleta = $("input[name=coleta]:checked").val();
	var valor_declarado = $("#valor_declarado").val();

	if(valor_declarado <= 0 ){
		alert("Informe o valor declarado");
		return false;
	}

	$.ajax({
		url: "ajax_gera_xml_nks.php?hd_chamado="+atendimento+"&coleta="+coleta+"&valor_declarado="+valor_declarado,
		cache: false,
		success: function(data) {

			retorno = data.split('|');

			if (retorno[0]=="OK") {
				$("#gerar_xml").html("<input type='button' value='Download XML' onclick=\"window.open('xls/"+atendimento+".zip');\">")
				window.location.href = "xls/"+atendimento+".zip";

			} else {
				alert(retorno[0]);
			}

		}
	});

}

<?php
if ($login_fabrica == 24) {
?>
	$(function () {
		$("#ligacao_agendada").datepick({startDate:"01/01/2000"}).mask("99/99/9999");
	});
<?php
}
?>
</script>


<?php // VALIDAÇÃO NUMERO TELEFONE / CELULAR VALIDO
	$prefixo = 55;
?>
	<script language="javascript" type="text/javascript" src="js/phoneparser.js"></script>

	<script>
		$(function(){

		    var cel = "";

		    $("#telefone3").blur(function() {

		    	if($(this).val() == "" || $(this).val() == cel){
		        	return;
		    	}

		    	cel = "<?php echo $prefixo?>"+$(this).val();
	      		var res = parsePhone($.trim(cel));

		      	if(JSON.stringify(res) == "null"){

			        $(this).focus();
			        alert("Número de Celular Inválido. Por favor verifique!");
			        return;

	    	    }
		    });

		    $("#telefone2").blur(function() {

		    	if($(this).val() == "" || $(this).val() == cel){
		        	return;
		    	}

		    	cel = "<?php echo $prefixo?>"+$(this).val();
	      		var res = parsePhone($.trim(cel));

		      	if(JSON.stringify(res) == "null"){

			        $(this).focus();
			        alert("Número de Telefone Inválido. Por favor verifique!");
			        return;

	    	    }
		    });

		    $("#telefone").blur(function() {

		    	if($(this).val() == "" || $(this).val() == cel){
		        	return;
		    	}

		    	cel = "<?php echo $prefixo?>"+$(this).val();
	      		var res = parsePhone($.trim(cel));

		      	if(JSON.stringify(res) == "null"){

			        $(this).focus();
			        alert("Número de Telefone Inválido. Por favor verifique!");
			        return;

	    	    }
		    });
		});

	</script>


<br><br><?php

if (strlen($msg_erro) > 0) { ?>

	<!-- Colocar aqui a função substr HD#311031 -->
	<? //recarrega informacoes
	$callcenter                = trim($_POST['callcenter']);
	$data_abertura_callcenter  = trim($_POST['data_abertura_callcenter']);
	$natureza_chamado          = trim($_POST['natureza_chamado']);
	$consumidor_nome           = trim($_POST['consumidor_nome']);
	$cliente                   = trim($_POST['cliente']);
	$consumidor_cpf            = trim($_POST['consumidor_cpf']);
	$consumidor_rg             = trim($_POST['consumidor_rg']);
	$consumidor_rg             = preg_replace("/\W/","",$consumidor_rg);
	$consumidor_email          = trim($_POST['consumidor_email']);
	$consumidor_fone           = trim($_POST['consumidor_fone']);
	$consumidor_fone2          = trim($_POST['consumidor_fone2']);
	$consumidor_fone3          = trim($_POST['consumidor_fone3']);
	$consumidor_cep            = trim($_POST['consumidor_cep']);
	$consumidor_cep            = str_replace("-","",$consumidor_cep);
	$consumidor_cep            = str_replace("/","",$consumidor_cep);
	$consumidor_endereco       = trim($_POST['consumidor_endereco']);
	$consumidor_numero         = trim($_POST['consumidor_numero']);
	$consumidor_complemento    = trim($_POST['consumidor_complemento']);
	$ponto_referencia		   = trim($_POST['ponto_referencia']);
	$consumidor_bairro         = trim($_POST['consumidor_bairro']);
	$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
	$consumidor_cidade         = str_replace("\\","",$consumidor_cidade);
	$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
	$assunto                   = trim($_POST['assunto']);
	$sua_os                    = trim($_POST['sua_os']);
	$data_abertura             = trim($_POST['data_abertura']);
	$produto                   = trim($_POST['produto']);
	$produto_referencia        = trim($_POST['produto_referencia']);
	$produto_nome              = trim($_POST['produto_nome']);
	$voltagem                  = trim($_POST['voltagem']);
	$serie                     = trim($_POST['serie']);
	$data_nf                   = trim($_POST['data_nf']);
	$marca                     = trim($_POST['marca']);
	$data_ag                   = trim($_POST['data_ag']);
	$mapa_linha                = trim($_POST['mapa_linha']);
	$nota_fiscal               = trim($_POST['nota_fiscal']);
	$revenda                   = trim($_POST['revenda']);
	$revenda_nome              = trim($_POST['revenda_nome']);
	$revenda_endereco          = trim($_POST['revenda_endereco']);
	$revenda_nro               = trim($_POST['revenda_nro']);
	$revenda_cmpto             = trim($_POST['revenda_cmpto']);
	$revenda_bairro            = trim($_POST['revenda_bairro']);
	$revenda_city              = trim($_POST['revenda_city']);
	$revenda_uf                = trim($_POST['revenda_uf']);
	$revenda_fone              = trim($_POST['revenda_fone']);
	$posto                     = trim($_POST['posto']);
	$posto_nome                = trim($_POST['posto_nome']);
	$defeito_reclamado         = trim($_POST['defeito_reclamado']);
	//$reclamado                 = trim($_POST['reclamado']);
	$status                    = trim($_POST['status']);

	$transferir                = trim($_POST['transferir']);
	$chamado_interno           = trim($_POST['chamado_interno']);
	$status_interacao          = trim($_POST['status_interacao']);
	$resposta                  = trim($_POST['resposta']);
	$abre_os                   = trim($_POST['abre_os']);
	$hd_extra_defeito          = trim($_POST['hd_extra_defeito']);
	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	$abre_ordem_servico        = trim($_POST['abre_ordem_servico']);
	$hd_motivo_ligacao         = trim($_POST['hd_motivo_ligacao']);
	$recebido	 = trim($_POST['recebido']);
	$analise      	 = trim($_POST['analise']);
	$validade        = trim($_POST['validade']);
	$lote            = trim($_POST['lote']);
	$origem_adi   	 = trim($_POST['origem_adi']);
	$aco_de          = trim($_POST['aco_de']);
	$procedente	 = trim($_POST['procedente']);
	$referencia_adi	 = trim($_POST['referencia_adi']);
	$descricao_adi	 = trim($_POST['descricao_adi']);
	$qtde_adi	 = trim($_POST['qtde_adi']);
	$reposicao	 = trim($_POST['reposicao']);
	$disposicao      = trim($_POST['disposicao']);
	$descricao_analise = trim($_POST['descricao_analise']);

	if(strlen($consumidor_fone) > 0){
		$consumidor_fone = str_replace("(", "", $consumidor_fone);
		$consumidor_fone = str_replace(")", "", $consumidor_fone);
		$consumidor_fone = str_replace("-", "", $consumidor_fone);
		$consumidor_fone = str_replace(" ", "", $consumidor_fone);
	}

?>
<body <? if ($login_fabrica == 24) {?> onload="javascript: var check = document.getElementById('indicacao_posto'); indicacao_suggar(check, 'onload')"; <?}?>>

	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F7503E;font-size:10px'>
		<tr>
			<td align='center'><? echo "<font color='#FFFFFF'>$msg_erro</font>"; ?></td>
		</tr>
	</table><?php

}

$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
$res = pg_query($con, $sql);
$nome_da_fabrica = pg_fetch_result($res,0,0);?>

<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
	<tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'>
			<img src='imagens/ajuda_call.png' align='absmiddle' onClick='javascript:mostraEsconde();'>
		</td>
		<td align='center'>
			<STRONG>APRESENTAÇÃO</STRONG><BR><?php

			$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='1' AND fabrica = $login_fabrica";
			$pe  = pg_query($con, $sql);

			if (pg_num_rows($pe) > 0) {
				echo pg_fetch_result($pe, 0, 0);
			} else {

				if ($login_fabrica == 25) echo "Hbflex"; else echo "$nome_da_fabrica";?>, <?echo ucfirst($login_login);?>, <?echo saudacao();?>.<BR> O Sr.(a) já fez algum contato com a <? if ($login_fabrica==25) echo "Hbflex"; else echo "$nome_da_fabrica ";?> <?if($login_fabrica==25){ ?> por telefone ou pelo Site<?}?> ?
			<?}?>
		</td>
		<td align='right' width='150'></td>
	</tr>
</table>

<BR /><?php

#94971
if ($login_fabrica == 59 AND strlen($_GET['herdar']) > 0) {
	$id = $_GET['Id'];
	$end_herda = "?herdar=sim&Id=$id";
}

if (strlen($callcenter) > 0) {
	include_once "../class/aws/s3_config.php";

	include_once S3CLASS;

	$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
?>
	<link rel="stylesheet" type="text/css" href="fancybox/jquery.fancybox-1.3.4.css" />

	<script src='js/FancyZoom.js'></script>
	<script src='js/FancyZoomHTML.js'></script>
	<script src="../js/jquery.form.js"></script>

	<style>
		.img_anexada {
			display: inline-block;
			width: 100px;
			height: 110px;
			border: 1px solid #000000;
			margin-left: 10px;
		}

		.img_anexada img {
			width: 100px;
			height: 90px;
		}

		.img_anexada .img_check_delete {
			display: block;
			width: 100%;
			border-top: 1px solid #000000;
		}
	</style>

	<script>
		$(function () {
			var login_fabrica = "<?=$login_fabrica?>";

			var anexando = false;

			var img_contador = {
				1: "div.img_anexada[rel=1]",
				2: "div.img_anexada[rel=2]",
				3: "div.img_anexada[rel=3]",
				4: "div.img_anexada[rel=4]",
				5: "div.img_anexada[rel=5]",
				6: "div.img_anexada[rel=6]",
				7: "div.img_anexada[rel=7]",
				8: "div.img_anexada[rel=8]",
				9: "div.img_anexada[rel=9]",
				10: "div.img_anexada[rel=10]"
			};

			$("#anexarImagens").click(function () {
				if (login_fabrica == 11) {
					if ($(".img_anexada").length == 6) {
						alert("O máximo de anexos por atendimento é 6 arquivos");

						return false;
					}
				} else {
					if ($(".img_anexada").length == 3) {
						alert("O máximo de anexos por atendimento é 3 arquivos");

						return false;
					}
				}

				if (anexando === false) {
					$.each(img_contador, function (key, div) {
						if ($(div).length == 0) {
							$("#file_form").find("input[name=file_i]").val(key);
							return false;
						}
					});

					anexando = true;

					$("#anexando").show();
					$("#anexarImagens").hide();
					$("#file_form").submit();
				} else {
					alert("Espere o upload atual finalizar!");
				}
			});

			var callcenter = $("input[name=callcenter]").val();

			$("#file_form").ajaxForm({
				data:{callcenter:callcenter},
                complete: function(data) {
                	data = $.parseJSON(data.responseText);

                    if (data.erro != undefined) {
                        alert(data.erro);
                    } else {
                        var file_div = $(".img_anexada_model").clone();

                        $(file_div).find("a").attr({ "href": data.file, "target": "_blank" });
                        if (data.type != "pdf") {
                        	$(file_div).find("img").attr({ "src": data.file_mini });
                        } else {
                        	$(file_div).find("img").attr({ "src": "imagens/icone_pdf.jpg" });
                        }
                        $(file_div).find("input[name=img_anexada_nome]").val(data.file_name);
                        $(file_div).find("input[name=img_i]").val(data.i);
                        $(file_div).addClass("img_anexada").removeClass("img_anexada_model").css({ "display": "inline-block" }).attr({ "rel": data.i });

                        $(".td_img_anexadas").append(file_div);

                        $("#file_form").find("input[name=file]").val("");

                        if (!$("#deleta_img_checked").is(":visible")) {
                        	$("#deleta_img_checked").show();
                        }
                    }

                    anexando = false;

                    $("#anexando").hide();
                    $("#anexarImagens").show();
                }
            });

			$("#deleta_img_checked").click(function () {
				if ($("input[name=img_anexada_nome]:checked").length > 0) {
					if (anexando === false) {
						var files = [];

						$("input[name=img_anexada_nome]:checked").each(function () {
							files.push($(this).val());
						});

						anexando = true;

						$("#deletando").show();
						$("#deleta_img_checked").hide();
						var callcenter = $("input[name=callcenter]").val();
						console.log(callcenter);
						$.ajax({
							url: "callcenter_upload_imagens.php",
							type: "POST",
							data: { files: files, deleta_imagens: true, callcenter:callcenter },
							complete: function (data) {

								data = $.parseJSON(data.responseText);

			                    if (data.erro != undefined) {
			                        alert(data.erro);
			                    } else {
			                    	$.each(files, function (key, value) {
			                    		$("input[name=img_anexada_nome][value='"+value+"']").parents("div.img_anexada").remove();
			                    	});
			                    }

								anexando = false;

                   				$("#deletando").hide();

                   				if ($(".img_anexada").length > 0) {
									$("#deleta_img_checked").show();
								}
							}
						});
					} else {
						alert("Espere o processo atual finalizar!");
					}
				}
			});
		});
	</script>

	<br />

	<table style="margin: 0 auto;" >
		<tr>
			<td style="color: #FF0000; font-size: 14px;">
			<?php if ($login_fabrica == 11) {
				echo "* Quantidade máxima de 6 anexos por atendimento";
			} else {
				echo "* Quantidade máxima de 3 anexos por atendimento";
			}
			?>
			<br />
			* Tamanho máximo do arquivo de 2MB
			</td>
		</tr>
		<tr>
			<td>
				<div style="text-align: center; height: 32px;">
					<form id="file_form" name="file_form" action="callcenter_upload_imagens.php" method="post" enctype="multipart/form-data" >
						<input type="file" name="file" value="" />
						<input type="hidden" name="file_hd_chamado" value="<?=$callcenter?>" />
						<input type="hidden" name="anexar_imagem" value="true" />
						<input type="hidden" name="file_i" value="" />
						<button type="button" id="anexarImagens" style="cursor: pointer;" >Anexar arquivo selecionado</button>
						<img class="loadImg" id="anexando" style="vertical-align: -14px; display: none;" src="imagens/loading_indicator_big.gif" />
					</form>
				</div>
			</td>
		</tr>
		<tr>
			<td style="text-align: center;" class="td_img_anexadas">
				<br />

				<div class="img_anexada_model" style="display: none;">
					<a href="" ><img src="" /></a>

					<br />

					<span class="img_check_delete" >
						<input type="checkbox" name="img_anexada_nome" value="" />
						<input type="hidden" name="img_i" value="" />
					</span>
				</div>

				<?php
				$s3->getObjectList("{$callcenter}-", false);

				if (count($s3->files) > 0) {
					$file_links = $s3->getLinkList($s3->files);

					foreach ($s3->files as $key => $file) {
						$img_i = preg_replace("/.*.\//", "", $file);
						$img_i = preg_replace("/\..*./", "", $img_i);
						$img_i = explode("-", $img_i);
						$img_i = $img_i[1];

						$file_name = preg_replace("/.*.\//", "", $file);

						$type  = trim(strtolower(preg_replace("/.+\./", "", $file_name)));

						if ($type != "pdf") {
							$file_thumb = $s3->getLink("thumb_".$file_name);

							if (!strlen($file_thumb)) {
								$file_thumb = $file_name;
							}
						} else {
							$file_thumb = "imagens/icone_pdf.jpg";
						}

						?>
						<div class="img_anexada" rel="<?=$img_i?>">
							<a href="<?=$file_links[$key]?>" target="_blank" ><img src="<?=$file_thumb?>" /></a>

							<br />

							<span class="img_check_delete" >
								<input type="checkbox" name="img_anexada_nome" value="<?=$file_name?>" />
								<input type="hidden" name="img_i" value="<?=$img_i?>" />
							</span>
						</div>
					<?php
					}
				}
				?>
			</td>
		</tr>
		<tr>
			<td style="text-align: center;">
				<br />
				<img id="deletando" class="loadImg" src="imagens/loading_indicator_big.gif" style="display: none;" />
				<button type="button" id="deleta_img_checked" style="display: <?=(count($s3->files) > 0) ? 'inline' : 'none'?>;" >Deletar os arquivos selecionados</button>
			</td>
		</tr>
	</table>

	<br />
<?php
}
?>

<form name='frm_callcenter' id='frm_callcenter' method='post' action='<?$PHP_SELF?><?=$end_herda?>'>

<input name="callcenter" class="input" type="hidden" value='<?echo $callcenter;?>' />
<input name="protocolo_id" id='protocolo_id' class="input" type="hidden" value='<?=$protocolo;?>' />
<table width="100%" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:12px'><?php
if ($login_fabrica == 5 or $login_fabrica == 24) { ?>
	<tr>
		<td align='right' style='font-size: 14px; font-weight: bold; font-family: arial; color:red'>INDICAÇÃO DE POSTO
			<input type="checkbox" name="indicacao_posto" id="indicacao_posto" <? if($indicacao_posto=="t") echo "checked";?> value="t" <?if ($login_fabrica == 24){ ?>onChange="indicacao_suggar(this, 'onchange');" <?} else {?> onChange="indicacao(this);" <?}?>>
		</td>
	</tr><?php
}

/*if (in_array($login_fabrica,array(11,80,81,114)) and strlen($callcenter) > 0) { ?>
	<tr>
		<td align='right' style='font-size: 14px; font-weight: bold; font-family: arial; color:red'>

			<a href='callcenter_upload_imagens.php?callcenter=<?=$callcenter;?>'>ANEXAR IMAGEM</a><?php

			$limite_anexos = 10;

			$caminho = 'callcenter_digitalizados/';

			$contador = 1;

			for ($i = 0; $i < $limite_anexos; $i++) {

				if ($i == 0)
					$arquivo = $callcenter . ".jpg";
				else
					$arquivo = $callcenter . "-$i.jpg";

				$arquivo_destino = $caminho.$arquivo;

				if (file_exists($arquivo_destino)) {

					if ($i == 0)
						echo "  <span style='font-size:11px;'><a href=\"javascript://\" OnClick=\"abrir_anexo('$arquivo')\">Anexo</a></span>";
					else
						echo "  - <span style='font-size:11px;'><a href=\"javascript://\" OnClick=\"abrir_anexo('$arquivo')\">Anexo ".($i)."</a></span>";
				}

			}?>
		</td>
	</tr><?php
}*/
?>

<tr>
	<td align='left'>

		<table width="100%" border='0'>

			<tr>
				<td align='left'><strong>Cadastro de Atendimento</strong></td><?php
				if (strlen($callcenter)>0 AND $login_fabrica == 3) {?>
					<td nowrap>Tipo de registro: <strong><? echo $tipo_registro; ?></strong></td><?php
				}

				if (strlen ($admin_login) > 0) {
					echo "<td><b>Aberto por: </b> $admin_login - $admin_nome_completo <b> Em: </b> $data_abertura_callcenter </td>";
				}

				if (strlen($atendimento_callcenter) > 0 AND $login_fabrica == 35) {?>
					<td nowrap>Atendimento(Solutiva): <strong><? echo $atendimento_callcenter; ?></strong></td><?php
				}?>

				<?php
					if($login_fabrica == 74 AND $status_interacao != "Resolvido" AND (strtotime($data_hd_chamado.'+ 30 days') < strtotime(date('Y-m-d')))){
				?>
					<td align='left' width='500'><font color='#FF0000'><b>Atendimento aberto a mais de 30 dias</b></font></td>
				<?php
					}
				?>

				<td align='right'>
					<div id='protocolo'>
						<strong><?php
						if (strlen($callcenter) > 0) {

							if (strlen($protocolo_cliente) > 0 && $login_fabrica == 90)
								echo "Número IBBL: <font color='#CC0033'>" . $protocolo_cliente . '</font> - ';

							echo "n <font color='#CC0033'>$callcenter</font>";

						} else {
							echo "<font color='#CC0033'><a href='#' onclick='geraProtocolo()'>GERAR PROTOCOLO</font></a>";
						}?>
						</strong>
					</div>
				</td>
			</tr>

		</table><?php

		if (strlen($callcenter) == 0) {?>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
				<tr>
					<td colspan="2" style='border: 1px solid #DD0000; background-color: #FFDDCC; padding: 5px;' id="aviso_localizar_nome">
					<b>BUSCA POR NOME:</b> utilizar sempre letras MAIUSCULAS E SEM ACENTOS. Clique no icone "?" a seguir para INSTRUÇÕES <img align="absmiddle" src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'>
					</td>
				</tr>
				<tr>
					<td align='left' width='68'><strong>Localizar:</strong></td>
					<td>
					<table><?php
					//HD 163220 - Abrir popup para todos os tipos de pesquisas na fábrica
					//			- Retirei o if ($login_fabrica == 3), pois a Britania não utiliza esta tela e a busca
					//			  está sendo padronizada
					$localizar_opcoes = array("cpf", "nome", "atendimento", "os", "serie", "cep", "telefone");
					$localizar_labels = array("CPF/CNPJ", "Nome", "Atendimento", "OS", "nº de Série", "CEP", "Telefone");
					$localizar_posicao_retorno = array(11, 1, 0, 16, 17, 6, 8);
					$localizar_links = array();
					$localizar_autocomplete = array();
					//HD311031
					$cor_font = $login_fabrica == 30 ? 'font-size:10px;' : '';

					for ($l = 0; $l < count($localizar_opcoes); $l++) {

						if ($l % 3 == 0) {
							$localizar_links[$l] = "<tr>";
						}

						if ($localizar_opcoes[$l] == "nome") {
							$onkeyup = "somenteMaiusculaSemAcento(this);";
						} else {
							$onkeyup = "";
						}

						if ($localizar_opcoes[$l] == "telefone") {
							$class = "class='input_req2 telefone'";
						} else {
							$class = "class='input_req2'";
						}

						$localizar_links[$l] .= "<td align=right style='padding-left:10px;$cor_font'>" . $localizar_labels[$l] . ": </td><td><input type=text name='localizar_" . $localizar_opcoes[$l] . "' id='localizar_" . $localizar_opcoes[$l] . "' " . ($localizar_opcoes[$l] != 'cep' ? "onkeyup='retiraAcentos(this); $onkeyup'" : '') . " ".$class."> <img src='imagens/lupa.png' title='Buscar' onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById(\"localizar_" . $localizar_opcoes[$l] . "\").value, \"" . $localizar_opcoes[$l] . "\")' style='cursor:pointer;'></td>";

						if ($l % 3 == 2) {
							$localizar_links[$l] .= "</tr>";
						}

						if ($localizar_opcoes[$l] != "nome") {

							$localizar_autocomplete[$l] = '
							$("#localizar_' . $localizar_opcoes[$l] . '").autocomplete("pesquisa_consumidor_callcenter_nv.php?tipo_pesquisa=' . $localizar_opcoes[$l] . '&ajax=sim&engana="+engana, {
								minChars: 6,
								delay: 700,
								width: 300,
								max: 30,
								matchContains: true,
								formatItem: formatLocalizar,
								formatResult: function(row) {
								return $("#localizar_' . $localizar_opcoes[$l] . '").val();
							}
							});

							$("#localizar_' . $localizar_opcoes[$l] . '").result(function(event, data, formatted) {
								preencheConsumidorAutocomplete(event, data, formatted);
								fnc_pesquisa_consumidor_callcenter(data[' . $localizar_posicao_retorno[$l] . '], "' . $localizar_opcoes[$l] . '", true);
							});
							';
						}
					}
					// $novo_consumidor = "<input type=button value='Novo Consumidor' onclick=\"javascript:localizarConsumidor('localizar','novo')\" class=input_req2 style='background-color:#DDDDDD'>";

					// switch ($l % 3) {
					// 	case 0:
					// 		$localizar_links[$l] .= "<td colspan=2 align=right>$novo_consumidor</td><td></td><td></td></tr>";
					// 	break;

					// 	case 1:
					// 		$localizar_links[$l] .= "<td colspan=2 align=right>$novo_consumidor</td><td></td></tr>";
					// 	break;

					// 	case 2:
					// 		$localizar_links[$l] .= "<td colspan=2 align=right>$novo_consumidor</td></tr>";
					// 	break;
					// }

					$localizar_links = implode("", $localizar_links);
					echo $localizar_links;

					$localizar_autocomplete = implode("", $localizar_autocomplete);
					echo "<script language='javascript'>
							hora = new Date();
							engana = hora.getTime()
							$localizar_autocomplete
						</script> "; ?>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					Digite pelo menos 6 caracteres em um dos campos acima e clique na lupa para localizar e recuperar na tela os dados do atendimento.
				</td>
			</tr>
		</table>
	<?  } ?>
	</td>
</tr>

<tr>
	<td>

	<div id='div_consumidor' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		<input type='hidden' name='atendimento_callcenter' value='<?=$atendimento_callcenter?>'>
		<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px' id='tabela_consumidor'>
		<!--HD36903--><?PHP
			if ($login_fabrica == 2 || $login_fabrica == 10 || $login_fabrica == 91) {?>
		<tr>
			<td colspan='6'  align='left'>
				<table border='0' cellpadding='3' cellspacing='0' width="50%">
					<tr>
						<td align='left'>
							<b>Tipo de atendimento:</b>
						</td>
						<td align='left'>
							<label for="consumidor_revenda_c">Consumidor</label>
							<input type='radio' id="consumidor_revenda_c" name='consumidor_revenda' value='C' <?PHP if ($consumidor_revenda == 'C' or $consumidor_revenda == '') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							<label for="consumidor_revenda_r">Revenda</label>
							<input type='radio' id="consumidor_revenda_r" name='consumidor_revenda' value='R' <?PHP if ($consumidor_revenda == 'R') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							<label for="consumidor_revenda_a">Assistência Técnica</label>
							<input type='radio' id="consumidor_revenda_a" name='consumidor_revenda' value='A' <?PHP if ($consumidor_revenda == 'A') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<?php if ( $login_fabrica == 10 ): ?>
						<td align='left'>
							<label for="consumidor_revenda_f">Fábrica</label>
							<input type='radio' id="consumidor_revenda_f" name='consumidor_revenda' value='F' <?PHP if ($consumidor_revenda == 'F') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<?php endif; ?>
						<?php if ( $login_fabrica == 91 ){ ?>
						<td align='left'>
							<label for="consumidor_revenda_f">Equipe Comercial</label>
							<input type='radio' id="consumidor_revenda_e" name='consumidor_revenda' value='E' <?PHP if ($consumidor_revenda == 'E') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<?php } ?>
					<tr>
				</table>
			</td>
		</tr><?php
		} else if(in_array($login_fabrica,array(7,24,30,35,52,59,85,138,145))) {
			if ($login_fabrica == 30){
?>
			<tr>
				<td colspan="6" style='border: 1px solid #DD0000; background-color: #FFDDCC; padding: 5px;' id="aviso_localizar_nome">
				OS CAMPOS EM VERMELHO SÃO DE PREENCHIMENTO OBRIGATÓRIO
				</td>
			</tr>
<?php
            }
        ?>
		<tr>
			<td colspan='6'  align='left'>
				<table border='0' cellpadding='3' cellspacing='0' width="50%">
					<tr>
						<td align='left'>
							<b>Tipo Consumidor:</b>
						</td>
						<?php


            if($login_fabrica == 7 ){
                $checkedcpf = "";
                $checkedcnpj = "";

                if($consumidor_cfp != ""){

                    $checkedcpf = "CHECKED";
                    $checkedcnpj = "";
                }

                if($consumidor_cnpj != ""){
                    $checkedcpf = "";
                    $checkedcnpj = "CHECKED";
                }

                if($consumidor_cfp == "" and $consumidor_cnpj == ""){
                    $checkedcpf = "";
                    $checkedcnpj = "CHECKED";
                }
            }
?>

						<td align='left'>
							CPF
							<input type='radio' name='consumidor_cpf_cnpj' id='consumidor_cfp' value='C'
<?PHP
            if (strlen($consumidor_cpf) == 14 or strlen($consumidor_cpf) == 0) {
                if($login_fabrica == 7){
                    echo $checkedcpf;
                }else{
                    echo "CHECKED";
                }
            }else{
                if($login_fabrica == 7){
                    echo $checkedcpf;
                }
            }

            $disable_cpf_cnpj = true;

            if (!empty($_GET['os']) and $login_fabrica == '85') {
                $chkOs = (int) $_GET['os'];
                $qryChkOs = pg_query($con, "SELECT * FROM tbl_hd_chamado_extra WHERE hd_chamado = $callcenter AND os = $chkOs");

                if (pg_num_rows($qryChkOs) > 0) {
                    $qryChkHDItem = pg_query($con, "SELECT hd_chamado_item FROM tbl_hd_chamado_item WHERE hd_chamado = $callcenter");

                    if (pg_num_rows($qryChkHDItem) == 0) {
                        $disable_cpf_cnpj = false;
                    }
                }
            }

            if((strlen($callcenter) > 0) && ($login_fabrica <> 7) and (true === $disable_cpf_cnpj)) {
                echo " disabled";
            }
?>
                                onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							CNPJ
							<input type='radio' name='consumidor_cpf_cnpj'id='consumidor_cnpj' value='R'
<?PHP
            if (strlen($consumidor_cpf) == 18) {
                if($login_fabrica == 7){
                    echo $checkedcnpj;
                }else{
                    echo "CHECKED";
                }

            }else{
                if($login_fabrica == 7){
                    echo $checkedcnpj;
                }
            }
            if((strlen($callcenter) > 0) && ($login_fabrica <> 7) and (true === $disable_cpf_cnpj)) {
                echo " disabled";
            }
?>
                                onclick="fnc_tipo_atendimento(this)">
						</td>
					</tr>
				</table>
			</td>
		</tr>
<?
        }
        if ($login_fabrica == 52) {
?>
		<tr>
			<td align='left'><strong>Cliente Fricon:</strong>
				<input type='hidden' name='cliente_admin' id='cliente_admin' value="<? echo $cliente_admin; ?>">
			</td>
			<td align='left'>

				<input type='hidden' name='cliente_nome_admin_anterior' value="<? echo trim($cliente_nome_admin); ?>">
				<input name="cliente_nome_admin" id="cliente_nome_admin" value='<?echo trim($cliente_nome_admin) ;?>' class='input_req' type="text" size="35" maxlength="50">
			</td>
		<tr>
<?
		}
?>
        <tr>
<?
        if ($login_fabrica == 30) {
?>
                <td align='left'><strong><acronym title='Campo Obrigatório'><font color="#AA0000">Nome:</font></acronym></strong></td>
<?
        } else {
            if($login_fabrica == 24){
?>
                <td align='left'><strong>Nome NF:</strong></td>
<?php
            }else{
                if($login_fabrica != 85){


?>
                <td align='left'><strong>Nome:</strong></td>
<?php
                }else{
?>
                <td align='left'><strong><span id='label_nome'>
<?php
                    if((strlen($consumidor_cpf) != 18 and strlen($callcenter) > 0) or strlen($callcenter) == 0) {
                        echo "Nome:";
                    }elseif(strlen($consumidor_cpf) == 18 and strlen($callcenter) > 0){
                        echo "Razão Social:";
                    }
?>

                </span></strong></td>
<?
                }
            }
        }
?>

                <td align='left'>
            <? if ($login_fabrica == 30) { ?>
                <input type="hidden" name="consumidor_nome_anterior" value="<?php echo $consumidor_nome; ?>" />
                <acronym title='Campo Obrigatório'><input name="consumidor_nome" id="consumidor_nome" maxlength='50' value='<?php echo $consumidor_nome ;?>'
                 <?echo ($login_fabrica == 24) ? 'class="input_req2"':'class="input_req"';?> type="text" size="35" maxlength="50"
                    onkeyup="somenteMaiusculaSemAcento(this);"
                 <? if($login_fabrica==11){?> onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
                 <img style="cursor: pointer;" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("consumidor_nome").value, "nome")'
                      title="Buscar" src="imagens/lupa.png"/></acronym>
            <? }else{ ?>
                <input type="hidden" name="consumidor_nome_anterior" value="<?php echo $consumidor_nome; ?>" />
                <input name="consumidor_nome" id="consumidor_nome" maxlength='50' value='<?php echo $consumidor_nome ;?>'
                 <?echo ($login_fabrica == 24) ? 'class="input_req2"':'class="input_req"';?> type="text" size="35" maxlength="50"
                    onkeyup="somenteMaiusculaSemAcento(this);"
                 <? if($login_fabrica==11){?> onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
                 <img style="cursor: pointer;" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("consumidor_nome").value, "nome")'
                      title="Buscar" src="imagens/lupa.png"/>
            <? } ?>
            </td>

            <td align='left'><strong><span id='label_cpf'>
            <?

            if($login_fabrica <> 7){
	            if((strlen($consumidor_cpf) != 18 and strlen($callcenter) > 0) or strlen($callcenter) == 0) {
	                echo "CPF:";
	                $limite ='14';
	            }elseif(strlen($consumidor_cpf) == 18 and strlen($callcenter) > 0){
	                echo "CNPJ:";
	                $limite = "18";
	            }
            }else{
            	echo "CNPJ";
            	$limite = "18";
            }


            $campos_obrig = $login_fabrica == 30 ? '<acronym title="Campo Obrigatório"><font color="#AA0000">' : NULL;
            $fx_cmp_obg = !is_null($campos_obrig) ? '</font></acronym>' : '';
            ?>
            </span></strong></td>
                <?/* 08/04/2010 MLG - HD 209670 - Retirado código para bloquear esta parte do formulário na Lenoxx */?>
            <td align='left'>

            	<?php
            	if ($_POST['consumidor_cpf_anterior'] and $msg_erro){
            		$consumidor_cpf_anterior = $_POST['consumidor_cpf_anterior'];
            	}else{
            		$consumidor_cpf_anterior = $consumidor_cpf;
            	}
            	?>
                <input type="hidden" name="consumidor_cpf_anterior" value="<?php echo trim($consumidor_cpf_anterior); ?>" />
                <input type="text" name="consumidor_cpf" id="cpf" value='<? echo trim($consumidor_cpf) ;?>'
                      class="input_req" size="15" maxlength="<?=$limite?>"
                 <?if($login_fabrica != 11){?>onkeypress="return txtBoxFormat(this.form, this.name, '999.999.999-99', event);"<?}?>
                 <?if($login_fabrica == 11){?>onkeyup="javascript:retiraAcentos(this);"<?}?>
                 >
                <img style="cursor: pointer;" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("cpf").value, "cpf")' title="Buscar" src="imagens/lupa.png"/>
                <input name="cliente" id="cliente" value='<? echo $cliente ;?>' type="hidden">
            </td>
<?php
            if($login_fabrica != 85){
?>
            <td align='left'><strong>RG:</strong></td>
            <td align='left'>
                <input type="hidden" name="consumidor_rg_anterior" value="<?php echo $consumidor_rg; ?>" />
                <input name="consumidor_rg" id="consumidor_rg" value='<? echo $consumidor_rg ;?>'  class="input_req" type="text" size="14" maxlength="14">
            </td>
<?php
            }else{
?>
            <td colspan="2">&nbsp;</td>
<?php
            }
?>
        </tr>

		<tr>
			<?php $endereco_readonly = '' ; //08/04/2010 MLG - HD 209670 - ( $login_fabrica == 11 && isset($callcenter) && $callcenter > 0 && $status_interacao != 'Aberto' ) ? 'readonly' : ?>
			<td align='left'><strong>E-mail:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_email_anterior" value="<?php echo $consumidor_email; ?>" />
				<input name="consumidor_email" id="consumidor_email" value='<? echo $consumidor_email ?>' class="

				" type="text" size="40" maxlength="50" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'>
				<strong>
					<? echo ($login_fabrica==59) ? "Telefone Residêncial:" : "<strong>".$campos_obrig."Telefone:".$fx_cmp_obg."</strong>"; ?>
				</strong>
			</td>
			<td align='left'>
				<input type="hidden" name="consumidor_fone_anterior" value="<?php echo $consumidor_fone; ?>" />
				<input name="consumidor_fone" id="telefone" value='<? echo $consumidor_fone ;?>'  <? if ($login_fabrica == 24) { ?>class="input_req2 telefone"<? } else { ?> class="input_req telefone" <? } ?>  type="text" size="15" maxlength="15" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>CEP:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_cep_anterior" value="<?php echo $consumidor_cep; ?>" />
				<input name="consumidor_cep" id="cep" value="<? echo $consumidor_cep ;?>"  class="input_req" type="text" size="14" maxlength="9" onblur="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado);" onchange="atualizaQuadroMapas();" onkeypress="return txtBoxFormat(this.form, this.name, '99999-999', event);" <?php echo $endereco_readonly; ?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong><?=$campos_obrig;?>Endereço:<?=$fx_cmp_obg;?></strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_endereco_anterior" value="<?php echo $consumidor_endereco; ?>" />
				<input name="consumidor_endereco" id='consumidor_endereco' value='<? echo $consumidor_endereco ;?>' class="input_req" type="text" size="40" maxlength="60" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong><?=$campos_obrig;?>Número:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_numero_anterior" value="<?php echo $consumidor_numero; ?>" />
				<input name="consumidor_numero" id='consumidor_numero' value='<? echo $consumidor_numero ;?>' class="input_req" type="text" size="15" maxlength="15" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>Complem.</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_complemento_anterior" value="<?php echo $consumidor_complemento; ?>" />
				<input name="consumidor_complemento" id='consumidor_complemento' value='<? echo $consumidor_complemento ;?>' class="input_req" type="text" size="15" maxlength="20" <?php echo $endereco_readonly; ?> >
			</td>
		</tr>
		<?php if ($login_fabrica == 52): ?>
			<tr>
				<td align="left"><strong>Ponto de Referência</strong></td>
				<td align="left">
					<input type="hidden" name="ponto_referencia_anterior" value="<?=$ponto_referencia?>" >
					<input type="text" name="ponto_referencia" id="ponto_referencia" class="input_req" value="<?=$ponto_referencia?>" size="40" maxleght="255" >
				</td>
			</tr>
		<?php endif ?>
		<tr>
			<td align='left'><strong><?=$campos_obrig;?>Bairro:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_bairro_anterior" value="<?php echo $consumidor_bairro; ?>" />
				<input name="consumidor_bairro" id='consumidor_bairro' value='<? echo $consumidor_bairro ;?>' <?if ($login_fabrica <> 24) { ?>class="input_req" <? }?>type="text" class="input_req" size="40" maxlength="50" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'>
				<strong>
					<? if ($login_fabrica == 30) { ?>
					<acronym title='Campo Obrigatório'><font color="#AA0000" style="bold">
					<? } ?>
					Estado:
					<? if ($login_fabrica == 30) { ?>
					</font><acronym>
					<? } ?>
				</strong>
			</td>
			<td align='left'>
				<input type="hidden" name="consumidor_estado_anterior" value="<?php echo $consumidor_estado; ?>" />
				<? if ($login_fabrica == 30) { ?>
				<acronym title='Campo Obrigatório'>
				<? } ?>
				<select name="consumidor_estado" id='consumidor_estado' class='frm' >
					<? $ArrayEstados = array('','AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);
					for ($i=0; $i<=27; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}?>
				</select>
				<? if ($login_fabrica == 30) { ?>
				</acronym>
				<? } ?>
			</td>
			<td align='left'>
				<strong>
					<? if ($login_fabrica == 30) { ?>
					<acronym title='Campo Obrigatório'><font color="#AA0000">
					<? } ?>
					Cidade:
					<? if ($login_fabrica == 30) { ?>
					</font></acronym>
					<? } ?>
				</strong>
			</td>
			<td align='left'>
				<input type="hidden" name="consumidor_cidade_anterior" value="<?php echo $consumidor_cidade; ?>" />
				<? if ($login_fabrica == 30) { ?>
				<acronym title='Campo Obrigatório'>
				<? } ?>
					<input name="consumidor_cidade" id='consumidor_cidade' value="<? echo $consumidor_cidade ;?>" <?=(!strlen($consumidor_estado)) ? "READONLY" : ""?>  <?if ($login_fabrica == 24) {?>class="input_req2"<?} else {?> class="input_req" <?}?> type="text" size="18" maxlength="16" <?php echo $endereco_readonly; ?> >
				<? if ($login_fabrica == 30) { ?>
				</acronym>
				<? } ?>
				<input name="cidade"  class="input_req" value='<? echo $cidade ;?>' type="hidden">
			</td>
		</tr>
		<tr>
			<?if(!in_array($login_fabrica, array(3,24,85))) { // HD 48900 ?>
			<td colspan='2' align='left'>
				<strong>Melhor horário p/ contato: </strong>
				<input name="hora_ligacao" id='hora_ligacao' class="input_req" value='<?echo $hora_ligacao ;?>' type="text" maxlength='5' size='7'>
			</td>
			<? } ?>
            <?php if(in_array($login_fabrica, array(24))) {?>
               <td align='left'><strong>Nome Contato:</strong></td>
               <td align='left'>
                    <input type="hidden" name="contato_nome_anterior" value="<?php echo $contato_nome; ?>" />
                    <input name="contato_nome" id="contato_nome" maxlength='50' size="40" value='<?php echo $contato_nome ;?>' class="input_req" type="text" size="35" onkeyup="somenteMaiusculaSemAcento(this);" />
               </td>
            <?php }?>

			<td align='left'><strong>Origem:</strong></td>
			<td align='left'>
				<select name='origem' id='origem' >
				<option value=''>Escolha</option>
				<option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>>Telefone</option>
				<option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>>E-mail</option>
				<?if ($login_fabrica == 2){?>
					<option value='0800' <?PHP if ($origem == '0800'){ echo "Selected";}?>>Atendimento 0800</option>
					<option value='9166' <?PHP if ($origem == '9166') { echo "Selected";}?>>Atendimento 9166</option>
					<option value='Outros' <?PHP if ($origem == 'Outros') { echo "Selected";}?>>Outros</option>
				<?}?>
					<!-- HD 2088425 - Pinsard -->
               			 <?php if( $login_fabrica == 59 ){?>
                			<option value='Chat' <?PHP if ($origem == 'Chat') { echo "Selected";}?>>Chat</option>
                			<option value='Facebook' <?PHP if ($origem == 'Facebook') { echo "Selected";}?>>Facebook</option>
                			<option value='LASA' <?PHP if ($origem == 'LASA') { echo "Selected";}?>>LASA</option>
                			<option value='NAJ' <?PHP if ($origem == 'NAJ') { echo "Selected";}?>>NAJ</option>
                			<option value='ReclameAqui' <?PHP if ($origem == 'ReclameAqui') { echo "Selected";}?>>Reclame Aqui</option>
                			<option value='Relacionamento' <?PHP if ($origem == 'Relacionamento') { echo "Selected";}?>>Relacionamento</option>
                		<?php } ?>
				<?if ($login_fabrica == 15){?>
					<option value='revenda' <?PHP if ($origem == 'revenda'){ echo "Selected";}?>>Revenda</option>
					<option value='reclame' <?PHP if ($origem == 'reclame') { echo "Selected";}?>>Reclame Aqui</option>
					<option value='redes' <?PHP if ($origem == 'redes') { echo "Selected";}?>>Redes Sociais</option>
				<?}?>
				</select>
			</td>
			<!--HD36903-->
			<?PHP if ($login_fabrica != 2 && $login_fabrica != 10 && $login_fabrica != 91) {?>
			<td align='left'><strong>Tipo:</strong></td>
			<td align='left'>
				<select name="consumidor_revenda" id='consumidor_revenda' >
				<option value=''>Escolha</option>
				<option value='C' <? if($consumidor_revenda == "C") echo "Selected" ;?>>Consumidor</option>
				<option value='R' <? if($consumidor_revenda == "R") echo "Selected" ;?>>Revenda</option>
				<? if($login_fabrica ==86) { // HD 48900?>
				<option value='T' <? if($consumidor_revenda == "T") echo "Selected" ;?>>Representante</option>
				<option value='N' <? if($consumidor_revenda == "N") echo "Selected" ;?>>Consultor</option>
				<option value='P' <? if($consumidor_revenda == "P") echo "Selected" ;?>>PA</option>
				<? } ?>
				</select>

			</td>

		</tr>
		<? if($login_fabrica == 86) { ?>
			<tr class='hd_motivo_ligacao'>
				<td colspan='4'>&nbsp;</td>
				<td align='left'><strong>Motivo:</strong></td>
				<td align='left'>
					<select name='hd_motivo_ligacao' id='hd_motivo_ligacao'></select>
				</td>
			</tr>
			<? } ?>
		<? if($login_fabrica == 74) { ?>
			<tr>
				<td align='left'><strong>Classe do atendimento:</strong></td>
				<td align='left'>
					<select name='hd_motivo_ligacao' id='hd_motivo_ligacao'>
						<option value=''>Escolha</option><?php
						$sqlLigacao = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica AND ativo IS TRUE $disabled; ";

						$resLigacao = pg_query($con,$sqlLigacao);
							for ($i = 0; $i < pg_num_rows($resLigacao); $i++) {
								$hd_motivo_ligacao_aux = pg_result($resLigacao,$i,'hd_motivo_ligacao');
								$motivo_ligacao    = pg_result($resLigacao,$i,'descricao');
								echo " <option value='".$hd_motivo_ligacao_aux."' ".($hd_motivo_ligacao_aux == $hd_motivo_ligacao ? "selected='selected'" : '').">$motivo_ligacao</option>";

							}?>

					</select>
				</td>
			</tr>
			<? } ?>
			<?PHP }?>
		<?php if ( in_array( $login_fabrica, $fab_usa_tipo_cons) ) : //HD 317864 ?>
			<tr>
				<td colspan="2">
					<fieldset style="width:230px; text-align:left;">
						<legend style="font-weight:bold;">Tipo de Consumidor</legend>
						<?php

							if (!isset($_POST['tipo_consumidor']) && !empty($callcenter) ) {

								$sql = "SELECT tipo_consumidor FROM tbl_hd_chamado_extra WHERE hd_chamado = $callcenter ";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res)) {
									$checked = pg_result ($res,0,0);
									$CA = $checked == 'CA' ? 'checked' : '';
									$CF = $checked == 'CF' ? 'checked' : '';
								}
							}

						?>
						<input type="radio" name="tipo_consumidor" <?=$CF?> value="CF" id="CF" <? echo $_POST['tipo_consumidor'] == 'CF' ? 'checked' : ''; ?> /><label for="CF">&nbsp;Consumidor Final</label>&nbsp;&nbsp;
						<input type="radio" name="tipo_consumidor" <?=$CA?> value="CA" id="CA"  <? echo $_POST['tipo_consumidor'] == 'CA' ? 'checked' : ''; ?> /><label for="CA">&nbsp;Cabeleireiro</label>
					</fieldset>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<?if(!in_array($login_fabrica,array(3,85))) { // HD 48900 ?>
			<td colspan='2' align='left'>
				<input type="checkbox" name="receber_informacoes" id="receber_informacoes" <? if($receber_informacoes=="t") echo "checked";?> value='t'>
				<strong>Aceita receber informações sobre nossos produtos? </strong> <br>
				<a  href="javascript:fnc_pesquisa_os (document.frm_callcenter.consumidor_cpf, 'os')">Clique aqui para ver todas as OSs cadastradas com CPF deste consumidor</a>
				<?php

				if($callcenter != "" and $telecontrol_distrib){
					$sqlCallcenter = "SELECT pedido from tbl_hd_chamado_extra where hd_chamado = $callcenter";
					$resCallcenter = pg_query($con,$sqlCallcenter);
					?>
					</br>
					<?php
					if(pg_result($resCallcenter,0,pedido) == ""){
						?>
						<a  target="_BLANK" href="pedido_cadastro.php?callcenter=<?php echo $callcenter ?>">Criar um pedido para o chamado <?php  echo $callcenter ?></a>
						<?php
					}else{
						?>
						<a  target="_BLANK" href="pedido_cadastro.php?pedido=<?php echo pg_result($resCallcenter,0,pedido) ?>">Abrir pedido <?php  echo pg_result($resCallcenter,0,pedido) ?></a>
						<?php
					}
				}

				?>
			</td>
			<? }

			//HD 201434 - Retirado o Telefone 2 para Gama Italy, pois é o mesmo campo de Telefone comercial
			?>

			<td align='left' colspan='1'><strong>Telefone Comercial:</strong></td>
			<td align='left' colspan='1'>
				<input type="hidden" name="consumidor_fone2_anterior" value="<?php echo $consumidor_fone2; ?>" />
				<input name="consumidor_fone2" id="telefone2" value='<?php echo $consumidor_fone2 ;?>'  class="input telefone"  type="text" size="15" maxlength="15" >
			</td>

			<td align='left' colspan='1'>
					<strong>Telefone Celular:</strong>
					<?php echo ($login_fabrica == 52) ? " <br /> <strong>Operadora / Celular</strong>" : ""; ?>
			</td>
			<td align='left' colspan='1'>
				<input type="hidden" name="consumidor_fone3_anterior" value="<?php echo $consumidor_fone3; ?>" />
				<input name="consumidor_fone3" id="telefone3" value='<?php echo $consumidor_fone3 ;?>'  class="input telefone"  type="text" size="15" maxlength="15" >


				<?php
					if($login_fabrica == 52){
						?>
						<br />
						<input type="hidden" name="operadora_celular" value="<?=$operadora_celular?>" />
						<input type="text" name="operadora_celular" id="operadora_celular" class="input_req" value="<?=$operadora_celular?>" size="15" maxleght="255"onkeyup="somenteMaiusculaSemAcento(this);" />
						<?php
					}
				?>

			</td>

			<? if (in_array($login_fabrica, array(11,15,24,81,114))) { // HD 14549, //HD 907550 +114-Cobimex?>
			<td align='left' width=50><strong>OS:</strong></td>
			<td align='left' width=150>
			<input name="os" id="os" class="input"  value='<?echo ($_POST["os"]) ? $os : $sua_os ;?>' onblur="if ($(this).length > 0) { $('input[name=os][id!=os]').val($(this).val()); } else { $('input[name=os][id!=os]').val($(this).val('')); }" > <img style="cursor: pointer;" align="absmiddle" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("os").value, "os")' title="Buscar" src="imagens/lupa.png"  />
			</td>
			<? } ?>
			<?  if ($login_fabrica == 24 AND strlen($familia) > 0 AND strlen($callcenter) > 0) { // HD 98922?>
			<td align='right' colspan='1'><br /><a href="envio_email_callcenter.php?callcenter=<?=$callcenter?>&KeepThis=true&TB_iframe=true&height=500&width=700" class='thickbox' title='Enviar E-mail para consumidor'>Clique aqui para enviar E-mail para <?=$consumidor_email?></a>
			</td>
			<? } ?>
		</tr>

		<?php
		if ($login_fabrica == 24) {
		?>
			<tr>
				<td colspan="2" >
					<strong>Ligação Agendada:</strong>
					&nbsp;
					<input type="text" style="width: 90px;" name="ligacao_agendada" id="ligacao_agendada" value="<?=$ligacao_agendada?>" />
				</td>
			</tr>
		<?php
		}

		if ($login_fabrica == 85) {
		?>
			<tr id="nome_fantasia" style="display: <?=($consumidor_cpf_cnpj == "R") ? 'table-row' : 'none'?>;" >
				<td colspan="2" >
					<strong>Nome Fantasia:</strong>
					&nbsp;
					<input type="text" name="nome_fantasia" value="<?=$nome_fantasia?>" />
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" >
					<strong>Atendimento AMBEV ?</strong>
					&nbsp;
					<input type="radio" name="atendimento_ambev" value="t" <?=($atendimento_ambev == "t") ? "CHECKED" : "" ?> />Sim
					&nbsp;
					<input type="radio" name="atendimento_ambev" value="f" <?=($atendimento_ambev == "f") ? "CHECKED" : "" ?> />Não
				</td>
			</tr>
			<tr id="informacoes_ambev">
				<td colspan="2" >
					<table border="0" >
						<tr>
							<td id="codigo_ambev" style="display: <?=($atendimento_ambev == 't') ? 'table-cell' : 'none' ?>;">
								Código AMBEV<br />
								<input type="text" name="codigo_ambev" value="<?=$codigo_ambev?>" style="width: 100px;" />
							</td>
							<td>
								Nome <br />
								<input type="text" name="atendimento_ambev_nome" value="<?=$atendimento_ambev_nome?>" style='width:150px;' />
							</td>
							<td>
								Data de Fabricação<br />
								<input type="text" name="data_fabricacao_ambev" value="<?=$data_fabricacao_ambev?>" style="width: 100px;" />
							</td>
							<td nowrap id="data_encerramento_ambev" style="display: <?=($atendimento_ambev == 't') ? 'table-cell' : 'none' ?>;">
								Data de Encerramento<br />
								<input type="text" name="data_encerramento_ambev" value="<?=$data_encerramento_ambev?>" style="width: 100px; " />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		<?php
		}

		if (in_array($login_fabrica, array(51))) {
			include 'pesquisa_satisfacao_new.php';
		}
		?>

		</table>
	</div>
	<br>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'>
				<img src='imagens/ajuda_call.png' align='absmiddle' >
			</td>
			<td align='center'>
				<?
				$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='2' AND fabrica = $login_fabrica";
				$pe = pg_query($con,$sql);
				echo (pg_num_rows($pe)>0) ? pg_fetch_result($pe,0,0) : "Qual o produto comprado?";
				?>
			</td>
			<td align='right' width='150'></td>
		</tr>
	</table>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Informações do produto</strong></td>
		</tr>
	</table>
	<?php
		//alteracao para Fricon lancar varios item num mesmo callcenter hd 165524 waldir
	if ($login_fabrica <> 52) { ?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>

		<tr>
			<td align='left'><?=$campos_obrig;?><strong>Referência:</strong><?=$fx_cmp_obg;?></td>
			<td align='left'>
				<input type="hidden" name="produto_referencia_anterior" value="<?php echo $produto_referencia;  ?>" />
				<?php
				if (in_array($login_fabrica, array(11,81,114,115,116,125,128))) {

					if (empty($callcenter)){
						$mostra_defeito = "mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value);";
					}

					if (!empty($callcenter) and empty($defeito_reclamado)) {
						$mostra_defeito = "mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value);";
					}

				}else{
					$mostra_defeito = "mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value);";
				}
				?>
				<input name="produto_referencia"
						class="input"
						value='<? echo $produto_referencia ;?>'
						<?php if ($login_fabrica != 81) {  ?>
						onblur="fnc_pesquisa_produto2(document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'referencia', null);
							<?php  if ($login_fabrica <> 51){ # HD 41923
								echo $mostra_defeito;
							} ?>
							atualizaQuadroMapas();"
						<?php } ?> type="text" size="15" maxlength="15">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer' onclick="fnc_pesquisa_produto2(document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'referencia', null);
						<?php  if ($login_fabrica <> 51){ # HD 41923
							echo $mostra_defeito;
						} ?>
					atualizaQuadroMapas();">
			</td>
			<td align='left'><?=$campos_obrig;?><strong>Descrição:</strong><?=$fx_cmp_obg;?></td>
			<td align='left'>
				<input type="hidden" name="produto_nome_anterior" value="<?php echo $produto_nome; ?>" />
				<input type='hidden' name='produto' value="<? echo $produto; ?>">
				<input name="produto_nome"  class="input" value="<?php echo $produto_nome ;?>"
				<?php if ($login_fabrica != 81) {  ?>
				<? if ($login_fabrica <> 52) { ?>
					onblur="fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao', null);
				<? } if ($login_fabrica <> 51){
				 	echo $mostra_defeito;
				 } ?>
					 atualizaQuadroMapas();"
				<?php } ?> type="text" size="35" maxlength="80">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick='clearTimeout(janela_descricao); fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,"descricao", null, <?=$i?>);'>
			</td>
			 <? if ($login_fabrica == 14 or $login_fabrica == 43) { ?>
				 <td align='left'><strong>Ordem de Montagem</strong></td>
				 <td align='left'>
		         <input type='text' class="input" name='ordem_montagem' size='20'  value='<?=$ordem_montagem;?>'>
				 </td>
				  <?}?>
			 	<? if (in_array($login_fabrica, array(81,114,122,123,125)) ) { ?>
				 <td align='left'><strong>NF Valor</strong></td>
				 <td align='left'>
		         <input type='text' class="input" name='valor_nf' id='valor_nf' size='20'  value='<?=$valor_nf;?>'>
				 </td>
				  <?}?>
			</tr>
			<tr>
				<td align='left'><?=$campos_obrig;?><strong>Voltagem:</strong><?=$fx_cmp_obg;?></td>
				<td align='left'>
					<input type="hidden" name="voltagem_anterior" value="<?php echo $voltagem; ?>" />
					<input name="voltagem" id="voltagem" class="input" value='<?php echo $voltagem;?>' maxlength="5" >
				</td>
				<?php
				if ($login_fabrica != 145) {
				?>
					<td align='left'><strong><?php echo ($login_fabrica <> 137) ? "Série:" : "Nº Lote:"; ?></strong></td>
					<td align='left'>
						<input type="hidden" name="serie_anterior" value="<?php echo $serie; ?>" />
						<input name="serie" id="serie" maxlength="20" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ?>" value="<?php echo $serie;?>" />
						<?php
						if (!in_array($login_fabrica, array(138,142))) {
						?>
							<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer'
							onclick="javascript: fnc_pesquisa_serie (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'serie',mapa_linha,document.frm_callcenter.serie)">
						<?php
						}
						?>
					</td>
				<?php
				}
				?>
			 	<? if (in_array($login_fabrica, array(81,114,122,123,125)) and !empty($callcenter)) { ?>
					 <td align='left'><strong>Tipo Postagem</strong></td>
					 <td align='left'>
						<select name='tipo_postagem' id='tipo_postagem'>
							<option value=''>- escolha</option>
							<option value='A' <?PHP if ($tipo_postagem == 'A'){ echo "Selected";}?>>Autorização de Postagem</option>
							<option value='C' <?PHP if ($tipo_postagem == 'C'){ echo "Selected";}?>>Coleta</option>
					</td>
				  <?}?>
				<? if($login_fabrica == 74) { ?>
				<td align='left'><strong>Data de Fabricação:</strong></td>
				<td align='left'>
					<input name="dt_fabricacao" readonly="readonly" id="dt_fabricacao" class="input" value="<?php echo $dt_fabricacao;?>" size="15" maxlength="10" />
				</td>
			<? } ?>
				<? if ($login_fabrica == 14 or $login_fabrica == 43) {?>
					<td align='left'><strong>
					Codigo de Postagem</strong>
					</td>
					<td align='left'>
					<input type='text' class="input" name='codigo_postagem' size='30' value='<?=$codigo_postagem;?>'>
					</td>
				<?}?>
		</tr>
		<tr>
			<? if($login_fabrica==30){?>
				<td align='left'><?=$campos_obrig;?><strong>NF compra:</strong><?=$fx_cmp_obg;?></td>
			<? } else{ ?>
				<td align='left'><strong>NF compra:</strong></td>
			<? } ?>

			<td align='left'>
				<?php if ($nota_fiscal == 'null'){
					$nota_fiscal = '';
				} ?>
					<input type="hidden" name="nota_fiscal_anterior" value="<?php echo $nota_fiscal; ?>"/>
				<input name="nota_fiscal" id="nota_fiscal" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $nota_fiscal;?>" maxlength="10" />
			</td>
			<td align='left'><?=$campos_obrig;?><strong>Data NF:</strong><?=$fx_cmp_obg;?></td>
			<td align='left'>
				<input type="hidden" name="data_nf_anterior" value="<?php echo $data_nf_anterior; ?>" />
				<input name="data_nf" id="data_nf" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" rel="data" value="<?php echo $data_nf ;?>">
			</td>
			<? if($login_fabrica==24 AND strlen($familia) > 0) {
				echo "<td align='left'><strong>Familia:</strong></td>";
				echo "<td align='left'>";
				$sql = " SELECT descricao FROM tbl_familia WHERE familia = $familia ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					echo "".pg_fetch_result($res,0,descricao);
				}
				echo "</td>";
			}?>
		</tr>
		<?
		//HD 204082: Busca de revenda para fábricas >= 81 e Telecontrol Net
		if(in_array($login_fabrica,array(2,15,46,74)) OR $login_fabrica >= 81)  {
			if ($nome_revenda == "" && strlen($revenda_nome)) {
				$nome_revenda = $revenda_nome;
			}
		?>
			<tr>
				<?php
					if($login_fabrica == 94 AND $posicao == 8){
						$disabled_rev = "readonly";
					}
				?>
				<td align='left'><strong>CNPJ da Revenda:</strong></td>
				<td align='left'>
					<?php $cnpj_revenda = ($_POST['cnpj_revenda']) ? $_POST['cnpj_revenda'] : $cnpj_revenda ; ?>
					<input name="cnpj_revenda" id="cnpj_revenda" class="<?php echo (in_array($login_fabrica,array(24,85))) ? 'input_req' : 'input' ; ?>" value="<?php echo $cnpj_revenda;?>"type="text" maxlength="14" <?=$disabled_rev?> />
					<input name="revenda_tab" id="revenda_tab" type="hidden" value="<?php echo $revenda;?>" />
				</td>
				<td align='left'><strong>Nome da Revenda:</strong></td>
				<td align='left'>
					<input name="nome_revenda" id="nome_revenda" class="<?php echo (in_array($login_fabrica,array(24,85))) ? 'input_req' : 'input' ; ?>" value="<?php echo $nome_revenda;?>" size=35 maxlength="50" <?=$disabled_rev?> />
				</td>
			<? if($login_fabrica == 74) { ?>
				<td align='left'>
					<strong>Telefone da Revenda 1:</strong> <br /> <br />
					<strong>Telefone da Revenda 2:</strong>
				</td>
				<td align='left'>
					<input name="fone_revenda" id="fone_revenda" class="input" value="<?php echo $fone_revenda;?>" size="35" maxlength="50" /> <br /> <br />
					<input name="fone_revenda2" id="fone_revenda2" class="input" value="<?php echo $fone_revenda2;?>" size="35" maxlength="50" />
				</td>
			<? } ?>
			<? if(in_array($login_fabrica,array(81,122,114,123,125)) and !empty($callcenter)) { ?>
				<td align='left'>
					<input name="btn_lgr" id="btn_lgr" value='Solicitar Logistica Reversa Correios' type='button' onclick='solicitaPostagem(<?=$callcenter?>)'>
				</td>
			<? } ?>
			</tr>
		<? 	if($login_fabrica == 74){ ?>
			<tr>
				<td align='left'><strong>Data visita técnico:</strong></td>
				<td align='left'>
					<?php $data_visita_tecnico = ($_POST['data_visita_tecnico']) ? $_POST['data_visita_tecnico'] : $data_visita_tecnico ; ?>
					<input name="data_visita_tecnico" id="data_visita_tecnico" class="input" rel="data" value="<?php echo $data_visita_tecnico;?>"type="text" maxlength="14"  />
				</td>
				<td align='left'><strong>Garantia:</strong></td>
				<td>
					<?php
						$checked_garantia = ($garantia_produto == "f") ? "checked" : "";
					?>
					<input type='radio' name='garantia_produto' value='t' checked> Sim &nbsp;
					<input type='radio' name='garantia_produto' value='f' <?=$checked_garantia?>>Não
				</td>
			</tr>
		<?
			}
		}
		if($login_fabrica==3) {?>
		<tr>
		<tr>
			<td colspan='2' align='left'>
				<a  href="javascript:fnc_pesquisa_os (document.frm_callcenter.nota_fiscal, 'nota_fiscal')">Clique aqui para ver todas as OSs cadastradas com esta nota fiscal</a>
			</td>
		</tr>
		<?}?>
	</table>

	<?
	}
	else {
		unset($defeito_reclamado);
	?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px' name='tabela_itens' id='tabela_itens'>
		<thead>
		<tr>
			<td align='left'><strong>NF compra:</strong></td>
			<td align='left'>
				<input type="hidden" name="nota_fiscal_anterior" value="<?php echo $nota_fiscal; ?>"/>
				<input name="nota_fiscal" id="nota_fiscal" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $nota_fiscal;?>" maxlength="10" />
			</td>
			<td align='left'><strong>Data NF:</strong></td>
			<td align='left'>
				<input type="hidden" name="data_nf_anterior" value="<?php echo $data_nf; ?>" />
				<input name="data_nf" id="data_nf" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" rel="data" value="<?php echo $data_nf ;?>">
			</td>
			<?
			if ($login_fabrica == 52) {
			?>
			<td align='left'><strong>Marca:</strong></td>
			<td align='left'>
				<?
				$sql_fricon = "SELECT marca, nome
								FROM tbl_marca
								WHERE tbl_marca.fabrica = $login_fabrica
								ORDER BY tbl_marca.nome ";
				$res_fricon = pg_query($con, $sql_fricon); ?>

				<select name='marca' id='marca' class="frm">
				<?
				if (pg_numrows($res_fricon) > 0) { ?>
					<option value=''>ESCOLHA</option> <?
					for ($x = 0 ; $x < pg_numrows($res_fricon) ; $x++){
						$marca_aux = trim(pg_result($res_fricon, $x, marca));
						$nome_aux = trim(pg_result($res_fricon, $x, nome));
						if ($marca == $marca_aux) {
							$selected = "SELECTED";
						}else {
							$selected = "";
							}?>
						<option value='<?=$marca_aux?>' <?=$selected?>><?=$nome_aux?></option> <?
					}
				}else { ?>
					<option value=''>Não existem linhas cadastradas</option><?
				} ?>
				</select>
				<input type="hidden" name="marca_anterior" value="<?php echo $marca; ?>">
			</td>
			<?
			}
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		if (strlen($callcenter)>0) {

			if($login_fabrica == 52){
				$join_ativo = " left JOIN tbl_numero_serie ON tbl_hd_chamado_item.produto = tbl_numero_serie.produto AND tbl_numero_serie.serie = tbl_hd_chamado_item.serie ";
				$campo = ", tbl_numero_serie.ordem , tbl_hd_chamado_item.hd_chamado_item ";
			}

			$sql_produto = "SELECT  tbl_produto.produto,
									descricao,
									referencia,
									tbl_hd_chamado_item.serie,
									defeito_reclamado,
									protocolo_cliente
									$campo
							from tbl_hd_chamado_item
							join tbl_produto using(produto)
							JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
							$join_ativo
							where tbl_hd_chamado_item.hd_chamado = $callcenter
							order by hd_chamado_item ";
			$res_produto = pg_query($con,$sql_produto);
			$qtde_produto = pg_num_rows($res_produto);

		}

		if ($_POST['qtde_produto'] > 0 and $msg_erro){
			$qtde_produto = $_POST['qtde_produto'];
		}

		if (empty($qtde_produto)) {
			$qtde_produto = 1;
		}
			for ( $i = 1 ; $i <= $qtde_produto ; $i++ ) {

				if (strlen($msg_erro)>0) {
					$serie					= $_POST['serie_'.$i];
					$produto_referencia		= $_POST['produto_referencia_'.$i];
					$produto_nome			= $_POST['produto_nome_'.$i];
					$defeito_reclamado		= $_POST['defeito_reclamado_'.$i];
					if($login_fabrica == 52){
						$numero_ativo			= $_POST['numero_ativo_'.$i];
						$hd_chamado_item_fricon	= $_POST['hd_chamado_item_'.$i];
						$controle_cliente	= $_POST['controle_cliente_'.$i];
					}
				}
				else {

					if (strlen($callcenter)>0) {
						$serie					= pg_fetch_result($res_produto,$i-1,serie);
						$produto_referencia		= pg_fetch_result($res_produto,$i-1,referencia);
						$produto_nome			= pg_fetch_result($res_produto,$i-1,descricao);
						$defeito_reclamado		= pg_fetch_result($res_produto,$i-1,defeito_reclamado);
						if($login_fabrica == 52){
							$numero_ativo			= pg_fetch_result($res_produto,$i-1,ordem);
							$produto_defeito_reclamado = pg_fetch_result($res_produto, $i-1, 'produto');
							$hd_chamado_item_fricon	= pg_fetch_result($res_produto,$i-1,'hd_chamado_item');
							$controle_cliente	= pg_fetch_result($res_produto,$i-1,'protocolo_cliente');

							if (!empty($hd_chamado_item_fricon)){
								$readonlyFricon = 'readonly="readonly"';
							}else{
								$readonlyFricon = 'readonly=""';
							}
						}


					}
				}

				if($login_fabrica == 52){
					if (!empty($hd_chamado_item_fricon)){
						$readonlyFricon = 'readonly="readonly"';
						$disabledFricon = 'disabled="disabled"';
					}else{
						$disabledFricon = '';
						$readonlyFricon = '';
					}
				}
		?>
		<tr>
			<td align='left'><strong>Série:</strong></td>
			<td align='left'>
				<input type="hidden" name="hd_chamado_item_<?=$i?>" value="<?=$hd_chamado_item_fricon?>">
				<input type="hidden" name="serie_anterior_<?=$i?>" value="<?php echo $serie; ?>" />
				<input name="serie_<?=$i;?>" id="serie_<?=$i;?>" maxlength="20" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $serie;?>" />
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_serie (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'serie',mapa_linha,document.frm_callcenter.serie_<?=$i;?>, <?=$i?>)">
			</td>
			<td align='left'><strong>Referência:</strong></td>
			<td align='left'>
				<input type="hidden" name="produto_referencia_anterior_<?=$i?>" value="<?php echo $produto_referencia;  ?>" />
				<input name="produto_referencia_<?=$i?>" class="input"  value='<? echo $produto_referencia ;?>'
				onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'referencia',mapa_linha, <?=$i?>);" <?php  if ($login_fabrica <> 51){ # HD 41923 ?>
					" mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_<?=$i;?>.value);"
					<?php } ?>
					" atualizaQuadroMapas();" type="text" size="15" maxlength="15"><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',mapa_linha, <?=$i?>)">
			</td>
			<td align='left'><strong>Descrição:</strong></td>
			<td align='left'>
				<input type="hidden" name="produto_nome_anterior_<?=$i?>" value="<?php echo $produto_nome; ?>" />
				<input type='hidden' name='produto_<?=$i?>' value="<? echo $produto; ?>">
				<input name="produto_nome_<?=$i?>"  size='20' class="input" value='<?php echo $produto_nome ;?>'
				<? if ($login_fabrica <> 52) { ?> onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',mapa_linha, <?=$i?>);" <?php }if ($login_fabrica <> 51){ ?>
				mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_<?=$i;?>.value);
				<?php } ?>
				" atualizaQuadroMapas();" type="text" size="35" maxlength="500"><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'descricao',mapa_linha, <?=$i?>)">
			</td>
			<td align='left'>
				<strong>Defeito Reclamado</strong>
			</td>
			<td align='left'><? ;?>
				<select class='input' name='defeito_reclamado_<?=$i?>' <?=$disabledFricon?> id='defeito_reclamado_<?=$i?>'>
					<option> </option>
					<?php
					//HD 706810
					if (in_array($login_fabrica,$fabricas_defeito_reclamado_sem_integridade)){

						$sqldef = "SELECT defeito_reclamado, descricao
												FROM tbl_defeito_reclamado
												WHERE fabrica = $login_fabrica
												and ativo='t' ORDER BY descricao";

					}else{
					//HD 706810 END

						if (!empty($produto_defeito_reclamado)) {
							$cond_defeito_produto = " AND tbl_produto.produto = $produto_defeito_reclamado ";
						} else {
							$cond_defeito_produto = "";
						}

						if ($login_fabrica <> 52) {
							$and_linha = " AND tbl_diagnostico.linha = tbl_produto.linha ";
						} else {
							$and_linha = '';
						}

						$sqldef = "SELECT distinct tbl_defeito_reclamado.descricao,
								tbl_defeito_reclamado.defeito_reclamado
								FROM tbl_diagnostico
								JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
								JOIN tbl_produto on  tbl_diagnostico.familia = tbl_produto.familia $and_linha
								WHERE tbl_diagnostico.fabrica = $login_fabrica
								AND tbl_diagnostico.ativo is true
								$cond_defeito_produto
								ORDER BY tbl_defeito_reclamado.descricao";

					}

					$resdef = pg_query($con,$sqldef);
					if (pg_num_rows($resdef)>0) {
							for ($w=0;$w<pg_num_rows($resdef);$w++) {
							unset($selected);
							$xdefeito_reclamado = pg_fetch_result($resdef,$w,defeito_reclamado);
							$descricao         = pg_fetch_result($resdef,$w,descricao);
							$descricao = substr($descricao,0,30);
							if ($defeito_reclamado == $xdefeito_reclamado) {
								$selected = "SELECTED";
							}

							echo "<option value='$xdefeito_reclamado' $selected> $descricao</option>";
						}
					}
				?>
			</select>
			</td>

			<? if($login_fabrica == 52){ ?>
					<td align='right'>
						<strong>Nº Controle Cliente</strong>
					</td>
					<td align='left'>
						<input type="text" name="controle_cliente_<?=$i?>" id="controle_cliente_<?=$i?>" <?=$readonlyFricon?> class="input" value="<?php echo $controle_cliente; ?>" maxlength='20'>

					</td>
			<? } ?>

			<td align='left'>
				<strong>Número do Ativo</strong>
			</td>
			<td align='left'>
				<input type="text" name="numero_ativo_<?=$i?>" id="numero_ativo_<?=$i?>" <?=$readonlyFricon?> class="input" value="<?php echo $numero_ativo; ?>">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_ordem (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'ordem',mapa_linha,document.frm_callcenter.serie_<?=$i;?>,document.frm_callcenter.numero_ativo_<?=$i;?>, <?=$i?>)">
			</td>

			<td>
				<input type='button' name='addlinha' value='+' onclick='function1(<?=$i?>)'>
			</td>
		</tr>
		<? }?>
		<INPUT TYPE='hidden' NAME='qtde_produto' value='<? echo $i= $i-1;?>' id='qtde_produto'>
		</tbody>
	</table>
	<?php
	}
	if($login_fabrica <> 3){ //HD 40086 ?>
	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Mapa da Rede</strong></td>
		</tr>
	</table>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<?php
				//HD 211895: Habilitar abertura de pré-os em um chamado após o mesmo já estar em aberto
			if (strlen($callcenter) > 0) {
				$sql = "SELECT abre_os FROM tbl_hd_chamado_extra WHERE hd_chamado=$callcenter";
				$res_abre_os = pg_query($con, $sql);
				$xabre_os = pg_result($res_abre_os, 0, abre_os);

				if ($xabre_os == 't') {
					if ($login_fabrica == 74) {
 						$disabled = "readonly";
 					} else {
 						$disabled = "disabled";
 					}

					$checked = "checked";
				}
				else {
					if (isset($abre_os_submeteu)) {
						if ($abre_os == 't') {
							$checked = "checked";
						}
						else {
							$abre_os = $xabre_os;
						}
					}
				}
			}
			else {
				if (isset($abre_os_submeteu)) {
					if ($abre_os == 't') {
						$checked = "checked";
					}
					else {
					}
				}
				elseif ($login_fabrica == 52) {
					$checked = "checked";
				}
			}

			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if (strlen($callcenter) > 0 && $login_fabrica == 30) {
				$sql = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado=$callcenter";
				$res_abre_ordem_servico = pg_query($con, $sql);

				if (pg_num_rows($res_abre_ordem_servico)) {
					$xabre_ordem_servico = pg_result($res_abre_ordem_servico, 0, os);
				}
				else {
					$xabre_ordem_servico = false;
				}

				if ($xabre_ordem_servico) {
					$disabled = "disabled";
					$checked_abre_ordem_servico = "checked";
				}
				else {
					if (isset($abre_ordem_servico_submeteu)) {
						if ($abre_ordem_servico == 't') {
							$checked_abre_ordem_servico = "checked";
						}
						else {
							if ($xabre_ordem_servico) {
								$abre_ordem_servico = 't';
							}
							else {
								$abre_ordem_servico = "";
							}
						}
					}
				}
			}else {

				if (isset($abre_ordem_servico_submeteu)) {
					if ($abre_ordem_servico == 't') {
						$checked_abre_ordem_servico = "checked";
					}
				}else{
					$checked_abre_ordem_servico = "checked";
				}

			}


			if(($login_fabrica == 94 AND $posicao == 9) or $xabre_os == 't' or $abre_ordem_servico == 't'){
				if($login_fabrica != 7 && $login_fabrica != 74){
					$disabled_posto = "readonly";
				}
			}

			if ( strlen($callcenter) > 0 && $login_fabrica == 52){



					$sql = "SELECT os FROM tbl_hd_chamado_item WHERE hd_chamado=$callcenter and os is not null";
					$res_abre_ordem_servico = pg_query($con, $sql);

					if (pg_num_rows($res_abre_ordem_servico)>0) {
						$abre_ordem_servico = 't' ;
						$checked_abre_ordem_servico = "checked";
						$disabled = 'disabled';
						$disabled_posto = "";
					}

			}

			if ($login_fabrica == 52 and !empty($msg_erro)){

				$checked_abre_ordem_servico = "checked";
				$disabled = '';
				$disabled_posto = "";

			}

			?>
			<td align='left' width='50'><strong>Linha:</strong></td>
			<td align='left'>
			<?


			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					  AND   tbl_linha.ativo
					ORDER BY tbl_linha.nome;";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
				echo "<select name='mapa_linha' id='mapa_linha' >\n";
				echo "<option value=''>Escolha</option>\n";
				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$aux_linha = trim(pg_fetch_result($res,$x,linha));
					$aux_nome  = trim(pg_fetch_result($res,$x,nome));

					if ($_POST['mapa_linha'] == $aux_linha || $linha == $aux_linha){
						$selected_linha = " SELECTED ";
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}else{
						$selected_linha = "  ";
						$mostraMsgLinha = " ";
					}

					echo "<option value='$aux_linha' $selected_linha>$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}


			#flag 7
			if($login_fabrica == 7 and $consumidor_cidade != ""){

				$sql = "SELECT *
						FROM tbl_cidade_sla
						WHERE cidade ilike('%".$consumidor_cidade."%') LIMIT 1";

				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$ssl_horas = pg_result($res,0,'hora');
				}else{
					$ssl_horas = "20";
				}

			}

			?>
			</td>
			<td align='left' width='50'><strong>Estado:</strong></td>
			<td align='left'>
				<?php
				if ($callcenter and $posto) {
					$sql = "SELECT contato_estado,contato_cidade
							from tbl_posto_fabrica
							where fabrica=$login_fabrica
							AND posto = $posto;";
					$res = pg_query($con,$sql);

					if (pg_num_rows($res)>0) {
						$estado = pg_fetch_result($res,0,0);
						$cidade = pg_fetch_result($res,0,1);
					}

				}
				?>
				<select name='mapa_estado' id='mapa_estado' <?=$disabled_posto?>>
					<?php if($login_fabrica != 74){?>
					<option value='00' selected>Todos</option>
					<?php }?>
					<? if ($login_fabrica == 5) {
						$estados = array('SUL' => 'Sul',
										 'SP-campital' => 'São Paulo - Capital',
										 'SP-interior' => 'São Paulo - Interior',
										 'RJ' => 'Rio de Janeiro' ,
										 'MG' => 'Minas Gerais',
										 'PE' => 'Pernambuco',
										 'BA' => 'Bahia' ,
										 'BR-NEES' => 'Nordeste + E.S.' ,
										 'BR-NCO' => 'Norte + C.O.'

						 				);
						foreach ($estados as $key => $value) {

							$selected_estado = ($_POST['mapa_estado'] == $key || $estado == $key) ? ' SELECTED ' : '' ;
							?>
							<option value='<?=$key?>'  <?=$selected?> ><?=$value?></option>
							<?
						}
					} else {
						$estados = array('AC' => 'Acre',
										 'AL' => 'Alagoas',
										 'AP' => 'Amapá',
										 'AM' => 'Amazonas' ,
										 'BA' => 'Bahia',
										 'CE' => 'Ceará',
										 'DF' => 'Distrito Federal' ,
										 'GO' => 'Goiás' ,
										 'ES' => 'Espirito Santo',
										 'MA' => 'Maranhão',
										 'MT' => 'Mato Grosso',
										 'MS' => 'Mato Grosso do Sul',
										 'MG' => 'Minas Gerais',
										 'PA' => 'Pará',
										 'PB' => 'Paraíba',
										 'PR' => 'Paraná',
										 'PE' => 'Pernambuco',
										 'PI' => 'Piaui',
										 'RJ' => 'Rio de Janeiro',
										 'RN' => 'Rio Grande do Norte',
										 'RS' => 'Rio Grande do Sul',
										 'RO' => 'Rondônia',
										 'RR' => 'Roraima',
										 'SC' => 'Santa Catarina',
										 'SE' => 'Sergipe',
										 'SP' => 'São Paulo',
										 'TO' => 'Tocantins',
										 'BR-N' => 'Região Norte',
										 'BR-NE' => 'Região Nordeste',
										 'BR-CO' => 'Região Centro-Oeste',
										 'BR-SE' => 'Região Sudeste',
										 'BR-S' => 'Região Sul'

						 				);
						foreach ($estados as $key => $value) {

							$selected_estado = ($_POST['mapa_estado'] == $key || $estado == $key) ? ' SELECTED ' : '' ;
							?>
							<option value='<?=$key?>'  <?php echo $selected_estado?> ><?=$value?></option>
							<?
						}
					?>
					<? }?>
				</select>
			<td align='left' width='50'><strong>Cidade:</strong></td>
			<?php
			if ($cidade) {
				$mapa_cidade = $cidade;
			}
			 ?>

			<?php
			if($login_fabrica == 85 or $login_fabrica == 74){
			?>
				<td align="left">
					<select id="mapa_cidade" name="mapa_cidade" style="width: 150px;">
						<option value=""></option>
					</select>
				</td>

				<?php if($login_fabrica == 74){ ?>
				<td><strong>Bairro:</strong></td>
				<td align="left">
					<select id="mapa_bairro" name="mapa_bairro" style="width: 150px;">
						<option value=""></option>
					</select>
				</td>
				<?php } ?>

				<td><strong>Postos:</strong></td>
				<td>
					<select id="postos_cidade" name="postos_cidade" style="width: 150px;">
						<option value=""></option>
					</select>
				</td>

				<td>
					<input type='button' name='btn_mapa' value=' Mapa ' onclick='javascript:mapa_rede_new(mapa_linha,mapa_estado,mapa_cidade,cep,consumidor_endereco,consumidor_numero,consumidor_bairro,consumidor_cidade,consumidor_estado)' <?=$disabled_posto?>>
				</td>

			<?php
			}else{
				?>
					<td align='left'><input type='text' id='mapa_cidade' name='mapa_cidade' value='<?=$mapa_cidade?>' <?=$disabled_posto?>>
					<input type='button' name='btn_mapa' value=' Mapa ' onclick='javascript:mapa_rede_new(mapa_linha,mapa_estado,mapa_cidade,cep,consumidor_endereco,consumidor_numero,consumidor_bairro,consumidor_cidade,consumidor_estado)' <?=$disabled_posto?>>
				<?php
			}
			?>
			</td>
		</tr>
			<tr>

				<td align='left'><strong>Código:</strong></td>
				<td align='left'>
					<input name="codigo_posto_tab" id="codigo_posto_tab"  class="posto_codigo input" value='<?echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15" <?=$disabled_posto?>>
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input type='hidden' name='posto_tab' id='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab" id="posto_nome_tab"  class="posto_nome input" value='<?echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="150" <?=$disabled_posto?>>
					<?php

					if($login_fabrica == 30){
						echo "<input type='button' name='btn_consulta_cidades_atendidas' id='btn_consulta_cidades_atendidas' class='btn_consulta_cidades_atendidas input_req2' style='background-color:#DDDDDD' value='Cidades atendidas'/>";
					}

					if ($login_fabrica == 74) {
 						echo "<input type='hidden' id='posto_atual' name='posto_atual' value='{$posto}' />";
 					}
					?>
				</td>
				<?
				if ($login_fabrica == 52) {
				?>
				<td align='left'><strong>Distancia Km(ida/volta):</strong></td>
				<td align='left'>
					<input type='hidden' name='posto_km_tab_anterior' value='<?echo $posto_km_tab?>'>
					<input type='text' name='posto_km_tab' class="km_distancia input" value='<?echo $posto_km_tab?>' maxlength="5">
				</td>
				<? } ?>
			</tr>
		<tr>
			<tr>
				<?php
				$posto_fone_tab = ($_POST['posto_fone_tab']) ? trim($_POST['posto_fone_tab']) : $posto_fone_tab ;
				$posto_email_tab = ($_POST['posto_email_tab']) ? trim($_POST['posto_email_tab']) : $posto_email_tab ;
				?>
				<td align='left'><strong>Telefone:</strong></td>
				<td align='left'>
					<input name="posto_fone_tab" id="posto_fone_tab"  class="posto_fone input" value='<?echo $posto_fone_tab;?>'  type="text" size="15" maxlength="15" readonly>
				</td>
				<td align='left'><strong>E-mail:</strong></td>
				<td align='left'>
					<input name="posto_email_tab" id="posto_email_tab"  class="posto_email input" value='<?echo $posto_email_tab ;?>'  type="text" size="35" maxlength="50" readonly>
				</td>
				<?
				if ($login_fabrica == 52 AND strlen($callcenter) > 0) {
					$sqlXX = "SELECT os,sua_os, obs_adicionais FROM tbl_os JOIN tbl_os_extra USING(os) where tbl_os.hd_chamado=$callcenter and tbl_os.fabrica=$login_fabrica";

					$resXX = pg_query($con,$sqlXX);
				?>
				<td colspan='2' align='left'>
					<?php
						for($xx = 0; $xx < pg_numrows($resXX); $xx++){
							$inf_adicional = pg_result($resXX,$xx,'obs_adicionais');
							if(!empty($inf_adicional)){
								echo "<b>".$inf_adicional."</b>";
								echo "<br />";
							}
						}
					?>
				</td>
				<? } ?>

				<?
				if ($login_fabrica == 7) {
				?>
				<td align='left' nowrap><strong>Data agendamento:</strong></td>
				<td colspan='2' align='left'>
					<input name="data_ag" id="data_ag" class="input" rel="data" value="<?=$data_ag?>" size='10'>
				</td>

				<td align='left' nowrap><strong>SLA:</strong></td>
				<td colspan='2' align='left'>
					<input name="ssl_horas" class="input" value="<?=$ssl_horas?>" readonly size='10'>
				</td>

				<? } ?>
			</tr>
		<tr>
			<td colspan='6'>
			<?

			if($login_fabrica == 85 && !empty($os)){

				$sql_abre_os = "SELECT abre_os FROM tbl_hd_chamado_extra WHERE hd_chamado = $callcenter";
				$res_abre_os = pg_query($con, $sql_abre_os);

				if(pg_num_rows($res_abre_os) > 0){

					$abre_os = pg_fetch_result($res_abre_os, 0, 'abre_os');

					if(empty($abre_os)){

						$abre_ordem_servico 		= 't';
						$checked 					= "";
						$disabled 					= 'disabled';

					}

				}

				$sql_reabrir_os = "SELECT tbl_os.data_fechamento
						FROM tbl_hd_chamado
						JOIN tbl_os ON tbl_os.os = {$os} AND tbl_os.data_fechamento IS NOT NULL
						WHERE tbl_hd_chamado.hd_chamado = {$callcenter}
						AND tbl_hd_chamado.status <> 'Resolvido'";
				$res_reabrir_os = pg_query($con, $sql_reabrir_os);
				$res_reabrir_os = pg_num_rows($res_reabrir_os);

				if($res_reabrir_os > 0){

					$button_reabrir_os = "<br /> <button type='button' onClick='inserirMotivoReabrirOS(\"{$os}\")' style='padding-left: 10px; padding-right: 10px;'>Reabrir OS</button> <br /> ";


					?>
					<script>
						function inserirMotivoReabrirOS(os){

							Shadowbox.open({
                                content: 'tela_motivo_reabrir_os.php?os=<?=$os?>&callcenter=<?=$callcenter?>&login_admin=<?=$login_admin?>',
                                player: "iframe",
                                title: "Motivo para Reabrir OS",
                                enableKeys: false,
                                width: 400,
                                height: 250
                            });

						}

					</script>
					<?php

				}

			}

			echo "<tr><td align='left' colspan='6'>";
			if($login_fabrica == 30 or $login_fabrica == 52){
				echo "";
			}else{
				echo "<strong><input type=hidden name='abre_os_submeteu' value='sim'><input $disabled type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)' $checked> Abrir PRE-OS para o esta Autorizada</strong>";
			}

			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if (in_array($login_fabrica,array(30,52,72,74,85,139))) {
				if ($login_fabrica == 52){

					if ($disabled){
						echo "<input type='hidden' name='abre_ordem_servico' value='t' />";
						echo "<br><strong><input type=hidden name='abre_ordem_servico_submeteu' value='sim'><input type='checkbox' $disabled $checked_abre_ordem_servico> Abrir ORDEM DE SERVIÇO para o esta Autorizada</strong>";
					}else{
						echo "<br><strong><input type=hidden name='abre_ordem_servico_submeteu' value='sim'><input type='checkbox' name='abre_ordem_servico' $disabled  id='abre_ordem_servico' value='t' onClick='verificarImpressao(this)' $checked_abre_ordem_servico> Abrir ORDEM DE SERVIÇO para o esta Autorizada</strong>";
					}

				}elseif($login_fabrica == 30){
					echo "<br><strong><input type=hidden name='abre_ordem_servico_submeteu' value='sim'><input type='checkbox' name='abre_ordem_servico' $disabled  id='abre_ordem_servico' value='t' onClick='verificarImpressao(this)' $checked_abre_ordem_servico> Abrir ORDEM DE SERVIÇO para o esta Autorizada</strong>";
				}
				if ($os and $login_fabrica == 30) {
					$sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
					$res = pg_query($con, $sql);
					$abre_os_sua_os = pg_result($res, 0, sua_os);

					echo " <a href='os_press.php?os=$os' target='_blank'>$abre_os_sua_os</a>";
				}elseif ($login_fabrica == 52 and $callcenter) {

					if (pg_num_rows($resXX)>0) {
						for ($xx=0; $xx < pg_num_rows($resXX); $xx++) {
							$os_fricon = pg_fetch_result($resXX, $xx, 'os');
							$sua_os_fricon = pg_fetch_result($resXX, $xx, 'sua_os');
							echo "&nbsp; <a href='os_press.php?os=$os_fricon' target='_blank'>$sua_os_fricon</a>&nbsp;";
						}
					}
				}elseif($os AND in_array($login_fabrica,array(72,74,85,139))){
					$sql = "SELECT tbl_os.sua_os, tbl_status_checkpoint.descricao
								FROM tbl_os
								JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
								WHERE tbl_os.os = $os ";
					$res = pg_query($con, $sql);
					$abre_os_sua_os = pg_result($res, 0, 'sua_os');
					$abre_os_status = pg_result($res, 0, 'descricao');
					echo "	<table class='tabela'>
								<tr class='titulo_tabela'>
									<th>OS</th>
									<th>Status</th>
								</tr>
								<tr>
									<td><a href='os_press.php?os=$os' target='_blank'>$abre_os_sua_os</a></td>
									<td>".$abre_os_status."</td>
									<input name='xos' value='$xos' type='hidden'>
								</tr>
							</table>";
				}
			}

			echo "<div id='imprimir_os' style='display:$display'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
			echo "</td>";
			echo "</tr>";
			?>
			</td>
		</tr>

		<tr>
			<td colspan="6">

				<?php
					if($login_fabrica == 85){
						echo $button_reabrir_os;
					}
				?>

			</td>
		</tr>

		</table>
	<? }

	?>

	<?if (in_array($login_fabrica, array(81, 114, 122, 123, 128))) { ?>

	<script>
		$(function () {
			$("input[name='origem_reclamacao[]'][value=outro]").change(function () {
				if ($(this).is(":checked")) {
					$("input[name=origem_reclamacao_outro]").show();
				} else {
					$("input[name=origem_reclamacao_outro]").hide().val("");
				}
			});
		});
	</script>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Análise da Reclamação</strong></td>
		</tr>
	</table>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>

			<tr>
				<td align='left'><strong>Produto:</strong></td>
				<td align='left'>
					<input name="referencia_adi" class="input" value='<? echo $referencia_adi ;?>' type="text" size="40" maxlength="60">
				</td>
				<td align='left'><strong>Quantidade:</strong></td>
				<td align='left'>
					<input name="qtde_adi"  class="input" value="<?php echo $qtde_adi ;?>" type="text" size='4' maxlength='4' >
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Recebido:</strong></td>
				<td align='left'><input type='text' name='recebido' class="input" value='<?=$recebido?>'  size="13" maxlength="13" rel='data'>
				</td>
				<td align='left'><strong>Análise:</strong></td>
				<td align='left'><input type='text' name='analise' id='analise' class="input" value='<?=$analise?>' size="13" maxlength="13" rel="data">

				</td>
			</tr>
			<tr>
				<td align='left'><strong>Validade:</strong></td>
				<td align='left'><input type='text' name='validade' id='validade' class="input" value='<?=$validade?>' size="13" maxlength="8" rel='mes_ano'>
				</td>
				<td align='left'><strong>Lote:</strong></td>
				<td align='left'><input type='text' name='lote' id='lote' class="input" value='<?=$lote?>' size="30" >
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Origem:</strong></td>
				<td align='left'><input type='text' name='origem_adi' id='origem_adi' class="input" value='<?=$origem_adi?>' size="30" >
				</td>
				<td align='left'><strong>Acompanhado De :</strong></td>
				<td align='left' colspan='2'><input type='text' name='aco_de' id='aco_de' class="input" value='<?=$aco_de?>'  size="30">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Status da Reclamação:</strong></td>
				<td align='left'>
				<input type='radio' name='procedente'  class="input" value='procedente' <?if ($procedente == 'procedente') echo " checked "?>  >PROCEDENTE
				<input type='radio' name='procedente'  class="input" value='improcedente'<?if ($procedente == 'improcedente') echo " checked "?> >IMPROCEDENTE
				<input type='checkbox' name='reposicao'  class="input" value='reposicao' <?if ($reposicao == 'reposicao') echo " checked "?> >FAZER REPOSIÇÃO
				</td>
				<td align='left'><strong>Disposição:</strong></td>
				<td align='left'><input type='text' name='disposicao' id='disposicao' class="input" value='<?=$disposicao?>' size="30" >
				</td>
			</tr>

			<tr>
				<td align='left'><strong>Descrição Da Análise</strong></td>
				<td colspan="5"><textarea name="descricao_analise" rows="6" cols="110" class="input" style="font-size: 10px;"><?=$descricao_analise?></textarea></td>
			</tr>
			</table>
		</div>

	<?}?>
	<br>

	<div rel='div_ajuda' style='display:inline; Position:relative;'>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG><?echo$consumidor_nome;?></STRONG><BR>
			<?
			$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='3' AND fabrica = $login_fabrica";
			$pe = pg_query($con,$sql);
			echo (pg_num_rows($pe)>0) ? pg_fetch_result($pe,0,0) : "em que posso ajudá-lo?";
			?>
			</td>
			<td align='right' width='150'></td>
		</tr>
		</table>
	</div>
	</td>
</tr>
<tr>
	<td align='left'>
	    <br /><?php
		if (strlen($callcenter) > 0 and strlen($msg_erro) == 0) {
			$tab_atual = $natureza_chamado;
		} else if (strlen($tab_atual) == 0) {
			$tab_atual = "reclamacao_produto";
		}?>

		<input type='hidden' name='tab_atual' id='tab_atual' value='<? echo $tab_atual; ?>' >

		<div id="container-Principal"><?php

		if ($usuario_abriu == 2473 && $login_fabrica == 24) {//HD 262718?>

			<strong>DESEJA FALAR SOBRE: </strong>
			<select name='tipo_contato_categoria' id='tipo_contato_categoria' onchange="alteraAba(this.value)">
				<option value=''></option><?php
				$vet_ordem = array(1,2,3,4,6,8);
				$xx = 0;
				foreach ($assuntos AS $topico => $opcoes) {
					echo " <option value='".$vet_ordem[$xx]."' ".($topico == $tipo_contato_categoria ? "selected='selected'" : '').">$topico</option>";
					$xx++;
				}?>
			</select>

			<br />
			<br /><?php

		}?>

		<ul><?php
			if ($login_fabrica == 25) {?>
				<li>
					<a href="#extensao" rel="extensao" id='extensao_click'>
					<span><img src='imagens/garantia_estendida.png' width='10' align="absmiddle">Garantia</span>
					</a>
				</li><?php
			}?>
 			<li <?php if( empty($callcenter) ){ echo 'class="tabs-selected"'; /* HD 896924 - setando a aba */ } ?>>
				<a href="#reclamacao_produto" id="reclamacao_produto_click" rel="reclamacao_produto">
				<span>
				<!--<img src='imagens/rec_produto.png' width='10' align="absmiddle" alt='Reclamao Produto/Defeito'>-->Reclamação</span>
				</a>
			</li><?php
			if ($login_fabrica != 2) {?>
				<li>
					<a href="#reclamacao_empresa" rel="reclamacao_empresa" id="reclamacao_empresa_click">
						<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamao Empresa'>--><?php
						if ($login_fabrica == 24) {
							echo "Empresa";
						} else {
							echo "Recl. Empresa";
						}?>
						</span>
					</a>
				</li>
				<li><?php
					if ($login_fabrica == 11 and strlen($tipo_reclamacao) > 0) {

						if (in_array($tipo_reclamacao, $sub_tipo_reclamacao)) {
							$tab_atual = 'reclamacao_at';
						}

					}?>
					<a href="#reclamacao_at" rel="reclamacao_at" id='reclamacao_at_click'>
						<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamao Assistncia Tcnica'>-->
						<? echo ($login_fabrica==11) ? "A.T." : "Recl. A.T."; ?>
						</span>
					</a>
				</li><?php

			}

			if ($login_fabrica == 2) {

				if ($tab_atual == 'reclamacao_at') {// or $tab_atual == 'reclamacao_produto') {
					$tab_atual = "reclamacoes";
				}?>

				<li>
					<a href="#reclamacoes" rel="reclamacoes" id ='reclamacoes_click'>
					<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamao Assistncia Tcnica'>-->Reclamações</span>
					</a>
				</li><?php

			}?>


			<li>
				<a href="#duvida_produto" rel="duvida_produto" id='duvida_produto_click' >
				<span><!--<img src='imagens/duv_produto.png' width='10' align=absmiddle>-->Dúvida Prod.</span>
				</a>
			</li>
			<li>
				<a href="#sugestao" rel="sugestao" id='sugestao_click'>
				<span><!--<img src='imagens/sugestao_call.png' width='10' align=absmiddle>-->Sugestão</span>
				</a>
			</li><?php
			if ($login_fabrica != 59) {

				if ($login_fabrica == 11) {

					if ($natureza_chamado2 == 'reclamacao_at_procon') {
						$tab_atual = 'procon';
					}

				}?>

				<li>
					<a href="#procon" rel="procon" id='procon_click'>
					<span><!--<img src='imagens/lupa.png' width='10' align=absmiddle>-->Procon/Jec.</span>
					</a>
				</li><?php

			}?>
			<li>
				<a href="#onde_comprar" rel="onde_comprar" id='onde_comprar_click'>
				<span><!--<img src='imagens/lupa.png' width='10' align=absmiddle>-->Onde Comprar</span>
				</a>
			</li><?php

			if ($login_fabrica == 45) {?>
				<li>
					<a href="#garantia" rel="garantia" id='garantia_click'>
					<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Garantia</span>
					</a>
				</li><?php
			}

			if ($login_fabrica==1 and strlen($callcenter) > 0) {?>
				<li>
					<a href="#hd_posto" rel="hd_posto" id='hd_posto_click'>
					<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->HD Posto</span>
					</a>
				</li><?php
			}

			if (in_array($login_fabrica, array(59,81,114))) {?>

				<li>
					<a href="#ressarcimento" rel="ressarcimento" id='ressarcimento_click'>
					<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Ressarcimento</span>
					</a>
				</li><?php

				if (!in_array($login_fabrica, array(81,114))) {?>
					<li>
						<a href="#sedex_reverso" rel="sedex_reverso" id='sedex_reverso_click'>
						<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Sedex Reverso</span>
						</a>
					</li><?php
				}

			}

			if (1 == 2 /* $login_fabrica==46 OR $login_fabrica == 11 Samuel Tirou esta aba, Troca de Produto  somente permitido na OS, no pode ser feita no call-center*/) {?>

				<li>
					<a href="#troca_produto" rel="troca_produto" id='troca_produto_click'>
					<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle">-->Troca Prod.</span>
					</a>
				</li><?php

			}

			if ($login_fabrica == 24) {?>
				<li>
					<a href="#outros_assuntos" rel="outros_assuntos" id='outros_assuntos_click'>
					<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Outros Assuntos</span>
					</a>
				</li><?php
			}?>

			<?php
			if ($login_fabrica == 94) {?>
				<li>
					<a href="#indicacao_rev" rel="indicacao_rev" id='indicacao_rev_click'>
					<span>Indicação Revenda</span>
					</a>
				</li>
				<li>
					<a href="#indicacao_at" rel="indicacao_at" id='indicacao_at_click'>
					<span>Indicação A.T.</span>
					</a>
				</li>
			<?php
			}

			if ($login_fabrica == 74) {
			?>
				<li>
					<a href="#informacoes" rel="informacoes" id ='informacoes_click'>
					<span>Informacoes</span>
					</a>
				</li>
			<?php
			}
			?>
		</ul><?php

		if ($login_fabrica == 25) {?>

			<div id="extensao" class='tab_content'>

				<div rel='div_ajuda' style='display:inline; Position:relative;'><?php

				if (strlen($callcenter) == 0) {?>

					<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
						<tr>
							<td align='right' width='150'></td>
							<td align='right' width='55'>
								<img src='imagens/ajuda_call.png' align=absmiddle>
							</td>
							<td align='center'>
								<STRONG>Oferecer Garantia Estendida.</STRONG><br />
								O Sr.(a) gostaria de cadastrar a garantia estendida do seu produto?
							</td>
							<td align='right' width='150'></td>
						</tr>
					</table><?php

				}?>

				Informações do Produto

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left'><strong>Referência:</strong></td>
						<td align='left'>
							<input name="produto_referencia_es" id="produto_referencia_es"  class="input"  value='<?echo $produto_referencia ;?>'
							onblur="fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_es,document.frm_callcenter.produto_nome_es,'referencia')" type="text" size="10" maxlength="15">
							<img src='imagens/lupa.png' border='0' align='absmiddle'
							style='cursor: pointer'
							onclick="fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')">
						</td>
						<td align='left'><strong>Descrição:</strong></td>
						<td align='left'>
							<input type='hidden' name='produto' value="<? echo $produto; ?>">
							<input name="produto_nome_es"  id="produto_nome_es"  class="input" value='<?echo $produto_nome ;?>'
							onblur="fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_es,document.frm_callcenter.produto_nome_es,'descricao')" type="text" size="30" maxlength="500">
							<img src='imagens/lupa.png' border='0' align='absmiddle'
							style='cursor: pointer'
							onclick="fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')">
						</td>
						<td align='left'><strong>Série:</strong></td>
						<td align='left'>
							<input name="serie_es" id='serie_es' maxlength="20" class="input"  value='<?echo $serie ;?>'>
						</td>
						<td align='left'><?php
						if (strlen($callcenter) == 0) {?>
							<INPUT TYPE="button" onClick='fn_verifica_garantia();' name='Verificar' value='Verificar'><?php
						}?>
						</td>
					</tr>
					<tr>
						<td colspan='7'>

							<div id='div_estendida'><?php

							if (strlen($callcenter) > 0) {

								if (strlen($serie) > 0) {

									include "conexao_hbtech.php";

									$sql = "SELECT idNumeroSerie ,
													idGarantia   ,
													revenda      ,
													cnpj
											FROM numero_serie
											WHERE numero = '$serie'";

									$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

									if (mysql_num_rows($res) > 0) {

										$idNumeroSerie = mysql_result($res, 0, 'idNumeroSerie');
										$idGarantia    = mysql_result($res, 0, 'idGarantia');
										$es_revenda    = mysql_result($res, 0, 'revenda');
										$es_cnpj       = mysql_result($res, 0, 'cnpj');

										if (strlen($idGarantia) > 0) {

											$sql = "SELECT	nf                ,
															dataCompra        ,
															municipioCompra   ,
															estadoCompra      ,
															dataNascimento    ,
															estadoCivil       ,
															filhos            ,
															sexo              ,
															dddComercial      ,
															foneComercial     ,
															dddCelular        ,
															foneCelular       ,
															prefMusical
													FROM garantia
													WHERE idGarantia = $idGarantia; ";

											$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

											if (mysql_num_rows($res) > 0) {

												$es_nf              = mysql_result($res, 0, 'nf');
												$es_dataCompra      = mysql_result($res, 0, 'dataCompra');
												$es_municipioCompra = mysql_result($res, 0, 'municipioCompra');
												$es_estadoCompra    = mysql_result($res, 0, 'estadoCompra');
												$es_dataNascimento  = mysql_result($res, 0, 'dataNascimento');
												$es_estadoCivil     = mysql_result($res, 0, 'estadoCivil');
												$es_filhos          = mysql_result($res, 0, 'filhos');
												$es_sexo            = mysql_result($res, 0, 'sexo');
												$es_dddComercial    = mysql_result($res, 0, 'dddComercial');
												$es_foneComercial   = mysql_result($res, 0, 'foneComercial');
												$es_telComercial    = "($es_dddComercial) $es_foneComercial";
												$es_dddCelular      = mysql_result($res, 0, 'dddCelular');
												$es_foneCelular     = mysql_result($res, 0, 'foneCelular');
												$es_prefMusical     = mysql_result($res, 0, 'prefMusical');
												$es_telCelular      = "($es_dddCelular) $es_foneCelular";

												$es_dataCompra = converte_data($es_dataCompra);
												$es_dataCompra = str_replace("-","/",$es_dataCompra);

												$es_dataNascimento = converte_data($es_dataNascimento);
												$es_dataNascimento = str_replace("-","/",$es_dataNascimento);

											}

											echo "<input name='es_id_numeroserie' id='es_id_numeroserie' value='$idNumeroSerie' type='hidden'>";

											echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style=' font-size:10px'>";
												echo "<tr>";
													echo "<td><B>Cnpj Revenda:</B></td>";
													echo "<td><input name='es_revenda_cnpj' id='es_revenda_cnpj' class='input' value='$es_cnpj' type='text' maxlength='14' size='15' readonly></td>";
													echo "<td><B>Nome Revenda:</B></td>";
													echo "<td><input name='es_revenda' id='es_revenda' class='input' value='$es_revenda' type='text' maxlength='50' size='25' readonly></td>";
													echo "<td><B>Nota Fiscal:</B></td>";
													echo "<td><input name='es_nota_fiscal' id='es_nota_fiscal' class='input' value='$es_nf' type='text' maxlength='8' size='8'> </td>";
												echo "</tr>";

												echo "<tr>";
													echo "<td><B>Data Compra:</B></td>";
													echo "<td><input name='es_data_compra' id='es_data_compra' class='input' value='$es_dataCompra' type='text' maxlength='10' size='12'></td>";
													echo "<td><B>Municipio Compra:</B></td>";
													echo "<td><input name='es_municipiocompra' id='es_municipiocompra' class='input' value='$es_municipioCompra' type='text' maxlength='255' size='25'></td>";
													echo "<td><B>Estado Compra:</B></td>";
													echo "<td>";
														echo "<select name='es_estadocompra' id='es_estadocompra' style='width:52px; font-size:9px' >";
														 $ArrayEstados = array('AC','AL','AM','AP',
																					'BA','CE','DF','ES',
																					'GO','MA','MG','MS',
																					'MT','PA','PB','PE',
																					'PI','PR','RJ','RN',
																					'RO','RR','RS','SC',
																					'SE','SP','TO'
																				);

														for ($i = 0; $i <= 26; $i++) {
															echo"<option value='".$ArrayEstados[$i]."'";
															if ($es_estadoCompra == $ArrayEstados[$i]) echo " selected";
															echo ">".$ArrayEstados[$i]."</option>\n";
														}

														echo "</select>";
													echo "</td>";
												echo "</tr>";

												echo "<tr>";
													echo "<td><B>Data Nascimento:</B></td>";
													echo "<td><input name='es_data_nascimento' id='es_data_nascimento' class='input' value='$es_dataNascimento' type='text' maxlength='10' size='12'></td>";
													echo "<td><B>Estado Civil:</B></td>";
													echo "<td>";
														echo "<select name='es_estadocivil' id='es_estadocivil' style='width:100px; font-size:9px' >";
														echo "<option value=''></option>";
														echo "<option value='0' ";
														if ($es_estadoCivil=="0")echo "SELECTED";
														echo ">Solteiro(a)</option>";
														echo "<option value='1' ";
														if ($es_estadoCivil=="1")echo "SELECTED";
														echo ">Casado(a)</option>";
														echo "<option value='2' ";
														if ($es_estadoCivil=="2")echo "SELECTED";
														echo ">Divorciado(a)</option>";
														echo "<option value='3' ";
														if ($es_estadoCivil=="3")echo "SELECTED";
														echo ">Viuvo(a)</option>";
														echo "</select>";
													echo "</td>";
													echo "<td><B>Sexo:</B></td>";
													echo "<td>";
														echo "<INPUT TYPE='radio' NAME='es_sexo' ";
														if ($es_sexo == "0") echo "CHECKED ";
														echo "value='0'>M. ";
														echo "<INPUT TYPE='radio' NAME='es_sexo' ";
														if ($es_sexo == "1") echo "CHECKED ";
														echo " value='1'>F. ";
													echo "</td>";
												echo "</tr>";

													echo "<tr>";
														echo "<td><B>Filhos:</B></td>";
														echo "<td>";
															echo "<INPUT TYPE='radio' NAME='es_filhos' ";
															if ($es_filhos == "0") echo "CHECKED ";
															echo "value='0'>Sim ";
															echo "<INPUT TYPE='radio' NAME='es_filhos' ";
															if ($es_filhos == "1") echo "CHECKED ";
															echo "value='1'>No ";
														echo "</td>";
														echo "<td><B>Fone Comercial:</B></td>";
														echo "<td><input name='es_fonecomercial' id='es_fonecomercial' class='input' value='$es_telComercial' type='text' maxlength='14' size='16'></td>";

														echo "<td><B>Celular:</B></td>";
														echo "<td>";
															echo "<input name='es_celular' id='es_celular' class='input' value='$es_telCelular' type='text' maxlength='14' size='16'>";
														echo "</td>";
													echo "</tr>";

													echo "<tr>";
														echo "<td colspan='6'><B>Preferência Musical:</B> ";
															echo "<input name='es_preferenciamusical' id='es_preferenciamusical' class='input' value='$es_prefMusical' type='text' maxlength='255' size='100'>";
														echo "</td>";
													echo "</tr>";

												echo "</table>";


											}

										} else {
											echo "Número de série não encontrado nas vendas";
										}

									}

								}?>

								</div>

							</td>
						</tr>
						<tr>
							<td align='left'><strong>Descrição:</strong></td>
							<td colspan='6'>
								<TEXTAREA NAME="reclamado_es" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
							</td>
						</tr>
					</table>

				</div>

			</div><?php

		}

		if ($login_fabrica == 5 and strlen($callcenter) > 0) { // hd 58796
			$read = " readonly='readonly' ";
		}?>

		<div id="reclamacao_produto" class='tab_content'>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>

			<!-- Incluído solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 --><?php

			if ($login_fabrica == 2) {?>

				Tipo da Produro/Defeito
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="aquisicao_mat" <?php
								if ($natureza_chamado2 == 'aquisicao_mat') {echo "CHECKED";}?>> AQUISIÇÃO DE MATERIAIS
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="aquisicao_prod" <?php
								if ($natureza_chamado2 == 'aquisicao_prod') { echo "CHECKED";}?>>AQUISIÇÃO DE PRODUTO
						</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="indicacao_posto" <?php
								if ($natureza_chamado2 == 'indicacao_posto') { echo "CHECKED";}?>>INDICAÇÃO DE POSTO
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="solicitacao_manual"
								<?PHP if ($natureza_chamado2 == 'solicitacao_manual') { echo "CHECKED";}?>>SOLICITAÇÕES DE MANUAIS / CATÁLOGOS
						</td>
					</tr>
				</table>

				<!-- FIM solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 --><?php

			} else {?>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
					<tr>
						<td align='right' width='150'></td>
						<td align='right' width='55'>
							<img src='imagens/ajuda_call.png' align=absmiddle>
						</td>
						<td align='center'>
							<STRONG>Confirmar ou perguntar a reclamação.</STRONG><br /><?php

							$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='4' AND fabrica = $login_fabrica";
							$pe  = pg_query($con, $sql);

							if (pg_num_rows($pe) > 0) {
								echo pg_fetch_result($pe,0,0);
							} else {
								echo ($login_fabrica == 11) ? "Qual a sua solicitação SR.(a)?<BR>" : "Qual a sua reclamação SR.(a)?<BR>";?> ou<BR> O Sr.(a) diz que...., correto?<?php
							}?>
						</td>
						<td align='right' width='150'></td>
					</tr>
				</table><?php

			}?>
			</div>

			<?php
			if (in_array($login_fabrica, array(81, 114, 122, 123, 128))) {
			?>
				<br />
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td style="width: 150px;">
							<strong>Origem da Reclamação:</strong>
						</td>
						<td>
							<input type="checkbox" name="origem_reclamacao[]" value="0800" <?=((!isset($origem_reclamacao) && !isset($_GET["hd_chamado"]) && !$_POST) || (in_array("0800", $origem_reclamacao))) ? "CHECKED" : ""?> /> 0800
							<input type="checkbox" name="origem_reclamacao[]" value="reclame_aqui" <?=(in_array("reclame_aqui", $origem_reclamacao)) ? "CHECKED" : ""?> /> Reclame Aqui
							<input type="checkbox" name="origem_reclamacao[]" value="facebook" <?=(in_array("facebook", $origem_reclamacao)) ? "CHECKED" : ""?> /> Facebook
							<input type="checkbox" name="origem_reclamacao[]" value="twitter" <?=(in_array("twitter", $origem_reclamacao)) ? "CHECKED" : ""?> /> Twitter
							<input type="checkbox" name="origem_reclamacao[]" value="procon" <?=(in_array("procon", $origem_reclamacao)) ? "CHECKED" : ""?> /> Procon
						<?php if ($login_fabrica == 122 ){ 	?>
							<input type="checkbox" name="origem_reclamacao[]" value="fora_garantia" <?=(in_array("fora_garantia", $origem_reclamacao)) ? "CHECKED" : ""?> /> Fora de Garantia
						<?php } ?>
							<input type="checkbox" name="origem_reclamacao[]" value="outro" <?=(in_array("outro", $origem_reclamacao)) ? "CHECKED" : ""?> /> Outros
							<input type="text" name="origem_reclamacao_outro" value="<?=$origem_reclamacao_outro?>" <?=(in_array("outro", $origem_reclamacao)) ? "style='display: inline;'" : "style='display: none;'"?> />
						</td>
					</tr>
				</table>
				<br />
			<?php
			}
			?>

			Informações do Produto <?php

			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td width="350px">
						Assunto: <select name="callcenter_assunto[reclamacao_produto]" id="callcenter_assunto" class="input_req">
						<option value=''>>>> Escolha <<<</option> <?php

						foreach ($assuntos["PRODUTOS"] AS $label => $valor) {

							if ($valor == $callcenter_assunto) {
								$selected = "selected";
							} else {
								$selected = "";
							}

							echo " <option value='$valor' $selected>PRODUTOS >> $label</option>";

						}

						foreach ($assuntos["MANUAL"] AS $label => $valor) {

							if ($valor == $callcenter_assunto) {
								$selected = "selected";
							} else {
								$selected = "";
							}

							echo " <option value='$valor' $selected>MANUAL >> $label</option>";

						}

						// HD 751794
						if (strlen($callcenter) > 0){
							$sql = "SELECT CASE WHEN orientacao_uso IS TRUE THEN 'checked' ELSE '' END as orientacao
								FROM tbl_hd_chamado_extra WHERE hd_chamado = $callcenter";
							$res = pg_query($con,$sql);

							$orientacao_uso = pg_result($res,0,0);
						}

						if ($_POST['orientacao_uso'] == 't') {

								$orientacao_uso = 'checked';

						} else if ( strlen($btn_acao) > 0 ) {

							$orientacao_uso = '';

						} ?>
						</select>
						</td>
						<td style="display:none;" id="orientacao_uso">
							<input type="checkbox" name="orientacao_uso" value="t" id="orientacao_uso_check" <?=$orientacao_uso?> />&nbsp;
							<label for="orientacao_uso_check">Orientação de Uso</label>
						</td>
					</tr>
				</table><?php

			} ?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<?php
				if($login_fabrica == 11 ){
					$titulo_combo =  "Motivo";
					$disabled = (!empty($hd_motivo_ligacao) and !empty($callcenter)) ? " AND hd_motivo_ligacao = $hd_motivo_ligacao " : "";
			?>
					<tr>
						<td>
							<strong><?=$titulo_combo?>: </strong>
						</td>
						<td>
							<select name='hd_motivo_ligacao' id='hd_motivo_ligacao' >
								<? if (empty($callcenter)){?>
								<option value=''>Escolha o motivo</option><?php
								}
								$sqlLigacao = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica AND ativo IS TRUE $disabled; ";

								$resLigacao = pg_query($con,$sqlLigacao);
									for ($i = 0; $i < pg_num_rows($resLigacao); $i++) {
										$hd_motivo_ligacao_aux = pg_result($resLigacao,$i,'hd_motivo_ligacao');
										$motivo_ligacao    = pg_result($resLigacao,$i,'descricao');
										echo " <option value='".$hd_motivo_ligacao_aux."' ".($hd_motivo_ligacao_aux == $hd_motivo_ligacao ? "selected='selected'" : '').">$motivo_ligacao</option>";

									}?>
								</select>
						</td>
					</tr>
			<?php
				}
			?>
			<tr>
				<td><?php
				//HD 201434 - Verifica como a fabrica trabalha com defeito reclamado na configuracao da tbl_fabrica e
				//			  trata devidamente para mostrar o tipo de campo correto
				$sql_defeito = "
				SELECT
				pedir_defeito_reclamado_descricao

				FROM
				tbl_fabrica

				WHERE
				fabrica=$login_fabrica
				AND pedir_defeito_reclamado_descricao IS TRUE";
				$res_defeito_reclamado = pg_query($con, $sql_defeito);

				//HD 219939: Para a Cadence deverá selecionar o defeito reclamado no CallCenter e digitar na OS
				if (pg_num_rows($res_defeito_reclamado) and !in_array($login_fabrica,array(11,35,81,114,115,116,117,125,128,134,137,142))) {


					echo "<strong>Defeitos</strong>";
					echo "</td>";
					echo "<td align='left'><input name='hd_extra_defeito' id='hd_extra_defeito' size='50' class='input' value='$hd_extra_defeito'>";
					echo "</td>";

				} else {

					if ($login_fabrica <> 52) {

						if (in_array($login_fabrica, array(11,81,114,115,116,117,125,128,134,137,142))) {?>
							<!-- <a href="javascript:mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value)">
							</a> -->
								<strong>Defeitos Reclamados:</strong>
								<?php
						} else {

							?>
								<a href="javascript:mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value)">Defeitos</a><?php

						}?>

					</td>
					<td align='left' colspan='5' width='630' valign='top'>

						<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>

						<?php

						if (strlen($defeito_reclamado) > 0 or (in_array($login_fabrica,array(11,74,81,114,115,116,117,125,128,134)) and $produto_referencia)) {

							if (!in_array($login_fabrica, array(11,74,81,114,115,116,117,125,128,134,137,142))) {

								$sql = "SELECT defeito_reclamado,
												descricao
										FROM tbl_defeito_reclamado
										WHERE defeito_reclamado = $defeito_reclamado";

								$res = pg_query($con, $sql);
								echo "teste";
								if (pg_num_rows($res) > 0) {
									$defeito_reclamado_descricao = pg_fetch_result($res,0,descricao);
									echo "<input type='radio' name='defeito_reclamado' id='defeito_reclamado' checked value='$defeito_reclamado'><font size='1'>$defeito_reclamado_descricao</font>";
								}

							} else { ?>

								<select id="defeito_reclamado" name="defeito_reclamado" >
									<option value="">Selecione o Defeito</option>

									<?
										$sql_familia_produto = "
											SELECT familia
											FROM tbl_produto
											JOIN tbl_familia using (familia)
											WHERE tbl_produto.referencia = '$produto_referencia'
											AND tbl_familia.fabrica = $login_fabrica; ";

										$res_familia_produto = pg_query($con,$sql_familia_produto);

										$familia_produto = (pg_numrows($res_familia_produto) > 0) ? pg_result($res_familia_produto,0,0) : null;

										if ($familia_produto) {

											$sql_diagnostico_reclamado = "
												SELECT DISTINCT tbl_defeito_reclamado.defeito_reclamado, tbl_defeito_reclamado.descricao
												FROM   tbl_defeito_reclamado
												JOIN   tbl_diagnostico on (tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado and tbl_diagnostico.fabrica = $login_fabrica and tbl_diagnostico.ativo is true)
												WHERE tbl_diagnostico.familia      = $familia_produto
												AND  tbl_defeito_reclamado.fabrica = $login_fabrica
												AND  tbl_defeito_reclamado.ativo is true
												ORDER BY tbl_defeito_reclamado.descricao";

											$res_diagnostico_reclamado = pg_query($con, $sql_diagnostico_reclamado);

											if (pg_num_rows($res_diagnostico_reclamado) > 0) {?>
												<?php
													for ($i = 0;$i < pg_num_rows($res_diagnostico_reclamado); $i++){
														$xdefeito_reclamado           = pg_result($res_diagnostico_reclamado, $i, 0);
														$xdefeito_reclamado_descricao = pg_result($res_diagnostico_reclamado, $i, 1);
														$selected = ($defeito_reclamado == $xdefeito_reclamado) ? "SELECTED" : null;?>

														<option value="<?echo $xdefeito_reclamado?>" <?=$selected?> > <?echo $xdefeito_reclamado_descricao?>  </option><?php
													}
												}

											}

										}

									}
									?>
								</select>

							</div><?php

					}

				} ?>
				</td>
			</tr>

			<tr>
				<td align='left' valign='top' width='80'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_produto_x" ROWS="6" COLS="110"  class="input" style='display: none;font-size:10px' <? echo $read; ?>>
					<?
						#94971
						if($_GET['herdar']=='sim' AND $login_fabrica==59){
							$sql2 ="SELECT		tbl_hd_chamado_extra.reclamado
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
									LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
									LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
									LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = 59
									LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
									LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
									LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
									LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
									WHERE tbl_hd_chamado.fabrica_responsavel = 59
									AND tbl_hd_chamado.hd_chamado = $Id";
							$res2 = pg_query($con,$sql2);

							if(pg_num_rows($res2)>0){
								$reclamado2       = pg_fetch_result($res2,0,reclamado);
							}
							echo $reclamado2;
						}
					?>
					</TEXTAREA>
					<?php
					if ($login_fabrica == 74 && !empty($callcenter) && $admin != 6437) {
						$bloqueia_reclamado_produto = "readonly";

						echo $reclamado;
						echo "<input type='hidden' name='reclamado_produto' value='$reclamado' />";
					}else{
						if ($login_fabrica == 74 && !empty($callcenter) && isset($_POST["reclamado_produto"])) {
							$reclamado = $_POST["reclamado_produto"];
						}

						$read = ($status_interacao == "Resolvido" || strlen($reclamado) > 0) ? "readonly='readonly'" : "";

						?>
						<TEXTAREA NAME="reclamado_produto" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read; ?> <?=$bloqueia_reclamado_produto?> ><?echo $reclamado ;?></TEXTAREA>
					<? } ?>
				</td>
			</tr>

			<!-- HD 196942: Controle de autorização de postagem -->
			<?
			if ($login_fabrica == 11) {
			?>
			<tr>
				<td align='left' valign='top' title="Data programada para resolver este atendimento.">
					<strong>Data Programada:</strong>
				</td>
				<td align='left' colspan='5' title="Data programada para resolver este atendimento.">
					<input type="text" class="frm" rel="data" name="data_programada" style="width: 80px;" value="<?=$data_programada?>" />
				</td>
			</tr>
			<tr>
				<td align='left' valign='top'><strong>Postagem:</strong></td>
				<td align='left' colspan='5'>
				<?

				if (strlen($callcenter)) {
					$sql = "
					SELECT
					TO_CHAR(tbl_hd_chamado_postagem.data, 'DD/MM/YYYY HH24:MI:SS') AS data,
					TO_CHAR(tbl_hd_chamado_postagem.data_aprovacao, 'DD/MM/YYYY HH24:MI:SS') AS data_aprovacao,
					tbl_hd_chamado_postagem.aprovado,
					tbl_hd_chamado_postagem.admin,
					tbl_admin.nome_completo AS admin_nome_completo,
					tbl_admin.login AS admin_login,
					tbl_hd_chamado_postagem.motivo,
					tbl_hd_chamado_postagem.obs,
					tbl_hd_chamado_postagem.codigo_postagem

					FROM
					tbl_hd_chamado_postagem
					LEFT JOIN tbl_admin ON tbl_hd_chamado_postagem.admin=tbl_admin.admin

					WHERE
					hd_chamado=$callcenter
					";
					$res_postagem = pg_query($con, $sql);
				}

				//Verifica se já existe solicitação de postagem cadastrada
				if (strlen($callcenter) && pg_num_rows($res_postagem)) {
					$postagem_aprovado = pg_result($res_postagem, 0, aprovado);
					$postagem_data = pg_result($res_postagem, 0, data);
					$postagem_data_aprovacao = pg_result($res_postagem, 0, data_aprovacao);
					$postagem_admin_nome_completo = pg_result($res_postagem, 0, admin_nome_completo);
					$postagem_motivo = pg_result($res_postagem, 0, motivo);
					$postagem_obs = pg_result($res_postagem, 0, obs);
					$postagem_codigo_postagem = pg_result($res_postagem, 0, codigo_postagem);

					switch ($postagem_aprovado) {

						case 't':
							if ($_POST["hd_chamado_codigo_postagem"]) {
								$hd_chamado_codigo_postagem = $_POST["hd_chamado_codigo_postagem"];
							} else {
								$hd_chamado_codigo_postagem = $postagem_codigo_postagem;
							}

							if ($postagem_obs) {
								$postagem_obs = "- <u>Observações:</u> $postagem_obs";
							}

							echo "<div style='display:inline; background: #44FF44; padding: 2px;'>Aprovado por $postagem_admin_nome_completo em $postagem_data_aprovacao - <u>Motivo:</u> $postagem_motivo $postagem_obs</div>";

							echo "</td></tr>";
							echo "<tr>
							<td align='left' valign='top'><strong>Código de Postagem:</strong></td>
							<td align='left' colspan='5'>
							<input class='input' type='text' name='hd_chamado_codigo_postagem' id='hd_chamado_codigo_postagem' value='$hd_chamado_codigo_postagem' size='30'>";
						break;

						case 'f':
							if ($postagem_obs) {
								$postagem_obs = "- <u>Observações:</u> $postagem_obs";
							}

							echo "<div style='display:inline; background: #FFAA99; padding: 2px;'>Reprovado por $postagem_admin_nome_completo em $postagem_data_aprovacao - <u>Motivo:</u> $postagem_motivo $postagem_obs</div>";
						break;

						default:
							echo "Em aprovação desde $postagem_data $postagem_aprovado";
					}

				} else {

					if ($_POST["hd_chamado_postagem"] == "sim") {
						$checked = "checked";
					} else {
						$checked = "";
					}

					echo "<input type='checkbox' name='hd_chamado_postagem' id='hd_chamado_postagem' value='sim' $checked onchange=''> este chamado precisa de postagem";
				} ?>
				</td>
			</tr> <?
			} ?>
			<!-- FIM - HD 196942: Controle de autorização de postagem -->

			</table>

			<?php if (in_array($login_fabrica, array(30))) : // HD 674943 ?>
				<br /><div id="questionario">
					<?php include 'pesquisa_satisfacao.php'; ?>
				</div> <br />
			<?php endif; // HD 674943 - FIM

			$aEsconderDuvidaProduto = array(2);

			if (in_array($login_fabrica, $aEsconderDuvidaProduto)):?>
				<div style="display:none">
			<?php endif; ?>
			Consultar FAQs sobre o Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='60'><strong>Dúvida:</strong></td>
				<td align='left'>
					<input name="faq_duvida_produto"  id='faq_duvida_produto' size='50' class="input" value='<?echo $faq_duvida ;?>'>
					<input  id="faq_duvida_produto_btn" class="input"  type="button" name="bt_localizar" value='Localizar' onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_produto')">
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<div id='div_faq_duvida_produto' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
					</div>
				</td>
			</tr>
			</table>
			<?php if ( in_array($login_fabrica, $aEsconderDuvidaProduto) ): ?>
				</div>
			<?php endif;
			if (1 ==2 /*$login_fabrica != 45 AND $login_fabrica != 3 Samuel retirou isto...a consulta do posto mais prximo  atravs do Mapa da Rede */ ) { ?>
				Consultar Posto Autorizado
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' colspan='6'><strong><a href="javascript: fnc_pesquisa_at_proximo('<?echo $login_fabrica?>')" title="Localize o Posto Autorizado" >Clique aqui para consultar o posto autorizado mais próximo do consumidor</a></strong></td>
					</tr>
				</table><?PHP
				}
			?>
		</div>

	<? if($login_fabrica <> 2){ ?>

		<div id="reclamacao_empresa" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='5' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a sua reclamação SR.(a)?<BR>	ou<BR> O Sr.(a) diz que...., correto?";
						}?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div> <?PHP
			if ($login_fabrica == 2) {

				if ($natureza_chamado2 == 'reclamacao_at') {
					$mostra_reclamacao = "Assitência Técnica";
				} else if ($natureza_chamado2 == 'reclamacao_produto') {
					$mostra_reclamacao = "o Produto";
				} else if ($natureza_chamado2 == 'reclamacao_revenda') {
					$mostra_reclamacao = "a Loja";
				} else if ($natureza_chamado2 == 'reclamacao_enderecos') {
					$mostra_reclamacao = "a Lista de Endereços Desatualizada";
				}

			}

			if ($login_fabrica == 24) {
				echo "Informações";
			} else {
				echo "Informações da Reclamação";
			}

			if ($login_fabrica == 2 and strlen($mostra_reclamacao) > 0) {
				echo "Sobre $mostra_reclamacao";
			}

			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {?>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td>
						Assunto: <select name="callcenter_assunto[reclamacao_empresa]" id="callcenter_assunto" class="input_req">
						<option value=''>>>> Escolha <<<</option><?php

						foreach ($assuntos["EMPRESA"] AS $label => $valor) {

							if ($valor == $callcenter_assunto) {
								$selected = "selected";
							} else {
								$selected = "";
							}

							echo " <option value='$valor' $selected>EMPRESA >> $label</option>";

						} ?>
						</select>
						</td>
					</tr>
				</table><?php

			} ?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' valign='top' width='80'><strong>Reclamação:</strong></td>
					<td align='left' colspan='5'>
						<?php
						if ($login_fabrica == 74 && !empty($callcenter) && $admin != 6437) {
							$bloqueia_reclamado_produto = "readonly";

							echo $reclamado;
							echo "<input type='hidden' name='reclamado_empresa' value='$reclamado' />";
						}else{
							if ($login_fabrica == 74 && !empty($callcenter) && isset($_POST["reclamado_empresa"])) {
								$reclamado = $_POST["reclamado_empresa"];
							}

							?>
							<TEXTAREA NAME="reclamado_empresa" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read;?>><?echo $reclamado ;?></TEXTAREA>
						<? } ?>
					</td>
				</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
					<tr>
						<td align='right' width='150'></td>
						<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
						<td align='center'><STRONG>Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.
						</td>
						<td align='right' width='150'></td>
					</tr>
				</table>
			</div>
		</div>

		<div id="reclamacao_at" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
					<tr>
						<td align='right' width='150'></td>
						<td align='right' width='55'>
							<img src='imagens/ajuda_call.png' align=absmiddle>
						</td>
						<td align='center'>
							<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR><?php

							$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='6' AND fabrica = $login_fabrica";
							$pe  = pg_query($con, $sql);

							if (pg_num_rows($pe) > 0) {
								echo pg_fetch_result($pe,0,0);
							} else {
								echo "Qual a sua reclamação SR.(a)?<BR> ou<BR> O Sr.(a) diz que...., correto?";
							}?>
						</td>
						<td align='right' width='150'></td>
					</tr>
				</table>
			</div>

			Informações da Assistência
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'>
					<input name="codigo_posto"  id="codigo_posto" class="input"  value='<?echo $codigo_posto ;?>'
					onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'codigo');" type="text" size="15" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.produto_nome,'codigo');">
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input name="posto_nome" id="posto_nome" class="input" value='<?echo $posto_nome ;?>'
					onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');" type="text" size="35" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');">
				</td>
				<?if ($login_fabrica <> 11) { // HD 14549?>
				<td align='left'><strong>OS:</strong></td>
				<td align='left'>
					<input name="os"  class="input"  value='<?echo $sua_os ;?>'>
				</td>
				<? } ?>
			</tr>
			</table>

			<? if($login_fabrica==11){ ?>
				Tipo da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="reclamacao_at" <?PHP if ($natureza_chamado2 == 'reclamacao_at' OR $natureza_chamado2 =='') { echo "CHECKED";}?>> RECLAMAÇÃO DA  ASSIST. TÉCN.</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="reclamacao_at_info" <?PHP if ($natureza_chamado2 == 'reclamacao_at_info') { echo "CHECKED";}?>>INFORMAÇÕES DE A.T</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="mau_atendimento" <?PHP if ($natureza_chamado2 == 'mau_atendimento') { echo "CHECKED";}?>>MAU ATENDIMENTO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="posto_nao_contribui" <?PHP if ($natureza_chamado2 == 'posto_nao_contribui') { echo "CHECKED";}?>>POSTO NÃO CONTRIBUI COM INFORMAÇÕES</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="demonstra_desorg" <?PHP if ($natureza_chamado2 == 'demonstra_desorg') { echo "CHECKED";}?>>DEMONSTRA DESORGANIZAÇÃO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="possui_bom_atend" <?PHP if ($natureza_chamado2 == 'possui_bom_atend') { echo "CHECKED";}?>>POSSUI BOM ATENDIMENTO</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="demonstra_org" <?PHP if ($natureza_chamado2 == 'demonstra_org') { echo "CHECKED";}?>>DEMONSTRA ORGANIZAÇÃO</td>
				</tr>
				</table>
			<? }
				echo ($login_fabrica==11 || $login_fabrica == 24) ? "Informações" : "Informações da Reclamação";

			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {?>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td>
							Assunto: <select name="callcenter_assunto[reclamacao_at]" id="callcenter_assunto" class="input_req">
							<option value=''>>>> Escolha <<<</option><?php

							foreach ($assuntos["ASSISTÊNCIA TÉCNICA"] AS $label => $valor) {

								if ($valor == $callcenter_assunto) {
									$selected = "selected";
								} else {
									$selected = "";
								}

								echo " <option value='$valor' $selected>ASSISTÊNCIA TÉCNICA >> $label</option>";
							} ?>
							</select>
						</td>
					</tr>
				</table><?php

			}?>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' valign='top' width='80'><strong>Reclamação:</strong></td>
					<td align='left' colspan='5'>
						<?php
						if ($login_fabrica == 74 && !empty($callcenter) && $admin != 6437) {
							$bloqueia_reclamado_produto = "readonly";

							echo $reclamado;
							echo "<input type='hidden' name='reclamado_at' value='$reclamado' />";
						}else{
							if ($login_fabrica == 74 && !empty($callcenter) && isset($_POST["reclamado_at"])) {
								$reclamado = $_POST["reclamado_at"];
							}

							?>
							<TEXTAREA NAME="reclamado_at" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?echo $read;?>><?echo $reclamado ;?></TEXTAREA>
						<? } ?>

					</td>
				</tr>
			</table>
			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
		</div>
		<?}

		if ($login_fabrica == 2) {?>

		<div id="reclamacoes" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				Tipo da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_revenda" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_revenda' OR $natureza_chamado2 == '') { echo "CHECKED";}?>> RECLAMAÇÃO DA LOJA</td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_at" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_at') { echo "CHECKED";}?>> RECLAMAÇÃO DA ASSIST. TÉCN.</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_enderecos" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_enderecos') { echo "CHECKED";}?>> RECL. LISTA ENDEREÇOS DESATUALIZADA </td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_produto" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_produto') { echo "CHECKED";}?>> RECLAMAÇÃO DO PRODUTO</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_conserto" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_conserto') { echo "CHECKED";}?>> RECLAMAÇÃO DE CONSERTOS </td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_posto_aut" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_posto_aut') { echo "CHECKED";}?>> RECLAMAÇÃO DE POSTOS AUTORIZADOS</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_orgao_ser" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_orgao_ser') { echo "CHECKED";}?>> RECLAMAÇÃO DE ÓRGÃO DE SERVIÇO</td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="repeticao_chamado" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'repeticao_chamado') { echo "CHECKED";}?>> REPETIÇÃO DE CHAMADO</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_outro" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_outro') { echo "CHECKED";}?>> OUTRAS RECLAMAÇÕES</td>
					</tr>
				</table>

				<div id="info_posto" style=" <?php echo ($natureza_chamado2 == 'reclamacao_at') ? "display:inline" : "display:none"; ?> ;">

				<br/>
				Informações da Assistência
				<table width='100%' class="tab_content" border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td>
							<strong>Código do Posto:&nbsp;</strong>
							<input name="codigo_posto" id="codigo_posto" class="input" value='<?echo $codigo_posto ;?>' <?php
								if (strlen($codigo_posto)>0){
									echo " disabled";
								} ?>
								onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'codigo');" type="text" size="15" maxlength="15"> <img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.produto_nome,'codigo');">
						</td>
						<td>
							<strong>Nome do Posto:&nbsp;</strong>
							<input name="posto_nome" class="input" value='<?echo $posto_nome ;?>'
							<?php
								if (strlen($posto_nome)>0){
									echo " disabled";
								} ?>
								onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');" type="text" size="35" maxlength="500"> <img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');">
						</td>
					</tr>
				</table>
				</div>

				<br>
				Informações da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='right' width='35%'><strong>Reclamação:</strong></td>
						<td align='center' colspan='5'>
							<TEXTAREA NAME="reclamado" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
						</td>
					</tr>
				</table>
			</div>
		</div> <?PHP
		} ?>

		<div id="duvida_produto" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			<!-- Incluído solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 -->
			<? if($login_fabrica==2){ ?>
				Dúvida dos produtos
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="especificacao_manuseio"
								<?PHP if ($natureza_chamado2 == 'especificacao_manuseio') {echo "CHECKED";}?>> ESPECIFICAÇÕES DE MANUSEIO
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="informacao_manuseio"
								<?PHP if ($natureza_chamado2 == 'informacao_manuseio') { echo "CHECKED";}?>>INFORMAÇÃO DE MANUSEIO
						</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="informacao_tecnica"
								<?PHP if ($natureza_chamado2 == 'informacao_tecnica') { echo "CHECKED";}?>>INFORMAÇÃO TÉCNICA
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="orientacao_instalacao"
								<?PHP if ($natureza_chamado2 == 'orientacao_instalacao') { echo "CHECKED";}?>>ORIENTAÇÃO PARA INSTALAÇÃO
						</td>
					</tr>
				</table>
				<!-- FIM solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 -->
			<?}else{?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a dúvida.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='7' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a sua dúvida SR.(a)?<BR>	ou<BR>A dúvida do Sr.(a) sobre como...., correto?";
						}?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			<?}?>
			<br>
			<br>
			</div>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td><strong>Dúvida :</strong></td>
				<td align='left' colspan='5'>
					<input name="faq_duvida_duvida"  id="faq_duvida_duvida" class="input" size="74" value="<? echo $faq_duvida ;?>">
					<input  class="input"  type="button" name="bt_localizar" value="Localizar" onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_duvida')">
				</td>
				<? if($login_fabrica==2) {
						$coluna ="7";
						echo "<td align='left' nowrap>";
						echo "<a href=\"javascript:listaFaq(document.frm_callcenter.produto_referencia.value)\">Listar todas dvidas cadastradas ou cadastrar a nova</a>";
						echo "</td>";
					}else{
						$coluna ="6";
					}
				?>
			</tr>
			<?php if ($login_fabrica == 24) { ?>
			<tr>
				<td>Assunto:</td>

				<td>
					<select name="callcenter_assunto[duvida_produto]" id="callcenter_assunto" class="input_req">
						<option value=''>>>> Escolha <<<</option><?php

						foreach ($assuntos["REVENDA"] AS $label => $valor) {

							if ($valor == $callcenter_assunto) {
								$selected = "selected";
							} else {
								$selected = "";
							}

							echo " <option value='$valor' $selected>REVENDA >> $label</option>";
						} ?>
					</select>
				</td>
			</tr>
			<?php
			} ?>
			<tr>
				<td colspan='<? echo $coluna; ?>' id="div_faq_duvida_duvida" class="div_faq_duvida_duvida"> &nbsp; </td>
			</tr>
			</table>
		</div>

		<div id="sugestao" class='tab_content'>
			<input type='hidden' name='callcenter_assunto[sugestao]' value='sugestao'>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top' width='80'><strong>Sugestão:</strong></td>
				<td align='left' colspan='5'>
					<?php
					if ($login_fabrica == 74 && !empty($callcenter) && $admin != 6437) {
						$bloqueia_reclamado_produto = "readonly";

						echo $reclamado;
						echo "<input type='hidden' name='reclamado_sugestao' value='$reclamado' />";
					}else{
						if ($login_fabrica == 74 && !empty($callcenter) && isset($_POST["reclamado_sugestao"])) {
							$reclamado = $_POST["reclamado_sugestao"];
						}

						?>
						<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read;?>><?echo $reclamado ;?></TEXTAREA>
					<? } ?>

				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
		</div>

		<?if($login_fabrica != 59 ){ # HD 37805 ?>
		<div id="procon" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='10' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a reclamação feita no Procon pelo SR.(a)?<BR>	ou<BR> O Sr.(a) diz que...., correto?";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>

			<? if($login_fabrica ==11) { // HD 55995?>
			Informações da Assistência
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left'><strong>Código:</strong></td>
					<td align='left'>
						<input name="procon_codigo_posto"  class="input"  value='<?echo $procon_codigo_posto ;?>'
						onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'codigo');" type="text" size="15" maxlength="15">
						<img src='imagens/lupa.png' border='0' align='absmiddle'
						style='cursor: pointer'
						onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'codigo');">
					</td>
					<td align='left'><strong>Nome:</strong></td>
					<td align='left'>
						<input name="procon_posto_nome"  class="input" value='<?echo $procon_posto_nome ;?>'
						onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'nome');" type="text" size="35" maxlength="500">
						<img src='imagens/lupa.png' border='0' align='absmiddle'
						style='cursor: pointer'
						onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'nome');">
					</td>
				</tr>
			</table>
				Tipo da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_reclamacao_at" <?PHP if ($natureza_chamado2 == 'pr_reclamacao_at' OR $natureza_chamado2 =='') { echo "CHECKED";}?>> RECLAMAÇÃO DA ASSIST. TÉCN.</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_info_at" <?PHP if ($natureza_chamado2 == 'pr_info_at') { echo "CHECKED";}?>>INFORMAÇÕES DE A.T</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_mau_atend" <?PHP if ($natureza_chamado2 == 'pr_mau_atend') { echo "CHECKED";}?>>MAU ATENDIMENTO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_posto_n_contrib" <?PHP if ($natureza_chamado2 == 'pr_posto_n_contrib') { echo "CHECKED";}?>>POSTO NÃO CONTRIBUI COM INFORMAÇÕES</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_demonstra_desorg" <?PHP if ($natureza_chamado2 == 'pr_demonstra_desorg') { echo "CHECKED";}?>>DEMONSTRA DESORGANIZAÇÃO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_bom_atend" <?PHP if ($natureza_chamado2 == 'pr_bom_atend') { echo "CHECKED";}?>>POSSUI BOM ATENDIMENTO</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_demonstra_org" <?PHP if ($natureza_chamado2 == 'pr_demonstra_org') { echo "CHECKED";}?>>DEMONSTRA ORGANIZAÇÃO</td>
				</tr>
				</table>
			<? } ?>
			Informações da Reclamação
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top' width='80'><strong>Reclamação:</strong></td>
				<td align='left' colspan='5'>
					<?php
					if ($login_fabrica == 74 && !empty($callcenter) && $admin != 6437) {
						$bloqueia_reclamado_produto = "readonly";

						echo $reclamado;
						echo "<input type='hidden' name='reclamado_procon' value='$reclamado' />";
					}else{
						if ($login_fabrica == 74 && !empty($callcenter) && isset($_POST["reclamado_procon"])) {
							$reclamado = $_POST["reclamado_procon"];
						}

						?>
						<TEXTAREA NAME="reclamado_procon" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read; ?>><?echo $reclamado ;?></TEXTAREA>
					<? } ?>

				</td>
			</tr>
			<?php if ($login_fabrica == 24) { ?>
			<tr>
				<td>Assunto:</td>

				<td>
					<select name="callcenter_assunto[procon]" id="callcenter_assunto" class="input_req">
						<option value=''>>>> Escolha <<<</option><?php

						foreach ($assuntos["PROCON"] AS $label => $valor) {

							if ($valor == $callcenter_assunto) {
								$selected = "selected";
							} else {
								$selected = "";
							}

							echo " <option value='$valor' $selected>PROCON >> $label</option>";
						} ?>
					</select>
				</td>
			</tr>
			<?php
			} ?>
			</table>
			<BR>
		</div>
		<?}?>
		<div id="onde_comprar" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados da Revenda.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>

			<?
			# HD 31204 - Francisco Ambrozio
			#   Alterado campo onde comprar para a Dynacom
			if ($login_fabrica == 2 || $login_fabrica == 24){
				if (strlen($revenda) > 0){
				$sql = "SELECT tbl_revenda.nome,
							tbl_revenda.endereco,
							tbl_revenda.numero,
							tbl_revenda.complemento,
							tbl_revenda.bairro,
							tbl_revenda.fone,
							tbl_cidade.nome AS revenda_city,
							tbl_cidade.estado AS revenda_uf
							FROM tbl_revenda
							JOIN tbl_cidade USING (cidade)
							WHERE revenda = $revenda";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$revenda_nome             = pg_fetch_result($res,0,nome);
					$revenda_endereco         = pg_fetch_result($res,0,endereco);
					$revenda_nro              = pg_fetch_result($res,0,numero);
					$revenda_cmpto            = pg_fetch_result($res,0,complemento);
					$revenda_bairro           = pg_fetch_result($res,0,bairro);
					$revenda_city             = pg_fetch_result($res,0,revenda_city);
					$revenda_uf               = pg_fetch_result($res,0,revenda_uf);
					$revenda_fone             = pg_fetch_result($res,0,fone);
				}
			}
			?>
				Informações da Revenda

			<?
			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {?>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td>
							Assunto: <select name="callcenter_assunto[onde_comprar]" id="callcenter_assunto" class="input_req">
							<option value=''>>>> Escolha <<<</option><?php

							foreach ($assuntos["REVENDA"] AS $label => $valor) {

								if ($valor == $callcenter_assunto) {
									$selected = "selected";
								} else {
									$selected = "";
								}

								echo " <option value='$valor' $selected>REVENDA >> $label</option>";
							} ?>
							</select>
						</td>
					</tr>
				</table><?php

			} ?>


			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' width='68'><strong>Localizar:</strong></td>
					<td align='left' nowrap colspan=5>
						<input name="localizarrevenda" id='localizarrevenda' value='<?echo $localizarrevenda ;?>' class="input" type="text" size="40" maxlength="500"> <a href='#onde_comprar' onclick='javascript: fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "nome","")'>Por Nome</a> | <a href='#onde_comprar' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "cidade","")'>Por Cidade</a> | <a href='#onde_comprar' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "cnpj","")'>Por CNPJ</a> | <a href='#onde_comprar' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "familia",document.frm_callcenter.consumidor_cidade)'>Por Família do Produto</a>
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Nome:</strong></td>
					<td align='left'><input type='hidden' name='revenda' id='revenda' value='<?=$revenda?>'><input type='text' name='revenda_nome' id='revenda_nome' value='<?=$revenda_nome?>'  size="40" maxlength="50">
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Endereço:</strong></td>
					<td align='left'><input type='text' name='revenda_endereco' id='revenda_endereco' value='<?=$revenda_endereco?>'  size="40" maxlength="500">
					</td>
					<td align='left'><strong>Nro.:</strong></td>
					<td align='left'><input type='text' name='revenda_nro' id='revenda_nro' value='<?=$revenda_nro?>'>
					</td>
					<td align='left'><strong>Complemento:</strong></td>
					<td align='left'><input type='text' name='revenda_cmpto' id='revenda_cmpto' value='<?=$revenda_cmpto?>'>
					</td>
				</tr>
					<tr>
					<td align='left'><strong>Bairro:</strong></td>
					<td align='left'><input type='text' name='revenda_bairro' id='revenda_bairro' value='<?=$revenda_bairro?>'>
					</td>
					<td align='left' valign='top'><strong>Cidade:</strong></td>
					<td align='left'><input type='text' name='revenda_city' id='revenda_city' value='<?=$revenda_city?>'>
					</td>
					<td align='left'><strong>UF:</strong></td>
					<td align='left'><input type='text' name='revenda_uf' id='revenda_uf' value='<?=$revenda_uf?>'>
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Telefone:</strong></td>
					<td align='left'><input type='text' name='revenda_fone' id='revenda_fone' value='<?=$revenda_fone?>'>
					</td>
				</tr><tr><td colspan='4'>Para cadastrar <a href='revenda_cadastro.php' target='_blank'>clique aqui</a></td><?php
				//HD 235203: Alterar assuntos Fale Conosco e CallCenter

				if ($login_fabrica == 24) {?>
					<tr><td colspan='7' height='10'></td></tr>
					<tr>
						<td align='left' valign='top'><strong>Informações:</strong></td>
						<td align='left' colspan='6'>
							<TEXTAREA NAME="reclamado_onde_comprar" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?echo $read;?>><?echo $reclamado ;?></TEXTAREA>
						</td>
					</tr><?php
				}?>

				</table><?php

			} else {?>

			Informações da Reclamação
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' valign='top'><strong>CNPJ:</strong></td>
					<td align='left' colspan='5'><input type='hidden' name='revenda' id='revenda' value='<?=$revenda?>'><input type='text' name='revenda_cnpj' id='revenda_cnpj' value='<?=$revenda_cnpj?>'>
					</td>
					<td align='left' valign='top'><strong>Nome:</strong></td>
					<td align='left' colspan='5'><input type='text' name='revenda_nome' id='revenda_nome' value='<?=$revenda_nome?>' maxlength='50'>
					</td>
				</tr>
				<tr><td colspan='4'>Para cadastrar <a href='revenda_cadastro.php' target='_blank'>clique aqui</a></td>
			</table>

			<? } ?>

			<BR>
		</div><?php

		if ($login_fabrica == 1 and strlen($callcenter) > 0) {

			$aDados = hdBuscarChamado($callcenter);

			switch ($aDados['categoria']) {

				case ('digitacao_fechamento_de_os') :   $categoria = "Digitação e/ou fechamento de OS\'s"; break;
				case ('utilizacao_do_site') :           $categoria = "Utilização do site"; break;
				case ('falha_no_site') :                $categoria = "Falha no site"; break;
				case ('pendencias_de_pecas') :          $categoria = "Pendências de peças"; break;
				case ('pedido_de_pecas') :              $categoria = "Pedido de peças"; break;
				case ('duvida_tecnica_sobre_produto') : $categoria = "Dúvida técnica sobre o produto"; break;
				case ('outros') :                       $categoria = "Outros"; break;

			}?>

			<div id="hd_posto" class='tab_content'>
				<div rel='div_ajuda' style='display:inline; Position:relative;'>
					<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
						<tr>
							<td align='right' width='150'></td>
							<td align='right' width='55'>
								<img src='imagens/ajuda_call.png' align=absmiddle>
							</td>
							<td align='center'>
								<STRONG>Informações cadastrados pelo posto.</STRONG><BR>
							</td>
							<td align='right' width='150'></td>
						</tr>
					</table>

					<br />
					<center>
					<table width='90%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px'>
						<tbody>
								<tr>
									<td class=" border "> Tipo de Solicitação: </td>
									<td class="dados"> <?php echo $categoria; ?> </td>

									<td class=" border "> Produto em Garantia: </td>
									<td class="dados"> <?php echo ($aDados['garantia'] =='t') ? "Sim" : "Não" ; ?> </td>
								</tr>
								<tr>
									<td class=" border "> Produto: </td>
									<td class="dados"> <?php echo $aDados['referencia']; ?> </td>

									<td class=" border "> OS: </td>
									<td class="dados"> <?php echo (!empty($aDados['sua_os'])) ? $aDados['codigo_posto']."".$aDados['sua_os'] : ""; ?> </td>
								</tr>
								<tr>
									<td class=" border "> Pedido: </td>
									<td class="dados"> <?php echo $aDados['pedido']; ?> </td>

									<td class=" border "> Posto recebe peça em garantia: </td>

									<td class="dados"> <?php echo ($aDados['pedido_em_garantia'] =='t') ? "Sim" : "Não" ; ?> </td>
								</tr>
							</tbody>
						</table>
					</center>
				</div>
				<br />
			</div><?php

		}

		if ($login_fabrica == 45) {?>
		<div id="garantia" class='tab_content'>
			<p style='font-size: 14px'><b>Garantia</b></p>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="produto_referencia_garantia"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'referencia');mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_garantia.value)" type="text" size="15" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto_garantia' value="<? echo $produto; ?>">
					<input name="produto_nome_garantia"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao');mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_garantia.value)" type="text" size="35" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'>
					<input name="serie_garantia" maxlength="20" class="input"  value='<?echo $serie ;?>'>
				</td>
			</tr>

			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_produto_garantia" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		</div>
		<? } ?>

		<?if(in_array($login_fabrica, array(59,81,114))) { /* HD 37805 */?>
		<div id="ressarcimento" class='tab_content'>

		<!-- SEDEX REVERSO -->
		<!--
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados Bancários.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
-->
			<?
			if (strlen($callcenter) > 0){
				$sql = "SELECT 	banco            ,
								agencia          ,
								contay           ,
								nomebanco        ,
								favorecido_conta ,
								cpf_conta        ,
								tipo_conta
						FROM tbl_hd_chamado_extra_banco
						WHERE hd_chamado = $callcenter";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$banco            = pg_fetch_result($res,0,banco);
					$agencia          = pg_fetch_result($res,0,agencia);
					$contay           = pg_fetch_result($res,0,contay);
					$nomebanco        = pg_fetch_result($res,0,nomebanco);
					$favorecido_conta = pg_fetch_result($res,0,favorecido_conta);
					$cpf_conta        = pg_fetch_result($res,0,cpf_conta);
					$tipo_conta       = pg_fetch_result($res,0,tipo_conta);
				}
			}
			?>
			Dados Bancários
			<table width='100%' border='0' align='center' cellpadding="0" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<? if (strlen($xos)==0) { $xos = $os ;}?>
				<td align='left'><strong>OS:</strong></td>
				<td align='left'><input type='text' name='os_ressarcimento' id='os_ressarcimento' class="input" value='<?=$xos?>'  size="13" maxlength="13">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Banco:</strong></td>
				<?

				$sql = "SELECT banco,codigo,nome from tbl_banco order by nome";
				$res = pg_exec($con,$sql);
				?>
				<td align='left'>
					<select name='banco' id='banco' class='input'>
						<option>- Escolha</option>
							<?
								for ($i=0;$i<pg_num_rows($res);$i++) {
									$xbanco = pg_result($res,$i,banco);
									$codigo = pg_result($res,$i,codigo);
									$nome = pg_result($res,$i,nome);

									if ($banco == $xbanco) {
										$selected = "SELECTED";
									}
									echo "<option value='$xbanco' $selected>$codigo-$nome</option>";
									$selected = '';
								}
							?>
						</select>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Agência:</strong></td>
				<td align='left'><input type='text' name='agencia' id='agencia' class="input" value='<?=$agencia?>' size="15" maxlength="10">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Conta:</strong></td>
				<td align='left'><input type='text' name='contay' id='contay' class="input" value='<?=$contay?>' size="15" maxlength="10">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Tipo de Conta:</strong></td>
				<td align='left'>
					<select name='tipo_conta' id='tipo_conta' class="input" style='width:150px; font-size:10px' >
						<option value='' <? if (strlen($tipo_conta)==0)echo "SELECTED";?> ></option>
						<option value='Conta conjunta' <? if ($tipo_conta == 'Conta conjunta')echo "SELECTED";?> >Conta conjunta</option>
						<option value='Conta corrente' <? if ($tipo_conta == 'Conta corrente')echo "SELECTED";?>>Conta corrente</option>
						<option value='Conta individual' <? if ($tipo_conta == 'Conta individual')echo "SELECTED";?>>Conta individual</option>
						<option value='Conta jurdica' <? if ($tipo_conta == 'Conta jurdica')echo "SELECTED";?>>Conta jurídica</option>
						<option value='Conta poupana' <? if ($tipo_conta == 'Conta poupana')echo "SELECTED";?>>Conta poupança</option>
					</select>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Nome do Favorecido:</strong></td>
				<td align='left' colspan='2'><input type='text' name='favorecido_conta' id='favorecido_conta' class="input" value='<?=$favorecido_conta?>'  size="40" maxlength="50" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
				<td align='left'><strong>CPF:</strong></td>
				<td align='left'><input type='text' name='cpf_conta' id='cpf_conta' class="input" value='<?=$cpf_conta?>'  size="15" maxlength="14" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Observações:</strong></td>
				<td align='left' colspan='5'><TEXTAREA NAME="obs_ressarcimento" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?if(strlen($callcenter)>0) echo " READONLY "?>><?echo $defeito;?></TEXTAREA></td>
			</tr>
			<tr>
				<td align='left'><strong>Procon? <input type="checkbox" name="procon" value='t' <?if (strlen($numero_processo) > 0) echo "CHECKED ";?> onClick='if (this.checked) {this.form.numero_processo.disabled = false;} else {this.form.numero_processo.disabled = true;}'></strong></td>
				<td align='left'><strong>Número do Processo:</strong></td>
				<td align='left'><input type='text' name='numero_processo' id='numero_processo' class="input" value='<?=$numero_processo?>' <?if(strlen($callcenter)>0) echo " READONLY "?> size="40" maxlength="30">
				</td>
			</tr>
			</table>

			<br>
			Valores do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Valor do Produto:</strong></td>
				<td align='left'><input type='text' name='valor_produto' id='valor_produto' class="input" value='<?=$valor_produto?>'  size="20" maxlength="10" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
				<td align='left'><!--<strong>Valor INPC.:</strong>--></td>
				<td align='left'><input type='hidden' name='valor_inpc' id='valor_inpc' class="input" value='<?=$valor_inpc?>' size="15" maxlength="10">
				</td>
				<td align='left'><strong>Valor Corrigido:</strong></td>
				<td align='left'><input type='text' name='valor_corrigido' id='valor_corrigido' readonly class="input" value='<?=$valor_corrigido?>' size="15" maxlength="10">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Data do Pagamento:</strong></td>
				<td align='left'><input type='text' name='data_pagamento' rel='data' id='data_pagamento' class="input" value='<?=$data_pagamento?>'  size="20" maxlength="10">
				</td>
			</tr>
			</table>
			<BR>

		</div>

		<!-- SEDEX REVERSO -->
		<div id="sedex_reverso" class='tab_content'>
		<!--
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados Bancários.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
-->
			<?
			if (strlen($callcenter) > 0){
				$sql = "SELECT 	banco            ,
								agencia          ,
								contay           ,
								nomebanco        ,
								favorecido_conta ,
								cpf_conta        ,
								tipo_conta
						FROM tbl_hd_chamado_extra_banco
						WHERE hd_chamado = $callcenter";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$banco            = pg_fetch_result($res,0,banco);
					$agencia          = pg_fetch_result($res,0,agencia);
					$contay           = pg_fetch_result($res,0,contay);
					$nomebanco        = pg_fetch_result($res,0,nomebanco);
					$favorecido_conta = pg_fetch_result($res,0,favorecido_conta);
					$cpf_conta        = pg_fetch_result($res,0,cpf_conta);
					$tipo_conta       = pg_fetch_result($res,0,tipo_conta);
				}
			}
			?>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="troca_produto_referencia"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'referencia');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="15" maxlength="15" <?if(strlen($callcenter)>0) echo " READONLY "?>>
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="troca_produto_nome"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="35" maxlength="500" <?if(strlen($callcenter)>0) echo " READONLY "?>>
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
			</tr>
			<tr>
				<td align='left' valign='top'><strong>Observações:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="troca_observacao" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?if(strlen($callcenter)>0) echo " READONLY "?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
			Informações de Envio
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Data do Retorno do Produto (Cliente):</strong></td>
				<td align='left'><input type='text' name='data_retorno_produto' id='data_retorno_produto' class="input" value='<?=$data_retorno_produto?>' size="12" maxlength="12" rel='data' <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
				<td align='left'><strong>Código de Postagem:</strong></td>
				<td align='left'><input type='text' name='numero_objeto' id='numero_objeto' class="input" value='<?=$numero_objeto?>'  size="25" maxlength="20" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
			</tr>
			<tr>
				<td align='left' colspan='4'><strong>Procon? <input type="checkbox" name="procon2" value='t' <?if (strlen($numero_processo)>0) echo "CHECKED ";?> <?if(strlen($callcenter)>0) echo " READONLY "?> onClick='if (this.checked) {this.form.numero_processo2.disabled = false;} else {this.form.numero_processo2.disabled = true;}'></strong>
				&nbsp;&nbsp;&nbsp;
				<strong>Número do Processo:</strong>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type='text' name='numero_processo2' id='numero_processo2' class="input" value='<?=$numero_processo?>'  size="25" maxlength="30" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
			</tr>
			</table>
			<BR>
		</div>
		<? } ?>
		<?if($login_fabrica==46 OR $login_fabrica==11){?>
		<div id="troca_produto" class='tab_content'>
			<p style='font-size: 14px'><b>Troca de Produto</b></p>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="troca_produto_referencia"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'referencia');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="15" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="troca_produto_nome"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="35" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'>
					<input name="troca_serie" maxlength="20" class="input"  value='<?echo $serie ;?>'>
				</td>
			</tr>

<?/*		<tr>
			<td>
				<a href="javascript:mostraDefeitos('Reclamao',document.frm_callcenter.produto_referencia.value)">Defeitos</a>
				</td>
				<td align='left' colspan='5' width='630' valign='top'>
					<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
					<?   if(strlen($defeito_reclamado)>0){
							$sql = "SELECT defeito_reclamado,
											descricao
									FROM tbl_defeito_reclamado
									WHERE defeito_reclamado = $defeito_reclamado";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res)>0){
								$defeito_reclamado_descricao = pg_fetch_result($res,0,descricao);
								echo "<input type='radio' checked value='$defeito_reclamado'><font size='1'>$defeito_reclamado_descricao</font>";
							}
						}
*/					?>
<?/*					</div>
				</td>
			</tr>
*/?>
			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="troca_produto_descricao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		</div>
		<? } ?>

	<?if($login_fabrica==24){?>
		<div id="outros_assuntos" class='tab_content'>
			<p style='font-size: 14px'><b>Outros Assuntos</b></p>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="outros_assuntos_descricao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			<?php if ($login_fabrica == 24) { ?>
			<tr>
				<td>Assunto:</td>

				<td>
					<select name="callcenter_assunto[outros_assuntos]" id="callcenter_assunto" class="input_req">
						<option value=''>>>> Escolha <<<</option><?php

						foreach ($assuntos["OUTROS ASSUNTOS"] AS $label => $valor) {

							if ($valor == $callcenter_assunto) {
								$selected = "selected";
							} else {
								$selected = "";
							}

							echo " <option value='$valor' $selected>Outros Assuntos >> $label</option>";
						} ?>
					</select>
				</td>
			</tr>
			<?php
			} ?>
			</table>
		</div>
		<? } ?>

			<?php if($login_fabrica == 94){ ?>
			<div id="indicacao_rev" class='tab_content'>
				<div rel='div_ajuda' style='display:inline; Position:relative;'>
					<table  width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px;'>
						<tr>
							<td align='right' valign='top' width="100"><strong>CNPJ:</strong></td>
							<td align='left'width="200">
								<input type='hidden' name='revenda_ind' id='revenda_ind' value='<?=$revenda?>'>
								<input type='text' name='revenda_ind_cnpj' id='revenda_ind_cnpj' value='<?=$revenda_cnpj?>' class="input">
								<img src="imagens/lupa.png" border="0" align="absmiddle" style="cursor: pointer" onclick="javascript: pesquisaRevenda (document.frm_callcenter.revenda_ind_cnpj,'cnpj');">
							</td>
							<td align='right' valign='top' width="100"><strong>Nome:</strong></td>
							<td align='left'><input type='text' name='revenda_ind_nome' id='revenda_ind_nome' value='<?=$revenda_nome?>' maxlength='50' size="50" class="input">
							<img src="imagens/lupa.png" border="0" align="absmiddle" style="cursor: pointer" onclick="javascript: pesquisaRevenda (document.frm_callcenter.revenda_ind_nome,'nome');">
							</td>
						</tr>

						<tr>
							<td colspan="4">
								<strong>Descrição:</strong>
								<TEXTAREA NAME="ind_revenda_desc" ROWS="6" COLS="110"  class="input" style='font-size:10px'> <? echo $reclamado; ?></textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div id="indicacao_at" class='tab_content'>
				<div rel='div_ajuda' style='display:inline; Position:relative;'>
					<table  width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px;'>
						<tr>
							<td align='right' width="100"><strong>Código:</strong></td>
							<td align='left' width="150">
								<input name="codigo_posto_ind"  class="input"  value='<?echo $codigo_posto ;?>'
								 type="text" size="15" maxlength="15">
								<img src='imagens/lupa.png' border='0' align='absmiddle'
								style='cursor: pointer'
								onclick="javascript: pesquisaPosto (document.frm_callcenter.codigo_posto_ind,'codigo');">
							</td>
							<td align='right' width="100"><strong>Nome:</strong></td>
							<td align='left'>
								<input name="posto_nome_ind"  class="input" value='<?echo $posto_nome ;?>'
								 type="text" size="35" maxlength="500">
								<img src='imagens/lupa.png' border='0' align='absmiddle'
								style='cursor: pointer'
								onclick="javascript: pesquisaPosto (document.frm_callcenter.posto_nome_ind,'nome');">
							</td>
						</tr>
						<tr>
							<td colspan="4">
								<strong>Descrição:</strong>
								<TEXTAREA NAME="ind_posto_desc" ROWS="6" COLS="110"  class="input" style='font-size:10px' ><? echo $reclamado; ?></textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>
		<?php } ?>

		<?php if($login_fabrica == 74) { ?>
			<div id="informacoes" class='tab_content'>
					<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' valign='top' width='80'><strong>Informações:</strong></td>
						<td align='left' colspan='5'>
							<?php
							if ($login_fabrica == 74 && !empty($callcenter) && $admin != 6437) {
								$bloqueia_reclamado_produto = "readonly";

								echo $reclamado;
								echo "<input type='hidden' name='informacoes_cliente' value='$reclamado' />";
							}else{
								if ($login_fabrica == 74 && !empty($callcenter) && isset($_POST["informacoes_cliente"])) {
									$reclamado = $_POST["informacoes_cliente"];
								}

								?>
								<TEXTAREA NAME="informacoes_cliente" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
							<? } ?>

						</td>
					</tr>
					</table>
			</div>
		<?php } ?>
	</div>
	</td>
</tr>

<? if(strlen($callcenter)>0 && ($login_fabrica == 24 OR $login_fabrica == 52)) {
		// HD 277105
		$sql = 'SELECT hd_chamado_anterior
				FROM tbl_hd_chamado
				WHERE hd_chamado = ' . $callcenter . '
				AND fabrica = '.$login_fabrica . '
				LIMIT 1';
		$res = pg_query($sql);

		if(pg_numrows($res) > 0)
			$hd_pesquisa_ini = pg_result ($res,0,hd_chamado_anterior);

		if(strlen($hd_pesquisa_ini) == 0) { // verifica se ele pode ser o chamado raiz

			$sql = 'SELECT hd_chamado
				FROM tbl_hd_chamado
				WHERE hd_chamado_anterior = ' . $callcenter . '
				AND fabrica = '.$login_fabrica . '
				LIMIT 1';
			$res = pg_query($sql);

			if(pg_numrows($res) > 0)
				$hd_pesquisa_ini = pg_result ($res,0,hd_chamado);

		}

		if(strlen($hd_pesquisa_ini) > 0) { //se tiver chamados anteriores

			function busca_chamados_filhos($hd_pai) { //busca os chamados vindo do passado

				global $con;
				$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado_anterior=$hd_pai";
				//echo $sql;
				$res = pg_query($con, $sql);

				$hd_filhos	= array();

				for($i=0; $i<pg_num_rows($res); $i++) {
					$hd_atual = pg_result($res,$i,hd_chamado);
					$hd_filhos[$hd_atual] = busca_chamados_filhos($hd_atual);
				}

				return($hd_filhos);

			}

			function onlyOne($arr) { //deixa o array unidimensional
				$rpltext = print_r($arr,true);
				$chars = array("Array","(",")","[","]"," ","\n");
				$repls = array("","","","=>","","","");
				$text = str_replace($chars,$repls,$rpltext);
				$expl = explode('=>',$text);

				foreach ($expl AS $result) {
					if (!empty($result)) $arrOrg[] = $result;
				}

				$count = count($arrOrg);
				$retorno = array();

				for ($i=0; $i<$count; $i++) {
					if ($i%2==0) $retorno[$arrOrg[$i]] = $arrOrg[($i+1)];
				}

				return $retorno;

			}
/**********     busca o primeiro chamado a partir do chamado aberto      **********/
			$controle = false;	//variavel que finaliza o loop
			$ultimo_reg = 0;	//pega o ultimo registro, sem chamado anterior
			$i = 0;				//contadora p/ zebrado e ajax/jQuery

			while($controle == false) {

				if(isset($hd_pesquisa_ini)) //inicia pesquisando o hd anterior do visualizado na tela
					$hd_pesquisa = $hd_pesquisa_ini;

				$i++;
				$sql = 'SELECT
						hd_chamado_anterior, hd_chamado
						FROM tbl_hd_chamado
						WHERE hd_chamado = ' . $hd_pesquisa . '
						AND fabrica = '.$login_fabrica . '
						LIMIT 1';
				$res = pg_query($sql);

				if(pg_numrows($res) > 0) {
					$hd_pesquisa	= pg_result ($res,0,hd_chamado_anterior);
					$hd_chamado_cp	= pg_result ($res,0,hd_chamado);

					if(strlen($hd_pesquisa) == 0 && $ultimo_reg == 0 ) {
						$hd_pesquisa = $hd_chamado_cp; // $hd_pesquisa = chamado raiz
						$ultimo_reg++;
					}
					if ($ultimo_reg == 1)
						$controle = true;
				}

				if($i == 1)
					unset($hd_pesquisa_ini); //usa essa variavel só na primeira iteração do loop
			}
/*******      fim da busca      @return $hd_pesquisa       *****/
			$vet = array();

			$vet = busca_chamados_filhos($hd_pesquisa); // pega todos os chamados vindo desse

			$res_chamados = onlyOne($vet); //tira o vetor multidimensional em um só

			$new_res_chamados = array();

			foreach ($res_chamados as $key => $value){ //tira as chaves pra ficar total unidimensional
				if(!in_array($key, $new_res_chamados))
					$new_res_chamados[] = $key;
				if(!in_array($value, $new_res_chamados))
					$new_res_chamados[] = $value;
			}

			$new_res_chamados[] = $hd_pesquisa; //adiciona no vetor chamado raiz

			asort($new_res_chamados);
			/*
			echo '<pre>';
			print_r($new_res_chamados);
			echo '</pre>';
			*/
			echo "
					<table width='100%' border='0' align='center' cellpadding=\"1\" cellspacing=\"1\" style=' border:#5AA962 1px solid; background-color:#E6E6FA;font-size:10px'>
							<tr><th colspan='4' style='border-bottom:1px solid #ccc'>Chamados Anteriores</th></tr>
							<tr align='left'>
								<th>Nº do Chamado/Protocolo</th>
								<th>Data</th>
								<th>Status</th>
								<th>Ações</th>
							</tr>
				";
			// percorre o vetor e exibe os protocolos anteriores (finalmente :) )
			if(!empty($new_res_chamados))
			foreach ($new_res_chamados as $hd_pesquisa){
				if($hd_pesquisa == $callcenter || is_null($hd_pesquisa))
					continue;
				$i++;
				$sql = 'SELECT
						TO_CHAR(data,\'DD/MM/YYY\') as data, status, hd_chamado
						FROM tbl_hd_chamado
						WHERE hd_chamado = ' . $hd_pesquisa . '
						AND fabrica = '.$login_fabrica . '
						LIMIT 1';

				$res = pg_query($sql);

				if(pg_numrows($res) > 0) {

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					$hd_chamado_cp	= pg_result ($res,0,hd_chamado);
					$data			= pg_result ($res,0,data);
					$status			= pg_result ($res,0,status);

					// entra no if se tiver hd e for até o ultimo registro
					if (strlen($hd_pesquisa)) {
						echo '<tr id="dados'.$i.'" style="cursor:pointer;" bgcolor="'.$cor.'" onclick="exibe_interacao_anterior('.$hd_chamado_cp.','.$i.')">
							<td>'.$hd_chamado_cp.'</td>
							<td>'.$data.'</td>
							<td>'.$status.'</td>
							<td><a href="#dados'.$hd_chamado_cp.'">Exibir <img src="imagens/mais.bmp" style="cursor:pointer" id="seta'.$i.'"></a></td>
						</tr>';
					}
				}
			}
			echo '</table><br />
				';

		} ?>

		<script type="text/javascript">

			function exibe_interacao_anterior(id, linha) {

				if ($("#seta"+linha).attr( "src" )=="imagens/menos.bmp"){
					$(".resp"+linha).fadeOut("slow");
					$("#seta"+linha).attr("src", "imagens/mais.bmp");
				} else {

					$("#seta"+linha).attr("src", "imagens/menos.bmp");

					url = '<?php echo $_SERVER[PHP_SELF]; ?>?ajax=sim&hdanterior='+id+'&resp='+linha;

					$.get(url, function(dados) {
						$("#dados" + linha).after(dados);
					});

				}

			}

		</script><?php

}
?>



	<?php
	if (strlen($callcenter) > 0) {
	?>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px;margin-top: 10px;'>
			<tr>
				<td align='right' width='150'></td>
				<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
				<td align='center'>
					<STRONG>Por favor, queira anotar o nº do protocolo de atendimento</STRONG><BR>
					Número <font color='#D1130E'><?echo $callcenter;?></font>
				</td>
				<td align='right' width='150'></td>
			</tr>
		</table>
		<br />
	<?php
	}
	?>

	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#5AA962 1px solid; background-color:#D1E7D3;font-size:10px'>

	<!-- HD-1667397 MONTEIRO -->

		<? if(strlen($callcenter)>0){ ?>
			 <tr>
				<td></td>
			 </tr>
			 <tr>
			 <td align='left' valign='top'> <strong>Resposta:</strong></td>
			 <td colspan='6' align='left'><TEXTAREA NAME="resposta" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $resposta ;?></TEXTAREA></td>
			 </tr>
		<?}?>

		<tr>
		<td align='left' width='80'><strong>Transferir p/:</strong></td>
		<td align='left' width='90'>
			<select name="transferir" style='width:80px; font-size:9px' class="input" >
			 <option value=''></option>
			<?
				if ($login_fabrica == 24) {
					$sql_fale = " AND atendente_callcenter = true ";
				}
				if($login_fabrica==30 and strlen($login_cliente_admin)>0) {
					$sql_marca = "SELECT marca FROM tbl_cliente_admin WHERE cliente_admin = $login_cliente_admin";
					$res_marca           = pg_exec($con,$sql_marca);
					$marca_cliente_admin = pg_result($res_img,0,marca);
					$sql_marca = " JOIN tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_admin.cliente_admin AND tbl_cliente_admin.marca = $marca_cliente_admin ";
				}
				else $sql_marca = '';
				$sql = "SELECT admin, login
						from tbl_admin
						$sql_marca
						where tbl_admin.fabrica = $login_fabrica
						and tbl_admin.ativo is true
						and (privilegios like '%call_center%' or privilegios like '*')
						$sql_fale
						order by login
						";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($i=0;pg_num_rows($res)>$i;$i++){
						$tranferir = pg_fetch_result($res,$i,admin);
						$tranferir_nome = pg_fetch_result($res,$i,login);
						echo "<option value='$tranferir'>$tranferir_nome</option>";
					}
				}
			?>
			</select>
		</td>
		<? if ($login_fabrica == 24 || $login_fabrica == 11 || $login_fabrica == 85) { ?>
		<td align='left' width='80'><strong>Interventor p/:</strong></td>
		<td align='left' width='90'>
						<select name="intervensor" style='width:80px; font-size:9px' class="input" >
			 <option value=''></option>
			<?
				if ($login_fabrica == 24 || $login_fabrica == 11 || $login_fabrica == 85) {
					$sql_fale = " AND intervensor = true ";
				}
				$sql = "SELECT admin, login
						from tbl_admin
						where fabrica = $login_fabrica
						and ativo is true
						and (privilegios like '%call_center%' or privilegios like '*')
						$sql_fale
						order by login
						";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($i=0;pg_num_rows($res)>$i;$i++){
						$tranferir_intervensor = pg_fetch_result($res,$i,admin);
						$tranferir_nome_intervensor = pg_fetch_result($res,$i,login);
						echo "<option value='$tranferir_intervensor'>$tranferir_nome_intervensor</option>";
					}
				}
			?>
			</select>
		</td>
		<?}?>
		<? if($login_fabrica == 35 AND strlen($callcenter) > 0 ){ ?>

				<td align='left' width='120'><strong>Enviar para Consumidor:</strong></td>
				<td align="left" style=" width: 14px;">
					<input type="checkbox" name="transferir_consumidor" id="transferir_consumidor" class="input"/>
				</td>
			<?php
				}
			?>
		<td align='left' width='50'><strong>Situação:</strong></td>
		<td align='left' width='85'>

			<?php
			if($login_fabrica == 74){
				?>

				<script>

				function verificaStatus(n){

					if(n == "button"){
						n = $('#status_interacao').val();
					}

					if(n == "PROTOCOLO DE INFORMACAO"){

						var info_cliente = $('textarea[name=informacoes_cliente]').val();

						if(info_cliente == ""){

							alert("Por favor insira as informações do Cliente!");

						}

					}

				}
				</script>

				<?php
			}
			?>
			<?php
			if($login_fabrica == 15){
				?>

				<script>

				function verificaStatus(n){

					if(n == "button"){
						n = $('#status_interacao').val();
					}
					if(n == "Retorno"){
						$('#data_para_retorno').show();
					}
					else {
						$('#data_para_retorno').hide();
					}

				}
				</script>

				<?php
			}
			?>

			<select name="status_interacao" id="status_interacao" style="width:80px; font-size:9px" class="input" <?php if ($login_fabrica == 74 OR $login_fabrica == 15 ) { echo "onchange=\"verificaStatus(this.value)\""; } ?> >

			<?php
				$sql = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica order by status ";
				$res = pg_query($con,$sql);

				for ($i = 0; $i < pg_num_rows($res);$i++){

					$status_hd = pg_result($res,$i,0);

					$status_hd_desc = ($status_hd == 'Ag Consumidor') ? 'Aguardando Consumidor' : $status_hd;

					$selected_status = ($status_hd == $status_interacao) ? "SELECTED" : null;

			?>
					<option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd_desc; ?></option>
			<?
				}
			?>
			</select>
		</td>
		<?php if ($login_fabrica == 30 AND ($status_interacao == "Aberto" OR !empty($situacao_interacao))) {?>
		<td align='left' width='50'><strong>Motivo:</strong></td>
		<td align='left' width='85'>
			<select name="motivo_situacao" id="motivo_situacao" style="width:80px; font-size:9px" class="input" >
				<option value=""></option>
			<?php
				$sql = " SELECT hd_situacao,descricao FROM tbl_hd_situacao where fabrica=$login_fabrica order by descricao ";
				$res = pg_query($con,$sql);

				for ($i = 0; $i < pg_num_rows($res);$i++){

					$hd_situacao = pg_result($res,$i,'hd_situacao');

					$hd_situacao_desc = pg_result($res,$i,'descricao');

					$selected_situacao = ($hd_situacao == $situacao_interacao) ? "SELECTED" : null;
			?>
					<option value="<?=$hd_situacao?>" <?echo $selected_situacao?> ><?echo $hd_situacao_desc?></option>
			<?
				}
			?>
			</select>
		</td>
		<?php } ?>
		<?php
			if ($login_fabrica == 15) {
				$display1 = ($status_interacao == "Retorno") ? "display: block;" : "display: none;";
			?>
			<td align="left" id="data_para_retorno" nowrap style="width: 130px; <?=$display1?>" >
	            <label for="marcar_data_para_retorno"><strong>Retorno: </strong></label>
	            <input type="text" value="<?php echo $xdata_retorno; ?>" name="data_retorno" id="data_retorno"  rel="data" class="input" />
	            <input type="hidden" id="data_retorno_alterou" name="data_retorno_alterou" value="<?=$_POST['data_retorno_alterou']?>" />
	        </td>
<?php
            }
?>
<?php
            if (in_array($login_fabrica,array(85,94,145))) {
                 $display = $status_interacao == "Resolvido" ? "display:block" : "display:none";
?>
			<td align="left" id="satisfacao" nowrap style="width: 130px; <?=$display?>">
	            <input type="checkbox" value="t" name="disparar_pesquisa" id="disparar_pesquisa" class="input" <?php echo (isset($_POST['disparar_pesquisa'])) ? 'checked="checked"' : '' ; ?> />
	            <label for="disparar_pesquisa"><strong>Disparar Pesquisa ao Consumidor</strong></label>
	        </td>
<?php
            }
?>
			<? if($login_fabrica != 74){ ?>
				<td align="left" nowrap style="width: 130px;">
					<input type="checkbox" name="chamado_interno" id="chamado_interno" class="input" <?php echo (isset($_POST['chamado_interno'])) ? 'checked="checked"' : '' ; ?> />
					<label for="chamado_interno"><strong>Chamado Interno</strong></label>
					<?php
						// !110180 - Nome do atendente que abriu o chamado no rodapé
						$fabrica_exibir_nome_atentende = array(30);
						$fabrica_exibir_nome_atentende = array_flip($fabrica_exibir_nome_atentende);
					?>
					<?php if ( ! empty($callcenter) && isset($fabrica_exibir_nome_atentende[$login_fabrica])): ?>
						<?php
							/**
							 * Colocar nome de usuário que abriu o chamado no rodapé.
							 * HD 110180
							 *
							 * @author Augusto Pascutti <augusto.pascuti@telecontrol.com.br>
							 */
							$sql_abriu = "SELECT nome_completo
										  FROM tbl_admin
										  WHERE admin = %s";
							$sql_abriu = sprintf($sql_abriu,$usuario_abriu);
							$sql_abriu = pg_escape_string($sql_abriu);
							$res_abriu = @pg_query($con,$sql_abriu);
							if ( is_resource($res_abriu) ) {
								$row_abriu = pg_num_rows($res_abriu);
								if ( $row_abriu > 0 ) {
									$nome_abriu = pg_fetch_result($res_abriu,0,'nome_completo');
								}
							}

							if (empty($nome_abriu) ) {
								$nome_abriu = "Erro";
							}?>
						&nbsp; Chamado aberto por <?php echo $nome_abriu; ?>
					<?php endif; ?>
				</td>
			<?php
				}
			if ($login_fabrica == 25) {?>
				<td align='center' nowrap><a href='sedex_cadastro.php' target='blank'><strong>Abrir OS Sedex</strong></a></td><?php
			}

			//HD 234227: Enviar e-mail para o consumidor
			if (in_array($login_fabrica,array(24,86)) && strlen($callcenter) > 0) {?>
				<td align='center' nowrap><INPUT TYPE="checkbox" <?php if ($envia_email) { echo "checked"; } ?> NAME="envia_email" class="input" > <strong>Envia e-mail para consumidor</strong></td><?php
			}?>

            <?php if ($login_fabrica == '80' and !empty($callcenter)): ?>
            <td align='left' style="width: 220px;"><INPUT TYPE="checkbox"  NAME="enviar_por_email" class="input" > <strong>Enviar por e-mail ao consumidor</strong></td>
            <td align='left' style="width: 220px;"><INPUT TYPE="checkbox"  NAME="enviar_por_sms" class="input" > <strong>Enviar por SMS ao consumidor</strong></td>
            <?php endif ?>

			<td align='left'>
				<input class="botao" type="hidden" name="btn_acao"  value=''>
				<input  class="input verifica_servidor" rel="frm_callcenter" type="button" name="bt" value='Gravar Atendimento' style='width:120px' onclick="if (document.frm_callcenter.btn_acao.value!='') alert('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.'); else {
				<?if($login_fabrica == 3) { // HD 48680
				  echo "if(confirm('Deseja confirmar o atendimento?') == true){ document.frm_callcenter.btn_acao.value='final';}else{ return; }";
				} else {
					echo "document.frm_callcenter.btn_acao.value='final';liberar_campos();";
				 }?>
				}
				">
			</td>
			<?php
				if(strlen($callcenter) > 0 AND $login_fabrica == 45){
					if(file_exists("xls/$callcenter.zip")){
						echo "<td align='left' width='150'>
								<input type='button' value='Download XML' onclick=\"window.open('xls/$callcenter.zip');\">
							  </td>
							  <td>
							  	<iframe src='upload_xml_nks.php?hd_chamado=".$callcenter."' width='500' height='60' style='border:0;'></iframe>
							  </td>";
					}else{
			?>
						<td align='left' id='gerar_xml'>
							<input type='button' value='Gerar XML' onclick="javascript: geraXml(<?=$callcenter?>)">
							<input type='radio' name='coleta' value='CA' checked> Coleta Domiciliar
							<input type='radio' name='coleta' value='A'> Autorização de Coleta &nbsp;&nbsp;
							 <input type='text' name='valor_declarado' id='valor_declarado' size='8' class='frm'> Valor Declararo
						</td>
			<?php
					}
				}
			?>
		</tr>
	</table>
		<!-- FIM HD-1667397 MONTEIRO -->
<tr>
	<td colspan='5'>
		<?php
		### 94971
		if (strlen($_GET['Id']) > 0) {
			$id_x = $_GET['Id'];
		} else {
			$id_x = "";
		}

		if (strlen($callcenter) > 0 OR strlen($id_x) > 0) {
			### respostas do chamado
			$_hd_chamado = (strlen($id_x) > 0 AND strlen($_GET['herdar']) > 0 AND $login_fabrica == 59) ? $id_x : $callcenter;
			## funcao declarada em 'assist/www/helpdesk.inc.php'
			$aRespostas = hdBuscarRespostas($_hd_chamado, false, true);
		?>

			<br />
			<table cellpadding="2" cellspancing="1" border="0" class="tabela" style="width: 100%; margin: 0 auto; border: #485989 1px solid; font-size: 10px; margin-bottom: 10px;" >
				<tr>
					<th class="titulo_coluna">Nº</th>
					<th class="titulo_coluna">Data</th>
					<th class="titulo_coluna">Usuário</th>
					<th class="titulo_coluna">Situação</th>
					<th class="titulo_coluna">Descrição</th>
					<? if($login_fabrica == 1){ ?>
						<th class="titulo_coluna">Imagem</th>
					<? } ?>
				</tr>
		<?php
			$count_interacao = 1;
			foreach ($aRespostas as $iResposta=>$aResposta) {
		?>
					<tr>
						<td align="center"><strong><?=$iResposta + 1?></strong></td>
						<td align="center" nowrap="nowrap">
							<?=$aResposta['data']?> <?=($login_fabrica == 74) ? "(GMT -3)" : ""?>
						</td>
						<td align="center">
							<strong><?=((!empty($aResposta['atendente'])) ? $aResposta['atendente'] : $aResposta['posto_nome'])?></strong>
						</td>
						<td style="text-align: center; font-weight:bold; font-size: 10px;">
							<?php
								echo $aResposta['status_item'];
							?>
						</td>
						<td valign="top" >
							<?php
								if ($aResposta['interno'] == "t") {
									if($login_fabrica != 74){
								?>
									<table width="100%">
										<tr>
											<td style="text-align: center; font-weight:bold; font-size: 10px; background-color: #EFEBCF;" valign="top">
												Chamado Interno
											</td>
										</tr>
									</table>
								<?php
									}
								}

								?>
							<?=nl2br($aResposta['comentario'])?>
						</td>
						<?php
							if ($login_fabrica == 1) {
							?>
							<td style="text-align: center; width: 50px;" valign="middle" >
							<?php
								$file = hdNomeArquivoUpload($aResposta['hd_chamado_item']);

								if (empty($file)) {
									echo '&nbsp';
								} else {
							?>
									<a href="<?=TC_HD_UPLOAD_URL.$file?>" target="_blank" >
										<img src="../helpdesk/imagem/clips.gif" alt="Baixar Anexo" />
										Baixar Anexo
									</a>
							<?php
								}
							?>
							</td>
							<?php
							}
							?>
					</tr>
			<?php
				$count_interacao++;
			}
			echo "</table>";

		unset($aRespostas,$iResposta,$aResposta,$_hd_chamado);

			if ($login_fabrica == 59 AND strlen($admin_abriu) > 0) { // HD 52082 14/11/2008
				$sqlAdm = " SELECT login
							FROM tbl_admin
							WHERE fabrica = $login_fabrica AND admin = $admin_abriu";
				$resAdm = pg_query($con, $sqlAdm);

				if (pg_num_rows($resAdm ) > 0) $login_abriu = pg_fetch_result($resAdm, 0, $login);

				echo "<div style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
					echo "<b>CHAMADO ABERTO PELO ATENDENTE: " . $login_abriu."</b>";
				echo "</div>";
			}
		}

	?>
	</td>
</tr>
<tr>
	<td align='center' colspan='5'>
	<? if($login_fabrica == 3){ ?>
		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Mapa da Rede</strong></td>
			</tr>
		</table>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='50'><strong>Linha:</strong></td>
				<td align='left'>
				<?
				$sql = "SELECT  *
						FROM    tbl_linha
						WHERE   tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_linha.nome;";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res) > 0) {
					echo "<select name='mapa_linha' id='mapa_linha' class='frm'>\n";
					echo "<option value=''>Escolha</option>\n";
					for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
						$aux_linha = trim(pg_fetch_result($res,$x,linha));
						$aux_nome  = trim(pg_fetch_result($res,$x,nome));

						if ($linha == $aux_liha){
							$selected_linha = "SELECTED";
							$mostraMsgLinha = "<br> da LINHA $aux_nome";
						}
						echo "<option value='$aux_linha' $selected_linha >$aux_nome</option>\n";
					}
					echo "</select>\n&nbsp;";
				}
				?>
				</td>
				<td align='left' width='50'><strong>Estado:</strong></td>
				<td align='left'>


					<select name='mapa_estado' id='mapa_estado'>
						<option value='00' 		   >Todos</option>
						<option value='SP' selected>São Paulo</option>
						<option value='RJ'         >Rio de Janeiro</option>
						<option value='PR'         >Paraná</option>
						<option value='SC'         >Santa Catarina</option>
						<option value='RS'         >Rio Grande do Sul</option>
						<option value='MG'         >Minas Gerais</option>
						<option value='ES'         >Espírito Santo</option>
						<option value='BR-CO'      >Centro-Oeste</option>
						<option value='BR-NE'      >Nordeste</option>
						<option value='BR-N'       >Norte</option>
					</select>
				<td align='left' width='50'><strong>Cidade:</strong></td>
				<td align='left'><input type='text' id='mapa_cidade' name='mapa_cidade' value='<?=$mapa_cidade?>'>

					<input type='button' name='btn_mapa' value='mapa' onclick='javascript:mapa_rede(mapa_linha,mapa_estado,mapa_cidade)'>
					</font>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'>
					<input name="codigo_posto_tab" id="codigo_posto_tab"  class="input" value='<?echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15">
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input type='hidden' name='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab" id="posto_nome_tab"  class="input" value='<?echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="500">
				</td>
			</tr>

			<tr>
				<td colspan='6'>
				<?
				if(strlen($callcenter)==0){
					echo "<tr><td align='left' colspan='6'>";
					if($login_fabrica == 30){
						echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)'> Abrir OS para esta Autorizada</strong>";
					}else{
						echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)'> Abrir PRE-OS para esta Autorizada</strong>";
					}
					echo "<div id='imprimir_os' style='display:none'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
					echo "</td></tr>";
				}
				?>
				</td>
			</tr>
			</table>
			<BR>
		<? } ?>

	<?php
	if ($login_fabrica == 11) {
	?>
		<br />
		<table border="0" cellpadding="2" cellspacing="2" style="width: 100%; margin: 0 auto; border: #A95F5A 1px solid; background-color: #E7D1D2; font-size: 10px;" >
			<tr>
				<td style="center; width: 250px; font-weight: bold;" >
					Data programada para resolver este atendimento:
				</td>
				<td>
					<input type="text" class="frm" rel="data" name="data_programada" style="width: 80px;" value="<?=$data_programada?>" />
				</td>
			</tr>
		</table>
		<br />
	<?php
	}
	?>
</td>
</tr>
<?php
if (strlen($callcenter) > 0) {

?>

	<tr>
		<td align='center' colspan='5'>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" id="continuar_chamado" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'><STRONG>Posso ajudá-lo(a) em algo mais Sr.(a)?</STRONG><BR>
					</td>
					<td align='right' width='150'></td>
				</tr>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'></td>
					<td align='center'>
						<input type="hidden" name="hd_chamado_anterior" value="<?=$_POST['hd_chamado_anterior']?>" id="chamado_anterior" /><?php
							//HD 94971 acrescentei um botão que pergunta se quer enviar o históico ou não
							if ($login_fabrica == 59) {?>

								<script>
									function questiona(){
										{
											var name=confirm("Võcê deseja herdar o histórico desse chamado?")
											if (name==true)
											{
												window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>&herdar=sim';
											}
											else
											{
												window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>';
											}
										}
									}
								</script>
								<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:questiona();"><?php

							} else if(in_array($login_fabrica, array(24, 52, 74)) ) {

                                ?>

								<script>
									function questiona(){
										{
											var name=confirm("Dar continuidade ao chamado atual?")

											if (name==true)
                                                {
												window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>&continuar_chamado=sim';
											}
											else
											{
												window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>';
											}
										}
									}
								</script>
								<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:questiona();"><?php

							} else {?>

								<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>';"><?php

							}?>
						<input  class="input"  type="button" name="bt" value='No' onclick="javascript:window.location='<?=$PHP_SELF?>';">
					</td>
					<td align='right' width='150'></td>
				</tr>
			</table>
			<bR>
		</td>
	</tr>
	<tr>
		<td>

		<?php
		/**
		 * PESQUISA DE SATISFAÇÃO CALLCENTER FRICON
		 */
		if ($login_fabrica == 52 and $status_interacao == 'Resolvido'){ ?>

			<table>
				<tr>
					<td>
						<strong>PESQUISA COM CLIENTE</strong>
					</td>
				</tr>
			</table>

			<?php
			/**
			 * Verifica se o chamado ja existe resposta cadastrada do formato antigo (com tbl_resposta.pesquisa null) e exibe o formato antigo
			 * sempre que tiver um chamado ja antigo com respostas na
			 * tbl_resposta irá incluir o formulario antigo e exibir as respostas
			 * visto que, o processo novo de exibição/cadastro da pesquisa é totalmente diferente do antigo.
			 */

			$sql_verifica_antigo = "SELECT count(tbl_resposta.*)
						from tbl_resposta
						join tbl_pergunta using(pergunta)
						WHERE tbl_pergunta.fabrica = $login_fabrica
						AND tbl_resposta.pesquisa is null
						AND tbl_resposta.hd_chamado = $callcenter";
			$res_verifica_antigo = pg_query($con,$sql_verifica_antigo);

			$qtde_respostas_antigo = pg_fetch_result($res_verifica_antigo, 0, 0);

			if ($qtde_respostas_antigo > 0) {


				include_once "form_pesquisa_callcenter_antigo.php";

			}else{
			?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>

					<tr>
						<td>
							<?php
							/**
							 * VERIFICA SE O CHAMADO JA POSSUI PESQUISA RESPONDIDA.
							 * SE POSSUIR NAO PODE DEXAR GRAVAR NOVAS RESPOSTAS E
							 * DEVE MOSTRAR AS OPÇÕES JA PREENCHIDAS
							 */

							$sql = "SELECT pesquisa  from tbl_resposta where hd_chamado = $callcenter";
							$res = pg_query($con,$sql);
							if (pg_num_rows($res)>0) {
								$pesquisa_respondida = pg_fetch_result($res,0,0);
								$disabled_pesquisa_respondida = ' disabled="DISABLED"';

							}else{
								$pesquisa_respondida = '';
							}

							$sql = "SELECT pesquisa, descricao FROM tbl_pesquisa where fabrica = $login_fabrica and categoria = 'callcenter' and ativo is true";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {

								$checked_pesquisa_respondida = ($key['pesquisa'] == $pesquisa_respondida) ? 'checked="CHECKED"' : '' ;
								echo $key['descricao']." ";
								?>
								<input type="radio" name="pesquisa_fricon" id="pesquisa_<?echo $key['pesquisa']?>" value="<?echo $key['pesquisa']?>" rel="<?echo $key['descricao']?>" <?php echo $checked_pesquisa_respondida.$disabled_pesquisa_respondida?> > <br>
								<?

							}
							?>
						</td>
					</tr>
				</table>

				<div class='errorPergunta' style='background-color:#F92F2F;color:#FFF;font:bold 14px Arial'></div>

				<div class='divTranspBlock' style='margin-top:57px;margin-left:378px;display:none;background-color:#000;position:absolute; z-index:1;width:900px;height:295px;opacity:0.65;-moz-opacity: 0.65;filter: alpha(opacity=65);'>
				</div>

				<div id="div_pesquisa" style="width:100%;border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px;display:none">

				</div>

				<div class='agradecimentosPesquisa' style='display:none'>
					<p >
						Gravado com Sucesso
					</p>
					<p>
						BOM......EM NOME DA <b>FRICON</b>, GOSTARIAMOS DE AGRADECER SUA ATENÇÃO, E DESEJAMOS-LHE UM EXCELENTE DIA.
					</p>
				</div>

			<?
			}

			?>


		<?php
        }
        if(in_array($login_fabrica, array(30,85,94,145))){
            if(in_array($login_fabrica, array(85,94,145)) && $status_interacao == 'Resolvido' && strlen($msg_erro) == 0){
?>
            <br /><div id="questionario" style="text-align:center;width:100%;height:40px;background-color: #596D9B;">
                <span onclick="fnc_pesquisa_satisfacao(<?=$callcenter?>)" style="vertical-align:middle;cursor:pointer;color:#FFF; font-size:14px">Pesquisa de Satisfação</span>
            </div> <br />
<?
            }/*
            if($login_fabrica == 30 && strlen($callcenter) > 0){
?>
            <br /><div id="questionario">
                <span onclick="fnc_pesquisa_satisfacao(<?=$callcenter?>)" style="cursor:pointer;text-align:center">Pesquisa de Satisfação</span>
            </div> <br />
<?
            }*/
        }

        ?>

		</td>
	</tr>
	<tr>
		<td align='center' colspan='5'>
			<br>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'>
						<STRONG>FINALIZAR LIGAÇÃO</STRONG><BR>
						A <?echo "$nome_da_fabrica";?> agradece a sua ligação, tenha um(a) <?echo saudacao();?>.
					</td>
					<td align='right' width='150'></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="5" style="text-align: center;">
			<br />
			<div style="margin: 0 auto; text-align: center;">
				<a href='callcenter_interativo_print.php?callcenter=<?echo $callcenter;?>' target='_blank' style='font-size:14px; font-family:Verdana;'>
					<img src='imagens/img_impressora.gif'>
					Imprimir
				</a>
			</div>
		</td>
	</tr>
<?php
}
?>
</table>
</form><?php

include "rodape.php";?>
<?php

function buscaChamado($hd_chamado){
	global $con;
	global $login_fabrica;
	$sql = ' SELECT * FROM tbl_hd_chamado WHERE hd_chamado = $1;';
	$result = pg_query_params($con,$sql,array($hd_chamado));
	if(!$result || pg_num_rows($result) != 1)
		throw new Exception("Chamado não Encontrado\n".pg_last_error($con));
	return pg_fetch_assoc($result);
}

function buscaAdmin($admin){
	global $con;
	global $login_fabrica;

	$sql = 'SELECT * FROM tbl_admin WHERE admin = $1 AND fabrica = $2;';
	$result = pg_query_params($con,$sql,array($admin,$login_fabrica));
	if(!$result || pg_num_rows($result) != 1)
		throw new Exception('Admin desconhecido');
	return pg_fetch_assoc($result);
}

function enviaEmail($mail){
	$headers  = "MIME-Version: 1.0 \r\n";
    $headers .= "Content-type: text/html \r\n";
    if(!empty($mail['from']))
    	$headers .= "From: ".$mail['from']." \r\n";
    $headers .= "To: ".$mail['to']." \r\n";
	if(!mail($mail['to'],$mail['subject'],utf8_encode($mail['body']),$headers))
		throw new Excpetion('Falha ao enviar email');
}

function montaEmail($chamado,$newAdmin,$interacao=''){
	$oldAdmin = buscaAdmin($chamado['atendente']);
	$newAdmin = buscaAdmin($newAdmin);
	$mail['to'] = $oldAdmin['email'];
	$mail['from'] = 'helpdesk@telecontrol.com.br';
	$body = $oldAdmin['nome_completo'].",<br />";
	$body.= 'o chamado '.$chamado['hd_chamado'].' sofreu uma interação realizada por '.$newAdmin['nome_completo'].".<br /><br />";
	$body.= "Interação:<br />";
	$body.= $interacao;
	$mail['body'] = $body;
	$mail['subject'] = 'Seu chamado foi Alterado : '.$chamado['hd_chamado'];
	return $mail;
}
