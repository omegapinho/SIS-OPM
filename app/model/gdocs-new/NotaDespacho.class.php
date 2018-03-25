<?php
/**
 * NotaDespacho Active Record
 * @author  <your-name-here>
 */
class NotaDespacho extends TRecord
{
    const TABLENAME = 'gdocs.nota_despacho';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('relator_id');
        parent::addAttribute('relator');
        parent::addAttribute('data_criado');
        parent::addAttribute('descricao');
        parent::addAttribute('privado');
        parent::addAttribute('visibilidade');
        parent::addAttribute('oculto');
        parent::addAttribute('problema_id');
    }


}
