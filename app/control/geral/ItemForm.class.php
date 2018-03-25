<?php
/**
 * ItemForm Form
 * @author  <your name here>
 */
class ItemForm extends TPage
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
        $this->form = new TQuickForm('form_Item');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:50%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Edição de Itens de Campo COMBO');

        // create the form fields
        $id = new TEntry('id');
        $ordem = new TEntry('ordem');
        $nome = new TEntry('nome');
        $dominio = new TEntry('dominio');
        $subdominio = new TEntry('subdominio');
        $oculto = new TCombo('oculto');
        
        //Define Valores Padrão
        $itemStatus= array();
        $itemStatus['t'] = 'Sim';
        $itemStatus['f'] = 'Não';
        $oculto->addItems($itemStatus);
        $oculto->setValue('f');
        
        $dominio->setCompletion(array('PARENTESCO','POSTO/GRADUAÇÃO','STATUS','SITUAÇÃO','SEXO','HABILITAÇÃO'));

        // add the fields
        $this->form->addQuickField('ID', $id,  50 );
        $this->form->addQuickField('Ordem', $ordem,  100 );
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Domínio', $dominio,  200 );
        $this->form->addQuickField('Subdomínio', $subdominio,  200 );
        $this->form->addQuickField('Oculto?', $oculto,  80 );




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
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('ItemList', 'onReload')), 'fa:table blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'ItemList'));
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
            
            $object = new Item;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
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
                $object = new Item($key); // instantiates the Active Record
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
