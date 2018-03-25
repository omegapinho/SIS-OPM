<?php
/**
 * materiaForm Master/Detail
 * @author  <your name here>
 */
class professorMateriaForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    protected $ato;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Professor';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado = false;          //Controle de correção de chamado
   
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
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Designa Professores para Disciplina da Turma - Edição'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Turma e Disciplina');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Professores');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $carga_horaria = new THidden('carga_horaria');
        
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('oculto','!=','S'));
        
        $turma_id = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        $disciplina_id = new TDBCombo('disciplina_id','sisacad','disciplina','id','nome','nome',$criteria);

        // sizes
        $id->setSize('80');
        $carga_horaria->setSize('100');
        $turma_id->setSize('400');
        $disciplina_id->setSize('400');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $turma_id->setEditable(FALSE);
            $disciplina_id->setEditable(FALSE);
        }
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($carga_horaria);
        $this->form->addField($turma_id);
        $this->form->addField($disciplina_id);
        
        // add form fields to the screen
        //$table_general->addRowSet( new TLabel('Id'), $id );
        //$table_general->addRowSet( new TLabel('Carga Horária'), $carga_horaria );
        $table_general->addRowSet( '', $id );
        $table_general->addRowSet( '', $carga_horaria );
        $table_general->addRowSet( new TLabel('Turma'), $turma_id );
        $table_general->addRowSet( new TLabel('Disciplina'), $disciplina_id );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('Professor') );
        $row->addCell( new TLabel('Ato de Autorização') );
        $row->addCell( new TLabel('Vínculo') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        $new_button->disableField('form_materia','new');
        
        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array($this, 'onReturn')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($new_button);
        $this->form->addField($return_button);
        
        if (!$this->nivel_sistema >80) //Controle de acesso
        {
            $table_master->addRowSet( array($save_button, $new_button, $return_button), '', '')->class = 'tformaction'; // CSS class
        }
        else
        {
            $table_master->addRowSet( array($save_button, $return_button), '', '')->class = 'tformaction'; // CSS class
        }
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
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
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new materia($key);
                $this->form->setData($object);
                $curso = new curso($object->turma->curso_id);
                //var_dump($curso);
                $this->ato = $curso->ato_autorizacao;
                
                $items  = professormateria::where('materia_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $this->addDetailRow($item , $object->disciplina_id);
                    }
                    
                    // create add button
                    $add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);
                }
                else
                {
                    $this->onClear($param, $object->disciplina_id);
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
    public function addDetailRow($item,$key = null)
    {
        $fer = new TFerramentas();
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $detail_id = new TEntry('detail_id[]');
        $professor_id = new TCombo('professor_id[]');
        $ato_autorizacao = new TText('ato_autorizacao[]');
        $vinculo = new TCombo('vinculo[]');

        // set id's
        $detail_id->setId('detail_id_'.$uniqid);
        $professor_id->setId('professor_id_'.$uniqid);
        $ato_autorizacao->setId('ato_autorizacao_'.$uniqid);
        $vinculo->setId('vinculo_'.$uniqid);

        // set sizes
        $detail_id->setSize('50');
        $professor_id->setSize('250');
        $ato_autorizacao->setSize('220','36');
        $vinculo->setSize('120');
        
        
        //Valores
        if ($key != null)
        {
            $lista = $this->listaProfessores(array('key'=>$key));
            $professor_id->addItems($lista);
            $vinculo->addItems($fer->lista_vinculo_professor());
            $vinculo->setValue('R');
            if ($this->ato == null)
            {
                $this->ato = getAtoCurso();
            }
            $ato_autorizacao->setValue($this->ato);
        }
        
        // set row counter
        $detail_id->{'data-row'} = $this->detail_row;
        $professor_id->{'data-row'} = $this->detail_row;
        $ato_autorizacao->{'data-row'} = $this->detail_row;
        $vinculo->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->id)) { $detail_id->setValue( $item->id ); }
        if (!empty($item->professor_id)) { $professor_id->setValue( $item->professor_id ); }
        if (!empty($item->ato_autorizacao)) { $ato_autorizacao->setValue( $item->ato_autorizacao ); }
        if (!empty($item->vinculo)) { $vinculo->setValue( $item->vinculo ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        //$del->setTipo('Apaga este Professor');
        
        //Bloqueio
        $detail_id->setEditable(false);
        
        $row = $this->table_details->addRow();
        // add cells
        if ($this->nivel_sistema >80)
        {
            $row->addCell( $del );
        }
        else
        {
            $row->addCell( '' );
        }
        
        $row->addCell($detail_id);
        $row->addCell($professor_id);
        $row->addCell($ato_autorizacao);
        $row->addCell($vinculo);
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($detail_id);
        $this->form->addField($professor_id);
        $this->form->addField($ato_autorizacao);
        $this->form->addField($vinculo);
        
        $this->detail_row ++;
    }
    
    /**
     * Clear form
     */
    public function onClear($param , $key = null)
    {
        $this->table_details->addSection('tbody');
        $this->addDetailRow( new stdClass , $key );
        
        // create add button
        $add = new TButton('clone');
        $add->setLabel('Add');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // add buttons in table
        $this->table_details->addRowSet([$add]);
    }
    
    /**
     * Save the materia and the professormateria's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new materia;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            //professormateria::where('materia_id', '=', $master->id)->delete();
            $lista = array();
            if( !empty($param['professor_id']) AND is_array($param['professor_id']) )
            {
                foreach( $param['professor_id'] as $row => $professor_id)
                {
                    if (!empty($professor_id))
                    {
                        $detail = new professormateria;
                        $detail->id              = $param['detail_id'][$row];
                        $detail->materia_id      = $master->id;
                        $detail->professor_id    = $param['professor_id'][$row];
                        $detail->ato_autorizacao = $param['ato_autorizacao'][$row];
                        $detail->vinculo         = $param['vinculo'][$row];
                        $detail->store();
                        $lista[] = $detai->id;
                    }
                }
            }
            var_dump($lista);
            if (count($lista)> 0 && !empty($lista))//Apaga somente quem não veio na lista
            {
               $objects = professormateria::where('id', 'NOT IN', $lista)->
                                            where('materia_id', '=', $master->id)->load();
               //var_dump($objects);
               if (!empty($objects))
               {
                   $apagadas  = 0;
                   $ignoradas = 0;
                   foreach($objects as $object)
                   {
                       if (!empty($object))
                       {
                           //echo 'ID da Matéria ' . $object->materia_id;
                           //Verifica se há aula lançada
                           $cadastro = controle_aula::where('materia_id','=',$object->materia_id)->load();
                           //var_dump($cadastro);
                           if (empty($cadastro))//Se não tem aula, posso apagar
                           {
                               professormateria::where('materia_id', '=', $object->materia_id)->
                                                 where('professor_id', '=', $master->id)->delete();
                               $apagadas ++;
                           }
                           else//Ignora se houver aulas lançadas
                           {
                               $ignoradas ++;
                           }
                       }
                   }
               }
            }


            //$data = new stdClass;
            //$data->id = $master->id;
            //TForm::sendData('form_materia', $data);
            TTransaction::close(); // close the transaction
            $action = new TAction(array('professorMateriaForm','onEdit'));
            $action->setParameters(array('key'=>$master->id));
            $msg = "Registro Salvo com sucesso";
            if ($apagadas == 0 && $ignoradas == 0)
            {
                $msg .= '.';
            }
            if ($apagadas != 0)
            {
                $msg .= '.<br>' . $apagadas . ' professor(es) foi(ram) desvinculados.';
            }
            if ($ignoradas != 0)
            {
                $msg .= '.<br>' . $ignoradas . ' vínculo(s) não foi(foram) desfeito(s) por já existir registro de aulas.';
            }
            new TMessage('info', $msg,$action);
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega Professores para Seleção
 *---------------------------------------------------------------------------------------*/
    public function listaProfessores($param)
    {
        //return;
        if (array_key_exists('key',$param))
        {
            $key = $param['key'];
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
                
                $disciplina = new disciplina($key);
                $professores = $disciplina->getprofessors();
                if (!empty($professores))
                {
                    $lista = array();
                    foreach ($professores as $professor)
                    {
                        //var_dump($professor);
                        $cargo = (!empty($professor->postograd->sigla)) ? $professor->postograd->sigla : '';
                        $orgao = (!empty($professor->orgaosorigem->sigla)) ? $professor->orgaosorigem->sigla : '';
                        $lista[$professor->id] = $cargo.' '.$professor->nome . ' / '.$orgao;
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
    return $lista;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Retorno a Listagem
 *---------------------------------------------------------------------------------------*/
    public function onReturn ($param = null)
    {
        TApplication::loadPage('professorMateriaList');
    }//Fim Módulo
}
