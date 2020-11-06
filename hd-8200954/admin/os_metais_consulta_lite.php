<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";

$msg = "";

// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$os = $_GET["excluir"];

if (strlen($os) > 0) {
	$sql =	"SELECT sua_os
			FROM tbl_os
			WHERE os = $os;";
	$res = @pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);
	if (@pg_numrows($res) == 1) {
		$sua_os = @pg_result($res,0,0);
		$sua_os_explode = explode("-", $sua_os);
		$xsua_os = $sua_os_explode[0];
	}

	if ($login_fabrica == 3) {
		$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
		$res = @pg_exec($con, $sql);
		
		#158147 Paulo/Waldir desmarcar se for reincidente
		$sql = "SELECT fn_os_excluida_reincidente($xxos,$login_fabrica)";
		$res = pg_exec($con, $sql);

	} else {
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
		$res = @pg_exec($con, $sql);
	}

	$msg_erro = pg_errormessage($con);
	$xsua_os  = strtoupper($xsua_os);

	if (strlen($msg_erro) == 0) {
		$sql =	"SELECT sua_os
				FROM tbl_os
				WHERE sua_os LIKE '$xsua_os-%'
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica;";
		$res = @pg_exec($con, $sql);
		$msg_erro = pg_errormessage($con);

		if (@pg_numrows($res) == 0) {
			$sql = "DELETE FROM tbl_os_revenda
					WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
					AND    tbl_os_revenda.fabrica = $login_fabrica
					AND    tbl_os_revenda.posto   = $login_posto";
			$res = @pg_exec($con, $sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$url = $_COOKIE["cookredirect"];
		header("Location: $url");
		exit;
	}

}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {
	if (strlen(trim($_GET["opcao1"])) > 0)  $opcao1 = trim($_GET["opcao1"]);
	if (strlen(trim($_GET["opcao2"])) > 0)  $opcao2 = trim($_GET["opcao2"]);
	if (strlen(trim($_GET["opcao3"])) > 0)  $opcao3 = trim($_GET["opcao3"]);
	if (strlen(trim($_GET["opcao4"])) > 0)  $opcao4 = trim($_GET["opcao4"]);
	if (strlen(trim($_GET["opcao5"])) > 0)  $opcao5 = trim($_GET["opcao5"]);
	if (strlen(trim($_GET["opcao6"])) > 0)  $opcao6 = trim($_GET["opcao6"]);
	if (strlen(trim($_GET["opcao7"])) > 0)  $opcao7 = trim($_GET["opcao7"]);

	if (strlen($opcao1) == 0 && strlen($opcao2) == 0 && strlen($opcao3) == 0 && strlen($opcao4) == 0 && strlen($opcao5) == 0 && strlen($opcao6) == 0) {
		$msg .= " Selecione pelo menos uma opção para realizar a pesquisa. ";
	}

	if (strlen($erro) == 0 && strlen($opcao1) > 0) {
		if (strlen($_GET["mes"]) > 0)  $mes = $_GET["mes"];
		if (strlen($_GET["ano"]) > 0)  $ano = $_GET["ano"];

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
		if (strlen($opcao2) == 0 AND strlen($opcao3) == 0 and strlen($opcao4) == 0) $msg .= " Informe mais parametros para pesquisa. ";

	} else {
		$mes = "";
		$ano = "";
	}

	if (strlen($opcao2) > 0) {

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";

		if (strlen($_GET["posto_codigo"]) > 0) $posto_codigo = "'".trim($_GET["posto_codigo"])."'";
		if (strlen($_GET["posto_nome"]) > 0)   $posto_nome   = trim($_GET["posto_nome"]);

		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {

			$sql = "SELECT tbl_posto_fabrica.posto        ,
							tbl_posto_fabrica.codigo_posto ,
							tbl_posto.nome
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = $posto_codigo;";
			$res = pg_exec($con, $sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res, 0, 'posto');
				$posto_codigo = pg_result($res, 0, 'codigo_posto');
				$posto_nome   = pg_result($res, 0, 'nome');
			} else {
				$msg .= " Posto não encontrado. ";
			}
		}

	} else {
		$posto        = "";
		$posto_codigo = "";
		$posto_nome   = "";
	}

	if (strlen($opcao3) > 0) {

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
	
		if (strlen($_GET["revenda_cnpj"]) > 0)  $revenda_cnpj = trim($_GET["revenda_cnpj"]);
		if (strlen($_GET["revenda_nome"]) > 0)  $revenda_nome = trim($_GET["revenda_nome"]);

		if (strlen($revenda_cnpj) > 0 && strlen($revenda_nome) > 0) {
			$sql = "SELECT revenda , cnpj , nome
					FROM tbl_revenda
					WHERE cnpj = '$revenda_cnpj';";
			$res = pg_exec($con, $sql);
			if (pg_numrows($res) == 1) {
				$revenda      = pg_result($res,0,revenda);
				$revenda_cnpj = pg_result($res,0,cnpj);
				$revenda_nome = pg_result($res,0,nome);
			} else {
				$msg .= " Revenda não encontrada. ";
			}
		}

	} else {
		$revenda      = "";
		$revenda_cnpj = "";
		$revenda_nome = "";
	}
	
	if (strlen($opcao4) > 0) {

		if (strlen($mes) == 0) $msg .= " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg .= " Selecione o ano para realizar a pesquisa. ";
		
		if (strlen($_GET["produto_referencia"]) > 0) $produto_referencia = trim($_GET["produto_referencia"]);
		if (strlen($_GET["produto_descricao"]) > 0)  $produto_descricao  = trim($_GET["produto_descricao"]);
		if (strlen($_GET["produto_voltagem"]) > 0)   $produto_voltagem   = trim($_GET["produto_voltagem"]);

		if (strlen($produto_referencia) > 0 && strlen($produto_descricao) > 0) {

			$sql = "SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  ,
							tbl_produto.voltagem
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica    = $login_fabrica
					AND   tbl_produto.referencia = '$produto_referencia'";

			if ($login_fabrica == 1) $sql .= " AND tbl_produto.voltagem = '$produto_voltagem';";
			$res = pg_exec($con, $sql);

			if (pg_numrows($res) == 1) {
				$produto            = pg_result($res, 0, 'produto');
				$produto_referencia = pg_result($res, 0, 'referencia');
				$produto_descricao  = pg_result($res, 0, 'descricao');
				$produto_voltagem   = pg_result($res, 0, 'voltagem');
			} else {
				$msg .= " Produto não encontrado. ";
			}
		}

	} else {
		$produto            = "";
		$produto_referencia = "";
		$produto_descricao  = "";
		$produto_voltagem   = "";
	}

	if (strlen($opcao5) > 0) {
		if (strlen($_GET["numero_os"]) > 0)  $numero_os = trim($_GET["numero_os"]);

		if (strlen($numero_os) > 0 && strlen($numero_os) < 3) $msg .= " Digite o número de série com o mínimo de 3 números. ";
	} else {
		$numero_os = "";
	}

	if (strlen($opcao6) > 0) {
		if (strlen($_GET["numero_serie"]) > 0)  $numero_serie = trim($_GET["numero_serie"]);

		if (strlen($numero_serie) > 0 && strlen($numero_serie) < 3) $msg .= " Digite o número de série com o mínimo de 3 números. ";
	} else {
		$numero_serie = "";
	}

}

$layout_menu = "callcenter";
$title       = "RELAÇÃO DE ORDENS DE SERVIÇO DE METAIS SANITÁRIOS LANÇADAS";

include "cabecalho.php";?>

<script language="JavaScript">

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if(campo.value != ""){
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
		janela.nome			= document.frm_pesquisa.revenda_nome;
		janela.cnpj			= document.frm_pesquisa.revenda_cnpj;
		janela.fone			= document.frm_pesquisa.revenda_fone;
		janela.cidade		= document.frm_pesquisa.revenda_cidade;
		janela.estado		= document.frm_pesquisa.revenda_estado;
		janela.endereco		= document.frm_pesquisa.revenda_endereco;
		janela.numero		= document.frm_pesquisa.revenda_numero;
		janela.complemento	= document.frm_pesquisa.revenda_complemento;
		janela.bairro		= document.frm_pesquisa.revenda_bairro;
		janela.cep			= document.frm_pesquisa.revenda_cep;
		janela.email		= document.frm_pesquisa.revenda_email;
		janela.focus();
	}
	else{
		alert("informe toda ou parte da informação para realizar a pesquisa");
	}
}

</script>

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
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<br>



<form name="frm_pesquisa" method="get" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
<? if (strlen($msg) > 0) { ?>
	<tr class="msg_erro">
		<td colspan="6"><?echo $msg?></td>
	</tr>
<? } ?>
	<tr class="titulo_tabela">
		<td colspan="6">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td width="100">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao1" value="1" class="frm" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Período </td>
		<td>Mês</td>
		<td colspan="2">Ano</td>
		<td width="100">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td colspan="2">
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao2" value="2" class="frm" <? if (strlen($opcao2) > 0) echo "checked"; ?>> Posto</td>
		<td>Código do Posto</td>
		<td colspan="2">Razão Social</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td colspan="2">
			<input type="text" name="posto_nome" size="15" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao3" value="3" class="frm" <? if (strlen($opcao3) > 0) echo "checked"; ?>> Revenda</td>
		<td>CNPJ da Revenda</td>
		<td colspan="2">Nome da Revenda</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="revenda_cnpj" size="8" value="<?echo $revenda_cnpj?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar revendas pelo código" onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_cnpj, 'cnpj');">
		</td>
		<td colspan="2">
			<input type="text" name="revenda_nome" size="15" value="<?echo $revenda_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar pelo nome da revenda." onclick="javascript: fnc_pesquisa_revenda (document.frm_pesquisa.revenda_nome, 'nome');">
		</td>
		<td>
			&nbsp;
			<input type='hidden' name = 'revenda_fone'>
			<input type='hidden' name = 'revenda_cidade'>
			<input type='hidden' name = 'revenda_estado'>
			<input type='hidden' name = 'revenda_endereco'>
			<input type='hidden' name = 'revenda_numero'>
			<input type='hidden' name = 'revenda_complemento'>
			<input type='hidden' name = 'revenda_bairro'>
			<input type='hidden' name = 'revenda_cep'>
			<input type='hidden' name = 'revenda_email'>
		</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao4" value="4" class="frm" <? if (strlen($opcao4) > 0) echo "checked"; ?>> Produto</td>
		<td>Referência</td>
		<td>Descrição</td>
		<td>Voltagem</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><input type="text" name="produto_referencia" size="8" value="<?echo $produto_referencia?>" class="frm"> <img src="imagens/lupa.png" width='20' height='18'  width='20' height='18' style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'referencia', document.frm_pesquisa.produto_voltagem)"></td>
		<td><input type="text" name="produto_descricao" size="15" value="<?echo $produto_descricao?>" class="frm"> <img src="imagens/lupa.png" width='20' height='18'  style="cursor: hand;" align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao, 'descricao', document.frm_pesquisa.produto_voltagem)"></td>
		<td><input type='text' name='produto_voltagem' size='5' value="<?echo $produto_voltagem?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao5" value="5" class="frm" <? if (strlen($opcao5) > 0) echo "checked"; ?>> Número da OS Metais</td>
		<td colspan="2"><input type="text" name="numero_os" size="15" value="<?echo $numero_os?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="2"><input type="checkbox" name="opcao6" value="6" class="frm" <? if (strlen($opcao6) > 0) echo "checked"; ?>> Número Série</td>
		<td colspan="2"><input type="text" name="numero_serie" size="15" value="<?echo $numero_serie?>" class="frm"></td>
		<td>&nbsp;</td>
	</tr>
	<? if($login_fabrica==19){ ?>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" colspan="4"><input type="checkbox" name="opcao7" value="7" class="frm" <? if (strlen($opcao7) > 0) echo "checked"; ?>> Somente OS não Efetivadas</td>
		<td>&nbsp;</td>
	</tr>
<? } ?>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6" align="center"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0) {
	if (strlen($mes) > 0 && strlen($ano) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if ($login_fabrica == 1) {
		/*$sql =	"SELECT DISTINCT
						A.os_revenda ,
						A.abertura                ,
						A.sua_os              ,
						SUBSTRING(A.sua_os,1,5) ,
						A.revenda_nome        ,
						A.revenda_cnpj        ,
						A.explodida           ,
						A.consumidor_revenda  ,
						A.data_fechamento     ,
						A.motivo_atraso       ,
						A.impressa            ,
						A.extrato             ,
						A.excluida            ,
						A.posto               ,
						A.codigo_posto        ,
						A.qtde_item
				FROM (
				(
					SELECT  DISTINCT
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.explodida                                                 ,
							TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os_revenda.digitacao                           AS digitacao          ,
							tbl_revenda.nome                                   AS revenda_nome       ,
							tbl_revenda.cnpj                                   AS revenda_cnpj       ,
							NULL                                               AS consumidor_revenda ,
							current_date                                       AS data_fechamento    ,
							TRUE                                               AS excluida           ,
							NULL                                               AS motivo_atraso      ,
							tbl_os_revenda_item.serie                                                ,
							tbl_os_revenda_item.produto                                              ,
							tbl_os_revenda.posto                                                     ,
							tbl_posto_fabrica.codigo_posto                                           ,
							tbl_produto.referencia                             AS produto_referencia ,
							tbl_produto.descricao                              AS produto_descricao  ,
							current_date                                       AS impressa           ,
							0                                                  AS extrato            ,
							0                                                  AS qtde_item
					FROM      tbl_os_revenda
					JOIN      tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN      tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
					LEFT JOIN tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
					JOIN tbl_posto                ON  tbl_posto.posto                = tbl_os_revenda.posto
					JOIN tbl_posto_fabrica        ON  tbl_posto_fabrica.posto        = tbl_posto.posto
												  AND tbl_posto_fabrica.fabrica      = $login_fabrica
					WHERE tbl_os_revenda.fabrica = $login_fabrica
				) UNION (
					SELECT  tbl_os.os                                  AS os_revenda         ,
							tbl_os.sua_os                                                    ,
							NULL                                       AS explodida          ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os.data_digitacao                      AS digitacao          ,
							tbl_os.revenda_nome                                              ,
							tbl_os.revenda_cnpj                                              ,
							tbl_os.consumidor_revenda                                        ,
							tbl_os.data_fechamento                                           ,
							tbl_os.excluida                                                  ,
							tbl_os.motivo_atraso                                             ,
							tbl_os.serie                                                     ,
							tbl_os.produto                                                   ,
							tbl_os.posto                                                     ,
							tbl_posto_fabrica.codigo_posto                                   ,
							tbl_produto.referencia                     AS produto_referencia ,
							tbl_produto.descricao                      AS produto_descricao  ,
							tbl_os_extra.impressa                                            ,
							tbl_os_extra.extrato                                             ,
							(
								SELECT COUNT(tbl_os_item.*) AS qtde_item
								FROM   tbl_os_item
								JOIN   tbl_os_produto USING (os_produto)
								WHERE  tbl_os_produto.os = tbl_os.os
							)                                          AS qtde_item
					FROM tbl_os
					JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
					JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
					JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.consumidor_revenda = 'R'
				)
			) AS A
			WHERE (1=1 ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND A.digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($revenda) > 0) {
			if (strlen($revenda_cnpj) > 0) $sql .= " AND A.revenda_cnpj = '$revenda_cnpj'";
			if (strlen($revenda_nome) > 0) $sql .= " AND A.revenda_nome = '$revenda_nome'";
		}

		if (strlen($produto) > 0) {
			$sql .= " AND A.produto = $produto";
		}

		if (strlen($posto) > 0) {
			$sql .= " AND A.posto = $posto";
		}

		if (strlen($numero_os) > 0) {
			$pos = strpos($numero_os, "-");
			
			if ($pos === false) {
				$pos = strlen($numero_os) - 5;
				if (strlen($numero_os) > 5) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			}else{
				$pos = $pos - 5;
				if (strlen($numero_os) > 7) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			}
			$numero_os = strtoupper($numero_os);
			$sql .= " AND A.sua_os LIKE '%$numero_os%'";
		}
		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND A.serie LIKE '%$numero_serie%'";

		$sql .= ") ORDER BY SUBSTRING(A.sua_os,1,5) ASC, A.os_revenda ASC;";*/
/*TAKASHI ALTEROU O SQL 16-05-07*/


	$sql =	"SELECT DISTINCT
						A.os_revenda ,
						A.abertura                ,
						A.sua_os              ,
						SUBSTRING(A.sua_os,1,5) as sub_sua_os ,
						A.revenda_nome        ,
						A.revenda_cnpj        ,
						A.explodida           ,
						A.consumidor_revenda  ,
						A.data_fechamento     ,
						A.motivo_atraso       ,
						A.impressa            ,
						A.extrato             ,
						A.excluida            ,
						A.posto               ,
						A.codigo_posto        ,
						A.qtde_item           ,
						A.tipo_atendimento    
				FROM (
				(
					SELECT  DISTINCT
							tbl_os_revenda.os_revenda                                                ,
							tbl_os_revenda.sua_os                                                    ,
							tbl_os_revenda.explodida                                                 ,
							TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os_revenda.digitacao                           AS digitacao          ,
							tbl_revenda.nome                                   AS revenda_nome       ,
							tbl_revenda.cnpj                                   AS revenda_cnpj       ,
							NULL                                               AS consumidor_revenda ,
							current_date                                       AS data_fechamento    ,
							TRUE                                               AS excluida           ,
							NULL                                               AS motivo_atraso      ,
							tbl_os_revenda_item.serie                                                ,
							tbl_os_revenda_item.produto                                              ,
							tbl_os_revenda.posto                                                     ,
							tbl_posto_fabrica.codigo_posto                                           ,
							tbl_produto.referencia                             AS produto_referencia ,
							tbl_produto.descricao                              AS produto_descricao  ,
							current_date                                       AS impressa           ,
							0                                                  AS extrato            ,
							0                                                  AS qtde_item          ,
							tbl_os_revenda.tipo_atendimento
					FROM      tbl_os_revenda
					JOIN      tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN      tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
					LEFT JOIN tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
					JOIN tbl_posto                ON  tbl_posto.posto                = tbl_os_revenda.posto
					JOIN tbl_posto_fabrica        ON  tbl_posto_fabrica.posto        = tbl_posto.posto
												  AND tbl_posto_fabrica.fabrica      = $login_fabrica
					WHERE tbl_os_revenda.fabrica       = $login_fabrica 
					AND   tbl_os_revenda.os_manutencao IS FALSE 
					AND   tbl_os_revenda.os_geo        IS TRUE
					and   tbl_os_revenda.excluida      IS FALSE ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($revenda) > 0) {
			if (strlen($revenda_cnpj) > 0) $sql .= " AND tbl_revenda.cnpj = '$revenda_cnpj'";
			if (strlen($revenda_nome) > 0) $sql .= " AND tbl_revenda.nome = '$revenda_nome'";
		}

		if (strlen($produto) > 0) {
			$sql .= " AND tbl_os_revenda_item.produto = $produto";
		}

		if (strlen($posto) > 0) {
			$sql .= " AND tbl_os_revenda.posto = $posto";
		}

		if (strlen($numero_os) > 0) {

			# HD 51628
			if (strlen($posto) == 0 and strlen($numero_os) > 9) {
				$cod_posto = substr($numero_os,0,5);
				$sqlP = "SELECT posto FROM tbl_posto_fabrica
						WHERE fabrica = $login_fabrica
						AND codigo_posto = '$cod_posto'";
				$resP = pg_exec($con,$sqlP);

				if (pg_numrows($resP) > 0){
					$aux_posto = pg_result($resP,0,0);
				}

				$sql .= " AND tbl_os_revenda.posto = $aux_posto";
			}

			$pos = strpos($numero_os, "-");
			
			if ($pos === false) {
				$pos = strlen($numero_os) - 5;
				if (strlen($numero_os) > 5) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			} else {
				$pos = $pos - 5;
				if (strlen($numero_os) > 7) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			}

			$numero_os = strtoupper($numero_os);

			$sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os'";
		}

		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie LIKE '%$numero_serie%'";

				$sql .= " ) UNION (
					SELECT  tbl_os.os                                  AS os_revenda         ,
							tbl_os.sua_os                                                    ,
							NULL                                       AS explodida          ,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura           ,
							tbl_os.data_digitacao                      AS digitacao          ,
							tbl_os.revenda_nome                                              ,
							tbl_os.revenda_cnpj                                              ,
							tbl_os.consumidor_revenda                                        ,
							tbl_os.data_fechamento                                           ,
							tbl_os.excluida                                                  ,
							tbl_os.motivo_atraso                                             ,
							tbl_os.serie                                                     ,
							tbl_os.produto                                                   ,
							tbl_os.posto                                                     ,
							tbl_posto_fabrica.codigo_posto                                   ,
							tbl_produto.referencia                     AS produto_referencia ,
							tbl_produto.descricao                      AS produto_descricao  ,
							tbl_os_extra.impressa                                            ,
							tbl_os_extra.extrato                                             ,
							(
								SELECT COUNT(tbl_os_item.*) AS qtde_item
								FROM   tbl_os_item
								JOIN   tbl_os_produto USING (os_produto)
								WHERE  tbl_os_produto.os = tbl_os.os
							)                                          AS qtde_item          ,
							NULL                                       As tipo_atendimento
					FROM tbl_os
					JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
					JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
					JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.consumidor_revenda = 'R' 
					AND tbl_os.tipo_atendimento in (64,65,69)
					/*AND   (tbl_os.tipo_os is null or tbl_os.tipo_os <> 13)*/";//HD 286686 - ITEM 14

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($revenda) > 0) {
			if (strlen($revenda_cnpj) > 0) $sql .= " AND tbl_os.revenda_cnpj = '$revenda_cnpj'";
			if (strlen($revenda_nome) > 0) $sql .= " AND tbl_os.revenda_nome = '$revenda_nome'";
		}

		if (strlen($produto) > 0) {
			$sql .= " AND tbl_os.produto = $produto";
		}

		if (strlen($posto) > 0) {
			$sql .= " AND tbl_os.posto = $posto";
		}

		if (strlen($numero_os) > 0) {

			# HD 51628
			if (strlen($aux_posto) > 0 and strlen($posto) == 0) {
				$sql .= " AND tbl_os.posto = $aux_posto";
			}

			$pos = strpos($numero_os, "-");
			
			if ($pos === false) {
				$pos = strlen($numero_os) - 5;
				if (strlen($numero_os) > 5) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			} else {
				$pos = $pos - 5;
				if (strlen($numero_os) > 7) $numero_os = substr($numero_os, $pos, strlen($numero_os));
			}

			$numero_os = strtoupper($numero_os);
			$sql .= " AND tbl_os.sua_os LIKE '%$numero_os'";

		}

		$numero_serie = strtoupper($numero_serie);

		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os.serie LIKE '%$numero_serie%'";

		$sql .= " )
			) AS A
			WHERE (1=1 ) ORDER BY A.sua_os ASC, A.os_revenda ASC ;";

/*TAKASHI ALTEROU O SQL 16-05-07*/
	} else {
		$sql =	"SELECT DISTINCT
						tbl_os_revenda.os_revenda                                          ,
						tbl_os_revenda.sua_os                                              ,
						tbl_os_revenda.explodida                                           ,
						TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS abertura     ,
						tbl_os_revenda.revenda                                             ,
						tbl_os_revenda.posto                                               ,
						tbl_revenda.cnpj                                   AS revenda_cnpj ,
						tbl_revenda.nome                                   AS revenda_nome ,
						tbl_posto.nome as nome_posto                                       ,
						tbl_posto_fabrica.codigo_posto                                     
			FROM		tbl_os_revenda
			LEFT JOIN	tbl_os_revenda_item ON  tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			LEFT JOIN	tbl_produto         ON  tbl_produto.produto            = tbl_os_revenda_item.produto
			LEFT JOIN	tbl_revenda         ON  tbl_revenda.revenda            = tbl_os_revenda.revenda
			JOIN tbl_posto on tbl_posto.posto = tbl_os_revenda.posto
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_revenda.fabrica = $login_fabrica
			AND   tbl_os_revenda.os_manutencao IS FALSE ";

		if (strlen($data_inicial) > 0 && strlen($data_final) > 0) $sql .= " AND tbl_os_revenda.digitacao BETWEEN '$data_inicial' AND '$data_final'";

		if (strlen($posto) > 0) $sql .= " AND tbl_os_revenda.posto = $posto";

		if (strlen($revenda) > 0) $sql .= " AND tbl_os_revenda.revenda = $revenda";

		if (strlen($produto) > 0) $sql .= " AND tbl_os_revenda_item.produto = $produto";

		$numero_os = strtoupper($numero_os);
		if (strlen($numero_os) > 0) $sql .= " AND tbl_os_revenda.sua_os LIKE '%$numero_os%'";

		$numero_serie = strtoupper($numero_serie);
		if (strlen($numero_serie) > 0) $sql .= " AND tbl_os_revenda_item.serie LIKE '%$numero_serie%'";
		
		if (strlen($opcao7) > 0 AND $login_fabrica == 19) $sql .= " AND tbl_os_revenda.explodida is null ";

		$sql .= " ORDER BY tbl_os_revenda.os_revenda DESC;";
	}

	$res = pg_exec($con, $sql);

	//if (getenv("REMOTE_ADDR") == "200.228.76.93") echo nl2br($sql);
	#echo nl2br($sql);
	if (pg_numrows($res) > 0) {

		$total_registro = pg_numrows($res);

		if ($login_fabrica == 1) {

			echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center' class='tabela'>";
				echo "<tr>";
					echo "<td align='center' width='10' bgcolor='#FFE1E1'>&nbsp;</td>";
					echo "<td align='left'><font size='1'>&nbsp; Excluídas do sistema</font></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='center' width='10' bgcolor='#91C8FF'>&nbsp;</td>";
					echo "<td align='left'><font size='1'>&nbsp; OSs sem fechamento há mais de 20 dias, informar \"Motivo\"</font></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='center' width='10' bgcolor='#FFCC66'>&nbsp;</td>";
					echo "<td align='left'><font size='1'>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</font></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='center' width='10' bgcolor='#FF0000'>&nbsp;</td>";
					echo "<td align='left'><font size='1'>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</font></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='center' width='10' bgcolor='#99FF66'>&nbsp;</td>";
					echo "<td align='left'><font size='1'>&nbsp; OSs de Troca</font></td>";
				echo "</tr>";
			echo "</table>";
			echo "<br />";

		}

		echo "<table border='0' cellpadding='2' align='center' cellspacing='1' class='tabela' width='700'>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

			if ($i == 0) {

				echo "<tr class='titulo_coluna' height='15'>";
				echo "<td>OS</td>";
				echo "<td>Data</td>";
				echo "<td>Revenda</td>";

				if ($login_fabrica == 1) {
					echo "<td>Item</td>";
					echo "<td><img border='0' src='imagens/img_impressora.gif' alt='OS que já foi impressa'></td>";
					$colspan = "5";//HD 283171
				} else if($login_fabrica == 19) {
					echo "<td>Código Posto</td>";
					echo "<td>Nome Posto</td>";
					$colspan = "5";
				} else {
					$colspan = "3";
				}

				echo "<td colspan='$colspan'>Ações</td>";
				echo "</tr>";

			}

			$os_revenda   = trim(pg_result($res, $i, 'os_revenda'));
			$sua_os       = trim(pg_result($res, $i, 'sua_os'));
			$explodida    = trim(pg_result($res, $i, 'explodida'));
			$abertura     = trim(pg_result($res, $i, 'abertura'));
			$revenda_cnpj = trim(pg_result($res, $i, 'revenda_cnpj'));
			$revenda_nome = trim(pg_result($res, $i, 'revenda_nome'));
			
			$xxposto      = trim(pg_result($res, $i, posto));/*TAKASHI ALTEROU O SQL 16/05/07*/
			
			if ($login_fabrica == 1) {

				$consumidor_revenda = trim(pg_result($res, $i, 'consumidor_revenda'));
				$data_fechamento    = trim(pg_result($res, $i, 'data_fechamento'));
				$motivo_atraso      = trim(pg_result($res, $i, 'motivo_atraso'));
				$impressa           = trim(pg_result($res, $i, 'impressa'));
				$extrato            = trim(pg_result($res, $i, 'extrato'));
				$excluida           = trim(pg_result($res, $i, 'excluida'));
				$qtde_item          = trim(pg_result($res, $i, 'qtde_item'));
				$posto_codigo       = trim(pg_result($res, $i, 'codigo_posto'));
				$tipo_atendimento   = trim(pg_result($res, $i, 'tipo_atendimento'));
	
				if (strlen($tipo_atendimento) > 0) {

					$sql2="SELECT descricao
							from tbl_tipo_atendimento
							where tipo_atendimento=$tipo_atendimento";

					$res2 = pg_exec($con,$sql2);
					$descricao = pg_result($res2, 0, 'descricao');

				}

				if (strlen($consumidor_revenda) > 0) {

					if ($excluida == "t") $cor = "#FFE1E1";

					// verifica se nao possui itens com 5 dias de lancamento...
					$aux_data_abertura = fnc_formata_data_pg($abertura);

					$sqlX = "SELECT to_char (current_date + INTERVAL '5 days', 'YYYY-MM-DD')";
					$resX = pg_exec($con,$sqlX);
					$data_hj_mais_5 = pg_result($resX,0,0);

					$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '5 days', 'YYYY-MM-DD')";
					$resX = pg_exec($con,$sqlX);
					$data_consultar = pg_result($resX,0,0);

					$sql = "SELECT COUNT(tbl_os_item.*) as total_item
							FROM tbl_os_item
							JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
							JOIN tbl_os on tbl_os.os = tbl_os_produto.os
							WHERE tbl_os.os = $os_revenda
							AND tbl_os.data_abertura::date >= '$data_consultar'";

					$resItem = pg_exec($con, $sql);

					$itens = pg_result($resItem,0,total_item);

					if ($itens == 0 and $data_consultar > $data_hj_mais_5) $cor = "#FFCC66";

					$mostra_motivo = 2;

					// verifica se está sem fechamento ha 20 dias ou mais da data de abertura...
					if (strlen($data_fechamento) == 0 AND $mostra_motivo == 2 AND $login_fabrica == 1) {

						$aux_data_abertura = fnc_formata_data_pg($abertura);

						$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '20 days', 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_consultar = pg_result($resX,0,0);

						$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_atual = pg_result ($resX,0,0);

						if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
							$mostra_motivo = 1;
							$cor = "#91C8FF";
						}

					}

					// Se estiver acima dos 30 dias, nao exibira os botoes...
					if (strlen($data_fechamento) == 0 AND $login_fabrica == 1) {
						$aux_data_abertura = fnc_formata_data_pg($abertura);

						$sqlX = "SELECT to_char ($aux_data_abertura::date + INTERVAL '30 days', 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_consultar = pg_result($resX,0,0);

						$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_atual = pg_result($resX,0,0);

						if ($data_consultar < $data_atual AND strlen($data_fechamento) == 0) {
							$mostra_motivo = 1;
							$cor = "#ff0000";
						}

					}

				}

			} else {

				$nome_posto   = trim(pg_result($res, $i, 'nome_posto'));
				$codigo_posto = trim(pg_result($res, $i, 'codigo_posto'));

			}

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			} else {
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			$sua_os = strtoupper($sua_os);
			/*$sql =	"SELECT *
					FROM tbl_os
					WHERE sua_os LIKE '$sua_os-%' ";*/
			/*TAKASHI ALTEROU O SQL 16/05/07*/
			$sql =	"SELECT tbl_os.os,
							tbl_os.sua_os
					FROM tbl_os
					WHERE fabrica = $login_fabrica 
					AND posto = $xxposto 
					AND (sua_os = '$sua_os' OR ";
					for ($x = 1; $x <= 40; $x++) {
						$sql .= " tbl_os.sua_os = '$sua_os-$x' OR ";
					}
			$sql .= "1=2 )";
			$resX = pg_exec($con, $sql);
			/*TAKASHI ALTEROU O SQL 16/05/07*/
			if ($login_fabrica == 1) {
				$sua_os = $sua_os;
				if (strlen($tipo_atendimento) > 0) {
					$cor = "#99FF66";
				}
			}
			echo "<tr  height='15' bgcolor='$cor'>";
			echo "<td nowrap>";
			if ($login_fabrica == 1) echo $posto_codigo;
			echo $sua_os . "</td>";
			echo "<td nowrap>" . $abertura . "</td>";
			echo "<td nowrap><acronym title='CNPJ: $revenda_cnpj\nRAZÃO SOCIAL: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,20) . "</acronym></td>";

			if ($login_fabrica != 1) {
				
				if ($login_fabrica == 19) {
					echo "<td>$codigo_posto</td>";
					echo "<td>$nome_posto</td>";
				}
				echo "<td width='80' align='center'>";
				if (pg_numrows($resX) == 0 || strlen($explodida) == 0) echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img border='0' src='imagens/btn_alterar_".$botao.".gif'></a>";
				else                                                   echo "&nbsp;";
				echo "</td>";
				echo "<td width='80' align='center'>";
				if (pg_numrows($resX) == 0 || strlen($explodida) == 0) {
					echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir'><img border='0' src='imagens/btn_explodir";
					if($login_fabrica==19){echo "_2";}
					echo ".gif'></a>";
				} else {
					echo "&nbsp;";
				}
				echo "</td>";

				echo "<td width='80' align='center'>";
					echo "<a href='os_revenda_print.php?os_revenda=$os_revenda' target='_blank'><img border='0' src='imagens/btn_imprimir_".$botao.".gif'></a>";
				echo "</td>";

			} else {

				if (strlen($consumidor_revenda) == 0) {
					echo "<td nowrap>&nbsp</td>";
					echo "<td nowrap>&nbsp</td>";
					// verifica se existem OS geradas pela OS Revenda
					$sua_os = strtoupper($sua_os);
					
					/*$sql = "SELECT *
							FROM   tbl_os
							WHERE  sua_os LIKE '$sua_os-%'";*/
		
					/*TAKASHI ALTEROU O SQL 16/05/07*/
					$sql =	"SELECT tbl_os.os,
									tbl_os.sua_os
							FROM tbl_os
							WHERE fabrica = $login_fabrica 
							AND posto = $xxposto 
							AND (sua_os = '$sua_os' OR ";
							for ($x = 1; $x <= 40; $x++) {
								$sql .= " tbl_os.sua_os = '$sua_os-$x' OR ";
							}
					$sql .= "1=2 )";
					$resX = pg_exec($con, $sql);
					/*TAKASHI ALTEROU O SQL 16/05/07*/
		
					$resX = pg_exec($con, $sql);

					echo "<td width='80' align='center'>";
					if (pg_numrows($resX) == 0 || strlen($explodida) == 0) echo "<a href='os_revenda.php?os_revenda=$os_revenda'><img src='imagens/btn_alterar_".$botao.".gif'></a>";
					else                                                   echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if (pg_numrows($resX) == 0 || strlen($explodida) == 0) echo "<a href='os_revenda_finalizada.php?os_revenda=$os_revenda&btn_acao=explodir'><img src='imagens/btn_explodir.gif'></a>";
					else                                                   echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'>";

					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os_revenda'><img src='imagens/btn_consulta.gif'></a>";
					else                                            echo "&nbsp;";
					echo "</td>\n";

					echo "<td width='80' align='center'><a href='os_revenda_print.php?os_revenda=$os_revenda' target='_target'><img src='imagens/btn_imprimir_" . $botao . ".gif' alt='Imprimir Revenda'></a></td>\n";

					//COMENTEI POIS ELE NAO ENCONTRAVA O ARQUIVO
					//echo "<td width='80' align='center'><a href='os_revenda_blackedecker_total_print.php?os_revenda=$os_revenda' target='_target'><img src='imagens/btn_imprimir_" . $botao . ".gif' alt='Imprimir Black & Decker'></a></td>\n";

					echo "<td width='80' align='center'>";
					if (strlen($tipo_atendimento) > 0) {
						echo "$descricao";
					}
					echo "&nbsp;</td>\n";

				} else {

					echo "<td width='30' align='center'>";
					if ($qtde_item > 0) echo"<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
					else                echo"&nbsp;";
					echo "</td>\n";

					echo "<td width='30' align='center'>";
					if (strlen($impressa) > 0) echo"<img border='0' src='imagens/img_ok.gif' alt='OS que já foi impressa'>";
					else                       echo"<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>";
					echo "</td>\n";

					echo "<td width='80' align='center'>";
					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os_revenda'><img src='imagens/btn_consulta.gif'></a>";
					else                                            echo "&nbsp;";
					echo "</td>\n";

					if ($login_fabrica != 1) {//HD 283171
						echo "<td width='80' align='center'>";
						if (($excluida == "f" || strlen($excluida) == 0) && strlen($data_fechamento) == 0) echo "<a href='os_cadastro.php?os=$os_revenda'><img src='imagens/btn_alterar_cinza.gif'></a>";
						else                                                                               echo "&nbsp;";
						echo "</td>\n";
					}//HD 283171

					echo "<td width='80' align='center'>";
					if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_print.php?os=$os_revenda' target='_blank'><img src='imagens/btn_imprime.gif'></a>";
					else                                            echo "&nbsp;";
					echo "</td>";

					if ($login_fabrica != 1) {//HD 283171
						echo "<td width='80' align='center'>";
						if ($mostra_motivo == 1) {
							if ($excluida == "f" || strlen($excluida) == 0) {
								echo "<a href='os_item.php?os=$os_revenda'><img src='imagens/btn_lanca.gif'></a> &nbsp; <a href='os_motivo_atraso.php?os=$os_revenda'>Motivo</a>";
							}
						} else if (strlen($data_fechamento) == 0) {
							if ($excluida == "f" || strlen($excluida) == 0) {
								echo "<a href='os_item.php?os=$os_revenda'><img src='imagens/btn_lanca.gif'></a>";
							}
						} else if (strlen($data_fechamento) > 0 && strlen($extrato) == 0) {
							if ($excluida == "f" || strlen($excluida) == 0) {
								echo "<a href='os_item.php?os=$os_revenda&reabrir=ok' ><img src='imagens/btn_reabriros.gif'></a>";
							}
						}
						echo "</td>\n";

						echo "<td width='80' align='center'>";
						if (strlen($data_fechamento) == 0 && strlen($pedido) == 0) {
							if ($excluida == "f" || strlen($excluida) == 0) {
								$sua_os_black = $posto_codigo.$sua_os;
								echo "<a href=\"javascript: if (confirm ('Deseja realmente excluir OS $sua_os_black ?') == true) { window.location='$PHP_SELF?excluir=$os_revenda' }\"><img src='imagens/btn_excluir.gif'></A>";
							} else {
								echo "&nbsp;";
							}

						} else {
							echo "&nbsp;";
						}
						echo "</td>\n";
					}//HD 283171
				}
			}

			echo "</tr>";
		}

		echo "</table>";
		echo "<p align='center'><b>Total de $total_registro registro(s).</b></p>";

	} else {
		echo "<table border='0' align='center'>";
			echo "<tr>";
				echo "<td><img border='0' src='imagens/atencao.gif'></td>";
				echo "<td> &nbsp; <b>Não foi encontrado nenhuma OS nessa pesquisa.</b></td>";
			echo "</tr>";
		echo "</table>";
	}
}?>

<br />

<? include "rodape.php" ?>