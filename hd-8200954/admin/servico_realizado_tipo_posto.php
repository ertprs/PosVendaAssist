<?php

$admin_privilegios="gerencia";
$layout_menu = "gerencia";
$title = "CADASTRO DE SERVICOS REALIZADOS X TIPOS DE POSTOS";

if(!$_GET["q"])
{
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'includes/funcoes.php';
	include 'autentica_admin.php';
	include "cabecalho.php";
	include "javascript_pesquisas.php";
	include "javascript_calendario.php";
//	$login_fabrica = $_GET["fabrica"];
}
else
{
	//BUSCA AJAX PARA O AUTOCOMPLETE DO CAMPO "POSTO" (id=postonome)
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'funcoes.php';
	include 'autentica_admin.php';
//	$login_fabrica = $_GET["fabrica"];

	$busca_nome = $_GET["q"];
	$busca_posto = intval($_GET["q"]);

	$sql = "
	SELECT
	tbl_posto.posto,
	tbl_posto.nome,
	tbl_posto_fabrica.tipo_posto,
	tbl_tipo_posto.descricao,
	ARRAY_TO_STRING(
		ARRAY (
			SELECT servico_realizado || '=' || percentual_acrescimo
			FROM tbl_mao_obra_servico_realizado_excecao
			WHERE tbl_mao_obra_servico_realizado_excecao.posto=tbl_posto.posto
		),
		','
	) AS servico_realizado

	FROM
	tbl_posto
	JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
	JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto

	WHERE
	tbl_posto_fabrica.fabrica=$login_fabrica
	AND
	(
		tbl_posto.nome ILIKE '%$busca_nome%'
		OR tbl_posto.posto = $busca_posto
	)
	";

	$res = pg_exec($con, $sql);
	
	$resultado = array();
	for($i = 0; $i < pg_num_rows($res); $i++)
	{
		$posto = pg_result($res, $i, posto);
		$nome = pg_result($res, $i, nome);
		$tipo = pg_result($res, $i, tipo_posto);
		$servico = pg_result($res, $i, servico_realizado);
		$descricao = pg_result($res, $i, descricao);

		$resultado[] = "$posto|[$posto] - $nome - $descricao|$tipo|$servico";
	}
	$resultado = implode("\n", $resultado);
	echo $resultado;
	die;
}

if($_POST["gravar"])
{
	$sql = "
	SELECT
	servico_realizado,
	tipo_posto

	FROM
	tbl_servico_realizado JOIN
	tbl_mao_obra_servico_realizado USING(servico_realizado) JOIN
	tbl_tipo_posto USING (tipo_posto)

	WHERE
	tbl_servico_realizado.fabrica=$login_fabrica
	AND tbl_servico_realizado.ativo=true
	AND tbl_tipo_posto.fabrica=$login_fabrica
	AND tbl_tipo_posto.ativo=true
	";

	$res = pg_exec($con, $sql);
	$servico_posto = array();

	for($i = 0; $i < pg_num_rows($res); $i++)
	{
		$cods = pg_result($res, $i, servico_realizado);
		$codp = pg_result($res, $i, tipo_posto);

		$servico_posto[$cods][$codp] = true;
	}

	foreach($_POST["servico_posto"] AS $servico => $postos)
	{
		foreach($postos AS $posto => $valor)
		{
			unset($sql);
			$valor = floatval($valor);

			if(isset($servico_posto[$servico][$posto]))
			{
				if ($valor == 0)
				{
					$sql = "
					DELETE FROM
					tbl_mao_obra_servico_realizado
					
					WHERE
					servico_realizado=$servico
					AND tipo_posto=$posto
					";
				}
				else
				{
					$sql = "
					UPDATE
					tbl_mao_obra_servico_realizado
					
					SET
					mao_de_obra=$valor
					
					WHERE
					servico_realizado=$servico
					AND tipo_posto=$posto
					";
				}
			}
			else
			{
				if ($valor != 0)
				{
					$sql = "
					INSERT INTO
					tbl_mao_obra_servico_realizado(servico_realizado, tipo_posto, mao_de_obra)
					VALUES($servico, $posto, $valor)
					";
				}
			}

			if (isset($sql))
			{
				@$res = pg_exec($con, $sql);
				if (strlen(pg_errormessage()) > 0)
					echo "<div class=srtperro>" . pg_errormessage() . "<br>$sql</div>";
			}
		}
	}

	if ($_POST["postoid"])
	{
		$sql = "
		SELECT
		servico_realizado

		FROM
		tbl_mao_obra_servico_realizado_excecao

		WHERE
		posto=" . $_POST["postoid"] . "
		";
		$res = pg_exec($con, $sql);

		$servicos = array();
		for($i = 0; $i < pg_num_rows($res); $i++)
		{
			$cods = pg_result($res, $i, servico_realizado);
			$servicos[$cods] = true;
		}

		foreach($_POST[servico_posto_excecao] as $servico => $valor)
		{
			$valor = floatval($valor);
			unset($sql);

			if($servicos[$servico])
			{
				if ($valor == 0)
				{
					$sql = "
					DELETE FROM
					tbl_mao_obra_servico_realizado_excecao

					WHERE
					posto=" . $_POST["postoid"] . "
					AND servico_realizado=$servico
					";
				}
				else
				{
					$sql = "
					UPDATE
					tbl_mao_obra_servico_realizado_excecao

					SET
					percentual_acrescimo=$valor

					WHERE
					posto=" . $_POST["postoid"] . "
					AND servico_realizado=$servico
					";
				}
			}
			else
			{
				if ($valor != 0)
				{
					$sql = "
					INSERT INTO
					tbl_mao_obra_servico_realizado_excecao(posto, servico_realizado, percentual_acrescimo)
					VALUES(" . $_POST["postoid"] . ", $servico, $valor)
					";
				}
			}

			if (isset($sql))
			{
				@$res = pg_exec($con, $sql);
				if (strlen(pg_errormessage()) > 0)
					echo "<div class=srtperro>" . pg_errormessage() . "</div>";
			}
		}
	}
}


//CARREGANDO TIPOS DE SERVICOS REALIZADOS DA FABRICA
$sql = "
SELECT
servico_realizado,
descricao

FROM
tbl_servico_realizado

WHERE
fabrica=$login_fabrica
AND ativo=true
";
$res_servico = pg_exec($con, $sql);

//CARREGANDO TIPOS DE POSTOS CADASTRADOS PARA A FABRICA
$sql = "
SELECT
tipo_posto,
descricao,
codigo

FROM
tbl_tipo_posto

WHERE
fabrica=$login_fabrica
AND ativo=true
";
$res_posto = pg_exec($con, $sql);

//CARREGANDO DADOS EXISTENTES NO CADASTRO DE SERVICOS REALIZADOS X TIPOS DE POSTOS PARA A FABRICA
$sql = "
SELECT
servico_realizado,
tipo_posto,
mao_de_obra

FROM
tbl_servico_realizado JOIN
tbl_mao_obra_servico_realizado USING(servico_realizado) JOIN
tbl_tipo_posto USING (tipo_posto)

WHERE
tbl_servico_realizado.fabrica=$login_fabrica
AND tbl_servico_realizado.ativo=true
AND tbl_tipo_posto.fabrica=$login_fabrica
AND tbl_tipo_posto.ativo=true
";
$res_dados = pg_exec($con, $sql);

//CARREGANDO POSTOS COM EXCECOES PARA LISTAGEM NO FINAL DA TELA
$sql = "
SELECT
tbl_posto.posto,
tbl_posto.nome,
tbl_posto_fabrica.tipo_posto,
tbl_tipo_posto.descricao,
ARRAY_TO_STRING(
	ARRAY (
		SELECT servico_realizado || '=' || percentual_acrescimo
		FROM tbl_mao_obra_servico_realizado_excecao
		WHERE tbl_mao_obra_servico_realizado_excecao.posto=tbl_posto.posto
	),
	','
) AS servico_realizado

FROM
tbl_posto
JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto

WHERE
tbl_posto_fabrica.fabrica=$login_fabrica
AND tbl_posto.posto IN
(
	SELECT
	DISTINCT(tbl_posto_fabrica.posto)

	FROM
	tbl_posto_fabrica
	JOIN tbl_mao_obra_servico_realizado_excecao ON tbl_posto_fabrica.posto=tbl_mao_obra_servico_realizado_excecao.posto

	WHERE
	tbl_posto_fabrica.fabrica=$login_fabrica
)
";
$res_excecoes = pg_exec($con, $sql);

for($i = 0; $i < pg_num_rows($res_dados); $i++)
{
	$cods = pg_result($res_dados, $i, servico_realizado);
	$codp = pg_result($res_dados, $i, tipo_posto);
	$valor = pg_result($res_dados, $i, mao_de_obra);

	$valor = explode(".", $valor);
	$valor[1] = substr($valor[1] . "00", 0, 2);
	$valor = implode(".", $valor);

	$dados[$cods][$codp] = $valor;
}


$nump = pg_num_rows($res_posto);
$nums = pg_num_rows($res_servico);
$nume = pg_num_rows($res_excecoes);
if ($nump < 2) $colunas = 2; else $colunas = $nump;

//************************* HTML DA TELA *************************//
echo "
<table class=srtpprincipal align=center>
	<tr>
		<td colspan=" . ($colunas + 1) . " class=srtptitulo>
			$title
		</td>
	</tr>
	<tr>
		<td colspan=" . ($colunas + 1) . " class=srtpinstrucoes>
			Digite os números nos campos desejados, separando os decimais por ponto<br>Para gravar as alterações, clique no botão <u>[Gravar]</u><br>ou pressione a tecla <u>[Enter]</u> quando estiver editando um campo
		</td>
	</tr>";

echo "
	<form name=formcadastro id=formcadastro method=post onsubmit='return formcadastro_onsubmit();'>";

for($s = 0; $s < $nums; $s++)
{
	/************************* CABECALHO CORPO *************************/
	$linhastela = $s % 15;

	if($linhastela == 0)
	{
		echo "
	<tr>
		<td>
		</td>";

	for($p = 0; $p < $nump; $p++)
	{
		$descricao = mb_strtoupper(pg_result($res_posto, $p, descricao));

		echo "
		<td class=srtptituloposto width=110>
			$descricao
		</td>";
	}

	for($p = $nump; $p < $colunas; $p++)
	{
		echo "
		<td class=srtptituloposto width=110>
		</td>";
	}

	echo "
	</tr>";
	}

	/************************* FIM CABECALHO CORPO *************************/

	$descricao = mb_strtoupper(pg_result($res_servico, $s, descricao));
	$cods = pg_result($res_servico, $s, servico_realizado);
	$linha = $s % 2;

	echo "
	<tr class=srtplinha$linha>
		<td class=srtptituloservico>
			$descricao
		</td>";

	for($p = 0; $p < $nump; $p++)
	{
		$codp = pg_result($res_posto, $p, tipo_posto);
		if(!isset($dados[$cods][$codp])) $dados[$cods][$codp] = "0.00";
		echo "
		<td>
			<input class='srtplinha$linha' size='15' type='text' id='servico_posto" . $cods . "_" . $codp . "' name='servico_posto[$cods][$codp]' value='" . $dados[$cods][$codp] . "' onkeyup='apenas_numeros(this); atualiza_valor_final(document.getElementById(\"servico_posto_excecao\" + $cods), $cods);' onblur='formatar_numeros(this);' />
		</td>";
	}

	for($p = $nump; $p < $colunas; $p++)
	{
		echo "
		<td>
		</td>";
	}

	echo "
	</tr>";

	if ($linhastela == 14)
	{
		echo "
	<tr>
		<td class=srtpopcoes colspan=" . ($colunas + 1) . ">
			<input type=submit value=Gravar id=gravar name=gravar />
		</td>
	</tr>";
	}
}


	if ($linhastela != 14)
	{
		echo "
	<tr>
		<td class=srtpopcoes colspan=" . ($colunas + 1) . ">
			<input type=submit value=Gravar id=gravar name=gravar />
		</td>
	</tr>";
	}

if ($colunas > 2)
	$colString = "
		<td colspan=" . ($colunas - 2) . ">
		</td>";

echo "
	<tr>
		<td class=srtptituloexcecao colspan=" . ($colunas + 1) . ">
			CADASTRAR EXCEÇÃO PARA UM POSTO
		</td>
	</tr>
	<tr>
		<td colspan=" . ($colunas + 1) . " class=srtpinstrucoes id=instrucoes_excecao>
			Selecione um posto, digitando no campo <u>Posto</u> o nome ou código do posto desejado
		</td>
	</tr>
	<tr valign=middle>
		<td class=srtpopcoes colspan=" . ($colunas + 1) . ">
			Posto: <input type=text id=postonome name=postonome size=100 onfocus='autocompletar(this.value)' />
			<input type=button value=Alterar id=btnalterar name=btnalterar disabled onclick='alterar_posto();'>
			<input type=hidden id=postoid name=postoid />
			<input type=hidden id=postotipo name=postotipo value=61 />
		</td>
	</tr>";

$limpar = "";

for($s = 0; $s < $nums; $s++)
{
	$descricao = mb_strtoupper(pg_result($res_servico, $s, descricao));
	$cods = pg_result($res_servico, $s, servico_realizado);
	$linha = $s % 2;
	$linhastela = $s % 15;
	
	if ($linhastela == 0)
	{
		echo "
	<tr class=srtplinha1>
		<td class=srtptituloservico>
		</td>
		<td class=srtptituloposto>
			% ACRÉSCIMO
		</td>
		<td class=srtptituloposto>
			VALOR FINAL
		</td>
		$colString
	</tr>";
	}

	echo "
	<tr class=srtplinhaexcecao$linha>
		<td class=srtptituloservico>
			$descricao
		</td>
		<td>
			<input onkeyup='apenas_numeros(this); atualiza_valor_final(this, $cods);' class=srtplinhaexcecao$linha size=15 type=text name='servico_posto_excecao[$cods]' id='servico_posto_excecao$cods' value='' disabled onblur='formatar_numeros(this);' />
		</td>
		<td>
			<input class=srtplinhaexcecao$linha size=15 type=text readonly tabindex=-1 name='servico_posto_excecao_final[$cods]' id='servico_posto_excecao_final$cods' value=''/>
		</td>
		$colString
	</tr>";

	$limpar .= "$('#servico_posto_excecao$cods').val(''); $('#servico_posto_excecao_final$cods').val(''); ";

	if ($linhastela == 14)
	{
		echo "
	<tr>
		<td class=srtpopcoes colspan=" . ($colunas + 1) . ">
			<input type=submit value=Gravar id=gravar name=gravar /> <input type=button value='Limpar Todos' onclick=\"$limpar\">
		</td>
	</tr>";
	}
}

if ($linhastela != 14)
{
	echo "
	<tr>
		<td class=srtpopcoes colspan=" . ($colunas + 1) . ">
			<input type=submit value=Gravar id=gravar name=gravar /> <input type=button value='Limpar Todos' onclick=\"$limpar\">
		</td>
	</tr>";
}

echo "
	</form>";

//************************* POSTOS COM EXCECOES *************************//

if ($colunas > 2)
	$colString = "
		<td colspan=" . ($colunas-1) . ">
		</td>";

if ($nume > 0) $msg = "Selecione um posto para alterar/visualizar as exceções";
else $msg = "Nenhum posto com exceções";

echo "
	<tr>
		<td class=srtptituloexcecaolista colspan=" . ($colunas + 1) . ">
			POSTOS COM EXCEÇÕES
		</td>
	</tr>
	<tr>
		<td colspan=" . ($colunas + 1) . " class=srtpinstrucoes id=instrucoes_excecao>
			$msg
		</td>
	</tr>";

for($e = 0; $e < $nume; $e++)
{
	$posto = pg_result($res_excecoes, $e, posto);
	$nome = pg_result($res_excecoes, $e, nome);
	$tipo = pg_result($res_excecoes, $e, tipo_posto);
	$descricao = pg_result($res_excecoes, $e, descricao);
	$servicos = pg_result($res_excecoes, $e, servico_realizado);
	$linha = $e % 2;

	echo "
	<tr class=srtplinha$linha>
		<td colspan=" . ($colunas + 1) . ">
			<a href=\"javascript:carrega_posto($posto, $tipo, '[$posto] $nome - $descricao', '$servicos')\">[$posto] $nome - $descricao
		</td>
	</tr>";
}

echo "
</table>
<table>
<tr height=30><td height=30></td></tr>
</table>";

//************************* FIM HTML DA TELA *************************//

?>

<style>
	table
	{
		border-collapse: collapse;
	}

	td
	{
		border-collapse: collapse;
	}

	#postonome
	{
		width: 598px;
	}

	.srtperro
	{
		color: #FF0000;
		font-size: 11pt;
		margin-top: 2px;
		margin-bottom: 5px;
	}

	.srtplinha0
	{
		background-color: #DDDDDD;
		border: solid 1px #888888;
	}

	.srtplinha1
	{
		background-color: #FFFFFF;
		border: solid 1px #888888;
	}

	.srtplinhaexcecao0
	{
		background-color: #BBDDBB;
		border: solid 1px #888888;
	}

	.srtplinhaexcecao1
	{
		background-color: #FFFFFF;
		border: solid 1px #888888;
	}

	.srtpinstrucoes
	{
		background-color: #EEEEEE;
		color: #000000;
		border-bottom: 1px solid #555555;
	}

	.srtpopcoes
	{
		text-align: center;
		background-color: #BBBBEE;
		height: 30px;
	}

	.srtpprincipal
	{
		font-size: 10pt;
		border: 1px solid #777777;
	}

	.srtptitulo
	{
		text-align: center;
		background-color: #222266;
		height: 30px;
		font-size: 12pt;
		font-weight: bold;
		color: #FFFFFF;
	}

	.srtptituloexcecao
	{
		text-align: center;
		background-color: #004400;
		height: 30px;
		font-size: 12pt;
		font-weight: bold;
		color: #FFFFFF;
	}

	.srtptituloexcecaolista
	{
		text-align: center;
		background-color: #004400;
		height: 30px;
		font-size: 12pt;
		font-weight: bold;
		color: #FFFFFF;
	}

	.srtptituloposto
	{
		font-weight: bold;
		text-align: center;
	}

	.srtptituloservico
	{
		font-weight: bold;
		text-align: right;
	}
</style>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>

<script language="javascript">

function apenas_numeros(origem, decimais)
{
	if (typeof decimais == "undefined") decimais = 2;		//SE NÃO FOR PASSADO PARÂMETRO EM decimais, DEFINE PADRÃO 2
	
	origem.value=origem.value.replace(/,/, ".");			//PERMITE QUE O USUÁRIO DIGITE A VÍRGULA, MAS COLOCA PONTO NO LUGAR
	
	parts = origem.value.split(".", 2);						//CONSIDERANDO APENAS O PRIMEIRO SEPARADOR DECIMAL E DESCARTANDO DEMAIS

	if(parts.length == 2) parts[1] = parts[1].substr(0, decimais);
	origem.value = parts.join(".");

	if (origem.value == ".") origem.value = "0.";			//CASO NÃO TENHAM NÚMEROS ANTES DO SEPARADOR DECIMAL, ACREDENTA ZERO

	origem.value=origem.value.replace(/[^0-9.]/gi, "");		//EXCLUINDO O QUE NÃO FOR NÚMEROS OU PONTO
}

function formatar_numeros(origem, decimais)
{
	if (typeof decimais == "undefined") decimais = 2;		//SE NÃO FOR PASSADO PARÂMETRO EM decimais, DEFINE PADRÃO 2

	parts = origem.value.split(".", 2);						//CONSIDERANDO APENAS O PRIMEIRO SEPARADOR DECIMAL E DESCARTANDO DEMAIS
	if(parts[0].length == 0) parts[0] = "0";
	if(parts.length == 2)
	{
		parts[1] += "00";
		parts[1] = parts[1].substr(0, decimais);
	}
	else
	{
		parts[1] = "00";
	}
	origem.value = parts.join(".");
}

function atualiza_valor_final(origem, cods)
{
	if ($("#postoid").val() != "")
	{
		valorpadrao = $("#servico_posto" + cods + "_" + $("#postotipo").val()).val();
		parseFloat(valorpadrao);

		resultado = origem.value;
		parseFloat(resultado);
		resultado = 1 + resultado/100;
		resultado = resultado * valorpadrao;
		resultado = resultado.toFixed(2);

		$("#servico_posto_excecao_final" + cods).val(resultado);
	}
}

function alterar_posto()
{
	if(confirm("Atualmente você está editando EXCEÇÕES para o posto:\n" + $("#postonome").val() + "\n\nDeseja alterar EXCEÇÕES de outro posto?\n\nATENÇÃO: Todas as informações não salvas serão perdidas"))
	{
<?

for($s = 0; $s < $nums; $s++)
{
	$cods = pg_result($res_servico, $s, servico_realizado);

	echo "
		$(\"#servico_posto_excecao$cods\").attr(\"disabled\", true);
		$(\"#servico_posto_excecao$cods\").val(\"\");
		$(\"#servico_posto_excecao_final$cods\").val(\"\");";
}

?>
		$("#btnalterar").attr("disabled", true);
		$("#postonome").attr("disabled", false);
		$("#postonome").val("");
		$("#postoid").val("");
		$("#postonome").focus();

		return true;
	}
	else
		return false;
}

function formcadastro_onsubmit()
{
	if ($("#postoid").val() != "")
	{
		return(confirm("Gravar todas as alterações feitas no <?=$title?> e nas EXCEÇÕES para o posto " + $("#postonome").val() + "?"));
	}
	else
	{
		return(confirm("Gravar todas as alterações feitas no <?=$title?>?"));
	}
}

function autocompletar(conteudo)
{
	var url = "<?echo $PHP_SELF;?>?q=" + conteudo + "&fabrica=<? echo $login_fabrica; ?>";
	
	$('#postonome').autocomplete(url, {
		minChars: 3,
		delay: 150,
		width: 600,
		scroll: true,
		scrollHeight: 200,
		matchContains: false,
		highlightItem: false,
		formatItem: function (row)   {return row[1]},
		formatResult: function(row)  {return row[1];}
	});
	
	$('#postonome').result(function(event, data, formatted) {
		$("#postoid").val(data[0]);
		$("#postonome").val(data[1]);
		$("#postotipo").val(data[2]);

		$("#postonome").attr("disabled", true);
		$("#btnalterar").attr("disabled", false);
		$("#instrucoes_excecao").html("Digite os números nos campos desejados, separando os decimais por ponto<br>Para editar outro posto, clique no botão <u>[Alterar]</u><br>Para excluir uma exceção, preencha o campo com valor 0.00 (zero)<br>Para gravar as alterações, clique no botão <u>[Gravar]</u><br>ou pressione a tecla <u>[Enter]</u> quando estiver editando um campo");
<?

for($s = 0; $s < $nums; $s++)
{
	$cods = pg_result($res_servico, $s, servico_realizado);

	echo "
		$(\"#servico_posto_excecao$cods\").attr(\"disabled\", false);";
}

	$cods = pg_result($res_servico, 0, servico_realizado);
	echo "
		$(\"#servico_posto_excecao$cods\").focus();"

?>
		if (data[3] != "")
		{
			data_parts = data[3].split(",")
			
			for(var i in data_parts)
			{
				data_parts[i] = data_parts[i].split("=");
				$("#servico_posto_excecao" + data_parts[i][0]).val(data_parts[i][1]);
				origem = document.getElementById("servico_posto_excecao" + data_parts[i][0]);
				atualiza_valor_final(origem, data_parts[i][0]);
				formatar_numeros(origem);
			}
			$("#servico_posto_excecao" + data_parts[0][0]).focus();
		}

	});
}

function carrega_posto(posto, tipo, nome, servicos)
{
	if ($("#postoid").val() != "")
		carregarok = alterar_posto();
	else
		carregarok = true
	
	if (carregarok)
	{
		$("#postoid").val(posto);
		$("#postonome").val(nome);
		$("#postotipo").val(tipo);

		$("#postonome").attr("disabled", true);
		$("#btnalterar").attr("disabled", false);
		$("#instrucoes_excecao").html("Digite os números nos campos desejados, separando os decimais por ponto<br>Para editar outro posto, clique no botão <u>[Alterar]</u><br>Para excluir uma exceção, preencha o campo com valor 0.00 (zero)<br>Para gravar as alterações, clique no botão <u>[Gravar]</u><br>ou pressione a tecla <u>[Enter]</u> quando estiver editando um campo");
<?

for($s = 0; $s < $nums; $s++)
{
	$cods = pg_result($res_servico, $s, servico_realizado);

	echo "
		$(\"#servico_posto_excecao$cods\").attr(\"disabled\", false);";
}

	$cods = pg_result($res_servico, 0, servico_realizado);
	echo "
		$(\"#servico_posto_excecao$cods\").focus();"

?>
		if (servicos != "")
		{
			data_parts = servicos.split(",")
			
			for(var i in data_parts)
			{
				data_parts[i] = data_parts[i].split("=");
				$("#servico_posto_excecao" + data_parts[i][0]).val(data_parts[i][1]);
				origem = document.getElementById("servico_posto_excecao" + data_parts[i][0]);
				atualiza_valor_final(origem, data_parts[i][0]);
				formatar_numeros(origem);
			}
			$("#servico_posto_excecao" + data_parts[0][0]).focus();
		}
	}
}

</script>