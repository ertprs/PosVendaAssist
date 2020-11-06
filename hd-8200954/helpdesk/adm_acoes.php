<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "menu.php";

$hd_chamado = $_GET['hd_chamado'];

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
					tbl_hd_chamado.hora_desenvolvimento                  ,
					to_char (tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI') AS previsao_termino,
					to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM/YYYY HH24:MI') AS previsao_termino_interna,
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
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$duracao              = pg_result($res,0,duracao);
		$atendente            = pg_result($res,0,atendente);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$nome                 = pg_result($res,0,nome_completo);
		$email                = pg_result($res,0,email);
		$prioridade           = pg_result($res,0,prioridade);
		$fone             = pg_result($res,0,fone);
		$nome_completo        = pg_result($res,0,nome_completo);
		$fabrica_nome         = pg_result($res,0,fabrica_nome);
		$login                = pg_result($res,0,login);
		$prazo_horas          = pg_result($res,0,prazo_horas);
		$previsao_termino     = pg_result($res,0,previsao_termino);
		$previsao_termino_interna= pg_result($res,0,previsao_termino_interna);
		$hora_desenvolvimento = pg_result($res,0,hora_desenvolvimento);
	}else{
		$msg_erro="Chamado não encontrado";
	}


?>

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});


 $(document).ready(function(){
   $(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   $(".relatorio tr:even").addClass("alt");
   });
   
</script>
<style>
.resolvido{
	background: #259826;
	color: #FCFCFC;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}
.interno{
	background: #FFE0B0;
	color: #000000;
	float: left;
	clear: none;
	padding: 2px 2px 2px 2px;
	width: 100%;
	font-style: normal;
	font-variant: normal;
	font-weight: bold;
	text-align: center;
	}


	table.relatorio {
		border-collapse: collapse;
		width: 750px;
		font-size: 1.1em;
		border-left: 1px solid #8BA4EB;
		border-right: 1px solid #8BA4EB;
	}

	table.relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 2px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		padding-top: 5px;
		padding-bottom: 5px;
	}

	table.relatorio td {
		padding: 1px 5px 5px 5px;
		border-bottom: 1px solid #95bce2;
		line-height: 15px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}

	table.relatorio tr.over td {
		background: #bcd4ec;
	}
	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}
	
	</style>




<table width = '750' align = 'center' border='0' cellpadding='2' cellspacing='2' style='font-family: arial ; font-size: 12px'>

<form name='frm_chamada' action='<? echo $PHP_SELF ?>' method='post' enctype="multipart/form-data">
<input type='hidden' name='hd_chamado' value='<?= $hd_chamado?>'>

<?
if (strlen ($hd_chamado) > 0) {
/*	echo "<tr>";
	echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado nº. $hd_chamado </strong></td>";
	echo "</tr>";*/
}
?>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px;'><strong>Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $titulo ?> </td>
	
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Abertura </strong></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'><?= $data ?> </td>

</tr>
<tr>
	<td  bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px '><strong>Solicitante </strong></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $login ?> </td>
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Chamado </strong></td>
	<td    bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'><strong><font  color='#FF0033' size='4'><?=$hd_chamado?></font></strong></td>
	<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Nome </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $nome ?></td>
	<td width="60" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Fábrica </strong></td>
	<td  bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'><?= $fabrica_nome ?> </td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $email ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'  align='center'><?= $fone ?></td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Atendente </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'><?= $atendente_nome ?></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Status </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'  align='center'><?= $status ?></td>
</tr>



</table>

<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
		to_char((tbl_hd_chamado_item.TERMINO -tbl_hd_chamado_item.DATA), 'HH24:MI') AS tempo_trabalho,
		tbl_hd_chamado_item.comentario                               ,
		tbl_hd_chamado_item.interno                                  ,
		tbl_admin.nome_completo                            AS autor  ,
		(select to_char(sum(termino - data),'HH24:MI') from tbl_hd_chamado_item where hd_chamado_item = tbl_hd_chamado_item.hd_chamado_item) as a,
		tbl_hd_chamado_item.status_item
		FROM tbl_hd_chamado_item 
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<BR><BR><table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio' style='font-family: arial; font-size:11px'>";
echo "<thead>";
	echo "<tr  bgcolor='#D9E8FF'>";
	echo "<th><strong>Nº</strong></th>";
	echo "<th  nowrap><strong>Data</strong></th>";
	echo "<th  nowrap><strong>Tmp Trab.</strong></th>";
	echo "<th><strong>  Comentário </strong></th>";
	echo "<th  ><strong> Anexo </strong></th>";
	echo "<th nowrap ><strong>Autor </strong></th>";
	echo "</tr>";
	echo "</thead>";
echo "<tbody>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);
		$status_item     = pg_result($res,$i,status_item);
		$interno         = pg_result($res,$i,interno);
		$tempo_trabalho  = pg_result($res,$i,tempo_trabalho);
		
		$autor = explode(" ",$autor);
		$autor = $autor[0];

		echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
		echo "<td nowrap width='25'>$x </td>";
		echo "<td nowrap width='50'>$data_interacao </td>";
		echo "<td nowrap width='40'>$tempo_trabalho</td>";
		echo "<td  width='520'>";
		if ($status_item == 'Resolvido'){

			echo "<span class='resolvido'><b>Chamado foi resolvido nesta interação</b></span>";

		}
		if($interno == 't'){
			echo "<span class='interno'><b>Chamado interno</b></span>";

		}
		echo "<font size='1'>" . nl2br(str_replace($filtro,"", $item_comentario)) . "</td>";
		echo "<td width='25'>";
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
		echo "<td nowrap width='50'>$autor</td>";
		echo "</tr>";


	}	
	echo "</tbody>";
	echo "</table>";
}
?>



<table width = '250' align = 'center'  cellpadding='2' cellspacing='1' border='0' style='font-family: arial; font-size:11px'>
	<caption>
	<strong>Painel de Controle</strong>
	</caption>
	<tr>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center' colspan='2'>
	<h1><? $hd_chamado?></h1>

	</td>
	</tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Status </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align = 'center' >
			<select name="status" size="1"  style='width: 150px;'>
			<!--<option value=''></option>-->
			<option value='Novo'      <? if($status=='Novo')      echo ' SELECTED '?> >Novo</option>
			<option value='Análise'   <? if($status=='Análise')   echo ' SELECTED '?> >Análise</option>
			<option value='Execução'  <? if($status=='Execução')  echo ' SELECTED '?> >Execução</option>
			<option value='Aguard.Execução'  <? if($status=='Aguard.Execução')  echo ' SELECTED '?> >Aguard.Execução</option>
			<option value='Aprovação' <? if($status=='Aprovação') echo ' SELECTED '?> >Aprovação</option>
			<option value='Cancelado' <? if($status=='Cancelado') echo ' SELECTED '?> >Cancelado</option>
			<option value='Resolvido' <? if($status=='Resolvido') echo ' SELECTED '?> >Resolvido</option>
			</select>
		</td>
		</tr>
	<tr>
	<td bgcolor='#CED8DE' style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>Atendente</strong></td>
	<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px'  align='center' >
	<?
	$sql = "SELECT  *
			FROM    tbl_admin
			WHERE   tbl_admin.fabrica = 10
			and ativo is true
			ORDER BY tbl_admin.nome_completo;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<select class='frm' style='width: 150px;' name='transfere'>\n";
		echo "<option value=''>- ESCOLHA -</option>\n";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_admin = trim(pg_result($res,$x,admin));
			$aux_nome_completo  = trim(pg_result($res,$x,nome_completo));

			echo "<option value='$aux_admin'"; if ($atendente == $aux_admin) echo " SELECTED "; echo "> $aux_nome_completo</option>\n";
		}
		
		echo "</select>\n";

	}
	?>
	</td>
	</tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Categoria </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<select name="categoria" size="1"  style='width: 150px;'>
			<option></option>
			<option value='Ajax' <? if($categoria=='Ajax') echo ' SELECTED '?> >Ajax, JavaScript</option>
			<option value='Design' <? if($categoria=='Design') echo ' SELECTED '?> >Design</option>
			<option value='Implantação' <? if($categoria=='Implantação') echo ' SELECTED '?> >Implantação</option>
			<option value='Integração' <? if($categoria=='Integração') echo ' SELECTED '?> >Integração (ODBC, Perl)</option>
			<option value='Linux' <? if($categoria=='Linux') echo ' SELECTED '?> >Linux, Hardware, Data-Center</option>
			<option value='Novos' <? if($categoria=='Novos') echo ' SELECTED '?> >Novos Projetos</option>
			<option value='SQL' <? if($categoria=='SQL') echo ' SELECTED '?> >Otimização de SQL e Views</option>
			<option value='PHP' <? if($categoria=='PHP') echo ' SELECTED '?> >PHP</option>
			<option value='PL' <? if($categoria=='PL') echo ' SELECTED '?> >PL/PgSQL, functions e triggers</option>
			<option value='Postgres' <? if($categoria=='Postgres') echo ' SELECTED '?> >Postgres</option>
			<option value='Suporte Telefone' <? if($categoria=='Suporte Telefone') echo ' SELECTED '?> >Suporte Telefone</option>

			</select>
		</td>
	</tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Tipo </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
			<select name="tipo_chamado" size="1"  style='width: 150px;'>
	<?
	$sql = "SELECT	tipo_chamado,
						descricao 
				FROM tbl_tipo_chamado 
				ORDER BY descricao;";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
				for($i=0;pg_numrows($res)>$i;$i++){
					$xtipo_chamado = pg_result($res,$i,tipo_chamado);
					$xdescricao    = pg_result($res,$i,descricao);
					echo "<option value='$xtipo_chamado' ";	
					if($tipo_chamado == $xtipo_chamado){echo " SELECTED ";}
					echo " >$xdescricao</option>";
				}

		}
	?>
	</select>
	</td>
	</tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Prazo </strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<input type='text' size='2' maxlength ='5' name='prazo' value='<?= $prazo ?>' class='caixa'> Hr.
		
		</td>
	</tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Cobrar ?</strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<input type='checkbox' name='cobrar' value='t'> Sim
		
		</td>
	</tr>
	<tr>
		<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>Arquivo:</strong></td>
		<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px' align='center'>
		<input name='programa' id='programa'value='' class='caixa' size='25' onKeyUp = 'recuperardados(<? $hd_chamado?>)' onblur='this.value=""'><br>
		</td>
	</tr>
	<tr><td></td>
	<td><div id='conteudo' class='Chamados2' style='position: absolute;opacity:.80;'>Digite no mínimo <br>4 caracteres</div></td>
	</tr>


	</TABLE>
