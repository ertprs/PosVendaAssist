<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($_GET['ajax']=='sim') {

	$arquivo  = $_GET['arquivo'];
	$finaliza = $_GET['finaliza'];
	$hd_chamado = $_GET['hd_chamado'];
	if(strlen($finaliza)>0){
		$controle_acesso_arquivo = $finaliza;

		$sql= "UPDATE tbl_controle_acesso_arquivo SET
				data_fim = CURRENT_DATE,
				hora_fim = CURRENT_TIME,
				status   = 'finalizado'
			WHERE controle_acesso_arquivo = $controle_acesso_arquivo";
		$res = pg_exec ($con,$sql);
		
		$sql= "UPDATE tbl_arquivo SET
				ultimo_admin = (SELECT admin FROM tbl_controle_acesso_arquivo WHERE controle_acesso_arquivo = $controle_acesso_arquivo),
				data         = CURRENT_DATE,
				hora         = CURRENT_TIME
			WHERE arquivo= $arquivo;";
		$res = pg_exec ($con,$sql);

		$sql = "SELECT descricao FROM tbl_arquivo WHERE arquivo = $arquivo";
		$res = pg_exec ($con,$sql);
		$descricao = pg_result ($res,0,0);
		system ("echo \"$descricao:$login_login\" >> /tmp/telecontrol/bloquear.txt");

		$arquivo = "";
	}

	if(strlen($arquivo) > 0 AND strlen($finaliza)==0){
		$sql= "SELECT descricao
			FROM tbl_arquivo
			WHERE arquivo= $arquivo";
		$res = pg_exec ($con,$sql);
	
		$sql= "SELECT * 
			FROM tbl_controle_acesso_arquivo
			JOIN tbl_arquivo USING (arquivo)
			JOIN tbl_admin USING (admin)
			WHERE tbl_controle_acesso_arquivo.status  = 'em uso' 
			AND   tbl_controle_acesso_arquivo.arquivo = $arquivo";
	
		$res = pg_exec ($con,$sql);
		if(@pg_numrows($res)>0){
			$msg_erro = "O arquivo ja esta sendo usado por outro admin!!";
		}else{
			$sql= "INSERT INTO tbl_controle_acesso_arquivo(admin, arquivo, data_inicio, hora_inicio, observacao, status,hd_chamado)
					values($login_admin, $arquivo, current_date, current_time, '$obs', 'em uso',$hd_chamado);";
	
			$res = pg_exec ($con,$sql);
	//$resposta .= "$sql";
			$sql= "UPDATE tbl_arquivo SET 
					ultimo_admin = $admin      ,
					data         = CURRENT_DATE,
					hora         = CURRENT_TIME
				WHERE arquivo = $arquivo;";

			$sql = "SELECT descricao FROM tbl_arquivo WHERE arquivo = $arquivo";
			$res = pg_exec ($con,$sql);
			$descricao = pg_result ($res,0,0);
			system ("echo \"$descricao:$login_login \" >> /tmp/telecontrol/liberar.txt");

		}
	}

	/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
	$resposta .="<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='750'>";
//	$resposta .="<tr class='Titulo' >";
//	$resposta .="<td colspan='11' align='left' background='../admin/imagens_admin/azul.gif'><font size='3'>ARQUIVOS ATIVOS </font></td>";
//	$resposta .="</tr>";
	$resposta .="<tr class='Titulo'>";
	//$resposta .="<td>Nº</td>";
	$resposta .="<th>&nbsp;</th>";
	$resposta .="<th>Chamado</th>";
	$resposta .="<th>Analista</th>";
	$resposta .="<th>Arquivo</th>";
	$resposta .="<th>Data Inicio</th>";
	$resposta .="<th>Hora Inicio</th>";
	//$resposta .="<td>Data Fim</td>";
	//$resposta .="<td>Hora Fim</td>";
	//$resposta .="<td>Obs</td>";
	//$resposta .="<td>Status</td>";
	$resposta .="<th>Ação</th>";
	$resposta .="</tr>";
	
	$sql= "SELECT   controle_acesso_arquivo                         ,
			admin                                           ,
			arquivo                                         ,
			descricao                                       ,
			TO_CHAR(data_inicio,'DD/MM/YY')   AS data_inicio,
			TO_CHAR(data_fim,'DD/MM/YY')      AS data_fim   ,
			hora_inicio                                     ,
			hora_fim                                        ,
			observacao                                      ,
			tbl_controle_acesso_arquivo.status              ,
			nome_completo                                   ,
			hd_chamado
		FROM tbl_controle_acesso_arquivo
		JOIN tbl_arquivo USING (ARQUIVO)
		JOIN tbl_admin USING (admin)
		WHERE tbl_controle_acesso_arquivo.status = 'em uso'
		ORDER BY status                                      ,
			tbl_controle_acesso_arquivo.data_inicio DESC";
	
	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
	
		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	
			$controle_acesso_arquivo = trim(pg_result($res,$i,controle_acesso_arquivo));
			$admin                   = trim(pg_result($res,$i,admin))                  ;
			$arquivo                 = trim(pg_result($res,$i,arquivo))                ;
			$descricao               = trim(pg_result($res,$i,descricao))              ;
			$data_inicio             = trim(pg_result($res,$i,data_inicio))            ;
			$data_fim                = trim(pg_result($res,$i,data_fim))               ;
			$hora_inicio             = trim(pg_result($res,$i,hora_inicio))            ;
			$hora_fim                = trim(pg_result($res,$i,hora_fim))               ;
			$observacao              = trim(pg_result($res,$i,observacao))             ;
			$status                  = trim(pg_result($res,$i,status))                 ;
			$nome_completo           = trim(pg_result($res,$i,nome_completo))          ;
			$hd_chamado              = trim(pg_result($res,$i,hd_chamado))             ;
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			$resposta .="<tr bgcolor='$cor' class='Conteudo' height='20'>";
			$resposta .="<td nowrap title='Finalizar a requisição do programa'>";
			if($status=="em uso" AND $login_admin == $admin){
				$resposta .="<a href=\"javascript:Exibir('dados','$arquivo','$controle_acesso_arquivo');\">X</a>";
			}else{
				$resposta .="<font color='#000000'>&nbsp;</font>";
			}
			$resposta .="</td>";
			//$resposta .="<td nowrap>$controle_acesso_arquivo</td>";
			$resposta .="<td nowrap align='center'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</td>";
			$resposta .="<td nowrap>$nome_completo</td>";
			$resposta .="<td nowrap title='Clique aqui para finalizar'>";
			if($login_admin == $admin AND $status=="em uso")$resposta .="<a href=\"javascript:Exibir('dados','$arquivo','$controle_acesso_arquivo');\">";
			$resposta .= substr_replace($descricao, '', 0, 9);
			$resposta .="</a></td>";
			$resposta .="<td nowrap align='center'>$data_inicio</td>";
			$resposta .="<td nowrap>$hora_inicio</td>";

			//$resposta .="<td nowrap>$data_fim</td>";
			//$resposta .="<td nowrap>$hora_fim</td>";
			//$resposta .="<td nowrap>$observacao</td>";

// Fornece: <body text='black'>

			$resposta .="<td nowrap><a href='adm_help.php?hd_chamado=$hd_chamado&programa=".str_replace("assist/www/admin/", "",substr_replace($descricao, '', 0, 9))."'>Help</a></td>";
			
			$resposta .="</tr>";
		}
	}else $resposta .="<tr bgcolor='#F7F5F0'><td colspan='10' align='center'><b>Sem requisições cadastradas&nbsp;</b></td></tr>";
	$resposta .="</table>";

	echo  "ok|".$resposta;
	exit;

}
?>
