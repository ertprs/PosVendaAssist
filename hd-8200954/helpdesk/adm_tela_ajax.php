<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
	$ajax_completar = strtolower( $_GET['ajax_completar'] );
if($ajax_completar == 'ok'){
	header("Content-Type: text/xml");
	
	$input = strtolower( $_GET['input'] );
	$len = strlen($input);
	
	echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?><results>";
	
	$sql= "SELECT * FROM tbl_ARQUIVO where arquivo like '%$input%'ORDER BY DESCRICAO";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
	
			$descricao= trim(pg_result($res,$i,descricao));	
			;
	
			for ($i=0;$i<count($aResults);$i++)
				echo"	<rs>".substr_replace($descricao, '', 0, 9)."</rs>";
			echo "
		</results>";
		}
	}
}

if(strlen($_GET['finalizar'])>0){

	$controle_acesso_arquivo = $_GET['finalizar'];
	
	$sql= "UPDATE tbl_controle_acesso_arquivo SET
			data_fim = CURRENT_DATE,
			hora_fim = CURRENT_TIME,
			status   = 'finalizado'
		WHERE controle_acesso_arquivo = $controle_acesso_arquivo";
		$res = pg_exec ($con,$sql);
	
	$sql= "SELECT arquivo
			FROM tbl_controle_acesso_arquivo
		WHERE controle_acesso_arquivo = $controle_acesso_arquivo; ";
	$res = pg_exec ($con,$sql);
	$arquivo = trim(pg_result($res,0,arquivo));	
	
	$sql= "UPDATE tbl_arquivo SET
			ultimo_admin = (SELECT admin FROM tbl_controle_acesso_arquivo WHERE controle_acesso_arquivo = $controle_acesso_arquivo),
			data         = CURRENT_DATE,
			hora         = CURRENT_TIME
		WHERE arquivo= $arquivo;";
	$res = pg_exec ($con,$sql);

}

if(strlen($_POST['enviar'])>0){

	if((strlen($_POST['admin'])>0) and(strlen($_POST['arquivo'])>0)){

		$admin   = $_POST['admin']     ;
		$arquivo = $_POST['arquivo']   ;
		$obs     = $_POST['observacao'];

		if(strlen($obs==0)) $obs= "null";

		$sql= "SELECT descricao
			FROM tbl_arquivo
			WHERE arquivo= $arquivo";
		$res = pg_exec ($con,$sql);

		if(@pg_numrows($res)>0){
			$descricao= trim(pg_result($res,0,descricao));
			if(is_file($descricao)){
				$data_atual = date ("Y-m-d");
				$data_arquivo= date ("Y-m-d", filectime($descricao));
	
				$hora_atual= date ("H:i");
				$hora_arquivo= date ("H:i", filectime($descricao));
				//echo "$data_atual >= $data_arquivo descricao" ;
				if($data_atual >= $data_arquivo){
/*					if($hora_atual >= $hora_arquivo){
						echo "Data: $data_atual- h:$hora_atual - MAIOR: $descricao foi modificado em: " . date ("Y-m-d", filectime($descricao)) ." H: $hora_arquivo";
					}else{
						echo "Data: $data_atual- h:$hora_atual - MAIOR: $descricao foi modificado em: " . date ("Y-m-d", filectime($descricao)) ." H: $hora_arquivo";
					}*/
				}else{
					echo "Data: $data_atual - MENOR: $descricao foi modificado em: " . date ("Y-m-d", filectime($descricao));
				}
			}else{
				 echo "<script language='JavaScript'>
						function mensagem() {
							  alert('Arquivo não existe');
						}
						mensagem();
					</script>";
			}
		}else{
			echo "<font color='#ff0000' >Arquivo nao encontrado. Verificar o caminho correto!!</font>";
		}
	
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
			$sql= "INSERT INTO tbl_controle_acesso_arquivo(admin, arquivo, data_inicio, hora_inicio, observacao, status)
					values($admin, $arquivo, current_date, current_time, $obs, 'em uso');";
			//echo "sql:".$sql;
			$res = pg_exec ($con,$sql);
	
			$sql= "UPDATE tbl_arquivo SET 
					ultimo_admin = $admin      ,
					data         = CURRENT_DATE,
					hora         = CURRENT_TIME
				WHERE arquivo = $arquivo;";
			//echo "sql:".$sql;
			$res = pg_exec ($con,$sql);
		}

	}else{
		echo "falta selecionar";
	}
}

include 'menu.php';
?>
<script type="text/javascript" src="js/auto_completar_ajax.js"></script>
<script type="text/javascript" src="js/auto_completar_dom.js"></script>
<script type="text/javascript" src="js/auto_completar.js"></script>
<?
include '../insere_diretorio.php';


/*--=== ATRIBUIR UM PROGRAMA A UM ANALISTA ====================================--*/
echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
echo "<FORM ACTION='$PHP_SELF' METHOD='POST'>";
echo "<tr bgcolor='#596D9B'>";
echo "<td nowrap colspan='3' class='Titulo' align='left' background='../admin/imagens_admin/azul.gif'><font size='3'>Acesso de Usuário</font></td>";
echo "</tr>";
echo "<tr class='Conteudo'>";
echo "<td nowrap align='left'>Analista</td>";

$sql= "SELECT * FROM tbl_admin 	WHERE tbl_admin.fabrica = 10 AND ativo IS TRUE ";
$res = pg_exec ($con,$sql);

if(@pg_numrows($res)>0){
	echo "<td class='table_line' align='left'>";

	echo "<select name='admin'>";
	echo "<option value=''>Selecionar";
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

		$admin = trim(pg_result($res,$i,admin));	
		$nome_completo_admin = trim(pg_result($res,$i,nome_completo));	
		echo "<option value='$admin'>$nome_completo_admin";
	}
	echo "</select>";
}
echo "</td>";
echo "</tr>";

echo "<tr bgcolor='#fafafa'>";
echo "<td class='Conteudo' align='left'>Arquivo:</td>";
echo "<td>";
$sql= "SELECT * FROM tbl_ARQUIVO ORDER BY DESCRICAO";
$res = pg_exec ($con,$sql);
if(@pg_numrows($res)>0){

	echo "<select name='arquivo'>";
	echo "<option value=''>Selecionar";
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

		$arquivo= trim(pg_result($res,$i,arquivo));	
		$descricao= trim(pg_result($res,$i,descricao));	
		echo "<option value='$arquivo'>".substr_replace($descricao, '', 0, 9);
	}
	echo "</select>";
	echo "<input type='submit' name='enviar' value='OK'>";
}
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<input type='hidden' name='requisicao' value='nova'>";

echo "</form><br>";




/*--=== ARQUIVOS ALTERADOS SEM AVISAR =========================================--*/
$sql= "SELECT ultimo_admin, 
		tbl_admin.nome_completo,
		arquivo                ,
		descricao              ,
		data                   ,
		hora                   ,
		TO_CHAR(data,'DD/MM/YYYY') AS data_inicio
	FROM tbl_arquivo
	LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_arquivo.ultimo_admin
	WHERE  TRIM(ultimo_admin) ='' OR ultimo_admin IS NULL
	OR (
		SELECT   status
		FROM tbl_controle_acesso_arquivo
		WHERE tbl_controle_acesso_arquivo.arquivo = tbl_arquivo.arquivo 
		ORDER BY tbl_controle_acesso_arquivo.controle_acesso_arquivo DESC LIMIT 1
	) <> 'em uso'
	
	ORDER BY DESCRICAO ";

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
		
	
		if(is_file($descricao)){
			//ARQUIVO EM DISCO
			$data_arquivo = date ("Y-m-d", filemtime($descricao));
			$hora_arquivo = date ("H:i"  , filemtime($descricao));
			
		}
		//echo "Data A:$data_arquivo Data Sis: $data Hora A:$hora_arquivo hora S $hora $descricao<br>";
		if($data_arquivo >= $data ){
			if($hora_arquivo >= $hora OR $data_arquivo > $data ){
				$erro= "<font color='#ff0000'> $data_arquivo - H:$hora_arquivo / $data - $hora</font>";

				$data_arquivo = explode("-",$data_arquivo);
				$data_arquivo = $data_arquivo[2].'/'.$data_arquivo[1].'/'.$data_arquivo[0];

				if($cor=="#FFEEEE")$cor = '#F7F5F0';
				else               $cor = '#FFEEEE';

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
		if($status=="em uso"){
			echo "<a href='$PHP_SELF?finalizar=$controle_acesso_arquivo'><font color='#0000ff'>finalizar</font></a>";
		}else{
			echo "<font color='#000000'>-</font>";
		}
		echo "</td>";
		echo "</tr>";
	}
}else echo "<tr bgcolor='#F7F5F0'><td colspan='10' align='center'><b>Sem requisições cadastradas&nbsp;</b></td></tr>";
echo "</table>";
?>
<div>
<form method="get" action="">
<label for="testinput">Country</label>
<input type="text" id="testinput" name="testinput"value="" /> 
<input type="submit" value="submit" />
</form>
</div>
<script type="text/javascript">
	var options = {
		script:"<?echo $PHP_SELF;?>?ajax_completar=ok",
		varname:"input",
		minchars:1
	};
	var as = new AutoSuggest('testinput', options);
</script>

<?
include "helpdesk/rodape.php";
?>  


