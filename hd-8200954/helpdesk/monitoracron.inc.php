  <!-- <link href="../admin/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">      -->

<?php
/**
 *
 *  monitoracron
 *
 *  Programa para monitoramento de rotinas cron
 *
 * @author  Francisco Ambrozio
 * @version 2012.03
 *
 */

class MonitoraCron {

	private $programas;
	private $workdir = '/tmp/.recron';
	private $workfile;
	private $modulo = 'padrao';	

	public function __construct()
	{
		global $login_fabrica;
		if ($login_fabrica <> 10) {
			echo '<meta http-equiv="Refresh" content="0 ; url=index.php" />';
			exit;
		}
	}

	public function run($busca = [])
	{
		$this->getModulo();

		if ($this->modulo == 'agendados') {
			$this->mostraAgenda();
		} else {
			$this->programas = $this->rodouCron($busca);			
			$this->listaProgramas();
		}
	}

	public function ajaxAgenda($programa)
	{
		global $con;

		$qry = pg_query($con, "SELECT perl from tbl_perl where programa = '$programa'");

		if (pg_num_rows($qry) == 1) {
			$perl = pg_fetch_result($qry, 0, 'perl');
			$this->isDirWorkdir();
			$existe = $this->isFileWorkFile();

			$gravacao = '';
			if ($existe == 1) {
				$this->gravaPerlSimples($perl);
				$gravacao = 0;
			} else {
				$gravacao = $this->gravaPerlAnexa($perl);
			}

			if ($gravacao == 0) {
				echo 'Agendado execução: ' , $programa;
			} else {
				//echo 'A execução de ' , $programa , ' já está agendada.';				
				echo '<div id="conteudo" style="display:none;">Conteudo da DIV</div>';
			}
		}

		return 0;

	}

	public function removeAgenda($perl)
	{
		$file = $this->isFileWorkFile();
		if ($file == 0) {
			$original = file_get_contents($this->workfile);
			$removido = str_replace("$perl\n", "", $original);

			$f = fopen($this->workfile, 'w');
			fwrite($f, $removido);
			fclose($f);

			echo 'Removido com sucesso';
		}

	}

	public function getWorkfile()
	{
		return $this->workfile;
	}

	public function setWorkfile()
	{
		date_default_timezone_set('America/Sao_Paulo');
		$this->workfile = $this->workdir . '/' . date('Ymd');
	}

	public function isFileWorkFile()
	{
		$this->setWorkfile();
		if (file_exists($this->workfile) and filesize($this->workfile) > 0) {
			return 0;
		} else {
		    return 1;
		}
	}

	private function getModulo()
	{
		$modulo = '';

		if (!empty($_GET['m'])) {
			$modulo = $_GET['m'];
		}

		if ($modulo == 'agendados') {
			$this->modulo = 'agendados';
		}
	}

	private function rodouCron($busca = [])
	{	
		global $con;

        echo $this->mostraFiltros($busca);

        $msg_erro = [];

        if (count($busca) > 0) {
        	if ($busca['data_inicial'] != "" && $busca['data_final'] != "") {
        		$data_inicial = $busca['data_inicial'];

        		$dat = explode ("/", $data_inicial );//tira a barra
				
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				
				if(!checkdate($m,$d,$y)) {
					$msg_erro = array("erro"=>"Data Inválida");
				} 
			
        		$data_final = $busca['data_final'];
				
				$dat = explode ("/", $data_final );//tira a barra
			
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				
				if(!checkdate($m,$d,$y)) {
					$msg_erro = array("erro"=>"Data Inválida");
				} 
				
        	}

        	if ($busca['data_inicial'] == "" && $busca['data_final'] == "" && $busca['fabrica_select'] == "" && $busca['btn_acao'] == "Pesquisar") {
        		$msg_erro = array("erro"=>"Para pesquisar preencha algum filtro");
        	} 

        	$cond_fab  = "";
        	
        	if ($busca['fabrica_select'] != "") {
        		$fabrica_select = $busca['fabrica_select'];
        		$cond_fab = " AND tbl_fabrica.fabrica = $fabrica_select";
        	}
        } else {
        	return 0;
        }

        if (!empty($msg_erro)) {
        	return $msg_erro;
        }

        $data_hj = date("d/m/Y");
        $cond_data = "inicio_processo BETWEEN '{$data_hj} 00:00:00' AND '{$data_hj} 23:59:59' ";

        if ($data_inicial != "" && $data_final != "") {
        	$cond_data = " inicio_processo BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' ";
        }

		$sql = "SELECT tbl_perl_processado.perl_processado,
						tbl_fabrica.nome AS fabrica_nome,
						tbl_perl.programa,
						TO_CHAR(tbl_perl_processado.inicio_processo, 'dd/mm/yyyy HH24:MI:SS') AS inicio,
						TO_CHAR(tbl_perl_processado.fim_processo, 'dd/mm/yyyy HH24:MI:SS') AS fim,
						tbl_perl_processado.log
					FROM tbl_perl
					JOIN tbl_fabrica USING (fabrica)
					JOIN tbl_perl_processado USING(perl)
					WHERE $cond_data
					$cond_fab 
					ORDER BY fabrica_nome, inicio";
		$query = pg_query($con, $sql);

		if (pg_num_rows($query) > 0) {
			$arrayResult = array();
			while ($fetch = pg_fetch_array($query)) {
				$arrayResult[] = array(
									'perl_processado' => $fetch['perl_processado'],
									'fabrica_nome' => $fetch['fabrica_nome'],
									'programa' => $fetch['programa'],
									'inicio' => $fetch['inicio'],
									'fim' => $fetch['fim'],
									'log' => $fetch['log'],
								);
			}
			return $arrayResult;
		} else {
			$msg_erro = array("alerta"=>"Nenhum resultado encontrado");
			return $msg_erro;
		}

		return 0;
	}
	
	private function listaProgramas()
	{
		
		if (!is_array($this->programas)) {
			exit(1);
		}

		if ($this->programas['erro']) {
			echo "	<div class='container'>
			 			<div class='alert alert-danger al' style='color: #b94a48 !important; text-align: center;'>
							<p><b>".$this->programas['erro']."</b></p>
						</div>
					</div>";
			exit();
		}

		if ($this->programas['alerta']) {
			echo "	<div class='container'>
			 			<div class='alert alert-warning al' style='color: #c09853 !important; text-align: center;'>
							<p><b>".$this->programas['alerta']."</b></p>
						</div>
					</div>";
			exit();	
		}
		
		$datas = date("d/m/Y");
		
        echo '<div class="container">';
		echo '<div class="alert alert-warning al" style="text-align: center; color: #c09853 !important; text-align: center;">';
		echo '<p><b>Rotinas que foram executadas</b></p>';		
		//echo 'Para agendar a execução de um processo que não terminou sua execução, clique sobre o respectivo programa.';		
		echo '</div><br/><p>';
		
		$programas_aux = $this->programas;
		$perl_printadoJ = array();
		$perl_printadoI = array();			
		
		for($i=0;$i<count($this->programas);$i++){				
		
			if(!in_array($this->programas[$i]['fabrica_nome'],$perl_printadoI)){
				//echo '<br/>';
				$table = '<table class="table table-condensed">';					
                $table .= '<font class="nome_fabricante">'.strtoupper($this->programas[$i]['fabrica_nome']).'</font>';
				$table.= '<tr  class="titulo_tabela">';
				$table.= '<td>Programa</td>';
				$table.= '<td>Iní­cio da Execução</td>';
				$table.= '<td>Término da Execução</td>';
				$table.= '<td>Opções</td>';
				$table.= '</tr>';
				array_push($perl_printadoI,$programas_aux[$i]['fabrica_nome']);				
				for($j=0;$j<count($programas_aux);$j++){							
				
					if ($this->programas[$j]['fim'] <> "") {
						$bgcolor = '';
					} else {
						$bgcolor = 'background: #F4F4F4; ';
					}
			
					if (empty($this->programas[$j]['fim'])) {
						$color = 'color: #FF0000; ';
					} else {
						$color = '';
					}
			
					if (!empty($bgcolor) or !empty($color)) {
						$style = ' style="' . $bgcolor . $color . '"';
					} else {
						$style = '';
					}
				
					if($programas_aux[$j]['fabrica_nome'] == $this->programas[$i]['fabrica_nome'] && !in_array($programas_aux[$j]['perl_processado'],$perl_printadoJ)){										
						$pos_extensao = strrpos($this->programas[$j]['programa'], ".");
						$extensao = substr($this->programas[$j]['programa'], $pos_extensao);
						$nome_fabricante = strtolower($programas_aux[$j]['fabrica_nome']);
						$pos_fabricante = strrpos($this->programas[$j]['programa'], $nome_fabricante);
						if ($extensao == ".php") {
							$parte = substr($this->programas[$j]['programa'], $pos_fabricante, -4);
						} else {
							$parte = substr($this->programas[$j]['programa'], $pos_fabricante, -3);
						}
						
						$table.= '<tr' . $style . '>';			
						$table.= '<td';						
						if (empty($this->programas[$j]['fim'])) {
							$table.=' style="cursor: pointer;" onmouseover="ShowContent(\'uniquename3\'); return true;" onmouseout="HideContent(\'uniquename3\'); return true;"';
							echo '<div id="uniquename3" class="conteudotxt">'.$this->programas[$j]['log'].'</div>';
							$table .= '><img src="../imagens/status_vermelho.gif">&nbsp;&nbsp;' . $this->programas[$j]['programa'] . '</td>';							
						} else {
							$table .= '><img src="../imagens/status_verde.gif">&nbsp;&nbsp;' . $this->programas[$j]['programa'] . '</td>';
						}
						$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
						$table.= '<td>' . $this->programas[$j]['inicio'] . '</td>';
						$table.= '<td>' . $this->programas[$j]['fim'] . '</td>';
                        if (empty($this->programas[$j]['fim'])) {
						$table.= '<td><form name="frm_resultados" method="POST" action="monitoracron_ler_log.php">									
									<input class="btn btn-info btn-sm" type="submit" value="Log" /> 
                                    <input type="hidden" name="fabricas" value="'.strtolower($this->programas[$j]['fabrica_nome']).'" />
									<input type="hidden" name="parte" value="'.$parte.'" /></form>
                                    </td>';
                        }
                        else {
						$table.= '<td><form name="frm_resultados" method="POST" action="monitoracron_ler_log.php">																		
                                    <input type="hidden" name="fabricas" value="'.strtolower($this->programas[$j]['fabrica_nome']).'" />
									<input type="hidden" name="parte" value="'.$parte.'" /></form>
                                    </td>';                        
                        }																										
						$table.= '</tr>';
						array_push($perl_printadoJ,$programas_aux[$j]['perl_processado']);																			
					}															
				}				
				$table.= '</table>';							                
				echo $table;	                
			}						
		}  
        echo '</p></div>';
		?> 
		<?php
	}

	private function isDirWorkdir()
	{
		if (!is_dir($this->workdir)) {
			if (!mkdir($this->workdir)) {
				echo 'ERRO: não foi possível criar diretório.';
				exit;
			}
		}
	}

	private function gravaPerlSimples($perl)
	{
		$f = fopen($this->workfile, 'w');
		fwrite($f, $perl . "\n");
		fclose($f);
	}

	private function gravaPerlAnexa($perl)
	{
		$f = fopen($this->workfile, 'a+');
		$conteudo = fread($f, filesize($this->workfile));
		$pos = strpos($conteudo, $perl);
		if ($pos === false) {
			fwrite($f, $perl . "\n");
			$retorno = 0;
		} else {
		    $retorno = 1;
		}
		fclose($f);

		return $retorno;
	}

	public function mostraFiltros($busca = [])
	{
		global $con; 

		if (count($busca) > 0) {
			if ($busca['data_inicial'] != "") {
				$data_inicial = $busca['data_inicial'];
			}

			if ($busca['data_final'] != "") {
				$data_final = $busca['data_final'];
			}

			if ($busca['fabrica_select'] != "") {
				$fabrica_select = $busca['fabrica_select'];
			}
		}

		$conteudo =	'   
					<div class="container">
						<br /><br />
						<form class="form-search form-inline" style=" background-color: #D9E2EF;" name="frm_relatorio" METHOD="POST" ACTION="monitoracron.php">
							<div class="titulo_tabela" style="font-size: 16px !important;">Parâmetros de Pesquisa</div>
							<br />
							<div class="row">
								<div class="col-sm-1 col-md-1"></div>
								<div class="col-sm-2 col-md-2">
									<div class="control-group ">
										<label class="control-label lbl" for="data_inicial">Data Inicial</label>
											<div class="controls controls-row">
												<input class="form-control" type="text" autocomplete="off" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="'.$data_inicial.'">
											</div>
									</div>
								</div>
								<div class="col-sm-1 col-md-1"></div>
								<div class="col-sm-2 col-md-2">
									<div class="control-group ">
										<label class="control-label lbl" for="data_final">Data Final</label>
											<div class="controls controls-row">
												<input class="form-control" type="text" name="data_final" id="data_final" size="12" maxlength="10" autocomplete="off" value="'.$data_final.'">
											</div>
									</div>
								</div>
								<div class="col-sm-1 col-md-1"></div>
								<div class="col-sm-2 col-md-2">
									<div class="control-group">
										<label class="control-label lbl">Fábrica</label>
											
												<select class="form-control" name="fabrica_select">
													<option value="">Selecione</option>';
														$sqlx = "SELECT nome            ,
																		fabrica
																FROM tbl_fabrica
																WHERE ativo_fabrica
																ORDER BY nome";

														$resx = pg_exec($con,$sqlx);
															if(pg_numrows($resx)>0){
																for($y=0;pg_numrows($resx)>$y;$y++){
																	$nome     = trim(pg_result($resx,$y,'nome'));
																	$fabrica  = trim(pg_result($resx,$y,'fabrica'));
																	
																	$conteudo .= '<option value="'.$fabrica.'"';
																		
																		$selected_fabrica = "";
																		if($fabrica_select == $fabrica) {
																			$selected_fabrica = "selected";
																		}
																	
																	$conteudo .= $selected_fabrica.'>'.$nome.'</option>';
																}

															}
		$conteudo .= '
												</select>
										
									</div>
								</div>
							</div>
							<br /><br />
								<div class="row">
									<div class="col-sm-12">
										<center>
											<input class="btn btn-default" type="submit" name="btn_acao" value="Pesquisar">
											<input class="btn btn-default" type="submit" name="btn_acao" value="Listar Todas">
										</center>
									</div>
								</div>
							<br />
						</form>	
					</div>';
		return $conteudo;
	}

	private function mostraAgenda()
	{
		$file = $this->isFileWorkFile();

		function callback($var)
		{
			$clean = str_replace("\n", "", $var);
			if (!empty($clean)) {
				return $clean;
			}
		}

		if ($file == 0) {
			$perls = implode(", ", array_filter(file($this->workfile), "callback"));
			if (empty($perls)) {
				$imprime = 'Nenhuma agenda cadastrada.';
			}
		} else {
			$imprime = 'Nenhuma agenda cadastrada.';
		}


		if (!isset($imprime)) {
			global $con;
			$sql = "SELECT tbl_perl.perl, tbl_fabrica.nome as fabrica_nome, tbl_perl.programa
					FROM tbl_perl JOIN tbl_fabrica USING(fabrica) WHERE perl IN ($perls)";
			$query = pg_query($con, $sql);

			if (pg_num_rows($query) > 0) {
				$imprime = '<div style="font-size: 12px; text-align: center; font-weight: bold">';
				$imprime.= 'Rotinas agendadas para rodarem hoje.';
				$imprime.= '</div><br/>';

				$imprime.= '<table align="center" class="listagem" style="width: 730px;">';
				$imprime.= '<tr class="titulo_tabela">';
				$imprime.= '<td>Fábrica</td>';
				$imprime.= '<td>Programa</td>';
				$imprime.= '<td align="center">Remover</td>';
				$imprime.= '</tr>';
				while ($fetch = pg_fetch_array($query)) {
					if ($i%2 == 0) {
						$bgcolor = '';
					} else {
						$bgcolor = ' style="background: #F4F4F4;"';
					}
					$imprime.= '<tr' . $bgcolor . '>';
					$imprime.= '<td>' . $fetch['fabrica_nome'] . '</td>';
					$imprime.= '<td>' . $fetch['programa'] . '</td>';
					$imprime.= '<td style="cursor: pointer;" align="center"><img src="imagens/delete_2.png" onClick="removeAgenda(\'' . $fetch['perl'] . '\')" /></td>';
					$imprime.= '</tr>';
				}
				$imprime.= '</table><br/>';
			}
		}

		echo $imprime;

	}
}
?>
