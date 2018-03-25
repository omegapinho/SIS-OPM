<?php
/**
 * cursoForm Form
 * @author  <your name here>
 */
class cursoForm extends TPage
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
        $this->form = new TQuickForm('form_curso');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Cadastro e Gerenciamento de Curso');
        
        $fer = new TFerramentas();
        TSession::setValue('curso_militar',null);//Limpa variável de seção

        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $sigla = new TEntry('sigla');
        $natureza = new TCombo('natureza');
        $nivel_pagamento_id = new TDBCombo('nivel_pagamento_id','sisacad','nivel_pagamento','id','nome','nome');
        
        $criteria = new TCriteria();
        $criteria->add(new TFilter ('oculto','!=','S'));
        $orgaoorigem_id = new TDBCombo('orgaoorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $tipo_curso = new TCombo('tipo_curso');
        $turno = new TCombo('turno');
        $data_inicio = new TDate('data_inicio');
        $data_final = new TDate('data_final');
        $carga_horaria = new TEntry('carga_horaria');
        $ementa_ok = new THidden('ementa_ok');
        $ato_autorizacao = new TText('ato_autorizacao');
        $oculto = new TCombo('oculto');

        //Valores
        $natureza->addItems($fer->lista_natureza_curso());
        $turno->addItems($fer->lista_turno());
        $tipo_curso->addItems($fer->lista_tipos_curso());
        $oculto->addItems($fer->lista_sim_nao());
        $orgaoorigem_id->setValue('1');
        
        $natureza->setValue('2');
        $tipo_curso->setValue('FOR');
        $turno->setValue('I');
        $oculto->setValue('N');
        
        //Mascaras
        $data_final->setMask('dd-mm-yyyy');
        $data_inicio->setMask('dd-mm-yyyy');
        

        // add the fields
        $this->form->addQuickField('Id', $id,  100 );
        $this->form->addQuickField('Instituição Interessada', $orgaoorigem_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Nome', $nome,  400 , new TRequiredValidator);
        $this->form->addQuickField('Sigla', $sigla,  200 , new TRequiredValidator);
        $this->form->addQuickField('Natureza do Curso', $natureza,  400 , new TRequiredValidator);
        $this->form->addQuickField('Nivel de Ensino', $nivel_pagamento_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Tipo de Curso', $tipo_curso,  400 , new TRequiredValidator);
        $this->form->addQuickField('Turno', $turno,  200 , new TRequiredValidator);
        $this->form->addQuickField('Data de Início', $data_inicio,  120 , new TRequiredValidator);
        $this->form->addQuickField('Data de Término', $data_final,  120 );
        $this->form->addQuickField('Carga Horária', $carga_horaria,  100 , new TRequiredValidator);
        $this->form->addQuickField('Ato de Autorização', $ato_autorizacao,  400 , new TRequiredValidator);
        $this->form->addQuickField('Curso Encerrado?', $oculto,  120 );
        $this->form->addQuickField(null, $ementa_ok);
        
        //Tamanhos
        $ato_autorizacao->setSize(400,40);

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            
        }
        
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        $this->form->addQuickAction('Disciplinas da Ementa',  new TAction(array($this, 'onMaterias')), 'fa:book gray');
        $this->form->addQuickAction('Gestão de Turmas',  new TAction(array($this, 'onTurma')), 'fa:users green');
        $this->form->addQuickAction('Avaliações',  new TAction(array($this,'onAvalia')), 'fa:book red');
        $this->form->addQuickAction('Ver Progresso',  new TAction(array($this,'onGraficos')), 'fa:bar-chart black');
        $this->form->addQuickAction('C.H. Usada',  new TAction(array($this,'onCargaHoraria')), 'fa:calendar red');
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('cursoList', 'onReload')), 'ico_back.png');

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'cursoList'));
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
            
            $object = new curso;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $data->data_inicio = TDate::date2us($data->data_inicio);
            $data->data_final  = TDate::date2us($data->data_final);
            $object->fromArray( (array) $data); // load the object with data
            $object->nome  = mb_strtoupper($object->nome,'UTF-8');
            $object->sigla = mb_strtoupper($object->sigla,'UTF-8');            
            $object->store(); // save the object
            
            //Atualiza o status das turmas se houver
            $turmas = turma::where('curso_id','=',$object->id)->load();
            if (!empty($turmas))
            {
                foreach ($turmas as $turma)
                {
                    $turma->oculto = $object->oculto;
                    $turma->store();
                }
            }
            
            // get the generated id
            $data->id = $object->id;
            $data->data_inicio = TDate::date2br($data->data_inicio);
            $data->data_final  = TDate::date2br($data->data_final);

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
                $object = new curso($key); // instantiates the Active Record
                $object->data_final  = TDate::date2br($object->data_final);
                $object->data_inicio = TDate::date2br($object->data_inicio); 
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
    }//Fim de Módulo
/*------------------------------------------------------------------------------
 *    Direciona para Gerir turmas
 *------------------------------------------------------------------------------*/
 public function onTurma ($param = null)
 {
     if ($param)
     {
         //var_dump($param);
     }
     $data = $this->form->getData();
     
     if (empty($data->id))
     {
         new TMessage('info','Por favor, salve primeiro antes de cadastrar qualquer turma!!!');
     }
     else if (empty($data->ementa_ok) || $data->ementa_ok != 'S')
     {
        new TMessage('info','As disciplinas da ementa não foram incluídas!!!<br>'.
                        'Cadastre as disciplinas através do Botão: Disciplina da Ementa ou,<br>'.
                        ' através deste mesmo botão, notifique que cadastrou a ementa.');        
     }
     else
     {
          TSession::setValue('curso_militar',$data);
          TApplication::loadPage('turmaForm');
          //var_dump($data);
     }
     $this->form->setData($data);
     
 }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Direciona para Gerir Materias da Ementa
 *------------------------------------------------------------------------------*/
 public function onMaterias ($param = null)
 {
     if ($param)
     {
         //var_dump($param);
     }
     $data = $this->form->getData();
     
     if (empty($data->id))
     {
         new TMessage('info','Por favor, salve primeiro antes de cadastrar qualquer disciplina da ementa!!!');
     }
     else
     {
          TSession::setValue('curso_militar',$data);
          TApplication::loadPage('disciplinaEmentaForm','onEdit', array('key'=>$data->id));
          //var_dump($data);
     }
     $this->form->setData($data);

 }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Gera o gráficos em tela
 *------------------------------------------------------------------------------*/
    public static function onGraficos ($param)
    {
        //var_dump($param);
        $id            = new THidden('id');
        $carga_horaria = new THidden('carga_horaria');
        $nome          = new THidden('nome');
        $criteria = new TCriteria();
        $sql = "(SELECT disciplina.id FROM sisacad.disciplina, sisacad.curso, sisacad.materias_previstas " .
               "WHERE curso.id = materias_previstas.curso_id AND " . 
               "materias_previstas.disciplina_id = disciplina.id AND " .
               "curso.id = " . $param['id'] . ")";
        $criteria->add(new TFilter('id','IN',$sql));
        $criteria->add (new TFilter ('oculto','!=','S'));
        
        $disciplina_id = new TDBCombo('disciplina_id','sisacad','disciplina','id','nome','nome',$criteria);
        
        //var_dump($param);
        $id->setValue($param['id']);
        $carga_horaria->setValue($param['carga_horaria']);
        $nome->setValue($param['nome']);
        
        //Tamanho
        $disciplina_id->setSize(300);
        //Mascaras

        //Trava
        //$id->setEditable(false);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( new TLabel('Disciplina: '), $disciplina_id );
        $table->addRowSet(array($nome, $id,$carga_horaria) );

        
        $form->setFields(array($disciplina_id,$id,$nome,$carga_horaria));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array('cursoForm', 'Progresso'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Verifica o gráfico do progresso das turmas do curso', $form, $action, 'Confirma');
    }
  
/*------------------------------------------------------------------------------
 *  Gera o progresso das Turma do curso na tela
 *------------------------------------------------------------------------------*/
    public static function Progresso ($param)
    {

        //var_dump($param);exit;
        //$data = $this->form->getData();
        //$this->form->setData($data);
        if (empty($param['disciplina_id']))
        {
            $html = new THtmlRenderer('app/resources/google_bar_chart.html');
            $dados = self::getDadosCurso($param);
            $legenda = array();
            $eixo    = array();
            
            if (!empty($dados))
            {
                $legenda[] = 'Turmas';
                $eixo[]    = 'Carga Horária em Percentual';
                foreach ($dados as $dado)
                {
                    $legenda[] = $dado->turma_id;
                    $eixo[]    = $dado->carga_percent;
                }
            }
            
            $data = array();
            $data[] = $legenda;
            $data[] = $eixo;
           
            $panel = new TPanelGroup('Progresso das Turmas do Curso ' . $param['nome'] . ' - C.H. do Curso ' . $param['carga_horaria'] . 'hs' );
            $panel->add($html);
            
            // replace the main section variables
            $html->enableSection('main', array('data'   => json_encode($data),
                                               'width'  => '100%',
                                               'height'  => '450px',
                                               'title'  => 'Progresso Percentual das turmas',
                                               'ytitle' => 'Percentual', 
                                               'xtitle' => 'Turmas'));
            
        }
        else
        {
            $html = new THtmlRenderer('app/resources/google_bar_chart.html');
            $array_dados = self::getDadosDisciplina($param);
            $dados = $array_dados['lista'];
            $base  = $array_dados['base'];
            $legenda = array();
            $eixo    = array();
            
            if (!empty($dados))
            {
                $legenda[] = 'Turmas';
                $eixo[]    = $base['disciplina'];
                foreach ($dados as $dado)
                {
                    $legenda[] = 'Turma ' . $dado->turma_id;
                    $eixo[]    = $dado->carga_percent;
                }
            }
            
            $data = array();
            $data[] = $legenda;
            $data[] = $eixo;
            $legenda_panel  = (!empty($base['disciplina'])) 
                                ? 'Progresso em % da disciplina - ' . $base['disciplina'] . ' - ' 
                                : 'Progresso em % de disciplina ';
            $legenda_panel .= 'das turmas do ' . $param['nome'];
            $legenda_panel .= '/ C.H. Prevista ' . $base['carga_horaria'] . 'hs.';
            
            
            $panel = new TPanelGroup($legenda_panel);
            $panel->add($html);
            
            // replace the main section variables
            $html->enableSection('main', array('data'   => json_encode($data),
                                               'width'  => '100%',
                                               'height'  => '450px',
                                               'title'  => 'Progresso Percentual',
                                               'ytitle' => 'Percentual', 
                                               'xtitle' => 'Turmas'));
        }   
        // show the input dialog

        $window = TWindow::create('Gráfico do Progresso de uma disciplina em um Curso', 1200, 550);
        //$window->setStackOrder(1000);
        $window->add($panel);
        $window->show();
        
        //self::onEdit(array('key'=>$param['id']));

    }//Fim  Módulo
/*------------------------------------------------------------------------------
 *  Carrega dados para estatística - geral das turmas
 *------------------------------------------------------------------------------*/
    public static function getDadosCurso ($param)
    {
        try
        {
            $lista   = false;
            $fer     = new TFerramentas();
            $ci      = new TSicadDados();
            $acad    = new TSisacad();
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário
            
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
               
            // creates a repository for materia
            $repository = new TRepository('turma');

            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            
            $criteria->add($filter = new TFilter('curso_id', '=', $param['id'])); // add the session filter

            // load the objects according to criteria
            $turmas = $repository->load($criteria, FALSE);
            if ($turmas)
            {
                // iterate the collection of active records
                //var_dump($turmas);exit;
                $lista = array();
                foreach ($turmas as $turma)
                {
                    // Atualiza os dados do objeto
                    $object = new stdClass;
                    $sql  = '(SELECT id FROM sisacad.materia WHERE turma_id = ' . $turma->id . ')';
                    $sql2 = 'SELECT SUM(horas_aula) as ch_tot FROM sisacad.controle_aula WHERE materia_id IN '.$sql;
                    $dados = $fer->runQuery($sql2);
                    //echo $sql2;
                    //var_dump($dados);
                    if (!empty($dados))
                    {
                        $ch = $dados['0']['ch_tot'];
                    }
                    else
                    {
                        $ch = 0;
                    }
                    $object->carga_total   = $ch;
                    $percent               = ($ch * 100) / $param['carga_horaria'];
                    $object->carga_percent = $percent;
                    $object->turma_id      = $turma->sigla;
                    $object->turma_nome    = $turma->nome;

                    $lista[]               = $object;
                }
            }
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage() . '<br>'. $criteria->dump());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Carrega dados para estatística - filtrando por disciplina
 *------------------------------------------------------------------------------*/
    public static function getDadosDisciplina ($param)
    {
        try
        {
            $lista   = false;
            $base    = array('carga_horaria'=>0,'disciplina'=>'');
            
            $fer     = new TFerramentas();
            $ci      = new TSicadDados();
            $acad    = new TSisacad();
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário
            
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
               
            // creates a repository for materia
            $repository = new TRepository('turma');

            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            
            $criteria->add($filter = new TFilter('curso_id', '=', $param['id'])); // add the session filter

            // load the objects according to criteria
            $turmas = $repository->load($criteria, FALSE);
            if ($turmas)
            {
                // iterate the collection of active records
                //var_dump($turmas);exit;
                $lista = array();
                $cs     = materias_previstas::where('curso_id','=',$param['id'])->
                                              where('disciplina_id','=',$param['disciplina_id'])->load();
                if (!empty($cs))
                {
                    foreach($cs as $c)
                    {
                        $carga_horaria = $c->carga_horaria;
                        $disciplina    = $c->disciplina->nome;
                    }
                    $carga_horaria = (!empty($carga_horaria)) ? $carga_horaria : 0;
                    $disciplina    = (!empty($disciplina)) ? $disciplina : '';
                }
                else
                {
                    $carga_horaria = 0;
                    $disciplina    = '';
                }
                $base = array('carga_horaria'=>$carga_horaria,'disciplina'=>$disciplina);
                foreach ($turmas as $turma)
                {
                    //Define a carga horária da matéria

                    // Atualiza os dados do objeto
                    $object = new stdClass;
                    $sql  = '(SELECT id FROM sisacad.materia WHERE turma_id = ' . $turma->id . 
                            ' AND disciplina_id = ' . $param['disciplina_id'] .')';
                    $sql2 = 'SELECT SUM(horas_aula) as ch_tot FROM sisacad.controle_aula WHERE materia_id IN '.$sql;
                    $dados = $fer->runQuery($sql2);

                    //echo $sql2;
                    //var_dump($dados);
                    if (!empty($dados))
                    {
                        $ch = $dados['0']['ch_tot'];
                    }
                    else
                    {
                        $ch = 0;
                    }
                    $object->carga_total   = $ch;
                    $percent               = ($ch * 100) / $carga_horaria;
                    $object->carga_percent = $percent;
                    $object->turma_id      = $turma->sigla;
                    $object->turma_nome    = $turma->nome;
                    $lista[]               = $object;
                }
            }
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage() . '<br>'. $criteria->dump());
            TTransaction::rollback();
        }
        return array('lista'=>$lista,'base'=>$base);
    }//Fim Módulo
    
/*------------------------------------------------------------------------------
 *    Cria avaliações
 *------------------------------------------------------------------------------*/
 public function onAvalia ($param = null)
 {
     if ($param)
     {
         //var_dump($param);
     }
     $data = $this->form->getData();
     
     if (empty($data->id))
     {
         new TMessage('info','Por favor, salve primeiro antes de cadastrar qualquer disciplina da ementa!!!');
     }
     else
     {
          TSession::setValue('curso_militar',$data);
          TApplication::loadPage('avaliacao_cursoList','onReload', array('key'=>$data->id));
          //var_dump($data);
     }
     $this->form->setData($data);

 }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Cria avaliações
 *------------------------------------------------------------------------------*/
 public static function onCargaHoraria ($param = null)
 {
        $lista = self::getSaldoCurso($param);
        if ($lista)
        {
            $fer = new TFerramentas();
            $head = array('Turma','Cidade','C.H. Prevista','C.H. Usada','Dias Finais (dia de 8hs)');
            $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                        'bordercolor="black" width="100%" '.
                                                        'style="border-collapse: collapse;"',
                                                        'cab'=>'style="background: lightblue; text-align: center;"',
                                                        'row'=>'style="text-align: right;"'));
            if (!empty($tabela))
            {
                $rel = new TBdhReport();
                $bot = $rel->scriptPrint();
                $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                       '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                       '<h4>USO DE CARGA HORÁRIA DO(A) '. $param['nome'] . '</h4><br><br></center>';
                $botao = '<center>'.$bot['botao'].'</center>';
                $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
                $window = TWindow::create('Uso de Carga Horária Geral do(a) ' . $param['nome'], 0.8, 0.8);
                $window->add($tabela);
                $window->show();
            }
        }
        else
        {
            new TMessage('error','Não consegui a listagem de turmas do Curso.<br>' . 
                                 'Verifique se já existe turmas cadastradas com sua devida ementa.');
        }

 }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getSaldoCurso ($param)
    {
        $lista  = false;
        try
        {
            TTransaction::open('sisacad');
            $fer       = new TFerramentas();
            
            //Carrega dados do professor
            $turmas = turma::where('curso_id','=', $param['id'])->orderBy('cidade','asc')->load();
            if ($turmas)
            {
                $lista = array();
                foreach ($turmas as $turma)
                {
                    $nome = $turma->sigla;
                    $city = $turma->cidade;
                    //Carga Horária máxima da turma            
                    $sql1      = 'SELECT SUM(carga_horaria) as ch_total FROM sisacad.materia WHERE turma_id = ' . $turma->id;
                    $ch_t      = $fer->runQuery($sql1);

                    //Carga Horária usada da turma
                    $sql2      = '(SELECT id FROM sisacad.materia WHERE turma_id = ' . $turma->id .')';
                    $sql3      = 'SELECT SUM(horas_aula) as ch_usada FROM sisacad.controle_aula WHERE materia_id IN ' . $sql2;
                    $ch_u      = $fer->runQuery($sql3);
                    
                    $ch_total = (empty($ch_t[0]['ch_total'])) ? 0 : $ch_t[0]['ch_total'];
                    $ch_usada = (empty($ch_u[0]['ch_usada'])) ? 0 : $ch_u[0]['ch_usada'];
                    $dias     = ($ch_total - $ch_usada) / 8;
                    $dias     = ($dias <= 0) ? 0 : $dias;
                    
                    $lista[] = array('nome'=>$nome,'cidade'=>$city,'ch_total'=>$ch_total . ' horas',
                                     'ch_usada'=>$ch_usada  . ' horas','dias'=>ceil($dias) . ' dias');
                }
            }

            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
        }

        return $lista;
    }//Fim Módulo 
 
}//Fim Classe
