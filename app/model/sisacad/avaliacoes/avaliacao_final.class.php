<?php
/**
 * avaliacao_final Active Record
 * @author  <your-name-here>
 */
class avaliacao_final extends TRecord
{
    const TABLENAME = 'sisacad.avaliacao_final';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $materia;
    private $avaliacao_finalalunos;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_fim');
        parent::addAttribute('usuario_encerra');
        parent::addAttribute('oculto');
        parent::addAttribute('materia_id');
    }

    
    /**
     * Method set_materia
     * Sample of usage: $avaliacao_final->materia = $object;
     * @param $object Instance of materia
     */
    public function set_materia(materia $object)
    {
        $this->materia = $object;
        $this->materia_id = $object->id;
    }
    
    /**
     * Method get_materia
     * Sample of usage: $avaliacao_final->materia->attribute;
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
    

    
    /**
     * Method getavaliacao_finalalunos
     */
    public function get_avaliacao_finalalunos()
    {
        if (empty($this->avaliacao_finalalunos))
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('avaliacao_final_id', '=', $this->id));
            $this->avaliacao_finalalunos = avaliacao_finalaluno::getObjects( $criteria );
        }
        return $this->avaliacao_finalalunos;
    }
    


}
