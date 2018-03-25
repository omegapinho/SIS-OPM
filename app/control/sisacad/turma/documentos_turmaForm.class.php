<?php
/**
 * documentos_turmaForm Form
 * @author  <your name here>
 */
class documentos_turmaForm extends TPage
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
        
        $turma = TSession::getValue('turma_militar');
        if (empty($turma))
        {        
            TSession::setValue('turma_militar',null);
            TSession::setValue('curso_militar',null);
            TApplication::loadPage('cursoList');
        }
        
        // creates the form
        $this->form = new TQuickForm('form_documentos_turma');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Cadastro de Documentos em Turma');
        
        // create the form fields
        $id                   = new TEntry('id');
        $turma_id             = new TDBCombo('turma_id','sisacad','turma','id','nome','nome');
        $data_doc             = new THidden('data_doc');
        
        $criteria = new TCriteria();
        $criteria->add(new TFilter('oculto','!=','S'));
        $criteria->add(new TFilter('servico','=','TURMA'));
        $tipo_doc             = new TDBCombo('tipo_doc','sisacad','tipo_doc','nome','nome','nome',$criteria);
        
        $cadastrador          = new THidden('cadastrador');
        $comprovante          = new THidden('comprovante');
        $descricao            = new TText('descricao');
        $oculto               = new TCombo('oculto');
        $arquivos_externos_id = new THidden('arquivos_externos_id');
        $arquivo_selecionado  = new TEntry('arquivo_selecionado');
        $arquivo              = new TFile('arquivo');
        
        //Propriedades
        $arquivo->setProperty('accept','application/pdf');//Aceitar somente PDF
        
        //Valores
        if (!empty($turma))
        {
            $turma_id->setValue($turma->id);
            $turma_id->setEditable(false);
            if ($turma->documento == 'COMPROVANTE')
            {
                $comprovante->setValue('S');
                $tipo_doc->setValue('REGISTRO DE AULA');
                $tipo_doc->setEditable(false);
                $data_aula = new TDate('data_aula');
                //mascara
                $data_aula->setMask('dd-mm-yyyy');
            }
            else
            {
                $comprovante->setValue('N');
                $data_aula = new THidden('data_aula');
                $tipo_doc->setEditable(true);
            }
        }
        
        $cadastrador->setValue(TSession::getValue('login'));
        $oculto->addItems($fer->lista_sim_nao());
        $oculto->setValue('N');
        
        // add the fields
        $this->form->addQuickField('Id', $id,  80 );
        $this->form->addQuickField('Turma', $turma_id,  400 );
        $this->form->addQuickField('Adicionado', $data_doc,  120 );
        $this->form->addQuickField('Tipo', $tipo_doc,  400 , new TRequiredValidator);
        $this->form->addQuickField('Comprovante de Aula?', $comprovante,  120);
        if ($turma->documento == 'COMPROVANTE')
        {
            $this->form->addQuickField('Data da Aula', $data_aula,  120 , new TRequiredValidator);
        }
        else
        {
            $this->form->addQuickField('Data da Aula', $data_aula,  120);
        }
        
        $this->form->addQuickField('Cadastrador', $cadastrador,  400 );
        $this->form->addQuickField('Descrição', $descricao,  400 , new TRequiredValidator);
        $this->form->addQuickField('Oculto?', $oculto,  120 );
        $this->form->addQuickField('Arquivos Externos Id', $arquivos_externos_id,  80 );
        $this->form->addQuickField('Arquivo (Use o formato PDF)', $arquivo,  150, new TRequiredValidator );
        $this->form->addQuickField('Arquivo Selecionado', $arquivo_selecionado,  400);

        //Tamannhos
        $descricao->setSize(400,48);
        $arquivo->setSize(600);

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $arquivo_selecionado->setEditable(false);
        }
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        if (!isset($turma->retorno) || (isset($turma->retorno) && $turma->retorno=!'ControleAulaList'))
        {
            $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('documentos_turmaList', 'onReload')), 'ico_back.png');
        }
        else
        {
            $this->form->addQuickAction('Retorna ao Ctr. Aula',  new TAction(array('ControleAulaList', 'onReload')), 'ico_back.png');
        }
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
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

            $data = $this->form->getData(); // get form data as array
            //Cria o arquivo no BD se ele não existir
            //var_dump($data);
            if (empty($data->arquivos_externos_id))
            {
                //busca o arquivo para a memória
                $file = 'tmp/'.$data->arquivo;
                $filedata = file_get_contents($file);
                $escaped = bin2hex($filedata);
                $arquivo = new arquivos_externos;
                $arquivo->file_type   = 'application/pdf';
                $arquivo->contend     = '';
                $arquivo->filename    = $data->arquivo;
                $arquivo->cadastrador = $data->cadastrador;
                $arquivo->date_add    = date('Y-m-d');
                $arquivo->oculto      = 'N';
                $arquivo->store();
                $data->arquivo_selecionado = $data->arquivo;

                $sql = "UPDATE sisacad.arquivos_externos SET contend = decode('{$escaped}' , 'hex')  WHERE id=".$arquivo->id;
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();

            }
            else//Ou troca o arquivo mantendo a referencia ao documento de turma antigo
            {
                $arquivo = new arquivos_externos($data->arquivos_externos_id);
                if ($arquivo->filename != $data->arquivo)
                {
                    //echo '<br>'.$data->id;
                    if (!empty($data->id))//Colocando a id do documento que foi mudado 
                    {
                        $arquivo->documento_turma_antigo_id = (int) $data->id;
                        $arquivo->cadastrador = $data->cadastrador;
                        $arquivo->date_add    = date('Y-m-d');
                        $arquivo->oculto      = 'S';
                        $arquivo->store();
                    }
                    //busca o arquivo para a memória
                    $file = 'tmp/'.$data->arquivo;
                    $filedata = file_get_contents($file);
                    $escaped = bin2hex($filedata);

                    $arquivo = new arquivos_externos;
                    $arquivo->file_type   = 'application/pdf';
                    $arquivo->contend     = '';
                    $arquivo->filename    = $data->arquivo;
                    $arquivo->cadastrador = $data->cadastrador;
                    $arquivo->date_add    = date('Y-m-d');
                    $arquivo->oculto      = 'N';
                    $arquivo->store();
                    $sql = "UPDATE sisacad.arquivos_externos SET contend = decode('{$escaped}' , 'hex')  WHERE id=".$arquivo->id;
                    $conn = TTransaction::get();
                    $res = $conn->prepare($sql);
                    $res->execute();
                }
            }
            unlink($file);
            $data->arquivos_externos_id = $arquivo->id;//Atualiza form
            $data->arquivo_selecionado = $data->arquivo;
            
            //Armazena os dados do Documento
            $object = new documentos_turma;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            //Correção de Valores
            $object->arquivos_externos_id = $arquivo->id;
            $object->data_doc   = (!empty($data->data_doc)) ? $data->data_doc : date('Y-m-d');
            $object->data_aula  = (!empty($data->data_aula)) ? $data->data_aula : null;
            $object->tipo_doc   = (!empty($data->tipo_doc)) ? $data->tipo_doc : null;
            
            $object->data_aula = TDate::date2us($object->data_aula);
            
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            $turma = TSession::getValue('turma_militar');
            if ($data->comprovante =='S')
            {
                $turma->documento = 'COMPROVANTE';
                TCombo::disableField('form_documentos_turma','tipo_doc');
            }
            else
            {
                $turma->documento = 'DIVERSO';
                TCombo::enableField('form_documentos_turma','tipo_doc');
            }
            TSession::setValue('turma_militar',$turma);
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
                $object = new documentos_turma($key); // instantiates the Active Record
                if (!empty($object->arquivos_externos_id))
                {
                    $arquivo = new arquivos_externos($object->arquivos_externos_id);
                    $object->arquivo_selecionado = $arquivo->filename;
                    $object->arquivo = $arquivo->filename;
                    $object->arquivos_externos_id = $arquivo->id;
                }
                else
                {
                    $object->arquivo_selecionado = 'Nenhum arquivo selecionado.';
                }

                $turma = TSession::getValue('turma_militar');
                if ($object->comprovante == 'S')
                {
                    $turma->documento = 'COMPROVANTE';
                    TCombo::disableField('form_documentos_turma','tipo_doc');
                }
                else
                {
                    $turma->documento = 'DIVERSO';
                    TCombo::enableField('form_documentos_turma','tipo_doc');
                }
            TSession::setValue('turma_militar',$turma);
                $object->data_aula = TDate::date2br($object->data_aula);
                
                //var_dump($object);
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
