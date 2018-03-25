<?php
/**
 * Item Active Record
 * @author  <your-name-here>
 */
class Item extends TRecord
{
    const TABLENAME = 'opmv.item';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('dominio');
        parent::addAttribute('subdominio');
        parent::addAttribute('oculto');
        parent::addAttribute('ordem');
    }


}
