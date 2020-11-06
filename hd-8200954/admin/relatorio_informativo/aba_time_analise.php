<input type="hidden" name="ri_analise[id]" value="<?= $dadosRequisicao["ri_analise"]["id"] ?>" />
<div role="tabpanel" class="tab-pane <?= $abaAtiva ?>" id="<?= $codigoAba ?>">
	<div align='center' class='form-horizontal tc_formulario'>
		<div class="row row-relatorio">
            <div class='titulo_tabela'>Time de Análise (Equipe Multidisciplinar)</div>
            <div class="col-sm-10 col-sm-offset-1">
        		<div class="alert alert-info"><h5>Informe o nome das pessoas que estarão envolvidas na resolução deste RI</h5></div>
        		<div class='row'>
			        <div class="col-sm-8 col-sm-offset-2">
	                    <div class="form-group" style="text-align: center;">
                            <select class="form-control" id="admin_analise" style="width: 350px;">
                                <option value="">Selecione o admin</option>
                                <?php
                                $sqlAdminAnalise = "SELECT admin, nome_completo
                                                    FROM tbl_admin
                                                    WHERE fabrica = {$login_fabrica}
                                                    AND json_field('analise_ri', parametros_adicionais) = 't'
                                                    AND ativo";
                                $resAdminAnalise = pg_query($con, $sqlAdminAnalise);

                                while ($dadosAdm = pg_fetch_object($resAdminAnalise)) {
                                ?>
                                    <option value="<?= $dadosAdm->admin ?>"><?= $dadosAdm->nome_completo ?></option>
                                <?php
                                } ?>
                            </select>
                            <button class="btn btn-success" type="button" style="width: 100px;" onclick='addIt();'>
		                		<span class="glyphicon glyphicon-plus"></span> Adicionar
		                	</button>
	                    </div>
	                </div>
			    </div>
			    <div class="row">
			    	<div class="col-sm-8 col-sm-offset-2">
			    		<div class="form-group" style="text-align: center;">
			    			<?= traduz("(Selecione o admin e clique em <strong>Adicionar</strong>)") ?><br />
					    	<select multiple size='5' id="PickList" name="ri_time_analise[admin][]" style="width: 455px;height: 250px;">
							<?php
								
                                foreach ($dadosRequisicao["ri_time_analise"]["admin"] as $adminId) { 

                                    $sqlAdminNome = "SELECT nome_completo FROM tbl_admin WHERE admin = {$adminId}";
                                    $resAdminNome = pg_query($con, $sqlAdminNome);

                                    $nomeAdmin = pg_fetch_result($resAdminNome, 0, 'nome_completo');
                                ?>
                                    <option value="<?= $adminId ?>"><?= $nomeAdmin ?></option>
                                <?php
                                }

							?>
							</select>
						</div>
					</div>
			    </div>
			    <div class="row">
			    	<div class="col-sm-8 col-sm-offset-2" style="text-align: center;">
			    		<input type="button" value="Remover" onclick="delIt();" class='btn btn-danger' style="width: 126px;">
			    	</div>
			    </div>
            </div>
        </div>
        <div class="row row-relatorio">
            <div class='titulo_tabela' style="margin-bottom: 30px;">Descrição do Problema</div>
            <div class="alert alert-info col-sm-6 col-sm-offset-3">
                <div class="row">
                    <div class="col-sm-5 col-sm-offset-1">
                        <h4><strong>Quantificar o problema:</strong></h4>
                    </div>
                    <div class="col-sm-3">
                        <ul class="quantificar-problema">
                            <li>O quê?</li>
                            <li>Por quê?</li>
                            <li>Onde?</li>
                            <li>Quem?</li>
                            <li>Quando?</li>
                            <li>Como?</li>
                            <li>Quanto?</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-sm-10 col-sm-offset-1" style="text-align: center;">
                <textarea name="ri_analise[descricao_problema]" class="textarea_ckeditor" rows="4" cols="110" style='font-size:10px;'><?= $dadosRequisicao["ri_analise"]["descricao_problema"] ?></textarea>
            </div>
        </div>
        <div class="row row-relatorio">
            <div class="col-sm-12" style="text-align: center;">
                <hr />
                <br />
                <input data-aba="<?= $codigoAba ?>" onclick="selIt()" type="button" class="btn-submit btn btn-default btn-lg" value="<?= !empty($dadosRequisicao['ri_analise']['id']) ? 'Alterar' : 'Gravar' ?>" style="width: 150px;" />
            </div>
        </div>
		<br /><br />
	</div>
</div>