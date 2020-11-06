<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

$faturamento = $_POST["faturamento"];
if(strlen($faturamento) == 0) $faturamento = $_GET["faturamento"];

$qtde = $_POST["qtde"];
if(strlen($qtde) == 0) $qtde = $_GET["qtde"];

$btnG = $_POST["btnG"];
if(strlen($btnG) == 0) $btnG = $_GET["btnG"];

$numero_serie = trim($_POST["numero_serie_$i"]);
if(strlen($numero_serie) == 0) $numero_serie = $_GET["numero_serie_$i"];

if(strlen($btnG) > 0){
	$qtde = $_POST["qtde"];

	for ($i = 0; $i < $qtde; $i ++){
		$sql = "select
			numero_serie ,
			peca
			from tbl_peca_item_numero_serie 
			where numero_serie = $numero_serie and peca=$peca";
		$res = pg_exec($con, $sql);

		if(pg_numrows($res) > 0){
			for ($i = 0; $i < pg_numrows ($res); $i++) {
				$numero_serie = trim(pg_result($res,$i,numero_serie));
				$peca         = trim(pg_result($res,$i,peca));
				$msg_erro = "Numero de serie:"."<font color='#000000'>".$numero_serie."</font>"." da peça:"."<font color='#000000'>".$peca."</font>"." , já cadastrado.";
			}
		}else{
			$sql2 = "INSERT INTO tbl_peca_item_numero_serie
				(peca       ,
				faturamento ,
				numero_serie)
				values
				($peca      ,
				$faturamento,
				$numero_serie)";
			$res2 = pg_exec($con, $sql2);
			if(strlen(pg_errormessage($con)) > 0){
				$msg_erro = "Erro ao inserir os numero de serie:$erro - sql:".$sql;
			}else{
				$msg_erro = "Numero de serie gravado com sucesso!";
			}
		}
	}

	if(strlen($msg_erro) > 0){
		echo"<DIV align='center'><FONT COLOR='red' SIZE='3'><B>$msg_erro</B></FONT></DIV>";
	}
}

$ns_auto=$_POST['numero_serie'];
if(strlen($ns_auto) == 0) $ns_auto=$_GET['numero_serie'];

if(strlen($ns_auto) > 0) {
	$sql="SELECT fabrica,
					qtde
			FROM tbl_faturamento 
			JOIN tbl_faturamento_item USING(faturamento)
			WHERE tbl_faturamento.faturamento=$faturamento
			AND   peca=$peca";
	$res=pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);

	$fabrica=pg_result($res,0,fabrica);
	$qtde   =pg_result($res,0,qtde);
	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		for ($i = 0; $i < $qtde; $i++) {

			$sql="SELECT max(numero_serie+1) as ultimo_numero_serie 
					FROM tbl_loja_dados
					where empresa= $fabrica";
			$res=pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$ultimo_numero_serie=pg_result($res,0,ultimo_numero_serie);
			
			$sql="INSERT INTO tbl_peca_item_numero_serie
						(peca       ,
						faturamento ,
						numero_serie)
						values
						($peca      ,
						$faturamento,
						$ultimo_numero_serie)";
			$res = pg_exec($con, $sql);
			$msg_erro = pg_errormessage($con);

			$sql="UPDATE tbl_loja_dados set numero_serie=$ultimo_numero_serie 
					WHERE empresa=$fabrica";
			$res = pg_exec($con, $sql);
			$msg_erro = pg_errormessage($con);
		}
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			echo"<DIV align='center'><FONT COLOR='Blue' SIZE='3'><B>Gerado com sucesso</B></FONT></DIV>";
?>
			<script language='javascript'>
			window.reload(); 
			setTimeOut("Refresh()",5000); 
			</script>	
<?
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
		if(strlen($msg_erro) > 0){
			echo"<DIV align='center'><FONT COLOR='red' SIZE='3'><B>$msg_erro</B></FONT></DIV>";
		}
} ?>

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>

<script language='javascript'>
/****************************************************************
Função para simular um Tab quando for pressionado a tecla Enter
Ex: <INPUT TYPE="text" NAME="" onKeyDown="TABEnter()">
Funciona em TEXT BOX,RADIO BUTTON, CHECK BOX e menu DROP-DOWN
*****************************************************************/
function TABEnter(oEvent){
	var oEvent = (oEvent)? oEvent : event;
	var oTarget =(oEvent.target)? oEvent.target : oEvent.srcElement;

	if(oEvent.keyCode==13)
	oEvent.keyCode = 9;

	if(oTarget.type=="text" && oEvent.keyCode==13)
	//return false;

	oEvent.keyCode = 9;
	}

/************************************
 Função para validar campo em branco 
/************************************/
function ValidaSemPreenchimento(form){
	for (i=0;i<form.length;i++){
			var obg = form[i].obrigatorio;
			if (obg==1){
			if (form[i].value == ""){
			var nome = form[i].descricao
			alert("O campo " + (i+1) + " não foi digitado.")
			form[i].focus();
			return false
		}
	}
}
return true
}
</script>
<?

	$sql = "SELECT
		tbl_faturamento_item.faturamento ,
		qtde                             ,
		tbl_peca.peca                    ,
		tbl_peca.descricao               ,
		tbl_peca_item_numero_serie.numero_serie
	FROM tbl_faturamento_item
	JOIN tbl_peca USING(peca)
	JOIN tbl_peca_item_numero_serie USING(peca)
	WHERE tbl_faturamento_item.faturamento = $faturamento AND tbl_peca.peca = $peca
	ORDER BY faturamento,
			 peca       ,
			 numero_serie";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res) > 0){

		$msg_erro = "O faturamento:"."<font color='#000000'>".$faturamento."</font>"." da peça:"."<font color='#000000'>".$peca."</font>"." , já foi cadastrado.";

		if(strlen($msg_erro) > 0){
		echo"<DIV align='center'><FONT COLOR='red' SIZE='3'><B>$msg_erro</B></FONT></DIV>";
		}

		echo "<BR>";
		echo "<TABLE width='90%' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0' align='center'>";
			echo"<TR class='titulo'>";
				echo"<TD class='menu_top'><FONT COLOR='black'><B>Qtde</B></FONT></TD>";
				echo"<TD class='menu_top'><FONT COLOR='black'><B>Faturamento</B></FONT></TD>";
				echo"<TD class='menu_top'><FONT COLOR='black'><B>Cod. Peça</B></FONT></TD>";
				echo"<TD class='menu_top'><FONT COLOR='black'><B>Numero de Serie</B></FONT></TD>";
			echo"</TR>";

		for ($i = 0; $i < pg_numrows ($res); $i++) {
		$faturamento  = trim(pg_result($res,$i,faturamento));
		$numero_serie = trim(pg_result($res,$i,numero_serie));
		$peca         = trim(pg_result($res,$i,peca));
		$descricao    = trim(pg_result($res,$i,descricao));
		$qtde         = trim(pg_result($res,$i,qtde));

			echo"<TR>";
				echo"<TD align='center'>".($i+1)."</TD>";
				echo"<TD>$faturamento</TD>";
				echo"<TD>$peca</TD>";
				echo"<TD>$numero_serie</TD>";
			echo"</TR>";
		}
	echo"</TABLE>";
	}else{

	$sql = "select 
		faturamento   ,
		qtde          ,
		tbl_peca.peca ,
		tbl_peca.descricao
	from tbl_faturamento_item
	JOIN tbl_peca USING(peca)
	where faturamento = $faturamento AND tbl_peca.peca = $peca";
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)> 0){
		for ($i = 0; $i < pg_numrows ($res); $i++) {
			$faturamento = trim(pg_result($res,$i,faturamento));
			$peca        = trim(pg_result($res,$i,peca));
			$descricao   = trim(pg_result($res,$i,descricao));
			$qtde        = trim(pg_result($res,$i,qtde));
		}
	}

	echo "<form name='frm_notas' action='$PHP_SELF' method='POST' onSubmit='return ValidaSemPreenchimento(this)'>";
	echo "<TABLE width='90%' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0' align='center'>";
	echo "<a href=$PHP_SELF?peca=$peca&faturamento=$faturamento&numero_serie=automatico>Gerar número de série automático</a>";
		echo"<TR class='titulo'>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Qtde</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Faturamento</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Cod. Peça</B></FONT></TD>";
			echo"<TD class='menu_top'><FONT COLOR='black'><B>Numero de Serie</B></FONT></TD>";
		echo"</TR>";

	for ($i = 0; $i < $qtde; $i++) {
		echo"<TR>";
			echo"<TD align='center'>".($i+1)."</TD>";
			echo"<TD>$faturamento</TD>";
			echo"<TD>$peca</TD>";
			echo"<TD><input type='text' obrigatorio='1' name='numero_serie_$i' value='' size='15' onKeyDown='TABEnter()'></TD>";
		echo"</TR>";
	}
		echo"<TR class='titulo'>";
			echo"<TD colspan='4' align='center' class='menu_top'><input type='submit' name='btnG' value='Adicionar' ></TD>";
		echo"</TR>";
	echo"</TABLE>";

	//passando valor hidden
	echo "<input type='hidden' name='peca' value='$peca'>";
	echo "<input type='hidden' name='qtde' value='$qtde'>";
	echo "<input type='hidden' name='faturamento' value='$faturamento'>";
	echo "</form>";
	}

?>
