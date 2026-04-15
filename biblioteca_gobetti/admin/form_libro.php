<!-- Form Libro (Include per aggiunta/modifica) -->
<div class="form-row">
    <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select name="tipo" class="form-control" required>
            <option value="libro" <?php echo ($libro['tipo'] ?? '') === 'libro' ? 'selected' : ''; ?>>Libro</option>
            <option value="rivista" <?php echo ($libro['tipo'] ?? '') === 'rivista' ? 'selected' : ''; ?>>Rivista</option>
            <option value="dizionario" <?php echo ($libro['tipo'] ?? '') === 'dizionario' ? 'selected' : ''; ?>>Dizionario</option>
            <option value="manuale" <?php echo ($libro['tipo'] ?? '') === 'manuale' ? 'selected' : ''; ?>>Manuale</option>
        </select>
    </div>
    
    <div class="form-group">
        <label class="form-label">Titolo *</label>
        <input type="text" name="titolo" class="form-control" required value="<?php echo e($libro['titolo'] ?? ''); ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label">Autore</label>
        <input type="text" name="autore" class="form-control" value="<?php echo e($libro['autore'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label class="form-label">Casa Editrice</label>
        <input type="text" name="casa_editrice" class="form-control" value="<?php echo e($libro['casa_editrice'] ?? ''); ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label">Anno Uscita</label>
        <input type="number" name="anno_uscita" class="form-control" value="<?php echo e($libro['anno_uscita'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label class="form-label">Lingua</label>
        <input type="text" name="lingua" class="form-control" value="<?php echo e($libro['lingua'] ?? 'Italiano'); ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label">Genere</label>
        <input type="text" name="genere" class="form-control" value="<?php echo e($libro['genere'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control" value="<?php echo e($libro['isbn'] ?? ''); ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label">Codice Dewey</label>
        <input type="text" name="codice_dewey" class="form-control" value="<?php echo e($libro['codice_dewey'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label class="form-label">Collocazione</label>
        <input type="text" name="collocazione" class="form-control" value="<?php echo e($libro['collocazione'] ?? ''); ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label">Numero Armadio</label>
        <input type="text" name="numero_armadio" class="form-control" value="<?php echo e($libro['numero_armadio'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label class="form-label">Numero Ripiano</label>
        <input type="text" name="numero_ripiano" class="form-control" value="<?php echo e($libro['numero_ripiano'] ?? ''); ?>">
    </div>
</div>

<div class="form-group">
    <label class="form-label">Numero Copie *</label>
    <input type="number" name="numero_copie" class="form-control" required min="1" value="<?php echo e($libro['numero_copie'] ?? 1); ?>">
</div>

<div class="form-group">
    <label class="form-label">URL Immagine Copertina</label>
    <input type="url" name="immagine_copertina" class="form-control" placeholder="https://..." value="<?php echo e($libro['immagine_copertina'] ?? ''); ?>">
</div>

<div class="form-group">
    <label class="form-label">Trama / Descrizione</label>
    <textarea name="trama" class="form-control" rows="4"><?php echo e($libro['trama'] ?? ''); ?></textarea>
</div>
