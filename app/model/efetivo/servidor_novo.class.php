<?php
/**
 * servidor_novo Active Record
 * @author  <your-name-here>
 */
class servidor_novo extends TRecord
{
    const TABLENAME = 'efetivo.servidor_novo';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('telefoneresidencial');
        parent::addAttribute('telefonecelular');
        parent::addAttribute('telefonetrabalho');
        parent::addAttribute('email');
        parent::addAttribute('peso');
        parent::addAttribute('altura');
        parent::addAttribute('codigocorbarba');
        parent::addAttribute('codigotipobarba');
        parent::addAttribute('codigocorbigote');
        parent::addAttribute('codigocorpele');
        parent::addAttribute('codigocorcabelo');
        parent::addAttribute('codigocorolho');
        parent::addAttribute('codigomaoqueescreve');
        parent::addAttribute('codigosabenadar');
        parent::addAttribute('codigotipobigode');
        parent::addAttribute('codigotipocabelo');
        parent::addAttribute('codigotipoboca');
        parent::addAttribute('codigotipocalvice');
        parent::addAttribute('codigotiponariz');
        parent::addAttribute('tituloeleitor');
        parent::addAttribute('zonatituloeleitor');
        parent::addAttribute('secaotituloeleitor');
        parent::addAttribute('municipiotituloeleitoral');
        parent::addAttribute('ufdotituloeleitoral');
        parent::addAttribute('cnh');
        parent::addAttribute('codcategoriacnh');
        parent::addAttribute('categoriacnh');
        parent::addAttribute('dtexpedicaocnh');
        parent::addAttribute('dtvalidadecnh');
        parent::addAttribute('ufcnh');
        parent::addAttribute('unidadeid');
        parent::addAttribute('unidade');
        parent::addAttribute('nome');
        parent::addAttribute('sexo');
        parent::addAttribute('dtnascimento');
        parent::addAttribute('nomeguerra');
        parent::addAttribute('dtpromocao');
        parent::addAttribute('postograd');
        parent::addAttribute('quadro');
        parent::addAttribute('lotacao');
        parent::addAttribute('funcao');
        parent::addAttribute('status');
        parent::addAttribute('situacao');
        parent::addAttribute('rgmilitar');
        parent::addAttribute('cpf');
        parent::addAttribute('nomepai');
        parent::addAttribute('nomemae');
        parent::addAttribute('logradouro');
        parent::addAttribute('numero');
        parent::addAttribute('quadra');
        parent::addAttribute('lote');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('codbairro');
        parent::addAttribute('municipio');
        parent::addAttribute('codmunicipio');
        parent::addAttribute('uf');
        parent::addAttribute('cep');
        parent::addAttribute('rgcivil');
        parent::addAttribute('orgaoexpedicaorg');
        parent::addAttribute('ufexpedicaorg');
        parent::addAttribute('orgaoorigem_id');
        parent::addAttribute('senha');
        parent::addAttribute('romaneio_calcado');
        parent::addAttribute('romaneio_camiseta');
        parent::addAttribute('romaneio_camisa');
        parent::addAttribute('romaneio_calca');
        parent::addAttribute('romaneio_chapeu');
        parent::addAttribute('social_residencia');
        parent::addAttribute('social_residencia_tipo');
        parent::addAttribute('social_esporte');
        parent::addAttribute('social_leitura');
        parent::addAttribute('social_plano_saude');
        parent::addAttribute('social_experiencia_profissional');
        parent::addAttribute('social_quantidade_filhos');
        parent::addAttribute('educacao_escolaridade');
        parent::addAttribute('educacao_graduacao');
        parent::addAttribute('importado');
        //Colocado em 13/09/2017
        parent::addAttribute('estadocivil');
        //Colocado 16/10/2017
        parent::addAttribute('ufnascimento');
        parent::addAttribute('municipionascimento');
        parent::addAttribute('reservista');
        parent::addAttribute('reservistaorgao');
        parent::addAttribute('reservistacategoria');
        parent::addAttribute('reservistadtexpedicao');
        parent::addAttribute('pis_pasep');
        
    }
    /**
     * Method get_postograd
     * Sample of usage: $servidor_novo->postograd->attribute;
     * @returns orgaoorigem instance
     */
    /*public function get_orgaoorigem()
    {
        // loads the associated object
        if (empty($this->orgaoorigem))
            $this->orgaoorigem = new orgaoorigem($this->orgaoorigem_id);
    
        // returns the associated object
        return $this->orgaoorigem;
    }*/

}
