<?php
/**
 * documentacao Active Record
 * @author  <your-name-here>
 */
class documentacao extends TRecord
{
    const TABLENAME = 'sisacad.documentacao';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_entrega');
        parent::addAttribute('tipo_documento');
        parent::addAttribute('documento_id');
        parent::addAttribute('escolaridade_id');
    }


}
