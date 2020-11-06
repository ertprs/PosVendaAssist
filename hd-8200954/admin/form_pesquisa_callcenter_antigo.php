<?php if ($login_fabrica == 52 and $status_interacao == 'Resolvido'): ?>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				
				<tr>
					<td>
						<?php  
						$sqlx = "SELECT tbl_tipo_pergunta.tipo_pergunta 
								from tbl_resposta 
								JOIN tbl_pergunta using(pergunta) 
								JOIN tbl_tipo_pergunta using(tipo_pergunta) 
								where tbl_tipo_pergunta.fabrica= $login_fabrica
								and tbl_resposta.hd_chamado=$callcenter
								group by tbl_tipo_pergunta.tipo_pergunta;";
						$resx = pg_query($con,$sqlx);
						$tipo_pergunta_cadastrada = (pg_num_rows($resx)==1) ? pg_fetch_result($resx, 0, 0) : '' ;
						$disabled_pergunta_cadastrada = (!empty($tipo_pergunta_cadastrada)) ? " disabled='disabled' " : '' ;
						
						$sql = "SELECT  tipo_pergunta,
										tbl_tipo_pergunta.descricao
								FROM tbl_tipo_pergunta
								JOIN tbl_tipo_relacao USING(tipo_relacao)
								WHERE tbl_tipo_pergunta.fabrica= $login_fabrica
								AND sigla_relacao = 'C'
								AND tbl_tipo_pergunta.ativo = true
								";
						$res = pg_query($con,$sql);
						
						if(pg_num_rows($res)>0){
							for ($x=0; $x < pg_num_rows($res); $x++) { 
								$tipo_pergunta 	= pg_fetch_result($res, $x, 'tipo_pergunta');
								$descricao 		= pg_fetch_result($res, $x, 'descricao');
								echo $descricao." ";

								$checked_radio_tipo_pergunta = ($tipo_pergunta == $tipo_pergunta_cadastrada) ? "CHECKED" : '' ;

								?>
								
								<input type="radio" name="tipo_pergunta" <?=$checked_radio_tipo_pergunta.$disabled_pergunta_cadastrada?> id="tipo_pergunta_<?=$tipo_pergunta?>" value='<?=$tipo_pergunta?>' rel='<?=$descricao?>'>
								<br>
								<?
							}
						}
						?>
					</td>
				</tr>
			</table>

			<div class='errorPergunta' style='background-color:#F92F2F;color:#FFF;font:bold 14px Arial'></div>
			
			<div class='divTranspBlockAuditoria' style='margin-top:57px;margin-left:378px;display:none;background-color:#000;position:absolute; z-index:1;width:900px;height:295px;opacity:0.65;-moz-opacity: 0.65;filter: alpha(opacity=65);'>
			</div>
			<div id="AuditoriaCampo" style='display:none'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
					<tr>
						<td>BOM DIA SENHOR(A)!</td>
					</tr>

					<tr>
						<td nowrap>
							MEU NOME É ___________, SOU DO SETOR DE QUALIDADE NO ATENDIMENTO AOS CLIENTES DA <b>FRICON</b>, 
							GOSTARIA DE FAZER UMA PESQUISA DE SATISFAÇÃO COM O SENHOR.
						</td>
					</tr>

					<tr>
						<td>
							SERÃO APENAS ALGUNS MINUTOS, ONDE NÃO TOMAREMOS MUITO TEMPO DO SENHOR.
						</td>
					</tr>
				
					<tr>
						<td>
							<table class='tabela table_perguntas_fricon_auditoria' width="900px" style='margin:auto'>
								<tr class='titulo_tabela'>
									<td width="33px" style='background-color:#F4E6D7;border:0px solid'>&nbsp;</td>
									<td width="404px" style='background-color:#F4E6D7;border:0px solid'>&nbsp;</td>
									<td width="102px">SIM</td>
									<td width="102px">NAO</td>
									<td width="102px">OUTROS</td>
								</tr>
								<!-- QUANDO AS PERGUNTAS DESTE TIPO ESTIVEREM DE 1->5 e a  pergunta 7 - IRÁ EXIBIR RADIO BUTONS SIM E NAO-->
								<!-- QUANDO A PERGUNTA DESTE TIPO FOR A 6 ($i == 5) - IRÁ EXIBIR RADIO BUTONS MUITO SATISFEITO | SATISFEITO | INSATISFEITO -->
								<?php  
								$sql = "SELECT tbl_pergunta.pergunta,tbl_pergunta.descricao,tbl_tipo_pergunta.tipo_pergunta 
										from tbl_pergunta join tbl_tipo_pergunta using (tipo_pergunta) 
										where tbl_tipo_pergunta.descricao = 'Auditoria em Campo' 
										AND tbl_pergunta.ativo 
										AND tbl_pergunta.fabrica = $login_fabrica 
										ORDER by tbl_pergunta.pergunta";

								$res = pg_query($con,$sql);

								if (pg_num_rows($res)>0){
									for ($i=0; $i < pg_num_rows($res) ; $i++) { 
										$pergunta 		= pg_fetch_result($res, $i, 'pergunta');
										$desc_pergunta  = pg_fetch_result($res, $i, 'descricao');
										$tipo_pergunta 	= pg_fetch_result($res, $i, 'tipo_pergunta');

										if (in_array($i, array(8,9,10,11))){
											$num = ($num + 0.1);
										}else{
											if ($i >= 12){
												if ($num <> 9){
													$num = 9;
												}else{
													$num++;
												}
											}else{
												$num = ($i+1);
											}
										}

										if ($tipo_pergunta_cadastrada){
											
											$sqlx = "SELECT txt_resposta FROM tbl_resposta where hd_chamado = $callcenter and pergunta = $pergunta";
											$resx = pg_query($con,$sqlx);

											$txt_resposta = (pg_num_rows($resx)>0) ? pg_fetch_result($resx, 0, 0) : '';


										}


										?>
										<tr>
											<td><?php echo $num ?></td>
											<td align="left">
												<input type="hidden" name="perg_<?=$i?>" value="<?=$pergunta?>">

												<?php echo $desc_pergunta ?>
											</td>

											<?php if ($i <> 5 && $i < 7): 
												$checked_nao = ($txt_resposta == 'NÃO') ? " CHECKED" : '';
												$checked_sim = ($txt_resposta == 'SIM') ? " CHECKED" : '';
											?>
												<td>
													SIM <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_sim?> id="perg_opt<?=$num?>_sim" value="SIM">
												</td>
												<td>
													NÃO <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_nao?> id="perg_opt<?=$num?>_nao" value="NÃO">
												</td>
												<td>&nbsp;</td>
											<?php endif ?>

											<?php if ($i == 5): 
												$checked_muitoSatisfeito = ($txt_resposta == 'Muito Satisfeito') ? " CHECKED" : '';
												$checked_Satisfeito = ($txt_resposta == 'Satisfeito') ? " CHECKED" : '';
												$checked_Insatisfeito = ($txt_resposta == 'Insatisfeito') ? " CHECKED" : '';
											?>
												<td>
													Muito Satisfeito <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_muitoSatisfeito?> id="perg_opt<?=$num?>_sim" value="Muito Satisfeito">
												</td>
												<td>
													Satisfeito <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_Satisfeito?> id="perg_opt<?=$num?>_nao" value="Satisfeito">
												</td>
												<td>
													Insatisfeito <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_Insatisfeito?> id="perg_opt<?=$num?>_nao" value="Insatisfeito">
												</td>
											<?php endif ?>

											<?php if ($i == 7): ?>
												<td colspan='3'>&nbsp;</td>
											<?php endif ?>

											<?php if ($i > 7 && $i < 12): ?>
												<td colspan='3'>
													<input type="text" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada?> id="perg_opt_<?=$num?>" class='input' style="width:90%; font:12px Arial" value="<?=$txt_resposta?>">
												</td>
											<?php endif ?>

											<?php if ($i == 12): ?>
												<td colspan='3' align="left">
													<input type="text" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada?> id="perg_opt_<?=$num?>" class="input numeric_field" size="2" maxlength="2" value="<?=$txt_resposta?>" style="margin-left:18px; font:12px Arial">
												</td>
											<?php endif ?>

										</tr>
										<?

									}
								}

								?>

								<input type="hidden" name="qtde_perg" value="<?=$i?>">
								<input type="hidden" name="tipo_pergunta" value="<?=$tipo_pergunta?>">
								
							</table>
							
						</td>
					
					</tr>
					<?php if (empty($tipo_pergunta_cadastrada)): ?>
						
						<tr>
							<td colspan="5" >
								<input type="button" value="Gravar" class='btn_grava_pergunta_fricon' rel='auditoria'>
								<div class="td_btn_gravar_pergunta"></div>
							</td>
						</tr>

					<?php endif ?>

					<tr class='agradecimentosAuditoria' style='display:none'>
						<td colspan="5">
							Gravado com Sucesso
							<br><br>
							BOM......EM NOME DA <b>FRICON</b>, GOSTARIAMOS DE AGRADECER SUA ATENÇÃO, E DESEJAMOS-LHE UM EXCELENTE DIA.
						</td>
					</tr>
				</table>
			</div>


			<div class='divTranspBlockPesquisa' style='margin-top:57px;margin-left:378px;display:none;background-color:#000;position:absolute; z-index:1;width:900px;height:217px;opacity:0.65;-moz-opacity: 0.65;filter: alpha(opacity=65);'>
			</div>
			<div id="PesquisaSatisfacao" style='display:none'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
					<tr>
						<td>BOM DIA SENHOR(A)!</td>
					</tr>
					<tr>
						<td nowrap>
							MEU NOME É ___________, SOU DO SETOR DE QUALIDADE NO ATENDIMENTO AOS CLIENTES DA <b>FRICON</b>, 
							GOSTARIA DE FAZER UMA PESQUISA DE SATISFAÇÃO COM O SENHOR.
						</td>
					</tr>
					<tr>
						<td>
							SERÃO APENAS ALGUNS MINUTOS, ONDE NÃO TOMAREMOS MUITO TEMPO DO SENHOR.
						</td>
					</tr>
				
					<tr>
						<td>
							<table class='tabela table_perguntas_fricon_pesquisa' width="900px" style='margin:auto' >
								<tr class='titulo_tabela'>
									<td width="33px" style='background-color:#F4E6D7;border:0px solid'>&nbsp;</td>
									<td width="404px" style='background-color:#F4E6D7;border:0px solid'>&nbsp;</td>
									<td width="102px">SIM</td>
									<td width="102px">NAO</td>
									<td width="102px">OUTROS</td>
								</tr>
								<!-- QUANDO AS PERGUNTAS DESTE TIPO ESTIVEREM DE 1->5 e a  pergunta 7 - IRÁ EXIBIR RADIO BUTONS SIM E NAO-->
								<!-- QUANDO A PERGUNTA DESTE TIPO FOR A 6 ($i == 5) - IRÁ EXIBIR RADIO BUTONS MUITO SATISFEITO | SATISFEITO | INSATISFEITO -->
								<?php  
								$sql = "SELECT tbl_pergunta.pergunta,tbl_pergunta.descricao,tbl_tipo_pergunta.tipo_pergunta 
										from tbl_pergunta join tbl_tipo_pergunta using (tipo_pergunta) 
										where tbl_tipo_pergunta.descricao = 'Pesquisa de Satisfação' 
										AND tbl_pergunta.ativo 
										AND tbl_pergunta.fabrica = $login_fabrica 
										ORDER by tbl_pergunta.pergunta";

								$res = pg_query($con,$sql);
								if (pg_num_rows($res)>0){
									for ($i=0; $i < pg_num_rows($res) ; $i++) { 
										$pergunta 		= pg_fetch_result($res, $i, 'pergunta');
										$desc_pergunta  = pg_fetch_result($res, $i, 'descricao');
										$tipo_pergunta 	= pg_fetch_result($res, $i, 'tipo_pergunta');

										$num = ($i+1);
										
										if ($tipo_pergunta_cadastrada){
											
											$sqlx = "SELECT txt_resposta FROM tbl_resposta where hd_chamado = $callcenter and pergunta = $pergunta";
											$resx = pg_query($con,$sqlx);

											$txt_resposta = (pg_num_rows($resx)>0) ? pg_fetch_result($resx, 0, 0) : '';


										}

										?>
										<tr>
											<td><?php echo $num ?></td>
											
											<td align="left">
												<input type="hidden" name="perg_<?=$i?>" value="<?=$pergunta?>">

												<?php echo $desc_pergunta ?>
											</td>

											<?php if ($i <> 5 && $i < 7): 
												$checked_nao = ($txt_resposta == 'NÃO') ? " CHECKED" : '';
												$checked_sim = ($txt_resposta == 'SIM') ? " CHECKED" : '';
											?>
												<td>
													SIM <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_sim?> id="perg_opt<?=$num?>_sim" value="SIM">
												</td>
												<td>
													NÃO <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_nao?> id="perg_opt<?=$num?>_nao" value="NÃO">
												</td>
												<td>&nbsp;</td>
											<?php endif ?>

											<?php if ($i == 5): 
												$checked_muitoSatisfeito = ($txt_resposta == 'Muito Satisfeito') ? " CHECKED" : '';
												$checked_Satisfeito = ($txt_resposta == 'Satisfeito') ? " CHECKED" : '';
												$checked_Insatisfeito = ($txt_resposta == 'Insatisfeito') ? " CHECKED" : '';
												
											?>
												<td>
													Muito Satisfeito <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_muitoSatisfeito?> id="perg_opt<?=$num?>_sim" value="Muito Satisfeito">
												</td>
												<td>
													Satisfeito <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_Satisfeito?> id="perg_opt<?=$num?>_nao" value="Satisfeito">
												</td>
												<td>
													Insatisfeito <input type="radio" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada.$checked_Insatisfeito?> id="perg_opt<?=$num?>_nao" value="Insatisfeito">
												</td>
											<?php endif ?>

											<?php if ($i == 7): ?>
												<td colspan='3' align="left">
													<input type="text" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada?> id="perg_opt_<?=$num?>" class='input numeric_field' size="2" maxlength="2"  value="<?=$txt_resposta?>" style="margin-left:18px; font:12px Arial">
												</td>
											<?php endif ?>

											<?php if ($i > 7): ?>
												<td colspan='3'>
													<input type="text" name="perg_opt<?=$i?>" <?=$disabled_pergunta_cadastrada?> id="perg_opt_<?=$num?>" class='input' style="width:90%; font:12px Arial" value="<?=$txt_resposta?>">
												</td>
											<?php endif ?>

										</tr>
										<?

									}
								}

								?>

								<input type="hidden" name="qtde_perg" value="<?=$i?>">
								<input type="hidden" name="tipo_pergunta" value="<?=$tipo_pergunta?>">
							</table>
							
						</td>
					
					</tr>
					<?php if (empty($tipo_pergunta_cadastrada)): ?>

						<tr>
							<td colspan="5">
								<input type="button" value="Gravar" class='btn_grava_pergunta_fricon' rel='pesquisa'>
								<div class="td_btn_gravar_pergunta"></div>
							</td>
						</tr>

					<?php endif ?>
					<tr class='agradecimentosPesquisa' style='display:none'>
						<td colspan="5">
							Gravado com Sucesso
							<br><br>
							BOM......EM NOME DA <b>FRICON</b>, GOSTARIAMOS DE AGRADECER SUA ATENÇÃO, E DESEJAMOS-LHE UM EXCELENTE DIA.
						</td>
					</tr>
				</table>
			</div>
		<?php endif ?>
