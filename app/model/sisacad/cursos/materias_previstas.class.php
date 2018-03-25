<?php
/**
 * materias_previstas Active Record
 * @author  <your-name-here>
 */
class materias_previstas extends TRecord
{
    const TABLENAME = 'sisacad.materias_previstas';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $disciplina;
    private $curso;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('carga_horaria');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('curso_id');
    }

    
    /**
     * Method set_disciplina
     * Sample of usage: $materias_previstas->disciplina = $object;
     * @param $object Instance of disciplina
     */
    public function set_disciplina(disciplina $object)
    {
        $this->disciplina = $object;
        $this->disciplina_id = $object->id;
    }
    
    /**
     * Method get_disciplina
     * Sample of usage: $materias_previstas->disciplina->attribute;
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
     * Method get_curso
     * Sample of usage: $materias_previstas->curso->attribute;
     * @returns curso instance
     */
    public function get_curso()
    {
        // loads the associated object
        if (empty($this->curso))
            $this->curso = new curso($this->curso_id);
    
        // returns the associated object
        return $this->curso;
    }

}
