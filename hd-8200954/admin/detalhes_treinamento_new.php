<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
#include 'includes/funcoes.php';
include '../ajax_cabecalho.php';
include 'funcoes.php';
$admin_privilegios="info_tecnica,call_center";
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

	$treinamento = $_GET["treinamento"];
	$cor         = $_GET["cor"];

	$sql = "SELECT tbl_treinamento.treinamento,
			tbl_treinamento.titulo,
			tbl_treinamento.descricao,
			tbl_treinamento.ativo,
			tbl_treinamento.local,
			tbl_treinamento.vagas,
			tbl_treinamento.vagas_min,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
			TO_CHAR(tbl_treinamento.prazo_inscricao,'DD/MM/YYYY') AS prazo_inscricao,
			tbl_admin.nome_completo,
			tbl_linha.nome                                        AS linha_nome,
			tbl_familia.descricao                                 AS familia_descricao,
			(
				SELECT COUNT(*)
				FROM tbl_treinamento_posto
				WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
				AND   tbl_treinamento_posto.ativo IS TRUE
				AND tbl_treinamento_posto.tecnico IS NOT NULL
			)                                                     AS qtde_postos,
			tbl_treinamento.adicional,
			tbl_treinamento.cidade,
			tbl_treinamento.visivel_portal
		FROM tbl_treinamento
		JOIN      tbl_admin   USING(admin)
		JOIN      tbl_linha   USING(linha)
		LEFT JOIN tbl_familia USING(familia)
		WHERE tbl_treinamento.fabrica = $login_fabrica
		AND   tbl_treinamento.treinamento = $treinamento
		ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo" ;

	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$treinamento       = trim(pg_fetch_result($res,0,'treinamento'));
		$titulo            = trim(utf8_encode(pg_fetch_result($res,0,'titulo')));
		$descricao         = trim(utf8_encode(pg_fetch_result($res,0,'descricao')));
		$ativo             = trim(pg_fetch_result($res,0,'ativo'));
		$data_inicio       = trim(pg_fetch_result($res,0,'data_inicio'));
		$data_fim          = trim(pg_fetch_result($res,0,'data_fim'));
		$prazo_inscricao   = trim(pg_fetch_result($res,0,'prazo_inscricao'));
		$nome_completo     = trim(pg_fetch_result($res,0,'nome_completo'));
		$linha_nome        = trim(pg_fetch_result($res,0,'linha_nome'));
		$familia_descricao = trim(pg_fetch_result($res,0,'familia_descricao'));
		$vagas             = trim(pg_fetch_result($res,0,'vagas'));
		$vagas_min         = trim(pg_fetch_result($res,0,'vagas_min'));
		$qtde_postos       = trim(pg_fetch_result($res,0,'qtde_postos'));
		$adicional         = trim(utf8_encode(pg_fetch_result($res,0,'adicional')));
		$local             = trim(pg_fetch_result($res,0,'local'));
		$cidade            = trim(pg_fetch_result($res,0,'cidade'));
		$visivel_portal    = trim(pg_fetch_result($res,0,'visivel_portal'));

		$array_resposta['Tema'] = utf8_decode($titulo);
		$array_resposta['Linha'] = htmlentities($linha_nome);
		$array_resposta['Fam&iacute;lia'] = htmlentities($familia_descricao);
		$array_resposta['Data de In&iacute;cio'] = $data_inicio;
		$array_resposta['Data de T&eacute;rmino'] = $data_fim;
		if ($treinamento_prazo_inscricao) {
			$array_resposta['Inscri&ccedil;&otilde;es at&eacute;'] = $prazo_inscricao;
		}
		$array_resposta['Informa&ccedil;&otilde;es Adicionais'] = utf8_decode($adicional);
		if ($login_fabrica == 117) { //elgin
			$visivel_portal = ($visivel_portal == 't') ? 'Sim':'Não';
			$array_resposta['Visualizar Portal'] = htmlentities($visivel_portal);
		}
		if ($treinamento_vagas_min) {
			$array_resposta['Mínimo de Vagas'] = $vagas_min;
		}
		$array_resposta['Vagas'] = $vagas;
		$array_resposta['Inscritos'] = $qtde_postos;


		$resposta .= "<table class='table table-fixed' style='border:0px !important' >
						<tr>
							<td valign='top' class='span6'  border=0>";

			$resposta .= "<table  span6' >
			<tbody>";
			foreach ($array_resposta as $dt=>$dd) {
				$corLinha = ($corLinha == '#F7F5F0') ? '#F1F4FA' : '#F7F5F0';
				$resposta .= "<tr bgcolor='$corLinha'>";
				$resposta .= "<td class='span3' align='left'  border=0><b>$dt</b></td>";
				$resposta .= "<td class='span3' align='left'  border=0>$dd</td>";
				$resposta .= "</tr>";
			}
			$resposta .= "</tbody></table>";

			$resposta .= "</td><td class='descricao_detalhe span5'  border=0>";
			#$resposta .= "<div><b>Descri&ccedil;&atilde;o:</b><br>".nl2br(htmlentities($descricao))."</div></td>";
			$resposta .= "<div><b>Descri&ccedil;&atilde;o:</b><br>".nl2br(utf8_decode($descricao))."</div></td>";

			if ($login_fabrica == 117 or $login_fabrica == 42) {
				if ($login_fabrica == 117 && $cidade != "") {
					$sql = "SELECT cidade,nome,estado from tbl_cidade where cidade = $cidade;";

					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$cidade        = pg_fetch_result($res,0,'cidade');
						$nome_cidade   = pg_fetch_result($res,0,'nome');
						$estado_cidade = pg_fetch_result($res,0,'estado');
					}else{
						$cidade = "";
						$nome_cidade = "";
						$estado_cidade = "";
					}
					$local = (!empty($local)) ? $local.', ' : $local;
					$local = $local.$nome_cidade." - ".$estado_cidade;
				}
				$resposta .= "<td valign='top' align='justify' bgcolor='#F1F4FA'><b>Local:</b><br>".htmlentities($local)."</td>";
			}
			$resposta .= "</tr>";
			$resposta .= "</table>";

		$resposta .= "</td></tr>";
		$resposta .= "</table>";

	}

	$sql = "SELECT  tbl_treinamento_posto.treinamento_posto,
					tbl_tecnico.nome     AS tecnico_nome,
					tbl_tecnico.rg       AS tecnico_rg,
					tbl_tecnico.cpf      AS tecnico_cpf,
					tbl_tecnico.email    AS tecnico_email,
					tbl_tecnico.telefone AS tecnico_fone,
					tbl_treinamento_posto.ativo,
					tbl_treinamento_posto.hotel,
					tbl_treinamento_posto.participou,
					tbl_treinamento_posto.confirma_inscricao,
					tbl_treinamento_posto.promotor,
					tbl_treinamento_posto.motivo_cancelamento AS motivo,
					TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
					TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao,
					tbl_posto.nome                                             AS posto_nome,
					tbl_posto.estado,
					tbl_posto_fabrica.codigo_posto,
					tbl_promotor_treinamento.nome,
					tbl_treinamento_posto.observacao    AS observacao_antigo,
					tbl_treinamento_posto.tecnico_nome  AS tecnico_nome_antigo,
					tbl_treinamento_posto.tecnico_rg    AS tecnico_rg_antigo,
					tbl_treinamento_posto.tecnico_cpf   AS tecnico_cpf_antigo,
					tbl_treinamento_posto.tecnico_email AS tecnico_email_antigo,
					tbl_treinamento_posto.tecnico_fone  AS tecnico_fone_antigo
			   FROM tbl_treinamento_posto
		  LEFT JOIN tbl_promotor_treinamento USING(promotor_treinamento)
		  LEFT JOIN tbl_posto USING(posto)
		  LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto       = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		  LEFT JOIN tbl_admin         ON tbl_treinamento_posto.admin   = tbl_admin.admin
		  LEFT JOIN tbl_tecnico       ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
			  WHERE tbl_treinamento_posto.treinamento = $treinamento
				AND tbl_treinamento_posto.ativo IS TRUE
				AND tbl_treinamento_posto.tecnico IS NOT NULL
		   ORDER BY tbl_posto.nome" ;

	$res = pg_query($con,$sql);

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($res) > 0) {

			$data = date("d-m-Y-H:i");
			$filename = "relatorio-os-{$data}.csv";
			$file = fopen("/tmp/{$filename}", "w");

			$titulo = array("cnpj" => "CNPJ" ,"posto" => "POSTO", "uf" => "UF", "info_tecnico" => "Nome do Tecnico", "rg_tecnico" => "RG do Tecnico","cpf_tecnico" => "CPF do Tecnico","fone_tecnico" => "Fone do Tecnico","info_adicionais" => $adicional,
				"data" => "Data", "inscricao" => "Inscrição", "confirmado_email" => "Confirmado por email", "hotel" => "Hotel",
				"presente" => "Presente", "motivo_cancelamento" => "Motivo do Cancelamento"
			);
			if(!$adicional){
				unset($titulo['info_adicionais']);
			}
			$titulo = implode(';', $titulo)."\r\n";
			
			fwrite($file, $titulo);
			for ($i=0; $i<pg_num_rows($res); $i++){

				$treinamento_posto = trim(pg_fetch_result($res,$i,'treinamento_posto'));
				$tecnico_nome      = trim(pg_fetch_result($res,$i,'tecnico_nome'));
				if($tecnico_nome == "" and trim(pg_fetch_result($res,$i,'tecnico_nome_antigo')) != ""){
					$tecnico_nome  = trim(pg_fetch_result($res,$i,'tecnico_nome_antigo'));
					$tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg_antigo'));
					$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
					$tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf_antigo'));
					$tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email_antigo'));
					$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
				}else{
					$tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg'));
					$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
					$tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf'));
					$tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email'));
					$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
				}
				$tecnico_tipo_sanguineo       = trim(pg_fetch_result($res,$i,'tecnico_tipo_sanguineo'));
				$tecnico_calcado              = trim(pg_fetch_result($res,$i,'tecnico_calcado'));
				$tecnico_celular              = trim(pg_fetch_result($res,$i,'tecnico_celular'));
				$tecnico_doencas              = trim(pg_fetch_result($res,$i,'tecnico_doencas'));
				$tecnico_medicamento          = trim(pg_fetch_result($res,$i,'tecnico_medicamento'));
				$tecnico_necessidade_especial = trim(pg_fetch_result($res,$i,'tecnico_necessidade_especial'));
				$motivo 	                  = trim(pg_fetch_result($res,$i,'motivo'));
				$data_inscricao               = trim(pg_fetch_result($res,$i,'data_inscricao'));
				$hora_inscricao               = trim(pg_fetch_result($res,$i,'hora_inscricao'));
				$posto_nome                   = trim(pg_fetch_result($res,$i,'posto_nome'));
				$estado                       = trim(pg_fetch_result($res,$i,'estado'));
				$codigo_posto                 = trim(pg_fetch_result($res,$i,'codigo_posto'));
				$ativo                        = trim(pg_fetch_result($res,$i,'ativo'));
				$hotel                        = trim(pg_fetch_result($res,$i,'hotel'));
				$participou                   = trim(pg_fetch_result($res,$i,'participou'));
				$promotor                     = trim(pg_fetch_result($res,$i,'promotor'));
				$confirma                     = trim(pg_fetch_result($res,$i,'confirma_inscricao'));
				$nome                         = trim(pg_fetch_result($res,$i,'nome'));
				$observacao                   = trim(pg_fetch_result($res,$i,'observacao_antigo'));
				if($ativo == 't'){
					$ativo   = "<img src='imagens_admin/status_verde.gif' id='tec_img_ativo_$i'>";
					$x_ativo = "Confirmado";
				}else{
					$ativo = "<img src='imagens_admin/status_vermelho.gif' id='tec_img_ativo_$i'>";
					$x_ativo = "Cancelado";
				}

				if($participou == 't'){
					$x_participou = "Sim";
				}else{
					$x_participou = "Não";
				}
				if($confirma == 't'){
					$x_confirma = "Sim";
				}else{
					$x_confirma = "Não";
				}
				if($login_fabrica != 117){
					if($hotel == 't'){
						$x_hotel = "Sim";
					}else{
						$x_hotel = "Não";
					}
				}

				$codigo_nome_posto = $codigo_posto.'-'.$posto_nome;
				#$dados_tecnico = "Nome: ".$tecnico_nome." RG: ".$tecnico_rg." CPF: ".$tecnico_cpf." FONE: ".$tecnico_fone;
				$dados_tecnico = array($tecnico_nome,$tecnico_rg,$tecnico_cpf,$tecnico_fone);
				$data_hora = "Data: ".$data_inscricao." Hora: ".$hora_inscricao;

				$linhas_result = array("cnpj" => $codigo_posto, "posto" => $posto_nome, "uf" => $estado, "info_tecnico" => $dados_tecnico,
					"info_adicionais" => $observacao,"data" => $data_hora, "inscricao" => $x_ativo,
					"confirmado_email" => $x_confirma, "hotel" => $x_hotel,
					"presente" => $x_participou, "motivo_cancelamento" => $motivo
				);
				if(!$adicional){
					unset($linhas_result['info_adicionais']);
				}

				foreach($linhas_result as $key => $valor){
					$linhas_result[$key] = str_replace(";","",$valor);
				}

				$linhas_result["info_tecnico"] = implode(";",$dados_tecnico);
				$linhas_result = implode(";", $linhas_result)."\r\n";
				#echo $linhas_result; exit;
				fwrite($file, $linhas_result);



			}
			fclose($file);
			if (file_exists("/tmp/{$filename}")) {
				system("mv /tmp/{$filename} xls/{$filename}");
				echo "xls/{$filename}";
			}
		}

		exit;
	}


	if (pg_num_rows($res) > 0) {

		//$resposta  .=  "<table class='table table-striped table-fixed formulario' >";
		$resposta  .=  "<table border='0' cellpadding='0' cellspacing='0' class='table table-striped table-fixed'  align='center' width='700px'>";
		$resposta  .=  "<thead>";
		$resposta  .=  "<TR class='titulo_coluna'  height='25'>";
		$resposta  .=  "<th>Posto</th>";
		$resposta  .=  "<th width='25'>UF</th>";
		$resposta  .=  "<th>Informações do T&eacute;cnico</th>";
		if ($adicional) $resposta .= "<th WIDTH=110>".htmlentities($adicional)."</th>";
		if($login_fabrica == 20) $resposta  .=  "<th width='80'>Promotor</th>";
		$resposta  .=  "<th >Data</th>";
		$resposta  .=  "<th width='60' colspan='2'>Inscri&ccedil;&atilde;o</th>";
		$resposta  .=  "<th width='60' colspan='2'>Confirmado<br> por email</th>";
		if($login_fabrica != 117){
			$resposta  .=  "<th width='60' colspan='2'>Hotel</th>";
		}
		$resposta  .=  "<th width='60' colspan='2'>Presente</th>";
		$resposta  .=  "<th >Motivo Cancelamento</th>";
		$resposta  .=  "</TR>";
		$resposta  .=  "</thead>";

		for ($i=0; $i<pg_num_rows($res); $i++){

			$treinamento_posto = trim(pg_fetch_result($res,$i,'treinamento_posto'));
			$tecnico_nome      = trim(pg_fetch_result($res,$i,'tecnico_nome'));
			if($tecnico_nome == "" and trim(pg_fetch_result($res,$i,'tecnico_nome_antigo')) != ""){
				$tecnico_nome  = trim(pg_fetch_result($res,$i,'tecnico_nome_antigo'));
				$tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg_antigo'));
				$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
				$tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf_antigo'));
				$tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email_antigo'));
				$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone_antigo'));
			}else{
				$tecnico_rg    = trim(pg_fetch_result($res,$i,'tecnico_rg'));
				$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
				$tecnico_cpf   = trim(pg_fetch_result($res,$i,'tecnico_cpf'));
				$tecnico_email = trim(pg_fetch_result($res,$i,'tecnico_email'));
				$tecnico_fone  = trim(pg_fetch_result($res,$i,'tecnico_fone'));
			}
			$tecnico_tipo_sanguineo       = trim(pg_fetch_result($res,$i,'tecnico_tipo_sanguineo'));
			$tecnico_calcado              = trim(pg_fetch_result($res,$i,'tecnico_calcado'));
			$tecnico_celular              = trim(pg_fetch_result($res,$i,'tecnico_celular'));
			$tecnico_doencas              = trim(pg_fetch_result($res,$i,'tecnico_doencas'));
			$tecnico_medicamento          = trim(pg_fetch_result($res,$i,'tecnico_medicamento'));
			$tecnico_necessidade_especial = trim(pg_fetch_result($res,$i,'tecnico_necessidade_especial'));
			$motivo 	                  = trim(pg_fetch_result($res,$i,'motivo'));
			$data_inscricao               = trim(pg_fetch_result($res,$i,'data_inscricao'));
			$hora_inscricao               = trim(pg_fetch_result($res,$i,'hora_inscricao'));
			$posto_nome                   = trim(pg_fetch_result($res,$i,'posto_nome'));
			$estado                       = trim(pg_fetch_result($res,$i,'estado'));
			$codigo_posto                 = trim(pg_fetch_result($res,$i,'codigo_posto'));
			$ativo                        = trim(pg_fetch_result($res,$i,'ativo'));
			$hotel                        = trim(pg_fetch_result($res,$i,'hotel'));
			$participou                   = trim(pg_fetch_result($res,$i,'participou'));
			$promotor                     = trim(pg_fetch_result($res,$i,'promotor'));
			$confirma                     = trim(pg_fetch_result($res,$i,'confirma_inscricao'));
			$nome                         = trim(pg_fetch_result($res,$i,'nome'));
			$observacao                   = trim(pg_fetch_result($res,$i,'observacao'));


			if($ativo == 't'){
				$ativo   = "<img src='imagens_admin/status_verde.gif' id='tec_img_ativo_$i'>";
				$x_ativo = "Confirmado";
			}
			else{
				$ativo = "<img src='imagens_admin/status_vermelho.gif' id='tec_img_ativo_$i'>";
				$x_ativo = "Cancelado";
			}

			if($participou == 't'){
				$participou = "<img src='imagens_admin/status_verde.gif' id='participou_img_$i'>";
				$x_participou = "Sim";
			}
			else{
				$participou = "<img src='imagens_admin/status_vermelho.gif' id='participou_img_$i'>";
				$x_participou = "Não";
			}
			if($confirma == 't'){
				$confirma = "<img src='imagens_admin/status_verde.gif' id='confirma_img_$i'>";
				$x_confirma = "Sim";
			}
			else{
				$confirma = "<img src='imagens_admin/status_vermelho.gif' id='confirma_img_$i'>";
				$x_confirma = "Não<br><a href='treinamento_cadastro.php?treinamento_posto=$treinamento_posto&ajax=enviar'>Enviar</a>";
			}
			if($login_fabrica != 117){
				if($hotel == 't'){
					$hotel = "<img src='imagens_admin/status_verde.gif' id='hotel_img_$i'>";
					$x_hotel = "Sim";
				}
				else{
					$hotel = "<img src='imagens_admin/status_vermelho.gif' id='hotel_img_$i'>";
					$x_hotel = "Não";
				}
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			$resposta  .=  "<TR class='Conteudo' id='inscricao_$treinamento_posto' bgcolor='$cor'>";
			$resposta  .=  "<TD align='left'>$codigo_posto - $posto_nome </TD>";
			$resposta  .=  "<TD align='center'nowrap>$estado</TD>";
			if($login_fabrica == 42){
				$resposta  .=  "<TD align='left'nowrap>
								<b>Nome: </b>".htmlentities($tecnico_nome)." <br>
								<b>E-mail:</b> $tecnico_email<br>
								<b>RG:</b> $tecnico_rg<br>
								<b>CPF:</b> $tecnico_cpf<br>
								<b>Fone:</b> $tecnico_fone<br>
								<b>Celular:</b> $tecnico_celular<br>
								<b>Tipo Sangu&iacute;neo:</b> $tecnico_tipo_sanguineo<br>
								<b>N&ord; do Calçado:</b> $tecnico_calcado<br>
								<b>O Participante sofreu ou sofre de alguma doença? - </b> $tecnico_doencas<br>
								<b>Toma algum medicamento controlado? Qual? - </b> $tecnico_medicamento<br>
								<b>&Eacute; portador de alguma necessidade especial? Qual? - </b> ".htmlentities($tecnico_necessidade_especial)."<br>
								<button type='button' class='btn btn-link' id='alterar_$treinamento_posto'>[Alterar dados]</button>
							</TD>";
			}else{
				$resposta  .=  "<TD align='left'nowrap>
									<b>Nome: </b>".htmlentities($tecnico_nome)." <br>
									<b>RG:</b> $tecnico_rg<br>
									<b>CPF:</b> $tecnico_cpf<br>
									<b>Fone:</b> $tecnico_fone<br>
									<button type='button' class='btn btn-link' id='alterar_$treinamento_posto'>[Alterar dados]</button>
								</TD>";
			}
			if ($adicional) $resposta .= "<TD>$observacao</TD>";
			if($login_fabrica == 20){
				$resposta  .=  "<TD align='left'>";
				if(strlen($nome)>0) $resposta  .=  "$nome";
				else                $resposta  .=  "$promotor";
			}

			$resposta  .=  "</TD>";
			$resposta  .=  "<TD align='center'>$data_inscricao <br> $hora_inscricao</TD>";
			$resposta  .=  "<TD align='center'>$ativo</TD>";
			$resposta  .=  "<TD align='center' width='60' title='Inscri&ccedil;&atilde;o?'><div id='tec_ativo_$i'><a href='javascript:if (confirm(\"Deseja cancelar esta inscrição?\") == true) {ativa_desativa_tecnico(\"$treinamento_posto\",\"$i\")}'>$x_ativo</a></div></TD>";

			$resposta  .=  "<TD align='center'>$confirma</TD>";
			$resposta  .=  "<TD align='center' width='60'title='Confirmado inscri&ccedil;&atilde;o por email?'><div id='confirma_$i'>$x_confirma</div></TD>";

			if ($login_fabrica == 20){
				$resposta  .=  "<TD align='center'>$hotel</TD>";
				$resposta  .=  "<TD align='center' width='60' title='Agendar Hotel?'><div id='hotel_$i'>$x_hotel</div></TD>";
			}else{
				if($login_fabrica != 117){
					$resposta  .=  "<TD align='center'>$hotel</TD>";
					$resposta  .=  "<TD align='center' width='60' title='Agendar Hotel?'><div id='hotel_$i'><a href=\"javascript:ativa_desativa_hotel('$treinamento_posto','$i')\">$x_hotel</a></div></TD>";
				}

			}

			$resposta  .=  "<TD align='center'>$participou</TD>";
			$resposta  .=  "<TD align='center' width='60' title='Esteve presente no treinamento?'><div id='participou_$i'><a href='javascript:ativa_desativa_participou(\"$treinamento_posto\",\"$i\")'>$x_participou</a></div></TD>";
            $resposta  .= "<td>$motivo</td>";
			$resposta  .=  "</TR>";

		}
		$resposta .= " </TABLE>";

		if($login_fabrica == 138){
			$jsonPOST = excelPostToJson($_POST);

			$resposta .="<div id='gerar_excel' class='btn_excel'>
					<input type='hidden' id='jsonPOST' value='".$jsonPOST."'/>
					<span><img src='imagens/excel.png' /></span>
					<span class='txt'>Gerar Arquivo Excel</span>
				</div>";
		}


	}else{

		if($qtde_postos == 0)	{
			$resposta .= "<b> Nenhum posto fez a inscri&ccedil;&atilde;o de seu t&eacute;cnico para participar do treinamento</b>";
		}


	}

	//exit;
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
		<script>
			// HDMONTEIRO //
			$(function(){
	    		var loadingCount = 0;
	    		var zindexSelector = '.ui-widget';
	    		var subZIndex = function(){
	    			$(zindexSelector).each(function(){
	    				var oldZindex = $(this).css('z-index');
	    				$(this).attr('old-z-index',oldZindex);
	    				$(this).css('z-index',1);
	    			});
	    		};
	    		var returnZIndex = function(){
	    			$('[old-z-index]').each(function(){
	    				var oldZindex = $(this).attr('old-z-index');
	    				$(this).removeAttr('old-z-index');
	    				$(this).css('z-index',oldZindex);
	    			});
	    		};
	    		var funcLoading = function(display){
		    		switch (display) {
		    			case true:
		    			case "show":
		    				loadingCount += 1;
		    				if(loadingCount != 1)
		    					return;
		    				subZIndex();
		    				$("#loading").show();
		    				$("#loading-block").show();
							$("#loading_action").val("t");
		    				break;
		    			case false:
		    			case "hide":
		    				if(loadingCount >0)
		    					 loadingCount-= 1;
		    				if(loadingCount != 0)
		    					return;
		    				$("#loading").hide();
							$("#loading_action").val("f");
							$("#loading-block").hide();
							returnZIndex();
		    				break;
		    		}
	    		};
	    		window.loading = funcLoading;
	    	});

			function ajaxAction () {
	    		if ($("#loading_action").val() == "t") {
	    			alert("Espere o processo atual terminar!");
	    			return false;
	    		} else {
	    			return true;
	    		}
	    	}

			$(function () {
	    		$("#gerar_excel").click(function () {
	    			if (ajaxAction()) {
	    				var json = $.parseJSON($("#jsonPOST").val());
	    				json["gerar_excel"] = true;
	    				var treinamento = <?=$treinamento?>;
	    				var urll = "detalhes_treinamento_new.php?treinamento="+treinamento;
	    				$.ajax({
		    				url: urll,
		    				type: "POST",
		    				data: json,
		    				beforeSend: function () {
		    					loading("show");
		    				},
		    				complete: function (data) {
		    					window.open(data.responseText, "_blank");
		    					loading("hide");
		    				}
		    			});
	    			}
	    		});

                $("button[id^=alterar_]").click(function(e){
                    e.preventDefault();

                    var botao               = $(this).attr("id");
                    var aux                 = botao.split("_");
                    var treinamento_posto   = aux[1];

                    $.ajax({
                        url:"ajax_treinamento.php",
                        type:"POST",
                        dataType:"html",
                        data:{
                            ajax:true,
                            tipo:"mostraTecnico",
                            treinamento_posto:treinamento_posto
                        },
                        beforeSend:function(){
                            $("button[id^=alterar_]").each(function(){
                                $(this).prop("disabled",true);
                            });
                        }
                    })
                    .done(function(data){
                        $("#inscricao_"+treinamento_posto).after(data);
                    });
                });
	    	});
			//HDMONTEIRO //
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

			var http_forn = new Array();

			function ativa_desativa_tecnico(treinamento,id) {

				var com = document.getElementById("tec_ativo_"+id);
				var img = document.getElementById("tec_img_ativo_"+id);

				com.innerHTML   ="Espere...";

				var acao='ativa_desativa_tecnico';

				url = "ajax_treinamento.php?ajax=sim&acao="+acao+"&treinamento_posto="+treinamento+"&id="+id;

				var curDateTime = new Date();
				http_forn[curDateTime] = createRequestObject();
				http_forn[curDateTime].open('GET',url,true);

				http_forn[curDateTime].onreadystatechange = function(){
					if (http_forn[curDateTime].readyState == 4)
					{
						if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304)
						{
							var response = http_forn[curDateTime].responseText.split("|");
							if (response[0]=="ok"){
								com.innerHTML   = response[1];
								img.src = "imagens_admin/status_"+response[2]+".gif";

							}
						}
					}
				}
				http_forn[curDateTime].send(null);
			}

function gravaTecnico(treinamento_posto){
    var nome        = $("#txt_tecnico_nome_"+treinamento_posto).val();
    var rg          = $("#txt_tecnico_rg_"+treinamento_posto).val();
    var cpf         = $("#txt_tecnico_cpf_"+treinamento_posto).val();
    var telefone    = $("#txt_tecnico_fone_"+treinamento_posto).val();

    $.ajax({
        url:"ajax_treinamento.php",
        type:"POST",
        dataType:"JSON",
        data:{
            ajax:true,
            tipo:"gravaTecnico",
            treinamento_posto:treinamento_posto,
            nome:nome,
            rg:rg,
            cpf:cpf,
            telefone:telefone
        }
    })
    .done(function(data){
        if (data.ok) {
            alert(data.msg);
            window.location.reload();
        }
    })
    .fail(function(){
        alert("Erro ao alterar técnico");
        $("#resp_"+treinamento_posto).detach();
        $("button[id^=alterar_]").each(function(){
            $(this).prop("disabled",true);
        });

    });
}
		</script>
	</head>
	<body>
		<div class="container-fluid form_tc" style="height:600px; overflow: auto;">
			<div class="titulo_tabela">Dados do Treinamento</div>
			<?php
				echo $resposta."<p>";
			?>
		</div>
	</body>
</html>
