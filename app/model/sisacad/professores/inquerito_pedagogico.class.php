<?php
/**
 * inquerito_pedagogico Active Record
 * @author  <your-name-here>
 */
class inquerito_pedagogico extends TRecord
{
    const TABLENAME = 'sisacad.inquerito_pedagogico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $disciplina;
    private $turma;
    private $professor;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data');
        parent::addAttribute('nota');
        parent::addAttribute('obs');
        parent::addAttribute('resultado');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('turma_id');
        parent::addAttribute('professor_id');
    }

    
    /**
     * Method set_disciplina
     * Sample of usage: $inquerito_pedagogico->disciplina = $object;
     * @param $object Instance of disciplina
     */
    public function set_disciplina(disciplina $object)
    {
        $this->disciplina = $object;
        $this->disciplina_id = $object->id;
    }
    
    /**
     * Method get_disciplina
     * Sample of usage: $inquerito_pedagogico->disciplina->attribute;
     * @returns disciplina instance
     */
    public function get_disciplina()
    {
        // loads the associated object
        if (empty($this->disciplina))
            $this->disciplina = new disciplina($this->disciplina_id);
    
        // returns the associated object
        return $this->disciplina;
    }
    
    
    /**
     * Method set_turma
     * Sample of usage: $inquerito_pedagogico->turma = $object;
     * @param $object Instance of turma
     */
    public function set_turma(turma $object)
    {
        $this->turma = $object;
        $this->turma_id = $object->id;
    }
    
    /**
     * Method get_turma
     * Sample of usage: $inquerito_pedagogico->turma->attribute;
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
     * Method set_professor
     * Sample of usage: $inquerito_pedagogico->professor = $object;
     * @param $object Instance of professor
     */
    public function set_professor(professor $object)
    {
        $this->professor = $object;
        $this->professor_id = $object->id;
    }
    
    /**
     * Method get_professor
     * Sample of usage: $inquerito_pedagogico->professor->attribute;
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
    


}
