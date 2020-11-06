<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
if($login_fabrica<>10){echo "PROIBIDO";exit;}

include 'menu.php';
include '../insere_diretorio.php';
?>
<form method='POST'>
<table align='center'>
<tr>
	<td><input name="busca" size="41" maxlength="2048" value="<?=$busca?>" title="Pesquisar" type="text" ><font size="-1"> <input name="btnG" value="Pesquisar" type="submit"></td>
</tr>

</table>
</form>
<?
if(strlen($_POST["btnG"])>0){
	$descricao = $_POST["busca"];
	$sql = "SELECT 	controle_acesso_arquivo                         ,
			admin                                           ,
			arquivo                                         ,
			descricao                                       ,
			TO_CHAR(data_inicio,'DD/MM/YY')   AS data_inicio,
			TO_CHAR(data_fim,'DD/MM/YY')      AS data_fim   ,
			hora_inicio                                     ,
			hora_fim                                        ,
			observacao                                      ,
			tbl_controle_acesso_arquivo.status              ,
			nome_completo
		FROM tbl_controle_acesso_arquivo 
		JOIN tbl_admin USING(admin) 
		JOIN tbl_arquivo USING(arquivo)
		WHERE tbl_arquivo.descricao like '%$descricao%'
		ORDER BY descricao,controle_acesso_arquivo DESC";
	$res = pg_exec ($con,$sql);
	
	if(pg_numrows($res)>0){
		echo "<BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
		echo "<tr class='Titulo' >";
		echo "<td colspan='9' align='left' background='../admin/imagens_admin/azul.gif'><font size='3'>ARQUIVOS </font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>Nº</td>";
		echo "<td>Analista</td>";
		echo "<td>Arquivo</td>";
		echo "<td>Data Inicio</td>";
		echo "<td>Hora Inicio</td>";
		echo "<td>Data Fim</td>";
		echo "<td>Hora Fim</td>";
		echo "<td>Obs</td>";
		echo "<td>Status</td>";
		echo "</tr>";
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
	
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
	
			echo "<tr bgcolor='$cor' class='Conteudo'>";
			echo "<td nowrap>$controle_acesso_arquivo</td>";
			echo "<td nowrap>$nome_completo</td>";
			echo "<td nowrap>".substr_replace($descricao, '', 0, 9)."</td>";
			echo "<td nowrap align='center'>$data_inicio</td>";
			echo "<td nowrap>$hora_inicio</td>";
			echo "<td nowrap>$data_fim</td>";
			echo "<td nowrap>$hora_fim</td>";
			echo "<td nowrap>$observacao</td>";
			echo "<td nowrap>$status</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else echo "Arquivo não encontrado";
}

/*--=== ARQUIVOS ALTERADOS SEM AVISAR =========================================--*/
$sql= "SELECT ultimo_admin, 
		tbl_admin.nome_completo,
		arquivo                ,
		descricao              ,
		data                   ,
		hora                   ,
		(
		SELECT   status
		FROM tbl_controle_acesso_arquivo
		WHERE tbl_controle_acesso_arquivo.arquivo = tbl_arquivo.arquivo 
		ORDER BY tbl_controle_acesso_arquivo.controle_acesso_arquivo DESC LIMIT 1
		) as status,
		TO_CHAR(data,'DD/MM/YYYY') AS data_inicio
	FROM tbl_arquivo
	LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_arquivo.ultimo_admin
	WHERE  (TRIM(ultimo_admin::text) ='' OR ultimo_admin IS NULL
	OR (
		SELECT   status
		FROM tbl_controle_acesso_arquivo
		WHERE tbl_controle_acesso_arquivo.arquivo = tbl_arquivo.arquivo 
		ORDER BY tbl_controle_acesso_arquivo.controle_acesso_arquivo DESC LIMIT 1
	) <> 'em uso')
	AND tbl_admin.ativo
	AND tbl_admin.responsabilidade is not null
	AND        tbl_admin.admin not in (24,435)
	ORDER BY data desc ";

$res = pg_exec ($con,$sql);

if(@pg_numrows($res)>0){

	echo "<table  border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' align='center' bordercolor='#990000'>";
	echo "<tr class='Titulo'>";
	echo "<td colspan='5' background='../admin/imagens_admin/vermelho.gif' align='left'><font size='3'>ARQUIVOS ALTERADOS SEM AVISAR</font></td>";
	echo "</tr>";

	echo "<tr class='Titulo'>";
	echo "<td bgcolor='#FF6666'>Nº</td>";
	echo "<td bgcolor='#FF6666'>Ult admin</td>";
	echo "<td bgcolor='#FF6666'>Arquivo</td>";
	echo "<td bgcolor='#FF6666'>Data Sistema</td>";
	echo "<td bgcolor='#FF6666'>Data Arquivo</td>";
	echo "</tr>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {	
		$admin         =trim(pg_result($res,$i,ultimo_admin)) ;
		$arquivo       =trim(pg_result($res,$i,arquivo))      ;
		$descricao     =trim(pg_result($res,$i,descricao))    ;
		$data_inicio   =trim(pg_result($res,$i,data_inicio))  ;
		$nome_completo =trim(pg_result($res,$i,nome_completo));
		$data          =trim(pg_result($res,$i,data))         ;
		$hora          =trim(pg_result($res,$i,hora))         ;
		$status          =trim(pg_result($res,$i,status))         ;
	
		if(is_file($descricao)){
			//ARQUIVO EM DISCO
			$data_arquivo = date ("Y-m-d", filemtime($descricao));
			$hora_arquivo = date ("H:i"  , filemtime($descricao));
			
		}
		//echo "Data A:$data_arquivo Data Sis: $data Hora A:$hora_arquivo hora S $hora $descricao<br>";
		if($data_arquivo >= $data AND trim($status)<>'em uso'){
			if($hora_arquivo >= $hora OR $data_arquivo > $data ){
				$erro= "<font color='#ff0000'> $data_arquivo - H:$hora_arquivo / $data - $hora</font>";

				$data_arquivo = explode("-",$data_arquivo);
				$data_arquivo = $data_arquivo[2].'/'.$data_arquivo[1].'/'.$data_arquivo[0];

				$cor =($i%2) ? '#F7F5F0' : '#FFEEEE';

				echo "<tr bgcolor='$cor' class='Conteudo'>";
				echo "<td>$arquivo</td>";
				echo "<td nowrap > $nome_completo</td>";
				echo "<td nowrap align='left'><font color='#ff0000'>".substr_replace($descricao, '', 0, 9)."</font></td>";
				echo "<td nowrap > $data_inicio $hora</td>";
				echo "<td nowrap > $data_arquivo $hora_arquivo</td>";
				echo "</tr>";
			}else{
				$erro= "<font color='#0000ff'> $data_arquivo - H:$hora_arquivo / $data - $hora</font>";
			}
		}else{
			$erro= "<font color='#0000ff'> $data_arquivo -> $data</font>";
		}
	}
	echo "</table>";
}else{
	echo "<tr bgcolor='#ff5555'><td colspan='10'> Sem requisições cadastradas&nbsp;</td></tr>"; 
}


/*--=== ARQUIVOS SOLICITADOS ==================================================--*/
/*
echo "<BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
echo "<tr class='Titulo' >";
echo "<td colspan='10' align='left' background='../admin/imagens_admin/azul.gif'><font size='3'>ARQUIVOS ATIVOS <a href='$PHP_SELF?mostrar_tudo=sim'>mostrar tudo</a></font></td>";
echo "</tr>";
echo "<tr class='Titulo'>";
echo "<td>Nº</td>";
echo "<td>Analista</td>";
echo "<td>Arquivo</td>";
echo "<td>Data Inicio</td>";
echo "<td>Hora Inicio</td>";
echo "<td>Data Fim</td>";
echo "<td>Hora Fim</td>";
echo "<td>Obs</td>";
echo "<td>Status</td>";
echo "<td>Ação</td>";
echo "</tr>";

$where= "WHERE tbl_controle_acesso_arquivo.status = 'em uso'";

if(strlen($_GET['mostrar_tudo'])>0) $where="";

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
		nome_completo
	FROM tbl_controle_acesso_arquivo
	JOIN tbl_arquivo USING (ARQUIVO)
	JOIN tbl_admin USING (admin)
$where
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

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		echo "<tr bgcolor='$cor' class='Conteudo'>";
		echo "<td nowrap>$controle_acesso_arquivo</td>";
		echo "<td nowrap>$nome_completo</td>";
		echo "<td nowrap>".substr_replace($descricao, '', 0, 9)."</td>";
		echo "<td nowrap align='center'>$data_inicio</td>";
		echo "<td nowrap>$hora_inicio</td>";
		echo "<td nowrap>$data_fim</td>";
		echo "<td nowrap>$hora_fim</td>";
		echo "<td nowrap>$observacao</td>";
		echo "<td nowrap>$status</td>";
		echo "<td nowrap>";
		if($status=="em uso" AND $login_admin == $admin){
			echo "finalizar";
		}else{
			echo "<font color='#000000'>-</font>";
		}
		echo "</td>";
		echo "</tr>";
	}
}else echo "<tr bgcolor='#F7F5F0'><td colspan='10' align='center'><b>Sem requisições cadastradas&nbsp;</b></td></tr>";
echo "</table>";
*/
include "helpdesk/rodape.php";
?>  


