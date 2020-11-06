<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$lista_admin = $_GET["lista_admin"]; 
$fabrica     = $_GET["fabrica"]; 


if ($lista_admin==1){
	$xml  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml .= "<admins>\n";
	if (strlen($fabrica)>0){
		$sql ="SELECT CASE WHEN fabrica = 10 THEN 0 ELSE
						1 END AS fabrica,
						admin,
						login
				FROM tbl_admin
				WHERE (fabrica = $fabrica OR admin = $login_admin)
				AND login IS NOT NULL
				AND length(trim(login))>0
				ORDER BY fabrica,login ";
		$resD = pg_exec ($con,$sql) ;
		$row = pg_numrows ($resD);
		if($row>0) {
			for($i=0; $i<$row; $i++){
				$admin     = trim(pg_result($resD, $i, 'admin'));
				$login     = trim(pg_result($resD, $i, 'login'));
				if (strlen($login)==0){
					$login = "-";
				}
				if ($admin == $login_admin){
					$login = $login." ( Você )";
				}
				$xml .= "<admin>\n";
				$xml .= "<id>".$admin."</id>\n";
				$xml .= "<login>".$login."</login>\n";
				$xml .= "</admin>\n";
			}
			Header("Content-type: application/xml; charset=iso-8859-1"); 
		}
	}
	$xml.= "</admins>\n";
	echo $xml;
	exit;
}


/*ATENDIMENTO TELEFONICO*/
$acao = $_GET['acao'];
if(strlen($acao)>0 and $acao=="INICIAR_ATENDIMENTO"){
	$res = @pg_exec($con,"BEGIN TRANSACTION");

	//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
	$sql =" SELECT hd_chamado_item
			FROM tbl_hd_chamado_item
			WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
				AND termino IS NULL
			ORDER BY hd_chamado_item desc
			LIMIT 1 ;";

	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if(pg_numrows($res)>0){
		$hd_chamado_item = pg_result($res,0,hd_chamado_item);

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);	
	}

	$sql ="select	hd_chamado_atendente  ,
					hd_chamado            , 
					data_termino 
			from tbl_hd_chamado_atendente 
			where admin = $login_admin 
			order by data_termino desc 
			limit 1";
	$res = pg_exec($con,$sql);
//	echo "$sql<BR>";
	if(pg_numrows($res)>0){
		$xhd_chamado           = pg_result($res,0,hd_chamado);
		$data_termino          = pg_result($res,0,data_termino);
		$hd_chamado_atendente  = pg_result($res,0,$hd_chamado_atendente);
		if(strlen($data_termino)==0){/*atendente estava trabalhando com algum chamado*/
		/*Insere uma interacao no chamado que o atendente estava trabalhando*/
			$sql =	"INSERT INTO tbl_hd_chamado_item (
							hd_chamado                   ,
							comentario                   ,
							interno                      ,
							admin                        ,
							data                         ,
							termino                      
						) VALUES (
							$xhd_chamado                                                  ,
							'Chamado interrompido para atendimento de telefone'           ,
							't'                                                           ,
							$login_admin                                                  ,
							current_timestamp                                             ,
							current_timestamp
						);";
			$res = pg_exec ($con,$sql);
			//echo "$sql<BR>";
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)==0){
				$sql = "update tbl_hd_chamado_atendente set data_termino=current_timestamp
						where hd_chamado_atendente = $hd_chamado_atendente";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			//	echo "$sql<BR>";
			}
		}
		/*insere novo chamado para atendente*/
			if(strlen($msg_erro)==0){
				$sql =	"INSERT INTO tbl_hd_chamado (
									admin                                                        ,
									fabrica                                                      ,
									fabrica_responsavel                                          ,
									titulo                                                       ,
									atendente                                                    ,
									categoria                                                    ,
									status                                                       
								) VALUES (
									$login_admin                            ,
									10                                      ,
									10                                      ,
									'Atendimento Telefone'                  ,
									$login_admin                            ,
									'Suporte Telefone'                      ,
									'Novo'                                                    
								);";
			//	echo "$sql<BR>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado  = pg_result ($res,0,0);

				$sql = "INSERT INTO tbl_hd_chamado_atendente(
												hd_chamado ,
												admin      ,
												data_inicio
										)VALUES(
										$hd_chamado       ,
										$login_admin      ,
										CURRENT_TIMESTAMP
										)";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);


			//	echo "$sql<BR>";	


			}
		
		if(strlen($msg_erro)==0){
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			//$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$hd_chamado  = $_POST['hd_chamado'];
	$fabrica     = $_POST['fabrica'];
	$titulo      = $_POST['titulo'];
	$status      = $_POST['status'];
	$admin       = $_POST['admin'];
	$tipo_chamado= $_POST['tipo_chamado'];

	if (strlen($titulo)==0){
		$titulo = "titulo";
	}else{
		$titulo = "'".$titulo."'";
	}

	if (strlen($admin)==0){
		$admin = $login_admin;
	}

	$comentario = trim($_POST['comentario']);
	if(strlen($fabrica)==0){$msg_erro .= "Escolha o fabricante<BR>";}
	if (strlen($comentario) < 3)$msg_erro.="Comentário muito pequeno";

	if(strlen($msg_erro)==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");

		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_exec ($con,$sql);

		$sql ="INSERT INTO tbl_hd_chamado_item (
						hd_chamado                                                   ,
						comentario                                                   ,
						admin                                                        ,
						status_item                                                  ,
						interno                                                      ,
						data
					) VALUES (
						$hd_chamado                                                  ,
						'$comentario'                                                ,
						$login_admin                                                 ,
						'$status'                                                    ,
						't'                                                          ,
						current_timestamp
					);";
		//			echo "$sql<BR>";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
	}
	if(strlen($msg_erro)==0){
		if($status == 'Resolvido'){
			$data_resolvido = " data_resolvido = current_timestamp ,";
		}
		$sql = "UPDATE tbl_hd_chamado set
						fabrica      = $fabrica, 
						$data_resolvido 
						status       ='$status',
						titulo       = $titulo,
						admin        = $admin,
						tipo_chamado = $tipo_chamado
				where hd_chamado = $hd_chamado
				and fabrica_responsavel = $login_fabrica";
		$res = pg_exec ($con,$sql);
	//	echo "$sql<BR>";
		$msg_erro = pg_errormessage($con);
	}
	if(strlen($msg_erro)==0){
		if($status == 'Resolvido'){
			$sql = "UPDATE tbl_hd_chamado_atendente
					SET data_termino = CURRENT_TIMESTAMP
					WHERE admin                = $login_admin
					AND   hd_chamado           = $hd_chamado
					AND   data_termino is null";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
//			echo "$sql<BR>";
			$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin) VALUES ($hd_chamado, 'Chamado resolvido. Se você não concordar com a solução basta inserir novo comentário para reabrir o chamado.',$login_admin)";
		}
	}
	if(strlen($msg_erro)==0){
		$res = @pg_exec ($con,"COMMIT TRANSACTION");
	//	$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		header ("Location: adm_chamado_detalhe.php?hd_chamado=$hd_chamado");
		exit;
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$hd_chamado = $_POST['hd_chamado'];
		$fabrica    = $_POST['fabrica'];
		$status     = $_POST['status'];
		$comentario = trim($_POST['comentario']);
	}
}
//$hd_chamado="4708";
if(strlen($hd_chamado)>0){
	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.duracao                               ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.prioridade                            ,
					tbl_hd_chamado.prazo_horas                           ,
					tbl_fabrica.nome   AS fabrica_nome                   ,
					tbl_admin.login                                      ,
					tbl_admin.nome_completo                              ,
					tbl_admin.fone                                       ,
					tbl_admin.email                                      ,
					atend.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			LEFT JOIN tbl_admin atend ON tbl_hd_chamado.atendente = atend.admin
			WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$admin                = pg_result($res,0,admin);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$duracao              = pg_result($res,0,duracao);
		$atendente            = pg_result($res,0,atendente);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$fone                 = pg_result($res,0,fone);
		$nome_completo        = pg_result($res,0,nome_completo);
		$fabrica_nome         = pg_result($res,0,fabrica_nome);
		$login                = pg_result($res,0,login);
	}else{
		$msg_erro="Chamado não encontrado";
	}
}
$TITULO = "Atendimento telefone - Telecontrol Hekp-Desk";
$ONLOAD = "frm_chamado.titulo.focus()";
if (strlen ($hd_chamado) > 0) $ONLOAD = "";
include "menu.php";
?>

<script language='javascript' src='ajax.js'></script>
<script language="JavaScript">
	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}
			
	var http_forn = new Array();


	function listaAdmin(fabrica) {
	//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
		catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
			catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
		}
		if(ajax) {
				admin  = document.getElementById("admin");
				admin.options.length = 1;
				ajax.open("GET", "<?=$PHP_SELF?>?lista_admin=1&fabrica="+fabrica.value);
				ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		
				ajax.onreadystatechange = function() {
					if(ajax.readyState == 1) {admin.innerHTML = "Carregando...!";
					}
					if(ajax.readyState == 4 ) {
						if(ajax.responseXML) { 
							montaComboAdmin(ajax.responseXML,admin);
						} else {
							admin.innerHTML = "Selecione a Fábrica";
						}
					}
				}
				ajax.send(null);
		}
	}

	function montaComboAdmin(obj,admin){
		var dataArray   = obj.getElementsByTagName("admin");
		if(dataArray.length > 0) {
			for(var i = 0 ; i < dataArray.length ; i++) {
				var item = dataArray[i];
				var id    =  item.getElementsByTagName("id")[0].firstChild.nodeValue;
				var login =  item.getElementsByTagName("login")[0].firstChild.nodeValue;
				var novo = document.createElement("option");
				novo.value = id;
				novo.text  = login;
				admin.options.add(novo);
			}
		} else { 
			admin.innerHTML = "Nenhuma solução encontrada";
		}
	}
</script>

<table width = '500' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>

<form name='frm_chamado' action='<? echo $PHP_SELF ?>' method='post' enctype='multipart/form-data' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>

<?
if (strlen ($hd_chamado) > 0) {
	echo "<tr>";
	echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado nº. $hd_chamado </strong></td>";
	echo "</tr>";
}
?>

<tr>
	<td width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Login </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<?= $login ?> </td>
	<td width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Abertura </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $data ?> </td>
</tr>

<?
if (strlen ($hd_chamado) > 0) {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Status </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<?= $status ?> </td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Analista </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $atendente_nome ?> </td>
</tr>
<? } ?>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='50' name='titulo' maxlength='50' value='<?= $titulo ?>' > </td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Nome </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='30' maxlength='30'  name='nome' value='<?= $nome ?>'> NOME COMPLETO DO USUÁRIO</td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='30' name='email' maxlength='30' value='<?= $email ?>'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='15' name='fone' value='<?= $fone ?>'></td>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fábrica </strong></td>

<?
$sql = "SELECT   * 
		FROM     tbl_fabrica 
		ORDER BY nome";

$res = pg_exec ($con,$sql);
$n_fabricas = pg_numrows($res);
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'>";
	echo "<select class='frm' style='width: 200px;' name='fabrica' class='caixa' onChange='listaAdmin(this)'></center>\n";
	echo "<option value=''>- FÁBRICA -</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$xfabrica   = trim(pg_result($res,$x,fabrica));
		$nome      = trim(pg_result($res,$x,nome));
		echo "<option value='$xfabrica' ";
		if($fabrica == $xfabrica){echo "SELECTED";}
		echo ">$nome</option>\n";
	}
	echo "</select>\n";
	echo"</td>";?>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Status </strong></td>
	<?
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'>";
	echo "<select class='frm' style='width: 100px;' name='status' class='caixa'></center>\n";
	echo "<option value='Análise'"; 
	if($status == 'Análise'){echo "SELECTED";}
	echo " >Análise</option>\n";
	echo "<option value='Resolvido'";
		if($status == 'Resolvido'){echo "SELECTED";}
		echo ">Resolvido</option>\n";
	echo "</select>\n";
	echo"</td>";
?>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Admin </strong></td>

	<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'>
	<select class='frm' style='width: 200px;' name='admin' id='admin' class='caixa'></center>

	<?
		if (strlen($admin)>0){
			echo "<option value='$admin'>$login</option>";
		}else{
			echo "<option value=''>- Selecione a Fábrica - </option>";
		}
	?>
		
	</select>
	</td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Tipo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
<?
	$sql = "SELECT	tipo_chamado,
					descricao 
			FROM tbl_tipo_chamado 
			ORDER BY descricao;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0){

			echo " <input type='hidden' size='60' name='tipo_chamado'  value='$tipo_chamado' >"; }
			echo "<select name=\"tipo_chamado\" size=\"1\" ";
			if (strlen ($hd_chamado) > 0 and strlen($tipo_chamado)>0){ 
				echo " disabled "; 
			}
			echo " class='Caixa'>";

			for($i=0;pg_numrows($res)>$i;$i++){
				$xtipo_chamado = pg_result($res,$i,tipo_chamado);
				$xdescricao    = pg_result($res,$i,descricao);
				echo "<option value='$xtipo_chamado' ";	
				if($tipo_chamado == $xtipo_chamado){echo " SELECTED ";}
				echo " >$xdescricao</option>";
			}

		echo "</select>";
	}
?>
	</td>
</tr>
</table>
<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
				tbl_hd_chamado_item.comentario                            ,
				tbl_admin.nome_completo AS autor                          
		FROM tbl_hd_chamado_item 
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Interações</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><font size='2'><strong>Nº </strong></font></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td nowrap><font size='2'><strong>Data</strong></font></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td ><font size='2'><strong>Coment&aacute;rio </strong></font></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'><font size='2'><strong> Anexo </strong></font></td>";
	echo "<td nowrap><font size='2'><strong>Autor </strong></font></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);

		$cor='#ffffff';
		if ($i % 2 == 0) $cor = '#F2F7FF';

		echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='50'>$x </td>";
		echo "<td></td>";
		echo "<td nowrap>$data_interacao </td>";
		echo "<td></td>";
		echo "<td >" . nl2br ($item_comentario) . "</td>";

		echo "<td>";
		$dir = "documentos/";
		$dh  = opendir($dir);
//		echo "$hd_chamado_item";
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$hd_chamado_item") !== false){
//			echo "$filename";
			echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'>Download</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
			}
		}
		echo "</td>";
		echo "<td nowrap > $autor</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
	}
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='7' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
}

echo "<center>";
echo "<table width = '500' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'>";
echo "<textarea name='comentario' cols='50' rows='6' wrap='VIRTUAL'>$comentario</textarea><br>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<input type='submit' name='btn_acao' value='Enviar Chamado'>";
echo "</center>";

echo "</form>";


?>
		</td>
	</tr>
</table>

<? include "rodape.php" ?>
