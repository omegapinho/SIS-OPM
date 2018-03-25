<?php
/**
 * Problema Active Record
 * @author  <your-name-here>
 */
class Problema extends TRecord
{
    const TABLENAME = 'gdocs.problema';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('relator');
        parent::addAttribute('categoria_id');
        parent::addAttribute('categoria');
        parent::addAttribute('opm_id');
        parent::addAttribute('opm');
        parent::addAttribute('relator_id');
        parent::addAttribute('gravidade');
        parent::addAttribute('prioridade');
        parent::addAttribute('data_criado');
        parent::addAttribute('data_atualizado');
        parent::addAttribute('data_previsto');
        parent::addAttribute('manipulador');
        parent::addAttribute('manipulador_id');
        parent::addAttribute('status');
        parent::addAttribute('numero_duplo');
        parent::addAttribute('descricao');
        parent::addAttribute('secao_id');
        parent::addAttribute('secao');
        parent::addAttribute('resultado');
        parent::addAttribute('visibilidade');
        parent::addAttribute('oculto');
    }


}
