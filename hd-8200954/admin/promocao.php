<html>
<head>
<title>Philips</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/emcimadahora.css" rel="stylesheet" type="text/css">
<script language="JavaScript" type="text/javascript">
<!--
/* Vefifica c e numero				*/ function Rules_Numero(c) { return (((c >=-99999999*9999999) && (c <=99999999*9999999)) || (c.indexOf(",")>=0)) }
/* Vefifica { } ( ) < > [ ] | \ /  	*/ function Rules_Esp1(c) { return ((c.indexOf("{")>=0) || (c.indexOf("}")>=0) || (c.indexOf("(")>=0) || (c.indexOf(")")>=0) || (c.indexOf("<")>=0) || (c.indexOf(">")>=0) || (c.indexOf("[")>=0) || (c.indexOf("]")>=0) || (c.indexOf("|")>=0) || (c.indexOf("../")>=0)) }
/* Vefifica & * $ % ? ! ^ ~ ` ' "  	*/ function Rules_Esp2(c) { return ((c.indexOf("&")>=0) || (c.indexOf("*")>=0) || (c.indexOf("$")>=0) || (c.indexOf("%")>=0) || (c.indexOf("?")>=0) || (c.indexOf("!")>=0) || (c.indexOf("^")>=0) || (c.indexOf("~")>=0) || (c.indexOf("`")>=0) || (c.indexOf("\"")>=0) || (c.indexOf("`")>=0) || (c.indexOf("'")>=0)) }
/* Vefifica , ; : = #  				*/ function Rules_Esp3(c) { return ((c.indexOf(",")>=0) || (c.indexOf(";")>=0) || (c.indexOf(":")>=0) || (c.indexOf("=")>=0) || (c.indexOf("#")>=0)) }
/* Vefifica @ .  					*/ function Rules_Email(c) { return ((c.indexOf("@")>=0) && (c.indexOf(".")>=0)); }
/* Verifica se o valor e Nulo       */ function Rules_Vazio(c) { return ((c == null) || (c.length == 0)); }
/* Verifica se o valor e Nulo       */ function Rules_Pequeno(c) { return ((c.length < 6)); }
/* Verifica estado valido	       */ function Rules_Estado(c) { return ((c.value == 'AC' || c.value == 'AL' || c.value == 'AM' || c.value == 'BA' || c.value == 'CE' || c.value == 'DF' || c.value == 'ES' || c.value == 'GO' || c.value == 'MA' || c.value == 'MT' || c.value == 'MS' || c.value == 'MG' || c.value == 'PA' || c.value == 'PE' || c.value == 'PB' || c.value == 'PI' || c.value == 'PR' || c.value == 'RJ' || c.value == 'RN' || c.value == 'RO' || c.value == 'RR' || c.value == 'SE' || c.value == 'SC' || c.value == 'SP' || c.value == 'TO')); }
function Tecla(e)
{
	if(document.all) // Internet Explorer
	{
  	var tecla = event.keyCode;
		if(tecla > 47 && tecla < 58) // numeros de 0 a 9
			return true;
		else
		{
			if (tecla != 8) // backspace
				return false;
			else
				return true;
    }
  }
	else if(document.layers) // Nestcape
	{
		var tecla = event.keyCode;
		if(tecla > 47 && tecla < 58) // numeros de 0 a 9
			return true;
		else
		{
			if (tecla != 8) // backspace
				return false;
			else
				return true;
	    }
	}
	else
	{
		if((e.which > 47 && e.which < 58) || e.which == 0) // numeros de 0 a 9
			return true;
		else
		{
			if (e.which != 8) // backspace
				return false;
			else
				return true;
	    }
	}
}

function valida()
{
	if(Rules_Vazio(document.frm.txtNome.value))
	{
		alert("Por favor preencher o campo nome.");
		document.frm.txtNome.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtEmail.value))
	{
		alert("Por favor preencher o campo e-mail.");
		document.frm.txtEmail.focus();
		return false;
	}

	if(!Rules_Email(document.frm.txtEmail.value))
	{
		alert("E-mail informado está incorreto.");
		document.frm.txtEmail.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtCpf.value))
	{
		alert("Por favor preencher o campo CPF.");
		document.frm.txtCpf.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtCidade.value))
	{
		alert("Por favor preencher o campo cidade.");
		document.frm.txtCidade.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtEndereco.value))
	{
		alert("Por favor preencher o campo endereço.");
		document.frm.txtEndereco.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtNumero.value))
	{
		alert("Por favor preencher o campo número.");
		document.frm.txtNumero.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.cmbEstado.value))
	{
		alert("Por favor escolha o estado.");
		document.frm.cmbEstado.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtCep.value))
	{
		alert("Por favor preencher o campo CEP.");
		document.frm.txtCep.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtDDD.value))
	{
		alert("Por favor preencher o campo DDD.");
		document.frm.txtDDD.focus();
		return false;
	}

	if(Rules_Vazio(document.frm.txtTelefone.value))
	{
		alert("Por favor preencher o campo telefone.");
		document.frm.txtTelefone.focus();
		return false;
	}

	document.frm.submit();
}
//-->
</script>
</head>

<body background="imagens/hotsite_background.gif" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<div align="center">
  <table width="770" border="0" cellspacing="0" cellpadding="0">
    <tr>

      <td><table width="770" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td><img src="imagens/spacer.gif" width="15" height="20"></td>
          <td width="580" valign="top"><img src="imagens/spacer.gif" width="15" height="15"></td>
          <td><img src="imagens/spacer.gif" width="15" height="15"></td>
          <td><img src="imagens/spacer.gif" width="15" height="15"></td>
        </tr>
        <tr>
          <td width="15"><img src="imagens/spacer.gif" width="25" height="21"></td>

          <td width="580" valign="top"><img src="imagens/mainlogo.gif" width="102" height="21"></td>
          <td class="fonte-azulink1117"><strong><a href="http://www.philips.com/global" target="_blank"><img src="imagens/arrow_orange.gif" border="0">Visit
                Philips Global</a></strong> </td>
          <td width="15">&nbsp;</td>
        </tr>
        <tr>
          <td><img src="imagens/spacer.gif" width="15" height="5"></td>
          <td width="580"><img src="imagens/spacer.gif" width="100" height="5"></td>
          <td><img src="imagens/spacer.gif" width="15" height="5"></td>

          <td><img src="imagens/spacer.gif" width="15" height="5"></td>
        </tr>
      </table></td>
    </tr>
    <tr>
      <td><img src="imagens/spacer.gif" width="770" height="20"></td>
    </tr>
    <tr>
      <td>

      	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="770" height="215">
          <param name="movie" value="logo_ganhadores.swf">
          <param name="quality" value="high">
		  		<param name="wmode" value="transparent">
          <embed src="logo_ganhadores.swf" quality="high" wmode="transparent" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="770" height="215"></embed>
      	</object>
      </td>
    </tr>
    <tr>

      <td><table width="770" height="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td valign="top" background="imagens/hotsite_background4.gif">

            <table width="770" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td><img src="imagens/spacer.gif" width="190" height="5"></td>
            <td width="578"><table width="578" border="0" cellspacing="0" cellpadding="0">
              <tr>

                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>
                	<form name="frm" method="post" action="http://www.clubphilips.com.br/emcimadahora/HOTSITE_ganhadores/hotsite_confirmeseusdados.asp">
                	<input type="hidden" name="ID_BASEBRASIL" value="">
									<input type="hidden" name="txtEmail" value="rafaeltakashi@hotmail.com">
                	<table width="578" border="0" cellspacing="0" cellpadding="0">
                    <tr>

                      <td width="12"><img src="imagens/spacer.gif" width="12" height="135"></td>
                      <td valign="top"><table width="550" border="0" cellpadding="0" cellspacing="0" class="fonte-black1117">
                          <tr>
                            <td width="67"><img src="imagens/spacer.gif" width="67" height="5"></td>
                            <td width="189"><img src="imagens/spacer.gif" width="158" height="5"></td>
                            <td width="25"><img src="imagens/spacer.gif" width="25" height="5"></td>
                            <td width="332"><img src="imagens/spacer.gif" width="301" height="5"></td>
                          </tr>
                          <tr>

                            <td class="fonte-black1015"><span class="fonte-laranja0912">*</span> Nome:</td>
                            <td><input name="txtNome" type="text" class="box1" id="nome6" size="25" maxlength="100" value=""></td>
                            <td>&nbsp;</td>
                            <td class="fonte-black1015"><span class="fonte-laranja0912">*</span> E-mail:
                                <input maxlength="100" name="ExibeEmail"  value="rafaeltakashi@hotmail.com" type="text" class="box1" id="email5" size="25">
                            </td>
                          </tr>

                          <tr>
                            <td><img src="imagens/spacer.gif" width="67" height="5"></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                          </tr>
                          <tr>
                            <td><span class="fonte-laranja0912">*</span> CPF:</td>

                            <td><input name="txtCpf" value="" onKeyPress="return Tecla(event);" maxlength="11" type="text" class="box1" id="cidade5" size="11"></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                          </tr>
                          <tr>
                            <td><img src="imagens/spacer.gif" width="67" height="5"></td>
                            <td><img src="imagens/spacer.gif" width="158" height="5"></td>
                            <td><img src="imagens/spacer.gif" width="25" height="15"></td>
                            <td><img src="imagens/spacer.gif" width="301" height="5"></td>

                          </tr>
                          <tr>
                            <td class="fonte-black1015"><span class="fonte-laranja0912">*</span> Cidade:</td>
                            <td><input name="txtCidade" maxlength="100" type="text" class="box1" id="cidade5" size="25" value=""></td>
                            <td>&nbsp;</td>
                            <td class="fonte-black1015"><span class="fonte-laranja0912">*</span> Rua:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                                <input maxlength="100" name="txtEndereco" type="text" class="box1" id="rua5" size="20" value="">
                                &nbsp;&nbsp;&nbsp;<span class="fonte-laranja0912">*</span> N&uacute;mero:&nbsp;<span class="fonte-black1015">
                                <input maxlength="20" name="txtNumero" type="text" class="box1" value="" id="numero5" size="6">
                								</span>
                						</td>
                          </tr>
                          <tr>

                            <td><img src="imagens/spacer.gif" width="67" height="5"></td>
                            <td><img src="imagens/spacer.gif" width="158" height="5"></td>
                            <td><img src="imagens/spacer.gif" width="25" height="15"></td>
                            <td><img src="imagens/spacer.gif" width="301" height="5"></td>
                          </tr>
                          <tr>
                            <td width="67" class="fonte-black1015"><span class="fonte-laranja0912">*</span> Estado:</td>

                            <td width="189"><strong>
															<select name="cmbEstado" class="box1" id="select4">
																<option>Escolha...</option>
																<option value="AC"  >AC</option>
																<option value="AL" >AL</option>
																<option value="AP" >AP</option>
																<option value="AM" >AM</option>

																<option value="BA" >BA</option>
																<option value="CE" >CE</option>
																<option value="DF" >DF</option>
																<option value="ES" >ES</option>
																<option value="GO" >GO</option>
																<option value="MA" >MA</option>

																<option value="MT" >MT</option>
																<option value="MS" >MS</option>
																<option value="MG" >MG</option>
																<option value="PA" >PA</option>
																<option value="PB" >PB</option>
																<option value="PE" >PE</option>

																<option value="PI" >PI</option>
																<option value="PR" >PR</option>
																<option value="RJ" >RJ</option>
																<option value="RN" >RN</option>
																<option value="RO" >RO</option>
																<option value="RR" >RR</option>

																<option value="RS" >RS</option>
																<option value="SE" >SE</option>
																<option value="SC" >SC</option>
																<option value="SP" >SP</option>
																<option value="TO" >TO</option>
															</select>

                            </strong></td>
                            <td width="25">&nbsp;</td>
                            <td width="332" class="fonte-black1015">&nbsp;&nbsp;&nbsp;Complemento:&nbsp;
                            	<input name="txtComplemento" type="text" class="box1" maxlength="100" value="" id="complemento5" size="12">
																&nbsp;&nbsp;&nbsp;&nbsp;<span class="fonte-laranja0912">*</span> CEP:<span class="fonte-black1015">
                  							<input name="txtCep" type="text" maxlength="8" class="box1" onKeyPress="return Tecla(event);" id="cep14" size="8" value="">
                  						</span>

                  					</td>
                          </tr>
                          <tr>
                            <td width="67"><img src="imagens/spacer.gif" width="67" height="5"></td>
                            <td width="189"><img src="imagens/spacer.gif" width="158" height="5"></td>
                            <td width="25"><img src="imagens/spacer.gif" width="25" height="15"></td>
                            <td width="332"><img src="imagens/spacer.gif" width="301" height="5"></td>
                          </tr>
                          <tr>

                            <td width="67"><span class="fonte-laranja0912">*</span><span class="fonte-black1015"> Telefone:</span></td>
                            <td width="189">
                            	<input name="txtDDD" maxlength="2" onKeyPress="return Tecla(event);" type="text" class="box1" id="foneddd6" size="2" value=""> -
                            	<input name="txtTelefone" type="text" maxlength="8" onKeyPress="return Tecla(event);" class="box1" id="fone17" size="12" value="">
                            </td>
                            <td width="25">&nbsp;</td>
                            <td width="332"><span class="fonte-black1015">&nbsp;&nbsp;&nbsp;Celular:
                            		<input maxlength="2" name="txtDDDCel" onKeyPress="return Tecla(event);" type="text" class="box1" id="cel14" size="2" value="" > -
                            		<input name="txtCelular" maxlength="8" type="text" class="box1" id="cel24" onKeyPress="return Tecla(event);" size="12" value="">

                            </span></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>

                <td><img src="imagens/spacer.gif" width="550" height="20"></td>
              </tr>
              <tr>
                <td><a href="#" onClick="valida();"><img src="imagens/bot_enviar.gif" width="215" height="60" border="0"></a></td>
              </tr>
              <tr>
                <td class="fonte-laranja0917"><div align="right">* Campos obrigat&oacute;rios&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                </td>

              </tr>
              <tr>
                <td><img src="imagens/spacer.gif" width="550" height="200"></td>
              </tr>
            </table></td>
          </tr>
        </table>

		  </td>

          </tr>
        <tr>
          <td valign="top">&nbsp;		    </td>
          </tr>
      </table>
      </td>
    </tr>
    <tr>

      <td align="center"><table width="750" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="105">&nbsp;</td>
          <td align="center" class="fonte-cinza0917"><a href="http://www.philips.com.br/siteowner/" target="_blank">Philips</a> | <a href="http://www.philips.com.br/privacypolicy" target="_blank">Pol&iacute;tica
              de privacidade</a> | <a href="http://www.philips.com.br/terms" target="_blank">Termos
              de uso</a><br>&copy;2006 Koninklijke Philips Electronics N.V. Todos os direitos reservados.</td>

          <td width="105">&nbsp;</td>
        </tr>
      </table></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
  </table>
</div>

</body>
</html>
