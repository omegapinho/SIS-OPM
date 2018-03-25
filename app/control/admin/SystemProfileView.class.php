<?php
class SystemProfileView extends TPage
{
    public function __construct()
    {
        parent::__construct();
        
        $html = new THtmlRenderer('app/resources/profile.html');
        $replaces = array();
        
        try
        {
            $area = TSession::getValue('area');
            if (!empty($area) && $area =='PROFESSOR')
            {
                TTransaction::open('sisacad');
                $user= professor::newFromLogin(TSession::getValue('login'));
                $replaces = $user->toArray();
                $replaces['name'] = $area . ' ' . $user->nome;
            }
            else if (!empty($area) && $area =='ALUNO')
            {
                TTransaction::open('sisacad');
                $user= aluno::newFromLogin(TSession::getValue('login'));
                $replaces = $user->toArray();
                $replaces['name'] = $area . ' ' . $user->nome;
            }
            else if (!empty($area) && $area =='SERVIDOR')
            {
                TTransaction::open('permission');
                $user= SystemUser::newFromLogin(TSession::getValue('login'));
                $replaces = $user->toArray();
                $replaces['name'] = $area . ' ' .$user->name;
            }
            else
            {
                TTransaction::open('permission');
                $user= SystemUser::newFromLogin(TSession::getValue('login'));
                $replaces = $user->toArray();
                $replaces['name'] = $user->name;
            }
            $replaces['frontpage'] = $user->frontpage_name;
            $replaces['groupnames'] = $user->getSystemUserGroupNames();
        
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
        
        $html->enableSection('main', $replaces);
        $html->enableTranslation();
        
        $bc = new TBreadCrumb();
        $bc->addHome();
        $bc->addItem('Profile');
        
        $container = TVBox::pack($bc, $html);
        $container->style = 'width:80%';
        parent::add($container);
    }
}
