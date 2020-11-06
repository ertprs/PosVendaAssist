<?php

$tdocs = new TdocsMirror;

$sqlTdocs = "SELECT tdocs_id, contexto
			 FROM tbl_tdocs 
			 WHERE fabrica = {$login_fabrica} 
			 AND referencia_id = {$riId} 
			 AND referencia ILIKE 'ri_%'";
$resTdocs = pg_query($con, $sqlTdocs);

while ($dadosTdocs = pg_fetch_object($resTdocs)) {

	$retornoAnexo = $tdocs->get($dadosTdocs->tdocs_id);

	$arrayImagens[$dadosTdocs->contexto][] = $retornoAnexo["link"];

}

$template = '
	<!DOCTYPE html>
        <head>
			<style>

				body {
					font-size: 10pt;
					font-family: sans-serif;
				}

				.bloco, table {
					width: 100%;
				}

				.titulo_tabela {
				  background-color:#2b2c50;
				  text-align:center;
				  padding: 10px;
				  font-size: 10pt;
				  color: white;
				}

				.titulo_coluna {
					background-color: #72849e;
					color: white;
					border: 2px black solid;
					font-size: 9pt;
				}

				.conteudo_coluna {
					border-bottom: 1px solid gray !important;
					font-size: 8pt !important;
				}

			</style>
		</head>
		<body>
			<h3 align=center>Relatório Informativo</h3>
			<div style="width: 100%;text-align: center;">
				<strong>Finalizado em: </strong> '.mostra_data($retorno["ri"]["data_conclusao"]).' &nbsp; &nbsp; &nbsp; &nbsp; 
				<strong>Grupo Follow-up: </strong> '.$retorno["ri_transferencia"]["nome_followup"].'
			</div>
			<br />
			<div class="bloco">
				<div class="titulo_tabela">Pós-Vendas</div>
				<table>
					<tbody>
						<tr class="linha">
							<td class="titulo_coluna">Data Abertura</td>
							<td class="conteudo_coluna">'.mostra_data(explode(" ",$retorno["ri"]["data_abertura"])[0]).'</td>
							<td class="titulo_coluna">Data Chegada</td>
							<td class="conteudo_coluna">'.mostra_data(explode(" ",$retorno["ri"]["data_chegada"])[0]).'</td>
							<td class="titulo_coluna">Código</td>
							<td class="conteudo_coluna">'.$retorno["ri"]["codigo"].'</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna">Aberto Por</td>
							<td class="conteudo_coluna">'.$retorno["ri"]["aberto_por"].'</td>
							<td class="titulo_coluna">Título</td>
							<td class="conteudo_coluna" colspan="3">'.$retorno["ri"]["titulo"].'</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna">Emitente</td>
							<td class="conteudo_coluna">'.$retorno["ri_posvenda"]["emitente"].'</td>
							<td class="titulo_coluna">E-mail</td>
							<td class="conteudo_coluna" colspan="3">'.$retorno["ri_posvenda"]["email"].'</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna">Qualidade</td>
							<td class="conteudo_coluna">'.$retorno["ri_posvenda"]["qualidade"].'</td>
							<td class="titulo_coluna">Setor</td>
							<td class="conteudo_coluna" colspan="3">'.$retorno["ri_posvenda"]["setor"].'</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna">Família</td>
							<td class="conteudo_coluna" colspan="4">'.$retorno["ri"]["descricao_familia"].'</td>
						</tr>
					</tbody>
				</table>
				<table>
					<tbody>
						<tr class="linha">
							<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="6">Produtos</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna" style="text-align: center;" colspan="2">Produto</td>
							<td class="titulo_coluna" style="text-align: center;">Qtde.</td>
							<td class="titulo_coluna" style="text-align: center;">Defeito</td>
							<td class="titulo_coluna" style="text-align: center;">Obs.</td>
							<td class="titulo_coluna" style="text-align: center;">Disp.</td>
						</tr>';

						for ($x = 0;$x < count($retorno["ri_posvenda_produto"]["produto"]); $x++) {

							$sqlDefeito = "SELECT descricao 
										   FROM tbl_defeito_constatado 
										   WHERE defeito_constatado = ".$retorno["ri_posvenda_produto"]["defeito_constatado"][$x];
							$resDefeito = pg_query($con, $sqlDefeito);

							$template .= '
							<tr class="linha" style="width: 100% !important;">
								<td class="conteudo_coluna" style="text-align: center;width: 25%;" colspan="2">
									'.$retorno["ri_posvenda_produto"]["referencia"][$x].' - '.$retorno["ri_posvenda_produto"]["descricao"][$x].'
								</td>
								<td class="conteudo_coluna" style="text-align: center;width: 5%;">
									'.$retorno["ri_posvenda_produto"]["qtde"][$x].'
								</td>
								<td class="conteudo_coluna" style="text-align: center;width: 15%;">
									'.pg_fetch_result($resDefeito, 0, "descricao").'
								</td>
								<td class="conteudo_coluna" style="text-align: center;width: 27.5%;">
									'.$retorno["ri_posvenda_produto"]["observacao"][$x].'
								</td>
								<td class="conteudo_coluna" style="text-align: center;width: 27.5%;">
									'.$retorno["ri_posvenda_produto"]["disposicao"][$x].'
								</td>
							</tr>';

						}

$template .= '  
					</tbody>
				</table>
				<table>
					<tbody>
						<tr class="linha">
							<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="8">Despesas da Garantia</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna">Custo Peça</td>
							<td class="conteudo_coluna">'.number_format($retorno["ri_posvenda"]["custo_peca"], 2, ",", ".").'</td>
							<td class="titulo_coluna">Valor Frete</td>
							<td class="conteudo_coluna">'.number_format($retorno["ri_posvenda"]["valor_frete"], 2, ",", ".").'</td>
							<td class="titulo_coluna">Mão de Obra</td>
							<td class="conteudo_coluna">'.number_format($retorno["ri_posvenda"]["mao_de_obra"], 2, ",", ".").'</td>
							<td class="titulo_coluna">Total</td>
							<td class="conteudo_coluna">'.number_format($retorno["ri_posvenda"]["total"], 2, ",", ".").'</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="8">Descrição do Problema</td>
						</tr>
						<tr class="linha">
							<td class="conteudo_coluna" colspan="8" style="height: 100px;"> &nbsp;&nbsp; '.$retorno["ri_posvenda"]["descricao_problema"].'</td>
						</tr>
					</tbody>
				</table>
			</div><br /><br />';

			foreach ($arrayImagens["ri_posvenda"] as $chave => $urlImg) {

				$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

			}

	$pdf->WriteHTML(utf8_encode($template));
	
	$listaAdms = [];
	foreach ($retorno["ri_time_analise"]["admin"] as $idAdmin) {

		$listaAdms[] = getNomeAdm($idAdmin);

	}

	$template = '<div class="bloco">
					<div class="titulo_tabela">Time de Análise (Equipe Multidisciplinar)</div>
					<table>
						<tbody>
							<tr class="linha">
								<td class="titulo_coluna">Envolvidos</td>
								<td class="conteudo_coluna"> &nbsp; '.implode(" &nbsp; | &nbsp; ", $listaAdms).'</td>
							</tr>
							<tr class="linha">
								<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="8">Descrição do Problema</td>
							</tr>
							<tr class="linha">
								<td class="conteudo_coluna" colspan="8" style="height: 150px;"> &nbsp;&nbsp; '.$retorno["ri_analise"]["descricao_problema"].'</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="bloco">
					<div class="titulo_tabela">Ação de Contenção</div>
					<table>
						<tbody>
							<tr class="linha">
								<td class="titulo_coluna">Data</td>
								<td class="conteudo_coluna"> &nbsp; '.mostra_data($retorno["ri_analise"]["acao_contencao_data"]).'</td>
								<td class="titulo_coluna">Responsável</td>
								<td class="conteudo_coluna"> &nbsp; '.getNomeAdm($retorno["ri_analise"]["acao_contencao_admin"]).'</td>
							</tr>
							<tr class="linha">
								<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="8">Descrição da Ação</td>
							</tr>
							<tr class="linha">
								<td class="conteudo_coluna" colspan="8" style="height: 150px;"> &nbsp;&nbsp; '.$retorno["ri_analise"]["acao_contencao"].'</td>
							</tr>
						</tbody>
					</table>
				</div>';

				foreach ($arrayImagens["ri_contencao"] as $chave => $urlImg) {

					$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

				}

$pdf->WriteHTML(utf8_encode($template));

		$template = '<div class="bloco">
						<div class="titulo_tabela">Análise de Causa</div>
						<table cellspacing="30px">
							<tbody>
							    <tr class="linha">
							    	<td class="titulo_coluna">Material</td>
							    	<td class="titulo_coluna">Mão de Obra</td>
							    </tr>
								<tr class="linha">
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["material"].'</td>
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["mao_de_obra"].'</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna">Máquina</td>
									<td class="titulo_coluna">Meio-Ambiente</td>
								</tr>
								<tr class="linha">
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["maquina"].'</td>
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["ambiente"].'</td>
								</tr>
								<tr>
									<td class="titulo_coluna">Método</td>
									<td class="titulo_coluna">Medição</td>
								</tr>
								<tr class="linha">
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["metodo"].'</td>
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["medicao"].'</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna" colspan="2" style="background-color: darkred;text-align: center;">Problema</td>
								</tr>
								<tr class="linha">
									<td class="conteudo_coluna" colspan="2"> &nbsp; '.$retorno["ri_analise"]["causa_efeito"]["problema"].'</td>
								</tr>
							</tbody>
						</table>';

		$template .= '<table cellspacing="25px" style="margin-left: 7.5px;">
					<tbody>
						<tr class="linha">
							<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="2">Análise dos 5 porquês ( Ocorrência e Não-detecção )</td>
						</tr>
						<tr class="linha">
							<td class="titulo_coluna" style="text-align: center;font-size: 9pt;" colspan="2">Porque da OCORRÊNCIA?</td>
						</tr>';

						for ($x = 0;$x < 5; $x++) {

							$template .= '
							<tr class="linha">
								<td style="font-size: 8pt;"><strong>'.($x + 1).'° Porque</strong></td>
								<td class="titulo_conteudo" style="border-left: 1px solid gray;border-right: 1px solid gray;font-size: 8pt;">'.$retorno["ri_analise"]['porque_ocorrencia'][$x].'</td>
							</tr>';

						}

		$template .= '
					  <tr class="linha">
						<td class="titulo_coluna" style="text-align: center;font-size: 9pt;" colspan="2">Porque da NÃO DETECÇÃO?</td>
					  </tr>
					  ';

					  for ($x = 0;$x < 5; $x++) {

							$template .= '
							<tr class="linha">
								<td style="font-size: 8pt;"><strong>'.($x + 1).'° Porque</strong></td>
								<td class="titulo_conteudo" style="border-left: 1px solid gray;font-size: 8pt;border-right: 1px solid gray;">'.$retorno["ri_analise"]['porque_nao_deteccao'][$x].'</td>
							</tr>';

					  }

		$template .= '
					  <tr class="linha">
							<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="2">Identificação da Causa Raíz</td>
						</tr>
						<tr class="linha">
							<td class="conteudo_coluna" colspan="2" style="height: 150px;"> &nbsp;&nbsp; '.$retorno["ri_analise"]["causa_raiz"].'</td>
						</tr>
					</tbody>
				</table>
			</div><br /><br />';

			foreach ($arrayImagens["ri_causa"] as $chave => $urlImg) {

				$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

			}

$pdf->WriteHTML(utf8_encode($template));

		$template = '<div class="bloco">
						<div class="titulo_tabela">Identificação e Verificação das Ações Corretivas</div>
						<table>
							<tbody>
								<tr class="linha">
									<td class="titulo_coluna">O quê?</td>
									<td class="titulo_coluna">Onde?</td>
									<td class="titulo_coluna">Quem?</td>
									<td class="titulo_coluna">Quando?</td>
								</tr>';

								for ($x = 0;$x < count($retorno["ri_acoes_corretivas"]["o_que"]); $x++) {

									
									$template .= '
									<tr class="linha" style="width: 100% !important;">
										<td class="conteudo_coluna" style="border: 1px solid black;">
											'.$retorno["ri_acoes_corretivas"]["o_que"][$x].'
										</td>
										<td class="conteudo_coluna" style="border: 1px solid black;">
											'.$retorno["ri_acoes_corretivas"]["onde"][$x].'
										</td>
										<td class="conteudo_coluna" style="border: 1px solid black;">
											'.$retorno["ri_acoes_corretivas"]["quem"][$x].'
										</td>
										<td class="conteudo_coluna" style="border: 1px solid black;">
											'.$retorno["ri_acoes_corretivas"]["quando"][$x].'
										</td>
									</tr>';

								}

		$template .= '	    </tbody>
						</table>
						<table>
							<tbody>
								<tr class="linha">
									<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="6">Poka Yoke</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna">Implementou Poka Yoke</td>
									<td class="conteudo_coluna"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["poka_yoke"]) ? "Sim" : "Não").'</td>
									<td class="titulo_coluna">Justificativa</td>
									<td class="conteudo_coluna" colspan="3"> &nbsp; '.$retorno["ri_acoes_corretivas"]["poka_yoke_justificativa"].'</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="6">Documentos Revisados</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna">Desenhos/Especificações</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["desenhos"] == "t") ? "&#10003;" : "").'</td>
									<td class="titulo_coluna">DVP FMEA</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["dvp"] == "t") ? "&#10003;" : "").'</td>
									<td class="titulo_coluna">PPAP</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["ppap"] == "t") ? "&#10003;" : "").'</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna">CEP</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["cep"] == "t") ? "&#10003;" : "").'</td>
									<td class="titulo_coluna">MSA</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["msa"] == "t") ? "&#10003;" : "").'</td>
									<td class="titulo_coluna">Plano de Controle</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["plano_controle"] == "t") ? "&#10003;" : "").'</td>
								</tr>
								<tr class="linha">
									<td class="titulo_coluna">Instrução Operacional</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["instrucao"] == "t") ? "&#10003;" : "").'</td>
									<td class="titulo_coluna">Procedimentos</td>
									<td class="conteudo_coluna" style="font-size: 20pt;text-align: center;"> &nbsp; '.(($retorno["ri_acoes_corretivas"]["documentos"]["procedimentos"] == "t") ? "&#10003;" : "").'</td>
									<td class="titulo_coluna">Outros</td>
									<td class="conteudo_coluna"> &nbsp; '.$retorno["ri_acoes_corretivas"]["documentos"]["outros"].'</td>
								</tr>
							</tbody>
						</table>
					</div>';

					foreach ($arrayImagens["ri_identificacao"] as $chave => $urlImg) {

						$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

					}

$pdf->WriteHTML(utf8_encode($template));

$template = '   <div class="bloco">
					<div class="titulo_tabela">Implementação Permanente das Ações Corretivas</div>
					<table>
						<tbody>
							<tr class="linha">
								<td class="titulo_coluna">Data</td>
								<td class="conteudo_coluna"> &nbsp; '.mostra_data($retorno["ri_acoes_corretivas"]["implementacao_permanente_data"]).'</td>
								<td class="titulo_coluna">Responsável</td>
								<td class="conteudo_coluna"> &nbsp; '.getNomeAdm($retorno["ri_acoes_corretivas"]["implementacao_permanente_admin"]).'</td>
							</tr>
							<tr class="linha">
								<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="8">Verificação da Implementação</td>
							</tr>
							<tr class="linha">
								<td class="conteudo_coluna" colspan="8" style="height: 100px;"> &nbsp;&nbsp; '.$retorno["ri_acoes_corretivas"]["implementacao_permanente"].'</td>
							</tr>
						</tbody>
					</table>
				</div><br /><br />';

				foreach ($arrayImagens["ri_implementacao"] as $chave => $urlImg) {

					$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

				}

$template .= '
				<div class="bloco">
					<div class="titulo_tabela">Verificação da Eficácia das Ações Corretivas</div>
					<table>
						<tbody>
							<tr class="linha">
								<td class="titulo_coluna">Data</td>
								<td class="conteudo_coluna"> &nbsp; '.mostra_data($retorno["ri_acoes_corretivas"]["verificacao_eficacia_data"]).'</td>
								<td class="titulo_coluna">Responsável</td>
								<td class="conteudo_coluna"> &nbsp; '.getNomeAdm($retorno["ri_acoes_corretivas"]["verificacao_eficacia_admin"]).'</td>
							</tr>
							<tr class="linha">
								<td class="titulo_coluna" style="background-color: #495d7a;text-align: center;" colspan="8">Verificação da Eficácia</td>
							</tr>
							<tr class="linha">
								<td class="conteudo_coluna" colspan="8" style="height: 100px;"> &nbsp;&nbsp; '.$retorno["ri_acoes_corretivas"]["verificacao_eficacia"].'</td>
							</tr>
						</tbody>
					</table>
				</div><br /><br />';

				foreach ($arrayImagens["ri_eficacia"] as $chave => $urlImg) {

					$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

				}

$template .= '
				<div class="bloco">
					<div class="titulo_tabela">Conclusão (Fechamento do RI)</div>
					<table>
						<tbody>
							<tr class="linha">
								<td class="conteudo_coluna" colspan="8" style="height: 100px;"> &nbsp;&nbsp; '.$retorno["ri"]["conclusao"].'</td>
							</tr>
						</tbody>
					</table>
				</div><br /><br />';

				foreach ($arrayImagens["ri_conclusao"] as $chave => $urlImg) {

					$template .= "<img src='{$urlImg}' style='margin: 15px;max-height: 200px;' />";

				}

$template .= '
			</body>
	</html>';

$pdf->WriteHTML(utf8_encode($template));


function getNomeAdm($admin) {
	global $con;

	$sqlAdm = "SELECT nome_completo FROM tbl_admin WHERE admin = {$admin}";
	$resAdm = pg_query($con, $sqlAdm);

	return pg_fetch_result($resAdm, 0, "nome_completo");

}