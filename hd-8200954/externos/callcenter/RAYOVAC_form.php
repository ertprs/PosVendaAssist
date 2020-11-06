<!---
<html>
<head>

<title>
	Contato - Rayovac
</title>
-->

    <!-- inicio: jquery -->
	<script src="./Rayovac_files/backgroundPosition.js" type="text/javascript"></script>    
    <!-- fim: jquery -->
    
	<script src="./Rayovac_files/WebResource.axd" type="text/javascript"></script>
	<script src="./Rayovac_files/WebResource(1).axd" type="text/javascript"></script>
	<script src="./Rayovac_files/communs.js" type="text/javascript"></script>
	
    <script src="./Rayovac_files/swfobject.js" type="text/javascript"></script>
	<link href="./Rayovac_files/master.css" rel="stylesheet" type="text/css" />
	<link href="./Rayovac_files/master(1).css" rel="stylesheet" type="text/css" />
	<link href="./Rayovac_files/style.css" rel="stylesheet" type="text/css" />
	<link href="./Rayovac_files/style(1).css" rel="stylesheet" type="text/css" />
    <!--[if IE]>
    <link href="resources/css/ie.css" rel="stylesheet" type="text/css" />
    <![endif]-->
	<style type='text/css'>
		form table td {height: 45px;vertical-align: middle;min-width:150px;}
	</style>

<!---
</head>
<body onload="resize()">
-->

	<!--- HEADER -->
	<form name="aspnetForm" method="post" action="./Rayovac_files/Rayovac.html" onsubmit="javascript:return WebForm_OnSubmit();" id="aspnetForm">
	<div>
		<input type="hidden" name="__EVENTTARGET" id="__EVENTTARGET" value="">
		<input type="hidden" name="__EVENTARGUMENT" id="__EVENTARGUMENT" value="">
		<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="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">
	</div>

	<div>
		<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="/wEWCQKh0vCfAwLdyYncCwKG9u1qAriR4awKAoacnfEBAumlyMEEArrk4loCuvTluwkCp+eXlA7ca4cFX7SseHE1r6kXud38HzGwiw==">
	</div>
	<div id="header_busca">
		<div id="logo_busca" class="widthGeral">
			<div class="logo"><a href="http://la.rayovac.com/default.aspx"><img src="./Rayovac_files/logo.png" border="0"></a></div>
			<!-- BUSCA -->
			<div class="busca">
				<div class="campo_busca">
                    <input name="ctl00$txbBusca" type="text" value="¿QUÉ BUSCAS?" id="ctl00_txbBusca" class="q">
					<input type="image" name="ctl00$ibBusca" id="ctl00_ibBusca" class="btn_ok" src="./Rayovac_files/ok_btn.png" style="border-width:0px;">
				</div>
				<div class="paises">
                	<img src="./Rayovac_files/selecione.png"><br>
                    
		                <ul class="topnav">
        	                <li>  
            	                <a href="http://la.rayovac.com/Contactenos.aspx#">Todos</a>                        	
                                <ul class="subnav">         
                                    <li><a href="http://la.rayovac.com/?pais_id=1">Todos</a></li>  

                                    <li><a id="ctl00_rptPaises_ctl01_hlPais" href="http://la.rayovac.com/?pais_id=2">Argentina</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl02_hlPais" href="http://la.rayovac.com/?pais_id=24">Bolivia</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl03_hlPais" href="http://la.rayovac.com/?pais_id=4">Brasil</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl04_hlPais" href="http://la.rayovac.com/?pais_id=5">Chile</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl05_hlPais" href="http://la.rayovac.com/?pais_id=7">Costa Rica</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl06_hlPais" href="http://la.rayovac.com/?pais_id=9">Cuba</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl07_hlPais" href="http://la.rayovac.com/?pais_id=10">El Salvador</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl08_hlPais" href="http://la.rayovac.com/?pais_id=11">Guatemala</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl09_hlPais" href="http://la.rayovac.com/?pais_id=12">Haiti</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl10_hlPais" href="http://la.rayovac.com/?pais_id=13">Honduras</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl11_hlPais" href="http://la.rayovac.com/?pais_id=23">Islas del Caribe</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl12_hlPais" href="http://la.rayovac.com/?pais_id=27">México</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl13_hlPais" href="http://la.rayovac.com/?pais_id=15">Nicarágua</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl14_hlPais" href="http://la.rayovac.com/?pais_id=16">Panamá</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl15_hlPais" href="http://la.rayovac.com/?pais_id=17">Paraguay</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl16_hlPais" href="http://la.rayovac.com/?pais_id=21">República Dominicana</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl17_hlPais" href="http://la.rayovac.com/?pais_id=19">Uruguay</a></li>  
                        
                                    <li><a id="ctl00_rptPaises_ctl18_hlPais" href="http://la.rayovac.com/?pais_id=20">Venezuela</a></li>  
                        
                                </ul>
                        </ul>
				</div>
			</div>
			<!-- FIM BUSCA -->
		</div>
		
		<div class="clearBooth"></div>
		
		<!-- MENU -->
		<div id="menu" class="widthGeral">
			<a id="ctl00_hlHome" class="home" href="http://la.rayovac.com/Default.aspx">Home</a>
			<a id="ctl00_hlRayovac" class="rayovac" href="http://la.rayovac.com/Historia.aspx">Rayovac</a>
			<a id="ctl00_hlPilas" class="pilas" href="http://la.rayovac.com/Produto.aspx?pid=6">Pilas</a>
			<a id="ctl00_hlLinternas" class="linternas" href="http://la.rayovac.com/Produto.aspx?pid=5">Linternas</a>
			<a id="ctl00_hlPromociones" class="promociones" href="http://la.rayovac.com/Promociones.aspx">Promociones</a>
			<a id="ctl00_hlServicos" class="servicos" href="http://la.rayovac.com/ServiciosAlConsumidor.aspx">Servicios Al Consumidor</a>
			<a id="ctl00_hlContactenos" class="contactenos" href="./Rayovac_files/Rayovac.html">Contactenos</a>
		</div>
		<!-- FIM MENU -->
		
	</div>
	</form>
	<!-- FIM HEADER -->
	
	<div class="bg_superior" style="height: 1113px;"></div>
	
	<div id="bg_inferior">
		<!-- GERAL -->
	<div id="geral" class="widthGeral">
    <div class="contactenos">
        <h1>Contáctanos</h1>
		<div class="envia-tu-mensaje">
			<h2>Envía tu mensaje</h2>
			<form action="" name="contato" method="post" onsubmit="return frmValidaFormContato()">
				<input type='hidden' name='marcaID' value='<?=$marcaID?>' />
				<table width="550" border="0" align="center" cellpadding="3" cellspacing="8">
					
					<caption><p style="text-align:right; border: 0px;">* Campos obrigatórios</p></caption>

					<?php if(count($msg_erro) > 0){?>
					<tr>
						<td colspan='2' style='background-color:#FF0000; font: bold 16px "Arial"; color:#FFFFFF; text-align:center;'>
							<?php echo implode('<br />', $msg_erro);?>
						</td>
					</tr>
					<?php }?>
					<tr>
						<td width="200" align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Assunto: <span> * </span></div>
						</td>
						<td>&nbsp;&nbsp;
							<select name='assunto' id='assunto' title='Assunto' style='width:280px; height:28px'>
								<option value='' <?php if($assunto == '') echo " selected "?>>- selecione</option>
								<option value='informacao' <?php if($assunto == 'informacao') echo " selected "?>>Informação</option>
								<option value='sugestao' <?php if($assunto == 'sugestao') echo " selected "?>>Sugestão</option>
								<option value='reclamacao_produto' <?php if($assunto == 'reclamacao_produto') echo " selected "?>>Reclamação</option>
							</select>
						</td>
					</tr>
					<tr>
						<td width="177" align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">
							&nbsp;Nome Completo: <span> * </span>
							</div>
						</td>
						<td width="220" class="txb-nome">
							&nbsp;&nbsp;<input name="nome_completo" type="text" class="form_text" id="nome_completo" title="Nome" size="40" placeholder='Digite seu nome' value='<?=$nome?>'>
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Sexo: <span> * </span></div>
						</td>
						<td class="texto">
							&nbsp;&nbsp;<input name="sexo" type="radio" value="M" id='M' <?php if ($sexo == 'M') echo "CHECKED";?>> 
							<label style='color: #666;' for='M'>Masculino</label>
							&nbsp;&nbsp;<input name="sexo" type="radio" value="F" id='F' <?php if ($sexo == 'F') echo "CHECKED";?>> 
							<label style='color: #666;' for='F'>Feminino</label>
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Data Nascimento: <span> *</span></div>
						</td>
						<td class="txb-nome">
							&nbsp;&nbsp;<input name="data_nascimento" type="text" class="form_text data_nascimento" id="data_nascimento" size="12" value='<?=$data_nascimento2?>' title="Data de Nascimento">
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;E-mail: <span> * </span></div>
						</td>
						<td class="txb-nome">
							&nbsp;&nbsp;<input name="email" type="email" class="form_text" id="email" placeholder='Seu endereço de e-mail'
							title="Email" size="40" value='<?=$email?>'></td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Tel: <span> * </span></div>
						</td>
						<td class="txb-nome">
							&nbsp;&nbsp;<input name="telefone" type="text" class="form_text telefone" size='8' maxlength='9' id="telefone" placeholder='Telefone' title="Telefone" value='<?=$telefone2?>'>
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;CEP: <span> * </span></div>
						</td>
						<td class="txb-nome">
							&nbsp;&nbsp;<input name="cep" type="text" class="form_text cep" id="cep" size="9" maxlength='9' value='<?=$cep?>' title="CEP">
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Endere&ccedil;o Completo: <span> * </span></div>
						</td>
						<td class="txb-nome">
							&nbsp;&nbsp;<input name="endereco" type="text" placeholder='Se sabe seu CEP, digite-o acima para agilizar'
							class="form_text" id="endereco" size="40" value='<?=$endereco?>' title="Endere&ccedil;o Completo">
						</td>
					</tr>

		<tr>
			<td align="right" class="txt_branco">
				<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Número: <span> * </span></div>
			</td>
			<td class="txb-nome">
				&nbsp;&nbsp;<input name="numero" type="text" class="form_text" id="numero" size="20" maxlength="20" value='<?=$numero?>' title="Número">
			</td>
		</tr>
		<tr>
			<td align="right" class="txt_branco">
				<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Complemento: </div>
			</td>
			<td class="txb-nome">
				&nbsp;&nbsp;<input name="complemento" type="text" class="form_text" id="complemento" size="30" value='<?=$complemento?>' title="Complemento">
			</td>
		</tr>

					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Bairro: <span> * </span></div>
						</td>
						<td class="txb-nome">
							&nbsp;&nbsp;<input name="bairro" type="text" class="form_text" id="bairro" size="40" value='<?=$bairro?>' title="Bairro">
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Estado: <span> * </span></div>
						</td>
						<td style="width: 95px; height: 45px">&nbsp;&nbsp;
							<!-- <input name="estado" type="text" class="form_text" id="estado" size="2" maxlength="2" value='<?=$estado?>'>//-->
							<select name='estado' id='estado' style='width:280px; height:28px'>
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
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Cidade: <span> * </span></div>
						</td>
						<td style="width: 95px; height: 45px" >
							&nbsp;&nbsp;
							<!--<input name="cidade" type="text" class="form_text" id="cidade" size="40" value='<?=$cidade2?>' title="Cidade">-->
							<select name='cidade' id='cidade' style='width:280px; height:28px'>
							</select>
						</td>
					</tr>
					<tr>
						<td align="right" class="txt_branco">
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Produto: <span> * </span></div>
						</td>
						<td class="txb-nome">
							<input type='hidden' name='produto' id='produto'>
							<!--<input name="produto_descricao" type="text" class="form_text" id="produto_descricao" size="40" value='<?=$produto_descricao?>'>-->
							<!-- HD 941072 - Busca autocomplete pelo Nome do Produto populando o combo de Defeitos Reclamados -->
							&nbsp;&nbsp;<input name="produto_descricao" class="form_text" id="produto_descricao" value="<?php echo $produto_descricao; ?>" type="text" size="40" maxlength="80" title="Produto" />
						</td>
					</tr>

<tr>
	<td align="right" class="txt_branco">
		<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Defeito Reclamado: <span> * </span></div>
	</td>
	<td align='left' colspan='5' width='630' valign='top'>&nbsp;&nbsp;

		<div id='div_defeitos' style='display:inline; width:100%'>
			<select id="defeito_" name="defeito_" style="width:280px; height:28px">
				<option value="">Digite primeiro o Produto acima</option>
			</select>
		</div>

	</td>
</tr>
<!-- HD 941072 - fim -->

					<tr>
						<td align="right" valign="top" class="txt_branco" style='vertical-align:top'>
							<div style="font-weight:bold; background-color:#007ab7; color: #FFFFFF; height:20px; padding-top:5px;">&nbsp;Mensagem: <span> * </span></div>
						</td>
						<td style="width: 95px; height: 45px">
							&nbsp;&nbsp;<textarea name="mensagem" cols="40" rows="5" class="txb-messaje1" id="mensagem" title="Mensagem"><?=$mensagem?></textarea>
						</td>
					</tr>
					<tr>
						<td align="right" valign="top" >&nbsp;</td>
						<td align="center">
							<button type="submit" style="cursor:pointer;">ENVIAR</button>
						</td>
					</tr>
				</table>
			</form>
		</div>
        <div class="clearBooth"></div>
    </div>    
	</div>
</div>
		<!-- FIM GERAL -->
	<div id="footer">
		<div class="direitos_redesSociais widthGeral">
		
			<div class="direitos">
				<p class="amarelo">Spectrum Brands Inc.</p><br>
				<p class="azul">
                    <span id="ctl00_lblTodosDer">Todos los derechos reservados.</span><br>
                    <a id="ctl00_hlPolPriv" href="http://la.rayovac.com/PoliticaPrivacidad.aspx">Política de Privacidad</a> | 
                    <a id="ctl00_hlTimUso" href="http://la.rayovac.com/TerminosDeUso.aspx">Términos de uso</a> | 
                    <a id="ctl00_hlCopyright">Copyright 2010</a></p>
			</div>
			
			<div class="redesSociais">
				<a href="javascript:void(0)"><img src="./Rayovac_files/icon_facebook.jpg" border="0"></a>
				<a href="javascript:void(0)"><img src="./Rayovac_files/icon_twitter.jpg" border="0"></a>
				<a href="javascript:void(0)"><img src="./Rayovac_files/icon_youtube.jpg" border="0"></a>
			</div>
		</div>
	</div>

<!---
</body>
</html>
-->
