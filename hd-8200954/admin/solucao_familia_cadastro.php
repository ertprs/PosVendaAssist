<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$btn_acao        = trim($_POST['btn_acao']);
$diagnostico     = trim($_REQUEST["diagnostico"]);
$codigo_solucao  = trim($_GET["solucao"]);

if(strlen($diagnostico)>0){
	$sql = "SELECT tbl_solucao.descricao as desc_solucao,
					   tbl_solucao.solucao   ,
					   tbl_diagnostico.mao_de_obra,
					   tbl_diagnostico.ativo,
					   tbl_diagnostico.diagnostico,
					   tbl_diagnostico.familia
					  FROM tbl_diagnostico
					  JOIN tbl_solucao ON tbl_diagnostico.solucao = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica  
					WHERE tbl_diagnostico.fabrica = $login_fabrica
					AND tbl_diagnostico.diagnostico = $diagnostico";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$diagnostico         = trim(pg_result($res,0,diagnostico));
		$solucao             = trim(pg_result($res,0,solucao));
		$mao_obra            = trim(pg_result($res,0,mao_de_obra));
		$descricao_solucao   = trim(pg_result($res,0,desc_solucao));
		$ativo               = trim(pg_result($res,0,ativo));
		$familia             = trim(pg_result($res,0,familia));
	}
}

if(strlen($btn_acao)>0){
	if($btn_acao=="gravar"){
		$diagnostico          = trim($_POST["diagnostico"]);
		$solucao              = trim($_POST['solucao']);
		$mao_obra             = trim($_POST['mao_obra']);
		$codigo_solucao    = $_POST['descricao_solucao'];
		$ativo                = trim($_POST['ativo']);
		$familia              = $_POST['familia'];
		
		$mao_obra = str_replace(".","",$mao_obra);
		$mao_obra = str_replace(",",".",$mao_obra);

		$mao_obra = "'".$mao_obra."'";//adicionado pois a fabrimar não usa o campos então tem que passa null e null não pode ter aspas ''

		if(empty($ativo)){
			$ativo = 'f';
		}

		if(empty($codigo_solucao)){
			$msg_erro = "Selecione uma Solução";
		}
		
		if(empty($familia) and empty($msg_erro)){
			$msg_erro = "Informe uma Família";
		}

		if($login_fabrica != 145 && $login_fabrica != 158){
			if(empty($mao_obra) and empty($msg_erro)){
				$msg_erro = "Informe o valor da Mão de Obra";
			}
		}else{
			$mao_obra = 'NULL';
		}		

		if(strlen($msg_erro) == 0){
			$res = pg_query($con,"BEGIN TRANSACTION");

			if(empty($diagnostico)){				
				$sql = "INSERT INTO tbl_diagnostico
									(
									fabrica,
									solucao,
									familia,
									mao_de_obra,
									ativo
									)
								VALUES
									(
									$login_fabrica,
									$codigo_solucao,
									$familia,
									$mao_obra,
									'$ativo'
									)";
				$res = pg_query($con,$sql);
			}
			else{
				$sql = "UPDATE tbl_diagnostico SET mao_de_obra = $mao_obra, ativo = '$ativo' 
							WHERE diagnostico = $diagnostico
							AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);

				$sql = "UPDATE tbl_solucao SET descricao = '$descricao_solucao' , ativo = '$ativo'
							WHERE fabrica = $login_fabrica 
							AND solucao = $solucao";
				$res = pg_query($con,$sql);
			}
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro) > 0){
				$res = pg_query($con,"ROLLBACK");
			}
			else{
				$res = pg_query($con,"COMMIT");
				$msg = 'Operação realizada com Sucesso!';
				//header("Location:$PHP_SELF?msg=$msg");
			}		
		}
	}//gravar

	if(($btn_acao=="deletar") AND (strlen($diagnostico)>0)){

		$sql = "DELETE FROM tbl_diagnostico WHERE diagnostico = $diagnostico";
		$res = pg_exec($con,$sql);

		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)==0){
			$msg="Apagado com sucesso!";
			//header("Location:$PHP_SELF?msg=$msg"); 
		}
	}//DELETAR
	
}//btn_acao

//$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE SOLUÇÃO X FAMÍLIA";
//include 'cabecalho.php';
include 'cabecalho_new.php';
?>

<script type='text/javascript'>
	$(document).ready(function(){
		$("#mao_obra").numeric({allow:'.,'});
	});
</script>
<?php

if (strlen($msg_erro) > 0) {?>
        <div class="alert alert-error">
            <h4><? echo $msg_erro; ?></h4>
        </div>

	<?php
} else if (strlen($msg) > 0) {?>
	<div class="alert alert-success">
            <h4><?php echo $msg; ?></h4>
        </div>
<?php
	}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<?
//todo alterar os $msg
echo "<form name='frm_defeito' method='post' action='$PHP_SELF' class='form-search form-inline tc_formulario' border='0'>";
?>

<div class="titulo_tabela ">
   CADASTRAMENTO DE SOLUÇÃO X FAMÍLIA
</div>
<div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
		<label class="control-label" for="data_inicial">Família</label>
		<div class="controls controls-row">
            <div class="span12">
                <h5 class="asteristico">*</h5>
            		<select name='familia' class='span12'>
						<option value=''>Selecione uma Família</option>
					<?
						$sql = "SELECT familia,descricao 
						            FROM tbl_familia 
								   WHERE fabrica = $login_fabrica 
								   AND ativo IS TRUE 
								   ORDER BY descricao";
						$res = pg_query($con,$sql);
						$total_familias = pg_num_rows($res);

						for($i=0; $i < $total_familias; $i++){
							$codigo    = pg_result($res,$i,familia);
							$descricao = pg_result($res,$i,descricao);

							echo "<option value='$codigo'";
							if($codigo == $familia){
								echo " SELECTED";
							}
							echo ">$descricao</option>";
						}
					?>
					</select>
            </div>
        </div>   		
    </div>
    <div class='span4'>
		<label class="control-label" for="data_inicial">Solução</label>
		<div class="controls controls-row">
            <div class="span12">
                <h5 class="asteristico">*</h5>
                <select name='descricao_solucao' class='span12'>
						<option value=''>Selecione uma Solução</option>
					<?php
						$sqlSolucao = "SELECT solucao,descricao FROM tbl_solucao WHERE fabrica = $login_fabrica";
						$resSolucao = pg_query($con,$sqlSolucao);
						if(pg_numrows($resSolucao) > 0){
							for($i = 0; $i < pg_numrows($resSolucao); $i++){
								$solucao = pg_result($resSolucao,$i,solucao);
								$desc_solucao = pg_result($resSolucao,$i,descricao);

								echo "<option value='$solucao'";
								if($solucao == $codigo_solucao){
									echo " SELECTED";
								}
								echo " >$desc_solucao</option>";

							}
						}
					?>
					</select>            		
            </div>
        </div>   		
    </div>
    <div class="span2"></div>
</div>
<div class='row-fluid'>
	<?php if($login_fabrica != 145 && $login_fabrica != 158){?>
	<div class='span2'></div>    
    <div class='span3'>
		<label class="control-label" for="data_inicial">Mão de Obra</label>
		<div class="controls controls-row">
            <div class="span12">
                <h5 class="asteristico">*</h5>
            		<input type='text' name='mao_obra' id='mao_obra'value='<? if(!empty($mao_obra))echo number_format($mao_obra,2,',','.'); ?>' size='8' class='frm' maxlength='8'>
            </div>
        </div>   		
    </div>
    <?php } ?>
    <div class='span2'></div>
     <div class='span5'>
		<label class="control-label" for="data_inicial"></label>		
            <div class="span12">
        		<?php echo	"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ativo&nbsp;&nbsp;";?>
            	<input type='checkbox' name='ativo'<? if ($ativo == 't' ) echo " checked "; ?> value='t'>
            </div>         		
    </div>
</div>
<div class='row-fluid form-horizontal'>
    <div class='span12 tac'>
    <p>
    	<input type='hidden' name='btn_acao' value=''>
		<input type='hidden' name='solucao' value='<? echo $codigo_solucao; ?>'>
		<input type='hidden' name='diagnostico' value='<? echo $diagnostico; ?>'>
		<input type="button" value="Gravar" class='btn btn-primary' onclick="javascript: if (document.frm_defeito.btn_acao.value == '' ) { document.frm_defeito.btn_acao.value='gravar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
		<input type="button" value="Apagar" class='btn btn-danger' onclick="javascript: if (document.frm_defeito.btn_acao.value == '' ) { document.frm_defeito.btn_acao.value='deletar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Linha" style="cursor: pointer;">
		<input type="button" value="Limpar" class='btn btn-success' onclick="javascript: if (document.frm_defeito.btn_acao.value == '' ) { document.frm_defeito.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
	</p>
    </div>
</div>

<?
echo "<BR>";
echo "</form>";
?>


<div class='container'>
	<table id='tbl_tipo_posto' class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr class="titulo_coluna">
				<th colspan="4">Relação de Solução X Família</th>
			</tr>
			<tr bgcolor='#D9E2EF' class='titulo_coluna'>
				<td align='left'>Ativo</td>
				<td align='left'>Família</td>
				<td align='left'>Solução</td>
				<?php if($login_fabrica != 145 && $login_fabrica != 158){?>
					<td align='left'>Mão de Obra</td>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php
				if($login_fabrica != 145 && $login_fabrica != 158){
					$complemento_where = "AND tbl_diagnostico.mao_de_obra IS NOT NULL";
				}				

				$sql = "SELECT tbl_solucao.descricao as desc_solucao,
						   tbl_solucao.solucao   ,
						   tbl_diagnostico.mao_de_obra,
						   tbl_diagnostico.ativo, 
						   tbl_diagnostico.diagnostico,
						   tbl_familia.descricao as familia
						  FROM tbl_diagnostico
						  JOIN tbl_solucao ON tbl_diagnostico.solucao = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
						  JOIN tbl_familia ON tbl_diagnostico.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
						WHERE tbl_diagnostico.fabrica = $login_fabrica
						$complemento_where						
						ORDER BY tbl_diagnostico.familia";
				$res = pg_exec ($con,$sql);
				for ($y = 0 ; $y < pg_numrows($res) ; $y++){
					$solucao           = trim(pg_result($res,$y,solucao));
					$solucao_descricao = trim(pg_result($res,$y,desc_solucao));
					$familia           = trim(pg_result($res,$y,familia));
					$diagnostico       = trim(pg_result($res,$y,diagnostico));
					$mao_obra          = trim(pg_result($res,$y,mao_de_obra));
					$ativo             = trim(pg_result($res,$y,ativo));
					if($ativo=='t'){ $ativo="Sim"; }else{$ativo="<font color='#660000'>Não</font>";}
					
					$cor = ($y % 2 == 0) ? "#F7F5F0": '#F1F4FA';

					echo "<tr bgcolor='$cor'>";
					echo "<td align='left'>$ativo</td>";
					echo "<td align='left'>$familia</td>";
					echo "<td align='left'><a href='$PHP_SELF?diagnostico=$diagnostico&solucao=$solucao'>$solucao_descricao</a></td>";
					if($login_fabrica != 145 && $login_fabrica != 158){
						echo "<td align='left'>".number_format($mao_obra,2,',','.')."</td>";
					}					
					echo "</tr>";
				}
			?>
		</tbody>
	</table>
</div>
<?php
include "rodape.php";
?>
</body>
</html>