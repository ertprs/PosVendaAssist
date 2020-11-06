<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);


if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
	if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
	if($_POST['status'])              { $status          = trim ($_POST['status']);}
	if($_POST['transfere'])           { $transfere         = trim ($_POST['transfere']);}

	if (strlen($comentario) < 3){
		$msg_erro="Comentário muito pequeno";
	}

	if (strlen($status) < 3){
		$msg_erro="O status deve ser preenchido";
	}

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");
		
		$sql =" UPDATE tbl_hd_chamado 
				SET status = '$status' , 
					titulo = '$titulo',
					atendente = $transfere
				WHERE hd_chamado = $hd_chamado";
		$res = pg_exec ($con,$sql);

		$sql ="INSERT INTO tbl_hd_chamado_item (
					hd_chamado                                                   ,
					comentario                                                   ,
					admin                                                        
				) VALUES (
					$hd_chamado                                                  ,
					'$comentario'                                                ,
					$login_admin                                                 
				);";
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro = 'Não foi possível Inserir o Chamado';
		}else{
			$res = @pg_exec($con,"COMMIT");
			header ("Location: adm_chamado_detalhe.php?hd_chamado=$hd_chamado");
		}
	}
}



if(strlen($hd_chamado) > 0){
	$sql = "UPDATE tbl_hd_chamado SET atendente = $login_admin WHERE hd_chamado = $hd_chamado AND atendente IS NULL";
	$res = pg_exec ($con,$sql);

	$sql= " SELECT tbl_hd_chamado.hd_chamado                             ,
					tbl_hd_chamado.admin                                 ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
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
		$status               = pg_result($res,0,status);
		$atendente            = pg_result($res,0,atendente);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$telefone             = pg_result($res,0,fone);
		$nome_completo        = pg_result($res,0,nome_completo);
		$fabrica_nome         = pg_result($res,0,fabrica_nome);
		$login                = pg_result($res,0,login);
	}else{
		$msg_erro="Chamado não encontrado";
	}
}



$TITULO = "ADM - Responder Chamado";

include "menu.php";
?>


<table width = '500' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

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
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>
		<select name="status" size="1">
		<option value=''></option>
		<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
		<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
		<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
		<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
		<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
		<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
		</select>
	</td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Analista </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $atendente_nome ?> </td>
</tr>
<? } ?>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' >&nbsp;<input type='text' size='30' name='titulo' value='<?= $titulo ?>' > </td>
	
	
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Transfere </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>
		
<?
		##### INÍCIO FAMÍLIA #####
		$sql = "SELECT  *
				FROM    tbl_admin
				WHERE   tbl_admin.fabrica = 10
				ORDER BY tbl_admin.nome_completo;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 200px;' name='transfere'>\n";
			echo "<option value=''>- ESCOLHA -</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_admin = trim(pg_result($res,$x,admin));
				$aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

				echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo ">$aux_nome_completo</option>\n";
			}
			
			echo "</select>\n";
		}
		##### FIM FAMÍLIA #####
?>
		
		
	</td>
	
	
	
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Nome </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='50' name='nome' value='<?= $nome ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> ></td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='30' name='email' value='<?= $email ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> ></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='15' name='fone' value='<?= $fone ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> ></td>
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
	echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Interações</b></td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><strong>Nº</strong></td>";
	echo "<td nowrap><strong>Data e Hora</strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td ><strong>  Coment&aacute;rio </strong></td>";
	echo "<td ><strong> Anexo </strong></td>";
	echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Autor </strong></td>";
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
		echo "<td nowrap>$data_interacao </td>";
		echo "<td >" . nl2br ($item_comentario) . "</td>";

		echo "<td>";
		$dir = "documentos/";
		$dh  = opendir($dir);
//		echo "$hd_chamado_item";
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$hd_chamado_item") !== false){
//			echo "$filename";
			echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename>Download</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
			}
		}
		echo "</td>";
		echo "<td nowrap >$autor</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
	}	
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='5' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}

echo "<center>";
echo "<b><font face='arial' color='#666666'>Resposta ao chamado</font></b>";
echo "<br>";

echo "<table width = '500' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'>";
echo "<textarea name='comentario' cols='50' rows='6' wrap='VIRTUAL'>$comentario</textarea><br>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<input type='submit' name='btn_acao' value='Responder Chamado'>";
echo "</center>";

echo "</form>";


?>

<? include "rodape.php" ?>