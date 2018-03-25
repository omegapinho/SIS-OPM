<?php
class PublicView extends TPage
{
    public function __construct()
    {
        parent::__construct();
        
        $reporter_id = new TEntry('reporter_id');
        
        
        $html = new THtmlRenderer('app/resources/public.html');

        // replace the main section variables
        $html->enableSection('main', array());
        
        $panel = new TPanelGroup('Public!');
        $panel->add($html);
        $panel->add($reporter_id);
        $panel->style = 'margin: 100px';
        
        // add the template to the page
        parent::add( $panel );
    }
}
