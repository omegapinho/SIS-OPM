<?php
/**
 * contratoReport Report
 * @author  <your name here>
 */
class contratoReport extends TPage
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
        $this->form = new TQuickForm('form_contrato_report');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Relatório de Contratos de Serviço');
        


        // create the form fields
        $id_servico   = new TDBCombo('id_servico','freap','servico','id','nome','nome');
        $opm_nome     = new TDBCombo('opm_nome','freap','OPM','nome','nome','nome');
        $opm_ext      = new TDBCombo('opm_ext','freap','unidades_ext','nome','nome','nome');
        $data_inicial = new TDate('data_inicial');
        $data_final   = new TDate('data_final');
        $boleto       = new TCombo('boleto');
        $output_type  = new TRadioGroup('output_type');

        // add the fields
        $this->form->addQuickField('Serviço', $id_servico,  400 );
        $this->form->addQuickField('Unidades da PM', $opm_nome,  400 );
        $this->form->addQuickField('Serviços de Unidades Externas', $opm_ext,  400 );
        $this->form->addQuickField('Data Inicial', $data_inicial,  80 );
        $this->form->addQuickField('Data Final', $data_final,  80 );
        $this->form->addQuickField('Quais contratos irei listar?', $boleto,  300 );
        $this->form->addQuickField('Formato de Saída do Relatório', $output_type,  100 , new TRequiredValidator);

        //Mascaras
        $data_inicial->setMask('dd/mm/yyyy');
        $data_final->setMask('dd/mm/yyyy');
        //Tips
        $data_final->setTip('Lista todas as datas de pagamento ocorridas antes esta data. Pode-se fazer um intervalo com a data inicial.');
        $data_inicial->setTip('Lista todas as datas de pagamento ocorridas após esta data. Pode-se fazer intevalo com a data final.');
        $boleto->setTip('Escolha dentre as opções que lhe convém mais. Se deixado em branco, irá desconsiderar qualquer dado neste campo.');
        $opm_nome->setTip('Escolha uma das unidades internas da PM para ver os movimentos.');
        $opm_ext->setTip('Escolha uma das unidades de externas da PM. Caso haja uma unidade interna selecionada esta é que será listada.');
        
        //Valores dos demais Combos
        $itemStatus= array();
        $itemStatus['1'] = 'Listar somente os contratos que geraram boletos';
        $itemStatus['2'] = 'Listar somente os contratos que NÃO geraram boletos';
        
        $boleto->addItems($itemStatus);
        $boleto->setValue('1');
        
        //Formato da saída        
        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('pdf');
        $output_type->setLayout('horizontal');
        
        // add the action button
        $this->form->addQuickAction(_t('Generate'), new TAction(array($this, 'onGenerate')), 'fa:cog blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
            // open a transaction with database 'freap'
            TTransaction::open('freap');
            
            // get the form data into an active record
            $formdata = $this->form->getData();
            
            $repository = new TRepository('contrato');
            $criteria   = new TCriteria;
            
            if ($formdata->id_servico)
            {
                $criteria->add(new TFilter('id_servico', '=', "{$formdata->id_servico}"));
            }
            if ($formdata->opm_nome)
            {
                $criteria->add(new TFilter('opm_nome', '=', "{$formdata->opm_nome}"));
            }
            elseif ($formdata->opm_ext)
            {
                $criteria->add(new TFilter('opm_nome', '=', "{$formdata->opm_ext}"));
            }
            
            if ($formdata->data_inicial || $formdata->data_final)
            {
                if ($formdata->data_inicial && !$formdata->data_final)
                {
                    $criteria->add(new TFilter('data_pagamento', '>=', "{$formdata->data_inicial}"));
                } 
                elseif (!$formdata->data_inicial && $formdata->data_final)
                {
                    $criteria->add(new TFilter('data_pagamento', '<=', "{$formdata->data_final}"));
                }
                else
                {
                    $criteria->add(new TFilter('data_pagamento', '>=', "{$formdata->data_inicial}"));
                    $criteria->add(new TFilter('data_pagamento', '<=', "{$formdata->data_final}"));
                }
                
            }
            if ($formdata->boleto)
            {
                if ($formdata->boleto =='1')
                {
                    $criteria->add(new TFilter('numero_sefaz', 'is not', null));
                }
                elseif ($formdata->boleto=='2')
                {
                    $criteria->add(new TFilter('numero_sefaz', 'is', null));
                }
            }

           
            $objects = $repository->load($criteria, FALSE);
            $format  = $formdata->output_type;
            
            if ($objects)
            {
                $widths = array(100,200,80,80);
                
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
                $tr->addStyle('title', 'Arial', '10', 'B',   '#ffffff', '#9898EA');
                $tr->addStyle('datap', 'Arial', '10', '',    '#000000', '#EEEEEE');
                $tr->addStyle('datai', 'Arial', '10', '',    '#000000', '#ffffff');
                $tr->addStyle('header', 'Arial', '16', '',   '#ffffff', '#494D90');
                $tr->addStyle('footer', 'Times', '10', 'I',  '#000000', '#B1B1EA');
                
                // add a header row
                $tr->addRow();
                $tr->addCell('Serviços Contratados com o FREAP', 'center', 'header', 4);
                
                // add titles row
                $tr->addRow();
                $tr->addCell('No. Item', 'center', 'title');
                $tr->addCell('Unidade', 'center', 'title');
                $tr->addCell('Data Pagamento', 'center', 'title');
                $tr->addCell('Valor em R$', 'center', 'title');

                
                // controls the background filling
                $colour= FALSE;
                
                // data rows
                $total = 0;
                $cont = 1 ;
                foreach ($objects as $object)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($cont, 'right', $style);
                    $tr->addCell($object->opm_nome, 'center', $style);
                    $tr->addCell(TDate::date2br($object->data_pagamento), 'right', $style);
                    $tr->addCell(number_format($object->valor_total,2,'.',''), 'right', $style);
                    $total+=$object->valor_total;

                    
                    $colour = !$colour;
                    $cont ++;
                }
                //Total
                $tr->addRow();
                $tr->addCell('Total Previsto para arrecadar R$ '.number_format($total,2,'.',''), 'right', 'title', 4);
                
                
                // footer row
                $tr->addRow();
                $tr->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 4);
                // stores the file
                if (!file_exists("app/output/contrato.{$format}") OR is_writable("app/output/contrato.{$format}"))
                {
                    $tr->save("app/output/contrato.{$format}");
                }
                else
                {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/contrato.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/contrato.{$format}");
                
                // shows the success message
                new TMessage('info', 'Gerando Relatório. Habilite seu navegador a abrir PopUps');
            }
            else
            {
                new TMessage('error', 'Nenhum regisro encontrado.');
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
