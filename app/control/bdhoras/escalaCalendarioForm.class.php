<?php
/**
 * escalaCalendarioForm
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class escalaCalendarioForm extends TWindow
{
    protected $form; // form
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    public function __construct()
    {
        parent::__construct();
        parent::setSize(640, null);
        parent::setTitle('Edição de Escala por Dia');
        
        // creates the form
        $this->form = new TForm('form_event');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'width: 600px';
        
        // add a table inside form
        $table = new TTable;
        $table-> width = '100%';
        $this->form->add($table);
        
        // add a row for the form title
        $row = $table->addRow();
        $row->class = 'tformtitle'; // CSS class
        $row->addCell( new TLabel('Escala') )->colspan = 2;
        
        // create the form fields
        $view           = new THidden('view');
        $id             = new TEntry('id');
        $rgmilitar      = new TEntry('rgmilitar');
        $datainicio     = new TEntry('datainicio');
        $datafim        = new TEntry('datafim');
        $status         = new TCombo('status');
        $turnos_id      = new TDBCombo('turnos_id','sicad','turnos','id','nome','nome');
        $opm_id         = new TDBCombo('opm_id','sicad','OPM','id','nome','nome');
        $opm_id_info    = new TDBCombo('opm_id_info','sicad','OPM','id','nome','nome');
        
        $id->setEditable(FALSE);
        $rgmilitar->setEditable(FALSE);
        $datainicio->setEditable(FALSE);
        $datafim->setEditable(FALSE);
        $status->setEditable(FALSE);
        $turnos_id->setEditable(FALSE);
        $opm_id->setEditable(FALSE);
        $opm_id_info->setEditable(FALSE);
        
        $itens = array ("P"=>"PENDENTE","T"=>"TRABALHADO","F"=>"FALTA","D"=>"DISPENSA","A"=>"AFASTADO");
        $status->addItems($itens);
        
        // define the sizes
        $id->setSize(40);
        $rgmilitar->setSize(80);
        $datainicio->setSize(150);
        $datafim->setSize(150);
        $status->setSize(200);
        $turnos_id->setSize(200);
        $opm_id->setSize(400);
        $opm_id_info->setSize(400);

        // add one row for each form field
        $table->addRowSet( $view );
        $table->addRowSet( array(new TLabel('ID:'), $id ));
        $table->addRowSet( array(new TLabel('RG Militar:'), $rgmilitar ));
        $table->addRowSet( array(new TLabel('Data Inicial:'), $datainicio,new TLabel('Data Final:'), $datafim) );
        $table->addRowSet( array(new TLabel('Status:'), $status ));
        $table->addRowSet( array(new TLabel('Turno:'), $turnos_id));
        $table->addRowSet( array(new TLabel('OPM de Lotação:'), $opm_id));
        $table->addRowSet( array(new TLabel('OPM Informante:'), $opm_id_info));

        //Botão de Trabalho
        $trab_button=new TButton('trabalhou');
        $trab_button->setAction(new TAction(array($this, 'onTrabalhou')), "Trabalhou");
        $trab_button->setImage('fa:calendar-check-o green');

 
        // Botão de Falta
        $falt_button=new TButton('faltou');
        $falt_button->setAction(new TAction(array($this, 'onFaltou')), "Faltou");
        $falt_button->setImage('fa:calendar-times-o red');

        //Botão de Dispensa
        $disp_button=new TButton('dispensa');
        $disp_button->setAction(new TAction(array($this, 'onDispensou')), "Dispensado");
        $disp_button->setImage('fa:child blue');
        
        // create an del button (edit with no parameters)
        $del_button=new TButton('del');
        $del_button->setAction(new TAction(array($this, 'onDelete')), _t('Delete'));
        $del_button->setImage('fa:trash-o red');

        // create an return button
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna ao Calendário');
        $ret_button->setImage('fa:arrow-left black');
        
        $this->form->setFields(array($id, $view, $rgmilitar,$datafim,$datainicio,$status,$turnos_id,$opm_id,$opm_id_info,
                                 $trab_button,$falt_button,$disp_button,$del_button,$ret_button));
        
        $buttons_box = new THBox;
        $buttons_box->add($trab_button);
        $buttons_box->add($falt_button);
        $buttons_box->add($disp_button);
        $buttons_box->add($del_button);
        $buttons_box->add($ret_button);
        
        // add a row for the form action
        $row = $table->addRow();
        $row->class = 'tformaction'; // CSS class
        $row->addCell($buttons_box)->colspan = 2;
        
        parent::add($this->form);
    }//Fim Módulo
 /* -------------------------------------------------------------------------------
  *        Salvar 
  * -------------------------------------------------------------------------------*/
    public function onSave()
    {
        try
        {
            // open a transaction with database 'samples'
            TTransaction::open('sicad');
            
            //$this->form->validate(); // form validation
            
            // get the form data into an active record Entry
            $data = $this->form->getData();
            
            $object = new CalendarEvent;
            $object->color = $data->color;
            $object->id = $data->id;
            $object->title = $data->title;
            $object->description = $data->description;
            $object->start_time = $data->start_date . ' ' . str_pad($data->start_hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($data->start_minute, 2, '0', STR_PAD_LEFT) . ':00';
            $object->end_time = $data->end_date . ' ' . str_pad($data->end_hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($data->end_minute, 2, '0', STR_PAD_LEFT) . ':00';
            
            $object->store(); // stores the object
            
            $data->id = $object->id;
            $this->form->setData($data); // keep form data
            
            TTransaction::close(); // close the transaction
            $posAction = new TAction(array('FullCalendarDatabaseView', 'onReload'));
            $posAction->setParameter('view', $data->view);
            $posAction->setParameter('date', $data->start_date);
            
            // shows the success message
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'), $posAction);
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            
            $this->form->setData( $this->form->getData() ); // keep form data
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
 /* -------------------------------------------------------------------------------
  *        Editar 
  * -------------------------------------------------------------------------------*/
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                // get the parameter $key
                $key=$param['key'];
                
                // open a transaction with database 'samples'
                TTransaction::open('sicad');
                
                // instantiates object CalendarEvent
                $object = new historicotrabalho($key);
                
                $data = new stdClass;
                $data->id = $object->id;
                $data->rgmilitar   = $object->rgmilitar;
                $data->datainicio  = $object->datainicio;
                $data->datafim     = $object->datafim;
                $data->status      = $object->status;//substr($object->start_time,0,10);
                $data->turnos_id   = $object->turnos_id;//substr($object->start_time,11,2);
                $data->opm_id      = $object->opm_id;
                $data->opm_id_info = $object->opm_id_info;
                $data->view = $param['view'];
                
                // fill the form with the active record data
                $this->form->setData($data);
                
                // close the transaction
                TTransaction::close();
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
 /* -------------------------------------------------------------------------------
  *        Pergunta pela Deleção 
  * -------------------------------------------------------------------------------*/
    public static function onDelete($param)
    {
        // define the delete action
        $action = new TAction(array('escalaCalendarioForm', 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
 /* -------------------------------------------------------------------------------
  *        Deleta 
  * -------------------------------------------------------------------------------*/
    public static function Delete($param)
    {
        try
        {
            // get the parameter $key
            $key = $param['id'];
            // open a transaction with database
            TTransaction::open('sicad');
            
            // instantiates object
            $object = new historicotrabalho($key, FALSE);
            
            // deletes the object from the database
            $object->delete();
            
            // close the transaction
            TTransaction::close();
            
            $posAction = new TAction(array('escalaCalendarioView', 'onReload'));
            $posAction->setParameter('view', $param['view']);
            $posAction->setParameter('date', $param['start_date']);
            
            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record deleted'), $posAction);
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
 /* -------------------------------------------------------------------------------
  *        Salvar 
  * -------------------------------------------------------------------------------*/
    public function onStartEdit($param)
    {
        $this->form->clear();
        $data = new stdClass;
        $data->view = $param['view']; // calendar view
        $data->color = '#3a87ad';
        
        if ($param['date'])
        {
            if (strlen($param['date']) == 10)
            {
                $data->start_date = $param['date'];
                $data->end_date = $param['date'];
            }
            if (strlen($param['date']) == 19)
            {
                $data->start_date   = substr($param['date'],0,10);
                $data->start_hour   = substr($param['date'],11,2);
                $data->start_minute = substr($param['date'],14,2);
                
                $data->end_date   = substr($param['date'],0,10);
                $data->end_hour   = substr($param['date'],11,2) +1;
                $data->end_minute = substr($param['date'],14,2);
            }
            $this->form->setData( $data );
        }
    }
 /* -------------------------------------------------------------------------------
  *        Salvar 
  * -------------------------------------------------------------------------------*/
    public static function onUpdateEvent($param)
    {
        try
        {
            if (isset($param['id']))
            {
                // get the parameter $key
                $key=$param['id'];
                
                // open a transaction with database 'samples'
                TTransaction::open('samples');
                
                // instantiates object CalendarEvent
                $object = new CalendarEvent($key);
                $object->start_time = str_replace('T', ' ', $param['start_time']);
                $object->end_time   = str_replace('T', ' ', $param['end_time']);
                $object->store();
                                
                // close the transaction
                TTransaction::close();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
 /* -------------------------------------------------------------------------------
  *        Retorna para o calendário 
  * -------------------------------------------------------------------------------*/
    public function onReturn ($param=null)
    {
        TApplication::loadPage('escalaCalendarioView','onLoad');
    }//Fim Módulo
 /* -------------------------------------------------------------------------------
  *        Função Confirma trabalho 
  * -------------------------------------------------------------------------------*/
    public function onChangeStatus($param="P")
    {
        try
        {
            // open a transaction with database 'samples'
            TTransaction::open('sicad');
            
            //$this->form->validate(); // form validation
            
            // get the form data into an active record Entry
            $data = $this->form->getData();
            
            $object = new historicotrabalho($data->id);
            $object->status = $param;
            $object->store(); // stores the object
            $data->status = $param;
            $this->form->setData($data); // keep form data
            
            TTransaction::close(); // close the transaction
            /*$posAction = new TAction(array('escalaCalendarioView', 'onReload'));
            $posAction->setParameter('view', $data->view);
            $posAction->setParameter('date', $data->start_date);*/
            // shows the success message
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));//, $posAction);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback();
        }
    }//Fim Módulo
 /* -------------------------------------------------------------------------------
  *        Função Trabalhou 
  * -------------------------------------------------------------------------------*/
    public function onTrabalhou()
    {
        self::onChangeStatus("T");
    }//Fim Módulo
 /* -------------------------------------------------------------------------------
  *        Função Falta 
  * -------------------------------------------------------------------------------*/
    public function onFaltou()
    {
        self::onChangeStatus("F");
    }//Fim Módulo
 /* -------------------------------------------------------------------------------
  *        Função Falta 
  * -------------------------------------------------------------------------------*/
    public function onDispensou()
    {
        self::onChangeStatus("D");
    }//Fim Módulo
}//Fim da Classe