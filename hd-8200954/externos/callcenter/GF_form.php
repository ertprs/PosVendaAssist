<script>
function mandar(){

pais = document.formulario.id_tipo[document.formulario.id_tipo.selectedIndex].value
	parent.location='http://www.georgeforeman.com.br/novo/receitas.asp?id_tipo='+pais
}

</script>

<link href='http://fonts.googleapis.com/css?family=Oswald:300:400' rel='stylesheet' type='text/css'>

<style>

	#alinhasite{
		width: 970px;
		margin: 0 auto;
		font: 16px 'Oswald', sans-serif;
		color: #595959;
	}

	button{
		font: 16px 'Oswald', sans-serif;
	}

	input{
		padding: 5px;
		background-color: #f5f5f5;
		border: 1px solid #595959;
	}

	select{
		padding: 5px;
		background-color: #f5f5f5;
		border: 1px solid #595959;
	}

	textarea{
		padding: 5px;
		background-color: #f5f5f5;
		border: 1px solid #595959;
	}

	.txt_branco{
		font-weight: bold;
		color: #FFFFFF;
		background-color: #b7212b;
		border-radius: 7px;
		padding-left: 10px;
		width: 170px !important;
	}

	.txt_vermelho{
		color: #b7212b;
	}

</style>

</head>

<?php

	$data_nascimento2  = str_replace("'","",$data_nascimento2);
	$sexo             = str_replace("'","",$sexo);
	$endereco         = str_replace("'","",$endereco);
	$numero			  = str_replace("'","",$numero);
	$complemento      = str_replace("'","",$complemento);
	$bairro           = str_replace("'","",$bairro);
	$estado           = str_replace("'","",$estado);
	$cidade2          = str_replace("'","",$cidade2);
	$cep              = str_replace("'","",$cep);

	//Campos obrigatórios
	$nome             = str_replace("'","",$nome);
	$email            = str_replace("'","",$email);
	$cidade           = str_replace("'","",$cidade);
	$ddd              = str_replace("'","",$ddd);
	$telefone         = str_replace("'","",$telefone);
	$telefone2        = str_replace("'","",$telefone2);
	$mensagem         = str_replace("'","",$mensagem);
	$assunto          = str_replace("'","",$assunto);
 ?>
<body>

<div id="alinhasite">
	<div id="conteudo_internas">

		<p>
			Fazemos o nosso melhor para desenvolver e produzir os grills que mais atendam as necessidades de nossos consumidores. Esse trabalho é contínuo e sempre seguimos em direção a inovação em nossos produtos.
			Contamos com sua ajuda e sugestões para melhorar os nossos serviços. Sua opinião é muito importante para nós!
			Entre em contato através do formulário abaixo.
		</p>

		<p style="text-align:right;" class="txt_vermelho">* Campos obrigatórios</p>

		<form action="" name="contato" method="post" onsubmit="return frmValidaFormContato();">
			<input type='hidden' name='marcaID' value='<?=$marcaID?>' />
			<table width="650" border="0" align="center" cellpadding="1" cellspacing="8">
				<?php if(count($msg_erro) > 0){?>
				<tr>
					<td colspan='2' style='background-color:#FF0000; font: bold 16px "Arial"; color:#FFFFFF; text-align:center;'>
						<?php echo implode('<br />', $msg_erro);?>
					</td>
				</tr>
				<?php }?>
				<tr>
					<td width="177" align="left"  class="txt_branco">
						&nbsp;Assunto: <span> * </span>
					</td>
					<td width="295" class="style1">
						<select style="width: 199px;"name='assunto' id='assunto' title='Assunto'>
							<option value='' <?php if($assunto == '') echo " selected "?>>- selecione</option>
							<option value='informacao' <?php if($assunto == 'informacao') echo " selected "?>>Informação</option>
							<option value='sugestao' <?php if($assunto == 'sugestao') echo " selected "?>>Sugestão</option>
							<option value='reclamacao_produto' <?php if($assunto == 'reclamacao_produto') echo " selected "?>>Reclamação</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width="480" align="left"  class="txt_branco">
						&nbsp;Nome Completo: *
					</td>
					<td width="295" class="style1">
						<input name="nome_completo"  type="text" class="form_text"
								 id="nome_completo" title="Nome Completo" size="30" placeholder='Digite seu nome' value='<?=$nome?>'>
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Sexo: <span> * </span></td>
					<td class="texto">
						<input name="sexo" type="radio" value="M" id='M' <?php if ($sexo == 'M') echo "CHECKED";?>>
						<label for='M'>Masculino</label>
						<input name="sexo" type="radio" value="F" id='F' <?php if ($sexo == 'F') echo "CHECKED";?>>
						<label for='F'>Feminino</label>
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;E-mail: <span> * </span></td>
					<td class="style1">
						<input name="email" type="email" class="form_text" id="email" placeholder='Seu endereço de e-mail'
							  title="Email" size="30" value='<?=$email?>'></td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Data Nascimento: <span> *</span></td>
					<td class="style1">
						<input name="data_nascimento" type="text" class="form_text data_nascimento" id="data_nascimento" size="12" value='<?=$data_nascimento2?>' title="Data de Nascimento">
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Tel Fixo: <span> * </span></td>
					<td class="style1">
						<input name="telefone" type="text" class="form_text telefone" id="telefone" title="Tel Fixo" size="12" value='<?=$telefone?>' />
					</td>
				</tr>

				<tr>
					<td align="left"  class="txt_branco">&nbsp;Celular: <span> * </span></td>
					<td class="style1">
						<input name="celular_sp" type="text" class="form_text telefone" id="celular_sp" title="Celular" size="12" value='<?=$celular_sp?>' maxlength="15" />
					</td>
				</tr>

				<tr>
					<td align="left"  class="txt_branco">&nbsp;CEP: <span> * </span></td>
					<td class="style1">
						<input name="cep" type="text" class="form_text cep" id="cep" size="12" maxlength='9' value='<?=$cep?>' title="CEP"><img id='loading-gif' style="height: 17px; display: none;" src="../imagens/loading.gif">
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Endere&ccedil;o Completo: <span> * </span></td>
					<td class="style1">
						<input type="text" name="endereco" placeholder="Se sabe seu CEP, digite-o acima para agilizar" class="form_text" id="endereco" size="30" value="<?php echo $endereco ?>" >
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Número: <span> * </span></td>
					<td class="style1">
						<input type="text" name="numero" class="form_text" id="numero" size="12" maxlength="20" value="<?php echo $numero ?>" title="Número" >
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Complemento:</td>
					<td class="style1">
						<input type="text" name="complemento" class="form_text" id="complemento" size="30" maxlength="30" value="<?php echo $complemento ?>" title="Complemento">
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Bairro: <span> * </span></td>
					<td class="style1"><input name="bairro" type="text" class="form_text" id="bairro" size="30" value="<?php echo $bairro?>" title="Bairro"></td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Estado: <span> * </span></td>
					<td class="style1">
						<!-- <input name="estado" type="text" class="form_text" id="estado" size="2" maxlength="2" value='<?=$estado?>'>//-->
						<select style="width: 199px;" name='estado' id='estado'>
							<option></option>
							<?php
							foreach ($array_estado as $k => $v) {
								echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Cidade: <span> * </span></td>
					<td class="style1">
						<!--<input name="cidade" type="text" class="form_text" id="cidade" size="38" value="<?php echo $cidade2?>" title="Cidade">-->
						<select style="width: 199px;" name='cidade' id='cidade'>
						</select>
					</td>
				</tr>
				<tr>
					<td align="left"  class="txt_branco">&nbsp;Produto: <span> * </span></td>
					<td class="style1">
						<input type='hidden' name='produto' id='produto' value="<?php echo $produto?>">
						<!--<input name="produto_descricao" type="text" class="form_text" id="produto_descricao" size="38" value='<?=$produto_descricao?>' title="Produto">-->

						<!-- HD 941072 - Busca autocomplete pelo Nome do Produto populando o combo de Defeitos Reclamados -->
						<input name="produto_descricao" class="form_text" id="produto_descricao" value="<?php echo $produto_descricao; ?>" type="text" size="30" maxlength="80" title="Produto" />
					</td>
				</tr>

				<tr>
					<td align="left"  class="txt_branco">
						&nbsp;Defeito Reclamado: <span> * </span>
					</td>
					<td align='left' colspan='5' width='630' valign='top'>

						<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
							<select style="width: 199px;" id="defeito_" name="defeito_">
								<option value="">Digite primeiro o Produto acima</option>
							</select>
						</div>

					</td>
				</tr>
				<!-- HD 941072 - fim -->

				<tr>
					<td align="left" valign="top">
						<div class="txt_branco" style="padding: 5px; padding-left: 10px; width: 304px !important">&nbsp;Mensagem: <span> * </span></div>
					</td>
					<td class="style1">
						<textarea name="mensagem" cols="29" rows="5" class="form_text" id="mensagem" title="Mensagem"><?=$mensagem?></textarea>
					</td>
				</tr>
				<tr>
					<td align="left" valign="top" >&nbsp;</td>
					<td align="center">
						<button type="submit" style="cursor:pointer;">ENVIAR</button>
					</td>
				</tr>
			</table>

 		</form>

	</div>

</div>
<?die();?>
