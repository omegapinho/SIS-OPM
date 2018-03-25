<?php
/**
 * avaliacao_cursoForm Form
 * @author  <your name here>
 */
class avaliacao_cursoForm extends TPage
{
    protected $form;          // form
    protected $salvar = null; //Controle do botão salvar
    
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Avaliações';        //Nome da página de serviço.
    
    private $opm_operador    = false;     // Unidade do Usuário
    private $listas          = false;           // Lista de valores e array de OPM
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

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
            TSession::setValue('SISACAD_CONFIG', $fer->getConfig($this->sistema));         //Busca o Nível de acesso que o usuário tem para a Classe

            $this->config_load = true;                               //Informa que configuração foi carregada
        }
        
        $curso_militar     = TSession::getValue('curso_militar');
         if (empty($curso_militar))
         {
              TSession::setValue('curso_militar',null);
              TApplication::loadPage('cursoList','onReload');
              //var_dump($data);
         }
         
        // creates the form
        $this->form = new TQuickForm('form_avaliacao_curso');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Gestor de Avaliações - Edição');

        // create the form fields
        $id = new TEntry('id');
        $criteria = new TCriteria();
        $criteria->add( new TFilter('id','=',$curso_militar->id));
        
        $curso = new TDBCombo('curso_id','sisacad','curso','id','nome','nome',$criteria);
        
        $materias_previstas_id = new TCombo('materias_previstas_id');
        $tipo_avaliacao        = new TCombo('tipo_avaliacao');
        $dt_inicio             = new TDate('dt_inicio');
        $ch_minima             = new TEntry('ch_minima');
        $media_minima          = new TEntry('media_minima');
        $oculto                = new TCombo('oculto');
        $motivo                = new THidden('motivo');
        $usuario_liberador     = new THidden('usuario_liberador');
        $data_liberacao        = new THidden('data_liberacao');

        //Valores
        $curso->setValue($curso_militar->id);
        $materias_previstas_id->addItems($this->getDisciplinas($curso_militar->id));
        $oculto->addItems($fer->lista_sim_nao());
        $tipo_avaliacao->addItems($fer->lista_verificacoes());
        
        $media_minima->setValue('05.00');
        $oculto->setValue('N');
        
        //Mascaras
        $ch_minima->setMask('999');
        $media_minima->setMask('99.99');
        $dt_inicio->setMask('dd/mm/yyyy');

        // add the fields
        $this->form->addQuickField('Id', $id,  80 );
        $this->form->addQuickField('Curso', $curso,  400 );
        $this->form->addQuickField('Matérias', $materias_previstas_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Tipo de Avaliação', $tipo_avaliacao,  300 , new TRequiredValidator);
        $this->form->addQuickField('Data de Início', $dt_inicio,  120 , new TRequiredValidator);
        $this->form->addQuickField('CH Mínima', $ch_minima,  120 );
        $this->form->addQuickField('Média Mínima', $media_minima,  120 );
        $this->form->addQuickField('Encerrado ?', $oculto,  120 );
        $this->form->addQuickField('', $motivo,  200 );
        $this->form->addQuickField('', $usuario_liberador,  200 );
        $this->form->addQuickField('', $data_liberacao,  100 );

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $curso->setEditable(false);
        }
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('avaliacao_cursoList', 'onReload')), 'ico_back.png');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'cursoList'));
        $container->add($this->form);
        
        parent::add($container);
    }
/*------------------------------------------------------------------------------
 * Valida o tipo de prova
 *------------------------------------------------------------------------------*/
    public function validaTipoProva ($param)
    {
        $status = false; 
        try
        {
            TTransaction::open('sisacad'); // open a transaction

            

            TTransaction::close(); // close the transaction
        }
        catch  (Exception $e)
        {
            //new TMessage('error', $e->getMessage()); // shows the exception error message
            //$this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
        return $status;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 * Save form data
 * @param $param Request
 *------------------------------------------------------------------------------*/
    public function onSave( $param )
    {
        $fer= new TFerramentas();
        try
        {
            TTransaction::open('sisacad'); // open a transaction
            
            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array
            
            //Carrega as avaliações já feitas para a disciplina do curso
            $status  = true;
            $objects = avaliacao_curso::where('curso_id','=',$data->curso_id)->
                                        where('materias_previstas_id','=',$data->materias_previstas_id)->load(); 

            //VU e V1 são únicas...não pode criar outra prova após as mesmas
            $erro        = 0;
            $lista_erros = array(1=>'Esse tipo de avaliação já foi aplicado.',
                                 2=>'2ª Verificação só se aplica após a 1ª Verificação conforme Carga Horária<br>da Disciplina permitir.',
                                 3=>'Verificação Final só é aplicável após 1ª ou 2ª VC conforme a carga horária.',
                                 4=>'Recuperação Final só se aplica após haver VU ou  VF.', 
                                 5=>'Uma verificação já foi aplicada tornando essa inviável de aplicar.',
                                 6=>'Já houve a aplicação de uma Verificação/Recuperação Final.');           
            if ($objects)
            {
                //Define os status iniciais dos tipos de avaliações
                $ver = array ('VU'=>false,'V1'=>false,'V2'=>false,'VF'=>false,'RF'=>false);
                //Conforme o que já foi aplicado, altera o status das provas aplicadas
                foreach ($objects as $object)
                {
                    if ($object->tipo_avaliacao == $data->tipo_avaliacao )
                    {
                        $status = false;
                        $erro = 1;
                    }
                    $ver [$object->tipo_avaliacao] = true;
                }
                if ($status === true)
                {
                    switch ($data->tipo_avaliacao)
                    {
                        case 'VU'://Verificação Única
                            //Só é permitido criar uma VU se não houver nenhuma das outros tipos de prova criada
                            if ($ver['VU'] === true || $ver['V1'] === true || $ver['V2'] === true || $ver['VF'] === true ||
                                $ver['RF'] === true)
                            {
                                $status = false;
                                $erro = 5;
                            }
                            break;
                        case 'V1'://1ª Verificação
                            //Para criar a 1ªVC não se pode ter nenhuma outra criada
                            if ($ver['VU'] === true || $ver['V1'] === true || $ver['V2'] === true || $ver['VF'] === true ||
                                $ver['RF'] === true)
                            {
                                $status = false;
                                $erro = 5;
                            }
                            break;
                        case 'V2'://2ª Verificação
                            //Só pode criar uma 2ªVC se houver uma 1ªVC antes
                            if ($ver['V1'] === false)
                            {
                                //Tem que ter havido a 1ªVC
                                $status = false;
                                $erro = 2;
                            }
                            else if ($ver['VF'] === true || $ver['RF'] === true)
                            {
                                //Se VF ou RF tiver sido aplicada, não há aplicação de 2ª VC
                                $status = false;
                                $erro = 6;
                            }
                            break;
                        case 'RF'://Recuperação Final
                            if ($ver['VF'] === false && $ver['VU'] === false)
                            {
                                //Só pode após a VF ou a VU
                                $status = false;
                                $erro = 4;
                            }
                            break;
                        case 'VF'://Verificação Final
                            if ($ver['RF'] === true || $ver['VU'] === true)
                            {
                                //Não pode ter uma VF se já teve uma VU ou se já teve uma RF
                                $status = false;
                                $erro = 3;
                            }
                            break;
                    } 
                }               

            }
            if ($status === false)
            {
                throw new Exception ('O tipo de avaliação (' . $fer->lista_verificacoes($data->tipo_avaliacao) .
                                     ') é inválido, verifique se não escolheu um não aplicável. <br>' .
                                     'A descrição exata do problema é:<br>' . $lista_erros[$erro] );
            }
            
            $object = new avaliacao_curso;  // create an empty object

            $object->fromArray( (array) $data); // load the object with data
            
            $object->dt_inicio         = TDate::date2us($object->dt_inicio);
            $object->oculto            = ($object->oculto != 'N' && $object->oculto != 'S') ? 'N' : $object->oculto;
            $object->media_minima      = (!isset($object->media_minima) || $object->media_minima == '') ? '05.00' : $object->media_minima;
            $object->data_liberacao    = date('Y-m-d');
            $object->usuario_liberador = TSession::getValue('login');
            
            $object->store(); // save the object
            //Limpa qualquer vestígio para essa avaliação
            $apagar = avaliacao_turma::where('avaliacao_curso_id','=',$object->id)->delete();
            
            $disciplina = materias_previstas::where('id','=',$data->materias_previstas_id)->load();
            
            //Gera as avaliações para as turmas
            //
            //Query que busca as turmas do curso que tem a disciplina não encerrada ainda
            $sql = "SELECT turma.id AS turma_id, materia.id AS materia_id FROM sisacad.turma, sisacad.materia, " .
                   "sisacad.curso, sisacad.materias_previstas WHERE turma.id = materia.turma_id AND " .
                   "curso.id = materias_previstas.curso_id AND curso.id = turma.curso_id AND " .
                   "materias_previstas.disciplina_id = materia.disciplina_id AND " .
                   "materias_previstas.disciplina_id =" . $disciplina[0]->disciplina_id . 
                   " AND curso.id = " . $data->curso_id . 
                   " AND (materia.oculto != 'S' OR materia.oculto ISNULL);";
            $turmas = $fer->runQuery($sql);
            if (!empty($turmas))
            {
                foreach($turmas as  $turma)
                {
                    $avalia = new avaliacao_turma();
                    $avalia->avaliacao_curso_id = $object->id;
                    $avalia->turma_id           = $turma['turma_id'];
                    $avalia->materia_id         = $turma['materia_id'];
                    $avalia->oculto             = 'N';
                    $avalia->store();
                }
            }
            else
            {
                throw new Exception ('Não há turmas ou a disciplina foi encerrada em todas as existentes');
            }
            
            // get the generated id
            $data->id = $object->id;
            //Se a verificação é Unica ou é final, já cria a recuperação
            if ($data->tipo_avaliacao == 'VF' || $data->tipo_avaliacao == 'VU')
            {

                $object = new avaliacao_curso;      // create an empty object
                $object->fromArray( (array) $data); // load the object with data
                unset($object->id);                 //Apaga a Id para ser um novo registro                
                $object->dt_inicio         = TDate::date2us($object->dt_inicio);
                $object->oculto            = ($object->oculto != 'N' && $object->oculto != 'S') ? 'N' : $object->oculto;
                $object->media_minima      = (!isset($object->media_minima) || $object->media_minima == '') ? '05.00' : $object->media_minima;
                $object->data_liberacao    = date('Y-m-d');
                $object->tipo_avaliacao    = 'RF';
                $object->usuario_liberador = TSession::getValue('login');
                
                $object->store(); // save the object
                
                /*$apagar = avaliacao_turma::where('avaliacao_curso_id','=',$object->id)->delete();
                
                $disciplina = materias_previstas::where('id','=',$data->materias_previstas_id)->load();
                
                //Gera as avaliações para as turmas
                //
                //Query que busca as turmas do curso que tem a disciplina não encerrada ainda
                $sql = "SELECT turma.id AS turma_id, materia.id AS materia_id FROM sisacad.turma, sisacad.materia, " .
                       "sisacad.curso, sisacad.materias_previstas WHERE turma.id = materia.turma_id AND " .
                       "curso.id = materias_previstas.curso_id AND curso.id = turma.curso_id AND " .
                       "materias_previstas.disciplina_id = materia.disciplina_id AND " .
                       "materias_previstas.disciplina_id =" . $disciplina[0]->disciplina_id . 
                       " AND curso.id = " . $data->curso_id . 
                       " AND (materia.oculto != 'S' OR materia.oculto ISNULL);";
                $turmas = $fer->runQuery($sql);*/
                if (!empty($turmas))
                {
                    foreach($turmas as  $turma)
                    {
                        $avalia = new avaliacao_turma();
                        $avalia->avaliacao_curso_id = $object->id;
                        $avalia->turma_id           = $turma['turma_id'];
                        $avalia->materia_id         = $turma['materia_id'];
                        $avalia->oculto             = 'S';
                        $avalia->store();
                    }
                }
            }  
            $this->form->setData($data); // fill form data
            
            TTransaction::close(); // close the transaction
            new TMessage('info', 'Avaliação Criada para as Turmas com sucesso!');
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
        $this->salvar = null;
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
                $object = new avaliacao_curso($key); // instantiates the Active Record
                $object->dt_inicio = TDate::date2br($object->dt_inicio);
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
                
                if ($this->getBotaoSalvar($key) == true)//Se não existe prova aberta pode salvar 
                {
                    TButton::enableField('form_avaliacao_curso','salvar');
                }
                else                                    //Não pode salvar se já existe prova aberta
                {
                    TButton::disableField('form_avaliacao_curso','salvar');
                }
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
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Monta combo box de Disciplinas
 *------------------------------------------------------------------------------*/
    public static function getDisciplinas($key = null)
    {
        $lista = array(0=>' --- Sem Disciplinas na Ementa ---');
        try
        {
            TTransaction::open('sisacad');
            $materias = materias_previstas::where('curso_id','=',$key)->load();
            //var_dump($materias);
            if ($materias)
            {
                $lista = array();
                foreach ($materias as $materia)
                {
                    //$disciplina = $materia->get_disciplina();
                    $lista[$materia->id] = $materia->disciplina->nome;
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
/*------------------------------------------------------------------------------
 *    Controle do Botão salvar
 *------------------------------------------------------------------------------*/
    public function getBotaoSalvar($key = null)
    {
        if (!empty($this->salvar))
        {
            return $this->salvar;
        }
        $this->salvar = $this->verificaProvas($key);
        return $this->salvar;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Verifica
 *------------------------------------------------------------------------------*/
    public static function verificaProvas($key = null)
    {
        $ret = 'N';
        $fer = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            $sql = "SELECT avaliacao_prova.id, avaliacao_prova.tipo_prova FROM " .
                   "sisacad.avaliacao_curso, sisacad.avaliacao_turma, sisacad.avaliacao_prova WHERE " .
                   "avaliacao_curso.id = avaliacao_turma.avaliacao_curso_id AND avaliacao_turma.id = avaliacao_prova.avaliacao_turma_id " .
                   "AND avaliacao_curso.id = " . $key . ";";
            $provas = $fer->runQuery($sql);
            if (!empty($provas))
            {
                $ret = 'S';
            }
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $ret;

    }//Fim Módulo
}//Fim Classe
