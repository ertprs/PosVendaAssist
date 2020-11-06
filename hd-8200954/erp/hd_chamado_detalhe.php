<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

function validatemail($email=""){ 
	if (preg_match("/^[a-z]+([\._\-]?[a-z0-9]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
		$valida = "1";
	}
	else {
		$valida = "0"; 
	}
	return $valida; 
}

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);
if($_GET ['msg'])        $msg        = trim ($_GET ['msg']);

if(strlen($btn_resolvido)>0){
	$sql= "UPDATE tbl_hd_chamado set resolvido = CURRENT_TIMESTAMP , exigir_resposta=null WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
}

if (strlen ($btn_acao) > 0) {

	if($_POST['comentario'])          { $comentario      = trim ($_POST['comentario']);}
	if($_POST['titulo'])              { $titulo          = trim ($_POST['titulo']);}
	if($_POST['categoria'])           { $categoria       = trim ($_POST['categoria']);}
	if($_POST['nome'])                { $nome            = trim ($_POST['nome']);}
	if($_POST['email'])               { $email           = trim ($_POST['email']);}
	if($_POST['fone'])                { $fone            = trim ($_POST['fone']);}
	if($_POST['status'])              { $status          = trim ($_POST['status']);}
	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

/*--==VALIDAÇÕES=====================================================--*/

	//SETA P/ USUARIO "SUPORTE"
	$fabricante_responsavel = $login_empresa;

	
	if (strlen($comentario) < 2) $msg_erro = "Comentário muito pequeno"                  ;


	$comentario =  str_replace($filtro,"", $comentario);
//CASO SEJA UM ERRO OU UMA ALTERAÇÃO.
	if($categoria=='Erro' OR $categoria=='Alteração') $prioridade = 0;
	else                                              $prioridade = 5;

	if (strlen($msg_erro) == 0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");

		if (strlen($titulo) == 0){
			$msg_erro="Por favor insira um titulo!";
		}

		//CASO A FÁBRICA TENHA UM SUPERVISOR O CHAMADO VAI PARA ANÁLISE DO MESMO
		if(strlen($hd_chamado)==0){

			$sql =	"INSERT INTO tbl_hd_chamado (
						posto                                                        ,
						empregado                                                    ,
						fabrica                                                      ,
						fabrica_responsavel                                          ,
						titulo                                                       ,
						categoria                                                    ,
						status                                                       ,
						prioridade
					) VALUES (
						$login_loja                                                  ,
						$login_empregado                                             ,
						$login_empresa                                               ,
						$fabricante_responsavel                                      ,
						'$titulo'                                                    ,
						'$categoria'                                                 ,
						'$status'                                                    ,
						$prioridade
					);";

			$res = pg_exec ($con,$sql);
			
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
			
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado  = pg_result ($res,0,0);
			
			$dispara_email = "SIM";
	//FIM DO ENVIAR EMAIL
		}//fim do inserir chamado


		$sql =	"INSERT INTO tbl_hd_chamado_item (
					hd_chamado                                                   ,
					empregado                                                    ,
					comentario                                                   ,
					status_item                                                  ,
					posto
				) VALUES (
					$hd_chamado                                                  ,
					$login_empregado                                             ,
					'$comentario'                                                ,
					'$status'                                                    ,
					$login_loja
				);";


		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
		$hd_chamado_item  = pg_result ($res,0,0);

//QUANDO O CHAMADO FOR REABERTO SETA ELE EM ANÁLISE
		if($status<>'Novo'and $status<>'Aprovação'){
			 $sql    = "UPDATE tbl_hd_chamado SET status = 'Análise', exigir_resposta = 'f' WHERE hd_chamado = $hd_chamado";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			$sql    = "UPDATE tbl_hd_chamado_item SET status_item = 'Análise'WHERE hd_chamado_item = $hd_chamado_item";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
		$msg_erro = substr($msg_erro,6);
		


//ROTINA DE UPLOAD DE ARQUIVO
		if (strlen ($msg_erro) == 0) {
			$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes) 

			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

				if (strlen($msg_erro) == 0) {
					// Pega extensão do arquivo
					preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "'".$ext[1]."'";
					
					$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));
					
					// Gera um nome único para a imagem
					$nome_anexo = "/www/assist/www/erp/chamados/" . $hd_chamado_item."-".strtolower ($nome_sem_espaco);

					// Faz o upload da imagem
					if (strlen($msg_erro) == 0) {
						if (copy($arquivo["tmp_name"], $nome_anexo)) {
						}else{
							$msg_erro = "Arquivo não foi enviado!!!";
						}
					}//fim do upload da imagem
				}//fim da verificação de erro
			}//fim da verificação de existencia no apache
		}//fim de todo o upload

//FIM DO ANEXO DO ARQUIVO

	//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

		if( strlen($dispara_email) > 0 AND strlen($msg_erro)==0 ){

			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte@telecontrol.com.br";
			$assunto       = "Novo Chamado aberto";

			$corpo.= "<br>Foi inserido um novo CHAMADO no HELP DESK do sistema TELECONTROL ASSIST, clique no link abaixo para confirmar o email.\n\n";
			$corpo.= "<br>Chamado n°: $hd_chamado\n\n";
			$corpo.= "<br>Titulo: $titulo \n";
			$corpo.= "<br>Solicitante: $nome <br>Email: $email\n\n";
			$corpo.= "<br><a href='http://www.telecontrol.com.br/assist/help_desk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>CLIQUE AQUI PARA VER O CHAMADO</a> \n\n";
			$corpo.= "<br><br>Telecontrol\n";
			$corpo.= "<br>www.telecontrol.com.br\n";
			$corpo.= "<br>_______________________________________________\n";
			$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

			$body_top  = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
	//$corpo = $body_top.$corpo;

			if ( mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
				$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
			}else{
				$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.<br>";
			}

		}





		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Não foi possível Inserir o Chamado. ';
		}else{
			$res = @pg_exec($con,"COMMIT");
			header ("Location: $PHP_SELF?hd_chamado=$hd_chamado&msg=$msg");
		}
	}
}


if(strlen($hd_chamado)>0){
	$sql= " SELECT tbl_hd_chamado.hd_chamado                              ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data   ,
					tbl_hd_chamado.titulo                                ,
					tbl_hd_chamado.categoria                             ,
					tbl_hd_chamado.status                                ,
					tbl_hd_chamado.atendente                             ,
					tbl_hd_chamado.fabrica_responsavel                   ,
					tbl_hd_chamado.resolvido                             ,
					tbl_hd_chamado.empregado
			FROM tbl_hd_chamado
			WHERE hd_chamado = $hd_chamado";

	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$hd_chamado           = pg_result($res,0,hd_chamado);
		$empregado            = pg_result($res,0,empregado);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$resolvido            = pg_result($res,0,resolvido);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);

		$sql2 = "SELECT nome     ,
				email    ,
				fone_residencial 
			FROM tbl_empregado 
			JOIN tbl_pessoa USING(pessoa)
			WHERE empregado = $empregado";
		$res2 = @pg_exec ($con,$sql2);
		$nome  = pg_result($res2,0,0);
		$email = pg_result($res2,0,1);
		$fone  = pg_result($res2,0,2);
	}else{
		$msg_erro .="Chamado não encontrado";
	}
}else{
	$status="Novo";
	$login = $login_empregado_nome;
	$data = date("d/m/Y");
	$sql = "SELECT * FROM tbl_empregado JOIN tbl_pessoa USING(pessoa) WHERE empregado = $login_empregado";
	$resX = pg_exec ($con,$sql);

	$nome  = pg_result($resX,0,nome);
	$email = pg_result($resX,0,email);
	$fone  = pg_result($resX,0,fone_residencial);

}

$TITULO = "Lista de Chamadas - Telecontrol Hekp-Desk";
if($sistema_lingua == 'ES') $TITULO = "Lista de llamados - Telecontrol Hekp-Desk";
$ONLOAD = "frm_chamado.titulo.focus()";
if (strlen ($hd_chamado) > 0) $ONLOAD = "";
include "menu.php";
?>

<table width = '500' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>

<form name='frm_chamado' action='<? echo $PHP_SELF ?>' method='post' enctype='multipart/form-data' >
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>
<input type='hidden' name='status' value='<?= $status ?>'>

<?

echo $msg_erro;
?>

<tr>
	<td width="100" bgcolor="#CED8DE" class='HD' ><strong>&nbsp;Abertura </strong></td>
	<td bgcolor="#E5EAED" class='HD' colspan='3'><?= $data ?> </td>
	<td  bgcolor="#E5EAED" class='HD' rowspan='4'  align='center' valign='middle'><?if($sistema_lingua=='ES')echo "Llamado N°";else echo "Chamado N°";?><br><h1><?=$hd_chamado?></h1></td>
</tr>

<?
if (strlen ($hd_chamado) > 0) {
?>
<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Status </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;<?= $status ?> </td>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Analista </strong></td>
	<td             bgcolor="#E5EAED" class='HD'><strong>&nbsp;<?=$atendente_nome?> </strong></td>
</tr>
<? } ?>


<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" class='HD' colspan='3'>&nbsp;<input type='text' size='50' name='titulo' maxlength='50' value='<?= $titulo ?>' <? if (strlen ($hd_chamado) > 0) echo " readonly " ?> class='Caixa'> </td>
</tr>

<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;Categoria </strong></td>
	<td bgcolor="#E5EAED" class='HD' colspan='3'>
		<select name="categoria" size="1" <? if (strlen ($hd_chamado) > 0) echo ' disabled '?> class='Caixa'>
		<option value='Venda'      <? if($categoria=='Venda')      echo ' SELECTED '?> >Venda</option>
		<option value='Serviço'    <? if($categoria=='Serviço')    echo ' SELECTED '?> >Serviço</option>
		<option value='Locação'    <? if($categoria=='Melhoria')   echo ' SELECTED '?> >Locação</option>
		<option value='Satisfação' <? if($categoria=='Satisfação') echo ' SELECTED '?> >Satisfação</option>
		<option value='Promoção'   <? if($categoria=='Promoção')   echo ' SELECTED '?> >Promoção</option>
		<option value='Outros'     <? if($categoria=='Erro')       echo ' SELECTED '?> >Outros</option>
		</select>
	</td>
</tr>

<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Nombre";else echo "Nome";?> </strong></td>
	<td bgcolor="#E5EAED" class='HD' colspan='3'>&nbsp;<b><i><input type='text' size='30' maxlength='30'  name='nome' value='<?= $nome ?>' class='Caixa'>&nbsp;<?if($sistema_lingua == 'ES')echo "Nombre completo del usuario";else echo "Nome completo de usuário";?></b></i></td>
</tr>

<tr>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Correo";else echo "Email";?> </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;<input type='text' size='30' name='email' maxlength='50' value='<?= $email ?>' class='Caixa'></td>
	<td bgcolor="#CED8DE" class='HD'><strong>&nbsp;<?if($sistema_lingua=='ES')echo "Teléfono";else echo "Fone";?> </strong></td>
	<td bgcolor="#E5EAED" class='HD'>&nbsp;<input type='text' size='20' name='fone' value='<?=$fone ?>' class='Caixa'></td>
</tr>

</table>
<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
		tbl_hd_chamado_item.comentario                              ,
		tbl_hd_chamado_item.empregado 
	FROM tbl_hd_chamado_item 

	WHERE hd_chamado = $hd_chamado
	AND   interno IS NOT TRUE
	ORDER BY hd_chamado_item";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {

	echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0'>";

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>";
	if($sistema_lingua == 'ES') echo "Interacciones";
	else                        echo "Interações";
	echo "</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><font size='2'><strong>Nº </strong></font></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td nowrap><font size='2'><strong>";
	if($sistema_lingua == 'ES') echo "Fecha";
	else                        echo "Data";
	echo "</strong></font></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'></td>";
	echo "<td ><font size='2'><strong>";
	if($sistema_lingua == 'ES') echo "Comentario";
	else                        echo "Coment&aacute;rio";
	echo "</strong></font></td>";
	echo "<td ><img src='/assist/imagens/pixel.gif' width='10'><font size='2'><strong> Anexo </strong></font></td>";
	echo "<td nowrap><font size='2'><strong>Autor </strong></font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$item_comentario = pg_result($res,$i,comentario);
		$empregado       = pg_result($res,$i,empregado);

		$sql2 = "SELECT nome FROM tbl_empregado JOIN tbl_pessoa USING(pessoa) WHERE empregado = $empregado";
		$res2 = @pg_exec ($con,$sql2);
		$empregado_nome = pg_result($res2,0,0);
		//if($fabrica_autor==10) $autor="Suporte";
		$cor='#ffffff';
		if ($i % 2 == 0) $cor = '#F2F7FF';

		echo "<tr  class='Conteudo' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='20'>$x </td>";
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
			//echo "$filename\n\n";
				$po = strlen($hd_chamado_item);
				if(substr($filename, 0,$po)==$hd_chamado_item){
					echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=documentos/$filename target='blank'><img src='imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
				}
				
			}
		}
		echo "</td>";
		echo "<td nowrap > $empregado_nome</td>";
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
if (strlen ($hd_chamado) > 0 AND strlen($resolvido==0)) {
	if ($status == 'Resolvido') {
		echo "<b><font face='arial' color='#666666'>";
		if($sistema_lingua == "ES") echo "Este llamado esta resolvido";
		else                        echo "Este chamado está resolvido.";
		echo "</font></b><br>";
		echo "<b><font face='arial' color='#FF0000' size='-1'>";
		if($sistema_lingua == "ES") echo "Si no concordas con la solución, puede reabrirlo digitando una mensaje abajo";
		else                        echo "Se você não concordar com a solução, pode reabri-lo digitando uma mensagem abaixo.";
		echo "</font><br><font face='arial' color='#00CC00' size='-1'>";
		if($sistema_lingua == "ES") echo "Si concordas con la solución haga un click no botón RESOLVIDO";
		else                        echo "Se você concorda com a solução clique no botão RESOLVIDO";
		echo "</font></b><br>";
	}else{
		echo "<b><font face='arial' color='#666666'>";
		if($sistema_lingua == 'ES') echo "Digite el texto para continuar el llamado";
		else                        echo "Digite o texto para dar continuidade ao chamado";
		echo "</font></b><br>";
	}
}else{
	echo "<b><font face='arial' color='#666666'>";
	echo "Digite o texto do seu chamado";
	echo "</b></font><br>";
}

echo "<table width = '500' align = 'center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' class='HD' colspan='3' align='center'>";
echo "<textarea name='comentario' cols='70' rows='10' class='Caixa'wrap='VIRTUAL'";
if (strlen($resolvido > 0)){ echo "DISABLED";}
echo ">$comentario</textarea><br>";
echo "<script language=\"JavaScript1.2\">editor_generate('comentario');</script>";

echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' class='HD' colspan='3' align='center'>";
if($sistema_lingua == 'ES') echo "Archivo";
else                        echo "Arquivo ";
echo "<input type='file' name='arquivo' size='50' class='Caixa'";
if (strlen($resolvido > 0)){ echo "DISABLED";}
echo">";
echo "</td>";
echo "</tr>";
echo "</table>";
if($status=='Resolvido' AND  strlen($resolvido)==0){
	echo "<input type='submit' name='btn_resolvido' value='";
	if($sistema_lingua == "ES") echo "RESOLVIDO - Concordo com la solución";
	else                        echo "RESOLVIDO - CONCORDO COM A SOLUÇÃO...";
	echo "' class='btn' ><br>";
}
echo "<input type='submit' name='btn_acao' value='";
if($sistema_lingua == 'ES') echo "Enviar llamado";
else                        echo "Enviar Chamado";
echo "'";
if (strlen($resolvido > 0)){ echo "DISABLED";}
echo ">";
echo "</center>";
echo "</form>";

?>
		</td>
	</tr>
</table>

<? include "rodape.php" ?>
