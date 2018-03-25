<?php
/**
 * turmaunidade Active Record
 * @author  <your-name-here>
 */
class turmaopm extends TRecord
{
    const TABLENAME = 'sisacad.turmaopm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
        
    private $turma;       //Associação
    private $opm;       //Associação
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('turma_id');
        parent::addAttribute('opm_id');
        parent::addAttribute('oculto');
    }
    /**
     * Method get_turma
     * Sample of usage: $turmaopm->turma->attribute;
     * @returns turmaopm instance
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
     * Method get_orgaosorigem
     * Sample of usage: $professor->orgaosorigem->attribute;
     * @returns orgaosorigem instance
     */
    public function get_opm()
    {
        // loads the associated object
        if (empty($this->opm))
            $this->opm = new OPM($this->opm_id);
    
        // returns the associated object
        return $this->opm;
    }
    

}

