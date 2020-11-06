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
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	background-color: #B2BFD9;
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
.atrasado {
	background-color: #FC7878;
}
.execucao {
	background-color: #AEFF88;
}
.resolvido {
	background-color: #3E71F9;
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
	<table align='center' border='0'>
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
		$sql  = "SELECT admin, login
				FROM tbl_admin
				WHERE fabrica=10
				AND (responsabilidade = 'Analista de Help-Desk' OR responsabilidade='Programador')
				AND ativo IS TRUE
				AND participa_agenda = 't'
				ORDER BY login
				";
		$res   = pg_exec($con,$sql);
		$linha = pg_numrows($res);

		$primeira_vez = 1;
		$sqlx = "SELECT horario, descricao, agenda, data,substring(horario, 1, 5)as hora1, substring(horario, 8, 12) as hora2
				FROM tbl_agenda
				WHERE data between '$data_inicial  00:00:00' AND '$data_final 23:59:59'
				AND fabrica = $login_fabrica;";
		$resx   = pg_exec($con,$sqlx);

		if (pg_numrows($res) >0) {
			for ($x = 0; $x < pg_numrows($res) ; $x++) {
				$descricao_agenda = pg_result($resx,0,descricao);
				$data             = pg_result($resx,0,data);
				if($primeira_vez == 1 or $descricao_agenda_ant != $descricao_agenda){
					echo "<table class='TabelaPadrao' align='center' border='0'>";
					echo "<tr height='20'>";
					$datax = implode(preg_match("~\/~", $data) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $data) == 0 ? "-" : "/", $data)));
					echo "<td align='center' class='Titulo_Tabela' nowrap><b>$descricao_agenda</b>($data)</td>";
					$descricao_agenda_ant = $descricao_agenda;
					for ($y = 0; $y < pg_numrows($resx) ; $y++) {
						$horario_agenda   = pg_result($resx,$y,horario);
						$descricao_agenda = pg_result($resx,$y,descricao);
						$agenda           = pg_result($resx,$y,agenda);
						$data             = pg_result($resx,$y,data);

						echo "<td align='center' class='Titulo_Tabela'><b>$horario_agenda</b></td>";
						echo "<input  type='hidden' name='data$x' value='$data' >";
						echo "<input  type='hidden' name='horario_agenda$x' value='$horario_agenda' >";
						echo "<input  type='hidden' name='descricao_agenda$x' value='$descricao_agenda' >";
						echo "<input  type='hidden' name='agenda$x' value='$agenda' >";
						echo "</td>";						
					}
					echo "</tr>";
					$primeira_vez = 2;
				}

				$admin         = pg_result($res,$x,admin);
				$nome_completo = pg_result($res,$x,login);
				echo "<tr height='20'>";
				echo "<input  type='hidden' name='admin_$x$y' value='$admin' nowrap>";
				echo "<td align='center'><b>&nbsp;$nome_completo - Agenda&nbsp;</b></td>";
				$x_horario = substr ($horario_agenda,0,5);

				for ($y = 0; $y < pg_numrows($resx) ; $y++) {
					$horario_agenda   = pg_result($resx,$y,horario);
					$descricao_agenda = pg_result($resx,$y,descricao);
					$agenda           = pg_result($resx,$y,agenda);
					$data             = pg_result($resx,$y,data);
					$imprimir = "";					
					//$imprimir .= "<select size='1' name='hd_chamado_$x$k' >";
					$sqlf="SELECT hd_chamado
							FROM tbl_hd_chamado_agenda
							WHERE agenda = $agenda
							AND   admin  = $admin";
					$resf=pg_exec($con,$sqlf) ;
					$class = 'Titulo_Tabela';
					if(pg_numrows($resf) >0){
						$hd_chamado = pg_result($resf,0,hd_chamado);

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
						

						//$imprimir .= "<option value='$hd_chamado' selected>$hd_chamado</option>\n";
						$imprimir .= "$hd_chamado\n";
					}else{
						//$imprimir .= "<option value ='' selected></option>\n";
						$imprimir .= "\n";
					}
					/*
					$sqla="SELECT hd_chamado
							FROM tbl_hd_chamado
							WHERE atendente = $admin
							AND tbl_hd_chamado.fabrica_responsavel = 10
							AND status NOT IN ('Resolvido','Cancelado') ";
					$resa=pg_exec($con,$sqla);
					if(pg_numrows($resa) >0){
						for($a=0; $a<pg_numrows($resa); $a++) {
							$hd_chamado = pg_result($resa,$a,hd_chamado);
							$imprimir .= "<option value='$hd_chamado'>$hd_chamado</option>\n";
						}
					}
					$imprimir .= "</select>";*/

					echo "<td align='center' width= '300' class='$class'>";
					echo "&nbsp; $imprimir &nbsp;";
					echo "</td>";
				}
				echo "</tr>";	

				echo "<tr height='20'>";
				echo "<input  type='hidden' name='admin_$x$y' value='$admin' nowrap>";
				echo "<td align='center' colspan='1'>Historico <br>Atendimento</td>";
				$x_horario = substr ($horario_agenda,0,5);

				for ($y = 0; $y < pg_numrows($resx) ; $y++) {
					$horario_agenda   = pg_result($resx,$y,horario);
					$hora1 = pg_result($resx,$y,hora1);
					$hora2 = pg_result($resx,$y,hora2);
					$descricao_agenda = pg_result($resx,$y,descricao);
					$agenda           = pg_result($resx,$y,agenda);
					$data             = pg_result($resx,$y,data);

					$imprimir = "";					
					$imprimir .= "";
					$sqlf="SELECT hd_chamado
							FROM tbl_hd_chamado_agenda
							WHERE agenda = $agenda
							AND   admin  = $admin";
					$resf=pg_exec($con,$sqlf) ;
					$class = 'Titulo_Tabela';
					if(pg_numrows($resf) >0){
						$hd_chamado = pg_result($resf,0,hd_chamado);

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
						
						//$imprimir .= "$hd_chamado\n";
					}else{
						$imprimir .= "\n";
					}
					$sqla="SELECT  TO_CHAR(data_inicio,' hh24:mi')            AS hora_inicio ,
					tbl_hd_chamado.hd_chamado                                 
							FROM tbl_hd_chamado_atendente
							JOIN tbl_admin using(admin)
							JOIN tbl_hd_chamado using(hd_chamado)
							JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
							WHERE data_inicio BETWEEN  (current_date||' '||'$hora1'||':00')::timestamp AND (current_date||' '||'$hora2'||':00')::timestamp
							and tbl_hd_chamado_atendente.admin = $admin
							ORDER BY hora_inicio				";
							//echo "sql: $sqla";
					$resa=pg_exec($con,$sqla);
					if(pg_numrows($resa) >0){
						for($a=0; $a<pg_numrows($resa); $a++) {
							$hd_chamado = pg_result($resa,$a,hd_chamado);
							$hora_inicio= pg_result($resa,$a,hora_inicio);
							$imprimir .= "<font color ='red'>$hd_chamado-$hora_inicio</font><br>\n";
						}
					}
					$imprimir .= "";
					echo "<td align='left' class='$class' nowrap>";
					echo "$imprimir";
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

