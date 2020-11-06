<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";
$titulo = "RELATÓRIO DE PEÇAS DO POSTO";
$title = "RELATÓRIO DE PEÇAS DO POSTO";
include 'cabecalho.php';
include "javascript_pesquisas.php"; 

?>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">

	$().ready(function(){
	
		Shadowbox.init();
		
	});
	
//PESQUISA POSTO - 

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
		
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,nome,credenciamento){
		gravaDados('codigo_posto',codigo_posto);
		gravaDados('posto_nome',nome);
	}
	
	

//PESQUISA PECA
	function pesquisaPeca(peca,tipo){

		if (jQuery.trim(peca.value).length > 2){
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?"+tipo+"="+peca.value,
				player:	"iframe",
				title:		"Peça",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			peca.focus();
		}

	}
	
	function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo){
		gravaDados('referencia',referencia);
		gravaDados('descricao',descricao);
	}


	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}
</script>

<style type='text/css'>
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px 'Arial';
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px 'Arial';
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px 'Arial';
	color:#FFFFFF;
	text-align:center;
	margin: 0 auto;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.msg_sucesso{
	background-color: green;
	font: bold 16px 'Arial';
	color: #FFFFFF;
	text-align:center;
	margin: 0 auto;
}
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
</style>
<center>
<?
$btn_acao = $_POST['btn_acao'];

#Caso esteja vazio, pega o valor de l que irá por GET da ThickBox
if (strlen($btn_acao)>0){

	if (strlen($codigo_posto)>0){	
		
		$sql = "SELECT tbl_posto_fabrica.posto,tbl_posto.nome
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING(posto)
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$posto = pg_fetch_result($res,0,'posto');
			$posto_nome = pg_fetch_result($res,0,'nome');
		}else{
			$msg_erro = "Posto não encontrado";
		}
	
	}

	if (strlen($referencia)>0){	
		$sql = "SELECT peca,descricao
				FROM tbl_peca
				WHERE tbl_peca.fabrica= $login_fabrica
				and tbl_peca.referencia='$referencia'
				AND tbl_peca.ativo = 't'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$peca = pg_fetch_result($res,0,'peca');
			$descricao = pg_fetch_result($res,0,'descricao');
		}else{
			$msg_erro = "Peça não encontrada";
		}

	}

}?>

<form name='frm_consulta' method='post' action='<?=$PHP_SELF;?>'>

<table cellspacing='1' cellpadding='3' align='center' width='700px' class='formulario'>
	<tr>
		<td class="msg_erro" id="msg_erro" style="display:none;"><?=$msg_erro?></td>
	</tr>
</table>

<table cellspacing='1' cellpadding='3' align='center' width='700px' class='formulario'>
	<tr>
		<td colspan='3' class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td width='23%'>&nbsp;</td>
		<td width='180px'>
			Código Posto 
			<br />
			<input type='text' name='codigo_posto' size='8' value='<?=$codigo_posto;?>' class='frm'>
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.codigo_posto, 'codigo')">
		</td>
		<td>
			Nome Posto <br />
			<input type='text' name='posto_nome' size='30' value='<?=$posto_nome;?>' class='frm'>

			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>
	<tr>
		<td width='10%'>&nbsp;</td>
		<td style='padding:10px 0 0 0;'>
			Referência<br />
			<input class='frm' type='text' name='referencia' value='<?=$referencia;?>' size='8' maxlength='20'>
			<a href="javascript: pesquisaPeca(document.frm_consulta.referencia,'referencia')">
			<IMG SRC='imagens/lupa.png' style='cursor : pointer'></a>
		</td>

		<td style='padding:10px 0 0 0;'>
			Descrição <br />
			<input class='frm' type='text' name='descricao' value='<?=$descricao;?>' size='30' maxlength='50'>
			<a href="javascript: pesquisaPeca(document.frm_consulta.descricao,'descricao')">
			<IMG SRC='imagens/lupa.png' style='cursor : pointer'></a>
		</td>
	</tr>
	<tr>
		<td width='10%'>&nbsp;</td>
		<td colspan='2' style='padding:10px 0 0 0;'>
			<input type='checkbox' name='devolucao_obrigatoria' id='devolucao_obrigatoria' value='true' onclick='verificaPecas(this.id)'"; <?if ($devolucao_obrigatoria) echo " checked ";?>> 
			Peças de devolução obrigatória
		</td>
	</tr>

	<tr>
		<td width='10%'>&nbsp;</td>
		<td colspan='2' style='padding:10px 0 0 0;'>
		<input type='checkbox' name='pecas_plasticas' id='pecas_plasticas' value='true' onclick='verificaPecas(this.id)'"; 		<?if ($pecas_plasticas) echo " checked ";?>> 
		Peças plásticas
		</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan='3' align='center'><input type='submit' name='btn_acao' value='Pesquisar'>
	</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
</table>
</form>

<?

$conds = (strlen($peca)>0) ? " AND tbl_estoque_posto.peca = $peca " : "";

if(!$_POST['devolucao_obrigatoria'] || !$_POST['pecas_plasticas']){

	if($_POST['codigo_posto']){
		
		$codigo_posto = $_POST['codigo_posto'];
		
		$sql = "SELECT tbl_posto_fabrica.posto,tbl_posto.nome
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING(posto)
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$posto = pg_fetch_result($res,0,'posto');
			$posto_nome = pg_fetch_result($res,0,'nome');
			
			$conds .= " AND tbl_estoque_posto.posto = $posto";
		}else{
			$msg_erro = "Posto não encontrado";
		}

	}
	
	if ($_POST['referencia'] and strlen($msg_erro)==0){	
		
		$referencia = $_POST['referencia'];
		
		$sql = "SELECT peca,descricao
				FROM tbl_peca
				WHERE tbl_peca.fabrica= $login_fabrica
				and tbl_peca.referencia='$referencia'
				AND tbl_peca.ativo = 't'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res)>0){
			$peca = pg_fetch_result($res,0,'peca');
			$descricao = pg_fetch_result($res,0,'descricao');
			
			$conds.=" AND tbl_estoque_posto.peca = $peca ";
		}else{
			$msg_erro = "Peça não encontrada";
		}

	}

	if($_POST['devolucao_obrigatoria'] == 'true'){
		$conds .= " AND tbl_peca.devolucao_obrigatoria IS TRUE ";
	}

	if($_POST['pecas_plasticas'] == 'true'){
		$conds .= " AND tbl_peca.devolucao_obrigatoria IS FALSE ";
	}

}

if (strlen($msg_erro) == 0) {

	$sql = "SELECT 	tbl_posto.cnpj			,
					tbl_posto.nome			,
					tbl_peca.referencia		,
					tbl_peca.peca			,
					tbl_peca.descricao      ,
					tbl_estoque_posto.qtde	,
					tbl_estoque_posto.estoque_devolucao,   
					tbl_estoque_posto.estoque_minimo,
					tbl_estoque_posto.consumo_mensal 
			FROM tbl_estoque_posto
			JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca 
			JOIN tbl_posto ON tbl_estoque_posto.posto = tbl_posto.posto 
			WHERE  tbl_estoque_posto.fabrica = $login_fabrica
			$conds
			ORDER BY tbl_posto.cnpj";

	$res = pg_query ($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		
		$data = date ("d/m/Y H:i:s");
		$total = pg_num_rows ($res);

		$fp = fopen ("xls/relatorio-posto-movimento-$login_fabrica.xls","w+");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE ESTOQUE DO POSTO: $codigo_posto - $data - $msg");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<TABLE width='700' align='center' border='1' cellspacing='0' cellpadding='1'>\n");
		fputs ($fp, "<tr  align='center'>\n");
		fputs ($fp, "<td colspan='7' bgcolor='#0000FF'>
						<FONT  COLOR='#FFFFFF'>
							<b>RELATÓRIO DE ESTOQUE DO POSTO $codigo_posto - $data - $msg</b>
						</FONT>
					</td>\n"
		);
		fputs ($fp, "</tr>\n");

		fputs ($fp,"<TR class='menu_top'>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>CNPJ POSTO</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>POSTO</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>CODIGO PECA</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>DESCRICAO PECA</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>ESTOQUE ATUAL</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>ESTOQUE DEVOLUCAO</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>ESTOQUE MINIMO</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>CONSUMO MENSAL</TD>\n");
		fputs ($fp,"</TR>\n");
		?>

		<br />
		<br />

		<table class='tabela' width="1000px" cellspacing="1" cellpadding="3" align='center'>
			<thead>
				<tr class='titulo_coluna'>
					<td>CNPJ Posto</td>
					<td>Posto</td>
					<td>Peça</td>
					<td>Descrição</td>
					<td>Peças Estoque</td>
					<td>Peças Devolução</td>
					<td>Estoque de Segurança</td>
					<?if ($login_fabrica == 15) : ?>
					<td>Consumo Mensal</td>
					<?endif;?>
				</tr>
			</thead>
			<tbody><?php
			
			$cnpj_anterior = '';

			for ($x = 0; pg_num_rows($res) > $x; $x++) {

				$cnpj            	= pg_fetch_result($res,$x,'cnpj');
				$nome 				= pg_fetch_result($res,$x,'nome');
				$peca            	= pg_fetch_result($res,$x,'peca');
				$peca_referencia 	= pg_fetch_result($res,$x,'referencia');
				$peca_descricao  	= pg_fetch_result($res,$x,'descricao');
				$qtde 				= pg_fetch_result($res,$x,'qtde');
				$estoque_minimo 	= pg_fetch_result($res,$x,'estoque_minimo');
				$consumo_mensal 	= pg_fetch_result($res,$x,'consumo_mensal');
				$estoque_devolucao 	= pg_fetch_result($res,$x,'estoque_devolucao');
				
				#Faz essa linha para separar os estoques peças de postos diferentes
				if($cnpj_anterior && ($cnpj_anterior != $cnpj)){
					fputs ($fp,"<tr><td colspan='7'>&nbsp;</td></tr>\n");
					echo "<tr><td colspan='7'>&nbsp;</td></tr>\n";
				}
				
				$cnpj_anterior = $cnpj;
				
				$estoque_devolucao 	= strlen($estoque_devolucao) ? $estoque_devolucao : '-';
				$qtde 				= strlen($qtde) ? $qtde : '-';
				$estoque_minimo 	= strlen($estoque_minimo) ? $estoque_minimo : '-';

				fputs ($fp,"<TR>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$cnpj</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$nome</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$peca_referencia</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$peca_descricao</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$qtde</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$estoque_devolucao</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$estoque_minimo</TD>\n");
				fputs ($fp,"	<TD  bgcolor='#FFCC00'>$consumo_mensal</TD>\n");
				fputs ($fp,"</TR>\n");
				
				$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
				?>

				<tr bgcolor='<? echo $cor;?>'>
					<td><?=$cnpj;?></td>
					<td><?=$nome;?></td>
					<td><?=$peca_referencia;?></td>
					<td><?=$peca_descricao;?></td>
					<td align='center'> <?php echo $qtde; ?></td>
					<td align='center'> <?php echo $estoque_devolucao; ?></td>
					<td align="center"> <?php echo $estoque_minimo; ?></td>
					<td align="center"> <?php echo $consumo_mensal; ?></td>
				</tr>
				
			<?}?>

			</tbody>
		</table>
		
		<?
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);
	
		echo "<div align='center'>";
			echo "<input type='button' value='Download em Excel' onclick='window.location.href=\"xls/relatorio-posto-movimento-$login_fabrica.xls\"' >";
		echo "</div>";

	} else { ?>
		<br />
		<div>Nenhum resultado Encontrado</div>
	<?}

} else {?>
	<script type="text/javascript">
		$("#msg_erro").appendTo("#mensagem").fadeIn("slow");
	</script><?php
}

?>
</center>
<?
include "rodape.php";
?>
