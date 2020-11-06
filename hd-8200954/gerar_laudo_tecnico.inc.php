<?php

$os 	= $_GET['os'];
$laudo 	= $_GET['laudo'];
$print 	= $_GET['print'];
$via	= $_GET['via'];



if(strlen($_COOKIE['cook_login_unico']) > 0){ //HD-3165481
	$sqlOS = "SELECT tbl_os.fabrica,
						tbl_os.sua_os,
						tbl_os.posto
				FROM tbl_os
				WHERE os = $os";
	$resOS = pg_query($con, $sqlOS);
	if(pg_num_rows($resOS) > 0){
		$posto_os = pg_fetch_result($resOS, 0, 'posto');
		$fabrica_os = pg_fetch_result($resOS, 0, 'fabrica');
		$sua_os_fabrica = pg_fetch_result($resOS, 0, 'sua_os');

		$sqlPosto = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $posto_os AND fabrica = $fabrica_os";
		$resPosto = pg_query($con, $sqlPosto);

		if(pg_num_rows($resPosto) > 0){
			$codigo_posto = pg_fetch_result($resPosto, 0, 'codigo_posto');
		}

		if($fabrica_os <> $login_fabrica){
			if($fabrica_os == 1){
				$xsua_os =  $codigo_posto.$sua_os_fabrica;
			}else{
				$xsua_os = $os;
			}

			echo '<table align="center" width="700">
					<tr style="background-color:#FF0000; font-size:16px; color:#FFFFFF; text-align:center;" >
						<td>OS: '.$xsua_os.' não pertence a essa Fábrica</td>
					</tr>
			</table>';
			exit;
		}
	}
}
if($print){
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

	echo '<script type="text/javascript">
			print();
		  </script>';
}

if(!empty($laudo)){
	$sql = "SELECT titulo,observacao,ordem FROM tbl_laudo_tecnico_os WHERE laudo_tecnico_os = $laudo";
	$res = pg_query($con,$sql);
	$titulo = pg_fetch_result($res, 0, 'titulo');
	$obs 	= pg_fetch_result($res, 0, 'observacao');
	$ordem 	= pg_fetch_result($res, 0, 'ordem');

	switch($titulo){
		case 'Falta de peça na AT' : $motivo_troca = 'autorizada'; break;
		case 'Falta de peça na fábrica' : $motivo_troca = 'fabrica'; break;
		case 'Peça não consta na lista básica' : $motivo_troca = 'produto_com_listabasica'; break;
		case 'Produto sem lista básica' : $motivo_troca = 'sem_lista_basica'; break;
		case 'Outros' : $motivo_troca = 'outros'; break;
	}
}

$sql = "SELECT to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				tbl_os.sua_os,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_numero,
				tbl_os.consumidor_bairro,
				tbl_os.consumidor_fone,
				tbl_os.consumidor_estado,
				tbl_os.consumidor_cep,
				tbl_os.consumidor_cidade,
				tbl_os.revenda,
				tbl_os.revenda_nome,
				tbl_os.nota_fiscal,
				to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_compra,
				tbl_os.codigo_fabricacao,
				tbl_os.serie,
				tbl_produto.produto,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_produto.voltagem,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
			WHERE tbl_os.os = $os
			AND tbl_os.posto = $login_posto";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0 and is_resource($res) ) {
	extract(pg_fetch_assoc($res));
}
$display = ($laudo) ? "block" : "none";

if($login_fabrica == 1){
	$sql = "SELECT * from tbl_lista_basica where produto = $produto and fabrica = $login_fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)==0){
	    $msg_lista_basica .= "Produto sem lista básica. ";
	    $disabled = true;
	}else{
		$disabled = false;
	}
}

?>
<style type="text/css">
	@media print {  /*  Estilo para impressão   */
		input { display:none;}
	}

	#laudo{margin:auto;}

	#laudo table{
		border:solid 1px;
		margin-top:3px;
		font-size:12px;
		text-align: left;
	}

	#laudo table tr td{
		padding-top:5px;
	}

	#laudo table tr td p{
		font-size:11px;
	}

	#texto_obs, #linha_peca {
		display:<?=$display?>;
	}
	#fora_listabasica{
		display: none;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}



	#logos tr td{
		font-size:15px;
		font-weight: bold;
		font-family: Arial;
		border: solid 2px;
	}
</style>

<?php if(!empty($msg_erro)){ ?>
	<table align='center' width='700'>
		<tr class='msg_erro'>
			<td colspan='3'><?=$msg_erro?></td>
		</tr>
	</table>
<?php } ?>

<form name='frm_cadastro' method='post'>
	<div id='laudo' style='width:700px;'>
		<table align='center' width='100%' id='logos' cellpadding="0" cellspacing="0">
			<tr>
				<td align='center'><img src="admin/imagens_admin/dewalt_logo.gif" width='140'></td>
				<td align='center'>
					<u>LAUDO TÉCNICO DE PRODUTOS PROVENIENTES <br> DE 90 DIAS DE SATISFAÇÃO</u>
				</td>
			</tr>
		</table>

		<table align='center' width='100%'>
			<tr>
				<td colspan='2'><b>Data de Entrada:</b> <?=$data_abertura?></td>
				<td><b>N° da OS:</b> <?=$codigo_posto.$sua_os?></td>
			</tr>

			<tr>
				<td colspan='2'><b>Cliente:</b> <?=$consumidor_nome?></td>
				<td><b>Tel.:</b> <?=$consumidor_fone?></td>
			</tr>

			<tr>
				<td colspan='2'><b>Endereço:</b> <?=$consumidor_endereco.",".$consumidor_numero?></td>
				<td><b>Bairro:</b> <?=$consumidor_bairro?></td>
			</tr>

			<tr>
				<td><b>Cep:</b> <?=$consumidor_cep?></td>
				<td><b>Cidade:</b> <?=$consumidor_cidade?></td>
				<td><b>Estado:</b> <?=$consumidor_estado?></td>
			</tr>

			<tr>
				<td><b>Modelo:</b> <?=$referencia." - ".$descricao?></td>
				<td><b>Voltagem:</b> <?=$voltagem?></td>
				<td><b>Cód. de fabricação:</b> <?=$codigo_fabricacao?></td>
			</tr>

			<tr>
				<td><b>Número de Série:</b><?=$serie?></td>
				<td><b>Nota Fiscal N°:</b> <?=$nota_fiscal?></td>
				<td><b>Data da Compra:</b> <?=$data_compra?></td>
			</tr>

			<tr>
				<td colspan='3'><b>Revendedor:</b> <?=$revenda_nome?></td>
			</tr>

			<tr>
				<td colspan='2'><b>Assistência Técnica Autorizada:</b> <?=$nome?></td>
				<td><b>Código:</b> <?=$codigo_posto?></td>
			</tr>
		</table>

		<table align='center' width='100%'>
			<?php if($login_fabrica == 1){?>
				<tr>
					<td colspan="4" align="center" style="color:red; font-weight:bold; "><?= $msg_lista_basica ?></td>
				</tr>
			<?php } ?>
			<?php if(empty($laudo)){ ?>
					<tr>
						<td colspan='2'><b>Especifique o motivo da troca</b></td>
					</tr>
					<tr>
						<td colspan='2'>
							<input type='radio' name='motivo_troca' <?php echo ($disabled == true)? " disabled ": " " ; ?> value='autorizada' onclick='javascript: informaPeca();' <?php echo ($motivo_troca == 'autorizada') ? 'checked' : '';?> > Falta de peça na AT
						</td>
					
					<?php if($login_fabrica == 1){?>
						<td colspan='2'>
							<input type='radio' name='motivo_troca' id="sem_lista_basica" value='sem_lista_basica'  <?php echo ($motivo_troca == 'produto_sem_listabasica') ? 'checked' : '';?> > Produto sem lista básica 
						</td>						
					<?php } ?>
					</tr>
					<tr>
						<td colspan='2'>
							<input type='radio' name='motivo_troca' value='fabrica' <?php echo ($disabled == true)? " disabled ": " " ; ?> onclick='javascript: informaPeca();' <?php echo ($motivo_troca == 'fabrica') ? 'checked' : '';?> > Falta de peça na fábrica
						</td>
					<?php if($login_fabrica == 1){?>
						<td colspan='2'>
								<input type='radio' name='motivo_troca' <?php echo ($disabled == true)? " disabled ": " " ; ?> value='produto_com_listabasica' onclick="verifica_lista_basica($('input[name=produto]').val())" <?php echo ($motivo_troca == 'peca_fora_listabasica') ? 'checked' : '';?> >  Peça não consta na lista básica
						</td>
					<?php } ?>
					</tr>
					<?php
					 if($login_fabrica != 1 ){ ?>
					<tr>
						<td colspan='2'>
							<input type='radio' name='motivo_troca' value='outros' onclick='javascript: informaPeca();' <?php echo ($motivo_troca == 'outros') ? 'checked' : '';?> > Outros
						</td>
					</tr>
					<?php } ?>
					<tr id='texto_obs'>
						<td align='center' colspan='2'>
							<?php if(empty($laudo)){ ?>
								<textarea name='obs' rows='5' cols='70'></textarea>
							<?php } else if(!empty($laudo) and $motivo_troca == 'outros') {
										echo $obs;
								}?>
						</td>
					</tr>
			<?php }else{ ?>
					<tr>
						<td colspan='2'><b>Motivo da troca</b></td>
					</tr>
					<tr>
						<td colspan='2'><?=$titulo?></td>
					</tr>
					<?php if(strlen($obs) > 0 ){?>
					<tr>
						<td colspan=2><b>Peças:</b><br>
						<?php $obs_pecas = explode(";", $obs);
							foreach ($obs_pecas as $linha) {
								if(strlen(trim($linha))>0){
									echo "$linha <br>";
								}
							}
						?>
						</td>
					</tr>
					<?php }?>
			<?php } ?>
			<tr id='linha_peca'>
				<td colspan="4">
					<?php if(empty($laudo)){ ?>
					<table width='100%' style='border:0'>
						<tr>
							<td align='left' width='150'>
								Ref. Peça <br />
								<input type="text" id="peca_referencia" name="peca_referencia" size="15" class='frm' maxlength="20" value="<? echo $peca_referencia ?>" >
								<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca_2 ($('input[name=peca_referencia]').val(), '', $('input[name=produto]').val())" >
							</td>
							<td  align='left' width='320'>
								Descrição Peça <br />
								<input type="text" id="peca_descricao" name="peca_descricao" size="40" class='frm' value="<? echo $peca_descricao ?>" >
								<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_2 ('', $('input[name=peca_descricao]').val(),$('input[name=produto]').val())" >
							</td>
							<td>
								<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();' style='margin-top:12px;'>
							</td>
						</tr>

						<tr>
							<td colspan="3" align="left">
								<span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>
							</td>
						</tr>

						<tr>
							<td colspan="3">
								<select multiple="multiple" SIZE='6' id='peca_faltante' name="peca_faltante[]" style='width:470px;'>
								<?
									if(count($peca_faltante) > 0) {
										for($i =0;$i<count($peca_faltante);$i++) {

											$sql = " SELECT tbl_peca.referencia,
															tbl_peca.descricao
													FROM tbl_peca
													WHERE fabrica = $login_fabrica
													AND   referencia  = '".$peca_faltante[$i]."'";
											$res = pg_query($con,$sql);
											if(pg_num_rows($res) > 0){
												echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
											}
										}
									}
								?>
								</select>
								<input type="button" value="Remover" onClick="delItPeca();" class='frm'> <br>
								<span id='num_pedido'>Pedido <input type='text' name='pedido' size='15' value='<?=$pedido?>'></span>
							</td>
						</tr>
					</table>
					<?php } else if(!empty($laudo) and ($motivo_troca == 'autorizada' or $motivo_troca == 'fabrica')) {
								$array_pecas = explode('<br>', $obs);

								echo "<table width='100%' style='border:0;font-weight:bold;font-size:12px;'>";

								if($motivo_troca != 'fabrica'){
									echo "<tr><td colspan='2'>Peças que faltaram</td></tr>";
								}else{
									echo "<tr><td colspan='2'>{$array_pecas[0]}</td></tr>";
								}
								echo "<tr><td>Referência</td><td style='padding-left:20px;'>Descrição</td></tr>";
								for($j = 1; $j < count($array_pecas); $j++){
									list($ref,$desc) = explode('|',$array_pecas[$j]);
									echo "<tr>
											<td>{$ref}</td>
											<td style='padding-left:20px;'>{$desc}</td>
										  </tr>";
								}
								/*foreach ($array_pecas as $peca_f) {
									list($ref,$desc) = explode('|',$peca_f);
									echo "<tr>
											<td>{$ref}</td>
											<td>{$desc}</td>
										  </tr>";
								}*/
								echo "</table>";

						}else if(!empty($laudo) and ($motivo_troca == 'outros')){
								echo $obs;
						}?>
				</td>
			</tr>
		
			<tr id=fora_listabasica>
				<td colspan="4">
					<table style="border:0px">
						<tr>
							<td align="right">Descrição Pecas:</td>
							<td>
								<input type="text" name="descricao_peca" id="descricao_peca" value="">
								<input type="button" name="add_peca" id="add_peca" onClick="add_peca2()" value="Adicionar">
							</td>
						</tr>
						<tr>
							<td colspan="3">
								<select multiple="multiple" SIZE='6' id='peca_listabasica' name="peca_listabasica[]" class="select" style='width:470px;'>
							
								</select>
								<input type="button" value="Remover" onClick="delPeca2()" class='frm'> <br>
							</td>
						</tr>
					</table>
				</td>
			</tr>

		</table>



		<table align='center' width='100%'>
			<tr>
				<td colspan='2'>
					<p><u><b>Obs.*</b></u></p>
					<p>
						Caso seja constatado na análise que o defeito do produto foi provocado por um dos motivos informados abaixo, o conserto será efetuado
					</p>
					<p>
						<b>FORA DA GARANTIA</b>. Portanto, nesses casos haverá perda da Garantia de Satisfação 90 dias.
					</p>
				</td>
			</tr>

			<tr>
				<td> 1) - Utilização Inadequada (mau uso);</td>
				<td> 4) - Produto ligado em voltagem errada;</td>
			</tr>

			<tr>
				<td> 2) - Acessório Inadequado;</td>
				<td> 5) - Quebras ocasionadas por queda.</td>
			</tr>

			<tr>
				<td colspan='2'> 3) - Máquina aberta por posto não autorizado;</td>
			</tr>
		</table>



		<table align='center' width='100%'>
			<tr>
				<td colspan='2'>
					<u><b>Mecânico</b></u>
				</td>
			</tr>

			<tr>
				<td colspan='2'>
					Análise feita por: <hr style='width:300px;margin-left:110px; border:solid 1px; color:#000;margin-top:-5px;'>
				</td>
			</tr>

			<tr>
				<td width='500'>
					<br />
					Assinatura: <hr style='width:330px;margin-left:80px; border:solid 1px; color:#000;margin-top:-5px;'>
				</td>
				<td>
					Carimbo:
				</td>
			</tr>

			<tr>
				<td colspan='2'>
					<u><b>Cliente</b></u>
				</td>
			</tr>

			<tr>
				<td colspan='2'>
					Declaro estar de acordo com o atendimento realizado pelo posto autorizado.
				</td>
			</tr>

			<tr>
				<td>
					<br />
					Assinatura: <hr style='width:330px;margin-left:80px; border:solid 1px; color:#000;margin-top:-5px;'>
				</td>
				<td align='right'>
					Data: <?=date('d/m/Y')?></td>
				</td>
			</tr>
		</table>

		<table width='120' align='right' style='margin-top:0'>
			<tr>
				<td><b>L.T. &nbsp;<?=$ordem?></b></td>
			</tr>
		</table>

		<?php
			if($print){
				$texto_rodape = ($via == 1) ? "1° VIA CLIENTE" : "2°VIA ASSISTÊNCIA TÉCNICA";
			}
		?>

		<table width='580' align='right' style='margin-top:0;border:0'>
			<tr>
				<td align='center' style='color:#CCC'><?=$texto_rodape?></td>
			</tr>
		</table>
		<br /> <br /> <br /> <br />
		<center>
		<?php if(empty($laudo)){ ?>
				<input type='hidden' name='produto' value='<?=$produto?>'>
				<input type='hidden' name='nota_fiscal' value='<?=$nota_fiscal?>'>
				<input type='hidden' name='revenda' value='<?=$revenda?>'>

				<input type='button' id='gravar' value='Gravar'>
		<?php }else{ ?>
				<input type='hidden' name='laudo_tec' id='laudo_tec' value='<?=$laudo?>'>
				<input type='hidden' name='os' id='os' value='<?=$os?>'>
				<input type='button' value='Imprimir 1ª Via' name="imprimir1" onclick="javascript: imprimir(1);">
				<input type='button' value='Imprimir 2ª Via' name="imprimir2" onclick="javascript: imprimir(2);">
		<?php } ?>
		</center>
	</div>


</form>
