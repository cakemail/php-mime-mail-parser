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
        if ( is_array($this->headers['from']) ) {
            $this->headers['from'] = implode(' ', $this->headers['from']);
        }
        $email = array(
            'header'=> $this->parser->getHeadersRaw(),
            'from'=> $this->get_imap_mime_header_decode( $this->headers['from'] ),
            'sender_name'=> $this->get_imap_mime_header_decode( $this->get_sender_name() ),
            'sender_email'=> $this->get_sender_email(),
            'sent_date'=> $this->get_sent_date(),
            'subject'=> $this->get_imap_mime_header_decode( $this->headers['subject'] ),
            'content'=> $this->get_content(),
        );
        return $email;
    }
            
    private function get_sender_name()
    {
        $regexp = '/([^\s<@]+@[^\s@>]+)>?$/';
        $name = trim(preg_replace($regexp, '', $this->headers['from'] )," \t\n\r\0\x0B\"><");

        return $name;
    }  
    
    private function get_sender_email()
    {
        $regexp = '/([^\s<@]+@[^\s@>]+)>?/';
        if ( (bool)preg_match($regexp, $this->headers['from'], $matches) ){
            $email = $matches[1];
            $email = trim($email," \t\n\r\0\x0B\"><");
        } else {
            $email = $this->headers['from'];
        }

        return $email;
    }  
            
    private function get_sent_date()
    {
        if ( is_array($this->headers['date']) ) {
            $this->headers['date'] = $this->headers['date'][0];
        }
        $filtered_strtime = preg_replace('/UT/i','UTC',$this->headers['date']);
        $filtered_strtime = preg_replace('/\s\(.+\)/','',$this->headers['date']);
        $filtered_strtime = preg_replace('/\s([0-9]{4})$/'," +$1",$this->headers['date']);
        $filtered_strtime = str_replace('"','',$this->headers['date']);
        $sent_date = date( 'Y-m-d H:i:s',strtotime( $filtered_strtime ) );
        if ( $sent_date <= '1971-01-01 00:00:00' ){
            $sent_date = '1971-01-01 00:00:00';
        }

        return $sent_date;
    }

    private function get_imap_mime_header_decode( $current )
    {
        $decoded_text = '';
        $texts = imap_mime_header_decode( $current );
        foreach ($texts as $i => $decode) {
            $decoded_text = $decoded_text . $decode->{'text'};
        }
        if ( $decoded_text === '') {
            return $current;   
        }

        return $decoded_text; 
    }

    private function get_content()
    {
        $html = $this->parser->getMessageBody('html',$embedded_img = true);
        $content = ($html!=='' and $html!==false) ? $html : $this->parser->getMessageBody('text');
        return $content;
    }
            
}
