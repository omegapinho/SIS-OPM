<?php
/**
 * professorForm Form
 * @author  <your name here>
 */
class professorForm extends TPage
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
        TSession::setValue('disciplina_professor',null);

        // creates the form
        $this->form = new TForm('form_professor');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'max-width:500px'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $table_master = new TTable;
        $table_master->width = '100%';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';

        $table_pes    = new TTable;//Dados Pessoais
        $table_car    = new TTable;//Caracteristicas Físicas
        $table_loc    = new TTable;//Endereço
        $table_con    = new TTable;//Contatos
        $table_pro    = new TTable;//Profissional
        $table_doc    = new TTable;//Documentação
        $table_den    = new TTable;//Dependentes
        
        $notebook = new TNotebook(500, 420);
        
        // add the notebook inside the form
        $notebook->appendPage('Informações Pessoais'   , $table_pes);
        $notebook->appendPage('Dados Profissionais'    , $table_pro);
        $notebook->appendPage('Endereço'               , $table_loc);
        $notebook->appendPage('Documentação'           , $table_doc);
        $notebook->appendPage('Contatos'               , $table_con);
        $notebook->appendPage('Titularidade'           , $table_den);
        $notebook->appendPage('Registros'            , $table_car);
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Formulário de Cadastro de Professores');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($notebook);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $this->form->add($table_master);
        
        $ci  = new TSicadDados();
        
        // create the form fields
        $id               = new TEntry('id');
        $nome             = new TEntry('nome');
        $cpf              = new TEntry('cpf');
        $data_nascimento  = new TDate('data_nascimento');
        $sexo             = new TCombo('sexo');
        
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        $orgao_origem     = new TDBCombo('orgaosorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $postograd        = new TDBCombo('postograd_id','sicad','postograd','id','nome','nome');
        $quadro           = new TCombo('quadro');
        $telefone         = new TEntry('telefone');
        $email            = new TEntry('email');
        $celular          = new TEntry('celular');
        $lattes           = new TEntry('lattes');
        $status_documento = new TCombo('status_documento');
        $rg               = new TEntry('rg');
        $orgao_expeditor  = new TEntry('orgao_expeditor');
        $uf_expeditor     = new TDBCombo('uf_expeditor','sicad','estados','sigla','sigla','sigla');
        $status_funcional = new TCombo('status_funcional');
        $logradouro       = new TEntry('logradouro');
        $quadra           = new TEntry('quadra');
        $lote             = new TEntry('lote');
        $numero           = new TEntry('numero');
        $bairro           = new TEntry('bairro');
        $uf_residencia    = new TDBCombo('uf_residencia','sicad','estados','sigla','sigla','sigla');
        
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
        $opm_id           = new TDBCombo('opm_id','sicad','OPM','id','nome','nome',$criteria);

        $estado           = ($uf_residencia->getValue()) ? $uf_residencia->getValue() : 'GO' ;
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('uf','=',$estado));
        $cidade           = new TDBCombo('cidade','sicad','cidades','nome','nome','nome',$criteria);
        
        $oculto           = new TCombo('oculto');

        //Valores
        $sexo->addItems($fer->lista_sexo());
        $oculto->addItems($fer->lista_sim_nao());
        $status_documento->addItems($fer->lista_status_documentacao());
        $status_funcional->addItems($fer->lista_status_funcional());
        $orgao_origem->setValue('PMGO');
        $oculto->setValue('N');
        $uf_expeditor->setValue('GO');
        $status_funcional->setValue('ATV');
        $quadro->addItems($ci->caracteristicas_SICAD('quadro_alfa'));
        $opm_id->setValue($this->opm_operador);//Define que a OPM padrão é a mesma do operador
        
        //Requeridos
        $nome->addValidation('Nome', new TRequiredValidator); // required field
        $cpf->addValidation('CPF', new TCPFValidator); // required field
        $sexo->addValidation('Sexo', new TRequiredValidator); // required field
        $postograd->addValidation('Cargo Posto/Gradução', new TRequiredValidator); // required field
        $telefone->addValidation('Telefone', new TRequiredValidator); // required field
        $celular->addValidation('Celular', new TRequiredValidator); // required field
        $email->addValidation('Email', new TRequiredValidator); // required field
        $orgao_origem->addValidation('Órgão de Origem', new TRequiredValidator); // required field
        $opm_id->addValidation('OPM Educacional de Vínculo', new TRequiredValidator); // required field

        //Ações
        $change_action_municipio = new TAction(array($this, 'onChangeAction_municipio'));//Popula as cidades com a troca da UF
        $uf_residencia->setChangeAction($change_action_municipio);
        $change_action_posto = new TAction(array($this, 'onChangeAction_posto'));//Popula as cidades com a troca da UF
        $orgao_origem->setChangeAction($change_action_posto);
        
        //Mascaras
        $data_nascimento->setMask('dd/mm/yyyy');
        $telefone->setMask('(99)999999999');
        $celular->setMask('(99)999999999');
        $cpf->setMask('99999999999');

        // add the fields
        $table_pes->addRowSet(array(new TLabel('Id'), $id ));
        $table_pes->addRowSet(array($lbl = new TLabel('Nome'), $nome));
        $lbl->setFontColor('red');
        $table_pes->addRowSet(array(new TLabel('Data de Nascimento'), $data_nascimento) );
        $table_pes->addRowSet(array($lbl = new TLabel('Sexo'), $sexo, new TLabel('Oculto?'),$oculto) );
        $lbl->setFontColor('red');
        
        $table_pro->addRowSet(array($lbl = new TLabel('Órgão de Origem'), $orgao_origem) );
        $lbl->setFontColor('red');
        $table_pro->addRowSet(array($lbl = new TLabel('Posto/Graduação'), $postograd) );
        $lbl->setFontColor('red');
        $table_pro->addRowSet(array(new TLabel('Status Funcional'), $status_funcional) );
        $table_pro->addRowSet(array(new TLabel('Curriculum Lattes'),$lattes) );
        $table_pro->addRowSet(array($lbl = new TLabel('OPM Educacional de Vínculo'),$opm_id) );
        $lbl->setFontColor('red');
        
        $table_loc->addRowSet(array(new TLabel('Logradouro'), $logradouro) );
        $table_loc->addRowSet(array(new TLabel('No.'), $numero,new TLabel('QD.'), $quadra,new TLabel('LT.'), $lote) );
        $table_loc->addRowSet(array(new TLabel('Bairro'), $bairro) );
        $table_loc->addRowSet(array(new TLabel('UF'), $uf_residencia,new TLabel('Município'), $cidade) );

        $table_doc->addRowSet(array($lbl = new TLabel('CPF'), $cpf) );
        $lbl->setFontColor('red');
        $table_doc->addRowSet(array(new TLabel('RG'), $rg, new TLabel('Expeditor'), $orgao_expeditor,new TLabel('UF'), $uf_expeditor) );
        $table_doc->addRowSet(array(new TLabel('Entregou Documentação'),$status_documento) );
        
        $table_con->addRowSet(array($lbl = new TLabel('Telefone Residencial'), $telefone) );
        $lbl->setFontColor('red');
        $table_con->addRowSet(array($lbl = new TLabel('Telefone Celular'), $celular) );
        $lbl->setFontColor('red');
        $table_con->addRowSet(array($lbl = new TLabel('Email'), $email) );
        $lbl->setFontColor('red');
        
        //Tamanhos
        $id->setSize(100);
        $nome->setSize(400);
        $postograd->setSize(200);
        $cpf->setSize(120);
        $data_nascimento->setSize(120);
        $sexo->setSize(200);
        $orgao_origem->setSize(400);
        $telefone->setSize(120);
        $email->setSize(400);
        $celular->setSize(120);
        $lattes->setSize(400);
        $rg->setSize(120);
        $orgao_expeditor->setSize(400);
        $uf_expeditor->setSize(80);
        $logradouro->setSize(400);
        $quadra->setSize(100);
        $lote->setSize(100);
        $numero->setSize(100);
        $cidade->setSize(400);
        $uf_residencia->setSize(80);
        $status_documento->setSize(120);
        $status_funcional->setSize(400);
        $oculto->setSize(100);
        $opm_id->setSize(400);

        // detail fields
        $detail_id                  = new THidden('detail_id');
        $detail_nome_graduacao      = new TEntry('detail_nome_graduacao');
        $detail_instituicao         = new TEntry('detail_instituicao');
        
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','=','N'));
        $detail_titularidade_id     = new TDBCombo('detail_titularidade_id','sisacad','titularidade','id','nome','nivel',$criteria);
        
        $detail_uf                  = new TDBCombo('detail_uf','sicad','estados','sigla','sigla','sigla');
        $detail_arquivo_certificado = new TEntry('detail_arquivo_certificado');
        $detail_pais                = new TEntry('detail_pais');
        $detail_data_conclusao      = new TDate('detail_data_conclusao');
        $detail_data_apresentacao   = new TDate('detail_data_apresentacao');
        $detail_status              = new TCombo('detail_status');
        $detail_comprovado          = new TCombo('detail_comprovado');
        
        //Valores
        $detail_status->addItems($fer->lista_status_documentacao());
        $detail_comprovado->addItems($fer->lista_status_documentacao());

        //Tamanho
        $detail_nome_graduacao->setSize(400);
        $detail_instituicao->setSize(400);
        $detail_titularidade_id->setSize(200);
        $detail_pais->setSize(200);
        $detail_uf->setSize(80);
        $detail_data_apresentacao->setSize(80);
        $detail_data_conclusao->setSize(80);
        $detail_status->setSize(80);
        $detail_comprovado->setSize(80);
        
        //Mascaras
        $detail_data_apresentacao->setMask('dd-mm-yyyy');
        $detail_data_conclusao->setMask('dd-mm-yyyy');
         
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Cursos e Certificações inclusas');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        $table_details->addRowSet( '', $detail_id );
        $table_details->addRowSet( array(new TLabel('Tipo'), $detail_titularidade_id,
                                        new TLabel('Descrição'), $detail_nome_graduacao) );
        $table_details->addRowSet( array(new TLabel('Nome da Instituição'), $detail_instituicao) );        
        $table_details->addRowSet( array(new TLabel('País de Realização'), $detail_pais,new TLabel('UF'), $detail_uf) );
        $table_details->addRowSet( array(new TLabel('Data de Conclusão'), $detail_data_conclusao,
                                            new TLabel('Data da Apresentação'), $detail_data_apresentacao ) );
        $table_details->addRowSet( array(new TLabel('Concluso?'), $detail_status,
                                            new TLabel('Comprovado?'), $detail_comprovado ) );
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 120 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('', 'edit', 'left', 50);
        $this->detail_list->addQuickColumn('', 'delete', 'left', 50);
        
        // items
        $this->detail_list->addQuickColumn('Nome da Graduação', 'nome_graduacao', 'left', 200);
        $this->detail_list->addQuickColumn('Tipo de Graduação', 'titularidade_id', 'left', 200);
        $this->detail_list->addQuickColumn('DT Conclusão', 'data_conclusao', 'left', 100);
        $this->detail_list->createModel();
        
        $row = $table_detail->addRow();
        $row->addCell($this->detail_list);
        
        //$table_den->addRowSet($frame_details);
        //$table_den->addRowSet($table_detail);

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        
        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array($this, 'onReturn')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        $disc_button=new TButton('disciplina');
        $disc_button->setAction(new TAction(array($this, 'onDisciplina')), 'Área de Interesse');
        $disc_button->setImage('fa:pencil fa-fw red');
        
        $tit_button = new TButton('titularidade');
        $tit_button->setAction(new TAction(array($this, 'onTitularidade')), 'Titularidade');
        $tit_button->setImage('fa:certificate green fa-lg');
        
        //Horizontal Box-01        
        $hbox1 = new THBox;
        $hbox1->addRowSet($disc_button);
        $hbox1->addRowSet($tit_button);
        $frame1 = new TFrame;
        $frame1->setLegend('Definições Básicas');
        $frame1->add($hbox1);
        
        $hbox2 = new THBox;
        $hbox2->addRowSet( '' );
        $frame2 = new TFrame;
        $frame2->setLegend('Incidentes e Avaliações pedagógicas');
        $frame2->add($hbox2);
        
        $table_car->addRowSet($frame1);
        $table_car->addRowSet($frame2);
        
        
        //$this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        //$this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        // define form fields
        $this->formFields   = array($id, $nome,$cpf,$data_nascimento,
                                    $telefone,$email,$sexo,$celular,
                                    $logradouro,$numero,$quadra,$lote,$bairro,$cidade,$uf_residencia,
                                    $rg,$orgao_expeditor,$uf_expeditor,$postograd,$status_funcional,
                                    $lattes,$status_documento,$orgao_origem,$oculto,$opm_id,
                                    
                                    $detail_arquivo_certificado,$detail_comprovado,$detail_data_apresentacao,$detail_data_conclusao,
                                    $detail_instituicao,$detail_nome_graduacao,$detail_pais,$detail_status,$detail_titularidade_id,
                                    $detail_uf
                                    );
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $save_button;
        $this->formFields[] = $new_button;
        $this->formFields[] = $return_button;
        $this->formFields[] = $disc_button;
        $this->formFields[] = $tit_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );

        $table_master->addRowSet( array($save_button, $new_button,$return_button), '', '')->class = 'tformaction'; // CSS class

        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'professorList'));
        $container->add($this->form);
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        $data = $this->form->getData();
        $fer = new TFerramentas();
        $data->cpf = $fer->soNumeros($data->cpf);//Remove simbolos diversos
        try
        {
            // open a transaction with database
            TTransaction::open('sicad');
            $this->form->validate(); // form validation
            //Verifica se já o CPF já existe
            if (empty($data->id))
            {
                $testes = professor::where('cpf','=',$data->cpf)->load();
            }
            else
            {
                $testes = null;
            }
            if (!empty($testes))
            {
                $teste = $testes[0];
                $repetido = '<br>Dados encontrados<br>Nome: '.$teste->nome  . 
                                    ((!empty($teste->postograd_id)) ? ' - ' . $teste->postograd->sigla : '') . 
                                    ' - CPF ' . $teste->cpf . '<br>'.
                                    ' Órgão de Vinculo '. ((!empty($teste->orgaosorigem_id)) ? $teste->orgaosorigem->nome : 'NC') .
                                    '<br>Vinculado à OPM Educacional ' .  ((!empty($teste->opm_id)) ? $teste->opm->nome : 'NC');
                throw new Exception('CPF já Cadastrado no Sistema'.$repetido);
            }
            //Fazendo ajustes em alguns campos.
            if ($data->data_nascimento)
                $data->data_nascimento   = TDate::date2us($data->data_nascimento);
            $data->rg = (!empty($data->rg)) ? $fer->soNumeros($data->rg) : ''; 
            
            $master = new professor;
            $master->fromArray( (array) $data);
            
            //Convertendo para maiúsculas
            $master->nome = mb_strtoupper($master->nome,'UTF-8');
            $master->logradouro = mb_strtoupper($master->logradouro,'UTF-8');
            $master->bairro = mb_strtoupper($master->bairro,'UTF-8');
            $master->orgao_expeditor = mb_strtoupper($master->orgao_expeditor,'UTF-8');

            $master->store(); // save master object
            // delete details
            $old_items = escolaridade::where('professor_id', '=', $master->id)->delete();
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            if( $items )
            {
                foreach( $items as $item )
                {
                    $detail = new escolaridade();
                    //var_dump($item);
                    $detail->nome_graduacao      = mb_strtoupper($item['nome_graduacao'],'UTF-8');
                    $detail->instituicao         = mb_strtoupper($item['instituicao'],'UTF-8');
                    $detail->uf                  = $item['uf'];
                    $detail->pais                = mb_strtoupper($item['pais'],'UTF-8');
                    $detail->data_conclusao      = TDate::date2us($item['data_conclusao']);
                    $detail->data_apresentacao   = TDate::date2us($item['data_apresentacao']);
                    $detail->status              = $item['status'];
                    $detail->titularidade_id     = $item['titularidade_id'];
                    $detail->comprovado          = $item['comprovado'];
                    $detail->arquivo_certificado = $item['arquivo_certificado'];
                    $detail->professor_id        = $master->id;
                    $detail->store();
                 }
            }
            TTransaction::close(); // close the transaction
            // reload form and session items
            $this->onEdit(array('key'=>$master->id));
            
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
        TSession::setValue(__CLASS__.'_items', array());
        $this->onReload( $param );
    }
    
    /**
     * Load Master/Detail data from database to form/session
     */
    public function onEdit($param)
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
                
                $object = new professor($key);

                $items  = escolaridade::where('professor_id', '=', $key)->load();
                $session_items = array();
                foreach( $items as $item )
                {
                    $item_key = $item->id;
                    $session_items[$item_key] = $item->toArray();
                    $session_items[$item_key]['id'] = $item->id;
                    $session_items[$item_key]['nome_graduacao'] = $item->nome_graduacao;
                    $session_items[$item_key]['instituicao'] = $item->instituicao;
                    $session_items[$item_key]['data_conclusao'] = TDate::date2br($item->data_conclusao);
                    $session_items[$item_key]['uf'] = $item->uf;
                    $session_items[$item_key]['pais'] = $item->pais;
                    $session_items[$item_key]['status'] = $item->status;
                    $session_items[$item_key]['arquivo_certificado'] = $item->arquivo_certificado;
                    $session_items[$item_key]['comprovado'] = $item->comprovado;
                    $session_items[$item_key]['data_apresentacao'] = TDate::date2br($item->data_apresentacao);;
                    $session_items[$item_key]['titularidade_id'] = $item->titularidade_id;
                }
                TSession::setValue(__CLASS__.'_items', $session_items);
                //Formata a data para d/m/YYYY
                if ($object->data_nascimento)
                    $object->data_nascimento = TDate::date2br($object->data_nascimento);

                //$this->form->setData($object); // fill the form with the active record data
                $this->onReload( $param ); // reload items list
                TTransaction::close(); // close transaction
                $this->form->setData($object); // fill the form with the active record data
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
/*---------------------------------------------------------------------------------------
 *                   Troca cidades conforme UF - Residência
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_municipio($param)
    {
        if (array_key_exists('uf_residencia',$param))
        {
            $key = $param['uf_residencia'];
        }
        else
        {
            return;
        }
        try
        {
                TTransaction::open('sicad'); // open a transaction
                $options  = cidades::where('uf', '=', $key)->load();//Lista de Cidades Filtradas
                TTransaction::close(); // close the transaction
                $lista = array(''=>'');
                foreach ($options as $option)
                {
                    $lista[$option->nome] = $option->nome;
                    
                }
                TDBCombo::reload('form_professor', 'cidade', $lista, true);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Retorno a Listagem
 *---------------------------------------------------------------------------------------*/
    public function onReturn ($param = null)
    {
        TApplication::loadPage('professorList');
    }//Fim Módulo

/*---------------------------------------------------------------------------------------
 *  Rotina: Reload
 *---------------------------------------------------------------------------------------*/
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
                $item->id = $list_item['id'];
                $item->nome_graduacao   = $list_item['nome_graduacao'];
                $titulo = $this->getTitulo($list_item['titularidade_id']);
                $titulo = ($titulo !=false) ? $titulo : '-';
                $item->titularidade_id  = $titulo;
                $item->data_conclusao   = $list_item['data_conclusao'];

                $row = $this->detail_list->addItem( $item );
                $row->onmouseover='';
                $row->onmouseout='';
            }

            $this->form->setFields( $this->formFields );
        }
        
        $this->loaded = TRUE;

    }//Fim Módulo
    /**
     * Save an item from form to session list
     * @param $param URL parameters
     */
    public function onSaveDetail( $param )
    {
        $data = $this->form->getData();
        try
        {
            TTransaction::open('sisacad');
           
            /** validation sample
            if (! $data->fieldX)
                throw new Exception('The field fieldX is required');
            **/
            
            $items = TSession::getValue(__CLASS__.'_items');
            $key = empty($data->detail_id) ? 'X'.mt_rand(1000000000, 1999999999) : $data->detail_id;
            
            $items[ $key ] = array();
            $items[ $key ]['id']                  = $key;
            $items[ $key ]['nome_graduacao']      = $data->detail_nome_graduacao;
            $items[ $key ]['data_conclusao']      = $data->detail_data_conclusao;
            $items[ $key ]['data_apresentacao']   = $data->detail_data_apresentacao;
            $items[ $key ]['uf']                  = $data->detail_uf;
            $items[ $key ]['pais']                = $data->detail_pais;
            $items[ $key ]['status']              = $data->detail_status;
            $items[ $key ]['arquivo_certificado'] = $data->detail_arquivo_certificado;
            $items[ $key ]['comprovado']          = $data->detail_comprovado;
            $items[ $key ]['titularidade_id']     = $data->detail_titularidade_id;
            $items[ $key ]['instituicao']         = $data->detail_instituicao;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_nome_graduacao      = '';
            $data->detail_data_conclusao      = '';
            $data->detail_data_apresentacao   = '';
            $data->detail_uf                  = '';
            $data->detail_pais                = '';
            $data->detail_instituicao         = '';
            $data->detail_status              = '';
            $data->detail_comprovado          = '';
            $data->detail_arquivo_certificado = '';
            $data->detail_titularidade_id     = '';
            
            TTransaction::close();
            $this->form->setData($data);
            
            $this->onReload( $param ); // reload the items
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }//Fim Módulo
    /**
     * Delete an item from session list
     * @param $param URL parameters
     */
    public function onDeleteDetail( $param )
    {
        $data = $this->form->getData();
        
        // clear detail form fields
        $data->detail_id = '';
        $data->detail_nome_graduacao      = '';
        $data->detail_data_conclusao      = '';
        $data->detail_data_apresentacao   = '';
        $data->detail_uf                  = '';
        $data->detail_pais                = '';
        $data->detail_instituicao         = '';
        $data->detail_status              = '';
        $data->detail_comprovado          = '';
        $data->detail_arquivo_certificado = '';
        $data->detail_titularidade_id     = '';
        
        // clear form data
        $this->form->setData( $data );
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // delete the item from session
        unset($items[ $param['item_key'] ] );
        TSession::setValue(__CLASS__.'_items', $items);
        
        // reload items
        $this->onReload( $param );
    }//Fim Módulo
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
        
        $data->detail_id                  = $item['id'];
        $data->detail_nome_graduacao      = $item['nome_graduacao'];
        $data->detail_data_conclusao      = $item['data_conclusao'];
        $data->detail_data_apresentacao   = $item['data_apresentacao']    ;
        $data->detail_uf                  = $item['uf'];
        $data->detail_pais                = $item['pais'];
        $data->detail_instituicao         = $item['instituicao'];
        $data->detail_status              = $item['status'];
        $data->detail_comprovado          = $item['comprovado'];
        $data->detail_arquivo_certificado = $item['arquivo_certificado'];
        $data->detail_titularidade_id     = $item['titularidade_id'];
        
        // fill detail fields
        $this->form->setData( $data );
    
        $this->onReload( $param );
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Busca dados titularidade
 *---------------------------------------------------------------------------------------*/
     public function getTitulo ($key = null)
    {
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                $options  = titularidade::where('id', '=', $key)->load();//Lista títulos
                TTransaction::close(); // close the transaction
                foreach ($options as $option)
                {
                    $titulo = $option->nome;
                }
                return $titulo;
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
        return false;

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Direciona para Gerir disciplinas do professor
 *------------------------------------------------------------------------------*/
     public function onDisciplina ($param = null)
     {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         
         if (empty($data->id))
         {
             new TMessage('info','Por favor, salve primeiro antes de cadastrar habilitar as disciplinas ao professor!!!');
         }
         else
         {
              TSession::setValue('disciplina_rofessor',$data);
              TApplication::loadPage('disciplinaProfessorForm','onEdit',array('key'=>$data->id));
              //var_dump($data);
         }
         $this->form->setData($data);
         
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Direciona para Gerir disciplinas do professor
 *------------------------------------------------------------------------------*/
     public function onTitularidade ($param = null)
     {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         
         if (empty($data->id))
         {
             new TMessage('info','Por favor, salve primeiro antes de cadastrar habilitar as disciplinas ao professor!!!');
         }
         else
         {
              TSession::setValue('disciplina_rofessor',$data);
              TApplication::loadPage('professorEscolaridadeForm','onEdit',array('key'=>$data->id));
              //var_dump($data);
         }
         $this->form->setData($data);
         
     }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca postos/graduação e função
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_posto($param)
    {
        if (array_key_exists('orgaosorigem_id',$param))
        {
            $key = (!empty($param['orgaosorigem_id'])) ? $param['orgaosorigem_id'] : false;
        }
        $lista = array ('-'=>'Sem definição cadastrada');
        if ($key)
        {
            try
            {
                TTransaction::open('sisacad'); // open a transaction
                
                $repository = new TRepository('orgaosorigem');
                $criteria = new TCriteria;
                $filter = new TFilter('id', '=', $key); // create the filter
                $criteria->add($filter); // add the session filter
                
                // load the objects according to criteria
                $objects = $repository->load($criteria, true);
                foreach ($objects as $object)
                {
                    $id = $object->id;
                }
                $repository = new TRepository('postograd');
                $criteria = new TCriteria;
                $filter = new TFilter('orgaosorigem_id', '=', $id); // create the filter
                $ord = array ('order'=>'id','direction'=>'asc');
                $criteria->setProperties($ord); // order, offset
                $criteria->add($filter); // add the session filter
    
                $postos = $repository->load($criteria, true);
    
    
                TTransaction::close(); // close the transaction
                if (!empty($postos))
                {
                    $lista = array();
                    foreach ($postos as $posto)
                    {
                        $lista[$posto->id] = $posto->nome;
                    }
                }
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        TDBCombo::reload('form_professor', 'postograd_id', $lista);
    }//Fim Módulo
}
