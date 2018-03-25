<?php
/**
 * TCidade
 * Copyright (c) 
 * @author  Fernando de Pinho Araújo 
 * @version 1.0, 2016-12-27
 */
class TCidade 
{
    //protected $elements;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        //parent::__construct('div');
        //$this->id = 'taccordion_' . uniqid();
        //$this->elements = array();
        //define("rest_cidade", 'https://legadows-h.ssp.go.gov.br/cidadesPorEstado/');
    }
/*-------------------------------------------------------------------------------
 *                        Busca as Cidades
 *------------------------------------------------------------------------------- */
    public function cidades ($param)
    {
        if ($param)
        {
            $uf = $param;   
        }
        else
        {
            $uf="GO";
        }
        if ($uf==null || $uf=="" || (strlen($uf)<2)) 
        {
            $uf = "GO";
        }
        try
        {
            $items = TCidade::object_to_array(json_decode (file_get_contents('https://legadows-h.ssp.go.gov.br/cidadesPorEstado/'.$uf)));
            $lista = TCidade::make_list_city($items);
            return $lista;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            return false;
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Busca as UF
 *------------------------------------------------------------------------------- */
    function estados ()
    {
        try
        {
            $items = TCidade::object_to_array(json_decode (file_get_contents('https://legadows-h.ssp.go.gov.br/estados/')));
            $lista = TCidade::make_list_states($items);
            return $lista;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            return false;
        }
    }//Fim Módulo
    
    
/*-------------------------------------------------------------------------------
 *                        Transforma objeto em array
 *------------------------------------------------------------------------------- */    
    public function object_to_array($data) {
        if (is_array($data) || is_object($data)) 
        {
            $result = array();
            foreach ($data as $key => $value) 
            {
                $result[$key] = TCidade::object_to_array($value);
            }
            return $result;
        }
        return $data;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Cria lista com Id e Nome de cidades
 *------------------------------------------------------------------------------- */    
    static function make_list_city($data) 
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) 
            {
                $result[$value['id']]= $value['nome'];
            }
            return $result;
        }
        return false;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Cria lista com Id e Nome de estados
 *------------------------------------------------------------------------------- */    
    static function make_list_states($data) 
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) 
            {
                $result[$value['sigla']]= $value['nome'];
            }
            return $result;
        }
        return false;
    }//Fim Módulo

}//Fim da classe
