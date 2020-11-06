<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';


$layout_menu = 'auditoria';
$title = strtoupper('Postos Bloqueados no crédito');

include 'cabecalho.php';


if($_POST["btnacao"] == "Gravar"){

	$select_acao	= $_POST['select_acao'];
	$postos 		= $_POST['posto'];
	$motivo 		= $_POST['motivo'];
	$pedido_faturado = 't';

	foreach($postos as $linha){
		$sql = "insert into tbl_posto_bloqueio (posto, desbloqueio, admin, pedido_faturado, fabrica, observacao) 
				values ($linha, '$select_acao', $login_admin, '$pedido_faturado', $login_fabrica, '$motivo')";
		$res = pg_query($con, $sql);
	}
}

if($_POST["btnacao"] == "Pesquisar"){

	$posto_codigo = $_POST['posto_codigo'];
	$posto_nome = $_POST['posto_nome'];

	if(strlen(trim($posto_codigo))>0){
		$complemento_sql .= " and tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
	}

	if(strlen(trim($posto_nome))>0){
		$complemento_sql .= " and tbl_posto.nome = '$posto_nome'";
	}
}


?>

<style type="text/css">

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<script src="js/jquery-1.3.2.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script language="JavaScript">

$(document).ready(function(){
	Shadowbox.init();
});

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_posto_bloqueio;
	if (!ok) {

		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;

				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;

				cont++;
			}
		}
	}
}

function pesquisaPosto(campo,tipo){
    var campo = campo.value;

    if (jQuery.trim(campo).length > 2){
        Shadowbox.open({
            content:	"posto_pesquisa_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
            player:	    "iframe",
            title:		"Pesquisa Posto",
            width:	    800,
            height:	    500
        });
    }else
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function retorna_posto(posto,codigo_posto,nome,cnpj,pais,cidade,estado,nome_fantasia){
    gravaDados('posto_nome',nome);
    gravaDados('posto_codigo',codigo_posto);
}


function gravaDados(name, valor){
        try {
                $("input[name="+name+"]").val(valor);
        } catch(err){
                return false;
        }
}

function expandir(posto){
   	var elemento = document.getElementById('tr_' + posto);
   	var display = elemento.style.display;

   	if (display == "none") {
		elemento.style.display = "";
	} else {
		elemento.style.display = "none";
   	}

}


</script>

<?


echo "<div class='msg_erro' id='erro' style='display:none;width:700px;margin:auto;'></div>";


if(strlen($msg) > 0){
	echo "<div class='sucesso' style='width:700px;margin:auto;'>$msg</div>";
}


?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario espaco'>

<TBODY>
<tr class="titulo_tabela"><th colspan="3">Parâmetros de Pesquisa</th></tr>
<tr><td colspan='3'>&nbsp;</td></tr>
<TR>
	<td width='150'>&nbsp;</td>
	<TD>
		Código Posto<br />
		<input type='text' name='posto_codigo' size='12' value='<?=$posto_codigo?>' class='frm' />&nbsp;
		<img src='../imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick="javascript: pesquisaPosto (document.frm_pesquisa.posto_codigo, 'codigo'); " />
	</TD>
	<TD>
		Nome Posto<br />
		<input type='text' name='posto_nome' size='30' value='<?=$posto_nome?>' class='frm' />&nbsp;
		<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_pesquisa.posto_nome, 'nome'); " style='cursor: pointer;' />
	</TD>
</TR>

</tbody>
<tr><td colspan='3'>&nbsp;</td></tr>
<TR>
	<TD colspan="3" style="padding-left:0px;" align="center">
		<input type='hidden' name='btn_acao' value=''>
		<input type="submit" name='btnacao' style="cursor:pointer;" value="Pesquisar" />
	</TD>
</TR>
<tr><td colspan='3'>&nbsp;</td></tr>
</table>
</form>

<?php

echo "<table align='center' width='700' class='tabela'>
	<form name='frm_posto_bloqueio' method='POST' action=''>
	<tr class='titulo_coluna'>
			<th><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: pointer;' align='center'></th>
			<th nowrap>Código Posto</th>
			<th>Nome Posto</th>
			<th>Status</th>
		 </tr>";

//bloqueados
	$sql = "
			select distinct posto, array(select distinct desbloqueio from tbl_posto_bloqueio e where e.posto = p.posto and pedido_faturado) as stat into temp tmp_posto_$login_admin
			from tbl_posto_bloqueio p
			where fabrica = $login_fabrica
			and pedido_faturado ;

			select distinct tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto,
		 (select case when desbloqueio = 'f' and tbl_posto_bloqueio.pedido_faturado then 'Bloqueado' when desbloqueio = 't' and admin is null and tbl_posto_bloqueio.pedido_faturado then 'Desbloqueio Automatico' when admin notnull and tbl_posto_bloqueio.pedido_faturado then 'Desbloqueado Admin' else '' end from tbl_posto_bloqueio where tbl_posto_bloqueio.posto = tbl_posto.posto and fabrica = $login_fabrica and pedido_faturado order by data_input desc limit 1) as status,
		  (select admin from tbl_posto_bloqueio where tbl_posto_bloqueio.posto = tbl_posto.posto and fabrica = $login_fabrica order by data_input desc limit 1) as admin
		from tbl_posto
		INNER JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		INNER join tbl_posto_bloqueio on tbl_posto_bloqueio.posto = tbl_posto_fabrica.posto and tbl_posto_bloqueio.fabrica = $login_fabrica
		WHERE tbl_posto_fabrica.fabrica = $login_fabrica
		and tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
		and tbl_posto_bloqueio.pedido_faturado
		and tbl_posto_fabrica.categoria !~* 'cadastro'
		and tbl_posto.pais='BR'
		and tbl_posto.posto not in (select posto from tmp_posto_$login_admin  where stat[2] isnull and stat[1] ) 
		$complemento_sql
		ORDER BY status";

		//and tbl_posto_bloqueio.desbloqueio = false
		$res = pg_query($con, $sql);

		if(pg_num_rows($res)>0){
		 	for($i=0; $i<pg_num_rows($res); $i++){
		 		$posto 		= pg_fetch_result($res, $i, "posto");
		 		$admin 		= pg_fetch_result($res, $i, "admin");
		 		$nome 		= pg_fetch_result($res, $i, "nome");
		 		$codigo 	= pg_fetch_result($res, $i, "codigo_posto");
		 		$status 	= pg_fetch_result($res, $i, "status");

					echo "<tr id='posto_$posto'>";
		 			echo "<td><input type='checkbox' name='posto[]' value='$posto'></td>";
		 			echo "<td>$codigo</td>";
		 			echo "<td><a href='javascript:void(0);' onclick='javascript: expandir($posto)'>$nome</a></td>";
		 			echo "<td>$status</td>";
		 		echo "</tr>";

				echo "<tr style='display: none' id='tr_$posto'>
					<td colspan='4'>
		 				<table width=700>";
		 				echo "<tr class='titulo_coluna'>";
		 					echo "<td>Observação</td>";
		 					echo "<td>Admin</td>";
		 					echo "<td>Data</td>";
		 					echo "<td>Ação</td>";
		 				echo "</tr>";
				$sql_bloqueios = "select observacao, desbloqueio, tbl_admin.nome_completo as admin, tbl_posto_bloqueio.data_input from tbl_posto_bloqueio
					left join tbl_admin on tbl_admin.admin = tbl_posto_bloqueio.admin and tbl_admin.fabrica = $login_fabrica
					where posto = $posto and tbl_posto_bloqueio.fabrica = $login_fabrica and pedido_faturado is true order by tbl_posto_bloqueio.data_input desc ";
				$res_bloqueios = pg_query($con, $sql_bloqueios);
				for($a=0; $a<pg_num_rows($res_bloqueios); $a++){
		 			$admin 			= pg_fetch_result($res_bloqueios, $a, admin);
		 			$status 	= pg_fetch_result($res_bloqueios, $a, desbloqueio);
		 			$data_input 	= mostra_data(pg_fetch_result($res_bloqueios, $a, data_input));
					$observacao 	= pg_fetch_result($res_bloqueios, $a, observacao);

						if($status == "t"){
				 			if(strlen(trim($admin))==0){
								$status = "Desbloqueado Automático";
				 			}else{
				 				$status = "Desbloqueado Admin";
				 			}
				 		}elseif($status == "f"){
				 			$status = "Bloqueado";
				 		}

		 			echo "<tr>";
		 				echo "<td>$observacao</td>";
		 				echo "<td>$admin</td>";
		 				echo "<td>$data_input</td>";
		 				echo "<td>$status</td>";
		 			echo "</tr>";
		 		}
		 		echo "</table>
		 			</td>
		 		</tr>";
		 	}
		 }

 //Nunca teve bloqueio

		$sql = "select
					tbl_posto_fabrica.posto,
					tbl_posto.nome,
					tbl_posto_fabrica.posto,
					tbl_posto_fabrica.codigo_posto
				from tbl_posto_fabrica
				inner join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
				join tmp_posto_$login_admin ON tbl_posto.posto = tmp_posto_$login_admin.posto
				where tbl_posto_fabrica.fabrica = $login_fabrica
				and tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				and tbl_posto.pais='BR'
				and stat[2] isnull and stat[1]
				and tbl_posto_fabrica.categoria !~* 'cadastro'
				$complemento_sql
				";
		$res = pg_query($con, $sql);
    	if(pg_num_rows($res)>0){
		 	for($i=0; $i<pg_num_rows($res); $i++){
		 		$posto 		= pg_fetch_result($res, $i, "posto");
		 		$admin 		= pg_fetch_result($res, $i, "admin");
		 		$nome 		= pg_fetch_result($res, $i, "nome");
		 		$codigo 	= pg_fetch_result($res, $i, "codigo_posto");

		 		$status 	= "Nunca Bloqueado";

		 		echo "<tr>";
		 			echo "<td><input type='checkbox' name='posto[]' value='$posto'></td>";
		 			echo "<td>$codigo</td>";
		 			echo "<td><a href='javascript:void(0);' onclick='javascript: expandir($posto)'>$nome</a></td>";
		 			echo "<td>$status</td>";
		 		echo "</tr>";
		 	}
		 }

		 echo "<tr class='titulo_tabela'><td colspan='4' align='left'>";
			echo "AÇÃO :
					<select name='select_acao' class='frm'>";
						echo "<option value='f'>BLOQUEAR</option>";
						echo "<option value='t'>DESBLOQUEAR</option>";
				echo "</select>";
				echo "MOTIVO: <input type='text' name='motivo' size='25' class='frm'>";

			echo "<input type=submit name='btnacao' style='cursor:pointer;' value='Gravar' />";
			echo "</td></tr>";
			echo "</table>";

			echo "</form>";

		 echo "</table>";

include "rodape.php" ?>
