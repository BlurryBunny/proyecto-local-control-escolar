<?php

namespace App\Mail;

use App\Models\AcademicProgram;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendRecommendationLetter extends Mailable
{
    use Queueable, SerializesModels;

    // public $id_archive;
    // public $id_recommendation_letter;
    public $academic_program;
    public $appliant;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( $email,  $appliant,  $academic_program )
    {
        $this->appliant = $appliant;
        $this->email = $email;
        $this->academic_program = $academic_program;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('Correos.recommendation-letter.ShowRecommendationLetter');
    }
}
