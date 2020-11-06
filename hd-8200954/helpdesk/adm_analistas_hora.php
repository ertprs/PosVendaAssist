<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

echo "<html>";
echo "<head>";
echo "</head>";
echo "<body>";
echo "<style>body{margin:0px;padding:0px;}</style>";


//--== TABELA DE ATENDENTES =========================================--\\

$sql = "SELECT DISTINCT tbl_admin.nome_completo         ,
						tbl_hd_chamado.atendente
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin=tbl_hd_chamado.atendente
			WHERE   tbl_admin.fabrica = 10
			AND tbl_admin.responsabilidade IN ('Analista de Help-Desk', 'Programador')
			AND tbl_admin.ativo IS TRUE
			ORDER BY tbl_admin.nome_completo ASC
";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	echo "<table width = '100%' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
	echo "<tr>";
	echo"<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'><strong>PREVISÃO DE TÉRMINO </strong></td>";

	echo"</tr>";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$pt = trim(pg_result($res,$x,atendente));
		$pt_nome_completo  = trim(pg_result($res,$x,nome_completo));

		echo "<tr>";
		echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='left' ><a href='adm_chamado_lista.php?atendente_busca=$pt' target='_blank'>$pt_nome_completo</a></td>";

		/* Total de horas e tarefas */
		$sql2=" SELECT  SUM(prazo_horas) as total         ,
						count(hd_chamado) as total_tarefas
				FROM tbl_hd_chamado
				JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
				WHERE atendente = $pt
				and tbl_hd_chamado.fabrica_responsavel = 10
				AND STATUS    NOT IN('Resolvido','Cancelado','Aprovação')
				AND resolvido IS NULL";

		$res2 = pg_exec ($con,$sql2);
		$pt_total        = trim(pg_result($res2,0,total));
		$pt_total_tarefa = trim(pg_result($res2,0,total_tarefas));

		/* Soma das Horas já trabalhadas */
		$sql2=" SELECT SUM(CASE WHEN data_termino IS NULL THEN CURRENT_TIMESTAMP ELSE data_termino END - data_inicio )
				FROM tbl_hd_chamado
				JOIN tbl_admin                 ON tbl_admin.admin                = tbl_hd_chamado.atendente
				JOIN tbl_hd_chamado_atendente  ON tbl_hd_chamado_atendente.hd_chamado = tbl_hd_chamado.hd_chamado
				WHERE tbl_hd_chamado.atendente         = $pt
				AND tbl_hd_chamado.fabrica_responsavel = 10
				AND tbl_hd_chamado.status NOT IN('Resolvido','Cancelado','Aprovação')
				AND   responsabilidade in ('Analista de Help-Desk','Programador')
				AND resolvido IS NULL";
		$res2 = pg_exec ($con,$sql2);
		$horas_trabalhadas = trim(pg_result($res2,0,0));
		if (strlen($horas_trabalhadas)>0){
			$horas_trabalhadas = explode(":",$horas_trabalhadas);
			$horas_trabalhadas = $horas_trabalhadas[0].".".$horas_trabalhadas[1];
		}

		$horas_restantes = $pt_total - $horas_trabalhadas;

		if($pt_total_tarefa==0)
			$frase = "$pt_nome_completo não tem nenhuma tarefa";
		else
			$frase = "$pt_nome_completo tem $pt_total_tarefa tarefa(s) pendente(s), com total de $pt_total horas, mas já trabalhou $horas_trabalhadas horas e ficará disponível em $horas_restantes horas";

		if($pt_total==''){
			$pt_total = "<font size='2'color='#006600'><b>LIVRE</b></font>";
		}

		echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='right'><acronym title='$frase'>$horas_restantes</acronym></td>";

		echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='right'	><acronym title='$frase'> $pt_total_tarefa </acronym></td>";
	}
	echo "</tr>";
	echo "</table>";

}
echo "</td></tr></table>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
echo "</center>";
?>