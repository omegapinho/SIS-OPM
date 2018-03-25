<?php
/**
 * incidente_pedagogico Active Record
 * @author  <your-name-here>
 */
class incidente_pedagogico extends TRecord
{
    const TABLENAME = 'sisacad.incidente_pedagogico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $professor;
    private $turma;
    private $tipo_incidente;
    private $controle_aula;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dt_inicio');
        parent::addAttribute('tipo');
        parent::addAttribute('status');
        parent::addAttribute('justificado');
        parent::addAttribute('professor_id');
        parent::addAttribute('turma_id');
        parent::addAttribute('tipo_incidente_id');
        parent::addAttribute('materia_id');
        parent::addAttribute('controle_aula_id');
    }

    
    /**
     * Method set_professor
     * Sample of usage: $incidente_pedagogico->professor = $object;
     * @param $object Instance of professor
     */
    public function set_professor(professor $object)
    {
        $this->professor = $object;
        $this->professor_id = $object->id;
    }
    
    /**
     * Method get_professor
     * Sample of usage: $incidente_pedagogico->professor->attribute;
     * @returns professor instance
     */
    public function get_professor()
    {
        // loads the associated object
        if (empty($this->professor))
            $this->professor = new professor($this->professor_id);
    
        // returns the associated object
        return $this->professor;
    }
    
    
    /**
     * Method set_turma
     * Sample of usage: $incidente_pedagogico->turma = $object;
     * @param $object Instance of turma
     */
    public function set_turma(turma $object)
    {
        $this->turma = $object;
        $this->turma_id = $object->id;
    }
    
    /**
     * Method get_turma
     * Sample of usage: $incidente_pedagogico->turma->attribute;
     * @returns turma instance
     */
    public function get_turma()
    {
        // loads the associated object
        if (empty($this->turma))
            $this->turma = new turma($this->turma_id);
    
        // returns the associated object
        return $this->turma;
    }
    
    
    /**
     * Method set_tipo_incidente
     * Sample of usage: $incidente_pedagogico->tipo_incidente = $object;
     * @param $object Instance of tipo_incidente
     */
    public function set_tipo_incidente(tipo_incidente $object)
    {
        $this->tipo_incidente = $object;
        $this->tipo_incidente_id = $object->id;
    }
    
    /**
     * Method get_tipo_incidente
     * Sample of usage: $incidente_pedagogico->tipo_incidente->attribute;
     * @returns tipo_incidente instance
     */
    public function get_tipo_incidente()
    {
        // loads the associated object
        if (empty($this->tipo_incidente))
            $this->tipo_incidente = new tipo_incidente($this->tipo_incidente_id);
    
        // returns the associated object
        return $this->tipo_incidente;
    }
    
   
    /**
     * Method getcontrole_aulas()
     * Sample of usage: $controle_aula->incidente_pedagogico->attribute;
     * @returns incidente_pedagogico instance
     */
    public function getcontrole_aulas()
    {
        // loads the associated object
        if (empty($this->controle_aula))
            $this->controle_aula = new controle_aula($this->controle_aula_id);
    
        // returns the associated object
        return $this->controle_aula;
    }
    


}
