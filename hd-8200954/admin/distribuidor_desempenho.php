<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";


include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "Desempenho dos Distribuidores";

include "cabecalho.php";

?>


<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<p>

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["ano"])) > 0) $ano = trim($_POST["ano"]);
if (strlen(trim($_GET["ano"])) > 0)  $ano = trim($_GET["ano"]);

if (strlen(trim($_POST["mes"])) > 0) $mes = trim($_POST["mes"]);
if (strlen(trim($_GET["mes"])) > 0)  $mes = trim($_GET["mes"]);

if (strlen(trim($_POST["distribuidor"])) > 0) $distribuidor = trim($_POST["distribuidor"]);
if (strlen(trim($_GET["distribuidor"])) > 0)  $distribuidor = trim($_GET["distribuidor"]);

if (strlen ($ano) >  0 AND strlen ($mes) == 0) $msg_erro = "Escolha o mês";
if (strlen ($ano) == 0 AND strlen ($mes)  > 0) $msg_erro = "Escolha o ano";
if (strlen ($ano) >  0 AND strlen ($distribuidor) == 0) $msg_erro = "Escolha o distribuidor";


if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}


if (strlen($msg_erro) > 0) { ?>
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>


<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class='topo'>
	<td colspan='3'>Escolha o mês e o ano para a avaliação <br> São selecionados todos os pedidos realizados no mês selecionado, e é calculado o prazo para atendimento destes pedidos. </td>
</tr>
<tr class='menu_top'>
	<td>Distribuidor</td>
	<td>Ano</td>
	<td>Mês</td>
</tr>
<tr>
	<td>
		<?
		echo "<select name='distribuidor' class='frm'>";
		echo "<option value=''></option>";

		echo "<option value='4311' " ;
		if ($distribuidor == "4311") echo " selected " ;
		echo ">Telecontrol</option>";

		echo "<option value='1007' " ;
		if ($distribuidor == "1007") echo " selected " ;
		echo ">Grala</option>";

		echo "<option value='560' " ;
		if ($distribuidor == "560") echo " selected " ;
		echo ">AM Jaime</option>";

		echo "<option value='595' " ;
		if ($distribuidor == "595") echo " selected " ;
		echo ">Martello</option>";

		echo "</select>";
		?>
	</td>
	<td>
		<select name="ano" size="1" class="frm">
		<option value=''></option>
		<?
		for ($i = 2006 ; $i <= date("Y") ; $i++) {
			echo "<option value='$i'";
			if ($ano == $i) echo " selected";
			echo ">$i</option>";
		}
		?>
		</select>
	</td>
	<td>
		<?
		$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
		echo "<select name='mes' class='frm'>";
		echo "<option value=''></option>";
		for ($i = 1 ; $i <= count($meses) ; $i++) {
			echo "<option value='$i' ";
			if ($mes == $i) echo " selected";
			echo ">".$meses[$i]."</option>\n";
		}
		echo "</select>";
		?>
	</td>
</tr>
</table>

<br>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	if (strlen($mes) > 0 OR strlen($ano) > 0){
		if ($mes == '10' ) { $mes = "0" . trim($mes);
		} else {
			$mes = "00" . trim ($mes);
		}
		$mes = substr ($mes,strlen ($mes-1),2);

		$data_inicial = "$ano-$mes-01 00:00:00";
		$data_final   = "$ano-$mes-".date("t", mktime(0, 0, 0, $mes, 1, $ano))." 23:59:59";
	}

	flush();


	#--------------------- TELECONTROL -------------------------#
	if ($distribuidor == 4311) {

		$resX = @pg_exec ($con,"DROP TABLE britania_desempenho");

		$sql = "
			SELECT  tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_posto.estado,
				tbl_pedido.pedido,
				tbl_pedido.data  ,
				TO_CHAR (tbl_pedido.data,'DD/MM/YYYY')     AS data_pedido     ,
				tbl_peca.peca                                                 ,
				tbl_peca.referencia                                           ,
				tbl_peca.descricao                                            ,
				tbl_os_item.os_item                                           ,
				tbl_os.sua_os                                                 
			INTO TEMP TABLE britania_desempenho
			FROM tbl_pedido
			JOIN tbl_pedido_item USING (pedido)
			JOIN tbl_os_item     ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca
			JOIN tbl_os_produto  ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_os          ON tbl_os.os = tbl_os_produto.os
			JOIN tbl_posto       ON tbl_pedido.posto = tbl_posto.posto
			JOIN tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
			LEFT JOIN tbl_posto_fabrica ON tbl_pedido.fabrica = tbl_posto_fabrica.fabrica AND tbl_pedido.posto = tbl_posto_fabrica.posto
			WHERE tbl_pedido.fabrica = 3
			AND   tbl_pedido.tipo_pedido = 3
			AND   tbl_pedido.distribuidor = 4311 
			AND   tbl_pedido.data BETWEEN '$data_inicial' AND '$data_final'
			;

			ALTER TABLE britania_desempenho ADD COLUMN emissao_distrib TEXT ;
			ALTER TABLE britania_desempenho ADD COLUMN data_distrib DATE ;
			ALTER TABLE britania_desempenho ADD COLUMN nf_distrib TEXT ;

			UPDATE britania_desempenho SET emissao_distrib = TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')
			FROM tbl_faturamento JOIN tbl_embarque_item ON tbl_faturamento.embarque = tbl_embarque_item.embarque AND tbl_faturamento.cfop LIKE '59%'
			WHERE britania_desempenho.os_item = tbl_embarque_item.os_item ;

			UPDATE britania_desempenho SET data_distrib = tbl_faturamento.emissao
			FROM tbl_faturamento JOIN tbl_embarque_item ON tbl_faturamento.embarque = tbl_embarque_item.embarque AND tbl_faturamento.cfop LIKE '59%'
			WHERE britania_desempenho.os_item = tbl_embarque_item.os_item ;

			UPDATE britania_desempenho SET nf_distrib = tbl_faturamento.nota_fiscal
			FROM tbl_faturamento JOIN tbl_embarque_item ON tbl_faturamento.embarque = tbl_embarque_item.embarque AND tbl_faturamento.cfop LIKE '59%'
			WHERE britania_desempenho.os_item = tbl_embarque_item.os_item ;

			ALTER TABLE britania_desempenho ADD COLUMN emissao_fabrica TEXT ;
			ALTER TABLE britania_desempenho ADD COLUMN data_fabrica DATE ;
			ALTER TABLE britania_desempenho ADD COLUMN nf_fabrica TEXT ;

			UPDATE britania_desempenho SET emissao_fabrica = TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')
			FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento)
			WHERE tbl_faturamento.fabrica = 3
			AND   tbl_faturamento.posto   = 4311
			AND   tbl_faturamento.cfop LIKE '69%'
			AND   tbl_faturamento_item.pedido = britania_desempenho.pedido
			AND   tbl_faturamento_item.peca   = britania_desempenho.peca
			;

			UPDATE britania_desempenho SET data_fabrica = tbl_faturamento.emissao
			FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento)
			WHERE tbl_faturamento.fabrica = 3
			AND   tbl_faturamento.posto   = 4311
			AND   tbl_faturamento.cfop LIKE '69%'
			AND   tbl_faturamento_item.pedido = britania_desempenho.pedido
			AND   tbl_faturamento_item.peca   = britania_desempenho.peca
			;

			UPDATE britania_desempenho SET nf_fabrica = tbl_faturamento.nota_fiscal
			FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento)
			WHERE tbl_faturamento.fabrica = 3
			AND   tbl_faturamento.posto   = 4311
			AND   tbl_faturamento.cfop LIKE '69%'
			AND   tbl_faturamento_item.pedido = britania_desempenho.pedido
			AND   tbl_faturamento_item.peca   = britania_desempenho.peca
			;

			ALTER TABLE britania_desempenho ADD COLUMN dias_distrib int4 ;
			ALTER TABLE britania_desempenho ADD COLUMN dias_fabrica int4 ;

			UPDATE britania_desempenho SET dias_distrib = data_distrib::date - data::date ;
			UPDATE britania_desempenho SET dias_fabrica = data_fabrica::date - data::date ;

			ALTER TABLE britania_desempenho DROP COLUMN peca ;
			ALTER TABLE britania_desempenho DROP COLUMN os_item ;
			ALTER TABLE britania_desempenho DROP COLUMN data ;
			ALTER TABLE britania_desempenho DROP COLUMN data_distrib ;
			ALTER TABLE britania_desempenho DROP COLUMN data_fabrica ;

			SELECT * FROM britania_desempenho ORDER BY nome ;
		";

	}else{
		#---------------- Outros Distribuidores --------------#
		$resX = @pg_exec ($con,"DROP TABLE britania_desempenho");

		$sql = "
		SELECT  tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_posto.estado,
			tbl_pedido.pedido,
			tbl_pedido.data  ,
			TO_CHAR (tbl_pedido.data,'DD/MM/YYYY')     AS data_pedido     ,
			tbl_peca.peca                                                 ,
			tbl_peca.referencia                                           ,
			tbl_peca.descricao                                            ,
			tbl_os_item.os_item                                           ,
			tbl_os.sua_os                                                 
		INTO TEMP TABLE britania_desempenho
		FROM tbl_pedido
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_os_item     ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca
		JOIN tbl_os_produto  ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN tbl_os          ON tbl_os.os = tbl_os_produto.os
		JOIN tbl_posto       ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
		LEFT JOIN tbl_posto_fabrica ON tbl_pedido.fabrica = tbl_posto_fabrica.fabrica AND tbl_pedido.posto = tbl_posto_fabrica.posto
		WHERE tbl_pedido.fabrica = 3
		AND   tbl_pedido.tipo_pedido = 3
		AND   tbl_pedido.distribuidor = $distribuidor
		AND   tbl_pedido.data BETWEEN '$data_inicial' AND '$data_final'
		;

		ALTER TABLE britania_desempenho ADD COLUMN emissao_distrib TEXT ;
		ALTER TABLE britania_desempenho ADD COLUMN data_distrib DATE ;
		ALTER TABLE britania_desempenho ADD COLUMN nf_distrib TEXT ;

		UPDATE britania_desempenho SET emissao_distrib = TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY')
		FROM tbl_os_item_nf
		WHERE britania_desempenho.os_item = tbl_os_item_nf.os_item ;

		UPDATE britania_desempenho SET data_distrib = tbl_os_item_nf.data_nf
		FROM tbl_os_item_nf
		WHERE britania_desempenho.os_item = tbl_os_item_nf.os_item ;

		UPDATE britania_desempenho SET nf_distrib = tbl_os_item_nf.nota_fiscal
		FROM tbl_os_item_nf
		WHERE britania_desempenho.os_item = tbl_os_item_nf.os_item ;


		ALTER TABLE britania_desempenho ADD COLUMN emissao_fabrica TEXT ;
		ALTER TABLE britania_desempenho ADD COLUMN data_fabrica DATE ;
		ALTER TABLE britania_desempenho ADD COLUMN nf_fabrica TEXT ;


		UPDATE britania_desempenho SET emissao_fabrica = TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')
		FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento)
		WHERE tbl_faturamento.fabrica = 3
		AND   tbl_faturamento.posto   = $distribuidor
		AND  (tbl_faturamento.cfop LIKE '69%' OR tbl_faturamento.cfop LIKE '59%')
		AND   tbl_faturamento_item.pedido = britania_desempenho.pedido
		AND   tbl_faturamento_item.peca   = britania_desempenho.peca
		;

		UPDATE britania_desempenho SET data_fabrica = tbl_faturamento.emissao
		FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento)
		WHERE tbl_faturamento.fabrica = 3
		AND   tbl_faturamento.posto   = $distribuidor
		AND  (tbl_faturamento.cfop LIKE '69%' OR tbl_faturamento.cfop LIKE '59%')
		AND   tbl_faturamento_item.pedido = britania_desempenho.pedido
		AND   tbl_faturamento_item.peca   = britania_desempenho.peca
		;

		UPDATE britania_desempenho SET nf_fabrica = tbl_faturamento.nota_fiscal
		FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento)
		WHERE tbl_faturamento.fabrica = 3
		AND   tbl_faturamento.posto   = $distribuidor
		AND  (tbl_faturamento.cfop LIKE '69%' OR tbl_faturamento.cfop LIKE '59%')
		AND   tbl_faturamento_item.pedido = britania_desempenho.pedido
		AND   tbl_faturamento_item.peca   = britania_desempenho.peca
		;



		ALTER TABLE britania_desempenho ADD COLUMN dias_distrib int4 ;
		ALTER TABLE britania_desempenho ADD COLUMN dias_fabrica int4 ;

		UPDATE britania_desempenho SET dias_distrib = data_distrib::date - data::date ;
		UPDATE britania_desempenho SET dias_fabrica = data_fabrica::date - data::date ;


		ALTER TABLE britania_desempenho DROP COLUMN peca ;
		ALTER TABLE britania_desempenho DROP COLUMN os_item ;
		ALTER TABLE britania_desempenho DROP COLUMN data ;
		ALTER TABLE britania_desempenho DROP COLUMN data_distrib ;
		ALTER TABLE britania_desempenho DROP COLUMN data_fabrica ;


		SELECT * FROM britania_desempenho ORDER BY nome ;

		";

	}
	//echo $sql;
	$res = pg_exec($con,$sql);

	if ($distribuidor == "4311") $distrib_nome = "TELECONTROL";
	if ($distribuidor == "1007") $distrib_nome = "GRALA";
	if ($distribuidor == "560" ) $distrib_nome = "AM JAIME";
	if ($distribuidor == "595" ) $distrib_nome = "MARTELLO";

	
	##########################################################################################
	################################ GERA ARQUIVO XLS BRITANIA ###############################
	##########################################################################################

if(pg_numrows($res) > 0){

	//HD 10685 2/1/2008
	flush();

		echo "<br><br>";
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td>
				<img src='imagens/excell.gif'>
			</td>
			<td align='center'>
				<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>
				<a href='xls/distribuidor_desempenho-".$login_fabrica."-".$data.".xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>
					download do arquivo em EXCEL
					</font>
				</a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	
		flush();
	
		$data = date('Y-m-d');
		$arquivo = "/var/www/assist/www/admin/xls/distribuidor_desempenho-".$login_fabrica."-".$data.".xls";
		$fp = fopen ($arquivo, "w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DISTRIBUIDOR DESEMPENHO - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
		fputs ($fp,"<tr class='Titulo'>");
		fputs ($fp,"<td >P.A.</td>");
		fputs ($fp,"<td >Posto Autorizado</td>");
		fputs ($fp,"<td >Pedido</td>");
		fputs ($fp,"<td >Data</td>");
		fputs ($fp,"<td >Peça</td>");
		fputs ($fp,"<td >Descrição</td>");
		fputs ($fp,"<td >O.S.</td>");
		//fputs ($fp,"<td >Pedido<BR>Garantia</td>");
		fputs ($fp,"<td >Emissão Distrib.</td>");
		fputs ($fp,"<td >NF Distrib.</td>");
		fputs ($fp,"<td >Emissão Fábrica</td>");
		fputs ($fp,"<td >NF Fábrica</td>");
		fputs ($fp,"<td >Dias Distrib.</td>");
		fputs ($fp,"<td >Dias Fábrica</td>");
		fputs ($fp,"</tr>");

		echo "<table width='700' border='1' cellspacing='0' cellpadding='2'>";
		echo "<tr class='menu_top'>";
		echo "<td align='center' colspan='13'>Desempenho do distribuidor $distrib_nome </td>";
		echo "</tr>";

		echo "<tr class='menu_top'>";
		echo "<td nowrap>P.A.</td>";
		echo "<td nowrap>Posto Autorizado</td>";
		echo "<td nowrap>Pedido</td>";
		echo "<td nowrap>Data</td>";
		echo "<td nowrap>Peça</td>";
		echo "<td nowrap>Descrição</td>";
		echo "<td nowrap>O.S.</td>";
		echo "<td nowrap>Emissão Distrib.</td>";
		echo "<td nowrap>NF Distrib.</td>";
		echo "<td nowrap>Emissão Fábrica</td>";
		echo "<td nowrap>NF Fábrica</td>";
		echo "<td nowrap>Dias Distrib.</td>";
		echo "<td nowrap>Dias Fábrica</td>";
		echo "</tr>";

		for ($i=0; $i<pg_numrows($res); $i++){
		
			$codigo_posto      = trim( pg_result ($res,$i,codigo_posto))    ;
			$nome              = trim( pg_result ($res,$i,nome))            ;
			$pedido            = trim( pg_result ($res,$i,pedido))          ;
			$data_pedido       = trim( pg_result ($res,$i,data_pedido))     ;
			$referencia        = trim( pg_result ($res,$i,referencia))      ;
			$descricao         = trim( pg_result ($res,$i,descricao))       ;
			$sua_os            = trim( pg_result ($res,$i,sua_os))          ;
			$emissao_distrib   = trim( pg_result ($res,$i,emissao_distrib)) ;
			$nf_distrib        = trim( pg_result ($res,$i,nf_distrib))      ;
			$emissao_fabrica   = trim( pg_result ($res,$i,emissao_fabrica)) ;
			$nf_fabrica        = trim( pg_result ($res,$i,nf_fabrica))      ;
			$dias_distrib      = trim( pg_result ($res,$i,dias_distrib))    ;
			$dias_fabrica      = trim( pg_result ($res,$i,dias_fabrica))    ;

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
		//HD 10685 2/1/2008
			fputs ($fp,"<tr class='Conteudo'>");
			fputs ($fp,"<td bgcolor='$cor' >$codigo_posto</td>");
			fputs ($fp,"<td bgcolor='$cor' >$nome</td>");
			fputs ($fp,"<td bgcolor='$cor' >$pedido</td>");
			fputs ($fp,"<td bgcolor='$cor' >$data_pedido</td>");
			fputs ($fp,"<td bgcolor='$cor' align='right'>$referencia</td>");
			fputs ($fp,"<td bgcolor='$cor' align='right'>$descricao</td>");
			fputs ($fp,"<td bgcolor='$cor' align='right'>$sua_os</td>");
			fputs ($fp,"<td bgcolor='$cor' align='right'>$emissao_distrib</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>$nf_distrib</td>");
			fputs ($fp,"<td bgcolor='$cor' align='right'>$emissao_fabrica</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>$nf_fabrica</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>$dias_distrib</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>$dias_fabrica</td>");
			fputs ($fp,"</tr>");

			echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td nowrap>$codigo_posto</td>";
				echo "<td nowrap>$nome</td>";
				echo "<td nowrap>$pedido</td>";
				echo "<td nowrap>$data_pedido</td>";
				echo "<td nowrap>$referencia</td>";
				echo "<td nowrap>$descricao</td>";
				echo "<td nowrap>$sua_os</td>";
				echo "<td nowrap>$emissao_distrib</td>";
				echo "<td nowrap>$nf_distrib</td>";
				echo "<td nowrap>$emissao_fabrica</td>";
				echo "<td nowrap>$nf_fabrica</td>";
				echo "<td nowrap>$dias_distrib</td>";
				echo "<td nowrap>$dias_fabrica</td>";
			echo "</tr>";

	}

	//HD 10685 2/1/2008
		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
	
	echo "</table>";

	##########################################################################################
	############################# FIM GERA ARQUIVO XLS BRITANIA ##############################
	##########################################################################################

	}

	echo "<br><br><br>";
}
echo "<br>";

include "rodape.php";
?>
