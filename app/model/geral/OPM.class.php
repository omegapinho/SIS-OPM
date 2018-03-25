<?php
/**
 * OPM Active Record
 * @author  <your-name-here>
 */
class OPM extends TRecord
{
    const TABLENAME = 'g_geral.opm';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    private $servicos;
    private $categorias = array();
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('idSuperior');
        parent::addAttribute('idsuperior');
        parent::addAttribute('sigla');
        parent::addAttribute('corporacao');
        parent::addAttribute('superior');
        parent::addAttribute('corporacaoId');
        parent::addAttribute('level');
        parent::addAttribute('telefone');
    }//Fim Módulo Construct

    public function getCategorias()
    {
        $categorias = array();
        
        // load the related System_program objects
        try
        {
            TTransaction::open('gdocs'); // open a transaction
            $repository = new TRepository('CategoriasOpm');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('opm_id', '=', $this->id));
            $categorias_opm = $repository->load($criteria);

            if ($categorias_opm)
            {
                foreach ($categorias_opm as $categoria_opm)
                {
                    $categorias[] = new CategoriasDoc($categoria_opm->categoria_id);
                    //echo $categoria_opm->id."<br>";
                }
            }
            TTransaction::close();
            //var_dump($categorias);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
        
        return $categorias;
    }
    
    /**
     * Delete the object and its aggregates
     * @param $id object ID
     */
    public function delete($id = NULL)
    {
        // delete the related System_groupSystem_program objects
        try
        {
            TTransaction::open('gdocs'); // open a transaction
            $id = isset($id) ? $id : $this->id;
            $repository = new TRepository('CategoriasOpm');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('opm_id', '=', $id));
            $repository->delete($criteria);
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
            
            // delete the object itself
            parent::delete($id);
            
    }//Fim Módulo
/*
 *
 */    
    
    public function addCategorias($categoria)
    {
        try
        {
            TTransaction::open('gdocs'); // open a transaction
            $object = new CategoriasOpm;
            $object->categoria_id = $categoria;
            $object->opm_id = $this->id;
            $object->store();
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
    
    /**
     * Reset aggregates
     */
    public function clearParts()
    {
        // delete the related System_groupSystem_program objects
        try
        {
            TTransaction::open('gdocs'); // open a transaction
            $criteria = new TCriteria;
            $criteria->add(new TFilter('opm_id', '=', $this->id));
            $repository = new TRepository('CategoriasOpm');
            $repository->delete($criteria);
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    

}
