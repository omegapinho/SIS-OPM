<?php
/**
 * controle_aula Active Record
 * @author  <your-name-here>
 */
class controle_aula extends TRecord
{
    const TABLENAME = 'sisacad.controle_aula';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $incidente_pedagogico;
    private $materia;
    private $professorcontrole_aulas;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dt_inicio');
        parent::addAttribute('horas_aula');
        parent::addAttribute('status');
        parent::addAttribute('justificativa');
        parent::addAttribute('hora_inicio');
        parent::addAttribute('materia_id');
        parent::addAttribute('conteudo');
        parent::addAttribute('cadastrador');
        parent::addAttribute('dt_cadastro');//2018-02-08
    }

    
    /**
     * Method set_incidente_pedagogico
     * Sample of usage: $controle_aula->incidente_pedagogico = $object;
     * @param $object Instance of incidente_pedagogico
     */
    public function set_incidente_pedagogico(incidente_pedagogico $object)
    {
        $this->incidente_pedagogico = $object;
        $this->incidente_pedagogico_id = $object->id;
    }
    
    /**
     * Method get_incidente_pedagogico
     * Sample of usage: $controle_aula->incidente_pedagogico->attribute;
     * @returns incidente_pedagogico instance
     */
    public function get_incidente_pedagogico()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        return incidente_pedagogico::getObjects( $criteria );
    }
    
    /**
     * Method get_professorcontrole_aula
     * Sample of usage: $controle_aula->professorcontrole_aula->attribute;
     * @returns incidente_pedagogico instance
     */
    public function get_professorcontrole_aula()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        return professorcontrole_aula::getObjects( $criteria );
    }
    /**
     * Method get_materia
     * Sample of usage: $controle_aula->incidente_pedagogico->attribute;
     * @returns incidente_pedagogico instance
     */
    public function get_materia()
    {
        //$criteria = new TCriteria;
        //$criteria->add(new TFilter('controle_aula_id', '=', $this->id));
        //return materia::getObjects( $criteria );
        // loads the associated object
        if (empty($this->materia))
            $this->materia = new materia($this->materia_id);
    
        // returns the associated object
        return $this->materia;
    }
    /**
     * Method getprofessorcontrole_aula
     * 
     */
    public function getprofessorcontrole_aulas()
    {
        // loads the associated object
        if (empty($this->professorcontrole_aulas))
            $this->professorcontrole_aulas = professorcontrole_aula::
                                                    where('controle_aula_id','=',$this->id)->load();
        // returns the associated object
        return $this->professorcontrole_aulas;
    }
    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // delete the related controle_aula objects
        $repository = new TRepository('professorcontrole_aula');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_aula_id', '=', $id));
        $repository->delete($criteria);
        
        // delete the related incidente_pedagogico objects
        $repository = new TRepository('incidente_pedagogico');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_aula_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }
}
