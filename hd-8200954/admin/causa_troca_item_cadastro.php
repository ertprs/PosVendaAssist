<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";

include 'autentica_admin.php';

include 'funcoes.php';

$msg_debug = "";


$causa_troca = $_REQUEST["causa_troca"];
$causa_troca_item = $_REQUEST["causa_troca_item"];


$msg_sucesso = ( trim($_POST["msg_sucesso"]) ) ?  trim( $_POST["msg_sucesso"] ) : trim( $_GET["msg_sucesso"] )  ;

$btnacao = $_REQUEST["btnacao"];


if ($btnacao == "deletar" and strlen($causa_troca_item) > 0 and strlen($causa_troca) > 0) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_causa_troca_item
			WHERE  tbl_causa_troca_item.causa_troca_item     = $causa_troca_item
			AND    tbl_causa_troca.causa_troca = $causa_troca;";
	$res = @pg_exec ($con,$sql);

	$msg_erro = pg_errormessage($con);
	if ( strpos($msg_erro, 'ERROR: update or delete on table "tbl_causa_troca_item" violates foreign key constraint "tbl_os_troca_causa_troca_item_fkey" on table "tbl_os_troca" DETAIL: Key (causa_troca_item)=('.$causa_troca_item.') is still referenced from table "tbl_os_troca".' ) === false) {
		$msg_erro = 'Este registro já está sendo usado em outros locais do sistema e não pode ser apagado';
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg_sucesso=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$causa_troca        = $_POST["causa_troca"];
		$descricao_item     = $_POST["descricao_item"];
		$codigo_item        = $_POST["codigo_item"];
		$ativo_item         = $_POST["ativo_item"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {

if (empty($causa_troca) ){
	$msg_erro="Selecione uma Causa de Troca";
}
	
	$descricao_item = substr($_REQUEST["descricao_item"],0,100);
	$codigo_item = substr($_REQUEST["codigo_item"],0,5);
	$ativo_item = $_REQUEST["ativo_item"];
	
	if (empty($ativo_item)){
		$ativo_item = "f";
	}
	
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($causa_troca_item) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_causa_troca_item (
						codigo      ,
						causa_troca ,
						descricao   ,
						ativo
					) VALUES (
						'$codigo_item'   ,
						$causa_troca   ,
						'$descricao_item',
						'$ativo_item'
					);";
			
			
			
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			// if ( $msg_erro == 'ERROR: update or delete on table "tbl_causa_troca_item" violates foreign key constraint "tbl_os_troca_causa_troca_item_fkey" on table "tbl_os_troca" DETAIL: Key (causa_troca_item)=('.$causa_troca_item.') is still referenced from table "tbl_os_troca".' ){
				// $msg_erro = 'Este registro já está sendo usado em outros locais do sistema e não pode ser apagado';
			// }
			// $res = pg_exec ($con,"SELECT CURRVAL ('tbl_causa_troca_item_seq')");
			// $x_causa_troca  = pg_result ($res,0,0);

		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_causa_troca_item SET
					codigo     = '$codigo_item'   ,
					descricao  = '$descricao_item',
					ativo      = '$ativo_item'
			WHERE  tbl_causa_troca_item.causa_troca_item = $causa_troca_item
			AND    tbl_causa_troca_item.causa_troca = $causa_troca";

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if ( strpos($msg_erro, 'ERROR: update or delete on table "tbl_causa_troca_item" violates foreign key constraint "tbl_os_troca_causa_troca_item_fkey" on table "tbl_os_troca" DETAIL: Key (causa_troca_item)=('.$causa_troca_item.') is still referenced from table "tbl_os_troca".' ) === false) {
				$msg_erro = 'Este registro já está sendo usado em outros locais do sistema e não pode ser alterado';
			}
			$x_causa_troca = $causa_troca;

		}

	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg_sucesso=Gravado com Sucesso!");
		exit;
	}else{
		$causa_troca    = $_POST["causa_troca"];
		$ativo_item          = $_POST["ativo_item"];
		$codigo_item         = $_POST["codigo_item"];
		$descricao_item      = $_POST["descricao_item"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($causa_troca_item) > 0) {

	$sql = "SELECT  codigo   ,
					descricao,
					causa_troca,
					ativo
			FROM    tbl_causa_troca_item
			WHERE     causa_troca_item = $causa_troca_item";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$ativo_item     = trim(pg_result($res,0,'ativo'));
		$codigo_item    = trim(pg_result($res,0,'codigo'));
		$descricao_item = trim(pg_result($res,0,'descricao'));
		$causa_troca_id = trim(pg_result($res,0,'causa_troca'));
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "CADASTRO DE ITENS DE CAUSA DA TROCA DE PRODUTOS";
	include 'cabecalho.php';
?>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script language="JavaScript">

function mostraItem(linha){

		if ( $("#col_item_"+linha).css("display") == "none" ){
			
			// $("#col_item_"+linha).slideDown("slow");
			
			$("#col_item_"+linha).fadeIn();
			
		} else {
		
			$("#col_item_"+linha).fadeOut();
			
		}
	

}
</script>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial" !important;
color:#FFFFFF;
text-align:center;
}


.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<? if (strlen($msg_erro) > 0) { ?>

<table width='700px' align='center' cellpadding='0' cellspacing='0'>
	<tr>
		<td class='msg_erro' width='700px'><?=$msg_erro?></td>
	</tr>
</table>

<? } if (strlen($msg_sucesso) > 0) { ?>

<table align='center' width='700px' cellpadding='0' cellspacing='0'>
	<tr>
		<td class='msg_sucesso' width='700px'><?=$msg_sucesso ?></td>
	</tr>
</table>
<? } ?>
<form name="frm_causa_troca" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="causa_troca" value="<? echo $causa_troca ?>">
 
<table width='700px' cellpadding='0' cellspacing='0' align='center' class='formulario'>
	<tr>
		<td align='left' colspan='4' class='titulo_tabela'>Cadastro</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td align="center">
			<table width="600px" cellpadding="1" cellspacing="2" align="center" border="0">
				<tr>

					<td align='left'>Código</td>
					<td align='left'>Descrição</td>
				
				</tr>
				
				<tr>
				
					<td  align='left' nowrap width="25%">
						<input class='frm' type='text' name='codigo_item' value='<?echo $codigo_item ?>' style="width:50%" maxlength='20'>
					</td>
					
					<td align='left' nowrap width="75%">
						<input class='frm' type='text' name='descricao_item' value='<? echo $descricao_item ?>' style="width:100%" maxlength='100'>
					</td>
					
				</tr>
				
				<tr><td>&nbsp;</td></tr>
				
				
				
				<tr>
					<td align='left'>Causa da Troca</td>
				</tr>
				
				<tr>
					<td colspan="2" align='left'>
						
						<select name="causa_troca" id="causa_troca" class='frm'>
							<option value="">Selecione a Causa da Troca</option>
							
							<?php
							$sql_causa_troca = "SELECT  tbl_causa_troca.causa_troca,
														tbl_causa_troca.codigo,
														tbl_causa_troca.descricao
												FROM    tbl_causa_troca
												WHERE   tbl_causa_troca.fabrica = $login_fabrica 
												ORDER BY  tbl_causa_troca.codigo";
							$res_causa_troca = pg_query($con,$sql_causa_troca);

							if (pg_num_rows($res_causa_troca) > 0) {
								for ($i = 0; $i < pg_num_rows($res_causa_troca); $i++) {
									
									
									$causa_troca_ident = pg_fetch_result($res_causa_troca,$i,'causa_troca');
									
									$causa_troca_cod = pg_fetch_result($res_causa_troca,$i,'codigo');
									$causa_troca_desc = pg_fetch_result($res_causa_troca,$i,'descricao');
									
									$selected_causa = ($causa_troca_ident==$causa_troca_id) ? "selected" : null;
									
									echo "<option $selected_causa value='$causa_troca_ident'>$causa_troca_cod - $causa_troca_desc</option>\n";
								
								}
							}
							?>
							
						</select>
						
					</td>
				</tr>
				
				<tr><td>&nbsp;</td></tr>
				
				<tr>
					
					<td align='left'>
						Ativo &nbsp;&nbsp;  <input class='frm' type='checkbox' name='ativo_item' value='t' <? if($ativo_item == 't') echo "CHECKED" ?> >
					</td>
				
				</tr>
				
			</table>
			
		</td>

	</tr>
	
	<?
	if($login_fabrica==20){
		echo "<tr>";
		echo "<td></td><td align='left'>Descrição Espanhol(*)<BR><input type='text' name='descricao_es' value='$descricao_es' size='40' maxlength='50' class='frm'></td>";
		echo "</tr>";
	}
	?>


	<tr>
		<td colspan='4'>
		<br />
		
		<center>
			<input type='hidden' name='btnacao' value=''>
			<input type='button' value="Gravar" ONCLICK="javascript: if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='gravar' ; document.frm_causa_troca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" style='cursor:pointer;'>
			&nbsp;
			<input type='button' value="Apagar" ONCLICK="javascript: if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='deletar' ; document.frm_causa_troca.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" style='cursor:pointer;'>
			&nbsp;
			<input type='button' value="Limpar" ONCLICK="javascript: if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" style='cursor:pointer;'>
		</center>

		</td>
	</tr>
	
</table>
</form>

<br>



<?
if (strlen ($causa_troca) == 0) {

	$sql = "SELECT  tbl_causa_troca.causa_troca,
				tbl_causa_troca.codigo,
				tbl_causa_troca.descricao
			FROM    tbl_causa_troca
			WHERE   tbl_causa_troca.fabrica = $login_fabrica
			ORDER BY  tbl_causa_troca.codigo;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table  align='center' width='700px' cellpadding='2' cellspacing='1' class='tabela'> ";
		
		echo "<tr ><td colspan='3' class='titulo_tabela'>Causa de Troca de Produtos</td></tr>";
		echo "<tr class='titulo_coluna'><td nowrap><b>Código</b></td>";
		echo "<td nowrap>Descrição</td>";
		echo "<td>Ação</td>";
		echo "</tr>";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){

			$causa_troca  		  = trim(pg_result($res,$x,causa_troca));
			$descricao            = trim(pg_result($res,$x,descricao));
			$codigo               = trim(pg_result($res,$x,codigo));

			$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='formulario' style='background-color: $cor'>";
				
				echo "<td nowrap>$codigo</td>";
				echo "<td nowrap align='left'>$descricao</td>";
				#hd 311414
				if ( $login_fabrica == 6 )
				{
				echo "<td nowrap align='center'> <input type='button' value='Ver Itens' id='btn_ver_item_$x' onclick='mostraItem($x)' title='Clique para ver os Itens desta Causa'/> </td>";
				}
			
			echo "</tr>";
			
			#hd 311414
			
			if ( $login_fabrica == 6 )
			{
				echo "<tr >";
					
					echo "<td  colspan='3' align='center' style='display:none' id='col_item_$x' > ";
						
						echo "<div style='display:block'>";
							
							echo "<table class='tabela' cellpadding='1' cellspacing='1' style='width:95%' align='center'>";
								
								echo "<tr class='titulo_coluna'>
										<td colspan='3'> Relação de Itens da Causa de Troca N° $codigo</td>
									  </tr>";
								
								echo "<tr class='titulo_coluna'>";
									
									echo "<td style='width:100px'>  Cód. Item </td>";
									echo "<td> Descrição Item </td>";
									echo "<td style='width:100px'> Ativo </td>";
									
								echo"</tr>";
								
								$sql_item_causa_troca = "Select causa_troca_item,
																causa_troca,
																descricao,
																ativo,
																codigo
														FROM tbl_causa_troca_item 
														WHERE causa_troca = $causa_troca 
														ORDER BY codigo";
								// echo nl2br($sql_item_causa_troca);
								
								$res_item_causa_troca = pg_query($con,$sql_item_causa_troca);
								
								if ( pg_num_rows($res_item_causa_troca)>0 ){
									
									for ($i=0;$i < pg_num_rows($res_item_causa_troca); $i++ ){
										
										$item_id        = pg_fetch_result($res_item_causa_troca,$i,'causa_troca_item');
										$item_codigo    = pg_fetch_result($res_item_causa_troca,$i,'codigo');
										$item_descricao = pg_fetch_result($res_item_causa_troca,$i,'descricao');
										$item_ativo     = pg_fetch_result($res_item_causa_troca,$i,'ativo');
										$cor2 = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
										?>
										
										<tr style='background-color:<?=$cor2?>'>
											<td><a href='<?=$PHP_SELF?>?causa_troca_item=<?=$item_id?>'> <?echo $item_codigo?> </a></td>
											<td><a href='<?=$PHP_SELF?>?causa_troca_item=<?=$item_id?>'> <?echo $item_descricao?> </a></td>
											<td> <?echo ($item_ativo == 't') ? 'SIM' : 'NÃO' ;?> </td>
										</tr>
										
										<?php
										
									}
								}
								
							echo"</table>";
						
						echo "</div>";
					
					echo "</td>";
				
				echo"</tr>";
			}
		}
		echo "</table>";
	}
}

echo "<br>";

include "rodape.php";
?>
