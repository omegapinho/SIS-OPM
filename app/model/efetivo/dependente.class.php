<?php
/**
 * dependente Active Record
 * @author  <your-name-here>
 */
class dependente extends TRecord
{
    const TABLENAME = 'efetivo.dependente';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $servidor;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('boletiminclusao');
        parent::addAttribute('boletimexclusao');
        parent::addAttribute('grauparentesco');
        parent::addAttribute('cpf');
        parent::addAttribute('dtnascimento');
        parent::addAttribute('nome');
        parent::addAttribute('servidor_id');
    }

    
    /**
     * Method set_servidor
     * Sample of usage: $dependente->servidor = $object;
     * @param $object Instance of servidor
     */
    public function set_servidor(servidor $object)
    {
        $this->servidor = $object;
        $this->servidor_id = $object->id;
    }
    
    /**
     * Method get_servidor
     * Sample of usage: $dependente->servidor->attribute;
     * @returns servidor instance
     */
    public function get_servidor()
    {
        // loads the associated object
        if (empty($this->servidor))
            $this->servidor = new servidor($this->servidor_id);
    
        // returns the associated object
        return $this->servidor;
    }
    


}
