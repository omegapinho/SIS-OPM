<?php
/**
 * servidorDependenteForm Master/Detail
 * @author  <your name here>
 */
class servidor_novoForm extends TPage
{
    protected $form; // form
    protected $formFields;
    protected $detail_list;
    protected $table_details;
    protected $detail_row;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
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
        $table_den    = new TTable;//Social
        
        $notebook = new TNotebook(700, 420);
        
        // add the notebook inside the form
        $notebook->appendPage('Informações Pessoais'   , $table_pes);
        $notebook->appendPage('Documentação'           , $table_doc);
        $notebook->appendPage('Características Físicas', $table_car);
        $notebook->appendPage('Concurso/Escolaridade'  , $table_pro);
        $notebook->appendPage('Endereço'               , $table_loc);
        $notebook->appendPage('Contatos'               , $table_con);
        $notebook->appendPage('Social'                 , $table_den);
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Formulário de Cadastro do Servidor');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($notebook);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $this->form->add($table_master);
        
        // master fields
        // Informações Pessoais
        $id                  = new TEntry('id');
        $nome                = new TEntry('nome');
        $sexo                = new TCombo('sexo');
        $nomeguerra          = new TEntry('nomeguerra');
        $nomepai             = new TEntry('nomepai');
        $nomemae             = new TEntry('nomemae');
        $dtnascimento        = new TDate('dtnascimento');
        $estadocivil         = new TCombo('estadocivil');
        
        //Características Físicas
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
        //Romaneio
        $romaneio_calcado    = new TEntry('romaneio_calcado');
        $romaneio_camiseta   = new TCombo('romaneio_camiseta');
        $romaneio_camisa     = new TCombo('romaneio_camisa');
        $romaneio_calca      = new TCombo('romaneio_calca');
        $romaneio_chapeu     = new TCombo('romaneio_chapeu');
        
        //Documentos
        $rgmilitar = new TEntry('rgmilitar');
        $cpf = new TEntry('cpf');
        $rgcivil = new TEntry('rgcivil');
        $orgaoexpedicaorg = new TEntry('orgaoexpedicaorg');
        $ufexpedicaorg = new TDBCombo('ufexpedicaorg','sicad','estados','sigla','sigla','sigla');
        $ufnatural = new TDBCombo('ufexpedicaorg','sicad','estados','sigla','sigla','sigla');
        $tituloeleitor       = new TEntry('tituloeleitor');
        $zonatituloeleitor   = new TEntry('zonatituloeleitor');
        $secaotituloeleitor  = new TEntry('secaotituloeleitor');
        $ufdotituloeleitoral = new TDBCombo('ufdotituloeleitoral','sicad','estados','sigla','sigla','sigla');

        $estado = ($ufdotituloeleitoral->getValue()) ? $ufdotituloeleitoral->getValue() : 'GO' ;
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('uf','=',$estado));
        $municipiotituloeleitoral = new TDBCombo('municipiotituloeleitoral','sicad','cidades','nome','nome','nome',$criteria);
        
        $cnh                 = new TEntry('cnh');
        $codcategoriacnh     = new THidden('codcategoriacnh');
        $categoriacnh        = new TCombo('categoriacnh');
        $dtexpedicaocnh      = new TDate('dtexpedicaocnh');
        $dtvalidadecnh       = new TDate('dtvalidadecnh');
        $ufcnh               = new TDBCombo('ufcnh','sicad','estados','sigla','sigla','sigla');

        //Profissionais
        $unidadeid           = new THidden('unidadeid');
        $unidade             = new TDBSeekButton('unidade','sicad','form_servidor','OPM','nome','unidadeid','unidade');
        $siglaunidade        = new TEntry('siglaunidade');
        $dtpromocao          = new TDate('dtpromocao');
        /*$criteria = new TCriteria;
        $criteria->add (new TFilter ('dominio','=','postograd'));
        $postograd = new TDBCombo('postograd','sicad','Item','nome','nome','nome',$criteria);*/
        $postograd             = new TCombo('postograd');
        $quadro                = new TCombo('quadro');
        $lotacao               = new THidden('lotacao');
        $funcao                = new THidden('funcao');
        /*$criteria = new TCriteria;
        $criteria->add (new TFilter ('dominio','=','STATUS'));
        $status = new TDBCombo('status','sicad','Item','nome','nome','ordem',$criteria);*/
        $status                = new TEntry('status');
        $situacao              = new THidden('situacao');
        $orgaoorigem_id        = new THidden('orgaoorigem_id');
        $importado             = new THidden('importado');
        $senha                 = new THidden('senha');
        $educacao_escolaridade = new THidden('educacao_escolaridade');
        $educacao_graduacao    = new THidden('educacao_graduacao');

        //Endereço
        $logradouro = new TEntry('logradouro');
        $numero = new TEntry('numero');
        $quadra = new TEntry('quadra');
        $lote = new TEntry('lote');
        $complemento = new TEntry('complemento');
        $bairro = new TEntry('bairro');
        $codbairro = new THidden('codbairro');
        $codmunicipio = new THidden('codmunicipio');
        $uf = new TDBCombo('uf','sicad','estados','sigla','sigla','sigla');
        $estado = ($uf->getValue()) ? $uf->getValue() : 'GO' ;
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('uf','=',$estado));
        $municipio = new TDBCombo('municipio','sicad','cidades','nome','nome','nome',$criteria);
        $cep = new TEntry('cep');

        //Contatos
        $telefoneresidencial = new TEntry('telefoneresidencial');
        $telefonecelular     = new TEntry('telefonecelular');
        $telefonetrabalho    = new TEntry('telefonetrabalho');
        $email               = new TEntry('email');
        
        //Social
        $social_residencia                 = new TCombo('social_residencia');
        $social_residencia_tipo            = new TCombo('social_residencia_tipo');
        $social_esporte                    = new TCombo('social_esporte');
        $social_leitura                    = new TCombo('social_leitura');
        $social_plano_saude                = new TCombo('social_plano_saude');
        $social_experiencia_profissional   = new TText('social_experiencia_profissional');
        $social_quantidade_filhos          = new TEntry('social_quantidade_filhos');
        
        //Escolaridade
        $frame_details = new TFrame(600,220);
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Escolaridade');
        
        // creates the scroll panel
        $scroll = new TScroll;
        $scroll->setSize('100%',180);
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $scroll->add($this->table_details);
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('Opções') );
        $row->addCell( new TLabel('Escolaridade') );        
        $row->addCell( new TLabel('Descrição da Graduação') );
        
        $this->detail_row = 0;
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }

        //Define tamanho
        $id->setSize(50);
        $nome->setSize(400);
        $nomemae->setSize(400);
        $nomepai->setSize(400);
        $nomeguerra->setSize(200);
        $sexo->setSize(120);
        $dtnascimento->setSize(80);
        $estadocivil->setSize(200);
                
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
        
        $cnh->setSize(120);
        $categoriacnh->setSize(80);
        $orgaoexpedicaorg->setSize(150);
        $ufexpedicaorg->setSize(50);
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
        
        $peso->setSize(80);
        $altura->setSize(80);
        $romaneio_calca->setSize(80);
        $romaneio_calcado->setSize(80);
        $romaneio_camisa->setSize(80);
        $romaneio_camiseta->setSize(80);
        $romaneio_chapeu->setSize(80);
        
        $email->setSize(400);
        $telefonecelular->setSize(120);
        $telefoneresidencial->setSize(120);
        $telefonetrabalho->setSize(120);
        
        $social_esporte->setSize(120);
        $social_experiencia_profissional->setSize(650);
        $social_leitura->setSize(120);
        $social_quantidade_filhos->setSize(120);
        $social_residencia->setSize(120);
        $social_residencia_tipo->setSize(120);

        //Define Valores Combo
        $ci = new TSicadDados();
        $fer = new TFerramentas();
        
        $sexo->addItems($ci->caracteristicas_SICAD('sexo'));
        $quadro->addItems($ci->caracteristicas_SICAD('quadro_alfa'));
        $codigocorcabelo->addItems($ci->caracteristicas_SICAD('codigocorcabelo'));
        $codigocorpele->addItems($ci->caracteristicas_SICAD('codigocorpele'));
        $codigotipocabelo->addItems($ci->caracteristicas_SICAD('codigotipocabelo'));
        $codigocorolho->addItems($ci->caracteristicas_SICAD('codigocorolho'));
        $codigomaoqueescreve->addItems($ci->caracteristicas_SICAD('codigomaoqueescreve'));
        $romaneio_calca->addItems($ci->caracteristicas_SICAD('romaneio_roupa'));
        $romaneio_camisa->addItems($ci->caracteristicas_SICAD('romaneio_roupa'));
        $romaneio_camiseta->addItems($ci->caracteristicas_SICAD('romaneio_tamanho'));
        $romaneio_chapeu->addItems($ci->caracteristicas_SICAD('romaneio_tamanho'));
        $postograd->addItems(array('307'=>'Aluno Soldado','267'=>'Cadete 1º Ano'));
        $categoriacnh->addItems($ci->caracteristicas_SICAD('categoriacnh'));
        
        $social_leitura->addItems($fer->lista_social_leitura());
        $social_plano_saude->addItems($fer->lista_social_plano_saude());
        $social_residencia->addItems($fer->lista_social_moradia());
        $social_residencia_tipo->addItems($fer->lista_social_tipo_moradia());
        $social_esporte->addItems($fer->lista_social_esporte());
        $estadocivil->addItems($fer->lista_estadocivil());
        //$educacao_escolaridade->addItems($fer->lista_escolaridade());
        
        //Valores Pre-definidos
        $quadro->setValue('QPPM');
        $postograd->setValue('307');
        $status->setValue('Aprovado em Concurso');
        $orgaoorigem_id->setValue('1');

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
        $peso->setMask('999.99');
        $altura->setMask('999.99');
        $romaneio_calcado->setMask('99');
        $social_quantidade_filhos->setMask('9');
        $telefonecelular->setMask('(99)999999999');
        $telefoneresidencial->setMask('(99)999999999');
        $telefonetrabalho->setMask('(99)999999999');
        
        //Bloqueios
        $quadro->setEditable(false);
        $status->setEditable(false);
        
        //Validações
        $nome->addValidation('Nome',new TRequiredValidator);
        $sexo->addValidation('Sexo',new TRequiredValidator);
        $estadocivil->addValidation('Estado Civil',new TRequiredValidator);
        $dtnascimento->addValidation('Data de Nascimento',new TDateValidator, array('dd/mm/yyyy'));
        $cpf->addValidation('CPF',new TCPFValidator);
        $peso->addValidation('Peso em Kilos',new TRequiredValidator);
        $altura->addValidation('Altura em Metros',new TRequiredValidator);
        $nomemae->addValidation('Nome da Mãe',new TRequiredValidator);
        $codigocorpele->addValidation('Cor da Pele',new TRequiredValidator);
        $tituloeleitor->addValidation('Número do Título de Eleitor',new TRequiredValidator);
        $zonatituloeleitor->addValidation('Zona Eleitoral',new TRequiredValidator);
        $secaotituloeleitor->addValidation('Seção Eleitoral',new TRequiredValidator);
        $municipiotituloeleitoral->addValidation('Nome',new TRequiredValidator);
        $ufdotituloeleitoral->addValidation('Cidade onde vota',new TRequiredValidator);
        $codigomaoqueescreve->addValidation('Mão que escreve',new TRequiredValidator);
        $telefonecelular->addValidation('Celular',new TRequiredValidator);
        $email->addValidation('Email',new TEmailValidator);
        $logradouro->addValidation('Logradouro onde reside',new TRequiredValidator);
        $bairro->addValidation('Bairro onde reside',new TRequiredValidator);
        $municipio->addValidation('Município onde reside',new TRequiredValidator);
        $cep->addValidation('CEP onde reside',new TRequiredValidator);
        $romaneio_calca->addValidation('Nº da Calça ',new TRequiredValidator);
        $romaneio_calcado->addValidation('Nº do Calçado',new TRequiredValidator);
        $romaneio_camisa->addValidation('Nº da Camisa',new TRequiredValidator);
        $romaneio_camiseta->addValidation('Tamanho da Camiseta',new TRequiredValidator);
        $romaneio_chapeu->addValidation('Circunferência da Cabeça',new TRequiredValidator);
        $social_esporte->addValidation('Esporte predileto',new TRequiredValidator);
        $social_experiencia_profissional->addValidation('Relato sobre sua experiência profissional',new TRequiredValidator);
        $social_leitura->addValidation('Hábito de leitura',new TRequiredValidator);
        $social_plano_saude->addValidation('Plano de saúde',new TRequiredValidator);
        $social_quantidade_filhos->addValidation('Quantidade de Filhos',new TRequiredValidator);
        $social_residencia->addValidation('Mora com quem',new TRequiredValidator);
        $social_residencia_tipo->addValidation('Tipo de habitação que usa',new TRequiredValidator);
        //$educacao_escolaridade->addValidation('Escolaridade',new TRequiredValidator);
        //$educacao_graduacao->addValidation('Graduação que possui',new TRequiredValidator);
        $postograd->addValidation('Posto/Graduação que foi admitido',new TRequiredValidator);
        
        // add the fields
        $table_pes->addRowSet(array(new TLabel('Id'), $id ));
        $table_pes->addRowSet(array($lbl = new TLabel('Nome'), $nome));
        $lbl->setFontColor('red');
        $table_pes->addRowSet(array(new TLabel('Nome de guerra'), $nomeguerra) );
        $table_pes->addRowSet(array(new TLabel('Pai'), $nomepai) );
        $table_pes->addRowSet(array($lbl = new TLabel('Mãe'), $nomemae) );
        $lbl->setFontColor('red');
        $table_pes->addRowSet(array($lbl = new TLabel('Sexo'), $sexo,$lbl2 = new TLabel('Data de Nascimento'), $dtnascimento) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $table_pes->addRowSet(array($lbl = new TLabel('Estado Civil'), $estadocivil) );
        $lbl->setFontColor('red');
        
        $table_pro->addRowSet(array(new TLabel('Quadro'), $quadro,$lbl = new TLabel('Posto/Graduação'), $postograd) );
        $lbl->setFontColor('red');
        $table_pro->addRowSet(array(new TLabel('Status'), $status, new TLabel(''),$orgaoorigem_id) );
        $table_pro->addRowSet(array(new TLabel(''), $senha,new TLabel(''),$importado) );
        $table_pro->addRowSet(array($frame_details) );

        $table_car->addRowSet(array($lbl = new TLabel('Peso'),$peso,$lbl2 = new TLabel('Altura'), $altura, new TLabel('Mão Hábil'), $codigomaoqueescreve) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');        
        $table_car->addRowSet(array($lbl = new TLabel('Cor da Pele'), $codigocorpele,new TLabel('Cor dos Olhos'), $codigocorolho) );
        $lbl->setFontColor('red');
        $table_car->addRowSet(array(new TLabel('Tipo de Cabelo'), $codigotipocabelo,new TLabel('Cor dos Cabelos'), $codigocorcabelo) );
        $table_car->addRowSet(array($lbl  = new TLabel('Nº Camiseta'), $romaneio_camiseta,
                                    $lbl2 = new TLabel('Nº Camisa'), $romaneio_camisa,
                                    $lbl3 = new TLabel('Nº Calça'), $romaneio_calca,
                                    $lbl4 = new TLabel('Nº Calçado'), $romaneio_calcado) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $lbl3->setFontColor('red');
        $lbl4->setFontColor('red');
        $table_car->addRowSet(array($lbl = new TLabel('Cobertura (boné)'), $romaneio_chapeu) );
        $lbl->setFontColor('red');
        $table_car->addRowSet(array(new TLabel(''), $codigocorbarba,new TLabel(''), $codigotipobarba) );
        $table_car->addRowSet(array(new TLabel(''), $codigocorbigote,new TLabel(''), $codigotipobigode) );
        $table_car->addRowSet(array(new TLabel(''), $codigotipoboca,new TLabel(''), $codigotiponariz) );
        $table_car->addRowSet(array(new TLabel(''), $codigotipocalvice, new TLabel(''), $codigosabenadar) );

        $table_loc->addRowSet(array($lbl = new TLabel('Logradouro'), $logradouro) );
        $lbl->setFontColor('red');
        $table_loc->addRowSet(array(new TLabel('No.'), $numero,new TLabel('QD.'), $quadra,new TLabel('LT.'), $lote) );
        $table_loc->addRowSet(array(new TLabel('Complemento'), $complemento) );
        $table_loc->addRowSet(array($lbl = new TLabel('Bairro'), $bairro) );
        $lbl->setFontColor('red');
        $table_loc->addRowSet(array(new TLabel('CEP'), $cep) );
        $table_loc->addRowSet(array(new TLabel('UF'), $uf,$lbl = new TLabel('Município'), $municipio) );
        $lbl->setFontColor('red');
        $table_loc->addRowSet(array(new TLabel(''), $codbairro,new TLabel(''), $codmunicipio) );
        
        $table_doc->addRowSet(array(new TLabel('RG Militar'), $rgmilitar,$lbl = new TLabel('CPF'), $cpf) );
        $lbl->setFontColor('red');
        $table_doc->addRowSet(array(new TLabel('RG Civil'), $rgcivil, new TLabel('Expeditor'), $orgaoexpedicaorg,new TLabel('UF'), $ufexpedicaorg) );
        $table_doc->addRowSet(array(new TLabel('CNH'), $cnh, new TLabel('Categoria'), $categoriacnh,new TLabel(''), $codcategoriacnh) );
        $table_doc->addRowSet(array(new TLabel('Expedição'), $dtexpedicaocnh, new TLabel('Validade'), $dtvalidadecnh, new TLabel('UF'), $ufcnh) );
        $table_doc->addRowSet(array($lbl = new TLabel('Titulo de Eleitor'), $tituloeleitor,$lbl2 = new TLabel('Zona'), $zonatituloeleitor, $lbl3 = new TLabel('Seção'), $secaotituloeleitor) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $lbl3->setFontColor('red');
        $table_doc->addRowSet(array($lbl = new TLabel('UF'), $ufdotituloeleitoral,$lbl2 = new TLabel('Município Eleitoral'), $municipiotituloeleitoral) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $table_con->addRowSet(array($lbl = new TLabel('Telefone Residencial'), $telefoneresidencial) );
        $lbl->setFontColor('red');
        $table_con->addRowSet(array($lbl = new TLabel('Telefone Celular'), $telefonecelular) );
        $lbl->setFontColor('red');
        $table_con->addRowSet(array($lbl = new TLabel('Telefone Trabalho'), $telefonetrabalho) );
        $lbl->setFontColor('red');
        $table_con->addRowSet(array($lbl = new TLabel('Email'), $email) );
        $lbl->setFontColor('red');
        
        //Social
        $table_den->addRowSet(array($lbl  = new TLabel('Mora com ?'), $social_residencia, 
                                    $lbl2 = new TLabel('Nossa residência é?'), $social_residencia_tipo) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $table_den->addRowSet(array($lbl  = new TLabel('Esporte favorito'), $social_esporte, 
                                    $lbl2 = new TLabel('Habito de Leitura'), $social_leitura) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $table_den->addRowSet(array($lbl  = new TLabel('Plano de Saúde'), $social_plano_saude, 
                                    $lbl2 = new TLabel('Qnt filhos'), $social_quantidade_filhos) );
        $lbl->setFontColor('red');
        $lbl2->setFontColor('red');
        $table_den->addRowSet(array($lbl = new TLabel('Abaixo, referencie onde trabalhou e sua função')) );
        $lbl->setFontColor('red');
        $table_den->addRowSet(array($social_experiencia_profissional) );
        $table_den->addRowSet(array(new TLabel(''), $educacao_escolaridade, 
                                    new TLabel(''), $educacao_graduacao) );


        //Informações Úteis
        $social_experiencia_profissional->setTip('<h4>Siga esse exemplo:</h4><br>Mercearia da Tia Lu: Balconista,<br>Armarinhos Agulha de Ouro: Costureiro... ');
        $romaneio_chapeu->setTip('Medida para uso nos diversos tipos de cobertura a serem usados pelo militar como gorro com pala(boné),'.
                                  '<br>gorro sem pala(bibico) etc.');
        $ddd = 'Não esqueça o DDD';
        $telefonecelular->setTip($ddd);
        $telefoneresidencial->setTip($ddd);
        $telefonetrabalho->setTip($ddd);
        $email->setTip('Entre com seu email de uso pessoal (de preferência).');  
       
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // define form fields
        $this->formFields   = array($id, $nome,$cpf,$nomeguerra,$nomemae,$nomepai,$telefonecelular,$dtnascimento,
                                    $telefoneresidencial,$telefonetrabalho,$email,$peso,$altura,$sexo,$estadocivil,
                                    $unidade,$unidadeid,$status,$situacao,$lotacao,$siglaunidade,$orgaoorigem_id,
                                    $codigocorbarba,$codigocorbigote,$codigocorcabelo,$codigocorolho,$codigocorpele,
                                    $codigomaoqueescreve,$codigosabenadar,$codigotipobarba,$codigotipobigode,$codigotipocabelo,
                                    $codigotipocalvice,$codigotiponariz,$codmunicipio,$dtpromocao,
                                    $logradouro,$numero,$quadra,$lote,$complemento,$bairro,$municipio,$uf,$codbairro,
                                    $cnh,$categoriacnh,$ufcnh,$codcategoriacnh,$dtexpedicaocnh,$dtvalidadecnh,
                                    $tituloeleitor,$municipiotituloeleitoral,$secaotituloeleitor,$zonatituloeleitor,$ufdotituloeleitoral,
                                    $cep,$rgcivil,$rgmilitar,$orgaoexpedicaorg,$ufexpedicaorg,$postograd,$funcao,$quadro,
                                    $romaneio_calca,$romaneio_calcado,$romaneio_camisa,$romaneio_camiseta,$romaneio_chapeu,
                                    $importado,$senha,
                                    $social_esporte,$social_experiencia_profissional,$social_leitura,$social_plano_saude,
                                    $social_quantidade_filhos,$social_residencia,$social_residencia_tipo,
                                    $educacao_escolaridade,$educacao_graduacao
                                    );
        $this->formFields[] = $save_button;
        $this->form->setFields( $this->formFields );
        
        $table_master->addRowSet( array($save_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'servidorList'));
        $container->add($this->form);
        parent::add($container);
    }
/*---------------------------------------------------------------------------------------
 *  Rotina Limpar
 *---------------------------------------------------------------------------------------*/
    public function onClear($param)
    {
        /*$this->form->clear(TRUE);
        TSession::setValue(__CLASS__.'_items', array());
        $this->onReload( $param );*/
        
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
/*---------------------------------------------------------------------------------------
 *  Rotina Recarregar
 *---------------------------------------------------------------------------------------*/
    /*public function onReload($param)
    {
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        $data = $this->form->getData();

        $this->loaded = TRUE;
    }//Fim Módulo*/
/*---------------------------------------------------------------------------------------
 *  Rotina Editar
 *---------------------------------------------------------------------------------------*/
    public function onEdit($param)
    {
        $id = TSession::getValue('keyuser');
        if (!empty($id))
        {
            $param['key']=$id;
        }
        else
        {
            AdiantiCoreApplication::gotoPage('PublicView');
            exit;
        }
        
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new servidor_novo($key);

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
                
                $items  = servidor_novo_escolaridade::where('servidor_novo_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                //var_dump($items);
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
                
                //$this->onReload( $param ); // reload items list
                TTransaction::close(); // close transaction
            }
            else
            {
                $this->form->clear(TRUE);
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
 *  Rotina Salvar
 *---------------------------------------------------------------------------------------*/
    public function onSave($param)
    {
        $data = $this->form->getData();
        //$data->siglaunidade = self::get_unidade($data->unidadeid);
        try
        {
            // open a transaction with database
            TTransaction::open('sisacad');
            $this->form->validate(); // form validation
            if ($data->dtnascimento)
                $data->dtnascimento   = TDate::date2us($data->dtnascimento);
            if ($data->dtexpedicaocnh)
                $data->dtexpedicaocnh = TDate::date2us($data->dtexpedicaocnh);
            if ($data->dtvalidadecnh)
                $data->dtvalidadecnh  = TDate::date2us($data->dtvalidadecnh);
            if ($data->dtpromocao)
                $data->dtpromocao     = TDate::date2us($data->dtpromocao);
            $fer = new TFerramentas();
            
            $master = new servidor_novo;

            if (empty($data->id))
            {
                $senha = 'Academia2017';//$fer->gerar_senha();
                $data->senha = md5($senha);
                $data->importado = 'N';
            }
            else
            {
                $senha = false;
            }
            //Transforma em caixa alta
            setlocale(LC_CTYPE, 'pt_BR.iso-8859-1');
            $data->nome = mb_strtoupper($data->nome,'UTF-8');
            $data->nomemae = mb_strtoupper($data->nomemae,'UTF-8');
            $data->nomepai = mb_strtoupper($data->nomepai,'UTF-8');
            $data->nomeguerra = mb_strtoupper($data->nomeguerra,'UTF-8');
            $data->logradouro = mb_strtoupper($data->logradouro,'UTF-8');
            $data->bairro = mb_strtoupper($data->bairro,'UTF-8');
            $data->complemento = mb_strtoupper($data->complemento,'UTF-8');
            
            //var_dump($data);
            $master->fromArray( (array) $data);
            $master->store(); // save master object
            
            // delete details
            servidor_novo_escolaridade::where('servidor_novo_id', '=', $master->id)->delete();
            //var_dump($param['escolaridade_id']);
            
            if( !empty($param['escolaridade_id']) AND is_array($param['escolaridade_id']) )
            {
                foreach( $param['escolaridade_id'] as $row => $escolaridade)
                {
                    if (!empty($escolaridade) && !empty($param['graduacao'][$row]))
                    {
                        $detail = new servidor_novo_escolaridade;
                        $detail->servidor_novo_id = $master->id;
                        $detail->escolaridade_id  = $param['escolaridade_id'][$row];
                        $detail->graduacao        = $param['graduacao'][$row];
                        $detail->store();
                    }
                }
            }
            
            TTransaction::close(); // close the transaction
            // reload form and session items
            $data->id = $master->id;
            if ($data->dtnascimento)
                $data->dtnascimento   = TDate::date2br($data->dtnascimento);
            if ($data->dtexpedicaocnh)
                $data->dtexpedicaocnh = TDate::date2br($data->dtexpedicaocnh);
            if ($data->dtvalidadecnh)
                $data->dtvalidadecnh  = TDate::date2br($data->dtvalidadecnh);
            if ($data->dtpromocao)
                $data->dtpromocao     = TDate::date2br($data->dtpromocao);            
            //$this->onEdit(array('key'=>$master->id));
            if ($senha != false)
            {
                new TMessage('info','Seu cadastro foi realizado com sucesso<br>' .
                                'Anote o dados para login caso precise alterar algum dado<br>' .
                                'Usuário:' . $data->cpf . '<br>' .
                                'Senha:'   . $senha);
            }
            else
            {
                new TMessage('info', 'Alterações realizadas com sucesso.');
            }
            $this->onEdit(array('key'=>$master->id));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
       
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Rotina Show
 *---------------------------------------------------------------------------------------*/
    //public function show()
    //{
        // check if the datagrid is already loaded
        /*if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();*/
    //}//Fim Módulo
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
    /**
     * Add detail row
     */
    public function addDetailRow($item)
    {
        $fer = new TFerramentas();
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $escolaridade = new TCombo('escolaridade_id[]');
        $graduacao    = new TEntry('graduacao[]');
        
        //Valores
        $escolaridade->addItems($fer->lista_escolaridade());
        
        // set id's
        $escolaridade->setId('escolaridade_id_'.$uniqid);
        $graduacao->setId('graduacao_'.$uniqid);

        // set sizes
        $escolaridade->setSize('200');
        $graduacao->setSize('200');
        
        // set row counter
        $escolaridade->{'data-row'} = $this->detail_row;
        $graduacao->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->escolaridade_id)) { $escolaridade->setValue( $item->escolaridade_id ); }
        if (!empty($item->graduacao)) { $graduacao->setValue( $item->graduacao ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($escolaridade);
        $row->addCell($graduacao);

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($escolaridade);
        $this->form->addField($graduacao);
        
        $this->detail_row ++;
    }
    
}//Fim Classe
