<?php
/**
 * controle_geracao Active Record
 * @author  <your-name-here>
 */
class controle_geracao extends TRecord
{
    const TABLENAME = 'g_geral.controle_geracao';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $historico_geracaos;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('dt_inicial');
        parent::addAttribute('usuario');
        parent::addAttribute('status');            //1=>gerado, 5=>retificado, 13=>cancelado
        parent::addAttribute('dt_atualizacao');
    }

    
    /**
     * Method addhistorico_geracao
     * Add a historico_geracao to the controle_geracao
     * @param $object Instance of historico_geracao
     */
    public function addhistorico_geracao(historico_geracao $object)
    {
        $this->historico_geracaos[] = $object;
    }
    
    /**
     * Method gethistorico_geracaos
     * Return the controle_geracao' historico_geracao's
     * @return Collection of historico_geracao
     */
    public function gethistorico_geracaos()
    {
        return $this->historico_geracaos;
    }

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->historico_geracaos = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    public function load($id)
    {
    
        // load the related historico_geracao objects
        $repository = new TRepository('historico_geracao');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_geracao_id', '=', $id));
        $this->historico_geracaos = $repository->load($criteria);
    
        // load the object itself
        return parent::load($id);
    }

    /**
     * Store the object and its aggregates
     */
    public function store()
    {
        // store the object itself
        parent::store();
    
        // delete the related historico_geracao objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_geracao_id', '=', $this->id));
        $repository = new TRepository('historico_geracao');
        $repository->delete($criteria);
        // store the related historico_geracao objects
        if ($this->historico_geracaos)
        {
            foreach ($this->historico_geracaos as $historico_geracao)
            {
                unset($historico_geracao->id);
                $historico_geracao->controle_geracao_id = $this->id;
                $historico_geracao->store();
            }
        }
    }

    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        $id = isset($id) ? $id : $this->id;
        // delete the related historico_geracao objects
        $repository = new TRepository('historico_geracao');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('controle_geracao_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }


}
