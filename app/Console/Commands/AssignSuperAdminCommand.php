<?php

namespace App\Console\Commands;

use App\Actions\AssignSuperAdminRole;
use App\Actions\RevokeSuperAdminRole;
use App\Enums\RoleName;
use App\Exceptions\PrivilegeAssignmentNotAllowed;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class AssignSuperAdminCommand extends Command
{
    protected $signature = 'authorization:super-admin
                            {email? : Email address of the internal account}
                            {--revoke : Revoke the protected role instead of assigning it}';

    protected $description = 'Assign or revoke the explicit super-admin role through a controlled console flow';

    public function handle(
        AssignSuperAdminRole $assignSuperAdminRole,
        RevokeSuperAdminRole $revokeSuperAdminRole,
    ): int {
        $emailArgument = $this->argument('email');
        $email = is_string($emailArgument) && trim($emailArgument) !== ''
            ? trim($emailArgument)
            : text('Internal account email', required: true);

        $revoke = (bool) $this->option('revoke');
        $operation = $revoke ? 'Revoke' : 'Assign';
        $preposition = $revoke ? 'from' : 'to';

        if (! confirm(sprintf(
            '%s the %s role %s %s?',
            $operation,
            RoleName::SuperAdmin->value,
            $preposition,
            $email,
        ))) {
            $this->components->info('No privilege was changed.');

            return self::SUCCESS;
        }

        try {
            $result = $revoke
                ? $revokeSuperAdminRole->execute($email)
                : $assignSuperAdminRole->execute($email);
        } catch (PrivilegeAssignmentNotAllowed $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('The super-admin role could not be changed. Check the application log.');

            return self::FAILURE;
        }

        if (! $result['changed']) {
            $this->components->info(sprintf(
                $revoke
                    ? '%s does not have the super-admin role.'
                    : '%s already has the super-admin role.',
                $result['user']->email,
            ));

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            $revoke
                ? 'The super-admin role was revoked from %s.'
                : 'The super-admin role was assigned to %s.',
            $result['user']->email,
        ));

        if (! $revoke) {
            $this->components->warn('Internal administrative access remains blocked until MFA is enabled and confirmed.');
        }

        return self::SUCCESS;
    }
}
