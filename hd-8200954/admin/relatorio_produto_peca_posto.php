
<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if ($botao == "BUSCAR") {
	$data_inicial    = trim($_POST["data_inicial"]);
	$data_final      = trim($_POST["data_final"]);
    $linha           = $_POST["linha"];
	$marca           = $_POST["marca"];
	$familia         = $_POST["familia"];
	$peca            = trim($_POST["peca"]);
	$peca_referencia = trim($_POST["peca_referencia"]);
	$peca_descricao  = trim($_POST["peca_descricao"]);

	if (strlen($data_inicial) == 0) $erro = " Data Inválida ";
	if (strlen($data_final) == 0)   $erro = " Data Inválida ";

	//Início Validação de Datas
	if(strlen($erro)==0){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if(strlen($erro)==0){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if(strlen($erro)==0){
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$x_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final);//tira a barra
		$x_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($x_data_final < $x_data_inicial){
			$erro = "Data Inválida.";
		}

		//Fim Validação de Datas
	}

	

	
	if(strlen($erro)==0){
        if($login_fabrica != 1){
            if (strlen($linha) == 0)
                $erro = " Preencha o Campo Linha ";
            if(strlen($erro)==0){
                if (strlen($familia) == 0)
                    $erro = " Preencha o Campo Família ";
            }
		}
		if(strlen($erro)==0){
			if (strlen($peca) > 0 OR strlen($peca_referencia) > 0 OR strlen($peca_descricao) > 0) {
				$sql =	"SELECT peca, referencia, descricao
						FROM tbl_peca
						WHERE fabrica = $login_fabrica";
				if (strlen($peca) > 0) {
					$sql .= " AND peca = $peca;";
				}else{
					if (strlen($peca_referencia) > 0) {
						$peca_pesquisa = str_replace (".","",$peca_referencia);
						$peca_pesquisa = str_replace ("-","",$peca_pesquisa);
						$peca_pesquisa = str_replace ("/","",$peca_pesquisa);
						$peca_pesquisa = str_replace (" ","",$peca_pesquisa);

						$sql .= " AND referencia_pesquisa = '$peca_pesquisa'";
					}
					if (strlen($peca_descricao) > 0) $sql .= " AND descricao ILIKE '$peca_descricao';";
				}

				$res = pg_exec($con,$sql);

				if (pg_numrows($res) == 1) {
					$peca            = pg_result($res,0,peca);
					$peca_referencia = pg_result($res,0,referencia);
					$peca_descricao  = pg_result($res,0,descricao);
				}else{
					$erro = " Peça Digitada não Foi Encontrada ";
				}
			}else{
                if($login_fabrica != 1){
                    $erro = " Preencha o Campo Peça ";
                }
			}
		}
	}
}

$layout_menu = "auditoria";
$title = "RELATÓRIO DE PEÇA POR POSTO E POR PERÍODO";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

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

<script language="JavaScript">
function FuncCarregaFamilia (linha) {
	if (linha != "") {
		RemoveFamilia("familia");
		document.all.FrameRelatorio.src = "carrega_familia.php?linha=" + linha;
	}
}

function RemoveFamilia (objeto) {
	var tamanho = document.frm_relatorio[objeto].length;
	while (tamanho > 0) {
		document.frm_relatorio[objeto].remove(tamanho-1);
		tamanho--;
	}
}

function AdicionaFamilia (texto, valor, objeto) {
	linha = document.createElement("option");
	linha.text = texto;
	linha.value = valor;
	document.frm_relatorio[objeto].add(linha);
}

function fnc_pesquisa_posto(campo, campo2, tipo) {
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
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function FuncPesquisaPeca (peca, referencia, descricao, tipo, linha, familia) {
	if (tipo == "REFERENCIA") {
		var campo = referencia;
	}

	if (tipo == "DESCRICAO") {
		var campo = descricao;
	}

	if (campo.value != "") {
		var url = "";
		url = "peca_pesquisa_3.php?campo=" + campo.value + "&tipo=" + tipo + "&linha=" + linha.value + "&familia=" + familia.value;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.peca       = peca;
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}
</script>
<?
    include "javascript_calendario_new.php";
    include "../js/js_css.php";

?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<br>


<form method="POST" action="<?echo $PHP_SELF?>" name="frm_relatorio">

<input type="hidden" name="botao">

<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="formulario">
	<? if (strlen($erro) > 0) { ?>
			<tr class="msg_erro">
				<td colspan="4"><?echo $erro?></td>
			</tr>
	<? } ?>
	<tr class="titulo_tabela">
		<td colspan="4" height="20">Parâmetros de Pesquisa</td>
	</tr>
	<tr >
		<td width="80">&nbsp;</td>
		<td width="130">
			Data Início<br>
			<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
			
		</td>
		<td width="130">
			Data Final<br>
			<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
			
		</td>
		<td width="20">&nbsp;</td>
	</tr>
	
	<tr >
		<td width="80">&nbsp;</td>
		<td colspan="<?=($login_fabrica != 1 ? '3' : '')?>" align="left">
			Linha<br>
			<?
			$sql =	"SELECT linha, nome
					FROM tbl_linha
					WHERE fabrica = $login_fabrica
					ORDER BY nome;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='linha' size='1' class='frm' onChange='javascript: FuncCarregaFamilia (this.value);'>";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha      = trim(pg_result($res,$x,linha));
					$aux_linha_nome = trim(pg_result($res,$x,nome));
					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha) echo " selected";
					echo ">$aux_linha_nome</option>";
				}
				echo "</select>";
			}
			?>
		</td>
<?
if($login_fabrica == 1){
?>
        <td colspan="2">
            Marca <br />
            <select name="marca" class="frm">
                <option value=''>&nbsp;</option>
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
                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
            </select>
        </td>
<?
}
?>
	</tr>

	<tr >
		<td width="80">&nbsp;</td>
		<td colspan="3" align="left">
			Família<br>
			<?
			if (strlen($linha) > 0) {
				$sql =	"SELECT DISTINCT
								tbl_produto.familia,
								tbl_familia.descricao
						FROM tbl_produto
						JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia
						WHERE tbl_produto.linha = $linha
						AND   tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
				$res_familia = @pg_exec($con,$sql);
			}
				echo "<select name='familia' size='1' class='frm' style='width: 209px'>";
				if (@pg_numrows($res_familia) > 0) {
					echo "<option value=''></option>";
					for ($x = 0 ; $x < @pg_numrows($res_familia) ; $x++){
						$aux_familia           = trim(@pg_result($res_familia,$x,familia));
						$aux_familia_descricao = trim(@pg_result($res_familia,$x,descricao));
						echo "<option value='$aux_familia'";
						if ($familia == $aux_familia) echo " selected";
						echo ">$aux_familia_descricao</option>";
					}
				}
				echo "</select>";
			?>
		</td>
	</tr>
	
	<tr >
		<td>&nbsp;<input type="hidden" name="peca" value="<? echo $peca; ?>"></td>
		<td>Referência<br>
			<input type="text" name="peca_referencia" size="10" value="<?echo $peca_referencia?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: FuncPesquisaPeca (document.frm_relatorio.peca,document.frm_relatorio.peca_referencia,document.frm_relatorio.peca_descricao,'REFERENCIA',document.frm_relatorio.linha,document.frm_relatorio.familia)" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
		</td>
		<td>Descrição Peça<br>
			<input type="text" name="peca_descricao" size="25" value="<?echo $peca_descricao?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: FuncPesquisaPeca (document.frm_relatorio.peca,document.frm_relatorio.peca_referencia,document.frm_relatorio.peca_descricao,'DESCRICAO',document.frm_relatorio.linha,document.frm_relatorio.familia)" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr >
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr >
		<td colspan="4" align="center"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="javascript: if (document.frm_relatorio.botao.value == '' ) { document.frm_relatorio.botao.value='BUSCAR'; document.frm_relatorio.submit(); }else{ alert('Aguarde submissão'); }" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

<iframe style="visibility: hidden; position: absolute;" id="FrameRelatorio"></iframe>

</form>

<br>
<?
if (strlen($erro) == 0 && strlen($botao) > 0) {
	$x_data_inicial = str_replace("'","",$x_data_inicial);
	$x_data_final   = str_replace("'","",$x_data_final);


	$cond_linha   = "";
	$cond_familia = "";
	$cond_peca    = "";
	$cond_marca   = "";
    if(strlen($lnha) > 0){
        $cond_linha = "AND      tbl_linha.linha     = $linha";
    }

    if(strlen($familia) > 0){
        $cond_familia = "AND     tbl_familia.familia = $familia";
    }
    if(strlen($peca) > 0){
        $cond_peca = "AND     tbl_peca.peca       = $peca";
    }

	if(strlen($marca) > 0){
        $cond_marca = "AND     tbl_produto.marca = $marca";
	}

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto            ,
					tbl_posto.nome                            ,
					tbl_peca.referencia                       ,
					tbl_peca.descricao                        ,
					SUM(tbl_os_item.qtde)       AS qtde       ,
					SUM(tbl_os_item.custo_peca) AS custo_peca
					FROM tbl_produto
					JOIN tbl_linha              ON  tbl_linha.linha = tbl_produto.linha
												AND tbl_linha.fabrica = $login_fabrica
					JOIN tbl_familia            ON  tbl_familia.familia = tbl_produto.familia
												AND tbl_familia.fabrica = $login_fabrica
					JOIN tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_produto.produto
					JOIN tbl_peca               ON  tbl_peca.peca = tbl_lista_basica.peca
												AND tbl_peca.fabrica = $login_fabrica
					JOIN tbl_os                 ON  tbl_os.produto = tbl_produto.produto
					JOIN tbl_posto_fabrica      ON  tbl_posto_fabrica.posto = tbl_os.posto
												AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
					JOIN tbl_posto              ON  tbl_posto.posto = tbl_posto_fabrica.posto
					JOIN tbl_os_produto         ON  tbl_os_produto.os = tbl_os.os$linha
					JOIN tbl_os_item            ON  tbl_os_item.os_produto = tbl_os_produto.os_produto
												AND tbl_os_item.peca = tbl_peca.peca
					JOIN tbl_os_extra           ON  tbl_os_extra.os = tbl_os.os
					JOIN tbl_extrato            ON  tbl_extrato.extrato = tbl_os_extra.extrato
					JOIN tbl_extrato_financeiro ON  tbl_extrato_financeiro.extrato = tbl_extrato.extrato
					WHERE   tbl_produto.fabrica_i = $login_fabrica
					$cond_linha
					$cond_familia
					$cond_peca
					$cond_marca
					AND     tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
					GROUP BY tbl_posto_fabrica.codigo_posto ,
							 tbl_posto.nome                 ,
							 tbl_peca.referencia            ,
							 tbl_peca.descricao
					ORDER BY SUM(tbl_os_item.qtde), SUM(tbl_os_item.custo_peca);";
// exit(nl2br($sql));
	$res = pg_exec($con,$sql);
	
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
//	exit;
	
	if (pg_numrows($res) > 0) {
		echo "<table border='0' align='center' width='700' cellpadding='2' cellspacing='1' class='tabela'>";
		echo "<tr class='titulo_coluna' height='15'>";
		echo "<td>Código</TD>";
		echo "<td>Posto Nome</TD>";
		echo "<td>Referência</TD>";
		echo "<td>Peça Descrição</TD>";
		echo "<td>Qtde</TD>";
		echo "<td>Ttotal</TD>";
		echo "</tr>";

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$posto_codigo    = trim(pg_result($res,$x,codigo_posto));
			$posto_nome      = trim(pg_result($res,$x,nome));
			$peca_referencia = trim(pg_result($res,$x,referencia));
			$peca_descricao  = trim(pg_result($res,$x,descricao));
			$peca_qtde       = trim(pg_result($res,$x,qtde));
			$peca_custo      = trim(pg_result($res,$x,custo_peca));
			
			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td align='center'>" . $posto_codigo . "</td>";
			echo "<td align='left'>" . $posto_nome . "</td>";
			echo "<td align='center'>" . $peca_referencia . "</td>";
			echo "<td align='left'>" . $peca_descricao . "</td>";
			echo "<td align='center'>" . $peca_qtde . "</td>";
			echo "<td align='right'>" . number_format($peca_custo,2,",",".") . "</td>";
			echo "</tr>";
		}
		echo "</table>\n";
	}
	else{
		echo "Não Foram Encontrados Resultados para esta Pesquisa";
	}
}
include "rodape.php";
?>
