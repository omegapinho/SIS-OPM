<?php
/**
 * orgaosorigem Active Record
 * @author  <your-name-here>
 */
class orgaosorigem extends TRecord
{
    const TABLENAME = 'g_geral.orgaosorigem';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('oculto');
    }

    
    /**
     * Method getpostograds
     */
    public function getpostograds()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('orgaosorigem_id', '=', $this->id));
        return postograd::getObjects( $criteria );
    }
    
    public function load ($id)
    {
        $objects = TSession::getValue('orgaosorigem');
        $ret        = false;
        if (empty($objects) || !array_key_exists($id,$objects))
        {
            $object = parent::load($id);
            if (!is_array($objects))
            {
                $objects = array();
            }
            if ($object)
            {
                $objects[$id] = $object;
            }
            else
            {
                $objects[$id] = false;
            }
            TSession::setValue('orgaosorigem',$objects);
        }
        if (array_key_exists($id,$objects))
        {
            $ret = $objects[$id];
        }
        return $ret;
    }//Fim Load
    


}
