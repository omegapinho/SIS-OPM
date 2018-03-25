<?php
/**
 * historicotrabalhoReport Report
 * @author  <your name here>
 */
class historicotrabalhoReport extends TPage
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
        $this->form = new TQuickForm('form_historicotrabalho_report');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('historicotrabalho Report');
        


        // create the form fields
        $rgmilitar = new TEntry('rgmilitar');
        $status = new TCombo('status');
        $datainicio = new TDate('datainicio');
        $datafim = new TDate('datafim');
        $turnos_id = new TCombo('turnos_id');
        $opm_id = new TCombo('opm_id');
        $output_type = new TRadioGroup('output_type');


        // add the fields
        $this->form->addQuickField('Rgmilitar', $rgmilitar,  100 );
        $this->form->addQuickField('Status', $status,  100 );
        $this->form->addQuickField('Data Início', $datainicio,  100 );
        $this->form->addQuickField('Data Fim', $datafim,  100 );
        $this->form->addQuickField('Turnos Id', $turnos_id,  50 );
        $this->form->addQuickField('Opm Id', $opm_id,  50 );
        $this->form->addQuickField('Output', $output_type,  100 , new TRequiredValidator);



        
        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('pdf');
        $output_type->setLayout('horizontal');
        
        // add the action button
        $this->form->addQuickAction(_t('Generate'), new TAction(array($this, 'onGenerate')), 'fa:cog blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
            // open a transaction with database 'sicad'
            TTransaction::open('sicad');
            
            // get the form data into an active record
            $formdata = $this->form->getData();
            
            $repository = new TRepository('historicotrabalho');
            $criteria   = new TCriteria;
            
            if ($formdata->rgmilitar)
            {
                $criteria->add(new TFilter('rgmilitar', '=', "{$formdata->rgmilitar}"));
            }
            if ($formdata->status)
            {
                $criteria->add(new TFilter('status', '=', "{$formdata->status}"));
            }
            if ($formdata->datainicio)
            {
                $criteria->add(new TFilter('datainicio', '>=', "{$formdata->datainicio}"));
            }
            if ($formdata->datafim)
            {
                $criteria->add(new TFilter('datafim', '<=', "{$formdata->datafim}"));
            }
            if ($formdata->turnos_id)
            {
                $criteria->add(new TFilter('turnos_id', '=', "{$formdata->turnos_id}"));
            }
            if ($formdata->opm_id)
            {
                $criteria->add(new TFilter('opm_id', '=', "{$formdata->opm_id}"));
            }

           
            $objects = $repository->load($criteria, FALSE);
            $format  = $formdata->output_type;
            
            if ($objects)
            {
                $widths = array(100,100,100,100,50,50);
                
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
                $tr->addCell('historicotrabalho', 'center', 'header', 6);
                
                // add titles row
                $tr->addRow();
                $tr->addCell('Rgmilitar', 'left', 'title');
                $tr->addCell('Status', 'left', 'title');
                $tr->addCell('Data Início', 'left', 'title');
                $tr->addCell('Data Fim', 'left', 'title');
                $tr->addCell('Turnos Id', 'right', 'title');
                $tr->addCell('Opm Id', 'right', 'title');

                
                // controls the background filling
                $colour= FALSE;
                
                // data rows
                foreach ($objects as $object)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($object->rgmilitar, 'left', $style);
                    $tr->addCell($object->status, 'left', $style);
                    $tr->addCell($object->datainicio, 'left', $style);
                    $tr->addCell($object->datafim, 'left', $style);
                    $tr->addCell($object->turnos_id, 'right', $style);
                    $tr->addCell($object->opm_id, 'right', $style);

                    
                    $colour = !$colour;
                }
                
                // footer row
                $tr->addRow();
                $tr->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 6);
                // stores the file
                if (!file_exists("app/output/historicotrabalho.{$format}") OR is_writable("app/output/historicotrabalho.{$format}"))
                {
                    $tr->save("app/output/historicotrabalho.{$format}");
                }
                else
                {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/historicotrabalho.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/historicotrabalho.{$format}");
                
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
