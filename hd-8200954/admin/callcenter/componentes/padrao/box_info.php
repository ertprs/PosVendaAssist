<div class="container_bc">
    <div class="top_painel">
        <div class="row">
            <div class="col-md-12 tac">
                <b><?php echo traduz("Informações do Atendimento");?></b> 
            </div>
        </div>
    </div>
    <div class="box_campos">
        <div class="box_add_init_callcenter">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Tipo");?>:</label>
                        <select name="informacao[tipo]" class="form-control input-sm">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                            <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Classificação");?>:</label>
                        <select name="informacao[classificacao]" class="form-control input-sm">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                            <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="control-label"><?php echo traduz("Origem");?>:</label>
                        <select name="informacao[origem]" class="form-control input-sm">
                            <option value=""><?php echo traduz("Selecione");?> ...</option>
                            <option value="A" <?php echo (isset($status) && (trim($status) == "A")) ? "selected" : "";?>>Aprovado</option>
                            <option value="P" <?php echo (isset($status) && (trim($status) == "P")) ? "selected" : "";?>>Reprovado</option>
                        </select>
                    </div>
                </div>
            </div>    
        </div>
    </div>
</div>
