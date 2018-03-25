<?php
/**
 * disciplina Active Record
 * @author  <your-name-here>
 */
class disciplina extends TRecord
{
    const TABLENAME = 'sisacad.disciplina';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $professors;
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('oculto');
    }

    /**
     * Method addprofessor
     * Add a professor to the disciplina
     * @param $object Instance of professor
     */
    public function addprofessor(professor $object)
    {
        $this->professors[] = $object;
    }
    
    /**
     * Method getprofessors
     * Return the disciplina' professor's
     * @return Collection of professor
     */
    public function getprofessors()
    {
            if (empty($this->professors))
            {
                
            // load the related professor objects
            $repository = new TRepository('professordisciplina');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('disciplina_id', '=', $this->id));
            $disciplina_professors = $repository->load($criteria);
            if ($disciplina_professors)
            {
                foreach ($disciplina_professors as $disciplina_professor)
                {
                    $professor = new professor( $disciplina_professor->professor_id );
                    $this->addprofessor($professor);
                }
            }
            
        }
        
        return $this->professors;
    }
    
    /**
     * Method getinquerito_pedagogicos
     */
    public function getinquerito_pedagogicos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('disciplina_id', '=', $this->id));
        return inquerito_pedagogico::getObjects( $criteria );
    }
    
    
    /**
     * Method getmaterias
     */
    public function getmaterias()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('disciplina_id', '=', $this->id));
        return materia::getObjects( $criteria );
    }
    
    
    /**
     * Method getmaterias_previstass
     */
    public function getmaterias_previstass()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('disciplina_id', '=', $this->id));
        return materias_previstas::getObjects( $criteria );
    }
    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
/*    public function load($id)
    {
    
        // load the related professor objects
        $repository = new TRepository('professordisciplina');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('disciplina_id', '=', $id));
        $disciplina_professors = $repository->load($criteria);
        if ($disciplina_professors)
        {
            foreach ($disciplina_professors as $disciplina_professor)
            {
                $professor = new professor( $disciplina_professor->professor_id );
                $this->addprofessor($professor);
            }
        }
    
        // load the object itself
        return parent::load($id);
    }*/


}
