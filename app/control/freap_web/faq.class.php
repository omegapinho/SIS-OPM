<?php
class faq extends TPage
{
    private $html;
    
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the notebook
        $master = new TPanelGroup($lbl = new TLabel('Perguntas Comuns sobre o FREAP.'));
        $lbl->setFontColor('red');
        $lbl->setFontStyle('b');
        
        $notebook = new TNotebook(800,360);
        $notebook->setName('Bem-Vindo');
        
        // creates the containers for each notebook page
        $page1 = new TPanelGroup('O que é o FREAP?');
        $page2 = new TPanelGroup('Quem pode contratar?');
        $page3 = new TPanelGroup('Quais são os serviços Disponíveis?');
        $page4 = new TPanelGroup('Como é feito o pagamento?');
        
        $page1->style = "margin: 15px";
        $page2->style = "margin: 15px";
        $page3->style = "margin: 15px";
        $page4->style = "margin: 15px";
        
        // adds two pages in the notebook
        $notebook->appendPage('O que é o FREAP?'          , $page1);
        $notebook->appendPage('Quem pode contratar?'      , $page2);
        $notebook->appendPage('Quais são os serviços?'    , $page3);
        $notebook->appendPage('Como é feito o pagamento?' , $page4);
        
        
        // creates a panel
        //1 painel
        

        $texto =  'O Fundo de Reaparelhamento da Polícia Militar do Estado de Goiás - FREAP - '.
                  'tem por finalidade cobrir despesas relativas ao custeio, a investimentos e '.
                  'inversões financeiras, objetivando a estruturação, o aparelhamento e '.
                  'equipamento da Polícia Militar, bem como o aprimoramento técnico-profissional '.
                  'dos seus integrantes, não podendo ser utilizado para quitação de folha de pagamento '.
                  'de pessoal. É dotado de natureza administrativa, financeira e contábil, '.
                  'sendo administrado por um Conselho Gestor';        
        $text = new TLabel($texto);
        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';
        $table->addRowSet($text);
        $page1->add($table);
        //$page1->addFooter('Panel group footer');


        //2 painel
        $texto =  'A contratação dos Serviços Oferecidos pela Polícia Militar do Estado de Goiás no que tange à ' .
                  'segurança pública, pode ser contratado por qualquer cidadão contribuinte ou por qualquer empresa ' .
                  'jurídica devidamente registrada.';        
        $text = new TLabel($texto);
        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';
        $table->addRowSet($text);
        $page2->add($table);
        //$page2->addFooter('Panel group footer');
                  
                  
        $texto = 'O contribuinte poderá contrar os serviços da Polícia Militar para:<br>
                        - Policiamento ostensivo em eventos com caráter lucrativo;<br>
                        - Policiamento com a utilização de animais;<br>
                        - Apresentações musicais da Banda ou de Músicos da PMGO.<br><br>
                        O contribuinte será, quando usar do serviços, cobrado por:<br>
                        - Extrato de ocorrência policial em rodovias;<br>
                        - Reboque (guincho) e permanência de veículos em pátio da PMGO.';
        $text = new TLabel($texto);
        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';
        $table->addRowSet($text);
        $page3->add($table);
        //$page3->addFooter('Panel group footer');
                        
                        
        $texto = 'Ao solicitar o serviço gera-se uma Documento de Arrecadação Estadual - DARE - que poderá ser pago na ' .
                  'Rede Bancária regular sem maiores dificuldades';
        $text = new TLabel($texto);
        $table = new TTable;
        $table->border = 0;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';
        $table->addRowSet($text);
        $page4->add($table);
        //$page4->addFooter('Panel group footer');
        
        $master->add($notebook);
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        //$vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($master);
        parent::add($vbox);
    }
}
