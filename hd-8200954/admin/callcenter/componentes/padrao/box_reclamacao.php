<div class="container_bc">
    <div class="alert alert-warning tac">
        <p><?php echo traduz("em que posso ajudá-lo?");?></p>
    </div>
    <div class="top_painel">
        <div class="row" style="line-height: 34px;">
            <div class="col-md-12 tac">
                <b><?php echo traduz("Reclamação");?></b> 
            </div>
        </div>
    </div>
    <div class="box_campos">
        <div class="box_add_init_callcenter">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Reclamação do Cliente");?>:</label>
                        <input type="text" name="reclamacao[reclamacao_cliente]" class="form-control input-sm">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Descrição");?>:</label>
                        <textarea name="reclamacao[descricao]" class="form-control input-sm" rows="8"></textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Transferir para");?>:</label>
                        <select name="reclamacao[transferir]" class="form-control input-sm">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                            <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Situação");?>:</label>
                        <select  name="reclamacao[situacao]" class="form-control input-sm">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                            <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Chamado Interno");?>:</label>
                        <select name="reclamacao[interno]" class="form-control input-sm">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Sim</option>
                            <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Não</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>