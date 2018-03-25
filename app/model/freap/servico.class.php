<?php
/**
 * servico Active Record
 * @author  <your-name-here>
 */
class servico extends TRecord
{
    const TABLENAME = 'freap.servico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $tipo;
    private $opms;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('nome_chave');
        parent::addAttribute('codigo');
        parent::addAttribute('valor_base');
        parent::addAttribute('valor_km');
        parent::addAttribute('valor_pm');
        parent::addAttribute('valor_juros');
        parent::addAttribute('valor_multa');
        parent::addAttribute('valor_diaria');
        parent::addAttribute('pm_max');
        parent::addAttribute('pm_min');
        parent::addAttribute('diaria_min');
        parent::addAttribute('diaria_max');
        parent::addAttribute('oculto');
        parent::addAttribute('calcula_multas');
        parent::addAttribute('tipo_servico');
        parent::addAttribute('hora_virtual');
        parent::addAttribute('valor_diurno');
        parent::addAttribute('valor_noturno');
    }

    
    /**
     * Method set_tipo
     * Sample of usage: $servico->tipo = $object;
     * @param $object Instance of tipo
     */
    public function set_tipo(tipo $object)
    {
        $this->tipo = $object;
        $this->tipo_servico = $object->id;
    }
    
    /**
     * Method get_tipo
     * Sample of usage: $servico->tipo->attribute;
     * @returns tipo instance
     */
    public function get_tipo()
    {
        // loads the associated object
        if (empty($this->tipo))
            $this->tipo = new tipo($this->tipo_servico);
    
        // returns the associated object
        return $this->tipo;
    }
    
    
    /**
     * Method addOPM
     * Add a OPM to the servico
     * @param $object Instance of OPM
     */
    /*public function addOPM(OPM $object)
    {
        $this->opms[] = $object;
    }
    
    /**
     * Method getOPMs
     * Return the servico' OPM's
     * @return Collection of OPM
     */
    /*public function getOPMs()
    {
        return $this->opms;
    }

    
    /**
     * Method getcontratos
     */
    /*public function getcontratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_servico', '=', $this->id));
        return contrato::getObjects( $criteria );
    }
    

    /**
     * Reset aggregates
     */
    /*public function clearParts()
    {
        $this->opms = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    /*public function load($id)
    {
    
        // load the related OPM objects
        $repository = new TRepository('grupo_servico');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_servico', '=', $id));
        $servico_opms = $repository->load($criteria);
        if ($servico_opms)
        {
            foreach ($servico_opms as $servico_opm)
            {
                $opm = new OPM( $servico_opm->id_opm );
                $this->addOPM($opm);
            }
        }
    
        // load the object itself
        return parent::load($id);
    }

    /**
     * Store the object and its aggregates
     */
    /*public function store()
    {
        // store the object itself
        parent::store();
    
        // delete the related grupo_servico objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_servico', '=', $this->id));
        $repository = new TRepository('grupo_servico');
        $repository->delete($criteria);
        // store the related grupo_servico objects
        if ($this->opms)
        {
            foreach ($this->opms as $opm)
            {
                $servico_opm = new grupo_servico;
                $servico_opm->id_opm = $opm->id;
                $servico_opm->id_servico = $this->id;
                $servico_opm->store();
            }
        }
    }

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    /*public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // delete the related grupo_servico objects
        $repository = new TRepository('grupo_servico');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_servico', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }*/


}
