<?php
/**
 * ItemCadastroForm Form
 * @author  <your name here>
 */
class ItemCadastroForm extends TPage
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
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Item');
        


        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $dominio = new TEntry('dominio');
        $subdominio = new TEntry('subdominio');
        $oculto = new TEntry('oculto');
        $ordem = new TEntry('ordem');


        // add the fields
        $this->form->addQuickField('Id', $id,  100 );
        $this->form->addQuickField('Nome', $nome,  200 );
        $this->form->addQuickField('Dominio', $dominio,  200 );
        $this->form->addQuickField('Subdominio', $subdominio,  200 );
        $this->form->addQuickField('Oculto', $oculto,  200 );
        $this->form->addQuickField('Ordem', $ordem,  100 );




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
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
            $ci = new TSicadDados();            
            $itens  = Item::where('id', '>', '0')->delete();
            $itens = $ci->caracteristicas_SICAD();
            set_time_limit(120);
            //print_r($itens);
            foreach ($itens as $dominio => $item)
            {
                if ($dominio!='sexo')
                {
                    foreach ($item as $key => $dado)
                    {
                        $object = new Item;
                        $object->id = $key;
                        $object->dominio = $dominio;
                        $object->nome = strtoupper($dado);
                        $object->oculto = 'f';
                        $object->store();
                        //print_r ($object);
                        
                    }
                }
            }
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
