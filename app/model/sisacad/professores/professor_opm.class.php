<?php
/**
 * ProfessorOpm Active Record
 * @author  <your-name-here>
 */
class professor_opm extends TRecord
{
    const TABLENAME = 'sisacad.professor_opm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $professor;        //Associação
    private $opm;        //Associação
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('professor_id');
        parent::addAttribute('opm_id');
        parent::addAttribute('obs');
    }
    /**
     * Method get_professor
     * Sample of usage: $professor_opm->professor->attribute;
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
     * Method get_opm
     * Sample of usage: $professor_opm->opm->attribute;
     * @returns professor instance
     */
    public function get_opm()
    {
        // loads the associated object
        if (empty($this->opm))
            $this->opm = new opm($this->opm_id);
    
        // returns the associated object
        return $this->opm;
    }

}
