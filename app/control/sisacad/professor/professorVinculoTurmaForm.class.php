<?php
/**
 * professorVinculoTurmaForm Master/Detail
 * @author  <your name here>
 */
class professorVinculoTurmaForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
    protected $listamateria;
    protected $professor_id;
    
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
        $this->form = new TForm('form_professor');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Designa Professor para Matéria de uma Turma'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados do Professor');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Turmas com Disciplinas dentro da área de interesse do professor');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id              = new TEntry('id');
        $nome            = new TEntry('nome');
        $cpf             = new TEntry('cpf');
        $criteria        = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        $orgaosorigem_id = new TDBCombo('orgaosorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $postograd_id    = new TDBCombo('postograd_id','sisacad','postograd','id','nome','nome',$criteria);
        //Monta ComboBox com OPMs que o Operador pode ver
        if ($this->nivel_sistema>80)           //Adm e Gestor
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
        $opm_id          = new TDBCombo('opm_id','sicad','OPM','id','nome','nome',$criteria);

        // sizes
        $id->setSize('100');
        $nome->setSize('400');
        $cpf->setSize('150');
        $orgaosorigem_id->setSize('400');
        $postograd_id->setSize('300');
        $opm_id->setSize('400');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        if ($this->nivel_sistema<=80)
        {
            $nome->setEditable(FALSE);
            $cpf->setEditable(FALSE);
            $orgaosorigem_id->setEditable(FALSE);
            $postograd_id->setEditable(FALSE);
            $opm_id->setEditable(FALSE);
        }
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($cpf);
        $this->form->addField($orgaosorigem_id);
        $this->form->addField($postograd_id);
        $this->form->addField($opm_id);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('Id'), $id );
        $table_general->addRowSet( new TLabel('Nome'), $nome );
        $table_general->addRowSet( new TLabel('CPF'), $cpf );
        $table_general->addRowSet( new TLabel('Órgao de origem'), $orgaosorigem_id );
        $table_general->addRowSet( new TLabel('Cargo'), $postograd_id );
        $table_general->addRowSet( new TLabel('Unidade Escolar'), $opm_id );
        
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
        $row->addCell( new TLabel('ID') );
        $row->addCell( new TLabel('Matéria/Turma') );
        $row->addCell( new TLabel('Tipo de Vínculo') );
        $row->addCell( new TLabel('Ato de Autorização') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        if ($this->nivel_sistema>90)
        {
            $new_button=new TButton('new');
            $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
            $new_button->setImage('ico_new.png');
        }
        
        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array('professorList', 'onReload')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // create an action button (go to list)
        $inter_button=new TButton('interesse');
        $inter_button->setAction(new TAction(array($this, 'onNovoInteresse')), 'Nova Área de Interesse');
        $inter_button->setImage('fa:pencil red');
        
        // define form fields
        $this->form->addField($save_button);

        $this->form->addField($return_button);
        $this->form->addField($inter_button);
        
        if ($this->nivel_sistema>90)
        {
            $this->form->addField($new_button);
            $table_master->addRowSet( array($save_button, $new_button,$inter_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        }
        else
        {
            $table_master->addRowSet( array($save_button, $inter_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        }
        
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
            if (isset($param['chamado']))
            {
                $this->chamado = $param['chamado'];
                $chamado = new TMantis();
                $chamado->fechaChamado(array('key'=>$param['chamado']));
            }
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                $this->professor_id = $key;
                
                $object = new professor($key);
                $this->form->setData($object);
                
                $items  = professormateria::where('professor_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $this->addDetailRow($item);
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
        $fer = new TFerramentas();
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $detail_id = new TEntry('detail_id[]');
        $materia_id = new TCombo('materia_id[]');
        $vinculo = new TCombo('vinculo[]');
        $ato_autorizacao = new TText('ato_autorizacao[]');
        
        //Valores
        $vinculo->addItems($fer->lista_vinculo_professor());
        $materia_id->addItems($this->getMaterias($this->professor_id));

        // set id's
        $detail_id->setId('detail_id_'.$uniqid);
        $materia_id->setId('materia_id_'.$uniqid);
        $vinculo->setId('vinculo_'.$uniqid);
        $ato_autorizacao->setId('ato_autorizacao_'.$uniqid);

        // set sizes
        $detail_id->setSize('30');
        $materia_id->setSize('480');
        $vinculo->setSize('90');
        $ato_autorizacao->setSize('140','28');
        
        //Bloqueios
        $detail_id->setEditable(false);
        
        // set row counter
        $detail_id->{'data-row'} = $this->detail_row;
        $materia_id->{'data-row'} = $this->detail_row;
        $vinculo->{'data-row'} = $this->detail_row;
        $ato_autorizacao->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->id)) { $detail_id->setValue( $item->id ); }
        if (!empty($item->materia_id)) { $materia_id->setValue( $item->materia_id ); }
        if (!empty($item->vinculo)) { $vinculo->setValue( $item->vinculo ); }
        if (!empty($item->ato_autorizacao)) { $ato_autorizacao->setValue( $item->ato_autorizacao ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($detail_id);
        $row->addCell($materia_id);
        $row->addCell($vinculo);
        $row->addCell($ato_autorizacao);
        
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($detail_id);
        $this->form->addField($materia_id);
        $this->form->addField($vinculo);
        $this->form->addField($ato_autorizacao);
        
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
     * Save the professor and the professormateria's
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
            //professormateria::where('professor_id', '=', $master->id)->delete();
            $lista = array();
            $apagadas  = 0;
            $ignoradas = 0;
            if( !empty($param['materia_id']) AND is_array($param['materia_id']) )
            {
                foreach( $param['materia_id'] as $row => $materia_id)
                {
                    //var_dump($materia_id);
                    if (!empty($materia_id))
                    {
                        $cadastro = professormateria::where('materia_id','=',$param['materia_id'][$row])->
                                                      where('professor_id','=',$master->id)->load();
                        if ((empty($cadastro) && empty($param['detail_id'][$row])) ||
                            (!empty($cadastro) && !empty($param['detail_id'][$row]) && $cadastro[0]->id == $param['detail_id'][$row]))
                        {
                            $detail = new professormateria;
                            $detail->professor_id     = $master->id;
                            
                            if (!empty($param['detail_id'][$row]))//Mantem o ID atual
                            {
                                $detail->id       = $param['detail_id'][$row];
                            }
                            $detail->materia_id       = $param['materia_id'][$row];
                            $detail->vinculo          = $param['vinculo'][$row];
                            $detail->ato_autorizacao  = $param['ato_autorizacao'][$row];
                            $detail->store();
                            $lista[] = $detail->id;   //Registra os ids que foram incluidos...
                        }
                    }
                }
            }
            //print_r($lista);
            if (count($lista)> 0 )//Apaga somente quem não veio na lista
            {
               $objects =professormateria::where('id', 'NOT IN', $lista)->
                                          where('professor_id', '=', $master->id)->load();
               //var_dump($objects);
               if (!empty($objects))
               {
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
            //TForm::sendData('form_professor', $data);
            TTransaction::close(); // close the transaction
            $action = new TAction(array('professorVinculoTurmaForm','onEdit'));
            $action->setParameters(array('key'=>$master->id));
            $msg = "Registro Salvo com sucesso";
            if ($apagadas == 0 && $ignoradas == 0)
            {
                $msg .= '.';
            }
            if ($apagadas != 0)
            {
                $msg .= '.<br>' . $apagadas . ' Disciplina(s) de turma foi(foram) desvinculada(s) do professor ' . $master->nome;
            }
            if ($ignoradas != 0)
            {
                $msg .= '.<br>' . $ignoradas . ' vínculo(s) não foi(foram) desfeito(s) por já existir registro de aulas.';
            }
            new TMessage('info', $msg,$action);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Busca dados das matérias que possuem vinculo do interesse do professor e a turma
 *---------------------------------------------------------------------------------------*/
    public function getMaterias ($key = null)
    {
        if ($this->listamateria == false )
        {

            $fer   = new TFerramentas();
            $sicad = new TSicadDados();
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
                $this->config        = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
                $this->config_load   = true;                               //Informa que configuração foi carregada
            }
    
            $aux1 = "(SELECT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $this->listas['valores'] . "))";
            $aux2 = "(SELECT id FROM sisacad.turma WHERE opm_id IN (" . $this->listas['valores'] . "))";
            $aux3 = "SELECT id FROM sisacad.turma WHERE id IN " . $aux1 . " OR id IN " . $aux2;
            
            $sql = "SELECT turma.id as id, turma.nome as turma, turma.sigla as turma_sigla, disciplina.nome as disciplina, " .
                      "disciplina.sigla as disciplina_sigla, curso.nome as curso, curso.sigla as curso_sigla, " . 
                      "professor.nome as professor, professor.postograd_id as postograd_id, professor.cpf as cpf, ".
                      "materia.id as materia_id " .
                      "FROM sisacad.materia, sisacad.professordisciplina, sisacad.disciplina, sisacad.turma, " .
                      "sisacad.curso, sisacad.professor " .
                      "WHERE materia.turma_id = turma.id AND materia.disciplina_id = disciplina.id AND " .
                      "professordisciplina.disciplina_id = materia.disciplina_id AND " .
                      "professordisciplina.professor_id = professor.id AND " .
                      "turma.curso_id = curso.id AND professordisciplina.professor_id = " . $key ;
            if ($this->nivel_sistema>80)
            {
                $sql .= ";";
            }
            else
            {
                $sql .= " AND turma.id IN (" . $aux3 . ");";
            }
            $fer = new TFerramentas();
            $materias = $fer->runQuery($sql);
            if (!empty($materias))
            {
                $lista = array();
                foreach($materias as $materia)
                {
                    $lista[$materia['materia_id']] = $materia['disciplina'] . ' - ' .$materia['turma_sigla'] . 
                            ' (' . $materia['curso_sigla'] . ')';
                } 
            }
            else
            {
                $lista = array (0=>'--- Sem nenhum vínculo estabelecido ---');
            }
            $this->listamateria = $lista;
        }
        //var_dump($lista);
        
        return $this->listamateria;
        
    }
/*---------------------------------------------------------------------------------------
 *  Rotina: Cria nova área de Interesse.
 *---------------------------------------------------------------------------------------*/
    public static function onNovoInteresse ($param)
    {
        $id         = new THidden('id');
        $interesse  = new TCombo('interesse');
        //var_dump($param);
        //Tamanho
        $interesse->setSize(300);
        
        //Valores
        $id->setValue($param['id']);
        $interesse->addItems(self::getInteresse($param));
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( $lbl = new TLabel('Área de Interessa: '), $interesse );
        $lbl->setFontColor('red');
        
        $table->addRowSet( '', $id );
        
        $form->setFields(array($interesse,$id));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array('professorVinculoTurmaForm', 'NovoInteresse'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Define nova área de Interesse para o Professor', $form, $action, 'Confirma');

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Salva nova área de Interesse.
 *---------------------------------------------------------------------------------------*/
    public static function NovoInteresse ($param)
    {
        if (!empty($param['interesse']))
        {
            $fer = new TFerramentas();
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
            $opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
            try
            {
                TTransaction::open('sisacad');
                $verifica = professordisciplina::where('professor_id','=',$param['id'])->
                                                 where('disciplina_id','=',$param['interesse'])->load();
                if (!empty($verifica))
                {
                    $ret = array('tipo'=>'error','mensagem'=>'O professor já tem essa área de interesse cadastrada.');
                }
                else
                {
                    $interesse = new professordisciplina();
                    $interesse->professor_id  = $param['id'];
                    $interesse->disciplina_id = $param['interesse'];
                    if ($opm_operador != false)
                    {
                        $interesse->opm_id = $opm_operador;
                    }
                    $interesse->store();
                }
                
                TTransaction::close();
                $ret = array('tipo'=>'info','mensagem'=>'Área de interesse cadastrada para o Professor.');
            }
            catch (Exception $e) // in case of exception
            {
                // shows the exception error message
                new TMessage('error', $e->getMessage());
                // undo all pending operations
                TTransaction::rollback();
            }
        }
        else
        {
            // show the input dialog
            $ret = array('tipo'=>'error','mensagem'=>'É necessário escolher uma área de interesse');
        }
        $action = new TAction(array('professorVinculoTurmaForm', 'onEdit'));
        $action->setParameter('key', $param['id']);
        new TMessage($ret['tipo'],$ret['mensagem'],$action);
    }
/*---------------------------------------------------------------------------------------
 *  Rotina: Salva nova área de Interesse.
 *---------------------------------------------------------------------------------------*/
    public static function getInteresse ($param = nul)
    {
        //var_dump($param);
        $fer   = new TFerramentas();
        $sicad = new TSicadDados();
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
        $opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        $nivel_sistema = $fer->getnivel ('professorVinculoTurmaForm');//Verifica qual nível de acesso do usuário
        $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra

        $aux1 = "(SELECT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $listas['valores'] . "))";
        $aux2 = "(SELECT id FROM sisacad.turma WHERE opm_id IN (" . $listas['valores'] . "))";
        $aux3 = "SELECT id FROM sisacad.turma WHERE id IN " . $aux1 . " OR id IN " . $aux2;
        
        $sql = "SELECT DISTINCT ON (disciplina.nome) disciplina.nome as disciplina, turma.nome as turma_nome, " . 
               "turma.sigla as turma_sigla, disciplina.id as disciplina_id FROM sisacad.materia, " .
               "sisacad.disciplina, sisacad.turma WHERE materia.disciplina_id = disciplina.id AND " .
               "materia.turma_id = turma.id";
        if ($nivel_sistema>80)
        {
            $sql .= ";";
        }
        else
        {
            $sql .= " AND turma.id IN (" . $aux3 . ");";
        }
        $interesses = $fer->runQuery($sql);
        if (!empty($interesses))
        {
            $lista = array();
            foreach($interesses as $interesse)
            {
                $lista[$interesse['disciplina_id']] = $interesse['disciplina'];
            }
        }
        else
        {
            $lista = array(0=>'-- Sem área de Interesses para turmas --');
        }
        return $lista;
    }

    

}//Fim Classe
