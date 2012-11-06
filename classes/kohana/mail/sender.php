<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Sender de mail.
 * 
 * @package Mail
 * @author Guillaume Poirier-Morency <guillaumepoiriermorency@gmail.com>
 * @copyright (c) 2012, Hète.ca
 */
class Kohana_Mail_Sender {

    /**
     *
     * @var Kohana_Mail_Sender 
     */
    protected static $_instances = array();

    /**
     *
     * @return Kohana_Mail_Sender 
     */
    public static function instance($name = "default") {
        return isset(Kohana_Mail_Sender::$_instances[$name]) ? Kohana_Mail_Sender::$_instances[$name] : Kohana_Mail_Sender::$_instances[$name] = new Mail_Sender($name);
    }

    /**
     * Template to be used to build the mail.
     * @var View 
     */
    public $_template = "mail/template";

    /**
     *
     * @var array 
     */
    private $_config;

    /**
     * 
     * @throws Kohana_Exception
     */
    private function __construct($name) {
        $this->_config = Kohana::$config->load("mail.$name");

        $this->_template = View::factory($this->_template);


        if ($this->_config['async']) {
            if (!is_writable($this->_config['queue_path']))
                throw new Kohana_Exception("Folder :folder is not writeable.", array(":folder" => Kohana::$config->load('mail.default.queue_path')));

            if ($this->_config['salt'] === NULL)
                throw new Kohana_Exception("Salt is not defined.");
        }
    }
    
    /**
     * Headers access method. Same as param.
     * @param string $key
     * @param string $value
     * @return type
     */
    public function config($key = NULL, $value = NULL) {
        if ($key === NULL) {
            return $this->_config;
        } else if ($value === NULL) {
            return $this->_config[$key];
        } else {
            $this->_config[$key] = $value;
            return $this;
        }
    }

   

    /**
     * Envoie un courriel à tous les utilisateurs de la variable $receivers
     * basé sur la vue et le modèle spécifié.
     * @param Model_User $receivers
     * @param View $view
     * @param ORM $model 
     * @return Boolean false si au moins un envoie échoue.
     */
    public function send(Model_User $receivers, View $view, ORM $model, $title = NULL) {

        $result = true;

        foreach ($receivers->find_all() as $receiver) {
            $result = $result && $this->send_to_one($receiver, $view, $model, $title);
        }

        return $result;
    }

    /**
     * 
     * @param Model_User|string $receivers
     * @param string $view
     * @param Model $model 
     * @return Boolean résultat de la fonction mail().
     */
    public function send_to_one($receiver, $content, ORM $model = NULL, $title = NULL) {
        $email = NULL;
        if ($title === NULL) {
            $title = $this->_config['default_subject'];
        }

        if ($title === NULL) {
            $title = $this->_config['default_subject'];
        }

        if ($receiver instanceof Model_Auth_User) {
            $email = $receiver->email;
        } elseif (Valid::email($receiver)) {
            $email = $receiver;
            $receiver = Model::factory("Auth_User", array("email" => $receiver));
        }

        if (!Valid::email($receiver->email))
            throw new Kohana_Exception("Le email :email est invalide !", array(":email" => $receiver->email));

        $mail = new Model_Mail_Mail($receiver, $title, $content, $model);

        if ($this->_config['async']) {
            return $this->push($mail);
        } else {
            return $mail->send();
        }
    }

    ////////////////////////////////////////////////////////////////////////////
    // Gestion asynchrome

    /**
     * Ajoute un objet Mail_Mail à la fin de la queue.   
     * @param Mail_Mail $mail
     * @return int
     */
    public function push(Mail_Mail $mail) {
        $serialized_mail = serialize($mail);
        $mail_sha1 = sha1($serialized_mail);
        $filename = $this->salt($mail_sha1);
        return file_put_contents($this->filename_to_path($filename), $serialized_mail);
    }

    /**
     * Converts filename from a mail in the queue to a path.
     * @param string $filename
     * @return string
     */
    private function filename_to_path($filename) {
        return $this->_config['queue_path'] . "/" . $filename;
    }

    /**
     * Retourne l'objet Mail_Mail au début de la queue.
     * Si l'objet est retournable, 
     * @param Mail_Mail $iterations
     */

    /**
     * 
     * @param type $unlink
     * @return boolean|\Mail_Mail FALSE if queue is empty, a Mail_Mail object otherwise.
     * @throws Kohana_Exception
     */
    public function pull($unlink = false) {
        $files = $this->get_queue();

        if (count($files) === 0) {
            return FALSE;
        }

        $file_path = $this->filename_to_path(array_shift($files));

        $file_content_serialized = file_get_contents($file_path);


        if ($file_content_serialized === FALSE) {

            throw new Kohana_Exception("Le contenu du fichier :fichier n'a pas pu être récupéré.",
                    array(":fichier", $file_path));
        }

        $file_content = unserialize($file_content_serialized);

        if ($file_content === FALSE) {
            throw new Kohana_Exception("La désérialization n'a pas fonctionné sur le fichier :file.",
                    array(":file", $file_path));
        }

        if (!($file_content instanceof Model_Mail_Mail)) {
            throw new Kohana_Exception("Le contenu du fichier :fichier n'est pas de type Mail_Mail.",
                    array(":fichier", $file_path));
        }

        if ($unlink) {
            unlink($file_path);
        }


        return $file_content;
    }

    /**
     * Créé un sel à partir d'un timestamp et le sha1 unique d'un mail.
     * @param string $mail_sha1 mail's content sha1.
     * @param int $timestamp 
     * @return string
     */
    public function salt($mail_sha1, $timestamp = NULL) {
        if (!is_integer($timestamp)) {
            $timestamp = time();
        }

        return $timestamp . "~" . sha1($this->_config['salt'] . $mail_sha1 . $timestamp);
    }

    /**
     * Valide un nom de fichier.
     * @param string $path
     * @return type
     */
    public function check($path) {
        $parts = explode("~", $path);

        $validation = Validation::factory($parts)
                ->rule(0, "digit")
                ->rule(1, "alpha_numeric");


        if (count($parts) !== 2 | !$validation->check()) {
            return false;
        }

        $mail_sha1 = sha1_file($this->filename_to_path($path));




        return $this->salt($mail_sha1, $parts[0]) === $path;
    }

    /**
     * 
     * @return type
     */
    public function get_queue() {
        $files = scandir($this->_config['queue_path']);
        return array_filter($files, array($this, "check"));
    }

}

?>