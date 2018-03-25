<?php
/**
 * SystemUnit Active Record
 * @author  <your-name-here>
 */
class SystemUnit extends TRecord
{
    //const TABLENAME = 'g_system.system_unit';
    const TABLENAME = 'g_geral.opm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('idsuperior');
        parent::addAttribute('level');
        
    }
}
