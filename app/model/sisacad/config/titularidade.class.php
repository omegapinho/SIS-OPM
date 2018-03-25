<?php
/**
 * titularidade Active Record
 * @author  <your-name-here>
 */
class titularidade extends TRecord
{
    const TABLENAME = 'sisacad.titularidade';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nivel');
        parent::addAttribute('nome');
        parent::addAttribute('oculto');
    }

    
    /**
     * Method getescolaridades
     */
    public function getescolaridades()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('titularidade_id', '=', $this->id));
        return escolaridade::getObjects( $criteria );
    }
    
    
    /**
     * Method getvalores_pagamentos
     */
    public function getvalores_pagamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('titularidade_id', '=', $this->id));
        return valores_pagamento::getObjects( $criteria );
    }
    


}
