<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";


$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if ($btn_finalizar == 1) {
	if(strlen($_POST["linha"]) > 0) $linha = trim($_POST["linha"]);

	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>no ESTADO $estado";
	}

	if($login_fabrica == 20 and $pais !='BR'){
		if(strlen($_POST["pais"]) > 0) $pais = trim($_POST["pais"]);
	}
	$tipo_os = trim($_POST['tipo_os']);

	$codigo_posto = "";
	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_descricao']) ;
	$multiplo           = trim($_POST['radio_qtde_produtos']);

	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){
		$sql = "SELECT produto
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
		}
	}

	if (strlen($erro) == 0) {
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_inicial = @pg_result ($fnc,0,0);
	}
	if (strlen($erro) == 0) {
		$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		if (strlen($erro) == 0)                    $aux_data_final = @pg_result ($fnc,0,0);
	}

	$replicar      = $_POST['PickList'];

	if (count($replicar)>0 and $multiplo == 'muitos'){ // HD 71431
		$array_produto = array();
		$produto_lista = array();
		for ($i=0;$i<count($replicar);$i++){
			$p = trim($replicar[$i]);
			if (strlen($p) > 0) {
				$sql = "SELECT  tbl_produto.produto,
								tbl_produto.referencia,
								tbl_produto.descricao
					from tbl_produto
					join tbl_familia using(familia)
					where tbl_familia.fabrica = $login_fabrica
					and tbl_produto.referencia = '$p'";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$multi_produto    = trim(pg_result($res,0,produto));
					$multi_referencia = trim(pg_result($res,0,referencia));
					$multi_descricao  = trim(pg_result($res,0,descricao));
					array_push($array_produto,$multi_produto);
					array_push($produto_lista,array($multi_produto,$multi_referencia,$multi_descricao));
				}
			}
		}
		$lista_produtos = implode($array_produto,",");
	}

	if (strlen($erro) == 0) $listar = "ok";

	if (strlen($erro) > 0) {
		$data_inicial       = trim($_POST["data_inicial_01"]);
		$data_final         = trim($_POST["data_final_01"]);
		$linha              = trim($_POST["linha"]);
		$estado             = trim($_POST["estado"]);
		$tipo_pesquisa      = trim($_POST["tipo_pesquisa"]);
		$pais               = trim($_POST["pais"]);
		$origem             = trim($_POST["origem"]);
		$criterio           = trim($_POST["criterio"]);
		$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
		$produto_descricao  = trim($_POST['produto_descricao']) ; // HD 2003 TAKASHI
		$tipo_os            = trim($_POST['tipo_os']);

		$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg_erro .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

include "cabecalho.php";

?>

<script language="JavaScript">

function AbrePeca(produto,data_inicial,data_final,linha,estado,posto,pais,marca,tipo_data,aux_data_inicial,aux_data_final,lista_produtos){
	janela = window.open("fcr_os_item.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&posto=" + posto +"&pais=" + pais +"&marca=" + marca + "&tipo_data=" + tipo_data +"&aux_data_inicial="+aux_data_inicial+"&aux_data_final="+aux_data_final+"&lista_produtos="+lista_produtos,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
	janela.focus();
}

</script>

<style type="text/css">

#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
#logo{
	BORDER-RIGHT: 1px ;
	BORDER-TOP: 1px ;
	BORDER-LEFT: 1px ;
	BORDER-BOTTOM: 1px ;
	position: absolute;
	right: 10px;
	z-index: 5;
}
</style>


<?
include "../javascript_pesquisas.php";
include "../javascript_calendario.php";
?>

<script type="text/javascript" charset="utf-8">
	jQuery.fn.slideFadeToggle = function(speed, easing, callback) {
		return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
	}
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

	function toogleProd(radio){
		var obj = document.getElementsByName('radio_qtde_produtos');
		if (obj[0].checked){
			$('#id_um').show("");
			$('#id_multi').hide("");
		}
		if (obj[1].checked){
			$('#id_um').hide("");
			$('#id_multi').show("");
		}
	}

	var singleSelect = true;  
	var sortSelect = true; 
	var sortPick = true; 


	function initIt() {
	  var pickList = document.getElementById("PickList");
	  var pickOptions = pickList.options;
	  pickOptions[0] = null; 
	}

	function addIt() {
		if ($('#produto_referencia_multi').val()=='')
			return false;
		if ($('#produto_descricao_multi').val()=='')
			return false;

		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
		pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

		$('#produto_referencia_multi').val("");
		$('#produto_descricao_multi').val("");

		if (sortPick) {
			var tempText;
			var tempValue;
			while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
				tempText = pickOptions[pickOLength-1].text;
				tempValue = pickOptions[pickOLength-1].value;
				pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
				pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
				pickOptions[pickOLength].text = tempText;
				pickOptions[pickOLength].value = tempValue;
				pickOLength = pickOLength - 1;
			}
		}

		pickOLength = pickOptions.length;
		$('#produto_referencia_multi').focus();
	}
	function delIt() {
	  var pickList = document.getElementById("PickList");
	  var pickIndex = pickList.selectedIndex;
	  var pickOptions = pickList.options;
	  while (pickIndex > -1) {
		pickOptions[pickIndex] = null;
		pickIndex = pickList.selectedIndex;
	  }
	}

	function selIt(btn) {
		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		for (var i = 0; i < pickOLength; i++) {
			pickOptions[i].selected = true;
		}
		
	}
</script>

<link rel="stylesheet" href="../js/blue/style.css" type="text/css" id="" media="print, projection, screen" />


<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="js/chili-1.8b.js"></script>
<script type="text/javascript" src="js/docs.js"></script>
<script>
// add new widget called repeatHeaders
	$(function() {
		// add new widget called repeatHeaders
		$.tablesorter.addWidget({
			// give the widget a id
			id: "repeatHeaders",
			// format is called when the on init and when a sorting has finished
			format: function(table) {
				// cache and collect all TH headers
				if(!this.headers) {
					var h = this.headers = [];
					$("thead th",table).each(function() {
						h.push(
							"<th>" + $(this).text() + "</th>"
						);

					});
				}

				// remove appended headers by classname.
				$("tr.repated-header",table).remove();

				// loop all tr elements and insert a copy of the "headers"
				for(var i=0; i < table.tBodies[0].rows.length; i++) {
					// insert a copy of the table head every 10th row
					if((i%20) == 0) {
						if(i!=0){
						$("tbody tr:eq(" + i + ")",table).before(
							$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

						);
					}}
				}

			}
		});
		$("table").tablesorter({
			widgets: ['zebra','repeatHeaders']
		});

	});



</script>


<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg_erro) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>

	</td>
</tr>
</table>

<br>
<?
}
if (1==2){ /* Tulio solicito que fosse retirado a mensagem. Resolvi retirar tudo! - Fabio - 03/10/2008 */
	echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:2px 10px 2px 10px;font-size:12px'>";
	echo "<p style='text-align:left;padding:0px;'><b>ATENÇÃO: </b>Este relatório de BI considera toda  OS que está finalizada, sendo possível fazer a pesquisa com os dados abaixo. Foi feita a carga até o mês de março, caso queira utilizar o antigo relatório <a href='../relatorio_field_call_rate_produto.php'>clique aqui.</a> </p>";
	echo "<p style='text-align:left'>TELECONTROL</p>";
	echo "</div>";
}
?>

<br>

<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2" id='Formulario'>
	<CAPTION>Pesquisa</CAPTION>
	<TBODY>
<!--
	<TR>
		<TH>Mês</TH>
		<TD>
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
		</TD>
		<TH>Ano</TH>
		<TD>
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
		</TD>
	</TR>
-->
	<TR>
		<TH>Data Inicial</TH>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" class="frm" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" ></TD>
		<TH>Data Final</TH>
		<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" class="frm" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
	</TR>
	<TR>
		<TH>Data</TH>
		<TD colspan='3'>
			<input type='radio' name='tipo_data' value='data_digitacao' <?if($tipo_data=="data_digitacao") echo "CHECKED";?>> Digitação
			<input type='radio' name='tipo_data' value='data_abertura' <?if($tipo_data=="data_abertura") echo "CHECKED";?>> Abertura
			<input type='radio' name='tipo_data' value='data_fechamento' <?if($tipo_data=="data_fechamento") echo "CHECKED";?>> Fechamento
			<input type='radio' name='tipo_data' value='data_finalizada'<?if($tipo_data=="data_finalizada") echo "CHECKED";?>> Finalizada
			<br>
			<input type='radio' name='tipo_data' value='extrato_geracao'<?if($tipo_data=="extrato_geracao") echo "CHECKED";?>> Geração de Extrato
			<input type='radio' name='tipo_data' value='extrato_aprovacao'<?if($tipo_data=="extrato_aprovacao") echo "CHECKED";?>> Aprovação do Extrato
			<?if($login_fabrica==20){?>
			<input type='radio' name='tipo_data' value='extrato_exportacao'<?if($tipo_data=="extrato_exportacao") echo "CHECKED";?>> Data pagamento
			<?}?>
		</TD>
	</TR>

	<?if($login_fabrica==3 /*OR $login_fabrica == 15*/){
		# Comentado para Latinatec no HD 72127 ?>
	<TR>
		<TH>Marca</TH>
		<TD>
			<?
			$sql = "SELECT  *
					FROM    tbl_marca
					WHERE   tbl_marca.fabrica = $login_fabrica
					ORDER BY tbl_marca.nome;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='marca'class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_marca = trim(pg_result($res,$x,marca));
					$aux_nome  = trim(pg_result($res,$x,nome));

					echo "<option value='$aux_marca'";
					if ($marca == $aux_marca){
						echo " SELECTED ";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<?}?>
	<TR>
		<TH>Linha</TH>
		<TD colspan='3'>
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='linha' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				if($login_fabrica == 15){
					echo "<option value='LAVADORAS LE'>";
					echo "LAVADORAS LE</option>";
					echo "<option value='LAVADORAS LS'>";
					echo "LAVADORAS LS</option>";
					echo "<option value='LAVADORAS LX'>";
					echo "LAVADORAS LX</option>";
					echo "<option value='IMPORTAÇÃO DIRETA WAL-MART'>";
					echo "IMPORTAÇÃO DIRETA WAL-MART</option>";
					echo "<option value='Purificadores / Bebedouros - Eletrônicos'>";
					echo "Purificadores / Bebedouros - Eletrônicos</option>";
				}
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
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<TR>
		<TH>Família</TH>
		<TD colspan='3'>
			<?
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='familia' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia   = trim(pg_result($res,$x,familia));
					$aux_descricao = trim(pg_result($res,$x,descricao));

					echo "<option value='$aux_familia'";
					if ($familia == $aux_familia){
						echo " SELECTED ";
						$mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
					}
					echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
		?>
		</TD>
	</TR>
	<? if ($login_fabrica ==50) {
		if (count($lista_produtos)>0){
			$display_um_produto    = "display:none";
			$display_multi_produto = "";
			$display_um            = "";
			$display_multi         = " CHECKED ";
		}else{
			$display_um_produto    = "";
			$display_multi_produto = "display:none";
			$display_um            = " CHECKED ";
			$display_multi         = "";
		}
	?>	
	
	<TR>
		<TH>SELECIONE</TH>
		<Td nowrap colspan='3'>Um produto
			<input type="radio" name="radio_qtde_produtos" value='um'  <?=$display_um?>  onClick='javascript:toogleProd(this)'>
			&nbsp;&nbsp;&nbsp;&nbsp;
			Vários Produtos
			<input type="radio" name="radio_qtde_produtos" value='muitos' <?=$display_multi?> onClick='javascript:toogleProd(this)'>
		</td>
	</tr>
	<TR>
		<TH colspan='100%' nowrap>
			<div id='id_um' style='<?echo $display_um_produto;?>'>
			<b>Ref. Produto:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="produto_referencia" id="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<b>Descrição Produto:&nbsp;<input type="text" name="produto_descricao" id="produto_descricao" value="<? echo $produto_descricao ?>" size="15" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
			</div>

			<div id='id_multi' style='<?echo $display_multi_produto;?>'>
			Ref. Produto:&nbsp;&nbsp;&nbsp;<input type="text" name="produto_referencia_multi" id="produto_referencia_multi" value="" size="10" maxlength="20" class='frm'>&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia_multi, document.frm_pesquisa.produto_descricao_multi,'descricao')">
			&nbsp;&nbsp;&nbsp;
			Descrição Produto:&nbsp;&nbsp;&nbsp;<input type="text" name="produto_descricao_multi" id="produto_descricao_multi" value="" size="20" maxlength="50" class='frm'>&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia_multi, document.frm_pesquisa.produto_descricao_multi,'descricao')">
			<input type='button' name='adicionar_produto' id='adicionar_produto' value='Adicionar' class='frm' onclick='addIt();'>
			<br>
			<b style='font-weight:normal;color:gray;font-size:10px'>(Selecione o produto e clique em 'Adicionar')</b>
			<br>
				<SELECT MULTIPLE SIZE='6' style="width:80%" ID="PickList" NAME="PickList[]" class='frm'>

				<?
				if (count($produto_lista)>0){
					for ($i=0; $i<count($produto_lista); $i++){
						$linha_prod = $produto_lista[$i];
						echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
					}
				}
				?>

				</SELECT>
				<input TYPE="BUTTON" VALUE="Remover" ONCLICK="delIt();" class='frm'></input>
			</div>

		</th>
	</TR>
	<? }else{ ?>
	<TR>
		<TH>Ref. Produto</TH>
		<TD>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > &nbsp;
			<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia')">
		</TD>
		<TH>Descrição Produto</TH>
		<TD>
			<input class="frm" type="text" name="produto_descricao" size="15" value="<? echo $produto_descricao ?>" >&nbsp;
			<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao')">
		</TD>
	</TR>	
	
	<? } ?>
	<TR>
		<TH>País</TH>
		<TD>
		<?
			$sql = "SELECT  *
					FROM    tbl_pais
					$w
					ORDER BY tbl_pais.nome;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select name='pais' class='frm'>\n";
				if(strlen($pais) == 0 ) $pais = 'BR';

				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_pais  = trim(pg_result($res,$x,pais));
					$aux_nome  = trim(pg_result($res,$x,nome));

					echo "<option value='$aux_pais'";
					if ($pais == $aux_pais){
						echo " SELECTED ";
						$mostraMsgPais = "<br> do PAÍS $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</TD>
	</TR>
	<TR>
		<TH>Cód. Posto</TH>
		<TD>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</TD>
		<TH>Nome Posto</TH>
		<TD nowrap>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</TD>
	</TR>
	<TR>
		<TH>Por região</TH>
		<td>
			<select name="estado" class='frm'>
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
		</TD>
	</TR>
	<tr>
		<th align='right'>Tipo Arquivo para Download</th>
		<TD>

		<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
		&nbsp;&nbsp;&nbsp;
		<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
		</TD>
	</TR>

	</TBODY>
	<TFOOT>
	<TR>
		<input type='hidden' name='btn_finalizar' value='0'>
		<TD colspan="4"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { <?if($login_fabrica==50){ echo "selIt();"; }?> document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
		</TD>
	</TR>
	</TFOOT>
</TABLE>

</FORM>
</DIV>

<?
if ($listar == "ok") {
	if(strlen($codigo_posto)>0){
		$sql = "SELECT  posto
				FROM    tbl_posto_fabrica
				WHERE   fabrica      = $login_fabrica
				AND     codigo_posto = '$codigo_posto';";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
	}

	if (strlen ($linha)           > 0) $cond_1 = " AND   BI.linha   = $linha ";
	if (strlen ($estado)          > 0) $cond_2 = " AND   BI.estado  = '$estado' ";
	if (strlen ($posto)           > 0) $cond_3 = " AND   BI.posto   = $posto ";
	if (strlen ($produto)         > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 
	if (strlen ($pais)            > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
	if (strlen ($marca)           > 0) $cond_7 = " AND   BI.marca   = $marca ";
	if (strlen ($familia)         > 0) $cond_8 = " AND   BI.familia  = $familia ";
	if (strlen ($lista_produtos)  > 0) {
		$cond_10 = " AND   BI.produto in ( $lista_produtos) ";
		$cond_4 = "";
	}

	if (strlen($tipo_data) == 0 ) $tipo_data = 'data_fechamento';
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
		$cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}

	if($login_fabrica == 20 and $pais !='BR'){
		$produto_descricao   ="tbl_produto_idioma.descricao ";
		$join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
	}else{
		$produto_descricao   ="tbl_produto.descricao ";
		$join_produto_idioma =" ";
	}

	if($login_fabrica == 3 /*OR $login_fabrica == 15*/){#HD49228
		$produto_marca = " MA.nome                AS m_nome     , ";
		$join_marca    = " LEFT JOIN tbl_marca   MA ON MA.marca   = BI.marca";
		$order_marca   = ", m_nome ";
	}

	# HD 65558 - não mostrar OSs excluidas

	$sql = "SELECT  PR.produto                           ,
					PR.ativo                             ,
					PR.referencia                        ,
					PR.descricao                         ,
					FA.descricao           AS f_nome     ,
					LI.nome                AS l_nome     ,
					$produto_marca
					count(BI.os)           AS ocorrencia ,
					SUM(BI.mao_de_obra)    AS mao_de_obra,
					SUM(BI.qtde_pecas)     AS qtde_pecas
		FROM      bi_os BI
		JOIN      tbl_produto PR ON PR.produto = BI.produto
		JOIN      tbl_linha   LI ON LI.linha   = BI.linha
		JOIN      tbl_familia FA ON FA.familia = BI.familia
		$join_marca
		WHERE BI.fabrica = $login_fabrica
		AND BI.excluida IS NOT TRUE
		 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
		GROUP BY    PR.produto                           ,
					PR.ativo                             ,
					PR.referencia                        ,
					PR.descricao                         ,
					f_nome                               ,
					l_nome                               
					$order_marca
		ORDER BY ocorrencia DESC ";

#echo nl2br($sql); exit;

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$total = 0;
		echo "<br>";

		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";


		echo "<br><br>";

		$data = date("Y-m-d").".".date("H-i-s");

		$arquivo_nome     = "bi-os-$login_fabrica.$login_admin.".$formato_arquivo;
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		$fp = fopen ($arquivo_completo_tmp,"w");

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"<html>");
			fputs ($fp,"<body>");
		}
		if ($login_fabrica==50) { // HD 41116
			echo "<span id='logo'><img src='../imagens_admin/colormaq_.gif' border='0' width='160' height='55'></span>";
		}
		echo "<p id='id_download' style='display:none'><a href='../xls/$arquivo_nome' target='_blank'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do arquivo em  ".strtoupper($formato_arquivo)."</font></a></p>";

		$conteudo .="<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
		$conteudo .="<thead>";
		$conteudo .="<TR>";
		$conteudo .="<Th width='100' height='15'>Referência</Th>";
		$conteudo .="<Th height='15'>Produto</Th>";
		if($login_fabrica==3 /*OR $login_fabrica == 15*/) $conteudo .="<Th>Marca</Th>";
		$conteudo .="<Th><b>Linha</b></Th>";
		$conteudo .="<Th><b>Família</b></Th>";
		$conteudo .="<Th width='120' height='15'>Ocorrência</Th>";
		$conteudo .="<Th width='50' height='15'>%</Th>";
		$conteudo .="<Th width='50' height='15'>Qtde. Peças</Th>";
		$conteudo .="<Th width='50' height='15'>M.O</Th>";
		$conteudo .="</TR>";
		$conteudo .="</thead>";
		$conteudo .="<tbody>";

		echo $conteudo;
		if ($formato_arquivo=='CSV'){
			$conteudo = "";
			$conteudo .= "REFERÊNCIA;PRODUTO;LINHA;FAMÍLIA;OCORRÊNCIA;%;QTDE. PECAS;M.O \n";
		}
		fputs ($fp,$conteudo);

		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
		}
		for ($i=0; $i<pg_numrows($res); $i++){
			$conteudo = "";
			$referencia   = trim(pg_result($res,$i,referencia));
			$ativo        = trim(pg_result($res,$i,ativo));
			$descricao    = trim(pg_result($res,$i,descricao));
			if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
				$descricao    = "<font color = 'red'>Tradução não cadastrada.</font>";
			}
			$produto      = trim(pg_result($res,$i,produto));
			$familia_nome = trim(pg_result($res,$i,f_nome));
			$linha_nome   = trim(pg_result($res,$i,l_nome));
			if($login_fabrica == 3 /*OR $login_fabrica == 15*/){
				$marca_nome   = trim(pg_result($res,$i,m_nome));
			}
			$ocorrencia   = trim(pg_result($res,$i,ocorrencia));
			$mao_de_obra  = trim(pg_result($res,$i,mao_de_obra));
			$qtde_pecas   = trim(pg_result($res,$i,qtde_pecas));

			if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

			if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';}

			$total_mo    += $mao_de_obra;
			$total_peca  += $qtde_pecas ;
			$total       += $ocorrencia ;

			$porcentagem = number_format($porcentagem,2,",",".");
			$mao_de_obra = number_format($mao_de_obra,2,",",".");

			$conteudo .="<TR>";
			$conteudo .="<TD align='left' nowrap>";
			if ($formato_arquivo<>'XLS'){
				$conteudo .="<a href='javascript:AbrePeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$posto\",\"$pais\",\"$marca\",\"$tipo_data\",\"$aux_data_inicial\",\"$aux_data_final\",\"\");'>";
			}
			$conteudo .="$referencia</TD>";
			$conteudo .="<TD align='left' nowrap>$descricao</TD>";
			if($login_fabrica==3 /*OR $login_fabrica == 15*/) $conteudo .="<TD align='left' nowrap>$marca_nome</TD>";
			$conteudo .="<TD align='left' nowrap>$linha_nome</TD>";
			$conteudo .="<TD align='left' nowrap>$familia_nome</TD>";
			$conteudo .="<TD align='center' nowrap>$ocorrencia</TD>";
			$conteudo .="<TD align='right' nowrap title=''>$porcentagem</TD>";
			$conteudo .="<TD align='center' nowrap>$qtde_pecas</TD>";
			$conteudo .="<TD align='center' nowrap>$mao_de_obra</TD>";
			$conteudo .="</TR>";

			echo $conteudo;

			if ($formato_arquivo=='CSV'){
				$conteudo = "";
				$conteudo .= $referencia.";".$descricao.";".$linha_nome.";".$familia_nome.";".$ocorrencia.";".$porcentagem.";".$qtde_pecas.";".$mao_de_obra.";\n";
			}
			fputs ($fp,$conteudo);
		}
		$conteudo = "";
		$total       = number_format($total,0,",",".");
		$total_mo    = number_format($total_mo,2,",",".");
		$total_pecas = number_format($total_pecas,2,",",".");
		$conteudo .="</tbody>";

		$conteudo .= "<tr class='table_line'><td colspan='";if($login_fabrica==3 OR $login_fabrica==5) $conteudo .= "5";else $conteudo .= "4";$conteudo .="><font size='2'><b><CENTER>";
		if ($login_fabrica == 50 and strlen($lista_produtos) >0) { // HD 74309
			$conteudo .="<a href='javascript:AbrePeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$posto\",\"$pais\",\"$marca\",\"$tipo_data\",\"$aux_data_inicial\",\"$aux_data_final\",\"$lista_produtos\");'>";
		}
		$conteudo .="TOTAL</b></td>";
		$conteudo .="<td colspan='2'><font size='2' color='009900'><b>$total</b></td>";
		$conteudo .="<td><font size='2' color='009900'><b>$total_peca</b></td>";
		$conteudo .="<td><font size='2' color='009900'><b>$total_mo</b></td>";
		$conteudo .="</tr>";
		$conteudo .=" </TABLE></div>";

		echo $conteudo;
		if ($formato_arquivo == 'CSV'){
			$conteudo = "";
			$conteudo .= "total: ;".$total.";".$total_peca.";".$total_mo.";\n";
		}
		fputs ($fp,$conteudo);

		if ($formato_arquivo!='CSV'){
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
		}
		echo ` cp $arquivo_completo_tmp $path `;

		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		echo "<br>";

	}else{
		echo "<br>";

		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";
	}

}

flush();

?>

<p>

<? include "../rodape.php" ?>
