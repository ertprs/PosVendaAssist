<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";
?>

<style type="text/css">


.combo{ 

width:160px; /* largura da janela do menu */

background-color:#ffffff; /* cor do fundo do menu em repouso */

font:10px  Verdana, Geneva, Arial, Helvetica, sans-serif; /* tamanho e tipo das fontes */

color: #3B3B3B; /* cor da fonte */ 

} 
#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
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
</style>

<?
$layout_menu = "gerencia";
$title = "CONSULTA DE OS PROCON";
include "cabecalho.php";



if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


if (strlen($_POST['btn_acao']) > 0 or strlen($_POST['btn_continue'])>0) {
    $linha                = trim (strtoupper ($_POST['linha']));
    $familia                = trim (strtoupper ($_POST['familia']));
	$marca                  = trim (strtoupper ($_POST['marca']));
	$mes                    = trim (strtoupper ($_POST['mes']));
	$ano                    = trim (strtoupper ($_POST['ano']));
	$estado                 = trim (strtoupper ($_POST['estado']));
	$consumidor_nome        = trim(strtoupper($_POST['consumidor_nome']));
	$consumidor_fone        = trim($_POST['consumidor_fone']);
	$consumidor_endereco    = trim(strtoupper($_POST['consumidor_endereco']));
	//HD 13923
	if($login_fabrica==11){
		if(strlen($_POST['btn_acao'])>0){
			$ano= date('Y');
			$mes=date('m');
		}elseif(strlen($_POST['btn_continue'])>0){
			$mes=$_POST['mes'];
			$ano=$_POST['ano'];
			if($mes=='1'){
				$mes='12';
				$ano=$ano-1;
			}else{
				$mes=$mes-1;
			}
		}
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

		if (strlen($consumidor_nome) >0 and strlen ($consumidor_nome) < 9 ) {
			$msg_erro = "Especifique no mínimo 10 caracteres para o Nome do Consumidor";
		}
		if ($consumidor_nome == 'MARIA' ) {
			$msg_erro = "Por favor! Coloque mais alguma coisa para pesquisar o nome de Maria, ex. Maria do Carmo!";
		}
		if (strlen ($consumidor_endereco) >0 and strlen ($consumidor_endereco) < 9 ) {
			$msg_erro = "Especifique no mínimo 10 caracteres para o Endereço do Consumidor";
		}
		if(strlen ($consumidor_endereco) ==0 and strlen ($consumidor_nome) ==0 and strlen ($consumidor_fone) ==0){
			$msg_erro = "Preenche pelo menos um campo para consulta.";		
		}
	}else{
		if (strlen ($consumidor_nome) < 2 ) {
			$msg_erro = "Especifique o no mínimo 3 caracteres para o Nome do Consumidor";
		}

		elseif (strlen($estado) ==0) {
			$msg_erro = "Especifique o Estado";
		}

		elseif (strlen ($mes) == 0) {
			$msg_erro = "Selecione o Mês";
		}
		elseif (strlen ($ano) == 0) {
			$msg_erro = "Selecione o Ano";
		}	
		elseif (strlen($mes) > 0) {
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		}
	}
// hd 13923
	if($login_fabrica==11){
		$sql_consulta="";
		if(strlen($consumidor_nome)>0){
			$sql_consulta.= " AND tbl_os.consumidor_nome LIKE '$consumidor_nome%'";
		}
		if (strlen ($consumidor_endereco) >0 ){
			$sql_consulta.= " AND tbl_os.consumidor_endereco LIKE '$consumidor_endereco%'";
		}
		if(strlen($consumidor_fone)>0){
			$sql_consulta.= " AND tbl_os.consumidor_fone = '$consumidor_fone'";
		}
	}else{
        if(strlen($linha)>0){
            $sql_linha = " AND    tbl_produto.linha = $linha ";
        }else{
            $sql_linha = " ";
        }
		if(strlen($familia)>0){
			$sql_familia = " AND    tbl_produto.familia = $familia ";
		}else{
			$sql_familia = " ";
		}

		$cond_marca = "";
		if(strlen($marca)){
            $cond_marca = " AND tbl_produto.marca = $marca";
		}
		$sql_consulta .=" AND    tbl_os.consumidor_nome LIKE '$consumidor_nome%'
						  AND    tbl_posto_fabrica.contato_estado = '$estado'";
	}

	
}
?>


<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center'>
<table class='formulario' width='700' cellspacing='0'  cellpadding='0' align='center'>
	<? if(strlen($msg_erro)>0){ ?>
		<tr class="msg_erro"><td><? echo $msg_erro; ?></td></tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td >Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td >
			<TABLE width="100%" border="0" cellspacing="2" cellpadding="3" CLASS='formulario' >
				<TR width='100%'  >
					<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
					<td colspan='2'  align='right'>Nome do Consumidor:&nbsp;</td>
					<td colspan='2'  align='left'><INPUT TYPE="text" NAME="consumidor_nome" value="<?=$consumidor_nome;?>" size='24' class='frm'> <img src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'></td>
				</tr>
				<?if($login_fabrica==11){?>
				<TR width='100%'  >
					<td colspan='2'  align='right'>Telefone do Consumidor:&nbsp;</td>
					<td colspan='2'  align='left'><INPUT TYPE="text" NAME="consumidor_fone" value="<?=$consumidor_fone;?>" size='24' class='frm'></td>
				</tr>
				<TR width='100%'  >
					<td colspan='2'  align='right'>Endereço do Consumidor:&nbsp;</td>
					<td colspan='2'  align='left'><INPUT TYPE="text" NAME="consumidor_endereco" value="<?=$consumidor_endereco;?>" size='24' class='frm'></td>
				</tr>
<?
                }else{
                    if($login_fabrica == 1){
?>
                <tr width='100%'  >
                    <TD colspan = '2'   align='right'>Por Linha:&nbsp;</TD>
                    <td colspan = '2'>
                        <select name='linha' size='1' class='frm'>
                            <option value=''>&nbsp;</option>
<?
        $sql = "SELECT
                            linha,
                            nome
                    FROM tbl_linha
                    WHERE tbl_linha.fabrica = $login_fabrica
                    ORDER BY tbl_linha.nome ";
        $res_linha = pg_query($con, $sql);

        if (pg_num_rows($res_linha) > 0) {
            for ($j = 0 ; $j < pg_num_rows($res_linha) ; $j++){
                $aux_linha    = trim(pg_fetch_result($res_linha,$j,linha));
                $aux_descricao  = trim(pg_fetch_result($res_linha,$j,nome));
?>
                                <option value = "<?=$aux_linha?>" <?=($linha == $aux_linha) ? " SELECTED " : ""?>><?=$aux_descricao?></option>
<?
            }
        }
?>
                        </select>
                    </td>
                </tr>
                <tr width='100%'  >
                    <TD colspan = '2'   align='right'>Por Marca:&nbsp;</TD>
                    <td colspan = '2'>
                        <select name='marca' size='1' class='frm'>
                            <option value=''>&nbsp;</option>
<?
                        $sqlMarca = "
                            SELECT  marca,
                                    nome
                            FROM    tbl_marca
                            WHERE   fabrica = $login_fabrica;
                        ";
                        $resMarca = pg_query($con,$sqlMarca);
                        $marcas = pg_fetch_all($resMarca);

                        foreach($marcas as $chave => $valor){
?>
                            <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_POST['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
                        }

?>
                        </select>
                    </td>
                </tr>
<?
                    }
?>
				<TR width='100%'  >
					<TD colspan = '2'   align='right'>Por Família:&nbsp;</TD>
					<td colspan = '2'>
						<?
						$sql =	"SELECT *
								FROM tbl_familia
								WHERE fabrica = $login_fabrica
								ORDER BY descricao;";
						$res = pg_exec($con,$sql);
						if (pg_numrows($res) > 0) {
							echo "<select name='familia' size='1' class='frm'>";
							echo "<option value=''></option>";
							for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
								$aux_familia = trim(pg_result($res,$i,familia));
								$aux_nome  = trim(pg_result($res,$i,descricao));
								echo "<option value='$aux_linha'";
								if ($familia == $aux_familia) echo " selected";
								echo ">$aux_nome</option>";
							}
							echo "</select>";
						}
						?>
						</center>
					</td>
				</tr>
				<TR width = '100%' >
					<TD colspan = '2' align='right'>Por Região:&nbsp;</TD>
					<td colspan = '2'>
						<select name="estado" size="1" width='160' class='frm'>
							<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
				<!-- 			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>UF</option> -->
							<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</td>
				</TR>

				<tr class="Conteudo" >
					<td align='right' colspan='2'>Mês:&nbsp;</td>
					<td colspan='2'>
						<select name="mes" size="1" class='frm'>
						<option value=''></option>
						<?
						for ($i = 1 ; $i <= count($meses) ; $i++) {
							echo "<option value='$i'";
							if ($mes == $i) echo " selected";
							echo ">" . $meses[$i] . "</option>";
						}
						?>
						</select>
					</td>
					<tr class='Conteudo'>
					<td colspan='2' align='right'>Ano:&nbsp;</td>
					<td colspan='2'>
						<select name="ano" size="1" class='frm'>
						<option value=''></option>
						<?
						for ($i = 2003 ; $i <= date("Y") ; $i++) {
							echo "<option value='$i'";
							if ($ano == $i) echo " selected";
							echo ">$i</option>";
						}
						?>
						</select>
					</td>
				</tr>
				<? } ?>
				<tr >
					<TD colspan="4" style="text-align: center;">
						<br><input type="submit" name="btn_acao" value="Pesquisar" <?if(strlen($mes)>0 and $login_fabrica==11 and strlen($msg_erro)==0) echo "disabled";?>>
					</TD>
				</tr>
				<?if(strlen($mes) >0 and $login_fabrica==11 and strlen($msg_erro)==0){?>
				<tr >
					<TD colspan="4" style="text-align: center;">
						<input type="hidden" name ="mes" value="<? echo $mes;?>">
						<input type="hidden" name="ano" value="<? echo $ano;?>">
						<br><input type="submit" name="btn_continue" value="Continuar">
					</TD>
				</tr>
				<tr class="Conteudo" >
					<TD colspan="4" style="text-align: center;">
					<a href='os_consulta_procon_teste.php?'><img src='imagens_admin/btn_nova_busca.gif'></a>
					</td>
				</tr>
				<?}?>
			</table>
		</td>
	</tr>
</table>
</form>
<br />
<?
	if (strlen($_POST['btn_acao']) > 0 or strlen($_POST['btn_continue'])>0) {
		if(strlen($msg_erro)==0){
			$sql = "SELECT 		tbl_os.os                                                         ,
								tbl_os.sua_os                                                     ,
								TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
								TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
								TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
								TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
								tbl_posto.nome                               AS posto_nome        ,
								tbl_posto.fone                                                    ,
								tbl_os.consumidor_nome                                            ,
								tbl_posto_fabrica.codigo_posto                                    ,
								tbl_produto.referencia                                            ,
								tbl_produto.descricao                        AS produto_descricao 
					FROM tbl_os
					JOIN tbl_posto   ON tbl_os.posto   = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					$cond_marca
					JOIN tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE  tbl_os.fabrica = $login_fabrica
					AND    tbl_os.excluida IS NOT TRUE
					AND    tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
                    $sql_consulta
                    $sql_linha
					$sql_familia
					";
			//if($ip == '201.76.71.206')
			// Samuel retirou a obrigatoriedade da familia porque estava dando este erro:Warning: pg_exec() [function.pg-exec]: Query failed: ERROR: tuple offset out of range: 0 in /var/www/assist/www/admin/os_consulta_procon.php on line 183
			//O Tulio precisa fazer um vacuo no banco.
// echo nl2br($sql);
			$res = @pg_exec($con,"/* query ->$sql */");
			$res = @pg_exec($con,$sql);
			$samuel        = pg_errormessage($con);
			if(strlen($samuel)>0){
				echo "<FONT SIZE='2' COLOR='#CC0000'>O sistema ficou impossibilitado de fazer o filtro, faça a consulta retirando a família!</FONT>";
			}else{
				if (pg_numrows($res) > 0) {
					echo "<table border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center' width='700'>";
					echo "<tr class='titulo_coluna' height='15'>";
					echo "<td>OS</td>";
					echo "<td>AB</td>";
					echo "<td>FC</td>";
					echo "<td>Posto</td>";
					echo "<td>Consumidor</td>";
					echo "<td>Produto</td>";
					echo "<td >Ações</td>";
					echo "</tr>";
					for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

						$os                 = trim(pg_result($res,$i,os));
						$sua_os             = trim(pg_result($res,$i,sua_os));
						$abertura           = trim(pg_result($res,$i,abertura));
						$fechamento         = trim(pg_result($res,$i,fechamento));
						$finalizada         = trim(pg_result($res,$i,finalizada));
						$digitacao          = trim(pg_result($res,$i,digitacao));
						$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
						$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
						$posto_nome         = trim(pg_result($res,$i,posto_nome));
						$produto_referencia = trim(pg_result($res,$i,referencia));
						$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
						
						if ($i % 2 == 0) {
							$cor   = "#F1F4FA";
							$botao = "azul";
						}else{
							$cor   = "#F7F5F0";
							$botao = "amarelo";
						}

						echo "<tr  bgcolor='$cor' align='left'>";
						echo "<td nowrap>" . $sua_os . "</td>";
						echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
						if ($login_fabrica == 1) $aux_fechamento = $finalizada;
						else                     $aux_fechamento = $fechamento;
						echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
						echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
						echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
						$produto = $produto_referencia . " - " . $produto_descricao;
						echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
						echo "<td width='60' align='center'>";
						echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
						echo "</td>\n";
						echo "</tr>";
					}
					echo "</table>";
				}elseif($login_fabrica==11 and pg_numrows($res)==0 ){
					echo "<FONT SIZE='2' COLOR='#CC0000'>Nenhum resultado encontrado no Mês $mes, se desejar pesquisar o mês anterior, clique no botão Continuar.</FONT>";
				}
				else
					echo "<center>Nenhum Resultado Encontrado</center>";
			}
		}
	}
?>
<? include "rodape.php" ?>
