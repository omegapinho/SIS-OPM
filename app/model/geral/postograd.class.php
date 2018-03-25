<?php
/**
 * postograd Active Record
 * @author  <your-name-here>
 */
class postograd extends TRecord
{
    const TABLENAME = 'g_geral.postograd';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $orgaosorigem;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('ordem');
        parent::addAttribute('oculto');
        parent::addAttribute('orgaosorigem_id');
    }

    
    /**
     * Method set_orgaosorigem
     * Sample of usage: $postograd->orgaosorigem = $object;
     * @param $object Instance of orgaosorigem
     */
    public function set_orgaosorigem(orgaosorigem $object)
    {
        $this->orgaosorigem = $object;
        $this->orgaosorigem_id = $object->id;
    }
    
    /**
     * Method get_orgaosorigem
     * Sample of usage: $postograd->orgaosorigem->attribute;
     * @returns orgaosorigem instance
     */
    public function get_orgaosorigem()
    {
        // loads the associated object
        if (empty($this->orgaosorigem))
            $this->orgaosorigem = new orgaosorigem($this->orgaosorigem_id);
    
        // returns the associated object
        return $this->orgaosorigem;
    }
    
    public function load ($id)
    {
        $objects = TSession::getValue('postograd');
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
            TSession::setValue('postograd',$objects);
        }
        if (array_key_exists($id,$objects))
        {
            $ret = $objects[$id];
        }
        return $ret;
    }//Fim Load    


}
