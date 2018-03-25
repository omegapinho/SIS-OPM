<?php
/**
 * editaEmentaturmaForm Master/Detail
 * @author  <your name here>
 */
class editaEmentaTurmaForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
    protected $chamado = false;                                                 //Se foi aberto por chamado
    
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
        /*$turma = TSession::getValue('turma_militar');
        if (empty($turma) && $this->chamado == false)
        {        
            TSession::setValue('turma_militar',null);
            TSession::setValue('curso_militar',null);
            TApplication::loadPage('cursoList');
        }*/
        // creates the form
        $this->form = new TForm('form_turma');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Gerenciamento de Disciplinas de Turma'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados básicos da Turma');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Disciplinas extraídas da Ementa');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $sigla = new TEntry('sigla');
        $curso_id = new TDBCombo('curso_id','sisacad','curso','id','nome','nome');
        $opm_id = new TDBCombo('opm_id','sicad','OPM','id','nome','nome');

        // sizes
        $id->setSize('100');
        $nome->setSize('400');
        $sigla->setSize('200');
        $curso_id->setSize('400');
        $opm_id->setSize('400');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $nome->setEditable(FALSE);
        $sigla->setEditable(FALSE);
        $curso_id->setEditable(FALSE);
        $opm_id->setEditable(FALSE);
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($sigla);
        $this->form->addField($curso_id);
        $this->form->addField($opm_id);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('Id'), $id );
        $table_general->addRowSet( new TLabel('Nome'), $nome );
        $table_general->addRowSet( new TLabel('Sigla'), $sigla );
        $table_general->addRowSet( new TLabel('Curso'), $curso_id );
        $table_general->addRowSet( new TLabel('OPM Vinculada'), $opm_id );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        
        $scroll = new TScroll;
        $scroll->setSize('100%',180);
        $scroll->add($this->table_details);
        
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('Disciplina') );
        $row->addCell( new TLabel('Carga Horária') );

        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
        
        // create an return button (edit with no parameters)
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), 'Retorna para Turma');
        $ret_button->setImage('ico_back.png');
        
        // create an return button (edit with no parameters)
        $not_button=new TButton('notifica');
        $not_button->setAction(new TAction(array($this, 'onNotifica')), 'Notifica');
        $not_button->setImage('fa:bell red');        
        
        // define form fields
        $this->form->addField($save_button);
        //$this->form->addField($new_button);
        $this->form->addField($ret_button);
        
        if ($this->nivel_sistema<=80)
        {
            $this->form->addField($not_button);
            $table_master->addRowSet( array($not_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        }
        else
        {
            $table_master->addRowSet( array($save_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
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
            if (isset($param['chamado']))
            {
                $this->chamado = $param['chamado'];
                TSession::setValue('turma_militar',null);
                $chamado = new TMantis();
                $chamado->fechaChamado(array('key'=>$param['chamado']));
            }
            else
            {
                $turma = TSession::getValue('turma_militar');
                $param['key'] = $turma->id;
            }
  
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new turma($key);
                $this->form->setData($object);
                
                $items  = materia::where('turma_id', '=', $key)->orderBy('id')->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $this->addDetailRow($item);
                    }
                    if ($this->nivel_sistema>80)
                    {                    
                        // create add button
                        $add = new TButton('clone');
                        $add->setLabel('Add');
                        $add->setImage('fa:plus-circle green');
                        $add->addFunction('ttable_clone_previous_row(this)');
                        
                        // add buttons in table
                        $this->table_details->addRowSet([$add]);
                    }
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
        $turma = TSession::getValue('turma_militar');
        if (!empty($turma)) 
        {
            $curso = $turma->curso_id;
        }
        else
        {
            $curso = null;
        }
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $id_detail     = new THidden('id_detail[]');
        $carga_horaria = new TEntry('carga_horaria[]');
        
        if ($this->nivel_sistema <= 80)
        {
            if (!empty($curso))
            {
                $criteria = new TCriteria;
                $sql = '(SELECT disciplina_id FROM sisacad.materias_previstas WHERE curso_id = ' . $curso . ')';
                $criteria->add(new TFilter ('id','IN',$sql));
            }
            else
            {
                $criteria = null;
            }
        }
        else
        {
            $criteria = null;
        }

        $disciplina_id = new TDBCombo('disciplina_id[]','sisacad','disciplina','id','nome','nome',$criteria);


        // set id's
        $id_detail->setId('id_detail_'.$uniqid);
        $carga_horaria->setId('carga_horaria_'.$uniqid);
        $disciplina_id->setId('disciplina_id_'.$uniqid);
        

        // set sizes
        $carga_horaria->setSize('50');
        $disciplina_id->setSize('300');
        
        //
        if ($this->nivel_sistema<=80)
        {
            $carga_horaria->setEditable(false);
            $disciplina_id->setEditable(false);
        }
        
        // set row counter
        $carga_horaria->{'data-row'} = $this->detail_row;
        $disciplina_id->{'data-row'} = $this->detail_row;
        $id_detail->{'data-row'}     = $this->detail_row;

        // set value
        if (!empty($item->id)) { $id_detail->setValue( $item->id ); }
        if (!empty($item->carga_horaria)) { $carga_horaria->setValue( $item->carga_horaria ); }
        if (!empty($item->disciplina_id)) { $disciplina_id->setValue( $item->disciplina_id ); }
        
        // create delete button

        if ($this->nivel_sistema>80)
        {
            $del = new TImage('fa:trash-o red');
            $del->onclick = 'ttable_remove_row(this)';
        }
        else
        {
            $del = '';
        }
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($disciplina_id);
        $row->addCell($carga_horaria);
        $row->addCell($id_detail);

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($disciplina_id);
        $this->form->addField($carga_horaria);
        $this->form->addField($id_detail);

        
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
        $add->setLabel('Add');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // add buttons in table
        $this->table_details->addRowSet([$add]);
    }
    
    /**
     * Save the turma and the materia's
     */
    public static function onSave($param)
    {
        // define the delete action
        $action = new TAction(array('editaEmentaTurmaForm', 'Save'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion('Confirma as alterações? Caso tenha removido alguma disciplina, o ato de salvar apagará o vínculo com professores bem como as aulas dadas', $action);
    }
     
    public static function Save($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            //Log de Alteração
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário
            $date = new DateTime();
            $data = (string) $date->getTimestamp();
            TTransaction::setLogger(new TLoggerTXT('tmp/editaEmentaTurma_log_' . $data . '.txt'));
            TTransaction::log('-- Mundanças nas disciplinas da turma id = ' . $param['id'] . '--');
            TTransaction::log('-- Responsável ->' . json_encode($profile));
            
            $id = (int) $param['id'];
            $master = new turma;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            //Reserva as máterias já cadastradas e faz uma lista delas
            $materias = materia::where('turma_id', '=', $master->id)->load();
            $lista_old = array();
            if (!empty($materias))
            {
                foreach ($materias as $materia)
                {
                    $lista_old[$materia->id] = $materia->id;
                }
            }
            //Verifica se há itens nos detalhes
            if( !empty($param['carga_horaria']) AND is_array($param['carga_horaria']) )
            {
                $lista = array();//Guarda os itens que foram cadastrados e que já existiam
                foreach( $param['carga_horaria'] as $row => $carga_horaria)
                {
                    if (!empty($carga_horaria))
                    {
                        if (!empty($param['id_detail'][$row]))
                        {
                            $detail = new materia($param['id_detail'][$row]);
                        } 
                        else
                        {
                            $detail = new materia;
                        }
                        $detail->turma_id = $master->id;
                        $detail->carga_horaria = $param['carga_horaria'][$row];
                        $detail->disciplina_id = $param['disciplina_id'][$row];
                        $detail->store();
                        $lista[] = $detail->id;//Guarda o id na lista para evitar exclusão//$param['id_detail'][$row];
                        if (array_key_exists($param['id_detail'][$row],$lista_old))
                        {
                            unset($lista_old[$param['id_detail'][$row]]);//Remove o item se ele estava na lista velha
                        }
                    }
                }
                if (count($lista) > 0)//Há alguns itens alterados/cadastrados
                {
                    $tipo_delete = 'ALGUNS'; //professormateria::where('materia_id','IN',$lista_old)->delete();
                }
                else//Não houve cadastro
                {
                    $tipo_delete = 'NENHUM';
                }
            }
            else//Não houve cadastro
            {
                $tipo_delete = 'NENHUM';
            }
            //Prepara as exclusões dos diversos itens em cascata
            $lista_aulas       = false;
            $lista_professores = false;
            if (count($lista_old)>0)
            {
                $aulas = controle_aula::where('materia_id','IN',$lista_old)->load();
                if (!empty($aulas))
                {
                    $lista_aulas = array();
                    foreach ($aulas as $aula)
                    {
                        $lista_aulas[] = $aula->id;
                        TTransaction::log('**** Deletando Aulas ****');
                        TTransaction::log($aula->toJson());
                    }
                }
                $professores = professormateria::where('materia_id','IN',$lista_old)->load();
                if (!empty($professores))
                {
                    foreach($professores as $professor)
                    {
                        $lista_professores[] = $professor->professor_id;
                        TTransaction::log('**** Deletado professor ****');
                        TTransaction::log($professor->toJson());
                    }
                }
            }
            if ($lista_aulas != false)
            {
                professorcontrole_aula::where('controle_aula_id','IN',$lista_aulas)->delete();
            }
            if ($lista_professores != false)
            {
                professormateria::where('professor_id','IN',$lista_professores)->delete();
            }
            if (count($lista_old)>0)
            {
                controle_aula::where('materia_id','IN',$lista_old)->delete();
            }
            if ($tipo_delete == 'ALGUNS')
            {
                materia::where('turma_id', '=', $master->id)->where('id','NOT IN',$lista)->delete();
            }
            else
            {
                materia::where('turma_id', '=', $master->id)->delete();
            }
            

            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_turma', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Modulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
     public function onReturn ($param)
     {
         //var_dump($param);
         TApplication::loadPage('turmaForm','onEdit', array('key'=>$param['id']));
     }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Lança Aula
 *---------------------------------------------------------------------------------------*/
    public function onNotifica ($param = null)
    {
        $id      = new THidden('id');
        $texto   = new TText('texto');
        //var_dump($param);
        //Tamanho
        $texto->setSize(250, 40);
        
        //Valores
        $id->setValue($param['id']);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( new TLabel('Descrição do Problema: '), $texto );
        $table->addRowSet( '', $id );
        
        $form->setFields(array($texto,$id));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'Notifica'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Notificação de Problema', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public function Notifica ($param)
    {

        //var_dump($param);
        try
        {
            TTransaction::open('sisacad');
            
            //var_dump($data);
            $ma          = new TMantis;
            $profile     = TSession::getValue('profile');
            $servidor    = $ma->FindServidor($profile['login']);
            $sistema     = $ma->FindSistema('SISACAD');
            $parameters  = json_encode(
                           array ('key'=>$param['id'],
                                 'class_to'=>'editaEmentaTurmaForm',
                                 'method_to'=>'onEdit'));
            
            $texto = 'Comunicação acerca de Disciplinas de uma turma';
            $info = array (
                'relator_id'    => $servidor->id,
                'operador_id'   => $servidor->id,
                'duplicata_id'  => 0,
                'prioridade'    => 50,
                'gravidade'     => 30,
                'status'        => 10,
                'resolucao'     => 0,
                'destino_id'    => 1,
                'resumo'        => $texto,
                'categoria_id'  => 5,
                'sistema_id'    => $sistema->id,
                'grupo_id'      => 90,
                'servidor_id'   => 0,
                'data_inicio'   => date('Y-m-d'),
                'data_fim'      => '',
                'data_atual'    => date('Y-m-d'),
                'oculto'        => 'N',
                'json'          => $parameters,
                'acesso'        => 10);//Acesso publico
            //$ma->chamado       = $info;
            //$ret               = $ma->criaChamado();
            $chamado = new incidentes;
            $chamado->fromArray($info);
            $chamado->store();
            $ret = $chamado->id;
            $nota = (!empty($param['texto'])) ? ' notifica algum problema nas diciplinas da ' .
                                                'turma conforme descreve abaixo:<br>' . $param['texto'] :
                                                ' notifica algum problema nas diciplinas da turma ';
            SystemNotification::register( 
                3, 
                'O servidor '.$servidor->nome . $nota, 
                'Aperte o botão para ir para a tela de Gerenciamento de Disciplinas da Turma.', 
                'class=editaEmentaTurmaForm&method=onEdit&key=' . $param['id'] . '&chamado=' . $ret, 
                'Correção', 
                'fa fa-pencil-square-o blu',$sistema->id,80);
            TTransaction::close();
            $action = new TAction(array($this, 'onEdit'));
            $action->setParameter('key', $param['id']);
            new TMessage('info','Administradores notificados.',$action);

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
        }
    }//Fim Módulo
     
}//Fim classe
