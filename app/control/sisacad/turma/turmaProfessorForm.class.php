<?php
/**
 * materiaForm Master/Detail
 * @author  <your name here>
 */
class turmaProfessorForm extends TPage
{
    protected $form; // form
    protected $formFields;
    protected $detail_list;
    
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
    private $cfg_vincula     = 'nivel_vincula_professor';
    private $lista_materias  = array ('0'=>'-- Sem Matérias vinculadas --');
   
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
        $this->form = new TForm('form_materia');
        $this->form->class = 'tform'; // CSS class
        //$this->form->style = 'width:700px'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Plantel de Professores da Turma - Edição'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Dados da Turma');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);
        
        $turma = TSession::getValue('turma_militar');
        
        // master fields
        $id         = new TEntry('id');
        $curso_id   = new TDBCombo('curso_id','sisacad','curso','id','nome','nome');
        $nome       = new TEntry('nome');
        $tipo_turma = new TCombo('tipo_turma');
        
        // sizes
        $id->setSize('80');
        $curso_id->setSize('300');
        $nome->setSize('400');
        $tipo_turma->setSize('160');
        
        //Dados
        $tipo_turma->addItems($fer->lista_tipos_curso());
        
        //Bloqueios
        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $curso_id->setEditable(FALSE);
            $nome->setEditable(FALSE);
            $tipo_turma->setEditable(FALSE);
        }
        
        // detail fields
        $detail_id = new TEntry('detail_id');
        $detail_professor_id = new TCombo('detail_professor_id');
        $detail_materia_id = new TCombo('detail_materia_id');
        $detail_vinculo = new TCombo('detail_vinculo');
        $detail_ato_autorizacao = new TEntry('detail_ato_autorizacao');
        
        //Valores
        $detail_vinculo->addItems($fer->lista_vinculo_professor());
        $detail_materia_id->addItems($this->get_materias(array('key'=> $turma->id ) ) );
        
        //Tamanhos
        $detail_id->setSize(50);
        $detail_professor_id->setSize(450);
        $detail_materia_id->setSize(450);
        $detail_ato_autorizacao->setSize(200);
        $detail_vinculo->setSize(180);

        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
        
        // master
        $table_general->addRowSet( array(new TLabel('ID'), $id, new TLabel('Curso'), $curso_id) );
        $table_general->addRowSet( array(new TLabel('Turma'), $nome) );
        $table_general->addRowSet( array(new TLabel('Tipo de Turma'), $tipo_turma ));
        
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Professor Designado por Matéria');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $table_detail -> width = '100%';
        $frame_details->add($table_details);
        
        //Ações
        $change_action_professores = new TAction(array($this, 'onChange_listaProfessores'));//Popula as cidades com a troca da UF
        $detail_materia_id->setChangeAction($change_action_professores);
        
        $table_details->addRowSet( new TLabel('ID'), $detail_id );
        $table_details->addRowSet( new TLabel('Materia'), $detail_materia_id );
        $table_details->addRowSet( new TLabel('Professor'), $detail_professor_id );
        $table_details->addRowSet( new TLabel('Vinculo'), $detail_vinculo );
        $table_details->addRowSet( new TLabel('Ato Autorização'), $detail_ato_autorizacao );
        
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('------------', 'edit', 'center', 50);
        $this->detail_list->addQuickColumn('------------', 'delete', 'center', 50);
        
        // items
        $this->detail_list->addQuickColumn('ID', 'id', 'center', 50);
        $this->detail_list->addQuickColumn('Professor', 'professor_id', 'center', 250);
        $this->detail_list->addQuickColumn('Matéria', 'materia_id', 'center', 300);
        $this->detail_list->addQuickColumn('Vinculo', 'vinculo', 'center', 120);
        $this->detail_list->addQuickColumn('Ato Autorização', 'ato_autorizacao', 'center', 150);
        $this->detail_list->createModel();
        
        $row = $table_detail->addRow();
        $row->addCell($this->detail_list);
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        
        // create an new button (edit with no parameters)
        $ret_button=new TButton('returm');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna à Turma');
        $ret_button->setImage('ico_back.png');
        
        // define form fields
        $this->formFields   = array($id,$curso_id,$nome,$tipo_turma,$detail_professor_id,$detail_materia_id,
                                    $detail_vinculo,$detail_ato_autorizacao);
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $save_button;
        $this->formFields[] = $ret_button;
        //$this->formFields[] = $new_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );
        
        $table_master->addRowSet( array($save_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    
    /**
     * Clear form
     * @param $param URL parameters
     */
    public function onClear($param)
    {
        $this->form->clear(TRUE);
        TSession::setValue(__CLASS__.'_items', array());
        $this->onReload( $param );
    }
    
    /**
     * Save an item from form to session list
     * @param $param URL parameters
     */
    public function onSaveDetail( $param )
    {
        try
        {
            TTransaction::open('sisacad');
            $data = $this->form->getData();
            
            /** validation sample
            if (! $data->fieldX)
                throw new Exception('The field fieldX is required');
            **/
            
            $items = TSession::getValue(__CLASS__.'_items');
            $key = empty($data->detail_id) ? 'X'.mt_rand(1000000000, 1999999999) : $data->detail_id;
            
            $items[ $key ] = array();
            $items[ $key ]['id'] = $key;
            $items[ $key ]['professor_id'] = $data->detail_professor_id;
            $items[ $key ]['materia_id'] = $data->detail_materia_id;
            $items[ $key ]['vinculo'] = $data->detail_vinculo;
            $items[ $key ]['ato_autorizacao'] = $data->detail_ato_autorizacao;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_professor_id = '';
            $data->detail_materia_id = '';
            $data->detail_vinculo = '';
            $data->detail_ato_autorizacao = '';
            
            TTransaction::close();
            $this->form->setData($data);
            
            $this->onReload( $param ); // reload the items
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Load an item from session list to detail form
     * @param $param URL parameters
     */
    public function onEditDetail( $param )
    {
        $data = $this->form->getData();
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // get the session item
        $item = $items[ $param['item_key'] ];

        //Atualiza lista de professores        
        self::onChange_listaProfessores(array('detail_materia_id'=>$item['materia_id']));
        
        //Preenche os dados do professor para editar        
        $data->detail_id = $item['id'];
        $data->detail_professor_id = $item['professor_id'];
        $data->detail_materia_id = $item['materia_id'];
        $data->detail_vinculo = $item['vinculo'];
        $data->detail_ato_autorizacao = $item['ato_autorizacao'];
        
        // fill detail fields
        $this->form->setData( $data );
    
        $this->onReload( $param );
        TEntry::disableField('form_materia', 'detail_id');
        if ($this->is_worked($item['professor_id'],$item['materia_id']))
        {
            TCombo::disableField('form_materia', 'detail_professor_id');
            TCombo::disableField('form_materia', 'detail_materia_id');
            TCombo::disableField('form_materia', 'detail_vinculo');
            
        }
    }
    
    /**
     * Delete an item from session list
     * @param $param URL parameters
     */
    public function onDeleteDetail( $param )
    {
        $data = $this->form->getData();
        
        // reset items
            $data->detail_professor_id = '';
            $data->detail_materia_id = '';
            $data->detail_vinculo = '';
            $data->detail_ato_autorizacao = '';
        
        // clear form data
        $this->form->setData( $data );
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // delete the item from session
        unset($items[ $param['item_key'] ] );
        TSession::setValue(__CLASS__.'_items', $items);
        
        // reload items
        $this->onReload( $param );
    }
    
    /**
     * Load the items list from session
     * @param $param URL parameters
     */
    public function onReload($param)
    {
        // read session items
        $fer = new TFerramentas();
        $items = TSession::getValue(__CLASS__.'_items');
        
        $this->detail_list->clear(); // clear detail list
        $data = $this->form->getData();
        
        if ($items)
        {
            $cont = 1;
            foreach ($items as $list_item_key => $list_item)
            {
                try
                {
                    TTransaction::open('sisacad');
                    
                    $item_name = 'prod_' . $cont++;
                    $item = new StdClass;
                    
                    // create action buttons
                    $action_del = new TAction(array($this, 'onDeleteDetail'));
                    $action_del->setParameter('item_key', $list_item_key);
                    
                    $action_edi = new TAction(array($this, 'onEditDetail'));
                    $action_edi->setParameter('item_key', $list_item_key);
                    
                    $sql = "(SELECT id FROM sisacad.controle_aula WHERE materia_id = ". $list_item['materia_id'] . ")";
                    $servicos = professorcontrole_aula::where('professor_id','=',$list_item['professor_id'])->
                                                  where('controle_aula_id','IN',$sql)->load();
                                                  
                    //var_dump($servicos);
                    $button_del = new TButton('delete_detail'.$cont);
                    $button_del->class = 'btn btn-default btn-sm';
                    $button_del->setAction( $action_del, '' );
                    $button_del->setImage('fa:trash-o red fa-lg');

                    $button_edi = new TButton('edit_detail'.$cont);
                    $button_edi->class = 'btn btn-default btn-sm';
                    $button_edi->setAction( $action_edi, '' );
                    $button_edi->setImage('fa:edit blue fa-lg');
                    if ($this->is_worked($list_item['professor_id'],$list_item['materia_id']))
                    {
                        if ($this->nivel_sistema <=80)
                        {
                            $button_edi->disableField('form_materia','edit_detail'.$cont);
                            $button_edi->setImage('fa:edit gray fa-lg');
                        }
                        $button_del->disableField('form_materia','delete_detail'.$cont);
                        $button_del->setImage('fa:trash-o gray fa-lg');
                    }
                    
                    $item->edit   = $button_edi;
                    $item->delete = $button_del;
                    
                    $this->formFields[ $item_name.'_edit' ] = $item->edit;
                    $this->formFields[ $item_name.'_delete' ] = $item->delete;

                    // items
                    $item->id = $list_item['id'];
                    
                    $professor = new professor ($list_item['professor_id']);
                    $posto = (!empty($professor->postograd->sigla)) ? $professor->postograd->sigla . ' '  : '' ;
                    $orgao = (!empty($professor->orgaosorigem->sigla)) ? '(' . $professor->orgaosorigem->sigla . ')' : '' ;
                    
                    
                    $item->professor_id = $posto . $professor->nome . $orgao ;
                    
                    $materia = new materia($list_item['materia_id']);
                    
                    $item->materia_id = $materia->disciplina->nome;
                    $item->vinculo = $fer->lista_vinculo_professor($list_item['vinculo']);
                    $item->ato_autorizacao = $list_item['ato_autorizacao'];
                    
                    $row = $this->detail_list->addItem( $item );
                    $row->onmouseover='';
                    $row->onmouseout='';
                    TTransaction::close(); // close transaction
                }
                catch (Exception $e) // in case of exception
                {
                    new TMessage('error', $e->getMessage());
                    TTransaction::rollback();
                }
            }

            $this->form->setFields( $this->formFields );
        }
        
        $this->loaded = TRUE;
    }
    
    /**
     * Load Master/Detail data from database to form/session
     */
    public function onEdit($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new turma($key);
                //$object->curso_id = $object->curso->nome;
                $this->form->setData($object);
                
                $this->ato = $object->curso->ato_autorizacao;
                $sql = '(SELECT id FROM sisacad.materia WHERE turma_id = ' . $key .')';
                $items  = professormateria::where('materia_id', 'IN', $sql)->load();
                
                $session_items = array();
                foreach( $items as $item )
                {
                    $item_key = $item->id;
                    $session_items[$item_key] = $item->toArray();
                    $session_items[$item_key]['id'] = $item->id;
                    $session_items[$item_key]['professor_id'] = $item->professor_id;
                    $session_items[$item_key]['vinculo'] = $item->vinculo;
                    $session_items[$item_key]['ato_autorizacao'] = $item->ato_autorizacao;
                }
                TSession::setValue(__CLASS__.'_items', $session_items);
               
                $this->form->setData($object); // fill the form with the active record data
                $this->onReload( $param ); // reload items list
                
                
                
                TTransaction::close(); // close transaction
            }
            else
            {
                $this->form->clear(TRUE);
                TSession::setValue(__CLASS__.'_items', null);
                $this->onReload( $param );
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
    
    /**
     * Save the Master/Detail data from form/session to database
     */
    public function onSave()
    {
        try
        {
            // open a transaction with database
            TTransaction::open('sisacad');
            
            $data = $this->form->getData();
            $master = new turma($data->id);

            $sql = '(SELECT id FROM sisacad.materia WHERE turma_id = ' . $master->id .')';
            $old_items  = professormateria::where('materia_id', 'IN', $sql)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new professormateria;
                    }
                    else
                    {
                        $detail = new professormateria ($item['id']);
                        if (empty($detail))
                        {
                            $detail = new professormateria;
                        }
                    }
                    
                    $detail->professor_id  = $item['professor_id'];
                    $detail->materia_id  = $item['materia_id'];
                    
                    $detail->vinculo  = $item['vinculo'];
                    $detail->ato_autorizacao  = $item['ato_autorizacao'];
                    //$detail->materia_id = $master->id;
                    //var_dump($detail);
                    $detail->store();
                    
                    $keep_items[] = $detail->id;//Armazena o id dos itens existentes
                }
            }
            //Rotina de exclusão, elimina os itens que não permaneceram
            if ($old_items)
            {
                foreach ($old_items as $old_item)
                {
                    if (!in_array( $old_item->id, $keep_items))
                    {
                        $old_item->delete();
                    }
                }
            }
            TTransaction::close(); // close the transaction
            
            // reload form and session items
            $this->onEdit(array('key'=>$master->id));
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback();
        }
    }
    
    /**
     * Show the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega Professores para Seleção
 *---------------------------------------------------------------------------------------*/
    public static function onChange_listaProfessores($param)
    {
        //return;
        if (array_key_exists('detail_materia_id',$param))
        {
            $key = $param['detail_materia_id'];
            if ($key=='')
            {
                return;
            }
        }
        else
        {
            return;
        }
        $lista = array('0'=>'- Nenhum Professor Vinculado -');
        try
        {

            if ($key != "XX")
            {
                TTransaction::open('sisacad'); // open a transaction
                
                $materia = new materia($key);
                $sql = '(SELECT professor_id FROM sisacad.professordisciplina WHERE disciplina_id = ' . $materia->disciplina->id .')';
                $professores = professor::where ('id','IN',$sql)->orderBy('nome')->load();
                if (!empty($professores))
                {
                    $lista = array();
                    foreach ($professores as $professor)
                    {
                        //var_dump($professor);
                        $cargo = (!empty($professor->postograd->sigla)) ? $professor->postograd->sigla . ' ' : '';
                        $orgao = (!empty($professor->orgaosorigem->sigla)) ? '(' . $professor->orgaosorigem->sigla . ')' : '';
                        $lista[$professor->id] = $cargo. $professor->nome .$orgao;
                    }
                }
                TTransaction::close(); // close the transaction
            }
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
    TCombo::reload('form_materia', 'detail_professor_id', $lista);
    return $lista;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega a lista de disciplinas previstas para a turma
 *---------------------------------------------------------------------------------------*/
    public function get_materias ($param)
    {
        $lista = array ('0'=>'-- Sem Matérias vinculadas --');
            try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new turma($key);
                $materias = $object->getmaterias();
                if (!empty($materias))
                {
                    $lista = array();
                    foreach ($materias as $materia)
                    {
                        $lista[$materia->id] = $materia->disciplina->nome; 
                    }
                }
                $this->lista_materias = $lista;
              
                TTransaction::close(); // close transaction
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Retorna para o TurmaForm
 *---------------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
        $turma = TSession::getValue('turma_militar');
        TSession::setValue('turma_militar',null);
        TApplication::loadPage('turmaForm','onEdit',array('key'=>$turma->id));
        
    }//Fim Módulo

    
    public function onEditAto($param)
    {
        try
        {
            // get the parameter $key
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            // open a transaction with database 'samples'
            TTransaction::open('sisacad');
            
            // instantiates object Customer
            $customer = new professormateria($key);
            $customer->{$field} = $value;
            $customer->store();
            
            // close the transaction
            TTransaction::close();
            
            // reload the listing
            $this->onReload($param);
            // shows the success message
            new TMessage('info', "Ato de designação atualizado");
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    public function is_worked ($professor,$materia)
    {
        $verifica = false;
        try
        {
            TTransaction::open('sisacad');
            $sql = "(SELECT id FROM sisacad.controle_aula WHERE materia_id = ". $materia . ")";
            $servicos = professorcontrole_aula::where('professor_id','=',$professor)->
                                          where('controle_aula_id','IN',$sql)->load();
            TTransaction::close();
            if (count($servicos) >0)
            {
                $verifica = true;
            }
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
        return $verifica;
    }
}//Fim Classe
