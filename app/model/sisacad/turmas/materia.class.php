<?php
/**
 * materia Active Record
 * @author  <your-name-here>
 */
class materia extends TRecord
{
    const TABLENAME = 'sisacad.materia';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $disciplina;
    private $controle_aulas;
    private $incidente_pedagogicos;
    private $turma;
    private $avaliacao_final;//Resultado das matérias
    private $avaliacao_turma;//Avaliações da Matéria

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('carga_horaria');
        parent::addAttribute('disciplina_id');
        parent::addAttribute('turma_id');
        parent::addAttribute('oculto');//2018-03-05
    }

    
    /**
     * Method set_disciplina
     * Sample of usage: $materia->disciplina = $object;
     * @param $object Instance of disciplina
     */
    public function set_disciplina(disciplina $object)
    {
        $this->disciplina = $object;
        $this->disciplina_id = $object->id;
    }
    
    /**
     * Method get_disciplina
     * Sample of usage: $materia->disciplina->attribute;
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
     * Sample of usage: $materia->turma = $object;
     * @param $object Instance of turma
     */
    public function set_turma(turma $object)
    {
        $this->turma = $object;
        $this->turma_id = $object->id;
    }
    
    /**
     * Method addcontrole_aula
     * Add a controle_aula to the materia
     * @param $object Instance of controle_aula
     */
    public function addcontrole_aula(controle_aula $object)
    {
        $this->controle_aulas[] = $object;
    }
    
    /**
     * Method getcontrole_aulas
     * Return the materia' controle_aula's
     * @return Collection of controle_aula
     */
    public function getcontrole_aulas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $this->id));
        return controle_aula::getObjects( $criteria );
    }
    /**
     * Method get_avaliacao_final
     * Return avaliacao_final 
     * @return Collection of avaliacao_final
     */
    public function get_avaliacao_final()
    {
        if (empty($this->avaliacao_final))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('materia_id', '=', $this->id));
            $this->avaliacao_final = avaliacao_final::getObjects( $criteria );
            if (!empty($this->avaliacao_final))
            {
                $this->avaliacao_final = $this->avaliacao_final[0];//Só existe uma avaliacao final
            }
        }
        return $this->avaliacao_final;
    }
    /**
     * Method get_avaliacao_turma
     * Return avaliacao_turma 
     * @return Collection of avaliacao_turma
     */
    public function get_avaliacao_turma()
    {
        if (empty($this->avaliacao_turma))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('materia_id', '=', $this->id));
            $this->avaliacao_turma = avaliacao_turma::getObjects( $criteria );
            /*if (!empty($this->avaliacao_turma))
            {
                $this->avaliacao_final = $this->avaliacao_final[0];//Só existe uma avaliacao final
            }*/
        }
        return $this->avaliacao_turma;
    }
    /**
     * Method addincidente_pedagogico
     * Add a incidente_pedagogico to the materia
     * @param $object Instance of incidente_pedagogico
     */
    public function addincidente_pedagogico(incidente_pedagogico $object)
    {
        $this->incidente_pedagogicos[] = $object;
    }
    
    /**
     * Method getincidente_pedagogicos
     * Return the materia' incidente_pedagogico's
     * @return Collection of incidente_pedagogico
     */
    public function getincidente_pedagogicos()
    {
        return $this->incidente_pedagogicos;
    }

    /**
     * Method get_turma
     * Sample of usage: $materia->turma->attribute;
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
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->controle_aulas = array();
        $this->incidente_pedagogicos = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    /*public function load($id)
    {
    
        // load the related controle_aula objects
        $repository = new TRepository('controle_aula');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $id));
        $this->controle_aulas = $repository->load($criteria);
    
        // load the related incidente_pedagogico objects
        $repository = new TRepository('incidente_pedagogico');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $id));
        $this->incidente_pedagogicos = $repository->load($criteria);
    
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
    
        // delete the related controle_aula objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $this->id));
        $repository = new TRepository('controle_aula');
        $repository->delete($criteria);
        // store the related controle_aula objects
        if ($this->controle_aulas)
        {
            foreach ($this->controle_aulas as $controle_aula)
            {
                unset($controle_aula->id);
                $controle_aula->materia_id = $this->id;
                $controle_aula->store();
            }
        }
        // delete the related incidente_pedagogico objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $this->id));
        $repository = new TRepository('incidente_pedagogico');
        $repository->delete($criteria);
        // store the related incidente_pedagogico objects
        if ($this->incidente_pedagogicos)
        {
            foreach ($this->incidente_pedagogicos as $incidente_pedagogico)
            {
                unset($incidente_pedagogico->id);
                $incidente_pedagogico->materia_id = $this->id;
                $incidente_pedagogico->store();
            }
        }
    }*/

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    /*public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // delete the related controle_aula objects
        $repository = new TRepository('controle_aula');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $id));
        $repository->delete($criteria);
        
        // delete the related incidente_pedagogico objects
        $repository = new TRepository('incidente_pedagogico');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $id));
        $repository->delete($criteria);
        
        $repository = new TRepository('professormateria');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('materia_id', '=', $id));
        $repository->delete($criteria);
   
        // delete the object itself
        parent::delete($id);
    }*/


}
