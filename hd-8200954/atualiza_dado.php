<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "login_unico_autentica_usuario.php";

//  Funções para facilitar a minha vida e fazer mais simples o código...
function iif($condition, $val_true, $val_false) {
	return ($condition) ? $val_true : $val_false;
}

function is_in($valor, $valores, $sep = ",")
{	// BEGIN function is_in
	// Devolve 'true' se o $valor é um dos $valores, 'false' se não está, 'null' se
	// qualquer um dos parámetros for "" ou se não houver $sep em $valores
	if (($valor=="") or ($valores=="") or (substr_count($valores, $sep)<1)) {
		return NULL;
	}
	$a_valores = iif((is_array($valores)),$valores,explode($sep,$valores));
    foreach ($a_valores as $valor_i) {
		$is_in = ($valor==$valor_i);
		if ($is_in) break;
	}
	return $is_in;
} // END function is_in

?>
<HTML>
<HEAD>
<TITLE>Cadastre seu aniversario</TITLE>
<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	color: #fff;
	background-color: #596D9B;
}
.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color: #596d9b;
	background-color: #fff;
}

.erro {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: normal;
	color: red;
	background-color: white;
	text-align: center;
}
</style>

<script src="javascripts/atualiza_dado.js" type="text/javascript"></script>

</HEAD>
<BODY onLoad='javascript:dias_mes();'>
<?
if(strlen($_POST['btn_acao'])> 0) {
	$dia_nascimento = trim($_POST['dia_nascimento']);
	$mes_nascimento = trim($_POST['mes_nascimento']);
	if(    strlen($dia_nascimento) == 0
		or strlen($mes_nascimento) == 0) {
		$msg_erro = "Por favor, preencha sua data de aniversário antes de gravar";
	}
	if(($dia_nascimento > 29 and $mes_nascimento == 2) or
	   ($dia_nascimento > 30 and is_in($mes_nascimento,"4,6,9,11"))) {
		$msg_erro = "Dia do nascimento inválido!";
	}
	if(strlen($msg_erro) == 0) {
		$sql = "UPDATE tbl_login_unico SET
				dia_nascimento	  = '$dia_nascimento',
				mes_nascimento	  = '$mes_nascimento'
				WHERE login_unico = $login_unico";
		$res= pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro) == 0) { ?>
		<BR><BR><BR>
		<TABLE class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>
			<CAPTION class='titulo'>Dados atualizados com sucesso!</CAPTION>
		</TABLE>
		<SCRIPT language='JavaScript'>
			setTimeout("window.close();",5000);
		</SCRIPT>
	</BODY>
</HTML>
<?			exit;
		}
	}
}

if(strlen($msg_erro) > 0){
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<H2 class='erro'>".$msg_erro."</H2>\n<BR>\n";
}
?>
	<FORM name='frm_data' method='post' action='<?=$PHP_SELF?>'>
	<TABLE class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>
		<CAPTION class='titulo'>Por favor, preencha sua data de nascimento</CAPTION>
		<TR class='menu_top'>
			<TD nowrap width='60%'>&nbsp;</TD>
			<TD nowrap>DIA</TD>
			<TD nowrap>MÊS</TD>
		</TR>
<?
	$sql = "SELECT nome,dia_nascimento,mes_nascimento
			FROM tbl_login_unico
			WHERE login_unico = $login_unico";
	$res = pg_query ($con,$sql);
	if (pg_numrows($res) > 0 and strlen($_POST['btn_acao']) == 0) {
		$nome			=	trim(pg_result ($res,0,nome));
		$dia_nascimento =	trim(pg_result ($res,0,dia_nascimento));
		$mes_nascimento =	trim(pg_result ($res,0,mes_nascimento));
//	echo "$login_unico, dia $dia_nascimento, mes $mes_nascimento";
	}
	echo "<TR class='table_line'>\n";
	echo "<INPUT type='hidden' name='login_unico' value='$login_unico'>\n";
	echo "<TD nowrap align='right'>$nome</td>\n";
	echo "<TD nowrap>\n\t<SELECT name='dia_nascimento'>\n";

    for ($i = 1;$i <= 31; $i++) {  // escreve os dias do mês...
		echo "\t\t<OPTION value='$i'".iif(($dia_nascimento == $i)," SELECTED","").">$i</OPTION>\n";
    }
	echo "\t</SELECT>\n\t</TD>\n";
	echo "<TD nowrap>\n\tde&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name='mes_nascimento' onChange='javascript:dias_mes();'>\n";
	
   $meses = array(1 => "Janeiro", "Fevereiro", "Mar&ccedil;o",
                  "Abril", "Maio", "Junho",
                  "Julho","Agosto", "Setembro",
                  "Outubro", "Novembro", "Dezembro");
    for ($i = 1;$i <= 12; $i++) {   // Escreve os meses do ano...
		echo "\t\t<OPTION value='$i'".iif(($mes_nascimento == $i)," SELECTED","").">".$meses[$i]."</OPTION>\n";
    }
	?>
			</SELECT>
		</TD>
	</TR>
	<TR> <!-- Rodapé -->
		<TD colspan=9 align='center'>
			<INPUT type='hidden' name='btn_acao' value=''>
			<IMG src='imagens/btn_gravar.gif' style='cursor: pointer;'
			 onclick="javascript: Enviar('gravar2',document.frm_data);"
				 alt='Gravar Formul&aacute;rio' border='0'>
		</TD>
	</TR>
</TABLE>
</FORM>
</BODY>
</HTML>