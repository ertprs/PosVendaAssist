<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if($_GET['ajax']=='sim') {

	if (strlen ($_GET["ano"]) == 0) $erro .=  "Selecione o ano<br>";
	if (strlen ($_GET["mes"]) == 0) $erro .=  "Selecione o mês<br>";

	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $_GET["mes"], 1, $_GET["ano"]));
		$data_final   = date("Y-m-t 23:59:59",  mktime(0, 0, 0, $_GET["mes"], 1, $_GET["ano"]));
	}

	if (strlen($erro) > 0) {

		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;

	}else $listar = "ok";
	if ($listar == "ok") {
	
	$sql = "SELECT  tbl_os.os                                                       ,
			tbl_os.sua_os                                                   ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura     ,
			tbl_produto.referencia                     AS produto_referencia,
			tbl_produto.descricao                      AS produto_descricao ,
			(select produto from tbl_os_item join tbl_os_produto using(os_produto)
				where tbl_os_produto.os = tbl_os.os and servico_realizado=45
			)
		FROM tbl_os
		JOIN tbl_produto    USING(produto)
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING(produto)
		WHERE fabrica = $login_fabrica
		AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
	//if ($ip == "201.27.213.76") { echo nl2br($sql); }
	
		//$res = pg_exec ($con,$sql);
	
		if (pg_numrows($res) > 0) {
			$total = 0;
			
			
			$resposta  .= "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
			
			$resposta  .=  "<br><br>";
			$resposta  .=  "<FONT SIZE=\"2\">(*) Peças que estão inativas.</FONT>";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			$resposta  .=  "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			$resposta  .=  "<TD><b>Referência</b></TD>";
			$resposta  .=  "<TD><b>Produto</b></TD>";
			$resposta  .=  "<TD><b>Ocorrência</b></TD>";
			$resposta  .=  "<TD><b>Custo</b></TD>";
			$resposta  .=  "<TD><b>%</b></TD>";
			$resposta  .=  "</TR>";


			for ($x = 0; $x < pg_numrows($res); $x++) {
				/*$total_pago = $total_pago + pg_result($res,$x,total_os);*/
			}
			
			for ($i=0; $i<pg_numrows($res); $i++){
/*
				$referencia = trim(pg_result($res,$i,referencia));
				$ativo      = trim(pg_result($res,$i,ativo))     ;
				$descricao  = trim(pg_result($res,$i,descricao)) ;
				$produto    = trim(pg_result($res,$i,produto))   ;
				$linha      = trim(pg_result($res,$i,linha))     ;
				$ocorrencia = trim(pg_result($res,$i,ocorrencia));
				$total_os   = trim(pg_result($res,$i,total_os))  ;
	
				if ($total_pago > 0) $porcentagem = (($total_os * 100) / $total_pago);
	*/
				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
	
				// Todo produto que for inativo estará com um (*) na frente para indicar se está Inativo ou Ativo.
				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';} 
	
				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='left'nowrap>$ativo<a href='javascript:AbrePeca(\"$produto\",\"$aux_data_inicial\",\"$aux_data_final\",\"$linha\",\"$estado\");'>$referencia</a></TD>";
				$resposta  .=  "<TD align='left'>$descricao</TD>";
				$resposta  .=  "<TD >$ocorrencia</TD>";
				$resposta  .=  "<TD align='right'>R$". number_format($total_os,2,",",".") ." </TD>";
				$resposta  .=  "<TD align='right'>". number_format($porcentagem,2,",",".") ." %</TD>";
				$resposta  .=  "</TR>";
				
				$total = $total_os + $total;
	
			}
			$resposta .=  "<tr class='Conteudo' bgcolor='#d9e2ef'><td colspan='3'><font size='2'><b><CENTER>VALOR CUSTO TOTAL</b></td><td colspan='2'><font size='2' color='009900'><b>R$". number_format($total,2,",",".") ." </b></td></tr>";
			$resposta .= " </TABLE>";
			
			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";
			
			// monta URL
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);
			$linha        = trim($_POST["linha"]);
			$estado       = trim($_POST["estado"]);
			$criterio     = trim($_POST["criterio"]);
			/*
			$resposta .= "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .= "<tr>";
			$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='relatorio_field_call_rate_produto-xls.php?data_inicial=$data_inicial&data_final=$data_final&linha=$linha&estado=$estado&criterio=$criterio' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			$resposta .=  "</tr>";
			$resposta .=  "</table>";*/
			
		}else{
			$resposta .=  "<br>";
			
			$resposta .= "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
		}
		$listar = "";
		
	}
	if (strlen($erro) > 0) {
		echo "no|".$msg;
	}else{
		echo "ok|".$resposta;
	}
	exit;

	flush();

}

$layout_menu = "financeiro";
$title = "Relatório de Troca em Garantia";

include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>



<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaPesquisa (http,componente,componente_erro,componente_carregando) {
	var com = document.getElementById(componente);
	var com2 = document.getElementById(componente_erro);
	var com3 = document.getElementById(componente_carregando);
	if (http.readyState == 1) {
		
		Page.getPageCenterX() ;
		com3.style.top = (Page.top + Page.height/2)-100;
		com3.style.left = Page.width/2-75;
		com3.style.position = "absolute";

		com3.innerHTML   = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
		com3.style.visibility = "visible";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
					com2.innerHTML  = " ";
					com2.style.visibility = "hidden";
					com3.innerHTML = "<br>&nbsp;&nbsp;Dados carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',3000);
				}
				if (results[0] == 'no') {
					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					com2.style.visibility = "visible";
					com3.style.visibility = "hidden";
				}
				
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function esconde_carregar(componente_carregando) {
	document.getElementById('carregando').style.visibility = "hidden";
}

function Exibir (componente,componente_erro,componente_carregando) {

	var1 = document.frm_relatorio.mes.value;
	var2 = document.frm_relatorio.ano.value;
	var3 = document.frm_relatorio.linha.value;
	var4 = document.frm_relatorio.estado.value;

	parametros = 'mes='+var1+'&ano='+var2+'&linha='+var3+'&estado='+var4+'&ajax=sim';

	url = "<?=$PHP_SELF?>?"+parametros;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPesquisa (http,componente,componente_erro,componente_carregando) ; } ;
	http.send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.getPageCenterX = function (){

	var fWidth;
	var fHeight;		
	//For old IE browsers 
	if(document.all) { 
		fWidth = document.body.clientWidth; 
		fHeight = document.body.clientHeight; 
	} 
	//For DOM1 browsers 
	else if(document.getElementById &&!document.all){ 
			fWidth = innerWidth; 
			fHeight = innerHeight; 
		} 
		else if(document.getElementById) { 
				fWidth = innerWidth; 
				fHeight = innerHeight; 		
			} 
			//For Opera 
			else if (is.op) { 
					fWidth = innerWidth; 
					fHeight = innerHeight; 		
				} 
				//For old Netscape 
				else if (document.layers) { 
						fWidth = window.innerWidth; 
						fHeight = window.innerHeight; 		
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}


function AbrePeca(produto,data_inicial,data_final,linha,estado){
	janela = window.open("relatorio_field_call_rate_pecas_custo.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}
</script>

<? include "javascript_pesquisas.php" ?>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando' width='150'></div>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Troca em Garantia</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'nowrap><font size='2'>Mês</td>
					<td align='left'>
						<select name="mes" size="1" class="Caixa">
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
					<td align='right' nowrap><font size='2'>Ano</td> 
					<td align='left'>
					<select name="ano" size="1" class="Caixa">
						<option value=''></option>
						<?
						//for ($i = 2003 ; $i <= date("Y") ; $i++) {
						for($i = date("Y"); $i > 2003; $i--){
							echo "<option value='$i'";
							if ($ano == $i) echo " selected";
							echo ">$i</option>";
						}
						?>
						</select>
					</td>
					<td width="10">&nbsp;</td>


				</tr>

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Linha</td>
					<td align='left'>
						<?
					$sql = "SELECT  *
							FROM    tbl_linha
							WHERE   tbl_linha.fabrica = $login_fabrica
							ORDER BY tbl_linha.nome;";
					$res = pg_exec ($con,$sql);
					
					if (pg_numrows($res) > 0) {
						echo "<select name='linha' class='Caixa'>\n";
						echo "<option value=''>ESCOLHA</option>\n";
						
						for ($x = 0 ; $x < pg_numrows($res) ; $x++){
							$aux_linha = trim(pg_result($res,$x,linha));
							$aux_nome  = trim(pg_result($res,$x,nome));
							
							echo "<option value='$aux_linha'"; 
							if ($linha == $aux_linha){
								echo " SELECTED "; 
								$mostraMsgLinha = "<br> da LINHA $aux_nome";
							}
							echo ">$aux_nome</option>\n";
						}
						echo "</select>\n";
					}
					?>

					</td>
					<td align='right'><font size='2'>Estados</td> 
					<td align='left'>
					<select name="estado" size="1" class='Caixa'>
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
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='button' onclick="javascript:Exibir('dados','erro','carregando')" style="cursor:pointer " value='Consultar'>
		</td>
	</tr>
</table>
</FORM>


<?

echo "<div id='dados'></div>";


?>

<p>

<? include "rodape.php" ?>
