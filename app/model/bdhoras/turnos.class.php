<?php
/**
 * turnos Active Record
 * @author  <your-name-here>
 */
class turnos extends TRecord
{
    const TABLENAME = 'bdhoras.turnos';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('tag');
        parent::addAttribute('inicia_seg');
        parent::addAttribute('quarta');
        parent::addAttribute('sabado');
        parent::addAttribute('domingo');
        parent::addAttribute('qnt_h_turno1');
        parent::addAttribute('qnt_h_intervalo1');
        parent::addAttribute('qnt_h_turno2');
        parent::addAttribute('qnt_h_folga');
        parent::addAttribute('feriado');
        parent::addAttribute('descricao');
        parent::addAttribute('oculto');
    }

    
    /**
     * Method gethistorico_trabalhos
     */
    public function gethistorico_trabalhos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('turnos_id', '=', $this->id));
        return historico_trabalho::getObjects( $criteria );
    }
    


}
