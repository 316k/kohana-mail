<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * PHPMailer mail built-in function mailer.
 *
 * @package Mail
 * @category Senders
 * @author Guillaume Poirier-Morency <guillaumepoiriermorency@gmail.com>
 * @copyright (c) 2014, Guillaume Poirier-Morency
 * @license BSD-3-Clauses
 */
class Kohana_Mail_Sender_PHPMailer_Mail extends Mail_Sender_PHPMailer {

	public function __construct(array $options)
	{
                parent::__construct($options);

		$this->mailer->isMail();
	}

}
