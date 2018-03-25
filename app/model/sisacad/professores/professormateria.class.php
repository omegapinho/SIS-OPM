<?php
/**
 * professormateria Active Record
 * @author  <your-name-here>
 */
class professormateria extends TRecord
{
    const TABLENAME = 'sisacad.professormateria';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $materia;
    private $disciplina;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('professor_id');
        parent::addAttribute('materia_id');
        parent::addAttribute('vinculo');
        parent::addAttribute('ato_autorizacao');
    }
    /**
     * Method get_titularidade
     * Sample of usage: $professormateria->materia->attribute;
     * @returns materia instance
     */
    public function get_materia()
    {
        // loads the associated object
        if (empty($this->materia))
            $this->materia = new materia($this->materia_id);
    
        // returns the associated object
        return $this->materia;
    }
}
