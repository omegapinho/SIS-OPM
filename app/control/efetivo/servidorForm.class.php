<?php
/**
 * servidorDependenteForm Master/Detail
 * @author  <your name here>
 */
class servidorForm extends TPage
{
    protected $form; // form
    protected $formFields;
    protected $detail_list;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $fer = new TFerramentas();
        $ci = new TSicadDados();
        
        $this->form = new TForm('form_servidor');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'max-width:700px'; // style
        parent::include_css('app/resources/custom-frame.css');
        
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
        
        $notebook = new TNotebook(700, 420);
        
        // add the notebook inside the form
        $notebook->appendPage('Informações Pessoais'   , $table_pes);
        $notebook->appendPage('Dados Profissionais'    , $table_pro);
        $notebook->appendPage('Características Físicas', $table_car);
        $notebook->appendPage('Endereço'               , $table_loc);
        $notebook->appendPage('Documentação'           , $table_doc);
        $notebook->appendPage('Contatos'               , $table_con);
        $notebook->appendPage('Dependente'             , $table_den);
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Formulário de Cadastro do Servidor');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($notebook);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $this->form->add($table_master);
        
        // master fields
        $id                  = new TEntry('id');
        $codpessoa           = new THidden('codpessoa');
        $codservidor         = new THidden('codservidor');
        $telefoneresidencial = new TEntry('telefoneresidencial');
        $telefonecelular     = new TEntry('telefonecelular');
        $telefonetrabalho    = new TEntry('telefonetrabalho');
        $email               = new TEntry('email');
        $peso                = new TEntry('peso');
        $altura              = new TEntry('altura');
        $codigocorbarba      = new THidden('codigocorbarba');
        $codigotipobarba     = new THidden('codigotipobarba');
        $codigocorbigote     = new THidden('codigocorbigote');
        $codigocorpele       = new TCombo('codigocorpele');
        $codigocorcabelo     = new TCombo('codigocorcabelo');
        $codigocorolho       = new TCombo('codigocorolho');
        $codigomaoqueescreve = new TCombo('codigomaoqueescreve');
        $codigosabenadar     = new THidden('codigosabenadar');
        $codigotipobigode    = new THidden('codigotipobigode');
        $codigotipocabelo    = new TCombo('codigotipocabelo');
        $codigotipoboca      = new THidden('codigotipoboca');
        $codigotipocalvice   = new THidden('codigotipocalvice');
        $codigotiponariz     = new THidden('codigotiponariz');
        $tituloeleitor       = new TEntry('tituloeleitor');
        $zonatituloeleitor   = new TEntry('zonatituloeleitor');
        $secaotituloeleitor  = new TEntry('secaotituloeleitor');

        $ufdotituloeleitoral = new TDBCombo('ufdotituloeleitoral','sicad','estados','sigla','sigla','sigla');
        $estado              = ($ufdotituloeleitoral->getValue()) ? $ufdotituloeleitoral->getValue() : 'GO' ;
        
        $criteria            = new TCriteria;
        $criteria->add (new TFilter ('uf','=',$estado));

        $municipiotituloeleitoral = new TDBCombo('municipiotituloeleitoral','sicad','cidades','nome','nome','nome',$criteria);
        $cnh                      = new TEntry('cnh');
        $codcategoriacnh          = new THidden('codcategoriacnh');
        $categoriacnh             = new TEntry('categoriacnh');
        $dtexpedicaocnh           = new TDate('dtexpedicaocnh');
        $dtvalidadecnh            = new TDate('dtvalidadecnh');
        $ufcnh                    = new TDBCombo('ufcnh','sicad','estados','sigla','sigla','sigla');
        $unidadeid                = new THidden('unidadeid');
        //$unidade = new TDBCombo('unidade','sicad','OPM','nome','nome','nome');
        $unidade                  = new TDBSeekButton('unidade','sicad','form_servidor','OPM','nome','unidadeid','unidade');
        $siglaunidade             = new TEntry('siglaunidade');
        $nome                     = new TEntry('nome');
        $sexo                     = new TCombo('sexo');
        $nomeguerra               = new TEntry('nomeguerra');
        $dtpromocao               = new TDate('dtpromocao');
        
        $criteria                 = new TCriteria;
        $criteria->add (new TFilter ('dominio','=','postograd'));
        $postograd                = new TDBCombo('postograd','sicad','Item','nome','nome','nome',$criteria);
        $patente                  = $postograd->getValue();
        $quadro                   = new TCombo('quadro');
        $lotacao                  = new TEntry('lotacao');
        $funcao                   = new TEntry('funcao');
        $criteria                 = new TCriteria;
        $criteria->add (new TFilter ('dominio','=','STATUS'));
        $status                   = new TDBCombo('status','sicad','Item','nome','nome','ordem',$criteria);
        $situacao                 = new TEntry('situacao');
        $rgmilitar                = new TEntry('rgmilitar');
        $cpf                      = new TEntry('cpf');
        $nomepai                  = new TEntry('nomepai');
        $nomemae                  = new TEntry('nomemae');
        $dtnascimento             = new TDate('dtnascimento');
        $logradouro               = new TEntry('logradouro');
        $numero                   = new TEntry('numero');
        $quadra                   = new TEntry('quadra');
        $lote                     = new TEntry('lote');
        $complemento              = new TEntry('complemento');
        $bairro                   = new TEntry('bairro');
        $codbairro                = new THidden('codbairro');
        $codmunicipio             = new THidden('codmunicipio');
        
        $uf                       = new TDBCombo('uf','sicad','estados','sigla','sigla','sigla');
        $estado                   = ($uf->getValue()) ? $uf->getValue() : 'GO' ;
        $criteria                 = new TCriteria;
        $criteria->add (new TFilter ('uf','=',$estado));
        $municipio                = new TDBCombo('municipio','sicad','cidades','nome','nome','nome',$criteria);
        
        $cep                      = new TEntry('cep');
        $rgcivil                  = new TEntry('rgcivil');
        $dtexpedicaorg            = new TDate('dtexpedicaorg');
        $orgaoexpedicaorg         = new TEntry('orgaoexpedicaorg');
        $ufexpedicaorg            = new TDBCombo('ufexpedicaorg','sicad','estados','sigla','sigla','sigla');
        
        //Novos Itens
        $estadocivil              = new TCombo('estadocivil');
        $reservista               = new TEntry('reservista');
        $orgaoexpedicaoreservista = new TEntry('orgaoexpedicaoreservista');
        $dtexpedicaoreservista    = new TDate('dtexpedicaoreservista');
        $ufnaturalidade           = new TDBCombo('ufnaturalidade','sicad','estados','sigla','sigla','sigla');
        
        $estado                   = ($ufnaturalidade->getValue()) ? $ufnaturalidade->getValue() : 'GO' ;
        $criteria                 = new TCriteria;
        $criteria->add (new TFilter ('uf','=',$estado));
        $naturalidade             = new TDBCombo('naturalidade','sicad','cidades','nome','nome','nome',$criteria);
        $planosaude               = new TEntry('planosaude');
        $escolaridade             = new TEntry('escolaridade');
        $descricaoescolaridade    = new TEntry('descricaoescolaridade');
        $pispasep                 = new TEntry('pispasep');
        $tiposangue               = new TEntry('tiposangue');
        $fatorrh                  = new TEntry('fatorrh');
        $coturnoromaneio          = new TEntry('coturnoromaneio');
        $camisetaromaneio         = new TEntry('camisetaromaneio');
        $shortromaneio            = new TEntry('shotrromaneio');
        $coberturaromaneio        = new TEntry('coberturaromaneio');
        $camisaromaneio           = new TEntry('camisaromaneio');
        $calcaromaneio            = new TEntry('calcaromaneio');
        $lattes                   = new TEntry('lattes');
        $filhos                   = new TEntry('filhos');
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $siglaunidade->setEditable(false);

        //Define tamanho
        $id->setSize(50);
        $nome->setSize(400);
        $nomemae->setSize(400);
        $nomepai->setSize(400);
        $nomeguerra->setSize(200);
        $sexo->setSize(120);
        $dtnascimento->setSize(80);
                
        $siglaunidade->setSize(180);
        $unidade->setSize(400);
        $unidadeid->setSize(50);
        $dtpromocao->setSize(80);
        $quadro->setSize(100);
        $lotacao->setSize(400);
        $funcao->setSize(400);
        $postograd->setSize(200);
        $status->setSize(400);
        $situacao->setSize(400);
        $codpessoa->setSize(50);
        $codservidor->setSize(500);
        
        $cnh->setSize(100);
        $orgaoexpedicaorg->setSize(150);
        $ufexpedicaorg->setSize(50);
        $dtexpedicaorg->setSize(100);
        $dtexpedicaocnh->setSize(80);
        $dtvalidadecnh->setSize(80);
        $ufcnh->setSize(50);
        $rgmilitar->setSize(80);
        $cpf->setSize(120);
        $rgcivil->setSize(80);
        $tituloeleitor->setSize(120);
        $zonatituloeleitor->setSize(50);
        $secaotituloeleitor->setSize(50);
        $municipiotituloeleitoral->setSize(400);
        $ufdotituloeleitoral->setSize(80);
        
        $logradouro->setSize(400);
        $numero->setSize(50);
        $quadra->setSize(50);
        $lote->setSize(50);
        $complemento->setSize(400);
        $bairro->setSize(400);
        $codbairro->setSize(50);
        $municipio->setSize(400);
        $codmunicipio->setSize(50);
        $uf->setSize(80);
        $cep->setSize(120);
        
        $email->setSize(400);
        $telefonecelular->setSize(120);
        $telefoneresidencial->setSize(120);
        $telefonetrabalho->setSize(120);
        //Novos Itens
        $estadocivil->setSize(150);
        $reservista->setSize(100);
        $orgaoexpedicaoreservista->setSize(200);
        $dtexpedicaoreservista->setSize(100);
        $ufnaturalidade->setSize(80);
        $naturalidade->setSize(200);
        $planosaude->setSize(150);
        $escolaridade->setSize(150);
        $descricaoescolaridade->setSize(250);
        $pispasep->setSize(120);
        $tiposangue->setSize(80);
        $fatorrh->setSize(120);
        $coturnoromaneio->setSize(80);
        $camisetaromaneio->setSize(80);
        $shortromaneio->setSize(80);
        $coberturaromaneio->setSize(80);
        $camisaromaneio->setSize(80);
        $calcaromaneio->setSize(80);
        $lattes->setSize(300);
        $filhos->setSize(80);
        
        // detail fields
        $detail_id = new THidden('detail_id');
        $detail_boletiminclusao = new TEntry('detail_boletiminclusao');
        $detail_boletimexclusao = new TEntry('detail_boletimexclusao');
        
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','=','f'));
        $criteria->add (new TFilter ('dominio','=','grauparentesco'));
        
        $detail_grauparentesco = new TDBCombo('detail_grauparentesco','sicad','Item','nome','nome','nome',$criteria);
        $detail_cpf = new TEntry('detail_cpf');
        $detail_dtnascimento = new TDate('detail_dtnascimento');
        $detail_nome = new TEntry('detail_nome');

        //Define Valores Padrão
        $sexo->addItems($ci->caracteristicas_SICAD('sexo'));
        //$postograd->addItems($ci->caracteristicas_SICAD('postograd'));
        $quadro->addItems($ci->caracteristicas_SICAD('quadro_alfa'));
        $codigocorcabelo->addItems($ci->caracteristicas_SICAD('codigocorcabelo'));
        $codigocorpele->addItems($ci->caracteristicas_SICAD('codigocorpele'));
        $codigotipocabelo->addItems($ci->caracteristicas_SICAD('codigotipocabelo'));
        $codigocorolho->addItems($ci->caracteristicas_SICAD('codigocorolho'));
        $codigomaoqueescreve->addItems($ci->caracteristicas_SICAD('codigomaoqueescreve'));
        
        $estadocivil->addItems($fer->lista_estadocivil());

        //Ações
        $change_action_municipio = new TAction(array($this, 'onChangeAction_municipio'));//Popula as cidades com a troca da UF
        $uf->setChangeAction($change_action_municipio);

        $change_action_municipioEleitoral = new TAction(array($this, 'onChangeAction_municipioEleitoral'));//Popula as cidades com a troca da UF
        $ufdotituloeleitoral->setChangeAction($change_action_municipioEleitoral);
        //Mascaras
        $dtnascimento->setMask('dd/mm/yyyy');
        $dtpromocao->setMask('dd/mm/yyyy');
        $dtvalidadecnh->setMask('dd/mm/yyyy');
        $dtexpedicaocnh->setMask('dd/mm/yyyy');
        $detail_dtnascimento->setMask('dd/mm/yyyy');
        // add the fields
        $table_pes->addRowSet(array(new TLabel('Id'), $id ));
        $table_pes->addRowSet(array(new TLabel('Nome'), $nome));
        $table_pes->addRowSet(array(new TLabel('Nome de guerra'), $nomeguerra) );
        $table_pes->addRowSet(array(new TLabel('Pai'), $nomepai) );
        $table_pes->addRowSet(array(new TLabel('Mãe'), $nomemae) );
        $table_pes->addRowSet(array(new TLabel('Sexo'), $sexo,new TLabel('Data de Nascimento'), $dtnascimento) );
        $table_pes->addRowSet(array(new TLabel('UF Natal'), $ufnaturalidade,new TLabel('Naturalidade'), $naturalidade) );
        $table_pes->addRowSet(array(new TLabel('Titularidade'), $escolaridade,new TLabel('Descrição'), $descricaoescolaridade) );
        $table_pes->addRowSet(array(new TLabel('QNT. Filhos'), $filhos) );
        
        $table_pro->addRowSet(array(new TLabel('Unidade'), $unidade,new TLabel(''), $unidadeid) );
        $table_pro->addRowSet(array(new TLabel('Sigla'), $siglaunidade) );
        $table_pro->addRowSet(array(new TLabel('Data da promoção'), $dtpromocao) );
        $table_pro->addRowSet(array(new TLabel('Quadro'), $quadro,new TLabel('Posto/Graduação'), $postograd) );
        $table_pro->addRowSet(array(new TLabel('Lotação'), $lotacao) );
        $table_pro->addRowSet(array(new TLabel('Função'), $funcao) );
        $table_pro->addRowSet(array(new TLabel('Status'), $status) );
        $table_pro->addRowSet(array(new TLabel('Situação'), $situacao) );
        $table_pro->addRowSet(array(new TLabel('Plano de Saúde'), $planosaude) );
        $table_pro->addRowSet(array(new TLabel(''), $codpessoa) );
        $table_pro->addRowSet(array(new TLabel(''), $codservidor) );
        
        $table_car->addRowSet(array(new TLabel('Peso'), $peso,new TLabel('Altura'), $altura) );
        $table_car->addRowSet(array(new TLabel('Cor da Pele'), $codigocorpele,new TLabel('Cor dos Olhos'), $codigocorolho) );
        $table_car->addRowSet(array(new TLabel('Mão Hábil'), $codigomaoqueescreve,new TLabel(''), $codigosabenadar) );
        $table_car->addRowSet(array(new TLabel('Tipo de Cabelo'), $codigotipocabelo,new TLabel('Cor dos Cabelos'), $codigocorcabelo) );
        $table_car->addRowSet(array(new TLabel('Tipo de Sagüíneo'), $tiposangue,new TLabel('Fator RH'), $fatorrh) );
        $table_car->addRowSet(array($lbl  = new TLabel('Nº Camiseta'), $camisetaromaneio,
                                    $lbl2 = new TLabel('Nº Camisa'), $camisaromaneio,
                                    $lbl3 = new TLabel('Nº Calça'), $calcaromaneio,
                                    $lbl4 = new TLabel('Nº Calçado'), $coturnoromaneio) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $lbl3->setFontColor('red');
        $lbl4->setFontColor('red');
        $table_car->addRowSet(array($lbl = new TLabel('Cobertura (boné)'), $coberturaromaneio) );
        $lbl->setFontColor('red');
        
        $table_car->addRowSet(array(new TLabel(''), $codigocorbarba,new TLabel(''), $codigotipobarba) );
        $table_car->addRowSet(array(new TLabel(''), $codigocorbigote,new TLabel(''), $codigotipobigode) );
        $table_car->addRowSet(array(new TLabel(''), $codigotipoboca,new TLabel(''), $codigotiponariz) );
        $table_car->addRowSet(array(new TLabel(''), $codigotipocalvice) );
                
        $table_loc->addRowSet(array(new TLabel('Logradouro'), $logradouro) );
        $table_loc->addRowSet(array(new TLabel('No.'), $numero,new TLabel('QD.'), $quadra,new TLabel('LT.'), $lote) );
        $table_loc->addRowSet(array(new TLabel('Complemento'), $complemento) );
        $table_loc->addRowSet(array(new TLabel('Bairro'), $bairro) );
        $table_loc->addRowSet(array(new TLabel('CEP'), $cep) );
        $table_loc->addRowSet(array(new TLabel('UF'), $uf,new TLabel('Município'), $municipio) );
        $table_loc->addRowSet(array(new TLabel(''), $codbairro,new TLabel(''), $codmunicipio) );
        
        $table_doc->addRowSet(array(new TLabel('RG Militar'), $rgmilitar, new TLabel('CPF'), $cpf) );
        $table_doc->addRowSet(array(new TLabel('RG Civil'), $rgcivil, new TLabel('Expeditor'), $orgaoexpedicaorg,new TLabel('UF'), $ufexpedicaorg,new TLabel('Expedição'), $dtexpedicaorg ) );
        
        $table_doc->addRowSet(array(new TLabel('CNH'), $cnh, new TLabel('Categoria'), $categoriacnh,new TLabel(''), $codcategoriacnh) );
        $table_doc->addRowSet(array(new TLabel('Expedição'), $dtexpedicaocnh, new TLabel('Validade'), $dtvalidadecnh, new TLabel('UF'), $ufcnh) );

        $table_doc->addRowSet(array(new TLabel('Titulo de Eleitor'), $tituloeleitor,new TLabel('Zona'), $zonatituloeleitor, new TLabel('Seção'), $secaotituloeleitor) );
        $table_doc->addRowSet(array(new TLabel('UF'), $ufdotituloeleitoral,new TLabel('Município Eleitoral'), $municipiotituloeleitoral) );
        
        $table_doc->addRowSet(array(new TLabel('Nº da Reservista'), $reservista,new TLabel('Órgão Alistador'), $orgaoexpedicaoreservista) );
        $table_doc->addRowSet(array(new TLabel('Data de Expedição'), $dtexpedicaoreservista) );
        
        $table_con->addRowSet(array(new TLabel('Telefone Residencial'), $telefoneresidencial) );
        $table_con->addRowSet(array(new TLabel('Telefone Celular'), $telefonecelular) );
        $table_con->addRowSet(array(new TLabel('Telefone Trabalho'), $telefonetrabalho) );
        $table_con->addRowSet(array(new TLabel('Email'), $email) );
        $table_con->addRowSet(array(new TLabel('Lattes'), $lattes) );
        
        
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Dados do Dependente para Incluir ou Editar');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        $table_details->addRowSet( '', $detail_id );
        $table_details->addRowSet( array(new TLabel('Nome'), $detail_nome,new TLabel('Parentesco'), $detail_grauparentesco) );        
        $table_details->addRowSet( array(new TLabel('BG Inclusão'), $detail_boletiminclusao,new TLabel('BG Exclusão'), $detail_boletimexclusao) );
        $table_details->addRowSet( array(new TLabel('CPF'), $detail_cpf,new TLabel('DT Nascimento'), $detail_dtnascimento ) );
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('', 'edit', 'left', 50);
        $this->detail_list->addQuickColumn('', 'delete', 'left', 50);
        
        // items
        $this->detail_list->addQuickColumn('BG Inclusão', 'boletiminclusao', 'left', 200);
        $this->detail_list->addQuickColumn('BG Exclusão', 'boletimexclusao', 'left', 200);
        $this->detail_list->addQuickColumn('Parentesco', 'grauparentesco', 'left', 200);
        $this->detail_list->addQuickColumn('CPF', 'cpf', 'left', 200);
        $this->detail_list->addQuickColumn('DT Nasc.', 'dtnascimento', 'left', 100);
        $this->detail_list->addQuickColumn('Nome', 'nome', 'left', 200);
        $this->detail_list->createModel();
        
        $row = $table_detail->addRow();
        $row->addCell($this->detail_list);
        
        //$table_den->addRowSet($frame_details);
        $table_den->addRowSet($table_detail);
        
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
        $return_button->setAction(new TAction(array($this, 'onBack')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define form fields
        $this->formFields   = array($id, $nome,$cpf,$nomeguerra,$nomemae,$nomepai,$telefonecelular,$dtnascimento,
                                    $telefoneresidencial,$telefonetrabalho,$email,$peso,$altura,$sexo,
                                    $unidade,$unidadeid,$status,$situacao,$lotacao,$siglaunidade,
                                    $codigocorbarba,$codigocorbigote,$codigocorcabelo,$codigocorolho,$codigocorpele,
                                    $codigomaoqueescreve,$codigosabenadar,$codigotipobarba,$codigotipobigode,$codigotipocabelo,
                                    $codigotipocalvice,$codigotiponariz,$codmunicipio,$codpessoa,$codservidor,$dtpromocao,
                                    $logradouro,$numero,$quadra,$lote,$complemento,$bairro,$municipio,$uf,$codbairro,
                                    $cnh,$categoriacnh,$ufcnh,$codcategoriacnh,$dtexpedicaocnh,$dtvalidadecnh,
                                    $tituloeleitor,$municipiotituloeleitoral,$secaotituloeleitor,$zonatituloeleitor,$ufdotituloeleitoral,
                                    $cep,$rgcivil,$rgmilitar,$orgaoexpedicaorg,$ufexpedicaorg,$postograd,$funcao,$quadro,
                                    $detail_boletiminclusao,$detail_boletimexclusao,$detail_grauparentesco,
                                    $detail_cpf,$detail_dtnascimento,$detail_nome);
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $save_button;
        $this->formFields[] = $new_button;
        $this->formFields[] = $return_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );
        
        $table_master->addRowSet( array($save_button, $new_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'servidorList'));
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
            TTransaction::open('sicad');
            $data = $this->form->getData();
            
            /** validation sample
            if (! $data->fieldX)
                throw new Exception('The field fieldX is required');
            **/
            
            $items = TSession::getValue(__CLASS__.'_items');
            $key = empty($data->detail_id) ? 'X'.mt_rand(1000000000, 1999999999) : $data->detail_id;
            
            $items[ $key ] = array();
            $items[ $key ]['id'] = $key;
            $items[ $key ]['boletiminclusao'] = $data->detail_boletiminclusao;
            $items[ $key ]['boletimexclusao'] = $data->detail_boletimexclusao;
            $items[ $key ]['grauparentesco'] = $data->detail_grauparentesco;
            $items[ $key ]['cpf'] = $data->detail_cpf;
            $items[ $key ]['dtnascimento'] = $data->detail_dtnascimento;
            $items[ $key ]['nome'] = $data->detail_nome;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_boletiminclusao = '';
            $data->detail_boletimexclusao = '';
            $data->detail_grauparentesco = '';
            $data->detail_cpf = '';
            $data->detail_dtnascimento = '';
            $data->detail_nome = '';
            
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
        $data->detail_boletiminclusao = $item['boletiminclusao'];
        $data->detail_boletimexclusao = $item['boletimexclusao'];
        $data->detail_grauparentesco = $item['grauparentesco'];
        $data->detail_cpf = $item['cpf'];
        $data->detail_dtnascimento = $item['dtnascimento'];
        $data->detail_nome = $item['nome'];
        
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
            $data->detail_boletiminclusao = '';
            $data->detail_boletimexclusao = '';
            $data->detail_grauparentesco = '';
            $data->detail_cpf = '';
            $data->detail_dtnascimento = '';
            $data->detail_nome = '';
        
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
                $item->id = $list_item['id'];
                $item->boletiminclusao = $list_item['boletiminclusao'];
                $item->boletimexclusao = $list_item['boletimexclusao'];
                $item->grauparentesco = $list_item['grauparentesco'];
                $item->cpf = $list_item['cpf'];
                $item->dtnascimento = $list_item['dtnascimento'];
                $item->nome = $list_item['nome'];
                
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
            TTransaction::open('sicad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new servidor($key);
                $items  = dependente::where('servidor_id', '=', $key)->load();
                
                $session_items = array();
                foreach( $items as $item )
                {
                    $item_key = $item->id;
                    $session_items[$item_key] = $item->toArray();
                    $session_items[$item_key]['id'] = $item->id;
                    $session_items[$item_key]['boletiminclusao'] = $item->boletiminclusao;
                    $session_items[$item_key]['boletimexclusao'] = $item->boletimexclusao;
                    $session_items[$item_key]['grauparentesco'] = $item->grauparentesco;
                    $session_items[$item_key]['cpf'] = $item->cpf;
                    $session_items[$item_key]['dtnascimento'] = TDate::date2br($item->dtnascimento);
                    $session_items[$item_key]['nome'] = $item->nome;
                }
                TSession::setValue(__CLASS__.'_items', $session_items);
                //Formata a data para d/m/YYYY
                if ($object->dtnascimento)
                    $object->dtnascimento = TDate::date2br($object->dtnascimento);
                if ($object->dtexpedicaocnh)
                    $object->dtexpedicaocnh = TDate::date2br($object->dtexpedicaocnh);
                if ($object->dtvalidadecnh)
                    $object->dtvalidadecnh = TDate::date2br($object->dtvalidadecnh);
                if ($object->dtpromocao)
                    $object->dtpromocao = TDate::date2br($object->dtpromocao);
                $this->form->setData($object); // fill the form with the active record data
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
        $data = $this->form->getData();
        $data->siglaunidade = self::get_unidade($data->unidadeid);
        try
        {
            // open a transaction with database
            TTransaction::open('sicad');
            if ($data->dtnascimento)
                $data->dtnascimento   = TDate::date2us($data->dtnascimento);
            if ($data->dtexpedicaocnh)
                $data->dtexpedicaocnh = TDate::date2us($data->dtexpedicaocnh);
            if ($data->dtvalidadecnh)
                $data->dtvalidadecnh  = TDate::date2us($data->dtvalidadecnh);
            if ($data->dtpromocao)
                $data->dtpromocao     = TDate::date2us($data->dtpromocao);
            $master = new servidor;
            $master->fromArray( (array) $data);
            $this->form->validate(); // form validation
            $master->store(); // save master object
            // delete details
            $old_items = dependente::where('servidor_id', '=', $master->id)->delete();
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            if( $items )
            {
                foreach( $items as $item )
                {
                    $detail = new dependente();
                    $detail->boletiminclusao  = $item['boletiminclusao'];
                    $detail->boletimexclusao  = $item['boletimexclusao'];
                    $detail->grauparentesco   = $item['grauparentesco'];
                    $detail->cpf              = $item['cpf'];
                    if ($detail->dtnascimento) 
                        $detail->dtnascimento     = TDate::date2us($item['dtnascimento']);
                    $detail->nome             = $item['nome'];
                    $detail->servidor_id      = $master->id;
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
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Retorno a Listagem
 *---------------------------------------------------------------------------------------*/
    public function onBack ()
    {
        TApplication::loadPage('servidorList');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca cidades conforme UF - Residência
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_municipio($param)
    {
        if (array_key_exists('uf',$param))
        {
            $key = $param['uf'];
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
                $lista = array();
                foreach ($options as $option)
                {
                    $lista[$option->nome] = $option->nome;
                    
                }
                TDBCombo::reload('form_servidor', 'municipio', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca cidades conforme UF - Título Eleitoral
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_municipioEleitoral($param)
    {
        if (array_key_exists('ufdotituloeleitoral',$param))
        {
            $key = $param['ufdotituloeleitoral'];
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
                $lista = array();
                foreach ($options as $option)
                {
                    $lista[$option->nome] = $option->nome;
                    
                }
                TDBCombo::reload('form_servidor', 'municipiotituloeleitoral', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca sigla
 *---------------------------------------------------------------------------------------*/
    public static function get_unidade($key)
    {
        try
        {
                TTransaction::open('sicad'); // open a transaction
                $options  = OPM::where('id', '=', $key)->load();//Lista OPM
                TTransaction::close(); // close the transaction
                foreach ($options as $option)
                {
                    $sigla = $option->sigla;
                }
                return $sigla;
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
        return false;

    }//Fim Módulo
    
}//Fim Classe
