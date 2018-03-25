<?php
/**
 * escolaridade Active Record
 * @author  <your-name-here>
 */
class escolaridade extends TRecord
{
    const TABLENAME = 'sisacad.escolaridade';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $titularidade;
    private $documentacaos;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome_graduacao');
        parent::addAttribute('instituicao');
        parent::addAttribute('data_conclusao');
        parent::addAttribute('uf');
        parent::addAttribute('pais');
        parent::addAttribute('status');
        parent::addAttribute('arquivo_certificado');
        parent::addAttribute('comprovado');
        parent::addAttribute('data_apresentacao');
        parent::addAttribute('titularidade_id');
        parent::addAttribute('professor_id');
    }

    
    /**
     * Method set_titularidade
     * Sample of usage: $escolaridade->titularidade = $object;
     * @param $object Instance of titularidade
     */
    public function set_titularidade(titularidade $object)
    {
        $this->titularidade = $object;
        $this->titularidade_id = $object->id;
    }
    
    /**
     * Method get_titularidade
     * Sample of usage: $escolaridade->titularidade->attribute;
     * @returns titularidade instance
     */
    public function get_titularidade()
    {
        // loads the associated object
        if (empty($this->titularidade))
            $this->titularidade = new titularidade($this->titularidade_id);
    
        // returns the associated object
        return $this->titularidade;
    }
    
    
    /**
     * Method adddocumentacao
     * Add a documentacao to the escolaridade
     * @param $object Instance of documentacao
     */
    public function adddocumentacao(documentacao $object)
    {
        $this->documentacaos[] = $object;
    }
    
    /**
     * Method getdocumentacaos
     * Return the escolaridade' documentacao's
     * @return Collection of documentacao
     */
    public function getdocumentacaos()
    {
        return $this->documentacaos;
    }

    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        $this->documentacaos = array();
    }

    /**
     * Load the object and its aggregates
     * @param $id object ID
     */
    public function load($id)
    {
    
        // load the related documentacao objects
        $repository = new TRepository('documentacao');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escolaridade_id', '=', $id));
        $this->documentacaos = $repository->load($criteria);
    
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
    
        // delete the related documentacao objects
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escolaridade_id', '=', $this->id));
        $repository = new TRepository('documentacao');
        $repository->delete($criteria);
        // store the related documentacao objects
        if ($this->documentacaos)
        {
            foreach ($this->documentacaos as $documentacao)
            {
                unset($documentacao->id);
                $documentacao->escolaridade_id = $this->id;
                $documentacao->store();
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
        // delete the related documentacao objects
        $repository = new TRepository('documentacao');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escolaridade_id', '=', $id));
        $repository->delete($criteria);
        
    
        // delete the object itself
        parent::delete($id);
    }*/


}
