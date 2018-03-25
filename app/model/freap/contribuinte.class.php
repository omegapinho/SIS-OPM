<?php
/**
 * contribuinte Active Record
 * @author  <your-name-here>
 */
class contribuinte extends TRecord
{
    const TABLENAME = 'freap.contribuinte';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $cidades;
    private $estados;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cpf');
        parent::addAttribute('logradouro');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('uf');
        parent::addAttribute('cep');
        parent::addAttribute('data_cadastro');
        parent::addAttribute('oculto');
        parent::addAttribute('razao_social');
        parent::addAttribute('telefone');
        parent::addAttribute('celular');
        parent::addAttribute('data_nascimento');
        parent::addAttribute('estado_civil');
        parent::addAttribute('ocupacao');
        parent::addAttribute('email');
        parent::addAttribute('sexo');
        //parent::addAttribute('id_cidades');
        //parent::addAttribute('id_estados');
    }

    
    /**
     * Method set_cidades
     * Sample of usage: $contribuinte->cidades = $object;
     * @param $object Instance of cidades
     */
    public function set_cidades(cidades $object)
    {
        $this->cidades = $object;
        $this->id_cidades = $object->id;
    }
    
    /**
     * Method get_cidades
     * Sample of usage: $contribuinte->cidades->attribute;
     * @returns cidades instance
     */
    public function get_cidades()
    {
        // loads the associated object
        if (empty($this->cidades))
            $this->cidades = new cidades($this->cidade);
    
        // returns the associated object
        return $this->cidades;
    }
    
    
    /**
     * Method set_estados
     * Sample of usage: $contribuinte->estados = $object;
     * @param $object Instance of estados
     */
    public function set_estados(estados $object)
    {
        $this->estados = $object;
        $this->estado = $object->id;
    }
    
    /**
     * Method get_estados
     * Sample of usage: $contribuinte->estados->attribute;
     * @returns estados instance
     */
    public function get_estados()
    {
        // loads the associated object
        if (empty($this->estados))
            $this->estados = new estados($this->estado);
    
        // returns the associated object
        return $this->estados;
    }
    

    
    /**
     * Method getcontratos
     */
    public function getcontratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_contribuinte', '=', $this->id));
        return contrato::getObjects( $criteria );
    }
    


}
