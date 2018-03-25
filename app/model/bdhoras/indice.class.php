<?php
/**
 * indice Active Record
 * @author  <your-name-here>
 */
class indice extends TRecord
{
    const TABLENAME = 'bdhoras.indice';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('diario');
        parent::addAttribute('segundosdiarios');
        parent::addAttribute('horassemanal');
        parent::addAttribute('valorhora');
        parent::addAttribute('datavigencia');
    }


}
