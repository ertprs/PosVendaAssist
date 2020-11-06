<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';


if($_GET['ajax']=='sim') {

	if (strlen($_GET["data_inicial_01"]) == 0)$erro = "Data Inválida";
	if ($_GET["data_inicial_01"] == 'dd/mm/aaaa') $erro = "Data Inválida";
	if ($_GET["data_final_01"] == 'dd/mm/aaaa')   $erro = "Data Inválida";

	if ($_GET["data_inicial_01"] > $_GET["data_final_01"]) 
		$erro = 'Data Inválida';

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);

		$dat = explode ("/", $data_inicial );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";

		if (strlen($msg_erro) == 0) {
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

		}
		else $erro = $msg_erro;
	}

	$promotor = $_GET['promotor'];
	if(strlen($promotor)>0){
		$cond_1 = " AND tbl_os_status.admin = $promotor ";
	}
	if (strlen($erro) == 0) {

		list($d, $m, $y) = explode("/", $_GET["data_final_01"]);
		if(!checkdate($m,$d,$y)) 
			$msg_erro = "Data Inválida";

		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}



	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);
		$msg = $erro;


	}else $listar = "ok";
	if ($listar == "ok") {
		$sql = "SELECT DISTINCT tbl_posto_fabrica.codigo_posto    ,
				tbl_posto.nome                             ,
				tbl_os.os                                  ,
				tbl_os.sua_os                              ,
				(tbl_os.pecas+tbl_os.mao_de_obra) as valor ,
				tbl_os.consumidor_nome                     ,
				tbl_admin.nome_completo                    ,
				tbl_admin.admin                            ,
				tbl_escritorio_regional.descricao          ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura
			FROM tbl_os ";
		if($login_fabrica == 20) {
			$sql .= " LEFT JOIN tbl_os_extra using(os)
					LEFT JOIN tbl_extrato using(extrato)
					LEFT JOIN tbl_extrato_extra using(extrato) ";
		}
		$sql .= " JOIN tbl_os_status USING (os)
			JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_promotor_treinamento ON tbl_promotor_treinamento.promotor_treinamento = tbl_os.promotor_treinamento
			JOIN tbl_escritorio_regional ON tbl_promotor_treinamento.escritorio_regional = tbl_escritorio_regional.escritorio_regional
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os_status.status_os = 93
			AND tbl_os.tipo_atendimento = 16 ";
			if (strlen ($aux_data_inicial) > 0 AND strlen ($aux_data_final) > 0) {
				if($login_fabrica == 20){
					$sql .= " AND tbl_extrato_extra.exportado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
				}else{
					$sql .= " AND tbl_os.data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
				}
			}
			$sql .= " $cond_1
			ORDER BY tbl_posto_fabrica.codigo_posto";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;

			$resposta  .=  "<br><br>";
				#HD 109174 acrescenta link para detalhar consulta
				if($login_fabrica==20){
					$link_i = "<button type='button' onclick=\"window.location='detalhe_os_cortesia_comercial.php?data_ini=$aux_data_inicial&data_fim=$aux_data_final'\">";
					$link_f = "</button>";
					
				}
			$resposta .= $link_i."Agrupar Resultados".$link_f;
			$resposta  .=  "<table border='0' cellpadding='0' cellspacing='1' align='center' class='tabela' >";
			$resposta .= "<tr><td class='titulo_coluna' colspan='7'>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</td></tr>";
			$resposta  .=  "<TR class='titulo_coluna'>";
			$resposta  .=  "<TD align='left'>Promotor</TD>";
			$resposta  .=  "<TD>Escritório</TD>";
			$resposta  .=  "<TD align='left'>Posto</TD>";
			$resposta  .=  "<TD>OS</TD>";
			$resposta  .=  "<TD>Data Abertura</TD>";
			$resposta  .=  "<TD>Valor</TD>";
			$resposta  .=  "<TD>Consumidor</TD>";
			$resposta  .=  "</TR>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto))   ;
				$nome            = trim(pg_result($res,$i,nome))           ;
				$nome_completo   = trim(pg_result($res,$i,nome_completo))  ;
				$os              = trim(pg_result($res,$i,os))             ;
				$sua_os          = trim(pg_result($res,$i,sua_os))         ;
				$valor           = trim(pg_result($res,$i,valor))          ;
				$consumidor_nome = trim(pg_result($res,$i,consumidor_nome));
				$descricao       = trim(pg_result($res,$i,descricao))      ;
				$data_abertura   = trim(pg_result($res,$i,data_abertura))  ;
				$admin_x         = trim(pg_result($res,$i,admin))          ;

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='left'nowrap>$nome_completo</TD>";
				$resposta  .=  "<TD align='center' nowrap>$descricao</TD>";
				$resposta  .=  "<TD align='left' nowrap>$codigo_posto - $nome</TD>";
				$resposta  .=  "<TD align='center'><a href='os_press?os=$os' target='_blank'>$sua_os</a></TD>";
				$resposta  .=  "<TD align='center'>$data_abertura</TD>";
				$resposta  .=  "<TD align='center'>R$". number_format($valor,2,",",".") ." </TD>";
				$resposta  .=  "<TD align='center'>$consumidor_nome</TD>";
				$resposta  .=  "</TR>";

				$total = $valor + $total;
			}
			$resposta .=  "<tfoot><tr class='Conteudo' bgcolor='#d9e2ef'><td colspan='4'><font size='2'><b><CENTER>VALOR TOTAL</b></td><td colspan='50%'><font size='2' color='009900'><b>R$". number_format($total,2,",",".") ." </b></td></tr>
			<tr class='Conteudo' bgcolor='#d9e2ef'><td colspan='4'><font size='2'><b><CENTER>TOTAL DA OS</b></td><td colspan='50%'><font size='2' color='009900'><b>$i</b></td></tr>
			</tfoot>";
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";

			// monta URL
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);
		}else{
			$resposta .=  "<br>";
//			$resposta .=  "$sql";
			$resposta .= "<b>Nenhum resultado encontrado.</b>";
		}
		$listar = "";

	}
	if (strlen($erro) > 0) {
		echo "no|".$msg;

	}else{
//		$resposta .=  "$sql";
		echo "ok|".$resposta;
	}
	exit;

	flush();

}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS DE CORTESIA COMERCIAL";

include "cabecalho.php";

?>
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
	border:1px solid #596d9b;
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
		com3.style.position = "relative";

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

					com3.innerHTML = "<br>&nbsp;&nbsp;Informações carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',1500);
				}
				if (results[0] == 'no') {
					Page.getPageCenterX() ;
					com2.style.top = (Page.top + Page.height/2)-100;

					com2.style.position = "relative";
					com2.style.background = "red";
					com2.style.width = "700px";
					com2.style.margin = "auto";

					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					com2.style.visibility = "visible";
					com3.style.visibility = "hidden";
				}
			}
		}
	}
}
function esconde_carregar(componente_carregando) {
	document.getElementById('carregando').style.visibility = "hidden";
}

function Exibir (componente,componente_erro,componente_carregando,fabrica) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	var var3 = document.frm_relatorio.promotor.value;

	var parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&promotor='+var3+'&ajax=sim';

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

</script>

<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.maskedinput.js" type="text/javascript"></script>
<script src="js/datePicker.v1.js" type="text/javascript"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div class="texto_avulso">
	<?
		if($login_fabrica == 20){
			echo "A data utilizada como parâmetro é a data de exportação do extrato!";
		}else{
			echo "A data utilizada como parâmetro é a data de digitação da ordem de serviço!";
		}
	?>
</div>
<br />
<div id='erro' style=' visibility:hidden;opacity:.85;' class='msg_erro'></div>
<table width='700' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center'>
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='0' >
				<tr>
					<td width="220">&nbsp;</td>
					<td align='left' width="130">Data Inicial
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td align='left'>
						Data Final
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>

				<tr class="Conteudo">
					<td width="10">&nbsp;</td>					
					<td align='left' colspan='5'>
					Promotor<br />
					<select name='promotor' size='1' style='width:250px' class="frm">
					<option></option>
					<?
					$sql = "SELECT tbl_promotor_treinamento.admin,
										tbl_promotor_treinamento.nome,
										tbl_promotor_treinamento.email,
										tbl_promotor_treinamento.ativo,
										tbl_escritorio_regional.descricao
							FROM tbl_promotor_treinamento
							JOIN tbl_escritorio_regional USING(escritorio_regional)
							WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
							AND   tbl_promotor_treinamento.ativo ='t'
							ORDER BY tbl_promotor_treinamento.nome";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						for($i=0;pg_numrows($res)>$i;$i++){
							$x_promotor_treinamento = pg_result ($res,$i,admin);
							$x_nome                 = pg_result ($res,$i,nome);
							echo "<option ";
							if ($promotor == $x_promotor_treinamento ) echo " selected ";
							echo " value='$x_promotor_treinamento' >" ;
							echo $x_nome;
							echo "</option>\n";
						}?>
					</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td align="center" colspan="5" style="padding-top:10px;"><input type='button' onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar'></td>
				</tr>
				<?}?>
			</table><br>
			
		</td>
	</tr>
</table>
<div id='carregando' style='position: absolute; bottom:0; visibility:hidden;opacity:.90;' class='Carregando'></div>
</FORM>
<?
echo "<div id='dados'></div>";
?>
<p>
<? include "rodape.php" ?>