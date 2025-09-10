<?php

namespace App\Notifications;

use App\Models\Admin\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantCreationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Tenant $tenant;
    private string $status;
    private ?string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Tenant $tenant, string $status, ?string $message = null)
    {
        $this->tenant = $tenant;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = new MailMessage;

        switch ($this->status) {
            case 'creating':
                $mailMessage
                    ->subject('Tenant Creation Started')
                    ->greeting('Hello!')
                    ->line("Tenant creation has started for '{$this->tenant->name}'.")
                    ->line('You will be notified once the process is complete.')
                    ->action('View Tenant', url("/admin/tenants/{$this->tenant->id}"));
                break;

            case 'completed':
                $mailMessage
                    ->subject('Tenant Creation Completed')
                    ->greeting('Hello!')
                    ->line("Tenant '{$this->tenant->name}' has been created successfully!")
                    ->line('The tenant database has been set up and is ready to use.')
                    ->line("Admin Email: " . ($this->tenant->admin_email ?? 'admin@' . $this->tenant->slug . '.com'))
                    ->action('View Tenant', url("/admin/tenants/{$this->tenant->id}"));
                break;

            case 'failed':
                $mailMessage
                    ->subject('Tenant Creation Failed')
                    ->greeting('Hello!')
                    ->line("Unfortunately, tenant creation failed for '{$this->tenant->name}'.")
                    ->line($this->message ?? 'Please check the logs for more details.')
                    ->action('View Tenant', url("/admin/tenants/{$this->tenant->id}"));
                break;
        }

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'status' => $this->status,
            'message' => $this->message,
            'created_at' => now(),
        ];
    }
}
