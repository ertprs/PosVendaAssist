<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if(isset($_GET["causa_troca"])){
	$get_causa_troca = $_GET["causa_troca"];
}

$acao = empty($acao) ? $_GET['acao'] : $acao; // HD 759089 - Se nao passar valor, variavel nao tem valor :-)

if (strlen($acao) > 0) {
	$mes = $_GET["mes"];
	$ano = $_GET["ano"];
	$codigo_posto = trim($_GET["codigo_posto"]);

	if ($login_fabrica==45){
		$data_in = $_GET["data_in"];
		$data_fl = $_GET["data_fl"];
		if (strlen($data_in) == 0) $msg = "Data Inválida";
		if (strlen($data_fl) == 0) $msg = "Data Inválida";
		if($data_in){
               $dat = explode ("/", $data_in);
               $d = $dat[0];
               $m = $dat[1];
               $y = $dat[2];
               if( !checkdate($m,$d,$y)) {
                       $msg ="Data Inválida";
               }
       }

		if($data_fl){
					   $dat = explode ("/", $data_fl);
					   $d = $dat[0];
					   $m = $dat[1];
					   $y = $dat[2];
					   if(!checkdate($m,$d,$y) ){
							   $msg = "Data Inválida.";
					   }
			   }
		//Converte data para comparação
			   $d_ini = explode ("/", $data_in);//tira a barra
			   $nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
			   
			   $d_fim = explode ("/", $data_fl);//tira a barra
			   $nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			   if($nova_data_inicial > $nova_data_final){
					   $msg="Data Inválida";
			   }
	}else{
		if (strlen($mes) == 0) $msg = " Selecione o mês para realizar a pesquisa. ";
		if (strlen($ano) == 0) $msg = " Selecione o ano para realizar a pesquisa. ";
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - TROCA DE PRODUTO";

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
	text-align:left;
}
.subtitulo{
	color: #7092BE
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
function GerarRelatorio (produto, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto=' + produto + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}

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
	else
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
}
</script>

<form name="frm_relatorio" method="get" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="0" class="formulario">
	<? if (strlen($msg) > 0) { ?>
		<tr class="msg_erro">
		<td colspan="4"><?echo $msg?></td>
	</tr>
	<? } ?>
	<tr class="titulo_tabela" height="15">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="4" width="100">&nbsp;</td>
	</tr>
		<? //HD 23425
		if ($login_fabrica == 45){ ?>

			<? include "javascript_calendario.php"; ?>

			<script type="text/javascript" charset="utf-8">
				$(function(){
					$("input[@rel='data_pesq']").maskedinput("99/99/9999");
				});
			</script>

			<?
			echo "<tr>";
			echo "<td width='170'>&nbsp;</td>";
			echo "<td nowrap>Data inicial</td>";
			echo "<td nowrap>Data final</td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>&nbsp;</td>";
			echo "<td><input class='frm' type='text' name='data_in' rel='data_pesq' value='$data_in' size='12' maxlength='20'></td>";
			echo "<td><input class='frm' type='text' name='data_fl' rel='data_pesq' value='$data_fl' size='12' maxlength='20'></td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			}else{
			?>
			<tr>
			<td  width="170">&nbsp;</td>
			<td nowrap>Mês *</td>
			<td nowrap>Ano *</td>
			<?php if(in_array($login_fabrica, array(3))){ ?>
			<td nowrap>Causa da Troca *</td>
			<?php } ?>
			<td>&nbsp;</td>
		</tr>

		<tr height="15">
			<td width="10">&nbsp;</td>
			<?php if(in_array($login_fabrica, array(3))){ ?>
			<td>
				<select name="mes" size="1" class="frm">
					<option value=""></option>
					<?
					for ($i = 1 ; $i <= count($meses) ; $i++) {
						echo "<option value='$i'";
						if ($mes == $i) echo " selected";
						echo ">" . $meses[$i] . "</option>";
					}
					?>
				</select>
			</td>
			<?php } ?>
			<td>
				<select name="ano" size="1" class="frm">
				<option value=""></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
				</select>
			</td>
			<td>
				<select name="causa_troca" size="1" class="frm">
				<option value=""></option>
				<?
				$sql = "SELECT causa_troca, descricao FROM tbl_causa_troca 
					WHERE fabrica = $login_fabrica 
						AND ativo IS TRUE
					ORDER BY descricao";
				$resCausaTroca = pg_query($con,$sql);
				$total_causa   = pg_num_rows($resCausaTroca);

				for($i=0; $i<$total_causa; $i++){
					$causa_troca = pg_fetch_result($resCausaTroca, $i, causa_troca);
					$descricao   = pg_fetch_result($resCausaTroca, $i, descricao);
					$selected    = $get_causa_troca == $causa_troca ? "selected" : "";
					?>
					<option value="<?=$causa_troca?>" <?=$selected?>><?=$descricao?></option>
					<?php
				}
				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	<? } ?>
	<?
	if ($login_fabrica <> 3){?>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td >&nbsp;</td>
			<td class="Conteudo" nowrap>Código do Posto</td>
			<td class="Conteudo" nowrap>Nome do Posto</td>
			<td>&nbsp;</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td>&nbsp;</td>
			<td><input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codigo_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')"></td>
			<td><input class="frm" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"><br></td>
			<td>&nbsp;</td>
		</tr>
	<?}?>
	<?if($login_fabrica==45){ //HD 14203?>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<TR>
		<td width="170">&nbsp;</td>
			<td colspan = '3' >
				
				<select name="estado" class="frm">
					<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
		<!-- 			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>UF</option> -->
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
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td width="170">&nbsp;</td>
			<td colspan='3'><b>Resumido:</b>&nbsp;&nbsp;
			Por posto<input type='radio' name='resumido' value='porposto' >&nbsp;
			Por produto<input type='radio' name='resumido' value='porproduto'>
			</td>
		</tr>
	<?}?>
	<?php if ( in_array($login_fabrica, array(24) ) ) : // HD 759089 ?>

		<tr>
			<td>&nbsp;</td>
			<td style="padding-top:10px;">
				<label for="resumido">Relatório resumido</label>&nbsp;
				<input type="checkbox" name="resumido" id="resumido" value="true" <?php if ($_GET['resumido'] == 'true') {echo 'checked';} ?> />
			</td>
		</tr>

	<?php endif; ?>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4" align="center"><input type="button" value="Pesquisar" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: pointer;" alt="Clique AQUI para pesquisar"></td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
</table>

</form>

<br>
<?

$codigo_posto = trim($_GET["codigo_posto"]);
$estado       = trim($_GET["estado"]);
$resumido     = trim($_GET["resumido"]);

if(in_array($login_fabrica, array(3))){
	$causa_troca  = trim($_GET["causa_troca"]);
}

if (strlen($acao) > 0 && strlen($msg) == 0) {
	//HD 23425
	if ($login_fabrica == 45){
		$ano_di = substr($data_in, -4, 4);
		$mes_di = substr($data_in, -7, 2);
		$dia_di = substr($data_in, -10, 2);
		$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes_di, $dia_di, $ano_di));
		$ano_df = substr($data_fl, -4, 4);
		$mes_df = substr($data_fl, -7, 2);
		$dia_df = substr($data_fl, -10, 2);
		$data_final = date("Y-m-d", mktime(0, 0, 0, $mes_df, $dia_df, $ano_df));
	}else{
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	}

	$condicao_posto = " 1=1 ";
	if (strlen ($codigo_posto) > 0 ) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
			$res = pg_exec ($con,$sql);
			$posto = pg_result ($res,0,0);
			$condicao_posto= " tbl_os.posto=$posto ";
	}

	$condicao_estado = " 1=1 "; //hd 14203
	if (strlen ($estado) > 0 ) {
		$condicao_estado= " tbl_posto_fabrica.contato_estado='$estado' ";
	}
	if(in_array($login_fabrica, array(3,45))){
		$sql_motivo = " , (SELECT descricao FROM tbl_os_troca JOIN tbl_causa_troca USING(causa_troca) 
			WHERE tbl_os_troca.os = tbl_os.os 
			LIMIT 1) as motivo ";
	}

	//HD 23425
	$msg_erro = "";
	$sql_data_intervalo="SELECT '$data_final'::date - interval '120 days' > '$data_inicial'::date AS maoirqcv";
	$resDI = pg_exec($con,$sql_data_intervalo);
	$maiorqcv = pg_result ($resDI,0,0);

	if($maiorqcv=='f'){
		if(($login_fabrica==45 or $login_fabrica==3) and strlen($resumido)==0){
			$sql_relatorio="
						SELECT tbl_posto_fabrica.posto                          AS posto               ,
						tbl_posto_fabrica.codigo_posto                   AS posto_codigo        ,
						tbl_posto.nome                                   AS posto_nome          ,
						tbl_os.sua_os                                                           ,
						tbl_os.os                                                               ,
						tbl_os.ressarcimento                                                    ,
						tbl_produto.referencia                           AS produto_referencia  ,
						tbl_produto.descricao                            AS produto_descricao   ,
						(SELECT referencia FROM tbl_peca JOIN tbl_os_item USING (peca) JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1) AS troca_por_referencia ,
						(SELECT descricao  FROM tbl_peca JOIN tbl_os_item USING (peca) JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1) AS troca_por_descricao ,
						(SELECT pedido     FROM tbl_peca JOIN tbl_os_item USING (peca) JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1) AS pedido ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura       ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento         ,
						tbl_admin.login
						$sql_motivo";
			$sql_group="ORDER BY tbl_posto_fabrica.codigo_posto asc, tbl_os.sua_os asc";
		}else{
						$sql_relatorio="
						SELECT tbl_posto_fabrica.posto                          AS posto               ,
						tbl_posto_fabrica.codigo_posto                   AS posto_codigo        ,
						tbl_posto.nome                                   AS posto_nome          ,
						tbl_os.sua_os                                                           ,
						tbl_os.os                                                               ,
						tbl_os.ressarcimento                                                    ,
						tbl_produto.referencia                           AS produto_referencia  ,
						tbl_produto.descricao                            AS produto_descricao   ,
						(SELECT referencia FROM tbl_peca JOIN tbl_os_item USING (peca) JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1) AS troca_por_referencia ,
						(SELECT descricao  FROM tbl_peca JOIN tbl_os_item USING (peca) JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1) AS troca_por_descricao ,
						(SELECT pedido     FROM tbl_peca JOIN tbl_os_item USING (peca) JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1) AS pedido ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')       AS data_abertura       ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento         ,
						tbl_admin.login
						$sql_motivo";
			$sql_group="ORDER BY tbl_posto_fabrica.codigo_posto asc, tbl_os.sua_os asc";

		}
		//HD 14932
		if($login_fabrica==45 and $resumido=='porposto'){
			$sql_relatorio="SELECT tbl_posto_fabrica.posto                   AS posto               ,
							tbl_posto_fabrica.codigo_posto                   AS posto_codigo        ,
							tbl_posto.nome                                   AS posto_nome          ,
							tbl_produto.referencia                           AS produto_referencia  ,
							tbl_produto.descricao                            AS produto_descricao   ,
							count(tbl_os.produto)                            AS produto_qtde";
			$sql_group   ="GROUP BY tbl_produto.referencia            ,
									tbl_produto.descricao             ,
									tbl_posto_fabrica.posto           ,
									tbl_posto_fabrica.codigo_posto    ,
									tbl_posto.nome
							ORDER BY tbl_posto_fabrica.codigo_posto asc, tbl_produto.referencia asc ";
		}
		if($login_fabrica==45 and $resumido=='porproduto'){
			$sql_relatorio="SELECT tbl_produto.referencia                    AS produto_referencia  ,
							tbl_produto.descricao                            AS produto_descricao   ,
							count(tbl_os.produto)                            AS produto_qtde";
			$sql_group   ="GROUP BY tbl_produto.referencia            ,
									tbl_produto.descricao
							ORDER BY tbl_produto.referencia asc ";
		} else if ( $_GET['resumido'] == 'true' && in_array($login_fabrica, array(24) ) ) {
			
			$sql_relatorio="SELECT tbl_produto.referencia                    AS produto_referencia  ,
							tbl_produto.descricao                            AS produto_descricao   ,
							count(tbl_os.produto)                            AS produto_qtde";
			$sql_group   ="GROUP BY tbl_produto.referencia            ,
									tbl_produto.descricao
							ORDER BY tbl_produto.referencia asc ";

		}

		$join_os_troca = "";

		if(in_array($login_fabrica, array(3)) && $causa_troca != ""){
			$join_os_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os AND tbl_os_troca.causa_troca = {$causa_troca} ";
		}

		$sql =	"SELECT os INTO TEMP rtp_$login_admin
						FROM tbl_os
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.data_fechamento BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE );

				CREATE INDEX rtp_os_$login_admin ON rtp_$login_admin(os);

				$sql_relatorio
				FROM rtp_$login_admin
				JOIN tbl_os ON rtp_$login_admin.os = tbl_os.os
				JOIN tbl_admin           ON tbl_admin.admin           = tbl_os.troca_garantia_admin
				JOIN tbl_posto_fabrica   ON tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN tbl_posto           ON tbl_posto.posto           = tbl_posto_fabrica.posto
				JOIN tbl_produto         ON tbl_produto.produto       = tbl_os.produto
				{$join_os_troca}
				WHERE tbl_os.fabrica = $login_fabrica
				AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE )
				AND   tbl_os.data_fechamento BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
				AND   $condicao_posto
				AND   $condicao_estado
				$sql_group";
		//if($ip== '201.76.78.194') echo nl2br($sql);
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			if(($login_fabrica==45 or $login_fabrica==3) and strlen($resumido)==0){
				echo "<table border='0' cellspacing='0' cellpadding='0' align='center' class='tabela'>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#33FFCC'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; Ressarcimento Financeiro </b></font></td>";
				echo "</tr>";
				echo "</table>";

				echo "<br>";
			}
			$posto_anterior = "*";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

				//HD 14932

				if($login_fabrica==45 and $resumido=='porposto'){

					$posto               = trim(pg_result($res,$i,posto));
					$posto_codigo        = trim(pg_result($res,$i,posto_codigo));
					$posto_nome          = trim(pg_result($res,$i,posto_nome));
					$produto_referencia  = trim(pg_result($res,$i,produto_referencia));
					$produto_descricao   = trim(pg_result($res,$i,produto_descricao));
					$produto_qtde        = trim(pg_result($res,$i,produto_qtde));
					if ($posto != $posto_anterior) {
						if ($posto_anterior <> "*") {
							echo "</table>";
							flush();
						}
						echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
						echo "<tr height='4' class='titulo_tabela' valign='middle'>";
						echo "<td colspan='2'>POSTO: $posto_codigo-$posto_nome</td>";
						echo "</tr>";
						echo "<tr height='2' class='titulo_coluna'>";
						echo "<td>Produto</td>";
						echo "<td>Qtde</td>";
						echo "</tr>";
					}
					
					if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";

					echo "<tr height='2' bgcolor='$cor'>";
					echo "<td nowrap align='center'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>" . substr($produto_descricao,0,20) . "</acronym></td>";
					echo "<td nowrap align='center'>$produto_qtde</td>";
					echo "</tr>";

					$posto_anterior  = $posto;

				}elseif( ( $login_fabrica==45 and $resumido=='porproduto' ) || ( $_GET['resumido'] == 'true' && in_array($login_fabrica, array(24) ) ) ){
					// HD 759089 - Reusando este codigo
					$produto_referencia  = trim(pg_result($res,$i,produto_referencia));
					$produto_descricao   = trim(pg_result($res,$i,produto_descricao));
					$produto_qtde        = trim(pg_result($res,$i,produto_qtde));
					if($table> 0){
						$table++;
					}else{
						$table=0;
					}
					if ($table==0) {
						echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
						echo "<tr height='2' class='titulo_coluna'>";
						echo "<td>Produto</td>";
						echo "<td>Qtde</td>";
						echo "</tr>";
					}

					echo "<tr height='2' bgcolor='$cor'>";
					echo "<td nowrap align='center'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>" . $produto_referencia . " - " . substr($produto_descricao,0,20) . "</acronym></td>";
					echo "<td nowrap align='center'>$produto_qtde</td>";
					echo "</tr>";

					$table++;

					if ( in_array($login_fabrica, array(24) ) ) {
						
						// cdados para gerar grafico - HD 759089
						$chart['produto'][] = array(
							'nome' => $produto_referencia . ' - ' . $produto_descricao,
							'qtde' => $produto_qtde
						);
						
						$chart['total']	+= $produto_qtde;

					}

				}else{

					$posto               = trim(pg_result($res,$i,posto));
					$posto_codigo        = trim(pg_result($res,$i,posto_codigo));
					$posto_nome          = trim(pg_result($res,$i,posto_nome));
					$posto_completo      = $posto_codigo . " - " . $posto_nome;
					$sua_os              = trim(pg_result($res,$i,sua_os));
					$os                  = trim(pg_result($res,$i,os));
					$produto_referencia  = trim(pg_result($res,$i,produto_referencia));
					$produto_descricao   = trim(pg_result($res,$i,produto_descricao));
					$produto_completo    = $produto_referencia . " - " . $produto_descricao;
					$troca_por_referencia = trim(pg_result($res,$i,troca_por_referencia));
					$troca_por_descricao  = trim(pg_result($res,$i,troca_por_descricao));
					$troca_por_completo   = $troca_por_referencia . " - " . $troca_por_descricao;
					$data_abertura       = trim(pg_result($res,$i,data_abertura));
					$data_fechamento     = trim(pg_result($res,$i,data_fechamento));
					$pedido              = trim(pg_result($res,$i,pedido));
					$login               = trim(pg_result($res,$i,login));
					$ressarcimento       = trim(pg_result($res,$i,ressarcimento));
					//HD 14203
					if(in_array($login_fabrica, array(3,45))){
						$motivo = trim(pg_result($res,$i,motivo));
					}
					#------ Não se deve ler o Banco dentro de LOOPINGS, principalmente grandres -------#
					#------ Só dá pra exibir NF da Troca quando for integrado. ------------------------#
					if (strlen($pedido) > 0 AND 1==2 ) {
						$sqlX =	"SELECT tbl_faturamento.nota_fiscal
								FROM tbl_faturamento
								JOIN tbl_faturamento_item USING (faturamento)
								WHERE tbl_faturamento.pedido    = $pedido
								AND   tbl_faturamento_item.peca = $peca;";
						$resX = pg_exec($con,$sqlX);
						if (pg_numrows($resX) > 0) {
							$nota_fiscal = trim(pg_result($resX,0,nota_fiscal));
						}
							$sqlX =	"SELECT tbl_faturamento.nota_fiscal
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									WHERE tbl_faturamento_item.os_item = $os_item
									AND   tbl_faturamento_item.peca    = $peca;";
							$resX = pg_exec($con,$sqlX);
							if (pg_numrows($resX) > 0) {
								$nota_fiscal = trim(pg_result($resX,0,nota_fiscal));
							}

					}

					#HD 13502
					$pecas_originou_troca = array();

					$sql = "SELECT referencia
							FROM tbl_os_produto
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_peca USING(peca)
							WHERE tbl_os_produto.os = $os
							AND tbl_os_item.originou_troca IS TRUE";
					$res2 = pg_exec($con,$sql);
					if (pg_numrows($res2) > 0) {
						for ($j=0; $j<pg_numrows($res2); $j++){
							array_push($pecas_originou_troca ,trim(pg_result($res2,$j,referencia)));
						}
					}

					if ($posto != $posto_anterior) {
						if ($posto_anterior <> "*") {
							echo "</table><br>";
							flush();
						}
						echo "<table width='100%' border='1' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
						echo "<tr height='25' class='titulo_tabela' valign='middle'>";
						echo "<td colspan='8' align='left'>POSTO: $posto_completo</td>";
						echo "</tr>";
						echo "<tr height='15' class='titulo_coluna'>";
						echo "<td>OS</td>";
						echo "<td>Produto</td>";
						echo "<td>Produto Troca</td>";
						echo "<td>Abertura</td>";
						echo "<td>Troca</td>";
						echo "<td>Pedido</td>";
		#				echo "<td>NOTA FISCAL</td>";
						echo "<td>Responsável</td>";
						echo "<td>Peça Originou a Troca</td>";
						echo "</tr>";
					}

					//$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0"; hd 759089 - liberando as cores para todos os laços :-) (movi no inicio)

					if ($ressarcimento == "t") $cor = "#33FFCC";

					echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
					echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
					echo "<td nowrap align='left'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>$produto_descricao</acronym></td>";
					echo "<td nowrap align='left'><acronym title='REFERÊNCIA: $troca_por_referencia \n DESCRIÇÃO: $troca_por_descricao' style='cursor: hand;'>$troca_por_descricao</acronym></td>";
					echo "<td nowrap>$data_abertura</td>";
					echo "<td nowrap>$data_fechamento</td>";
					echo "<td nowrap>$pedido</td>";
		#			echo "<td nowrap>$nota_fiscal</td>";
					echo "<td nowrap align='left'>$login</td>";
					echo "<td nowrap align='left'>".implode(", ",$pecas_originou_troca)."</td>";
					echo "</tr>";
					if(in_array($login_fabrica, array(3,45)) && strlen($motivo)>0){
						echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
						echo "<td nowrap align='left' colspan='100%'>MOTIVO : $motivo</td></tr>";
					}

					$posto_anterior  = $posto;
					$nota_fiscal     = null;
					$login           = null;
				}
			}
			echo "</table>";
	//		echo "<br><a href=\"javascript: GerarRelatorio ('$produto', '$x_data_inicial', '$x_data_final');\"><font size='2'>Clique aqui para gerar arquivo do EXCEL</font></a><br>";
		}else{
			echo "<br><center><B>Não foram Encontrados Resultados para esta Pesquisa!</B></center><br>";
		}
	}else{
		$msg_erro = "O intervalo de pesquisa não pode exceder 120 dias!";
	}
}
if (strlen($msg_erro)>0){ ?>
	<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<?}
echo "<br>";

if ($login_fabrica == 24 && $_GET['resumido'] == 'true') : // HD 759089

?>

<div style="clear:both; overflow:hidden;">&nbsp;</div>
<div id="container" style="width:900px; margin:auto;"></div>
<div style="clear:both; overflow:hidden;">&nbsp;</div>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>

<script type="text/javascript">
	
	$().ready(function(){
		
		chart = new Highcharts.Chart({
			chart: {
				renderTo: 'container',
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				margin: [10, 0, 50, 50],
				style: { 
					clear:'both'
				}
			},
			title: {
				text: ''
			},
			tooltip: {
				formatter: function() {
					return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
				}
			},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer',
					dataLabels: {
						enabled: true
					},
					showInLegend: false
				}
			},
			legend: {
				layout: 'vertical',
				align: 'left',
				x: 0,
				//verticalAlign: 'top',
				y: 0,
				floating: false,
				backgroundColor: '#FFFFFF',
				borderColor: '#CCC',
				borderWidth: 1,
				shadow: false
			},
			series: [{
				type: 'pie',
				name: 'Pesquisa de Satisfação x Total de Atendimentos',
				data: [

					<?php 

						foreach($chart as $k => $v) {
							
							if ( !is_array($v) )
								continue;

							foreach($v as $item) {

								$pc_prod = ( $item['qtde'] * 100 ) / $chart['total'];
								
								$print[] = "['".$item['nome']." - ".number_format($pc_prod, 2)."%', ".number_format($pc_prod, 2)."]";

							}

						}

						echo implode(', ', $print);

					?>
					
				]
			}]
		});

		values = $.map(legend.children(), function(e){ return e.offsetHeight; });
		legend_h = Math.max.apply( Math, values);

	});

</script>

<?php

endif;

include "rodape.php";
?>
