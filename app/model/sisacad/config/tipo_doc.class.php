<?php
/**
 * tipo_doc Active Record
 * @author  <your-name-here>
 */
class tipo_doc extends TRecord
{
    const TABLENAME = 'sisacad.tipo_doc';
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
        parent::addAttribute('servico');
        parent::addAttribute('oculto');
    }
}

