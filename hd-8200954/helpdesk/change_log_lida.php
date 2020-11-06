<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btn_acao    = $_GET['btn_acao'];
if(strlen($btn_acao)==0)$btn_acao    = $_POST['btn_acao'];
$titulo      = $_GET['titulo'];
if(strlen($titulo)==0)$titulo    = $_POST['titulo'];
$hd_chamado= $_GET['hd_chamado'];
if(strlen($titulo)==0)$hd_chamado    = $_POST['hd_chamado'];

$conteudo    = $_GET['conteudo'];
if(strlen($conteudo)==0)$conteudo    = $_POST['conteudo'];


if(strlen(is_numeric($hd_chamado)) >0){
	$busca1=" AND tbl_change_log.hd_chamado=$hd_chamado ";
}
if(strlen($titulo) >0){
	$busca2=" AND tbl_change_log.titulo ilike '%$titulo%' ";
}

if(strlen($conteudo) >0){
	if($login_fabrica <> 10){
		$busca3=" AND tbl_change_log.change_log_fabrica ilike '%$conteudo%' ";
	}else{
		$busca3=" AND tbl_change_log.change_log_interno ilike '%$conteudo%' ";
	}
}
?>

<? 
$TITULO = "Change Logs Lidos";
include "menu.php"; 
?>
<style>

div.exibe{
	padding:8px;
	color:  #555555;
	display:none;
}
</style>
<script>
function MostraEsconde(dados,imagem){
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style3.style.display = "";
			img.src='../imagens/mais.gif';
		}else{
			style2.style.display = "block";
			img.src='../imagens/menos.gif';
		}
	}
}
</script>
<?
	echo "<FORM METHOD='post' ACTION='$PHP_SELF'>";

	echo "<table width = '450' align = 'center' cellpadding='0' cellspacing='0' border='0' style='font-family: verdana ; font-size:11px ; color: #666666'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' >";
	echo "	<td><b><CENTER>Pesquisa Change Log</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td>";

	echo "<table border='0'  cellpadding='2' cellspacing='3' width='400' align='center' style='font-family: verdana ; font-size:11px ; color: #00000'>";

	echo "<tr >";
	echo "<td width='150'>Número de Chamado</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='26' maxlength='26' name='hd_chamado' value='$hd_chamado'> ";
	echo "</tr>";
	echo "<tr >";
	echo "<td width='150'>Titulo</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='26' name='titulo' id='titulo' value='$titulo'> ";
	echo "</tr>";
	echo "<tr >";
	echo "<td width='150'>Conteúdo</td>";
	echo "<td width='250'>";
	echo "<input type='text' size='26' name='conteudo' value='$conteudo'> ";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td colspan='2' align='center'> <INPUT TYPE=\"submit\" value=\"Pesquisar\" name='btn_acao'>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table><BR><BR>";
	echo "</form>";
	
if(strlen($btn_acao) >0) {
		$sql="SELECT change_log                                                ,
					 hd_chamado                                                ,
					 titulo                                                    ,
					 tbl_fabrica.nome                                          ,
					 tipo                                                      ,
					change_log_interno                                         ,
					change_log_fabrica                                         ,
					 to_char(tbl_change_log_admin.data,'DD/MM/YYYY HH24:MI') as data 
			FROM	tbl_change_log
			JOIN    tbl_change_log_admin USING(change_log)
			LEFT jOIN tbl_fabrica ON tbl_fabrica.fabrica=tbl_change_log.fabrica
			WHERE   tbl_change_log_admin.admin=$login_admin 
			$busca1
			$busca2
			$busca3
			ORDER BY tbl_change_log.fabrica   ,
					 tbl_change_log.tipo      ,
					 tbl_change_log.change_log ";

		$res=pg_exec($con,$sql);
	?>


	<?
	if(pg_numrows($res) >0){
		for($i=0;$i<pg_numrows($res);$i++){

			$hd_chamado          = trim(pg_result($res,$i,hd_chamado));
			$titulo              = trim(pg_result($res,$i,titulo));
			$nome                = trim(pg_result($res,$i,nome));
			$change_log          = trim(pg_result($res,$i,change_log));
			$change_log_interno  = trim(pg_result($res,$i,change_log_interno));
			$change_log_fabrica  = trim(pg_result($res,$i,change_log_fabrica));
			$tipo                = trim(pg_result($res,$i,tipo));
			$data                = trim(pg_result($res,$i,data));
			
			if($login_fabrica <>10){
				$link_chamado="chamado_detalhe.php?hd_chamado=";
				$change_log_conteudo=$change_log_fabrica;
			}else{
				$link_chamado="adm_chamado_detalhe.php?hd_chamado=";
				$change_log_conteudo=$change_log_interno;
			}
			
			echo "	<table width = '720' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
			echo "<tr>";
			echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>";
			echo "<a href =\"javascript:MostraEsconde('dados_$i','visualizar_$i')\"><img src='../imagens/mais.gif' id='visualizar_$i'>";
			echo "&nbsp;$titulo</td>";
			echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;HD CHAMADO </strong></td>";
			echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<a href='$link_chamado$hd_chamado' target='_blank'>$hd_chamado</a></td>";
			echo "<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;DATA </strong></td>";
			echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;$data</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td bgcolor='#FFFFFF' style='border-style: double; border-color: #6699CC; ' colspan='100%'><DIV class='exibe' id='dados_$i' >&nbsp;$change_log_conteudo</div></td>";
			echo "</tr>";
			echo "</table><BR>";
			
		}
	}else{
		$msg_erro = "Nenhum resultado encontrado.";
	}
}
?>

<? include "rodape.php" ?>