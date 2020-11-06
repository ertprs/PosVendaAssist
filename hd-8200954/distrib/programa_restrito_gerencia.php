<?php

include "dbconfig.php";
// $dbnome = "teste";          // ATÉ FUNCIONAR LEGAL VAI FICAR MESMO NO TESTE!!!!
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include 'autentica_usuario.php';
include 'menu.php';

//  Erros na hora de criar ou excluir uma restrição:
$erros_restricao = array(0 => "Erro ao gravar informações no banco de dados",
						 1 => "Operação realizada com sucesso",
						 2 => "Já existe restrição para este programa",
						 3 => "O programa não está restrito",
						 4 => "O usuário informado não pertence a este fabricante",
						 5 => "Sem restrições!",
						 6 => "Já existe restrição para algum dos programas",
						 7 => "Algum desses programas não está restrito"
						 );

function iif($condition, $val_true, $val_false = "") {
	if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
	if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
	return ($condition) ? $val_true : $val_false;
}

/**
 * Descrição da função
 *
 * @param int $admin
 * @param string $programa
 * @return boolean
 */

function esta_restrito($login_unic,$programa) {
	
    $sql = "SELECT programa FROM tbl_programa_restrito WHERE posto=$login_unico_posto AND login_unico=$login_unico AND programa='$programa'";
	$resp= @pg_query($con, $sql);
	return (@pg_num_rows($resp)==1);
}

function restringir($login_unic,$programa) {
	global $con, $login_fabrica;
	if (esta_restrito($login_unic,$programa)) return 2;
	if ($programa == "null") {
		return 1;
	}
    $sql = "INSERT INTO tbl_programa_restrito (fabrica, programa, login_unico) VALUES ($login_fabrica, '$programa',$login_unico)";
	$res = @pg_query($con, $sql);
	return (pg_affected_rows($res)==1) ? 0 : 1;
}

/****************************************************************************************************
 *  Esta função por enquanto não vai ser usada, pois a rotina de atualização apaga as restrições    *
 *  atuais para inserir as novas, não apaga. Mas se precisar fazer que o botão excluir trabalhe     *
 *  direto, a função já existe e é só fazer uma de ajax=excluir com acao=liberar e nome=programa    *
 ***************************************************************************************************/
function liberar($login_unic,$programa) {
	global $con;
	if ($programa != 'tudo') if (!esta_restrito($admin,$programa)) return 3;
	$cond_prog = ($programa == 'tudo') ? "" : "programa = '$programa' AND";
    $sql = "DELETE FROM tbl_programa_restrito WHERE $cond_prog login_unico = $login_unic";
	$res = @pg_query($con, $sql);
	return (is_bool($res)) ? 1 : 0;
}

// Funções do banco para iniciar e concluir transações
// Quando fizer testes, mudar a função 'commit' para o 'rollback' ;)
function begin()	{global $con;return pg_query($con, "BEGIN TRANSACTION");}
// function commit()	{global $con;return pg_query($con, "ROLLBACK TRANSACTION");}
function commit()	{global $con;return pg_query($con, "COMMIT TRANSACTION");}
function rollback()	{global $con;return pg_query($con, "ROLLBACK TRANSACTION");}

function atualiza_restricao($login_unic, $fabrica, $programa, $acao='restringir', $stopOnError=false) {
	global $con, $login_fabrica;
	if (!is_array($programa)) {
	    $prog = $programa;
	    begin();
		if (($resultado = $acao($login_unic,$programa))==0) { // Chama à função 'restringir' ou 'liberar' dependendo do valor de $acao
			commit();
		} else {
			rollback();
		}
		return $resultado;
	} else {    // Em caso de ter vários programas ao mesmo tempo para liberar/restringir
	    begin();
		foreach ($programa as $prog) {
			$resultado = $acao($login_unic,$prog);
// 		    echo "$acao ($admin,'$prog') = $resultado\n";
			if ($resultado == 1) {rollback(); return 1;}    // Erro ao gravar dados no banco, tem que parar...
		    if ($resultado != 0 and $stopOnError) {rollback(); return $resultado + 4;}   // Erro em caso de array
  		}
		commit();
		return 0;
	}   //  Fim da conferência para saber e algum não pode ser processado
}

if (strlen($ajax = $_REQUEST['ajax'])>0) {  //Seta a variável e confere o length para saber se é uma requisição ajax...

/*  Listado de usuários distrib   */
	if ($ajax=='usuarios') {    // Devolve a lista de admins do posto pelo login_unico, por ordem alfabética de NOME COMPLETO pronta para inserir no SELECT
		$sql = "SELECT login_unico,nome FROM tbl_login_unico WHERE posto=$login_unico_posto AND ativo IS TRUE ORDER BY nome";
		$resa= @pg_query($con, $sql);
		if ($resa !== false) {
			$numads = @pg_num_rows($resa);
			if ($numads == 0) {
				echo "5";
				exit;
			}
			$admins = @pg_fetch_all($resa);
			echo "<option value='".$_POST['editar_admin']."'></option>\n";
			foreach ($admins as $adminInfo) {
				$edit_admin = ($_REQUEST['editar_admin'] == $adminInfo['login_unico'])?" SELECTED":"";
				echo "<option value='".$adminInfo['login_unico']."'$edit_admin>".$adminInfo['nome']."</option>\n";
			}
			exit;
		}
	}

/*  Listado de programas restritos para esses admin */
	if ($ajax=='programas') {
	    if (strlen($admin = trim($_REQUEST['admin']))==0) {
			//echo "KO|Bad request!";
			exit;
		}
	    $sql = "SELECT programa FROM tbl_programa_restrito WHERE fabrica=$login_fabrica AND admin=$admin ORDER BY programa";
		$resp= @pg_query($con, $sql);
		if ($resp !== false) {
			$nprogs = @pg_num_rows($resp);
			if ($nprogs == 0) {
				echo "OK|".$erros_restricao[5];
				exit;
			}
			$progs = @pg_fetch_all($resp);
			foreach ($progs as $programa) {
			    $prog_c = $programa['programa'];
			    $prog_n = substr(strrchr($prog_c,"/"),1);
			    $prog_n = ucwords(strtr(substr($prog_n,0,strpos($prog_n,".")),"_"," "));
			    $prog_n = str_replace("Os ", "OS ", $prog_n);
				echo "<option value='$prog_c'>$prog_n</option>\n";
			}
			exit;
		}
	}

/**
 * Atualiza as restrições com o conteúdo do <SELECT> #programas para o admin #admin
 *
 * @param	int				$admin
 * @param	string/array	$programas
 * @return	ERRORLEVEL
 * @author:	Manuel López <manolo@telecontrol.com.br>
 * Requires:begin(), commit(), rollback(), atualiza_restricao(), iif()
 */
	if ($ajax=='atualiza') {
	    if (!isset($_POST['admin'])) {
			echo "KO|Bad request!";
			exit;
		}
	    $admin = $_POST['admin'];
		$progs = $_POST['programas'];
// 	    echo "KO|Var: $progs\nArray: "; var_dump($progs);
// 	    exit;

		if (!is_array($progs)) {
		    begin();
			if (!file_exists("/var/www$progs")) {
			    echo "KO|Não existe o programa $progs!! Quem é você?!";
			    exit;
			}
			$atualizou = atualiza_restricao($admin,$login_fabrica,$progs);
			if ($atualizou == 0) {
				echo 'OK|Atualizado restrições com sucesso!';
			} else {
				echo 'KO|Erro ao ' , $acao , ' o acesso à tela: ' , $progs , '.';
			}
		} else {
			liberar($admin,"tudo");
			$atualizou = atualiza_restricao($admin,$login_fabrica,$progs);
			if ($atualizou == 0) {
				echo 'OK|Atualizado restrições com sucesso!';
				commit();
			} else {
				echo 'KO|Erro ao ' , $acao , ' o acesso às telas selecionadas.';
				rollback();
			}
		}
		exit;
	}
	
//  Chama com ajax=apagar & admin = #usuario.value
	if ($ajax=='apagar') {
		$admin = $_REQUEST['admin'];
		if ($admin != '') {
			begin();
			$retorno = liberar($admin, "tudo");
			if ($retorno == 0) {
				commit();
				echo "OK|Todas as restrições para este usuário foram excluídas!";
			} else {
			    echo "KO|Erro ao excluir as restrições deste usuário!";
			}
			exit;
		} else {
			echo "KO|Usuário não encontrado.";
		}
	}
}

$title = "Restrição de Acesso - Gerenciamento";
$cabecalho = "Restrição de Acesso";
$layout_menu = "gerencia";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: white
}

	form {
	position: relative;
	width: 700px;
	height: 300px;
	background-color: #fee;
	padding: 0
	}
	fieldset {
		position: absolute;
		top: 15px;
		left: 20px;
		float: left;
		clear: none;k
		text-align: left;
		background-color: white;
		width: 300px;
		height: 250px;
	}

fieldset + fieldset {left: 360px}

select,input {
	font-family: tahoma,arial,helvetica,sans-serif;
	font-size: 11px;
	width: 40ex;
	display: block;
	margin-left: 4em;
	margin-bottom: 1.2em;
}
select option {overflow-x: hidden;}
.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
}

</style>

<script type='text/javascript' src='../js/jquery.js'></script>
<script type="text/javascript" src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js'></script>
<script type="text/javascript">
$().ready(function() {
	phpSelf = '<?=$PHP_SELF?>'; /* ;)   */
	lendo = '<option></option><option></option><option value="">Atualizando...</option>';

	var edit_admin = '<?=$_GET['edit_admin']?>';

	$.post(phpSelf,{'ajax':'usuarios','editar_admin':edit_admin},function(data) {
	    if (data.indexOf('|') > 0) {
	        info = data.split("|");
	        //alert(info[1]+'aaaaa');
		} else {
		    $('#admin').html(data);
		    if ($('#admin').val() != "") $('#admin').change();
		    $('#org').html(data).val('');
	    }
	});

	$('#admin').change(function() {
		user = $('#admin').val();
		$('#programas').html(lendo);
		$.post(phpSelf,{'ajax':'programas','admin':user},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        if (info[0] == "KO") alert(info[1]);
				$('#programas').html('');
		    } else {
				$('#programas').html(data);
			}
		});
	});

	$('#org').change(function() {
		user = $('#org').val();
		$('#restricoes').html(lendo);
		$.post(phpSelf,{'ajax':'programas','admin':user},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        alert(info[1]);
				$('#restricoes').html('');
		    } else {
				$('#restricoes').html(data);
			}
		});
	});

/*	$('#programas').change(function () {
		$('fieldset[for=usuario] button').removeAttr('disabled');
	});

	$('#restricoes').change(function () {
		$('fieldset[for=restricoes] button').removeAttr('disabled');
	});
*/
	$('#kill_all').click(function() {
		user = $('#admin').val();
	    $.post(phpSelf,{'ajax':'apagar','admin':user},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        alert(info[1]);
			    $('#admin').change();
			} else alert ('erro de AJAX!'+data);
		});
	});
    $('#atualiza').click(function() {
		user = $('#admin').val();
		$('#programas option').attr('selected','selected');
		var progs= $('#programas').val();
	    $.post(phpSelf,{'ajax':'atualiza','admin':user,'programas[]':progs},function(data) {
		    if (data.indexOf('|') > 0) {
		        info = data.split("|");
		        alert(info[1]);
			} else {
			    $('#admin').change();
			}
		});
	});

    $('#excl_sel').click(function() {
		$('#programas option:selected').remove();
	});

    $('#reset_prg').click(function() {$('#admin').change();});
    $('#reset_rs').click(function() {$('#org').change();});

    $('#copy_sel').click(function() {
		opcoes = $('#restricoes option:selected');
		for (i = 0; i < opcoes.length; i++) {
		    valorOpcao = opcoes[i].value;
		    if ($('#programas').html().indexOf(valorOpcao) == -1) {
		 		$('#programas').append(opcoes[i]);
			}
		}
	});
	$('#copy_all').click(function() {
		opcoes = $('#restricoes option');
		for (i = 0; i < opcoes.length; i++) {
		    valorOpcao = opcoes[i].value;
		    if ($('#programas').html().indexOf(valorOpcao) == -1) {
		 		$('#programas').append(opcoes[i]);
			}
		}
	});
}); // FIM jQuery
</script>

<br><br>
<center>
<form class='frm'>
	<fieldset for='restricoes'>
		<legend>Copiar restri&ccedil;&otilde;es de: <?=$banco_de_testes?></legend>
		<label for='org'>Escolha o usu&aacute;rio</label>
		<select name='org' id='org'>
			<option></option>
		</select>
		<label for='programas'>Este usuário tem restrição para as seguintes telas:</label><br>
        <select title="Restrições ativas para este usuário" class="lista"
			 	 name="restricoes" id="restricoes" size="10" multiple>
			<option></option>
        </select>
        <button type='button' id='copy_all' title='Copia todas as restrições para o usuário'		style='float: right'>Copiar Tudo</button>
        <button type='button' id='copy_sel' title='Copia as restrições selecionadas para o usuário'	style='float: right'>Copiar</button>
        <button type='button' id='reset_rs' title='Recarrega as restrições para o usuário selecionado'>Recarregar</button>
	</fieldset>

	<fieldset for='usuario'>
		<legend>Editar usu&aacute;rio<?=$banco_de_testes?></legend>
		<label for='usuario'>Escolha o usu&aacute;rio:</label>
		<select name='admin' id='admin'>
			<option></option>
		</select>
		<label for='programas'>Este usuário tem restrição para as seguintes telas:</label><br>
        <select title="Restrições ativas para este usuário" class="lista"
			 	 name="programas[]" id="programas" size="10" multiple>
			<option></option>
        </select>
        <button type='button' id='excl_sel'  title='Exlcui as restrições selecionadas' style="width: 80px;">Excluir</button>
        <button type='button' id='atualiza'  title='Atualiza as alterações' style="float: right; width: 155px;">Atualizar Restrições</button><br/>
        <button type='button' id='reset_prg' title='Anula as alterações NÃO SALVAS' style="width: 80px;">Cancelar</button>
		<button type='button' id='kill_all'  title='Apaga todas as restrições deste usuário' style="float: right; width: 155px;">Apagar Restrições</button>
	</fieldset>
</form>
</center>
<? include "rodape.php"; ?>
