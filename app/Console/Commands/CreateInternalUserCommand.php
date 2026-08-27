<?php

namespace App\Console\Commands;

use App\Actions\ProvisionInternalUser;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateInternalUserCommand extends Command
{
    protected $signature = 'internal:user';

    protected $description = 'Provision an active and verified internal user without privileges';

    public function handle(ProvisionInternalUser $provisionInternalUser): int
    {
        $input = [
            'name' => text('Name', required: true),
            'email' => text('Email address', required: true),
            'password' => password('Password', required: true),
            'password_confirmation' => password('Confirm password', required: true),
        ];

        try {
            $user = $provisionInternalUser->execute($input);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->components->error($message);
                }
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('The internal account could not be provisioned. Check the application log.');

            return self::FAILURE;
        }

        $this->components->info("Internal account {$user->email} was provisioned without roles or positions.");

        return self::SUCCESS;
    }
}
