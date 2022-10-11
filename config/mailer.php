<?php
return [
    'default' => [
        //mail[Send messages using PHP's mail() function], SMTP[Send messages using SMTP], Sendmail[Send messages using $Sendmail]
        'Mailer'=>'smtp',  
        //Set the SMTP server to send through
        /**
         * SMTP hosts.
         * Either a single hostname or multiple semicolon-delimited hostnames.
         * You can also specify a different port
         * for each host by using this format: [hostname:port]
         * (e.g. "smtp1.example.com:25;smtp2.example.com").
         * You can also specify encryption type, for example:
         * (e.g. "tls://smtp1.example.com:587;ssl://smtp2.example.com:465").
         * Hosts will be tried in order.
         *
         */
        'Host'=>'smtp.example.com', 
        //Enable SMTP authentication
        'SMTPAuth'=>false, 
        //SMTP username
        'Username'=>'user@example.com', 
        //SMTP password
        'Password'=>'secret', 
        //Enable implicit TLS encryption[ssl,tls]
        'SMTPSecure'=>'',  
        //TCP port to connect to; use 587 if you have set SMTPSecure = `tls` use 465 if you have set SMTPSecure = `ssl`
        'Port'=>25, 
        /**
            Enable verbose debug output
            [0] DEBUG_OFF: No output,
            [1] Debug level to show client -> server messages.,
            [2] Debug level to show client -> server and server -> client messages,
            [3] Debug level to show connection status, client -> server and server -> client messages.
            [4] Debug level to show all messages.
        */
        'SMTPDebug'=>0,
        /**
         * How to handle debug output.
         * Options:
         * * `echo` Output plain-text as-is, appropriate for CLI
         * * `html` Output escaped, line breaks converted to `<br>`, appropriate for browser output
         * * `error_log` Output to error log as configured in php.ini
         * By default PHPMailer will use `echo` if run from a `cli` or `cli-server` SAPI, `html` otherwise.
         * Alternatively, you can provide a callable expecting two params: a message string and the debug level:
         * Debugoutput = function($str, $level) {echo "debug level $level; message: $str";};
         */
        'Debugoutput'=>'error_log',
        //enables exceptions
        'Exception'=>true,
        //Set the language for error messages
        'Language'=>'zh_cn',
        //The character set of the message. [us-ascii],[iso-8859-1],[utf-8],default:[iso-8859-1]
        'CharSet'=>'utf-8'
    ],
    'custom1' => [
        'Mailer'=>'mail',
        'SMTPDebug'=>0,
        'Exception'=>true
    ],
    'custom2' => [
        'Mailer'=>'Sendmail',
        'SMTPDebug'=>0,
        'Exception'=>true
    ]
];
