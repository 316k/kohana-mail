<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Mail sender.
 * 
 * @package   Mail
 * @category  Senders
 * @author    Guillaume Poirier-Morency <guillaumepoiriermorency@gmail.com>
 * @copyright (c) 2013, Hète.ca Inc.
 * @license   BSD-3-Clauses
 */
abstract class Kohana_Mail_Sender {

    /**
     * Return an instance of the specified sender.
     * 
     * @param  string $name    name of the Mail_Sender object to instanciate.
     * @param  array  $options options for the Mail_Sender object.
     * @return Mail_Sender 
     */
    public static function factory($name, array $options = NULL) {

        $class = "Mail_Sender_$name";

        return new $class($options);
    }

    /**
     *
     * @var array 
     */
    protected $headers = array();

    /**
     *
     * @var string 
     */
    protected $body = NULL;

    /**
     *
     * @var array 
     */
    protected $attachments = array();

    /**
     *
     * @var array 
     */
    protected $params = array();

    /**
     * Initialize a Sender with options.
     *
     * @param array $options options for the Mail_Sender object.
     */
    public function __construct(array $options = NULL) {

        $this->options = (array) $options;
    }

    /**
     * Getter-setter for mail headers.
     * 
     * @param  string  $key
     * @param  variant $value
     * @return variant
     */
    public function headers($key = NULL, $value = NULL) {

        if (is_array($key)) {

            $this->headers = $key;

            return $this;
        }

        if ($key === NULL) {

            return $this->headers;
        }

        if ($value === NULL) {

            return Arr::get($this->headers, $key);
        }

        $this->headers[$key] = (string) $value;

        return $this;
    }

    /**
     * Set the Content-Type header of this mail.
     * 
     * Use text/html for HTML email.
     * Use text/plain for plain email.
     * 
     * You may also specify a custom charset using the charset parameter like
     * 
     *     text/html; charset=utf-8
     * 
     * @param  $content_type
     * @return \Mail_Sender
     */
    public function content_type($content_type = NULL) {
        return $this->headers('Content-Type', $content_type);
    }

    /**
     *
     * @param  $sender
     * @return \Mail_Sender
     */
    public function sender($sender = NULL) {
        return $this->headers('Sender', $sender);
    }

    /**
     * 
     * @param  string $cc
     * @return \Mail_Sender
     */
    public function cc($cc = NULL) {
        return $this->headers('Cc', $cc);
    }

    /**
     * 
     * @param  string $bcc
     * @return \Mail_Sender
     */
    public function bcc($bcc = NULL) {
        return $this->headers('Bcc', $bcc);
    }

    /**
     * 
     * @param  string $from
     * @return \Mail_Sender
     */
    public function from($from = NULL) {
        return $this->headers('From', $from);
    }

    /**
     * 
     * @param  string $resent_from
     * @return \Mail_Sender
     */
    public function resent_from($resent_from = NULL) {
        return $this->headers('Resent-From', $resent_from);
    }

    /**
     * 
     * @param  string $resent_to
     * @return \Mail_Sender
     */
    public function resent_to($resent_to = NULL) {
        return $this->headers('Resent-To', $resent_to);
    }

    /**
     * 
     * @param  string $subject
     * @return \Mail_Sender
     */
    public function subject($subject = NULL) {
        return $this->headers('Subject', $subject);
    }

    /**
     * 
     * @param  string $resent_subject
     * @return \Mail_Sender
     */
    public function resent_subject($resent_subject = NULL) {
        return $this->headers('Resent-Subject', $resent_subject);
    }

    /**
     * 
     * @param  string $return_path
     * @return \Mail_Sender
     */
    public function return_path($return_path = NULL) {
        return $this->headers('Return-Path', $return_path);
    }

    /**
     * 
     * @param  string $reply_to
     * @return \Mail_Sender
     */
    public function reply_to($reply_to = NULL) {
        return $this->headers('Reply-To', $reply_to);
    }

    /**
     * 
     * @param  string $mail_reply_to
     * @return \Mail_Sender
     */
    public function mail_reply_to($mail_reply_to = NULL) {
        return $this->headers('Mail-Reply-To', $mail_reply_to);
    }

    /**
     * 
     * @param  string $mail_followup_to
     * @return \Mail_Sender
     */
    public function mail_followup_to($mail_followup_to = NULL) {
        return $this->headers('Mail-Followup-To', $mail_followup_to);
    }

    /**
     * 
     * @param  string $message_id
     * @return \Mail_Sender
     */
    public function message_id($message_id = NULL) {
        return $this->headers('Message-ID', $message_id);
    }

    /**
     * 
     * @param  string $in_reply_to
     * @return \Mail_Sender
     */
    public function in_reply_to($in_reply_to = NULL) {
        return $this->headers('In-Reply-To', $in_reply_to);
    }

    /**
     * 
     * @param  string $references
     * @return \Mail_Sender
     */
    public function references($references = NULL) {
        return $this->headers('References', $references);
    }

    /**
     * Get or set the body of the mail. The body is immediatly evaluated.
     * 
     * If you are assigning an HTML body, specify Content-Type to text/html in
     * headers.
     *
     * @param  variant $body
     * @return string  the body of the mail
     */
    public function body($body = NULL) {

        if ($body === NULL) {

            return $this->body;
        }

        $this->body = (string) $body;

        return $this;
    }

    /**
     * Append an attachment to this mail.
     *
     * You should set at least the Content-Type header.
     *
     * @param string $attachment the raw content of the attachment
     * @param array  $headers    headers for this attachment.
     */
    public function attachment($attachment, array $headers = array()) {

        $this->attachments[] = array(
            'attachment' => $attachment,
            'headers' => $headers
        );

        return $this;
    }

    /**
     * Bind a substitution parameter to this mail sender.
     * 
     * Mail headers values (including subject) and body will be substituted.
     * 
     * @param  string  $name
     * @param  variant $value
     * @return \Mail_Sender
     */
    public function param($name, $value) {

        $this->params[$name] = (string) $value;

        return $this;
    }

    /**
     * Send an email to its receivers.
     * 
     * When fetching an ORM, it is somewhat useful to do 
     * 
     *     $users = $model->as_array('email', 'username');
     * 
     * To send heavy mail, you may use register_shutdown_function so that your
     * mail gets sent after the user has received his mail.
     * 
     * Don't do that for critical mail! You will not be able to access the
     * result of the function.
     * 
     *     register_shutdown_function(array($mailer, 'send'), $users);
     *
     * @param  variant $receivers an email, list of email or associative array of email to name.
     * @return boolean TRUE on success FALSE otherwise.
     */
    public function send($receivers) {

        if (Kohana::$profiling) {

            $benchmark = Profiler::start('Mailer', $this->subject());
        }

        // Check if the receiver is a traversable structure
        $receivers = Arr::is_array($receivers) ? $receivers : array($receivers);

        $to = array();

        foreach ($receivers as $key => $value) {

            // $key is an email, so $value is a name
            if (is_string($key) && Valid::email($key)) {

                $to[] = mb_encode_mimeheader($value) . ' <' . $key . '>';
                // $key is a numeric index, $vaalue is an email
            } else {

                $to[] = $value;
            }
        }

        foreach ($this->headers as $key => $value) {

            // substitute headers with params
            $value = strtr($value, $this->params);

            /**
             * Detects recipient and list of recipient to encode them 
             * accordingly.
             *
             * Regex group are used to detect emails and names in recipient.
             */
            if (preg_match_all('/((\b[\w ]*) <(\w+@\w+\.\w+)>)|(\w+@\w+\.\w+)/', $value, $matches, PREG_SET_ORDER)) {
                
                $recipients = array();

                foreach($matches as $match) {

                    if (count($match) === 4) {
                    
                        $recipients[] = mb_encode_mimeheader($match[2]) . ' <' . $match[3] . '>';    

                    } else {

                        $recipients[] = $match[4];
                    }
                }

                $value = join(', ', $recipients);
            }

            $this->headers[$key] = $value;
        }

        // substitute body values
        $this->body = strtr($this->body, $this->params);

        $status = (bool) $this->_send($to);

        if (isset($benchmark)) {

            Profiler::stop($benchmark);
        }

        return $status;
    }

    /**
     * Implemented by the sender.
     *
     * @param  string $to list of valid RFC emails.
     * @return variant return code of the sender.
     */
    protected abstract function _send(array $to);
}
