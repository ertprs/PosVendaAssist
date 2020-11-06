<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

$layout_menu = "cadastro";
$title = "LISTAGEM DE PEÇAS POR TIPO DE INCIDÊNCIA";

$msg_erro = '';

include 'cabecalho.php';?>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}
	.Conteudo {
		text-align: left;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	.Caixa{
		BORDER-RIGHT: #6699CC 1px solid;
		BORDER-TOP: #6699CC 1px solid;
		FONT: 8pt Arial ;
		BORDER-LEFT: #6699CC 1px solid;
		BORDER-BOTTOM: #6699CC 1px solid;
		BACKGROUND-COLOR: #FFFFFF
	}
</style>
<?

if($btn_acao=="Consultar"){
	if((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final)>0 AND $data_final!="dd/mm/aaaa")){
		$ver_data = "Select case when '$data_inicial' < '$data_final' then true else false end";
		$res = @pg_exec($con,$ver_data);
		$resposta = pg_result($res,0,0);
		if ($resposta == 'f'){
			$msg_erro = "A DATA INICIAL NÃO PODE SER SUPERIOR A DATA FINAL";
		}
		if (strlen($msg_erro) == 0) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
			if (strlen($msg_erro) == 0)
				$aux_data_inicial = @pg_result ($fnc,0,0);
		}
		if (strlen($erro) == 0) {
			if (strlen($msg_erro) == 0) {
				$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
					if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}
				if (strlen($msg_erro) == 0)
					$aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}else{
		$msg_erro = "ENTRE COM O PERÍODO PARA FILTRAGEM";
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='490' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}?>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
	<br>
	<table width='700' class='Conteudo' style='background-color: #485989' border='0' cellpadding="0" cellspacing="0" align='center'>
		<tr>
			<td width='100%' class='Titulo' background='imagens_admin/azul.gif' colspan="2">
				<span style="font-size:13px">Listar peça por: </span>
			</td>
		</tr>
		<tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="3">
				<br />
					<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="devolucao_obrigatoria" <? if ($fleg == 'devolucao_obrigatoria') echo "checked"; ?>>
						<span>Devolução Obrigatória</span>
						</select>
					</div>
					<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="item_aparencia" <? if ($fleg == 'item_aparencia') echo "checked"; ?>>
						<span>Item de Aparência</span>
						</select>
					</div>
						<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="acumula_para_kit" <? if ($fleg == 'acumula_para_kit') echo "checked"; ?>>
						<span>Acumula para Kit</span>
						</select>
					</div>
					
				</td>
			<tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="3">
				<br />
					</div>
						<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="retorna_para_conserto" <? if ($fleg == 'retorna_para_conserto') echo "checked"; ?>>
						<span>Retorna para Conserto</span>
						</select>
					</div>
					<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="bloqueada_para_garantia" <? if ($fleg == 'bloqueada_para_garantia') echo "checked"; ?>>
						<span>Bloqueada para Garantia</span>
						</select>
					</div>
					<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="acess" <? if ($fleg == 'acess') echo "checked"; ?>>
						<span>Acess.</span>
						</select>
				</td>
			<tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="3">
				<br />
					</div>
						<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="PAGA" <? if ($fleg == 'aguarda_inspecao') echo "checked"; ?>>
						<span>Aguarda Inspeção</span>
						</select>
					</div>
					</div>
						<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="peca_critica" <? if ($fleg == 'peca_critica') echo "checked"; ?>>
						<span>Peça Crítica</span>
						</select>
					</div>
					<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="produto_acabado" <? if ($fleg == 'produto_acabado') echo "checked"; ?>>
						<span>Produto Acabado</span>
						</select>
					</div>
				</td>
			<tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="3">
				<br />
					</div>
						<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="mero_desgaste" <? if ($fleg == 'mero_desgaste') echo "checked"; ?>>
						<span>Mero Desgaste</span>
						</select>
					</div>
					</div>
						<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="ativo" <? if ($fleg == 'ativo') echo "checked"; ?>>
						<span>ATIVO</span>
						</select>
					</div>
					<div style="float:left; width:200px;padding-left:10px">
						<input type="radio" id="fleg" name="fleg" value="pre_selec" <? if ($fleg == 'pre_selec') echo "checked"; ?>>
						<span>Pre - Selec.</span>
						</select>
					</div>
				</td>
			<tr>
				
			<tr bgcolor="#D9E2EF">
				<td>
					<center>
						<br>
						<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.btn_acao.value='Listar'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
						<input type='hidden' name='btn_acao' value='<?=$acao?>'>
					</center>
				</td>
			</tr>
			<br />
	</table>

	<? if($btn_acao=="Listar" AND strlen($msg_erro) == 0){
		if ($_GET ['fleg'] > 0){
			$fleg = $_GET['fleg'];
		}else{
			$fleg = $_POST['fleg'];
		}
		switch ($fleg) {
			case 'devolucao_obrigatoria'     : $pesq = " AND devolucao_obrigatoria = 't' "; 
				break;
			case 'item_aparencia'            : $pesq = " AND item_aparencia = 't'"; 
				break;
			case 'acumula_para_kit'          : $pesq = " AND acumular_kit = 't'"; 
				break;
			case 'retorna_para_conserto'     : $pesq = " AND retorna_conserto = 't'"; 
				break;
			case 'bloqueada_para_garantia'   : $pesq = " AND bloqueada_garantia = 't'"; 
				break;
			case 'acess'                     : $pesq = " AND acessorio = 't'"; 
				break;
			case 'aguarda_inspecao'          : $pesq = " AND aguarda_inspecao = 't'"; 
				break;
			case 'peca_critica'              : $pesq = " AND peca_critica = 't'"; 
				break;
			case 'produto_acabado'           : $pesq = " AND produto_acabado = 't'"; 
				break;
			case 'mero_desgaste'             : $pesq = " AND mero_desgaste = 't'"; 
				break;
			case 'ativo'                     : $pesq = " AND ativo = 't'"; 
				break;
			case 'pre_selec'                 : $pesq = " AND pre_selecionada = 't'"; 
				break;
		}
		$sql = "
			SELECT DISTINCT
				peca                        AS peca,
				referencia                  AS referencia,
				descricao                   AS descricao,
				unidade                     AS unidade
			FROM tbl_peca
			WHERE fabrica = $login_fabrica
				$pesq
			ORDER BY referencia;";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			echo "<br>";?>
				<table align='center'>
					<tr bgcolor="#D9E2EF">
						<td>
							<center>
								<br>
									Clique aqui para fazer o 
									<a href="xls/peca_fleg-<?=$login_fabrica?>.xls">
										<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>
											download do arquivo em EXCEL
										</font>
									</a>
									<br>
									<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>
										Você pode ver, imprimir e salvar a tabela para consultas off-line.
									</font>;
							</center>
						</td>
					</tr>
				</table><?
			$fp = fopen ("xls/peca_fleg-$login_fabrica.xls","w");
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='400'>";
			fputs($fp,"<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='400'>");
				echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
				fputs($fp,"<tr>");
					// 1 coluna (Referência da PEÇA)
					echo "<td width='80'>Referência</td>";
					fputs($fp,"<td width='80'>Referência</td>");
					// 2 coluna (Descrição da PEÇA)
					echo "<td width='120'>Descrição</td>";
					fputs($fp,"<td width='120'>Descrição</td>");
				echo "</tr>";
				fputs($fp,"</tr>");
				for ($i=0; $i<pg_numrows($res); $i++){
					$peca          = @trim(pg_result($res,$i,peca));
					$referencia    = @trim(pg_result($res,$i,referencia));
					$descricao     = @trim(pg_result($res,$i,descricao));
					$unidade       = @trim(pg_result($res,$i,unidade));
					if($cor=="#F1F4FA")
						$cor = '#F7F5F0';
					else
						$cor = '#F1F4FA';
					echo "<tr class='Conteudo2'>";
					fputs($fp,"<tr class='Conteudo2'>");
						// 1 coluna (Referência da PEÇA)
						echo "<td width='80' nowrap>$referencia</td>";
					fputs($fp,"<td width='80'>$referencia</td>");
						// 2 coluna (Descrição da PEÇA)
						echo "<td width='120' nowrap>$descricao</td>";
					fputs($fp,"<td width='120'>$descricao</td>");
					echo "</tr>";
					fputs($fp,"</tr>");
				}
			echo "</table>";
			fputs ($fp,"</table>");
			fclose($fp);
		}else{
			echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
		}
	}?>
</form>
<?include 'rodape.php';?>
