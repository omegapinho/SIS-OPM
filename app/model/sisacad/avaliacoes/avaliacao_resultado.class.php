<?php
/**
 * avaliacao_resultado Active Record
 * @author  <your-name-here>
 */
class avaliacao_resultado extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_resultado';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $avaliacao_turma;
    private $avaliacao_resultadoalunos;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_fim');
        parent::addAttribute('usuario_encerra');
        parent::addAttribute('oculto');
        parent::addAttribute('avaliacao_turma_id');
    }

    
    /**
     * Method set_avaliacao_turma
     * Sample of usage: $avaliacao_resultado->avaliacao_turma = $object;
     * @param $object Instance of avaliacao_turma
     */
    public function set_avaliacao_turma(avaliacao_turma $object)
    {
        $this->avaliacao_turma = $object;
        $this->avaliacao_turma_id = $object->id;
    }
    
    /**
     * Method get_avaliacao_turma
     * Sample of usage: $avaliacao_resultado->avaliacao_turma->attribute;
     * @returns avaliacao_turma instance
     */
    public function get_avaliacao_turma()
    {
        // loads the associated object
        if (empty($this->avaliacao_turma))
            $this->avaliacao_turma = new avaliacao_turma($this->avaliacao_turma_id);
    
        // returns the associated object
        return $this->avaliacao_turma;
    }
    
    
    /**
     * Method addavaliacao_resultadoaluno
     * Add a avaliacao_resultadoaluno to the avaliacao_resultado
     * @param $object Instance of avaliacao_resultadoaluno
     */
    public function addavaliacao_resultadoaluno(avaliacao_resultadoaluno $object)
    {
        $this->avaliacao_resultadoalunos[] = $object;
    }
    
    /**
     * Method getavaliacao_resultadoalunos
     * Return the avaliacao_resultado' avaliacao_resultadoaluno's
     * @return Collection of avaliacao_resultadoaluno
     */
    public function get_avaliacao_resultadoalunos()
    {
        if (empty($this->avaliacao_resultadoalunos))
        {
            $repository = new TRepository('avaliacao_resultadoaluno');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('avaliacao_resultado_id', '=', $this->id));
            $this->avaliacao_resultadoalunos = $repository->load($criteria);
        }
        return $this->avaliacao_resultadoalunos;
    }

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->avaliacao_resultadoalunos = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    /*public function load($id)
    {
    
        // load the related avaliacao_resultadoaluno objects
        $repository = new TRepository('avaliacao_resultadoaluno');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('avaliacao_resultado_id', '=', $id));
        $this->avaliacao_resultadoalunos = $repository->load($criteria);
    
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
    
        // delete the related avaliacao_resultadoaluno objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('avaliacao_resultado_id', '=', $this->id));
        $repository = new TRepository('avaliacao_resultadoaluno');
        $repository->delete($criteria);
        // store the related avaliacao_resultadoaluno objects
        if ($this->avaliacao_resultadoalunos)
        {
            foreach ($this->avaliacao_resultadoalunos as $avaliacao_resultadoaluno)
            {
                unset($avaliacao_resultadoaluno->id);
                $avaliacao_resultadoaluno->avaliacao_resultado_id = $this->id;
                $avaliacao_resultadoaluno->store();
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
        // delete the related avaliacao_resultadoaluno objects
        $repository = new TRepository('avaliacao_resultadoaluno');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('avaliacao_resultado_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }*/


}
