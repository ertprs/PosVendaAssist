<?php
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

		if (empty($_POST['data_inicial']) or empty($_POST['data_final'])) {
			$msg_erro .= 'Escolha um período de datas para gerar o relatório.<br>';
		}

		if (!empty($_POST["data_inicial"]) && !empty($_POST["data_final"])) {
			$data_inicial = $_POST["data_inicial"];
			$data_final   = $_POST["data_final"];

			list($diDia, $diMes, $diAno) = explode("/", $data_inicial);
			list($dfDia, $dfMes, $dfAno) = explode("/", $data_final);

			if (!checkdate($diMes, $diDia, $diAno) || !checkdate($dfMes, $dfDia, $dfAno)) {
				$msg_erro .= "Data inválida <br />";
			} else if (strtotime("{$diAno}-{$diMes}-{$diDia}") > strtotime("{$dfAno}-{$dfMes}-{$dfDia}")) {
				$msg_erro .= "Data inicial não pode ser maior que data final <br />";
			} else if (strtotime("{$dfAno}-{$dfMes}-{$dfDia} -18 Months") > strtotime("{$diAno}-{$diMes}-{$diDia}")) {
				$msg_erro .= "O intervalo entre as datas não pode ser maior que 18 meses <br />";
			} else {
				$query_where_data = " AND tbl_os.data_abertura BETWEEN '{$diAno}-{$diMes}-{$diDia} 00:00:00' AND '{$dfAno}-{$dfMes}-{$dfDia} 23:59:59' ";
				$aux_data_inicial = "{$diAno}-{$diMes}-{$diDia}";
				$aux_data_final   = "{$dfAno}-{$dfMes}-{$dfDia}";
			}
		}

		if (strlen(trim($_POST["codigo_posto"])) > 0) $codigo_posto = trim($_POST["codigo_posto"]);
		if (strlen(trim($_GET["codigo_posto"])) > 0)  $codigo_posto = trim($_GET["codigo_posto"]);

		if (strlen(trim($_POST["estado"])) > 0) $estado = trim($_POST["estado"]);
		if (strlen(trim($_GET["estado"])) > 0)  $estado = trim($_GET["estado"]);

		if (strlen(trim($_POST["qtde_dias"])) > 0) $qtde_dias = trim($_POST["qtde_dias"]);
		if (strlen(trim($_GET["qtde_dias"])) > 0)  $qtde_dias = trim($_GET["qtde_dias"]);

        if (strlen(trim($_POST["marca"])) > 0) $marca = trim($_POST["marca"]);
        if (strlen(trim($_GET["marca"])) > 0)  $marca = trim($_GET["marca"]);

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

		if (strlen(trim($_POST["cancelada_45_dias"])) > 0) $cancelada_45_dias = trim($_POST["cancelada_45_dias"]);
		if (strlen(trim($_GET["cancelada_45_dias"])) > 0)  $cancelada_45_dias = trim($_GET["cancelada_45_dias"]);

		if (strlen(trim($_POST["cancelada_manual"])) > 0) $cancelada_manual = trim($_POST["cancelada_manual"]);
		if (strlen(trim($_GET["cancelada_manual"])) > 0)  $cancelada_manual = trim($_GET["cancelada_manual"]);


		if (strlen($codigo_posto) > 0) {
			$sqlp = "SELECT posto
					 FROM tbl_posto_fabrica
					 WHERE fabrica = $login_fabrica
					 AND codigo_posto = '$codigo_posto'";
			$resp = pg_query($con, $sqlp);

			if (pg_num_rows($resp) > 0) {
				$posto = pg_fetch_result($resp, 0, 0);
			} else {
				$msg_erro .= "Posto informado não encontrado.<br />";
			}
		}

		if (strlen($codigo_posto) == 0 and strlen($estado) == 0 and $agendar == 'NAO') {
			$msg_erro .= "Para pesquisar deve ser informado posto ou estado. <br />";
		}

		if ((!is_numeric($qtde_dias) or strlen($qtde_dias)==0) and $agendar=='NAO') {
			$msg_erro .= "Parâmetro quantidade de dias inválido.<br />";
		}

        if ($login_fabrica != 3) {
            if ($qtde_dias<10) {
                if ($linha<>528) {
                    //HD 236265: Arrumei a mensagem
                    $msg_erro .= "Selecionar a quantidade de dias: NO MÍNIMO 10 DIAS<br />";
                }
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
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) <> 1) {
				$msg_erro .= "Produto $produto_referencia não encontrado. Utilize a lupa para pesquisar. <br />";
			} else {
				$produto=pg_fetch_result($res,0,produto);
			}
		}
	}
}else{
	$qtde_dias = 0 ;
}

$layout_menu = "auditoria";
if($login_fabrica==45 )$layout_menu="callcenter";
$title = "RELAÇÃO DE ORDENS DE SERVIÇO EM ABERTO";
include "cabecalho.php";

?>
<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;

}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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

    } else {
        alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
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
        }
    } else {
        alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
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
        } else {
            alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
        }
    }
}

</script>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
	<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	<script src="plugins/jquery.mask.js"></script>
	<script>

	$(function() {
		$("#data_inicial, #data_final").datepicker({ maxDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	});

	</script>
<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
#	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
#	include "gera_relatorio_pararelo_verifica.php";
}
?>

<br />
<div style='width:700px; padding: 5px 0px' class='texto_avulso'>
	Este Relatório considera a Data de Digitação da OS<br><br>
	<strong>
		É obrigatório informar um período de datas para a extração do relatório.<br>
		Este período não pode ser superior a 18 meses.
	</strong>
</div>
<br />
<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">
<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if (strlen($msg_erro) > 0) { ?>
		<tr class="msg_erro">
			<td><?echo $msg_erro?></td>
		</tr>
	<? } ?>
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa </td>
	</tr>

	<tr>
		<td>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
				<?if($login_fabrica==3 OR $login_fabrica == 15){?>
				  <TR align='right'>
					  <TD>Marca&nbsp;</TD>

					  <TD align='left'>	<!-- começa aqui -->
							<?
							$sql = "SELECT  *
									FROM    tbl_marca
									WHERE   tbl_marca.fabrica = $login_fabrica
									ORDER BY tbl_marca.nome;";
							$res = pg_query ($con,$sql);

							if (pg_num_rows($res) > 0) {
								echo "<select name='marca' class='frm'>\n";
								echo "<option value=''>ESCOLHA</option>\n";
								for ($x = 0 ; $x < pg_num_rows($res) ; $x++) {
									$aux_marca = trim(pg_fetch_result($res,$x,'marca'));
									$aux_nome  = trim(pg_fetch_result($res,$x,'nome'));

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

					<tr>
						<td style="text-align: right;" >Data Inicial</td>
						<td>
							<input type="text" class="frm" id="data_inicial" name="data_inicial" value="<?=$data_inicial?>" />
						</td>
					</tr>
					<tr>
						<td style="text-align: right;" >Data Final</td>
						<td>
							<input type="text" class="frm" id="data_final" name="data_final" value="<?=$data_final?>" />
						</td>
					</tr>

				<tr width='100%' >
					<td align='right' height='20'>Código Posto&nbsp;</td>
					<td align='left'>
						<input class='frm' type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></A>
					</td>
				</tr>

				<tr>
					<td align='right'>Razão Social&nbsp;</td>
					<td align='left'><input class='frm' type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>

				<tr>
					<td align='right'>Estado&nbsp;</td>
					<td align='left'>
						<select name="estado" size="1" class='frm'>
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
<?php
if ($login_fabrica == 1) {
?>
                <tr>
                    <td style="text-align:right">Marca </td>
                    <td style="text-align:left;">

                        <select name="marca" class="frm">
                            <option value=''>TODAS</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
                                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_GET['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
                        </select>
                    </td>
                </tr>
<?
}
?>
				<tr>
					<td align='right'>Linha:&nbsp;</td>
					<td><?
						$sql = "SELECT  *
								FROM    tbl_linha
								WHERE   tbl_linha.fabrica = $login_fabrica
								ORDER BY tbl_linha.nome;";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) {
							echo "<select name='linha' class='frm'>\n";
							echo "<option value=''>TODAS</option>\n";
							for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
								$aux_linha = trim(pg_fetch_result($res,$x,linha));
								$aux_nome  = trim(pg_fetch_result($res,$x,nome));

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
					<td align='right' height='20'>OS Abertas há mais de&nbsp;</td>
					<td align='left'><input type="text" name="qtde_dias" size="3" maxlength="3" value="<?=(strlen($qtde_dias)> 0) ? $qtde_dias : ''?>" class='frm'> dias</td>
				</tr>

				<tr>
					<td align='right'>Tipo&nbsp;</td>
					<td align='left'>
						<select name="os_tipo" size="1" class='frm'>
							<option value=""   <?=(strlen($os_tipo) == 0) ? " selected " : "" ?>>TODOS</option>
							<option value="C" <?=($os_tipo == "C") ? " selected " : "" ?>>CONSUMIDOR</option>
							<option value="R" <?=($os_tipo == "R") ? " selected " : "" ?>>REVENDA</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align='right'>Nome Consumidor</td>
					<td>
						<input class='frm' type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>">
					</td>
				</tr>
				<tr valign="top">
					<td align='right'>CNPJ Revenda</td>
					<td>
						<input class='frm' type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_cnpj, "cnpj")' style='cursor: pointer'>
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
						<input class='frm' type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_relatorio.revenda_nome, "nome")' style='cursor: pointer'>
					</td>
				</tr>
				<?if ($login_fabrica == 11){  //HD 12796?>
				<tr valign="top">
					<td align='right'>Ref. Produto</td>
					<td>
					<input class="Caixa" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
					&nbsp;
					<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
				</tr>
				<tr valign="top">
					<td align='right'>Descrição Produto</td>
					<td>
					<input class='frm' type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
					&nbsp;
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
				</tr>
				<?}
				if ($login_fabrica != 3) {//HD 253966?>
					<!-- HD 236265: Filtro por conserto -->
					<tr>
						<td align='right'>Conserto</td>
						<td >
							<?
							if ($situacao_conserto == "") {
								if ($login_fabrica == 45 || $login_fabrica ==80) {
									$situacao_conserto = 's';
								}
								else {
									$situacao_conserto = 'a';
								}
							}?>
							<input type='radio' name='situacao_conserto' value='s'<?if($situacao_conserto=='s')echo "checked";?>> Não Consertado
							<input type='radio' name='situacao_conserto' value='c'<?if($situacao_conserto=='c')echo "checked";?>> Consertado
							<input type='radio' name='situacao_conserto' value='a'<?if($situacao_conserto=='a')echo "checked";?>> Ambos
						</td>
					</tr><?php
				}?>
				<tr>
					<td align='right'>Situação</td>
					<td >
						<input type='radio' name='situacao' value='c'<?if($situacao=='c')echo "checked";?>> Com peça
						<input type='radio' name='situacao' value='s'<?if($situacao=='s')echo "checked";?>> Sem peça
						<input type='radio' name='situacao' value='a'<?if($situacao=='a')echo "checked";?>> Ambos
					</td>
				</tr><?php
				if ($login_fabrica == 3) {?>
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
						<td colspan='2' align='center'><input type='checkbox' name='cancelada_90_dias' value='t' <? if ($cancelada_90_dias == 't') echo "checked";?> /> Desconsiderar OSs Canceladas (OS aberta a mais 90 dias - Cancelada)</td>
					</tr>
					<tr>
						<td colspan='2' align='center'><input type='checkbox' name='cancelada_45_dias' value='t' <?if ($cancelada_45_dias == 't') echo "checked";?>> Desconsiderar OSs Canceladas (OS aberta a mais 45 dias - Cancelada)</td>
					</tr>
					<tr>
						<td colspan='2' align='center'><input type='checkbox' name='cancelada_manual' value='t' <?if ($cancelada_manual == 't') echo "checked";?>> Desconsiderar OSs Canceladas (Cancelamento manual)</td>
					</tr>
					<?php
				}?>
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
				<tr>
					<td colspan="2	" align="center" ><input type='button' border="0" value="&nbsp;" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" onClick="document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" alt="Preencha as opções e clique aqui para pesquisar"></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php


if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {

	#echo "<span id='msg_carregando'><img src='imagens/carregar2.gif'><font face='Verdana' size='1' color=#FF0000>
	#<BR>Aguarde carregamento.
	#<BR>Devido a grande quantidade de Ordem de Serviço o resultado pode levar alguns minuto para ser exibido.
	#<BR>Aguarde até o término do carregamento.</font></span>";
	flush();

	$cond_posto = " 1=1 ";
	$cond_aux_posto = " 1=1 ";

	if (strlen($posto)>0) {
		$cond_posto = " tbl_os.posto = $posto ";
		$cond_aux_posto = " tmp_os_aberta.posto = $posto ";
	}

	$cond_estado     = "";
	$cond_aux_estado = "";
	$cond_consumidor = " 1=1 ";
	$cond_revenda    = " 1=1 ";

	//HD 236265: Filtro se está consertado ou não. Para a NKS havia um código para que viesse sempre as sem conserto. Coloquei como padrão na seleção do filtro e retirei as linhas de código
	if (strlen($situacao_conserto) > 0) {
		if($situacao_conserto=='s') $cond_conserto = " AND tmp_os_aberta.excluida = 'f' AND tmp_os_aberta.data_conserto IS NULL ";
		if($situacao_conserto=='c') $cond_conserto = " AND tmp_os_aberta.excluida = 'f' AND tmp_os_aberta.data_conserto IS NOT NULL ";
		if($situacao_conserto=='a') $cond_conserto = " AND 1=1 ";
	}

	// HD 12796
	$cond_marca = "1=1";
	$cond_produto = "1=1";
	if (strlen($produto) > 0) 	$cond_produto    = " tbl_produto.produto = $produto ";
	if (strlen ($marca)    > 0) $cond_marca = " tbl_produto.marca = $marca ";

	if (strlen($estado) > 0) {
		$tmp_estado = "select
					tbl_posto.posto
				INTO TEMP temp_postoestado
				from tbl_posto
				join tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
				where tbl_posto.estado = '$estado';

				create index temp_postoestado_estado ON temp_postoestado(posto);";
		$cond_estado = "JOIN temp_postoestado ON tbl_os.posto = temp_postoestado.posto ";
		$cond_aux_estado = "JOIN temp_postoestado ON tmp_os_aberta.posto = temp_postoestado.posto ";

	}

	if (strlen($linha) > 0) $cond_linha = "tbl_produto.linha = $linha";
	else                    $cond_linha = "1=1";


	if (strlen($os_tipo) > 0) $cond_os_tipo = "tmp_os_aberta.consumidor_revenda = '$os_tipo'";
	else                      $cond_os_tipo = "1=1";

	if (strlen($consumidor_nome) > 0) $cond_consumidor = " tmp_os_aberta.consumidor_nome ILIKE '%$consumidor_nome%'";


	if (strlen($revenda_cnpj) > 0) {
		$sql_temp = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$revenda_cnpj';";
		//echo "<br>$sql_temp";
		$res = pg_query($con,$sql_temp);
		$revenda = trim(pg_fetch_result($res,0,0));
		$cond_revenda = " 1=1 ";
		if (strlen($revenda)>0)
			$cond_revenda = "tmp_os_aberta.revenda = $revenda";
	}

	if (strlen($situacao) > 0) {
		if($situacao=='s') $cond_sem_peca = " AND tmp_os_aberta.os_produto IS NULL ";
		if($situacao=='c') $cond_sem_peca = " AND tmp_os_aberta.os_produto IS NOT NULL";
		if($situacao=='a') $cond_sem_peca = " AND 1=1 ";
	}

	if (strlen($intervencao) > 0 AND $login_fabrica == 3) {
		$join_intervencao = "LEFT JOIN tbl_os_status ON tbl_os_status.os = tbl_os.os AND tbl_os_status.fabrica_status=$login_fabrica";
		$temp_intervencao = " AND tbl_os.os NOT IN (SELECT os FROM tbl_os_status WHERE tbl_os_status.fabrica_status=$login_fabrica AND tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65)) ";
	}

	if (strlen($conserto) > 0) {
		if ($conserto=='t') $cond_data_conserto = " 1=1 ";
		if ($conserto=='c') $cond_data_conserto = " tmp_os_aberta.data_conserto is not null ";
		if ($conserto=='n') $cond_data_conserto = " tmp_os_aberta.data_conserto is null ";
	} else {
		$cond_data_conserto = " 1=1 ";
	}

	$temp_cancelada_90_dias = " ";
	if (strlen($cancelada_90_dias) > 0 AND $login_fabrica == 3) {
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
								WHERE interv_serie.ultimo_serie_status = 126
							) ";

		$sql_cancelada_90_dias = "SELECT os
						INTO TEMP tmp_canceladas_90_dias
						FROM tbl_os_status
						WHERE status_os = 126 and fabrica_status=$login_fabrica;

						CREATE INDEX tmp_canceladas_90_dias_os ON tmp_canceladas_90_dias(os);";

		$temp_cancelada_90_dias = "
						AND tbl_os.os NOT IN ( SELECT os
								FROM tmp_canceladas_90_dias) ";
	}

	$temp_cancelada_45_dias = " ";
	if (strlen($cancelada_45_dias) > 0 AND $login_fabrica == 3) {
		$temp_cancelada_45_dias = "
						AND     tbl_os.os NOT IN (
							SELECT interv_serie.os
								FROM (
										SELECT
											ultima_serie.os,
											(
												SELECT status_os
												FROM tbl_os_status
												WHERE tbl_os_status.os = ultima_serie.os
													AND status_os IN (140, 141, 142, 143)
												ORDER BY data DESC
												LIMIT 1
											) AS ultimo_serie_status
										FROM (
											SELECT DISTINCT os
											FROM tbl_os_status
											WHERE status_os IN (140, 141, 142, 143)
										) ultima_serie
									) interv_serie
								WHERE interv_serie.ultimo_serie_status = 143
							) ";

		$sql_cancelada_45_dias="SELECT os
						INTO TEMP tmp_canceladas_45_dias
						FROM tbl_os_status
						WHERE status_os = 143 and fabrica_status=$login_fabrica;

						CREATE INDEX tmp_canceladas_45_dias_os ON tmp_canceladas_45_dias(os);";


		$temp_cancelada_45_dias = "
						AND tbl_os.os NOT IN ( SELECT os
								FROM tmp_canceladas_45_dias ) ";
	}
	$temp_cancelada_manual = " ";
	if (strlen($cancelada_manual) > 0 AND $login_fabrica == 3) {
		$temp_cancelada_manual = "
						AND     tbl_os.os NOT IN (
							SELECT interv_serie.os
								FROM (
										SELECT
											ultima_serie.os,
											(
												SELECT status_os
												FROM tbl_os_status
												WHERE tbl_os_status.os = ultima_serie.os
													AND status_os IN (245, 246, 247)
												ORDER BY data DESC
												LIMIT 1
											) AS ultimo_serie_status
										FROM (
											SELECT DISTINCT os
											FROM tbl_os_status
											WHERE status_os IN (245, 246, 247)
										) ultima_serie
									) interv_serie
								WHERE interv_serie.ultimo_serie_status = 246
							) ";

		$sql_cancelada_manual="SELECT os
						INTO TEMP tmp_cancelada_manual
						FROM tbl_os_status
						WHERE status_os = 246 and fabrica_status=$login_fabrica;

						CREATE INDEX tmp_cancelada_manual_os ON tmp_cancelada_manual(os);";


		$temp_cancelada_manual = "
						AND tbl_os.os NOT IN ( SELECT os
								FROM tmp_cancelada_manual ) ";
	}

	if ($login_fabrica == 3) {
		$sql  =  "$sql_cancelada_90_dias
				$sql_cancelada_45_dias
				$sql_cancelada_manual
";
		$res = pg_query($con,$sql);

		$campos_peca = " , tbl_peca.referencia AS referencia_peca,
			tbl_peca.descricao  AS descricao_peca ";
		$join_peca = "  LEFT JOIN tbl_peca ON ( tbl_os_item.peca = tbl_peca.peca) ";
        $temp_peca = ",  tmp_oa.referencia_peca AS referencia_peca,
                tmp_oa.descricao_peca  AS descricao_peca";
        $temp2_peca = " ,  tmp_os_aberta.referencia_peca AS referencia_peca,
                tmp_os_aberta.descricao_peca  AS descricao_peca ";

	}

	if(!empty($tmp_estado)) {
		$sql = "$tmp_estado";
		#echo nl2br($sql);
		$res = pg_query($con,$sql);
	}
	
	$datas = relatorio_data("$aux_data_inicial","$aux_data_final");			
	foreach($datas as $cont => $data_pesquisa){
		$data_inicial = $data_pesquisa[0];
		$data_final = $data_pesquisa[1];
		$data_final = str_replace(' 23:59:59', '', $data_final);

		if ($cont == 0) {
			$tempTableCreate = "create temp table tmp_oa as";
		} else if ($cont > 0) {
			$tempTableCreate = "INSERT INTO tmp_oa ";
		}
		$sql_os =  "
		$tempTableCreate
		SELECT 
				tbl_os.os,
				tbl_os.data_conserto,
				tbl_os.sua_os,
				tbl_os.data_abertura,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') as abertura,
				tbl_os.serie,tbl_os.posto,
				tbl_os.fabrica,
				tbl_os.consumidor_revenda,
				tbl_os.excluida,
				tbl_os.revenda,
				tbl_os.consumidor_nome,
				tbl_os.consumidor_fone,
				tbl_os.produto,
				tbl_os.status_checkpoint,
				tbl_os.troca_garantia,
				tbl_os.troca_faturada,
				to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') as digitacao_item,
				tbl_os_produto.os_produto
				$campos_peca
		FROM    tbl_os
		$cond_estado
		$join_intervencao
		LEFT JOIN tbl_os_produto ON ( tbl_os.os = tbl_os_produto.os)
		LEFT JOIN tbl_os_item ON ( tbl_os_produto.os_produto = tbl_os_item.os_produto)
		$join_peca
		WHERE   tbl_os.fabrica          = $login_fabrica
		AND     tbl_os.finalizada       IS NULL
		AND     tbl_os.data_fechamento  IS NULL
		AND     tbl_os.os_fechada       IS FALSE
		AND     tbl_os.excluida         IS NOT TRUE
		AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'
		AND     tbl_os.data_abertura < CURRENT_DATE - INTERVAL '$qtde_dias days'
		AND     $cond_posto
		$temp_cancelada_90_dias
		$temp_cancelada_45_dias
		$temp_cancelada_manual ;";

		$res_os = pg_query($con,$sql_os);
	}
 	//echo nl2br($sql_os); 
	$sql_temp = "
		CREATE INDEX tmp_oa_os ON tmp_oa(os);
		CREATE INDEX tmp_oa_produto ON tmp_oa(produto);
		CREATE INDEX tmp_oa_posto ON tmp_oa(posto);

		SELECT  tmp_oa.os,
                tmp_oa.data_conserto,
                tmp_oa.sua_os,
                tmp_oa.data_abertura,
                tmp_oa.abertura,
                tmp_oa.serie,
                tmp_oa.posto,
                tmp_oa.fabrica,
                tmp_oa.consumidor_revenda,
                tmp_oa.excluida,
                tmp_oa.revenda,
                tmp_oa.consumidor_nome,
                tmp_oa.consumidor_fone,
                tmp_oa.troca_garantia,
                tmp_oa.troca_faturada,
                       tmp_oa.digitacao_item,
				tmp_oa.os_produto,
                tbl_status_checkpoint.descricao AS status_checkpoint,
                tbl_marca.nome as marca_nome,
                tbl_produto.referencia||' - '||tbl_produto.descricao as descricao_produto,
                tbl_produto.referencia  AS produto_referencia,
                tbl_produto.descricao   AS produto_descricao,
                tbl_produto.produto,
                tbl_linha.nome          AS produto_linha,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.contato_estado,
                tbl_posto_fabrica.contato_email,
                CASE
                    WHEN tbl_os_excluida.os IS NULL
                    THEN 'f'
                    ELSE 't'
				END AS os_excluida
				$temp_peca
   INTO TEMP    tmp_os_aberta
        FROM    tmp_oa
        JOIN    tbl_produto         	ON  tmp_oa.produto = tbl_produto.produto
                                    	AND tmp_oa.fabrica = tbl_produto.fabrica_i
		JOIN    tbl_linha           	USING(linha)
		JOIN	tbl_status_checkpoint	USING(status_checkpoint)
   LEFT JOIN    tbl_marca           ON  tbl_marca.marca             = tbl_produto.marca
		   LEFT JOIN    tbl_os_excluida     ON  tbl_os_excluida.os          = tmp_oa.os
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tmp_oa.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
                                    AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
                                    AND $cond_linha
        AND     $cond_marca
        AND     $cond_produto;

        CREATE INDEX tmp_os_aberta_os ON tmp_os_aberta(os);

        ";

    if($login_fabrica == 3){
    	$campo_dt_digitacao = ' tmp_os_aberta.digitacao_item,';
    } else {
    	$distinct = ' DISTINCT ';
    }

	$sql = "

        $sql_temp

        SELECT  {$distinct}
                tmp_os_aberta.marca_nome,
                tmp_os_aberta.os,
                to_char(tmp_os_aberta.data_conserto,'DD/MM/YYYY') as data_conserto,
                tmp_os_aberta.sua_os as sua_os,
                tmp_os_aberta.data_abertura,
                to_char(tmp_os_aberta.data_abertura,'DD/MM/YYYY') as abertura,
                tmp_os_aberta.descricao_produto,
                tmp_os_aberta.produto_referencia,
                tmp_os_aberta.produto_descricao,
                tmp_os_aberta.produto_linha,
                tmp_os_aberta.serie,
                CASE
                    WHEN tmp_os_aberta.consumidor_revenda = 'R'
                    THEN 'Revenda'
                    ELSE 'Consumidor'
                END AS consumidor_revenda,
                tmp_os_aberta.codigo_posto,
                tmp_os_aberta.consumidor_nome,
                tmp_os_aberta.consumidor_fone,
                tbl_posto.nome AS nome_posto,
                tmp_os_aberta.contato_estado,
                tbl_posto.fone,
                {$campo_dt_digitacao}       
                tmp_os_aberta.contato_email,
                tmp_os_aberta.os_excluida,
                tmp_os_aberta.troca_garantia,
                tmp_os_aberta.troca_faturada,
                tmp_os_aberta.status_checkpoint,
                (
                    SELECT  DISTINCT
                            tbl_faturamento.nota_fiscal
                    FROM    tbl_faturamento
                    JOIN    tbl_faturamento_item USING(faturamento)
                    WHERE   tbl_faturamento_item.os = tmp_os_aberta.os
                    LIMIT 1
                ) AS nota_fiscal ,
                (
                    SELECT  DISTINCT
                            TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS data_nf
                    FROM    tbl_faturamento
                    JOIN    tbl_faturamento_item USING(faturamento)
                    WHERE   tbl_faturamento_item.os = tmp_os_aberta.os
                    LIMIT 1
                ) AS data_nf
				$temp2_peca
   INTO TEMP    tmp_os_aberta_resultado
        FROM    tmp_os_aberta
		JOIN    tbl_posto         ON tmp_os_aberta.posto    = tbl_posto.posto
		$cond_aux_estado
		WHERE   tmp_os_aberta.fabrica = $login_fabrica
		AND     tmp_os_aberta.posto <> 6359
		AND     $cond_aux_posto
		AND     $cond_os_tipo
		AND     $cond_consumidor
		AND     $cond_data_conserto
		AND     $cond_revenda $cond_sem_peca
		$cond_conserto
		$condnks
    ";
	//echo nl2br($sql);
	$res = pg_query($con,$sql);
	echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p>";

	//cria o indice para poder ordenar
	$sql = "CREATE INDEX tmp_os_aberta_resultado_data_abertura ON tmp_os_aberta_resultado(data_abertura);
		CREATE INDEX tmp_os_aberta_resultado_os ON tmp_os_aberta_resultado(os);";

	if ($login_fabrica == 3) {
		$sql = "SELECT  tmp_os_aberta_resultado.*,
						array_to_string(array_agg(tbl_os_interacao.comentario),' - ') AS interacoes
				FROM tmp_os_aberta_resultado
				LEFT JOIN tbl_os_interacao on tmp_os_aberta_resultado.os = tbl_os_interacao.os
				GROUP BY marca_nome, tmp_os_aberta_resultado.os, data_conserto,	sua_os, data_abertura, abertura, descricao_produto, produto_referencia, produto_descricao, produto_linha, serie, consumidor_revenda, codigo_posto, consumidor_nome, consumidor_fone, nome_posto, contato_estado, fone, digitacao_item, contato_email, os_excluida, troca_garantia, troca_faturada, status_checkpoint, nota_fiscal, data_nf, referencia_peca, descricao_peca";
	} else {
		$sql = "SELECT DISTINCT * FROM tmp_os_aberta_resultado ORDER BY data_abertura;";
	}
	
	//echo nl2br($sql);
	$res = pg_query($con,$sql);
	$numero_registros = pg_num_rows($res);
	//$all = pg_fetch_all($res);

	$conteudo = "";
	#HD 47918
	$mostrar_relatorio = 'sim';
	if ($numero_registros > 500){
		$mostrar_relatorio = 'nao';
	}

	$data = date("Y-m-d").".".date("H-i-s");

	$arquivo_nome     = "relatorio-os-aberta-$login_fabrica.$login_admin.".$formato_arquivo;
	$path             = __DIR__ . "/./xls/";
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	$fp = fopen ($arquivo_completo_tmp,"w");

/*	if ($formato_arquivo!='CSV'){
		fputs ($fp,"<html>");
		fputs ($fp,"<body>");
	}*/


	echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome'><img src='../imagens/excel.gif'><br/>Clique aqui para fazer download do relatório em  ".strtoupper($formato_arquivo)." </a></p>";
	echo "<p style='color:red'>*A relação exibe apenas um número de registros que seja inferior a 500. Para consulta total, faça o download do relatório em CSV ou XLS</p>";
	if ($numero_registros > 0) {
		$conteudo = "<table width='90%' border='1' cellpadding='2' cellspacing='1' class='tabela' align='center'>";

		$colspan = 7;

		if (strlen($posto)==0) {
			$colspan += 2;
		}

		if ($login_fabrica==3){
			$colspan = "100%";
		}

		$conteudo .= "<tr class='titulo_tabela'>";
		$conteudo .= "<td colspan='{$colspan}'><font style='font-size:14px;'>Relação de OS</font></td>";
		$conteudo .= "</tr>";
		$conteudo .= "<tr class='titulo_coluna'>";

		if ($login_fabrica==3){
			$conteudo .= "<td align='center'>Marca</td>";
			$conteudo .= "<td align='center'><acronym title='Revenda ou Consumidor'>R/C</acronym></td>";
			$conteudo .= "<td align='center'><acronym title='Data de Conserto'>DC</acronym></td>";
		}
		$conteudo .= "<td align='center'>OS</td>";
        $conteudo .= "<td align='center'>Abertura</td>";
        if ($login_fabrica == 3) {
            $conteudo .= "<td>Status</td>";
            $conteudo .= "<td>Nome Consumidor</td>";
            $conteudo .= "<td>Telefone Consumidor</td>";
            $conteudo .= "<td>Código Produto</td>";
            $conteudo .= "<td>Descrição Produto</td>";
            $conteudo .= "<td>Linha Produto</td>";
        } else {

            $conteudo .= "<td>Produto</td>";
        }
        $conteudo .= "<td>Série</td>";
		if (strlen($posto)==0) {
			$conteudo .= "<td>Código Posto</td>";
			$conteudo .= "<td>Nome Posto</td>";
		}
		$conteudo .= "<td>Estado</td>";
		$conteudo .= "<td>Fone</td>";
        $conteudo .= "<td>Email</td>";
        if ($login_fabrica == 3) {
            $conteudo .= "<td>Data Emissão</td>";
            $conteudo .= "<td>NF Peças</td>";
            #$conteudo .= "<td>Histórico de Interações</td>";

        }
		$conteudo .= "</tr>";

		#HD 47918
		if ($login_fabrica == 3 || $mostrar_relatorio != 'nao') {
			echo $conteudo;
		}

		#if ($formato_arquivo=='CSV'){
			$conteudoExcel = "";
			if ($login_fabrica == 3) {

                $conteudoExcel .= "MARCA;R/C;CONSERTO;OS;ABERTURA;STATUS;NOME CONSUMIDOR;TELEFONE CONSUMIDOR;CÓDIGO PRODUTO;DESCRIÇÃO PRODUTO;LINHA PRODUTO;SÉRIE;CODIGO POSTO;NOME POSTO;ESTADO;FONE;EMAIL;DATA EMISSÃO;NOTA PEÇAS;DIGITAÇÃO ITEM; REFERENCIA PECA; DESCRICAO PECA";
                
                $conteudoExcel .= "; RESPONSÁVEL; DATA; TROCADO POR; CAUSA DA TROCA";
                
                $conteudoExcel .= "; ORIENTACOES DO SAC AO POSTO AUTORIZADO;  DATA CONSERTO; HISTÓRICO DE INTERAÇÕES ";
                
               	$conteudoExcel .= ";\n";

            } else {
                $conteudoExcel .= "OS;ABERTURA;PRODUTO;SÉRIE;CODIGO POSTO;NOME POSTO;ESTADO;FONE;EMAIL \n";
            }
		#}
		fputs ($fp,$conteudoExcel);

		$numRows = 0; 

		while ($fetch = pg_fetch_array($res)) {
			$marca_nome    = trim($fetch['marca_nome']);
			$os            = trim($fetch['os']);
			$sua_os             = trim($fetch['sua_os']);
            $status_checkpoint  = trim($fetch['status_checkpoint']);
			$data_conserto = trim($fetch['data_conserto']);
			$interacoes = str_replace(["\t", "\r", "\n", "\0", ";"], ",", trim($fetch['interacoes']));
			$data_abertura = trim($fetch['abertura']);
			$produto       = substr(trim($fetch['descricao_produto']),0,40);
            $produto_referencia = $fetch['produto_referencia'];
            $produto_descricao = $fetch['produto_descricao'];
            $produto_linha = $fetch['produto_linha'];
            $serie         = trim($fetch['serie']);
			$consumidor_nome = trim($fetch['consumidor_nome']);
			$consumidor_fone = trim($fetch['consumidor_fone']);
			$consumidor_revenda= trim($fetch['consumidor_revenda']);
			$codigo_posto  = trim($fetch['codigo_posto']);
			$nome_posto    = substr(trim($fetch['nome_posto']),0,40);
			$contato_estado= trim($fetch['contato_estado']);
			$fone          = trim($fetch['fone']);
			$contato_email = trim($fetch['contato_email']);
			$troca_garantia = trim($fetch['troca_garantia']);
			$troca_faturada = trim($fetch['troca_faturada']);
			$digitacaoItem  = trim($fetch['digitacao_item']);
			$pecaDescricao  = trim($fetch['descricao_peca']);
			$pecaDescricao = str_replace(",", " ", $pecaDescricao);
			$pecaReferencia  = trim($fetch['referencia_peca']);
			if($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			$i++;

			#-------- Desconsidera ou marca em vermelho OS Excluidas ------
			#$sql = "SELECT * FROM tbl_os_excluida WHERE fabrica =$login_fabrica AND os = $os";
			#$resX = pg_query ($con,$sql);
			$dica = "";
			if ($os_excluida == 't') {
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
				$conteudo .= "<td>".$data_conserto."</td>";
			}
			$conteudo .= "<td><a href='os_press.php?os=$os' target='_blank'>\"".$sua_os."\"</a></td>";
            $conteudo .= "<td>".$data_abertura."</td>";
            if ($login_fabrica == 3) {
                $conteudo .= "<td>".$status_checkpoint."</td>";
                $conteudo .= "<td>".$consumidor_nome."</td>";
                $conteudo .= "<td>".$consumidor_fone."</td>";
                $conteudo .= "<td>".$produto_referencia."</td>";
                $conteudo .= "<td>".$produto_descricao."</td>";
                $conteudo .= "<td>".$produto_linha."</td>";
            } else {
                $conteudo .= "<td>".$produto      ."</td>";
            }
			$conteudo .= "<td>".$serie        ."</td>";
			if (strlen($posto)==0) {
				$conteudo .= "<td>".$codigo_posto."</td>";
				$conteudo .= "<td>".$nome_posto  ."</td>";
			}
			$conteudo .= "<td>".$contato_estado ."</td>";
			$conteudo .= "<td>".$fone           ."</td>";
            $conteudo .= "<td>".$contato_email  ."</td>";
            if ($login_fabrica == 3) {
                $nota_fiscal    = trim($fetch['nota_fiscal']);
                $data_nf        = trim($fetch['data_nf']);

                $conteudo .= "<td>".$data_nf."</td>";
                $conteudo .= "<td>\"".$nota_fiscal."\"</td>";
                #$conteudo .= "<td>\"".$interacoes."\"</td>";
            }
			$conteudo .= "</tr>";

			#HD 47918

			if (($login_fabrica == 3 && $numRows <= 499) || ($mostrar_relatorio != 'nao') ) { 
				
				echo $conteudo;
			}

			if ($login_fabrica == 3) {

			  	$queryAdicional = "SELECT a.nome_completo as responsavel, 
										  TO_CHAR(ot.data,'DD/MM/YYYY') as data, 
										  pc.descricao as troca_por,
										  ct.descricao as causa_troca, 
										  oe.orientacao_sac as orientacao_sac, 
										  TO_CHAR(o.data_conserto,'DD/MM/YYYY') as data_conserto
									FROM tbl_os o
									LEFT JOIN tbl_os_extra oe ON (oe.os = o.os) 
									LEFT JOIN tbl_os_troca ot ON (ot.os = o.os)
									LEFT JOIN tbl_admin a ON (a.admin = ot.admin)
									LEFT JOIN tbl_causa_troca ct ON (ct.causa_troca = ot.causa_troca)
									LEFT JOIN tbl_peca pc ON (ot.peca = pc.peca)
									WHERE o.os = $os";

				$resAdicional = pg_query($con,$queryAdicional);

				$os_troca = pg_fetch_object($resAdicional);

				$orientacao_sac = $os_troca->orientacao_sac;

				$os_troca_por = str_replace(",", "", $os_troca->troca_por);

				$orientacao_sac = preg_replace('[^a-z0-9\-]'  , ' ', $orientacao_sac);
				$orientacao_sac = preg_replace('/[[:space:]]/', ' ', $orientacao_sac);
				$orientacao_sac = preg_replace('/(-){2,}/'    , ' ', $orientacao_sac);
				$orientacao_sac = str_replace(";", "", $orientacao_sac);		
				$orientacao_sac = str_replace(",", " ", $orientacao_sac);
			}
			
			#if ($formato_arquivo=='CSV'){
				$conteudoExcel = "";

				if ($login_fabrica == 3) {
					
					$nome_posto = str_replace(";"," ",$nome_posto);
					$nome_posto = str_replace(":"," ",$nome_posto);
					$nome_posto = str_replace(","," ",$nome_posto);
					$nome_posto = str_replace("."," ",$nome_posto);
					
                    $conteudoExcel .= $marca_nome.";".$consumidor_revenda.";".$data_conserto.";".$sua_os.";".$data_abertura.";".$status_checkpoint.";".$consumidor_nome.";".$consumidor_fone.";".$produto_referencia.";".$produto_descricao.";".$produto_linha.";".$serie.";".$codigo_posto.";".$nome_posto.";".$contato_estado.";".$fone.";".$contato_email.";".$data_nf.";".$nota_fiscal . ";" . $digitacaoItem . ";" . $pecaReferencia . ";" . $pecaDescricao;

                    $conteudoExcel .= ";" . $os_troca->responsavel. ";" . $os_troca->data . ";" . $os_troca_por . ";" . $os_troca->causa_troca;

					$conteudoExcel .= ";" . $orientacao_sac . ";" . $os_troca->data_conserto . ";" . $interacoes;

	               	$conteudoExcel .= "; \n";

                } else {
                    $conteudoExcel .= "\"".$sua_os."\";".$data_abertura.";".$produto.";".$serie.";".$codigo_posto.";".$nome_posto.";".$contato_estado.";".$fone.";".$contato_email.";\n";
                }
			#}
			fputs ($fp,$conteudoExcel);

			$numRows++;
		}
		$conteudo  = "</table>";
		$conteudo .= "<BR><CENTER>".$numero_registros." Registros encontrados</CENTER>";

		#HD 47918
		if ($login_fabrica == 3 || $mostrar_relatorio != 'nao') {
			echo $conteudo;
		}
		#if ($formato_arquivo=='CSV'){
			$conteudoExcel = "";
		#}

		fputs ($fp,$conteudoExcel);
/*
		if ($formato_arquivo!='CSV'){
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		}*/
		fclose ($fp);
		flush();

		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		echo "<br>";
	} else {
		echo "<table border='0' cellpadding='2' cellspacing='0' align='center'>";
		echo "<tr height='50'>";
		echo "<td valign='middle' align='center'><img src='imagens/atencao.gif' border='0'>
			<font size=\"2\"><b>Não foram encontrados registros com os parâmetros informados/digitados!!!</b></font>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	#echo "<script language='javascript'>";
	#echo "document.getElementById('msg_carregando').style.visibility='hidden';";
	#echo "</script>";
}

include "rodape.php";
?>
