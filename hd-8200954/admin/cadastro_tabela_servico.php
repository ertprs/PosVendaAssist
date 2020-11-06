<?php

/*


IMPORTANTE: por favor ao próximo programador que pegar esta tela para trabalhar, não utilize "ifs" desordenadamente. crie um metodo
especifique a fabrica e depois chame na main()

Bruno Kawakami

*/


class pagGenerate{

	private $msg_erro;
	private $msg_sucesso;

	##################################### jquerySource
	private function jquerySource(){
		

		$jquerySource = "
			<script type=\"text/javascript\" language=\"JavaScript\" src=\"js/jquery-1.6.1.min.js\"></script>
			<script type=\"text/javascript\" language=\"JavaScript\" src=\"js/jquery-ui-1.8rc3.custom.js\"></script>

			<script type=\"text/javascript\" language=\"JavaScript\">
			$(document).ready(function() {
				$('#selecionarTodos').click(function() {
			        if(this.checked == true){
			            $(\"input[id=id_tabela_mao_obra]\").each(function() {
			                this.checked = true;
			            });
			        } else {
			            $(\"input[id=id_tabela_mao_obra]\").each(function() {
			                this.checked = false;
			            });
			        }
			    });
			});
			</script>

			";
		return $jquerySource;

	}

	##################################### styleSource
	private function styleSource(){
		$style = '
		<link rel="stylesheet" type="text/css" href="js/jquery-ui-1.8rc3.custom.css">
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
		    font: bold 16px "Arial";
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


		.sucesso{
		    background-color:#008000;
		    font: bold 14px "Arial";
		    color:#FFFFFF;
		    text-align:center;
		}

		.ui-dropdownchecklist-item{
			padding: 5px 5px 5px 5px;
		}

		</style>';

		return $style;

	}

	##################################### geraForm
	private function geraForm(){

		global $con;	
		global $login_fabrica;

		$form = NULL;

		if(!empty($_GET['tabela'])){
			$sqlSelectUpdate = "SELECT * FROM tbl_tabela_mao_obra WHERE tabela_mao_obra = ".$_GET['tabela'].";";
			$resSelectUpdate = pg_query($con, $sqlSelectUpdate);
			$fetchDbSelectUpdate = pg_fetch_all($resSelectUpdate);
			$_POST['siglaForm'] = $fetchDbSelectUpdate[0]['sigla_tabela'];
			$_POST['descricaoForm'] = $fetchDbSelectUpdate[0]['descricao'];
			
			$ativoSelected = ($fetchDbSelectUpdate[0]['ativo'] == 't') ? "checked='checked'" : "";
		}

		if (!empty($this->msg_erro)) { 
			$form .='<div class="msg_erro" style="width:700px; margin:auto;">';
			$form .= $this->msg_erro;
			$form .= "</div>";
		}

		if (!empty($this->msg_sucesso)) { 
			$form .='<div class="sucesso" style="width:700px; margin:auto;">';
			$form .= $this->msg_sucesso;
			$form .= "</div>";
		}

		$form .= '<form name="frm_pesquisa" id="frm_pesquisa" method="post">
			<table width="700px" border="0" align="center" cellpadding="3" cellspacing="0" class="formulario">
					<tr>
						<td colspan="4" class="titulo_tabela">Cadastro</td>
					</tr>
				
					
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					
					<tr>
						<td width="210px"><div style="margin-left:40px;">Descrição da tabela</div></td>
						<td width="240px">Código da tabela</td>
						<td width="180px">Ativo</td>
					</tr>
					<tr>
						<td width="210px"><input type="text" name="descricaoForm" class="frm" style="width:250px; margin-left:40px;" value="'.$_POST['descricaoForm'].'"></td>
						<td width="240px"><input name="siglaForm" type="text" class="frm" value="'.$_POST['siglaForm'].'" /></td>
						<td width="180px"><input name="ativoForm" class="frm" '.$ativoSelected.' type="checkbox" value="t" /></td>
					</tr>
					
					<tr>
						<td  colspan="3" align="center" height="60">
							<input type="submit" name="btnForm" value="Limpar" />
							<input type="submit" name="btnForm" value="Gravar" style="margin-left:50px;" />
							<input type="submit" name="btnForm" value="Consultar" style="margin-left:50px;" />
						</td>
					</tr>
					
					
			</table>
		</form><br />';


			return $form;
		 
	}

	##################################### geraTabela
	private function geraTabela($Array){

		global $PHP_SELF;

		$tabela = NULL;

		$qtArray = count($Array);

		if(empty($Array[0])){
				$tabela .= "<table align=\"center\" width=\"700px\" style=\"background-color: #D9E2EF; font-family: Arial, san-serief; font-size: 12px;\">";
				$tabela .= "<tr>";
				$tabela .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
				$tabela .= "<strong>Consulta de todos registros</strong>";
				$tabela .= "</td>";
				$tabela .= "</tr>";
				$tabela .= "<tr>";
				$tabela .= "<td>";
				$tabela .= "Não existem registros!";
				$tabela .= "</td>";
				$tabela .= "</tr>";
				$tabela .= "</table>";
			return $tabela;
		}

		$tabela .= "<script>
						function move_i(what) { what.style.background='#D9E2EF'; }
						function move_o(what) { what.style.background='#FFFFFF'; }
					</script>";
		$tabela .= "<table align=\"center\" width=\"695px\" cellspacing=\"1\" id=\"tabelaRegistro\">";
		$tabela .= "<tr>";
		$tabela .= "<td colspan=\"3\" align=\"center\" style=\"background-color:#596D9B; color: #FFFFFF; font-size: 14px;font:bold;\">";
		$tabela .= "<strong>Consulta de todos registros</strong>";
		$tabela .= "</td>";
		$tabela .= "</tr>";
		$tabela .= "<tr>";
		$tabela .= "<td>";

		$tabela .= "<form method=\"post\">";
		$tabela .= "<table align=\"center\" width=\"700\" >";
		$tabela .= "<tr style=\"border: 1px solid silver;background-color:#596D9B; color: #FFFFFF;\">";
		$tabela .= "<td style=\"border: 1px solid silver;\"/>";
		$tabela .= "<input  type=\"checkbox\" id=\"selecionarTodos\" />";
		$tabela .= "</td>";


		foreach ($Array[0] as $key => $value) {

			$key = ($key == "sigla_tabela") ? "Código da tabela" : $key;
			$key = ($key == "descricao") ? "Descrição da tabela" : $key;
			$key = ($key == "ativo") ? "Ativo" : $key;

			if(empty($countArray) && $key != "tabela_mao_obra"){		
				$tabela .= "<td style=\"border: 1px solid silver;\"><strong>".$key."</strong></td>";
			}
		}
		$tabela .= "</tr>";

		for($countArray = 0; $qtArray>$countArray;$countArray++){
			
				$tabela .= "<tr style=\"border: 1px solid silver;\"  onMouseOver='move_i(this)' onMouseOut='move_o(this)'>";
				$tabela .= "<td style=\"border: 1px solid silver;\">";
				$tabela .= "<input type=\"checkbox\" name=\"id_tabela_mao_obra\" id=\"id_tabela_mao_obra\" value=\"".$Array[$countArray]['tabela_mao_obra']."\" />";
				$tabela .= "</td>";
				foreach ($Array[$countArray] as $key => $value) {
					if($key != "tabela_mao_obra" && $key != "sigla_tabela"){
						if($key == "ativo"){
							$value = ($value == "f") ? "Inativo" : "Ativo";
						}
						$tabela .= "<td style=\"border: 1px solid silver;\">".$value."</td>";
					}
					if($key == "sigla_tabela"){
						$tabela .= "<td style=\"border: 1px solid silver;\"><a href='".$PHP_SELF."?tabela=".$Array[$countArray]['tabela_mao_obra']." '>".$value."</a></td>";
					}
				}
				$tabela .= "</tr>";
			
		}

		$tabela .= "</table>";
		$tabela .= "<table align=\"center\" width=\"700\" >";

		$tabela .= "<tr style=\"border: 1px solid silver;\">";
		$tabela .= "<td style=\"border: 1px solid silver;\" align=\"left\"/>";
		$tabela .= "<select  name=\"consultaAction\">
						<option value=\"ativar\">Ativar</option>
						<option value=\"inativar\">Inativar</option>
					</select>
					<input type=\"submit\" value=\"Gravar\" />";
		$tabela .= "</td>";
		$tabela .= "</tr>";
		$tabela .= "</table>";
		$tabela .= "</form>";
		$tabela .= "</td>";
		$tabela .= "</tr>";
		$tabela .= "</table>";
		return $tabela;

	}

	##################################### set
	public function set($prop,$value){ 
        $this->$prop = $value; 
    }

    ##################################### main 
	public function main(){

		global $con;
		global $login_fabrica;
		global $PHP_SELF;


		if($_POST['btnForm'] == "Gravar"){

			if(empty($_POST['siglaForm'])){
				$this->set("msg_erro", "Por favor digite uma sigla");
			}

			if(empty($_POST['descricaoForm'])){
				$this->set("msg_erro", "Por favor digite uma descrição");
			}


			if(empty($this->msg_erro)){

				$ativoForm = (empty($_POST['ativoForm'])) ? "FALSE" : "TRUE";

				if(empty($_GET['tabela'])){
					$sqlInsert = "INSERT INTO tbl_tabela_mao_obra
					 (fabrica,ativo,sigla_tabela,descricao)
					  VALUES
					 ('".$login_fabrica."',".$ativoForm.",'".$_POST['siglaForm']."','".$_POST['descricaoForm']."') ;
		";
					$this->set("msg_sucesso", "Registro gravado com sucesso!");
				}else{
					$sqlInsert = "UPDATE tbl_tabela_mao_obra SET sigla_tabela = '".$_POST['siglaForm']."', descricao = '".$_POST['descricaoForm']."', ativo = ".$ativoForm." WHERE tabela_mao_obra = ".$_GET['tabela']."; ";
					echo " <meta http-equiv=\"refresh\" content=\"0; url=".$PHP_SELF."\">";
					$this->set("msg_sucesso", "Registro atualizado com sucesso!");
				}
				$resInsert = pg_query($con, $sqlInsert);
				$fetchDbInsert = pg_fetch_all($resInsert);
				unset($_POST);
			}
		}
		if($_POST['btnForm'] == "Limpar"){

		unset($_POST);
		echo " <meta http-equiv=\"refresh\" content=\"0; url=".$PHP_SELF."\">";

		}

		if($_POST['consultaAction'] == "excluir"){
			$postDelete = file_get_contents("php://input");

			$explodePost = explode("&", $postDelete);

			$qtPost = count($explodePost);

			$cmdDelete =  NULL;

			$cmdDelete .= "DELETE FROM tbl_tabela_mao_obra WHERE tbl_tabela_mao_obra.tabela_mao_obra in (";

			if($qtPost==1){
				$this->set("msg_erro", "Você deve selecionar algum item para excluir");
			}

			if($qtPost>1){
				for($countPost = 0; $countPost<($qtPost-1); $countPost++){

					$numPost = explode("=", $explodePost[$countPost]);

					if(($countPost+1) == ($qtPost-1)){
						$cmdDelete .= $numPost[1]."";
					}else{
						$cmdDelete .= $numPost[1].",";
					}

				}
				$cmdDelete .= ") AND tbl_tabela_mao_obra.fabrica = ".$login_fabrica.";";
				$resDelete = pg_query($con, $cmdDelete);
				$fetchDbDelete = pg_fetch_all($resDelete);

				$this->set("msg_sucesso", "".($countPost)." registro(s) excluídos com sucesso!");

			}

		}

		if($_POST['consultaAction'] == "ativar"){
			$postAtivar = file_get_contents("php://input");

			$explodePost = explode("&", $postAtivar);

			$qtPost = count($explodePost);

			$cmdAtivar =  NULL;

			$cmdAtivar .= "UPDATE tbl_tabela_mao_obra SET ativo = TRUE WHERE tabela_mao_obra in (";

			if($qtPost==1){
				$this->set("msg_erro", "Você deve selecionar algum item para ativar");
			}

			if($qtPost>1){
				for($countPost = 0; $countPost<($qtPost-1); $countPost++){

					$numPost = explode("=", $explodePost[$countPost]);

					if(($countPost+1) == ($qtPost-1)){
						$cmdAtivar .= $numPost[1]."";
					}else{
						$cmdAtivar .= $numPost[1].",";
					}

				}
				$cmdAtivar .= ") AND fabrica = ".$login_fabrica.";";
				
				$resAtivar = pg_query($con, $cmdAtivar);
				$fetchDbAtivar = pg_fetch_all($resAtivar);

				$this->set("msg_sucesso", "".($countPost)." registro(s) ativados com sucesso!");

			}

		}

		if($_POST['consultaAction'] == "inativar"){
			$postInativar = file_get_contents("php://input");


			$explodePost = explode("&", $postInativar);

			$qtPost = count($explodePost);

			$cmdInativar =  NULL;

			$cmdInativar .= "UPDATE tbl_tabela_mao_obra SET ativo = FALSE WHERE tabela_mao_obra in (";

			if($qtPost==1){
				$this->set("msg_erro", "Você deve selecionar algum item para inativar");
			}

			if($qtPost>1){
				for($countPost = 0; $countPost<($qtPost-1); $countPost++){

					$numPost = explode("=", $explodePost[$countPost]);
					
					if(($countPost+1) == ($qtPost-1)){	
						$cmdInativar .= $numPost[1]."";
					}else{
						$cmdInativar .= $numPost[1].",";
					}

				}
				$cmdInativar .= ") AND fabrica = ".$login_fabrica.";";
				
				$resInativar = pg_query($con, $cmdInativar);
				$fetchDbInativar = pg_fetch_all($resInativar);

				$this->set("msg_sucesso", "".($countPost)." registro(s) inativados com sucesso!");

			}

		}


		echo $this->jquerySource();
		echo $this->styleSource();
		echo $this->geraForm();

		if($_POST['btnForm'] == "Consultar"){
			$sqlTabela = "SELECT tabela_mao_obra,sigla_tabela,descricao,ativo FROM tbl_tabela_mao_obra WHERE fabrica = ".$login_fabrica." ORDER BY tabela_mao_obra DESC ;
";
			$resTabela = pg_query($con, $sqlTabela);
			$fetchDbTabela = pg_fetch_all($resTabela);

			echo $this->geraTabela($fetchDbTabela);
		}

	}

}




$pagGenerate = new pagGenerate();

$admin_privilegios = "cadastros";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$layout_menu = 'cadastro';

$title = "CADASTRO TABELA DE SERVIÇOS";

include 'cabecalho.php';

$pagGenerate->main();

include "rodape.php"; 

?>
