<?php
/**
 * tipo Active Record
 * @author  <your-name-here>
 */
class tipo extends TRecord
{
    const TABLENAME = 'freap.tipo';
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
     * Method getservicos
     */
    public function getservicos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_servico', '=', $this->id));
        return servico::getObjects( $criteria );
    }
    


}
