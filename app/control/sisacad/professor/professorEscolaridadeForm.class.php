<?php
/**
 * professorEscolaridadeForm Master/Detail
 * @author  <your name here>
 */
class professorEscolaridadeForm extends TPage
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
    var $servico  = 'Professor';            //Nome da página de serviço.
    
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
        $this->form = new TForm('form_professor');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $scroll_master = new TScroll;
        $scroll_master->setSize('100%',180);
        
        $table_master->addRowSet( new TLabel('Cadastro de Titularidades para Professores'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados Básicos do Professor');
        $frame_general->style = 'background:whiteSmoke';

        $scroll_master->add($table_general);
        $frame_general->add($scroll_master);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Dados Básicos da Titularidade');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $cpf = new TEntry('cpf');

        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        $orgao_origem        = new TDBCombo('orgaosorigem_id','sisacad','orgaosorigem','id','sigla','sigla',$criteria);

        $postograd_id        = new TDBCombo('postograd_id','sicad','postograd','id','nome','nome');
        $quadro              = new TCombo('quadro');
        $sexo                = new TCombo('sexo');
        $rg                  = new TEntry('rg');
        $orgao_expeditor     = new TEntry('orgao_expeditor');
        $uf_expeditor        = new TDBCombo('uf_expeditor','sicad','estados','sigla','sigla','sigla');
        $telefone            = new TEntry('telefone');
        $celular             = new TEntry('celular');
        $email               = new TEntry('email');
        $data_nascimento     = new TDate('data_nascimento');

        //Monta ComboBox com OPMs que o Operador pode ver
        //echo $this->nivel_sistema.'---'.$this->opm_operador;
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
        $opm_id           = new TDBCombo('opm_id','sicad','OPM','id','sigla','sigla',$criteria);
        
        //Valores
        $quadro->addItems($sicad->caracteristicas_SICAD('quadro_alfa'));
        $uf_expeditor->setValue('GO');
        $sexo->addItems($fer->lista_sexo());
        
        //Mascaras
        $rg->setMask('99999999999');
        $cpf->setMask('99999999999');
        $telefone->setMask('(99)999999999');
        $celular->setMask('(99)999999999');
        $data_nascimento->setMask('dd/mm/yyyy');

        // sizes
        $id->setSize('50');
        $nome->setSize('350');
        $cpf->setSize('120');
        $orgao_origem->setSize('120');
        $postograd_id->setSize('300');
        $quadro->setSize('80');
        $sexo->setSize('100');
        $rg->setSize('80');
        $orgao_expeditor->setSize('200');
        $uf_expeditor->setSize('80');
        $telefone->setSize('100');
        $celular->setSize('100');
        $email->setSize('200');
        $data_nascimento->setSize('80');
        $opm_id->setSize('100');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        //Validação
        $nome->addValidation('Nome',new TRequiredValidator);
        $cpf->addValidation('CPF',new TCPFValidator);
        $orgao_origem->addValidation('Órgão de Origem',new TRequiredValidator);
        $postograd_id->addValidation('Cargo',new TRequiredValidator);
        $opm_id->addValidation('OPM Educacional de Vínculo',new TRequiredValidator);
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($cpf);
        $this->form->addField($orgao_origem);
        $this->form->addField($postograd_id);
        $this->form->addField($quadro);
        $this->form->addField($sexo);
        $this->form->addField($orgao_expeditor);
        $this->form->addField($rg);
        $this->form->addField($uf_expeditor);
        $this->form->addField($telefone);
        $this->form->addField($celular);
        $this->form->addField($email);
        $this->form->addField($data_nascimento);
        $this->form->addField($opm_id);
        
        // add form fields to the screen
        $table_general->addRowSet( array(new TLabel('Id'), $id,$lbl = new TLabel('Nome'), $nome, 
                                    new TLabel('D.N.'), $data_nascimento ));
        $lbl->setFontColor('red');
        $table_general->addRowSet( array($lbl = new TLabel('CPF'), $cpf, $lbl2 = new TLabel('Órgao Vinculo'), 
                                         $orgao_origem, $lbl3 = new TLabel('OPM Educacional'),$opm_id ));
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $lbl3->setFontColor('red');
        $table_general->addRowSet( array($lbl = new TLabel('Cargo'), 
                                   $postograd_id,new TLabel('Quadro'), $quadro, new TLabel('Sexo'), $sexo  ));
        $lbl->setFontColor('red');
        $table_general->addRowSet( array(new TLabel('RG'), $rg,new TLabel('Órgão Expeditor'), 
                                         $orgao_expeditor, new TLabel('UF'), $uf_expeditor ));
        $table_general->addRowSet( array(new TLabel('Telefone'), $telefone,new TLabel('Celular'), $celular
                                        ,new TLabel('Email'),$email));
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('Opções') );        
        $row->addCell( $lbl = new TLabel('Tipo') );
        $lbl->setFontColor('red');
        $row->addCell( new TLabel('Graduação') );
        $row->addCell( new TLabel('Instituição') );
        //$row->addCell( new TLabel('UF') );
        //$row->addCell( new TLabel('País') );
        //$row->addCell( new TLabel('Concluso?') );
        //$row->addCell( new TLabel('Data da Conclusão') );
        $row->addCell( $lbl = new TLabel('Comprovado?') );
        $lbl->setFontColor('red');
        $row->addCell( $lbl = new TLabel('Data da Apresentação') );
        $lbl->setFontColor('red');
        $row->addCell( new TLabel('Certificado Arquivado no(a)') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');
       
        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array('professorList', 'onReload')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($return_button);
        $table_master->addRowSet( array($save_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        
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
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new professor($key);
                $object->data_nascimento = TDate::date2br($object->data_nascimento);
                $this->form->setData($object);
                
                $items  = escolaridade::where('professor_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $item->data_apresentacao = TDate::date2br($item->data_apresentacao);
                        $this->addDetailRow($item);
                    }
                    
                    // create add button
                    $add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet(array($add));
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
        $fer = new TFerramentas();
        // create fields
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','=','N'));

        $titularidade_id     = new TDBCombo('titularidade_id[]','sisacad','titularidade','id','nome','nivel',$criteria);
        $nome_graduacao      = new TEntry('nome_graduacao[]');
        $instituicao         = new TEntry('instituicao[]');
        $uf                  = new THidden('uf[]');
        $pais                = new THidden('pais[]');
        $status              = new THidden('status[]');
        $data_conclusao      = new THidden('data_conclusao[]');
        $comprovado          = new TCombo('comprovado[]');
        $data_apresentacao   = new TDate('data_apresentacao[]');
        $arquivo_certificado = new TEntry('arquivo_certificado[]');

        //Valores
        $comprovado->addItems($fer->lista_sim_nao());
        $comprovado->setValue('S');
        // set id's
        $titularidade_id->setId('titularidade_id_'.$uniqid);
        $nome_graduacao->setId('nome_graduacao_'.$uniqid);
        $instituicao->setId('instituicao_'.$uniqid);
        $uf->setId('uf_'.$uniqid);
        $pais->setId('pais_'.$uniqid);
        $status->setId('status_'.$uniqid);
        $data_conclusao->setId('data_conclusao_'.$uniqid);
        $comprovado->setId('comprovado_'.$uniqid);
        $data_apresentacao->setId('data_apresentacao_'.$uniqid);
        $arquivo_certificado->setId('arquivo_certificado_'.$uniqid);

        // set sizes
        $titularidade_id->setSize('120');
        $nome_graduacao->setSize('100');
        $instituicao->setSize('100');
        /*$uf->setSize('40');
        $pais->setSize('100');
        $status->setSize('50');
        $data_conclusao->setSize('100');*/
        $comprovado->setSize('50');
        $data_apresentacao->setSize('80');
        $arquivo_certificado->setSize('140');
        
        //Mascaras
        $data_apresentacao->setMask('dd-mm-yyyy');
        
        // set row counter
        $titularidade_id->{'data-row'} = $this->detail_row;
        $nome_graduacao->{'data-row'} = $this->detail_row;
        $instituicao->{'data-row'} = $this->detail_row;
        $uf->{'data-row'} = $this->detail_row;
        $pais->{'data-row'} = $this->detail_row;
        $status->{'data-row'} = $this->detail_row;
        $data_conclusao->{'data-row'} = $this->detail_row;
        $comprovado->{'data-row'} = $this->detail_row;
        $data_apresentacao->{'data-row'} = $this->detail_row;
        $arquivo_certificado->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->titularidade_id)) { $titularidade_id->setValue( $item->titularidade_id ); }
        if (!empty($item->nome_graduacao)) { $nome_graduacao->setValue( $item->nome_graduacao ); }
        if (!empty($item->instituicao)) { $instituicao->setValue( $item->instituicao ); }
        if (!empty($item->uf)) { $uf->setValue( $item->uf ); }
        if (!empty($item->pais)) { $pais->setValue( $item->pais ); }
        if (!empty($item->status)) { $status->setValue( $item->status ); }
        if (!empty($item->data_conclusao)) { $data_conclusao->setValue( $item->data_conclusao ); }
        if (!empty($item->comprovado)) { $comprovado->setValue( $item->comprovado ); }
        if (!empty($item->data_apresentacao)) { $data_apresentacao->setValue( $item->data_apresentacao ); }
        if (!empty($item->arquivo_certificado)) { $arquivo_certificado->setValue( $item->arquivo_certificado ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($titularidade_id);
        $row->addCell($nome_graduacao);
        $row->addCell($instituicao);
        $row->addCell($comprovado);
        $row->addCell($data_apresentacao);
        $row->addCell($arquivo_certificado);
        $row->addCell($uf);
        $row->addCell($pais);
        $row->addCell($status);
        $row->addCell($data_conclusao);

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($titularidade_id);
        $this->form->addField($nome_graduacao);
        $this->form->addField($instituicao);
        $this->form->addField($comprovado);
        $this->form->addField($data_apresentacao);
        $this->form->addField($arquivo_certificado);
        $this->form->addField($uf);
        $this->form->addField($pais);
        $this->form->addField($status);
        $this->form->addField($data_conclusao);
        
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
     * Save the professor and the escolaridade's
     */
    public static function onSave($param)
    {
        
        $verifica      = self::verificaMaster($param);
        $fer           = new TFerramentas;
        $sicad         = new TSicadDados;
        $profile       = TSession::getValue('profile');           //Profile da Conta do usuário
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
        $opm_operador  = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        $nivel_sistema = $fer->getnivel ('professorEscolaridadeForm');//Verifica qual nível de acesso do usuário
        $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
        $config        = $fer->getConfig('SISACAD');         //Busca o Nível de acesso que o usuário tem para a Classe
        if ($verifica['status'] == true)
        {
            try
            {
                TTransaction::open('sisacad');
                $id = (int) $param['id'];
                $master = new professor;
                $master->fromArray( $param);
                $master->nome = mb_strtoupper($master->nome,'UTF-8');
                $master->orgao_expeditor = mb_strtoupper($master->orgao_expeditor,'UTF-8');
                $master->data_nascimento = TDate::date2us($master->data_nascimento);
                $master->store(); // save master object
                
                // delete details
                escolaridade::where('professor_id', '=', $master->id)->delete();
                
                if( !empty($param['titularidade_id']) AND is_array($param['titularidade_id']) )
                {
                    $erro_item = 0;
                    foreach( $param['titularidade_id'] as $row => $titularidade_id)
                    {
                        if (self::verificaItems($param,$row))
                        {
                            $detail = new escolaridade;
                            $detail->professor_id = $master->id;
                            $detail->titularidade_id = $param['titularidade_id'][$row];
                            $detail->nome_graduacao = $param['nome_graduacao'][$row];
                            $detail->instituicao = $param['instituicao'][$row];
                            $detail->uf = $param['uf'][$row];
                            $detail->pais = $param['pais'][$row];
                            $detail->status = $param['status'][$row];
                            $detail->data_conclusao = $param['data_conclusao'][$row];
                            $detail->comprovado = $param['comprovado'][$row];
                            $detail->data_apresentacao = TDate::date2us($param['data_apresentacao'][$row]);
                            $detail->arquivo_certificado = $param['arquivo_certificado'][$row];
                            $detail->store();
                        }
                        else
                        {
                            $erro_item ++;
                        }
                    }
                }
                if ($erro_item == 0)
                {
                    if (self::noAula (array('key'=>$master->id))== false )
                    {
                        new TMessage('info','Registro salvo com sucesso.');
                    }
                    else
                    {
                        TSession::setValue('gravei_chamado','N');
                        if ($nivel_sistema >80)
                        {
                            $dados = array('key'=>$master->id,'notifica'=>'N','recalculo'=>'S');
                        }
                        else
                        {
                            $dados = array('key'=>$master->id,'notifica'=>'S','recalculo'=>'N');
                        }
                        
                        $param = $dados;
                        //var_dump($param);
                        $ma        = new TMantis;
                        $notifica  = ($param['notifica'] == 'S') ? true : false;
                 
                        $profile   = TSession::getValue('profile');
                        $servidor  = $ma->FindServidor($profile['login']);
                        $professor = $ma->FindServidor($param['key'],true);
                        if ($professor)
                        {
                            $texto = 'O docente ' . $professor->nome . ' CPF ' . $professor->cpf . ' teve acrescido um título que '.
                                     'pode ter alterado os valores de aulas que tinha em aberto.';
                        }
                        else
                        {
                            $texto = 'Houve alterações na titularidade do professor que pode alterar os valores da aulas em aberto.';
                        }
                        $sistema    = $ma->FindSistema('SISACAD');
                        $parameters = json_encode(
                                      array ('key'=>$master->id,
                                             'class_to'=>'recalculo_aulaForm',
                                             'method_to'=>'onEdit'));
                        
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
                        if ($notifica == true && TSession::getValue('gravei_chamado')=='N')//Cria Notificação no Sistema
                        {
                            SystemNotification::register( 
                                3, 
                                'Correção de Valores de Aulas do Professor '.$professor->nome, 
                                'Aperte o botão para ir para a tela de recalculo.', 
                                'class=recalculo_aulaForm&method=onEdit&key=' . $param['key'] . '&chamado=' . $ret, 
                                'Correção', 
                                'fa fa-pencil-square-o blu',$sistema->id,80);
                        }
                        TSession::setValue('gravei_chamado','Y');
                        if ($nivel_sistema >80)
                        {
                            $action = new TAction(array('recalculo_aulaForm','onEdit'));
                            $action->setParameters (array('key'=>$param['key'],'chamado'=>$ret));
                            new TMessage ('info','Alteração gravada com Sucesso.<br>Irei para tela de Recalculo onde '.
                                                 'poderá recalcular as aulas que o professor tem em aberto',$action);
                        }
                        else
                        {
                            new TMessage ('info','Alteração gravada com Sucesso.<br>Notifiquei a Administração uma vez que '.
                                                 'o professor tem aulas em aberto.');
                        }

                    }
                }
                else
                {
                    new TMessage('erro', $erro_item.' titulo(s) estavam faltando dados como o tipo, se é Comprovado e a data de'.
                                                    ' apresentação<br>Por favor corriga e salve novamente.');
                }
                $data = new stdClass;
                $data->id = $master->id;
                TForm::sendData('form_professor', $data);
                TTransaction::close(); // close the transaction
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage().'o Erro esta nesta rotina');
                TTransaction::rollback();
            }
        }
        else
        {
            new TMessage('error',$verifica['erros']);
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica o Professor
 *------------------------------------------------------------------------------*/
    public static function verificaMaster ($param)
    {
        $status = false;
        $fer = new TFerramentas();
        if ((isset($param['nome']) && $param['nome']) &&
            (isset($param['cpf']) && $param['cpf'] && $fer->isValidCPF($param['cpf'])) &&
            (isset($param['orgaosorigem_id']) && $param['orgaosorigem_id']) &&
            (isset($param['opm_id']) && $param['opm_id']) &&
            (isset($param['postograd_id']) && $param['postograd_id'])) // validate required field
        {
            $status = true;$lista = '';
        }
        else
        {
            $lista = '';
            if (!(isset($param['nome']) && $param['nome'])) $lista .= 'Nome é necessário.<br>';
            if (!(isset($param['cpf']) && $param['cpf'] && $fer->isValidCPF($param['cpf']))) $lista .= 'CPF Válido é necessário.<br>';
            if (!(isset($param['orgaosorigem_id']) && $param['orgaosorigem_id'])) $lista .= 'Órgão de Origem  é necessário.<br>';
            if (!(isset($param['postograd_id']) && $param['postograd_id'])) $lista .= 'Cargo/Posto/Graduação  é necessário.<br>';
            if (!(isset($param['opm_id']) && $param['opm_id'])) $lista .= 'OPM Educacional (de Vínculo)  é necessário.<br>';
        }
        return array('status'=>$status,'erros'=>$lista);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Verifica os itens
 *------------------------------------------------------------------------------*/    
    public static function verificaItems ($param,$row)
    {
        $status = false;
        if ((isset($param['titularidade_id'][$row]) && $param['titularidade_id'][$row]) &&
            (isset($param['comprovado'][$row]) && $param['comprovado'][$row]) &&
            (isset($param['data_apresentacao'][$row]) && $param['data_apresentacao'][$row])) // validate required field
        {
            $status = true;
        }
        return $status;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Verifica Aulas em aberto
 *------------------------------------------------------------------------------*/    
    public static function noAula ($param)
    {
        $status = false;
        if (isset($param['key']))
        {
            $key = $param['key'];
            try
            {
                TTransaction::open('sisacad');
                $items  = professorcontrole_aula::where('professor_id', '=', $key)->
                                                  where('aulas_saldo','>','0')->
                                                  where('data_quitacao','IS',NULL)->
                                                  count();
                $status = ($items > 0);
                TTransaction::close();
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
        return $status;
    }//Fim Módulo
}//Fim Classe
