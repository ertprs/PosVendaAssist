<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";


$layout_menu = "gerencia";
$title = "RELATÓRIO - TROCA DE PRODUTO CAUSA";
if (strlen($acao) > 0) {
	$data_inicial = $_POST["data_inicial"];
	$data_final = $_POST["data_final"];
	if (strlen($data_inicial) == 0 OR strlen($data_final) == 0) $msg = "Data Inválida";

	//Início Validação de Datas
	if($data_inicial){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg = "Data Inválida";
	}
	if($data_final){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg = "Data Inválida";
	}
	if(strlen($erro)==0){
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final);//tira a barra
		$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($nova_data_final < $nova_data_inicial){
			$msg = "Data Inválida.";
		}

		$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
		$cont = 0;
		while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {      
		  $nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
		  $cont++;
		}

		if($cont > 120){
			$msg="O intervalo entre as datas não pode ser maior que 120 dias.";
		}

		//Fim Validação de Datas
	}
}
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

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>
<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if (strlen($msg) > 0) { ?>
		<tr class="msg_erro">
			<td><?echo $msg?></td>
		</tr>
	<? } ?>
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>

	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

			<tr>
				<td width="102">&nbsp;</td>
				<td align='right' nowrap>Data Inicial</td>
				<td align='left' width="100" nowrap>
					<input type="text" name="data_inicial" id="data_inicial" size="10" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
				</td>
				<td align='left'width="50"  nowrap>Data Final</td>
				<td align='left' nowrap>
					<input type="text" name="data_final" id="data_final" size="10" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
				</td>
				<td width="110">&nbsp;</td>
			</tr>
			</table>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >
			<TR>
				<td width="10">&nbsp;</td>
				<td align='right' nowrap>Causa da Troca</td>
				<td align='left'>
					<?
					$sql = "SELECT  tbl_causa_troca.causa_troca,
									tbl_causa_troca.codigo     ,
									tbl_causa_troca.descricao
							FROM tbl_causa_troca
							WHERE tbl_causa_troca.fabrica = $login_fabrica
							AND tbl_causa_troca.ativo     IS TRUE
							ORDER BY tbl_causa_troca.codigo,tbl_causa_troca.descricao";
					$resTroca = pg_exec ($con,$sql);
					?>
						<select name='causa_troca' size='1' class='frm' style='width: 250px;'>
						<option value='' ></option>
						<?
						for ($i = 0 ; $i < pg_numrows($resTroca) ; $i++) {
							echo "<option value='" . pg_result ($resTroca,$i,causa_troca) . "'
							>" . pg_result ($resTroca,$i,codigo) . " - " . pg_result ($resTroca,$i,descricao) . "</option>";
						} 
						?>
					</select>
				</td>
				<td width="10" colspan='3'>&nbsp;</td>
			</tr>
			<TR>
				<td width="10">&nbsp;</td>
				<td align='right' nowrap>Consumidor ou Revenda</td>
				<td align='left'>
					<select name="consumidor_revenda" size="1"  class='frm' style='width: 250px;'>
					<option value=""   <? if (strlen($consumidor_revenda) == 0)    echo " selected "; ?>>TODOS</option>
					<option value="C" <? if ($consumidor_revenda == "C") echo " selected "; ?>>Consumidor</option>
					<option value="R" <? if ($consumidor_revenda == "R") echo " selected "; ?>>Revenda</option>
					</select>
				</td>
				<td width="10" colspan='3'>&nbsp;</td>
			</TR>
			<TR>
				<td width="10">&nbsp;</td>
				<td align='right' nowrap>Estado</td>
				<td align='left'>
					<select name="estado" size="1"  class='frm' style='width: 250px;'>
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
				<td width="10" colspan='3'>&nbsp;</td>
			</TR>
			<tr>
				<td width="10">&nbsp;</td>
				<td align='right'>Resumido</td>
				<td align='left'>
					<input type="radio" name="resumido" class='frm' value="resumido">
				</td>
				<td width="10" colspan='3'>&nbsp;</td>
			</tr>
			</table><br>
			<input type="hidden" name="acao">
			<input type="image" src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Clique AQUI para pesquisar">
		</td>
	</tr>
</table>
</FORM>

<?

$estado             = trim($_POST["estado"]);
$causa_troca        = trim($_POST["causa_troca"]);
$consumidor_revenda = trim($_POST["consumidor_revenda"]);
$resumido           = trim($_POST["resumido"]);

if (strlen($acao) > 0 && strlen($msg) == 0) {
	$condicao_1 = " 1=1 ";
	if (strlen ($estado) > 0 ) {
		$condicao_1= " tbl_posto_fabrica.contato_estado='$estado' ";
	}
	$condicao_2= " 1=1 "; 
	if (strlen ($causa_troca) > 0 ) {
		$condicao_2= " tbl_os_troca.causa_troca=$causa_troca ";
	}
	$condicao_3 = " 1=1 "; 
	if (strlen ($consumidor_revenda) > 0 ) {
		$condicao_3= " tbl_os.consumidor_revenda='$consumidor_revenda' ";
	}

	$msg_erro = "";
	$sql_data_intervalo="SELECT '$data_final'::date - interval '120 days' > '$data_inicial'::date AS maoirqcv";
	$resDI = pg_exec($con,$sql_data_intervalo);
	$maiorqcv = pg_result ($resDI,0,0);

	if($maiorqcv=='f'){
			if(strlen($resumido) > 0){
				$sql_relatorio="SELECT	tbl_causa_troca.codigo   ,
										tbl_causa_troca.descricao,
										tbl_os.consumidor_revenda,
										count(tbl_os.os) as causa_qtde";
				$sql_group="GROUP BY tbl_causa_troca.codigo   ,
									 tbl_causa_troca.descricao,
									 tbl_os.consumidor_revenda
							ORDER BY tbl_causa_troca.codigo asc";
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
							tbl_admin.login                                                         ,
							tbl_causa_troca.descricao as descricao_causa
							";
				$sql_group="ORDER BY tbl_posto_fabrica.codigo_posto asc, tbl_os.sua_os asc";
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
				JOIN tbl_os_troca        ON tbl_os.os = tbl_os_troca.os
				JOIN tbl_causa_troca     USING(causa_troca)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE )
				AND   tbl_os.data_fechamento BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
				AND   $condicao_1
				AND   $condicao_2
				AND   $condicao_3
				$sql_group";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			if(strlen($resumido)==0){
				echo "<table border='0' cellspacing='0' cellpadding='0'>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#33FFCC'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; Ressarcimento Financeiro </b></font></td>";
				echo "</tr>";
				echo "</table>";
				echo "<br>";
			}
			$posto_anterior = "*";
			if (strlen($resumido) > 0) {
				echo "<br><table width='700' border='1' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
				echo "<tr height='2' class='titulo_tabela'>";
				echo "<td colspan='2' align='center'>CONSUMIDOR</td>";
				echo "</tr>";
				echo "<tr height='2' class='titulo_coluna'>";
				echo "<td align='left'>Causa</td>";
				echo "<td>Qtde</td>";
				echo "</tr>";
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$codigo              = trim(pg_result($res,$i,codigo));
					$descricao           = trim(pg_result($res,$i,descricao));
					$consumidor_revenda  = trim(pg_result($res,$i,consumidor_revenda));
					$causa_qtde          = trim(pg_result($res,$i,causa_qtde));
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
					if ($consumidor_revenda =='C') {
						echo "<tr class='Conteudo' height='2' bgcolor='$cor'>";
						echo "<td nowrap align='left'>" . $codigo . " - " . $descricao. "</td>";
						echo "<td nowrap align='center'>$causa_qtde</td>";
						echo "</tr>";
					}
				}
				echo "</table>";
				echo "<br>";
				echo "<table width='700' border='1' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
				echo "<tr height='2' class='titulo_tabela'>";
				echo "<td colspan='2'>REVENDA</td>";
				echo "</tr>";
				echo "<tr height='2' class='titulo_coluna'>";
				echo "<td align='left'>Causa</td>";
				echo "<td>Qtde</td>";
				echo "</tr>";
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$codigo              = trim(pg_result($res,$i,codigo));
					$descricao           = trim(pg_result($res,$i,descricao));
					$consumidor_revenda  = trim(pg_result($res,$i,consumidor_revenda));
					$causa_qtde          = trim(pg_result($res,$i,causa_qtde));
					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
					if ($consumidor_revenda =='R') {
						echo "<tr class='Conteudo' height='2' bgcolor='$cor'>";
						echo "<td nowrap align='left'>" . $codigo . " - " . $descricao. "</td>";
						echo "<td nowrap align='center'>$causa_qtde</td>";
						echo "</tr>";
					}
				}
				echo "</table>";
			}else{
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
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
					$causa_troca         = trim(pg_result($res,$i,descricao_causa));

					$pecas_originou_troca = array();

					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

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
							echo "</table>";
							echo "<br>";
							flush();
						}
						echo "<table width='100%' border='1' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
						echo "<tr height='25' class='titulo_tabela' valign='middle'>";
						echo "<td colspan='8'>POSTO: $posto_completo</td>";
						echo "</tr>";
						echo "<tr height='15' class='titulo_coluna'>";
						echo "<td>OS</td>";
						echo "<td>Produto</td>";
						echo "<td>Produto Troca</td>";
						echo "<td>Abertura</td>";
						echo "<td>Troca</td>";
						echo "<td>Pedido</td>";
						echo "<td>Responsável</td>";
						echo "<td>Peça Origiou a Troca</td>";
						echo "</tr>";
					}

					$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

					if ($ressarcimento == "t") $cor = "#33FFCC";

					echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
					echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
					echo "<td nowrap align='left'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>$produto_descricao</acronym></td>";
					echo "<td nowrap align='left'><acronym title='REFERÊNCIA: $troca_por_referencia \n DESCRIÇÃO: $troca_por_descricao' style='cursor: hand;'>$troca_por_descricao</acronym></td>";
					echo "<td nowrap>$data_abertura</td>";
					echo "<td nowrap>$data_fechamento</td>";
					echo "<td nowrap>$pedido</td>";
					echo "<td nowrap align='left'>$login</td>";
					echo "<td nowrap align='left'>".implode(", ",$pecas_originou_troca)."</td>";
					echo "</tr>";
					echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
					echo "<td nowrap align='left' colspan='8'>Causa da Troca : $causa_troca</td></tr>";

					$posto_anterior  = $posto;
					$nota_fiscal     = null;
					$login           = null;
				}
			}
			echo "</table>";
		}else{
			echo "<br><FONT size='2' COLOR='#FF3333'><B>Nenhum resultado encontrado!</B></FONT><br><br>";
		}
	}else{
		$msg_erro = "O intervalo de pesquisa não pode exceder 120 dias!";
	}
}

include "rodape.php";
?>
