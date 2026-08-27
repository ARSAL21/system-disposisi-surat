<?php

namespace App\Console\Commands;

use App\Actions\AssignSuperAdminRole;
use App\Enums\RoleName;
use App\Exceptions\PrivilegeAssignmentNotAllowed;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class AssignSuperAdminCommand extends Command
{
    protected $signature = 'authorization:super-admin {email? : Email address of the internal account}';

    protected $description = 'Assign the explicit super-admin role to an internal account';

    public function handle(AssignSuperAdminRole $assignSuperAdminRole): int
    {
        $emailArgument = $this->argument('email');
        $email = is_string($emailArgument) && trim($emailArgument) !== ''
            ? trim($emailArgument)
            : text('Internal account email', required: true);

        if (! confirm(sprintf('Assign the %s role to %s?', RoleName::SuperAdmin->value, $email))) {
            $this->components->info('No privilege was changed.');

            return self::SUCCESS;
        }

        try {
            $result = $assignSuperAdminRole->execute($email);
        } catch (PrivilegeAssignmentNotAllowed $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('The super-admin role could not be assigned. Check the application log.');

            return self::FAILURE;
        }

        if (! $result['changed']) {
            $this->components->info(sprintf('%s already has the super-admin role.', $result['user']->email));

            return self::SUCCESS;
        }

        $this->components->info(sprintf('The super-admin role was assigned to %s.', $result['user']->email));
        $this->components->warn('Internal administrative access remains blocked until MFA is enabled and confirmed.');

        return self::SUCCESS;
    }
}
