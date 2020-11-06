<?php
/**
 * @author Brayan L. Rastelli
 * @description Cadastrar tipo de resposta para pesquisa - HD 408341
 */

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$layout_menu = "cadastro";
	$admin_privilegios="cadastros";
	include 'autentica_admin.php';
	include 'funcoes.php';

	/* Request para excluir */
	if ( isset( $_GET['excluir'] ) ) {

		$tipo = (int) $_GET['excluir'];
		if(!empty($tipo)) {

			pg_query($con, "BEGIN TRANSACTION");

			$sql = "DELETE FROM tbl_tipo_resposta_item
					 WHERE tipo_resposta = $tipo;
					DELETE FROM tbl_tipo_resposta WHERE tipo_resposta = $tipo AND fabrica = $login_fabrica";

			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (empty($msg_erro)) {

				pg_query($con, "COMMIT");
				header('Location: ?msg='.traduz("Excluído com Sucesso").'');
				exit;

			} else if (strpos($msg_erro, 'foreign key')) {
					$msg_erro = traduz("Tipo de resposta está sendo utilizado e não pode ser excluído.");
					pg_query($con, "ROLLBACK");
			}

		}

	}
	/* Fim exclusao */

	/* Request GET */
	if ( isset($_GET['tipo_resposta']) ) {

		$tipo_resposta = (int) $_GET['tipo_resposta'];

		if (!empty($tipo_resposta)) {

			$sql = "SELECT tipo_resposta,
						descricao,
						ativo,
						label_inicio,
						label_fim,
						label_intervalo,
						tipo_descricao,
						peso,
						obrigatorio
					FROM tbl_tipo_resposta
					WHERE fabrica = $login_fabrica AND tipo_resposta = $tipo_resposta";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res)) {

				$descricao 		 = pg_result($res, 0, 'descricao');
				$tipo_input		 = pg_result($res, 0, 'tipo_descricao');
				$ativo 	 		 = pg_result($res, 0, 'ativo');
				$label_inicio	 = pg_result($res, 0, 'label_inicio');
				$label_fim		 = pg_result($res, 0, 'label_fim');
				$label_intervalo = pg_result($res, 0, 'label_intervalo');
				$obrigatorio 	 = pg_result($res, 0, 'obrigatorio');
				$peso 			 = pg_result($res, 0, 'peso');

				if($login_fabrica == 129){
					if($tipo_input == 'checkbox' OR $tipo_input == 'radio'){
						$donly = 'readOnly';
					}
				}
					
				$bloqueia_edicao = "";
				$bloqueia_edicao_class = "";
				if ($login_fabrica == 52) {
					$sql = "SELECT tbl_resposta.resposta 
							FROM tbl_tipo_resposta 
							JOIN tbl_pergunta ON tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta AND tbl_pergunta.fabrica = $login_fabrica
							JOIN tbl_resposta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_pergunta.fabrica = $login_fabrica
							WHERE tbl_pergunta.fabrica = $login_fabrica
							AND tbl_tipo_resposta.tipo_resposta = $tipo_resposta
							LIMIT 1";
					$res = pg_query($con, $sql);
					if (pg_num_rows($res) > 0) {
						$bloqueia_edicao = "readonly: readonly";
						$bloqueia_edicao_select = '	tabindex= "-1";	aria-disabled= "true";';
						$bloqueia_edicao_class = "bloqueia_edicao";
					}
				}

			} else {

				$tipo_resposta = null;

			}

		}

	}
	/* Fim request GET */

	/* Request para gravar */

	if( isset($_POST['enviar']) && $_POST['enviar'] == 'Gravar' ) {

		$descricao       = trim( $_POST['descricao'] );
		$ativo           = trim( $_POST['ativo'] );
		$tipo_resposta   = trim( $_POST['tipo_resposta'] );
		$tipo_input      = trim( $_POST['tipo_input'] );
		$peso            = trim( $_POST['peso'] );
		$obrigatorio     = trim( $_POST['obrigatorio'] );


		if($tipo_input == "range" && (strlen($_POST['inicio']) == 0 || strlen($_POST['fim']) == 0 || strlen($_POST['intervalo']) == 0)){
            $msg_erro = traduz("Complete todos os campos da escala");
		}else if($tipo_input != "range" && (strlen($_POST['inicio']) == 0 || strlen($_POST['fim']) == 0 || strlen($_POST['intervalo']) == 0)){
            $label_inicio    = "null";
            $label_fim       = "null";
            $label_intervalo = "null";
		}else{
            $label_inicio    = str_replace( ',', '.', trim($_POST['inicio']) );
            $label_fim       = str_replace( ',', '.', trim($_POST['fim']) );
            $label_intervalo = str_replace( ',', '.', trim($_POST['intervalo']) );
		}

		$peso = (strlen($peso) == 0) ? "null" : $peso;


		if (!in_array($tipo_input, array('radio','checkbox'))) {

			unset ($_POST['item']);

		} else {

			$count = 0;

			if($login_fabrica != 129){
				foreach ($_POST['item'] as $item) {

					$item = trim($item);
					if (empty($item))
						continue;
					$count++;
				}

				if ($count == 0 && !isset($_POST['tipo_resposta_item'])) {

					$msg_erro = 'Insira ao menos uma opção.';

				} else if (isset($_POST['tipo_resposta_item'])) {

					foreach ($_POST['tipo_resposta_item'] as $item => $v) {

						$sql = "UPDATE tbl_tipo_resposta_item SET descricao = '$v' WHERE tipo_resposta_item = $item AND tipo_resposta = $tipo_resposta";

						$res = pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);

					}

				}
			}else{
				foreach ($_POST['tipo_resposta_item'] as $item => $v) {
					$array_id_item[] = $item;
					$array_desc_item[] = $v;
					$array_peso_item[] = $_POST['tipo_resposta_peso_item'][$item];
				}

				if(count($_POST['tipo_resposta_item'] > 0)){

					for($x = 0; $x < count($_POST['tipo_resposta_item']); $x++){

						//$peso = (strlen($array_peso_item[$x] == 0)) ? "null" : $array_peso_item[$x];
						$peso = (strlen($array_peso_item[$x] == '')) ? 0 : $array_peso_item[$x]; //hd_chamado=2551668

						$peso = str_replace(",",".",$peso);
						$item = $array_id_item[$x];
						$desc_item = $array_desc_item[$x];

						$sql = "UPDATE tbl_tipo_resposta_item SET descricao = '".$desc_item."', peso = ".$peso." WHERE tipo_resposta_item = $item AND tipo_resposta = $tipo_resposta";
						$res = pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);
					}
				}else{

					$msg_erro = traduz('Insira ao menos uma opção.');
				}
			}

		}

		if (empty($descricao)) {

			$msg_erro = traduz("Digite a descrição.");

		} else if (empty($ativo)) {

			$msg_erro = traduz("Selecione a situação, ativo ou inativo.");

		} else if (empty($obrigatorio)) {

			$msg_erro = traduz("Selecione uma obrigatoriedade da resposta.");

		}

		if (empty($msg_erro)) {

			pg_query($con, "BEGIN TRANSACTION");

			if (!empty($tipo_resposta)) { // UPDATE

				$sql = "SELECT tipo_resposta,tipo_descricao FROM tbl_tipo_resposta WHERE tipo_resposta = $tipo_resposta";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)) {

					if($login_fabrica == 129){
						$tipo_input_aux   = pg_result($res, 0, 'tipo_descricao');
						if($tipo_input_aux == 'checkbox' OR $tipo_input_aux == 'radio'){
							$peso = 'null';
						}
					}
					// Faz update

					$sql = "UPDATE tbl_tipo_resposta
							SET descricao = '$descricao',
							ativo = '$ativo',
							tipo_descricao = '$tipo_input',
							label_inicio = $label_inicio,
							label_fim = $label_fim,
							label_intervalo = $label_intervalo,
							obrigatorio = '$obrigatorio',
							peso = $peso
							WHERE fabrica = $login_fabrica
							AND tipo_resposta = $tipo_resposta";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

				} else {

					$msg_erro = traduz("Tipo de Resposta não encontrado");
					unset($tipo_resposta);

				}

			} else { //INSERT

				$sql = "INSERT INTO tbl_tipo_resposta(fabrica,descricao,tipo_descricao,label_inicio,label_fim,label_intervalo,ativo,peso,obrigatorio)
						VALUES ($login_fabrica,'$descricao','$tipo_input',$label_inicio,$label_fim,$label_intervalo,'$ativo',$peso,'$obrigatorio')
						RETURNING tipo_resposta";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if (empty($msg_erro)) {
					$tipo_resposta = pg_result($res,0,0);
				}

			}

			$ordem = 0;

			if (!empty($_POST['item'])){

				if($login_fabrica != 129){
					foreach ($_POST['item'] as $value) {

						$value = trim($value);
						if (empty($value))
							continue;

						$sql = "SELECT tipo_resposta_item FROM tbl_tipo_resposta_item WHERE tipo_resposta = $tipo_resposta AND descricao = '$value'";
						$res = @pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);

						if (@pg_num_rows($res) > 0) {
							//@todo ordenar
						} else {
							$sql = "INSERT INTO tbl_tipo_resposta_item(tipo_resposta,descricao,ordem)
									VALUES($tipo_resposta,'$value',$ordem)";
							$res = @pg_query($con, $sql);
							$msg_erro .= pg_errormessage($con);
						}
						$ordem++;

					}
				}else{

					for($x = 0; $x < count($_POST['item']); $x++){

						$value = $_POST['item'][$x];
						$peso  = $_POST['peso_item'][$x];

						$peso        = str_replace(",",".",$peso);

						if(strlen($value) > 0){

							$sql = "SELECT tipo_resposta_item FROM tbl_tipo_resposta_item WHERE tipo_resposta = $tipo_resposta AND descricao = '$value'";
	            			$res = @pg_query($con, $sql);

							if(pg_num_rows($res) == 0){
								$peso = (strlen($peso) == 0) ? "null" : $peso;
								$sql = "INSERT INTO tbl_tipo_resposta_item(tipo_resposta,descricao,ordem,peso)
		                    			VALUES($tipo_resposta,'$value',$ordem,$peso)";
				          		$res = @pg_query($con, $sql);
			            		$msg_erro .= pg_errormessage($con);

							}
	            $msg_erro .= pg_errormessage($con);
						}
					}
				}
			}

			if (empty($msg_erro)) {

				$msg = traduz("Gravado com Sucesso");
				pg_query($con,"COMMIT");
				unset($tipo_resposta,$_POST['item'],$descricao,$ativo,$tipo_input,$label_inicio,$label_fim,$label_intervalo);
				header('Location: ?msg='.traduz("Gravado com Sucesso").'');

			} else
				pg_query($con, "ROLLBACK");

		}

	}

	/* Fim Request */

	/* Setar os selects para GET */
	if (!empty($ativo)) {
		$x_ativo 	= ($ativo == 't') ? 'selected' : FALSE;
		$inativo 	= ($ativo == 'f') ? 'selected' : FALSE;
	}

	/* Mensagem por GET */
	if (isset($_GET['msg'])  && !empty($_GET['msg']) ) {
		$msg = $_GET['msg'];
	}

	$title= traduz("CADASTRO DE TIPO DE RESPOSTA");
	include 'cabecalho.php';

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
.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
#tabela{display:none;}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.bloqueia_edicao {
	pointer-events: none;
}

#relatorio tr td{ cursor:pointer; }
#range, #options {display:none;}
</style>

<?php if(isset($msg_erro) && !empty($msg_erro)) { ?>
	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?> </div>
<?php } ?>
<?php if(isset($msg)) { ?>
	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?> </div>
<?php } ?>

<div class="formulario" style="width:700px; margin:auto;">

	<div class="titulo_tabela"><?=traduz('Cadastro')?></div>
	<form action="<?=$PHP_SELF?>" method="POST">
	<div style="padding:10px;">
		<table style="width:500px;margin:auto; text-align:left; border:none;">
			<tr>
				<td width="250">
					<label for="descricao"><?=traduz('Descrição')?></label><br />
					<input type="hidden" name="tipo_resposta" value="<?=$tipo_resposta;?>" />
					<input type="text" value="<?=$descricao;?>" size="35" id="descricao" name="descricao" class="frm" <?=$bloqueia_edicao?> />
				</td>
				<td>
					<label for="tipo"><?=traduz('Tipo')?></label><br />
					<select name="tipo_input" id="tipo" class="frm">

						<option value=""></option>
						<option value="text" <? echo $tipo_input == 'text' ? 'selected' : ''; ?> ><?=traduz('Caixa de Texto')?></option>
						<option value="date" <? echo $tipo_input == 'date' ? 'selected' : ''; ?> ><?=traduz('Data')?></option>
						<?php
							if(!in_array($login_fabrica,array(129))){
						?>
								<option value="range" <? echo $tipo_input == 'range' ? 'selected' : ''; ?> ><?=traduz('Escala')?></option>
						<?php
							}
						?>
						<option value="radio" <? echo $tipo_input == 'radio' ? 'selected' : ''; ?> ><?=traduz('Escolha Única')?></option>
						<option value="checkbox" <? echo $tipo_input == 'checkbox' ? 'selected' : ''; ?> ><?=traduz('Múltipla Escolha')?></option>
						<option value="textarea" <? echo $tipo_input == 'textarea' ? 'selected' : ''; ?>><?=traduz('Parágrafo')?></option>
					</select>
				</td>

				<?php
					if($login_fabrica == 129){
				?>
						<td>
							<label for="peso"><?=traduz('Peso')?></label><br />
							<input type="text" name="peso" id="peso" size="5" class="frm" value="<?php echo $peso ?>" <?php echo $donly ?> />
						</td>

				<?php
					}
				?>
				<td>
					<label for="ativo"><?=traduz('Ativo')?></label><br />
					<select name="ativo" id="ativo" class="frm">
						<option value=""></option>
						<option value="t" <? echo $x_ativo; ?>><?=traduz('Ativo')?></option>
						<option value="f" <? echo $inativo; ?>><?=traduz('Inativo')?></option>
					</select>
				</td>
				<td>
					<label for="obrigatorio"><?= traduz('Obrigatorio'); ?></label><br />
					<select name="obrigatorio" id="obrigatorio" class="frm <?=$bloqueia_edicao_class?>" <?=$bloqueia_edicao_select?>>
						<option value=""></option>
						<option value="t" <? echo $obrigatorio == 't'? 'selected="selected"':''; ?>><?=traduz('Sim')?></option>
						<option value="f" <? echo $obrigatorio == 'f'? 'selected="selected"':''; ?>><?=traduz('Não')?></option>
					</select>
				</td>
			</tr>
			<tr id="range">
				<td>
					<table>
						<td>

							<label for="inicio"><?= traduz('Início'); ?></label><br />
							<input type="text" name="inicio" class="frm" size="4" id="inicio" value="<?=$label_inicio?>" <?=$bloqueia_edicao?>/>
						</td>
						<td>
							<label for="fim"><?= traduz('Fim'); ?></label><br />
							<input type="text" name="fim" class="frm" size="4" id="fim" value="<?=$label_fim?>" <?=$bloqueia_edicao?>/>
						</td>
						<td>
							<label for="intervalo"><?= traduz('Intervalo'); ?></label><br />
							<input type="text" name="intervalo" class="frm" size="4" id="intervalo" value="<?=$label_intervalo?>" <?=$bloqueia_edicao?>/>
						</td>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<table id="options" style="width:500px; margin:auto;">
						<tr bgcolor="#F7F5F0">
							<td nowrap>
							<?php
								if($login_fabrica != 129){
									if ($login_fabrica == 52) {
										if (empty($bloqueia_edicao)) {
							?>
											<input type="text" value="<?=$_POST['item'][0] ?>" name="item[0]" size="50" class="frm" />&nbsp;<input type="button" value="Adicionar" onclick="addDefeito()" />
							<?			
										}
									} else {
							?>
										<input type="text" value="<?=$_POST['item'][0] ?>" name="item[0]" size="50" class="frm" />&nbsp;<input type="button" value="Adicionar" onclick="addDefeito()" />
							<?php
									}
								}else{
							?>
								<input type="text" style="width:375px;" value="<?=$_POST['item'][0] ?>" name="item[0]" size="50" class="frm" />&nbsp;<input type="text" name="peso_item[0]" size="5" class="frm" value="<?=$_POST['peso_item'][0]?>" <?=$bloqueia_edicao?>>&nbsp;<input type="button" value="Adicionar" onclick="addDefeito()" />

							<?php
								}
							?>
							</td>
						</tr>
						<?php
							for ($i = 1; $i < count($_POST['item']); $i++) {

								if (empty($_POST["item"][$i]))
									continue;

								$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

								if ($i == 0) {
									$button = '<input type="button" value="'.traduz('Adicionar').'" onclick="addDefeito()" />';
								}
								else {
									$button = "<button onclick=\"deletaitem($i)\">".traduz("Remover")."</button>";
								}

								if ($login_fabrica == 52 && !empty($bloqueia_edicao)) {
									$button = "";
								}

								if($login_fabrica != 129){
								echo '<tr bgcolor="'.$cor.'">
										<td nowrap>
											<input type="text" value="'.$_POST["item"][$i].'" name="item['.$i.']" size="50" class="frm" '.$bloqueia_edicao.'/>&nbsp;'.$button.'
										</td>
									  </tr>';
								}else{
									echo '<tr bgcolor="'.$cor.'">
                                                                                <td nowrap>
                                                                                        <input type="text"  value="'.$_POST["item"][$i].'" name="item['.$i.']" size="50" class="frm" />&nbsp;
											<input type="text" value="'.$_POST["peso_item"][$i].'" name="peso_item['.$i.']" size="5" class="frm" />&nbsp;'.$button.'
                                                                                </td>
                                                                          </tr>';
								}

							}
							if ($tipo_resposta) {
								$sql = "SELECT * FROM tbl_tipo_resposta_item
										WHERE tipo_resposta = $tipo_resposta";
								$res = pg_query($con,$sql);

								for ($i=0;$i<pg_num_rows($res); $i++) {

									$tipo_resposta_item = pg_result($res,$i,'tipo_resposta_item');
									$descricao_resposta_item = pg_result($res,$i,'descricao');
									$peso = pg_result($res,$i,'peso');
									$button = "<button onclick=\"deletaRespostaItem($tipo_resposta_item,$tipo_resposta); return false;\">".traduz("Remover")."</button>";

									if ($login_fabrica == 52 && !empty($bloqueia_edicao)) {
										$button = "";
									}

									$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

									if($login_fabrica != 129){
									echo '<tr bgcolor="'.$cor.'" id="'.$tipo_resposta_item.'">
											<td nowrap>
												<input type="text" value="'.$descricao_resposta_item.'" name="tipo_resposta_item['.$tipo_resposta_item.']" size="50" class="frm" '.$bloqueia_edicao.'/>&nbsp;'.$button.'
											</td>
										  </tr>';
									}else{
										$peso = str_replace('.', ',', $peso);
										echo '<tr bgcolor="'.$cor.'" id="'.$tipo_resposta_item.'">
                                     <td nowrap>
                                             <input type="text" style="width:375px;" value="'.$descricao_resposta_item.'" name="tipo_resposta_item['.$tipo_resposta_item.']" size="50" class="frm" />&nbsp;
															<input type="text"  value="'.$peso.'" name="tipo_resposta_peso_item['.$tipo_resposta_item.']" size="5" class="frm" />&nbsp;'.$button.'
                                     </td>
                               </tr>';
									}

								}
							}
						?>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" name="enviar" value="Gravar"  />
					<?php
						if ($login_fabrica <> 52 || empty($bloqueia_edicao)) {
					?>
							<?php if(!empty($tipo_resposta)) { ?>
								&nbsp;&nbsp;<input type="button" name="limpar" value="<?= traduz('Limpar'); ?>" />
								&nbsp;&nbsp;<input type="button" name="excluir" id="<?=$tipo_resposta?>" value="<?= traduz('Excluir'); ?>" />
							<? }
						}
					?>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div><?php

	$sql = "SELECT  tipo_resposta,
                    descricao,
                    tipo_descricao,
                    label_inicio,
                    label_fim,
                    label_intervalo,
                    CASE WHEN ativo IS TRUE
                         THEN 'Ativo'
                         ELSE 'Inativo'
                    END AS ativo,
		    		peso,
		    		obrigatorio
			FROM    tbl_tipo_resposta
			WHERE   fabrica = $login_fabrica
      ORDER BY tipo_resposta
    ";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {?>
		<br />
		<table class="tabela" width="700" align="center" cellspacing="1" id="relatorio">
			<tr class="titulo_coluna">
				<th><?=traduz('Ativo')?></th>
				<th nowrap><?=traduz('Descrição')?></th>
				<th><?=traduz('Tipo Resposta')?></th>
			<?php
				if($login_fabrica == 129){
					echo "<th>".traduz("Peso")."</th>";
					echo "<th>".traduz("Obrigatorio")."</th>";
				}elseif(in_array($login_fabrica,array(145,161))){
					echo "<th>".traduz("Obrigatorio")."</th>";
				}else{
				?>
					<th><?=traduz('Início')?></th>
					<th><?=traduz('Fim')?></th>
					<th><?=traduz('Intervalo')?></th>
					<th><?=traduz('Obrigatorio')?></th>
				<?
				}
			?>
			</tr>
			<tbody><?php
				for ($i = 0; $i < pg_num_rows($res) ; $i++) {

					$xdescricao     = pg_result($res, $i, 'tipo_descricao');
					$xativo         = pg_result($res, $i, 'ativo');
					$xtipo_resposta = pg_result($res, $i, 'tipo_resposta');

					switch ($xdescricao) {
						case 'range'    : $xcategoria = traduz('Escala');           break;
						case 'textarea' : $xcategoria = traduz('Parágrafo');        break;
						case 'radio'    : $xcategoria = traduz('Escolha Única');    break;
						case 'text'     : $xcategoria = traduz('Caixa de Texto');   break;
						case 'date'     : $xcategoria = traduz('Data');             break;
						case 'checkbox' : $xcategoria = traduz('Múltipla Escolha'); break;
					}

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                    $img_src = ($xativo == 'Ativo') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";
?>

					<tr bgcolor="<?=$cor?>" id="<?=$xtipo_resposta?>">
						<td align="center">&nbsp;<img src="<?echo $img_src?>" alt=""></td>
						<td align="left">&nbsp;<?=pg_result($res,$i, 'descricao')?></td>
						<td><?=$xcategoria?></td>
						<?php
							if(pg_result($res,$i,'obrigatorio') == 't'){
								$obrigatorio = traduz('Sim');
							}else{
								$obrigatorio = traduz('Não');
							}
						if(in_array($login_fabrica,array(129))){
							echo "<td>&nbsp;".pg_result($res,$i,'peso')."</td>";
							echo "<td>&nbsp;".$obrigatorio."</td>";
						}elseif(in_array($login_fabrica,array(145,161))){
						?>
							<td>&nbsp;<?=$obrigatorio?></td>
						<?php
						}else{
						?>
							<td>&nbsp;<?=pg_result($res,$i,'label_inicio')?></td>
							<td>&nbsp;<?=pg_result($res,$i,'label_fim')?></td>
							<td>&nbsp;<?=pg_result($res,$i,'label_intervalo')?></td>
							<td>&nbsp;<?=$obrigatorio?></td>
						<?php
						}

						?>
					</tr><?php

				}?>
			</tbody>
		</table><?php

}?>
<!-- <script type="text/javascript" src="js/jquery.js"></script> -->
<script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script>

<script type="text/javascript">
	var login_fabrica = <?=$login_fabrica?>;
	i = 1;

	function addDefeito() {

		var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

		if(login_fabrica != 129){
			var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="text" value="" name="item[]" size="50" class="frm" />&nbsp;<button onclick="deletaitem('+i+')">Remover</button></td></tr>';
		}else{
			var htm_input = '<tr id="'+i+'" bgcolor="'+cor+'"><td><input type="text" style="width:375px;" value="" name="item[]" size="50" class="frm" />&nbsp;<input type="text" name="peso_item[]" size="5" class="frm">&nbsp;<button onclick="deletaitem('+i+')">Remover</button></td></tr>';
		}
		i++;
		$(htm_input).appendTo("#options");

	}

	function deletaitem(id) {

		$("#"+id).remove();

	}

	function verificaTipo() {

		var val = $("#tipo").val();

		$("tr#range, #options").hide();

		if (val === 'range') {

			$("tr#range").show();

		} else if (val === 'checkbox' || val === 'radio') {

			$("#options").show();

		}

	}

	function deletaRespostaItem(item, resposta) {

		if (confirm ('<?=traduz("Deseja mesmo excluir essa opção de resposta ?")?>') ) {
			$.get('cadastro_tipo_resposta_ajax.php?deleta=t&item='+item+'&tipo_resposta='+resposta, function(data){
				if ( data === 't' ) {

					alert('<?=traduz("Opção excluída com sucesso")?>');
					$("tr#"+item).remove();

				} else {
					alert('<?=traduz("Item já foi respondido na pesquisa, portanto não pode ser excluído.")?>');
				}
			});
		}
		return false;

	}

	$().ready(function(){

    $("input[name*=tipo_resposta_peso_item]").maskMoney({symbol:"", decimal:",", thousands:'', precision:2, maxlength: 15});


		$("#inicio, #fim, #intervalo").numeric({allow: ',.'});

		$("#relatorio > tbody > tr").click(function(){

			tipo = $(this).attr('id');
			window.location = '?tipo_resposta=' + tipo;

		});

		<?php if(!empty($tipo_resposta)) { ?>
			$("input[name=excluir]").click(function(e) {

				if (confirm ('<?=traduz("Deseja mesmo excluir esse tipo de pergunta?")?>' )) {
					window.location = '?excluir=' + $(this).attr('id');
				}
				else return false;

				e.preventDefault();

			});

			$("input[name=limpar]").click(function(e) {

				e.preventDefault();
				window.location = 'cadastro_tipo_resposta.php';

			});
		<?php } ?>

		verificaTipo();

		$("#tipo").change(function(){

			verificaTipo();

		});

		$("#tipo").change(function(){
			var tipo = $(this).val();
			if(tipo == "checkbox" || tipo == "radio"){
				$("#peso")[0].readOnly = true;
			}else{
				$("#peso")[0].readOnly = false;
			}
		});

	});
</script>

<?php include 'rodape.php'; ?>
