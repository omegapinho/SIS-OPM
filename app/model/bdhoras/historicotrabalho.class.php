<?php
/**
 * historico_trabalho Active Record
 * @author  <your-name-here>
 */
class historicotrabalho extends TRecord
{
    const TABLENAME = 'bdhoras.historicotrabalho';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $afastamentos;
    private $servidor;
    private $turnos;
    private $opm;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nometurno');
        parent::addAttribute('rgmilitar');
        parent::addAttribute('status');
        parent::addAttribute('datainicio');
        parent::addAttribute('datafim');
        parent::addAttribute('afastamento');
        parent::addAttribute('bgafastamento');
        parent::addAttribute('anobg');
        parent::addAttribute('remunerada');
        parent::addAttribute('afastamentos_id');
        parent::addAttribute('servidor_id');
        parent::addAttribute('turnos_id');
        parent::addAttribute('opm_id');
        parent::addAttribute('opm_id_info');
    }

    
    /**
     * Method set_afastamentos
     * Sample of usage: $historico_trabalho->afastamentos = $object;
     * @param $object Instance of afastamentos
     */
    /*public function set_afastamentos(afastamentos $object)
    {
        $this->afastamentos = $object;
        $this->afastamentos_id = $object->id;
    }
    
    /**
     * Method get_afastamentos
     * Sample of usage: $historico_trabalho->afastamentos->attribute;
     * @returns afastamentos instance
     */
    /*public function get_afastamentos()
    {
        // loads the associated object
        if (empty($this->afastamentos))
            $this->afastamentos = new afastamentos($this->afastamentos_id);
    
        // returns the associated object
        return $this->afastamentos;
    }
    
    
    /**
     * Method set_servidor
     * Sample of usage: $historico_trabalho->servidor = $object;
     * @param $object Instance of servidor
     */
    /*public function set_servidor(servidor $object)
    {
        $this->servidor = $object;
        $this->servidor_id = $object->id;
    }
    
    /**
     * Method get_servidor
     * Sample of usage: $historico_trabalho->servidor->attribute;
     * @returns servidor instance
     */
    /*public function get_servidor()
    {
        // loads the associated object
        if (empty($this->servidor))
            $this->servidor = new servidor($this->servidor_id);
    
        // returns the associated object
        return $this->servidor;
    }
    
    
    /**
     * Method set_turnos
     * Sample of usage: $historico_trabalho->turnos = $object;
     * @param $object Instance of turnos
     */
    /*public function set_turnos(turnos $object)
    {
        $this->turnos = $object;
        $this->turnos_id = $object->id;
    }
    
    /**
     * Method get_turnos
     * Sample of usage: $historico_trabalho->turnos->attribute;
     * @returns turnos instance
     */
    /*public function get_turnos()
    {
        // loads the associated object
        if (empty($this->turnos))
            $this->turnos = new turnos($this->turnos_id);
    
        // returns the associated object
        return $this->turnos;
    }
    
    
    /**
     * Method set_opm
     * Sample of usage: $historico_trabalho->opm = $object;
     * @param $object Instance of opm
     */
    /*public function set_opm(OPM $object)
    {
        $this->opm = $object;
        $this->opm_id = $object->id;
    }
    
    /**
     * Method get_opm
     * Sample of usage: $historico_trabalho->opm->attribute;
     * @returns opm instance
     */
    /*public function get_opm()
    {
        // loads the associated object
        if (empty($this->opm))
            $this->opm = new OPM($this->opm_id);
    
        // returns the associated object
        return $this->opm;
    }*/
    


}
