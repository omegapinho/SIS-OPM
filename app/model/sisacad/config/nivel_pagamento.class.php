<?php
/**
 * nivel_pagamento Active Record
 * @author  <your-name-here>
 */
class nivel_pagamento extends TRecord
{
    const TABLENAME = 'sisacad.nivel_pagamento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $valores_pagamentos;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('oculto');
    }

    
    /**
     * Method addvalores_pagamento
     * Add a valores_pagamento to the nivel_pagamento
     * @param $object Instance of valores_pagamento
     */
    public function addvalores_pagamento(valores_pagamento $object)
    {
        $this->valores_pagamentos[] = $object;
    }
    
    /**
     * Method getvalores_pagamentos
     * Return the nivel_pagamento' valores_pagamento's
     * @return Collection of valores_pagamento
     */
    public function getvalores_pagamentos()
    {
        return $this->valores_pagamentos;
    }

    
    /**
     * Method getcursos
     */
    public function getcursos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('nivel_pagamento_id', '=', $this->id));
        return curso::getObjects( $criteria );
    }
    

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->valores_pagamentos = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    public function load($id)
    {
    
        // load the related valores_pagamento objects
        $repository = new TRepository('valores_pagamento');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('nivel_pagamento_id', '=', $id));
        $this->valores_pagamentos = $repository->load($criteria);
    
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
    
        // delete the related valores_pagamento objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('nivel_pagamento_id', '=', $this->id));
        $repository = new TRepository('valores_pagamento');
        $repository->delete($criteria);
        // store the related valores_pagamento objects
        if ($this->valores_pagamentos)
        {
            foreach ($this->valores_pagamentos as $valores_pagamento)
            {
                unset($valores_pagamento->id);
                $valores_pagamento->nivel_pagamento_id = $this->id;
                $valores_pagamento->store();
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
        // delete the related valores_pagamento objects
        $repository = new TRepository('valores_pagamento');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('nivel_pagamento_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }*/


}
