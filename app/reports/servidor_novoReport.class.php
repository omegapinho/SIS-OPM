<?php
/**
 * servidor_novoReport Report
 * @author  <your name here>
 */
class servidor_novoReport extends TPage
{
    protected $form; // form
    protected $notebook;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_servidor_novo_report');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Relatório de Novos Militares');
        


        // create the form fields
        $nome = new TEntry('nome');
        $cpf = new TEntry('cpf');
        $rgmilitar = new TEntry('rgmilitar');
        $output_type = new TRadioGroup('output_type');


        // add the fields
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('CPF', $cpf,  100 );
        $this->form->addQuickField('rg', $rgmilitar,  80 );
        $this->form->addQuickField('Output', $output_type,  100 , new TRequiredValidator);



        
        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('pdf');
        $output_type->setLayout('vertical');
        
        // add the action button
        $this->form->addQuickAction(_t('Generate'), new TAction(array($this, 'onGenerate')), 'fa:cog blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'servidor_novoList'));
        $container->add($this->form);
        
        parent::add($container);
    }
    
    /**
     * Generate the report
     */
    function onGenerate()
    {
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // get the form data into an active record
            $formdata = $this->form->getData();
            
            $repository = new TRepository('servidor_novo');
            $criteria   = new TCriteria;
            
            if ($formdata->nome)
            {
                $criteria->add(new TFilter('nome', 'like', "%{$formdata->nome}%"));
            }
            if ($formdata->cpf)
            {
                $criteria->add(new TFilter('cpf', '=', "{$formdata->cpf}"));
            }
            if ($formdata->rgmilitar)
            {
                $criteria->add(new TFilter('rgmilitar', '=', "{$formdata->rgmilitar}"));
            }

           
            $objects = $repository->load($criteria, FALSE);
            $format  = $formdata->output_type;
            
            if ($objects)
            {
                $widths = array(50,100,100,100,100,100,50,100,100,100,100,100,50,100,100,100,100,100,100,50,50,50,50,50,50,50,50,50,50,50,50,50,100,100,100,100,100,100,100,100,100,100,100,100,100,50,100,100,100,50,50,100,100,100,100,100,100,100,100,100,50,50,100,50,100,100,100,100,100,100,100,100,100,100,100,100,50,100,50,100,100,100,100,100,100,100,100,50,100,100);
                
                switch ($format)
                {
                    case 'html':
                        $tr = new TTableWriterHTML($widths);
                        break;
                    case 'pdf':
                        $tr = new TTableWriterPDF($widths);
                        break;
                    case 'rtf':
                        if (!class_exists('PHPRtfLite_Autoloader'))
                        {
                            PHPRtfLite::registerAutoloader();
                        }
                        $tr = new TTableWriterRTF($widths);
                        break;
                }
                
                // create the document styles
                $tr->addStyle('title', 'Arial', '10', 'B',   '#ffffff', '#A3A3A3');
                $tr->addStyle('datap', 'Arial', '10', '',    '#000000', '#EEEEEE');
                $tr->addStyle('datai', 'Arial', '10', '',    '#000000', '#ffffff');
                $tr->addStyle('header', 'Arial', '16', '',   '#ffffff', '#6B6B6B');
                $tr->addStyle('footer', 'Times', '10', 'I',  '#000000', '#A3A3A3');
                
                // add a header row
                $tr->addRow();
                $tr->addCell('servidor_novo', 'center', 'header', 90);
                
                // add titles row
                $tr->addRow();
                $tr->addCell('Id', 'right', 'title');
                $tr->addCell('Nome', 'left', 'title');
                $tr->addCell('Nomeguerra', 'left', 'title');
                $tr->addCell('Nomepai', 'left', 'title');
                $tr->addCell('Nomemae', 'left', 'title');
                $tr->addCell('Sexo', 'left', 'title');
                $tr->addCell('Dtnascimento', 'left', 'title');
                $tr->addCell('Estadocivil', 'left', 'title');
                $tr->addCell('Rgmilitar', 'left', 'title');
                $tr->addCell('Cpf', 'left', 'title');
                $tr->addCell('Ufnascimento', 'left', 'title');
                $tr->addCell('Municipionascimento', 'left', 'title');
                $tr->addCell('Orgaoorigem Id', 'right', 'title');
                $tr->addCell('Telefoneresidencial', 'left', 'title');
                $tr->addCell('Telefonecelular', 'left', 'title');
                $tr->addCell('Telefonetrabalho', 'left', 'title');
                $tr->addCell('Email', 'left', 'title');
                $tr->addCell('Peso', 'left', 'title');
                $tr->addCell('Altura', 'left', 'title');
                $tr->addCell('Codigocorbarba', 'right', 'title');
                $tr->addCell('Codigotipobarba', 'right', 'title');
                $tr->addCell('Codigocorbigote', 'right', 'title');
                $tr->addCell('Codigocorpele', 'right', 'title');
                $tr->addCell('Codigocorcabelo', 'right', 'title');
                $tr->addCell('Codigocorolho', 'right', 'title');
                $tr->addCell('Codigomaoqueescreve', 'right', 'title');
                $tr->addCell('Codigosabenadar', 'right', 'title');
                $tr->addCell('Codigotipobigode', 'right', 'title');
                $tr->addCell('Codigotipocabelo', 'right', 'title');
                $tr->addCell('Codigotipoboca', 'right', 'title');
                $tr->addCell('Codigotipocalvice', 'right', 'title');
                $tr->addCell('Codigotiponariz', 'right', 'title');
                $tr->addCell('Romaneio Calcado', 'left', 'title');
                $tr->addCell('Romaneio Camiseta', 'left', 'title');
                $tr->addCell('Romaneio Camisa', 'left', 'title');
                $tr->addCell('Romaneio Calca', 'left', 'title');
                $tr->addCell('Romaneio Chapeu', 'left', 'title');
                $tr->addCell('Rgcivil', 'left', 'title');
                $tr->addCell('Orgaoexpedicaorg', 'left', 'title');
                $tr->addCell('Ufexpedicaorg', 'left', 'title');
                $tr->addCell('Tituloeleitor', 'left', 'title');
                $tr->addCell('Zonatituloeleitor', 'left', 'title');
                $tr->addCell('Secaotituloeleitor', 'left', 'title');
                $tr->addCell('Municipiotituloeleitoral', 'left', 'title');
                $tr->addCell('Ufdotituloeleitoral', 'left', 'title');
                $tr->addCell('Dtexpedicao Titulo', 'left', 'title');
                $tr->addCell('Cnh', 'left', 'title');
                $tr->addCell('Codcategoriacnh', 'left', 'title');
                $tr->addCell('Categoriacnh', 'left', 'title');
                $tr->addCell('Dtexpedicaocnh', 'left', 'title');
                $tr->addCell('Dtvalidadecnh', 'left', 'title');
                $tr->addCell('Ufcnh', 'left', 'title');
                $tr->addCell('Pis Pasep', 'left', 'title');
                $tr->addCell('Tipo Certidao', 'left', 'title');
                $tr->addCell('Uf Certidao', 'left', 'title');
                $tr->addCell('Municipio Certidao', 'left', 'title');
                $tr->addCell('Matricula Certidao', 'left', 'title');
                $tr->addCell('Numero Certidao', 'left', 'title');
                $tr->addCell('Livro Certidao', 'left', 'title');
                $tr->addCell('Folha Certidao', 'left', 'title');
                $tr->addCell('Dtexpedicao Certidao', 'left', 'title');
                $tr->addCell('Unidadeid', 'right', 'title');
                $tr->addCell('Unidade', 'left', 'title');
                $tr->addCell('Dtpromocao', 'left', 'title');
                $tr->addCell('Postograd', 'left', 'title');
                $tr->addCell('Quadro', 'left', 'title');
                $tr->addCell('Lotacao', 'left', 'title');
                $tr->addCell('Funcao', 'left', 'title');
                $tr->addCell('Status', 'left', 'title');
                $tr->addCell('Situacao', 'left', 'title');
                $tr->addCell('Logradouro', 'left', 'title');
                $tr->addCell('Numero', 'left', 'title');
                $tr->addCell('Quadra', 'left', 'title');
                $tr->addCell('Lote', 'left', 'title');
                $tr->addCell('Complemento', 'left', 'title');
                $tr->addCell('Bairro', 'left', 'title');
                $tr->addCell('Codbairro', 'right', 'title');
                $tr->addCell('Municipio', 'left', 'title');
                $tr->addCell('Codmunicipio', 'right', 'title');
                $tr->addCell('Uf', 'left', 'title');
                $tr->addCell('Cep', 'left', 'title');
                $tr->addCell('Social Residencia', 'left', 'title');
                $tr->addCell('Social Residencia Tipo', 'left', 'title');
                $tr->addCell('Social Esporte', 'left', 'title');
                $tr->addCell('Social Leitura', 'left', 'title');
                $tr->addCell('Social Plano Saude', 'left', 'title');
                $tr->addCell('Social Experiencia Profissional', 'left', 'title');
                $tr->addCell('Social Quantidade Filhos', 'right', 'title');
                $tr->addCell('Educacao Escolaridade', 'left', 'title');
                $tr->addCell('Educacao Graduacao', 'left', 'title');

                
                // controls the background filling
                $colour= FALSE;
                
                // data rows
                foreach ($objects as $object)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($object->id, 'right', $style);
                    $tr->addCell($object->nome, 'left', $style);
                    $tr->addCell($object->nomeguerra, 'left', $style);
                    $tr->addCell($object->nomepai, 'left', $style);
                    $tr->addCell($object->nomemae, 'left', $style);
                    $tr->addCell($object->sexo, 'left', $style);
                    $tr->addCell($object->dtnascimento, 'left', $style);
                    $tr->addCell($object->estadocivil, 'left', $style);
                    $tr->addCell($object->rgmilitar, 'left', $style);
                    $tr->addCell($object->cpf, 'left', $style);
                    $tr->addCell($object->ufnascimento, 'left', $style);
                    $tr->addCell($object->municipionascimento, 'left', $style);
                    $tr->addCell($object->orgaoorigem_id, 'right', $style);
                    $tr->addCell($object->telefoneresidencial, 'left', $style);
                    $tr->addCell($object->telefonecelular, 'left', $style);
                    $tr->addCell($object->telefonetrabalho, 'left', $style);
                    $tr->addCell($object->email, 'left', $style);
                    $tr->addCell($object->peso, 'left', $style);
                    $tr->addCell($object->altura, 'left', $style);
                    $tr->addCell($object->codigocorbarba, 'right', $style);
                    $tr->addCell($object->codigotipobarba, 'right', $style);
                    $tr->addCell($object->codigocorbigote, 'right', $style);
                    $tr->addCell($object->codigocorpele, 'right', $style);
                    $tr->addCell($object->codigocorcabelo, 'right', $style);
                    $tr->addCell($object->codigocorolho, 'right', $style);
                    $tr->addCell($object->codigomaoqueescreve, 'right', $style);
                    $tr->addCell($object->codigosabenadar, 'right', $style);
                    $tr->addCell($object->codigotipobigode, 'right', $style);
                    $tr->addCell($object->codigotipocabelo, 'right', $style);
                    $tr->addCell($object->codigotipoboca, 'right', $style);
                    $tr->addCell($object->codigotipocalvice, 'right', $style);
                    $tr->addCell($object->codigotiponariz, 'right', $style);
                    $tr->addCell($object->romaneio_calcado, 'left', $style);
                    $tr->addCell($object->romaneio_camiseta, 'left', $style);
                    $tr->addCell($object->romaneio_camisa, 'left', $style);
                    $tr->addCell($object->romaneio_calca, 'left', $style);
                    $tr->addCell($object->romaneio_chapeu, 'left', $style);
                    $tr->addCell($object->rgcivil, 'left', $style);
                    $tr->addCell($object->orgaoexpedicaorg, 'left', $style);
                    $tr->addCell($object->ufexpedicaorg, 'left', $style);
                    $tr->addCell($object->tituloeleitor, 'left', $style);
                    $tr->addCell($object->zonatituloeleitor, 'left', $style);
                    $tr->addCell($object->secaotituloeleitor, 'left', $style);
                    $tr->addCell($object->municipiotituloeleitoral, 'left', $style);
                    $tr->addCell($object->ufdotituloeleitoral, 'left', $style);
                    $tr->addCell($object->dtexpedicao_titulo, 'left', $style);
                    $tr->addCell($object->cnh, 'left', $style);
                    $tr->addCell($object->codcategoriacnh, 'left', $style);
                    $tr->addCell($object->categoriacnh, 'left', $style);
                    $tr->addCell($object->dtexpedicaocnh, 'left', $style);
                    $tr->addCell($object->dtvalidadecnh, 'left', $style);
                    $tr->addCell($object->ufcnh, 'left', $style);
                    $tr->addCell($object->pis_pasep, 'left', $style);
                    $tr->addCell($object->tipo_certidao, 'left', $style);
                    $tr->addCell($object->uf_certidao, 'left', $style);
                    $tr->addCell($object->municipio_certidao, 'left', $style);
                    $tr->addCell($object->matricula_certidao, 'left', $style);
                    $tr->addCell($object->numero_certidao, 'left', $style);
                    $tr->addCell($object->livro_certidao, 'left', $style);
                    $tr->addCell($object->folha_certidao, 'left', $style);
                    $tr->addCell($object->dtexpedicao_certidao, 'left', $style);
                    $tr->addCell($object->unidadeid, 'right', $style);
                    $tr->addCell($object->unidade, 'left', $style);
                    $tr->addCell($object->dtpromocao, 'left', $style);
                    $tr->addCell($object->postograd, 'left', $style);
                    $tr->addCell($object->quadro, 'left', $style);
                    $tr->addCell($object->lotacao, 'left', $style);
                    $tr->addCell($object->funcao, 'left', $style);
                    $tr->addCell($object->status, 'left', $style);
                    $tr->addCell($object->situacao, 'left', $style);
                    $tr->addCell($object->logradouro, 'left', $style);
                    $tr->addCell($object->numero, 'left', $style);
                    $tr->addCell($object->quadra, 'left', $style);
                    $tr->addCell($object->lote, 'left', $style);
                    $tr->addCell($object->complemento, 'left', $style);
                    $tr->addCell($object->bairro, 'left', $style);
                    $tr->addCell($object->codbairro, 'right', $style);
                    $tr->addCell($object->municipio, 'left', $style);
                    $tr->addCell($object->codmunicipio, 'right', $style);
                    $tr->addCell($object->uf, 'left', $style);
                    $tr->addCell($object->cep, 'left', $style);
                    $tr->addCell($object->social_residencia, 'left', $style);
                    $tr->addCell($object->social_residencia_tipo, 'left', $style);
                    $tr->addCell($object->social_esporte, 'left', $style);
                    $tr->addCell($object->social_leitura, 'left', $style);
                    $tr->addCell($object->social_plano_saude, 'left', $style);
                    $tr->addCell($object->social_experiencia_profissional, 'left', $style);
                    $tr->addCell($object->social_quantidade_filhos, 'right', $style);
                    $tr->addCell($object->educacao_escolaridade, 'left', $style);
                    $tr->addCell($object->educacao_graduacao, 'left', $style);

                    
                    $colour = !$colour;
                }
                
                // footer row
                $tr->addRow();
                $tr->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 90);
                // stores the file
                if (!file_exists("app/output/servidor_novo.{$format}") OR is_writable("app/output/servidor_novo.{$format}"))
                {
                    $tr->save("app/output/servidor_novo.{$format}");
                }
                else
                {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/servidor_novo.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/servidor_novo.{$format}");
                
                // shows the success message
                new TMessage('info', 'Report generated. Please, enable popups.');
            }
            else
            {
                new TMessage('error', 'No records found');
            }
    
            // fill the form with the active record data
            $this->form->setData($formdata);
            
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', '<b>Error</b> ' . $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
