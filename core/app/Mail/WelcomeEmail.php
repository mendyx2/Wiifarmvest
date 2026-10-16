<?php
// app/Mail/WelcomeEmail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;
 
    public $name;
    public $email;
    public $order_id;
    public $date;

    public function __construct($name, $email, $order_id)
    {
        $this->name = $name;
        $this->email = $email;
        $this->order_id = $order_id;
        $this->date = now()->format('F j, Y');
    }

    public function build()
    {
        return $this->subject('Welcome to Our Service')
                    ->view('emails.welcome');
    }
}