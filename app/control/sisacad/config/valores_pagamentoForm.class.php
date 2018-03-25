<?php
/**
 * valores_pagamentoForm Form
 * @author  <your name here>
 */
class valores_pagamentoForm extends TPage
{
    protected $form; // form
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Turmas';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

/*------------------------------------------------------------------------------
 * Carrega configurações
 *------------------------------------------------------------------------------*/
        $fer   = new TFerramentas();                                            //Ferramentas diversas
        $sicad = new TSicadDados();                                             //Ferramentas SICAD
        $profile = TSession::getValue('profile');                               //Profile da Conta do usuário
        if (!$this->nivel_sistema || $this->config_load == false)               //Carrega OPMs que tem acesso
        {
            $this->opm_operador  = $sicad->get_OPM();                           //Carrega OPM do Usuário
            $this->nivel_sistema = $fer->getnivel (get_class($this));           //Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OPMsUsuario();                   //Carrega Listas de OPMs
            $this->config        = $fer->getConfig($this->sistema);             //Carrega config
            TSession::setValue('SISACAD_CONFIG', $this->config);                //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                                          //Informa que configuração foi carregada
        }
        // creates the form
        $this->form = new TQuickForm('form_valores_pagamento');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Cadastro de valores para pagamento');

        // create the form fields
        $id = new TEntry('id');
        $valor = new TEntry('valor');
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        $natureza = new TCombo('natureza');
        $titularidade_id = new TDBCombo('titularidade_id','sisacad','titularidade','id','nome','nivel');
        $nivel_pagamento_id = new TDBCombo('nivel_pagamento_id','sisacad','nivel_pagamento','id','nome','nome');
        
        //Mascaras
        $data_inicio->setMask('dd/mm/yyyy');
        $data_fim->setMask('dd/mm/yyyy');
        
        //Valores
        $natureza->addItems($fer->lista_natureza_curso());
        
        $natureza->setValue('2');


        // add the fields
        $this->form->addQuickField('Id', $id,  100 );
        $this->form->addQuickField('Natureza do Curso', $natureza,  400 , new TRequiredValidator);
        $this->form->addQuickField('Nivel do Ensino', $nivel_pagamento_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Título do Docente', $titularidade_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Início de Vigência', $data_inicio,  120 , new TRequiredValidator);
        $this->form->addQuickField('Fim de Vigência', $data_fim,  120 );
        $this->form->addQuickField('Valor R$', $valor,  120 , new TRequiredValidator);




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
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('valores_pagamentoList', 'onReload')), 'fa:table blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'sisacadConfiguracao'));
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
            TTransaction::open('sisacad'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            
            $object = new valores_pagamento;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            
            $data->data_inicio = TDate::date2us($data->data_inicio);
            $data->data_fim    = TDate::date2us($data->data_fim);
            
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            $data->data_inicio = TDate::date2br($data->data_inicio);
            $data->data_fim = TDate::date2br($data->data_fim);            
            
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
                TTransaction::open('sisacad'); // open a transaction
                $object = new valores_pagamento($key); // instantiates the Active Record
                $object->data_inicio = TDate::date2br($object->data_inicio);
                $object->data_fim = TDate::date2br($object->data_fim);
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
