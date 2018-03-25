<?php
/**
 * ControleAulaForm Master/Detail
 * @author  <your name here>
 */
class ControleAulaForm extends TPage
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
        $this->form = new TForm('form_controle_aula');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'max-width:700px'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('controle_aula'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('controle_aula');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $materia_id = new TCombo('materia_id');
        $dt_inicio = new TDate('dt_inicio');
        $hora_inicio = new TEntry('hora_inicio');
        $horas_aula = new TEntry('horas_aula');
        $status = new TCombo('status');
        $justificativa = new TEntry('justificativa');
        $conteudo = new TEntry('conteudo');
        
        //Tamanhos
        $id->setSize(80);
        $materia_id->setSize(200);
        $dt_inicio->setSize(120);
        $hora_inicio->setSize(100);
        $horas_aula->setSize(100);
        $status->setSize(200);
        $justificativa->setSize(400);
        $conteudo->setSize(400);
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        // detail fields
        $detail_id = new THidden('detail_id');
        $detail_professor_id = new TCombo('detail_professor_id');
        $detail_aulas_saldo = new TEntry('detail_aulas_saldo');
        $detail_aulas_pagas = new TEntry('detail_aulas_pagas');
        $detail_data_aula = new TDate('detail_data_aula');
        
        //Valores
        $detail_professor_id->addItems($this->getDocentes($materia_id->getValue()));
        
        //Tamanhos
        $detail_professor_id->setSize(200);
        $detail_aulas_saldo->setSize(80);
        $detail_aulas_saldo->setSize(80);
        $detail_data_aula->setSize(120);

        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
        
        // master
        $table_general->addRowSet( new TLabel('Id'), $id );
        $table_general->addRowSet( new TLabel('Disciplina'), $materia_id );
        $table_general->addRowSet( new TLabel('Data da Aula'), $dt_inicio );
        $table_general->addRowSet( new TLabel('Hora de Início'), $hora_inicio );
        $table_general->addRowSet( new TLabel('Horas Aula'), $horas_aula );
        $table_general->addRowSet( new TLabel('Status'), $status );
        $table_general->addRowSet( new TLabel('Justificativa'), $justificativa );
        $table_general->addRowSet( new TLabel('Conteúdo'), $conteudo );
        
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('professorcontrole_aula');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        $table_details->addRowSet( '', $detail_id );
        $table_details->addRowSet( new TLabel('Professor'), $detail_professor_id );
        $table_details->addRowSet( new TLabel('Saldo'), $detail_aulas_saldo );
        $table_details->addRowSet( new TLabel('Aulas Pagas'), $detail_aulas_pagas );
        $table_details->addRowSet( new TLabel('Data da Aula'), $detail_data_aula );
        
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('', 'edit', 'left', 50);
        $this->detail_list->addQuickColumn('', 'delete', 'left', 50);
        
        // items
        $this->detail_list->addQuickColumn('Data da Aula', 'data_aula', 'left', 120);
        $this->detail_list->addQuickColumn('Professor', 'professor_id', 'left', 200);
        $this->detail_list->addQuickColumn('Saldo', 'aulas_saldo', 'left', 80);
        $this->detail_list->addQuickColumn('Aulas Pagas', 'aulas_pagas', 'left', 80);

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
        
        // define form fields
        $this->formFields   = array($id,$materia_id,$dt_inicio,$hora_inicio,$horas_aula,$status,$justificativa,$conteudo,$detail_professor_id,$detail_aulas_saldo,$detail_aulas_pagas,$detail_data_aula);
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $save_button;
        $this->formFields[] = $new_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );
        
        $table_master->addRowSet( array($save_button, $new_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'ControleAulaList'));
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
            $items[ $key ]['aulas_saldo'] = $data->detail_aulas_saldo;
            $items[ $key ]['aulas_pagas'] = $data->detail_aulas_pagas;
            $items[ $key ]['data_aula'] = $data->detail_data_aula;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_professor_id = '';
            $data->detail_aulas_saldo = '';
            $data->detail_aulas_pagas = '';
            $data->detail_data_aula = '';
            
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
        
        $data->detail_id = $item['id'];
        $data->detail_professor_id = $item['professor_id'];
        $data->detail_aulas_saldo = $item['aulas_saldo'];
        $data->detail_aulas_pagas = $item['aulas_pagas'];
        $data->detail_data_aula = $item['data_aula'];
        
        // fill detail fields
        $this->form->setData( $data );
    
        $this->onReload( $param );
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
            $data->detail_aulas_saldo = '';
            $data->detail_aulas_pagas = '';
            $data->detail_data_aula = '';
        
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
        $items = TSession::getValue(__CLASS__.'_items');
        
        $this->detail_list->clear(); // clear detail list
        $data = $this->form->getData();
        
        if ($items)
        {
            $cont = 1;
            foreach ($items as $list_item_key => $list_item)
            {
                $item_name = 'prod_' . $cont++;
                $item = new StdClass;
                
                // create action buttons
                $action_del = new TAction(array($this, 'onDeleteDetail'));
                $action_del->setParameter('item_key', $list_item_key);
                
                $action_edi = new TAction(array($this, 'onEditDetail'));
                $action_edi->setParameter('item_key', $list_item_key);
                
                $button_del = new TButton('delete_detail'.$cont);
                $button_del->class = 'btn btn-default btn-sm';
                $button_del->setAction( $action_del, '' );
                $button_del->setImage('fa:trash-o red fa-lg');
                
                $button_edi = new TButton('edit_detail'.$cont);
                $button_edi->class = 'btn btn-default btn-sm';
                $button_edi->setAction( $action_edi, '' );
                $button_edi->setImage('fa:edit blue fa-lg');
                
                $item->edit   = $button_edi;
                $item->delete = $button_del;
                
                $this->formFields[ $item_name.'_edit' ] = $item->edit;
                $this->formFields[ $item_name.'_delete' ] = $item->delete;
                
                // items
                $item->id           = $list_item['id'];
                try
                {
                    TTransaction::open('sisacad');
                    $professor = new professor ($list_item['professor_id']);
                    if (!empty($professor))
                    {
                        $posto = $professor->get_postograd();
                        $item->professor_id  = (!empty($posto)) ? $posto->nome : '';
                        $item->professor_id .= (!empty($professor->nome)) ? $professor->nome : '-NC-';  
                    }
                    else
                    {
                        $item->professor_id = '-NC-';
                    }
                    TTransaction::close();

                }
                catch (Exception $e)
                {
                    //new TMessage('error', $e->getMessage());
                    TTransaction::rollback();
                    $item->professor_id = '-NC-';
                }
                $item->professor_id = ($professor) ? $professor->get_postograd()->nome. ' ' .$professor->nome : ' - NC - ';//$list_item['professor_id'];//$list_item['professor_id'];
                $item->aulas_saldo  = $list_item['aulas_saldo'];
                $item->aulas_pagas  = $list_item['aulas_pagas'];
                $item->data_aula    = TDate::date2br($list_item['data_aula']);
                
                $row = $this->detail_list->addItem( $item );
                $row->onmouseover='';
                $row->onmouseout='';
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
                
                $object = new controle_aula($key);
                $items  = professorcontrole_aula::where('controle_aula_id', '=', $key)->load();
                
                $session_items = array();
                //var_dump($items);
                foreach( $items as $item )
                {
                    $item_key = $item->id;
                    $session_items[$item_key] = $item->toArray();
                    $session_items[$item_key]['id'] = $item->id;
                    $session_items[$item_key]['professor_id'] = $item->professor_id;
                    $session_items[$item_key]['aulas_saldo'] = $item->aulas_saldo;
                    $session_items[$item_key]['aulas_pagas'] = $item->aulas_pagas;
                    $session_items[$item_key]['data_aula'] = $item->data_aula;
                }
                TSession::setValue(__CLASS__.'_items', $session_items);
                
                $this->form->setData($object); // fill the form with the active record data
                $lista_mestres = $this->getDocentes($object->materia_id);
                TCombo::reload('form_controle_aula','detail_professor_id',$lista_mestres);
                $turma = $object->get_materia();
                $disciplinas = $this->getDisciplinas($turma->turma_id);
                TCombo::reload('form_controle_aula','materia_id',$disciplinas);
                
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
    }
    
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
            $master = new controle_aula;
            $master->fromArray( (array) $data);
            $master->conteudo = mb_strtoupper($master->conteudo,'UTF-8');

            $this->form->validate(); // form validation
            
            $master->store(); // save master object
            // delete details
            $old_items = professorcontrole_aula::where('controle_aula_id', '=', $master->id)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new professorcontrole_aula;
                    }
                    else
                    {
                        $detail = professorcontrole_aula::find($item['id']);
                    }
                    $detail->professor_id  = $item['professor_id'];
                    $detail->aulas_saldo  = $item['aulas_saldo'];
                    $detail->aulas_pagas  = $item['aulas_pagas'];
                    $detail->data_aula  = $item['data_aula'];
                    $detail->controle_aula_id = $master->id;
                    $detail->store();
                    
                    $keep_items[] = $detail->id;
                }
            }
            
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
    }
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getDocentes ($key = null)
    {
        $lista = array(0=>' --- Sem Professores Vinculados ---');
        try
        {
            TTransaction::open('sisacad');
            $docentes = professormateria::where('materia_id','=',$key)->load();
            //var_dump($key);
            if ($docentes)
            {
                $lista = array();
                foreach ($docentes as $docente)
                {
                    $mestre = new professor($docente->professor_id);
                    $posto = $mestre->get_postograd();
                    $grad = ($posto) ? $posto->nome : '';
                    $lista[$mestre->id] = $grad . ' ' . $mestre->nome;
                }
            }
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getDisciplinas($key = null)
    {
        $lista = array(0=>' --- Sem Disciplinas Vinculadas ---');
        try
        {
            TTransaction::open('sisacad');
            $materias = materia::where('turma_id','=',$key)->load();
            //var_dump($materias);
            if ($materias)
            {
                $lista = array();
                foreach ($materias as $materia)
                {
                    $disciplina = $materia->get_disciplina();
                    $lista[$materia->id] = $disciplina->nome;
                }
            }
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        //var_dump($lista);
        return $lista;

    }//Fim Módulo
    
}
