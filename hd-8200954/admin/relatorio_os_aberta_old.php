<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";


#Para a rotina automatica - Fabio - HD 11750
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

#include "gera_relatorio_pararelo_include.php";

include "funcoes.php";

$msg_erro = "";

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET ["btn_acao"]) > 0 ) $btn_acao = strtoupper($_GET ["btn_acao"]);

$intervencao = $_GET['intervencao'];
$excluidas   = $_GET['excluidas'];
if ( empty($excluidas) ) {
	$excluidas = $_POST['excluidas'];
}

if (strlen($btn_acao) > 0) {

	if (strlen($msg_erro)==0) {

		$codigo_posto    = "";
		$estado          = "";
		$qtde_dias       = "";
		$linha           = "";
		$os_tipo         = "";
		$consumidor_nome = "";
		$revenda_nome    = "";
		$revenda_cnpj    = "";


		if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
		if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

		if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
		if (strlen(trim($_GET["estado"])) > 0)  $estado = trim($_GET["estado"]);

		if (strlen(trim($_POST["qtde_dias"])) > 0) $qtde_dias = trim($_POST["qtde_dias"]);
		if (strlen(trim($_GET["qtde_dias"])) > 0)  $qtde_dias = trim($_GET["qtde_dias"]);

		if (strlen(trim($_POST["linha"])) > 0) $linha = trim($_POST["linha"]);
		if (strlen(trim($_GET["linha"])) > 0)  $linha = trim($_GET["linha"]);

		if (strlen(trim($_POST["os_tipo"])) > 0) $os_tipo = trim($_POST["os_tipo"]);
		if (strlen(trim($_GET["os_tipo"])) > 0)  $os_tipo = trim($_GET["os_tipo"]);

		if (strlen(trim($_POST["consumidor_nome"])) > 0) $consumidor_nome = trim($_POST["consumidor_nome"]);
		if (strlen(trim($_GET["consumidor_nome"])) > 0)  $consumidor_nome = trim($_GET["consumidor_nome"]);

		if (strlen(trim($_POST["revenda_nome"])) > 0) $revenda_nome = trim($_POST["revenda_nome"]);
		if (strlen(trim($_GET["revenda_nome"])) > 0)  $revenda_nome = trim($_GET["revenda_nome"]);

		if (strlen(trim($_POST["revenda_cnpj"])) > 0) $revenda_cnpj = trim($_POST["revenda_cnpj"]);
		if (strlen(trim($_GET["revenda_cnpj"])) > 0)  $revenda_cnpj = trim($_GET["revenda_cnpj"]);

		if (strlen(trim($_POST["agendar"])) > 0) $agendar = trim($_POST["agendar"]);
		if (strlen(trim($_GET["agendar"])) > 0)  $agendar = trim($_GET["agendar"]);

		if (strlen(trim($_POST["formato_arquivo"])) > 0) $formato_arquivo = trim($_POST["formato_arquivo"]);
		if (strlen(trim($_GET["formato_arquivo"])) > 0)  $formato_arquivo = trim($_GET["formato_arquivo"]);

		if (strlen(trim($_POST["produto_referencia"])) > 0) $produto_referencia = trim($_POST["produto_referencia"]);
		if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

		if (strlen(trim($_POST["cancelada_90_dias"])) > 0) $cancelada_90_dias = trim($_POST["cancelada_90_dias"]);
		if (strlen(trim($_GET["cancelada_90_dias"])) > 0)  $cancelada_90_dias = trim($_GET["cancelada_90_dias"]);


		if (strlen($codigo_posto)>0) {
			$sqlp = "SELECT posto
					 FROM tbl_posto_fabrica
					 WHERE fabrica = $login_fabrica
					 AND codigo_posto = '$codigo_posto'";
			$resp = pg_exec($con, $sqlp);

			if (pg_numrows($resp) > 0) {
				$posto = pg_result($resp, 0, 0);
			} else {
				$msg_erro = "Posto informado não encontrado.";
			}
		}

		if (strlen($codigo_posto)==0 and strlen($estado)==0 and $agendar=='NAO') {
			$msg_erro = "Para pesquisar deve ser informado posto ou estado.";
		}

		if ((!is_numeric($qtde_dias) or strlen($qtde_dias)==0) and $agendar=='NAO') {
			$msg_erro = "Parâmetro quantidade de dias inválido.";
		}

		if ($qtde_dias<10) {
			if($linha<>528){
				$msg_erro = "No mínimo 10 dias.";
			}
		}

		$revenda_cnpj = str_replace (".","",$revenda_cnpj );
		$revenda_cnpj = str_replace ("-","",$revenda_cnpj );
		$revenda_cnpj = str_replace ("/","",$revenda_cnpj );
		$revenda_cnpj = str_replace (",","",$revenda_cnpj );
		$revenda_cnpj = str_replace (" ","",$revenda_cnpj );

		//HD 12796
		if (strlen($msg_erro)==0 AND strlen($produto_referencia)>0) {
			$sql = "SELECT produto from tbl_produto join tbl_linha using(linha) where fabrica=$login_fabrica and upper(referencia) = upper('$produto_referencia')";
			$res = pg_exec($con, $sql);
			if (pg_numrows($res) <> 1) {
				$msg_erro = "Produto $produto_referencia não encontrado. Utilize a lupa para pesquisar.";
			}else{
				$produto=pg_result($res,0,produto);
			}
		}
	}
}

$layout_menu = "auditoria";
if($login_fabrica==45 )$layout_menu="callcenter";
$title = "Relação de Ordens de Serviço em aberto";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.divisao{
	width:600px;
	text-align:center;
	margin:0 auto;
	font-size:10px;
	background-color:#FEFCCF;
	border:1px solid #928A03;
	padding:5px;
}
.sucesso{
	width:600px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}


.menu_ajuda{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#332D00;
	background-color: #FFF9CA;
}
</style>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

// ========= Fun??o PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_relatorio.revenda_nome;
			janela.cnpj			= document.frm_relatorio.revenda_cnpj;
			janela.fone			= document.frm_relatorio.revenda_fone;
			janela.cidade		= document.frm_relatorio.revenda_cidade;
			janela.estado		= document.frm_relatorio.revenda_estado;
			janela.endereco		= document.frm_relatorio.revenda_endereco;
			janela.numero		= document.frm_relatorio.revenda_numero;
			janela.complemento	= document.frm_relatorio.revenda_complemento;
			janela.bairro		= document.frm_relatorio.revenda_bairro;
			janela.cep			= document.frm_relatorio.revenda_cep;
			janela.email		= document.frm_relatorio.revenda_email;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}


// ========= Fun??o PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.cliente		= document.frm_relatorio.consumidor_cliente;
			janela.nome			= document.frm_relatorio.consumidor_nome;
			janela.cpf			= document.frm_relatorio.consumidor_cpf;
			janela.rg			= document.frm_relatorio.consumidor_rg;
			janela.cidade		= document.frm_relatorio.consumidor_cidade;
			janela.estado		= document.frm_relatorio.consumidor_estado;
			janela.fone			= document.frm_relatorio.consumidor_fone;
			janela.endereco		= document.frm_relatorio.consumidor_endereco;
			janela.numero		= document.frm_relatorio.consumidor_numero;
			janela.complemento	= document.frm_relatorio.consumidor_complemento;
			janela.bairro		= document.frm_relatorio.consumidor_bairro;
			janela.cep			= document.frm_relatorio.consumidor_cep;
			janela.proximo		= document.frm_relatorio.revenda_nome;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

</script>

<?
include "cabecalho.php";
?>
<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
#	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
#	include "gera_relatorio_pararelo_verifica.php";
}
?>

<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>

	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>OSs em aberto: INFORME O POSTO OU O ESTADO PARA PESQUISAR </td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
				<?if($login_fabrica==3 OR $login_fabrica == 15){?>
				  <TR align='right'>
					  <TD>Marca:&nbsp;</TD>

					  <TD align='left'>	<!-- começa aqui -->
							<?
							$sql = "SELECT  *
									FROM    tbl_marca
									WHERE   tbl_marca.fabrica = $login_fabrica
									ORDER BY tbl_marca.nome;";
							$res = pg_exec ($con,$sql);

							if (pg_numrows($res) > 0) {
								echo "<select name='marca'>\n";
								echo "<option value=''>ESCOLHA</option>\n";
								for ($x = 0 ; $x < pg_numrows($res) ; $x++){
									$aux_marca = trim(pg_result($res,$x,marca));
									$aux_nome  = trim(pg_result($res,$x,nome));

									echo "<option value='$aux_marca'";
									if ($marca == $aux_marca){
										echo " SELECTED ";
									}
									echo ">$aux_nome</option>\n";
								}
								echo "</select>\n&nbsp;";
							}
							?>
					</TD>
					</TR>
				<?}?>

				<tr width='100%' >
					<td align='right' height='20'>Código Posto:&nbsp;</td>
					<td align='left'>
						<input class="Caixa" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
				</tr>

				<tr>
					<td align='right'>Razão Social:&nbsp;</td>
					<td align='left'><input class="Caixa" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>

				<tr>
					<td align='right'>Estado:&nbsp;</td>
					<td align='left'>
						<select name="estado" size="1">
							<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS</option>
							<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</td>
				</tr>

				<tr>
					<td align='right'>Linha:&nbsp;</td>
					<td><?
						$sql = "SELECT  *
								FROM    tbl_linha
								WHERE   tbl_linha.fabrica = $login_fabrica
								ORDER BY tbl_linha.nome;";
						$res = pg_exec ($con,$sql);

						if (pg_numrows($res) > 0) {
							echo "<select name='linha'>\n";
							echo "<option value=''>TODAS</option>\n";
							for ($x = 0 ; $x < pg_numrows($res) ; $x++){
								$aux_linha = trim(pg_result($res,$x,linha));
								$aux_nome  = trim(pg_result($res,$x,nome));

								echo "<option value='$aux_linha'";
								if ($linha == $aux_linha){
									echo " SELECTED ";
									$mostraMsgLinha = "<br> da LINHA $aux_nome";
								}
								echo ">$aux_nome</option>\n";
							}
							echo "</select>\n&nbsp;";
						}
						?>
					</td>
				</tr>

				<tr width='100%' >
					<td align='right' height='20'>OSs em aberto há mais de:&nbsp;</td>
					<td align='left'><input type="text" name="qtde_dias" size="3" maxlength="3" <? if (strlen($qtde_dias)> 0) echo "value=\"$qtde_dias\""; else echo "VALUE=\"\"";?>> dias</td>
				</tr>

				<tr>
					<td align='right'>Tipo:&nbsp;</td>
					<td align='left'>
						<select name="os_tipo" size="1">
							<option value=""   <? if (strlen($os_tipo) == 0)    echo " selected "; ?>>TODOS</option>
							<option value="C" <? if ($os_tipo == "C") echo " selected "; ?>>CONSUMIDOR</option>
							<option value="R" <? if ($os_tipo == "R") echo " selected "; ?>>REVENDA</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align='right'>Nome Consumidor</td>
					<td>
						<input class="Caixa" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>">
					</td>
				</tr>
				<tr valign="top">
					<td align='right'>CNPJ Revenda</td>
					<td>
						<input class="Caixa" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_cnpj, "cnpj")' style='cursor: pointer'>
						<input type ='hidden' name='revenda_fone' value=' '>
						<input type ='hidden' name='revenda_cidade' value=' '>
						<input type ='hidden' name='revenda_estado' value=' '>
						<input type ='hidden' name='revenda_endereco' value=' '>
						<input type ='hidden' name='revenda_numero' value=' '>
						<input type ='hidden' name='revenda_complemento' value=' '>
						<input type ='hidden' name='revenda_bairro' value=' '>
						<input type ='hidden' name='revenda_cep' value=' '>
						<input type ='hidden' name='revenda_email' value=' '>
					</td>
				</tr>
				<tr>
					<td align='right'>Nome Revenda</td>
					<td>
						<input class="Caixa" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_nome, "nome")' style='cursor: pointer'>
					</td>
				</tr>
				<?if ($login_fabrica == 11){  //HD 12796?>
				<tr valign="top">
					<td align='right'>Ref. Produto</td>
					<td>
					<input class="Caixa" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
					&nbsp;
					<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
				</tr>
				<tr valign="top">
					<td align='right'>Descrição Produto</td>
					<td>
					<input class="Caixa" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
					&nbsp;
					<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
				</tr>
				<?}?>
				<tr>
					<td align='right'>Situação</td>
					<td >
						<input type='radio' name='situacao' value='c'<?if($situacao=='c')echo "checked";?>> Com peça
						<input type='radio' name='situacao' value='s'<?if($situacao=='s')echo "checked";?>> Sem peça
						<input type='radio' name='situacao' value='a'<?if($situacao=='a')echo "checked";?>> Ambos
					</td>
				</tr>
				<? if($login_fabrica==3){?>
				<tr>
					<td colspan='2' align='center'>
						<INPUT TYPE="radio" NAME="conserto" value="t" <?if($conserto=='t')echo "checked";?>>Todas
						<INPUT TYPE="radio" NAME="conserto" value="c" <?if($conserto=='c')echo "checked";?>>Consertadas
						<INPUT TYPE="radio" NAME="conserto" value="n" <?if($conserto=='n')echo "checked";?>>Não Consertadas
					</td>
				</tr>
				<tr>
					<td colspan='2' align='center'><input type='checkbox' name='intervencao' value='t' <?if($intervencao=='t')echo "checked";?>> OSs que não estão em intervenção </td>
				</tr>
				<tr>
					<td colspan='2' align='center'><input type='checkbox' name='excluidas' value='t' <?if($excluidas=='t')echo "checked";?>> Desconsiderar OSs excluídas </td>
				</tr>
				<tr>
					<td colspan='2' align='center'><input type='checkbox' name='cancelada_90_dias' value='t' <?if($cancelada_90_dias=='t')echo "checked";?>> Desconsiderar OSs Canceladas (OS aberta a mais 90 dias - Cancelada)</td>
				</tr>
				<?}?>
				<tr>
					<td colspan='2'><hr></td>
				</tr>

				<tr>
					<td align='right'>Tipo Arquivo para Download</td>
					<td>

					<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
					&nbsp;&nbsp;&nbsp;
					<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
					</td>
				</tr>

				<tr bgcolor="#D9E2EF">
					<td colspan="2	" align="center" ><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>

			</table>
		</td>
	</tr>
</table>

<?


if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {

	echo "<span id='msg_carregando'><img src='imagens/carregar2.gif'><font face='Verdana' size='1' color=#FF0000>
	<BR>Aguarde carregamento.
	<BR>Devido a grande quantidade de Ordem de Serviço o resultado pode levar alguns minuto para ser exibido.
	<BR>Aguarde até o término do carregamento.</font></span>";
	flush();

	if (strlen($posto)>0) $cond_posto = " tbl_os.posto = $posto ";
	else                  $cond_posto = " 1=1 ";

	$cond_estado     = "";
	$cond_estado2    = "";
	$cond_consumidor = " 1=1 ";
	$cond_revenda    = " 1=1 ";

	# HD 37901
	# Para a nks, OS consertada não deve ser contada como aberta.
	if ($login_fabrica == 45){
		$condnks = "AND tbl_os.excluida = 'f' AND tbl_os.data_conserto IS NULL";
	}

	// HD 12796
	if(strlen($produto) > 0) 	$cond_produto    = " AND tbl_produto.produto = $produto ";
	if (strlen ($marca)    > 0) $cond_marca = " AND tbl_produto.marca = $marca ";
	if (strlen($estado)>0){
		$tmp_estado = "select
							tbl_posto.posto
						INTO TEMP temp_postoestado
						from tbl_posto
						join tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
							AND tbl_posto_fabrica.fabrica = $login_fabrica
						where tbl_posto.estado = '$estado';

						create index temp_postoestado_estado ON temp_postoestado(posto);";
		$cond_estado = "JOIN temp_postoestado ON tbl_os.posto = temp_postoestado.posto ";
	}

	if (strlen($linha)>0) $cond_linha = "tbl_produto.linha = $linha";
	else                  $cond_linha = "1=1";


	if (strlen($os_tipo)>0) $cond_os_tipo = "tbl_os.consumidor_revenda = '$os_tipo'";
	else                    $cond_os_tipo = "1=1";

	if (strlen($consumidor_nome)>0) $cond_consumidor = " tbl_os.consumidor_nome ILIKE '%$consumidor_nome%'";


	if (strlen($revenda_cnpj)>0){
		$sql_temp = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj';";
		//echo "<br>$sql_temp";
		$res = pg_exec($con,$sql_temp);
		$revenda = trim(pg_result($res,0,0));
		$cond_revenda = "tbl_os.revenda = $revenda";
	}

	if(strlen($situacao)>0){
		$tmp_sem_peca = "SELECT DISTINCT temp_os_aberta.os
				INTO TEMP temp_os_com_peca
				FROM temp_os_aberta
				JOIN tbl_os_produto ON temp_os_aberta.os         = tbl_os_produto.os
				JOIN tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto ;
				CREATE INDEX temp_os_com_peca_os ON temp_os_com_peca(os);";
		if($situacao=='s') $cond_sem_peca = " AND tbl_os.os NOT IN (SELECT OS FROM temp_os_com_peca) ";
		if($situacao=='c') $cond_sem_peca = " AND tbl_os.os     IN (SELECT OS FROM temp_os_com_peca) ";
		if($situacao=='a') $cond_sem_peca = " AND 1=1 ";
	}


	if(strlen($intervencao)>0 AND $login_fabrica==3){
		$join_intervencao = "LEFT JOIN tbl_os_status using(os)";
		$temp_intervencao = " AND tbl_os.os NOT IN (SELECT os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65)) ";
	}

	if(strlen($conserto)>0){
		if($conserto=='t') $cond_data_conserto = " 1=1 ";
		if($conserto=='c') $cond_data_conserto = " tbl_os.data_conserto is not null ";
		if($conserto=='n') $cond_data_conserto = " tbl_os.data_conserto is null ";
	}else{
		$cond_data_conserto = " 1=1 ";
	}


	$temp_cancelada_90_dias = " ";
	if(strlen($cancelada_90_dias)>0 AND $login_fabrica==3){
		$temp_cancelada_90_dias = " 
						AND     tbl_os.os NOT IN (
							SELECT interv_serie.os
								FROM (
										SELECT 
											ultima_serie.os, 
											(
												SELECT status_os 
												FROM tbl_os_status 
												WHERE tbl_os_status.os = ultima_serie.os 
													AND status_os IN (120, 122, 123, 126)
												ORDER BY data DESC 
												LIMIT 1
											) AS ultimo_serie_status
										FROM (
											SELECT DISTINCT os 
											FROM tbl_os_status 
											WHERE status_os IN (120, 122, 123, 126)
										) ultima_serie
									) interv_serie
								WHERE interv_serie.ultimo_serie_status IN (126)
							) ";
		$temp_cancelada_90_dias = " 
						AND tbl_os.os NOT IN (
								SELECT os 
								FROM tbl_os_status 
								WHERE tbl_os.os = tbl_os_status.os AND status_os IN (126)
						) ";

	}


	$sql_temp = "   
			$tmp_estado
			SELECT tbL_os.os
			INTO TEMP TABLE temp_os_aberta
			FROM tbl_os
			$join_intervencao
			$cond_estado
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.finalizada IS NULL
			AND   tbl_os.excluida   IS NOT TRUE
			AND   tbl_os.posto <> 6359
			AND   $cond_posto
			AND   data_abertura < current_date - INTERVAL '$qtde_dias days'
			$temp_intervencao
			$temp_cancelada_90_dias
			;
			CREATE INDEX temp_os_aberta_os ON temp_os_aberta(os);

			$tmp_sem_peca

			";

	$sql = "
			$sql_temp

			SELECT DISTINCT
			tbl_marca.nome as marca_nome,
			tbl_os.os,
			tbl_os.data_conserto,
			tbl_os.sua_os as sua_os,
			tbl_os.data_abertura,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') as abertura,
			tbl_produto.referencia||' - '||tbl_produto.descricao as produto,
			tbl_os.serie,
			CASE WHEN tbl_os.consumidor_revenda = 'R' THEN 'Revenda' ELSE 'Consumidor' END AS consumidor_revenda,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome AS nome_posto,
			tbl_posto_fabrica.contato_estado,
			tbl_posto.fone,
			tbl_posto_fabrica.contato_email
		INTO TEMP TABLE temp_os_aberta_resultado
		FROM tbl_os
		JOIN temp_os_aberta    ON tbl_os.os               = temp_os_aberta.os
		JOIN tbl_produto       USING (produto)
		LEFT JOIN tbl_marca    ON tbl_marca.marca         = tbl_produto.marca
		JOIN tbl_posto         ON tbl_os.posto            = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		$cond_estado
		WHERE tbl_os.fabrica = $login_fabrica
		AND   $cond_posto
		AND   $cond_linha
		AND   $cond_os_tipo
		AND   $cond_consumidor
		AND   $cond_data_conserto
		AND   $cond_revenda $cond_sem_peca
		$cond_marca
		$cond_produto
		$condnks";
//echo nl2br($sql);
//exit;
	$res = pg_exec($con,$sql);

	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

	//cria o indice para poder ordenar
	$sql = "CREATE INDEX temp_os_aberta_resultado_data_abertura ON temp_os_aberta_resultado(data_abertura);
			SELECT * FROM temp_os_aberta_resultado ORDER BY data_abertura;";

	$res = pg_exec($con,$sql);
	$numero_registros = pg_numrows($res);
	$conteudo = "";

	#HD 47918
	$mostrar_relatorio = 'sim';
	/*if ($numero_registros > 1000){
		$mostrar_relatorio = 'nao';
	}*/

	$data = date("Y-m-d").".".date("H-i-s");

	$arquivo_nome     = "relatorio-os-aberta-$login_fabrica.$login_admin.".$formato_arquivo;
	$path             = "/www/assist/www/admin/xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen ($arquivo_completo_tmp,"w");

	if ($formato_arquivo!='CSV'){
		fputs ($fp,"<html>");
		fputs ($fp,"<body>");
	}


	echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome'>Clique aqui para fazer download do relatório em  ".strtoupper($formato_arquivo)." </a></p>";

	if ($numero_registros > 0) {
		$conteudo = "<table width='95%' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";

		$conteudo .= "<tr class='Titulo'>";
		$conteudo .= "<td colspan='11'>RELAÇÃO DE OS</td>";
		$conteudo .= "</tr>";
		$conteudo .= "<tr class='Titulo'>";

		if ($login_fabrica==3){
			$conteudo .= "<td align='center'>MARCA</td>";
			$conteudo .= "<td align='center'><acronym title='Revenda ou Consumidor'>R/C</acronym></td>";
		}
		$conteudo .= "<td align='center'>OS</td>";
		$conteudo .= "<td align='center'>ABERTURA</td>";
		$conteudo .= "<td>PRODUTO</td>";
		$conteudo .= "<td>SÉRIE</td>";
		if (strlen($posto)==0) {
			$conteudo .= "<td>CÓDIGO POSTO</td>";
			$conteudo .= "<td>NOME POSTO</td>";
		}
		$conteudo .= "<td>ESTADO</td>";
		$conteudo .= "<td>FONE</td>";
		$conteudo .= "<td>EMAIL</td>";
		$conteudo .= "</tr>";

		#HD 47918
		if ($mostrar_relatorio != 'nao'){
			echo $conteudo;
		}

		if ($formato_arquivo=='CSV'){
			$conteudo = "";
			if ($login_fabrica==3){
				$conteudo = "MARCA;";
				$conteudo = "R/C;";
			}
			$conteudo .= "OS;ABERTURA;PRODUTO;SÉRIE;CODIGO POSTO;NOME POSTO;ESTADO;FONE;EMAIL \n";
		}
		fputs ($fp,$conteudo);

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$marca_nome    = trim(pg_result($res,$i,marca_nome));
			$os            = trim(pg_result($res,$i,os));
			$sua_os        = trim(pg_result($res,$i,sua_os));
			$data_abertura = trim(pg_result($res,$i,abertura));
			$produto       = substr(trim(pg_result($res,$i,produto)),0,40);
			$serie         = trim(pg_result($res,$i,serie));
			$consumidor_revenda= trim(pg_result($res,$i,consumidor_revenda));
			$codigo_posto  = trim(pg_result($res,$i,codigo_posto));
			$nome_posto    = substr(trim(pg_result($res,$i,nome_posto)),0,40);
			$contato_estado= trim(pg_result($res,$i,contato_estado));
			$fone          = trim(pg_result($res,$i,fone));
			$contato_email = trim(pg_result($res,$i,contato_email));

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			#-------- Desconsidera ou marca em vermelho OS Excluidas ------
			$sql = "SELECT * FROM tbl_os_excluida WHERE os = $os";
			$resX = pg_exec ($con,$sql);
			$dica = "";
			if (pg_numrows ($resX) > 0) {
				$cor = "#FF3300";
				$dica = "OS excluída";
				if ($excluidas == 't') {
					continue ;
				}
			}

			$conteudo  = "<tr class='Conteudo' bgcolor='$cor' title='$dica'>";
			if ($login_fabrica==3){
				$conteudo .= "<td>".$marca_nome."</td>";
				$conteudo .= "<td>".$consumidor_revenda."</td>";
			}
			$conteudo .= "<td>".$sua_os."</td>";
			$conteudo .= "<td>".$data_abertura."</td>";
			$conteudo .= "<td>".$produto      ."</td>";
			$conteudo .= "<td>".$serie        ."</td>";
			if (strlen($posto)==0) {
				$conteudo .= "<td>".$codigo_posto."</td>";
				$conteudo .= "<td>".$nome_posto  ."</td>";
			}
			$conteudo .= "<td>".$contato_estado ."</td>";
			$conteudo .= "<td>".$fone           ."</td>";
			$conteudo .= "<td>".$contato_email  ."</td>";
			$conteudo .= "</tr>";

			#HD 47918
			if ($mostrar_relatorio != 'nao'){
				echo $conteudo;
			}

			if ($formato_arquivo=='CSV'){
				$conteudo = "";
				if ($login_fabrica==3){
					$conteudo = $marca_nome.";";
					$conteudo = $consumidor_revenda.";";
				}
				$conteudo .= $sua_os.";".$data_abertura.";".$produto.";".$serie.";".$codigo_posto.";".$nome_posto.";".$contato_estado.";".$fone.";".$contato_email.";\n";
			}
			fputs ($fp,$conteudo);
		}
		$conteudo  = "</table>";
		$conteudo .= "<BR><CENTER>".pg_numrows($res)." Registros encontrados</CENTER>";

		#HD 47918
		if ($mostrar_relatorio != 'nao'){
			echo $conteudo;
		}
		if ($formato_arquivo=='CSV'){
			$conteudo = "";
		}

		fputs ($fp,$conteudo);

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		}
		fclose ($fp);
		flush();

		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		echo "<br>";
	} else {
		echo "<table border='0' cellpadding='2' cellspacing='0'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>Não foram encontrados registros com os parâmetros informados/digitados!!!</b></font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	echo "<script language='javascript'>";
	echo "document.getElementById('msg_carregando').style.visibility='hidden';";
	echo "</script>";
}

include "rodape.php";
?>
