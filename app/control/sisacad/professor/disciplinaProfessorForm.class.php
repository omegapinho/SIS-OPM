<?php
/**
 * disciplinaProfessorForm Master/Detail
 * @author  <your name here>
 */
class disciplinaProfessorForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
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
        
        $professor = TSession::getValue('disciplina_rofessor');
        if (empty($professor) && $this->chamado != false)
        {
            $action = new TAction(array('professorList','onReload'));
            new TMessage('erro','Acesso negado! Retorne ao cadastro de Docente para acesso correto!',$action);
            exit;
            
        }
       
        // creates the form
        $this->form = new TForm('form_professor');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Vincula disciplinas ao professor'), '', '')->class = 'tformtitle';
        

        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Identificação');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Disciplinas que o Docente ministra');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');        
        $nome = new TEntry('nome');
        
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        $postograd = new TDBCombo('postograd_id','sisacad','postograd','id','nome','ordem',$criteria);
        $orgao_origem = new TDBCombo('orgaosorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
                
        $cpf = new TEntry('cpf');
        $oculto = new TCombo('oculto');
        $sexo = new TCombo('sexo');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $sexo->addItems($fer->lista_sexo());
        
        
        if (!empty($professor))
        {
            $id->setValue($professor->id);
            $nome->setValue($professor->nome);
            $postograd->setValue($professor->postograd_id);
            $cpf->setValue($professor->cpf);
            $orgao_origem->setValue($professor->orgaosorigem_id);
            $sexo->setValue($professor->sexo);
            $oculto->setValue($professor->oculto);
        }

        // sizes
        $id->setSize('50');
        $nome->setSize('400');
        $postograd->setSize('200');
        $cpf->setSize('120');
        $orgao_origem->setSize('400');
        $oculto->setSize('120');
        $sexo->setSize('120');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $nome->setEditable(FALSE);
            $postograd->setEditable(FALSE);
            $cpf->setEditable(FALSE);
            $orgao_origem->setEditable(FALSE);
            $sexo->setEditable(FALSE);
            $oculto->setEditable(FALSE);
        }
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($postograd);
        $this->form->addField($cpf);
        $this->form->addField($orgao_origem);
        $this->form->addField($oculto);
        $this->form->addField($sexo);
        
        // add form fields to the screen
        $table_general->addRowSet( 'ID', $id );
        $table_general->addRowSet( new TLabel('Nome'), $nome );
        $table_general->addRowSet( new TLabel('Posto/Graduação'), $postograd );
        $table_general->addRowSet( new TLabel('CPF'), $cpf );
        $table_general->addRowSet( new TLabel('Órgão Origem'), $orgao_origem );
        $table_general->addRowSet( new TLabel('Oculto?'), $oculto );
        $table_general->addRowSet( new TLabel('Sexo'), $sexo );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('Disciplina que trabalha') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
        
        // retorno
        $ret_button=new TButton('retorno');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna ao Professor');
        $ret_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        //$this->form->addField($new_button);
        $this->form->addField($ret_button);
        
        //$table_master->addRowSet( array($save_button, $new_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        $table_master->addRowSet( array($save_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'professorList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['chamado']))
            {
                $this->chamado = $param['chamado'];
                $chamado = new TMantis();
                $chamado->fechaChamado(array('key'=>$param['chamado']));
            }
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new professor($key);
                $this->form->setData($object);
                if (!empty($object))
                {
                    TSession::setValue('disciplina_rofessor',$object);
                }
                
                //$items  = disciplinaprofessor::where('professor_id', '=', $key)->load();
                $items  = professordisciplina::where('professor_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $this->addDetailRow($item);
                    }
                    
                    // create add button
                    $add = new TButton('clone');
                    $add->setLabel('Adiciona Disciplina');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);
                }
                else
                {
                    $this->onClear($param);
                }
                
                TTransaction::close(); // close transaction
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Add detail row
     */
    public function addDetailRow($item)
    {
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        
        $disciplina_id = new TDBCombo('disciplina_id[]','sisacad','disciplina','id','nome','nome',$criteria);

        // set id's
        $disciplina_id->setId('disciplina_id_'.$uniqid);

        // set sizes
        $disciplina_id->setSize('400');
        
        // set row counter
        $disciplina_id->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->disciplina_id)) { $disciplina_id->setValue( $item->disciplina_id ); }
        
        // create delete button
        //$del = new TImage('fa:trash-o red');
        //$del->onclick = 'ttable_remove_row(this)';
        //$del->setLabel('Apaga');
        
        $del = new TButton('apaga');
        $del->setLabel('Remove Disciplina');
        $del->setImage('fa:trash-o red');
        $del->addFunction('ttable_remove_row(this)');
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($disciplina_id);
        

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($disciplina_id);
        
        $this->detail_row ++;
    }
    
    /**
     * Clear form
     */
    public function onClear($param)
    {
        $this->table_details->addSection('tbody');
        $this->addDetailRow( new stdClass );
        
        // create add button
        $add = new TButton('clone');
        $add->setLabel('Adiciona Disciplina');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // add buttons in table
        $this->table_details->addRowSet([$add]);
    }
    
    /**
     * Save the professor and the disciplinaprofessor's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new professor;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            //disciplinaprofessor::where('professor_id', '=', $master->id)->delete();
            professordisciplina::where('professor_id', '=', $master->id)->delete();
            
            if( !empty($param['disciplina_id']) AND is_array($param['disciplina_id']) )
            {
                foreach( $param['disciplina_id'] as $row => $disciplina_id)
                {
                    if (!empty($disciplina_id))
                    {
                        //$detail = new disciplinaprofessor;
                        $detail = new professordisciplina;
                        $detail->professor_id = $master->id;
                        $detail->disciplina_id = $param['disciplina_id'][$row];
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_professor', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         $data = $this->form->getData();
         TSession::setValue('disciplina_rofessor',null);
         TApplication::loadPage('professorForm','onEdit', array('key'=>$data->id));
         //$this->form->setData($data);
    }//Fim Módulo
}
