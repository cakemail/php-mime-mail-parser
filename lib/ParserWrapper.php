<?php

//  @author espaciomore

require_once __dir__.'/MimeMailParser.class.php';

class ParserWrapper
{
    protected $parser;
    private $headers;

    public function __construct()
    {
        $this->parser = new MimeMailParser();
    }
    
    public function parse($mail_message)
    {
        $this->parser->setText( $mail_message );
        $this->headers = $this->parser->getHeaders();
        foreach ($this->headers as $key => $value) {
            if (is_string($key)) {
                $name = strtolower($key);
                $this->headers[ $name ] = $value; 
            }
         } 
        $email = array(
            'header'=> $this->parser->getHeadersRaw(),
            'from'=> $this->headers['from'],
            'sender_name'=> $this->get_sender_name(),
            'sender_email'=> $this->get_sender_email(),
            'sent_date'=> $this->get_sent_date(),
            'subject'=> $this->headers['subject'],
            'content'=> $this->get_content(),
        );
        return $email;
    }
            
    private function get_sender_name()
    {
        $name = trim(preg_replace('/([^\s<@]+@[^\s@>]+)>?$/', '', $this->headers['from'] )," \t\n\r\0\x0B\"><");
        return $name;
    }  
    
    private function get_sender_email()
    {
        if ( (bool)preg_match('/([^\s<@]+@[^\s@>]+)>?$/', $this->headers['from'], $matches) ){
            $email = $matches[1];
        }
        $email = trim($email," \t\n\r\0\x0B\"><");
        return $email;
    }  
            
    private function get_sent_date()
    {
        $sent_date = date( 'Y-m-d H:i:s',strtotime( $this->headers['date'] ) );
        $sent_date = $sent_date<'1971-01-01 00:00:00' ? date('Y-m-d H:i:s',strtotime('1971-01-01 00:00:00')) : $sent_date ;
        return $sent_date;
    }
    
    private function get_content()
    {
        $html = $this->parser->getMessageBody('html');
        $content = ($html!=='' and $html!==false) ? $html : $this->parser->getMessageBody('text');
        return $content;
    }
            
}
