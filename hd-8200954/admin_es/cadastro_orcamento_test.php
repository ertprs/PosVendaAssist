<?include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

include_once('../../telecontrol/www/trad_site/fn_ttext.php');
$a_trad_orc = array (
	"informe_o_produto" => array (
		"pt-br" => "Informe o produto",
		"es"    => "Especifique el producto",
		"en"    => "Inform which product",
	),

);
echo "$Idioma: $sistema_lingua<br>";
$btn_acao             = $_POST['btn_acao'];
$consumidor_nome      = $_POST['consumidor_nome'];
$consumidor_fone      = $_POST['consumidor_fone'];
$consumidor_email     = $_POST['consumidor_email'];
/*$os                 = $_POST['os'];*/
$produto_referencia   = $_POST['produto_referencia'];
$servico_realizado    = $_POST['servico_realizado']; #HD 198948
$abertura             = $_POST['abertura'];
$fechamento           = $_POST['fechamento'];
$orcamento_envio      = $_POST['orcamento_envio'];
$orcamento_aprovacao  = $_POST['orcamento_aprovacao'];
$conserto             = $_POST['conserto'];
$orcamento_aprovado   = $_POST['orcamento_aprovado'];

if($btn_acao=="gravar"){
	if(strlen($produto_referencia)==0) $msg_erro = ttext($a_trad_orc, "informe_o_produto",strtolower($sistema_lingua));

	if(strlen($produto_referencia)>0){
		$sqlP = "SELECT produto
				 FROM tbl_produto
				 JOIN tbl_linha USING(linha)
				 WHERE referencia = '$produto_referencia'
				 AND   fabrica    = $login_fabrica";
		$resP = pg_exec($con,$sqlP);
		if(pg_numrows($resP)>0) $produto = pg_result($resP,0,produto);
	}

	if(strlen($servico_realizado)==0){
		if($sistema_lingua) $xservico_realizado = "informe o Defeito";
		else                $xservico_realizado = "Especifique el defecto";
	}else{
		$xservico_realizado = $servico_realizado;
	}

	if(strlen($abertura)==0){
		if($sistema_lingua) $msg_erro = "informe a data de abertura";
		else                $msg_erro = "informe a data de abertura";
	}else{
		$xabertura = fnc_formata_data_hora_pg(trim($abertura));
	}

	if(strlen($orcamento_envio)==0)     $xorcamento_envio = "null";
	else                                $xorcamento_envio = fnc_formata_data_hora_pg(trim($orcamento_envio));

	if(strlen($orcamento_aprovacao)==0) $xorcamento_aprovacao = "null";
	else                                $xorcamento_aprovacao = fnc_formata_data_hora_pg(trim($orcamento_aprovacao));

	if($orcamento_aprovado=="t")        $xorcamento_aprovado = "t";
	else                                $xorcamento_aprovado = "f";

	if(strlen($fechamento)==0)          $xfechamento = "null";
	else                                $xfechamento = fnc_formata_data_hora_pg(trim($fechamento));

	if(strlen($conserto)==0)            $xconserto = "null";
	else                                $xconserto = fnc_formata_data_hora_pg(trim($conserto));

	if(strlen($consumidor_nome)==0)     $xconsumidor_nome = "null";
	else                                $xconsumidor_nome = "'" . $consumidor_nome . "'";

	if(strlen($consumidor_fone)==0)     $xconsumidor_fone = "null";
	else                                $xconsumidor_fone = "'" . $consumidor_fone . "'";

	if(strlen($consumidor_email)==0)    $xconsumidor_email = "null";
	else                                $xconsumidor_email = "'" . $consumidor_email . "'";

	if(strlen($xfechamento)>0 AND $xfechamento<>"null"){
		$sql = "SELECT $xfechamento > CURRENT_TIMESTAMP AS data_maior";
		$res      = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro)==0){
			$data_maior = pg_result($res,0,data_maior);

			if ($data_maior == 't'){
				if($sistema_lingua) $msg_erro = "A Data de fechamento no pode ser maior que a data atual";
				else                $msg_erro = "A Data de fechamento no pode ser maior que a data atual";
			}
		}
	}

	if(strlen($msg_erro)==0){
		$sql = "INSERT INTO tbl_os_orcamento(
								posto               ,
								produto             ,
								servico_realizado   ,
								abertura            ,
								orcamento_envio     ,
								orcamento_aprovacao ,
								orcamento_aprovado  ,
								conserto            ,
								fechamento          ,
								consumidor_nome     ,
								consumidor_fone     ,
								consumidor_email
							)VALUES(
								$login_posto          ,
								$produto              ,
								$xservico_realizado   ,
								$xabertura            ,
								$xorcamento_envio     ,
								$xorcamento_aprovacao ,
								'$xorcamento_aprovado',
								$xconserto            ,
								$xfechamento          ,
								$xconsumidor_nome     ,
								$xconsumidor_fone     ,
								$xconsumidor_email
							)";
		#echo nl2br($sql);
		$res      = @pg_query ($con,$sql) ;
		$msg_erro = pg_errormessage($con);

		if(strpos($msg_erro, "out of range")){
			if($sistema_lingua) $msg_erro = "A hora esta incorreta";
			else                $msg_erro = "A hora esta incorreta";
		}
		if(strpos($msg_erro, "data_futura_fechamento")){
			if($sistema_lingua) $msg_erro = "A data do fechamento não pode ser menor que a data do conserto";
			else                $msg_erro = "A data do fechamento não pode ser menor que a data do conserto";
		}
		if(strpos($msg_erro, "data_futura_orcamento_envio")){
			if($sistema_lingua) $msg_erro = "A data do envio não pode ser menor que a data de abertura";
			else                $msg_erro = "A data do envio não pode ser menor que a data de abertura";
		}
		if(strpos($msg_erro, "data_futura_orcamento_aprovacao")){
			if($sistema_lingua) $msg_erro = "A data do envio não pode ser maior que a data de aprovação";
			else                $msg_erro = "A data do envio não pode ser maior que a data de aprovação";
		}
		if(strpos($msg_erro, "data_futura_conserto")){
			if($sistema_lingua) $msg_erro = "A data do conserto não pode ser menor que a data de aprovação";
			else                $msg_erro = "A data do conserto não pode ser menor que a data de aprovação";
		}
		if(strlen($msg_erro)==0){
			header ("Location: cadastro_orcamento.php");
			exit;
		}
	}
}

if($sistema_lingua){
	if($sistema_lingua) $title = "Telecontrol - Servicio Tecnico - OS Fora de Garantia";
	else                $title = "Telecontrol - Servicio Tecnico - OS Fora de Garantia";
}else{
	if($sistema_lingua) $title = "Telecontrol - Assistência Tecnica - OS Fora de Garantia";
	else                $title = "Telecontrol - Assistência Tecnica - OS Fora de Garantia";
}

$layout_menu = 'os';
include "cabecalho.php";
?>

<!-- JQuery -->
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<!-- Ajax TULIO -->
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<? include "javascript_pesquisas.php" ?>
<script type="text/javascript" charset="utf-8">
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.rg			= document.frm_os.consumidor_rg;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
}

function gravaEnvio (orcamento_envio, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?orcamento_envio=' + escape(orcamento_envio) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaAprovacao (orcamento_aprovacao, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?orcamento_aprovacao=' + escape(orcamento_aprovacao) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaConserto (conserto, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?conserto=' + escape(conserto) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaFechamento (fechamento, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?fechamento=' + escape(fechamento) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function gravaAprovado (orcamento_aprovado, os_orcamento, linha) {
	requisicaoHTTP ('GET','os_orcamento_ajax.php?orcamento_aprovado=' + escape(orcamento_aprovado) + '&os_orcamento=' + os_orcamento + '&linha=' + linha + '&<?= $cache_bypass ?>', true , 'retornaCampos');
}

function retornaCampos (campos) {
	if (campos.indexOf ('<erro>') >= 0) {
		campos = campos.substring (campos.indexOf('<erro>')+6,campos.length);
		campos = campos.substring (0,campos.indexOf('</erro>'));
		campos_array = campos.split("|");

		msg_erro = campos_array[0];
		if (msg_erro.indexOf('data_futura_orcamento_aprovacao') > 0) {
			msg_erro = "Data da aprovação do orçamento deve ser superior à data do envio do orçamento";
		}
		if (msg_erro.indexOf('data_futura_orcamento_envio') > 0) {
			msg_erro = "Data do envio do orçamento deve ser posterior a data de abertura";
		}
		if (msg_erro.indexOf('data_futura_conserto') > 0) {
			msg_erro = "Data do conserto deve ser posterior a data de aprovação";
		}
		if (msg_erro.indexOf('data_futura_fechamento') > 0) {
			msg_erro = "Data do fechamento deve ser posterior a data de conserto";
		}
		if (msg_erro.indexOf('out of range') > 0) {
			msg_erro = "A hora está incorreta";
		}
		alert (msg_erro) ;
		linha = campos_array[1] ;
		campo = campos_array[2] ;

		document.getElementById(campo + "_" + linha).val();
		document.getElementById(campo + "_" + linha).focus() ;
	}
}
</script>

<? include "javascript_calendario.php"; ?>

<!-- Formatar DATA -->
<script type="text/javascript" charset="utf-8">
$(function(){
		$("input[@rel='data']").maskedinput("99/99/9999 99:99");
		$("input[@rel='fone']").maskedinput("(99)9999-9999");
});
</script>

<style>
	td{
		font-size: 11px;
		font-family: arial;
	}
	.erro{
		font-size: 12px;
		font-family: arial;
		color: #fff;
		background-color: #f00;
	}
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}

</style>
<?
include "javascript_pesquisas.php";

if(strlen($erro)>0) $msg_erro = $erro;

if(strlen($msg_erro)>0){
	if(strpos($msg_erro, "out of range")){
		if($sistema_lingua) $msg_erro = "A hora esta incorreta";
		else                $msg_erro = "A hora esta incorreta";
	}
	if(strpos($msg_erro, "data_futura_fechamento")){
		if($sistema_lingua) $msg_erro = "A data do fechamento não pode ser menor que a data do conserto";
		else                $msg_erro = "A data do fechamento não pode ser menor que a data do conserto";
	}
	if(strpos($msg_erro, "data_futura_orcamento_envio")){
		if($sistema_lingua) $msg_erro = "A data do envio não pode ser menor que a data de abertura";
		else                $msg_erro = "A data do envio não pode ser menor que a data de abertura";
	}
	if(strpos($msg_erro, "data_futura_orcamento_aprovacao")){
		if($sistema_lingua) $msg_erro = "A data do envio não pode ser maior que a data de aprovação";
		else                $msg_erro = "A data do envio não pode ser maior que a data de aprovação";
	}
	if(strpos($msg_erro, "data_futura_conserto")){
		if($sistema_lingua) $msg_erro = "A data do conserto não pode ser menor que a data de aprovação";
		else                $msg_erro = "A data do conserto não pode ser menor que a data de aprovação";
	}

	echo "<div class='erro'>" . $msg_erro . "</div>";
}
?>

<br>
<form method="POST" name="frm_os" action="<? echo $PHP_SELF; ?>">
	<table width="700" border="0" cellpadding="4" cellspacing="4" align="center">
		<tr>
			<td nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Consumidor Nome"; else echo "Consumidor Nome"; ?>
				</font>
				<br>
				<input name="consumidor_nome" size="30" maxlength="50" value="<?
				echo $consumidor_nome; ?>" type="text" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com o nome do consumidor";else echo "Entre com o nome do consumidor";?>'); " tabindex="0">
				<!-- <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, \"nome\")' style='cursor: pointer'> -->
			</td>
			<td>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Consumidor Fone"; else echo "Consumidor Fone"; ?>
				</font>
				<br>
				<input name="consumidor_fone" size="14" maxlength="14" value="<?
				echo $consumidor_fone; ?>" type="text" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com o telefone do consumidor";else echo "Entre com o telefone do consumidor";?>'); " tabindex="0" rel="fone">
			</td>
			<td colspan="3">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Consumidor Email"; else echo "Consumidor Email"; ?>
				</font>
				<br>
				<input name="consumidor_email" size="30" maxlength="50" value="<?
				echo $consumidor_email; ?>" type="text" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com o email do consumidor";else echo "Entre com o email do consumidor";?>'); " tabindex="0">
			</td>
		</tr>
		<tr>
			<?/*<td valign="top">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>OS</font><br>
				<input type="text" name="os" value="<? echo $os; ?>" size="10">
			</td>*/?>
			<td valign="top" nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Produto Referência"; else echo "Produto Referência"; ?>
				<br>
				<input type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
				&nbsp;
				<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao,'referencia',document.frm_os.voltagem)">
			</td>
			<td valign="top" nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Produto Descrição"; else echo "Produto Descrição"; ?>
				<br>
				<input  type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
				&nbsp;
				<img src='imagens/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia, document.frm_os.produto_descricao,'descricao',document.frm_os.voltagem)">
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua) echo "Voltaje"; else echo "Voltagem";?></font>
				<br>
				<input  type="text" name="voltagem" id="voltagem" size="5" value="<? echo $voltagem ?>">
			</td>
			<td valign="top" colspan="2">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Defeito"; else echo "Defeito"; ?>
				</font>
				<br>
				<select name="servico_realizado">
					<option></option>
					<?
					$sql = "SELECT * FROM tbl_servico_realizado
							WHERE tbl_servico_realizado.fabrica = $login_fabrica ";
					$res = pg_query ($con,$sql) ;

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
						$descricao_d = pg_fetch_result ($res,$x,descricao);
						//--=== Tradução para outras linguas ===== Raphael HD:1212
						$sql_idioma = "SELECT * FROM tbl_servico_realizado_idioma WHERE servico_realizado = ".pg_fetch_result ($res,$x,servico_realizado)." AND upper(idioma) = '$sistema_lingua'";
						$res_idioma = @pg_query($con,$sql_idioma);
						if (@pg_num_rows($res_idioma) >0) {
							$descricao_d  = trim(@pg_fetch_result($res_idioma,0,descricao));
						}
						//--=== Tradução para outras linguas ======================
						echo "<option ";
						if ($servico_realizado == pg_fetch_result ($res,$x,servico_realizado)) echo " selected ";
						echo " value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
						echo $descricao_d ;
						echo "</option>";
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Fecha de abertura"; else echo "Data Abertura"; ?>
				</font>
				<br>
				<input name="abertura" rel='data' size='18' maxlength='18' value="<?
				echo $abertura; ?>" type="text" rel="data"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Ingrese com la fecha de abertura de la OS";else echo "Entre com a Data da Abertura da OS.";?>'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<td valign="top">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Envio do Orçamento"; else echo "Envio do Orçamento"; ?>
				</font>
				<br>
				<input name="orcamento_envio" rel='data' size='18' maxlength='18' value="<?
				echo $orcamento_envio; ?>" type="text" rel="data"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com a Data do Envio do Orçamento.";else echo "Entre com a Data do Envio do Orçamento.";?>'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<td valign="top">
				<table border="0" cellspacing="0" cellpadding="1">
					<tr>
						<td valign="top">
							<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
							<? if($sistema_lingua) echo "Aprovação"; else echo "Aprovação"; ?>
							</font>
							<br>
							<input name="orcamento_aprovacao" rel='data' size='18' maxlength='18' value="<?
							echo $orcamento_aprovacao; ?>" type="text" rel="data"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com a Data de Aprovao.";else echo "Entre com a Data de Aprovao.";?>'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
						</td>
						<td align="center" valign="top">
							&nbsp;&nbsp;
							<input type="checkbox" name="orcamento_aprovado" value="t" <? if($orcamento_aprovado=="t") echo "checked"; ?>>
							<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
							<? if($sistema_lingua) echo "Orçamento Aprovado"; else echo "Orçamento Aprovado"; ?>
							</font>
						</td>
					</tr>
				</table>
			</td>
			<td valign="top">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Data Conserto"; else echo "Data Conserto"; ?>
				</font>
				<br>
				<input name="conserto" rel='data' size='18' maxlength='18' value="<?
				echo $conserto; ?>" type="text" rel="data"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com a Data de Conserto.";else echo "Entre com a Data de Conserto.";?>'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<td valign="top">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
				<? if($sistema_lingua) echo "Data Fechamento"; else echo "Data Fechamento"; ?>
				</font>
				<br>
				<input name="fechamento" rel='data' size='18' maxlength='18' value="<?
				echo $fechamento; ?>" type="text" rel="data" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Entre com a Data de Fechamento.";else echo "Entre com a Data de Fechamento.";?>'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
		</tr>
		<tr>
			<td colspan="5" align="center">
				<input type="hidden" name="btn_acao" value="">
				<br>
				<?if ($sistema_lingua=='ES') {?>
					<img src='imagens/btn_guardar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde ') }" ALT="Guardar itenes de la orden de servicio" border='0' style="cursor:pointer;">
				<?} else {?>
					<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde ') }" ALT="Gravar itens da Ordem de Servio" border='0' style="cursor:pointer;">
			<? } ?>
			</td>
		</tr>
	</table>
</form>

<?
########## CONSULTA ORAMENTO ###########
$sql = "SELECT  os_orcamento        ,
				posto               ,
				produto             ,
				servico_realizado   ,
				abertura            ,
				orcamento_envio     ,
				orcamento_aprovacao ,
				orcamento_aprovado  ,
				conserto            ,
				fechamento          ,
				consumidor_nome     ,
				consumidor_fone     ,
				consumidor_email
		FROM  tbl_os_orcamento
		WHERE tbl_os_orcamento.posto = $login_posto
		ORDER BY os_orcamento DESC";
$res = pg_exec($con,$sql);

if(pg_numrows($res)>0){
	echo "<br>";
	echo "<table width='100%' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr class='Titulo'>";
			echo "<td nowrap>";
				if($sistema_lingua) "Consumidor Nome";
				else                "Consumidor Nome";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Consumidor Fone";
				else                echo "Consumidor Fone";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) "Consumidor Email";
				else                "Consumidor Email";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) "Produto";
				else                "Produto";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Defeito";
				else                echo "Defeito";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) "Data Abertura";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Envio do Orçamento";
				else                echo "Envío de presupuesto";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Data Aprovação";
				else                echo "Data Aprovação";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Orçamento Aprovado";
				else                echo "Orçamento Aprovado";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Data Conserto";
				else                echo "Data Conserto";
			echo "</td>";
			echo "<td nowrap>";
				if($sistema_lingua) echo "Data Fechamento";
				else                echo "Data Fechamento";
			echo "</td>";
		echo "</tr>";
		for($i=0; $i<pg_numrows($res); $i++){
			$os_orcamento        = pg_result($res,$i,os_orcamento);
			$produto             = pg_result($res,$i,produto);
			$servico_realizado   = pg_result($res,$i,servico_realizado);
			$abertura            = mostra_data_hora(pg_result($res,$i,abertura));
			$orcamento_envio     = mostra_data_hora(pg_result($res,$i,orcamento_envio));
			$orcamento_aprovacao = mostra_data_hora(pg_result($res,$i,orcamento_aprovacao));
			$orcamento_aprovado  = pg_result($res,$i,orcamento_aprovado);
			$conserto            = mostra_data_hora(pg_result($res,$i,conserto));
			$fechamento          = mostra_data_hora(pg_result($res,$i,fechamento));
			$consumidor_nome     = pg_result($res,$i,consumidor_nome);
			$consumidor_fone     = pg_result($res,$i,consumidor_fone);
			$consumidor_email    = pg_result($res,$i,consumidor_email);

			if($i%2==0) $cor = "#E8E8E8";
			else        $cor = "#FFFFFF";

			if(strlen($produto)>0){
				$sqlP = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
				$resP = pg_exec($con,$sqlP);

				if(pg_numrows($resP)>0){
					$referencia_produto = pg_result($resP,0,referencia);
					$descricao_produto  = pg_result($resP,0,descricao);
				}
			}

			$descricao_defeito = " ";
			if(strlen($servico_realizado)>0){
				$sqlDC = "SELECT descricao FROM tbl_servico_realizado WHERE servico_realizado = $servico_realizado";
				$resDC = pg_exec($con,$sqlDC);

				if(pg_numrows($resDC)>0){
					$descricao_defeito = pg_result($resDC,0,descricao);
				}
			}

			if($orcamento_aprovado=="t") $orcamento_aprovado = "t";
			else                         $orcamento_aprovado = "f";

			echo "<tr bgcolor='$cor'>";
				echo "<td nowrap>$consumidor_nome</td>";
				echo "<td nowrap>$consumidor_fone</td>";
				echo "<td nowrap>$consumidor_email</td>";
				echo "<td nowrap>$referencia_produto - $descricao_produto</td>";
				echo "<td nowrap>$descricao_defeito</td>";
				echo "<td nowrap>$abertura</td>";
				echo "<td nowrap><input type='text' name='orcamento_envio_$i' value='$orcamento_envio' size='18' maxlength='18' rel='data' onblur='gravaEnvio(this.value,$os_orcamento,$i)'></td>";
				echo "<td nowrap><input type='text' name='orcamento_aprovacao_$i' value='$orcamento_aprovacao' size='18' maxlength='18' rel='data' onblur='gravaAprovacao(this.value,$os_orcamento,$i)'></td>";
				echo "<td nowrap align='center'>";
				echo "<input type='checkbox' name='orcamento_aprovado_$i' value='t' onClick='gravaAprovado(this.value,$os_orcamento,$i)'";
				if($orcamento_aprovado=="t") echo " checked";
				echo ">";
				echo "</td>";
				echo "<td nowrap><input type='text' name='conserto_$i' value='$conserto' size='18' maxlength='18' rel='data' onblur='gravaConserto(this.value,$os_orcamento,$i)'></td>";
				echo "<td nowrap><input type='text' name='fechamento_$i' value='$fechamento' size='18' maxlength='18' rel='data' onblur='gravaFechamento(this.value,$os_orcamento,$i)'></td>";
			echo "</tr>";
		}
	echo "</table>";
	echo "<br>";
}




?>

<? include "rodape.php"; ?>