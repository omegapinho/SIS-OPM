<?php
class TCsvManager
{
    private $separador;
    private $cabecalho;
    private $dados;
    private $path;
    private $arquivo;
    
    public function csv($separador=null, $cabecalho=null, $dados="", $path="", $arquivo="csv")
    {
        #seta as propriedades
        $this->separador = $separador;
        $this->cabecalho = $cabecalho;
        $this->dados = $dados;
        $this->path = $path;
        $this->arquivo = $arquivo;
    }
    public function salvar($param = null)
    {
        #gera string de cabeçalho
        $colunas = "";
        foreach($this->cabecalho as $coluna)
        {
            if ($colunas == "")
            {
                $colunas .= $coluna;
            } 
            else 
            {
                $colunas .= $this->separador.$coluna;
            }
        }
        $saida[] = $colunas;
        #gera string do corpo do arquivo
        foreach ($this->dados as $linha)
        {
            #pega as variaveis do array
            $colunasDados = "";
            foreach($linha as $coluna)
            {
                if ($colunasDados == "")
                {
                    $colunasDados .= $coluna;
                     
                } 
                else 
                {
                    $colunasDados .= $this->separador.$coluna;
                }
            }
            $saida[] = utf8_decode(iconv(mb_detect_encoding($colunasDados), "UTF-8", $colunasDados));
        }
        #verifica se alguma linha foi inserida
        if(count($saida)>1)
        {
            #monta o corpo do CSV
            $corpo = implode("\n", $saida);
            #abre um arquivo para escrita, se o arquivo não existir ele tenta criar
            $fp = fopen ($this->path.$this->arquivo.".csv", "w");// W = sobrescreve
            if($fp <> NULL)
            {
                #escreve no arquivo
                fwrite($fp, $corpo);
                #fecha o arquivo
                fclose($fp);
                #retorno do sistema
                echo "<p>Pronto</p>";
            } 
            else 
            {
                echo "<p>Verifique se a pasta ou o arquivo tem permissão para escrita!</p>";
            }
         } 
         else 
         {
            echo "<p>Sem linhas para importação!</p>";
         }
    }//Fim Módulo
}//Fim Classe
