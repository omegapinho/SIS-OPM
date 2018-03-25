<?php
/**
 * endereco Active Record
 * @author  <your-name-here>
 */
class endereco extends TRecord
{
    const TABLENAME = 'efetivo.endereco';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $servidor;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('logradouro');
        parent::addAttribute('numero');
        parent::addAttribute('quadra');
        parent::addAttribute('lote');
        parent::addAttribute('complemento');
        parent::addAttribute('cep');
        parent::addAttribute('bairro');
        parent::addAttribute('codbairro');
        parent::addAttribute('municipio');
        parent::addAttribute('codmunicipio');
        parent::addAttribute('uf');
        parent::addAttribute('estado');
        parent::addAttribute('oculto');
        parent::addAttribute('dtcadastro');
        parent::addAttribute('servidor_id');
    }

    
    /**
     * Method set_servidor
     * Sample of usage: $endereco->servidor = $object;
     * @param $object Instance of servidor
     */
    public function set_servidor(servidor $object)
    {
        $this->servidor = $object;
        $this->servidor_id = $object->id;
    }
    
    /**
     * Method get_servidor
     * Sample of usage: $endereco->servidor->attribute;
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
