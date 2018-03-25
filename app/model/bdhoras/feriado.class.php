<?php
/**
 * feriado Active Record
 * @author  <your-name-here>
 */
class feriado extends TRecord
{
    const TABLENAME = 'bdhoras.feriado';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $opms;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dataferiado');
        parent::addAttribute('nome');
        parent::addAttribute('tipo');
        parent::addAttribute('movel');
    }

    
    /**
     * Method addopm
     * Add a opm to the feriado
     * @param $object Instance of opm
     */
    public function addopm(OPM $object)
    {
        $this->opms[] = $object;
    }
    
    /**
     * Method getopms
     * Return the feriado' opm's
     * @return Collection of opm
     */
    public function getopms()
    {
        return $this->opms;
    }

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->opms = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    /*public function load($id)
    {
    
        // load the related opm objects
        $repository = new TRepository('feriadoopm');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('feriado_id', '=', $id));
        $feriado_opms = $repository->load($criteria);
        if ($feriado_opms)
        {
            foreach ($feriado_opms as $feriado_opm)
            {
                $opm = new OPM( $feriado_opm->opm_id );
                $this->addopm($opm);
            }
        }
    
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
    
        // delete the related feriadoopm objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('feriado_id', '=', $this->id));
        $repository = new TRepository('feriadoopm');
        $repository->delete($criteria);
        // store the related feriadoopm objects
        if ($this->opms)
        {
            foreach ($this->opms as $opm)
            {
                $feriado_opm = new feriadoopm;
                $feriado_opm->opm_id = $opm->id;
                $feriado_opm->feriado_id = $this->id;
                $feriado_opm->store();
            }
        }
    }*/

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
   /* public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // delete the related feriadoopm objects
        $repository = new TRepository('feriadoopm');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('feriado_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }*/


}
