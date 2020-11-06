<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

//ESTÁ EM TESTE PARA A TECTOY 27/09/06 TAKASHI
if ($login_fabrica == '6') {
	include "linha_cadastro_new.php";
	exit;
}

$labelLinha = traduz("Linha");
if ($login_fabrica == 117) {
        $labelLinha = traduz("Macro - Família");
}


include 'funcoes.php';

if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim') {
	if ($_POST['action'] == 'altera_macro_familia') {
		$nome  = (mb_detect_encoding($_POST['nome'], 'utf-8', true)) ? utf8_decode($_POST['nome']) : $_POST['nome'];
		$linha = $_POST['linha'];
		$ativo = ($_POST['ativo'] == 'TRUE') ? ',ativo = true' : ',ativo = false';

		$sql = "UPDATE tbl_linha SET nome = '{$nome}' {$ativo} WHERE linha = {$linha}";
		pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0)
			exit(json_encode(array("erro" => utf8_encode(traduz("Ocorreu um erro ao tentar atualizar esta Macro - Família")))));

		exit(json_encode(array("ok" => utf8_encode(traduz("Macro - família alterada com sucesso")))));
	}
}

// AJAX
if (in_array($btn_acao, array('ativar','inativar'))) {

    $linha = $_REQUEST["linha"];
    $status = ($btn_acao == 'ativar') ? "true" : "false";

    $sql = "UPDATE tbl_linha SET auto_agendamento = {$status} WHERE linha = {$linha} AND fabrica = {$login_fabrica};";
    $res = pg_query($con, $sql);

    $status = (pg_affected_rows($res) > 0) ? true : false;

    if($status == true){
        $descricao = "";
    }else{
        $descricao = traduz("Erro ao alterar Status do Auto Agendamento da Linha");
    }

    $descricao = utf8_encode($descricao);

    echo json_encode(array("status" => $status, "descricao" => $descricao));
    exit;

}

$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = traduz("CADASTRO DE LINHA DE PRODUTO");

include "cabecalho_new.php";

$res = pg_exec ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
$pedido_via_distribuidor = pg_result ($res,0,0);

if (strlen($_GET["linha"]) > 0)       $linha      = trim($_GET["linha"]);
if (strlen($_POST["linha"]) > 0)      $linha      = trim($_POST["linha"]);
if (strlen($_POST["multimarca"]) > 0) $multimarca = trim($_POST["multimarca"]);
if (strlen($_POST["btn_acao"]) > 0)    $btnacao    = trim($_POST["btn_acao"]);

if ($btnacao == "deletar" and strlen($linha) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_linha
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_linha.linha   = $linha;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_last_error($con);

	if (strpos ($msg_erro,'linha_fk') > 0)              $msg_erro = traduz("Esta linha já possui produtos cadastrados, e não pode ser excluida <br/>");
	if (strpos ($msg_erro,'tbl_defeito_reclamado') > 0) $msg_erro = traduz("Esta linha já possui 'Defeitos Reclamados' cadastrados, e não pode ser excluida <br/>");

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = traduz("Apagado com Sucesso!");
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$linha        = $_POST["linha"];
		$codigo_linha = $_POST["codigo_linha"];
		$nome         = $_POST["nome"];
		$marca        = $_POST["marca"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar" AND in_array($login_fabrica, array(10,175)) ) {
	if (strlen($_POST["codigo_linha"]) > 0) $aux_codigo_linha = "'" . trim($_POST["codigo_linha"]) . "'" ;
	else                                    $aux_codigo_linha = "null";
	if (strlen($_POST["nome"]) > 0)         $aux_nome         = "'". trim($_POST["nome"]) ."'";
	else                                    $msg_erro         = "Favor informar o nome da linha. <br/>";
	if (strlen($_POST["marca"]) > 0)        $aux_marca        = "'". trim($_POST["marca"]) ."'";
	else                                    $aux_marca        = "null";
	if (strlen($_POST["ativo"]) > 0)        $aux_ativo        = "'t'";
	else                                    $aux_ativo        = "'f'";

	/*if (strlen($msg_erro) == 0) {
		if (strlen($_POST["marca"]) > 0) {
			$aux_marca = "'". trim($_POST["marca"]) ."'";
		}else{
			if (strlen($multimarca) > 0) {
				$msg_erro = "Selecione a marca para esta linha.";
			}else{
				$aux_marca = "null";
			}
		}
	}*/

	if (strlen($msg_erro) == 0) {
		$mao_de_obra_adicional_distribuidor = trim ($_POST['mao_de_obra_adicional_distribuidor']);
		$aux_mao_de_obra_adicional_distribuidor = $mao_de_obra_adicional_distribuidor;
		if (strlen ($aux_mao_de_obra_adicional_distribuidor) == 0) {
			if ($pedido_via_distribuidor == 't') {
				$aux_mao_de_obra_adicional_distribuidor = 0 ;
			}else{
				$aux_mao_de_obra_adicional_distribuidor = 'null' ;
			}
		}
		$aux_mao_de_obra_adicional_distribuidor = str_replace (",",".",$aux_mao_de_obra_adicional_distribuidor);

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($linha) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_linha (
						fabrica,
						codigo_linha,
						nome   ,
						marca  ,
						mao_de_obra_adicional_distribuidor,
						ativo
					) VALUES (
						$login_fabrica,
						$aux_codigo_linha,
						$aux_nome     ,
						$aux_marca    ,
						$aux_mao_de_obra_adicional_distribuidor,
						$aux_ativo
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_linha SET
					codigo_linha = $aux_codigo_linha,
					nome         = $aux_nome,
					marca        = $aux_marca,
					ativo        = $aux_ativo,
					mao_de_obra_adicional_distribuidor = $aux_mao_de_obra_adicional_distribuidor
				WHERE   tbl_linha.fabrica =	$login_fabrica
				AND     tbl_linha.linha   = $linha;";
		}
		$res = pg_exec ($con,$sql);

		if(strpos(pg_last_error($con), "duplicate key")!==false){
			$msg_erro .= traduz("Código da linha já existe. <br/>");
		}
	}

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = traduz("Gravado com Sucesso!");
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$linha        = $_POST["linha"];
		$codigo_linha = $_POST["codigo_linha"];
		$nome         = $_POST["nome"];
		$marca        = $_POST["marca"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($linha) > 0) {
	$sql =	"SELECT tbl_linha.linha,
				tbl_linha.codigo_linha,
				tbl_linha.nome,
				tbl_linha.marca,
				tbl_linha.mao_de_obra_adicional_distribuidor,
				tbl_linha.ativo,
				tbl_linha.auto_agendamento
		FROM      tbl_linha
		LEFT JOIN tbl_marca on tbl_marca.marca = tbl_linha.marca
		WHERE     tbl_linha.fabrica = $login_fabrica
		AND       tbl_linha.linha   = $linha;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$linha        = trim(pg_result($res,0,linha));
		$codigo_linha = trim(pg_result($res,0,codigo_linha));
		$nome         = trim(pg_result($res,0,nome));
		$marca        = trim(pg_result($res,0,marca));
		$ativo        = trim(pg_result($res,0,ativo));
		$auto_agendamento = trim(pg_result($res,0,auto_agendamento));
		$mao_de_obra_adicional_distribuidor = trim(pg_result($res,0,mao_de_obra_adicional_distribuidor));
	}
}

?>

<script type="text/javascript">
	$(function() {
		$(".status").on("click", function (){
			var i = $(this).attr('rel');
	        var linha = $('#linha_'+i).val();
	        var that = $(this);

	        var btn_acao = $(this).text().toLowerCase();
	        var r = confirm("Deseja realmente "+$(this).text()+" esse registro?");

	        if(r == false) {
                return false;
	        }

	        $.ajax({
	            url : "<?= $PHP_SELF; ?>",
	            type : "POST",
	            data : {
	                btn_acao : btn_acao,
	                linha : linha
	            },
	            complete: function(data){
	                data = $.parseJSON(data.responseText);
	                if(data.status == true){
	                    if (btn_acao == 'ativar') {
		                    $(that).removeClass("btn-success").addClass("btn-danger");
		                    $(that).text("Inativar");
		                    $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_verde.png?" + (new Date()).getTime() });
		                } else {
		                    $(that).removeClass("btn-danger").addClass("btn-success");
		                    $(that).text("Ativar");
		                    $(that).parents("tr").find("img[name=visivel]").attr({ "src": "imagens/status_vermelho.png?" + (new Date()).getTime() });
		                }
	                }else{
	                    alert(data.descricao);
	                }
	            }
	        });
	    });
	    $('#btn_gravar_elgin').on('click', function(){
	    	var linha = $('input[name=linha]').val();
	    	var nome  = $('#nome').val();
	    	var ativo = ($('#ativo').is(':checked')) ? 'TRUE' : '';

	    	if (linha !== '') {
	    		$.ajax({
	    			url: window.location.href,
	    			type: "POST",
	    			data: { ajax: 'sim', action: "altera_macro_familia", linha: linha, nome: nome, ativo: ativo },
	    			timeout: 8000
	    		}).fail(function(){
		    		$('.alert-error').show().find('h4').html('<?=traduz("Ocorreu um erro ao tentar atualizar esta Macro - Família")?>');
					setTimeout(function(){
						$('.alert-error').hide();
					}, 5000);	    			
	    		}).done(function(data){
	    			data = JSON.parse(data);
	    			if (data.ok !== undefined) {
	    				$('.alert-success').show().find('h4').html(data.ok);
	    				setTimeout(function(){
	    					$('.alert-success').hide();
	    				}, 5000);
	    			}else{
	    				$('.alert-error').show().find('h4').html(data.erro);
	    				setTimeout(function(){
	    					$('.alert-error').hide();
	    				}, 5000);	    				
	    			}
	    		});
	    	}else{
	    		$('.alert-error').show().find('h4').html('<?=traduz("Opção disponível apenas para alteração e não inclusão de novos registros!")?>');
				setTimeout(function(){
					$('.alert-error').hide();
				}, 5000);
	    	}
	    });

	    $('#btn_limpar_elgin').on('click', function(){
	    	window.location.href = 'linha_cadastro.php';
	    });
	});
</script>

<? $display = (strlen($msg_erro) > 0) ? 'block' : 'none'; ?>
	<div class="alert alert-error" style="display: <?=$display; ?>">
		<h4><?=$msg_erro?></h4>
    </div>

<?
$display = (strlen($msg) > 0) ? 'block' : 'none';
if (strlen($msg) > 0) {
	$codigo_linha = "";
	$nome = "";
	$mao_de_obra_adicional_distribuidor = "";
	$ativo = "f";
	$linha = "";
	$auto_agendamento = "f";
} ?>

<div class="alert alert-success" style="display: <?=$display; ?>">
	<h4><? echo $msg; ?></h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<form name="frm_linha" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>" align="center" class='form-search form-inline tc_formulario' >
<input type="hidden" name="linha" value="<? echo $linha ?>">
<input type="hidden" name="multimarca" value="<? echo $multimarca ?>">

	<div class="titulo_tabela"><?=traduz('Cadastro de')?> <?=$labelLinha?></div>
	<br/>
	<div class="row-fluid">
		<!--Margem  -->
		<?php $varspan3 = ($login_fabrica == 117) ? "span2" : "span3" ;
        $varspan2 = ($login_fabrica == 117) ? "span3" : "span2" ;
        ?>		
		<div class="<?=$varspan3?>"></div>

        <div class="<?=$varspan2?>">
                <div class='control-group'>
                    <label class='control-label' for=''><?=traduz('Código da')?> <?=$labelLinha?></label>
                    <div class='controls controls-row'>
                      <input class="span10" type="text" name="codigo_linha" id="codigo_linha" value="<? echo $codigo_linha ?>" />
                    </div>
                </div>
        </div>

        <div class="span3">
                <div class='control-group <?=(strpos($msg_erro,"nome da linha") !== false) ? "error" : "" ?>'>
                    <label class='control-label' for=''><?=traduz('Nome da')?> <?=$labelLinha?></label>
                    <div class='controls controls-row'>
                        <h5 class='asteristico'>*</h5>
                                <input class="span12" type="text" id="nome" name="nome" value="<? echo $nome ?>" >
                    </div>
                </div>
        </div>
		<div class="span1">
			<div class='control-group tac'>
			    <label class='control-label' for=''><?=traduz('Ativo')?></label>
			    <div class='controls controls-row tac'>
					<input type='checkbox' name='ativo' id='ativo' value='TRUE' <?if($ativo == 't') echo "CHECKED";?> />
			    </div>
			</div>
		</div>

		<? if ($login_fabrica == 158) { ?>
			<div class="span1">
				<div class='control-group tac'>
				    <label class='control-label' for='auto_agendamento'><?=traduz('Auto Agendamento')?></label>
				    <div class='controls controls-row tac'>
						<input type='checkbox' name='auto_agendamento' id='auto_agendamento' value='TRUE' <?= ($auto_agendamento == 't') ? "CHECKED" : "";?> />
				    </div>
				</div>
			</div>
		<? } ?>
		<!--Margem  -->
		<div class="span3"></div>

	</div>

	<?php
		if ($multimarca == 't') {
	?>

		<!-- Multimarca -->
		<div class="row-fluid">
			<!--Margem  -->
			<div class="span3"></div>
			<div class="span1">
				<div class='control-group'>
				    <label class='control-label' for=''><?=traduz('Marca')?></label>
				    <div class='controls controls-row'>
				    	<select name="marca" id="marca">
				    	<option value=""></option>
				    	<?php
				    		$sql = "SELECT  tbl_marca.marca              ,
								tbl_marca.nome AS nome_marca
								FROM    tbl_marca
								WHERE   tbl_marca.fabrica = $login_fabrica
								ORDER BY tbl_marca.nome;";
							$res = pg_exec ($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
									$selected_marca = ( isset($marca) and ($marca == $key['marca']) ) ? "SELECTED" : '' ;
				    	?>

				    			<option value="<?= $key['marca']?>" <?= $selected_linha ?> >

									<?= $key['nome_marca']?>

								</option>
				    	<?php
				    	}
				    	?>
				    	</select>
				    </div>
				</div>
			</div>
			<!--Margem  -->
			<div class="span3"></div>
		</div>

	<?php
		}
	?>
	<!--  -->
	<?php  if ($pedido_via_distribuidor == 't') { ?>
		<div class="row-fluid">
			<!--Margem  -->
			<div class="span3"></div>
			<div class="span6">
				<div class='control-group'>
				    <label class='control-label span12' for=''><?=traduz('M.O. adicional para Distribuidor')?></label>
				    <div class='controls controls-row'>
						<input type='text' id="mao_de_obra_adicional_distribuidor" name='mao_de_obra_adicional_distribuidor' value="<?php echo $mao_de_obra_adicional_distribuidor; ?>" size='10' maxlength='10' />
				    </div>
				</div>
			</div>
			<!--Margem  -->
			<div class="span3"></div>
		</div>
	<?php } ?>
<br/>
<? if (in_array($login_fabrica, array(10,175)) ) { ?>
	<div class="row-fluid">
		<!-- Margem -->
		<div class="span4"></div>
		<div class="span4 tac">
			<button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="<?= traduz('Gravar formulário'); ?>" >Gravar</button>
			<? if (strlen($linha) > 0) { ?>
				<button type="button" class="btn btn-danger" onclick="if (confirm('<?= traduz('Deseja realmente apagar o registro?'); ?>')){submitForm($(this).parents('form'),'deletar');}" alt="<?= traduz('Apagar Linha'); ?>" ><?=traduz('Apagar')?></button>
			<? } ?>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />

		</div>
		<div class="span4"></div>
	</div>
	<br/>
</form>
<?}else{
	if ($login_fabrica == 117 && isset($_GET['linha'])) {
?>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4 tac">
			<button type="button" class="btn btn-primary" id="btn_gravar_elgin" alt="<?= traduz('Gravar formulário'); ?>"><?=traduz('Gravar')?></button>
			<button type="button" class="btn btn-warning" id="btn_limpar_elgin" alt="<?= traduz('Limpa formulário'); ?>"><?=traduz('Limpar')?></button>
		</div>
		<div class="span4"></div>
	</div>
<?
	}
?>
</form>
<div class="alert">
	<? if ($login_fabrica == 117) {
		echo traduz("<h4>A inclusão de novas Macro - Famílias é feita pelo Suporte da Telecontrol</h4>");
	}else{
		echo traduz("<h4>A Manutenção das % s é feita pelo Suporte da Telecontrol</h4>", null, null, [$labelLinha]);
	} ?>
</div>
<? }
if ($login_fabrica == 117 && !isset($_GET['linha'])) {
	echo "<div class='alert' style='margin-top: -12px'>";
	echo "<h4>".traduz("Selecione uma Macro - Família abaixo para alteração")."</h4>";
	echo "</div>";
}

if(strlen($linha) > 0) {

	$sql =	"SELECT   tbl_linha.codigo_linha AS codigo_linha ,
					  tbl_linha.nome         AS nome_linha   ,
					  tbl_produto.produto                    ,
					  tbl_produto.referencia                 ,
					  tbl_produto.descricao                  ,
					  tbl_produto.ativo
			FROM      tbl_produto
			LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE     tbl_linha.fabrica = $login_fabrica
			AND       tbl_produto.linha = $linha
			AND 	  tbl_produto.fabrica_i = $login_fabrica
			ORDER BY  tbl_produto.descricao;";
	$res = @pg_exec ($con,$sql);

	if (pg_num_rows($res) > 0){ ?>
		<table class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class="titulo_tabela">
					<th colspan='3'><?=traduz('Produtos da')?> <?=(in_array($login_fabrica, array(117)) ? traduz('Macro - Família') : traduz('Linha'));?> <? echo pg_result($res,0,nome_linha);?> </th>
				</tr>
				<tr class="titulo_coluna">
					<th ><?=traduz('Referência')?></th>
					<th ><?=traduz('Descrição')?></th>
					<th ><?=traduz('Status')?></th>
				</tr>
			</thead>

			<tbody>

		<? for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
				$produto       = trim(pg_result($res,$i,produto));
				$referencia    = trim(pg_result($res,$i,referencia));
				$descricao     = trim(pg_result($res,$i,descricao));

				if (strtoupper(mb_detect_encoding($descricao)) == "UTF-8") {
					$result_descricao = iconv("UTF-8", "ISO-8859-1", $descricao);
					if ($result_descricao) {
						$descricao = $result_descricao;
					}
				}

				$ativo         = trim(pg_result($res,$i,ativo));

				if($ativo=='t'){
					$ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
				}else{
					$ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
				}

		?>
				<tr>
				<td><?php echo $referencia; ?></td>
				<td><a href='produto_cadastro.php?produto=<?=$produto?>'><?php echo $descricao; ?></a></td>
				<td class="tac"><?php echo $ativo; ?></td>
				</tr>
		<?	} ?>
			</tbody>
		</table>
	<? }else if (pg_num_rows($res) == 0){ ?>

			<div class='alert'>
				    <h4><?=traduz('ESTA LINHA NÃO POSSUI PRODUTOS CADASTRADOS')?></h4>
			</div>
	<? } 
}
if ($login_fabrica == 117) {
    $joinElgin = "JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                 JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha";
}
$sql = "SELECT DISTINCT tbl_linha.linha,
				  tbl_linha.codigo_linha AS codigo_linha,
				  tbl_linha.nome AS nome_linha,
				  tbl_linha.marca,
				  tbl_linha.ativo,
				  tbl_linha.auto_agendamento,
				  tbl_linha.deslocamento,
				  tbl_marca.nome AS nome_marca
		FROM      tbl_linha
		$joinElgin
		LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_linha.marca
		WHERE     tbl_linha.fabrica = $login_fabrica
		ORDER BY  tbl_linha.ativo DESC, tbl_marca.nome,tbl_linha.nome;";
$res = @pg_exec ($con,$sql);
?>
<table class='table table-striped table-bordered table-hover table-fixed'>
	<thead>
		<tr class="titulo_tabela">
			<? $colspan = ($login_fabrica == 158) ? 5 : 4; ?>
			<th colspan="<?= $colspan; ?>"><?=$labelLinha?>s</th>
		</tr>
		<tr class='titulo_coluna'>
			<th><?=traduz('Código')?></th>
			<th><?=traduz('Descrição')?></th>
			<th><?=traduz('Status')?></th>
			<? if ($multimarca == 't') { ?>
				<th><?=traduz('Marca')?></th>
			<? }
			if ($login_fabrica == 158) { ?>
				<th><?=traduz('Auto Agendamento')?></th>
				<th><?=traduz('Ações')?><br /><?=traduz('(Auto Agendamento)')?></th>
			<? }
			if(in_array($login_fabrica, array(169,170))){
			?>
				<th><?=traduz('Deslocamento')?></th>
			<?php
			}
			?>
		</tr>
	</thead>
	<tbody>
		<? for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			$linha          = trim(pg_result($res,$i,linha));
			$codigo_linha   = trim(pg_result($res,$i,codigo_linha));
			$nome_linha     = trim(pg_result($res,$i,nome_linha));
			$marca          = trim(pg_result($res,$i,marca));
			$ativo          = trim(pg_result($res,$i,ativo));
			$auto_agendamento = trim(pg_result($res,$i,auto_agendamento));
			$nome_marca     = trim(pg_result($res,$i,nome_marca));
			$deslocamento   = trim(pg_result($res,$i,deslocamento));

			$deslocamento = ($deslocamento == 't') ? "<img title='Ativo' src='imagens/status_verde.png'>" : "<img title='Inativo' src='imagens/status_vermelho.png'>";

			$ativo = ($ativo == 't') ? "<img title='Ativo' src='imagens/status_verde.png'>" : "<img title='".traduz("Inativo")."' src='imagens/status_vermelho.png'>";
			$img_agenda = ($auto_agendamento == 't') ? "<img name='visivel' title='Disponível' src='imagens/status_verde.png'>" : "<img name='visivel' title='".traduz("Indisponível")."' src='imagens/status_vermelho.png' />";
			$class_btn_agenda = ($auto_agendamento == "t") ? 'btn-danger' : 'btn-success';
			$botao_agenda = "<button type='button' class='btn btn-small {$class_btn_agenda} status' rel='{$i}'>";
		    $botao_agenda .= ($auto_agendamento == "t") ? traduz("Inativar") : traduz("Ativar");
		    $botao_agenda .="</button>"; ?>
			<tr>
				<td><?= $codigo_linha; ?></td>
				<td><a href=<?= "$PHP_SELF?linha=$linha".((isset($semcab)) ? "&semcab=yes" : "");?>><?= $nome_linha;?></a></td>
				<td class="tac"><?= $ativo;?> </td>
				<? if ($multimarca == 't') { ?>
						<td><?= $nome_marca;?> </td>
				<? }
				if ($login_fabrica == 158) { ?>
					<td class="tac"><?= $img_agenda; ?></td>
					<td class="tac">
						<input type="hidden" id="linha_<?= $i; ?>" name="linha_<?= $i; ?>" value="<?= $linha; ?>" />
						<?= $botao_agenda; ?>
					</td>
				<? }
				if(in_array($login_fabrica, array(169,170))){
				?>
				<td class='tac'>
					<?=$deslocamento?>
				</td>
				<?php } ?>
			</tr>
		<? } ?>
	</tbody>
</table>

<br/>


<?
if(!isset($semcab))include "rodape.php";
?>

