<?php
/**
 * Incidentes Active Record
 * @author  <your-name-here>
 */
class incidentes extends TRecord
{
    const TABLENAME = 'g_geral.incidentes';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $relator;         //Associação
    private $operador;        //Associação
    private $sistema;         //Associação
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('relator_id');
        parent::addAttribute('operador_id');
        parent::addAttribute('duplicata_id');
        parent::addAttribute('prioridade');
        parent::addAttribute('gravidade');
        parent::addAttribute('status');
        parent::addAttribute('resolucao');
        parent::addAttribute('categoria');
        parent::addAttribute('destino_id');
        parent::addAttribute('resumo');
        parent::addAttribute('categoria_id');
        parent::addAttribute('sistema_id');
        parent::addAttribute('servidor_id');
        parent::addAttribute('grupo_id');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('data_atual');
        parent::addAttribute('oculto');
        parent::addAttribute('json');       //2017-09-29
        parent::addAttribute('acesso');     //2017-09-30
    }
    /**
     * Method get_curso
     * Sample of usage: $turma->curso->attribute;
     * @returns curso instance
     */
    public function get_relator()
    {
        // loads the associated object
        if (empty($this->relator))
            $this->relator = new servidor($this->relator_id);
    
        // returns the associated object
        return $this->relator;
    }
    public function get_operador()
    {
        // loads the associated object
        if (empty($this->operador))
            $this->operador = new servidor($this->operador_id);
    
        // returns the associated object
        return $this->operador;
    }
    public function get_sistema()
    {
        // loads the associated object
        if (empty($this->sistema))
            $this->sistema = new Item($this->sistema_id);
    
        // returns the associated object
        return $this->sistema;
    }
    
    

}
