<?php
/**
 * indiceForm Form
 * @author  <your name here>
 */
class indiceForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_indice');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('indice');
        
        // create the form fields
        $id = new TEntry('id');
        $diario = new TEntry('diario');
        $segundosdiarios = new TEntry('segundosdiarios');
        $horassemanal = new TEntry('horassemanal');
        $valorhora = new TEntry('valorhora');
        $datavigencia = new TDate('datavigencia');

        //Máscara
        $datavigencia->setMask('dd/mm/yyyy');

        // add the fields
        $this->form->addQuickField('Id', $id,  50 );
        $this->form->addQuickField('Quantidade de Horas Diário', $diario,  80 , new TRequiredValidator);
        $this->form->addQuickField('Segundos Diários', $segundosdiarios,  80 , new TRequiredValidator);
        $this->form->addQuickField('Horas Semanal', $horassemanal,  80 , new TRequiredValidator);
        $this->form->addQuickField('Valor Hora', $valorhora,  80 );
        $this->form->addQuickField('Data de Início de Vigência', $datavigencia,  120 , new TRequiredValidator);




        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('indiceList', 'onReload')), 'ico_back.png');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'indiceList'));
        $container->add($this->form);
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('sicad'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            
            $object = new indice;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            $object->datavigencia = TDate::date2us($object->datavigencia);
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sicad'); // open a transaction
                $object = new indice($key); // instantiates the Active Record
                $object->datavigencia = TDate::date2br($object->datavigencia);;
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
