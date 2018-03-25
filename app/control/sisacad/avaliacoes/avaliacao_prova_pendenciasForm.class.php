<?php
/**
 * avaliacao_provaForm Master/Detail
 * @author  Capitão PM Fernando de Pinho Araújo
 */
class avaliacao_prova_pendenciasForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
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
        
        // creates the form
        $this->form = new TForm('form_avaliacao_prova');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Gestor de Provas - Conferência de Pendências'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados da Prova/Verificação');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Alunos da Turma conferência de Pendências');
        
        $scroll = new TScroll();
        $scroll->setSize('100%',200);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id                   = new TEntry('id');
        $dt_aplicacao         = new TDate('dt_aplicacao');
        $tipo_prova           = new TCombo('tipo_prova');
        $tipo_avaliacao       = new TEntry('tipo_avaliacao');
        $avaliacao_turma_id   = new THidden('avaliacao_turma_id');

        // sizes
        $id->setSize('80');
        $dt_aplicacao->setSize('120');
        $tipo_prova->setSize('200');
        $tipo_avaliacao->setSize('200');

        //Mascara
        $dt_aplicacao->setMask('dd/mm/yyyy');
        
        //Valores
        $tipo_prova->addItems($fer->lista_tipo_prova());
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $dt_aplicacao->setEditable(FALSE);
            $tipo_prova->setEditable(FALSE);
            $tipo_avaliacao->setEditable(FALSE);
        }
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($dt_aplicacao);
        $this->form->addField($tipo_prova);
        $this->form->addField($avaliacao_turma_id);
        
        // add form fields to the screen
        $table_general->addRowSet( array(new TLabel('Id'), $id,new TLabel('Data da Aplicação'), $dt_aplicacao,
                                    new TLabel('Tipo de Prova'), $tipo_prova,$avaliacao_turma_id));
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $scroll->add($this->table_details);
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('Aluno') );
        $row->addCell( new TLabel('Nota') );
        $row->addCell( new TLabel('Status') );
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        //Encerra o trabalho de lançamento de notas
        $end_button=new TButton('concluir');
        $end_button->setAction(new TAction(array($this, 'onConclui')), 'Conclui Pendências');
        $end_button->setImage('fa:circle green');
        
        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
        
        //Retorna para Listagem
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), _t('Back to the listing'));
        $ret_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($ret_button);
        $this->form->addField($end_button);
        //$this->form->addField($new_button);
        
        $table_master->addRowSet( array($save_button,$end_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'avaliacao_provaList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        $fer = new TFerramentas();
        $sis = new TSisacad();
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new avaliacao_prova($key);
                $tipo_avaliacao = $object->avaliacao_turma->avaliacao_curso->tipo_avaliacao;
                
                //$object->dt_aplicacao = TDate::date2br($$object->dt_aplicacao);
                $this->form->setData($object);
                
                $items  = avaliacao_aluno::where('avaliacao_prova_id', '=', $key)->orderBy('id')->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $cpf = $item->aluno->cpf;
                        $dados = $sis->getDadosAluno($cpf);//Busca dados do Aluno para preencher campo
                        if ($dados != false)//Se retornar os dados do aluno, preenche
                        {
                            if (!empty($dados->rgmilitar))
                            {
                                $rg = ' RG ' . $dados->rgmilitar; 
                            }
                            else if (!empty($dados->rgcivil))
                            {
                                $rg = ' CI ' . $dados->rgcivil;
                            }
                            else
                            {
                                $rg = '';
                            }
                            $rg .= ' ';
                            $posto = $dados->postograd;
                            $posto = (!empty($posto)) ? $sis->getPostograd($posto) : '';
                            $ident = $posto . $rg . $dados->nome . ', CPF '.$dados->cpf;
                        }
                        else
                        {
                            $ident = '-- Dados do aluno não localizado -- ';
                        }
                        
                        $item->aluno_id = $ident;
                        $this->addDetailRow($item , $object->tipo_prova);
                    }
                    if (!empty($tipo_avaliacao))
                    {
                        $ob = new stdClass;
                        $ob->tipo_avaliacao = $fer->lista_verificacoes($tipo_avaliacao);
                        $this->form->sendData('form_avaliacao_prova',$ob);
                    }
                    // create add button
                    /*$add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);*/
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
    public function addDetailRow($item, $tipo_prova = '1C')
    {
        $fer = new TFerramentas();
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $detail_id        = new THidden('detail_id[]');
        $aluno_id         = new TEntry('aluno_id[]');
        $nota             = new TEntry('nota[]');
        $status           = new TCombo('status[]');
        $usuario_lancador = new THidden('usuario_lancador[]');
        $data_lancamento  = new THidden('data_lancamento[]');

        // set id's
        $detail_id->setId('detail_id_'.$uniqid);
        $aluno_id->setId('aluno_id_'.$uniqid);
        $nota->setId('nota_'.$uniqid);
        $status->setId('status_'.$uniqid);
        $usuario_lancador->setId('usuario_lancador_'.$uniqid);
        $data_lancamento->setId('data_lancamento_'.$uniqid);

        // set sizes
        $detail_id->setSize('60');
        $aluno_id->setSize('500');
        $nota->setSize('60');
        $status->setSize('220');
        //$usuario_lancador->setSize('120');
        //$data_lancamento->setSize('120');
        
        //Bloqueios
        $aluno_id->setEditable(FALSE);
        $detail_id->setEditable(FALSE);
        $nota->setEditable(FALSE);
        
        //valores
        $status->addItems($fer->lista_status_prova());
        
        //Mascara
        $nota->setMask('99.99');
        
        // set row counter
        $detail_id->{'data-row'}        = $this->detail_row;
        $aluno_id->{'data-row'}         = $this->detail_row;
        $nota->{'data-row'}             = $this->detail_row;
        $status->{'data-row'}           = $this->detail_row;
        $usuario_lancador->{'data-row'} = $this->detail_row;
        $data_lancamento->{'data-row'}  = $this->detail_row;

        // set value
        if (!empty($item->id)) { $detail_id->setValue( $item->id ); }
        if (!empty($item->aluno_id)) { $aluno_id->setValue( $item->aluno_id ); }
        if (!empty($item->nota)) { $nota->setValue( number_format($item->nota, 2, '.', '') ); }
        if (!empty($item->status)) 
        { 
            $status->setValue( $item->status );
            if ($item->status == 'P' || $tipo_prova != '1C') 
            {
                $status->setEditable(FALSE);
            } 
        }
        if (!empty($item->usuario_lancador)) { $usuario_lancador->setValue( $item->usuario_lancador ); }
        if (!empty($item->data_lancamento)) { $data_lancamento->setValue( $item->data_lancamento ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell($detail_id);
        $row->addCell($aluno_id);
        $row->addCell($nota);
        $row->addCell($status);
        $row->addCell($usuario_lancador);
        $row->addCell($data_lancamento);
        
        //$row->addCell( $del );
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($detail_id);
        $this->form->addField($aluno_id);
        $this->form->addField($nota);
        $this->form->addField($status);
        $this->form->addField($usuario_lancador);
        $this->form->addField($data_lancamento);
        
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
        $this->table_details->addRowSet(array($add));
    }
    
    /**
     * Save the avaliacao_prova and the avaliacao_aluno's
     */
    public static function onSave($param, $conclui = 'N')
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id             = (int) $param['id'];
            $master         = new avaliacao_prova($id);
            $concluida      = $master->status;
            $tipo_avaliacao = $master->avaliacao_turma->avaliacao_curso->tipo_avaliacao;
             
            //Conclui a avaliação de pendências
            if ($conclui == 'S')
            {
                $master->status = 'CO';//Muda o status para concluido
                $master->oculto = 'S';//Muda para finalizado
                $master->store(); // save master object
            }
            //Salva a notas dos alunos
            if( !empty($param['aluno_id']) AND is_array($param['aluno_id']) )
            {
                foreach( $param['aluno_id'] as $row => $aluno_id)
                {
                    if (!empty($aluno_id))
                    {
                        $detail                   = new avaliacao_aluno($param['detail_id'][$row]);
                        $detail->nota             = (empty($param['nota'][$row]))   ?  0  : $param['nota'][$row];
                        $detail->status           = (empty($param['status'][$row])) ? 'A' : $param['status'][$row];
                        $detail->usuario_lancador = TSession::getValue('login');//$param['usuario_lancador'][$row];
                        $detail->data_lancamento  = date('Y-m-d');//$param['data_lancamento'][$row];
                        //Zera automático se estava ausente
                        if ($detail->status != 'P')
                        {
                            $detail->nota         = 0;
                        }
                        //Se é 2ª Chamada, calcular a nota ponderando a justificativa 
                        if ($concluida != 'S' && $master->tipo_prova == '2C' && $conclui == 'S')
                        {
                            $detail->nota         = ($detail->nota <= 0) ? 0 : $detail->nota * $detail->fator_moderador;
                        }
                        $detail->store();
                    }
                }
            }
            //Atualiza o Form
            $fechamento = true;
            $recupera   = true;
            if ($conclui == 'N')
            {
                $data = new stdClass;
                $data->id = $master->id;
                TForm::sendData('form_avaliacao_prova', $data);
            }
            TTransaction::close(); // close the transaction
            //Se for para concluir
            if ($conclui == 'S')
            {
                //Monta provas de 2ªChamada e Recuperação
                $retorno    = self::onMontaProvas($master,$param);
                $provas     = $retorno['status'];
                $erro       = $retorno['erro'];
                //Se não há mais provas, encerra avaliação
                if ($provas === true)
                {
                    $retorno = self::onEncerra($master,$param);
                    //Se é uma VU ou VF, prepara a RF com os alunos que não recuperaram
                    if ($retorno === true && ($tipo_avaliacao == 'VF' || $tipo_avaliacao == 'VU'))
                    {
                        //echo 'Criando Recuperação...';
                        $recupera = self::onMontaRecuperacao($master,$param);
                        //Se não tem ninguem de recuperação, encerra a disciplina e cria o resultado
                        if ($recupera === false)
                        {
                            $fechamento = self::onEncerraDisciplina ($master,$param);
                        }
                    }
                    //Se a prova é do tipo RF, encerra a disciplina e cria o resultado
                    if ($tipo_avaliacao == 'RF')
                    {
                        $fechamento = self::onEncerraDisciplina ($master,$param);
                    }
                    
                }
            }
            $msg    = '';
            $action = null;
            if ($conclui == 'N')
            {
                $msg = TAdiantiCoreTranslator::translate('Record saved');
            }
            else
            {
                $action = new TAction(array('avaliacao_prova_pendenciasForm','onEdit'),array('key'=>$master->id));
                if ($provas === false)
                {
                    $msg = 'Avaliação de Pendências Concluso.<br>Novas provas devidamente criadas.<br>Clique em OK para recarregar.';
                }
                else if ($erro === true)
                {
                    $msg = 'Avaliação de Pendências Concluso.<br>Houve um erro ao criar 2ª Chamada e/ou Recuperação.<br>Clique em OK para recarregar.';
                }
                else if ($provas === true && $erro === false)
                {
                    $msg = 'Avaliação de Pendências Concluso.<br>';
                    if ($tipo_avaliacao == 'RF' || $tipo_avaliacao == 'VU' || $tipo_avaliacao == 'VF')
                    {
                        $msg .= 'Encerrando a Disciplina por não haver pedências como 2ª Chamada ou recuperação.';
                    }
                    else
                    {
                        $msg .= 'Encerrando a Avaliação por não haver 2ª Chamada ou recuperação.';   
                    }
                    $msg .= '<br>Clique em OK e AGUARDE para recarregar.';
                } 
            }

            new TMessage('info',$msg,$action);

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Finaliza a avaliação criando uma registro de resultado 
 *------------------------------------------------------------------------------*/
    public static function onEncerra($master,$param)
    {
        $avaliacao_turma_id = $master->avaliacao_turma_id;
        try
        {
            TTransaction::open('sisacad');
            //Pega dados para criar os resultados
            $prova          = new avaliacao_turma($avaliacao_turma_id);
            $prova->oculto  = 'S';
            $prova->store();
            $turma_id       = $prova->turma->id;//id da Turma
            $media_minima   = $prova->avaliacao_curso->media_minima;//Menor nota da disciplina
            $alunos         = aluno::where('turma_id','=',$turma_id)->load();//Matriz de alunos
            $tipo_avaliacao = $prova->avaliacao_curso->tipo_avaliacao;//Pegar o tipo de prova criada
            
            //Cria avaliacao_resultado
            $check                         = avaliacao_resultado::where('avaliacao_turma_id','=',$avaliacao_turma_id)->
                                             load();
            if (!empty($check))
            {
                $r     = avaliacao_resultadoaluno::where('avaliacao_resultado_id','=',$check[0]->id)->delete();
                $check                         = avaliacao_resultado::where('avaliacao_turma_id','=',$avaliacao_turma_id)->
                                                 delete();
                $check = false;
            }

            //Verifica se já teve uma totalização para a avaliação.
            if ($check === false || empty($check) )
            {
                $resultado                     = new avaliacao_resultado();
                $resultado->avaliacao_turma_id = $avaliacao_turma_id;
                $resultado->data_fim           = date('Y-m-d');
                $resultado->usuario_encerra    = TSession::getValue('login');
                $resultado->oculto             = 'N';
                $resultado->store();
                
                //Varre a matriz de alunos para gravar seus resultados
                foreach ($alunos as $aluno)
                {
                    //Pega as notas de cada aluno para compor a nota final
                    $sql = "(SELECT id FROM sisacad.avaliacao_prova WHERE avaliacao_turma_id =" . $avaliacao_turma_id .")";
                    $resultados = avaliacao_aluno::where('aluno_id','=',$aluno->id)->
                                                   where('avaliacao_prova_id','IN',$sql)->load();
                    //Default de notas e presença
                    $notas      = array('1C'=>0,'2C'=>0,'RC'=>0);
                    $status     = array('1C'=>'A','2C'=>'A','RC'=>'A');
                    $recuperado = 'N';//Passou por recuperação
                    //Controi as notas do aluno
                    foreach($resultados as $result)
                    {
                        $tp = $result->avaliacao_prova->tipo_prova;
                        $notas[$tp]  = $result->nota;
                        $status[$tp] = $result->status;
                        if ($status[$tp] != 'P')
                        {
                            $notas[$tp] = 0;
                        }
                    }
                    //Se não tive nenhuma nota, zera nota composta
                    if ($notas['1C'] == 0 && $notas['2C'] == 0 && $notas['RC'] == 0)
                    {
                        $nota_composta = 0;
                    }
                    else
                    {
                        //Se fez a 1ªChamada e teve Média
                        if ($status['1C'] == 'P' && $notas['1C'] >= $media_minima)
                        {
                            $nota_composta = $notas['1C'];
                        }
                        //Se fez a 2ªChamada e teve Média
                        if ($status['1C'] != 'P' && $status['2C'] == 'P' && $notas['2C'] >= $media_minima)
                        {
                            $nota_composta = $notas['2C'];
                        }
                        //Se na 1ª ou 2ª Chamada não teve Média, Verifica a recuperação
                        if ($notas['1C'] < $media_minima && $notas['2C'] < $media_minima)
                        {
                            $recuperado = 'S';
                            //Se fez a recuperação e teve média, pego a média mínima da disciplina
                            if ($status['RC'] == 'P' && $notas['RC'] >= $media_minima)
                            {
                                $nota_composta = $media_minima;
                            }
                            else //Caso contrário, pego a maior média que teve
                            {
                                $nota_composta = ($notas['1C'] > $notas['2C']) ? $notas['1C'] : $notas['2C'];
                                $nota_composta = ($nota_composta > $notas['RC']) ? $nota_composta : $notas['RC'];
                            }
                        }
                    }//Fim da composição da nota por avaliações
                    //Prepara o resultado do aluno para gravação 
                    $avaliado                         = new avaliacao_resultadoaluno();
                    $avaliado->avaliacao_resultado_id = $resultado->id;
                    $avaliado->aluno_id               = $aluno->id;
                    $avaliado->tipo_avaliacao         = $tipo_avaliacao;
                    $avaliado->nota                   = $nota_composta;
                    $avaliado->recuperado             = $recuperado;
                    $avaliado->store();
                }//Fim foreach ($alunos)
            }//Fim $check
            else
            {
                throw new Exception ('Uma totalização já foi criada.');
            }
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
            return false;
        }
        return true;
    }
/*------------------------------------------------------------------------------
 *   Questiona antes da conclusão
 *------------------------------------------------------------------------------*/
    public static function onConclui($param)
    {
        $action = new TAction(array('avaliacao_prova_pendenciasForm', 'Conclui'));
        //$action->setParameters($param); // pass the key parameter ahead
        TSession::setValue('avaliacao_prova_pendenciasForm_Conclui', $param);
        
        // shows a dialog to the user
        new TQuestion('Finaliza a Avaliação de Pendências? Ao finalizar irei verificar o seguinte:<br>' .
                      '- Se houve ausência e criar uma 2ª Chamada (se pertinente); <br>' .
                      '- Se houver média menor que o definido para disciplina já lançar o Aluno de Recuperação (também se pertinente)<br>'.
                      '- Se não houver nenhuam hipótese acima, encerro o processo criando a tabela de resultados.', $action);
    }
/*------------------------------------------------------------------------------
 *   Conclusão
 *------------------------------------------------------------------------------*/
    public static function Conclui($param = null)
    {
        $param = TSession::getValue('avaliacao_prova_pendenciasForm_Conclui');
        self::onSave($param, 'S');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Retorna a lista
 *------------------------------------------------------------------------------*/
    public function onReturn($param = null)
    {
        $data = $this->form->getData();
        $key = $data->avaliacao_turma_id;
        if ($key)
        {
            TApplication::gotoPage('avaliacao_turmaForm','onEdit',array('key'=>$key));
        }
        else
        {
            TApplication::loadPage('avaliacao_turmaList','onReload');
        }

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Monta as proximas avaliações (2ª Chamada e Recuperação)
 *------------------------------------------------------------------------------*/
    public static function onMontaProvas($master,$param)
    {
        //Pega parte inicial para abrir nova prova
        $prova = $master;
        $next  = true;//Retorno informando se foi ou não criado novas sequencias de provas
        try
        {
            TTransaction::open('sisacad');
            /*------------------------------------------------------------------
             * Monta 2ªChamada para ausentes e justificados (ver se os ausentes fazem direto a 2ªChamada
             *------------------------------------------------------------------*/
            $avaliacao           = new avaliacao_prova($prova->id);
            if ($avaliacao->tipo_prova == '1C' ) //Somente se cria 2ªchamada se a prova for de 1ªchamada
            {
                $alunos_ausentes = avaliacao_aluno::where('avaliacao_prova_id','=',$prova->id)->
                                                    where('status','!=','P')->
                                                    where('status','!=','A')->load();
                
                if (count($alunos_ausentes) > 0)//Verifica se há ausências Justificadas
                {
                    $next                        = false;//Registra nova fase de provas
                    //Cria uma avaliação com status de 2ª Chamada (2C)
                    $segunda                     = new avaliacao_prova();
                    $segunda->avaliacao_turma_id = $prova->avaliacao_turma_id;
                    $segunda->tipo_prova         = '2C'; //segunda Chamada
                    $segunda->dt_aplicacao       = '';
                    $segunda->oculto             = 'N';
                    $segunda->status             = 'AG';//Aguardar liberação
                    $segunda->usuario_liberador  = '';
                    $segunda->data_liberacao     = '';
                    
                    $segunda->store();
                    
                    foreach ($alunos_ausentes as $aluno_ausente)//Cria as provas para os alunos ausentes
                    {
                        $prova_aluno                     = new avaliacao_aluno();
                        $prova_aluno->avaliacao_prova_id = $segunda->id;
                        $prova_aluno->nota               = 0;
                        $prova_aluno->aluno_id           = $aluno_ausente->aluno_id;
                        $prova_aluno->status             = 'P';
                        $prova_aluno->usuario_lancador   = '';
                        $prova_aluno->data_lancamento    = '';
                        $prova_aluno->fator_moderador    = ($aluno_ausente->status == 'J') ? 1 : 0.9;
                        
                        $prova_aluno->store();
                    }
                }
            }
            /*------------------------------------------------------------------
             * Monta Recuperação
             *------------------------------------------------------------------*/
            $media_minima = $avaliacao->avaliacao_turma->avaliacao_curso->media_minima;
            if (!isset($media_minima) || empty($media_minima))
            {
                $media_minima = 5.00;
            } 
            //Cria matriz de alunos
            //1ª Chamada, quem fez a prova e não teve média faz RC
            if ($avaliacao->tipo_prova == '1C')
            {
                $sql          = "(SELECT id FROM sisacad.avaliacao_aluno WHERE avaliacao_prova_id = " . $prova->id . 
                       " AND nota < " . $media_minima . " AND (status = 'P' OR status = 'A'))";
                echo $sql;
                $alunos_media = avaliacao_aluno::where('id','IN',$sql)->load();
                /*$alunos_media = avaliacao_aluno::where('avaliacao_prova_id','=',$prova->id)->
                                                    where('nota','<',$media_minima)->
                                                    orWhere('status','=','P')->
                                                    orWhere('status','=','A')->load();*/
               
            }
            else if ($avaliacao->tipo_prova == '2C')//2ª Chamada todos sem média faz RC 
            {
                $alunos_media = avaliacao_aluno::where('avaliacao_prova_id','=',$prova->id)->
                                                    where('nota','<',$media_minima)->load();
            }
            //Pegao o tipo de Avaliação (VU,V1,V2,V3 ou RF)
            $tipo      = $avaliacao->avaliacao_turma->avaliacao_curso->tipo_avaliacao;
            //echo 'Média ->'.$media_minima.'<br>';echo 'Tipo Prova' . $tipo.'<br>';
            //Não há prova de recuperação para Verificações do tipo única/final e Recuperação
            if (isset($alunos_media) && count($alunos_media) > 0 && ($tipo != 'VU' && $tipo != 'VF' && $tipo != 'RF'))
            {
                $next       = false;//Novo ciclo de provas
                //echo "Dados dos Alunos <br>";var_dump($alunos_media);
                //Se não é a 1ª Chamada pode haver uma recuperação já criada.
                $id         = false;//Registra se a prova foi ou não criada
                if ($avaliacao->tipo_prova == '1C')
                {
                    //É 1ª Chamada, cria prova de recuperação
                    $recupera   = new avaliacao_prova();
                }
                else
                {
                    //Verifica se já tem Prova de Recuperação criada
                    $ver_provas = avaliacao_prova::where('tipo_prova','=','RC')->
                                                   where('avaliacao_turma_id','=',$avaliacao->avaliacao_turma_id)->
                                                   load();
                    if ($ver_provas)
                    {
                        //Varre para pegar a Id da Recuperação
                        foreach ($ver_provas as $ver_prova)
                        {
                            $id = $ver_prova->id;
                        }
                    }
                    //Verifica se o $id da prova de recuperação existe
                    if (empty($id))
                    {
                        //Se não achar a Id, Cria uma nova
                        $recupera = new avaliacao_prova();
                        $id = false;//Marca id como false para criar a prova de recuperação
                    }
                    else
                    {
                        //Se achou, prepara o Update
                        $recupera = new avaliacao_prova($id);
                    }
                }
                //Cria prova se não existe
                if ($id == false)
                {
                    //Monta o cabeçalho da prova de recuperação
                    $recupera->avaliacao_turma_id = $prova->avaliacao_turma_id;
                    $recupera->tipo_prova         = 'RC'; //Recuperação
                    $recupera->dt_aplicacao       = '';
                    $recupera->oculto             = 'N';
                    $recupera->status             = 'AG';//Aguardando
                    $recupera->usuario_liberador  = '';
                    $recupera->data_liberacao     = '';
                    $recupera->store();
                }
                //Adiciona alunos para a prova
                foreach ($alunos_media as $aluno_media)
                {
                    $prova_aluno                     = new avaliacao_aluno();
                    $prova_aluno->avaliacao_prova_id = $recupera->id;
                    $prova_aluno->nota               = 0;
                    $prova_aluno->aluno_id           = $aluno_media->aluno_id;
                    $prova_aluno->status             = 'P';
                    $prova_aluno->usuario_lancador   = '';
                    $prova_aluno->data_lancamento    = '';
                    
                    $prova_aluno->store();
                }
            }
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            TTransaction::rollback();
            return array('status'=>$next,'erro'=>true);
        }
        return array('status'=>$next,'erro'=>false);

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Monta a Recuperação Final
 *------------------------------------------------------------------------------*/
    public static function onMontaRecuperacao($master,$param)
    {
        //Pega parte inicial para abrir nova prova
        $prova = $master;
        //var_dump($master);return false;
        $next  = true;//Retorno informando se foi ou não criado prova de Recuperação Final (true == tem recuperação)
        try
        {
            TTransaction::open('sisacad');
            TTransaction::setLogger(new TLoggerTXT('tmp/montarecupera.txt')); 
            TTransaction::log("Inserindo Recuperação");  
            //Busca informação das provas
            $media_minima       = $master->avaliacao_turma->avaliacao_curso->media_minima;//Média Mínima da avaliação
            $avaliacao_turma_id = $master->avaliacao_turma_id;//Vínculo de Avaliação turma trazida da avaliação
            $disciplina_id      = $master->avaliacao_turma->avaliacao_curso->materias_previstas_id;//Id da Disciplina
            $materia_id         = $master->avaliacao_turma->materia_id;//Id da Matéria
            $curso_id           = $master->avaliacao_turma->avaliacao_curso->curso_id;//Curso Vinculado
            $sql1               = "(SELECT DISTINCT id FROM sisacad.avaliacao_curso WHERE curso_id = " . $curso_id .
                                  " AND materias_previstas_id = " . $disciplina_id . " AND tipo_avaliacao = 'RF')";
            $sql2               = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE avaliacao_curso_id IN " .  $sql1 . 
                                  " AND materia_id = ". $materia_id . ")";
            //Busca descobrir qual é o id da avaliação de Recuperação final para a turma
            TTransaction::log(" Busca a Recuperação Final ->".$sql2);
            $avaliacao_turmas   = avaliacao_turma::where('id','IN',$sql2)->load();
            //Se houve retorno pega o primeiro
            if (!empty($avaliacao_turmas))
            {
                $avaliacao_turma = $avaliacao_turmas[0];
            }
            else
            {
                $msg = 'Não achei o Index de Recuperação. Talvez não tenha sido criado.';
                TTransaction::log(" ERRO " . $msg);
                throw new Exception ($msg);
            }
            $sql1 = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE materia_id = " . $materia_id . ")";//Preciso dos resultados para fechar a nota....
            TTransaction::log("Busca os Resultados => ".$sql1);
            $resultados = avaliacao_resultado::where('avaliacao_turma_id','IN',$sql1)->load();
            //Array padrão de notas de avaliação
            $notas = array('V1'=>false,'V2'=>false,'VF'=>false,'FIM'=>0);
            //Id da turma
            $turma_id = $master->avaliacao_turma->turma->id;
            //Carrega Alunos vinculados à turmas
            $alunos = aluno::where('turma_id','=',$turma_id)->load();
            if (!empty($alunos))
            {
                //Cria o boletim de notas dos alunos da turma
                foreach ($alunos as $aluno)
                {
                    $boletim [$aluno->id] = $notas;
                }
                //Preenche o boletim de notas dos alunos
                foreach ($resultados as $resultado)
                {
                    $resultado_notas = $resultado->avaliacao_resultadoalunos;
                    foreach ($resultado_notas as $r)
                    {
                        if ($r->tipo_avaliacao == 'VU')
                        {
                            $boletim[$r->aluno_id]['VF'] = $r->nota;
                        }
                        else
                        {
                            $boletim[$r->aluno_id][$r->tipo_avaliacao] = $r->nota;
                        } 
                    }
                }
                //Lista onde será armazenados os alunos de recuperação
                $lista = array();
                //Rotina para conferir as médias obtidas
                foreach ($alunos as $aluno)
                {
                    if ($boletim[$aluno->id]['V1'] === false)
                    {
                        //Não tem V1, logo foi feito só VF 
                        $boletim[$aluno->id]['FIM'] = $boletim[$aluno->id]['VF'];
                        echo "Nota Final = VU = " . $boletim[$aluno->id]['FIM'];
                    }
                    else
                    {
                        //Teve duas verificações (V1 == true e VF == true)
                        if ($boletim[$aluno->id]['V2'] === false && 
                            $boletim[$aluno->id]['VF'] !== false && 
                            $boletim[$aluno->id]['V1'] !== false)
                        {
                            $boletim[$aluno->id]['FIM'] = ($boletim[$aluno->id]['V1'] + (2 * $boletim[$aluno->id]['VF'])) / 3;
                            echo "Nota Final = (V1 + (2 * VF)) / 3 = " . $boletim[$aluno->id]['FIM'];
                        }
                        //forma 3 verificações (V1, V2 e VF)
                        else if ($boletim[$aluno->id]['V2'] !== false && 
                                 $boletim[$aluno->id]['VF'] !== false && 
                                 $boletim[$aluno->id]['V1'] !== false)
                        {
                            $boletim[$aluno->id]['FIM'] = ($boletim[$aluno->id]['V1'] + $boletim[$aluno->id]['V1'] +
                                                            (2 * $boletim[$aluno->id]['VF'])) / 4;
                            echo "Nota Final = (V1 + V2 + (2 * VF)) / 3 = " . $boletim[$aluno->id]['FIM'];
                        }
                    }
                    if ($boletim[$aluno->id]['FIM'] < $media_minima)
                    {
                        $lista[] = $aluno->id;
                    }
                }
                //Verifica se há alguém de recuperação final 
                if (count($lista) == 0)
                {
                    $next = false;
                }
                else
                {
                    //Cria a prova de recuperação
                    $segunda                     = new avaliacao_prova();
                    $segunda->avaliacao_turma_id = $avaliacao_turma->id;
                    $segunda->tipo_prova         = '1C'; //1ª Chamada da RF
                    $segunda->dt_aplicacao       = '';
                    $segunda->oculto             = 'N';
                    $segunda->status             = 'AG';//Aguardar liberação
                    $segunda->usuario_liberador  = '';
                    $segunda->data_liberacao     = '';
                    
                    $segunda->store();
                    
                    $avaliacao_turma->oculto = 'N';//Habilita a prova de recuperação para turma.
                    $avaliacao_turma->store();
                    
                    //var_dump($segunda);
                    foreach($lista as $l)
                    {
                            //var_dump($l);
                            //Inscreve os alunos que não atingiram média na prova
                            $prova_aluno                     = new avaliacao_aluno();
                            $prova_aluno->avaliacao_prova_id = $segunda->id;
                            $prova_aluno->nota               = 0;
                            $prova_aluno->aluno_id           = $l;
                            $prova_aluno->status             = 'P';
                            $prova_aluno->usuario_lancador   = '';
                            $prova_aluno->data_lancamento    = '';
                            $prova_aluno->fator_moderador    = 1;
                            
                            $prova_aluno->store();
                    }
                }
            }
            else
            {
                TTransaction::log('Sem alunos');
            }

            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            TTransaction::rollback();
            //return array('status'=>$next,'erro'=>true);
        }
        return $next;
    }//Fim Módulo

/*------------------------------------------------------------------------------
 *   
 *------------------------------------------------------------------------------*/
    public static function onEncerraDisciplina($master,$param)
    {
        //Pega parte inicial para abrir nova prova
        $prova = $master;
        //var_dump($master);return false;
        $next  = true;//Retorno informando se foi ou não criado prova de Recuperação Final (true == tem recuperação)
        try
        {
            TTransaction::open('sisacad');
            TTransaction::setLogger(new TLoggerTXT('tmp/encerradisciplina.txt')); 
            TTransaction::log("Finalizando tudo");  
            //Busca informação das provas
            $media_minima       = $master->avaliacao_turma->avaliacao_curso->media_minima;//Média Mínima da avaliação
            $avaliacao_turma_id = $master->avaliacao_turma_id;//Vínculo de Avaliação turma trazida da avaliação
            $disciplina_id      = $master->avaliacao_turma->avaliacao_curso->materias_previstas_id;//Id da Disciplina
            $materia_id         = $master->avaliacao_turma->materia_id;//Id da Matéria. Deve ser encerrada
            $curso_id           = $master->avaliacao_turma->avaliacao_curso->curso_id;//Curso Vinculado
            
            /*$sql1               = "(SELECT DISTINCT id FROM sisacad.avaliacao_curso WHERE curso_id = " . $curso_id .
                                  " AND materias_previstas_id = " . $disciplina_id . " AND tipo_avaliacao = 'RF')";
            $sql2               = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE avaliacao_curso_id IN " .  $sql1 . 
                                  " AND materia_id = ". $materia_id . ")";
            //Busca descobrir qual é o id da avaliação de Recuperação final para a turma
            TTransaction::log(" Busca a Recuperação Final ->".$sql2);
            $avaliacao_turmas   = avaliacao_turma::where('id','IN',$sql2)->load();
            //Se houve retorno pega o primeiro
            if (!empty($avaliacao_turmas))
            {
                $avaliacao_turma = $avaliacao_turmas[0];
            }
            else
            {
                $msg = 'Não achei o Index de Recuperação. Talvez não tenha sido criado.';
                TTransaction::log(" ERRO " . $msg);
                throw new Exception ($msg);
            }*/
            
            $sql1 = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE materia_id = " . $materia_id . ")";//Preciso dos resultados para fechar a nota....
            TTransaction::log("Busca os Resultados => ".$sql1);
            $resultados = avaliacao_resultado::where('avaliacao_turma_id','IN',$sql1)->load();
            //Array padrão de notas de avaliação com a recuperação final
            $notas = array('V1'=>false,'V2'=>false,'VF'=>false,'RF'=>false,'FIM'=>0);
            //Id da turma
            $turma_id = $master->avaliacao_turma->turma->id;
            //Carrega Alunos vinculados à turmas
            $alunos = aluno::where('turma_id','=',$turma_id)->load();
            if (!empty($alunos))
            {
                /*--------------------------------------------------------------
                 *
                 * Criar o Resultado Final da prova
                 *
                 *--------------------------------------------------------------*/
                //Verifica se já houve uma finalização da materia
                $g_provas = avaliacao_final::where ('materia_id','=',$materia_id)->load();
                if (count($g_provas) > 0)
                {
                    //Se sim, pega o id do fechamento e atualiza
                    $p_final = $g_provas[0];
                    //Atualiza o encerramento
                    $p_final->data_fim        = date('Y-m-d');
                    $p_final->usuario_encerra = TSession::getValue('login');
                    $p_final->materia_id      = $materia_id;
                    $p_final->oculto          = 'S';
                    $p_final->store();
                    //Limpa o resultado dos alunos
                    $obj = avaliacao_finalaluno::where('avaliacao_final_id','=',$p_final->id)->delete();
                    
                }
                else
                {
                    //Se não, cria uma prova de fechamento
                    $p_final = new avaliacao_final();
                    $p_final->data_fim        = date('Y-m-d');
                    $p_final->usuario_encerra = TSession::getValue('login');
                    $p_final->materia_id      = $materia_id;
                    $p_final->oculto          = 'S';
                    $p_final->store();
                }
                
                //Cria o boletim de notas dos alunos da turma
                foreach ($alunos as $aluno)
                {
                    $boletim [$aluno->id] = $notas;
                }
                //Preenche o boletim de notas dos alunos
                foreach ($resultados as $resultado)
                {
                    $resultado_notas = $resultado->avaliacao_resultadoalunos;
                    foreach ($resultado_notas as $r)
                    {
                        if ($r->tipo_avaliacao == 'VU')
                        {
                            $boletim[$r->aluno_id]['VF']         = $r->nota;
                        }
                        else
                        {
                            $boletim[$r->aluno_id][$r->tipo_avaliacao] = $r->nota;
                        }
                        $boletim[$r->aluno_id]['recuperado'] = $r->recuperado;
                    }
                }
                //Lista onde será armazenados os alunos de recuperação
                $lista = array();
                //Rotina para montar a média dos alunos já contando a RF
                foreach ($alunos as $aluno)
                {
                    if ($boletim[$aluno->id]['V1'] === false)
                    {
                        //Não tem V1, logo foi feito só VF

                        $boletim[$aluno->id]['FIM'] = $boletim[$aluno->id]['VF'];
                        echo "Nota Final = VU = " . $boletim[$aluno->id]['FIM'];
                    }
                    else
                    {
                        //Teve duas verificações (V1 == true e VF == true)
                        if ($boletim[$aluno->id]['V2'] === false && 
                            $boletim[$aluno->id]['VF'] !== false && 
                            $boletim[$aluno->id]['V1'] !== false)
                        {
                            $boletim[$aluno->id]['FIM'] = ($boletim[$aluno->id]['V1'] + (2 * $boletim[$aluno->id]['VF'])) / 3;
                            echo "Nota Final = (V1 + (2 * VF)) / 3 = " . $boletim[$aluno->id]['FIM'];
                        }
                        //forma 3 verificações (V1, V2 e VF)
                        else if ($boletim[$aluno->id]['V2'] !== false && 
                                 $boletim[$aluno->id]['VF'] !== false && 
                                 $boletim[$aluno->id]['V1'] !== false)
                        {
                            $boletim[$aluno->id]['FIM'] = ($boletim[$aluno->id]['V1'] + $boletim[$aluno->id]['V1'] +
                                                            (2 * $boletim[$aluno->id]['VF'])) / 4;
                            echo "Nota Final = (V1 + V2 + (2 * VF)) / 3 = " . $boletim[$aluno->id]['FIM'];
                        }
                    }
                    //Verifica se o mesmo fez RF e se a média o salva.
                    $recuperado = 'N';
                    if ($boletim[$aluno->id]['FIM'] < $media_minima)
                    {
                        if (isset($boletim[$aluno->id]['RF']))
                        {
                            if ($boletim[$aluno->id]['RF'] > $media_minima)
                            {
                                //Se o mesmo passou pela Recuperação a nota é no máximo a média mínima
                                $boletim[$aluno->id]['FIM'] = $media_minima;
                            }
                            else
                            {
                                //Mesmo não recuperando, fica com o nota maior entre o Resultado e o RF
                                $boletim[$aluno->id]['FIM'] = ($boletim[$aluno->id]['FIM'] > $boletim[$aluno->id]['RF'])
                                                               ? $boletim[$aluno->id]['FIM'] : $boletim[$aluno->id]['RF'];
                            }
                        }
                        $recuperado = 'S';
                    }
                    $aprovado = ($boletim[$aluno->id]['FIM'] >= $media_minima) ? $aprovado = 'S' :$aprovado = 'N';
                    //Gravar a nota do aluno no boletim da disciplina
                    $nota_aluno                     = new avaliacao_finalaluno();
                    $nota_aluno->recuperado         = $recuperado;
                    $nota_aluno->avaliacao_final_id = $p_final->id;
                    $nota_aluno->nota               = $boletim[$aluno->id]['FIM'];
                    $nota_aluno->aluno_id           = $aluno->id;
                    $nota_aluno->aprovado           = $aprovado;
                    $nota_aluno->store();
                    
                }
                //Fehca a Matéria da turma
                $materia          = new materia ($materia_id);
                $materia->oculto  = 'S';
                $materia->store();

            }
            else
            {
                TTransaction::log('Sem alunos');
            }

            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            TTransaction::rollback();
            //return array('status'=>$next,'erro'=>true);
        }
        return $next;
    }//Fim Módulo

}//Fim Classe
