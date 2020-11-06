<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,auditoria";
include "autentica_admin.php";

if($login_fabrica != 1) {
	include("menu_os.php");
	exit;
}

include "funcoes.php";

$erro = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if(strlen($_POST["opcao1"]) > 0) $opacao1 = $_POST["opcao1"];
if(strlen($_POST["opcao2"]) > 0) $opacao2 = $_POST["opcao2"];

if (strlen($acao) > 0) {

	if (strlen($opacao1) == 0 && strlen($opacao2) == 0) {
		$erro .= " Selecione o tipo da pesquisa. ";
	}

	##### Pesquisa entre datas #####
	if (strlen($opacao1) > 0) {
		$x_data_inicial = trim($_POST["data_inicial"]);
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		$x_data_final   = trim($_POST["data_final"]);
		$x_data_final   = fnc_formata_data_pg($x_data_final);

		$aux_data_inicial = str_replace("/","",$x_data_inicial);
		$aux_data_inicial = str_replace("-","",$aux_data_inicial);
		$aux_data_inicial = str_replace(".","",$aux_data_inicial);
		$aux_data_inicial = fnc_so_numeros($aux_data_inicial);
		
		$aux_data_final = str_replace("/","",$x_data_final);
		$aux_data_final = str_replace("-","",$aux_data_final);
		$aux_data_final = str_replace(".","",$aux_data_final);
		$aux_data_final = fnc_so_numeros($aux_data_final);

		if (strlen($aux_data_final) < 8) $erro = "Data final em formato inválido";

		if (strlen($aux_data_inicial) < 8) $erro = "Data inicial em formato inválido";

		if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null") {
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial = substr($x_data_inicial, 8, 2);
			$mes_inicial = substr($x_data_inicial, 5, 2);
			$ano_inicial = substr($x_data_inicial, 0, 4);
			$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
		}else{
			$erro = "Data Inválida";
		}
		if (strlen($x_data_final) > 0 && $x_data_final != "null") {
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final = substr($x_data_final, 8, 2);
			$mes_final = substr($x_data_final, 5, 2);
			$ano_final = substr($x_data_final, 0, 4);
			$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
		}else{
			$erro = "Data Inválida";
		}
//Início Validação de Datas
if($data_inicial){
$dat = explode ("/", $data_inicial );//tira a barra
$d = $dat[0];
$m = $dat[1];
$y = $dat[2];
if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
}
if($data_final){
$dat = explode ("/", $data_final );//tira a barra
$d = $dat[0];
$m = $dat[1];
$y = $dat[2];
if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
}
if(strlen($erro)==0){
$d_ini = explode ("/", $data_inicial);//tira a barra
$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


$d_fim = explode ("/", $data_final);//tira a barra
$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

if($nova_data_final < $nova_data_inicial){
$erro = "Data Inválida.";
}

$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
$nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
$cont = 0;
while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {
$nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
$cont++;
}

if($cont > 30){
$erro="O intervalo entre as datas não pode ser maior que 30 dias.";
}

//Fim Validação de Datas
}
	}

	##### Pesquisa posto #####
	if (strlen($opacao2) > 0) {
		$posto_codigo = trim($_POST["posto_codigo"]);
		$posto_nome   = trim($_POST["posto_nome"]);
		$sql =	"SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica  USING (posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
		if (strlen($posto_codigo) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		if (strlen($posto_nome) > 0)   $sql .= " AND tbl_posto.nome = '$posto_nome';";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = pg_result($res,0,0);
		}else{
			$erro = " Posto não encontrado. ";
		}
	}
}

$layout_menu = "gerencia";
$title = "RELAÇÃO DO SISTEMA";

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
margin: 0 auto;
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

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<br>

<? if (strlen($erro) > 0) { ?>
<table width="700px" border="0" cellspacing="0" cellpadding="2" align="center" class="msg_erro">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>

<? } ?>
<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width="700px" border="0" class='formulario' cellspacing="0" cellpadding="2" align="center">
	<tr class='titulo_tabela'>
		<td colspan="5">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao1" value="t" class="frm" <? if (strlen($opcao1) > 0) echo "checked"; ?>> Entre datas</td>
		<td align="left">Data Inicial</td>
		<td align="left">Data Final</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>&nbsp;</td>
		<td align="left">
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
		</td>
		<td align="left">
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align="left"><input type="checkbox" name="opcao2" value="t" class="frm" <? if (strlen($opcao2) > 0) echo "checked"; ?>> Posto</td>
		<td align="left">Código</td>
		<td align="left">Razão Social</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>&nbsp;</td>
		<td align="left">
			<input type="text" name="posto_codigo" size="8" value="<? if (strlen($posto_codigo) > 0) echo $posto_codigo; ?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome,'codigo')" style="cursor: hand;" alt="Clique aqui para pesquisar postos pelo código">
		</td>
		<td align="left">
			<input type="text" name="posto_nome" size="15" value="<? if (strlen($posto_nome) > 0) echo $posto_nome; ?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome,'nome')" style="cursor: hand;" alt="Clique aqui para pesquisas postos pelo nome">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="5"><input type="submit" style="background:url(imagens/btn_pesquisar_400.gif); width:400px; height:22px;" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" value=" "></td>
	</tr>
</table>
<div id="msg" style="display:none; width:700px; margin:auto;"></div>
<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {

	##### OS FINALIZADAS #####
	$sql = "SELECT	tbl_os.os ,
					tbl_os.sua_os ,
					tbl_os_extra.extrato ,
					tbl_produto.referencia AS produto_referencia ,
					tbl_produto.descricao AS produto_descricao ,
					tbl_produto.voltagem AS produto_voltagem ,
					tbl_posto_fabrica.codigo_posto AS posto_codigo ,
					tbl_posto.nome AS posto_nome ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada ,
					TO_CHAR(tbl_os.mao_de_obra + tbl_os.pecas,'999,990.99') AS total ,
					A.peca_referencia ,
					A.peca_nome ,
					A.qtde ,
					A.preco
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			LEFT JOIN (
				SELECT tbl_os_produto.os ,
						tbl_peca.referencia AS peca_referencia ,
						tbl_peca.descricao  AS peca_nome ,
						tbl_os_item.qtde ,
						tbl_os_item.preco
				FROM tbl_os_item
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
			) AS A ON A.os = tbl_os.os
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_extrato.aprovado NOTNULL";
			if (strlen($opacao1) > 0)
				$sql.= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			if (strlen($opacao2) > 0)
				$sql.="	AND tbl_posto.posto = $posto";
			$sql.= " ORDER BY tbl_os_extra.extrato, tbl_os.sua_os";

	##### OS FINALIZADAS #####
	$sql = "SELECT	tbl_os.os ,
					tbl_os.sua_os ,
					tbl_os_extra.extrato ,
					tbl_produto.referencia AS produto_referencia ,
					tbl_produto.descricao AS produto_descricao ,
					tbl_produto.voltagem AS produto_voltagem ,
					tbl_posto_fabrica.codigo_posto AS posto_codigo ,
					tbl_posto.nome AS posto_nome ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada ,
					TO_CHAR(tbl_os.mao_de_obra + tbl_os.pecas,'999,990.99') AS total ,
					A.peca_referencia ,
					A.peca_nome ,
					A.qtde ,
					A.preco
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			LEFT JOIN (
				SELECT tbl_os_produto.os ,
						tbl_peca.referencia AS peca_referencia ,
						tbl_peca.descricao  AS peca_nome ,
						tbl_os_item.qtde ,
						tbl_os_item.preco
				FROM tbl_os_item
				JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
			) AS A ON A.os = tbl_os.os
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_extrato.aprovado NOTNULL";
			if (strlen($opacao1) > 0)
				$sql.= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
			if (strlen($opacao2) > 0)
				$sql.="	AND tbl_posto.posto = $posto";
			$sql.= " ORDER BY tbl_os_extra.extrato, tbl_os.sua_os";

	//$res = pg_exec($con,$sql);
	
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res)."<br>";
	
/*	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		echo pg_result($res,$i,sua_os)." - ".pg_result($res,$i,peca_referencia)."<br>";
	}
*/
	echo "<br>";
	
	$sql =	"SELECT tbl_os.os                                                     ,
					tbl_os.sua_os                                                 ,
					tbl_os_extra.extrato                                          ,
					tbl_produto.referencia                  AS produto_referencia ,
					tbl_produto.descricao                   AS produto_descricao  ,
					tbl_produto.voltagem                    AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto          AS posto_codigo       ,
					tbl_posto.nome                          AS posto_nome         ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada         ,
					TO_CHAR(tbl_os.mao_de_obra + tbl_os.pecas,'999,990.99') AS total
			FROM tbl_os
			JOIN tbl_os_extra       ON  tbl_os_extra.os           = tbl_os.os
			JOIN tbl_extrato        ON  tbl_extrato.extrato       = tbl_os_extra.extrato
			JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_extrato.aprovado NOTNULL";
	if (strlen($opcao1) > 0)
		$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	if (strlen($opcao2) > 0)
		$sql .= " AND tbl_posto.posto = $posto";
	$sql .= " ORDER BY tbl_os_extra.extrato, tbl_os.sua_os;";

	if (strlen($opcao1) > 0) $cond_1 = " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	if (strlen($opcao2) > 0) $cond_2 = " AND tbl_posto.posto = $posto";


	$sql = "SELECT os,extrato
		INTO TEMP tmp_auditoria_$login_admin
		FROM tbl_extrato
		JOIN tbl_os_extra USING(extrato)
		WHERE fabrica = $login_fabrica
		AND aprovado IS NOT NULL $cond_1 $cond_2;

		CREATE INDEX tmp_auditoria_OS_$login_admin ON tmp_auditoria_$login_admin(os);

		SELECT tbl_os.os                                                     ,
					tbl_os.sua_os                                                 ,
					x.extrato                                          ,
					tbl_produto.referencia                  AS produto_referencia ,
					tbl_produto.descricao                   AS produto_descricao  ,
					tbl_produto.voltagem                    AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto          AS posto_codigo       ,
					tbl_posto.nome                          AS posto_nome         ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada         ,
					TO_CHAR(tbl_os.mao_de_obra + tbl_os.pecas,'999,990.99') AS total
			FROM tbl_os
			JOIN tmp_auditoria_$login_admin X ON x.os = tbl_os.os
			JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica			";

	$sql .= " ORDER BY x.extrato, tbl_os.sua_os;";

	$res1 = pg_exec($con,$sql);
	
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res1)."<br>";
	if(pg_numrows($res1) == 0){
		echo '<div id="ret_msg" style="display:none;">Não Foram encontrados Resultados para esta Consulta.</div>';
?>
		
		<script type="text/javascript">
			$('#ret_msg').appendTo('#msg').css("display",'block');
			$("#msg").show();
		</script>
<?	
	}
	
	else if (pg_numrows($res1) > 0) {
		echo "<table cellpadding='2' cellspacing='1' class='tabela' align='center'>\n";
		
		for ($i = 0 ; $i < pg_numrows($res1) ; $i++) {
			$os                 = trim(pg_result($res1,$i,os));
			$sua_os             = trim(pg_result($res1,$i,sua_os));
			$extrato            = trim(pg_result($res1,$i,extrato));
			$produto_referencia = trim(pg_result($res1,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res1,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res1,$i,produto_voltagem));
			$posto_codigo       = trim(pg_result($res1,$i,posto_codigo));
			$posto_nome         = trim(pg_result($res1,$i,posto_nome));
			$finalizada         = trim(pg_result($res1,$i,finalizada));
			$total              = trim(pg_result($res1,$i,total));
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			flush();
			if ($posto_antigo != $posto_codigo) {
				echo "<tr class='titulo_tabela'>\n";
				echo "<td colspan='9'>" . $posto_codigo . " - " . $posto_nome . "</td>\n";
				echo "</tr>\n";
				echo "<tr class='titulo_coluna'>\n";
				echo "<td>Extrato</td>\n";
				echo "<td>OS</td>\n";
				echo "<td>Produto</td>\n";
				echo "<td>Total OS</td>\n";
				echo "<td width='310'>Peça</td>\n";
				echo "<td width='70'>Defeito</td>\n";
				echo "<td width='45'>QTDE</td>\n";
				echo "<td width='65'>Preço</td>\n";
				echo "<td width='25'>&nbsp;</td>\n";
				echo "</tr>\n";
			}
			
			echo "<tr bgcolor='$cor'>\n";
			echo "<td align='center'>" . $extrato . "</td>\n";
			echo "<td align='center'>";
			if ($login_fabrica == 1) echo $posto_codigo;
			echo $sua_os . "</td>\n";
			echo "<td align='center'><acronym title='Referência: $produto_referencia | Descrição: $produto_descricao | Voltagem: $produto_voltagem' style='cursor: hand;'>" . $produto_referencia . "</acronym></td>\n";
			echo "<td align='right' nowrap>R$ " . number_format($total,2,",",".") . "</td>\n";
			
			echo "<td colspan='5'>\n";
			$sql =	"SELECT tbl_peca.referencia AS peca_referencia ,
							tbl_peca.descricao  AS peca_nome       ,
							tbl_os_item.qtde                       ,
							tbl_os_item.custo_peca AS preco        ,
							tbl_defeito.descricao AS defeito       
					FROM tbl_os_item
					JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_peca       ON tbl_peca.peca             = tbl_os_item.peca
					JOIN tbl_defeito    ON tbl_defeito.defeito       = tbl_os_item.defeito
					WHERE tbl_os_produto.os = $os;";
			$res2 = pg_exec($con,$sql);
			if (pg_numrows($res2) > 0) {
				echo "<table border='0' cellpadding='2' cellspacing='1'>\n";
				for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
					$peca_referencia = trim(pg_result($res2,$j,peca_referencia));
					$peca_nome       = trim(pg_result($res2,$j,peca_nome));
					$qtde            = trim(pg_result($res2,$j,qtde));
					$preco           = trim(pg_result($res2,$j,preco));
					$defeito         = trim(pg_result($res2,$j,defeito));
					
					$bottom = pg_numrows($res2) - 1;
					if ($j == $bottom) $bottom = $cor;
					else               $bottom = "#000000";
					
					echo "<tr bgcolor='$cor'>\n";
					echo "<td width='310px' align='left' >" . $peca_referencia . " - " . $peca_nome . "</td>\n";
					echo "<td width='70px' >" . $defeito . "</td>\n";
					echo "<td width='45' >" . $qtde . "</td>\n";
					echo "<td width='62' align='right'  nowrap>R$ " . number_format($preco,2,",",".") . "</td>\n";
					echo "<td ><input type='checkbox'></td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			
			$posto_antigo = $posto_codigo;
		}
		echo "</table>\n";
		echo "<br>\n";
	}
}

include "rodape.php"; 
?>