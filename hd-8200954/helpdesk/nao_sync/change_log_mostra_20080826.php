<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$confirmo_li=$_GET['confirmo_li'];
$admin=$_GET['admin'];
if(strlen($confirmo_li) >0 AND strlen($admin) >0){
	$sql="SELECT change_log FROM tbl_change_log_admin where admin=$admin and change_log=$confirmo_li	";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) ==0){
		$sql2="INSERT INTO tbl_change_log_admin(
					change_log    ,
					admin         ,
					data          
				)values(
					$confirmo_li  ,
					$admin        ,
					current_timestamp
				)";
		$res2=@pg_exec($con,$sql2);
		$msg_erro=pg_errormessage($con);
	}else{
		$msg_erro="Você já confirmou que leu este Change Log.";
	}
}

$tipo=$_GET['tipo'];
if(strlen($tipo) >0){
	if($tipo =='importante') {
		$cond_tipo ="AND tbl_change_log.tipo='Importante'";
	}elseif($tipo=='necessario'){
		$cond_tipo ="AND tbl_change_log.tipo='Necessário'";
	}else{
		$cond_tipo ="AND tbl_change_log.tipo='Telas'";
	}

	$sql="SELECT tbl_change_log.change_log,
				 hd_chamado               ,
				 titulo                   ,
				 tbl_fabrica.nome         ,
				 change_log_interno       ,
				 change_log_fabrica       ,
				 tipo               ,
				 to_char(tbl_change_log.data,'DD/MM/YYYY HH24:MI') as data
		FROM	tbl_change_log
		LEFT JOIN tbl_fabrica ON tbl_fabrica.fabrica=tbl_change_log.fabrica
		LEFT JOIN tbl_change_log_admin On tbl_change_log.change_log=tbl_change_log_admin.change_log AND tbl_change_log_admin.admin = $login_admin
		WHERE tbl_change_log_admin.data IS NULL 
		$cond_tipo";
	if($login_fabrica <> 10){
		$sql.=" AND (tbl_change_log.fabrica=$login_fabrica OR tbl_change_log.fabrica IS NULL)
				AND length(change_log_fabrica) >0";
	}
	
	$sql.="
	ORDER BY tbl_change_log.fabrica,tbl_change_log.tipo,tbl_change_log.change_log ";

	$res=pg_exec($con,$sql);

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
			

			$resposta .="<table width = '720' align = 'center' border='0' cellpadding='2'    id='relatorio' >";
			$resposta .="<thead>";
			$resposta .="<tr>";
			$resposta .="<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px' >&nbsp;";
			$resposta .="<a href =\"javascript:MostraEsconde('dados_$tipo-$i','dados2_$tipo-$i','visualizar_$i')\"><img src='../imagens/mais.gif' id='visualizar_$i'>";
			$resposta .="$titulo</td>";
			$resposta .="<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px' nowrap><strong>&nbsp;HD CHAMADO </strong></td>";
			$resposta .="<td class='conteudo' >&nbsp;<a href='$link_chamado$hd_chamado' target='_blank'>$hd_chamado</a></td>";
			$resposta .="<td class='conteudo2'><strong>&nbsp;DATA </strong></td>";
			$resposta .="<td class='conteudo' nowrap>&nbsp;$data</td>";
			$resposta .="</tr>";
			$resposta .="</thead>";
			$resposta .="<tbody>";
			$resposta .="<tr>";
			$resposta .="<td bgcolor='#FFFFFF' style='border-style: double;'  colspan='3'>";
			$resposta .="<DIV class='exibe' id='dados_$tipo-$i' >&nbsp;$change_log_conteudo</div></td>";
			$resposta .="<td bgcolor='#FFFFFF' style='border-style: double; font:#FF0000;' colspan='2' align='center'>";
			$resposta .="<DIV class='exibe' id='dados2_$tipo-$i' ><a href='$PHP_SELF?confirmo_li=$change_log&admin=$login_admin'>Já Li e Confirmo</a></div></td>";
			$resposta .="</tr>";
			$resposta .="</tbody>";
			$resposta .="</table>";
		}
	}else{
		$resposta.="Não há nenhum CHANGE LOG pendente para ser lido!";
	}
		echo "ok|$resposta";
		flush();
		exit;
}
?>
<style>
div.exibe{
	padding:8px;
	color:  #555555;
	display:none;
}

.conteudo{
	background:#E5EAED; 
	border-style: solid;
	border-color: #6699CC;
	border-width: 1px;
	font-size: 15px;
}

.conteudo2{
	background:#CED8DE; 
	border-style: solid;
	border-color: #6699CC;
	border-width: 1px;
	font-size: 15px;
}
.tipo{
	font-size: 20px;
	letter-spacing: 6px;
}

</style>
<script type="text/javascript" src="js/ajax_busca.js"></script>
<? include "javascript_calendario.php"; ?>

<script>

function retornaTipo (http , componente ) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com = document.getElementById(componente);
					com.innerHTML   = results[1];
				}else{
					alert ('Erro ao abrir Change Log' );
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function MostraTipo (tipo,dados) {
	url = "<?= $PHP_SELF ?>?tipo="+escape(tipo) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaTipo (http ,dados) ; } ;
	http.send(null);
}
function MostraChange(dados,tipo){
	if (document.getElementById)
	{
		var change  = document.getElementById(dados);
		var type    = document.getElementById(tipo);
			if (change.style.display){
				change.style.display = "";
				type.src='../imagens/mais.gif';
			}else{
				change.style.display = "block";
				type.src='../imagens/menos.gif';
				MostraTipo(tipo,dados);

		}
	}
}

function MostraEsconde(dados,dados2,imagem){
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		var style3 = document.getElementById(dados2);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			style3.style.display = "";
			img.src='../imagens/mais.gif';

		}else{
			style2.style.display = "block";
			style3.style.display = "block";
			img.src='../imagens/menos.gif';
		}
	}
}
</script>

<? 
$TITULO = "Change Logs";
include "menu.php"; 
?>

<table width = '800' align = 'center' border='0' cellpadding='1'>
<caption align='center'>Escolha o tipo de Change Log que quer visualizar.</caption>
<thead align='center' >
<tr class='tipo'><td><img src='../imagens/mais.gif' id='importante' onclick="javascript:MostraChange('change_log_importante','importante');" >Importante</td></tr>
<tr><td><DIV class='exibe' id='change_log_importante'></td></tr>
<tr class='tipo'><td><img src='../imagens/mais.gif' id='necessario' onclick="javascript:MostraChange('change_log_necessario','necessario');" >Necessário</td></tr>
<tr><td><DIV class='exibe' id='change_log_necessario'></td></tr>
<tr class='tipo'><td><img src='../imagens/mais.gif' id='telas' onclick="javascript:MostraChange('change_log_telas','telas');" >Telas</td></tr>
<tr><td><DIV class='exibe' id='change_log_telas'></td></tr>
</thead>
</table>
<?



?>


<? include "rodape.php" ?>