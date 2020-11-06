<?
include "dbconfig.php";
// $dbnome = "teste";          // AT� FUNCIONAR LEGAL VAI FICAR MESMO NO TESTE!!!!
include "includes/dbconnect-inc.php";
if ($dbnome=='teste') $banco_de_testes = "  (BANCO DE TESTE!!)";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

//  Erros na hora de criar ou excluir uma restri��o:
$erros_restricao = array(0 => "Erro ao gravar informa��es no banco de dados",
						 1 => "Restri��es Gravadas com Sucesso!",
						 2 => "J� existe restri��o para este programa",
						 3 => "O programa n�o est� restrito",
						 4 => "O usu�rio informado n�o pertence a este fabricante",
						 5 => "Sem restri��es!",
						 6 => "J� existe restri��o para algum dos programas",
						 7 => "Algum desses programas n�o est� restrito"
						 );

function iif($condition, $val_true, $val_false = "") {
	if (is_numeric($val_true) and is_null($val_false)) $val_false = 0;
	if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) return null;
	return ($condition) ? $val_true : $val_false;
}

/**
 * Descri��o da fun��o
 *
 * @param int $admin
 * @param string $programa
 * @return boolean
 */
function esta_restrito($admin,$programa) {
	global $con, $login_fabrica;
    $sql = "SELECT programa FROM tbl_programa_restrito WHERE fabrica=$login_fabrica AND admin=$admin AND programa='$programa'";
	$resp= @pg_query($con, $sql);
	return (@pg_num_rows($resp)==1);
}

function restringir($admin,$programa) {
	global $con, $login_fabrica;
	if (esta_restrito($admin,$programa)) return 2;
    $sql = "INSERT INTO tbl_programa_restrito (fabrica, programa, admin) VALUES ($login_fabrica, '$programa',$admin)";
	$res = @pg_query($con, $sql);
	return (pg_affected_rows($res)==1) ? 0 : 1;
}

/****************************************************************************************************
 *  Esta fun��o por enquanto n�o vai ser usada, pois a rotina de atualiza��o apaga as restri��es    *
 *  atuais para inserir as novas, n�o apaga. Mas se precisar fazer que o bot�o excluir trabalhe     *
 *  direto, a fun��o j� existe e � s� fazer uma de ajax=excluir com acao=liberar e nome=programa    *
 ***************************************************************************************************/
function liberar($admin,$programa) {
	global $con;
	if ($programa != 'tudo') if (!esta_restrito($admin,$programa)) return 3;
	$cond_prog = ($programa == 'tudo') ? "" : "programa = '$programa' AND";
    $sql = "DELETE FROM tbl_programa_restrito WHERE $cond_prog admin = $admin";
	$res = @pg_query($con, $sql);
	return (is_resource($res)) ? 1 : 0;
}

// Fun��es do banco para iniciar e concluir transa��es
// Quando fizer testes, mudar a fun��o 'commit' para o 'rollback' ;)
function begin()	{global $con;return pg_query($con, "BEGIN TRANSACTION");}
// function commit()	{global $con;return pg_query($con, "ROLLBACK TRANSACTION");}
function commit()	{global $con;return pg_query($con, "COMMIT TRANSACTION");}
function rollback()	{global $con;return pg_query($con, "ROLLBACK TRANSACTION");}

function atualiza_restricao($admin, $fabrica, $programa, $acao='restringir', $stopOnError=false) {
	global $con, $login_fabrica;
	if (!is_array($programa)) {
	    $prog = $programa;
	    begin();
		if (($resultado = $acao($admin,$programa))==0) { // Chama � fun��o 'restringir' ou 'liberar' dependendo do valor de $acao
			commit();
		} else {
			rollback();
		}
		return $resultado;
	} else {    // Em caso de ter v�rios programas ao mesmo tempo para liberar/restringir
	    begin();
		foreach ($programa as $prog) {
			$resultado = $acao($admin,$prog);
// 		    echo "$acao ($admin,'$prog') = $resultado\n";
			if ($resultado == 1) {rollback(); return 0;}    // Erro ao gravar dados no banco, tem que parar...
		    if ($resultado != 0 and $stopOnError) {rollback(); return $resultado + 4;}   // Erro em caso de array
  		}
		commit();
		return 1;
	}   //  Fim da confer�ncia para saber e algum n�o pode ser processado
}

if (strlen($ajax = $_REQUEST['ajax'])>0) {  //Seta a vari�vel e confere o length para saber se � uma requisi��o ajax...

/*  Listado de usu�rios admin   */
	if ($ajax=='usuarios') {    // Devolve a lista de admins do fabricante, por ordem alfab�tica de NOME COMPLETO pronta para inserir no SELECT
		$sql = "SELECT admin,login FROM tbl_admin WHERE fabrica=$login_fabrica AND ativo IS TRUE ORDER BY nome_completo";
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
				$edit_admin = ($_REQUEST['editar_admin'] == $adminInfo['admin'])?" SELECTED":"";
				echo "<option value='".$adminInfo['admin']."'$edit_admin>".$adminInfo['login']."</option>\n";
			}
			exit;
		}
	}

/*  Listado de programas restritos para esses admin */
	if ($ajax=='programas') {
	    if (strlen($admin = trim($_REQUEST['admin']))==0 ) {
			echo "KO|Bad request!";
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
 * Atualiza as restri��es com o conte�do do <SELECT> #programas para o admin #admin
 *
 * @param	int				$admin
 * @param	string/array	$programas
 * @return	ERRORLEVEL
 * @author:	Manuel L�pez <manolo@telecontrol.com.br>
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
			    echo "KO|N�o existe o programa $progs!! Quem � voc�?!";
			    exit;
			}
			$atualizou = atualiza_restricao($admin,$login_fabrica,$progs);
			echo iif($atualizou==1,"OK|","KO|Erro ao $acao o acesso � tela $progs: ").$erros_restricao[$atualizou];
		} else {
			liberar($admin,"tudo");
			$atualizou = atualiza_restricao($admin,$login_fabrica,$progs);
			echo iif($atualizou==1,"OK|","KO|Erro ao $acao o acesso �s telas selecionadas: ").$erros_restricao[$atualizou];
		}
		if ($atualizou==1) { // Chama � fun��o 'restringir' ou 'liberar' dependendo do valor de $acao
			commit();
		} else {
			rollback();
		}
		exit;
	}
	
//  Chama com ajax=apagar & admin = #usuario.value
	if ($ajax=='apagar') {
		$admin = $_REQUEST['admin'];
		if ($admin != '') {
			if (liberar($admin,"tudo")==0) {
				rollback(); // Aqui vai um commit(), mas por enquanto s� quero ver se tem algum erro nas rotinas... ;)
				return "OK|Todas as restri��es para este usu�rio foram exclu�das!";
			} else {
			    rollback();
				return "KO|Erro ao excluir as restri��es deste usu�rio!";
			}
		}
	}
}

$title = "GERENCIAMENTO DE PERMISS�ES DE ACESSO";
$cabecalho = "Restri��o de Acesso";
$layout_menu = "gerencia";
include 'cabecalho.php';
?>

<style type="text/css">

select,input {
	font-family: tahoma,arial,helvetica,sans-serif;
	font-size: 11px;
	width: 50ex;
	display: block;
	
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

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<script type='text/javascript' src='/assist/js/jquery-1.3.2.js'></script>
<script type="text/javascript" src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js'></script>
<script type="text/javascript">
$().ready(function() {
	phpSelf = '<?=$PHP_SELF?>'; /* ;)   */
	lendo = '<option value="">Atualizando...</option>';

	var edit_admin = '<?=$_GET['edit_admin']?>';

	$.post(phpSelf,{'ajax':'usuarios','editar_admin':edit_admin},function(data) {
	    if (data.indexOf('|') > 0) {
	        info = data.split("|");
	        alert(info[1]);
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
		        if (info[0] == "KO") {
					document.getElementById("atualiza").disabled = true;
					document.getElementById("excl_sel").disabled = true;
				}
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
		       // alert(info[1]);
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
		var de = $("#org option:selected").text().toUpperCase();
		var para = $("#admin option:selected").text().toUpperCase();
//		var para = document.getElementById("admin").options[document.getElementById("admin").selectedIndex].text().toUpperCase();
	
		if(confirm('ATEN��O!!! Voc� est� Copiando Permiss�es de Acesso do Usu�rio '+de+' para o Usu�rio '+para+'. Deseja Continuar?')){
			user = $('#admin').val();
			$('#programas option').attr('selected','selected');
			var progs= $('#programas').val();
			$.post(phpSelf,{'ajax':'atualiza','admin':user,'programas[]':progs},function(data) {
				if (data.indexOf('|') > 0) {
					info = data.split("|");
					$('#erro').css('display','block');
					$('#erro').html(info[1]);
				   // alert(info[1]);
				} else {
					$('#admin').change();
				}
			});
		}
		
	});

    $('#excl_sel').click(function() {
		$('#programas option:selected').remove();
	});
   /* $('#reset_prg').click(function() {$('#admin').change();});
    $('#reset_prg').click(function() {$('#org').change();});
	*/
    $('#copy_sel').click(function() {
		$('#restricoes option:selected').each(function(idx, opcao) {
			$('#restricoes option:selected').css('color','#090');
			var valorOpcao = opcao.value;
		    if ($('#programas').html().indexOf(valorOpcao) == -1) {
				$(this).clone().css('color', '#FF0000').removeAttr('selected').appendTo("#programas");
			}
		});
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

function habilitaBotao(){
		
		document.getElementById("atualiza").disabled = false;
		document.getElementById("excl_sel").disabled = false;
		
		
	}
</script>
<style type='text/css'>
	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
</style>
<div style='width:700px;' class='texto_avulso'>
	Esta tela permite que voc� copie os <b>"Privil�gios de Acesso"</b> de um usu�rio para outro. <br>Assim, quando voc� copiar um programa para outro usu�rio estar� incluindo o mesmo em um grupo <br>restrito de usu�rios que poder� acessar esta tela.
</div>
<br />
<form  align='center' >
<center><div id='erro' style='display:none;width:698px;' class='sucesso'></div></center>
	<table align='center' width='700' class='formulario'>
		<tr class='titulo_tabela' height='25' valign='middle'><td colspan='3'>C�pia de Privil�gios de Acesso</td></tr>
		<tr>
			<td width='325' >
				<fieldset for='restricoes' style='width:300px;'>
					<legend>Copiar permiss�es do usu�rio: <?=$banco_de_testes?></legend>
					<select name='org' id='org' >
						<option></option>
					</select>
					<label for='programas'>Este usu�rio tem privil�gios de acesso �s seguintes telas:</label><br>
					<select class="lista" name="restricoes" id="restricoes" size="10" multiple style='height:130px;'>
						<option></option>
					</select>
					<center>
					<!--
					<button type='button' id='reset_rs' title='Recarrega as restri��es para o usu�rio selecionado'>Atualizar</button>
					-->
					<button type='button' id='reset_prg' onclick="window.location='<? echo $PHP_SELF;?>'" style='width:230px;'>Cancelar Altera��es n�o Salvas</button>
					</center>
				</fieldset>
			</td>

			<td width='50'>
				<!--<button type='button' id='copy_all' title='Copia todas as restri��es para o usu�rio' style='width:40px;'>>></button> <br /> -->
				<button type='button' id='copy_sel' title='Copia as restri��es selecionadas para o usu�rio'	style='width:40px;'>>></button>
			</td>

			<td width='325'>
				<fieldset for='usuario' style='width:300px;'>
					<legend>Para o usu&aacute;rio<?=$banco_de_testes?>:</legend>
					<select name='admin' id='admin' onchange='habilitaBotao();'>
						<option></option>
					</select>
					<label for='programas'>Este usu�rio tem privil�gios de acesso �s seguintes telas:</label><br>
					<select class="lista"
							 name="programas[]" id="programas" size="10" multiple style='height:130px;'>
						<option></option>
					</select>
					<center>
						<button type='button' id='atualiza' disabled='disabled' >Salvar</button>
						<button type='button' id='excl_sel' disabled='disabled'>Excluir Selecionadas</button>
					</center>
					<!--
					<button type='button' id='kill_all'  title='Apaga todas as restri��es deste usu�rio' disabled='disabled' >Excluir Todas</button>
					-->
				</fieldset>
			</td>
		</tr>
	</table>
</form>

<? include "rodape_test.php"; ?>
