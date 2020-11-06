<div class="container_bc">
    <div class="alert alert-warning tac">
        <p> <?php echo traduz("Qual o produto comprado");?>?</p>
    </div>

    <div class="result_produto_callcenter">
        <div id="boxcall">
            
            <div class="top_painel">
                <div class="row">
                    <div class="col-md-12 tac">
                        <b><?php echo traduz("Informações do produto");?></b> 
                    </div>
                </div>
            </div>
            <div class="box_campos">
                <div class="box_add_init_callcenter">
                    <div class="row">
                        <div class="col-md-2 tal">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Abrir PréOS");?>:</label><br>
                                <input type="checkbox" name="produto[abre_pre_os]">
                            </div>
                        </div>
                        <div class="col-md-1 tal">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Abrir OS");?>:</label><br>
                                <input type="checkbox" name="produto[abre_os]">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Nota Fiscal");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[nota_fiscal]" class="form-control input-sm">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Data NF");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[data_nf]" class="form-control input-sm">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Referencia");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[referencia]" class="form-control input-sm">
                                    <span class="input-group-btn">
                                        <a  href="<?php ?>" class="btn btn-default btn-sm" data-toggle="modal" data-target=".modal_tipo" title="Adicionar Novo Tipo" ><i class="fa fa-search"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Descrição");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[descricao]" class="form-control input-sm">
                                    <span class="input-group-btn">
                                        <a  href="<?php ?>" class="btn btn-sm btn-default" data-toggle="modal" data-target=".modal_projeto" title="Adicionar Novo Projeto"><i class="fa fa-search"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Voltagem");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[voltagem]" class="form-control input-sm">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Qtde");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[qtde]" class="form-control input-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Nº Série");?>:</label>
                                <div class="input-group">
                                    <input type="text" name="produto[n_serie]" class="form-control input-sm">
                                    <span class="input-group-btn">
                                        <a  href="<?php ?>"  data-toggle="modal" data-target=".modal_contato" title="Adicionar Novo Contato" class="btn  btn-sm btn-default"><i class="fa fa-search"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label"><?php echo traduz("Defeito Reclamado");?>:</label>
                                <select name="produto[defeito_reclamado]" class="form-control input-sm">
                                    <option value=""> <?php echo traduz("Selecione");?>...</option>
                                    <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                                    <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
                                </select>
                            </div>
                        </div>
                        
                        
                    </div><BR>
                    <?php 
                        include("callcenter/componentes/padrao/box_produto_mapa_rede.php");
                    ?>
                    <div class="row remove_pd" style="display: none;">
                        <div class="col-md-5"></div>
                        <div class="col-md-2 tac">
                            <button type="button" class="btn btn-danger btn-block del_produto" ><i class="fa fa-trash"></i> <?php echo traduz("Remover Produto");?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br><br>

    <div class="row">
            <div class="col-md-5"></div>
            <div class="col-md-2 tac">
                <button type="button" class="btn btn-primary btn-lg btn-block add_produto" ><i class="fa fa-plus"></i> <?php echo traduz("Adicionar Produto");?></button>
            </div>
        </div>
</div>
        