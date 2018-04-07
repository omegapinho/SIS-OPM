<?php
/**
 * turma Active Record
 * @author  <your-name-here>
 */
class turma extends TRecord
{
    const TABLENAME = 'sisacad.turma';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $alunos;       //Associação
    private $materias;     //Associação
    private $curso;        //Composição
    private $incidente_pedagogicos; //Associação
    private $inquerito_pedagogicos; //Associação
    private $opm;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('tipo_turma');
        parent::addAttribute('oculto');
        parent::addAttribute('cidade');
        parent::addAttribute('curso_id');
        parent::addAttribute('opm_id');
        parent::addAttribute('corporacao');//Acrescimo 2017-12-22
    }
    /**
     * Method addaluno
     * Add a aluno to the turma
     * @param $object Instance of aluno
     */
    public function addaluno(aluno $object)
    {
        $this->alunos[] = $object;
    }
    /**
     * Method addmateria
     * Add a materia to the turma
     * @param $object Instance of materia
     */
    public function addmateria(materia $object)
    {
        $this->materias[] = $object;
    }
    /**
     * Method getalunos
     * Return the turma' aluno's
     * @return Collection of aluno
     */
    public function getalunos()
    {
        if (empty($this->alunos))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('turma_id', '=', $this->id));
            $this->alunos = aluno::getObjects( $criteria );
        }
        return $this->alunos;
    }
    /**
     * Method getmaterias
     */
    public function getmaterias()
    {
        if (empty($this->materias))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('turma_id', '=', $this->id));
            $this->materias = materia::getObjects( $criteria );
        }
        return $this->materias;
    }
    /**
     * Method getincidente_pedagogicos
     */
    public function getincidente_pedagogicos()
    {
        if (empty($this->incidente_pedagogicos))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('turma_id', '=', $this->id));
            $this->incidente_pedagogicos = incidente_pedagogico::getObjects( $criteria );
        }
        return $this->incidente_pedagogicos;
    }
    /**
     * Method getinquerito_pedagogicos
     */
    public function getinquerito_pedagogicos()
    {
        if (empty($this->inquerito_pedagogicos))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('turma_id', '=', $this->id));
            $this->inquerito_pedagogicos = inquerito_pedagogico::getObjects( $criteria );
        }
        return $this->inquerito_pedagogicos;
    }
    /**
     * Method get_curso
     * Sample of usage: $turma->curso->attribute;
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
    /**
     * Method get_opm
     * Sample of usage: $turma->opm->attribute;
     * @returns opm instance
     */
    public function get_opm()
    {
        // loads the associated object
        if (empty($this->opm))
            $this->opm = new OPM($this->opm_id);
    
        // returns the associated object
        return $this->opm;
    }
    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->alunos = array();
        $this->materias = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
/*    public function load($id)
    {
    
        // load the related aluno objects
        $repository = new TRepository('aluno');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $id));
        $this->alunos = $repository->load($criteria);
    
        // load the related materia objects
        $repository = new TRepository('materia');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $id));
        $this->materias = $repository->load($criteria);
    
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
    
        // delete the related aluno objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        $repository = new TRepository('aluno');
        //$repository->delete($criteria);
        // store the related aluno objects
        if ($this->alunos)
        {
            foreach ($this->alunos as $aluno)
            {
                unset($aluno->id);
                $aluno->turma_id = $this->id;
                $aluno->store();
            }
        }
        // delete the related materia objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        $repository = new TRepository('materia');
        //$repository->delete($criteria);
        // store the related materia objects
        if ($this->materias)
        {
            foreach ($this->materias as $materia)
            {
                unset($materia->id);
                $materia->turma_id = $this->id;
                $materia->store();
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
        // delete the related aluno objects
        $repository = new TRepository('aluno');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $id));
        $repository->delete($criteria);
        
        // delete the related materia objects
        $repository = new TRepository('materia');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }
//------------------------------------------------------------------------------
//Adicionado em 2018-04-05
    /**
     * Method getdocumentos_turmas
     */
    public function getdocumentos_turmas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        return documentos_turma::getObjects( $criteria );
    }
    
    
    /**
     * Method getavaliacao_turmas
     */
    public function getavaliacao_turmas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        return avaliacao_turma::getObjects( $criteria );
    }
    
    
    /**
     * Method getavaliacao_rankings
     */
    public function getavaliacao_rankings()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turma_id', '=', $this->id));
        return avaliacao_ranking::getObjects( $criteria );
    }


}
