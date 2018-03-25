<?php
/**
 * curso Active Record
 * @author  <your-name-here>
 */
class curso extends TRecord
{
    const TABLENAME = 'sisacad.curso';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $nivel_pagamento;        //Associação
    private $orgaoorigem;        //Associação
    private $materias_previstass;    //Associação
    
    private $turmas;                 //Composição

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_final');
        parent::addAttribute('carga_horaria');
        parent::addAttribute('oculto');
        parent::addAttribute('tipo_curso');
        parent::addAttribute('turno');
        parent::addAttribute('natureza');
        parent::addAttribute('ementa_ok');
        parent::addAttribute('ato_autorizacao');
        parent::addAttribute('nivel_pagamento_id');
        parent::addAttribute('orgaoorigem_id');
        parent::addAttribute('corporacao');//Acrescimo 2017-12-22
    }

    
    /**
     * Method set_nivel_pagamento
     * Sample of usage: $curso->nivel_pagamento = $object;
     * @param $object Instance of nivel_pagamento
     */
    public function set_nivel_pagamento(nivel_pagamento $object)
    {
        $this->nivel_pagamento = $object;
        $this->nivel_pagamento_id = $object->id;
    }
    
    /**
     * Method addturma
     * Add a turma to the curso
     * @param $object Instance of turma
     */
    public function addturma(turma $object)
    {
        $this->turmas[] = $object;
    }
    /**
     * Method addmaterias_previstas
     * Add a materias_previstas to the curso
     * @param $object Instance of materias_previstas
     */
    public function addmaterias_previstas(materias_previstas $object)
    {
        $this->materias_previstass[] = $object;
    }
    /**
     * Method get_nivel_pagamento
     * Sample of usage: $curso->nivel_pagamento->attribute;
     * @returns nivel_pagamento instance
     */
    public function get_nivel_pagamento()
    {
        // loads the associated object
        if (empty($this->nivel_pagamento))
            $this->nivel_pagamento = new nivel_pagamento($this->nivel_pagamento_id);
    
        // returns the associated object
        return $this->nivel_pagamento;
    }
    /**
     * Method getturmas
     * Return the curso' turma's
     * @return Collection of turma
     */
    public function getturmas()
    {
        if (empty($this->turmas))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('curso_id', '=', $this->id));
            $this->turmas = turma::getObjects( $criteria );
        }
        return $this->turmas;
        
    }
    /**
     * Method getmaterias_previstass
     */
    public function getmaterias_previstass()
    {
        if (empty($this->materias_previstass))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('curso_id', '=', $this->id));
            $this->materias_previstass = materias_previstas::getObjects( $criteria );
        }
        return $this->materias_previstass;
        
    }
    /**
     * Method get_orgaoorigem
     * Sample of usage: $curso->orgaoorigem->attribute;
     * @returns orgaoorigem instance
     */
    public function get_orgaoorigem()
    {
        // loads the associated object
        if (empty($this->orgaoorigem))
            $this->orgaoorigem = new orgaoorigem($this->orgaoorigem_id);
    
        // returns the associated object
        return $this->orgaoorigem;
    }

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->turmas = array();
        $this->materias_previstass = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
/*    public function load($id)
    {
        // load the related turma objects
        $repository = new TRepository('turma');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('curso_id', '=', $id));
        $this->turmas = $repository->load($criteria);
    
        // load the related materias_previstas objects
        $repository = new TRepository('materias_previstas');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('curso_id', '=', $id));
        $this->materias_previstass = $repository->load($criteria);
    
        // load the object itself
        return parent::load($id);
    }*/

    /**
     * Store the object and its aggregates
     */
    /*public function store()
    {
        // store the object itself
        parent::store();
    
        // delete the related turma objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('curso_id', '=', $this->id));
        $repository = new TRepository('turma');
        //$repository->delete($criteria);
        // store the related turma objects
        if ($this->turmas)
        {
            foreach ($this->turmas as $turma)
            {
                unset($turma->id);
                $turma->curso_id = $this->id;
                $turma->store();
            }
        }
        // delete the related materias_previstas objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('curso_id', '=', $this->id));
        $repository = new TRepository('materias_previstas');
        //$repository->delete($criteria);
        // store the related materias_previstas objects
        if ($this->materias_previstass)
        {
            foreach ($this->materias_previstass as $materias_previstas)
            {
                unset($materias_previstas->id);
                $materias_previstas->curso_id = $this->id;
                $materias_previstas->store();
            }
        }
    }*/

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // delete the related turma objects
        $repository = new TRepository('turma');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('curso_id', '=', $id));
        $repository->delete($criteria);
        
        // delete the related materias_previstas objects
        $repository = new TRepository('materias_previstas');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('curso_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }


}
