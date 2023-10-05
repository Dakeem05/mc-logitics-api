<?php

namespace App\Notifications\Api\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

class ApiResetPassword extends ResetPassword
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    // public function via(object $notifiable): array
    // {
    //     return ['mail'];
    // }

    /**
     * Get the mail representation of the notification.
     */
    protected function resetUrl($notifiable)

    {
        $email = $notifiable->getEmailForPasswordReset();
        return $email;
        // $url = URL::temporarySignedRoute(

        // 'forgot.verify', Carbon::now()->addMinutes(60), ['id' => $notifiable->getKey()]

        // ); // this will basically mimic the email endpoint with get request
        // // $activeUser = ;
        // $user = User::where('email' , $email)->first();
        // $link = $user->forgotLink;
        // // $link = User::where('id', $id)->first()->link;
        // // ->delete();
        // if(is_null($link)){
        //     $token = ForgotPasswordLink::create([
        //         'website_id' => $user->id,
        //         'link' => $url
        //     ]);
        //     return $url;
        // } else {
        //     $link->delete();
        //     $token = ForgotPasswordLink::create([
        //         'website_id' => $user->id,
        //         'link' => $url
        //     ]);
        //     return $url;
        // }
        
    }
}
