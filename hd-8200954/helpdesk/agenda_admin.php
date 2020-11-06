<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>
<style type="text/css">
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.TabelaPadrao{

	border:#484789 1px solid;
	background-color: #EAEEF7;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	border-collapse: collapse;
	border-left: 1px solid #8BA4EB;
	border-right: 1px solid #8BA4EB;
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	background-color: #B2BFD9;
	border-collapse: collapse;
	border-left: 1px solid #8BA4EB;
	border-right: 1px solid #8BA4EB;

}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	background-color: #CE0000;
	text-decoration: blink;
	text-align:center; 
}
.Ok{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	background-color: #66CC00;
	text-decoration: blink;
	text-align:center; 
}

.tab_linha{
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	text-align:left; 
}
.atrasado {
	background-color: #FC7878;
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	text-align:left; 
}
.execucao {
	background-color: #AEFF88;
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	text-align:left; 
}
.resolvido {
	background-color: #3E71F9;
	font-family: Verdana;
	font-size: 10px;
	font-weight: bold;
	text-align:left; 
}

</style>
<?php

$data_inicial   = trim($_GET['data_inicial']);
if (strlen($data_inicial)==0) $data_inicial = trim($_POST['data_inicial']);
$data_final   = trim($_GET['data_final']);
if (strlen($data_final)==0) $data_final = trim($_POST['data_final']);

$botao_gravar = trim($_GET["botao_gravar"]);
if (strlen($botao_gravar)==0){
	$botao_gravar = trim($_POST["botao_gravar"]);
}
if ($botao_gravar == "Gravar") {
	$data_inicial = implode(preg_match("~\/~", $data_inicial) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_inicial) == 0 ? "-" : "/", $data_inicial)));
	$data_final = implode(preg_match("~\/~", $data_final) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_final) == 0 ? "-" : "/", $data_final)));

	$sqlc = "SELECT admin, nome_completo
				FROM tbl_admin
				WHERE fabrica=10
				AND (responsabilidade = 'Analista de Help-Desk' OR responsabilidade='Programador')
				AND ativo IS TRUE
				AND participa_agenda = 't'
				AND admin=$login_admin
				ORDER BY login";
	$resc   = pg_exec($con,$sqlc);

	$sqla = "SELECT horario, descricao
				FROM tbl_agenda
				WHERE data between '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND   fabrica=$login_fabrica";
	$resa   = pg_exec($con,$sqla);

	if (pg_numrows($resa) >0) {
		for ($a = 0; $a < pg_numrows($resa) ; $a++) {
			for ($c = 0; $c < pg_numrows($resc) ; $c++) {
				$admin             = trim($_POST["admin$a$c"]);
				$admin             = pg_result($resc,$c,admin);
				$agenda            = trim($_POST["agenda$a"]);
				$descricao_agenda  = trim($_POST["descricao_agenda$a"]);
				$horario_agenda    = trim($_POST["horario_agenda$a"]);
				$data              = trim($_POST["data$a"]);
				$hd_chamado        = trim($_POST["hd_chamado_$a$c"]);

				//echo "admin $admin agenda $agenda hd_chamado $hd_chamado <br>";
				if(strlen($admin)>0 AND strlen($hd_chamado) >0 and strlen($agenda) >0){
					$sqlj = "SELECT hd_chamado
							FROM tbl_hd_chamado_agenda
							WHERE agenda = $agenda
							AND admin = $admin" ;
					$resj   = pg_exec($con,$sqlj);
					$linhaj = pg_numrows($resj);

					if($admin == $login_admin){
						if($linhaj == 0){
							$sql = "INSERT INTO tbl_hd_chamado_agenda 
							(hd_chamado, agenda, admin) VALUES ($hd_chamado, $agenda, $admin)";
						}else{
							$sql = "UPDATE tbl_hd_chamado_agenda
									SET hd_chamado = $hd_chamado
									WHERE agenda = $agenda 
										AND admin= $admin";
						}

						//echo $sql; 
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						if(strlen($msg_erro)==0) {
							$msg_ok = "Gravado com sucesso!";
						}else{
							$msg_erro = "Problema na gravação, consulte novamente a agenda!";
						}
					}
				}
			}
		}
	}
	$data_inicial = implode(preg_match("~\/~", $data_inicial) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_inicial) == 0 ? "-" : "/", $data_inicial)));
	$data_final = implode(preg_match("~\/~", $data_final) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_final) == 0 ? "-" : "/", $data_final)));
}
if(strlen($data_inicial)==0 AND strlen($data_final) ==0){
	$sqld="SELECT current_date";
	$resd=pg_exec($con,$sqld);
	$data_inicial = pg_result($resd,0,0);
	$data_inicial = implode(preg_match("~\/~", $data_inicial) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_inicial) == 0 ? "-" : "/", $data_inicial)));
	$data_final = pg_result($resd,0,0);
	$data_final = implode(preg_match("~\/~", $data_final) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_final) == 0 ? "-" : "/", $data_final)));
	$data_inicialx = $data_inicial;
	$data_finalx = $data_final;
}
$botao_consulta = "Consultar";

$TITULO='Agenda Help-Desk';
include 'menu.php';
include 'javascript_calendario.php';


?>
<script type="text/javascript" >
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<div class='Erro'>
	<? echo $msg_erro; ?>
</div>
<div class='Ok'>
	<? echo $msg_ok; ?>
</div>


<form name="frm_agenda" method="post" action="<? echo $PHP_SELF ?>">
	<table align='center' border='1'>
		<caption>Agenda - Help-Desk</caption>
		<tr align='center'>
			<td class='Label' colspan='1' align='center' nowrap>DATA INICIAL&nbsp;<input type='text' name='data_inicial' id='data_inicial' size='10' maxlength='10' value='<? echo $data_inicial ?>' ></td> 
			<td class='Label' colspan='1' align='center' nowrap>DATA FINAL&nbsp;<input type='text' name='data_final' id='data_final' size='10' maxlength='10' value='<?=$data_final?>' ></td> 
		</tr>
			<tr>
			<td class='Label' colspan='2' align='center'>&nbsp;
			</td>
		</tr>

		<tr>
			<td class='Label' colspan='2' align='center'>
				<input type="submit" name="botao_consulta" value="Consultar">
			</td>
		</tr>
	</table>
	
	<BR><BR>
	<center>
	<table cellpadding='0' cellspacing='0' border='0'><tr>
	<td bgcolor='#FC7878'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>&nbsp;atrasado&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
	<td bgcolor='#AEFF88'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>&nbsp;em execução&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<td bgcolor='#3E71F9'>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>&nbsp;resolvido&nbsp;&nbsp;&nbsp;&nbsp;</td>
	</tr></table>
	</center>
<?
if ($botao_consulta == "Consultar"){
	$data_inicial   = trim($_GET['data_inicial']);
	if (strlen($data_inicial)==0) $data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_GET['data_final']);
	if (strlen($data_final)==0) $data_final = trim($_POST['data_final']);	

	if (strlen($data_inicial)==0 OR strlen($data_final) ==0){
		$data_inicial = $data_inicialx;
		$data_final = $data_finalx;
	}else{
		$data_inicial = implode(preg_match("~\/~", $data_inicial) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_inicial) == 0 ? "-" : "/", $data_inicial)));
		$data_final = implode(preg_match("~\/~", $data_final) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data_final) == 0 ? "-" : "/", $data_final)));
	}

	if(strlen ($msg_erro) == 0){
		$sql  = "
				SELECT admin, login
				FROM tbl_admin
				WHERE fabrica=10
					AND (responsabilidade = 'Analista de Help-Desk' OR responsabilidade='Programador')
					AND ativo IS TRUE
					AND participa_agenda = 't'
					AND admin=$login_admin
				ORDER BY login";
		$res   = pg_exec($con,$sql);
		$linha = pg_numrows($res);

		$primeira_vez = 1;
		$sqlx = "SELECT horario, descricao, agenda, data
				FROM tbl_agenda
				WHERE data between '$data_inicial  00:00:00' AND '$data_final 23:59:59'
				AND fabrica = $login_fabrica;";
		$resx   = pg_exec($con,$sqlx);

		if (pg_numrows($resx) >0) {
			for ($x = 0; $x < pg_numrows($resx) ; $x++) {

				$horario_agenda   = pg_result($resx,$x,horario);
				$descricao_agenda = pg_result($resx,$x,descricao);
				$agenda           = pg_result($resx,$x,agenda);
				$data             = pg_result($resx,$x,data);
				if($primeira_vez == 1 or $descricao_agenda_ant != $descricao_agenda){
					echo "<table><tr><td><br></td></tr></table>";
					echo "<table class='TabelaPadrao' align='center' border='1'>";
					echo "<tr height='20'>";
					$datax = implode(preg_match("~\/~", $data) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data) == 0 ? "-" : "/", $data)));
					echo "<td align='center' class='Titulo_Tabela' nowrap><b>$descricao_agenda</b>($data)</td>";
					$descricao_agenda_ant = $descricao_agenda;
					for ($y = 0; $y < pg_numrows($res) ; $y++) {
						$admin         = pg_result($res,$y,admin);
						$nome_completo = pg_result($res,$y,login);
						echo "<input  type='hidden' name='admin_$x$y' value='$admin' nowrap>";
						echo "<td class ='Titulo_Tabela' align='center'><b>&nbsp;&nbsp;&nbsp;$nome_completo&nbsp;&nbsp;&nbsp;</b></td>";
					}
					echo "</tr>";
					$primeira_vez = 2;
				}
				echo "<tr height='20'>";
				echo "<td align='center' class='Titulo_Tabela'><b>$horario_agenda</b></td>";
				echo "<input  type='hidden' name='data$x' value='$data' >";
				echo "<input  type='hidden' name='horario_agenda$x' value='$horario_agenda' >";
				echo "<input  type='hidden' name='descricao_agenda$x' value='$descricao_agenda' >";
				echo "<input  type='hidden' name='agenda$x' value='$agenda' >";
				
				$x_horario = substr ($horario_agenda,0,5);

				for ($k = 0; $k < $linha ; $k++) {

					$imprimir = "";
					
					$imprimir .= "<select size='1' name='hd_chamado_$x$k' ";
						$sqldisable = "SELECT CURRENT_TIMESTAMP <= '$data $x_horario';";
						$resdisable = pg_exec($con,$sqldisable);
						$regdisable = pg_numrows($resdisable);
						$verdade    = pg_result($resdisable,0,0);

						if($verdade == 'f'){$imprimir .= "disabled";}
					$imprimir .= ">";
					$admin        = pg_result($res,$k,'admin');

					$sqlf="SELECT tbl_hd_chamado_agenda.hd_chamado, 
									titulo,  
									replace(rpad(substr(tbl_fabrica.nome, 1, 10),13, ' '), ' ', ' &nbsp; ') as nome,
									tbl_hd_chamado.prazo_horas,
									to_char(tbl_hd_chamado.previsao_termino, 'dd/mm/yyyy') as previsao_termino,
									tbl_hd_chamado.esta_agendado
							FROM tbl_hd_chamado_agenda
							LEFT JOIN tbl_hd_chamado using(hd_chamado)
							LEFT JOIN tbl_fabrica using(fabrica)
							WHERE tbl_hd_chamado_agenda.agenda = $agenda
								AND   tbl_hd_chamado_agenda.admin  = $admin";
					$resf=pg_exec($con,$sqlf) ;
					$esta_agendado = "";
					$class = 'tab_linha';
					if(pg_numrows($resf) >0){
						$hd_chamado = pg_result($resf,0,hd_chamado);
						$titulo     = pg_result($resf,0,titulo);
						$nome       = pg_result($resf,0,nome);
						$prazo_horas= pg_result($resf,0,prazo_horas);
						$esta_agendado= pg_result($resf,0,esta_agendado);

						$previsao_termino= pg_result($resf,0,previsao_termino);

						$sqlStatus = "SELECT status,
											 CASE WHEN previsao_termino < current_timestamp
												THEN 't'
												ELSE 'f'
											 END as atrasado
									  FROM tbl_hd_chamado 
									  WHERE hd_chamado = $hd_chamado";
						$resStatus = pg_exec($con,$sqlStatus);
						$status    = pg_result($resStatus,0,status);
						$atrasado = pg_result($resStatus,0,atrasado);
						
						if ($atrasado == 't') {
							$class = 'atrasado';
						} else {
							if ($status == 'Execução') {
								$class = 'execucao';
							} else {
								//$class = 'Titulo_Tabela';
							}
						}
						if ($status == 'Resolvido') $class = 'resolvido';
						
						if($esta_agendado == 't'){
							$imprimir = "$hd_chamado - $nome - $titulo ($prazo_horas h) $previsao_termino\n";
						}else{
							$imprimir .= "<option value='$hd_chamado' selected>$hd_chamado - $nome - $titulo ($prazo_horas h) $previsao_termino</option>\n";
						}
					}else{
						$imprimir .= "<option value ='' selected></option>\n";
					}

					if($esta_agendado == 't'){
						$imprimir = "$hd_chamado - $nome - $titulo ($prazo_horas h) $previsao_termino\n";
					}else{
						$sqla="SELECT hd_chamado, 
										titulo, 
										replace(rpad(substr(tbl_fabrica.nome, 1, 10),13, ' '), ' ', ' &nbsp; ') as nome,
										tbl_hd_chamado.prazo_horas,
										to_char(tbl_hd_chamado.previsao_termino, 'dd/mm/yyyy') as previsao_termino
								FROM tbl_hd_chamado
								JOIN tbl_fabrica using(fabrica)
								WHERE atendente = $admin
								AND tbl_hd_chamado.fabrica_responsavel = 10
								AND status NOT IN ('Resolvido','Cancelado')
								AND esta_agendado is not true
								ORDER BY TBL_FABRICA.NOME";
						$resa=pg_exec($con,$sqla);
						if(pg_numrows($resa) >0){

							for($a=0; $a<pg_numrows($resa); $a++) {
								$hd_chamado = pg_result($resa,$a,hd_chamado);
								$titulo     = pg_result($resa,$a,titulo);
								$nome       = pg_result($resa,$a,nome);
								$prazo_horas= pg_result($resa,$a,prazo_horas);
								$previsao_termino= pg_result($resa,$a,previsao_termino);

								$imprimir .= "<option value='$hd_chamado'>$hd_chamado - $nome - $titulo ($prazo_horas h) $previsao_termino </option>\n";
							}
						}
						$imprimir .= "</select>";
					}


					echo "<td align='center' class='$class'>";
					echo "&nbsp; $imprimir &nbsp;";
					echo "</td>";
				}
			echo "</tr>";	
			}
			?>
			</table>
			<table class='TabelaPadrao' align='center' border='0'>
				<tr>
					<td class='Label' colspan='1' align='center'>
						<input type="hidden" name="botao_gravar" value="">
						<img src="../imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_agenda.botao_gravar.value == '' ) { document.frm_agenda.botao_gravar.value='Gravar' ; document.frm_agenda.submit() } else { alert ('Aguarde submissão') }"  border='0'>
					</td>
					<td>
						<font color='red'><b>Não esqueça de gravar a agenda clicando no botão gravar!</b></font>
					</td>
				</tr>
			</table>
			<?
		}else{
			$msg_erro = "Não existe agenda cadastrada para este dia!";
		}
	}
}
?>
<div class='Erro'>
	<? echo $msg_erro; ?>
</div>
</form>
<?
include "rodape.php";
?>

