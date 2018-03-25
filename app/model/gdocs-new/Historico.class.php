<?php
/**
 * Historico Active Record
 * @author  <your-name-here>
 */
class Historico extends TRecord
{
    const TABLENAME = 'gdocs.historico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('item_alterado');
        parent::addAttribute('novo_valor');
        parent::addAttribute('data_mudanca');
        parent::addAttribute('alterador_id');
        parent::addAttribute('alterador');
        parent::addAttribute('antigo_valor');
        parent::addAttribute('problema_id');
    }


}
