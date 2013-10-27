<?php

require_once __dir__.'/MimeMailParser.class.php';

class ParserWrapper
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new MimeMailParser();
    }
    
    public function parse($mail_message)
    {
        $this->parser->setText( $mail_message );
        $email = array(
            'header'=> $this->escape( $this->parser->getHeadersRaw() ),
            'sender_name'=> $this->get_sender_name(),
            'sender_email'=> $this->get_sender_email(),
            'sent_date'=> $this->get_sent_date(),
            'subject'=> $this->escape( $this->parser->getHeader('subject') ),
            'content'=> $this->get_content(),
        );
        return $email;
    }
    
    private function escape($param)
    {
        $escaped_param = str_replace( '\'','\\\'',str_replace('"','\\"',$param) );
        return $escaped_param;
    }
            
    private function get_sender_name()
    {
        $name = trim(preg_replace('/(<.*>)+/', '', $this->parser->getHeader('from') )," \t\n\r\0\x0B\"");
        return $this->escape( $name );
    }  
    
    private function get_sender_email()
    {
        $email = preg_replace('/[<>]+/','',preg_replace('/^.*\\s/', '', $this->parser->getHeader('from') ));
        return $this->escape( $email );
    }  
            
    private function get_sent_date()
    {
        $sent_date = date( 'Y-m-d H:i:s',strtotime( $this->parser->getHeader('date') ) );
        $sent_date = $sent_date<'1971-01-01 00:00:00' ? date('Y-m-d H:i:s',strtotime('1971-01-01 00:00:00')) : $sent_date ;
        return $sent_date;
    }
    
    private function get_content()
    {
        $html = $this->parser->getMessageBody('html');
        $html = $html!=='' ? $html : $this->parser->getMessageBody('text');
        return $this->escape( $html );
    }
            
}
