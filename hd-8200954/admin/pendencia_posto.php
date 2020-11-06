<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

if ($btn_finalizar == 1) {
	$data_inicial = trim($_POST["data_inicial_01"]);
	$data_final   = trim($_POST["data_final_01"]);

	if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }
   

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            $msg_erro = "Data Inválida";
        }
    }

	if(strlen($msg_erro)==0){
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month')) {
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
		}
	 }

	 if(strlen($msg_erro)==0){
		$aux_data_inicial = $aux_data_inicial." 00:00:00";
        $aux_data_final = $aux_data_final." 23:59:59";
	 }


	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	$codigo_posto = "";
	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

	if (strlen($msg_erro) == 0 && $login_fabrica == 14) {
		$posto_codigo = trim($_POST["posto_codigo"]);
		$posto_nome   = trim($_POST["posto_nome"]);
		if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto_fabrica.posto        ,
					tbl_posto_fabrica.codigo_posto ,
					tbl_posto.nome
				FROM tbl_posto
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
			if (strlen($posto_codigo) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
			if (strlen($posto_nome) > 0)   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = pg_result($res,0,posto);
				$posto_codigo = pg_result($res,0,codigo_posto);
				$posto_nome   = pg_result($res,0,nome);

				$mostraMsgPosto = "<br>no POSTO $posto_codigo - $posto_nome";
			}else   $msg_erro .= " Posto não encontrado<br>";
		}
	}

	if (strlen($msg_erro) == 0) $listar = "ok";

	if (strlen($msg_erro) > 0) {
		$data_inicial = trim($_POST["data_inicial_01"]);
		$data_final   = trim($_POST["data_final_01"]);
		$estado       = trim($_POST["estado"]);
		$pais         = trim($_POST["pais"]);
		$criterio     = trim($_POST["criterio"]);
		$tipo_os = trim($_POST['tipo_os']);
		$msg = $msg_erro;
	}
}


$layout_menu = "gerencia";
$title = "RELATÓRIO DE PENDÊNCIA DOS POSTOS";

include "cabecalho.php";

include "javascript_pesquisas.php";
include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>
<style>
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.espaco td{
	padding:10px 0 10px;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
   font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<?
if (strlen($msg) > 0){
	echo "<table width='700' border='0' cellpadding='0' cellspacing='1' align='center'>";
	echo "<tr><td class='msg_erro'>$msg</td></tr>";
	echo "</table>";
}
?>
<TABLE width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario'>

<tr><td class="titulo_tabela" colspan="3">Parâmetros de Pesquisa</td></tr>

<TBODY>
<TR>
	<td width="30%">&nbsp;</td>
	<TD width="150px">
		Data Inicial<br>
		<INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? echo !empty ($data_inicial) ? $data_inicial : ''; ?>">
	</TD>
	<TD align="left">
		Data Final<br>
		<INPUT class="frm" size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" >
	</TD>
</TR>
<?/*-
<TR>
	<td colspan = '2'>
		Por região<br>
		<select class='frm' name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
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
</TR>

<TR>
	<TD nowrap>
			Código do Posto<br>
			<input type="text" class='frm' name="posto_codigo" size="10" value="<?echo $posto_codigo?>">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
	</TD>
	<TD nowrap>
			Razão Social do Posto<br>
			<input type="text" class='frm' name="posto_nome" size="25" value="<?echo $posto_nome?>">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
	</TD>
</TR>
*/
?>
	<TR>
		<TD colspan="3" align="center" style="padding:10px 0 10px;">
			<input type='hidden' name='btn_finalizar' value='0'>
			<input type="button" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " value="Pesquisar" />
		</TD>
	</TR>
</TABLE>

</FORM>

</DIV>

<?
flush();
if ($listar == "ok") {
?>
<p>

<center>
	<div class='texto_avulso' style='width:700px;'>
		Clique no Código do Posto para ver sua Relação de Peças
	</div>
</center>
<BR />
<table width='700' align='center' border='0' cellspacing='1' class="tabela">
<tr class="titulo_coluna">
<td>Posto</td>
<td align="left">Nome do Posto</td>
<td>UF</td>
<td>&gt; 15 dias</td>
<td>&lt;=15 dias</td>
</tr>

<?
/*ultima alteracao tinha sido 07-03-07*/
flush();

	if ($login_fabrica == 2 ) $pedido_faturado = 1;
	if ($login_fabrica == 2 ) $pedido_garantia = 70;
	if ($login_fabrica == 3 ) $pedido_faturado = 2;
	if ($login_fabrica == 3 ) $pedido_garantia = 3;
	/*TAKASHI HD 1895 - nao sei pq definiram para fabrica 2 e 3 e liberaram para todas as fabricas*/
	if ($login_fabrica == 24 ) $pedido_garantia = 104;
	if ($login_fabrica == 24 ) $pedido_faturado = 103;
	if ($login_fabrica == 11 ) $pedido_garantia = 84;
	if ($login_fabrica == 172 ) $pedido_garantia = 84;
	if ($login_fabrica == 11 ) $pedido_faturado = 85;
	if ($login_fabrica == 172 ) $pedido_faturado = 85;

	/*TAKASHI HD 1895 - nao sei pq definiram para fabrica 2 e 3 e liberaram para todas as fabricas
	$sql = "SELECT  tbl_posto_fabrica.codigo_posto, 
				tbl_posto.posto, 
				tbl_posto.nome, 
				tbl_posto.estado, 
				pend.qtde_menos_15, 
				pend.qtde_mais_15
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN (	SELECT  pend_0.posto, 
						SUM (pend_0.qtde_menos_15) AS qtde_menos_15, 
						SUM (pend_0.qtde_mais_15) AS qtde_mais_15
				FROM (	SELECT (CASE WHEN tbl_pedido.distribuidor IS NULL THEN 
									tbl_pedido.posto 
								ELSE tbl_pedido.distribuidor END) AS posto,
								SUM (CASE WHEN tbl_pedido.data >  (CURRENT_DATE - INTERVAL '15 days') THEN 			(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) ELSE 0 END ) AS qtde_menos_15 ,
						SUM  (	CASE WHEN tbl_pedido.data <= (CURRENT_DATE - INTERVAL '15 days') THEN 
									(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) 
								ELSE 0 END ) AS qtde_mais_15
						FROM tbl_pedido
						JOIN tbl_pedido_item USING (pedido)
						WHERE tbl_pedido.fabrica = $login_fabrica ";
						//a pedido de Edina só exibir pedidos não exportados (chamado 1413)
						if ($login_fabrica == 2) $sql .= "AND tbl_pedido.exportado ISNULL ";
						$sql .= "
						AND   tbl_pedido.status_pedido IN (2,5,8)
						AND   tbl_pedido.data > '2004-01-01'
						AND   (tbl_pedido.tipo_pedido = $pedido_garantia OR (tbl_pedido.distribuidor IS NULL AND tbl_pedido.tipo_pedido = $pedido_faturado))
						GROUP BY tbl_pedido.posto, tbl_pedido.distribuidor
					) pend_0
				GROUP BY pend_0.posto
			) pend ON tbl_posto.posto = pend.posto
		ORDER BY (pend.qtde_mais_15 + pend.qtde_menos_15) DESC";
	//a pedido de Edina só exibir pedidos não exportados (chamado 1413)*/

	if ($login_fabrica == 2) $cond_fabrica2 .= "AND tbl_pedido.exportado ISNULL ";
	
	$sql = "
		SELECT pedido
		INTO TEMP tmp_pp1_$login_admin
		FROM tbl_pedido
		WHERE tbl_pedido.fabrica = $login_fabrica 
		$cond_fabrica2
		AND   tbl_pedido.status_pedido IN (2,5,8)
		AND   tbl_pedido.data between '$aux_data_inicial' AND '$aux_data_final'
		AND   (tbl_pedido.tipo_pedido = $pedido_garantia OR (tbl_pedido.distribuidor IS NULL AND tbl_pedido.tipo_pedido = $pedido_faturado));
	
		CREATE INDEX tmp_pp1_pedido_$login_admin ON tmp_pp1_$login_admin(pedido);
	
		SELECT  pend_0.posto, 
				SUM (pend_0.qtde_menos_15) AS qtde_menos_15, 
				SUM (pend_0.qtde_mais_15) AS qtde_mais_15
		INTO TEMP tmp_pp_$login_admin
		FROM (
			SELECT (
					CASE WHEN tbl_pedido.distribuidor IS NULL THEN tbl_pedido.posto 
					ELSE tbl_pedido.distribuidor END
				) AS posto,
				SUM (
					CASE WHEN tbl_pedido.data >  (CURRENT_DATE - INTERVAL '15 days') THEN (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada)
					ELSE 0 END 
				) AS qtde_menos_15 ,
				SUM  (
					CASE WHEN tbl_pedido.data <= (CURRENT_DATE - INTERVAL '15 days') THEN (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada)
					ELSE 0 END 
				) AS qtde_mais_15
				FROM tmp_pp1_$login_admin
				JOIN tbl_pedido      ON tmp_pp1_$login_admin.pedido = tbl_pedido.pedido
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
				WHERE tbl_pedido.fabrica = $login_fabrica 
				$cond_fabrica2
				AND   tbl_pedido.status_pedido IN (2,5,8)
				AND   tbl_pedido.data between '$aux_data_inicial' AND '$aux_data_final'
				AND   (tbl_pedido.tipo_pedido = $pedido_garantia OR (tbl_pedido.distribuidor IS NULL AND tbl_pedido.tipo_pedido = $pedido_faturado))
				GROUP BY tbl_pedido.posto, tbl_pedido.distribuidor
			) pend_0
		GROUP BY pend_0.posto;
	
		CREATE INDEX tmp_pp_POSTO_$login_admin ON tmp_pp_$login_admin(posto);
	
		SELECT  tbl_posto_fabrica.codigo_posto, 
			tbl_posto.posto, 
			tbl_posto.nome, 
			tbl_posto.estado, 
			pend.qtde_menos_15, 
			pend.qtde_mais_15
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tmp_pp_$login_admin pend ON tbl_posto.posto = pend.posto
		ORDER BY (pend.qtde_mais_15 + pend.qtde_menos_15) DESC";

	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	$total_mais_15  = 0 ;
	$total_menos_15 = 0 ;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto         = pg_result ($res,$i,posto);
		$nome          = pg_result ($res,$i,nome);
		$codigo_posto  = pg_result ($res,$i,codigo_posto);
		$estado        = pg_result ($res,$i,estado);
		$qtde_mais_15  = pg_result ($res,$i,qtde_mais_15);
		$qtde_menos_15 = pg_result ($res,$i,qtde_menos_15);

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor'>";
		echo "<td><a href='pendencia_peca.php?posto=$posto'>$codigo_posto</a></td>";
		echo "<td align='left'>$nome</td>";
		echo "<td>$estado</td>";
		echo "<td align='right'>$qtde_mais_15</td>";
		echo "<td align='right'>$qtde_menos_15</td>";
		echo "</tr>";
	
		$total_mais_15  += pg_result ($res,$i,qtde_mais_15);
		$total_menos_15 += pg_result ($res,$i,qtde_menos_15);
	}
	
	echo "<tr class='titulo_coluna'>";
	echo "<td colspan='3'>Total da Pendência</td>";
	echo "<td>$total_mais_15</td>";
	echo "<td>$total_menos_15</td>";
	echo "</tr>";

	if (pg_numrows ($res) == 0) {
		echo "<tr>";
		echo "<td colspan='5'><BR><BR>Nenhuma pendência encontrada.</td>";
		echo "</tr>";
	}
	echo "</table>";
}

$codigo_posto = trim ($_POST['codigo_posto']);
$nome         = trim ($_POST['nome']);
$pedido       = trim ($_POST['pedido']);
$nota_fiscal  = trim ($_POST['nota_fiscal']);

if (strlen ($codigo_posto) > 1 OR strlen ($nome) > 2 OR strlen ($pedido) > 3 OR strlen ($nota_fiscal) > 2 ) {

	if (strlen ($codigo_posto) > 1) $condicao = " tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%' ";
	if (strlen ($nome) > 1)         $condicao = " tbl_posto.nome                 ILIKE '%$nome%' ";
	if (strlen ($pedido) > 1)       $condicao = " tbl_pedido.pedido           = $pedido ";
	if (strlen ($nota_fiscal) > 1)  $condicao = " tbl_faturamento.nota_fiscal = LPAD ('$nota_fiscal',6,'0') ";

	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.posto, tbl_posto.nome, tbl_posto.cidade, tbl_posto.estado, tbl_peca.referencia, tbl_peca.descricao, tbl_pedido_item.qtde, tbl_pedido_item.qtde_cancelada, tbl_pedido_item.qtde_faturada_distribuidor, tbl_pedido.pedido, to_char (tbl_pedido.data,'DD/MM/YYYY') AS pedido_data, tbl_faturamento.nota_fiscal, to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS nf_emissao , tbl_posto_estoque.qtde AS qtde_estoque, tbl_pedido_item.pedido_item, tbl_pedido.tipo_pedido
		FROM tbl_posto
		JOIN tbl_pedido      USING (posto)
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_peca        USINg (peca)
		JOIN tbl_posto_fabrica      ON tbl_posto.posto              = tbl_posto_fabrica.posto        AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
		LEFT JOIN tbl_posto_estoque ON tbl_pedido.distribuidor      = tbl_posto_estoque.posto        AND tbl_pedido_item.peca      = tbl_posto_estoque.peca
		LEFT JOIN tbl_embarque_item ON tbl_pedido_item.pedido_item  = tbl_embarque_item.pedido_item
		LEFT JOIN tbl_faturamento   ON tbl_embarque_item.embarque   = tbl_faturamento.embarque       AND tbl_pedido.tipo_pedido    = tbl_faturamento.tipo_pedido
		WHERE $condicao
		AND   tbl_pedido.fabrica      = $login_fabrica
		AND   tbl_pedido.distribuidor = $login_posto
		ORDER BY tbl_posto.nome, tbl_peca.referencia, tbl_pedido_item.pedido_item";

	$res = pg_exec ($con,$sql);

	echo "<table border='1' cellspacing='0'>";
	$posto_ant = "";
	$pedido_item_ant = "";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($posto_ant <> pg_result ($res,$i,posto) ) {
	
			echo "<tr bgcolor='#99FFFF' align='center' style='font-weight:bold'>";
			echo "<td colspan='10' align='center'>";
			echo pg_result ($res,$i,nome);
			echo "</td>";
			echo "</tr>";

			echo "<tr bgcolor='#99FFFF' align='center' style='font-weight:bold'>";
			echo "<td nowrap>Pedido</td>";
			echo "<td nowrap>Data Pedido</td>";
			echo "<td nowrap>Tipo</td>";
			echo "<td nowrap>Peça</td>";
			echo "<td nowrap>Descrição</td>";
			echo "<td nowrap>Pedida</td>";
			echo "<td nowrap>Cancelada</td>";
			echo "<td nowrap>Atendida</td>";
#			echo "<td nowrap>Estoque</td>";
			echo "<td nowrap>Nota Fiscal</td>";
			echo "</tr>";

			$posto_ant = pg_result ($res,$i,posto);
			$pedido_item_ant = "";
		}

		if ($pedido_item_ant <> pg_result ($res,$i,pedido_item) ) {
			echo "</td>";
			echo "</tr>";

			echo "<tr style='font-size:12px'> ";
			echo "<td>".pg_result ($res,$i,pedido)."</td>";
			echo "<td>".pg_result ($res,$i,pedido_data)."</td>";

			echo "<td>";
			if ((pg_result ($res,$i,tipo_pedido) == 2 and $login_fabrica == 3) or (pg_result ($res,$i,tipo_pedido) == 1 and $login_fabrica == 2)) {
				echo "FAT";
			}
			if ((pg_result ($res,$i,tipo_pedido) == 3 and $login_fabrica == 3) or (pg_result ($res,$i,tipo_pedido) == 70 and $login_fabrica == 2)) {
				echo "GAR";
			};
			echo "</td>";

			echo "<td nowrap>".pg_result ($res,$i,referencia)."</td>";

			echo "<td nowrap>".pg_result ($res,$i,descricao)."</td>";

			echo "<td>".pg_result ($res,$i,qtde)."</td>";

			echo "<td>";
			if (pg_result ($res,$i,qtde_cancelada) > 0) {
				echo pg_result ($res,$i,qtde_cancelada);
			}else{
				echo "&nbsp;";
			}
			echo "</td>";

			echo "<td>". pg_result ($res,$i,qtde_faturada_distribuidor)."</td>";

#			echo "<td>";
#			echo pg_result ($res,$i,qtde_estoque);
#			echo "</td>";

			echo "<td nowrap>";

			$pedido_item_ant = pg_result ($res,$i,pedido_item);
		}

		echo pg_result ($res,$i,nota_fiscal);
		echo "-";
		echo pg_result ($res,$i,nf_emissao);
		echo "<br>";

	}


	echo "</table>";

	exit;
	if ($login_fabrica == 2 ) $pedido_faturado = 1;
	if ($login_fabrica == 2 ) $pedido_garantia = 70;
	if ($login_fabrica == 3 ) $pedido_faturado = 2;
	if ($login_fabrica == 3 ) $pedido_garantia = 3;
	if ($login_fabrica == 11 ) $pedido_garantia = 84;
	if ($login_fabrica == 172 ) $pedido_garantia = 84;
	if ($login_fabrica == 11 ) $pedido_faturado = 85;
	if ($login_fabrica == 172 ) $pedido_faturado = 85;
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao
		FROM   tbl_peca 
		LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_depara                    ON tbl_peca.peca = tbl_depara.peca_de
		LEFT JOIN tbl_peca para                ON tbl_depara.peca_para = para.peca
		LEFT JOIN (
			SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica
			FROM tbl_pedido_item
			JOIN tbl_pedido USING (pedido)
			WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = $pedido_faturado) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = $pedido_garantia ) )
			AND tbl_pedido.fabrica = $login_fabrica
			GROUP BY tbl_pedido_item.peca
		) fabrica ON tbl_peca.peca = fabrica.peca
		LEFT JOIN (
			SELECT peca, SUM (qtde) AS qtde_transp
			FROM tbl_faturamento_item
			JOIN tbl_faturamento USING (faturamento)
			WHERE tbl_faturamento.posto   = $login_posto
			AND   tbl_faturamento.fabrica = $login_fabrica
			AND tbl_faturamento.conferencia IS NULL
			GROUP BY tbl_faturamento_item.peca
		) transp ON tbl_peca.peca = transp.peca
		WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
		AND    (tbl_peca.referencia ILIKE '%$referencia%' OR para.referencia ILIKE '%$referencia%')
		AND    tbl_peca.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao";
	//echo $sql;

	$res = pg_exec ($con,$sql);
}


if ($login_fabrica == 2 ) $pedido_faturado = 1;
if ($login_fabrica == 2 ) $pedido_garantia = 70;
if ($login_fabrica == 3 ) $pedido_faturado = 2;
if ($login_fabrica == 3 ) $pedido_garantia = 3;
if (strlen ($descricao) > 2) {
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao
		FROM   tbl_peca 
		LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca        = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca        = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_depara                    ON tbl_peca.peca        = tbl_depara.peca_de
		LEFT JOIN tbl_peca para                 ON tbl_depara.peca_para = para.peca
		LEFT JOIN (
			SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica
			FROM tbl_pedido_item
			JOIN tbl_pedido USING (pedido)
			WHERE ( (tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = $pedido_faturado) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = $pedido_garantia ) )
			 AND tbl_pedido.fabrica = $login_fabrica GROUP BY tbl_pedido_item.peca
		) fabrica ON tbl_peca.peca = fabrica.peca
		LEFT JOIN (
			SELECT peca, SUM (qtde) AS qtde_transp
			FROM tbl_faturamento_item
			JOIN tbl_faturamento USING (faturamento)
			WHERE tbl_faturamento.posto   = $login_posto
			AND   tbl_faturamento.fabrica = $login_fabrica
			AND   tbl_faturamento.conferencia IS NULL
			GROUP BY tbl_faturamento_item.peca
		) transp ON tbl_peca.peca = transp.peca
		WHERE  ( tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL )
		AND    ( tbl_peca.descricao ILIKE '%$descricao%' OR para.descricao ILIKE '%$descricao%' )
		AND    tbl_peca.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao";
	
	$res = pg_exec ($con,$sql);
}

if (strlen ($descricao) > 2 or strlen ($referencia) > 2) {

	echo "<table align='center' border='1' cellspacing='3' cellpaddin='3'>";
	echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Referência</td>";
	echo "<td>Descrição</td>";
	echo "<td>Estoque</td>";
	echo "<td>Fábrica</td>";
	echo "<td>Transp.</td>";
	echo "<td>Localização</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$cor = "";
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#dddddd';
		
		echo "<tr bgcolor='$cor'>";

		echo "<td>";
		echo pg_result ($res,$i,referencia);
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,descricao);
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde_fabrica);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde_transp);
		echo "</td>";

		echo "<td align='left'>&nbsp;";
		echo pg_result ($res,$i,localizacao);
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";

}

?>

<? include "rodape.php"; ?>


