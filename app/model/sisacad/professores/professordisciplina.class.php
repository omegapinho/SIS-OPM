<?php
/**
 * professordisciplina Active Record
 * @author  <your-name-here>
 */
class professordisciplina extends TRecord
{
    const TABLENAME = 'sisacad.professordisciplina';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $disciplina;
    private $professor;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('professor_id');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('opm_id');
    }
    /**
     * Method get_titularidade
     * Sample of usage: $professormateria->materia->attribute;
     * @returns materia instance
     */
    public function get_disciplina()
    {
        // loads the associated object
        if (empty($this->disciplina))
            $this->disciplina = new disciplina($this->disciplina_id);
    
        // returns the associated object
        return $this->disciplina;
    }
    public function get_professor()
    {
        // loads the associated object
        if (empty($this->professor))
            $this->professor = new professor($this->professor_id);
    
        // returns the associated object
        return $this->professor;
    }

}
