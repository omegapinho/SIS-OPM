<?php
/**
 * tipo_incidente Active Record
 * @author  <your-name-here>
 */
class tipo_incidente extends TRecord
{
    const TABLENAME = 'sisacad.tipo_incidente';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('oculto');
    }

    
    /**
     * Method getincidente_pedagogicos
     */
    public function getincidente_pedagogicos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_incidente_id', '=', $this->id));
        return incidente_pedagogico::getObjects( $criteria );
    }
    


}
