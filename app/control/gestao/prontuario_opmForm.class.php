<?php
/**
 * prontuario_opmForm Form
 * @author  <your name here>
 */
class prontuario_opmForm extends TPage
{
    protected $form; // form
    
    var $popAtivo = true;
    var $sistema  = 'Gestão de OPM';  //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Dados OPM';      //Nome da página de serviço.
    
    protected $nivel_sistema = false;  //Registra o nível de acesso do usuário
    protected $config        = array();//Array com configuração
    protected $config_load   = false;  //Informa que a configuração está carregada
    
    private $up_date_pm    = false;  //Ativa desativa atualização pessoal
    static $up_date_opm   = true;  //Ativa desativa atualização de OPM    
    
    //Nomes registrados em banco de configuração e armazenados na array config
    //private $cfg_ord     = 'criar_ordinaria';
    
    private $opm_operador = false;     // Unidade do Usuário
    private $listas = false;           // Lista de valores e array de OPM
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_prontuario_opm');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Lista de OPMs que já possuem prontuários');
        
        // Inicía ferramentas auxiliares
        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if ($this->opm_operador==false)                     //Carrega OPM do usuário
        {
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
            $this->opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        }
        if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
        {
            $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
            $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                               //Informa que configuração foi carregada
        }
        


        // create the form fields
        $id = new TEntry('id');
        // Inicía ferramentas auxiliares
        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD        
        //Monta ComboBox com OPMs que o Operador pode ver
        //echo $this->nivel_sistema.'---'.$this->opm_operador;
        if ($this->nivel_sistema>=80)           //Adm e Gestor
        {
            $criteria = null;
        }
        else if ($this->nivel_sistema>=50 )     //Nível Operador (carrega OPM e subOPMs)
        {
            $criteria = new TCriteria;
            //Se não há lista de OPM, carrega só a OPM do usuário
            $lista = ($this->listas['valores']!='') ? $this->listas['valores'] : $profile['unidade']['id'];
            $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$lista."))";
            $criteria->add (new TFilter ('id','IN',$query));
        }
        else if ($this->nivel_sistema<50)       //nível de visitante (só a própria OPM)
        {
            $criteria = new TCriteria;
            $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$this->opm_operador."))";
            $criteria->add (new TFilter ('id','IN',$query));
        }
        $nome           = new TDBCombo('nome','sicad','OPM','id','nome','nome',$criteria);
        $status         = new TCombo('status');
        $oculto         = new TCombo('oculto');
        $dataativacao   = new TDate('dataativacao');
        $datainativacao = new TDate('datainativacao');
        $endereco       = new TEntry('endereco');
        $bairro         = new TEntry('bairro');
        
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('uf','=','GO'));
        
        $cidade = new TDBCombo('cidade','sicad','cidades','nome','nome','nome',$criteria);
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $doc_ativacao = new TEntry('doc_ativacao');

        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $oculto->setValue('N');
        $status->addItems(array('S'=>'Ativa','N'=>'Inativa'));
        $status->setValue('S');
        date_default_timezone_set('America/Sao_Paulo');
        $dataativacao->setValue(date('d/m/Y'));
        
        //Mascaras
        $telefone->setMask('(99)999999999');

        // add the fields
        $this->form->addQuickField('Id', $id,  50 );
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Status', $status,  100 );
        $this->form->addQuickField('Oculto ?', $oculto,  100 );
        $this->form->addQuickField('Data de ativação', $dataativacao,  120 );
        $this->form->addQuickField('Data de inativação', $datainativacao,  120 );
        $this->form->addQuickField('Endereço', $endereco,  400 );
        $this->form->addQuickField('Bairro', $bairro,  200 );
        $this->form->addQuickField('Cidade', $cidade,  200 );
        $this->form->addQuickField('Telefone', $telefone,  200 );
        $this->form->addQuickField('Email', $email,  200 );
        $this->form->addQuickField('Documento de Ativação', $doc_ativacao,  400 );

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $dataativacao->setEditable(FALSE);
            $datainativacao->setEditable(FALSE);
        }
        
        if ($fer->i_adm())
        {
            $criado = $nome->getValue();
            if (!empty($criado))
            {
                $nome->setEditable(FALSE);
                $status->setEditable(FALSE);
                $oculto->setEditable(FALSE);
            }
        }
        
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        if ($fer->i_adm())
        {
            $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        }
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', "prontuario_opmList"));
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
            
            $object = new prontuario_opm;  // create an empty object
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
                $object = new prontuario_opm($key); // instantiates the Active Record
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
