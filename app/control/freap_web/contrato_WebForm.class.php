<?php
/**
 * contratoForm
 * @author  Fernando de Pinho Araújo
 */
class contrato_WebForm extends TPage
{
    protected $form; // form
    protected $servico_info; //Dados para calculo do serviço
    const     rest_dare     = "http://10.6.0.36/arr-rs/api/dare";
    var       $opm_ext      = 27;//BPMRV
    var       $opm_ext_nome = "BPMRV";//Nome do local atendido
    var       $form_nome    = "contratoForm";//Nome do Formulário ou do Form de Retorno
    var       $isWeb        = true;//Ativa o serviço para WEB (freap-web) ou junto com o SISOPM
    
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $unidade = TSession::getValue('unidade_FREP');
        if (!empty($unidade))
        {
            $this->opm_ext = $unidade['opm'];
            $this->opm_ext_nome = $unidade['nome'];
        }
        $this->form = new TForm('form_contrato');
        $this->form->class = 'tform'; // change CSS class
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        //$this->form->setFormTitle('DARE de Serviços do BPMRV');
        //Cria as abas
        $table_data    = new TTable;
        $table_doc     = new TTable;
        $table_loc     = new TTable;
        
        $notebook = new TNotebook(700, 350);
        
        // add the notebook inside the form
        $this->form->add($notebook);
        
        $notebook->appendPage('Contratante/Serviço', $table_data);
        $notebook->appendPage('Endereço', $table_loc);
        $notebook->appendPage('Diversos', $table_doc);

        // create the form fields
        $id = new TEntry('id');
        //Monta pesquisa dos serviços Regionais
        $ferram  = new TFerramentas;
        if ($this->isWeb == false)//Está no conjunto SISOPM
        {
            $profile = TSession::getValue('profile');

            if ($profile)//Verifica se há um profile oriundo do Login SSO
            {
                $criteria_serv = new TCriteria;
                if (!$ferram->i_adm())
                {
                    $query = "(SELECT DISTINCT id_servico FROM freap.grupo_servico_opm WHERE id_opm IN (".$profile['unidade']['id']."))";
                    $criteria_serv->add (new TFilter ('id','IN',$query));
                }
                $criteria_serv->add (new TFilter ('oculto','=','false'));
            }
            else//Critério para ambiente de desenvolvimento
            {
                $criteria_serv = new TCriteria;
                if (!$ferram->i_adm())
                {
                    $query = "(SELECT DISTINCT id_servico FROM freap.grupo_servico_opm WHERE id_opm IN (140))";
                    $criteria_serv->add (new TFilter ('id','IN',$query));
                }
                $criteria_serv->add (new TFilter ('oculto','=','false'));
            }
        }
        else//Está em ambiente FREAP-WEB
        {
            $criteria_serv = new TCriteria;
            $query = "(SELECT DISTINCT id_servico FROM freap.grupo_servico WHERE id_opm IN (".$this->opm_ext."))";
            $criteria_serv->add (new TFilter ('id','IN',$query));
            $criteria_serv->add (new TFilter ('oculto','=','false'));
        }

    
        $id_servico = new TDBCombo('id_servico','freap','servico','id','nome','codigo',$criteria_serv);
        $id_contribuinte = new TEntry('id_contribuinte');
        $opm_nome = new TEntry('opm_nome');
        $valor_total = new TEntry('valor_total');
        $qnt_km = new TEntry('qnt_km');
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        $doc_vinculo = new TEntry('doc_vinculo');
        $qnt_policial = new TEntry('qnt_policial');
        $data_criado = new THidden('data_criado');
        $numero_sefaz = new TEntry('numero_sefaz');
        $qnt_horas = new TEntry('qnt_horas');
        $cpf_atendente = new THidden('cpf_atendente');
        $cpf_liberador = new THidden('cpf_liberador');
        $descricao_contratante = new TText('descricao_contratante');
        $descricao_servico = new TText('descricao_servico');
        $uf_servico = new TDBCombo('uf_servico','sisacad','estados','sigla','sigla');
        //Filtro de cidades
        $criteria = new TCriteria;
        $criteria->add(new TFilter('uf','=','GO'));
        $cidade_servico = new TDBCombo('cidade_servico','sisacad','cidades','nome','nome','nome',$criteria);
        //
        $bairro_servico = new TEntry('bairro_servico');
        $endereco_servico = new TEntry('endereco_servico');
        $fone_contato_servico = new TEntry('fone_contato_servico');
        $obs = new THidden('obs');
        $data_vencimento = new TDate('data_vencimento');
        $data_pagamento = new TDate('data_pagamento');
        $razao_social = new TEntry('razao_social');
        $id_opm = new THidden('id_opm');
        $horas_diurno = new TEntry('horas_diurno');
        $horas_noturno = new TEntry('horas_noturno');

        //Ações
        $change_action = new TAction(array($this, 'onChangeAction_cidades'));//Popula as cidades com a troca da UF
        $uf_servico->setChangeAction($change_action);

        $cpf_action = new TAction(array($this,'onExitAction_nome'));//Busca dados de contribuinte já cadastrado
        $id_contribuinte->setExitAction($cpf_action);
        
        $servico_action = new TAction(array($this, 'onServico'));//Troca o tipo de serviço e seus dados
        $id_servico->setChangeAction($servico_action);
        
        // cria botão de calculo
        $calc_button=new TButton('calculator');
        $calc_button->setAction(new TAction(array($this, 'onChangeAction_calculos')), 'Calcula Valores');
        $calc_button->setImage('fa:calculator white');
        $calc_button->setProperty('style','text-align:center');
        $calc_button->setProperty('title','Use após escolher o serviço e preencher as quantidades que necessita.');
        $calc_button->class = 'btn btn-warning btn-sm';

        //Formatação
        $valor_total->setProperty('style','text-align:right');
        
        //Valores pré-definidos
        $doc_vinculo->setCompletion(array('PRESENCIAL','CONTATO TELEFÔNICO','DIVERSO','OFÍCIO Nº','REQUERIMENTO Nº','AUTO DE INFRAÇÃO Nº','NOTIFICAÇÃO Nº'));
         
        //Tips
        $id_contribuinte->setTip('Preencha com o CPF ou CNPJ do contratante.');
        $razao_social->setTip('Nome do Contratante ou a Razão Social da Empresa contratante. Se o CPF ou CNPJ já tiver cadastro no Sistema, o campo se preenche sozinho');
        $id_servico->setTip('Escolha um dos serviços disponíveis para contratar.');
        $doc_vinculo->setTip('Entre com a numeração do Boletim de Ocorrência, Guia de Recolhimento/Apreenção, dados do Ofício/Requerimento etc');
        $data_inicio->setTip('Entre com a data inicial do processo. Necessariamente deve ser uma data anterior a Data Final.');
        $data_fim->setTip('Entre com a data final do processo. Nessariamente deve ser posterior a Data Inicial.');
        $qnt_policial->setTip('Informe a quantidade de Policiais a serem empregados. Dependendo do serviço, pode haver um mínimo ou máximo que pode ser utilizado.');
        $qnt_km->setTip('Informe a quantidade de Kilometros percorridos.');
        $qnt_horas->setTip('Informe a quantidade de horas que serão utilizados os serviços da PM.');
        $data_vencimento->setTip('Informe a data que este contrato vencerá. Vale lembrar que pode haver a cobrança de multa e juros caso o pagamento se dê a posteriori da mesma.');
        $data_pagamento->setTip('Informe o dia que se dará o pagamento. É bom lembrar que se for uma data superior ao do dia de vencimento poderá haver cobrança de multa e juros');
        
        // add the fields
        //Aba Contratante
        $table_data->addRowSet(array(new TLabel('Id:'), $id));
        $table_data->addRowSet(array(new TLabel('CPF/CNPJ:'), $id_contribuinte));
        $table_data->addRowSet(array(new TLabel('Razão Social:'), $razao_social));
        $table_data->addRowSet(array(new TLabel('Serviço:'), $id_servico));
        $table_data->addRowSet('<center><center>');
        $table_data->addRowSet(array(new TLabel('QNT PM:'), $qnt_policial,new TLabel('QNT KM:'), $qnt_km,new TLabel('QNT Horas:'), $qnt_horas));
        $table_data->addRowSet(array(new TLabel('Horas AC-4 Diurna:'), $horas_diurno,new TLabel('Horas AC-4 Noturna:'), $horas_noturno));
        $table_data->addRowSet(array(new TLabel('Data Inicial:'),$data_inicio,new TLabel('Data Final:'), $data_fim));
        $table_data->addRowSet('<center><center>');
        $table_data->addRowSet(array(new TLabel('Documento Vinculado:'),$doc_vinculo,new TLabel('Número da SEFAZ:'), $numero_sefaz));
        $table_data->addRowSet(array(new TLabel('Vencimento:'),$data_vencimento,new TLabel('Pagamento:'),$data_pagamento));
        $table_data->addRowSet(array(new TLabel('Valor Total R$:'), $valor_total));
        //$table_data->addRowSet(array($calc_button));

        //Aba Endereço
        $table_loc->addRowSet(new TLabel('Logradouro:'),$endereco_servico);
        $table_loc->addRowSet(new TLabel('Bairro:'),$bairro_servico);
        $table_loc->addRowSet(new TLabel('UF:'),$uf_servico);
        $table_loc->addRowSet(new TLabel('Cidade:'),$cidade_servico);
        $table_loc->addRowSet(new TLabel('Telefone:'),$fone_contato_servico);
        $table_loc->addRowSet(new TLabel('Missão:'),$descricao_servico);
        //Campos ocultos
        $table_loc->addRowSet($cpf_atendente,$cpf_liberador);
        $table_loc->addRowSet($id_opm,$obs);
        //Aba Diversos
        $table_doc->addRowSet(new TLabel('Descrição do Contratante:'),$descricao_contratante);
        
        //Bloqueios
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $descricao_contratante->setEditable(false);
        $valor_total->setEditable(false);
        $numero_sefaz->setEditable(false);
        self::campo_Habilita('0','f');
        
        //Formata o tamanho dos campos
        $id->setSize(100);
        $id_contribuinte->setSize(150);
        $id_servico->setSize(400);
        $razao_social->setSize(400);
        $data_inicio->setSize(80);
        $data_fim->setSize(80);
        $qnt_horas->setSize(50);
        $qnt_policial->setSize(50);
        $qnt_km->setSize(50);
        $horas_diurno->setSize(50);
        $horas_noturno->setSize(50);
        $valor_total->setSize(80);
        $numero_sefaz->setSize(150);
        $endereco_servico->setSize(400);
        $cidade_servico->setSize(400);
        $uf_servico->setSize(80);
        $bairro_servico->setSize(400);
        $fone_contato_servico->setSize(150);
        $descricao_servico->setSize(400,160);
        $descricao_contratante->setSize(400,160);
        $data_vencimento->setSize(80);
        $data_pagamento->setSize(80);

        //Mascaras
        $data_inicio->setMask('d/mm/yyyy');
        $data_fim->setMask('d/mm/yyyy');
        $data_pagamento->setMask('d/mm/yyyy');
        $data_vencimento->setMask('d/mm/yyyy');
        $qnt_horas->setMask('99999');
        $qnt_km->setMask('99999');
        $qnt_policial->setMask('99999');
        $horas_diurno->setMask('99999');
        $horas_noturno->setMask('99999');

        // create the form actions
        // create the save button
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), "Salva Boleto");
        $save_button->setImage('ico_save.png');
        $xtest = $numero_sefaz->getValue();
        if (empty($xtest))//Bloqueia o botão salvar se o boleto já foi gerado
        {
             $save_button->enableField('form_contrato','save');
             $calc_button->enableField('form_contrato','calculator');
        }
        else
        {
            if (!$ferram->i_adm())// Se for administrador não bloqueia
            {
                 $save_button->disableField('form_contrato','save');
                 $calc_button->disableField('form_contrato','calculator');
            }
        }
        //Botão Gerador de Boleto
        $primeiravia_button = new TButton('primeiravia');
        $primeiravia_button->setAction(new TAction(array($this, 'primeiraVia')), 'Emite Boleto');
        $primeiravia_button->setImage('ico_print.png');
        $primeiravia_button->setProperty('style','background: lightblue;');

        $segundavia_button = new TButton('segundavia');
        $segundavia_button->setAction(new TAction(array($this, 'segundaVia')), '2ª Via do Boleto');
        $segundavia_button->setImage('ico_print.png');
        $segundavia_button->setProperty('style','background: lightblue;');

        $primeiravia_button->disableField('form_contrato','primeiravia');
        $segundavia_button->disableField('form_contrato','segundavia');
                       
        // create an action button
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), "Novo Boleto");
        $new_button->setImage('ico_new.png');
                
        // Cria botão de retorno para listagem e insere campos no form
        if ($this->isWeb == false )
        {
            $return_button=new TButton('back');
            $return_button->setAction(new TAction(array($this, 'onBack')), "Retorna para Listagem");
            $return_button->setImage('ico_back.png');
            $this->form->setFields(array($id, $id_contribuinte,$razao_social,$id_servico,$qnt_policial,$qnt_horas,$qnt_km,
                                        $valor_total,$doc_vinculo,$numero_sefaz,$endereco_servico,$bairro_servico,$uf_servico,
                                        $cidade_servico,$fone_contato_servico,$descricao_servico,$descricao_contratante,$obs,
                                        $cpf_atendente,$cpf_liberador,$data_criado,$data_pagamento,$data_vencimento,
                                        $calc_button,$horas_diurno,$horas_noturno,
                                        $save_button,$new_button,$primeiravia_button,$segundavia_button,$return_button));//
        }
        else
        {
            $this->form->setFields(array($id, $id_contribuinte,$razao_social,$id_servico,$qnt_policial,$qnt_horas,$qnt_km,
                                        $valor_total,$doc_vinculo,$numero_sefaz,$endereco_servico,$bairro_servico,$uf_servico,
                                        $cidade_servico,$fone_contato_servico,$descricao_servico,$descricao_contratante,$obs,
                                        $cpf_atendente,$cpf_liberador,$data_criado,$data_pagamento,$data_vencimento,
                                        $calc_button,$horas_diurno,$horas_noturno,
                                        $save_button,$new_button,$primeiravia_button,$segundavia_button));//
        }
        $subtable = new TTable;
        $row = $subtable->addRow();
        $row->addCell($calc_button);
        $row->addCell($save_button);
        $row->addCell($new_button);
        $row->addCell($primeiravia_button);
        $row->addCell($segundavia_button);
        if ($this->isWeb == false)
        {
            $row->addCell($return_button);
        }
        // wrap the page content
        $vbox = new TVBox;
        //$vbox->add(new TXMLBreadCrumb('menu.xml', $this->form_nome));
        $vbox->add(new TLabel('<h4 style = "color: red;"><center>Recolhimento de Taxas para o ' . $this->opm_ext_nome.'</center></h4>'));
        $vbox->add($this->form);
        $vbox->add($subtable);
        
        //$panel = new TPanelGroup('<h4 style = "color: red;"><center>Recolhimento de Taxas para o ' . $this->opm_ext_nome .'</center></h4>');
        //$panel->add('<center>' . $vbox . '</center>');
        
        
        // add the form inside the page
        parent::add('<center>' . $vbox . '</center>');
    }

/*---------------------------------------------------------------------------------------
 *  Rotina: Salvar. Recebe $param mas na verdade extrai os dados via $this->form->getdata();
 *---------------------------------------------------------------------------------------*/
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('freap'); // open a transaction
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            //$this->form->validate(); // validate form data
            $ferram = new TFerramentas;
            //$data = new contrato; // get form data as array
            //$data->fromArray((array) $param);
            $data = $this->form->getData();
            //var_dump($data);
            self::onChangeAction_calculos($param);
            $relatos = self::isValidSave($data);//Valida o Formulário antes de salvar
            if (!empty($relatos))//Erro ao validar
            {
                $validar='';
                foreach ($relatos as $relato)
                {
                    if (!empty($relato))
                    {
                        $validar.=$relato.'<br>';//Cria o relatório dos erros
                    }
                }
                throw new Exception ($validar);
            }
            $servico_info = TSession::getValue('servico_info');
            if ($servico_info->calcula_multas!=true)
            {
                $data->data_vencimento = (empty($data->data_vencimento)) ? $data->data_pagamento : $data->data_vencimento;
            }
            
            $object = new contrato;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            
            //Completa os dados ocultos
            if ($this->isWeb == false)
            {
                if (TSession::getValue('ambiente')!='local')//Se logou pelo SSO da SSPAP
                {
                    $profile = TSession::getValue('profile');
                    $object->opm_nome = (empty($data->opm_nome)) ? $profile['unidade']['nome'] : $data->opm_nome;                 
                    $object->opm_id   = (empty($data->opm_id))   ? $profile['unidade']['id']   : $data->opm_id;
                }
                else
                {
                    $object->opm_nome = (empty($data->opm_nome)) ? 'AMBIENTE DE DESENVOLVIMENTO' : $data->opm_nome;                 
                    $object->opm_id   = (empty($data->opm_id))   ? '100555999'   : $data->opm_id;
                }
            }
            else
            {
                $object->opm_nome = $this->opm_ext_nome;                 
                $object->opm_id   = $this->opm_ext;
            }
            date_default_timezone_set('America/Sao_Paulo');
            $object->data_criado     = (empty($data->data_criado))   ?  date('Y/m/d H:i:s') : $data->data_criado;
            if ($this->isWeb == false)
            {
                $object->cpf_atendente   = (empty($data->cpf_atendente)) ?  TSession::getValue('login')  : $data->cpf_atendente;
            }
            else
            {
                $object->cpf_atendente   = "WEB";
            }
            $object->data_inicio     = (!empty($data->data_inicio))  ? date('Y/m/d', strtotime($data->data_inicio)) : null;
            $object->data_fim        = (!empty($data->data_fim))     ? date('Y/m/d', strtotime($data->data_fim))    : null;
            $object->data_vencimento = date('Y/m/d', strtotime($data->data_vencimento));
            $object->data_pagamento  = date('Y/m/d', strtotime($data->data_pagamento));
             
            //$message = var_dump($object);
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            $key = self::busca_tipo($object->id_servico);
            if ($key)
            {
                self::campo_Habilita($key->tipo_servico,$key->hora_virtual);
            }
            else
            {
                self::campo_Habilita('0','f');
            }
            $data->valor_total = number_format($data->valor_total,2,'.','');
            TTransaction::close(); // close the transaction
            if (!empty($object->numero_sefaz))
            {
                TButton::disableField('form_contrato','primeiravia');
                TButton::enableField('form_contrato','segundavia');                    
                TButton::disableField('form_contrato','save');
            }
            else
            {
                TButton::enableField('form_contrato','primeiravia');
                TButton::disableField('form_contrato','segundavia');
                TButton::enableField('form_contrato','save');                                    
            }
            //var_dump($data);
            TForm::sendData('form_contrato', $data);           
            new TMessage('info', "Boleto Salvo!<br>Agora você pode imprimi-lo.");

            //$this->form->sendData($data); // fill form data
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TForm::sendData('form_contrato', $data); 
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Limpa o Form para novo preenchimento
 *---------------------------------------------------------------------------------------*/
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
        TButton::disableField('form_contrato','primeiravia');
        TButton::disableField('form_contrato','segundavia');
        TButton::enableField('form_contrato','save');
        $obj = new StdClass;
        TForm::sendData('form_contrato', $obj);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Edição. Recebe $param para preencher o Form
 *---------------------------------------------------------------------------------------*/
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('freap'); // open a transaction
                $object = new contrato($key); // instantiates the Active Record
                $object->valor_total = number_format($object->valor_total,2,'.','');
                $this->form->setData($object); // fill the form
                $key = self::busca_tipo($object->id_servico);
                if ($key)
                {
                    self::campo_Habilita($key->tipo_servico,$key->hora_virtual);
                }
                else
                {
                    self::campo_Habilita('0','f');
                }
                TTransaction::close(); // close the transaction
                if (!empty($object->numero_sefaz))
                {
                    TButton::disableField('form_contrato','primeiravia');
                    TButton::enableField('form_contrato','segundavia');                    
                    TButton::disableField('form_contrato','save');
                }
                else
                {
                    TButton::enableField('form_contrato','primeiravia');
                    TButton::disableField('form_contrato','segundavia');
                    TButton::enableField('form_contrato','save');                                    
                }

            }
            else
            {
                $this->form->clear(TRUE);
                TButton::disableField('form_contrato','primeiravia');
                TButton::disableField('form_contrato','segundavia');
                TButton::enableField('form_contrato','save');
            }
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
    public function onBack ()
    {
        TApplication::loadPage('contratoList');
    }
/*---------------------------------------------------------------------------------------
 *                   Troca cidades conforme UF
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_cidades($param)
    {
        if (array_key_exists('uf_servico',$param))
        {
            $key = $param['uf_servico'];
        }
        else
        {
            return;
        }
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                $options  = cidades::where('uf', '=', $key)->load();//Lista de Cidades Filtradas
                TTransaction::close(); // close the transaction
                $lista = array();
                foreach ($options as $option)
                {
                    $lista[$option->nome] = $option->nome;
                    
                }
                TDBCombo::reload('form_contrato', 'cidade_servico', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *                       Busca nome de contribuinte
 *------------------------------------------------------------------------------*/
    public static function onExitAction_nome($param)
    {
        if ($param)
        {
            $key = (is_array($param)) ? $param['id_contribuinte'] : $param;
            if (strlen($key)!=11 && strlen($key)!=14)
            {
                return;
            }
            try
            {
                TTransaction::open('freap');
                $contribuintes = (strlen($key)==11) ? contribuinte::where ('cpf','=',$key)->load(): $contribuintes = contribuinte::where ('cnpj','=',$key)->load();
                TTransaction::close();
                $contribuinte = false;
                foreach ($contribuintes as $c)
                {
                    $contribuinte = $c;
                }
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage().'Erro na pesquisa'); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }            
        }
        else
        {
            return;
        }
        if(!$contribuinte)
        {
            return;
        }
        $obj = new StdClass;
        $obj->razao_social = (strlen($key)==11) ? $contribuinte->nome : $contribuinte->razao_social ;
        $obj->endereco_servico = $contribuinte->logradouro;
        $obj->uf_servico = $contribuinte->uf;
        $obj->cidade_servico = $contribuinte->cidade;
        $obj->bairro_servico = $contribuinte->bairro;
        $obj->fone_contato_servico = $contribuinte->telefone;
        
        TForm::sendData('form_contrato', $obj);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca servico
 *---------------------------------------------------------------------------------------*/
    public static function onServico($param) //onServico
    {
        if (array_key_exists('id_servico',$param))
        {
            $key = $param['id_servico'];
        }
        else
        {
            return;
        }
        if (!$key)
        {
            return;
        }
        try
        {
            TTransaction::open('freap'); // open a transaction
            $options  = servico::where('id', '=', $key)->load();//busca o serviço
            TTransaction::close(); // close the transaction
            foreach ($options as $op)
            {
                $option = $op;
            }
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
        if(!$option)
        {
            return;
        }
        $obj = self::busca_tipo($key);//Busca no BD os dados do serviço
        //var_dump($obj);
        $ret = self::campo_Habilita($obj->tipo_servico,$obj->hora_virtual);//Atualiza campos (enable/disable)
        $multas = new StdClass;
        $multas = TSession::getValue('servico_info');
        if ($multas->calcula_multas == true || strtoupper($multas->calcula_multas)=='T')
        {
            $msg = 'Este tipo de Serviço permite que a data de Vencimento seja diferente da de Pagamento, com isto pode ';
            $msg.= 'haver um acréscimo de multa e juros caso a data de pagamento seja posterior ao vencimento.';
            $msg.= '<br><br><center>Automaticamente irei preencher ambas datas com o dia de hoje mas fique a vontade para alterar ';
            $msg.= 'com os dados que julgar corretos...</center>';
            new TMessage('info', $msg );
        } 
         $obj = new StdClass;
         $obj->data_vencimento = date('d/m/Y');
         $obj->data_pagamento  = date('d/m/Y');

         TForm::sendData('form_contrato', $obj);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Habilita/Desabilita
 *---------------------------------------------------------------------------------------*/
    public static function campo_Habilita($tipo='0',$key='f')
    {
        switch ($tipo)
        {
            case '1'://Valor Único
                TEntry::disableField('form_contrato','qnt_policial');
                TEntry::disableField('form_contrato','qnt_km');
                TEntry::disableField('form_contrato','qnt_horas');
                TEntry::disableField('form_contrato','data_inicio');
                TEntry::disableField('form_contrato','data_fim');
                break;

            case '2':// PM X (qnt_horas X valor_diaria)
                TEntry::enableField('form_contrato','qnt_policial');
                TEntry::disableField('form_contrato','qnt_km');
                TEntry::enableField('form_contrato','qnt_horas');
                TEntry::disableField('form_contrato','data_inicio');
                TEntry::disableField('form_contrato','data_fim');
                break;
            
            case '3'://qnt_dias X valor_diaria
                TEntry::disableField('form_contrato','qnt_policial');
                TEntry::disableField('form_contrato','qnt_km');
                TEntry::disableField('form_contrato','qnt_horas');
                TEntry::enableField('form_contrato','data_inicio');
                TEntry::enableField('form_contrato','data_fim');
                break;
                
            case '4'://valor base + (qnt_dias X valor_diaria)
                TEntry::disableField('form_contrato','qnt_policial');
                TEntry::disableField('form_contrato','qnt_km');
                TEntry::disableField('form_contrato','qnt_horas');
                TEntry::enableField('form_contrato','data_inicio');
                TEntry::enableField('form_contrato','data_fim');
                break;
                
            case '5'://(PM X (qnt_horas X valor_horas))+ (qnt_km X valor_km)
                TEntry::enableField('form_contrato','qnt_policial');
                TEntry::enableField('form_contrato','qnt_km');
                TEntry::enableField('form_contrato','qnt_horas');
                TEntry::disableField('form_contrato','data_inicio');
                TEntry::disableField('form_contrato','data_fim');
                break;
            
            case '6'://valor base + (qnt_km X valor_km)
                TEntry::disableField('form_contrato','qnt_policial');
                TEntry::enableField('form_contrato','qnt_km');
                TEntry::disableField('form_contrato','qnt_horas');
                TEntry::disableField('form_contrato','data_inicio');
                TEntry::disableField('form_contrato','data_fim');
                break;
            case '7'://qnt_horas X valor_horas
                TEntry::disableField('form_contrato','qnt_policial');
                TEntry::disableField('form_contrato','qnt_km');
                TEntry::enableField('form_contrato','qnt_horas');
                TEntry::disableField('form_contrato','data_inicio');
                TEntry::disableField('form_contrato','data_fim');
                break;                
            default://Valor padrão
                TEntry::disableField('form_contrato','qnt_policial');
                TEntry::disableField('form_contrato','qnt_km');
                TEntry::disableField('form_contrato','qnt_horas');
                TEntry::disableField('form_contrato','data_inicio');
                TEntry::disableField('form_contrato','data_fim');
                break;
        }
        if ($key!='t')
        {
            TCombo::disableField('form_contrato','horas_diurno');
            TCombo::disableField('form_contrato','horas_noturno');
        }
        else if ($key =='t')
        {
            TCombo::enableField('form_contrato','horas_diurno');
            TCombo::enableField('form_contrato','horas_noturno');
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Acha o Tipo do serviço
 *------------------------------------------------------------------------------*/
     public static function busca_tipo($key)
     {
        try
        {
            TTransaction::open('freap'); // open a transaction
            $options  = servico::where('id', '=', $key)->load();//busca o serviço
            TTransaction::close(); // close the transaction
            if($options)
            {
                foreach ($options as $op)
                {
                    $option = $op;
                }
            }
            else
            {
                return false;
            }
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            return false;          
        }
        $obj = new StdClass;
        $obj->valor_base = $option->valor_base;
        $obj->valor_km = $option->valor_km;
        $obj->valor_pm = $option->valor_pm;
        $obj->valor_juros = $option->valor_juros;
        $obj->valor_multa = $option->valor_multa;
        $obj->valor_diaria = $option->valor_diaria;
        $obj->pm_max = $option->pm_max;
        $obj->pm_min = $option->pm_min;
        $obj->diaria_min = $option->diaria_min;
        $obj->diaria_max = $option->diaria_max;
        $obj->tipo_servico = $option->tipo_servico;
        $obj->calcula_multas = $option->calcula_multas;
        $obj->id_servico = $key;
        $obj->hora_virtual = $option->hora_virtual;
        $obj->valor_diurno = $option->valor_diurno;
        $obj->valor_noturno = $option->valor_noturno;
        TSession::setValue('servico_info',$obj);//Armazena os dados do Tipo do Serviço para calculos
        return $obj;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Atualiza calculos
 *------------------------------------------------------------------------------*/
     public function onChangeAction_calculos($param)
     {
        $data = $this->form->getData();
        if (array_key_exists('id_servico',$param))
        {
            $servico = $param['id_servico'];
            //echo "<br>";print_r($param);
        }
        else//Na falta do identificador do serviço
        {
             $ret = '';
             //$obj = new StdClass;
             $data->valor_total = number_format($ret,2,'.','');//Envia o valor totalizado para o formulário
             TForm::sendData('form_contrato', $data);            
             return;
        }
        //$obj = $data;
        $servico = (!$servico) ? '0' : $servico;
        $obj = TSession::getValue('servico_info');
        $key = ($servico!=$obj->id_servico) ? '0' : $obj->tipo_servico;
        $virtual = $obj->hora_virtual;
        $ci = new TFerramentas;
        //echo "<br>".$key;
        switch ($key)
         {
         case '1'://Valor Único
             $ret = $obj->valor_base;
             break;
         case '2'://PM X (qnt_horas X valor_diaria)
             if (self::isValidValue($param['qnt_policial'],$obj->pm_max,$obj->pm_min))//Verifica limite de PMs
             { 
                 $ret = $param['qnt_policial'] *($param['qnt_horas']*$obj->valor_diaria);
             }
             else
             {
                 if (!self::isValidValue($param['qnt_policial'],$obj->pm_max,0))//Excede o PMs?
                 {
                      $ret = $obj->pm_max*($param['qnt_horas']*$obj->valor_diaria);
                      new TMessage ('info', 'Não se pode contratar mais de '.$obj->pm_max.' PMs.');
                 }
                 else
                 {
                     new TMessage ('info', 'A quantidade de Policiais empregados neste serviço não é permitido');
                     $ret = false;
                 }
             }
             if ($ret!=false && ($virtual=='t' || $virtual==true))
             {
                 $tot_ac4 = self::CalculaVirtual($param,$obj);
                 $ret = $ret + $tot_ac4;
             }
             break;
         case '3'://qnt_dias X valor_diaria
             $dias = $ci->diffDatas($param['data_inicio'],$param['data_fim']);
             //echo "<br>".$dias."<br>"; var_dump($obj);
             
             if (self::isValidValue($dias,$obj->diaria_max,$obj->diaria_min))//Verifica se está na faixa permitida
             {
                 $ret = ($dias==false) ? false : $dias*$obj->valor_diaria;
                 //echo "<br> --- ".$ret;
             }
             else
             {
                 if (!self::isValidValue($dias,$obj->diaria_max,0))//Excede o máximo?
                 {
                      $ret = $obj->diaria_max*$obj->valor_diaria;
                      new TMessage ('info', 'Não se pode cobrar por mais de '.$obj->diaria_max.'dias.');
                 }
                 else
                 {
                     if($dias<0)//Possível Erro de datas
                     {
                         new TMessage ('info', 'A quantidade de dias a pagar neste serviço não é permitido');
                         $ret = false;
                         //echo "<br> --- ".$ret;
                     }
                     else
                     {
                         $ret = 0;
                         //echo "<br> --- ".$ret;
                     }
                 }
             }
             break;
         case '4'://valor base + (qnt_dias X valor_diaria)
             $dias = $ci->diffDatas($param['data_inicio'],$param['data_fim']);
             if (self::isValidValue($dias,$obj->diaria_max,$obj->diaria_min))
             {
                 $ret = ($dias==false) ? false : $obj->valor_base + ($dias* $obj->valor_diaria);
             }
             else
             {
                 if (!self::isValidValue($dias,$obj->diaria_max,0))
                 {
                      $ret = ($dias==false) ? false : $obj->valor_base + ($obj->diaria_max* $obj->valor_diaria);
                      new TMessage ('info', 'Não se pode cobrar por mais de '.$obj->diaria_max.'dias.');
                 }
                 else
                 {
                     if($dias<0)
                     {
                         new TMessage ('info', 'A quantidade de dias a pagar neste serviço não é permitido');
                         $ret = false;
                     }
                     else
                     {
                         $ret = 0;
                     }
                 }
             }
             break;
         case '5'://(PM X (qnt_horas X valor_horas))+ (qnt_km X valor_km)
             if (self::isValidValue ($param['qnt_policial'],$obj->pm_max,$obj->pm_min))
             {
                 $ret = ($param['qnt_policial']*($param['qnt_horas']*$obj->valor_diaria))+($param['qnt_km']*$obj->valor_km);//valor_diaria neste contexto assume o papel de horas
             }
             else
             {
                 if (!self::isValidValue($param['qnt_policial'],$obj->pm_max,0))
                 {
                      $ret = ($obj->pm_max*($param['qnt_horas']*$obj->valor_diaria))+($param['qnt_km']*$obj->valor_km);//valor_diaria neste contexto assume o papel de horas
                      new TMessage ('info', 'Não se pode contratar mais de '.$obj->pm_max.' PMs.');
                 }
                 else
                 {
                     new TMessage ('info', 'A quantidade de Policiais empregados neste serviço não é permitido');
                     $ret = false;
                 }
             }
             if ($ret!=false && ($virtual=='t' || $virtual==true))
             {
                 $tot_ac4 = self::calculaVirtual($param,$obj);
                 $ret = $ret + $tot_ac4;
             }
             break;
         case '6'://valor base + (qnt_km X valor_km)
             $ret = $obj->valor_base + ($param['qnt_km']*$obj->valor_km);
             break;
         case '7'://qnt_horas X valor_horas
             $ret = $param['qnt_horas']*$obj->valor_diaria;//valor_diaria neste contexto assume o papel de horas
             break;
         default:
             $ret = false;
             break;
         }
         $ret = (false == $ret) ? 0 : $ret;
         //$obj = new StdClass;
         //$obj->fromArray($param);
         $data->valor_total = number_format($ret,2,'.','');//Envia o valor totalizado para o formulário
         $data->data_inicio = $param['data_inicio'];
         $data->data_fim = $param['data_fim'];
         $data->doc_vinculo = $param['doc_vinculo'];
         
         TForm::sendData('form_contrato', $data);
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Calcula AC4 e se os valores são possíveis
 *------------------------------------------------------------------------------*/     
    public function calculaVirtual ($param,$obj)
    {
        $t_horas = $param['qnt_policial'] * $param['qnt_horas'];
        $v_horas = $param['horas_diurno'] + $param['horas_noturno'];
        if ($t_horas == $v_horas)
        {
            $periodo_d = $param['horas_diurno'] * $obj->valor_diurno;
            $periodo_n = $param['horas_noturno'] * $obj->valor_noturno;
            return $periodo_d + $periodo_n;
        }
        else
        {
            new TMessage ('info',"A quantidade de horas distribuidas entre o período Diurno e Noturno (".$t_horas .") para ".
                            "Pagamento da AC-4 não pode ser diferente do contratado (".$v_horas.")");
            return 0;
        }
        
    }
/*------------------------------------------------------------------------------
 *        Gera 2ª Via do Boleto
 *------------------------------------------------------------------------------*/     
    public function segundaVia ($param)
    {
        $data = $this->form->getData(); // get form data as array
        $result = $data->numero_sefaz;
        $this->form->setData($data); // fill form data
        $key = self::busca_tipo($data->id_servico);
        if ($key)
        {
            self::campo_Habilita($key->tipo_servico);
        }
        else
        {
            self::campo_Habilita('0');
        }
        if (!empty($data->numero_sefaz))
        {
            TButton::disableField('form_contrato','primeiravia');
            TButton::enableField('form_contrato','segundavia');                    
            TButton::disableField('form_contrato','save');
        }
        else
        {
            TButton::enableField('form_contrato','primeiravia');
            TButton::disableField('form_contrato','segundavia');
            TButton::enableField('form_contrato','save');                                    
        }
        
        $message = '<div class="container" style="border-radius: 3px; border: 1px solid black; background-color: lightskyblue;';
        $message.= 'background-image: linear-gradient(to bottom, white, lightskyblue); margin-top: 5%; margin-bottom: 2%; width:90%; height: 100px;">';
        $message.= '   <form action="https://app.sefaz.go.gov.br/arr-www/view/exibeDARE.jsf?codigo='.$result.'" method="Post" ';
        $message.= '      name="showDare" id="showDare" target="_blank">';
        $message.= '      <center><br><label><strong>Clique no Botão abaixo para abrir a 2ª Via de seu Boleto</strong></label><br>';
        $message.= '        <input class="btn btn-success btn-lg" type="submit" value="2ª Via">';
        $message.= '      </center>';
        $message.= '   </form>';
        $message.= '</div>';
        
        TForm::sendData('form_contrato',$data);
        $window = TWindow::create('Pegue Seu Boleto', 0.5, 0.5);
        $window->add($message);
        $window->show();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Valida quantidade em prol dos limites Máximos e Mínimos
 *------------------------------------------------------------------------------*/     
    static function isValidValue ($qnt,$max,$min)
    {
        if ($max == 0 && $min ==0)
        {
            return true;
        }
        if ((($max>0 && $min==0) && $qnt<=$max) || (($max==0 && $min>0) && $qnt>=$min) || (($max>0 && $min>0) && ($qnt>=$min && $qnt<=$max)))
        {
            return true;
        }
        return false;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Valida formulário para salvar. Recebe os dados do Form para validar
 *------------------------------------------------------------------------------*/
    static function isValidSave ($obj)
    {
        $nosave = array();
        $ci = new TFerramentas;
        if (empty($obj->id_contribuinte) || (strlen($obj->id_contribuinte)!=11 && strlen($obj->id_contribuinte)!=14))
        {
            $nosave[] = '<li>É necessário preencher corretamente a identificação do Contribuinte ou CPF ou CNPJ.</li>';
        }
        else
        {
            if ((strlen($obj->id_contribuinte)==11 && !$ci->isValidCPF($obj->id_contribuinte)) || (strlen($obj->id_contribuinte)==14 && !$ci->isValidCNPJ($obj->id_contribuinte)))
            {
                $nosave[] = '<li>O CPF/CNPJ é inválido...</li>';
            } 
        }
        if (empty($obj->razao_social))
        {
            $nosave[] = '<li>Não houve o preenchimento da Razão Social...</li>';
        }
        if (empty($obj->id_servico))
        {
            $nosave[] = '<li>É necessário selecionar um serviço...</ul>';
        }
        if (empty($obj->valor_total))
        {
            $nosave[] = '<li>É necessário calcular o valor do serviço, para isto clique no botão Calcular Valores...</ul>';
        }
        if (empty($obj->doc_vinculo))
        {
            $msg = '<br>É necessário preencher o Documento Vinculado com o que Gerou a necessidade deste Contrato sendo este ';
            $msg.= 'comumente preenchido com o Número de um:<ul><li>Boletim de Ocorrência;</li><li>Guia de Apreenção de veículos;</li>';
            $msg.= '<li>Guia de Recolhimento de veículos</li><li>Ofício ou requerimento</li>Os caso diversos podem ser ainda: PRESENCIAL, CONTATO TELEFÔNICO OU DIVERSO</ul>';
            $nosave[]=$msg;
        }
        return $nosave;

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Gera 1ª Via do Boleto
 *------------------------------------------------------------------------------*/     
    public function primeiraVia ($param)
    {
        try
        {
            $param = $this->form->getData(); // get form data as array
            $relatos = self::isValidSave($param);
            if (!empty($relatos))
            {
                $validar='';
                foreach ($relatos as $relato)
                {
                    if (!empty($relato))
                    {
                        $validar.=$relato.'<br>';
                    }
                }
                throw new Exception ($validar);
            }
            $ferram = new TFerramentas;
            $valor_total = (float)$param->valor_total;
            $valor_total = number_format($valor_total, 2, ',', '.');
            $data_pagamento  = $ferram->corrigeData ($param->data_pagamento);
            $data_vencimento = $ferram->corrigeData ($param->data_vencimento); 
            $razao_social    = $ferram->removeAcentos($param->razao_social);
            $descricao       = $ferram->removeAcentos($param->descricao_servico);
            $doc_vinculo     = $ferram->removeAcentos($param->doc_vinculo);
            $cod_servico     = self::achaCodServico($param->id_servico);
            $contribuinte    = $param->id_contribuinte;
            if (strlen($contribuinte)>11) 
            {
                $cpf = '';
                $cnpj = $contribuinte;
            } 
            else 
            {
                $cpf = $contribuinte;
                $cnpj = '';
            }
            $city     = (empty($param->cidade_servico)) ? 'GOIÂNIA' : $param->cidade_servico;
            $rua      = $param->endereco_servico;
            $bairro   = $param->bairro_servico;
            $endereco = $ferram->removeAcentos($rua . ", ".$bairro." - ".$city);
            $fone     = $ferram->formata_fone($param->fone_contato_servico);
            $cod_municipio = self::achaCodMunicipio($city);
            
            $orgao  = "FREAP/PM";
            $url    = self::rest_dare;
            $boleto = array(
                "siglaOrgaoEmissor"          => $orgao,
                "numeroControleOrgaoEmissor" => "KMGHUOSSQM",
                "codgDetalheReceita"         => (int) $cod_servico,
                "valorOriginal"              => $valor_total,
                //"dataVencimentoTributo"      => $data_vencimento,
                "dataCalcPagamento"          => $data_pagamento,
                "codgTipoDocumentoOrigem"    => 16,//16
                "numrDocumentoOrigem"        => $doc_vinculo,
                "numrCPFContrib"             => $cpf,
                "numrCNPJContrib"            => $cnpj,
                "nomeRazaoSocialContrib"     => $razao_social,  
                "enderecoEmitente"           => $endereco,  
                "codgMunicipioContrib"       => (int)$cod_municipio,//5208707  
                "codgDddTelefoneContrib"     => (int)$fone['ddd'],//62  
                "numrTelefoneContrib"        => (int)$fone['fone']);//
            $data = $boleto;
            $data_string = json_encode($data);
            //new TMessage ('info', var_dump($boleto));
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);        
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'Accept: text/plain')
            );
            $result = (TSession::getValue('ambiente')!='local') ? $result = curl_exec($ch) : $result = 'sucesso';
            if ($result) 
            {
                if (strtoupper(substr($result,0,4))=="ERRO") 
                {
                    throw new Exception ('Falha ao Gerar Boleto...Tente mais tarde.');
                } 
                else 
                {
                    $obj = new StdClass;
                    $obj->numero_sefaz = $result;
                    $obj->id = $param->id;
                    self::saveDare($obj);//Salva o Numero do Dare
                    $obj = $param;
                    $obj->numero_sefaz = $result;
                    TForm::sendData('form_contrato',$obj);//Atualiza o Formulário
                    $key = self::busca_tipo($obj->id_servico);
                    if ($key)
                    {
                        self::campo_Habilita($key->tipo_servico);
                    }
                    else
                    {
                        self::campo_Habilita('0');
                    }
                    if (!empty($obj->numero_sefaz))
                    {
                        TButton::disableField('form_contrato','primeiravia');
                        TButton::enableField('form_contrato','segundavia');                    
                        TButton::disableField('form_contrato','save');
                    }
                    else
                    {
                        TButton::enableField('form_contrato','primeiravia');
                        TButton::disableField('form_contrato','segundavia');
                        TButton::enableField('form_contrato','save');                                    
                    }
                                
                    // creates a string with the form element's values
                    $message = '<div class="container" style="border-radius: 3px; border: 1px solid black; background-color: lightskyblue;';
                    $message.= 'background-image: linear-gradient(to bottom, white, lightskyblue); margin-top: 5%; margin-bottom: 2%; width:90%; height: 100px;">';
                    $message.= '   <form action="https://app.sefaz.go.gov.br/arr-www/view/exibeDARE.jsf?codigo='.$result.'" method="Post" ';
                    $message.= '      name="showDare" id="showDare" target="_blank">';
                    $message.= '      <center><br><label><strong>Clique no Botão abaixo para abrir o Boleto</strong></label><br>';
                    $message.= '        <input class="btn btn-success btn-lg" type="submit" value="Boleto">';
                    $message.= '      </center>';
                    $message.= '   </form>';
                    $message.= '</div>';
                    
                    $window = TWindow::create('Pegue Seu Boleto', 0.5, 0.5);
                    $window->add($message);
                    $window->show();
                }
            } 
            else 
            {
                throw new Exception ('Falha na comunicação com a SEFAZ. Boleto não Gerar Boleto...Tente mais tarde.');            
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData($this->form->getData() ); // keep form data            
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Busca no BD o código do Município
 *------------------------------------------------------------------------------*/
    public function achaCodMunicipio ($param)
    {
        try 
        {
            TTransaction::open('sisacad'); // open a transaction
            $cidades  = cidades::where('nome', '=', strtoupper($param))->load();//busca o cidade
            TTransaction::close(); // close the transaction
            if (empty($cidades))
            {
                $cidade = new StdClass;
                $cidade->id       = '5208707';
                $cidade->nome     = 'GOIÂNIA';
                $cidade->uf       = 'GO';
                $cidade->bairros  = null;
            }
            else
            {
                foreach ($cidades as $cidade)
                {
                    $id = $cidade->id;
                }
            }
        }
        catch (Exception $e) // in case of exception 
        {
            //new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            $cidade = new StdClass;
            $cidade->id       = '5208707';
            $cidade->nome     = 'GOIÂNIA';
            $cidade->uf       = 'GO';
            $cidade->bairros  = null;
        }
        return $cidade->id;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *        Grava o DARE no contrato
 *------------------------------------------------------------------------------*/
    public function saveDare ($param)
    {
        try 
        {
            TTransaction::open('freap'); // open a transaction
            $contrato  = new contrato($param->id);
            $contrato->numero_sefaz = $param->numero_sefaz;
            $contrato->store();
            TTransaction::close(); // close the transaction
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
        return;
    }//Fim Módulo  
/*------------------------------------------------------------------------------
 *        Busca no BD o código do serviço
 *------------------------------------------------------------------------------*/
    public function achaCodServico ($param)
    {
        try 
        {
            TTransaction::open('sisacad'); // open a transaction
            $servicos  = new servico($param);//busca o cidade
            TTransaction::close(); // close the transaction
            if (empty($servicos))
            {
                return false;
            }
            else
            {
                foreach ($servicos as $servico)
                {
                    $cod = $servicos->codigo;
                }
            }
        }
        catch (Exception $e) // in case of exception 
        {
            TTransaction::rollback(); // undo all pending operations
            return false;
        }
        return $servicos->codigo;
    }//Fim Módulo

}//Fim Classe
