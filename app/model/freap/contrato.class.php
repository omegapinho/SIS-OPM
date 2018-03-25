<?php
/**
 * contrato Active Record
 * @author  <your-name-here>
 */
class contrato extends TRecord
{
    const TABLENAME = 'freap.contrato';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    use SystemChangeLogTrait;
    
    private $contribuinte;
    private $servico;
    private $cidades;
    private $estados;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('opm_nome');
        parent::addAttribute('valor_total');
        parent::addAttribute('qnt_km');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('doc_vinculo');
        parent::addAttribute('qnt_policial');
        parent::addAttribute('data_criado');
        parent::addAttribute('numero_sefaz');
        parent::addAttribute('qnt_horas');
        parent::addAttribute('cpf_atendente');
        parent::addAttribute('descricao_contratante');
        parent::addAttribute('descricao_servico');
        parent::addAttribute('uf_servico');
        parent::addAttribute('cidade_servico');
        parent::addAttribute('bairro_servico');
        parent::addAttribute('endereco_servico');
        parent::addAttribute('fone_contato_servico');
        parent::addAttribute('obs');
        parent::addAttribute('data_vencimento');
        parent::addAttribute('data_pagamento');
        parent::addAttribute('razao_social');
        parent::addAttribute('cpf_liberador');
        parent::addAttribute('id_contribuinte');
        parent::addAttribute('id_servico');
        parent::addAttribute('horas_diurno');
        parent::addAttribute('horas_noturno');
        //parent::addAttribute('id_cidades');
        //parent::addAttribute('id_estados');
    }

    
    /**
     * Method set_contribuinte
     * Sample of usage: $contrato->contribuinte = $object;
     * @param $object Instance of contribuinte
     */
    public function set_contribuinte(contribuinte $object)
    {
        $this->contribuinte = $object;
        $this->id_contribuinte = $object->id;
    }
    
    /**
     * Method get_contribuinte
     * Sample of usage: $contrato->contribuinte->attribute;
     * @returns contribuinte instance
     */
    public function get_contribuinte()
    {
        // loads the associated object
        if (empty($this->contribuinte))
            $this->contribuinte = new contribuinte($this->id_contribuinte);
    
        // returns the associated object
        return $this->contribuinte;
    }
    
    
    /**
     * Method set_servico
     * Sample of usage: $contrato->servico = $object;
     * @param $object Instance of servico
     */
    public function set_servico(servico $object)
    {
        $this->servico = $object;
        $this->id_servico = $object->id;
    }
    
    /**
     * Method get_servico
     * Sample of usage: $contrato->servico->attribute;
     * @returns servico instance
     */
    public function get_servico()
    {
        // loads the associated object
        if (empty($this->servico))
            $this->servico = new servico($this->id_servico);
    
        // returns the associated object
        return $this->servico;
    }
    
    
    /**
     * Method set_cidades
     * Sample of usage: $contrato->cidades = $object;
     * @param $object Instance of cidades
     */
    public function set_cidades(cidades $object)
    {
        $this->cidades = $object;
        $this->cidade_servico = $object->id;
    }
    
    /**
     * Method get_cidades
     * Sample of usage: $contrato->cidades->attribute;
     * @returns cidades instance
     */
    public function get_cidades()
    {
        // loads the associated object
        if (empty($this->cidades))
            $this->cidades = new cidades($this->cidade_servico);
    
        // returns the associated object
        return $this->cidades;
    }
    
    
    /**
     * Method set_estados
     * Sample of usage: $contrato->estados = $object;
     * @param $object Instance of estados
     */
    public function set_estados(estados $object)
    {
        $this->estados = $object;
        $this->estados_servico = $object->id;
    }
    
    /**
     * Method get_estados
     * Sample of usage: $contrato->estados->attribute;
     * @returns estados instance
     */
    public function get_estados()
    {
        // loads the associated object
        if (empty($this->estados))
            $this->estados = new estados($this->estados_servico);
    
        // returns the associated object
        return $this->estados;
    }
    


}
