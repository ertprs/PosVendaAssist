<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim($_POST["btn_acao"]);

if ($btn_acao == "apagar"){
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_mensalidade
			WHERE tbl_mensalidade.mes     = '$mes'
			AND   tbl_mensalidade.ano     = '$ano'
			AND   tbl_mensalidade.fabrica = $fabrica;";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$fabrica			= $_POST["fabrica"];
		$mes				= $_POST["mes"];
		$ano				= $_POST["ano"];
		$qtde_pedido		= $_POST["qtde_pedido"];
		$ultimo_pedido		= $_POST["ultimo_pedido"];
		$qtde_os			= $_POST["qtde_os"];
		$ultima_os			= $_POST["ultima_os"];
		$qtde_callcenter	= $_POST["qtde_callcenter"];
		$ultimo_callcenter	= $_POST["ultimo_callcenter"];
		$valor				= $_POST["valor"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}//FIM APAGAR

if ($btn_acao == "gravar") {
	
	if (strlen($_POST["valor"]) > 0) {
		$aux_valor =  trim($_POST["valor"]) ;
		$aux_valor = fnc_limpa_moeda($aux_valor);
	}else{
		$msg_erro = "Digite o valor.";
	}

	/*	
	if (strlen($_POST["valor"]) > 0) {
		$aux_valor =  trim($_POST["valor"]) ;
		$sql = "SELECT fnc_limpa_moeda('$aux_valor')";
		$res = pg_exec ($con,$sql);
		$aux_valor = "'". pg_result ($res,0,0) ."'";
	}else{
		$msg_erro = "Digite o valor.";
	}*/

	if (strlen($_POST["ultimo_callcenter"]) > 0) {
		$aux_ultimo_callcenter = "'". trim($_POST["ultimo_callcenter"]) ."'";
	}else{
		$msg_erro = "Digite o último call-center.";
	}

	if (strlen($_POST["qtde_callcenter"]) > 0) {
		$aux_qtde_callcenter = "'". trim($_POST["qtde_callcenter"]) ."'";
	}else{
		$msg_erro = "Digite a quantidade de call-center.";
	}

	if (strlen($_POST["ultima_os"]) > 0) {
		$aux_ultima_os = "'". trim($_POST["ultima_os"]) ."'";
	}else{
		$msg_erro = "Digite a última OS.";
	}

	if (strlen($_POST["qtde_os"]) > 0) {
		$aux_qtde_os = "'". trim($_POST["qtde_os"]) ."'";
	}else{
		$msg_erro = "Digite a quantidade de OS.";
	}

	if (strlen($_POST["ultimo_pedido"]) > 0) {
		$aux_ultimo_pedido = "'". trim($_POST["ultimo_pedido"]) ."'";
	}else{
		$msg_erro = "Digite o último pedido.";
	}

	if (strlen($_POST["qtde_pedido"]) > 0) {
		$aux_qtde_pedido = "'". trim($_POST["qtde_pedido"]) ."'";
	}else{
		$msg_erro = "Digite a quantidade de pedido.";
	}

	if (strlen($_POST["ano"]) > 0) {
		$aux_ano = "'". trim($_POST["ano"]) ."'";
	}else{
		$msg_erro = "Selecione um ano.";
	}
	
	if (strlen($_POST["mes"]) > 0) {
		$aux_mes = "'". trim($_POST["mes"]) ."'";
	}else{
		$msg_erro = "Selecione um mês.";
	}

	if (strlen($_POST["fabrica"]) > 0) {
		$aux_fabrica = "'". trim($_POST["fabrica"]) ."'";
	}else{
		$msg_erro = "Selecione uma fábrica.";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "SELECT *
				FROM tbl_mensalidade
				WHERE fabrica = $aux_fabrica
				AND   mes     = $aux_mes
				AND   ano     = $aux_ano";
		$res = @pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 0) {
			#----------------------------------INSERE NOVO REGISTRO------------------------------#
			$sql = "INSERT INTO tbl_mensalidade (
						fabrica               ,
						mes                   ,
						ano                   ,
						ultimo_pedido         ,
						ultima_os             ,
						qtde_pedido           ,
						qtde_os               ,
						qtde_callcenter       ,
						ultimo_callcenter     , 
						valor         
					) VALUES (
						$aux_fabrica          ,
						$aux_mes              ,
						$aux_ano              ,
						$aux_ultimo_pedido    ,
						$aux_ultima_os        ,
						$aux_qtde_pedido      ,
						$aux_qtde_os          ,
						$aux_qtde_callcenter  ,
						$aux_ultimo_callcenter,
						$aux_valor        
					);";
		}else{
			#-----------------------------------ALTERA REGISTRO----------------------------------#
			$sql = "UPDATE tbl_mensalidade SET
							ultimo_pedido	  = $aux_ultimo_pedido    ,
							ultima_os		  = $aux_ultima_os        ,
							qtde_pedido		  = $aux_qtde_pedido      ,
							qtde_os           = $aux_qtde_os          ,
							qtde_callcenter   = $aux_qtde_callcenter  ,
							ultimo_callcenter = $aux_ultimo_callcenter,
							valor             = $aux_valor        
					WHERE   mes               = $aux_mes
					AND     ano               = $aux_ano
					AND     fabrica           = $aux_fabrica";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen ($msg_erro) == 0) {
			#--------------CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE-----------#
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			
			header ("Location: $PHP_SELF");
			exit;
		}else{
			#--------ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS----------#
			$fabrica           = $_POST["fabrica"];
			$mes               = $_POST["mes"];
			$ano               = $_POST["ano"];
			$ultimo_pedido     = $_POST["ultimo_pedido"];
			$ultima_os         = $_POST["ultima_os"];
			$qtde_pedido       = $_POST["qtde_pedido"];
			$qtde_os           = $_POST["qtde_os"];
			$qtde_callcenter   = $_POST["qtde_callcenter"];
			$ultimo_callcenter = $_POST["ultimo_callcenter"];
			$valor             = $_POST["valor"];

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg erro
}

#------------------------------------------CARREGA REGISTRO--------------------------------------#
if(strlen($_GET['mes']) > 0 and strlen($_GET['ano']) > 0 and strlen($_GET['fabrica']) > 0){
	$sql = "SELECT	mes              ,
					ano              ,
					ultimo_pedido    ,
					ultima_os        ,
					qtde_pedido      ,
					qtde_os          ,
					qtde_callcenter  ,
					ultimo_callcenter,
					valor         
			FROM    tbl_mensalidade
			WHERE   fabrica = $fabrica
			AND     ano     = '$ano'
			AND     mes     = '$mes'";
	$res = pg_exec ($con,$sql);
//echo $sql."<BR> | ".pg_numrows($res)." |";
	if (pg_numrows($res) > 0) {
		$mes               = trim(pg_result($res,0,mes));
		$ano               = trim(pg_result($res,0,ano));
		$ultimo_pedido     = trim(pg_result($res,0,ultimo_pedido));
		$ultima_os         = trim(pg_result($res,0,ultima_os));
		$qtde_pedido       = trim(pg_result($res,0,qtde_pedido));
		$qtde_os           = trim(pg_result($res,0,qtde_os));
		$qtde_callcenter   = trim(pg_result($res,0,qtde_callcenter));
		$ultimo_callcenter = trim(pg_result($res,0,ultimo_callcenter));
		$valor             = trim(pg_result($res,0,valor));
		$valor             = number_format($valor,2,',','.');
	}
}
//echo 	$mes ."<br>". $ano ."<br>". $ultimo_pedido ."<br>". $ultima_os ."<br>". $qtde_pedido ."<br>". $qtde_os ."<br>". $valor;

$layout_menu = "cadastro";
$title = "Cadastro de Mensalidades";
include 'cabecalho.php';
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}
</style>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?> 

<p>

<form name="frm_mensalidade" method="post" action="<? echo $PHP_SELF; ?>">
<table ><tr><td><a href='mensalidade_consulta.php'>Consulta Mensalidade</a></td></tr></table>
<!-- ======= FABRICA ========== -->
<table width='400px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td colspan="2">
		FÁBRICA
	</td>
</tr>
<tr class="table_line">
	<td>
		<center>
		<!-- começa aqui -->
		<?
		$sql = "SELECT   fabrica, nome
				FROM    tbl_fabrica
				ORDER BY nome";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 130px;' name='fabrica'>\n";
			echo "<option value=''>ESCOLHA</option>\n";
			
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_fabrica = trim(pg_result($res,$x,fabrica));
				$aux_nome  = trim(pg_result($res,$x,nome));
				
				echo "<option value='$aux_fabrica'"; 
				if ($fabrica == $aux_fabrica) echo " SELECTED "; 
				echo ">$aux_nome</option>\n";
			}
			echo "</select>\n";
		}
		?>
	
		</center>
	</td>
</tr>
</table>


<!-- ======= MÊS E ANO ========== -->

<table width='400px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td width = '50%'>
		MÊS
	</td>
	<td width = '50%'>
		ANO
	</td>
</tr>
<tr class="table_line">
	<td width = '50%'>
		<CENTER>
		<select name="mes" class="frm" style="width: 110px;">
			<option value="">ESCOLHA</option>
			<option value="01" <? if ($mes == "01") echo " SELECTED "; ?>>Janeiro</option>
			<option value="02" <? if ($mes == "02") echo " SELECTED "; ?>>Fevereiro</option>
			<option value="03" <? if ($mes == "03") echo " SELECTED "; ?>>Março</option>
			<option value="04" <? if ($mes == "04") echo " SELECTED "; ?>>Abril</option>
			<option value="05" <? if ($mes == "05") echo " SELECTED "; ?>>Maio</option>
			<option value="06" <? if ($mes == "06") echo " SELECTED "; ?>>Junho</option>
			<option value="07" <? if ($mes == "07") echo " SELECTED "; ?>>Julho</option>
			<option value="08" <? if ($mes == "08") echo " SELECTED "; ?>>Agosto</option>
			<option value="09" <? if ($mes == "09") echo " SELECTED "; ?>>Setembro</option>
			<option value="10" <? if ($mes == "10") echo " SELECTED "; ?>>Outubro</option>
			<option value="11" <? if ($mes == "11") echo " SELECTED "; ?>>Novembro</option>
			<option value="12" <? if ($mes == "12") echo " SELECTED "; ?>>Dezembro</option>
		</select>
		</CENTER>
	</td>
	<td width = '50%'>
		<CENTER>
		<select name="ano" class="frm" style="width: 110px;">
			<option value="">ESCOLHA</option>
			<option value="2004" <? if ($ano == "2004") echo " SELECTED "; ?>>2004</option>
			<option value="2005" <? if ($ano == "2005") echo " SELECTED "; ?>>2005</option>
			<option value="2006" <? if ($ano == "2006") echo " SELECTED "; ?>>2006</option>
			<option value="2007" <? if ($ano == "2007") echo " SELECTED "; ?>>2007</option>
			<option value="2008" <? if ($ano == "2008") echo " SELECTED "; ?>>2008</option>
			<option value="2009" <? if ($ano == "2009") echo " SELECTED "; ?>>2009</option>
			<option value="2010" <? if ($ano == "2010") echo " SELECTED "; ?>>2010</option>
		</select>
		</CENTER>
	</td>
</tr>
</table>

<!-- ======= ULTIMO PEDIDO E OS ========== -->

<table width='400px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td width = '50%'>
		QUANTIDADE PEDIDO
	</td>
	<td width = '50%'>
		ÚLTIMO PEDIDO
	</td>
</tr>
<tr class="table_line">
	<td width = '50%'>
		<CENTER>
		<INPUT TYPE="text" NAME="qtde_pedido"  size="30" maxlength="16" value="<? echo $qtde_pedido ?>" class="frm">
		</CENTER>
	</td>
	<td width = '50%'>
		<CENTER>
		<INPUT TYPE="text" NAME="ultimo_pedido"  size="30" maxlength="16" value="<? echo $ultimo_pedido ?>" class="frm">
		</CENTER>
	</td>
	
</tr>
</table>

<!-- ======= QTDE PEDIDO E OS ========== -->

<table width='400px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td width = '50%'>
		QUANTIDADE OS
	</td>
	<td width = '50%'>
		ÚLTIMA OS
	</td>
</tr>
<tr class="table_line">
	<td width = '50%'>
		<CENTER><INPUT TYPE="text" NAME="qtde_os"  size="30" maxlength="16" value="<? echo $qtde_os ?>" class="frm"></CENTER>
	</td>
	<td width = '50%'>
		<CENTER><INPUT TYPE="text" NAME="ultima_os"  size="30" maxlength="16" value="<? echo $ultima_os ?>" class="frm"></CENTER>
	</td>
</tr>
</table>

<!-- ======= QTDE CALLCENTER E ULTIMO CALLCENTER ========== -->

<table width='400px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td width = '50%'>
		QUANTIDADE CALLCENTER
	</td>
	<td width = '50%'>
		ÚLTIMO CALLCENTER
	</td>
</tr>
<tr class="table_line">
	<td width = '50%'>
		<CENTER><INPUT TYPE="text" NAME="qtde_callcenter"  size="30" maxlength="16" value="<? echo $qtde_callcenter?>" class="frm"></CENTER>
	</td>
	<td width = '50%'>
		<CENTER><INPUT TYPE="text" NAME="ultimo_callcenter"  size="30" maxlength="16" value="<? echo $ultimo_callcenter ?>" class="frm"></CENTER>
	</td>
</tr>
</table>


<!-- ======= VALOR ========== -->

<table width='400px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td width = '50%'>
		VALOR
	</td>
</tr>
<tr class="table_line">
	<td width = '50%'>
		<CENTER>
		<INPUT TYPE="text" NAME="valor"  size="30" maxlength="16" value="<? echo $valor ?>" class="frm">
		</CENTER>
	</td>
</tr>
</table>

<br>

<center>
<!-- ============================ Botoes de Acao ========================= -->
<input type='hidden' name='btn_acao' value=''>

<img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_mensalidade.btn_acao.value == '' ) { document.frm_mensalidade.btn_acao.value='gravar' ; document.frm_mensalidade.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<img src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_mensalidade.btn_acao.value == '' ) { document.frm_mensalidade.btn_acao.value='apagar' ; document.frm_mensalidade.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar" border='0' style="cursor:pointer;">
<img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_mensalidade.btn_acao.value == '' ) { document.frm_mensalidade.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">

</center>



</form>

<?
include("rodape.php");
?>

