<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../helpdesk/mlg_funciones.php';

$layout_menu = "callcenter";
$title       = "RELATÓRIO DE CHAMADOS DO CALL-CENTER";

include "cabecalho.php";

$ArrayEstados = Array('AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO');

if ($_POST["btn_pesquisa"] == "Pesquisar")
{
	$produto           = $_POST["produto"];
	$proudo_referecia  = $_POST["produto_referencia"];
	$produto_descricao = $_POST["produto_descricao"];
	$posto             = $_POST["posto"];
	$posto_codigo      = $_POST["posto_codigo"];
	$posto_nome        = $_POST["posto_nome"];
	$data_inicial      = $_POST["data_inicial"];
	$data_final        = $_POST["data_final"];
	$estado            = $_POST["estado"];
	$cidade 		   = $_POST["cidade"];
	$bairro 		   = $_POST["bairro"];
	$atendente         = $_POST["atendente"];
	$status            = $_POST["status"];
	$linha             = $_POST["linha"];


	if(strlen(trim($bairro))>0 and strlen(trim($cidade)) ==0 ){
		$msg_erro .= "Por favor informe a cidade. <br>";
	}

	if(empty($data_inicial) or empty($data_final))
	{
		$msg_erro .= "Selecione uma Data";
	}

	 if(strlen($msg_erro)==0)
	{
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi))
			$msg_erro .= "Data Inválida";
	}
    
	if(strlen($msg_erro)==0)
	{
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf))
			$msg_erro .= "Data Inválida";
	}

	if(strlen($msg_erro)==0)
	{
		$aux_data_inicial .= "$yi-$mi-$di";
		$aux_data_final .= "$yf-$mf-$df";
	}

	if(strlen($msg_erro)==0)
	{
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
        or strtotime($aux_data_final) > strtotime('today'))
		{
			$msg_erro .= "Data Inválida.";
		}
	}
        
	if(strlen($msg_erro)==0)
	{
		if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final))
		{
			$msg_erro .= 'O intervalo entre as datas não pode ser maior que 3 meses';
		}
	}

	if (empty($msg_erro))
	{
		if($login_fabrica == 94){
			if(strlen(trim($cidade))>0){
				$where .= " AND tbl_cidade.nome = UPPER(fn_retira_especiais(TRIM('$cidade'))) ";
			}

			if(strlen(trim($bairro))>0 and strlen(trim($cidade))>0){	
				$where .= " AND tbl_hd_chamado_extra.bairro = UPPER(fn_retira_especiais(TRIM('$bairro'))) ";
			}
		}

		if (strlen($atendente) > 0)
		{
			$sqlx = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo = TRUE AND admin = $atendente";
			$resx = pg_query($con, $sqlx);

			if (pg_num_rows($resx) == 0)
			{
				$msg_erro .= "Atendente Inválido";
			}
			else
			{
				$where .= " AND atendente = $atendente ";
			}
		}

		if (strlen($estado) > 0)
		{
			if (!in_array($estado, $ArrayEstados))
			{
				$msg_erro .= "Estado Inválido";
			}
			else
			{
				$where .= " AND tbl_cidade.estado = '$estado' ";
			}
		}

		if (strlen($produto) > 0 and strlen($produto_referencia) > 0 and strlen($produto_descricao) > 0)
		{
			$sqlx = "SELECT produto FROM tbl_produto JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha WHERE tbl_linha.fabrica = $login_fabrica AND produto = $produto";
				$resx = pg_query($con, $sqlx);

			if (pg_num_rows($resx) == 0)
			{
				$msg_erro .= "Produto Inválido";
			}
			else
			{
				$where .= " AND tbl_hd_chamado_extra.produto = $produto ";
			}
		}

		if (strlen($posto) > 0 and strlen($posto_codigo) > 0 and strlen($posto_nome) > 0)
		{
			$sqlx = "SELECT tbl_posto.posto FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto WHERE tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto.posto = $posto";
			$resx = pg_query($con, $sqlx);

			if (pg_num_rows($resx) == 0)
			{
				$msg_erro .= "Posto Inválido";
			}
			else
			{
				$where .= " AND tbl_hd_chamado_extra.posto = $posto ";
			}
		}

		if ($status <> "")
		{
			$where .= " AND tbl_hd_chamado.status = '$status' ";
		}

		if ($linha <> "")
		{
			$where .= " AND tbl_produto.linha = $linha ";
		}
	}
}


if($_GET['buscaCidade']){
    $uf = $_GET['estado'];

    $sql = "SELECT UPPER(fn_retira_especiais(TRIM(cidade))) as cidade from tbl_ibge where estado = UPPER('$uf') ORDER BY cidade";
    $res = pg_query($con,$sql);
    if(pg_numrows($res) > 0){
    	$retorno = "<option value=''></option>";
        for($i = 0; $i < pg_numrows($res); $i++){
            $cidade = pg_result($res,$i,'cidade');

            $retorno .= "<option value='$cidade'>$cidade</option>";
        }
    } else {
        $retorno .= "<option value=''>Cidade não encontrada</option>";
    }

    echo $retorno;
    exit;
}

function nome_atendente($param1, $hd_chamado = false)
{
	global $con;
	global $login_fabrica;

	if ($hd_chamado == false)
	{
		$sql = "SELECT nome_completo, login FROM tbl_admin WHERE admin = $param1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0)
		{
			$nome_completo = pg_result($res, 0, "nome_completo");
			$login         = pg_result($res, 0, "login");

			if (strlen($nome_completo) > 0)
			{
				return $nome_completo;
			}
			else
			{
				return $login;
			}
		}
		else
		{
			return "nada";
		}
	}
	else if ($hd_chamado == true)
	{
		$sql = "SELECT tbl_admin.nome_completo, tbl_admin.login FROM tbl_hd_chamado JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin WHERE tbl_hd_chamado.fabrica = $login_fabrica AND tbl_hd_chamado.hd_chamado = $param1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0)
		{
			$nome_completo = pg_result($res, 0, "nome_completo");
			$login         = pg_result($res, 0, "login");

			if (strlen($nome_completo) > 0)
			{
				return $nome_completo;
			}
			else
			{
				return $login;
			}
		}
		else
		{
			return "";
		}
	}
	
}

?>


<script src="../plugins/jquery/jpaginate/jquery.min.js"></script>
<script src="../plugins/shadowbox/shadowbox.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script src="../plugins/jquery/jpaginate/jquery-ui.min.js"></script>
<script src="../plugins/jquery/jpaginate/jquery.paginate.js"></script>
<script>
	$(function() {
		var rows = $('#rows').val();
		var pages = parseInt(rows / 50);
		var pages2 = parseInt(rows % 50);

		if (pages2 <= 49 && pages2 > 0)
		{
			var pages2 = 1
		}

		var pages_total = parseInt(pages + pages2);

		$("#pages").paginate({
			count 		: pages_total,
			start 		: 1,
			display     : 10,
			border					: false,
			text_color  			: '#495677',
			background_color    	: 'transparent',
			text_hover_color  		: '#FFB70F',
			background_hover_color	: 'transparent',
			rotate      			: false,
			images					: false,
			mouse					: 'press',
			onChange     			: function(page)
									  {
										  $('._current').removeClass('_current').hide("slide", { direction: "left" }, 500);
										  $('#p'+page).addClass('_current').delay(500).show("slide", { direction: "right" }, 500);
										  $(window).delay(500).scrollTop(180);
										  $('#page_atual').html("Página "+page);
									  }
		});

		Shadowbox.init();

		$("input[rel=data]").datepick({startDate:"01/01/2000"});
		$("input[rel=data]").maskedinput("99/99/9999");

		$("input[rel=pesq_prod]").change(function () {
			var value = $(this).val();

			if (value.length == 0)
			{
				$("input[name=produto_referencia]").val("");
				$("input[name=produto_descricao]").val("");
				$("input[name=produto]").val("");
			}
		});

		$("input[rel=pesq_posto]").change(function () {
			var value = $(this).val();

			if (value.length == 0)
			{
				$("input[name=posto_codigo]").val("");
				$("input[name=posto_nome]").val("");
				$("input[name=posto]").val("");
			}
		});
	});

	function fnc_pesquisa_produto(referencia, descricao)
	{
		if (referencia.length > 2 || descricao.length > 2)
		{
			Shadowbox.open({
				content: "produto_pesquisa_2_nv.php?referencia=" + referencia + "&descricao=" + descricao,
				player : "iframe",
				title  : "Pesquisa Produto",
				width  : 800,
				height : 500
			});
		}
		else
		{
			alert("Digite toda ou parte da informação para pesquisar !");
		}
	}

	function fnc_pesquisa_posto(codigo, descricao)
	{
		if (codigo.length > 2 || descricao.length > 2)
		{
			Shadowbox.open({
				content: "posto_pesquisa_nv.php?codigo=" + codigo + "&nome=" + descricao,
				player : "iframe",
				title  : "Pesquisa posto",
				width  : 800,
				height : 500
			});
		}
		else
		{
			alert("Digite toda ou parte da informação para pesquisar !");
		}
	}

	function retorna_dados_produto(produto, linha, nome_comercial, voltagem, referencia, descricao, referencia_fabrica, garantia, ativo, valor_troca, troca_garantia, troca_faturada, mobra, off_line, capacidade, ipi, troca_obrigatoria, posicao)
	{
		gravaDados("produto", produto);
		gravaDados("produto_referencia", referencia);
		gravaDados("produto_descricao", descricao);
	}

	function retorna_posto(posto, codigo_posto, nome, cnpj, pais, cidade, estado, nome_fantasia)
	{
		gravaDados("posto", posto);
		gravaDados("posto_codigo", codigo_posto);
		gravaDados("posto_nome", nome);
	}

	function gravaDados(name, valor)
	{
		try 
		{
			$("input[name="+name+"]").val(valor);
		} 
		catch(err)
		{
			return false;
		}
	}

	function montaComboCidade(estado){
    $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
            cache: false,
            success: function(data) {
                $('#cidade').html(data);
            }

        });
}


</script>

<link href="../plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
<link href="../plugins/jquery/jpaginate/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="../plugins/jquery/jpaginate/css/style.css" type="text/css" />
<style>
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";

	.formulario
	{
		background-color:#D9E2EF;
		font:11px "Arial";
		text-align:left;
	}

	.titulo_tabela
	{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.subtitulo
	{
		background-color: #7092BE;
		font:bold 11px "Arial";
		color: #FFFFFF;
	}

	.msg_erro
	{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		width: 700px;
	}

	.table_line 
	{
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
		background-color: #D9E2EF
	}

	table.tabela tr td
	{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.demo
	{
		width: 1025px;
		padding: 10px;
		margin: 0px auto;
	}

	.pagedemo
	{
		width: 1000px;
		margin: 0 auto;
		padding: 10px;
		text-align: center;
	}

	.pages
	{
		width: 300px;
		position: relative;
		margin: 0 auto;
	}
</style>

<div class="msg_erro" style="margin: 0 auto;">
	<?php

	echo $msg_erro;

	?>
</div>

<form id="frm_pesquisa" name="frm_pesquisa" method="POST" style="margin: 0 auto; width: 700px; background-color: #D9E2EF;">

	<input type="hidden" name="produto" value="<?=$produto?>" />
	<input type="hidden" name="posto" value="<?=$posto?>" />

	<table align="center" class="formulario" style="width: 700px; border: 0; margin: 0 auto; border-collapse: collapse;">
		<tr class="titulo_tabela">
			<td>
				Parâmetros de Pesquisa
			</td>
		</tr>
	</table>
<br />
	<table class="formulario" style="border: 0; width: 700px; margin: 0 auto;">
		<tr>
			<td style="text-align: right;">
				Data Inicial
			</td>
			<td>
				<input type="text" rel="data" name="data_inicial" value="<?=$data_inicial?>" size="14" maxlength="10" class="frm" />
			</td>
			<td style="text-align: right;">
				Data Final 
			</td>
			<td>
				<input type="text" rel="data" name="data_final" value="<?=$data_final?>" size="14" maxlength="10" class="frm" />
			</td>
		</tr>
		<tr>
			<td style="text-align: right;">
				Referência do Produto
			</td>
			<td>
				<input type="text" name="produto_referencia" value="<?=$produto_referencia?>" size="12" class="frm" rel="pesq_prod" />
				<img src="imagens/lupa.png" style="cursor: pointer; width: 15px; height: 15px;" align="absmiddle" title="Clique aqui para pesquisar pela referência do Produto" onclick="fnc_pesquisa_produto($('input[name=produto_referencia]').val(), '');" />
			</td>
			<td style="text-align: right;">
				Descrição do Produto
			</td>
			<td>
				<input type="text" name="produto_descricao" value="<?=$produto_descricao?>" size="20" class="frm" rel="pesq_prod" />
				<img src="imagens/lupa.png" style="cursor: pointer; width: 15px; height: 15px;" align="absmiddle" title="Clique aqui para pesquisar pela descrição do Produto" onclick="fnc_pesquisa_produto('', $('input[name=produto_descricao]').val());" />
			</td>
		</tr>
		<tr>
			<td style="text-align: right;">
				Código do Posto
			</td>
			<td>
				<input type="text" name="posto_codigo" value="<?=$posto_codigo?>" size="24" maxlength="18" class="frm" rel="pesq_posto" />
				<img src="imagens/lupa.png" style="cursor: pointer; width: 15px; height: 15px;" align="absmiddle" title="Clique aqui para pesquisar pelo Código do Posto" onclick="fnc_pesquisa_posto($('input[name=posto_codigo]').val(), '');" />
			</td>
			<td style="text-align: right;">
				Nome do Posto
			</td>
			<td>
				<input type="text" name="posto_nome" value="<?=$posto_nome?>" size="20" class="frm" rel="pesq_posto" />
				<img src="imagens/lupa.png" style="cursor: pointer; width: 15px; height: 15px;" align="absmiddle" title="Clique aqui para pesquisar pelo nome do Posto" onclick="fnc_pesquisa_posto('', $('input[name=posto_nome]').val());" />
			</td>
		</tr>
		<tr>
			<td style="text-align: right;">
				Estado
			</td>
			<td>
				<select name="estado" class="frm" onchange="montaComboCidade(this.value)">
					<option value=""></option>
					<?php

					foreach ($ArrayEstados as $sigla)
					{
						if ($estado == $sigla)
						{
							$selected = "SELECTED";
						}
						else
						{
							$selected = "";
						}

						echo "<option value='$sigla' $selected>$sigla</option>";
					}

					?>
				</select>
			</td>
<?php if($login_fabrica == 94){ ?>
			<td style="text-align: right;">Cidade</td>
			<td>
				<select class='frm' name='cidade' id='cidade' style="width:150px;">
					<option value=""></option>
					<?if(strlen(trim($estado))>0){
						    $sql = "SELECT UPPER(fn_retira_especiais(TRIM(cidade))) as nome from tbl_ibge where estado = UPPER('$estado') ORDER BY nome";
						    $res = pg_query($con,$sql);
						    if(pg_numrows($res) > 0){
						    	echo "<option value=''></option>";
						        for($i = 0; $i < pg_numrows($res); $i++){
						            $nome = pg_result($res,$i,'nome');

						            if($cidade == $nome){
							    		$selected = " selected ";
							    	}else{
							    		$selected = " ";
							    	}

						            echo "<option value='$nome' $selected >$nome</option>";
						        }
						    } else {
						        echo "<option value=''>Cidade não encontrada</option>";
						    }
					}
					?>
				</select>
			</td>
		</tr>

		<tr>
			<td style="text-align: right;">Bairro</td>
			<td>
				<input type="text" name="bairro" value="<?= $bairro?>" size="23" class="frm">
			</td>
<?}?>
			<td style="text-align: right;">
				Atendente
			</td>
			<td>
				<select name="atendente" class="frm">
					<option value=""></option>
					<?php

					$sqlx = "SELECT admin, nome_completo, privilegios FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo = TRUE ORDER BY nome_completo";
					$resx = pg_query($con, $sqlx);

					if (pg_num_rows($resx) > 0)
					{
						for ($i = 0; $i < pg_num_rows($resx); $i++)
						{
							$admin         = pg_result($resx, $i, "admin");
							$nome_completo = pg_result($resx, $i, "nome_completo");
							$privilegios   = pg_result($resx, $i, "privilegios");
							$privilegios   = explode(",", $privilegios);

							if ($atendente == $admin)
							{
								$selected = "SELECTED";
							}
							else
							{
								$selected = "";
							}

							foreach ($privilegios as $privilegio)
							{
								if ($privilegio == "call_center" or $privilegio == "*")
								{
									echo "<option value='$admin' $selected>$nome_completo</option>";
								}
							}
						}
					}

					?>
				</select>
			</td>
		</tr>
		<tr>
			<td style="text-align: right;">
				Status
			</td>
			<td>

				<select name="status" class="frm" >
					<option value="" SELECTED >Todos</option>
					<option value="Aberto" <? if ($status == "Aberto") echo "SELECTED"; else echo ""; ?> >Aberto</option>
					<option value="Resolvido" <? if ($status == "Resolvido") echo "SELECTED"; else echo ""; ?> >Resolvido</option>
					<option value="Cancelado" <? if ($status == "Cancelado") echo "SELECTED"; else echo ""; ?> >Cancelado</option>
				</select>
			</td>
			<td style="text-align: right;">
				Linha
			</td>
			<td>
				<select name="linha" class="frm" >
					<option value="" SELECTED >Todos</option>
					<option value="624" <? if ($linha == 624) echo "SELECTED"; else echo ""; ?> >Máquina de Gelo</option>
					<option value="625" <? if ($linha == 625) echo "SELECTED"; else echo ""; ?> >Purificador</option>
				</select>
			</td>
		</tr>
	</table>
	<br />
	<table class="formulario" style="border: 0; width: 500px; margin: 0 auto;">
		<tr>
			<td style="text-align: center; vertical-align: bottom;">
				<input type="submit" name="btn_pesquisa" value="Pesquisar" />
			</td>
		</tr>
	</table>
</form>

<?php

if (empty($msg_erro) and $_POST["btn_pesquisa"] == "Pesquisar")
{
	if($login_fabrica == 94){
		$campo_94 .= " 	tbl_cidade.nome as nome_cidade, 
						tbl_hd_chamado_extra.bairro, ";
	}

	$sql = "SELECT
				tbl_hd_chamado.hd_chamado,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
				tbl_cidade.estado,
				$campo_94
				tbl_produto.referencia AS produto_referencia,
				tbl_produto.descricao AS produto_descricao,
				(SELECT t2.admin from tbl_hd_chamado_item t2 where admin notnull and t2.hd_chamado = tbl_hd_chamado.hd_chamado order by t2.hd_chamado_item DESC limit 1) AS atendente,
				tbl_hd_chamado.categoria,
				tbl_posto.nome AS posto_nome,
				tbl_hd_chamado_extra.reclamado AS hd_chamado_descricao,
				tbl_hd_chamado_extra.defeito_reclamado_descricao AS  defeito_reclamado_descricao,
				tbl_linha.nome AS linha_descricao,
				tbl_hd_chamado.status
			FROM 
				tbl_hd_chamado
			JOIN 
				tbl_hd_chamado_extra
				ON 
					tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			LEFT JOIN 
				tbl_cidade
				ON 
					tbl_cidade.cidade = tbl_hd_chamado_extra.cidade 
			LEFT JOIN
				tbl_produto
				ON
					tbl_produto.produto = tbl_hd_chamado_extra.produto
			LEFT JOIN
				tbl_linha
				ON
					tbl_linha.linha = tbl_produto.linha
			LEFT JOIN
				tbl_posto
				ON
					tbl_posto.posto = tbl_hd_chamado_extra.posto
			WHERE
				tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				AND tbl_hd_chamado.data::date BETWEEN '$aux_data_inicial' AND '$aux_data_final'
				$where
			ORDER BY
				tbl_hd_chamado.data, tbl_hd_chamado.categoria";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0)
	{
		$msg_erro .= "Houve um erro ao realizar a pesquisa";
	}
	else
	{
		$rows = pg_num_rows($res);
	}
}

	if (isset($rows))
	{

		if ($rows == 0)
		{

?>

			<div class="msg_erro" style="margin: 0 auto; width: 700px;">
				Nenhum Atendimento encontrado com estes parâmetros
			</div>

<?php

		}
		else if ($rows > 0)
		{
			echo "<div id='pagination' class='demo'>";

			$xls_rows = pg_num_rows($res);
			if (pg_num_rows($res) <= 500)
			{
				$rows = pg_num_rows($res);
			}
			else
			{
				$rows = 500;
			}

			echo "<input type='hidden' id='rows' value='$rows'>";

			echo "<center>
						<p style='color: #ff2222;'>
							Serão mostrados em tela no maximo os últimos 500 atendimentos, para visualizar todos os atendimentos baixe o arquivo xls.
						</p>
						<p id='page_atual' style='color: #63798D'>
							Página 1
						</p>
				 </center>";

			for ($i = 0; $i < $rows; $i++)
			{
				$hd_chamado         = pg_result($res, $i, "hd_chamado");
				$data               = pg_result($res, $i, "data");
				$estado             = pg_result($res, $i, "estado");
				$produto_referencia = pg_result($res, $i, "produto_referencia");
				$produto_descricao  = pg_result($res, $i, "produto_descricao");
				$categoria          = pg_result($res, $i, "categoria");
				$posto_nome         = pg_result($res, $i, "posto_nome");
				$atendente          = pg_result($res, $i, "atendente");
				$linha_descricao    = pg_result($res, $i, "linha_descricao");
				$status             = pg_result($res, $i, "status");
				$nome_cidade 			= pg_result($res, $i, "nome_cidade");
				$bairro 			= pg_result($res, $i, "bairro");

				if (strlen($atendente) > 0)
				{
					$atendente   = nome_atendente($atendente);

					if ($atendente == "nada")
					{
						$atendente = nome_atendente($hd_chamado, true);
					}
				}
				else
				{
					$atendente = nome_atendente($hd_chamado, true);
				}

				$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';

				if ($i == $z)
				{
					if (empty($z))
					{
						$class = "class='pagedemo _current'";
					}
					else
					{
						$class   = "class='pagedemo'";
						$display = "display: none;'";
					}

					$p = $p + 1;
					$z = $z + 50;

					echo "<div id='p$p' $class style='$display width:1200px; margin-left:-92px;' >
							<table class='tabela' style=' border: 0; margin: 0 auto; border-collapse: collapse;' cellpadding='3px'>
								<tr class='titulo_tabela'>
									<td>
										Nº Atendimento
									</td>
									<td>
										Data
									</td>
									<td>
										Estado
									</td>";
					if($login_fabrica == 94){
						echo "<td>Cidade</td>";
						echo "<td>Bairro</td>";
					}

					echo "<td>
										Posto
									</td>
									<td>
										Produto
									</td>
									<td>
										Atendente
									</td>
									<td>
										Natureza
									</td>
									<td>
										Linha
									</td>
									<td>
										Status
									</td>
								</tr>";
				}

				echo "<tr style='text-align: center; background-color: $cor;' class='table_line'>
							<td style='cursor: pointer; color: #596D9B; text-style: underline;'>
								<a href='callcenter_interativo_new.php?callcenter=$hd_chamado#$natureza' target='_blank'>$hd_chamado</a>
							</td>
							<td>
								$data
							</td>
							<td>
								$estado
							</td>";

							if($login_fabrica == 94){
								echo "<td>$nome_cidade</td>";
								echo "<td>$bairro</td>";
							}
				
							echo "<td nowrap title='$posto_nome'>
								".substr($posto_nome, 0, 30)."
							</td>
							<td nowrap title='$produto_referencia - $produto_descricao'>
								$produto_referencia - ".substr($produto_descricao, 0, 30)."
							</td>
							<td nowrap>
								$atendente
							</td>
							<td>
								$categoria
							</td>
							<td nowrap >
								$linha_descricao
							</td>
							<td>
								$status
							</td>
					  </tr>";

				if ($i == ($z - 1))
				{
					echo "</table>
						  </div>";
				}

				if ($i == ($rows - 1))
				{
					echo "</table>
						  </div>";
				}
			}

			echo "<div id='pages' class='pages'></div>
				  </div>
				  <div style='color: #ff2222;'>
					Total de $xls_rows atendimentos.
				  </div>";

			if ($xls_rows > 0)
			{
				flush();

				echo `rm /tmp/assist/relatorio-chamado-callcenter-$login_fabrica.xls`;
				$fp = fopen ("/tmp/assist/relatorio-chamado-callcenter-$login_fabrica.html","w");

				$campo_excel_94 = "<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>Cidade</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>Bairro</td>";

				fputs($fp,"<table border='1' style='width: 700px; border: #000 solid; margin: 0 auto; border-collapse: collapse;' cellpadding='3px'>
								<tr>
									<td colspan='11' style='text-align: center; background-color: #F1C913; color: #373B57;'>
										Relatório de Atendimentos do Callcenter
									</td>
								</tr>
								<tr>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Nº Atendimento
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Data
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Estado
									</td>

									$campo_excel_94

									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Posto
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Produto
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Atendente
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Natureza
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Linha
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Status
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Defeito Reclamado
									</td>
									<td style='text-align: center; background-color: #373B57; color: #FFFFFF;'>
										Descrição
									</td>
								</tr>");
				for ($i = 0; $i < $xls_rows; $i++)
				{
					$hd_chamado                  = pg_result($res, $i, "hd_chamado");
					$data                        = pg_result($res, $i, "data");
					$estado                      = pg_result($res, $i, "estado");
					$produto_referencia          = pg_result($res, $i, "produto_referencia");
					$produto_descricao           = pg_result($res, $i, "produto_descricao");
					$categoria                   = pg_result($res, $i, "categoria");
					$defeito_reclamado_descricao = pg_result($res, $i, "defeito_reclamado_descricao");
					$posto_nome                  = pg_result($res, $i, "posto_nome");
					$hd_chamado_descricao        = pg_result($res, $i, "hd_chamado_descricao");
					$atendente                   = pg_result($res, $i, "atendente");
					$nome_cidade                 = pg_result($res, $i, "nome_cidade");
					$bairro                   = pg_result($res, $i, "bairro");
					$linha_descricao    = pg_result($res, $i, "linha_descricao");
					$status             = pg_result($res, $i, "status");

					if (strlen($atendente) > 0)
					{
						$atendente   = nome_atendente($atendente);

						if ($atendente == "nada")
						{
							$atendente = nome_atendente($hd_chamado, true);
						}
					}
					else
					{
						$atendente = nome_atendente($hd_chamado, true);
					}

					$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';

					$values_excel_94 = "<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$nome_cidade
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$bairro
									</td>";

					fputs($fp,"<tr>
									<td valign='top' style='cursor: pointer; color: #596D9B; text-style: underline; vertical-align: top;'>
										$hd_chamado
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$data
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$estado
									</td>
									$values_excel_94
									<td valign='top' nowrap title='$posto_nome' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$posto_nome
									</td>
									<td valign='top' nowrap style='text-align: center; background-color: $cor; vertical-align: top;'>
										$produto_referencia - $produto_descricao
									</td>
									<td valign='top' nowrap style='text-align: center; background-color: $cor; vertical-align: top;'>
										$atendente
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$categoria
									</td>
									<td valign='top' nowrap style='text-align: center; background-color: $cor; vertical-align: top;'>
										$linha_descricao
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$status
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$defeito_reclamado_descricao
									</td>
									<td valign='top' style='text-align: center; background-color: $cor; vertical-align: top;'>
										$hd_chamado_descricao
									</td>
							  </tr>");
				}

				fputs ($fp,"<tr>
								<td colspan='11' style='text-align: center; background-color: #ECC90E; color: #373B57;'>Total de Atendimentos: $xls_rows</td>
							</tr>
							</table>");

				fclose($fp);

				$data = date("Y-m-d").".".date("H-i-s");
				rename("/tmp/assist/relatorio-chamado-callcenter-$login_fabrica.html", "xls/relatorio-chamado-callcenter-$login_fabrica.$data.xls");

				echo"<br />
					<table width='200' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
						<tr>
							<td align='left' valign='absmiddle'>
								<a href='xls/relatorio-chamado-callcenter-$login_fabrica.$data.xls' target='_blank'>
									<img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>
									
									Gerar Arquivo Excel
								</a>
							</td>
						</tr>
					</table>";
			}
		}
	}

	include "rodape.php";

?>